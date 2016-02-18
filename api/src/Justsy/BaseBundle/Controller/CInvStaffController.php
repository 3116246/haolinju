<?php
namespace Justsy\BaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\DataAccess\SysSeq;

//邀请同事组件
class CInvStaffController extends Controller
{
  //邀请同事
  public function invStaffAction($id, $gname)
  {
    $da = $this->get('we_data_access');
	  $user = $this->get('security.context')->getToken()->getUser();
    //邀请同事加入wefafa
    $tmp = explode("@",$user->getUsername());      
    $tmp = (count($tmp) > 1) ? $tmp[1] : 'fafatime.com';
    //判断邮箱是否是公共邮箱
    $sql = "select count(domain_name) as cnt from we_public_domain where domain_name=?";
    $ds = $da->GetData('we_public_domain',$sql,array($tmp));
    $mailtype = ($ds && $ds['we_public_domain']['rows'][0]['cnt'] > 0) ? "0" : "1";
	  if (empty($id))
	  {
	    $title = $user->ename;
	    $groupid = null;
    }
    else
    {
      //邀请同事加入群组
      $title = $gname;
      $groupid = $id;
    }
	  return $this->render('JustsyBaseBundle:CInvStaff:inv_staff.html.twig', 
	    array('ename'=>$title,'emaildomain'=>$tmp,'mailtype'=>$mailtype,'account'=>$user->getUsername(),
	    'groupid'=>$groupid,'gname'=>$gname));
  }
  //邀请同事保存
  public function invStaffSaveAction()
  {
    $user = $this->get('security.context')->getToken()->getUser();
    $da = $this->get('we_data_access');
    $mails = $this->get("request")->request->get("mails");
    //邀请加入wefafa
    if (empty($mails)) return new Response('0');
    //发送邀请邮件
    $arrMails = explode(",",$mails);
    $invMsg = $this->get("request")->request->get("invMsg");
    $groupid = $this->get("request")->request->get("groupid");
    $gname = $this->get("request")->request->get("gname");
    if (!empty($groupid)) 
    {
      foreach($arrMails as $key=>$value)
      {
        if (empty($value)) continue;
        //邀请加入群组 群id,加入人员帐号
        $para = DES::encrypt("$groupid,$value");
        $activeurl = $this->generateUrl("JustsyBaseBundle_group_invjoin",array('para'=>$para),true);
        $txt = $this->renderView("JustsyBaseBundle:CInvStaff:mail.html.twig",
          array("ename"=>$user->ename,"realName"=>$user->nick_name,"activeurl"=>$activeurl,
          "invMsg"=>$invMsg,'groupid'=>$groupid,'gname'=>$gname));
        Utils::sendMail($this->get('mailer'),"邀请您加入群组【".$gname."】",
          $this->container->getParameter('mailer_user'),null,$value,$txt);
      }
    }
    else
    {
      foreach($arrMails as $key=>$value)
      {
        if (empty($value)) continue;
        $activeurl = $this->generateUrl("JustsyBaseBundle_active_inv_s1",
          array('account'=>DES::encrypt($value),'invacc'=>DES::encrypt($user->getUsername())),true);
        $txt = $this->renderView("JustsyBaseBundle:CInvStaff:mail.html.twig",
          array("ename"=>$user->ename,"realName"=>$user->nick_name,"activeurl"=>$activeurl,
          "invMsg"=>$invMsg,'groupid'=>'','gname'=>''));
        Utils::sendMail($this->get('mailer'),"邀请您加入微发发",
          $this->container->getParameter('mailer_user'),null,$value,$txt);
        //保存邀请信息
        $sql = "select count(1) as cnt from we_invite where invite_send_email=? and invite_recv_email=?";
        $ds = $da->GetData("we_invite",$sql,array((string)$user->getUsername(),(string)$value));
        $para = array();
        if ($ds && $ds["we_invite"]["rows"][0]["cnt"]>0)
        {
          $sql = "update we_invite set last_invite_date=now(),invite_num=invite_num+1 where invite_send_email=? and invite_recv_email=?";
          $para = array((string)$user->getUsername(),(string)$value);
        }
        else
        {
          $sql = "insert into we_invite(invite_send_email,invite_recv_email,invite_num,eno,first_invite_date,last_invite_date) values (?,?,?,?,now(),now())";
          $para = array((string)$user->getUsername(),(string)$value,(integer)1,(string)$user->eno);
        }
        $da->ExecSQL($sql,$para);
      }
    }
    return new Response('1');
  }
  //未激活的人员
  public function getUnActiveStaffAction()
  {
    $account = $this->get('request')->request->get('account');
    $eno = $this->get('request')->request->get('eno');
    $circleid = $this->get('request')->request->get('circleid');
    $re = array();
    $da = $this->get('we_data_access');
    $sql = "select case when length(invite_recv_email)>15 then concat(left(invite_recv_email,14),'....') else invite_recv_email end as invite_recv_email,
      invite_recv_email as show_email 
      from we_invite where invite_send_email=? and eno=? and ifnull(active_date,'')=''";
    if (empty($circleid))
    {
      $ds = $da->GetData("we_invite", $sql, array((string)$account,(string)$eno));
    }
    else
    {
      $ds = $da->GetData("we_invite", $sql, array((string)$account,(string)"c$circleid"));
    }
    if ($ds && $ds['we_invite']['recordcount']>0)
    {
      $re = $ds['we_invite']['rows'];
    }
    $response = new Response(json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  //删除未激活的人员
  public function delStaffAction()
  {
    $invrec = $this->get('request')->request->get('invrec');
    $account = $this->get('request')->request->get('account');
    $da = $this->get('we_data_access');
    $sql = "delete from we_invite where invite_send_email=? and invite_recv_email=?";
    $da->ExecSQL($sql,array((string)$account,(string)$invrec));
    $sql = "delete from we_register where login_account=? and state_id='2'";
    $da->ExecSQL($sql,array((string)$invrec));
    return new Response("1");
  }
  //邀请未激活的人员
  public function reInviteAction()
  {
    $da = $this->get('we_data_access');
    $user = $this->get('security.context')->getToken()->getUser();
    $invrec = $this->get('request')->request->get('invrec');
    $account = $this->get('request')->request->get('account');
    $activeurl = $this->generateUrl("JustsyBaseBundle_active_inv_s1",
      array('account'=>DES::encrypt($invrec),'invacc'=>DES::encrypt($account)),true);
    $txt = $this->renderView("JustsyBaseBundle:CInvStaff:mail.html.twig",
      array("ename"=>$user->ename,"realName"=>$user->nick_name,"activeurl"=>$activeurl,
      "invMsg"=>'','groupid'=>'','gname'=>''));
    Utils::sendMail($this->get('mailer'),"邀请您加入微发发",
      $this->container->getParameter('mailer_user'),null,$invrec,$txt);
    //保存邀请信息
    $sql = "update we_invite set last_invite_date=now(),invite_num=invite_num+1 where invite_send_email=? and invite_recv_email=?";
    $para = array((string)$account,(string)$invrec);
    $da->ExecSQL($sql,$para);
    return new Response("1");
  }
  //邀请加入圈子
  public function invIntoCircleAction($network_domain)
  {
    $re = array();
    $user = $this->get('security.context')->getToken()->getUser();
    $circleId = $user->get_circle_id($network_domain);
    $re['circleId'] = $circleId;
    $da = $this->get('we_data_access');
    $sql = "select circle_name from we_circle where circle_id=?";
    $ds = $da->GetData("we_circle", $sql, array((string)$circleId));
    if ($ds && $ds['we_circle']['recordcount']>0) $re['circle_name'] = $ds['we_circle']['rows'][0]['circle_name'];
    $re['invSendAcc'] = $user->getUsername();
    return $this->render('JustsyBaseBundle:CInvStaff:inv_into_circle.html.twig',$re);
  }
  //根据帐号查询人员姓名
  public function invNickNameAction()
  {
    $re = array('hs'=>'0','name'=>'');
    $account = $this->get('request')->request->get('account');
    $circleId = $this->get('request')->request->get('circleId');
    $da = $this->get('we_data_access');
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
    $resp = new Response(json_encode($re));
    $resp->headers->set('Content-Type', 'text/json');
    return $resp;
  }
  //邀请人员加入圈子
  public function sendCircleInvitationAction()
  {
    $acts = $this->get('request')->request->get('acts');
    $logger = $this->get('logger');
    $da = $this->get('we_data_access');
    $user = $this->get('security.context')->getToken()->getUser();
    $circleId = $this->get("request")->request->get("circleId");
    $circleName = $this->get('request')->request->get('circleName');
    try
    {
      foreach($acts as $key=>$value)
      {
        $invacc = trim($value);
        $sql = "select count(1) as cnt from we_staff where login_account=?";
        $ds = $da->GetData("we_staff",$sql,array((string)$invacc));
        if ($ds && $ds['we_staff']['rows'][0]['cnt']>0)
        {
          //1.帐号存在，直接加入圈子
          //受邀人员帐号,圈子id
          $encode = DES::encrypt("$invacc,$circleId");
          $activeurl = $this->generateUrl("JustsyBaseBundle_component_agreejoincircle",array('para'=>$encode));
          $txt = $this->renderView("JustsyBaseBundle:CInvStaff:circle_invitation.html.twig",
            array("ename"=>$user->ename,"nick_name"=>$user->nick_name,"activeurl"=>$activeurl,'circle_name'=>$circleName));
          //发送站内消息
          $msgId = SysSeq::GetSeqNextValue($da,"we_message","msg_id");
          $sql = "insert into we_message(msg_id,sender,recver,title,content,send_date)values(?,?,?,?,?,now())";
          $da->ExecSQL($sql,array((int)$msgId,(string)$user->getUserName(),(string)$invacc,"邀请加入圈子",$txt));
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
              $encode = DES::encrypt($user->getUserName().",$circleId,$eno");
              $activeurl = $this->generateUrl("JustsyBaseBundle_active_inv_s1",
                array('account'=>DES::encrypt($invacc),'invacc'=>$encode),true);
              $txt = $this->renderView('JustsyBaseBundle:CInvStaff:mail_circle_invitation.html.twig',
                array("ename"=>$user->ename,"nick_name"=>$user->nick_name,"activeurl"=>$activeurl,'circle_name'=>$circleName));
              Utils::sendMail($this->get('mailer'),"邀请您加入圈子【".$circleName."】",
                $this->container->getParameter('mailer_user'),null,$invacc,$txt);
              //保存邀请信息 circleid保存到eno字段，以字母'c'开头
              CInvStaffController::saveWeInvInfo($da, $user->getUserName(), $invacc, $circleId);
            }
            else
            {
              //2.1.2企业未创建
              $sql = "insert into we_register (login_account,ename,credential_path,active_code,ip,email_type,first_reg_date,last_reg_date,register_date,state_id)"
                ." values (?,?,?,?,?,?,now(),now(),now(),'0')";
              $para = array($invacc,'','',strtoupper(substr(uniqid(),3,10)),$_SERVER['REMOTE_ADDR'],'1');
              $da->ExecSQL($sql,$para);
              //发送邮件 帐号,圈子id,邀请发送者帐号 des encode
              $encode = DES::encrypt("$invacc,$circleId,".$user->getUserName());
              $activeurl = $this->generateUrl("JustsyBaseBundle_active_reg_s1",array('account'=>$encode),true);
              $txt = $this->renderView('JustsyBaseBundle:CInvStaff:mail_circle_invitation.html.twig',
                array("ename"=>$user->ename,"nick_name"=>$user->nick_name,"activeurl"=>$activeurl,'circle_name'=>$circleName));
              Utils::sendMail($this->get('mailer'),"邀请您加入圈子【".$circleName."】",
                $this->container->getParameter('mailer_user'),null,$invacc,$txt);
              //保存邀请信息
              CInvStaffController::saveWeInvInfo($da, $user->getUserName(), $invacc, $circleId);
            }
          }
          else
          {
            //2.2公共邮箱
            $sql = "insert into we_register (login_account,ename,credential_path,active_code,ip,email_type,first_reg_date,last_reg_date,register_date,state_id)"
              ." values (?,?,?,?,?,?,now(),now(),now(),'2')";
            $para = array($invacc,'','',strtoupper(substr(uniqid(),3,10)),$_SERVER['REMOTE_ADDR'],'0');
            $da->ExecSQL($sql,$para);
            //发送邮件 帐号,圈子id,邀请发送者帐号 des encode
            $encode = DES::encrypt("$invacc,$circleId,".$user->getUserName());
            $activeurl = $this->generateUrl("JustsyBaseBundle_active_reg_s1",array('account'=>$encode),true);
            $txt = $this->renderView('JustsyBaseBundle:CInvStaff:mail_circle_invitation.html.twig',
              array("ename"=>$user->ename,"nick_name"=>$user->nick_name,"activeurl"=>$activeurl,'circle_name'=>$circleName));
            Utils::sendMail($this->get('mailer'),"邀请您加入圈子【".$circleName."】",
              $this->container->getParameter('mailer_user'),null,$invacc,$txt);
            //保存邀请信息
            CInvStaffController::saveWeInvInfo($da, $user->getUserName(), $invacc, $circleId);
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
  public static function saveWeInvInfo($da, $acc, $invacc, $circleId)
  {
    //保存邀请信息 circleid保存到eno字段，以字母'c'开头
    $sql = "select count(1) as cnt from we_invite where invite_send_email=? and invite_recv_email=? and eno=?";
    $ds = $da->GetData("we_invite",$sql,array((string)$acc,(string)$invacc,(string)"c$circleId"));
    if ($ds && $ds["we_invite"]["rows"][0]["cnt"]>0)
    {
      $sql = "update we_invite set last_invite_date=now(),invite_num=invite_num+1 where invite_send_email=? and invite_recv_email=? and eno=?";
      $para = array((string)$acc,(string)$invacc,(string)"c$circleId");
    }
    else
    {
      $sql = "insert into we_invite(invite_send_email,invite_recv_email,invite_num,eno,first_invite_date,last_invite_date) values (?,?,?,?,now(),now())";
      $para = array((string)$acc,(string)$invacc,(integer)1,(string)"c$circleId");
    }
    $da->ExecSQL($sql,$para);
  }
  //查询邀请加入圈子的人员
  public function invCircleQueryAction()
  {
    $re = array();
    $photourl = $this->container->getParameter('FILE_WEBSERVER_URL');
    $circleId = $this->get('request')->request->get('circleId');
    $name = $this->get('request')->request->get('name');
    $da = $this->get('we_data_access');
    $sql = "select count(1) as cnt from we_staff a where nick_name like concat('%',?,'%') ";
    $sql .= "and not exists (select 1 from we_circle_staff b where a.login_account=b.login_account and b.circle_id=?)";
    $ds = $da->GetData("cnt", $sql, array((string)$name,(string)$circleId));
    $re['totalPage'] = ceil($ds['cnt']['rows'][0]['cnt']/7);
    $sql = "select a.login_account,a.nick_name,a.self_desc,c.ename,";
    $sql .= "concat('".$photourl."',case trim(ifnull(a.photo_path,'')) when '' then null else a.photo_path end) as photo_path ";
    $sql .= "from we_staff a inner join we_enterprise c on a.eno=c.eno where nick_name like concat('%',?,'%') ";
    $sql .= "and not exists (select 1 from we_circle_staff b where a.login_account=b.login_account and b.circle_id=?)";
    $ds = $da->GetData("we_staff", $sql, array((string)$name,(string)$circleId));
    $re['jsonData'] = ($ds && $ds['we_staff']['recordcount']>0) ? $ds['we_staff']['rows'] : array();
    $response = new Response(json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  //邀请人员加入圈子-发送邀请消息
  public function invCircleSendMsgAction()
  {
    $da = $this->get('we_data_access');
    $user = $this->get('security.context')->getToken()->getUser();
    $circleId = $this->get('request')->request->get('circleId');
    $acts = $this->get('request')->request->get('acts');
    $circleName = $this->get('request')->request->get('circleName');
    foreach($acts as $key=>$value)
    {
      //受邀人员帐号,圈子id
      $encode = DES::encrypt("$value,$circleId");
      $activeurl = $this->generateUrl("JustsyBaseBundle_component_agreejoincircle",array('para'=>$encode));
      $txt = $this->renderView("JustsyBaseBundle:CInvStaff:circle_invitation.html.twig",
        array("ename"=>$user->ename,"nick_name"=>$user->nick_name,"activeurl"=>$activeurl,'circle_name'=>$circleName));
      //发送站内消息
      $msgId = SysSeq::GetSeqNextValue($da,"we_message","msg_id");
      $sql = "insert into we_message(msg_id,sender,recver,title,content,send_date)values(?,?,?,?,?,now())";
      $da->ExecSQL($sql,array((int)$msgId,(string)$user->getUserName(),(string)$value,"邀请加入圈子",$txt));
    }
    return new Response("1");
  }
  //收到加入圈子邀请消息，同意加入圈子
  public function agreeJoinCircleAction($para)
  {
    if (empty($para)) return $this->render('JustsyBaseBundle:Error:index.html.twig',array('error'=>'参数错误！'));
    $da = $this->get('we_data_access');
    //受邀人员帐号,圈子id
    $paraArr = explode(",",trim(DES::decrypt($para)));
    //是否有帐号
    $sql = "select nick_name from we_staff where login_account=?";
    $ds = $da->GetData("we_staff", $sql, array((string)$paraArr[0]));
    if (!$ds || $ds['we_staff']['recordcount']==0)
    {
      return $this->render('JustsyBaseBundle:Error:index.html.twig',array('error'=>'您还没有微发发帐号，请先注册！'));
    }
    $nick_name = $ds['we_staff']['rows'][0]['nick_name'];
    //圈子是否存在
    $sql = "select network_domain,circle_name from we_circle where circle_id=?";
    $ds = $da->GetData("we_circle", $sql, array((string)$paraArr[1]));
    if (!$ds || $ds['we_circle']['recordcount']==0)
    {
      return $this->render('JustsyBaseBundle:Error:index.html.twig',array('error'=>'您要加入的圈子不存在！'));
    }
    $network_domain = $ds['we_circle']['rows'][0]['network_domain'];
    $circle_name = $ds['we_circle']['rows'][0]['circle_name'];
    //是否已经加入圈子
    $sql = "select count(1) cnt from we_circle_staff where login_account=? and circle_id=?";
    $ds = $da->GetData("cnt", $sql, array((string)$paraArr[0],(string)$paraArr[1]));
    if ($ds && $ds['cnt']['rows'][0]['cnt']>0)
    {
      return $this->render('JustsyBaseBundle:Error:index.html.twig',array('error'=>'您已经加入该圈子！'));
    }
    $sql = "insert into we_circle_staff (circle_id,login_account,nick_name) values (?,?,?)";
    $da->ExecSQL($sql,array((string)$paraArr[1],(string)$paraArr[0],(string)$nick_name));
    //10－加入外部圈子－5
    $sql = "insert into we_staff_points (login_account,point_type,point_desc,point,point_date) values (?,?,?,?,now())";
    $da->ExecSQL($sql,array((string)$paraArr[0],(string)'10',(string)'成功加入外部圈子'.$circle_name.'，获得积分5',(int)5));
    $backurl = $this->generateUrl("JustsyBaseBundle_enterprise",array('network_domain'=>$network_domain));
    return $this->render('JustsyBaseBundle:Error:success.html.twig',array('backurl'=>$backurl,'backtitle'=>'返回圈子首页'));
  }
}