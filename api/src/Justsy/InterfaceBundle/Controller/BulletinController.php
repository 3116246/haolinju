<?php

namespace Justsy\InterfaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;


class BulletinController extends Controller
{
  //8.1	取得未读公告数
  public function getUnreadNumAction()
  {    
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
	  $user = $this->get('security.context')->getToken()->getUser();
    
    $da = $this->get('we_data_access'); 

    try 
    {
      $sql = "select count(*) num from we_notify a where a.notify_type='01' and a.notify_staff=?";
      $params = array();
      $params[] = (string)$user->getUserName();
      
      $da = $this->get('we_data_access');
      $ds = $da->GetData("we_notify", $sql, $params);
      
      $re["unreadnum"] = $ds["we_notify"]["rows"][0]["num"];
    } 
    catch (\Exception $e) 
    {
      $re["returncode"] = ReturnCode::$SYSERROR;
      $this->get('logger')->err($e);
    }
    
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  
  //8.2	取得未读公告
  public function getUnreadAction()
  {    
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
	  $user = $this->get('security.context')->getToken()->getUser();
    
    $da = $this->get('we_data_access'); 
    
    try 
    {
      $sql = "select a.bulletin_id, a.circle_id, a.group_id, a.bulletin_date, a.bulletin_desc, a.bulletin_staff,
  b.nick_name bulletin_staff_nickname, b.photo_path 
from we_bulletin a, we_staff b
where a.bulletin_staff=b.login_account
  and exists(select 1 from we_notify wn where wn.notify_type='01' and wn.msg_id=a.bulletin_id and wn.notify_staff=?)";
      $params = array();
      $params[] = (string)$user->getUserName();
          
      $sql .= " order by a.bulletin_date desc";

      $ds = $da->GetData("we_bulletin", $sql, $params);
      
			$sql="delete from we_notify where notify_type='01' and notify_staff=?";
      $params = array();
      $params[] = (string)$user->getUserName();
      $da->ExecSQL($sql, $params);
      
      $re["bulletins"] = $this->genBulletin($ds["we_bulletin"]["rows"]);
    } 
    catch (\Exception $e) 
    {
      $re["returncode"] = ReturnCode::$SYSERROR;
      $this->get('logger')->err($e);
    }
    
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  
  //8.3	取得公告（每次15条）
  public function getBulletinAction()
  {    
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
	  $user = $this->get('security.context')->getToken()->getUser();
    
    $da = $this->get('we_data_access'); 
    
    $circle_id = $request->get("circle_id");
    $group_id = $request->get("group_id");
    $last_end_id = $request->get("last_end_id");

    try 
    {
      $circle_where = " and a.circle_id=?";
      if (empty($circle_id)) $circle_where = " and a.circle_id in (select circle_id from we_circle_staff wcs where wcs.login_account=?)";
      
      $sql = "select a.bulletin_id, a.circle_id, a.group_id, a.bulletin_date, a.bulletin_desc, a.bulletin_staff,
  b.nick_name bulletin_staff_nickname, b.photo_path 
from we_bulletin a, we_staff b
where a.bulletin_staff=b.login_account
  $circle_where ";
      $params = array();
      if (empty($circle_id)) 
        $params[] = (string)$user->getUserName();
      else 
        $params[] = (string)$circle_id;
      
      if (!empty($group_id))
      {
        $sql .= " and a.group_id = ? ";
        $params[] = (string)$group_id;
      }
      else
      {
        $sql .= " and a.group_id = 'ALL' ";
      }
      if (!empty($last_end_id))
      {
        $sql .= " and (0+bulletin_id)<?";
        $params[] = (float)$last_end_id;
      }
    
      $sql .= " order by a.bulletin_date desc";
      $sql .= " limit 0, 15 ";

      $ds = $da->GetData("we_bulletin", $sql, $params);
           
			$sql="delete from we_notify where notify_type='01' and notify_staff=?";
      $params = array();
      $params[] = (string)$user->getUserName();
      $da->ExecSQL($sql, $params);
       
      $re["bulletins"] = $this->genBulletin($ds["we_bulletin"]["rows"]);
    } 
    catch (\Exception $e) 
    {
      $re["returncode"] = ReturnCode::$SYSERROR;
      $this->get('logger')->err($e);
    }
    
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  
  //8.4	发布公告
  public function pushBulletinAction()
  {    
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
	  $user = $this->get('security.context')->getToken()->getUser();
    
    $da = $this->get('we_data_access'); 
    
    $circle_id = $request->get("circle_id");
    $group_id = $request->get("group_id");
    $bulletin_desc = $request->get("bulletin_desc");

    try 
    {
      if (empty($circle_id) || empty($bulletin_desc)) throw new \Exception("param is null");
      
      if (empty($group_id)) $group_id = "ALL";
      
      //判断是否有权限
      if (!$this->checkCanPushBulletin($da, $user, $circle_id))
      {
        $re["returncode"] = ReturnCode::$NOTAUTHORIZED;
      }
      else      //发布 
      {
        $bulletin_id=\Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_bulletin", "bulletin_id");
        
        $sqls = array();
        $all_params = array();
      
        $sql = "insert into we_bulletin (bulletin_date,bulletin_desc,bulletin_id,group_id,circle_id,bulletin_staff) values (CURRENT_TIMESTAMP(), ?, ?, ?, ?,?)";
        $params = array();
        $params[] = (string)$bulletin_desc;
        $params[] = (string)$bulletin_id;
        $params[] = (string)$group_id;
        $params[] = (string)$circle_id;
        $params[] = (string)$user->getUserName();
        
        $sqls[] = $sql;
        $all_params[] = $params;
        
        if ($group_id == "ALL")
        {
          $sql = "insert into we_notify (notify_type,msg_id,notify_staff) 
select '01', ?, login_account
from we_circle_staff where circle_id=?";        
          $params = array();
          $params[] = (string)$bulletin_id;
          $params[] = (string)$circle_id;
        }
        else 
        {
          $sql = "insert into we_notify (notify_type,msg_id,notify_staff) 
select '01', ?, login_account
we_group_staff where group_id=?";        
          $params = array();
          $params[] = (string)$bulletin_id;
          $params[] = (string)$group_id;          
        }
        
        $sqls[] = $sql;
        $all_params[] = $params;
        
        $ds = $da->ExecSQLs($sqls, $all_params);
      }
    } 
    catch (\Exception $e) 
    {
      $re["returncode"] = ReturnCode::$SYSERROR;
      $this->get('logger')->err($e);
    }
    
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  
  public function checkCanPushBulletin($da, $user, $circle_id) 
  {
    $sql = "select count(*) c
from we_circle a
where a.circle_id=?
  and ? in (create_staff, manager)";

    $params = array();
    $params[] = (string)$circle_id;
    $params[] = (string)$user->getUserName();
  
    $ds = $da->GetData("we_circle", $sql, $params);
    
    return $ds["we_circle"]["rows"][0]["c"] > 0;
  }
  
  
  public function genBulletin(&$rows) 
  {
    $bulletins = array();
    foreach ($rows as &$row) 
    {
       $bulletin = array();
       $bulletin["bulletin_id"] = $row["bulletin_id"];
       $bulletin["circle_id"] = $row["circle_id"];
       $bulletin["group_id"] = $row["group_id"];
       $bulletin["bulletin_date"] = $row["bulletin_date"];
       $bulletin["bulletin_desc"] = $row["bulletin_desc"];
       $bulletin["bulletin_staff"] = $row["bulletin_staff"];
       $bulletin["bulletin_staff_nickname"] = $row["bulletin_staff_nickname"];
       $bulletin["bulletin_staff_photo"] = $row["photo_path"];
       $bulletins[] = $bulletin;
    } 
    
    return $bulletins;
  }
  
}
