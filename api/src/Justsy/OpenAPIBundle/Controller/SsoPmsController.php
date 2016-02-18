<?php

namespace Justsy\OpenAPIBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Management\Staff;
use Justsy\BaseBundle\Common\Cache_Enterprise;
use SoapClient;

class SsoPmsController  extends Controller  implements ISso
{

	public static $bind_type = "Pms";
	public static function ssoAction($controller,$conn,$appid,$openid,$token,$encrypt)
	{
		//重新授权
		$app = new \Justsy\BaseBundle\Management\App($controller->container);
		$appdata = $app->getappinfo(array("appid"=>$appid));
		if(empty($appdata))
		{
			$resp = new Response("无效的APPID");
	   		$resp->headers->set('Content-Type', 'text/html');
	   		return $resp;
		}
		$auth_url = $appdata["authorization_url"];
		if(empty($auth_url))
		{
			$resp = new Response("无效的配置：授权地址无效");
	   		$resp->headers->set('Content-Type', 'text/html');
	   		return $resp;
		}
		//判断是否绑定
		$bindinfo = $app->getappbind(array(
	            	"appid"=>$appid,
	            	"openid"=>$openid
	            ));
	    if(empty($bindinfo))
	    {
	    	$controller->get("logger")->err("================not bind");
	    	//重定向到绑定页面
	    	return $controller->render("JustsyBaseBundle:AppCenter:h5bundle.html.twig",
  	 	      array('appid'=> $appid,
  	 	      'openid'=>$openid,
  	 	      'ssomodule'=>self::$bind_type."Controller"));
	    }
		else
		{
			/*
			$syspara = new \Justsy\BaseBundle\DataAccess\SysParam($controller->container);
    		$sysappid = $syspara->GetSysParam("sso_".strtolower(self::$bind_type)."_appid");
    		if(empty($sysappid))
    		{
    			$resp = new Response("未配置集成登录业务系统或参数".strtolower(self::$bind_type)."_appid");
	   			$resp->headers->set('Content-Type', 'text/html');
	   			return $resp;
    		}*/
			$sysinfo = $appdata;//$app->getbussysteminfo(array("appid"=>$sysappid));
			
			$wwwUrl = $sysinfo["inf_url"];
			$loginUrl = $sysinfo["authorization_url"];	
			if(empty($wwwUrl))
			{
				$resp = new Response("未正确配置业务系统，请检查服务地址");
		   		$resp->headers->set('Content-Type', 'text/html');
		   		return $resp;
			}
			//获取seesionid
			$cookie_key= self::$bind_type."_".$openid;
			$getsessionUrl = $wwwUrl."index.php?m=api&f=getSessionID&t=json";
			$controller->get("logger")->err("getsessionUrl:".$getsessionUrl);
			$sessionre = Utils::do_get_request_cookie($getsessionUrl,null,null,$cookie_key);
			$controller->get("logger")->err("session data:".$sessionre);
			$sessionre = json_decode($sessionre,true);
			if($sessionre["status"]!="success")
			{
				$resp = new Response("获取seesion失败");
		   		$resp->headers->set('Content-Type', 'text/html');
		   		return $resp;
			}
			$sessionre = json_decode($sessionre["data"],true);
			$sid = $sessionre["sessionID"];
			//用户身份认证
			
			$controller->get("logger")->err("================loginUrl:".$loginUrl."&sid=".$sid."&account=".$bindinfo["bind_uid"]."&password=".$bindinfo["authkey"]);
			$authResult = Utils::do_post_request_cookie($loginUrl."&sid=".$sid."&account=".$bindinfo["bind_uid"]."&password=".$bindinfo["authkey"],
	            	null,
	            	null,
	            	$cookie_key);
            $retuenAry = array("session"=>array("access_token"=>$sid),"appid"=>$appid,"openid"=>$openid);
            $controller->get("logger")->err("pms login result:".$authResult);
            //认证失败时要求重新绑定
            $authResult = json_decode($authResult,true);
            if(!isset($authResult["status"]) || $authResult["status"]!="success")
            {
		    	return $controller->render("JustsyBaseBundle:AppCenter:h5bundle.html.twig",
	  	 	      array('appid'=> $appid,
	  	 	      'openid'=>$openid,
	  	 	      'ssomodule'=>"PmsController"));
            }
            $app->setappsession($retuenAry);

            //$re = Utils::do_post_request_cookie($wwwUrl."?m=my&f=index&t=json",null,null,$cookie_key);
			//$controller->get("logger")->err("my-todo result:".$re);
		}

		$result =array("returncode"=>"0000","data"=>$authResult);
		return self::responseJson(json_encode($result));
	}

	public static function tokenAction($controller,$con,$appid,$openid,$encrypt)
	{

	}
	public static function bindTitleAction($controller,$con,$appid,$openid,$encrypt)
	{
		return "请输入员工工号：";
	}
	public static function directUrlAction($container)
	{
		return self::$direct_url;
	}
	public static function bindAction($controller,$con,$appid,$openid,$params)
	{
		$re = array("returncode"=>"0000");
		try
		{
			$bindinfo = $params->get("auth");
			$bindinfo = explode(",", $bindinfo);
			$bind_uid = $bindinfo[0];
			$authkey = count($bindinfo)==1?"":$bindinfo[1];
			$sql = "select appkey from we_appcenter_apps where appid=?";
			$ds = $con->GetData("t",$sql,array((string)$appid));
			if(count($ds["t"]["rows"])==0)
			{
				$re = array("returncode"=>"9999","msg"=>"appid is not found");
			}
			else
			{
				//$appkey = $ds["t"]["rows"][0]["appkey"];
				$sql = "delete from we_staff_account_bind where bind_account=? and bind_type=? and appid=?";
				$con->ExecSQL($sql,array((string)$openid,self::$bind_type,$appid));
				//$authkey=$authcode;//DES::encrypt2($authcode,$appkey);
				//$bind_uid=$authkey;
				$sql = "insert into we_staff_account_bind(bind_account,appid,bind_uid,authkey,bind_type,bind_created)values(?,?,?,?,?,now())";
				$con->ExecSQL($sql,array(
					(string)$openid,
					(string)$appid,
					(string)$bind_uid,
					(string)$authkey,
					(string)self::$bind_type
				));
				$app = new \Justsy\BaseBundle\Management\App($controller->container);
		        $app->refreshappbind(array("appid"=>$appid,"openid"=>$openid));				
			}
		}
		catch(\Exception $e)
		{
			return array("returncode"=>"9999","msg"=>$e->getMessage());
		}
		return self::responseJson(json_encode($re));
	}
	public static function bindBatAction($controller,$con,$appid,$eno,$encrypt,$params)
	{		
	}

	public static function rest($controller,$user,$re,$parameters,$need_params)
	{
		$appid = $parameters["appid"];
		$openid = $user->openid;
		$cookie_key= self::$bind_type."_".$openid;
		//获取绑定的sid		
		$app = new \Justsy\BaseBundle\Management\App($controller);
		$sessioninfo = $app->getappsession(array(
	            	"appid"=>$appid,
	            	"openid"=>$openid
	    ));
		if(empty($sessioninfo))
		{
		    return array("status"=>"fail","msg"=>"session已过期");
		}
		$data = $sessioninfo["access_token"];

		$appinfo = $app->getappinfo(array("appid"=>$appid));
		$restUrl = $re["inf_url"];
		$str_para = array();
		if (!empty($parameters) )
	    {
	        //将参数数组转化为字符串
	        if ( is_array($parameters) && !empty($need_params))
	        {
	            for ($i=0; $i <count($need_params) ; $i++) {
	              	$pname = $need_params[$i]["paramname"];
	              	$val = isset($parameters[$pname]) ? $parameters[$pname] : $need_params[$i]["paramvalue"];	              	 
	                $str_para[$pname]=$val;
	            }
	        }
	    }
		$re = Utils::do_post_request_cookie($restUrl."&".http_build_query($str_para),null,null,$cookie_key);
		//对data进行2次转换
		$tmpObj = json_decode($re,true);
		if(isset($tmpObj["data"]))
		{
			$txt = $tmpObj["data"];
			$fChar = substr($txt, 0,1);
			if($fChar=="{" || $fChar=="[")
			{
				$tmpObj["data"] = json_decode($txt,true);
				$re = json_encode($tmpObj);
			}
		}
		return $re;
	}

	private function responseJson($re){
		$re = "<script>if(navigator.userAgent.indexOf('Android')!=-1) {window.wefafa.onAuthResult('".$re."');} else {onAuthResult('".$re."');}</script>";
        $response = new Response($re);
        $response->headers->set('Content-Type', 'text/html');
        return $response;
    }	
}