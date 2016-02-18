<?php
namespace Justsy\BaseBundle\weibo;
class TencentClient
{
	public $appkey;
	public $appsecret;
	public $access_token;
	public $refresh_token;
	public $openid;
	private $get_user_baseinfo_url="https://open.t.qq.com/api/user/info";
	private $publish_weibo_url="https://open.t.qq.com/api/t/add";
	private $get_weibo_url="https://open.t.qq.com/api/statuses/broadcast_timeline";
	private $get_commit_url="https://open.t.qq.com/api/t/re_list";
	function __construct($client_id, $client_secret,$openid, $access_token = NULL, $refresh_token = NULL)
	{
		$this->appkey=$client_id;
		$this->appsecret=$client_secret;
		$this->access_token=$access_token;
		$this->refresh_token=$refresh_token;
		$this->openid=$openid;
	}
	//获取用户基本信息
	public function get_user_baseinfo(){
		$params=array();
		$params['format']='json';
		$params['oauth_consumer_key']=$this->appkey;
		$params['access_token']=$this->access_token;
		$params['openid']=$this->openid;
		$params['clientip']='';
		$params['oauth_version']='2.a';
		$params['scope']='all';
		$re=$this->do_post_request($this->get_user_baseinfo_url,http_build_query($params));
		$re=json_decode($re,true);
		if(isset($re['data']))
			return $re['data'];
		else
			return $re;
	}
	//发布微博
	public function publish_weibo($content){
		$params=array();
		$params['format']='json';
		$params['access_token']=$this->access_token;
		$params['oauth_consumer_key']=$this->appkey;
		$params['clientip']='';
		$params['openid']=$this->openid;
		$params['content']=$content;
		$params['oauth_version']='2.a';
		$params['scope']='all';
		$re=$this->do_post_request($this->publish_weibo_url,http_build_query($params));
		return json_decode($re,true);
	}
	//发表带图片的微博
	public function publish_img_weibo(){
		
	}
	//获取所发布的微博
	public function getMyWeiboList($reqnum='20',$pageflag='0',$pagetime='0',$lastid='0')
	{
		$params=array();
		$params['format']='json';
		$params['oauth_consumer_key']=$this->appkey;
		$params['client_id']=$this->appkey;
		$params['reqnum']=$reqnum;
		$params['contenttype']='0';
		$params['type']='0x1';
		$params['oauth_version']='2.a';
		$params['scope']='all';
		$params['clientip']='';
		$params['access_token']=$this->access_token;
		$params['openid']=$this->openid;
		$params['pageflag']=$pageflag;
		$params['pagetime']=$pagetime;
		$params['lastid']=$lastid;
		$re=$this->do_get_request($this->get_weibo_url."?".http_build_query($params));
		return json_decode($re,true);
	}
	//获取微博的评论列表
	public function getCommitList($blog_id,$reqnum,$pageflag='0',$pagetime='0',$twitterid)
	{
		$params=array();
		$params['format']='json';
		$params['oauth_consumer_key']=$this->appkey;
		$params['client_id']=$this->appkey;
		$params['reqnum']=$reqnum;
		$params['oauth_version']='2.a';
		$params['scope']='all';
		$params['clientip']='';
		$params['access_token']=$this->access_token;
		$params['openid']=$this->openid;
		$params['pageflag']=$pageflag;
		$params['pagetime']=$pagetime;
		$params['rootid']=$blog_id;
		$params['twitterid']=$twitterid;
		$re=$this->do_post_request($this->get_weibo_url,http_build_query($params));
		return json_decode($re,true);
	}
	public function hasError($re)
	{
		if((isset($re['errcode']) && $re['errcode']!='0') || (isset($re['errCode']) && $re['errCode']!='0') || (isset($re['err']) && $re['err']!='0') || (isset($re['errorCode']) && $re['errorCode']!='0'))
			return true;
		else
			return false;
	}
	public function getError($re)
	{
		if(isset($re['errcode'])){
			return array(
				"ret"=> $re['ret'],
				"code"=> $re['errcode'],
				"desc"=> $re['msg']
			);
		}
		else if(isset($re['errCode'])){
			return array(
				"ret"=> $re['ret'],
				"code"=> $re['errCode'],
				"desc"=> $re['msg']
			);
		}
		else if(isset($re['err'])){
			return array(
				"ret"=> $re['ret'],
				"code"=> $re['err'],
				"desc"=> $re['msg']
			);
		}
		else if(isset($re['errorCode'])){
			return array(
				"ret"=> $re['ret'],
				"code"=> $re['errorCode'],
				"desc"=> $re['msg']
			);
		}
		else{
			return null;
		}
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
	    throw new \Exception("Problem with $url, $php_errormsg");
	  }
	
	  $response = @stream_get_contents($fp);
	  if ($response === false) {
	    throw new \Exception("Problem reading data from $url, $php_errormsg");
	  }
	  if ($logger) $logger->alert("POST Result:".$response);
	  return $response;
	}
	public static function do_get_request($url) 
  {
			try{
		    $ch = curl_init();
		    curl_setopt($ch, CURLOPT_URL, $url);
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		    //curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT,10);
		    //curl_setopt($ch, CURLOPT_FORBID_REUSE, true); //处理完后，关闭连接，释放资源
		    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
		    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); 
		    $content = curl_exec($ch);
		    return $content;
		}
		catch(Exception $e){
			var_dump($e->getMessage());
		}
  }
}
?>