<?php

namespace Justsy\AdminAppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Common\DES;

class BuffetController extends Controller
{    
    public function IndexAction()
    {
    	return $this->render('JustsyAdminAppBundle:Buffet:setting.html.twig');
    }
    
    //编辑自助设置信息
    public function settingEditAction()
    {
    	$da = $this->get('we_data_access');
    	$request = $this->getRequest();
    	$id = $request->get("id");
    	$keyword = $request->get("keyword");
    	$title = $request->get("title");
    	$content = $request->get("content");    	
    	$keyword = empty($keyword) ? null :$keyword;
    	$title =   empty($title) ? null : $title;
    	$content = empty($content) ? null : $content;
    	$sql = "";
    	$para = array();
    	if ( empty($id) || $id=="0"){
    		 $currUser = $this->get('security.context')->getToken();
		     $staffid = $currUser->getUser()->getUserName();
    		 $id = SysSeq::GetSeqNextValue($da,"mb_buffet","id");
    		 $sql = "insert into mb_buffet(id,keyword,title,content,create_date,create_staffid)values(?,?,?,?,now(),?)";
    		 $para = array((string)$id,$keyword,$title,$content,$staffid);
    	}
    	else{
    		 $sql = "update mb_buffet set keyword=?,title=?,content=? where id=?";
    		 $para = array($keyword,$title,$content,(string)$id);    		 
    	}
    	$success = true;$message = "";
    	try
    	{
    		 $da->ExecSQL($sql,$para);
    	}
    	catch (\Exception $e){
    		$this->get("logger")->err($e->getMessage());
    		$success = false;
    		$message = "编辑数据错误，请重试！";
    	}
    	$result = array("success"=>$success,"message"=>$message,"id"=>$id);
    	$response = new Response(json_encode($result));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }
    
    //搜索自助查询
    public function settingSearchAction()
    {
    	$da = $this->get('we_data_access');
    	$request = $this->getRequest();
    	$keyword = $request->get("keyword");    	
    	$pageindex = (int)$request->get("pageindex");
  	  $record = (int)$request->get("record");
  	  $success = true;
  	  $msg = "";
  	  $datasource = array();
  	  $recordcount = 0;  
  	  $limit = " limit ".(($pageindex - 1) * $record).",".$record;
  	  $condition = "";
    	$sql = "select id,date_format(create_date,'%Y-%m-%d %H:%i') date,keyword,nick_name 
    	        from mb_buffet a inner join we_staff on create_staffid=login_account";
    	$para = array();
    	if (!empty($keyword)){
    		$condition = " where keyword like concat(?,'%')";
    		$para = array((string)$keyword);
    	}
    	try
    	{
	    	$ds = null;
	    	$sql = $sql.$condition." order by create_date desc ".$limit;
	    	if ( count($para)>0)
	    		$ds = $da->GetData("table",$sql,$para);
	    	else
	    		$ds = $da->GetData("table",$sql);
	      $datasource = $ds["table"]["rows"];
	      //当为第一页时，求出总数
	      if ($pageindex==1){
	      	$sql = "select count(*) total from mb_buffet ".$condition;
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
    
    //查询数据记录
    private function getBuffetData($keyword)
    {
    	$da = $this->get('we_data_access');
    	$sql = "select id,date_format(create_date,'%Y-%m-%d %H:%i') date,keyword,nick_name 
    	        from mb_buffet a inner join we_staff on create_staffid=login_account";
    	$para = array();
    	if (!empty($keyword)){
    		$sql .= " where keyword like concat(?,'%')";
    		$para = array((string)$keyword);
    	}
    	$data = array();
    	$success = true;
    	$message = "";
    	try
    	{
	    	$ds = null;
	    	$sql .=" order by create_date desc;";
	    	if ( count($para)>0)
	    		$ds = $da->GetData("table",$sql,$para);
	    	else
	    		$ds = $da->GetData("table",$sql);
	      $data = $ds["table"]["rows"];
	    }
	    catch (\Exception $e){
	    	$this->get("logger")->err($e->getMessage());
	    	$message = "查询数据失败！";
	    	$success = false;
	    }
	    $result = array("success"=>$success,"message"=>$message,"datasource"=>$data);
	    return $result;
    }
    
    //根据id查询详细
    public function SearchByIdAction()
    {    	
    	 $da = $this->get('we_data_access');
    	 $request = $this->getRequest();
    	 $id = $request->get("id");
    	 $sql = "select * from mb_buffet where id=?";
    	 $para = array((string)$id);
    	 $success = true;$message="";
    	 $data = array();
    	 try
    	 {
    	 	  $ds = $da->GetData("table",$sql,$para);
    	 	  $data = $ds["table"]["rows"];    	 	 
    	 }
    	 catch (\Exception $e){
    	 	 $this->get("logger")->err($e->getMessage());
    	 	 $success = false;
    	 	 $message = "查询数据失败！";
    	 }
    	 $result = array("success"=>$success,"message"=>$message,"data"=>$data);
    	 $response = new Response(json_encode($result));
       $response->headers->set('Content-Type', 'text/json');
       return $response;
    }
    
    //删除自助设置信息
    public function deleteBuffetAction()
    {
    	 $da = $this->get('we_data_access');
    	 $request = $this->getRequest();
    	 $id = $request->get("id");
    	 $sql = "delete from mb_buffet where id=?";
    	 $success = true;
    	 $msg = "";
    	 try{
    	 	 $para = array((string)$id);
    	 	 $da->ExecSQL($sql,$para);
    	 }
    	 catch (\Exception $e){
    	 	 $this->get("logger")->err($e->getMessage());
    	 	 $success = false;
    	 	 $msg = "删除自助设置信息失败！";
    	 }
    	 $result = array("success"=>$success,"msg"=>$msg);
    	 $response = new Response(json_encode($result));
       $response->headers->set('Content-Type', 'text/json');
       return $response;
    }
    
    //------------------------------------------接口部门----------------------------------
    //用户自助查询接口
    public function searchByKeyAction()
    {
    	 $da = $this->get('we_data_access');
    	 $request = $this->getRequest();
    	 $keyword = $request->get("keyword");
    	 $keyword = trim($keyword);
    	 $returncode = ReturnCode::$SUCCESS;
    	 $data = array();$msg="";
    	 if ( empty($keyword)){
    	 	 $returncode = ReturnCode::$OTHERERROR;
    	 	 $msg = "请输入查询关键字！";
    	 }
    	 else{
    	 	 $sql = "select title,content from mb_buffet where keyword like concat(?,'%')";
    	 	 $para = array((string)$keyword);
    	 	 try{
    	 	 	 $ds = $da->GetData("table",$sql,$para);
    	 	 	 $data = $ds["table"]["rows"];
    	 	 	 //成功查询成功后统计关键字被查询次数
    	 	 	 $sql = "select id from mb_buffet_keyword where keyword=?";
    	 	 	 $ds = $da->GetData("table",$sql,$para);
    	 	 	 if ( $ds && $ds["table"]["recordcount"]>0)
    	 	 	 	 $sql = "update mb_buffet_keyword set last_date=now(),timer=timer+1 where keyword=?";
    	 	 	 else
    	 	 	 	 $sql = "insert into mb_buffet_keyword(keyword,last_date,timer)values(?,now(),1);";
    	 	 	 try{
    	 	 	   $da->ExecSQL($sql,$para);
    	 	   }
    	 	   catch (\Exception $e){ }
    	 	 }
    	 	 catch (\Exception $e){
    	 	 	 $returncode = ReturnCode::$SYSERROR;
    	 	 	 $this->get("logger")->err($e->getMessage());
    	 	 	 $msg = "查询关键字失败！";
    	 	 }    	 	     	 	 
    	 }
    	 $result = array("returncode"=>$returncode,"msg"=>$msg,"data"=>$data);
    	 $response = new Response(json_encode($result));
       $response->headers->set('Content-Type', 'text/json');
       return $response;
    }  
}