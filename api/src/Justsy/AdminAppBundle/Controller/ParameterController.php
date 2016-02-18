<?php

namespace Justsy\AdminAppBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Common\Utils;

class ParameterController extends Controller
{    
   public function IndexAction()
   {
        $da = $this->get("we_data_access");
        $request = $this->getrequest();
        $fileElementName=$request->get("filename");
        $success = true;$msg="";$fileid="";
        try 
        {
            $filename=$_FILES[$fileElementName]['name'];
            $filesize=$_FILES[$fileElementName]['size'];
            $filetemp=$_FILES[$fileElementName]['tmp_name'];	
            $dm = $this->get('doctrine.odm.mongodb.document_manager');         
            $fileid = Utils::saveFile($filetemp,$dm);
            if(empty($fileid))
            {
                $success = false;
                $msg ='文件上传失败';
            }
        }
        catch(\Exception $e){
            $this->logger->err($e->getMessage());
            $msg =$e->getMessage();
        }
        
        
        
        $result = array("success"=>$success,"msg"=>$msg,"fileid"=>$fileid);
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'text/html');
        return $response;
        
      
      
   	  //return $this->render('JustsyAdminAppBundle:Sys:parameter.html.twig');
   }
   
   //保存系统参数配置信息
   public function SaveAction()
   {
   	  $da = $this->get('we_data_access');
   	  $request = $this->getRequest();
   	  $data = $request->get("data");
   	  $sqls = array();
   	  $paras = array();
   	  $success = true;$msg = "";
   	  for($i=0; $i< count($data);$i++)
   	  {
   	  	$input = $data[$i];
   	  	$type = $input["type"];
   	  	$exists = $this->exists($type);
   	  	$number = $input["number"];
   	  	$number = empty($number) ? null : $number;
   	  	$unit  =  $input["unit"];
   	  	$unit  =  empty($unit) ? null : $unit;
   	  	$self  =  $input["self"];
   	  	$org   =  $input["org"];
   	  	$other = $input["other"];
   	  	$other = empty($other) ? null : $other;
   	  	$sendmessage = $input["sendmessage"];
   	  	$sendtype = $input["sendtype"];
   	  	$sendtype = rtrim($sendtype,",");
   	  	$sql="";
   	  	$para = array();
   	  	if ($exists){
   	  		$sql="update mb_parameter set number=?,unit=?,self=?,org=?,other=?,sendmessage=?,sendtype=? where type=?";
   	  		$para = array($number,$unit,$self,$org,$other,$sendmessage,$sendtype,$type);
   	  	}
   	  	else{
   	  		$sql="insert into mb_parameter(type,number,unit,self,org,other,sendmessage,sendtype)values(?,?,?,?,?,?,?,?);";
   	  		$para = array($type,$number,$unit,$self,$org,$other,$sendmessage,$sendtype);
   	  	}
   	  	array_push($sqls,$sql);
   	  	array_push($paras,$para);
   	  }
   	  if ( count($sqls)>0){
   	  	try{
   	  		 $da->ExecSQLS($sqls,$paras);
   	  	}
   	  	catch(\Exception $e){
   	  		$this->get("logger")->err($e->getMessage());
   	  		$msg = "编辑参数信息失败！";
   	  		$success = false;
   	  	}
   	  }
   	  $result = array("success"=>$success,"msg"=>$msg);
   	  $response = new Response(json_encode($result));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
   }
   
   //获得系统参数记录
   public function viewAction()
   {
   	  $da = $this->get('we_data_access');
   	  $request = $this->getRequest();
   	  $type    = $request->get("type");
   	  $data = array();
   	  $success = true;
   	  $msg = "";
   	  $sql = "select * from mb_parameter where type=?";
   	  try
   	  {
	   	  $ds = $da->GetData("table",$sql,array((string)$type));
	   	  if ( $ds && $ds["table"]["recordcount"]>0)
	   	    $data = $ds["table"]["rows"];
   	  }
   	  catch(\Exception $e){
   	  	$this->get("logger")->err($e->getMessage());
   	  	$msg = "查询数据记录失败！";
   	  	$success = false;
   	  }
   	  $result = array("success"=>$success,"msg"=>$msg,"datasource"=>$data);
   	  $response = new Response(json_encode($result));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
   	  
   }   
   
   //判断参数类型是否已经存在
   private function exists($type)
   {
   	  $result = false;
   	  $da = $this->get('we_data_access');
   	  $sql = "select 1 from mb_parameter where type=?";
   	  $ds = $da->GetData("table",$sql,array((string)$type));
   	  if ( $ds && $ds["table"]["recordcount"]>0)
   	    $result = true;
   	  return $result;
   }
}
