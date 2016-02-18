<?php

namespace Justsy\BaseBundle\Management;
use Justsy\BaseBundle\Common\Utils;
class RelationMgr
{
	  private $conn=null;
	  private $conn_im=null;
	  private $account="";    //帐号
	  private $userInfo; //用户对象
	 
	  
	  public function __construct($_db,$_db_im,$_account)
	  {
	    $this->conn = $_db;
	    $this->conn_im = $_db_im;
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
	    
	  }
	  //获取当前人的粉丝总数
	  public function getAttenMeCount()
	  {
	  	  $sql = "select count(1) cnt from we_staff_atten where atten_id=? and atten_type='01'";
	  	  $ds = $this->conn->GetData("cnt",$sql,array($this->account));
	  	  return $ds["cnt"]["rows"][0]["cnt"];
	  }
	  
	  public function getAttenMeList($start,$size,$photo_path,$order='date',$searchby='')
	  {
	  	  $sql = "select a.login_account,a.nick_name,f_checkAttentionWithAccount(?,b.login_account) state,c.ename, concat('$photo_path',a.photo_path_big) photo from we_staff_atten b inner join we_staff a on a.login_account=b.login_account inner join we_enterprise c on a.eno=c.eno where b.atten_id=? and b.atten_type='01'";
	  	  $params=array($this->account,$this->account);
	  	  //查询附加条件
	  	  if($searchby==''){
	  	  }
	  	  else if(strlen($searchby)>mb_strlen($searchby,'utf8')){
	  	  	$sql.=" and (a.nick_name like ? or c.ename like ?)";
	  	  	array_push($params,$searchby."%",$searchby."%");
	  	  }
	  	  else{
	  	  	$sql.=" and (b.login_account like ? or a.nick_name like ? or c.ename like ?)";
	  	  	array_push($params,$searchby."%",$searchby."%",$searchby."%");
	  	  }
	  	  //排序条件
	  	  if($order=='date'){
	  	  	$sql.=" order by b.atten_date desc";
	  	  }
	  	  else if($order=='name')
	  	  {
	  	  	$sql.=" order by a.nick_name asc";
	  	  }
	  	  $sql .= " limit $start,".($start+$size);
	  	  $ds = $this->conn->GetData("cnt",$sql,$params);
	  	  return $ds["cnt"]["rows"];
	  }
	  
	  //获取当前人关注的总数
	  public function getMeAttenCount()
	  {
	  	  $sql = "select count(1) cnt from we_staff_atten where login_account=? and atten_type='01'";
	  	  $ds = $this->conn->GetData("cnt",$sql,array($this->account));
	  	  return $ds["cnt"]["rows"][0]["cnt"];
	  }	  
	  public function getMeAttenList($start,$size,$photo_path,$order='date',$searchby='')
	  {
	  	  $sql = "select a.login_account,a.nick_name,f_checkAttentionWithAccount(b.atten_id,?) state,c.ename, concat('$photo_path',a.photo_path_big) photo from we_staff_atten b inner join we_staff a on a.login_account=b.atten_id inner join we_enterprise c on a.eno=c.eno where b.login_account=? and b.atten_type='01'";
	  	  $params=array($this->account,$this->account);
	  	  //查询附加条件
	  	  if($searchby==''){
	  	  }
	  	  else if(strlen($searchby)>mb_strlen($searchby,'utf8')){
	  	  	$sql.=" and (a.nick_name like ? or c.ename like ?)";
	  	  	array_push($params,$searchby."%",$searchby."%");
	  	  }
	  	  else{
	  	  	$sql.=" and (b.atten_id like ? or a.nick_name like ? or c.ename like ?)";
	  	  	array_push($params,$searchby."%",$searchby."%",$searchby."%");
	  	  }
	  	  if($order=='date'){
	  	  	$sql.=" order by b.atten_date desc";
	  	  }
	  	  else if($order=='name')
	  	  {
	  	  	$sql.=" order by a.nick_name asc";
	  	  }
	  	  $sql .= " limit $start,".($start+$size);
	  	  $ds = $this->conn->GetData("cnt",$sql,$params);
	  	  return $ds["cnt"]["rows"];
	  }
	  //从我的好友、同事中搜索关键字
	  public function query($value)
	  {
	  	if(empty($value)) return null;
	  	$staff_c ="select nick_name,
    we_staff.login_account,
    photo_path_big,
    photo_path from we_staff 
where eno=? and state_id='1' and login_account!=?
 and not exists (select 1 from we_micro_account where eno=? and we_staff.login_account=number)
";  //根据帐号过虑无用数据后的集合
	  	$staff_n ="select nick_name,
    we_staff.login_account,
    photo_path_big,
    photo_path from we_staff 
where eno=? and state_id='1' and nick_name!=?
 and not exists (select 1 from we_micro_account where eno=? and we_staff.login_account=number)
";  //根据姓名过虑无用数据后的集合

     $atten_c = "select 
    nick_name,
    we_staff.login_account,
    photo_path_big,
    photo_path
from
    we_staff,
    we_staff_atten
where
    we_staff.login_account = we_staff_atten.login_account
        and we_staff_atten.atten_type = '01'
        and we_staff_atten.atten_id = ?"; //关注我的数据集合
        
      $paras = array();
	  	if (preg_match("/[\x7f-\xff]/", $value)) //根据姓名查询
	  	{
	  	  $sql = $staff_n." and nick_name like concat('%',?,'%') union ".
	  	         $atten_c." and we_staff.nick_name like concat('%',?,'%')";
	  	  $paras[] = (string)$this->userInfo->eno;
	  	  $paras[] = (string)$this->userInfo->nick_name;
	  	  $paras[] = (string)$this->userInfo->eno;
	  	  $paras[] = (string)$value;
	  	  $paras[] = (string)$this->userInfo->getUserName();
	  	  $paras[] = (string)$value;
	  	}
	  	else   //非中文查询，需要考虑姓名中包含非中文的人员
	  	{
	  	  $sql = $staff_n." and (nick_name like concat('%',?,'%') or login_account like concat('%',?,'%')) union ".
	  	         $atten_c." and (we_staff.nick_name like concat('%',?,'%') or we_staff.login_account like concat('%',?,'%')) ";
	  	  $paras[] = (string)$this->userInfo->eno;
	  	  $paras[] = (string)$this->userInfo->nick_name;
	  	  $paras[] = (string)$this->userInfo->eno;
	  	  $paras[] = (string)$value;
	  	  $paras[] = (string)$value;
	  	  $paras[] = (string)$this->userInfo->getUserName();
	  	  $paras[] = (string)$value;
	  	  $paras[] = (string)$value;
	  	}
	    $ds = $this->conn->GetData("staff",$sql,$paras);
	    return $ds["staff"]["rows"];
	  }
}
