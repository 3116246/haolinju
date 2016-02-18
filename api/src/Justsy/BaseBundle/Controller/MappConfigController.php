<?php
namespace Justsy\BaseBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Common\Utils;
use ZipArchive;

class MappConfigController extends Controller
{
	public function indexAction()
	{
		$da = $this->get("we_data_access");
		$request=$this->getrequest();
		$appid=$request->get('appid');
		if($appid=="PORTAL")
		{
			//获取门户。页面为空	
			$rows = array();	
		}
//		else
//		{
//			//获取应用页面列表
//			$sql = "select a.*,b.attrvalue,(select d.attrvalue from we_apps_runtime_attr d,we_map_temp_attrs t where d.attrid=t.attr_id and t.attr_code='uuid' and d.configid=a.id limit 1) uuid from we_apps_config a inner join we_apps_runtime_attr b on a.id=b.configid inner join we_map_temp_attrs c on c.attr_id=b.attrid
//   		       where a.appid=? and attr_code='name' order by a.id asc;";
//			$ds = $da->GetData("appconfig",$sql,array((string)$appid));
//			$rows = $ds["appconfig"]["rows"];
//		}
		return $this->render("JustsyBaseBundle:MappConfig:index.html.twig",array("config"=>json_encode($rows)));
	}

	public function compenteditAction()
	{
		return $this->render("JustsyBaseBundle:MappConfig:componentedit.html.twig",array());
	}

	public function componentAttrEditAction($component)
	{
		if($component=="applist")
		{
			$appcenter = new AppCenterController();
			$appcenter->setContainer($this->container);
			$applist = $appcenter->getAppList();
      		$jsonresult=json_encode($applist);
    		return $this->render("JustsyBaseBundle:MappConfig:componentedit_".$component.".html.twig",array("list"=>$jsonresult));
		}
		else if($component=="publicstyle")
		{
			$datamodule = array();
			$datasource = array();
			$datamodule[] = array("name"=>"所有数据源","id"=>"0");
			return $this->render("JustsyBaseBundle:MappConfig:componentedit_".$component.".html.twig",array("module"=>json_encode($datamodule),"datasource"=>json_encode($datasource)));
		}
		else
			return $this->render("JustsyBaseBundle:MappConfig:componentedit_".$component.".html.twig",array());
	}
	
	//portal高级定制页面
	public function portaladvAction()
	{
		$da = $this->get('we_data_access');
		$request = $this->getrequest();
		$user=$this->get('security.context')->getToken()->getUser();
		$eno=$user->eno;
		
		$sql="select eno,ename,telephone,ewww from we_enterprise where eno=?";
		$params=array($eno);
		$ds=$da->Getdata('info',$sql,$params);
		$enoinfo=$ds['info']['rows'][0];
		
		$result=$this->getAdvResAction();
		$rows=$result->getContent();
		
		$eno_level=$user->eno_level;
		
		$ename=$user->ename;
		
		$isCanPublish=$eno_level=="S"?"1":"0";
		
		return $this->render("JustsyBaseBundle:MappConfig:portaladv2.html.twig",array("rows"=> $rows, "eno"=>$eno, "enoinfo"=> $enoinfo,"isCanPublish"=> $isCanPublish,"ename"=> $ename));
	}

	public function portaladvsaveAction()
	{
		$da = $this->get('we_data_access');
  	$fileElementName="filedata";

		if($appid=="PORTAL")
		{
			$userinfo = $this->get('security.context')->getToken()->getUser();
			$appid = $appid.$userinfo->eno;
		}
	  	$re=array('succeed'=>'1','m'=>'','fileid'=>'','filename'=>'');
	  	try{
						$filename=$_FILES[$fileElementName]['name'];
						$filesize=$_FILES[$fileElementName]['size'];
						$filetemp=$_FILES[$fileElementName]['tmp_name'];
						if($re['succeed']=='1'){
							$fileid=$this->saveCertificate($filetemp,$filename);
							if(empty($fileid))
							{
								$re['succeed']='0';
								$re['msg']='文件上传失败';
								//$fileid="523fe22a7d274a2d01000000";
							}
							if($re['succeed']=='1')
							{
								$sql="insert into we_apps_resource (appid,fileid,name,restype,ressize,cdate) values(?,?,?,?,?,now())";
								$params=array($appid,$fileid,$this->getname($filename),$this->getprex($filename),$filesize);
								if(!$da->ExecSQL($sql,$params))
								{
									$re['succeed']='0';
									$re['msg']='文件保存失败';
								}
								else{
									$re['fileid']=$fileid;
									$re['filename']=$filename;
								}
							}
						}
					}
					catch(\Exception $e){
						var_dump($e->getMessage());
					}
					$response = new Response(json_encode($re));
	     	$response->headers->set('Content-Type', 'text/json');
	     	return $response; 
	}
	protected function saveCertificate($filetemp,$filename)
  {
  	try{
	  	$upfile = tempnam(sys_get_temp_dir(), "we");
	    unlink($upfile);
	    /*
	    $somecontent1 = base64_decode($filedata);
	    if ($handle = fopen($upfile, "w+")) {   
	      if (!fwrite($handle, $somecontent1) == FALSE) {   
	        fclose($handle);  
	      }  
	    }
	    */
	    if(move_uploaded_file($filetemp,$upfile)){
		    $dm = $this->get('doctrine.odm.mongodb.document_manager'); 
		    $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
		    $doc->setName($filename);
		    $doc->setFile($upfile); 
		    $dm->persist($doc);
		    $dm->flush();
		    $fileid = $doc->getId();
		    return $fileid;
		  }
		  else{
		  	return "";
		  }
	  }
	  catch(\Exception $e)
	  {
	  	$this->get('logger')->err($e);
	  	return "";
	  }
  } 
  private function getname($filename)
  {
  	$arr=explode('.',$filename);
  	 return $arr[0];
  }
  private function getprex($filename)
  {
  	 $arr=explode('.',$filename);
  	 return $arr[count($arr)-1];
  }

	//发布并生成xml配置，存储到mongo
	public function publishConfigAction()
	{
		$request = $this->getrequest();
		$appid = $request->get("appid");
		$xmldata = $request->get("xmldata");  //由页面上生成完整的xml串
		try
		{
			$sql = "";
			if($appid=="PORTAL")   //门户配置
	        {
		       $property = $this->publishProtalConfig($xmldata);
	        }
		    else{
					$da = $this->get("we_data_access");
					$sql = "select b.* from we_appcenter_apps b where b.appid=?";
					$ds = $da->GetData("t",$sql,array((string)$appid));
	
					$data = $ds["t"]["rows"][0];
	
					$fileid = $ds["t"]["rows"][0]["configfileid"];
					if(!empty($fileid))
					{
						$this->removeFile($fileid);
					}
					//生成新的版本号
					$version = $data["version"];
					if(empty($version)) $version=1;
					else $version= (int)$version+1;
					$data["version"] = $version;
					//生成xml文件 
					$path = "/tmp/".$appid.".xml";//$this->getXML($da,$ds["t"]["rows"][0]);
					$cont =fopen($path,'w');
					fwrite($cont,$xmldata);
					fclose($cont);
					$fileid=$this->saveFile($path);
					$sql = "update we_appcenter_apps set configfileid=?,version=? where appid=?";
					$da->ExecSQL($sql,array((string)$fileid,(string)$version,(string)$appid));
				    //$this->updateAppsPublic($appid,$fileid,$version);			  
					$property = array("s"=>"1");
			  }
		}
		catch(\Exception $e)
		{
			$this->get('logger')->err($e);
			$property = array("s"=>0,"msg"=> $e->getMessage());
		}
	  $result = new Response(json_encode($property));
	  $result->headers->set('Content-Type', 'text/json');
		return $result;
	}

	//发布并生成门户xml配置，存储到mongo
	public function publishProtalConfigAction()
	{
		$request = $this->getrequest();
		$version = 1;
		$xmldata = $request->get("xmldata");  //由页面上生成完整的xml串
		$property = $this->publishProtalConfig($xmldata);
	  	$result = new Response(json_encode($property));
  		$result->headers->set('Content-Type', 'text/json');
		return $result;
	}
	public function publishProtalConfig($xmldata)
	{
		$version = 1;
		try
		{
			$eno = $this->get('security.context')->getToken()->getUser()->eno;
			$da = $this->get("we_data_access");
			$sql = "select b.* from we_apps_portalconfig b where b.eno=? and b.appid=?";
			$ds = $da->GetData("t",$sql,array((string)$eno,(string)$eno));
			$fileid="";
			$version="";
			if(count($ds["t"]["rows"])>0)
			{
				$data = $ds["t"]["rows"][0];

				$fileid = $ds["t"]["rows"][0]["configfileid"];
				if(!empty($fileid))
				{
					$this->removeFile($fileid);
				}
				//生成新的版本号
				$version = $data["version"];
				if(empty($version)) $version=1;
				else $version= (int)$version+1;
				$data["version"] = $version;
			}
			else
			{
				//第一次保存配置，先新增
				$sql = "insert into we_apps_portalconfig(eno,configfileid,version,makedate,appid)values(?,'','0',now(),?)";
				$da->ExecSQL($sql,array((string)$eno,(string)$eno));
			}
			//生成xml文件 
			$path = "/tmp/".$eno.".xml";//$this->getXML($da,$ds["t"]["rows"][0]);
			$cont =fopen($path,'w');
			fwrite($cont,$xmldata);
			fclose($cont);
			$fileid=$this->saveFile($path);
			$sql = "update we_apps_portalconfig set configfileid=?,version=?,makedate=now() where eno=? and appid=?";
			$da->ExecSQL($sql,array((string)$fileid,(string)$version,(string)$eno,(string)$eno));
			$property = array("s"=>"1");
			//$this->updateAppsPublic($eno,$fileid,$version);
		}
		catch(\Exception $e)
		{
			$this->get('logger')->err($e);
		    $property = array("s"=>0,"msg"=> $e->getMessage());
		}	  	
		return $property;
	}	
	
	
	public function updateAppsPublic($appid,$fileid,$version)
	{ 
		 $da = $this->get("we_data_access");
		 $parameter = Array();
		 $sql = "select appid from we_apps_publish where appid=?";
		 $ds = $da->GetData("exists",$sql,array((string)$appid));
		 if ($ds && $ds["exists"]["recordcount"]==0){
			 $user = $this->get('security.context')->getToken()->getUser();
			 $staff = $user->getUserName();
			 $id = SysSeq::GetSeqNextValue($da,"we_apps_publish","id");
		   $sql ="insert into we_apps_publish(id,appid,configfileid,publishdate,publishstaff,publishstate,publishversion)value(?,?,?,now(),?,0,?)";
			 $parameter = array((string)$id,(string)$appid,(string)$fileid,(string)$staff,(string)$version);		 	 
		 }
		 else{
		 	 $sql = "update we_apps_publish set publishstate=0 where appid=?";
		 	 $parameter = array((string)$appid);
		 }
		 $da->ExecSQL($sql,$parameter);
	}
	
	//发布应用
	public function appPublishAction()
	{
		$request = $this->getrequest();
		$appid = $request->get("appid");
		$result = $this->appPublish($appid);
		$resp = new Response(json_encode($result));
		$resp->headers->set('Content-Type', 'text/json');
		return $resp;
	}

	public function appPublish($appid)
	{
 		$result = array("s"=>true);
	   	try
	   	{
				
				$user = $this->get('security.context')->getToken()->getUser();
				$eno=  $user->eno;
				$staff = $user->getUserName();
				$da = $this->get("we_data_access");
				$caption  = "";
				if($appid=="PORTAL")
				  $caption  = "portal_publish";
				else
				  $caption  = "app_publish";
				if($appid=="PORTAL")
				{
					$appid = $eno;					
					$sql = "select * from we_apps_portalconfig where appid=?";
				}
				else
				{					
					$sql = "select * from we_appcenter_apps where appid=?";
				}
				$dataset= $da->GetData("t",$sql,array((string)$appid));
				//判断配置文件是否有编辑或者更改，没有变化时不用发布
				$sql = "select publishversion from we_apps_publish where appid=? order by id+0 desc limit 0,1";
				$versiondt = $da->GetData("t_v",$sql,array((string)$appid));
				
				$version =count($dataset["t"]["rows"])==0? 0 : $dataset["t"]["rows"][0]["version"];
				$publishversion_max = $versiondt["t_v"]["recordcount"] > 0 ? $versiondt["t_v"]["rows"][0]["publishversion"] : "";
				if($version==$publishversion_max)
				{
					$result = array("s"=>false,"msg"=> "当前版本无更新");
				}
				else
				{
					//获取原配置文件生成用于发布的xml文件
					//对门户android文件的处理
					$doc = $this->get('doctrine.odm.mongodb.document_manager')
								->getRepository('JustsyMongoDocBundle:WeDocument')
								->find($dataset["t"]["rows"][0]["configfileid"]);
					if($doc==null)
					{
						return array("s"=>false,"msg"=> "配置文件不存在");
					}
					$xmldata=$doc->getFile()->getBytes();

					$path = "/tmp/".$appid."_publish.xml";
					$cont =fopen($path,'w');
					fwrite($cont,$xmldata);
					fclose($cont);
					$fileid=$this->saveFile($path);
					//对门户ios文件的处理
					$ios_fileid = null;	
					if($appid == $eno)
					{
							$ios_fileid = $dataset["t"]["rows"][0]["ios_configfileid"];							
							if ( !empty($ios_fileid))
							{
								$doc = $this->get('doctrine.odm.mongodb.document_manager')
										 ->getRepository('JustsyMongoDocBundle:WeDocument')
										 ->find($ios_fileid);
							    if(empty($doc))
							    {
							    	$ios_fileid = $fileid;
							    }
								else
								{

								  	$xmldata=$doc->getFile();
								  	if(empty($xmldata )) $ios_fileid = $fileid;
								  	else
								  	{
									  	$xmldata = $xmldata->getBytes();		
										$path = "/tmp/".$appid."ios_publish.xml";
										$cont =fopen($path,'w');
										fwrite($cont,$xmldata);
										fclose($cont);
										$ios_fileid = $this->saveFile($path);	
									}
								}
						  }
					}
					$id = SysSeq::GetSeqNextValue($da,"we_apps_publish","id");
					$sqls = array();
					$paras = array();
				  	//更改发布状态
				  	$sql = "update we_apps_publish set publishstate=0 where appid=?";
				  	$parameter = array((string)$appid);
				  	array_push($sqls,$sql);
				  	array_push($paras,$parameter);							
					//添加发布信息
					$sql ="insert into we_apps_publish(id,appid,configfileid,ios_configfileid,publishdate,publishstaff,publishstate,publishversion)value(?,?,?,?,now(),?,1,?)";
				  	$parameter = array((string)$id,(string)$appid,(string)$fileid,$ios_fileid,(string)$staff,(string)$version);
				  	array_push($sqls,$sql);
				  	array_push($paras,$parameter);
				  	//更改应用表最新发布日期及发布人员
				  	$sql = "update we_appcenter_apps set publishdate=now(),publishstaff=? where appid=?";
				  	$parameter = array((string)$user->nick_name,(string)$appid);
				  	array_push($sqls,$sql);
				  	array_push($paras,$parameter);
					$da->ExecSQLs($sqls,$paras);
					
					$cacheupdate = new \Justsy\BaseBundle\Management\App($this->container);					
					$cacheupdate->refreshPortal(array("eno"=>$eno));
					
					//成功后返回的内容
					$sql = "select date_format(date_add(publishdate,interval 8 hour),'%Y-%m-%d %H:%i') publishdate from we_apps_publish where appid=? order by id+0 desc limit 1";
					$ds = $da->GetData("date",$sql,array((string)$appid));
					$date = $ds["date"]["rows"][0]["publishdate"];
					//发送出席
				$sql = "select fafa_jid from we_staff where state_id!=3 and eno=?";
		      	$ds = $da->GetData("jid",$sql,array((string)$eno));
		      	$tojid = array();
		      	$message = $version;
		      	if ( $ds && $ds["jid"]["recordcount"]>0){
		  	    	for($i=0;$i<$ds["jid"]["recordcount"];$i++){
			  		    array_push($tojid,$ds["jid"]["rows"][$i]["fafa_jid"]);
			  		    if(count($tojid)>200)
			  		    {
			  		    	Utils::sendImPresence($this->container->getParameter('im_sender'),implode(",",$tojid),$caption,$message,$this->container,"","",false,Utils::$systemmessage_code);
			  		    	$tojid = array();
			  		    }
		  	    	}
		      	}
          	  	if(count($tojid)>0)
  		    		Utils::sendImPresence($this->container->getParameter('im_sender'),implode(",",$tojid),$caption,$message,$this->container,"","",false,Utils::$systemmessage_code);
  		    	//近回结果//近回结果
			    $result = array("s"=>true,"date"=>$date,"staff"=>$user->nick_name,"version"=>$version,"fileid"=>$fileid);
				}
		 }
		catch(\Exception $e)
		{
			$this->get('logger')->err($e);
		 	$result = array("s"=>false,"msg"=> $e->getMessage());		 	
		}
		return $result;
	}
	
	public function resourcemgrAction($appid)
	{
		$da = $this->get("we_data_access");
		if($appid=="PORTAL")
		{
			$userinfo = $this->get('security.context')->getToken()->getUser();
			$appid = $appid.$userinfo->eno;
		}
		$sql = "select * from we_apps_resource where appid=? order by name";
		$ds = $da->GetData("t",$sql,array((string)$appid));
		$request = $this->getrequest();
		$dlg = $request->get("dlg");
		return $this->render('JustsyBaseBundle:MappConfig:resmgr.html.twig',array('dlg'=>$dlg,'appid'=> $appid,"list"=>$ds["t"]["rows"]));
	}

	private function saveFile($path)
	{
		$dm = $this->get('doctrine.odm.mongodb.document_manager');
	    $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
	    $doc->setName(basename($path));
	    $doc->setFile($path);
	    $dm->persist($doc);
	    $dm->flush();
	    unlink($path);
	    return $doc->getId();
	}

	  
	private function removeFile($path)
	{
	    if (!empty($path))
	    {
	    	$dm = $this->get('doctrine.odm.mongodb.document_manager');
	        $doc = $dm->getRepository('JustsyMongoDocBundle:WeDocument')->find($path);
	        if(!empty($doc))
	            $dm->remove($doc);
	        $dm->flush();
	    }
	    return true;
	}
		
	//上传门户配置文件
	public function uploadPortalsFileAction()
  {
  	$error = "";
		$msg = "";
		$request = $this->get("request");
		$da = $this->get('we_data_access');
		$ds = null;$oldfileid="";$version = "";
		$add = true;
		$success = true;$msg="";
		$android_fileid = "";$ios_fileid="";
		try
		{
			$type = $request->get("filetype");
			$type = explode(",",$type);
			$data = $this->upload($type);
			for($i=0;$i < count($data);$i++)
			{
				$object = $data[$i];
				$type = $object["type"];
				$fileid = $object["fileid"];
				if ( empty($fileid)){
					$success = false;
				  $msg ='文件上传失败';
				}
				else{
					//取出旧文件
					if ( $ds == null){
	          $eno = $this->get('security.context')->getToken()->getUser()->eno;
					  $sql="select version, configfileid,ios_configfileid from we_apps_portalconfig where eno=? and ifnull(configfileid,'')!=''";
					  $params = array((string)$eno);
					  $ds = $da->Getdata('fileid',$sql,$params);
				  }
					if($ds['fileid']['recordcount']>0){  //有记录存在
						if ( $type=="ios")
						  $oldfileid=$ds['fileid']['rows'][0]['ios_configfileid'];
						else
						  $oldfileid=$ds['fileid']['rows'][0]['configfileid'];
	          $version = $ds['fileid']['rows'][0]["version"];
	          $add = false;
					}
					if(!empty($oldfileid)){
						//删除monggo
						$this->removeFile($oldfileid);
					}
	        $version = empty($version) ? 1 : (int)$version + 1;  
	        $params = Array();
	        if ( $add ){
	        	 $sql="insert into we_apps_portalconfig(eno,version,configfileid,ios_configfileid,makedate,appid)values(?,1,?,?,now(),?)";
	        	 if ( $type=="ios")
	        	 {
	        	 	 $params = array((string)$eno,null,(string)$fileid,(string)$eno);
	        	 }
	        	 else {
	        	   $params = array((string)$eno,(string)$fileid,null,(string)$eno);
	        	 }
	        }
	        else{
	        	 if ($type=="ios")
	        	   $sql="update we_apps_portalconfig set ios_configfileid=?,version=? where eno=? and appid=?";
	        	 else
	        	   $sql="update we_apps_portalconfig set configfileid=?,version=? where eno=? and appid=?";
	        	 $params = array((string)$fileid,(string)$version,(string)$eno,(string)$eno);
	        }
					if(!$da->ExecSQL($sql,$params))
				  {
				  	$success = false;
				    $msg ='文件保存失败';
				  }
				  if ( $type=="ios")
				    $ios_fileid = $fileid;
				  else
				    $android_fileid = $fileid;
				}
			}
		}
		catch(\Exception $e){
			$success = false;
			$msg = "上传文件失败！";
			$this->get('logger')->err($e);
		}
		$result = array("success"=>$success,"android_fileid"=>$android_fileid,"ios_fileid"=>$ios_fileid,"msg"=>$msg);
		$response = new Response(json_encode($result));
    $response->headers->set('Content-Type', 'text/html');
    return $response;
  }
  
  //上传配置文件
  private function upload($type)
  {
  	$result = array();
  	for($i=0;$i<count($type);$i++)
  	{  		
	  	$fileElementName = $type[$i];
			$da = $this->get('we_data_access');
			try
			{
				$filename=$_FILES[$fileElementName]['name'];
				$filesize=$_FILES[$fileElementName]['size'];
				$filetemp=$_FILES[$fileElementName]['tmp_name'];
				$fileid=$this->saveCertificate($filetemp,$filename);
				if(empty($fileid))  //上传失败
				{
					if ( $fileElementName=="file_ios")
					  array_push($result,array("type"=>"ios","fileid"=>"","msg"=>"IOS文件上传失败！"));
				  else
				    array_push($result,array("type"=>"android","fileid"=>"","msg"=>"android文件上传失败！"));
				}
				else{
					if ( $fileElementName=="file_ios")
					  array_push($result,array("type"=>"ios","fileid"=>$fileid,"msg"=>"IOS文件上传成功！"));
				  else
				    array_push($result,array("type"=>"android","fileid"=>$fileid,"msg"=>"android文件上传成功！"));
				}
			}
			catch(\Exception $e){
				if ( $fileElementName=="file_ios")
					array_push($result,array("type"=>"ios","fileid"=>"","msg"=>"IOS文件上传失败！"));
				else
				  array_push($result,array("type"=>"android","fileid"=>"","msg"=>"android文件上传失败！"));				
			}
			@unlink($_FILES[$fileElementName]);
	  }
		return $result;
  }
  
  //获得门户配置文件id
  public function getPortalsFileAction()
  {
  	$result = array("android_fileid"=>"","ios_fileid"=>"");
		$da = $this->get('we_data_access');
		$eno = $this->get('security.context')->getToken()->getUser()->eno;
		$sql="select ifnull(configfileid,'') android_fileid,ifnull(ios_configfileid,'') ios_fileid from we_apps_portalconfig where eno=? and appid=? and ( ifnull(configfileid,'')!='' or ifnull(ios_configfileid,'')!='')";
		$ds = $da->GetData("portal",$sql,array((string)$eno,(string)$eno));
    if($ds['portal']['recordcount']>0){
    	 $android_fileid = $ds['portal']['rows'][0]['android_fileid'];
    	 $ios_fileid = $ds['portal']['rows'][0]['ios_fileid'];
    	 $result = array("android_fileid"=>$android_fileid,"ios_fileid"=>$ios_fileid);
	  }
		$response = new Response(json_encode($result));
    $response->headers->set('Content-Type', 'text/html');
    return $response;
  }
  
  //获得发布历史
  public function getPublishHistoryAction(Request $request)
  {
  	 $appid = $request->get("appid");
		 $da = $this->get("we_data_access");
		 if($appid=="PORTAL")
		 {
		 	  $user = $this->get('security.context')->getToken()->getUser();
				$appid=  $user->eno;
		 }
  	 $sql = "select date_format(date_add(publishdate,interval 8 hour),'%Y-%m-%d %H:%i') as publishdate,nick_name as publishstaff,publishversion,ifnull(configfileid,'') as configfileid,ifnull(ios_configfileid,'') ios_configfileid 
             from we_apps_publish a inner join we_staff b on publishstaff=login_account where appid=? 
             order by cast(publishversion as signed integer) desc,publishdate desc;";
     $ds = $da->GetData("publish",$sql,array((string)$appid));
     $result = $ds["publish"]["rows"];
	   $result = new Response(json_encode($result));
	   $result->headers->set('Content-Type', 'text/json');
		 return $result;
  }
  public function getAdvResAction()
  {
  	$da = $this->get('we_data_access');
  	$request = $this->getrequest();
  	$restype=$request->get("restype");
  	$id=$request->get("id");
  	$resvalue=$request->get("resvalue");
  	$device=$request->get("device");
  	$order=$request->get("order");
  	$appid="PORTAL";
  	$eno = $this->get('security.context')->getToken()->getUser()->eno;
  	
  	$sql="select * from we_app_portal_advconfig where eno=? ";
  	$params=array();
  	array_push($params,$eno);
  	if(!empty($id)){
  		$sql.=" and id=?";
  		array_push($params,$id);
  	}
  	if(!empty($restype)){
  		$sql.=" and restype=?";
  		array_push($params,$restype);
  	}
  	if(!empty($device)){
  		$sql.=" and device=?";
  		array_push($params,$device);
  	}
  	if(!empty($order)){
  		$sql.=" and order=?";
  		array_push($params,$order);
  	}
  	if(!empty($appid)){
  		$sql.=" and appid=?";
  		array_push($params,$appid);
  	}
  	$ds=$da->Getdata('info',$sql,$params);
  	$response = new Response(json_encode($ds['info']['rows']));
    $response->headers->set('Content-Type', 'text/html');
    return $response;
  }
  public function delAdvResAction()
  {
  	$request = $this->getrequest();
  	$da = $this->get('we_data_access');
  	$restype=$request->get("restype");
  	$id=$request->get("id");
  	$resvalue=$request->get("resvalue");
  	$device=$request->get("device");
  	$order=$request->get("order");
  	$error = "";
		$msg = "";
		$result = array('s'=>'1','msg'=>'',"fileid"=>'',"filename"=>'');
		
		$eno = $this->get('security.context')->getToken()->getUser()->eno;
				$sql="select resvalue from we_app_portal_advconfig where id=?";
				$params = array((string)$id);
				$oldfileid="";
				$ds=$da->Getdata('fileid',$sql,$params);
				if($ds['fileid']['recordcount']>0){
					$oldfileid=$ds['fileid']['rows'][0]['resvalue'];
				}
				if(!empty($oldfileid)){
					//删除monggo
					$this->removeFile($oldfileid);
				}
		$response = new Response(json_encode($result));
    $response->headers->set('Content-Type', 'text/html');
    return $response;
  }
  public function imgCreate($fileid,$arr,$id,$restype,$resvalue,$order){
  	$eno = $this->get('security.context')->getToken()->getUser()->eno;
  	//获取图片资源并存到本地
		$doc = $this->get('doctrine.odm.mongodb.document_manager')
					->getRepository('JustsyMongoDocBundle:WeDocument')
					->find($fileid);
		$imgdata=$doc->getFile()->getBytes();
		$imgname=$doc->getName();
		
		//文件后缀
		$imgpre=explode('.',$imgname);
		$imgpres=$imgpre[count($imgpre)-1];
		
		$path = "/tmp/".$eno."_tmp.".$imgpres;//$this->getXML($da,$ds["t"]["rows"][0]);
		$cont =fopen($path,'w');
		fwrite($cont,$imgdata);
		fclose($cont);
		
		list($width,$height)=getimagesize($path);
		$img=null;
		if(strtoupper($imgpres)=="PNG"){
			$img=imagecreatefrompng($path);
		}
		else if(strtoupper($imgpres)=="JPEG"){
			$img=imagecreatefromjpeg($path);
		}
		if($img!=null){
			for($i=0;$i< count($arr);$i++){
				$width_new=$arr[$i]["width"];
				$height_new=$arr[$i]["height"];
				if($arr[$i]["restype"]=="logo"){
					$width_new=min((int)$width_new,(int)$width);
					$height_new=min((int)$height_new,(int)$height);
				}
				$new=imagecreatetruecolor($width_new,$height_new);
				imagecopyresized($new,$img,0,0,0,0,$width_new,$height_new,$width,$height);
				$newpath="/tmp/".$eno."_tmp_new.".$imgpres;
				if(strtoupper($imgpres)=="PNG"){
					imagepng($new,$newpath);
				}
				else if(strtoupper($imgpres)=="JPEG"){
					imagejpeg($new,$newpath);
				}
				$fileid=$this->saveFile($newpath);
				$arr[$i]["resvalue"]=$fileid;
				imagedestroy($new);
			}
			imagedestroy($img);
		}
		else{
			$arr=array();
			//$arr=array("id"=>$id,"restype"=>$restype,"resvalue"=>$resvalue,"order"=>$order);
		}
		unlink($path);
		return $arr;
  }
  public function getArrByResType($restype,$id,$resvalue,$order,$appid)
  {
  	$eno = $this->get('security.context')->getToken()->getUser()->eno;
  	$arr=array();
  	$da = $this->get('we_data_access');
  	if($restype=="icon120" || $restype=="icon60" || $restype=="icon58" || $restype=="icon29"){
  		$arr[]=array(
  			"id"=>$id,
  			"restype"=>"icon120",
  			"resvalue"=>$resvalue,
  			"order"=>$id,
  			"width"=>"120",
  			"height"=>"120"
  		);
  		$arr[]=array(
  			"id"=>$id,
  			"restype"=>"icon60",
  			"resvalue"=>$resvalue,
  			"order"=>"",
  			"width"=>"60",
  			"height"=>"60"
  		);
  		$arr[]=array(
  			"id"=>$id,
  			"restype"=>"icon58",
  			"resvalue"=>$resvalue,
  			"order"=>"",
  			"width"=>"58",
  			"height"=>"58"
  		);
  		$arr[]=array(
  			"id"=>$id,
  			"restype"=>"icon29",
  			"resvalue"=>$resvalue,
  			"order"=>"",
  			"width"=>"29",
  			"height"=>"29"
  		);
  		$sql="select * from we_app_portal_advconfig where restype in(?,?,?,?) and appid=? and eno=?";
  		$params=array("icon120","icon60","icon58","icon29",$appid,$eno);
  		$ds=$da->Getdata("info",$sql,$params);
  		$rows=$ds['info']["rows"];
  		foreach($rows as $row){
  			for($i=0;$i<count($arr);$i++){
  				if($row["restype"]==$arr[$i]["restype"]){
  					$arr[$i]["id"]=$row["id"];
  					$arr[$i]["order"]=$row["order"];
  					$arr[$i]["resvalue"]=$row["resvalue"];
  					break;
  				}
  			}
  		}
  	}
  	else if($restype=="start_iphone4"){
  		$arr[]=array(
  			"id"=> $id,
  			"restype"=>"start_iphone4",
  			"resvalue"=> $resvalue,
  			"order"=> $order,
  			"width"=>"640",
  			"height"=>"960"
  		);
  	}
  	else if($restype=="start_iphone5"){
  		$arr[]=array(
  			"id"=> $id,
  			"restype"=>"start_iphone5",
  			"resvalue"=> $resvalue,
  			"order"=> $order,
  			"width"=>"640",
  			"height"=>"1136"
  		);
  	}
  	else if($restype=="start_android"){
  		$arr[]=array(
  			"id"=> $id,
  			"restype"=>"start_android",
  			"resvalue"=> $resvalue,
  			"order"=> $order,
  			"width"=>"720",
  			"height"=>"1280"
  		);
  	}
  	else if($restype=="guide640_960"){
  		$arr[]=array(
  			"id"=> $id,
  			"restype"=>"guide640_960",
  			"resvalue"=> $resvalue,
  			"order"=> $order,
  			"width"=>"640",
  			"height"=>"960"
  		);
  	}
  	else if($restype=="guide640_1138"){
  		$arr[]=array(
  			"id"=> $id,
  			"restype"=>"guide640_1138",
  			"resvalue"=> $resvalue,
  			"order"=> $order,
  			"width"=>"640",
  			"height"=>"1138"
  		);
  	}
  	else if($restype=="guide720_1280"){
  		$arr[]=array(
  			"id"=> $id,
  			"restype"=>"guide720_1280",
  			"resvalue"=> $resvalue,
  			"order"=> $order,
  			"width"=>"720",
  			"height"=>"1280"
  		);
  	}
  	else if($restype=="logo"){
  		$arr[]=array(
  			"id"=> $id,
  			"restype"=>"logo",
  			"resvalue"=> $resvalue,
  			"order"=> "",
  			"width"=>"600",
  			"height"=>"200"
  		);
  	}
  	return $arr;
  }
  public function setAdvResAction()
  {
  	$request = $this->getrequest();
  	
  	$res=$request->get("res");
  	$result = array('s'=>'1','msg'=>'',"arr"=>array());
  	$da = $this->get('we_data_access');
  	for($j=0;$j< count($res);$j++){
  		$restype=$res[$j]["restype"];
	  	$id=$res[$j]["resid"];
	  	$resvalue=$res[$j]["resvalue"];
	  	$order=$res[$j]["order"];
	  	$appid=$res[$j]["appid"];
	  	$error = "";
			$msg = "";
			try
			{
				if($restype!="identify" && $restype!="appname" && $restype!="iosversion" && $restype!="androidversion" && $restype!="iosdesc" && $restype!="androiddesc"){
					//获取图片资源并存到本地
					$doc = $this->get('doctrine.odm.mongodb.document_manager')
								->getRepository('JustsyMongoDocBundle:WeDocument')
								->find($resvalue);
					$imgname=$doc->getName();
					
					//文件后缀
					$imgpre=explode('.',$imgname);
					$imgpres=$imgpre[count($imgpre)-1];
					if(strtoupper($imgpres)!="PNG"){
						$response = new Response(json_encode(array('s'=>'0','msg'=>'请选择一张PNG格式的图片')));
				    $response->headers->set('Content-Type', 'text/json');
				    return $response;
					}
				}
				$arr;
				if($restype=="identify" || $restype=="appname" || $restype=="iosversion" || $restype=="androidversion" || $restype=="iosdesc" || $restype=="androiddesc"){
					$arr=array(array("id"=>$id,"restype"=>$restype,"resvalue"=>$resvalue,"order"=>$order));
				}
				else{
					$arr=$this->imgCreate($resvalue,$this->getArrByResType($restype,$id,$resvalue,$order,$appid),$id,$restype,$resvalue,$order);
				}
				for($i=0;$i<count($arr);$i++){
					$oldfileid="";
					$id=$arr[$i]["id"];
					$restype=$arr[$i]["restype"];
					$resvalue=$arr[$i]["resvalue"];
					$order=$arr[$i]["order"];
					//取出旧文件
	        $eno = $this->get('security.context')->getToken()->getUser()->eno;
					$sql="select resvalue from we_app_portal_advconfig where id=?";
					$params = array((string)$id);
					$ds=$da->Getdata("info",$sql,$params);
					if($ds["info"]["recordcount"]>0){
						$oldfileid=$ds["info"]["rows"][0]["resvalue"];
					}
					if(!empty($oldfileid) && $arr[$i]["restype"]!="identify"){
						//删除monggo
						$this->removeFile($oldfileid);
					}       
	        $params = Array();
	        if(empty($id)){
	        	$id=SysSeq::GetSeqNextValue($da,"we_app_portal_advconfig","id");
	        	$sql="insert into we_app_portal_advconfig (id,appid,restype,resvalue,resorder,eno) values(?,?,?,?,?,?)";
	        	$params=array(
	        		$id,
	        		$appid,
	        		$restype,
	        		$resvalue,
	        		$order,
	        		$eno
	        	);
	        }
	        else{
	        	$sql="update we_app_portal_advconfig set resvalue=? where id=?";
	        	$params=array($resvalue,$id);
	        }
					$da->ExecSQL($sql,$params);
					$result["arr"][]=array(
						"id"=>$id,
						"restype"=>$restype,
						"resvalue"=>$resvalue,
						"order"=>$order
					);
				}
			}
			catch(\Exception $e){
			}
  	}
		$response = new Response(json_encode($result));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  
  public function portalsHomeAction()
  {
  	 $data = $this->getPortalinfo();
  	 $count = $data["count"];
  	 $icon  = $data["icon"];
  	 $appname = $data["appname"];
  	 return $this->render("JustsyBaseBundle:MappConfig:portalshome.html.twig",array("appcount"=>$count,"appicon"=>$icon,"appname"=>$appname));
  }
  
  private function getPortalinfo()
  {
  	 $da = $this->get("we_data_access");
  	 $user = $this->get('security.context')->getToken()->getUser();
  	 $eno = $user->eno;
	 	 $result["count"] = 0;
	 	 $result["icon"] = "wefafa";
	 	 $result["appname"] = "";
	 	 $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
  	 //应用数量
  	 $sql = "select count(*) `count` from we_appcenter_apps where apptype like '99%' and appdeveloper=?";
  	 $ds = $da->GetData("table",$sql,array((string)$eno));
  	 if ($ds && $ds["table"]["recordcount"]>0)
  	    $result["count"] = $ds["table"]["rows"][0]["count"];
  	 //应用名称及图标
  	 $sql ="select resvalue,restype from we_app_portal_advconfig where  (restype='appname' or restype='icon60')  and (eno=? and appid='PORTAL')";
  	 $ds = $da->GetData("eno",$sql,array((string)$eno));
  	 if ( $ds && $ds["eno"]["recordcount"]>0) {
  	 	 for($i = 0;$i < $ds["eno"]["recordcount"];$i++){
  	 	 	 $filedvalue = $ds["eno"]["rows"][$i]["restype"];
  	 	 	 if ($filedvalue == "icon60")
  	 	 	   $result["icon"] = $FILE_WEBSERVER_URL.$ds["eno"]["rows"][$i]["resvalue"];
  	 	 	 else if ($filedvalue =="appname")
  	 	 	   $result["appname"] = $ds["eno"]["rows"][$i]["resvalue"];
  	 	 }
  	 }
  	 if ($result["appname"]=="")
  	   $result["appname"] = "Wefafa";
  	 return $result;
  }
  
  public function protalsStaffAction()
  {
  	 $request = $this->getrequest();
  	 $pageindex = $request->get("pageindex");  	
  	 $recordrow = $request->get("recordrow"); 
  	 $data = $this->getPortalsUser($pageindex,$recordrow);
  	 $response = new Response(json_encode($data));  	 
     $response->headers->set('Content-Type', 'text/json');
     return $response;
  }
  
  //获得门户首页用户数据
  private function getPortalsUser($pageindex,$recordrow)
  {
  	 $da = $this->get("we_data_access");
  	 $user = $this->get('security.context')->getToken()->getUser();
  	 $eno = $user->eno;
  	 $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
  	 $limit = " limit ".(($pageindex - 1) * $recordrow).",".$recordrow;
  	 $sql = "select login_account,nick_name,case when photo_path is null or photo_path='' then '' else concat('$FILE_WEBSERVER_URL',photo_path) end  photo_path,
  	                ifnull(instr((select manager from we_circle where enterprise_no=? limit 1),login_account),0) manager
  	        from we_staff a where state_id='1' and not exists (select 1 from we_micro_account m where a.login_account= m.number) and eno=? order by manager desc ".$limit;
  	 $ds = $da->GetData("staff",$sql,array((string)$eno,(string)$eno));
  	 $result["staff"] = $ds["staff"]["rows"];
  	 //如果为第一页时则计算总记录数
  	 if ($pageindex == 1 ){
  	 	 $sql = "select count(*) staffcount from we_staff a where not exists (select 1 from we_micro_account m where a.login_account= m.number) and eno=?";
  	 	 $ds  = $da->GetData("table",$sql,array((string)$eno));
  	 	 if ( $ds && $ds["table"]["recordcount"]>0 )
  	 	   $result["recordcount"] = $ds["table"]["rows"][0]["staffcount"];
  	 }
  	 else{
  	 	 $result["recordcount"] = 0;
  	 }
  	 return $result;
  }

  public function publishPortalAction()
  {
  	$da = $this->get("we_data_access");
  	$user = $this->get('security.context')->getToken()->getUser();
  	$request = $this->getrequest();
  	$result = array('s'=>'1','msg'=>'');
  	
  	// $appid=$request->get("appid");
  	$appid = "PORTAL";
  	$device=$request->get("device");// ios/android
  	$eno=$user->eno;
  	$restype = $device == "ios" ? "iosdeploying" : "androiddeploying";
  	
  	// 判断是否已开始发布
	$sql = "select count(*) c from we_app_portal_advconfig where eno=? and appid=? and restype in ('androiddeploying', 'iosdeploying') and resvalue='1'";
	$params = array();
	$params[] = (string)$eno;
	$params[] = (string)$appid; 
    $ds = $da->Getdata("res",$sql,$params);
    if ($ds["res"]["rows"][0]["c"] > 0)
    { 
	  	$response = new Response(json_encode($result));
	    $response->headers->set('Content-Type', 'text/json');
	    return $response;
    }

    //  start
	$sqls = array();
	$all_params = array();
	
	$sql = "delete from we_app_portal_advconfig where eno=? and appid=? and restype=?";
	$params = array();
	$params[] = (string)$eno;
	$params[] = (string)$appid;
	$params[] = (string)$restype;

    $sqls[] = $sql;
    $all_params[] = $params;

	$sql = "insert into we_app_portal_advconfig (id, resvalue, restype, device, appid, eno, resorder) values (?, ?, ?, null, ?, ?, null)";
	$params = array();
	$params[] = (string)SysSeq::GetSeqNextValue($da,"we_app_portal_advconfig","id");
	$params[] = (string)"1";
	$params[] = (string)$restype;
	$params[] = (string)$appid;
	$params[] = (string)$eno;

    $sqls[] = $sql;
    $all_params[] = $params;

    $da->ExecSQLs($sqls, $all_params); 

  	//获取用户设置的应用资源
  	// $sql="select * from we_app_portal_advconfig where appid=? and eno=?";
  	// $params=array($appid,$eno);
  	// $ds=$da->Getdata("res",$sql,$params);
  	
  	// 开始编译
  	if ($device == "ios")
  	{
  		$ip = "localhost";
  		$this->getUrlContent("http://$ip/appdeploy/ios?eno=$eno");
  	}
  	else
  	{
  		$ip = "192.168.10.101:8008";
  		$this->getUrlContent("http://$ip/appdeploy/android?eno=$eno");
  	}
  	
  	$response = new Response(json_encode($result));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }

  public static function getUrlContent($url,$logger=null) 
  {
    try
    {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT,10);
      curl_setopt($ch, CURLOPT_FORBID_REUSE, true); //处理完后，关闭连接，释放资源
      $content = curl_exec($ch);
      if ($logger) $logger->alert("getUrlContent:".$content);
      return $content;
    }
    catch(\Exception $e)
    {
      if ($logger) $logger->err($e);
      return null;
    }
  }

  public function checkPublishPortalAction()
  {
  	$da = $this->get("we_data_access");
  	$user = $this->get('security.context')->getToken()->getUser();
  	$request = $this->getrequest();
  	$result = array('s'=>'1','msg'=>'');
  	 
  	$appid = "PORTAL"; 
  	$eno=$user->eno;

	$sql = "select count(*) c from we_app_portal_advconfig where eno=? and appid=? and restype in ('androiddeploying', 'iosdeploying') and resvalue='1'";
	$params = array();
	$params[] = (string)$eno;
	$params[] = (string)$appid; 

    $ds = $da->Getdata("res",$sql,$params);

    if ($ds["res"]["rows"][0]["c"] > 0)
    {
    	$result["IsComplete"] = "0";
    }
    else
    {
    	$result["IsComplete"] = "1";
    }
  	
  	$response = new Response(json_encode($result));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }

  public function publishPortalDownAction($eno)
  {
	//是否手机
	if(isset($_SERVER['HTTP_USER_AGENT'])) 
	{
		$userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
		$clientkeywords = array('nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu', 'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'opera mobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile');
		if(preg_match("/(".implode('|', $clientkeywords).")/i", $userAgent))
		{
			// 是否微信
			if (strpos($userAgent, 'micromessenger') !== false) 
			{
				return $this->publishPortalDown_WXAction($eno);
			}
			else
			{
				// 是否IOS
				$clientkeywords = array("ios", "iphone", "ipad", "ipod");
				if(preg_match("/(".implode('|', $clientkeywords).")/i", $userAgent))
			    {
					return $this->publishPortalDown_IOSAction($eno);
			    }
			    else
			    {
					return $this->publishPortalDown_AndroidAction($eno);
			    }
			}
		}
	}

	//电脑
	return $this->publishPortalDown_PCAction($eno);
  }

  public function publishPortalDown_PCAction($eno)
  {
  	$advconfig = array();
  	$ename = "";

	$da = $this->get("we_data_access");

	//取公司名字
  	$sql="select ename from we_enterprise where eno=?";
  	$params = array();
	$params[] = (string)$eno; 

    $ds = $da->Getdata("we_enterprise",$sql,$params);
    if (count($ds["we_enterprise"]["rows"]) > 0)
    {
    	$ename = $ds["we_enterprise"]["rows"][0]["ename"];
    }

  	//获取用户设置的应用资源
  	$sql="select restype, resvalue from we_app_portal_advconfig where appid='PORTAL' and eno=?";
  	$params = array();
	$params[] = (string)$eno; 

    $ds = $da->Getdata("res",$sql,$params);
    for ($i=0; $i < count($ds["res"]["rows"]); $i++) {
    	$item =  $ds["res"]["rows"][$i];
    	$advconfig[$item["restype"]] = $item["resvalue"];
    }
  	
	return $this->render("JustsyBaseBundle:MappConfig:app_download.html.twig",
		array('eno' => $eno, 'ename' => $ename, 'advconfig' => $advconfig));
  }

  public function publishPortalDown_WXAction($eno)
  {
	return $this->render("JustsyBaseBundle:MappConfig:app_download_wx.html.twig",
		array());
  }

  public function publishPortalDown_AndroidAction($eno)
  {
  	$advconfig = array();
  	$ename = "";

	$da = $this->get("we_data_access");

	//取公司名字
  	$sql="select ename from we_enterprise where eno=?";
  	$params = array();
	$params[] = (string)$eno; 

    $ds = $da->Getdata("we_enterprise",$sql,$params);
    if (count($ds["we_enterprise"]["rows"]) > 0)
    {
    	$ename = $ds["we_enterprise"]["rows"][0]["ename"];
    }

  	//获取用户设置的应用资源
  	$sql="select restype, resvalue from we_app_portal_advconfig where appid='PORTAL' and eno=?";
  	$params = array();
	$params[] = (string)$eno; 

    $ds = $da->Getdata("res",$sql,$params);
    for ($i=0; $i < count($ds["res"]["rows"]); $i++) {
    	$item =  $ds["res"]["rows"][$i];
    	$advconfig[$item["restype"]] = $item["resvalue"];
    }
  	
	return $this->render("JustsyBaseBundle:MappConfig:app_download_mobile_android.html.twig",
		array('eno' => $eno, 'ename' => $ename, 'advconfig' => $advconfig));
  }

  public function publishPortalDown_IOSAction($eno)
  {
	return $this->render("JustsyBaseBundle:MappConfig:app_download_mobile_ios.html.twig",
						array());
  }
  
  //组件图片上传
  public function component_imguploadAction($appid)
  {
  	  $da = $this->get("we_data_access");
  	  $fileElementName="componentImage";
	  if($appid=="PORTAL")
	  {
		    $userinfo = $this->get('security.context')->getToken()->getUser();
		    $appid = $appid.$userinfo->eno;
	  }
	  $request = $this->getrequest();
	  $isSaveToRes = $request->get("saveres");//是否存到资源列表中,默认为要存储 
  	  $fileElementName = 'componentImage';
  	 	$re=array('succeed'=>true,'m'=>'','fileid'=>'','filename'=>'');
	  	try {
			   $filename=$_FILES[$fileElementName]['name'];
				 $filesize=$_FILES[$fileElementName]['size'];
				 $filetemp=$_FILES[$fileElementName]['tmp_name'];	
				 $fileid=$this->saveCertificate($filetemp,$filename);
				 if(empty($fileid)) {
				   $re['succeed'] = false;
				   $re['msg']='文件上传失败';
				 }
				 else	{
				 	$re['fileid'] = $fileid;
				 	if("0"!=$isSaveToRes){
				  		$sql="insert into we_apps_resource (appid,fileid,name,restype,ressize,cdate) values(?,?,?,?,?,now())";
					 	$params=array($appid,$fileid,$this->getname($filename),$this->getprex($filename),$filesize);
					 	if(!$da->ExecSQL($sql,$params)){
					   		$re['succeed']=false;
						 	$re['msg']='文件保存失败';
					 	}
					}
				 }
			}
			catch(\Exception $e){
			  $re['msg']=($e->getMessage());
			}
			$response = new Response(json_encode($re));
     	$response->headers->set('Content-Type', 'text/html');
     	return $response;
  }

	public function getComponentlayoutAction()
	{
		 return $this->render("JustsyBaseBundle:MappConfig:componentlayout.html.twig",array());
	}
	
  //导出资源文件
	public function exportConfigureAction()
	{		
		set_time_limit(1800);
		$eno = $this->get('security.context')->getToken()->getUser()->eno;
		//文件存储目录的确定
    $path = rtrim($_SERVER['DOCUMENT_ROOT'],'\/')."/upload";
    if ( !is_dir($path))
       mkdir($path);
    $path = $path."/$eno";
    if (!is_dir($path)){
       mkdir($path);
    }
    else{
   	  //删除目录下的文件或文件夹
		  $dh=opendir($path);
		  while ($file=readdir($dh)) {		  	
		    if($file!="." && $file!="..") {
		      $fullpath=$path."/".$file;
		      if(!is_dir($fullpath))
		        unlink($fullpath);
		      else{
		      	$this->removeDir($fullpath);		      	
		      }
		    }
      }
    }
    //获得企业所有的应用		
		$FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');		
	  $da = $this->get("we_data_access");
	  $sql = "select eno as appid,eno as appname,concat('".$FILE_WEBSERVER_URL."',configfileid) as 'url',eno appname from we_apps_portalconfig where eno=? and configfileid is not null and configfileid!=''
						union
						select appid,appname,concat('".$FILE_WEBSERVER_URL."',configfileid) as 'url',appname from we_appcenter_apps where configfileid is not null and configfileid!='' and appdeveloper=?";
	  $ds = $da->GetData("table",$sql,array((string)$eno,(string)$eno));
	  $newpath = "";
	  $zipfiles = array();
	  if ( $ds && $ds["table"]["recordcount"]>0){
	  	for($i=0;$i<$ds["table"]["recordcount"];$i++){
	  		$newpath = $path."/".$ds["table"]["rows"][$i]["appname"];
	  		if ( !is_dir($newpath))
	  		  mkdir($newpath);
	  		//创建xml文件
	  		$xmlfile = $newpath."/".$ds["table"]["rows"][$i]["appid"].".xml";
        $xmldatastring = $this->GetConfigure($ds["table"]["rows"][$i]["url"],$xmlfile);
        //获得图片网络地址
        $files = $this->urlBygetImage($this->GetURL($xmldatastring),$newpath);
        array_push($files,$xmlfile);
        //获得Zip文件
        $zip = $newpath."/".$ds["table"]["rows"][$i]["appname"].".zip";       
        $zip = $this->GetZip($files,$zip);
        if ($zip != null)
          array_push($zipfiles,$zip);
	  	}
	  }	  
	  if (count($zipfiles)>0){
			$zip = rtrim($_SERVER['DOCUMENT_ROOT'],'\/')."/upload/$eno.zip";
			if ( file_exists($zip))
			   unlink($zip);
	    $result = $this->create_zip($zipfiles,$zip,true);
	    if ($result){
	      $zip = "/upload/$eno.zip";
	      $dh=opendir($path);
			  while ($file=readdir($dh)) {
			    if($file!="." && $file!="..") {
			      $fullpath=$path."/".$file;
			      if(!is_dir($fullpath))
			        unlink($fullpath);
			      else{
			      	$this->removeDir($fullpath);
			        rmdir($fullpath);
			      }
			    }
	      }
	      $this->removeDir($path);
			  rmdir($path);
      }      
	    else
	      $zip = "";
    }
	  $response = new Response(json_encode($zip));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
	}	
	//根据地址获得图片
	private function urlBygetImage($urls,$path)
	{
		$files = array();
		$filename = "";
		for($i=0;$i< count($urls);$i++){
			$url = $urls[$i];
			$filename = strrchr($url,'/');
			$filename = ltrim($filename,"/");
			if ( strpos($filename,"."))
			  $filename = $path."/".$filename;
			else
			  $filename = $path."/".$filename.".png";	
			$this->getImage($url,$filename);
			array_push($files,$filename);
		}
		return $files;
	}	
	//获得zip文件
	private function GetZip($files,$zipfile)
	{
		$zip = "";
		if (count($files)>0){
	    $result = $this->create_zip($files,$zipfile,true);
	    if ($result){
	      $zip = $zipfile;
	      for($j=0;$j<count($files);$j++){
	      	if (file_exists($files[$j]))
	      	   unlink($files[$j]);
	      }
      }
    }
    return $zip;
	}
	//删除目录下文件
	private function removeDir($path)
	{
	  $dh=opendir($path);
		 while ($file=readdir($dh)) {		  	
		   if($file!="." && $file!="..") {
		     $fullpath=$path."/".$file;
		      if(!is_dir($fullpath))
		        unlink($fullpath);
		      else
		        $this->removeDir($fullpath);
		   }
     }
	}
	
	private function removeEmpdir($path)
	{
		 $dh=opendir($path);
		 while ($file=readdir($dh)) {		  	
		   if($file!="." && $file!="..") {
		     $fullpath=$path."/".$file;
		      if(is_dir($fullpath))
		       rmdir($fullpath);
		   }
     }
	}	
	
	private function GetConfigure($xml_url,$xmlfile)
	{	
		$xml_content = "";
		try
		{
	    $xml_content = file_get_contents($xml_url);
	    //创建空xml文件
	    fclose(fopen($xmlfile,'w'));
	    //写xml文件    
	    $cont =fopen($xmlfile,'w');
			fwrite($cont,$xml_content);
			fclose($cont);
	  }
		catch(\Exception $e){
		}
	  return $xml_content;
	}	
	//获得url地址（返回结果为数组）
  private	function GetURL($test){  
    $rule = '/((?:https?|mailto|ftp):\/\/.*?)(\s|&nbsp;|\'|\"|：|；|，|。|！|>|<)/i';
    preg_match_all($rule,$test,$result);    
    $arrayurl = array();
    for($i=0; $i < count($result);$i++){
    	$reurl = $result[$i];
    	if ( is_array($reurl)){
    		for($j=0;$j < count($reurl);$j++){
    			$url = rtrim($reurl[$j],"<");
    			if ( $url=="" || strpos($url,"im.fafacn.com/namespace/mapp",0)>-1 || strlen($url)<5 ) continue;
    			if ( !in_array($url,$arrayurl,true))
    			  array_push($arrayurl,$url);
    		}
    	}
    }
    return $arrayurl;  
  }
  
  private  function getImage($url,$filename){
    if($url==''){return false;}
    ob_start(); 
	  readfile($url);
	  $img=ob_get_contents(); 
	  ob_end_clean();	  
	  $size=strlen($img);	  
    $fp2=@fopen($filename,'a');
    fwrite($fp2,$img);
    fclose($fp2);
    return $filename;
  }
  //生成压缩形式的.zip文件
	private function create_zip($files = array(),$destination = '',$overwrite) { 
	   $valid_files = array();
	   if(is_array($files)) {
	     foreach($files as $file) {    
	       if(file_exists($file)) {
	         $valid_files[] = $file;   
	       }   
	     }    
	   }
	   if(count($valid_files)) {
	   	  $zip = new ZipArchive();
	      if($zip->open($destination,$overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true){ 
	        return false;    
	      }
        //向压缩文件中添加文件    
        foreach($valid_files as $file) {   
          $file_info_arr= pathinfo($file);  
          $filename =$file_info_arr['basename'];   
          $zip->addFile($file,$filename);
        }
        $zip->close();  
	      return true;
	   }
	   else{
	      return false; 
	   }    
	}
	
	//根据不同类型获得用户权限
	public function getRoleAccountAction()
	{
		 $da = $this->get("we_data_access");
		 $eno = $this->get('security.context')->getToken()->getUser()->eno;
		 $request=$this->getrequest();
		 $type = $request->get("type");
		 $appid = $request->get("appid");
		 $pageindex = $request->get("pageindex");
		 $recordcount = $request->get("recordcount"); //每页记录数
		 $searchtext = $request->get("searchtext");		 
		 $sql="";$sql2="";
		 $condition = "";
		 $limit = "limit ".(($pageindex - 1) * $recordcount).",".$recordcount;
		 $header = $this->container->getParameter('FILE_WEBSERVER_URL');
		 if ($type =="dev"){ //开发者
		 	 if ($searchtext!=null && !empty($searchtext)){
		 	 	 if (strlen($searchtext)>mb_strlen($searchtext,'utf8'))
		 	 	   $condition = " and a.nick_name like concat('%',?,'%') ";
		 	 	 else
		 	 	   $condition = " and (a.login_account like concat('%',?,'%') or a.nick_name like concat('%',?,'%'))";
		 	 }		 	 
		 	 $sql = "select b.id,a.nick_name name,case when a.photo_path is null or a.photo_path='' then '' else concat('$header',a.photo_path) end as header 
               from we_staff a inner join we_app_developer b on a.login_account=b.login_account where b.appid=?".
               $condition." and b.agree=1 and a.eno=? order by a.nick_name asc ".$limit;
       if($pageindex==1)
		 	   $sql2="select count(*) recordcount from we_staff a inner join we_app_developer b on a.login_account=b.login_account where b.appid=?".$condition." and b.agree=1 and a.eno=?";
		 	 if (empty($condition))
		 	   $para = array((string)$appid,(string)$eno);
		 	 else{
		 	 	 if (strlen($searchtext)>mb_strlen($searchtext,'utf8'))
		 	 	   $para = array((string)$appid,(string)$searchtext,(string)$eno);
		 	 	 else
		 	 	   $para = array((string)$appid,(string)$searchtext,(string)$searchtext,(string)$eno);
		 	 }
		 }
		 else if($type=="user"){ //普通用户
		 	 if ($searchtext!=null && !empty($searchtext)){
		 	 	 if (strlen($searchtext)>mb_strlen($searchtext,'utf8'))
		 	 	   $condition = " and a.nick_name like concat('%',?,'%') ";
		 	 	 else
		 	 	   $condition = " and (a.login_account like concat('%',?,'%') or a.nick_name like concat('%',?,'%'))";
		 	 }
		 	 $sql = "select b.id,a.nick_name name,case when a.photo_path is null or a.photo_path='' then '' else concat('$header',a.photo_path) end as header 
		 	         from we_staff a inner join we_app_userpriv b on a.login_account=b.login_account where appid=? ".$condition." and eno=? order by a.nick_name asc ".$limit;
		 	 if ($pageindex==1)
		 	   $sql2="select count(*) recordcount from we_staff a inner join we_app_userpriv b on a.login_account=b.login_account where appid=? ".$condition." and eno=?";
		 	 if (empty($condition))
		 	   $para = array((string)$appid,(string)$eno);
		 	 else {
		 	 	 if (strlen($searchtext)>mb_strlen($searchtext,'utf8'))
		 	 	   $para = array((string)$appid,(string)$searchtext,(string)$eno);
		 	 	 else
		 	 	   $para = array((string)$appid,(string)$searchtext,(string)$searchtext,(string)$eno);
		 	 }		 	 
     }
		 else if ($type=="role"){
		 	 if ($searchtext!=null && !empty($searchtext))
		 	   $condition = " and a.name like concat('%',?,'%')";
		 	 $sql = "select b.id,a.name,null as header 
		 	         from we_role a inner join we_app_unitalloc b on a.id=b.roleid where b.appid=?".$condition." and b.eno=? order by a.name asc ".$limit;
		 	 if (empty($condition))
		 	   $para = array((string)$appid,(string)$eno);
		 	 else
		 	   $para = array((string)$appid,(string)$searchtext,(string)$eno);
		 	 if ($pageindex==1)
		 	   $sql2="select count(*) recordcount from we_role a inner join we_app_unitalloc b on a.id=b.roleid where b.appid=?".$condition." and b.eno=?";
     }
		 else if ($type=="org"){
		 	 if ($searchtext!=null && !empty($searchtext))
		 	   $condition = " and dept_name like concat('%',?,'%') ";
		 	 $sql = "select b.id,dept_name as name,null as header 
               from we_department a inner join we_app_unitalloc b on a.dept_id=b.deptid where b.appid=? ".$condition." and a.eno=? order by a.dept_id asc ".$limit;
		 	 if (empty($condition))
		 	   $para = array((string)$appid,(string)$eno);
		 	 else
		 	   $para = array((string)$appid,(string)$searchtext,(string)$eno);
		 	 if ($pageindex==1)
		 	   $sql2="select count(*) recordcount from we_department a inner join we_app_unitalloc b on a.dept_id=b.deptid where b.appid=? ".$condition." and a.eno=?";
     }
     //总记录条数
     $record = 0;
     $table = array();
     if (!empty($sql)){
		   $ds = $da->GetData("table",$sql,$para);
		   if ($ds && $ds["table"]["recordcount"]>0){
		   	 $table = $ds["table"]["rows"];
		   	 if ($pageindex==1 && !empty($sql2)){
		   	 	 $ds = $da->GetData("record",$sql2,$para);
		   	 	 if($ds && $ds["record"]["recordcount"]>0)
		   	 	   $record = $ds["record"]["rows"][0]["recordcount"];
		   	 }
		   }
	   }
	   $data = array("list"=>$table,"recordcount"=>$record);
		 $result = new Response(json_encode($data));
	   $result->headers->set('Content-Type', 'text/json');
		 return $result;
	}
	
	//保存权限用户
	public function saveRoleAccountAction()
	{
		 $da = $this->get("we_data_access");		 
		 $request=$this->getrequest();
		 $appid = $request->get("appid");
		 $type  = $request->get("type");
		 $selectedid = $request->get("selectedid");
		 $result = array("success"=>true,"msg"=>"");
		 try
		 {
		 	 if ($type=="dev")
		 	   $this->saveDeveloper($selectedid,$appid);
		 	 else if($type=="user")
		 	   $this->saveUserRole($selectedid,$appid);
		 	 else if($type=="role")
		 	   $this->saveRoleOrDepart($selectedid,$appid,"role");
		 	 else if($type=="org")
		 	   $this->saveRoleOrDepart($selectedid,$appid,"org");
		 	 if ( $type=="dev" || $type=="user"){
		 	 	 $this->SaveAcountBind($selectedid,$appid);
		 	 }
		 }
		 catch(\Exception $e){
		 	  $this->get("logger")->err($e->getMessage());
			  $result = array("success"=>false,"msg"=>"添加失败！");
		 }
		 $result = new Response(json_encode($result));
	   $result->headers->set('Content-Type', 'text/json');
		 return $result;
	}	
	
	//将用户权限保存到we_staff_account_bind表
	private function SaveAcountBind($selectedid,$appid)
	{
		 $da = $this->get("we_data_access");
		 $sqls = array();
		 $paras = array();
		 $img = $this->container->getParameter('FILE_WEBSERVER_URL');
		 foreach($selectedid as $val){
		   $sql="insert into we_staff_account_bind(bind_account,bind_type,login_account,bind_created,nick_name,profile_image_url,appid)
		          select openid,'wefafa',login_account,now(),nick_name,case when ifnull(photo_path,'')='' then null else concat('$img',photo_path) end,'$appid'
              from we_staff where login_account=?";
			 $para = array((string)$val);
			 array_push($sqls,$sql);
			 array_push($paras,$para);
		 }
		 try
		 {
		   if (count($sqls)>0 && count($paras)>0)
		     $da->ExecSQLs($sqls,$paras);
		 }
		 catch (\Exception $e){
		 	 $this->get("logger")->err($e->getMessage());
		 }
	}	
	
	//保存一般用户应用权限
	private function saveUserRole($selectedid,$appid)
	{
		$da = $this->get("we_data_access");
		$login_account = "";
		$sqls = array();
		$paras = array();	
		for($i=0;$i< count($selectedid);$i++) {
			$staffid = $selectedid[$i];
			$id=SysSeq::GetSeqNextValue($da,"we_app_userpriv","id");
			$sql="insert into we_app_userpriv(id,login_account,appid,role)values(?,?,?,0)";
			$para = array((string)$id,(string)$staffid,(string)$appid);
			array_push($sqls,$sql);
			array_push($paras,$para);
		}
		if (count($sqls)>0 && count($paras)>0)
		  $da->ExecSQLs($sqls,$paras);
	}
	
	//保存开发人员数据记录
	private function saveDeveloper($selectedid,$appid)
	{
		$da = $this->get("we_data_access");
		$sqls = array();
		$paras = array();
		for($i=0;$i< count($selectedid);$i++) {
			$self_id = $this->get('security.context')->getToken()->getUser()->getUserName();
			$id=SysSeq::GetSeqNextValue($da,"we_app_developer","id");
			$staffid = $selectedid[$i];
			$sql="insert into we_app_developer(id,login_account,appid,agree,operator_staffid)values(?,?,?,1,?)";
			$para = array((string)$id,(string)$staffid,(string)$appid,$self_id);
			array_push($sqls,$sql);
			array_push($paras,$para);		
		}
		if ( count($sqls)>0 && count($paras)>0)
		  $da->ExecSQLs($sqls,$paras);
	}
	
	//保存开发人员数据记录
	private function saveRoleOrDepart($selectedid,$appid,$type)
	{
		$da = $this->get("we_data_access");
		$sqls = array();$sql="";
		$paras = array();
		$eno = $this->get('security.context')->getToken()->getUser()->eno;
		for($i=0;$i< count($selectedid);$i++) {
			$id=SysSeq::GetSeqNextValue($da,"we_app_unitalloc","id");
      if ($type=="role")
		    $sql = "insert into we_app_unitalloc(id,eno,appid,roleid)values(?,?,?,?)";
		  else
		    $sql = "insert into we_app_unitalloc(id,eno,appid,deptid)values(?,?,?,?)";
		  $para=array((string)$id,(string)$eno,(string)$appid,(string)$selectedid[$i]);
			array_push($sqls,$sql);
			array_push($paras,$para);		
		}
		if ( count($sqls)>0 && count($paras)>0)
		  $da->ExecSQLs($sqls,$paras);
	}
	
	//申请加入开发
	public function applyJoinAppAction()
	{
		 $da = $this->get("we_data_access");		 
		 $userinfo = $this->get('security.context')->getToken()->getUser();
		 $eno = $userinfo->eno;
		 $return = array("success"=>true,"message"=>"");
		 try{
		 	  $sql = "select sys_manager from we_enterprise where eno=?";
		    $ds = $da->GetData("staff",$sql,array((string)$eno));
		    if ( $ds && $ds["staff"]["recordcount"]>0){
		 	    $manager = $ds["staff"]["rows"][0]["sys_manager"];
		 	    $request=$this->getrequest();
		      $appid = $request->get("appid");
		      $appname = $request->get("appname");
		 	    $staffid = $userinfo->getUserName();
		 	    $id=SysSeq::GetSeqNextValue($da,"we_app_developer","id");
		 	    $sql = "insert into we_app_developer(id,login_account,appid,agree)values(?,?,?,0)";
		 	    $da->ExecSQL($sql,array((string)$id,(string)$staffid,(string)$appid));
		 	    //向企业管理都或应用创建者发送消息
		 	    $message = "申请访问".$appname;
  	      Utils::sendImPresence($this->container->getParameter('im_sender'),$manager,"appdeveloper_apply",$message,$this->container,"","",false,Utils::$systemmessage_code);
		    }
		    else{
		    	$return = array("success"=>false,"message"=>$e->getMessage());
		    }	 	
		 }
		 catch(\Exception $e){
		 	 $return = array("success"=>false,"message"=>$e->getMessage());		 	 
		 }
		 $result = new Response(json_encode($return));
	   $result->headers->set('Content-Type', 'text/json');
		 return $result;
	}
	
	//根据id和权限用户类型删除数据记录
	public function removeRoleAction(){
		
		$da = $this->get("we_data_access");
		$request=$this->getrequest();
		$type= $request->get("type");
		$id =  $request->get("id");		
		$sqls = array();
		$paras = array();
		if ($type=="dev"){		
	    $sql = "delete from we_staff_account_bind 
		          where login_account=(select login_account from we_app_developer where id=?) 
		                and appid = (select appid from we_app_developer where id=?)";
      array_push($sqls,$sql);
      array_push($paras,array((string)$id,(string)$id));   		                	
		  $sql = "delete from we_app_developer where id=?";
		  array_push($sqls,$sql);
		}
		else if ($type=="user"){
	    $sql = "delete from we_staff_account_bind 
		          where login_account=(select login_account from we_app_userpriv where id=?) 
		                and appid = (select appid from we_app_userpriv where id=?)";
      array_push($sqls,$sql);
      array_push($paras,array((string)$id,(string)$id));
		  $sql="delete from we_app_userpriv where id=?";
		  array_push($sqls,$sql);
		}
		else if ($type=="role"){
		  $sql = "delete from we_app_unitalloc where id=?";
		  array_push($sqls,$sql);
		}
		else if ($type=="org"){
		  $sql = "delete from we_app_unitalloc where id=? ";
		  array_push($sqls,$sql);
		}
		array_push($paras,array((string)$id));
		$message = array("success"=>true,"message"=>"");
		try
		{
		  $da->ExecSQLS($sqls,$paras);
		}
		catch(\Exception $e){
			$message = array("success"=>true,"message"=>$e->getMessage());
		}
		$result = new Response(json_encode($message));
	  $result->headers->set('Content-Type', 'text/json');
		return $result;
	}
	
	//获得待添加的用户、开发者、组织机构、角色
	public function getAccountAction()
	{
		 $da = $this->get("we_data_access");
		 $eno = $this->get('security.context')->getToken()->getUser()->eno;
		 $request=$this->getrequest();
		 $type= $request->get("type");
		 $appid = $request->get("appid");
		 $searchtext = $request->get("searchtext");
		 $pageindex = $request->get("pageindex");  //页号
		 $pagerecord = $request->get("pagerecord"); //每页记录条数
		 $limit = " limit ".(($pageindex - 1) * $pagerecord).",".$pagerecord;
		 $header = $this->container->getParameter('FILE_WEBSERVER_URL');
		 $sql="";$condition="";
		 $sql2="";
		 $para = array();
		 $para2 = array();
		 if ($type=="dev"){
		 	  if (!empty($searchtext)){
		 	  	if (strlen($searchtext)>mb_strlen($searchtext,'utf8'))
		 	  	  $condition = " and a.nick_name like concat('%',?,'%')";
		 	  	else
		 	  	  $condition = " and (a.login_account like concat('%',?,'%') or a.nick_name like concat('%',?,'%'))";
		 	  }
		 	  $sql="select a.login_account `id`,a.nick_name `name`,case when a.photo_path is null or a.photo_path='' then '' else concat('$header',a.photo_path) end as header 
              from we_staff a where not exists (select 1 from we_app_developer b where a.login_account=b.login_account and agree=1 and appid=?)".
              $condition." and eno=? order by nick_name asc ".$limit;
        //是否返回记录总数
        if ($pageindex==1)
			 	  $sql2="select count(*) as recordcount from we_staff a where not exists (select 1 from we_app_developer b where a.login_account=b.login_account and agree=1 and appid=?)".$condition." and eno=? ";
        if (empty($condition))
          $para=array((string)$appid,(string)$eno);
        else{
        	if (strlen($searchtext)>mb_strlen($searchtext,'utf8'))
        	  $para=array((string)$appid,(string)$searchtext,(string)$eno);
        	else
        	  $para=array((string)$appid,(string)$searchtext,(string)$searchtext,(string)$eno);
        }
		 }
		 else if ($type=="user"){
		 	  if (!empty($searchtext)){
		 	  	if (strlen($searchtext)>mb_strlen($searchtext,'utf8'))
		 	  	  $condition = " and a.nick_name like concat('%',?,'%')";
		 	  	else
		 	  	  $condition = " and (a.login_account like concat('%',?,'%') or a.nick_name like concat('%',?,'%'))";
		 	  }
		 	  $sql="select a.login_account as `id`,a.nick_name `name`,case when a.photo_path is null or a.photo_path='' then '' else concat('$header',a.photo_path) end as header 
              from we_staff a where not exists (select 1 from we_app_userpriv b where a.login_account=b.login_account and appid=?)".
              $condition." and eno=? order by nick_name asc ".$limit;
        if ($pageindex==1)
           $sql2 = "select count(*) as recordcount from we_staff a where not exists (select 1 from we_app_userpriv b where a.login_account=b.login_account and appid=?)".$condition." and eno=?";             
        if (empty($condition))
          $para=array((string)$appid,(string)$eno);
        else{
        	if (strlen($searchtext)>mb_strlen($searchtext,'utf8'))
        	  $para=array((string)$appid,(string)$searchtext,(string)$eno);
        	else
        	  $para=array((string)$appid,(string)$searchtext,(string)$searchtext,(string)$eno);
        }       
		 }
		 else if ($type=="role"){
		 	 if (!empty($searchtext))
		 	    $condition = " and a.name like concat('%',?,'%')";
		 	 $sql="select id as `id`,name,null header
             from we_role a where not exists (select eno from we_app_unitalloc b where locate(a.id,roleid)>0 and appid=?)".
             $condition." and role_type is not null and eno=? order by name asc ".$limit;
       if (empty($condition))
         $para=array((string)$appid,(string)$eno);
       else
         $para=array((string)$appid,(string)$searchtext,(string)$eno);
       if ($pageindex==1)
         $sql2="select count(*) as recordcount from we_role a where not exists (select eno from we_app_unitalloc b where locate(a.id,roleid)>0 and appid=?)".$condition." and role_type is not null and eno=?";
		 }
		 else if ($type=="org"){
		 	 if (!empty($searchtext))
		 	   $condition = " and a.dept_name like concat('%',?,'%')";
		 	 $sql="select dept_id id,dept_name as `name`,null header
             from we_department a where not exists (select eno from we_app_unitalloc b where locate(a.dept_id,deptid)>0 and appid=?)
             and locate(concat('v',eno),fafa_deptid)=0".$condition." and eno=? order by dept_name asc ".$limit;
       if (empty($condition))
         $para=array((string)$appid,(string)$eno);
       else
         $para=array((string)$appid,(string)$searchtext,(string)$eno);
       if($pageindex==1)
         $sql2 ="select count(*) as recordcount from we_department a where not exists (select eno from we_app_unitalloc b where locate(a.dept_id,deptid)>0 and appid=?) and locate(concat('v',eno),fafa_deptid)=0".$condition." and eno=?";
		 }
		 $ds=$da->GetData("table",$sql,$para);
		 $list = array();
		 if($ds && $ds["table"]["recordcount"]>0)
		   $list = $ds["table"]["rows"];
		 $recordcount=0;
     if ($pageindex==1 && !empty($sql2)){
     	 $ds=$da->GetData("table",$sql2,$para);
		   if($ds && $ds["table"]["recordcount"]>0)
		     $recordcount = $ds["table"]["rows"][0]["recordcount"];
     }
     $data = array("list"=>$list,"totalcount"=>$recordcount);
		 $result = new Response(json_encode($data));
	   $result->headers->set('Content-Type', 'text/json');
		 return $result;
	}
}