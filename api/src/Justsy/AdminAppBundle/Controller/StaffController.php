<?php
namespace Justsy\AdminAppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Login\UserProvider;
use Justsy\BaseBundle\Login\UserSession;
use Justsy\BaseBundle\DataAccess\SysSeq;

class StaffController extends Controller
{	
  public function IndexAction()
  {
    $result = $this->getStaffSh("2");
    $data = json_encode($result["data"]);   
  	return $this->render('JustsyAdminAppBundle:Basic:staff.html.twig',array("data"=>$data));
  }
  
  //-------------------------------------------相关接口部分-------------------------------------------
  //获得用户基本信息(按用户传入不同参数返回不同数据)
  public function getStaffinfoAction()
  {
  	 $this->get("logger")->err("------------begin time:------------".date("Y-m-d H:i:s"));
  	 $da = $this->get("we_data_access");
     $currUser = $this->get('security.context')->getToken();
     //取员工工号
		 $user = $currUser->getUser()->getUserName();
		 $staff = explode("@",$user);
		 $worknumber = $staff[0];		 
		 $request = $this->getRequest();
		 $type = $request->get("type");
		 $sql = "";
		 $para = array();
		 $data = array();
		 $code = "";$message  = "";
  	 switch($type)
  	 {
  	 	 case "gzjl": //工作经历
  	 	   $sql ="select c.* from mb_hr_2 c inner join mb_hr_7 b on b.sapid_num=c.sapid_num where zhr_pa903112=? order by begda asc;";
  	 	   break;
  	 	 case "htxx": //合同信息
  	 	   $sql ="select c.* from mb_hr_1 c inner join mb_hr_7 b on b.sapid_num=c.sapid_num where zhr_pa903112=? order by begda asc;";
  	 	   break;
  	 	 case "jyjl": //教育经历
  	 	   $sql ="select c.* from mb_hr_5 c inner join mb_hr_7 b on b.sapid_num=c.sapid_num where zhr_pa903112=? order by begda asc;";
  	 	   break;
  	 	 case "jjlxr": //紧急联系人
  	 	   $sql ="select c.* from mb_hr_8 c iner join mb_hr_7 b on b.sapid_num=c.sapid_num where zhr_pa903112=?";
  	 	   break;
  	 	 case "jbxx": //基本信息 
  	 	   $sql ="select c.* from mb_hr_6 c inner join mb_hr_7 b on b.sapid_num=c.sapid_num where zhr_pa903112=?;";
  	 	   break;
  	 }
  	 if ( !empty($sql) ){
  	 	 try {
  	 	   $ds = $da->GetData("table",$sql,array((string)$worknumber));
  	 	   if ( $ds && $ds["table"]["recordcount"]>0)
  	 	     $data = $ds["table"]["rows"];
  	 	   $code=ReturnCode::$SUCCESS;
		 	   $message = "操作成功！";
		 	 }
		 	 catch (Exception $e) {
  		   $code=ReturnCode::$SYSERROR;
  		   $message = $e->getMessage();
  	   }
		 	 
  	 }
  	 else{
  	 	 $code = ReturnCode::$OTHERERROR;
  	 	 $message  = "请传入正确的参数！";
  	 }
  	 $result = array("returnCode"=>$code,"message"=>$message,"list"=>$data); 
  	 $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;
  } 
  
  //人员信息接口
  public function updateStaffInfoAction(){  	
     $da = $this->get("we_data_access");
     $currUser = $this->get('security.context')->getToken();
		 $user = $currUser->getUser()->getUserName();
		 $request = $this->getRequest();
		 $sapid_num = $this->getSapid($da,$user); //获得用户sapid_num字段值
		 $gzjl = $request->get("gzjl");           //工作经历
		 $jyjl = $request->get("jyjl");           //教育经历
		 $jbxx = $request->get("jbxx");           //基本信息
		 $shgx = $request->get("shgx");           //社会关系		 
		 $result = null;
	   if ( !empty($gzjl) ){
	   	 if (gettype($gzjl)=="string")
	   	   $gzjl = json_decode($gzjl, true);
	   	 $result = $this->staff_gzjl($da,$gzjl,$sapid_num);
	   }
	   if ( !empty($jyjl) ) {
	   	 if ( gettype($jyjl)=="string")
	   	  $jyjl = json_decode($jyjl, true);
	   	 $result = $this->staff_jyjl($da,$jyjl,$sapid_num);
	   }
	   if ( !empty($jbxx) ) {	   	
	   	 if (gettype($jbxx)=="string")
	   	   $jbxx = json_decode($jbxx, true);
	   	 $result = $this->staff_jbxx($da,$jbxx,$sapid_num);
	   }
	   if ( !empty($shgx) ){
	   	 if ( gettype($shgx)=="string")
	   	   $shgx = json_decode($shgx, true);
	   	 $result = $this->staff_shgx($da,$shgx,$sapid_num);
	   }
	   $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;  
  }  
  
  //处理待审核处理的mb_hr_2数据记录(工作经历)
  private function staff_gzjl($da,$data,$sapid_num){  	
  	 $sqls = array();
  	 $paras = array();
  	 $groupid = $this->getGroupid();
  	 $code = ReturnCode::$OTHERERROR;
  	 $message  = "没有修改的数据记录";  	 
  	 
		 if ( $data != null && count($data)>0){
		 	 for($i=0;$i< count($data);$i++){
		 	 	 $id = $data[$i]["id"];
		 	 	 $begda = null;   //开始日期
		 	 	 if (isset($data[$i]["begda"]))
		 	 	   $begda = $data[$i]["begda"];
		 	 	 $endda = null;
		 	 	 if (isset($data[$i]["endda"]))
		 	 	   $endda = $data[$i]["endda"];  //结束日期
		 	 	 $arbgb = null;
		 	 	 if (isset($data[$i]["arbgb"]))
		 	 	   $arbgb = $data[$i]["arbgb"];  //工作单位
		 	 	 $ort01 = null;
		 	 	 if (isset($data[$i]["ort01"]))
		 	 	   $ort01 = $data[$i]["ort01"];  //城市
		 	 	 $zhr_pa002302 = null;
		 	 	 if (isset($data[$i]["zhr_pa002302"]))
		 	 	 	 $zhr_pa002302 = $data[$i]["zhr_pa002302"]; // 工作部门
		 	 	 $zhr_zw = null;
		 	 	 if (isset($data[$i]["zhr_zw"]))
		 	 	   $zhr_zw = $data[$i]["zhr_zw"];            // 工作职务
		 	 	 $zhr_zmr = null;
		 	 	 if (isset($data[$i]["zhr_zmr"]))
		 	 	   $zhr_zmr = $data[$i]["zhr_zmr"];          // 证明人
		 	 	 $zhr_dh = null;
		 	 	 if (isset($data[$i]["zhr_dh"]))
		 	 	   $zhr_dh = $data[$i]["zhr_dh"];           // 证明人联系电话		 	 	 
		 	 	 $sql = "";
		 	 	 $para = array();
		 	 	 $main_id = SysSeq::GetSeqNextValue($da,"mb_hr_sh_main","id");
		 	 	 $temp="";
		 	 	 if ( empty($id) || $id=="0"){
		 	 	 	 //添加日志记录子表
		 	 	 	 if ( !empty($begda)){
		 	 	 	 	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'开始日期',?)";
 	 	 	 	 	  	$para = array((string)$main_id,(string)$begda);
 	 	 	 	 	  	array_push($sqls,$sql);
 	 	 	 	 	  	array_push($paras,$para);
		 	 	 	 }
		 	 	 	 if ( !empty($endda)){
		 	 	 	 	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'结束日期',?)";
 	 	 	 	 	  	$para = array((string)$main_id,(string)$endda);
 	 	 	 	 	  	array_push($sqls,$sql);
 	 	 	 	 	  	array_push($paras,$para);
		 	 	 	 }		 	
		 	 	 	 if ( !empty($arbgb)){
		 	 	 	 	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'工作单位',?)";
 	 	 	 	 	  	$para = array((string)$main_id,(string)$arbgb);
 	 	 	 	 	  	array_push($sqls,$sql);
 	 	 	 	 	  	array_push($paras,$para);
		 	 	 	 }
		 	 	 	 if ( !empty($ort01)){
		 	 	 	 	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'工作城市',?)";
 	 	 	 	 	  	$para = array((string)$main_id,(string)$ort01);
 	 	 	 	 	  	array_push($sqls,$sql);
 	 	 	 	 	  	array_push($paras,$para);
		 	 	 	 }
		 	 	 	 if ( !empty($zhr_pa002302)){
		 	 	 	 	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'工作部门',?)";
 	 	 	 	 	  	$para = array((string)$main_id,(string)$zhr_pa002302);
 	 	 	 	 	  	array_push($sqls,$sql);
 	 	 	 	 	  	array_push($paras,$para);
		 	 	 	 }
		 	 	 	 if ( !empty($zhr_zw)){
		 	 	 	 	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'工作职务',?)";
 	 	 	 	 	  	$para = array((string)$main_id,(string)$zhr_zw);
 	 	 	 	 	  	array_push($sqls,$sql);
 	 	 	 	 	  	array_push($paras,$para);
		 	 	 	 }
		 	 	 	 if ( !empty($zhr_zmr)){
		 	 	 	 	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'证明人',?)";
 	 	 	 	 	  	$para = array((string)$main_id,(string)$zhr_zmr);
 	 	 	 	 	  	array_push($sqls,$sql);
 	 	 	 	 	  	array_push($paras,$para);
		 	 	 	 }
		 	 	 	 if ( !empty($zhr_dh)){
		 	 	 	 	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'证明人联系电话',?)";
 	 	 	 	 	  	$para = array((string)$main_id,(string)$zhr_dh);
 	 	 	 	 	  	array_push($sqls,$sql);
 	 	 	 	 	  	array_push($paras,$para);
		 	 	 	 }		 	 	 	 
			 	 	 //添加数据记录
			 	 	 $sql2 = "insert into mb_hr_2(sapid_num,begda,endda,arbgb,ort01,zhr_pa002302,zhr_zw,zhr_zmr,zhr_dh)values(?,?,?,?,?,?,?,?,?);";
			 	 	 $para2 = array($sapid_num,$begda,$endda,$arbgb,$ort01,$zhr_pa002302,$zhr_zw,$zhr_zmr,$zhr_dh);
			 	 	 $da->ExecSQL($sql2,$para2);
			 	 	 //获得最新id
			 	 	 $sql2 = "select last_insert_id() id;";
			 	 	 $ds2 = $da->GetData("table",$sql2);
			 	 	 if ( $ds2 && $ds2["table"]["recordcount"]>0)
			 	 	 {
			 	 	 	 $groupid = $ds2["table"]["rows"][0]["id"];
			 	 	   //添加一条日志记录
		 	 	 	   $sql="insert into mb_hr_sh_main(id,groupid,tablename,hr_id,sapid_num,dj_date,status)values(?,?,'mb_hr_2',0,?,now(),0)";
		 	 	 	   $para = array((string)$main_id,(string)$groupid,(string)$sapid_num);
		 	 	 	   array_push($sqls,$sql);
		 	 	 	   array_push($paras,$para); 	 		
		 	 	   } 	 			 	 	  	 	 			 	 	 		 	 	 
		 	   }
		 	 	 else{
		 	 	 	 $sql = "select sapid_num,begda,endda,arbgb,ort01,zhr_pa002302,zhr_zw,zhr_zmr,zhr_dh from mb_hr_2 where id=?";		 	 	 	 
		 	 	 	 $ds = $da->GetData("table",$sql,array((string)$id));
		 	 	 	 if ( $ds && $ds["table"]["recordcount"]>0){
		 	 	 	 	 $temp = $ds["table"]["rows"][0]["begda"]; 
 	 	 	 	 	   if ( $begda != null && date("Y-m-d",strtotime($begda)) != date("Y-m-d",strtotime($temp))){
 	 	 	 	 	  	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'开始日期',?)";
 	 	 	 	 	  	  $para = array((string)$main_id,(string)$begda);
 	 	 	 	 	  	  array_push($sqls,$sql);
 	 	 	 	 	  	  array_push($paras,$para);
 	 	 	 	 	   }
 	 	 	 	 	   $temp = $ds["table"]["rows"][0]["endda"];
 	 	 	 	 	   if ( $endda != null && date("Y-m-d",strtotime($endda))!=date("Y-m-d",strtotime($temp))){
 	 	 	 	 	  	 $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'结束日期',?)";
 	 	 	 	 	  	 $para = array((string)$main_id,(string)$endda);
 	 	 	 	 	  	 array_push($sqls,$sql);
 	 	 	 	 	  	 array_push($paras,$para);
 	 	 	 	 	   }
 	 	 	 	 	   $temp = $ds["table"]["rows"][0]["arbgb"];
 	 	 	 	 	   if ( $arbgb != null && $arbgb != $temp ){
 	 	 	 	 	  	 $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'工作单位',?)";
 	 	 	 	 	  	 $para = array((string)$main_id,(string)$arbgb);
 	 	 	 	 	  	 array_push($sqls,$sql);
 	 	 	 	 	  	 array_push($paras,$para);		 	 	 	 	 	     
 	 	 	 	 	   }
 	 	 	 	 	   $temp = $ds["table"]["rows"][0]["ort01"];
 	 	 	 	 	   if ( $ort01 != null && $ort01 != $temp ){
 	 	 	 	 	  	 $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'城市',?)";
 	 	 	 	 	  	 $para = array((string)$main_id,(string)$ort01);
 	 	 	 	 	  	 array_push($sqls,$sql);
 	 	 	 	 	  	 array_push($paras,$para);		 	 	 	 	 	     
 	 	 	 	 	   }	 	 	 	 	 	  	 	 	 	 	  
 	 	 	 	 	   $temp = $ds["table"]["rows"][0]["zhr_pa002302"];
 	 	 	 	 	   if ( $zhr_pa002302 != null && $zhr_pa002302 != $temp ){
 	 	 	 	 	  	 $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'工作部门',?)";
 	 	 	 	 	  	 $para = array((string)$main_id,(string)$zhr_pa002302);
 	 	 	 	 	  	 array_push($sqls,$sql);
 	 	 	 	 	  	 array_push($paras,$para);		 	 	 	 	 	     
 	 	 	 	 	   }
 	 	 	 	 	   $temp = $ds["table"]["rows"][0]["zhr_zw"];
 	 	 	 	 	   if ( $zhr_zw != null && $zhr_zw != $temp ){
 	 	 	 	 	  	 $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'工作职务',?)";
 	 	 	 	 	  	 $para = array((string)$main_id,(string)$zhr_zw);
 	 	 	 	 	  	 array_push($sqls,$sql);
 	 	 	 	 	  	 array_push($paras,$para);		 	 	 	 	 	     
 	 	 	 	 	   }
 	 	 	 	 	   $temp = $ds["table"]["rows"][0]["zhr_zmr"];
 	 	 	 	 	   if ( $zhr_zmr != null && $zhr_zmr != $temp ){
 	 	 	 	 	  	 $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'证明人',?)";
 	 	 	 	 	  	 $para = array((string)$main_id,(string)$zhr_zmr);
 	 	 	 	 	  	 array_push($sqls,$sql);
 	 	 	 	 	  	 array_push($paras,$para);		 	 	 	 	 	     
 	 	 	 	 	   }
 	 	 	 	 	   $temp = $ds["table"]["rows"][0]["zhr_dh"];
 	 	 	 	 	   if (  $zhr_dh != null && $zhr_dh != $temp ){
 	 	 	 	 	  	 $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'证明人联系电话',?)";
 	 	 	 	 	  	 $para = array((string)$main_id,(string)$zhr_dh);
 	 	 	 	 	  	 array_push($sqls,$sql);
 	 	 	 	 	  	 array_push($paras,$para);		 	 	 	 	 	     
 	 	 	 	 	   }
			 	 	   if ( count($para) >0){ //表示有修改内容
			 	 	   	 $sql = "update mb_hr_2 set begda=?,endda=?,arbgb=?,ort01=?,zhr_pa002302=?,zhr_zw=?,zhr_zmr=?,zhr_dh=? where id=?";
			 	 	   	 $para = array($begda,$endda,$arbgb,$ort01,$zhr_pa002302,$zhr_zw,$zhr_zmr,$zhr_dh,$id);
			 	 	   	 $da->ExecSQL($sql,$para);			 	 	   	 
			 	 	 	   $sql="insert into mb_hr_sh_main(id,`groupid`,tablename,hr_id,sapid_num,dj_date,status)values(?,?,'mb_hr_2',?,?,now(),0)";
			 	 	 	   $para = array((string)$main_id,(string)$groupid,(string)$id,(string)$sapid_num);
			 	 	 	   array_push($sqls,$sql);
			 	 	 	   array_push($paras,$para);
			 	 	   }
		 	 	 	 } 
		 	 	 }
		 	 }
		 	 if ( count($sqls)>0){
		 	 	 try{
		 	 	 	 $code=ReturnCode::$SUCCESS;
		 	 	 	 $message = "操作成功！";
		 	 	   $da->ExecSQLS($sqls,$paras);
		 	 	 }
		 	 	 catch (Exception $e) {
  		     $code=ReturnCode::$OTHERERROR;
  		     $message = "操作失败！";
  	     }
		   }  
		 }
		 $result = array("ReturnCode"=>$code,"message"=>$message);
		 return $result;		 	
  }
  
  //处理待审核处理的mb_hr_5数据记录(教育经历)
  private function staff_jyjl($da,$data,$sapid_num){
  	 $sqls = array();
  	 $paras = array();
  	 $code = ReturnCode::$OTHERERROR;
  	 $message  = "没有修改的数据记录";
  	 $groupid = $this->getGroupid();
		 if ( $data != null && count($data)>0){
		 	 for($i=0;$i< count($data);$i++){
		 	 	 $id = $data[$i]["id"];		 	 	 
		 	 	 $begda = null;  //开始日期
		 	 	 if (isset($data[$i]["begda"]))
		 	 	 	 $begda = $data[$i]["begda"];		 	 	 
		 	 	 $endda = null;    //结束日期
		 	 	 if (isset($data[$i]["endda"]))
		 	 	   $endda = $data[$i]["endda"];
		 	 	 $zhr_xl = null;   //学历
		 	 	 if (isset($data[$i]["zhr_xl"]))
		 	 	   $zhr_xl = $data[$i]["zhr_xl"];
		 	 	 $zhr_xl_d = null; //学历文本
		 	 	 if (isset($data[$i]["zhr_xl_d"]))
		 	 	   $zhr_xl_d = $data[$i]["zhr_xl_d"];
		 	 	 $zhr_xx = null;    //学校
		 	 	 if (isset( $data[$i]["zhr_xx"]))
		 	 	   $zhr_xx = $data[$i]["zhr_xx"];
		 	 	 $zhr_zy = null;   // 专业
		 	 	 if (isset($data[$i]["zhr_zy"]))
		 	 	   $zhr_zy = $data[$i]["zhr_zy"];
		 	 	 $zhr_mbzy = null; // 美邦专业属性
		 	 	 if (isset($data[$i]["zhr_mbzy"]))
		 	 	   $zhr_mbzy = $data[$i]["zhr_mbzy"]; 
		 	 	 $zhr_zd = null;  // 是否在读
		 	 	 if (isset($data[$i]["zhr_zd"]))
		 	 	   $zhr_zd = $data[$i]["zhr_zd"];
		 	 	 $zhr_zgxl = null;   // 最高学历
		 	 	 if (isset($data[$i]["zhr_zgxl"]))
		 	 	   $zhr_zgxl = $data[$i]["zhr_zgxl"];
		 	 	 $zhr_bz = null;     // 备注	
		 	 	 if (isset($data[$i]["zhr_bz"]))
		 	 	  $zhr_bz = $data[$i]["zhr_bz"];	 	 	 		 	 	 
		 	 	 $sql = "";
		 	 	 $para = array();
		 	 	 $main_id = SysSeq::GetSeqNextValue($da,"mb_hr_sh_main","id");
		 	 	 $temp="";
		 	 	 if ( empty($id) || $id=="0"){
		 	 	 	 //添加日志记录子表
		 	 	 	 if ( !empty($begda)){
		 	 	 	 	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'开始日期',?)";
 	 	 	 	 	  	$para = array((string)$main_id,(string)$begda);
 	 	 	 	 	  	array_push($sqls,$sql);
 	 	 	 	 	  	array_push($paras,$para);
		 	 	 	 }
		 	 	 	 if ( !empty($endda)){
		 	 	 	 	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'结束日期',?)";
 	 	 	 	 	  	$para = array((string)$main_id,(string)$endda);
 	 	 	 	 	  	array_push($sqls,$sql);
 	 	 	 	 	  	array_push($paras,$para);
		 	 	 	 }		 	 
		 	 	 	 if ( !empty($zhr_xl)){
		 	 	 	 	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'学历',?)";
 	 	 	 	 	  	$para = array((string)$main_id,(string)$zhr_xl);
 	 	 	 	 	  	array_push($sqls,$sql);
 	 	 	 	 	  	array_push($paras,$para);
		 	 	 	 }
		 	 	 	 if ( !empty($zhr_xl_d)){
		 	 	 	 	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'学历文本',?)";
 	 	 	 	 	  	$para = array((string)$main_id,(string)$zhr_xl_d);
 	 	 	 	 	  	array_push($sqls,$sql);
 	 	 	 	 	  	array_push($paras,$para);
		 	 	 	 }
		 	 	 	 if ( !empty($zhr_xx)){
		 	 	 	 	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'学校',?)";
 	 	 	 	 	  	$para = array((string)$main_id,(string)$zhr_xx);
 	 	 	 	 	  	array_push($sqls,$sql);
 	 	 	 	 	  	array_push($paras,$para);
		 	 	 	 }
		 	 	 	 if ( !empty($zhr_zy)){
		 	 	 	 	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'专业',?)";
 	 	 	 	 	  	$para = array((string)$main_id,(string)$zhr_zy);
 	 	 	 	 	  	array_push($sqls,$sql);
 	 	 	 	 	  	array_push($paras,$para);
		 	 	 	 }	
		 	 	 	 if ( !empty($zhr_mbzy)){
		 	 	 	 	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'美邦专业',?)";
 	 	 	 	 	  	$para = array((string)$main_id,(string)$zhr_mbzy);
 	 	 	 	 	  	array_push($sqls,$sql);
 	 	 	 	 	  	array_push($paras,$para);
		 	 	 	 }
		 	 	 	 if ( !empty($zhr_zd)){
		 	 	 	 	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'是否在读',?)";
 	 	 	 	 	  	$para = array((string)$main_id,(string)$zhr_zd);
 	 	 	 	 	  	array_push($sqls,$sql);
 	 	 	 	 	  	array_push($paras,$para);
		 	 	 	 }
		 	 	 	 if ( !empty($zhr_zgxl)){
		 	 	 	 	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'最高学历',?)";
 	 	 	 	 	  	$para = array((string)$main_id,(string)$zhr_zgxl);
 	 	 	 	 	  	array_push($sqls,$sql);
 	 	 	 	 	  	array_push($paras,$para);
		 	 	 	 }	
		 	 	 	 if ( !empty($zhr_bz)){
		 	 	 	 	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'备注',?)";
 	 	 	 	 	  	$para = array((string)$main_id,(string)$zhr_bz);
 	 	 	 	 	  	array_push($sqls,$sql);
 	 	 	 	 	  	array_push($paras,$para);
		 	 	 	 }
		 	 	 	 //添加一条数据记录
			 	 	 $sql2 = "insert into mb_hr_5(sapid_num,begda,endda,zhr_xl,zhr_xl_d,zhr_xx,zhr_zy,zhr_mbzy,zhr_zd,zhr_zgxl,zhr_bz)values(?,?,?,?,?,?,?,?,?,?,?)";
			 	 	 $para2 = array($sapid_num,$begda,$endda,$zhr_xl,$zhr_xl_d,$zhr_xx,$zhr_zy,$zhr_mbzy,$zhr_zd,$zhr_zgxl,$zhr_bz);
			 	 	 $da->ExecSQL($sql2,$para2);
			 	 	 //获得最新id
			 	 	 $sql2 = "select last_insert_id() id;";
			 	 	 $ds2 = $da->GetData("table",$sql2);
			 	 	 if ( $ds2 && $ds2["table"]["recordcount"]>0)
			 	 	 {
			 	 	 	 $groupid = $ds2["table"]["rows"][0]["id"];
			 	 	   //添加操作日志
			 	 	   $sql="insert into mb_hr_sh_main(id,groupid,tablename,hr_id,sapid_num,dj_date,status)values(?,?,'mb_hr_5',0,?,now(),0)";
			 	 	   $para = array((string)$main_id,(string)$groupid,(string)$sapid_num);
			 	 	   array_push($sqls,$sql);
			 	 	   array_push($paras,$para);
		 	 	   }
		 	   }
		 	 	 else{
		 	 	 	 //记录操作日志到表
		 	 	 	 $sql = "select begda,endda,zhr_xl,zhr_xl_d,zhr_xx,zhr_zy,zhr_mbzy,zhr_zd,zhr_zgxl,zhr_bz from mb_hr_5 where id=?";		 	 	 	 
		 	 	 	 $ds = $da->GetData("table",$sql,array((string)$id));
		 	 	 	 if ( $ds && $ds["table"]["recordcount"]>0){
		 	 	 	 	 $temp = $ds["table"]["rows"][0]["begda"];
 	 	 	 	 	   if ( $begda !=null  && date("Y-m-d",strtotime($begda))!=date("Y-m-d",strtotime($temp))){
 	 	 	 	 	  	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'开始日期',?)";
 	 	 	 	 	  	  $para = array((string)$main_id,(string)$begda);
 	 	 	 	 	  	  array_push($sqls,$sql);
 	 	 	 	 	  	  array_push($paras,$para);		 	 	 	 	 	     
 	 	 	 	 	   }
 	 	 	 	 	   $temp = $ds["table"]["rows"][0]["endda"];
 	 	 	 	 	   if ( $endda != null && date("Y-m-d",strtotime($endda))!=date("Y-m-d",strtotime($temp))){
 	 	 	 	 	  	 $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'结束日期',?)";
 	 	 	 	 	  	 $para = array((string)$main_id,(string)$endda);
 	 	 	 	 	  	 array_push($sqls,$sql);
 	 	 	 	 	  	 array_push($paras,$para);		 	 	 	 	 	     
 	 	 	 	 	   }
 	 	 	 	 	   $temp = $ds["table"]["rows"][0]["zhr_xl"];
 	 	 	 	 	   if ( $zhr_xl != null  && $zhr_xl != $temp ){
 	 	 	 	 	  	 $sql = "insert into mb_hr_sh_sub(main_id,field,val)values(?,'学历代码',?)";
 	 	 	 	 	  	 $para = array((string)$main_id,(string)$zhr_xl);
 	 	 	 	 	  	 array_push($sqls,$sql);
 	 	 	 	 	  	 array_push($paras,$para);
 	 	 	 	 	   }
 	 	 	 	 	   $temp = $ds["table"]["rows"][0]["zhr_xl"];
 	 	 	 	 	   if ( $zhr_xl_d != null && $zhr_xl_d != $temp ){
 	 	 	 	 	  	 $sql = "insert into mb_hr_sh_sub(main_id,field,val)values(?,'学历',?)";
 	 	 	 	 	  	 $para = array((string)$main_id,(string)$zhr_xl_d);
 	 	 	 	 	  	 array_push($sqls,$sql);
 	 	 	 	 	  	 array_push($paras,$para);
 	 	 	 	 	   } 	 	 
 	 	 	 	 	   $temp = $ds["table"]["rows"][0]["zhr_xx"];
 	 	 	 	 	   if ( $zhr_xx !=null && $zhr_xx != $temp ){
 	 	 	 	 	  	 $sql = "insert into mb_hr_sh_sub(main_id,field,val)values(?,'学校',?)";
 	 	 	 	 	  	 $para = array((string)$main_id,(string)$zhr_xx);
 	 	 	 	 	  	 array_push($sqls,$sql);
 	 	 	 	 	  	 array_push($paras,$para);	 	 	 	 	 	   	 
 	 	 	 	 	   }
 	 	 	 	 	   $temp = $ds["table"]["rows"][0]["zhr_zy"];
 	 	 	 	 	   if ( $zhr_zy != null && $zhr_zy != $temp ){
 	 	 	 	 	  	 $sql = "insert into mb_hr_sh_sub(main_id,field,val)values(?,'专业',?)";
 	 	 	 	 	  	 $para = array((string)$main_id,(string)$zhr_zy);
 	 	 	 	 	  	 array_push($sqls,$sql);
 	 	 	 	 	  	 array_push($paras,$para);	 	 	 	 	 	   	 
 	 	 	 	 	   }
 	 	 	 	 	   $temp = $ds["table"]["rows"][0]["zhr_mbzy"];
 	 	 	 	 	   if ( $zhr_mbzy != null && $zhr_mbzy != $temp ){
 	 	 	 	 	  	 $sql = "insert into mb_hr_sh_sub(main_id,field,val)values(?,'美邦专业',?)";
 	 	 	 	 	  	 $para = array((string)$main_id,(string)$zhr_mbzy);
 	 	 	 	 	  	 array_push($sqls,$sql);
 	 	 	 	 	  	 array_push($paras,$para);	 	 	 	 	 	   	 
 	 	 	 	 	   } 
 	 	 	 	 	   $temp = $ds["table"]["rows"][0]["zhr_zd"];
 	 	 	 	 	   if ( $zhr_zd != null && $zhr_zd != $temp){
 	 	 	 	 	  	 $sql = "insert into mb_hr_sh_sub(main_id,field,val)values(?,'是否在读',?)";
 	 	 	 	 	  	 $para = array((string)$main_id,(string)$zhr_zd);
 	 	 	 	 	  	 array_push($sqls,$sql);
 	 	 	 	 	  	 array_push($paras,$para);	 	 	 	 	 	   	 
 	 	 	 	 	   }
 	 	 	 	 	   $temp = $ds["table"]["rows"][0]["zhr_zgxl"];
 	 	 	 	 	   if ( $zhr_zgxl !=null && $zhr_zgxl != $temp ){
 	 	 	 	 	  	 $sql = "insert into mb_hr_sh_sub(main_id,field,val)values(?,'最高学历',?)";
 	 	 	 	 	  	 $para = array((string)$main_id,(string)$zhr_zgxl);
 	 	 	 	 	  	 array_push($sqls,$sql);
 	 	 	 	 	  	 array_push($paras,$para);	 	 	 	 	 	   	 
 	 	 	 	 	   }
 	 	 	 	 	   $temp = $ds["table"]["rows"][0]["zhr_bz"];
 	 	 	 	 	   if ( $zhr_bz != null && $zhr_bz != $temp ){
 	 	 	 	 	  	 $sql = "insert into mb_hr_sh_sub(main_id,field,val)values(?,'备注信息',?)";
 	 	 	 	 	  	 $para = array((string)$main_id,(string)$zhr_bz);
 	 	 	 	 	  	 array_push($sqls,$sql);
 	 	 	 	 	  	 array_push($paras,$para);	 	 	 	 	 	   	 
 	 	 	 	 	   }
			 	 	   if ( count($para) >0){
			 	 	   	 //记录到日志主表
			 	 	 	   $sql="insert into mb_hr_sh_main(id,groupid,tablename,hr_id,sapid_num,dj_date,status)values(?,?,'mb_hr_5',?,?,now(),0)";
			 	 	 	   $para = array((string)$main_id,(string)$groupid,(string)$id,(string)$sapid_num);
			 	 	 	   array_push($sqls,$sql);
			 	 	 	   array_push($paras,$para);
			 	 	 	   //修改mb_hr_5表数据记录
			 	 	 	   $sql = "update mb_hr_5 set begda=?,endda=?,zhr_xl=?,zhr_xl_d=?,zhr_xx=?,zhr_zy=?,zhr_mbzy=?,zhr_zd=?,zhr_zgxl=?,zhr_bz=? where id=?";
			 	 	 	   $para = array($begda,$endda,$zhr_xl,$zhr_xl_d,$zhr_xx,$zhr_zy,$zhr_mbzy,$zhr_zd,$zhr_zgxl,$zhr_bz,$id);
			 	 	 	   array_push($sqls,$sql);
			 	 	 	   array_push($paras,$para);
			 	 	   } 	 	 	 	 	   
		 	 	 	 }
		 	 	 }
		 	 }		 	 
		 	 if ( count($sqls)>0){
		 	 	 try{
		 	 	 	 $code=ReturnCode::$SUCCESS;
		 	 	 	 $message = "操作成功！";
		 	 	   $da->ExecSQLS($sqls,$paras);
		 	 	 }
		 	 	 catch (Exception $e) {
  		     $code=ReturnCode::$OTHERERROR;
  		     $message = $e->getMessage();
  	     }
		   }  
		 }
		 $result = array("ReturnCode"=>$code,"message"=>$message);
		 return $result;		 	
  }
  
  //直接修改mb_hr_8数据记录(社会关系表)
  private function staff_shgx($da,$data,$sapid_num){
  	 $sqls = array();
  	 $paras = array();
  	 $code = ReturnCode::$OTHERERROR;
  	 $message  = "没有修改的数据记录";
		 if ( $data != null && count($data)>0){
		 	 for($i=0;$i< count($data);$i++){
		 	 	 $id = $data[$i]["id"];
		 	 	 $zhr_pa902301 = null;
		 	 	 if (isset($data[$i]["zhr_pa902301"]))
		 	 	   $zhr_pa902301 = $data[$i]["zhr_pa902301"];     //姓名
		 	 	 $zhr_pa902302 = null;
		 	 	 if (isset($data[$i]["zhr_pa902302"]))
		 	 	   $zhr_pa902302 = $data[$i]["zhr_pa902302"];     //关系
		 	 	 $zhr_pa902302_d = null;
		 	 	 if (isset($data[$i]["zhr_pa902302_d"]))
		 	 	   $zhr_pa902302_d = $data[$i]["zhr_pa902302_d"]; //关系文本说明
		 	 	 $zhr_pa902303 = null;
		 	 	 if (isset($data[$i]["zhr_pa902303"]))
		 	 	   $zhr_pa902303 = $data[$i]["zhr_pa902303"];     //电话
		 	 	 $zhr_pa902304 = null;
		 	 	 if (isset($data[$i]["zhr_pa902304"]))
		 	 	   $zhr_pa902304 = $data[$i]["zhr_pa902304"];     //手机
		 	 	 $zhr_pa902305 = null;
		 	 	 if (isset($data[$i]["zhr_pa902305"]))
		 	 	    $zhr_pa902305 = $data[$i]["zhr_pa902305"];     //地址
		 	 	 $zhr_pa902306 = null;
		 	 	 if (isset($data[$i]["zhr_pa902306"]))
		 	 	   $zhr_pa902306 = $data[$i]["zhr_pa902306"];     //邮编
		 	 	 $sql = ""; $para = array();
		 	 	 $fields = array();
		 	 	 $values = array();
		 	 	 $temp = "";
		 	 	 if ( empty($id) || $id=="0"){		 	 	 	
		 	 	 	 $sql = "insert into mb_hr_8(sapid_num,zhr_pa902301,zhr_pa902302,zhr_pa902302_d,zhr_pa902303,zhr_pa902304,zhr_pa902305,zhr_pa902306)values(?,?,?,?,?,?,?,?)";
		 	 	 	 $para = array($sapid_num,$zhr_pa902301,$zhr_pa902302,$zhr_pa902302_d,$zhr_pa902303,$zhr_pa902304,$zhr_pa902305,$zhr_pa902306);
		 	 	 	 array_push($sqls,$sql);
		 	 	 	 array_push($paras,$para);
		 	   }
		 	 	 else{
		 	 	 	 $sql = "select * from mb_hr_8 where id=?";
		 	 	 	 $ds = $da->GetData("table",$sql,array($id));
		 	 	 	 if ( $ds && $ds["table"]["recordcount"]>0){
		 	 	 	 	 $temp = $ds["table"]["rows"][0]["zhr_pa902301"];
 	 	 	 	 	   if ( $zhr_pa902301 != null && $zhr_pa902301 != $temp ){
 	 	 	 	 	      array_push($fields,"zhr_pa902301=?");
 	 	 	 	 	      array_push($para,(string)$zhr_pa902301);
 	 	 	 	 	   }
		 	 	 	 	 $temp = $ds["table"]["rows"][0]["zhr_pa902302"];
 	 	 	 	 	   if ( $zhr_pa902302 != null && $zhr_pa902302 != $temp ){
 	 	 	 	 	      array_push($fields,"zhr_pa902302=?");
 	 	 	 	 	      array_push($para,(string)$zhr_pa902302);
 	 	 	 	 	   }
		 	 	 	 	 $temp = $ds["table"]["rows"][0]["zhr_pa902302_d"];
 	 	 	 	 	   if ( $zhr_pa902302_d != null && $zhr_pa902302_d != $temp ){
 	 	 	 	 	      array_push($fields,"zhr_pa902302_d=?");
 	 	 	 	 	      array_push($para,(string)$zhr_pa902302_d);
 	 	 	 	 	   } 
		 	 	 	 	 $temp = $ds["table"]["rows"][0]["zhr_pa902303"];
 	 	 	 	 	   if ( $zhr_pa902303 != null && $zhr_pa902303 != $temp ){
 	 	 	 	 	      array_push($fields,"zhr_pa902303=?");
 	 	 	 	 	      array_push($para,(string)$zhr_pa902303);
 	 	 	 	 	   }
		 	 	 	 	 $temp = $ds["table"]["rows"][0]["zhr_pa902304"];
 	 	 	 	 	   if ( $zhr_pa902304 != null && $zhr_pa902304 != $temp ){
 	 	 	 	 	      array_push($fields,"zhr_pa902304=?");
 	 	 	 	 	      array_push($para,(string)$zhr_pa902304);
 	 	 	 	 	   }
		 	 	 	 	 $temp = $ds["table"]["rows"][0]["zhr_pa902305"];
 	 	 	 	 	   if ( $zhr_pa902305 != null && $zhr_pa902305 != $temp ){
 	 	 	 	 	      array_push($fields,"zhr_pa902305=?");
 	 	 	 	 	      array_push($para,(string)$zhr_pa902305);
 	 	 	 	 	   }
		 	 	 	 	 $temp = $ds["table"]["rows"][0]["zhr_pa902306"];
 	 	 	 	 	   if ( $zhr_pa902306 != null && $zhr_pa902306 != $temp ){
 	 	 	 	 	      array_push($fields,"zhr_pa902306=?");
 	 	 	 	 	      array_push($para,(string)$zhr_pa902306);
 	 	 	 	 	   }
 	 	 	 	 	   if (count($para)>0){
 	 	 	 	 	   	 $field = implode(",",$fields);
 	 	 	 	 	   	 $sql = "update mb_hr_8 set $field where id=?";
 	 	 	 	 	   	 array_push($para,(string)$id);
 	 	 	 	 	   	 array_push($sqls,$sql);
 	 	 	 	 	   	 array_push($paras,$para);
 	 	 	 	 	   }
		 	 	 	 }
		 	 	 }
		 	 }
		 	 if ( count($sqls)>0){
		 	 	 try{
		 	 	 	 $code=ReturnCode::$SUCCESS;
		 	 	 	 $message = "操作成功！";
		 	 	   $da->ExecSQLS($sqls,$paras);
		 	 	 }
		 	 	 catch (Exception $e) {
  		     $code=ReturnCode::$SYSERROR;
  		     $message = "操作失败！";
  	     }
		   }
		 }
		 $result = array("returncode"=>$code,"message"=>$message);
		 return $result;	
  }
  
  //mb_hr_6数据记录操作
  private function staff_jbxx($da,$data,$sapid_num){
  	 $sqls = array();
  	 $paras = array();
  	 $sqls2 = array();
  	 $paras2 = array();
  	 $code = ReturnCode::$OTHERERROR;
  	 $groupid = $this->getGroupid();
  	 $message  = "没有修改的数据记录";
		 if ( $data != null && count($data)>0){
		 	 foreach($data as $row){
		 	 	 $id = $row["id"];
		 	 	 $para = array();
		 	 	 $fields = array();
		 	 	 $values = array();
		 	 	 $sql = "";
		 	 	 $field="";
		 	 	 $value="";
		 	 	 //可直接修改的字段
		 	 	 $vorna = isset($row["vorna"]) ? $row["vorna"]:null; //英文名
		 	 	 $zhr_pa000212 = isset( $row["zhr_pa000212"]) ?  $row["zhr_pa000212"]:null; //政治面貌
		 	 	 $zhr_pa000212_d = isset($rows["zhr_pa000212_d"]) ? $rows["zhr_pa000212_d"]:null; //政治面貌文本
	 	 	   $zhr_xx = isset($row["zhr_xx"]) ? $row["zhr_xx"] :null;                 //血型
	 	 	   $zhr_xx_d = isset($row["zhr_xx_d"]) ? $row["zhr_xx_d"]:null;             //血型文本
	 	 	   $zhr_pa902201 = isset($row["zhr_pa902201"]) ? $row["zhr_pa902201"] :null;     //公司内部手机号码
		 	 	 $zhr_pa902202 = isset($row["zhr_pa902202"])? $row["zhr_pa902202"]:null;     //公司内部固定电话
		 	 	 $zhr_pa902203 = isset($row["zhr_pa902203"]) ? $row["zhr_pa902203"]:null;   //公司外部手机号码
		 	 	 $zhr_pa902204 = isset($row["zhr_pa902204"]) ? $row["zhr_pa902204"]:null;     //公司外部固定电话
		 	 	 $zhr_pa902205 = isset($row["zhr_pa902205"]) ? $row["zhr_pa902205"]:null;     //电子邮件		 	 	 
		 	 	 //需要审核的字段
		 	 	 $famst = isset($row["famst"]) ? $row["famst"] : null; //婚姻状况
		 	 	 $famst_d = isset($row["famst_d"]) ? $row["famst_d"] : null;         //婚姻状况文本
		 	 	 $famdt = isset($row["famdt"]) ? $row["famdt"] : null;           //婚姻状况始于		 	 	 
		 	 	 $anzkd = isset( $row["anzkd"])? $row["anzkd"] : null; //子女数目
		 	 	 $zhr_pa000204 = isset($row["zhr_pa000204"]) ? $row["zhr_pa000204"]: null; //户籍省
		 	 	 $zhr_pa000204_d = isset($row["zhr_pa000204_d"]) ? $row["zhr_pa000204_d"] : null; //户籍省文本
		 	 	 $zhr_hjd = isset($row["zhr_hjd"]) ? $row["zhr_hjd"]:null; //户籍地址
		 	 	 $zhr_hjyb = isset($row["zhr_hjyb"]) ? $row["zhr_hjyb"]:null; //户籍省邮编
		 	 	 $zhr_pa000205 = isset($row["zhr_pa000205"]) ? $row["zhr_pa000205"]:null; //是否农业户口
		 	 	 $zhr_pa000209 = isset($row["zhr_pa000209"]) ? $row["zhr_pa000209"]: null; //最高学历
		 	 	 $zhr_pa000209_d = isset($row["zhr_pa000209_d"]) ? $row["zhr_pa000209_d"]:null; //最高学历文本
		 	 	 $zhr_pa000210 = isset($row["zhr_pa000210"]) ? $row["zhr_pa000210"]:null; //最高学历毕业学校
		 	 	 $zhr_pa000211 = isset($row["zhr_pa000211"]) ? $row["zhr_pa000211"]:null; //最高学历毕业专业
		 	 	 $zhr_mz = isset($row["zhr_mz"]) ? $row["zhr_mz"]:null; //民族
		 	 	 $zhr_mz_d = isset($row["zhr_mz_d"]) ? $row["zhr_mz_d"]:null; //民族文本
		 	 	 $icnum1 = isset($row["icnum1"]) ? $row["icnum1"]:null; //身份证号码
		 	 	 $main_id = SysSeq::GetSeqNextValue($da,"mb_hr_sh_main","id");
		 	 	 if ( empty($id) || $id=="0"){
		 	 	 	 //添加日志记录子表
		 	 	 	 if ( !empty($famst)){
		 	 	 	 	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'婚姻状况',?)";
 	 	 	 	 	  	$para = array((string)$main_id,(string)$famst);
 	 	 	 	 	  	array_push($sqls,$sql);
 	 	 	 	 	  	array_push($paras,$para);
		 	 	 	 }
		 	 	 	 if ( !empty($famst_d)){
		 	 	 	 	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'婚姻状况文本',?)";
 	 	 	 	 	  	$para = array((string)$main_id,(string)$famst_d);
 	 	 	 	 	  	array_push($sqls,$sql);
 	 	 	 	 	  	array_push($paras,$para);
		 	 	 	 }
		 	 	 	 if ( !empty($famdt)){
		 	 	 	 	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'婚姻状况始于',?)";
 	 	 	 	 	  	$para = array((string)$main_id,(string)$famdt);
 	 	 	 	 	  	array_push($sqls,$sql);
 	 	 	 	 	  	array_push($paras,$para);
		 	 	 	 }
		 	 	 	 if ( !empty($anzkd)){
		 	 	 	 	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'子女数目',?)";
 	 	 	 	 	  	$para = array((string)$main_id,(string)$anzkd);
 	 	 	 	 	  	array_push($sqls,$sql);
 	 	 	 	 	  	array_push($paras,$para);
		 	 	 	 }
		 	 	 	 if ( !empty($zhr_pa000204_d)){
		 	 	 	 	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'户籍省份',?)";
 	 	 	 	 	  	$para = array((string)$main_id,(string)$zhr_pa000204_d);
 	 	 	 	 	  	array_push($sqls,$sql);
 	 	 	 	 	  	array_push($paras,$para);
		 	 	 	 }		
		 	 	 	 if ( !empty($zhr_hjd)){
		 	 	 	 	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'户籍地址',?)";
 	 	 	 	 	  	$para = array((string)$main_id,(string)$zhr_hjd);
 	 	 	 	 	  	array_push($sqls,$sql);
 	 	 	 	 	  	array_push($paras,$para);
		 	 	 	 }	
		 	 	 	 if ( !empty($zhr_hjyb)){
		 	 	 	 	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'户籍省邮编',?)";
 	 	 	 	 	  	$para = array((string)$main_id,(string)$zhr_hjyb);
 	 	 	 	 	  	array_push($sqls,$sql);
 	 	 	 	 	  	array_push($paras,$para);
		 	 	 	 }	
		 	 	 	 if ( !empty($zhr_pa000205)){
		 	 	 	 	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'是否农业户口',?)";
 	 	 	 	 	  	$para = array((string)$main_id,(string)$zhr_pa000205);
 	 	 	 	 	  	array_push($sqls,$sql);
 	 	 	 	 	  	array_push($paras,$para);
		 	 	 	 }
		 	 	 	 if ( !empty($zhr_pa000209_d)){
		 	 	 	 	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'最高学历',?)";
 	 	 	 	 	  	$para = array((string)$main_id,(string)$zhr_pa000209_d);
 	 	 	 	 	  	array_push($sqls,$sql);
 	 	 	 	 	  	array_push($paras,$para);
		 	 	 	 } 	 	 	  	 	 	 		 	 	 	 	 	 
		 	 	 	 if ( !empty($zhr_pa000209)){
		 	 	 	 	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'最高学历',?)";
 	 	 	 	 	  	$para = array((string)$main_id,(string)$zhr_pa000209);
 	 	 	 	 	  	array_push($sqls,$sql);
 	 	 	 	 	  	array_push($paras,$para);
		 	 	 	 }
		 	 	 	 if ( !empty($zhr_pa000210)){
		 	 	 	 	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'最高学历毕业学校',?)";
 	 	 	 	 	  	$para = array((string)$main_id,(string)$zhr_pa000210);
 	 	 	 	 	  	array_push($sqls,$sql);
 	 	 	 	 	  	array_push($paras,$para);
		 	 	 	 }
		 	 	 	 if ( !empty($zhr_pa000211)){
		 	 	 	 	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'最高学历毕业专业',?)";
 	 	 	 	 	  	$para = array((string)$main_id,(string)$zhr_pa000211);
 	 	 	 	 	  	array_push($sqls,$sql);
 	 	 	 	 	  	array_push($paras,$para);
		 	 	 	 }		 	
		 	 	 	 if ( !empty($zhr_mz_d)){
		 	 	 	 	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'民族',?)";
 	 	 	 	 	  	$para = array((string)$main_id,(string)$zhr_mz_d);
 	 	 	 	 	  	array_push($sqls,$sql);
 	 	 	 	 	  	array_push($paras,$para);
		 	 	 	 }
		 	 	 	 if ( !empty($icnum1)){
		 	 	 	 	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'身份证号码',?)";
 	 	 	 	 	  	$para = array((string)$main_id,(string)$icnum1);
 	 	 	 	 	  	array_push($sqls,$sql);
 	 	 	 	 	  	array_push($paras,$para);
		 	 	 	 }
		 	 	 	 
		 	 	 	 $sql2 = "insert into mb_hr_6(sapid_num,vorna,zhr_pa000212,zhr_pa000212_d,zhr_xx,zhr_xx_d,famst,famst_d,famdt,anzkd,zhr_pa000204,zhr_pa000204_d,zhr_hjd,zhr_hjyb,zhr_pa000205,zhr_pa000209,zhr_pa000209_d,zhr_pa000210,zhr_pa000211,zhr_mz,zhr_mz_d,icnum1,zhr_pa902201,zhr_pa902202,zhr_pa902203,zhr_pa902204,zhr_pa902205)values
		 	 	 	        (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
		 	 	 	 $para2 = array($sapid_num,$vorna,$zhr_pa000212,$zhr_pa000212_d,$zhr_xx,$zhr_xx_d,$famst,$famst_d,$famdt,$anzkd,$zhr_pa000204,$zhr_pa000204_d,$zhr_hjd,$zhr_hjyb,$zhr_pa000205,
		 	 	 	               $zhr_pa000209,$zhr_pa000209_d,$zhr_pa000210,$zhr_pa000211,$zhr_mz,$zhr_mz_d,$icnum1,$zhr_pa902201,$zhr_pa902202,$zhr_pa902203,$zhr_pa902204,$zhr_pa902205);
		 	 	 	 $da->ExecSQL($sql2,$para2);		 	 	 	 
		 	 	 	 //获得最新id
			 	 	 $sql2 = "select last_insert_id() id;";
			 	 	 $ds2 = $da->GetData("table",$sql2);
			 	 	 if ( $ds2 && $ds2["table"]["recordcount"]>0) {
			 	 	 	 $groupid = $ds2["table"]["rows"][0]["id"];
			 	 	   //添加一条日志记录
		 	 	 	   $sql="insert into mb_hr_sh_main(id,groupid,tablename,hr_id,sapid_num,dj_date,status)values(?,?,'mb_hr_6',0,?,now(),0)";
		 	 	 	   $para = array((string)$main_id,(string)$groupid,(string)$sapid_num);
		 	 	 	   array_push($sqls,$sql);
		 	 	 	   array_push($paras,$para); 	 		
		 	 	   }		 	 	 	 
		 	   }
		 	 	 else{
		 	 	 	 //需要审核的个人信息
		 	 	 	 $sql = "select * from mb_hr_6 where id=?";
		 	 	 	 $ds = $da->GetData("table",$sql,array((string)$id));
		 	 	 	 if ( $ds && $ds["table"]["recordcount"]>0){
		 	 	 	 	 $temp = $ds["table"]["rows"][0]["famst"];
 	 	 	 	 	   if ( $famst != $temp ){
 	 	 	 	 	  	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'婚姻状态',?)";
 	 	 	 	 	  	  $para = array((string)$main_id,(string)$famst);
 	 	 	 	 	  	  array_push($sqls2,$sql);
 	 	 	 	 	  	  array_push($paras2,$para);		 	 	 	 	 	     
 	 	 	 	 	   }
		 	 	 	 	 $temp = $ds["table"]["rows"][0]["famst_d"];
 	 	 	 	 	   if ( $famst_d != $temp ){
 	 	 	 	 	  	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'婚姻状态文本',?)";
 	 	 	 	 	  	  $para = array((string)$main_id,(string)$famst_d);
 	 	 	 	 	  	  array_push($sqls2,$sql);
 	 	 	 	 	  	  array_push($paras2,$para);		 	 	 	 	 	     
 	 	 	 	 	   }
		 	 	 	 	 $temp = $ds["table"]["rows"][0]["famdt"];
 	 	 	 	 	   if ( $famdt != $temp ){ 	 	 	 	 	  	  
 	 	 	 	 	  	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'婚姻状况始于',?)";
 	 	 	 	 	  	  $para = array((string)$main_id,(string)$famdt);
 	 	 	 	 	  	  array_push($sqls2,$sql);
 	 	 	 	 	  	  array_push($paras2,$para);		 	 	 	 	 	     
 	 	 	 	 	   }
		 	 	 	 	 $temp = $ds["table"]["rows"][0]["anzkd"];
 	 	 	 	 	   if ( $anzkd != $temp ){
 	 	 	 	 	  	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'子女数目',?)";
 	 	 	 	 	  	  $para = array((string)$main_id,(string)$anzkd);
 	 	 	 	 	  	  array_push($sqls2,$sql);
 	 	 	 	 	  	  array_push($paras2,$para);
 	 	 	 	 	   }
		 	 	 	 	 $temp = $ds["table"]["rows"][0]["zhr_pa000204"];
 	 	 	 	 	   if ( $zhr_pa000204 != $temp ){
 	 	 	 	 	  	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'户籍省份Code',?)";
 	 	 	 	 	  	  $para = array((string)$main_id,(string)$zhr_pa000204);
 	 	 	 	 	  	  array_push($sqls2,$sql);
 	 	 	 	 	  	  array_push($paras2,$para);		 	 	 	 	 	     
 	 	 	 	 	   }
		 	 	 	 	 $temp = $ds["table"]["rows"][0]["zhr_pa000204_d"];
 	 	 	 	 	   if ( $zhr_pa000204_d != $temp ){
 	 	 	 	 	  	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'户籍省份',?)";
 	 	 	 	 	  	  $para = array((string)$main_id,(string)$zhr_pa000204_d);
 	 	 	 	 	  	  array_push($sqls2,$sql);
 	 	 	 	 	  	  array_push($paras2,$para);
 	 	 	 	 	   }  	 	 
		 	 	 	 	 $temp = $ds["table"]["rows"][0]["zhr_hjd"];
 	 	 	 	 	   if ( $zhr_hjd != $temp ){
 	 	 	 	 	  	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'户籍地址',?)";
 	 	 	 	 	  	  $para = array((string)$main_id,(string)$zhr_hjd);
 	 	 	 	 	  	  array_push($sqls2,$sql);
 	 	 	 	 	  	  array_push($paras2,$para);
 	 	 	 	 	   }   	 	 	 	 	
		 	 	 	 	 $temp = $ds["table"]["rows"][0]["zhr_hjyb"];
 	 	 	 	 	   if ( $zhr_hjyb != $temp ){
 	 	 	 	 	  	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'户籍邮编',?)";
 	 	 	 	 	  	  $para = array((string)$main_id,(string)$zhr_hjyb);
 	 	 	 	 	  	  array_push($sqls2,$sql);
 	 	 	 	 	  	  array_push($paras2,$para);
 	 	 	 	 	   } 
		 	 	 	 	 $temp = $ds["table"]["rows"][0]["zhr_pa000205"];
 	 	 	 	 	   if ( $zhr_pa000205 != $temp ){
 	 	 	 	 	  	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'是否农业户口',?)";
 	 	 	 	 	  	  $para = array((string)$main_id,(string)$zhr_pa000205);
 	 	 	 	 	  	  array_push($sqls2,$sql);
 	 	 	 	 	  	  array_push($paras2,$para);
 	 	 	 	 	   }    
		 	 	 	 	 $temp = $ds["table"]["rows"][0]["zhr_pa000209"];
 	 	 	 	 	   if ( $zhr_pa000209 != $temp ){
 	 	 	 	 	  	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'最高学历Code',?)";
 	 	 	 	 	  	  $para = array((string)$main_id,(string)$zhr_pa000209);
 	 	 	 	 	  	  array_push($sqls2,$sql);
 	 	 	 	 	  	  array_push($paras2,$para);
 	 	 	 	 	   }
		 	 	 	 	 $temp = $ds["table"]["rows"][0]["zhr_pa000209_d"];
 	 	 	 	 	   if ( $zhr_pa000209_d != $temp ){
 	 	 	 	 	  	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'最高学历',?)";
 	 	 	 	 	  	  $para = array((string)$main_id,(string)$zhr_pa000209_d);
 	 	 	 	 	  	  array_push($sqls2,$sql);
 	 	 	 	 	  	  array_push($paras2,$para);
 	 	 	 	 	   }
		 	 	 	 	 $temp = $ds["table"]["rows"][0]["zhr_pa000210"];
 	 	 	 	 	   if ( $zhr_pa000210 != $temp ){
 	 	 	 	 	  	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'最高学历毕业学校',?)";
 	 	 	 	 	  	  $para = array((string)$main_id,(string)$zhr_pa000210);
 	 	 	 	 	  	  array_push($sqls2,$sql);
 	 	 	 	 	  	  array_push($paras2,$para);
 	 	 	 	 	   }
		 	 	 	 	 $temp = $ds["table"]["rows"][0]["zhr_pa000211"];
 	 	 	 	 	   if ( $zhr_pa000211 != $temp ){
 	 	 	 	 	  	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'最高学历毕业专业',?)";
 	 	 	 	 	  	  $para = array((string)$main_id,(string)$zhr_pa000211);
 	 	 	 	 	  	  array_push($sqls2,$sql);
 	 	 	 	 	  	  array_push($paras2,$para);
 	 	 	 	 	   }
		 	 	 	 	 $temp = $ds["table"]["rows"][0]["zhr_mz"];
 	 	 	 	 	   if ( $zhr_mz != $temp ){
 	 	 	 	 	  	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'民族Code',?)";
 	 	 	 	 	  	  $para = array((string)$main_id,(string)$zhr_mz);
 	 	 	 	 	  	  array_push($sqls2,$sql);
 	 	 	 	 	  	  array_push($paras2,$para);
 	 	 	 	 	   } 	 	 	 	
		 	 	 	 	 $temp = $ds["table"]["rows"][0]["zhr_mz_d"];
 	 	 	 	 	   if ( $zhr_mz_d != $temp ){
 	 	 	 	 	  	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'民族',?)";
 	 	 	 	 	  	  $para = array((string)$main_id,(string)$zhr_mz_d);
 	 	 	 	 	  	  array_push($sqls2,$sql);
 	 	 	 	 	  	  array_push($paras2,$para);
 	 	 	 	 	   }
		 	 	 	 	 $temp = $ds["table"]["rows"][0]["icnum1"];
 	 	 	 	 	   if ( $icnum1 != $temp ){
 	 	 	 	 	  	  $sql = "insert into mb_hr_sh_sub(main_id,field_t,val)values(?,'身份证号码',?)";
 	 	 	 	 	  	  $para = array((string)$main_id,(string)$icnum1);
 	 	 	 	 	  	  array_push($sqls2,$sql);
 	 	 	 	 	  	  array_push($paras2,$para);
 	 	 	 	 	   }
 	 	 	 	 	  	   	 	 	 	   
		 	 	 	 }
	 	 	 	   if ( count($para) >0){  //表明至少有一条有修改，记录日志表
		 	 	   	 //记录到日志主表
		 	 	 	   $sql="insert into mb_hr_sh_main(id,groupid,tablename,hr_id,sapid_num,dj_date,status)values(?,?,'mb_hr_6',?,?,now(),0)";
		 	 	 	   $para = array((string)$main_id,(string)$groupid,(string)$id,(string)$sapid_num);
		 	 	 	   array_push($sqls,$sql);
		 	 	 	   array_push($paras,$para);
		 	 	   } 
		 	 	 	 //以下字段不存日志
		 	 	 	 array_push($fields,"vorna=?");
		 	 	 	 array_push($para,$vorna);
		 	 	 	 array_push($fields,"zhr_pa000212=?");
		 	 	 	 array_push($para,$zhr_pa000212);
		 	 	 	 array_push($fields,"zhr_pa000212_d=?");
		 	 	 	 array_push($para,$zhr_pa000212_d);
		 	 	 	 array_push($fields,"zhr_xx=?");
		 	 	 	 array_push($para,$zhr_xx);		
		 	 	 	 array_push($fields,"zhr_xx_d=?");
		 	 	 	 array_push($para,$zhr_xx_d);	
		 	 	 	 array_push($fields,"zhr_pa902201=?");
		 	 	 	 array_push($para,$zhr_pa902201);
		 	 	 	 array_push($fields,"zhr_pa902202=?");
		 	 	 	 array_push($para,$zhr_pa902202);		
		 	 	 	 array_push($fields,"zhr_pa902203=?");
		 	 	 	 array_push($para,$zhr_pa902203);		
		 	 	 	 array_push($fields,"zhr_pa902204=?");
		 	 	 	 array_push($para,$zhr_pa902204);		
		 	 	 	 array_push($fields,"zhr_pa902205=?");
		 	 	 	 array_push($para,$zhr_pa902205);
		 	 	 	 //修改数据记录
			 	 	 $field = implode(",",$fields);
 	 	 	 	 	 $sql = "update mb_hr_6 set ".$field. " where id=?";
 	 	 	 	 	 array_push($para,(string)$id);
 	 	 	 	 	 array_push($sqls,$sql);
 	 	 	 	 	 array_push($paras,$para);
		 	 	 }
		 	 }
		 	 if ( count($paras)>0 ){
		 	 	 try{
		 	 	 	 $code=ReturnCode::$SUCCESS;
		 	 	 	 $message = "操作成功！";
		 	 	   $da->ExecSQLS($sqls,$paras);
		 	 	   $da->ExecSQLS($sqls2,$paras2);
		 	 	 }
		 	 	 catch (Exception $e) {
  		     $code=ReturnCode::$OTHERERROR;
  		     $message = "操作失败！";
  	     }
		   }
		 }
		 $result = array("ReturnCode"=>$code,"message"=>$message);
		 return $result;	
  }
  
  public function updateStatusAction(){
  	 $da = $this->get("we_data_access");
		 $request = $this->getRequest();
		 $groupid  = $request->get("groupid");
	 	 $currUser = $this->get('security.context')->getToken();
	   $user = $currUser->getUser()->getUserName();
	   
	   $sql = "update mb_hr_sh_main set sh_date=now(),`status`=1,staffid=? where groupid=?";
	   $para = array((string)$user,(string)$groupid);
	   $success = true;$message="";
	   try
	   {
	   	 $da->ExecSQL($sql,$para);
	   }
	   catch(Exception $e){
	   	 $success = false;
	   	 $message = $e->getMessage();
	   }
	   $result = array("success"=>$success,"message"=>$message);
	   $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;
  }
    
  //生成随机码
  private function getGroupid()
  {
  	$result = date("YmdHmi").rand(1000,9999);
  	return $result;
  }
  
  private function getMaxId($da,$tablename){
  	$maxid = "";
  	$sql = "select ifnull(max(id),0)+1 newid from ".$tablename;
  	$ds = $da->GetData("table",$sql);
  	if ($ds && $ds["table"]["recordcount"]>0)
  	  $maxid = $ds["table"]["rows"][0]["newid"];
  	return $maxid;
  }
  
  //根据用户账号获得sapid_num
  private function getSapid($da,$user){
  	$sapid = "";
  	$user = explode("@",$user);
  	$user = $user[0];
  	$sql = "select sapid_num  from mb_hr_7 where zhr_pa903112=? limit 1;";
  	$ds = $da->GetData("table",$sql,array((string)$user));
  	if ($ds && $ds["table"]["recordcount"]>0)
  	  $sapid = $ds["table"]["rows"][0]["sapid_num"];
    else if($ds && $ds["table"]["recordcount"]==0){  //如果mb_hr_7无记录则新加一条
  		$users = explode("@",$user);
  		if ( count($users)>0)
  		  $user = $users[0];
  	  $sapid =	$this->getGroupid();
  		$sql = "insert into mb_hr_7(sapid_num,zhr_pa903112)values(?,?)";
  		$da->ExecSQL($sql,array((string)$sapid,(string)$user));  		  		
  	}
  	return $sapid;
  }
  
  //查询用户审核信息
  public function searchStaffAction()
  {  	 
  	 $request = $this->getRequest();
  	 $status = $request->get("status");
     $result  = $this-> getStaffSh($status); 
  	 $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;  	
  }
  
  //获得审核数据
  private function getStaffSh($status)
  {
  	$da = $this->get("we_data_access");  	
  	$webservice_url = $this->container->getParameter('FILE_WEBSERVER_URL');
  	$sql = "select groupid,date_format(max(dj_date),'%Y-%m-%d %H:%i') date,ifnull((select nick_name from we_staff a inner join mb_hr_7 b on zhr_pa903112 = a.ldap_uid where b.sapid_num=c.sapid_num limit 1),'') nickname,
  	               (select case when ifnull(photo_path,'')='' then '' else concat('".$webservice_url."',photo_path) end from we_staff a inner join mb_hr_7 b on zhr_pa903112 = a.ldap_uid where b.sapid_num=c.sapid_num limit 1) head,
  	               ifnull((select zhr_pa903112 from mb_hr_7 dd where dd.sapid_num=c.sapid_num limit 1),'') sapid_num,status from mb_hr_sh_main c ";  	 
  	if ($status=="0" || $status=="1")
  	   $sql .=" where status=".$status;
  	$sql .=" group by groupid order by dj_date desc;";  	
  	$data = array();$success = true;$message = "";
  	try
  	{
  	   $ds = $da->GetData("table",$sql); 
  	   if ($ds && $ds["table"]["recordcount"]>0)
  	     $data = $ds["table"]["rows"];
  	}
  	catch (Exception $e) {
  	  $success = false;
  	  $message = $e->getMessage();
  	}
  	$result = array("success"=> $success,"message"=>$message,"data"=>$data);
  	return $result;
  }
  
  public function searchbyidAction()
  {
  	 $da = $this->get("we_data_access");
  	 $request = $this->getRequest();
  	 $groupid = $request->get("groupid");
  	 $sql = "select field_t,ifnull(val,'') val from mb_hr_sh_main a inner join mb_hr_sh_sub b on a.id=b.main_id where groupid=?";
  	 $data = array();$message = "";$success = true;
  	 try
  	 {
	  	 $ds = $da->GetData("table",$sql,array((string)$groupid)); 
	  	 if ($ds && $ds["table"]["recordcount"]>0){
	  	 	 $data = $ds["table"]["rows"];
	  	 }
  	 }
  	 catch( Exception $e) {
  		 $success = false;
  		 $message = $e->getMessage();
  	 }
  	 $result = array("success"=>$success,"message"=>$message,"data"=>$data);
     $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;
  }
  
  //取我的下属
  public function SubordinatesAction()
  {
  	 $da = $this->get("we_data_access");
  	 $currUser = $this->get('security.context')->getToken()->getUser();
		 $user = $currUser->getUserName();
		 $eno =$currUser->eno;
		 $account = explode("@",$user);
		 $user = $account[0];
		 $request = $this->getRequest();
		 $staff = $request->get("staff");
		 $code=ReturnCode::$SUCCESS;
	   $message = "";
	   $data = array();
		 $sql = "";
     $dept = array();
     try { 
	     	 $webservice_url = $this->container->getParameter('FILE_WEBSERVER_URL');
	     	 $condition = "";$para = array();
	     	 //获得当前人员负责的部门id(可能为多个)和sapid_num
	     	 $sql = "select objid,orgeh,sobjid from mb_hr_7 a inner join mb_org_3 b on a.sapid_num=b.sobjid where zhr_pa903112=?";
	     	 $ds = $da->GetData("table",$sql,array((string)$user));	     	 
	     	 if ( $ds==null || $ds["table"]["recordcount"]==0){
	     	 	   $result = array("returncode"=>ReturnCode::$SYSERROR,"message"=>"不是领导负责人，没有下属员工！","data"=>array(),"dept"=>null);
             $response = new Response(json_encode($result));
             $response->headers->set('Content-Type', 'text/json');
             return $response;
	     	 }
	     	 $orgids = array();
	     	 $orgid_condition = "";
	     	 $sapid= $ds["table"]["rows"][0]["sobjid"];
	     	 $org_staff = $ds["table"]["rows"][0]["orgeh"];	     	 
	     	 for($i=0; $i< $ds["table"]["recordcount"];$i++){
	     	 	  if ( $ds["table"]["rows"][$i]["objid"]==$org_staff) continue;
	     	 	  $orgid_condition .="?,";
	     	 	  array_push($orgids,$ds["table"]["rows"][$i]["objid"]);
	     	 }
	     	 $orgid_condition = rtrim($orgid_condition,",");
	     	 //获得人员
	     	 $staff_sql = "select null parent_dept_id,b.sapid_num,nachn name,case when ifnull(photo_path_big,'')='' then null else concat('".$webservice_url."',photo_path_big) end header,1 isstaff 
	     	               from mb_hr_7 a inner join mb_hr_6 b on a.sapid_num=b.sapid_num left join we_staff c on a.zhr_pa903112=c.ldap_uid 
	     	               where a.stat2=3 and a.sapid_num<>? and orgeh=?";
	     	 $para = array((string)$sapid,(string)$org_staff);
	     	 if ( !empty($staff)){
	     	 	  $condition = " and (b.nachn like concat(?,'%') or a.zhr_pa903112 like concat(?,'%')) ";
			 	  	array_push($para,(string)$staff,(string)$staff);
		 	   }
	       $staff_sql .= $condition;
	       //获得组织部门
	       $org_sql = "";
	       if (count($orgids)>0){
	         $condition = "";
		       $org_sql = " union select parent_dept_id,dept_id,dept_name,null header,0 isstaff  from we_department where dept_id in($orgid_condition)";
		       for($i=0;$i< count($orgids);$i++){
		       	 array_push($para,$orgids[$i]);
		       }		       
		       if( !empty($staff)){
		       	 $condition = "and dept_name like concat(?,'%')";
		       	 array_push($para,(string)$staff);
		       }
		       $org_sql = $org_sql.$condition;
	       }
	       $sql = $staff_sql.$org_sql." order by isstaff desc,parent_dept_id asc;";
	       $ds = $da->GetData("table",$sql,$para);
	       if ($ds && $ds["table"]["recordcount"]>0)
	         $data = $ds["table"]["rows"];
	       else{
	       	 $message = "未查询到数据记录！";
	       }
     }
     catch(\Exception $e){
     	 $code=ReturnCode::$SYSERROR;
     	 $message = $e->getMessage();
     }
     $result = array("returncode"=>$code,"message"=>$message,"data"=>$data,"dept"=>$dept );
     $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;
  }
  
  //获得我的下属中部门下人员信息或部门
  public function getStaffbydepartAction()
  {
  	 $da = $this->get("we_data_access");
		 $request = $this->getRequest();
		 $deptid = $request->get("deptid");
		 $staff  = $request->get("staff");
		 $code=ReturnCode::$SUCCESS;
	   $message = "";
	   $data = array();
     try{
	      $webservice_url = $this->container->getParameter('FILE_WEBSERVER_URL');
	      $condition = "";$para = array();
	      //取人员
	     	$sql = "select distinct b.sapid_num,nachn name,case when ifnull(photo_path_big,'')='' then null else concat('".$webservice_url."',photo_path_big) end header,1 isstaff 
	     	       from mb_hr_7 a inner join mb_hr_6 b on a.sapid_num=b.sapid_num left join we_staff c on a.zhr_pa903112=c.ldap_uid where a.stat2=3 ";
	     	if ( !empty($staff)){
		     	$condition = " and (b.nachn like concat(?,'%') or a.zhr_pa903112 like concat(?,'%')) ";
			 	  array_push($para,(string)$staff,(string)$staff);
		 	  }
		 	  $sql .= $condition." and orgeh=? ";
		 	  array_push($para,(string)$deptid);
		 	  //取部门
		 	  $condition = "";
		 	  $sql .= " union select dept_id,dept_name,null header,0 isstaff from we_department where 1=1 ";
		 	  if (!empty($staff)){
		 	  	 $condition = " and dept_name like concat(?,'%') ";
		 	  	 array_push($para,(string)$staff);
		 	  }
		 	  $sql .= $condition. " and parent_dept_id=? order by isstaff desc  ";
		 	  array_push($para,(string)$deptid);
        $ds = $da->GetData("table",$sql,$para);
        $data = $ds["table"]["rows"];
     }
     catch(Exception $e){
     	 $this->get("logger")->err($e->getMessage());
     	 $code=ReturnCode::$SYSERROR;
     	 $message = "数据查询失败！";
     }  
     $result = array("returncode"=>$code,"message"=>$message,"data"=>$data);
     $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;
  }
  
  //获得我的下属员工信息
  public function subordinatesinfoAction()
  {  	
  	 $da = $this->get("we_data_access");
  	 $request = $this->getRequest();
  	 $sapid = $request->get("sapid");
  	 $type  = $request->get("type");
  	 $data = array();
  	 $success = true;
  	 $message = "";
  	 $code=ReturnCode::$SUCCESS;
  	 if ( !empty($sapid) && !empty($type)){
  	 	  $sql = "";
  	 	  $para = array();
  	 	  switch($type){
  	 	  	case "jbxx":  //基本信息
  	 	  	  $sql = "select nachn,gesch,case gesch when 1 then '男' when 2 then '女' else null end gesch,gbdat,zhr_pa000204_d,zhr_pa000209_d,zhr_pa000212_d,zhr_mz_d,orgeh_d,stell_d,zhr_pa903112,zhr_pa903102_d,zhr_pa903113_d,zhr_pa903114,dat02,dat04
                    from mb_hr_6 a inner join mb_hr_7 b on a.sapid_num = b.sapid_num where a.sapid_num = ? order by zhr_pa903114 desc limit 1";
            $para = array((string)$sapid);
  	 	  	  break;
  	 	  	case "txfs": //通讯方式
  	 	  	  $sql = "select zhr_pa902201,zhr_pa902203 from mb_hr_6 a inner join mb_hr_7 b on a.sapid_num = b.sapid_num where a.sapid_num = ? order by zhr_pa903114 desc limit 1";
            $para = array((string)$sapid);
            break;
  	 	    case "jyjl": //教育经历
  	 	      $sql = "select begda,endda,zhr_xl_d,zhr_xx,zhr_zy from mb_hr_5 where sapid_num=? order by begda asc;";
  	 	      $para = array((string)$sapid);
  	 	      break;
  	 	    case "gzjl_in":  //工作经历
  	 	      $sql="select begda,endda,zhr_szbm,zhr_gzzw from mb_hr_3 where sapid_num=? order by begda asc;";
  	 	      $para = array((string)$sapid);
  	 	      break;
  	 	    case "gzjl_out":
  	 	      $sql = "select begda,endda,arbgb,zhr_pa002302,zhr_zw from mb_hr_2 where sapid_num=? order by begda asc;";
  	 	      $para = array((string)$sapid);
  	 	      break;
  	 	    case "jcxx": //奖惩信息
  	 	      $sql="select begda,zhr_jjdj_d,zhr_jlyy,zhr_cjdj_d,zhr_cjyy from mb_hr_4 where sapid_num=? ";
  	 	      $para = array((string)$sapid);
  	 	      break;
  	 	  }
  	 	  if( count($para)>0){
  	 	  	try
  	 	  	{
  	 	  	  $ds = $da->GetData("table",$sql,$para);  	 	  	  
  	 	  	  if ($ds && $ds["table"]["recordcount"]>0)
  	 	  	    $data = $ds["table"]["rows"];
  	 	  	  else{
  	 	  	  	$message = "获得信息为空！";  	 	  		
  	 	  	  }
  	 	    }
  	 	    catch(Exception $e){
  	 	    	$code = ReturnCode::$OTHERERROR;
  	 	    	$message = $e->getMessage();
  	 	    }
  	 	  }
  	 	  else{
  	 	    $code = ReturnCode::$SYSERROR;
  	 	    $message = "传入的type值错误！";  	 	    
  	 	  }
  	 }
  	 else{
  	 	 $code = ReturnCode::$OTHERERROR;
  	 	 if (empty($sapid))
  	 	   $message = "请传入人员sapid号!";
  	 	 if (empty($type))
  	 	   $message = "请传入获得下属成员信息type!";
  	 }
  	 $result = array("returncode"=>$code,"message"=>$message,"data"=>$data);
  	 $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;
  }  
}