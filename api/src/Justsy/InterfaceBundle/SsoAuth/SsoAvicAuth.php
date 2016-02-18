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

class SsoAvicAuth extends Controller implements  ISsoAuth
{
  //wefafa自动认证
  public static function userAuthAction($container,$request,$dbcon,$con_im,$login_account,$password,$comefrom)
  {
    $rest = "/rest/authenticate";
    $defaultPostURl = "https://sso.avicmall.com:8443";
    $appcodeConfig = "fafa-app";
    $appkeyConfig = "DKGHwqJ5H91noPYNYm9b8EUPQSY";
    $cacheobj = new Enterprise($dbcon,$container->get("logger"),$container);//  
    $authConfig =   $cacheobj->getUserAuth();
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
      $appcodeConfig = $ldapConfgiObject["AppCode"];
      $appkeyConfig = $ldapConfgiObject["AppKey"];
    }
    $httpUrlConfig = $httpUrlConfig.$rest;
    
    try
    {
      $reqHeader = SsoAvicAuth::getHeaders($appcodeConfig,$appkeyConfig);
      $para =array( "username"=>$login_account,"password"=>$password);
      //$container->get("logger")->err("SOA URL:".$httpUrlConfig." Body:".json_encode($para));
      $postresult = Utils::do_post_request($httpUrlConfig,json_encode($para),$reqHeader,$container->get("logger"));
      
      $container->get("logger")->err("SOA Result:".$postresult);
      $resultObject = json_decode($postresult,true);
      if(!isset($resultObject["status"]))
      {
        $re["returncode"] = ReturnCode::$ERROFUSERORPWD;
        $re["msg"] = "服务器异常";
        return $re;
      }
      if(!$resultObject["status"] || $resultObject["status"]=="false")
      {
        $re["returncode"] = ReturnCode::$ERROFUSERORPWD;
        $re["msg"] = $resultObject["message"];
        return $re;
      }
      $usertoken = $resultObject["ticketEntry"]["ticketValue"];
      $user = $resultObject["user"]; //用户信息

      $eninfo = $cacheobj->getInfo($eno);
      $domain = $eninfo["edomain"];
      $domain =  strpos($domain,".")===false?"fafatime.com": $domain; 

      $fafa_account =strtolower($login_account."@".$domain);

      //$nickName = SsoAvicAuth::getUserAttr($user["attributes"],"cn"); //获取姓名
      //$phoneNumber = SsoAvicAuth::getUserAttr($user["attributes"],"smart-securemobile");//获取手机号
      $nickName = $user["cn"]; //获取姓名
      $phoneNumber = $user["smart-securemobile"];//获取手机号
      $uid =$user["uid"];      

      $staff = new Staff($dbcon,$con_im,$fafa_account);
      $staffinfo=$staff->getInfo();
      if(empty($staffinfo))
      {
            //新用户：注册 激活
            $enInfo= $cacheobj->getInfo($eno);
            $active=new \Justsy\BaseBundle\Controller\ActiveController();
            $active->setContainer($container);
            $active->doSave(array(
                              'account'=> $fafa_account,
                              'realName'=> $nickName,
                              'passWord'=> $password,
                              'eno'=> $eno,
                              'ename'=> $enInfo["ename"],
                              'isNew'=>'0',
                              'mailtype'=> "1",
                              'deptid'=> "100054",
                              'isSendMessage'=>"N",
                              'import'=>'1'
            )); 
            $sql="update we_staff set ldap_uid=?,mobile=?,mobile_bind=? where login_account=?";
            $params=array((string)$uid,(string)$phoneNumber,(string)$phoneNumber,(string)$fafa_account);
          
            $dbcon->ExecSQL($sql,$params);
      }
      else
      {
          $ldap_uid = $uid;
          //更新信息
          if($nickName==$staffinfo["nick_name"])
          {
            $nickName = null;
          }
          if($phoneNumber==$staffinfo["mobile"])
          {
            $phoneNumber = null;
          }          
          if(!empty($nickName) || !empty($phoneNumber))
          {
            try
            {
              $staff->checkAndUpdate($nickName,$phoneNumber,null,null,$ldap_uid);
            }
            catch(\Exception $e)
            {
              $container->get("logger")->err($e->getMessage());
            }
          }
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
      $re["token"] = $usertoken; //用户凭据
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
      $re["msg"] = $e->getMessage();
      $re["returncode"] = ReturnCode::$SYSERROR;        
    }
    return $re;
  }

  public static function tokenValidate($container,$token)
  {
    $request = $container->get("request");
    $dbcon = $container->get("we_data_access");
    $con_im = $container->get("we_data_access_im");
    $rest = "/rest/validate";
    $defaultPostURl = "https://sso.avicmall.com:8443";
    $appcodeConfig = "fafa-app";
    $appkeyConfig = "DKGHwqJ5H91noPYNYm9b8EUPQSY";
    $cacheobj = new Enterprise($dbcon,$container->get("logger"),$container);//  
    $authConfig =   $cacheobj->getUserAuth();
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
      $appcodeConfig = $ldapConfgiObject["AppCode"];
      $appkeyConfig = $ldapConfgiObject["AppKey"];
    }
    $httpUrlConfig = $httpUrlConfig.$rest;
    
    try
    {
      $reqHeader = SsoAvicAuth::getHeaders($appcodeConfig,$appkeyConfig);
      $para =array( "ticketName"=>"SIAMTGT","ticketValue"=>$token);
      //$container->get("logger")->err("SOA URL:".$httpUrlConfig." Body:".json_encode($para));
      $postresult = Utils::do_post_request($httpUrlConfig,json_encode($para),$reqHeader,$container->get("logger"));
      
      //$container->get("logger")->err("SOA Result:".$postresult);
      $resultObject = json_decode($postresult,true);
      if(!isset($resultObject["status"]))
      {
        $re["returncode"] = ReturnCode::$ERROFUSERORPWD;
        $re["msg"] = "服务器异常";
        return $re;
      }
      if(!$resultObject["status"] || $resultObject["status"]=="false")
      {
        $re["returncode"] = ReturnCode::$ERROFUSERORPWD;
        $re["msg"] = $resultObject["message"];
        return $re;
      }
      $usertoken = $resultObject["ticketEntry"]["ticketValue"];
      $user = $resultObject["user"]; //用户信息
      
      $nickName = $user["cn"]; //获取姓名
      $phoneNumber = $user["smart-securemobile"];//获取手机号
      $login_account =$user["uid"];

      $eninfo = $cacheobj->getInfo($eno);
      $domain = $eninfo["edomain"];
      $domain =  strpos($domain,".")===false?"fafatime.com": $domain; 

      $fafa_account =strtolower($login_account."@".$domain);
      $staff = new Staff($dbcon,$con_im,$fafa_account);
      $staffinfo=$staff->getInfo();
      if(empty($staffinfo))
      {
            $password= rand(100000, 999999);
            //新用户：注册 激活
            $enInfo= $cacheobj->getInfo($eno);
            $active=new \Justsy\BaseBundle\Controller\ActiveController();
            $active->setContainer($container);
            $uid = strtolower($login_account);            
            $active->doSave(array(
                              'account'=> $fafa_account,
                              'realName'=> $nickName,
                              'passWord'=> $password,
                              'eno'=> $eno,
                              'ename'=> $enInfo["ename"],
                              'isNew'=>'0',
                              'mailtype'=> "1",
                              'deptid'=> "100054",
                              'isSendMessage'=>"N",
                              'import'=>'1'
            )); 
            $sql="update we_staff set ldap_uid=?,mobile=?,mobile_bind=? where login_account=?";
            $params=array((string)$uid,(string)$phoneNumber,(string)$phoneNumber,(string)$fafa_account);
          
            $dbcon->ExecSQL($sql,$params);
      }
      else
      {
          $ldap_uid = $login_account;
          //更新信息
          if($nickName==$staffinfo["nick_name"])
          {
            $nickName = null;
          }
          if($phoneNumber==$staffinfo["mobile"])
          {
            $phoneNumber = null;
          }          
          if(!empty($nickName) || !empty($phoneNumber))
          {
            try
            {
              $staff->checkAndUpdate($nickName,$phoneNumber,null,null,$ldap_uid);
            }
            catch(\Exception $e)
            {
              $container->get("logger")->err($e->getMessage());
            }
          }
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
      $re["token"] = $usertoken; //用户凭据
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
      $re["msg"] = $e->getMessage();
      $re["returncode"] = ReturnCode::$SYSERROR;        
    }
    return $re;
  } 

  public static function getUserAttr($attrs,$attrname)
  {
     for ($i=0; $i < count($attrs); $i++) { 
        if($attrs[$i]["name"]==$attrname)
        {
          return $attrs[$i]["value"];
        }
     }
     return "";
  }

  public static function getHeaders($appcode,$appkey)
  {
      $reqHeader = array();
      $timestamp = date("YmdHis")."Z";
      $flag="{".$appkey."}";
      $reqHeader[] = "Content-Type: application/json\r\n";
      $reqHeader[] = "appcode: ".$appcode."\r\n";
      $reqHeader[] = "appkey: ".$appkey."\r\n";
      $reqHeader[] = "timestamp: ".$timestamp."\r\n";
      $reqHeader[] = "encode: ".hash('sha256',$appcode.$appkey.$timestamp.$flag)."\r\n";

      return implode("", $reqHeader);
  }

  //
  //{"name":"wangqiang","attributes":[{"name":"uid","value":"wangqiang","values":null},{"name":" mail","value":"qiang.wang@company.com","values":null},{"name":"sn","value":"王 ","values":null},{"name":"smart-type","value":"0","values":null},{"name":"smartidcardnumber","value":"230109198907290987","values":null},{"name":"userpassword","value":"11 1111","values":null},{"name":"cn","value":"王强","values":null},{"name":"smartsecuremobile","value":"18600000000","values":null},{"name":"smartidcardtype","value":"1","values":null},{"name":"mobile","value":"18600000000","values":null} ]}
  public function createUser($container,$attributes)
  {
    $createUserRest=$container->getParameter('staff_sync_url');

    $defaultPostURl = "https://sso.avicmall.com:8443";
    $appcodeConfig = "fafa-app";
    $appkeyConfig = "DKGHwqJ5H91noPYNYm9b8EUPQSY";
    $cacheobj = new Enterprise(null,$container->get("logger"),$container);//  
    $authConfig =   $cacheobj->getUserAuth();
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
      $appcodeConfig = $ldapConfgiObject["AppCode"];
      $appkeyConfig = $ldapConfgiObject["AppKey"];
    }
    $reqHeader = SsoAvicAuth::getHeaders($appcodeConfig,$appkeyConfig);
    $data = array();
    $data["name"] = "";
    $data["attributes"] = array(
            array("name"=>"mobile","value"=>$attributes["mobile"]),
            array("name"=>"smart-securemobile","value"=>$attributes["mobile"]),
            array("name"=>"userpassword","value"=>$attributes["password"]),
            array("name"=>"smart-type","value"=>"2"),
            array("name"=>"cn","value"=> $attributes["nick_name"])
    );
    $para = json_encode($data);

    $container->get("logger")->err("SOA URL:".$createUserRest."?".$para);
    $postresult = Utils::do_post_request($createUserRest,$para,$reqHeader,$container->get("logger"));
    $container->get("logger")->err("SOA Result:".$postresult);
    $resultObject = json_decode($postresult,true);
    if(!$resultObject["status"] || $resultObject["status"]=="false")
    {
        throw new \Exception($resultObject["message"]);  
    }
    $resultObject["ldap_uid"] = $resultObject["key"];
    $resultObject["deptid"]   = "100054"; //默认部门
    return $resultObject;  
  }  
}