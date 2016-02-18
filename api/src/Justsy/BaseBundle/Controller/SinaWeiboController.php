<?php

namespace Justsy\BaseBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Login\UserSession;
use Justsy\BaseBundle\weibo\SaeTOAuthV2;
use Justsy\BaseBundle\weibo\SaeTClientV2;
use Justsy\BaseBundle\weibo\SinaWeiboMgr;

class SinaWeiboController extends Controller
{   
	public $groups=null;
  
  public function authorizeAction()
  {
  	try{
  		$da = $this->get("we_data_access");
  		$user = $this->get('security.context')->getToken()->getUser();
  		$request = $this->get("request");
	  	$code=$request->get("code");
	  	if(empty($code)){
	  		$Auth=new SaeTOAuthV2(SaeTOAuthV2::$appid,SaeTOAuthV2::$appkey);
	  		$get_code_url=$Auth->getAuthorizeURL($this->generateUrl("JustsyBaseBundle_weibo_sina_authorize"),"code","123456");
	  		$response=new Response('');
		  	$response->headers->set('location',$get_code_url);
		  	return $response;
	  	}
	  	else{
	  		$Auth=new SaeTOAuthV2(SaeTOAuthV2::$appid,SaeTOAuthV2::$appkey);
	  		$token=$Auth->getAccessToken('code',array(
	  		'code'=> $code,
	  		'redirect_uri'=> 'http://'.$_SERVER["HTTP_HOST"].$this->generateUrl("JustsyBaseBundle_weibo_sina_authorize")
	  		));
	  		$SinaWeiboMgr=new SinaWeiboMgr($da,$token["UID"],$token["Token"]);
	  		$SinaWeiboMgr->saveToken($token,$user->getUserName(),$user->eno);
	  		return $this->render("JustsyBaseBundle:Weibo:sinaback.html.twig",array('s'=>'1','m'=>'','curr_network_domain'=> $user->edomain));
	  	}
  	}
  	catch(\Exception $e){
  		var_dump($e->getMessage());
  		return $this->render("JustsyBaseBundle:Weibo:sinaback.html.twig",array('s'=>'0','m'=> $e->getMessage(),'curr_network_domain'=> $user->edomain));
  	}
  }
  public function getlistAction()
  {
  	$request = $this->get("request");
  	$uid =$_SESSION["uid"];
  	$token = $_SESSION["token"];
  	$page = $request->get("page");
  	$where = array("id"=>$request->get("id"),
  	               "screen_name"=>$request->get("screen_name"),
  	               "verified"=>$request->get("verified"),
  	               "gender"=>$request->get("gender"),
  	               "province"=>$request->get("province"), 
  	               "city"=>$request->get("city")  	               
  	);
  	$mgr = new SinaWeiboMgr($this->get('we_data_access'),$uid,$token);

  	$myfans = $mgr->getlist(empty($page) ? 1: ((int)$page),$where);
    $response = new Response(json_encode($myfans));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  
  public function getfansBySinaAction()
  {
  	$request = $this->get("request");
  	$uid = $_SESSION["uid"];
  	$token = $_SESSION["token"];
  	$next_cursor = $request->get("next_cursor");
  	$mgr = new SinaWeiboMgr($this->get('we_data_access'),$uid,$token);
  	try{
        $re=$mgr->synclistbysina($this->get("logger"),$next_cursor);
  	    $myfans = array();
  	    if(!empty($re["next_cursor"]) && $re["next_cursor"]!=0)
  	      $myfans["count"] = $mgr->getFansCountByBindUid($uid);   //粉丝还未同步完成时，只返回已获取的部数
  	    else
  	      $myfans=$mgr->getlist(1);                               //获取完毕时，返回一页的数据及总数
  	    $myfans["total_number"] = $re["total_number"];
  	    $myfans["cursor"] = $re["next_cursor"];
    }
    catch(\Exception $e)
    {
        	$myfans = $e;
    }
    $response = new Response(json_encode($myfans));
    $response->headers->set('Content-Type', 'text/json');
    return $response;      	
  }
  //发微博
  public function publishWeibo($uid,$content)
  {
  	$result=array('returncode'=>'0000','msg'=>'','err'=>array());
  	$da = $this->get("we_data_access");
  	$user = $this->get('security.context')->getToken()->getUser();
  	$SinaWeiboMgr=new SinaWeiboMgr($da);
  	$token=$SinaWeiboMgr->getToken($uid,$user->eno);
  	if($token==null){
  		$result=array('returncode'=>'0003','msg'=>'令牌无效','err'=>array());
  		return $result;
  	}
  	$SaeTClientV2= new SaeTClientV2(SaeTOAuthV2::$appid,SaeTOAuthV2::$appkey,$token['access_token'],$token['refresh_token']);
  	$re=$SaeTClientV2->update($content);
		if($SaeTClientV2->hasError($re)){
	  	$result['returncode']='0004';
	  	$result['err']=$SaeTClientV2->getError();
    }
  	return $result;
  }
  //获取发布的微博
  public function getMyWeibo($uid,$reqnum=20,$since_id)
  {
  	$result=array('returncode'=>'0000','data'=>null,'msg'=>'','err'=>array());
  	$da = $this->get("we_data_access");
  	$user = $this->get('security.context')->getToken()->getUser();
  	$SinaWeiboMgr=new SinaWeiboMgr($da);
  	$token=$SinaWeiboMgr->getToken($uid,$user->eno);
  	if($token==null){
  		$result=array('returncode'=>'0003','msg'=>'令牌无效','err'=>array());
  		return $result;
  	}
  	$SaeTClientV2= new SaeTClientV2(SaeTOAuthV2::$appid,SaeTOAuthV2::$appkey,$token['access_token'],$token['refresh_token']);
  	$re=$SaeTClientV2->user_timeline_by_id('', $page = 1 ,$reqnum, $since_id);
  	if($SaeTClientV2->hasError($re)){
    	$result['returncode']='0004';
    	$result['err']=$SaeTClientV2->getError();
    }
    else{
    	$data=array();
    	foreach($re['statuses'] as $row){
    		$data[]=array(
    			"blog_id"=> $row["id"],
    			"create_at"=> $row["create_at"],
    			"blog_content"=> $row["text"],
    			"source"=> $row["source"],
    			"comment_count" => $row["comments_count"],
    			"repost_count" => $row["reposts_count"],
    			"pic_urls" => $row["pic_urls"]
    		);
    	}
    	$result['data']=$data;
    }
  	return $result;
  } 
}