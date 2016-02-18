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

class SsoK3Controller  extends Controller  implements ISso
{

	public static $bind_type = "K3";
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
  	 	      'ssomodule'=>"K3Controller"));
	    }
		else
		{
			$loginUrl = $appdata["authorization_url"];
			$controller->get("logger")->err("================loginUrl:".$loginUrl);
			//用户身份认证
			$cookie_key= "k3_".$openid;
			$authResult = Utils::do_get_request_cookie($loginUrl,
	            	"provider=credentials&UserName=".$bindinfo["bind_uid"]."&Password=".$bindinfo["authkey"]."&PasswordIsEncrypted=false&RememberMe=false",
	            	null,
	            	$cookie_key);
                
            //$container->get("logger")->err("k3 login result:".$authResult);
            //认证失败时要求重新绑定
            $authResult = json_decode($authResult,true);
            if(!isset($authResult["Result"]) || !$authResult["Result"]["ResponseStatus"]["IsSuccess"])
            {
		    	return $controller->render("JustsyBaseBundle:AppCenter:h5bundle.html.twig",
	  	 	      array('appid'=> $appid,
	  	 	      'openid'=>$openid,
	  	 	      'ssomodule'=>"K3Controller"));
            }
		}
		$result =array("returncode"=>"0000","data"=>$authResult);
		return self::responseJson(json_encode($result));
	}

	public static function tokenAction($controller,$con,$appid,$openid,$encrypt)
	{
			$da = $con;
    	//$result = Utils::do_post_request("http://www.wefafa.com", array());
			$sql = "select appkey from we_appcenter_apps where appid=?";
			$ds = $da->GetData("t",$sql,array((string)$appid));
			$result="";
			$json=array("error"=>"bad error");
			try{
				if(count($ds["t"]["rows"])==0)
			{
				$json=array("error"=>"invalid appid");
			}
			else
			{
				$appkey = $ds["t"]["rows"][0]["appkey"];
				$sql = "select authkey,bind_uid from we_staff_account_bind a,we_staff b where a.bind_account=b.openid and a.bind_account=? and a.bind_type=?";
				$ds = $da->GetData("tb",$sql,array((string)$openid,self::$bind_type));
				if(count($ds["tb"]["rows"])>0)
				{
					//$api = new \Justsy\OpenAPIBundle\Controller\ApiController();
					//$api->setContainer($controller->container);					
					$code = md5($appid.$appkey);
					
					//解析autokey
					$bind_uid=$ds['tb']['rows'][0]["bind_uid"];
					if($encrypt=='1')
						$bind_uid=DES::decrypt2($bind_uid,$appkey);
					
					//获取携程令牌
		    		$EmployeeNO=$bind_uid;
					
					$paraXml='<SSOAuthRequest>'.
									 '<Language>Chinese</Language>'.
									 '<SSOAuth>'.
									 '<AccessUK>'.self::$AccessUK.'</AccessUK>'.
									 '<AccessPK>'.self::$AccessPK.'</AccessPK>'.
									 '<EmployeeNO>'.$EmployeeNO.'</EmployeeNO>'.
									 '</SSOAuth>'.
									 '</SSOAuthRequest>';
					
		    	$soap=new SoapClient(self::$get_token_url."?WSDL");
		    	$para=array("requestXMLString"=>array(
		    			"SSOAuthRequest"=>array(
			    			"Language"=>"Chinese",
				    		"SSOAuth"=>array(
				    			"AccessUK"=>self::$AccessUK,
				    			"AccessPK"=>self::$AccessPK,
				    			"EmployeeNO"=>$EmployeeNO,
				    		)
			    		)
		    		)
		    	);
		    	$para=array("requestXMLString"=>$paraXml);
		    	error_reporting(E_ERROR|E_WARNING|E_PARSE);
		    	$result=$soap->SSOAuthenticaionWithXML($para);
		    	error_reporting(E_ERROR|E_WARNING|E_PARSE|E_NOTICE);
		    	//$controller->get("logger")->err($result);
		    	$accesstoken='';
		    	//解析result
		    	if(isset($result->SSOAuthenticaionWithXMLResult)){
		    		$str=$result->SSOAuthenticaionWithXMLResult;
		    		$arr1=explode('&',$str);
		    		for($i=0;$i<count($arr1);$i++){
		    			$arr2=explode('=',$arr1[$i]);
		    			if($arr2[0]=='AccessToken'){
		    				$accesstoken=$arr2[1];
		    				break;
		    			}
		    		}
		    		if(empty($accesstoken)){
		    			$json=array("error"=>"您的账号激活周期为24小时，如有疑问请拨打：010-67876363-2， 如需出行服务请拨打：400-920-0670或400-820-6699。");
		    		}
		    		else
		    			$json=array('token'=>$accesstoken);
		    	}
		    	else{
		    		$json=array("error"=>"您的账号激活周期为24小时，如有疑问请拨打：010-67876363-2， 如需出行服务请拨打：400-920-0670或400-820-6699。");
		    	}
				}
				else
				{
					$json=array("error"=>"您的账号激活周期为24小时，如有疑问请拨打：010-67876363-2， 如需出行服务请拨打：400-920-0670或400-820-6699。");
				}
			}
			}
			catch(\Exception $e){
				$json['error']=$e->getMessage();
			}
			return $json;	
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
	        	$cookie_key= $authtype."_".$user->openid;
	            $loginUrl= $appdata["authorization_url"];
	            if(empty($loginUrl))
	            	throw new \Exception("认证接口未配置！");
	            $bindinfo = $app->getappbind(array(
	            	"appid"=>$parameters["appid"],
	            	"openid"=>$user->openid
	            ));
	            if(empty($bindinfo))
	            	throw new \Exception("帐号未绑定K3！");
	            //$container->get("logger")->err("k3 login url:".$loginUrl);
	            //获取绑定的k3帐号和密码进行k3登录认证
	            $authResult = Utils::do_get_request_cookie($loginUrl,
	            	"provider=credentials&UserName=".$bindinfo["bind_uid"]."&Password=".$bindinfo["authkey"]."&PasswordIsEncrypted=false&RememberMe=false",
	            	null,
	            	$cookie_key);
                
                //$container->get("logger")->err("k3 login result:".$authResult);
                $authResult = json_decode($authResult,true);
                if(!isset($authResult["Result"]))
                	throw new \Exception("用户认证失败！");
                if(!$authResult["Result"]["ResponseStatus"]["IsSuccess"])
                	throw new \Exception($authResult["Result"]["ResponseStatus"]["Message"]);
                //$container->get("logger")->err("k3 api url:".$url);
                $http_data = Utils::do_post_request_cookie($url,$str_para,null,$cookie_key,$method);
                //$container->get("logger")->err("k3 api result:".$http_data);
                return $http_data;		
	}

	private function responseJson($re){
		$re = "<script>if(navigator.userAgent.indexOf('Android')!=-1) {window.wefafa.onAuthResult('".$re."');} else {onAuthResult('".$re."');}</script>";
        $response = new Response($re);
        $response->headers->set('Content-Type', 'text/html');
        return $response;
    }	
}