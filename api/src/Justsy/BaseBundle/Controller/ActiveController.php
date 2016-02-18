<?php

namespace Justsy\BaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Management\Enterprise;
use Justsy\BaseBundle\Login\UserSession;
use Justsy\BaseBundle\Login\UserProvider;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Justsy\BaseBundle\Management\MicroAccountMgr;
use Justsy\InterfaceBundle\Common\ReturnCode;

class ActiveController extends Controller
{
  public $account,$realName,$passWord,$ename,$mails,$emaildomain,$eno,$circleName,$invMsg,$attenMember,$mobile;
  //邮箱类型 0：公共邮箱 1：企业邮箱
  public $mailtype;
  //是否是新注册企业
  public $isNew;
  //邀请发起人员 被邀请对象激活以后增加邀请人积分
  public $invstaff;
  //邀请加入圈子
  public $circleId;
  //邀请发送人企业
  public $inv_ename;
  //企业简称
  public $eshortname;
  //注册类型
  public $actype;
  public $duty;


  //第三方注册(用于微信、ＱＱ注册)
  public function ThirdRegister($eno,$login_account,$nick_name,$ldap_uid=null)
  {    
  	  $da = $this->get('we_data_access');
	    $da_im = $this->get('we_data_access_im');
	    $logger = $this->get('logger');
	    $success = true;
	    $resultdata = array();   
      $password=rand(1000000,999999); //账号后六位
      if(strpos($login_account, "@")===false)
        $login_account = $login_account."@fafatime.com"; 
      //判断用户是否已经注册
      $staffMgr = new \Justsy\BaseBundle\Management\Staff($da,$this->get('we_data_access_im'),$login_account,$this->get("logger"),$this->container);
      $staffdata = $staffMgr->getInfo();
      if (!empty($staffdata)){
     	  $logger->err("已经注册");
     	  $resultdata = array("success"=>false,"msg"=>"用户账号".$login_account."已被注册");
     	  return $resultdata;
      } 
      $re = $staffMgr->createstaff(array(
        
      ));
		  return $re;
  }

public function doSave($params)
{
  	$isSendMessage = isset($params["isSendMessage"])? $params["isSendMessage"]:""; //是否向圈子成员发送加入通知。一般通过ldap直接登录或导入的帐号不需要通知
  	//是否加入默认圈子
  	$indefaultgroup = isset($paras["indefaultgroup"]) ? $paras["indefaultgroup"] : null; //是否加入部门默认群组
    $para_deptid = isset($params["deptid"])? $params["deptid"] : "";
    $ldap_uid = isset($params["ldap_uid"]) ? $params["ldap_uid"] : null;
    //互为好友标志
    $mutual = isset($params["mutual"]) ? $params["mutual"] : null;  //是否和部门成员自动成员好友
    $fafa_deptid = "";
    //$this->getPara2($params);
    return $this->doBatchSave(null,array($params));
}

//批量注册 
//人员信息格式：[{"realName":"","account":"","sex":"","deptid":"","duty":"","mobile":"","ldap_uid":"","passWord":""}]
public function doBatchSave($eno,$params)
{
    $da = $this->get('we_data_access');
    $da_im = $this->get('we_data_access_im');
    $logger = $this->get('logger');
    $deploy_mode = $this->container->getParameter('deploy_mode');
    //获取缓存的企业信息
    $enobj = new Enterprise($da,$logger,$this->container);
    $domain =  $this->container->getParameter('edomain');
    if(empty($eno)&&$deploy_mode=="E")
    {
      	//获取配置的企业号
      	$eno = $this->container->getParameter("ENO");
    }
    else if(empty($eno))
    {
      	return Utils::WrapResultError("eno参数不能为空");
    }
	 
    $en_row = $enobj->getInfo($eno);
    $circleId = $en_row['circle_id'];
	$eno_level=$en_row["eno_level"];
	$eno_vip=empty($en_row["vip_level"])? 1 : $en_row["vip_level"];	   
	
	$deptMap = array();
	$dept_microaccountMap = array();
	$weStaffSql = 'insert into we_staff (login_account,eno,password,nick_name,photo_path,state_id,fafa_jid,dept_id,
	        photo_path_small,photo_path_big,openid,register_date,active_date,t_code,auth_level,duty,ldap_uid,mobile,mobile_bind,sex_id)
	        values';   //人员表sql
	$weCircleSql='insert into we_circle_staff (circle_id,login_account,nick_name)values';    //圈子成员sql
	$imEmployeeSql = 'insert into im_employee (employeeid, deptid, loginname, password, employeename,spell)values';//人员表sql
	$imUserSql = 'insert into users (username, password, created_at)values';  //用户表Sql
	$imMicroSql = 'insert into im_microaccount_memebr(employeeid,microaccount,lastreadid,subscribedate)values';//关注公众号sql
	$imRosterSql ='insert into rosterusers (username,jid,nick,subscription,ask,askmessage,server,subscribe,type,created_at)values';//部门好友sql
	$imMsgReadSql = 'insert into im_b_msg_read (employeeid, lastid, readdatetime) values';
	$weStaffSqlValues=array();
	$weCircleSqlValues=array();
	$imEmployeeSqlValues=array();
	$imUserSqlValues=array();
	$imMicroSqlValues=array();
	$imRosterSqlValues=array();
	$imMsgReadSqlValues=array();

	$c_count = count($params);
    $start_jid = SysSeq::GetSeqNextValue($da,"we_staff","fafa_jid",$c_count);
    //$logger->err(" ins start {$c_count}:".microtime(true));
    $maxReadMsgSql = 'select max(id) cnt from im_b_msg';
    $mds = $da_im->GetData('m',$maxReadMsgSql,array());
    $maxReadValue =count($mds['m']['rows']) >0 ? $mds['m']['rows'][0]['cnt'] : 0;

	$deptinfo = new \Justsy\BaseBundle\Management\Dept($da,$da_im,$this->container);	
	for ($i=0; $i < $c_count; $i++) 
	{
				$staff = $params[$i];
			    $login_account = $staff["account"];
			    $para_deptid   = isset($staff["deptid"])?$staff["deptid"]:"";
			    $ldap_uid      = isset($staff["ldap_uid"])?$staff["ldap_uid"]: "";
			    $passWord      = $staff["passWord"];
			    $mobile        = isset($staff["mobile"]) ? $staff["mobile"] : "";
			    $duty          = isset($staff["duty"]) ? $staff["duty"] : "";
			    $sex          = isset($staff["sex"]) ? $staff["sex"] : "";
			    $realName      = str_replace('\'', '\'\'', $staff["realName"]);
			    $im_deptid = "";
				if(!empty($para_deptid) && preg_match("/[A-Za-z\x80-\xff]/",$para_deptid))
			  	{
			  		if(isset($deptMap[$para_deptid]))
			  		{
			  			$im_deptid   = $deptMap[$para_deptid]["fafa_deptid"];
			  			$para_deptid = $deptMap[$para_deptid]["deptid"];	  			
			  		}
			  		else
			  		{
				  		$deptMap[$para_deptid] = array();
				    	//如果是部门名称，获取对应的部门编号
				    	$deptAry = $deptinfo->getIdByName($eno,$para_deptid);
				    	if(empty($deptAry)) $para_deptid = "";
				    	else
				    	{
				    		$deptMap[$para_deptid] = $deptAry;//缓存	
							$para_deptid = $deptAry["deptid"];
				    		$im_deptid   = $deptAry["fafa_deptid"];		    			    		
				        	$microaccountAry = array();
				        	$mac = new \Justsy\BaseBundle\Management\MicroAccountMgr($da,$da_im,"admin@".$domain,$this->get("logger"),$this->container);
	  						$list = $mac->getListByDept($para_deptid);
							for($ic=0; $ic<count($list); $ic++)
							{
								if( $list[$ic]["type"]=="1"  || ($list[$ic]["type"]=="0" && $list[$ic]["concern_approval"]=="1"))
							  		$microaccountAry[]=$list[$ic]["jid"];
							}
							$dept_microaccountMap[$para_deptid] =  $microaccountAry;	
		    		
				    	}
			    	}
			  	}
			  	else if(!empty($para_deptid))
			  	{
			  		if(!isset($deptMap[$para_deptid]))
			  		{
			  			$deptMap[$para_deptid]=array('fafa_deptid'=>$para_deptid,'deptid'=>$para_deptid);
			  			$im_deptid = $para_deptid;
						$microaccountAry = array();
				       	$mac = new \Justsy\BaseBundle\Management\MicroAccountMgr($da,$da_im,"admin@".$domain,$this->get("logger"),$this->container);
	  					$list = $mac->getListByDept($para_deptid);
						for($ix=0; $ix<count($list); $ix++)
						{
							if( $list[$ix]["type"]=="1"  || ($list[$ix]["type"]=="0" && $list[$ix]["concern_approval"]=="1"))
							  	$microaccountAry[]=$list[$ix]["jid"];
						}
						$dept_microaccountMap[$para_deptid] =  $microaccountAry;			  		
			  		}
			  		else
			  		{
			  			$im_deptid = $deptMap[$para_deptid]["fafa_deptid"];
			  		}
			  	}
			  	else if ( empty($para_deptid))
			    {
			    	if(isset($deptMap["defaultdeptid"]))
			  		{
			  			$para_deptid = $deptMap["defaultdeptid"]["dept_id"];
			  			$im_deptid   = $deptMap["defaultdeptid"]["fafa_deptid"];
			  		}
			  		else
			  		{
			        	$para_deptid = $this->getDeptId($eno);
			        	$deptMap["defaultdeptid"] = $para_deptid;
			        	$im_deptid   = $para_deptid["fafa_deptid"];  
			        	$para_deptid = $para_deptid["dept_id"];		
			        	$microaccountAry = array();
			        	$mac = new \Justsy\BaseBundle\Management\MicroAccountMgr($da,$da_im,"admin@".$domain,$this->get("logger"),$this->container);
						$list = $mac->getListByDept($para_deptid);
						for($iz=0; $iz<count($list); $iz++)
						{
							if( $list[$iz]["type"]=="1"  || ($list[$iz]["type"]=="0" && $list[$iz]["concern_approval"]=="1"))
						  		$microaccountAry[]=$list[$iz]["jid"];
						}
						$dept_microaccountMap[$para_deptid] =  $microaccountAry;      	      	
			    	}
			    }
			  	if ( !strpos($login_account,"@"))
			  	{
				    if(empty($ldap_uid) && !Utils::validateMobile($login_account))
				    {
				    	$ldap_uid = $login_account;
				    }
				    $login_account .= "@".$domain;
			  	}
			  	$jid = $start_jid++;
		      	$jid .= "-".$eno."@".$domain;
		      	//t_code
		      	$t_code = DES::encrypt($passWord);
		      	//openid
		      	$openid=md5($eno.$login_account);
				//生成密码
		      	$user = new UserSession($login_account, $passWord, $login_account, array("ROLE_USER"));
		      	$factory = $this->get("security.encoder_factory");
		      	$encoder = $factory->getEncoder($user);
		      	$pwd = $encoder->encodePassword($passWord,$user->getSalt());      
		      	//获取人员拼音
		      	$spell = Utils::Pinyin($realName);	
			    //业务处理
				$weStaffSqlValues[]   ='(\''.$login_account.'\',\''.$eno.'\',\''.$pwd.'\',\''.$realName.'\',\'\',\'1\',\''.$jid.'\',\''.$para_deptid.'\',\'\',\'\',\''.$openid.'\',now(),now(),\''.$t_code.'\',\''.$eno_level.$eno_vip.'\',\''.$duty.'\',\''.$ldap_uid.'\','.(empty($mobile)?'null':'\''.$mobile.'\'').','.(empty($mobile)?'null':'\''.$mobile.'\'').',\''.$sex.'\')';
				$weCircleSqlValues[]  ='(\''.$circleId.'\',\''.$login_account.'\',\''.$realName.'\')';
				$imEmployeeSqlValues[]='(\''.$jid.'\', \''.$im_deptid.'\', \''.$jid.'\', \''.$t_code.'\', \''.$realName.'\',\''.$spell.'\')';
				$imUserSqlValues[]    ='(\''.$jid.'\', \''.$t_code.'\', now())';
				$imMsgReadSqlValues[] = '(\''.$jid.'\', '.$maxReadValue.', now())';
				if(isset($dept_microaccountMap[$para_deptid]))
				{
					$cnt = count($dept_microaccountMap[$para_deptid]);
					for ($si=0; $si < $cnt; $si++) {
						$imMicroSqlValues[]   ='(\''.$jid.'\',\''.$dept_microaccountMap[$para_deptid][$si].'\',0,\''.$now.'\')';
					}
				}
	}
	if(!empty($weStaffSqlValues))
	{
			    $da->ExecSQLs(array(
			    	$weStaffSql.implode(",", $weStaffSqlValues),
			    	$weCircleSql.implode(",", $weCircleSqlValues)
			    	),array());
			    //$logger->err("sql3:".microtime());
			    $da_im->ExecSQLs(array(
			    	$imEmployeeSql.implode(",", $imEmployeeSqlValues),
			    	$imUserSql.implode(",", $imUserSqlValues),
			    	$imMsgReadSql.implode(",", $imMsgReadSqlValues)
			    	),array());
			    //$logger->err("sql4:".microtime());
			    //$logger->err("sql5:".microtime());
			    if(!empty($imMicroSqlValues))
			    	$da_im->ExecSQL($imMicroSql.implode(",", $imMicroSqlValues),array());
			    $ver = new \Justsy\BaseBundle\Management\VersionChange($da,$logger,$this->container);
				foreach ($deptMap as $key => $value) 
				{
					$fafa_deptid = $value['fafa_deptid'];
					$sql = 'call dept_emp_stat(\''.$fafa_deptid.'\')';
					$da_im->ExecSQL($sql);
					$deptdata = $deptinfo->getinfo($fafa_deptid);
					if(!empty($deptdata))
					{
						if((int)$deptdata['friend']==1)
						{
							//自动创建部门好友
							$deptinfo->setFriendByDept($fafa_deptid);
						}
						//判断并设置默认群组成员
						$deptinfo->setGroupMemberByDept($fafa_deptid);
						$ver->deptchange($fafa_deptid);
					}
				}			    
	}
	unset($deptSqlValues);
	unset($imDeptSqlValues);	
    //$logger->err("all threads finish:".microtime(true));
    return Utils::WrapResultOK("");
}

  //保存文件
  private function saveFile($path, $dm)
  {
    $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
    $doc->setName(basename($path));
    $doc->setFile($path);
    $dm->persist($doc);
    $dm->flush();
    unlink($path);
    return $doc->getId();
  }
  
  //检查在企业内部是否有重名
  public function checkStaffNameAction()
  {
    $request = $this->get('request');
    $rn = $request->request->get('realname');
    $eno = $request->request->get('eno');
    $da = $this->get('we_data_access');
    $sql = "select nick_name from we_staff where eno=? and nick_name=?";
    $ds = $da->GetData('we_staff',$sql,array((string)$eno,(string)$rn));
    $result = '';
    if ($ds && $ds['we_staff']['recordcount'] > 0)
    {
      $result = $ds['we_staff']['rows'][0]['nick_name'];
    }
    return new Response($result);
  }
  
  //发送邀请邮件
  private function sendMail($eno)
  {
    if (empty($this->mails)) return;
    $da = $this->get('we_data_access');
    $user = $this->get('security.context')->getToken()->getUser();
    //发送邀请邮件
    $arrMails = explode(",",$this->mails);
    foreach($arrMails as $key=>$value)
    {
      if (empty($value)) continue;
      $activeurl = $this->generateUrl("JustsyBaseBundle_active_inv_s1",
        array('account'=>DES::encrypt($value),'invacc'=>DES::encrypt($this->account)),true);
      $txt = $this->renderView("JustsyBaseBundle:Active:mail.html.twig",
          array("ename"=>$this->ename,"realName"=>$this->realName,"activeurl"=>$activeurl,"invMsg"=>$this->invMsg));
      $title = $user->nick_name." 邀请您加入 ".$user->ename." 协作网络";
      Utils::saveMail($da,$this->account,$value,$title,$txt,$eno);
      //Utils::sendMail($this->get('mailer'),$title,$this->container->getParameter('mailer_user'),null,$value,$txt);
      //保存邀请信息
      $sql = "select count(1) as cnt from we_invite where invite_send_email=? and invite_recv_email=? and eno=?";
      $ds = $da->GetData("we_invite",$sql,array((string)$this->account,(string)$value,(string)$eno));
      $para = array();
      if ($ds && $ds["we_invite"]["rows"][0]["cnt"]>0)
      {
        $sql = "update we_invite set last_invite_date=now(),invite_num=invite_num+1 where invite_send_email=? and invite_recv_email=? and eno=?";
        $para = array((string)$this->account,(string)$value,(string)$eno);
      }
      else
      {
        $sql = "insert into we_invite(invite_send_email,invite_recv_email,invite_num,eno,inv_title,inv_content,active_addr,
          first_invite_date,last_invite_date,invate_rela) values (?,?,?,?,?,?,?,now(),now(),'0')";
        $para = array((string)$this->account,(string)$value,(integer)1,(string)$eno,(string)$title,$txt,$activeurl);
      }
      $da->ExecSQL($sql,$para);
    }
  }
  //获取页面参数
  private function getPara()
  {
    $request = $this->get("request");
    $this->account = $request->request->get("account");
    $this->realName = $request->request->get("realName");
    $this->passWord = $request->request->get("passWord");
    $authobj = new Enterprise($this->get('we_data_access'),$this->get("logger"),$this->container);
    $authConfig =   $authobj->getUserAuth();
    $ssoauthmodule=$authConfig["ssoauthmodule"];    
    if(!empty($ssoauthmodule) && $ssoauthmodule=="WefafaMd5Auth")
    {
      $this->passWord = strtoupper(md5($this->passWord));
    }
    $this->ename = $request->request->get("ename");
    $this->mails = $request->request->get("mails");
    $this->emaildomain = $this->getSubDomain($this->account);
    $this->eno = $request->request->get("eno");
    $this->isNew = $request->request->get("isNew");
    $this->mailtype = $request->request->get("mailtype");
    $this->circleName = $request->request->get("circleName");
    $this->invMsg = $request->request->get("invMsg");
    $this->attenMember = $request->request->get("attens");
    $this->invMsg = $request->request->get("invMsg");
    $this->invstaff = $request->request->get("invstaff");
    $this->circleId = $request->request->get("circleId");
    $this->eshortname = $request->request->get("eshortname");
    $this->actype=$request->request->get("actype");
    $this->duty=$request->request->get('duty');
  }
  private function getPara2($params)
  {
  	$this->account = isset($params["account"])?$params["account"]:'';
    $this->realName =isset($params["realName"])?$params["realName"]:'';
    $this->passWord =isset($params["passWord"])?$params["passWord"]:'';
    $authobj = new Enterprise($this->get('we_data_access'),$this->get("logger"),$this->container);
    $authConfig =   $authobj->getUserAuth();
    $ssoauthmodule=$authConfig["ssoauthmodule"];  
    if(!empty($ssoauthmodule) && $ssoauthmodule=="WefafaMd5Auth")
    {
      $this->passWord = strtoupper(md5($this->passWord));
    }    
    $this->ename =isset($params["ename"])?$params["ename"]:'';
    $this->mails =isset($params["mails"])?$params["mails"]:'';
    $this->emaildomain = $this->getSubDomain($this->account);
    $this->eno =isset($params["eno"])?$params["eno"]:'';
    $this->isNew =isset($params["isNew"])?$params["isNew"]:'';
    $this->mailtype =isset($params["mailtype"])?$params["mailtype"]:'';
    $this->circleName =isset($params["circleName"])?$params["circleName"]:'';
    $this->invMsg = isset($params["invMsg"])?$params["invMsg"]:'';
    $this->attenMember =isset($params["attens"])?$params["attens"]:'';
    $this->invMsg =isset($params["invMsg"])?$params["invMsg"]:'';
    $this->invstaff =isset($params["invstaff"])?$params["invstaff"]:'';
    $this->circleId =isset($params["circleId"])?$params["circleId"]:'';
    $this->eshortname =isset($params["eshortname"])?$params["eshortname"]:'';
    $this->actype=isset($params["actype"])?$params["actype"]:'';
    $this->duty=isset($params["duty"])?$params["duty"]:'';
    $this->mobile=isset($params["mobile"])?$params["mobile"]:'';
    $this->import=isset($params["import"])?$params["import"]:'';
  }
  private function getSubDomain($account)
  {
    $re = '';
    $tmp = explode("@",$account);
    if (count($tmp) > 1) $re = $tmp[1];
    return $re;
  }
  
  //根据企业号获得部门id
  private function getDeptId($eno){
  	$deptinfo = new \Justsy\BaseBundle\Management\Dept($this->get('we_data_access'),$this->get('we_data_access_im'),$this->container);
    return $deptinfo->getDefaultDept($eno);
  }
  
}