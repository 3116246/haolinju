<?php

namespace Justsy\OpenAPIBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Login\UserSession;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Login\UserProvider;
use Justsy\BaseBundle\Management\Identify;
use Justsy\InterfaceBundle\SsoAuth\SsoUserAuthController;

class ThirdPartyController extends Controller
{
	
	 //第三方登录(微信登录)
	 public function WeiXinLoginAction()
	 {
	 	$deploy_mode = $this->container->getParameter('deploy_mode');
	 	$da = $this->get("we_data_access");  	 	  
	 	$request = $this->getRequest();
	 	$openid = $request->get("openid");
	 	$unionid = $request->get("unionid");
	 	$logintype = $request->get("logintype");
	 	$logintype = empty($logintype) ? "02":$logintype;      
      	$ldap_uid = null;
	 	$login_account = $unionid."@fafatime.com";
	 	$staffMgr = new \Justsy\BaseBundle\Management\Staff($da,$this->get('we_data_access_im'),$login_account,$this->get("logger"),$this->container);
    	$staffdata = $staffMgr->getInfo();
	 	$re = array("returncode" => ReturnCode::$SYSERROR,"msg"=>"");
	 	$password="";
	 	//账号为空表示不存在
	 	if ( empty($staffdata) )
	 	{
	 		$eno = $deploy_mode=="C" ? Utils::$PUBLIC_ENO: "";
	 		if(empty($eno))
	 		{
	 			$cacheobj = new \Justsy\BaseBundle\Management\Enterprise($da,$this->get("logger"),$this->container);   
      			//获取用户认证模块           
      			$authConfig = $cacheobj->getUserAuth();
      			if(!empty($authConfig))
			    {
			        $eno = $authConfig["ENO"];
			    }
			    if(empty($eno))
			    {
			    	$re["returncode"] = ReturnCode::$SYSERROR;
			    	$re["msg"] = "企业号不能为空。";
		    		return $re;
			    }
	 		}
	 	  	$ldap_uid = $unionid;
	 	  	$password=rand(1000000,999999);
        	$parameter = array( "appid"=>$request->get("appid"),
                            "eno" => $eno,
                            "openid"=> $openid,
                            "nick_name"=>$request->get("nickname"),
                            "sex" => $request->get("sex"),
                            "province"=>$request->get("province"),
                            "city"=>$request->get("city"),
                            "headimgurl"=>$request->get("headimgurl"),
                            "unionid"=>$unionid,
                            "account"=>"",
                            "password"=> $password,
                            "ldap_uid"=>$ldap_uid,
                            "type"=>"weixin");         
        	$staffdata = $staffMgr->createstaff($parameter); //注册用户账号
        	if ($staffdata["returncode"]== ReturnCode::$SUCCESS ){
          		$re =$this->autologin($login_account,$password,$logintype);
        	}
        	else
        	{
          		$re = $staffdata;
        	}
	 	}
	 	else{
	 		$password = DES::decrypt($staffdata["t_code"]);
	 	    $re =$this->autologin($login_account,$password,$logintype);
	 	}
	 	//$staffdata["des"] = DES::decrypt($staffdata["t_code"]);
	 	$response = new Response(json_encode($re));
      	$response->headers->set('Content-Type', 'text/json');
      	return $response;
	 }

	 //第三方登录(腾讯ＱＱ)
	 public function TencentLoginAction()
	 {
	 	  $da = $this->get("we_data_access");
	 	  $request = $this->getRequest();
	 	  $openid = $request->get("openid");  //普通用户的标识，对当前开发者帐号唯一	 	  
	 	  $logintype = $request->get("logintype");
	 	  $logintype = empty($logintype) ? "02":$logintype;	 	 
	 	  $ldap_uid = ""; 
	    
		  $login_account = $openid."@fafatime.com";
	 	  $staffMgr = new \Justsy\BaseBundle\Management\Staff($da,$this->get('we_data_access_im'),$login_account,$this->get("logger"),$this->container);
    	  $staffdata = $staffMgr->getInfo();
	 	  $re = array("returncode" => ReturnCode::$SYSERROR,"msg"=>"");
	 	  $password="";
	 	  //账号为空表示不存在
	 	  if ( empty($staffdata) ){
	 	  	 
	 	  	$sex = $request->get("gender");
	 	  	if ( trim($sex)=="男" )
	 	  	  $sex = 1;
	 	  	else if ( trim($sex)=="女")
	 	  	  $sex = 2;
	 	  	else
	 	  	  $sex = 0;
	      	$parameter = array("appid"=>$request->get("appid"),
                           "province"=>$request->get("province"),
                           "city"=>$request->get("city"),
                           "account"=>$login_account,
                           "nick_name"=>$request->get("nickname"),
                           "eno" =>$request->get("eno"),
                           "ldap_uid"=>$openid,
                           "openid"=> $openid,
                           "sex" => $sex,
                           "headimgurl"=>$request->get("figureurl_2"),
                           "type"=>"tencent" );   
	        $registerInfo = $staffMgr->createstaff($parameter);
	        if ($registerInfo["returncode"]== ReturnCode::$SUCCESS ){ 
	          $re =	$this->autologin($login_account,$logintype);
	        }
	        else
          		$re = $registerInfo;
	 	  }
	 	  else
	 	  {	
	 	    $re =	$this->autologin($login_account,$logintype);
	 	  }

	 	  $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
      	  $response->headers->set('Content-Type', 'text/json');
      	  return $response;
	 }
	 	 
	 //生成随机号
	 private function delimit_login_account($type)
	 {
	 	  return $type.rand(10000000,99999999);
	 }
	 
	 //根据openid获得账号
	 private function getAccountByOpenid($openid,$type)
	 {
	 	 if (empty($openid) || empty($type)) return null;
	 	 $da = $this->get("we_data_access");
	 	 $login_account = "";
	 	 $sql = "select login_account from we_staff_account_bind where bind_type=? and bind_uid=?";
	 	 $ds = $da->GetData("table",$sql,array((string)$type,(string)$openid));
	 	 if ( $ds && $ds["table"]["recordcount"]>0)
	 	   $login_account = $ds["table"]["rows"][0]["login_account"];
	 	 return $login_account;
	 }
	 	 
   //注册第三方账号信息
   public function Register($parameter){
   	  $eno  =  trim($parameter["eno"]);
   	  $conn = $this->get("we_data_access");
	    if(empty($eno)) {
	    	$this->get("logger")->err("企业编号不能为空。");
	      return array("returncode"=>ReturnCode::$SYSERROR,"msg"=>"企业编号不能为空。");
	    }
	    $login_account = $parameter["login_account"];
	    $nick_name = $parameter["nick_name"];
	    $returncode = ReturnCode::$SUCCESS;$msg = "";
      $user_openid = "";
      $employeeRegister = new \Justsy\BaseBundle\Controller\ActiveController();
      $employeeRegister->setContainer($this->container);
      $registerResult = $employeeRegister->ThirdRegister($eno,$login_account,$nick_name,$parameter["ldap_uid"]);
      if ($registerResult["success"]){
       	 $appid = $parameter["appid"];
       	 $user_openid = $registerResult["openid"];
      	 $sqls = array();
         $paras = array();         
      	 $login_account = $login_account."@fafatime.com";
         $sql = " insert into we_staff_account_bind(bind_account,bind_type,bind_uid,login_account,bind_created,nick_name,province,city,profile_image_url,appid)values(?,?,?,?,now(),?,?,?,?,?);";
         $para = array((string)$user_openid,(string)$parameter["type"],(string)$parameter["openid"],(string)$login_account,(string)$nick_name,
                 (string)$parameter["province"],(string)$parameter["city"],(string)$parameter["headimgurl"],(string)$appid );
         array_push($sqls,$sql);
         array_push($paras,$para);
         //更改用户性别字段
         $sex = $parameter["sex"];
         if (!empty($sex)){
         	 if ($sex=="1" || $sex=="2"){
         	   $sex = $sex=="1"?"男":"女";
         	   $sql = "update we_staff set sex_id=? where login_account=?";
         	   $para = array((string)$sex,(string)$login_account);
         	   array_push($sqls,$sql);
             array_push($paras,$para);
           }
         }
         try
         {
         	 $conn->ExecSQLs($sqls,$paras);
         	 //用户头像处理
         	 // $this->SaveUserHead($login_account,$parameter["headimgurl"]);
         	 $msg = "操作成功！";
         }
         catch (Exception $e) {
         	 $returncode = ReturnCode::$SYSERROR;
        	 $msg = $e->getMessage();
        	 $this->get("logger")->err($e->getMessage());
         }      	 
       }  
       else {
       	 $returncode = ReturnCode::$SYSERROR;
         $msg="帐号注册失败。";
       }
      return array("returncode"=>$returncode,"msg"=>$msg);
   }
   
   //自动登录
   public function autologin($login_account,$password,$comefrom) 
   { 
   		$authController = new SsoUserAuthController();
    	$authController->setContainer($this->container);
    	return $authController->dispatchAction($this,$login_account,$password,$comefrom,"all","");
   }
   
   //获得用户头像保存至Ｍongo
   private function SaveUserHead($login_account,$image_url){
   	  $logger = $this->get("logger");
      if (empty($image_url)) return;
      //取用户头像
      $filename = "";
      try
      {
		   	  $path = rtrim($_SERVER['DOCUMENT_ROOT'],'\/')."/upload";
		      if (!is_dir($path))
		        mkdir($path);
		      $path = $path."/weixin";
		      if (!is_dir($path))
		        mkdir($path);
		      $filename = explode("@",$login_account);
		      $filename = $filename[0];
		      $filename = $path."/".$filename.".png";
		      ob_start(); 
			    readfile($image_url);
			    $img=ob_get_contents();
			    ob_end_clean();	  
			    $size=strlen($img);	  
		      $fp2=@fopen($filename,'a');
		      fwrite($fp2,$img);
		      fclose($fp2);
      }
      catch(\Exception $e){
     	  $filename = "";     	  
	 	    $logger->err($e);
      }
      //将文件存入mongo
      $fileid = $this->saveFile($filename);
      if (!empty($fileid)){
      	$da = $this->get("we_data_access");
      	$sql = "update we_staff set photo_path=?,photo_path_small=?,photo_path_big=? where login_account=?";
      	$para = array((string)$fileid,(string)$fileid,(string)$fileid,(string)$login_account);
      	try
      	{
      		 $da->ExecSQL($sql,$para);      		 
      	}
      	catch(\Exception $e){
      		$logger->err($e);
      	}      	
      }
   }
   
   
   //将文件保存到mogo
	 private function saveFile($filename)
	 {
			$fileid = "";
			try
			{
				if (!empty($filename) && file_exists($filename)){ 
			     $newfile = sys_get_temp_dir()."/".basename($filename);
	         if (rename($filename,$newfile)){			    
				      //进行mongo操作
					    $dm = $this->get('doctrine.odm.mongodb.document_manager'); 
					    $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
					    $doc->setName(basename($newfile));
					    $doc->setFile($newfile);
					    $dm->persist($doc);
					    $dm->flush();
					    $fileid = $doc->getId();
					    //存入mongo后删除源文件
					    if (file_exists($filename))
					      unlink($filename);
			    }
		    }
		  }
		  catch(\Exception $e){
		  	$this->get("logger")->err($e);
		  	$fileid = "";
		  }
		  return $fileid;
	 } 

  //第三方账号注册
  public function ThirdpartyRegisterAction()
  {
  	 $da = $this->get('we_data_access');	     	 
     $request = $this->getRequest();
  	 $login_account=trim($request->get("login_account"));  //传入的注册账号
  	 $account_type = $request->get("account_type");
  	 $appid = $request->get("appid");
  	 $eno   = $request->get("eno");
  	 $code  = $request->get("code");
  	 $staffinfo = $request->get("staffinfo");
  	 //数据完整性判断
  	 if(empty($appid)) return array("returncode"=>"9999","msg"=>"应用ID不能为空。");
     if(empty($code)) return array("returncode"=>"9999","msg"=>"动态授权码不能为空。");
     if(empty($eno)) return array("returncode"=>"9999","msg"=>"企业编号不能为空。");
     if(empty($staffinfo)) return array("returncode"=>"9999","msg"=>"注册人员不能为空。");
     
     if($account_type !="createfulluser") 
     {
		   $sql="select appkey from we_appcenter_apps where appid=?";
		   $ds=$da->GetData("table",$sql,array((string)$appid));
		   if( ($ds && $ds["table"]["recordcount"]==0) || empty($ds["table"]["rows"][0]["appkey"])) {
		        $result = array("success"=>false,"msg"=>"应用ID不正确。");
		      	$response = new Response(json_encode($result));
	     		$response->headers->set('Content-Type', 'text/json');
	     		return $response; 
		   }
		   $appkey=$ds["table"]["rows"][0]["appkey"];
		   if(strtolower($code)!=strtolower(MD5($appid.$appkey))){
		        $result =  array("returncode"=>"9999","msg"=>"动态授权码不正确。");
		      	$response = new Response(json_encode($result));
	     		$response->headers->set('Content-Type', 'text/json');
	     		return $response;
		   }
	  }
	  $thirdRegister = new \Justsy\OpenAPIBundle\Controller\ApiController();
      $thirdRegister->setContainer($this->container);
       //返回结果
	   $result = array("returncode"=>"0000","msg"=>"");
	   $nick_name = "";
	   $stafflist=json_decode($staffinfo,true);
	   $openid = null;
  	 if ( $account_type=="email"){ //邮箱格式
  	 	  $parameter = array( "email"=>$login_account,
  	                        "reg_name"=>$stafflist[0]["nick_name"],
  	                        "password"=>$stafflist[0]["password"],
  	                        "uid"=>$stafflist[0]["uid"]);
  	    $para = array($parameter); 	    
  	 	  $result = $thirdRegister->mail_registerStaff($appid,$code,$eno,json_encode($para));
  	 }
  	 else if ( $account_type=="mobile") {
  	    $parameter = array( "mobile"=>$login_account,
  	                        "reg_name"=>$stafflist[0]["nick_name"],
  	                        "password"=>$stafflist[0]["password"],
  	                        "uid"=>$stafflist[0]["uid"]);
  	    $para = array($parameter);
        $result = $thirdRegister->registerStaff($appid,$code,$eno,json_encode($para),1);
        
  	 }
  	 else if($account_type=="createfulluser")
  	 {
        $active = new \Justsy\BaseBundle\Controller\ActiveController();
     	$active->setContainer($this->container);
     	$result = $active->doSave($stafflist);
     	if($result===true)
     		$result = array("returncode"=>"0000","msg"=>"");
     	else
     		$result = array("returncode"=>"9999","msg"=>"");
  	 }
  	 else if ( $account_type=="qq"){  	 	
  	 	  if ( isset($stafflist[0]["openid"]))
  	 	   $openid = $stafflist[0]["openid"];
  	 	  if ( !empty($openid)){
  	 	    $login_account = $this->getAccountByOpenid($openid,"tencent");
	  	 	  if ( empty($login_account)){
	  	 	  	$login_account = $this->delimit_login_account("tencent_");
	  	 	  	$nick_name = isset($stafflist[0]["nick_name"]) ? $stafflist[0]["nick_name"] : "";
	  	 	  	if ( empty($nick_name)) $nick_name = "QQ_".rand(100000,999999);
	  	 	  	$img_url = isset($stafflist[0]["headimgurl"]) ? $stafflist[0]["headimgurl"] : null;
		  	 		$parameter = array("appid"=>$appid,
		                           "login_account"=>$login_account,
		                           "province"=>"",
		                           "city"=>"",
		                           "nick_name"=>$nick_name,
		                           "eno" =>$eno,
		                           "ldap_uid"=>$stafflist[0]["uid"],
		                           "openid"=>$openid,
		                           "sex"=>"",
		                           "headimgurl"=> $img_url,
		                           "type"=>"tencent" );                 
		        $result = $this->Register($parameter);
		        $this->get("logger")->err($login_account);
	  	 	  }
	  	 	  else {
	  	 	  	$result = array("returncode"=>"99999","msg"=>"已存在该用户账号！");
	  	 	  }
        }
        else{
       	  $result = array("returncode"=>"9999","msg"=>"请传入openid参数值");
        }
  	 }
  	 else if ( $account_type=="micro"){
  	 	 if ( isset($stafflist[0]["openid"]))
  	 	   $openid = $stafflist[0]["openid"];
  	 	 if ( !empty($openid)){
	  	 	 $login_account = $this->getAccountByOpenid($openid,"weixin");
	  	 	 if ( empty($login_account)){
	  	 	 	 $login_account = $this->delimit_login_account("weixin_");
		  	 	 $nick_name = isset($stafflist[0]["nick_name"]) ? $stafflist[0]["nick_name"] : null;
		  	 	 if ( empty($nick_name)) $nick_name = "WX_".rand(100000,999999);
		  	 	 $img_url   = isset($stafflist[0]["headimgurl"]) ? $stafflist[0]["headimgurl"] : null;
		 	  	 $ldap_uid  = isset($stafflist[0]["unionid"]) ? $stafflist[0]["unionid"] : null;
		 	  	 $unionid =  isset( $stafflist[0]["unionid"]) ?  $stafflist[0]["unionid"] : null;
			  	 $parameter = array( "appid"=>$appid,
			  	 	                   "eno" => $eno,
			                         "openid"=> $openid,
			                         "login_account"=>$login_account,
			                         "nick_name"=>$nick_name,
			                         "headimgurl"=>$img_url,
			                         "unionid"=>$unionid,
			                         "ldap_uid"=>$ldap_uid,
			                         "sex"=>null,
			                         "province"=>null,
                               "city"=>null,
			                         "type"=>"weixin");            
			  	 	 $result = $this->Register($parameter);
		  	 }
		  	 else {
		  	 	 $result = array("returncode"=>"9999","msg"=>"已存在用户账号！");
		     }
		   }
		   else{
		  	 $result = array("returncode"=>"9999","msg"=>"请传入openid值！");
		   }
  	 }
  	 $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;  	 
  }	 	
  
  //第三方登录
  public function ThirdpartyLoginAction()
  {
  	 $request = $this->getRequest();
  	 $login_account = $request->get("account");  	 
  	 $password = $request->get("password");
  	 $result = array();
  	 $logintype = "ThirdLogin";
  	 if ( empty($login_account)){
  	 	 $result = array("success"=>false,"msg"=>"请输入登录账号！");
  	 }
  	 else if ( empty($password)) {
  	  $result = array("success"=>false,"msg"=>"请输入登录密码！");
  	 }
  	 else{
	  	 try 
	     {
	     	  if ( !strpos($login_account,"@")){
	     	  	$login_account .= "@mb.com";
	     	  }
		      $Obj = new \Justsy\BaseBundle\Login\UserProvider($this->container);
		      $user = $Obj->loadUserByUsername($login_account);
		      $factory = $this->get('security.encoder_factory');
		      $encoder = $factory->getEncoder($user);
		      $password = $encoder->encodePassword($password, $user->getSalt());
		  	  if($user->getPassword() != $password){
		  	    $result = array("success"=>false,"msg"=>"登录密码错误！");
		  	  }
		  	  else
		  	  {
		    	  $token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken($user, $user->getPassword(), "secured_area", $user->getRoles());
		    	  $this->get("security.context")->setToken($token);
		        $session = $request->getSession()->set('_security_'.'secured_area',serialize($token));        
		    	  $event = new \Symfony\Component\Security\Http\Event\InteractiveLoginEvent($this->get("request"), $token);
		    	  $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);	    	  
		    	  $request->getSession()->set('comefrom',$logintype);
		    	  $result = array("success"=>true,"msg"=>"登录成功！","openid"=>$user->openid);
		  	  }
	     }
	     catch (\Symfony\Component\Security\Core\Exception\UsernameNotFoundException $e) {
	       $result = array("success"=>false,"msg"=>"未登录！");
	     }
	     catch (\Exception $e) {
	       $result = array("success"=>false,"msg"=>"登录失败！");
	     }
     }
     $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;
  }
}