<?php

namespace Justsy\InterfaceBundle\SsoAuth;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Login\UserProvider;
use Justsy\BaseBundle\Login\UserSession;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Management\Staff;

//从华润ldap集成登录
class SsoWefafaMd5Auth extends Controller implements  ISsoAuth
{
	public static function userAuthAction($container,$request,$dbcon,$con_im,$login_account,$password,$comefrom)
	{		
		$login_account = strtolower($login_account);
	    try 
	    {
	      $password =strtoupper(md5($password));
          //$container->get("logger")->err($password);

          $staff = new Staff($dbcon,$con_im,strtolower($login_account));
	      $user=$staff->getInfo();
	      if($user==null || $user["state_id"]!="1")
	      {
	      	$re["returncode"] = ReturnCode::$ERROFUSERORPWD;
	      	return $re;
	      }
          $login_account = $user["login_account"];
	      $Obj = new \Justsy\BaseBundle\Login\UserProvider($container);
	      // 
          $factory = $container->get('security.encoder_factory');
	      //判断是否修改过密码.这时针对从第三方注册的帐号的密码为不可解开密文的情况时，sns中的密码和t_code字段临时存储为完全相同的数据
	      //程序判断到这种情况 时，需要进行内部自动更改密码操作，之前的临时数据即为用户新密码
	      //$container->get("logger")->err($user["password"]."==".$user["t_code"]);
	      if($user["password"]==$user["t_code"])
	      {
	      	 $tmpPass = DES::encrypt($password);
	      	 if($tmpPass!=$user["t_code"])  //修改后第一次登录，直接使用输入的密码md5并判断是否正确
	      	 {
	      	 	$re["returncode"] = ReturnCode::$ERROFUSERORPWD;
	      	 }
	      	 //更新密码
	      	 $staff->changepassword($login_account,$password,$factory);      	 	      	
	      }
		 
		  $user = new UserSession($login_account, $password, $login_account, array("ROLE_USER"));
          $encoder = $factory->getEncoder($user);
          $password_enc = $encoder->encodePassword($password,$login_account);

          $user = $Obj->loadUserByUsername($login_account,$comefrom);
		  //$container->get("logger")->err($user->getPassword()."==".$password_enc);

	      $logined= 1;
	  	  if($user->getPassword() != $password_enc) 
	  	  {
	  	  	//如果密码不正确时，有可能是修改了密码，只刷新了一台服务器上的缓存，其他集群环境中的还是原来的缓存
	  	  	//刷新当前服务器的人员信息才重试
	  	  	$u_staff = new Staff($dbcon,$con_im,$user->getusername(),$container->get('logger'));
            $user = $u_staff->getInfo(true);//刷新人员信息
            //$container->get("logger")->err("refresh cache data.....");
	        $user = $Obj->loadUserByUsername($login_account,$comefrom);
	        if($user->getPassword() != $password_enc)
	        {	      			     
          		$container->get("logger")->err($login_account."==>".$password."=>".$password_enc."=>".$user->getPassword());	  	  	
	  	    	$re["returncode"] = ReturnCode::$ERROFUSERORPWD;
	  	    	$logined=null;
	  		}
	  	  }
	  	  if(!empty($logined))
	  	  {
	    	//�Ǽ�seesion
	    	$token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken($user, $user->getPassword(), "secured_area", $user->getRoles());
	    	$container->get("security.context")->setToken($token);    	  
	        $session = $request->getSession()->set('_security_'.'secured_area',  serialize($token));        
	    	$event = new \Symfony\Component\Security\Http\Event\InteractiveLoginEvent($request, $token);
	    	$container->get("event_dispatcher")->dispatch("security.interactive_login", $event);
	    	     	  
	    	$re["returncode"] = ReturnCode::$SUCCESS;
	    	$re["openid"] = $user->openid;
	    	$re["login_account"] = $login_account;
	    	$re["ldap_uid"] = $user->ldap_uid;
	    	$re["jid"] = $user->fafa_jid;
	    	$re["des"] = $user->t_code;
	  	  }  	  
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