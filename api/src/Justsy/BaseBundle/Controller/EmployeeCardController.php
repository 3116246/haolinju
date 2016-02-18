<?php

namespace Justsy\BaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Justsy\BaseBundle\Login\UserProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Common\Utils;
class EmployeeCardController extends Controller
{
    public $photo_url = "";
    public $userName = "",$network_domain;
    var $isSelf = false;
    public function indexAction(Request $request)
    {
    	$da = $this->container->get('we_data_access');
    	$this->userName = $request->get("account");
      $user = $this->getUserInfo($this->userName); 
      
      if (empty($user)) return $this->render('JustsyBaseBundle:EmployeeCard:index.html.twig', array("userName" => $this->userName));
      
      $this->photo_url = $this->container->getParameter('FILE_WEBSERVER_URL').$user["photo_path_big"];
      $user["dept_name"] = $user["dept_name"]==null?"[未设置部门]":$user["dept_name"];
      $this->user = $user;
      $this->userName = $user["login_account"];
      $this->network_domain = $request->get("network_domain");
			$level = \Justsy\BaseBundle\Common\ExperienceLevel::getLevel($user["total_point"]);
      $level = (int)($level/10);
      
      $staff_pref = \Justsy\BaseBundle\Business\WeStaff::getPreference($da, $user["login_account"]);
      $staffMgr = new \Justsy\BaseBundle\Management\Staff($da,null,$this->userName,$this->get("logger"));
      $tag = $staffMgr->getTag();
      return $this->render('JustsyBaseBundle:EmployeeCard:index.html.twig', array('this' => $this,'tag'=> $tag,'css_level'=>$level, 'staff_pref' => $staff_pref));
    }
    
    public function contactAction(Request $request)
    {
    	$da = $this->container->get('we_data_access');
    	$this->userName = $request->get("account");
      $user = $this->getUserInfo($this->userName); 
      
      if (empty($user)) return $this->render('JustsyBaseBundle:EmployeeCard:contact_card.html.twig', array("userName" => $this->userName));
      
      $this->photo_url = $this->container->getParameter('FILE_WEBSERVER_URL').$user["photo_path_big"];
      $user["dept_name"] = $user["dept_name"]==null?"[未设置部门]":$user["dept_name"];
      $this->user = $user;
      $this->userName = $user["login_account"];
      $this->network_domain = $request->get("network_domain");
			$level = \Justsy\BaseBundle\Common\ExperienceLevel::getLevel($user["total_point"]);
      $level = (int)($level/10);
      $staffMgr = new \Justsy\BaseBundle\Management\Staff($da,null,$this->userName,$this->get("logger"));
      $tag = $staffMgr->getTag();
      $staff_pref = \Justsy\BaseBundle\Business\WeStaff::getPreference($da, $user["login_account"]);
      
      return $this->render('JustsyBaseBundle:EmployeeCard:contact_card.html.twig', array('this' => $this,'tag'=> $tag,'css_level'=>$level, 'staff_pref' => $staff_pref));
    }    
    
    public function queryAccountAction($empname)
    {
    	  $DataAccess = $this->container->get('we_data_access');
    	  $userNameAry = $this->parseUserName($empname);
    	  $sql = "select a.login_account from we_staff a join we_enterprise b on a.eno=b.eno where a.nick_name=? and b.eshortname=?";
        $params = array((String)$userNameAry[0],(String)$userNameAry[1]);
				$dataset = $DataAccess->GetData("we_staff", $sql, $params);
				$response = new Response("");
				if ($dataset && $dataset["we_staff"]["recordcount"] > 0)
				{
					  $response=new Response($dataset["we_staff"]["rows"][0]["login_account"]);
				}       
			  $response->headers->set('Content-Type', 'text/html');				
				return $response;   	  
    }
    
    private function parseUserName($name)
    {
    	  $ary = explode("{",$name);
    	  if(count($ary)==1)
    	  {
    	  	 $user = $this->get('security.context')->getToken()->getUser();    	  	 
    	  	 return array($ary[0],$user->eshortname);
    	  }
    	  return array($ary[0],str_replace("}","",$ary[1]));
    }
    
    function getUserInfo($username)
    {
    	   $DataAccess = $this->container->get('we_data_access');
    	   $curuser = $this->get('security.context')->getToken()->getUser();
    	   if(Utils::validateEmail($username))
    	   {
    	      //帐号
    	      $sqls = (
					      "select f_checkAttentionWithAccount(?,a.login_account) attention,a.login_account, a.nick_name, a.photo_path_big, a.password, a.dept_id, a.eno,a.fafa_jid,a.duty,a.work_phone,a.mobile,date_format(a.birthday,'%Y-%c-%d') birthday, b.edomain, b.ename, b.eshortname, c.dept_name ,
					ifnull(a.self_desc,'未设置个性签名') self_desc,ifnull(a.we_level, 0) we_level,b.vip_level,a.auth_level,a.total_point, a.attenstaff_num, a.fans_num, a.publish_num,ifnull(d.id,0) addcard from we_staff a
					  join we_enterprise b on a.eno=b.eno
					  left join we_department c on a.eno=c.eno and a.dept_id=c.dept_id 
					  left join we_addrlist_main d on d.owner=? and d.typeid='M001' and d.addr_account=a.login_account
					where a.login_account=? "
					    );
					    $params = array(
					      $curuser->getUsername(),
					      $curuser->getUsername(),
					      (String)$username
					    );
    	   }
    	   else
    	   {
         $userNameAry = $this->parseUserName($username);	
				 $sqls = (
					      "select f_checkAttentionWithAccount(?,a.login_account) attention,a.login_account, a.nick_name, a.photo_path_big, a.password, a.dept_id, a.eno,a.fafa_jid,a.duty,a.work_phone,a.mobile,date_format(a.birthday,'%Y-%c-%d') birthday, b.edomain, b.ename, b.eshortname, c.dept_name ,
					ifnull(a.self_desc,'未设置个性签名') self_desc,ifnull(a.we_level, 0) we_level,b.vip_level,a.auth_level,a.total_point, a.attenstaff_num, a.fans_num, a.publish_num,ifnull(d.id,0) addcard from we_staff a
					  join we_enterprise b on a.eno=b.eno
					  left join we_department c on a.eno=c.eno and a.dept_id=c.dept_id 
					  left join we_addrlist_main d on d.owner=? and d.typeid='M001' and d.addr_account=a.login_account
					where a.nick_name=? and b.eshortname=?"
					    );
					    $params = array(
					    	$curuser->getUsername(),
					      $curuser->getUsername(),
					      (String)$userNameAry[0],
					      (String)$userNameAry[1]
					    );
					}
					$dataset = $DataAccess->GetData("we_staff", $sqls, $params);					
					if ($dataset && $dataset["we_staff"]["recordcount"] > 0)
					{
					    	$this->isSelf = $curuser->getUsername()==$dataset["we_staff"]["rows"][0]["login_account"];
                $dataset["we_staff"]["rows"][0]["vip_level"] = $dataset["we_staff"]["rows"][0]["auth_level"]!='S'? \Justsy\BaseBundle\Common\ExperienceLevel::getLevel($dataset["we_staff"]["rows"][0]["total_point"]) : "1";
					      return $dataset["we_staff"]["rows"][0];
					}
					else
					{
					      return "";
					}        
    }
    //取消对指定人员的关注 
    public function cancelAttentionAction($attenaccount)
    {
    	 $curuser = $this->get('security.context')->getToken()->getUser();
    	 $isme=$curuser->getUsername();
       $da = $this->get('we_data_access');
       $attention_type='01'; //关注人员
       $sql = "delete from we_staff_atten where login_account=? and atten_type=? and atten_id=?";
       $para = array((string)$isme,(string)$attention_type,(string)$attenaccount);
       $da->ExecSQL($sql,$para); 
       //变更版本号
       $eno = $curuser->eno;
       $verchange = new \Justsy\BaseBundle\Management\VersionChange($da,$this->get("logger"));
    	 $result = $verchange->SetVersionChange(1,$attenaccount,$eno);
    	 $result = $verchange->SetVersionChange(1,$isme,$eno);    	        
       //取消关注-0.2分
       $sql = "insert into we_staff_points (login_account,point_type,point_desc,point,point_date)
          values (?,?,?,?,now())";
       $para = array(
          (string)$isme,
          (string)'08',
          (string)'取消关注'.$attenaccount.'，扣除积分0.2',
          (float)-0.2); 
       $da->ExecSQL($sql,$para);
       //      
       $response = new Response(("{\"succeed\":1,\"both\":0}"));
			 $response->headers->set('Content-Type', 'text/json');
			 return $response;       
    }
    //解除互相关注。主要用于外部程序调用（如：客户端删除好友时）
    //$attenaccount:加密内容。解除关注的双方帐号，用，隔开。
    public function releaseTogetherAttentionAction($attenaccount)
    {
    	 $da = $this->get('we_data_access');
    	 $paras =  explode(",", DES::decrypt($attenaccount));
    	 if(count($paras)!=2) return new Response(("{\"succeed\":0,\"msg\":\"parameter error\"}"));
    	 $sql ="select (select login_account from we_staff where fafa_jid=?) account1,(select login_account from we_staff where fafa_jid=?) account2";
    	 $dataset = $da->GetData("accounts",$sql,array((string)$paras[0],(string)$paras[1]));
    	 if(empty($dataset)|| $dataset["accounts"]["recordcount"] == 0)
    	 {
    	     	return new Response(("{\"succeed\":1}"));
    	 }
    	 
    	 $isme = $dataset["accounts"]["rows"][0]["account1"];
    	 $attenaccount = $dataset["accounts"]["rows"][0]["account2"];
    	 
       $da = $this->get('we_data_access');
       $attention_type='01'; //关注人员
       $sqls =array( "delete from we_staff_atten where login_account=? and atten_type=? and atten_id=?",
                     "delete from we_staff_atten where login_account=? and atten_type=? and atten_id=?"
                   );
       $para = array();
       $para[] = array((string)$isme,(string)$attention_type,(string)$attenaccount);
       $para[] = array((string)$attenaccount,(string)$attention_type,(string)$isme);
       $da->ExecSQLs($sqls,$para);        
       $response = new Response(("{\"succeed\":1,\"both\":0}"));
			 $response->headers->set('Content-Type', 'text/json');
			 return $response;        
    }
    //关注指定的人员
    public function attentionAction($attenaccount)
    {
    	 $this->get("logger")->err("---------------------------2222222222222222------------------");
    	 $curuser = $this->get('security.context')->getToken()->getUser();
    	 $isme=$curuser->getUsername();
       $da = $this->get('we_data_access');
       
       $staffMgr = new \Justsy\BaseBundle\Management\Staff($da,$this->get('we_data_access_im'),$curuser->getUserName(),$this->get("logger"));
       $staffMgr->attentionTo($attenaccount);
       //变更版本信息
       $eno = $curuser->eno;
       $this->get("logger")->err("----------------------------eno:".$eno."----------------------");
       $verchange = new \Justsy\BaseBundle\Management\VersionChange($da,$this->get("logger"));
    	 $result = $verchange->SetVersionChange(1,$attenaccount,$eno);
    	 $result = $verchange->SetVersionChange(1,$isme,$eno);
    	 
    	 $this->get("logger")->err("-----------------444444-------------------");
    	        
       //发送关注消息
       $msgId = SysSeq::GetSeqNextValue($da,"we_message","msg_id");
       $sql = "insert into we_message(msg_id,sender,recver,send_date,title,content)values(?,?,?,now(),?,?)";
       $da->ExecSQL($sql,array((int)$msgId,(string)$isme,(string)$attenaccount,"好友请求","你的好友<a style='cursor:pointer;color:#1A65A5' class='employee_name' login_account='$isme'>".$curuser->nick_name."</a>关注了你"));

       //查询是否互关注
       $sql = "select f_checkAttentionWithAccount(?,?) cnt";
       $ds = $da->GetData("both", $sql, array((string)$isme,(string)$attenaccount));
       $IsBoth =  $ds["both"]["rows"][0]["cnt"];
       $im_sender = $this->container->getParameter('im_sender');
       $fafa_jid = Utils::getJidByAccount($da, $attenaccount);
             
       if($IsBoth!=2)
       {
         //发送即时消息
         $message = "您的好友 ".Utils::makeHTMLElementTag("employee",$curuser->fafa_jid,$curuser->nick_name)." 关注了您"; 
         $link = $this->generateUrl("JustsyBaseBundle_component_emp_attention",array("attenaccount"=> $isme),true);
         $linkButtons=Utils::makeBusButton(array(array("code"=>"action","text"=>"关注TA","value"=> "atten")));
         Utils::sendImMessage($im_sender,$fafa_jid,"好友请求",$message,$this->container, $link,$linkButtons,false,Utils::$systemmessage_code);       	
         $response = new Response(("{\"succeed\":1,\"both\":$IsBoth}"));
         //提醒交换名片
         $msg_id = SysSeq::GetSeqNextValue($da,"we_message","msg_id");
         $sql="insert into we_message (msg_id,sender,recver,send_date,title,content,msg_type) values(?,?,?,now(),?,?,'02')";
         $params=array($msg_id,$isme,$attenaccount,'好友请求',"<a login_account='".$isme."' class='account_baseinfo'>".$curuser->nick_name."</a>希望与您成为好友");
         $da->ExecSQL($sql,$params);
       }
       else
       {
       	  //互相添加好友          
          $staffMgr->bothAddFriend($this->container,$attenaccount);
          //加入对方的人脉圈子
         $msg_id = SysSeq::GetSeqNextValue($da,"we_message","msg_id");
         $sql="insert into we_message (msg_id,sender,recver,send_date,title,content,msg_type) values(?,?,?,now(),?,?,'02')";
         $params=array($msg_id,$isme,$attenaccount,'好友消息',"<a login_account='".$isme."' class='account_baseinfo'>".$curuser->nick_name."</a>与您成为了好友，并进入了您的人脉圈");
         $da->ExecSQL($sql,$params);
         $response = new Response("{\"succeed\":1,\"both\":$IsBoth,\"msg\":\"\"}");
       }
			 $response->headers->set('Content-Type', 'text/json');
			 return $response;     
    }
    //查询指定人员的关注的人员列表。$topCount为-1时表示查询该帐号关注的所有
    //返回html table
    public function queryAttentionAction($account,$topCount)
    {
    	 $attention_type='01'; //关注人员
    	 $result = "<div class='rightboxone clearfix' style='padding-left:8px;'><ul class='personalhomelist'>";
    	 $sql = "select B.login_account, B.photo_path,B.nick_name from we_staff_atten A,we_staff B where A.atten_id=B.login_account and A.login_account=? and atten_type=? and not exists(select 1 from we_micro_account where number= A.atten_id)";
    	 $da = $this->get('we_data_access');
    	 if(strlen($topCount)>0 || $topCount!="-1")
    	 {
    	    $da->PageSize=(integer)$topCount;
    	    $da->PageIndex=0;
    	 }    	 
    	 $ds = $da->GetData("both", $sql, array((string)$account,(string)$attention_type));
    	 $rs = $ds["both"]["rows"];
    	 if(count($rs)==0)
    	   $result .= "<li><div style='width:100px'>没有关注好友</div></li>";
    	 for($i=0;$i<count($rs); $i++)
    	 {    	 	   
    	 	   if(empty($rs[$i]["photo_path"]))
    	 	       $imgpath = "/bundles/fafatimewebase/images/no_photo.png";
    	 	   else
    	 	       $imgpath = $this->container->getParameter('FILE_WEBSERVER_URL').$rs[$i]["photo_path"];
    	     $result .= "<li style='margin-right:8px;margin-left:8px;'><img onerror=\"this.src='/bundles/fafatimewebase/images/no_photo.png'\" width=48 height=48 src='$imgpath'><span><a class='employee_name' style='width:48px;display:block;white-space: nowrap;text-overflow: ellipsis; overflow: hidden;' login_account='".$rs[$i]["login_account"]."'>".$rs[$i]["nick_name"]."</a></span></li>";
    	 }
    	 $result .= "</ul></div>";
    	 $result .= "<div id='meattentioncount' style='display:none'>".$ds["both"]["recordcount"]."</div>";
    	 $response = new Response($result);
			 $response->headers->set('Content-Type', 'text/html');
			 return $response; 
    }
    
    //查询指定人员的粉丝列表。$topCount为-1时表示查询该帐号所有粉丝
    //返回html table
    public function queryAttentionThisAction($account,$topCount)
    {
    	 $attention_type='01'; //关注人员
    	 $result = "<div class='rightboxone clearfix' style='padding-left:8px;'><ul class='personalhomelist'>";
    	 $sql = "select B.login_account, B.photo_path,B.nick_name from we_staff_atten A,we_staff B where A.login_account=B.login_account and atten_id=? and atten_type=? and not exists(select 1 from we_micro_account where number= A.login_account)";
    	    
    	 $da = $this->get('we_data_access');
    	 if(strlen($topCount)>0 || $topCount!="-1")
    	 {
    	    $da->PageSize=(integer)$topCount;
    	    $da->PageIndex=0;
    	 }     	 
    	 $ds = $da->GetData("both", $sql, array((string)$account,(string)$attention_type));
    	 $rs = $ds["both"]["rows"];
    	 if(count($rs)==0)
    	   $result .= "<li><div style='width:100px'>还没有粉丝</div></li>";
    	 for($i=0;$i<count($rs); $i++)
    	 {
    	 	   if(empty($rs[$i]["photo_path"]))
    	 	       $imgpath = "/bundles/fafatimewebase/images/no_photo.png";
    	 	   else
    	 	       $imgpath = $this->container->getParameter('FILE_WEBSERVER_URL').$rs[$i]["photo_path"];
    	     $result .= "<li style='margin-right:8px;margin-left:8px;'><img onerror=\"this.src='/bundles/fafatimewebase/images/no_photo.png'\" width=48 height=48 src='$imgpath'><span><a style='width:48px;display:block;white-space: nowrap;text-overflow: ellipsis; overflow: hidden;' class='employee_name' login_account='".$rs[$i]["login_account"]."'>".$rs[$i]["nick_name"]."</a></span></li>";
    	 }
    	 $result .= "</ul></div>";  	 
    	 $result .= "<div id='attentionmecount' style='display:none'>".$ds["both"]["recordcount"]."</div>";
    	 $response = new Response($result);
			 $response->headers->set('Content-Type', 'text/html');
			 return $response;    
    }    
}
