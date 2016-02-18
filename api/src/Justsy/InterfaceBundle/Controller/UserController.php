<?php
namespace Justsy\InterfaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Management\FriendCircle;
use Justsy\BaseBundle\Management\Staff;
use Justsy\BaseBundle\Management\UserTag;

class UserController extends Controller
{
	public function getRecomUserAction()
	{
		$user = $this->get('security.context')->getToken()->getUser();
    $request = $this->getRequest();
    $code=ReturnCode::$SUCCESS;
    $rows=array();
    try{
	    $da = $this->get('we_data_access');
	    $da->PageSize  =$request->get('pagesize',4);
		  $da->PageIndex = $request->get('pageindex')?$request->get('pageindex')-1:0;
		  
	    $friendcircle=new FriendCircle($da,$this->get('logger'),$this->container);
	    $rows=$friendcircle->getRemAccount($user->eno,$user->getUserName());
	  }
    catch(\Exception $e)
    {
    	$this->get('logger')->err($e);
			$code=ReturnCode::$SYSERROR;
			$rows=array(); 
    }
    $re=array('returncode'=> $code,'rows'=> $rows);
    $response=new Response(json_encode($re));
	  $response->headers->set('Content-Type','Application/json');
	  return $response;
	}
	public function attenUserAction()
	{
		$code=ReturnCode::$SUCCESS;
		$msg='';
		try{
			$user = $this->get('security.context')->getToken()->getUser();
	    $request = $this->getRequest();
	    $login_account=$request->get('atten_account');
	    $da = $this->get('we_data_access');
	    $da_im = $this->get('we_data_access_im');
	    $staff=new Staff($da,$da_im,$user->getUserName());
	    $staff->attentionTo($login_account);
	  }
	  catch(\Exception $e)
	  {
	  	$this->get('logger')->err($e);
	  	$msg='系统错误';
    	$code=ReturnCode::$SYSERROR;
	  }
	  $re=array('returncode'=> $code,'msg'=> $msg);
	  $response=new Response(json_encode($re));
		$response->headers->set('Content-Type','Application/json');
		return $response;
	}
	public function unAttenUserAction()
	{
		$code=ReturnCode::$SUCCESS;
		$msg='';
		try{
			$user = $this->get('security.context')->getToken()->getUser();
	    $request = $this->getRequest();
	    $login_account=$request->get('atten_account');
	    $da = $this->get('we_data_access');
	    $da_im = $this->get('we_data_access_im');
	    $staff=new \Justsy\BaseBundle\Controller\EmployeeCardController();
	    $staff->setContainer($this->container);
	    $resp=$staff->cancelAttentionAction($login_account);
	    $res=json_decode($resp->getContent(),true);
	    if($res['succeed']!='1')
	    {
	    	$msg='操作失败';
    		$code=ReturnCode::$SYSERROR;
	    }
	  }
	  catch(\Exception $e)
	  {
	  	$this->get('logger')->err($e);
	  	$msg='系统错误';
    	$code=ReturnCode::$SYSERROR;
	  }
	  $re=array('returncode'=> $code,'msg'=> $msg);
	  $response=new Response(json_encode($re));
		$response->headers->set('Content-Type','Application/json');
		return $response;
	}
	public function getUserTagAction()
	{
		$code=ReturnCode::$SUCCESS;
	  $rows=array();
		try{
			$user = $this->get('security.context')->getToken()->getUser();
	    $request = $this->getRequest();
	   	$da = $this->get('we_data_access');
	    $account=$request->get('account');
	    if(empty($account))$account=$user->getUserName();
	   	$login_account=$this->getAccountByKey($da,$account);
	    $usertag=new UserTag($da,$this->get('logger'),$this->container);
	    $rows=$usertag->gettag($login_account);
	  }
	  catch(\Exception $e){
	  	$this->get('logger')->err($e);
			$code=ReturnCode::$SYSERROR;
			$rows=array(); 
	  }
    $re=array('returncode'=> $code,'rows'=> $rows);
    $response=new Response(json_encode($re));
	  $response->headers->set('Content-Type','Application/json');
	  return $response;
	}
	public function checkTagVersionAction()
	{
		$code=ReturnCode::$SUCCESS;
	  $tagIds='';
	  $msg='';
	  try{
			$user = $this->get('security.context')->getToken()->getUser();
	    $request = $this->getRequest();
	   	$da = $this->get('we_data_access');
	    $account=$request->get('account');
	    if(empty($account))$account=$user->getUserName();
	   	$login_account=$this->getAccountByKey($da,$account);
			$sql="select tag_id from we_tag where owner_id=? order by tag_id";
			$params=array($login_account);
			$ds=$da->Getdata('tag',$sql,$params);
			$rows=$ds['tag']['rows'];
	    $tags=array();
	    foreach($rows as $row)
	    {
	    	array_push($tags,$row['tag_id']);
	    }
	    $tagIds=implode(',',$tags);
	  }
	  catch(\Exception $e){
	  	$this->get('logger')->err($e);
			$code=ReturnCode::$SYSERROR;
			$tagIds='';
			$msg='系统错误'; 
	  }
	  $re=array();
	  if($code==ReturnCode::$SUCCESS){
	  	$re=array('returncode'=> $code,'version'=> Md5($tagIds));
	  }
	  else
	  	$re=array('returncode'=> $code,'version'=> '');
    $response=new Response(json_encode($re));
	  $response->headers->set('Content-Type','Application/json');
	  return $response;
	}
	protected function getAccountByJid($da,$list)
	{
		try{
			$sql="select login_account from we_staff where fafa_jid in (";
			$params=array();
			for($i=0;$i<count($list);$i++)
			{
				if($i==(count($list)-1))
				{
					$sql.='?';
				}
				else
				{
					$sql.='?,';
				}
				array_push($params,$list[$i]);
			}
			$sql.=")";
			$ds=$da->Getdata('account',$sql,$params);
			$rows=$ds['account']['rows'];
			$re=array();
			foreach($rows as $row)
			{
				array_push($re,$row['login_account']);
			}
			return $re;
		}
		catch(\Exception $e)
		{
			$this->get('logger')->err($e);
			return array();
		}
	}
	protected function getAccountByKey($da,$key){
		try{
			if(!empty($key)){
				if(strpos($key,'-')!==false){
					$sql="select login_account from we_staff where fafa_jid=?";
				}
				else if(strpos($key,'@')!==false){
					return $key;
				}
				else if(preg_match("/^(13[0-9]|15[0|3|6|7|8|9]|18[2|5|6|8|9])\d{8}$/",$key)==1){
					$sql="select login_account from we_staff where mobile_bind=?";
				}
				else{
					$sql="select login_account from we_staff where openid=?";
				}
				$params=array($key);
				$ds=$da->Getdata('account',$sql,$params);
				if($ds['account']['recordcount']>0){
					return $ds['account']['rows'][0]['login_account'];
				}
				else{
					return null;
				}
			}
			else{
				return null;
			}
		}
		catch(\Exception $e)
		{
			$this->get('logger')->err($e);
			return null;
		}
	}
}
?>