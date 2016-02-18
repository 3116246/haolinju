<?php

namespace Justsy\AdminAppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;


class SysController extends Controller
{    
    public function htryqxAction()
    {
        return $this->render('JustsyAdminAppBundle:Sys:htryqx.html.twig', array());
    }
  
    //角色管理
    public function roleindexAction()
    {
        return $this->render('JustsyAdminAppBundle:Sys:role.html.twig', array());
    }

    //功能点管理
    public function funcindexAction()
    {
        return $this->render('JustsyAdminAppBundle:Sys:function.html.twig', array());
    }  

    //取权限人员列表
    public function SearchListAction()
    {
        $re = array();
        $request = $this->getRequest();
        $eno= $this->get('security.context')->getToken()->getUser()->eno;
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
        try 
        {
            $para = array();
            $sql = "select login_account, nick_name staff from we_staff a ";
            $condition = " where not exists (select 1 from we_micro_account where number=a.login_account) ";
            //排除广播员(广播员在创建时已分配权限且只能有一个权限)
            $condition .= " and not exists(select 1 from we_announcer announcer where announcer.login_account=a.login_account) ";
            //排除系统管理员
            $condition .= " and position(a.login_account in (select sys_manager from we_enterprise where eno=? limit 1))=0 ";
            $condition .= " and eno=? ";
            array_push($para,(string)$eno,(string)$eno);
            if ( !empty($staff))
            {
                if (strlen($staff)>mb_strlen($staff,'utf8'))
                    $condition .= " and nick_name like concat('%',?,'%') ";
                else
                    $condition .= " and login_account like concat(?,'%') ";
                array_push($para,(string)$staff);
            }
            $sql .= $condition.$limit;
            $ds = $da->GetData("we_staff", $sql,$para);
            if ( $ds!=null && $ds["we_staff"]["recordcount"]>0)
                $data = $ds["we_staff"]["rows"];
            if ($pageindex==1 &&  $ds["we_staff"]["recordcount"]>=$record)
            {
                $sql = "select count(*) total from we_staff a ".$condition;
                $ds = $da->GetData("staff",$sql,$para);
                
                
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
    
  //取权限人员功能列表
	public function getQXRYMenuAction()
	{    
		$re = array("returncode" => ReturnCode::$SUCCESS);
		$request = $this->getRequest();
		$da = $this->get('we_data_access'); 
		$login_account = $request->get("login_account");
		$success = true;
		$msg = "";
		$menus = array();
		$exists = false;
		try 
		{
			//用户是否有菜单权限
			$sql = "select 1 from mb_staff_menu where staff_id=?;";
			$ds = $da->GetData("table",$sql,array((string)$login_account));
			if ( $ds && $ds["table"]["recordcount"]>0)
			  $exists = true;
			$sql = "select b.menu_id id, b.parent_menu_id pId, b.menu_name name, case when a.staff_id is not null then 'true' else 'false' end checked, 'true' open
              from mb_menus b left join mb_staff_menu a on a.menu_id=b.menu_id and a.staff_id=? order by b.order_no; ";
			$params = array((string)$login_account);
			$ds = $da->GetData("mb_staff_menu", $sql, $params);
			$menus = $ds["mb_staff_menu"]["rows"];
		} 
		catch (\Exception $e) 
		{
			$success = false;
			$msg = "获取用户菜单权限失败！";
			$this->get('logger')->err($e);
		}
    $result = array("success"=>$success,"msg"=>$msg,"menus"=>$menus,"exists"=>$exists);
    $response = new Response(json_encode($result));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
	}

  //保存用户权限变动日志
	public function saveQXRYMenuAction()
	{    
		$request = $this->getRequest();
		$da = $this->get('we_data_access');
		$login_account = $request->get("login_account");
		$menuid = $request->get("menuid");
		$success = true;
		$msg = "";
		try
		{
			//用户权限增减日志
		  $syslog = new \Justsy\AdminAppBundle\Controller\SysLogController();
		  $syslog->setContainer($this->container);
		  $desc = "";
			if ( empty($menuid)){
				$sql = "delete from mb_staff_menu where staff_id=?";
				$da->ExecSQL($sql,array((string)$login_account));
				$desc = "取消了用户".$login_account."的所有菜单权限！";
				$syslog->AddSysLog($desc,"菜单权限");	
			}
			else {
				$sqls = array();
				$paras = array();
				$sql = "select a.menu_id,menu_name from mb_menus a inner join mb_staff_menu b on a.menu_id=b.menu_id where staff_id=?";
				$ds = $da->GetData("table",$sql,array((string)$login_account));
				if ( $ds && $ds["table"]["recordcount"]>0){
	        //增加权限
	        $menuName = "";
	        for($i=0;$i< count($menuid);$i++){
	        	$menu_id = $menuid[$i];
	        	$status = true;
	        	for($j=0;$j< $ds["table"]["recordcount"];$j++){
	        		$menuid2 = $ds["table"]["rows"][$j]["menu_id"];
	        		if ( $menu_id==$menuid2){
	              $status = false;
	        			break;
	        		}
	        	}
	        	if ( $status){
	        		$menuname = $this->getMenuName($menu_id);
	        		$menuName .= empty($menuname) ? "":"【".$menuname."】、"; 
	        	}
	        }
	        if ( !empty($menuName))
	          $desc = "为用户账号：".$login_account."授予了菜单权限".rtrim($menuName,"、");
	        $menuName = "";
	        for($j=0;$j< $ds["table"]["recordcount"];$j++){
	        	$status = true;
	      		$menuid2 = $ds["table"]["rows"][$j]["menu_id"];
	      		for($i=0; $i< count($menuid);$i++){
	      			$menu_id = $menuid[$i];
	      			if ($menuid2 == $menu_id){
	      				$status = false;
	      				break;
	      			}
	      		}
	      		if ( $status){
	        		$menuname = $this->getMenuName($menuid2);
	        		$menuName .= empty($menuname) ? "":"【".$menuname."】、";
	        	}
	        }
	        if ( !empty($menuName))
	          $desc .= "取消了菜单权限:".rtrim($menuName,"、");
	        if ( !empty($desc))
	          $syslog->AddSysLog($desc,"菜单权限");
	        		
					$sql = "delete from mb_staff_menu where staff_id=?";
	        array_push($sqls,$sql);
	        array_push($paras,array((string)$login_account));			
				}
				else{
					$desc = "";
					for($i=0;$i< count($menuid);$i++){
						$menuname = $this->getMenuName($menuid[$i]);
						$desc .= empty($menuname) ? "":"【".$menuname."】、";
					}
					$desc = "为用户账号:".$login_account."授予了以下菜单权限：".rtrim($desc,"、");
					$syslog->AddSysLog($desc,"菜单权限");
				}			
				//增减权限
				$sql = "insert into mb_staff_menu (staff_id, menu_id) values(?, ?)";
				for ($i=0; $i<count($menuid); $i++)
				{
					 array_push($sqls,$sql);
					 array_push($paras,array((string)$login_account,(string)$menuid[$i]));
				}
				$da->ExecSQLS($sqls,$paras);
		  }
		} 
		catch (\Exception $e) 
		{
			$success = false;
			$msg = "保存用户菜单权限失败！";
			$this->get('logger')->err($e->getMessage());
		}
		$result = array("success"=>$success,"msg"=>$msg);
    $response = new Response(json_encode($result));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
	}
	
	//获得菜单名称
	private function getMenuName($menuid)
	{
		 $menu_name = "";
		 $da = $this->get('we_data_access');
		 $sql = "select menu_name from mb_menus where parent_menu_id !='###' and menu_id=?";
		 $ds = $da->GetData("table",$sql,array((string)$menuid));
		 if ( $ds && $ds["table"]["recordcount"]>0){
		 	 $menu_name = $ds["table"]["rows"][0]["menu_name"];
		 }
		 return $menu_name;
	}
	
	//清除用户权限
	public function CleareRoleAction()
	{
		$da = $this->get('we_data_access');
		$request = $this->getRequest();
		$login_account = $request->get("login_account");
		$success = true;
		$msg = "";
		try 
		{
			$sql = "delete from mb_staff_menu where staff_id=?";
			$da->ExecSQL($sql,array((string)$login_account));
			//用户权限增减日志
		  $syslog = new \Justsy\AdminAppBundle\Controller\SysLogController();
		  $syslog->setContainer($this->container);
		  $desc = "取消了用户".$login_account."的所有菜单权限！";
			$syslog->AddSysLog($desc,"菜单权限");	
		  
		} 
		catch (\Exception $e) 
		{
		  $msg = "清除用户菜单权限失败！";
			$success = false;
			$this->get('logger')->err($e);
		}
		$result = array("success"=>$success,"msg"=>$msg);
    $response = new Response(json_encode($result));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
	}
}
