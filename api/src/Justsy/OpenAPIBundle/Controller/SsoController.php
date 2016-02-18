<?php

namespace Justsy\OpenAPIBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Login\UserProvider;
use Justsy\BaseBundle\Login\UserSession;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Management\Staff;
use Justsy\InterfaceBundle\SsoAuth\SsoModules;

class SsoController extends Controller
{
	public function tokenAction()
	{
		$request = $this->get("request");
		$classname=$request->get("ssomodule");
		$appid=$request->get("appid");
    	$openid=$request->get("openid");
    	$encrypt=$request->get("encrypt");
    	$app =new \Justsy\BaseBundle\Management\App($this->container);
    	$appinfo = $app->getappinfo(array("appid"=>$appid));
    	if(empty($appinfo))
    	{
    		$resp = new Response("invalid appid");
	   		$resp->headers->set('Content-Type', 'text/html');
	   		return $resp;
    	}
    	$classname = ucfirst($appinfo["authtype"])."Controller";

		if(empty($classname) || $classname=="null"){
			//$classname = "SsoWefafaController";
			$re=array("error"=>"invalid ssomodule");
			$resp = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
	   		$resp->headers->set('Content-Type', 'text/json');
	   		return $resp;
		}
		$this->get("logger")->err("sso classname:".$classname);
		$classname = "\Justsy\OpenAPIBundle\Controller\Sso".$classname;
		try{
			$re = call_user_func(array($classname, 'tokenAction'),$this,$this->get("we_data_access"),$appid,$openid,$encrypt);
			if($re['error']=='not bind'){
				$title=call_user_func(array($classname, 'bindTitleAction'),$this,$this->get("we_data_access"),$appid,$openid,$encrypt);
				$re['title']=$title;
			} 
			$this->get("logger")->err(json_encode($re));
			$resp = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		    $resp->headers->set('Content-Type', 'text/json');
	   		return $resp;
		}
		catch(\Exception $e)
		{
			$re=array("error"=>"invalid ssomodule");
			$resp = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
	   		$resp->headers->set('Content-Type', 'text/json');
	   		return $resp;
		}
	}
	public function directAction()
	{
		$request = $this->get("request");
		$classname=$request->get("ssomodule");
		$appid=$request->get("state");
		$openid=$request->get("openid");
		if(empty($appid))
		{
			$appid = $request->get("appid");
		}
		else
		{
			$stat_v = explode(",", $appid);
			$appid = $stat_v[0];
			$openid = $stat_v[1];
		}    	
    	$encrypt=$request->get("encrypt");
    	$isLogin = null;
    	$app =new \Justsy\BaseBundle\Management\App($this->container);
    	if(strpos($appid,"SSO_")!==false)
    	{
    		$isLogin = $appid;
    		//新浪微博集成登录
    		//获取微博对应的业务系统认证配置
    		$syspara = new \Justsy\BaseBundle\DataAccess\SysParam($this->container);
    		$appid = $syspara->GetSysParam(strtolower($appid)."_appid");
    		if(empty($appid))
    		{
    			$resp = new Response("未配置集成登录业务系统或参数".strtolower($isLogin)."_appid");
	   			$resp->headers->set('Content-Type', 'text/html');
	   			return $resp;
    		}
    		$appinfo = $app->getbussysteminfo(array("appid"=>$appid));
    		$appid = $isLogin;//把appid还原成sso集成登录标识
    	}    	
    	else
    	{
    		if(strpos($appid,"SYS_")!==false)
    		{
    			$appid = substr($appid, 4);
    			//业务系统直接对接
    			$appinfo = $app->getbussysteminfo(array("appid"=>$appid));
    		}
    		else
    		{
    			$appinfo = $app->getappinfo(array("appid"=>$appid));
    		}
    	}
    	if(empty($appinfo))
    	{
    		$resp = new Response("invalid appid");
	   		$resp->headers->set('Content-Type', 'text/html');
	   		return $resp;
    	}
    	$classname = ucfirst($appinfo["authtype"])."Controller";

    	if(empty($classname) || $classname=="null"){
			//$classname = "SsoWefafaController";
			$re=array("error"=>"invalid ssomodule");
			$resp = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
	   		$resp->headers->set('Content-Type', 'text/json');
	   		return $resp;
		}
		$this->get("logger")->err("sso classname:".$classname);
		
		try{
			if($classname=="OAuth2Controller")
			{
				$classname = "\Justsy\OpenAPIBundle\Controller\Sso".$classname;
				//OAuth2认证模式时，授权请求返回为一个页面，单独处理
				return call_user_func(array($classname, 'directUrlAction'),$this);
			}
			else
			{
				$classname = "\Justsy\OpenAPIBundle\Controller\Sso".$classname;
				$params=array();
				$direct_url = call_user_func(array($classname, 'directUrlAction'),$this);
				$this->redirectUrl($direct_url,$params);
			}
		}
		catch(\Exception $e){
			$re=array("error"=>"invalid ssomodule");
			$resp = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
	   		$resp->headers->set('Content-Type', 'text/json');
	   		return $resp;
		}
	}
	//单点登录分发。
	//每个具体的单点登录实现必须实现接口ISso
	public function ssoAction()
	{
		$request = $this->get("request");
		$classname=$request->get("ssomodule");
		$appid=$request->get("appid");
    	$openid=$request->get("openid");
    	$token=$request->get("token");
    	$encrypt=$request->get("encrypt");
    	$isLogin = null;
    	$app =new \Justsy\BaseBundle\Management\App($this->container);
    	if(strpos($appid,"SSO_")!==false)
    	{
    		$isLogin = $appid;
    		//新浪微博集成登录
    		//获取微博对应的业务系统认证配置
    		$syspara = new \Justsy\BaseBundle\DataAccess\SysParam($this->container);
    		$appid = $syspara->GetSysParam(strtolower($appid)."_appid");
    		if(empty($appid))
    		{
    			$resp = new Response("未配置集成登录业务系统或参数".strtolower($isLogin)."_appid");
	   			$resp->headers->set('Content-Type', 'text/html');
	   			return $resp;
    		}
    		$appinfo = $app->getbussysteminfo(array("appid"=>$appid));
    		$appid = $isLogin;//把appid还原成sso集成登录标识
    	}
    	else
    	{
    		if(strpos($appid,"SYS_")!==false)
    		{
    			//业务系统直接对接
    			$appinfo = $app->getbussysteminfo(array("appid"=>substr($appid, 4)));
    		}
    		else
    		{
    			$appinfo = $app->getappinfo(array("appid"=>$appid));
    		}
    	}
    	if(empty($appinfo))
    	{
    		$resp = new Response("invalid appid：$appid");
	   		$resp->headers->set('Content-Type', 'text/html');
	   		return $resp;
    	}
    	$classname = ucfirst($appinfo["authtype"])."Controller";
		if(empty($classname) || $classname=="null"){
			//$classname = "SsoWefafaController";
			//$resp = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($row).");" : json_encode($row));
			$resp = new Response("invalid ssomodule");
	   		$resp->headers->set('Content-Type', 'text/html');
	   		return $resp;
		}
		try{			
			$classname = "\Justsy\OpenAPIBundle\Controller\Sso".$classname;
			return call_user_func(array($classname, 'ssoAction'),$this,$this->get("we_data_access"),$appid,$openid,$token,$encrypt);
		}
		catch(\Exception $e)
		{
			$this->get("logger")->err($e);
			$resp = new Response("invalid ssomodule");
	   		$resp->headers->set('Content-Type', 'text/html');
	   		return $resp;
		}
	}
	public function bindBatAction()
	{
		$request = $this->get("request");
		$classname=$request->get("ssomodule");
		$appid=$request->get("appid");
    	$openid=$request->get("openid");
    	$token=$request->get("token");
    	$encrypt=$request->get("encrypt");
		if(empty($classname) || $classname=="null"){
			//$classname = "SsoWefafaController";
			//$resp = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($row).");" : json_encode($row));
			$resp = new Response("invalid ssomodule");
	   		$resp->headers->set('Content-Type', 'text/html');
	   		return $resp;
		}
		$classname = "\Justsy\OpenAPIBundle\Controller\Sso".$classname;
		try{
			$re=call_user_func(array($classname, 'bindBatAction'),$this,$this->get("we_data_access"),$appid,$request->get('eno'),$encrypt,$request);
			$resp = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
	   		$resp->headers->set('Content-Type', 'text/json');
	   		return $resp;
		}
		catch(\Exception $e)
		{
			$resp = new Response("invalid ssomodule");
	   		$resp->headers->set('Content-Type', 'text/html');
	   		return $resp;
		}
	}
	//手工邦定
	public function bindAction($openid,$appid)
	{
		$da = $this->get("we_data_access");
		$request = $this->get("request");
		//$authcode = $request->get("auth");
		/*
			$bind_type= $request->get("bind_type");
			$modules=SsoModules::$modules;
			$isbindtype=false;
			$classname='';
			for($i=0;$i<count($modules);$i++){
				if($modules[$i]['bind_type']==$bind_type){
					$isbindtype=true;
					$classname=$modules[$i]['module_code'];
				}
			}
			if(!$isbindtype){
				return $this->responseJson(array("returncode"=>"9999","msg"=>'bind_type无效'),$request->get('jsoncallback'));
			}*/
		$app =new \Justsy\BaseBundle\Management\App($this->container);
    	$appinfo = $app->getappinfo(array("appid"=>$appid));
    	if(empty($appinfo))
    	{
    		$resp = new Response("invalid appid");
	   		$resp->headers->set('Content-Type', 'text/html');
	   		return $resp;
    	}
    	$classname = ucfirst($appinfo["authtype"])."Controller";
	    $encrypt=$request->get("encrypt");
			if(empty($classname) || $classname=="null"){
				//$classname = "SsoWefafaController";
				//$resp = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($row).");" : json_encode($row));
				$resp = new Response("invalid ssomodule");
		   		$resp->headers->set('Content-Type', 'text/html');
		   		return $resp;
			}
			$classname = "\Justsy\OpenAPIBundle\Controller\Sso".$classname;
			try{
				return call_user_func(array($classname, 'bindAction'),$this,$this->get("we_data_access"),$appid,$openid,$request);
				//$resp = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		   		//$resp->headers->set('Content-Type', 'text/json');
		   		//return $resp;
			}
			catch(\Exception $e)
			{
				$resp = new Response("invalid ssomodule");
		   		$resp->headers->set('Content-Type', 'text/html');
		   		return $resp;
			}
		
		//$bx_data=$request->get("data")
	}

	public function unbindAction($openid,$appid)
	{
		$da = $this->get("we_data_access");
		$request = $this->get("request");
		$bind_type=$request->get("bind_type");
		$openids=$request->get("openids");
		$re = array("returncode"=>"0000");
		//$bx_data=$request->get("data");
		try
		{
			$openidArr=array();
			if(empty($openids)){
				$openidArr[]=$openid;
			}
			else{
				$openidArr=explode(',',$openids);
			}
			if($bind_type==''){
				return $this->responseJson(array("returncode"=>"9999","msg"=>'bind_type无效'),$request->get('jsoncallback'));
			}
			$sqls=array();
			$paras=array();
			for($i=0;$i< count($openidArr);$i++){
				$sqls[]= "delete from we_staff_account_bind where bind_account=? and bind_type=? and appid=?";
				$paras[]=array($openidArr[$i],(string)$bind_type,$appid);
			}
			$da->ExecSQLs($sqls,$paras);
		}
		catch(\Exception $e)
		{
			$re = array("returncode"=>"9999","msg"=>$e->getMessage());
		}
		return $this->responseJson($re,$request->get('jsoncallback'));
	}

	public function getauthAction($openid,$appid)
	{
		$da = $this->get("we_data_access");
		$request = $this->get("request");
		$re = array("returncode"=>"0000");
		//$bx_data=$request->get("data");
		try
		{
			$bind_type=$request->get('bind_type');
			if($bind_type==''){
				return $this->responseJson(array("returncode"=>"9999","msg"=>'bind_type无效'),$request->get('jsoncallback'));
			}
			$sql = "select appkey from we_appcenter_apps where appid=?";
			$ds = $da->GetData("t",$sql,array((string)$appid));
			if(count($ds["t"]["rows"])==0)
			{
				$re = array("returncode"=>"9999","msg"=>"appid is not found");
			}
			else
			{
				$appkey = $ds["t"]["rows"][0]["appkey"];
				$isdecrypt = $request->get("decrypt");
				$sql = "select authkey,bind_uid from we_staff_account_bind where bind_account=? and bind_type=? and appid=?";
				$ds = $da->GetData("t",$sql,array((string)$openid,(string)$bind_type,$appid));
				if($ds['t']['recordcount']==0){
					 $re = array("returncode"=>"0000","msg"=>"未获取到绑定信息");
					 return $this->responseJson($re);
				}
				$authkey =$ds["t"]["rows"][0]["authkey"];
				$authkey=DES::decrypt2($authkey,$appkey);
				if($isdecrypt=="1")
				{
					//$authkey=DES::decrypt2($authkey,$appkey);
				}
				$re["code"] = $authkey;
			}
		}
		catch(\Exception $e)
		{
			$re = array("returncode"=>"9999","msg"=>$e->getMessage());
		}
		return $this->responseJson($re);		
	}

	private function getToken($appid,$appkey)
	{
		$code = $this->makeCode($appid,$appkey);
		$api = new ApiController();
		$json=$api->getProxySession($appid,$code,"test-fafa-app");
		return $json["access_token"];
	}
	private function makeCode($appid,$appkey)
	{
		return strtolower(md5($appid.$appkey));
	}


	private function getLink($uniqid) {
		$web_url=$this->container->getParameter('open_api_url');
		return $web_url.'/api/http/getpagepath/'.$uniqid;
	}

	private function responseJson($data,$jsopfunc=null)
	{
		$resp = new Response( empty($jsopfunc) ? json_encode($data): $jsopfunc."(".json_encode($data).")");
	    $resp->headers->set('Content-Type', 'text/json');
	   	return $resp;
	}
	private function redirectUrl($url,$params)
  {
  	$par="";
  	while(list($key,$value)=each($params))
  	{
  		$par.=$key."=".$value."&";
  	}
  	$par=trim($par,'&');
  	if(strpos($url,"?")!==false)
  		$url.="&".$par;
  	else
  	  $url.="?".$par;
  	header("location:".$url);
  	die();
  }
}