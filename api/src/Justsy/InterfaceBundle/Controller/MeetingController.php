<?php

namespace Justsy\InterfaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Controller\EmployeeCardController;

///会议接口
class BaseInfoController extends Controller
{  
  public function getcirclesAction() 
  {
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
	  $user = $this->get('security.context')->getToken()->getUser();
    
    $sql = "select a.circle_id, case when ifnull(a.enterprise_no, '')='' then a.circle_name else ? end circle_name, a.circle_desc, a.logo_path, a.create_staff, a.create_date, a.manager, 
a.join_method, a.enterprise_no, a.network_domain, a.allow_copy, a.logo_path_small, a.logo_path_big
from we_circle a, we_circle_staff b
where a.circle_id=b.circle_id
  and b.login_account=?
";
    $params = array();
    $params[] = (string)$user->eshortname;
    $params[] = (string)$user->getUserName();
    
    $da = $this->get('we_data_access');
    $ds = $da->GetData("we_circle", $sql, $params);
    $re["circles"] = $ds["we_circle"]["rows"];
    
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  
  public function getgroupsAction() 
  {
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
	  $user = $this->get('security.context')->getToken()->getUser();
    
    $circle_id = $request->get("circle_id");
    
    $sql = "select a.circle_id, a.group_id, a.group_name, a.group_desc, a.group_photo_path, a.join_method,
a.create_staff, a.create_date
from we_groups a, we_group_staff b
where a.group_id=b.group_id
  and a.circle_id=?
  and b.login_account=?
";
    $params = array();
    $params[] = (string)$circle_id;
    $params[] = (string)$user->getUserName();
    
    $da = $this->get('we_data_access');
    $ds = $da->GetData("we_groups", $sql, $params);
    $re["groups"] = $ds["we_groups"]["rows"];
    
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  
  public function getstaffcardAction() 
  {
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    
    $staff = $request->get("staff");
    
    $sql = "select a.login_account,a.fafa_jid jid, a.nick_name, a.photo_path, a.photo_path_small, a.photo_path_big, 
a.dept_id, b.dept_name, a.eno, c.ename, c.eshortname, a.self_desc, a.duty, a.birthday, a.specialty,a.sex_id,a.hometown,ifnull(a.we_level,1) we_level,a.work_his,a.graduated,
a.hobby, a.work_phone, a.mobile, case when a.mobile_bind is not null and a.mobile_bind=a.mobile then '1' else '0' end mobile_is_bind, 
a.total_point, a.register_date, a.active_date, a.attenstaff_num, a.fans_num, a.publish_num
from we_staff a
left  join we_department b on a.dept_id=b.dept_id
inner join we_enterprise c on a.eno=c.eno
where a.login_account=?
union
select a.login_account,a.fafa_jid jid, a.nick_name, a.photo_path, a.photo_path_small, a.photo_path_big, 
a.dept_id, b.dept_name, a.eno, c.ename, c.eshortname, a.self_desc, a.duty, a.birthday, a.specialty,a.sex_id,a.hometown,ifnull(a.we_level,1) we_level,a.work_his,a.graduated,
a.hobby, a.work_phone, a.mobile, case when a.mobile_bind is not null and a.mobile_bind=a.mobile then '1' else '0' end mobile_is_bind, 
a.total_point, a.register_date, a.active_date, a.attenstaff_num, a.fans_num, a.publish_num
from we_staff a
left  join we_department b on a.dept_id=b.dept_id
inner join we_enterprise c on a.eno=c.eno
where a.fafa_jid=?
union
select a.login_account,a.fafa_jid jid, a.nick_name, a.photo_path, a.photo_path_small, a.photo_path_big, 
a.dept_id, b.dept_name, a.eno, c.ename, c.eshortname, a.self_desc, a.duty, a.birthday, a.specialty,a.sex_id,a.hometown,ifnull(a.we_level,1) we_level,a.work_his,a.graduated,
a.hobby, a.work_phone, a.mobile, case when a.mobile_bind is not null and a.mobile_bind=a.mobile then '1' else '0' end mobile_is_bind, 
a.total_point, a.register_date, a.active_date, a.attenstaff_num, a.fans_num, a.publish_num
from we_staff a
left  join we_department b on a.dept_id=b.dept_id
inner join we_enterprise c on a.eno=c.eno
where a.mobile_bind=?
union
select a.login_account,a.fafa_jid jid, a.nick_name, a.photo_path, a.photo_path_small, a.photo_path_big, 
a.dept_id, b.dept_name, a.eno, c.ename, c.eshortname, a.self_desc, a.duty, a.birthday, a.specialty,a.sex_id,a.hometown,ifnull(a.we_level,1) we_level,a.work_his,a.graduated,
a.hobby, a.work_phone, a.mobile, case when a.mobile_bind is not null and a.mobile_bind=a.mobile then '1' else '0' end mobile_is_bind, 
a.total_point, a.register_date, a.active_date, a.attenstaff_num, a.fans_num, a.publish_num
from we_staff a
left  join we_department b on a.dept_id=b.dept_id
inner join we_enterprise c on a.eno=c.eno
where a.openid=?
";
    $params = array();
    $params[] = (string)$staff;
    $params[] = (string)$staff;
    $params[] = (string)$staff;
    $params[] = (string)$staff;
    
    $da = $this->get('we_data_access');
    $ds = $da->GetData("we_staff", $sql, $params);
    if ($ds && $ds["we_staff"]["recordcount"] == 0) $re["returncode"] = ReturnCode::$SYSERROR;
    else $re["staff_full"] = $ds["we_staff"]["rows"][0];
    
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  //��ע��Ա
  public function attenstaffAction()
  {
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $staff = $request->get("staff");
    $da = $this->get('we_data_access');
    $sql_t = "select login_account from we_staff where ";
    $sql = $sql_t."login_account=? union ".$sql_t."fafa_jid=?";
    $ds = $da->GetData("we_staff", $sql, array((string)$staff,(string)$staff));
    if ($ds && $ds['we_staff']['recordcount']>0)
    {
      $login_account = $ds['we_staff']['rows'][0]['login_account'];
    	try
    	{
    	  $ec = new EmployeeCardController();
    	  $ec->setContainer($this->container);
    	  $resp = $ec->attentionAction($login_account);
    	  $jo = json_decode($resp->getContent());
    	  if ($jo && $jo->{'succeed'}==1) $re['returncode'] = ReturnCode::$SUCCESS;
    	  else $re['returncode'] = ReturnCode::$SYSERROR;
      }
      catch(\Exception $e)
      {
        $this->get('logger')->err($e);
        $re['returncode'] = ReturnCode::$SYSERROR;
      }
    }
    else
    {
      $re['returncode'] = ReturnCode::$ERROFUSERORPWD;
    }
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  //ȡ���ע
  public function cancelattenstaffAction()
  {
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $staff = $request->get("staff");
    $da = $this->get('we_data_access');
    $sql_t = "select login_account from we_staff where ";
    $sql = $sql_t."login_account=? union ".$sql_t."fafa_jid=?";
    $ds = $da->GetData("we_staff", $sql, array((string)$staff,(string)$staff));
    if ($ds && $ds['we_staff']['recordcount']>0)
    {
      $login_account = $ds['we_staff']['rows'][0]['login_account'];
    	try
    	{
    	  $ec = new EmployeeCardController();
    	  $ec->setContainer($this->container);
    	  $resp = $ec->cancelAttentionAction($login_account);
    	  $jo = json_decode($resp->getContent());
    	  if ($jo && $jo->{'succeed'}==1) $re['returncode'] = ReturnCode::$SUCCESS;
    	  else $re['returncode'] = ReturnCode::$SYSERROR;
      }
      catch(\Exception $e)
      {
        $this->get('logger')->err($e);
        $re['returncode'] = ReturnCode::$SYSERROR;
      }
    }
    else
    {
      $re['returncode'] = ReturnCode::$ERROFUSERORPWD;
    }
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  //��ע��Ա�б�
  public function attenstafflistAction()
  {
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $staff = $request->get("staff");
    $da = $this->get('we_data_access');
    $sql_t = "select a.login_account,a.nick_name,a.photo_path,a.photo_path_small,a.photo_path_big from we_staff a ";
    $sql = $sql_t."inner join we_staff_atten b on a.login_account=b.atten_id and b.login_account=? union ".$sql_t;
    $sql .= "inner join we_staff_atten b on a.login_account=b.atten_id inner join we_staff c on b.login_account=c.login_account and c.fafa_jid=?";
    $ds = $da->GetData("we_staff", $sql, array((string)$staff,(string)$staff));
    if ($ds && $ds["we_staff"]["recordcount"] == 0) $re["returncode"] = ReturnCode::$SYSERROR;
    else $re["staffs"] = $ds["we_staff"]["rows"];

    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  //获取关注人员版本
  public function attenstafflistversionAction()
  {
      $re = array("returncode" => ReturnCode::$SUCCESS);
      $request = $this->getRequest();
      $staff = $request->get("staff");
      $da = $this->get('we_data_access');
      $sql_t = "select a.login_account,a.nick_name,a.photo_path,a.photo_path_small,a.photo_path_big from we_staff a ";
      $sql = $sql_t."inner join we_staff_atten b on a.login_account=b.atten_id and b.login_account=? union ".$sql_t;
      $sql .= "inner join we_staff_atten b on a.login_account=b.atten_id inner join we_staff c on b.login_account=c.login_account and c.fafa_jid=?";
      $ds = $da->GetData("we_staff", $sql, array((string)$staff,(string)$staff));
      if ($ds && $ds["we_staff"]["recordcount"] == 0) $re["returncode"] = ReturnCode::$SYSERROR;
      else{
          $s = "";
      foreach($ds["we_staff"]["rows"] as $key => $value)
      {
        $s .= $value['login_account'];
      }
      $re["version"] = md5($s);
      }
  
      $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
  }
  //Ȧ����Ա�б�
  public function circlestaffAction()
  {
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $circle_id = $request->get("circle_id");
    $da = $this->get('we_data_access');
    $sql = "select a.login_account,a.nick_name,c.eshortname,a.photo_path,a.photo_path_small,a.photo_path_big
from we_staff a inner join we_circle_staff b on a.login_account=b.login_account
inner join we_enterprise c on a.eno=c.eno
where b.circle_id=?";
    $ds = $da->GetData("we_staff", $sql, array((string)$circle_id));
    if ($ds && $ds["we_staff"]["recordcount"] == 0) $re["returncode"] = ReturnCode::$SYSERROR;
    else $re["staffs"] = $ds["we_staff"]["rows"];

    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }

  public function circlestaffversionAction()
  {
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $circle_id = $request->get("circle_id");
    $da = $this->get('we_data_access');
    $sql = "select login_account from we_circle_staff where circle_id=?";
    $ds = $da->GetData("we_staff", $sql, array((string)$circle_id));
    if ($ds && $ds["we_staff"]["recordcount"] == 0)
    {
      $re["returncode"] = ReturnCode::$SYSERROR;
    }
    else
    {
      $s = "";
      foreach($ds["we_staff"]["rows"] as $key => $value)
      {
        $s .= $value['login_account'];
      }
      $re["version"] = md5($s);
    }

    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  
  public function getServerDiffTimeAction(){
      $re = array("returncode" => ReturnCode::$SUCCESS);
      $request = $this->getRequest();
      $time = $request->get("time");
      $re['server_time']=$this->getMillisecond($time);
      $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
  }
  
  public function getMillisecond($c_time)
  {
      $time = explode (" ", microtime () );
      $time = ($time [1]+$time [0])* 1000;
      $time = round($c_time-$time,0);
      return $time;
  }
  
  //获得用户所在组织部门
  public function getDepartmentAction(){
  	$request = $this->get("request");
  	$name = $request->get("name");
  	$sql = "";
  	$parameter= Array();
  	$user = $this->get('security.context')->getToken()->getUser();
  	if(empty($name)){
  	  $sql = "select dept_id,dept_name from we_department where eno=?";
  	  array_push($parameter,(String)$user->eno);
  	}
  	else{
  		$sql = "select dept_id,dept_name from we_department where dept_name like concat('%',?,'%') and eno=?";
  		array_push($parameter,(String)$name);
  	  array_push($parameter,(String)$user->eno);
  	}
    $da = $this->get("we_data_access");
    $ds = $da->GetData("department", $sql, $parameter);
    $response = new Response(json_encode($ds["department"]));
	  $response->headers->set('Content-Type', 'text/json');
	  return $response;
  }
  
  //获得用户所在组织人员信息
  public function getenostaffAction(){
  	$request = $this->get("request");
  	$nick_name = $request->get("nick");
  	$letter = $request->get("letter");
  	$sql = "";
  	$parameter= Array();
  	$user = $this->get('security.context')->getToken()->getUser();
  	if(empty($letter)){
  	  if(empty($nick_name)){
  	    $sql = "select dept_id,login_account,nick_name as name,photo_path_small from we_staff where state_id=1 and eno=? order by login_account asc";
  	    array_push($parameter,(String)$user->eno);
  	  }
  	  else{
  		  if ((ord($nick_name) & 0x80) == 128){
  			  $sql = "select dept_id,login_account,nick_name as name,photo_path_small from we_staff where nick_name like concat('%',?,'%') and eno=? order by login_account asc";
  		    array_push($parameter,(String)$nick_name);
  	      array_push($parameter,(String)$user->eno);
  		  }
  		  else{
          $sql = "select dept_id,login_account,nick_name as name,photo_path_small from we_staff where (nick_name like concat('%',?,'%') or login_account like concat('%',?,'%')) and eno=? order by login_account asc";
  		    array_push($parameter,(String)$nick_name);
  		    array_push($parameter,(String)$nick_name);
  	      array_push($parameter,(String)$user->eno);
  		  }
  	  }
    }
    else{
    	if($letter=="ALL"){
    		$sql = "select dept_id,login_account,nick_name as name,photo_path_small from we_staff where eno=? order by login_account asc";
	  	  array_push($parameter,(String)$user->eno);
    	}
    	else{
	    	$sql = "select dept_id,login_account,nick_name as name,photo_path_small from we_staff where login_account like concat(?,'%') and eno=? order by login_account asc";
	  		array_push($parameter,(String)$letter);
	  	  array_push($parameter,(String)$user->eno);
  	  }
    }
    $da = $this->get("we_data_access");
    $ds = $da->GetData("department", $sql, $parameter);
    $response = new Response(json_encode($ds["department"]));
	  $response->headers->set('Content-Type', 'text/json');
	  return $response;
  }
  
  //企业组织树
  public function getenotreeAction(){
  	$request = $this->get("request");
  	$user = $this->get('security.context')->getToken()->getUser();
  	$root = "v".$user->eno;
    $sql = "select dept_id,dept_name,case dept_id when parent_dept_id then '".$root."' else parent_dept_id end parent_dept_id,create_staff from we_department where eno=?";
    $da = $this->get("we_data_access");
    $ds = $da->GetData("dept", $sql, array((string)$user->eno));
    $re=$ds['dept']['rows'];
    $treedata=array();
    $treedata[]=array('id'=>$root,'name'=>$user->eshortname,'pId'=>0,'open'=>true);
    for($i = 0; $i<count($re); $i++)
    {
    	if(!empty($result)) $result .=",";
      $treedata[]= array('id'=>$re[$i]['dept_id'],'name'=>$re[$i]['dept_name'],'pId'=>$re[$i]['parent_dept_id'],'open'=>true);
    }
    $response = new Response(json_encode($treedata));
	  $response->headers->set('Content-Type', 'text/json');
	  return $response;  	
  }
}