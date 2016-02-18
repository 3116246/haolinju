<?php

namespace Justsy\BaseBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\Common\DES;

class FeedbackController extends Controller
{   
  public function saveAction()
  {  	 	 
  	//try{
  		$request=$this->get("request");
  	  $user = $this->get('security.context')->getToken()->getUser();
  	  $da = $this->get("we_data_access");
  	  $new_id=\Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_sys_feedback", "id");
  	  $sql = "insert into we_sys_feedback (id,login_account,ip,feedback_con,feedback_date)values(?,?,?,?,now())";
  	  $da->ExecSQL($sql,
  	            array(
  	                   (string)$new_id,(string)$user->getUserName(),"",(string)$request->get("txt")
  	                  )
  	              ); 
  	  //查询出开发公司的圈子id
  	  $sql = "select * from we_circle where network_domain='fafatime.com'"; 
  	  $ds = $da->GetData("tmp",$sql);
  	  $circle_id = $ds["tmp"]["rows"][0]["circle_id"];
  	 	//发公告
  	 	$txt = "用户反馈：".$request->get("txt");
      //发送站内消息
			$msgId = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da,"we_bulletin","bulletin_id");
			$sql = "insert into we_bulletin(bulletin_id,circle_id,group_id,bulletin_date,bulletin_desc)values(?,?,?,now(),?)";
			$da->ExecSQL($sql,array((int)$msgId,(string)$circle_id ,"ALL",$txt));
  	 	  //通知圈子成员
  	 	  $members = $this->notifyCircleMember($da,$circle_id );
  	 	  for($i=0;$i<count($members);$i++)
  	 	  {
  	 	      	$membersrow = $members[$i];
  	 	      	if($membersrow["login_account"]==$user->getUserName()) continue; 
				      $sql = "insert into we_notify(notify_type, msg_id,notify_staff)values('01',?,?)";
				      $da->ExecSQL($sql,array((int)$msgId,(string)(string)$user->getUserName()));
							//向对方发送及时消息
		          //认证码格式：当前人员企业号、帐号、密码（空）、空、空
		          $encode = $user->eno.",".$user->fafa_jid.",,,";
		          $encode = "00442,".DES::encrypt($encode);
		          $url = $this->container->getParameter("FAFA_REG_JID_URL");
		      	  //Utils::sendImMessage($url,"",$user->fafa_jid,$row["fafa_jid"],$txt);
  	 	  }  	              
  	  return new Response("1");
     //}
     //catch(\Exception $e)
     //{
     //    	return new Response("0");
     //}
  }  
  
  public function notifyCircleMember($da,$circleid)
  {
     	$sql = "select a.login_account,b.fafa_jid,b.nick_name from we_circle_staff a,we_staff b where a.login_account=b.login_account and a.circle_id=?";
     	$ds = $da->GetData("dataset",$sql,array((string)$circleid));
     	return $ds["dataset"]["rows"];
  }
}