<?php
namespace Justsy\OpenAPIBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Login\UserProvider;
use Justsy\BaseBundle\Login\UserSession;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Management\Staff;

class WeiboController extends Controller
{
	//获取微博帐号
	public function getWeiboAccountsAction(){
		$request = $this->get("request");
		$da = $this->get("we_data_access");
		$re = array("returncode"=>"9999");
  	if($_SERVER['REQUEST_METHOD']!="POST")
  	{
	   	$re["code"]="err0105";
	   	$re["msg"]="只支持使用POST方式提交数据.";
			$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
			$response->headers->set('Content-Type', 'text/json');
			return $response;
  	}
  	//判断请求域。是wefafa或子域则不验证授权令牌
  	$isWeFaFaDomain = $this->checkWWWDomain();
  	if(!$isWeFaFaDomain)
  	{
	   	$token = $this->checkAccessToken($request,$da);	
	   	if(!$token)
	   	{
	   	  $re["code"]="err0105";
	   	  $re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
	    	$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
				$response->headers->set('Content-Type', 'text/json');
				return $response;
	   	}
  	}
   	//作自动登录
			$ds=$this->checkOpenid($da ,$request->get("openid"));
			if($ds!=false){
				$this->autoLogin($request,$ds['login_account']);
			}
			$weibomgr=new \Justsy\BaseBundle\weibo\WeiboMgr($da,$this->get('logger'));
			$user = $this->get('security.context')->getToken()->getUser();
			$rows=$weibomgr->getAccounts($user->eno);
			$re=array('returncode'=>'0000','accounts'=> $rows);
			$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
			$response->headers->set('Content-Type', 'text/json');
			return $response;
	}
	//发布新浪微博
	public function publishSinaWeiboAction(){
		$request = $this->get("request");
		$da = $this->get("we_data_access");
		$re = array("returncode"=>"9999");
  	if($_SERVER['REQUEST_METHOD']!="POST")
  	{
	   	$re["code"]="err0105";
	   	$re["msg"]="只支持使用POST方式提交数据.";
			$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
			$response->headers->set('Content-Type', 'text/json');
			return $response;
  	}
  	//判断请求域。是wefafa或子域则不验证授权令牌
  	$isWeFaFaDomain = $this->checkWWWDomain();
  	if(!$isWeFaFaDomain)
  	{
	   	$token = $this->checkAccessToken($request,$da);	
	   	if(!$token)
	   	{
	   	  $re["code"]="err0105";
	   	  $re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
	    	$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
				$response->headers->set('Content-Type', 'text/json');
				return $response;
	   	}
  	}
    //作自动登录
		$ds=$this->checkOpenid($da ,$request->get("openid"));
		if($ds!=false){
			$this->autoLogin($request,$ds['login_account']);
		}
		
		$content=$request->get("content");
		$uid=$request->get('uid');
		$sinaController=new \Justsy\BaseBundle\Controller\SinaWeiboController();
		$sinaController->setContainer($this->container);
		$re=$sinaController->publishWeibo($uid,$content);
		
		$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		$response->headers->set('Content-Type', 'text/json');
		return $response;
	}
	//发布腾讯微博
	public function publishTencentWeiboAction(){
		$request = $this->get("request");
		$da = $this->get("we_data_access");
		$re = array("returncode"=>"9999");
  	if($_SERVER['REQUEST_METHOD']!="POST")
  	{
	   	$re["code"]="err0105";
	   	$re["msg"]="只支持使用POST方式提交数据.";
			$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
			$response->headers->set('Content-Type', 'text/json');
			return $response;
  	}
  	//判断请求域。是wefafa或子域则不验证授权令牌
  	$isWeFaFaDomain = $this->checkWWWDomain();
  	if(!$isWeFaFaDomain)
  	{
	   	$token = $this->checkAccessToken($request,$da);	
	   	if(!$token)
	   	{
	   	  $re["code"]="err0105";
	   	  $re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
	    	$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
				$response->headers->set('Content-Type', 'text/json');
				return $response;
	   	}
  	}
    //作自动登录
		$ds=$this->checkOpenid($da ,$request->get("openid"));
		if($ds!=false){
			$this->autoLogin($request,$ds['login_account']);
		}
		
		$content=$request->get("content");
		$uid=$request->get('uid');
		$TencentController=new \Justsy\BaseBundle\Controller\TencentWeiboController();
		$TencentController->setContainer($this->container);
		$re=$TencentController->publishWeibo($uid,$content);
		
		$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		$response->headers->set('Content-Type', 'text/json');
		return $response;
	}
	//获取新浪微博
	public function getMySinaWeiboAction(){
		$request = $this->get("request");
		$da = $this->get("we_data_access");
		$re = array("returncode"=>"9999");
  	if($_SERVER['REQUEST_METHOD']!="POST")
  	{
	   	$re["code"]="err0105";
	   	$re["msg"]="只支持使用POST方式提交数据.";
			$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
			$response->headers->set('Content-Type', 'text/json');
			return $response;
  	}
  	//判断请求域。是wefafa或子域则不验证授权令牌
  	$isWeFaFaDomain = $this->checkWWWDomain();
  	if(!$isWeFaFaDomain)
  	{
	   	$token = $this->checkAccessToken($request,$da);	
	   	if(!$token)
	   	{
	   	  $re["code"]="err0105";
	   	  $re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
	    	$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
				$response->headers->set('Content-Type', 'text/json');
				return $response;
	   	}
  	}
    //作自动登录
		$ds=$this->checkOpenid($da ,$request->get("openid"));
		if($ds!=false){
			$this->autoLogin($request,$ds['login_account']);
		}
		
		$uid=$request->get("uid");
		$reqnum=$request->get("reqnum");
		$since_id=$request->get("since_id");
		
		$sinaController=new \Justsy\BaseBundle\Controller\SinaWeiboController();
		$sinaController->setContainer($this->container);
		$re=$sinaController->getMyWeibo($uid,$reqnum,$since_id);
		
		$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		$response->headers->set('Content-Type', 'text/json');
		return $response;
		
	}
	//获取腾讯微博
	public function getMyTencentWeiboAction(){
		$request = $this->get("request");
		$da = $this->get("we_data_access");
		$re = array("returncode"=>"9999");
  	if($_SERVER['REQUEST_METHOD']!="POST")
  	{
	   	$re["code"]="err0105";
	   	$re["msg"]="只支持使用POST方式提交数据.";
			$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
			$response->headers->set('Content-Type', 'text/json');
			return $response;
  	}
  	//判断请求域。是wefafa或子域则不验证授权令牌
  	$isWeFaFaDomain = $this->checkWWWDomain();
  	if(!$isWeFaFaDomain)
  	{
	   	$token = $this->checkAccessToken($request,$da);	
	   	if(!$token)
	   	{
	   	  $re["code"]="err0105";
	   	  $re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
	    	$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
				$response->headers->set('Content-Type', 'text/json');
				return $response;
	   	}
  	}
    //作自动登录
		$ds=$this->checkOpenid($da ,$request->get("openid"));
		if($ds!=false){
			$this->autoLogin($request,$ds['login_account']);
		}
		
		$uid=$request->get("uid");
		$reqnum=$request->get("reqnum");
		$lastid=$request->get("lastid");
		$pagetime=$request->get("pagetime");
		
		$TencentController=new \Justsy\BaseBundle\Controller\TencentWeiboController();
		$TencentController->setContainer($this->container);
		$re=$TencentController->getMyWeibo($uid,$reqnum,$pagetime,$lastid);
		
		$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		$response->headers->set('Content-Type', 'text/json');
		return $response;
	}
	private function do_post_request($url, $data, $optional_headers = null)
    {
        $params = array('http' => array(
                'method' => 'POST',
                'content' => $data
              ));
        if ($optional_headers !== null) {
            $params['http']['header'] = $optional_headers;
        }
        $ctx = stream_context_create($params);
        $fp = @fopen($url, 'r', false, $ctx);
        if (!$fp) {
            throw new \Exception("Problem with $url, $php_errormsg");
        }
        $response = @stream_get_contents($fp);
        if ($response === false) {
            throw new \Exception("Problem reading data from $url, $php_errormsg");
        }
        return $response;
    }
    private function checkWWWDomain()
		{
			try{
				//$this->get("logger")->info(">>>>>>>>>>>>>>>>>>>HTTP_REFERER:".$_SERVER["HTTP_REFERER"]);
        if(!isset($_SERVER["HTTP_REFERER"]) || empty($_SERVER["HTTP_REFERER"])) return false;
        
        $srv_name = $_SERVER["HTTP_REFERER"];
        
        
        //获取安全的，不需要验证访问令牌的域
			  $mayDomain = $this->getSecurityDomains();
			  //$this->get("logger")->err(">>>>>>>>>>>>>>>>>>>SecurityDomains:".implode(",", $mayDomain));
			  if(in_array($srv_name,$mayDomain)) return true;
			  $resDomainAry = parse_url ($srv_name);
			  if(Utils::is_ip($srv_name)) $resDomain=$srv_name;
			  else
			  {
			      $host = $resDomainAry["host"];
			      if(empty($host)) return false;
			      $resDomain = strpos($host,".")===false? $host : substr($host, strpos($host,".")+1); 
			  }
			  //$this->get("logger")->err(">>>>>>>>>>>>>>>>>>>resDomain:".$resDomain);
		    return in_array($resDomain,$mayDomain);
		  }
		  catch(\Exception $e)
		  {
		  	$this->get('logger')->err($e);
		  	return false;
		  }
		}  
		
		private function getSecurityDomains()
		{
			  if(!empty(ApiController::$securityDomains))
			  {
			     return ApiController::$securityDomains;	
			  }
			  $mayDomain=array("localhost");
			  $configWeFaFa=$this->container->getParameter('open_api_url');//获取配置的wefafa地址
			  $tmp = parse_url($configWeFaFa);
			  $host = $tmp["host"];
			  //$this->get("logger")->err(">>>>>>>>>>>>>>>>>>>open_api_url>host:".$host);
			  if(Utils::is_ip($host)) $mayDomain[]= $host;
			  else{
			  	  $host =substr($host, strpos($host,".")+1); 
			  	  $mayDomain[]= $host;
			  }
			  $configWeFaFa=$this->container->getParameter('fafa_appcenter_url');//获取配置的应用中心地址
			  $tmp = parse_url($configWeFaFa);
			  $host = $tmp["host"];
			  //$this->get("logger")->err(">>>>>>>>>>>>>>>>>>>fafa_appcenter_url>host:".$host);
			  if(Utils::is_ip($host)) $mayDomain[]= $host;
			  else{
			  	  $host =substr($host, strpos($host,".")+1); 
			  	  $mayDomain[]= $host;
			  }	
			  ApiController::$securityDomains = $mayDomain;
			  return 		ApiController::$securityDomains;
		}
		
		private function checkOpenid($db,$openid)
		{
			  $sql ="select * from we_staff where openid=? and state_id='1'";
			  $ds = $db->getData("che",$sql,array((string)$openid));
			  if($ds && count($ds["che"]["rows"])>0) return $ds["che"]["rows"][0];
			  else return false;
		}
		

		  
		//验证授权令牌
		private function checkAccessToken($resquest,$db)
		{
			  $sql = "select userid from we_app_oauth_sessions where appid=? and access_token=? and access_token_expires>=?";
			  $para=array();
              $para[]=(string)($resquest->get("Appid").$resquest->get("appid"));
			  
			  $para[]=(string)($resquest->get("Access_token").$resquest->get("access_token"));
              $para[]=time();
			  $ds = $db->getData("che",$sql,$para);
			  if($ds && count($ds["che"]["rows"])>0){ 
			  	$userid=$ds["che"]["rows"][0]["userid"];
			  	$openid = $resquest->get("Openid").$resquest->get("openid");
			  	if($userid=="wefafaproxy" || $userid==$openid)
			  		return true;
			  	else 
			  		return false;
			  }
			  else{
			  	 //$this->get("logger")->err("API access URL:".$_SERVER["HTTP_REFERER"]);
			  	 $this->get("logger")->err("access_token not pass:");
			  	 $this->get("logger")->err("                appid:".$resquest->get("appid"));
			  	 $this->get("logger")->err("               openid:".$resquest->get("openid"));
			  	 $this->get("logger")->err("         access_token:".$resquest->get("access_token"));
			  	 return false;			  	
			  }
		}
    //自动登录
    private function autoLogin($res,$login_account)
    {
    	$userprovider=new UserProvider($this->container);
  	  $user = $userprovider->loadUserByUsername($login_account);
  	  $token = new UsernamePasswordToken($user, $user->getPassword(), "secured_area", $user->getRoles());
  	  $this->get("security.context")->setToken($token);
  	  $session = $res->getSession()->set('_security_'.'secured_area',  serialize($token));
  		return empty($user)? false:true;
    }
    //comfrom
    private function setComefrom($appid)
    {
    	$request = $this->getRequest();
    	$da = $this->get('we_data_access');
    	$sql="select comefrom from we_appcenter_apps where appid=?";
    	$params=array((string)$appid);
    	$ds=$da->Getdata('comefrom',$sql,$params);
    	if($ds['comefrom']['recordcount']>0){
    		$comefrom=$ds['comefrom']['rows'][0]['comefrom'];
    		$request->getSession()->set('comefrom',$comefrom);
    	}
    }
    private  function formatXml($xml)
    {
        $result = $xml;
        $result =str_replace("&","&amp;" ,$result);
        $result = str_replace("<","&lt;",$result);
        $result = str_replace(">","&gt;",$result);
        $result = str_replace("'","&apos;",$result);
        $result = str_replace("\"","&quot;",$result);
        return $result;
    }

    private  function formatText($text)
    {
        $result = $text;
        $result =str_replace("&amp;" ,"&",$result);
        $result = str_replace("&lt;","<",$result);
        $result = str_replace("&gt;",">",$result);
        $result = str_replace("&apos;","'",$result);
        $result = str_replace("&quot;","\"",$result);
        return $result;
    }
     private function getSubDomain($account)
	  {
	    $re = '';
	    $tmp = explode("@",$account);
	    if (count($tmp) > 1) $re = $tmp[1];
	    return $re;
	  }
	  private function responseJson($re){
        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }
    private function checkmobile(){
        return "/^13[0-9]{9}$|15[0-9]{9}$|18[0-9]{9}$|19[0-9]{9}$/";
    }
    private function checkmail(){
        return "/([a-z0-9]*[-_.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[.][a-z]{2,3}([.][a-z]{2})?/i";
    }

    private function checkint($num){
        if (is_int($num) && ($num>=0)) return true;
        else return false;
    }    
}
?>