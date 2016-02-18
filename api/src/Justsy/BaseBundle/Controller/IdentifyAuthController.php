<?php
namespace Justsy\BaseBundle\Controller;

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
use Justsy\BaseBundle\Rbac\StaffRole;

class IdentifyAuthController extends Controller
{
	public function EnoAuthAction($network_domain)
	{
		$da = $this->get('we_data_access');
  	$request = $this->getRequest();
  	$user=$this->get('security.context')->getToken()->getUser();
  	$eno=$user->eno;
  	$identifyauth=new IdentifyAuth($da,$this->get('logger'));
  	$fileurl = $this->container->getParameter('FILE_WEBSERVER_URL');
  	$credential_path='';
  	$bool=$identifyauth->checkAuth($eno,'eno');
  	if($bool)
  	{
  		$result='';
  		
  		//取出当前企业的基本信息
  		$sql="select * from we_enterprise where eno=?";
  		$params=array($eno);
  		$ds=$da->Getdata('info',$sql,$params);
  		$row=$ds['info']['rows'][0];
  		
  		if(!empty($row['credential_path']))
  			$credential_path=$fileurl.$row['credential_path'];
  		
  		//取出行业类别信息
  		$sql="select * from we_industry_class order by classify_id asc";
  		$ds=$da->Getdata('classify',$sql);
  		$classify=$ds['classify']['rows'];
  		
  		//查询当前审核结果
  		$sql="select max(ifnull(result,'2')) as re from we_apply where recv_type='e' and account=?";
  		$params=array($user->eno);
  		$ds=$da->Getdata('res',$sql,$params);
  		if($ds['res']['recordcount']>0)
				$result=$ds['res']['rows'][0]['re'];
			$cur=$user->auth_level.$user->vip_level;
			$up="V".$user->vip_level;
  		return $this->render("JustsyBaseBundle:IdentifyAuth:eno_auth.html.twig",array('row'=> $row,'credential_path'=> $credential_path,'fileurl'=> $fileurl,'curr_network_domain'=> $network_domain,'result'=> $result,'classify'=> $classify,'cur'=>$cur,'up'=>$up));
  	}
  	else
  	{
  		return $this->redirect($this->generateUrl('JustsyBaseBundle_home'));//主页
  	}
	}
	public function UserAuthAction($network_domain)
	{
		$da = $this->get('we_data_access');
  	$request = $this->getRequest();
  	$user=$this->get('security.context')->getToken()->getUser();
  	$eno=$user->eno;
  	$login_account=$this->get('security.context')->getToken()->getUser()->getUserName();
  	$identifyauth=new IdentifyAuth($da,$this->get('logger'));
  	$bool=$identifyauth->checkAuth($login_account,'user');
  	//取出通过身份认证的所有员工总数
  	$sql="select count(*) as num from we_staff a where a.eno=? and a.auth_level!=? and not exists(select 1 from we_micro_account b where b.number=a.login_account)";
  	$params=array($eno,Identify::$SIdent); 
  	$ds=$da->Getdata('num',$sql,$params);
  	$num=$ds['num']['rows'][0]['num'];
  	//获取已同意数
  	$sql="select count(1) as agreenum from we_apply where account=? and recv_type='p' and result='1'";
  	$params=array($user->getUserName());
  	$ds=$da->Getdata('agreenum',$sql,$params);
  	$agreenum=$ds['agreenum']['rows'][0]['agreenum'];
  	if($num >=5)
  		$n=3;
  	else
  		$n=1;
  	if($bool)
  	{
  		$al=$user->auth_level;
  	  $el = (int)$user->vip_level;
  	  $ev=$user->eno_level;
  	  $up = "";
  	  $cur = "";
			$cur=$al.$el;
			$up=$ev.$el; 		
  		$fileurl = $this->container->getParameter('FILE_WEBSERVER_URL');
			return $this->render("JustsyBaseBundle:IdentifyAuth:user_auth.html.twig",array('curr_network_domain'=> $network_domain,'mobile_bind'=> $user->mobile_bind,'cur'=> $cur,'up'=> $up,'m'=>($n-$agreenum),'n'=> $n,'nick_name'=> ($user->nick_name),'path'=> $user->photo_path,'fileurl'=>$fileurl));
		}
		else{
			return $this->redirect($this->generateUrl('JustsyBaseBundle_home'));//主页
		}
	}
	public function checkAuthAction()
	{
		$da = $this->get('we_data_access');
  	$request = $this->getRequest();
  	$user=$this->get('security.context')->getToken()->getUser();
  	$login_account=$this->get('security.context')->getToken()->getUser()->getUserName();
  	$eno=$user->eno;
  	$identifyauth=new IdentifyAuth($da,$this->get('logger'));
  	$enoauth=$identifyauth->checkAuth($eno,'eno');
  	$userauth=$identifyauth->checkAuth($login_account,'user');
  	$response = new Response(json_encode(array('eno'=> $enoauth ?'1':'0','user'=> $userauth ?'1':'0')));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
	}
	public function getPostMembersAction()
	{
		$rows = array();
    $da = $this->get('we_data_access');
    $user=$this->get('security.context')->getToken()->getUser();
    $eno = $user->eno;
    $account = $this->get('security.context')->getToken()->getUser()->getUserName();
    $page = $this->get('request')->request->get('page');
    if(empty($page))
    	$page=1;
    $rows=$this->getEnoMembers($eno,$account,((int)$page-1)*10);
		
		$cnt=count($rows);
    $re['page'] =$page;
    $re['cnt'] = count($rows);
    $re['json'] = $rows;
    $response = new Response(json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
	}
	public function getSelectedMembersAction()
	{
		$da = $this->get('we_data_access');
		$request = $this->getRequest();
		$da->PageSize  =$request->get('pagesize',10);
	  $da->PageIndex = $request->get('pageindex')?$request->get('pageindex')-1:0;
    $user=$this->get('security.context')->getToken()->getUser();
    $ds=$this->getSelectedMembers($da,$user->getUserName());
    $re['page'] = ceil($ds['select']['recordcount']/10);
    $re['json'] = $ds['select']['rows'];
    $response = new Response(json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
	}
	public function getSelectedMembers($da,$login_account)
	{
		try{
			$fileurl = $this->container->getParameter('FILE_WEBSERVER_URL');
			$sql="select max(case when a.result is null or a.result='' then '2' else a.result end) as result,b.login_account,a.recv_id,b.nick_name,b.fafa_jid,
	      concat('".$fileurl."',case trim(ifnull(b.photo_path,'')) when '' then null else b.photo_path end) as photo_path 
	from we_apply a 
	left join we_staff b on 
	b.login_account=a.recv_id 
	where a.account=? and recv_type='p' group by a.recv_id";
			$params=array($login_account);
			$ds=$da->Getdata('select',$sql,$params);
			return $ds;
		}
		catch(\Exception $e)
		{
			$this->get('logger')->err($e);
			return array('recordcount'=>0,'rows'=> array());
		}
	}
	public function getEnoMembers($eno, $account, $page)
  {
    $members = array();
    $da = $this->get('we_data_access');
    $fileurl = $this->container->getParameter('FILE_WEBSERVER_URL');
    $sql = "select a.login_account,a.nick_name,fafa_jid,
      concat('".$fileurl."',case trim(ifnull(a.photo_path,'')) when '' then null else a.photo_path end) as photo_path
      from we_staff a where a.eno=? and a.login_account!=? and a.auth_level<>? and not exists(select 1 from we_micro_account b where b.number=a.login_account) limit $page,10";
    $ds = $da->GetData('we_staff',$sql,array((string)$eno,(string)$account,Identify::$SIdent));
    if ($ds && $ds['we_staff']['recordcount']>0)
    {
      $members = $ds['we_staff']['rows'];
    }
    return $members;
  }
  public function SaveEnoAuthAction()
  {
  	$da = $this->get('we_data_access');
  	$request = $this->getRequest();
  	$user=$this->get('security.context')->getToken()->getUser();
  	$eno=$user->eno;
  	$account = $this->get('security.context')->getToken()->getUser()->getUserName();
  	$ename=$request->get('ename');
		$eshortname=$request->get('eshortname');
		$industry=$request->get('industry');
		$phone=$request->get('phone');
		$addr=$request->get('addr');
		$mobile=$request->get('mobile');
		$fax=$request->get('fax');
		$website=$request->get('website');
		//$filedata=$request->get('certificate');
		//$filename=$_FILES['certificate']['name'];
		$re=array('s'=>'1','m'=>'');
		//数据校验
		//$re=$this->vilidate();
		if($re['s']=='1'){
			//证件保存
			//$fileid=$this->saveCertificate($filedata,$filename);
//			if(empty($fileid))
//			{
//				$re=array('s'=>'0','m'=>'有效证件提交失败!');
//			}
			//判断是否有证件
			$sql1="select 1 from we_enterprise where eno=? and eno_level=?";
			$params1=array($user->eno,Identify::$MIdent);
			$ds=$da->Getdata('path',$sql1,$params1);
			if($ds['path']['recordcount']==0)
			{
				$re=array('s'=>'0','m'=>'您的企业不需要认证！');
			}
			if($re['s']=='1'){
				//判断是否有证件
				$sql1="select 1 from we_enterprise where eno=? and credential_path is not null and credential_path!=''";
				$params1=array($user->eno);
				$ds=$da->Getdata('path',$sql1,$params1);
				if($ds['path']['recordcount']==0)
				{
					$re=array('s'=>'0','m'=>'请先上传证件！');
				}
				//保存企业认证信息
				if($re['s']=='1'){
					//$sql[]="update we_enterprise set industry=?,addr=?,telephone=?,fax=?,ewww=? where eno=?";
					//$params[]=array($industry,$addr,$phone,$fax,$website,$eno);
					//保存申请到申请表中
					$apply_id=SysSeq::GetSeqNextValue($da,"we_apply","id");
					$sql[]="insert into we_apply (id,account,recv_type,recv_id,content,is_valid,apply_date) values(?,?,?,null,null,?,now())";
					$params[]=array($apply_id,$user->eno,'e','1');
					if(!$da->ExecSQLs($sql,$params))
					{
						$re=array('s'=>'0','m'=>'认证信息提交失败!');
					}
					if($re['s']=='1'){
						//通知运营人员
		        $resd= new \Justsy\BaseBundle\Management\PromptlyNotice($da,$this->get('logger'));
		        Utils::sendImMessage('',implode(',',$resd->getRecv('0002')),"企业身份认证",$user->nick_name."创建的企业【".$user->ename."】请求通过企业身份认证,请及时处理。",$this->container,"","",true,Utils::$systemmessage_code);
		      }
				}
			}
		}
		$response = new Response(json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  public function getNMembersAction()
  {
    $da = $this->get('we_data_access');
    $request = $this->getRequest();
    $da->PageSize  =$request->get('pagesize',10);
	  $da->PageIndex = $request->get('pageindex')?$request->get('pageindex')-1:0;
	  $user=$this->get('security.context')->getToken()->getUser();
	  $eno=$user->eno;
    $fileurl = $this->container->getParameter('FILE_WEBSERVER_URL');
    $sql = "select a.login_account,a.nick_name,fafa_jid,
      concat('".$fileurl."',case trim(ifnull(a.photo_path,'')) when '' then null else a.photo_path end) as photo_path
      from we_staff a where a.eno=? and a.auth_level=? and not exists(select 1 from we_micro_account b where b.number=a.login_account)";
    $ds = $da->GetData('we_staff',$sql,array((string)$eno,Identify::$MIdent));
    $re['page'] = ceil($ds['we_staff']['recordcount']/10);
    $re['json'] = $ds['we_staff']['rows'];
    $re['count']=$ds['we_staff']['recordcount'];
    $response = new Response(json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  public function saveCrenditialAction()
  {
  	$da = $this->get('we_data_access');
  	$request = $this->getRequest();
  	$user=$this->get('security.context')->getToken()->getUser();
		$filename=$_FILES['uploadfile']['name'];
		$filesize=$_FILES['uploadfile']['size'];
		$filetemp=$_FILES['uploadfile']['tmp_name'];
		$re=array('s'=>'1','m'=>'');
		if((int)$filesize > 1024*1024)
		{
			$re=array('s'=>'0','m'=>'上传的证件不能大于1M！');
		}
		if($re['s']=='1'){
			$fileid=$this->saveCertificate($filetemp,$filename);
			if(empty($fileid))
			{
				$re=array('s'=>'0','m'=>'有效证件提交失败!');
				//$fileid="523fe22a7d274a2d01000000";
			}
			if($re['s']=='1')
			{
				$sql="update we_enterprise set credential_path=? where eno=?";
				$params=array($fileid,$user->eno);
				if(!$da->ExecSQL($sql,$params))
				{
					$re=array('s'=>'0','m'=>'有效证件提交失败!');
				}
				else{
					$re['file']=array('filename'=> $filename,'filepath'=> $fileid);
				}
			}
		}
		$response = new Response(json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  public function saveLogAction()
  {
  	$da = $this->get('we_data_access');
  	$request = $this->getRequest();
  	$user=$this->get('security.context')->getToken()->getUser();
  	$fileurl = $this->container->getParameter('FILE_WEBSERVER_URL');
  	$session = $this->get('session');
  	$re=array('s'=>1,'m'=>'','file'=>'');
  	try{
	  	$filename120 = $session->get("avatar_big"); 
	  	$filename48 = $session->get("avatar_middle"); 
	  	$filename24 = $session->get("avatar_small");
	  	$file_big=$this->savefile($filename120);
	  	$file_middle=$this->savefile($filename48);
	  	$file_small=$this->savefile($filename24);
	  	$session->remove("avatar_big");
     	$session->remove("avatar_middle");
     	$session->remove("avatar_small");
	  	//跟新头像
	  	$sql="update we_staff set photo_path=?,photo_path_small=?,photo_path_big=? where login_account=?";
	  	$params=array($file_middle,$file_small,$file_big,$user->getUserName());
	  	if(!$da->ExecSQL($sql,$params))
	  	{
	  		$re=array('s'=>0,'m'=>'上传失败','file'=>'');	  		
	  	}
	  	else{
	  		$re=array('s'=>1,'m'=>'','file'=> $file_big);
	  	}
	  }
	  catch(\Exception $e)
	  {
	  	$re=array('s'=>0,'m'=>'上传失败','file'=>'');
	  	$this->get("logger")->err($e);
	  }
	  $response = new Response(json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;	
  }
  public function SaveUserAuthAction()
  {
  	$da = $this->get('we_data_access');
  	$request = $this->getRequest();
  	$user=$this->get('security.context')->getToken()->getUser();
  	$account = $this->get('security.context')->getToken()->getUser()->getUserName();
  	$nick_name=$request->get('realname');
  	$sex=$request->get('sex');
  	$duty=$request->get('duty');
  	$mobile=$request->get('mobile');
  	$vilidateinfo=$request->get('vilidateinfo');
  	$postto=$request->get('applyto');
  	$n=$request->get('num');
  	$re=array('s'=>'1','m'=>'');
  	//数据校验
  	//$re=$this->vilidate2();
  	if($re['s']=='1')
  	{
  		try{
	  		//判断用户是否已提交过申请,申请3天过后失效
	  		//$re=$this->checkApply($da,$account);
	  		//判断是否已绑定手机号
	  		$sql="select mobile_bind from we_staff where login_account=?";
	  		$params=array($user->getUserName());
	  		$ds=$da->Getdata('bind',$sql,$params);
	  		if($ds['bind']['recordcount']==0)
	  		{
	  			$re=array('s'=>0,'m'=>'请先绑定手机号');
	  		}
	  		else{
	  			$mobile=$ds['bind']['rows'][0]['mobile_bind'];
	  		}
	  		if($re['s']=='1'){
		  		//保存用户信息
//		  		$sql="update we_staff set sex_id=?,mobile=?,duty=? where login_account=?";
//		  		$params=array($mobile$account);
//		  		$da->ExecSQL($sql,$params);
		  		
		  		//处理认证请求
		  		$re=$this->SaveUserAuth($da,$account,explode(',',$postto),$mobile,$n);
			  }
			}
			catch(\Exception $e)
			{
				$this->get('logger')->err($e);
				$re=array('s'=>0,'m'=>'系统错误');
			}
  	}
  	$response = new Response(json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  //处理用户认证请求
	public function SaveUserAuth($da,$account,$postto,$mobile,$n=1)
	{
		$re=array('s'=>'1','m'=>'');
		try{
			$user=$this->get('security.context')->getToken()->getUser();
			//保存认证信息
			$content=$n;
			$l=$postto;
			$sendarr=array();
			$sendbnt=array();
			//最少同意数跟新
			$sql[]="update we_apply set content=? where account=? and recv_type='p'";
			$params[]=array($n,$account);
			for($i=0;$i<count($l);$i++){
				//判断是否需要
				$sql1="select id from we_apply where account=? and recv_type='p' and recv_id=? and (is_valid='1' or (is_valid='0' and result='1'))";
				$params1=array($account,$l[$i]);
				$ds=$da->Getdata('count',$sql1,$params1);
				if($ds['count']['recordcount']==0){
		  		$apply_id=SysSeq::GetSeqNextValue($da,"we_apply","id");
		  		$sql[]="insert into we_apply (id,account,recv_type,recv_id,content,is_valid,apply_date) values(?,?,?,?,?,?,now())";
		  		$params[]=array($apply_id,$account,'p',$l[$i],$n,'1');
		  		
		  		//写入消息
		  		$msg_id=SysSeq::GetSeqNextValue($da,"we_message","msg_id");
		  		$msg=$this->getauthmsg($apply_id,$user);
		  		$sql[]="insert into we_message (msg_id,sender,recver,title,content,send_date) values(?,?,?,?,?,now())";
		  		$params[]=array($msg_id,$account,$l[$i],'用户认证协助审核',$msg);
		  		
		  		$sendarr[]=$l[$i];
		  		//群发即时消息
		  		$identify=new IdentifyAuth($da,$this->get('logger'),$this->container);
					$buttons=$identify->getVerifyButton($apply_id);
					$sendbnt[]=$buttons;
		  	}
		  	else{
		  		/*
		  		$apply_id=$ds['count']['rows'][0]['id'];
		  		//写入消息
		  		$msg_id=SysSeq::GetSeqNextValue($da,"we_message","msg_id");
		  		$msg=$this->getauthmsg($apply_id,$user);
		  		$sql[]="insert into we_message (msg_id,sender,recver,title,content,send_date) values(?,?,?,?,?,now())";
		  		$params[]=array($msg_id,$account,$l[$i],'用户认证协助审核',$msg);
		  		
		  		$sendarr[]=$l[$i];
		  		//群发即时消息
		  		$identify=new IdentifyAuth($da,$this->get('logger'),$this->container);
					$buttons=$identify->getVerifyButton($apply_id);
					$sendbnt[]=$buttons;
					*/
		  	}
	  	}
	  	if(!$da->ExecSQLs($sql,$params))
	  	{
	  		$re=array('s'=>'0','m'=>'认证信息保存失败');
	  	}
	  	else{
	  		for($j=0;$j<count($sendarr);$j++)
	  		{
	  			Utils::sendImMessage('',implode(',',$this->getOpenid($da,array($sendarr[$j]))),"用户身份认证",$user->nick_name."(邮箱:".$user->getUserName().",手机:".$mobile.")邀请您协助身份认证。",$this->container,"",$sendbnt[$j],true,Utils::$systemmessage_code);
	  		}
	  	}
	  }
	  catch(\Exception $e)
	  {
	  	$this->get('logger')->err($e);
	  	return array('s'=>'0','m'=>'系统错误');
	  }
	  return $re;
	}
  protected function checkApply($da,$account)
  {
  	$sql="select 1 as num from we_apply where account=? and recv_type='p' and (is_valid='1' and apply_date > (now()-interval 3 day))";
  	$params=array($account);
  	$ds=$da->Getdata('count',$sql,$params);
  	if($ds['count']['recordcount']>0)
  		return array('s'=>0,'m'=>'已提交申请,请等待审核结果。');
  	else
  	  return array('s'=>1,'m'=>'');
  }
  protected function getOpenid($da,$arr)
  {
  	$sql2="select openid from we_staff where login_account in (";
		$params2=array();
		for($i=0;$i< count($arr);$i++)
		{
			$sql2.="?,";
			array_push($params2,$arr[$i]);
		}
		$sql2=rtrim($sql2,',').")";
		$ds=$da->Getdata('openid',$sql2,$params2);
		if($ds['openid']['recordcount']>0)
		{
			$re=array();
			foreach($ds['openid']['rows'] as $row)
			{
				$re[]=$row['openid'];
			}
			return $re;
		}
  }
  protected function getauthmsg($apply_id,$user)
  {
  	$account=$user->getUserName();
  	$duty=$user->duty;
  	$nick_name=$user->nick_name;
  	$mobile=$user->mobile_bind;
  	$agree_url=$this->generateUrl("JustsyBaseBundle_identify_user_agree")."?apply_id=".$apply_id."&re=1";
  	$confict_url=$this->generateUrl("JustsyBaseBundle_identify_user_agree")."?apply_id=".$apply_id."&re=0";
  	$msg="<p>尊敬的用户,<a class='employee_name post_staffname' login_account='".$account."'>".$nick_name."</a>申请加入您的企业，邀请您协助审核该用户的身份。<p>";
  	$msg.="<p>该用户的申请资料如下:</p>";
  	$msg.="<div><p>姓名:".$nick_name."</p>";
  	$msg.="<p>职务:".$duty."</p>";
  	$msg.="<p>手机号:".$mobile."</p>";
  	$msg.="</div>";
  	$msg.="<p>若您已经看过以上的审核资料，现在就可以审核了。</p>";
  	$msg.="<p><a target='_blank' href='".$agree_url."'>同意</a><a style='margin-left:20px;' target='_blank' href='".$confict_url."'>拒绝</a></p>";
  	return $msg;
  }
  public function UserAgreeAction()
  {
  	$da = $this->get('we_data_access');
  	$da_im = $this->get('we_data_access_im');
    $logger = $this->get('logger');
  	$request = $this->getRequest();
  	$apply_id=$request->get('apply_id');
  	$re=$request->get('re');
  	$user=$this->get('security.context')->getToken()->getUser();
  	$r=array('s'=>1,'m'=>'');
  	try{
  		//验证是否能审核
  		$sql="select result,is_valid from we_apply where id=?";
  		$params=array($apply_id);
  		$ds=$da->Getdata('info',$sql,$params);
  		if($ds['info']['recordcount']==0)
  		{
  			$r=array('s'=>0,'m'=>'该条审核记录已失效');
  		}
  		else{
  			if($ds['info']['rows'][0]['is_valid']=='0')
  			{
  				$r=array('s'=>0,'m'=>'你已经审核过了！');
  			}
  		}
  		if($r['s']=='1'){
			  	//同意人数
			  	$sql="select a.content,a.account,b.openid,b.fafa_jid from we_apply a left join we_staff b on b.login_account=a.account where a.id=?";
			  	$params=array($apply_id);
			  	$ds=$da->Getdata('content',$sql,$params);
			  	if($ds['content']['recordcount']>0)
			  	{
			  		$content=$ds['content']['rows'][0]['content'];
			  		$account=$ds['content']['rows'][0]['account'];
			  		$acc_openid=$ds['content']['rows'][0]['openid'];
			  		$acc_jid=$ds['content']['rows'][0]['fafa_jid'];
			  		$num=(int)$content;
			  		$sql2[]="select 1 from we_apply where account=? and recv_type='p' and result='1'";
			  		$sql2[]="select distinct recv_id from we_apply where account=? and recv_type='p' and result='0'";
			  		$sql2[]="select distinct recv_id from we_apply where account=? and recv_type='p'";
			  		$params2[]=array($account);
			  		$params2[]=array($account);
			  		$params2[]=array($account);
			  		$ds=$da->GetDatas(array('agreenum','confictnum','allnum'),$sql2,$params2);
			  		$n=$ds['agreenum']['recordcount'];
			  		$m=$ds['confictnum']['recordcount'];
			  		$allnum=$ds['allnum']['recordcount'];
			  		if($re=='1')
			  		{
			  			$n++;
			  		}
			  		if($re=='0')
			  		{
			  			$m++;
			  		}
			  		//更新审核结果
				  	$sql1[]="update we_apply set result=?,is_valid='0' where id=? and (result is null or result='') and is_valid='1'";
				  	$params1[]=array($re,$apply_id);
			  		if($num<=$n)//审核通过人数已达到规定值,更改用户身份
			  		{
			  			$sql1[]="update we_staff set auth_level=? where login_account=?";
			  			$params1[]=array($user->eno_level,$account);
			  			
			  			if($user->edomain==$user->eno){
			  				$sql="select 1 from we_public_domain where LOCATE(domain_name,?)=0 and not exists(select 1 from we_enterprise where edomain=?)";
			  				$params=array($account,$this->getSubDomain($account));
			  				$ds=$da->Getdata('acc',$sql,$params);
			  				if($ds['acc']['recordcount']>0){
			  					$sql1[]="update we_enterprise set edomain=? where eno=?";
			  					$params1[]=array($this->getSubDomain($account),$user->eno);
			  					$sql1[]="update we_enterprise_stored set eno_mail=? where enoname=?";
			  					$params1[]=array($account,$user->ename);
			  					$sql1[]="update we_circle set network_domain=? where enterprise_no=?";
			  					$params1[]=array($this->getSubDomain($account),$user->eno);
			  				}
			  			}
			  			
			  			//写入消息
				  		$msg_id=SysSeq::GetSeqNextValue($da,"we_message","msg_id");
				  		$msg="您已通过身份认证,现在就可以正常使用Wefafa平台了！";
				  		$sql1[]="insert into we_message (msg_id,sender,recver,title,content,send_date) values(?,?,?,?,?,now())";
				  		$params1[]=array($msg_id,'sysadmin@fafatime.com',$account,'用户认证协助审核',$msg);
			  		}
			  	  if(($allnum-$m)< $num)//审核通过人数已达到规定值,更改用户身份
			  		{		  			
			  			//写入消息
				  		$msg_id=SysSeq::GetSeqNextValue($da,"we_message","msg_id");
				  		$msg="您的身份认证申请未通过审核。";
				  		$sql1[]="insert into we_message (msg_id,sender,recver,title,content,send_date) values(?,?,?,?,?,now())";
				  		$params1[]=array($msg_id,'sysadmin@fafatime.com',$account,'用户认证协助审核',$msg);
			    	}
			  		if(!$da->ExecSQLs($sql1,$params1))
		  			{
		  				$r=array('s'=>0,'m'=>'系统错误');
		  			}
		  			if($r['s']=='1'){
		  				//通知申请人审核结果
			  			//Utils::sendImMessage($user->openid,$acc_openid,"用户身份认证",$user->nick_name.($re=='1'?"通过了您的加入请求。":"拒绝了您的加入请求。"),$this->container,"","",true,Utils::$systemmessage_code);
		  				if($num<=$n){
		  					//同步权限到Rbac
					  		$staffRole=new StaffRole($da,$da_im,$this->get('logger'));
					  		/*
					  		if($user->vip_level!='0')
					  			$staffRole->UpdateStaffRoleByCode($account,(Identify::$SIdent).($user->vip_level),(Identify::$BIdent).($user->vip_level),$user->eno);
					  		else
					  			$staffRole->UpdateStaffRoleByCode($account,(Identify::$SIdent).($user->vip_level),(Identify::$MIdent).($user->vip_level),$user->eno);
					  		*/
					  		$staffRole->UpdateStaffRoleByCode($account,(Identify::$SIdent).($user->vip_level),($user->eno_level).($user->vip_level),$user->eno);
				  			//通知申请人权限已通过
				  			Utils::sendImMessage('',$acc_openid,"用户身份认证","您的身份认证申请已被审核通过。",$this->container,"","",true,Utils::$systemmessage_code);
				  			//发送出席
				  			Utils::sendImPresence('',$acc_jid,"用户身份认证","您的身份认证申请已被审核通过。",$this->container,"","",false,Utils::$eno_identify_auth);
				  		}
				  		else if(($allnum-$m)< $num)
				  		{
				  			//通知申请人权限已通过
				  			Utils::sendImMessage('',$acc_openid,"用户身份认证","您的身份认证申请未通过审核。",$this->container,"","",true,Utils::$systemmessage_code);
				  		}
			  		}
			  	}
	  	}
	  }
	  catch(\Exception $e)
	  {
	  	$this->get('logger')->err($e);
	  	$r=array('s'=>0,'m'=>'审核出现错误');
	  }
	  $desc="";
	  $title="";
  	if($r['s']=='0'){
  		$title="提交失败";
  		$desc=$r['m'];
  	}
  	else{
  		$title="提交成功";
  		$desc="您的审核结果已经提交成功，谢谢你的配合。";
  	}
	  $home=$this->container->getParameter('open_api_url');
  	return $this->render("JustsyBaseBundle:IdentifyAuth:verify_success.html.twig",array('desc'=> $desc,'home'=> $home,'title'=> $title));
  }
  public function apply_successAction()
  {
  	$da = $this->get('we_data_access');
  	$request = $this->getRequest();
  	$type=$request->get('type');
  	$desc="";
  	if($type=='eno')
  		$desc="您成功提交了企业认证申请，我们将在24小时之内对您的申请进行审核，请耐心等待。";
  	else
  		$desc="您成功提交了用户身份认证申请，我们会及时通知被选用户进行审核，请耐心等待。";
  	$home=$this->container->getParameter('open_api_url');
  	return $this->render("JustsyBaseBundle:IdentifyAuth:apply_success.html.twig",array('desc'=> $desc,'home'=> $home));
  }
  
  public function getCommentAction()
  {
  	  $StaffRole = new \Justsy\BaseBundle\Rbac\StaffRole($this->get('we_data_access'),$this->get('we_data_access_im'),$this->get("logger"));
  	  $user=$this->get('security.context')->getToken()->getUser();
  	  $al=$user->auth_level;
  	  $el = (int)$user->vip_level;
  	  $up = "";
  	  $cur = "";
  	  if($al=="J"){
  	  	 $up="N1"; 
  	  	 $cur="J1";
  	  	 if($el>0){
  	  	 	 $up="V".$el;
  	  	 	 $cur="J".$el;
  	  	 }else if($el<0)
  	  	 {
  	  	 	 $up="N".abs($el);
  	  	 	 $cur="J".abs($el);
  	  	 }
  	  }
  	  else if ($al=="N"){
  	  	 $up="V1"; 
  	  	 $cur="N1";
         if($el!=0){
  	  	 	 $up="V".abs($el);
  	  	 	 $cur="N".abs($el);
  	  	 }
  	  }
  	  else{
  	  	$up="V".($el+1);
  	  	$cur="V".$el;
  	  }
  	  $myfunc = $StaffRole->getFunctionCodes( $el>0?$cur: substr($cur,0,1)."1");//对于未认证的等级角色，固定取N1/J1的功能点
  	  $upfunc = $StaffRole->getFunctionCodes($el>0?$up : substr($up,0,1)."1");  //对于未认证的等级角色，固定取N1/J1的功能点
  	  return $this->render("JustsyBaseBundle:IdentifyAuth:auth_info_help.html.twig",array("up"=> $up,"curFunc"=> $myfunc,"upFunc"=> $upfunc));
  }
  
  protected function savefile($path)
  {
  	try{
	  	$dm = $this->get('doctrine.odm.mongodb.document_manager');
	  	$doc = new \Justsy\MongoDocBundle\Document\WeDocument();
	    $doc->setName(basename($path));
	    $doc->setFile($path);
	    $dm->persist($doc);
	    $dm->flush();
	    unlink($path);
	    return $doc->getId();
	  }
	  catch(\Exception $e)
	  {
	  	$this->get('logger')->err($e);
	  	return '';
	  }
  }
  protected function saveCertificate($filetemp,$filename)
  {
  	try{
	  	$upfile = tempnam(sys_get_temp_dir(), "we");
	    unlink($upfile);
	    /*
	    $somecontent1 = base64_decode($filedata);
	    if ($handle = fopen($upfile, "w+")) {   
	      if (!fwrite($handle, $somecontent1) == FALSE) {   
	        fclose($handle);  
	      }  
	    }
	    */
	    if(move_uploaded_file($filetemp,$upfile)){
		    $dm = $this->get('doctrine.odm.mongodb.document_manager'); 
		    $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
		    $doc->setName($filename);
		    $doc->setFile($upfile); 
		    $dm->persist($doc);
		    $dm->flush();
		    $fileid = $doc->getId();
		    return $fileid;
		  }
		  else{
		  	return "";
		  }
	  }
	  catch(\Exception $e)
	  {
	  	$this->get('logger')->err($e);
	  	return "";
	  }
  }
  protected function getSubDomain($account)
  {
    $re = '';
    $tmp = explode("@",$account);
    if (count($tmp) > 1) $re = $tmp[1];
    return $re;
  }
  protected function vilidate2()
  {
  	$request = $this->getRequest();
  	$nick_name=$request->get('realname');
  	$sex=$request->get('sex');
  	$deptname=$request->get('deptname');
  	$mobile=$request->get('mobile');
  	$vilidateinfo=$request->get('vilidateinfo');
  	$postto=$request->get('postto');
  	if(preg_match("/^(1[8|3|5][0-9]|15[0|3|6|7|8|9]|18[2|5|6|8|9])\d{8}$/",$mobile)!=1)
  	{
  		return array('s'=>'0','m'=>'手机号格式不正确');
  	}
//  	if(preg_match("//",$postto))
//  	{
//  		return array('s'=>'0','m'=>'邀请人至少需要选择三位');
//  	}
  	return array('s'=>'1','m'=>'');
  }
  protected function vilidate()
  {
  	$request = $this->getRequest();
		$phone=$request->get('phone');
		$fax=$request->get('fax');
		$website=$request->get('website');
		
		if(preg_match("((\d{11})|^((\d{7,8})|(\d{4}|\d{3})-(\d{7,8})|(\d{4}|\d{3})-(\d{7,8})-(\d{4}|\d{3}|\d{2}|\
d{3}|\d{2}|\

d{1})|(\d{7,8})-(\d{4}|\d{3}|\d{2}|\d{1}))$)",$phone)!=1)
		{
			return array('s'=>'0','m'=>'电话格式错误!');
		}
		if(!empty($fax) && preg_match("/^[+]{0,1}(\d){1,3}[ ]?([-]?((\d)|[ ]){1,12})+$/",$fax)!=1)
		{
			return array('s'=>'0','m'=>'传真格式不正确!');
		}
//		if(preg_match("(http|ftp|https):\/\/[\w\-_]+(\.[\w\-_]+)+([\w\-\.,@?^=%&amp;:/~\+#]*[\w\-\@?^=%&amp;/~\+#])?",$website)!=1)
//		{
//			return array('s'=>'0','m'=>'网址格式不正确!');
//		}
		return array('s'=>'1','m'=>'');
  }
}
?>