<?php

namespace Justsy\BaseBundle\Management;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Common\Cache_Enterprise;
use Symfony\Component\HttpFoundation\Response;

class QrcodeMgr implements IBusObject
{
	private $conn=null;
	private $conn_im=null; 
	private $container = null;
	private $logger = null;
    public function __construct($container)
    {
        $this->container = $container;
        $this->conn    = $container->get('we_data_access');
        $this->conn_im = $container->get('we_data_access_im');
        $this->logger  = $container->get('logger');
    }
    public function getInstance($container)
    {
        return new self($container);
    }

    public function getData($type,$code)
    {
        if(empty($code))
        {
            $result = Utils::WrapResultError("无效的数据");
            return $this->responseJson($result);
        }
        try
        {
            if(empty($type))
            {
                $result = Utils::WrapResultError("无效的type参数");
                return $this->responseJson($result);
            }
            //$this->logger->err($code);     
            $code = DES::decrypt($code);            
            $code = explode(",",$code);
            $result = call_user_func_array(array(self,$type),$code);
            return $this->responseJson($result);
        }
        catch(\Exception $e)
        {
            $this->logger->err($e);
            $result = Utils::WrapResultError($e->getMessage());            
        }
        return $this->responseJson($result);
    }

    //通过二维码获取人员信息
    public function n($parameters)
    {
        if (is_string($parameters))
        {
           $parameters = explode("\\",$parameters);
        }
        if(count($parameters)==0)
        {
            return Utils::WrapResultError("无效的login_account参数");
        }
        $login_account = $parameters[0];
        if(empty($login_account)) return Utils::WrapResultError("无效的login_account参数");
        $staffMgr = new Staff($this->conn,$this->conn_im,$login_account,$this->logger,$this->container);
        $staffdata = $staffMgr->getInfo();
        if(empty($staffdata))
        {
            return Utils::WrapResultError("未查找到人员信息");
        }
        return Utils::WrapResultOK(array('code'=>'n','data'=>$staffdata)); 
    }

    //通过二维码获取企业信息
    public function e($parameters)
    {
        if(count($parameters)==0)
        {
            return Utils::WrapResultError("无效的eno参数");
        }
        $eno = $parameters[0];
        if(empty($eno)) return Utils::WrapResultError("无效的eno参数");
        $staffMgr = new Enterprise($this->conn,$this->logger,$this->container);
        $staffdata = $staffMgr->getInfo($eno);
        if(empty($staffdata))
        {
            return Utils::WrapResultError("未查找到企业信息");
        }
        return Utils::WrapResultOK($staffdata); 
    }

    private function responseJson($re)
    {
        $request = $this->container->get("request");
        $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }
}
