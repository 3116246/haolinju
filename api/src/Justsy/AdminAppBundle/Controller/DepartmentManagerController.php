<?php
namespace Justsy\AdminAppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\DataAccess\SysSeq;
use PHPExcel;
use PHPExcel_IOFactory;
use Justsy\BaseBundle\Common\Cache_Enterprise;
use Justsy\BaseBundle\Common\SendMessage;

class DepartmentManagerController extends Controller
{
    public function IndexAction()
    {
        $request = $this->getRequest();
        $type = $request->get("type");
        if ( empty($type))
            return $this->render("JustsyAdminAppBundle:Basic:departmentManager.html.twig");
        else
            return $this->render("JustsyAdminAppBundle:Basic:department.html.twig");
    }
  
  //查询部名名称
  public function searchNameAction()
  {
  	 $da = $this->get('we_data_access');
  	 $eno = $this->get('security.context')->getToken()->getUser()->eno;
  	 $request = $this->getRequest();
  	 $name = $request->get("dept_name");
  	 $sql = "select dept_id as deptid,dept_name as deptname,fafa_deptid 
  	         from we_department where dept_name like concat('%',?,'%') and eno=?;";
  	 $data = array();
  	 $success = true;
  	 $msg = "";
  	 try
  	 {
  	 	 $ds = $da->GetData("table",$sql,array((string)$name,(string)$eno));
  	 	 $data = $ds["table"]["rows"];
  	 }
  	 catch(\Exception $e){
  	 	 $this->get("logger")->err($e->getMessage());
  	 	 $success = false;
  	 	 $msg = "查询失败，请重试！";
  	 	 $msg = $e->getMessage();  	 	 
  	 }  	
  	 $result = array("success"=>$success,"msg"=>$msg,"datasource"=>$data);
  	 $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;
  }
  
  //添加部门数据
  public function AddDepartmentAction()
  {
  	 $da    = $this->get("we_data_access");
  	 $da_im = $this->get('we_data_access_im');
  	 $request = $this->getRequest();
  	 $p_deptid = $request->get("p_deptid");
  	 $deptid = $request->get("deptid");
  	 $deptname = $request->get("deptname");
  	 $manager = $request->get("manager");
  	 $friend = $request->get("friend");
  	 $show   = $request->get("show");
  	 $parameter = array();$result = array();  	 
  	 //引用对象
  	 $deptMrg = new \Justsy\BaseBundle\Management\Dept($da,$da_im,$this->container);
  	 //所需参数
  	 $user = $this->get('security.context')->getToken()->getUser();
	   $parameter["deptname"] = $deptname;
  	 if ( empty($deptid)) //添加部门信息
  	 {
  	    $parameter["eno"] = $user->eno;
  	    $deptinfo = $this->getDeptInfo($p_deptid);
  	    $parameter["p_deptid"] = $deptinfo["deptid"];
  	    $parameter["manager"] = $manager;
  	    $parameter["friend"] = $friend;
  	    $parameter["show"] = $show;
  	    $result = $deptMrg->createDepartment($parameter);  	    
        $parameter = array("flag"=>"all","title"=>"createDept","message"=>json_encode($result["data"]),"container"=>$this->container);
  	 }
  	 else
  	 {
  	    $deptinfo = $this->getDeptInfo($deptid);
  	    $parameter["deptid"] = $deptinfo["deptid"];
  	    $deptinfo = $this->getDeptInfo($p_deptid);
  	    $parameter["p_deptid"] = $deptinfo["deptid"];
  	    $result = $deptMrg->updateDepartment($parameter);
        $parameter = array("flag"=>"all","title"=>"editDept","message"=>json_encode($result["data"]),"container"=>$this->container);
  	 }
     $sendMessage = new SendMessage($da,$da_im);
     $sendMessage->sendImMessage($parameter);
  	 $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;
  }
  
  //获得sns的部门信息
  private function getDeptInfo($deptid)
  {
    $result = array("deptid"=>"","parent_id"=>"");
    $da  = $this->get("we_data_access");
    $sql = "select dept_id,parent_dept_id from we_department where fafa_deptid=?;";
    $ds = $da->GetData("table",$sql,array((string)$deptid));
    if ( $ds && $ds["table"]["recordcount"]>0)
    {
      $result = array(
                       "deptid"=> $ds["table"]["rows"][0]["dept_id"],
                       "parent_id"=>$ds["table"]["rows"][0]["parent_dept_id"]
                     );
    }
    return $result;
  }
   
  //获得导入的Excel数据
  public function importExcelAction()
  {
    $request = $this->getRequest();  	
		$upfile = $request->files->get("filedata");
		$tmpPath = $upfile->getPathname();
		$filename = $upfile->getClientOriginalName();		
		$fixedType = explode(".",strtolower($filename));		
		$fixedType = $fixedType[count($fixedType)-1];		
		$newfile = $_SERVER['DOCUMENT_ROOT']."/upload/dept_".rand(10000,99999).".".$fixedType;
		$field_name = array();$field_value = array();
    $msg = "";$success = true;
    $recordcount = 0;
    $totalpage = 0;
    if(move_uploaded_file($tmpPath,$newfile)) {
	  	$re = $this->getExcelContent($newfile);
	  	$data = $re["data"];
	  	$recordcount = (int)$re["recordcount"];
	  	$totalpage = ceil($recordcount/100);
    }
    else{
    	 $this->get("logger")->err($e->getMessage());
    	 $msg = "文件上传错误！";
    	 $success = false;
    }    
    $result = array("success"=>$success,"msg"=>$msg,"DataSource"=>$data,"filepath"=>$newfile,"recordcount"=>$recordcount,"total_page"=>$totalpage);
		$response = new Response("<script>dept_import_callback(".json_encode($result).")</script>");		
    $response->headers->set('Content-Type', 'text/html');
    return $response;
  }  
  
  //根据文件名获得文件内容数据
  public function getExcelContent($filename)
  {
  	 $fixedType = explode(".",basename($filename));
  	 $fixedType = $fixedType[count($fixedType)-1];  	 
  	 $objReader = \PHPExcel_IOFactory::createReader( $fixedType=="xlsx"? 'Excel2007':"Excel5"); 
     $objPHPExcel = $objReader->load($filename);
		 $objWorksheet = $objPHPExcel->getActiveSheet();
     $totalrow = $objWorksheet->getHighestRow(); 
	   $highestColumn = $objWorksheet->getHighestColumn();
	   $totalcolumn = \PHPExcel_Cell::columnIndexFromString($highestColumn);//总列数
	   $data = array();
	   $total_row = $totalrow;
	   $totalrow = $totalrow>16 ? 16 : $totalrow;
	   for($rowindex=2;$rowindex<=$totalrow;$rowindex++)
	   {
	   	 $rowdata = array();
	   	 for($colindex=0;$colindex < $totalcolumn;$colindex++)
	   	 {
	   	 	 $name = $objWorksheet->getCellByColumnAndRow($colindex,1)->getValue();	   	 	 
	   	 	 $value = $objWorksheet->getCellByColumnAndRow($colindex,$rowindex)->getValue();
	   	 	 $rowdata[$name] = empty($value) ? "":$value;
	   	 }	   	 
	   	 array_push($data,$rowdata);
	   }
	   return array("data"=>$data,"recordcount"=>$total_row);
  }
  
  //导入部门数据信息
  public function ImportDataAction()
  {
  	 set_time_limit(300);
  	 $request = $this->getRequest();
  	 $Field = $request->get("relation");
  	 $datatype = (boolean)$request->get("datatype");
  	 $rootdeptid = $request->get("rootdeptid");
  	 $file = $request->get("file");
  	 $totalrecord = $request->get("totalrecord");
  	 $pageindex =   $request->get("index");
  	 $index = array();
  	 $val = $Field["dept_id"]["index"];
  	 if ( $val!="")
  	   $index["dept_id"] = $val;
  	 $val = $Field["dept_name"]["index"];
  	 if ( $val !="")
  	   $index["dept_name"] = $val;
  	 $val = $Field["parent_dept"]["index"];
  	 if ( $val!="" )
  	   $index["parent_dept"] = $val;
  	 $val = $Field["parent_dept_id"]["index"];
  	 if ( $val!="" )
  	   $index["parent_dept_id"] = $val;  	   	   
  	 $curUser = $this->get('security.context')->getToken()->getUser();
  	 $eno = $curUser->eno;
  	 $user = $curUser->getUserName();
  	 //返回的数据参数
  	 $result = array();
  	 $da = $this->get("we_data_access");
  	 //计算记录起始行号
     $startindex =0;$endindex = 0;
     $totalrecord  = empty($totalrecord ) ? 0 : (int)$totalrecord ;
  	 if ( $pageindex == 1){
  	    $startindex = 2;
  	    if ( $totalrecord < 100)
  	      $endindex = $totalrecord;
  	    else
  	      $endindex = 100;
  	 }
  	 else{
  	 	 	$startindex = 100 * ($pageindex-1) + 1;
  	 	 	$endindex   = $startindex + 100 - 1;
  	 	 	if ( $endindex>$totalrecord)
  	 	 	  $endindex = $totalrecord;
  	 }
  	 if ( $datatype )
  	   $result["errorData"] = $this->ImportDataByDeptName($eno,$user,$file,$index,$startindex,$endindex);
  	 else 
  	   $result["errorData"] = $this->ImportDataByDeptID($rootdeptid,$eno,$user,$file,$index,$startindex,$endindex);
  	 $result["index"] = $pageindex + 1;
  	 $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;
  }
  
  //统计IM部门人数
  public function statisticsAction()
  {
  	 $request = $this->getRequest();
  	 $file = $request->get("file");
  	 //删除文件
  	 if ( !empty($file) && file_exists($file)){
  	 	 unlink($file);
  	 }
  	 $da_im = $this->get('we_data_access_im');
  	 //企业号
  	 $eno = $this->get('security.context')->getToken()->getUser()->eno;  	 
  	 //统计人员
  	 $sql = " call dept_emp_stat(?);";
  	 $para = array((string)$eno);
  	 $success = true;
  	 $msg = "";
  	 try
  	 {
  	 	 $da_im->ExecSQL($sql,$para);
  	 }
  	 catch(\Exception $e){
  	 	 $msg = "操作失败！";
  	 	 $this->get("logger")->err($e->getMessage());
  	 	 $success = false;  	 	 
  	 }
  	 $result = array("success"=>$success,"msg"=>$msg);
  	 $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;
  }
  
  //导入部门数据（根据部门名称和上级部门名称)
  public function ImportDataByDeptName($eno,$user,$filename,$field,$starindex,$endindex)
  {  	
     $fixedType = explode(".",basename($filename));
  	 $fixedType = $fixedType[count($fixedType)-1];
  	 $objReader = \PHPExcel_IOFactory::createReader( $fixedType=="xlsx"? 'Excel2007':"Excel5"); 
     $objPHPExcel = $objReader->load($filename);
		 $objWorksheet = $objPHPExcel->getActiveSheet();
	   $sql = "";
	   $para = array();
	   $da = $this->get("we_data_access");
  	 $da_im = $this->get('we_data_access_im');
  	 $data = array();
  	 $curUser = $this->get('security.context')->getToken()->getUser();
  	 $eno = $curUser->eno;
	   while ($starindex <=$endindex)
	   {
	   	 	$dept_name   = $objWorksheet->getCellByColumnAndRow((int)$field["dept_name"],$starindex)->getValue();   //部门名称
	   	 	$parent_dept = $objWorksheet->getCellByColumnAndRow((int)$field["parent_dept"],$starindex)->getValue(); //上级部门名称
	   	 	$ids = $this->getDeptID($da,$eno,$dept_name);
	   	 	if ( empty($ids) )
  	 	 	{
  	 	 	 	 $dept_id  = SysSeq::GetSeqNextValue($da,"we_department","dept_id");
  	 	 	 	 $parent_dept_id = $this->getSNSParentId($da,$parent_dept);
  	 	 	 	 $fafa_deptid = SysSeq::GetSeqNextValue($da_im,"im_base_dept","deptid");
	  	 	 	 $sql = "insert into we_department(eno,dept_id,fafa_deptid,dept_name,parent_dept_id,create_staff)values(?,?,?,?,?,?)";
	  	 	 	 $para = array((string)$eno,(string)$dept_id,(string)$fafa_deptid,(string)$dept_name,(string)$parent_dept_id,(string)$user);
	  	 	 	 $da->ExecSQL($sql,$para);	  	 	 	 
	  	 	 	 //im部门表
	  	 	 	 $pid =  $this->getIMParentId($da_im,$parent_dept);
	  	 	 	 $sql = "insert into im_base_dept(deptid,deptname,pid,noorder)values(?,?,?,0)";
	  	 	 	 $para = array((string)$fafa_deptid,(string)$dept_name,(string)$pid);
	  	 	 	 $da_im->ExecSQL($sql,$para);  	 	 	 
	  	 	 	 //计算部门路径
	  	 	 	 $sql = "call p_reset_deptpath(?,?);";
			  	 $para = array((string)$eno,(string)$pid);			  	 
	  	 	 	 $da_im->ExecSQL($sql,$para);
  	 	 	}
  	 	 	else{
  	 	 	  array_push($data,"部门名称：\"".$dept_name."\"已存在！");
	  	 	}
	      $starindex = $starindex + 1;
     }     
	   return $data;
  }

  //按部门id导入数据
  public function ImportDataByDeptID($rootdeptid,$eno,$user,$filename,$field,$startindex,$endindex)
  {
  	 $logger = $this->get("logger");
  	 $fixedType = explode(".",basename($filename));
  	 $fixedType = $fixedType[count($fixedType)-1];  	 
  	 $objReader = \PHPExcel_IOFactory::createReader( $fixedType=="xlsx"? 'Excel2007':"Excel5"); 
     $objPHPExcel = $objReader->load($filename);
		 $objWorksheet = $objPHPExcel->getActiveSheet();
	   $path_sql = array();
	   $path_para = array();
	   $sql = "";
	   $para = array();
	   $da = $this->get("we_data_access");
  	 $da_im = $this->get('we_data_access_im');
  	 $data = array();
  	 $pids = array();  	 
  	 $rdeptid = $this->getRootdeptid();
  	 $sns_sql = array();
  	 $sns_para = array();
  	 $im_sql = array();
  	 $im_para = array();
  	 $path_sql = array();
  	 $path_para = array();
	   while ($startindex <=$endindex)
	   {	   	  
	   	 	$dept_id        = $objWorksheet->getCellByColumnAndRow((int)$field["dept_id"],$startindex)->getValue();
	   	 	$dept_name      = $objWorksheet->getCellByColumnAndRow((int)$field["dept_name"],$startindex)->getValue();
	   	 	$parent_dept_id = $objWorksheet->getCellByColumnAndRow((int)$field["parent_dept_id"],$startindex)->getValue();
	   	 	if ( $parent_dept_id == $rootdeptid)
	   	 	  $parent_dept_id = $rdeptid;
        //判断部门id是否存在
        //$val = Cache_Enterprise::get(Cache_Enterprise::$EN_DEPT,$dept_id);
        $exists =  $this->existsDeptId($da,$eno,$dept_id);
        if ( $exists )
        {
        	 array_push($data,"部门ID：\"".$dept_id."\"，　部门名称：\"".$dept_name."\"已经存在！");
        }
  	 	 	else{
  	 	 		 $fafa_deptid = "";
  	 	 		 if ( $parent_dept_id == $rdeptid ) 
  	 	 		   $fafa_deptid = "v".$eno;
  	 	 		 else
  	 	 		   $fafa_deptid = $parent_dept_id;
  	 	 	   //sns部门表
  	 	 	 	 $sql = "insert into we_department(eno,dept_id,dept_name,parent_dept_id,create_staff,fafa_deptid)values(?,?,?,?,?,?)";
	  	 	 	 $para = array((string)$eno,(string)$dept_id,(string)$dept_name,(string)$parent_dept_id,(string)$user,(string)$dept_id);
	  	 	 	 array_push($sns_sql,$sql);
	  	 	 	 array_push($sns_para,$para);
	  	 	 	 if ( count($sns_sql)>50)
 	 	  	 	 {
 	 	  	 	 	 try
 	 	  	 	 	 {
		 	 	 	  	 $da->ExecSQLs($sns_sql,$sns_para);
		 	 	 	  	 $sns_sql = array();
		 	 	 	  	 $sns_para=array();
		 	 	 	   }
		 	 	 	   catch(\Exception $e)
		 	 	 	   {
		 	 	 	   	 $logger->err($e->getMessage());
		 	 	 	   }
		  	 	 }
	  	 	 	 //更新im_base_dept
	  	 	 	 $sql = "insert into im_base_dept(deptid,deptname,pid,noorder)values(?,?,?,?)";
	  	 	 	 $para = array((string)$dept_id,(string)$dept_name,(string)$fafa_deptid,(string)$dept_id);
	  	 	 	 array_push($im_sql,$sql);
	  	 	 	 array_push($im_para,$para);		  	 	 
		  	 	 if ( count($im_sql)>50)
 	 	 	  	 {
		  	 	 	 try
		  	 	 	 {
		  	 	 	 	  $da_im->ExecSQLs($im_sql,$im_para);
		  	 	 	 	  $im_sql=array();
  	 	 	 	  	  $im_para=array();
		  	 	 	 }
		  	 	 	 catch(\Exception $e)
		  	 	 	 {
		  	 	 	 	 $logger->err($e->getMessage());	  	 	 	 	 
		  	 	 	 }
	  	 	   }
		  	 	 //写缓存数据
 	 	       //Cache_Enterprise::set(Cache_Enterprise::$EN_DEPT,$dept_id,$dept_id);
	      }
	      $startindex = $startindex + 1;
     }
     if ( count($sns_sql)>0)
 	   {
 	  	 try
 	  	 {
 	 	  	 $da->ExecSQLs($sns_sql,$sns_para);
 	 	   }
 	 	   catch(\Exception $e)
	 	 	 {
	 	 	 	 $logger->err($e->getMessage());	  	 	 	 	 
	 	 	 } 	
 	   }
 	   if ( count($im_sql)>0)
 	   {
 	  	 try
 	  	 {
 	 	  	 $da_im->ExecSQLs($im_sql,$im_para);
 	 	   }
 	 	   catch(\Exception $e)
	 	 	 {
	 	 	 	 $logger->err($e->getMessage());	  	 	 	 	 
	 	 	 } 	 	   	 
 	   }
// 	   if ( count($path_sql)>0)
// 	 	 {
// 	 	 	 try
//	 	 	 {
//	 	 	 	 $da_im->ExecSQLs($path_sql,$path_para);
//	 	 	 }
//	 	 	 catch(\Exception $e)
//	 	 	 {
//	 	 	 	  $logger->err($e->getMessage());
//	 	 	 }
// 	 	 }
	   return $data;
  }
  
  //判断部门id是否存在
  private function existsDeptId($da,$eno,$dept_id)
  {
  	 $exists = false;
  	 $sql="select dept_id from we_department where dept_id=? and eno=?";
  	 $ds = $da->GetData("table",$sql,array((string)$dept_id,(string)$eno));
  	 if ( $ds && $ds["table"]["recordcount"]>0)
  	    $exists = true;
  	 return $exists;  	 
  }  
  
  //获得部门根id
  private function getRootdeptid()
  {
  	 $curUser = $this->get('security.context')->getToken()->getUser();
  	 $eno = $curUser->eno;
  	 $da = $this->get("we_data_access");
  	 $da_im = $this->get('we_data_access_im'); 
  	 $root = "v".$eno;
  	 $sql = "select dept_id,fafa_deptid from we_department where parent_dept_id='-10000' and fafa_deptid=? and eno=?;";
  	 $ds = $da->GetData("table",$sql,array((string)$root,(string)$eno));
  	 $root_deptid = "";
  	 if ( $ds && $ds["table"]["recordcount"]>0)
  	 {
  	 	  $root_deptid = $ds["table"]["rows"][0]["dept_id"];
  	 }
  	 else{
  	 	  $ename = $curUser->ename;
  	 	  $root_deptid = SysSeq::GetSeqNextValue($da,"we_department","dept_id");
  	 	  //创建we_sns最上层部门
  	 	  $sql = "insert into we_department(eno,dept_id,dept_name,parent_dept_id,fafa_deptid,create_staff)values(?,?,?,'-10000',?,?);";
  	 	  $para = array((string)$eno,(string)$root_deptid,(string)$ename,$root,$curUser->getUserName());
  	 	  try
  	 	  {
  	 	  	 $da->ExecSQL($sql,$para);
  	 	  	 //创建we_im最上层部门
  	 	     $sql_im = "insert into im_base_dept(deptid,deptname,pid,path,noorder)values(?,?,'-10000',?,0);";
  	 	     $para_im = array((string)$root,(string)$ename,"/-10000/".$root."/");
  	 	     $da_im->ExecSQL($sql_im,$para_im);
  	 	  }
  	 	  catch(\Exception $e)
  	 	  {
  	 	  	$this->get("logger")->err($e->getMessage());
  	 	  }
  	 }
  	 return $root_deptid;
  }
  
  //获得SNS部门的上级部门id
  private function getSNSParentId($da,$dept_name)
  {
  	 $curUser = $this->get('security.context')->getToken()->getUser();
  	 $eno = $curUser->eno;
  	 $dept_id = $this->getDeptID($da,$eno,$dept_name);
  	 if ( empty($dept_id) )
  	 {
  	 	 $user = $curUser->getUserName();
  	 	 $dept_id = SysSeq::GetSeqNextValue($da,"we_department","dept_id");
  	 	 $sql = "insert into we_department(eno,dept_id,dept_name,parent_dept_id,create_staff)values(?,?,?,?,?)";
  	 	 $para = array((string)$eno,(string)$dept_id,(string)$dept_name,(string)$eno,(string)$user);
  	 	 try
  	 	 {
  	 	 	 $da->ExecSQL($sql,$para);
  	 	 }
  	 	 catch(\Exception $e){
  	 	 	 $dept_id="";
  	 	 	 $this->get("logger")->err($e->getMessage());
  	 	 }  	 	 
  	 }
  	 return $dept_id;  	 
  }

  //获得IM部门的上级部门id
  private function getIMParentId($da_im,$dept_name)
  {
  	 $curUser = $this->get('security.context')->getToken()->getUser();
  	 $eno = $curUser->eno;
  	 $sql = "select deptid from im_base_dept where deptname=?";
  	 $ds = $da_im->GetData("table",$sql,array((string)$dept_name));
  	 if ( $ds && $ds["table"]["recordcount"]>0)
  	 {
  	 	  return $ds["table"]["rows"][0]["deptid"];
  	 }
  	 else
  	 {
  	 	 $deptid = SysSeq::GetSeqNextValue($da_im,"im_base_dept","deptid");
  	 	 $sql = "insert into im_base_dept(deptid,deptname,pid,noorder)values(?,?,?,0);";
  	 	 $para = array((string)$deptid,$dept_name,"v".$eno);
  	 	 $da_im->ExecSQL($sql,$para);
  	 	 return $deptid;    	 	 
  	 }
  }  
  
  //获得部门id
  private function getDeptID($da,$eno,$dept_name)
  {
  	 $dept_id = "";
  	 $sql="select dept_id from we_department where dept_name=? and eno=?";
  	 $ds = $da->GetData("table",$sql,array((string)$dept_name,(string)$eno));
  	 if ( $ds && $ds["table"]["recordcount"]>0){
  	 	 $dept_id = $ds["table"]["rows"][0]["dept_id"];
  	 }
  	 return $dept_id;
  }
  
  //sql数据查询
  public function QueryAction()
  {
  	 $request = $this->getRequest();
  	 $url = $request->get("url");
  	 $dbname = $request->get("dbname");
  	 $dbuser = $request->get("dbuser");
  	 $dbpwd = $request->get("dbpwd");
  	 $dbtype = $request->get("dbtype");
  	 $sqlcomment = $request->get("sqlcomment");
  	 $data = array();
  	 $success = true;$msg="";
  	 try
  	 {
  	   $da = new \Justsy\BaseBundle\DataAccess\DataAccess($this->container,null,$dbtype,$url,$dbname,$dbpwd,$dbuser);
       $ds = $da->GetData("table",$sqlcomment);
       $data = $ds["table"]["rows"];
     }
     catch (\Exception $e){
     	 $success = false;
     	 $msg = "查询数据失败，请检查各项输入及网速状况！";
     }
     $result = array("success"=>$success,"msg"=>$msg,"DataSource"=>$data);
     $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;     
  }
  
  ///查询数据
  public function searchDataAction()
  {
  	 $da_im = $this->get('we_data_access_im');
  	 $da = $this->get('we_data_access');
  	 $request = $this->getRequest();
  	 $pageindex = (int)$request->get("pageindex");
  	 $pid = $request->get("pid");
  	 $eno = $this->get('security.context')->getToken()->getUser()->eno;
  	 $dept_name   = $request->get("dept_name");
  	 if ( !empty($dept_name))
  	 {
      	 $dept_name = str_replace("%","\%",$dept_name);
      	 $dept_name = str_replace("_","\_",$dept_name);
      	 $dept_name = str_replace("[","\[",$dept_name);
      	 $dept_name = str_replace("]","\]",$dept_name);
  	 }
  	 $pagenumber = (int)$request->get("record");
  	 $success = true;
  	 $msg = "";
  	 $pageindex = $pageindex <=0 ? 1:$pageindex;
  	 $limit = " limit ".(($pageindex - 1) * $pagenumber).",".$pagenumber;
  	 $condition = "";
  	 $para = array();$para2 = array();
  	 $micro = "v".$eno."999";
  	 $sql ="select pid parent_dept_id,(select deptname from im_base_dept b where b.deptid=a.pid ) parent_dept_name,deptid,deptname,
  	               manager manager_jid,ifnull((select employeename from im_employee where loginname=manager),'') manager,friend,`show`,
  	               (select count(*) from im_employee where deptid in(select b.deptid from im_base_dept b where b.path like concat(a.path,'%') and b.deptid not like concat('%',?,'%') )) number,
  	               case when ifnull(noorder,'')='' then '' else cast(noorder as signed) end noorder
  	       from im_base_dept a where pid!=-10000 ";
     $condition = "  and deptid not like concat('%',?,'%') and path like concat('%',?,'%') ";
  	 array_push($para,$micro,$micro,(string)"v".$eno);
  	 array_push($para2,$micro,(string)"v".$eno);
  	 //部门名称
     if ( !empty($dept_name)){
        if ( $dept_name =="%" )
          $condition .= " and position(? in deptname);";
        else
          $condition .= " and deptname like concat('%',?,'%') ";
     	 array_push($para,(string)$dept_name);
     	 array_push($para2,(string)$dept_name);
     }
     //上级部门id
     else if (!empty($pid)){
       	 $condition .= " and pid=? ";
       	 array_push($para,(string)$pid);
       	 array_push($para2,(string)$pid);
     }
     $sql .= $condition. " order by noorder+0 asc".$limit;
  	 $ds = $da_im->GetData("table",$sql,$para);
  	 $data = $ds["table"]["rows"];
  	 $recordcount = 0;
  	 if ( $pageindex==1){  //如果为第一页时返回记录总数
  	 	 $sql = " select count(*) recordcount 
  	 	         from im_base_dept a where pid!=-10000 ";
  	 	 $sql .= $condition;
  	 	 if ( count($para2)>0)
  	 	   $ds = $da_im->GetData("table",$sql,$para2);
  	 	 else
  	 	   $ds = $da_im->GetData("table",$sql);
  	 	 if ( $ds && $ds["table"]["recordcount"]>0)
  	 	   $recordcount = $ds["table"]["rows"][0]["recordcount"];
  	 }
  	 //返回的数据
     $result = array("success"=>$success,"msg"=>$msg,"datasource"=>$data,"recordcount"=>$recordcount);
     $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;
  }
  
  //删除部门数据记录
  //删除逻辑：１、不能删除具有下级部门的部门
  //           2、不能删除具有人员关联的部门
  public function deleteDeptAction()
  {
  	$da = $this->get("we_data_access");
  	$da_im = $this->get("we_data_access_im");
  	$request = $this->getRequest();
  	$deptid = $request->get("dept_id");  //此处为fafa_deptid
  	//返回参数
  	$success = true;
  	$msg = "";
  	try
  	{
  		//判断是否有下级部门
  		$sql = "select count(*) number from im_base_dept where pid=?";
  		$ds = $da_im->GetData("table",$sql,array((string)$deptid));
  		if ( $ds && $ds["table"]["recordcount"]>0){
  			 $count = $ds["table"]["rows"][0]["number"];
  			 if ( $count >0 ){
  			 	 $success = false;
  			 	 $msg = "不能删除具有下级部门的部门！";
  			 }
  			 else{
		  			$sql = "select count(*) number from im_employee where deptid=?;";
		  			$ds = $da_im->GetData("table",$sql,array((string)$deptid));
		  			if ( $ds && $ds["table"]["recordcount"]>0){
		  				$count = $ds["table"]["rows"][0]["number"];
		  				if ( $count>0){
		  					 
		  					 $success = false;
		  					 $msg="该部门底下有人员，不允许删除 !";
		  				}
		  				else{
                $deptinfo = $this->getDeptInfo($deptid);
                $deptMrg = new \Justsy\BaseBundle\Management\Dept($da,$da_im,$this->container);
                $deptdata = $deptMrg->getinfo($deptinfo["deptid"]);
			  				//删除部门数据
			  				$sql = "delete from we_department where fafa_deptid=?";
			  				$da->ExecSQL($sql,array((string)$deptid));
			  				//删除im部门
			  				$sql_pid="select pid from im_base_dept where deptid=?;";
			  				try
			  				{
    			  				$ds_pid = $da_im->GetData("table",$sql_pid,array((string)$deptid));
    			  				if ( $ds_pid && $ds_pid["table"]["recordcount"]>0)
    			  				{
    			  				    $deptdata["pid"] = $ds_pid["table"]["rows"][0]["pid"];
    			  				}
			  		    }
			  		    catch(\Exception $e)
			  		    {
			  		        $deptdata["pid"] = "";
			  		    }			  				
			  				$sql = "delete from im_base_dept where deptid=?;";
			  				$da_im->ExecSQL($sql,array((string)$deptid));
			  				$msg = "删除部门数据成功！";
                $parameter = array("flag"=>"all","title"=>"removeDept","message"=>json_encode($deptdata),"container"=>$this->container);
                $sendMessage = new SendMessage($da,$da_im);
                $sendMessage->sendImMessage($parameter);
		  			  }
		  			}
  		   }
  		}
  		else{
  			$success = false;
    	  $msg = "删除部门记录失败，请重试！";
  		}  		
    }
    catch(\Exception $e){
    	$success = false;
    	$this->get("logger")->err($e->getMessage());
    	$msg = "删除部门记录失败，请重试！";
    }
    //返回的数据
    $result = array("success"=>$success,"msg"=>$msg);
    $response = new Response(json_encode($result));
    $response->headers->set('Content-Type', 'text/json');
    return $response;  	
  }
  
  //获得部门数据数
  public function getTreeAction()
  {
  	$eno= $this->get('security.context')->getToken()->getUser()->eno;
  	$root = "v".$eno;
  	$request = $this->getRequest();
  	$pid = $request->get("deptid");
  	$number=$request->get("number");
  	$da = $this->get('we_data_access_im');
  	$sql="";$para = array();$condition = "";
  	$micro = $root."999";
  	if ( $number=="1")
  	{
        $sql =  "select 'true' open,deptid id,concat(deptname,'(',(select count(*) from im_employee where deptid in(select b.deptid from im_base_dept b where b.path like concat(a.path,'%') and deptid not like concat('%',?,'%') )),'人)') name,
                         pid pId,(select count(*) from im_base_dept where pid=a.deptid) state,0 readstate 
                 from im_base_dept a where pid!=-10000 and deptid not like concat('%',?,'%') ";
        array_push($para,$micro,$micro);
    }
    else
    {
        $sql =  "select 'true' open,deptid id,deptname as name,
                         pid pId,(select count(*) from im_base_dept where pid=a.deptid) state,0 readstate 
                 from im_base_dept a where pid!=-10000 and deptid not like concat('%',?,'%') ";
        array_push($para,$micro); 
    }    
    if (empty($pid))
    {
    	$condition = " and pid=? or deptid=? order by pid asc,noorder asc ";
    	array_push($para,(string)$root,(string)$root);
    }
    else
    {
    	$condition = " and pid=?";
    	array_push($para,(string)$pid);
    }
    $sql = $sql.$condition;
    $data = array();$success = true;
    try
    {
        $ds = $da->GetData("dept",$sql,$para);
        if ( $ds && $ds["dept"]["recordcount"]>0)
          $data = $ds["dept"]["rows"]; 
    }
    catch(\Exception $e)
    {
        $success = false;
        $this->get("logger")->err($e->getMessage());
    }       
    $result = array("success"=>$success,"datasource"=>$data);    
    $response = new Response(json_encode($result));
		$response->headers->set('Content-Type', 'text/json');  	    	
  	return $response;
  }
  
  //编辑部门排序列号
  public function editOrderAction()
  {
  	$da_im = $this->get('we_data_access_im');
  	$request = $this->getRequest();
  	$noorders = $request->get("noorders");
  	$sqls = array();
  	$paras = array();
  	$result = array("success"=>true,"msg"=>"更新部门排序号成功！");
  	for($i=0;$i < count($noorders);$i++){
  		 $sql = "update im_base_dept set noorder=? where deptid=?";
  		 $para = array((string)$noorders[$i]["noorder"],(string)$noorders[$i]["deptid"]);
  		 array_push($sqls,$sql);
  		 array_push($paras,$para);
  	}
  	try
  	{
  	  $da_im->ExecSQLS($sqls,$paras);
  	}
  	catch (\Exception $e){
  		$this->get("logger")->err($e->getMessage());
  		$result = array("success"=>false,"msg"=>"更新部门排序号失败！");
  	}
  	$response = new Response(json_encode($result));
		$response->headers->set('Content-Type', 'text/json');  	    	
  	return $response;
  }  
}