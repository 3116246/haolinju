<?php

namespace Justsy\InterfaceBundle\SsoAuth;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Login\UserProvider;
use Justsy\BaseBundle\Login\UserSession;
use Justsy\InterfaceBundle\Common\ReturnCode;

class SsoDefaultAuth extends Controller implements  ISsoAuth
{
	//wefafa自动认证
	public static function userAuthAction($container,$request,$dbcon,$con_im,$login_account,$password,$comefrom)
	{		
		$login_account =strtolower($login_account);
	    try 
	    {
	      $Obj = new \Justsy\BaseBundle\Login\UserProvider($container);
	      $user = $Obj->loadUserByUsername($login_account,$comefrom);  
	      $logined= 1; 
	      //$container->get("logger")->err(json_encode($user));
	      $user2 = new UserSession($user->getusername(), $password, $user->getusername(), array("ROLE_USER"));
	      $factory = $container->get("security.encoder_factory");
	      $encoder = $factory->getEncoder($user2);
	      $password_enc = $encoder->encodePassword($password,$user2->getSalt());
	      /*$factory = $container->get('security.encoder_factory');
	      $encoder = $factory->getEncoder($user);
	      $password_enc = $encoder->encodePassword($password, $user->getSalt());	     
    
	      $logined= 1;*/
	  	  if($user->getPassword() != $password_enc) 
	  	  {
	  	  	//如果密码不正确时，有可能是修改了密码，只刷新了一台服务器上的缓存，其他集群环境中的还是原来的缓存
	  	  	//刷新当前服务器的人员信息才重试
	  	  	$u_staff = new \Justsy\BaseBundle\Management\Staff($dbcon,$con_im,$user->getusername(),$container->get('logger'),$container);
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
	  	  	$user->comefrom = $comefrom;//登录源
	    	//�Ǽ�seesion
	    	//$token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken($user, $user->getPassword(), "secured_area", $user->getRoles());
	    	//$container->get("security.context")->setToken($token);    	  
	        //$session = $request->getSession()->set('_security_'.'secured_area',  serialize($token));        
	    	//$event = new \Symfony\Component\Security\Http\Event\InteractiveLoginEvent($request, $token);
	    	//$container->get("event_dispatcher")->dispatch("security.interactive_login", $event);
	    	     	  
	    	$re["returncode"] = ReturnCode::$SUCCESS;
	    	$re["openid"] = $user->openid;
	    	$re["login_account"] = $user->getusername();
	    	$re["ldap_uid"] = $user->ldap_uid;
	    	$re["jid"] = $user->fafa_jid;
	    	$re["des"] = DES::encrypt($user->t_code);  //im登录密码。生成session时对该属性解密，在些进行重新加密得到
	  	  } 	  
	    } 
	    catch (\Symfony\Component\Security\Core\Exception\UsernameNotFoundException $e)
	    {
	      $re["returncode"] = ReturnCode::$ERROFUSERORPWD;
	    }
	    catch (\Exception $e) 
	    {
	      $re["returncode"] = ReturnCode::$SYSERROR;  	    
	    }
	    return $re;
	}
	
}