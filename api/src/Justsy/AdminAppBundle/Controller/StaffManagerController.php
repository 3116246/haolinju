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
use Justsy\BaseBundle\Management\Staff;
use Justsy\BaseBundle\DataAccess\SysSeq;
use PHPExcel;
use PHPExcel_IOFactory;
use Justsy\BaseBundle\Common\Cache_Enterprise;

class StaffManagerController extends Controller
{        
    public function IndexAction()
    {
        $request = $this->getRequest();
        $type = $request->get("type");
        if ( empty($type))        
           return $this->render('JustsyAdminAppBundle:Basic:staffManager.html.twig');
        else
           return $this->render('JustsyAdminAppBundle:Basic:staffManager2.html.twig');
    }
    
  //上传导入的Excel文件
  public function importExcelAction()
  {
    $request = $this->getRequest();  	
		$upfile = $request->files->get("filedata");
		$tmpPath = $upfile->getPathname();
		$filename = $upfile->getClientOriginalName();		
		$fixedType = explode(".",strtolower($filename));		
		$fixedType = $fixedType[count($fixedType)-1];
		$newfile = $_SERVER['DOCUMENT_ROOT']."/upload/staff_".rand(10000,99999).".".$fixedType;
		$field_name = array();$field_value = array();
    $msg = "";$success = true;
    $recordcount = 0;
    $totalpage = 1;
    $page_record = 100;
    if(move_uploaded_file($tmpPath,$newfile)) {
	  	$re = $this->getExcelContent($newfile);
	  	$data = $re["data"];
	  	$recordcount = $re["recordcount"];
	  	$totalpage = ceil($recordcount / $page_record);
    }
    else{
    	 $this->get("logger")->err("上传文件错误！");
    	 $msg = "文件上传错误！";
    	 $success = false;
    }
    $result = array("success"=>$success,"msg"=>$msg,"DataSource"=>$data,"filepath"=>$newfile,"recordcount"=>$recordcount,"totalpage"=>$totalpage);
		$response = new Response("<script>parent.staff_import_callback(".json_encode($result).")</script>");	
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
	   $totalrow = $totalrow>16 ? 16 : $totalrow; //只取16行数据返回
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
  
  //导入人员数据记录
  public function ImportDataAction()
  {
  	 set_time_limit(300);
  	 $request = $this->getRequest();
  	 $Field = $request->get("relation");
  	 $file = $request->get("file");
  	 $totalrecord = $request->get("totalrecord");
  	 $pageindex =   $request->get("index");
  	 $isDel = (int)$request->get("isDel");
  	 //返回的数据参数
	   $result = array();
  	 if ( $isDel == 0)
  	 {
	  	 $index = array();
	  	 $val = $Field["login_account"]["index"];
	  	 if ($val!="")
	  	   $index["login_account"] = $val;
	  	 $val = $Field["nick_name"]["index"];
	  	 if ($val!="")
	  	   $index["nick_name"] = $val;
	  	 $val = $Field["dept_id"]["index"];
	  	 if ($val!="")
	  	 	 $index["dept_id"] = $val;
	  	 $val = $Field["password"]["index"];
	  	 if ($val!="")
	  	   $index["password"] = $val;
	  	 $val = $Field["mobile"]["index"];
	  	 if ($val!="")
	  	   $index["mobile"] = $val;  
	  	 $val = $Field["duty"]["index"];
	  	 if ($val!="")
	  	   $index["duty"] = $val;
	  	 $pageindex = empty($pageindex) ? 1 : (int)$pageindex;
	  	 $totalrecord = empty($totalrecord) ? 1 : (int)$totalrecord;
	  	 //计算记录起始行号
	  	 $page_record = 100;
	     $startindex =0;$endindex = 0;
	  	 if ( $pageindex == 1){
	  	    $startindex = 2;
	  	    if ( $totalrecord <= $page_record){
	  	      $endindex = $totalrecord;
	  	    }
	  	    else {
	  	      $endindex = $page_record;
	  	    }
	  	 }
	  	 else{
	  	 	 	$startindex = $page_record * ($pageindex-1) + 1;
	  	 	 	$endindex   = $startindex + $page_record - 1;
	  	 	 	if ( $endindex>$totalrecord)
	  	 	 	  $endindex = $totalrecord;
	  	 }
	  	 $user = $this->get('security.context')->getToken()->getUser();
	  	 $result["errorData"] = $this->startImportData($user,$file,$index,$startindex,$endindex,$this->get("logger"));
  	 }
  	 else{ //删除文件
  	 	 if ( file_exists($file))
  	 	    unlink($file);
  	 }
  	 $result["index"] = $pageindex + 1;
  	 $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;
  }
  
  //根据企业部门名称获得部门id
  private function getdeptid($eno,$deptname)
  {
  	 $da = $this->get("we_data_access");
  	 $sql = "select dept_id from we_department where dept_name=? and eno=?;";
  	 $para = array((string)$deptname,(string)$eno);
  	 $ds = $da->GetData("table",$sql,$para);
  	 if ( $ds && $ds["table"]["recordcount"]>0){
  	 	 return $ds["table"]["rows"][0]["dept_id"];
  	 }
  	 else
  	   return null;
  }
  
  //判断用户是否注册
  private function isregister($login_account,$mobile)
  {
  	$da = $this->get("we_data_access");
  	$para = array();
  	$sql = "select login_account from we_staff where login_account=? ";
  	array_push($para,(string)$login_account);
  	if ( !empty($mobile))
  	{
  	    $sql .= " or mobile_bind=?";
  	    array_push($para,(string)$mobile);
  	}  	
	  $ds = $da->GetData("table",$sql,$para);
	  if ( $ds && $ds["table"]["recordcount"]>0){
	 	  return true;
	  }
	  else
	    return false;
  }
  
  //导入用户数据
  public function startImportData($user,$filename,$field,$startindex,$endindex,$logger)
  {
  	 $startindex = (int)$startindex;
  	 $endindex = (int)$endindex;
     $fixedType = explode(".",basename($filename));
  	 $fixedType = $fixedType[count($fixedType)-1];  	 
  	 $objReader = \PHPExcel_IOFactory::createReader( $fixedType=="xlsx"? 'Excel2007':"Excel5"); 
     $objPHPExcel = $objReader->load($filename);
		 $objWorksheet = $objPHPExcel->getActiveSheet();
		 $returndata = array(); 
		 $eno = $user->eno;
		 $ename=$user->ename;
		 $account=$user->getUserName();
		 $da = $this->get("we_data_access");
	   $da_im = $this->get("we_data_access_im");
     $staffMgr = new \Justsy\BaseBundle\Management\Staff($da,$da_im,$user,$this->container->get("logger"),$this->container);
     $to_jids = implode(",", $staffMgr->getFriendAndColleagueJid());//获取通知的人范围
     $register = new \Justsy\BaseBundle\Controller\ActiveController();
     $register->setContainer($this->container);
	   $deptids = array();	   
	   //账号后缀
	   $suffix = "@".$this->container->getParameter('edomain');;
	   $login_array = explode("@",$account);
	   if ( count($login_array)>1)
     {
	      $suffix = "@".$login_array[1];
     }
	   for (;$startindex <=$endindex;$startindex++)
	   {
	   	 	$login_account="";$nick_name="";$dept_id="";$password="";$mobile="";$duty="";
	   	 	if ( isset($field["login_account"]))
        {
	   	 	   $login_account = $objWorksheet->getCellByColumnAndRow((int)$field["login_account"],$startindex)->getValue();
        }
	   	 	if ( isset($field["nick_name"]))
	   	 	  $nick_name = $objWorksheet->getCellByColumnAndRow((int)$field["nick_name"],$startindex)->getValue(); 	
	   	 	if ( isset($field["dept_id"]))
	   	 	{
	   	 	  $dept_id = $objWorksheet->getCellByColumnAndRow((int)$field["dept_id"],$startindex)->getValue();    	 	  
	   	 	  if ( !empty($dept_id) && (is_numeric($dept_id)==false))
	  	 	    $dept_id = $this->getdeptid($eno,$dept_id);
	  	 	  if ( !in_array($dept_id,$deptids))
	  	 	     array_push($deptids,$dept_id);
	   	 	}
	   	 	if ( isset($field["password"]))
	   	 	  $password = $objWorksheet->getCellByColumnAndRow((int)$field["password"],$startindex)->getValue(); 
	   	 	if ( isset($field["mobile"]))
	   	 	  $mobile = $objWorksheet->getCellByColumnAndRow((int)$field["mobile"],$startindex)->getValue();
	   	 	if ( isset($field["duty"]))
	   	 	  $duty = $objWorksheet->getCellByColumnAndRow((int)$field["duty"],$startindex)->getValue();
        //帐号处理	   	 	
	   	 	if(empty($login_account) && !empty($mobile))
        {
           $login_account = $mobile.$suffix;
        }
        else if(!empty($login_account) && strpos($login_account,"@")===false)
        {
          $login_account .= $suffix;
        }
        //数据检验
        if ( empty($login_account) || empty($nick_name) || empty($password)){
	   	 		 array_push($returndata,"用户账号、用户名称、密码不能为空！");
	   	 	   continue;
	   	 	}
	   	 	if ( strlen($login_account)>50)
	   	 	{
	   	 		 array_push($returndata,"请将用户账号(".$login_account."设置为小于50位的账号！");
	   	 	   continue;
	   	 	}
	   	 	//如果昵称过长只取前10位
	   	 	if ( mb_strlen($nick_name,"UTF8")>10) $nick_name = mb_substr($nick_name,0,10,"UTF8");	   	 	
	   	 	if ($this->isregister($login_account,$mobile)){
	   	 		 array_push($returndata,"用户账号(".$login_account.")已经注册！");
	   	 	   continue;
	   	 	}
	   	 	//判断手机号正误
	   	  if (!empty($mobile))
        {
	   	 		if (!Utils::validateMobile($mobile))
			    {
			      array_push($returndata,"用户账号(".$login_account.")所对应的手机号输入错误！");
			      continue;
			    }
	   	 	}	   	 	
	   	  //获得ldap_uid
	      //$login_array = explode("@",$login_account);
	      //if ( count($login_array)<2) continue;
	      //$ldap_uid = $login_array[0];
  	 	  //自动注册  	 	  
  	 	  $parameter = array("account"=>$login_account,"realName"=>$nick_name,"passWord"=>$password,"ldap_uid"=>"",
  	 	                    "eno"=>$eno,"ename"=>$ename,"isNew"=>'0',"mailtype"=>"1","import"=>'1',"mutual"=>"N",
  	 	                    "isSendMessage"=>"N","mobile"=>$mobile,"duty"=>$duty,"deptid"=>$dept_id); 	                    
			  $result = $register->doSave($parameter);
			  if ($result["returncode"]!="0000"){
			  	array_push($returndata,"用户账号（".$login_account."）注册失败:".$result["msg"]);
			  }
     }
     //统计人员
     $deptMgr = new \Justsy\BaseBundle\Management\Dept($da,$da_im,$this->container);
     for($j=0;$j<count($deptids);$j++)
     {
        $info = $deptMgr->getinfo($deptids[$j]);
        $im_deptid = $info["fafa_deptid"];
        if ( !empty($im_deptid))
        {
            $sql = " call dept_emp_stat(?);";
      	    $para = array((string)$im_deptid);
      	    try
      	    {
      	       $da_im->ExecSQL($sql,$para);
                //发送通知 消息 
                Utils::sendImMessage("",$to_jids,"staff-changedept",json_encode($info),$this->container,"","",false,Utils::$systemmessage_code);
         
      	    }
      	    catch(\Exception $e)
      	    {
              $this->container->get("logger")->err($e);
      	    }
  	    }
     }
	   return $returndata;
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
  	 $log = $this->get("logger");
  	 try
  	 {
  	   $da = new \Justsy\BaseBundle\DataAccess\DataAccess($this->container,null,$dbtype,$url,$dbname,$dbpwd,$dbuser);
       $ds = $da->GetData("table",$sqlcomment);
       $data = $ds["table"]["rows"];
     }
     catch (\Exception $e){
     	 $log->err($e->getMessage());
     	 $success = false;
     	 $msg = "查询数据失败，请检查各项输入及网速状况！";
     }
     $result = array("success"=>$success,"msg"=>$msg,"DataSource"=>$data);
     $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;     
  }
  
  //查询用户数据
  public function searchStaffAction()
  {
    
  	 $da = $this->get("we_data_access");
  	 $user = $this->get('security.context')->getToken()->getUser();
  	 $eno = $user->eno;
  	 $request = $this->getRequest();
  	 $pageindex = (int)$request->get("pageindex",1);
  	 $dept_id   = $request->get("dept_id");
  	 $login_account = $request->get("login_account");  	 
  	 $pagenumber = (int)$request->get("record");
  	 $success = true;
  	 $msg = "";
  	 $pageindex = $pageindex<1 ? 1 : $pageindex;
  	 $limit = " limit ".(($pageindex - 1) * $pagenumber).",".$pagenumber;
  	 $para = array();
     $sql ="select ifnull((select fafa_deptid from we_department b where a.dept_id=b.dept_id),'') dept_id,
                   ifnull((select dept_name from we_department b where a.dept_id=b.dept_id),'') dept_name,
                   login_account,fafa_jid as jid,
                   case when position('fafatime.com' in login_account)=0 then login_account else '' end e_mail,
                   nick_name,ifnull(mobile_bind,'') mobile,ifnull(date_format(this_login_date,'%Y-%m-%d %H:%i'),'') login_date,ifnull(duty,'') duty,ifnull(sex_id,'男') sex 
                   from we_staff a ";
     $condition = "  where not exists (select 1 from we_micro_account where a.login_account=number) and eno=? ";
     $para[] = (string)$eno;
     //排除广播员
     $condition .= " and not exists(select 1 from we_announcer cer where cer.login_account=a.login_account) ";
     //排除系统管理员
     $condition .= " and position(a.login_account in (select sys_manager from we_enterprise where eno=? limit 1))=0 ";
     $para[] = (string)$eno;
     //用户账号或昵称不为空
     if (!empty($login_account)){
        $login_account = str_replace("%","\%",$login_account);
        $login_account = str_replace("_","\_",$login_account);
        $login_account = str_replace("[","\[",$login_account);
        $login_account = str_replace("]","\]",$login_account);        
        if (strlen($login_account)>mb_strlen($login_account,'utf8')){
          $condition .= " and a.nick_name like concat('%',?,'%') ";
          $para[] = (string)$login_account;
        }
        else {
          $condition .= " and (a.login_account like concat('%',?,'%') or a.nick_name like concat('%',?,'%')) ";
          $para[] = (string)$login_account;
          $para[] = (string)$login_account;
        }
     }       
     if ( !empty($dept_id)){
     	 $condition .= " and a.dept_id=(select dept_id from we_department where fafa_deptid=?) ";
     	 $para[] = (string)$dept_id;
     }
     $sql .= $condition. " order by this_login_date desc ".$limit;
     $ds = $da->GetData("table",$sql,$para);
  	 $data = $ds["table"]["rows"];
  	 $recordcount = 0;
  	 if ( $pageindex==1)
  	 {  
  	    //如果为第一页时返回记录总数
  	 	  $sql = " select count(*) recordcount from we_staff a ".$condition;
  	 	  if ( count($para)>0)
  	 	    $ds = $da->GetData("table",$sql,$para);
  	 	  else
  	 	    $ds = $da->GetData("table",$sql);
  	 	  if ( $ds && $ds["table"]["recordcount"]>0)
  	 	    $recordcount = $ds["table"]["rows"][0]["recordcount"];
  	 }
  	 //返回的数据
     $result = array("success"=>$success,"msg"=>$msg,"datasource"=>$data,"recordcount"=>$recordcount);
     $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;
  }
  
  //删除用户账号
  public function deleteStaffAction()
  {
  	 $request = $this->getRequest();
  	 $success = true;$msg="";
  	 $login_account = $request->get("login_account");
  	 $success = true;$msg="";
  	 $staffMgr = new \Justsy\BaseBundle\Management\Staff($this->get("we_data_access"),$this->get('we_data_access_im'),$login_account,$login_account,$this->container);
     $staffdata = $staffMgr->getInfo();
     $toList = $staffMgr->getFriendAndColleagueJid();
     $success = $staffMgr->leave();

     if ($success )
     {
        //推送消息
	      $user = $this->get('security.context')->getToken()->getUser();
        Utils::sendImMessage($user->fafa_jid,implode(",", $toList),"removeStaff",json_encode($staffdata),$this->container,"","",false,Utils::$systemmessage_code);
     }
     else
     {
        $success = false;
        $msg="删除用户账号失败！";
     }
  	 $result = array("success"=>$success,"msg"=>$msg);
     $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;
  }
    
  //添加或修改用户账号信息
  public function updateStaffAction()
  {
  	 $da = $this->get("we_data_access");
  	 $da_im = $this->get('we_data_access_im');
  	 $request = $this->getRequest();
  	 $state = $request->get("state");
  	 $im_deptid = $request->get("dept_id");
  	 //将im的部门id转化为sns里的部门id
     $deptInfo =$this->getDeptInfo($im_deptid);
     $sns_deptid = $deptInfo["deptid"];
  	 $login_account = $request->get("login_account");
  	 $e_mail = $request->get("e_mail");
  	 $nick_name = $request->get("nick_name");
  	 $password = $request->get("password");
  	 $sex = $request->get("sex");
  	 $duty = $request->get("duty");
     $duty = empty($duty) ? null : $duty;
  	 $mobile = $request->get("mobile");
  	 $mobile = empty($mobile) ? null : $mobile;
  	 $success = true;$msg = "";
     //判断手机号正误
 	   if (!empty($mobile))
 	 	 {
 	 	   if (!Utils::validateMobile($mobile))
	     {
	       $result = array("success"=>false,"msg"=>"手机账号格式错误！");
  	     $response = new Response(json_encode($result));
         $response->headers->set('Content-Type', 'text/json');
         return $response;
	     }
	     else
	     {
	         $sql="";$para = array();
	         if ( $state=="add" )
	         {
	           $sql = "select count(*) number from we_staff where mobile_bind=?;";
	           array_push($para,(string)$mobile);
	         }
	         else
	         {
              $sql = "select count(*) number from we_staff where mobile_bind=? and login_account!=?;";
              array_push($para,(string)$mobile,$login_account);
           }
           try
	         {
	            $ds = $da->GetData("table",$sql,$para);
	            if ( $ds && $ds["table"]["recordcount"]>0)
	            {
	                if ( (int)$ds["table"]["rows"][0]["number"]>0)
	                {
	                    $result = array("success"=>false,"msg"=>"已存在该手机号码！");
	                    $response = new Response(json_encode($result));
                      $response->headers->set('Content-Type', 'text/json');
                      return $response;
	                }
	            }
	         }
	         catch(\Exception $e)
	         {
	            $this->get("logger")->err($e->getMessage());
	         }
	     }
	   }
	   $user = $this->get('security.context')->getToken()->getUser();
     $deptMgr = new \Justsy\BaseBundle\Management\Dept($da,$da_im,$this->container);
     $staffMgr = new \Justsy\BaseBundle\Management\Staff($da,$da_im,$login_account,$this->container->get("logger"),$this->container);
	   if ( $state=="add")
	   {
	      if ( $staffMgr->checkUser($mobile))
	      {
	         $result = array("success"=>false,"msg"=>"用户账号已存在，请重新输入！");
  	       $response = new Response(json_encode($result));
           $response->headers->set('Content-Type', 'text/json');
           return $response;
	      }
	      //获得ldap_uid
	      $login_array = explode("@",$login_account);
	      if ( count($login_array)<2) continue;
	      $ldap_uid = $login_array[0];
  	 	  //注册用户账号
	      $register = new \Justsy\BaseBundle\Controller\ActiveController();
  	    $register->setContainer($this->container);
  	 	  $parameter = array("account"=>$login_account,"realName"=>$nick_name,"passWord"=>$password,"ldap_uid"=>$ldap_uid,
  	 	                    "eno"=>$user->eno,"ename"=>$user->ename,"isNew"=>'0',"mailtype"=>"1","import"=>'1',
  	 	                    "isSendMessage"=>"N","mobile"=>$mobile,"duty"=>$duty,"deptid"=>$sns_deptid,"mutual"=>"Y");
			  $result = $register->doSave($parameter);
			  if ($result["returncode"]=="0000"){
          $staffdata = $staffMgr->getInfo();
			    //成功后统计人员
          $sql = "call dept_emp_stat(?)";
          $da_im->ExecSQL($sql,array((string)$staffdata["fafa_jid"]));
          
          //自动关注服务号
          $servicerMgr = new \Justsy\BaseBundle\Management\Service($this->container);
          $parameter=array("eno"=>$user->eno,"deptid"=>$im_deptid,"login_account"=>$login_account);        	 	 	  
          $servicerMgr->atten_service($parameter);

          $revJids = $staffMgr->getFriendAndColleagueJid();
          Utils::sendImMessage($user->fafa_jid,implode(",", $revJids),"newstaff",json_encode($staffMgr->getinfo()),$this->container,"","",false,Utils::$systemmessage_code);
			  }
			  else
			  {
			    $success = false;
			  	$msg = "添加用户账号失败:".$result["msg"];
			  }
			  $result = array("success"=>$success,"msg"=>$msg);
  	    $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
	   }
	   else
	   {
        $staffinfo = $staffMgr->getInfo();
        $deptinfo = $deptMgr->getinfo($staffinfo["dept_id"]);
        //判断是否更改了性别
        if($sex==$staffinfo["sex_id"])
          $sex=null;
        //判断是否更改了职务
        if($duty==$staffinfo["duty"])
          $duty=null;
        //判断是否更改了姓名
        if($nick_name==$staffinfo["nick_name"])
          $nick_name=null;
        //判断是否更新了部门
        if($sns_deptid==$deptinfo["dept_id"])
          $sns_deptid =  null ;
        //判断是否更新了手机
        if($mobile==$staffinfo["mobile_bind"])
          $mobile =  null ;
        $uResult = $staffMgr->checkAndUpdate($nick_name,$mobile,$sns_deptid,$duty,null,$sex,null,$e_mail);
        
        $u_staff = null;$factory = null;
        //判断是否修改了密码
        if ( !empty($password))
        {
            $u_staff = new Staff($da,$da_im,$login_account,$this->get('logger'),$this->container);
            $factory = $this->get('security.encoder_factory');
            $targetStaffInfo = $u_staff->getInfo();
    		    $re = $u_staff->changepassword($targetStaffInfo["login_account"],$password,$factory);
    	    	if ( $re ){
              //给自己发送一个staff-changepasswod的出席，通知在线客户端密码发生修改，需要新密码重新登录
              Utils::sendImPresence($user->fafa_jid,$targetStaffInfo["fafa_jid"],"staff-changepasswod","staff-changepasswod",$this->container,"","",false,Utils::$systemmessage_code);
    		    }
        }
        //判断是否修改了帐号
        if ( $e_mail!=$login_account)
        {
            //判断邮件是否存在
            
            if ( empty($u_staff))
               $u_staff = new Staff($da,$da_im,$login_account,$this->get('logger'),$this->container);
            if ( empty($factory))
               $factory = $this->get('security.encoder_factory');
            $u_staff->changeLoginAccount($e_mail,$factory);
        }
      	try
      	{
            $revJids = $staffMgr->getFriendAndColleagueJid();
            if ( $uResult && !empty($sns_deptid))
            {
              //部门变更时，需要通知手机端更新原部门和新部门数据
                Utils::sendImMessage("",implode(",", $revJids),"staff-changedept",json_encode($deptinfo),$this->container,"","",false,Utils::$systemmessage_code);
                Utils::sendImMessage("",implode(",", $revJids),"staff-changedept",json_encode($deptMgr->getinfo($sns_deptid)),$this->container,"","",false,Utils::$systemmessage_code);
                
                $old_fafa_deptid = $deptinfo["fafa_deptid"];
                $fafa_jid = $staffinfo["fafa_jid"];
        	 	 	  //取消关注服务号
        	 	 	  $servicerMgr = new \Justsy\BaseBundle\Management\Service($this->container);
        	 	 	  $parameter=array("eno"=>$user->eno,"deptid"=>$old_fafa_deptid,"login_account"=>$login_account);
        	 	 	  $servicerMgr->cancel_atten($parameter);
        	 	 	  //自动关注服务号
        	 	 	  $parameter=array("eno"=>$user->eno,"deptid"=>$im_deptid,"login_account"=>$login_account);
        	 	 	  $servicerMgr->atten_service($parameter);
    	 	    }
        }
      	catch (\Exception $e)
      	{
      	 	$this->get("logger")->err($e->getMessage());
      	 	$success = false;
      	 	$msg = "修改人员信息失败！";
      	}
  	 }
  	 $result = array("success"=>$success,"msg"=>$msg);
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
  
  //获得部门信息
  private function getDeptPID($deptid)
  {
  	 $da_im = $this->get('we_data_access_im');
  	 $sql = "select pid from im_base_dept where deptid=?";
 	 	 $para = array((string)$deptid);
 	 	 $ds = $da_im->GetData("table",$sql,$para);
 	 	 $pid = "";
 	 	 if ( $ds && $ds["table"]["recordcount"]>0){
 	 	   $pid = $ds["table"]["rows"][0]["pid"];
 	   }
 	   return $pid;
  }
}