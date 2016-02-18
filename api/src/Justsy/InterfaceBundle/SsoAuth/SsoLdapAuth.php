<?php

namespace Justsy\InterfaceBundle\SsoAuth;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Login\UserProvider;
use Justsy\BaseBundle\Login\UserSession;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Management\Enterprise;
use Justsy\BaseBundle\Management\Staff;
class SsoLdapAuth extends Controller implements  ISsoAuth
{
	private $logger = null;
	//ldap认证
	public static function userAuthAction($container,$request,$con,$con_im,$login_account,$password,$comefrom)
	{
		$this->logger = $container->get("logger");
		if(empty($login_account) || empty($password))
    	{
    		$re['returncode']=ReturnCode::$OTHERERROR;
    		$re['msg']="无效的帐号或密码。";
    		return $re;
    	}
		$cacheobj = new Enterprise(null,$this->logger,$container);//	
		$authConfig = 	$cacheobj->getUserAuth();
		if(empty($authConfig)|| count($authConfig)==0)
		{
    		$re['returncode']=ReturnCode::$OTHERERROR;
    		$re['msg']="无效的认证配置";
    		return $re;			
		}
		$ldapConfig = $authConfig["ssoauthldap"];
        if(empty($ldapConfig))
        {
    		$re['returncode']=ReturnCode::$OTHERERROR;
    		$re['msg']="无效的目录配置";
    		return $re;	        	
        }
        //有效的配置及格式：{"ENO":"","LDAP":
        //                             {"HOST":"XXX","DN":"","LIST":
        //                             			{"LDAP_ID":"uid","NAME":"cn","DEPTNAME":"ou","MOBILE":"mobile"}
        //                             }
        //                   }
        try 
	    {
	        $ldapConfgiObject = json_decode($ldapConfig,true);
	        $eno = $ldapConfgiObject["ENO"];
	        $ldapConfgiObject = $ldapConfgiObject["LDAP"];
	        $result = $this->loginLdapServer($ldapConfgiObject,$login_account,$password);
	        if(isset($result['s']))
	        {
	    		$re['returncode']=ReturnCode::$ERROFUSERORPWD;
	    		$re['msg']="无效的帐号或密码";
	    		return $re;        	
	        }

	        $uid =$result[$ldapConfgiObject["LIST"]["LDAP_ID"]];
	        $name = $ldapConfgiObject["LIST"]["NAME"];
	        $deptname = $ldapConfgiObject["LIST"]["DEPTNAME"];
	        $mobile = $ldapConfgiObject["LIST"]["MOBILE"];

	        $fafa_account = strtolower($uid."@fafatime.com");

			$user = new UserSession($fafa_account, $password,$fafa_account, array("ROLE_USER"));
			$factory = $this->get("security.encoder_factory");
			$encoder = $factory->getEncoder($user);
			$snspwd = $encoder->encodePassword($password,$user->getSalt());

	        $staff = new Staff($con,$con_im,$fafa_account);
	        $staffinfo=$staff->getInfo();
	        if(!empty($staffinfo))
	        {
	        	$staff->checkAndUpdate($result[$name],$result[$mobile],$result[$deptname]);
	        	if($snspwd!=$staffinfo["password"])
	        	{
					$tcode =  DES::encrypt($password);
					//更改sns密码
					$sql="update we_staff set password=?,t_code=? where login_account=?";
					$params=array((string)$snspwd,(string)$tcode,$fafa_account);
					$da->ExecSQL($sql,$params);
							      		
					//更改im密码
					$sql="update users set password=? where username=?";
					$params=array($password,$staffinfo['fafa_jid']);
					$dm->ExecSQL($sql,$params);
	        	}
	        }
	        else
	        {
	        	$enInfo= $cacheobj->getInfo($eno);
	        	$active=new \Justsy\BaseBundle\Controller\ActiveController();
	            $active->setContainer($container);
	            $active->doSave(array(
	                            	'account'=> $fafa_account,
	                            	'realName'=>$name,
	                            	'passWord'=> $password,
	                            	'eno'=> $eno,
	                            	'ename'=> $enInfo["ename"],
	                            	'isNew'=>'0',
	                            	'mailtype'=> "1",
	                            	'isSendMessage'=>"N",
	                            	'import'=>'1'
	            ));
	            $sql="update we_staff set ldap_uid=? where login_account=?";
				$params=array(strtolower($uid),$fafa_account);
						
				$con->ExecSQL($sql,$params);

				//$staffinfo=$staff->getInfo();	
	        }
			//登录wefafa系统		    
	      	//�Ǽ�seesion
	    	$token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken($user, $user->getPassword(), "secured_area", $user->getRoles());
	    	$this->get("security.context")->setToken($token);    	  
	        $session = $request->getSession()->set('_security_'.'secured_area',  serialize($token));        
	    	$event = new \Symfony\Component\Security\Http\Event\InteractiveLoginEvent($request, $token);
	    	$this->get("event_dispatcher")->dispatch("security.interactive_login", $event);
	    	     	  
	    	$re["returncode"] = ReturnCode::$SUCCESS;	
	    	$re["openid"] = $user->openid;
	    	$re["login_account"] = $fafa_account;
	    	$re["ldap_uid"] = $user->ldap_uid;
	    	$re["jid"] = $user->fafa_jid;
	    	$re["des"] = $user->t_code;
	    } 
	    catch (\Symfony\Component\Security\Core\Exception\UsernameNotFoundException $e)
	    {
	      $re["returncode"] = ReturnCode::$ERROFUSERORPWD;
	    }
	    catch (\Exception $e) 
	    {
	    	$this->logger->err($e);
	      	$re["returncode"] = ReturnCode::$SYSERROR;  	    
	    }
	    retur $re;
	}

	private function loginLdapServer($config,$userid,$pwd)
	{
			$ldap_admin = $userid;
			$ldap_password = $pwd;
			$conn = ldap_connect($config["HOST"]);
			if(!$conn){
				return array('s'=>'0','m'=>'服务器连接失败','info'=>array());
			}
			$this->logger->info("日志记录：服务器连接成功");
			ldap_set_option($conn,LDAP_OPT_PROTOCOL_VERSION,3);
			ldap_set_option($conn,LDAP_OPT_REFERRALS,0);
			
			$bind = ldap_bind($conn, "uid=".$userid.",".$config["DN"],$pwd);
			if(!$bind){
				return array('s'=>'0','m'=>'服务器登录失败','info'=>array());
			}
			$this->logger->info("日志记录：服务器绑定成功");
			$attrArray =array();
			foreach ($config["LIST"] as $key => $value) {
				$attrArray[] = $value;
			}
			$result = ldap_search($conn,$config["DN"],"uid=".$userid,$attrArray);			
			if(!$result){
				return array('s'=>'0','m'=>'为获取到任何数据','info'=>array());
			}
			$info = ldap_get_entries($conn, $result);
			rturn $info[0];
	}
	
}