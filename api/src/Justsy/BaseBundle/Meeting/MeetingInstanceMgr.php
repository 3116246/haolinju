<?php

namespace Justsy\BaseBundle\Management;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\DataAccess\SysSeq;
//会议实例管理
class MeetingInstanceMgr
{
	  private $conn=null;
	  private $conn_im=null;
	  
	  public function __construct($_db,$_db_im)
	  {
	    $this->conn = $_db;
	    $this->conn_im = $_db_im;	    
	  }
	  
	  public function RegisterPublish($enoName,$circleId,$circleName,$publishAccount,$nickName)
	  {
	  	 $sqls=array();
	  	 $paras=array();
	  	 
	  	 $trends = $this->GetAll();
	  	 for($i=0; $i<count($trends);$i++)
	  	 {
	  	    $convId = SysSeq::GetSeqNextValue($da,"we_convers_list","conv_id");
		      $sqls[] = "insert into we_convers_list (conv_id, login_account, post_date, conv_type_id, conv_root_id, 
		        conv_content, post_to_group, post_to_circle, copy_num, reply_num) 
		        values (?, ?, now(), '00', ?, ?, ?, ?, 0, 0)";
		      $paras[] = array(
		        (string)$convId,
		        'sysadmin@fafatime.com',
		        (string)$convId,
		        (string)$trends[$i]["content"],
		        (string)'ALL',
		        (string)$circleId);
	  	 }
	  	 $firstTrend = "【企业圈子创建成功】<br>企业名称：".$enoName."<br>圈子名称：".$circleName."<br>创建者/管理员：".$nickName."(".$publishAccount.")";
	  	 //插入动态
      $convId = SysSeq::GetSeqNextValue($da,"we_convers_list","conv_id");
      $sqls[] = "insert into we_convers_list (conv_id, login_account, post_date, conv_type_id, conv_root_id, 
        conv_content, post_to_group, post_to_circle, copy_num, reply_num) 
        values (?, ?, now(), '00', ?, ?, ?, ?, 0, 0)";
      $paras[] = array(
        (string)$convId,
        (string)$publishAccount,
        (string)$convId,
        (string)$firstTrend,
        (string)'ALL',
        (string)$circleId);
        return $this->conn->ExecSQLs($sqls,$paras);
	  }
	  
	  public function Get($conv_id)
	  {
	      	$sql = "select * from we_official_publish where info_id=? and info_type=?";
	      	$ds = $this->conn->GetData("result",$sql,array((string)$conv_id,"static"));
	      	return $ds["result"]["rows"];
	  }
	  
	  public function GetAll()
	  {
	      	$sql = "select * from we_official_publish where  info_type=? order by info_id desc";
	      	$ds = $this->conn->GetData("result",$sql,array("static"));
	      	return $ds["result"]["rows"];	      	
	  }
	  
	  public  function Add($content)
	  {
	  	  $convId = 'ws'.SysSeq::GetSeqNextValue($da,"we_official_publish","info_id");
	      $sqls = "insert into we_official_publish (info_id, info_type,content) 
	        values (?, 'static', ?)";
	      $paras = array(
	        (string)$convId,
          (string)$content);
        return $this->conn->ExecSQL($sqls,$paras);
	  }
	  
	  public  function Update($id,$content)
	  {
	      $sqls = "update we_official_publish set content=? where info_id=? and info_type='static'";
	      $paras = array(
	        (string)$content,
          (string)$id);
        return $this->conn->ExecSQL($sqls,$paras);
	  }	  
	  
	  public  function Del($id)
	  {
	      $sqls = "delete we_official_publish where info_id=? and info_type='static'";
	      $paras = array((string)$id);
        return $this->conn->ExecSQL($sqls,$paras);
	  }	  
	  
}
