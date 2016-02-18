<?php
namespace Justsy\BaseBundle\weibo;

class TencentOAuth
{
	public static $client_id='801537807';
	public static $client_key='74adce76c14ec318f7da5e84002d61e6';
	public $appkey;
	public $appsecret;
	public $access_token;
	public $refresh_token;
	function accessTokenURL()  { return 'https://open.t.qq.com/cgi-bin/oauth2/access_token'; }
	/**
	 * @ignore
	 */
	function authorizeURL()    { return 'https://open.t.qq.com/cgi-bin/oauth2/authorize'; }
	
	function __construct($client_id, $client_secret, $access_token = NULL, $refresh_token = NULL)
	{
		$this->appkey=$client_id;
		$this->appsecret=$client_secret;
		$this->access_token=$access_token;
		$this->refresh_token=$refresh_token;
	}
	function getAuthorizeURL($url, $response_type = 'code', $state = NULL)
	{
		$params = array();
		$params['client_id'] = $this->appkey;
		$params['redirect_uri'] = 'http://'.$_SERVER["HTTP_HOST"].$url;
		$params['response_type'] = $response_type;
		$params['state'] = $state;
		return $this->authorizeURL() . "?" . http_build_query($params);
	}
	function getAccessToken($redirect_url,$code,$grant_type="authorization_code")
	{
		$params=array();
		$params['format'] = 'json';
		$params['client_id'] = $this->appkey;
		$params['client_secret']=$this->appsecret;
		$params['redirect_uri']='http://'.$_SERVER['HTTP_HOST'].$redirect_url;
		$params['code']=$code;
		$params['grant_type']=$grant_type;
		$url=$this->accessTokenURL()."?".http_build_query($params);
		$token=$this->do_post_request($url);
		if(is_string($token)){
			return $this->formatToArray($token);
		}
		return $token;
	}
	private function formatToArray($str)
	{
		$arr1=explode('&',$str);
		$re=array();
		for($i=0;$i< count($arr1);$i++){
			if(empty($arr1[$i]))continue;
			$arr2=explode('=',$arr1[$i]);
			$re[$arr2[0]]=$arr2[1];
		}
		return $re;
	}
	private	function do_post_request($url, $data, $optional_headers = null,$logger=null)
	{
	  $params = array('http' => array(
	              'method' => 'POST',
	              'content' => $data
	            ));
	  if ($optional_headers !== null) {
	    $params['http']['header'] = $optional_headers;
	  }
	  $ctx = stream_context_create($params);
	  if ($logger){
	   $logger->alert("POST URL:".$url);
	   $logger->alert("POST Data:".$data);
	  }    
	  $fp = @fopen($url, 'r', false, $ctx);
	  if (!$fp) {
	    throw new Exception("Problem with $url, $php_errormsg");
	  }
	
	  $response = @stream_get_contents($fp);
	  if ($response === false) {
	    throw new Exception("Problem reading data from $url, $php_errormsg");
	  }
	  if ($logger) $logger->alert("POST Result:".$response);
	  return $response;
	}
}
?>