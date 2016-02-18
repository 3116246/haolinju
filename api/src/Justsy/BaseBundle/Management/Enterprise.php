<?php
namespace Justsy\BaseBundle\Management;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Management\Identify;
use Justsy\BaseBundle\Common\Cache_Enterprise;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Rbac\StaffRole;

class Enterprise implements IBusObject
{
	protected $logger;
	protected $da;
	protected $da_im;
	protected $container;
	public static $code='04';
	public static $mirocode='05';
	public function __construct($dataaccess,$log='',$v_containter='')
	{
		$this->logger=$log;
		$this->da=$dataaccess;
		$this->db_im = $v_containter->get("we_data_access_im");
		$this->container=$v_containter;
	}

	public function getInstance($container)
	{
	  	$db = $container->get("we_data_access");	  	
	  	$logger = $container->get("logger");
	  	//$token = $container->get('security.context')->getToken();

	  	return new self($db,$logger,$container);
	}	

	public function cache_flush_all($paraObj)
	{
		$r = Cache_Enterprise::flush($this->container);
		return Utils::WrapResultOK($r);
	}

	public function cache_getStat($paraObj)
	{
		$r = Cache_Enterprise::stat($this->container);
		return Utils::WrapResultOK($r);
	}

	public function cache_getItem($paraObj)
	{
		$key = $paraObj['cachekey'];
		$r = Cache_Enterprise::get('',$key,$this->container);
		return Utils::WrapResultOK($r);
	}

	public function cache_clearItem($paraObj)
	{
		$key = $paraObj['cachekey'];
		Cache_Enterprise::delete('',$key,$this->container);
		return Utils::WrapResultOK('');
	}


    //获取当前企业的用户认证方式
	public function getUserAuth($eno='')
	{
		try
		{
			$data=Cache_Enterprise::get(Cache_Enterprise::$EN_USER_AUTH,$eno,$this->container);
		}
		catch(\Exception $e)
		{
			$this->logger->err($e);
			$data=null;
		}
		if(empty($data))
		{
			$ssoauthldap = "";
			$ssoauthurl = "";
			$ssoauthdb_conn = "";
			try{
				$ssoauthmodule = $this->container->getParameter("ssoauthmodule");
			}
			catch(\Exception $e)
			{
				$this->logger->err($e);
				$ssoauthmodule = "WefafaAuth";
			}
			if(empty($ssoauthmodule))
				$ssoauthmodule = "WefafaAuth";
			try{
				$ssoauthldap   = $this->container->getParameter("ssoauthldap");
			}
			catch(\Exception $e){}
			try{
				$ssoauthurl   = $this->container->getParameter("ssoauthurl");
			}
			catch(\Exception $e){}
			try{
				$ssoauthdb_conn   = $this->container->getParameter("ssoauthdb_conn");
			}
			catch(\Exception $e){}					
			$data = array(
				"ssoauthmodule" => $ssoauthmodule,
				"ssoauthurl"=> $ssoauthurl,
				"ssoauthdb_conn" => $ssoauthdb_conn,
				"ssoauthldap"=> $ssoauthldap 
			);
		    Cache_Enterprise::set(Cache_Enterprise::$EN_USER_AUTH,$eno,json_encode($data),0,$this->container);
		}
		else
		{
			return json_decode($data,true);
		}
	    return $data;		
	}

	public function refresh($eno)
	{
		Cache_Enterprise::delete(Cache_Enterprise::$EN_INFO,$eno,$this->container);
		return $this->getInfo($eno);
	}

	public function getinfo2($paraObj)
	{
		$eno  =$paraObj["eno"];
		return $this->getInfo($eno);
	}

	public function getManager($paraObj)
	{
		$eno = $paraObj['user']->eno;
		$data=$this->getInfo($eno);
		if(empty($data))
		{
			return Utils::WrapResultError("无效的企业信息");
		}
		$result=array();
		$managers = $data['sys_manager'];
		$managers =explode(';', $managers) ;
		$staffMgr = new Staff($this->da,$this->db_im,$paraObj['user'],$this->logger,$this->container);
		foreach ($managers as $key => $value) {
			$result[] = $staffMgr->getStaffInfo($value);
		}
		return Utils::WrapResultOK($result);
	}

	public function delManager($paraObj)
	{
		$eno = $paraObj['user']->eno;
		$data=$this->getInfo($eno);
		if(empty($data))
		{
			return Utils::WrapResultError("无效的企业信息");
		}
		$staff = $paraObj['staff'];
		if(empty($staff))
		{
			return Utils::WrapResultError("未指定要移除的管理员");
		}
		$managers =explode(';', $data['sys_manager']) ;
		foreach ($managers as $key => $value) {
			if($value==$staff) unset($managers[$key]);
		}
		$managers = implode(';', $managers);
		$sql='update we_enterprise set sys_manager=? where eno=?';
		$this->da->ExecSQL($sql,array((string)$managers,(string)$eno));
		$this->getInfo($eno,true);
		return Utils::WrapResultOK("");
	}

	public function saveManager($paraObj)
	{
		$eno = $paraObj['user']->eno;
		$data=$this->getInfo($eno);
		if(empty($data))
		{
			return Utils::WrapResultError("无效的企业信息");
		}
		$staff = $paraObj['staff'];
		if(empty($staff))
		{
			return Utils::WrapResultError("未指定要添加的管理员");
		}
		$staff = explode(',', $staff);
		$managers =explode(';', $data['sys_manager']) ;
		$managers = array_merge($managers,$staff);
		$managers = implode(';', $managers);
		$sql='update we_enterprise set sys_manager=? where eno=?';
		$this->da->ExecSQL($sql,array((string)$managers,(string)$eno));
		$this->getInfo($eno,true);
		return Utils::WrapResultOK("");
	}

	public function getInfo($eno,$refresh=false)
	{
		try
		{
			$data=Cache_Enterprise::get(Cache_Enterprise::$EN_INFO,$eno,$this->container);
		}
		catch(\Exception $e)
		{
			$this->logger->err($e);
			$data=null;
		}
		if(empty($data) ||$refresh)
		{
			$photo_url=$this->container->getParameter('FILE_WEBSERVER_URL');
			$sql=" select a.*,concat('$photo_url',ifnull(a.logo_path,'')) logo_path,wc.circle_id,wc.circle_name from we_enterprise a ".
			     " left join we_circle wc on a.eno=wc.enterprise_no and a.edomain=wc.network_domain where a.eno=?";
		    $params=array((string)$eno);
		    $ds=$this->da->Getdata('main',$sql,$params);
		    $data = empty($ds)|| count($ds["main"]["rows"])==0? null : $ds["main"]["rows"][0];
		    Cache_Enterprise::set(Cache_Enterprise::$EN_INFO,$eno,json_encode($data),0,$this->container);
		}
		else
		{
			return json_decode($data,true);
		}
	    return $data;
	}

	public function IsManager($paraObj)
	{
		$eno = $paraObj['user']->eno;
		$data=$this->getInfo($eno);
		if(empty($data))
		{
			return Utils::WrapResultError("无效的企业信息");
		}
		$manager = explode(';',  $data['sys_manager']);
		$manager[]= $data['create_staff'];
		$re = in_array($paraObj['user']->getUsername(), $manager);
		return Utils::WrapResultOK($re);
	}

	public function getInfoByName($name)
	{
		try
		{
			$data=Cache_Enterprise::get(Cache_Enterprise::$EN_INFO,md5($name),$this->container);
		}
		catch(\Exception $e)
		{
			$this->logger->err($e);
			$data=null;
		}
		if(empty($data) || $data=="null")
		{ 
			$photo_url=$this->container->getParameter('FILE_WEBSERVER_URL');
			$sql=" select a.*,concat('$photo_url',ifnull(a.logo_path,'')) logo_path,wc.circle_id,wc.circle_name from we_enterprise a ".
			     " left join we_circle wc on a.eno=wc.enterprise_no and a.edomain=wc.network_domain where a.enoname=?";
		    $params=array((string)$name);
		    $ds=$this->da->Getdata('main',$sql,$params);
		    $data = empty($ds)||count($ds["main"]["rows"])==0? null : $ds["main"]["rows"][0];
		    Cache_Enterprise::set(Cache_Enterprise::$EN_INFO,md5($name),json_encode($data),0,$this->container);
		}
		else
		{
			return json_decode($data,true);
		}
	    return $data;	    
	}
	public function attenEno($paraObj)
	{
		$login_account =isset($paraObj["login_account"]) ? $paraObj["login_account"] : $paraObj["user"]->getUsername();
		$eno = $paraObj["eno"];
		try{
			//关注默认公众号
			$account = "_wexin_{$eno}@fafatime.com";
			//取出默认公众号
			$sql1="select number from we_micro_account where eno=? and number=?";
			$params1=array((string)$eno,(string)$account);
			$ds=$this->da->Getdata('account',$sql1,$params1);
			if($ds['account']['recordcount']>0)
			{
				$sql[]="insert into we_staff_atten (login_account,atten_type,atten_id,atten_date) values(?,?,?,now())";
				$params[]=array($login_account,"01",$account);
			}
			else
			{
				 //未找到公众号时，直接关注该企业，
				 //注：当注册成功时，需要将这些关注数据转换为关注对应的默认公众号
			   $sql[]="insert into we_staff_atten (login_account,atten_type,atten_id,atten_date) values(?,?,?,now())";
			   $params[]=array($login_account,self::$code,$eno);				
			}
			if(!$this->da->ExecSQLs($sql,$params))
				return false;
			$friendevent=new \Justsy\BaseBundle\Management\FriendEvent($this->da,$this->logger,$this->container);
      		$friendevent->atteneno($login_account,$eno);
			return true;
		}
		catch(\Exception $e)
		{
			$this->writelog($e);
			return false;
		}
	}
	public function cancelatten($paraObj)
	{
		$login_account =isset($paraObj["login_account"]) ? $paraObj["login_account"] : $paraObj["user"]->getUsername();
		$eno = $paraObj["eno"];

		$number = "_weixin_{$eno}@fafatime.com";
		$sqls=array();
		$params=array();
		$sqls[]="delete from we_staff_atten where login_account=? and atten_id in (?,?) ";
		$params[]=array((string)$login_account,(string)$number,(string)$eno);
		$sqls[]="delete from we_micro_account_group_re where login_account=? and micro_account=?";
		$params[]=array($login_account,$number);
		if(!$this->da->ExecSQLs($sqls,$params))
			return false;
		return true;
	}
	//获取关注列表
	public function getAtten($eno)
	{
		  $sql1="select number from we_micro_account where eno=? and number like '_weixin_%' order by id asc limit 0,1";
			$params1=array($eno);
			$ds=$this->da->Getdata('account',$sql1,$params1);
			$sql = "";
			$para = array();
			if($ds['account']['recordcount']>0)
			{
			    $sql="select a.login_account,a.nick_name,ifnull(a.photo_path_small,'') as logo from we_staff a,we_staff_atten b where a.login_account=b.login_account and b.atten_id=? and atten_type=? limit 0,5";
			    $para[]=(string)$ds['account']["rows"][0]["number"];
			    $para[]=(string)self::$mirocode;
			}
			else
			{
			    $sql="select a.login_account,a.nick_name,ifnull(a.photo_path_small,'') as logo from we_staff a,we_staff_atten b where a.login_account=b.login_account and b.atten_id=? and atten_type=?  limit 0,5";
			    $para[]=(string)$eno;
			    $para[]=(string)self::$code;
			}
			$datarows = $this->da->Getdata('dt',$sql,$para);
			return $datarows["dt"]["rows"];
	}

	//统计指定企业的粉丝数、应用数等
	public function statis($paraObj)
	{
		$login_account = $paraObj["user"]->getUsername();
		$eno = $paraObj["eno"];
		$endata = $this->getInfo($eno);
		$account = "_wexin_{$eno}@fafatime.com";
		//粉丝总数
		$sql1 = "select count(1) fanscount from we_staff_atten where atten_id in(?,?)";
		//已发布的应用总数
		$sql2 = "select count(1) appcount from app_mall_publish a ,we_appcenter_apps b where a.appid=b.appid and b.appdeveloper=?";
		//当前登录 人的关注状态
		$sql3 = "select count(1) atten from we_staff_atten where atten_id in(?,?) and login_account=?";

		$sql = "select '".$endata["logo_path"]."' logo, '".$eno."' eno,'".$endata["ename"]."' ename, ({$sql1}) fanscount,({$sql2}) appcount,({$sql3}) atten from dual";
		$ds=$this->da->Getdata("info",
				$sql,
				array((string)$account,(string)$eno,(string)$eno,(string)$account,(string)$eno,(string)$login_account)
			);
		return $ds["info"]["rows"];
	}
	
	public function search($where,$params)
	{
		try{
			$photo_url=$this->container->getParameter('FILE_WEBSERVER_URL');
			$sql="select a.eno,a.ename,a.eshortname,b.classify_name,
				a.eno_level,a.vip_level,a.edomain,a.create_staff,a.sys_manager,a.mstyle,a.ewww,a.addr,a.telephone,a.fax,
				(select c.classify_name from we_industry_class c where c.classify_id=b.parent_classify_id) as classify_parent_name,
				concat('$photo_url',ifnull(a.logo_path,'')) as logo_path,
				a.total_point from we_enterprise a
				left join we_industry_class b on a.industry=b.classify_id where 1=1";
		$param=array();
		$ds=$this->da->Getdata("info",$sql.$where,array_merge($param,$params));
		return $ds['info']['rows'];
		}
		catch(\Exception $e)
		{
			$this->writelog($e);
			return array();
		}
	}

	//支持移动端使用统一数据接口进行模糊搜索
	public function search2($params)
	{
		$ename= $params["name"];
		if(empty($ename))
		{
			return Utils::WrapResultOK("");
		}
		try{
			$photo_url=$this->container->getParameter('FILE_WEBSERVER_URL');
			$sql="select a.eno,a.ename,a.eshortname,b.classify_name,
				a.eno_level,a.vip_level,a.edomain,a.create_staff,a.sys_manager,a.mstyle,a.ewww,a.addr,a.telephone,a.fax,
				(select c.classify_name from we_industry_class c where c.classify_id=b.parent_classify_id) as classify_parent_name,
				concat('$photo_url',ifnull(a.logo_path,'')) as logo_path,
				a.total_point from we_enterprise a
				left join we_industry_class b on a.industry=b.classify_id where a.ename like concat('%',?,'%') limit 0,20";
			$ds=$this->da->Getdata('info',$sql,array((string)$ename));
			return Utils::WrapResultOK($ds['info']['rows']);
		}
		catch(\Exception $e)
		{
			$this->writelog($e);
			return Utils::WrapResultError($e->getMessage());
		}
	}

	public function getInfoByEno($login_account,$eno)
	{
		try{
			$photo_url=$this->container->getParameter('FILE_WEBSERVER_URL');
			$sql="select a.id,a.eno,a.enoname ename,a.eshortname,b.classify_name,
			(select province from we_province where provinceID=a.province) province,
			(select city from we_city where cityid= a.city) city,
			(select area from we_area where areaid=a.area) area,
			(select c.classify_name from we_industry_class c where c.classify_id=b.parent_classify_id) as classify_parent_name,
			a.eno_address addr,a.eno_website ewww,concat('$photo_url',ifnull(a.eno_logo_path_big,'')) as logo,
			a.eno_phone,we.total_point,concat(we.eno_level,we.vip_level) as vip,
			(select count(1) from we_staff d where d.eno=a.eno and d.auth_level<>? and state_id='1') as staff_num,
			(select e.circle_recommend from we_circle e where e.circle_id=a.eno) as eno_recommend,
			f.nick_name,we.create_staff,we.create_date,a.eno_introduction edesc,case when g.login_account is null then '0' else '1' end eno_atten from we_enterprise_stored a 
			left join we_enterprise we on a.eno=we.eno 
			left join we_industry_class b on a.eno_trade=b.classify_id 
			left join we_staff f on f.login_account=we.create_staff 
			left join we_staff_atten g on g.atten_id=a.id and g.atten_type=? and g.login_account=?  where a.id=?";
			$params=array(Identify::$SIdent,$login_account,self::$code,$eno);
			$ds=$this->da->Getdata('info',$sql,$params);
			if($ds['info']['recordcount']>0)
			{
				return $ds['info']['rows'][0];
			}
			else
				return array();
		}
		catch(\Exception $e)
		{
			//var_dump($e->getMessage());
			$this->writelog($e);
			return array();
		}
	}

	//生成加入企业邀请码
	public function getinvitecode($paraObj)
	{
	    $currUser = $paraObj["user"];
	    if(empty($currUser))
	    {
	        return Utils::WrapResultError("请登录后重试",ReturnCode::$NOTLOGIN);
	    }
	    $account = $currUser->getUsername();
	    $eno = $currUser->eno;
	    $code = $eno.rand(1000,9999);
	    $code = substr($code,-6);
	    //清除之前生成的邀请码
	    $sql =array("delete from we_register where eno=? and review_staff=?");
	    $para=array(array((string)$eno,(string)$account));
	    //生成邀请码
	    $sql[]= "insert into we_register(login_account,active_code,register_date,review_staff,eno,email_type)values(?,?,now(),?,?,'invitecode')";
		$para[]=array((string)$code,(string)$code,(string)$account,(string)$eno);
		$this->da->ExecSQLs($sql,$para);
		return Utils::WrapResultOK($code);
	}
	//生成扫一扫加入企业地址
	//手机端应根据该地址生成二维码
	public function getscanurl($paraObj)
	{
	    $currUser = $paraObj["user"];
	    if(empty($currUser))
	    {
	        return Utils::WrapResultError("请登录后重试",ReturnCode::$NOTLOGIN);
	    }
	    $codereuslt = $this->getinvitecode($paraObj);
	    if($codereuslt["returncode"]=="0000")
	    {
	    	$code = $codereuslt["data"];
	    	$url = $this->container->getParameter('open_api_url')."/interface/dataaccess?module=enterprise&action=joinbycode&params={\"invitecode\":\"".$code."\"}";
	    	return Utils::WrapResultOK($url);
	    }
	    else
	    	return $codereuslt;
	}
	//通过邀请码加入企业
	public function joinbycode($paraObj)
	{
	    $currUser = $paraObj["user"];
	    if(empty($currUser))
	    {
	        return Utils::WrapResultError("请登录后重试",ReturnCode::$NOTLOGIN);
	    }
		if($currUser->eno!=Utils::$PUBLIC_ENO)
		{
			return Utils::WrapResultError("你已加入企业");
		}	    
	    $invitecode = $paraObj["invitecode"];
	    if(empty($invitecode))
	    {
	    	return Utils::WrapResultError("邀请码不能为空");
	    }
	    //获取设置的邀请码过期小时数,默认为1小时
	    //企业参数名称：invitecode_expire_hour
	    $sys = new \Justsy\BaseBundle\DataAccess\SysParam($this->container);
	    $hour = $sys->GetSysParam("invitecode_expire_hour");
	    if(empty($hour))
	    	$hour = 1;
	    $sql = "select eno,review_staff from we_register where active_code=? and register_date>date_sub(now(),interval ".$hour." hour)";
		$dataset = $this->da->GetData("t",$sql,array((string)$invitecode));
		if(count($dataset["t"]["rows"])>0)
		{
			$eno = $dataset["t"]["rows"][0]["eno"];
			if(empty($eno))
			{
				return Utils::WrapResultError("邀请码已过期");
			}
			if($currUser->eno==$eno)
			{
				return Utils::WrapResultError("你已经是该企业员工");
			}
		    //消息通知 
			
	        $message = "你已成功加入企业";
			Utils::sendImPresence("",$currUser->fafa_jid ,"enterprise_joinagree",$message,$this->container,"","",false,'','0');
						
			//加入企业
			$jid = SysSeq::GetSeqNextValue($this->da,"we_staff","fafa_jid");
		    $jid .= "-".$eno."@fafacn.com";	
		    $staffobj = new \Justsy\BaseBundle\Management\Staff($this->da,$this->container->get("we_data_access_im"),$currUser->getUsername(),$this->logger,$this->container);

		    $tr = $staffobj->swtichEno($eno); //更换企业号
		    if($tr)
		        $staffobj->updateJid($currUser->fafa_jid,$jid); //更新im库中的jid
		    
		    //申请人和邀请人成为好友	    
	    	$staffobj->bothAddFriend($this->container,$dataset["t"]["rows"][0]["review_staff"]);

		    $newinfo=$staffobj->getInfo(); 		
			$enodata = $this->getInfo($eno);
			return Utils::WrapResultOK($enodata);
		}
		return Utils::WrapResultError("无效的邀请码");
	}

	//注册新企业
	public function register($paraObj)
	{
	    $ename = $paraObj["ename"];
	    //企业名称不能为空
	    if (empty($ename))
	    {
	        return Utils::WrapResultError("请输入企业名称");
	    }
	    $currUser = $paraObj["user"];
	    if(empty($currUser))
	    {
	        return Utils::WrapResultError("请登录后重试",ReturnCode::$NOTLOGIN);
	    }
		if($currUser->eno!=Utils::$PUBLIC_ENO)
		{
			return Utils::WrapResultError("你已成功加入企业，不能创建企业");
		}	    
	    $da = $this->da;
	    $en_row = $this->getInfoByName($ename);
	    if(!empty($en_row))
	    {
	        return Utils::WrapResultError('企业已存在');
	    }
	    
	    $da_im = $this->container->get('we_data_access_im');
	    $authtype = $paraObj["authtype"];
	    $website = $paraObj["website"];
	    $phone = $paraObj["phone"];
	    $address = $paraObj["address"];
	    $eno = SysSeq::GetSeqNextValue($da,"we_enterprise","eno");
	    $auth_level = "S";
	    $eno_vip = '1';
	    $edomain = $eno;
	    $login_account = $currUser->getUsername();
	    $sqls[] = "insert into we_enterprise (eno,edomain,ename,sys_manager,create_staff,state_id,eshortname,create_date,vip_level,eno_level,industry,addr,ewww,telephone,mstyle) values(?, ?, ?, ?, ?, ?, ?, now(), ?, ?, ?, ?, ?, ?,'outpriv')";
	    $paras[] = array(
	        (string)$eno,
	        (string)$edomain,
	        (string)$ename,
	        (string)$login_account,
	        (string)$login_account,
	        (string)"1",
	        (string)$ename,
	        (string)$eno_vip,
	        (string)$auth_level,
	        "",
	        (string)$address,
	        (string)$website,
	        (string)$phone
	    );
	    $sqls[] = "insert into we_enterprise_stored (id,enoname,eshortname,eno,auth) values(?, ?, ?, ?, ?)";
	    $paras[] = array(
	        (string)$eno,
	        (string)$ename,
	        (string)$ename, 
	        (string)$eno,
	        (string)$authtype
	    ); 
	    $eshortname = $ename;
	    $circleName = $eshortname;
	    $circleId = SysSeq::GetSeqNextValue($da,"we_circle","circle_id");
	    $sqls[] = "insert into we_circle (circle_id,circle_name,create_staff,manager,join_method,enterprise_no,network_domain,create_date,fafa_groupid) values (?,?,?,?,?,?,?,now(),?)";
	    $paras[] = array(
	        (string)$circleId,
	        (string)$circleName,
	        (string)$login_account,
	        (string)$login_account,
	        (string)1,
	        (string)$eno,
	        (string)$edomain,
	        ""
	    );
	    if(true)
	    {
	        //写入企业
	        $im_dept_sqls=array();
	        $im_dept_paras=array();
	        $subdomain = "fafacn.com";	        
	        //$da_im->ExecSQL($sql, $para);
	        //写入IM库部门表
	        $depts = array();
	        $pdeptid = "v".$eno;
	        $sql = "insert into im_base_dept (deptid, deptname, pid, path, noorder) values (?, ?, ?, ?, ?)";
	        $pid = "-10000";
	        $pubDeptPath = "/-10000/".$pdeptid."/";
	        $para = array((string)$pdeptid,(string)$ename,(string)$pid,(string)$pubDeptPath,(string)$eno);
	        $im_dept_sqls[]=$sql;
	        $im_dept_paras[]=$para;
	        $depts[] = "$pdeptid,$ename";
	        //创建默认部门：公共帐号
	        $pid = $pdeptid;
	        $pubDeptID = $pdeptid."999";                  //公共部门编号
	        $pubDeptPath = $pubDeptPath.$pubDeptID."/";//公共部门路径
	        $para = array((string)$pubDeptID,(string)"公共帐号",(string)$pid,(string)$pubDeptPath,(float)1);
	        $im_dept_sqls[]=$sql;
	        $im_dept_paras[]=$para;
	        $depts[] = $pubDeptID.",公共帐号";
	        //创建默认部门：公众号。公共部门子部门
	        $pid = $pubDeptID;
	        $pubDeptID = $pubDeptID."888";                  //公共部门子部门“公众号”部门编号
	        $pubDeptPath = $pubDeptPath.$pubDeptID."/";
	        $para = array((string)$pubDeptID,(string)"公众号",(string)$pid,(string)$pubDeptPath,(float)1);
	        $im_dept_sqls[]=$sql;
	        $im_dept_paras[]=$para;
	        $depts[] = $pubDeptID.",公众号";
	        //创建默认部门
	        $dnames = explode(",","体验部门,行政部,销售部,财务部,客服服务部,总经办,技术部");
	        $sn = 2;
	        foreach($dnames as $key => $value)
	        {
	          if (empty($value)) continue;
	          $deptid = SysSeq::GetSeqNextValue($da_im,"im_base_dept","deptid");
	          $para = array((string)$deptid,(string)$value,(string)$pdeptid,(string)"/-10000/".$pdeptid."/".$deptid."/",$value=="体验部门"?(int)$pdeptid :(int)$sn);
	          $im_dept_sqls[]=$sql;
	          $im_dept_paras[]=$para;
	          //$da_im->ExecSQL($sql, $para);
	          $depts[] = "$deptid,$value";
	          $sn++;
	        }
	        //写入虚拟人员
	        $users = array("service,客服","admin,管理员","guest,匿名访客","front,前台","sale,销售");
	        foreach($users as $key => $value)
	        {
	          if (empty($value)) continue;
	          $ary = explode(",",$value);
	          $pwd = (strcmp($ary[0],"guest")==0) ? "ljy20080511" : "";
	          $im_dept_sqls[] = "insert into im_employee (employeeid, deptid, loginname, password, employeename) values (?, ?, ?, ?, ?)";
	          $im_dept_paras[] = array(
	            (string)$pdeptid."-".$ary[0],
	            (string)$pdeptid."999",
	            (string)$ary[0]."-".$eno."@".$subdomain,
	            (string)$pwd,
	            (string)$ary[1]
	          );
	          $im_dept_sqls[] = "insert into users (username, password, created_at) values (?, ?, now())";
	          $im_dept_paras[] = array(
	            (string)$ary[0]."-".$eno."@".$subdomain,
	            (string)$pwd
	          );
	        }
	        $im_dept_sqls[]="insert into im_dept_stat(deptid,empcount)values(?,6)";
	        $im_dept_paras[]=array((string)$pdeptid);

	        $da_im->ExecSQLs($im_dept_sqls, $im_dept_paras);

	        //we_sns写入部门表
	        foreach($depts as $key => $value)
	        {
	          if (empty($value)) continue;
	          $ary = explode(",",$value);
	          $deptid = SysSeq::GetSeqNextValue($da,"we_department","dept_id");
	          $sqls[] = "insert into we_department (eno,dept_id,dept_name,parent_dept_id,fafa_deptid,create_staff) values (?,?,?,?,?,?)";
	          $paras[] = array(
	            (string)$eno,
	            (string)$deptid,
	            (string)$ary[1],
	            (string)$deptid,
	            (string)$ary[0],
	            (string)$login_account
	          );
	        }
	        try{
	        	 $StaticTrendMgr = new \Justsy\BaseBundle\Management\StaticTrendMgr($da,$da_im);
	        	 $StaticTrendMgr->RegisterPublish($ename,$circleId,$circleName,$login_account,$currUser->nick_name);
	        }
	        catch(\Exception $e)
	        {
	            $this->get("logger")->err($e);
	        }
	    }
	    $da->ExecSQLs($sqls,$paras);
	    //向RBAC跟新用户身份
      	//$staffRole=new StaffRole($da,$da_im,$this->logger);
      	//$staffRole->InsertStaffRoleByCode($login_account,$auth_level.$eno_vip,$eno);
		//为新企业创建默认的外部公众号
		try
		{
			//解密
			$pwd=$currUser->t_code;
			$micro_name=$ename;
			$public_number="_wexin_".$eno."@fafatime.com";
			$micro_type='1';
			$micro_use='0'; // 
			$concern='0';
			$create_account=$login_account;
			//企业默认公众号
			$micro=new MicroAccountMgr($da,$da_im,$currUser,$this->logger,$this->container);
			$micro->insertMicroAccount($micro_name,$public_number,$pwd,$micro_type,$micro_use,$concern,'','','','','',$this->container->get('security.encoder_factory'));
		    		    
		    //创建默认微应用
		    $appid=Utils::getAppid($eno,$login_account);
		    $appkey=Utils::getAppkey();
		    $micro_number = "_push_".$eno."@fafatime.com";
		    $micro->register(null,$micro_number,"企业推送服务",$micro_type,"1","",$concern,"","",$pwd,"","","",$this->container->get('security.encoder_factory'),$this->container->get('doctrine.odm.mongodb.document_manager'),$appid);
			
			$updateSQL[] = "update we_staff set dept_id=(SELECT dept_id FROM we_department where eno=? and fafa_deptid=?),eno=? where login_account in(?,?)";
		    $updatePara[]=array((string)$eno,"v".$eno."999",(string)$eno,(string)$micro_number,(string)$public_number);
			$updateSQL[] = "update we_micro_account set eno=? where number in(?,?)";
		    $updatePara[]=array((string)$eno,(string)$micro_number,(string)$public_number);
			
			$updateSQL[]="insert into we_appcenter_apps(appid,appkey,appname,state,appdeveloper,appdesc,apptype) values(?,?,?,?,?,?,?);";
		    $updatePara[]=array($appid,$appkey,"企业推送服务",0,$eno,"企业推送服务",'00');
		    $da->ExecSQLs($updateSQL,$updatePara);
		}
		catch(\Exception $e)
		{
		    $this->logger->err("创建默认公众号发生异常：".$e);
		}
		//自动选择企业门户
		$App = new \Justsy\BaseBundle\Management\App($this->container);    
        $App->portal_template(array("eno"=>$eno,"login_account"=>$login_account)); 

	    //通知创建者其他设备
	    Utils::sendImMessage("",$currUser->fafa_jid,"enterprise_create",$ename,$this->container,"","",false,'','0');
	      
	    $jid = SysSeq::GetSeqNextValue($da,"we_staff","fafa_jid");
	    $jid .= "-".$eno."@fafacn.com";
	      
	    $staffObj = new \Justsy\BaseBundle\Management\Staff($da,$da_im,$login_account,$this->logger,$this->container);
	    $tr = $staffObj->swtichEno($eno,$circleId); //更换企业号
	    if($tr)
	        $staffObj->updateJid($currUser->fafa_jid,$jid); //更新im库中的jid
	    $data = $staffObj->getInfo(true);//更新人员缓存信息
	    $data=$this->getInfo($eno); //缓存企业信息
	    return Utils::WrapResultOK($data);
	}

	public function joinapply($paraObj)
	{
		$currUser = $paraObj["user"];
	    if(empty($currUser))
	    {
	        return Utils::WrapResultError("请登录后重试",ReturnCode::$NOTLOGIN);
	    }		
		$wfl = new \Justsy\BaseBundle\Business\WeWorkflow($this->container);
		if($currUser->eno!=Utils::$PUBLIC_ENO)
		{
			return Utils::WrapResultError("你已成功加入企业，不能再提交加入请求");
		}
		//判断用户是否已提交过加入申请
		//没有等待处理中的申请或者已被拒绝时才能申请新的加入企业
		$paraObj["submit_staff"] = $currUser->getUsername();
		$paraObj["wf_type"]="WF_EN_JOIN";
		$nodeinfo = $wfl->getNode($paraObj);
		if(!empty($nodeinfo) && $nodeinfo["status"]!="0" && $nodeinfo["status"]!="-1")
		{
			return Utils::WrapResultError($nodeinfo["status"]=="9"? "你已提交过请求，请等待企业审核" :"你请求的企业已同意你加入，不能再提交请求");
		}
		$joineno = $paraObj["eno"];	
        if(empty($joineno))
        {
        	$joineno = $paraObj["ename"];
        	if(empty($joineno))
            	return Utils::WrapResultError("未指定申请加入的企业");
            $endata = $this->getInfoByName($joineno);
            if(empty($endata))
            	return Utils::WrapResultError("无效的企业名称");
            $joineno = $endata["eno"];
        }
        else
        {
			$endata = $this->getInfo($joineno);
			if(empty($endata))
	            	return Utils::WrapResultError("无效的企业编号");	        
        }
        $to = $endata["sys_manager"];
        if(empty($to))
        	$to=$endata["create_staff"];
        if(empty($to))
        {
            return Utils::WrapResultError("该企业未指定管理员");
        }
		$message = $paraObj["user"]->nick_name."请求加入企业";
		$re = $wfl->createWorkflow(array(
			"appid"=>$joineno,
			"user"=>$paraObj["user"],
			"to"=>$to,
			"wf_name"=>"新员工请求加入企业",
			"wf_content"=>$message,
			"wf_type"=>"WF_EN_JOIN"
		));
		
		if($re)
		{			
			Utils::sendImMessage("",explode(";", $to) ,"enterprise_joinapply",json_encode($re),$this->container,"","",true,'','0');
			$re = $wfl->getNode(array("node_id"=>$re["node_id"]));

			$re["eno"] = $joineno;
			$re["ename"] = $endata["ename"];
			$re["logo_path"] = $endata["logo_path"];
		}
		return Utils::WrapResultOK($re);
	}
	
	public function agreejoin($paraObj)
	{
		$currUser = $paraObj["user"];
	    if(empty($currUser))
	    {
	        return Utils::WrapResultError("请登录后重试",ReturnCode::$NOTLOGIN);
	    }

		$wfl = new \Justsy\BaseBundle\Business\WeWorkflow($this->container);
		//根据申请帐号处理
		$account = isset($paraObj["staff"]) ? $paraObj["staff"] : "";
		if(!empty($account))
		{
			$paraObj["appid"] = $paraObj["user"]->eno;
			$paraObj["submit_staff"] = $account;
		}
		$nodeinfo = $wfl->getNode($paraObj);
		
		if(empty($nodeinfo))
		{
			return Utils::WrapResultError("申请已被取消或删除");
		}
		$paraObj["node_id"] = $nodeinfo["node_id"];
		$applystaff = $nodeinfo["submit_staff"];		
		//判断申请人是否已加入企业
		$staffobj = new \Justsy\BaseBundle\Management\Staff($this->da,$this->container->get('we_data_access_im'),$applystaff,$this->logger,$this->container);
        $staffata = $staffobj->getInfo(); 
	    if($staffata["eno"]!=Utils::$PUBLIC_ENO)
		{
			if($staffata["eno"]==$currUser->eno)
				return Utils::WrapResultError("该帐号已加入企业");
			else
				return Utils::WrapResultError("该帐号已加入其他企业");
		}

		$message = "你加入{$currUser->ename}的申请已审批通过";
		Utils::sendImMessage("",$applystaff ,"enterprise_joinagree",$message,$this->container,"","",true,'','0');

        //员工加入处理
	    $eno = $paraObj["user"]->eno;
	    $jid = SysSeq::GetSeqNextValue($this->da,"we_staff","fafa_jid");
	    $jid .= "-".$eno."@fafacn.com";	      
	    $tr = $staffobj->swtichEno($eno); //更换企业号
	    if($tr)
	        $staffobj->updateJid($staffata["fafa_jid"],$jid); //更新im库中的jid
	    $newinfo=$staffobj->getInfo(true);
		Utils::sendImMessage("",$newinfo["fafa_jid"] ,"enterprise_joinagree",$message,$this->container,"","",true,'','0');

        //申请状态处理		
		$re = $wfl->agree($paraObj);
		if(!empty($re))
		{
            //通知企业其他管理员
			$endata = $this->getInfo($currUser->eno);
			$to = $endata["sys_manager"];
        	if(empty($to))
        		$to=$endata["create_staff"];
        	Utils::sendImMessage("",explode(";", $to) ,"enterprise_joinagree",json_encode($re),$this->container,"","",true,'','0');
		}
		return Utils::WrapResultOK($re);
	}

	public function rejectjoin($paraObj)
	{
		$currUser = $paraObj["user"];
	    if(empty($currUser))
	    {
	        return Utils::WrapResultError("请登录后重试",ReturnCode::$NOTLOGIN);
	    }		
		$wfl = new \Justsy\BaseBundle\Business\WeWorkflow($this->container);
		//根据申请帐号处理
		$account = isset($paraObj["staff"]) ? $paraObj["staff"] : "";
		if(!empty($account))
		{
			$paraObj["appid"] = $paraObj["user"]->eno;
			$paraObj["submit_staff"] = $account;
		}
		$nodeinfo = $wfl->getNode($paraObj);
		if(empty($nodeinfo))
		{
			return Utils::WrapResultError("申请已被取消或删除");
		}
		//判断申请人是否已加入企业
		$staffobj = new \Justsy\BaseBundle\Management\Staff($this->da,$this->container->get('we_data_access_im'),$paraObj["submit_staff"],$this->logger,$this->container);
        $staffata = $staffobj->getInfo(); 
	    if($staffata["eno"]!=Utils::$PUBLIC_ENO)
		{
			if($staffata["eno"]==$currUser->eno)
				return Utils::WrapResultError("该帐号已加入企业");
			else
				return Utils::WrapResultError("该帐号已加入其他企业");
		}		
        $paraObj["node_id"] = $nodeinfo["node_id"];
        //申请状态处理		
		$re = $wfl->reject($paraObj);

		//消息通知 
		if(!empty($re))
		{
            $message = "你的企业加入申请已被拒绝，请联系企业管理员";
			Utils::sendImMessage("",$re["submit_staff"] ,"enterprise_joinreject",$message,$this->container,"","",true,'','0');
		
			//通知企业其他管理员
			$endata = $this->getInfo($currUser->eno);
			$to = $endata["sys_manager"];
        	if(empty($to))
        		$to=$endata["create_staff"];
        	Utils::sendImMessage("",explode(";", $to) ,"enterprise_joinreject",json_encode($re),$this->container,"","",true,'','0');
		}
		return Utils::WrapResultOK($re);
	}

	public function removeapply($paraObj)
	{
		$wfl = new \Justsy\BaseBundle\Business\WeWorkflow($this->container);
		$re = $wfl->cancel($paraObj);
		//消息通知 
		if($re)
		{
            $message = "申请取消成功";
			Utils::sendImMessage("",$paraObj["user"]->fafa_jid ,"enterprise_removeapply",$message,$this->container,"","",true,'','0');
		
			//通知企业其他管理员
			$endata = $this->getInfo($re["appid"]);
			$to = $endata["sys_manager"];
        	if(empty($to))
        		$to=$endata["create_staff"];
        	Utils::sendImMessage("",explode(";", $to) ,"enterprise_removeapply",json_encode($re),$this->container,"","",true,'','0');
		}
		return Utils::WrapResultOK($re);
	}

	public function deleteapply($paraObj)
	{
		$wfl = new \Justsy\BaseBundle\Business\WeWorkflow($this->container);
		$re = $wfl->removeWorkflowNode($paraObj);
		//消息通知 
		if($re)
		{
            $message = "申请删除成功";
			Utils::sendImMessage("",$paraObj["user"]->fafa_jid ,"enterprise_deleteapply",$message,$this->container,"","",true,'','0');
		
			//通知企业其他管理员
			$endata = $this->getInfo($re["appid"]);
			$to = $endata["sys_manager"];
        	if(empty($to))
        		$to=$endata["create_staff"];
        	Utils::sendImMessage("",explode(";", $to) ,"enterprise_deleteapply",json_encode($re),$this->container,"","",true,'','0');
		}
		return Utils::WrapResultOK($re);
	}	
  
    //获取所有发提交发起的申请
    public function submitapplylist($paraObj)
    {
        $currUser = $paraObj["user"];
        if(empty($currUser))
        {
            return Utils::WrapResultError("请登录后重试",ReturnCode::$NOTLOGIN);
        }		
        $wfl = new \Justsy\BaseBundle\Business\WeWorkflow($this->container);
        $paraObj["wf_type"] = "WF_EN_JOIN";
        $result = $wfl->mylist($paraObj);
        return Utils::WrapResultOK($result);
    }	
    
    //获取所有我处理的申请
	public function dealapplylist($paraObj)
	{
		$currUser = $paraObj["user"];
	    if(empty($currUser))
	    {
	        return Utils::WrapResultError("请登录后重试",ReturnCode::$NOTLOGIN);
	    }		
		$wfl = new \Justsy\BaseBundle\Business\WeWorkflow($this->container);
		if(isset($paraObj["status"]))
		{
			if($paraObj["status"]=="todo")
			{
				return Utils::WrapResultOK($wfl->listtodo($paraObj));
			}
			if($paraObj["status"]=="did")
			{
				return Utils::WrapResultOK($wfl->listtdid($paraObj));
			}
		}
		return Utils::WrapResultOK($wfl->listall($paraObj));
	}

	protected function writelog($e)
	{
		if(!empty($logger))
		{
			$this->logger->err($e);
		}
	}
}
?>