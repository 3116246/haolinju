<?php

namespace Justsy\BaseBundle\Management;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\DataAccess\SysSeq;
//申请加入圈子、群组
class ApplyMgr
{
	  private $conn=null;
	  private $conn_im=null;
	  private $circleapplylimit = 50;
	  private $groupapplylimit = 50;
	  public function __construct($_db,$_db_im)
	  {
	    $this->conn = $_db;
	    $this->conn_im = $_db_im;	    
	  }
	  
	  public function GetCircleApplyLimit()
	  {
	  		return $this->circleapplylimit;	
	  }
	  
	  public function GetGroupApplyLimit()
	  {
	  		return $this->groupapplylimit;	
	  }	  
	  
	  //返回指定申请人的有效申请
	  public function GetCircleApply($account)
	  {
	      	$sql = "select * from we_apply where account=? and recv_type='c' and is_valid='1'";
	      	$ds = $this->conn->GetData("result",$sql,array((string)$account));
	      	return $ds["result"]["rows"];
	  }
	  
	  //返回指定申请人的有效申请
	  public function GetGroupApply($account)
	  {
	      	$sql = "select * from we_apply where account=? and recv_type='g' and is_valid='1'";
	      	$ds = $this->conn->GetData("result",$sql,array((string)$account));
	      	return $ds["result"]["rows"];
	  }	  
	  
	  public  function ApplyJoinCircle($account,$circleid,$remark)
	  {
	  	  //判断是否已申请
	  	  $isapply = $this->GetCircleApplyValid($account,$circleid);
	  	  if($isapply===false){
	  	     return 0;	
	  	  }	  	
	  	  //判断已申请的圈子总数是否超过了circleapplylimit设置
	  	  $c = $this->GetCircleApply($account);
	  	  if(count($c)>= $this->circleapplylimit)
	  	  {
	  	  	  return 99999;	
	  	  }
	  	  $Id = SysSeq::GetSeqNextValue($this->conn,"we_apply","id");
	      $sqls = "insert into we_apply (id, account,recv_type,recv_id,content,is_valid,apply_date) 
	        values (?, ?, 'c',?,?,'1',now())";
	      $paras = array(
	        (string)$Id,
	        (string)$account,
	        (string)$circleid,
          (string)$remark);
        return $this->conn->ExecSQL($sqls,$paras);
	  }
	  public  function ApplyJoinGroup($account,$groupid,$remark)
	  {
	  	  //判断是否已申请
	  	  $isapply = $this->GetGroupApplyValid($account,$groupid);
	  	  if($isapply===false){
	  	     return 0;	
	  	  }
	  	  //判断已申请的圈子总数是否超过了groupapplylimit设置
	  	  $c = $this->GetGroupApply($account);
	  	  if(count($c)>= $this->groupapplylimit)
	  	  {
	  	  	  return 99999;	
	  	  }	  	  
	  	  $Id = SysSeq::GetSeqNextValue($this->conn,"we_apply","id");
	      $sqls = "insert into we_apply (id, account,recv_type,recv_id,content,is_valid,apply_date) 
	        values (?, ?, 'g',?,?,'1',now())";
	      $paras = array(
	        (string)$Id,
	        (string)$account,
	        (string)$groupid,
          (string)$remark);
        return $this->conn->ExecSQL($sqls,$paras);
	  }	  
	  //设置申请无效
	  public  function SetCircleApplyInvalid($id,$recv_id)
	  {
	  	  if(empty($recv_id)){
	         $sqls = "delete from we_apply where id=? ";
	         $paras = array((string)$id);
	      }
	      else
	      {
	         $sqls = "delete from we_apply where account=? and recv_type='c' and recv_id=? ";
	         $paras = array((string)$id,(string)$recv_id);	      	
	      }
        return $this->conn->ExecSQL($sqls,$paras);
	  }
	  public  function SetGroupApplyInvalid($id,$recv_id)
	  {
	  	  if(empty($recv_id)){
	         $sqls = "delete from we_apply where id=? ";
	         $paras = array((string)$id);
	      }
	      else
	      {
	         $sqls = "delete from we_apply where account=? and recv_type='g' and recv_id=? ";
	         $paras = array((string)$id,(string)$recv_id);	      	
	      }
        return $this->conn->ExecSQL($sqls,$paras);
	  }	  
	  ///判断指定申请是否有效
	  //当指定了recv_id时。id则对应申请人帐号
	  public function GetCircleApplyValid($id,$recv_id=null)
	  {
	  	  if($recv_id!=null)
	  	  {
	  	      $sql = "select is_valid from we_apply where account=? and recv_id=? and recv_type='c'";
	  	      $paras = array((string)$id,(string)$recv_id);
	  	  }
	  	  else
	  	  {
	  	      $sql = "select is_valid from we_apply where id=?";
	  	      $paras = array((string)$id);
	  	  }
	  	  $ds= $this->conn->GetData("r",$sql,$paras);
	  	  if($ds && count($ds["r"]["rows"])==0) return true;
	  	  return false;
	  }
	  public function GetGroupApplyValid($id,$recv_id=null)
	  {
	  	  if($recv_id!=null)
	  	  {
	  	      $sql = "select is_valid from we_apply where account=? and recv_id=? and recv_type='g'";
	  	      $paras = array((string)$id,(string)$recv_id);
	  	  }
	  	  else
	  	  {
	  	      $sql = "select is_valid from we_apply where id=?";
	  	      $paras = array((string)$id);
	  	  }
	  	  $ds= $this->conn->GetData("r",$sql,$paras);
	  	  if($ds && count($ds["r"]["rows"])==0) return true;
	  	  return false;
	  }	  
	  //删除申请
	  public  function Del($id)
	  {
	      $sqls = "delete we_apply where id=?";
	      $paras = array((string)$id);
        return $this->conn->ExecSQL($sqls,$paras);
	  }
	  //申请是否还存在
	  public function IsExist($id)
	  {
	  	  $sql = "select count(1) from we_apply where id=?";
	  	  $paras = array((string)$id);
	  	  $ds= $this->conn->GetData("r",$sql,$paras);
	  	  if($ds && count($ds["r"]["rows"])) return true;
	  	  return false;
	  }
	  
}
