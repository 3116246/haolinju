<?php

namespace Justsy\InterfaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Rbac\Func;
use Justsy\BaseBundle\Rbac\Role;
use Justsy\BaseBundle\Rbac\StaffRole;
use Justsy\BaseBundle\Management\Staff;

class RbacController extends Controller{
	//添加人员_角色
 	public function InsertStaffRoleAction(){
 		$request = $this->getRequest();  	
  	$conn = $this->get("we_data_access");
  	$conn_im = $this->get("we_data_access_im");
  	$currUser = $this->get('security.context')->getToken();
  	
    if(!empty($currUser)){
       $currUser = $currUser->getUser(); 
    }
    else{
    	  //当应用通过api接口调用时，不用登录，只能通过openid获取人员信息
    	  $baseinfoCtl = new Staff($conn,null,$request->get("openid"),$this->get("logger"));    	  
    	  $currUser = $baseinfoCtl->getSessionUser();
    }
    $rolecode=$request->get("rolecode");
    
  	$staffRole=new StaffRole($conn,$conn_im,$this->get("logger"));
  	
  	$re=$staffRole->InsertStaffRoleByCode($currUser->getUsername(),$rolecode,$currUser->getEno());
  	
  	$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
 	}
 	//修改人员_角色
 	public function UpdateStaffRoleAction(){
 		$request = $this->getRequest();  	
  	$conn = $this->get("we_data_access");
  	$conn_im = $this->get("we_data_access_im");
  	$currUser = $this->get('security.context')->getToken();
  	
    if(!empty($currUser)){
       $currUser = $currUser->getUser(); 
    }
    else{
    	  //当应用通过api接口调用时，不用登录，只能通过openid获取人员信息
    	  $baseinfoCtl = new Staff($conn,null,$request->get("openid"),$this->get("logger"));    	  
    	  $currUser = $baseinfoCtl->getSessionUser();
    }
    $rolecode=$request->get("rolecode");
    $newrolecode=$request->get("newrolecode");
    
  	$staffRole=new StaffRole($conn,$conn_im,$this->get("logger"));
  	
  	$re=$staffRole->UpdateStaffRoleByCode($currUser->getUsername(),$rolecode,$newrolecode,$currUser->getEno());
  	
  	$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
 	}
 	//删除人员_角色
 	public function DeleteStaffRoleAction(){
 		$request = $this->getRequest();  	
  	$conn = $this->get("we_data_access");
  	$conn_im = $this->get("we_data_access_im");
  	$currUser = $this->get('security.context')->getToken();
  	
    if(!empty($currUser)){
       $currUser = $currUser->getUser(); 
    }
    else{
    	  //当应用通过api接口调用时，不用登录，只能通过openid获取人员信息
    	  $baseinfoCtl = new Staff($conn,null,$request->get("openid"),$this->get("logger"));    	  
    	  $currUser = $baseinfoCtl->getSessionUser();
    }
    $rolecode=$request->get("rolecode");
    
  	$staffRole=new StaffRole($conn,$conn_im,$this->get("logger"));
  	
  	$re=$staffRole->DeleteStaffRoleByCode($currUser->getUsername(),$rolecode,$currUser->getEno());
  	
  	$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
 	}
 	//获取用户自定义角色
 	public function getCustomRolesAction()
 	{
 		$request = $this->getRequest();  	
  	$conn = $this->get("we_data_access");
 		$user = $this->get('security.context')->getToken()->getUser();
 		
 		$sql="select a.staff as login_account,b.name as rolename,a.roleid,b.code as rolecode from we_staff_role a left join we_role b on b.id=a.roleid where a.staff=? and b.eno!='' and b.eno is not null";
 		$params=array($user->getUserName());
 		$ds=$conn->Getdata('info',$sql,$params);
 		$rows=$ds['info']['rows'];
 		
 		$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($rows).");" : json_encode($rows));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
 	}
}
