<?php
namespace Justsy\BaseBundle\Management;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Management\Identify;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Login\UserSession;
use Justsy\BaseBundle\Management\MicroAccountMgr;
use Justsy\BaseBundle\Common\Cache_Enterprise;

//权限管理
class Role
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
    
    //获得角色列表
    public function getRoleList($parameter)
    {   
        $sql ="select id,name from we_role where role_type='sys' order by id asc;";
        $method_para = array("sql"=>$sql);
        $data = $this->getData($method_para);
        return $data;
    }
    
    //获得功能列表
    public function getFunctionList($parameter)
    {
        $sql="select id functionid,name from we_function where type='sys' order by id asc";
        $method_para = array("sql"=>$sql);
        $data = $this->getData($method_para);
        return $data;
    }
    
    //获得角色对应功能列表
    public function getRoleFunction($parameter)
    {
        $roleid = $parameter["roleid"];
        $success = true;$f_data=array();$u_data=array();
        //角色对应的功能点
        $sql="select id,functionid from we_role_function where roleid=?;";
        $method_para = array("sql"=>$sql,"para"=>array((string)$roleid));
        $data = $this->getData($method_para);
        if ( $data["success"] )
          $f_data = $data["returndata"];
        else
          $success = false;
        //角色对应的人员
        $sql="select id,login_account,nick_name from we_staff_role a inner join we_staff b on staff=login_account where roleid=?;";
        $method_para = array("sql"=>$sql,"para"=>array((string)$roleid));
        $data = $this->getData($method_para);
        if ( $data["success"])
          $u_data = $data["returndata"]; 
        else
          $success = false;      
        return array("success"=>$success,"f_data"=>$f_data,"u_data"=>$u_data);
    }
    
    public function search_staff($parameter)
    {
        $url = $this->container->getParameter('FILE_WEBSERVER_URL');
        $staff=$parameter["staff"];
        $user = $parameter["user"];
        $roleid=$parameter["roleid"];
        $eno = $user->eno;
        $sql="select login_account,nick_name,case when ifnull(photo_path,'')='' then '' else concat('$url',photo_path) end header 
        from we_staff a where not exists(select 1 from we_staff_role b where a.login_account=b.staff and roleid=?)
             and not exists(select 1 from we_micro_account where a.login_account=number) and a.eno=? ";
        $para = array((string)$roleid,(string)$eno);
        if (!empty($staff))
        {
            if(strlen($staff)==mb_strlen($staff,'utf8'))
            {
              $sql.=" and a.login_account like concat('%',?,'%') ";
     	    	}
     	    	else
     	    	{
     	    	   $sql.=" and a.nick_name like concat('%',?,'%') ";
     	    	}
     	    	$sql .= " order by nick_name asc ";
     	    	array_push($para,(string)$staff);
        }
        else
        {
            $sql .= " order by nick_name limit 100";
        }
        $method_para = array("sql"=>$sql,"para"=>$para);
        $data = $this->getData($method_para);
        return $data;
    }
    
    //保存权限
    public function saveRole($parameter)
    {
        $roleid = $parameter["roleid"];
        $add_function = isset($parameter["add_function"]) ? $parameter["add_function"]:Array();
        $del_function = isset($parameter["del_function"]) ? $parameter["del_function"]:Array();
        $login_account = isset($parameter["login_account"]) ? $parameter["login_account"]:Array();
        $success = true;
        $user = $parameter["user"];
        $sqls = array();$paras = array();
        //新加数据
        if ( $success )
        {
            //添加we_role_function表
            //删除记录
            if ( count($del_function)>0)
            {
               $sql="delete from we_role_function where roleid=? and functionid in(".implode(",",$del_function).");";
               $this->db_conn->ExecSQL($sql,array((string)$roleid));
            }
            $sqls=array();$paras=array();
            for($i=0;$i< count($add_function);$i++)
            {
                $id = SysSeq::GetSeqNextValue($this->db_conn,"we_role_function","id");
                $sql="insert into we_role_function(id,roleid,functionid)values(?,?,?);";
                array_push($sqls,$sql);
                array_push($paras,array((string)$id,(string)$roleid,(string)$add_function[$i]));                                          
            }
            try
            {
                if ( count($sqls)>0)
                   $this->db_conn->ExecSQLs($sqls,$paras);
            }
            catch(\Exception $e)
            {
                $success = false;
                $this->logger->err($e->getMessage());
            }
            //添加we_staff_role
            if ( $success )
            {
                $sqls=array();$paras=array();
                for($i=0;$i< count($login_account);$i++)
                {
                    $id = SysSeq::GetSeqNextValue($this->db_conn,"we_staff_role","id");
                    $sql="insert into we_staff_role(id,staff,roleid,eno)values(?,?,?,?);";
                    array_push($sqls,$sql);
                    array_push($paras,array((string)$id,(string)$login_account[$i],(string)$roleid,(string)$user->eno));
                }
                try
                {
                    if ( count($sqls)>0)
                       $this->db_conn->ExecSQLs($sqls,$paras);
                }
                catch(\Exception $e)
                {
                    var_dump($e->getMessage());
                    $success = false;
                    $this->logger->err($e->getMessage());
                }
            }            
        }
        return array("success"=>$success);
    }
    
    //获得数据
    private function getData($parameter)
    {
        $sql=$parameter["sql"];
        $para=isset($parameter["para"]) ? $parameter["para"]:array();        
        $success = true;$data = array();
        try
        {   
            $ds = null;
            if ( count($para)==0)
              $ds=$this->db_conn->GetData("table",$sql);
            else
              $ds=$this->db_conn->GetData("table",$sql,$para);
            if ( $ds && $ds["table"]["recordcount"]>0)
              $data=$ds["table"]["rows"];
        }
        catch(\Exception $e)
        {
            $success = false;
            $this->logger->err($e->getMessage());
        }
        return array("success"=>$success,"returndata"=>$data);
        
    }
    
    //删除权限
    public function delRole($parameter)
    {
        $success = true;
        $id = $parameter["id"];
        $sql = "delete from we_staff_role where id=?;";
        try
        {
            $this->db_conn->ExecSQL($sql,array((string)$id));
        }
        catch(\Exception $e)
        {
            $success = false;
            $this->logger->err($e->getMessage());
        }
        return array("success"=>$success);
    }
    
    //获得用户权限数据或详细
    public function getUserRole($parameter)
    {
        $login_account = isset($parameter["login_account"]) ? $parameter["login_account"] : null;
        if ( empty($login_account))
        {
            $user=$parameter["user"];
            $login_account = $user->getUserName();
        }
        $type = isset($parameter["type"]) ? $parameter["type"] : "count";
        $data = Array();        
        if ( $type == "count")  //取总数
        {
            $sql = "select count(*) rowcount
                    from we_role_function a inner join we_role b on a.roleid=b.id inner join we_function c on a.functionid=c.id  inner join we_staff_role staff on staff.roleid=a.roleid 
                    where b.role_type='sys' and c.type='sys' and staff=?;";
            $para = array((string)$login_account);
            $data = $this->getData(array("sql"=>$sql,"para"=>$para)); 
            $recordcount = $data["returndata"][0]["rowcount"];
            $data["recordcount"] = $recordcount;
        }
        else
        {
            //判断是否系统管理员
            $isAdmin = $this->isAdmin($login_account);
            if ( $isAdmin)
            {
                $data = array("success"=>true,"returndata"=>array(),"isAdmin"=>$isAdmin);              
            }
            else
            {
                $sql = "select distinct a.functionid,c.name 
                        from we_role_function a inner join we_role b on a.roleid=b.id inner join we_function c on a.functionid=c.id  inner join we_staff_role staff on staff.roleid=a.roleid 
                        where b.role_type='sys' and c.type='sys' and staff=?;";
                $para = array((string)$login_account);
                $data = $this->getData(array("sql"=>$sql,"para"=>$para));
                $item = array();
                $list = $data["returndata"];
                if ( !empty($list) && count($list)>0)
                {
                    for($i=0;$i<count($list);$i++)
                    {
                        array_push($item,$list[$i]["name"]);
                    }
                }
                $data["returndata"] = $item;
                $data["isAdmin"] = false;
            }            
        }        
        return $data;
    }
    
    //判断是否超级管理员
    public function isAdmin($login_account)
    {
        $isAdmin = false;
        $sql = "select 1 from we_enterprise where create_staff=?;";
        try
        {
            $ds=$this->db_conn->GetData("table",$sql,array((string)$login_account));
            if ( $ds && $ds["table"]["recordcount"]>0)
              $isAdmin = true;
        }
        catch(\Exception $e)
        {
        }
        return $isAdmin;        
    }
    
    //保存应用权限
    public function save_approle($parameter)
    {
        $appid = isset($parameter["appid"]) ? $parameter["appid"]:"";
        $deptid = isset($parameter["deptid"]) ? $parameter["deptid"] : Array();
        $allow_staffid = isset($parameter["allow_staffid"]) ? $parameter["allow_staffid"] : Array();
        $allow_staff = isset($parameter["allow_staff"]) ? $parameter["allow_staff"] : Array();
        $prohibition_staffid = isset($parameter["prohibition_staffid"]) ? $parameter["prohibition_staffid"] : Array();
        $sqls = array();$paras = array();$success = true;
//        //获得原来的数据记录
//        $allow_deptid=Array();$allow_staff=Array();$
//        $sql="select id,objid,type from we_app_role where appid=?;";
//        try
//        {
//            $ds = $this->db_conn->GetData("table",$sql,array((string)$appid));
//            if ( $ds && $ds["table"]["recordcount"]>0)
//            {
//                $oldrole = $ds["table"]["rows"];
//            }
//        }
//        catch(\Exception $e)
//        {
//            $this->logger->err($e->getMessage());
//        }                    
        //删除应用对应的数据记录
        $sql = "delete from we_app_role where appid=?;";
        try
        {
            $this->db_conn->ExecSQL($sql,array((string)$appid));
        }
        catch(\Exception $e)
        {
            $this->logger->err($e->getMessage());
        }
        
        //选择部门的处理
        if ( count($deptid)>0 )
        {
            for($i=0;$i < count($deptid);$i++)
            {
                $sql = "insert into we_app_role(appid,objid,type)values(?,?,1);";
                array_push($sqls,$sql);
                array_push($paras,array((string)$appid,(string)$deptid[$i]));        
            }            
        }
        
        
        
        //允许访问的成员
        if ( count($allow_staffid)>0)
        {
            for($i=0;$i< count($allow_staffid);$i++)
            {
                $sql = "insert into we_app_role(appid,objid,type)values(?,?,2);";
                array_push($sqls,$sql);
                array_push($paras,array((string)$appid,(string)$allow_staffid[$i]));        
            }
        }
        //禁止访问的成员
        if ( count($prohibition_staffid)>0)
        {
            for($i=0;$i< count($prohibition_staffid);$i++)
            {
                $sql = "insert into we_app_role(appid,objid,type)values(?,?,3);";
                array_push($sqls,$sql);
                array_push($paras,array((string)$appid,(string)$prohibition_staffid[$i]));        
            }
        }
        if ( count($sqls)>0 && count($paras)>0)
        {
            try
            {
                $this->db_conn->ExecSQLs($sqls,$paras);
            }
            catch(\Exception $e)
            {
                $success = false;
                $this->logger->err($e->getMessage());
            }
        }
        return array("success"=>$success);
    }
    
    //保存权限时写入应用订阅表
    private function AppSubscibe($eno,$deptid,$staffid,$staff,$removestaffid)
    {
        $sql="";$para=Array();
        $condition = explode(",",$removestaffid);
        if ( $deptid=="v".$eno)
        {
            $sql="select login_account,nick_name from we_staff where eno=?;";
                     
        }
        else
        {
            $sql="";            
        }
        
    }
    
    //获得应用所对应的权限
    public function get_approle_detial($parameter)
    {
        $appid = $parameter["appid"];
        $user = $parameter["user"];
        $eno = $user->eno;
        //部门的处理
        $treedata = array();$checkdata = array();
        $sql="select objid from we_app_role where appid=? and type=1;";
        try
        {
            $ds=$this->db_conn->GetData("table",$sql,array((string)$appid));
            if ($ds && $ds["table"]["recordcount"]>0)
            {
                for($i=0;$i< $ds["table"]["recordcount"];$i++)
                {
                    array_push($checkdata,$ds["table"]["rows"][$i]["objid"]);
                }                
                if ( count($checkdata)>0)
                  $treedata = $this->get_parent_deptid($eno,$checkdata);
            }
            
        }
        catch(\Exception $e)
        {
        }
        //人员部分的处理
        $sql="select a.id,login_account,fafa_jid,nick_name,a.type 
              from we_app_role a inner join we_staff b on objid=b.login_account where appid=? and a.type in(2,3) order by type asc;";
        $para = array((string)$appid);
        $success=true;$staffs=array();
        try
        {
            $ds = $this->db_conn->GetData("table",$sql,$para);
            if ( $ds && $ds["table"]["recordcount"]>0)
                $staffs = $ds["table"]["rows"];
        }
        catch(\Exception $e)
        {
            $success = false;
            $this->logger->err($e->getMessage());
        }        
        $result = array("success"=>$success,"treedata"=>$treedata,"checkdata"=>$checkdata,"staffs"=>$staffs);
        return $result;
    }
    
    public function get_parent_deptid($eno,$deptid)
    {
        $deptid = implode(",",$deptid);
        $result = array();
        $eno = "v".$eno;
        $len = strlen($eno);
        $sql="select distinct replace(substring(path,position(? in path)+?),deptid,'') path
              from im_base_dept where deptid in(".$deptid.");";
        $para=array((string)$eno,(string)$len);
        try
        {
            $ds=$this->db_conn_im->GetData("table",$sql,$para);
            if ( $ds && $ds["table"]["recordcount"]>0)
            {
                $deptids = array();
                $deptMgr = new \Justsy\AppCenterBundle\ManagerBase\Dept($this->container);
                for($j=0;$j< $ds["table"]["recordcount"];$j++)
                {
                    $path = $ds["table"]["rows"][$j]["path"];
                    $path = str_replace($deptid,"",$path);
                    $path = ltrim($path,"/");
                    $path = rtrim($path,"/");
                    if ( empty($path) ) continue;
                    $deptids = explode("/",$path);                    
                    for($i=0;$i< count($deptids);$i++)
                    {
                        $id=$deptids[$i];
                        $tree = $deptMgr->getSelectedTree($eno,$id);
                        if ( $tree["success"])
                        {
                            array_push($result,array("deptid"=>$id,"dept"=>$tree["datasource"]));
                        }                      
                    }
                }
            }
        }
        catch(\Exception $e)
        {
            $this->logger->err($e->getMessage());   
        }
        return $result;
    }
    
    //删除应用权限人员
    public function del_approle_staff($parameter)
    {
         $appid = isset($parameter["appid"]) ? $parameter["appid"]:null;
         $jid = isset($parameter["jid"]) ? $parameter["jid"] : null;
         $success = true;
         $sql = "delete from we_app_role where objid=? and appid=?;";
         $para = array((string)$jid,(string)$appid);
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
