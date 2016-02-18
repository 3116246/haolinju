<?php

namespace Justsy\AdminAppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Management\Staff;
use Justsy\BaseBundle\Login\UserSession;

class ResetPWDController extends Controller
{    
    public function IndexAction()
    {
    	return $this->render('JustsyAdminAppBundle:Sys:resetpwd.html.twig', array());
    }
    
    //查询账号信息
    public function getAccountAction()
    {
    	$da = $this->get("we_data_access");
    	$request = $this->getRequest();
    	$account = $request->get("account");
    	$user = $this->get('security.context')->getToken()->getUser();
    	$eno = $user->eno;
    	if ( $account!=null)
    	  $account = strtolower($account);    	
    	$header = $this->container->getParameter('FILE_WEBSERVER_URL');
    	$sql ="select login_account,nick_name,case when ifnull(photo_path,'')='' then '' else concat('$header',photo_path) end as header,mobile,'123456' card,'0' as salary_password 
    	       from we_staff d where eno=? and (mobile_bind=? or login_account=?) ";
    	$ds = $da->GetData("table",$sql,array((string)$eno,(string)$account,(string)$account));
    	$result = array();
    	if ( $ds && $ds["table"]["recordcount"]>0)
    	  $result = $ds["table"]["rows"];
      $response = new Response(json_encode($result));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }
    
    //重置账号密码
    public function ResetPassWordAction()
    {
    	$da = $this->get("we_data_access");
    	$da_im = $this->get("we_data_access_im");
    	$request = $this->getRequest();
    	$account = strtolower($request->get("account"));
    	$password = $request->get("password");
    	$result = array("success"=>true,"message"=>"");
      $user = $this->get('security.context')->getToken()->getUser();
    	try
    	{
	    	$u_staff = new Staff($da,$da_im,$account,$this->get('logger'),$this->container);
        $targetStaffInfo = $u_staff->getInfo();
		    $re = $u_staff->changepassword($targetStaffInfo["login_account"],$password,$this->get('security.encoder_factory'));
	    	if ( $re ){
          //$this->get("logger")->err("sendImPresence:".$targetStaffInfo["fafa_jid"]);
          //给自己发送一个staff-changepasswod的出席，通知在线客户端密码发生修改，需要新密码重新登录
          Utils::sendImPresence($user->fafa_jid,$targetStaffInfo["fafa_jid"],"staff-changepasswod","staff-changepasswod",$this->container,"","",false,Utils::$systemmessage_code);
			    //记录用户操作日志
			    $syslog = new \Justsy\AdminAppBundle\Controller\SysLogController();
			    $syslog->setContainer($this->container);
			    $desc = "重置了用户账号:".$account."登录密码！"; 
			    $syslog->AddSysLog($desc,"重置密码");
		    }
		    else{
		    	$result = array("success"=>false,"message"=>"修改密码错误！");    	
		    }
	    }
	    catch(\Exception $e) {
	    	 $this->get("logger")->err($e->getMessage());
	    	 $result = array("success"=>false,"message"=>"修改密码错误！"); 
	    }
      $response = new Response(json_encode($result));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }
}
