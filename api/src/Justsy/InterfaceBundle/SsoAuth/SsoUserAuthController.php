<?php

namespace Justsy\InterfaceBundle\SsoAuth;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\Common\Utils;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Management\Staff;
use Justsy\BaseBundle\Management\App;
use Justsy\BaseBundle\Management\Enterprise;

class SsoUserAuthController extends Controller
{
	//用户认证请求分发
	//$comefrom:03 表示业务代理登录
	public function dispatchAction($container,$user,$pass,$comefrom="00",$datascope="",$portalversion="")
	{		
		$request = $container->get("request");
		$clientdatetime = $request->get("clientdatetime");
		$appid = $request->get("appid"); //判断是否集成登录，是则同时返回OAuth2的code，用于客户端获取token
		$db  =$this->get("we_data_access");
		$db_im = $this->get("we_data_access_im");

		try{
			//获取当前企业的认证方式：默认认证、ldap认证、ad认证、统一接口认证
			$authobj = new Enterprise($db,$this->get("logger"),$container->container);//
			if($comefrom=="03")
			{
				$classname = "DefaultAuth";  //业务代理登录默认采用wefafa认证
			}
			else
			{
				$authConfig = 	$authobj->getUserAuth();
				$classname=$authConfig["ssoauthmodule"];
				if(empty($classname) || $classname=="null"){
					$re=array("msg"=>"invalid ssoauthmodule");
					$re["returncode"] = ReturnCode::$SYSERROR; 
					return $re;
				}
			}
			$classname = "\Justsy\InterfaceBundle\SsoAuth\Sso".$classname;
			$re = call_user_func(array($classname, 'userAuthAction'),$container->container,$request,$db,$db_im,$user,$pass,$comefrom); 
			if( $re["returncode"] == ReturnCode::$SUCCESS )
	 	  	{
	 	  		if(!empty($appid))
	 	  		{
	 	  			$appMgr = new \Justsy\BaseBundle\Management\App($container->container);
	 	  			$appinfo = $appMgr->getappinfo(array('appid'=> $appid));
	 	  			if(empty($appinfo))
	 	  			{
	 	  				$re=array("msg"=>"无效的应用标识号");
						$re["returncode"] = ReturnCode::$SYSERROR; 
						return $re;
	 	  			}
	 	  			$appkey = $appinfo['appkey'];
	 	  			$code = strtolower(MD5($appid.$appkey));
	 	  			$re['auth2_code'] = $code;
	 	  		}
	 	  		//if ( !empty($datascope))
	 	  		{   
	 	  			$this->getLoginAppendData($re,$re["login_account"],$portalversion,$comefrom,$db,$db_im,$clientdatetime);
	 	  			if(!empty($re["info"]))
	 	  			{
	 	  				$einfo=$authobj->getInfo($re["info"]["eno"]);
	 	  				if(!empty($einfo))
	 	  				{
	 	  					$re["info"]["ename"] = $einfo["ename"];
						  	$re["info"]["circle_id"] = $einfo["circle_id"];
						  	$re["info"]["circle_name"] = $einfo["circle_name"];
	 	  				}
	 	  			}
	 	  		}
	 	  		$this->setLoginDate($re["openid"],$db);
	    	}
			  return $re;
		}
		catch(\Exception $e)
		{
			$this->get("logger")->err($e);
			$re=array("msg"=>"invalid ssoauthmodule");
			$re["returncode"] = ReturnCode::$SYSERROR; 
			return $re;
		}
	}

  //设置用户本次登录时间
  private function setLoginDate($login_account,$db)
  {
  	$success = true;
  	$sql = "update we_staff set prev_login_date=this_login_date,this_login_date=now() where login_account=? or openid=?";
  	try
  	{
  	  $db->ExecSQL($sql,array((string)$login_account,(string)$login_account));
    }
    catch(\Exception $e){
    	$success = false;
    	$this->get("logger")->err($e->getMessage());
    }
    return $success;
  }
  
	public function getLoginAppendData(&$re,$login_account,$portalversion,$comefrom,$db,$db_im,$clientdatetime)
	{
		$we_sys_param = $this->container->get('we_sys_param'); 
		$imserver = $we_sys_param->GetSysParam("imserver");
     	if (empty($imserver)) $imserver = "localhost:5222";
     	$re["imserver"] = $imserver;
     	$url = $this->container->getParameter('FILE_WEBSERVER_URL');
	   	$url = str_replace("/getfile/","",$url)."/api/http/version/check";
     	$re["imupdateserver"] = $url;
     	if ( !empty($clientdatetime))
     	{
        	$sys=new \Justsy\InterfaceBundle\Controller\SystemController();
         	$sys->setContainer($this->container);
         	$re["server_time"] = $sys->getMillisecond($clientdatetime);
     	}
     	else
     	{
        	$re["server_time"] = 0;
     	}
     	//$re["publicuser"] = "0"; //是否是公共用户（未加入企业用户）
     	//$re["micro_app_jid"] = "";
	 	$re["info"] = array();
     	//$re["rosters"] = array(); 
     	//$re["portalconfig_version"] = "";
     	//$re["portalconfig_xml"] = "";
     	//获取个人信息
     	$staffinfo = new Staff($db,$db_im,$login_account,$this->get("logger"),$this->container);
		$result = $staffinfo->getInfo();
  	 	if(!empty($result))
  	 	{
  	 		$returnAttrs = explode(",", "jid,login_account,nick_name,photo_path,dept_id,dept_name,eno,ename,self_desc,duty,sex_id,mobile_bind,birthday");
		  	for ($i=0; $i <count($returnAttrs) ; $i++) { 
		  		$key = $returnAttrs[$i];
		  		$re["info"][$key] =  $result[$key]; 
		  	}	 	  	
		  		 
		  	//为了避免用户修改密码后只刷新了所在服务器，im密码实时获取
		    //$sql = "select password from users where username=?";
		    //$iminfo = $db_im->GetData("im",$sql,array((string)$re["jid"]));
		    //$re["des"] =count($iminfo["im"]["rows"])>0 ? $iminfo["im"]["rows"][0]["password"] : "";	    	
		   	
		}
		return $re;
	}

	private function responseJson($data,$jsopfunc=null)
	{
		$resp = new Response( empty($jsopfunc) ? json_encode($data): $jsopfunc."(".json_encode($data).")");
	    $resp->headers->set('Content-Type', 'text/json');
	   	return $resp;
	}
	
}