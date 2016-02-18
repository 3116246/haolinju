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

class SsoMb2cAuth extends Controller implements  ISsoAuth
{
	//wefafa自动认证
	public static function userAuthAction($container,$request,$dbcon,$con_im,$login_account,$password,$comefrom)
	{
		$defaultPostURl = "http://10.100.20.27/CallCenter/ESB_InvokeService.ashx";

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
      $data = array();
      $data["loginName"] = $login_account;
      $data["password"] = $password;
      $data["isNeedSyn"] = false;      
      $para = "ServiceName=WXSC_Account&MethodName=POST:JSON:UserAuthentication&Message=".json_encode($data)."&Version=1";
      $container->get("logger")->err("SOA URL:".$httpUrlConfig."?".$para);
      $postresult = Utils::do_post_request($httpUrlConfig,$para);
      
      $container->get("logger")->err("SOA Result:".$postresult);
      $resultObject = json_decode($postresult,true);
      if(!empty($resultObject["errcode"]) || $resultObject["isSuccess"]===false)
      {
      	$re["returncode"] = ReturnCode::$ERROFUSERORPWD;
      	return $re;
      }
      $mbuser = $resultObject["results"];
      $fafa_account =strtolower($login_account."@fafatime.com");
      if(count($mbuser)>0)
      {
      	 $mbuser = $mbuser[0];
      	 $nickName = isset($mbuser["nickName"]) ? $mbuser["nickName"] : $mbuser["phoneNumber"];      	 
      	 $staff = new Staff($dbcon,$con_im,$fafa_account);
         $staffinfo=$staff->getInfo();
         if(empty($staffinfo))
         {
         	//新用户：注册 激活
        	$enInfo= $cacheobj->getInfo($eno);
        	$active=new \Justsy\BaseBundle\Controller\ActiveController();
            $active->setContainer($container);
            $uid = strtolower($mbuser["id"]);
            
            $active->doSave(array(
                            	'account'=> $fafa_account,
                            	'realName'=> $nickName,
                            	'passWord'=> $password,
                            	'eno'=> $eno,
                            	'ename'=> $enInfo["ename"],
                            	'isNew'=>'0',
                            	'mailtype'=> "1",
                            	'isSendMessage'=>"N",
                            	'import'=>'1'
            ));
            $sex_id = "1";
         	$duty = isset($mbuser["userRoles"])? $mbuser["userRoles"] : "";
         	$ldap_uid = isset($mbuse["id"]) ? $mbuser["id"] : "";
         	$tmp = "";
            if(!empty($duty) &&count($duty)>0)
            {
            	for($i=0; $i<count($duty); $i++)
            	{
            		$tmp = $duty[$i]["roleName"];
            		if($tmp=="Designer")
            		{
            			break;
            		}
            	}
            }
            if($tmp=="Designer") $duty="造型师";
            else $duty = "";
            $sql="update we_staff set ldap_uid=?,sex_id=?,duty=? where login_account=?";
			$params=array($uid,(string)$sex_id,$duty,$fafa_account);
					
			$dbcon->ExecSQL($sql,$params);
			if(!empty($mbuser["phoneNumber"]))
			{
				$staff->checkAndUpdate(null,$mbuser["phoneNumber"],null,null);
			}
         }
         else
         {
         	$duty = isset($mbuser["userRoles"])? $mbuser["userRoles"] : "";
         	$ldap_uid = isset($mbuser["id"]) ? $mbuser["id"] : "";
         	$tmp = "";
            if(!empty($duty) &&count($duty)>0)
            {
            	for($i=0; $i<count($duty); $i++)
            	{
            		$tmp = $duty[$i]["roleName"];
            		if($tmp=="Designer")
            		{
            			break;
            		}
            	}
            }
            if($tmp=="Designer") $duty="造型师";
            else $duty = "";
         	//更新信息
         	$staff->checkAndUpdate($nickName,$mbuser["phoneNumber"],null,$duty,$ldap_uid);
         }
         //头像
         $headUrl = $mbuser["headPortrait"];
      }
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
      $re["returncode"] = ReturnCode::$SYSERROR;  	    
    }
    return $re;
	}
	

	public function createUser($container,$username,$pwd)
	{
		$defaultPostURl = "http://10.100.20.27/CallCenter/ESB_InvokeService.ashx";
		$cacheobj = new Enterprise(null,$container->get("logger"),$container);//	
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
		try{
		      $data = array();
		      $data["loginName"] = $username;
		      $data["password"] = $pwd;
		      $data["roleId"] = "1";
		      $data["isNeedSyn"] = false;
		      $para = "ServiceName=WXSC_Account&MethodName=POST:JSON:generalRegister&Message=".json_encode($data);
		      $container->get("logger")->err("SOA URL:".$httpUrlConfig."?".$para);
		      $postresult = Utils::do_post_request($httpUrlConfig,$para);
		      $container->get("logger")->err("SOA Result:".$postresult);
		      $resultObject = json_decode($postresult,true);
		      return $resultObject;
		}
		catch(\Exception $e)
		{
			$container->get("logger")->err($e->getMessage());
			return array("isSuccess"=>false,"message"=>"");
		}		
	}

	public static function registerToPlatform($container,$type,$uid,$openid,$nickName)
	{
		$defaultPostURl = "http://10.100.20.27/CallCenter/ESB_InvokeService.ashx";

		$cacheobj = new Enterprise(null,$container->get("logger"),$container);//	
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
		try{
		      $data = array();
		      $data["providerLoginKey"] = $uid;
		      $data["loginProviderName"] = $type;
		      $data["nickName"] = $nickName;
		      $data["openid"] = $openid;
		      $data["isNeedSyn"] = false;
		      $para = "ServiceName=WXSC_Account&MethodName=POST:JSON:loginWithRegisterExternal&Message=".json_encode($data)."&Version=1";
		      $container->get("logger")->err("SOA URL:".$httpUrlConfig."?".$para);
		      $postresult = Utils::do_post_request($httpUrlConfig,$para);
		      $container->get("logger")->err("SOA Result:".$postresult);
		      $resultObject = json_decode($postresult,true);
		      return $resultObject;
		}
		catch(\Exception $e)
		{
			$container->get("logger")->err("SOA ERROR:".$e);
		}
	}
}