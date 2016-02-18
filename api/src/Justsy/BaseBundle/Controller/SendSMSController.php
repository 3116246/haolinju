<?php

namespace Justsy\BaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\BaseBundle\Common\Utils;

class SendSMSController extends Controller
{  
	public function sendSMSAction($mobiles, $msg)
  	{
	    $SmsFunction=null;
	    try{
	        $syspara =new \Justsy\BaseBundle\DataAccess\SysParam($this->container);
	        $SmsFunction = $syspara->GetSysParam('SMS_FUNCTION_NAME','');
	    }
	    catch(\Exception $e)
	    {
	    	return Utils::WrapResultError($e->getMessage());
	    }
	    if(!empty($SmsFunction))
	    {
	    	$re= call_user_func(array($this, $SmsFunction),$mobiles, $msg);
	      	return $re;
	    }
	    $SMS_ACT = $this->container->getParameter('SMS_ACT');
	    $SMS_PWD = $this->container->getParameter('SMS_PWD');
	    $SMS_URL = $this->container->getParameter('SMS_URL');
	    $mobiles = str_replace(";",",",$mobiles);
	    $content = urlEncode(urlEncode(mb_convert_encoding($msg, 'gb2312' ,'utf-8')));
	    $pwd = md5($SMS_PWD);
	    $apidata = "func=sendsms&username=$SMS_ACT&password=$pwd&mobiles=$mobiles&message=$content&smstype=0&timerflag=0&timervalue=&timertype=0&timerid=0";
	    $this->get("logger")->err($SMS_URL."?".$apidata);
	    $result = mb_convert_encoding($this->do_post_request($SMS_URL."?".$apidata,null),'utf-8','gb2312');
	    $this->get("logger")->err($result);
	    return Utils::WrapResultOK('');
  	}
  
  	public function sendAvicSMSAction($mobiles,$msg)
  	{
	    $SMS_ACT = $this->container->getParameter('SMS_ACT');
	    $SMS_PWD = $this->container->getParameter('SMS_PWD');
	    $SMS_URL = $this->container->getParameter('SMS_URL');
	    $SMS_EID = $this->container->getParameter('SMS_EID');
	    $mobiles = str_replace(";",",",$mobiles);
	    $content = urlEncode(urlEncode(mb_convert_encoding($msg, 'gb2312' ,'utf-8')));
	    $pwd = md5($SMS_PWD);
	    $apidata = "username=$SMS_ACT&password=$pwd&message=$content&phone=$mobiles&epid=$SMS_EID&linkid=&subcode=";
	    $this->get("logger")->err($SMS_URL."?".$apidata);
	    $result = mb_convert_encoding($this->do_post_request($SMS_URL."?".$apidata,null),'utf-8','gb2312');
	    $this->get("logger")->err($result);
	    return Utils::WrapResultOK('');
  	} 

	private function do_post_request($url, $data, $optional_headers = null)
	{
	    $params = array('http' => array(
	                'method' => 'GET',
	                'content' => $data
	              ));
	    if ($optional_headers !== null) 
	    {
	      $params['http']['header'] = $optional_headers;
	    }
	    $ctx = stream_context_create($params);
	    $fp = @fopen($url, 'r', false, $ctx);
	    if (!$fp) 
	    {
	      throw new Exception("Problem with $url, $php_errormsg");
	    }
	    $response = @stream_get_contents($fp);
	    if ($response === false) 
	    {
	      throw new Exception("Problem reading data from $url, $php_errormsg");
	    }
	    return $response;
	}
}