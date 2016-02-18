<?php
namespace Justsy\InterfaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Login\UserSession;
use Justsy\BaseBundle\Management\IdentifyAuth;
use Justsy\BaseBundle\Management\Identify;
use Justsy\InterfaceBundle\Common\ReturnCode;

class IdentifyAuthController extends Controller
{
	public function SaveApplyAction()
	{
		 $da = $this->get('we_data_access');
		 $request = $this->getRequest();
		 $user=$this->get('security.context')->getToken()->getUser();
		 //获取请求消息
		 $applyer=$user->getUserName();
		 $recver=$request->get('recver');
		 $code=ReturnCode::$SUCCESS;
		 $msg='';
		 try{
			 //将jid转成邮箱帐号
			 //$applyer=$this->getAccountByJid($da,$applyer);
			 $recver=$this->getAccounts($da,explode(',',$recver));
			 $sex=$user->sex_id;
	 		 $duty=$user->duty;
	 		 $mobile=$user->mobile;
	 		 $vilidateinfo=$request->get('authren');
	 		 //获取最小同意数
	 		 $identify=new IdentifyAuth($da,$this->get('logger'),$this->container);
	 		 $n=$identify->getMinAreeNum($user->eno);
	 		 $ec=new \Justsy\BaseBundle\Controller\IdentifyAuthController();
	 		 $ec->setContainer($this->container);
   		 $return=$ec->SaveUserAuth($da,$applyer,$recver,$mobile,$n);
   		 if($return['s']=='0')
   		 {
   			$code=ReturnCode::$SYSERROR;
   			$msg=$return['m'];
   		 }
		}
		catch(\Exception $e)
		{
			$this->get('logger')->err($e);
			$code=ReturnCode::$SYSERROR;
			$msg="系统错误";
		}
		$response = new Response(json_encode(array('returncode'=> $code,'msg'=> $msg)));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
	}
	public function getAuthUsersAction()
	{
		$code=ReturnCode::$SUCCESS;
		$rows=array();
		$pagecount=0;
		try{
			$da = $this->get('we_data_access');
		 	$request = $this->getRequest();
		 	$user=$this->get('security.context')->getToken()->getUser();
		 	$pagesize=(int)($request->get('pagesize',20));
	    $da->PageSize  =$pagesize;
			$da->PageIndex = $request->get('pageindex')?$request->get('pageindex')-1:0;
		 	$eno=$user->eno;
		 	$account=$user->getUserName();
			$sql="select a.login_account,a.nick_name,fafa_jid,openid,
      a.photo_path,a.photo_path_small,a.photo_path_big,c.eshortname
      from we_staff a left join we_enterprise c on a.eno=c.eno where a.eno=? and a.login_account!=? and a.auth_level<>? and not exists(select 1 from we_micro_account b where b.number=a.login_account)";
    	$ds = $da->GetData('we_staff',$sql,array((string)$eno,(string)$account,Identify::$SIdent));
    	$rows=$ds['we_staff']['rows'];
    	$pagecount=ceil($ds['we_staff']['recordcount']/$pagesize);
		}
		catch(\Exception $e)
		{
			$this->get('logger')->err($e);
			$rows=array();
    	$code=ReturnCode::$SYSERROR;
		}
		$re=array('returncode'=> $code,'rows'=> $rows,'pagecount'=> $pagecount);
		$response=new Response(json_encode($re));
		$response->headers->set('Content-Type','Application/json');
		return $response;
	}
	public function SaveEnoAuthAction()
	{
		try{
			$da = $this->get('we_data_access');
		 	$request = $this->getRequest();
		  $user=$this->get('security.context')->getToken()->getUser();
		  $fileid=$request->get('fileid');
		  $code=ReturnCode::$SUCCESS;
		  $msg='申请成功！';
		  if(!empty($fileid)){
			  //保存证件
			  $sql="update we_enterprise set credential_path=? where eno=?";
			  $params=array($fileid,$user->eno);
			  $da->ExecSQL($sql,$params);
			}
		  
		  $ec=new \Justsy\BaseBundle\Controller\IdentifyAuthController();
	 		$ec->setContainer($this->container);
   		$return=$ec->SaveEnoAuthAction();
   		$res=json_decode($return->getContent(),true);
   		if($res['s']=='0')
   		{
   			$code=ReturnCode::$SYSERROR;
   			$msg=$res['m'];
   		}
		}
		catch(\Exception $e)
		{
			$this->get('logger')->err($e);
			$code=ReturnCode::$SYSERROR;
   		$msg='系统错误！';
		}
		$response = new Response(json_encode(array('returncode'=> $code,'msg'=> $msg)));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
	}
	protected function getAccounts($da,$list){
		$rows=array();
		try{
			for( $i=0;$i< count($list);$i++ ){
				$login_account=$this->getAccountByKey($da,$list[$i]);
				if(!empty($login_account))
					array_push($rows,$login_account);
			}
			return $rows;
		}
		catch(\Exception $e){
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
}
?>