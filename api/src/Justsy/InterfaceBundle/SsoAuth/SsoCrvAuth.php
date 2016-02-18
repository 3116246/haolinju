<?php

namespace Justsy\InterfaceBundle\SsoAuth;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Management\Enterprise;
use Justsy\BaseBundle\Login\UserProvider;
use Justsy\BaseBundle\Login\UserSession;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Management\Staff;

//从万家门户集成登录
class SsoCrvAuth extends Controller implements  ISsoAuth
{
	//万家认证
	public static function userAuthAction($container,$request,$dbcon,$con_im,$login_account,$password,$comefrom)
	{
		//判断是门户登录还是独立登录
		if(strlen($login_account)<32)
		{
			//独立登录模式
			$classname = "\Justsy\InterfaceBundle\SsoAuth\SsoWefafaMd5Auth";
		
			$re = call_user_func(array($classname, 'userAuthAction'),$container,$request,$dbcon,$con_im,$login_account,$password,$comefrom); 
			return $re;
		}
		//解密token和pass
		$token = DES::decrypt_crv_fortoken($login_account,"cn.com.crv.ivv");
		if($token===false)
		{
			$container->get("logger")->err("decrypt token error:".$login_account);
	      	$re["returncode"] = ReturnCode::$SYSERROR;
	      	return $re;			
		}
		$pass = DES::decrypt_crv_fortoken($password,"cn.com.crv.ivv");
		if($pass===false)
		{
			$container->get("logger")->err("decrypt password error:".$password);
	      	$re["returncode"] = ReturnCode::$SYSERROR;
	      	return $re;
		}
		$defaultPostURl = "http://cremobile.crc.com.cn:9090/conn/CrvSecurityWS/userresource/userprofile";

		$cacheobj = new Enterprise($dbcon,$container->get("logger"),$container);//	
		$authConfig = 	$cacheobj->getUserAuth();
		$httpUrlConfig = $authConfig["ssoauthurl"];

		if(empty($httpUrlConfig)) 
		{
			$httpUrlConfig = $defaultPostURl;
			$eno = "100001"; 
		}
		else
		{
			$ldapConfgiObject = json_decode($httpUrlConfig,true);
	        $eno = $ldapConfgiObject["ENO"];
	        $httpUrlConfig = $ldapConfgiObject["URL"];
		}
	    try
	    {
	      $para = "access_token=".$token;
	      $container->get("logger")->err("SOA URL:".$httpUrlConfig."?".$para);
	      $postresult = Utils::getUrlContent($httpUrlConfig."?".$para,null);
	      $container->get("logger")->err("SOA Result:".$postresult);
	      $resultObject = json_decode($postresult,true);	

	      //$resultObject=array("empUid"=>"test101","empName"=>"TEST101"); //集成测试

	      if(!isset($resultObject["empUid"]))
	      {
	      	$container->get("logger")->err("get user info error.".$postresult);
	      	$re["returncode"] = ReturnCode::$SYSERROR;
	      	return $re;
	      }
	      $crvuser = $resultObject["empUid"];
	      $email = $resultObject["email"];
	      
	      $fafa_account = !empty($crvuser)? strtolower($crvuser) : $email;
	      
	      	$Obj = new \Justsy\BaseBundle\Login\UserProvider($container);
	      	$user = $Obj->loadUserByUsername($fafa_account,$comefrom);   
	      
	    	//�Ǽ�seesion
	    	$token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken($user, $user->getPassword(), "secured_area", $user->getRoles());
	    	$container->get("security.context")->setToken($token);    	  
	        $session = $request->getSession()->set('_security_'.'secured_area',  serialize($token));        
	    	$event = new \Symfony\Component\Security\Http\Event\InteractiveLoginEvent($request, $token);
	    	$container->get("event_dispatcher")->dispatch("security.interactive_login", $event);

	    	$re["returncode"] = ReturnCode::$SUCCESS;
	    	$re["openid"] = $user->openid;
	    	$re["login_account"] = $fafa_account;
	    	$re["ldap_uid"] = $user->ldap_uid;
	    	$re["jid"] = $user->fafa_jid;
		    //为了避免用户修改密码后只刷新了所在服务器，im密码实时获取
		    $sql = "select password from users where username=?";
		    $iminfo = $con_im->GetData("im",$sql,array((string)$user->fafa_jid));
		    $re["des"] =count($iminfo["im"]["rows"])>0 ? $iminfo["im"]["rows"][0]["password"] : "";
	    } 
	    catch (\Symfony\Component\Security\Core\Exception\UsernameNotFoundException $e)
	    {
	      $re["returncode"] = ReturnCode::$ERROFUSERORPWD;
	    }
	    catch (\Exception $e) 
	    {
	    	$container->get("logger")->err($e);
	       	$re["returncode"] = ReturnCode::$SYSERROR;  	    
	    }
	    return $re;
	}
}