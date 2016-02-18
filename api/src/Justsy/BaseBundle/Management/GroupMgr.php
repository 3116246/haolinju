<?php

namespace Justsy\BaseBundle\Management;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Common\Cache_Enterprise;
class GroupMgr
{
	  private $conn=null;
	  private $conn_im=null; 
	  private $container = null;
	  private $logger = null;
    public function __construct($_db,$_db_im,$container=null)
    {
        $this->conn = $_db;
        $this->conn_im = $_db_im;
        $this->container = $container;
        if ( !empty($container))
            $this->logger = $container->get("logger");
    }
    public function GetByIM($grouid,$refreshCache=false)
    {
    	try
    	{
    		$data=Cache_Enterprise::get("group_",$grouid,$this->container);
    	}
    	catch(\Exception $e)
    	{
    		$this->logger->err($e);
    		$data=null;
    	}
    	if(empty($data) || $refreshCache===true)
    	{
    		$url = $this->container->getParameter('FILE_WEBSERVER_URL');
	        $sql = "select a.*, b.employeename create_staff_name from im_group a left join im_employee b on b.loginname=a.creator where a.groupid=?";
	        $params = array();
	        $params[] = (string)$grouid;
	        $ds = $this->conn_im->GetData("we_groups", $sql, $params);
	        if (count($ds["we_groups"]["rows"]) > 0) 
	        {
	        	if(!empty($ds["we_groups"]["rows"][0]['logo']))
	        	{
	        		$ds["we_groups"]["rows"][0]['logo'] = $url.$ds["we_groups"]["rows"][0]['logo'];
	        	}
	        	Cache_Enterprise::set("group_",$grouid,json_encode($ds["we_groups"]["rows"][0]),0,$this->container);
	        	return $ds["we_groups"]["rows"][0];  
	        }
	        else
	        {
	        	Cache_Enterprise::delete("group_",$grouid,$this->container);
    		    return null;
	        }
        }
    	$returnObj = json_decode($data,true);
    	return $returnObj;
    }	  
	  public function Get($grouid)
	  {
	       $sql = "select a.*, b.nick_name create_staff_name, date_format(a.create_date, '%Y-%c-%e') create_date_d from we_groups a
		left join we_staff b on b.login_account=a.create_staff
		where group_id=?";
		    $params = array();
		    $params[] = (string)$grouid;
		    
		    $ds = $this->conn->GetData("we_groups", $sql, $params);
		    if (count($ds["we_groups"]["rows"]) > 0) return $ds["we_groups"]["rows"][0];	
		    return null;
	  }
	  
	  public function IsExist($groupid,$openid=null)
	  {	      
	      if(!empty($openid))
	      {
	          	$sql = "select count(0) cnt from we_groups where group_id=?";	
	          	$ds = $this->conn->GetData("we_groups", $sql, array((string)$groupid));
					    if ($ds["we_groups"]["rows"][0]["cnt"] > 0) return true;	
					    return false;
	      }
	      else
	      {
	          	$sql = "select count(0) cnt from we_group_staff where group_id=? and login_account =(select distinct login_account from we_staff where login_account=? or openid=? or fafa_jid=?)";	
	          	$ds = $this->conn->GetData("we_groups", $sql, array((string)$groupid,(string)$openid,(string)$openid,(string)$openid));
					    if ($ds["we_groups"]["rows"][0]["cnt"] > 0) return true;	
					    return false;	      	
	      }
	  }
	  
	//根据群组的IM ID返回成员JID
	public function getGroupMembersJidByIM($fafa_groupid,$flag_receive_trend=null)
  	{
  	  try{        
        $sql = "select employeeid jid from im_groupemployee where groupid=?";
        if(!empty($flag_receive_trend))
        {
            	if($flag_receive_trend=="1")
            	{
            	   	$sql .= " and (flag_receive_trend is null or flag_receive_trend='".$flag_receive_trend."')";//Î´ÉèÖÃÊÇ·ñ½ÓÊÕ¶¯Ì¬Í¨ÖªÊ±£¬Ä¬ÈÏÎªÔÊÐí½ÓÊÕ
            	}
            	else
            	{
            	    $sql .= " and flag_receive_trend='".$flag_receive_trend."'";	
            	}
        }
      	$da_im = $this->conn_im;
      	$ds = $da_im->GetData("ims",$sql,array((string)$fafa_groupid));
      	$fafa_jid = array();
	    foreach ($ds["ims"]["rows"] as $key => $value) {
	    	$fafa_jid[]=  $value["jid"];
	    }	       	      	
      	return  $fafa_jid;
      }
      catch(\Exception $e)
      {
      	  return  null;
      }
  	}	  
	public function getGroupMembersJid($groupid,$flag_receive_trend=null)
  	{
  	  try{
  	    $da = $this->conn;
        $sql = " select fafa_groupid from we_groups where group_id=?";
        $table = $da->GetData("group",$sql,array((String)$groupid));
        $fafa_groupid= $table["group"]["rows"][0]["fafa_groupid"];
        
        $sql = "select employeeid jid from im_groupemployee where groupid=?";
        if(!empty($flag_receive_trend))
        {
            	if($flag_receive_trend=="1") //
            	{
            	   	$sql .= " and (flag_receive_trend is null or flag_receive_trend='".$flag_receive_trend."')";//Î´ÉèÖÃÊÇ·ñ½ÓÊÕ¶¯Ì¬Í¨ÖªÊ±£¬Ä¬ÈÏÎªÔÊÐí½ÓÊÕ
            	}
            	else
            	{
            	    $sql .= " and flag_receive_trend='".$flag_receive_trend."'";	
            	}
        }
      	$da_im = $this->conn_im;
      	$ds = $da_im->GetData("ims",$sql,array((string)$fafa_groupid));
      	$fafa_jid = array();
	      for($i=0; $i<count($ds["ims"]["rows"]); $i++)
	       	  $fafa_jid[]=  $ds["ims"]["rows"][$i]["jid"];      	
      	return  $fafa_jid;
      }
      catch(\Exception $e)
      {
      	  return  null;
      }
  	}
    
  
	public function getGroupMembers($parameter)
  	{


  	  try{
        $da = $this->conn;
  	    $da_im = $this->conn_im;

        $groupid = $parameter["groupid"];
        $pageindex = $parameter["page_index"];
        $limit = $parameter["limit"];
        $user = $parameter["user"];

        $sql = "  select groupid, employeeid,grouprole from im_groupemployee where groupid=? limit ".($pageindex-1)*$limit." , ".$limit;
        $table = $da_im->GetData("group",$sql,array((String)$groupid));
        $staffinfo = new Staff($da,$da_im,$user,$this->get("logger"),$this->container);

        foreach ($table["group"]["rows"] as $key => $value) {
            $staff = $staffinfo->getStaffInfo($table["group"]["rows"][$key]['employeeid']);
            $table['group']['rows'][$key]['dept_name'] = $staff["dept_name"];
            $table['group']['rows'][$key]['nick_name'] = $staff['nick_name'];
            $table['group']['rows'][$key]['login_account'] = $staff['login_account'];
            $table['group']['rows'][$key]['mobile'] = $staff['mobile_bind'];
            $table['group']['rows'][$key]['photo_path'] = $staff['photo_path'];
        }
        // $result = Utils::WrapResultOK($table["group"]["rows"]);
        // return $this->container->responseJson($request,$result);
        return $table["group"]["rows"];
      }
      catch(\Exception $e)
      {
      	  return  null;
      }
  	}  

  public function getGroupList($login_account) {
    $sql="select DISTINCT a.group_id ,a.group_name,b.flag_receive_trend as hint from we_groups a LEFT JOIN we_group_staff b ON a.group_id=b.group_id WHERE b.login_account=? ";
    $para=array($login_account);
    $data=$this->conn->GetData('dt',$sql,$para);
    $list['rows']=array();
    $list['count']=0;
    if($data!=null && count($data['dt']['rows'])>0) {
      $list['rows']=$data['dt']['rows'];
      $list['count']=count($data['dt']['rows']);
    }
    return $list;
  }
  
  public function setHint($groupid,$user,$action)
  {
    try {
      $da = $this->conn;
        $gObj = $this->Get($groupid);
        
        if(empty($gObj)) return false;
        $sql = "update we_group_staff set flag_receive_trend=? where group_id=? and login_account=?";
        $da->ExecSQL($sql,array((string)$action,(string)$groupid,(string)$user->getUserName()));
        $sql_im = "update im_groupemployee set flag_receive_trend=? where groupid=? and employeeid=?";
        $this->conn_im->ExecSQL($sql_im,array((string)$action,(string)$gObj["fafa_groupid"],(string)$user->fafa_jid));
        return true;
    } catch (\Exception $e) {
      $this->get('logger')->err($e->getMessage());
    }
    return false;
  }
  
    //添加或修改im群组
    public function editGroup($parameter)
    {
        $da_im = $this->conn_im;
        $sql = "";
        $para = array();
        $success = true;$msg="";        
        $groupid = $parameter["groupid"];
        $groupname = isset($parameter["groupname"]) ? $parameter["groupname"]:null;
        $groupdesc = isset($parameter["groupdesc"]) ? $parameter["groupdesc"]:null;
        $add_member_method = isset($parameter["add_member_method"]) ? $parameter["add_member_method"] : 0;
        $accessright = isset($parameter["accessright"]) ? $parameter["accessright"]:"none";
        $user = isset($parameter["user"]) ? $parameter["user"]:null;
        $creator = $user->fafa_jid;
        $eno = $user->eno;
        $logo = isset($parameter["logo"]) ? $parameter["logo"]:null;
        $max_number = isset($parameter["max_number"]) ? $parameter["max_number"]:0;
        if ( empty($groupid) ){
            $add=true;
            $groupid = SysSeq::GetSeqNextValue($da_im,"im_group","groupid");
            $sql = "insert into im_group(eno,groupid,groupname,groupclass,groupdesc,creator,add_member_method,accessright,logo,max_number,createdate)
                    values(?,?,?,'defaultgroup',?,?,?,?,?,?,now());";
            $para = array((string)$eno,(string)$groupid,$groupname,$groupdesc,$creator,$add_member_method,$accessright,$logo,$max_number);
        }
        else{
            $add=false;
            $sql = "update im_group set groupname=?,groupdesc=?,logo=?,max_number=? where groupid=?;";
            $para = array((string)$groupname,$groupdesc,$logo,(string)$max_number,(string)$groupid);
        }
        try
        {
            $da_im->ExecSQL($sql,$para);
            $groupinfo = $this->GetByIM($groupid,true);//刷新缓存
            //添加群组成员
            $member = array();
            $member["groupid"] = $groupid;  	 	 
            $member["deptid"] = $parameter["deptid"];
            $member["allow_jid"] = $parameter["allow_jid"];
            $member["user"]= $user;
            $member["groupname"] = $groupname;
            $re = $this->addGroupEmployeeMulti($member);
            if($re['success'])
            {
                //将默认群组人员范围添加到im_group_memberarea表
                $area = array();
                $area["deptid"] = $parameter["deptid"];
                $area["allow_jid"] = $parameter["allow_jid"];
                $this->editGroupMemberAera($groupid,$area); 
            }
            else
            {
                return Utils::WrapResultError($re['msg']);
            }         
        }
        catch(\Exception $e)
        {
            return Utils::WrapResultError($e->getMessage());
        }
        return Utils::WrapResultOK($groupid);
    }
  
    //添加或修改默认群组允许人员范围
    private function editGroupMemberAera($groupid,$area)
    {
        $success = true;
        $da_im = $this->conn_im;
        $deptids = $area["deptid"];
        $allow_jid = $area["allow_jid"];
        
        $sqls = array();
        $paras = array();
        
        //添加部门(修改时不做此操作)
        for($i=0;$i< count($deptids);$i++)
        {
            $id = SysSeq::GetSeqNextValue($da_im,"im_group_memberarea","id");
            $deptid = $deptids[$i];
            $sql = "insert into im_group_memberarea(id,groupid,objid,status)values(?,?,?,'1');";
            $para = array((string)$id,$groupid,$deptid); 
            array_push($sqls,$sql);
            array_push($paras,$para);
        }
        
        if(!empty($allow_jid))
        {
	        //添加允许的人员
	        $sql = 'insert into im_group_memberarea(id,groupid,objid,status)values';
	        $values = array();
	        for($j=0;$j< count($allow_jid);$j++)
	        {
	            $id = SysSeq::GetSeqNextValue($da_im,"im_group_memberarea","id");
	            $jid = $allow_jid[$j];
	            $values[] = '(\''.$id.'\',\''.$groupid.'\',\''.$jid.'\',\'2\')';	            
	        }
	        array_push($sqls,$sql.implode(',', $values));
	        array_push($paras,array());
    	}
        
        try
        {
        	$da_im->ExecSQL('delete from im_group_memberarea where groupid=?',array((string)$groupid));
            $da_im->ExecSQLS($sqls,$paras);
        }
        catch(\Exception $e)
        {
            $success = false;
            $this->logger->err($e->getMessage());  	    
        }
        return $success;
    }
  
    //判断某个成员是否在群成员中
    private function GrouMemberExist($groupid,$jid)
    {
        $exists = false;
        $sql = "select 1 from im_groupemployee where employeeid=? and groupid=?;";
        $para = array((string)$jid,(string)$groupid);
        try
        {
            $ds = $this->conn_im->GetData("table",$sql,$para);
            if ( $ds && $ds["table"]["recordcount"]>0)
                $exists = true;
        }
        catch(\Exception $e)
        {
            $exists = true;        
        }
        return $exists;
    }
  
    //添加群组成员(多个)
    public function addGroupEmployeeMulti($parameter)
    {  	
        $da_im = $this->conn_im;
        $sql = "";$para = array();
        $groupid = $parameter["groupid"];        
        $deptid = $parameter["deptid"];  //允许加入的部门
        $allow_jid = $parameter["allow_jid"]; //允许加入的特定人员
        $user = $parameter["user"];
        $user_jid = $user->fafa_jid;
        $eno = $user->eno;
        $groupname = $parameter["groupname"];
        $sql = "select loginname,employeename from im_employee ";
        $condition = "";
        //取部门下的人员
        if ( count($deptid)>0 )
        {
            $deptid = $this->getChildrenDept($eno,$deptid);
            $condition =" where deptid in(";
            for($i=0;$i<count($deptid);$i++)
            {
                $condition = $condition."?,";
                array_push($para,(string)$deptid[$i]);  	 	 	  	 	 	  	   
            }
            $condition = rtrim($condition,",").")";
        }
        //允许人员
        if ( !empty($allow_jid) && count($allow_jid)>0)
        {
            $condition .= empty($condition) ? " where loginname in(":" or loginname in("; 
            for($i=0;$i< count($allow_jid);$i++)
            {
                $condition = $condition."?,";
                array_push($para,(string)$allow_jid[$i]); 
            }
            $condition = rtrim($condition,",").")";
        }
        //总是将创建人员加入人员列表
        $condition .= empty($condition) ? " where loginname=?" : " or loginname=?";
        array_push($para,(string)$user_jid);  	 
        $sql = $sql.$condition;
        $success = true;$msg = "";
        try
        {
            $ds = $da_im->GetData("table",$sql,$para);  
            if ($ds && $ds["table"]["recordcount"]>0)
            {
                $syspara =new \Justsy\BaseBundle\DataAccess\SysParam($this->container);
                $grouplimit = $syspara->GetSysParam('grouplimit','1000');
                if($ds["table"]["recordcount"]>(int)$grouplimit)
                {
                    return array('success'=>false,'msg'=>'人数超过群成员最大限制'.$grouplimit);
                }
            	$sql = "select employeeid from im_groupemployee where groupid=?";
            	$old_ds = $da_im->GetData("members",$sql,array((string)$groupid));
            	$oldMembers=array();
            	foreach ($old_ds["members"]["rows"] as $key => $value) {
            		$oldMembers[] = $value['employeeid'];
            	}
                $grouprole  = "";
                $newJid =array();
                $newNick =array();
                foreach ($ds["table"]["rows"] as $key => $value) {
                	$newJid[] =  $value['loginname'];
                	$newNick[$value['loginname']] = $value['employeename'];
                }

                //获取需要删除的人员。求差集
                $needSubLst = Utils::array_diff_ex($oldMembers,$newJid);
                //获取需要新加的人员
                $needJoinLst = Utils::array_diff_ex($newJid,$oldMembers);
                //获取未变动的人员
                $noticeLst = Utils::array_intersect_ex($newJid,$oldMembers);
                $sqls = array();
                foreach ($needSubLst as $key => $value) {
                	$sqls[] = 'delete from im_groupemployee where employeeid=\''.$value.'\'';
                }
                $intSql='insert into im_groupemployee(employeeid,groupid,grouprole,employeenick)values';
                $values=array();
                foreach ($needJoinLst as $key => $value) {
                	if ( $user_jid == $value )
                        $grouprole = "owner";
                    else
                        $grouprole = "normal";
                	$values[] = '(\''.$value.'\',\''.$groupid.'\',\''.$grouprole.'\',\''.$newNick[$value].'\')';
                }
                if(count($values)>0)
                {
                	$sqls[] = $intSql.implode(',', $values);
            	}
                $sqls[] = 'update im_group set number=(select count(1) from im_groupemployee where groupid=\''.$groupid.'\') where groupid=\''.$groupid.'\'';
                if(count($sqls)>0)
                {
                    $da_im->ExecSQLs($sqls,array());
                }
                $groupinfo = $this->GetByIM($groupid);
                $groupinfo = array(
                    'groupname'=>$groupinfo['groupname'],
                    'groupid'=>$groupinfo['groupid'],
                    'logo'=>$groupinfo['logo'],
                );
                if(count($needSubLst)>0)
                {
                	$iconUrl = $groupinfo['logo'];
                	$noticeinfo = Utils::WrapMessageNoticeinfo('你已退出企业群组 '.$groupinfo['groupname'],$user->nick_name,null,$iconUrl);
                	$msg = Utils::WrapMessage("exit_group",$groupinfo,$noticeinfo);
                	Utils::sendImMessage($user_jid,$needSubLst,"exit_group",json_encode($msg),$this->container,"","",false,Utils::$systemmessage_code);
                }
                if(count($needJoinLst)>0)
                {
                	$iconUrl = $groupinfo['logo'];
                	$noticeinfo = Utils::WrapMessageNoticeinfo('你已受邀加入企业群组 '.$groupinfo['groupname'],$user->nick_name,null,$iconUrl);
                	$msg = Utils::WrapMessage("join_group",$groupinfo,$noticeinfo);
                	//添加成员成功发送消息
                	Utils::sendImMessage($user_jid,$needJoinLst,"join_group",json_encode($msg),$this->container,"","",false,'');
            	}
                if(count($noticeLst)>0)
                {
                    //通知这部分成员需要更新群信息
                    $noticeinfo = array();
                    $msg = Utils::WrapMessage("update_group",$groupinfo,$noticeinfo);
                    Utils::sendImMessage($user_jid,$noticeLst,"update_group",json_encode($msg),$this->container,"","",false,'');
                }
            }
        }
        catch(\Exception $e)
        {
            $success = false;
            $msg = "";
        }
        
        return array("success"=>$success,"msg"=>$msg);
    }
    
    //根据用户部门id判断是否加入默认群组
    public function AddDefaultGroup($parameter)
    {
    	try
    	{
	        $deptid = isset($parameter["deptid"]) ? $parameter["deptid"] : null;
	        if ( empty($deptid)) return false;  	 
	        $da = $this->conn;
	        $da_im = $this->conn_im;
	        $deptMgr = new Dept($da,$da_im,$this->container);
	        $ds = $deptMgr->getinfo($deptid);
	        if ( !empty($ds))
	        {
	            $deptid = $ds["fafa_deptid"];
	            $groupids = $deptMgr->getDeptDefaultGroup($deptid);
	            if ( !empty($groupids) && count($groupids)>0)
	            {
	                $jid = $parameter["jid"];
	                $nick_name = $parameter["nick_name"];
	                $group_parameter = array();
	                for($i=0;$i< count($groupids);$i++)
	                {
	                    $group_parameter["fafa_jid"] = $jid;
	                    $group_parameter["groupid"]  = $groupids[$i]["groupid"];
	                    $group_parameter["grouprole"] = "normal";
	                    $group_parameter["employeenick"] = $nick_name;
	                    $this->AddGroupEmployee($group_parameter);
	                }
	            }
	        }
	        return true;
    	}
    	catch(\Exception $e)
    	{
    		$this->logger->err($e);
    		return false;
    	}
    }

    //根据用户部门id从对应默认群组中移除成员
    public function RemoveDefaultGroupMember($parameter)
    {
    	try
    	{
			$deptid = isset($parameter["deptid"]) ? $parameter["deptid"] : null;
	        if ( empty($deptid)) return false;
	        $da = $this->conn;
	        $da_im = $this->conn_im;
	        $deptMgr = new Dept($da,$da_im,$this->container);
	        $ds = $deptMgr->getinfo($deptid);
	        if ( !empty($ds))
	        {
	        	$deptid = $ds["fafa_deptid"];
	            $groupids = $deptMgr->getDeptDefaultGroup($deptid);
	            if ( !empty($groupids) && count($groupids)>0)
	            {
	            	$jid = $parameter["jid"];
	                $group_parameter = array();
	                for($i=0;$i< count($groupids);$i++)
	                {
	                    $group_parameter["jid"] = $jid;
	                    $group_parameter["groupid"]  = $groupids[$i]["groupid"];
	                    $this->delGroupMember($group_parameter);
	                }
	            }
	        }
	        return true;
    	}
    	catch(\Exception $e)
    	{
    		$this->logger->err($e);
    		return false;
    	}
    }
  
    //取部门下的所有部门
    private function getChildrenDept($eno,$deptid)
    {
        $eno = "v".$eno;
        $result = array();
        $da_im = $this->conn_im;
        for($i=0;$i< count($deptid);$i++)
        {
            $sql = "select deptid from im_base_dept where position(? in path)>0 and position(? in path)>0;";
            $para = array((string)$deptid[$i],(string)$eno);
            try
            {
                $ds = $da_im->GetData("table",$sql,$para);
                if ($ds && $ds["table"]["recordcount"]>0)
                {
                    for($i=0;$i<$ds["table"]["recordcount"];$i++)
                    {
                        array_push($result,$ds["table"]["rows"][$i]["deptid"]);
                    }
                }
            }
            catch(\Exception $e)
            {
                $this->logger->err($e->getMessage());
            }  		  		  		 
        }
        return $result;
    }
  
    //添加群组成员(单个群组成员)
    private function AddGroupEmployee($parameter)
    {
        $success = true;
        $da_im = $this->conn_im;
        $fafa_jid = $parameter["fafa_jid"];
        $groupid = $parameter["groupid"];
        $grouprole = $parameter["grouprole"];
        $employeenick = $parameter["employeenick"];
        $sqls = array();$paras = array();
        //添加成员
        array_push($sqls,"insert into im_groupemployee(employeeid,groupid,grouprole,employeenick )values(?,?,?,?);");  	
        array_push($paras,array($fafa_jid,$groupid,$grouprole,$employeenick)); 	
        //同时使成员数量字段加1
        array_push($sqls,"update im_group set number=ifnull(number,0)+1 where groupid=?;");
        array_push($paras,array((string)$groupid));
        try
        {
            $da_im->ExecSQLs($sqls,$paras); 
            $groupinfo = $this->GetByIM($groupid);
            $iconUrl = $groupinfo['logo'];
            $noticeinfo = Utils::WrapMessageNoticeinfo('你已加入企业群组 '.$groupinfo['groupname'],'管理员',null,$iconUrl);
            $msg = Utils::WrapMessage("join_group",$groupinfo,$noticeinfo);
            //添加成员成功发送消息
            Utils::sendImMessage('',$fafa_jid,"join_group",json_encode($msg),$this->container,"","",false,'');
        }
        catch(\Exception $e)
        {
            $this->logger->err($e->getMessage());
            $success = false;
        }
        return $success;
    }

    //删除群成员
    public function delGroupMember($parameter)
    {
        $groupid = $parameter["groupid"];
        $jid = $parameter["jid"];
        // $staff = $parameter["member"];
        $staffMgr = new Staff($this->conn,$this->conn_im,$jid,$this->logger,$this->container);
        $staffdata = $staffMgr->getinfo();
        if(empty($staffdata))
        {
            return Utils::WrapResultError('成员不存在');
        }
        $staffdata = array(
            'jid'=>$staffdata['jid'],
            'nick_name'=>$staffdata['nick_name'],
            'photo_path'=>$staffdata['photo_path'],
        );
        $success = true;
        $da = $this->conn_im;
       
        $sql = 'select ge.grouprole from im_groupemployee ge where ge.groupid=? and ge.employeeid=? ';
        $result = $da->GetData('t',$sql,array((string)$groupid,(string)$jid));
        
        if($result['t']['rows'][0]['grouprole']=='owner'){
            //创建者不能删除            
            return Utils::WrapResultOK('false','不能删除创建者！');
        }else if ($result['t']['rows'][0]['number']<=3) {        	
            //群成员小于3人，自动解散群
			$this->delDefaultGroup(array('groupid'=>$groupid));
            return Utils::WrapResultOK("dissolve","成员小于3人，已自动解散该群！");
        }

        $sql = "delete from im_groupemployee where employeeid=? and groupid=?;";
        $para = array((string)$jid,(string)$groupid);
        
        try
        {
            $da->ExecSQL($sql,$para);
            //同时重新计算群成员数量
            $sql = "update im_group set number = number-1 where groupid=?;";
            $para = array((string)$groupid,(string)$groupid);
           
            try
            {

                $da->ExecSQL($sql,$para);

                $groupinfo = $this->GetByIM($groupid,true);
                //向群组所有成员发送出席（包括当前被删除的群成员）
                $groupObj = $this->getGroupMemberJid($groupid);
                $to_jid = $groupObj["member_jid"];
                $groupname = $groupObj["groupname"];
                if ( !empty($to_jid))
                {
                    //由于当前被删除群成员已经不在表中，所以应加上
                    $to_jid .=",".$jid;
                    $userinfo = $parameter["user"];
                    $send_jid = $userinfo->fafa_jid;

                    $title = "exit_group";
                    $message = $staffdata['nick_name'].'退出了 '.$groupname.' 群';
                    $noticeinfo = Utils::WrapMessageNoticeinfo($message,$userinfo->nick_name,null,$groupinfo['logo']);
                    $msg = Utils::WrapMessage($title,array('groupid'=>$groupid,'groupname'=>$groupinfo['groupname'],'member'=>$staffdata),$noticeinfo);
                    Utils::sendImMessage($send_jid,$to_jid,$title,json_encode($msg),$this->container,"","",false,Utils::$systemmessage_code);                    
                }
            }
            catch(\Exception $e)
            {
                $this->logger->err($e->getMessage());
            }
        }
        catch(\Exception $e)
        {
            $success = false;
            $this->logger->err($e->getMessage());
        }
        return Utils::WrapResultOK('');
    }
  
    //获得企业参数
    public function getEnoParameter($para_name)
    {
        $result = null;
        $sql = "select minval,maxval,defaultvalue from wa_eno_params where param_name=?";
        $da = $this->conn;
        $ds = $da->GetData("table",$sql,array((string)$para_name));
        if ( $ds && $ds["table"]["recordcount"]>0)
            $result = $ds["table"]["rows"][0];
        return $result;
    }

    //	获取群组总数
    public function count($parameter)
    {
        $userinfo = $parameter["user"];
        $eno = $userinfo->eno;
        $length = strlen($eno);	  
        $login_account = $userinfo->getUserName();
        $fafa_jid = $userinfo->fafa_jid;
        $url = $this->container->getParameter('FILE_WEBSERVER_URL');
        $groupname = $parameter["groupname"];
        $groupname = str_replace("%","\%",$groupname);
        $groupname = str_replace("_","\_",$groupname);
        $groupname = str_replace("[","\[",$groupname);
        $groupname = str_replace("]","\]",$groupname);
        $groupclass = isset($parameter["groupclass"]) ? $parameter["groupclass"] : "defaultgroup";	
        $da_im = $this->container->get("we_data_access_im");
        $sql = "select count(1) cnt from im_group a where substring(a.creator,position('-' in a.creator)+1,?)=? ";
        $para = array((string)$length,(string)$eno);
        $condition = "";
        $success = true;$msg="";$returndata = array();$recordcount=0;
        if ( !empty($groupname))
        {
            $condition = " and groupname like concat('%',?,'%') ";
            array_push($para,(string)$groupname);
        }
        $sql .= $condition;
        try
        {
            $ds = $da_im->GetData("table",$sql,$para);  
            return Utils::WrapResultOK($ds['table']['rows'][0]['cnt']);          
        }
        catch(\Exception $e){
            $this->logger->err($e->getMessage());
            return Utils::WrapResultError($e->getMessage());
        }        
    }
  
    //查询默认群组
    public function searchDefaultGroup($parameter)
    {
        $userinfo = $parameter["user"];
        $eno = $userinfo->eno;
        $length = strlen($eno);	  
        $login_account = $userinfo->getUserName();
        $fafa_jid = $userinfo->fafa_jid;
        $url = $this->container->getParameter('FILE_WEBSERVER_URL');
        $domain = $this->container->getParameter('edomain');
        $groupname = $parameter["groupname"];
        $groupname = str_replace("%","\%",$groupname);
        $groupname = str_replace("_","\_",$groupname);
        $groupname = str_replace("[","\[",$groupname);
        $groupname = str_replace("]","\]",$groupname);
        $groupclass = isset($parameter["groupclass"]) ? $parameter["groupclass"] : "defaultgroup";	
        $pageindex = isset($parameter["pageindex"]) ? $parameter["pageindex"] : 1;
        $pageindex = (int)$pageindex==0 ? 1 : $pageindex;
        $record = isset($parameter["record"]) ? $parameter["record"] : 8;
        $limit = " limit ".(($pageindex - 1) * $record).",".$record;
        $da_im = $this->container->get("we_data_access_im");
        $sql = "select groupid,groupname,employeename creator,ifnull(number,0) number,ifnull(max_number,0) max_number,case when ifnull(logo,'')='' then concat('$url','') else concat('$url',logo) end as logo,
                '' role,case when ifnull(last_date,'')='' then '　' else date_format(last_date,'%m-%d %H:%i') end last_date,ifnull(manager,'') as manager,
                    case when groupclass='defaultgroup' then 1 else 0 end group_type 
                from im_group a inner join im_employee b on a.creator=b.loginname where a.creator like concat('%',?,'@{$domain}') or a.eno=?";
        $para = array((string)$eno,(string)$eno);
        $condition = "";
        $success = true;$msg="";$returndata = array();$recordcount=0;
        if ( !empty($groupname))
        {
            $condition = " and groupname like concat('%',?,'%') ";
            array_push($para,(string)$groupname);
        }
        $sql .= $condition." order by createdate desc ".$limit;
        try
        {
            $ds = $da_im->GetData("table",$sql,$para);
            if ( $ds && $ds["table"]["recordcount"]>0)
            {
                $sys_manager = $this->isManager($eno,$login_account);
                $returndata = $ds["table"]["rows"];
                for($i=0;$i<count($returndata);$i++)
                {
                    $groupid = $returndata[$i]["groupid"];
                    $staffs = $this->getManager($groupid);
                    $member = $staffs["member"]; 
                    $memberid = $staffs["memberid"];
                    if ( empty($sys_manager) )  //当前用户为非系统管理员
                    {
                        if ( count($memberid)>0 && in_array($fafa_jid,$memberid))
                            $returndata[$i]["role"] = 'manager';
                    }
                    else
                    {
                        $returndata[$i]["role"] = $sys_manager;
                    }
                    $returndata[$i]["manager"]=$member;
                }
            }
            return Utils::WrapResultOK($returndata);
        }
        catch(\Exception $e){
            $this->logger->err($e->getMessage());
            return Utils::WrapResultError($e->getMessage());
        }        
    }
  
  //获得管理员
  private function getManager($groupid)
  {
  	$memberid = array();
  	$member = array();
  	$da_im = $this->conn_im;
  	$sql = "select employeeid,employeenick from im_groupemployee where grouprole='manager' and groupid=?";
  	$para = array((string)$groupid);  	
  	try
  	{ 
  		 $ds = $da_im->GetData("table",$sql,$para);
  		 if ( $ds && $ds["table"]["recordcount"]>0)
  		 {
  		 	 for($j=0;$j<$ds["table"]["recordcount"];$j++)
  		 	 {
  		 	 	  array_push($memberid,$ds["table"]["rows"][$j]["employeeid"]);
  		 	 	  array_push($member,  $ds["table"]["rows"][$j]["employeenick"]);
  		 	 }
  		 }
  	}
  	catch(\Exception $e){
  		$this->logger->err($e->getMessage());
  	}
  	return array("memberid"=>$memberid,"member"=>$member);
  }
  
  //当前用户是否系统管理员
  public function isManager($eno,$login_account)
  {
  	$da = $this->conn;
  	$manager = "";
  	$sql = "select case when position(? in concat(sys_manager,';',create_staff))>0 then 'sys_manager' else '' end manager from we_enterprise where eno=?;";
  	$para = array((string)$login_account,(string)$eno);
  	try
  	{
    	$ds = $da->GetData("table",$sql,$para);
    	if ( $ds && $ds["table"]["recordcount"]>0)
    	  $manager = $ds["table"]["rows"][0]["manager"];
  	}
  	catch(\Exception $e)
  	{
  		$this->get("logger")->err($e->getMessage());
  	}
  	return $manager;
  }
  
  //删除默认群组
  public function delDefaultGroup($parameter)
  {
  	 $groupid = $parameter["groupid"];
  	 $da_im = $this->conn_im;
  	 $sqls = array();$paras = array();
  	 $to_jid="";
  	 //删除群组
  	 $sql = "select logo from im_group where groupid=?";
  	 try
  	 {
  	    $ds  = $da_im->GetData("table",$sql,array((string)$groupid));
  	    if ( $ds && $ds["table"]["recordcount"]>0)
  	    {
  	    	$fileid = $ds["table"]["rows"][0]["logo"];
  	    	if (!empty($fileid))
  	    	{
  	    		$this->removeFile($fileid);
  	    	}
  	    }
  	 }
  	 catch(\Exception $e)
  	 {
  	 	  $this->logger->err($e->getMessage());
  	 }  	 
  	 $sql = "delete from im_group where groupid=?";
  	 array_push($sqls,$sql);
  	 array_push($paras,array((string)$groupid));
  	 //删除群成员(删除群成员表时取群用户jid以发送出席)	
  	 $groupObj = $this->getGroupMemberJid($groupid);
     $to_jid = $groupObj["member_jid"];
     $groupname = $groupObj["groupname"];        	 
  	 $sql = "delete from im_groupemployee where groupid=?;";
  	 array_push($sqls,$sql);
  	 array_push($paras,array((string)$groupid));
  	 //删除群组对应允许加入的部门表
  	 $sql = "delete from im_group_memberarea where groupid=?;";
  	 array_push($sqls,$sql);
  	 array_push($paras,array((string)$groupid));

  	 $sql = "delete from im_group_msg where groupid=?;";
  	 array_push($sqls,$sql);
  	 array_push($paras,array((string)$groupid));
  	 /*
  	 $sql = "delete from im_groupemployee_covert where groupid=?;";
  	 array_push($sqls,$sql);
  	 array_push($paras,array((string)$groupid));*/
  	 $success = true;$msg="";
  	 try
  	 {
      	 	$da_im->ExecSQLs($sqls,$paras);
      	 	//解散群组成功后发送出席
      	 	$userinfo = $parameter["user"];
    	    $send_jid = $userinfo->fafa_jid;
    	    $title = "remove_group";
    	    $message = $userinfo->nick_name;
    	    $message = '管理员解散群组了 '.$groupname;

            $noticeinfo = Utils::WrapMessageNoticeinfo($message,$userinfo->nick_name,null,null);
            $msg = Utils::WrapMessage($title,array('groupid'=>$groupid),$noticeinfo);
            Utils::sendImMessage($send_jid,$to_jid,$title,json_encode($msg),$this->container,"","",false,Utils::$systemmessage_code);
  	 }
  	 catch(\Exception $e)
  	 {
  	 	  $this->logger->err($e->getMessage());
  	 	  Utils::WrapResultError($e->getMessage());
  	 }  	 
  	 return Utils::WrapResultOK("");	 
  }
  
  //删除文件
	public function removeFile($fileid)
	{
	    if (!empty($fileid))
	    {
	    	$dm = $this->container->get('doctrine.odm.mongodb.document_manager');
	        $doc = $dm->getRepository('JustsyMongoDocBundle:WeDocument')->find($fileid);
	        if(!empty($doc))
	            $dm->remove($doc);
	        $dm->flush();
	    }
	    return true;
	}
		
	//获得默认群组信息
	public function getGroupInfo($parameter)
	{
		 $groupid = $parameter["groupid"];
		 $success = true;$msg = "";
		 $basic = array();
		 $member_area = array();
		 $da = $this->conn_im;
		 $url = $this->container->getParameter('FILE_WEBSERVER_URL');
		 //取默认群基本信息
		 $sql = "select groupid,groupname,groupdesc,case when ifnull(logo,'')='' then concat('$url','') else concat('$url',logo) end url,logo,max_number from im_group where groupid=?";
		 try
		 {
		 	  $ds = $da->GetData("table",$sql,array((string)$groupid));
		 	  if ( $ds && $ds["table"]["recordcount"]>0)
		 	  {
		 	  	$basic["groupid"] = $ds["table"]["rows"][0]["groupid"];
		 	    $basic["groupname"] = $ds["table"]["rows"][0]["groupname"];
		 	    $basic["groupdesc"] = $ds["table"]["rows"][0]["groupdesc"];
		 	    $basic["max_number"] = $ds["table"]["rows"][0]["max_number"];
		 	    $basic["url"] = $ds["table"]["rows"][0]["url"];
		 	    $basic["logo"] = $ds["table"]["rows"][0]["logo"];
		 	  }
		 	  //获得默认群组允许或排除条件
				$sql = "select objid,deptname as objname,status from im_group_memberarea a inner join im_base_dept b on a.objid=b.deptid where status=1 and groupid=? ";
				$sql.= " union select loginname,employeename,status from im_group_memberarea a inner join im_employee b on a.objid=b.loginname where status>1 and groupid=? order by status asc;";
        		$para = array((string)$groupid,(string)$groupid);
				try
				{
				  $ds = $da->GetData("table",$sql,$para);
				  if ( $ds && $ds["table"]["recordcount"]>0)
				    $member_area = $ds["table"]["rows"];
				}
				catch(\Exception $e)
				{
					 $this->logger->err($e->getMessage());
				}
		 }
		 catch(\Exception $e)
		 {
		 	  $this->logger->err($e->getMessage());
		 	  return Utils::WrapResultError($e->getMessage());
		 }
		 return Utils::WrapResultOK(array("basic"=>$basic,"member_area"=>$member_area));
	}
	
	//获得默认组群成员
	public function getGroupMember($parameter)
	{
		$groupid  = $parameter["groupid"];
		$nick_name = isset($parameter["nick_name"]) ? $parameter["nick_name"] : null; 	
		$success = true;
		$list = array();
		$da = $this->conn_im;
		$sql = "select a.employeeid fafa_jid,a.employeenick nick_name,ifnull((select deptname from im_base_dept dept where dept.deptid=b.deptid),'') deptname,case a.grouprole when 'owner' then 2 when 'manager' then 1 else 0 end role 
            from im_groupemployee a inner join im_employee b on a.employeeid=b.loginname where 1=1 ";
	    $para = array();$condition = "";        
	    if ( !empty($nick_name))
	    {
	        $condition = " and a.employeenick like concat('%',?,'%') ";
	        array_push($para,(string)$nick_name);
	    }
	    $condition .= " and groupid=? ";
	    array_push($para,(string)$groupid); 
	    $sql .= $condition." order by role desc ";
	    $recordcount = 0;
		try
		{
			$ds = $da->GetData("table",$sql,$para);
			$list = $ds["table"]["rows"];	    
		}
		catch(\Exception $e)
		{
		  	$this->logger->err($e->getMessage());
		  	return Utils::WrapResultError($e->getMessage());
		}
		return Utils::WrapResultOK($list);
	}
	
	//设置或取消管理员
	public function setGroupMember($parameter)
	{
		$groupid = $parameter["groupid"];
		$jid = $parameter["jid"];
		$member = $parameter["member"];
		$role = $parameter["role"];
		if ( $role =="1")
		  $role = "normal";
		else
		  $role = "manager";		   
		$success = true;
		$da = $this->conn_im;
		$sql = "update im_groupemployee set grouprole=? where employeeid=? and groupid=?;";
		$para = array((string)$role,(string)$jid,(string)$groupid);
		try
		{
			$da->ExecSQL($sql,$para);
			//只向当前人员发送消息
  	  $userinfo = $parameter["user"];
      $send_jid = $userinfo->fafa_jid;
      $nick_name = $userinfo->nick_name;
      //获得群名称
      $groupname = "";
      $sql = "select groupname from im_group where groupid=?";
      $ds = $da->GetData("table",$sql,array((string)$groupid));
      if ( $ds && $ds["table"]["recordcount"]>0)
        $groupname = $ds["table"]["rows"][0]["groupname"];      
      if ( $role == "manager")
      {
        $title = "set_manager";
        $message = $nick_name." 设置了您的群(".$groupname.")管理员权限！";
      }
      else
      {
        $title = "cancel_manager";
        $message = $nick_name." 取消了您的群(".$groupname.")管理员权限！";
      }
      Utils::sendImPresence($send_jid,$jid,$title,$message,$this->container,"","",true,'','0');
	 	  
	  }
	  catch(\Exception $e)
	  {
	  	$success = false;
	  	$this->logger->err($e->getMessage());
	  	return Utils::WrapResultError($e->getMessage());
	  }
		return Utils::WrapResultOK('');
	}
	
	public function setMaxNumber($parameter)
	{
		$groupid = $parameter["groupid"];
		$max_number = $parameter["max_number"];
		$success = true;
		$da = $this->conn_im;
		$sql = "update im_group set max_number =? where groupid=?;";
		$para = array((string)$max_number,(string)$groupid);
		try
		{
			$da->ExecSQL($sql,$para);
			//向群组所有成员发送出席
			$groupObj = $this->getGroupMemberJid($groupid);
	      $to_jid = $groupObj["member_jid"];
	      $groupname = $groupObj["groupname"];       
	      if ( !empty($to_jid))
	      {
	      	$userinfo = $parameter["user"];
	        $send_jid = $userinfo->fafa_jid;
	        $title = "set_maxnumber";
	        $nick_name = $userinfo->nick_name;
	        $message = $nick_name.'调整'.$groupname.'群为'.$max_number.'人群';
	        Utils::sendImPresence($send_jid,$to_jid,$title,$message,$this->container,"","",true,'','0');
	 	  }
	  }
	  catch(\Exception $e)
	  {
	  	$success = false;
	  	$this->logger->err($e->getMessage());
	  	return Utils::WrapResultError($e->getMessage());
	  }
		return Utils::WrapResultOK('');
	}
	
	//获得群组成员(只取jid用于发送出席或消息)
	public function getGroupMemberJid($groupid)
	{
		$da = $this->conn_im;
		$member_jid = null;$groupname = "";
		$sql = "select employeeid as jid from im_groupemployee where groupid=?";
	  	try
	  	{
	  	 	 $ds = $da->GetData("table",$sql,array((string)$groupid));
	  	 	 if ($ds && $ds["table"]["recordcount"]>0)
	  	 	 {
	  	 	 	  $jids = array();
	  	 	 	  for($i=0;$i<$ds["table"]["recordcount"];$i++)
	  	 	 	  {
	  	 	 	     array_push($jids,$ds["table"]["rows"][$i]["jid"]);
	  	 	 	  }
	  	 	 	  $member_jid = implode(",",$jids);
	  	 	 }
	  	}
	  	catch(\Exception $e)
	  	{
	  	 	  $this->logger->err($e->getMessage());
	  	}
	  	//获得群组名称
	  	$sql = "select groupname from im_group where groupid=?;";
	  	try
	  	{
	  		$ds = $da->GetData("table",$sql,array((string)$groupid));
	  		if ( $ds && $ds["table"]["recordcount"]>0)
	  		  $groupname = $ds["table"]["rows"][0]["groupname"];
	  	}
	  	catch(\Exception $e)
	  	{
	  		$this->logger->err($e->getMessage());  		
	  	}
	  	return array("member_jid"=>$member_jid,"groupname"=>$groupname);
	}
	
  //检查群组名称是否已经存在
  public function checkGroupName($parameter)
  {
	  	$da = $this->conn_im;
	  	$groupname = $parameter["groupname"];
	  	$groupid   = $parameter["groupid"];
	  	$sql = "";$para = array();
	  	if (empty($groupid))
	  	{
	  		$sql = "select 1 from im_group where groupname=?";
	  		$para = array((string)$groupname);
	  	}
	  	else{
	  		$sql = "select 1 from im_group where groupname=? and groupid!=?";
	  		$para = array((string)$groupname,(string)$groupid);
	  	}
	  	$success = true;$exists = false;
	  	try
	  	{
	  		 $ds = $da->GetData("table",$sql,$para);
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
  
  //设置或取消群屏蔽消息表
  public function setCovert($parameter)
  {
     $groupid = isset($parameter["groupid"]) ? $parameter["groupid"] : null;
     $jid     = isset($parameter["jid"]) ? $parameter["jid"] : null;
     $state   = isset($parameter["state"]) ? $parameter["state"] : "1";  //默认为添加
     $success=true;$msg="";
     if ( empty($groupid))
     {
        return Utils::WrapResultError('群编号不能为空');     
     }
     else if ( empty($jid))
     {
        return Utils::WrapResultError('设置的成员帐号不能为空');
     }
     $sql="";$para=array();
     if ( $state=="1")
        $sql ="insert into im_groupemployee_covert(groupid,jid)values(?,?);";
     else
        $sql = "delete from im_groupemployee_covert where groupid=? and jid=?;";
     try
     {
        $this->conn_im->ExecSQL($sql,array((string)$groupid,(string)$jid));
        //向成员发送出席
        $user = $parameter["user"];
        $title = $state=="1" ? "set_covert":"cancel_covert";
        $message = $state=="1" ? "设置了您的屏蔽群消息":"取消了您的屏蔽群消息";
        Utils::sendImPresence($user->fafa_jid,$jid,$title,$message,$this->container,"","",true,'','0');
     }
     catch(\Exception $e)
     {
        $success = false;
        $msg = $state == "1" ? "设置屏蔽成员失败！":"取消屏蔽成员失败！";
        return Utils::WrapResultError($e->getMessage());
     }
     return Utils::WrapResultOK('1'); 
  }
}
