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

class SsoWefafaController extends Controller implements  ISso
{
	public static $ssoLogin= "http://xxxxxxxxxxxx/callback.php";
	public static function ssoAction($container,$con,$appid,$openid,$token,$encrypt)
	{
    	$da = $con;
    	//$result = Utils::do_post_request("http://www.wefafa.com", array());
		$sql = "select appkey from we_appcenter_apps where appid=?";
		$ds = $da->GetData("t",$sql,array((string)$appid));
		$result="";
		
		if(count($ds["t"]["rows"])==0)
		{
			$result="invalid appid";
		}
		else
		{
			$appkey = $ds["t"]["rows"][0]["appkey"];
			$sql = "select a.authkey,b.login_account from we_staff_account_bind a,we_staff b where a.bind_account=b.openid and a.bind_account=? and a.appid=?";
			$ds = $da->GetData("t",$sql,array((string)$openid,(string)$appid));
			if(count($ds["t"]["rows"])>0)
			{
				$row = $ds["t"]["rows"][0];
				//$authkey = $row["authkey"];
				//$authkey=DES::decrypt2($authkey,$appkey);
				//$parameter = "";
				//自动登录
				$Obj = new \Justsy\BaseBundle\Login\UserProvider($container->container);
      			$user = $Obj->loadUserByUsername($row["login_account"]);
				$token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken($user, $user->getPassword(), "secured_area", $user->getRoles());
    	  		$container->get("security.context")->setToken($token);    	  
        		$session = $container->get("request")->getSession()->set('_security_'.'secured_area',  serialize($token));        
    	  		$event = new \Symfony\Component\Security\Http\Event\InteractiveLoginEvent($container->get("request"), $token);
    	  		$container->get("event_dispatcher")->dispatch("security.interactive_login", $event);
				//$result = Utils::do_post_request("http://we.fafatime.com", array());
				$weburl = "http://we.fafatime.com";
				return Utils::http_redirect($weburl);
			}
			else
			{
				$result="not bind";
			}
		}
		
    	$resp = new Response($result);
	   	$resp->headers->set('Content-Type', 'text/html');
	   	return $resp;
	}
	

	public static function tokenAction($container,$con,$appid,$openid,$encrypt)
	{
		$da = $con;
    	//$result = Utils::do_post_request("http://www.wefafa.com", array());
		$sql = "select appkey from we_appcenter_apps where appid=?";
		$ds = $da->GetData("t",$sql,array((string)$appid));
		$result="";
		
		if(count($ds["t"]["rows"])==0)
		{
			$json=array("error"=>"invalid appid");
		}
		else
		{
			$appkey = $ds["t"]["rows"][0]["appkey"];
			$sql = "select 1 from we_staff_account_bind a,we_staff b where a.bind_account=b.openid and a.bind_account=? and a.appid=?";
			$ds = $da->GetData("tb",$sql,array((string)$openid,(string)$appid));
			if(count($ds["tb"]["rows"])>0)
			{
				$api = new \Justsy\OpenAPIBundle\Controller\ApiController();
				$api->setContainer($container->container);				
				$code = md5($appid.$appkey);
				$json=$api->getProxySession($appid,$code,"394usjf0sd");
			}
			else
			{
				$json=array("error"=>"not bind");
			}
		}
		return $json;
	}

	 private function responseJson($re){
        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }
}