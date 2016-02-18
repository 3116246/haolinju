<?php
namespace Justsy\BaseBundle\Management;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Management\Identify;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Login\UserSession;
use Justsy\BaseBundle\Management\MicroAccountMgr;
use Justsy\BaseBundle\Common\Cache_Enterprise;

//移动门户管理
class Portal
{
    private $container="";
	  private $db_conn=null;
	  private $db_conn_im = null;
	  private $logger=null;
	  
	  public function __construct($container)
	  {
        $this->container = $container;
        $this->db_conn = $container->get('we_data_access');
        $this->db_conn_im = $container->get('we_data_access_im');
        $this->logger=$container->get('logger');
    }
    
    //获得当前企业门户信息
    public function GetPortInfo()
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $sql = "select * from we_apps_portalconfig  where eno=?;";
        $dbset = $this->db_conn->getData("t",$sql,array((string)$user->eno));
        $result = array();
        if( $dbset && $dbset["t"]["recordcount"]>0)
        	$result = $dbset["t"]["rows"][0];
        return $result;
    }
    
    //修改门户名称
    public function updatePortal($parameter)
    {
        $user = $parameter["user"];
        $name = isset($parameter["name"]) ? $parameter["name"]:"";
        $provision = isset($parameter["provision"]) ? $parameter["provision"]:"";
        $condition = array();$para = array();
        if ( !empty($name))
        {
            array_push($condition,"portalname=?");
            array_push($para,(string)$name);
            
        }
        if ( !empty($provision))
        {
            array_push($condition,"provision=?");
            array_push($para,(string)$provision);
        }
        $sql = "update we_apps_portalconfig set ".implode(",",$condition)." where eno=?;";
        array_push($para,(string)$user->eno);
        $success = true;
        try
        {
            $this->db_conn->ExecSQL($sql,$para);
        }
        catch(\Exception $e)
        {
            $success = false;
            $this->logger->err($e->getMessage());
        }
        return array("success"=>$success);
    }
}
