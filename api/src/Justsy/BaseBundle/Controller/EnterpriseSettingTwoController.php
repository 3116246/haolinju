<?php
namespace Justsy\BaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\BaseBundle\Meeting\MeetingManager;
use Justsy\BaseBundle\Management\MicroAccountMgr;
use Justsy\BaseBundle\Management\EnoParamManager;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Management\Staff;
use PHPExcel;
use PHPExcel_IOFactory;
use Justsy\BaseBundle\Rbac\StaffRole;
use Justsy\BaseBundle\Rbac\Func;
use Justsy\BaseBundle\Rbac\Role;
use Justsy\InterfaceBundle\SsoAuth\SsoModules;

class EnterpriseSettingTwoController extends Controller
{
	public function employeeMgrAction($network_domain)
	{
		$user = $this->get('security.context')->getToken()->getUser();
        //判断当前导入人员是否是企业邮箱
        $userDomain = explode("@", $user->getUserName());
        $da = $this->get("we_data_access");
    		$da_im = $this->get('we_data_access_im');
        $sql = "select 1 from we_public_domain where domain_name=?";
        $ds = $da->GetData("mt", $sql, array((string) $userDomain[1]));
        $mailType = count($ds["mt"]["rows"]) > 0 ? "0" : "1"; //1表示是企业邮箱
        $staffrole=new StaffRole($da,$da_im,$this->get('logger'));
        $rolecode=$user->eno_level.$user->vip_level;
        $sql ="select DISTINCT c.code,c.name,c.id from we_role a,we_role_function b,we_function c where a.id=b.roleid and b.functionid=c.id and (a.code=? or a.id=?) and c.code is not null and c.name is not null and c.type<>'module'";
	  		$para=array((string)$rolecode,(string)$rolecode);
	  		$ds=$da->Getdata('funcs',$sql,$para);
	  		$functions=$ds['funcs']['rows'];		
        //获取现有人员
        $sql="select login_account,nick_name from we_staff where eno=? and auth_level!='J' and state_id='1'";
        $params=array($user->eno);
        $ds=$da->Getdata("info",$sql,$params);
        $rows=$ds['info']['rows'];
        return $this->render('JustsyBaseBundle:EnterpriseSettingTwo:employee.html.twig', array("userDomain" => $userDomain[1],"functions"=> $functions, "mailType" => $mailType,"account"=> ($user->getUserName()),"staffs"=> json_encode($rows), "curr_network_domain" => $network_domain));
	}
	public function employeeEditAction($network_domain)
	{
		$request=$this->getRequest();
		$user = $this->get('security.context')->getToken()->getUser();
		$da = $this->get("we_data_access");
		$saveValue=$request->get("saveValue");
		//$arr=json_decode(str_replace('"','\"',$saveValue),true);
		$editvalue=$saveValue['editrows'];
		$re=array('s'=>'1','m'=>'');
		$sqls=array();
		$paras=array();
		for($i=0;$i<count($editvalue);$i++)
		{
			//检查名称是否重复
			$sql="select 1 from we_staff where login_account!=? and (nick_name=? or mobile=?)";
			$params=array($editvalue[$i]["login_account"],$editvalue[$i]["nick_name"],$editvalue[$i]["mobile"]);
			$ds=$da->Getdata('have',$sql,$params);
			if($ds['have']['recordcount']>0)continue;
			
			$sqls[]="update we_staff set nick_name=?,mobile=?,duty=?,sex_id=? where login_account=?";
			$paras[]=array($editvalue[$i]["nick_name"],$editvalue[$i]["mobile"],$editvalue[$i]["duty"],$editvalue[$i]["sex_id"],$editvalue[$i]["login_account"]);
		}
		if(!$da->ExecSQLs($sqls,$paras)){
				$re=array('s'=>'0','m'=>'操作失败');
		}
		$response = new Response(json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
	}
	public function addEmployeeAction()
	{
		$request=$this->getRequest();
		$user = $this->get('security.context')->getToken()->getUser();
		//跟新字段
		$sex=$request->get("sex");
		$dept=$request->get("txtdeptid");
		$mobile=$request->get("mobile");
		$account=$request->get("account");
		$duty = $request->get("duty");
		$pass=$request->get("pass");
		$realName = $request->get("realName");
		if(empty($account))
		{
			$response=new Response(json_encode(array('s'=>0,'m'=>'帐号不能为空')));
		  $response->headers->set('Content-Type', 'text/json');
	    return $response;
		}		
		if(empty($pass))
		{
			$response=new Response(json_encode(array('s'=>0,'m'=>'密码不能为空')));
		  $response->headers->set('Content-Type', 'text/json');
	    return $response;
		}		
		$da = $this->get("we_data_access");
		$dm = $this->get("we_data_access_im");
		$sql="select 1 from we_staff where login_account=?";
		$params=array($account);
		$ds=$da->Getdata('staff',$sql,$params);
		if($ds['staff']['recordcount']>0)
		{
			$response=new Response(json_encode(array('s'=>0,'m'=>'用户已存在')));
		  $response->headers->set('Content-Type', 'text/json');
	    return $response;
		}
		$active=new \Justsy\BaseBundle\Controller\ActiveController;
		$active->setContainer($this->container);
		$success = 1;
		$msg = "";
		try
		{
			//自动注册 
			$active->doSave(array(
                            	'account'=> $account,
                            	'realName'=>$realName,
                            	'passWord'=> $pass, 
                            	'eno'=> $user->eno,
                            	'ename'=>$user->ename,
                            	'isNew'=>'0',
                            	'mailtype'=> "1",
                            	'import'=>'1',
                            	'deptid'=>$dept
	                    ));
			$sql="select 1 from we_staff where login_account=?";
			$params=array($account);
			$ds=$da->Getdata('staff',$sql,$params);
			if($ds['staff']['recordcount']>0)
			{
				if(empty($mobile))
				{
					$sql="update we_staff set sex_id=? where login_account=?";
					$params=array($sex,$account);
				}
				else
				{
					$sql="update we_staff set mobile=?,mobile_bind=?,sex_id=? where login_account=?";
					$params=array($mobile,$mobile,$sex,$account);
				}
				try
				{
				$da->ExecSQL($sql,$params);
				$dm->ExecSQL("call dept_emp_stat(?)",array((string)$user->eno));
				//推送消息
				$staffMgr=new \Justsy\BaseBundle\Management\Staff($da,$this->get("we_data_access_im"),$user);
        Utils::sendImPresence($user->fafa_jid,implode(",", $staffMgr->getFriendAndColleagueJid()),"staff-changeinfo","",$this->container,"","",false,Utils::$systemmessage_code);        
			 }
			 catch(\Exception $e){
			 	$log->err($e->getMessage());
			}
			}
			else{
				$success = 0;
				$msg = "添加失败!";
			}
		}
	  catch (\Exception $e){
	  	$this->get("logger")->err($e->getMessage());
	  	$success = 0;	  	
	  	$msg = "添加失败！";
	  }
	  $result = array("s"=>$success,"m"=>$msg);
	  $response=new Response(json_encode($result));
	  $response->headers->set('Content-Type', 'text/json');
		return $response;
	}
	//导入人员
  public function importEmployeeAction($network_domain)
  {
  	
  	$request = $this->get("request");
  	$user = $this->get('security.context')->getToken()->getUser();
  	//判断当前导入人员是否是企业邮箱
  	$userDomain = explode("@",$user->getUserName());
  	$da=$this->get("we_data_access");
    $sql = "select 1 from we_public_domain where domain_name=?";
    $ds = $da->GetData("mt",$sql,array((string)$userDomain[1]));
    $mailType = count($ds["mt"]["rows"])>0 ? "0":"1"; //1表示是企业邮箱
  	try{
		  	$upfile = $request->files->get("filedata");
		  	$tmpPath = $upfile->getPathname();
		    $oldName=$upfile->getClientOriginalName();
		    $fixs = explode(".",strtolower($oldName));
		    if(count($fixs)<2) 
		    {
		    	 $re=array('s'=>0,'message'=>"文件类型不正确");
		    }
		    else
		    {
				    $fixedType = $fixs[count($fixs)-1];
				    if($fixedType!="xlsx" && $fixedType!="xls")
				    {
				    	$re=array('s'=>0,'message'=>"文件类型不正确");
				    }
				    else
				    {
						    $newFileName=$user->openid.date('y-m-d-H-m-s').".".$fixedType;
						    if(move_uploaded_file($tmpPath,'upload/'.$newFileName))
							{									  
								$da=$this->container->get('we_data_access');
								$objReader = \PHPExcel_IOFactory::createReader( $fixedType=="xlsx"? 'Excel2007':"Excel5") ;//use excel2007 for 2007 format
						        $objPHPExcel = $objReader->load($_SERVER['DOCUMENT_ROOT'].'/upload/'.$newFileName); 
								$objWorksheet = $objPHPExcel->getActiveSheet();
						        $highestRow = $objWorksheet->getHighestRow(); 
						        $highestColumn = $objWorksheet->getHighestColumn();
						        $highestColumnIndex = \PHPExcel_Cell::columnIndexFromString($highestColumn);//总列数
						        //获取标题行
						        $titleAry = array();
						        $account_index = 0;
						        $name_index = 0;
						        $mobile_index=0;
						        $duty_index=0;
						        $dept_index=0;
						        $pwd_index=0;
								for ($row = 0;$row <= 1;$row++) 
						        {
						            for ($col = 0;$col < $highestColumnIndex;$col++)
						            {
						                $titleAry[$col] =$objWorksheet->getCellByColumnAndRow($col, $row)->getValue();	
						                if(strpos($titleAry[$col],"邮箱")!==false) $account_index=$col;		
						                else if(strpos($titleAry[$col],"姓名")!==false) $name_index=$col;	 
						                else if(strpos($titleAry[$col],"手机")!==false) $mobile_index=$col;
						                else if(strpos($titleAry[$col],"职务")!==false) $duty_index=$col;
						                else if(strpos($titleAry[$col],"部门")!==false) $dept_index=$col;	
						                else if(strpos($titleAry[$col],"密码")!==false) $pwd_index=$col;                
						            }
						        }
						        $dm=$this->get("we_data_access_im");
		                		$titleAry[]="eno";
		                		$err_list=array();
		                		$active=new \Justsy\BaseBundle\Controller\ActiveController;
								$active->setContainer($this->container);
		                		//获取数据行
				            	for ($row = 2;$row <= $highestRow;$row++) 
						        {
						            $strs=array();
						            for ($col = 0;$col < $highestColumnIndex;$col++)
						            {
						                $strs[$col] =trim((string)$objWorksheet->getCellByColumnAndRow($col, $row)->getValue());
						            }
						            $strs[]=$user->eno;
						            $name = $strs[$name_index];
						            if(empty($name))
						            {
						            	$err_list[]=array("name"=>"","row"=>($row),"msg"=>"姓名不能为空");		
						               	continue;
						            }
						            if(strlen($name)==1)
						            {
						            	$err_list[]=array("name"=>"","row"=>($row),"msg"=>"姓名不能少于2个字符");		
						               	continue;
						            }
						            
						            //获取填写的帐号
						            $account = $strs[$account_index];
						            
						            if(empty($account))
						            {
						            	$err_list[]=array("name"=>$name,"row"=>($row),"msg"=>"邮箱帐号不能为空");		
						               	continue;						            	
						            }  
						            if(!Utils::validateEmail($account))
						            {
						            	$err_list[]=array("name"=>$name,"row"=>($row),"msg"=>"邮箱帐号格式不正确");		
						               	continue;
						            }
						            $staffmgr = new Staff($this->get("we_data_access"),$this->get("we_data_access_im"),$account);
						            if($staffmgr->checkNickname($user->eno,$name)===true)
						            {
						                $err_list[]=array("name"=>"","row"=>($row),"msg"=> "[".$name."]已经注册，请检查！");		
						                continue;
						            }
						            //if($mailType=="1" && explode("@",$account)[1]!=$userDomain[1] )
						            //{
						            //	 $err_list[]=array("name"=>$name,"row"=>($row),"msg"=>"不允许导入公共邮箱$account");
						            //   continue;
						            //}
						            $mobile = $strs[$mobile_index];
						            if(!empty($mobile))
						            {
						            	if(!Utils::validateMobile($mobile))
						            	{
						            	    $err_list[]=array("name"=>$name,"row"=>($row),"msg"=>"手机号码格式不正确");		
						                   	continue;						            	  	
						            	}
						            }
						            
						            //判断帐号是否已经注册
						            $isexist = $staffmgr->isExist($mobile);
						            if(!empty($isexist)){
						            	//已注册
						            	$err_list[]=array("name"=>$name,"msg"=>"邮箱或手机号已被使用");		
						               	continue;
						            }
						            //判断是否已导入，已导入，则不再发邮件
						            $isImport=false;
						            try{
						               	$isImport=$staffmgr->getImportInfo();
						            }
						            catch(\Exception $err)
						            {
						            }
						            try{
							            $staffmgr->importReg($titleAry,$strs);
							            //判断是否设置了密码
							            $pwd = $strs[$pwd_index];
							            if(!empty($pwd))
							            {
							            	$sql = "select ename from we_enterprise where eno=?";
							            	$ds = $da->GetData("t",$sql,array((string)$user->eno));
							            	//自动激活
							            	$active=new \Justsy\BaseBundle\Controller\ActiveController();
				                            $active->setContainer($this->container);
				                            $active->doSave(array(
				                            	'account'=> $account,
				                            	'realName'=>$name,
				                            	'passWord'=> $pwd,
				                            	'eno'=> $user->eno,
				                            	'ename'=> $user->ename,
				                            	'eshortname'=> $user->eshortname,
				                            	'isNew'=>'0',
				                            	'mailtype'=> "1",
				                            	'isSendMessage'=>"N",
				                            	'import'=>'1'
				                            ));
				                            $dm->ExecSQL("call dept_emp_stat(?)",array((string)$user->eno));
				                            $staffmgr = new Staff($da,$dm,$account);
				                            $importData=$staffmgr->getImportInfo();
				                            $staffmgr->updateByImport($importData);
	  	    								$staffmgr->deleteImportPhy();
							            }
							            else
							            {
								            if($isImport===false)
								            {
										        if($active->doSave(array(
										         			'account'=> $account,
										         			'passWord'=> empty($mobile)?$account:$mobile,
										         			'realName'=> $name,
										         			'eno'=> $user->eno,
										         			'ename'=> $user->ename,
										         			'eshortname'=> $user->eshortname,
										         			'isNew'=> '0',
										         			'mailtype'=> "1",
				                            				'isSendMessage'=>"N",
										         			'import'=> '1'
										         		)))
										        {
										      		$staffmgr = new Staff($da,$dm,$account);
										      		//根据导入信息更新注册信息
										      		$importData=$staffmgr->getImportInfo();
													$staffmgr->updateByImport($importData);
													$staffmgr->deleteImportPhy();
										        }
								            }
								          	else{
								          		$err_list[]=array("name"=>$name,"msg"=>"注册失败！");
								          	}
							          	}
						            }
						            catch(\Exception $err)
						            {                          
						            	//写导入数据发生异常
						            	$err_list[]=array("name"=>$name,"msg"=>"导入失败:".$err->getMessage());
						                continue;
						            }
						        }
						        $re=array('s'=>1,'error_list'=>$err_list);
							}
							else
							{
								$re=array('s'=>0,'message'=>"文件上传失败");
							}
							try{
						    	unlink($tmpPath);
							}
							catch(\Exception $e){}
						    
				    }
		    }
    }catch(\Exception $ex)
    {
    	 $re=array('s'=>0,'message'=>"导入失败");
    }
		$response = new Response("<script>parent.import_callback(".json_encode($re).")</script>");
    $response->headers->set('Content-Type', 'text/html');
    return $response;
  }
  //权限控制
  public function privControllAction()
  {
  	$request = $this->get("request");
  	$user = $this->get('security.context')->getToken()->getUser();
  	$da=$this->get("we_data_access");
  	$da_im = $this->get('we_data_access_im');
  	$onlist=$request->get("onlist");
  	$roleid=$request->get("roleid");
  	$role=new Role($da,$da_im);
		$re=array('s'=>"1","m"=>"");
		if(!$role->setRoleFuncs($roleid,explode(',',$onlist))){
			$re=array('s'=>"0","m"=>"操作失败");
		}
  	$response=new Response(json_encode($re));
		$response->headers->set('Content-Type', 'text/json');
	  return $response;
  }
  //获取有权限的用户
  public function getOnUsersOfFunctionAction($network_domain)
  {
  	$request = $this->get("request");
  	$user = $this->get('security.context')->getToken()->getUser();
  	$da=$this->get("we_data_access");
  	$functionid=$request->get("functionid");
  	
  	$sql="select functionid,login_account from we_function_onoff where eno=? and functionid=?";
  	$params=array($user->eno,$functionid);
  	$ds=$da->Getdata('accounts',$sql,$params);
  	$response=new Response(json_encode($ds['accounts']['rows']));
		$response->headers->set('Content-Type', 'text/json');
	  return $response;
  }
  //设置管理模式
  public function saveMstyleAction($network_domain)
  {
  	$request = $this->get("request");
  	$user = $this->get('security.context')->getToken()->getUser();
  	$da=$this->get("we_data_access");
  	$mstyle=$request->get("mstyle");
  	$sql="update we_enterprise set mstyle=? where eno=?";
  	$params=array($mstyle,$user->eno);
  	if($da->ExecSQL($sql,$params))
  	{
  		$user->mstyle=$mstyle;
  	}
  	$response=new Response(json_encode(array('s'=>1,'m'=>'')));
		$response->headers->set('Content-Type', 'text/json');
	  return $response;
  }
  //添加自定义角色
  public function addRoleAction($network_domain)
  {
  	$request = $this->get("request");
  	$user = $this->get('security.context')->getToken()->getUser();
  	$da=$this->get("we_data_access");
  	$da_im = $this->get('we_data_access_im');
  	$role=new Role($da,$da_im);
  	$rolename=$request->get("rolename");
  	$rolecode=$request->get("rolecode");
  	$re=array('s'=>"1","m"=>"","role"=>array());
  	
  	//检查角色名是否重复
  	$sql="select 1 from we_role where name=? and (eno=? or eno is null or eno='')";
  	$params=array($rolename,$user->eno);
  	$ds=$da->Getdata('num',$sql,$params);
  	if($ds['num']['recordcount']>0){
  		$re=array('s'=>'0','m'=>'角色名称重复','role'=>array());
  	}
  	else{
  		if(!$role->saveEnRole($user->eno,$rolecode,$rolename)){
  			$re=array('s'=>'0','m'=>'角色添加失败','role'=>array());
  		}
  	}
  	$response=new Response(json_encode($re));
		$response->headers->set('Content-Type', 'text/json');
	  return $response;
  }
  //编辑
  public function editRoleAction($network_domain)
  {
  	$request = $this->get("request");
  	$user = $this->get('security.context')->getToken()->getUser();
  	$da=$this->get("we_data_access");
  	$da_im = $this->get('we_data_access_im');
  	$role=new Role($da,$da_im);
  	$roleid=$request->get("roleid");
  	$rolename=$request->get("rolename");
  	$rolecode=$request->get("rolecode");
  	$re=array('s'=>'1','m'=>'','role'=>array());
  	//检查角色名是否重复
  	$sql="select 1 from we_role where name=? and (eno is null or eno='' or (eno=? and id!=?))";
  	$params=array($rolename,$user->eno,$roleid);
  	
  	$ds=$da->Getdata('num',$sql,$params);
  	if($ds['num']['recordcount']>0){
  		$re=array('s'=>'0','m'=>'角色名称重复','role'=>array());
  	}
  	else{
  		if(!$role->editEnRole($roleid,$rolecode,$rolename)){
  			$re=array('s'=>'0','m'=>'角色编辑失败','role'=>array());
  		}
  	}
  	
  	$response=new Response(json_encode($re));
		$response->headers->set('Content-Type', 'text/json');
	  return $response;
  }
  //删除
  public function delRoleAction($network_domain)
  {
  	$request = $this->get("request");
  	$user = $this->get('security.context')->getToken()->getUser();
  	$da=$this->get("we_data_access");
  	$da_im = $this->get('we_data_access_im');
  	$role=new Role($da,$da_im);
  	$re=array('s'=>'1','m'=>'','role'=>array());
  	$roleid=$request->get("roleid");
  	if(!$role->deleteRole($roleid)){
  		$re=array('s'=>'0','m'=>'角色编辑失败','role'=>array());
  	}
  	$response=new Response(json_encode($re));
		$response->headers->set('Content-Type', 'text/json');
	  return $response;
  }
  //查询
  public function getRolesAction($network_domain)
  {
  	$request = $this->get("request");
  	$user = $this->get('security.context')->getToken()->getUser();
  	$da=$this->get("we_data_access");
  	$da_im = $this->get('we_data_access_im');
  	$eno=$user->eno;
  	
  	$role=new Role($da,$da_im);
  	$rows=$role->GetRoleDataByEno($eno);
  	
  	$response=new Response(json_encode($rows));
		$response->headers->set('Content-Type', 'text/json');
	  return $response;
  }
  //查询所有自定义角色授权
  public function getUserRoles($eno)
  {
  	$request = $this->get("request");
  	$user = $this->get('security.context')->getToken()->getUser();
  	$da=$this->get("we_data_access");
  	$da_im = $this->get('we_data_access_im');
  	
  	$role=new Role($da,$da_im);
  	return $role->GetUserRoles($eno);
  }
  public function setUserRoleAction($network_domain)
  {
  	$request = $this->get("request");
  	$user = $this->get('security.context')->getToken()->getUser();
  	$da=$this->get("we_data_access");
  	$da_im = $this->get('we_data_access_im');
  	$re=array('s'=>'1','m'=>'');
  	
  	$roleid=$request->get("roleid");
  	$staffs=$request->get("staffs");
  	$StaffRole=new StaffRole($da,$da_im);
  	if(!$StaffRole->setStaffRole($roleid,empty($staffs)? array():explode(',',$staffs),$user->eno)){
  		$re=array('s'=>'0','m'=>'角色授权失败');
  	}
  	else{
  	}
  	$response=new Response(json_encode($re));
		$response->headers->set('Content-Type', 'text/json');
	  return $response;
  }
  public function setRoleStaffAction($network_domain)
  {
  	$request = $this->get("request");
  	$user = $this->get('security.context')->getToken()->getUser();
  	$da=$this->get("we_data_access");
  	$da_im = $this->get('we_data_access_im');
  	$re=array('s'=>'1','m'=>'');
  	
  	$login_account=$request->get("login_account");
  	$roles=$request->get("roles");
  	$StaffRole=new StaffRole($da,$da_im);
  	if(!$StaffRole->setRoleStaff($login_account,empty($roles)? array():explode(',',$roles),$user->eno)){
  		$re=array('s'=>'0','m'=>'角色授权失败');
  	}
  	$response=new Response(json_encode($re));
		$response->headers->set('Content-Type', 'text/json');
	  return $response;
  }
  public function getPrivByRoleAction($network_domain)
  {
  	$request = $this->get("request");
  	$user = $this->get('security.context')->getToken()->getUser();
  	$da=$this->get("we_data_access");
  	$da_im = $this->get('we_data_access_im');
  	$re=array('s'=>'1','m'=>'');
  	
  	$roleid=$request->get("roleid");
  	$role=new Role($da,$da_im);
  	$rows=$role->getPrivByRole($roleid);
  	$response=new Response(json_encode($rows));
		$response->headers->set('Content-Type', 'text/json');
	  return $response;
  }
  public function getRoleByStaffAction($network_domain)
  {
  	$request = $this->get("request");
  	$user = $this->get('security.context')->getToken()->getUser();
  	$da=$this->get("we_data_access");
  	$da_im = $this->get('we_data_access_im');
  	$re=array('s'=>'1','m'=>'');
  	
  	$login_account=$request->get("login_account");
  	$role=new Role($da,$da_im);
  	$rows=$role->getRoleByStaff($login_account);
  	$response=new Response(json_encode($rows));
		$response->headers->set('Content-Type', 'text/json');
	  return $response;
  }
  public function getStaffByRoleAction($network_domain){
  	$request = $this->get("request");
  	$user = $this->get('security.context')->getToken()->getUser();
  	$da=$this->get("we_data_access");
  	$da_im = $this->get('we_data_access_im');
  	$re=array('s'=>'1','m'=>'');
  	
  	$roleid=$request->get("roleid");
  	$role=new Role($da,$da_im);
  	$rows=$role->getStaffByRole($roleid);
  	$response=new Response(json_encode($rows));
		$response->headers->set('Content-Type', 'text/json');
	  return $response;
  }
  public function getStaffsAction($network_domain)
  {
  	$request = $this->get("request");
  	$user = $this->get('security.context')->getToken()->getUser();
  	$da=$this->get("we_data_access");
  	$da_im = $this->get('we_data_access_im');
  	$re=array('s'=>'1','m'=>'');
  	
  	$da->PageSize=15;
		$da->PageIndex=(int)($request->get('pageindex'))-1;
		$searchText=$request->get("searchtext");
		
		$sql="select login_account,nick_name from we_staff where eno=? and auth_level!='J' and state_id='1'";
		$params=array($user->eno);
		if($searchText!=""){
			if((strlen($searchText)>mb_strlen($searchText,'utf8'))){
				$sql.=" and nick_name like ?";
				array_push($params,"%".$searchText."%");
			}
			else{
				$sql.=" and (nick_name like ? or login_account like ?)";
				array_push($params,"%".$searchText."%");
				array_push($params,"%".$searchText."%");
			}
		}
		
		$ds=$da->Getdata('info',$sql,$params);
		$rows=$ds['info']['rows'];
		$response=new Response(json_encode(array("count"=>$ds['info']['recordcount'],"rows"=> $rows)));
		$response->headers->set('Content-Type', 'text/json');
	  return $response;
  }
  public function bindIndexAction($network_domain)
  {
  	try{
  		$request = $this->get("request");
	  	$user = $this->get('security.context')->getToken()->getUser();
	  	$da=$this->get("we_data_access");
	  	$eno=$user->eno;
			$nick_name=$user->nick_name;
	  	$account = $user->getUsername();
		   $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
		   $sql = "select appid,appname,apptype,case when logo is null or logo='' then null else concat('$FILE_WEBSERVER_URL',logo) end applogo,
		                  ifnull(version,'') version,createstaffid createstaff,publishdate `date`,publishstaff staff,
		                  case when createstaffid=? then 1 else 0 end isowner,f_app_role(?,b.appid) role 
		           from we_appcenter_apps b where apptype like '99%' and appdeveloper=? order by sortid asc;";
		   $para = array((string)$nick_name,(string)$account,(string)$eno);
		   $ds = $da->GetData('applist',$sql,$para);
		   $list = array();
		   if ($ds && $ds["applist"]["recordcount"]>0 )
		     $list = $ds["applist"]["rows"];
	  	//获取所有类型
	  	$modules=SsoModules::$modules;
			return $this->render('JustsyBaseBundle:EnterpriseSettingTwo:bind.html.twig', array('modules'=> $modules,'curr_network_domain'=> $network_domain,'appids'=> $list,'','eno'=>$user->eno));
  	}
  	catch(\Exception $e){
  	}
  }
  public function getBindListAction()
  {
  	$request = $this->get("request");
  	$user = $this->get('security.context')->getToken()->getUser();
  	$da=$this->get("we_data_access");
  	try{
  		$da->PageSize=20;
			$da->PageIndex=(int)($request->get('pageindex'))-1;
	  	$bind_type=$request->get('bind_type');
	  	$appid=$request->get('appid');
	  	$searchtext=$request->get('searchtext');
	  	$sql="select a.login_account,a.openid,a.nick_name,ifnull(b.bind_uid,'') as bind_uid,case when b.bind_account is null or b.bind_account='' then '否' else '是' end isbind from we_staff a left join we_staff_account_bind b on b.bind_account=a.openid and b.bind_type=? and b.appid=? where a.eno=?";
	  	$params=array($bind_type,$appid,$user->eno);
	  	if(!empty($searchtext)){
	  		if(mb_strlen($searchtext,'UTF8')==strlen($searchtext)){
	  			$sql.=" and (a.nick_name like ? or a.login_account like ?)";
	  			array_push($params,'%'.$searchtext.'%');
	  			array_push($params,'%'.$searchtext.'%');
	  		}
	  		else{
	  			$sql.=" and a.nick_name like ?";
	  			array_push($params,'%'.$searchtext.'%');
	  		}
	  	}
	  	$ds=$da->GetData('info',$sql,$params);
	  	$rows=$ds['info']['rows'];
	  	$count=$ds['info']['recordcount'];
  	}
  	catch(\Exception $e){
  		var_dump($e->getMessage());
  		$this->get("logger")->err($e->getMessage());
  	}
  	
  	$response=new Response(json_encode(array('rows'=> $rows,'count'=>$count)));
		$response->headers->set('Content-Type', 'text/json');
	  return $response;
  }
}
?>