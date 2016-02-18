<?php

namespace Justsy\BaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Login\UserSession;
use Justsy\BaseBundle\Login\UserProvider;

class InviteController extends Controller
{
  //初始页面
  public function indexAction($network_domain)
  {
  	 $DataAccess = $this->container->get('we_data_access');
     $dataset = $DataAccess->GetData("domain","select domain_name from we_public_domain order by order_num limit 0,10");
     $emials=($dataset["domain"]["rows"]);
     $huowa = array();
		 for($i=0;$i<count($emials);$i++)
		 {
			 $huowa[]=$emials[$i]["domain_name"];
		 }
  	 return $this->render("JustsyBaseBundle:Invite:invite_index.html.twig",array('inviteDomain'=> json_encode($huowa)));
  }
  //已发出邀请
  public function sendedAction($network_domain)
  {
    return $this->render("JustsyBaseBundle:Invite:invite_sended.html.twig",array(
      'curr_network_domain' => $network_domain));
  }
  //获取发送邀请分页数据
  public function sendedListAction($pageindex)
  {
    $user = $this->get('security.context')->getToken()->getUser();
    $account = $user->getUsername();
    $da = $this->get('we_data_access');
    $sql = "select count(1) as cnt from we_invite where invite_send_email=?";
    $ds = $da->GetData("we_invite", $sql, array((string)$account));
    if ($ds)
    {
      $pages = ceil($ds['we_invite']['rows'][0]['cnt']/10);
    }
    else
    {
      $pages = 0;
    }
    $rows = $this->getSendedInvite($da, $account, $pageindex-1);
    return $this->render("JustsyBaseBundle:Invite:invite_sended_list.html.twig",array(
      'rows' => $rows,
      'pagecount' => $pages,
      'pageindex' => $pageindex));
  }
  //获取已发消息
  private function getSendedInvite($da, $account, $page)
  {
    $start = $page*10;
    $sql = "select invite_recv_email,date_format(last_invite_date,'%Y年%c月%e日') as last_invite_date,
      invite_num,inv_title,eno,active_date,status
      from we_invite where invite_send_email=? order by active_date limit $start,10";
    $ds = $da->GetData("we_invite", $sql, array((string)$account));
    return ($ds && $ds['we_invite']['recordcount']>0) ? $ds['we_invite']['rows'] : array();
  }
  //已收到邀请
  public function recvedAction($network_domain)
  {
    $user = $this->get('security.context')->getToken()->getUser();
    $account = $user->getUsername();
    $da = $this->get('we_data_access');
    $sql = "select invite_send_email,date_format(last_invite_date,'%Y年%c月%e日') as last_invite_date,last_invite_date as date_sort,
      invite_num,inv_title,eno,active_addr,inv_content
      from we_invite where invite_recv_email=? and ifnull(active_date,'')='' and ifnull(status,'0')='0'
      order by date_sort desc";
    $ds = $da->GetData("we_invite", $sql, array((string)$account));
    return $this->render("JustsyBaseBundle:Invite:invite_recved.html.twig",array(
      'curr_network_domain' => $network_domain,
      'rows' => $ds['we_invite']['rows']));
  }
  //拒绝邀请
  public function refuseAction()
  {
    $logger = $this->get('logger');
    $user = $this->get('security.context')->getToken()->getUser();
    $account = $user->getUsername();
    $da = $this->get('we_data_access');
    $request = $this->getRequest();
    $invite_send_email = $request->get("invite_send_email");
    $eno = $request->get("eno");
    $para = $request->get("para");
    $paraArr = array();
    if(!empty($para))
    {
         $paraArr = explode(",",trim(DES::decrypt($para)));
         $invite_send_email = $paraArr[2];
    }    
    $name = "";
    if(substr($eno,0,1)=="c") 
    {
        //圈子邀请 
        $sql = "select circle_name from we_circle where circle_id=?";
        $ds = $da->GetData("c",$sql,array((string)substr($eno,1)));
        if($ds && count($ds["c"]["rows"])>0) $name ="拒绝了加入圈子【".$ds["c"]["rows"][0]["circle_name"]."】的邀请！";
        else $name ="拒绝了您的邀请！";
    }
    else if($eno!="-1")
    {
        $name = "拒绝了加入企业的邀请！";
    }
    else
    {
        $name = "拒绝了注册Wefafa的邀请！";
    }
    $msgId = SysSeq::GetSeqNextValue($da,"we_message","msg_id");
    $sqls[] = "insert into we_message(msg_id,sender,recver,title,content,send_date)values(?,?,?,?,?,now())";
    $paras[] = array((string)$msgId,(string)$account,(string)$invite_send_email,"拒绝邀请","【".$user->nick_name."】".$name);
    $sqls[] = "update we_invite set status='1' where invite_recv_email=? and invite_send_email=? and eno=?";
    $paras[] = array((string)$account,(string)$invite_send_email,(string)$eno);
    try
    {
      $da->ExecSQLs($sqls,$paras);    
    }
    catch(\Exception $e)
    {
      $logger->err($e);
      return new Response('0');
    }
    if(!empty($para))
    {
    	    $im_sender = $this->container->getParameter('im_sender');
        	//向邀请人发送拒绝消息
        	$staff = new \Justsy\BaseBundle\Management\Staff($da,null,$invite_send_email);
        	$getInfo = $staff->getInfo();
        	if($getInfo==null) return;
        	$message="【".$user->nick_name."】".$name;
        	Utils::sendImMessage($im_sender,$getInfo["fafa_jid"],"拒绝加入圈子",$message,$this->container,"","",false,Utils::$systemmessage_code);
    }
    return new Response('1');
  }
  //根据帐号查询人员姓名
  public function invNickNameAction()
  {
    $re = array('hs'=>'0','name'=>'','eno'=>'');
    $account = $this->get('request')->request->get('account');
    $circleId = $this->get('request')->request->get('circleId');
    $eno = $this->get('request')->request->get('eno');
    $da = $this->get('we_data_access');
    if (empty($eno))
    {
      $sql = "select count(1) as cnt from we_circle_staff where circle_id=? and login_account=?";
      $ds = $da->GetData("we_circle_staff", $sql, array((string)$circleId,(string)$account));
      if ($ds && $ds['we_circle_staff']['rows'][0]['cnt']==0)
      {
        $sql = "select nick_name from we_staff where login_account=?";
        $ds = $da->GetData("we_staff", $sql, array((string)$account));
        if ($ds && $ds['we_staff']['recordcount']>0)
        {
          $re['name'] = $ds['we_staff']['rows'][0]['nick_name'];
        }
      }
      else
      {
        $re['hs'] = '1';
      }
    }
    else
    {
      $sql = "select eno from we_staff where login_account=?";
      $ds = $da->GetData("we_staff", $sql, array((string)$account));
      if ($ds && $ds['we_staff']['recordcount']>0)
      {
        $re['hs']='1';
        $re['eno']=$ds['we_staff']['rows'][0]['eno'];
      }
    }
    $resp = new Response(json_encode($re));
    $resp->headers->set('Content-Type', 'text/json');
    return $resp;
  }
  //邀请人员加入圈子
  public function sendInvitationAction()
  {
  	$res = $this->getRequest();
    $im_sender = $this->container->getParameter('im_sender');
    $acts = $res->get('acts');
    $logger = $this->get('logger');
    $da = $this->get('we_data_access');
    $user = $this->get('security.context')->getToken()->getUser();
    $circleId = $res->get("circleId");
    $eno = $res->get('eno');
    $invMsg = $res->get('invMsg');
    $subject = $res->get('subject');
    $invRela = $res->get('invRela');
    $circleName = "";
    if(!empty($circleId))
    {
		    $circleMgr = new \Justsy\BaseBundle\Management\CircleMgr($da,$this->get('we_data_access_im'),null);
		    $circleObj = $circleMgr->Get($circleId);
		    if($circleObj ==null &&(empty($eno) || $eno=="-1"))
		    {
		       return new Response('1');
		    }
		    $circleName = $circleObj["circle_name"];    
    }
    $invInfo = array('inv_send_acc'=>$user->getUsername(),'inv_recv_acc'=>'','eno'=>'','inv_rela'=>$invRela,
      'inv_title'=>'','inv_content'=>'','active_addr'=>'');
    $photourl = $this->container->getParameter('FILE_WEBSERVER_URL');
    $staff_e = array(); $staff_c = array();

    $sql = "select c.login_account,c.nick_name,concat('".$photourl."',case trim(ifnull(c.photo_path,'')) when '' then null else c.photo_path end) as photo_path 
from we_staff c inner join we_circle_staff d on c.login_account=d.login_account where d.circle_id=(
select b.circle_id from we_staff a inner join we_circle b on a.eno=b.enterprise_no and a.login_account=?) limit 0,9";
    $ds = $da->GetData("staff",$sql,array((string)$user->getUsername()));
    if ($ds && $ds['staff']['recordcount']>0) $staff_e = $ds['staff']['rows'];

    $sql = "select a.login_account,a.nick_name,concat('".$photourl."',case trim(ifnull(a.photo_path,'')) when '' then null else a.photo_path end) as photo_path 
from we_staff a inner join we_circle_staff b on a.login_account=b.login_account
where a.eno=(select eno from we_staff where login_account=?) and b.circle_id=? limit 0,9";
    $ds = $da->GetData("staff",$sql,array((string)$user->getUsername(),(string)$circleId));
    if ($ds && $ds['staff']['recordcount']>0) $staff_c = $ds['staff']['rows'];
    
    try
    {
      foreach($acts as $key=>$value)
      {
        $invacc = trim($value);
        $invInfo['inv_recv_acc'] = $invacc;
        //排除自己
        if($invacc==$user->getUsername()) continue;
        $sql = "select fafa_jid from we_staff where login_account=?";
        $ds = $da->GetData("we_staff",$sql,array((string)$invacc));  
        $isReg =  $ds && $ds['we_staff']['recordcount']>0;  //是否已注册
        if (empty($eno) || $eno=="-1")   //外部圈子邀请
        {
          //加入圈子
          if ($isReg)
          {
            //1.帐号存在，直接加入圈子
            //受邀人员帐号,圈子id,邀请人帐号
            $encode = DES::encrypt("$invacc,$circleId,".$user->getUsername());
            $activeurl = $this->generateUrl("JustsyBaseBundle_invite_agreejoincircle",array('para'=>$encode,'eno'=>'c'.$circleId), true);
            $rejectactiveurl = $this->generateUrl("JustsyBaseBundle_invite_refuse",array('para'=>$encode,'eno'=>'c'.$circleId), true);
            $txt = $this->renderView('JustsyBaseBundle:Invite:circle_invitation_msg.html.twig',
              array("ename"=>$user->ename,"nick_name"=>$user->nick_name,"activeurl"=>$activeurl,'circle_name'=>$circleName,'invMsg'=>$invMsg));
            $invInfo['eno'] = "c$circleId";
            if(empty($subject)){
		            if($circleId=="9999")
		               $invInfo['inv_title'] = $user->nick_name." 邀请您加入TA的人脉圈";
		            else
		               $invInfo['inv_title'] = $user->nick_name." 邀请您加入圈子【".Utils::makeCircleTipHTMLTag($circleId, $circleName)."】";
            }
            else
               $invInfo['inv_title']=$subject;
            $invInfo['inv_content'] = $invMsg;
            $invInfo['active_addr'] = $activeurl;
            //保存邀请信息
            InviteController::saveWeInvInfo($da, $invInfo);
            //发送即时消息
            $fafa_jid = $ds['we_staff']['rows'][0]['fafa_jid'];
            if($circleId=="9999")
               $message = Utils::makeHTMLElementTag('employee',$user->fafa_jid,$user->nick_name)."邀请您加入TA的人脉圈";
            else
               $message = Utils::makeHTMLElementTag('employee',$user->fafa_jid,$user->nick_name)."邀请您加入圈子【".Utils::makeHTMLElementTag('circle',$circleObj["fafa_groupid"],$circleName)."】";
            $buttons = array();            
            $buttons[]=array("text"=>"拒绝","code"=>"agree","value"=>"0","link"=> $rejectactiveurl);
            $buttons[]=array("text"=>"立即加入","code"=>"agree","value"=>"1","link"=> $activeurl);
            Utils::sendImMessage($user->fafa_jid,$fafa_jid,"邀请加入圈子",$message,$this->container,"",Utils::makeBusButton($buttons),false,Utils::$systemmessage_code,"1");
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
                $encode = DES::encrypt($user->getUsername().",$circleId,$eno");
                $eno="";
                $activeurl = $this->generateUrl("JustsyBaseBundle_active_inv_s1",array('account'=>DES::encrypt($invacc),'invacc'=>$encode),true);
                $staff_t = $staff_e;
                $has_e = "1";
              }
              else
              {
                //2.1.2企业未创建
                $sql = "insert into we_register (login_account,ename,credential_path,active_code,ip,email_type,first_reg_date,last_reg_date,register_date,state_id) "
                  ."select ?,'','','".strtoupper(substr(uniqid(),3,10))."','".$_SERVER['REMOTE_ADDR']."','1',now(),now(),now(),'0' from dual "
                  ."where not exists (select 1 from we_register where login_account=?)";
                $para = array($invacc,$invacc);
                $da->ExecSQL($sql,$para);
                //发送邮件 帐号,圈子id,邀请发送者帐号,邀请人企业名 des encode
                $encode = DES::encrypt("$invacc,$circleId,".$user->getUserName().",".$user->ename);
                $activeurl = $this->generateUrl("JustsyBaseBundle_active_reg_s1",array('account'=>$encode),true);
                $staff_t = array();
                $has_e = "0";
              }
              //保存邀请信息 circleid保存到eno字段，以字母'c'开头
              if ($circleId=="-1")
              {
                $invInfo['eno'] = "-1";
                $title = empty($subject)? $user->nick_name." 邀请您加入Wefafa企业协作网络" : $subject;
                $txt = $this->renderView("JustsyBaseBundle:Invite:enterprise_invitation.html.twig",array(
                  "ename" => $user->ename,
                  "realName" => $user->nick_name,
                  "activeurl" => $activeurl,
                  "invMsg" => $invMsg,
                  "staff" => $staff_t,
                  "has_e" => $has_e));
              }
              else
              {
                if ($invRela=="0")
                {
                  $has_e = "1";
                  $staff_t = $staff_e;
                }
                else
                {
                  $has_e = "0";
                  $staff_t = array();
                }
                $invInfo['eno'] = "c$circleId";
                $title = empty($subject) ? $user->nick_name." 邀请您加入 ".Utils::makeCircleTipHTMLTag($circleId, $circleName)." 协作网络": $subject;
                $txt = $this->renderView('JustsyBaseBundle:Invite:circle_invitation.html.twig',array(
                  "ename" => $user->ename,
                  "nick_name" => $user->nick_name,
                  "activeurl" => $activeurl,
                  'circle_name' => $circleName,
                  'invMsg' => $invMsg,
                  "staff" => $staff_t,
                  "has_e" => $has_e));
              }
              $invInfo['inv_title'] = $title;
              $invInfo['inv_content'] = $txt;
              $invInfo['active_addr'] = $activeurl;
              InviteController::saveWeInvInfo($da, $invInfo);
              $title = empty($subject)? $user->nick_name." 邀请您加入 ". $circleName." 协作网络" : $subject;
              Utils::saveMail($da,$user->getUsername(),$invacc,$title,$txt,$invInfo['eno']);
              //Utils::sendMail($this->get('mailer'),$title,$this->container->getParameter('mailer_user'),null,$invacc,$txt);
            }
            else
            {
              //2.2公共邮箱
              if ($invRela=="0")
              {
                //邀请同事
                $eno = $user->eno;
                $encode = DES::encrypt($user->getUsername().",$circleId,$eno");
                $eno="";
                $activeurl = $this->generateUrl("JustsyBaseBundle_active_inv_s1",
                  array('account'=>DES::encrypt($invacc),'invacc'=>$encode),true);
                $txt = $this->renderView('JustsyBaseBundle:Invite:circle_invitation.html.twig',array(
                  "ename" => $user->ename,
                  "nick_name" => $user->nick_name,
                  "activeurl" => $activeurl,
                  'circle_name' => $circleName,
                  'invMsg' => $invMsg,
                  "staff" => $staff_c,
                  "has_e" => "1"));
                //保存邀请信息 circleid保存到eno字段，以字母'c'开头
                $invInfo['eno'] = "c$circleId";
                $invInfo['inv_title'] = empty($subject)? $user->nick_name." 邀请您加入 ".Utils::makeCircleTipHTMLTag($circleId, $circleName)." 协作网络" : $subject;
                $invInfo['inv_content'] = $txt;
                $invInfo['active_addr'] = $activeurl;
                InviteController::saveWeInvInfo($da, $invInfo);
                $invInfo['inv_title'] =empty($subject)? $user->nick_name." 邀请您加入 ". $circleName." 协作网络": $subject;
                Utils::saveMail($da,$user->getUsername(),$invacc,$invInfo['inv_title'],$txt,$invInfo['eno']);
                //Utils::sendMail($this->get('mailer'),"邀请加入圈子【".$circleName."】",$this->container->getParameter('mailer_user'),null,$invacc,$txt);
              }
              else
              {
                $sql = "insert into we_register (login_account,ename,credential_path,active_code,ip,email_type,first_reg_date,last_reg_date,register_date,state_id) "
                  ."select ?,'','','".strtoupper(substr(uniqid(),3,10))."','".$_SERVER['REMOTE_ADDR']."','0',now(),now(),now(),'2' from dual "
                  ."where not exists (select 1 from we_register where login_account=?)";
                $para = array($invacc,$invacc);
                $da->ExecSQL($sql,$para);
                //发送邮件 帐号,圈子id,邀请发送者帐号,邀请人企业名 des encode
                $encode = DES::encrypt("$invacc,$circleId,".$user->getUserName().",".$user->ename);
                $activeurl = $this->generateUrl("JustsyBaseBundle_active_reg_s1",array('account'=>$encode),true);
                if ($circleId=="-1")
                {
                  $invInfo['eno'] = "-1";
                  $circleName = "Wefafa企业";
                  $title =empty($subject)? $user->nick_name." 邀请您加入".$circleName."协作网络": $subject;
                  $txt = $this->renderView("JustsyBaseBundle:Invite:enterprise_invitation.html.twig",array(
                    "ename" => $user->ename,
                    "realName" => $user->nick_name,
                    "activeurl" => $activeurl,
                    "invMsg" => $invMsg,
                    "staff" => array()));
                }
                else
                {
                  $invInfo['eno'] = "c$circleId";
                  $title =empty($subject)? $user->nick_name." 邀请您加入 ".Utils::makeCircleTipHTMLTag($circleId, $circleName)." 协作网络" : $subject;
                  $txt = $this->renderView('JustsyBaseBundle:Invite:circle_invitation.html.twig',array(
                    "ename" => $user->ename,
                    "nick_name" => $user->nick_name,
                    "activeurl" => $activeurl,
                    'circle_name' => $circleName,
                    'invMsg' => $invMsg,
                    "staff" => array()));
                }
                //保存邀请信息
                $invInfo['inv_title'] = $title;
                $invInfo['inv_content'] = $txt;
                $invInfo['active_addr'] = $activeurl;
                InviteController::saveWeInvInfo($da, $invInfo);
                $invInfo['inv_title'] =empty($subject)? $user->nick_name." 邀请您加入 ". $circleName." 协作网络" : $subject;
                Utils::saveMail($da,$user->getUsername(),$invacc,$title,$txt,$invInfo['eno']);
                //Utils::sendMail($this->get('mailer'),$title,$this->container->getParameter('mailer_user'),null,$invacc,$txt);
              }
            }
          }
        }
        else   //企业圈子邀请
        {
        	//判断受邀请人是否已注册,已注册的不能再邀请加个企业圈子
        	//与邀请人不同企业域的其他企业邮箱不能加入
        	if(!$isReg)
        	{
		          //加入企业
		          $activeurl = $this->generateUrl("JustsyBaseBundle_active_inv_s1",
		            array('account'=>DES::encrypt($invacc),'invacc'=>DES::encrypt($user->getUsername())),true);
		          $txt = $this->renderView("JustsyBaseBundle:Invite:enterprise_invitation.html.twig",array(
		            "ename" => $user->ename,
		            "realName" => $user->nick_name,
		            "activeurl" => $activeurl,
		            "invMsg" => $invMsg,
		            "staff" => $staff_e,
		            "has_e" => "1"));
		          //保存邀请信息
		          $invInfo['eno'] = $eno;
		          $invInfo['inv_title'] = empty($subject) ? "您的同事 ".$user->nick_name." 邀请您加入Wefafa企业协作网络" : $subject;
		          $invInfo['inv_content'] = $txt;
		          $invInfo['active_addr'] = $activeurl;
		          InviteController::saveWeInvInfo($da, $invInfo);
		          Utils::saveMail($da,$user->getUsername(),$invacc,$invInfo['inv_title'],$txt,$invInfo['eno']);
          }
        }
      }
    }
    catch(\Exception $e)
    {
      $logger->err($e);
      return new Response('0');
    }
    return new Response('1');
  }
  //重新发送邀请
  public function reSendInvitationAction()
  {
    $im_sender = $this->container->getParameter('im_sender');
    $da = $this->get('we_data_access');
    $user = $this->get('security.context')->getToken()->getUser();
    $eno = $this->get("request")->request->get("eno");
    $invite_recv_email = $this->get("request")->request->get("invite_recv_email");
    $invInfo = array('inv_send_acc'=>$user->getUsername(),'inv_recv_acc'=>$invite_recv_email,'eno'=>$eno,'inv_rela'=>'',
      'inv_title'=>'','inv_content'=>'','active_addr'=>'');
    //被邀请帐号是否存在，存在则update we_invite，不存在 取we_invite的数据，重发邮件
    $sql = "select fafa_jid from we_staff where login_account=?";
    $ds = $da->GetData("we_staff",$sql,array((string)$invite_recv_email));
    if ($ds && $ds['we_staff']['recordcount']>0)
    {
      InviteController::saveWeInvInfo($da, $invInfo);
      //发送即时消息
      $fafa_jid = $ds['we_staff']['rows'][0]['fafa_jid'];
      $message = $user->nick_name."邀请您加入圈子，请登录微发发进行确认。";
      Utils::sendImMessage($im_sender,$fafa_jid,"邀请加入圈子",$message,$this->container,"","",false,Utils::$systemmessage_code);
    }
    else
    {
      $sql = "select inv_title,inv_content from we_invite where invite_recv_email=? and invite_send_email=? and eno=?";
      $ds = $da->GetData("we_invite",$sql,array((string)$invite_recv_email,(string)$user->getUsername(),(string)$eno));
      if ($ds && $ds['we_invite']['recordcount']>0)
      {
        Utils::saveMail($da,$user->getUsername(),$invite_recv_email,$ds['we_invite']['rows'][0]['inv_title'],$ds['we_invite']['rows'][0]['inv_content'],$eno);
        //Utils::sendMail($this->get('mailer'),$ds['we_invite']['rows'][0]['inv_title'],$this->container->getParameter('mailer_user'),null,$invite_recv_email,$ds['we_invite']['rows'][0]['inv_content']);
      }
      InviteController::saveWeInvInfo($da, $invInfo);
    }
    return new Response("1");
  }
  //删除邀请
  public function delInvitationAction()
  {
    $da = $this->get('we_data_access');
    $user = $this->get('security.context')->getToken()->getUser();
    $eno = $this->get("request")->request->get("eno");
    $invite_recv_email = $this->get("request")->request->get("invite_recv_email");
    $invite_send_email = $this->get("request")->request->get("invite_send_email");
    if (!empty($invite_recv_email))
    {
      $sql = "delete from we_invite where invite_recv_email=? and invite_send_email=? and eno=?";
      $da->ExecSQL($sql,array((string)$invite_recv_email,(string)$user->getUsername(),(string)$eno));
    }
    else if (!empty($invite_send_email))
    {
      $sql = "delete from we_invite where invite_recv_email=? and invite_send_email=? and eno=?";
      $da->ExecSQL($sql,array((string)$user->getUsername(),(string)$invite_send_email,(string)$eno));
    }
    return new Response("1");
  }
  //收到加入圈子邀请消息，同意加入圈子
  public function agreeJoinCircleAction($para)
  {
    if (empty($para)) return $this->render('JustsyBaseBundle:Error:index.html.twig',array('error'=>'参数错误！'));
    $da = $this->get('we_data_access');
    $res = $this->get('request');
    $urlSource = $res->get("_urlSource"); //获取操作源。FaFaWin:从PC客户端操作的
    //受邀人员帐号,圈子id,邀请人帐号
    $paraArr = explode(",",trim(DES::decrypt($para)));
    //是否有帐号
    $sql = "select nick_name,fafa_jid from we_staff where login_account=?";
    $ds = $da->GetData("we_staff", $sql, array((string)$paraArr[0]));
    if (!$ds || $ds['we_staff']['recordcount']==0)
    {
      if(empty($urlSource)) return $this->render('JustsyBaseBundle:Error:index.html.twig',array('error'=>'您还没有微发发帐号，请先注册！'));
      else{
         $response = new Response(("{\"succeed\":0,\"msg\":\"您还没有微发发帐号，请先注册！\"}"));
		     $response->headers->set('Content-Type', 'text/json');
		     return $response;
		  }
    }
    //判断是否是邀请加入人脉圈子,则在互相关注
    if($paraArr[1]=="9999")
    {
          //互相添加好友
          $staffMgr = new \Justsy\BaseBundle\Management\Staff($da,$this->get('we_data_access_im'),$paraArr[2],$this->get("logger"));
          try{
              $staffMgr->attentionTo($paraArr[0]);
          }
          catch(\Exception $e){}
          try{
              $staffMgr->attentionMe($paraArr[0]);
          }
          catch(\Exception $e){}
          try{
             $staffMgr->bothAddFriend($this->container,$paraArr[0]); 
          }
          catch(\Exception $e){}   	    
		      $response = new Response(("{\"succeed\":1,\"name\":\"人脉圈\",\"circleurl\":\"".$this->generateUrl("JustsyBaseBundle_enterprise",array('network_domain'=>"9999"),true)."\"}"));
					$response->headers->set('Content-Type', 'text/json');
					return $response;	
    }
    $nick_name = $ds['we_staff']['rows'][0]['nick_name'];
    $fafa_jid = $ds['we_staff']['rows'][0]['fafa_jid'];
    //圈子是否存在
    $sql = "select network_domain,circle_name,fafa_groupid from we_circle where circle_id=?";
    $ds = $da->GetData("we_circle", $sql, array((string)$paraArr[1]));
    if (!$ds || $ds['we_circle']['recordcount']==0)
    {
      if(empty($urlSource)) return $this->render('JustsyBaseBundle:Error:index.html.twig',array('error'=>'您要加入的圈子不存在！'));
      else
      {
      	$response = new Response(("{\"succeed\":0,\"msg\":\"您要加入的圈子不存在！\"}"));
		    $response->headers->set('Content-Type', 'text/json');
		    return $response;   
		   }
    }
    $fafa_groupid = $ds['we_circle']['rows'][0]['fafa_groupid'];
    $network_domain = $ds['we_circle']['rows'][0]['network_domain'];
    $circle_name = $ds['we_circle']['rows'][0]['circle_name'];
    //是否已经加入圈子
    $sql = "select count(1) cnt from we_circle_staff where login_account=? and circle_id=?";
    $ds = $da->GetData("cnt", $sql, array((string)$paraArr[0],(string)$paraArr[1]));
    if ($ds && $ds['cnt']['rows'][0]['cnt']>0)
    {
      if(empty($urlSource)) return $this->render('JustsyBaseBundle:Error:index.html.twig',array('error'=>'您已经加入该圈子！'));
      else{
      $response = new Response(("{\"succeed\":0,\"msg\":\"您已经加入该圈子！\"}"));
		  $response->headers->set('Content-Type', 'text/json');
		  return $response;
		  }
    }
    //圈子id+nick_name不能重复
    $sql = "select count(1) cnt from we_circle_staff where circle_id=? and nick_name=?";
    $ds = $da->GetData("cnt", $sql, array((string)$paraArr[1],(string)$nick_name));
    if ($ds && $ds['cnt']['rows'][0]['cnt']>0)
    {
      $user = $this->get('security.context')->getToken()->getUser();
      $nick_name = $nick_name."(".$user->eshortname.")";
    }
    //判断圈子是否有人
    $sql = "select count(1) as cnt from we_circle_staff where circle_id=?";
    $ds = $da->GetData('we_circle_staff',$sql,array((string)$paraArr[1]));
    if ($ds && $ds['we_circle_staff']['rows'][0]['cnt']==0)
    {
      $sql = "update we_circle set create_staff=? where circle_id=?";
      $da->ExecSQL($sql,array((string)$paraArr[0],(string)$paraArr[1]));
    }
    $sql = "insert into we_circle_staff (circle_id,login_account,nick_name) values (?,?,?)";
    $da->ExecSQL($sql,array((string)$paraArr[1],(string)$paraArr[0],(string)$nick_name));
    //更新邀请信息
    $sql = "update we_invite set real_active_email=?,active_date=now() where invite_send_email=? and invite_recv_email=? and eno=?";
    $da->ExecSQL($sql,array((string)$paraArr[0],(string)$paraArr[2],(string)$paraArr[0],(string)("c".$paraArr[1])));
    //10－加入外部圈子－5
    $sql = "insert into we_staff_points (login_account,point_type,point_desc,point,point_date) values (?,?,?,?,now())";
    $da->ExecSQL($sql,array((string)$paraArr[0],(string)'10',(string)'成功加入外部圈子'.$circle_name.'，获得积分5',(int)5));
    
    $apply = new \Justsy\BaseBundle\Management\ApplyMgr($da,null);
    $apply->SetCircleApplyInvalid($paraArr[0],$paraArr[1]);
    
    //发送即时消息通知申请人及成员
    $circleObj = new \Justsy\BaseBundle\Controller\CircleController();
    $circleObj->setContainer($this->container);    
    $message = Utils::makeHTMLElementTag('employee',$fafa_jid,$nick_name)."加入了圈子【".Utils::makeHTMLElementTag('circle',$fafa_groupid,$circle_name)."】";
    $circleObj->sendPresenceCirlce($paraArr[1],"circle_addmember",$message);
    
       $backurl = $this->generateUrl("JustsyBaseBundle_enterprise",array('network_domain'=>$paraArr[0]),true);
      if(empty($urlSource)) return $this->render('JustsyBaseBundle:Error:success.html.twig',array('backurl'=>$backurl));
      else
      {
		      $response = new Response(("{\"succeed\":1,\"name\":\"".$circle_name."\",\"circleurl\":\"".$backurl."\"}"));
					$response->headers->set('Content-Type', 'text/json');
					return $response;
		  }
  }
  //获取邀请数
  public function getInvCountAction()
  {
    $re = 0;
    $da = $this->get('we_data_access');
    $user = $this->get('security.context')->getToken()->getUser();
    $sql = "select count(1) as cnt from we_invite where invite_recv_email=? and ifnull(active_date,'')='' and ifnull(status,'0')='0'";
    $ds = $da->GetData("we_invite",$sql,array((string)$user->getUsername()));
    if ($ds) $re = $ds['we_invite']['rows'][0]['cnt'];
    return new Response($re);
  }
  
  public static function saveWeInvInfo($da, $invInfo)
  {
    //保存邀请信息 circleid保存到eno字段，以字母'c'开头
    $sql = "select count(1) as cnt from we_invite where invite_send_email=? and invite_recv_email=? and eno=?";
    $ds = $da->GetData("we_invite",$sql,array((string)$invInfo['inv_send_acc'],(string)$invInfo['inv_recv_acc'],(string)$invInfo['eno']));
    if ($ds && $ds["we_invite"]["rows"][0]["cnt"]>0)
    {
      $sql = "update we_invite set last_invite_date=now(),invite_num=invite_num+1,status='0' where invite_send_email=? and invite_recv_email=? and eno=?";
      $para = array((string)$invInfo['inv_send_acc'],(string)$invInfo['inv_recv_acc'],(string)$invInfo['eno']);
    }
    else
    {
      $sql = "insert into we_invite(invite_send_email,invite_recv_email,invite_num,eno,invate_rela,inv_title,inv_content,active_addr,
        first_invite_date,last_invite_date,status) values (?,?,?,?,?,?,?,?,now(),now(),'0')";
      $para = array(
        (string)$invInfo['inv_send_acc'],
        (string)$invInfo['inv_recv_acc'],
        (integer)1,
        (string)$invInfo['eno'],
        (string)$invInfo['inv_rela'],
        (string)$invInfo['inv_title'],
        (string)$invInfo['inv_content'],
        (string)$invInfo['active_addr']);
    }
    $da->ExecSQL($sql,$para);
  }
  private function getCircleList($type)
  {
    $re = array();
    $da = $this->get('we_data_access');
    $user = $this->get('security.context')->getToken()->getUser();
    $account = $user->getUsername();
    //$type 0；同事，1；合作伙伴，2：同学校友，9：其他好友
    $sql = '';
    if ($type=="0")
    {
      $sql .= "select circle_id,circle_name,enterprise_no,network_domain from we_circle where enterprise_no=(select eno from we_staff where login_account=?) and 0+circle_id>10000 union all ";
      $para = array($account,$account,$account,$account);
    }
    else
    {
      $para = array($account,$account,$account);
    }
    $sql .= "select circle_id,circle_name,enterprise_no,network_domain from we_circle where create_staff=? and ifnull(enterprise_no,'')='' and 0+circle_id>10000 
      union all select circle_id,circle_name,enterprise_no,network_domain from we_circle a where 
      exists (select 1 from we_circle_staff b where a.circle_id=b.circle_id and b.login_account=?)
      and ifnull(enterprise_no,'')='' and create_staff!=? and 0+a.circle_id>10000";
    $ds = $da->GetData('we_circle',$sql,$para);
    if ($ds && $ds['we_circle']['recordcount'])
    {
      $re = $ds['we_circle']['rows'];
    }
    
    return $re;
  }
}