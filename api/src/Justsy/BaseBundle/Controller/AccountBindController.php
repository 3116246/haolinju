<?php

namespace Justsy\BaseBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Management\Staff;


class AccountBindController extends Controller
{   
	public $groups=null;
  
  public function saveAction()
  {
  	$reslt = array();
  	$request = $this->get("request");
  	$uid = isset($_SESSION["uid"]) ? $_SESSION["uid"] : "";
  	if(empty($uid))
  	{
	  			$reslt["s"]= "0";
	  			$reslt["msg"]= "微博登录失败或超时，请重新通过微博登录！";  
			    $response = new Response(json_encode($reslt));
			    $response->headers->set('Content-Type', 'text/json');
			    return $response;	  					  
  	}
  	$type = $request->get("bind_type");
  	$login_account = $request->get("login_account");
  	$pwd = $request->get("pwd");
  	try{ 	
	  	//校验wefafa帐号和密码
	  	$staffMgr = new Staff($this->get('we_data_access'),null,$login_account,$this->get('logger'));
	  	$staffInfo = $staffMgr->getInfo();
	  	if(empty($staffInfo))
	  	{
	  			$reslt["s"]= "0";
	  			$reslt["msg"]= "帐号[".$login_account."]不存在!";
	  	}
	  	else{
	  		  $tcode = $staffInfo["t_code"];
	  		  $p_code = DES::encrypt($pwd);
	  		  if($tcode!=$p_code)
	  		  {
		  			$reslt["s"]= "0";
		  			$reslt["msg"]= "帐号或密码不正确!";  		  	
	  		  }
			  	else{
			  		$accountbind =new \Justsy\BaseBundle\Management\StaffAccountBind($this->get('we_data_access'),null,$this->get('logger'));
			  	  $r=$accountbind->Bind($type,"",$login_account,$uid);
			  	  $this->get('logger')->err($type.",".$uid.",".$login_account.",".$uid);
			  	  $reslt["s"]= $r;
			    }
	    }
    }
    catch(\Exception $e)
    {
        	$this->get('logger')->err($e);
		  		$reslt["s"]= "0";
		  		$reslt["msg"]= "绑定失败，请检查帐号是否填写正确!";         	
    }
    $response = new Response(json_encode($reslt));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  
  public function unbindAction()
  {
    $response = new Response("");
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  
}