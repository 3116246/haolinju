<?php
namespace Justsy\InterfaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Management\Identify;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Controller\InviteController;

class CircleController extends Controller
{
	public function getRecomCircleAction()
	{
		$user = $this->get('security.context')->getToken()->getUser();
    $request = $this->getRequest();
      
    $da = $this->get('we_data_access');
    $da->PageSize  =$request->get('pagesize',4);
		$da->PageIndex = $request->get('pageindex')?$request->get('pageindex')-1:0;
		$circlename=$request->get('circlename');
    $code=ReturnCode::$SUCCESS;
    $arr=array();
    try{
	    $sql = "select a.circle_id, a.circle_name,a.circle_desc, a.logo_path_small,(select count(1) from we_circle_staff c where c.circle_id=a.circle_id) as staff_num,a.logo_path_big,a.create_date,a.logo_path, a.create_staff,
	b.nick_name,b.photo_path,b.photo_path_small,b.photo_path_big,d.eshortname ,a.circle_class_id,f.classify_name
	from we_circle a 
	left join we_staff b on b.login_account=a.create_staff
	left join we_enterprise d on d.eno=b.eno 
	left join we_circle_class f on a.circle_class_id=f.classify_id
	where a.enterprise_no is null and a.join_method='0'
	  and not exists(select 1 from we_circle_staff wcs where wcs.circle_id=a.circle_id and wcs.login_account=?)
	  and not exists(select 1 from we_apply wa where wa.recv_type='c' and wa.recv_id=a.circle_id and wa.account=?)
	  and circle_recommend is not null ";
			$params=array($user->getUserName(),$user->getUserName());
			if(!empty($circlename))
			{
				$sql.=" and a.circle_name like ? ";
				array_push($params,"%".$circlename."%");
			}
			$sql.=" order by circle_recommend desc, a.create_date asc";
			$da = $this->get('we_data_access');
	    $ds = $da->GetData("we_circle", $sql, $params);
	    $rows=$ds['we_circle']['rows'];
	    for($i=0;$i< count($rows);$i++)
		  {
		  	$arr[$i]['circle_id']=$rows[$i]['circle_id'];
		  	$arr[$i]['circle_name']=$rows[$i]['circle_name'];
		  	$arr[$i]['logo_path_small']=$rows[$i]['logo_path_small'];
		  	$arr[$i]['logo_path']=$rows[$i]['logo_path'];
		  	$arr[$i]['logo_path_big']=$rows[$i]['logo_path_big'];
		  	$arr[$i]['create_staff']=$rows[$i]['create_staff'];
		  	$arr[$i]['create_date']=$rows[$i]['create_date'];
		  	$arr[$i]['staff_num']=$rows[$i]['staff_num'];
		  	$arr[$i]['classify_name']=$rows[$i]['classify_name'];
		  	$arr[$i]['circle_class_id']=$rows[$i]['circle_class_id'];
		  	//$arr[$i]['top']=$this->getTopByCircle($rows[$i]['circle_id']);
		  	$arr[$i]['staff']=array('login_account'=> $rows[$i]['create_staff'],
		  	'nick_name'=>$rows[$i]['nick_name'],
		  	'photo_path'=>$rows[$i]['photo_path'],
		  	'photo_path_small'=>$rows[$i]['photo_path_small'],
		  	'photo_path_big'=>$rows[$i]['photo_path_big'],
		  	'eshortname'=>$rows[$i]['eshortname']);
		  }
	  }
	  catch(\Exception $e)
	  {
	  	//var_dump($e->getMessage());
	  	$this->get('logger')->err($e);
	  	$code=ReturnCode::$SYSERROR;
			$arr=array();
	  }
    $re=array('returncode'=> $code,'rows'=> $arr);
    $response=new Response(json_encode($re));
	  $response->headers->set('Content-Type','Application/json');
	  return $response;
	}
	public function getTopByCircleAction(){
		$code=ReturnCode::$SUCCESS;
		$rows=array();
		try{
			$user = $this->get('security.context')->getToken()->getUser();
	    $request = $this->getRequest();
	   	$circle_id=$request->get('circle_id');
	   	$rows=$this->getTopByCircle($circle_id);
    }
    catch(\Exception $e)
    {
    	$this->get('logger')->err($e);
    	$code=ReturnCode::$SYSERROR;
    	$rows=array();
    }
    $re=array('returncode'=> $code,'top'=> $rows);
	  $response=new Response(json_encode($re));
		$response->headers->set('Content-Type','Application/json');
		return $response;
	}
	public function joinCircleAction()
	{
		$code=ReturnCode::$SUCCESS;
		$msg='';
		try{
			$user = $this->get('security.context')->getToken()->getUser();
	    $request = $this->getRequest();
	    //$circle_id=$request->get('circleId');
	    $circle=new \Justsy\BaseBundle\Controller\CircleController();
	    $circle->setContainer($this->container);
	    $reponses=$circle->applyJoinAction();
	    $res=$reponses->getContent();
	    if($res!='1')
	    {
	    	$msg='操作失败';
	    	if($res=='99999')$msg='所申请的圈子数已超过限制';
    		$code=ReturnCode::$SYSERROR;
    		if($res=='99999' || $res=='-2' || $res=='-3' || $res=='0')$code=ReturnCode::$OUTOFRANGE;
    		if($res=='-2')$msg='您所加入的圈子数已达到限制';
    		if($res=='-3')$msg='该圈子已满员';
    		if($res=='0')$msg='您之前已提交过加入申请，请耐心等待审核结果。';
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
	public function getTopByCircle($circle_id)
	{
		try{
	    $da = $this->get('we_data_access');
	    $da->PageSize  =-1;
	  	$da->PageIndex =-1;
	    $sql="select b.login_account,b.nick_name,b.photo_path,b.photo_path_small,b.photo_path_big,d.eshortname,d.ename from  we_circle a,we_circle_staff c, we_staff b left join we_enterprise d on d.eno=b.eno where a.circle_id=c.circle_id and b.login_account=c.login_account and a.circle_id=? order by b.prev_login_date desc limit 0,5";
	    $params=array($circle_id);
	    $ds=$da->Getdata('info',$sql,$params);
	    return $ds['info']['rows'];
    }
    catch(\Exception $e)
    {
    	$this->get('logger')->err($e);
    	return array();
    }
	}
	public function getCirclesAction()
	{
		$code=ReturnCode::$SUCCESS;
		$rows=array();
		$pagecount=0;
		try{
			$user = $this->get('security.context')->getToken()->getUser();
			$account=$user->getUserName();
	    $request = $this->getRequest();
	    $da = $this->get('we_data_access');
	    $pagesize=(int)($request->get('pagesize',20));
	    $da->PageSize  =$pagesize;
			$da->PageIndex = $request->get('pageindex')?$request->get('pageindex')-1:0;
			$circle_name=$request->get('circleName');
			$classify=$request->get('circleClass');
			$circle_id=$request->get('circleId');
			$isjoin=$request->get('isjoin');
	    $isapply=$request->get('isapply');
			$sql="select l.* from (select a.circle_id,circle_name,a.circle_desc,logo_path_big,logo_path,logo_path_small, 
      circle_recommend,a.fafa_groupid,a.create_staff,a.create_date,a.manager,a.join_method,a.enterprise_no,a.network_domain,case when a.allow_copy='1' then '1' else '0' end allow_copy,
      (select count(*) from we_circle_staff c where c.circle_id=a.circle_id) as staff_num,
      case ifnull(c.circle_id,'') when '' then '0' else '1' end as isjoin,a.circle_class_id,f.classify_name
      from we_circle a 
      left join we_circle_staff c on a.circle_id=c.circle_id and c.login_account=?
      left join we_circle_class f on a.circle_class_id=f.classify_id
      where enterprise_no is null and join_method='0' and 0+a.circle_id>10000 ".(empty($classify)?"":"and a.circle_class_id=? ").(empty($circle_name)?"":" and a.circle_name like ? ").(empty($circle_id)?"":" and a.circle_id=?").($isapply==''?"":($isapply=='1'?" and exists(select 1 from we_apply d where d.account=? and d.recv_id=a.circle_id and d.recv_type='c')":" and not exists(select 1 from we_apply d where d.account=? and d.recv_id=a.circle_id and d.recv_type='c')")).
      " order by circle_recommend , a.create_date)l ".($isjoin==''?"":" where isjoin=? ");
      $params=array((string)$account);
      if(!empty($classify))
       array_push($params,$classify);
      if(!empty($circle_name))
      	array_push($params,'%'.$circle_name.'%');
      if(!empty($circle_id))
      	array_push($params,$circle_id);
      if($isapply!=''){
      	array_push($params,$account);
      }
      if($isjoin!=''){
      	array_push($params,$isjoin);
      }
    	$ds = $da->GetData('we_circle',$sql,$params);
    	$rows=$ds['we_circle']['rows'];
    	$pagecount=ceil($ds['we_circle']['recordcount']/$pagesize);
		}
		catch(\Exception $e)
		{
			//var_dump($e->getMessage());
			$this->get('logger')->err($e);
			$rows=array();
    	$code=ReturnCode::$SYSERROR;
		}
		$re=array('returncode'=> $code,'rows'=> $rows,'pagecount'=> $pagecount);
		$response=new Response(json_encode($re));
		$response->headers->set('Content-Type','Application/json');
		return $response;
	}
	public function getMemberByCircleAction()
	{
		$code=ReturnCode::$SUCCESS;
		$rows=array();
		$pagecount=0;
		try{
			  $user = $this->get('security.context')->getToken()->getUser();
		    $request = $this->getRequest();
		    $da = $this->get('we_data_access');
		    $da_im = $this->get('we_data_access_im');
		    $PageSize  =$request->get('pagesize',20);
			  $PageIndex = $request->get('pageindex')?$request->get('pageindex')-1:0;
		    $circleId=$request->get('circleId');
		    $searchby=$request->get('searchby');
		    //总数
		    $sql_total = "";
		    //数据sql
		    $sql_data = "";

		    if($circleId=='9999'){
		  	  	$staffMgr=new \Justsy\BaseBundle\Management\Staff($da,$da_im,$user,$this->container->get("logger"),$this->container);
		  	  	$list=$staffMgr->getFriendJidList();
		  	  	$pagecount=ceil(count($list)/($PageSize));
		  	  	$rows = array();		  	  	
		  	  	if(!empty($list)){
			    	$sql_sql = "select B.nick_name,B.login_account,B.photo_path,B.photo_path_small,B.photo_path_big from we_staff B where B.login_account in ('".implode("','", $list)."') ";

			    	$sql_sql .= (empty($searchby)?"":(strlen($searchby)>mb_strlen($searchby,'utf8') ? " and B.nick_name like ? " : " and (B.nick_name like ? or B.login_account like ?)"));
		            $sql_sql .=" order by A.login_account";
		            $sql_sql .=" limit ".($PageIndex*$PageSize).",".($PageIndex*$PageSize+$PageSize);

		            $params=array($circleId);
		            if(!empty($searchby)){
		            	array_push($params,$searchby."%");
		            	if(strlen($searchby)==mb_strlen($searchby,'utf8'))
		            		array_push($params,$searchby."%");
		            }
		            $ds=$da->Getdata('info',$sql_sql,$params);
			    	$rows=$ds['info']['rows'];
		    	}
		    }
		    else{
		    	//根据jid获取sns ID
		    	//$sql = "select circle_id from we_circle where fafa_groupid=?";
		    	//$ds=$da->Getdata('circle',$sql,array((string)$circleId));
		    	//$circleId = $ds["circle"]["rows"][0]["circle_id"];
		    	
		    	$sql_sql = "select B.nick_name,B.login_account,B.photo_path,B.photo_path_small,B.photo_path_big from we_circle_staff A,we_staff B where A.login_account=B.login_account and A.circle_id=? ";

		    	$sql_sql .= (empty($searchby)?"":(strlen($searchby)>mb_strlen($searchby,'utf8') ? " and A.nick_name like ? " : " and (A.nick_name like ? or A.login_account like ?)"));
	            $sql_sql .=" order by B.login_account";
	            $sql_sql .=" limit ".($PageIndex*$PageSize).",".($PageIndex*$PageSize+$PageSize);

	            $sql_total = "select count(1) cnt from we_circle_staff where circle_id=?";
	            $sql_total .= (empty($searchby)?"":(strlen($searchby)>mb_strlen($searchby,'utf8') ? " and nick_name like ? " : " and (nick_name like ? or login_account like ?)"));

	            $params=array($circleId);
	            if(!empty($searchby)){
	            	array_push($params,$searchby."%");
	            	if(strlen($searchby)==mb_strlen($searchby,'utf8'))
	            		array_push($params,$searchby."%");
	            }
	           	$ds=$da->Getdata('info',$sql_sql,$params);
		    	$ds_total=$da->Getdata('total',$sql_total,$params);
		    	$pagecount=ceil($ds_total['total'][0]['cnt']/($PageSize));
		    	$rows=$ds['info']['rows'];
		    }

		}
		catch(\Exception $e)
		{
			//var_dump($e->getMessage());
			$this->get('logger')->err($e);
			$rows=array();
    	$code=ReturnCode::$SYSERROR;
		}
		$re=array('returncode'=> $code,'rows'=> $rows,'pagecount'=> $pagecount);
		$response=new Response(json_encode($re));
		$response->headers->set('Content-Type','Application/json');
		return $response;
	}

	public function createcircleAction(){
		$re = array("returncode" => ReturnCode::$SUCCESS);
		$user = $this->get('security.context')->getToken()->getUser();
	    $request = $this->getRequest();
	    $circlename=$request->get("circle_name");
	    $circledesc=$request->get("circle_desc");
	    $joinmethod=$request->get("join_method","0");
	    $allowcopy=$request->get("allow_copy","0");
	    $circleclassid=$request->get("circle_class_id");
	    $invitedmemebers=$request->get("invitedmemebers");
	    $logo_path=$request->get("logo_path");
	    $logo_path_small=$request->get("logo_path_small");
	    $logo_path_big=$request->get("logo_path_big");
	    $da = $this->get('we_data_access');
	    $da_im = $this->get('we_data_access_im');
	    try {
	    	if(empty($circlename)||empty($circleclassid))
	    	{
	    		$re["returncode"] = ReturnCode::$SYSERROR;
	    		$re["error"]="参数传递错误！";
				$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		    	$response->headers->set('Content-Type', 'text/json');
		    	return $response;
	    	}
	    	if(!$this->hasCircle($da,$circlename))
	    	{
				$ec=new \Justsy\BaseBundle\Management\EnoParamManager($da,$this->get('logger'));
				if($ec->IsBeyondCreateCircle($user->getUserName()))
				{
				    	$re["returncode"] = ReturnCode::$SYSERROR;
	    				$re["error"]="创建圈子数量已超过限制！";
				}else
				{
				    	$circle_id = (String)SysSeq::GetSeqNextValue($da,"we_circle","circle_id");
					    //创建圈子不再同步创建群组
					    //liling 2015-1-18
					    $fafa_groupid = "";// SysSeq::GetSeqNextValue($da_im,"im_group","groupid");
					    $network =$circle_id;
					    $sqls = array
					    (
					        "insert into we_circle(circle_id,circle_name,circle_desc,logo_path,logo_path_big,logo_path_small,create_staff,create_date,manager,join_method,network_domain,allow_copy,circle_class_id,fafa_groupid)value(?,?,?,?,?,?,?,now(),?,?,?,?,?,?)",
					        "insert into we_circle_staff(circle_id,login_account,nick_name)values(?,?,?)"
					    );
					    $paras = array
					    (
					        array((String)$circle_id,(String)$circlename,(String)$circledesc,(String)$logo_path,(string)$logo_path_big,(string)$logo_path_small,
					        	(String)$user->getUsername(),(String)$user->getUsername(),(String)$joinmethod,$network,(String)$allowcopy,
					        	(string)$circleclassid,(string)$fafa_groupid),
					        array((String)$circle_id,(String)$user->getUsername(),(String)$user->nick_name)
      					);
				        $da->ExecSQLs($sqls,$paras);
				        //创建文档根目录
				        $docCtl = new \Justsy\BaseBundle\Controller\DocumentMgrController();
				        $docCtl->setContainer($this->container);
				        if($docCtl->createDir("c".$circle_id,"",$circlename,$circle_id)>0)
				        {
				        	  $docCtl->saveShare("c".$circle_id,"0",$circle_id,"c","w");//将圈子目录共享给该圈子成员
				        }
				      //给创建者发送创建群组成功出席
				      //Utils::sendImPresence($user->fafa_jid,$user->fafa_jid,"creategroup",json_encode(array("groupid"=> $fafa_groupid,"groupname"=> $circlename)),$this->container,"","",false,Utils::$systemmessage_code);      
				      //发送邀请邮件
				      if(!empty($invitedmemebers))
				      {
				        $user = $this->get('security.context')->getToken()->getUser();
				        $invInfo = array(
				          'inv_send_acc' => $user->getUsername(),
				          'inv_recv_acc' => '',
				          'eno' => '',
				          'inv_rela' => '',
				          'inv_title' => '',
				          'inv_content' => '',
				          'active_addr' => '');
				        $invitedmemebers=str_replace("；",";",$invitedmemebers);
				        $invitedmemebersLst = explode(";",$invitedmemebers);
				        foreach($invitedmemebersLst as $key => $value)
				        {
				          $invacc = trim($value);
				          if (empty($invacc)) continue;
				          $invInfo['inv_recv_acc'] = $invacc;
				          $sql = "select eno,fafa_jid from we_staff where login_account=?";
				          $ds = $da->GetData("we_staff",$sql,array((string)$invacc));
				          //帐号存在
				          if ($ds && $ds['we_staff']['recordcount']>0)
				          {
				            //1.帐号存在，直接加入圈子
				            //受邀人员帐号,圈子id,邀请人帐号
				            $encode = DES::encrypt("$invacc,$circle_id,".$user->getUsername());
				            $activeurl = $this->generateUrl("JustsyBaseBundle_invite_agreejoincircle",array('para'=>$encode), true);
				            $rejectactiveurl = $this->generateUrl("JustsyBaseBundle_invite_refuse",array('para'=>$encode), true);
				            $txt = $this->renderView('JustsyBaseBundle:Invite:circle_invitation_msg.html.twig', array(
				              "ename" => $user->ename,
				              "nick_name" => $user->nick_name,
				              "activeurl" => $activeurl,
				              'circle_name' => $circlename,
				              'invMsg' => '',
				              'staff'=>array()));
				            $invInfo['eno'] = "c$circle_id";
				            $invInfo['inv_title'] = "邀请加入圈子【".Utils::makeCircleTipHTMLTag($circle_id, $circlename)."】";
				            $invInfo['inv_content'] = '';
				            $invInfo['active_addr'] = $activeurl;
				            //保存邀请信息
				            InviteController::saveWeInvInfo($da, $invInfo);
				            //发送即时消息
				            $fafa_jid = $ds['we_staff']['rows'][0]['fafa_jid'];
				            $message = Utils::makeHTMLElementTag('employee',$user->fafa_jid,$user->nick_name)."邀请您加入圈子【".Utils::makeHTMLElementTag('circle',$fafa_groupid,$circlename)."】";
				            $buttons = array();            
				            $buttons[]=array("text"=>"拒绝","code"=>"agree","value"=>"0","link"=> $rejectactiveurl);
				            $buttons[]=array("text"=>"立即加入","code"=>"agree","value"=>"1","link"=> $activeurl);
				            Utils::sendImMessage($im_sender,$fafa_jid,"邀请加入圈子",$message,$this->container,"",Utils::makeBusButton($buttons),false,Utils::$systemmessage_code);
				          }
				          else
				          {
				            //2.帐号不存在
				            $tmp = explode("@",$invacc);      
				            $tmp = (count($tmp) > 1) ? $tmp[1] : 'fafatime.com';
				            $sql = "select count(1) as cnt from we_public_domain where domain_name=?";
				            $ds = $da->GetData("we_public_domain",$sql,array((string)$tmp));
				            if ($ds && $ds['we_public_domain']['rows'][0]['cnt']==0)
				            {
				              //2.1企业邮箱
				              $sql = "select eno from we_enterprise where edomain=?";
				              $ds = $da->GetData("we_enterprise",$sql,array((string)$tmp));
				              if ($ds && $ds['we_enterprise']['recordcount']>0)
				              {
				                //2.1.1企业已创建 帐号,圈子id,企业edomain des encode
				                $eno = $ds['we_enterprise']['rows'][0]['eno'];
				                $encode = DES::encrypt($user->getUsername().",$circle_id,$eno");
				                $activeurl = $this->generateUrl("JustsyBaseBundle_active_inv_s1", array(
				                  'account' => DES::encrypt($invacc),
				                  'invacc' => $encode), true);
				              }
				              else
				              {
				                //2.1.2企业未创建
				                $sql = "insert into we_register (login_account,ename,credential_path,active_code,ip,email_type,first_reg_date,last_reg_date,register_date,state_id)"
				                  ." values (?,?,?,?,?,?,now(),now(),now(),'0')";
				                $para = array($invacc,'','',strtoupper(substr(uniqid(),3,10)),$_SERVER['REMOTE_ADDR'],'1');
				                $da->ExecSQL($sql,$para);
				                //发送邮件 帐号,圈子id,邀请发送者帐号,邀请人企业名 des encode
				                $encode = DES::encrypt("$invacc,$circle_id,".$user->getUserName().",".$user->ename);
				                $activeurl = $this->generateUrl("JustsyBaseBundle_active_reg_s1", array('account' => $encode), true);
				              }
				              //保存邀请信息 circleid保存到eno字段，以字母'c'开头
				              $invInfo['eno'] = "c$circle_id";
				              $title = $user->nick_name." 邀请您加入 ".Utils::makeCircleTipHTMLTag($circle_id , $circlename)." 协作网络";
				              $txt = $this->renderView('JustsyBaseBundle:Invite:circle_invitation.html.twig', array(
				                "ename" => $user->ename,
				                "nick_name" => $user->nick_name,
				                "activeurl" => $activeurl,
				                'circle_name' => $circlename,
				                'invMsg' => '',
				                'staff'=>array()));
				              $invInfo['inv_title'] = $title;
				              $invInfo['inv_content'] = $txt;
				              $invInfo['active_addr'] = $activeurl;
				              InviteController::saveWeInvInfo($da, $invInfo);
				              Utils::saveMail($da,$user->getUsername(),$invacc,$title,$txt,$invInfo['eno']);
				            }
				            else
				            {
				              //公共邮箱
				              $sql = "insert into we_register (login_account,ename,credential_path,active_code,ip,email_type,first_reg_date,last_reg_date,register_date,state_id) "
				                ."select ?,'','','".strtoupper(substr(uniqid(),3,10))."','".$_SERVER['REMOTE_ADDR']."','0',now(),now(),now(),'2' from dual "
				                ."where not exists (select 1 from we_register where login_account=?)";
				              $para = array($invacc,$invacc);
				              $da->ExecSQL($sql,$para);
				              //发送邮件 帐号,圈子id,邀请发送者帐号,邀请人企业名 des encode
				              $encode = DES::encrypt("$invacc,$circle_id,".$user->getUserName().",".$user->ename);
				              $activeurl = $this->generateUrl("JustsyBaseBundle_active_reg_s1",array('account'=>$encode),true);
				              $invInfo['eno'] = "c$circle_id";
				              $title = $user->nick_name." 邀请您加入 ".Utils::makeCircleTipHTMLTag($circle_id, $circlename)." 协作网络";
				              $txt = $this->renderView('JustsyBaseBundle:Invite:circle_invitation.html.twig', array(
				                "ename" => $user->ename,
				                "nick_name" => $user->nick_name,
				                "activeurl" => $activeurl,
				                'circle_name' => $circlename,
				                'invMsg' => '',
				                'staff'=> array()));
				              //保存邀请信息
				              $invInfo['inv_title'] = $title;
				              $invInfo['inv_content'] = $txt;
				              $invInfo['active_addr'] = $activeurl;
				              InviteController::saveWeInvInfo($da, $invInfo);
							  Utils::saveMail($da,$user->getUsername(),$invacc,$title,$txt,$invInfo['eno']);
				            }
				          }
				        }
				      }
				      $re["circle"]=$this->getCricle($da,$circle_id);			
				    }
	    	}else{
	    		$re["returncode"] = ReturnCode::$SYSERROR;
	    		$re["error"]="圈子名称已存在！";
	    	}
	    } catch (\Exception $e) {
	    	$re["returncode"] = ReturnCode::$SYSERROR;
	    	$re["error"]="系统错误，创建圈子失败！";
      		$this->get('logger')->err($e);
	    }
	    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    	$response->headers->set('Content-Type', 'text/json');
    	return $response;
	}

	public function getcircleinvitestaffAction(){
		$re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();
    $da = $this->get('we_data_access');
    $da->PageSize= $request->get('pagesize',30);
  	$da->PageIndex =$request->get('pageindex')?$request->get('pageindex')-1:0;
  	$search=$request->get("search");
  	$circleid = $request->get("circleid");
  	$params = array();
    if (!empty($circleid)){
    	$sql = "select a.login_account,a.nick_name,a.photo_path,a.photo_path_small,a.photo_path_big,
    	               case when b.login_account is null then 0 else 1 end incircle 
              from we_staff a left join we_circle_staff b on a.login_account=b.login_account and circle_id=? ";
    	array_push($params,(string)$circleid);
    }
    else{
    	$sql = "select a.login_account,a.nick_name,a.photo_path,a.photo_path_small,a.photo_path_big,0 incircle
              from we_staff a ";
    }	    
    $sql .= " where a.eno=? and a.login_account!=? and a.state_id<>'3' 
            and a.login_account not in('corp@fafatime.com','service@fafatime.com','sysadmin@fafatime.com') 
            and a.auth_level<>? and not exists(select 1 from we_micro_account b where b.number=a.login_account) ";
    array_push($params,(string)$user->eno);
    array_push($params,(string)$user->getUsername());	    
    array_push($params,(string)Identify::$SIdent);
    if(!empty($search))
    {
	    $array=explode("@",$search);
      if(strlen($search)==mb_strlen($search,"utf-8"))
      {
          if(!empty($array)&&count($array)>1)
          {
              $sql.=" and a.login_account like CONCAT('%',?,'%') ";
              $params[]=$search;
          }else
          {
              $sql.=" and (substring_index(a.login_account,'@',1) like CONCAT('%',?,'%')";
              $sql.=" or a.nick_name like CONCAT('%',?,'%') ) ";
							$params[]=$search;
							$params[]=$search;
          }
      }else
      {
          $sql.=" and a.nick_name like CONCAT('%',?,'%') ";
          $params[]=$search;
      }
  	}
    $sql.=" order by a.login_account";
    try {
    	$ds = $da->GetData("we_staff",$sql,$params);
    	$re["staffs"] = $ds["we_staff"]["rows"];
    } catch (\Exception $e) {
    	$re["returncode"] = ReturnCode::$SYSERROR;
    		$this->get('logger')->err($e);
    }
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
  	$response->headers->set('Content-Type', 'text/json');
  	return $response;
	}
	
	
	

	public function getcricletypeAction(){
		$re = array("returncode" => ReturnCode::$SUCCESS);
		$request = $this->getRequest();
		$sql = "select classify_id id,classify_name name,parent_classify_id parent_id from we_circle_class order by classify_order_by";	
        $da = $this->get('we_data_access');
        try {
        	$ds = $da->GetData("data",$sql);
	        $re["circle_types"] = $ds["data"]["rows"];
        } catch (\Exception $e) {
        	$re["returncode"] = ReturnCode::$SYSERROR;
      		$this->get('logger')->err($e);
        }
        $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    	$response->headers->set('Content-Type', 'text/json');
    	return $response;
	}

	public function modifycirclelogoAction(){
		$re = array("returncode" => ReturnCode::$SUCCESS);
	    $request = $this->getRequest();
	    $user = $this->get('security.context')->getToken()->getUser();
	    $dm = $this->get('doctrine.odm.mongodb.document_manager');
	    $da = $this->get("we_data_access");
	    $circleid=$request->get("circle_id");
	    // multipart/form-data
	    $filepath = $_FILES['filepath']['tmp_name'];
	    if(empty($filepath)){
	    	$filepath = tempnam(sys_get_temp_dir(), "we");
		    unlink($filepath);
		    $somecontent1 = base64_decode($request->get('filedata'));
		    if ($handle = fopen($filepath, "w+")) {
			    if (!fwrite($handle, $somecontent1) == FALSE) {   
			        fclose($handle);  
			    }
		    }
	    }

	    $filepath_24 = $filepath."_24";
	    $filepath_48 = $filepath."_48";
	    try 
	    {
	    	if(empty($circleid)||empty($filepath)){
				$re["returncode"] = ReturnCode::$SYSERROR;
	    		$re["error"]="参数传递错误！";
				$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		    	$response->headers->set('Content-Type', 'text/json');
		    	return $response;
	    	}
	      $im = new \Imagick($filepath);
	      $im->scaleImage(48, 48);
	      $im->writeImage($filepath_48);
	      $im->destroy();
	      $im = new \Imagick($filepath);
	      $im->scaleImage(24, 24);
	      $im->writeImage($filepath_24);
	      $im->destroy();
	      if (!empty($filepath)) $filepath = Utils::saveFile($filepath,$dm);
	      if (!empty($filepath_48)) $filepath_48 = Utils::saveFile($filepath_48,$dm);
	      if (!empty($filepath_24)) $filepath_24 = Utils::saveFile($filepath_24,$dm);
	      $sql="select logo_path,logo_path_small,logo_path_big,fafa_groupid,circle_name from we_circle where circle_id=?";
	      $table=$da->GetData("circle",$sql,array($circleid));
	      if($table && $table['circle']['recordcount']>0){
	      	Utils::removeFile($table['circle']['rows'][0]['logo_path'],$dm);
	        Utils::removeFile($table['circle']['rows'][0]['logo_path_small'],$dm);
	        Utils::removeFile($table['circle']['rows'][0]['logo_path_big'],$dm);
	      }
	      $sql="update we_circle set logo_path=?,logo_path_small=?,logo_path_big=? where circle_id=?";
	      $params=array($filepath_48,$filepath_24,$filepath,$circleid);
	      $da->ExecSQL($sql,$params);

        //取圈子成员
        $sql="select b.fafa_jid from we_circle_staff a left join we_staff b on a.login_account=b.login_account where a.circle_id=? and b.fafa_jid is not null";
        $para=array($circleid);
        $data=$da->GetData('dt',$sql,$para);
        if($data!=null && count($data['dt']['rows'])>0) 
        {
          //修改头像之后需要发出席消息。让各端及时修改头像
          $fafa_jid=$user->fafa_jid;
          $tojid=array();
          for ($i=0; $i < count($data['dt']['rows']); $i++) 
          	{ 
            	array_push($tojid, $data['dt']['rows'][$i]['fafa_jid']);
          	}
          if($table!=null && count($table['circle']['rows'])>0) 
          	{
		        $circlejid= $table["circle"]["rows"][0]["fafa_groupid"];
		        $circlename= $table["circle"]["rows"][0]["circle_name"];
		        $message=json_encode(array('circle_id'=>$circleid
		        ,'logo_path'=>$filepath
		        ,'circle_name'=>$circlename
		        ,'jid'=>$circlejid));
		        Utils::sendImMessage($fafa_jid,implode(",",$tojid),"circle_info_change",$message, $this->container,"","",false,Utils::$systemmessage_code);
          	}
        }
	      $re["returncode"] = ReturnCode::$SUCCESS;
	      $re["filepath"] = $filepath_48;
	      $re["filepath_small"] = $filepath_24;
	      $re["filepath_big"] = $filepath;
	    }
	    catch (\Exception $e) 
	    {
	      $re["returncode"] = ReturnCode::$SYSERROR;
	      $re["error"]="系统错误，创建圈子失败！";
	      $this->get('logger')->err($e);
	    }
	    
	    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
	    $response->headers->set('Content-Type', 'text/json');
	    return $response;
	}

	public function modifycircleAction(){
		$re = array("returncode" => ReturnCode::$SUCCESS);
		$user = $this->get('security.context')->getToken()->getUser();
		$request = $this->getRequest();
		$circle_id=$request->get('circle_id');
		$circle_name=$request->get('circle_name');
	  	$circle_desc=$request->get('circle_desc');
	  	$manager=$request->get('manager');
	    $join_method=$request->get('join_method');
	  	$allow_copy=$request->get('allow_copy');
	  	$circle_class_id=$request->get('circle_class_id');
        $da = $this->get('we_data_access');
        try {
        	if(empty($circle_id)){
				$re["returncode"] = ReturnCode::$SYSERROR;
	    		$re["error"]="参数传递错误！";
				$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		    	$response->headers->set('Content-Type', 'text/json');
		    	return $response;
	    	}
	    	$sql = "update we_circle set ";
    		$para = array();
    		if ($circle_name !== null)
		    {
		        $sql .= "circle_name=?,";
		        $para[] = $circle_name;
		    }
		    if ($circle_desc !== null)
		    {
		        $sql .= "circle_desc=?,";
		        $para[] = $circle_desc;
		    }
		    if ($manager !== null)
		    {
		        $sql .= "manager=?,";
		        $para[] = $manager;
		    }
		    if ($join_method !== null)
		    {
		        $sql .= "join_method=?,";
		        $para[] = $join_method;
		    }
		    if ($allow_copy !== null)
		    {
		        $sql .= "allow_copy=?,";
		        $para[] = $allow_copy;
		    }
		    if ($circle_class_id !== null)
		    {
		        $sql .= "circle_class_id=?,";
		        $para[] = $circle_class_id;
		    }
		    if (count($para) === 0) throw new \Exception("param is null");
		    $sql = substr($sql, 0, strlen($sql)-1);
		    $sql .= " where circle_id=?";
		    $para[] = $circle_id;
		    $sql2='select manager,fafa_groupid from we_circle where circle_id=?';
		  	$dataset2=$da->GetData('we_circle',$sql2,array((string)$circle_id));
		  	$fafa_groupid = $dataset2['we_circle']['rows'][0]['fafa_groupid'];
		  	$old_manager_array=array();
		  	$new_manager_array=array();
		  	if($dataset2['we_circle']['recordcount']>0)
		  	{
		  		$old_manager_array=explode(';',$dataset2['we_circle']['rows'][0]['manager']);
		  	}
		  	$new_manager_array=explode(';',$manager);
		  	$add_manager_array=array_diff($new_manager_array,$old_manager_array);  //新增管理员
		  	$del_manager_array=array_diff($old_manager_array,$new_manager_array);  //被取消了管理员
		    $dataexec=$da->ExecSQL($sql,$para);
		  	if($dataexec)
		  	{
		  		if(count($add_manager_array)>0||count($del_manager_array)>0)
		  		{
			  		$sqls=array(
			  		    'insert into we_message(msg_id,sender,send_date,title,content,isread,recver) values(?,?,CURRENT_TIMESTAMP(),?,?,?,?)',
			  		    'insert into we_notify(notify_type,msg_id,notify_staff) values(?,?,?)'
			  		);
			  		$login_account=$user->getUsername();
			  		$FAFA_CIRCLE_URL=$this->generateUrl('JustsyBaseBundle_enterprise_home',array('network_domain'=>$circle_id),true);
		        foreach($add_manager_array as $key=>$value)
		        {
		        	$msg_id = SysSeq::GetSeqNextValue($da, "we_message", "msg_id");
		        	$manager=$value;
				  		$title='您被设置为管理员';
				  		$content='您被设置为圈子'.'<a href="'.$FAFA_CIRCLE_URL.'">【'.$circle_name.'】</a>的管理员！';
				  		$paras=array(
				  		       array(
				  		             (string)$msg_id,
				  		             (string)$login_account,
				  		             (string)$title,
				  		             (string)$content,
				  		             '0',
				  		             (string)$manager
				  		       ),
				  		       array(
				  		             '02',
				  		             (string)$msg_id,
				  		             (string)$manager
				  		            )
				  		);
				    	$dataexec1=$da->ExecSQLs($sqls,$paras);
				    }
				    foreach($del_manager_array as $key=>$value)
				    {
				    	$msg_id = SysSeq::GetSeqNextValue($da, "we_message", "msg_id");
				    	$manager=$value;
				  		$title='您被取消了管理员';
				  		$content='您被取消了圈子'.'<a href="'.$FAFA_CIRCLE_URL.'">【'.$circle_name.'】</a>的管理员！';
				  		$paras=array(
				  		       array(
				  		             (string)$msg_id,
				  		             (string)$login_account,
				  		             (string)$title,
				  		             (string)$content,
				  		             '0',
				  		             (string)$manager
				  		       ),
				  		       array(
				  		             '02',
				  		             (string)$msg_id,
				  		             (string)$manager
				  		            )
				  		);
				    	$dataexec2=$da->ExecSQLs($sqls,$paras);
				    }
		  		}
		  	}
		  	$re["circle"]=$this->getCricle($da,$circle_id);
        } catch (\Exception $e) {
        	$re["returncode"] = ReturnCode::$SYSERROR;
        	$re["error"]="系统错误，创建圈子失败！";
      		$this->get('logger')->err($e);
        }
        $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    	$response->headers->set('Content-Type', 'text/json');
    	return $response;
	}

	public function exitcircleAction(){
		$re = array("returncode" => ReturnCode::$SUCCESS);
		$user = $this->get('security.context')->getToken()->getUser();
		$request = $this->getRequest();
		$circle_id=$request->get("circle_id");
        $da = $this->get('we_data_access');
        $da = $this->get('we_data_access');
		$da_im = $this->get('we_data_access_im');
        try {
        	if(empty($circle_id)){
				$re["returncode"] = ReturnCode::$SYSERROR;
				$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		    	$response->headers->set('Content-Type', 'text/json');
		    	return $response;
	    	}
        	$sql = "call p_quitcircle(?, ?, 0)";
	        $params = array();
	        $params[] = (string) $circle_id;
	        $params[] = (string) $user->getUserName();
	        $ds = $da->GetData("p_quitcircle", $sql, $params);

	        if ($ds["p_quitcircle"]["rows"][0]["recode"] == "0") {
	        	  //向成员发送通知 
	        	  $msgJson = array();
	        	  $msgJson["circle_id"]=$circle_id;
	        	  $msgJson["member"]=$user->getUserName();	        	  
	        	  $message = Utils::makeHTMLElementTag('employee',$user->fafa_jid,$user->nick_name)."退出了圈子【".$circleObj["circle_name"]."】";
	              $msgJson["text"]=$message;
	        	  $circleCtl = new \Justsy\BaseBundle\Controller\CircleController();
	        	  $circleCtl->setContainer($this->container);
	        	  $circleCtl->sendPresenceCirlce($circle_id,"circle_deletemeber",json_encode($msgJson));
	        } else {
	            $re["returncode"] = ReturnCode::$SYSERROR;
	            $logger = $this->container->get('logger');
	            $logger->err("quitCircle Error circle_id:" . $circle_id . " msg:" . $ds["p_quitgroup"]["rows"][0]["remsg"]);
	        }
        } catch (\Exception $e) {
        	$re["returncode"] = ReturnCode::$SYSERROR;
      		$this->get('logger')->err($e);
        }
        $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    	$response->headers->set('Content-Type', 'text/json');
    	return $response;
	}

	public function invitedmemebersAction(){
		$re = array("returncode" => ReturnCode::$SUCCESS);
		$user = $this->get('security.context')->getToken()->getUser();
	    $request = $this->getRequest();
	    $circle_id=$request->get("circle_id");
	    $invitedmemebers=$request->get("invitedmemebers");
	    $da = $this->get('we_data_access');
	    $da_im = $this->get('we_data_access_im');
	    try {
	    	if(empty($invitedmemebers)||empty($circle_id))
	    	{
	    		$re["returncode"] = ReturnCode::$SYSERROR;
				$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		    	$response->headers->set('Content-Type', 'text/json');
		    	return $response;
	    	}
	    	$circlename="";
	    	$fafa_groupid="";
			$user = $this->get('security.context')->getToken()->getUser();
			$invInfo = array(
			'inv_send_acc' => $user->getUsername(),
			'inv_recv_acc' => '',
			'eno' => '',
			'inv_rela' => '',
			'inv_title' => '',
			'inv_content' => '',
			'active_addr' => '');
			$invitedmemebersLst = explode(";",$invitedmemebers);
			$circleStaffs=array();
			$sql="select login_account from we_circle_staff where circle_id=?";
			$ds=$da->GetData("circle_staffs",$sql,array($circle_id));
			if($ds && $ds["circle_staffs"]["recordcount"]>0)
			{
				foreach ($ds["circle_staffs"]["rows"] as &$row) 
				{
					$circleStaffs[]=$row['login_account'];
                }
			}
			$sql="select circle_name,fafa_groupid from we_circle where circle_id=?";
			$ds=$da->GetData("circle",$sql,array($circle_id));
			if($ds && $ds["circle"]["recordcount"]>0)
			{
				$circlename=$ds["circle"]["rows"][0]['circle_name'];
				$fafa_groupid=$ds["circle"]["rows"][0]['fafa_groupid'];
			}
			foreach($invitedmemebersLst as $key => $value)
			{
				$invacc = trim($value);
				if (empty($invacc)) continue;
				$invInfo['inv_recv_acc'] = $invacc;
				$sql = "select eno,fafa_jid from we_staff where login_account=?";
				$ds = $da->GetData("we_staff",$sql,array((string)$invacc));
				//帐号存在
				if ($ds && $ds['we_staff']['recordcount']>0)
				{
				    if(count($circleStaffs)>0 && in_array($invacc, $circleStaffs))continue;
				    //1.帐号存在，直接加入圈子
				    //受邀人员帐号,圈子id,邀请人帐号
					$encode = DES::encrypt("$invacc,$circle_id,".$user->getUsername());
					$activeurl = $this->generateUrl("JustsyBaseBundle_invite_agreejoincircle",array('para'=>$encode,'eno'=>'c'.$circle_id), true);
					$rejectactiveurl = $this->generateUrl("JustsyBaseBundle_invite_refuse",array('para'=>$encode,'eno'=>'c'.$circle_id), true);
					$txt = $this->renderView('JustsyBaseBundle:Invite:circle_invitation_msg.html.twig', array(
					"ename" => $user->ename,
					"nick_name" => $user->nick_name,
					"activeurl" => $activeurl,
					'circle_name' => $circlename,
					'invMsg' => '',
					'staff'=>array()));
					$invInfo['eno'] = "c$circle_id";
					$invInfo['inv_title'] = "邀请您加入圈子【".Utils::makeCircleTipHTMLTag($circle_id, $circlename)."】";
					$invInfo['inv_content'] = '';
					$invInfo['active_addr'] = $activeurl;
					//保存邀请信息
					InviteController::saveWeInvInfo($da, $invInfo);
					//发送即时消息
					$fafa_jid = $ds['we_staff']['rows'][0]['fafa_jid'];
					$message = Utils::makeHTMLElementTag('employee',$user->fafa_jid,$user->nick_name)."邀请您加入圈子【".Utils::makeHTMLElementTag('circle',$fafa_groupid,$circlename)."】";
					$buttons = array();            
					$buttons[]=array("text"=>"拒绝","code"=>"agree","value"=>"0","link"=> $rejectactiveurl);
					$buttons[]=array("text"=>"立即加入","code"=>"agree","value"=>"1","link"=> $activeurl);
					Utils::sendImMessage($im_sender,$fafa_jid,"邀请加入圈子",$message,$this->container,"",Utils::makeBusButton($buttons),false,Utils::$systemmessage_code);
				}
				else
				{
					//2.帐号不存在
					$tmp = explode("@",$invacc);      
					$tmp = (count($tmp) > 1) ? $tmp[1] : 'fafatime.com';
					$sql = "select count(1) as cnt from we_public_domain where domain_name=?";
					$ds = $da->GetData("we_public_domain",$sql,array((string)$tmp));
					if ($ds && $ds['we_public_domain']['rows'][0]['cnt']==0)
					{
					    //2.1企业邮箱
					    $sql = "select eno from we_enterprise where edomain=?";
					    $ds = $da->GetData("we_enterprise",$sql,array((string)$tmp));
					    if ($ds && $ds['we_enterprise']['recordcount']>0)
					    {
					        //2.1.1企业已创建 帐号,圈子id,企业edomain des encode
					        $eno = $ds['we_enterprise']['rows'][0]['eno'];
					        $encode = DES::encrypt($user->getUsername().",$circle_id,$eno");
					        $activeurl = $this->generateUrl("JustsyBaseBundle_active_inv_s1", array(
					        'account' => DES::encrypt($invacc),
					        'invacc' => $encode), true);
					    }
					    else
					    {
					        //2.1.2企业未创建
					        $sql = "insert into we_register (login_account,ename,credential_path,active_code,ip,email_type,first_reg_date,last_reg_date,register_date,state_id)"
					        ." values (?,?,?,?,?,?,now(),now(),now(),'0')";
					        $para = array($invacc,'','',strtoupper(substr(uniqid(),3,10)),$_SERVER['REMOTE_ADDR'],'1');
					        $da->ExecSQL($sql,$para);
					        //发送邮件 帐号,圈子id,邀请发送者帐号,邀请人企业名 des encode
					        $encode = DES::encrypt("$invacc,$circle_id,".$user->getUserName().",".$user->ename);
					        $activeurl = $this->generateUrl("JustsyBaseBundle_active_reg_s1", array('account' => $encode), true);
					    }
					    //保存邀请信息 circleid保存到eno字段，以字母'c'开头
					    $invInfo['eno'] = "c$circle_id";
					    $title = $user->nick_name." 邀请您加入 ".Utils::makeCircleTipHTMLTag($circle_id , $circlename)." 协作网络";
					    $txt = $this->renderView('JustsyBaseBundle:Invite:circle_invitation.html.twig', array(
					    "ename" => $user->ename,
					    "nick_name" => $user->nick_name,
					    "activeurl" => $activeurl,
					    'circle_name' => $circlename,
					    'invMsg' => '',
					    'staff'=>array()));
					    $invInfo['inv_title'] = $title;
					    $invInfo['inv_content'] = $txt;
					    $invInfo['active_addr'] = $activeurl;
					    InviteController::saveWeInvInfo($da, $invInfo);
					    Utils::saveMail($da,$user->getUsername(),$invacc,$title,$txt,$invInfo['eno']);
					}
					else
					{
					    //公共邮箱
					    $sql = "insert into we_register (login_account,ename,credential_path,active_code,ip,email_type,first_reg_date,last_reg_date,register_date,state_id) "
					    ."select ?,'','','".strtoupper(substr(uniqid(),3,10))."','".$_SERVER['REMOTE_ADDR']."','0',now(),now(),now(),'2' from dual "
					    ."where not exists (select 1 from we_register where login_account=?)";
					    $para = array($invacc,$invacc);
					    $da->ExecSQL($sql,$para);
					    //发送邮件 帐号,圈子id,邀请发送者帐号,邀请人企业名 des encode
					    $encode = DES::encrypt("$invacc,$circle_id,".$user->getUserName().",".$user->ename);
					    $activeurl = $this->generateUrl("JustsyBaseBundle_active_reg_s1",array('account'=>$encode),true);
					    $invInfo['eno'] = "c$circle_id";
					    $title = $user->nick_name." 邀请您加入 ".Utils::makeCircleTipHTMLTag($circle_id, $circlename)." 协作网络";
					  	$txt = $this->renderView('JustsyBaseBundle:Invite:circle_invitation.html.twig', array(
					    "ename" => $user->ename,
					    "nick_name" => $user->nick_name,
					   	"activeurl" => $activeurl,
					  	'circle_name' => $circlename,
					    'invMsg' => '',
					  	'staff'=> array()));
					   	//保存邀请信息
					  	$invInfo['inv_title'] = $title;
					    $invInfo['inv_content'] = $txt;
					 	$invInfo['active_addr'] = $activeurl;
					    InviteController::saveWeInvInfo($da, $invInfo);
						Utils::saveMail($da,$user->getUsername(),$invacc,$title,$txt,$invInfo['eno']);
					}
				}
			}
			$re["returncode"] = ReturnCode::$SUCCESS;
	    } catch (\Exception $e) {
        	$re["returncode"] = ReturnCode::$SYSERROR;
      		$this->get('logger')->err($e);
        }
        $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    	$response->headers->set('Content-Type', 'text/json');
    	return $response;
	}

	public function hasCircle($da,$circlename){
	    try {
	    	$sql="select circle_id from we_circle where circle_name=?";
	    	$ds=$da->Getdata('circles',$sql,array($circlename));
	    	 if ($ds && $ds['circles']['recordcount']>0)
	        {
	          return true;
	        }
	    } catch (\Exception $e) {
	        $this->get('logger')->err($e);
	    }
    	return false;
	}

	private function getCricle($da,$circleid){
		$re = array();
		$sql="select a.circle_id, a.circle_name, a.circle_desc, a.logo_path, a.create_staff, a.create_date, a.manager, 
		a.join_method, a.enterprise_no, a.network_domain, a.allow_copy, a.logo_path_small, a.logo_path_big, a.fafa_groupid,(select count(1) from we_circle_staff c where c.circle_id=a.circle_id) as staff_num
	 	,b.classify_name,a.circle_class_id
		from we_circle a
		left join we_circle_class b on a.circle_class_id=b.classify_id 
		where a.circle_id=?"; 
		$ds = $da->GetData("circle",$sql,array($circleid.''));
	    $rowRoot = $ds["circle"]["rows"][0];
	    $re["circle_id"]=$rowRoot["circle_id"];
	    $re["circle_name"]=$rowRoot["circle_name"];
	    $re["circle_desc"]=$rowRoot["circle_desc"];
	    $re["logo_path"]=$rowRoot["logo_path"];
	    $re["create_staff"]=$rowRoot["create_staff"];
	    $re["create_date"]=$rowRoot["create_date"];
	    $re["manager"]=$rowRoot["manager"];
	    $re["join_method"]=$rowRoot["join_method"];
	    $re["enterprise_no"]=$rowRoot["enterprise_no"];
	    $re["network_domain"]=$rowRoot["network_domain"];
	    $re["allow_copy"]=$rowRoot["allow_copy"];
	    $re["logo_path_small"]=$rowRoot["logo_path_small"];
	    $re["logo_path_big"]=$rowRoot["logo_path_big"];
	    $re["fafa_groupid"]=$rowRoot["fafa_groupid"];
	    $re["staff_num"]=$rowRoot["staff_num"];
	    $re["classify_name"]=$rowRoot["classify_name"];
	    $re["circle_class_id"]=$rowRoot["circle_class_id"];
	    return $re;
	}
}