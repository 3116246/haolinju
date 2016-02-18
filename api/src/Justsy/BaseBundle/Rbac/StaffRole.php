<?php

namespace Justsy\BaseBundle\Rbac;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Management\Staff;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Common\Cache_Enterprise;
class StaffRole{
	  private $conn=null;
	  private $conn_im=null;
	  //private $userinfo=null;
	  private $logger=null;
	  private $container = null;
	  //$conn,$conn_im,$userinfo,$logger,$container
	  public function __construct($conn,$conn_im,$logger=null,$container=null){
	    $this->conn=$conn;
	  	$this->conn_im=$conn_im;
	  	$this->container=$container;
	  	//$this->userinfo=$userinfo;
	  	//if(!empty($userinfo)){
		  	//if( is_string($userinfo)){
	  		 //$staff = new Staff($conn,$conn_im,$userinfo,$logger);
	  		 //$this->userinfo = $staff->getSessionUser();
		  	//}
	    //}
	  	$this->logger=$logger;
	  }
	  //根据帐号和角色获取关联数据
	  private function getStaffRole($staff_account,$roleid){
	  	$sql ="select * from we_staff_role where staff=? and roleid=?";
	  	$para=array((string)$staff_account,(string)$roleid);
	  	$data=$this->conn->GetData("dt",$sql,$para);
	  	return $data["dt"]["rows"];
	  }
	  //根据角色代码获取角色数据
	  private function getRoleByCode($rolecode){
	  	$roleMgr = new Role($this->conn,$this->conn_im);
        return $roleMgr->GetRoleDataByCode($rolecode);
	  }
	  //根据角色CODE或ID查询对应角色拥有的功能点集合
	  public function getFunctionCodes($rolecode){
	  	$data=Cache_Enterprise::get(Cache_Enterprise::$EN_FUNCTION,$rolecode);
        if(empty($data))
        {
		  	$sql ="select DISTINCT c.code,c.name from we_role a,we_role_function b,we_function c where a.id=b.roleid and b.functionid=c.id and (a.code=? or a.id=?) and c.code is not null and c.name is not null";
		  	$para=array((string)$rolecode,(string)$rolecode);
		  	$data=$this->conn->GetData("dt",$sql,$para);
		  	Cache_Enterprise::set(Cache_Enterprise::$EN_FUNCTION,$rolecode,json_encode($data["dt"]["rows"]));
		  	return $data["dt"]["rows"];
	  	}
	  	else
	  	{
	  		return json_decode($data,true);
	  	}
	  }
	  //根据角色获取功能点代码
	  private function getFunctionCode($roleid){
	  	$data=Cache_Enterprise::get(Cache_Enterprise::$EN_FUNCTION,$roleid);
        if(empty($data))
        {
		  	$sql ="select DISTINCT b.code from we_role_function a,we_function b where a.functionid=b.id and a.roleid=? and b.code is not null";
		  	$para=array((string)$roleid);
		  	$data=$this->conn->GetData("dt",$sql,$para);
		  	Cache_Enterprise::set(Cache_Enterprise::$EN_FUNCTION,$roleid,json_encode($data["dt"]["rows"]));
		  	return $data["dt"]["rows"];
	  	}
	  	else
	  	{
	  		return json_decode($data,true);
	  	}
	  }
	  //根据角色代码添加人员-角色管理  返回 0 添加成功 1添加失败
	  public function InsertStaffRoleByCode($staff_account,$rolecode,$eno){
	  	$array["success"]=0;
	  	$array["msg"]="";
	  	$data_role=$this->getRoleByCode($rolecode);
	  	if(!empty($data_role)){
	  		$roleid=$data_role[0]["id"];
	  		$array=$this->InsertStaffRole($staff_account,$roleid,$eno);
	  	}else{
	  		$array["success"]=1;
	  		$array["msg"]="系统中不存在该角色";
	  	}
	  	return $array;
	  }
	  //根据帐号获取员工fafa_jid
	  private function getStaffJid($staff_account){
	  	$sql="select fafa_jid from we_staff where login_account=? ";
	  	$para=array((string)$staff_account);
	  	$data=$this->conn->GetData("dt",$sql,$para);
	  	if(count($data["dt"]["rows"])>0){
	  		return $data["dt"]["rows"][0]["fafa_jid"];
	  	}
	  	return "";
	  }
	  //根据帐号获取员工openid
	  private function getStaffOpenid($staff_account){
	  	$sql="select openid from we_staff where login_account=? ";
	  	$para=array((string)$staff_account);
	  	$data=$this->conn->GetData("dt",$sql,$para);
	  	if(count($data["dt"]["rows"])>0){
	  		return $data["dt"]["rows"][0]["openid"];
	  	}
	  	return "";
	  }
	  //添加人员-角色管理 返回 0 添加成功 1添加失败
	  public function InsertStaffRole($staff_account,$roleid,$eno){
	  	$turn='0';
	  	//判断权限控制类型
	  	$cacheobj = new \Justsy\BaseBundle\Management\Enterprise($this->conn,$this->logger,$this->container);   
      	$enterinfo =   $cacheobj->getInfo($eno);
	  	$eno_level=$enterinfo['eno_level'];
	  	$mstyle=$enterinfo['mstyle'];
	  	if($eno_level!='S' || $mstyle=='outpriv'){
	  			$turn='1';
	  	}
	  	
	  	$array["success"]=0;
	  	$array["msg"]="";
	  	$staff_fafajid=$this->getStaffJid($staff_account);
	  	if(empty($staff_fafajid)){
	  		$array["success"]=1;
	  		$array["msg"]="帐号不存在";
	  	}else{
		  	$sqls=array();
		  	$paras=array();
		  	$sqls_im=array();
		  	$paras_im=array();
		  	if(count($this->getStaffRole($staff_account,$roleid))>0){
		  		$array["success"]=1;
		  		$array["msg"]="人员角色已经存在";
		  	}else{
		  		$id= SysSeq::GetSeqNextValue($this->conn,"we_staff_role","id");
			  	$sqls[] ="insert into we_staff_role(id,staff,roleid,eno) values(?,?,?,?)";
			  	$para=array();
			  	array_push($para,(string)$id);
			  	array_push($para,(string)$staff_account);
			  	array_push($para,(string)$roleid);
			  	array_push($para,(string)$eno);
		  		$paras[]=$para; 
		  		$data_function=$this->getFunctionCode($roleid);
		  		if(count($data_function)>0){
		  			$sqls[]="delete from we_function_onoff where login_account=? ";
		  			$paras[]=array($staff_account);
		  			$sqls_im="delete from im_employeerole where employeeid=? ";
				  	$para_im=array();
				  	array_push($para_im,(string)$staff_fafajid);				  	
				  	$this->conn_im->ExecSQL($sqls_im, $para_im);
				  	$sqls_im=array();
				  	$paras_im=array();
				  	if($turn=='1'){
			  			for ($i = 0; $i < count($data_function); $i++) {
			  				$sqls[]="insert into we_function_onoff (functionid,login_account,state,eno) values(?,?,?,?)";
			  				$paras[]=array($data_function[$i]["code"],$staff_account,$turn,$eno);

		  			 	    $sqls_im[]="insert into im_employeerole(employeeid,roleid) values(?,?)";
						  	$para_im=array();
						  	array_push($para_im,(string)$staff_fafajid);
						  	array_push($para_im,(string)$data_function[$i]["code"]);
						  	$paras_im[]=$para_im;
			  			}
			  		}
		  		}
		  	}
		  	
	  	  try {
	      	if(!empty($sqls)){
		      	$dataexec=$this->conn->ExecSQLs($sqls, $paras);
		      	if($dataexec&&!empty($sqls_im)){
		      		$this->conn_im->ExecSQLs($sqls_im, $paras_im);
		      		$array["success"]=0;
		  			$array["msg"]="数据保存成功";
		      	}
		      	else{
		      		$array["success"]=1;
		  			$array["msg"]="数据保存失败";
		      	}
	      	}
	      } catch (\Exception $exc) {
	      	$this->logger->err($exc);
	      	$array["success"]=1;
	  		$array["msg"]="保存出现异常";
	      }
	    }
       return $array;
	  }
	  //批量修改企业对应角色并且同步IM库对应成员的功能点
	  public function UpdateEnoRoleByCode($rolecode,$newrolecode,$eno){
	  	$turn='0';
	  	//判断权限控制类型
	  	$sql="select eno_level,mstyle from we_enterprise where eno=?";
	  	$params=array($eno);
	  	$ds=$this->conn->Getdata('eee',$sql,$params);
	  	if($ds['eee']['recordcount']>0)
	  	{
	  		$eno_level=$ds['eee']['rows'][0]['eno_level'];
	  		$mstyle=$ds['eee']['rows'][0]['mstyle'];
	  		if($eno_level!='S' || $mstyle=='outpriv'){
	  			$turn='1';
	  		}
	  	}
	  	$array["success"]=0;
	  	$array["msg"]="";
	  	//获取原有角色代码对应的用户-(同一企业)
	  	$sql_staff="select a.id,a.staff,a.roleid,c.fafa_jid from we_staff_role a,we_role b,we_staff c where a.staff=c.login_account and a.roleid=b.id and a.eno=? and b.code=? and a.staff is not null and c.fafa_jid is not null";
	  	$para_staff=array((string)$eno,(string)$rolecode);
	  	$data_staff=$this->conn->GetData("dt",$sql_staff,$para_staff);
	  	$sqls=array();
	  	$paras=array();
	  	$sqls_im=array();
	  	$paras_im=array();
	  	if(count($data_staff["dt"]["rows"])>0){
	  		//获取最新角色代码对应的角色数据
	  		$data_new_role=$this->getRoleByCode($newrolecode);
	  		if(count($data_new_role)>0){
		  		$data_role=$this->getRoleByCode($rolecode);
		  		if(count($data_role)>0){
		  			$array_staff_fafajid=array();
		  			$array_staff_account=array();
		  			//循环原有角色对应的用户
			  		for ($i = 0; $i < count($data_staff["dt"]["rows"]); $i++) {
			  			//修改原有用户的角色
			  		 	$sqls[]="update we_staff_role set roleid=? where id=?";
			  		 	$paras[]=array((string)$data_new_role[0]["id"],$data_staff["dt"]["rows"][$i]["id"]);
			  		 	array_push($array_staff_fafajid,$data_staff["dt"]["rows"][$i]["fafa_jid"]);
			  		 	array_push($array_staff_account,$data_staff["dt"]["rows"][$i]["login_account"]);
			  		}
		  		 //查询原有角色对应的功能点
	  		 	 $data_function=$this->getFunctionCode($data_role[0]["id"]);
	  		 	 //查询最新角色对应的功能点
	  		 	 $data_new_function=$this->getFunctionCode($data_new_role[0]["id"]);
	  		 	 $functionstr='';
		  			for($i=0;$i< count($data_new_function);$i++)
		  			{
		  					$functionstr.="'".$data_new_function[$i]["code"]."',";
		  			}
		  			$functionstr=trim($functionstr,',');
		  			
		  			//权限控制
		  			for($j=0;$j< count($array_staff_account);$j++)
		  			{
		  				$sqls[]="delete from we_function_onoff where login_account=? and functionid not in($functionstr)";
			  			$paras[]=array($array_staff_account[$j]);
			  			
			  			if($turn=='1'){
				  			for($i=0;$i< count($data_new_function);$i++)
				  			{
				  				$sqls[]="insert into we_function_onoff (functionid,login_account,state,eno) values(?,?,?,?) ";
				  				$paras[]=array($data_new_function[$i]["code"],$array_staff_account[$j],$turn,$eno);
				  			}
				  		}
		  			}
		  			
	  		 	 if(count($data_function["dt"]["rows"])>0 && count($array_staff_fafajid)>0){
	  		 	 	//循环原有角色对应的功能点 删除IM库
	  		 	 	for ($k = 0; $k < count($array_staff_fafajid); $k++) {
	  		 	 		 $sqls_im[]="delete from im_employeerole where employeeid=? and roleid not in($functionstr)";
	  		 	  	 $paras_im[]=array((string)$array_staff_fafajid[$k]);
	  		 	 	}
	  		 	 }
	  		 	 
	  		 	 if(count($data_new_function["dt"]["rows"])>0&&count($array_staff_fafajid)>0){
	  		 	 	if($turn=='1'){
		  		 	 	for ($i = 0; $i < count($data_new_function["dt"]["rows"]); $i++) {
	  		 	 	 	  for ($j = 0; $j < count($array_staff_fafajid); $j++) {
	  		 	 	 	   	$sqls_im[]="insert into im_employeerole(employeeid,roleid) values(?,?)";
	  		 	  			$paras_im[]=array(
	  		 	  				(string)$array_staff_fafajid[$j]
	  		 	  				,(string)$data_new_function["dt"]["rows"][$i]["code"]);
	  		 	 	 	  }
		  		 	 	}
		  		 	}
	  		 	 }
		  		}else{
		  			$array["success"]=1;
	  				$array["msg"]="角色代码不存在【".$rolecode."】";
		  		}
	  		}else{
	  			$array["success"]=1;
	  			$array["msg"]="角色代码不存在【".$newrolecode."】";
	  		}
	  	}else{
	  		$array["success"]=1;
	  		$array["msg"]="企业不存在角色【".$rolecode."】";
	  	}
	  	try {
      	if(!empty($sqls)){
	      	$dataexec=$this->conn->ExecSQLs($sqls, $paras);
	      	if($dataexec&&!empty($sqls_im)){
	      		$this->conn_im->ExecSQLs($sqls_im, $paras_im);
	      		$array["success"]=0;
	  				$array["msg"]="数据保存成功";
	      	}	
	      	else{
	      		$array["success"]=1;
	  				$array["msg"]="数据保存失败";
	      	}
      	}else{
      		$array["success"]=1;
	  			$array["msg"]="没有数据需要保存";
      	}
      } catch (\Exception $exc) {
      	$this->logger->err($exc);
      	$array["success"]=1;
  			$array["msg"]="保存出现异常";
      }
	  	return $array;
	  }
	  //根据角色代码修改人员-角色管理  返回 0 添加成功 1添加失败
	  public function UpdateStaffRoleByCode($staff_account,$rolecode,$newrolecode,$eno){
	  	$array["success"]=0;
	  	$array["msg"]="";
	  	if($rolecode!=$newrolecode){
	  		$data_role=$this->getRoleByCode($rolecode);
		  	$data_new_role=$this->getRoleByCode($newrolecode);
		  	if(count($data_role)>0&&count($data_new_role)>0){
		  		$roleid=$data_role[0]["id"];
		  		$newroleid=$data_new_role[0]["id"];
		  		$array=$this->UpdateStaffRole($staff_account,$roleid,$newroleid,$eno);
		  	}else{
		  		$array["success"]=1;
		  		$array["msg"]="系统中不存在该角色";
		  	}
	  	}else{
	  		$array["success"]=0;
	  		$array["msg"]="权限没有任何更改";
	  	}
	  	return $array;
	  }
	  //修改人员-角色管理 返回 0 添加成功 1添加失败
	  public function UpdateStaffRole($staff_account,$roleid,$newroleid,$eno){
	  	$array["success"]=0;
	  	$array["msg"]="";
	  	$sqls=array();
	  	$paras=array();
	  	$sqls_im=array();
	  	$paras_im=array();
	  	$data=$this->getStaffRole($staff_account,$roleid);
	  	if(count($data)>0){
	  		$sqls[] ="update we_staff_role set roleid=? where staff=? and id=? and eno=?";
	  		$para=array();
	  		array_push($para,(string)$newroleid);
	  		array_push($para,(string)$staff_account);
	  		array_push($para,(string)$data[0]["id"]);
	  		array_push($para,(string)$eno);
	  		$paras[]=$para; 
	  	}else{
	  		$id= SysSeq::GetSeqNextValue($this->conn,"we_staff_role","id");
		  	$sqls[] ="insert into we_staff_role(id,staff,roleid,eno) values(?,?,?,?)";
		  	$para=array();
		  	array_push($para,(string)$id);
		  	array_push($para,(string)$staff_account);
		  	array_push($para,(string)$newroleid);
		  	array_push($para,(string)$eno);
	  		$paras[]=$para;
	  	}
	  	$staff_fafajid=$this->getStaffJid($staff_account);
	  	$data_function=$this->getFunctionCode($roleid);
	  	if(count($data_function)>0){
	  		for ($i = 0; $i < count($data_function); $i++) {
	  			$sqls_im[]="delete from im_employeerole where employeeid=? and roleid=?";
			  	$para_im=array();
			  	array_push($para_im,(string)$staff_fafajid);
			  	array_push($para_im,(string)$data_function[$i]["code"]);
			  	$paras_im[]=$para_im;
	  		}
	  	}
	  	$data_new_function=$this->getFunctionCode($newroleid);
  		if(count($data_new_function)>0){
  			for ($i = 0; $i < count($data_new_function); $i++) {
			 	  $sqls_im[]="insert into im_employeerole(employeeid,roleid) values(?,?)";
			  	$para_im=array();
			  	array_push($para_im,(string)$staff_fafajid);
			  	array_push($para_im,(string)$data_new_function[$i]["code"]);
			  	$paras_im[]=$para_im;
  			}
  		}
	  	try {
	  		if(!empty($sqls)){
	      	$dataexec=$this->conn->ExecSQLs($sqls, $paras);
	      	if($dataexec&&!empty($sqls_im)){
	      		$this->conn_im->ExecSQLs($sqls_im, $paras_im);
	      		$array["success"]=0;
	  				$array["msg"]="数据保存成功";
	      	}	
	      	else if(!$dataexec){
	      		$array["success"]=1;
	  				$array["msg"]="数据保存失败";
	      	}
      	}
      } catch (\Exception $exc) {
      	$this->logger->err($exc);
      	$array["success"]=1;
  			$array["msg"]="保存出现异常";
      }
      return $array;
	  }
	  public function DeleteStaffRoleByCode($staff_account,$rolecode,$eno){
	  	$array["success"]=0;
	  	$array["msg"]="";
  		$data_role=$this->getRoleByCode($rolecode);
	  	if(count($data_role)>0){
	  		$roleid=$data_role[0]["id"];
	  		$array=$this->DeleteStaffRole($staff_account,$roleid,$eno);
	  	}else{
	  		$array["success"]=1;
	  		$array["msg"]="系统中不存在该角色";
	  	}
	  	return $array;
	  }
	  //删除人员-角色管理 返回 0 删除成功 1 删除失败
	  public function DeleteStaffRole($staff_account,$roleid,$eno){
	  	$array["success"]=0;
	  	$array["msg"]="";
	  	$sql ="delete from we_staff_role where staff=? and roleid=? and eno=?";
	  	$para=array((string)$staff_account,(string)$roleid,(string)$eno);
	  	
	  	$sqls_im=array();
	  	$paras_im=array();
	  	$staff_fafajid=$this->getStaffJid($staff_account);
	  	$data_function=$this->getFunctionCode($roleid);
  		if(count($data_function)>0){
  			for ($i = 0; $i < count($data_function); $i++) {
			 	  $sqls_im[]="delete from im_employeerole where employeeid=? and roleid=?";
			  	$para_im=array();
			  	array_push($para_im,(string)$staff_fafajid);
			  	array_push($para_im,(string)$data_function[$i]["code"]);
			  	$paras_im[]=$para_im;
  			}
  		}
	  	try {
      	if(!empty($sql)){
	      	$dataexec=$this->conn->ExecSQLs($sql, $para);
	      	if($dataexec&&!empty($sqls_im)){
	      		$this->conn_im->ExecSQLs($sqls_im, $paras_im);
	      		$array["success"]=0;
	  				$array["msg"]="数据保存成功";
	      	}
	      	else if(!$dataexec){
	      		$array["success"]=1;
	  				$array["msg"]="数据保存失败";
	      	}
      	}
      } catch (\Exception $exc) {
      	$this->logger->err($exc);
      	$array["success"]=1;
  			$array["msg"]="保存出现异常";
      }
      return $array;
	  }
	  //通过角色CODE和企业获取对应用户集合
	  public function GetStaffByRoleCode($rolecode,$eno=""){
	  	$sql ="select c.login_account,c.nick_name,c.fafa_jid,c.openid from we_role a,we_staff_role b,we_staff c where a.id=b.roleid and b.staff=c.login_account and a.code=? ";
	  	$para=array((string)$rolecode);
	  	if(!empty($eno)){
	  		$sql.="and c.eno=?";
	  		array_push($para,(string)$eno);
	  	}
	  	$data=$this->conn->GetData("dt",$sql,$para);
	  	return $data["dt"]["rows"];
	  }
	  //根据人员主键获取角色名称集合 返回 多个用逗号分隔开的字符串形式
	  public function GetRoleNames($staff_account,$eno){
	  	$sql ="select group_concat(b.name) as names ";
	  	$sql.="from we_staff_role a , we_role b where a.roleid=b.id and  a.staff=? and a.eno=?";
	  	$para=array((string)$staff_account,(string)$eno);
	  	$data=$this->conn->GetData("dt",$sql,$para);
	  	
	  	return $data["dt"]["rows"][0]["names"];
	  }
	  //根据人员主键获取角色代码集合 返回 多个用逗号分隔开的字符串形式
	  public function GetRoleCodes($staff_account,$eno){
	  	$sql ="select group_concat(b.code) as codes from we_staff_role a , we_role b where a.roleid=b.id and  a.staff=? and a.eno=?";
	  	$para=array((string)$staff_account,(string)$eno);
	  	$data=$this->conn->GetData("dt",$sql,$para);
	  	
	  	return $data["dt"]["rows"][0]["codes"];
	  }
	  //判断当前人是否拥有对应角色名称 返回 1 不包含 0 包含
	  public function IsContainRoleName($staff_account,$eno,$role_name){
	  	$array["success"]=0;
	  	$array["msg"]="";
	  	$sql ="select count(1) as count from we_staff_role a , we_role b where a.roleid=b.id and b.name=? and a.staff=? and a.eno=?";
	  	$para=array((string)$role_name,(string)$staff_account,(string)$eno);
	  	$data=$this->conn->GetData("dt",$sql,$para);
	  	if(count($data["dt"]["rows"][0]["count"])>0){
	  		$array["success"]=0;
  			$array["msg"]="该角色名称已存在";
	  	}
	  	else{
	  		$array["success"]=0;
  			$array["msg"]="该角色名称可以使用";
	  	}
	  	return $array;
	  }
	  //判断当前人是否拥有对应角色代码 返回 1 不包含 0 包含
	  public function IsContainRoleCode($staff_account,$eno,$role_code){
	  	$array["success"]=0;
	  	$array["msg"]="";
	  	$sql ="select count(1) as count from we_staff_role a , we_role b where a.roleid=b.id and b.code=? and a.staff=? and a.eno=?";
	  	$para=array((string)$role_code,(string)$staff_account,(string)$eno);
	  	$data=$this->conn->GetData("dt",$sql,$para);
	  	if(count($data["dt"]["rows"][0]["count"])>0){
	  		$array["success"]=0;
  			$array["msg"]="该角色代码已存在";
	  	}
	  	else{
	  		$array["success"]=0;
  			$array["msg"]="该角色代码可以使用";
	  	}
	  	return $array;
	  }
	  //角色授权
	  public function setStaffRole($roleid,$staffs,$eno)
	  {
	  	try{
	  	$sql="select staff from we_staff_role where roleid=?";
	  	$params=array($roleid);
	  	$ds=$this->conn->Getdata('staffs',$sql,$params);
	  	$staffs2=array();
	  	foreach($ds["staffs"]["rows"] as $row){
	  		array_push($staffs2,$row['staff']);
	  	}
	  	$sqls=[];
	  	$paras=[];
	  	$sqls[]="delete from we_staff_role where roleid=? and eno=?";
	  	$paras[]=array($roleid,$eno);
	  	for($i=0;$i<count($staffs);$i++){
	  		if(empty($staffs[$i]))continue;
	  		$id=SysSeq::GetSeqNextValue($this->conn,"we_staff_role","id");
	  		$sqls[]="insert into we_staff_role (id,staff,roleid,eno) values(?,?,?,?)";
	  		$paras[]=array($id,$staffs[$i],$roleid,$eno);
	  	}
	  	if(!$this->conn->ExecSQLs($sqls,$paras))return false;
	  	//return true;  //这是谁直接写了return. liling 2015-01-22
	  	//同步到im库
	  	$staffs=array_unique(array_merge($staffs,$staffs2));
	  	for($j=0;$j< ceil(count($staffs)/256);$j++){
	  		$sqls1=[];
	  		$paras1=[];
		  	$sqls2=[];
		  	$paras2=[];
	  		$sql1="select c.code,(select d.fafa_jid from we_staff d where d.login_account=a.staff) as fafa_jid from we_staff_role a,we_role_function b left join we_function c on c.id=b.functionid where a.eno=? and a.roleid=b.roleid and a.staff in (";
	  		$sql2="select fafa_jid from we_staff where login_account in (";
		  	for($i=0;$i< min(($j+1)*256,count($staffs));$i++){
		  		$sql1.="'".$staffs[$j*256+$i]."',";
		  		$sql2.="'".$staffs[$j*256+$i]."',";
		  	}
		  	$sql1=rtrim($sql1,',').")";
		  	$sql2=rtrim($sql2,',').")";
		  	$sqls1[]=$sql1;
		  	$paras1[]=array($eno);
		  	$sqls1[]=$sql2;
		  	$paras1[]=array();
		  	$ds=$this->conn->Getdatas(array("info1","info2"),$sqls1,$paras1);
		  	$rows1=$ds['info1']['rows'];
		  	$rows2=$ds['info2']['rows'];
		  	$sql3="delete from im_employeerole where employeeid in (";
		  	foreach($rows2 as $row){
		  		$sql3.="'".$row['fafa_jid']."',";
		  	}
		  	$sql3=rtrim($sql3,',').")";
		  	$sqls2[]=$sql3;
		  	$paras2[]=array();
		  	for($i=0;$i< count($rows1);$i++){
		  		$sqls2[]="insert into im_employeerole (employeeid,roleid) values(?,?)";
		  		$paras2[]=array($rows1[$i]['fafa_jid'],$rows1[$i]['code']);
		  	}
		  	return $this->conn_im->ExecSQLs($sqls2,$paras2);
	  	}
	  	return true;
	  	}
	  	catch(\Exception $e){
	  		return false;
	  	}
	  	 
	  }
	  //角色授权
	  public function setRoleStaff($login_account,$roles,$eno)
	  {
	  	try{
	  		$sqls=[];
	  	$paras=[];
	  	$sqls[]="delete from we_staff_role where staff=? and eno=? and roleid not in (select b.id from we_role b where b.eno is null or b.eno='')";
	  	$paras[]=array($login_account,$eno);
	  	
	  	for($i=0;$i<count($roles);$i++){
	  		if(empty($roles[$i]))continue;
	  		$id=SysSeq::GetSeqNextValue($this->conn,"we_staff_role","id");
	  		$sqls[]="insert into we_staff_role (id,staff,roleid,eno) values(?,?,?,?)";
	  		$paras[]=array($id,$login_account,$roles[$i],$eno);
	  	}
	  	if(!$this->conn->ExecSQLs($sqls,$paras))return false;
	  	return true;
	  	//同步im库
	  	$sqls=[];
	  	$paras=[];
	  	$sqls[]="select c.code,(select d.fafa_jid from we_staff d where d.login_account=a.staff) as fafa_jid from we_staff_role a,we_role_function b left join we_function c on c.id=b.functionid where a.eno=? and a.roleid=b.roleid and a.staff=?";
	  	$sqls[]="select fafa_jid from we_staff where login_account=?";
	  	$paras[]=array($eno,$login_account);
	  	$paras[]=array($login_account);
	  	$ds=$this->conn->Getdatas(array('info1','info2'),$sqls,$paras);
	  	$rows1=$ds['info1']['rows'];
	  	$fafa_jid=$ds['info2']['rows'][0]["fafa_jid"];
	  	
	  	$sqls=[];
	  	$paras=[];
	  	$sqls[]="delete from im_employeerole where employeeid=?";
	  	$paras[]=array($fafa_jid);
	  	for($i=0;$i< count($rows1);$i++){
	  		$sqls[]="insert into im_employeerole (employeeid,roleid) values(?,?)";
	  		$paras[]=array($fafa_jid,$rows1[$i]['code']);
	  	}
	  	return $this->conn_im->ExecSQLs($sqls,$paras);
	  	}
	  	catch(\Exception $e){
	  		return false;
	  	}
	  }
}
