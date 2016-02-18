<?php

namespace Justsy\BaseBundle\Management;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Management\Identify;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Login\UserSession;
use Justsy\BaseBundle\Login\UserProvider;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Common\Cache_Enterprise;

class App implements IBusObject
{ 
	  private $containerObj="";
	  private $conn=null;
	  private $conn_im = null;
	  private $logger=null;
	  	  
	  public function __construct($container)
	  {
	    $this->containerObj = $container;
    	$this->conn    = $container->get('we_data_access');
    	$this->conn_im = $container->get('we_data_access_im');
    	$this->logger=$container->get('logger');
	  }

	  public function getInstance($container)
	  {
	  		return new self($container);
	  }

	  public function publishApp($parameter)
	  {
	  	$mappMgr = new \Justsy\BaseBundle\Controller\MappConfigController();
	  	$mappMgr->setContainer($this->containerObj);
	  	return $mappMgr->appPublish($parameter["appid"]);
	  }

	//刷新缓存的应用信息
	public function refreshapp($parameter)
	{
		$appid = $parameter["appid"];
		Cache_Enterprise::delete(Cache_Enterprise::$EN_APP,$appid,$this->containerObj);
		$this->getappinfo($parameter);
		return true;
	}

	public function refreshappbind($parameter)
	{
		$appid = $parameter["appid"];
		$openid = $parameter["openid"];
		$key = md5($appid.$openid);
		Cache_Enterprise::delete(Cache_Enterprise::$EN_APP_BIND,$key,$this->containerObj);
		$this->getappbind($parameter);
		return true;
	}

	public function delExceptionLog($parameter){
		$da = $this->conn;
		// $da_im = this->conn_im;

		$reportId = $parameter["reportId"];
		$sql = "delete from wa_err_report where report_id=? ";
		$result = $this->conn->ExecSQL($sql,array($reportId));
		return Utils::WrapResultOK("");
	}

	public function getExceptionList($parameter){
		try{
        $da = $this->conn;
  	    $da_im = $this->conn_im;

        $pageindex = $parameter["page_index"];
        $limit = $parameter["limit"];
        $user = $parameter["user"];

        $sql = " select report_id,report_staff,report_date,report_device,report_content"
				 ." from wa_err_report"
				 ." order by report_date desc "
				 ." limit  ".($pageindex-1)*$limit." , ".$limit;
        $table = $da->GetData("t",$sql);
        
        return $table["t"]["rows"];
      }
      catch(\Exception $e)
      {
      	  return  null;
      }
	}

	public function refreshbussystem($parameter)
	{
		$appid = $parameter["appid"];
		$inf_type ="Rest";
		$key = ($appid.$inf_type);
		Cache_Enterprise::delete(Cache_Enterprise::$EN_APP,$key,$this->containerObj);
		$inf_type ="Database";
		$key = ($appid.$inf_type);
		Cache_Enterprise::delete(Cache_Enterprise::$EN_APP,$key,$this->containerObj);
		$inf_type ="Soap";
		$key = ($appid.$inf_type);
		Cache_Enterprise::delete(Cache_Enterprise::$EN_APP,$key,$this->containerObj);
		return true;
	}

	public function refreshPortal($parameter)
	{
		$eno =$parameter["eno"];
		Cache_Enterprise::delete(Cache_Enterprise::$EN_APP,$eno,$this->containerObj);
	}

	public function getappinfo($parameter)
	{
		$appid = $parameter["appid"];
		$inf_type =isset($parameter["inf_type"]) ? $parameter["inf_type"] : "Rest";
		$appdata = Cache_Enterprise::get(Cache_Enterprise::$EN_APP,$appid,$this->containerObj);
		if(!empty($appdata))
		{
			return json_decode($appdata,true);			 
		}
		$url = $this->containerObj->getParameter('FILE_WEBSERVER_URL');
			
		$sql = "select a.appid,a.appname,a.functiontype,a.appkey,concat('{$url}',a.logo) logo,a.appdeveloper,a.apptype,a.configfileid,b.configfileid publishfileid,a.version,b.publishversion,a.createstaffid,a.subscribe,a.show_type,case a.show_type when '01' then '企业应用' else '个人应用' end show_type_name,a.circleid,b.publishstaff,b.publishdate,".
			  " d.jid".
		      " from we_appcenter_apps a left join we_apps_publish b on a.appid=b.appid and b.publishstate='1' ".
		      " left join we_micro_account d on a.appid=d.micro_source".
		      " where a.appid=?";
		$dataset = $this->conn->GetData("t",$sql,array(
			(string)$appid
		));
		if(count($dataset["t"]["rows"])>0)
		{
			//$rowdata = $dataset["t"]["rows"][0];
			//$functiontype = $rowdata["functiontype"];//关联的业务系统
			//$sysinfo = $this->getbussysteminfo(array("appid"=>$functiontype));
			//if(!empty($sysinfo))
			//{
			//	$rowdata =array_merge( $rowdata,$sysinfo); //合并属性
			//}
			Cache_Enterprise::set(Cache_Enterprise::$EN_APP,
									$appid,
									json_encode($rowdata),
									0,
									$this->containerObj);
			return $rowdata;
		}
		return null;
	}

	public function getbussysteminfo($parameter)
	{
		$appid = $parameter["appid"];
		$inf_type =isset($parameter["inf_type"]) ? $parameter["inf_type"] : "Rest";
		$appdata = Cache_Enterprise::get(Cache_Enterprise::$EN_APP,$appid.$inf_type,$this->containerObj);
		if(!empty($appdata))
		{
			return json_decode($appdata,true);			 
		}
		$sql = "select a.*,'' clientid,'' clientkey,'' authorization_url,'' token_url,'' token_method,'' redirection_url,'' state_code,'' code_para_name,'' token_para_name,'' clientid_para_name,'' clientkey_para_name, '' redirecturl_para_name,'' userdefined_para from mapp_interface_system a where a.systemid=? and inf_type=?";
		$dataset = $this->conn->GetData("t",$sql,array(
			(string)$appid,
			(string)$inf_type
		));
		if(count($dataset["t"]["rows"])>0)
		{
			$tmpAppid = $appid."-".$inf_type;
			$sql2 = "select b.authtype,b.clientid,b.clientkey,b.authorization_url,b.token_url,b.token_method,b.redirection_url,b.state_code,b.code_para_name,b.token_para_name,b.clientid_para_name,b.clientkey_para_name,b.redirecturl_para_name,b.userdefined_para from we_app_auth2_config b where b.appid=?";
			$dataset2 = $this->conn->GetData("t2",$sql2,array(
				(string)$tmpAppid
			));
			if(count($dataset2["t2"]["rows"])>0)
			{
				$tr = $dataset2["t2"]["rows"][0];
				$dataset["t"]["rows"][0]["authtype"] = $tr["authtype"];
				$dataset["t"]["rows"][0]["clientid"] = $tr["clientid"];
				$dataset["t"]["rows"][0]["clientkey"] = $tr["clientkey"];
				$dataset["t"]["rows"][0]["authorization_url"] = $tr["authorization_url"];
				$dataset["t"]["rows"][0]["token_url"] = $tr["token_url"];
				$dataset["t"]["rows"][0]["token_method"] = $tr["token_method"];
				$dataset["t"]["rows"][0]["redirection_url"] = $tr["redirection_url"];
				$dataset["t"]["rows"][0]["state_code"] = $tr["state_code"];
				$dataset["t"]["rows"][0]["code_para_name"] = $tr["code_para_name"];
				$dataset["t"]["rows"][0]["token_para_name"] = $tr["token_para_name"];
				$dataset["t"]["rows"][0]["clientid_para_name"] = $tr["clientid_para_name"];
				$dataset["t"]["rows"][0]["clientkey_para_name"] = $tr["clientkey_para_name"];
				$dataset["t"]["rows"][0]["redirecturl_para_name"] = $tr["redirecturl_para_name"];
				$dataset["t"]["rows"][0]["userdefined_para"] = $tr["userdefined_para"];
			}
			Cache_Enterprise::set(Cache_Enterprise::$EN_APP,
									$appid.$inf_type,
									json_encode($dataset["t"]["rows"][0]),
									0,
									$this->containerObj);		
			return $dataset["t"]["rows"][0];
		}
		return null;
	}
	public function getappbind($parameter)
	{
		$appid = $parameter["appid"];
		$openid = $parameter["openid"];
		$key = md5($appid.$openid);
		$appdata = Cache_Enterprise::get(Cache_Enterprise::$EN_APP_BIND,$key,$this->containerObj);
		if(!empty($appdata))
		{
			return json_decode($appdata,true);			 
		}
		$sql = "select a.bind_uid,a.authkey from we_staff_account_bind a  where a.appid=? and bind_account=?";
		$dataset = $this->conn->GetData("t",$sql,array(
			(string)$appid,
			(string)$openid
		));
		if(count($dataset["t"]["rows"])>0)
		{
			Cache_Enterprise::set(Cache_Enterprise::$EN_APP_BIND,
									$key,
									json_encode($dataset["t"]["rows"][0]),
									0,
									$this->containerObj);		
			return $dataset["t"]["rows"][0];	
		}
		return null;	
	}

	public function setappbind($parameter)
	{
		$appid     = $parameter["appid"];
		$openid    = $parameter["openid"];
		$bind_uid  = $parameter["bind_uid"];
		$authkey   = $parameter["authkey"];
		$bind_type = $parameter["bind_type"];
		$sql = "delete from we_staff_account_bind where bind_account=? and bind_type=? and appid=?";
		$this->conn->ExecSQL($sql,array((string)$openid,$bind_type,$appid));
		$sql = "insert into we_staff_account_bind(bind_account,appid,bind_uid,authkey,bind_type,bind_created)values(?,?,?,?,?,now())";
		$this->conn->ExecSQL($sql,array(
					(string)$openid,
					(string)$appid,
					(string)$bind_uid,
					(string)$authkey,
					(string)$bind_type
		));
		$this->refreshappbind(array("appid"=>$appid,"openid"=>$openid));			
	}

	public function setappsession($parameter)
	{
		$appid = $parameter["appid"];
		$openid = $parameter["openid"];
		$retuenAry =  $parameter["session"];
		//存储token=>we_app_oauth_sessions
			$sql = "select 1 from we_app_oauth_sessions where appid=? and userid=?";
			$db = $this->conn;
			$dsset = $db->getdata("t",$sql,array(
				(string)$appid,
				(string)$openid
			));
			if(isset($retuenAry["expires_in"]))
			{
				$expires_in = (int)$retuenAry["expires_in"];
				$accessTokenExpires = time() + $expires_in;
				$retuenAry["expires_in"] = $accessTokenExpires;
			}
			else
			{
				$retuenAry["expires_in"] = 0;
				$accessTokenExpires = 0;
				$expires_in =0 ;
			}
			if($dsset && count($dsset["t"]["rows"])>0)
			{
				$sql = "update we_app_oauth_sessions set access_token=?,refresh_token=?,access_token_expires=? where appid=? and userid=?";
				$db->ExecSQL($sql,array(
					(string)$retuenAry["access_token"],
					isset($retuenAry["refresh_token"]) ? (string)$retuenAry["refresh_token"] : "",
					(int)$accessTokenExpires,
					(string)$appid,
					(string)$openid
				));
			}
			else
			{
				$id = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue( $db, "we_app_oauth_sessions", "id");
				$sql = "insert into we_app_oauth_sessions(id,appid,userid,user_type,access_token,refresh_token,access_token_expires)values(?,?,?,?,?,?,?)";
				$db->ExecSQL($sql,array(
					(int)$id,
					(string)$appid,
					(string)$openid,
					"user",
					(string)$retuenAry["access_token"],
					isset($retuenAry["refresh_token"]) ? (string)$retuenAry["refresh_token"] : "",
					(int)$accessTokenExpires	
				));			
			}
			$cacheKey = md5($appid.$openid);
			Cache_Enterprise::set(Cache_Enterprise::$EN_OAUTH2,$cacheKey,json_encode($retuenAry),$expires_in,$this->containerObj);
	}
	public function getappsession($parameter)
	{
		$appid = $parameter["appid"];
		$openid = $parameter["openid"];
		$key = md5($appid.$openid);
		$appdata = Cache_Enterprise::get(Cache_Enterprise::$EN_OAUTH2,$key,$this->containerObj);
		if(!empty($appdata))
		{
			return json_decode($appdata,true);
		}
		$sql = "select access_token,refresh_token,access_token_expires expires_in from we_app_oauth_sessions where appid=? and userid=?";		
		$dsset = $this->conn->getdata("t",$sql,array(
				(string)$appid,
				(string)$openid
		));
		if(count($dsset["t"]["rows"])>0)
		{
			Cache_Enterprise::set(Cache_Enterprise::$EN_APP_BIND,
									$key,
									json_encode($dsset["t"]["rows"][0]),
									0,
									$this->containerObj);		
			return $dsset["t"]["rows"][0];	
		}
		return null;		
	}

	public function save($parameter)
	{
     $curuser = $parameter["user"]; 
     $sql = "";
     $para = array();
     $da = $this->conn;
     $result = array();
     try
     {  
     	if ($this->existsAppName($curuser->eno,$parameter["appid"],$parameter["appname"]))
     	{
	     	return Utils::WrapResultError("PUSH应用名称已存在！");
	    }
	    else
	    {
	      if (empty($appid)) {
	         	$appid = Utils::getAppid($curuser->eno,$curuser->login_account);
    			$appkey = Utils::getAppkey();
    			//$MicroAccountMgr=new MicroAccountMgr($da,$this->get('we_data_access_im'),$curuser,$this->get("logger"), $this->container);
    			//$MicroAccountMgr->register("",$number,$name,$type,$micro_use,$introduction,$concern_approval,$salutatory,$level,$password,$filename48,$filename120,$filename24,$factory,$dm,$appid);
    			$sql = "insert into we_appcenter_apps(appid,appkey,logo,appname,state,appdeveloper,appdesc,bindurl,apptype,sortid,createstaffid)values(?,?,?,?,1,?,?,?,?,?,?)";
    			$para = array($appid,$appkey,"",$parameter["appname"],$curuser->eno,"","","99",0,$curuser->login_account);
		    }
		    else {
  		     	$sql = "update we_appcenter_apps set appname=?,logo=?,appdesc=?,bindurl=?,sortid=?,createstaffid=? where appid=?";
  		     	$para = array($parameter["appname"],"","","",0,$curuser->login_account,$parameter["appid"]);
		    }
		    if ($sql!="")
		      $da->ExecSQL($sql,$para);
	    }
     }
     catch(\Exception $e)
     {
     	 return Utils::WrapResultError($e->getMessage());
     }     
     return Utils::WrapResultOK("");	
	}

	//查询商城应用
	public function search($parameter)
	{
		$userinfo = $parameter["user"]; 
		$appname =isset($parameter["appname"]) ? $parameter["appname"] : "";
	  	if(!empty($appid))
	  	{
	  		return $this->getappinfo(array("appid"=>$appid));
	  	}
	  	$url = $this->containerObj->getParameter('FILE_WEBSERVER_URL');
	  	$pageno =isset($parameter["pageno"]) ? $parameter["pageno"] : 0;//获取列表的起始位置
	  	$pagesize = isset($parameter["pagesize"]) ? $parameter["pagesize"] : 50;
	  	$ordertype = isset($parameter["ordertype"]) ? $parameter["ordertype"] : "";//排序类型。默认按发布日期
	  	$sql = "select a.appid,a.appkey,a.appname,a.appdeveloper,a.version,a.show_type,a.apptype,concat('{$url}',a.logo) logo,case a.show_type when '01' then '企业应用' else '个人应用' end show_type_name ".
	  		   " from we_appcenter_apps a where 1=1 ";
	  	$para = array();

	  	if(!empty($appname))
	  	{
	  		$sql .= " and a.appname like concat('%',?,'%')";
	  		$para[] = (string)$appname;
	  	}	  	
	  	$sql .= " order by a.sortid ";

	  	$startno = $pageno*$pagesize;
	  	$sql .= " limit {$startno},{$pagesize} ";
	  	$ds = $this->conn->GetData("table",$sql,$para);
	  	return Utils::WrapResultOK($ds["table"]["rows"]);
	}  

  private function existsAppName($appdeveloper,$appid,$appname)
  {
  	 $result = false;
  	 $sql = "";
  	 $para = array();
  	 if (empty($appid))  //添加时的判断
  	 {
  	 	  $sql = "select appid from we_appcenter_apps where appname=? and appdeveloper=?";
  	 	  $para = array((string)$appname,(string)$appdeveloper);
  	 }
  	 else  //修改时的判断
  	 {
  	 	  $sql = "select appid from we_appcenter_apps where appname=? and appdeveloper=? and appid !=?";
  	 	  $para = array((string)$appname,(string)$appdeveloper,(string)$appid);
  	 }
  	 $da = $this->conn;
  	 $ds = $da->GetData('table',$sql,$para);
  	 if ($ds && $ds["table"]["recordcount"]>0)
  	   $result = true;
  	 return $result;
  }
	private function saveFile($path)
	{
			$dm = $this->containerObj->get('doctrine.odm.mongodb.document_manager');
		    $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
		    $doc->setName(basename($path));
		    $doc->setFile($path);
		    $dm->persist($doc);
		    $dm->flush();
		    unlink($path);
		    return $doc->getId();
	}
    
    //获得应用列表
    public function get_AppList($parameter)
    {
        $user= $parameter["user"];
        $nick_name = $user->nick_name;
        $sql = "select appid,ifnull(logo,'') fileid,appname,case when version is null then '' else concat('v',convert(version,decimal(10,1))) end as version,ifnull(createstaffid,'') createstaff,configfileid,(select count(1) from we_app_subscibe b where b.appid=we_appcenter_apps.appid) subscibecount,
                    case when createstaffid=? then 1 else 0 end iscreate,ifnull(publishdate,'') publishdate,publishstaff
                from we_appcenter_apps where appdeveloper=? order by sortid desc,publishdate desc;";
        $para = array((string)$nick_name,(string)$user->eno);
        $success = true;$msg = "";$appinfo=array();
        try
        {
            $ds = $this->conn->GetData("table",$sql,$para);
            if ( $ds && $ds["table"]["recordcount"]>0)
                $appinfo = $ds["table"]["rows"];
        }
        catch(\Exception $e)
        {
            $success = false;
            $msg = "查询应用数据失败！";
            $this->logger->err($e->getMessage());
        }
        return array("success"=>$success,"msg"=>$msg,"appinfo"=>$appinfo);
    }
}