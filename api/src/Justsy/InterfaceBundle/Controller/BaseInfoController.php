<?php

namespace Justsy\InterfaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Controller\EmployeeCardController;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Float;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Controller\AccountController;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Management\Enterprise;
class BaseInfoController extends Controller
{  
  public function getcirclesAction() 
  {
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $da = $this->get('we_data_access');
    $currUser = $this->get('security.context')->getToken();
    if(!empty($currUser)){
       $user = $currUser->getUser();
       $eshortname = $user->eshortname;//当前登录人企业 号
       $userAccount = $user->getUserName();
    }
    else
    {
    	  //当应用通过api接口调用时，不用登录，只能通过openid获取人员信息
    	  $ds = $this->getstaffinfo($request->get("Openid").$request->get("openid"));
    	  $eshortname =  $ds["eshortname"];
    	  $userAccount = $ds["login_account"];
    }

    //获取朋友圈子数量
    $staffMgr = new \Justsy\BaseBundle\Management\Staff($da,$this->get('we_data_access_im'),$userAccount,$this->container->get("logger"),$this->container);
    $friendList = $staffMgr->getFriendCount();
    $friendListSize = empty($friendList) ? 0 : $friendList;
    $sql = "select a.circle_id, a.circle_name, a.circle_desc, a.logo_path, a.create_staff, a.create_date, a.manager, 
    a.join_method, a.enterprise_no, a.network_domain, a.allow_copy, a.logo_path_small, a.logo_path_big, a.fafa_groupid,";
    $sql.=" ? staff_num ,'' classify_name, '' circle_class_id  from we_circle a where a.circle_id='9999' 
      union select a.circle_id, a.circle_name, a.circle_desc, a.logo_path, a.create_staff, a.create_date, a.manager, 
      a.join_method, a.enterprise_no, a.network_domain, a.allow_copy, a.logo_path_small, a.logo_path_big, a.fafa_groupid,(select count(1) from we_circle_staff c where c.circle_id=a.circle_id) as staff_num ,c.classify_name,a.circle_class_id
      from we_circle a left join we_circle_staff b on a.circle_id=b.circle_id
      left join we_circle_class c on a.circle_class_id=c.classify_id
      where  b.login_account=? and a.circle_id!='9999'";
    $params = array();
    $params[] = (int)$friendListSize;
    $params[] = (string)$userAccount;
    
    
    $ds = $da->GetData("we_circle", $sql, $params);
    $re["circles"] = $ds["we_circle"]["rows"];


    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  
  public function getgroupsAction() 
  {
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $currUser = $this->get('security.context')->getToken();
    if(!empty($currUser)){
       $user = $currUser->getUser();
       $eshortname = $user->eshortname;//当前登录人企业 号
       $userAccount = $user->getUserName();
    }
    else
    {
    	  //当应用通过api接口调用时，不用登录，只能通过openid获取人员信息
    	  $ds = $this->getstaffinfo($request->get("Openid").$request->get("openid"));
    	  $eshortname =  $ds["eshortname"];
    	  $userAccount = $ds["login_account"];
    }    
    $circle_id = $request->get("circle_id");
    if(empty($circle_id))
    {
    	  //没传圈子编号时，获取当前人员所有的群组列表
		    $sql = "select a.circle_id, a.group_id, a.group_name, a.group_desc, a.group_photo_path, a.join_method,
		a.create_staff, a.create_date, a.fafa_groupid,a.group_class
		from we_groups a left join we_group_staff b
		  on a.group_id=b.group_id
		  where b.login_account=? order by a.create_date
		";
		    $params = array();
		    $params[] = (string)$userAccount;    	
    }
    else{
		    $sql = "select a.circle_id, a.group_id, a.group_name, a.group_desc, a.group_photo_path, a.join_method,
		a.create_staff, a.create_date, a.fafa_groupid,a.group_class
		from we_groups a left join we_group_staff b
		  on a.group_id=b.group_id
		  where a.circle_id=?
		  and b.login_account=?  order by a.create_date
		";
		    $params = array();
		    $params[] = (string)$circle_id;
		    $params[] = (string)$userAccount;
    }
    $da = $this->get('we_data_access');
    $ds = $da->GetData("we_groups", $sql, $params);
    $re["groups"] = $ds["we_groups"]["rows"];
    
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
    
  public function getstaffpresenceAction()
  {   	
        	$request = $this->getRequest();
        	$da = $this->get("we_data_access");
        	$da_im = $this->get("we_data_access_im");
			    $uEno="";
			    $userJid="";
			    $currUser = $this->get('security.context')->getToken();
			    if(!empty($currUser)){
			       $user = $currUser->getUser();
			       $uEno = $user->eno;//当前登录人企业 号
			       $userJid = $user->fafa_jid;
			    }
			    else
			    {
			    	  //当应用通过api接口调用时，不用登录，只能通过openid获取人员信息
			    	  $ds = $this->getstaffinfo($request->get("Openid").$request->get("openid"));
			    	  $uEno =  $ds["eno"];
			    	  $userJid = $ds["fafa_jid"];
			    }
        	//获取当前企业号
        	$acc = $request->get("staff");
        	if(!empty($acc))
        	{
		        	$acc = "'".trim(str_replace(",", "','", $acc))."'";
		        	$sql = "select a.fafa_jid from we_staff a where a.login_account in($acc) and a.eno=?";
		        	$para = array();
		        	$para[] = (string)$uEno;
		        	$ds=$da->GetData('we_staff',$sql,$para);
		
		        	$rows = $ds["we_staff"]["rows"];
		        	if(!$ds || count($rows)==0)
		        	{
					      $response = new Response(json_encode(array('s'=>0,'msg'=> $acc.' not found!')));
							  return $response;        		
		        	}
		        	$jid="";
		        	for($i=0;$i<count($rows);$i++)
		        	{
		        		 if($jid!="") $jid = $jid.",";
		        	   $jid = $jid . "'".$rows[$i]["fafa_jid"]."'";        	   
		        	}
		        	$sql = "select a.employeeid,a.loginname account,a.employeename name,ifnull(b.res,'') resource,case when (b.res is null) then '0' else '1' end state from im_employee a left join global_session b on a.loginname=b.us where a.loginname in ($jid)";
          }
          else
          		$sql = "select a.employeeid,a.loginname account,a.employeename name,ifnull(b.res,'') resource,case when (b.res is null) then '0' else '1' end state from im_employee a left join global_session b on a.loginname=b.us where a.deptid = 'v".$user->eno."999'";
        	$para = array();
        	$ds=$da_im->GetData('staff',$sql,$para);
        	$staffs=array();
        	foreach($ds['staff']['rows'] as $row)
        	{
        		$sql="select login_account from we_staff where fafa_jid=?";
        		$params=array($row['account']);
        		$ds2=$da->Getdata('account',$sql,$params);
        		if($ds2['account']['recordcount']>0){
        			$row['login_account']=$ds2['account']['rows'][0]['login_account'];
        			//$staffs[]=$row;
        		}
        	}
        	$re = array("returncode" => ReturnCode::$SUCCESS);
        	$re["rows"] = $ds["staff"]["rows"];
			    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
			    $response->headers->set('Content-Type', 'text/json');
			    return $response;
  }  
  //获取指定帐号的人员信息
  public function getstaffcardAction() 
  {
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $staff = $request->get("staff");
    if (empty($staff)){
    	 $re["returncode"] = ReturnCode::$SYSERROR;
	     $re["msg"] = "请传入正确的参数！";
    }
    else{
	    $ds = $this->getstaffinfo($staff);
	    if ( $ds!=null){
	    	$re["staff_full"] = $ds;
	    	$re["staff_full"]["pinyin"] =Utils::Pinyin($re["staff_full"]["nick_name"]);
	    }
	    else{
	    	 $re["returncode"] = ReturnCode::$SYSERROR;
	    	 $re["msg"] = "未查询到用户数据！";
	    }
    }
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }

  //获取指定人员信息，只能获取本企业内的和好友信息
  //对第三方提供。由于不需要登录就可调用该接口，所以只能通过获取参数Openid取得当前人员信息
  public function getfriendcardAction() 
  {
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $uEno="";
    $userJid="";
    $currUser = $this->get('security.context')->getToken();
    if(!empty($currUser)){
       $user = $currUser->getUser();
       $uEno = $user->eno;//当前登录人企业 号
       $userJid = $user->fafa_jid;
    }
    else
    {
    	$ds = $this->getstaffinfo($request->get("Openid").$request->get("openid"));
    	if(empty($ds))
    	{
    	  	$re = Utils::WrapResultError("当前操作人帐号无效");
    		$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    		$response->headers->set('Content-Type', 'text/json');
    		return $response;    	  	
    	}
    	$uEno =  $ds["eno"];
    	$userJid = $ds["jid"];
    }
    $staff = $request->get("staff");        
    $ds = $this->getstaffinfo($staff);
    if(empty($ds))
    {
    	$re = Utils::WrapResultError("未查询到用户数据！");
    }
    else
    {
    	$_source=$request->get("_source");//wefafa_bizproxy:业务代理
    	if($ds["eno"]!=$uEno)
    	{
    	  		$da_im = $this->get('we_data_access_im');
	   	    	//判断该人员是否自己好友
	    	  	$dfriend = $da_im->GetData("f","select count(1) from rosterusers where username=? and jid=? and subscription='B'",
	    	                               array((string)$userJid,(string)$ds["we_staff"]["rows"][0]["jid"]));
	    	  	if($dfriend==null || count($dfriend["f"]["rows"])==0)
	    	    	$re=Utils::WrapResultError("没有权限查询该帐号的信息",ReturnCode::$NOTACCESS);
    	  	  	else
    	  	  	{
	    	  		if("wefafa_bizproxy"!=$_source)
	    	  		{
	    	  			$re["staff_full"] = $ds;
	    	  			$re["staff_full"]["pinyin"] =Utils::Pinyin($re["staff_full"]["nick_name"]);
	    	  		}
	    	  		else
	    	  		{
	    	  			$re=$ds;
	    	  			$re["pinyin"] =Utils::Pinyin($re["nick_name"]);
	           	   		$re["returncode"]=ReturnCode::$SUCCESS;
	    	  		}
    	  		}
    	}
    	else
    	{    	  	 
           	if("wefafa_bizproxy"!=$_source)
           	{
           		$re["staff_full"] = $ds;
           		$re["staff_full"]["pinyin"] =Utils::Pinyin($re["staff_full"]["nick_name"]);
   		    }
           	else{
           	  $re=$ds;     
           	  $re["pinyin"] =Utils::Pinyin($re["nick_name"]);      	  
           	  $re["returncode"]=ReturnCode::$SUCCESS;
           	}
        }
    }
    
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  
  public function getstaffinfo($staff)
  {
    	$staffinfo = new \Justsy\BaseBundle\Management\Staff($this->get('we_data_access'),$this->get("we_data_access_im"),$staff,$this->get("logger"),$this->container);
		return $staffinfo->getInfo();
  }
  
  //对第三方提供。由于不需要登录就可调用该接口，所以只能通过获取参数Openid取得当前人员信息
  public function getselfcardAction()
  {
  	$re = array("returncode" => ReturnCode::$SUCCESS);
  	$request = $this->getRequest();
    $uEno="";
    $userJid="";
    $currUser = $this->get('security.context')->getToken();
    if(!empty($currUser)){
       $user = $currUser->getUser();  	
  	   $re["staff_full"]=array(
  			"login_account"=> $user->getUserName(),
  			"jid"=> $user->fafa_jid,
  			"nick_name"=> $user->nick_name,
  			"photo_path"=> $user->photo_path,
  			"photo_path_small"=> $user->photo_path_small,
  			"photo_path_big"=> $user->photo_path_big,
  			"dept_id"=> $user->dept_id,
  			"dept_name"=> $user->dept_name,
  			"identify" => $user->identify,
  			"eno"=> $user->eno,
  			"ename"=> $user->ename,
  			"eshortname"=> $user->eshortname,
  			"self_desc"=> $user->self_desc,
  			"duty"=> $user->duty,
  			"birthday"=> $user->birthday,
  			"specialty"=> $user->specialty,
  			"sex_id"=> $user->sex_id,
  			"hometown"=> $user->hometown,
  			"we_level"=> $user->we_level,
  			"work_his"=> $user->work_his,
  			"graduated"=> $user->graduated,
  			"hobby"=> $user->hobby,
  			"work_phone"=> $user->work_phone,
  			"mobile"=> $user->mobile,
  			"mobile_is_bind"=> (empty($user->mobile_bind)?'0':'1'),
  			"total_point"=> $user->total_point,
  			"register_date"=> $user->register_date,
  			"active_date"=> $user->active_date,
  			"attenstaff_num"=> $user->attenstaff_num,
  			"fans_num"=> $user->fans_num,
  			"publish_num"=> $user->publish_num,
  			"ldap_uid"=> $user->ldap_uid
  	    );  	
    }
    else
    {
    	  $ds = $this->getstaffinfo($request->get("Openid").$request->get("openid"));
    	  $user =  $ds;
  	    $re["staff_full"]=array(
  			"login_account"=> $user["login_account"],
  			"jid"=> $user["jid"],
  			"nick_name"=> $user["nick_name"],
  			"photo_path"=> $user["photo_path"],
  			"photo_path_small"=> $user["photo_path_small"],
  			"photo_path_big"=> $user["photo_path_big"],
  			"dept_id"=> $user["dept_id"],
  			"dept_name"=> $user["dept_name"],
  			"identify" => $user["identify"],
  			"eno"=> $user["eno"],
  			"ename"=> $user["ename"],
  			"eshortname"=> $user["eshortname"],
  			"self_desc"=> $user["self_desc"],
  			"duty"=> $user["duty"],
  			"birthday"=> $user["birthday"],
  			"specialty"=> $user["specialty"],
  			"sex_id"=> $user["sex_id"],
  			"hometown"=> $user["hometown"],
  			"we_level"=> $user["we_level"],
  			"work_his"=> $user["work_his"],
  			"graduated"=> $user["graduated"],
  			"hobby"=> $user["hobby"],
  			"work_phone"=> $user["work_phone"],
  			"mobile"=> $user["mobile"],
  			"mobile_is_bind"=> (empty($user["mobile_bind"])?'0':'1'),
  			"total_point"=> $user["total_point"],
  			"register_date"=> $user["register_date"],
  			"active_date"=> $user["active_date"],
  			"attenstaff_num"=> $user["attenstaff_num"],
  			"fans_num"=> $user["fans_num"],
  			"publish_num"=> $user["publish_num"],
  			"ldap_uid"=> $user["ldap_uid"]
  	    );
    }
  	
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;  	
  }
  
  //创建圈子
  //对第三方提供。由于不需要登录就可调用该接口，所以只能通过获取参数Openid取得当前人员信息
  public function createCircleAction()
  {
        $re = array("returncode" => ReturnCode::$SUCCESS);
        $request = $this->getRequest();
        $user = $this->get('security.context')->getToken()->getUser();
	      $circleName = trim($request->get("name"));	
	      if(empty($circleName))
	      {
					  $re["s"]=0;
					  $re["msg"]="圈子名称不能为空";
				    $response=new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
				    $response->headers->set('Content-Type', 'text/json');
				    return $response;	      	
	      }
	      $logo = trim($request->get("logo"));
	      $desc = trim($request->get("desc"));
	      
				$da = $this->get('we_data_access');
				$da_im = $this->get('we_data_access_im');	      
	      //判断圈子是否已存在		
				$ds = $da->GetData("app", "select * from we_circle where circle_name=? ", array((string)$circleName));
				if($ds ==null ||count($ds["app"]["rows"])>0 )
				{
					  $re["s"]=0;
					  $re["msg"]="圈子已存在";
				    $response=new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
				    $response->headers->set('Content-Type', 'text/json');
				    return $response;
				}

	      $circle_id = (String)\Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da,"we_circle","circle_id");
	      $fafa_groupid = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da_im,"im_group","groupid");
	      $network =$circle_id;
	      $sqls = array
	      (
	        "insert into we_circle(circle_id,circle_name,circle_desc,logo_path,logo_path_big,logo_path_small,create_staff,create_date,manager,join_method,network_domain,allow_copy,circle_class_id,fafa_groupid)value(?,?,?,?,?,?,?,now(),?,?,?,?,?,?)",
	        "insert into we_circle_staff(circle_id,login_account,nick_name)values(?,?,?)"
	      );
	
	      $paras = array
	      (
	        array((String)$circle_id,(String)$circleName,(String)$desc,
	               (String)$logo,(string)$logo,(string)$logo,(String)$user->getUsername(),(String)$user->getUsername(),
	               "0",$network,"0","004014",(string)$fafa_groupid),
	        array((String)$circle_id,(String)$user->getUsername(),(String)$user->nick_name)
	      );
	      $da->ExecSQLs($sqls,$paras);
	      //创建文档根目录
	      $docCtl = new \Justsy\BaseBundle\Controller\DocumentMgrController();
	      $docCtl->setContainer($this->container);
	      $dirid=$docCtl->createDir("c".$circle_id,"",$circleName,$circle_id);
	      if($dirid>0)
	      {
	        	  $docCtl->saveShare("c".$circle_id,"0",$circle_id,"c","w");//将圈子目录共享给该圈子成员
	      }
	      $re["circleid"] = $circle_id;
	      //$response = new Response(json_encode($re));
	      $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
	      $response->headers->set('Content-Type', 'text/json');
	      return $response;      
  }
    
  
  //��ע��Ա
  public function attenstaffAction()
  {
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $staff = $request->get("staff");
    $da = $this->get('we_data_access');
    $sql_t = "select login_account from we_staff where ";
    $sql = $sql_t."login_account=? union ".$sql_t."fafa_jid=?";
    $ds = $da->GetData("we_staff", $sql, array((string)$staff,(string)$staff));	    
    if ($ds && $ds['we_staff']['recordcount']>0)
    {
      $login_account = $ds['we_staff']['rows'][0]['login_account'];
    	try
    	{
    	  $ec = new EmployeeCardController();
    	  $ec->setContainer($this->container);
    	  $resp = $ec->attentionAction($login_account);
    	  $jo = json_decode($resp->getContent());
    	  if ($jo && $jo->{'succeed'}==1)
    	  	$re['returncode'] = ReturnCode::$SUCCESS; 	
    	  else $re['returncode'] = ReturnCode::$SYSERROR;
      }
      catch(\Exception $e)
      {
        $this->get('logger')->err($e->getMessage());
        $re['returncode'] = ReturnCode::$SYSERROR;
      }
    }
    else
    {
      $re['returncode'] = ReturnCode::$ERROFUSERORPWD;
    }
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  //ȡ���ע
  public function cancelattenstaffAction()
  {
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $staff = $request->get("staff");
    $da = $this->get('we_data_access');
    $sql_t = "select login_account from we_staff where ";
    $sql = $sql_t."login_account=? union ".$sql_t."fafa_jid=?";
    $ds = $da->GetData("we_staff", $sql, array((string)$staff,(string)$staff));
    if ($ds && $ds['we_staff']['recordcount']>0)
    {
      $login_account = $ds['we_staff']['rows'][0]['login_account'];
    	try
    	{
    	  $ec = new EmployeeCardController();
    	  $ec->setContainer($this->container);
    	  $resp = $ec->cancelAttentionAction($login_account);
    	  $jo = json_decode($resp->getContent());
    	  if ($jo && $jo->{'succeed'}==1) $re['returncode'] = ReturnCode::$SUCCESS;
    	  else $re['returncode'] = ReturnCode::$SYSERROR;
      }
      catch(\Exception $e)
      {
        $this->get('logger')->err($e);
        $re['returncode'] = ReturnCode::$SYSERROR;
      }
    }
    else
    {
      $re['returncode'] = ReturnCode::$ERROFUSERORPWD;
    }
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  //获取关注人员列表
  public function attenstafflistAction()
  {
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $staff = $request->get("staff");
    $da = $this->get('we_data_access');
    $isdesigner = $request->get("isdesigner");
    $sql = "select a.login_account,a.fafa_jid,ifnull(sex_id,'') sex,a.openid,a.nick_name,a.photo_path,a.photo_path_small,a.photo_path_big,a.duty
            from we_staff a inner join we_staff_atten b on a.login_account=b.atten_id
            where b.login_account=(select login_account from we_staff where login_account=? or openid=? or fafa_jid=?) and not exists (select 1 from we_micro_account where number=atten_id)";
    if(!empty($isdesigner))
        $sql.= " and duty='造型师'";   
    $ds = $da->GetData("we_staff", $sql, array((string)$staff,(string)$staff,(string)$staff));
    $re["staffs"] = $ds["we_staff"]["rows"];
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  //获取粉丝列表
  public function fansstafflistAction()
  {
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $da = $this->get('we_data_access');
    $staff = $request->get("staff");
    $isdesigner = $request->get("isdesigner");
    //始终将其转换为login_account
    $sql = "select login_account from we_staff where fafa_jid=? or openid=?";
    $ds = $da->GetData("table",$sql,array((string)$staff,(string)$staff));
    if ( $ds && $ds["table"]["recordcount"]>0)
      $staff = $ds["table"]["rows"][0]["login_account"];
    
    $sql = "select a.login_account,a.fafa_jid,ifnull(sex_id,'') sex,a.openid,a.nick_name,a.photo_path,a.photo_path_small,a.photo_path_big,eshortname
            from we_staff a inner join we_enterprise c on a.eno=c.eno inner join we_staff_atten b on a.login_account=b.login_account
            where not exists(select 1 from we_micro_account where we_micro_account.number=a.login_account) and b.atten_id=?";
    if(!empty($isdesigner))
       $sql.= " and a.duty='造型师'";
    $ds = $da->GetData("we_staff", $sql, array((string)$staff));
    $re["staffs"] = $ds["we_staff"]["rows"];    
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  
  //人员列表关注数量和粉丝数量
  public function stafflisttotalnumAction()
  {
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $staff = $request->get("staff");
    $da = $this->get('we_data_access');
    $staff = explode(",",$staff);
    $sql="";
    $para = array();
    if ( count($staff)==1){
      $sql = "select login_account,nick_name,attenstaff_num,fans_num from we_staff where login_account=? or openid=? or fafa_jid=?";
      $para = array((string)$staff[0],(string)$staff[0],(string)$staff[0]); 
    }
    else{
    	 $parameter = "";
    	 $paras = array();
    	 foreach($staff as $jid){
    	 	 $parameter.="?,";
    	 	 array_push($paras,(string)$jid);
    	 }
    	 $parameter = rtrim($parameter,",");
    	 $sql = "select login_account,nick_name,attenstaff_num,fans_num from we_staff
    	         where login_account in( $parameter ) or openid in( $parameter ) or fafa_jid in( $parameter )";
    	 for($i=0;$i<3;$i++){
    	 	 foreach($paras as $jid){
    	 	 	 array_push($para,$jid);
    	 	 }
    	 }
    }
    try
    {
       $ds = $da->getData("table",$sql,$para);
       $re["staffs"] = $ds["table"]["rows"];
    }
    catch(\Exception $e) {
    	$re["staff"] = array();
    	$re['returncode'] = ReturnCode::$SYSERROR;
    }
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }  
  
  //获取关注人员版本
  public function attenstafflistversionAction()
  {
      $re = array("returncode" => ReturnCode::$SUCCESS);
      $request = $this->getRequest();
      $staff = $request->get("staff");      
      /*
      $da = $this->get('we_data_access');
      $sql_t = "select a.login_account,a.nick_name,a.photo_path,a.photo_path_small,a.photo_path_big from we_staff a ";
      $sql = "select * from (".$sql_t."inner join we_staff_atten b on a.login_account=b.atten_id and b.login_account=? union ".$sql_t;
      $sql .= "inner join we_staff_atten b on a.login_account=b.atten_id inner join we_staff c on b.login_account=c.login_account and c.fafa_jid=?";
      $sql .=" ) b where not exists(select 1 from we_micro_account  where we_micro_account.number=b.login_account)";
      $ds = $da->GetData("we_staff", $sql, array((string)$staff,(string)$staff));
      if ($ds && $ds["we_staff"]["recordcount"] == 0){
          $re["version"] ="";
      }
      else{
          $s = "";
          foreach($ds["we_staff"]["rows"] as $key => $value)
          {
            $s .= $value['login_account'];
          }
          $re["version"] = md5($s);
      }
      */
      $re["version"] = $this->getVersionChange(1,$staff);
      $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
  }

  //查询当前登录人同企业联系人
  public function enterprisestaffsAction(){
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $curuser = $this->get('security.context')->getToken()->getUser();
    $cur_account=$curuser->getUsername();
    $eno=$curuser->eno;
    $pagesize=$request->get("pagesize","20");
    $page=$request->get("page","0");
    $da=$this->get("we_data_access");
    try {
          
          $base_sql=" select a.login_account,a.fafa_jid jid, a.nick_name, a.photo_path, a.photo_path_small, a.photo_path_big,ep.eshortname,0 attention
          from we_staff  a inner join we_enterprise ep on a.eno=ep.eno where a.eno = ? 
          and not exists(select 1 from we_micro_account  where we_micro_account.number=a.login_account) 
          limit ".$page.", ".$pagesize;

          $page=((float)$page)*((float)$pagesize);
          $dataset = $da->GetData("staffs",$base_sql,array(
            (String)$eno
          ));
          $rows =  $dataset["staffs"]["rows"];
          $re['staffs']=$rows;
          $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
          $response->headers->set('Content-Type', 'text/json');
          return $response;
    }
     catch (Exception $e) {
      $this->get('logger')->err($e);
      $re['returncode']=ReturnCode::$SYSERROR;
      $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }
  }
  
  

  //Ȧ����Ա�б�
  public function circlestaffAction()
  {
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $curuser = $this->get('security.context')->getToken()->getUser();
    $cur_account=$curuser->getUsername();
    $request = $this->getRequest();
    $circle_id = $request->get("circle_id");
    $da = $this->get('we_data_access');
    if($circle_id=='9999'){
      $staffMgr = new \Justsy\BaseBundle\Management\Staff();
      $list = $staffMgr->getFriendBaseinfoList();
      $re["staffs"] = empty($list) ? array() : $list;
    }else{
      $sql = "select a.login_account,a.nick_name,c.eshortname,a.photo_path,a.photo_path_small,a.photo_path_big
      from we_staff a inner join we_circle_staff b on a.login_account=b.login_account
      inner join we_enterprise c on a.eno=c.eno
      where b.circle_id=? and not exists(select 1 from we_micro_account  where we_micro_account.number=a.login_account) ";
      $sql.=" order by b.login_account limit 0,50";
      $ds = $da->GetData("we_staff", $sql, array((string)$circle_id));
      if ($ds && $ds["we_staff"]["recordcount"] == 0) $re["staffs"] = array();
      else $re["staffs"] = $ds["we_staff"]["rows"];
    }

    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }

  public function circlestaffversionAction()
  {
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $curuser = $this->get('security.context')->getToken()->getUser();
    $cur_account=$curuser->getUsername();
    $request = $this->getRequest();
    $circle_id = $request->get("circle_id");
    
    $da = $this->get('we_data_access');
    /*
    if($circle_id=='9999'){
        $sql="select staff.login_account from (";
        $sql.=" select tt.atten_id from(";
        $sql.=" select atten.atten_id from we_staff_atten atten ";
        $sql.=" WHERE atten.login_account='{$cur_account}' and atten.atten_type='01'";
        $sql.=" ) tt WHERE f_checkAttentionWithAccount('{$cur_account}',tt.atten_id)=2";
        $sql.=" ) att left join we_staff staff on att.atten_id=staff.login_account";
        $sql.=" inner join we_enterprise c on staff.eno=c.eno";
        $sql.=" UNION ";
        $sql.="select login_account";
        $sql.=" from we_staff a ";
        $sql.=" inner join we_enterprise c on a.eno=c.eno and a.login_account='{$cur_account}'";
        $sql.=" order by 1";
        $ds = $da->GetData("we_staff", $sql);
        if ($ds && $ds["we_staff"]["recordcount"] == 0)
        {
          $re["returncode"] = ReturnCode::$SYSERROR;
        }
        else
        {
          $s = "";
          foreach($ds["we_staff"]["rows"] as $key => $value)
          {
            $s .= $value['login_account'];
          }
          $re["version"] = md5($s);
        }
    }else{
        $sql = "select login_account from we_circle_staff where circle_id=? order by 1";
        $ds = $da->GetData("we_staff", $sql, array((string)$circle_id));
        if ($ds && $ds["we_staff"]["recordcount"] == 0)
        {
          $re["returncode"] = ReturnCode::$SYSERROR;
        }
        else
        {
          $s = "";
          foreach($ds["we_staff"]["rows"] as $key => $value)
          {
            $s .= $value['login_account'];
          }
          $re["version"] = md5($s);
        }
    }
    */
    
    $re["version"] = $this->getVersionChange(3,$circle_id);
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  
  public function getServerDiffTimeAction(){
      $re = array("returncode" => ReturnCode::$SUCCESS);
      $request = $this->getRequest();
      $time = $request->get("time");
      $re['server_time']=$this->getMillisecond($time);
      $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
  }
  
  //获得版本号变化情况
  public function getVersionChange($type,$number)
  {
  	 $da=$this->get("we_data_access");
  	 $eno = $this->get('security.context')->getToken()->getUser()->eno;
  	 $verchange = new \Justsy\BaseBundle\Management\VersionChange($da,$this->get("logger"));
     $result = $verchange->GetVersionChange($type,$number,$eno);
     return $result;
  }
  
  public function getMillisecond($c_time)
  {
      $time = explode (" ", microtime () );
      $time = ($time [1]+$time [0])* 1000;
      $time = round($c_time-$time,0);
      return $time;
  }
  
  //获得用户所在组织部门
  //对第三方提供。由于不需要登录就可调用该接口，所以只能通过获取参数Openid取得当前人员信息
  public function getDepartmentAction(){
  	$request = $this->get("request");
  	
    $uEno="";
    $userJid="";
    $currUser = $this->get('security.context')->getToken();
    if(!empty($currUser)){
       $user = $currUser->getUser();
       $uEno = $user->eno;//当前登录人企业 号
       $userJid = $user->fafa_jid;
    }
    else
    {
    	  $ds = $this->getstaffinfo($request->get("Openid").$request->get("openid"));
    	  $uEno =  $ds["eno"];
    	  $userJid = $ds["fafa_jid"];
    }
  	
  	$name = $request->get("name");
  	$sql = "";
  	$parameter= Array();
  	if(empty($name)){
  	  $sql = "select dept_id,dept_name,parent_dept_id from we_department where eno=?";
  	  array_push($parameter,(String)$uEno);
  	}
  	else{
  		$sql = "select dept_id,dept_name,parent_dept_id from we_department where dept_name like concat('%',?,'%') and eno=?";
  		array_push($parameter,(String)$name);
  	  array_push($parameter,(String)$uEno);
  	}
    $da = $this->get("we_data_access");
    $ds = $da->GetData("department", $sql, $parameter);
    $ds["returncode"]="0000";
    //$response = new Response(json_encode($ds["department"]));
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($ds).");" : json_encode($ds));
	  $response->headers->set('Content-Type', 'text/json');
	  return $response;
  }
  
  //获得用户所在组织人员信息
  //对第三方提供。由于不需要登录就可调用该接口，所以只能通过获取参数Openid取得当前人员信息
  public function getenostaffAction(){
  	$request = $this->get("request");
  	$nick_name = $request->get("nick");
  	$letter = $request->get("letter");
  	$sql = "";
  	$parameter= Array();
    $uEno="";
    $userJid="";
    $currUser = $this->get('security.context')->getToken();
    if(!empty($currUser)){
       $user = $currUser->getUser();
       $uEno = $user->eno;//当前登录人企业 号
       $userJid = $user->fafa_jid;
    }
    else
    {
    	  $ds = $this->getstaffinfo($request->get("Openid").$request->get("openid"));
    	  $uEno =  $ds["eno"];
    	  $userJid = $ds["fafa_jid"];
    }
  	if(empty($letter)){
  	  if(empty($nick_name)){
  	    $sql = "select a.dept_id,a.login_account,a.nick_name as name,a.photo_path_small,a.photo_path,a.fafa_jid,a.openid,a.mobile from we_staff a where a.state_id=1 and a.eno=? and not exists (select 1 from we_micro_account where number=a.login_account and eno=?) order by login_account asc";
  	    array_push($parameter,(String)$uEno);
  	    array_push($parameter,(String)$uEno);
  	  }
  	  else{
  		  if ((ord($nick_name) & 0x80) == 128){
  			  $sql = "select dept_id,login_account,nick_name as name,photo_path_small,photo_path,fafa_jid,openid,mobile from we_staff where nick_name like concat('%',?,'%') and eno=? and not exists (select 1 from we_micro_account where number=a.login_account and eno=?) order by login_account asc";
  		    array_push($parameter,(String)$nick_name);
  	      array_push($parameter,(String)$uEno);
  	      array_push($parameter,(String)$uEno);
  		  }
  		  else{
          $sql = "select dept_id,login_account,nick_name as name,photo_path_small,photo_path,fafa_jid,openid,mobile from we_staff where (nick_name like concat('%',?,'%') or login_account like concat('%',?,'%')) and eno=? and not exists (select 1 from we_micro_account where number=a.login_account and eno=?) order by login_account asc";
  		    array_push($parameter,(String)$nick_name);
  		    array_push($parameter,(String)$nick_name);
  	      array_push($parameter,(String)$uEno);
  	      array_push($parameter,(String)$uEno);
  		  }
  	  }
    }
    else{
    	if(empty($nick_name)){
		    $sql = "select dept_id,login_account,nick_name as name,photo_path_small,photo_path,fafa_jid,openid,mobile from we_staff where login_account like concat(?,'%') and eno=? order by login_account asc";
		  	array_push($parameter,(String)$letter);
		  	array_push($parameter,(String)$uEno);
	  	}
	  	else{
	  		if ((ord($nick_name) & 0x80) == 128){
  			  $sql = "select dept_id,login_account,nick_name as name,photo_path_small,photo_path,fafa_jid,openid,mobile from we_staff where nick_name like concat('%',?,'%') and login_account like concat('%',?,'%') and eno=? order by login_account asc";
  		    array_push($parameter,(String)$nick_name);
  		    array_push($parameter,(String)$letter);
  	      array_push($parameter,(String)$uEno);
  		  }
  		  else{
          $sql = "select dept_id,login_account,nick_name as name,photo_path_small,photo_path,fafa_jid,openid,mobile from we_staff where (nick_name like concat('%',?,'%') or login_account like concat('%',?,'%')) and login_account like concat('%',?,'%') and eno=? order by login_account asc";
  		    array_push($parameter,(String)$nick_name);
  		    array_push($parameter,(String)$nick_name);
  		    array_push($parameter,(String)$letter);
  	      array_push($parameter,(String)$uEno);
  		  }
	  	}
    }
    $da = $this->get("we_data_access");
    $data=$da->GetData("dt", $sql, $parameter);
    $ds=array('returncode'=>'0000','list'=>array());
    if($data!=null && count($data['dt']['rows'])>0 ) $ds['list']=$data['dt']['rows'];
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($ds).");" : json_encode($ds));
	  $response->headers->set('Content-Type', 'text/json');
	  return $response;
  }
  
  //企业组织树
  public function getenotreeAction(){
  	$request = $this->get("request");
  	$user = $this->get('security.context')->getToken()->getUser();
  	$root = "v".$user->eno;
    $sql = "select dept_id,dept_name,case dept_id when parent_dept_id then '".$root."' else parent_dept_id end parent_dept_id,create_staff from we_department where eno=?";
    $da = $this->get("we_data_access");
    $ds = $da->GetData("dept", $sql, array((string)$user->eno));
    $re=$ds['dept']['rows'];
    $treedata=array();    
    $treedata[]=array('id'=>$root,'name'=>$user->eshortname,'pId'=>0,'open'=>true);
    for($i = 0; $i<count($re); $i++)
    {
    	if(!empty($result)) $result .=",";
      $treedata[]= array('id'=>$re[$i]['dept_id'],'name'=>$re[$i]['dept_name'],'pId'=>$re[$i]['parent_dept_id'],'open'=>true);
    }
    $ds=array("returncode"=>"0000");
    $ds["tree"]=$treedata;
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($ds).");" : json_encode($ds));
	  $response->headers->set('Content-Type', 'text/json');
	  return $response;  	
  }
  //企业组织树包括人员
  public function getOrganization(){
  	$request = $this->get("request");
  	$user = $this->get('security.context')->getToken()->getUser();
  	$root = "v".$user->eno;
    $sql = "select dept_id,dept_name,case dept_id when parent_dept_id then '".$root."' else parent_dept_id end parent_dept_id,create_staff from we_department where eno=?";
    $da = $this->get("we_data_access");
    $ds = $da->GetData("dept", $sql, array((string)$user->eno));
    $re=$ds['dept']['rows'];
    $treedata=array();    
    $treedata[]=array('id'=>$root,'name'=>$user->eshortname,'pId'=>0,'open'=>true);
    for($i = 0; $i<count($re); $i++)
    {
    	if(!empty($result)) $result .=",";
      $treedata[]= array('id'=>$re[$i]['dept_id'],'name'=>$re[$i]['dept_name'],'pId'=>$re[$i]['parent_dept_id'],'type'=>'org','open'=>true);
    }
   	$sql="select case when dept_id is null or dept_id='' then '$root' else dept_id end dept_id,login_account,nick_name,openid from we_staff where eno=? and auth_level<>'J'";
   	$params=array($user->eno);
   	$ds=$da->Getdata('staff',$sql,$params);
   	$staffs=$ds['staff']['rows'];
   	for($i=0;$i<count($staffs);$i++)
   	{
   		$treedata[]=array('id'=>$staffs[$i]['login_account'],'name'=>$staffs[$i]['nick_name'],'pId'=>$staffs[$i]['dept_id'],'openid'=>$staffs[$i]['openid'],'type'=>'account');
   	}
   	$ds=array("returncode"=>"0000");
    $ds["tree"]=$treedata;
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($ds).");" : json_encode($ds));
	  $response->headers->set('Content-Type', 'text/json');
	  return $response;  		
  }
  //获取指定帐号的openid。只能获取当前人员所在企业的人员的openid信息。暂时不对第三方开放
  public function getuseropenidAction($account)
  {
  	$re = array("returncode" => ReturnCode::$SUCCESS);
  	$request = $this->get("request");
    $uEno="";
    
  	if(empty($account))
  	{
					  $re["returncode"]=ReturnCode::$SYSERROR;
					  $re["msg"]="查询的人员帐号无效";
				    $response=new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
				    $response->headers->set('Content-Type', 'text/json');
				    return $response;	  	    	
  	}    
    
    $currUser = $this->get('security.context')->getToken();
    if(!empty($currUser)){
       $user = $currUser->getUser();
       $uEno = $user->eno;//当前登录人企业 号
    }
    else
    {
    	  $ds = $this->getstaffinfo($request->get("Openid").$request->get("openid"));
    	  $uEno =  $ds["eno"];
    }
    
    //获取目标帐号的所在企业及openid信息
    $staffMgr = new \Justsy\BaseBundle\Management\Staff($this->get('we_data_access'),$this->get('we_data_access_im'),$account,$this->get("logger"));
    $staff = $staffMgr->getInfo();
  	if(empty($staff))
  	{
					  $re["returncode"]=ReturnCode::$SYSERROR;
					  $re["msg"]="查询的人员帐号无效";
				    $response=new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
				    $response->headers->set('Content-Type', 'text/json');
				    return $response;	  	    	
  	}
  	if($uEno!=$staff["eno"])
  	{
  		$notaccess = true;//没权限访问
  		//判断当前帐号是否是微应用。是则需要判断该帐号是否已关注了当前微应用，成功关注的才能获取对应的openid
  		if(!empty($user))
  		{
  		    $biz_sql = "select 1 from we_micro_account where number=?";	
  		    $da = $this->get('we_data_access');
  		    $biz_ds = $da->GetData("biz",$biz_sql,array((string)$user->getUserName()));
  		    if($biz_ds && count($biz_ds["biz"]["rows"])>0)
  		    {
  		       //当前登录帐号是微应用,判断获取openid的帐号是否关注了微应用
  		       $biz_sql ="select 1 from we_staff_atten where login_account=? and atten_id=?";
  		       $biz_atten_ds = $da->GetData("biz_atten",$biz_sql,array((string)$staff["login_account"], (string)$user->getUserName()));
  		       if($biz_atten_ds && count($biz_atten_ds["biz_atten"]["rows"])>0)
  		       {
  		           	$notaccess=false;
  		       }
  		    }
  		}
  		if($notaccess)
  		{
					  $re["returncode"]=ReturnCode::$NOTACCESS;
					  $re["msg"]="没有足够的权限查询该帐号信息";
				    $response=new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
				    $response->headers->set('Content-Type', 'text/json');
				    return $response;
			}
  	}
  	$re["login_account"] = $staff["login_account"];
  	$re["nick_name"] = $staff["nick_name"];
  	$re["openid"] = $staff["openid"];
		$response=new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		$response->headers->set('Content-Type', 'text/json');
		return $response;	  	
  }
  
  public function circlestafflimitAction()
  {
      $re = array("returncode" => ReturnCode::$SUCCESS);
      $request = $this->getRequest();
      $circle_id = $request->get("circle_id");
      $page=$request->get("page");
      $like=$request->get("like");
      $da = $this->get('we_data_access');
      if(empty($circle_id)){
      	$re["returncode"] = ReturnCode::$SYSERROR;
      	$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
      	$response->headers->set('Content-Type', 'text/json');
      	return $response;
      }
      $currUser = $this->get('security.context')->getToken();
      if(!empty($currUser)){
      	$user = $currUser->getUser();
      	$userAccount = $user->getUserName();
      }
      else
      {
      	//当应用通过api接口调用时，不用登录，只能通过openid获取人员信息
      	$ds = $this->getstaffinfo($request->get("Openid").$request->get("openid"));
      	$userAccount = $ds["login_account"];
      }
      
      if($circle_id=='9999'){
        $staffMgr = new \Justsy\BaseBundle\Management\Staff();
        $list = $staffMgr->getFriendBaseinfoList();
        if( empty($list) )
          $list= array();
        $result = array(); 
        $startPos=0;       
        if(!empty($page)){
          $startPos=((float)$page)*30;
        }
        for($i= $startPos; $i<min(count($list),$startPos+30); $i++)
        {
          $result[]= $list[$i];
        }
        $re["staffs"] = $result; 
      }else{
        $sql="select * from (";
        $sql.="select a.login_account,a.nick_name,c.eshortname,a.photo_path,a.photo_path_small,a.photo_path_big,a.fafa_jid from we_staff a inner join we_enterprise c on a.eno=c.eno ";
      	$sql.=" inner join we_circle_staff b on a.login_account=b.login_account  where b.circle_id=? ";
     
        if(!empty($like)){
        	  if(strlen($like)==mb_strlen($like,"utf-8"))
            {
        	  		$sql.=" and (a.nick_name like CONCAT('{$like}','%') or a.login_account like CONCAT('{$like}','%'))";      	  	
        	  }else
            {
        	  	$sql.=" and a.nick_name like CONCAT('{$like}','%')";
        	  }
        }
        $sql.=" and not exists(select 1 from we_micro_account  where we_micro_account.number=a.login_account) ";
        $sql.=" ) rsp order by 1";
        if(!empty($page)){
        	$page=((float)$page)*30;
        	$sql.=" limit {$page}, 30";
        }else{
        	$sql.=" limit 0, 30";
        }
        $ds = $da->GetData("we_staff", $sql, array((string)$circle_id));
        $re["staffs"] = $ds["we_staff"]["rows"];
      }
      $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
  }
  
  public function getCurrUserDirAction()
  {
    	$request=$this->get("request");
    	$user = $this->get('security.context')->getToken()->getUser();
    	$dirid=$request->get('dirid');
    	$mode=$request->get('mode');
    	   if($mode!='0' && $mode!='1'){
    	   	 $re = array("returncode" => "9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="request parameter(mode) no right.";
			       $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;
    	   }
    	if(empty($dirid)){
    		$dirid="c".$user->get_circle_id("");
    	}
    	
    	$rows=$this->getUserDirBy($user->getUserName(),$dirid,$mode);
    	$re = array("returncode" => "0000");
    	$re['recordcount']=count($rows);
    	$re['rows']=$rows;
    	$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
  }
    public function getShareDirAction()
    {
    	$request=$this->get("request");
    	$user = $this->get('security.context')->getToken()->getUser();
    	$da = $this->get('we_data_access');
    	
    	$sql="select b.id,b.parentid,b.name,b.createdate,b.remark,b.owner from we_doc_share a inner join we_doc_dir b on b.id=a.resourceid and b.owner!=? where a.resourcetype='0' 
    	and ((a.objecttype='p' and objectid=?)
    	 or (a.objecttype='c' and exists(select 1 from we_circle_staff where circle_id=a.objectid and login_account=?))
    	 or (a.objecttype='g' and exists(select 1 from we_group_staff where group_id=a.objectid and login_account=?))
    	 or (a.objecttype='d' and exists(select 1 from we_staff where dept_id=a.objectid and login_account=?)))";
    	$params=array();
      array_push($params,$user->getUserName());
      array_push($params,$user->getUserName());
      array_push($params,$user->getUserName());
      array_push($params,$user->getUserName());
      array_push($params,$user->getUserName());
      
      $ds=$da->Getdata('share',$sql,$params);
      $rows=$ds['share']['rows'];
			$re = array("returncode" => "0000");
			$re['recordcount']=count($rows);
			$re['rows']=$rows;
    	$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }
    private function getDirTree($searchby,$dirid)
    {
    	if(empty($dirid))return array();
    	 $result=array();
    	 foreach($searchby as $row){
    	 	 if($row['id']==$dirid){
    	 	 	$result[]=$row;
    	 	 }
    	 	 if($row['parentid']==$dirid){
    	 	 	 $result=array_merge($result,$this->getDirTree($searchby,$row['id']));
    	 	 }
    	 }
    	 return $result;
    }
    private function getUserDirBy($username,$dirid,$mode)
    {
    	if(empty($dirid))return array();
    	$da = $this->get('we_data_access');
    	
    	$sql="select id,parentid,name,createdate,remark,owner from we_doc_dir where owner=?";
    	$params=array($username);
    	if($mode=='0'){
    		$sql.=" and parentid=?";
    		$params[]=$dirid;
    	}
    	
    	$ds=$da->Getdata('doc',$sql,$params);
    	$rows=$ds['doc']['rows'];
    	if($mode='1'){
    		return $this->getDirTree($rows,$dirid);
    	}
    	else{
    		return $rows;
    	}
    }
    public function getUserFilesAction()
    {
    	$request=$this->get("request");
    	$user = $this->get('security.context')->getToken()->getUser();
    	$da = $this->get('we_data_access');
    	$dirid=$request->get('dirid');
    	$params=array();
    	if(empty($dirid)){
    		$dirid="c".$user->get_circle_id("");
    		$sql="select distinct a.file_id,a.file_name,a.file_ext,a.up_by_staff,a.up_date,a.dir from we_files a where a.dir=?";
    		array_push($params,$dirid);
    	}
    	else{
	    	$sql="select distinct a.file_id,a.file_name,a.file_ext,a.up_by_staff,a.up_date,a.dir from we_files a where a.dir=? and exists(select 1 from we_doc_dir b where b.id=a.dir and b.owner=?)";
	      
	    	
	    	array_push($params,$dirid);
	    	array_push($params,$user->getUserName());
	    }
      
      $ds=$da->Getdata('file',$sql,$params);
      $rows=$ds['file']['rows'];
      $re = array("returncode" => "0000");
      $re['recordcount']=count($rows);
      $re['rows']=$rows;
      $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }
    public function getShareFilesAction()
    {
    	$request=$this->get("request");
    	$user = $this->get('security.context')->getToken()->getUser();
    	$da = $this->get('we_data_access');
    	
    	$sql="select distinct a.file_id,a.file_name,a.file_ext,a.up_by_staff,a.up_date,a.dir from we_files a,we_doc_share b where ((a.file_id=b.resourceid and b.resourcetype='1' and ((b.objecttype='p' and b.objectid=?)
    	 or (b.objecttype='c' and exists(select 1 from we_circle_staff where circle_id=b.objectid and login_account=?))
    	 or (b.objecttype='g' and exists(select 1 from we_group_staff where group_id=b.objectid and login_account=?))
    	 or (b.objecttype='d' and exists(select 1 from we_staff where dept_id=b.objectid and login_account=?)))))";
    	 $params=array();
      array_push($params,$user->getUserName());
      array_push($params,$user->getUserName());
      array_push($params,$user->getUserName());
      array_push($params,$user->getUserName());
      
      $ds=$da->Getdata('file',$sql,$params);
      $rows=$ds['file']['rows'];
      $re=array();
      $re['recordcount']=count($rows);
      $re['rows']=$rows;
      $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }

  public function modifyavatarAction()
  {
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();
    $dm = $this->get('doctrine.odm.mongodb.document_manager');
    $da = $this->get("we_data_access");
    $login_account = $user->getUsername();    
    $photofile = $_FILES['photofile']['tmp_name'];
    if(empty($photofile)){
        $photofile = tempnam(sys_get_temp_dir(), "we");
        unlink($photofile);
        $somecontent1 = base64_decode($request->get('photodata'));
        if ($handle = fopen($photofile, "w+")) {
          if (!fwrite($handle, $somecontent1) == FALSE) {   
              fclose($handle);  
          }
        }
    }
    $photofile_24 = $photofile."_24";
    $photofile_48 = $photofile."_48";
    try 
    {
      if (empty($photofile)) throw new \Exception("param is null");
      $im = new \Imagick($photofile);
      $im->scaleImage(48, 48);
      $im->writeImage($photofile_48);
      $im->destroy();
      $im = new \Imagick($photofile);
      $im->scaleImage(24, 24);
      $im->writeImage($photofile_24);
      $im->destroy();

      $table = $da->GetData("staff","select photo_path,photo_path_small,photo_path_big 
        from we_staff where login_account=?",array((string)$login_account));
      if ($table && $table["staff"]["recordcount"] > 0)  //如果用户原来有头像则删除
      {
        Utils::removeFile($table["staff"]["rows"][0]["photo_path"],$dm);
        Utils::removeFile($table["staff"]["rows"][0]["photo_path_small"],$dm);
        Utils::removeFile($table["staff"]["rows"][0]["photo_path_big"],$dm);
      }
      if (!empty($photofile)) $photofile = Utils::saveFile($photofile,$dm);
      if (!empty($photofile_48)) $photofile_48 = Utils::saveFile($photofile_48,$dm);
      if (!empty($photofile_24)) $photofile_24 = Utils::saveFile($photofile_24,$dm);
      $da->ExecSQL("update we_staff set photo_path=?,photo_path_big=?,photo_path_small=? 
        where login_account=?",
        array((string)$photofile_48, (string)$photofile, (string)$photofile_24, (string)$login_account));

      $message = json_encode(array("path" => $this->container->getParameter('FILE_WEBSERVER_URL').$photofile));
      $staffMgr=new \Justsy\BaseBundle\Management\Staff($da,$this->get("we_data_access_im"),$user,$this->container->get("logger"),$this->container);
      Utils::sendImPresence($user->fafa_jid,implode(",", $staffMgr->getFriendJidList()),"staff-changeinfo",$message,$this->container,"","",false,Utils::$systemmessage_code);        

      $re["returncode"] = ReturnCode::$SUCCESS;
      $re["fileid"] = $photofile;
      $re["photo_path"] = $photofile_48;
      $re["photo_path_big"] = $photofile;
      $re["photo_path_small"] = $photofile_24;
    }
    catch (\Exception $e) 
    {
      $re["returncode"] = ReturnCode::$SYSERROR;
      $this->get('logger')->err($e);
    }
    
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }

  public function modifystaffinfoAction()
  {
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();
    $da = $this->get("we_data_access");
    $da_im = $this->get('we_data_access_im');
    $login_account = $user->getUsername();
    $nick_name = $request->get("nick_name");
    $sex_id = $request->get("sex_id");
    $self_desc = $request->get("self_desc");
    $work_phone = $request->get("work_phone");
    $dept_id = $request->get("dept_id");
    $duty = $request->get("duty");
    $old_self_desc = $user->self_desc;
    $sql = "update we_staff set ";
    $para = array();
    $changeAttr = array();
    try 
    {
      if ($nick_name !== null)
      {
        $sql .= "nick_name=?,";
        $para[] = $nick_name;
        $changeAttr["nick_name"] = $nick_name;
      }
      if ($sex_id !== null)
      {
        $sql .= "sex_id=?,";
        $para[] = $sex_id;
        $changeAttr["sex_id"] = $sex_id;
      }
      if ($self_desc !== null)
      {
        $sql .= "self_desc=?,";
        $para[] = $self_desc;
        $changeAttr["desc"] = $self_desc;
      }
      if ($work_phone !== null)
      {
        $sql .= "work_phone=?,";
        $para[] = $work_phone;
        $changeAttr["work_phone"] = $work_phone;
      }
      if ($dept_id !== null)
      {
        $sql .= "dept_id=?,";
        $para[] = $dept_id;
        $changeAttr["dept_id"] = $dept_id;
      }
      if ($duty !== null)
      {
        $sql .= "duty=?,";
        $para[] = $duty;
        $changeAttr["duty"] = $duty;
      }
      if (count($para) === 0) throw new \Exception("param is null");
      $sql = substr($sql, 0, strlen($sql)-1);
      $sql .= " where login_account=?";
      $para[] = $login_account;
      try
      {
         $da->ExecSQL($sql, $para);
      }
      catch(\Exception $e)
      {
         $this->get("logger")->err($e->getMessage());
      }
      
      
      if ($dept_id !== null)
      {
        $ds = $da->GetData("dept","select fafa_deptid from we_department where dept_id=?", array((string)$dept_id));
        if ($ds && $ds['dept']['recordcount']>0)
        {
          $fafa_deptid = $ds["dept"]["rows"][0]["fafa_deptid"];
          $sqls[] = "update im_employee set deptid=? where loginname=?";
          $paras[] = array((string)$fafa_deptid,(string)$user->fafa_jid);
          //重置IM数据及版本
          $da_im->ExecSQL("call dept_emp_stat(?)",array((string)$user->fafa_jid));
          $da_im->ExecSQL("delete from im_employee_version where us=?", array((string)$user->fafa_jid));
        }
      }
      if ($nick_name !== null)
      {
          try
          {
             //理改we_sns库相关昵称
             $da->ExecSQL("call emp_change_name(?,?)",array((string)$user->fafa_jid,(string)$nick_name));
             //理改we_im库相关昵称
             $da_im->ExecSQL("call emp_change_name(?,?)",array((string)$user->fafa_jid,(string)$nick_name));              
          }
          catch(\Exception $e)
          {
             $this->get("logger")->err($e->getMessage());
          }
      }
      $re["returncode"] = ReturnCode::$SUCCESS;
       if($self_desc !== null&&$old_self_desc!=$self_desc)  //签名发生变化事，处理人脉事件
        {
            $friendevent=new \Justsy\BaseBundle\Management\FriendEvent($da,$this->get('logger'),$this->container);
            $friendevent->signchange($user->getUserName(),$user->nick_name,$self_desc);        
        }
        //所有变化都默认通知在线的好友
        //if($self_desc !== null){
            $message = json_encode($changeAttr);
            $staffMgr=new \Justsy\BaseBundle\Management\Staff($da,$this->get("we_data_access_im"),$user);
            Utils::sendImPresence($user->fafa_jid,implode(",", $staffMgr->getFriendAndColleagueJid()),"staff-changeinfo",$message,$this->container,"","",false,Utils::$systemmessage_code);
        //}
        //通知自己在线的其他设备
        Utils::sendImPresence("",$user->fafa_jid,"staff-changeinfo","",$this->container,"","",false,Utils::$systemmessage_code);
    }
    catch (\Exception $e) 
    {
      $re["returncode"] = ReturnCode::$SYSERROR;
      $this->get('logger')->err($e);
    }
    
    $this->get("logger")->err("---------------2---------".$sql);
    
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }

  public function getdeptsAction()
  {
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();
    $da = $this->get("we_data_access");
    $sql="select dept_id,dept_name,parent_dept_id as parent_deptid,fafa_deptid from we_department where eno=? and fafa_deptid<>concat('v',?,'999') and fafa_deptid<>concat('v',?,'999888') and fafa_deptid<>concat('v',?,'')";
    $params=array();
    array_push($params, (string)$user->eno);
    array_push($params, (string)$user->eno);
    array_push($params, (string)$user->eno);
    array_push($params, (string)$user->eno);
    $ds = $da->GetData("dept",$sql, $params);
    if ($ds) $re["depts"] = $ds["dept"]["rows"];
    else $re["depts"] = array();

    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  
  public function getmobilevaildcodeAction()
  {
  	$re = array("returncode" => ReturnCode::$SUCCESS);
  	$da = $this->get("we_data_access");
  	$user = $this->get('security.context')->getToken()->getUser();
  	$accountContorller=new AccountController();
  	$request = $this->getRequest();
  	$session = $request->getSession();
  	$txtmobile = $request->get("txtmobile");
  
  	if (empty($txtmobile) || !preg_match("/^(1[8|3|5][0-9]|15[0|3|6|7|8|9]|18[2|5|6|8|9])\d{8}$/",$txtmobile))
  	{
  		$re["returncode"] = ReturnCode::$SYSERROR;
  		$re["msg"] = "请输入正确的手机号！";
  
	 	$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
	    $response->headers->set('Content-Type', 'text/json');
  		return $response;
  	}
  	//判断此手机已被绑定
  	$sql="select 1 from we_staff where mobile_bind=?";
  	$params=array($txtmobile);
  	$ds=$da->Getdata('lo',$sql,$params);
  	if($ds['lo']['recordcount'] > 0)
  	{
  		$re["returncode"] = ReturnCode::$SYSERROR;
  		$re["msg"] = "该手机号已被绑定！";
  		 
	 	$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
	    $response->headers->set('Content-Type', 'text/json');
  		return $response;
  	}
  	$lastgetmobilevaildcodetime = $session->get("lastgetmobilevaildcodetime");
  	$getmobilevaildcodenums = $session->get("getmobilevaildcodenums");
  
  	if (empty($lastgetmobilevaildcodetime)) $lastgetmobilevaildcodetime = time()-60*60;
  	if (empty($getmobilevaildcodenums)) $getmobilevaildcodenums = 0;
  
  	try
  	{
  		if ($lastgetmobilevaildcodetime + 90 > time()) //1分钟只能取一次
  		{
  			$re["returncode"] = ReturnCode::$SYSERROR;
  			$re["msg"] = "你获取验证码的次数太频繁！90秒钟只能取一次!";
  
  			$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    		$response->headers->set('Content-Type', 'text/json');
  			return $response;
  		}
  		if ($getmobilevaildcodenums >= 5 && $lastgetmobilevaildcodetime + 60*60*24 > time()) //最多三次
  		{
  			$re["returncode"] = ReturnCode::$SYSERROR;
  			$re["msg"] = "你获取验证码的次数太多！每天最多只能取5次!";
  
  			$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    		$response->headers->set('Content-Type', 'text/json');
  			return $response;
  		}
  
  		$mobilevaildcode = rand(100000, 999999);
  
  		$user = $this->container->getParameter("SMS_ACT");
  		$pass  = md5($this->container->getParameter("SMS_PWD"));//需要MD5
  		$phone  = $txtmobile;
  		$content = "欢迎使用Wefafa，您的验证码是：$mobilevaildcode 。【发发时代】";
  		$content = urlEncode(urlEncode(mb_convert_encoding($content, 'gb2312' ,'utf-8')));
  		$apidata="func=sendsms&username=$user&password=$pass&mobiles=$phone&message=$content&smstype=0&timerflag=0&timervalue=&timertype=0&timerid=0";
  		$apiurl = $this->container->getParameter("SMS_URL");
  		$ret = $accountContorller->do_post_request($apiurl,$apidata);
  		if(strpos($ret,"<errorcode>0</errorcode>")>0)
  		{
  			$session->set("mobilevaildcode", $mobilevaildcode);
  			$session->set("lastgetmobilevaildcodetime", time());
  			$session->set("getmobilevaildcodenums", $getmobilevaildcodenums+1);
  			$session->set("txtmobile",$txtmobile);
  
  			$re["returncode"] = ReturnCode::$SUCCESS;
  		}
  		else
  		{
  			$re["returncode"] = ReturnCode::$SYSERROR;
  			$re["msg"] = "短信发送失败！请重试";
  			$this->get('logger')->info($ret);
  		}
  	}
  	catch (\Exception $e)
  	{
  		$re["returncode"] = ReturnCode::$SYSERROR;
  		$re["msg"] = "获取并发送短信验证码失败！请重试";
  		$this->get('logger')->err($e);
  	}
  
   	$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
  	return $response;
  }
  
  public function savemobilebindAction()
  {
  	$re = array();
  	$user = $this->get('security.context')->getToken()->getUser();
  	$request = $this->getRequest();
  
  	$txtmobile = $request->get("txtmobile");
  	$txtvaildcode = $request->get("txtvaildcode");
  
  	if (empty($txtmobile))
  	{
  		$re["returncode"] = ReturnCode::$SYSERROR;
  		$re["msg"] = "请输入正确的手机号！";
  
	 	$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
	    $response->headers->set('Content-Type', 'text/json');
  		return $response;
  	}
  	if($txtmobile != $request->getSession()->get("txtmobile"))
  	{
  		$re["returncode"] = ReturnCode::$SYSERROR;
  		$re["msg"] = "两次手机号输入不一致！";
  
	 	$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
	    $response->headers->set('Content-Type', 'text/json');
  		return $response;
  	}
  	if (empty($txtvaildcode) || $txtvaildcode != $request->getSession()->get("mobilevaildcode"))
  	{
  		$re["returncode"] = ReturnCode::$SYSERROR;
  		$re["msg"] = "请输入正确的验证码！";
  
	  	$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
	    $response->headers->set('Content-Type', 'text/json');
  		return $response;
  	}
  	try
  	{
  		$sqls = array();
  		$all_params = array();
  
  		$sql = "update we_staff set mobile_bind=null where mobile_bind=?";
  		$params = array();
  		$params[] = $txtmobile;
  
  		$sqls[] = $sql;
  		$all_params[] = $params;
  
  		$sql = "update we_staff set mobile=?, mobile_bind=? where login_account=?";
  		$params = array();
  		$params[] = $txtmobile;
  		$params[] = $txtmobile;
  		$params[] = $user->getUserName();
  
  		$sqls[] = $sql;
  		$all_params[] = $params;
  
  		$da = $this->get("we_data_access");
  		$da->ExecSQLs($sqls, $all_params);
  
  		//发送手机绑定通知
  		try{
  			$noticeMsg = array();
  			$noticeMsg["login_account"]=$user->fafa_jid;
  			$noticeMsg["nick_name"]=$user->nick_name;
  			$noticeMsg["mobile_bind"]="1";
  			$noticeMsg["mobile"]=$txtmobile;
  			$message = json_encode($noticeMsg);
  			$staffMgr=new \Justsy\BaseBundle\Management\Staff($da,$this->get("we_data_access_im"),$user);
  			$recv=$staffMgr->getFriendAndColleagueJid();
  			array_push($recv,$user->fafa_jid);
  			Utils::sendImPresence("",implode(",",$recv),"mobile_bind",$message,$this->container,"","",false,Utils::$systemmessage_code);
  		}
  		catch (\Exception $e) {
  			$this->get("logger")->err($e);
  		}
  		$re["returncode"] = ReturnCode::$SUCCESS;
  	}
  	catch (\Exception $e)
  	{
  		$re["returncode"] = ReturnCode::$SYSERROR;
  		$re["msg"] = "绑定手机号失败！请重试";
  		$this->get('logger')->err($e);
  	}
  
  	$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
  	return $response;
  }
  
  //修改密码
  public function updatepasswordAction()
  {
  	$re = array();
  	$request = $this->getRequest();
  	$user = $this->get('security.context')->getToken()->getUser();
  	$factory = $this->get('security.encoder_factory');
  	$encoder = $factory->getEncoder($user);
  	$oldpwd = $request->get('txtoldpwd');
  	$pwd = $request->get("txtnewpwd");
  	if(empty($oldpwd)){
  		$re["returncode"] = ReturnCode::$SYSERROR;
  		$re["msg"] = "原始密码不能为空";
  		$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
  		$response->headers->set('Content-Type', 'text/json');
  		return $response;
  	}
  	if(empty($pwd)){
  		$re["returncode"] = ReturnCode::$SYSERROR;
  		$re["msg"] = "新密码不能为空";
  		$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
  		$response->headers->set('Content-Type', 'text/json');
  		return $response;
  	}
  	$da = $this->get("we_data_access");
  	$table = $da->GetData("staff","select eno, password, fafa_jid,t_code from we_staff where login_account=?",array((String)$user->getUsername()));
  	$Jid = $table["staff"]["rows"][0]["fafa_jid"];
  	$eno = $table["staff"]["rows"][0]["eno"];
  	$OldPass = $table["staff"]["rows"][0]["password"];
  	$Old_t_code = $table["staff"]["rows"][0]["t_code"];
  	 
  	$oldpwd = $encoder->encodePassword($oldpwd, $user->getSalt());
  	if ($oldpwd != $OldPass)
  	{
  		$re["returncode"] = ReturnCode::$SYSERROR;
  		$re["msg"] = "原始密码不正确";
  		$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
  		$response->headers->set('Content-Type', 'text/json');
  		return $response;
  	}
  	 
  	$sql = "update we_staff set password=?,t_code=? where login_account=?";
  	$paras[0] = $encoder->encodePassword($pwd, $user->getSalt());
  	$paras[1] = DES::encrypt($pwd);
  	$paras[2] = $user->getUsername();
  	try
  	{
  		$da->ExecSQL($sql,$paras);
  		//同步ejabberd
  		try{
  			$sql_im = "update users set password=? where username=?";
  			$para_im = array();
  			$para_im[] = (string)$pwd;
  			$para_im[] = (string)$user->fafa_jid;
  			$da_im = $this->get('we_data_access_im');
  			$da_im->ExecSQL($sql_im, $para_im);
  
  			$re["returncode"] = ReturnCode::$SUCCESS;
  			$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
  			$response->headers->set('Content-Type', 'text/json');
  			return $response;
  		}
  		catch(\Exception $e)
  		{
  			//还原原密码
  			$sql = "update we_staff set password=?,t_code=? where login_account=?";
  			$paras[0] = $OldPass;
  			$paras[1] = $Old_t_code;
  			$paras[2] = $user->getUsername();
  			$da->ExecSQL($sql,$paras);
  			$re["returncode"] = ReturnCode::$SYSERROR;
  			$re["msg"]="同步密码出错";
  			$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
  			$response->headers->set('Content-Type', 'text/json');
  			return $response;
  		}
  	}
  	catch(\Exception $e)
  	{
  		$re["returncode"] = ReturnCode::$SYSERROR;
  		$re["msg"]="系统出错";
  		$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
  		$response->headers->set('Content-Type', 'text/json');
  		return $response;
  	}
  }

   /**
  *模糊查询联系人staff
  */
  public function searchstaffsAction(){
    $request=$this->getRequest();
    $curuser = $this->get('security.context')->getToken()->getUser();
    $cur_account=$curuser->getUsername();
    $search=$request->get("search");
    $eno = $request->get("eno");
    $pagesize=$request->get("pagesize","20");
    $page=$request->get("page","0");
    $da=$this->get("we_data_access");    
    //群成员fafa_jid
    $group_staff = array();
    $groupid = $request->get("groupid");    
    $action = $request->get("action"); //擴展參數。用于限制搜索員工時的范圍。默認為不限制
    //action:暫時支持創建群 成員搜索
    if($action=="create_group")
    {

    }
    else if ($action=="search_group_staff"){  //搜索群成员
    	if ( !empty($groupid)){
	    	$da_im = $this->get("we_data_access_im");
	    	$sql_im = "select employeeid from im_groupemployee where groupid=?;";
	    	$ds_im = $da_im->GetData('groupemployee',$sql_im,array((string)$groupid));
	    	if ( $ds_im && $ds_im["groupemployee"]["recordcount"]>0){
	    		for($i=0;$i< $ds_im["groupemployee"]["recordcount"];$i++){
	    	    array_push($group_staff,(string)$ds_im["groupemployee"]["rows"][$i]["employeeid"]);
	    	  }
	    	}
      }
    }
    $re= array('returncode' => ReturnCode::$SUCCESS);
    if(empty($cur_account)){
      $re['returncode']=ReturnCode::$SYSERROR;
      $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }
    if(empty($eno))
    {
    	$deploy_mode = $this->container->getParameter('deploy_mode');//获取部署模式，E表示是企业独立部署，C表示是云端模式
    	if($deploy_mode =="E")
    	{
    		$eno = $curuser->eno;
    	}
    }
    try {
        $orderKey = "login_account";
        $sql="";
        $base_sql=" select a.openid, a.login_account,a.fafa_jid jid, a.nick_name, a.photo_path, a.photo_path_small, a.photo_path_big,
                           ep.dept_name eshortname,a.active_date,0 ingroup,ifnull(self_desc,'') self_desc
                    from we_staff  a    inner join we_department ep on a.eno=ep.eno and a.dept_id=ep.dept_id";
        if (!empty($eno))
            $base_sql .= " and ep.eno={$eno} ";
        if (preg_match("/^(1[8|3|5][0-9]|15[0|3|6|7|8|9]|18[2|5|6|8|9])\d{8}$/", $search)) 
        {
          $sql.=$base_sql." where a.mobile like '{$search}%' ";
          $orderKey = "mobile";
        }
        else if(!empty($search))
        {
            $sql.=$base_sql;
            $array=explode("@",$search);
            if(strlen($search)==mb_strlen($search,"utf-8"))
            {
              if(!empty($array)&&count($array)>1)
              {
                  $sql.=" where a.login_account like CONCAT('{$search}','%') ";
                  $orderKey = "login_account";
              }else
              {
                  $sql.=" where a.login_account like CONCAT('{$search}','%')";
                  $sql.=" union ".$base_sql." where a.nick_name like CONCAT('{$search}','%') ";
                  $orderKey = "nick_name";             
              }
            }
            else
            {
                $sql.=" where a.nick_name like CONCAT('{$search}','%') ";
                $orderKey = "nick_name";
            }
        }
        else
        {
            $sql.=$base_sql." where 1=1 ";
        }
        $sql.=" and not exists(select 1 from we_micro_account  where we_micro_account.number=a.login_account)  and a.login_account!='".$cur_account."'";
        // $sql.=" ) staff_res where staff_res.attention<>-1 ";
        $sql.=" order by ".$orderKey;
        $page=((float)$page)*((float)$pagesize);
        $sql.=" limit {$page}, {$pagesize}";
          
        $this->get('logger')->err($sql);
        $dataset = $da->GetData("staffs",$sql);
        //处理数据
        if ( count($group_staff)>0 && $dataset && $dataset["staffs"]["recordcount"]>0){
        	for($i=0;$i< $dataset["staffs"]["recordcount"];$i++){
        		$jid = $dataset["staffs"]["rows"][$i]["jid"];
        		if ( in_array($jid,$group_staff)){
        		  $dataset["staffs"]["rows"][$i]["ingroup"] = "1";
        		}
        	}
        }
        $rows =  $dataset["staffs"]["rows"];
        $re['staffs']=$rows;
        $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }
     catch (Exception $e) {
      $this->get('logger')->err($e);
      $re['returncode']=ReturnCode::$SYSERROR;
      $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }
  }
  //提醒设置
  public function hintsetAction()
  {
  	  $request=$this->getRequest();
  	  try{
		      $curuser = $this->get('security.context')->getToken()->getUser();
		      //获取要设置的对象。暂时只有圈子和群组2种
		      $type = $request->get("type");
		      $action = $request->get("action");
		      $cur_account=$curuser->getUsername();
		      $re["returncode"] = ReturnCode::$SUCCESS;
		      if($type=="groupid")
		      {
		         $g = new \Justsy\BaseBundle\Management\GroupMgr($this->get('we_data_access'),$this->get('we_data_access_im'));
		         $g->setHint($request->get("id"),$curuser,$action);
		      }
		      else if($type=="circleid")
		      {
		         $c = new \Justsy\BaseBundle\Management\CircleMgr($this->get('we_data_access'),$this->get('we_data_access_im'),$request->get("id"));
		         $c->setHint($curuser,$action);         
		      }
		      else
		      {
		         $re["returncode"] = ReturnCode::$SYSERROR;
		         $re["msg"]="无效的类型";
		      }
      }
      catch(\Exception $e)
      {
      	    $re["returncode"] = ReturnCode::$SYSERROR;
          	$re["msg"]="系统错误";
      }
  		$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
  		$response->headers->set('Content-Type', 'text/json');
  		return $response;      
  }

  public function uploadheadimageorlogoAction(){
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();
    $dm = $this->get('doctrine.odm.mongodb.document_manager');
    $da = $this->get("we_data_access");
    // multipart/form-data
    $filepath = $_FILES['filepath']['tmp_name'];
    if(empty($filepath)){
        $filepath = tempnam(sys_get_temp_dir(), "we");
        unlink($filepath);
        $somecontent1 = base64_decode($request->get('filedata'));
        if ($handle = fopen($filepath, "w+")) {
          if (!fwrite($handle, $somecontent1) == FALSE) {   
              fclose($handle);  
          }
        }
    }
    $filepath_24 = $filepath."_24";
    $filepath_48 = $filepath."_48";

    try 
    {
      if (empty($filepath)) throw new \Exception("param is null");
      $im = new \Imagick($filepath);
      $im->scaleImage(48, 48);
      $im->writeImage($filepath_48);
      $im->destroy();
      $im = new \Imagick($filepath);
      $im->scaleImage(24, 24);
      $im->writeImage($filepath_24);
      $im->destroy();
      if (!empty($filepath)) $filepath = Utils::saveFile($filepath,$dm);
      if (!empty($filepath_48)) $filepath_48 = Utils::saveFile($filepath_48,$dm);
      if (!empty($filepath_24)) $filepath_24 = Utils::saveFile($filepath_24,$dm);
      $re["returncode"] = ReturnCode::$SUCCESS;
      $re["filepath"] = $filepath_48;
      $re["filepath_small"] = $filepath_24;
      $re["filepath_big"] = $filepath;
    }
    catch (\Exception $e) 
    {
      $re["returncode"] = ReturnCode::$SYSERROR;
      $this->get('logger')->err($e);
    }
    
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  //同步组织机构
  public function syncOrgAction()
  {
  	$re = array("returncode" => ReturnCode::$SUCCESS,'msg'=>'');
    $request = $this->getRequest();
    $da = $this->get("we_data_access");
    $da_im = $this->get('we_data_access_im');
    $user = $this->get('security.context')->getToken()->getUser();
    $eno=$user->eno;
    $login_account=$user->getUserName();
    $orgs=$request->get("orgjson");
    try{
    	$orgdata=array();
    	try{
    		$orgdata=json_decode($orgs,true);
	    		//获取现有组织机构
	    	$sql="select dept_id,parent_dept_id,dept_name,fafa_deptid,origin_deptid from we_department where eno=?";
	    	$params=array($eno);
	    	$ds=$da->Getdata('deps',$sql,$params);
	    	$depts=$ds['deps']['rows'];
	    	$sqls=array();
	    	$paras=array();
	    	//组织机构树
	    	$tree=$this->formatorg($orgs,'');
	    	$arr=$this->syncsql($tree,$depts,'v'.$eno,$eno,$login_account,$da,$da_im);
	    	$deptystr='';
	    	$deptnstr='';
	    	for($i=0;$i< count($arr['depty']);$i++)
	    	{
	    		$deptystr.="'".$arr['depty'][$i]."',";
	    		$deptnstr.="'".$arr['deptn'][$i]."',";
	    	}
	    	$deptystr=trim($deptystr,',');
	    	$deptnstr=trim($deptnstr,',');
	    	$arr['sqls'][]="delete from we_department where dept_id not in($deptystr) and eno=?";
	    	$arr['paras'][]=array($eno);
	    	$arr['sqls_m'][]="delete from im_dept_version where us in(SELECT loginname FROM we_im.im_employee a, im_base_dept b where a.deptid=b.deptid and b.path like ? )";
	    	$arr['paras_m'][]=array("/-10000/v".$eno."/%");
	    	$arr['sqls_m'][]="delete from im_base_dept where deptid not in($deptnstr) and locate(/-10000/v$eno/,path)=1";
	    	$arr['paras_m'][]=array();
	    	if($da->ExecSQLs($arr['sqls'],$arr['paras']))
	    	{
	    		if(!$da_im->ExecSQLs($arr['sqls_m'],$arr['paras_m']))
	    			$re = array("returncode" => ReturnCode::$SYSERROR,'msg'=>'同步失败');
	    		else{
	    		}
	    	}
	    	else{
	    		$re = array("returncode" => ReturnCode::$SYSERROR,'msg'=>'同步失败');
	    	}
    	}
    	catch(\Exception $e)
    	{
    		$re = array("returncode" => ReturnCode::$SYSERROR,'msg'=>'参数(orgjson)格式错误');
    	}
    }
    catch(\Exception $e)
    {
    	$re["returncode"] = ReturnCode::$SYSERROR;
    	$re["msg"]="系统错误";
      $this->get('logger')->err($e);
    }
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  private function syncsql($tree,$depts,$pid,$eno,$create_staff,$da,$da_im)
  {
  	$arr=array();
  	for($i=0;$i< count($tree);$i++)
  	{
  		$sqls=array();
  		$paras=array();
  		$sqls_m=array();
  		$paras_m=array();
  		$deptY=array();
  		$deptN=array();
  		$goin=false;
  		$npid='';
  		foreach($depts as $row)
  		{
  			if($tree[$i]['id']==$row['origin_deptid'])
  			{
  				$sqls[]="update we_department set dept_name=?,parent_dept_id=? where origin_deptid=? and eno=?";
  				$paras[]=array($tree[$i]['name'],$pid,$tree[$i]['id'],$eno);
  				$sqls_m[]="update im_base_dept set deptname=? where deptid=?";
  				$paras_m[]=array($tree[$i]['name'],$row['fafa_deptid']);
  				$goin=true;
  				$npid=$row['dept_id'];
  				array_push($deptY,$row['dept_id']);
  				array_push($deptN,$row['fafa_deptid']);
  				break;
  			}
  			if($tree[$i]['name']==$row['dept_name'] && $row['parent_dept_id']==$pid)
  			{
  				$sqls[]="update we_department set origin_deptid=? where dept_name=? and parent_dept_id=? and eno=?";
  				$paras[]=array($tree[$i]['id'],$tree[$i]['name'],$pid,$eno);
  				$goin=true;
  				$npid=$row['dept_id'];
  				array_push($deptY,$row['dept_id']);
  				array_push($deptN,$row['fafa_deptid']);
  				break;
  			}
  		}
  		if(!$goin){
  			$sqls[]="insert into we_department (create_staff,dept_id,dept_name,eno,fafa_deptid,origin_deptid,parent_dept_id) values(?,?,?,?,?,?,?)";
  			$deptid = SysSeq::GetSeqNextValue($da,"we_department","dept_id");
			  $fafa_deptid = SysSeq::GetSeqNextValue($da_im,"im_base_dept","deptid");
  			$paras[]=array($create_staff,$deptid,$tree[$i]['name'],$eno,$fafa_deptid,$tree[$i]['id'],$pid);
  			$sqls_m[]="insert im_base_dept(deptid, deptname, pid, path, noorder, manager, remark) 
select ?, ?, deptid, concat(path, '".$fafa_deptid."/'), (select count(*)+1 from im_base_dept where pid=?) noorder, null, null 
from im_base_dept 
where deptid=? ";
				$paras_m[]=array($fafa_deptid,$tree[$i]['name'],$fafa_deptid,$fafa_deptid);
				array_push($deptY,$deptid);
				array_push($deptY,$fafa_deptid);
				$npid=$deptid;
  		}
  		$arr['sqls']=$sqls;
  		$arr['paras']=$paras;
  		$arr['sqls_m']=$sqls_m;
  		$arr['paras_m']=$paras_m;
  		$arr['depty']=$deptY;
  		$arr['deptn']=$deptN;
  		if(isset($tree[$i]['childs'])){
  			$arr2=$this->syncsql($tree[$i]['childs'],$depts,$npid,$eno,$create_staff,$da,$da_im);
  			$arr['sqls']=array_merge($arr['sqls'],$arr2['sqls']);
  			$arr['paras']=array_merge($arr['paras'],$arr2['paras']);
  			$arr['sqls_m']=array_merge($arr['sqls_m'],$arr2['sqls_m']);
  			$arr['paras_m']=array_merge($arr['paras_m'],$arr2['paras_m']);
  			$arr['depty']=array_merge($arr['depty'],$arr2['depty']);
  			$arr['deptn']=array_merge($arr['deptn'],$arr2['deptn']);
  		}
  	}
  	return $arr;
  }
  private function formatorg($orgs,$pid)
  {
  	$arr=array();
  	for($i=0;$i< count($orgs);$i++)
  	{
  		if($orgs[$i]['pid']==$pid)
  		{
  			$arr[]=array('id'=>$orgs[$i]['id'],'name'=>$orgs[$i]['name'],'childs'=> $this->formatorg($orgs,$orgs[$i]['id']));
  		}
  	}
  	return $arr;
  }
  //查询圈子内的群组
  private function getGroupByCircle($circle_id, $username) 
  {
    $re = null;
    $da = $this->get('we_data_access');
    
    $sql = "select * from ( select a.group_id, a.circle_id, a.group_name ,'' applying
								from we_groups a, we_circle b, we_group_staff c
								where a.circle_id=b.circle_id 
								  and a.group_id=c.group_id
								  and a.circle_id=? 
								  and c.login_account=?
								limit 0, 1000 union
								select a.group_id, a.circle_id, a.group_name ,'1' applying
								from we_groups a, we_circle b, we_apply c
								where a.circle_id=b.circle_id 
								  and a.group_id=c.recv_id
								  and a.circle_id=? 
								  and c.account=?
								  and c.recv_type='g'
								  and c.is_valid='1' ) b order by convert(group_name USING gbk)
						";
        $params = array();
        $params[] = (string) $circle_id;
        $params[] = (string) $username;
        $params[] = (string) $circle_id;
        $params[] = (string) $username;
        $ds = $da->GetData("we_groups", $sql, $params);
    
        return $ds["we_groups"]["rows"];
  }
  //创建群组
  public function createGroupAction()
  {
  	$re = array("returncode" => ReturnCode::$SUCCESS,'msg'=>'');
    $request = $this->getRequest();
    $da = $this->get("we_data_access");
    $da_im = $this->get('we_data_access_im');
    $user = $this->get('security.context')->getToken()->getUser();
    $eno=$user->eno;
    $login_account=$user->getUserName();
    
    $circleId = $request->get('circleId');
    if(empty($circleId)){
    	//获取内部圈子
    	$sql="select circle_id from we_circle where enterprise_no=?";
    	$params=array($eno);
    	$ds=$da->Getdata('circle',$sql,$params);
    	$circleId=$ds['circle']['rows'][0]['circle_id'];
    }
    $isCreate = "0";
    $groups = $this->getGroupByCircle($circleId, $user->getUserName());
    $joinMethod = $request->get('radjoin');
    if(empty($joinMethod)){
    	$joinMethod='0';
    }
    $gname = $request->get('gname');
    $des = $request->get('des');
    $typeid = $request->get('classify');
    $typeid='';
    
    try{
    	//群组名称不能为空,加入方式验证
    	if($gname!='' || ($joinMethod!='' && preg_match("^/[0|1]$/",$joinMethod))){
    	//判断重名
    	$sql="select 1 from we_groups where group_name=?";
    	$params=array($gname);
    	$ds=$da->Getdata('gp',$sql,$params);
    	if($ds['gp']['recordcount']==0){
    		//根据typeid取出typename
    	$sql = "select typename from im_grouptype where typeid=?";
    	$ds = $da_im->GetData('im_grouptype',$sql,array($typeid));    
    	$typename='';
    	if($ds['im_grouptype']["recordcount"]>0){
    			$typename=$ds["im_grouptype"]['rows'][0]["typename"];
    	}
      //注册fafa_group
      $sqls = array(); $paras = array();
      $fafa_groupid = SysSeq::GetSeqNextValue($da_im,"im_group","groupid");
      $sqls[] = "insert into im_group (groupid, groupname, groupclass, groupdesc, creator, add_member_method, accessright) 
        values (?, ?, ?, ?, ?, ?, 'any')";
      $paras[] = array(
        (string)$fafa_groupid,
        (string)$gname,
        (string)$typename,
        (string)$des,
        (string)$user->fafa_jid,
        (string)$joinMethod
      );
      $sqls[] = "insert into im_groupemployee (employeeid, groupid, employeenick, grouprole) values (?,?,?,'owner')";
      $paras[] = array((string)$user->fafa_jid,(string)$fafa_groupid,(string)$user->nick_name);
      //跟新群组版本号
      $sqls[] = "delete from im_group_version where us=?";
      $paras[] = array((string)$user->fafa_jid);
      $da_im->ExecSQLs($sqls,$paras);
      //保存图标
      $fileid = '';
      //$fileid = $session->get("group_logo_id");
      //if (!empty($filename120)) $fileid = Utils::saveFile($filename120,$this->get('doctrine.odm.mongodb.document_manager'));
      //保存
      $sqls = array(); $paras = array();
      $groupId = SysSeq::GetSeqNextValue($da,"we_groups","group_id");
      $sqls[] = "insert into we_groups (group_id,circle_id,group_name,group_desc,group_photo_path,
        join_method,create_staff,fafa_groupid,create_date, group_class)
        values (?,?,?,?,?,?,?,?,now(),?)";
      $paras[] = array((string)$groupId,
        (string)$circleId,
        (string)$gname,
        (string)$des,
        (string)$fileid,
        (string)$joinMethod,
        (string)$user->getUserName(),
        (string)$fafa_groupid,
        (string)$typeid);
      $sqls[] = "insert into we_group_staff (group_id,login_account) values (?,?)";
      $paras[] = array((string)$groupId,(string)$user->getUserName());
      $da->ExecSQLs($sqls,$paras);
      //创建文档根目录
        $docCtl = new \Justsy\BaseBundle\Controller\DocumentMgrController();
        $docCtl->setContainer($this->container);
        if($docCtl->createDir("g".$groupId,"c".$circleId,$gname,$circleId)>0)
        {
        	$docCtl->saveShare("g".$groupId,"0",$groupId,"g","w");//将群目录共享给该群组成员
        }
    	}
    	else{
    		$re["returncode"] = ReturnCode::$OTHERERROR;
    		$re["msg"]="群组名称重复";
    	}
    	}
    	else{
    		$re["returncode"] = ReturnCode::$OTHERERROR;
    		$re["msg"]="参数不能为空或格式错误";
    	}
    }
    catch(\Exception $e)
    {
    		$re["returncode"] = ReturnCode::$SYSERROR;
    		$re["msg"]="系统错误";
      	$this->get('logger')->err($e);
    }
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		$response->headers->set('Content-Type', 'text/json');
		return $response;
  }
  //添加群组成员
  public function addGroupUserAction()
  {
  	$re = array("returncode" => ReturnCode::$SUCCESS,'msg'=>'');
    $request = $this->getRequest();
    $da = $this->get("we_data_access");
    $da_im = $this->get('we_data_access_im');
    $user = $this->get('security.context')->getToken()->getUser();
    $eno=$user->eno;
    $gname=$request->get("gname");
    $userlist=$request->get("userlist");
    try{
    	//查询群组
    	$sql="select group_id,group_name from we_groups where group_name=?";
    	$params=array($gname);
    	$ds=$da->Getdata('group',$sql,$params);
    	if($ds['group']['recordcount']>0){
    		$gid=$ds['group']['rows'][0]['group_id'];
    	}
    	if(!empty($gid)){
	    		//判断群组中是否有人
	    	$isEmpty=false;
	    	$sql = "select count(1) as cnt from we_group_staff where group_id=?";
	      $ds = $da->GetData('we_group_staff',$sql,array($gid));
	      if ($ds && $ds['we_group_staff']['rows'][0]['cnt']==0)
	      {
	      	$isEmpty=true;
	      }
	      $sqls=array();
	      $paras=array();
	    	$openids=explode(',',$userlist);
				$n=0;
				$str='';
				$firstp='';
	    	for($i=0;$i< count($openids);$i++)
	    	{
	    		if($n<=256)
	    		{
	    			$str.="'".$openids[$i]."',";
	    			$n++;
	    		}
	    		if($n==256 || $i== count($openids)-1)
	    		{
	    			$str=trim($str,',');
	    			$sql="select a.login_account,a.fafa_jid,a.openid from we_staff a where a.login_account in($str) and not exists(select 1 from we_group_staff b where b.group_id=? and b.login_account=a.login_account)";
	    			$params=array($gid);
	    			$ds=$da->Getdata('staffs',$sql,$params);
	    			$staffs=$ds['staffs']['rows'];
	    			if(empty($firstp) && count($staffs)>0)$firstp=$staffs[0]['login_account'];
	    			foreach($staffs as $row)
	    			{
	    				$sqls[]="insert into we_group_staff (group_id,login_account) values (?,?)";
	    				$paras[]=array($gid,$row['login_account']);
	    			}
	    			$n=0;
	    			$str='';
	    		}
	    	}
	    	if($isEmpty){
	    		//设置管理员
	    		$sqls[]="update we_groups set create_staff=? where group_id=?";
	    		$paras[]=array($firstp,$gid);
	    	}
	    	if(!$da->ExecSQLs($sqls,$paras)){
	    		$re = array("returncode" => ReturnCode::$SYSERROR,'msg'=>'同步失败');
	    	}
    	}
    	else{
    		$re["returncode"] = ReturnCode::$OTHERERROR;
    		$re["msg"]="未找到指定群组";
    	}
    }
    catch(\Exception $e)
    {
    	$re["returncode"] = ReturnCode::$SYSERROR;
    	$re["msg"]="系统错误";
     	$this->get('logger')->err($e);
    }
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		$response->headers->set('Content-Type', 'text/json');
		return $response;
  }
  public function getFriendOpenidListAction()
  {
  	$re = array("returncode" => ReturnCode::$SUCCESS,'msg'=>'');
    $request = $this->getRequest();
    $da = $this->get("we_data_access");
    $da_im = $this->get('we_data_access_im');
    $user = $this->get('security.context')->getToken()->getUser();
    
    try{
    	$sql="select c.openid from we_staff_atten a inner join we_staff c on c.login_account=a.atten_id where a.login_account=? and exists(select 1 from we_staff_atten b where b.login_account=a.atten_id and b.atten_id=a.login_account)";
	    $params=array($user->getUserName());
	    $ds=$da->Getdata('info',$sql,$params);
	    $rows=$ds['info']['rows'];
	    $openids='';
	    foreach($rows as $row){
	    	$openids.=','.$row["openid"];	
	    }
	    $openids=trim($openids,',');
	    $re["list"]=$openids;
    }
    catch(\Exception $e){
    	$re["returncode"] = ReturnCode::$SYSERROR;
    	$re["msg"]="系统错误";
     	$this->get('logger')->err($e);
    }
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		$response->headers->set('Content-Type', 'text/json');
		return $response;
  }
  public function getFansOpenidListAction()
  {
  	$re = array("returncode" => ReturnCode::$SUCCESS,'msg'=>'');
    $request = $this->getRequest();
    $da = $this->get("we_data_access");
    $da_im = $this->get('we_data_access_im');
    $user = $this->get('security.context')->getToken()->getUser();
    try{
    	$sql="select c.openid from we_staff_atten a inner join we_staff c on c.login_account=a.login_account where a.atten_id=?";
	    $params=array($user->getUserName());
	    $ds=$da->Getdata('info',$sql,$params);
	    $rows=$ds['info']['rows'];
	    $openids='';
	    foreach($rows as $row){
	    	$openids.=','.$row["openid"];	
	    }
	    $openids=trim($openids,',');
	    $re["list"]=$openids;
    }
    catch(\Exception $e){
    	$re["returncode"] = ReturnCode::$SYSERROR;
    	$re["msg"]="系统错误";
     	$this->get('logger')->err($e);
    }
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		$response->headers->set('Content-Type', 'text/json');
		return $response;
  }
  public function getAttenListAction()
  {
  	$re = array("returncode" => ReturnCode::$SUCCESS,'msg'=>'');
    $request = $this->getRequest();
    $da = $this->get("we_data_access");
    $da_im = $this->get('we_data_access_im');
    $user = $this->get('security.context')->getToken()->getUser();
    try{
    	$sql="select c.openid from we_staff_atten a inner join we_staff c on c.login_account=a.atten_id where a.login_account=?";
	    $params=array($user->getUserName());
	    $ds=$da->Getdata('info',$sql,$params);
	    $rows=$ds['info']['rows'];
	    $openids='';
	    foreach($rows as $row){
	    	$openids.=','.$row["openid"];	
	    }
	    $openids=trim($openids,',');
	    $re["list"]=$openids;
    }
    catch(\Exception $e){
    	$re["returncode"] = ReturnCode::$SYSERROR;
    	$re["msg"]="系统错误";
     	$this->get('logger')->err($e);
    }
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		$response->headers->set('Content-Type', 'text/json');
		return $response;
  }


  //推送分享消息
  public function sendsharemsgAction()
  {
      $da = $this->get("we_data_access");
      $da_im = $this->get("we_data_access_im");
	  	$re = array("returncode" => ReturnCode::$SUCCESS,'msg'=>'');
	    $res = $this->getRequest();
	    $user = $this->get('security.context')->getToken()->getUser();
	    try{
	    	 //获取接收者
	    	 $openids = $res->get("openids");
	    	 $groupid = $res->get("groupid");
	    	 $circleid = $res->get("circleid");//分享到指定的圈子中，需要单独 处理。不走实时消息通道	    	 
	    	 if(empty($openids) && empty($groupid) && empty($circleid))
	    	 {
	    	 	$toType = $res->get("totype"); //分享目标类型，当openids\groupid\circleid存在时无效
	    	 	//分享到其他网站或者平台上,暂时支持微信朋友圈\QQ空间
	    	 	$re = "";
	   			$response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
				  $response->headers->set('Content-Type', 'text/json');
				  return $response;	    	 	
	    	 }
	    	 //获取推送的分享图片地址
	    	 $imgurl = $res->get("imgurl");
	    	 //获取推送的分享内容
	    	 $content = $res->get("content");
	    	 $shareitem = array();
	    	 $shareitem["content"] = $content;
	    	 if(!empty($imgurl))
	    	 {
	    	 	  $shareitem["image"] = array("value"=>$imgurl,"type"=>"URL");
	    	 }
	    	 $shareitem["iosclass"] = $res->get("iosclass");
	    	 $shareitem["androidclass"] = $res->get("androidclass");
	    	 $shareitem["bizdata"] = $res->get("bizdata");
         if(!empty($circleid))
         {
            $ref_url = json_encode(array(
               "iosclass"=>$shareitem["iosclass"],
               "androidclass"=>$shareitem["androidclass"],
               "bizdata"=>$shareitem["bizdata"]
            ));
            //分享到圈子  
            $conv_id = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_convers_list", "conv_id");
            $conv = new \Justsy\BaseBundle\Business\Conv();
            $conv->newShareTrend($da, $user->getUserName(), $conv_id,$content, $imgurl, $circleid, "ALL", $ref_url, array(), "00",null) ;
            $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
            $response->headers->set('Content-Type', 'text/json');
            return $response;             
         }

	    	 $msgBody = array();
	    	 $msgBody["snssharemsg"] =array("shareitem" => $shareitem);
	    	 $tolist = array();
	    	 
	    	 if(!empty($groupid ))
	    	 {          
          $groupmgr = new \Justsy\BaseBundle\Management\GroupMgr($da,$da_im);
          //$groupdata = $groupmgr->GetByIM($groupid);
	    	 	$tolist = $groupmgr->getGroupMembersJidByIM($groupid);
          $msgBody["snssharemsg"]["shareitem"]["groupid"] = $groupid;
	    	}
	    	if(!empty($openids))
	    	 {
	    	 	$tolist = array_merge($tolist,explode(",", $openids));
	    	 }
	    	$cnt = count($tolist);
	    	if($cnt>0)
	    	{
	    	 	$api = new \Justsy\OpenAPIBundle\Controller\ApiController();
	    	 	$api->setContainer($this->container);
	    	 	$re = $api->sendMsg2($user->fafa_jid,implode(",", $tolist),json_encode($msgBody),"sharemsg",true);
	    	}
	    }
	    catch(\Exception $e){
	    	$re["returncode"] = ReturnCode::$SYSERROR;
	    	$re["msg"]="系统错误";
	     	$this->get('logger')->err($e);
	    }
	    $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		$response->headers->set('Content-Type', 'text/json');
		return $response;    
  }

  //删除用户账号
  public function deletestaffAction()
  {
     $da = $this->get("we_data_access");
     $da_im = $this->get('we_data_access_im');
  	 $request = $this->getRequest();
  	 $ldap_uid = $request->get("ldap_uid");
  	 $fafa_jid = "";$dept_id = "";
  	 $returncode = ReturnCode::$SUCCESS;$msg="";
  	 $sql = "select login_account,fafa_jid from we_staff where ldap_uid=?";
  	 try
  	 {
	  	 $ds = $da->GetData("staff",$sql,array((string)$ldap_uid));
	  	 if ($ds && $ds["staff"]["recordcount"]>0){
	  	   $fafa_jid = $ds["staff"]["rows"][0]["fafa_jid"];
	  	   $login_account = $ds["staff"]["rows"][0]["login_account"];
	  	   //删除we_sns库
		  	 $sql = "call p_deluser(?)";
		  	 $para = array((string)$login_account);
		  	 try
		  	 {
		  	 	  $da->ExecSQL($sql,$para);		  	 	  
		  	 	  //获得deptid字段
		  	 	  
		  	 	  $sql_im = "select deptid from im_employee where loginname=?;";
		  	 	  $ds = $da_im->GetData("dept",$sql_im,array((string)$fafa_jid));
		  	 	  if ($ds && $ds["dept"]["recordcount"]>0){
		  	 	  	$dept_id = $ds["dept"]["rows"][0]["deptid"];
		  	 	  }
		  	 	  //删除we_im库
		  	    if (!empty($fafa_jid)){
			  	    $sql_im = "call p_deluser_im(?)";
			  	    $para = array((string)$fafa_jid);
			  	    $da_im->ExecSQL($sql_im,$para);
		  	    }
		  	    //从新统计部门人员
		  	    if (!empty($dept_id)){
		  	    	try{
			  	    	$sql_im="call dept_emp_stat(?);";
			  	    	$para = array((string)$dept_id);
			  	    	$da_im->ExecSQL($sql_im,$para);
			  	    }
			  	    catch(\Exception $e){
			  	    	
			  	    }
		  	    }
		  	 }
		  	 catch(\Exception $e){
		  	 	 $this->get("logger")->err($e->getMessage());
		  	 	 $returncode = ReturnCode::$SYSERROR;
		  	 	 $msg = "删除用户失败！";
		  	 }
	  	 }
	  	 else{
	  	 	 $returncode = ReturnCode::$SYSERROR;
	  	 	 $msg = "用户账号不存在";	  	 	 
	  	 }
  	 }
  	 catch (\Exception $e){
  	 	 $this->get("logger")->err($e->getMessage());
  	 }  	
  	 $result = array("returncode"=>$returncode,"msg"=>$msg,"dept_id"=>$dept_id);
     $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;
  } 

  //根据指定的帐号属性获取指定帐号的其他指定属性
  //如根据jid获取对应的人员的帐号、昵称等
  public function getAttrlistByAttrAction()
  {
    $re = array("returncode" => ReturnCode::$SUCCESS,'msg'=>'','list'=>array());
    $request = $this->getRequest();
    $da = $this->get("we_data_access");
    $da_im = $this->get('we_data_access_im');
    $user = $this->get('security.context')->getToken()->getUser();
    $bytype= $request->get("bytype");//获取是根据用户的什么属性查询，支持login_account,ldap_uid,mobile_bind,openid默认是根据login_account查
    $list = $request->get("list");//查询的人员列表，多个人员时用,间隔
    $getattrs = $request->get("returnattrs");//默认返回人员的jid属性
    if(empty($getattrs))
    {
      $getattrs = "fafa_jid";
    }
    if(empty($list))
    {
        $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }
    if(empty($bytype))
    {
       $bytype = "login_account";
    }

    try{
      $getattrs = str_replace($bytype, "", $getattrs);
      $list = str_replace(",", "','", $list);
      $sql="select ".$getattrs.",".$bytype." from we_staff a  where a.".$bytype." in('".$list."')";
      $ds = $da->getData("t",$sql,array());
      
      $re["list"]= $ds["t"]["rows"];
    }
    catch(\Exception $e){
      $re["returncode"] = ReturnCode::$SYSERROR;
      $re["msg"]="系统错误";
      $this->get('logger')->err($e);
    }
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  } 
}