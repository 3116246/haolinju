<?php

namespace Justsy\BaseBundle\Rbac;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Management\Staff;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Common\Cache_Enterprise;
class Role{

	  private $conn=null;
	  private $conn_im=null;
	  
	  public function __construct($conn,$conn_im){
	    $this->conn=$conn;
	  	$this->conn_im=$conn_im;
	  }
	  //获取角色所有数据 返回 array数组类型
	  public function GetRoleData(){
	  	$data=Cache_Enterprise::get(Cache_Enterprise::$EN_ROLE,"");
	  	if(empty($data))
	  	{
		  	$sql ="select id,name,code,role_type from we_role where role_type is null";
		  	$para=array();
		  	$data=$this->conn->GetData("dt",$sql,$para);
		  	for($i=0;$i<count($data["dt"]["rows"]);$i)
		  	{
		  		$datarow = $data["dt"]["rows"][$i];
		  		Cache_Enterprise::set(Cache_Enterprise::$EN_ROLE,$datarow["code"],json_encode($datarow));
		  	}		  	
		  	return $data["dt"]["rows"];
	  	}
	  	else
	  	{
	  		return json_decode($data,true);
	  	}
	  }
	  //获取企业的自定义角色列表
	  public function GetRoleDataByEno($eno)
	  {
	  	$data='';//Cache_Enterprise::get(Cache_Enterprise::$EN_ROLE,$eno);
	  	if(empty($data))
	  	{
		  	$sql ="select a.id,a.name,a.code,a.role_type from we_role a where a.role_type='2' and a.eno=?";
		  	$para=array((string)$eno);
		  	$data=$this->conn->GetData("dt",$sql,$para);
		  	$rows=$data["dt"]["rows"];
		  	for($i=0;$i<count($rows);$i++)
		  	{
		  		$datarow = $rows[$i];
		  		//Cache_Enterprise::set(Cache_Enterprise::$EN_ROLE,$datarow["code"],json_encode($datarow));
		  	}		  	
		  	return $rows;
	  	}
	  	else
	  	{
	  		return json_decode($data,true);
	  	}	  	
	  }
	  //设置角色权限
	  public function setRoleFuncs($roleid,$functions){
	  	try{
	  		$sqls=[];
	  	$paras=[];
	  	$sqls[]="delete from we_role_function where roleid=?";
	  	$paras[]=array($roleid);
	  	
	  	for($i=0;$i< count($functions);$i++){
	  		$id=SysSeq::GetSeqNextValue($this->conn,"we_role_function","id");
	  		$sqls[]="insert into we_role_function (id,roleid,functionid) values(?,?,?)";
	  		$paras[]=array($id,$roleid,$functions[$i]);
	  	}
	  	if(!$this->conn->ExecSQLs($sqls,$paras))return false;
	  	
	  	//获取企业,员工
	  	$sqls=[];
	  	$paras=[];
	  	$sqls[]="select eno from we_role where id=?";
	  	$paras[]=array($roleid);
	  	$sqls[]="select staff from we_staff_role where roleid=?";
	  	$paras[]=array($roleid);
	  	$ds=$this->conn->Getdatas(array('info1','info2'),$sqls,$paras);
	  	$eno=$ds['info1']['rows'][0]["eno"];
	  	$rows=$ds['info2']['rows'];
	  	$staffs=[];
	  	foreach($rows as $row){
	  		array_push($staffs,$row['staff']);
	  	}
	  	$staffRole = new StaffRole($this->conn,$this->conn_im,null);
	  	return $staffRole->setStaffRole($roleid,$staffs,$eno);
	  	}
	  	catch(\Exception $e){
	  		return false;
	  	}
	  }
	  //获取企业员工角色列表
	  public function GetUserRoles($eno){
	  	$sql="select a.login_account,a.nick_name,b.roleid from we_staff a left join we_staff_role b on b.staff=a.login_account and b.eno=?  where a.eno=?";
	  	$params=array($eno,$eno);
	  	$ds=$this->conn->Getdata('roles',$sql,$params);
	  	return $ds["roles"]['rows'];
	  }
	  //添加企业自定义角色
	  public function saveEnRole($eno,$rolecode,$rolename)
	  {
	  	$id= SysSeq::GetSeqNextValue($this->conn,"we_role","id");
	  	$sql = "insert into we_role(id,name,code,role_type,eno)values(?,?,?,'2',?)";
	  	$para=array((string)$id,(string)$rolename,(string)$rolecode,(string)$eno);
	  	$this->conn->ExecSQL($sql,$para);
	  	$datarow = array(
	  		"id"=>$id,
	  		"name"=>$rolename,
	  		"code"=>$rolecode,
	  		"role_type"=>"2",
	  		"eno" => $eno
	  	);
	  	Cache_Enterprise::set(Cache_Enterprise::$EN_ROLE,$rolecode,json_encode($datarow));
	  	return 1;
	  }
	  //编辑企业自定义角色
	  public function editEnRole($roleid,$rolecode,$rolename)
	  {
	  	$sql="update we_role set name=?,code=? where id=?";
	  	$params=array($rolename,$rolecode,$roleid);
	  	return $this->conn->ExecSQL($sql,$params);
	  }
	  public function deleteRole($roleid)
	  {
	  	$staffRole = new StaffRole($this->conn,$this->conn_im,null);
	  	$ds = $this->conn->GetData("t","SELECT  a.staff,b.fafa_jid,b.eno FROM we_staff_role a,we_staff b where a.staff=b.login_account and a.roleid=?",array((string)$roleid));
	  	$sql=array();
	  	$paras=array();
	  	$sql[] = "delete from we_role where id=?";
	  	$paras[]=array((string)$roleid);
	  	for($i=0; $i<count($ds["t"]["rows"]); $i++)
	  	{
	  		$row= $ds["t"]["rows"][$i];
	  		$staffRole->DeleteStaffRole($row["staff"],$roleid,$row["eno"]);
	  	}
		$this->conn->ExecSQLs($sql,$paras);
		//Cache_Enterprise::delete(Cache_Enterprise::$EN_ROLE,$eno);
		return true;
	  }
	  //根据角色代码 获取角色数据
	  public function GetRoleDataByCode($code){
	  	$data=Cache_Enterprise::get(Cache_Enterprise::$EN_ROLE,$code);
	  	if(empty($data))
	  	{
		  	$sql ="select id,name,code from we_role where code=?";
		  	$para=array((string)$code);
		  	$data=$this->conn->GetData("dt",$sql,$para);
		  	$datarow = $data["dt"]["rows"];
		  	Cache_Enterprise::set(Cache_Enterprise::$EN_ROLE,$code,json_encode($datarow));
		  	return $data["dt"]["rows"];
	  	}
	  	else
	  	{
	  		return json_decode($data,true);
	  	}
	  }
	  //根据角色名称 获取角色数据
	  public function GetRoleDataByName($name){
	  	$sql ="select id,name,code from we_role where name=?";
	  	$para=array((string)$name);
	  	$data=$this->conn->GetData("dt",$sql,$para);
	  	
	  	return $data["dt"]["rows"];
	  }
	  //获取角色的对应权限
	  public function getPrivByRole($roleid){
	  	$sql="select functionid from we_role_function where roleid=?";
	  	$params=array($roleid);
	  	$ds=$this->conn->Getdata('info',$sql,$params);
	  	return $ds['info']['rows'];
	  }
	  //获取员工的角色
	  public function getRoleByStaff($login_account){
	  	$sql="select roleid from we_staff_role where staff=?";
	  	$params=array($login_account);
	  	$ds=$this->conn->Getdata('info',$sql,$params);
	  	return $ds["info"]["rows"];
	  }
	  //获取角色对应的员工
	  public function getStaffByRole($roleid){
	  	$sql="select staff from we_staff_role where roleid=?";
	  	$params=array($roleid);
	  	$ds=$this->conn->Getdata('info',$sql,$params);
	  	return $ds['info']['rows'];
	  }
	  //获取角色所有数据并返回功能点  返回 树形结构数据 需要json_encode编码返回页面
	  public function GetRoleToTree(){
	  	$sql ="select id,name,code from we_role  where role_type is null";
	  	$para=array();
	  	$data=$this->conn->GetData("dt",$sql,$para);
	  	$array=array();
	  	for ($i = 0; $i < count($data["dt"]["rows"]); $i++) {
	  	 	$sql_function="select b.id,b.name,b.code from we_role_function a, we_function b where a.functionid=b.id and a.roleid=?";
	  	 	$para_function=array((string)$data["dt"]["rows"][$i]["id"]);
	  	 	$data_function=$this->conn->GetData("dt",$sql,$para);
	  	 	if(count($data_function["dt"]["rows"])>0){
	  	 		$array_function=array();
	  	 		for ($j = 0; $j < count($data_function["dt"]["rows"]); $j++) {
	  	 		 	 array_push($array_function,array("id"=>"f".$data_function["dt"]["rows"][$j]["id"]
	  													 							 ,"text"=>$data_function["dt"]["rows"][$j]["name"]));
	  	 		}
	  	 		array_push($array,array("id"=>"r".$data["dt"]["rows"][$i]["id"]
	  													 ,"text"=>$data["dt"]["rows"][$i]["name"]
	  													 ,"children"=>$array_function));
	  	 	}else{
	  	 		array_push($array,array("id"=>"r".$data["dt"]["rows"][$i]["id"]
	  													 ,"text"=>$data["dt"]["rows"][$i]["name"]));
	  	 	}
	  	}
	  	return $array;
	  }
}
