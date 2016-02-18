<?php
namespace Justsy\AdminAppBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Login\UserSession;

class SchoolController extends Controller
{    
    public function IndexAction()
    {
    	$initdata = $this->getInitData();
    	$date = $initdata["date"];
    	$staff = $initdata["staff"];
    	return $this->render('JustsyAdminAppBundle:HR:school.html.twig',array("report_date"=>$date,"staff"=>$staff));
    }

    //初始数据
    private function getInitData()
    {
    	$da = $this->get('we_data_access');
    	$sqls = array();
    	$sql = "select date_format(`date`,'%Y-%m-01') begindate,date_format(`date`,'%Y年%m月') reportdate from mb_school group by begindate order by begindate asc;";
    	array_push($sqls,$sql);
    	$sql = "select staffid,nick_name staff from mb_school a inner join we_staff b on a.staffid=b.login_account group by staffid order by nick_name asc;";
    	array_push($sqls,$sql);
    	$ds = $da->GetDatas(array("date","staff"),$sqls);
    	$date = array();
    	$staff = array();
    	if ( $ds && $ds["date"]["recordcount"]>0)
    	  $date = $ds["date"]["rows"];
    	if ( $ds && $ds["staff"]["recordcount"]>0)
    	  $staff = $ds["staff"]["rows"];
    	$result = array("date"=>$date,"staff"=>$staff);
    	return $result;
    }    
    
    //查看我的大学
    public function searchschoolAction()
    {
    	 $da = $this->get('we_data_access');
    	 $request = $this->getRequest();
    	 $begindate = $request->get("date");
    	 $staffid = $request->get("staffid");
    	 $title = $request->get("title");
    	 $pageindex = $request->get("pageindex");
    	 $record = $request->get("record");
       $limit = " limit ".(($pageindex - 1) * $record).",".$record;
    	 $web_url = $this->container->getParameter('FILE_WEBSERVER_URL');    	 
       $sql="select id,fileid,concat('$web_url',fileid) url,title,filename,date_format(date,'%Y-%m-%d %H:%i') as date,nick_name staff
             from mb_school a inner join we_staff b on staffid=login_account where 1=1 ";
       $condition = "";       
    	 $para = array();
    	 if (!empty($title)){
    	 	 $condition .= " and `title` like concat(?,'%')";
    	 	 array_push($para,(string)$title);
    	 }
    	 if (!empty($staffid)){
    	   $condition .=" and staffid=?";
    	   array_push($para,(string)$staffid);
    	 }
    	 if (!empty($begindate)){
    	 	 $enddate = date("Y-m-d",strtotime("+1months",strtotime($begindate)));
    	 	 $condition .=" and date between ? and ?";
    	 	 array_push($para,(string)$begindate);
    	 	 array_push($para,(string)$enddate);
    	 }
    	 //返回的数据参数
    	 $success = true;
       $data = array();
       $recordcount = 0;
       $msg = "";
       try
       {
       	 $ds = null;
	       if ( count($para)>0 ){
	       	 $sql .= $condition." order by date desc ".$limit;
	       	 $ds = $da->GetData("table",$sql,$para);
	       }
	       else{
	       	 $sql = $sql." order by date desc ".$limit;
	         $ds = $da->GetData("table",$sql);
	       }
	       if ($ds && $ds["table"]["recordcount"]>0)
         $data = $ds["table"]["rows"];
         if ( $pageindex == 1){
         	 $sql = "select count(*) recordcount from mb_school where 1=1 ".$condition;
         	 if ( count($para)>0)
         	   $ds = $da->GetData("record",$sql,$para);
         	 else
         	   $ds = $da->GetData("record",$sql);
         	 if ($ds && $ds["record"]["recordcount"]>0)
         	   $recordcount = $ds["record"]["rows"][0]["recordcount"];         	 
         }         
       }
       catch(\Execption $e){
       	 $this->get("logger")->err($e->getMessage());
       	 $msg = "数据查询错误！";
       	 $success = false;
       }
       $result = array("success"=>$success,"msg"=>$msg,"datasource"=>$data,"recordcount"=>$recordcount);
    	 $response = new Response(json_encode($result));
       $response->headers->set('Content-Type', 'text/json');
       return $response;
    }
        
    //上传报表文件详细
    public function searchdetailAction()
    {
    	 $da = $this->get('we_data_access');
    	 $request = $this->getRequest();
    	 $schoolid = $request->get("schoolid");
    	 $data = array();
    	 $success = true;
    	 $message = "";    	 
    	 $sql = "select level1,level2,level3,level4,leveltype type from mb_school_role where schoolid=?";
    	 try
    	 {
	    	 $ds = $da->GetData("table",$sql,array((string)$schoolid));
	    	 if ($ds && $ds["table"]["recordcount"]>0)
	    	   $data = $ds["table"]["rows"];
    	 }
    	 catch(Exception $e) {
    	 	 $this->get("logger")->err($e->getMessage());
    	 	 $success = false;
    	 	 $message = $e->getMessage();
    	 }
    	 $result = array("success"=>$success,"message"=>$message,"data"=>$data);
    	 $response = new Response(json_encode($result));
       $response->headers->set('Content-Type', 'text/json');
       return $response;
    }
    
    //上传报表文件并返回id值
	  public function uploadschoolAction()
    {
    	$da = $this->get('we_data_access');
    	$result = $this->getRequest();
			$success = true;
			$message = "";
			$fileid = "";$filename = "";$message="";
			$fileElementName = 'uploadrepeat';
			try
			{
				 $filename=$_FILES[$fileElementName]['name'];
				 $filesize=$_FILES[$fileElementName]['size'];
				 $filetemp=$_FILES[$fileElementName]['tmp_name'];				 
				 $fileid = $this->saveCertificate($filetemp,$filename);
				 if(empty($fileid)) {
					 $success = false;
					 $message = "文件上传失败！";
				 }
			}
			catch(\Exception $e){
			  $success = false;
				$message = $e->getMessage();
			}
			@unlink($_FILES[$fileElementName]);//删除文件
			$result = array("success"=>$success,"message"=>$message,"fileid"=>$fileid,"filename"=>$filename);
			$response = new Response(json_encode($result));
	    $response->headers->set('Content-Type', 'text/html');
	    return $response;
    }
    
	  //删除mongo文件
	  private function deleteFile($fileid)
		{
			 $result = true;
			 try
			 {
				 if (!empty($fileid)) {
			    	$dm = $this->get('doctrine.odm.mongodb.document_manager');
			      $doc = $dm->getRepository('JustsyMongoDocBundle:WeDocument')->find($fileid);
			      if(!empty($doc))
			        $dm->remove($doc);
			      $dm->flush();
			   }
		   }
		   catch(\Exception $e){
		   	 $result = false;
		   	 $this->get("logger")->err($e->getMessage());
		   }
		   return true;
		}
		 
	  //保存文件
	  protected function saveCertificate($filetemp,$filename)
	  {
	  	$result = array();
	  	try{
		  	$upfile = tempnam(sys_get_temp_dir(), "we");
		    unlink($upfile);
		    if(move_uploaded_file($filetemp,$upfile)){
			    $dm = $this->get('doctrine.odm.mongodb.document_manager'); 
			    $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
			    $doc->setName($filename);
			    $doc->setFile($upfile);
			    $dm->persist($doc);
			    $dm->flush();
			    $fileid = $doc->getId();
			    return $fileid;
			  }
			  else{
			  	return "";
			  }
		  }
		  catch(\Exception $e)
		  {
		  	$this->get('logger')->err($e);
		  	$result = array("fileid"=>"","filepath"=>"");
		  	return "";
		  }
	  }  
    
    //编辑或添加报表上传记录（包括报表允许访问人员范围）
    public function editschoolAction()
    {
    	 $da = $this->get('we_data_access');
    	 $request = $this->getRequest();
    	 $schoolid = $request->get("schoolid");
    	 $fileid = $request->get("fileid");
    	 $filename = $request->get("filename");
    	 $staffobj = $request->get("staff");
    	 $title = $request->get("title");
    	 $sql="";$para=array();
    	 $data = array();
    	 $id = "";
    	 if ( empty($schoolid)){ //添加
    	 	 $currUser = $this->get('security.context')->getToken();
		     $staffid = $currUser->getUser()->getUserName();
		     $id = SysSeq::GetSeqNextValue($da,"mb_content_publish","id");
    	 	 $sql="insert into mb_school(id,fileid,title,filename,date,staffid)values(?,?,?,?,now(),?)";
    	 	 $para = array((string)$id,(string)$fileid,(string)$title,(string)$filename,(string)$staffid);
    	 }
    	 else {
    	 	 //判断fileid和表中的fileid是否一致，如果不一致则删除原来的文件
  	 	 	 $sql ="select fileid from mb_school where id=?";
  	 	 	 $ds = $da->GetData("table",$sql,array((string)$schoolid));
  	 	 	 if ($ds && $ds["table"]["recordcount"]>0){
  	 	 	 	 $oldfileid = $ds["table"]["rows"][0]["fileid"];
  	 	 	 	 if ($oldfileid != $fileid){
	  	 	 	 	 $this->deleteFile($oldfileid);
	         }
  	 	 	 }
  	 	   $sql="update mb_school set fileid=?,title=?,filename=? where id=?";
  	 	   $para=array((string)$fileid,(string)$title,(string)$filename,(string)$schoolid);
    	 }
    	 $success = true;$message = "";
    	 if ( count($para)>0){
    	 	 try
    	 	 {
    	 	 	  $da->ExecSQL($sql,$para);
    	 	 } 
    	 	 catch(Exception $e){
    	 	 	 
    	 	 	 $this->get("logger")->err($e->getMessage());
    	 	 	 $succcess = false;
    	 	 	 $message = $e->getMessage();
    	 	 }
    	 }
    	 //保存用户权限
    	 if ($success){
    	 	 if (empty($schoolid))
    	 	 	 $success = $this->editSchool($da,false,$id,$staffobj);
    	 	 else
    	 	 	 $success = $this->editSchool($da,true,$schoolid,$staffobj);
    	 	 if ( !$success ){
    	 	 	 $message = "保存用户权限失败！";
    	 	 }
    	 }
    	 $result = array("success"=>$success,"message"=>$message);
    	 $response = new Response(json_encode($result));
       $response->headers->set('Content-Type', 'text/json');
       return $response;
    }
    
    //编辑我的大学关联子表(人员权限表)mb_school_role
    private function editSchool($da,$isedit,$schoolid,$staff)
    {
    	 $sqls = array();
    	 $paras = array();
    	 $sql="";
    	 $para = array();
    	 if ($isedit){
    	 	  $desql="delete from mb_school_role where schoolid=?";
    	 	  $depara = array((string)$schoolid);
    	 	  $da->ExecSQL($desql,$depara);    	 	  
    	 }
    	 $success = true;
    	 if ( !empty($staff)){    	 	  
    	    //组织机构
    	    if (isset($staff["zzjg"])){
		    	 	 foreach($staff["zzjg"] as $row){
		    	 	 	 $sql = "insert into mb_school_role(schoolid,level1,leveltype)values(?,?,1)";
		    	 	 	 $para = array((string)$schoolid,(string)$row);
		    	 	 	 array_push($sqls,$sql);
		    	 	 	 array_push($paras,$para);
		    	 	 }
	    	  }
	    	 //职级维度
	    	 if (isset($staff["zjwd"])){
	    	 	 foreach($staff["zjwd"] as $row){
	    	 	 	 $level1=null;$level2=null;$level3=null;
	    	 	 	 if (isset($row["zjlb"]))
	    	 	 	   $level1 = $row["zjlb"];
	    	 	 	 if (isset($row["glzj"]))
	    	 	 	   $level2 = $row["glzj"];
	    	 	 	 if (isset($row["ywzj"])){
	    	 	 	   $level3 = $row["ywzj"];
	    	 	 	 }
	    	 	 	 $sql="insert into mb_school_role(schoolid,level1,level2,level3,leveltype)values(?,?,?,?,2)";
	    	 	 	 $para = array((string)$schoolid,$level1,$level2,$level3);
	    	 	 	 array_push($sqls,$sql);
	    	 	 	 array_push($paras,$para);
	    	 	 }
	    	 }
	    	 //人员分类
	    	 if (isset($staff["ryfl"])){
	    	 	 $level_array = array();
	    	 	 foreach($staff["ryfl"] as $row){
	    	 	 	 $level1=null;$level2=null;$level3=null;$level4=null;
	    	 	 	 $obj =null;
	    	 	 	 if (isset($row["level1"]))
	    	 	 	   $level1 = $row["level1"];
	    	 	 	 if (isset($row["level2"])){
	    	 	 	   $level2 = $row["level2"];
	    	 	 	   $level_array = explode("-",$level2);
	    	 	 	   if ($level_array!=null && count($level_array)>0)
	    	 	 	     $level2 = end($level_array);
	    	 	 	 }
	    	 	 	 if (isset($row["level3"])){
	    	 	 	   $level3 = $row["level3"];
	    	 	 	   $level_array = explode("-",$level3);
	    	 	 	   if ($level_array!=null && count($level_array)>0)
	    	 	 	     $level3 = end($level_array);    	 	 	   
	    	 	 	 }
	    	 	 	 if (isset($row["level4"])){
	    	 	 	   $level4 = $row["level4"];
	    	 	 	   $level_array = explode("-",$level4);
	    	 	 	   if ($level_array!=null && count($level_array)>0)
	    	 	 	     $level4 = end($level_array);    	 	 	   
	    	 	 	 }
	    	 	 	 $sql="insert into mb_school_role(schoolid,level1,level2,level3,level4,leveltype)values(?,?,?,?,?,3)";
	    	 	 	 $para = array((string)$schoolid,$level1,$level2,$level3,$level4);
	    	 	 	 array_push($sqls,$sql);
	    	 	 	 array_push($paras,$para);
	    	 	 	 
	    	 	 } 
	    	 }
	    	 //员工号
	    	 if (isset($staff["ygh"])){
	    	 	 foreach($staff["ygh"] as $row){
	    	 	 	 $sql = "insert into mb_school_role(schoolid,level1,leveltype)values(?,?,4)";
	    	 	 	 $para = array((string)$schoolid,(string)$row);
	    	 	 	 array_push($sqls,$sql);
	    	 	 	 array_push($paras,$para);
	    	 	 }
	    	 }  	 
	   	   //排除员工号
	    	 if (isset($staff["noygh"])){
	    	 	 foreach($staff["noygh"] as $row){
	    	 	 	 $sql = "insert into mb_school_role(schoolid,level1,leveltype)values(?,?,5)";
	    	 	 	 $para = array((string)$schoolid,(string)$row);
	    	 	 	 array_push($sqls,$sql);
	    	 	 	 array_push($paras,$para);
	    	 	 }
	    	 }
    	 }    	 
    	 if (count($paras)>0){
    	 	 try
    	 	 {
    	 	   $da->ExecSQLS($sqls,$paras);
    	 	 }
    	 	 catch(Exception $e){
    	 	 	$this->get("logger")->err($e->getMessage());
    	 	 	 $success = false;
    	 	 }
    	 } 
    	 return $success;	 
    }
    
    //删除上传的报表文件
    public function deleteschoolAction()
    {
    	 $da = $this->get('we_data_access');
    	 $request = $this->getRequest();
    	 $schoolid = $request->get("schoolid");
    	 $fileid =   $request->get("fileid");
    	 $success = true;
    	 $message = "";
    	 try
    	 {
	    	 //删除报表文件
	    	 if ( !empty($fileid)){
	    	   $this->deleteFile($fileid);
		     }
		     //删除表文件
		     $sqls = array();
		     $paras = array();
		     $sql = "delete from mb_school where id=?";
		     $para = array((string)$schoolid);
		     array_push($sqls,$sql);
		     array_push($paras,$para);
		     $sql = "delete from mb_school_role where schoolid=?";
	       array_push($sqls,$sql);
		     array_push($paras,$para);
		     $da->ExecSQLS($sqls,$paras);
	     }
	     catch(Exception $e) {
    	 	 $success = false;
    	 	 $this->get("logger")->err($e->getMessage());
    	 	 $message="删除文件失败！";
    	 }
    	 $result = array("success"=>$success,"message"=>$message);
    	 $response = new Response(json_encode($result));
       $response->headers->set('Content-Type', 'text/json');
       return $response;
    }
    
    //客户端调用接口-------------------------------------------------------------------------------------------------------
    //查看报表文件接口
    public function Api_ViewSchoolAction()
    {
    	 $da = $this->get('we_data_access');
    	 $web_url = $this->container->getParameter('FILE_WEBSERVER_URL');
    	 //返回字段
    	 $returncode=ReturnCode::$SUCCESS;
    	 $msg = "";
    	 //取用户工员
    	 $user = $this->get('security.context')->getToken()->getUser()->getUserName();
    	 $staff = explode("@",$user);
    	 $work_num = "";
    	 if ( count($staff)>0)
    	   $work_num = $staff[0];
    	 $getsql = $this->getSql($work_num);
    	 $sql2 = $getsql["sql"];    	 
       $sql = "select id reportid,title,concat('$web_url',fileid) url,filename,date_format(date,'%Y-%m-%d %H:%i') as date,nick_name staff
               from mb_school a inner join we_staff b on staffid=login_account inner join (".$sql2.") school_role on a.id=school_role.schoolid ";
       $para = $getsql["para"];
       //排除员工号
       $condition = " where not exists (select 1 from mb_school_role where schoolid=a.id and level1=? and leveltype=5) ";
			 array_push($para,(string)$work_num);
       $sql .= $condition." order by id desc;";        
       $ds = null;
       $returndata = array();
       try
       {
	       if ( count($para)>0)
	       	 $ds = $da->GetData("table",$sql,$para);
	       else
	       	 $ds = $da->GetData("table",$sql);
	       $returndata = $ds["table"]["rows"];
       }
       catch (\Exception $e){
       	 $this->get("logger")->err($e->getMessage());
       	 $returncode=ReturnCode::$SYSERROR;
			   $msg="查看文件失败！";			   
       }
       $result = array("returncode"=>$returncode,"msg"=>$msg,"returndata"=>$returndata);
       $response = new Response(json_encode($result));
       $response->headers->set('Content-Type','text/json');
       return $response;
    }

		//获得人员范围
		//work_num：员工号
		private function getSql($work_num){
			$sql = "";
			$sqls = array();
			$paras = array();
			//选择全部组织机构时所以人员可见
			$eno = $this->get('security.context')->getToken()->getUser()->eno;
		  $eno = "v".$eno;		
			$sql = "select schoolid from mb_school_role where level1=? and leveltype=1 ";
			array_push($sqls,$sql);
			array_push($paras,(string)$eno);
			//组织机构
			$sql ="union select schoolid from mb_school_role a inner join mb_hr_7 b on level1=b.orgeh where zhr_pa903112=? and leveltype=1 ";
			array_push($sqls ,$sql);
			array_push($paras,(string)$work_num);
			//职级维度
			$sql = " union select schoolid from mb_hr_7 a inner join mb_school_role b on zhr_pa903101=level1 
			         where case when level2 is null then 1=1 else zhr_pa903102=level2 end and case when level3 is null then 1=1 else zhr_pa903113=level3 end and a.zhr_pa903112=? and leveltype=2 ";
			array_push($sqls ,$sql);
			array_push($paras,(string)$work_num);		
			//人员类别
			$sql = " union select schoolid from mb_hr_7 a inner join mb_school_role b on zhr_pa903116=level1 
			         where case when level2 is null then 1=1 else zhr_pa903117=level2 end and case when level3 is null then 1=1 else zhr_pa903118=level3 end 
			           and case when level4 is null then 1=1 else zhr_pa903119=level4 end and a.zhr_pa903112=? and leveltype=3 ";
			array_push($sqls ,$sql);
			array_push($paras,(string)$work_num);
			//员工号
			$sql = " union select schoolid from mb_school_role where level1=? and leveltype=4 ";
			array_push($sqls ,$sql);
			array_push($paras,(string)$work_num);
			$sql = null;
			if ( count($sqls)>0)
			  $sql = implode(" ",$sqls);		
			$result = array("sql"=> $sql,"para"=> $paras);
			return $result;
		}	
}