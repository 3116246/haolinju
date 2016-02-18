<?php

namespace Justsy\BaseBundle\Management;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Management\Identify;
class StaffAccountBind
{
	  private $conn=null;
	  private $conn_im=null;
	  
	  public function __construct($_db,$_db_im,$_logger=null)
	  {
	    $this->conn = $_db;
	    $this->conn_im = $_db_im; 
	    $this->logger = $_logger;	    
	  }
	  //判断是否绑定指定类型的帐号
	  public function GetBind($type,$acc,$more_para=null)
	  {
	     //we_staff_account_bind
	     $sql = "select * from we_staff_account_bind where bind_type=? and bind_account=?";
	     $ds = $this->conn->GetData("data",$sql,array((string)$type),(string)$acc);
	     if($ds && count($ds["data"]["rows"])>0)
	     {
	     	   return $ds["data"]["rows"][0]["login_account"];
	     }
	     else if(!empty($more_para))
	     {
	     	   //写入帐号信息，但不绑定	
	     	   if($type=="weibo_sina") $this->insertInfoBySinaWeibo($type,"",$acc,$more_para);
	     }
	     return null;
	  }
	  
	  public function GetBind_By_Uid($type,$uid,$more_para=null)
	  {
	  	 if(empty($uid)) throw new \Exception("uid不能为空");
	     $sql = "select * from we_staff_account_bind where bind_type=? and bind_uid=?";
	     $ds = $this->conn->GetData("data",$sql,array((string)$type,(string)$uid));
	     if($ds && count($ds["data"]["rows"])>0)
	     {
	     	   return $ds["data"]["rows"][0]["login_account"];
	     }
	     else if(!empty($more_para))
	     {
	         //写入帐号信息，但不绑定	
	         if($type=="weibo_sina") $this->insertInfoBySinaWeibo($type,$uid,"",$more_para);
	     }
	     return null;
	  }
	  
	  private function insertInfoBySinaWeibo($type,$uid,$acc,$para)
	  {
	  	$sql = "insert into we_staff_account_bind(bind_account,bind_type,bind_uid,province,city,gender,nick_name,favourites_count,followers_count,friends_count,statuses_count,verified,profile_image_url,url,online_status)values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
	    $this->conn->ExecSQL($sql,array(
	          (string)$acc,
	          (string)$type,	          
	          (string)$uid,
	          (int)$para["province"],
	          (int)$para["city"],
	          (string)$para["gender"],
	          (string)$para["screen_name"],
	          (int)$para["favourites_count"],
	          (int)$para["followers_count"],
	          (int)$para["friends_count"],
	          (int)$para["statuses_count"],
	          (int)$para["verified"],
	          (string)$para["profile_image_url"],
	          (string)$para["url"],
	          (int)$para["online_status"]
	     ));
	     return true;	    
	  }
	  
	  public function Bind($type,$acc,$wefafa_account,$uid="")
	  {
	  	  $isexist_sql= "select 1 from we_staff_account_bind where bind_type=? ";
	  	  if(empty($acc) && empty($uid))
	  	  {
	  	  	return false;	
	  	  }
	  	  $query_paras = array((string)$type);
	  	  if(!empty($acc)){
	  	  	 $isexist_sql = $isexist_sql. " and bind_account=?";
	  	  	 $query_paras[] = (string)$acc;
	  	  }
	  	  if(!empty($uid)){
	  	  	 $isexist_sql = $isexist_sql. " and bind_uid=?";
	  	  	 $query_paras[] = (string)$uid;
	  	  }
	  	  $ds = $this->conn->GetData("e",$isexist_sql,$query_paras);
	  	  if($ds && count($ds["e"]["rows"])>0)
	  	  {
	  	  	  $update_paras = array((string)$wefafa_account,(string)$type);
		  	  	$sql = "update we_staff_account_bind set login_account=?,bind_created=now() where bind_type=?";	
			  	  if(!empty($acc)){
			  	  	 $sql = $sql. " and bind_account=?";
			  	  	 $update_paras[] = (string)$acc;
			  	  }
			  	  if(!empty($uid)){
			  	  	 $sql = $sql. " and bind_uid=?";
			  	  	 $update_paras[] = (string)$uid;
			  	  }
		  	  	$this->conn->ExecSQL($sql,$update_paras);
	  	  }
	  	  else
	  	  {
		      $sql = "insert into we_staff_account_bind(bind_account,bind_type,bind_uid,login_account,bind_created)values(?,?,?,?,now())";	
		      $this->conn->ExecSQL($sql,array(
		          (string)$acc,
		          (string)$type,	          
		          (string)$uid,
		          (string)$wefafa_account
		      ));
	      }
	      return true;
	  }
	  public function UnBind($wefafa_account,$type,$acc)
	  {
	  		$sql = "delete from we_staff_account_bind where bind_account=? and bind_type=? and login_account=?";	
	      $this->conn->ExecSQL($sql,array(
	          (string)$acc,
	          (string)$type,
	          (string)$wefafa_account
	      ));
	      return true;
	  }
}
