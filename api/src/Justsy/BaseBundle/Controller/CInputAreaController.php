<?php

namespace Justsy\BaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\BaseBundle\Common\Utils;

class CInputAreaController extends Controller
{
    public $groups_array;
    public $groups;
    public $staffs;
    public $FaceEmotes;
    
    public function indexAction($network_domain, $name) 
    {
      $user = $this->get('security.context')->getToken()->getUser();
      $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
      $curr_circle_id = $user->get_circle_id($network_domain);
      $sqls = array();
      $params = array();
      
      //取出当前圈子中该用户参与的群组
      $sql1 = "select a.group_id, a.group_name, a.group_photo_path, concat('$FILE_WEBSERVER_URL', ifnull(a.group_photo_path, '')) group_photo_url
from we_groups a, we_group_staff b 
where a.group_id=b.group_id
  and b.login_account=?
  and a.circle_id=?";
      $params1 = array();
      $params1[] = (string)$user->getUserName();
      $params1[] = (string)$curr_circle_id;
      
      $sqls[] = $sql1;
      $params[] = $params1;
      
      //当用户数小于100时，取出当前圈子中所有用户
      $sql1 = "select a.login_account, ifnull(a.nick_name, a.login_account) nick_name
from we_staff a, we_circle_staff b
where a.login_account=b.login_account
  and b.circle_id=?";
      $params1 = array();
      $params1[] = (string)$curr_circle_id;
      
      $sqls[] = $sql1;
      $params[] = $params1;
      
      $da = $this->get('we_data_access');
      $da->PageSize = 100;
      $da->PageIndex = 0;
      $ds = $da->GetDatas(array("we_groups", "we_staff"), $sqls, $params);
            
      $this->groups_array = $ds["we_groups"]["rows"];
      $this->groups = json_encode($ds["we_groups"]["rows"]);
      $this->staffs = json_encode($ds["we_staff"]["recordcount"] > count($ds["we_staff"]["rows"]) ? array() : $ds["we_staff"]["rows"]);
      $para=array();
     	$publish= $this->get("request")->get('publish');
      if($name=="foo")
      {      	
      	$para=array('this' => $this,'ismanager'=> $user->is_in_manager_circles($network_domain), 'network_domain' => $network_domain,'publish'=>$publish);
      	return $this->render('JustsyBaseBundle:CInputArea:index.html.twig',$para);
      }
      elseif($name=="group")
      {
      	$g_id = $this->get("request")->get('groupid');
      	$groupObj = new \Justsy\BaseBundle\Controller\GroupController();
      	$groupObj->setContainer($this->container);
      	$isManager=$groupObj->isManager($g_id ,$user->getUserName());
      	
      	$para=array('this' => $this,'ismanager'=> $isManager, 'network_domain' => $network_domain,'groupid'=> $g_id,'publish'=>$publish);
      	
      	return $this->render('JustsyBaseBundle:CInputArea:groupInputArea.html.twig', $para);
      }      
      elseif($name=="notice")
      {
      	$para=array('this' => $this, 'network_domain' => $network_domain,'publish'=>$publish);
      	return $this->render('JustsyBaseBundle:Notice:pushNotice.html.twig',$para);
      }
    }
    //index for pc 
    public function indexpcAction($network_domain, $name) 
    {
      $user = $this->get('security.context')->getToken()->getUser();
      $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
      $curr_circle_id = $user->get_circle_id($network_domain);
      
      $sqls = array();
      $params = array();
      
      //取出当前圈子中该用户参与的群组
      $sql1 = "select a.group_id, a.group_name, a.group_photo_path, concat('$FILE_WEBSERVER_URL', ifnull(a.group_photo_path, '')) group_photo_url
from we_groups a, we_group_staff b 
where a.group_id=b.group_id
  and b.login_account=?
  and a.circle_id=?";
      $params1 = array();
      $params1[] = (string)$user->getUserName();
      $params1[] = (string)$curr_circle_id;
      
      $sqls[] = $sql1;
      $params[] = $params1;
      
      //当用户数小于100时，取出当前圈子中所有用户
      $sql1 = "select a.login_account, ifnull(a.nick_name, a.login_account) nick_name
from we_staff a, we_circle_staff b
where a.login_account=b.login_account
  and b.circle_id=?";
      $params1 = array();
      $params1[] = (string)$curr_circle_id;
      
      $sqls[] = $sql1;
      $params[] = $params1;
      
      $da = $this->get('we_data_access');
      $da->PageSize = 100;
      $da->PageIndex = 0;
      $ds = $da->GetDatas(array("we_groups", "we_staff"), $sqls, $params);
            
      $this->groups_array = $ds["we_groups"]["rows"];
      $this->groups = json_encode($ds["we_groups"]["rows"]);
      $this->staffs = json_encode($ds["we_staff"]["recordcount"] > count($ds["we_staff"]["rows"]) ? array() : $ds["we_staff"]["rows"]);
      if($name=="foo")
      {      	
      	return $this->render('JustsyBaseBundle:CInputArea:index_pc.html.twig', array('this' => $this,'ismanager'=> $user->is_in_manager_circles($network_domain), 'network_domain' => $network_domain));
      }
      elseif($name=="group")
      {
      	$g_id = $this->get("request")->get('groupid');
      	$groupObj = new \Justsy\BaseBundle\Controller\GroupController();
      	$groupObj->setContainer($this->container);
      	$isManager=$groupObj->isManager($g_id ,$user->getUserName());
      	return $this->render('JustsyBaseBundle:CInputArea:groupInputArea_pc.html.twig', array('this' => $this,'ismanager'=> $isManager, 'network_domain' => $network_domain,'groupid'=> $g_id));
      }      
      elseif($name=="notice")
      {
      	return $this->render('JustsyBaseBundle:Notice:pushNotice.html.twig', array('this' => $this, 'network_domain' => $network_domain));
      }
    }
    
    public function indexfriendAction()
    {
        return $this->render('JustsyBaseBundle:CInputArea:index_friend.html.twig', array('this' => $this,'ismanager'=>"", 'network_domain' => "",'groupid'=>""));
    }
    
    //载入表情
    public function loadFaceEmoteAction() 
    {
      return $this->render('JustsyBaseBundle:CInputArea:faceemote.html.twig', array('this' => $this));
    }
    
    public function loadFaceEmoteJsonAction() 
    {
      $FaceEmotesArray = array();
      
      foreach (\Justsy\BaseBundle\Common\Face::$FaceEmotes_GIF as $key => $value) 
      {
        $FaceEmotesArray[] = array("key" => $key, "value" => $value);
      }
      
      $response = new Response(json_encode($FaceEmotesArray));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }
    
    public function queryCircleStaff100Action() 
    {
      $request = $this->getRequest();
      $user = $this->get('security.context')->getToken()->getUser();
      $query = $request->get('query');
      $network_domain = $request->get('network_domain');
      
      $sql1 = "select a.login_account, ifnull(a.nick_name, a.login_account) nick_name
from we_staff a, we_circle_staff b
where a.login_account=b.login_account
  and not exists (select 1 from we_micro_account m where m.eno=a.eno and a.login_account= m.number)
  and b.circle_id=?
  and (a.login_account like concat('%', ? ,'%') or a.nick_name like concat('%', ? ,'%'))";
      $params1 = array();
      $params1[] = (string)$user->get_circle_id($network_domain);
      $params1[] = (string)$query;
      $params1[] = (string)$query;
  
      $da = $this->get('we_data_access');
//      $da->PageSize = 10;
//      $da->PageIndex = 0;
      $sql1 .= " limit 0, 10 ";
      $ds = $da->GetData("we_staff", $sql1, $params1);
      
      $response = new Response(json_encode($ds["we_staff"]["rows"]));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }
    //朋友圈子@
    public static function genAtMe9999($da, $AConvContent, $conv_id, $user,$ctl=null)
    {
      try 
      {
        $matchs = null;
        $matchcount = preg_match_all("/@(.*?)(\{([^\{\}]*)\}|(?=&nbsp;|<br|@|\'|\"| |\.|\,|\/|\!|\:|$))/", $AConvContent, $matchs);
        
        if ($matchcount == 0) return;
        
        $sql_login_account = "select a.login_account ,a.fafa_jid
          from we_staff a, we_enterprise b
          where a.eno=b.eno
            and a.nick_name=? 
            and b.eshortname = ? and not exists (select 1 from we_convers_notify where conv_id=? and a.login_account=cc_login_account)
            and exists (select 1 from we_convers_list wcl where wcl.post_to_circle='9999' and wcl.conv_id=?)";
        
        $sqls = array();
        
        $sql = "insert into we_staff_at_me(login_account, conv_id) 
                select  ?, ? 
                from dual
                where not exists(select 1 from we_staff_at_me c where c.login_account=? and c.conv_id=?)";
        $sqls[] = $sql;
        
        $sql = "delete a from we_staff_at_me a 
                where a.login_account=?
                  and (0+a.conv_id) < (select t1.conv_id from (select 0+b.conv_id as conv_id from we_staff_at_me b where b.login_account=? order by 0+b.conv_id desc limit 0, 100) as t1 order by t1.conv_id limit 0, 1)";
        $sqls[] = $sql;
        
        $sql = "insert into we_notify(notify_type, msg_id, notify_staff) 
                select '03', ?, ?
                from dual
                where exists(select 1 from we_convers_list wcl, we_circle_staff wcs where wcl.post_to_circle=wcs.circle_id and wcl.conv_id=? and wcs.login_account=?)
                  and not exists(select 1 from we_notify c where c.notify_type='03' and c.msg_id=? and c.notify_staff=?)";
        $sqls[] = $sql;
        
        $sql = "insert into we_staff_last_at(login_account, at_date, at_login_account)
                select ?, CURRENT_TIMESTAMP(), ?
                from dual
                where not exists(select 1 from we_staff_last_at c where c.login_account=? and c.at_login_account=?) ";
        $sqls[] = $sql;
        
        $sql = "delete a from we_staff_last_at a 
                where a.login_account=? 
                  and a.at_date < (select min(t1.at_date) from (select b.at_date from we_staff_last_at b where b.login_account=? order by b.at_date desc limit 0, 10) as t1)";
        $sqls[] = $sql;
        
        $imJids = array();
        for ($i = 0; $i < $matchcount; $i++)
        {
          $params = array();
          $params[] = (string)$matchs[1][$i];
          $params[] = (string)($matchs[3][$i] == "" ? $user->eshortname : $matchs[3][$i]);
          $params[]=  (string)$conv_id;
          $params[]=  (string)$conv_id;
          
          $ds = $da->GetData("we_staff", $sql_login_account, $params);
          if ($ds["we_staff"]["recordcount"] == 0) continue;
          
          $all_params = array();
          
          $params = array();
          $params[] = (string)$ds["we_staff"]["rows"][0]["login_account"];
          $params[] = (string)$conv_id;  
          $params[] = (string)$ds["we_staff"]["rows"][0]["login_account"];
          $params[] = (string)$conv_id;          
          $all_params[] = $params;
          
          $params = array();
          $params[] = (string)$ds["we_staff"]["rows"][0]["login_account"];
          $params[] = (string)$ds["we_staff"]["rows"][0]["login_account"];
          $all_params[] = $params;
          
          $params = array();
          $params[] = (string)$conv_id;
          $params[] = (string)$ds["we_staff"]["rows"][0]["login_account"];
          $params[] = (string)$conv_id;
          $params[] = (string)$ds["we_staff"]["rows"][0]["login_account"];
          $params[] = (string)$conv_id;
          $params[] = (string)$ds["we_staff"]["rows"][0]["login_account"];
          $all_params[] = $params;
          
          $params = array();
          $params[] = (string)$user->getUserName();
          $params[] = (string)$ds["we_staff"]["rows"][0]["login_account"];
          $params[] = (string)$user->getUserName();
          $params[] = (string)$ds["we_staff"]["rows"][0]["login_account"];
          $all_params[] = $params;
          
          $params = array();
          $params[] = (string)$user->getUserName();  
          $params[] = (string)$user->getUserName();
          $all_params[] = $params;
          
          $da->ExecSQLs($sqls, $all_params);
          $imJids[] = $ds["we_staff"]["rows"][0]["fafa_jid"];
        }
        if($ctl!=null && count($imJids)>0)
        {
		          try{
		          	//获取是否是在评论中@的
		          	$get_sql = "select conv_root_id from we_convers_list where conv_id=?";
		          	$ds = $da->GetData("get_sql",$get_sql,array((string)$conv_id));
		          	if($ds && count($ds["get_sql"]["rows"])>0)
		          	{
		          				$conv_id=$ds["get_sql"]["rows"][0]["conv_root_id"];
		          	}
		            //发送即时消息
		            $link = $ctl->get('router')->generate("JustsyBaseBundle_view_oneconv",array("conv_root_id"=> $conv_id),true);
                $linkButtons=Utils::makeBusButton(array(array("code"=>"action","text"=>"查看","value"=> "","blank"=>"true")));
			          $message = "您的好友在朋友圈上提到了你，快去看看吧！";
			          Utils::sendImMessage("",implode(",",$imJids),"atme",$message,$ctl,$link,$linkButtons,"","",false,Utils::$atme_code);
			        }catch (\Exception $e) 
				      {
				          $ctl->get('logger')->err($e);
				      }
		    }
      } 
      catch (\Exception $e) 
      {
        throw $e;
      }
    }
    
    public static function genAtMe($da, $AConvContent, $conv_id, $user,$ctl=null)
    {
      try 
      {
        $matchs = null;
        $matchcount = preg_match_all("/@(.*?)(\{([^\{\}]*)\}|(?=&nbsp;|<br|@|\'|\"| |\.|\,|\/|\!|\:|$))/", $AConvContent, $matchs);
        
        if ($matchcount == 0) return;
        
        $sql_login_account = "select a.login_account ,a.fafa_jid
          from we_staff a, we_enterprise b
          where a.eno=b.eno
            and a.nick_name=? 
            and b.eshortname = ? and not exists (select 1 from we_convers_notify where conv_id=? and a.login_account=cc_login_account)
            and exists (select 1 from we_convers_list wcl, we_circle_staff wcs
          			  where wcl.post_to_circle=wcs.circle_id and wcl.conv_id=?
                          and wcs.login_account=a.login_account
                          and (wcl.post_to_group in (select 'ALL' from dual 
                                                 union select case when wcl.login_account=wcs.login_account then 'PRIVATE' else '' end from dual
                                                 union select c.group_id from we_group_staff c where c.login_account=wcs.login_account)
                          	or exists(select 1 from we_convers_notify wcn where wcn.conv_id=wcl.conv_id and wcn.cc_login_account=wcs.login_account)))";
                  
        $sqls = array();
        
        $sql = "insert into we_staff_at_me(login_account, conv_id) 
                select  ?, ? 
                from dual
                where not exists(select 1 from we_staff_at_me c where c.login_account=? and c.conv_id=?)";
        $sqls[] = $sql;
        
        $sql = "delete a from we_staff_at_me a 
                where a.login_account=?
                  and (0+a.conv_id) < (select t1.conv_id from (select 0+b.conv_id as conv_id from we_staff_at_me b where b.login_account=? order by 0+b.conv_id desc limit 0, 100) as t1 order by t1.conv_id limit 0, 1)";
        $sqls[] = $sql;
        
        $sql = "insert into we_notify(notify_type, msg_id, notify_staff) 
                select '03', ?, ?
                from dual
                where exists(select 1 from we_convers_list wcl, we_circle_staff wcs where wcl.post_to_circle=wcs.circle_id and wcl.conv_id=? and wcs.login_account=?)
                  and not exists(select 1 from we_notify c where c.notify_type='03' and c.msg_id=? and c.notify_staff=?)";
        $sqls[] = $sql;
        
        $sql = "insert into we_staff_last_at(login_account, at_date, at_login_account)
                select ?, CURRENT_TIMESTAMP(), ?
                from dual
                where not exists(select 1 from we_staff_last_at c where c.login_account=? and c.at_login_account=?) ";
        $sqls[] = $sql;
        
        $sql = "delete a from we_staff_last_at a 
                where a.login_account=? 
                  and a.at_date < (select min(t1.at_date) from (select b.at_date from we_staff_last_at b where b.login_account=? order by b.at_date desc limit 0, 10) as t1)";
        $sqls[] = $sql;
        
        $imJids = array();
        for ($i = 0; $i < $matchcount; $i++)
        {
          $params = array();
          $params[] = (string)$matchs[1][$i];
          $params[] = (string)($matchs[3][$i] == "" ? $user->eshortname : $matchs[3][$i]);
          $params[]=  (string)$conv_id;
          $params[]=  (string)$conv_id;
          
          $ds = $da->GetData("we_staff", $sql_login_account, $params);
          if ($ds["we_staff"]["recordcount"] == 0) continue;
          
          $all_params = array();
          
          $params = array();
          $params[] = (string)$ds["we_staff"]["rows"][0]["login_account"];
          $params[] = (string)$conv_id;  
          $params[] = (string)$ds["we_staff"]["rows"][0]["login_account"];
          $params[] = (string)$conv_id;          
          $all_params[] = $params;
          
          $params = array();
          $params[] = (string)$ds["we_staff"]["rows"][0]["login_account"];
          $params[] = (string)$ds["we_staff"]["rows"][0]["login_account"];
          $all_params[] = $params;
          
          $params = array();
          $params[] = (string)$conv_id;
          $params[] = (string)$ds["we_staff"]["rows"][0]["login_account"];
          $params[] = (string)$conv_id;
          $params[] = (string)$ds["we_staff"]["rows"][0]["login_account"];
          $params[] = (string)$conv_id;
          $params[] = (string)$ds["we_staff"]["rows"][0]["login_account"];
          $all_params[] = $params;
          
          $params = array();
          $params[] = (string)$user->getUserName();
          $params[] = (string)$ds["we_staff"]["rows"][0]["login_account"];
          $params[] = (string)$user->getUserName();
          $params[] = (string)$ds["we_staff"]["rows"][0]["login_account"];
          $all_params[] = $params;
          
          $params = array();
          $params[] = (string)$user->getUserName();  
          $params[] = (string)$user->getUserName();
          $all_params[] = $params;
          
          $da->ExecSQLs($sqls, $all_params);
          $imJids[] = $ds["we_staff"]["rows"][0]["fafa_jid"];
        }
        if($ctl!=null && count($imJids)>0)
        {
		          try{
		          	//获取是否是在评论中@的
		          	$get_sql = "select conv_root_id from we_convers_list where conv_id=?";
		          	$ds = $da->GetData("get_sql",$get_sql,array((string)$conv_id));
		          	if($ds && count($ds["get_sql"]["rows"])>0)
		          	{
		          				$conv_id=$ds["get_sql"]["rows"][0]["conv_root_id"];
		          	}
		            //发送即时消息
		            $link = $ctl->get('router')->generate("JustsyBaseBundle_view_oneconv",array("conv_root_id"=> $conv_id),true);
                $linkButtons=Utils::makeBusButton(array(array("code"=>"action","text"=>"查看","value"=> "","blank"=>"true")));
			          $message = "您的好友在圈子中提到了你，快去看看吧！";
			          Utils::sendImMessage("",implode(",",$imJids),"atme",$message,$ctl,$link,$linkButtons,"","",false,Utils::$atme_code);
			        }catch (\Exception $e) 
				      {
				          $ctl->get('logger')->err($e);
				      }
		    }
      } 
      catch (\Exception $e) 
      {
        throw $e;
      }
    }

    //手机端发动态时的at人员处理。
    //从参数atstaffs中获取人员列表
    public static function genAtMe9999WithMobile($da, $AConvContent, $conv_id, $user,$ctl=null)
    {
        self::genAtMeWithMobile($da, $AConvContent, $conv_id, $user,$ctl);
    }
    //手机端发动态时的at人员处理。
    //从参数atstaffs中获取人员列表
    public static function genAtMeWithMobile($da, $AConvContent, $conv_id, $user,$ctl=null)
    {
      try 
      {
        if ($ctl == null ) return;
        $matchs = $ctl->get("request")->get("atstaffs"); //手机端提交的是jid列表 
        if (empty($matchs) ) return;
        $matchs = explode(",", $matchs);        
        $imJids = array();
        for ($i = 0; $i < count($matchs); $i++)
        {
          if( empty($matchs[$i]) || in_array($matchs[$i],$imJids)) continue;
          $imJids[] = $matchs[$i];
        }
        //写入we_staff_at_me表
        $sqls = array();$paras = array();                
        for($j=0;$j< count($imJids);$j++)
        {
            $sql="insert into we_staff_at_me select login_account,? from we_staff where fafa_jid=?;";
            $para=array((string)$conv_id,(string)$imJids[$j]);
            array_push($sqls,$sql);
            array_push($paras,$para);
        }
        if ( count($sqls)>0)
        {            
            try
            {
                $da->ExecSQLs($sqls,$paras);
            }
            catch(\Exception $e)
            {
                $ctl->get('logger')->err($e->getMessage());
            }
        }        
        /*
        $sqls = array();
        
        $sql = "insert into we_staff_at_me(login_account, conv_id) 
                select  ?, ? 
                from dual
                where not exists(select 1 from we_staff_at_me c where c.login_account=? and c.conv_id=?)";
        $sqls[] = $sql;
        
        $sql = "delete a from we_staff_at_me a 
                where a.login_account=?
                  and (0+a.conv_id) < (select t1.conv_id from (select 0+b.conv_id as conv_id from we_staff_at_me b where b.login_account=? order by 0+b.conv_id desc limit 0, 100) as t1 order by t1.conv_id limit 0, 1)";
        $sqls[] = $sql;
        
        $sql = "insert into we_notify(notify_type, msg_id, notify_staff) 
                select '03', ?, ?
                from dual
                where exists(select 1 from we_convers_list wcl, we_circle_staff wcs where wcl.post_to_circle=wcs.circle_id and wcl.conv_id=? and wcs.login_account=?)
                  and not exists(select 1 from we_notify c where c.notify_type='03' and c.msg_id=? and c.notify_staff=?)";
        $sqls[] = $sql;
        
        $sql = "insert into we_staff_last_at(login_account, at_date, at_login_account)
                select ?, CURRENT_TIMESTAMP(), ?
                from dual
                where not exists(select 1 from we_staff_last_at c where c.login_account=? and c.at_login_account=?) ";
        $sqls[] = $sql;
        
        $sql = "delete a from we_staff_last_at a 
                where a.login_account=? 
                  and a.at_date < (select min(t1.at_date) from (select b.at_date from we_staff_last_at b where b.login_account=? order by b.at_date desc limit 0, 10) as t1)";
        $sqls[] = $sql;
        
        
        for ($i = 0; $i < count($matchs); $i++)
        {
          $login_account = $matchcount[$i];
          if(empty($login_account)) continue;
          $staff = new \Justsy\BaseBundle\Management\Staff($da,null,$login_account,$ctl->get("logger"),$ctl);
          $staffdata = $staff->getInfo();
          if(empty($staffdata)) continue;
          if(in_array($staffdata["fafa_jid"],$imJids)) continue;

          $imJids[] = $staffdata["fafa_jid"];
          $all_params = array();
          
          $params = array();
          $params[] = (string)$login_account;
          $params[] = (string)$conv_id;  
          $params[] = (string)$login_account;
          $params[] = (string)$conv_id;          
          $all_params[] = $params;
          
          $params = array();
          $params[] = (string)$login_account;
          $params[] = (string)$login_account;
          $all_params[] = $params;
          
          $params = array();
          $params[] = (string)$conv_id;
          $params[] = (string)$login_account;
          $params[] = (string)$conv_id;
          $params[] = (string)$login_account;
          $params[] = (string)$conv_id;
          $params[] = (string)$login_account;
          $all_params[] = $params;
          
          $params = array();
          $params[] = (string)$user->getUserName();
          $params[] = (string)$login_account;
          $params[] = (string)$user->getUserName();
          $params[] = (string)$login_account;
          $all_params[] = $params;
          
          $params = array();
          $params[] = (string)$user->getUserName();  
          $params[] = (string)$user->getUserName();
          $all_params[] = $params;
          
          $da->ExecSQLs($sqls, $all_params);
          
        }*/

        if($ctl!=null && count($imJids)>0)
        {
              try{
                //获取是否是在评论中@的
                $get_sql = "select conv_root_id from we_convers_list where conv_id=?";
                $ds = $da->GetData("get_sql",$get_sql,array((string)$conv_id));
                if($ds && count($ds["get_sql"]["rows"])>0)
                {
                    $conv_id=$ds["get_sql"]["rows"][0]["conv_root_id"];
                }
                //发送即时消息
                $link = $ctl->get('router')->generate("JustsyBaseBundle_view_oneconv",array("conv_root_id"=> $conv_id),true);
                $linkButtons=Utils::makeBusButton(array(array("code"=>"action","text"=>"查看","value"=> "","blank"=>"true")));
                $message = "您的好友在圈子中提到了你，快去看看吧！";
                Utils::sendImMessage("",implode(",",$imJids),"atme",$message,$ctl,$link,$linkButtons,"","",false,Utils::$atme_code);
              }catch (\Exception $e) 
              {
                  $ctl->get('logger')->err($e);
              }
        }
      } 
      catch (\Exception $e) 
      {
        throw $e;
      }
    }    
    
    //发布动态信息
    public function publishTrendAction() 
    {
      $request = $this->getRequest();
      $user = $this->get('security.context')->getToken()->getUser();
      
      $da = $this->get('we_data_access');
      
      $network_domain = $request->get('network_domain');
      $conv_content = $request->get('trend');
      $notifystaff = $request->get('notifystaff');
      $attachs = $request->get('attachs');
      $post_to_group = $request->get('post_to_group');
      $conv_id = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_convers_list", "conv_id");

      $conv = new \Justsy\BaseBundle\Business\Conv();
      $conv->newTrend($da, $user, $conv_id, $conv_content, $user->get_circle_id($network_domain), $post_to_group, $notifystaff, $attachs,"00",$this->container);
      
      $this->sendPresence($conv_id,$da,$user->get_circle_id($network_domain),$post_to_group,"trend");
      $re = array('success' => '1', 'conv_id' => $conv_id);
      $response = new Response(json_encode($re));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }
    

    //发布官方动态信息
    public function publishOfficialTrendAction()
    {
      $request = $this->getRequest();
      $user = $this->get('security.context')->getToken()->getUser();
      
      $da = $this->get('we_data_access');
      
      $network_domain = $request->get('network_domain');
      $conv_content = $request->get('trend');
      $notifystaff = $request->get('notifystaff');
      $attachs = $request->get('attachs');
      $post_to_group = $request->get('post_to_group');
      $circle_id = $user->get_circle_id($network_domain);
      $infotype = $request->get('infotype');//信息类型：通知、公告、....
      $top=$request->get('top');//是否置顶
      $time=$request->get('time');//置顶时间
      $conv_id = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_convers_list", "conv_id");

      $conv = new \Justsy\BaseBundle\Business\Conv();
      if($conv->newOfficialTrend($da, $user, $conv_id, $conv_content, $circle_id, $post_to_group, $notifystaff, $attachs,$infotype,"00",$this->container)){
      	if($top=='1'){
      		$conv->convTop($da,$conv_id,$time);
      	}
      }
	    $caption="";
	    $receivedept="";
	    $fafa_jid = array();
	    if($infotype=="notice")
	    {
	       //通知
	       if($post_to_group!="ALL")
	       {
	       	   $groupObj = new \Justsy\BaseBundle\Management\GroupMgr($da,$this->get('we_data_access_im'));
	       	   $getGroupInfo = $groupObj->Get($post_to_group);	 
	       	   $fafa_jid=$groupObj->getGroupMembersJid($post_to_group);	       	   
	       	   //群组通知
	       	   $caption = $getGroupInfo["group_name"]."—群组通知";
	       }
	       else
	       {
	       	   //判断是否是外部圈子
	       	   $en_circle_id = $user->get_circle_id($user->edomain);	       	   
	       	   //全体通知
	       	   $caption = "全体通知";
	       	   if($en_circle_id==$circle_id)
	       	       $receivedept = "v".$user->eno;
	       	   else
	       	   {
								$circlemgr = new \Justsy\BaseBundle\Management\CircleMgr($da,$this->get('we_data_access_im'));
						    $data = $circlemgr->Get($circle_id);
						    $caption = $data["circle_name"]."—圈子通知";
	       	      $fafa_jid=$circlemgr->getCircleMembersJid($circle_id);
	       	   }
	       }
	    }
	    else if($infotype=="bulletin") //公告
	    {
	       if($post_to_group!="ALL")
	       {
	       	   $groupObj = new \Justsy\BaseBundle\Management\GroupMgr($da,$this->get('we_data_access_im'));
	       	   $getGroupInfo = $groupObj->Get($post_to_group);
	       	   $fafa_jid=$groupObj->getGroupMembersJid($post_to_group);	       	
	       	   //群组公告
	       	   $caption = $getGroupInfo["group_name"]."—群组公告";
	       }
	       else
	       {
	       	   //全体公告
	       	   $caption = "全体公告";
	       	   //判断是否是外部圈子
	       	   $en_circle_id = $user->get_circle_id($user->edomain);	       	   
	       	   if($en_circle_id==$circle_id)
	       	       $receivedept = "v".$user->eno;
	       	   else
	       	   {
								$circlemgr = new \Justsy\BaseBundle\Management\CircleMgr($da,$this->get('we_data_access_im'));
						    $data = $circlemgr->Get($circle_id);	 
						    $caption = $data["circle_name"]."—圈子公告"; 	       	   	
	       	      //获取圈子成员
	       	      $fafa_jid=$circlemgr->getCircleMembersJid($circle_id);
	       	   }
	       }
	    }
	    
	    //发送即时消息
	    $ec = new \Justsy\OpenAPIBundle\Controller\ApiController();  		
      $ec->setContainer($this->container);
	    $im_sender = $this->container->getParameter('im_sender');
      $message = $conv_content; 
      $link = $this->generateUrl("JustsyBaseBundle_view_oneconv",array("conv_root_id"=> $conv_id),true);
      $linkButtons=Utils::makeBusButton(array(array("code"=>"action","text"=>"详细","blank"=>"1","value"=> "")));
      $r= $ec->runonceRemindTask($im_sender,implode(",",$fafa_jid),$receivedept,$caption,$message,$link,$linkButtons);   	    
      $re = array('success' => '1', 'conv_id' => $conv_id);
      $response = new Response(json_encode($re));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }    
    
    //发布提问
    public function publishAskAction() 
    {
      $request = $this->getRequest();
      $user = $this->get('security.context')->getToken()->getUser();
      
      $da = $this->get('we_data_access');
      
      $network_domain = $request->get('network_domain');
      $conv_content = $request->get('question');
      $notifystaff = $request->get('notifystaff');
      $attachs = $request->get('attachs');
      $post_to_group = $request->get('post_to_group');
      $conv_id = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_convers_list", "conv_id");
            
      $conv = new \Justsy\BaseBundle\Business\Conv();
      $conv->newAsk($da, $user, $conv_id, $conv_content, $user->get_circle_id($network_domain), $post_to_group, $notifystaff, $attachs,"00",$this->container);
      $this->sendPresence($conv_id,$da,$user->get_circle_id($network_domain),$post_to_group,"ask");
      $re = array('success' => '1', 'conv_id' => $conv_id);
      $response = new Response(json_encode($re));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }
    
    //发布活动
    public function publishTogetherAction() 
    {
      $request = $this->getRequest();
      $user = $this->get('security.context')->getToken()->getUser();
      
      $da = $this->get('we_data_access');
      
      $network_domain = $request->get('network_domain');
      $conv_content = $request->get('title');
      $will_date = $request->get('will_date');
      $will_dur = $request->get('will_dur');
      $will_addr = $request->get('will_addr');
      $map_point = $request->get('map_point');
      $together_desc = $request->get('together_desc');
      $notifystaff = $request->get('notifystaff');
      $attachs = $request->get('attachs');
      $post_to_group = $request->get('post_to_group');
      $conv_id = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_convers_list", "conv_id");
            
      $conv = new \Justsy\BaseBundle\Business\Conv();
      $conv->newTogether($da, $user, $conv_id, $conv_content, $will_date, $will_dur, $will_addr, $together_desc, $user->get_circle_id($network_domain), $post_to_group, $notifystaff, $attachs,$map_point,"00",$this->container);
      $this->sendPresence($conv_id,$da,$user->get_circle_id($network_domain),$post_to_group,"together");
      $re = array('success' => '1', 'conv_id' => $conv_id);
      $response = new Response(json_encode($re));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }
    
    //发布投票
    public function publishVoteAction() 
    {
      $request = $this->getRequest();
      $user = $this->get('security.context')->getToken()->getUser();
      
      $da = $this->get('we_data_access');
      
      $network_domain = $request->get('network_domain');
      $conv_content = $request->get('title');
      $is_multi = $request->get('is_multi');
      $optionvalues = $request->get('optionvalues');
      $notifystaff = $request->get('notifystaff');
      $attachs = $request->get('attachs');
      $post_to_group = $request->get('post_to_group');
      $finishdate = $request->get('finishdate');
      $conv_id = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_convers_list", "conv_id");

      $conv = new \Justsy\BaseBundle\Business\Conv();
      $conv->newVote($da, $user, $conv_id, $conv_content, $is_multi, $finishdate, $optionvalues, $user->get_circle_id($network_domain), $post_to_group, $notifystaff, $attachs,"00",$this->container);
      $this->sendPresence($conv_id,$da,$user->get_circle_id($network_domain),$post_to_group,"vote");
      $re = array('success' => '1', 'conv_id' => $conv_id);
      $response = new Response(json_encode($re));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }
    
    public function sendPresence($conv_id,$da,$circle_id,$post_to_group,$type)
    {	    
	    $groupid="";
	    $group_type="group";
	    $fafa_jid = array();
	    $jid = "";
	    if($circle_id=="10000") return;          
	    $user = $this->get('security.context')->getToken()->getUser();
	       if($post_to_group!="ALL" && $post_to_group!="PRIVATE")
	       {
			       	   $groupObj = new \Justsy\BaseBundle\Management\GroupMgr($da, $this->get('we_data_access_im'),$this->container);
			       	   $getGroupInfo = $groupObj->Get($post_to_group);
			       	   $jid = $getGroupInfo["fafa_groupid"];
			       	   $fafa_jid=$groupObj->getGroupMembersJid($post_to_group,"1");	    //获取允许接收群组动态通知的成员列表   	
			       	   $groupid = $post_to_group;
	       }
	       else
	       {
	       	   $group_type="circle"; 
	       	   $groupid = $circle_id;
	       	   $circlemgr = new \Justsy\BaseBundle\Management\CircleMgr($da,$this->get('we_data_access_im'));
	       	   $getGroupInfo = $circlemgr->Get($circle_id);
	       	   $jid = $getGroupInfo["fafa_groupid"];
	       	   //判断是否是私密,私密时不向圈子成员发出席
	       	   if($post_to_group=="PRIVATE")
	       	   {
	       	   	   $group_type = "private"; 
	       	   }
						 else
             {
                if($circle_id=="9999") //给好友发通知
                {
                    $staffMgr = new \Justsy\BaseBundle\Management\Staff($da,$this->get('we_data_access_im'),$user,$this->get("logger"),$this->container );
                    $fafa_jid = $staffMgr->getFriendJidList($conv_id);
                }
                else
                {
  						     $fafa_jid = $circlemgr->getCircleMembersJid($circle_id,"1");
                }
             }
	       }
	       $cc_jid=array();
	       //获取抄送的人员jid
	       $sql = "select b.fafa_jid from we_convers_notify a ,we_staff b where a.cc_login_account=b.login_account and conv_id=?";
	       $ds = $da->getData("ds",$sql,array((string)$conv_id));
	       if($ds && count($ds["ds"]["rows"])>0)
	       {
	       	   for($i=0; $i<count($ds["ds"]["rows"]); $i++)
	       	   	   $cc_jid[]= $ds["ds"]["rows"][$i]["fafa_jid"];
	       }
        //发送即时消息
	      $ec = new \Justsy\OpenAPIBundle\Controller\ApiController();
	      $ec->setContainer($this->container); 
        $message = array($group_type."id"=> $groupid,"t"=> $type,"jid"=>$jid);
        $link = $this->generateUrl("JustsyBaseBundle_view_oneconv",array("conv_root_id"=> $conv_id),true);
        $linkButtons=Utils::makeBusButton(array(array("code"=>"action","text"=>"详细","blank"=>"1","value"=> "")));
        //分次发送通知。每次200个号
        $c=0;        
        $sendAry=array();
        for($i=0; $i<count($fafa_jid); $i++)
        {
      	  $sendAry[] = $fafa_jid[$i];
          $c++;	  
          if($c>=200){
          	 $r= $ec->sendPresence($user->fafa_jid,implode(",",$sendAry),"",json_encode($message),$link,$linkButtons,false,$group_type."_newtrend"); 
          	 $c=0;
          	 $sendAry=array();
          }
        }
        if($c>0)
        {  
            
           $r= $ec->sendPresence($user->fafa_jid,implode(",",$sendAry),"",json_encode($message),$link,$linkButtons,false,$group_type."_newtrend","0"); 
        }
        if(count($cc_jid)>0)//给抄送人员推消息
          $r= $ec->sendMsg($user->fafa_jid,implode(",",$cc_jid),"",json_encode($message),$link,$linkButtons,false,"private_newtrend"); ;
    }
    
    public function queryAtTipsAction() 
    {
      $request = $this->getRequest();
      $user = $this->get('security.context')->getToken()->getUser();
      $network_domain = $request->get("network_domain");
      
      $query = $request->get("query");
      $sql="
select c.nick_name, b.eshortname
from we_staff_last_at a, we_enterprise b, we_staff c
where a.at_login_account=c.login_account and c.eno=b.eno
  and a.login_account=?
  and c.nick_name like concat('%', ?, '%') ";
  $params = array();
      $params[] = (string)$user->getUserName();
      $params[] = (string)$query;      
      if($user->get_circle_id($network_domain)=="9999"){
      	$sql.=" union select c.nick_name,d.eshortname from we_staff c,we_enterprise d,we_staff_atten a
where a.atten_id=? and a.login_account 
in (select b.atten_id from we_staff_atten b where b.login_account=?) 
and c.login_account=a.login_account and d.eno=c.eno and c.nick_name like concat('%',?,'%') limit 0,10";
				$params[]=(string)$user->getUserName();
				$params[]=(string)$user->getUserName();
				$params[]=(string)$query;
      }
      else if($user->get_circle_id($network_domain)=="10000"){}
      else{
      $sql.= "
union
select c.nick_name, b.eshortname
from we_circle_staff a, we_enterprise b, we_staff c
where a.login_account=c.login_account 
  and b.eno=c.eno
  and a.circle_id=?
  and c.nick_name like concat('%', ?, '%')
limit 0, 10
";
	$params[] = (string)$user->get_circle_id($network_domain);
      $params[] = (string)$query;
}
      $da = $this->get('we_data_access');
      $ds = $da->GetData("queryattips", $sql, $params);
      $response = new Response(json_encode($ds["queryattips"]["rows"]));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }
}
