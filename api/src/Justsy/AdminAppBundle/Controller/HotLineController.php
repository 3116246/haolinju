<?php

namespace Justsy\AdminAppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Common\DES;

class HotLineController extends Controller
{    
    public function IndexAction()
    {
    	//接收人默认为当前登录用户
    	$parameter = $this->InitData();
    	return $this->render('JustsyAdminAppBundle:Buffet:hotline.html.twig',$parameter);
    }
    
    //页面初始化数据
    private function InitData()
    {
    	 $da = $this->get('we_data_access');
    	 $mindate=null;$maxdate=null;
    	 $sql = "select date_format(ifnull(min(receivedate),curdate()),'%Y-%m-%d') min_date,curdate() max_date from mb_hotline;";
    	 try
    	 {
    	 	  $ds = $da->GetData("table",$sql);
    	 	  if ( $ds && $ds["table"]["recordcount"]>0){
    	 	  	 $mindate = $ds["table"]["rows"][0]["min_date"];
    	 	  	 $maxdate = $ds["table"]["rows"][0]["max_date"];    	 	  	 
    	 	  }
    	 	  else{
    	 	  	$mindate = date("Y-m-d");
    	 	  	$maxdate = $mindate;
    	 	  }  
    	 }
    	 catch(\Exception $e){
    	 	 $mindate = date("Y-m-d");
    	 	 $maxdate = $mindate;
    	 }    	 
    	 $staff = $this->get('security.context')->getToken()->getUser()->nick_name;
    	 return array("min_date"=>$mindate,"max_date"=>$maxdate,"default_staff"=>$staff);
    }
    
    //热线查询
    public function SearchHotLineAction()
    {
    	$da = $this->get('we_data_access');
    	$request = $this->getRequest();
    	$startdate = $request->get("startdate");
    	$enddate = $request->get("enddate");
    	$keyword = $request->get("keyword");
    	$pageindex = (int)$request->get("pageindex");
  	  $record = (int)$request->get("record");
  	  $success = true;
  	  $msg = "";
  	  $datasource = array();
  	  $recordcount = 0;  
  	  $limit = " limit ".(($pageindex - 1) * $record).",".$record;
  	  $condition = "";
  	  $para = array();
    	$sql = "select id,number,date_format(receivedate,'%m/%d %H:%i') receivedate,ifnull(name,'&nbsp;') name,content,case `source` when 1 then '企业微信' when 2 then '热线' when 3 then '电子邮件' else '其他' end 'source',
                     case grade when 1 then '紧急重要' when 2 then '不紧急但重要' when 3 then '紧急一般重要' when 4 then '不紧急一般重要' else '&nbsp;' end grade_desc,
                     (select count(*) from mb_hotline_scheme where hotid=a.id) scheme,(select count(*) from mb_hotline_visit where hotid=a.id) visit
              from mb_hotline a where 1=1";
    	$para = array();
    	if (!empty($keyword)){
    		$condition = " and content like concat(?,'%')";
    		array_push($para,(string)$keyword);
    	}
    	if (!empty($startdate)){
    		 $condition .= " and receivedate>=?";
    		 array_push($para,(string)$startdate);
    	}
    	if (!empty($enddate)){
    		$enddate = $enddate." 23:59:59";
    		$condition .= " and receivedate<=?";
    		array_push($para,(string)$enddate);
    	}
    	try
    	{
	    	$ds = null;
	    	$sql = $sql.$condition." order by receivedate desc,grade asc ".$limit;
	    	if ( count($para)>0)
	    		$ds = $da->GetData("table",$sql,$para);
	    	else
	    		$ds = $da->GetData("table",$sql);
	      $datasource = $ds["table"]["rows"];
	      //当为第一页时，求出总数
	      if ($pageindex==1){
	      	$sql = "select count(*) total from mb_hotline where 1=1 ".$condition;
	      	if (count($para)>0) 
	      	  $ds = $da->GetData("total",$sql,$para);
	      	else
	      	  $ds = $da->GetData("total",$sql);
	      	if ($ds && $ds["total"]["recordcount"]>0){
	      		$recordcount = (int)$ds["total"]["rows"][0]["total"];
	      	}
	      }
	    }
	    catch (\Exception $e){
	    	$this->get("logger")->err($e->getMessage());
	    	$msg = "查询数据失败！";
	    	$success = false;
	    }	    
	    $result = array("success"=>$success,"message"=>$msg,"datasource"=>$datasource,"recordcount"=>$recordcount);
    	$response = new Response(json_encode($result));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }
    
    //按热线id查询热线信息
    public function SearchHotLineByIdAction()
    {
    	 $da = $this->get("we_data_access");
    	 $request = $this->getRequest();
    	 $hotid = $request->get("hotid");
    	 $sql = "select id,number,ifnull(receivestaff,'') receivestaff,source,date_format(receivedate,'%Y-%m-%d %H:%i') receivedate,staff_number,name,
    	           case when source=1 and dept1 is null then (select orgeh_d from mb_hr_7 where zhr_pa903112=staff_number limit 1) else dept1 end dept1,
                 ifnull(dept2,'') dept2,ifnull(address,'') address,ifnull(case when source=1 and ifnull(duty,'')='' then (select stell_d from mb_hr_7 where zhr_pa903112=staff_number limit 1) else duty end,'') duty,
                      ifnull(case when source=1 and ifnull(in_date,'')='' then (select date_format(dat03,'%Y-%m-%d') from mb_hr_7 where zhr_pa903112=staff_number limit 1) else in_date end,'') in_date,ifnull(contact,'') contact,ifnull(content,'') content,ifnull(scheme,0) scheme,ifnull(grade,0) grade
              from mb_hotline where id=?";
       $para = array((string)$hotid);
       $success = true;
       $msg = "";
       $data = array();
       try
       {
       	  $ds = $da->GetData("table",$sql,$para);
       	  if ($ds && $ds["table"]["recordcount"]>0)
       	   $data = $ds["table"]["rows"];
       }
       catch (\Exception $e){
       	 $this->get("logger")->err($e->getMessage());
       	 $msg = "查询热线数据失败！";
       	 $success = false;
       }
       $result = array("success"=>$success,"msg"=>$msg,"returndata"=>$data);
       $response = new Response(json_encode($result));
       $response->headers->set('Content-Type', 'text/json');
       return $response;
    }
    
    
    //编辑热线
    public function editHotLineAction()
    {
    	 $da = $this->get('we_data_access');
    	 $request = $this->getRequest();
    	 //获得用户传入参数
    	 $hotid = $request->get("hotid");
    	 $receivestaff = $request->get("receivestaff");
    	 $source = $request->get("source");
    	 $receivedate = $request->get("receivedate");
    	 $staff_number = $request->get("staff_number");
    	 $name = $request->get("name");
    	 $dept1 = $request->get("dept1");
    	 $dept2 = $request->get("dept2");
    	 $address = $request->get("address");
    	 $duty = $request->get("duty");
    	 $in_date = $request->get("in_date");
    	 $contact = $request->get("contact");
    	 $content = $request->get("content");
    	 $scheme = $request->get("scheme");
    	 $grade = $request->get("grade");
    	 $sql ="";
    	 //处理加盟日期
    	 $in_date = empty($in_date) ? null :$in_date;
    	 $para = array();
    	 if ( empty($hotid)){
    	 	 $number = $this->getHotLineNumber();
    	 	 $hotid = SysSeq::GetSeqNextValue($da,"mb_hotline","id");
    	 	 $sql = "insert into mb_hotline(id,number,receivestaff,source,receivedate,staff_number,name,dept1,dept2,address,duty,in_date,contact,content,scheme,grade)values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    	 	 $para = array((string)$hotid,(string)$number,(string)$receivestaff,(string)$source,(string)$receivedate,(string)$staff_number,(string)$name,(string)$dept1,(string)$dept2,(string)$address,(string)$duty,$in_date,(string)$contact,(string)$content,(string)$scheme,(string)$grade);
    	 }
    	 else {
    	 	 $sql = "update mb_hotline set receivestaff=?,source=?,receivedate=?,staff_number=?,name=?,dept1=?,dept2=?,address=?,duty=?,in_date=?,contact=?,content=?,scheme=?,grade=? where id=?";
    	 	 $para = array((string)$receivestaff,(string)$source,(string)$receivedate,(string)$staff_number,(string)$name,(string)$dept1,(string)$dept2,(string)$address,(string)$duty,$in_date,(string)$contact,(string)$content,$scheme,$grade,(string)$hotid);
    	 }
    	 //返回的参数
    	 $success = true;
    	 $msg = "";
    	 try
    	 {
    	 	  $da->ExecSQL($sql,$para);
    	 }
    	 catch (\Exception $e){
    	 	 $this->get("logger")->err($e->getMessage());
    	 	 $success = false;
    	 	 $msg = "编辑热线数据失败！";
    	 }
    	 $result = array("success"=>$success,"msg"=>$msg);
       $response = new Response(json_encode($result));
       $response->headers->set('Content-Type', 'text/json');
       return $response;
    }
        
    //获得热线编号
    private function getHotLineNumber()
    {
    	 $da = $this->get("we_data_access");
    	 $receive_number = "";
    	 $sql = "select ifnull(max(number),concat(year(now()),month(now()),'00'))+1 receive_number
    	         from mb_hotline where year(receivedate)=year(curdate()) and month(receivedate)=month(curdate());";
    	 $ds = $da->GetData("table",$sql);
    	 if ( $ds && $ds["table"]["recordcount"]>0)
    	    $receive_number = $ds["table"]["rows"][0]["receive_number"];
    	 return $receive_number;
    }
    
    //删除热线记录数据
    public function DeleteHotLineAction()
    {
    	 $da=$this->get("we_data_access");
    	 $request = $this->getRequest();
    	 $hotid = $request->get("hotid");
    	 $sqls = array();
    	 $paras = array();
    	 $para = array((string)$hotid);
    	 //删除热线回访
    	 $sql = "delete from mb_hotline_visit where hotid=?";
    	 array_push($sqls,$sql);
    	 array_push($paras,$para);
    	 //删除热线方案
    	 $sql = "delete from mb_hotline_scheme where hotid=?";
       array_push($sqls,$sql);
    	 array_push($paras,$para);
    	 //删除热线记录
    	 $sql = "delete from mb_hotline where id=?";
    	 array_push($sqls,$sql);
    	 array_push($paras,$para);
    	 $success = true;$msg="";
    	 try
    	 {
    	 	 $da->ExecSQLS($sqls,$paras);
    	 }
    	 catch(\Execption $e) {
    	 	 $this->get("logger")->err($e->getMessage());
    	 	 $success = false;
    	 	 $msg = "删除热线数据记录失败！";
    	 }
       $result = array("success"=>$success,"msg"=>$msg);
    	 $response = new Response(json_encode($result));
       $response->headers->set('Content-Type', 'text/json');
       return $response;
    }
    
    //删除热线处理记录数据
    public function DeleteSchemeAction()
    {
    	 $da=$this->get("we_data_access");
    	 $request = $this->getRequest();
    	 $schemeid = $request->get("schemeid");    	 
    	 $sql = "delete from mb_hotline_scheme where id=?";
    	 $para = array((string)$schemeid);
    	 $success = true;$msg="";
    	 try
    	 {
    	 	 $da->ExecSQL($sql,$para);
    	 } 
    	 catch(\Execption $e) {
    	 	 $this->get("logger")->err($e->getMessage());
    	 	 $success = false;
    	 	 $msg = "删除热线处理方案失败！";
    	 }
       $result = array("success"=>$success,"msg"=>$msg);
    	 $response = new Response(json_encode($result));
       $response->headers->set('Content-Type', 'text/json');
       return $response;    	
    }
    
    //删除热线回方记录
    public function DeleteVisitAction()
    {
       $da=$this->get("we_data_access");
    	 $request = $this->getRequest();
    	 $visitid = $request->get("visitid");
    	 $sqls = array();
    	 $paras = array(); 
    	 $sql = "delete from mb_hotline_visit where visitid=?";
    	 $para = array((string)$visitid);    	
    	 $success = true;$msg="";
    	 try
    	 {
    	 	 $da->ExecSQL($sql,$para);
    	 } 
    	 catch(\Execption $e) {
    	 	 $this->get("logger")->err($e->getMessage());
    	 	 $success = false;
    	 	 $msg = "删除热线回访记录失败！";
    	 }
       $result = array("success"=>$success,"msg"=>$msg);
    	 $response = new Response(json_encode($result));
       $response->headers->set('Content-Type', 'text/json');
       return $response;        	
    }
    
    //查看热线处理详细
    public function viewSchemedescAction()
    {
    	 $da=$this->get("we_data_access");
    	 $request = $this->getRequest();
    	 $hotid = $request->get("hotid");
    	 $staffid = $this->get('security.context')->getToken()->getUser()->getUserName();
    	 $success = true;$msg = "";
    	 $resourcedata = array();
    	 $sql = "select id,scheme,date_format(date,'%Y-%m-%d %H:%i') date,scheme_staff,case when staffid=? then 1 else 0 end isedit 
    	         from mb_hotline_scheme where hotid=? order by id desc;";
    	 $para = array((string)$staffid,(string)$hotid);
    	 try
    	 {
    	   $ds = $da->GetData("table",$sql,$para);
    	   if ( $ds && $ds["table"]["recordcount"]>0)
    	     $resourcedata = $ds["table"]["rows"];   
    	 }
    	 catch (\Exception $e){
    	 	 $this->get("logger")->err($e->getMessage());
    	 	 $success = false;
    	 	 $msg = "查看热线处理方案失败！";
    	 }
    	 $result = array("success"=>$success,"msg"=>$msg,"datasource"=>$resourcedata);
    	 $response = new Response(json_encode($result));
       $response->headers->set('Content-Type', 'text/json');
       return $response;
    }
    
    //编辑回访记录Api接口
    public function Api_EditVisitAction()
    {
    	 $da = $this->get('we_data_access');
    	 $request = $this->getRequest();
    	 $visitid = $request->get("visitid");
    	 $hotid = $request->get("hotid");
    	 $hr_satisfied = $request->get("hr_satisfied");
    	 $zb_satisfied = $request->get("zb_satisfied");
    	 $suggest = $request->get("suggest");
    	 $question1 = $request->get("question1");    	
    	 $question2 = $request->get("question2");
    	 $note = $request->get("note");
    	 $visit_staff = $request->get("visit_staff");
    	 $sql = "";$parameter = array();
    	 $returncode = ReturnCode::$SUCCESS;$msg = "";
    	 if ( empty($visitid)){
    	 	 $visitid = SysSeq::GetSeqNextValue($da,"mb_hotline_visit","id");
    	 	 $sql = "insert into mb_hotline_visit values(?,?,?,?,?,?,?,?,now(),?);";
    	 	 $parameter = array((string)$visitid,(string)$hotid,(string)$hr_satisfied,(string)$zb_satisfied,(string)$suggest,(string)$question1,(string)$question2,(string)$note,(string)$visit_staff);
    	 }
    	 else{
    	 	 $sql = "update mb_hotline_visit set hr_satisfied=?,zb_satisfied=?,suggest=?,question1=?,question2=?,note=?,visit_staff=? where visitid=?";
    	 	 $parameter = array((string)$hr_satisfied,(string)$zb_satisfied,(string)$suggest,(string)$question1,(string)$question2,(string)$note,(string)$visit_staff,(string)$visitid);
    	 }
    	 try
    	 {
    	 	 $da->ExecSQL($sql,$parameter);
    	 }
    	 catch (\Exception $e){
    	 	 $returncode = ReturnCode::$SYSERROR;
    	 	 $msg = "编辑热线回访信息失败！";
    	 	 $this->get("logger")->err($e->getMessage());
    	 }
    	 $result = array("returncode"=>$returncode,"msg"=>$msg);
    	 $response = new Response(json_encode($result));
       $response->headers->set('Content-Type', 'text/json');
       return $response;
    }
    
    //热线回访详细信息
    public function viewVisitAction()
    {
    	 $da = $this->get('we_data_access');
    	 $request = $this->getRequest();
    	 $hotid= $request->get("hotid"); 	
    	 $sql = "select visitid,hotid,hr_satisfied,zb_satisfied,suggest,question1,question2,note,date_format(date,'%Y-%m-%d %H:%i') date 
    	         from mb_hotline_visit where hotid=?";
    	 $success = true;$msg = "";$datasource = array();
    	 try
    	 {
	    	 $ds = $da->GetData("table",$sql,array((string)$hotid));
	    	 if ($ds && $ds["table"]["recordcount"]>0){
	    	 	 $datasource = $ds["table"]["rows"];
	    	 }
    	 }
    	 catch(\Exception $e){
    	 	 $success = false;
    	 	 $msg = "查看热线回访记录失败！";
    	 	 $this->get("logger")->err($e->getMessage());
    	 	 
    	 }
    	 $result = array("success"=>$success,"msg"=>$msg,"datasource"=>$datasource);
    	 $response = new Response(json_encode($result));
       $response->headers->set('Content-Type', 'text/json');
       return $response;
    }
        
    //--------------------------------------------------调用接口部分-----------------------------------
    //客户端添加热线接口
    public function Api_AddHotlineAction()
    {
    	 $da = $this->get('we_data_access');
    	 $request = $this->getRequest();
    	 $content = $request->get("content");
    	 $hostId = SysSeq::GetSeqNextValue($da,"mb_hotline","id");
    	 $receive_number = $this->getHotLineNumber();
    	 $source = 1;
    	 //员工号
    	 $curuser = $this->get('security.context')->getToken()->getUser();
    	 $staff = $curuser->getUserName();
    	 $temp = explode("@",$staff);
    	 $staff_number = strtoupper($temp[0]);
    	 $sql = "insert into mb_hotline(id,number,source,receivedate,staff_number,name,content)values(?,?,1,now(),?,?,?)";
    	 $para = array((string)$hostId,(string)$receive_number,(string)$staff_number,(string)$curuser->nick_name,(string)$content);
    	 $returncode = ReturnCode::$SUCCESS;
    	 $msg = "";
    	 try
    	 {
    	 	 $da->ExecSQL($sql,$para);
    	 }
    	 catch(\Exception $e){
    	 	 $returncode = ReturnCode::$SYSERROR;
    	 	 $this->get("logger")->err($e->getMessage());
    		 $msg = "添加热线内容失败！";
    	 }
    	 $result = array("returncode"=>$returncode,"msg"=>$msg);
    	 $response = new Response(json_encode($result));
       $response->headers->set('Content-Type', 'text/json');
       return $response;
    }
    
    //查看我的热线（热线列表）
    public function Api_MyHotLineAction()
    {
    	$da = $this->get('we_data_access');
    	$user = $this->get('security.context')->getToken()->getUser()->getUserName();
    	$work_num = explode("@",$user);
    	$work_num = $work_num[0];
    	$sql = "select id as hotid,date_format(receivedate,'%Y-%m-%d %H:%i') date,content,(select count(*) from mb_hotline_scheme b where a.id=b.hotid and isread=0) isread
              from mb_hotline a where staff_number=?";
    	$para = array((string)$work_num);
    	$returncode = ReturnCode::$SUCCESS;
    	$msg = "";
    	$data = array();
    	try
    	{
    		 $ds = $da->GetData("table",$sql,$para);
    		 $data = $ds["table"]["rows"];
    	}
    	catch(\Exception $e){
    		$returncode = ReturnCode::$SYSERROR;
    		$this->get("logger")->err($e->getMessage());
    		$msg = "查询热线数据失败！";
    	}
    	$result = array("returncode"=>$returncode,"msg"=>$msg,"returndata"=>$data);    	
    	$response = new Response(json_encode($result));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }
    
    //查看我的热线回复(热线解决方案)
    public function Api_HotLineReplyAction()
    {
      $da = $this->get('we_data_access');
    	$user = $this->get('security.context')->getToken()->getUser()->getUserName();
    	$request = $this->getRequest();
    	$hotid = $request->get("hotid");
    	$sql = "select scheme reply,date_format(date,'%Y-%m-%d %H:%i') reply_date,scheme_staff reply_staff,isread new_reply from mb_hotline_scheme where hotid=? order by isread asc,date desc;";
    	$para = array((string)$hotid);
    	$returncode = ReturnCode::$SUCCESS;
    	$msg = "";
    	$data = array();
    	try
    	{
    		 $ds = $da->GetData("table",$sql,$para);
    		 $data = $ds["table"]["rows"];
    	}
    	catch(\Exception $e){
    		$returncode = ReturnCode::$SYSERROR;
    		$this->get("logger")->err($e->getMessage());
    		$msg = "查询热线回复失败！";
    	}
    	$result = array("returncode"=>$returncode,"msg"=>$msg,"returndata"=>$data);    	
    	$response = new Response(json_encode($result));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }
    
    //编辑热线处理方案
    public function Api_EditSchemeAction()
    {
    	$da = $this->get('we_data_access');
    	$request = $this->getRequest();
    	$id = $request->get("schemeid");
    	$hotid = $request->get("hotid");
    	$content = $request->get("content");
    	$date = $request->get("date");
    	$staff = $request->get("staff");
    	if (empty($date))
    	  $date = date("Y-m-d H:i");
    	if (empty($staff))
    	  $staff = $this->get('security.context')->getToken()->getUser()->nick_name;
    	$staffid = $this->get('security.context')->getToken()->getUser()->getUserName();
    	$sqls = array();
    	$paras = array();
    	if (empty($id)){
    		$id = SysSeq::GetSeqNextValue($da,"mb_hotline_scheme","id");
    		$sql = "insert into mb_hotline_scheme(id,hotid,scheme,date,scheme_staff,staffid)values(?,?,?,?,?,?);";
    		$para = array((string)$id,(string)$hotid,(string)$content,(string)$date,(string)$staff,$staffid);
    		array_push($sqls,$sql);
    		array_push($paras,$para);
    	}
    	else{
    		$sql = "update mb_hotline_scheme set scheme=?,date=? where id=?";
    		$para = array((string)$content,(string)$date,(string)$id);
        array_push($sqls,$sql);
    		array_push($paras,$para);
    	}
    	$returncode = ReturnCode::$SUCCESS;
    	$msg = "";
    	try
    	{
    		 $da->ExecSQLS($sqls,$paras);
    	}
    	catch(\Execption $e){
    		$returncode = ReturnCode::$SYSERROR;
    		$this->get("logger")->err($e->getMessage());
    		$msg = "编辑热线处理方案失败！";
    	}
    	$result = array("returncode"=>$returncode,"msg"=>$msg);
    	$response = new Response(json_encode($result));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }
}