<?php

namespace Justsy\BaseBundle\Management;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\DataAccess\SysSeq;
//固定的常用动态维护
//常用的官方动态通过系统代码表进行维护
//
class StaticTrendMgr
{
	  private $conn=null;
	  private $conn_im=null;
	  
	  public function __construct($db,$db_im)
	  {
	    $this->conn = $db;
	    $this->conn_im = $db_im;	    
	  }
	  //获取人员激活后，显示在人脉圈子中的系统动态
	  public function GetStaticRelationTrend()
	  {
	      	$sql = "select * from we_official_publish where  info_type=? order by info_id desc";
	      	$ds = $this->conn->GetData("result",$sql,array("relationship"));
	      	return $ds["result"]["rows"];
	  }
	  
	  ///企业注册成功时，默认在企业圈子中发布的动态
	  public function RegisterPublish($enoName,$circleId,$circleName,$publishAccount,$nickName)
	  {
	  	 $sqls=array();
	  	 $paras=array();
	  	 
	  	 $trends = $this->GetAll();
	  	 for($i=0; $i<count($trends);$i++)
	  	 {
	  	    $convId = SysSeq::GetSeqNextValue($this->conn,"we_convers_list","conv_id");
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
	  	 $firstTrend = "【企业圈子创建成功】
	  	 企业名称：".$enoName."
	  	 圈子名称：".$circleName."
	  	 创 建 者：".$nickName;
	  	 //插入动态
      $convId = SysSeq::GetSeqNextValue($this->conn,"we_convers_list","conv_id");
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
	  	  $convId = 'ws'.SysSeq::GetSeqNextValue($this->conn,"we_official_publish","info_id");
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
