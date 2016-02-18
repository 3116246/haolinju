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

class ApiVersionController extends Controller
{
	public function pushAction()
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
		
		$verCrl = new \Justsy\AdminAppBundle\Controller\VersionController();
		$verCrl->setContainer($this->container);
		return $this->responseJson($request,$verCrl->UploadFileAction());
	}

	public function deleteAction()
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
		
		$verCrl = new \Justsy\AdminAppBundle\Controller\VersionController();
		$verCrl->setContainer($this->container);
		return $this->responseJson($request,$verCrl->DelVersionAction());		
	}

	public function listAction()
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
		
		$verCrl = new \Justsy\AdminAppBundle\Controller\VersionController();
		$verCrl->setContainer($this->container);
		return $this->responseJson($request,$verCrl->SearchVersionAction());			
	}

	private function responseJson($request,$re)
	{
		$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		$response->headers->set('Content-Type', 'text/json');
		return $response;
	}
	
}