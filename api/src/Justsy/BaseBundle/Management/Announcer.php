<?php

namespace Justsy\BaseBundle\Management;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Management\Identify;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Login\UserSession;
use Justsy\BaseBundle\Management\MicroAccountMgr;
use Justsy\BaseBundle\Common\Cache_Enterprise;

//广播号管理类
class Announcer implements IBusObject
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

    //注册或修改广播员账号
    public function register_announcer($parameter)
    {
        $nick_name = isset($parameter["name"]) ? $parameter["name"] : null;
        $state = isset($parameter["state"]) ? $parameter["state"] : "add";
        $login_account = isset($parameter["login_account"]) ? $parameter["login_account"] : null;
        $account = explode("@",$login_account);
        $ldap_uid = $account[0];
        $deptid = isset($parameter["deptid"]) ? $parameter["deptid"] : array();
        $fileid = isset($parameter["fileid"]) ? $parameter["fileid"] : null;
        $fileid = empty($fileid) ? null : $fileid;
        $password = isset($parameter["password"]) ? $parameter["password"] : null;
        $staffid = isset($parameter["staffid"] ) ? $parameter["staffid"] : array();
        $user = $parameter["user"];
        $success = true;$msg = "";
        $da = $this->conn;
        if ( $state=="add" )  //注册广播员账号
        {
            //取公众号部门id
            $departmentid = "";
            $sql = " select dept_id from we_department where fafa_deptid=?;";
            $ds = $da->GetData("table",$sql,array("v".$user->eno."999888"));
            if ( $ds && $ds["table"]["recordcount"]>0)
            {
                $departmentid = $ds["table"]["rows"][0]["dept_id"];
                $register = new \Justsy\BaseBundle\Controller\ActiveController();
                $register->setContainer($this->container);
                $register_parameter = array("account"=>$login_account,
                         	                  "realName"=>$nick_name,
                         	                  "passWord"=>$password,
                         	                  "ldap_uid"=>$ldap_uid,
                         	                  "eno"=>$user->eno,
                         	                  "ename"=>$user->ename,
                         	                  "isNew"=>'0',
                         	                  "mailtype"=>"1",
                         	                  "import"=>'1',
                         	                  "isSendMessage"=>"N",
                         	                  "mobile"=>"",
                         	                  "duty"=>"",
                         	                  "indefaultgroup"=>"N",  //不加入默认群组
                         	                  "mutual"=>"N",          //不互为好友
                         	                  "deptid"=>$departmentid);
                $success = $register->doSave($register_parameter);
            }
            if ($success )
            {
                //向广播员添加具有的默认菜单项
                $sql = "insert into mb_staff_menu(staff_id,menu_id)values(?,'firendcircle');";
                try
                {
                  $da->ExecSQL($sql,array((string)$login_account));
                }
                catch(\Exception $e)
                {
                }
            }
            if ( !$success )
                $msg = "用户账号(".$login_account.")注册失败！";	 
        }
        else
        {
            //用户修改了密码的操作
            if ( !empty($password))
            {
                $u_staff = new Staff($da,$this->conn_im,$login_account,$this->logger);
                $targetStaffInfo = $u_staff->getInfo();
		            $re = $u_staff->changepassword($targetStaffInfo["login_account"],$password,$this->container->get('security.encoder_factory'));
		            $this->logger("-----------".$re);
            }
        }
        if ( $success )
        {
            $sqls = array();$paras = array();
            //修改头像
            $sql = "update we_staff set nick_name=?,photo_path=?,photo_path_small=?,photo_path_big=? where login_account=?;";
            $para = array((string)$nick_name,(string)$fileid,(string)$fileid,(string)$fileid,(string)$login_account);
            array_push($sqls,$sql);
            array_push($paras,$para);
            //修改im数据
            //获得fafa_jid
            $fafa_jid = "";
            $sql = "select fafa_jid from we_staff where login_account=?;";
            $ds = $da->GetData("table",$sql,array((string)$login_account));
            if ( $ds && $ds["table"]["recordcount"]>0)
            {
                $fafa_jid = $ds["table"]["rows"][0]["fafa_jid"];
                $sql_im = "update im_employee set employeename=?,photo=? where loginname=?;";
                $para_im = array((string)$nick_name,$fileid,(string)$fafa_jid);
                try
                {
                    $this->conn_im->ExecSQL($sql_im,$para_im);
                }
                catch(\Exception $e)
                {
                }
            }
            if ( $state=="edit")
            {
                $sql = "delete from we_announcer where login_account=?;";
                $da->ExecSQL($sql,array((string)$login_account));
            }
            //部门的处理
            for($j=0;$j< count($deptid);$j++)
            {
                $sql = "insert into we_announcer(login_account,objid,`type`)values(?,?,1)";
                $para = array((string)$login_account,(string)$deptid[$j]);
                array_push($sqls,$sql);
                array_push($paras,$para);
            }
            //人员的处理
            for($j=0;$j< count($staffid);$j++)
            {
                $sql = "insert into we_announcer(login_account,objid,`type`)values(?,?,2)";
                $para = array((string)$login_account,(string)$staffid[$j]);
                array_push($sqls,$sql);
                array_push($paras,$para);
            }
            try
            {
                $da->ExecSQLS($sqls,$paras);
            }
            catch(\Exception $e)
            {
                $success = false;
                $msg = "更新用户信息失败！";
                $this->logger->err($e->getMessage());
            }        
        }
        return array("success"=>$success,"msg"=>$msg);
    }

    //查询广播员记录
    public function search_announcer($parameter)
    {
        $success = true;
        $data = array();
        $staff = isset($parameter["staff"]) ? $parameter["staff"] : null;
        $pageindex = isset($parameter["pageindex"]) ? $parameter["pageindex"] : 1;
        $record = isset($parameter["record"]) ? $parameter["record"] : 14;
        $pageindex = $pageindex < 1 ? 1 : $pageindex;
        $limit = " limit ".(($pageindex - 1) * $record).",".$record;
        $recordcount = 0;
        $sql = "select a.login_account,a.nick_name from we_staff a where exists(select 1 from we_announcer b where a.login_account=b.login_account) ";
        $condition  = "";$para = array();
        if ( !empty($staff))
        {
            if (strlen($staff)>mb_strlen($staff,'utf8'))
            {
                $condition = " and a.nick_name like concat('%',?,'%') ";
             	  $para = array((string)$staff);
            }
            else
            {
             	 $condition = " and (a.login_account like concat('%',?,'%') or a.nick_name like concat('%',?,'%')) ";
             	 $para = array((string)$staff,(string)$staff);
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
                $count = $ds["table"]["recordcount"];
                if ( $pageindex==1 && $count>=$record) //当为第一页时返回数据记录条数
                {
                    $sql = "select count(*) `recordcount` from we_staff a where exists (select 1 from we_announcer b where a.login_account=b.login_account) ";
                    $sql .= $condition;
                    if ( count($para)==0)
                        $ds = $this->conn->GetData("table",$sql);
                    else
                        $ds =  $this->conn->GetData("table",$sql,$para);
                    if ( $ds && $ds["table"]["recordcount"]>0)
                        $recordcount = $ds["table"]["rows"][0]["recordcount"];
                }
            }
        }
        catch(\Exception $e)
        {
            $success = false;
            $this->logger->err($e->getMessage());
        }
        return array("success"=>$success,"recordcount"=>$recordcount,"data"=>$data);
    }
    
    //获得广播员信息
    public function get_announcer($parameter)
    {
        $success = true;$returndata = array();$fileid = "";
        $url = $this->container->getParameter('FILE_WEBSERVER_URL');
        $login_account = $parameter["login_account"];
        //查询范围
        $sql = " select objid,nick_name,a.type from we_announcer a inner join we_staff b on objid=fafa_jid where a.type='2' and a.login_account=?".
                " union select objid,dept_name,a.type from we_announcer a inner join we_department b on objid=b.fafa_deptid where a.type='1' and a.login_account=?;";
        try
        {
            $ds = $this->conn->GetData("table",$sql,array((string)$login_account,(string)$login_account));
            if ($ds && $ds["table"]["recordcount"]>0)
               $returndata = $ds["table"]["rows"];
            $sql = "select ifnull(photo_path,'') fileid from we_staff where login_account=?;";
            $ds = $this->conn->GetData("staff",$sql,array((string)$login_account));
            if ( $ds && $ds["staff"]["recordcount"]>0)
               $fileid = $ds["staff"]["rows"][0]["fileid"];
        }
        catch(\Exception $e)
        {
            $success = false;
            $this->container->get("logger")->err($e->getMessage());        
        }
        return array("success"=>$success,"returndata"=>$returndata,"url"=>$url,"fileid"=>$fileid );
    }

    //删除广播员账号
    public function delete_announcer($parameter)
    {
        $login_account = $parameter["login_account"];
        $this->account = $login_account;
        $staffMgr = new \Justsy\BaseBundle\Management\Staff($this->conn,$this->conn_im,$login_account,$this->logger,$this->container);
        $success =  $staffMgr->leave();
        if ( $success )
        {
            $sqls = array();$paras = array();
            $sql = "delete from we_announcer where login_account=?;";
            array_push($sqls,$sql);
            array_push($paras,array((string)$login_account));
            $sql = "delete from mb_staff_menu where staff_id=?;";
            array_push($sqls,$sql);
            array_push($paras,array((string)$login_account));
            try
            {
                $this->conn->ExecSQLs($sqls,$paras);
            }
            catch(\Exception $e)
            {
               $this->container->get("logger")->err($e->getMessage());
            }        
        }
        return array("success"=>$success);
    }
  
    //获得广播账号
    public function announcerAccount($parameter)
    {
        $account = null;
        $sql = "select max(replace(substring(login_account,position('@gb' in login_account)+3),'.com','')+0)+1 account 
             from we_announcer where position('@gb' in login_account)>0;";
        try
        {
            $ds = $this->conn->GetData("table",$sql);
            if ( $ds && $ds["table"]["recordcount"]>0)
            {
                $ac = $ds["table"]["rows"][0]["account"];
                $ac = empty($ac) ? 1 : (int)$ac;
                $account = $ac < 10 ? "0".$ac : $ac;
                $account = "admin@gb".$account.".com";
            }
            else
            {
                $account = "admin@gb01.com";
            }
        }
        catch(\Exception $e)
        {
        }
        return array("account"=>$account);
    }

    //返回广播员
    public function broadcaster_staff($eno,$dept_id,$fafa_jid)
    {
        $conv_id = array();
        $da_im = $this->conn_im;
        //获得当前人员所具有的conv_id
        $sql = "select conv_id from im_convers_announcer where objid=? and type='2'";
        try
        {
            $ds = $da_im->GetData("table",$sql,array((string)$fafa_jid));
            if ( $ds && $ds["table"]["recordcount"]>0)
            {
                for($i=0;$i< $ds["table"]["recordcount"];$i++)
                {
                    array_push($conv_id,$ds["table"]["rows"][$i]["conv_id"]);
                }
            }
        }
        catch(\Exception $e)
        {
            $this->logger->err($e->getMessage());
        }
        //对应部门的判断
        if (!empty($dept_id))
        {
            $sql = "select a.deptid,a.path from im_base_dept a inner join im_employee b on a.deptid=b.deptid where loginname=?;";
            try
            {
                $ds = $da_im->GetData("table",$sql,array((string)$fafa_jid));
                if ( $ds && $ds["table"]["recordcount"]>0)
                {
                    //当前人员的部门path
                    $im_path   = $ds["table"]["rows"][0]["path"];
                    $im_deptid = $ds["table"]["rows"][0]["deptid"];
                    if (!empty($im_path) && !empty($im_deptid))
                    {
                        $sql = "select conv_id,case when objid=? then 1 else position(path in ?) end state 
                                from im_convers_announcer a inner join im_base_dept b on objid=b.deptid where type='1';";
                        $ds = $da_im->GetData("table",$sql,array((string)$im_deptid,(string)$im_path));
                        if ( $ds && $ds["table"]["recordcount"]>0)
                        {
                            for($i=0; $i < $ds["table"]["recordcount"];$i++)
                            {
                                $convid = $ds["table"]["rows"][$i]["conv_id"];
                                $state = (int)$ds["table"]["rows"][$i]["state"];
                                if ( $state>0 && !in_array($convid,$conv_id))
                                {     
                                    array_push($conv_id,$convid);
                                }
                            }
                        }
                    }
                }            
            }
            catch(\Exception $e)
            {     
                $this->logger->err($e->getMessage());  
            }   
        }
        //返回人员广播员
        $login_account = array();
        if ( count($conv_id)>0)
        {
            $sql = "select login_account from we_convers_list  where conv_id in('".implode("','",$conv_id)."');";
            $ds =  $this->conn->GetData("table",$sql);
            if ( $ds && $ds["table"]["recordcount"]>0)
            {
               for($i=0;$i < $ds["table"]["recordcount"];$i++)
               {
                  $account = $ds["table"]["rows"][$i]["login_account"];
                  if ( !in_array($account,$login_account))
                  {
                     array_push($login_account,$account);
                  }
               }
            }
        }    
        return $login_account;
    } 
  
    //返回广播员  
    public function broadcaster_staffJid($conv_id)
    {
        $fafa_jid = array();
        $da_im = $this->conn_im;
        $sql = "select objid jid from im_convers_announcer where conv_id=? and type='2'";
        try
        {
            $ds = $da_im->GetData("table",$sql,array((string)$conv_id));
            if ( $ds && $ds["table"]["recordcount"]>0)
            {
                for($i=0;$i< $ds["table"]["recordcount"];$i++)
                {
                    array_push($fafa_jid,$ds["table"]["rows"][$i]["jid"]);
                }
            }
        }
        catch(\Exception $e)
        {
            $this->logger->err($e->getMessage());
        }
        //对应部门下的人员jid
        $sql = "select `path` from im_convers_announcer inner join im_base_dept on objid=deptid where conv_id=? and type='1';";
        try
        {
            $ds = $da_im->GetData("table",$sql,array((string)$conv_id));
            if ( $ds && $ds["table"]["recordcount"]>0 )
            {
                $path = $ds["table"]["rows"][0]["path"];
                if (!empty($path))
                {
                    $sql = "select loginname as jid from im_base_dept a inner join im_employee b on a.deptid=b.deptid where position(? in path)>0;";
                    $ds = $da_im->GetData("table",$sql,array((string)$path));
                    if ( $ds && $ds["table"]["recordcount"]>0)
                    {
                        for($i=0; $i < $ds["table"]["recordcount"];$i++)
                        {
                            $jid = $ds["table"]["rows"][$i]["jid"];
                            if ( !empty($jid) )
                            {     
                                array_push($fafa_jid,$jid);
                            }
                        }
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
    
    //删除动态人员范围表(后台广播)
    public function delConvers($conv_id)
    {
        $success = true;
        $sql ="delete from im_convers_announcer where conv_id=?";
        try
        {
            $this->conn_im->ExecSQL($sql,array((string)$conv_id));            
        }
        catch(\Exception $e)
        {
            $success = false;
        }
        return $success;
    }
    
	  //后台朋友圈广播信息发
    public function publishFriendCircle($parameter)
    { 
        $da    = $this->conn;
        $da_im = $this->conn_im;
        $user = $parameter["user"];
        $conv_content = isset($parameter["content"]) ? $parameter["content"] : null;
        $fileid = isset($parameter["fileid"]) ? $parameter["fileid"] : Array();
        $conv_type_id = "00";
        $post_to_group="ALL";
        $post_to_circle="9999";
        $conv_id = SysSeq::GetSeqNextValue($da, "we_convers_list", "conv_id");
        $conv = new \Justsy\BaseBundle\Business\Conv();
        $success = $conv->Broadcast($da,$da_im,$user,$conv_id,$conv_type_id,$conv_content,$post_to_group,$post_to_circle,$fileid,"00",$this->container);
        //发布成功才发送出席
        if ( $success)
        {
            $inputArea = new \Justsy\BaseBundle\Controller\CInputAreaController();
            $inputArea->setContainer($this->container);
            $inputArea->sendPresence($conv_id,$da,"9999","ALL","trend");
        }
        return array('success' => $success, 'conv_id' => $conv_id);
    }     
}