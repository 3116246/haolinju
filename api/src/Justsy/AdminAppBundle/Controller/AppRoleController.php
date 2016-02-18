<?php

namespace Justsy\AdminAppBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Common\SendMessage;

class AppRoleController extends Controller
{    
    public function IndexAction()
    {
    	$list = json_encode($this->getAppList());
    	return $this->render('JustsyAdminAppBundle:Sys:approle.html.twig', array("AppList"=>$list));
    }
    
    private function getAppList()
    {
    	 $da = $this->get('we_data_access');
	     $curuser = $this->get('security.context')->getToken()->getUser();
       $eno  = $curuser->eno;
       $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
       $sql = "select appid,appname,apptype,case when logo is null or logo='' then null else concat('$FILE_WEBSERVER_URL',logo) end applogo,
                      concat((select case when max(publishdate) is null then 'V 0.' else 'V 1.' end from we_apps_publish a where a.appid=b.appid),version) version,
                      (select case when count(*)>0 then 1 else 0 end from mb_approle approle where approle.appid=b.appid) count 
               from we_appcenter_apps b where apptype like '99%' and appdeveloper=? order by sortid asc;";
       $ds = $da->GetData('applist',$sql,array((string)$eno));
       $list = array();
       if ($ds && $ds["applist"]["recordcount"]>0 )
          $list = $ds["applist"]["rows"];
        return $list;
    }
    
    //根据appid获得应用权限
    public function getRoleByAppIDAction()
    {
    	$da = $this->get("we_data_access");
    	$request = $this->getRequest();
    	$appid = $request->get("appid");
    	$sql = "select level1,case when leveltype=3 and ifnull(level2,'')!='' then concat( level1,'-',level2) else level2 end as level2,
                     case when leveltype=3 and ifnull(level3,'')!='' then concat(level1,'-',level2,'-',level3) else level3 end as level3,
                     case when leveltype=3 and ifnull(level4,'')!='' then concat(level1,'-',level2,'-',level3,'-',level4) end as level4,leveltype as 'type' from mb_approle where appid=?";
    	$ds = $da->GetData("role",$sql,array((string)$appid));
    	$result = array();
    	if ( $ds && $ds["role"]["recordcount"]>0)
    	  $result = $ds["role"]["rows"];
    	$response = new Response(json_encode($result));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }
        
    //保存应用权限
    public function SaveRoleAction()
    { 	
    	$request = $this->getRequest();
    	$appid = $request->get("appid");
    	$roles = $request->get("roles");
    	$clear = $request->get("clear");
    	$result = array();
    	$da = $this->get("we_data_access");
    	if ( $clear==1){
    	  $ex = $this->DeleteRole($da,$appid);
    	  if ($ex){
    	  	$result = array("success"=>true,"message"=>"清除用户权限成功！","count"=>0);
    	  	//记录日志
    	  	$syslog = new \Justsy\AdminAppBundle\Controller\SysLogController();
	        $syslog->setContainer($this->container);
	        $user = $this->get('security.context')->getToken()->getUser()->getUserName();
	        $sql = "select appname from we_appcenter_apps where appid=?;";
	        $ds = $da->GetData("table",$sql,array((string)$appid));
	        if ( $ds && $ds["table"]["recordcount"]>0){
	        	$desc = "清除了应用【".$ds["table"]["rows"][0]["appname"]."】的所有人员查看权限。";
	        	$syslog->AddSysLog($desc,"应用权限");
	        }
    	  }
    	  else{
    	  	$result = array("success"=>false,"message"=>"清除用户权限失败！");
    	  }
    	}
    	else {
    	  $result = $this->EditAppRole($appid,$roles);    	  
    	}
    	if ( $result["success"]){
    	  $this->sendPresence();
    	}
    	$response = new Response(json_encode($result));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }
    
   
    
   private function sendPresence()
   {
	  	$da = $this->get('we_data_access');
	  	$da_im = $this->get('we_data_access_im');	  		  
	  	$my_jid = $this->get('security.context')->getToken()->getUser()->fafa_jid; 
			//操作成功发送出席
		  $sendMessage = new SendMessage($da,$da_im);
		  $parameter = array(
		                      "flag"=>"online",
		                      "title"=>"portal_publish",
		                      "message"=>"approlechange",
		                      "container"=>$this->container,
		                      "formjid"=>$my_jid
		                     );
		  $sendMessage->sendImPresence($parameter); 
   }    
    
    //维护内容发布子表
	  public function EditAppRole($appid,$roles){
	  	$da = $this->get("we_data_access");
	  	$sql = "select appid from mb_approle where appid=?";
    	$ds = $da->GetData("table",$sql,array((string)$appid));
    	if ( $ds && $ds["table"]["recordcount"]>0)
    	  $this->DeleteRole($da,$appid);
    	//添加权限
	  	$sqls=array();
	  	$paras=array();
	  	$levels = null;
	  	$obj = null;
	  	//组织机构id
	  	if ( isset($roles["zzjg"])){
		  	$levels = $roles["zzjg"];
		  	if ($levels!=null && count($levels)>0){
		  		for($i=0;$i< count($levels);$i++){
		  			$sql = "insert into mb_approle(appid,level1,leveltype)values(?,?,1)";
		  			$para = array((string)$appid,(string)$levels[$i]);
		  			array_push($sqls,$sql);
		  			array_push($paras,$para);
		  		}
		  	}
	    }
	  	//职级维度
	  	if ( isset($roles["zjwd"])){
		  	$levels = $roles["zjwd"];	  	
		  	if ($levels!=null && count($levels)>0){
		  		for($i=0;$i< count($levels);$i++){
		  			$zjlb=null;$glzj=null;$ywzj=null;
		  			$sql="insert into mb_approle(appid,level1,level2,level3,leveltype)values(?,?,?,?,2)";
		  			$obj = $levels[$i];
		  			$zjlb = $obj["zjlb"];
		  			if ( isset($obj["glzj"]))
		  			  $glzj = $obj["glzj"];
		  			if (isset($obj["ywzj"]))
		  			  $ywzj = $obj["ywzj"];	
		  			$para = array((string)$appid,$zjlb,$glzj,$ywzj);
		  			array_push($sqls,$sql);
		  			array_push($paras,$para);
		  		}
		  	}
	    }	    
	  	//人员分类
	  	if ( isset($roles["ryfl"])){
		  	 $levels = $roles["ryfl"];
		  	 $level_array = array();
		  	 if ($levels!=null && count($levels)>0){
		  		for($i=0;$i< count($levels);$i++){
		  			$level1 = null;$level2 = null;$level3 = null;$level4 = null;
		  			$sql="insert into mb_approle(appid,level1,level2,level3,level4,leveltype)values(?,?,?,?,?,3)";
		  			$obj = $levels[$i];
		  			if (isset($obj["level1"]))
		  			  $level1=$obj["level1"];
		  			if (isset($obj["level2"])){
		  			  $level2=$obj["level2"];
		  			  $level_array=explode("-",$level2);
		  			  if ($level_array!=null && count($level_array)>0)
		  			   $level2 = end($level_array);
		  			}
		  			if (isset($obj["level3"])){
		  			  $level3=$obj["level3"];
		  			  $level_array=explode("-",$level3);
		  			  if ($level_array!=null && count($level_array)>0)
		  			   $level3 = end($level_array);
		  			}
		  			if (isset($obj["level4"])){
		  			  $level4=$obj["level4"];
		  			  $level_array=explode("-",$level4);
		  			  if ($level_array!=null && count($level_array)>0)
		  			   $level4 = end($level_array);			  			  
		  			}
		  			$para = array((string)$appid,$level1,$level2,$level3,$level4);
		  			array_push($sqls,$sql);
		  			array_push($paras,$para);
		  		}
		  	}
		  }
	  	//员工号
	  	if ( isset($roles["ygh"])){
		  	$levels = $roles["ygh"];
		  	if ($levels!=null && count($levels)>0){
		  		for($i=0;$i< count($levels);$i++){
		  			$sql="insert into mb_approle(appid,level1,leveltype)values(?,?,4)";
		  			$para = array((string)$appid,(string)$levels[$i]);
		  			array_push($sqls,$sql);
		  			array_push($paras,$para);
		  		}
		  	}
		  }
	  	//排除员工号
	  	if ( isset($roles["noygh"])){
		  	$levels = $roles["noygh"];
		  	if ($levels!=null && count($levels)>0){
		  		for($i=0;$i< count($levels);$i++){
		  			$sql="insert into mb_approle(appid,level1,leveltype)values(?,?,5)";
		  			$para = array((string)$appid,(string)$levels[$i]);
		  			array_push($sqls,$sql);
		  			array_push($paras,$para);
		  		}
		  	}
		  }
		  $result = array("success"=> true,"message"=>"");
	  	try{
	  		if (count($sqls)>0 && count($paras)>0)
	  	    $da->ExecSQLS($sqls,$paras);	  	    
	  	  if (empty($appid))  	  
	  	    $result = array("success"=> true,"message"=>"修改应用权限成功！","count"=>1);
	  	  else
	  	    $result = array("success"=> true,"message"=>"保存应用权限成功！","count"=>1);
	  	}
	  	catch (Exception $e) {
	  		$this->get("logger")->err($e->getMessage());
	  	  if (empty($appid))  	  
	  	    $result = array("success"=> false,"message"=>"修改应用权限失败！");
	  	  else
	  	    $result = array("success"=> false,"message"=>"保存应用权限失败！");
	  	}
	  	return $result;
	  }
	  
	  //删除已存在的权限
	  private function DeleteRole($da,$appid){
	  	try
	  	{
	  		$sql ="delete from mb_approle where appid=?";
	  	  $da->ExecSQL($sql,array((string)$appid));	 
	    }
	    catch(\Exception $e){
	    	return false;
	    }
	    return true;
	  }
    
    //根据用户查看具有权限的应用
    public function SearchAppRoleAction()
    {    	
    	 $result = $this->getRequest();
    	 $worknumber = $result->get("worknumber");
    	 $result = array();
    	 if (empty($worknumber)){
    	 	 $result = $this->SearchAppRole(null,true);
    	 }
    	 else{
    	 	 $da = $this->get('we_data_access');
    	 	 $code=ReturnCode::$SUCCESS;$data = array();$msg = "";
    	 	 if (strpos($worknumber,"@")){
    	 	 	 $account = explode("@",$worknumber);
    	 	 	 $worknumber= $account[0];
    	 	 }
    	 	 $sql="select zhr_pa903112 from mb_hr_7 where zhr_pa903112=? 
    	 	       union select login_account from we_staff where ldap_uid=?";
    	 	 $ds = $da->GetData("table",$sql,array((string)$worknumber,(string)$worknumber));
    	 	 if ($ds && $ds["table"]["recordcount"]>0){
    	 	 	 $result = $this->SearchAppRole($worknumber,false);
    	 	 }
    	 	 else{
    	 	 	 $result = array("returncode"=>ReturnCode::$SYSERROR,"msg"=>"查询的工号或用户账号不存在！","data"=>array());
    	 	 }
    	 }
    	 $response = new Response(json_encode($result));
       $response->headers->set('Content-Type', 'text/json');
       return $response;
    }
    
    //获得我的应用列表
    public function myAppListAction()
    {
       $da = $this->get('we_data_access');
    	 $curuser = $this->get('security.context')->getToken()->getUser();
    	 $request = $this->getRequest();
	     $eno  = $curuser->eno;
	     $work_number = $curuser->getUsername();
	     if (strpos($work_number,"@")){
	     	 $account = explode("@",$work_number);
	     	 $work_number = $account[0];
	     }
       $code=ReturnCode::$SUCCESS;
       $data = array();
       $msg = "";
       //获得权限的SQL语句
       try
       {
       	 $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
     	 	 $getsql = $this->getSql($work_number);
         $sql2 = $getsql["sql"];         
         $para = $getsql["para"];
         $sql = "select a.appid,appname,case when logo is null or logo='' then null else concat('$FILE_WEBSERVER_URL',logo) end applogo
                 from we_appcenter_apps a inner join (".$sql2.") role_app on a.appid=role_app.appid and not exists(select 1 from mb_approle role where a.appid=role.appid and level1=? and leveltype=5) and apptype like '99%' and appdeveloper=? order by sortid asc;"; 
	       array_push($para,(string)$work_number);
	       array_push($para,(string)$eno);
	       $ds = $da->GetData('applist',$sql,$para);
	       if ($ds && $ds["applist"]["recordcount"]>0 ){
	         $data = $ds["applist"]["rows"];
	       }
       }
       catch(\Exception $e){
       	 $this->get("logger")->err($e->getMessage());
       	 $code=ReturnCode::$SYSERROR;
       	 $msg = "查询数据记录失败！";
       }
       $result = array("returncode"=>$code,"msg"=>$msg,"data"=>$data);
       $response = new Response(json_encode($result));
       $response->headers->set('Content-Type', 'text/json');
       return $response;
    }
    
    private function SearchAppRole($staffid,$state)
    {
    	 $da = $this->get('we_data_access');
    	 $curuser = $this->get('security.context')->getToken()->getUser();
	     $eno  = $curuser->eno;
	     if (empty($staffid))
	     	 $staffid = $curuser->getUsername();
	     if (strpos($staffid,"@")){
	     	 $account = explode("@",$staffid);
	     	 $staffid = $account[0];
	     }
       $code=ReturnCode::$SUCCESS;
       $data = array();
       $msg = "";
       //获得权限的SQL语句
       try
       {
       	 $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
       	 //判断用户是否系统管理员
       	 $sql ="select eno from we_enterprise where locate(?,sys_manager)>0 and eno=?";
       	 $para = array((string)$staffid,(string)$eno);
       	 $ds = $da->GetData("table",$sql,$para);
       	 //如为系统管理员则具有所有权限
       	 if (($ds && $ds["table"]["recordcount"]>0) || $state){
       	 	 $sql = "select appid,appname,apptype,case when logo is null or logo='' then null else concat('$FILE_WEBSERVER_URL',logo) end applogo,
	                        concat((select case when max(publishdate) is null then 'V 0.' else 'V 1.' end from we_apps_publish a where a.appid=b.appid),version) version,
	                        (select case when count(*)>0 then 1 else 0 end from mb_approle approle where approle.appid=b.appid) count 
	                 from we_appcenter_apps b where apptype like '99%' and appdeveloper=? order by sortid asc;";
	         $para = array((string)$eno);
       	 }
       	 else {
       	 	 $getsql = $this->getSql($staffid);
	         $sql2 = $getsql["sql"];
	         $para2 = $getsql["para"];
	         $para = array();
	         $sql = "select appid,appname,apptype,case when logo is null or logo='' then null else concat('$FILE_WEBSERVER_URL',logo) end applogo,
	                        concat((select case when max(publishdate) is null then 'V 0.' else 'V 1.' end from we_apps_publish a where a.appid=b.appid),version) version,
	                        (select case when count(*)>0 then 1 else 0 end from mb_approle approle where approle.appid=b.appid) count 
	                 from we_appcenter_apps b where appid in(".$sql2.") and
	                 not exists(select 1 from mb_approle role where b.appid=role.appid and level1=? and leveltype=5) and apptype like '99%' and appdeveloper=? order by sortid asc;";
		       if ( count($para2)>0){
		       	 foreach($para2 as $val){
		       	 	 array_push($para,$val);
		       	 }
		       }
		       array_push($para,(string)$staffid);
		       array_push($para,(string)$eno);
       	 }
	       $ds = $da->GetData('applist',$sql,$para);
	       if ($ds && $ds["applist"]["recordcount"]>0 )
	          $data = $ds["applist"]["rows"];
       }
       catch(\Exception $e){
       	 $this->get("logger")->err($e->getMessage());
       	 $code=ReturnCode::$SYSERROR;
       	 $msg = "查询数据记录失败！";
       }
       $result = array("returncode"=>$code,"msg"=>$msg,"data"=>$data);
       return $result;
    }
	  
	  //获得人员范围sql
		private function getSql($staffid){
			$sql = "";
			$sqls = array();
			$paras = array();
			//所有组织机构可以查看的应用
		  $eno = $this->get('security.context')->getToken()->getUser()->eno;
	    $eno = "v".$eno;		
		  $sql = "select appid from mb_approle where level1=? and leveltype=1 ";
		  array_push($sqls,$sql);
		  array_push($paras,(string)$eno);
			//组织机构
			$sql ="union select appid from mb_approle a inner join mb_hr_7 b on level1=b.orgeh where zhr_pa903112=? and leveltype=1 ";
			array_push($sqls ,$sql);
			array_push($paras,(string)$staffid);	
			//职级维度
			$sql = " union select appid from mb_hr_7 a inner join mb_approle b on zhr_pa903101=level1 
			         where case when level2 is null then 1=1 else zhr_pa903102=level2 end and 
			               case when level3 is null then 1=1 else zhr_pa903113=level3 end and 
			               a.zhr_pa903112=? and leveltype=2 ";
			array_push($sqls ,$sql);
			array_push($paras,(string)$staffid);
			//人员类别
			$sql = " union select appid from mb_hr_7 a inner join mb_approle b on zhr_pa903116=level1 
			         where case when level2 is null then 1=1 else zhr_pa903117=level2 end
			           and case when level3 is null then 1=1 else zhr_pa903118=level3 end 
			           and case when level4 is null then 1=1 else zhr_pa903119=level4 end 
			           and a.zhr_pa903112=? and leveltype=3 ";
			array_push($sqls ,$sql);
			array_push($paras,(string)$staffid);
			//员工号
			$sql = " union select appid from mb_approle where level1=? and leveltype=4 ";
			array_push($sqls ,$sql);
			array_push($paras,(string)$staffid);
			$sql = null;
			if ( count($sqls)>0)
			  $sql = implode(" ",$sqls);		
			$result = array("sql"=> $sql,"para"=> $paras);
			return $result;
		}
		
		//删除多余数据
		public function delete_existsAction()
		{
			 $da = $this->get('we_data_access');
			 $sqls = array();$success = true;
			 $paras = array();$count=0;
			 $sql = "select max(id) id from mb_hr_7 group by sapid_num having count(*)>1 limit 3000;";
			 try
			 {
			   $ds=$da->GetData("table",$sql);
			   $count = $ds["table"]["recordcount"];
			   if ($ds && $ds["table"]["recordcount"]>0){
			 	   for($i=0; $i< $ds["table"]["recordcount"];$i++){
			 	   	 $id = $ds["table"]["rows"][$i]["id"];
			 	   	 $sql = "delete from mb_hr_7 where id='".$id."'";
			 	   	 array_push($sqls,$sql);
			 	   	 if ( count($sqls)>99){
			 	   	 	 $da->ExecSQLS($sqls);
			 	   	 	 $sqls = array();
			 	   	 }
			 	   }
			 	   if ( count($sqls)>0){
			 	   	 $da->ExecSQLS($sqls);
			 	   }
			   }
			 }
			 catch(\Exception $e){
			 	 $this->get("logger")->err($e->getMessage());
			 	 $success = false;
			 }
			 $result = array("success"=>$success,"count"=>$count);
       $response = new Response(json_encode($result));
       $response->headers->set('Content-Type', 'text/json');
       return $response;
		}
}
