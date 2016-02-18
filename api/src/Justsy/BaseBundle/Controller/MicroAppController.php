<?php

namespace Justsy\BaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\BaseBundle\Management\MicroAccountMgr;
use Justsy\BaseBundle\Management\EnoParamManager;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;

class MicroAppController extends Controller {
	//微应用列表页面
	public function listAction($network_domain){
		if (0 == $this->get('security.context')->getToken()->getUser()->is_in_manager_circles($network_domain)) {
            return $this->render('JustsyBaseBundle:EnterpriseSetting:no_rights.html.twig');
        }
        $conn = $this->get('we_data_access');
        $conn_im = $this->get('we_data_access_im');
        $userinfo = $this->get('security.context')->getToken()->getUser();
        $logger=$this->get("logger");
        $eno=$userinfo->getEno();
        $array["curr_network_domain"]=$network_domain;
        $array["clicknumber"] = $this->getRequest()->get("clicknumber");
        
        $EnoParamManager=new EnoParamManager($conn,$logger);
        //获取微应用允许创建总个数
        $micro_app_allow_count=$EnoParamManager->getCountCreateAppMicroAccount($eno);
        //var_dump($micro_app_allow_count);
        $array["micro_app_allow_count"]=$micro_app_allow_count;
        //if(!empty($micro_app_allow_count)) $array["micro_app_allow_count"]=$micro_app_allow_count["micro_app_count"]["param_value"];
        
        //获取微应用列表
        $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
        $data =$MicroAccountMgr->getmicroaccount(false,1);
        $array["curr_network_domain"]=$network_domain;
        $array["micro_app_count"]=0;
        if(!empty($data)){
        	$array["micro_app_count"]=count($data);
        	$photo_url = $this->container->getParameter('FILE_WEBSERVER_URL');
        	for($i=0;$i<count($data);$i++)
        	{
        	   $data[$i]["logo_path"] = $photo_url.$data[$i]["logo_path"];
        	   $data[$i]["logo_path_big"] = $photo_url.$data[$i]["logo_path_big"];
        	   $data[$i]["logo_path_small"] = $photo_url.$data[$i]["logo_path_small"];
        	}
        	$array["micro_app_json_data"]=json_encode($data);
        	$array["micro_app_data"]=$data;
        }else{
        	$array["micro_app_json_data"]='[]';
        	$array["micro_app_data"]=array();
        }
        if($userinfo->edomain=="fafatime.com")
        {
        	$array['micro_app_cancount']=$micro_app_allow_count;
        }
        else{
            $array['micro_app_cancount']=(int)$micro_app_allow_count-(int)count($data);
        }
        //var_dump($array);
        return $this->render('JustsyBaseBundle:MicroApp:list.html.twig', $array);
	}
	//列表页面微应用详细数据页面
	public function itemAction($network_domain){
		$conn = $this->get('we_data_access');
        $conn_im = $this->get('we_data_access_im');
        $userinfo = $this->get('security.context')->getToken()->getUser();
        $logger=$this->get("logger");
        $array["curr_network_domain"]=$network_domain;
        $micro_app_data=$this->getRequest()->get("micro_app_data");
         
        if(!empty($micro_app_data)){
        	$array["micro_app_data"]=$micro_app_data;
        }else{
        	$array["micro_app_data"]=array();
        }
        //var_dump($array);
		return $this->render('JustsyBaseBundle:MicroApp:item.html.twig', $array);
	}
	//添加微应用帐号
	public function addAction($network_domain) {
		$conn = $this->get('we_data_access');
        $conn_im = $this->get('we_data_access_im');
        $userinfo = $this->get('security.context')->getToken()->getUser();
        $logger=$this->get("logger");
        $eno=$userinfo->getEno();
        $micro_id=$this->getRequest()->get("micro_id");
        $array["micro_app_data"]='[]';
        if(!empty($micro_id)) { //读取数据
        	$MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
	        $data =$MicroAccountMgr->get_micro_data_id($micro_id);
	        if(!empty($data)) {
	        	//$data[0]["logo_path"] = $this->container->getParameter('FILE_WEBSERVER_URL').$data[0]["logo_path"];
	        	//$data[0]["logo_path_big"] = $this->container->getParameter('FILE_WEBSERVER_URL').$data[0]["logo_path_big"];
	        	//$data[0]["logo_path_small"] = $this->container->getParameter('FILE_WEBSERVER_URL').$data[0]["logo_path_small"];
        		$array["micro_app_data"]=json_encode($data);
	        }else {
	        	$array["micro_app_data"]="[]";
	        }
        }
        $path=$this->get('templating.helper.assets')->geturl('bundles/fafatimewebase/images/no_photo.png');
        $array["path"]=$path;
        $array["curr_network_domain"]=$network_domain;
        $EnoParamManager=new EnoParamManager($conn,$logger);
        //获取微应用允许创建总个数
        $micro_app_allow_count=$EnoParamManager->getCountCreateAppMicroAccount($eno);
        //var_dump($micro_app_allow_count);
        $array["micro_app_allow_count"]=$micro_app_allow_count;
        //if(!empty($micro_app_allow_count)) $array["micro_app_allow_count"]=$micro_app_allow_count["micro_app_count"]["param_value"];
        //获取已创建微应用个数
        $sql="select count(1) as count from we_micro_account where eno=? and micro_use='1' ";
        $para=array($eno);
        $data=$conn->GetData('dt',$sql,$para);
        $array["micro_app_count"]=0;
        if($data!=null && count($data['dt']['rows'])>0 && !empty($data['dt']['rows'][0]['count'])) {
        	$array["micro_app_count"]=$data['dt']['rows'][0]['count'];
        }
        $array["file_path"]=$this->container->getParameter('FILE_WEBSERVER_URL');
		return $this->render('JustsyBaseBundle:MicroApp:add.html.twig', $array);
	}
	public function resetMicroPwdAction($network_domain)
	{
		$re=array('s'=>1,'m'=>'');
		try{
			$conn = $this->get('we_data_access');
        $conn_im = $this->get('we_data_access_im');
        $userinfo = $this->get('security.context')->getToken()->getUser();
    $getRequest=$this->getRequest();
    $micro_account=$getRequest->get("micro_account");
    $newpwd=$getRequest->get("newpwd");
    $factory = $this->get('security.encoder_factory');
    $encoder = $factory->getEncoder($userinfo);
		$t_code=DES::encrypt($newpwd);
		$micro_password = $encoder->encodePassword($newpwd, $micro_account);
    $sql="update we_staff set password=? where login_account=?";
    $params=array($micro_password,$micro_account);
    $conn->ExecSQL($sql,$params);
		}
		catch(\Exception $e)
		{
			$this->get('logger')->err($e->getMessage());
			$re['s']=0;
			$re['m']='重设密码失败';
		}
    $response = new Response(json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
	}
	//保存微应用
	public function saveAction($network_domain){	    
		$conn = $this->get('we_data_access');
        $conn_im = $this->get('we_data_access_im');
        $userinfo = $this->get('security.context')->getToken()->getUser();
        $logger=$this->get("logger");
        $eno=$userinfo->getEno();
        $login_account=$userinfo->getUsername();
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
        $salutatory= $getRequest->get('salutatory');
        $level= $getRequest->get('send_status');
        $password=$getRequest->get('password');
        $micro_use=$getRequest->get('micro_use');

        $appid=Utils::getAppid($eno,$login_account);
        $appkey=Utils::getAppkey();

        if(empty($micro_id)) $dataexec = $MicroAccountMgr->register($micro_id,$number,$name,$type,$micro_use,$introduction,$concern_approval,$salutatory,$level,$password,$filename48,$filename120,$filename24,$factory,$dm,$appid);
        else  $dataexec = $MicroAccountMgr->register($micro_id,$number,$name,$type,$micro_use,$introduction,$concern_approval,$salutatory,$level,$password,$filename48,$filename120,$filename24,$factory,$dm);
        
        $r = array("success"=> false);
        if ($dataexec) {
            $r["success"]=true;
            if(empty($micro_id)) {
                try {
                    $sql="insert into we_appcenter_apps(appid,appkey,appname,state,appdeveloper,appdesc,apptype) values(?,?,?,?,?,?,?);";
                    $para=array($appid,$appkey,$name,1,$eno,$name,'00');
                    $data=$conn->ExecSQL($sql,$para);    
                } catch (Exception $e) {
                    $this->get('logger')->err($e->getMessage());
                }
            }
        }
        else $r["msg"]="帐号已经被使用";
        return $this->res(json_encode($r),'text/html');
	}
	//关注微应用页面
	public function attenAction($network_domain){
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
         
		return $this->render('JustsyBaseBundle:MicroApp:atten.html.twig', $micro_fans);
	}
	//微应用对应人员列表
	public function staffAction($network_domain){
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
        
    	 return $this->render('JustsyBaseBundle:MicroApp:staff.html.twig', $micro_fans);
	}
	//微应用帐号关注人改变的方法
	public function changeattenAction(){
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
    public function res($content, $type = 'html') {
        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/' . $type);
        $response->headers->set('charset', 'utf-8');
        return $response;
    }

    public function webizproxyAction($network_domain)
    {
        $re = array();
        $re["curr_network_domain"]=$network_domain;

        return $this->render('JustsyBaseBundle:MicroApp:webizproxy.html.twig', $re);
    }
}