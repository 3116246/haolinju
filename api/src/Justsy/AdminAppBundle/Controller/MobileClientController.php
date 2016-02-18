<?php

namespace Justsy\AdminAppBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Management\Staff;
use Justsy\BaseBundle\Common\Utils;

class MobileClientController extends Controller
{    
    public function IndexAction()
    {
    	return $this->render('JustsyAdminAppBundle:Sys:mobileclient.html.twig');
    }
    
    //取权限人员列表
		public function searchStaffAction()
		{    
			$re = array();
			$request = $this->getRequest();
			$user = $this->get('security.context')->getToken()->getUser();
			$da = $this->get('we_data_access');
			$staff = $request->get("staff");
			$pageindex = $request->get("pageindex");
			$record = $request->get("record");
			$limit = " limit ".(($pageindex - 1) * $record).",".$record;
			$result = array();
			$data = array();
			$recordcount = 0;
			$success = true;
			$msg = "";
			$eno = $this->get('security.context')->getToken()->getUser()->eno;
			try 
			{
				$condition = "";
				$para = array();
				$sql = "select login_account,fafa_jid,nick_name staff from we_staff where eno=? ";
	      if ( !empty($staff)){
	      	if (strlen($staff)>mb_strlen($staff,'utf8'))
		 	       $condition = " and nick_name like concat(?,'%') ";
		 	    else
		 	 	    $condition = " and login_account like concat(?,'%') ";
		 	 	  $para = array((string)$eno,(string)$staff);
	      }
	      else{
	      	$para = array((string)$eno);
	      }
	      $sql .= $condition.$limit;
				$ds = null;
				if ( count($para)==0)
				  $ds = $da->GetData("we_staff", $sql);
				else 
				  $ds = $da->GetData("we_staff", $sql,$para);
				if ( $ds!=null && $ds["we_staff"]["recordcount"]>0)
				  $data = $ds["we_staff"]["rows"];
				if ($pageindex==1){
					$sql = "select count(*) total from we_staff where eno=? ".$condition;
					if (count($para)>0)
					  $ds = $da->GetData("staff",$sql,$para);
					else
					  $ds = $da->GetData("staff",$sql);
				  if ($ds && $ds["staff"]["recordcount"]>0)
				    $recordcount = $ds["staff"]["rows"][0]["total"];
				}			
			} 
			catch (\Exception $e)
			{
				$success = false;
				$this->get('logger')->err($e->getMessage());
				$msg = "查询数据失败，请重试！";
			}
			$result = array("success"=>$success,"msg"=>$msg,"datasource"=>$data,"recordcount"=>$recordcount);
	    $response = new Response(json_encode($result));
	    $response->headers->set('Content-Type', 'text/json');
	    return $response;
		}
    
    public function executeAction()
    {
    	$request = $this->getRequest();
    	$login_account = $request->get("login_account");
    	$fafa_jid = $request->get("fafa_jid");
    	$type = $request->get("type");
    	$password = $request->get("password");
    	$my_jid = $this->get('security.context')->getToken()->getUser()->fafa_jid;
    	$result = array();
    	$send_status = false;
    	$msg = "";
    	//修改密码
    	if ( $type=="adminLock" ) {
    		$da = $this->get('we_data_access');
    		$da_im = $this->get('we_data_access_im');
    		$u_staff = new Staff($da,$da_im,$login_account,$this->get('logger'));
        $targetStaffInfo = $u_staff->getInfo();
		    $re = $u_staff->changepassword($login_account,$password,$this->get('security.encoder_factory'));
	    	if ( $re ){
          $send_status = Utils::sendImMessage($my_jid,$fafa_jid,$type,$password,$this->container,"","",false,Utils::$systemmessage_code,'0');
		    }
		    else{
		    	$msg = "密码修改失败！";
		    }
    	}
    	else{
    		 $send_status = Utils::sendImMessage($my_jid,$fafa_jid,$type,$type,$this->container,"","",false,Utils::$systemmessage_code,'0');
    	}
    	$result = array("success"=>$send_status,"msg"=>$msg);
	    $response = new Response(json_encode($result));
	    $response->headers->set('Content-Type', 'text/json');
	    return $response;
    }
}