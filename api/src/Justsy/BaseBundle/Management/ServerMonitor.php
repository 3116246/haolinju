<?php

namespace Justsy\BaseBundle\Management;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Common\Cache_Enterprise;
use Symfony\Component\HttpFoundation\Response;

class ServerMonitor implements IBusObject
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

    public function imServerCtl($paraObj)
    {
        $command = $paraObj['command'];
        if(empty($command))
        {
            $result = Utils::WrapResultError("无效的命令");
            return $this->responseJson($result);
        }
        try
        {
            $syspara =new \Justsy\BaseBundle\DataAccess\SysParam($this->container);
            $ejabberd_server_path = $syspara->GetSysParam('ejabberd_server_path','');
            if(empty($ejabberd_server_path))
            {
                return Utils::WrapResultError('请检查参数ejabberd_server_path设置是否正确有效！');
            }
            if($command=="start")
            {
                $command = $ejabberd_server_path."/bin/ejabberdctl status";            
                $data=shell_exec($command);
                if(strpos($data, 'is running')!==false)
                {
                    $data=shell_exec($ejabberd_server_path."/bin/ejabberdctl restart");
                }
                else if(strpos($dta, 'nodedown')!==false)
                {
                    $data=shell_exec($ejabberd_server_path."/bin/ejabberdctl start");
                }
                return Utils::WrapResultOK($data);
            }
            else if($command=="status")
            {
                $command = $ejabberd_server_path."/bin/ejabberdctl status";
                $data=shell_exec($command);
                return Utils::WrapResultOK($data);
            }
            else if($command=="stop")
            {
                $command = $ejabberd_server_path."/bin/ejabberdctl stop";            
                $data=shell_exec($command);
                return Utils::WrapResultOK($data);
            }
            return Utils::WrapResultError('无效的命令'.$command);
        }
        catch(\Exception $e)
        {
            $this->logger->err($e);
            return Utils::WrapResultError($e->getMessage());            
        }
    }

    public function webServerCtl($paraObj)
    {
        $command = $paraObj['command'];
        if(empty($command))
        {
            $result = Utils::WrapResultError("无效的命令");
            return $this->responseJson($result);
        }
        try
        {
            $dir = explode("src", __DIR__);
            if($command=="start")
            {
                $str = "php {$dir[0]}app/console cache:clear --env=prod --no-debug\nchmod -R 777 {$dir[0]}app";
                
                $command = $dir[0].'clear_cache_prod.sh';
                $data=shell_exec($command);
                if(strpos($data, 'Clearing the cache for the prod environment with debug false')===false)
                {
                    throw new Exception($data);
                }
                return Utils::WrapResultOK($data);
            }
            return Utils::WrapResultError('无效的命令'.$command);
        }
        catch(\Exception $e)
        {
            $this->logger->err($e);
            return Utils::WrapResultError($e->getMessage());            
        }        
    }
}
