<?php
namespace Justsy\BaseBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Login\UserSession;
use Justsy\BaseBundle\weibo\TencentOAuth;
use Justsy\BaseBundle\weibo\TencentClient;
use Justsy\BaseBundle\weibo\TencentWeiboMgr;

class TencentWeiboController extends Controller
{
	public function authorizeAction()
  {
  	try{
  		$da = $this->get("we_data_access");
  		$user = $this->get('security.context')->getToken()->getUser();
  		$request = $this->get("request");
	  	$code=$request->get("code");
	  	$openid=$request->get("openid");
	  	$openkey=$request->get("openkey");
	  	
	  	if(empty($code)){
	  		$Auth=new TencentOAuth(TencentOAuth::$client_id,TencentOAuth::$client_key);
	  		$get_code_url=$Auth->getAuthorizeURL($this->generateUrl("JustsyBaseBundle_weibo_tencent_authorize"),"code","123456");
	  		$response=new Response('');
		  	$response->headers->set('location',$get_code_url);
		  	return $response;
	  	}
	  	else{
	  		$Auth=new TencentOAuth(TencentOAuth::$client_id,TencentOAuth::$client_key);
	  		$token=$Auth->getAccessToken($this->generateUrl("JustsyBaseBundle_weibo_tencent_authorize"),$code);
	  		$TencentWeiboMgr=new TencentWeiboMgr($da,$openid,$token["access_token"]);
	  		$TencentWeiboMgr->saveToken($token,$openid,$openkey,$user->getUserName(),$user->eno);
	  		return $this->render("JustsyBaseBundle:Weibo:tencentback.html.twig",array('s'=>'1','m'=>'','curr_network_domain'=> $user->edomain));
	  	}
  	}
  	catch(\Exception $e){
  		return $this->render("JustsyBaseBundle:Weibo:sinaback.html.twig",array('s'=>'0','m'=> $e->getMessage(),'curr_network_domain'=> $user->edomain));
  	}
  }
  //发微博
  public function publishWeibo($uid,$content)
  {
  	$result=array('returncode'=>'0000','data'=>null,'msg'=>'','err'=>array());
  	$da = $this->get("we_data_access");
  	$user = $this->get('security.context')->getToken()->getUser();

  	$TencentWeiboMgr=new TencentWeiboMgr($da);
  	$token=$TencentWeiboMgr->getToken($uid,$user->eno);
  	if($token==null){
  		$result=array('returncode'=>'0003','msg'=>'令牌无效','err'=>array());
  		return $result;
  	}
  	$TencentClient=new TencentClient(TencentOAuth::$client_id,TencentOAuth::$client_key,$token['openid'],$token['access_token'],$token['refresh_token']);
    $re=$TencentClient->publish_weibo($content);
   	if($TencentClient->hasError($re)){
    	$result['returncode']='0004';
    	$result['err']=$TencentClient->getError($re);
    }
  	return $result;
  }
  //获取所发布的微博
  public function getMyWeiboAction()
  {
  	
  }
  public function getMyWeibo($uid,$reqnum,$pagetime,$lastid)
  {
  	$result=array('returncode'=>'0000','data'=>null,'msg'=>'','err'=>array());
  	$da = $this->get("we_data_access");
  	$user = $this->get('security.context')->getToken()->getUser();
  	$TencentWeiboMgr=new TencentWeiboMgr($da);
  	$token=$TencentWeiboMgr->getToken($uid,$user->eno);
  	if($token==null){
  		$result=array('returncode'=>'0003','msg'=>'令牌无效','err'=>array());
  		return $result;
  	}
  	$TencentClient=new TencentClient(TencentOAuth::$client_id,TencentOAuth::$client_key,$token['openid'],$token['access_token'],$token['refresh_token']);
    $re=$TencentClient->getMyWeiboList($reqnum,empty($lastid)?"0":"2",$pagetime,$lastid);
    if($TencentClient->hasError($re)){
    	$result['returncode']='0004';
    	$result['err']=$TencentClient->getError($re);
    }
    else{
    	$data=array();
    	foreach($re['data']['info'] as $row){
    		$data[]=array(
    			"blog_id"=> $row["id"],
    			"create_at"=> $row["timestamp"],
    			"blog_content"=> $row["text"],
    			"source"=> $row["from"],
    			"comment_count" => $row["mcount"],
    			"repost_count" => $row["count"],
    			"pic_urls" => $row["image"]
    		);
    	}
    	$result['data']=$data;
    }
  	return $result;
  }
  //评论微博
  public function commitWeiboAction()
  {
  	
  }
  public function commitWeibo($blog_id,$reqnum=20,$pagetime,$twitterid)
  {
  	$result=array('returncode'=>'0000','data'=>null,'msg'=>'','err'=>array());
  	$da = $this->get("we_data_access");
  	$user = $this->get('security.context')->getToken()->getUser();
  	$TencentWeiboMgr=new TencentWeiboMgr($da);
  	$token=$TencentWeiboMgr->getToken($uid,$user->eno);
  	if($token==null){
  		$result=array('returncode'=>'0003','msg'=>'令牌无效','err'=>array());
  		return $result;
  	}
  	$TencentClient=new TencentClient(TencentOAuth::$client_id,TencentOAuth::$client_key,$token['openid'],$token['access_token'],$token['refresh_token']);
  	$re=$TencentClient->getCommitList($blog_id,$reqnum,$pageflag='0',$pagetime,$twitterid);
  }
}
?>