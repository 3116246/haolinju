<?php

namespace Justsy\BaseBundle\Management;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Management\Identify;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Login\UserSession;
use Justsy\BaseBundle\Management\MicroAccountMgr;
use Justsy\BaseBundle\Common\Cache_Enterprise;

//服务号管理
class Service implements IBusObject
{
    private $conn=null;
	  private $conn_im=null;
	  private $user=null;   //用户对象
	  private $logger=null;
	  private $container = null;

    public function __construct($_container)
	  {
	    $this->conn = $_container->get("we_data_access");
	    $this->conn_im = $_container->get("we_data_access_im");
	    $this->logger = $_container->get("logger");
	    $this->container = $_container;
	    $token = $_container->get('security.context')->getToken();
	    if(!empty($token))
	  		$user = $token->getUser();
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
	  		return new self($container);
	  }
	  
	  
    //注册或修改服务员
    public function register_service($parameter)
    {
        $nick_name = isset($parameter["name"]) ? $parameter["name"] : null;
        $micro_id  = isset($parameter["micro_id"]) ? $parameter["micro_id"] : null;
        $login_account = isset($parameter["login_account"]) ? $parameter["login_account"] : null;
        $deptid = isset($parameter["deptid"]) ? $parameter["deptid"] : array();
        $fileid = isset($parameter["fileid"]) ? $parameter["fileid"] : null;
        $fileid = empty($fileid) ? null : $fileid;
        if(empty($login_account))
        {
        	$re = $this->serviceAccount($parameter);
        	if(!empty($re))
        	{
        		$login_account = $re['account'];
        	}
        }
        //服务号密码自动生成
        $password = time();
        $staffid = isset($parameter["staffid"] ) ? $parameter["staffid"] : array();
        $manager = isset($parameter["manager"] ) ? $parameter["manager"] : array();
        $introduction=isset($parameter["desc"]) ? $parameter["desc"] : "";  //简介
        $user = $parameter["user"];
        $success = true;$msg = "";
        $da = $this->conn;
        $type = "0";
        $micro_use = "0";     
        $concern_approval = isset($parameter["concern_approval"]) ? $parameter["concern_approval"] : 1;     //0  表示私密 1  表示开放
        $salutatory=null;
        $level = null;
        //注册服务员或修改服务号信息
        $factory = $this->container->get('security.encoder_factory');
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $eno = $user->eno;
        $appid=Utils::getAppid($eno,$login_account);
        $appkey=Utils::getAppkey();
        $MicroAccountMgr=new MicroAccountMgr($this->conn,$this->conn_im,$user,$this->container->get("logger"),$this->container);	  
        $dataexec = $MicroAccountMgr->register($micro_id,$login_account,$nick_name,$type,$micro_use,$introduction,$concern_approval,$salutatory,$level,$password,$fileid,$fileid,$fileid,$factory,$dm,$appid);
        //修改im人员信息表
        $sqls = array();
        $paras = array();
        $fafa_jid = "";
        $staffMgr = new Staff($this->conn,$this->conn_im,$login_account,$this->container->get("logger"),$this->container);
        $ds = $staffMgr->getInfo();
        if (!empty($ds))
        {
            $fafa_jid = $ds["fafa_jid"];
            $sql_im = "update im_employee set employeename=?,photo=? where loginname=?;";
            $para_im = array((string)$nick_name,(string)$fileid,(string)$fafa_jid);
            try
            {
                $this->conn_im->ExecSQL($sql_im,$para_im);
            }
            catch(\Exception $e)
            {
            }
        }
        //如果为修改时删除原来的相关记录
        if (!empty($micro_id))
        {
            $sql =array( "delete from we_service where login_account=?");
            $sql[]="delete from we_staff_atten where atten_id=?";
            $this->conn->ExecSQLs($sql,array(array((string)$login_account),array((string)$login_account)));        
        	//$sql =array( "delete from im_microaccount_memebr where microaccount=?");
            //$sql[]="delete from im_microaccount_msg where microaccount=?";
            //$this->conn_im->ExecSQLs($sql,array(array((string)$fafa_jid),array((string)$fafa_jid))); 
        }
        //部门的处理
        for($j=0;$j< count($deptid);$j++)
        {
            $sql = "insert into we_service(login_account,objid,`type`)values(?,?,1)";
            $para = array((string)$login_account,(string)$deptid[$j]);
            array_push($sqls,$sql);
            array_push($paras,$para);
        }
        //人员的处理
        for($j=0;$j< count($staffid);$j++)
        {
            $sql = "insert into we_service(login_account,objid,`type`)values(?,?,2)";
            $para = array((string)$login_account,(string)$staffid[$j]);
            array_push($sqls,$sql);
            array_push($paras,$para);
        }    
        //管理人员的处理
        for($j=0;$j< count($manager);$j++)
        {
            $sql = "insert into we_service(login_account,objid,`type`)values(?,?,3)";
            $para = array((string)$login_account,(string)$manager[$j]);
            array_push($sqls,$sql);
            array_push($paras,$para);
        }
        try
        {
            $da->ExecSQLS($sqls,$paras);
            //菜单处理
            for($j=0;$j< count($manager);$j++)
            {
                  $sql="select 1 from mb_staff_menu where menu_id='service' and staff_id=?;";
                  $ds = $this->conn->GetData("table",$sql,array((string)$manager[$j]));
                  if ( $ds && $ds["table"]["recordcount"]==0)
                  {
                     $sql="insert into mb_staff_menu(staff_id,menu_id)values(?,'service');";
                     $this->conn->ExecSQL($sql,array((string)$manager[$j]));
                  }
            }
            $memberSql="select employeeid from im_microaccount_memebr where microaccount=?";
            $memberRs = $this->conn_im->getData("m",$memberSql,array((string)$fafa_jid));
            $memberList=array();
            for ($i=0;$i<$memberRs["m"]["recordcount"];$i++) {
            	$memberList[] = $memberRs["m"]["rows"][$i]["employeeid"];
            }
            $account = $this->service_jid($login_account);

            $sqlffix = "insert into im_microaccount_memebr(employeeid,microaccount,lastreadid,subscribedate)values";
            
            //获取需要新加的帐号
            $needAdd=Utils::array_diff_ex($account,$memberList); 
            //获取需要移除删除的帐号
            $needRemove=Utils::array_diff_ex($memberList,$account); 
            //需要移除删除的帐号
            $sqls = array(); 
            if(count($needRemove)<500)
            {
	            if(count($needRemove)>0)
	            {
	            	$this->conn_im->ExecSQL("delete from im_microaccount_memebr where employeeid in('".implode("','", $needRemove)."') and microaccount='".$fafa_jid."'",array());
	            }
            }
            else
            {
            	//使用另外的方式处理删除
            	$keep = Utils::array_intersect_ex($account,$memberList); 
            	$this->conn_im->ExecSQL('delete from im_microaccount_memebr where microaccount=?',array((string)$fafa_jid));
            	foreach ($keep as $key => $ac)
	            {
	                $sqls[] = "('".$ac."','".$fafa_jid."',0,now())";
	                $paras[] = array(); 
	                if($i>0 && $i%10000==0)
	                {
	                    try
	                    {
	                        $this->conn_im->ExecSQL($sqlffix.implode(",", $sqls),array());
	                    }
	                    catch(\Exception $e)
	                    {
	                        $this->logger->err($e->getMessage());
	                    }
	                    $sqls = array();
	                    $paras=array();
	                }
	                $i++;                           
	            }
	            if(count($sqls)>0)
	            {
	                try
	                {
	                    $this->conn_im->ExecSQL($sqlffix.implode(',', $sqls),array());
	                }
	                catch(\Exception $e)
	                {
	                    $this->logger->err($e->getMessage());
	                }
	            }
            }
            $sqls = array();
            $paras=array();
            $i=0;
            //处理新增的帐号
            foreach ($needAdd as $key => $ac)
            {
                $sqls[] = "('".$ac."','".$fafa_jid."',0,now())";
                $paras[] = array(); 
                if($i>0 && $i%10000==0)
                {
                    try
                    {
                        $this->conn_im->ExecSQL($sqlffix.implode(",", $sqls),array());
                    }
                    catch(\Exception $e)
                    {
                        $this->logger->err($e->getMessage());
                    }
                    $sqls = array();
                    $paras=array();
                }
                $i++;                           
            }
           
            if(count($sqls)>0)
            {
                try
                {
                    $this->conn_im->ExecSQL($sqlffix.implode(",", $sqls),array());
                }
                catch(\Exception $e)
                {
                    $this->logger->err($e->getMessage());
                }
            }
        }
        catch(\Exception $e)
        {
            $success = false;
            $msg = "更新用户信息失败！";
            $this->logger->err($e->getMessage());
        }
        return array("success"=>$success,"msg"=>$msg);
    }

    //判断服务号名称
    public function check_service($parameter)
    {
        $micro_id = $parameter["micro_id"];
        $name     = $parameter["name"];
        $success=true;$exists=false;$sql = "";$para=array();
        if (empty($micro_id))
        {
            $sql="select 1 from we_micro_account where name=?;";
            $para = array((string)$name);
        }
        else
        {
            $sql="select 1 from we_micro_account where name=? and id!=?;";
            $para=array((string)$name,(string)$micro_id);
        }
        try
        {
            $ds = $this->conn->GetData("table",$sql,$para);
            if ( $ds && $ds["table"]["recordcount"]>0)
              $exists = true;
        }
        catch(\Exception $e)
        {
            $success = false;
            $this->logger->err($e->getMessage());        
        }
        return array("success"=>$success,"exists"=>$exists);
    }

    //删除服务账号
    public function delete_service($parameter)
    {
        $micro_id = $parameter["micro_id"];
        $login_account = $parameter["login_account"];
        $user = $parameter["user"];
        $MicroAccountMgr=new Staff($this->conn,$this->conn_im,$login_account,$this->container->get("logger"),$this->container);
        $data = $MicroAccountMgr->getInfo();
        if(empty($data))
        {
        	return array("success"=>false);
        }
        $MicroAccountMgr=new MicroAccountMgr($this->conn,$this->conn_im,$login_account,$this->container->get("logger"),$this->container);
        $dataexec =$MicroAccountMgr->removeByID($micro_id);
        $success = true;
        if ( $dataexec === false)   
        {
            $success = false;
        }
        else
        {
            $sqls = array();
            $paras = array();

            $sqls[] = "delete from im_microaccount_msg where microaccount=?";
            $paras[]=array((string)$data["fafa_jid"]);
			$sqls[] = "delete from im_microaccount_memebr where microaccount=?";
            $paras[]=array((string)$data["fafa_jid"]);
            $this->conn_im->ExecSQLS($sqls,$paras);

            $sql = "delete from we_service where login_account=?;";
            try
            {
                $this->conn->ExecSQL($sql,array((string)$login_account));
            }
            catch(\Exception $e)
            {
               $this->container->get("logger")->err($e->getMessage());
               return Utils::WrapResultError($e->getMessage());
            }
        }
        return Utils::WrapResultOK("");
    }
    
    //返回服务员对应的人员范围(账号)
    public function service_loginaccount($login_account)
    {
        $login_accounts = array();
        $para = array();
        $sql = "select objid from we_service where login_account=? and type='2';";
        $para = array((string)$login_account);
        try
        {
            $ds = $this->conn->GetData("table",$sql,$para);
            $RowsLen = $ds["table"]["recordcount"];
            if ( $ds && $RowsLen>0)
            {
            	$Rows = $ds["table"]["rows"];
                for($i=0;$i< $RowsLen;$i++)
                {
                    array_push($login_accounts,$ds["table"]["rows"][$i]["objid"]);
                }
            }
        }
        catch(\Exception $e)
        {
            $this->logger->err($e->getMessage());
        }
        //对应部门下的人员jid
        $fafa_jid = array();
        $sql = "select objid from we_service where login_account=? and type='1';";
        try
        {
            $ds1 = $this->conn->GetData("table",$sql,array((string)$login_account));
            $RowsLen = $ds["table"]["recordcount"];
            if ( $ds1 && $RowsLen>0)
            {
                $deptid = array();
                for($i=0;$i< $RowsLen;$i++)
                {
                    $dept_fafaid = $ds1["table"]["rows"][$i]["objid"];
                    array_push($deptid,($i==0?"":" union ")."select deptid from im_base_dept where path like '%/".$dept_fafaid."/%'");
                } 
                if ( count($deptid)>0)
                {               
                    $sql = implode(" ", $deptid);
                    try
                    {                        
                        $ds_path = $this->conn_im->GetData("table",$sql);
                        $RowsLen = $ds_path["table"]["recordcount"];
                        if ( $ds_path && $RowsLen>0 )
                        {
                            $getStaffSqlAry = array();
                            $sqlffix="select a.login_account from we_staff a ,we_department b where a.dept_id=b.dept_id and b.fafa_deptid in";
                            for($j=0;$j< $RowsLen;$j++)
                            {
                                $dept_fafaid = $ds_path["table"]["rows"][$j]["deptid"];
                                $getStaffSqlAry[]=$dept_fafaid;
                                if(count($getStaffSqlAry)>=255)
                                {
                                    $stafflist = $this->conn->GetData("stafflist",$sqlffix."('".implode("','", $getStaffSqlAry)."')");
                                    $rsLen = count($stafflist["stafflist"]["rows"]);
                                    for($i=0;$i< $rsLen;$i++)
                                    {
                                        $fafa_jid[]=$stafflist["stafflist"]["rows"][$i]["login_account"];
                                    }
                                    $getStaffSqlAry = array();
                                }
                            }
                            if(count($getStaffSqlAry)>0)
                            {
                                $stafflist = $this->conn->GetData("stafflist",$sqlffix."('".implode("','", $getStaffSqlAry)."')");
                                $rsLen = count($stafflist["stafflist"]["rows"]);
                                for($i=0;$i< $rsLen;$i++)
                                {
                                    $fafa_jid[]=$stafflist["stafflist"]["rows"][$i]["login_account"];
                                }
                            }
                        }    
                    }
                    catch(\Exception $e)
                    {
                        $this->logger->err($e->getMessage());
                    }
                }
            }
        }
        catch(\Exception $e)
        {     
            $this->logger->err($e->getMessage());  
        }
        if ( count($fafa_jid)>0)
        {
           $login_accounts = array_merge($login_accounts,$fafa_jid);
        }
        return $login_accounts;
    }
    
    //返回服务员对应的人员范围(fafa_jid)
    public function service_jid($login_account)
    {
        $fafa_jid = array();
        $staffMgr = new Staff($this->conn,$this->conn_im,$login_account,$this->logger,$this->container);
        $sql = "select b.fafa_jid from we_service a inner join we_staff b on a.objid=b.login_account where a.login_account=? and a.type='2'";
        try
        {
            $ds = $this->conn->GetData("table",$sql,array((string)$login_account));
            if ( $ds)
            {
                for($i=0;$i< $ds["table"]["recordcount"];$i++)
                {
                	//$staffMgr->getStaffInfo();
                    array_push($fafa_jid,$ds["table"]["rows"][$i]["fafa_jid"]);
                }
            }
        }
        catch(\Exception $e)
        {
            $this->logger->err($e->getMessage());
        }
        //对应部门下的人员jid  
        $sql = "select objid from we_service where login_account=? and type='1';";
        try
        {
            $ds1 = $this->conn->GetData("table",$sql,array((string)$login_account));
            if ( $ds1 && $ds1["table"]["recordcount"]>0)
            {
                $deptid = array();
                $dept_rows = $ds1["table"]["recordcount"];
                for($i=0;$i< $dept_rows;$i++)
                {
                    $dept_fafaid = $ds1["table"]["rows"][$i]["objid"];
                    array_push($deptid,($i==0?"":" union ")."select deptid from im_base_dept where path like '%/".$dept_fafaid."/%'");
                }
                if ( count($deptid)>0)
                {
                    $sql="select a.loginname jid from im_employee a ,(".implode("", $deptid).") b where a.deptid=b.deptid ";
                	$jidRs = $this->conn_im->GetData("jids",$sql,array());
                	$rsAry = $jidRs["jids"]["rows"];
                	$rsLen = count($rsAry);
                    for($i=0;$i< $rsLen;$i++)
                    {
                        $fafa_jid[]=$rsAry[$i]["jid"];
                    }
                }
            }
        }
        catch(\Exception $e)
        {     
            $this->logger->err($e->getMessage());  
        }
        return $fafa_jid;
    }    

    //查询服务员记录
    public function search_service($parameter)
    {
        $success = true;
        $data = array();
        $staff = isset($parameter["staff"]) ? $parameter["staff"] : null;
        $user = $parameter["user"];
        $curUser = $user->getUserName();
        $pageindex = isset($parameter["pageindex"]) ? $parameter["pageindex"] : 1;
        $record = isset($parameter["record"]) ? $parameter["record"] : 100;
        $pageindex = $pageindex < 1 ? 1 : $pageindex;
        $limit = " limit ".(($pageindex - 1) * $record).",".$record;
        $recordcount = 0;	   
        $groupMgr = new \Justsy\BaseBundle\Management\GroupMgr($this->conn,$this->conn_im,$this->container);
        $manager  = $groupMgr->isManager($user->eno,$curUser);
        $fileurl = $this->container->getParameter("FILE_WEBSERVER_URL");
        if (empty($manager))  //非系统管理员
        { 
            $sql = "select b.id as micro_id,a.fafa_jid as jid,a.login_account,a.nick_name,b.micro_use,concat('{$fileurl}',a.photo_path_big) photo_path,b.type,
                    ifnull((select 'manager' from we_service s where s.objid='".$curUser."' and s.login_account=b.number and type=3 ),'') manager ,(select count(1) msgcount from we_micro_send_message msg where msg.send_account=b.number) msgcount
                    from we_staff a inner join we_micro_account b on a.fafa_jid=b.jid where b.eno=? "; 
        }
        else
        {
            $sql = "select b.id as micro_id,a.fafa_jid as jid,a.login_account,a.nick_name,b.micro_use,concat('{$fileurl}',a.photo_path_big) photo_path,b.type,'".$manager."' manager ,(select count(1) msgcount from we_micro_send_message msg where msg.send_account=b.number) msgcount
                    from we_staff a inner join we_micro_account b on a.fafa_jid=b.jid where b.eno=? ";         
        }
        $condition  = "";
        $page_sql = "select count(*) recordcount from we_staff a inner join we_micro_account b on a.fafa_jid=b.jid where b.eno=?";
        $para = array((string)$user->eno);
        if ( !empty($staff))
        {
            if (strlen($staff)>mb_strlen($staff,'utf8'))
            {
                $condition = " and a.nick_name like concat('%',?,'%') ";
             	  array_push($para,(string)$staff);
            }
	        else
	        {
	            $condition = " and (a.login_account like concat('%',?,'%') or a.nick_name like concat('%',?,'%')) ";
	            array_push($para,(string)$staff,(string)$staff);
	        }
        }
        $sql .= $condition." order by a.login_account asc ".$limit;
        try
        {
            $ds = null;
            if ( count($para)==0)
              $ds = $this->conn->GetData("table",$sql);
            else
              $ds =  $this->conn->GetData("table",$sql,$para);
            if ( $ds && $ds["table"]["recordcount"]>0)
            {
                $data = $ds["table"]["rows"];
                $sql ='select a.send_account,a.send_datetime,b.msg_title,b.msg_text,b.msg_content,msg_summary,msg_img_url,msg_web_url,msg_type from  we_micro_send_message a ,we_micro_message b where a.id=b.send_id and a.send_account=? order by a.send_datetime desc limit 1';
                foreach ($data as $key => $value) {
                	//获取最后推送的消息
                	$data[$key]["message"]=array();
                	if($value['msgcount']==0) continue;
                	$tmp_para = array((string)$value['login_account']);
                	$tmp_ds =  $this->conn->GetData("msg",$sql,$tmp_para);
                	$data[$key]["message"]=$tmp_ds['msg']['rows'];
                }
                $count = $ds["table"]["recordcount"];
                if ( $pageindex==1 && $count>=$record) //当为第一页时返回数据记录条数
                {
                    $page_sql .= $condition;
                    if ( count($para)==0)
                        $ds = $this->conn->GetData("table",$page_sql);
                    else
                        $ds =  $this->conn->GetData("table",$page_sql,$para);
                    if ( $ds && $ds["table"]["recordcount"]>0)
                        $recordcount = $ds["table"]["rows"][0]["recordcount"];
               }
            }
        }
        catch(\Exception $e)
        {
            $success = false;
            $this->logger->err($e->getMessage());
            return Utils::WrapResultError($e->getMessage());
        }     
        return Utils::WrapResultOK($data);
    }
    
    //服务号消息撤回
    public function service_revoke($parameter)
    {
         $msgid = $parameter["msgid"];
         $login_account = $parameter["login_account"];
         $user = $parameter["user"];
         $send_jid = $user->fafa_jid;
         $staffMgr = new \Justsy\BaseBundle\Management\Staff($this->conn,$this->conn_im,$login_account,$this->container->get("logger"),$this->container);   
         $microData = $staffMgr->getInfo();
         $jid = $this->service_sendjid($microData["fafa_jid"]);
         $to_jid = implode(",",$jid);

         $notice = array();
		 $message =json_encode(Utils::WrapMessage('message_revoke',array('type'=>'serviceaccount','msgid'=>$msgid),$notice));
         $success = Utils::sendImMessage($send_jid,$to_jid,"message_revoke",$message,$this->container,"","",false,Utils::$systemmessage_code);
         if ( $success )
         {
            //删除推送消息记录
            $sqls = array();
            $paras = array();
            $sql="delete from we_micro_send_message where id=?;";
            array_push($sqls,$sql);
            array_push($paras,array((string)$msgid));
            $sql="delete from we_micro_message where send_id=?;";
            array_push($sqls,$sql);
            array_push($paras,array((string)$msgid));
            try
            {
                $this->conn->ExecSQLS($sqls,$paras);
                $sql = 'delete from im_microaccount_msg where msgid=?';
                $this->conn_im->ExecSQL($sql,array((string)$msgid));
            }
            catch(\Exception $e)
            {
                $success = false;
                $this->logger->err($e->getMessage());
                return Utils::WrapResultError($e->getMessage());       
            }
         }
         return Utils::WrapResultOK("");
    }
    
    //返回服务员对应消息推送人员的jid
    public function service_sendjid($micro_jid,$onlyonline=false)
    {
        $jids = array();        
        $sql = "select a.employeeid jid from im_microaccount_memebr a where a.microaccount=?;";
        try
        {
            $ds = $this->conn_im->GetData("table",$sql,array((string)$micro_jid));
            $count = $ds["table"]["recordcount"];
            if ( $ds && $count>0)
            {
                foreach ($ds["table"]["rows"] as $key => $value) {
                    $jids[] = $value["jid"];
                }
            }
            else
            	return array();
        }
        catch(\Exception $e)
        {
            $this->logger->err($e->getMessage());
        }
        !$onlyonline ? Utils::resortjid($this->conn_im,$jids) : Utils::findonlinejid($this->conn_im,$jids);
        return $jids;
    }
      
    //查询推送消息记录
    public function search_sendmessage($parameter)
    {
         $success = true;
         $data = array();
         $login_account = isset($parameter["login_account"]) ? $parameter["login_account"] : $parameter["staff"];
         $user = $parameter["user"];
         $pageindex = isset($parameter["pageindex"]) ? $parameter["pageindex"] : 1;
         $record = isset($parameter["record"]) ? $parameter["record"] : 50;
         $pageindex = $pageindex < 1 ? 1 : $pageindex;
         $limit = " limit ".(($pageindex - 1) * $record).",".$record;
         $recordcount = 0;
         $sql = "select a.id messageid,send_account,date_format(a.send_datetime,'%Y-%m-%d %H:%i') senddate,b.msg_title,b.msg_text,b.msg_summary,case send_type when 'TEXT' then '文字消息' when 'PICTURE' then '图文消息' when 'TEXTPICTURE' then '多图文消息' else '' end sendtype
                 from we_micro_send_message a inner join we_micro_message b on b.send_id=a.id where 1=1 ";
         $condition  = "";
         $para = array();
         if ( !empty($login_account))
         {            
        	$condition = " and a.send_account=? ";
        	array_push($para,(string)$login_account);
        		
         }
         $sql .= $condition." order by a.send_datetime desc ".$limit;
         try
         {
            $ds = null;
            if ( count($para)==0)
              $ds = $this->conn->GetData("table",$sql);
            else
              $ds =  $this->conn->GetData("table",$sql,$para);
            if ( $ds && $ds["table"]["recordcount"]>0)
            {
              $data = $ds["table"]["rows"];
              $count = $ds["table"]["recordcount"];
              
            }
         }
         catch(\Exception $e)
         {
            $success = false;
            $this->logger->err($e->getMessage());
            return Utils::WrapResultError($e->getMessage());
         }
         return Utils::WrapResultOK($data);
    }
    
    //查年推送消息详细
    public function getMessageDetail($parameter)
    {
        $msgid = $parameter["msgid"];
        $sql="select msg_title,msg_type,case when msg_type='TEXT' then msg_text else msg_content end as msg_content,ifnull(msg_summary,'') msg_summary,msg_img_url 
              from we_micro_message where send_id=?;";
        $success = true;
        $returndata=array();
        $msg_type="";
        try
        {
            $ds=$this->conn->GetData("table",$sql,array((string)$msgid));
            if ( $ds && $ds["table"]["recordcount"]>0)
            {
              $returndata = $ds["table"]["rows"];
              $msg_type = strtolower($ds["table"]["rows"][0]["msg_type"]);
            }
        }
        catch(\Exception $e)
        {
            $success = false;
            $this->logger->err($e->getMessage());
            return Utils::WrapResultError($e->getMessage());
        }
        return Utils::WrapResultOK($returndata);
    }
    
    //根据部门id，取消关联服务号关注
    public function cancel_atten($parameter)
    {
    	try
    	{
	        $deptid = $parameter["deptid"];
	        $eno = $parameter["eno"];
	        $login_account=$parameter["jid"];
	        if ($deptid=="v".$eno)
	        {
	            $sql="select login_account from we_service where objid=? and type=1;";
	            $ds=$this->conn->GetData("table",$sql,array((string)$deptid));
	            if ( $ds && $ds["table"]["recordcount"]>0)
	            {
	                for($i=0;$i< $ds["table"]["recordcount"];$i++)
	                {
	                    $service = $ds["table"]["rows"][$i]["login_account"];
	                    $sql="delete from im_microaccount_memebr where employeeid=? and microaccount=?;";
	                    $para = array((string)$login_account,(string)$service);
	                    try
	                    {
	                        $this->conn_im->ExecSQL($sql,$para);
	                    }
	                    catch(\Exception $e)
	                    {
	                        $this->logger->err($e->getMessage());
	                    }
	                }                                
	            }
	        }
	        else
	        {
	            //获取当前部门的所有上级部门
	        	$deptMgr = new Dept($this->conn,$this->conn_im,$this->container);
	        	$deptdata = $deptMgr->getInfo($deptid);
	        	if(empty($deptdata)) return;
	        	$path = str_replace('/-10000/v'.$eno.'/', '',rtrim($deptdata['path'],'/')) ;
	        	$path = explode('/', $path);
	        	//获取这些部门关联的公众号
				$sql="select login_account from we_service where objid in('".implode("','", $path)."')  and type=1;";
	            $ds=$this->conn->GetData("table",$sql,array());
	            if ( $ds && $ds["table"]["recordcount"]>0)
	            {
	                for($i=0;$i< $ds["table"]["recordcount"];$i++)
	                {
	                    $service = $ds["table"]["rows"][$i]["login_account"];
	                    $sql="delete from im_microaccount_memebr where employeeid=? and microaccount=?;";
	                    $para = array((string)$login_account,(string)$service);
	                    try
	                    {
	                        $this->conn_im->ExecSQL($sql,$para);
	                    }
	                    catch(\Exception $e)
	                    {
	                        $this->logger->err($e->getMessage());
	                    }
	                }                                
	            }
	        }
        }
    	catch(\Exception $e)
    	{
    		$this->logger->err($e);
    	}
    }
  
    //根据部门id，自动关注部门关联的服务号
    public function atten_service($parameter)
    {
    	try
    	{
	        $deptid = $parameter["deptid"];
	        $eno = $parameter["eno"];
	        $login_account=$parameter["jid"];
	        if ($deptid=="v".$eno)
	        {
	            $sql="select login_account from we_service where objid=? and type=1;";
	            $ds=$this->conn->GetData("table",$sql,array((string)$deptid));
	            if ( $ds && $ds["table"]["recordcount"]>0)
	            {
	                for($i=0;$i< $ds["table"]["recordcount"];$i++)
	                {
	                    $service = $ds["table"]["rows"][$i]["login_account"];
	                    $sql="insert into im_microaccount_memebr(employeeid,microaccount,lastreadid,subscribedate)values(?,?,(select max(id) from im_microaccount_msg where microaccount=?),now())";
	                    $para = array((string)$login_account,(string)$service,(string)$service);
	                    try
	                    {
	                        $this->conn_im->ExecSQL($sql,$para);
	                    }
	                    catch(\Exception $e)
	                    {
	                        $this->logger->err($e->getMessage());
	                    }
	                }                                
	            }
	        }
	        else
	        {
	        	//获取当前部门的所有上级部门
	        	$deptMgr = new Dept($this->conn,$this->conn_im,$this->container);
	        	$deptdata = $deptMgr->getInfo($deptid);
	        	if(empty($deptdata)) return;
	        	$path = str_replace('/-10000/v'.$eno.'/', '',rtrim($deptdata['path'],'/')) ;
	        	$path = explode('/', $path);
	        	//获取这些部门关联的公众号
				$sql="select login_account from we_service where objid in('".implode("','", $path)."')  and type=1;";
	            $ds=$this->conn->GetData("table",$sql,array());
	            if ( $ds && $ds["table"]["recordcount"]>0)
	            {
	                for($i=0;$i< $ds["table"]["recordcount"];$i++)
	                {
	                    $service = $ds["table"]["rows"][$i]["login_account"];
	                    $sql="insert into im_microaccount_memebr(employeeid,microaccount,lastreadid,subscribedate)values(?,?,(select max(id) from im_microaccount_msg where microaccount=?),now())";
	                    $para = array((string)$login_account,(string)$service,(string)$service);
	                    try
	                    {
	                        $this->conn_im->ExecSQL($sql,$para);
	                    }
	                    catch(\Exception $e)
	                    {
	                        $this->logger->err($e->getMessage());
	                    }
	                }                                
	            }
	        }
    	}
    	catch(\Exception $e)
    	{
    		$this->logger->err($e);
    	}
    }

    //获得服务号详细信息
    public function get_service($parameter)
    {
        $success = true;$fileid = "";
        $staff_area = array();$staff_basic = array();
        $url = $this->container->getParameter('FILE_WEBSERVER_URL');
        $login_account = $parameter["login_account"];        
        $fileid = "";
        //查询范围
        $sql = " select objid,nick_name,a.type,'' pid from we_service a inner join we_staff b on objid=b.login_account where a.type in(2,3) and a.login_account=? 
                 union select objid,dept_name,a.type,b.parent_dept_id pid from we_service a inner join we_department b on objid=b.fafa_deptid where a.type='1' and a.login_account=? order by type desc;";
        try
        {
            $ds = $this->conn->GetData("table",$sql,array((string)$login_account,(string)$login_account));
            if ($ds && $ds["table"]["recordcount"]>0)
               $staff_area = $ds["table"]["rows"];
            
            $sql = "select case when ifnull(logo_path,'')='' then case when ifnull(logo_path_big,'')='' then ifnull(logo_path_small,'') else logo_path_big  end else logo_path end fileid,introduction,concern_approval 
                    from we_micro_account where number=?;";
            $ds = $this->conn->GetData("staff",$sql,array((string)$login_account));
            if ( $ds && $ds["staff"]["recordcount"]>0)
            {
                $row = $ds["staff"]["rows"][0];
                $staff_basic["fileid"] = $row["fileid"];
                $staff_basic["desc"] = $row["introduction"];
                $staff_basic["area"] = $row["concern_approval"];
            }
            else
            {
                $staff_basic["fileid"] = "";
                $staff_basic["desc"] = "";
                $staff_basic["area"] = 0;
            }
        }
        catch(\Exception $e)
        {
            $success = false;
            $this->container->get("logger")->err($e->getMessage());        
        }
        return array("success"=>$success,"staff_area"=>$staff_area,"staff_basic"=>$staff_basic,"url"=>$url);
    }
    
    //获得服务号帐号
    public function serviceAccount($parameter)
    {
        $mode = $this->container->getParameter('deploy_mode');
        $mode = strtolower($mode);
        $success=true;$account = null;
        $user = $parameter["user"];
        $eno = $user->eno;       
        $sql = "select max(replace(left(number,position('@' in number)-1),'service',''))+1 account
                from we_micro_account where position('@service.com' in number)>0 and eno=?;";
        try
        {
            $ds = $this->conn->GetData("table",$sql,array((string)$eno));
            if ( $ds && $ds["table"]["recordcount"]>0 && !empty($ds["table"]["rows"][0]["account"]))
            {
                $service = $ds["table"]["rows"][0]["account"];
                $service = empty($service) ? 1 : (int)$service;
                $account = $service < 10 ? "0".$service : $service;
                $account = "service".$account."@service.com";
            }
            else
            {
                if ( !empty($mode) && $mode=="c")
                    $account = "service".$eno."01@service.com";
                else
                    $account = "service01@service.com";
            }
        }
        catch(\Exception $e)
        {
            $success = false;
        }
        return array("success"=>$success,"account"=>$account);
    }

    //将jid转化为用户账号
    public function jidToAccount($jids)
    {
        $jid = "";$login_account=array();
        if ( is_array($jids))
            $jid = "'".implode("','",$jids)."'"; 
        else
            $jid = "'".$jids."'";
        $sql = "select login_account from we_staff where fafa_jid in(".$jid.");";
        try
        {
            $ds = $this->conn->getData("table",$sql);
            if ( $ds && $ds["table"]["recordcount"]>0)
            {
                for($j=0;$j< $ds["table"]["recordcount"];$j++)
                {
                    array_push($login_account,$ds["table"]["rows"][$j]["login_account"]);
                }
            }
        }
        catch(\Exception $e)
        {
            $this->logger->err($e->getMessage());
        }
        return $login_account;
    }
}