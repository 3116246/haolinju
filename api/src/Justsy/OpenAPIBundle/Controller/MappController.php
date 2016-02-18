<?php

namespace Justsy\OpenAPIBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Login\UserProvider;
use Justsy\BaseBundle\Login\UserSession;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Management\Staff;

class MappController extends Controller
{
	public function getPortalVersionAction($openid)
	{
		if(empty($openid)) return $this->responseJson(array("returncode"=>"9999","msg"=>"openid is not null."));
		$da = $this->get("we_data_access");

        $res = $this->getRequest();

		$dev = $res->get("dev");
        if(empty($dev) || $dev=="0")
        {
        	$sql = " select publishversion version from we_apps_publish a,we_staff b where a.appid=b.eno and b.openid=? order by  a.id+0 desc limit 0,1 "; //获取正式发布的配置文件
    	}
    	else
    	{
    		$sql = " select version from we_apps_portalconfig a,we_staff b where a.eno=b.eno and b.openid=? "; //获取开发测试用的配置文件  
    	}

        $f = $da->GetData("data",$sql,array((string)$openid));    
        if($f!=null && count($f['data']['rows']) > 0 ){
			return $this->responseJson(array("returncode"=>"0000","version"=>$f['data']['rows'][0]["version"]));
		}
		else
		{
			return $this->responseJson(array("returncode"=>"0000","version"=>""));
		}
	}

	public function getPortalConfigAction($openid)
	{		
      try
      {
      	//$fileid = $f["data"]["rows"][0]["ios_configfileid"];
      	//$fileid = empty($fileid) ? $f["data"]["rows"][0]["configfileid"] : $fileid;
        $doc = $this->get('doctrine.odm.mongodb.document_manager')
        	->getRepository('JustsyMongoDocBundle:WeDocument')
        	->find($openid);
	    if (!$doc) 
	    {
	    	//尝试通过人员标识获取配置文件
	        //throw $this->createNotFoundException('No file found for id '.$id);
	        return $this->getPortalConfigByOpenid($openid);
	    }
	    $filename = $doc->getName();
	    if(isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER["HTTP_USER_AGENT"], "MSIE")) $filename = urlencode($filename);
	       
	        $finfo = new \finfo(FILEINFO_MIME);
		$bytes = $doc->getFile()->getBytes();
		$cnf_xml =str_replace(">Portal<",">portal<", str_replace("\t", "",str_replace("\r", "", str_replace("\n", "", $bytes)))) ;
		$cnf_xml = str_replace("&amp;", "&", $cnf_xml);	        
	    $response = new Response($cnf_xml);
	    $response->headers->set('Content-Type', "text/html");
	    $response->headers->set('Accept-Ranges','bytes');
	    $response->headers->set('Accept-Length',$doc->getLength());
	    //$response->headers->set('Content-Disposition','size='.$doc->getLength().'; filename="'.$filename.'"');
	    return $response; 
	    
	    }
	    catch(\Exception $e){
	    	return $this->responseJson(array("returncode"=>"999901","msg"=>"xml文件结构错误！"));
	    }
	   
	}

	public function getPortalConfigByOpenid($openid)
	{
		
		if(empty($openid)) return $this->responseJson(array("returncode"=>"9999","msg"=>"openid is not null."));
		$da = $this->get("we_data_access");

        $res = $this->getRequest();
        $dev = $res->get("dev");
        if(empty($dev) || $dev=="0")
        {
        	$sql = " select configfileid,ios_configfileid from we_apps_publish a,we_staff b where a.appid=b.eno and b.openid=? order by  a.id+0 desc limit 0,1 "; //获取正式发布的配置文件
    	}
    	else
    	{
    		$sql = " select configfileid,ios_configfileid from we_apps_portalconfig a,we_staff b where a.appid=b.eno and b.openid=? "; //获取开发测试用的配置文件  
    	}

        $f = $da->GetData("data",$sql,array((string)$openid));

        if(count($f["data"]["rows"])==0)
        {
        	return new Response("No found APP info.", 404); 
        }
        if(empty($f["data"]["rows"][0]["configfileid"]))
        {
        	return new Response("config file is not make.", 404); 
        }
        
      try
      {
      	$fileid = $f["data"]["rows"][0]["configfileid"];
      	$fileid = empty($fileid) ? $f["data"]["rows"][0]["ios_configfileid"] : $fileid;
        $doc = $this->get('doctrine.odm.mongodb.document_manager')
        	->getRepository('JustsyMongoDocBundle:WeDocument')
        	->find($fileid);
	    if (!$doc) 
	    {
	        //throw $this->createNotFoundException('No file found for id '.$id);
	        return new Response("No found config file.", 404); 
	    }
	    $filename = $doc->getName();
	    if(isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER["HTTP_USER_AGENT"], "MSIE")) $filename = urlencode($filename);
	       
	        $finfo = new \finfo(FILEINFO_MIME);
		$bytes = $doc->getFile()->getBytes();
		$cnf_xml =str_replace(">Portal<",">portal<", str_replace("\t", "",str_replace("\r", "", str_replace("\n", "", $bytes)))) ;
		$cnf_xml = str_replace("&amp;", "&", $cnf_xml);	        
	    $response = new Response($cnf_xml);
	    $response->headers->set('Content-Type', "text/html");
	    $response->headers->set('Accept-Ranges','bytes');
	    $response->headers->set('Accept-Length',$doc->getLength());
	    //$response->headers->set('Content-Disposition','size='.$doc->getLength().'; filename="'.$filename.'"');
	    return $response; 
	    
	    }
	    catch(\Exception $e){
	    	return $this->responseJson(array("returncode"=>"999901","msg"=>"xml文件结构错误！"));
	    }
	   
	}

	//获取指定openid人员可以使用的应用列表
	public function getMyAppListAction($openid){
		$conn = $this->get("we_data_access");
		$request = $this->getRequest();
		if(empty($openid)) return $this->responseJson(array("returncode"=>"9999","msg"=>"openid is not null."));
		$FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
		try{
			//判断是否设置有权限
			$sql = "select appid from we_app_developer a inner join we_staff b on a.login_account=b.login_account where openid=?
              union select appid from we_app_userpriv a inner join we_staff b on a.login_account=b.login_account where openid=?
              union select appid from we_app_unitalloc a inner join we_staff b on a.deptid=b.dept_id where openid=?
              union select appid from we_app_unitalloc a inner join we_staff_role b on a.roleid=b.roleid inner join we_staff c on b.staff=c.login_account where openid=?";
      $para=array((string)$openid,(string)$openid,(string)$openid,(string)$openid);
      $ds = $conn->GetData("table",$sql,$para);
      //有权限时只能设置有权限的应用
      if ( $ds && $ds["table"]["recordcount"]>0){
      	 $para = array();
      	 $appid = "";
      	 for($i=0;$i< $ds["table"]["recordcount"];$i++){
      	 	 $appid .= "?,";
      	 	 array_push($para,(string)$ds["table"]["rows"][$i]["appid"]);
      	 }
      	 $appid = rtrim($appid,",");
         $sql="select appid,appname,appkey,appdesc,concat('".$FILE_WEBSERVER_URL ."',logo) logo,(select max(publishversion) from we_apps_publish where appid=a.appid) version,
				       b.login_account,(select count(1) from we_app_userpriv p where p.appid=a.appid and p.login_account=b.login_account) isbind,(select concat(jid,'/WeBizProxy') from we_micro_account wma where wma.micro_source=a.appid) jid
			 	       from we_appcenter_apps a,we_staff b 
			 	      where  a.appid in(".$appid.") and a.appdeveloper=b.eno and b.openid=? and apptype like '99%' order by sortid";
			 	 array_push($para,$openid);    	
      }
      else{
         $sql="select appid,appname,appkey,appdesc,concat('".$FILE_WEBSERVER_URL ."',logo) logo,(select max(publishversion) from we_apps_publish where appid=a.appid) version,
				         b.login_account,(select count(1) from we_app_userpriv p where p.appid=a.appid and p.login_account=b.login_account) isbind,(select concat(jid,'/WeBizProxy') from we_micro_account wma where wma.micro_source=a.appid) jid
			 	       from we_appcenter_apps a,we_staff b 
			 	       where a.appdeveloper=b.eno and b.openid=? and apptype like '99%' order by sortid";
			   $para=array((string)$openid);
      }
			$data=$conn->GetData('dt',$sql,$para);
			if($data!=null && count($data['dt']['rows']) > 0 ){
				return $this->responseJson(array("returncode"=>"0000","list"=>$data['dt']['rows']));
			}
		}
		catch(\Exception $e)
	    {
	     	$result = array("returncode"=>"9999","msg"=>$e->getMessage());
	     	return $this->responseJson($result);
	    }
		return $this->responseJson(array("returncode"=>"0000","list"=>array()));
	}

	public function getAppConfigAction($appid)
	{
		if(empty($appid)) return new Response("bad request.error parameter APPID.", 404);

        $da = $this->get("we_data_access");
        $res = $this->getRequest();
        $dev = $res->get("dev");
        if(empty($dev) || $dev=="0")
        {
        	$sql = " select configfileid from we_apps_publish where appid=?  order by id+0 desc limit 0,1 "; //获取正式发布的配置文件
    	}
    	else
    	{
    		$sql = " select configfileid from we_appcenter_apps where appid=? and apptype like '99%'"; //获取开发测试用的配置文件  
    	}
        $f = $da->GetData("data",$sql,array((string)$appid));

        if(count($f["data"]["rows"])==0)
        {
        	return new Response("No found APP info.", 404); 
        }
        if(empty($f["data"]["rows"][0]["configfileid"]))
        {
        	return new Response("config file is not make.", 404); 
        }
        $fileid = $f["data"]["rows"][0]["configfileid"];
        $doc = $this->get('doctrine.odm.mongodb.document_manager')
        	->getRepository('JustsyMongoDocBundle:WeDocument')
        	->find( $fileid);
	    if (!$doc) 
	    {
	        //throw $this->createNotFoundException('No file found for id '.$id);
	        return new Response("No found config file.", 404); 
	    }
	    $filename = $doc->getName();
	    if(isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER["HTTP_USER_AGENT"], "MSIE")) $filename = urlencode($filename);
	       
	    $finfo = new \finfo(FILEINFO_MIME);
	    $response = new Response($doc->getFile()->getBytes());
	    $response->headers->set('Content-Type', "text/html");
	    $response->headers->set('Accept-Ranges','bytes');
	    $response->headers->set('Accept-Length',$doc->getLength());
	    //$response->headers->set('Content-Disposition','size='.$doc->getLength().'; filename="'.$filename.'"');
	    return $response;
	}

	public function appBindAction($openid,$appid)
	{
		$result = array("returncode"=>"9999","msg"=>"openid or appid is not null.");
		try
		{
			$conn = $this->get("we_data_access");
			if(empty($openid)) return $this->responseJson($result);
			if(empty($appid)) return $this->responseJson($result);
			//判断openid
			$login_account = "";
			$sql = "select login_account from we_staff where openid=?";
			$data=$conn->GetData('dt',$sql,array((string)$openid));
			if($data && count($data["dt"]["rows"])==0)
			{
				$result["msg"]="openid is invalid.";
				return $this->responseJson($result);
			}
			$login_account = $data["dt"]["rows"][0]["login_account"];
			$sql = "select 1 from we_appcenter_apps where appid=?";
			$data=$conn->GetData('dt',$sql,array((string)$appid));
			if($data && count($data["dt"]["rows"])==0)
			{
				$result["msg"]="appid is invalid.";
				return $this->responseJson($result);
			}
			$sql="select 1 from we_app_userpriv a where a.login_account=? and appid=? ";
			$data=$conn->GetData('dt',$sql,array((string)$login_account,(string)$appid));
			if($data && count($data["dt"]["rows"])==0)
			{
				$result["returncode"]="0000";
				$result["msg"]="";
				$id = SysSeq::GetSeqNextValue($conn,"we_app_userpriv","id");
				$sql="insert into we_app_userpriv(id,login_account,appid,role)values(?,?,?,'0')";
				$conn->ExecSQL($sql,array(
					(string)$id,
					(string)$login_account,
					(string)$appid
				));
				return $this->responseJson($result);
			}
			$result["msg"]="openid is bind.";
		}
		catch(\Exception $e)
	    {
	     	$result = array("returncode"=>"9999","msg"=>$e->getMessage());
	    }
		return $this->responseJson($result);
	}

	public function appUnBindAction($openid,$appid)
	{
		$result = array("returncode"=>"0000","msg"=>"");
		try{
			$conn = $this->get("we_data_access");
			$sql="delete from we_app_userpriv where appid=? and login_account=(select login_account from we_staff where openid=?)";
			$conn->ExecSQL($sql,array(
					(string)$openid,
					(string)$appid
			));
		}
		catch(\Exception $e)
	    {
	     	$result = array("returncode"=>"9999","msg"=>$e->getMessage());
	    }
		return $this->responseJson($result);
	}
	
	private function getLink($uniqid) {
		$web_url=$this->container->getParameter('open_api_url');
		return $web_url.'/api/http/getpagepath/'.$uniqid;
	}

	private function responseJson($data)
	{
		$resp = new Response(json_encode($data));
	    $resp->headers->set('Content-Type', 'text/json');
	   	return $resp;
	}
	
}