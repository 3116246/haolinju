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
use PHPExcel;
use PHPExcel_IOFactory;

class SalaryController extends Controller
{
  public function IndexAction()
  {  
  	$data = array();
  	$searchdata = $this->searchdata(null,null);
  	if ( $searchdata["success"])
  	  $data = $searchdata["data"];
  	$data = json_encode($data);
  	$date = json_encode($this->getInitDate());
  	return $this->render('JustsyAdminAppBundle:HR:gzd.html.twig',array("uploadData"=>$data,"InitDate"=>$date));
  }  
  
  //加密
  public function dec($str){
  	if ( empty($str)) return;
  	$len = strlen($str);
  	$dec = "";
  	for($i=0;$i< $len;$i++){
  		$val = substr($str,$i,1);
  		$val = ord($val);
  		$val = chr((int)$val+67).chr(rand(1,127));
  		$dec .= $val;
  	}
  	return $dec;
  }
  
  //解密
  public function decrypt($str){
  	$len = strlen($str);
  	$cry = "";
  	for($i=0;$i< $len ;$i++){
  		if ( $i % 2 == 1) continue;
  		$val = substr($str,$i,1);
  		$cry .=chr((int)ord($val)-67);
  	}
  	return $cry;
  }
  
  public function searchDataAction()
  {	
  	$request = $this->getRequest();
  	$date = $request->get("date");
  	$filename = $request->get("filename");
  	$result = $this->searchdata($filename,$date);
  	$response = new Response(json_encode($result));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }

  //查询数据记录方法
  private function searchdata($filename,$date)
  {
  	$file_webserver_url = $this->container->getParameter('FILE_WEBSERVER_URL');
  	$da = $this->get("we_data_access");
  	$sql = "select id,fileid,concat('$file_webserver_url',fileid) url,filename,filedate, date_format(filedate,'%Y-%m-%d %H:%i') filedate,ifnull(note,'') note,nick_name
  	        from mb_salary_file inner join we_staff on staffid=login_account";
  	$condition = "";$para = array();
  	if (!empty($filename)){
  		$condition = " and filename like concat(?,'%')";
  		array_push($para,$filename);
  	}
  	if (!empty($date)){
  		$enddate = date("Y-m-d",strtotime("$date +1 month"));
  		$condition .=" and (filedate between ? and ?)";
  		array_push($para,$date);
  		array_push($para,$enddate);
  	}
  	$sql .= $condition." order by filedate desc;";
  	$data = array();$msg = "";$success = true;
  	try
  	{
  	  $ds = $da->GetData("table",$sql,$para);
  	  $data = $ds["table"]["rows"];
  	}
  	catch (\Exception $e){
  		$success = false;
  		$this->get("logger")->err($e->getMessage());
  		$msg = "系统错误，请稍候重试!";
  	}
  	$result = array("success"=>$success,"msg"=>$msg,"data"=>$data);
  	return $result;
  }
  
  //初始化时用到的日期
  private function getInitDate()
  {
  	$da = $this->get("we_data_access");
  	$sql= "select date_format(filedate,'%Y-%m-01') date_val,date_format(filedate,'%Y-%m') date from mb_salary_file group by date_val order by date_val desc;";
  	$ds = $da->GetData("table",$sql);
  	return $ds["table"]["rows"];
  }
  
  public function IndexFieldAction()
  {			
  	$data = $this->getBigCodename();
  	return $this->render('JustsyAdminAppBundle:HR:salaryfield.html.twig',array("data"=>$data));
  }
  
  ///添加或修改工资项目
  public function editSalaryFieldAction()
  {
  	 $da = $this->get("we_data_access");
  	 $request = $this->getRequest();
  	 $data = $request->get("data");
  	 $sqls = array();
  	 $paras = array();
  	 $sql="";
  	 $para = array();
  	 $success = true;
		 $message = "操作成功";
  	 if ( $data != null && count($data)>0){
  	 	 $row = array();
  	 	 for($i=0;$i< count($data);$i++)
  	 	 {
  	 	 	 $row = $data[$i];
  	 	 	 $id = $row["id"];
  	 	 	 $bigid = $row["bigid"];
  	 	 	 $bigid = empty($bigid) ? null:$bigid;
  	 	 	 $codename = $row["codename"];
  	 	 	 $sort = $row["sort"];
  	 	 	 $sapname = $row["sapname"];
  	 	 	 $sapname = empty($sapname) ? null :$sapname;
  	 	 	 $code = $row["code"];
  	 	 	 $code = empty($code) ? null :$code;
  	     if ( $id==null && empty($id)){
  	 	     $id = SysSeq::GetSeqNextValue($da,"mb_salarycode","id");
  	 	     $sql = "insert into mb_salarycode(`id`,`bigid`,`codename`,`sort`,`sapname`,`code`)values(?,?,?,?,?,?)";
  	 	     $para = array((string)$id,$bigid,(string)$codename,(string)$sort,$sapname,$code);
   	     }
   	     else{
   	 	     $sql = "update mb_salarycode set `codename`=?,`sort`=?,`sapname`=?,`code`=? where `id`=?";
   	 	     $para = array((string)$codename,(string)$sort,$sapname,$code,(string)$id);   	 	 
   	     }
   	     array_push($sqls,$sql);
   	     array_push($paras,$para);
  	 	 }
  	 	 if ( count($sqls)>0){
  	 	 	 try
				 {
				    $da->ExecSQLs($sqls,$paras);
				 }
				 catch (Exception $e) {
				   $this->get('logger')->err($e->getMessage());
					 $success = false;
					 $message = $e->getMessage();
				 }
  	 	 }
  	 }
	   $result = array("success"=> $success,"message"=> $message);
	   $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;
  }
  
  private function getBigCodename()
  {
  	 $da = $this->get("we_data_access");
  	 $sql = "select id,codename,sort from mb_salarycode where bigid is null union select 0,'所属大类',0 order by sort asc;";
  	
  	 $ds = $da->GetData("table",$sql);
  	 $result = array();
  	 if ($ds && $ds["table"]["recordcount"]>0)
  	   $result = $ds["table"]["rows"];
  	 return $result;
  }
  
  //上传工资单
	public function uploadFileAction()
  {
  	$request = $this->getRequest();  	
		$upfile = $request->files->get("filedata");
		$note = $request->get("textnote");
		$check_cover = $request->get("check_cover");
		$tmpPath = $upfile->getPathname();
		$cover = false;
		if (!empty($check_cover) && strtolower($check_cover)=="on")
		  $cover = true;
		$filename = $upfile->getClientOriginalName();
		$logger = $this->get("logger");
		$fixedType = explode(".",strtolower($filename));
		$fixedType = $fixedType[count($fixedType)-1];
		$newfile = $_SERVER['DOCUMENT_ROOT']."/upload/"."salary".rand(10000,99999).".".$fixedType;
		$success = true;
		$message = array();
    if(move_uploaded_file($tmpPath,$newfile)) {
    	$rand = date('Y-m-d H:i:s',time());
  	  $rand = strtotime($rand).rand(10000000,99999999);
    	//添加工资数据
    	$importinfo =	$this->importSalary($newfile,$rand,$cover); 
    	$message = $importinfo["message"];
	    if ( $importinfo["success"] ){
		    //添加上传文件记录表
	    	$da = $this->get("we_data_access");
	      $currUser = $this->get('security.context')->getToken();
			  $user = $currUser->getUser()->getUserName();		
	      $id = SysSeq::GetSeqNextValue($da,"mb_salarycode","id");
	      $fileid = $this->saveFile($newfile);
	      $sqls = array();
	      $paras = array();
	      $sql = "insert into mb_salary_file(id,fileid,filename,filedate,staffid,note) values(?,?,?,now(),?,?)";
	      $para = array((string)$id,(string)$fileid,(string)$filename,(string)$user,$note);
	      array_push($sqls,$sql);
	      array_push($paras,$para);
	      //修改数据
	      $sql = "update mb_salary_main set fileid=? where fileid=?";
	      $para = array((string)$fileid,(string)$rand);
	      array_push($sqls,$sql);
	      array_push($paras,$para);	      
	      try
	      {
		      $da->ExecSQLS($sqls,$paras);
	      }
	      catch(\Exception $e){
	      	$success = false;
	      	$this->get("logger")->err($e->getMessage());      	
	      	$message = array("添加工资项失败！");
	      }
      }
    }
    else{
    	$success = false;
    	$message = array("上传工资文件失败！");
    }
    $result = array("success"=>$success,"msg"=>$message);
    $response = new Response("<script>parent.Salary.import_callback(".json_encode($result).")</script>");
    $response->headers->set('Content-Type', 'text/html');
    return $response;
  }
  
  //将文件保存到mogo
	private function saveFile($filename)
	{
		$fileid = "";
		try
		{
			if (!empty($filename) && file_exists($filename)){ 
		     $newfile = sys_get_temp_dir()."/".basename($filename);
         if (rename($filename,$newfile)){			    
			      //进行mongo操作
				    $dm = $this->get('doctrine.odm.mongodb.document_manager'); 
				    $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
				    $doc->setName(basename($newfile));
				    $doc->setFile($newfile);
				    $dm->persist($doc);
				    $dm->flush();
				    $fileid = $doc->getId();
				    if (file_exists($filename))
				      unlink($filename);
		    }
	    }
	  }
	  catch(\Exception $e){
	  	$this->get("logger")->err($e);
	  	$fileid = "";
	  }
	  return $fileid;
	}	 
	
	//删除mongo文件
  private function deleteFile($fileid)
	{
		 if (!empty($fileid)) {
	    	$dm = $this->get('doctrine.odm.mongodb.document_manager');
	      $doc = $dm->getRepository('JustsyMongoDocBundle:WeDocument')->find($fileid);
	      if(!empty($doc))
	        $dm->remove($doc);
	      $dm->flush();
	   }
	   return true;
	}
 
  //导入薪资excel文件内容
  public function importSalary($filename,$rand,$cover)
  {
  	 $success = true;
  	 $message = array();
  	 $da = $this->get("we_data_access");  	 
  	 $fixedType = explode(".",basename($filename));
  	 if ( count($fixedType)<2){
  	 	 return array("success"=>false,"message"=> array("文件名格式错误！"));
  	 }
  	 $fixedType = $fixedType[count($fixedType)-1];  	 
  	 $objReader = \PHPExcel_IOFactory::createReader( $fixedType=="xlsx"? 'Excel2007':"Excel5"); 
     $objPHPExcel = $objReader->load($filename);
		 $objWorksheet = $objPHPExcel->getActiveSheet();
     $totalrow = $objWorksheet->getHighestRow(); 
	   $highestColumn = $objWorksheet->getHighestColumn();
	   $totalcolumn = \PHPExcel_Cell::columnIndexFromString($highestColumn);//总列数
	   $field_cn = array();  //保存列名名称
	   $field_en = array();  //保存列名代码
	   for($rowindex=1;$rowindex<=2;$rowindex++){
	   	 for($col=0;$col< $totalcolumn;$col++){
	   	 	  $val = $objWorksheet->getCellByColumnAndRow($col,$rowindex)->getValue();
	   	 		if ( $rowindex==1)
		   	 	  array_push($field_cn,$val);
		   	 	else
		   	 	  array_push($field_en,$val);
	   	 }
	   }
	   //判断工资项编码是否存在
     $check = $this->checkColumn($field_cn,$field_en);
     if ( count($check)==0){
     	 $getval = array();
		   for($rowindex=3;$rowindex<=$totalrow;$rowindex++){
		   	 for($col=0;$col<$totalcolumn;$col++){
		   	 	 array_push($getval,$objWorksheet->getCellByColumnAndRow($col,$rowindex)->getValue());
		   	 }
		   	 $add = $this->AddSalaryVal($rowindex,$field_en,$getval,$rand,$cover);
		   	 $addmessage = $add["message"];
		   	 if ( count($addmessage)>0){
		   	 	 foreach($addmessage as $msg){
		   	 	 	 array_push($message,$msg);
		   	 	 }
		   	 }
		   	 $getval = array();
		   }
     }
     else{
     	 $success = false;
     	 $message = $check ;
     }
     return  array("success"=>$success,"message"=>$message);
  }
  
  //添加用户工资数据
  private function AddSalaryVal($rowindex,$field_key,$field_val,$rand,$cover)
  {
  	$success = true;
  	$month=null;$number=null;$name=null;$key=null;$val=null;
  	for($i=0;$i< count($field_key);$i++) {
  		$key = $field_key[$i];
  		$val = $field_val[$i];
  		if ($month==null && $key=="month"){
  		  $month = $val."-01";
  		  continue;
  		}
  		if ($number == null && $key=="number"){
  		  $number = $val;
  		  continue;
  		}
  		if ($name==null && $key == "name"){
  		  $name = $val;
  		  continue;
  		}
  		if (!empty($month) && !empty($number) && !empty($name))
  		 break;
  	}
  	//添加员工工资前的判断
    $message = $this->checkSalary($rowindex,$month,$number,$name,$cover);
    if ( count($message)==0){
    	$da = $this->get("we_data_access");
    	$sqls = array();
    	$paras = array();
    	$id = SysSeq::GetSeqNextValue($da,"mb_salary_main","id");
    	$sql ="insert into mb_salary_main(id,fileid,month,number)values(?,?,?,?)";
    	$para = array((string)$id,(string)$rand,(string)$month,(string)$number);
    	array_push($sqls,$sql);
    	array_push($paras,$para);    	
    	for($i=0;$i< count($field_val);$i++){
    		$key = $field_key[$i];
  		  $val = $field_val[$i];
  		  if ( $key=="month" || $key=="number" || $key=="name") continue;
  		  $sql = "insert into mb_salary_sub(salary_id,code,money)values(?,?,?)";
  		  if ( empty($val) ) continue; //工资为0或空时不存储
  		  $val = $this->dec($val);
  		  $para = array((string)$id,$key,$val);
  		  array_push($sqls,$sql);
    	  array_push($paras,$para);
    	}
    	if ( count($sqls)>0){
    		try
    		{
    		  $da->ExecSQLS($sqls,$paras);
    		}
    		catch(\Exception $e){
    			$success = false;
    			$message = array("添加第".$rowindex."行(工号='".$number."')的数据记录失败！");
    		}
    	}
    }
    else{
    	$success = false;    	
    }
    $result = array("success"=>$success,"message"=>$message); 
    return $result;
  }
  
  //添加员工工资前的判断
  private function checkSalary($rowindex,$month,$number,$name,$cover)
  {
  	$da = $this->get("we_data_access");
  	$sql = "select id from mb_salary_main where month=? and number=?";
	  $ds = $da->GetData("table",$sql,array((string)$month,(string)$number));
	  $result = array();
		if ( $ds && $ds["table"]["recordcount"]>0){
			if ($cover){  //覆盖数据(删除此前数据记录)
				$sqls = array();
				$paras = array();
				//工资单id
				$salaryid = $ds["table"]["rows"][0]["id"];
				//删除工资单子表
		    $sql = "delete from mb_salary_sub where salary_id=?";
		    $para = array((string)$salaryid);
		    array_push($sqls,$sql);
		    array_push($paras,$para);
		    //删除工资单主表
		    $sql = "delete from mb_salary_main where id=?";
		    $para = array((string)$salaryid);
		    array_push($sqls,$sql);
		    array_push($paras,$para);
		    try
		    {
		    	$da->ExecSQLS($sqls,$paras);
		    }
		    catch(\Exception $e){
		    	$this->get("logger")->err($e->getMessage());
		    }		    
			}
			else{
  		  $result=array("工资单中第".$rowindex."行，工号='".$number."'的".$month."工资已存在！");
  		}
  	}
  	else{
			$sql = "select id from mb_hr_7 where zhr_pa903112=?";
			$ds = $da->GetData("table",$sql,array((string)$number));
			if ($ds && $ds["table"]["recordcount"]==0){
				$result=array("工资单中第".$rowindex."行，工号='".$number."'的记录不存在,该条记录已忽略添加！");
			}
	  }		
  	return $result;
  }
  
  //删除工资文件及相关表数据记录
  public function deleteFileAction()
  {
  	$da = $this->get("we_data_access");
  	$request = $this->getRequest();
  	$id = $request->get("id");
  	$fileid = $request->get("fileid");
  	//删除mongo文件
  	try
  	{
       $this->deleteFile($fileid);
    }
    catch(\Exception $e){
    }
  	$sqls = array();
  	$paras = array();
  	//删除mb_salary_file表记录
  	$sql = "delete from mb_salary_file where id=? ";
  	$para = array((string)$id);
  	array_push($sqls,$sql);
  	array_push($paras,$para);
  	//删除工资记录子表
  	$sql = "delete from mb_salary_sub where salary_id in(select id from mb_salary_main where fileid=?)";
  	$para = array((string)$fileid);  	
  	array_push($sqls,$sql);
  	array_push($paras,$para);
    //删除工资记录主表
    $sql = "delete from mb_salary_main where fileid=?";
    $para = array((string)$fileid);
  	array_push($sqls,$sql);
  	array_push($paras,$para);
  	$success = true;$msg = "";
  	try
  	{
  		 $da->ExecSQLS($sqls,$paras);
  	}
  	catch(\Exception $e){
  		$success = false;
  		$this->get("logger")->err($e->getMessage());
  		$msg = "删除文件失败！";
  	}
  	$result = array("success"=>$success,"msg"=>$msg);
  	$response = new Response(json_encode($result));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  
  //判断工资项编码是否在码表里
  private function checkColumn($field_cn,$field_en){
  	$da = $this->get("we_data_access");
  	$result = array();
  	$sql = "select id from mb_salarycode where code=?";
  	for($i=3;$i < count($field_en);$i++){
  		$code = $field_en[$i];
  		$para = array((string)$code);
  		$ds = $da->GetData("t",$sql,$para);
  		if ( $ds && $ds["t"]["recordcount"]==0){
  		  array_push($result,"工资项目：".$field_cn[$i]."  工资项编码：".$code." 不存在");
  		}
  	}	
  	return $result;
  }
  
  //------------------工资字段管理----------------------------
  //返回工资字段表值
  public function getSalaryFieldsAction()
  {
  	 $da = $this->get("we_data_access");
  	 $sql = "select sort,id,ifnull(bigid,id) bigid,codename,ifnull(sapname,'') as sapname,ifnull(`code`,'') as `code`,
                    case when bigid is null then (select count(*) from mb_salarycode where bigid=a.id) else 0 end itemcount       
             from mb_salarycode a order by (case when bigid is null then concat(id,'0') else concat(bigid,'1') end)+0 asc,sort asc;";
     $data = array();
     $isadmin = false;
     $success = true;
     try {
     	 $ds=$da->GetData("table",$sql);
     	 $data = $ds["table"]["rows"];
       //判断用户是否系统管理员
       $user = $this->get('security.context')->getToken()->getUser();
       $login_account = $user->getUserName();
       $eno = $user->eno;
  	   $sql = "select eno from we_enterprise  where locate(?,sys_manager)>0 and eno=?";
       $ds = $da->GetData("table",$sql,array((string)$login_account,$eno));
       if ( $ds && $ds["table"]["recordcount"]>0)
     	   $isadmin = true;
     }
     catch (\Exception $e){
     	 $this->get("logger")->err($e->getMessage());
     	 $success = false;
     }
     $result = array("success"=>$success,"isadmin"=>$isadmin,"data"=>$data);
     $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;
  }
  
  //判断账号是否存在
  public function existAccountAction()
  {
  	 $da = $this->get("we_data_access");
  	 $curUser = $this->get('security.context')->getToken()->getUser();
		 $login_account = $curUser->getUserName();
		 $sql = "select login_account from mb_salary_staff where login_account=? and status=1;";
		 $ds = $da->GetData("table",$sql,array((string)$login_account));
		 $result = array("exists"=>false);
		 if ( $ds && $ds["table"]["recordcount"]>0)
		   $result["exists"] = true;
     $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;		   		 
  }
  
  //注册账号
  public function salaryRegisterAction()
  {
  	 $da = $this->get("we_data_access");
  	 $request = $this->getRequest();
  	 $password = $request->get("password");  	 
  	 $login_account = $this->get('security.context')->getToken()->getUser()->getUsername();
  	 $returncode = ReturnCode::$SUCCESS;
  	 $msg = "";
  	 if (empty($password)){
  	 	 $returncode = ReturnCode::$SYSERROR;
  	 	 $msg = "请传入password参数！";
  	 }
  	 else{
  	 	 $sql="select login_account from mb_salary_staff where login_account=?";
       $ds = $da->GetData("table",$sql,array((string)$login_account));     
       if ($ds && $ds["table"]["recordcount"]>0){
     	   $returncode = ReturnCode::$SYSERROR;
     	   $msg = "已存在该用户账号！";
       }
       else{
		  	 //加密密码
		  	 $user = new UserSession($login_account, $password, $login_account, array("ROLE_USER"));
		     $factory = $this->get("security.encoder_factory");
		     $encoder = $factory->getEncoder($user);
		     $password = $encoder->encodePassword($password,$user->getSalt());
		     $sql = "insert into mb_salary_staff(login_account,`password`)values(?,?);";
		     $para = array((string)$login_account,(string)$password);
		     try
		     {
		     	 $da->ExecSQL($sql,$para);
		     	 $msg = "注册用户账号成功！";
		     }
		     catch (\Exception $e){
		     	 $returncode = ReturnCode::$SYSERROR;
		     	 $msg = "注册用户账号失败！";
		     }
       }
  	 }
     $result = array("returncode"=>$returncode,"msg"=>$msg);
     $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;
  }
  
  //工资独立登录
  public function salaryLoginAction()
  {
  	 $da = $this->get("we_data_access");
		 $request = $this->getRequest();
		 $password = $request->get("password");
		 $login_account = $this->get('security.context')->getToken()->getUser()->getUserName();
		 $returncode = ReturnCode::$SUCCESS;
		 $msg = "";
     //加密密码
	   $user = new UserSession($login_account, $password, $login_account, array("ROLE_USER"));
	   $factory = $this->get("security.encoder_factory");
	   $encoder = $factory->getEncoder($user);
	   $password = $encoder->encodePassword($password,$user->getSalt());
	   $sql = "select login_account from mb_salary_staff where login_account=? and `password`=? and `status`=1;";
	   $para = array((string)$login_account,(string)$password);
	   try
	   {
	     $ds = $da->GetData("table",$sql,$para);
	     if ($ds && $ds["table"]["recordcount"]>0) {
	     	 $returncode = ReturnCode::$SUCCESS;
	     	 $msg = "登录成功";
	     }
	     else{
	     	 $returncode = ReturnCode::$ERROFUSERORPWD;
	     	 $msg = "用户账号或密码错误！";
	     }
	   }
	   catch (\Exception $e){
	   	 $this->get("logger")->err($e->getMessage());
	   	 $returncode = ReturnCode::$SYSERROR;
	     $msg = "登录时发生系统错误，请重试！";
	   }
	   $result = array("returncode"=>$returncode,"msg"=>$msg);
     $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;
  }
  
  //查看工资大类
  public function salaryViewAction()
  {
     $da = $this->get("we_data_access");
		 $request = $this->getRequest();
		 $date = $request->get("date");
		 $user = $this->get('security.context')->getToken()->getUser();
		 $login_account = $user->getUserName();
		 $account = explode("@",$login_account);
		 $work_number = $account[0];
		 $returncode = ReturnCode::$SUCCESS;
		 $data = array();$msg="";
		 $sql = "";
		 $para = array();
		 if (empty($date)){
		 	 $sql = "select id from mb_salary_main where number=? and `month`=(select max(`month`) from mb_salary_main where number=?);";
		 	 $para = array((string)$work_number,(string)$work_number);
		 }
		 else{
		 	 $sql = "select id from mb_salary_main where `month`=? and number=?";
		 	 $para = array((string)$date,(string)$work_number);
		 }
		 try
		 {
		   $ds = $da->GetData("table",$sql,$para);
		   if ($ds && $ds["table"]["recordcount"]>0){
		   	 $id = $ds["table"]["rows"][0]["id"];  	 
		   	 $sql = "select id codeid,".$id." as salaryid,codename,null `ischildren`,0 total from mb_salarycode where bigid is null order by sort asc";
		   	 $ds = $da->GetData("salary_field",$sql);
		   	 if ( $ds && $ds["salary_field"]["recordcount"]>0){		   	 	 
		   	 	 for($i=0;$i< $ds["salary_field"]["recordcount"];$i++){
		   	 	   $bigid = $ds["salary_field"]["rows"][$i]["codeid"];
		   	 	   $result = $this->getTotalMoney($da,$id,$bigid);
		   	 	   $ds["salary_field"]["rows"][$i]["ischildren"] = $result["count"];
		   	 	   $ds["salary_field"]["rows"][$i]["total"] = $this->dec($result["total"]);
		   	   }
		   	 }
		   	 $data = $ds["salary_field"]["rows"];
		   }
		   else{
		   	 $msg = "该员工无对应工资单！";
		   }
			 //写入日志信息
	     $syslog = new \Justsy\AdminAppBundle\Controller\SysLogController();
	     $syslog->setContainer($this->container);
	     $date = date($date);
   	   $ym = date("Y年m月", strtotime($date));
	     $desc =  $user->nick_name."查看了【".$ym."工资单】。";
	     $syslog->AddSysLog($desc,"查看工资");
		 }
		 catch (\Exception $e){
		 	 $this->get("logger")->err($e->getMessage());
		 	 $returncode = ReturnCode::$SYSERROR;
		 	 $msg = "系统出错，请重试！";
		 } 
		 $result = array("returncode"=>$returncode,"msg"=>$msg,"data"=>$data);
		 $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;
  }
    
  //查看工资详细
  public function salarydetailAction()
  {
  	 $da = $this->get("we_data_access");
		 $request = $this->getRequest();
		 $codeid = $request->get("codeid");
		 $salaryid = $request->get("salaryid");
		 $returncode = ReturnCode::$SUCCESS;
		 $data = array();$msg = "";
		 if ( empty($codeid) || empty($salaryid)){
	     	$msg = "请传入正确的参数！";
	     	$returncode = ReturnCode::$SYSERROR; 		 	 
		 }
		 else{
			 $sql = "select codename,money from mb_salary_sub a inner join mb_salarycode b on a.code=b.code
	             where bigid=? and salary_id=? order by sort asc;";
	     $para = array((string)$codeid,(string)$salaryid);
	     try
	     {
	     	  $ds = $da->GetData("table",$sql,$para);
	     	  $data = $ds["table"]["rows"];
	     }	
	     catch (\Exception $e)
	     {
	     	 $this->get("logger")->err($e->getMessage());
	     	 $msg = "系统出错，请重试！";
	     	 $returncode = ReturnCode::$SYSERROR;     	 
	     }
     }
     $result = array("returncode"=>$returncode,"msg"=>$msg,"data"=>$data);
		 $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;		 
  }  
  
  //获得当前用户最大工资日期
  public function maxMonthAction()
  {
  	 $da = $this->get("we_data_access");
		 $login_account = $this->get('security.context')->getToken()->getUser()->getUserName();
		 $account = explode("@",$login_account);
		 $work_number = $account[0];
		 $returncode = ReturnCode::$SUCCESS;
		 $msg="";$maxmonth=null;
		 $sql = "";		 
		 $sql = "select max(`month`) maxmonth from mb_salary_main where number=?";
		 $para = array((string)$work_number);
		 try
		 {
		   $ds = $da->GetData("table",$sql,$para);
		   if ($ds && $ds["table"]["recordcount"]>0){
		   	 	$maxmonth = $ds["table"]["rows"][0]["maxmonth"];
		   }
		 }
		 catch (\Exception $e){
		 	 $this->get("logger")->err($e->getMessage());
		 	 $returncode = ReturnCode::$SYSERROR;
		 	 $msg = "系统出错，请重试！";
		 }
		 if (empty($maxmonth)) {
		 	 $maxmonth="";		 	
		 }
		 else{
		 	 $date = strtotime($maxmonth);
		 	 $maxmonth = date("Y-m",$date);
		 }
		 $result = array("returncode"=>$returncode,"msg"=>$msg,"month"=>$maxmonth);
		 $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;
  }
  
  //重置工资单密码
  public function RestartPasswordAction()
  {
  	 $da = $this->get("we_data_access");
  	 $request = $this->getRequest();
  	 $login_account = $request->get("login_account");
  	 $success = true;$msg = "";
  	 if ( empty($login_account)){
  	 	 $success = false;
  	 	 $msg = "请输入用户账号！";  	 	  
  	 }
  	 else{
  	 	 if(!Utils::validateEmail($login_account)){
      	 $success = false;
      	 $msg = "请输入正确的用户账号！";
       }
       else{
	  	 	 $sql = "delete from mb_salary_staff where login_account=?;";
	  	 	 try
	  	 	 {
	  	 	 	  $da->ExecSQL($sql,array((string)$login_account));
	  	 	 	  //记录用户操作日志
				    $syslog = new \Justsy\AdminAppBundle\Controller\SysLogController();
				    $syslog->setContainer($this->container);
				    $desc = "清除用户账号:".$login_account."工资独立密码！";
				    $syslog->AddSysLog($desc,"工资密码");
	  	 	 }
	  	 	 catch(\Exception $e){
	  	 	 	 $success = false;
	  	 	 	 $msg = "重置用户工资密码错误！";
	  	 	 	 $this->get("logger")->err($e->getMessage());
	  	 	 }
  	   }
  	 }
  	 $result = array("success"=>$success,"msg"=>$msg);
	   $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;
  }
  
  //删除工资单字段项
  public function deleteFieldAction()
  {
  	$da = $this->get("we_data_access");
  	$request = $this->getRequest();
  	$type = $request->get("type");
  	$id = $request->get("id");
  	$isdelete = false;
  	$success = true;
  	$message = "";
  	if ( $type == "1" ) {
  		$sql = "select count(*) t from mb_salarycode a where bigid=? and exists (select * from mb_salary_sub b where a.code=b.code)";
  	}
  	else{
  		$sql = "select count(*) t from mb_salarycode a where id=? and exists (select * from mb_salary_sub b where a.code=b.code)";
  	}  	
  	$para = array((string)$id);
  	try
  	{
      $ds = $da->GetData("table",$sql,$para);
      if ($ds){
      	$count = (int)$ds["table"]["rows"][0]["t"];
      	if ( $count>0){
      		 $success = false;
      		 $message = "已有工资数据对应到该项，不允许删除！";
      	}
      	else{
      		if ( $type=="1" ){
      			$sql = "delete from mb_salarycode where id=? or bigid=?";
      			$para = array((string)$id,(string)$id);
      	  } 
      	  else{
      	    $sql = "delete from mb_salarycode where id=?";
      	    $para = array((string)$id);
      	  }      	  
      	  try
      	  {
      	  	$da->ExecSQL($sql,$para);
      	  	$success = true;
      	  	$message = "删除工资字段项成功！";
      	  }
      	  catch(\Exception $e){
      	  	$this->get("logger")->err($e->getMessage());
      	  	$success = false;
      	  	$message = "删除工资字段项失败！";
      	  } 
      	}
      }
    }
    catch (\Exception $e){
    	$this->get("logger")->err($e->getMessage());
    	$success = false;
    	$message = "操作出现错误，请重试！";
    }
    $result = array("success"=>$success,"msg"=>$message);
		$response = new Response(json_encode($result));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  
  //获得用户每项工资大类总和及是否有子项  
  public function getTotalMoney($da,$salaryid,$bigid){
  	$count = false;$total = 0.0;
  	$sql = "select money from mb_salary_sub a inner join mb_salarycode b on a.code=b.code where a.salary_id=? and bigid=?";
  	$para = array((string)$salaryid,(string)$bigid);
  	$ds = $da->GetData("table",$sql,$para);
  	if ( $ds && $ds["table"]["recordcount"]>0){
  		 $recordcount = $ds["table"]["recordcount"];
  		 if ( $recordcount>1) 
  		   $count=true;
  		 for($i=0;$i< $ds["table"]["recordcount"];$i++){
  		 	 $money = $this->decrypt($ds["table"]["rows"][$i]["money"]);
  		 	 $total += (double)$money;
  		 }
  	}
  	return array("count"=>$count,"total"=>$total);
  }
}