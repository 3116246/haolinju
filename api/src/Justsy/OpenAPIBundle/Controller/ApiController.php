<?php

namespace Justsy\OpenAPIBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Login\UserProvider;
use Justsy\BaseBundle\Login\UserSession;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Management\Staff;
use Justsy\BaseBundle\Management\Enterprise;
use SoapClient;

class ApiController extends Controller
{
      static $PDODBDriver = array(
        "oracle" => "pdo_oci" ,
        "mysql" => "pdo_mysql" ,
        "sqlserver2005" => "pdo_dblib" ,
        "sqlserver2008" => "pdo_dblib" 
      );
	  static $securityDomains = null;
	  private $buinsessobject = null;
	  public function testAction()
	  {
	  	 return $this->render("JustsyOpenAPIBundle:Default:index.html.twig");
	  }
	  public function setBusinessObject($obj)
	  {
	      	$this->buinsessobject = $obj;
	  }

	//根据指定的群id或者jid获取对应的logo或头像
	public function getLogoAction()
	{
		$request = $this->get("request");
    	$da = $this->get("we_data_access"); 
    	$type=trim($request->get('type'));
    	$id=trim($request->get('id'));
    	if($type=="" || $id=="")
    	{
    	   	return $this->responseJson($request,Utils::WrapResultError('类型或id不能为空'));
    	}
    	if($type=="STAFF")
    	{
    	   		$staffMgr = new Staff($da,$this->get("we_data_access_im"),$id,$this->container->get('logger'),$this->container);
    	   		$data = $staffMgr->getInfo();
    	   		if(empty($data))
    	   		{
    	   			return $this->responseJson($request,Utils::WrapResultError('无效的员工jid'));
    	   		}
    	   		return $this->responseJson($request,Utils::WrapResultOK($data['photo_path']));
    	}
    	else if($type=="GROUP")
    	{
    	   		$mgr = new \Justsy\BaseBundle\Management\GroupMgr($da,$this->get("we_data_access_im"),$this->container);
    	   		$data = $mgr->GetByIM($id);
    	   		if(empty($data))
    	   		{
    	   			return $this->responseJson($request,Utils::WrapResultError('无效的员工jid'));
    	   		}
    	   		return $this->responseJson($request,Utils::WrapResultOK($data['logo']));
    	}
    	return $this->responseJson($request,Utils::WrapResultError('无效的类型'));
	}
	  
	  public function bizProxyAuthAction()
	  {
	  	   $re = array("returncode"=>"0000");
	  	   $request = $this->get("request");
	  	   $openid = $request->get("openid");
	  	   $uname = $request->get("loginname");
	  	   $upwd = $request->get("passwd");
	  	   if(empty($openid) || empty($uname) || empty($upwd))
	  	   {
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数openid或loginname或passwd未指定或无效.";
			       $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response; 	  	   	
	  	   }
	  	   $da = $this->get("we_data_access");
	  	   $factory = $this->get('security.encoder_factory');
	  	   $staff = new \Justsy\BaseBundle\Management\Staff($da,$this->get("we_data_access_im"),$uname,$this->get("logger"),$this->container);
	  	   $data = $staff->getInfo();
	  	   if(empty($data))
	  	   {
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数loginname无效.";
			       $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;	  	       	
	  	   }
	  	   $t_code = $data["t_code"];
	  	   $t_code2=DES::encrypt($upwd);
	  	   if($openid!=$data["openid"] || $t_code!=$t_code2)
	  	   {
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数openid或密码无效.";
			       $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;   	
	  	   }
	  	   $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
				 $response->headers->set('Content-Type', 'text/json');
				 return $response;	
	  }
	  public function getValueOfKeyAction()
	  {
	  	$request = $this->get("request");
    	$da = $this->get("we_data_access");
    	$appid=$request->get("appid");
    	$openid=$request->get("openid");
    	
    	//获取关联参数
    	$sql="select a.bind_uid from we_staff_account_bind a where a.appid=? and a.login_account=(select b.login_account from we_staff b where b.openid=?)";
    	$params=array($appid,$openid);
    	$ds=$da->Getdata('info',$sql,$params);
    	$bind_uid='';
    	if($ds['info']['recordcount']>0){
    		$bind_uid=$ds['info']['rows'][0]["bind_uid"];
    	}
    	
    	$re;
    	try{
    		//获取携程令牌
	    	$get_token_url="https://www.corporatetravel.ctrip.com/corpservice/CorpSSOAccessCheck.asmx";
	    	$action="SSOAuthenticaionWithXML";
	    	$AccessUK='obk_eavic_sq';
	    	$AccessPK='obk_eavic_sq';
	    	$CtripCardNO='';
	    	$EmployeeNO=$bind_uid;
				
				$paraXml='<SSOAuthRequest>'.
								 '<Language>Chinese</Language>'.
								 '<SSOAuth>'.
								 '<AccessUK>'.$AccessUK.'</AccessUK>'.
								 '<AccessPK>'.$AccessPK.'</AccessPK>'.
								 '<EmployeeNO>'.$EmployeeNO.'</EmployeeNO>'.
								 '</SSOAuth>'.
								 '</SSOAuthRequest>';
				
	    	$soap=new SoapClient($get_token_url."?WSDL");
	    	$para=array("requestXMLString"=>array(
	    			"SSOAuthRequest"=>array(
		    			"Language"=>"Chinese",
			    		"SSOAuth"=>array(
			    			"AccessUK"=>$AccessUK,
			    			"AccessPK"=>$AccessPK,
			    			"EmployeeNO"=>$EmployeeNO,
			    		)
		    		)
	    		)
	    	);
	    	$para=array("requestXMLString"=>$paraXml);
	    	error_reporting(E_ERROR|E_WARNING|E_PARSE);
	    	$result=$soap->SSOAuthenticaionWithXML($para);
	    	error_reporting(E_ERROR|E_WARNING|E_PARSE|E_NOTICE);
	    	$re=array('s'=>'1','info'=>$result,'employeeid'=>$EmployeeNO);
    	}
    	catch(\Exception $e){
    		$re=array('s'=>'0','msg'=> $e->getMessage());
    	}
    	$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
			$response->headers->set('Content-Type', 'text/json');
			return $response;
	  }
     
     //获得json数据
	  public function gettasklistAction()
	  {
	  	 $request = $this->get("request");
    	 $da = $this->get("we_data_access");
    	 $openid=$request->get("openid");
    	 $type = $request->get("type");
    	 $start =$request->get("start");
    	 $num   =$request->get("num");
    	 $type = empty($type) ? 0 : $type;
    	 $start = empty($start) ? 0 : $start;
    	 $num   = empty($num) ? 10 : $num;
    	 $result = array("returncode"=>"0000","msg"=>"");
    	 //获得ldap_uid
    	 $ldap_uid = null;
    	 $sql = "select ldap_uid from we_staff where openid=?";
    	 $ds = $da->GetData("table",$sql,array((string)$openid));
  	 	 if ( $ds && $ds["table"]["recordcount"]>0){
  	 	 	 $ldap_uid = $ds["table"]["rows"][0]["ldap_uid"];
  	 	 	 if ( !empty($ldap_uid)){
	  	 	   try {
				    	$webservice="http://oadev.crpower.com.cn/indishare/indiNewOAMobile.nsf/wsForTodoList?wsdl";
				    	$SELECTTYPE	= $type;
			        $STRUSER		= $ldap_uid;
			        $START		  = $start;
			        $DOCNUM		  = $num;
				    	$client = new SoapClient($webservice);
				    	$client->soap_defencoding = 'utf-8';
	            $client->decode_utf8 = true;
	            $client->xml_encoding = 'utf-8';
	            $filecontent = $client->WSGETTODOLIST($SELECTTYPE,$STRUSER,$START,$DOCNUM);
	            $filecontent = htmlspecialchars($filecontent);            
	            $result["datasource"] = $this->xmlToJson($openid,$filecontent);
	    	   }
	    	   catch(\Exception $e) {
	    		   $result =  array("returncode"=>"9999","msg"=> $e->getMessage());
	    	   }
    	   }
    	   else{
    	   	 $result =  array("returncode"=>"0000","datasource"=>array());
    	   }
       }
       else{
       	 $result =  array("returncode"=>"9999","msg"=> "传入的openid错误！");
       }
    	$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($result).");" : json_encode($result));
			$response->headers->set('Content-Type', 'text/json');
			return $response;
	  }	  
	  
	  //将xml内容转化为json对象
    private function xmlToJson($openid,$xml_content)
    {
    	 $xml_content = str_replace("&lt;","<",$xml_content);
    	 $xml_content = str_replace("&quot;","'",$xml_content);
    	 $xml_content = str_replace("&gt;",">",$xml_content); 
    	 $xml_content = str_replace("\/","/",$xml_content);
    	 $xml_content = str_replace("\"",'"',$xml_content);
    	 $xml_content = str_replace("&amp;ldquo;",'"',$xml_content);
    	 $xml_content = str_replace("&amp;rdquo;",'"',$xml_content);
    	 $xml_content = str_replace("&rdquo;",'"',$xml_content);
    	 $xml_content = str_replace("&ldquo;",'"',$xml_content);    	     	 
    	 $filename = $_SERVER['DOCUMENT_ROOT']."/upload/".$openid."_".rand(100000,999999).".xml";
    	 $this->get("logger")->err("filename:".$filename);
    	 //保存xml文件
    	 $xmlArray = array();
    	 try{
	       $of = fopen($filename,"w");
		     if($of){
		       fwrite($of,$xml_content);
		     }
		     fclose($of);
	    	 //获得xml文件内容
	    	 $xmlcontent = file_get_contents($filename);
	    	 //将gb2312转换成uft-8字符串
	    	 $xmlcontent = str_replace("gb2312","utf-8",$xmlcontent);
	    	 $xmlcontent = str_replace("GB2312","utf-8",$xmlcontent);
	    	 $xmlobj = simplexml_load_string($xmlcontent);
	    	 $xmljson =json_encode($xmlobj);
	    	 $xmlArray = json_decode($xmljson,true);
	    	 //获得数据后删除文件
	    	 if ( file_exists($filename)){
	    	 	 unlink($filename);
	    	 }
    	 }
    	 catch (\Exception $e){
    	 	 $this->get("logger")->err($e->getMessage());
    	 }
    	 return $xmlArray;
    }	  
	  	  
	  public function getStaffsByRoleAction()
	  {
	  	$re = array("returncode"=>"9999");
    	//判断请求域。是wefafa或子域则不验证授权令牌
    	$isWeFaFaDomain = $this->checkWWWDomain();    	
    	$request = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
			       $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response; 
    	   }
    		 //comfrom
    		 $this->setComefrom($request->get('Appid'));
    	}
    	$eno=$request->get("eno");
    	$rolecode=$request->get("role");
    	$sql="select b.openid from we_staff_role a left join we_staff b on b.login_account=a.staff where a.eno=? and a.roleid=(select c.id from we_role c where c.code=?)";
    	$params=array($eno,$rolecode);
    	$ds=$da->Getdata('info',$sql,$params);
    	$re=$ds["info"]["rows"];
			$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
			$response->headers->set('Content-Type', 'text/json');
			return $response;
	  }
	  public function getStaffInfoByOpenidAction()
	  {
	  	$re = array("returncode"=>"9999");
    	//判断请求域。是wefafa或子域则不验证授权令牌
    	$isWeFaFaDomain = $this->checkWWWDomain();    	
    	$request = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
			       $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response; 
    	   }
    		 //comfrom
    		 $this->setComefrom($request->get('Appid'));
    	}
    	
    	$strs=$request->get("openids");
    	$re=array();
    	if(empty($strs)){
    		$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
				$response->headers->set('Content-Type', 'text/json');
				return $response;
    	}
    	$openids=explode(',',$strs);
    	$sql="select login_account,nick_name,photo_path,photo_path_small,photo_path_big,openid,mobile from we_staff where openid in(";
    	for($i=0;$i< count($openids);$i++){
    		$sql.="'".$openids[$i]."',";
    	}
    	$sql=trim($sql,',').")";
    	$ds=$da->Getdata('info',$sql);
    	$re=$ds["info"]["rows"];
    	$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
			$response->headers->set('Content-Type', 'text/json');
			return $response;
	  }
	  public function getStaffInfoByStaffAction()
	  {
	  	$re = array("returncode"=>"9999");
    	//判断请求域。是wefafa或子域则不验证授权令牌
    	$isWeFaFaDomain = $this->checkWWWDomain();    	
    	$request = $this->get("request");
    	$da = $this->get("we_data_access");
    	$da_im = $this->get('we_data_access_im');
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
			       $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response; 
    	   }
    		 //comfrom
    		 $this->setComefrom($request->get('Appid'));
    	}
    	$row=array();
    	$strs=$request->get("staff");
    	if(empty($strs)){
    		$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($row).");" : json_encode($row));
				$response->headers->set('Content-Type', 'text/json');
				return $response;
    	}
    	$sql="select login_account,fafa_jid,nick_name,photo_path,photo_path_small,photo_path_big,openid from we_staff where openid=?";
    	$params=array($strs);
    	$ds=$da->Getdata('info',$sql,$params);
    	if($ds["info"]["recordcount"]>0){
    		$row=$ds["info"]["rows"][0];
    		$fafa_jid=$row["fafa_jid"];
    		$sql="select password from users where username=?";
    		$params=array($fafa_jid);
    		$ds=$da_im->Getdata('pass',$sql,$params);
    		if($ds['pass']["recordcount"]>0){
    			$row["password"]=$ds["pass"]["rows"][0]["password"];
    		}
    		else{
    			$row["password"]="";
    		}
    	}
    	$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($row).");" : json_encode($row));
			$response->headers->set('Content-Type', 'text/json');
			return $response;
	  }
    public function newtrendAction()
    { 
    	$re = array("returncode"=>"9999");
    	//判断请求域。是wefafa或子域则不验证授权令牌
    	$isWeFaFaDomain = $this->checkWWWDomain();    	
    	$request = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
			       $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response; 
    	   }
    		 //comfrom
    		 $this->setComefrom($request->get('Appid'));
    	}
    	$obj = new \Justsy\InterfaceBundle\Controller\ConvInfoController();
    	$obj->setContainer($this->container);
    	return $obj->newEnterpriseTrendAction(); 
    }
    public function newTrendAndEmailsAction()
    {
    	$re = array("returncode"=>"9999");
    	//判断请求域。是wefafa或子域则不验证授权令牌
    	$isWeFaFaDomain = $this->checkWWWDomain();    	
    	$request = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
			       $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response; 
    	   }
    		 //comfrom
    		 $this->setComefrom($request->get('Appid'));
    	}
    	$re=array("returncode"=>'0000','msg'=>'');
    	try{
    		$circleid=$request->get("circleid");
    		$ds=$this->checkOpenid($da ,$request->get("openid"));
		    $send_email=$ds["login_account"];
		    $this->autoLogin($request,$ds['login_account']);
				$user = $this->get('security.context')->getToken()->getUser();
				$sql="select 1 from we_circle where circle_id=? and enterprise_no=?";
				$para=array($circleid,$user->eno);
				$ds=$da->Getdata('info',$sql,$para);
				if($ds['info']['recordcount']>0)
	    		$this->newtrendAction(); 
    		
	    	$emails=explode(',',$request->get("emails"));
	    	$title=$request->get("title");
	    	$content=$request->get("content");
	    	$sqls=array();
	    	$params=array();
	    	for($i=0;$i< count($emails);$i++){
	    		if(empty($emails[$i]))continue;
	    		$id=SysSeq::GetSeqNextValue($this->get("we_data_access"),"we_mails","id");
	    		$sqls[]="insert into we_mails (id,send_email,recv_email,title,content,remark,is_send) values(?,?,?,?,?,?,'0')";
	    		$params[]=array($id,$send_email,$emails[$i],$title,$content,"business-zhongjian");
	    	}
	    	$da->ExecSQLs($sqls,$params);
    	}
    	catch(\Exception $e){
    		$re=array("returncode"=>'9999','msg'=>'系统错误');
    	}
    	$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
			$response->headers->set('Content-Type', 'text/json');
			return $response;
    }
    public function privatetrendAction()
    { 
    	$re = array("returncode"=>"9999");
    	//判断请求域。是wefafa或子域则不验证授权令牌
    	$isWeFaFaDomain = $this->checkWWWDomain();    	
    	$request = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
			       $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response; 
    	   }
    	}
    	$obj = new \Justsy\InterfaceBundle\Controller\ConvInfoController();
    	$obj->setContainer($this->container);
    	return $obj->newPrivateTrendAction(); 
    }   
       
    //采用出席方式发送特殊消息
    //$from:消息发送人
    //$to  :消息接收人。多个间用，分隔
    //$title：消息标题
    //$msg:      消息内容
    //$link：    回调地址
    //$linktext：业务按钮定义
    //$isCheckTo:是否检查接收人有效性
    //$type：    消息类型。当增加新类型时，需要客户端支持
    //$cctomail：是否抄送邮箱。
    public function sendPresence($from,$to,$title,$msg,$link,$linktext,$isCheckTo=false,$type="",$cctomail="0")
    {
        $pre_code = '/&quot;code&quot;:&quot;.*?&quot;/i';  
        preg_match($pre_code,$msg,$result);
        if(count($result)>0)
        {
            $type=str_replace("&quot;","" ,$result[0]);
            $type=str_replace("code:","" ,$type);
        }         
    	//获取发送人和接收人jid或者openid
    	$from = trim($from);
    	$title = urlencode(trim($title));
    	$msg = urlencode(trim($msg));
    	$link = urlencode(trim($link));
    	$linktext = urlencode(trim($linktext));    	
    	if(empty($from))
    	{
    		$domain =  $this->container->getParameter('edomain');
	        $from="admin@".$domain; 		
    	}
    	if(empty($msg))
    	{
	       return ("{\"returncode\":\"9999\",\"code\":\"err1001\",\"msg\":\"消息内容不能为空\"}");
    	}
    	$da = $this->get("we_data_access");
    	$senderMail =  "admin@".$this->container->getParameter('edomain');
    	//--------------check From--------------
    	if(!strpos($from,"@"))
    	{
    	    //获取opendid对应的jid，没找到则返回错误
            if(Utils::validateMobile($from)) //手机号
                $table = $da->GetData("staff","select fafa_jid,login_account from we_staff where mobile_bind=? ",array((String)$from));
            else
    	        $table = $da->GetData("staff","select fafa_jid,login_account from we_staff where openid=? or ldap_uid=?",array((String)$from,(String)$from));
    	    if(count($table["staff"]["rows"])==0)
    	    {
    	    	return ("{\"returncode\":\"9999\",\"code\":\"err1002\",\"msg\":\"消息发送者参数(From)无效\"}");
    	    }
    	    $from = $table["staff"]["rows"][0]["fafa_jid"];
    	    $senderMail = $table["staff"]["rows"][0]["login_account"];
    	}
    	else if(strpos($from,"admin")===false){  //jid
    	    $table = $da->GetData("staff","select fafa_jid,login_account from we_staff where fafa_jid=? or login_account=?",array((String)$from,(String)$from));
    	    if(count($table["staff"]["rows"])==0)
    	    {
    	    	return ("{\"returncode\":\"9999\",\"code\":\"err1002\",\"msg\":\"消息发送者参数(From)无效\"}");
    	    }
    	    $senderMail = $table["staff"]["rows"][0]["login_account"];
    	}
    	//-------------check To--------------
    	if(empty($to))
    	{
		    	    	return ("{\"returncode\":\"9999\",\"code\":\"err1003\",\"msg\":\"消息接收者参数(To)无效\"}");
    	}
    	$arr =is_array($to) ? $to : explode(",",$to);
    	$regUrl = $this->container->getParameter("FAFA_REG_JID_URL");
    	$toLst = array();
    	$nosendLst=array();
    	foreach ($arr as $key => $value)
    	{
    		$to2 = $value;
    		if($isCheckTo===false && $cctomail!="1"){$toLst[]=$to2; continue;} //是否需要检查接收人是否有效。当内部调用且100%能确定有效时，可设置为不再检查
		    if(!strpos($to2,"@"))
		    {    		  
		    	//获取opendid对应的jid，没找到则返回错误
                if(Utils::validateMobile($to2)) //手机号
                    $table = $da->GetData("staff","select fafa_jid,login_account from we_staff where mobile_bind=? ",array((String)$to2));
                else
		    	    $table = $da->GetData("staff","select fafa_jid,login_account from we_staff where openid=? or ldap_uid=?",array((String)$to2,(String)$to2));
		    	if(count($table["staff"]["rows"])==0)
		    	{
		    	    if(!empty($to2)) $nosendLst[]=$to2;
		    	    continue;
		    	}
		    	$to2 = $table["staff"]["rows"][0]["fafa_jid"];
		    	if($cctomail=="1") Utils::saveMail($da,$senderMail,$table["staff"]["rows"][0]["login_account"],$title,$msg);
		    }
		    else if(strpos($to2,"admin")===false)
            {  
                //jid
                $staffMgr = new Staff($da,$this->get("we_data_access_im"),$to2,$this->get("logger"),$this->container);
                $staffinfo = $staffMgr->getInfo();
                if(!empty($staffinfo))
                {
                    $to2 = $staffinfo["fafa_jid"];
                }
                else
                {
    		    	$table = $da->GetData("staff","select fafa_jid,login_account from we_staff where fafa_jid=? ",array((String)$to2));
    		    	if(count($table["staff"]["rows"])==0)
    		    	{
    		    	    if(!empty($to2)) $nosendLst[]=$to2;
    		    	    continue;
    		    	}
    		    	if($cctomail=="1") Utils::saveMail($da,$senderMail,$table["staff"]["rows"][0]["login_account"],urldecode(iconv('UTF-8', 'GBK',$title)),urldecode(iconv('UTF-8', 'GBK',$msg)),"business-message");
                }
            }
		    $toLst[]=$to2;
        }       
		$regUrlOrg = $regUrl."/service.yaws";
		$data = "sendPresence=1&from=$from&to=".implode(",",$toLst)."&presence=$msg&type=$type&title=$title&link=$link&linktext=$linktext";
		try
		{
            $this->get("logger")->alert("SEND Presence Result:{$regUrlOrg}?{$data}");
		    $re =Utils::do_post_request($regUrlOrg,$data);
		}
		catch(\Exception $e)
		{
		}
		  
		if(empty($nosendLst))       
    	   return "{\"returncode\" : \"0000\"}";
    	else
    	   return "{\"returncode\" : \"0000\",\"nosend\":\"".implode(",",$nosendLst)."\"}";
    }
    public function sendPresenceAction()
    {
    	//判断请求域。是wefafa或子域则不验证授权令牌
    	$isWeFaFaDomain = $this->checkWWWDomain();    	
    	$res = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($res,$da);	
    	   if(!$token)
    	   {
			       $response = new Response("{\"returncode\":\"9999\",\"code\":\"err1015\",\"msg\":\"参数Appid或Openid或Access_token未指定或无效.\"}");
						 $response->headers->set('Content-Type', 'text/html');
						 return $response;
    	   }
    	}
    	$cctomail = $res->get("cctomail");
    	$r = $this->sendPresence($res->get("From").$res->get("from"),$res->get("To").$res->get("to"),$res->get("Title").$res->get("title"),$res->get("Message").$res->get("message"),$res->get("Link").$res->get("link"),$res->get("Linktext").$res->get("Buttons").$res->get("linktext").$res->get("buttons"),true,$res->get("type"),$cctomail );
    	$response = new Response($r);
			$response->headers->set('Content-Type', 'text/html');
			return $response;
    }
    
    public function sendChat($from,$to,$msg,$isCheckTo=false)
    {
        //获取发送人和接收人jid或者openid
        $from = trim($from);
        $msg = urlencode(trim($msg));
        if(empty($from))
        {
            $domain =  $this->container->getParameter('edomain');
            $from="admin@".$domain;     
        }
        if(empty($msg))
        {
           return ("{\"returncode\":\"9999\",\"code\":\"err1001\",\"msg\":\"消息内容不能为空\"}");          
        }
        $da = $this->get("we_data_access");
        //--------------check From--------------
        if(!strpos($from,"@"))
        {
            //获取opendid对应的jid，没找到则返回错误
            if(Utils::validateMobile($from)) //手机号
               $table = $da->GetData("staff","select fafa_jid,login_account from we_staff where mobile_bind=? ",array((String)$from));
            else
               $table = $da->GetData("staff","select fafa_jid,login_account from we_staff where openid=? or ldap_uid=?",array((String)$from,(String)$from));
            if(count($table["staff"]["rows"])==0)
            {
                return ("{\"returncode\":\"9999\",\"code\":\"err1002\",\"msg\":\"消息发送者参数(From)无效\"}");
            }
            $from = $table["staff"]["rows"][0]["fafa_jid"];
        }
        else if(strpos($from,"admin")===false){  //jid
            $table = $da->GetData("staff","select fafa_jid,login_account from we_staff where fafa_jid=? or login_account=?",array((String)$from,(String)$from));
            if(count($table["staff"]["rows"])==0)
            {
                return ("{\"returncode\":\"9999\",\"code\":\"err1002\",\"msg\":\"消息发送者参数(From)无效\"}");
            }
            $from = $table["staff"]["rows"][0]["fafa_jid"];
        }
      
        //-------------check To--------------
        if(empty($to))
        {
            return ("{\"returncode\":\"9999\",\"code\":\"err1003\",\"msg\":\"消息接收者参数(To)无效\"}");            
        }
        $arr = is_array($to) ? $to : explode(",",$to);
        $regUrl = $this->container->getParameter("FAFA_REG_JID_URL");
        $toLst = array();
        $nosendLst=array();     
        foreach ($arr as $key => $value)
        {
            $to2 = $value;
            if($isCheckTo===false){
                $toLst[]=$to2; 
                continue;//是否需要检查接收人是否有效。当内部调用且100%能确定有效且不抄送邮箱（要抄送时还是得去查询一次）时，可设置为不再检查
            } 
            if(!strpos($to2,"@"))
            {
                    //获取opendid对应的jid，没找到则返回错误
                    if(Utils::validateMobile($to2)) //手机号
                        $table = $da->GetData("staff","select fafa_jid,login_account from we_staff where mobile_bind=? ",array((String)$to2));
                    else       //
                        $table = $da->GetData("staff","select fafa_jid,login_account from we_staff where openid=? or ldap_uid=?",array((String)$to2,(String)$to2));
                    if(count($table["staff"]["rows"])==0)
                    {
                        $nosendLst[]=$to2;
                        continue;
                    }
                    $to2 = $table["staff"]["rows"][0]["fafa_jid"];
            }
            else if(strpos($to2,"admin")===false)
            {  
                //jid
                    $staffMgr = new Staff($da,$this->get("we_data_access_im"),$to2,$this->get("logger"),$this->container);
                    $staffinfo = $staffMgr->getInfo();
                    if(!empty($staffinfo))
                    {
                        $to2 = $staffinfo["fafa_jid"];
                    }
                    else
                    {
                        $table = $da->GetData("staff","select fafa_jid,login_account from we_staff where fafa_jid=?",array((String)$to2));
                        if(count($table["staff"]["rows"])==0)
                        {
                            $nosendLst[]=$to2;
                            continue;
                        }
                        $to2 = $table["staff"]["rows"][0]["fafa_jid"];
                    }
            }
            $toLst[]=$to2;
        }
        $regUrlOrg = $regUrl."/service.yaws";
        $data="send=1&from=$from&to=".implode(",",$toLst)."&msg=$msg";
        $this->get("logger")->err("SEND URL:$regUrlOrg?$data");
        $re =Utils::do_post_request($regUrlOrg,$data);
        $this->get("logger")->alert("SEND Result:$re");
        if(count($nosendLst)==0)
           return "{\"returncode\" : \"0000\"}";
        else
           return "{\"returncode\" : \"0000\",\"nosend\":\"".implode(",",$nosendLst)."\"}";
    }

    //发送及时消息.主要用于第三方应用服务端发送消息
    //参数
    //From: openid/Jid
    //To：  openid/jid
    //Title:text
    //Message:text
    //
    public function sendMsg($from,$to,$title,$msg,$link='',$linktext='',$isCheckTo=false,$type="",$cctomail="0")
    {
        $pre_code = '/&quot;code&quot;:&quot;.*?&quot;/i';  
        preg_match($pre_code,$msg,$result);
        if(count($result)>0)
        {
            $type=str_replace("&quot;","" ,$result[0]);
            $type=str_replace("code:","" ,$type);
        }        
    	//获取发送人和接收人jid或者openid
    	$from = trim($from);
    	$title = urlencode(trim($title));
    	$msg = urlencode(trim($msg));
    	$link = urlencode(trim($link));
    	$linktext =urlencode(is_array($linktext)? json_encode($linktext) : trim($linktext));
    	if(empty($from))
    	{
	        $domain =  $this->container->getParameter('edomain');
	        $from="admin@".$domain;		
    	}
    	if(empty($msg))
    	{
	       return ("{\"returncode\":\"9999\",\"code\":\"err1001\",\"msg\":\"消息内容不能为空\"}");   		
    	}
    	$da = $this->get("we_data_access");
    	$senderMail =  "admin@".$this->container->getParameter('edomain');
    	//--------------check From--------------
    	if(!strpos($from,"@"))
    	{
    	    //获取opendid对应的jid，没找到则返回错误
            if(Utils::validateMobile($from)) //手机号
               $table = $da->GetData("staff","select fafa_jid,login_account from we_staff where mobile_bind=? ",array((String)$from));
            else
    	       $table = $da->GetData("staff","select fafa_jid,login_account from we_staff where openid=? or ldap_uid=?",array((String)$from,(String)$from));
    	    if(count($table["staff"]["rows"])==0)
    	    {
    	    	return ("{\"returncode\":\"9999\",\"code\":\"err1002\",\"msg\":\"消息发送者参数(From)无效\"}");
    	    }
    	    $from = $table["staff"]["rows"][0]["fafa_jid"];
    	    $senderMail = $table["staff"]["rows"][0]["login_account"];
    	}
    	else if(strpos($from,"admin")===false){  //jid
    	    $table = $da->GetData("staff","select fafa_jid,login_account from we_staff where fafa_jid=? or login_account=?",array((String)$from,(String)$from));
    	    if(count($table["staff"]["rows"])==0)
    	    {
    	    	return ("{\"returncode\":\"9999\",\"code\":\"err1002\",\"msg\":\"消息发送者参数(From)无效\"}");
    	    }
    	    $from = $table["staff"]["rows"][0]["fafa_jid"];
    	    $senderMail = $table["staff"]["rows"][0]["login_account"];
    	}
      
    	//-------------check To--------------
    	if(empty($to))
    	{
		    return ("{\"returncode\":\"9999\",\"code\":\"err1003\",\"msg\":\"消息接收者参数(To)无效\"}"); 	    	
    	}
    	$arr = is_array($to) ? $to : explode(",",$to);
    	$regUrl = $this->container->getParameter("FAFA_REG_JID_URL");
    	$toLst = array();
    	$nosendLst=array();    	
    	foreach ($arr as $key => $value)
    	{
    		$to2 = $value;
    		if($isCheckTo===false && $cctomail!="1"){
    		  	$toLst[]=$to2; 
    		  	continue;//是否需要检查接收人是否有效。当内部调用且100%能确定有效且不抄送邮箱（要抄送时还是得去查询一次）时，可设置为不再检查
    		} 
		    if(!strpos($to2,"@"))
		    {
		    	    //获取opendid对应的jid，没找到则返回错误
                    if(Utils::validateMobile($to2)) //手机号
                        $table = $da->GetData("staff","select fafa_jid,login_account from we_staff where mobile_bind=? ",array((String)$to2));
                    else       //
		    	        $table = $da->GetData("staff","select fafa_jid,login_account from we_staff where openid=? or ldap_uid=?",array((String)$to2,(String)$to2));
		    	    if(count($table["staff"]["rows"])==0)
		    	    {
		    	    	$nosendLst[]=$to2;
		    	    	continue;
		    	    }
		    	    $to2 = $table["staff"]["rows"][0]["fafa_jid"];
		    	    if($cctomail=="1") Utils::saveMail($da,$senderMail,$table["staff"]["rows"][0]["login_account"],$title,$msg);
		    }
		    else if(strpos($to2,"admin")===false)
            {  
                //jid
                    $staffMgr = new Staff($da,$this->get("we_data_access_im"),$to2,$this->get("logger"),$this->container);
		    	    $staffinfo = $staffMgr->getInfo();
                    if(!empty($staffinfo))
                    {
                        $to2 = $staffinfo["fafa_jid"];
                    }
                    else
                    {
                        $table = $da->GetData("staff","select fafa_jid,login_account from we_staff where fafa_jid=?",array((String)$to2));
    		    	    if(count($table["staff"]["rows"])==0)
    		    	    {
    		    	    	$nosendLst[]=$to2;
    		    	    	continue;
    		    	    }
    		    	    $to2 = $table["staff"]["rows"][0]["fafa_jid"];
                        if($cctomail=="1") Utils::saveMail($da,$senderMail,$table["staff"]["rows"][0]["login_account"],urldecode(iconv('UTF-8', 'GBK',$title)),urldecode(iconv('UTF-8', 'GBK',$msg)),"business-message");
                    }
		    }
            $toLst[]=$to2;
        }
		$regUrlOrg = $regUrl."/service.yaws";
		$data="sendMsg=1&from=$from&to=".implode(",",$toLst)."&msg=$msg&type=$type&title=$title&link=$link&linktext=$linktext";
		$this->get("logger")->err("SEND MSG URL:$regUrlOrg?$data");
		$re =Utils::do_post_request($regUrlOrg,$data); //trim(Utils::getUrlContent($regUrlOrg,$this->get("logger"))); 
		$this->get("logger")->alert("SEND Presence Result:$re");     
		if(count($nosendLst)==0)       
    	   return "{\"returncode\" : \"0000\"}";
    	else
    	   return "{\"returncode\" : \"0000\",\"nosend\":\"".implode(",",$nosendLst)."\"}";
    }
    
    public function sendMsg2($from,$to,$msg,$type,$isCheckTo=false,$cctomail="0",$msg_id="")
    {
        $pre_code = '/&quot;code&quot;:&quot;.*?&quot;/i';  
        preg_match($pre_code,$msg,$result);
        if(count($result)>0)
        {
            $type=str_replace("&quot;","" ,$result[0]);
            $type=str_replace("code:","" ,$type);
        }        
    	//获取发送人和接收人jid或者openid
    	$from = trim($from);   
    	$msg = urlencode(trim($msg));
    	if(empty($from))
    	{
	       $domain =  $this->container->getParameter('edomain');
	        $from="admin@".$domain; 		
    	}
    	if(empty($msg))
    	{
	       return array('returncode'=>'9999','msg'=>'消息内容不能为空');
    	}
    	$da = $this->get("we_data_access");
    	$senderMail =  "admin@".$this->container->getParameter('edomain');
    	if(!strpos($from,"@"))
    	{
    	    //获取opendid对应的jid，没找到则返回错误
            if(Utils::validateMobile($from)) //手机号
                $table = $da->GetData("staff","select fafa_jid,login_account from we_staff where mobile_bind=? ",array((String)$from,(String)$from));
            else
    	       $table = $da->GetData("staff","select fafa_jid,login_account from we_staff where openid=? or ldap_uid=?",array((String)$from,(String)$from));
    	    if(count($table["staff"]["rows"])==0)
    	    {
    	    	return array('returncode'=>'9999','msg'=>'消息发送者参数不能为空');
    	    }
    	    $from = $table["staff"]["rows"][0]["fafa_jid"];
    	    $senderMail = $table["staff"]["rows"][0]["login_account"];
    	}
    	else if(strpos($from,"admin")===false){  //jid
    	    $table = $da->GetData("staff","select fafa_jid,login_account from we_staff where fafa_jid=? or login_account=?",array((String)$from,(String)$from));
    	    if(count($table["staff"]["rows"])==0)
    	    {
    	    	return array('returncode'=>'9999','msg'=>'消息发送者参数不能为空');
    	    }
    	    $from = $table["staff"]["rows"][0]["fafa_jid"];
    	    $senderMail = $table["staff"]["rows"][0]["login_account"];
    	}
      
    	//-------------check To--------------
    	if(empty($to))
    	{
		    return array('returncode'=>'9999','msg'=>'消息接收者参数不能为空');
    	}
    	$arr = is_array($to) ? $to : explode(",",$to);
    	$regUrl = $this->container->getParameter("FAFA_REG_JID_URL");
    	$toLst = array();
    	$nosendLst=array();
    	foreach ($arr as $key => $value)
    	{
		    $to2 = $value;
		    if($isCheckTo===false && $cctomail!="1"){
		  	    $toLst[]=$to2; 
		  	    continue;//是否需要检查接收人是否有效。当内部调用且100%能确定有效且不抄送邮箱（要抄送时还是得去查询一次）时，可设置为不再检查
		    } 
	    	if(!strpos($to2,"@"))
	    	{
	    	    //获取opendid对应的jid，没找到则返回错误
                if(Utils::validateMobile($to2)) //手机号
                    $table = $da->GetData("staff","select fafa_jid,login_account from we_staff where mobile_bind=? ",array((String)$to2));
                else
	    	        $table = $da->GetData("staff","select fafa_jid,login_account from we_staff where openid=? or ldap_uid=?",array((String)$to2,(String)$to2));
	    	    if(count($table["staff"]["rows"])==0)
	    	    {
	    	    	$nosendLst[]=$to2;
	    	    	continue;
	    	    }
	    	    $to2 = $table["staff"]["rows"][0]["fafa_jid"];
	    	    if($cctomail=="1") Utils::saveMail($da,$senderMail,$table["staff"]["rows"][0]["login_account"],$title,$msg);
	    	}
	    	else if(strpos($to2,"admin")===false)
            {
                //jid
                $staffMgr = new Staff($da,$this->get("we_data_access_im"),$to2,$this->get("logger"),$this->container);
                $staffinfo = $staffMgr->getInfo();
                if(!empty($staffinfo))
                {
                    $to2 = $staffinfo["fafa_jid"];
                }
                else
                {
    	    	    $table = $da->GetData("staff","select fafa_jid,login_account from we_staff where fafa_jid=?",array((String)$to2));
    	    	    if(count($table["staff"]["rows"])==0)
    	    	    {
    	    	    	$nosendLst[]=$to2;
    	    	    	continue;
    	    	    }
    	    	    $to2 = $table["staff"]["rows"][0]["fafa_jid"];
    	    	    if($cctomail=="1") Utils::saveMail($da,$senderMail,$table["staff"]["rows"][0]["login_account"],urldecode(iconv('UTF-8', 'GBK',$title)),urldecode(iconv('UTF-8', 'GBK',$msg)),"business-message");
                }
            }
            $toLst[]=$to2;
        }		
        $regUrlOrg = $regUrl."/service.yaws";
		$data="sendMicroMsg=1&from=$from&to=".implode(",",$toLst)."&msg=$msg&type=$type&busdata=$msg_id";
		//$this->get("logger")->alert("SEND MSG URL:$regUrlOrg?$data");
		$re =Utils::do_post_request($regUrlOrg,$data);  
		if(count($nosendLst)==0) return array('returncode'=>'0000','nosend'=>'');
    	else return array('returncode'=>'0000','nosend'=>implode(",",$nosendLst));
    }
    
    public function sendMsgAction()
    {
    	//判断请求域。是wefafa或子域则不验证授权令牌
    	$isWeFaFaDomain = $this->checkWWWDomain();    	
    	$res = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($res,$da);	
    	   if(!$token)
    	   {
			       $response = new Response("{\"returncode\":\"9999\",\"code\":\"err1015\",\"msg\":\"参数Appid或Openid或Access_token未指定或无效.\"}");
						 $response->headers->set('Content-Type', 'text/html');
						 return $response;     	   	
    	   }
    	}
    	$cctomail = $res->get("cctomail");
    	$r = $this->sendMsg($res->get("From").$res->get("from"),$res->get("To").$res->get("to"),$res->get("Title").$res->get("title"),$res->get("Message").$res->get("message"),$res->get("Link").$res->get("link"),$res->get("Linktext").$res->get("Buttons").$res->get("linktext").$res->get("buttons"),true,$res->get("type"),$cctomail);
    	$response = new Response($r);
			$response->headers->set('Content-Type', 'text/html');
			return $response;
    }
    
    public function sendMsg2Action()
    {
    	//判断请求域。是wefafa或子域则不验证授权令牌
    	$isWeFaFaDomain = $this->checkWWWDomain();    	
    	$res = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($res,$da);	
    	   if(!$token)
    	   {
			       $response = new Response("{\"returncode\":\"9999\",\"code\":\"err1015\",\"msg\":\"参数Appid或Openid或Access_token未指定或无效.\"}");
						 $response->headers->set('Content-Type', 'text/html');
						 return $response;     	   	
    	   }
    	}
    	$cctomail = $res->get("cctomail");
    	$r = $this->sendMsg2($res->get("From").$res->get("from"),$res->get("To").$res->get("to"),$res->get("Message").$res->get("message"),"",true,$cctomail);
    	$response = new Response(json_encode($r));
			$response->headers->set('Content-Type', 'text/html');
			return $response;
    }
        
    
    ///向指定部门推送消息
    public function sendDeptMsgAction()
    {
    	//判断请求域。是wefafa或子域则不验证授权令牌
    	$isWeFaFaDomain = $this->checkWWWDomain();
    	$res = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($res,$da);
    	   if(!$token)
    	   {
			       $response = new Response("{\"returncode\":\"9999\",\"code\":\"err1015\",\"msg\":\"参数Appid或Openid或Access_token未指定或无效.\"}");
						 $response->headers->set('Content-Type', 'text/html');
						 return $response;     	   	
    	   }
    	}
    	//获取部门人员
    	$depts = trim($res->get("deptid"));
    	if(empty($depts))
    	{
			       $response = new Response("{\"returncode\":\"9999\",\"code\":\"err1015\",\"msg\":\"参数deptid未指定或无效.\"}");
						 $response->headers->set('Content-Type', 'text/html');
						 return $response;
    	}
    	$r="{\"returncode\" : \"0000\"}";
    	$depts = explode(",",$depts);
    	$staffMgr =new \Justsy\BaseBundle\Management\Staff($da,$this->get("we_data_access_im"),$res->get("openid"));
    	$staff = $staffMgr->getInfo();
    	if(empty($staff))
    	{
			       $response = new Response("{\"returncode\":\"9999\",\"code\":\"err1015\",\"msg\":\"参数openid未指定或无效.\"}");
						 $response->headers->set('Content-Type', 'text/html');
						 return $response;    	    	
    	}
    	$type = trim($res->get("type"));
    	$cctomail = $res->get("cctomail");
    	$deptMgr =new \Justsy\BaseBundle\Management\Dept($da,$this->get("we_data_access_im"));
    	for($pos=0;$pos<count($depts); $pos++)
    	{
    			if(empty($depts[$pos])) continue;
    			//判断当前人员是否部门所属企业成员
    			$eno = $deptMgr->getEno($depts[$pos]);
    			if(empty($eno)) continue;
    			if($eno != $staff["eno"]) continue;
    			
		    	$staffs = $deptMgr->getAllStaffJid($depts[$pos]);		    	
		    	if(count($staffs)>0)
		    	{
		    		  $jids=array();
				  	  for($i=0;$i<count($staffs);$i++)
				  	  {
				  	  	  $jids[]=$staffs[$i]["jid"];
				  	  }
		    	    $r = $this->sendMsg($res->get("From").$res->get("from"),implode(",",$jids),$res->get("Title").$res->get("title"),$res->get("Message").$res->get("message"),$res->get("Link").$res->get("link"),$res->get("Linktext").$res->get("Buttons").$res->get("linktext").$res->get("buttons"),false,$type,$cctomail);
		    	}
      }
    	$response = new Response($r);
			$response->headers->set('Content-Type', 'text/html');
			return $response;     	
    }
    ///向指定群组推送消息
    public function sendGroupMsgAction()
    {
    	//判断请求域。是wefafa或子域则不验证授权令牌
    	$isWeFaFaDomain = $this->checkWWWDomain();
    	$res = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($res,$da);
    	   if(!$token)
    	   {
			       $response = new Response("{\"returncode\":\"9999\",\"code\":\"err1015\",\"msg\":\"参数Appid或Openid或Access_token未指定或无效.\"}");
						 $response->headers->set('Content-Type', 'text/html');
						 return $response;     	   	
    	   }
    	}
    	//获取群组人员    	
    	$depts = trim($res->get("groupid"));
    	if(empty($depts))
    	{
			       $response = new Response("{\"returncode\":\"9999\",\"code\":\"err1015\",\"msg\":\"参数groupid未指定或无效.\"}");
						 $response->headers->set('Content-Type', 'text/html');
						 return $response;    		
    	}
    	$type = trim($res->get("type"));
    	$cctomail = $res->get("cctomail");
    	$deptMgr =new \Justsy\BaseBundle\Management\GroupMgr($da,$this->get("we_data_access_im"));
    	$r="{\"returncode\" : \"0000\"}";
    	$depts = explode(",",$depts);
    	for($pos=0;$pos<count($depts); $pos++)
    	{
    		  if(empty($depts[$pos])) continue;
    		  //判断自己是否该群组成员
          $isPriv = $deptMgr->IsExist($depts[$pos],$res->get("openid"));
    		  if(!$isPriv) continue;    		  
		    	$jids = $deptMgr->getGroupMembersJid($depts[$pos]);		    	
		    	if($jids!=null && count($jids)>0)
		    	{
		    	    $r = $this->sendMsg($res->get("From").$res->get("from"),implode(",",$jids),$res->get("Title").$res->get("title"),$res->get("Message").$res->get("message"),$res->get("Link").$res->get("link"),$res->get("Linktext").$res->get("Buttons").$res->get("linktext").$res->get("buttons"),false,$type,$cctomail);
		    	}
      }
    	$response = new Response($r);
			$response->headers->set('Content-Type', 'text/html');
			return $response;     	
    }
    ///向指定圈子推送消息
    public function sendCircleMsgAction()
    {
    	//判断请求域。是wefafa或子域则不验证授权令牌
    	$isWeFaFaDomain = $this->checkWWWDomain();
    	$res = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($res,$da);
    	   if(!$token)
    	   {
			       $response = new Response("{\"returncode\":\"9999\",\"code\":\"err1015\",\"msg\":\"参数Appid或Openid或Access_token未指定或无效.\"}");
						 $response->headers->set('Content-Type', 'text/html');
						 return $response;     	   	
    	   }
    	}
    	$depts = trim($res->get("circleid"));
    	if(empty($depts))
    	{
			       $response = new Response("{\"returncode\":\"9999\",\"code\":\"err1015\",\"msg\":\"参数circleid未指定或无效.\"}");
						 $response->headers->set('Content-Type', 'text/html');
						 return $response;    		
    	}    	
    	$type = trim($res->get("type"));
    	$cctomail = $res->get("cctomail");
    	$depts = explode(",",$depts);
    	$r="{\"returncode\" : \"0000\"}";
    	for($pos=0;$pos<count($depts); $pos++)
    	{
    		  if(empty($depts[$pos])) continue;

    	    $deptMgr =new \Justsy\BaseBundle\Management\CircleMgr($da,$this->get("we_data_access_im"),$depts[$pos]);    		  
    		  //判断自己是否该圈子成员。否则不能发送
    		  $isPriv = $deptMgr->IsExist($res->get("openid"));
    		  if(!$isPriv) continue;
    		  //获取圈子人员
		    	$jids = $deptMgr->getCircleMembersJid($depts[$pos]);
		    	if($jids!=null && count($jids)>0)
		    	{
		    	    $r = $this->sendMsg($res->get("From").$res->get("from"),implode(",",$jids),$res->get("Title").$res->get("title"),$res->get("Message").$res->get("message"),$res->get("Link").$res->get("link"),$res->get("Linktext").$res->get("Buttons").$res->get("linktext").$res->get("buttons"),false,$type,$cctomail);
		    	}
      }
    	$response = new Response($r);
			$response->headers->set('Content-Type', 'text/html');
			return $response;    	
    }
    ///向指定部门推送出席消息
    public function sendDeptPresenceAction()
    {
    	//判断请求域。是wefafa或子域则不验证授权令牌
    	$isWeFaFaDomain = $this->checkWWWDomain();
    	$res = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($res,$da);
    	   if(!$token)
    	   {
			       $response = new Response("{\"returncode\":\"9999\",\"code\":\"err1015\",\"msg\":\"参数Appid或Openid或Access_token未指定或无效.\"}");
						 $response->headers->set('Content-Type', 'text/html');
						 return $response;     	   	
    	   }
    	}
    	$depts = trim($res->get("deptid"));
    	if(empty($depts))
    	{
			       $response = new Response("{\"returncode\":\"9999\",\"code\":\"err1015\",\"msg\":\"参数deptid未指定或无效.\"}");
						 $response->headers->set('Content-Type', 'text/html');
						 return $response;    		
    	}    	
    	//获取部门人员
    	$depts = trim($res->get("deptid"));
    	if(empty($depts))
    	{
			       $response = new Response("{\"returncode\":\"9999\",\"code\":\"err1015\",\"msg\":\"参数deptid未指定或无效.\"}");
						 $response->headers->set('Content-Type', 'text/html');
						 return $response;    		
    	}
    	$cctomail = $res->get("cctomail");
    	$r="{\"returncode\" : \"0000\"}";
    	$depts = explode(",",$depts);
    	$deptMgr =new \Justsy\BaseBundle\Management\Dept($da,$this->get("we_data_access_im"));
    	for($pos=0;$pos<count($depts); $pos++)
    	{
    		  if(empty($depts[$pos])) continue;
		    	$staffs = $deptMgr->getAllStaffJid($depts[$pos]);		    	
		    	if(count($staffs)>0)
		    	{
		    		  $jids=array();
				  	  for($i=0;$i<count($staffs);$i++)
				  	  {
				  	  	  $jids[]=$staffs[$i]["jid"];
				  	  }
		    	    $r = $this->sendPresence($res->get("From").$res->get("from"),implode(",",$jids),$res->get("Title").$res->get("title"),$res->get("Message").$res->get("message"),$res->get("Link").$res->get("link"),$res->get("Linktext").$res->get("Buttons").$res->get("linktext").$res->get("buttons"),false,trim($res->get("type")),$cctomail);
		    	}
      }
    	$response = new Response($r);
			$response->headers->set('Content-Type', 'text/html');
			return $response;    	 	  
    }
    ///向指定群组推送出席消息
    public function sendGroupPresenceAction()
    {
    	//判断请求域。是wefafa或子域则不验证授权令牌
    	$isWeFaFaDomain = $this->checkWWWDomain();
    	$res = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($res,$da);
    	   if(!$token)
    	   {
			       $response = new Response("{\"returncode\":\"9999\",\"code\":\"err1015\",\"msg\":\"参数Appid或Openid或Access_token未指定或无效.\"}");
						 $response->headers->set('Content-Type', 'text/html');
						 return $response;     	   	
    	   }
    	}
    	//获取群组人员
    	$depts = trim($res->get("groupid"));
    	if(empty($depts))
    	{
			       $response = new Response("{\"returncode\":\"9999\",\"code\":\"err1015\",\"msg\":\"参数groupid未指定或无效.\"}");
						 $response->headers->set('Content-Type', 'text/html');
						 return $response;    		
    	}
    	$cctomail = $res->get("cctomail");
    	$deptMgr =new \Justsy\BaseBundle\Management\GroupMgr($da,$this->get("we_data_access_im"));
    	$r="{\"returncode\" : \"0000\"}";
    	$depts = explode(",",$depts);
    	for($pos=0;$pos<count($depts); $pos++)
    	{    	
    		  if(empty($depts[$pos])) continue;
		    	$jids = $deptMgr->getGroupMembersJid($depts[$pos]);
		    	if($jids!=null && count($jids)>0)
		    	{
		    	    $r = $this->sendPresence($res->get("From").$res->get("from"),implode(",",$jids),$res->get("Title").$res->get("title"),$res->get("Message").$res->get("message"),$res->get("Link").$res->get("link"),$res->get("Linktext").$res->get("Buttons").$res->get("linktext").$res->get("buttons"),false,trim($res->get("type")),$cctomail);
		    	}
      }
    	$response = new Response($r);
			$response->headers->set('Content-Type', 'text/html');
			return $response;       	  	
    }
    ///向指定圈子推送出席消息
    public function sendCirclePresenceAction()
    {
    	//判断请求域。是wefafa或子域则不验证授权令牌
    	$isWeFaFaDomain = $this->checkWWWDomain();
    	$res = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($res,$da);
    	   if(!$token)
    	   {
			       $response = new Response("{\"returncode\":\"9999\",\"code\":\"err1015\",\"msg\":\"参数Appid或Openid或Access_token未指定或无效.\"}");
						 $response->headers->set('Content-Type', 'text/html');
						 return $response;     	   	
    	   }
    	}
    	$depts = trim($res->get("circleid"));
    	if(empty($depts))
    	{
			       $response = new Response("{\"returncode\":\"9999\",\"code\":\"err1015\",\"msg\":\"参数circleid未指定或无效.\"}");
						 $response->headers->set('Content-Type', 'text/html');
						 return $response;    		
    	}
    	$cctomail = $res->get("cctomail");
    	//获取圈子人员
    	$deptMgr =new \Justsy\BaseBundle\Management\CircleMgr($da,$this->get("we_data_access_im"));
    	$depts = explode(",",$depts);
    	$r="{\"returncode\" : \"0000\"}";
    	for($pos=0;$pos<count($depts); $pos++)
    	{     	
    		  if(empty($depts[$pos])) continue;
		    	$jids = $deptMgr->getCircleMembersJid($depts[$pos]);
		    	if($jids!=null && count($jids)>0)
		    	{
		    	    $r = $this->sendPresence($res->get("From").$res->get("from"),implode(",",$jids),$res->get("Title").$res->get("title"),$res->get("Message").$res->get("message"),$res->get("Link").$res->get("link"),$res->get("Linktext").$res->get("Buttons").$res->get("linktext").$res->get("buttons"),false,trim($res->get("type")),$cctomail);
		    	}
      }
    	$response = new Response($r);
			$response->headers->set('Content-Type', 'text/html');
			return $response;    	
    }   

    //撤回一条消息
    //只能撤回当前操作者自己发送的消息
    public function revokeMsgAction()
    {
    	//判断请求域。是wefafa或子域则不验证授权令牌
    	$isWeFaFaDomain = $this->checkWWWDomain();    	
    	$request = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   		return $this->responseJson($request,Utils::WrapResultError('参数Appid或Openid或Access_token未指定或无效.'));
    	   }
    	}

    	$openid = $request->get("openid");
		$staffinfo = new \Justsy\BaseBundle\Management\Staff($da,$this->get("we_data_access_im"),$openid,$this->get("logger"),$this->container);
		$staffdata = $staffinfo->getInfo();
		if(empty($staffdata))
		{
			$result = Utils::WrapResultError("无效操作帐号");
			return $this->responseJson($request,$result);
		}

    	$to_jid = array();
    	$chatjid = $request->get('jid');
    	$to_jid[] = $chatjid;
    	$groupid = $request->get('groupid');
    	$msgid = $request->get('msgid');
    	if(empty($chatjid) && empty($groupid))
    	{
    		return $this->responseJson($request,Utils::WrapResultError('无效的撤回类型.'));
    	}
    	if(empty($msgid))
    	{
    		return $this->responseJson($request,Utils::WrapResultError('无效的消息ID.'));
    	}

    	if(!empty($groupid))
    	{
    		$groupMgr = new \Justsy\BaseBundle\Management\GroupMgr($da,$this->get("we_data_access_im"),$this->container);
    		$to_jid = $groupMgr->getGroupMembersJidByIM($groupid);
            //群消息默认采用的发送时的iq ID。生成规则：Msgid =case QMsgid of []-> From#jid.luser++"-"++IQId; _-> QMsgid end,
            //生成消息ID
            $jid_user = explode('@', $staffdata['jid'])[0];
            $msgid = $jid_user.'-'.$msgid;
    	}
    	$msgtype = !empty($chatjid) ? 'chat' : 'group';
    	
    	$notice = array();
		$message =json_encode(Utils::WrapMessage('message_revoke',
            array('type'=>$msgtype,'msgid'=>$msgid,
                  'sender'=>array('nick_name'=>$staffdata['nick_name'],'photo'=>$staffdata['photo_path'],'jid'=>$staffdata['jid'],'sendtime'=>date("Y-m-d H:i:s",time()))
                ),
            $notice));
        $success = Utils::sendImMessage($staffdata['jid'],$to_jid,"message_revoke",$message,$this->container,"","",false,Utils::$systemmessage_code);
    	
    	return $this->responseJson($request,Utils::WrapResultOK(''));
    }
    
    //添加定时提醒/通知任务
    //参数：
    public function timerRemindTask($from,$to,$receivedept,$busid,$timer,$title,$msg,$link,$linktext)
    {
    	$da = $this->get("we_data_access");
    	if(empty($from))
    	{
	       $domain =  $this->container->getParameter('edomain');
	        $from="admin@".$domain; 		
    	}
    	if(empty($msg))
    	{
	       $response = new Response("{\"returncode\":\"9999\",\"code\":\"err1001\",\"msg\":\"消息内容不能为空\"}");
				 $response->headers->set('Content-Type', 'text/html');
				 return $response;    		
    	}
    	//--------------check From--------------
    	if(!strpos($from,"@"))
    	{
    	    //获取opendid对应的jid，没找到则返回错误
    	    $table = $da->GetData("staff","select fafa_jid from we_staff where openid=?",array((String)$from));
    	    if(count($table["staff"]["rows"])==0)
    	    {
    	    	$response = new Response("{\"returncode\":\"9999\",\"code\":\"err1002\",\"msg\":\"消息发送者参数(From)无效\"}");
				    $response->headers->set('Content-Type', 'text/html');
				    return $response; 
    	    }
    	    $from = $table["staff"]["rows"][0]["fafa_jid"];
    	}
    	else if(strpos($from,"admin")===false){  //jid
    	    $table = $da->GetData("staff","select fafa_jid from we_staff where fafa_jid=?",array((String)$from));
    	    if(count($table["staff"]["rows"])==0)
    	    {
    	    	$response = new Response("{\"returncode\":\"9999\",\"code\":\"err1002\",\"msg\":\"消息发送者参数(From)无效\"}");
				    $response->headers->set('Content-Type', 'text/html');
				    return $response; 
    	    }
    	}

    	//-------------check To--------------
    	if(empty($to) && empty($receivedept))
    	{
		    	    	$response = new Response("{\"returncode\":\"9999\",\"code\":\"err1003\",\"msg\":\"消息接收者参数(To)无效\"}");
						    $response->headers->set('Content-Type', 'text/html');
						    return $response;    	    	
    	}
    	$arr = explode(",",$to);
    	$toJids = array();
    	$regUrl = $this->container->getParameter("FAFA_REG_JID_URL");
    	for($i=0;$i<count($arr);$i++)
    	{
    		  $to2 = $arr[$i];
    		  if(empty($to2)) continue;
		    	if(!strpos($to2,"@"))
		    	{    		  
		    	    //获取opendid对应的jid，没找到则返回错误
		    	    $table = $da->GetData("staff","select fafa_jid from we_staff where openid=?",array((String)$to2));
		    	    if(count($table["staff"]["rows"])==0)
		    	    {
		    	    	$response = new Response("{\"returncode\":\"9999\",\"code\":\"err1003\",\"msg\":\"消息接收者参数(To)无效\"}");
						    $response->headers->set('Content-Type', 'text/html');
						    return $response; 
		    	    }
		    	    $to2 = $table["staff"]["rows"][0]["fafa_jid"];
		    	}
		    	else if(strpos($to2,"admin")===false){  //jid
		    	    $table = $da->GetData("staff","select fafa_jid from we_staff where fafa_jid=?",array((String)$to2));
		    	    if(count($table["staff"]["rows"])==0)
		    	    {
		    	    	$response = new Response("{\"returncode\":\"9999\",\"code\":\"err1003\",\"msg\":\"消息接收者参数(To)无效\"}");
						    $response->headers->set('Content-Type', 'text/html');
						    return $response; 
		    	    }
		    	}
		    	$toJids[]= $to2;
      }
		  $regUrlOrg = $regUrl."/service.yaws";
		  $data="sendRemind=1&busid=$busid&sendemp=$from&receivedept=$receivedept&caption=$title&receive=".implode(",",$toJids)."&mssage=$msg&sendtype=02&sendtime=$timer&link=$link&linktext=$linktext";
		  //$this->get("logger")->alert("SEND REMIND URL:$regUrlOrg?$data");
		  $re =Utils::do_post_request($regUrlOrg,$data); // trim(Utils::getUrlContent($regUrlOrg,$this->get("logger"))); 
		  //$this->get("logger")->alert("SEND REMIND Result:$re");       
    	$response = new Response("{\"returncode\" : \"0000\"}");
			$response->headers->set('Content-Type', 'text/html');
			return $response;    	
    }
    public function timerRemindTaskAction()
    {
    	//判断请求域。是wefafa或子域则不验证授权令牌
    	$isWeFaFaDomain = $this->checkWWWDomain();    	
    	$res = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($res,$da);	
    	   if(!$token)
    	   {
			       $response = new Response("{\"returncode\":\"9999\",\"code\":\"err1015\",\"msg\":\"参数Appid或Openid或Access_token未指定或无效.\"}");
						 $response->headers->set('Content-Type', 'text/html');
						 return $response;     	   	
    	   }
    	}
    	$appid = urlencode(trim($res->get("Appid").$res->get("appid")));//应用标识。可选参数(如果有针对该记录进行提醒时间修改的可能，则一定要设置该参数)
    	$busid = empty($appid)?"":$appid.urlencode(trim($res->get("Busid").$res->get("busid")));//业务记录标识.可选参数(如果有针对该记录进行提醒时间修改的可能，则一定要设置该参数)
    	$timer = urlencode(trim($res->get("Time").$res->get("time"))); //提醒时间，格式为月,日,时,分,周,次数（为0时表示周期轮循发送，默认为发送1次）
    	$r = $this->timerRemindTask($res->get("From").$res->get("from"),
    	                                $res->get("To").$res->get("to"),
    	                            $res->get("Todept").$res->get("todept"),
    	                                         $busid,
    	                                         $timer,
    	                             $res->get("Title").$res->get("title"),
    	                           $res->get("Message").$res->get("message"),
    	                              $res->get("Link").$res->get("link"),
    	                          $res->get("Linktext").$res->get("Buttons").$res->get("linktext").$res->get("buttons"));
    	$response = new Response($r);
			$response->headers->set('Content-Type', 'text/html');
			return $response;      	 		
    }
    
    public function runonceRemindTaskAction()
    {
      //判断请求域。是wefafa或子域则不验证授权令牌
    	$isWeFaFaDomain = $this->checkWWWDomain();    	
    	$res = $this->get("request");
    	
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($res,$da);	
    	   if(!$token)
    	   {
	    	   	   $re = array("returncode"=>"9999");
				       $re["code"]="err0105";
	    	   	   $re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
				       $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
							 $response->headers->set('Content-Type', 'text/json');
							 return $response;  	   	
    	   }
    	}
    	$r = $this->runonceRemindTask($res->get("From").$res->get("from"),
    	                                $res->get("To").$res->get("to"),
    	                            $res->get("Todept").$res->get("todept"),
    	                             $res->get("Title").$res->get("title"),
    	                           $res->get("Message").$res->get("message"),
    	                              $res->get("Link").$res->get("link"),
    	                          $res->get("Linktext").$res->get("Buttons").$res->get("linktext").$res->get("buttons"));
    	$response = new Response($r);
			$response->headers->set('Content-Type', 'text/html');
			return $response;    	
    }
    //添加立即发送的提醒/通知/公告/任务等
    public function runonceRemindTask($from,$to,$receivedept,$title,$msg,$link,$linktext)
    {
    	if(empty($from))
    	{
	       $domain =  $this->container->getParameter('edomain');
	        $from="admin@".$domain; 		
    	}
    	if(empty($msg))
    	{
	       $response = new Response("{\"returncode\":\"9999\",\"code\":\"err1001\",\"msg\":\"消息内容不能为空\"}");
				 $response->headers->set('Content-Type', 'text/html');
				 return $response;    		
    	}
    	$da = $this->get("we_data_access");
    	//--------------check From--------------
    	if(!strpos($from,"@"))
    	{
    	    //获取opendid对应的jid，没找到则返回错误
    	    $table = $da->GetData("staff","select fafa_jid from we_staff where openid=?",array((String)$from));
    	    if(count($table["staff"]["rows"])==0)
    	    {
    	    	$response = new Response("{\"returncode\":\"9999\",\"code\":\"err1002\",\"msg\":\"消息发送者参数(From)无效\"}");
				    $response->headers->set('Content-Type', 'text/html');
				    return $response; 
    	    }
    	    $from = $table["staff"]["rows"][0]["fafa_jid"];
    	}
    	else if(strpos($from,"admin")===false){  //jid
    	    $table = $da->GetData("staff","select fafa_jid from we_staff where fafa_jid=?",array((String)$from));
    	    if(count($table["staff"]["rows"])==0)
    	    {
    	    	$response = new Response("{\"returncode\":\"9999\",\"code\":\"err1002\",\"msg\":\"消息发送者参数(From)无效\"}");
				    $response->headers->set('Content-Type', 'text/html');
				    return $response; 
    	    }
    	}

    	//-------------check To--------------
    	if(empty($to) && empty($receivedept))
    	{
		    	    	$response = new Response("{\"returncode\":\"9999\",\"code\":\"err1003\",\"msg\":\"消息接收者参数(To)无效\"}");
						    $response->headers->set('Content-Type', 'text/html');
						    return $response;    	    	
    	}
    	$arr = explode(",",$to);
    	$toJids = array();
    	$regUrl = $this->container->getParameter("FAFA_REG_JID_URL");
    	for($i=0;$i<count($arr);$i++)
    	{
    		  $to2 = $arr[$i];
    		  if(empty($to2)) continue;
		    	if(!strpos($to2,"@"))
		    	{    		  
		    	    //获取opendid对应的jid，没找到则返回错误
		    	    $table = $da->GetData("staff","select fafa_jid from we_staff where openid=?",array((String)$to2));
		    	    if(count($table["staff"]["rows"])==0)
		    	    {
		    	    	$response = new Response("{\"returncode\":\"9999\",\"code\":\"err1003\",\"msg\":\"消息接收者参数(To)无效\"}");
						    $response->headers->set('Content-Type', 'text/html');
						    return $response; 
		    	    }
		    	    $to2 = $table["staff"]["rows"][0]["fafa_jid"];
		    	}
		    	else if(strpos($to2,"admin")===false){  //jid
		    	    $table = $da->GetData("staff","select fafa_jid from we_staff where fafa_jid=?",array((String)$to2));
		    	    if(count($table["staff"]["rows"])==0)
		    	    {
		    	    	$response = new Response("{\"returncode\":\"9999\",\"code\":\"err1003\",\"msg\":\"消息接收者参数(To)无效\"}");
						    $response->headers->set('Content-Type', 'text/html');
						    return $response; 
		    	    }
		    	}
		    	$toJids[]= $to2;
      }
		  $regUrlOrg = $regUrl."/service.yaws";
		  $data="sendRemind=1&sendemp=$from&receivedept=$receivedept&caption=$title&receive=".implode(",",$toJids)."&mssage=$msg&sendtype=1&link=$link&linktext=$linktext";
		  //$this->get("logger")->alert("SEND REMIND URL:$regUrlOrg?$data");
		  $re =Utils::do_post_request($regUrlOrg,$data); // trim(Utils::getUrlContent($regUrlOrg,$this->get("logger"))); 
		  //$this->get("logger")->alert("SEND REMIND Result:$re");       
    	$response = new Response("{\"returncode\" : \"0000\"}");
			$response->headers->set('Content-Type', 'text/html');
			return $response;     	
    }
    public function removeRemindTaskAction()
    {
    	//判断请求域。是wefafa或子域则不验证授权令牌
    	$isWeFaFaDomain = $this->checkWWWDomain();    	
    	$res = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($res,$da);	
    	   if(!$token)
    	   {
			       $response = new Response("{\"returncode\" : \"9999\",\"code\":\"err1015\",\"msg\":\"参数Appid或Openid或Access_token未指定或无效.\"}");
						 $response->headers->set('Content-Type', 'text/html');
						 return $response;     	   	
    	   }
    	}
    	$busid = trim($res->get("ID"));
    	$regUrl = $this->container->getParameter("FAFA_REG_JID_URL");
		  $regUrlOrg = $regUrl."/service.yaws";
		  $data="removeRemind=1&busid=$busid";
		  //$this->get("logger")->alert("SEND API URL:$regUrlOrg?$data");
		  $re =Utils::do_post_request($regUrlOrg,$data); 
		  //$this->get("logger")->alert("SEND API Result:$re"); 
		  $response = new Response("{\"returncode\" : \"0000\"}");
			$response->headers->set('Content-Type', 'text/html');
    }
    //获取指定人员信息，获取人员的好友列表。对第三方开放接口。 
    public function getmyrelationAction()
    {
    	//判断请求域。是wefafa或子域则不验证授权令牌
    	$isWeFaFaDomain = $this->checkWWWDomain();    	
    	$request = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
			       $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;     	   	
    	   }
    	}
    	$ds=$this->checkOpenid($da ,$request->get("openid"));
    	if($ds===false)
    	{
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数openid未指定或无效.";
			       $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;    		
    	}	
    	$obj = new \Justsy\BaseBundle\Management\Staff($this->get("we_data_access"),$this->get("we_data_access_im"),$ds["login_account"],$this->get("logger"),$this->container);
      $result = $obj->getRelation();
    	$re = array("returncode" => "0000");
    	$re["list"] = $result;
			$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
			$response->headers->set('Content-Type', 'text/json');
			return $response;      
    }
    
    public function getuserinfoAction()
    {
    	//判断请求域。是wefafa或子域则不验证授权令牌
    	$isWeFaFaDomain = $this->checkWWWDomain();    	
    	$request = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
			       $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;     	   	
    	   }
    	}
    	$ds=$this->checkOpenid($da ,$request->get("openid"));
    	if($ds===false)
    	{
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数openid未指定或无效.";
			       $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;    		
    	}    	
    	$obj = new \Justsy\InterfaceBundle\Controller\BaseInfoController();
    	$obj->setContainer($this->container);
    	return $obj->getselfcardAction();
    }   
    
    public function getgroupsAction()
    {
    	//判断请求域。是wefafa或子域则不验证授权令牌
    	$isWeFaFaDomain = $this->checkWWWDomain();    	
    	$request = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
			       $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;     	   	
    	   }
    	}
    	$ds=$this->checkOpenid($da ,$request->get("openid"));
    	if($ds===false)
    	{
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数openid未指定或无效.";
			       $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;    		
    	}    	
    	$obj = new \Justsy\InterfaceBundle\Controller\BaseInfoController();
    	$obj->setContainer($this->container);
    	return $obj->getgroupsAction();
    }
    
    public function getcirclesAction()
    {
    	//判断请求域。是wefafa或子域则不验证授权令牌
    	$isWeFaFaDomain = $this->checkWWWDomain();    	
    	$request = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
			       $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;     	   	
    	   }
    	}
    	$ds=$this->checkOpenid($da ,$request->get("openid"));
    	if($ds===false)
    	{
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数openid未指定或无效.";
			       $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;    		
    	}    	
    	$obj = new \Justsy\InterfaceBundle\Controller\BaseInfoController();
    	$obj->setContainer($this->container);
    	return $obj->getcirclesAction();
    } 
    
    public function getgroupinfoAction()
    {
    	//判断请求域。是wefafa或子域则不验证授权令牌
    	$isWeFaFaDomain = $this->checkWWWDomain();    	
    	$request = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
			       $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;     	   	
    	   }
    	}
    	$groupid= $request->get("groupid");
    	if(empty($groupid))
    	{
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数groupid未指定或无效.";
			       $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;
    	}
    	$sql = "SELECT fafa_groupid groupid,group_name groupname,group_desc groupdesc,'' grouppost,group_class groupclass,group_photo_path logo,join_method,create_staff FROM we_sns.we_groups where fafa_groupid=? "
    	      ." union SELECT fafa_groupid groupid,circle_name groupname,circle_desc groupdesc,'' grouppost,'circlegroup' groupclass,logo_path logo,join_method,create_staff FROM we_sns.we_circle where fafa_groupid=?"
    	      ." union select groupid,groupname,groupdesc,grouppost,groupclass,logo,join_method,create_staff from (SELECT groupid,name groupname,subject groupdesc,item grouppost,case when cycle is null then 'discussgroup' else 'meeting' end groupclass,'' logo, '1' join_method,create_staff FROM we_sns.we_meeting_plan where groupid=? order by create_date desc limit 0,1) a;";
    	$ds = $da->GetData("data",$sql,array((string)$groupid,(string)$groupid,(string)$groupid)); 
    	$re = array("returncode" => "0000");
	    $re["group"] =count($ds["data"]["rows"])>0 ? $ds["data"]["rows"][0] : array();
	    
	    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
	    $response->headers->set('Content-Type', 'text/json');
	    return $response;    	
    }   
    

    //获取指定人员信息，只能获取本企业内的和好友信息。对第三方开放接口。   
    public function getstaffcardAction()
    {
    	//判断请求域。是wefafa或子域则不验证授权令牌
    	$isWeFaFaDomain = $this->checkWWWDomain();    	
    	$request = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
			       $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;    	   	
    	   }
    	}
    	$ds=$this->checkOpenid($da ,$request->get("openid"));
    	if($ds===false)
    	{
    	   	   	$re = array("returncode"=>"9999");
			    $re["code"]="err0105";
    	   	   	$re["msg"]="参数openid未指定或无效.";
			    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
				$response->headers->set('Content-Type', 'text/json');
				return $response;    		
    	}    	
    	$obj = new \Justsy\InterfaceBundle\Controller\BaseInfoController();
    	$obj->setContainer($this->container);
    	return $obj->getfriendcardAction();
    }
     
    
    public function getdepartmentAction()
    {
    	//判断请求域。是wefafa或子域则不验证授权令牌
    	$isWeFaFaDomain = $this->checkWWWDomain();    	
    	$res = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($res,$da);	
    	   if(!$token)
    	   {
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
			       $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;     	   	
    	   }
    	}
    	$ds=$this->checkOpenid($da ,$res->get("openid"));
    	if($ds===false)
    	{
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数openid未指定或无效.";
			       $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;    		
    	}    	
    	$obj = new \Justsy\InterfaceBundle\Controller\BaseInfoController();
    	$obj->setContainer($this->container);
    	return $obj->getdepartmentAction();
    }
    
    public function getenostaffAction()
    {
    	//判断请求域。是wefafa或子域则不验证授权令牌
    	$isWeFaFaDomain = $this->checkWWWDomain();    	
    	$res = $this->get("request");
    	$request = $res;
    	$da = $this->get("we_data_access");
        $openid=$res->get("openid");
        if(empty($openid)) return $this->responseJson($request,array('returncode'=>'9999','msg'=>'登录帐号不能为空。'));
        $appid=$res->get("appid");
        if(empty($appid)) return $this->responseJson($request,array('returncode'=>'9999','msg'=>'应用ID不能为空。'));
        $access_token=$res->get("access_token");
        if(empty($access_token)) return $this->responseJson($request,array('returncode'=>'9999','msg'=>'访问令牌不能为空。'));
    	if(!$isWeFaFaDomain)
    	{
    	    $token = $this->checkAccessToken($res,$da);	
    	    if(!$token)
    	    {
    	   	    $re = array("returncode"=>"9999");
			    //$re["code"]="err0105";
    	   	    $re["msg"]="访问令牌或应用ID无效。";
			    $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
				$response->headers->set('Content-Type', 'text/json');
				return $response;    	   	
    	    }
    	}
    	$ds=$this->checkOpenid($da ,$res->get("openid"));
    	if($ds===false)
    	{
    	   	    $re = array("returncode"=>"9999");
			    //$re["code"]="err0105";
    	   	    $re["msg"]="登录帐号不正确。";
			    $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
				$response->headers->set('Content-Type', 'text/json');
				return $response;    		
    	}
    	$obj = new \Justsy\InterfaceBundle\Controller\BaseInfoController();
    	$obj->setContainer($this->container);
    	return $obj->getenostaffAction();
    }
    

    public function getgroupmemberAction()
    {
    	//判断请求域。是wefafa或子域则不验证授权令牌
    	$isWeFaFaDomain = $this->checkWWWDomain();    	
    	$res = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($res,$da);	
    	   if(!$token)
    	   {
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
			       $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;    	   	
    	   }
    	}
    	$opneid = $res->get("openid");
    	$ds=$this->checkOpenid($da ,$opneid);
    	if($ds===false)
    	{
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数openid未指定或无效.";
			       $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;
    	}
    	$groupid = $res->get("groupid");
    	$obj = new \Justsy\BaseBundle\Management\GroupMgr($da,$this->get("we_data_access_im"));
    	$isPriv = $obj->IsExist($groupid,$opneid);
    	if($isPriv===false)
    	{
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="权限不足，不能获取该群组的成员信息.";
			       $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;    		
    	}
    	$datalist = $obj->getGroupMembers($groupid);
    	$re = array("returncode"=>"0000");
    	$re["members"]=$datalist==null? array(): $datalist;
			$response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
			$response->headers->set('Content-Type', 'text/json');
			return $response;
    }
    
    public function getcirclememberAction()
    {
    	//判断请求域。是wefafa或子域则不验证授权令牌
    	$isWeFaFaDomain = $this->checkWWWDomain();    	
    	$res = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($res,$da);	
    	   if(!$token)
    	   {
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
			       $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;    	   	
    	   }
    	}
    	$opneid = $res->get("openid");
    	$ds=$this->checkOpenid($da ,$opneid);
    	if($ds===false)
    	{
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数openid未指定或无效.";
			       $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;
    	}
    	$circleid = $res->get("circleid");
    	$obj = new \Justsy\BaseBundle\Management\CircleMgr($da,$this->get("we_data_access_im"),$circleid);
    	$isPriv = $obj->IsExist($opneid);
    	if($isPriv===false)
    	{
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="权限不足，不能获取该圈子的成员信息.";
			       $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;    		
    	}
    	$datalist = $obj->getCircleMembers($circleid);
    	$re = array("returncode"=>"0000");
    	$re["members"]=$datalist==null? array(): $datalist;
			$response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
			$response->headers->set('Content-Type', 'text/json');
			return $response;
    }    
    
	  public function createCircleAction()
	  {
   	//判断请求域。是wefafa或子域则不验证授权令牌
    	$isWeFaFaDomain = $this->checkWWWDomain();    	
    	$res = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($res,$da);	
    	   if(!$token)
    	   {
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
			       $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;    	   	
    	   }
    	}	
			//作自动登录
			$ds=$this->checkOpenid($da ,$res->get("openid"));
			if($ds!=false){
			  		$this->autoLogin($res,$ds['login_account']);
			}    	
    	$obj = new \Justsy\InterfaceBundle\Controller\BaseInfoController();
    	$obj->setContainer($this->container);
    	return $obj->createCircleAction();  
	  }
	  
	  public function joincircleAction()
	  {
   	  //判断请求域。是wefafa或子域则不验证授权令牌
    	$isWeFaFaDomain = $this->checkWWWDomain();    	
    	$res = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($res,$da);	
    	   if(!$token)
    	   {
    	   	   $re = array("returncode"=> "9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数Appid或Openid或Access_token未指定或无效。";
			       $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;    	   	
    	   }
    	}
    	//判断帐号是否有效
			$ds=$this->checkOpenid($da ,$res->get("openid"));
			if($ds===false){
    	   	   $re = array("returncode"=> "9999");
			       $re["code"]="err0199";
    	   	   $re["msg"]="参数openid未指定或无效。";
			       $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;			  		
			}
			$account_list=$res->get("lst");
			$circleid=$res->get("circleid");
      $nick_name = $ds['nick_name'];
      $fafa_jid = $ds['fafa_jid'];
      $account = $ds["login_account"];
      $circleMgr = new \Justsy\BaseBundle\Management\CircleMgr($da,$this->get("we_data_access_im"),$circleid);
      $circleObj = $circleMgr->Get();
      if($circleObj==null)
      {
    	   	   $re = array("returncode"=> "9999");
			       $re["code"]="err0199";
    	   	   $re["msg"]="参数circleid未指定或无效。";
			       $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;
      }
      $save = empty($account_list) ? $circleMgr->joinCircle($account,$nick_name) : $circleMgr->batchJoinCircle($account_list);
      if(!$save)
      {
    	   	   $re = array("returncode"=> "9999");
			       $re["code"]="err0199";
    	   	   $re["msg"]="成员已存在";
			       $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;      	
      }
    	$re = array("returncode"=> "0000");
			$response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
			$response->headers->set('Content-Type', 'text/json');
			return $response; 	    
	  }
    
    //根据openid获取jid及密码，主要用于第三方应用使用js消息库的登录
    //支持jsop
    public function getJidAction()
    {
    	  $request = $this->getRequest();
    	  $token = $request->get("access_token");
    	  $openid = $request->get("openid");
    	  $da = $this->get("we_data_access");
    	  $re = array("s"=>0);
    	  //判断token是否还有效
    	  $tokenR = $da->GetData("tken","select access_token_expires,appid from we_app_oauth_sessions where access_token=? and userid=?",
    	       array((String)$token,(string)$openid));
    	  if(count($tokenR["tken"]["rows"])>0)
    	  {
    	  	    $appid = $tokenR["tken"]["rows"][0]["appid"];
			    	  $table = $da->GetData("app","select appkey from we_appcenter_apps where appid=? and state=1",array((String)$appid));
			    	  if(count($table["app"]["rows"])==0)
			    	  {
			    	    	$re["msg"]="10009";
							    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
							    $response->headers->set('Content-Type', 'text/json');
							    return $response;
			    	  }
			    	  $appkey = $table["app"]["rows"][0]["appkey"];
			        $table = $da->GetData("staff","select fafa_jid,t_code from we_staff where openid=?",array((String)$openid));
			    	  if(count($table["staff"]["rows"])==0)
			    	  {
			    	    	$re["msg"]="10008";
							    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
							    $response->headers->set('Content-Type', 'text/json');
							    return $response; 
			    	  }
			    	  $p = trim(DES::decrypt($table["staff"]["rows"][0]["t_code"]));//获取原始密码
			    	  $pk = str_pad(substr($p,0,8),"0");
			    	  //$p = DES::encrypt2($p,$pk);                         //重新使用当前应用的key加密，应用得到后需解密才能使用
			    	  
			    	  $u = $table["staff"]["rows"][0]["fafa_jid"];	
			    	  $uk = str_pad(substr($u,0,8),"0");
			    	  //$u = DES::encrypt2($u,$uk);
			    	  $re=array("s"=>1,"qa"=> $u ,"xs"=> $p);
			  }
		    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		    $response->headers->set('Content-Type', 'text/json');
		    return $response;
    }
    public function getOpenidAction()
    {
    	$request = $this->getRequest();
    	if($_SERVER['REQUEST_METHOD']!="POST") 
    	  $ts="error:http method isn't POST";
    	else
    	{
    	    	$u = $request->get("u");
    	    	$p = $request->get("p");
    	    	$da = $this->get("we_data_access");
            $password = DES::encrypt($p);
    	    	$ds = $da->GetData("ood","select openid from we_staff where (login_account=? or fafa_jid=?) and t_code=?",array((string)$u,(string)$u,(string)$password));
    	    	if($ds && count($ds["ood"]["rows"])>0)
    	    	{
    	    	   $ts=$ds["ood"]["rows"][0]["openid"];	
    	    	}
    	    	else
    	    	{
    	    	    	$ts="error:username or pass not found";
    	    	}
    	}
      $response = new Response($ts);
		  return $response;    	
    }
    //获取平台的一次性访问授权码（其实就是加密的当前时间戳），一次性访问授权有效期为10秒
    //被调用的API总是应该校验该授权码的有效性
    public function getTmpAuthCodeAction()
    {
    	    $ts = time();
        	$ts = DES::encrypt($ts);
          $response = new Response($ts);
		      return $response;
    }
    
    public function getPresenceAction()
    {   	
        	$res = $this->getRequest();
        	$da = $this->get("we_data_access");
        	$da_im = $this->get("we_data_access_im");
        	
		    	//判断请求域。是wefafa或子域则不验证授权令牌
		    	$isWeFaFaDomain = $this->checkWWWDomain();  
		    	if(!$isWeFaFaDomain)
		    	{
		    	   $token = $this->checkAccessToken($res,$da);	
		    	   if(!$token)
		    	   {
	    	   	   $re = array("returncode"=>"9999");
				       $re["code"]="err0105";
	    	   	   $re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
				       $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
							 $response->headers->set('Content-Type', 'text/json');
							 return $response;     	   	
		    	   }
		    	}
    	$ds=$this->checkOpenid($da ,$res->get("openid"));
    	if($ds===false)
    	{
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数openid未指定或无效.";
			       $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;    		
    	}		    	
    	$obj = new \Justsy\InterfaceBundle\Controller\BaseInfoController();
    	$obj->setContainer($this->container);
    	return $obj->getstaffpresenceAction();		    	
    }
    
    //获取当前用户自有文档目录
    public function getUserDirectoryAction()
    {
    	$isWeFaFaDomain = $this->checkWWWDomain();    	
    	$res = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($res,$da);	
    	   if(!$token)
    	   {
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
			       $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;    	   	
    	   }
    	}
    	$ds=$this->checkOpenid($da ,$res->get("openid"));
    	if($ds===false)
    	{
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数openid未指定或无效.";
			       $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;    		
    	}    	
    	$obj = new \Justsy\InterfaceBundle\Controller\BaseInfoController();
    	$obj->setContainer($this->container);
    	return $obj->getCurrUserDirAction();
    }
    //获取共享目录
    public function getShareDirectoryAction()
    {
    	$isWeFaFaDomain = $this->checkWWWDomain();    	
    	$res = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($res,$da);	
    	   if(!$token)
    	   {
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
			       $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;    	   	
    	   }
    	}
    	$ds=$this->checkOpenid($da ,$res->get("openid"));
    	if($ds===false)
    	{
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数openid未指定或无效.";
			       $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;    		
    	}    	
    	$obj = new \Justsy\InterfaceBundle\Controller\BaseInfoController();
    	$obj->setContainer($this->container);
    	return $obj->getShareDirAction();
    }
    //根据目录获取目录下的文件
    public function getUserFilesAction()
    {
    	$isWeFaFaDomain = $this->checkWWWDomain();    	
    	$res = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($res,$da);	
    	   if(!$token)
    	   {
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
			       $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;    	   	
    	   }
    	}
    	$ds=$this->checkOpenid($da ,$res->get("openid"));
    	if($ds===false)
    	{
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数openid未指定或无效.";
			       $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;    		
    	}    	
    	$obj = new \Justsy\InterfaceBundle\Controller\BaseInfoController();
    	$obj->setContainer($this->container);
    	return $obj->getUserFilesAction();
    }
    //根据目录获取目录下的文件
    public function getShareFilesAction()
    {
    	$isWeFaFaDomain = $this->checkWWWDomain();    	
    	$res = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($res,$da);	
    	   if(!$token)
    	   {
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
			       $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;    	   	
    	   }
    	}
    	$ds=$this->checkOpenid($da ,$res->get("openid"));
    	if($ds===false)
    	{
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数openid未指定或无效.";
			       $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;    		
    	}    	
    	$obj = new \Justsy\InterfaceBundle\Controller\BaseInfoController();
    	$obj->setContainer($this->container);
    	return $obj->getShareFilesAction();
    }
    //专门为第三方应用进行手机绑定提供的接口。不需要登录
    public function getmobilevaildcodeAction($network_domain)
    {
    	$isWeFaFaDomain = $this->checkWWWDomain();    	
    	$res = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($res,$da);	
    	   if(!$token)
    	   {
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
			       $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;    	   	
    	   }
    	}
    	$ds=$this->checkOpenid($da ,$res->get("openid"));
    	if($ds===false)
    	{
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数openid未指定或无效.";
			       $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;    		
    	}
    	$obj = new \Justsy\BaseBundle\Controller\AccountController();
    	$obj->setContainer($this->container);
    	return $obj->getmobilevaildcode2Action($ds);
    }
    //专门为第三方应用进行手机绑定提供的接口。不需要登录
    public function savemobilebindAction($network_domain)
    {
    	$isWeFaFaDomain = $this->checkWWWDomain();    	
    	$res = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($res,$da);	
    	   if(!$token)
    	   {
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
			       $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;    	   	
    	   }
    	}
    	$ds=$this->checkOpenid($da ,$res->get("openid"));
    	if($ds===false)
    	{
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数openid未指定或无效.";
			       $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;    		
    	}
    	$obj = new \Justsy\BaseBundle\Controller\AccountController();
    	$obj->setContainer($this->container);
    	return $obj->savemobilebind2Action($ds);
    }
    //专门为第三方应用进行手机绑定提供的接口。不需要登录
    public function savemobileunbindAction($network_domain)
    {
    	$isWeFaFaDomain = $this->checkWWWDomain();    	
    	$res = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($res,$da);	
    	   if(!$token)
    	   {
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
			       $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;    	   	
    	   }
    	}
    	$ds=$this->checkOpenid($da ,$res->get("openid"));
    	if($ds===false)
    	{
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数openid未指定或无效.";
			       $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;
    	}
    	$obj = new \Justsy\BaseBundle\Controller\AccountController();
    	$obj->setContainer($this->container);
    	return $obj->savemobileunbind2Action($ds);
    }        
    public function getOrganizationAction()
    {
    	$isWeFaFaDomain = $this->checkWWWDomain();
    	$res = $this->get("request");
    	$da = $this->get("we_data_access");
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $this->checkAccessToken($res,$da);	
    	   if(!$token)
    	   {
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
			       $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;    	   	
    	   }
    	}
    	$ds=$this->checkOpenid($da ,$res->get("openid"));
    	if($ds===false)
    	{
    	   	   $re = array("returncode"=>"9999");
			       $re["code"]="err0105";
    	   	   $re["msg"]="参数openid未指定或无效.";
			       $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
						 $response->headers->set('Content-Type', 'text/json');
						 return $response;
    	}
    	else{
    		$this->autoLogin($res,$ds['login_account']);	
    	}
    	$obj = new \Justsy\InterfaceBundle\Controller\BaseInfoController();
    	$obj->setContainer($this->container);
    	return $obj->getOrganization();
    }
    
    public function checkWWWDomain()
	{
		try{
			//$this->get("logger")->info(">>>>>>>>>>>>>>>>>>>HTTP_REFERER:".$_SERVER["HTTP_REFERER"]);
        	if(!isset($_SERVER["HTTP_REFERER"]) || empty($_SERVER["HTTP_REFERER"])) return false;
        
        	$srv_name = $_SERVER["HTTP_REFERER"];        
            
        	//获取安全的，不需要验证访问令牌的域
			  $mayDomain = $this->getSecurityDomains();

			  //$this->get("logger")->err(">>>>>>>>>>>>>>>>>>>SecurityDomains:".json_encode($mayDomain));
			  if(in_array($srv_name,$mayDomain)) return true;
              $resDomainAry = parse_url ($srv_name);
              $host = $resDomainAry["host"];		  
			  if(Utils::is_ip($host)) $resDomain=$host;
			  else
			  {
			      if(empty($host)) return false;
			      $resDomain = strpos($host,".")===false? $host : substr($host, strpos($host,".")+1); 
			      
              }
			  //$this->get("logger")->err(">>>>>>>>>>>>>>>>>>>resDomain:".$resDomain);
		    return in_array($resDomain,$mayDomain);
		}
		catch(\Exception $e)
		{
		  	$this->get('logger')->err($e);
		  	return false;
		}
	}  
		
		private function getSecurityDomains()
		{
            $securityDomains = \Justsy\BaseBundle\Common\Cache_Enterprise::get("securityDomains","",$this->container);
			if(!empty($securityDomains))
			{
                return json_decode($securityDomains,true);	
			}
			$mayDomain=array("localhost","127.0.0.1");
			$configWeFaFa=$this->container->getParameter('open_api_url');//获取配置的wefafa地址
			$tmp = parse_url($configWeFaFa);
			$host = $tmp["host"];
			//$this->get("logger")->err(">>>>>>>>>>>>>>>>>>>open_api_url>host:".$host);
			if(Utils::is_ip($host)) $mayDomain[]= $host;
			else{
			  	  $host =substr($host, strpos($host,".")+1); 
			  	  $mayDomain[]= $host;
			}
			$configWeFaFa=$this->container->getParameter('fafa_appcenter_url');//获取配置的应用中心地址
			$tmp = parse_url($configWeFaFa);
			$host = $tmp["host"];
			//$this->get("logger")->err(">>>>>>>>>>>>>>>>>>>fafa_appcenter_url>host:".$host);
			if(Utils::is_ip($host)) $mayDomain[]= $host;
			else{
			  	  $host =substr($host, strpos($host,".")+1); 
			  	  $mayDomain[]= $host;
			}	
			//ApiController::$securityDomains = $mayDomain;
            \Justsy\BaseBundle\Common\Cache_Enterprise::set("securityDomains","",json_encode($mayDomain),0,$this->container);
			return $mayDomain;
		}
		
		private function checkOpenid($db,$openid)
		{
			$staffinfo = new Staff($db,$this->get("we_data_access_im"),$openid,$this->get("logger"),$this->container);
			$obj = $staffinfo->getInfo();
			return empty($obj) ? false : $obj;
		}
		

		  
		//验证授权令牌
		public function checkAccessToken($resquest,$db)
		{
			  //$sql = "select userid from we_app_oauth_sessions where appid=? and access_token=? and access_token_expires>=UNIX_TIMESTAMP(now())";
			  $sql = "select userid from we_app_oauth_sessions where appid=? and access_token=?";
			  $para=array();
              $para[]=(string)($resquest->get("Appid").$resquest->get("appid"));
			  
			  $para[]=(string)($resquest->get("Access_token").$resquest->get("access_token"));
			  $ds = $db->getData("che",$sql,$para);
			  if($ds && count($ds["che"]["rows"])>0){ 
			  	$userid=$ds["che"]["rows"][0]["userid"];
			  	$openid = $resquest->get("Openid").$resquest->get("openid");
			  	if($userid=="wefafaproxy" || $userid==$openid)
			  		return true;
			  	else 
			  		return false;
			  }
			  else{
			  	 $this->get("logger")->err("API access URL:".(isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"]:''));
			  	 $this->get("logger")->err("REQUEST_URI:".(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI']:''));
                 $this->get("logger")->err("QUERY_STRING:".(isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING']:''));
                 $this->get("logger")->err("argv:".(isset($_SERVER['argv']) ? $_SERVER['argv']:''));
                 $this->get("logger")->err("access_token not pass:");
			  	 $this->get("logger")->err("                appid:".$resquest->get("appid"));
			  	 $this->get("logger")->err("               openid:".$resquest->get("openid"));
			  	 $this->get("logger")->err("         access_token:".$resquest->get("access_token"));
			  	 return false;
			  }
		}
    //自动登录
    private function autoLogin($res,$login_account)
    {
    	$userprovider=new UserProvider($this->container);
  	  $user = $userprovider->loadUserByUsername($login_account);
  	  $token = new UsernamePasswordToken($user, $user->getPassword(), "secured_area", $user->getRoles());
  	  $this->get("security.context")->setToken($token);
  	  $session = $res->getSession()->set('_security_'.'secured_area',  serialize($token));
  		return empty($user)? false:true;
    }
    //comfrom
    private function setComefrom($appid)
    {
    	$request = $this->getRequest();
    	$da = $this->get('we_data_access');
    	$sql="select comefrom from we_appcenter_apps where appid=?";
    	$params=array((string)$appid);
    	$ds=$da->Getdata('comefrom',$sql,$params);
    	if($ds['comefrom']['recordcount']>0){
    		$comefrom=$ds['comefrom']['rows'][0]['comefrom'];
    		$request->getSession()->set('comefrom',$comefrom);
    	}
    }
    private  function formatXml($xml)
    {
        $result = $xml;
        $result =str_replace("&","&amp;" ,$result);
        $result = str_replace("<","&lt;",$result);
        $result = str_replace(">","&gt;",$result);
        $result = str_replace("'","&apos;",$result);
        $result = str_replace("\"","&quot;",$result);
        return $result;
    }

    private  function formatText($text)
    {
        $result = $text;
        $result =str_replace("&amp;" ,"&",$result);
        $result = str_replace("&lt;","<",$result);
        $result = str_replace("&gt;",">",$result);
        $result = str_replace("&apos;","'",$result);
        $result = str_replace("&quot;","\"",$result);
        return $result;
    }  
      
  //错误报告，用于各客户端崩溃时上传错误信息
  public function erreportAction() 
  {
    $re = array("returncode" => "0000");
    $request = $this->getRequest();
    
    $da = $this->get('we_data_access'); 
    
    $report_staff = $request->get("report_staff");
    $report_device = $request->get("report_device");
    $report_content = $request->get("report_content");
    
    try 
    {
      $report_id = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "wa_err_report", "report_id");
      
      $sqls = array();
      $all_params = array();
      
      $sqlInsert = 'insert into wa_err_report (report_id, report_staff, report_date, report_device, report_content) values (?, ?, CURRENT_TIMESTAMP(), ?, ?)';
      $params = array();
      $params[] = (string)$report_id;
      $params[] = (string)$report_staff;
      $params[] = (string)$report_device;
      $params[] = (string)$report_content;
            
      $sqls[] = $sqlInsert;
      $all_params[] = $params;
            
      $da->ExecSQLs($sqls, $all_params); 
    } 
    catch (\Exception $e) 
    {
    $this->get('logger')->err($e->getMessage());
      $re["returncode"] = "9999";
      $this->get('logger')->err($e);
    }
    
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  
  //根据公众号和appkey MD5加密认证码获取公众号信息
  public function getMicroaccountAction(){
    $r["returncode"]=ReturnCode::$SUCCESS;
    $r["msg"]=null;
    $r["data"]=null;
    $request = $this->get("request");
    $conn = $this->get("we_data_access");
    $conn_im = $this->get("we_data_access_im");
    $micro_account=$request->get("microaccount");
    $unique_id=$request->get("uniqueid");
    if(empty($micro_account)){
        $r["returncode"]=ReturnCode::$SYSERROR;
        $r["msg"]="参数不能为空。";
    }
    else{
        $sql="select count(1) as count from we_appcenter_apps where md5(appkey)=?;";
        $para=array($unique_id);
        $data=$conn->GetData("dt",$sql,$para);
        if($data!=null&& count($data["dt"]["rows"])>0 && $data["dt"]["rows"][0]["count"]>0){
            $sql="select a.*,'' as groupid,b.openid,b.fafa_jid from we_micro_account a left join we_staff b on a.number=b.login_account and a.eno=b.eno where a.number=? ;";
            $para=array((string)$micro_account);
            $data_account=$conn->GetData("dt",$sql,$para);
            if($data_account!=null && count($data_account["dt"]["rows"])>0){
                $sql_group="select GROUP_CONCAT(a.id) as groupid from we_micro_account_group a where a.micro_account=?;";
                $para_group=array((string)$micro_account);
                $data_group=$conn->GetData("dt",$sql_group,$para_group);
                if($data_group!=null && count($data_group["dt"]["rows"])>0){
                    $data_account["dt"]["rows"][0]["groupid"]=$data_group["dt"]["rows"][0]["groupid"];
                }
                $r["returncode"]=ReturnCode::$SUCCESS;
                $r["data"]=$data_account["dt"]["rows"][0];
            }else{
                $r["returncode"]=ReturnCode::$SYSERROR;
                $r["msg"]="微应用帐号不存在。";
            }
        }else{
            $r["returncode"]=ReturnCode::$SYSERROR;
            $r["msg"]="访问地址不存在。";
        }
    }

    $response = new Response(json_encode($r));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  
    
	//通过邮箱注册用户
	public function mail_registerStaff($appid,$code,$eno,$stafflist){
			$conn = $this->get("we_data_access");
      $conn_im = $this->get("we_data_access_im");
      $request = $this->getRequest();
			//if(empty($appid)) return array("returncode"=>ReturnCode::$SYSERROR,"msg"=>"应用ID不能为空。");
      //if(empty($code)) return array("returncode"=>ReturnCode::$SYSERROR,"msg"=>"动态授权码不能为空。");
      if(empty($eno)) return array("returncode"=>ReturnCode::$SYSERROR,"msg"=>"企业编号不能为空。");
      if(empty($stafflist)) return array("returncode"=>ReturnCode::$SYSERROR,"msg"=>"注册人员不能为空，");

      $sql_app="select appkey from we_appcenter_apps where appid=?";
      $para_app=array($appid);
      $data_app=$conn->GetData("dt",$sql_app,$para_app);
      if( $data_app==null || count($data_app["dt"]["rows"])==0 || empty($data_app["dt"]["rows"][0]["appkey"])) {
          //return array("returncode"=>ReturnCode::$SYSERROR,"msg"=>"应用ID不正确。");
      }
//        $appkey=$data_app["dt"]["rows"][0]["appkey"];
//        if(strtolower($code)!=strtolower(MD5($appid.$appkey))){
//            //return array("returncode"=>ReturnCode::$SYSERROR,"msg"=>"动态授权码不正确。");
//        }
      //判断企业是否存在
      $sql_eno="select a.eno,a.eno_level,a.create_staff,b.auth_level,a.sys_manager,a.ename from we_enterprise a left join we_staff b on a.create_staff=b.login_account where a.eno=?";
      $para_eno=array($eno);
      $data_eno=$conn->GetData("dt",$sql_eno,$para_eno);
      if( $data_eno==null || count($data_eno["dt"]["rows"])==0 || empty($data_eno["dt"]["rows"][0]["eno"])) {
          return array("returncode"=>ReturnCode::$SYSERROR,"msg"=>"企业编号不正确。");
      }
      $stafflist=json_decode($stafflist,true);
      $create_staff=$data_eno["dt"]["rows"][0]["create_staff"];
      if(empty($stafflist)) return array("returncode"=>ReturnCode::$SYSERROR,"msg"=>"注册人员不正确。");

      $staffdata=array();
      $staffreg=array();
      $staffnoreg=array();
      $json=array();
      $ename=$data_eno["dt"]["rows"][0]['ename'];//企业名称
      $eno_level=$data_eno["dt"]["rows"][0]['eno_level'];//企业属于什么角色
      $auth_level=$data_eno["dt"]["rows"][0]['auth_level'];//企业创建者属于什么角色
      $sys_manager=$data_eno["dt"]["rows"][0]['sys_manager'];//企业管理员
      $mobileReg=new \Justsy\InterfaceBundle\Controller\MobileRegisterController();
      $mobileReg->setContainer($this->container);
      for ($i=0; $i < count($stafflist); $i++) { 
          if(!empty($stafflist[$i]["reg_name"]) && !empty($stafflist[$i]["email"])){
              //需要验证电子邮箱的合法性
              $pattern = $this->checkmail();
              $login_account=$stafflist[$i]['email'];
              $reg_name=$stafflist[$i]['reg_name'];                
              $password = isset($stafflist[$i]["password"]) ? $stafflist[$i]["password"] : "123456";
              $ldap_uid = isset($stafflist[$i]["uid"]) ? $stafflist[$i]["uid"] : "";
              
              $this->get("logger")->err("-----------------传入的ldap_uid:".$ldap_uid);
                          
              if(preg_match($pattern,$login_account)){//验证通过
                  $sql_staff="select eno,mobile,openid,nick_name,auth_level from we_staff where login_account=? ";
                  $para_staff=array($login_account);
                  $data_staff=$conn->GetData("dt",$sql_staff,$para_staff);
                  if($data_staff!=null && count($data_staff["dt"]["rows"])>0 && !empty($data_staff["dt"]["rows"][0]["openid"])) {//成员已经存在
                      if($data_staff["dt"]["rows"][0]["eno"]!=$eno) {
                          array_push($staffreg, array("openid"=>"","login_account"=>$login_account,"reg_name"=>$reg_name,"msg"=>"注册人邮箱已注册，加入企业编号与【".$eno."】不是同一企业。"));
                      } else if($data_staff["dt"]["rows"][0]["nick_name"]!=$reg_name) {
                          array_push($staffreg, array("openid"=>"","login_account"=>$login_account,"reg_name"=>$reg_name,"msg"=>"注册人邮箱已注册，注册人姓名与【".$reg_name."】不一致。"));
                      } else { //同一企业的同一用户需要修改权限
                          $mobile=$data_staff["dt"]["rows"][0]["mobile"];
                          $staff_auth_level=$data_staff["dt"]["rows"][0]["auth_level"];
                          //权限与管理员不一致，修改人员权限
                          if($staff_auth_level!=$auth_level) {
                              try {
                                  $sql_upd="update we_staff set auth_level=? where login_account=? ";
                                  $para_upd=array($staff_auth_level,$login_account);
                                  $conn->ExecSQL($sql_upd,$para_upd);
                                  $staffRole=new \Justsy\BaseBundle\Rbac\staffRole($conn,$conn_im,$this->container);
                                  $staffRole->UpdateStaffRoleByCode($login_account,$auth_level.$eno_level,$staff_auth_level.$eno_level,$eno);       
                              } catch (\Exception $e) {
                                  $this->get("logger")->err($e->getMessage());
                              }
                          }
                          $staffMgr = new \Justsy\BaseBundle\Management\Staff($conn,$conn_im,$login_account,$this->get("logger"));
                          //和管理员相互添加好友
                          if(!empty($sys_manager)) {
                              $sysmanager=explode(';',$sys_manager);
                              //循环添加管理员为好友
                              for ($i=0; $i < count($sysmanager); $i++) {
                                  $manager_staff=trim($sysmanager[$i]);
                                  try {
                                      if(!empty($manager_staff)) $staffMgr->bothAddFriend($this->container,$manager_staff);    
                                  } catch (\Exception $e) {
                                      $this->get("logger")->err($e->getMessage());
                                  }
                              }
                          }
                          try {
                              //和创建者相互添加好友
                              $staffMgr->bothAddFriend($this->container,$create_staff);
                          } catch (\Exception $e) {
                              $this->get("logger")->err($e->getMessage());
                          }
                          array_push($staffreg, array("openid"=>$data_staff["dt"]["rows"][0]["openid"],"login_account"=>$login_account,"reg_name"=>$reg_name,"msg"=>"注册人邮箱已注册。"));
                      }
                  } else {//手机号码未被注册
                      //获取企业名称
                      $sql="select ename from we_enterprise where eno=?";
                      $params=array($eno);
                      $ds=$conn->Getdata('enoname',$sql,$params);
                      $ename='';
                      $mailtype='';
                      if($ds['enoname']['recordcount']>0){
                      	$ename=$ds['enoname']['rows'][0]['ename'];
                      }
                      $sql1="select 1 from we_public_domain where domain_name=?";
								      $params1=array($this->getSubDomain($login_account));
								      $ds1=$conn->Getdata('tt',$sql1,$params1);
								      if($ds1['tt']['recordcount']>0)
								      	$mailtype='0';
								      else
								        $mailtype='1';
                      $active=new \Justsy\BaseBundle\Controller\ActiveController();
                      $active->setContainer($this->container);
                      $active->doSave(array(
                      	'account'=> $login_account,
                      	'realName'=>$reg_name,
                      	'passWord'=> $password,
                      	'eno'=> $eno,
                      	'ename'=> $ename,
                      	'isNew'=>'0',
                      	'mailtype'=> $mailtype,
                      	'ldap_uid'=> $ldap_uid,
                      	'import'=>'1'
                      ));
                      $sql="select openid from we_staff where login_account=?";
                      $params=array($login_account);
                      $ds=$conn->Getdata('op',$sql,$params);
                      $openid='';
                      if($ds['op']['recordcount']>0)
                      	$openid=$ds['op']['rows'][0]['openid'];
                      $res=array('openid'=>$openid);
                      if(!empty($res["openid"])){
                      		//更改其他信息
                          array_push($staffdata, array("openid"=>$res["openid"],"login_account"=>$login_account));//"reg_name"=>$reg_name,
                      } else {//注册成员失败
                          $msg="注册成员失败。";
                          if(!empty($res["msg"])) $msg=$res["msg"];
                          array_push($staffnoreg, array("login_account"=>$login_account,"reg_name"=>$reg_name,"msg"=>$msg));
                      }
                  }
              }else{//电子邮件格式不对
                  array_push($staffnoreg,array("login_account"=>$login_account,"reg_name"=>$reg_name,"msg"=>"注册人帐号格式不正确。") );
              }
          }
      }
      //返回结果
      if(empty($staffdata) && empty($staffreg) && empty($staffnoreg)) $json=array("returncode"=>ReturnCode::$SYSERROR,"msg"=>"没有人员需要注册。");
      else $json=array("returncode"=>ReturnCode::$SUCCESS,"list"=>$staffdata,"reg"=>$staffreg,"noreg"=>$staffnoreg);

      return $json;
	}  
  
    public function getProxytokenAction() {
        
        $request = $this->getRequest();
        if($_SERVER['REQUEST_METHOD']!="POST") return $this->responseJson($request,array("error"=>"10009","msg"=>"HTTP请求仅支持POST提交方式"));
        $conn = $this->get("we_data_access");
        $conn_im = $this->get("we_data_access_im");
        $appid=trim($request->get("appid"));
        $openid=trim($request->get("openid"));
        $code=trim($request->get("code"));
        $grant_type=trim($request->get("grant_type"));
        $state=trim($request->get("state"));
        if(empty($appid)) return $this->responseJson($request,array("error"=>ReturnCode::$SYSERROR,"msg"=>"应用ID不能为空。"));
        if(empty($code)) return $this->responseJson($request,array("error"=>ReturnCode::$SYSERROR,"msg"=>"动态授权码不能为空。"));
        if(empty($grant_type)) return $this->responseJson($request,array("error"=>ReturnCode::$SYSERROR,"msg"=>"固定值grant_type不能为空。"));
        if($grant_type!=="proxy") return $this->responseJson($request,array("error"=>ReturnCode::$SYSERROR,"msg"=>"固定值grant_type不正确。"));

        $sql_app="select appkey from we_appcenter_apps where appid=?";
        $para_app=array($appid);
        $data_app=$conn->GetData("dt",$sql_app,$para_app);
        if( $data_app==null || count($data_app["dt"]["rows"])==0 || empty($data_app["dt"]["rows"][0]["appkey"])) {
            return $this->responseJson($request,array("returncode"=>ReturnCode::$SYSERROR,"msg"=>"应用ID不正确。"));
        }
        $appkey=$data_app["dt"]["rows"][0]["appkey"];
        if(strtolower($code)!=strtolower(MD5($appid.$appkey))){
            return $this->responseJson($request,array("returncode"=>ReturnCode::$SYSERROR,"msg"=>"动态授权码不正确。"));
        }
        $json=$this->getProxySession($appid,$code,$state,$openid);
        $json = Utils::WrapResultOK($json);
        return $this->responseJson($request,$json);
    }


    public function getProxySession($appid,$code,$state,$userid = "wefafaproxy") {
        $conn = $this->get("we_data_access");
        $conn_im = $this->get("we_data_access_im");
        $expires_in = 60*60*24; //一天
        $sql = "select * from we_app_oauth_sessions where appid=? and user_type='sys' and userid=? and access_token_expires>=?";
        $time=time();
        $data = $conn->GetData("dt",$sql,array((string)$appid,(string)$userid,$time));
        $accessTokenExpires = $time + $expires_in;
        $response=array();
        if($data!=null && count($data["dt"]["rows"])>0 && !empty($data["dt"]["rows"][0]["access_token"])) {//token存在并且有效
            try {
                $sql_upd="update we_app_oauth_sessions set last_updated=? where appid=? and user_type='sys' and userid=? ";
                $conn->ExecSQL($sql_upd,array($time,(string)$appid,(string)$userid));
                $access_token=$data["dt"]["rows"][0]["access_token"];
                $response = array(
                    'access_token' => $access_token,
                    'token_type' => 'bearer',
                    'expires' => $accessTokenExpires,
                    'expires_in' => $expires_in,
                    'state' => $state
                );
            }catch (\Exception $e) {
                $response = array(
                    'error' => ReturnCode::$SYSERROR,
                    'msg' => '获取token失败，请稍后重试。'
                );
                $this->get('logger')->err($e);
            }
        }else {
            $sql_token="select * from we_app_oauth_sessions where appid=? and user_type='sys' and userid=? ";
            $data_token=$conn->GetData("dt",$sql_token,array((string)$appid,(string)$userid));
            //存在APPID缓存,并且存在Token,只是Token过期
            if($data_token!=null && count($data_token["dt"]["rows"])>0 && !empty($data_token["dt"]["rows"][0]["access_token"])) {
                try {
                    $access_token=$this->createKey($appid);
                    if(empty($access_token)) {
                        $response = array(
                            'error' => ReturnCode::$SYSERROR,
                            'msg' => '生成token失败，请稍后重试。'
                        );
                    }else {
                        $sql_upd="update we_app_oauth_sessions set access_token=?, access_token_expires=?,last_updated=? where appid=? and user_type='sys' and userid=? ";
                        $conn->ExecSQL($sql_upd,array((string)$access_token,(string)$accessTokenExpires,$time,(string)$appid,(string)$userid));
                        $response = array(
                            'access_token' => $access_token,
                            'token_type' => 'bearer',
                            'expires' => $accessTokenExpires,
                            'expires_in' => $expires_in,
                            'state' => $state
                        );
                    }
                }catch (\Exception $e) {
                    $response = array(
                        'error' => ReturnCode::$SYSERROR,
                        'msg' => '获取token失败，请稍后重试。'
                    );
                    $this->get('logger')->err($e);
                }
            //存在APPID缓存,但是TOKEN为空,生成Token并修改记录
            }else if($data_token!=null && count($data_token["dt"]["rows"])>0 && empty($data_token["dt"]["rows"][0]["access_token"])) {
                try {
                    $access_token=$this->createKey($appid);
                    if(empty($access_token)) {
                        $response = array(
                            'error' => ReturnCode::$SYSERROR,
                            'msg' => '生成token失败，请稍后重试。'
                        );
                    }else {
                        $sql_upd="update we_app_oauth_sessions set access_token=?,access_token_expires=?,last_updated=? where appid=? and user_type='sys' and userid=? ";
                        $conn->ExecSQL($sql_upd,array((string)$access_token,(string)$accessTokenExpires,$time,(string)$appid,(string)$userid));
                        $response = array(
                            'access_token' => $access_token,
                            'token_type' => 'bearer',
                            'expires' => $accessTokenExpires,
                            'expires_in' => $expires_in,
                            'state' => $state
                        );
                    }
                } catch (\Exception $e) {
                    $response = array(
                        'error' => ReturnCode::$SYSERROR,
                        'msg' => '获取token失败，请稍后重试。'
                    );
                    $this->get('logger')->err($e);
                }
            //不存在APPID缓存,生成Token并添加一条记录
            }else {
                try {
                    $access_token=$this->createKey($appid);
                    if(empty($access_token)) {
                        $response = array(
                            'error' => ReturnCode::$SYSERROR,
                            'msg' => '生成token失败，请稍后重试。'
                        );
                    }else {
                        $refresh_token=$this->createKey($appid);
                        $sql_insert="INSERT INTO we_app_oauth_sessions(id,appid,user_type,userid,access_token,access_token_expires,auth_code,auth_code_expires,stage,refresh_token,redirect_uri,first_requested,last_updated) VALUES(?,?,?,?,?,?,?,?,?,?,'',?,?)";
                        $id=SysSeq::GetSeqNextValue($conn,"we_app_oauth_sessions","id");
                        $stage="requested";
                        $auth_code="";
                        $user_type="sys";
                        $auth_code_expires=30000;
                        $paras=array($id,(string)$appid,(string)$user_type,(string)$userid,$access_token,$accessTokenExpires,$auth_code,$auth_code_expires,$stage,$refresh_token,$time,$time);

                        $conn->ExecSQL($sql_insert,$paras);
                        $response = array(
                            'access_token' => $access_token,
                            'token_type' => 'bearer',
                            'expires' => $accessTokenExpires,
                            'expires_in' => $expires_in,
                            'state' => $state
                        );
                    }
                } catch (\Exception $e) {
                    $response = array(
                        'error' => ReturnCode::$SYSERROR,
                        'msg' => '获取token失败，请稍后重试。'
                    );
                    $this->get('logger')->err($e);
                }
            }
        }
        return $response;
    }

    public function createKey($appendchar="") {
        return md5(time().$appendchar);
        // We generate twice as many bytes here because we want to ensure we have
        // enough after we base64 encode it to get the length we need because we
        // take out the "/", "+", and "=" characters.
        //$bytes = openssl_random_pseudo_bytes($len * 2, $strong);
        //var_dump($bytes);
        // We want to stop execution if the key fails because, well, that is bad.
        //if ($bytes === false || $strong === false) {
            // @codeCoverageIgnoreStart
            //throw new \Exception('Error Generating Key');
            //return "";
            // @codeCoverageIgnoreEnd
        //}
        //return substr(str_replace(array('/', '+', '='), '', base64_encode($bytes)), 0, $len);
    }

	private function responseJson($request,$re)
	{
		$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		$response->headers->set('Content-Type', 'text/json');	
		return $response;
	}

    private function checkmobile(){
        return "/^13[0-9]{9}$|15[0-9]{9}$|18[0-9]{9}$|19[0-9]{9}$/";
    }
    private function checkmail(){
        return "/([a-z0-9]*[-_.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[.][a-z]{2,3}([.][a-z]{2})?/i";
    }

    private function checkint($num){
        if (is_int($num) && ($num>=0)) return true;
        else return false;
    }
    
    //type值对应关系：0:PC版;1:Android版;2:IOS版
    public function check_VersionAction()
    {
       $da = $this->get('we_data_access');
       $request = $this->getRequest();
       $version = $request->get("ver");
       $type = $request->get("type");
       if (empty($type))
         $type = 0;
       $url = $this->container->getParameter('FILE_WEBSERVER_URL');
       //$url = str_replace("https","http",$url); 
       //判断是否有最新版本
       $sql = "select type,filename,left(replace(version,'.',''),4) as new_ver,version,concat('$url',fileid) url,plist_fileid,plist_url,replace(update_content,'|','｜') update_content                     
               from we_version where type=? order by id desc limit 1;";
       $para = array((string)$type);
       $returncode = ReturnCode::$SUCCESS;
       $msg = "";$down_url="";$update_content="";$newversion="";
       try
       {
       	  $ds = $da->GetData("table",$sql,$para);
          $result = array();
          if ( $ds && $ds["table"]["recordcount"]>0)
          {
            $newversion = $ds["table"]["rows"][0]["version"];
            //比较版本
            $new_ver = $ds["table"]["rows"][0]["new_ver"];
            $version = str_replace(".","",$version);
            //$version = substr($version,0,3);             
            if ( !empty($version) && !empty($new_ver) && (int)$new_ver > (int)$version)
            {            
                if ( $type==2)
                {
	                  $plist_url = $ds["table"]["rows"][0]["plist_url"];
	                  if(empty($plist_url))
	                  {
	                    $down_url = $this->container->getParameter('open_api_url');
	                    $down_url.= "/home/download/ios.plist";
	                  }
	                  else
	                  {
	                    $down_url = $plist_url;
	                  }
	                  //$down_url = urlencode($down_url);
	                  //暂时注释掉
	                  //$down_url ="itms-services://?action=download-manifest&url=$down_url";
                }
                else{
                   $down_url = $ds["table"]["rows"][0]["url"];
                }
                $update_content = $ds["table"]["rows"][0]["update_content"];
            }
            else{
              $msg="目前没有版本更新！";
              $newversion = "";
            }
          }
          else{
             $msg="目前没有版本更新！";
             $newversion="";
          }
       }
       catch(\Exception $e){
           $returncode = ReturnCode::$SYSERROR;
           $this->get("logger")->err($e->getMessage());
           $msg = "检查最新版本数据失败！";
       }
       $result = array("returncode"=>$returncode,"msg"=>$msg,"dow_url"=>$down_url,"update_desc"=>$update_content,"new_ver"=>$newversion);
       $response = new Response(json_encode($result));
       $response->headers->set('Content-Type', 'text/json');
       return $response;
    }
	  
	  //最新版本二维码下载
	  public function dowloadAction()
	  {
	  	$url = $this->container->getParameter('FILE_WEBSERVER_URL');
	  	$list = array();
	  	$da  = $this->get("we_data_access");
	  	//Android最新版
	  	$sql = "select version,type,concat('$url',fileid) dow_url from we_version where type=1 order by date desc limit 1;";
	  	$ds  = $da->GetData("version",$sql);
	  	if ( $ds && $ds["version"]["recordcount"]>0){
	  		$temp = array("version"=>$ds["version"]["rows"][0]["version"],
	  		              "type"=> $ds["version"]["rows"][0]["type"],
	  		              "dow_url"=>str_replace("https","http",$ds["version"]["rows"][0]["dow_url"])
	  		              );
	  		array_push($list,$temp);
	  	}
	  	//IOS最新版
	  	$sql = "select version,type,ifnull(plist_url,'') as plist_url,concat('$url',fileid) down_url,filename from we_version where type=2 order by date desc limit 1;";
	  	$ds  = $da->GetData("version",$sql);
	  	if ( $ds && $ds["version"]["recordcount"]>0){
	  		$version   = $ds["version"]["rows"][0]["version"];
	  		$plist_url = $ds["version"]["rows"][0]["plist_url"];
	  		$this->get("logger")->err("plist_url".$plist_url);
        if(empty($plist_url))
        {        	 
           $down_url = $url = $this->container->getParameter('open_api_url');
	         $down_url.= "/home/download/ios.plist";
        }
        else
        {
           $down_url = $plist_url;
        }
        //$down_url = urlencode($down_url); 
        //暂时注释掉
	  	  $down_url ="itms-services://?action=download-manifest&url=$down_url";
	  		$temp = array("version"=>$version,"type"=>$ds["version"]["rows"][0]["type"],"dow_url"=>$down_url);
	  		array_push($list,$temp);
	  	}
	  	return $this->render("JustsyBaseBundle:Home:tcodedownload.html.twig",array("downlist"=>json_encode($list)));
	  }
	  
	  public function dowloadindexAction()
	  {
	  	$url = $this->container->getParameter('open_api_url');
	  	$url = str_replace("https","http",$url);
	  	return $this->render("JustsyBaseBundle:Home:download.html.twig",array("domain_url"=>$url));
	  }

    public function downiosplistAction()
    {
        $da = $this->get("we_data_access");
        $url = $this->container->getParameter('FILE_WEBSERVER_URL');
        $sql = "select version,concat('$url',fileid) url,fileid from we_version where type=2 order by date desc limit 1;";
        $ds = $da->GetData("table",$sql);         
        if ( $ds && $ds["table"]["recordcount"]>0)
        {
                $down_url  = $ds["table"]["rows"][0]["url"];
                $version  = $ds["table"]["rows"][0]["version"];
                $appname  = "Wefafa";
                $icon_url = $this->container->getParameter('open_api_url');
                $appicon  = $icon_url."/download/appicon.png";      
                $certificate = "";
                try
                {
                    $certificate = $this->container->getParameter('fafa_appcenter_certificate');
                }
                catch(\Exception $e){
                    $this->get("logger")->err("请在parameters.ini文件中配置IOS证书参数(fafa_appcenter_certificate)");
                }
                //动态创建文件内容
                $xmlContent = "<?xml version='1.0' encoding='UTF-8'?>
                    <!DOCTYPE plist PUBLIC '-//Apple//DTD PLIST 1.0//EN' 'http://www.apple.com/DTDs/PropertyList-1.0.dtd'>
                    <plist version='1.0'>
                        <dict>
                          <key>items</key>
                          <array>
                            <dict>
                              <key>assets</key>
                                <array>
                                   <dict>
                                      <key>kind</key>
                                      <string>software-package</string>
                                      <key>url</key>
                                      <string>$down_url</string>
                                   </dict>
                                   <dict>
                                      <key>kind</key>
                                      <string>display-image</string>
                                      <key>needs-shine</key>
                                      <true/>
                                      <key>url</key>
                                      <string>$appicon</string>
                                   </dict>
                                </array>
                              <key>metadata</key>
                              <dict>
                                  <key>bundle-identifier</key>
                                  <string>$certificate</string>
                                  <key>bundle-version</key>
                                  <string>$version</string>
                                  <key>kind</key>
                                  <string>software</string>
                                  <key>subtitle</key>
                                  <string>欢迎使用Wefafa企业移动应用平台</string>
                                  <key>title</key>
                                  <string>$appname</string>
                              </dict>
                            </dict>
                          </array>
                        </dict>
                    </plist>";
                $response = new Response($xmlContent);
                $response->headers->set("Content-Type", "text/xml");
                return $response;
        }
        else{
            return new Response("", 404);
        }        
    }
    	  
	  //ios安装文件下载
	  //返回下载文件流下载
	  public function downiosAction()
	  {		   
	  	 $da = $this->get("we_data_access");
	  	 $url = $this->container->getParameter('FILE_WEBSERVER_URL');
	  	 $request = $this->getRequest();
	  	 $type = $request->get("gettype");	  	 
	  	 $sql = "select version,concat('$url',fileid) url,fileid from we_version where type=2 order by date desc limit 1;";
	  	 $ds = $da->GetData("table",$sql);
	  	 if ( empty($type)){
	       $fileid = "";
	       if(count($ds["table"]["rows"])==0)
	       {
	        	return new Response("", 404);
	       }
	       $fileid = $ds["table"]["rows"]["0"]["fileid"];
	       if(empty($fileid))
	       {
	       	 return new Response("", 404);
	       }       
	       $doc = $this->get('doctrine.odm.mongodb.document_manager')
	              ->getRepository('JustsyMongoDocBundle:WeDocument')
	              ->find($fileid);
	       if (!$doc)
	       {
	         return new Response("", 404); 
	       }
	       $filename = $doc->getName();
	       if(isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER["HTTP_USER_AGENT"], "MSIE")) $filename = urlencode($filename);
	       $finfo = new \finfo(FILEINFO_MIME);
	       $response = new Response($doc->getFile()->getBytes());
	       $response->headers->set('Content-Type', "application/octet-stream");
	       $response->headers->set('Accept-Ranges','bytes');
	       $response->headers->set('Content-Length',$doc->getLength());
	       $response->headers->set('Content-Disposition','size='.$doc->getLength().'; filename="'.$filename.'"');
	       return $response;
	     }
	     else if ($type="plist"){
				if ( $ds && $ds["table"]["recordcount"]>0){
				  $down_url  = $ds["table"]["rows"][0]["url"];
				  $version  = $ds["table"]["rows"][0]["version"];
				  $appname  = "Wefafa";
				  $icon_url = $this->container->getParameter('open_api_url');
	        $appicon  = $icon_url."/download/appicon.png";
				  $certificate = "";
					try
					{
						$certificate = $this->container->getParameter('fafa_appcenter_certificate');
					}
					catch(\Exception $e){
						 $this->get("logger")->err("请在parameters.ini文件中配置IOS证书参数(fafa_appcenter_certificate)");
					}
					//动态创建文件内容
					$xmlContent = "<?xml version='1.0' encoding='UTF-8'?>
					<!DOCTYPE plist PUBLIC '-//Apple//DTD PLIST 1.0//EN' 'http://www.apple.com/DTDs/PropertyList-1.0.dtd'>
					<plist version='1.0'>
						<dict>
						  <key>items</key>
						  <array>
						    <dict>
						      <key>assets</key>
						        <array>
						           <dict>
						              <key>kind</key>
						              <string>software-package</string>
						              <key>url</key>
						              <string>$down_url</string>
						           </dict>
						           <dict>
						              <key>kind</key>
						              <string>display-image</string>
						              <key>needs-shine</key>
						              <true/>
						              <key>url</key>
						              <string>$appicon</string>
						           </dict>
						        </array>
					          <key>metadata</key>
					          <dict>
					              <key>bundle-identifier</key>
					              <string>$certificate</string>
					              <key>bundle-version</key>
					              <string>$version</string>
					              <key>kind</key>
					              <string>software</string>
					              <key>subtitle</key>
					              <string>欢迎使用Wefafa企业移动应用平台</string>
					              <key>title</key>
					              <string>$appname</string>
					          </dict>
						    </dict>
						  </array>
						</dict>
					</plist>";
					$response = new Response($xmlContent);
					$response->headers->set("Content-Type", "text/xml");
					return $response;
			  }
			  else{
			  	return new Response("", 404);
			  }
	     }
	  } 
	  
    public function staffAttrSyncAction()
    {
      $conn = $this->get("we_data_access");
      $conn_im = $this->get("we_data_access_im");
      $request = $this->getRequest();
      $appid=trim($request->get("appid"));
      $code=trim($request->get("code"));
      $openid=trim($request->get("openid"));
      $eno=trim($request->get("eno"));
      $staffattrlist=trim($request->get("attrs"));
      if(empty($appid)) return $this->responseJson($request,array("returncode"=>ReturnCode::$SYSERROR,"msg"=>"应用ID不能为空。"));
      if(empty($code)) return $this->responseJson($request,array("returncode"=>ReturnCode::$SYSERROR,"msg"=>"动态授权码不能为空。"));
      if(empty($staffattrlist)) return $this->responseJson($request,array("returncode"=>ReturnCode::$SYSERROR,"msg"=>"同步的人员属性不能为空。"));

      $sql_app="select appkey from we_appcenter_apps where appid=?";
      $para_app=array($appid);
      $data_app=$conn->GetData("dt",$sql_app,$para_app);
      if( $data_app==null || count($data_app["dt"]["rows"])==0 || empty($data_app["dt"]["rows"][0]["appkey"])) {
          return $this->responseJson($request,array("returncode"=>ReturnCode::$SYSERROR,"msg"=>"应用ID不正确。"));
      }
      $appkey=$data_app["dt"]["rows"][0]["appkey"];
      if(strtolower($code)!=strtolower(MD5($appid.$appkey))){
          return $this->responseJson($request,array("returncode"=>ReturnCode::$SYSERROR,"msg"=>"动态授权码不正确。"));
      }
      $attrObject = json_decode($staffattrlist,true);
      $nickname = isset($attrObject["NickName"])?$attrObject["NickName"]:null;
      $nickname = isset($attrObject["nickname"])?$attrObject["nickname"]:$nickname;

      $headportrait_url = isset($attrObject["HeadPortrait"])?$attrObject["HeadPortrait"]:null;
      $headportrait_url = isset($attrObject["headportrait"])?$attrObject["headportrait"]:$headportrait_url;

      $gender = isset($attrObject["Gender"])?$attrObject["Gender"]:null;
      $gender = isset($attrObject["gender"])?$attrObject["gender"]:$gender;

      $duty = isset($attrObject["Role"])?$attrObject["Role"]:null;
      $duty = isset($attrObject["role"])?$attrObject["role"]:$duty;

      $staff = new \Justsy\BaseBundle\Management\Staff($conn,$conn_im,$openid);
      
      if(!empty($headPortrait))
      {
          $staff->SaveUserHead($headportrait_url);
      }        
      $staff->checkAndUpdate($nick_name,null,null,$duty,null,$gender);
    }
    //不对外提供，内部调用
    public function updatepasswordAction()
    {
      //判断请求域。是wefafa或子域则不验证授权令牌
      $isWeFaFaDomain = $this->checkWWWDomain();      
      $res = $this->get("request");
      $da = $this->get("we_data_access");
      $opneid = $res->get("staff");
      $newpass = $res->get("newpass");   
      $factory = $this->get('security.encoder_factory');
      $staffMgr = new \Justsy\BaseBundle\Management\Staff($da,$this->get("we_data_access_im"),$opneid);
      $staffMgr->changepassword($opneid,$newpass,$factory);
      $re = array();
      $re["returncode"]="0000";
      $response = new Response($res->get('jsoncallback') ? $res->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
      $response->headers->set('Content-Type', 'text/json');
                       return $response;

    }
}