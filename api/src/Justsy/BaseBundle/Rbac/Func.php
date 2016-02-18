<?php

namespace Justsy\BaseBundle\Rbac;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Management\Staff;
use Justsy\BaseBundle\Common\DES;

class Func{
	  private $conn=null;
	  private $conn_im=null;
	  
	  public function __construct($conn,$conn_im){
	    $this->conn=$conn;
	  	$this->conn_im=$conn_im;
	  }
	  //根据功能点代码 获取角色数据
	  public function GetFunctionDataByCode($code){
	  	$sql ="select id,name,code from we_function where code=?";
	  	$para=array((string)$code);
	  	$data=$this->conn->GetData("dt",$sql,$para);
	  	
	  	return $data["dt"]["rows"];
	  }
	  //根据功能点名称 获取角色数据
	  public function GetFunctionDataByName($name){
	  	$sql ="select id,name,code from we_function where name=?";
	  	$para=array((string)$name);
	  	$data=$this->conn->GetData("dt",$sql,$para);
	  	
	  	return $data["dt"]["rows"];
	  }
	  //根据角色主键获取功能点名称集合 返回 多个用逗号分隔开的字符串形式
	  public function GetFunctionNames($roleid){
	  	$sql ="select group_concat(b.name) as names ";
	  	$sql.="from we_role_function a , we_function b where a.functionid=b.id and  a.roleid=? ";
	  	$para=array((string)$roleid);
	  	$data=$this->conn->GetData("dt",$sql,$para);
	  	
	  	return $data["dt"]["rows"][0]["names"];
	  }
	  //根据角色主键获取功能点代码集合 返回 多个用逗号分隔开的字符串形式
	  public function GetFunctionCodes($roleid){
	  	$sql ="select group_concat(b.code) as codes ";
	  	$sql.="from we_role_function a , we_function b where a.functionid=b.id and  a.roleid=? ";
	  	$para=array((string)$roleid);
	  	$data=$this->conn->GetData("dt",$sql,$para);
	  	
	  	return $data["dt"]["rows"][0]["codes"];
	  }
	  //根据角色主键获取功能点数据集合 返回 array数组类型
	  public function GetFunctionData($roleid){
	  	$sql ="select b.name,b.code from we_role_function a , we_function b where a.functionid=b.id and  a.roleid=? ";
	  	$para=array((string)$roleid);
	  	$data=$this->conn->GetData("dt",$sql,$para);
	  	
	  	return $data["dt"]["rows"];
	  }
	  //权限控制开/关
	  public function SetFunctionOn($accounts,$functions,$eno)
	  {
	  	try{
	  		$accounts_str='';
		  	for($i=0;$i< count($accounts);$i++)
		  	{
		  		$accounts_str.="'".$accounts[$i]."',";
		  	}
		  	$accounts_str=trim($accounts_str,',');
		  	$sql="select login_account,fafa_jid from we_staff where login_account in($accounts_str)";
		  	$ds=$this->conn->Getdata('accounts',$sql,array());
		  	$staffs=array();
		  	if($ds['accounts']['recordcount']>0)
		  	{
		  		$staffs=$ds['accounts']['rows'];
		  	}
		  	$sqls=array();
		  	$paras=array();
		  	$sqls_m=array();
		  	$paras_m=array();
		  	for($i=0;$i< count($staffs);$i++)
		  	{
		  		if($staffs[$i]["fafa_jid"]==null or $staffs[$i]["fafa_jid"]=='')continue;
		  		for($j=0;$j< count($functions);$j++)
		  		{
		  			$sqls[]="insert into we_function_onoff (functionid,login_account,state,eno) values(?,?,?,?) ON DUPLICATE KEY UPDATE state='1'";
		  			$paras[]=array($functions[$j],$staffs[$i]["login_account"],'1',$eno);
		  			//获取jid
		  			$sqls_m[]="insert into im_employeerole(employeeid,roleid) values(?,?) ON DUPLICATE KEY UPDATE employeeid=?";
		  			$paras_m[]=array($staffs[$i]["fafa_jid"],$functions[$j],$staffs[$i]["fafa_jid"]);
		  		}
		  	}
		  	if($this->conn->ExecSQLs($sqls,$paras))
		  	{
		  		$this->conn_im->ExecSQLs($sqls_m,$paras_m);
		  		return true;
		  	}
		  	else
		  		return false;
	  	}
	  	catch(\Exception $e)
	  	{
	  		var_dump($e->getMessage());
	  		return false;
	  	}
	  }
	  public function SetFunctionOff($accounts,$functions)
	  {
	  	try{
	  		$accounts_str='';
		  	for($i=0;$i< count($accounts);$i++)
		  	{
		  		$accounts_str.="'".$accounts[$i]."',";
		  	}
		  	$accounts_str=trim($accounts_str,',');
		  	$sql="select login_account,fafa_jid from we_staff where login_account in($accounts_str)";
		  	$ds=$this->conn->Getdata('accounts',$sql,array());
		  	$staffs=array();
		  	if($ds['accounts']['recordcount']>0)
		  	{
		  		$staffs=$ds['accounts']['rows'];
		  	}
		  	$sqls=array();
		  	$paras=array();
		  	$sqls_m=array();
		  	$paras_m=array();
		  	for($i=0;$i< count($staffs);$i++)
		  	{
		  		if($staffs[$i]["fafa_jid"]==null or $staffs[$i]["fafa_jid"]=='')continue;
		  		for($j=0;$j< count($functions);$j++)
		  		{
		  			$sqls[]="delete from we_function_onoff where login_account=? and functionid=?";
		  			$paras[]=array($staffs[$i]["login_account"],$functions[$j]);
		  			$sqls_m[]="delete from im_employeerole where employeeid=? and roleid=?";
		  			$paras_m[]=array($staffs[$i]["fafa_jid"],$functions[$j]);
		  		}
		  	}
		  	if($this->conn->ExecSQLs($sqls,$paras))
		  	{
		  		$this->conn_im->ExecSQLs($sqls_m,$paras_m);
		  		return true;
		  	}
		  	else
		  		return false;
	  	}
	  	catch(\Exception $e)
	  	{
	  		return false;
	  	}
	  } 
}
