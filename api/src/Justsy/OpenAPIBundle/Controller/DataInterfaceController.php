<?php
namespace Justsy\OpenAPIBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Management\Staff;
use Justsy\BaseBundle\Common\Cache_Enterprise;
use Justsy\BaseBundle\Common\Utils;
/*
	* @author liling@fafatime.com
	* @copyright Copyright(c) 2012-2015 www.wefafa.com
	* @param string dsid datasource ID
	* 统一数据访问总控制器
	* 移动端所有数据访问全部请求该方法
	*/
class DataInterfaceController extends Controller
{

	public function dataAccessAction()
	{
		  //访问权限校验
		  $api = new \Justsy\OpenAPIBundle\Controller\ApiController();
    	$api->setContainer($this->container);
    	$isWeFaFaDomain = $api->checkWWWDomain();
    	$request = $this->getRequest();
    	$da = $this->get('we_data_access');
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $api->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   	$re = array("returncode"=>"9999");
			    $re["code"]="err0105";
    	   	   	$re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
 				return $this->responseJson($request,$re);
    	   }
    	}
    	$dbAccessInf = new \Justsy\InterfaceBundle\Controller\DataInterfaceController();
    	$dbAccessInf->setContainer($this->container);
	    $response= $dbAccessInf->dataAccessAction();	    
	  	return $response;
	}

	public function getDataAccessAction()
	{
		$request = $this->getRequest();
		//判断是否是访问api控制器,是则直接跳转过去
		$module = trim($request->get("module"));
		if(!empty($module) && strpos($module,"Api")!==false)
		{
			$action = trim($request->get("action"));
			$apiclass = "\Justsy\OpenAPIBundle\Controller\\".$module."Controller";
			$apiclass = new $apiclass;
			$apiclass->setContainer($this->container);
			return call_user_func_array(array($apiclass,$action."Action"),array());
		}
	  	
		//访问权限校验
		$api = new \Justsy\OpenAPIBundle\Controller\ApiController();
    	$api->setContainer($this->container);
    	$isWeFaFaDomain = $api->checkWWWDomain();
    	
    	$da = $this->get('we_data_access');
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $api->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   	$re = array("returncode"=>"9999");
			    $re["code"]="err0105";
    	   	   	$re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
 				return $this->responseJson($request,$re);
    	   }
    	}
    	$dbAccessInf = new \Justsy\InterfaceBundle\Controller\DataInterfaceController();
    	$dbAccessInf->setContainer($this->container);
	    $response= $dbAccessInf->getDataAccessAction();	    
	  	return $response;
	}
    //单点登录腾讯企业邮箱
    public function tencentexmailloginAction()
    {
        $request = $this->getRequest();
        $param = $request->get("params");
        if(empty($param)) 
            $param=array();
        else if(is_string($param))
            $param = json_decode($param,true);
        if(!isset($param["appid"]))
        {
            $param["appid"] = $request->get("appid");
        }
        $openid = $request->get("openid");
        $staffObj = new \Justsy\BaseBundle\Management\Staff(
                    $this->get('we_data_access'),
                    $this->get('we_data_access_im'),
                    $openid,
                    $this->get("logger"));
        $user = $staffObj->getSessionUser(); 

        $appid = $param["appid"];
        //$openid = $user->openid;
        //$ldap_uid = $user->ldap_uid;
        //判断是否绑定
        $app = new \Justsy\BaseBundle\Management\App($this->container);
        $appdata = $app->getappinfo(array("appid"=>$appid));
        if(empty($appdata))
        {
            $resp = new Response("无效的APPID");
            $resp->headers->set('Content-Type', 'text/html');
            return $resp;
        }
        $agent = $appdata["clientid"];        
        //判断是否绑定
        $bindinfo = $app->getappbind(array(
                    "appid"=>$appid,
                    "openid"=>$openid
                ));
        if(empty($bindinfo))
        {
            //$controller->get("logger")->err("================not bind");
            //重定向到绑定页面
            return $this->render("JustsyBaseBundle:AppCenter:h5bundle.html.twig",
              array('appid'=> $appid,
              'openid'=>$openid,
              'ssomodule'=>"OAuth2"));
        }
        $ldap_uid = $bindinfo["bind_uid"];

        $cacheKey = md5($appid.$openid);
        $data=Cache_Enterprise::get(Cache_Enterprise::$EN_OAUTH2,$cacheKey,$this->container);
        if(empty($data))
        {
            $this->get("logger")->err("{$appid}.{$openid}");
            $resp = new Response("太长时间未操作，请重新进入应用");
            $resp->headers->set('Content-Type', 'text/html');
            return $resp;            
        }
        $data = json_decode($data,true);
        $acctoken = $data["access_token"];
        //$this->get("logger")->err($acctoken);
        //获取authkey
        $url = "http://openapi.exmail.qq.com:12211/openapi/mail/authkey";
        $authkey = Utils::do_post_request($url,"alias=".$ldap_uid."&access_token=".$acctoken);
        //$this->get("logger")->err($url."?"."alias=".$ldap_uid."&access_token=".$acctoken);
        //$this->get("logger")->err($authkey);
        if(empty($authkey))
        {
            $resp = new Response("腾讯企业邮箱登录失败");
            $resp->headers->set('Content-Type', 'text/html');
            return $resp;           
        }
        $authkey = json_decode($authkey,true);
        if(!isset($authkey["auth_key"]))
        {
            if($authkey["error"]=="invalid_token")
            {
                Cache_Enterprise::delete(Cache_Enterprise::$EN_OAUTH2,$cacheKey,$this->container);
                $resp = new Response("腾讯企业邮箱登录失败:<br>token无效或已经过期，请稍后重试！");
            }
            else
                $resp = new Response("腾讯企业邮箱登录失败:<br>".json_encode($authkey));
            $resp->headers->set('Content-Type', 'text/html');
            return $resp;           
        }
        $authkey = $authkey["auth_key"];
        $login_url = "https://exmail.qq.com/cgi-bin/login?fun=bizopenssologin&method=bizauth&agent=".$agent."&user=".$ldap_uid."&ticket=".$authkey;
        //$this->get("logger")->err($login_url);
        return Utils::http_redirect($login_url);
    }

    //二维码统一访问接口
    public function qrcodeAction($type,$code)
    {
        $syspara = new \Justsy\BaseBundle\DataAccess\SysParam($this->container);
        $downappUrl = $syspara->GetSysParam('app_download_page');
        if(empty($downappUrl))
        {
            return $this->responseJson("系统配置误:app_download_page");
        }
        return Utils::http_redirect($downappUrl);
        /*$request = $this->getRequest();
        $openid = $request->get("openid");
        $qrcodeMgr = new \Justsy\BaseBundle\Management\QrcodeMgr($this->container);
        return $qrcodeMgr->getData($openid,$type,$code);*/
    }

	private function responseJson($request,$re)
	{
		$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		$response->headers->set('Content-Type', 'text/json');
		return $response;
	}	
}
?>