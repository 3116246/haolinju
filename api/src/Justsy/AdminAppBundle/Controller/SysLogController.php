<?php

namespace Justsy\AdminAppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;


class SysLogController extends Controller
{    
   public function IndexAction()
   {
   	  $init = $this->GetInit();
   	  $mindate = $init["mindate"];
   	  $maxdate = $init["maxdate"];
   	  $data = json_encode($init["data"]);
   	  return $this->render('JustsyAdminAppBundle:Sys:syslog.html.twig',array("mindate"=>$mindate,"maxdate"=>$maxdate,"syslog_type"=>$data));
   }

   //获得初始化数据
   public function GetInit()
   {
   	  $mindate = "";$maxdate = "";$data = array();
   	  $da = $this->get('we_data_access');
   	  $sql = "select date_format(min(date),'%Y-%m-%d') mindate,curdate() maxdate from mb_syslog;";
   	  $ds = $da->GetData("table",$sql);
   	  if ( $ds && $ds["table"]["recordcount"]>0) {
   	  	 $mindate = $ds["table"]["rows"][0]["mindate"];
   	  	 $maxdate = $ds["table"]["rows"][0]["maxdate"];
   	  }
   	  $sql = "select type from mb_syslog group by type";
   	  $ds = $da->GetData("table",$sql);
   	  if ($ds && $ds["table"]["recordcount"]>0)
   	    $data = $ds["table"]["rows"];  	    
   	  return array("mindate"=>$mindate,"maxdate"=>$maxdate,"data"=>$data);
   }

   //添加日志信息
   public function AddSysLog($desc,$type)
	 {	    
	 	  $user = $this->get('security.context')->getToken()->getUser();
	 	  $login_account = $user->getUserName();
			$da = $this->get('we_data_access');
			$sql = "insert into mb_syslog(date,description,staff,type)value(now(),?,?,?);";
			$para = array($desc,$login_account,$type);
			$success =true;
			try{
				 $da->ExecSQL($sql,$para);
			}
			catch(\Exception $e){
				$this->get("logger")->err($e->getMessage());
				$success = false;
			}
			return $success;		
	 }
	 
	 //搜索日志信息
	 public function SearcySysLogAction()
	 {	 	
	 	  $da = $this->get('we_data_access');
	 	  $request = $this->getRequest();
	 	  $type = $request->get("type");
	 	  $startdate = $request->get("startdate");
	 	  $enddate   = $request->get("enddate");
	 	  $staff = $request->get("staff");
	 	  $pageindex = $request->get("pageindex");
	 	  $record =    $request->get("record");
	 	  $limit = " limit ".(($pageindex - 1) * $record).",".$record;
	 	  $success = true;
	 	  $msg = "";
	 	  $datasource = array();
	 	  $recordcount = 0;
	 	  $user = $this->get('security.context')->getToken()->getUser();
	 	  $eno = $user->eno;
	 	  try
	 	  {
 	  	  $condition = "";$paras = array();
 	  	  $sql = "select logid,date_format(date,'%Y-%m-%d %H:%i') date,description,nick_name work_num,nick_name,type 
 	  	          from mb_syslog inner join we_staff on staff=login_account ";
 	  	  $condtion = " where eno=? ";
 	  	  array_push($paras,$eno);
 	  	  if ( !empty($staff)){
 	  	  	if (strlen($staff)>mb_strlen($staff,'utf8'))
	 	        $condition .= " and nick_name like concat(?,'%') ";
	 	      else
	 	 	      $condition .= " and login_account like concat('%',?,'%') ";
 	  	    array_push($paras,$staff);
 	  	  }
 	      if ( !empty($startdate) && !empty($enddate)){
 	      	 $condition .= " and date between ? and date_add(?,interval 1 day)";
 	      	 array_push($paras,(string)$startdate);
 	      	 array_push($paras,(string)$enddate);
 	      }
 	      else if ( !empty($startdate)){
 	      	$condition .= " and date >=? ";
 	      	array_push($paras,(string)$startdate);
 	      }
 	      else if (!empty($enddate)){
 	      	$condition .= " and date <= date_add(?,interval 1 day)";
 	      	array_push($paras,(string)$enddate);
 	      }
 	      if (!empty($type)){
 	      	$condition .= " and type=?";
 	      	array_push($paras,$type);
 	      } 	      
 	      $sql .= $condition." order by date desc ".$limit;
 	      $ds = null;
 	      if ( count($paras)>0)
 	      	$ds = $da->GetData("table",$sql,$paras);
 	      else
 	      	$ds = $da->GetData("table",$sql);
 	      $datasource = $ds["table"]["rows"];
 	      if ( $pageindex==1){
 	      	$sql = "select count(*) total from mb_syslog ".$condition;
 	      	if ( count($paras)>0 )
 	      	  $ds = $da->GetData("tj",$sql,$paras);
 	      	else
 	      	  $ds = $da->GetData("tj",$sql); 	      	
 	      	$recordcount = $ds["tj"]["rows"][0]["total"];
 	      }
	 	  }
	 	  catch(\Exception $e){
	 	  	$success = false;
	 	  	$msg = "查询日志记录失败！";
	 	  	$this->get("logger")->err($e->getMessage());
	 	  }
	 	  $result = array("success"=>$success,"msg"=>$msg,"datasource"=>$datasource,"recordcount"=>$recordcount);
      $response = new Response(json_encode($result));
      $response->headers->set('Content-Type', 'text/json');
      return $response;	 	  
	 }
}
