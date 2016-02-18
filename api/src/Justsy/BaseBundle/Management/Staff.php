<?php

namespace Justsy\BaseBundle\Management;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Management\Identify;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Login\UserSession;
use Justsy\BaseBundle\Management\MicroAccountMgr;
use Justsy\BaseBundle\Common\Cache_Enterprise;

class Staff implements IBusObject
{
	  private $conn=null;
	  private $conn_im=null;
	  private $account="";    //用户账号
	  private $userInfo=null; //用户对象
	  private $logger=null;
	  private $import_mapping=null;
	  private $container = null;
	  
	  public function __construct($_db,$_db_im,$_account,$_logger=null,$_container=null)
	  {
	    $this->conn = $_db;
	    $this->conn_im = $_db_im;
	    $this->logger = $_logger;
	    $this->container = $_container;
	    if(empty($_account))
	    {
	       throw new \Exception("帐号不能为空");	
	    }
	    if(is_string($_account))
	    {
	    	  $this->account = ($_account);
	    }
	    else
	    {
	    	$this->account =  $_account->getUserName();
	    	$this->userInfo = $_account;
	    }
	    $this->import_mapping = array();
	    $this->import_mapping["eno"] = "eno";
	    $this->import_mapping["姓名"] = "nick_name";
	    $this->import_mapping["邮箱帐号"] = "login_account";
	    $this->import_mapping["密码"] = "pwd";
	    $this->import_mapping["性别"] = "sex_id";
	    $this->import_mapping["部门"] = "dept_id";
	    $this->import_mapping["职务"] = "duty";
	    $this->import_mapping["手机"] = "mobile";
	    $this->import_mapping["电话"] = "work_phone";
	  }

	  public function getInstance($container)
	  {
	  		$db = $container->get("we_data_access");
	  		$db_im = $container->get("we_data_access_im");
	  		$logger = $container->get("logger");
	  		$token = $container->get('security.context')->getToken();
	  		if(!empty($token))
	  			$user = $token->getUser();
	  		else
	  			$user = $container->get('request')->get("openid");

	  		return new self($db,$db_im,$user,$logger,$container);
	  }	  
	  
	  //获得用户Session信息
	  public function getSessionUser($we_staff_row=null)
	  {	    
	      	if(empty($we_staff_row)) 
	      	{
	      	    $we_staff_row = $this->getInfo();
	      	}	      	
			$us = new \Justsy\BaseBundle\Login\UserSession($we_staff_row['login_account'], $we_staff_row['password'], $we_staff_row['login_account'], array('ROLE_USER'));		      
		      $us->nick_name = $we_staff_row['nick_name'];
		      $us->photo_path = $we_staff_row['photo_path'];
		      $us->photo_path_small = $we_staff_row['photo_path'];
		      $us->photo_path_big = $we_staff_row['photo_path'];
		      $us->dept_id = $we_staff_row['dept_id'];
		      $us->dept_name = $we_staff_row['dept_name'];
		      $us->eno = $we_staff_row['eno'];
		      $us->fafa_jid = $we_staff_row['fafa_jid'];
		      $us->self_desc= $we_staff_row['self_desc'];
		      $us->sex_id= $we_staff_row['sex_id'];
		      $us->mobile_bind=$we_staff_row['mobile_bind'];
		      $us->duty = isset($we_staff_row['duty']) ? $we_staff_row['duty']:null; 
		      $us->mobile =  isset($we_staff_row['mobile']) ? $we_staff_row['mobile']:null; 
		      $us->birthday =  isset($we_staff_row['birthday']) ? $we_staff_row['birthday']:null; 
		      $us->openid = isset($we_staff_row['openid']) ? $we_staff_row['openid']:null; 
		      $us->t_code = isset($we_staff_row['t_code']) ? $we_staff_row['t_code']:null; 
		      $us->edomain = isset($we_staff_row['edomain']) ? $we_staff_row['edomain']:null; 
		      $us->ename = isset($we_staff_row['ename']) ? $we_staff_row['ename']:null; 
		      $us->eshortname = isset($we_staff_row['eshortname']) ? $we_staff_row['eshortname']:null; 
		      $us->state_id = isset($we_staff_row['state_id']) ? $we_staff_row['state_id']:null;
		      $us->prev_login_date =isset($we_staff_row['prev_login_date']) ? $we_staff_row['prev_login_date']:null;
		      $us->t_code = (DES::decrypt($we_staff_row['t_code'])); 
		      return $us;     	
	  }
	  
	  //获取当前帐号导入信息
	  public function getImportInfo()
	  {
	  	 $sql = "select * from we_staff_import where login_account=?";
	  	 $ds=$this->conn->getData("info",$sql,array((string)$this->account));
	  	 if(count($ds["info"]["rows"])==0)  throw new \Exception("帐号已激活或者被导入人员删除");
	  	 return $ds["info"]["rows"][0];
	  }
	  
	  //导入注册
	  //导入时状态为:0
	  public function importReg($title,$datainfo)
	  {	  	  
	  	  $cols = array();
	  	  $vs = array();
	  	  $eno="";
	  	  $mobile="";
	  	  $mapping= $this->import_mapping;
	  	  while (list($key, $val) = each($mapping)) {
				     $cols[]=$val;
				     $col_index=0;
				     $title2 = $title;
				     while (list($index, $titlename) = each($title2)) {
						     if(strpos($titlename,$key)!==false)
						     {
						     	    $col_index = $index;
						         	break;
						     }
				     }
				    if($key=="eno") $eno = $datainfo[$col_index];
				    if($key=="手机")
				    {
				      	$mobile = $datainfo[$col_index];
				      	if(empty($mobile))
				      	{
				      		$vs[] = "NULL";  //手机号有唯一索引，除了NULL外不能重复字符
				      		continue;
				  		}
				  	}
				    $vs[] = "'".$datainfo[$col_index]."'";
	         } 
	         
		//判断手机是否已使用
		if(!empty($mobile))
		{
		    $sql = "select  1 from we_staff_import where mobile=? limit 0,1";
			$importDB = $this->conn->GetData("t",$sql,array((string)$mobile));
		    if($importDB && count($importDB["t"]["rows"])>0){
		      throw new \Exception("手机号[".$mobile."]已使用");
		 	}
	    }
		$sql =!empty($mobile)?  array( "delete from we_staff_import where eno=? and (login_account=? or mobile=?)") :
				                                          array( "delete from we_staff_import where eno=? and (login_account=?)");
		$sql[]="insert into we_staff_import(".implode(',',$cols).",state_id)values(".implode(',',$vs).",'0')";
		$deletePara = !empty($mobile)? array((string)$eno,(string)$this->account,(string)$mobile):array((string)$eno,(string)$this->account);				
		$this->conn->execsqls($sql,array($deletePara, array() ));
	  }
	  
	public function updateByImport($importData=null)
	{
	  	 $d2=$importData;
	  	 if($d2==null) $d2=$this->getImportInfo();
	  	 //获取部门编号
	  	 $dept = new Dept($this->conn,$this->conn_im);
	  	 $d_r = $dept->getIdByName($d2["eno"],trim($d2["dept_id"]));
	  	 $deptId = "";
	  	 if(!empty($d_r))
	  	 {
	  	     $deptId = $d_r["deptid"];
	  	 }
	  	if(!empty($d2["mobile"]))
	  	{
		  	 $sql = "update we_staff set dept_id=?,duty=?,mobile=?,mobile_bind=?,sex_id=?,work_phone=? where login_account=?";
		  	 $this->conn->ExecSql($sql,array(
		  	    (string)$deptId,
		  	    (string)$d2["duty"],
		  	    (string)$d2["mobile"],
		  	    (string)$d2["mobile"],
		  	    (string)$d2["sex_id"],
		  	    (string)$d2["work_phone"],
		  	    $this->account
		  	 ));
	  	}
	  	else
	  	{
		  	 $sql = "update we_staff set dept_id=?,duty=?,sex_id=?,work_phone=? where login_account=?";
		  	 $this->conn->ExecSql($sql,array(
		  	    (string)$deptId,
		  	    (string)$d2["duty"],
		  	    (string)$d2["sex_id"],
		  	    (string)$d2["work_phone"],
		  	    $this->account
		  	 ));	  		
	  	}
	  	 if(!empty($d_r))
	  	 {
	  	 	  $empInfo = $this->getInfo();
	  	 	  $sql = "update im_employee set deptid=? where loginname=?";
	  	 	  $this->conn_im->ExecSql($sql,array(
	  	 	      (string)$d_r["fafa_deptid"],
	  	 	       $empInfo["fafa_jid"]
	  	 	  ));
	  	    //更新IM库
	  	    $this->conn_im->ExecSQL("call dept_emp_stat(?)",array((string)$d2["eno"]));	
	  	 }
	  }
	  
	  //禁用当前帐号
	  //只有已激活的帐号才能禁用
	  //$endDate：结束日期。默认永久禁用
	  public function disable($endDate=null)
	  {
	  	  $state = $this->getState();
	  	  if("1"== $state)
	  	  {
	      	 $sql = "update we_staff set state_id=? where login_account=?";
	      	 $ds=$this->conn->ExecSQL($sql,array("-1",(string)$this->account));
	      	 $this->getInfo(true);
	  	  }
	  	  return true;
	  }
	  //从数据库中物理删除当前帐号
	  //已激活的不能删除
	  public function deletePhy()
	  {
	  	  $state = $this->getState();
	  	  if($state==null) throw new \Exception("帐号已不存在");
	  	  if("1"!= $state)
	  	  {
	  	      	$sql =array( "delete from we_staff where login_account=?","delete from we_staff_import where login_account=?");
	  	      	$ds=$this->conn->ExecSQLs($sql,array((string)$this->account));
	  	      	$this->getInfo(true);
	  	  }
	  }
	  //从导入表中物理删除当前帐号
	  public function deleteImportPhy()
	  {
	  	  $sql ="delete from we_staff_import where login_account=?";
	  	  $ds=$this->conn->ExecSQL($sql,array((string)$this->account));
	  }
	//离职
	//已激活的才能
	public function leave($stat="")
	{
		$fafa_jid = "";
		$dept_id = "";
		$tmpobj = $this->getInfo();
		if($tmpobj==null) return true;
		try
		{
				$VersionChange = new VersionChange($this->conn,$this->logger,$this->container);
		  	    $VersionChange->deptchange($tmpobj['dept_id']);
			  	$fafa_jid = $tmpobj["fafa_jid"];
			  	$login_account = $tmpobj["login_account"];
			  	//删除we_sns库
				$sql = "call p_deluser(?)";
				$para = array((string)$login_account);
				$this->conn->ExecSQL($sql,$para);	  
				//获得deptid字段				  	 	  
				$sql_im = "select deptid from im_employee where loginname=?;";
				$ds = $this->conn_im->GetData("dept",$sql_im,array((string)$fafa_jid));
				if ($ds && $ds["dept"]["recordcount"]>0){
				  	$dept_id = $ds["dept"]["rows"][0]["deptid"];
					//删除we_im库
					if (!empty($fafa_jid)){
							//$sql_im = "call p_deluser_im(?)";
                      		$this->del_im_user($fafa_jid);
					}
					//统计部门人员
					if ( $stat !="N" && !empty($dept_id)){
						$sql_im="call dept_emp_stat(?);";
						$para = array((string)$dept_id);
						$this->conn_im->ExecSQL($sql_im,$para);
					}
				}
		}
		catch (\Exception $e){
		  	if(!empty($this->logger))
		  		$this->logger->err($e->getMessage());
		  	return false;
		}
		$this->getInfo(true);
		return true;
	}
	
	public function del_im_user($jid)
	{
	    $sqls = array();$paras = array();
	    array_push($sqls,"delete  from rosterusers where username=?;");
	    array_push($sqls,"delete  from rosterusers where jid=?;");
	    array_push($sqls,"delete  from users where username=?;");
	    array_push($sqls,"delete  from rostergroups where username=?;");
	    array_push($sqls,"delete  from rostergroups where jid=?;");
	    array_push($sqls,"delete  from im_subscribe_ex where jid=?;");
	    array_push($sqls,"delete  from im_offline_file where sendfrom=?;");
	    array_push($sqls,"delete  from im_groupemployee where employeeid=?;");
	    array_push($sqls,"delete  from im_friendgroups where loginname=?;");
	    array_push($sqls,"delete  from im_employeerole where employeeid=?;");
	    array_push($sqls,"delete  from im_employee where loginname=?;");
	    array_push($sqls,"delete  from im_employee_version where us=?;");
	    array_push($sqls,"delete  from global_session where us=?;");
	    array_push($sqls,"delete  from im_employeerole where employeeid=?;");
	    array_push($sqls,"delete  from rosterdept where jid=?;");
      for ($i=0;$i < count($sqls);$i++)
      {
         array_push($paras,array((string)$jid));
      }
      $success = true;
      try
      {
        $this->conn_im->ExecSQLs($sqls,$paras);
      }
      catch(\Exception $e)
      {
        $success = false;
        $this->logger->err($e->getMessage());        
      }
      return $success;
	}

	public function updateJid($oldjid,$newjid)
	{
	        $sqls = array(); $paras = array();
	        $arrp = array((string)$newjid, (string)$oldjid);
	        $sqls[] = "update im_friendgroups set loginname=? where loginname=?";
	        $paras[] = $arrp;
	        $sqls[] = "update im_group set creator=? where creator=?";
	        $paras[] = $arrp;
	        $sqls[] = "update im_groupemployee set employeeid=? where employeeid=?";
	        $paras[] = $arrp;
	        $sqls[] = "update im_groupshare_file set addstaff=? where addstaff=?";
	        $paras[] = $arrp;
	        $sqls[] = "update im_offline_file set sendfrom=? where sendfrom=?";
	        $paras[] = $arrp;
	        $sqls[] = "update im_offline_file set sendto=? where sendto=?";
	        $paras[] = $arrp;
	        $sqls[] = "update rostergroups set username=? where username=?";
	        $paras[] = $arrp;
	        $sqls[] = "update rostergroups set jid=? where jid=?";
	        $paras[] = $arrp;
	        $sqls[] = "update rosterusers set username=? where username=?";
	        $paras[] = $arrp;
	        $sqls[] = "update rosterusers set jid=? where jid=?";
	        $paras[] = $arrp;
	        $sqls[] = "update users set username=? where username=?";
	        $paras[] = $arrp;
	        $sqls[] = "update im_employee set loginname=? where loginname=?";
	        $paras[] = $arrp;
	        $sqls[] = "update im_employeerole set employeeid=? where employeeid=?";
	        $paras[] = $arrp;
	        $sqls[] = "update spool set username=? where username=?";
	        $paras[] = $arrp; 
	        $sqls[] = "update im_runtime_message set jid=? where jid=?";
	        $paras[] = $arrp;
	        $sqls[] = "update im_runtime_message set `from`=? where `from`=?";
	        $paras[] = $arrp;	        
	        $this->conn_im->ExecSQLs($sqls,$paras);
	        $this->conn->ExecSQL("update we_staff set fafa_jid=? where fafa_jid=?",$arrp);
	        $this->getInfo(true);
	        return true;		
	}

	public function swtichEno($neweno,$circleId=null)
	{
		$staffinfo = $this->getInfo();
		$enObj = new Enterprise($this->conn,$this->logger,$this->container);
		if(empty($circleId))
		{
			//新的企业圈子id
			$endata = $enObj->getInfo($neweno);
			$circleId = $endata["circle_id"];
		}
		$deptinfo = new \Justsy\BaseBundle\Management\Dept($this->conn,$this->conn_im);
        $deptid=$deptinfo->getDefaultDept($neweno);
        $fafa_deptid = $deptid["fafa_deptid"];
        $deptid = $deptid["deptid"];
		$endata = $enObj->getInfo($staffinfo["eno"]);
		$sqls=array();
		$paras=array();
		$sqls[]= "update we_staff set eno=?,dept_id=? where login_account=?";
    	$paras[]=array((string)$neweno,(string)$deptid,(string)$this->account);
    	if($staffinfo["eno"]==Utils::$PUBLIC_ENO) //原企业是公共企业时，则加入新的企业圈子
    	{
    		$sqls[]= "insert into we_circle_staff(circle_id,login_account,nick_name)values(?,?,?)";
    		$paras[]=array((string)$circleId,(string)$this->account,(string)$staffinfo["nick_name"]);
    	}
    	else
    	{
    		$oldendata=$enObj->getInfo($staffinfo["eno"]);
    		$sqls[]= "update we_circle_staff set circle_id=? where login_account=? and circle_id=?";
    		$paras[]=array((string)$circleId,(string)$this->account,(string)$oldendata["circle_id"]);
    	}
    	$sqls[]= "update we_function_onoff set eno=? where login_account=?";
    	$paras[]=array((string)$neweno,(string)$this->account);    	
    	$this->conn->ExecSQLs($sqls,$paras);
        //更新为新企业根部门
    	$this->conn_im->ExecSQL("update im_employee set deptid=? where loginname=?",array((string)$fafa_deptid,(string)$staffinfo["fafa_jid"]));
		$this->conn_im->ExecSQL("call dept_emp_stat(?)",array((string)$staffinfo["fafa_jid"]));
		return true;
	}
	
	  //再次重新邀请当前帐号
	  public function invite()
	  {
	  	   $isexist = $this->isExist();
	  	   if(!empty($isexist)) throw new \Exception("帐号已激活");
	  }
	  
	  public function attentionTo($attenaccount)
	  { 
	  	 $staffinfo = $this->getInfo();
       $attention_type='01'; //关注人员
       $sql = "insert into we_staff_atten(login_account,atten_type,atten_id,atten_date)values(?,?,?,now())";
       $para = array((string)$staffinfo["login_account"],(string)$attention_type,(string)$attenaccount);
       $this->conn->ExecSQL($sql,$para);       
       //每个关注0.2分
       $sql = "insert into we_staff_points (login_account,point_type,point_desc,point,point_date)
          values (?,?,?,?,now())";
       $para = array(
          (string)$staffinfo["login_account"],
          (string)'08',
          (string)'关注'.$attenaccount.'，获得积分0.2',
          (float)0.2); 
       $this->conn->ExecSQL($sql,$para);
       $friendevent=new \Justsy\BaseBundle\Management\FriendEvent($this->conn,$this->logger,null);
       $friendevent->attenuser($staffinfo["login_account"],$staffinfo["nick_name"],$attenaccount);
	  }
	  
	  public function attentionMe($account)
	  {
	  	 $staffinfo = $this->getInfo();
       $attention_type='01'; //关注人员
       $sql = "insert into we_staff_atten(login_account,atten_type,atten_id,atten_date)values(?,?,?,now())";
       $para = array((string)$account,(string)$attention_type,(string)$staffinfo["login_account"]);
       $this->conn->ExecSQL($sql,$para);
       //每个关注0.2分
       $sql = "insert into we_staff_points (login_account,point_type,point_desc,point,point_date)
          values (?,?,?,?,now())";
       $para = array(
          (string)$account,
          (string)'08',
          (string)'关注'.$staffinfo["login_account"].'，获得积分0.2',
          (float)0.2); 
       $this->conn->ExecSQL($sql,$para);
	  }	  
	  
	//添加好友
	public function bothAddFriend($paraObj)
	{
		$firendaccount = $paraObj['firendaccount'];
        $datarow = $this->getStaffInfo($firendaccount);
        if(empty($datarow))
        {
        	return Utils::WrapResultError('好友帐号无效');
        }
        $me = $this->getInfo();
        $jid= $me["fafa_jid"];
        $nick_name=$me["nick_name"];
        
        $att_jid = $datarow['fafa_jid'];
        $att_nick_name = $datarow['nick_name'];
        $firend_login_account = $datarow['login_account'];
        $sqls=array();
        $paras=array();
        $sqls[]='delete from rosterusers where username=? and jid=? ';
        $paras[] = array((string)$jid,(string)$att_jid);
        $sqls[]='delete from rosterusers where username=? and jid=? ';
        $paras[] = array((string)$att_jid,(string)$jid);        
        $sql = "insert into rosterusers (username, jid, nick, subscription, ask, server, type, created_at) values (?, ?, ?, 'B','N','N','item',now())";
        $sqls[] = $sql;
        $paras[] = array(
          (string)$jid,
          (string)$att_jid,
          (string)$att_nick_name
        );
        $sqls[] = $sql;
        $paras[] = array(
          (string)$att_jid,
          (string)$jid,
          (string)$nick_name
        );
		  
        try
        {
        	try{
        	    $this->conn_im->ExecSQLs($sqls,$paras);
        	    //发送即时消息
        	    $notice = array();// Utils::WrapMessageNoticeinfo('你已添加'.$att_nick_name.'为好友。','',null,'');
        	    $senddata = array('jid'=>$me['jid'],'photo_path'=>$me['photo_path'],'nick_name'=>$me['nick_name'],'msg'=>'你已添加'.$me['nick_name'].'为好友，可以开始聊天了。');
		        $message =json_encode(Utils::WrapMessage('friend_both',$senddata,$notice));
		        Utils::sendImMessage($jid,$att_jid,"friend_both",$message,$this->container,"","",false,Utils::$systemmessage_code);

		        $notice = array();//Utils::WrapMessageNoticeinfo('你已添加'.$nick_name.'为好友。','',null,'');
        	    $senddata = array('jid'=>$datarow['jid'],'photo_path'=>$datarow['photo_path'],'nick_name'=>$datarow['nick_name'],'msg'=>'你已添加'.$att_nick_name.'为好友，可以开始聊天了。');
		        $message =json_encode(Utils::WrapMessage('friend_both',$senddata,$notice));
               	Utils::sendImMessage($att_jid,$jid,"friend_both",$message,$this->container,"","",false,Utils::$systemmessage_code);
            }
        	catch(\Exception $e){}
        }
        catch(\Exception $e)
        {
          $this->logger->err($e);
        } //不处理已经是好友时，又添加为好友的异常。这时数据库会报主键重复异常
        return Utils::WrapResultOK(array('jid'=>$att_jid,'nick_name'=>$att_nick_name,'photo_path'=>$datarow['photo_path']));  
	}

	public function getRoster($parameters)
	{
		$to=$parameters["to"];
		$mydata = isset($parameters["from"]) ? $this->getStaffInfo($parameters["from"]) : $this->getInfo();
		$toData = $this->getStaffInfo($to);
		if(empty($toData))
		{
			return Utils::WrapResultError("NULL");
		}
		$sql = "select subscription from rosterusers where username=? and jid=?";
		$ds = $this->conn_im->getData("t",$sql,array(
			(string)$mydata["fafa_jid"],
			(string)$toData["fafa_jid"]
		));
		if(count($ds["t"]["rows"])>0)
			return Utils::WrapResultOK($ds["t"]["rows"][0]);
		else
			return Utils::WrapResultOK(array());
	}

	//向指定帐号发送好友请求
	public function requestFriend($parameters)
	{
		$to=$parameters["to"];
		$authtext=$parameters["authtext"];
		$mydata = $this->getInfo();
		$toData = $this->getStaffInfo($to);
		if(empty($toData))
		{
			return Utils::WrapResultError("请求的好友帐号无效");
		}
		//判断和联系人当前关系
		$roster = $this->getRoster($parameters);
		$rosterinfo = $roster["data"];
		if(!empty($rosterinfo))
		{
			if($rosterinfo["subscription"]=="B")
				return Utils::WrapResultError("已经是好友");
			else
				return Utils::WrapResultOK("");
		}
		$sql = "insert into rosterusers (username, jid, nick, subscription, ask, server, type, created_at) values (?, ?, ?, 'N','N','N','item',now())";
        $paras = array(
          (string)$mydata["fafa_jid"],
          (string)$toData["fafa_jid"],
          (string)$toData["nick_name"]
        );
        $this->conn_im->ExecSQL($sql,$paras);
        return Utils::WrapResultOK("");
	}

	//同意好友请求
	public function agreeFriend($parameters)
	{
		$from = $parameters["to"];
		$myData = $this->getInfo();
		$fromData = $this->getStaffInfo($from);
		if(empty($fromData))
		{
			return Utils::WrapResultError("请求的好友帐号无效");
		}
		//判断自己是否给对方发送过请求
		$roster = $this->getRoster(array("from"=>$from,"to"=>$myData["login_account"]));
		$rosterinfo = $roster["data"];
		if(!empty($rosterinfo))
		{
			if($rosterinfo["subscription"]=="B")
				return Utils::WrapResultError("已经是好友");
		}
		$sqls=array();
		$paras=array();
		$sqls[] = "delete from rosterusers where username=? and jid=?";
		$paras[] = array((string)$myData["fafa_jid"],(string)$fromData["fafa_jid"]);
		$sqls[] = "delete from rosterusers where jid=? and username=?";
		$paras[] = array((string)$myData["fafa_jid"],(string)$fromData["fafa_jid"]);
		$sqls[] = "insert into rosterusers (username, jid, nick, subscription, ask, server, type, created_at) values (?, ?, ?, 'B','N','N','item',now())";
	    $paras[] = array(
	          (string)$myData["fafa_jid"],
	          (string)$fromData["fafa_jid"],
	          (string)$fromData["nick_name"]
	        );
		$sqls[] = "insert into rosterusers (username, jid, nick, subscription, ask, server, type, created_at) values (?, ?, ?, 'B','N','N','item',now())";
	    $paras[] = array(
	          (string)$fromData["fafa_jid"],
	          (string)$myData["fafa_jid"],
	          (string)$myData["nick_name"]
	        );
        $this->conn_im->ExecSQLs($sqls,$paras);
        return Utils::WrapResultOK("");		
	}
	//拒绝好友请求
	public function rejectFriend($parameters)
	{
		$from = $parameters["to"];
		$myData = $this->getInfo();
		$fromData = $this->getStaffInfo($from);
		if(empty($fromData))
		{
			return Utils::WrapResultError("请求的好友帐号无效");
		}
		$sql = "delete from rosterusers where username=? and jid=? and subscription='N'";
        $paras = array(
          (string)$fromData["fafa_jid"],
          (string)$myData["fafa_jid"]
        );
        $this->conn_im->ExecSQL($sql,$paras);
        return Utils::WrapResultOK("");		
	}
	//删除好友请求
	public function removeFriend($parameters)
	{
		$to = $parameters["to"];
		$myData = $this->getInfo();
		$toData = $this->getStaffInfo($to);
		if(empty($toData))
		{
			return Utils::WrapResultError("好友帐号无效");
		}
		$sql = "delete from rosterusers where username=? and jid=? ";
        $paras = array(
          (string)$toData["fafa_jid"],
          (string)$myData["fafa_jid"]
        );
        $this->conn_im->ExecSQL($sql,$paras);
        $paras = array(
          (string)$myData["fafa_jid"],
          (string)$toData["fafa_jid"]
        );
        $this->conn_im->ExecSQL($sql,$paras);
        return Utils::WrapResultOK("");		
	}	
	//查询好友请求
	public function getFriendRequest()
	{
		$mydata = $this->getInfo();
		$sql = "select username,nick nick_name from rosterusers where jid=? and subscription='N'";
		$ds = $this->conn_im->getData("t",$sql,array((string)$mydata["fafa_jid"]));
		return Utils::WrapResultOK($ds["t"]["rows"]);
	}

	//查询好友列表
	public function getFriendList()
	{
		$mydata = $this->getInfo();
		$sql = "select jid,nick nick_name from rosterusers where username=? and subscription='B'";
		$ds = $this->conn_im->getData("t",$sql,array((string)$mydata["fafa_jid"]));
		return Utils::WrapResultOK($ds["t"]["rows"]);
	}	

	  
	  //将当前人员移动到指定部门
	  public function moveToDept($deptid,$fafa_deptid=null)
	  { 
	  	$staffinfo = $this->getInfo();
	  	//判断是否同部门，是则不处理
	  	if($staffinfo==null || $staffinfo["dept_id"]==$deptid) return "";
	  	$jid = $staffinfo["fafa_jid"];
	  	$deptMgr = new Dept($this->conn,$this->conn_im,$this->container);
	  	$dept = $deptMgr->getinfo($staffinfo["dept_id"]);
	  	$olddeptid = $dept["deptid"];
	  	
	  	if(empty($fafa_deptid))
	  	{	  		
	  		$dept = $deptMgr->getinfo($deptid);
	  		$fafa_deptid = empty($dept)?"v".$staffinfo["eno"]: $dept["deptid"];
	  	}
		$VersionChange = new VersionChange($this->conn,$this->logger,$this->container);
		$VersionChange->deptchange($staffinfo);

	  	//更新sns中人员部门编号
	  	$staff_update_sql = "update we_staff set dept_id=? where login_account=?";
	  	$this->conn->ExecSQL($staff_update_sql,array((string)$deptid,(string)$this->account));
	  	//更新IM库中的人员部门信息
	  	$this->conn_im->ExecSQL("update im_employee set deptid=? where loginname=?",array((string)$fafa_deptid,(string)$jid));
	  	
	  	$staff_update_sql=array();
	  	$paras = array();
	  	$staff_update_sql[] ="call dept_emp_stat(?)";
	  	$paras[]=array((string)$jid);
	  	$staff_update_sql[] ="call dept_emp_stat(?)";
	  	$paras[]=array((string)$olddeptid);	
	  	$this->conn_im->ExecSQLs($staff_update_sql,$paras);

	   	//删除原部门互为好友关系
	   	$this->delFriend($olddeptid,$jid,$this->logger);
	   	//新部门互为好友
	   	$newdept = $deptMgr->getinfo($deptid);
	   	$deptMgr->setFriendByDept($newdept["deptid"],array("login_account"=>$this->account,"fafa_jid"=>$jid,'nick_name'=>$staffinfo['nick_name']));
	    $groupMgr = new GroupMgr($this->conn,$this->conn_im,$this->container);
	    //从原部门默认群组中移除
	    $groupMgr->RemoveDefaultGroupMember(array('deptid'=>$olddeptid,'jid'=>$jid));
	    //加入新部门的默认群组
	    $groupMgr->AddDefaultGroup(array('deptid'=>$newdept["deptid"],
	    	'jid'=>$jid,
	    	'nick_name'=>$staffinfo['nick_name']));
	    
	    $serviceMgr = new Service($this->container);
	    //取消关注原部门关联的公众号
	    $serviceMgr->cancel_atten(array(
	    		'deptid'=>$olddeptid,
	    		'jid'=>$jid,
	    		'eno'=>$staffinfo['eno']
	    	));
	    //关注新部门关联的公众号
	    $serviceMgr->atten_service(array(
	    		'deptid'=>$newdept["deptid"],
	    		'jid'=>$jid,
	    		'eno'=>$staffinfo['eno']
	    	));
	    //刷新缓存
	   	$staffinfo=$this->getInfo(true);
	   	$VersionChange->deptchange($staffinfo);
	   	$message = array('jid'=>$jid,'newdeptid'=>$deptid,'olddeptid'=>$olddeptid);
	    $msg = json_encode(Utils::WrapMessage('staff_move_dept',$message,array()));
	 	$sendMessage = new \Justsy\BaseBundle\Common\SendMessage($this->conn,$this->conn_im);
	    $parameter = array("eno"=>$staffinfo['eno'],"flag"=>"all","title"=>"staff_move_dept","message"=>$msg,"container"=>$this->container);
	    $sendMessage->sendImMessage($parameter);
	  	return $jid;
	  }
	  
	  public function isExist($mobile=null)
	  {
	      $sql = "select state_id from we_staff where login_account=? or openid=? or fafa_jid=?";
	      $para = array((string)$this->account,(string)$this->account,(string)$this->account);
	      if(!empty($mobile))
	      {
	      	  $sql.= " or mobile=?";
	      	  $para[]= (string)$mobile;
	      }
	      $ds=$this->conn->getData("t",$sql,$para);
	      if($ds && count($ds["t"]["rows"])>0)
	      {
	          return 	$ds["t"]["rows"][0]["state_id"];
	      }
	      return null;	  	
	  }
	  
	  	public function checkNickname($eno,$name)
	  	{
        	$sql = "select state_id from we_staff where eno=? and nick_name=?";
        	$para = array((string)$eno,(string)$name);
        	     
        	$ds=$this->conn->getData("t",$sql,$para);
        	if($ds && count($ds["t"]["rows"])>0)
        	{
        	    return 	true;
        	}	     
        	return false;	  	
	   }
	   //检查并更新指定的姓名和手机号
	   public function checkAndUpdate($nick_name,$mobile=null,$deptid=null,$duty=null,$ldap_uid=null,$sex_id=null,$self_desc=null)
	   {
	   		$sql = "update we_staff set ";
	   		$returnItem = array();
	   		$para = array();
	   		$updateItm=array();
	   		if(!empty($nick_name))
	   		{
	   			$updateItm[]="nick_name=?"; 
	   			$para[] = (string)$nick_name;
	   			$returnItem["nick_name"] = $nick_name;
	   		}
	   		if($mobile!==null)
	   		{
	   			$updateItm[]="mobile=?, mobile_bind=?"; 
	   			$para[] = (string)$mobile;
	   			$para[] = (string)$mobile;
	   			$returnItem["mobile"] = $mobile;
	   		}
	   		if($duty!==null)
	   		{
	   			$updateItm[]="duty=?"; 
	   			$para[] = (string)$duty;
	   			$returnItem["duty"] = $duty;
	   		}
	   		if ($ldap_uid != null){
	   			$updateItm[] = "ldap_uid=?";
	   			$para[] = (string)$ldap_uid;
	   		}
	   		if($sex_id!=null)
	   		{
	   			$updateItm[] = "sex_id=?";
	   			$para[] = (string)$sex_id;
	   			$returnItem["sex_id"] = $sex_id;
	   		}
	   		if($self_desc!=null)
	   		{
	            $updateItm[] = "self_desc=?";
	            $para[] = (string)$self_desc;
	            $returnItem["self_desc"] = $self_desc;
	   		} 		
			  if($deptid!==null)
	   		{
	   		   	$staffinfo = $this->getInfo();	  	     	
	  	     	   		    
	   			$this->moveToDept($deptid);
	   		}
	   		
	   		
	   		if(empty($updateItm) || count($updateItm)==0)
	   			return true;
	   		$sql = "update we_staff set ".implode(",", $updateItm)." where login_account=? or ldap_uid=? or openid=?";
	   		$para[] = (string)$this->account;
	   		$para[] = (string)$this->account;
	   		$para[] = (string)$this->account;
	   		$this->conn->ExecSQL($sql,$para);
	   		if(!empty($nick_name))
	   		{
	   			$this->conn->ExecSQL("call emp_change_name(?,?)",array((string)$this->account,(string)$nick_name));
	   			$jid = $this->getInfo();
	   			$jid = $jid["fafa_jid"];
	   			$this->conn_im->ExecSQL("call emp_change_name(?,?)",array((string)$jid,(string)$nick_name));
	   		}
	   		//刷新缓存
	   		$staffdata = $this->getInfo(true);
	   		if(!empty($self_desc) || !empty($nick_name))
	   		{
	   			$pinyin=$this->syncAttrsToIM();
	   			$returnItem['spell'] = $pinyin;
	   		}
	   		$VersionChange = new VersionChange($this->conn,$this->logger,$this->container);
		  	$VersionChange->deptchange($staffdata);
		  	$returnItem['jid']=$staffdata['jid'];
		  	$msg = json_encode(Utils::WrapMessage('staff-changeinfo',$returnItem,array()));
     	    //操作成功发送出席
		  	$sendMessage = new \Justsy\BaseBundle\Common\SendMessage($this->conn,$this->conn_im);
		  	$parameter = array("eno"=>$staffdata["eno"],"fromjid"=>$staffdata["jid"],"flag"=>"all","title"=>"staff-changeinfo","message"=>$msg,"container"=>$this->container);
		  	$sendMessage->sendImMessage($parameter);
		  	$parameter = array("eno"=>$staffdata["eno"],"fromjid"=>$staffdata["jid"],"flag"=>"onself","title"=>"staff-changeinfo","message"=>$msg,"container"=>$this->container);
		  	$sendMessage->sendImPresence($parameter);	   		
	   		return true;
	   }

	   public function modifystaffinfo($paraObj)
	   {
	   		$nick_name = isset($paraObj["nick_name"]) ? $paraObj["nick_name"] : "";
	   		if(empty($nick_name)) $nick_name=null;
	   		$mobile = isset($paraObj["mobile"]) ? $paraObj["mobile"] : "";
	   		if(empty($mobile)) $mobile=null;
	   		$ldap_uid=isset($paraObj["ldap_uid"]) ? $paraObj["ldap_uid"] : "";
	   		if(empty($ldap_uid)) $ldap_uid=null;
	   		$deptid = isset($paraObj["dept_id"]) ? $paraObj["dept_id"] : "";
	   		if(empty($deptid)) $deptid=null;
	   		$duty = isset($paraObj["duty"]) ? $paraObj["duty"] : "";
	   		if(empty($duty)) $duty=null;
	   		$sex_id = isset($paraObj["sex_id"]) ? $paraObj["sex_id"] : "";
	   		if(empty($sex_id)) $sex_id=null;
	   		$self_desc = isset($paraObj["self_desc"]) ? $paraObj["self_desc"] : "";
	   		if(empty($self_desc)) $self_desc=null;
	   		return $this->checkAndUpdate($nick_name,$mobile,$deptid,$duty,$ldap_uid,$sex_id,$self_desc);
	   }

	   //手机端登录时，获取当前人员的基本信息
	  public function getInfoByMobileLogin()
	  {
		try
		{
			$data=Cache_Enterprise::get(Cache_Enterprise::$EN_STAFF,$this->account,$this->container);
		}
		catch(\Exception $e)
		{
			$this->logger->err($e);
			$data=null;
		}
		if(empty($data))
		{
			$fileurl=$this->container->getParameter("FILE_WEBSERVER_URL");
		    $sql = "select a.nick_name, a.login_account,a.self_desc,a.sex_id,a.mobile_bind,a.state_id,a.photo_path_big photo_path, a.dept_id, a.eno,a.fafa_jid jid,a.duty,a.t_code,a.openid,a.password,a.fafa_jid,a.birthday".
		          " from we_staff a left  join we_department b on a.dept_id=b.dept_id ".
		          " where a.state_id='1' and (a.login_account=? or a.mobile_bind=? or a.ldap_uid=?)";
		    $ds=$this->conn->getData("t",$sql,
		      	array(
		      			(string)$this->account,(string)$this->account,(string)$this->account
		      	)
			);
		    if($ds && count($ds["t"]["rows"])>0)
		    {
		    	$returnObj = $ds["t"]["rows"][0];
		    	if(!empty($returnObj["photo_path"]))
		    	{
		    		$returnObj["photo_path"] = $fileurl.$returnObj["photo_path"];
		    	}
			    $dept=new Dept($this->conn,$this->conn_im,$this->container);
			    $d_info = $dept->getinfo($returnObj['dept_id']);
			    $returnObj['dept_name'] = empty($d_info) ? '' : $d_info['deptname'];	
			    $en=new Enterprise($this->conn,$this->logger,$this->container);
			    $e_info = $en->getinfo($returnObj['eno']);
			    $returnObj['ename'] = empty($e_info) ? '' : $e_info['ename'];
		    	$this->account = $returnObj["login_account"];
		    	//判断人员状态。如果不正常朋需要刷新缓存时，从缓存中删除
		    	if($returnObj["state_id"]!="1")
		    	{
		    		Cache_Enterprise::delete(Cache_Enterprise::$EN_STAFF,$this->account,$this->container);
		    	}
		    	else
		    	{
		    		Cache_Enterprise::set(Cache_Enterprise::$EN_STAFF,$this->account,json_encode($returnObj),0,$this->container);
		    	}
		        return $returnObj;
		    }
		    else
		    {
		    	//没有获取到人员时，从缓存中删除
		    	Cache_Enterprise::delete(Cache_Enterprise::$EN_STAFF,$this->account,$this->container);
		    	return null;
		    }
		}
		$returnObj = json_decode($data,true);
		$this->account = $returnObj["login_account"];
	    return $returnObj;
	  }
	  
	  //获取当前人员的基本信息
	  public function getInfo($refreshCache=false)
	  {
    		try
    		{
    			$data=null;
    			if(!$refreshCache)
    			{
	    			$data=Cache_Enterprise::get(Cache_Enterprise::$EN_STAFF,$this->account,$this->container);
	    			if(empty($data))
	    			{
	    				//判断是否是影射到帐号上的其他属性
	    				$mapp_login_account = Cache_Enterprise::get('staff_mapp_',$this->account,$this->container);
	    				if(!empty($mapp_login_account))
	    				{
	    					$data=Cache_Enterprise::get(Cache_Enterprise::$EN_STAFF,$mapp_login_account,$this->container);
	    				}
	    			}
    			}
    		}
    		catch(\Exception $e)
    		{
    			$this->logger->err($e);
    			$data=null;
    		}
    		if(empty($data))
    		{
    			$fileurl=$this->container->getParameter("FILE_WEBSERVER_URL");
    			if(Utils::validateMobile($this->account))
    			{
    				$sql = "select a.nick_name, a.login_account,a.self_desc,a.sex_id,a.mobile_bind,a.state_id,a.photo_path_big photo_path, a.dept_id, a.eno,a.fafa_jid,a.fafa_jid jid,a.duty,a.t_code,a.openid,a.password,a.fafa_jid,a.birthday from we_staff a  where a.mobile_bind=? ";
    				$ds=$this->conn->getData("t",$sql,
    			      	array(
    			      			(string)$this->account
    			      	)
    				);
    			}
    			else if(strlen($this->account)>=30)
    			{
    				$sql = "select a.nick_name, a.login_account,a.self_desc,a.sex_id,a.mobile_bind,a.state_id,a.photo_path_big photo_path, a.dept_id, a.eno,a.fafa_jid,a.fafa_jid jid,a.duty,a.t_code,a.openid,a.password,a.fafa_jid,a.birthday from we_staff a  where a.openid=? ";
    				$ds=$this->conn->getData("t",$sql,
    			      	array(
    			      			(string)$this->account
    			      	)
    				);
    			}
    		  	else
		      	{
		    	  	$sql = "select a.nick_name, a.login_account,a.self_desc,a.sex_id,a.mobile_bind,a.state_id,a.photo_path_big photo_path, a.dept_id, a.eno,a.fafa_jid,a.fafa_jid jid,a.duty,a.t_code,a.openid,a.password,a.fafa_jid,a.birthday from we_staff a where a.login_account=? or a.fafa_jid=? or a.ldap_uid=?";
		        	$ds=$this->conn->getData("t",$sql,
			      	array(
			      			(string)$this->account,(string)$this->account,(string)$this->account
			      		)
				    );
    			}
    		    if($ds && count($ds["t"]["rows"])>0)
    		    {
    		    	$returnObj = $ds["t"]["rows"][0];
    		    	if(!empty($returnObj["photo_path"]))
			    	{
			    		$returnObj["photo_path"] = $fileurl.$returnObj["photo_path"];
			    	}
			    	$dept=new Dept($this->conn,$this->conn_im,$this->container);
			    	$d_info = $dept->getinfo($returnObj['dept_id']);

				    $returnObj['dept_name'] = empty($d_info) ? '' : $d_info['deptname'];
				    $en=new Enterprise($this->conn,$this->logger,$this->container);
				    $e_info = $en->getinfo($returnObj['eno']);
				    $returnObj['ename'] = empty($e_info) ? '' : $e_info['ename'];
					if($this->account!=$returnObj["login_account"])
				    {
				    	//非帐号时，做缓存影射
				    	Cache_Enterprise::set('staff_mapp_',$this->account,$returnObj["login_account"],0,$this->container);
				    }
    		    	$this->account = $returnObj["login_account"];
    		    	Cache_Enterprise::delete(Cache_Enterprise::$EN_STAFF,$this->account,$this->container);
    		    	//判断人员状态。如果不正常朋需要刷新缓存时，从缓存中删除
    		    	if($returnObj["state_id"]=="1")
    		    	{
    		    		Cache_Enterprise::set(Cache_Enterprise::$EN_STAFF,$this->account,json_encode($returnObj),0,$this->container);
    		    	}
    		      	return $returnObj;
    		    }
    		    else
    		    {
    		    	//没有获取到人员时，从缓存中删除
    		    	Cache_Enterprise::delete(Cache_Enterprise::$EN_STAFF,$this->account,$this->container);
    		    	return null;
    		    }
    		}
    		$returnObj = json_decode($data,true);
    		$this->account = $returnObj["login_account"];
    	    return $returnObj;
	  }

	  //获取指定人员的基本信息
	  public function getStaffInfo($staff,$refreshCache=false)
	  {
	  		if(empty($staff)) return null;
    		try
    		{
    			$data=null;
    			if(!$refreshCache)
    			{
	    			$data=Cache_Enterprise::get(Cache_Enterprise::$EN_STAFF,$staff,$this->container);
	    			if(empty($data))
	    			{
	    				//判断是否是影射到帐号上的其他属性
	    				$mapp_login_account = Cache_Enterprise::get('staff_mapp_',$staff,$this->container);
	    				if(!empty($mapp_login_account))
	    				{
	    					$data=Cache_Enterprise::get(Cache_Enterprise::$EN_STAFF,$mapp_login_account,$this->container);
	    				}
	    			}
    			}
    		}
    		catch(\Exception $e)
    		{
    			$this->logger->err($e);
    			$data=null;
    		}
    		if(empty($data))
    		{
    			if(Utils::validateMobile($staff))
    			{
    				$sql = "select a.nick_name, a.login_account,a.self_desc,a.sex_id,a.mobile_bind,a.state_id,a.photo_path_big photo_path, a.dept_id, a.eno,a.fafa_jid,a.fafa_jid jid,a.duty,a.openid from we_staff a where a.mobile_bind=? ";
    				$ds=$this->conn->getData("t",$sql,
    			      	array(
    			      			(string)$staff
    			      	)
    				);			
    			}
    			else if(strlen($staff)>=30)
    			{
    				$sql = "select a.nick_name, a.login_account,a.self_desc,a.sex_id,a.mobile_bind,a.state_id,a.photo_path_big photo_path, a.dept_id, a.eno,a.fafa_jid,a.fafa_jid jid,a.duty,a.openid from we_staff a  where a.openid=? ";
    				$ds=$this->conn->getData("t",$sql,
    			      	array(
    			      			(string)$staff
    			      	)
    				);
    			}
    		  	else
		      	{
		    	  	$sql = "select a.nick_name, a.login_account,a.self_desc,a.sex_id,a.mobile_bind,a.state_id,a.photo_path_big photo_path, a.dept_id, a.eno,a.fafa_jid,a.fafa_jid jid,a.duty,a.openid from we_staff a where a.login_account=? or a.fafa_jid=? or a.ldap_uid=?";
		        	$ds=$this->conn->getData("t",$sql,
			      	array(
			      			(string)$staff,(string)$staff,(string)$staff
			      		)
				    );
    			}
    		    if($ds && count($ds["t"]["rows"])>0)
    		    {
    		    	$fileurl=$this->container->getParameter("FILE_WEBSERVER_URL");
    		    	$returnObj = $ds["t"]["rows"][0];
    		    	if(!empty($returnObj["photo_path"]))
			    	{
			    		$returnObj["photo_path"] = $fileurl.$returnObj["photo_path"];
			    	}
			    	$dept=new Dept($this->conn,$this->conn_im,$this->container);
			    	$d_info = $dept->getinfo($returnObj['dept_id']);
				    $returnObj['dept_name'] = empty($d_info) ? '' : $d_info['deptname'];	
				    $en=new Enterprise($this->conn,$this->logger,$this->container);
				    $e_info = $en->getinfo($returnObj['eno']);
				    $returnObj['ename'] = empty($e_info) ? '' : $e_info['ename'];
				    if($staff!=$returnObj["login_account"])
				    {
				    	//非帐号时，做缓存影射
				    	Cache_Enterprise::set('staff_mapp_',$staff,$returnObj["login_account"],0,$this->container);
				    }
    		    	$staff = $returnObj["login_account"];
    		    	Cache_Enterprise::delete(Cache_Enterprise::$EN_STAFF,$staff,$this->container);
    		    	//判断人员状态。如果不正常朋需要刷新缓存时，从缓存中删除
    		    	if($returnObj["state_id"]=="1")
    		    	{
    		    		Cache_Enterprise::set(Cache_Enterprise::$EN_STAFF,$staff,json_encode($returnObj),0,$this->container);
    		    	}
    		      return $returnObj;
    		    }
    		    else
    		    {
    		    	//没有获取到人员时，从缓存中删除
    		    	Cache_Enterprise::delete(Cache_Enterprise::$EN_STAFF,$staff,$this->container);
    		    	return null;
    		    }
    		}
    		$returnObj = json_decode($data,true);
    	    return $returnObj;
	  }


	  //获取当前人员的企业信息
	  public function getEnInfo()
	  {
	      $sql = "select b.* from we_staff a,we_enterprise b where a.eno=b.eno and (login_account=? or openid=? or fafa_jid=?)";
	      $ds=$this->conn->getData("t",$sql,array((string)$this->account,(string)$this->account,(string)$this->account));
	      if($ds && count($ds["t"]["rows"])>0)
	      {
	          return 	$ds["t"]["rows"][0];
	      }
	      return null;	  	
	  }


	  //同步人员信息到im库，同步属性有头像、签名、全拼
	  public function syncAttrsToIM()
	  {
	  		$staffinfo  =$this->getInfo(true);
	  		if(empty($staffinfo)) return false;
	  		try
	  		{
		  		$pinyin = Utils::Pinyin($staffinfo["nick_name"]);
		  		$sql_im = "update im_employee set photo=?,p_desc=?,spell=? where loginname=?";
		  		$this->conn_im->ExecSQL($sql_im,array(
		  			(string)$staffinfo["photo_path"],
		  			mb_substr((string)$staffinfo["self_desc"],0,140),
		  			(string)$pinyin,
		  			(string)$staffinfo["fafa_jid"]
		  		));
	  		}
	  		catch(\Exception $e)
	  		{
	  			if(!empty($this->logger))
	  			{
	  				$this->logger->err($e);
	  				return false;
	  			}
	  		}
	  		return $pinyin;
	  }	  
	  
	  public function queryBaseInfo($deptid,$state)
	  {
	  	  $info=$this->getInfo();
	  	  if(empty($deptid))
	  	  {	  	     	
	  	     	$deptid = $info["eno"];
	  	  }
	  	  $para=array();
	  	  if($deptid==$info["eno"])
	  	  {
	  	      $sql = "select a.dept_id,a.eno,a.nick_name,a.login_account,a.photo_path,a.duty,a.birthday,a.mobile,a.fafa_jid,a.work_phone,a.sex_id,a.openid,a.mobile_bind,b.dept_name from we_staff a left join we_department b on a.dept_id=b.dept_id where a.eno=?";
	  	  }
	  	  else
	  	  {
	  	      $sql = "select a.dept_id,a.eno,a.nick_name,a.login_account,a.photo_path,a.duty,a.birthday,a.mobile,a.fafa_jid,a.work_phone,a.sex_id,a.openid,a.mobile_bind,b.dept_name from we_staff a left join we_department b on a.dept_id=b.dept_id where a.dept_id=?";
	  	  }
	  	  $para[]=$deptid;
	  	  $ds=$this->conn->getData("t",$sql,$para);
	  	  return $ds["t"]["rows"];
	  }
	  
	  	//搜索员工的方法
	  public function querySearchBaseInfo($deptid,$state,$search,$limit=100,$lastid=0){
	  	$info=$this->getInfo();
	  	$eno=$info["eno"];
	  	$fileurl=$this->container->getParameter("FILE_WEBSERVER_URL");
	  	$sql="select a.id,a.dept_id,a.eno,a.nick_name,a.login_account,case a.photo_path when '' then '' else concat('".$fileurl."',a.photo_path) end photo_path,a.duty,a.birthday,a.mobile,a.fafa_jid, a.work_phone,a.sex_id,a.openid,a.mobile_bind,b.dept_name ,state_id from we_staff a left join we_department b on a.dept_id=b.dept_id where a.eno=?  and not EXISTS (select 1 from we_micro_account c where c.number=a.login_account and c.eno=a.eno) ";
	  	$para=array($eno);
	  	if(!empty($search)) {
 	    	if(strlen($search)==mb_strlen($search,'utf8'))
 	    	{
 	    		$sql .= " and (a.nick_name like binary concat('%',?,'%') or a.mobile=? or a.login_account like binary concat('%',?,'%') )";
 	    		array_push($para,$search,$search,$search);
 	    	}
 	    	else{
 	    		$sql .= " and (a.nick_name like binary concat('%',?,'%') or  a.login_account like binary concat('%',?,'%') )";
 	    		array_push($para,$search,$search);
 	    	}
 	    }else if(!empty($deptid)) {
 	    	$sql .= " and a.dept_id=? ";
 	    	array_push($para,$deptid);
 	    }
 	    $sql .= " order by a.id limit ".$lastid.",".$limit;
 	    //array_push($para,(int)$lastid);
 	    $staffs= $this->conn->GetData("staff",$sql,$para);
 	    $result = $staffs["staff"]["rows"];
        return  $result;
	  }

	public function querySearchCount($deptid,$state,$search){
	  	$info=$this->getInfo();
	  	$eno=$info["eno"];
	  	if($state=="9") { //导入人员还未激活状态
	  		$sql = " select count(1) cnt from we_staff_import a where a.eno=? and state_id='0' ";
     	    $para = array($eno);
     	    if(!empty($search)) {
     	    	if(strlen($search)==mb_strlen($search,'utf8')){
     	    		$sql .= " and (a.nick_name like binary concat('%',?,'%') or a.mobile=? or  a.login_account like binary concat('%',?,'%') ) ";
     	    		array_push($para,$search,$search,$search);
     	    	}
     	    	else{
     	    		$sql .= " and (a.nick_name like binary concat('%',?,'%') or  or  a.login_account like binary concat('%',?,'%') ) ";
     	    		array_push($para,$search,$search);
     	    	}
     	    }
         	$staffs= $this->conn->GetData("staff",$sql,$para);
	        $result = $staffs["staff"]["rows"];
        	return  $result;
	  	}
	  	$sql="select count(1) cnt from we_staff a  where a.eno=?  ";
	  	$para=array($eno);
	  	if(!empty($search)) {
 	    	if(strlen($search)==mb_strlen($search,'utf8'))
 	    	{
 	    		$sql .= " and (a.nick_name like binary concat('%',?,'%') or a.mobile=? or a.login_account like binary concat('%',?,'%') )";
 	    		array_push($para,$search,$search,$search);
 	    	}
 	    	else{
 	    		$sql .= " and (a.nick_name like binary concat('%',?,'%') or  a.login_account like binary concat('%',?,'%') )";
 	    		array_push($para,$search,$search);
 	    	}
 	    }else if(!empty($deptid)) {
 	    	$sql .= " and a.dept_id=? ";
 	    	array_push($para,$deptid);
 	    }
 	    $staffs= $this->conn->GetData("staff",$sql,$para);
 	    $result = (int)$staffs["staff"]["rows"][0]["cnt"];
        return  $result;
	}	  
	  
	public function queryAllBaseInfo($paramObj)
	{
		$deptid = isset($paramObj['deptid']) ? $paramObj['deptid'] : '';
		$limit = isset($paramObj['limit']) ? (int)$paramObj['limit'] :100;
		$pageno = isset($paramObj['page_num']) ? (int)$paramObj['page_num'] :1;
	  	$info=$this->getInfo();
	  	$eno = $info["eno"];
	  	$edomain=$this->container->getParameter("edomain");
	  	$deptObj = new Dept($this->conn,$this->conn_im,$this->container);
	  	$start = ($pageno-1)*$limit;
	  	$end = $pageno*$limit;;
	  	if(empty($deptid))
	  	{
	  	    $sql = 'select loginname from im_employee where loginname like concat(\'%\',?) order by id limit '.$start.','.$limit;
	  	    $result = $this->conn_im->GetData('t',$sql,array($eno.'@'.$edomain));
	  	    $result = $result['t']['rows'];
	  	}
	  	else
	  	{
	  		$deptdata = $deptObj->getinfo($deptid);
	  		$path = $deptdata['path'];
	  		$sql = 'select a.loginname from im_employee a,im_base_dept b where a.deptid=b.deptid and b.path like concat(?,\'%\') and a.loginname like concat(\'%\',?) order by a.id limit '.$start.','.$limit;
	  		$result = $this->conn_im->GetData('t',$sql,array((string)$path,$eno.'@'.$edomain));
	  	    $result = $result['t']['rows'];
	  	}

	  	foreach ($result as $key => $value) {
	  		$staffdata = $this->getStaffInfo($value['loginname']);
	  		$result[$key] = $staffdata;	  				
	  	}
	  	return Utils::WrapResultOK($result);
	}	

	public function queryAllCount($paramObj)
	{
		$deptid = isset($paramObj['deptid']) ? $paramObj['deptid'] : '';
		
	  	$info=$this->getInfo();
	  	$eno = $info["eno"];
	  	$edomain=$this->container->getParameter("edomain");
	  	$deptObj = new Dept($this->conn,$this->conn_im,$this->container);
	  	if(empty($deptid))
	  	{
	  	    $sql = 'select count(1) cnt from im_employee where loginname like concat(\'%\',?) ';
	  	    $result = $this->conn_im->GetData('t',$sql,array($eno.'@'.$edomain));
	  	    $result = $result['t']['rows'];
	  	}
	  	else
	  	{
	  		$deptdata = $deptObj->getinfo($deptid);
	  		$path = $deptdata['path'];
	  		$sql = 'select count(1) cnt from im_employee a,im_base_dept b where a.deptid=b.deptid and b.path like concat(?,\'%\') and a.loginname like concat(\'%\',?) ';
	  		$result = $this->conn_im->GetData('t',$sql,array((string)$path,$eno.'@'.$edomain));
	  	    $result = $result['t']['rows'];
	  	}
	  	return $result[0]['cnt'];
	}  
	  //获取指定在线状态的所有好友和同事的jid
	  //$onlinestate：在线状态。1：在线 0：离线 空：全部
	  public function getFriendAndColleagueJid($onlinestate=1)
	  {
	  	  $eno = "";
	  	  if($this->userInfo!=null)
	  	     $eno = $this->userInfo->eno;
	  	  else
	  	  {
	  	  	 $userInfo= $this->getInfo();
	  	  	 $eno = $userInfo["eno"];
	  	  }
	  	  $domain = $this->container->getParameter('edomain');
	  	  $paras=array();
	  	  $sql="SELECT loginname jid FROM im_employee where loginname like concat('%',?,'@','".$domain."') union select jid from rosterusers where username=?";
	  	  if($onlinestate==1)
	  	  {
	  	     $sql = "select a.jid from (".$sql.") a, global_session b where a.jid=b.us and a.jid not in(?,?,?,?,?)";	
	  	  }
	  	  else if($onlinestate==0)
	  	  {
	  	     $sql = "select a.jid from (".$sql.") a, global_session b where not exists (select 1 from global_session where us=a.jid) and a.jid not in(?,?,?,?,?)";	
	  	  }
	  	  else
	  	  {
	  	     $sql = "select a.jid from (".$sql.") a where a.jid not in(?,?,?,?,?)";
	  	  }
	  	  $paras[]=(string)$eno;
	  	  $paras[]=(string)$this->account;
	  	  $paras[]="admin-".$eno."@".$domain;
	  	  $paras[]="sale-".$eno."@".$domain;
	  	  $paras[]="front-".$eno."@".$domain;
	  	  $paras[]="service-".$eno."@".$domain;
	  	  $paras[]="guest-".$eno."@".$domain;
	  	  $ds=$this->conn_im->getData("t",$sql,$paras);
	      if($ds && count($ds["t"]["rows"])>0)
	      {
	      	  $result=array();
	      	  for($i=0; $i<count($ds["t"]["rows"]); $i++)
	      	     $result[]= $ds["t"]["rows"][$i]["jid"];
	          return 	$result;
	      }
	      return array();		  	  
	  }
	  
	  public function getFriendCount()
	  {
		  if($this->userInfo!=null)
	  	  {
	  	     $jid = $this->userInfo->fafa_jid;

	  	     $eno = $this->userInfo->eno;
	  	  }
	  	  else
	  	  {
	  	  	 $userInfo= $this->getInfo();
	  	  	 $jid = $userInfo["fafa_jid"];
	  	  	 $eno = $userInfo["eno"];
	  	  }	
		  $paras=array();
		  $domain = $this->container->getParameter('edomain');
	  	  $sql="select count(1) cnt from rosterusers where username=? and subscription='B' and jid not in(?,?,?,?,?)";
	  	  $paras[]=(string)$jid;
	  	  $paras[]="admin-".$eno."@".$domain;
	  	  $paras[]="sale-".$eno."@".$domain;
	  	  $paras[]="front-".$eno."@".$domain;
	  	  $paras[]="service-".$eno."@".$domain;
	  	  $paras[]="guest-".$eno."@".$domain;
	  	  $ds=$this->conn_im->getData("t",$sql,$paras);
	  	  return $ds["t"]["rows"][0]["cnt"];	  	
	  }
	  
	  public function getFriendJidList($conv_id="")
	  {
	  	  $jid="";
	  	  $eno = "";
	  	  if($this->userInfo!=null)
	  	  {
	  	     $jid = $this->userInfo->fafa_jid;
	  	     $eno = $this->userInfo->eno;
	  	  }
	  	  else
	  	  {
	  	  	 $userInfo= $this->getInfo();
	  	  	 $jid = $userInfo["fafa_jid"];
	  	  	 $eno = $userInfo["eno"];
	  	  }	
		    $paras=array();
		    $domain = $this->container->getParameter('edomain');
	  	  $sql="select jid from rosterusers where username=? and subscription='B' and jid not in(?,?,?,?,?)";
	  	  $paras[]=(string)$jid;
	  	  $paras[]="admin-".$eno."@".$domain;
	  	  $paras[]="sale-".$eno."@".$domain;
	  	  $paras[]="front-".$eno."@".$domain;
	  	  $paras[]="service-".$eno."@".$domain;
	  	  $paras[]="guest-".$eno."@".$domain;
	  	  $ds=$this->conn_im->getData("t",$sql,$paras);
	  	  $list = array();
		    if($ds && count($ds["t"]["rows"])>0)
	      {
	      	  for($i=0; $i<count($ds["t"]["rows"]); $i++)
	      	  {
	          	$list[]= $ds["t"]["rows"][$i]["jid"];
	      	  }
	      }
	      if ( !empty($conv_id))
	  	  {
	  	     $Announcer = new \Justsy\BaseBundle\Management\Announcer($this->container);
	  	     $jid = $Announcer->broadcaster_staffJid($conv_id);
	  	     if ( count($list)>0 && count($jid)>0)
	  	     {
	  	        $list = array_merge($list,$jid);
	  	     }
	  	     else if ( count($list)==0 )
	  	     {
	  	        $list = $jid;
	  	     }
	  	  }
	      return $list;
	  }
	  
	  //获得朋友圈人员范围
	  public function getFriendCircle_jid($conv_id)
	  {
	     $staff_jid = array();
	     $da = $this->conn_im;
	     //获得人员部分
	     $sql = "select objid from im_convers_announcer where conv_id=? and type='2';";
	     try
	     {
	        $ds = $da->GetData("table",$sql,array((string)$conv_id));
	        if ( $ds && $ds["table"]["recordcount"]>0)
	        {
	            for($i=0;$i < $ds["table"]["recordcount"];$i++)
	            {
	                array_push($staff_jid,$ds["table"]["rows"][$i]["objid"]);
	            }
	        }
	     }
	     catch(\Exception $e)
	     {}
	     //获得部门部分
	     $sql = " select loginname jid from im_employee emp inner join im_base_dept dept on emp.deptid=dept.deptid inner join (select b.path from im_convers_announcer a inner join im_base_dept b on objid=deptid where conv_id=? and type='1') area on position(area.path in dept.path)>0;";
	     try
	     {
	        $ds = $da->GetData("table",$sql,array((string)$conv_id));
	        if ( $ds && $ds["table"]["recordcount"]>0)
	        {
	            for($i=0;$i< $ds["table"]["recordcount"];$i++)
	            {
	                array_push($staff_jid,$ds["table"]["rows"][$i]["jid"]);	                	                
	            }
	        }
	     }
	     catch(\Exception $e)
	     {}
	     return $staff_jid;
	  }

	  public function getFriendLoginAccountList($broadcaster="")
	  {
	  	  $jid="";
	  	  $eno = "";
	  	  $deptid = "";
	  	  if($this->userInfo!=null)
	  	  {
	  	     $jid = $this->userInfo->fafa_jid;
	  	     $eno = $this->userInfo->eno;
	  	     $deptid = $this->userInfo->dept_id;
	  	  }
	  	  else
	  	  {
	  	  	 $userInfo= $this->getInfo();
	  	  	 $jid = $userInfo["fafa_jid"];
	  	  	 $eno = $userInfo["eno"];
	  	  	 $deptid = $userInfo["dept_id"];
	  	  }	
		    $paras=array();
		    $domain = $this->container->getParameter('edomain');
	  	  $sql="select jid from rosterusers where username=? and subscription='B' and jid not in(?,?,?,?,?)";
	  	  $paras[]=(string)$jid;
	  	  $paras[]="admin-".$eno."@".$domain;
	  	  $paras[]="sale-".$eno."@".$domain;
	  	  $paras[]="front-".$eno."@".$domain;
	  	  $paras[]="service-".$eno."@".$domain;
	  	  $paras[]="guest-".$eno."@".$domain;
	  	  $ds=$this->conn_im->getData("t",$sql,$paras);
	  	  $list = array();
		  if($ds && count($ds["t"]["rows"])>0)
	      {
	      	  for($i=0; $i<count($ds["t"]["rows"]); $i++)
	      	  {
	          	$list[]= $ds["t"]["rows"][$i]["jid"];
	      	  }
	      }
	      if(count($list)>0)
	      {
	      	  $sql = "select login_account from we_staff where fafa_jid in('".implode("','",$list)."')";
	      	  $ds=$this->conn->getData("t",$sql,array());
	      	  $list = array();
    			  if($ds && count($ds["t"]["rows"])>0)
    		    {
    		      	  for($i=0; $i<count($ds["t"]["rows"]); $i++)
    		      	  {
    		          	$list[]= $ds["t"]["rows"][$i]["login_account"];
    		      	  }
    		    } 	
	  	  }
	  	  if ( !empty($broadcaster) && $broadcaster=="1")
	  	  {
	  	     $Announcer = new \Justsy\BaseBundle\Management\Announcer($this->container);
	  	     $jid = $Announcer->broadcaster_staff($eno,$deptid,$jid);
	  	     if ( count($list)>0 && count($jid)>0)
	  	     {
	  	        $list = array_merge($list,$jid);
	  	     }
	  	     else if ( count($list)==0)
	  	     {
	  	        $list = $jid;
	  	     }
	  	  }
	      return $list;
	  }	  

	  public function getFriendBaseinfoList()
	  {
	  	  $jid="";
	  	  if($this->userInfo!=null)
	  	  {
	  	     $jid = $this->userInfo->fafa_jid;
	  	     $eno = $this->userInfo->eno;
	  	  }
	  	  else
	  	  {
	  	  	 $userInfo= $this->getInfo();
	  	  	 $jid = $userInfo["fafa_jid"];
	  	  	 $eno = $userInfo["eno"];
	  	  }	
		  $paras=array();
		  $domain = $this->container->getParameter('edomain');
	  	  $sql="select jid from rosterusers where username=? and subscription='B' and jid not in(?,?,?,?,?)";
	  	  $paras[]=(string)$jid;
	  	  $paras[]="admin-".$eno."@".$domain;
	  	  $paras[]="sale-".$eno."@".$domain;
	  	  $paras[]="front-".$eno."@".$domain;
	  	  $paras[]="service-".$eno."@".$domain;
	  	  $paras[]="guest-".$eno."@".$domain;
	  	  $ds=$this->conn_im->getData("t",$sql,$paras);
	  	  $list = array();
		  if($ds && count($ds["t"]["rows"])>0)
	      {
	      	  for($i=0; $i<count($ds["t"]["rows"]); $i++)
	      	  {
	          	$list[]= $ds["t"]["rows"][$i]["jid"];
	      	  }
	      }
	      if(count($list)>0)
	      {
	      	$FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
	      	$sql = "select eno,'' eshortname,login_account,nick_name,concat('".$FILE_WEBSERVER_URL."',photo_path) photo_path,fafa_jid,concat('".$FILE_WEBSERVER_URL."',photo_path_small) photo_path_small,concat('".$FILE_WEBSERVER_URL."',photo_path_big) photo_path_big from we_staff where fafa_jid in('".implode("','",$list)."')";
	      	$ds=$this->conn->getData("t",$sql,array());
	      	$list = array();
			if($ds && count($ds["t"]["rows"])>0)
		    {
		    	$enMgr = new Enterprise($this->conn,$this->logger,$this->container);
		    	//判断是否同一企业，不在同一企业时，加上企业简称一起返回
		    	for ($i=0; $i < count($ds["t"]["rows"]); $i++) { 
		    		$t_eno = $ds["t"]["rows"][$i]["eno"];
		    		if($eno==$t_eno) continue;
		    		//获取企业简称.在朋友圈发动态@好友时使用。
		    		$endata = $enMgr->getinfo($t_eno);
		    		if(!empty($endata))
		    		{
		    			$ds["t"]["rows"][$i]["eshortname"] = $endata["eshortname"];
		    			//$ds["t"]["rows"][$i]["nick_name"] = $ds["t"]["rows"][$i]["nick_name"]."{".$endata["eshortname"]."}";
		    		}
		    	}
		      	return $ds["t"]["rows"];
		    } 	
	  	  }
	      return null;
	  }

	//获取人员的关系列表
	public function getRelation($paraObj=null)
	{
		$parameters = $paraObj;

		$relationtype = empty($parameters)? null : $parameters["relationtype"];
	  	$list=array("friends"=>array(),"groupsbyadmin"=>array(),"circlesbyadmin"=>array());
	  	$eno = "";
	  	$jid="";	 
	  	$eshortname=""; 	  
	  	if($this->userInfo!=null)
	  	{
	  	     $eno = $this->userInfo->eno;
	  	     $jid = $this->userInfo->fafa_jid;
	  	     $eshortname = $this->userInfo->eshortname;
	  	}
	  	else
	  	{
	  	  	 $userInfo= $this->getInfo();
	  	  	 $eno = $userInfo["eno"];
	  	  	 $jid = $userInfo["fafa_jid"];
	  	  	 $enInfo= $this->getEnInfo();
	  	  	 $eshortname = $enInfo["eshortname"];
	  	}
	  	if( empty( $relationtype) || $relationtype=="friend")
	  	{
		  	$paras=array();
		  	$domain = $this->container->getParameter('edomain');
		  	$sql="select jid from rosterusers where username=? and jid not in(?,?,?,?,?)";
		  	$paras[]=(string)$jid;
		  	$paras[]="admin-".$eno."@".$domain;
		  	$paras[]="sale-".$eno."@".$domain;
		  	$paras[]="front-".$eno."@".$domain;
		  	$paras[]="service-".$eno."@".$domain;
		  	$paras[]="guest-".$eno."@".$domain;
		  	$ds=$this->conn_im->getData("t",$sql,$paras);
	  	  	$FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
	    	if($ds && count($ds["t"]["rows"])>0)
	    	{
	      	  $result=array();
	      	  $sqls =array();
	      	  for($i=0; $i<count($ds["t"]["rows"]); $i++)
	      	  {
	      	  	 //排除公共帐号	      	  	 
	      	  	 if(count($result)>100)
	      	  	 {
	      	  	 	   $sqls[]= "select dept_id,login_account,nick_name as name,concat('$FILE_WEBSERVER_URL',photo_path) photo_path,fafa_jid from we_staff where fafa_jid in('".implode("','",$result)."')";
	      	  		   $result=array();
	      	  	 }
	      	     $result[]= $ds["t"]["rows"][$i]["jid"];	      	     
	      	  }
	      	  if(count($result)>0)
	      	     $sqls[]= "select dept_id,login_account,nick_name as name,concat('$FILE_WEBSERVER_URL',photo_path) photo_path,fafa_jid from we_staff where fafa_jid in('".implode("','",$result)."')";

	          $ds=$this->conn->getData("t1",implode(" union ",$sqls),array());
	          $list["friends"]= $ds["t1"]["rows"];
	    	}
	    }
	    if( empty( $relationtype) || $relationtype=="circle")
	    {
		    //获取可管理的圈子
		    $sql = "select a.circle_id, case when ifnull(a.enterprise_no, '')='' then a.circle_name else ? end circle_name, a.circle_desc, concat('$FILE_WEBSERVER_URL',a.logo_path) logo_path, a.create_staff, a.create_date, 
				a.join_method, a.enterprise_no, a.network_domain, a.allow_copy, concat('$FILE_WEBSERVER_URL',a.logo_path_small) logo_path_small, concat('$FILE_WEBSERVER_URL',a.logo_path_big) logo_path_big
				from we_circle a, we_circle_staff b
				where a.circle_id=b.circle_id
		  		and b.login_account=? and (a.create_staff=? or 0<instr(a.manager, ?))";
		    $paras=array();
		    $paras[]=(string)$eshortname;
		    $paras[]=(string)$this->account;
		    $paras[]=(string)$this->account;
		    $paras[]=(string)$this->account;
		    $rowset = $this->conn->getData("c",$sql,$paras);
		    if($rowset && count($rowset["c"]["rows"])>0)
		       $list["circlesbyadmin"]= $rowset["c"]["rows"];
		}
		if( empty( $relationtype) || $relationtype=="group")
		{
			//获取可管理的群组
			$sql="select groupid from im_groupemployee where employeeid=? and grouprole in('owner','manager')";
			$rowset = $this->conn_im->getData("im_g",$sql,array((string)$jid));
			if($rowset && count($rowset["im_g"]["rows"])>0)
			{
			  	   $groupids=array();
			  	   $paraChar = array();
			  	   for($i=0; $i<count($rowset["im_g"]["rows"]); $i++)
			  	   {
			  	   	   $paraChar[]="?";
			  	   	   $groupids[]= (string)$rowset["im_g"]["rows"][$i]["groupid"];
			  	   }
			  	   $sql = "select a.circle_id,case when ifnull(b.enterprise_no, '')='' then concat(a.group_name,'(',b.circle_name,')') else a.group_name end group_name, a.group_id, a.group_desc, a.group_photo_path, a.join_method,	a.create_staff, a.create_date
			  	   		from we_groups a,we_circle b 	where a.circle_id=b.circle_id and a.fafa_groupid in(".implode(",",$paraChar).")";
			  	   $rowset = $this->conn->getData("g",$sql,$groupids);
			  	   if($rowset && count($rowset["g"]["rows"])>0)
			  	    $list["groupsbyadmin"]= $rowset["g"]["rows"];
			}
		}
		return $list;	  	  
	}
	  //获取当前人员的同事
	  //$limit_num:获取同事数量，未指定时默认为全部
	  public function getColleague($limit_num)
	  {
	  	  $eno = "";
	  	  if($this->userInfo!=null)
	  	     $eno = $this->userInfo->eno;
	  	  else
	  	  {
	  	  	 $userInfo= $this->getInfo();
	  	  	 $eno = $userInfo["eno"];
	  	  }
	  	  $sql = "select login_account,nick_name,dept_id,photo_path,fafa_jid from we_staff where eno=? and state_id='1' and login_account!=? and not exists (select 1 from we_micro_account where number=login_account) order by photo_path desc,total_point desc";
	  	  if(!empty($limit_num))
	  	  {
	  	      $sql .= " limit 0,".$limit_num;
	  	  }
	  	  $ds=$this->conn->getData("t",$sql,array((string)$eno,(string)$this->account));
	  	  return $ds["t"]["rows"];
	  }
	  
	  public function getTag()
	  {
	  	  $tag = new UserTag($this->conn,$this->logger);
	  	  return $tag->gettag($this->account);
	  }

    //注册新用户
	public function createstaff($parameter)
	{
		$deploy_mode = $this->container->getParameter('deploy_mode');
	    $mobile_num = $parameter['account'];
	    $mobile_pwd = $parameter['password'];
	    $org_pwd = $mobile_pwd; //原始密码
	    $deptid =isset($parameter['deptid']) ? $parameter['deptid'] : "";
	    $eno =isset($parameter['eno']) ? $parameter['eno'] : $this->container->getParameter('ENO');
	    
	    $nick_name = $parameter['nick_name']; 
	    $ldap_uid =isset($parameter["ldap_uid"]) ? $parameter["ldap_uid"] : "";
	    //优先采用第三方标识做为帐号，其次使用手机号做为帐号
	    $login_account = empty($ldap_uid) ? $mobile_num : $ldap_uid;

	    if (empty($login_account))
	    {
	      return Utils::WrapResultError("请输入帐号！");
	    }   
	    if (empty($mobile_pwd))
	    {
	      return Utils::WrapResultError("请输入密码！");
	    }
	    if (empty($nick_name))
	    {
	      return Utils::WrapResultError("请输入昵称！");
	    }
	    if ( !strpos($login_account,"@"))
	    {
	    	$domain = $this->container->getParameter('edomain');
	        $login_account .= "@".$domain; 
	  	}

	  	$this->account = $login_account;

	  	$isHd = $this->getInfo();
	  	if(!empty($isHd))
	  	{
	  		//已经注册，直接返回
	  		return Utils::WrapResultOK($isHd);
	  	}
	  	if(!empty($mobile_num) && Utils::validateMobile($mobile_num))
	  	{
	  		if($this->checkUser($mobile_num))
	  		{
	  			return Utils::WrapResultError("该手机号已被绑定，请解绑后重试");
	  		}
	  	}
	    $da = $this->conn;
	    $da_im = $this->conn_im;
	    try
	    {	      
	      //同步人员到业务系统	      
	      $syncurl=null;
	      try{
	        //判断是否需要同步到其他系统
	        $syspara =new \Justsy\BaseBundle\DataAccess\SysParam($this->container);
	        $syncurl = $syspara->GetSysParam('staff_sync_url',''); 
	      }
	      catch(\Exception $e)
	      {
	      }

	      $cacheobj = new \Justsy\BaseBundle\Management\Enterprise($da,$this->logger,$this->container);   
	      //获取用户认证模块              
	      $authConfig = $cacheobj->getUserAuth();      
	      if(!empty($syncurl) && !empty($authConfig))
	      {
	          try{
	              $classname  = $authConfig["ssoauthmodule"];
	              if(!empty($classname))
	              {
	                  $parameters = array(
	                    "nick_name"=> $nick_name,
	                    "mobile"   => $login_account,
	                    "password" => $mobile_pwd
	                  );
	                  $classname = "\Justsy\InterfaceBundle\SsoAuth\Sso".$classname;
	                  $re = call_user_func(array($classname, 'createUser'),$this->container,$parameters); 
	                  $ldap_uid = $re["ldap_uid"]; //该 属性必须由对应用户认证模块的方法createUser返回 
	              }
	          }
	          catch(\Exception $e)
	          {
	              $this->get("logger")->err($e);
	              return Utils::WrapResultError($e->getMessage());
	          }
	      } 
	      if(!empty($authConfig) && empty($eno))
	      {
	          $eno = $authConfig["ENO"];
	      }
	      	      
	      //验证企业号	      
	      if (!empty($eno))
	      {      	
	        	$enterinfo =   $cacheobj->getInfo($eno);        
	        	if ($enterinfo==null)
	        	{
	          		return Utils::WrapResultError("未找到您注册的企业！");
	        	}
	        	$edomain=$enterinfo['edomain'];
	      }
	      if(empty($deptid))
	      {
		  	$deptinfo = new \Justsy\BaseBundle\Management\Dept($da,$da_im);
          	$deptid=$deptinfo->getDefaultDept($eno);
          	$fafa_deptid = $deptid["deptid"];
          	$deptid = $deptid["deptid"];
      	  }
      	  else
      	  {
      	  	$deptinfo = new \Justsy\BaseBundle\Management\Dept($da,$da_im);
          	$deptid=$deptinfo->getinfo($deptid);
          	$fafa_deptid = $deptid["deptid"];
          	$deptid = $deptid["deptid"];
      	  }

	      $auth_level = "S";
	        
	      $eno_vip=$enterinfo['vip_level'];
	      $eno_level=$enterinfo['eno_level'];
	      $edomain=$enterinfo['edomain'];       
	      $circleId = $enterinfo['circle_id'];  
	      //注册jid
	      $jid = SysSeq::GetSeqNextValue($da,"we_staff","fafa_jid");
	      $jid .= "-".$eno."@".$edomain;
	      //生成密码
	      $user = new UserSession($login_account, $mobile_pwd, $login_account, array("ROLE_USER"));
	      $factory = $this->container->get("security.encoder_factory");
	      $encoder = $factory->getEncoder($user);
	      $pwd = $encoder->encodePassword($mobile_pwd,$user->getSalt());

	      $mobile_pwd = DES::encrypt($mobile_pwd);

	      $istester = ""; //是否是通过万能验证码激活的测试人员
	      //插入人员、圈子信息
	      $sqls[] = "insert into we_staff (dept_id,login_account,eno,password,nick_name,photo_path,state_id,fafa_jid,photo_path_small,photo_path_big,openid,register_date,active_date,t_code,auth_level,mobile,mobile_bind,ldap_uid,login_source) values (?,?,?,?,?,?,?,?,?,?,?,(select register_date from we_register where login_account=?),now(),?,?,?,?,?,?)";
	      $sqls[] = "insert into we_circle_staff (circle_id,login_account,nick_name) values (?,?,?)";
	      $sqls[] = "update we_register set state_id='3' where login_account=?";
	      $paras[] = array(
	        (string)$deptid,
	        (string)$login_account,
	        (string)$eno,
	        (string)$pwd,
	        (string)$nick_name,
	        (string)'',
	        (string)"1",
	        (string)$jid,
	        (string)'',
	        (string)'',
	        (string)md5($eno.$login_account),
	        (string)$login_account,
	        (string)$mobile_pwd,
	        (string)$auth_level,
	        empty($mobile_num) ? null:(string)$mobile_num,
	        empty($mobile_num) ? null:(string)$mobile_num,
	        (string)$ldap_uid,
	        $istester);

	      $paras[] = array(
	        (string)$circleId,
	        (string)$login_account,
	        (string)$nick_name);

	      $paras[] = array((string)$login_account);
	      $da->ExecSQLs($sqls,$paras);
	      //向RBAC跟新用户身份
	      //$staffRole=new \Justsy\BaseBundle\Rbac\StaffRole($da,$da_im,$this->logger);
	      //$staffRole->InsertStaffRoleByCode($login_account,$auth_level.$eno_vip,$eno);    
	    }
	    catch (\Exception $e) 
	    {
	      $this->logger->err($e);
	      return Utils::WrapResultError($e->getMessage());
	    }
	    //写we_im库
	    $sqls = array(); $paras = array();
	    try
	    {     
	      //写入人员 如果$jid_old为空执行原有逻辑，否则更新旧jid数据
	      if (empty($jid_old))
	      {
	        $sqls = array(); 
	        $paras = array();
	        $pinyin = Utils::Pinyin($nick_name);
	        $employeeid = SysSeq::GetSeqNextValue($da_im,"im_employee","employeeid");
	        $sqls[] = "insert into im_employee (employeeid, deptid, loginname, password, employeename,spell) values (?, ?, ?, ?, ?,?)";
	        $paras[] = array(
	          (string)$employeeid,
	          (string)$fafa_deptid,
	          (string)$jid,
	          (string)$mobile_pwd,
	          (string)$nick_name,
	          (string)$pinyin
	        );
	        $sqls[] = "insert into users (username, password, created_at) values (?, ?, now())";
	        $paras[] = array(
	          (string)$jid,
	          (string)$mobile_pwd
	        );
	        $sqls[] = "insert into im_b_msg_read (employeeid, lastid, readdatetime) values (?, (select max(id) from im_b_msg), now())";
	        $paras[] = array(
	          (string)$jid
	        );
	        $da_im->ExecSQLs($sqls,$paras);
	        try{
	             $da_im->ExecSQL("call dept_emp_stat(?)",array((string)$jid));
	        }
	        catch(\Exception $e){}

	        $this->syncAttrsToIM();

	        $jid_old = $jid;
	      }
	    }
	    catch(\Exception $e)
	    {
	      $this->logger->err($e);
	      return Utils::WrapResultError($e->getMessage());
	    }
		//关注自己所属企业的开放的内部公众号
		$mac = new \Justsy\BaseBundle\Management\MicroAccountMgr($da,$da_im,$login_account,$this->logger,$this->container);
		$mac->attenCompanyOpenAccount();    //自动关注当前企业的开放公众号
		$re = $this->getInfo();
		return Utils::WrapResultOK($re);  	
	}

	//检查密码是否更新
	public function checkpassword($container,$da,$db_im,$login_account,$password)
	{
			 $success = false;
			 $user = new UserSession($login_account,$password,$login_account,array("ROLE_USER"));
			 $factory = $container->get("security.encoder_factory");
			 $encoder = $factory->getEncoder($user);
			 $new_password = $encoder->encodePassword($password,$user->getSalt());
			 $sql = "select `password` from we_staff where login_account=?;";
			 try
			 {
			 	  $ds = $da->GetData("table",$sql,array((string)$login_account));
			 	  if ($ds && $ds["table"]["recordcount"]>0){
			 	  	if ( $ds["table"]["rows"][0]["password"]!=$new_password){
			 	  		$success = $this->changepassword($login_account,$password,$factory);			 	  		  
			 	  	}
			 	  }
			 }
			 catch(\Exception $e){
			 	 $container->get("logger")->err($e->getMessage());
			 }
			 return $success;
	}	  
	//更改密码.直接重置密码
	//当省略$pass和$factory参数时，表示采用的统一数据访问接口提交的数据
	//所有相关参数需要从第一个参数是解析获取
	public function changepassword($account,$pass=null,$factory=null)
	{
		if(empty($pass) && empty($factory))
		{
			$parameters =$account;
			$account =isset($parameters["account"])&&!empty($parameters["account"]) ? $parameters["account"]:$this->account;
			$pass = $parameters["newpass"];
			$factory = $this->container->get("security.encoder_factory");
		}
	  	$sqls_im=array();
      	$paras_im = array(); 
      	$this->account=$account;
		$u_flag=$this->getInfo();
		if(empty($u_flag)){
			throw new \Exception("帐号无效！");
		}
		if(empty($pass))
		{
			throw new \Exception("密码不能为空！");
		}
		$jid = $u_flag["fafa_jid"];
		$user = new \Justsy\BaseBundle\Login\UserSession($this->account, $pass, $this->account, array("ROLE_USER"));
		$encoder = $factory->getEncoder($user);
		$t_code=DES::encrypt($pass);
		$micro_password = $encoder->encodePassword($pass, $this->account);
		$sqls_im[] = "update im_employee set password=? where loginname=?";
		$paras_im[] = array((string)$t_code,(string)$jid);
		$sqls_im[] = "update users set password=? where username=?";
		$paras_im[] = array((string)$t_code,(string)$jid);
		$sql = "update we_staff set password=? ,t_code=? where login_account=?";
		$paras = array((string)$micro_password,(string)$t_code,(string)$this->account);
		$this->conn->ExecSQL($sql, $paras);
		$this->conn_im->ExecSQLs($sqls_im, $paras_im);
		//刷新缓存
	  	$this->getInfo(true);
    	return Utils::WrapResultOK(true);
	}

	//修改密码。需要确认原密码
	public function updatepassword($parameter)
	{
	  	$re = array();
	  	$user = $parameter["user"];
	  	$factory = $this->container->get('security.encoder_factory');
	  	$encoder = $factory->getEncoder($user);
	  	$oldpwd = $parameter['txtoldpwd'];
	  	$pwd = $parameter["txtnewpwd"];
	  	if(empty($oldpwd)){
	  		return Utils::WrapResultError("原密码不能为空");
	  	}
	  	if(empty($pwd)){
	  		return Utils::WrapResultError("新密码不能为空");
	  	}
	  	$da = $this->conn;
	  	$da_im = $this->conn_im;
	  	$Jid = $user->fafa_jid;
	  	$eno = $user->eno;
	  	$OldPass = $user->getPassword();
	  	$Old_t_code = $user->t_code;
	  	 
	  	$oldpwd = $encoder->encodePassword($oldpwd, $user->getSalt());
	  	if ($oldpwd != $OldPass)
	  	{
	  		return Utils::WrapResultError("原密码不正确");
	  	}
	  	$t_code = DES::encrypt($pwd);
	  	$sql = "update we_staff set password=?,t_code=? where login_account=?";
	  	$paras[0] = $encoder->encodePassword($pwd, $user->getSalt());
	  	$paras[1] =$t_code;
	  	$paras[2] = $user->getUsername();
	  	try
	  	{
	  		$da->ExecSQL($sql,$paras);
	  		//同步ejabberd
	  		try{
	  			$jid = $user->fafa_jid;
	  			$sqls_im =array("update im_employee set password=? where loginname=?");
				$paras_im=array( array((string)$t_code,(string)$jid));
				$sqls_im[] = "update users set password=? where username=?";
				$paras_im[] = array((string)$t_code,(string)$jid);
	  			
	  			$da_im->ExecSQLs($sqls_im, $paras_im);
	  			$this->getInfo(true);
	  			return Utils::WrapResultOK(true);
	  		}
	  		catch(\Exception $e)
	  		{
	  			//还原原密码
	  			$sql = "update we_staff set password=?,t_code=? where login_account=?";
	  			$paras[0] = $OldPass;
	  			$paras[1] = $Old_t_code;
	  			$paras[2] = $user->getUsername();
	  			$da->ExecSQL($sql,$paras);
	  			return Utils::WrapResultError("同步密码出错");
	  		}
	  	}
	  	catch(\Exception $e)
	  	{
	  		return Utils::WrapResultError("系统出错");
	  	}
	}

    //获得用户头像保存至Ｍongo
    public function SaveUserHead($image_url){
    	if (empty($image_url)) return;
      	//取用户头像
      	$filename = "";
      	try
      	{
		   	$path = rtrim($_SERVER['DOCUMENT_ROOT'],'\/')."/upload";
		    if (!is_dir($path))
		        mkdir($path);
		    $filename = explode("@",$this->account);
		    $filename = $filename[0];
		    $filename = $path."/".$filename.".png";
		    ob_start(); 
			readfile($image_url);
			$img=ob_get_contents();
			ob_end_clean();	  
			$size=strlen($img);
		   	$fp2=@fopen($filename,'a');
		    fwrite($fp2,$img);
		    fclose($fp2);
      	}
      	catch(\Exception $e){
     	  	$filename = "";     	  
	 	    $this->logger->err($e);
	 	    return false;
      	}
     	//将文件存入mongo
      	$fileid = $this->saveFile($filename);
      	if (!empty($fileid)){
      		$sql = "update we_staff set photo_path=?,photo_path_small=?,photo_path_big=? where login_account=? or ldap_uid=? or openid=?";
      		$para = array((string)$fileid,(string)$fileid,(string)$fileid,(string)$this->account,(string)$this->account,(string)$this->account);
      	try
      	{
      		$this->conn->ExecSQL($sql,$para);
      		//刷新缓存
	   		$this->getInfo(true);
	   		$this->syncAttrsToIM();
      	}
      	catch(\Exception $e){
      		$this->logger->err($e);
      		return false;
      	}
      	return true;
      }
    }  
   
    public function changeLoginAccount($newAccount,$factory)
	  {
  			$oldUser = $this->getInfo(); //原用户信息
  			$jid = $oldUser["fafa_jid"];
  			$t_code = $oldUser["t_code"];
  			$ldap_uid = $oldUser["ldap_uid"];
  			$pass = DES::decrypt($t_code);	
	  		if ( !strpos($newAccount,"@"))
	  		{ 
	  			$domain =  $this->container->getParameter('edomain');
			    $ldap_uid = $newAccount;
			    $newAccount .= "@".$domain;
	  		}  			
			$user = new \Justsy\BaseBundle\Login\UserSession($newAccount, $pass, $newAccount, array("ROLE_USER"));
			$encoder = $factory->getEncoder($user);
			$micro_password = $encoder->encodePassword($pass, $newAccount);
			$sql = "update we_staff set password=?,t_code=?,ldap_uid=? where login_account=?";
			$paras = array((string)$micro_password,(string)$t_code,(string)$ldap_uid,(string)$this->account);
			$result = array();
			try
			{
			   	$dataexec = $this->conn->ExecSQL($sql, $paras);
			   	//更新帐号 
				$this->conn->ExecSQL("call p_change_login_account(?,?)", array((string)$this->account,(string)$newAccount));
				//刷新缓存
		   	 	$this->getInfo(true);
	       		$result=Utils::WrapResultOK("");
		  }
		  catch(\Exception $e){		  	
		  	$result=Utils::WrapResultError($e->getMessage());
		  }
		  return $result;
	  }

    //将文件保存到mogo
	private function saveFile($filename)
	{
			$fileid = "";
			try
			{
				if (!empty($filename) && file_exists($filename)){ 
			    	$newfile = sys_get_temp_dir()."/".basename($filename);
	         	if (rename($filename,$newfile)){			    
				      //进行mongo操作
					  $dm = $this->container->get('doctrine.odm.mongodb.document_manager'); 
					  $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
					  $doc->setName(basename($newfile));
					  $doc->setFile($newfile);
					  $dm->persist($doc);
					  $dm->flush();
					  $fileid = $doc->getId();
					  //存入mongo后删除源文件
					  if (file_exists($filename))
					    unlink($filename);
			    }
		    }
		  }
		  catch(\Exception $e){
		  	$this->logger->err($e);
		  	$fileid = "";
		  }
		  return $fileid;
	}
	

  //保存人员头像数据
	public function modifyavatar($parameter)
	{
	    $re = array("success" => true);
	    $request = $this->container->getRequest();
	    $user = $parameter["user"];
	    $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
	    $da = $this->container->get("we_data_access");
	    $da_im = $this->container->get("we_data_access_im");
	    $login_account = $user->getUsername();
	    $photofile = $_FILES['photofile']['tmp_name'];
	    if(empty($photofile)){
	        $photofile = tempnam(sys_get_temp_dir(), "we");
	        unlink($photofile);
	        $somecontent1 = base64_decode($parameter['photodata']);
	        if ($handle = fopen($photofile, "w+")) {
	          if (!fwrite($handle, $somecontent1) == FALSE) {   
	              fclose($handle);  
	          }
	        }
	    }
	    $photofile_24 = $photofile."_24";
	    $photofile_48 = $photofile."_48";
	    try 
	    {
	      if (empty($photofile)) throw new \Exception("param is null");
	      $im = new \Imagick($photofile);
	      $im->scaleImage(48, 48);
	      $im->writeImage($photofile_48);
	      $im->destroy();
	      $im = new \Imagick($photofile);
	      $im->scaleImage(24, 24);
	      $im->writeImage($photofile_24);
	      $im->destroy();

	      $table = $this->getInfo();
	      if (!empty($table))  //如果用户原来有头像则删除
	      {
	        Utils::removeFile($table["photo_path"],$dm);
	        Utils::removeFile($table["photo_path_small"],$dm);
	        Utils::removeFile($table["photo_path_big"],$dm);
	      }
	      if (!empty($photofile)) $photofile = Utils::saveFile($photofile,$dm);
	      if (!empty($photofile_48)) $photofile_48 = Utils::saveFile($photofile_48,$dm);
	      if (!empty($photofile_24)) $photofile_24 = Utils::saveFile($photofile_24,$dm);
	      $da->ExecSQL("update we_staff set photo_path=?,photo_path_big=?,photo_path_small=? 
	        where login_account=?",
	        array((string)$photofile_48, (string)$photofile, (string)$photofile_24, (string)$login_account));
	      $da_im->ExecSQL("update im_employee set photo=? where loginname=?",array(
	      		(string)$photofile,(string)$table["fafa_jid"]
	      ));	      
	      $this->getInfo(true);
		    $this->syncAttrsToIM();
	      $message = json_encode(array('jid'=>$user->fafa_jid,"path" => $this->container->getParameter('FILE_WEBSERVER_URL').$photofile));
	      Utils::sendImPresence($user->fafa_jid,implode(",", $this->getFriendJidList()),"staff-changeinfo",$message,$this->container,"","",false,Utils::$systemmessage_code);        

	      $re["success"] = true;
	      $re["fileid"] = $photofile;
	      $re["photo_path"] = $photofile_48;
	      $re["photo_path_big"] = $photofile;
	      $re["photo_path_small"] = $photofile_24;
	    }
	    catch (\Exception $e) 
	    {
	      $re["success"] = false;
	      $this->get('logger')->err($e);
	    }
	    return $re;
	}

  	//保存人员头像数据
	public function save_Photo($parameter)
	{
	    $success = true;
	    $session = $this->container->get('session');
		  $path =    $session->get("avatar_big");	     
	    $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
	    $login_account = $parameter["login_account"];
	    $fileid = Utils::saveFile($path,$dm);
	    $session->remove("avatar_big");
	    return array("success"=>$success,"fileid"=>$fileid);
	}
  
  private function removeFile($path)
  {
    $dm = $this->get('doctrine.odm.mongodb.document_manager'); 
         if (!empty($path))
         {
            $doc = $dm->getRepository('JustsyMongoDocBundle:WeDocument')->find($path);
            if(!empty($doc))
               $dm->remove($doc);
            $dm->flush();
         }
         return true;
  }  
	  	 
	//获取帐号状态
	//注册状态：0－已注册、1－已驳回、2－已审核、3－已激活
	private function getState()
	{
	    $obj = $this->getInfo();
	    if(empty($obj))
	    	return null;
	    return $obj["state_id"];
	}
  
	//将部门底下的人员设置为互为好友
  public function DeptAddFriend($container,$deptid,$owner_jid,$owner_nick,$datarow)
  {  	
  	 	$da_im = $this->conn_im;
  	 	$success = true;
  	 	if(empty($datarow) || count($datarow)==0) return $success;
  	 	$sql = "insert into rosterusers (username,jid,nick,subscription,ask,askmessage,server,subscribe,type,created_at)values";
		$values=array();
  	 	for($i=0;$i< count($datarow);$i++)
  	 	{
	  	 	$jid = $datarow[$i]["jid"];
	  	 	$nick_name = $datarow[$i]["nick_name"];	  	 	
	  	 	if ( $jid == $owner_jid) continue;
	  	 	//判断是否已经存在，已经存在不添加
	  	 	if ($this->checkFriend($jid,$owner_jid))
	  	 	{
	  	 		$da_im->ExecSQL('delete from rosterusers where (username=? and jid=?) or (username=? and jid=?)',
	  	 			array((string)$owner_jid,(string)$jid,(string)$jid,(string)$owner_jid)
	  	 		);
	  	 	}
	  	 	$values[]="('".$jid."','".$owner_jid."','".$owner_nick."','B','N','','N','','item',now())";
	  	 	$values[]="('".$owner_jid."','".$jid."','".$nick_name."','B','N','','N','','item',now())";
  	 	}
  	 	if(count($values)>0)
  	 	{
			try
			{
				$da_im->ExecSQL($sql.implode(",",$values));
			}
			catch(\Exception $e)
			{
				$success = false;
				$container->get("logger")->err($e->getMessage());
			}	 	
		}
  	 	//将记录添加到rosterdept
  	 	if ( !$this->CheckRosterdept($deptid,$owner_jid))
  	 	{
		 	 $sql = "insert into rosterdept(deptid,jid)values(?,?);";
		 	 $para = array((string)$deptid,$owner_jid);
		 	 try
		 	 {
		 	   $da_im->ExecSQL($sql,$para);
		 	 }
		 	 catch(\Exception $e)
		 	 {
		 	 	 $success = false;
		 	 	 $container->get("logger")->err($e->getMessage());
		 	 }
	   	}	
	 	return $success;
  }
  
  //删除部门好友关系
  public function delFriend($deptid,$jid,$logger=null)
  {
     $sqls = array();$paras = array();
     $sql = "delete from rosterusers where jid=? and username in(select jid from rosterdept where jid!=? and deptid=?);";
     $para = array((string)$jid,(string)$jid,(string)$deptid);
     array_push($sqls,$sql);
     array_push($paras,$para);
     $sql = "delete from rosterusers where username=? and jid in(select jid from rosterdept where jid!=? and deptid=?);";
     $para = array((string)$jid,(string)$jid,(string)$deptid);
     array_push($sqls,$sql);
     array_push($paras,$para);
     $sql = "delete from rostergroups where jid=? and username in(select jid from rosterdept where jid!=? and deptid=?);";
     $para = array((string)$jid,(string)$jid,(string)$deptid);
     array_push($sqls,$sql);
     array_push($paras,$para);
     $sql = "delete from rostergroups where username=? and jid in(select jid from rosterdept where jid!=? and deptid=?);";
     $para = array((string)$jid,(string)$jid,(string)$deptid);
     array_push($sqls,$sql);
     array_push($paras,$para);
     $sql = "delete from rosterdept where jid=? and deptid=?;";
     $para = array((string)$jid,(string)$deptid);
     array_push($sqls,$sql);
     array_push($paras,$para);
     $success = true;
     try
     {
        $this->conn_im->ExecSQLs($sqls,$paras);
     }
     catch(\Exception $e)
     {
        $success = false;
        if ( !empty($logger))
            $logger->err($e->getMessage());
     }
     return $success;
  }
  
  //判断是否已经互为好友
  private function checkFriend($username,$jid)
  {
  	 $da_im = $this->conn_im;
  	 $exists = false;
  	 $sql = "select 1 from rosterusers where (username=? and jid=?) or (username=? and jid=?)";
  	 $para = array((string)$username,(string)$jid,(string)$jid,(string)$username);
  	 try
  	 {
	  	 $ds = $da_im->GetData("table",$sql,$para);
	  	 if ( $ds && $ds["table"]["recordcount"]>0)
	  	   $exists = true;
  	 }
  	 catch(\Exception $e)
  	 {
  	 	 $this->logger->err($e->getMessage());
  	 }
     return $exists;
  }
  
  //判断好友分组
  private function checkFriendGroup($username,$jid)
  {
  	 $da_im = $this->conn_im;
  	 $exists = false;
  	 $sql = "select 1 from rostergroups where (username=? and jid=?) or (username=? and jid=?)";
  	 $para = array((string)$username,(string)$jid,(string)$jid,(string)$username);
  	 try
  	 {
	  	 $ds = $da_im->GetData("table",$sql,$para);
	  	 if ( $ds && $ds["table"]["recordcount"]>0)
	  	   $exists = true;
  	 }
  	 catch(\Exception $e)
  	 {
  	 	 $this->logger->err($e->getMessage());
  	 }
     return $exists;
  }
  
  //判断人员部门是否存在
  private function CheckRosterdept($deptid,$jid)
  {
  	 $da_im = $this->conn_im;
  	 $exists = false;
  	 $sql = "select 1 from rosterdept where deptid=? and jid=?;";
  	 $para = array((string)$deptid,(string)$jid);
  	 try
  	 {
	  	 $ds = $da_im->GetData("table",$sql,$para);
	  	 if ( $ds && $ds["table"]["recordcount"]>0)
	  	   $exists = true;
  	 }
  	 catch(\Exception $e)
  	 {
  	 	 $this->logger->err($e->getMessage());
  	 }
     return $exists;
  }  

//启用当前帐号
public function enabled()
{
    $state = $this->getState();
    if("1"!= $state)
    {
        $sql = "update we_staff set state_id=? where login_account=?";
        $ds=$this->conn->ExecSQL($sql,array("1",(string)$this->account));
        $this->getInfo(true);
    }
    return true;
}

//根据1个或多个邮箱帐号检查帐号是否已存在并返回。支持dsid统一数据访问
public function checkmail($parameter)
{
  	$list = $parameter["list"];//邮箱列表。多个手机号用,分隔
  	if(empty($list))
  	{
  		return Utils::WrapResultError("邮箱不能为空");
  	}
  	$sqlqlist = array();
  	$para=array();
  	$list = explode(",", $list);
  	$reglist = array();
  	for ($i=0; $i < count($list); $i++) {
  		$m = $list[$i];	
  		if(empty($m)) continue;
  		$this->account = $m;
  		$staffdata = $this->getInfo();
  		if(empty($staffdata)) continue;
  		$reglist[] =array("mobile"=>$staffdata["mobile_bind"],"login_account"=>$m,"fafa_jid"=>$staffdata["fafa_jid"],"nick_name"=>$staffdata["nick_name"]);
  	}
  	return $reglist;	
}

//根据1个或多个手机号检查帐号是否已存在并返回。支持dsid统一数据访问
public function checkmobile($parameter)
{
  	$list = $parameter["list"];//手机号列表。多个手机号用,分隔
  	if(empty($list))
  	{
  		return Utils::WrapResultError("手机号不能为空");
  	}
  	$sqlqlist = array();
  	$para=array();
  	$list = explode(",", $list);
  	$reglist = array();
  	for ($i=0; $i < count($list); $i++) {  		 
  		$m = $list[$i];	
  		if(empty($m)) continue;
  		$sqlqlist[] = "?";
  		$para[] = (string)$m;
  	}
  	if(count($sqlqlist)==0)
  		return Utils::WrapResultError("手机号不能为空");
  	$sql = "select mobile_bind mobile,login_account,fafa_jid,nick_name from we_staff where mobile_bind in(".implode(",", $sqlqlist).")";
    try
    {
        $ds = $this->conn->GetData("t",$sql,$para);
        if ( $ds && count($ds["t"]["rows"])>0)
        {
        	for ($i=0; $i < count($ds["t"]["rows"]); $i++) 
        	{
        		$reglist[] = $ds["t"]["rows"][$i];
        	}
        }
        return $reglist;
    }
    catch(\Exception $e)
    {
        $this->logger->err($e->getMessage());
        return Utils::WrapResultError($e->getMessage());
    }
}
  
//判断用户是否存在
public function checkUser($mobile)
{
     $result = false;
     $sql = "";$para = array();
     if ( !empty($mobile))
     {
         $sql ="select login_account from we_staff where mobile_bind=?";
         $para = array((string)$mobile);
     }
     else
     {
         $sql ="select login_account from we_staff where login_account=?;";
         $para = array((string)$this->account);
     }
     try
     {
        $ds = $this->conn->GetData("t",$sql,$para);
        if ( $ds && $ds["t"]["recordcount"]>0)
          $result = true;
     }
     catch(\Exception $e)
     {
        $this->logger->err($e->getMessage());
     }
     return $result;
}
  
  //搜索应用权限人员范围
  public function search_approle_staff($parameter)
  {
     $staff = $parameter["staff"];
     $appid = $parameter["appid"];
     $type  = isset($parameter["type"]) ? $parameter["type"] : 0;
     $user = $parameter["user"];
     $eno = $user->eno;
     $condition = "";
     $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
     $sql = "";$para = array();
     if ( $type=="1")
     {
        $sql = "select fafa_jid,nick_name,case when type=2 and ifnull(photo_path,'')!='' then concat('".$FILE_WEBSERVER_URL."',photo_path) else '' end header_img
    	           from we_staff inner join we_app_role on fafa_jid=objid where eno=? and type=2 and appid=? ";
    	  $para = array((string)$eno,(string)$appid);
     }
     else
     {
    	   $sql = "select fafa_jid,nick_name,case when ifnull(photo_path,'')!='' then concat('".$FILE_WEBSERVER_URL."',photo_path) else '' end header_img
    	           from we_staff where eno=? and not exists (select 1 from we_app_role where fafa_jid=objid and appid=? and type=2) ";
    	   $para = array((string)$eno,(string)$appid);
	   }
	   if ( !empty($staff))
	   {
	      if (strlen($staff)>mb_strlen($staff,'utf8'))
	          $sql.= " and nick_name like concat('%',?,'%') ";
     	  else
     	    	$sql.=" and login_account like concat('%',?,'%') ";
     	  array_push($para,(string)$staff);
	   }
	   $sql = $sql." order by login_account desc ";
	   $success = true;$list = array();
	   try
	   {
	      $ds = $this->conn->GetData("table",$sql,$para);
	      if ( $ds && $ds["table"]["recordcount"]>0)
	          $list = $ds["table"]["rows"];
	   }
	   catch(\Exception $e)
	   {
	      $success = false;
	      $this->logger->err($e->getMessage());
	   }
	   return array("success"=>$success,"list"=>$list);	   
  }  
  
  //保存应用权限
  public function save_approle($parameter)
  {
     $appid = isset($parameter["appid"]) ? $parameter["appid"]:null;
     $deptid = isset($parameter["deptid"]) ? $parameter["deptid"] : null;
     $staffid = isset($parameter["staffid"]) ? $parameter["staffid"] : null;
     $sqls = array();$paras = array();$success = true;
     if ( !empty($deptid))
     {
        //删除原来的部门权限重新添加
        $sql = "delete from we_app_role where appid=? and type=1;";
        array_push($sqls,$sql);
        array_push($paras,array((string)$appid));   
        $deptids = explode(",",$deptid);
        for($i=0;$i< count($deptids);$i++)
        {
            $sql = "insert into we_app_role(appid,objid,type)values(?,?,1);";
            array_push($sqls,$sql);
            array_push($paras,array((string)$appid,(string)$deptids[$i]));        
        }
     }
     if ( !empty($staffid))
     {
        $staffids = explode(",",$staffid);
        for($i=0;$i< count($staffids);$i++)
        {
            $sql = "insert into we_app_role(appid,objid,type)values(?,?,2);";
            array_push($sqls,$sql);
            array_push($paras,array((string)$appid,(string)$staffids[$i]));        
        }
     }
     if ( count($sqls)>0 && count($paras)>0)
     {
        try
        {
            $this->conn->ExecSQLs($sqls,$paras);
        }
        catch(\Exception $e)
        {
            $success = false;
            $this->logger->err($e->getMessage());
        }
     }
     return array("success"=>$success);
  }
  
  //删除应用权限人员
  public function del_approle_staff($parameter)
  {
     $appid = isset($parameter["appid"]) ? $parameter["appid"]:null;
     $jid = isset($parameter["jid"]) ? $parameter["jid"] : null;
     $success = true;
     $sql = "delete from we_app_role where objid=? and appid=?;";
     $para = array((string)$jid,(string)$appid);
     try
     {
        $this->conn->ExecSQL($sql,$para);
     }
     catch(\Exception $e)
     {
        $success = false;
        $this->logger->err($e->getMessage());
     }
     return array("success"=>$success);
  }
    
    //查询人员
    public function search_staff($parameter)
    {
        $login_account = $parameter["login_account"];
        $user = $parameter["user"];
        $eno = $user->eno;
        $sql ="select login_account,fafa_jid,nick_name 
               from we_staff a where not exists(select 1 from we_micro_account b where a.login_account=b.number)
                    and not exists(select 1 from we_announcer c where a.login_account=c.login_account) and a.eno=? ";
        $condition = "";$para = array();
        array_push($para,(string)$eno);
        //用户账号或昵称不为空
        if (!empty($login_account)){
            $login_account = str_replace("%","\%",$login_account);
            $login_account = str_replace("_","\_",$login_account);
            $login_account = str_replace("[","\[",$login_account);
            $login_account = str_replace("]","\]",$login_account);        
            if (strlen($login_account)>mb_strlen($login_account,'utf8')){
              $condition = " and nick_name like concat('%',?,'%') ";
              array_push($para,(string)$login_account);
            }
            else {
              $condition = " and (login_account like concat('%',?,'%') or nick_name like concat('%',?,'%')) ";
              array_push($para,(string)$login_account,(string)$login_account);
            }
        }
        else
        {
            $condition = " limit 100 ";
        }
        $sql .= $condition;
        $success = true;$returndata=array();
        try
        {            
            $ds = $this->conn->GetData("table",$sql,$para);
      	    if ( $ds && $ds["table"]["recordcount"]>0)
      	      $returndata = $ds["table"]["rows"];
        }
        catch(\Exception $e){
          	$success = false;
          	$this->logger->err($e->getMessage());
        }
        return array("success"=>$success,"returndata"=>$returndata);
    }
    
    //判断用户是否为管理员
    public function isAdmin()
    {
        $login_account = $this->account;
        $isadmin = false;
        $sql="select 1 from we_enterprise where create_staff=?;";
        try
        {
            $ds=$this->conn->GetData("table",$sql,array((string)$login_account));
            if ( $ds && $ds["table"]["recordcount"]>0)
              $isadmin = true;
        }
        catch(\Exception $e)
        {
            $this->logger->err($e->getMessage());
        }
        return $isadmin;
    }
}