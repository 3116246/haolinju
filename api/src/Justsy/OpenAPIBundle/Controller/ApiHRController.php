<?php

namespace Justsy\OpenAPIBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Common\SendMessage;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Management\Staff;
use Justsy\BaseBundle\Management\Dept;
use Justsy\OpenAPIBundle\Controller\ApiController;

class ApiHRController extends Controller
{
	//新增部门接口
	public function org_addAction()
	{
		$da = $this->get("we_data_access");
		$da_im = $this->get('we_data_access_im');
		$request = $this->getRequest();
		//访问权限校验
		$api = new \Justsy\OpenAPIBundle\Controller\ApiController();
      	$api->setContainer($this->container);

    	$isWeFaFaDomain = $api->checkWWWDomain(); 
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $api->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   	$re = array("returncode"=>"9999");
			    $re["code"]="err0105";
    	   	   	$re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
 				return $this->responseJson($request,$re);
    	   }
    	}
    	$openid = $request->get("openid");
		$staffinfo = new \Justsy\BaseBundle\Management\Staff($da,$da_im,$openid,$this->get("logger"),$this->container);
		$staffdata = $staffinfo->getInfo();
		if(empty($staffdata))
		{
			$result = Utils::WrapResultError("无效操作帐号");
			return $this->responseJson($request,$result);
		}
		$eno = $staffdata["eno"];
		$deptid = $request->get("deptid");     //部门id
		$p_deptid = $request->get("p_deptid"); //上级部门id
		$pname = '';
		$deptinfo = new Dept($da,$da_im,$this->container);
		if(!empty($p_deptid) && preg_match("/[\x80-\xff]/",$p_deptid))
		{
			$pname = $p_deptid;			
			$deptAry = $deptinfo->getIdByName($eno,$p_deptid);
			if(empty($deptAry))
			{
			    $result = array("returncode"=>"9999","msg"=>"无效的上级部门名称");
			    return $this->responseJson($request,$result);
			}
			else
			{
			    $p_deptid = $deptAry["deptid"];
			}
		}
		else if(!empty($p_deptid))
		{
			$deptdata = $deptinfo->getinfo($p_deptid);
			if(empty($deptdata))
			{
			    $result = array("returncode"=>"9999","msg"=>"无效的上级部门编号");
			    return $this->responseJson($request,$result);
			}
			$pname = $deptdata['deptname'];
		}

		
		$deptname = $request->get("deptname"); //部门名称
		$deptinfo = array("eno"=>$eno,"deptid"=>$deptid,"deptname"=>$deptname,"pid"=>$p_deptid,'pname'=>$pname);
				
		$dept = new Dept($da,$da_im,$this->container);
		$result = $dept->createBatchDepartment($eno,array( $deptinfo));
        if ($result["returncode"]=="0000"){
        	$da->__destruct();
        	$da_im->__destruct();
        	unset($da);
        	unset($da_im);
          	//操作成功发送出席
          	$da_c = new \Justsy\BaseBundle\DataAccess\DataAccess($this->container,'default');
	    	$da_im_c=new \Justsy\BaseBundle\DataAccess\DataAccess($this->container,'im');
			
	        $sendMessage = new SendMessage($da_c,$da_im_c);
	        $msg = json_encode(Utils::WrapMessage('createDept',$deptinfo,array()));
	        $parameter = array("eno"=>$eno,"flag"=>"all","title"=>"createDept","message"=>$msg,"container"=>$this->container);
	        $sendMessage->sendImMessage($parameter);
        }
		return $this->responseJson($request,$result);		
	}

	//批量添加部门。可应用于导入
	public function org_batch_addAction()
	{
		$request = $this->getRequest();
		$da = $this->get("we_data_access");
    	$da_im = $this->get("we_data_access_im");
		$deptinfo = $request->get("deptinfo");//格式:[{deptid:"",deptname:"",pid:"",pname:"",order:""}]
		if(empty($deptinfo))
		{
			return $this->responseJson($request,array("returncode"=>"9999","msg"=>"无效的参数[deptinfo]"));
		}
		$deptinfo = json_decode($deptinfo,true);
		if(empty($deptinfo))
		{
			return $this->responseJson($request,array("returncode"=>"9999","msg"=>"参数[deptinfo]只支持json格式"));
		}
		$openid = $request->get("openid");
		$staffinfo = new \Justsy\BaseBundle\Management\Staff($da,$da_im,$openid,$this->get("logger"),$this->container);
		$staffdata = $staffinfo->getInfo();
		if(empty($staffdata))
		{
			$result = Utils::WrapResultError("无效操作帐号");
			return $this->responseJson($request,$result);
		}		
		//访问权限校验
		$api = new \Justsy\OpenAPIBundle\Controller\ApiController();
      	$api->setContainer($this->container);

    	$isWeFaFaDomain = $api->checkWWWDomain(); 
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $api->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   	$re = array("returncode"=>"9999");
			    $re["code"]="err0105";
    	   	   	$re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
 				return $this->responseJson($request,$re);
    	   }
    	}
		$dept = new Dept($da,$da_im,$this->container);
		if(count($deptinfo)>1000)
		{
			$weburl = $this->container->getParameter('open_api_url');
			$admin_openid = 'admin@'.$this->container->getParameter('edomain');
			//导入部门太多（>1000）时，采用线程
			$dir =explode('src',  __DIR__);
			$command = $dir[0].'app/threads.sh "dept" "'.(str_replace('"', '\"', json_encode($deptinfo)).'" "'.$weburl.'" "'.$admin_openid.'"');
			$data = shell_exec($command);
			$result = Utils::WrapResultOK($data);
		}
		else
		{
			$result = $dept->createBatchDepartment($staffdata["eno"],$deptinfo);
		}
        if ($result["returncode"]=='0000'){
          //操作成功发送出席
          $sendMessage = new SendMessage($da,$da_im);
          $msg = json_encode(Utils::WrapMessage('createDept',$result,array()));
          $parameter = array("eno"=>$staffdata['eno'],"flag"=>"online","title"=>"createDept","message"=>$msg,"container"=>$this->container);
          $sendMessage->sendImMessage($parameter);
        }
		return $this->responseJson($request,$result);
	}

	//删除部门接口
	//1、通知企业所有在线设备。通知类型：removeDept
	public function org_delAction()
	{
		$da = $this->get("we_data_access");
		$da_im = $this->get('we_data_access_im');
		$request = $this->getRequest();
		//访问权限校验
		$api = new \Justsy\OpenAPIBundle\Controller\ApiController();
        $api->setContainer($this->container);

    	$isWeFaFaDomain = $api->checkWWWDomain(); 
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $api->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   	$re = array("returncode"=>"9999");
			    $re["code"]="err0105";
    	   	   	$re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
 				return $this->responseJson($request,$re);
    	   }
    	}	
    	$openid = $request->get("openid");
		$staffinfo = new \Justsy\BaseBundle\Management\Staff($da,$da_im,$openid,$this->get("logger"),$this->container);
		$staffdata = $staffinfo->getInfo();
		if(empty($staffdata))
		{
			$result = Utils::WrapResultError("无效操作帐号");
			return $this->responseJson($request,$result);
		}
		$eno = $staffdata["eno"];    	
		$deptid = $request->get("deptid");
		if(empty($deptid))
		{
			return $this->responseJson($request,array("returncode"=>"9999","msg"=>"删除的部门编号或名称不能为空"));
		}
		$dept = new Dept($da,$da_im,$this->container);
		if(preg_match("/[\x80-\xff]/",$deptid))
		{
			$deptAry = $dept->getIdByName($eno,$deptid);
			if(empty($deptAry))
			{
			    $result = array("returncode"=>"9999","msg"=>"无效的部门名称");
			    return $this->responseJson($request,$result);
			}
			else
			{
			    $deptid = $deptAry["deptid"];
			}
		}
		$result = $dept->delDepartment($deptid);
		if ($result["returncode"]=="0000"){
		  //操作成功发送出席
		  $sendMessage = new SendMessage($da,$da_im);
		  $msg = json_encode(Utils::WrapMessage('removeDept',array('deptid'=>$deptid),array()));
		  $parameter = array("eno"=>$eno,"flag"=>"all","title"=>"removeDept","message"=>$msg,"container"=>$this->container);
		  $sendMessage->sendImMessage($parameter);
		}
		return $this->responseJson($request,$result);
	}

	//编辑部门接口
	//1、通知企业所有在线设备。通知类型：editDept
	public function org_editAction()
	{
		$da = $this->get("we_data_access");
		$da_im = $this->get('we_data_access_im');
		
		$request = $this->getRequest();
		//访问权限校验
		$api = new \Justsy\OpenAPIBundle\Controller\ApiController();
      	$api->setContainer($this->container);

    	$isWeFaFaDomain = $api->checkWWWDomain(); 
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $api->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   	$re = array("returncode"=>"9999");
			    $re["code"]="err0105";
    	   	   	$re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
 				return $this->responseJson($request,$re);
    	   }
    	}
    	$openid = $request->get("openid");
		$staffinfo = new \Justsy\BaseBundle\Management\Staff($da,$da_im,$openid,$this->get("logger"),$this->container);
		$staffdata = $staffinfo->getInfo();
		if(empty($staffdata))
		{
			$result = Utils::WrapResultError("无效操作帐号");
			return $this->responseJson($request,$result);
		}
		$eno = $staffdata["eno"];
		$deptid = $request->get("deptid");
		$p_deptid = $request->get("p_deptid");
		$deptname = $request->get("deptname");
		$deptinfo = new \Justsy\BaseBundle\Management\Dept($this->get('we_data_access'),$this->get('we_data_access_im'),$this->container);
		if(!empty($p_deptid) && preg_match("/[\x80-\xff]/",$p_deptid))
		{
			$deptAry = $deptinfo->getIdByName($eno,$p_deptid);
			if(empty($deptAry))
			{
			    $result = array("returncode"=>"9999","msg"=>"无效的上级部门名称");
			    return $this->responseJson($request,$result);
			}
			else
			{
			    $p_deptid = $deptAry["deptid"];
			}
		}
		if(!empty($deptid) && preg_match("/[\x80-\xff]/",$deptid))
		{
			$deptAry = $deptinfo->getIdByName($eno,$deptid);
			if(empty($deptAry))
			{
			    $result = array("returncode"=>"9999","msg"=>"无效的部门名称");
			    return $this->responseJson($request,$result);
			}
			else
			{
			    $deptid = $deptAry["deptid"];
			}
		}
		$parameter = array("eno"=>$eno,"deptid"=>$deptid,"p_deptid"=>$p_deptid,"deptname"=>$deptname);

        $has = $deptinfo->getinfo($deptid);
        if(!empty($has))
		  $result = $deptinfo->updateDepartment($parameter);
        else
        {
            $result = $deptinfo->createDepartment($parameter);
        }
		if ((boolean)$result["success"]){
		  //操作成功发送出席
		  $sendMessage = new SendMessage($da,$da_im);
		  $msg = json_encode(Utils::WrapMessage('editDept',$parameter,array()));
		  $parameter = array("eno"=>$eno,"flag"=>"online","title"=>"editDept","message"=>$msg,"container"=>$this->container);
		  $sendMessage->sendImMessage($parameter);
		}
		return $this->responseJson($request,$result);				
	}

	public function org_queryAction()
	{
		$request = $this->getRequest();
		$limit = $request->get("limit");//获取记录数
		if(empty($limit)) $limit=50;
		$lastid = $request->get("lastid");//获取记录的起始位置
		if(empty($lastid)) $lastid=0;
		$openid = $request->get("openid");
		$deptid = $request->get("deptid");
		$search = $request->get("search");
		$da=$this->get('we_data_access');
		$da_im=$this->get('we_data_access_im');
		$staffinfo = new \Justsy\BaseBundle\Management\Staff($da,$da_im,$openid,$this->get("logger"),$this->container);
		$staffdata = $staffinfo->getInfo();
		if(empty($staffdata))
		{
			$result = Utils::WrapResultError("无效操作帐号");
			return $this->responseJson($request,$result);
		}
		$eno = $staffdata["eno"];
		$deptinfo = new \Justsy\BaseBundle\Management\Dept($da,$da_im,$this->container);
		if(!empty($deptid) && preg_match("/[\x80-\xff]/",$deptid))
		{			
			$deptAry = $deptinfo->getIdByName($eno,$deptid);
			if(empty($deptAry))
			{
			    $result = Utils::WrapResultError("无效的部门名称");
			    return $this->responseJson($request,$result);
			}
			else
			{
			    $deptid = $deptAry["fafa_deptid"];
			}
		}
		
		$data = $deptinfo->getChild($eno,$deptid,$search);
		$result = Utils::WrapResultOK($data);
		
		return $this->responseJson($request,$result);		
	}

	public function org_impAction()
	{
		$request = $this->getRequest();  	
		$openid = $request->get("openid");
		$upfile = $request->files->get("filedata");
		if(empty($upfile))
		{
			return $this->responseJson($request,Utils::WrapResultError("未上传文件"));
		}
		$tmpPath = $upfile->getPathname();
		$size = $upfile->getSize();
		if($size>1024*1024*5)
		{
			return $this->responseJson($request,Utils::WrapResultError("文件大小超过5M"));
		}
	    $da=$this->get('we_data_access');
		$da_im=$this->get('we_data_access_im');		
		$staffinfo = new \Justsy\BaseBundle\Management\Staff($da,$da_im,$openid,$this->get("logger"),$this->container);
		$staffdata = $staffinfo->getInfo();
		if(empty($staffdata))
		{
			$result = Utils::WrapResultError("无效操作帐号");
			return $this->responseJson($request,$result);
		}
		$eno = $staffdata["eno"];		
		$filename = $upfile->getClientOriginalName();		
		$fixedType = explode(".",strtolower($filename));		
		$fixedType = $fixedType[count($fixedType)-1];	
		if($fixedType!="xlsx" && $fixedType!="xls")
	  	{
	  		return Utils::WrapResultError("文件格式不正确，只支持xlsx和xls。");
	  	}
		$newfile = $_SERVER['DOCUMENT_ROOT']."/upload/dept_".rand(10000,99999).".".$fixedType;
		$field_name = array();
		$field_value = array();
	    $msg = "";
	    $success = true;
	    $recordcount = 0;
	    $totalpage = 0;
	    if(move_uploaded_file($tmpPath,$newfile)) {
	    	$deptinfo = new \Justsy\BaseBundle\Management\Dept($da,$da_im,$this->container);		
		  	$result=$deptinfo->import($eno,$newfile);
		  	unlink($newfile);
	        if ($result["returncode"]=="0000"){
	        	$da->__destruct();
	        	$da_im->__destruct();
	        	unset($da);
	        	unset($da_im);
	          	//操作成功发送出席
	          	$da_c = new \Justsy\BaseBundle\DataAccess\DataAccess($this->container,'default');
		    	$da_im_c=new \Justsy\BaseBundle\DataAccess\DataAccess($this->container,'im');
				
		        $sendMessage = new SendMessage($da_c,$da_im_c);
		        $msg = json_encode(Utils::WrapMessage('createDept',$result,array()));
		        $parameter = array("eno"=>$eno,"flag"=>"all","title"=>"createDept","message"=>$msg,"container"=>$this->container);
		        $sendMessage->sendImMessage($parameter);
	        }		  	
	    }
	    else{
	    	$result = Utils::WrapResultError("文件上传失败！");
	    }    
	    return $this->responseJson($request,$result);		
	}

	public function org_infoAction()
	{
		$request = $this->getRequest();
		$openid = $request->get("openid");
		$deptid = $request->get("deptid"); 
		$da=$this->get('we_data_access');
		$da_im=$this->get('we_data_access_im');
		$openid = $request->get("openid");
		$staffinfo = new \Justsy\BaseBundle\Management\Staff($da,$da_im,$openid,$this->get("logger"),$this->container);
		$staffdata = $staffinfo->getInfo();
		if(empty($staffdata))
		{
			$result = Utils::WrapResultError("无效操作帐号");
			return $this->responseJson($request,$result);
		}
		$eno = $staffdata["eno"];
		if(empty($deptid)) $deptid = 'v'.$eno;
		$deptinfo = new \Justsy\BaseBundle\Management\Dept($da,$da_im,$this->container);
		if(!empty($deptid) && preg_match("/[\x80-\xff]/",$deptid))
		{			
			$deptAry = $deptinfo->getIdByName($eno,$deptid);
			if(empty($deptAry))
			{
			    $result = Utils::WrapResultError("无效的部门名称");
			    return $this->responseJson($request,$result);
			}
			else
			{
			    $deptid = $deptAry["fafa_deptid"];
			}
		}
		
		$data = $deptinfo->getinfo($deptid);
		$result = Utils::WrapResultOK($data);
		
		return $this->responseJson($request,$result);		
	}

	public function staff_impAction()
	{
		$request = $this->getRequest();  	
		$openid = $request->get("openid");
		$upfile = $request->files->get("filedata");
		if(empty($upfile))
		{
			return $this->responseJson($request,Utils::WrapResultError("未上传文件"));
		}
		$tmpPath = $upfile->getPathname();
		$size = $upfile->getSize();
		if($size>1024*1024*5)
		{
			return $this->responseJson($request,Utils::WrapResultError("文件大小超过5M"));
		}
	    $da=$this->get('we_data_access');
		$da_im=$this->get('we_data_access_im');		
		$staffinfo = new \Justsy\BaseBundle\Management\Staff($da,$da_im,$openid,$this->get("logger"),$this->container);
		$staffdata = $staffinfo->getInfo();
		if(empty($staffdata))
		{
			$result = Utils::WrapResultError("无效操作帐号");
			return $this->responseJson($request,$result);
		}
		$eno = $staffdata["eno"];		
		$filename = $upfile->getClientOriginalName();		
		$fixedType = explode(".",strtolower($filename));		
		$fixedType = $fixedType[count($fixedType)-1];	
		if($fixedType!="xlsx" && $fixedType!="xls")
	  	{
	  		return Utils::WrapResultError("文件格式不正确，只支持xlsx和xls。");
	  	}
		$newfile = $_SERVER['DOCUMENT_ROOT']."/upload/staff_".rand(10000,99999).".".$fixedType;
		$field_name = array();
		$field_value = array();
	    $msg = "";
	    $success = true;
	    $recordcount = 0;
	    $totalpage = 0;
	    if(move_uploaded_file($tmpPath,$newfile)) {
		    $fixedType = explode(".",basename($filename));
		  	$fixedType = $fixedType[count($fixedType)-1];
		  	$objReader = \PHPExcel_IOFactory::createReader( $fixedType=="xlsx"? 'Excel2007':"Excel5"); 
		    $objPHPExcel = $objReader->load($newfile);
			$objWorksheet = $objPHPExcel->getActiveSheet();
			//导入的表格列一定要与该字段属性位置匹配
			$keys = array("realName","account","sex","deptid","duty","mobile","ldap_uid","passWord");
		    $excelDataObj = array();
		    $i=false;
		 	foreach ($objWorksheet->getRowIterator() as $row) 
		 	{
		 		if(!$i)
		 		{
		 			$i=true;
		 			continue;
		 		}
		        $cellIterator = $row->getCellIterator();
		        $cellIterator->setIterateOnlyExistingCells(false);
		        $item=array();
		        $cell_pos=0;
		        foreach ($cellIterator as $cell) {
		        	if(!$keys[$cell_pos]) break;
		        	$item[$keys[$cell_pos]]=$cell->getCalculatedValue();		        	
		        	$cell_pos++;
		        }
		        if(empty($item['account'])||empty($item['realName'])||empty($item['passWord']))
		        {
		        	continue;
		        }
		        $excelDataObj[] = $item;
		    }
		    if(empty($excelDataObj)) return Utils::WrapResultError("文件内容为空");
		   	$active = new \Justsy\BaseBundle\Controller\ActiveController();
		    $active->setContainer($this->container);  
		   	if(count($excelDataObj)>200)
			{
				$weburl = $this->container->getParameter('open_api_url');
				$admin_openid = 'admin@'.$this->container->getParameter('edomain');
				//导入人员太多（>200）时，采用线程
				$dir =explode('src',  __DIR__);
				$command = $dir[0].'app/threads.sh "staff" "'.(str_replace('"', '\"', json_encode($excelDataObj)).'" "'.$weburl.'" "'.$admin_openid.'"');
				$data = shell_exec($command);
				$result = Utils::WrapResultOK($data);
			}
		    else
		    {
		    	$result = $active->doBatchSave($staffdata["eno"],$excelDataObj);
		    }
		  	unlink($newfile);
	     	if($result["returncode"]=="0000")
	     	{
	          	//操作成功发送出席
				
		        $sendMessage = new SendMessage($da,$da_im);
	            $senddata = $result;
				$msg = json_encode(Utils::WrapMessage('newstaff',$senddata,array()));
	            $parameter = array("eno"=>$staffdata["eno"],"flag"=>"all","title"=>"newstaff","message"=>$msg,"container"=>$this->container);
	            $sendMessage->sendImMessage($parameter);
	     	}		  	
	    }
	    else{
	    	$result = Utils::WrapResultError("文件上传失败！");
	    }    
	    return $this->responseJson($request,$result);		
	}

	public function staff_countAction()
	{
		$request = $this->getRequest();
		$openid = $request->get("openid");
		$deptid = $request->get("deptid");
		$search = $request->get("search");
		$da=$this->get('we_data_access');
		$da_im=$this->get('we_data_access_im');
		$staffinfo = new \Justsy\BaseBundle\Management\Staff($da,$da_im,$openid,$this->get("logger"),$this->container);
		$staffdata = $staffinfo->getInfo();
		if(empty($staffdata))
		{
			$result = Utils::WrapResultError("无效操作帐号");
			return $this->responseJson($request,$result);
		}
		if(!empty($deptid) && preg_match("/[\x80-\xff]/",$deptid))
		{
			$deptinfo = new \Justsy\BaseBundle\Management\Dept($da,$da_im,$this->container);
			$eno = $staffdata["eno"];
			$deptAry = $deptinfo->getIdByName($eno,$deptid);
			if(empty($deptAry))
			{
			    $result = Utils::WrapResultError("无效的部门名称");
			    return $this->responseJson($request,$result);
			}
			else
			{
			    $deptid = $deptAry["deptid"];
			}
		}
		
		$data = $staffinfo->querySearchCount($deptid,"1",$search);
		$result = Utils::WrapResultOK($data);
		
		return $this->responseJson($request,$result);
	}

	//查询人员接口
	public function staff_queryAction()
	{
		$request = $this->getRequest();
		$limit = $request->get("limit");//获取记录数
		if(empty($limit)) $limit=50;
		$lastid = $request->get("lastid");//获取记录的起始位置，合适手机下拉查询
		if(empty($lastid)) $lastid=0;
		$pageno = $request->get("page_num");//获取记录的起始位置，合适翻页查询
		if(!empty($pageno))
		{
			$pageno=(int)$pageno;
			$lastid = ($pageno-1)*(int)$limit;
		}
		$openid = $request->get("openid");
		$deptid = $request->get("deptid");
		$search = $request->get("search");
		$da=$this->get('we_data_access');
		$da_im=$this->get('we_data_access_im');
		$staffinfo = new \Justsy\BaseBundle\Management\Staff($da,$da_im,$openid,$this->get("logger"),$this->container);
		$staffdata = $staffinfo->getInfo();
		if(empty($staffdata))
		{
			$result = Utils::WrapResultError("无效操作帐号");
			return $this->responseJson($request,$result);
		}
		if(!empty($deptid) && preg_match("/[\x80-\xff]/",$deptid))
		{
			$deptinfo = new \Justsy\BaseBundle\Management\Dept($da,$da_im,$this->container);
			$eno = $staffdata["eno"];
			$deptAry = $deptinfo->getIdByName($eno,$deptid);
			if(empty($deptAry))
			{
			    $result = Utils::WrapResultError("无效的部门名称");
			    return $this->responseJson($request,$result);
			}
			else
			{
			    $deptid = $deptAry["deptid"];
			}
		}
		
		$data = $staffinfo->querySearchBaseInfo($deptid,"1",$search,$limit,$lastid);
		$result = Utils::WrapResultOK($data);
		
		return $this->responseJson($request,$result);
	}

	//新增/注册人员接口	
	//staffinfo格式：[{},{},...]
	public function staff_addAction()
	{
		$request = $this->getRequest();
		$da = $this->get("we_data_access");
    	$da_im = $this->get("we_data_access_im");
		$staffinfo = $request->get("staffinfo");
		if(empty($staffinfo))
		{
			return $this->responseJson($request,array("returncode"=>"9999","msg"=>"无效的参数[staffinfo]"));
		}
		$stafflist = json_decode($staffinfo,true);
		if(empty($stafflist))
		{
			return $this->responseJson($request,array("returncode"=>"9999","msg"=>"参数[staffinfo]只支持json格式"));
		}
		if(empty($stafflist[0]['eno']))
		{
			$openid = $request->get("openid");
			$staffinfo = new \Justsy\BaseBundle\Management\Staff($da,$da_im,$openid,$this->get("logger"),$this->container);
			$staffdata = $staffinfo->getInfo();
			if(empty($staffdata))
			{
				$result = Utils::WrapResultError("无效操作帐号");
				return $this->responseJson($request,$result);
			}
		}
		else
		{
			$staffdata = $stafflist[0];
		}
		//访问权限校验
		$api = new \Justsy\OpenAPIBundle\Controller\ApiController();
      	$api->setContainer($this->container);

    	$isWeFaFaDomain = $api->checkWWWDomain(); 
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $api->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   	$re = array("returncode"=>"9999");
			    $re["code"]="err0105";
    	   	   	$re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
 				return $this->responseJson($request,$re);
    	   }
    	}
	    $active = new \Justsy\BaseBundle\Controller\ActiveController();
	    $active->setContainer($this->container);  
		if(count($stafflist)>200)
		{
			$weburl = $this->container->getParameter('open_api_url');
			$admin_openid = 'admin@'.$this->container->getParameter('edomain');
			//导入人员太多（>200）时，采用线程
			$dir =explode('src',  __DIR__);
			$command = $dir[0].'app/threads.sh "staff" "'.(str_replace('"', '\"', json_encode($stafflist)).'" "'.$weburl.'" "'.$admin_openid.'"');
			$data = shell_exec($command);
			$result = Utils::WrapResultOK($data);
		}
		else
		{	    
			
	    	$result = $active->doBatchSave($staffdata["eno"],$stafflist);
		}
     	if($result["returncode"]=="0000")
     	{
          	//操作成功发送出席			
	        $sendMessage = new SendMessage($da,$da_im);
            $senddata = $staffdata;
			$msg = json_encode(Utils::WrapMessage('newstaff',$senddata,array()));
            $parameter = array("eno"=>$staffdata["eno"],"flag"=>"all","title"=>"newstaff","message"=>$msg,"container"=>$this->container);
            $sendMessage->sendImMessage($parameter);
     	}
   	
     	return $this->responseJson($request,$result);
	}

	//删除人员接口
	//1、通知企业所有在线设备。通知类型：removeStaff
	public function staff_delAction()
	{
		$request = $this->getRequest();
		$staff = $request->get("staff");
		if(empty($staff))
		{
			return $this->responseJson($request,array("returncode"=>"9999","msg"=>"无效的参数[staff]"));
		}		
		//访问权限校验
		$api = new \Justsy\OpenAPIBundle\Controller\ApiController();
      	$api->setContainer($this->container);

    	$isWeFaFaDomain = $api->checkWWWDomain(); 
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $api->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   	$re = array("returncode"=>"9999");
			    $re["code"]="err0105";
    	   	   	$re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
 				return $this->responseJson($request,$re);
    	   }
    	}
	  	if ( !strpos($staff,"@") && !Utils::validateMobile($staff))
	  	{ 
	  		$domain =  $this->container->getParameter('edomain');
			$staff .= "@".$domain;
	  	}     	
    	//业务处理
    	$staffinfo = new \Justsy\BaseBundle\Management\Staff($da,$this->get("we_data_access_im"),$staff,$this->get("logger"),$this->container);
		$staffdata = $staffinfo->getInfo();
		if(empty($staffdata))
		{
			return $this->responseJson($request,array("returncode"=>"9999","msg"=>"人员不存在或已禁用"));
		}
		$result = $staffinfo->leave();
     	if($result===true)
     	{
			$msg = json_encode(Utils::WrapMessage('removeStaff',$staffdata,array()));
     		//操作成功发送出席
		  	$sendMessage = new SendMessage($da,$this->get('we_data_access_im'));
		  	$parameter = array("eno"=>$staffdata["eno"],"flag"=>"all","title"=>"removeStaff","message"=>$msg,"container"=>$this->container);
		  	$sendMessage->sendImMessage($parameter);
     		$result = array("returncode"=>"0000","msg"=>"");
     	}
     	else
     		$result = array("returncode"=>"9999","msg"=>"");
     	return $this->responseJson($request,$result);	
	}

	//禁用人员接口
	//1、通知企业所有在线设备。通知类型：disabledStaff
	public function staff_disabledAction()
	{
		$request = $this->getRequest();
		$staff = $request->get("staff");
		if(empty($staff))
		{
			return $this->responseJson($request,array("returncode"=>"9999","msg"=>"无效的参数[staff]"));
		}		
		//访问权限校验
		$api = new \Justsy\OpenAPIBundle\Controller\ApiController();
      	$api->setContainer($this->container);

    	$isWeFaFaDomain = $api->checkWWWDomain(); 
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $api->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   	$re = array("returncode"=>"9999");
			    $re["code"]="err0105";
    	   	   	$re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
 				return $this->responseJson($request,$re);
    	   }
    	}
	  	if ( !strpos($staff,"@") && !Utils::validateMobile($staff))
	  	{ 
	  		$domain =  $this->container->getParameter('edomain');
			$staff .= "@".$domain;
	  	}     	
    	//业务处理
    	$staffinfo = new \Justsy\BaseBundle\Management\Staff($da,$this->get("we_data_access_im"),$staff,$this->get("logger"),$this->container);
		$staffdata = $staffinfo->getInfo();
		if(empty($staffdata))
		{
			return $this->responseJson($request,array("returncode"=>"9999","msg"=>"人员不存在或已禁用"));
		}
        $result = $staffinfo->disable();
     	if($result===true)
     	{
     		$msg = json_encode(Utils::WrapMessage('disabledStaff',$staffdata,array()));
     		//操作成功发送出席
		  	$sendMessage = new SendMessage($da,$this->get('we_data_access_im'));
		  	$parameter = array("eno"=>$staffdata["eno"],"flag"=>"onself","title"=>"disabledStaff","message"=>$msg,"container"=>$this->container);
		  	$sendMessage->sendImMessage($parameter);
     		$result = array("returncode"=>"0000","msg"=>"");
     	}
     	else
     		$result = array("returncode"=>"9999","msg"=>"");
     	return $this->responseJson($request,$result);
	}

	//启用人员接口
	public function staff_enabledAction()
	{
		$request = $this->getRequest();
		$staff = $request->get("staff");
		if(empty($staff))
		{
			return $this->responseJson($request,array("returncode"=>"9999","msg"=>"无效的参数[staff]"));
		}		
		//访问权限校验
		$api = new \Justsy\OpenAPIBundle\Controller\ApiController();
      	$api->setContainer($this->container);

    	$isWeFaFaDomain = $api->checkWWWDomain(); 
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $api->checkAccessToken($request,$da);
    	   if(!$token)
    	   {
    	   	   	$re = array("returncode"=>"9999");
			    $re["code"]="err0105";
    	   	   	$re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
 				return $this->responseJson($request,$re);
    	   }
    	}
	  	if ( !strpos($staff,"@") && !Utils::validateMobile($staff))
	  	{ 
	  		$domain =  $this->container->getParameter('edomain');
			$staff .= "@".$domain;
	  	}
    	//业务处理
    	$staffinfo = new \Justsy\BaseBundle\Management\Staff($da,$this->get("we_data_access_im"),$staff,$this->get("logger"),$this->container);
		$staffdata = $staffinfo->getInfo();
		if(empty($staffdata))
		{
			return $this->responseJson($request,array("returncode"=>"9999","msg"=>"人员不存在或已禁用"));
		}		
		$result = $staffinfo->enabled();
     	if($result===true)
     	{
     		$result = array("returncode"=>"0000","msg"=>"");
     	}
     	else
     		$result = array("returncode"=>"9999","msg"=>"");
     	return $this->responseJson($request,$result);
	}

	public function staff_modifyAction()
	{
		$request = $this->getRequest();
		$staff = trim($request->get("staff"));
		$nick_name = trim($request->get("nick_name"));
		$deptid = trim($request->get("deptid"));
		$duty = trim($request->get("duty"));
		$sex = trim($request->get("sex"));
		$mobile = trim($request->get("mobile"));
		$desc = trim($request->get("desc"));
		if(empty($staff))
		{
			return $this->responseJson($request,array("returncode"=>"9999","msg"=>"无效的参数[staff]"));
		}
		//访问权限校验
		$api = new \Justsy\OpenAPIBundle\Controller\ApiController();
      	$api->setContainer($this->container);

    	$isWeFaFaDomain = $api->checkWWWDomain(); 
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $api->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   	$re = array("returncode"=>"9999");
			    $re["code"]="err0105";
    	   	   	$re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
 				return $this->responseJson($request,$re);
    	   }
    	}
	  	if ( !strpos($staff,"@") && !Utils::validateMobile($staff))
	  	{ 
	  		$domain =  $this->container->getParameter('edomain');
			$staff .= "@".$domain;
	  	}		
    	//业务处理
    	$staffinfo = new \Justsy\BaseBundle\Management\Staff($da,$this->get("we_data_access_im"),$staff,$this->get("logger"),$this->container);
		$staffdata = $staffinfo->getInfo();
		if(empty($staffdata))
		{
			return $this->responseJson($request,array("returncode"=>"9999","msg"=>"人员不存在或已禁用"));
		}
		if(!empty($deptid) && preg_match("/[\x80-\xff]/",$deptid))
		{
			$deptinfo = new \Justsy\BaseBundle\Management\Dept($da,$da_im,$this->container);
			$eno = $staffdata["eno"];
			$deptAry = $deptinfo->getIdByName($eno,$deptid);
			if(empty($deptAry))
			{
			    $result = Utils::WrapResultError("无效的部门名称");
			    return $this->responseJson($request,$result);
			}
			else
			{
			    $deptid = $deptAry["deptid"];
			}
		}
		if(empty($mobile)) $mobile=null;
		if(empty($desc)) $desc=null;
		if(empty($duty)) $duty=null;
		if(empty($deptid)) $deptid=null;
		if(empty($nick_name)) $nick_name=null;
		if(empty($sex)) $sex=null;
		$result = $staffinfo->checkAndUpdate($nick_name,$mobile,$deptid,$duty,null,$sex,$desc);

     	if($result===true)
     	{
		  	$result = array("returncode"=>"0000","msg"=>"");
        }
     	else
     		$result = array("returncode"=>"9999","msg"=>"");
     	return $this->responseJson($request,$result);

	}

	//人员密码更新接口
	//1、通知该人员所有在线设备。通知类型：staff-changepasswod
	public function staff_modifypasswordAction()
	{
		$request = $this->getRequest();
		$staff = $request->get("staff");
		$newpass = $request->get("newpass");
		if(empty($staff))
		{
			return $this->responseJson($request,array("returncode"=>"9999","msg"=>"无效的参数[staff]"));
		}
		if(empty($newpass))
		{
			return $this->responseJson($request,array("returncode"=>"9999","msg"=>"无效的登录密码参数[newpass]"));
		}		
		//访问权限校验
		$api = new \Justsy\OpenAPIBundle\Controller\ApiController();
      	$api->setContainer($this->container);

    	$isWeFaFaDomain = $api->checkWWWDomain(); 
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $api->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   	$re = array("returncode"=>"9999");
			    $re["code"]="err0105";
    	   	   	$re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
 				return $this->responseJson($request,$re);
    	   }
    	}
	  	if ( !strpos($staff,"@") && !Utils::validateMobile($staff))
	  	{ 
	  		$domain =  $this->container->getParameter('edomain');
			$staff .= "@".$domain;
	  	}     	
    	//业务处理
    	$staffinfo = new \Justsy\BaseBundle\Management\Staff($da,$this->get("we_data_access_im"),$staff,$this->get("logger"),$this->container);
		$staffdata = $staffinfo->getInfo();
		if(empty($staffdata))
		{
			return $this->responseJson($request,array("returncode"=>"9999","msg"=>"人员不存在或已禁用"));
		}		
		$result = $staffinfo->changepassword($staff,$newpass,$this->get("security.encoder_factory"));
     	if($result["returncode"]=="0000")
     	{
     		$msg = json_encode(Utils::WrapMessage('staff-changepasswod',$staffdata,array()));
     		//操作成功发送出席
		  	$sendMessage = new SendMessage($da,$this->get('we_data_access_im'));
		  	$parameter = array("flag"=>"onself",'myjid'=>$staffdata['jid'],'fromjid'=>$staffdata['jid'],"title"=>"staff-changepasswod","message"=>$msg,"container"=>$this->container);
		  	$sendMessage->sendImPresence($parameter);
     	}
     	return $this->responseJson($request,$result);
	}	
	//人员昵称更新接口
	//1、通知该人员所有在线设备。通知类型：
	//2、通知所有好友
	public function staff_modifyNicknameAction()
	{
		$request = $this->getRequest();
		$staff = $request->get("staff");
		$nick_name = $request->get("nick_name");
		if(empty($staff))
		{
			return $this->responseJson($request,array("returncode"=>"9999","msg"=>"无效的参数[staff]"));
		}		
		if(empty($nick_name))
		{
			return $this->responseJson($request,array("returncode"=>"9999","msg"=>"无效的人员姓名参数[nick_name]"));
		}
		//访问权限校验
		$api = new \Justsy\OpenAPIBundle\Controller\ApiController();
      	$api->setContainer($this->container);

    	$isWeFaFaDomain = $api->checkWWWDomain(); 
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $api->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   	$re = array("returncode"=>"9999");
			    $re["code"]="err0105";
    	   	   	$re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
 				return $this->responseJson($request,$re);
    	   }
    	}
	  	if ( !strpos($staff,"@") && !Utils::validateMobile($staff))
	  	{ 
	  		$domain =  $this->container->getParameter('edomain');
			$staff .= "@".$domain;
	  	}    	
    	//业务处理
    	$staffinfo = new \Justsy\BaseBundle\Management\Staff($da,$this->get("we_data_access_im"),$staff,$this->get("logger"),$this->container);
		$staffdata = $staffinfo->getInfo();
		if(empty($staffdata))
		{
			return $this->responseJson($request,array("returncode"=>"9999","msg"=>"人员不存在或已禁用"));
		}
		$result = $staffinfo->checkAndUpdate($nick_name);

     	if($result===true)
     	{
		  	$result = array("returncode"=>"0000","msg"=>"");
        }
     	else
     		$result = array("returncode"=>"9999","msg"=>"");
     	return $this->responseJson($request,$result);
	}

	//人员部门变更接口
	//1、通知该人员所有在线设备。通知类型：changedept
	//2、通知人员原部门成员和新部门成员
	public function staff_deptChangeAction()
	{
		$request = $this->getRequest();
		$staff = $request->get("staff");
		$newdeptid = $request->get("newdeptid");
		if(empty($staff))
		{
			return $this->responseJson($request,array("returncode"=>"9999","msg"=>"无效的参数[staff]"));
		}		
		if(empty($newdeptid))
		{
			return $this->responseJson($request,array("returncode"=>"9999","msg"=>"无效的部门名称参数[newdeptid]"));
		}
		//访问权限校验
		$api = new \Justsy\OpenAPIBundle\Controller\ApiController();
      	$api->setContainer($this->container);
    	$isWeFaFaDomain = $api->checkWWWDomain(); 
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	    $token = $api->checkAccessToken($request,$da);	
    	    if(!$token)
    	    {
    	   	   	$re = array("returncode"=>"9999");
			    $re["code"]="err0105";
    	   	   	$re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
 				return $this->responseJson($request,$re);
    	    }
    	}
	  	if ( !strpos($staff,"@") && !Utils::validateMobile($staff))
	  	{ 
	  		$domain =  $this->container->getParameter('edomain');
			$staff .= "@".$domain;
	  	}    	
    	//业务处理
    	$staffinfo = new \Justsy\BaseBundle\Management\Staff($da,$this->get("we_data_access_im"),$staff,$this->get("logger"),$this->container);
		$staffdata = $staffinfo->getInfo();
		if(empty($staffdata))
		{
			return $this->responseJson($request,array("returncode"=>"9999","msg"=>"人员不存在或已禁用"));
		}
		if(preg_match("/[\x80-\xff]/",$newdeptid))
		{
		   	//如果是部门名称，获取对应的部门编号
		    $deptinfo = new \Justsy\BaseBundle\Management\Dept($da,$this->get('we_data_access_im'),$this->container);
		    $deptAry = $deptinfo->getIdByName($staffdata["eno"],$newdeptid);
		    if(empty($deptAry))
		    {
		    	$result = array("returncode"=>"9999","msg"=>"无效的部门名称");
		    	return $this->responseJson($request,$result);
		    }
		    else
		    {
		    	$newdeptid = $deptAry["deptid"];
		    }
		}
    	$result = $staffinfo->checkAndUpdate(null,null,$newdeptid);
		$staffdata = $staffinfo->getInfo();
     	if($result===true)
     	{    		
     		$result = array("returncode"=>"0000","msg"=>"");
     	}
     	else
     		$result = array("returncode"=>"9999","msg"=>"");
     	return $this->responseJson($request,$result);
	}
	//人员绑定手机变更接口
	public function staff_mobileChangeAction()
	{
		$request = $this->getRequest();
		$staff = $request->get("staff");
		$newmobile = $request->get("newmobile");
		if(empty($staff))
		{
			return $this->responseJson($request,array("returncode"=>"9999","msg"=>"无效的参数[staff]"));
		}		
		if(empty($newmobile))
		{
			return $this->responseJson($request,array("returncode"=>"9999","msg"=>"无效的手机号参数[newmobile]"));
		}		
		//访问权限校验
		$api = new \Justsy\OpenAPIBundle\Controller\ApiController();
      	$api->setContainer($this->container);

    	$isWeFaFaDomain = $api->checkWWWDomain(); 
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $api->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   	$re = array("returncode"=>"9999");
			    $re["code"]="err0105";
    	   	   	$re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
 				return $this->responseJson($request,$re);
    	   }
    	}
	  	if ( !strpos($staff,"@") && !Utils::validateMobile($staff))
	  	{ 
	  		$domain =  $this->container->getParameter('edomain');
			$staff .= "@".$domain;
	  	}    	
    	//业务处理
    	$staffinfo = new \Justsy\BaseBundle\Management\Staff($da,$this->get("we_data_access_im"),$staff,$this->get("logger"),$this->container);
		$staffdata = $staffinfo->getInfo();
		if(empty($staffdata))
		{
			return $this->responseJson($request,array("returncode"=>"9999","msg"=>"人员不存在或已禁用"));
		}		
		$result = $staffinfo->checkAndUpdate(null,$newmobile);
     	if($result===true)
     		$result = array("returncode"=>"0000","msg"=>"");
     	else
     		$result = array("returncode"=>"9999","msg"=>"");
     	return $this->responseJson($request,$result);
	}

	//人员登录帐号变更接口
	//1、通知该人员所有在线设备。通知类型：
	public function staff_loginAccountChangeAction()
	{
		$request = $this->getRequest();
		$staff = $request->get("staff");
		$newloginAccount = $request->get("newloginAccount");
		if(empty($staff))
		{
			return $this->responseJson($request,array("returncode"=>"9999","msg"=>"无效的参数[staff]"));
		}		
		if(empty($newloginAccount))
		{
			return $this->responseJson($request,array("returncode"=>"9999","msg"=>"无效的帐号参数[newloginAccount]"));
		}		
		//访问权限校验
		$api = new \Justsy\OpenAPIBundle\Controller\ApiController();
      	$api->setContainer($this->container);

    	$isWeFaFaDomain = $api->checkWWWDomain(); 
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $api->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   	$re = array("returncode"=>"9999");
			    $re["code"]="err0105";
    	   	   	$re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
 				return $this->responseJson($request,$re);
    	   }
    	}
	  	if ( !strpos($staff,"@") && !Utils::validateMobile($staff))
	  	{ 
	  		$domain =  $this->container->getParameter('edomain');
			$staff .= "@".$domain;
	  	}
    	//业务处理
    	$staffinfo = new \Justsy\BaseBundle\Management\Staff($da,$this->get("we_data_access_im"),$staff,$this->get("logger"),$this->container);
		$staffdata = $staffinfo->getInfo();
		if(empty($staffdata))
		{
			return $this->responseJson($request,array("returncode"=>"9999","msg"=>"人员不存在或已禁用"));
		}		
		$result = $staffinfo->changeLoginAccount($newloginAccount,$this->get('security.encoder_factory'));
     	return $this->responseJson($request,$result);
	}


  	public function staff_photoChangeAction()
  	{
    	$re = array();
    	$request = $this->getRequest();
		//访问权限校验
		$api = new \Justsy\OpenAPIBundle\Controller\ApiController();
      	$api->setContainer($this->container);

    	$isWeFaFaDomain = $api->checkWWWDomain(); 
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $api->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   	$re = array("returncode"=>"9999");
			    $re["code"]="err0105";
    	   	   	$re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
 				return $this->responseJson($request,$re);
    	   }
    	}
    	$staffinfo = new \Justsy\BaseBundle\Management\Staff($da,$this->get("we_data_access_im"),$request->get('openid'),$this->get("logger"),$this->container);
		$staffdata = $staffinfo->getInfo();
		if(empty($staffdata))
		{
			return $this->responseJson($request,array("returncode"=>"9999","msg"=>"无效的操作人员"));
		}
	    $dm = $this->get('doctrine.odm.mongodb.document_manager');
	    $da = $this->get("we_data_access");
	    $login_account = $staffdata['login_account'];
	    $photofile = $_FILES['photofile']['tmp_name'];
	    if(empty($photofile)){
	        $photofile = tempnam(sys_get_temp_dir(), "we");
	        unlink($photofile);
	        $somecontent1 = base64_decode($request->get('photodata'));
	        if ($handle = fopen($photofile, "w+")) {
	          if (!fwrite($handle, $somecontent1) == FALSE) {   
	              fclose($handle);  
	          }
	        }
	    }
	    $photofile_24 = $photofile."_24";
	    $photofile_48 = $photofile."_48";
	    try 
	    {
	      if (empty($photofile))
	      { 
	      	return $this->responseJson($request,Utils::WrapResultError('头像未上传'));
	  	  }
	      $im = new \Imagick($photofile);
	      $im->scaleImage(48, 48);
	      $im->writeImage($photofile_48);
	      $im->destroy();
	      $im = new \Imagick($photofile);
	      $im->scaleImage(24, 24);
	      $im->writeImage($photofile_24);
	      $im->destroy();

	      $table = $da->GetData("staff","select photo_path,photo_path_small,photo_path_big 
	        from we_staff where login_account=?",array((string)$login_account));
	      if ($table && $table["staff"]["recordcount"] > 0)  //如果用户原来有头像则删除
	      {
	        Utils::removeFile($table["staff"]["rows"][0]["photo_path"],$dm);
	        Utils::removeFile($table["staff"]["rows"][0]["photo_path_small"],$dm);
	        Utils::removeFile($table["staff"]["rows"][0]["photo_path_big"],$dm);
	      }
	      if (!empty($photofile)) $photofile = Utils::saveFile($photofile,$dm);
	      if (!empty($photofile_48)) $photofile_48 = Utils::saveFile($photofile_48,$dm);
	      if (!empty($photofile_24)) $photofile_24 = Utils::saveFile($photofile_24,$dm);
	      $da->ExecSQL("update we_staff set photo_path=?,photo_path_big=?,photo_path_small=? 
	        where login_account=?",
	        array((string)$photofile_48, (string)$photofile, (string)$photofile_24, (string)$login_account));
	      
	      $staffinfo->syncAttrsToIM();
	      
	      /*$message =json_encode(Utils::WrapMessage('staff-changeinfo',array('jid'=>$staffdata['jid'],"photo_path" => $this->container->getParameter('FILE_WEBSERVER_URL').$photofile)));// json_encode(array("path" => $this->container->getParameter('FILE_WEBSERVER_URL').$photofile));
	      $sendMessage = new \Justsy\BaseBundle\Common\SendMessage($this->get("we_data_access"),$this->get("we_data_access_im"));

	      $sendMessage->sendImMessage($staffdata['jid'],implode(",", $staffinfo->getFriendJidList()),"staff-changeinfo",$message,$this->container,"","",false,Utils::$systemmessage_code);        
	      */
		  $sendMessage = new SendMessage($this->get("we_data_access"),$this->get("we_data_access_im"));
	      $msg = json_encode(Utils::WrapMessage('staff-changeinfo',array('jid'=>$staffdata['jid'],"photo_path" => $this->container->getParameter('FILE_WEBSERVER_URL').$photofile)));
	      $parameter = array("eno"=>$staffdata['eno'],"flag"=>"all","title"=>"staff-changeinfo","message"=>$msg,"container"=>$this->container);
	      $sendMessage->sendImMessage($parameter);

	      $path = $this->container->getParameter('FILE_WEBSERVER_URL');
	      $re["fileid"] = $photofile;
	      $re["photo_path"] = $path.$photofile;
	      return $this->responseJson($request,Utils::WrapResultOK($re));
	    }
	    catch (\Exception $e) 
	    {
	      $this->get('logger')->err($e);
	      return $this->responseJson($request,Utils::WrapResultError($e->getMessage()));
	    }
  	}	


   /**
  *模糊查询联系人staff
  */
  public function staff_searchAction()
  {
    $request=$this->getRequest();
	//访问权限校验
	$api = new \Justsy\OpenAPIBundle\Controller\ApiController();
    $api->setContainer($this->container);

   	$isWeFaFaDomain = $api->checkWWWDomain(); 
    $da = $this->get("we_data_access");
    if(!$isWeFaFaDomain)
    {
    	$token = $api->checkAccessToken($request,$da);	
    	if(!$token)
    	{
    	   	$re = array("returncode"=>"9999");
			$re["code"]="err0105";
    	   	$re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
 			return $this->responseJson($request,$re);
    	}
    }
	$openid = $request->get("openid");
	$staffinfo = new \Justsy\BaseBundle\Management\Staff($da,$this->get("we_data_access_im"),$openid,$this->get("logger"),$this->container);
	$staffdata = $staffinfo->getInfo();

    $cur_account=$staffdata["login_account"];
    $search=$request->get("search");
    $eno = $staffdata["eno"];
    $pagesize=$request->get("pagesize","20");
    $page=$request->get("page","0");
    $da=$this->get("we_data_access");    
    //群成员fafa_jid
    $group_staff = array();
    $groupid = $request->get("groupid");    
    $action = $request->get("action"); //擴展參數。用于限制搜索員工時的范圍。默認為不限制
    //action:暫時支持創建群 成員搜索
    if($action=="create_group")
    {

    }
    else if ($action=="search_group_staff"){  //搜索群成员
    	if ( !empty($groupid)){
	    	$da_im = $this->get("we_data_access_im");
	    	$sql_im = "select employeeid from im_groupemployee where groupid=?;";
	    	$ds_im = $da_im->GetData('groupemployee',$sql_im,array((string)$groupid));
	    	if ( $ds_im && $ds_im["groupemployee"]["recordcount"]>0){
	    		for($i=0;$i< $ds_im["groupemployee"]["recordcount"];$i++){
	    	    array_push($group_staff,(string)$ds_im["groupemployee"]["rows"][$i]["employeeid"]);
	    	  }
	    	}
      }
    }
    $re= array('returncode' => ReturnCode::$SUCCESS);
    if(empty($cur_account)){
      return $this->responseJson($request,Utils::WrapResultError("查询条件不能为空"));
    }

    try {
    	$fileurl=$this->container->getParameter("FILE_WEBSERVER_URL");
        $orderKey = "login_account";
        $sql="";
        $base_sql=" select a.openid, a.login_account,a.fafa_jid jid, a.nick_name, concat('{$fileurl}',a.photo_path_big) photo_path,
                           ep.dept_name eshortname,0 ingroup,ifnull(self_desc,'') self_desc
                    from we_staff  a    inner join we_department ep on a.eno=ep.eno and a.dept_id=ep.dept_id";
        if (!empty($eno))
            $base_sql .= " and ep.eno={$eno} ";
        if (preg_match("/^1\d{5,}/", $search))  //输入6个及以上数字，采用手机号查询
        {
          $sql.=$base_sql." where a.mobile like '{$search}%' ";
          $orderKey = "mobile";
        }
        else if(!empty($search))
        {
            $sql.=$base_sql;
            $array=explode("@",$search);
            if(strlen($search)==mb_strlen($search,"utf-8"))
            {
              if(!empty($array)&&count($array)>1)
              {
                  $sql.=" where a.login_account like CONCAT('{$search}','%') ";
                  $orderKey = "login_account";
              }else
              {
                  $sql.=" where a.login_account like CONCAT('{$search}','%')";
                  $sql.=" union ".$base_sql." where a.nick_name like CONCAT('{$search}','%') ";
                  $orderKey = "nick_name";             
              }
            }
            else
            {
                $sql.=" where a.nick_name like CONCAT('{$search}','%') ";
                $orderKey = "nick_name";
            }
        }
        else
        {
            $sql.=$base_sql." where 1=1 ";
        }
        $sql.=" and not exists(select 1 from we_micro_account  where we_micro_account.number=a.login_account)  and a.login_account!='".$cur_account."'";
        // $sql.=" ) staff_res where staff_res.attention<>-1 ";
        $sql.=" order by ".$orderKey;
        $page=((float)$page)*((float)$pagesize);
        $sql.=" limit {$page}, {$pagesize}";
          
        $dataset = $da->GetData("staffs",$sql);
        //处理数据
        if ( count($group_staff)>0 && $dataset && $dataset["staffs"]["recordcount"]>0){
        	for($i=0;$i< $dataset["staffs"]["recordcount"];$i++){
        		$jid = $dataset["staffs"]["rows"][$i]["jid"];
        		if ( in_array($jid,$group_staff)){
        		  $dataset["staffs"]["rows"][$i]["ingroup"] = "1";
        		}
        	}
        }
        $rows =  $dataset["staffs"]["rows"];
        $re['staffs']=$rows;
        return $this->responseJson($request,Utils::WrapResultOK($re));
    }
     catch (Exception $e) {
      $this->get('logger')->err($e);
      return $this->responseJson($request,Utils::WrapResultError($e->getMessage()));
    }
  }	

	private function getToken($appid,$appkey)
	{
		$code = $this->makeCode($appid,$appkey);
		$api = new ApiController();
		$json=$api->getProxySession($appid,$code,"test-fafa-app");
		return $json["access_token"];
	}
	private function makeCode($appid,$appkey)
	{
		return strtolower(md5($appid.$appkey));
	}


	private function getLink($uniqid) {
		$web_url=$this->container->getParameter('open_api_url');
		return $web_url.'/api/http/getpagepath/'.$uniqid;
	}

	private function responseJson($request,$re)
	{
		$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		$response->headers->set('Content-Type', 'text/json');	
		return $response;
	}
	
}