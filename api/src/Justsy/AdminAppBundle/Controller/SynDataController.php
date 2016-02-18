<?php

namespace Justsy\AdminAppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;

class SynDataController extends Controller
{
	 //同步组织机构数据(同步后删除同步源数据)
	 public function old_SynDataByORGAction()
	 { 
	 	 $da = $this->get('we_data_access');
	   $da_im = $this->get('we_data_access_im');
	   //企业号
	   $eno = "100001";
	   $root = "v".$eno;
	   $sqls = array();
	   $paras = array();
	   $result = array("success"=>true,"msg"=>"");
	   try
	   {
	   	  $sql = "select objid from mb_org_1 limit 1;";
	   	  $ds = $da->GetData("table",$sql);
	   	  if ( $ds && $ds["table"]["recordcount"]>0) {
		   	  //一级部门id
		   	  $sql = "select distinct sobid deptid from mb_org_1 where sobid not in(select objid from mb_org_1);";
		   	  $ds = $da->GetData("node",$sql);
		   	  if ( $ds && $ds["node"]["recordcount"]==1 ){		   	  	
		   	  	$parentId = $ds["node"]["rows"][0]["deptid"]; //部门根id
			   	  //删除we_sns部门表
			      $sql = "delete from we_department where eno=?;";
			   	  $da->ExecSQL($sql,array((string)$eno));
			   	  //获得数据源
			   	  $sql = "select objid deptid,stext deptname,case when sobid=? then ? else sobid end sns_parent_id, 
			   	            case when sobid=? then ? else sobid end parent_id  from mb_org_1 limit 20000;";
			   	  $para = array((string)$parentId,(string)$eno,(string)$parentId,(string)$root);
			   	  $ds = $da->GetData("table",$sql,$para);			   	  
			   	  //添加部门根节点
		   	  	$sql = "insert into we_department(eno,dept_id,dept_name,parent_dept_id,fafa_deptid)values(?,?,?,?,?);";
		   	  	$para = array((string)$eno,(string)$eno,"美特斯邦威","-10000",$root);
		   	  	array_push($sqls,$sql);
		   	  	array_push($paras,$para);
		   	  	for($i=0;$i< $ds["table"]["recordcount"]; $i++){
		   	  		 $row = $ds["table"]["rows"][$i];
		   	  		 $sql = "insert into we_department(eno,dept_id,dept_name,parent_dept_id,fafa_deptid)values(?,?,?,?,?)";
		   	  		 $para = array((string)$eno,(string)$row["deptid"],(string)$row["deptname"],(string)$row["sns_parent_id"],(string)$row["deptid"]);
		   	  		 array_push($sqls,$sql);
		   	  		 array_push($paras,$para);
		   	  		 if ( count($sqls)>50){
		   	  		 	 $da->ExecSQLS($sqls,$paras);
		   	  		 	 $sqls = array();
		   	  		 	 $paras = array();
		   	  		 }
		   	  	}	
		   	  	if ( count($sqls)>0) {
	   	  		   $da->ExecSQLS($sqls,$paras);
	   	  		 	 $sqls = array();
	   	  		 	 $paras = array();
		   	  	}
		   	  	$sql = "update we_staff set dept_id=? where dept_id=?";
		   	  	$para = array((string)$eno,(string)$parentId);
		   	  	$da->ExecSQL($sql,$para);
			   	  //操作we_im库的部门数据
			   	  $sql = "delete from im_base_dept;";
			   	  $da_im->ExecSQL($sql);	
			   	  $sqls = array();
			   	  $paras = array();
			   	  //添加部门根节点
			   	  $sql = "insert into im_base_dept(deptid,deptname,pid,noorder)values(?,?,?,0)";
			   	  $para = array("v".$eno,"美特斯邦威","-10000");
			   	  array_push($sqls,$sql);
			   	  array_push($paras,$para);
			   	  if ( $ds && $ds["table"]["recordcount"]>0){
			   	  	for($i=0;$i< $ds["table"]["recordcount"]; $i++){
			   	  		 $row = $ds["table"]["rows"][$i];
			   	  		 $sql = "insert into im_base_dept(deptid,deptname,pid,noorder)values(?,?,?,?)";
			   	  		 $para = array((string)$row["deptid"],(string)$row["deptname"],(string)$row["parent_id"],$i);
			   	  		 array_push($sqls,$sql);
			   	  		 array_push($paras,$para);
			   	  		 if ( count($sqls)>=50){
			   	  		 	 $da_im->ExecSQLS($sqls,$paras);
			   	  		 	 $sqls = array();
			   	  		 	 $paras = array();
			   	  		 }
			   	  	}
			   	  	if ( count($sqls)>0) {
		   	  		  $da_im->ExecSQLS($sqls,$paras);
			   	  	}
			   	  	//更改路径
			   	  	$sqls = array();
		   	  		$paras = array();		  	  
				  	  $sql = " call p_reset_deptpath(?,'');";
				  	  $para = array((string)$eno);
				  	  array_push($sqls,$sql);
				  	  array_push($paras,$para); 	  	  
				  	  $da_im->ExecSQLS($sqls,$paras);
				  	  //更新im_employee
				  	  $sql = "update im_employee set deptid=? where dept_id=?";
		   	  	  $para = array((string)$root,(string)$parentId);
		   	  	  $da_im->ExecSQL($sql,$para);
		   	  	
				  	  //数据更新后删除mb_org_1表数据
//				  	  $sql = "delete from mb_org_1;";
//				  	  $da->ExecSQL($sql);
			   	  }
			   	  
		   	  }
		   	  else if ( $ds && $ds["node"]["recordcount"]>0) {
		   	  	$result = array("success"=>false,"msg"=>"根部门ID必须唯一。");
		   	  }
		   	  else{
		   	  	$result = array("success"=>false,"msg"=>"未找到一级部门id");  	
		   	  }
	   	  }
	   	  else {
	   	  	$result = array("success"=>false,"msg"=>"未有部门数据可同步！");
	   	  }
	   }
	   catch(\Exception $e){
	   	 $result = array("success"=>false,"msg"=>"更新部门数据出错"); 
	   	 $this->get("logger")->err($e->getMessage());
	   }
	   $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;
	 }
	 
	  //同步组织机构数据(同步后删除同步源数据)
	 public function SynDataByORGAction()
	 { 
	 	 $da = $this->get('we_data_access');
	   $da_im = $this->get('we_data_access_im');
	   //企业号
	   $eno = "100001";
	   $root = "v".$eno;
	   $sqls = array();
	   $paras = array();
	   $result = array("success"=>true,"msg"=>"");
	   try
	   {
	   	  $sql = "select objid from mb_org_1 where stat2<3 limit 1;";
	   	  $ds = $da->GetData("table",$sql);
	   	  if ( $ds && $ds["table"]["recordcount"]>0) {
	   	  	$sqls = array();
	   	  	$sqls_im = array();
	   	  	$sqls_up = array();
	   	  	$paras_up = array();
	   	  	$paras = array();
	   	  	$paras_im = array();
	   	  	//添加一级部门数据---------------------------------------------------------------------------------------------------
	   	  	$sql = "select sobid deptid,stext deptname from mb_org_1 where sobid not in(select objid from mb_org_1) and stat2=1;";
	   	  	$ds = $da->GetData("depart",$sql);
	   	  	for ( $i=0;$i< $ds["depart"]["recordcount"];$i++){
	   	  		$deptid = $ds["depart"]["rows"][$i]["deptid"];
	   	  		if ( $this->isAdd($da,$deptid)){
	   	  			$deptname = $ds["depart"]["rows"][$i]["deptname"];
	   	  			//添加we_sns数据
	   	  			array_push($sqls,"insert into we_department(eno,dept_id,dept_name,parent_dept_id,fafa_deptid)values(?,?,?,?,?)");
	   	  			array_push($paras,array((string)$eno,(string)$deptid,(string)$deptname,(string)$eno,(string)$root));
	   	  			//更改标志
	   	  			array_push($sqls_up,"update from mb_org_1 set stat2=3 where objid=?");
	   	  			array_push($paras_up,array((string)$deptid));
	   	  			//添加we_im数据
	   	  			array_push($sqls_im,"insert into im_base_dept(deptid,deptname,pid,noorder)values(?,?,?,0)");
	   	  			array_push($paras_im,array((string)$deptid,(string)$deptname,(string)$root));
	   	  		}
	   	  		if ( count($sqls_im)>50){
	   	  			$da->ExecSQLS($sqls,$paras); 			
	   	  			$da_im->ExecSQLS($sqls_im,$paras_im);
	   	  			$da->ExecSQLS($sqls_up,$paras_up);
	   	  			$sqls = array();
		   	      $sqls_im = array();
		   	      $paras = array();
		   	      $paras_im = array();
	   	  		}
	   	  	}
 	  			if ( count($sqls)>0){
 	  			  $da->ExecSQLS($sqls,$paras);
 	  		  }
 	  		  if ( count($sqls_im)>0){
 	  		  	$da_im->ExecSQLS($sqls_im,$paras_im);
 	  		  }
	  		  $sqls = array();
	   	    $sqls_im = array();
	   	    $paras = array();
	   	    $paras_im = array();
	  		  //添加一般部门数据--------------------------------------------------------------------
	  		  $sql = "select objid deptid,stext deptname,sobid parent_dept_id from mb_org_1 where stat2=1;";
	  		  $ds = $da->GetData("table",$sql);
	  		  for($i=0;$i< $ds["table"]["recordcount"];$i++){
	  		  	$row = $ds["table"]["rows"][$i];
	  		  	$deptid = $row["deptid"];
	  		  	$deptname = $row["deptname"];
	  		  	$parent_dpet_id = $row["parent_dept_id"];
	  		  	if ( $this->isAdd($da,$deptid)){
	  		  		//添加we_sns数据
	   	  			array_push($sqls,"insert into we_department(eno,dept_id,dept_name,parent_dept_id,fafa_deptid)values(?,?,?,?,?)");
	   	  			array_push($paras,array((string)$eno,(string)$deptid,(string)$deptname,(string)$parent_dpet_id,(string)$deptid));
	   	  			//添加we_im数据
	   	  			array_push($sqls_im,"insert into im_base_dept(deptid,deptname,pid,noorder)values(?,?,?,0)");
	   	  			array_push($paras_im,array((string)$deptid,(string)$deptname,(string)$parent_dpet_id));
	  		  	}
	  		  	if ( count($sqls)>0){
	  		  		$da->ExecSQLS($sqls,$paras);
	   	  			$da_im->ExecSQLS($sqls_im,$paras_im);
	   	  			$sqls = array();
		   	      $sqls_im = array();
		   	      $paras = array();
		   	      $paras_im = array();	   	  			
	  		  	}		  	
	  		  }
	  		  $sqls = array();
	   	    $sqls_im = array();
	   	    $paras = array();
	   	    $paras_im = array();
	   	    //删除数据部门------------------------------------------------------------------------
	   	    $sql = "select objid deptid from mb_org_1 where stat2=0;";
	   	    $ds = $da->GetData("table",$sql);
	   	    for($i=0;$i<$ds["table"]["recordcount"];$i++){
	   	    	 $deptid = $ds["table"]["rows"][$i]["deptid"];
	   	    	 //删除we_sns数据
	   	  		 array_push($sqls,"delete from we_department where dept_id=?");
	   	  		 array_push($paras,array((string)$deptid));
	   	  		 //删除we_im数据
	   	  		 array_push($sqls_im,"delete from im_base_dept where deptid=?");
	   	  		 array_push($paras_im,array((string)$deptid));
	   	  		 if ( count($sqls)>50){
	  		  		$da->ExecSQLS($sqls,$paras);	   	  			
	   	  			$da_im->ExecSQLS($sqls_im,$paras_im);
	   	  			$sqls = array();
		   	      $sqls_im = array();
		   	      $paras = array();
		   	      $paras_im = array();	   	  			
	  		  	}
	   	    }
	   	    if ( count($sqls)>0){
	   	    	$da->ExecSQLS($sqls,$paras);	   	  			
	   	  		$da_im->ExecSQLS($sqls_im,$paras_im);	
	   	    }
	   	    $sqls = array();
	   	    $paras = array();
	 	      $sqls_im = array();	 	      
	 	      $paras_im = array();
	 	      //更新部门数据-----------------------------------------------------------------------------------
	 	      $sql = "select objid deptid,stext deptname,sobid parent_dept_id from mb_org_1 where stat2=2;";
	   	    $ds = $da->GetData("table",$sql);
	   	    for($i=0;$i<$ds["table"]["recordcount"];$i++){
	   	    	 $deptid = $ds["table"]["rows"][$i]["deptid"];
	   	    	 $deptname = $ds["table"]["rows"][$i]["deptname"];
	   	    	 $parent_dept_id = $ds["table"]["rows"][$i]["parent_dept_id"];
	   	    	 //更新we_sns数据
	   	  		 array_push($sqls,"update we_department set dept_name=?,parent_dept_id=? where dept_id=?");
	   	  		 array_push($paras,array((string)$deptname,(string)$parent_dept_id,(string)$deptid));
	   	  		 //更新we_im数据
	   	  		 array_push($sqls_im,"update from im_base_dept set deptname=?,pid=? where deptid=?");
	   	  		 array_push($paras_im,array((string)$deptname,(string)$parent_dept_id,(string)$deptid));
	   	  		 if ( count($sqls)>0){
	   	  		 	 $da->ExecSQLS($sqls,$paras);	   	  			
	   	  			 $da_im->ExecSQLS($sqls_im,$paras_im);
	   	  			 $sqls = array();
		   	       $sqls_im = array();
		   	       $paras = array();
		   	       $paras_im = array();
	  		  	}		  	
	   	    }
	   	    if ( count($sqls)>0){
	   	    	$da->ExecSQLS($sqls,$paras);	   	  			
	   	  		$da_im->ExecSQLS($sqls_im,$paras_im);	
	   	    }
	   	    //更改表中的标志stat2为３
	   	    $sql = "update mb_org_1 set stat2=3 where stat2 in(0,1,2);";
	   	    $da->ExecSQL($sql);
	   	  }
	   }
	   catch(\Exception $e){
	   	 $this->get("logger")->err($e->getMessage());
	   	 $result = array("success"=>false,"msg"=>"更新部门数据失败！");
	   }
	   $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;
	 }
	 
	 //判断部门是否存在，不存在就允许添加
	 private function isAdd($da,$dept_id)
	 {
	 	  $sql = "select 1 from we_department where dept_id=?";
	 	  $ds = $da->GetData("table",$sql,array((string)$dept_id));	
	 	  if ( $ds && $ds["table"]["recordcount"]>0){
	 	  	return false;
	 	  }
	 	  else {
	 	  	return true;
	 	  }
	 }
}
