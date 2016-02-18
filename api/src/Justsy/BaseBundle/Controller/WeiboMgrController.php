<?php
namespace Justsy\BaseBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Login\UserSession;
use Justsy\BaseBundle\weibo\WeiboMgr;
class WeiboMgrController extends Controller
{
	//获取帐号列表
	public function getWeiboAccountsAction()
	{
		
	}
	//发微博
  public function publishWeiboAction()
  {
  	$da = $this->get("we_data_access");
  	$user = $this->get('security.context')->getToken()->getUser();
  	$request = $this->get("request");
		
		$content=$request->get("content");
		$uids=$request->get("uids");
  }
  public function publishWeibo($uids,$content)
  {
  	$da = $this->get("we_data_access");
  	$user = $this->get('security.context')->getToken()->getUser();
  	$result=array('s'=>'1','m'='');
  	try{
  		$uidarr=explode(",",trim($uids));
			$WeiboMgr=new WeiboMgr($da,$this->get('logger'));
	  	for($i=0;$i<count($uidarr);$i++){
	  		if(empty($uidarr[$i]))continue;
	  		$type=$WeiboMgr->getPlatType($uidarr[$i],$user->eno);
	  		if($type==null)continue;
	  		if($type=='sina'){
	  			$sinaController=new \Justsy\BaseBundle\Controller\SinaWeiboController();
	  			try{
	  				$sinaController->publishWeibo($uidarr[$i],$content);
	  			}
	  			catch(\Exception $r1){
	  			
	  			}
	  		}
	  		else if($type=='tencent'){
	  			$tencentController= new \Justsy\BaseBundle\Controller\TencentWeiboController();
	  			try{
	  				$tencentController->publishWeibo($uidarr[$i],$content);
	  			}
	  			catch(\Exception $r){
	  				
	  			}
	  		}
	  		else if($type=='weixin'){
	  			
	  		}
	  	}
  	}
  	catch(\Exception $e){
  		$result['s']='0';
  		$result['m']='发布失败';
  	}
  	return $result;
  }
  //获取所发布的微博
  public function getMyWeiboAction()
  {
  	
  }
  //评论微博
  public function commitWeiboAction()
  {
  	
  }
}
?>