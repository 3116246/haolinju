<?php

namespace Justsy\OpenAPIBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Login\UserProvider;
use Justsy\BaseBundle\Login\UserSession;
use Justsy\InterfaceBundle\Common\ReturnCode;

use Justsy\BaseBundle\Common\Cache_Enterprise;

class SsoOAuth2Controller extends Controller implements  ISso
{
	public static $bind_type = "OAuth2";
	//加载授权页面
	public static function ssoAction($container,$conn,$appid,$openid,$token,$encrypt)
	{
		//判断token是否过期，没有过期且有效，直接返回
		$cacheKey = md5($appid.$openid);
		$data=Cache_Enterprise::get(Cache_Enterprise::$EN_OAUTH2,$cacheKey,$container);
		if(!empty($data) && strpos($appid,"SSO_")===false)
		{
			//$container->get("logger")->err(json_encode($data));
			$data = json_decode($data,true);
			if($data["expires_in"]>time())
			{
				$result =array("returncode"=>"0000","data"=>$data);	

				if(strpos($appid,"SYS_")!==false)
					return self::responseJsonStr(json_encode($result));
				else
					return self::responseJson(json_encode($result));				
			}
		}
    	$isLogin = null;
    	$app = new \Justsy\BaseBundle\Management\App($container->container);
    	if(strpos($appid,"SSO_")!==false)
    	{
    		$isLogin = $appid;
    		//新浪微博集成登录
    		//获取微博对应的业务系统认证配置
    		$syspara = new \Justsy\BaseBundle\DataAccess\SysParam($container);
    		$appid = $syspara->GetSysParam(strtolower($appid)."_appid");
    		if(empty($appid))
    		{
    			$resp = new Response("未配置微博业务系统或参数sso_sina_appid");
	   			$resp->headers->set('Content-Type', 'text/html');
	   			return $resp;
    		}
    		$appdata = $app->getbussysteminfo(array("appid"=>$appid));
    		$appid = $isLogin;//把appid还原成sso集成登录标识
    	}		
		//重新授权
		else
		{
    		if(strpos($appid,"SYS_")!==false)
    		{
    			//业务系统直接对接
    			$appdata = $app->getbussysteminfo(array("appid"=>substr($appid, 4)));
    		}
    		else
    		{
    			$appdata = $app->getappinfo(array("appid"=>$appid));
    		}
		}
		if(empty($appdata))
		{
			return "无效的APPID";
		}
		$auth_url = $appdata["authorization_url"];
		if(empty($auth_url))
		{
			//将直接采用client_credentials方式，直接获取token
			return self::tokenAction($container,$conn,$appid.",".$openid,"",$encrypt);
		}
	
		$para_name = $appdata["redirecturl_para_name"];
		if(empty($para_name)) $para_name = "redirect_uri";
		$auth_url .= "?response_type=code&".$para_name."=".$appdata["redirection_url"];

		$para_name = $appdata["clientid_para_name"];
		if(empty($para_name)) $para_name = "client_id";
		$auth_url .= "&".$para_name."=".$appdata["clientid"];

		$auth_url .= "&state=".$appid.",".$openid;
		$container->get("logger")->err($auth_url); 
		return Utils::http_redirect($auth_url);
	}
	//换取token
	public static function tokenAction($container,$con,$appid,$code,$encrypt)
	{
		$app = new \Justsy\BaseBundle\Management\App($container->container);
		$stat_v = explode(",", $appid);
		$appid = $stat_v[0];
		$openid = $stat_v[1];
    	$isLogin = null;
    	if(strpos($appid,"SSO_")!==false)
    	{
    		$isLogin = $appid;
    		//新浪微博集成登录
    		//获取微博对应的业务系统认证配置
    		$syspara = new \Justsy\BaseBundle\DataAccess\SysParam($container);
    		$appid = $syspara->GetSysParam(strtolower($appid)."_appid");
    		if(empty($appid))
    		{
    			$resp = new Response("未配置微博业务系统或参数sso_sina_appid");
	   			$resp->headers->set('Content-Type', 'text/html');
	   			return $resp;
    		}
    		$appdata = $app->getbussysteminfo(array("appid"=>$appid));
    		$appid = $isLogin;//把appid还原成sso集成登录标识
    	}	
    	else
    	{	
    		if(strpos($appid,"SYS_")!==false)
    		{
    			//业务系统直接对接
    			$appdata = $app->getbussysteminfo(array("appid"=>substr($appid, 4)));
    		}
    		else
    		{
    			$appdata = $app->getappinfo(array("appid"=>$appid));
    		}
		}
		if(empty($appdata))
		{
			return "无效的APPID";
		}
		$token_url = $appdata["token_url"];
		if(empty($token_url))
		{
			return "无效的配置：令牌获取地址无效";
		}

		$token_method = $appdata["token_method"];
		$token_method = empty($token_method) ? "POST" : $token_method;

		$auth_url = $token_url;
		$para_name = $appdata["redirecturl_para_name"];
		if(empty($para_name)) $para_name = "redirect_uri";
		$paraString = "";
		if(empty($code))
			$paraString .= "grant_type=client_credentials&".$para_name."=".$appdata["redirection_url"];
		else
			$paraString .= "grant_type=authorization_code&".$para_name."=".$appdata["redirection_url"];

		$para_name = $appdata["clientid_para_name"];
		if(empty($para_name)) $para_name = "client_id";
		$paraString .= "&".$para_name."=".$appdata["clientid"];

		$para_name = $appdata["clientkey_para_name"];
		if(empty($para_name)) $para_name = "client_secret";
		$paraString .= "&".$para_name."=".$appdata["clientkey"];

		$paraString .= "&code=".$code;

		$paraString .= "&state=".$appid.",".$openid;
		$container->get("logger")->err($auth_url." -- ".$paraString); 
		if(strtoupper($token_method)=="POST")
			$token =  Utils::do_post_request($auth_url,$paraString);
		else
			$token =  Utils::do_post_request($auth_url."?".$paraString,null);
		$container->get("logger")->err("token value:".$token); 
		$retuenAry = array();
		if(substr($token,0,1)=="{")
		{
			$retuenAry = json_decode($token,true);
		}
		else
		{
			$rv = 	explode("&", $token);
			for ($i=0; $i < count($rv); $i++) { 
				 $rv_i = explode("=", $rv[$i]);
				 $retuenAry[$rv_i[0]] =preg_replace("/'/is", "", $rv_i[1]) ;
			}
		}
		$result =array("returncode"=>"0000","data"=>null);

		$para_name = $appdata["token_para_name"];
		if(empty($para_name)) $para_name="access_token";

		if(isset($retuenAry[$para_name]))
		{
			$retuenAry[$appdata["clientid_para_name"]] = $appdata["clientid"];
			$retuenAry[$appdata["clientkey_para_name"]] = $appdata["clientkey"];
			$result["returncode"] = "0000";
			$retuenAry[$appdata["token_para_name"]] = $retuenAry[$para_name];
			$app->setappsession(array("session"=>$retuenAry,"openid"=>$openid,"appid"=>$appid));
		}
		else
		{
			$result["returncode"] = "9999";
		}
		$result["data"] = $retuenAry;
		if(!empty($isLogin))
		{
			if(strpos($isLogin,"SSO_")!==false)
			{
				if($isLogin=="SSO_SINA") $uid= $retuenAry["uid"];
				else if($isLogin=="SSO_WECHAT") $uid= $retuenAry["openid"];
				//判断并注册用户
				$staffobj = new \Justsy\BaseBundle\Management\Staff($container->get("we_data_access"),$container->get("we_data_access_im"),$uid,null,$container->container);
				$re = $staffobj->createstaff(array(
					"password"=> rand(100000,999999),
					"eno"=>Utils::$PUBLIC_ENO,
					"nick_name"=>$uid,
					"ldap_uid"=>$uid,
					"account"=>""
				));
				$re["data"]["des"] = DES::decrypt($re["data"]["t_code"]);
			}
			return self::responseLoginJson(json_encode($re));
		}
		else
		{
			if(strpos($appid,"SYS_")!==false)
				return self::responseJsonStr(json_encode($result));
			else
				return self::responseJson(json_encode($result));
		}
	}
	//重定向地址
	public static function directUrlAction($container)
	{
		$code = $container->get("request")->get("code");
		$access_token = $container->get("request")->get("access_token");
		$appid = $container->get("request")->get("state");

		if(!empty($code))
		{
			//获取token
			return self::tokenAction($container,null,$appid,$code,null);
		}
		if(strpos($appid,"SYS_")!==false)
			return self::responseJsonStr($access_token);
		else
			return self::responseJson($access_token);
	}

    public static function bindTitleAction($controller,$con,$appid,$openid,$encrypt)
    {

    }

    public static function bindAction($controller,$con,$appid,$openid,$params)
    {
		$re = array("returncode"=>"0000");
		try
		{
			$bindinfo = $params->get("auth");
			$bindinfo = explode(",", $bindinfo);
			$bind_uid = $bindinfo[0];
			$authkey = count($bindinfo)==1?"":DES::encrypt($bindinfo[1]);			
			$app = new \Justsy\BaseBundle\Management\App($controller->container);
		    
			$appdata = $app->getappinfo(array("appid"=>$appid));//获取应用信息
			//自动身份认证			
			/*$cookie_key= self::$bind_type."_".$openid;
			$loginUrl = $appdata["authorization_url"];
			if(!empty($loginUrl))
			{
				$authResult = Utils::do_get_request_cookie($loginUrl."&".http_build_query(array("uid"=>$bind_uid,"upwd"=>md5(DES::decrypt($authkey)))),
		            	null,
		            	null,
		            	$cookie_key);
				$authResult = json_decode($authResult,true);
				if(!isset($authResult["islogin"]) || $authResult["islogin"]!="1")
				{
			        return $controller->render("JustsyBaseBundle:AppCenter:h5bundle.html.twig",
	  	 	      		array(	'appid'=> $appid,
	  	 	      				'openid'=>$openid,
	  	 	      				'errormsg'=>'绑定的帐号或密码不正确',
	  	 	      				'ssomodule'=>self::$bind_type."Controller"));
				}
			}*/
			$app->setappbind(array(	"appid"=>$appid,
		        					"openid"=>$openid,
		        					"bind_type"=>self::$bind_type,
		        					"bind_uid"=>$bind_uid,
		        					"authkey"=>$authkey
		    ));
		}
		catch(\Exception $e)
		{
			$response = new Response($e->getMessage());
        	$response->headers->set('Content-Type', 'text/html');
        	return $response;
		}
		return self::responseJson(json_encode($re));    	
    }

    public static function bindBatAction($controller,$con,$appid,$eno,$encrypt,$params)
    {}

    public static function rest($controller,$user,$re,$parameters,$need_params)
    {
    	$api_parameter = "";
    	$appid = $parameters["appid"];
		$openid = $user->openid;
		$cacheKey = md5($appid.$openid);
        $data=Cache_Enterprise::get(Cache_Enterprise::$EN_OAUTH2,$cacheKey,$controller);
        if($data==null)
        {
            throw new \Exception("token 已过期，请重新获取");
        }
        if(isset($data["expires_in"]) && (int)$data["expires_in"]<time())
        {
            throw new \Exception("token 已过期，请重新获取");
        }
        $access_token = json_decode($data,true);
        $str_para = array();
		if (!empty($parameters) )
	    {
	        //将参数数组转化为字符串
	        if ( is_array($parameters) && !empty($need_params))
	        {
	            for ($i=0; $i <count($need_params) ; $i++) {
	              	$pname = $need_params[$i]["paramname"];
	              	if(!empty($access_token) && isset($access_token[$pname]))
		            {
		              //先从授权结果中匹配
		              $val = $access_token[$key];
		            }
	              	else 
	              		$val = isset($parameters[$pname]) ? $parameters[$pname] : $need_params[$i]["paramvalue"];	              	 
	                $str_para[$pname]=$val;
	            }
	        }
	    }
        $restUrl = $re["inf_url"];
        if(strpos($restUrl,"?")===false)
	    {
	    	$restUrl = $restUrl."?".http_build_query($str_para);
	    }
		else
		{
			$restUrl = $restUrl."&".http_build_query($str_para);
		}
	    $controller->get("logger")->err("===============restUrl:".$restUrl);
		$re = Utils::do_post_request($restUrl,null,null);
		return $re;
    }

	private function responseJsonStr($re){
		$response = new Response($re);
        $response->headers->set('Content-Type', 'text/html');
        return $response;
    }
	private function responseJson($re){
		$re = "<script>if(navigator.userAgent.indexOf('Android')!=-1) {window.wefafa.onAuthResult('".$re."');} else {onAuthResult('".$re."');}</script>";
        $response = new Response($re);
        $response->headers->set('Content-Type', 'text/html');
        return $response;
    }

	private function responseLoginJson($re){
		$re = "<script>if(navigator.userAgent.indexOf('Android')!=-1) {window.wefafa.onLoginAuthResult('".$re."');} else {var a='".$re."';location.href='ios:/wefafa/sianlogin';}</script>";
        $response = new Response($re);
        $response->headers->set('Content-Type', 'text/html');
        return $response;
    }    
}