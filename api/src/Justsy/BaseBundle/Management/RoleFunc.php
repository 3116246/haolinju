<?php

namespace Justsy\BaseBundle\Management;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Management\Identify;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Login\UserSession;
use Justsy\BaseBundle\Management\MicroAccountMgr;
use Justsy\BaseBundle\Common\Cache_Enterprise;

//权限、功能点管理
class RoleFunc implements IBusObject
{
	  private $conn=null;
	  private $conn_im=null;
	  private $user=null;   //用户对象
	  private $logger=null;
	  private $container = null;

    public function __construct($_container)
	  {
	    $this->conn = $_container->get("we_data_access");
	    $this->conn_im = $_container->get("we_data_access_im");
	    $this->logger = $_container->get("logger");
	    $this->container = $_container;
	    $token = $_container->get('security.context')->getToken();
	    if(!empty($token))
	  		$user = $token->getUser();
	  }
	  	  
    public function getInstance($container)
    {
    		$db = $container->get("we_data_access");
    		$db_im = $container->get("we_data_access_im");
    		$logger = $container->get("logger");
    		$token = $container->get('security.context')->getToken();
    		if(!empty($token))
    			$user = $token->getUser();
    		else
    			$user = $container->get('request')->get("openid");
    		return new self($container);
    }
	  
	  //------------------------------------------角色信息管理-----------------------------------
	  //角色查询信息
    public function search_role($parameter)
    {
        $role = $parameter["role"];
        $pageindex = isset($parameter["pageindex"]) ? $parameter["pageindex"] : 1;
        $pageindex = $pageindex < 1 ? 1 : $pageindex;
        $record = isset($parameter["record"]) ? $parameter["record"]:8;
        $user = $parameter["user"];
        $eno = $user->eno;
        $limit = " limit ".(($pageindex - 1) * $record).",".$record;
        $condition = "";$para = array();
        $sql="select id,ifnull(name,'') name,ifnull(code,'') code,ifnull(role_type,'') role_type from we_role where eno=? ";
        array_push($para,(string)$eno);
        if ( !empty($role))
        {
            $condition = " and name like concat('%',?,'%') ";
            array_push($para,(string)$role);
        }
        $sql .= $condition." order by convert(id,unsigned) desc ".$limit;
        $success = true;$rolelist = array();$recordcount=0;
        try
        {
            $ds=$this->conn->GetData("table",$sql,$para);
            if ( $ds && $ds["table"]["recordcount"]>0)
            {
                $rolelist = $ds["table"]["rows"];                
                if ( $pageindex==1 && $ds["table"]["recordcount"]==$record)
                {
                    $sql="select count(*) recordcount from we_role where eno=? ".$condition;
                    $ds=$this->conn->GetData("table",$sql,$para);
                    if ( $ds && $ds["table"]["recordcount"]>0)
                      $recordcount = $ds["table"]["rows"][0]["recordcount"];
                }
            }
        }
        catch(\Exception $e)
        {
            $success = false;
            $this->logger->err($e->getMessage());
        }
        return array("success"=>$success,"recordcount"=>$recordcount,"rolelist"=>$rolelist);
    }
    
    //编辑角色信息
    public function editRole($parameter)
    {
        $id=isset($parameter["id"])?$parameter["id"]:"";
        $name=$parameter["name"];
        $code=$parameter["code"];
        $type=$parameter["type"];
        $user=$parameter["user"];
        $eno =$user->eno;
        $success=true;$sql="";$para=array();
        $exists = $this->checkRole($eno,$id,$name);
        if ( $exists )
           return array("success"=>false,"exists"=>$exists );
        if ( empty($id))
        {
            $id = SysSeq::GetSeqNextValue($this->conn,"we_role","id");
            $sql="insert into we_role(id,name,code,eno,role_type)values(?,?,?,?,?);";
            $para=array((string)$id,(string)$name,$code,$eno,$type);
        }
        else
        {
            $sql="update we_role set name=?,code=?,role_type=? where id=?;";
            $para=array($name,$code,$type,$id);
        }
        try
        {
            $this->conn->ExecSQL($sql,$para);
        }
        catch(\Exception $e)
        {
            $success=false;
            $this->logger->err($e->getMessage());
        }
        return array("success"=>$success,"exists"=>$exists,"id"=>$id);
    }
    
    //判断角色名称是否存在
    public function checkRole($eno,$id,$name)
    {
        $exists=false;$sql="";$para=array();
        if ( empty($id))
        {
            $sql="select 1 from we_role where name=? and eno=?;";
            $para=array((string)$name,(string)$eno);
        }
        else
        {
            $sql="select 1 from we_role where name=? and id!=? and eno=?;";
            $para=array((string)$name,(string)$id,(string)$eno);
        }
        try
        {
            $ds=$this->conn->GetData("table",$sql,$para);
            if ( $ds && $ds["table"]["recordcount"]>0)
              $exists = true;
        }
        catch(\Exception $e)
        {
        }
        return $exists;
    }
    
    //删除角色
    public function del_role($parameter)
    {
        $roleid = $parameter["roleid"];
        $sql="delete from we_role where id=?;";
        $success = true;
        try
        {
            $this->conn->ExecSQL($sql,array((string)$roleid));
        }
        catch(\Exception $e)
        {
            $success = false;
            $this->logger->err($e->getMessage());
        }
        return array("success"=>$success);
    }
    
    //------------------------------------------功能点信息管理-----------------------------------
	  //功能点查询信息
    public function search_func($parameter)
    {
        $func = $parameter["func"];
        $pageindex = isset($parameter["pageindex"]) ? $parameter["pageindex"] : 1;
        $pageindex = $pageindex < 1 ? 1 : $pageindex;
        $record = isset($parameter["record"]) ? $parameter["record"]:8;
        $user = $parameter["user"];
        $eno = $user->eno;
        $limit = " limit ".(($pageindex - 1) * $record).",".$record;
        $condition = "";$para = array();
        $sql="select id,ifnull(name,'') name,ifnull(code,'') code,ifnull(type,'') `type` from we_function where 1=1 ";
        if ( !empty($func))
        {
            $condition = " and name like concat('%',?,'%') ";
            array_push($para,(string)$func);
        }
        $sql .= $condition." order by convert(id,unsigned) desc ".$limit;
        $success = true;$funclist = array();$recordcount=0;
        try
        {
            $ds=$this->conn->GetData("table",$sql,$para);
            if ( $ds && $ds["table"]["recordcount"]>0)
            {
                $funclist = $ds["table"]["rows"];                
                if ( $pageindex==1 && $ds["table"]["recordcount"]==$record)
                {
                    $sql="select count(*) recordcount from we_function where 1=1 ".$condition;
                    $ds=$this->conn->GetData("table",$sql,$para);
                    if ( $ds && $ds["table"]["recordcount"]>0)
                      $recordcount = $ds["table"]["rows"][0]["recordcount"];
                }
            }
        }
        catch(\Exception $e)
        {
            $success = false;
            $this->logger->err($e->getMessage());
        }
        return array("success"=>$success,"recordcount"=>$recordcount,"funclist"=>$funclist);
    }
    
    //编辑角色信息
    public function editfunc($parameter)
    {
        $id=isset($parameter["id"])?$parameter["id"]:null;
        $name=$parameter["name"];
        $code=$parameter["code"];
        $type=$parameter["type"];
        $success=true;$sql="";$para=array();
        $exists = $this->checkFunc($id,$name);
        if ( $exists )
           return array("success"=>false,"exists"=>$exists );
        if ( empty($id))
        {
            $id = SysSeq::GetSeqNextValue($this->conn,"we_function","id");
            $sql="insert into we_function(id,name,code,`type`)values(?,?,?,?);";
            $para=array($id,$name,$code,$type);
        }
        else
        {
            $sql="update we_function set name=?,code=?,`type`=? where id=?;";
            $para=array($name,$code,$type,$id);
        }
        try
        {
            $this->conn->ExecSQL($sql,$para);
        }
        catch(\Exception $e)
        {
            $success=false;
            $this->logger->err($e->getMessage());
        }
        return array("success"=>$success,"exists"=>$exists,"id"=>$id);
    }
    
    //判断角色名称是否存在
    public function checkFunc($id,$name)
    {
        $exists=false;$sql="";$para=array();
        if ( empty($id))
        {
            $sql="select 1 from we_function where name=?;";
            $para=array((string)$name);
        }
        else
        {
            $sql="select 1 from we_function where name=? and id!=?;";
            $para=array((string)$name,(string)$id);
        }
        try
        {
            $ds=$this->conn->GetData("table",$sql,$para);
            if ( $ds && $ds["table"]["recordcount"]>0)
              $exists = true;
        }
        catch(\Exception $e)
        {
        }
        return $exists;
    }
    
    //删除角色
    public function del_func($parameter)
    {
        $funcid = $parameter["funcid"];
        $sql="delete from we_function where id=?;";
        $success = true;
        try
        {
            $this->conn->ExecSQL($sql,array((string)$funcid));
        }
        catch(\Exception $e)
        {
            $success = false;
            $this->logger->err($e->getMessage());
        }
        return array("success"=>$success);
    }    
    
    
}