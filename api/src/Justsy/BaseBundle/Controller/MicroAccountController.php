<?php

namespace Justsy\BaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\BaseBundle\Management\MicroAccountMgr;
use Justsy\BaseBundle\Management\EnoParamManager;
use Symfony\Component\HttpFoundation\Request;

class MicroAccountController extends Controller {

    public function saveFile($filePath, $dm) {
        $doc = new \Justsy\MongoDocBundle\Document\WeDocument;
        $doc->setName(basename($filePath));
        $doc->setFile($filePath);
        $dm->persist($doc);
        $dm->flush();
        unlink($filePath);
        return $doc->getId();
    }

    public function deleteFile($fileId, $dm) {
        $doc = $dm->getRepository('JustsyMongoDocBundle:WeDocument')->find($fileId);
        if (!empty($doc)) {
            $dm->remove($doc);
            $dm->flush();
        }
        return true;
    }

    public function res($content, $type = 'html') {
        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/' . $type);
        $response->headers->set('charset', 'utf-8');
        return $response;
    }

    public function format_conv($data1) {
        $data = substr($data1, 1, strlen($data1) - 6);
        $data = json_decode($data);
        $data = get_object_vars($data);
        $data = $data['data'];
        return $data;
    }
    
    public function saveGroupAction()
    {
        $conn = $this->get('we_data_access');
        $conn_im = $this->get('we_data_access_im');
        $userinfo = $this->get('security.context')->getToken()->getUser();
        $logger=$this->get("logger");
         
        $res=$this->getRequest();
        $acc = $res->get("micro_account");
        $name = $res->get("name");
        $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
        $flag = $MicroAccountMgr->saveGroup($acc,$name);
        return $this->res(json_encode(array("returncode"=> $flag!=-1,"id"=> $flag)),'json');
    }
    
    public function checkgroupnameAction(){
    	$conn = $this->get('we_data_access');
        $conn_im = $this->get('we_data_access_im');
        $userinfo = $this->get('security.context')->getToken()->getUser();
        $logger=$this->get("logger");
         
        $res=$this->getRequest();
        $acc = $res->get("micro_account");
        $name = $res->get("name");
        $newname = $res->get("newname");
        $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
        $flag = $MicroAccountMgr->checkgroupname($acc,$name,$newname);
        return $this->res($flag,'json');
    }
    
    public function getGrouplistAction()
    {
        $conn = $this->get('we_data_access');
        $conn_im = $this->get('we_data_access_im');
        $userinfo = $this->get('security.context')->getToken()->getUser();
        $logger=$this->get("logger");
        
        $res=$this->getRequest();
        $acc = $res->get("micro_account");
        $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
        $data = $MicroAccountMgr->grouplist($acc);
        return $this->res(json_encode($data),'json');    	
    }
    
    public function removeGroupAction()
    {
        $conn = $this->get('we_data_access');
        $conn_im = $this->get('we_data_access_im');
        $userinfo = $this->get('security.context')->getToken()->getUser();
        $logger=$this->get("logger");
        
        $res=$this->getRequest();
        $groupid = $res->get("groupid");
        $rcount = $res->get("rcount");
        $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
        $flag = $MicroAccountMgr->deletegroup($groupid,$rcount);
        return $this->res(json_encode(array("returncode"=> $flag )),'json');    	
    }    
    
    public function updateGroupAction()
    {
        $conn = $this->get('we_data_access');
        $conn_im = $this->get('we_data_access_im');
        $userinfo = $this->get('security.context')->getToken()->getUser();
        $logger=$this->get("logger");
        
        $res=$this->getRequest();
        $groupid = $res->get("groupid");
        $acc = $res->get("micro_account");
        $name = $res->get("name");
        $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$userinfo, $this->container);
        $flag = $MicroAccountMgr->updateGroup($groupid,$acc,$name);
        return $this->res(json_encode(array("returncode"=> $flag )),'json');    	
    }
    
    public function getMemberListAction()
    {
        $conn = $this->get('we_data_access');
        $conn_im = $this->get('we_data_access_im');
        $userinfo = $this->get('security.context')->getToken()->getUser();
        $logger=$this->get("logger");
        
        $res=$this->getRequest();
        $groupid = $res->get("groupid");
        $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$userinfo, $this->container);
        $data = $MicroAccountMgr->groupMemberlist($groupid);
        return $this->res(json_encode($data),'json');     	
    }
    
    public function assignGroupMemberAction() 
    {
        $conn = $this->get('we_data_access');
        $conn_im = $this->get('we_data_access_im');
        $userinfo = $this->get('security.context')->getToken()->getUser();
        $logger=$this->get("logger");
        
        $res=$this->getRequest();
        $groupid = $res->get("groupid");
        $acc = $res->get("micro_account");
        $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$userinfo, $this->container);
        $flag = $MicroAccountMgr->assignGroup($groupid,explode(",",$acc));
        return $this->res(json_encode(array("returncode"=> $flag)),'json');     	
    }
    
   public function moveGroupMemberAction()
    {
        $conn = $this->get('we_data_access');
        $conn_im = $this->get('we_data_access_im');
        $userinfo = $this->get('security.context')->getToken()->getUser();
        $logger=$this->get("logger");
        
        $res=$this->getRequest();
        $groupid = $res->get("groupid");
        $micro_account = $res->get("micro_account");
        $check_login_accounts=$res->get("check_login_accounts");
        $url_groupid=$res->get("url_groupid");
        
        $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$userinfo, $this->container);
        $flag=0;
        if(empty($groupid)){
        	$flag = $MicroAccountMgr->deleteMembers(explode(",",$check_login_accounts),$micro_account,$url_groupid);
        }else{
        	$flag = $MicroAccountMgr->movememebers(explode(",",$check_login_accounts),$groupid,$micro_account);
        }
        return $this->res(json_encode(array("returncode"=> $flag)),'json');     	
    }
    
    //删除公众号
    public function microaccount_deleteAction()
    {
    		$conn = $this->get('we_data_access');
        $conn_im = $this->get('we_data_access_im');
        $userinfo = $this->get('security.context')->getToken()->getUser();
        $logger=$this->get("logger");
        $getRequest=$this->getRequest();
        
        $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
        $micro_id=$getRequest->get("micro_id");
        $microFlag =$MicroAccountMgr->removeByID($micro_id);
        $re["success"]=$microFlag;
        $re["micro_id"]= $micro_id;
        return $this->res(json_encode($re),'json');
    }
    
    //搜索公众号接口 暂时没用
    public function microaccount_searchAction(){
    		$conn = $this->get('we_data_access');
        $conn_im = $this->get('we_data_access_im');
        $userinfo = $this->get('security.context')->getToken()->getUser();
        $logger=$this->get("logger");
        $getRequest=$this->getRequest();
        
        $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
        $micro_search=$getRequest->get("txtsearch");
        $micro =$MicroAccountMgr->microaccount_search($micro_search);
        return $this->res(json_encode($micro),'json');
    }
    
    //公众号列表数据管理页面
    public function microitemAction($network_domain){
    		$conn = $this->get('we_data_access');
        $conn_im = $this->get('we_data_access_im');
        $userinfo = $this->get('security.context')->getToken()->getUser();
        $logger=$this->get("logger");
        
        $micro_data=$this->getRequest()->get("micro_data");
         
        $array["curr_network_domain"]=$network_domain;
        if(!empty($micro_data)){
        	$array["micro_data"]=$micro_data;
        }else{
        	$array["micro_data"]=array();
        }
        
        return $this->render('JustsyBaseBundle:EnterpriseSetting:microitem.html.twig', $array);
    }
    
    //粉丝页面
    public function microfansAction($network_domain){
    		$conn = $this->get('we_data_access');
        $conn_im = $this->get('we_data_access_im');
        $userinfo = $this->get('security.context')->getToken()->getUser();
        $logger=$this->get("logger");
        $getRequest=$this->getRequest();
        
        $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
        $micro_account=$getRequest->get("micro_number");
        $micro_data = $MicroAccountMgr->get_micro_data_account($micro_account);
        
        $micro_name=$micro_data["name"];
        $micro_concern_approval=$micro_data["concern_approval"];
        $micro_type=$micro_data["type"];
        $micro_page_size=$getRequest->get("micro_pagesize");
	  		$micro_page_index=$getRequest->get("micro_pageindex");
	  		$groupid=$getRequest->get("groupid");
	  		if(empty($groupid))$groupid=0;
	  		$txtsearch=$getRequest->get("txtsearch");
	  		if(empty($micro_account)){
	  			$txtsearch="";
	  			$micro_name="";
	  			$micro_concern_approval=false;
	  			$micro_type=0;
	  			$micro_page_size=10;
	  			$micro_page_index=1;
	  		}
	  		$micro_fans["micro_use"]=$micro_data["micro_use"];
	  		$micro_fans["micro_concern_approval"]=$micro_concern_approval;
	  		$micro_fans["micro_type"]=$micro_type;
	  		$micro_fans["micro_pageindex"]=$micro_page_index;
	  		$micro_fans["micro_page_size"]=$micro_page_size;
        $micro_fans["curr_network_domain"]=$network_domain;
        $micro_fans["txtsearch"]=$txtsearch;
        $micro_fans["micro_name"]=$micro_name;
        $micro_fans["micro_account"]=$micro_account; 
        $micro_fans["groupid"]=$groupid; 
        //var_dump($groupid);
        $data =$MicroAccountMgr->get_micro_fans($micro_account,$txtsearch,$micro_page_size,$micro_page_index,$groupid);
        //粉丝总记录数(当前分组)
        $micro_fans["micro_fans_count"]=$data["micro_fans_count"];
        //所有粉丝记录数
        $micro_fans["micro_fans_all_count"]=$MicroAccountMgr->getFansCount($micro_account);
        //最大页数
        $micro_fans["micro_page_max_index"]=$data["micro_page_max_index"];
        //粉丝列表
        $micro_fans["micro_fans_data"]=$data["micro_fans_data"];
        //var_dump($data["micro_fans_data"]);
        $micro_fans_ungrouped_count =$MicroAccountMgr->get_fans_ungrouped_count($micro_account);
        //未分组成员数
        $micro_fans["micro_fans_ungrouped_count"]=$MicroAccountMgr->getFansUngroupedCount($micro_account);
        $micro_fans["micro_fans_max_count"]=$micro_fans_ungrouped_count["max_count"];
        $groupdata =$MicroAccountMgr->grouplist($micro_account);
        //分组数据集合
        $micro_fans["micro_fans_groupdata"]=$groupdata;
         
        return $this->render('JustsyBaseBundle:EnterpriseSetting:microfans.html.twig', $micro_fans);
    }
    //粉丝列表页面
    public function microfanslistAction($network_domain){
    		$conn = $this->get('we_data_access');
        $conn_im = $this->get('we_data_access_im');
        $userinfo = $this->get('security.context')->getToken()->getUser();
        $logger=$this->get("logger");
        $getRequest=$this->getRequest();
        
        $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
        $micro_account=$getRequest->get("micro_number");
        $micro_name=$getRequest->get("micro_name");
        $micro_concern_approval=$getRequest->get("micro_concern_approval");
        $micro_type=$getRequest->get("micro_type");
        $micro_page_size=$getRequest->get("micro_pagesize");
	  		$micro_page_index=$getRequest->get("micro_pageindex");
	  		$groupid=$getRequest->get("groupid");
	  		if(empty($groupid))$groupid=0;
	  		$txtsearch=$getRequest->get("txtsearch");
	  		if(empty($micro_account)){
	  			$txtsearch="";
	  			$micro_name="";
	  			$micro_concern_approval=false;
	  			$micro_type=0;
	  			$micro_page_size=10;
	  			$micro_page_index=1;
	  		}
	  		$micro_fans["micro_concern_approval"]=$micro_concern_approval;
	  		$micro_fans["micro_type"]=$micro_type;
	  		$micro_fans["micro_pageindex"]=$micro_page_index;
	  		$micro_fans["micro_page_size"]=$micro_page_size;
        $micro_fans["curr_network_domain"]=$network_domain;
        $micro_fans["txtsearch"]=$txtsearch;
        $micro_fans["micro_name"]=$micro_name;
        $micro_fans["micro_account"]=$micro_account; 
        $micro_fans["groupid"]=$groupid; 
        
        $data =$MicroAccountMgr->get_micro_fans($micro_account,$txtsearch,$micro_page_size,$micro_page_index,$groupid);
        //粉丝总记录数
        $micro_fans["micro_fans_count"]=$data["micro_fans_count"];
        //最大页数
        $micro_fans["micro_page_max_index"]=$data["micro_page_max_index"];
        //粉丝列表
        $micro_fans["micro_fans_data"]=$data["micro_fans_data"];
        //var_dump($data["micro_fans_data"]);
        $micro_fans_ungrouped_count =$MicroAccountMgr->get_fans_ungrouped_count($micro_account);
        //未分组成员数
        $micro_fans["micro_fans_ungrouped_count"]=$micro_fans_ungrouped_count["max_count"]-$micro_fans_ungrouped_count["group_count"];
        $micro_fans["micro_fans_max_count"]=$micro_fans_ungrouped_count["max_count"];
        $groupdata =$MicroAccountMgr->grouplist($micro_account);
        //分组数据集合
        $micro_fans["micro_fans_groupdata"]=$groupdata;
        
    	 return $this->render('JustsyBaseBundle:EnterpriseSetting:microfanslist.html.twig', $micro_fans);
    }
    
    //初始化公众号页面
    public function microaccountAction($network_domain) {
        if (0 == $this->get('security.context')->getToken()->getUser()->is_in_manager_circles($network_domain)) {
            return $this->render('JustsyBaseBundle:EnterpriseSetting:no_rights.html.twig');
        }
        $conn = $this->get('we_data_access');
        $conn_im = $this->get('we_data_access_im');
        $userinfo = $this->get('security.context')->getToken()->getUser();
        $logger=$this->get("logger");
        
        $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
        $data =$MicroAccountMgr->getmicroaccount(false,0);
        
        $array["curr_network_domain"]=$network_domain;
        if(!empty($data)){
        	$photo_url = $this->container->getParameter('FILE_WEBSERVER_URL');
        	for($i=0;$i<count($data);$i++)
        	{
        	   $data[$i]["logo_path"] = $photo_url.$data[$i]["logo_path"];
        	   $data[$i]["logo_path_big"] = $photo_url.$data[$i]["logo_path_big"];
        	   $data[$i]["logo_path_small"] = $photo_url.$data[$i]["logo_path_small"];
        	}
        	$array["micro_json_data"]=json_encode($data);
        	$array["micro_data"]=$data;
        }else{
        	$array["micro_json_data"]="[]";
        	$array["micro_data"]=array();
        }
        $array["path"]="";
        
        $EnoParamManager=new EnoParamManager($conn,$logger);
        //外部公众号企业对应参数
        $enoparam_external=$EnoParamManager->getParamByEno($userinfo->getEno(),'micro_external_count');
        //内部公众号企业对应参数
        $enoparam_internal=$EnoParamManager->getParamByEno($userinfo->getEno(),'micro_internal_count');
        
        $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
        //获取所有公众号已经创建个数和内部公众号已经创建个数
        $micro_count=$MicroAccountMgr->getmicrocount();
        //获取外部公众号一共能创建多少个数
        if(!empty($enoparam_external)){
        	$micro_external_param_value=$enoparam_external["micro_external_count"]["param_value"];
        	$array["micro_external_param_value"]=$micro_external_param_value;
        }else{
        	$array["micro_external_param_value"]=0;
        }
        //获取内部公众号一共能创建多少个数
        if(!empty($enoparam_internal)){
        	$micro_internal_param_value=$enoparam_internal["micro_internal_count"]["param_value"];
        	$array["micro_internal_param_value"]=$micro_internal_param_value;
        }else{
        	$array["micro_internal_param_value"]=0;
        }
        $array["micro_internal_count"]=$micro_count["count"];
        $array["micro_external_count"]=$micro_count["allcount"]-$micro_count["count"];
        
        return $this->render('JustsyBaseBundle:EnterpriseSetting:microaccount.html.twig', $array);
    }
    
		//添加或修改化公众号页面
    public function microaccount_addAction($network_domain) {
        if (0 == $this->get('security.context')->getToken()->getUser()->is_in_manager_circles($network_domain)) {
            return $this->render('JustsyBaseBundle:EnterpriseSetting:no_rights.html.twig');
        }
        $conn = $this->get('we_data_access');
        $conn_im = $this->get('we_data_access_im');
        $userinfo = $this->get('security.context')->getToken()->getUser();
        $eno=$userinfo->getEno();
        $logger=$this->get("logger");
        $micro_id=$this->getRequest()->get("micro_id");
         $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
        if(!empty($micro_id)){
	        $data =$MicroAccountMgr->get_micro_data_id($micro_id);
	        if(!empty($data)){
	        	//$data[0]["logo_path"] = $this->container->getParameter('FILE_WEBSERVER_URL').$data[0]["logo_path"];
	        	//$data[0]["logo_path_big"] = $this->container->getParameter('FILE_WEBSERVER_URL').$data[0]["logo_path_big"];
	        	//$data[0]["logo_path_small"] = $this->container->getParameter('FILE_WEBSERVER_URL').$data[0]["logo_path_small"];
        		$array["micro_data"]=json_encode($data);
	        }else{
	        	$array["micro_data"]="[]";
	        }
        }else{
        	$array["micro_data"]="[]";
        }
        $array["curr_network_domain"]=$network_domain;
        
        $path=$this->get('templating.helper.assets')->geturl('bundles/fafatimewebase/images/no_photo.png');
        $array["path"]=$path;
        
        $EnoParamManager=new EnoParamManager($conn,$logger);
        //外部公众号企业对应参数
        $enoparam_external=$EnoParamManager->getParamByEno($userinfo->getEno(),'micro_external_count');
        //内部公众号企业对应参数
        $enoparam_internal=$EnoParamManager->getParamByEno($userinfo->getEno(),'micro_internal_count');
        
        //获取所有公众号已经创建个数和内部公众号已经创建个数
        $micro_count=$MicroAccountMgr->getmicrocount();
        //获取外部公众号一共能创建多少个数
        if(!empty($enoparam_external)){
        	$micro_external_param_value=$enoparam_external["micro_external_count"]["param_value"];
        	$array["micro_external_param_value"]=$micro_external_param_value;
        }else{
        	$array["micro_external_param_value"]=0;
        }
        //获取内部公众号一共能创建多少个数
        if(!empty($enoparam_internal)){
        	$micro_internal_param_value=$enoparam_internal["micro_internal_count"]["param_value"];
        	$array["micro_internal_param_value"]=$micro_internal_param_value;
        }else{
        	$array["micro_internal_param_value"]=0;
        }
        $array["micro_internal_count"]=$micro_count["count"];
        $array["micro_external_count"]=$micro_count["allcount"]-$micro_count["count"];
        
        $array["file_path"]=$this->container->getParameter('FILE_WEBSERVER_URL');
        
        return $this->render('JustsyBaseBundle:EnterpriseSetting:microaccount_add.html.twig', $array);
    } 
    
		//新增或修改公众号数据
    public function savemicroaccountAction($network_domain) {
    		$conn = $this->get('we_data_access');
        $conn_im = $this->get('we_data_access_im');
        $userinfo = $this->get('security.context')->getToken()->getUser();
        $logger=$this->get("logger");
        
        $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
        $getRequest=$this->getRequest();
        
	      //$session = $this->get('session'); 
		    $filename120 = $getRequest->get("logo_path_big");
		    $filename48 = $getRequest->get("logo_path");
		    $filename24 = $getRequest->get("logo_path_small");
		    
		    $dm = $this->get('doctrine.odm.mongodb.document_manager');
		    //if (!empty($filename120)) $filename120= $this->saveFile($filename120,$dm);
		    //if (!empty($filename48)) $filename48=   $this->saveFile($filename48,$dm);
		    //if (!empty($filename24)) $filename24=   $this->saveFile($filename24,$dm);
		    
		    //$session->remove("avatar_big");
		    //$session->remove("avatar_middle");
		    //$session->remove("avatar_small");      
		    
		    $factory = $this->get('security.encoder_factory');
		    $micro_id = $getRequest->get("id");
        $number = $getRequest->get('micro_number');
        $name= $getRequest->get('micro_name');
        $type= $getRequest->get('type');
        $introduction= $getRequest->get('introduction');
        $concern_approval= $getRequest->get('concern_approval');
        //var_dump($concern_approval);return;
        $salutatory= $getRequest->get('salutatory');
        $level= $getRequest->get('send_status');
        $password=$getRequest->get('password');
        $micro_use=$getRequest->get('micro_use');
		    //var_dump(1222);
        $dataexec =$MicroAccountMgr->register($micro_id,$number,$name,$type,$micro_use,$introduction,$concern_approval,$salutatory,$level,$password,$filename48,$filename120,$filename24,$factory,$dm);
        //$dataexec =$MicroAccountMgr->register($request,"","","");
        $r = array("success"=> false);
        //var_dump($dataexec);
        if ($dataexec) {
        	  $r["success"]=true;
        	  //$r["id"] = $dataexec;
        	  //$r["logo_path"] = $this->container->getParameter('FILE_WEBSERVER_URL').$filename120;
        }
        else $r["msg"]="帐号已经被使用";
        return $this->res(json_encode($r),'text/html');
    }
    
    //检测公众号帐号是否存在
    public function check_micro_numberAction(){
	    	$request=$this->getRequest();
	    	$number=$request->get("micro_number");
	    	$conn = $this->get('we_data_access');
	      $conn_im = $this->get('we_data_access_im');
	      $userinfo = $this->get('security.context')->getToken()->getUser();
	      $logger=$this->get("logger");
    	  $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
    	  
      	$number_count=$MicroAccountMgr->check_micro_number($number);
	      if ($number_count>0) {
            return $this->res('{success:true}', 'text/html');//不可以
        } else {
            return $this->res('{success:false}', 'text/html');
        }
    }
    //检测公众号名称是否存在
    public function check_micro_nameAction(){
	    	$request=$this->getRequest();
	    	$name=$request->get("micro_name");
	    	$old_name=$request->get("micro_old_name");
	    	$conn = $this->get('we_data_access');
	      $conn_im = $this->get('we_data_access_im');
	      $userinfo = $this->get('security.context')->getToken()->getUser();
	      $logger=$this->get("logger");
	      
    	  $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
      	$number_count=$MicroAccountMgr->check_micro_name($name,$old_name,$userinfo->getEno());
      	
	      if ($number_count>0) {
            return $this->res('{success:true}', 'text/html');//不可以
        } else {
            return $this->res('{success:false}', 'text/html');
        }
    }
    
    //粉丝关注并修改对应粉丝数
    public function change_micro_fansAction(){
    	  $conn = $this->get('we_data_access');
        $conn_im = $this->get('we_data_access_im');
        $userinfo = $this->get('security.context')->getToken()->getUser();
        $logger=$this->get("logger");
        $request=$this->getRequest();
        $login_account=$userinfo->getUsername();
        
        $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
        
		  	$micro_number=$request->get("micro_number");
		  	$obj=$request->get("obj");
		  	$obj_type=$request->get("obj_type");
		  	$array["success"]=0;
		  	$array["login_account"]=array();
		  	$array["msg"]="";
		  	switch ($obj_type) { 	
		  		case "friend": 		
		  			$dataexec =$MicroAccountMgr->invite_micro_fans_friend($micro_number,$obj);
		  			$array["success"]=$dataexec["success"];
		  			$array["msg"]=$dataexec["msg"];
		  		break; 	
		  		case "group": 		
		  			$dataexec =$MicroAccountMgr->invite_micro_fans_group($micro_number,$obj);
		  			$array["success"]=$dataexec["success"];
		  			$array["msg"]=$dataexec["msg"];
		  		break; 	
		  		case "circle": 		
		  			$dataexec =$MicroAccountMgr->invite_micro_fans_circle($micro_number,$obj);
		  			$array["success"]=$dataexec["success"];
		  			$array["msg"]=$dataexec["msg"];
		  		break; 	
		  		case "enterprise": 		
		  			$dataexec =$MicroAccountMgr->micro_fans_enterprise($micro_number,$obj);
		  			$array["success"]=$dataexec["success"];
		  			$array["msg"]=$dataexec["msg"];
		  		break; 	
		  		default:
		  			$array["success"] =$MicroAccountMgr->micro_fans_attention($micro_number,$login_account);
		  		break;
		    }
        return $this->res(json_encode($array), 'json');
    }
    
    //获取对应公众号粉丝列表
    public function get_micro_fansAction(){
    		$conn = $this->get('we_data_access');
        $conn_im = $this->get('we_data_access_im');
        $userinfo = $this->get('security.context')->getToken()->getUser();
        $logger=$this->get("logger");
        $request=$this->getRequest();
        
        $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
        $micro_account=$request->get("micro_account");
        $micro_page_size=$request->get("micro_pagesize");
	  		$micro_page_index=$request->get("micro_pageindex");
	  		$txtsearch=$request->get("txtsearch");
	  		
        $micro_fans =$MicroAccountMgr->get_micro_fans($micro_account,$txtsearch,$micro_page_size,$micro_page_index);
        
        return $this->res(json_encode($micro_fans),'json');
    }
    
    //修改公众号LOGO标志接口
    public function change_micro_logoAction(){
    	$conn = $this->get('we_data_access');
      $conn_im = $this->get('we_data_access_im');
      $userinfo = $this->get('security.context')->getToken()->getUser();
      $logger=$this->get("logger");
      $request=$this->getRequest();
      $micro_id=$request->get("micro_id");
      
      $session = $this->get('session'); 
	    $filename120 = $session->get("avatar_big");
	    $filename48 = $session->get("avatar_middle");
	    $filename24 = $session->get("avatar_small");
	    
	    $dm = $this->get('doctrine.odm.mongodb.document_manager');
	    if (!empty($filename120)) $filename120= $this->saveFile($filename120,$dm);
	    if (!empty($filename48)) $filename48=   $this->saveFile($filename48,$dm);
	    if (!empty($filename24)) $filename24=   $this->saveFile($filename24,$dm);
	    
	    $session->remove("avatar_big");
		  $session->remove("avatar_middle");
		  $session->remove("avatar_small");   
	    
	    $r["success"]=0;
	    $r["logo_path"]=$filename48;
	    $r["logo_path_big"]=$filename120;
	    $r["logo_path_small"]=$filename24;
	    $r["file_path"]="";
	    $r["file_path_big"]="";
	    $r["file_path_small"]="";
	    if(!empty($filename48)){
	    	$r["file_path"]=$this->container->getParameter('FILE_WEBSERVER_URL').$filename48;
	    	$r["file_path_big"]=$this->container->getParameter('FILE_WEBSERVER_URL').$filename120;
	    	$r["file_path_small"]=$this->container->getParameter('FILE_WEBSERVER_URL').$filename24;
	    }
	    
      if(!empty($micro_id)&&!empty($filename48)){
      	$MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
      	$dataexec =$MicroAccountMgr->change_logo_path($micro_id,$filename120,$filename48,$filename24);
      }
      return $this->res(json_encode($r),'json');
    }
    
    //获取所有公众号数据
    public function getmicroaccountAction(){
    	 $conn = $this->get('we_data_access');
    	 $conn_im = $this->get('we_data_access_im');
    	 $userinfo = $this->get('security.context')->getToken()->getUser();
       $logger=$this->get("logger");
       
       $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
       $data =$MicroAccountMgr->getmicroaccount();
       
       return $this->res(json_encode($data), 'json');
    }
    
    public function add_micro_quantityAction(){
    	 $conn = $this->get('we_data_access');
    	 $conn_im = $this->get('we_data_access_im');
    	 $userinfo = $this->get('security.context')->getToken()->getUser();
       $logger=$this->get("logger");
       
       $ext_count=$this->getRequest()->get("ext_count");
       $int_count=$this->getRequest()->get("int_count");
       
       $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
       $data =$MicroAccountMgr->add_micro_quantity($ext_count,$int_count);
       
       return $this->res(json_encode($data), 'json');
    }
}
