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

class ApiNoticeController extends Controller
{
	//新增通知接口
	public function push_nowAction()
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
		$appid = $request->get('appid');
		$appmgr = new \Justsy\BaseBundle\Management\App($this->container);
		$appdata = $appmgr->getappinfo(array('appid'=>$appid));
		if(empty($appdata))
		{
			$result = Utils::WrapResultError("无效应用标识");
			return $this->responseJson($request,$result);
		}
		$data = $request->get("data"); 		//部门名称
		if(empty($data))
		{
			$result = Utils::WrapResultError("无效的数据");
			return $this->responseJson($request,$result);
		}
		$data = json_decode($data,true);
		$noticeMgr = new \Justsy\BaseBundle\Management\PromptlyNotice($this->container,$staffdata,$appdata);
		$result = $noticeMgr->pushNotice($data);
		return $this->responseJson($request,$result);		
	}

	public function push_receivedAction()
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
		$appid = $request->get('appid');
		$appmgr = new \Justsy\BaseBundle\Management\App($this->container);
		$appdata = $appmgr->getappinfo(array('appid'=>$appid));
		if(empty($appdata))
		{
			$result = Utils::WrapResultError("无效应用标识");
			return $this->responseJson($request,$result);
		}		
		$eno = $staffdata["eno"];           //企业号
		$data = $request->get("data"); 		//部门名称
		if(empty($data))
		{
			$result = Utils::WrapResultError("无效的数据");
			return $this->responseJson($request,$result);
		}
		$data = json_decode($data,true);
		$noticeMgr = new \Justsy\BaseBundle\Management\PromptlyNotice($this->container,$staffdata,$appdata);
		$result = $noticeMgr->received($data);
		return $this->responseJson($request,$result);		
	}

	public function push_replyAction()
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
		$appid = $request->get('appid');
		$appmgr = new \Justsy\BaseBundle\Management\App($this->container);
		$appdata = $appmgr->getappinfo(array('appid'=>$appid));
		if(empty($appdata))
		{
			$result = Utils::WrapResultError("无效应用标识");
			return $this->responseJson($request,$result);
		}		
		$eno = $staffdata["eno"];           //企业号
		$data = $request->get("data"); 		//部门名称
		if(empty($data))
		{
			$result = Utils::WrapResultError("无效的数据");
			return $this->responseJson($request,$result);
		}
		$data = json_decode($data,true);
		$noticeMgr = new \Justsy\BaseBundle\Management\PromptlyNotice($this->container,$staffdata,$appdata);
		$result = $noticeMgr->reply($data);
		return $this->responseJson($request,$result);		
	}	

	public function push_unread_listAction()
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
		$appid = $request->get('appid');
		$appmgr = new \Justsy\BaseBundle\Management\App($this->container);
		$appdata = $appmgr->getappinfo(array('appid'=>$appid));
		if(empty($appdata))
		{
			$result = Utils::WrapResultError("无效应用标识");
			return $this->responseJson($request,$result);
		}
	
		$noticeMgr = new \Justsy\BaseBundle\Management\PromptlyNotice($this->container,$staffdata,$appdata);
		$result = $noticeMgr->getUnreadList(array('lastid'=>$request->get('lastid')));
		return $this->responseJson($request,$result);
	}

	public function push_all_listAction()
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
		$appid = $request->get('appid');
		$appmgr = new \Justsy\BaseBundle\Management\App($this->container);
		$appdata = $appmgr->getappinfo(array('appid'=>$appid));
		if(empty($appdata))
		{
			$result = Utils::WrapResultError("无效应用标识");
			return $this->responseJson($request,$result);
		}
		
		$noticeMgr = new \Justsy\BaseBundle\Management\PromptlyNotice($this->container,$staffdata,$appdata);
		$result = $noticeMgr->getList(array('lastid'=>$request->get('lastid')));
		return $this->responseJson($request,$result);
	}	

	public function push_replylistAction()
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
		$appid = $request->get('appid');
		$appmgr = new \Justsy\BaseBundle\Management\App($this->container);
		$appdata = $appmgr->getappinfo(array('appid'=>$appid));
		if(empty($appdata))
		{
			$result = Utils::WrapResultError("无效应用标识");
			return $this->responseJson($request,$result);
		}
		$data = $request->get("data"); 		//部门名称
		if(empty($data))
		{
			$result = Utils::WrapResultError("无效的数据");
			return $this->responseJson($request,$result);
		}
		$data = json_decode($data,true);		
		$noticeMgr = new \Justsy\BaseBundle\Management\PromptlyNotice($this->container,$staffdata,$appdata);
		$result = $noticeMgr->getReplyList($data);
		return $this->responseJson($request,$result);		
	}
	public function push_receiverlistAction()
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
		$appid = $request->get('appid');
		$appmgr = new \Justsy\BaseBundle\Management\App($this->container);
		$appdata = $appmgr->getappinfo(array('appid'=>$appid));
		if(empty($appdata))
		{
			$result = Utils::WrapResultError("无效应用标识");
			return $this->responseJson($request,$result);
		}
		$data = $request->get("data"); 		//部门名称
		if(empty($data))
		{
			$result = Utils::WrapResultError("无效的数据");
			return $this->responseJson($request,$result);
		}
		$data = json_decode($data,true);
		$noticeMgr = new \Justsy\BaseBundle\Management\PromptlyNotice($this->container,$staffdata,$appdata);
		$result = $noticeMgr->getReceiverList($data);
		return $this->responseJson($request,$result);		
	}

	private function responseJson($request,$re)
	{
		$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		$response->headers->set('Content-Type', 'text/json');
		return $response;
	}
	
}