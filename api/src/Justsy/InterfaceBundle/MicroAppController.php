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

class MicroAppController extends Controller
{
	//初始化页面
	public function IndexAction($network_domain="",$name=0)
	{
		$conn = $this->get("we_data_access");
    	$conn_im = $this->get("we_data_access_im");
		$currUser = $this->get('security.context')->getToken();
		if(!empty($currUser)) $currUser = $currUser->getUser(); 
		$network_domain=empty($network_domain) ? $currUser->edomain : $network_domain;
		$file_url=$this->container->getParameter('FILE_WEBSERVER_URL');
	    $web_url=$this->container->getParameter('open_api_url');
		$request = $this->getRequest();
		$microUse=$name;
		//错误页面地址
	    $WefafaMicroPage=$web_url."/microaccount_help.htm";
		$WefafaHelpPage=$web_url."/help/index";
		if($microUse!=0 && $microUse!=1) {
			$array=array('errorMsg'=> '您无权使用企业微信推送平台,请联系您的管理员'
			,'microPage'=> $WefafaMicroPage
			,'curr_network_domain'=>$network_domain
			,'helpPage'=> $WefafaHelpPage
			,'returnurl'=>''); 
	    	return $this->render("JustsyOpenAPIBundle:MicroApp:error.html.twig",$array);
		}
	    
	    $login_account=$currUser->getUsername(); //登录帐号
	    $eno=$currUser->getEno(); //企业号
	    $photo_path=$currUser->photo_path; //用户地址
	    $user_name=$currUser->nick_name; //用户姓名
	    $eshortName=$currUser->ename; //企业名称
	    //用户模块和导航地址
	    $enterUrl=$web_url.'/'.$eno; //企业地址
	    $helpUrl=$web_url."/microaccount_help.htm"; //帮助地址
	    $user_photo_path=$file_url.$photo_path; //用户头像
	    $isManager=true; //判断是否是管理员
	    $sql="SELECT eno,1 AS type FROM we_enterprise WHERE sys_manager LIKE CONCAT('%',?,'%') AND eno=? UNION (SELECT eno,2 AS type FROM we_enterprise WHERE create_staff=? AND eno=?) UNION (SELECT eno,3 AS type FROM we_micro_account WHERE number=? AND eno=?);";
	    $para=array($login_account,$eno,$login_account,$eno,$login_account,$eno);
	    $data=$conn->GetData('dt',$sql,$para);
	    if($data==null && count($data["dt"]["rows"])==0 && !empty($data["dt"]["rows"][0]["eno"])) { //不是管理员的情况
	    	$array=array('errorMsg'=> '您无权使用企业微信推送平台,请联系您的管理员'
			,'microPage'=> $WefafaMicroPage
			,'curr_network_domain'=>$network_domain
			,'helpPage'=> $WefafaHelpPage
			,'returnurl'=>''); 
	    	return $this->render("JustsyOpenAPIBundle:MicroApp:error.html.twig",$array);
	    }

	    $isAuth=false; //判断是否是服务器地址
	    $auth= 1; //$request->get('auth');
	    if(!empty($auth)) $isAuth=true;
	    else {
			$array=array('errorMsg'=> '请您通过正常途径,访问企业微信推送平台'
			,'microPage'=> $WefafaMicroPage
			,'curr_network_domain'=>$network_domain
			,'helpPage'=> $WefafaHelpPage
			,'returnurl'=>''); 
	    	return $this->render("JustsyOpenAPIBundle:MicroApp:error.html.twig",$array);
	    }
	    $user_type=$data["dt"]["rows"][0]["type"];
	    $microlist='[]';
	    $microdata='[]';
	    $microgrouplist='[]';
	    switch ($user_type) {
	    	case '3': //使用的是公众号登录
	    		$sql="SELECT a.name,a.number,a.type,a.jid,b.openid,a.micro_use FROM we_micro_account a LEFT JOIN we_staff b ON a.number=b.login_account AND a.eno=b.eno WHERE a.eno=? AND a.number=? ";
	    		$para=array($eno,$login_account);
	    		$data=$conn->GetData('dt',$sql,$para);
	    		if($data==null || count($data["dt"]["rows"])==0 || empty($data["dt"]["rows"][0]['name'])) {
	    			$array=array('errorMsg'=> '您的公众号信息有误,请联系管理员'
					,'microPage'=> $WefafaMicroPage
					,'curr_network_domain'=>$network_domain
					,'helpPage'=> $WefafaHelpPage
					,'returnurl'=>''); 
			    	return $this->render("JustsyOpenAPIBundle:MicroApp:error.html.twig",$array);
	    		}
	    		$microdata=json_encode(array('microName'=>$data["dt"]["rows"][0]['name']
	    						,'microNumber'=>$data["dt"]["rows"][0]['number']
	    						,'microType'=>$data["dt"]["rows"][0]['type']
	    						,'microJid'=>$data["dt"]["rows"][0]['jid']
	    						,'microUse'=>$data['dt']['rows'][0]['micro_use']
	    						,'microOpenid'=>$data["dt"]["rows"][0]['openid']));
	    		$sql="SELECT id,groupname FROM we_micro_account_group WHERE micro_account=? ;";
				$para=array($login_account);
				$data=$conn->GetData('dt',$sql,$para);
				if($data!=null && count($data['dt']['rows']) > 0 ) {
					$microgrouplist=$data['dt']['rows'];
				}
	    		break;
	    	default:
	    		$sql='';
	    		if($microUse==0) $sql="SELECT CASE WHEN type=0 THEN CONCAT(name,'【内部公众号】') ELSE CONCAT(name,'【外部公众号】') END AS sayname,a.name,a.type,a.number,a.jid,b.openid,a.micro_use FROM we_micro_account a LEFT JOIN we_staff b ON a.number=b.login_account AND a.eno=b.eno WHERE a.eno=? AND a.name IS NOT NULL AND a.micro_use=? ORDER BY a.type,a.create_datetime;";
	    		else if($microUse==1)  $sql="SELECT CONCAT(name,'【微应用】') AS sayname,a.name,a.type,a.number,a.jid,b.openid,a.micro_use FROM we_micro_account a LEFT JOIN we_staff b ON a.number=b.login_account AND a.eno=b.eno WHERE a.eno=? AND a.name IS NOT NULL AND a.micro_use=? ORDER BY a.type,a.create_datetime;";
	    		if(!empty($sql)) {
	    			$para=array($eno,$microUse);
		    		$data=$conn->GetData('dt',$sql,$para);
		    		if($data==null || count($data["dt"]["rows"])==0 || empty($data["dt"]["rows"][0]['name'])) {
		    			$errormsg='您的企业还没有创建公众号,请先创建公众号';
		    			$returnurl="microaccount";
		    			if($microUse==1) $returnurl="microapp";
		    			if($microUse==1) $errormsg='您的企业还没有创建微应用,请先创建微应用';
		    			$array=array('errorMsg'=> $errormsg,'microPage'=> $WefafaMicroPage,'curr_network_domain'=>$network_domain,'helpPage'=> $WefafaHelpPage,'returnurl'=>$returnurl); 
				    	return $this->render("JustsyOpenAPIBundle:MicroApp:error.html.twig",$array);
		    		}
		    		$microlist=$data["dt"]["rows"];
	    		}
	    		break;
	    }
	    $sqls=array();
	    $paras=array();
	    $sql="select id from `we_micro_use_record` where login_account=?";
	    $para=array($currUser->getUsername());
	    $data=$conn->GetData("dt",$sql,$para);
	    if($data==null || count($data["dt"]["rows"])==0 || empty($data["dt"]['rows'][0]["id"])) {
	    	$cid=SysSeq::GetSeqNextValue($conn,'we_micro_use_detailed','id');
	    	$sqls[]="INSERT INTO `we_micro_use_record` (`id`, `login_account`, `show_guide`) VALUES (?, ?, true);";
	    	$paras[]=array($cid,$currUser->getUsername());
	    }
	    else $cid=$data["dt"]['rows'][0]["id"];

	    $did=SysSeq::GetSeqNextValue($conn,'we_micro_use_detailed','id');
	    $sqls[]="INSERT INTO `we_micro_use_detailed` (`id`, `use_id`, `use_ip`, `use_approach`, `use_datetime`) VALUES (?, ?, ?, '通过平台直接登录', now());";
	    $paras[]=array($did,$cid,$this->getIp());

	    try { if(!empty($sqls))  $conn->ExecSQLs($sqls,$paras);} catch (\Exception $e) {$this->get('logger')->err($e->getMessage());}

	    $code_path='http://mp.wefafa.com/SourceCode/E-MessagePlatform-1.0.rar';
	    $array=array('circleUrl'=>''
	    	,'enterUrl'=>$enterUrl
	    	,'eshortName'=>$eshortName
	    	,'helpUrl'=>$helpUrl
	    	,'user_name'=>$user_name
	    	,'user_photo_path'=>$user_photo_path
	    	,'user_type'=>$user_type
	    	,'code_path'=>$code_path
	    	,'isManager'=>$isManager
	    	,'isAuth'=>$isAuth
	    	,'footer'=>false
	    	,'microlist'=>$microlist
	    	,'microdata'=>$microdata
	    	,'curr_network_domain'=>$network_domain
	    	,'microgrouplist'=>$microgrouplist);
	    
		return $this->render("JustsyOpenAPIBundle:MicroApp:index.html.twig",$array);
	}
	public function getMicroGroupListAction(){
		$conn = $this->get("we_data_access");
		$request = $this->getRequest();
		$micro_account=$request->get('micro_account');

		if(empty($micro_account)) return $this->responseJson(json_encode(array()));

		$sql="SELECT id,groupname FROM we_micro_account_group WHERE micro_account=? ;";
		$para=array($micro_account);
		$data=$conn->GetData('dt',$sql,$para);
		if($data!=null && count($data['dt']['rows']) > 0 ){
			return $this->responseJson(json_encode($data['dt']['rows']));
		}
		return $this->responseJson(json_encode(array()));
	}
	//系统错误页面
	public function ErrorAction($network_domain) {
		$request = $this->getRequest();
	    $web_url=$this->container->getParameter('open_api_url');
		$WefafaMicroPage=$web_url."/microaccount_help.htm";
		$WefafaHelpPage=$web_url."/help/index";
		$errorMsg=$request->get('errorMsg');
		$array=array('errorMsg'=> $errorMsg 
		,'microPage'=> $WefafaMicroPage
		,'curr_network_domain'=>$network_domain
		,'helpPage'=> $WefafaHelpPage
		,'returnurl'=>''); 
		return $this->render("JustsyOpenAPIBundle:MicroApp:error.html.twig",$array);
	}
	//内部系统文件上传
	public function UploadFileAction(){
		$re= array('success'=>false,'msg'=>'图片上传失败');
		try {
			$request = $this->get("request");
        	$user = $this->get('security.context')->getToken()->getUser();
        	$upfile = $request->files->get("image_uploadFile");
        	$maxwidth = 2100 ;//$request->get('maxwidth'); //图片最宽像素
        	$maxheight = 1600 ;//$request->get('maxheight'); //图片最高像素
        	if(empty($upfile)){
        		$re= array('success'=>false,'msg'=>'请选择图片格式的文件');
        		return $this->responseJson(json_encode($re));
        	}
	        $isimage = preg_match(\Justsy\BaseBundle\Common\MIME::getMIMEImgReg(), strtolower($upfile->getClientOriginalName()));
	        
	        if ($isimage) {
	        	$im = new \Imagick($upfile->getPathname());
	        	$filesize=floor($im->getImageLength() / (1024 * 1024));
	        	if($filesize > 2){
	        		unlink($upfile->getPathname());
	        		$re= array('success'=>false,'msg'=>'图片太大了,最大支持2MB的图片');
        			return $this->responseJson(json_encode($re));
	        	}
	        	if(!empty($maxwidth) && $im->getImageWidth() > $maxwidth) {
	        		unlink($upfile->getPathname());
	        		$re= array('success'=>false,'msg'=>'图片像素超过指定像素范围');
					return $this->responseJson(json_encode($re));
	        	}
	        	if(!empty($maxheight) && $im->getImageHeight() > $maxheight) {
	        		unlink($upfile->getPathname());
	        		$re= array('success'=>false,'msg'=>'图片像素超过指定像素范围');
					return $this->responseJson(json_encode($re));
	        	}

	        	//开始上传图片
	        	$dm = $this->get('doctrine.odm.mongodb.document_manager'); 
		        $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
		        $filename = $upfile->getClientOriginalName();
		        $doc->setName($filename);
		        $doc->setFile($upfile->getPathname()); 
		        $dm->persist($doc);
		        $dm->flush();
		        $fileid = $doc->getId();

				$file_url=$this->container->getParameter('FILE_WEBSERVER_URL');
				$filepath=$file_url.$fileid;
				$re= array('success'=>true,'filepath'=>$filepath ,'msg'=>'图片上传成功');
	        } else {
	        	$re= array('success'=>false,'msg'=>'请选择图片格式的文件');
	        }
	        unlink($upfile->getPathname());
		} catch (\Exception $e) {
			$this->get('logger')->err($e->getMessage());
			$re= array('success'=>false,'msg'=>'图片上传超时');
		}
		return $this->responseJson(json_encode($re));
	}
	//编辑器文件上传
	public function EditorUploadImageAction(){
		$re= array('error'=>1,'message'=>'图片上传失败');
		try {
			$request = $this->get("request");
        	$user = $this->get('security.context')->getToken()->getUser();
        	$upfile = $request->files->get("keImg");
        	$maxwidth = 2100;//$request->get('maxwidth'); //图片最宽像素
        	$maxheight = 1600;//$request->get('maxheight'); //图片最高像素
        	if(empty($upfile)){
        		$re= array('error'=>1,'message'=>'请选择图片格式的文件');
        		return $this->responseJson(json_encode($re));
        	}
	        $isimage = preg_match(\Justsy\BaseBundle\Common\MIME::getMIMEImgReg(), strtolower($upfile->getClientOriginalName()));

	        if ($isimage) {
	        	$im = new \Imagick($upfile->getPathname());
	        	$filesize=floor($im->getImageLength() / (1024 * 1024));
	        	if($filesize > 5){
	        		unlink($upfile->getPathname());
	        		$re= array('error'=>1,'message'=>'图片太大了,最大支持5MB的图片');
        			return $this->responseJson(json_encode($re));
	        	}
	        	//开始上传图片
	        	$dm = $this->get('doctrine.odm.mongodb.document_manager'); 
		        $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
		        $filename = $upfile->getClientOriginalName();
		        $doc->setName($filename);
		        $doc->setFile($upfile->getPathname()); 
		        $dm->persist($doc);
		        $dm->flush();
		        $fileid = $doc->getId();

				$file_url=$this->container->getParameter('FILE_WEBSERVER_URL');
				$filepath=$file_url.$fileid;
				$re= array('error'=>0,'url'=>$filepath);
	        } else {
	        	$re= array('error'=>1,'message'=>'请选择图片格式的文件');
	        }
	        unlink($upfile->getPathname());
		} catch (\Exception $e) {
			$this->get('logger')->err($e->getMessage());
			$re= array('error'=>1,'message'=>'图片上传超时');
		}
		return $this->responseJson(json_encode($re));
	}

	//内部消息发送
	public function SendMsgAction(){
		$conn = $this->get("we_data_access");
        $conn_im = $this->get("we_data_access_im");
		$currUser = $this->get('security.context')->getToken();
		$request = $this->getRequest();
	    if(!empty($currUser)) $currUser = $currUser->getUser(); 
	    else {
	    	$web_url=$this->container->getParameter('open_api_url');
			$WefafaMicroPage=$web_url."/microaccount_help.htm";
			$WefafaHelpPage=$web_url."/help/index";
	    	$array=array('errorMsg'=> '您还没有登录系统,请您先登录系统'
						,'microPage'=> $WefafaMicroPage
						,'helpPage'=> $WefafaHelpPage
						,'returnurl'=>''); 
			return $this->render("JustsyOpenAPIBundle:MicroApp:error.html.twig",$array);
	    }
	    //公众号相关参数
	    $microName= $request->get('microName'); //接收对象(公众号名称)
	    $microNumber= $request->get('microNumber'); //接收对象(公众号帐号)
	    $microOpenid= $request->get('microOpenid'); //接收对象(公众号Openid)
	    $microType= $request->get('microType'); //接收对象(公众号类型,内部或外部)
	    $microUse= $request->get('microUse'); //接收对象(是公众号还是微应用)
	    $microGroupId= $request->get('microGroupId'); //接收对象(公众号分组主键)
	    $microObj= $request->get('microObj'); //接收对象(公众号参数集合)
	    $microObj_list= json_decode($microObj);
	    if(!empty($microObj_list)) {
	    	foreach ($microObj_list as $key => $val){
	    		if($key=="microName") $microName=$val;
	    		else if($key=="microNumber") $microNumber=$val;
	    		else if($key=="microOpenid") $microOpenid=$val;
	    		else if($key=="microType") $microType=$val;
	    		else if($key=="microUse") $microUse=$val;
	    		else if($key=="microGroupId") $microGroupId=$val;
	    	}
	    }
	    //消息参数
	    $msgType= $request->get('type'); //消息类型
	    $msgContent= $request->get('msgContent'); //消息内容(XML拼接Json字符串,包括标题,图片,摘要等)
	    $msgContentHtml= $request->get('contentHtml'); //消息内容(HTML内容)
	    $msgTitle= $request->get('title'); //消息标题
	    $imgUrl= $request->get('imgUrl'); //图片地址
	    $msgObj= $request->get('msgObj'); //消息对象
	    $msgObj_list= json_decode($msgObj);
	    if(!empty($msgObj_list)) {
	    	foreach ($msgObj_list as $key => $val){
	    		if($key=="type") $msgType=$val;
	    		else if($key=="msgContent") $msgContent=$val;
	    		else if($key=="contentHtml") $msgContentHtml=$val;
	    		else if($key=="title") $msgTitle=$val;
	    		else if($key=="imgUrl") $imgUrl=$val;
	    	}
	    }
	    if(empty($microName) || empty($microNumber) || empty($microOpenid) || $microType==null) {
	    	$re= array('returncode'=>'9999','msg'=>'请选择接收对象');
	    	return $this->responseJson(json_encode($re));
	    }
	    //外部公众号判断发送数量
	    if ("1"==$microType && $microUse=='0') {
	    	//默认当前外部公众号每周只能发送一条  内部无限发
	    	$sql="CALL p_check_msg_send_count (?);";
	    	$para=array($microNumber);
	    	$data=$conn->GetData('dt',$sql,$para);
	    	//判断外部公众号是否已经发布过
	    	if($data!=null && count($data["dt"]["rows"])>0 && $data['dt']['rows'][0]['count']==1) {
	    		$re= array('returncode'=>'9999','msg'=>'外部公众号,每周只能推送一条消息');
	    		return $this->responseJson(json_encode($re));
	    	}
	    }
	    $re= array('returncode'=>'9999','msg'=>'消息发送失败');
	    $sqls=array();
	    $paras=array();
	    $send_state='2';
	    $id=SysSeq::GetSeqNextValue($conn,'we_micro_send_message','id');
		$sqls[]= "INSERT INTO `we_micro_send_message` (`id`, `send_account`, `send_groupid`, `send_datetime`, `send_state`, `send_isbutton`, `send_source`,`send_type`) VALUES (?, ?, ?, now(), ?, ?, ?,?);";
		$paras[]= array($id,$microNumber,$microGroupId,$send_state,false,'wefafa',$msgType);
		$error= array('returncode'=>'9999','msg'=>'消息内容有误,请检查');
	    //处理消息
	    switch ($msgType) {
	    	case 'PICTURE':
	    		$title= ''; //标题
	    		$image_type= ''; //图片类型  URL或CODE
				$image_value= ''; //图片地址
				$content= ''; //摘要
				$link= ''; //手机端点击之后连接地址
				try {
					foreach ($msgContent as $key => $value) {
		    			if($key=='picturemsg') {
		    				$picturemsg = $value;
		    				//判断参数是否为空。并返回错误提示
		    				if(empty($picturemsg)) return $this->responseJson(json_encode($error));
		    				foreach ($picturemsg as $pkey => $pvalue) {
		    					if($pkey=='headitem') {
		    						$headitem= $pvalue;
		    						//判断参数是否为空。并返回错误提示
		    						if(empty($headitem)) return $this->responseJson(json_encode($error));
		    						foreach ($headitem as $hkey => $hvalue) {
		    							if($hkey=='title') $title=$hvalue;
		    							else if($hkey=='image') {
		    								$image=$hvalue;
		    								//判断参数是否为空。并返回错误提示
		    								if(empty($image)) return $this->responseJson(json_encode($error));
		    								foreach ($image as $ikey => $ivalue) {
		    									if($ikey=='type') $image_type=$ivalue;
		    									else if($ikey=='value') $image_value=$ivalue;
		    								}
		    							}else if($hkey=='content') $content=$hvalue;
		    						}
		    					}
		    				}
		    			}
		    		}
				} catch (Exception $e) {
					$this->get('logger')->err($e->getMessage());
	    			return $this->responseJson(json_encode($error));
				}
				
				$uniqid=str_replace('.','',uniqid('',true));
				$link=$this->getLink($uniqid);

				$msgContent= array('picturemsg'=>array('headitem'=>array('title'=>$title
								,'image'=>array('type'=>$image_type,'value'=>$image_value)
								,'content'=>$content
								,'link'=>$link)));
				$msgid=SysSeq::GetSeqNextValue($conn,'we_micro_message','id');
				$sqls[]= "INSERT INTO `we_micro_message` (`id`, `send_id`, `msg_title`, `msg_type`, `msg_text`, `msg_content`, `msg_summary`, `msg_img_type`, `msg_img_url`, `msg_web_url`, `ishead`, `isread`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
				$paras[]= array($msgid,$id,$title,$msgType,null,$msgContentHtml,$content,$image_type,$image_value,$uniqid,true,false);
	    		$conn->ExecSQLs($sqls,$paras);
	    		break;
	    	case 'TEXTPICTURE':
	    		try {
	    			$headitem=array();
	    			$items=array();
	    			foreach ($msgContent as $key => $value) {
	    				if($key=='textpicturemsg') {
	    					$textpicturemsg=$value;
	    					if(empty($textpicturemsg)) return $this->responseJson(json_encode($error));
	    					foreach ($textpicturemsg as $tpmkey => $tpmvalue) 
	    					{
	    						if($tpmkey=='headitem') 
	    						{
	    							$headitem=$tpmvalue;
	    							if(empty($headitem)) return $this->responseJson(json_encode($error));
	    							$head_title='';
	    							$head_img_type='';
	    							$head_img_url='';
	    							$head_contentHtml='';
	    							$head_link='';
	    							foreach ($headitem as $hkey => $hvalue) {
	    								if($hkey=='title') $head_title=$hvalue;
	    								else if($hkey=='image') {
	    									$image=$hvalue;
	    									if(empty($image)) return $this->responseJson(json_encode($error));
	    									foreach ($image as $imgkey => $imgvalue) {
	    										if($imgkey=='type') $head_img_type=$imgvalue;
	    										else if($imgkey=='value') $head_img_url=$imgvalue;
	    									}
	    								}else if($hkey=='content') $head_contentHtml=$hvalue;
	    							}
	    							$uniqid=str_replace('.','',uniqid('',true));
									$head_link=$this->getLink($uniqid);

									$headitem= array('title'=>$head_title
													,'image'=>array('type'=>$head_img_type,'value'=>$head_img_url)
													,'link'=>$head_link);
									$msgid=SysSeq::GetSeqNextValue($conn,'we_micro_message','id');
									$sqls[]= "INSERT INTO `we_micro_message` (`id`, `send_id`, `msg_title`, `msg_type`, `msg_text`, `msg_content`, `msg_summary`, `msg_img_type`, `msg_img_url`, `msg_web_url`, `ishead`, `isread`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
									$paras[]= array($msgid,$id,$head_title,$msgType,null,$head_contentHtml,null,$head_img_type,$head_img_url,$uniqid,true,false);
	    						}
	    						else if($tpmkey=='item') 
	    						{
	    							$item=$tpmvalue;
	    							if(empty($item)) return $this->responseJson(json_encode($error));
	    							$item_array=array();
	    							for ($i=0; $i < count($item); $i++) 
	    							{ 
	    								$item_title='';
		    							$item_img_type='';
		    							$item_img_url='';
		    							$item_contentHtml='';
		    							$item_link='';
	    								foreach ($item[$i] as $itemkey => $itemvalue) {
	    									if($itemkey=='title') $item_title=$itemvalue;
		    								else if($itemkey=='image') {
		    									$image=$itemvalue;
		    									if(empty($image)) return $this->responseJson(json_encode($error));
		    									foreach ($image as $imgkey => $imgvalue) {
		    										if($imgkey=='type') $item_img_type=$imgvalue;
		    										else if($imgkey=='value') $item_img_url=$imgvalue;
		    									}
		    								}else if($itemkey=='content') $item_contentHtml=$itemvalue;
	    								}
	    								$uniqid=str_replace('.','',uniqid('',true));
										$item_link=$this->getLink($uniqid);

										$item_array= array('title'=>$item_title
													,'image'=>array('type'=>$item_img_type,'value'=>$item_img_url)
													,'link'=>$item_link);
										array_push($items, $item_array);
										$msgid=SysSeq::GetSeqNextValue($conn,'we_micro_message','id');
										$sqls[]= "INSERT INTO `we_micro_message` (`id`, `send_id`, `msg_title`, `msg_type`, `msg_text`, `msg_content`, `msg_summary`, `msg_img_type`, `msg_img_url`, `msg_web_url`, `ishead`, `isread`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
										$paras[]= array($msgid,$id,$item_title,$msgType,null,$item_contentHtml,null,$item_img_type,$item_img_url,$uniqid,false,false);
	    							}	    							
	    						}
				    			//每200条写一次
					            if(count($sqls)==200)
					            {
					            	$conn->ExecSQLs($sqls,$paras);
					            	$sqls=array();
					    			$paras=array();
					            } 	    						
	    					}
	    					if(!empty($sqls)) $conn->ExecSQLs($sqls,$paras);	    					
	    				}
	    			}
	    			$msgContent= array('textpicturemsg'=>array('headitem'=>$headitem,'item'=>$items));
	    		} catch (\Exception $e) {
	    			$this->get('logger')->err($e->getMessage());
	    			return $this->responseJson(json_encode($error));
	    		}
	    		break;
	    	case 'TEXT':
	    		foreach ($msgContent as $key => $value) {
	    			if($key=='textmsg') {
	    				$textmsg= $value;
	    				//判断参数是否为空。并返回错误提示
	    				if(empty($textmsg)) return $this->responseJson(json_encode($error));
	    				foreach ($textmsg as $tkey => $tvalue) {
	    					if($tkey=='item') {
	    						$items=$tvalue;
	    						//判断参数是否为空。并返回错误提示
	    						if(empty($items)) return $this->responseJson(json_encode($error));
	    						$new_items=array();
	    						for ($i=0; $i < count($items); $i++) {
			    					$title='';
			    					$content='';
			    					foreach ($items[$i] as $itemkey => $itemvalue) {
			    						if($itemkey=='title') $title=$itemvalue;
			    						else if($itemkey=='content') $content=$itemvalue;
			    					}
			    					if(empty($title)) return $this->responseJson(json_encode($error));
			    					if(empty($content)) return $this->responseJson(json_encode($error));

			    					array_push($new_items, array('title'=>$title,'content'=>$content));
				    				$msgid=SysSeq::GetSeqNextValue($conn,'we_micro_message','id');
				    				$sqls[]= "INSERT INTO `we_micro_message` (`id`, `send_id`, `msg_title`, `msg_type`, `msg_text`, `msg_content`, `msg_summary`, `msg_img_type`, `msg_img_url`, `msg_web_url`, `ishead`, `isread`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
				    				$paras[]= array($msgid,$id,$title,$msgType,$content,null,null,null,null,null,false,false);
			    					//每200条写一次
				            		if(count($sqls)==200)
				            		{
				            			$conn->ExecSQLs($sqls,$paras);
				            			$sqls=array();
				    					$paras=array();
				            		}
			    				}
			    				if(!empty($sqls)) $conn->ExecSQLs($sqls,$paras);
			    				$msgContent= array('textmsg'=>array('item'=>$new_items));
	    					}
	    				}
	    			}
	    		}
	    		break;
	    	default: //消息类型有误
	    		return $this->responseJson(json_encode($error));
	    		break;
	    }
	    $msgContent=json_encode($msgContent);
	    try {
	    	$fafa_jids=array();
	    	$sqls_staff=array();
	    	$paras_staff=array();
		    $apicontroller = new \Justsy\OpenAPIBundle\Controller\ApiController();
		    $apicontroller->setContainer($this->container);
		    $MicroAccountMgr = new \Justsy\BaseBundle\Management\MicroAccountMgr($conn,$conn_im,$currUser,$this->get("logger"),$this->container);
		    if($microGroupId > 0) 
		    {
		    	$count = $MicroAccountMgr->check_micro_fans_groupid($microNumber,$microGroupId);
	            $microdata=array();
	            if($count>0) {//分组主键在数据库存在
	                $microdata = $MicroAccountMgr->get_micro_fans_group($microNumber,$microGroupId);
	                for ($i=0; $i < count($microdata); $i++) { 
		              	if(!in_array($microdata[$i]["fafa_jid"],$fafa_jids) && !empty($microdata[$i]["fafa_jid"]))  {
		                	array_push($fafa_jids, $microdata[$i]["fafa_jid"]);
		                	$staffid=SysSeq::GetSeqNextValue($conn,'we_micro_message_recipient','id');
		                	$sqls_staff[]= "INSERT INTO `we_micro_message_recipient` (`id`, `send_id`, `eno`, `login_account`, `openid`, `fafa_jid`, `rec_datetime`) VALUES (?, ?, ?, ?, ?, ?, now());";
		                	$paras_staff[]= array($staffid,$id,$microdata[$i]["eno"],$microdata[$i]["login_account"],$microdata[$i]["openid"],$microdata[$i]["fafa_jid"]);
		            		//每500条写一次
		            		if(count($sqls_staff)==500)
		            		{
		            			$conn->ExecSQLs($sqls_staff,$paras_staff);
		            			$sqls_staff=array();
		    					$paras_staff=array();
		            		}
		            	}
		            }
		            if(!empty($sqls_staff)) $conn->ExecSQLs($sqls_staff,$paras_staff);
	            }
		    }
		    else 
		    {	    	
		    	//发送所有粉丝太多（超过20000）会失败，页面会30秒超时，后期需要重新处理发送过程 
		    	$microdata = $MicroAccountMgr->get_micro_all_fans($microNumber);
		    	$Len = count($microdata);
		    	if($Len>0)
		    	{
		            for ($i=0; $i < $Len; $i++) 
		            { 
		            	if(!in_array($microdata[$i]["fafa_jid"],$fafa_jids) && !empty($microdata[$i]["fafa_jid"])) {
		                	array_push($fafa_jids, $microdata[$i]["fafa_jid"]);
		                	if($Len>10000) continue;
		                	$staffid=SysSeq::GetSeqNextValue($conn,'we_micro_message_recipient','id');
		                	$sqls_staff[]= "INSERT INTO `we_micro_message_recipient` (`id`, `send_id`, `eno`, `login_account`, `openid`, `fafa_jid`, `rec_datetime`) VALUES (?, ?, ?, ?, ?, ?, now());";
		                	$paras_staff[]= array($staffid,$id,$microdata[$i]["eno"],$microdata[$i]["login_account"],$microdata[$i]["openid"],$microdata[$i]["fafa_jid"]);
		            		//每500条写一次
		            		if(count($sqls_staff)==500)
		            		{
		            			$conn->ExecSQLs($sqls_staff,$paras_staff);
		            			$sqls_staff=array();
		    					$paras_staff=array();
		            		}
		            	}
		            }
		            if(!empty($sqls_staff)) $conn->ExecSQLs($sqls_staff,$paras_staff);
	            }
		    }
		    if(!empty($fafa_jids)) {
	            $jids=array();
	            for ($i = 0; $i < count($fafa_jids); $i++) {
	                array_push($jids,(string)$fafa_jids[$i]);
	                if(count($jids)==200) {
	                    $re= $apicontroller->sendMsg2($microOpenid,implode(",",$jids),$msgContent,$msgType);
	                    $jids=array();
	                }
	            }
	            if(!empty($jids)) $re= $apicontroller->sendMsg2($microOpenid,implode(",",$jids),$msgContent,$msgType);
	        }	        
	    } catch (\Exception $e) {
	    	$this->get('logger')->err($e->getMessage());
	    	$re= array('returncode'=>'9999','msg'=>'消息发送失败');
	    }
	    return $this->responseJson(json_encode($re));
	}
	//获取页面详细地址
	private function GeneratePage($title,$sendname,$contenthtml) {
		$html_path='';
		try {
			$template_file= $_SERVER['DOCUMENT_ROOT'].'/staticpage/template/template.htm';
			//判断文件是否可读
			if(is_readable($template_file)) {
				//读取文件内容
				$read=fopen($template_file,'r');
				$content_file = fread($read,filesize($template_file));
				$content_file=str_replace('$title', $title, $content_file);
				$content_file=str_replace('$datetime', date('Y-m-d H:i'), $content_file);
				$content_file=str_replace('$sendname', $sendname, $content_file);
				$content_file=str_replace('$contenthtml', $contenthtml, $content_file);
				fclose($read);
				$upfile = tempnam(sys_get_temp_dir(), "we");
				$handle = fopen($upfile, "w+");
				fwrite($handle, $content_file);
				fclose($handle);
				//先把web文件转换成files对象  在用mongodb进行上传文件
				$dm = $this->get('doctrine.odm.mongodb.document_manager'); 
                $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
                //上传新文件
                $doc->setName(date('Y-m-d H:i'));
                $doc->setFile($upfile); 
                $dm->persist($doc);
                $dm->flush();
                //获取上传之后的fileid
                $fileid = $doc->getId();
                unlink($upfile);
                //创建新文件
                $path= $_SERVER['DOCUMENT_ROOT'].'/staticpage/page/';
                $file= $fileid.'.htm';
                $this->create_folders($path);
                $this->readerFile($path,$file,'w',$content_file);
                $file_url=$this->container->getParameter('FILE_WEBSERVER_URL');
                //返回文件地址
				$html_path=$file_url.$fileid;
			} else $html_path='';
		} catch (\Exception $e) {
			$this->get('logger')->err($e);
			$html_path='';
		}
		return $html_path;
	}
	public function getPagePathAction($name) {
		$conn = $this->get("we_data_access");
        $conn_im = $this->get("we_data_access_im");
        $request = $this->getRequest();
        
        if(empty($name)) return $this->responseJson('获取消息详细内容失败');
		
		$re= '获取消息详细内容失败';
		try {
			$sql='SELECT a1.msg_title,a1.msg_content,a2.send_datetime,a3.name,UNIX_TIMESTAMP(a2.send_datetime) as sendtime FROM (SELECT send_id,msg_title,msg_content FROM we_micro_message WHERE msg_web_url=? ) as a1 LEFT JOIN we_micro_send_message a2 ON a2.id=a1.send_id LEFT JOIN we_micro_account a3 ON a2.send_account=a3.number';
			$para= array($name);
			$data=$conn->GetData('dt',$sql,$para);
			if($data!=null && count($data['dt']['rows'])>0) {
				$re= $data['dt']['rows'][0]['msg_content'];
			}
		} catch (\Exception $e) {
			$this->get('logger')->err($e->getMessage());
		}
		return $this->responseJson($re);
	}
	//文件上传 接口
	public function UploadImageAction() {
		$conn = $this->get("we_data_access");
        $conn_im = $this->get("we_data_access_im");

        $request = $this->getRequest();
        //是否是消息标题图片  可以为空  默认1 是
		//0 不是  图片大小限制2MB
		//1 是    图片像素限制1600*1200px
		$appid=$request->get('appid'); 

		$upfile = $request->files->get("filedata");
		$openid=$request->get('openid');
        $access_token=$request->get('access_token');
		$ishead=$request->get('ishead');

		if(empty($ishead)) $ishead="1";
		if(empty($access_token)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'访问令牌不能为空。')));
		if(empty($upfile)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'文件流不能为空。')));
		if(empty($openid)) $openid="wefafaproxy";
		if(empty($appid)) {
			if($_SERVER['REQUEST_METHOD']!="POST") return $this->responseJson(json_encode(array("error"=>"10009","msg"=>"HTTP请求仅支持POST提交方式")));
			
			$sql_app_oauth="select appid from we_app_oauth_sessions where user_type='sys' and userid=? and access_token=? and access_token_expires>=?";
	        $data_app_oauth=$conn->GetData("dt",$sql_app_oauth,array((string)$openid,(string)$access_token,time()));
	        //token通过认证
	        if($data_app_oauth!=null && count($data_app_oauth["dt"]["rows"])>0 && !empty($data_app_oauth["dt"]["rows"][0]["appid"])) {
	            $appid=$data_app_oauth["dt"]["rows"][0]["appid"];
	            $sql_micro_account="select number from we_micro_account where micro_source=? ";
	            $data_micro_account=$conn->GetData("dt",$sql_micro_account,array((string)$appid));
	            if($data_micro_account!=null && count($data_micro_account["dt"]["rows"])>0 && !empty($data_micro_account["dt"]["rows"][0]["number"])){

	            }else return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'访问令牌没有对应的微应用。')));
	        }else {
	        	return $this->responseJson(json_encode(array('returncode'=>'0001','msg'=>'访问令牌已过期。')));
	        }
		}else {
			$token = $this->checkAccessToken($conn,$appid,$openid,$access_token);	
	    	if($token===false) return $this->responseJson(json_encode(array('returncode'=>'0001','msg'=>'访问令牌已过期。')));
		}
		$sql_app="select appkey from we_appcenter_apps where appid=?";
	    $para_app=array($appid);
		if($openid=='wefafaproxy') {
	        $data_app=$conn->GetData("dt",$sql_app,$para_app);
	        if( $data_app==null || count($data_app["dt"]["rows"])==0 || empty($data_app["dt"]["rows"][0]["appkey"])) return $this->responseJson(array("returncode"=>ReturnCode::$SYSERROR,"msg"=>"访问令牌没有对应的微应用。"));
		}else {
	        $data_app=$conn->GetData("dt",$sql_app,$para_app);
	        if( $data_app==null || count($data_app["dt"]["rows"])==0 || empty($data_app["dt"]["rows"][0]["appkey"])) return $this->responseJson(array("returncode"=>ReturnCode::$SYSERROR,"msg"=>"应用ID不正确。"));
        	
        	$staff=$this->checkOpenid($conn,$openid);
	    	if(empty($staff)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'登录人不存在。')));
        }
		$re= array('returncode'=>'9999','msg'=>'图片上传失败。');
		try {
			$isimage = preg_match(\Justsy\BaseBundle\Common\MIME::getMIMEImgReg(), strtolower($upfile->getClientOriginalName()));
	        if ($isimage) {
	        	$im = new \Imagick($upfile->getPathname());
        		if ($im->getImageWidth() > 1600 && $im->getImageHeight() > 1200) $re= array('returncode'=>'9999','msg'=>'图片像素太高啦(1600px*1200px)。');
        		else {
        			$dm = $this->get('doctrine.odm.mongodb.document_manager'); 
			        $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
			        $filename = $upfile->getClientOriginalName();
			        $doc->setName($filename);
			        $doc->setFile($upfile->getPathname());
			        $dm->persist($doc);
			        $dm->flush();
			        $fileid = $doc->getId();

			        if($ishead=="1") $re= array('returncode'=>'0000','fileid'=>$fileid);
			        else {
			        	$file_url=$this->container->getParameter('FILE_WEBSERVER_URL');
			        	$re= array('returncode'=>'0000','fileid'=>$file_url.$fileid);
			        }
        		}
      		}else $re= array('returncode'=>'9999','msg'=>'只能上传图片类型的文件。');
      		unlink($upfile->getPathname());
		} catch (\Exception $e) {
			$this->get('logger')->err($e->getMessage());
			$re= array('returncode'=>'9999','msg'=>'图片上传出现异常。');
		}
        return $this->responseJson(json_encode($re));
	}
	//文本消息发送 接口
	public function TextMsgAction() {
		$conn = $this->get("we_data_access");
        $conn_im = $this->get("we_data_access_im");
        $request = $this->getRequest();
        $micro_account=$request->get('micro_account');
        $micro_groupid=$request->get('micro_groupid');
        $appid=$request->get('appid');
        $openid= 'wefafaproxy';
        $recopenid=$request->get('openid');
        $access_token=$request->get('access_token');
        $msg=$request->get('msg');
        if(empty($appid) && empty($micro_account)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'appid不能为空。')));
        if(empty($access_token)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'访问令牌不能为空。')));
        if(empty($msg)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'消息不能为空。')));
        $msgObj=json_decode($msg);
        if(empty($msgObj)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'消息格式不正确。')));
        if(empty($openid)) $openid="wefafaproxy";

        $token_appid = $this->checkAccessToken($conn,$appid,$openid,$access_token);	
	    if($token_appid===false) return $this->responseJson(json_encode(array('returncode'=>'0001','msg'=>'访问令牌已过期。')));
        if($token_appid != $appid) return $this->responseJson(json_encode(array('returncode'=>'0001','msg'=>'appid无效。')));
		//token通过认证
	    if(empty($micro_account))
	    {
		    $sql_micro_account="select number from we_micro_account where micro_source=? ";
		    $data_micro_account=$conn->GetData("dt",$sql_micro_account,array((string)$appid));
		    if($data_micro_account!=null && count($data_micro_account["dt"]["rows"])>0 && !empty($data_micro_account["dt"]["rows"][0]["number"])){
		        $micro_account=$data_micro_account["dt"]["rows"][0]["number"];
		    }
		    else 
		    	return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'应用未开启业务代理，不能推送消息。')));	     
        }
        $login_account= $micro_account;
        if($openid!="wefafaproxy"){
        	$staff = $this->checkOpenid($conn,$openid);
		    if(empty($staf)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'openid不存在。')));
        }
		$sql="SELECT b.openid,a.micro_source FROM we_micro_account a LEFT JOIN we_staff b ON b.login_account=a.number AND b.eno=a.eno WHERE a.number=?";
		$para=array($micro_account);
		$data=$conn->GetData('dt',$sql,$para);
		if($data==null || count($data['dt']['rows'])==0 || empty($data['dt']['rows'][0]['openid'])) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'微应用帐号不存在。')));
		$microOpenid=$data['dt']['rows'][0]['openid'];	
        //$this->get('logger')->err("=================");
        $re= array('returncode'=>'9999','msg'=>'消息发送失败。');
        try {
        	$buttons='';
        	foreach ($msgObj as $objkey => $objval) {
        		if($objkey=='title') $title=$objval;
        		else if($objkey=='content') $content=htmlspecialchars_decode($objval);
        		else if($objkey=='buttons') $buttons=$objval;
        	}
        	//$this->get('logger')->err("content=================".$content);
        	if(empty($title)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'消息标题不能为空。')));
        	if(empty($content)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'消息内容不能为空。')));

        	$items=array();
        	if(empty($buttons)) array_push($items, array('title'=>$title,'content'=>$content));
        	else array_push($items, array('title'=>$title,'content'=>$content,'buttons'=>$buttons));
        	$msgContent= array('textmsg'=>array('item'=>$items));
        	$msgContent=json_encode($msgContent);

		    $msgType='TEXT';
		    $send_state='2';
 			$sqls=array();
	        $paras=array();
	        $id=SysSeq::GetSeqNextValue($conn,'we_micro_send_message','id');
			$sqls[]= "INSERT INTO `we_micro_send_message` (`id`, `send_account`, `send_groupid`, `send_datetime`, `send_state`, `send_isbutton`, `send_source`,`send_type`) VALUES (?, ?, ?, now(), ?, ?, ?,?);";
			$paras[]= array($id,$micro_account,$micro_groupid,$send_state,false,'interface',$msgType);
			$msgid=SysSeq::GetSeqNextValue($conn,'we_micro_message','id');
			$sqls[]= "INSERT INTO `we_micro_message` (`id`, `send_id`, `msg_title`, `msg_type`, `msg_text`, `msg_content`, `msg_summary`, `msg_img_type`, `msg_img_url`, `msg_web_url`, `ishead`, `isread`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
			$paras[]= array($msgid,$id,$title,$msgType,$content,null,null,null,null,null,false,false); 
		    $fafa_jids=array();
	    	$sqls_staff=array();
	    	$paras_staff=array();
		    $apicontroller = new \Justsy\OpenAPIBundle\Controller\ApiController();
		    $apicontroller->setContainer($this->container);
		    $MicroAccountMgr = new \Justsy\BaseBundle\Management\MicroAccountMgr($conn,$conn_im,$login_account,$this->get("logger"),$this->container);
		    if($this->checkint($micro_groupid)) {
		    	$count = $MicroAccountMgr->check_micro_fans_groupid($micro_account,$micro_groupid);
	            $microdata=array();
	            if($count>0) {//分组主键在数据库不存在
	                $microdata = $MicroAccountMgr->get_micro_fans_group($micro_account,$micro_groupid);
	                for ($i=0; $i < count($microdata); $i++) { 
		              	if(!in_array($microdata[$i]["fafa_jid"],$fafa_jids) && !empty($microdata[$i]["fafa_jid"])) {
		                	if(!in_array($microdata[$i]["fafa_jid"],$fafa_jids)){
			                	array_push($fafa_jids, $microdata[$i]["fafa_jid"]);
			                	$staffid=SysSeq::GetSeqNextValue($conn,'we_micro_message_recipient','id');
			                	$sqls_staff[]= "INSERT INTO `we_micro_message_recipient` (`id`, `send_id`, `eno`, `login_account`, `openid`, `fafa_jid`, `rec_datetime`) VALUES (?, ?, ?, ?, ?, ?, now());";
			                	$paras_staff[]= array($staffid,$id,$microdata[$i]["eno"],$microdata[$i]["login_account"],$microdata[$i]["openid"],$microdata[$i]["fafa_jid"]);
		            		}
		            	}
		            }
	            }
	            if(!empty($recopenid)){
			    	$openids=explode(',',$recopenid);
			    	for ($i=0; $i < count($openids); $i++) { 
			    		$sql_staff="select fafa_jid,login_account,openid,eno from we_staff where openid=?";
			    		$data_staff=$conn->GetData("dt",$sql_staff,array((string)$openids[$i]));
			    		if ($data_staff!=null && count($data_staff["dt"]["rows"])>0) {
			    			if(!in_array($data_staff["dt"]["rows"][0]["fafa_jid"],$fafa_jids)){
				    			array_push($fafa_jids, $data_staff["dt"]["rows"][0]["fafa_jid"]);
				    			$staffid=SysSeq::GetSeqNextValue($conn,'we_micro_message_recipient','id');
			                	$sqls_staff[]= "INSERT INTO `we_micro_message_recipient` (`id`, `send_id`, `eno`, `login_account`, `openid`, `fafa_jid`, `rec_datetime`) VALUES (?, ?, ?, ?, ?, ?, now());";
			                	$paras_staff[]= array($staffid,$id,$data_staff["dt"]["rows"][0]["eno"],$data_staff["dt"]["rows"][0]["login_account"],$data_staff["dt"]["rows"][0]["openid"],$data_staff["dt"]["rows"][0]["fafa_jid"]);
		            		}
		            	}
			    	}
			    }
		    } else {
		    	if(!empty($recopenid)){
			    	$openids=explode(',',$recopenid);
			    	for ($i=0; $i < count($openids); $i++) { 
			    		$sql_staff="select fafa_jid,login_account,openid,eno from we_staff where (openid=? or ldap_uid=? or login_account=?)";
			    		$data_staff=$conn->GetData("dt",$sql_staff,array((string)$openids[$i],(string)$openids[$i],(string)$openids[$i]));
			    		if ($data_staff!=null && count($data_staff["dt"]["rows"])>0) {
			    			if(!in_array($data_staff["dt"]["rows"][0]["fafa_jid"],$fafa_jids)){
				    			array_push($fafa_jids, $data_staff["dt"]["rows"][0]["fafa_jid"]);
				    			$staffid=SysSeq::GetSeqNextValue($conn,'we_micro_message_recipient','id');
			                	$sqls_staff[]= "INSERT INTO `we_micro_message_recipient` (`id`, `send_id`, `eno`, `login_account`, `openid`, `fafa_jid`, `rec_datetime`) VALUES (?, ?, ?, ?, ?, ?, now());";
			                	$paras_staff[]= array($staffid,$id,$data_staff["dt"]["rows"][0]["eno"],$data_staff["dt"]["rows"][0]["login_account"],$data_staff["dt"]["rows"][0]["openid"],$data_staff["dt"]["rows"][0]["fafa_jid"]);
		            		}
		            	}
			    	}
			    }else{
			    	$microdata = $MicroAccountMgr->get_micro_all_fans($micro_account);
		            for ($i=0; $i < count($microdata); $i++) { 
		            	if(!in_array($microdata[$i]["fafa_jid"],$fafa_jids) && !empty($microdata[$i]["fafa_jid"])) {
		                	array_push($fafa_jids, $microdata[$i]["fafa_jid"]);
		                	$staffid=SysSeq::GetSeqNextValue($conn,'we_micro_message_recipient','id');
		                	$sqls_staff[]= "INSERT INTO `we_micro_message_recipient` (`id`, `send_id`, `eno`, `login_account`, `openid`, `fafa_jid`, `rec_datetime`) VALUES (?, ?, ?, ?, ?, ?, now());";
		                	$paras_staff[]= array($staffid,$id,$microdata[$i]["eno"],$microdata[$i]["login_account"],$microdata[$i]["openid"],$microdata[$i]["fafa_jid"]);
		            	}
		            }
	        	}
		    }
		    if(!empty($fafa_jids)) {
	            $jids=array();
	            for ($i = 0; $i < count($fafa_jids); $i++) {
	                array_push($jids,(string)$fafa_jids[$i]);
	                if(count($jids)==200) {
	                    $re= $apicontroller->sendMsg2($microOpenid,implode(",",$jids),$msgContent,$msgType);
	                    $jids=array();
	                }
	            }
	            if(!empty($jids)) $re= $apicontroller->sendMsg2($microOpenid,implode(",",$jids),$msgContent,$msgType);
	        }
	        
	        if(!empty($re['returncode']) && $re['returncode']=='0000') {
		        //添加发送消息数据
		        if(!empty($sqls)) $conn->ExecSQLs($sqls,$paras);
		        //添加接收人员
		        if(!empty($sqls_staff)) $conn->ExecSQLs($sqls_staff,$paras_staff);
	        }
        } catch (\Exception $e) {
        	$this->get('logger')->err($e->getMessage());
        }
        return $this->responseJson(json_encode($re));
	}
	//图片流消息发送 接口
	public function ImageMsgAction(){
		$conn = $this->get("we_data_access");
        $conn_im = $this->get("we_data_access_im");
        $request = $this->getRequest();
        $micro_account=$request->get('micro_account');
        $micro_groupid=$request->get('micro_groupid');
        $openid= 'wefafaproxy';
        $recopenid=$request->get('openid');
        $appid=$request->get('appid');
        $access_token=$request->get('access_token');
        $msg=$request->get('msg');
        $filedata=$request->files->get('filedata');
        if(empty($appid) && empty($micro_account)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'appid不能为空。')));
        if(empty($access_token)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'访问令牌不能为空。')));
        if(empty($msg)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'消息不能为空。')));
        $msgObj=json_decode($msg);
        if(empty($msgObj)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'消息格式不正确。')));
        if(empty($openid)) $openid="wefafaproxy";

        $token_appid = $this->checkAccessToken($conn,$appid,$openid,$access_token);	
	    if($token_appid===false) return $this->responseJson(json_encode(array('returncode'=>'0001','msg'=>'访问令牌已过期。')));
        if($token_appid != $appid) return $this->responseJson(json_encode(array('returncode'=>'0001','msg'=>'appid无效。')));
		//token通过认证
	    if(empty($micro_account))
	    {
		    $sql_micro_account="select number from we_micro_account where micro_source=? ";
		    $data_micro_account=$conn->GetData("dt",$sql_micro_account,array((string)$appid));
		    if($data_micro_account!=null && count($data_micro_account["dt"]["rows"])>0 && !empty($data_micro_account["dt"]["rows"][0]["number"])){
		        $micro_account=$data_micro_account["dt"]["rows"][0]["number"];
		    }
		    else 
		    	return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'应用未开启业务代理，不能推送消息。')));	     
        }
        $login_account= $micro_account;
        if($openid!="wefafaproxy"){
        	$staff = $this->checkOpenid($conn,$openid);
		    if(empty($staff)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'openid不存在。')));
        }
		$sql="SELECT b.openid FROM we_micro_account a LEFT JOIN we_staff b ON b.login_account=a.number AND b.eno=a.eno WHERE a.number=?;";
		$para=array($micro_account);
		$data=$conn->GetData('dt',$sql,$para);
		if($data==null || count($data['dt']['rows'])==0 || empty($data['dt']['rows'][0]['openid'])) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'微应用帐号不存在。')));
		$microOpenid=$data['dt']['rows'][0]['openid'];	
        $re= array('returncode'=>'9999','msg'=>'消息发送失败。');
        try {
        	$msgType='PICTURE';
        	$send_state='2';
        	$buttons='';
        	foreach ($msgObj as $objkey => $objval) {
        		if($objkey=='title') $title=$objval;
        		if($objkey=='summary') $summary=$objval;
        		else if($objkey=='content') $content=htmlspecialchars_decode($objval);
        		else if($objkey=='buttons') $buttons=$objval;
        	}
        	if(empty($title)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'消息标题不能为空。')));
        	if(empty($content)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'消息内容不能为空。')));

        	//需要把文件流生成图片保存到服务器
        	$fileid=$this->getFileId($filedata);

        	if(empty($fileid)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'消息图片上传失败。')));

			$uniqid=str_replace('.','',uniqid('',true));
			$link=$this->getLink($uniqid);

			$file_url=$this->container->getParameter('FILE_WEBSERVER_URL');
			if(empty($buttons)) $msgContent= array('picturemsg'=>array('headitem'=>array('title'=>$title
							,'image'=>array('type'=>'URL','value'=>$file_url.$fileid)
							,'content'=>$summary
							,'link'=>$link)));
			else $msgContent= array('picturemsg'=>array('headitem'=>array('title'=>$title
							,'image'=>array('type'=>'URL','value'=>$file_url.$fileid)
							,'content'=>$summary
							,'link'=>$link
							,'buttons'=>$buttons)));
			$msgContent=json_encode($msgContent);

 			$sqls=array();
	        $paras=array();
	        $id=SysSeq::GetSeqNextValue($conn,'we_micro_send_message','id');
			$sqls[]= "INSERT INTO `we_micro_send_message` (`id`, `send_account`, `send_groupid`, `send_datetime`, `send_state`, `send_isbutton`, `send_source`,`send_type`) VALUES (?, ?, ?, now(), ?, ?, ?,?);";
			$paras[]= array($id,$micro_account,$micro_groupid,$send_state,false,'interface',$msgType);
			$msgid=SysSeq::GetSeqNextValue($conn,'we_micro_message','id');
			$sqls[]= "INSERT INTO `we_micro_message` (`id`, `send_id`, `msg_title`, `msg_type`, `msg_text`, `msg_content`, `msg_summary`, `msg_img_type`, `msg_img_url`, `msg_web_url`, `ishead`, `isread`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
			$paras[]= array($msgid,$id,$title,$msgType,null,$content,$summary,'URL',$file_url.$fileid,$uniqid,true,false);
		    $fafa_jids=array();
	    	$sqls_staff=array();
	    	$paras_staff=array();
		    $apicontroller = new \Justsy\OpenAPIBundle\Controller\ApiController();
		    $apicontroller->setContainer($this->container);
		    $MicroAccountMgr = new \Justsy\BaseBundle\Management\MicroAccountMgr($conn,$conn_im,$login_account,$this->get("logger"),$this->container);

		    if($this->checkint($micro_groupid)) {
		    	$count = $MicroAccountMgr->check_micro_fans_groupid($micro_account,$micro_groupid);
	            $microdata=array();
	            if($count>0) {//分组主键在数据库不存在
	                $microdata = $MicroAccountMgr->get_micro_fans_group($micro_account,$micro_groupid);
	                for ($i=0; $i < count($microdata); $i++) { 
		              	if(!in_array($microdata[$i]["fafa_jid"],$fafa_jids) && !empty($microdata[$i]["fafa_jid"])) {
		                	if(!in_array($microdata[$i]["fafa_jid"],$fafa_jids)){
			                	array_push($fafa_jids, $microdata[$i]["fafa_jid"]);
			                	$staffid=SysSeq::GetSeqNextValue($conn,'we_micro_message_recipient','id');
			                	$sqls_staff[]= "INSERT INTO `we_micro_message_recipient` (`id`, `send_id`, `eno`, `login_account`, `openid`, `fafa_jid`, `rec_datetime`) VALUES (?, ?, ?, ?, ?, ?, now());";
			                	$paras_staff[]= array($staffid,$id,$microdata[$i]["eno"],$microdata[$i]["login_account"],$microdata[$i]["openid"],$microdata[$i]["fafa_jid"]);
		            		}
		            	}
		            }
	            }
	            if(!empty($recopenid)){
			    	$openids=explode(',',$recopenid);
			    	for ($i=0; $i < count($openids); $i++) { 
			    		$sql_staff="select fafa_jid,login_account,openid,eno from we_staff where openid=?";
			    		$data_staff=$conn->GetData("dt",$sql_staff,array((string)$openids[$i]));
			    		if ($data_staff!=null && count($data_staff["dt"]["rows"])>0) {
			    			if(!in_array($data_staff["dt"]["rows"][0]["fafa_jid"],$fafa_jids)){
				    			array_push($fafa_jids, $data_staff["dt"]["rows"][0]["fafa_jid"]);
				    			$staffid=SysSeq::GetSeqNextValue($conn,'we_micro_message_recipient','id');
			                	$sqls_staff[]= "INSERT INTO `we_micro_message_recipient` (`id`, `send_id`, `eno`, `login_account`, `openid`, `fafa_jid`, `rec_datetime`) VALUES (?, ?, ?, ?, ?, ?, now());";
			                	$paras_staff[]= array($staffid,$id,$data_staff["dt"]["rows"][0]["eno"],$data_staff["dt"]["rows"][0]["login_account"],$data_staff["dt"]["rows"][0]["openid"],$data_staff["dt"]["rows"][0]["fafa_jid"]);
		            		}
		            	}
			    	}
			    }
		    } else {
		    	if(!empty($recopenid)){
			    	$openids=explode(',',$recopenid);
			    	for ($i=0; $i < count($openids); $i++) { 
			    		$sql_staff="select fafa_jid,login_account,openid,eno from we_staff where openid=?";
			    		$data_staff=$conn->GetData("dt",$sql_staff,array((string)$openids[$i]));
			    		if ($data_staff!=null && count($data_staff["dt"]["rows"])>0) {
			    			if(!in_array($data_staff["dt"]["rows"][0]["fafa_jid"],$fafa_jids)){
				    			array_push($fafa_jids, $data_staff["dt"]["rows"][0]["fafa_jid"]);
				    			$staffid=SysSeq::GetSeqNextValue($conn,'we_micro_message_recipient','id');
			                	$sqls_staff[]= "INSERT INTO `we_micro_message_recipient` (`id`, `send_id`, `eno`, `login_account`, `openid`, `fafa_jid`, `rec_datetime`) VALUES (?, ?, ?, ?, ?, ?, now());";
			                	$paras_staff[]= array($staffid,$id,$data_staff["dt"]["rows"][0]["eno"],$data_staff["dt"]["rows"][0]["login_account"],$data_staff["dt"]["rows"][0]["openid"],$data_staff["dt"]["rows"][0]["fafa_jid"]);
		            		}
		            	}
			    	}
			    }else{
			    	$microdata = $MicroAccountMgr->get_micro_all_fans($micro_account);
		            for ($i=0; $i < count($microdata); $i++) { 
		            	if(!in_array($microdata[$i]["fafa_jid"],$fafa_jids) && !empty($microdata[$i]["fafa_jid"])) {
		                	array_push($fafa_jids, $microdata[$i]["fafa_jid"]);
		                	$staffid=SysSeq::GetSeqNextValue($conn,'we_micro_message_recipient','id');
		                	$sqls_staff[]= "INSERT INTO `we_micro_message_recipient` (`id`, `send_id`, `eno`, `login_account`, `openid`, `fafa_jid`, `rec_datetime`) VALUES (?, ?, ?, ?, ?, ?, now());";
		                	$paras_staff[]= array($staffid,$id,$microdata[$i]["eno"],$microdata[$i]["login_account"],$microdata[$i]["openid"],$microdata[$i]["fafa_jid"]);
		            	}
		            }
	        	}
		    }
		    if(!empty($fafa_jids)) {
	            $jids=array();
	            for ($i = 0; $i < count($fafa_jids); $i++) {
	                array_push($jids,(string)$fafa_jids[$i]);
	                if(count($jids)==200) {
	                    $re= $apicontroller->sendMsg2($microOpenid,implode(",",$jids),$msgContent,$msgType);
	                    $jids=array();
	                }
	            }
	            if(!empty($jids)) $re= $apicontroller->sendMsg2($microOpenid,implode(",",$jids),$msgContent,$msgType);
	        }
	        if(!empty($re['returncode']) && $re['returncode']=='0000') {
		        //添加发送消息数据
		        if(!empty($sqls)) $conn->ExecSQLs($sqls,$paras);
		        //添加接收人员
		        if(!empty($sqls_staff)) $conn->ExecSQLs($sqls_staff,$paras_staff);
	        }
        } catch (\Exception $e) {
        	$this->get('logger')->err($e->getMessage());
        }
        return $this->responseJson(json_encode($re));
	}
	private function getLink($uniqid) {
		$web_url=$this->container->getParameter('open_api_url');
		return $web_url.'/api/http/getpagepath/'.$uniqid;
	}
	//图文消息发送 接口
	public function PictureMsgAction(){
		$conn = $this->get("we_data_access");
        $conn_im = $this->get("we_data_access_im");
        $request = $this->getRequest();
        $micro_account=$request->get('micro_account');
        $micro_groupid=$request->get('micro_groupid');
        $appid=$request->get('appid');
        $openid= 'wefafaproxy';
        $recopenid=$request->get('openid');
        $access_token=$request->get('access_token');
        $msg=$request->get('msg');
        
        if(empty($appid) && empty($micro_account)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'appid不能为空。')));
        if(empty($access_token)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'访问令牌不能为空。')));
        if(empty($msg)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'消息不能为空。')));
        $msgObj=json_decode($msg);
        if(empty($msgObj)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'消息格式不正确。')));
        if(empty($openid)) $openid="wefafaproxy";

        $token_appid = $this->checkAccessToken($conn,$appid,$openid,$access_token);	
	    if($token_appid===false) return $this->responseJson(json_encode(array('returncode'=>'0001','msg'=>'访问令牌已过期。')));
        if($token_appid != $appid) return $this->responseJson(json_encode(array('returncode'=>'0001','msg'=>'appid无效。')));
		//token通过认证
	    if(empty($micro_account))
	    {
		    $sql_micro_account="select number from we_micro_account where micro_source=? ";
		    $data_micro_account=$conn->GetData("dt",$sql_micro_account,array((string)$appid));
		    if($data_micro_account!=null && count($data_micro_account["dt"]["rows"])>0 && !empty($data_micro_account["dt"]["rows"][0]["number"])){
		        $micro_account=$data_micro_account["dt"]["rows"][0]["number"];
		    }
		    else 
		    	return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'应用未开启业务代理，不能推送消息。')));	     
        }
        $login_account= $micro_account;
        if($openid!="wefafaproxy"){
        	$staff = $this->checkOpenid($conn,$openid);
		    if(empty($staff)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'openid不存在。')));
        }

		$sql="SELECT b.openid FROM we_micro_account a LEFT JOIN we_staff b ON b.login_account=a.number AND b.eno=a.eno WHERE a.number=?;";
		$para=array($micro_account);
		$data=$conn->GetData('dt',$sql,$para);
		if($data==null || count($data['dt']['rows'])==0 || empty($data['dt']['rows'][0]['openid'])) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'微应用帐号不存在。')));
		$microOpenid=$data['dt']['rows'][0]['openid'];	
        $re= array('returncode'=>'9999','msg'=>'消息发送失败。');
        try {
        	$msgType='PICTURE';
        	$send_state='2';
        	$buttons='';
        	foreach ($msgObj as $objkey => $objval) {
        		if($objkey=='title') $title=$objval;
        		if($objkey=='image') $image=$objval;
        		if($objkey=='summary') $summary=$objval;
        		else if($objkey=='content') $content=htmlspecialchars_decode($objval);
        		else if($objkey=='buttons') $buttons=$objval;
        	}
        	if(empty($title)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'消息标题不能为空。')));
        	if(empty($image)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'消息图片不能为空。')));
        	if(empty($summary)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'消息摘要不能为空。')));
        	if(empty($content)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'消息内容不能为空。')));
        	
			$uniqid=str_replace('.','',uniqid('',true));
			$link=$this->getLink($uniqid);

			$file_url=$this->container->getParameter('FILE_WEBSERVER_URL');
			if(strpos($image,$file_url)===false)
			{				
				$image = $file_url.$image;
			}
			if(empty($buttons)) $msgContent= array('picturemsg'=>array('headitem'=>array('title'=>$title
							,'image'=>array('type'=>'URL','value'=>$image)
							,'content'=>$summary
							,'link'=>$link)));
			else $msgContent= array('picturemsg'=>array('headitem'=>array('title'=>$title
							,'image'=>array('type'=>'URL','value'=>$image)
							,'content'=>$summary
							,'link'=>$link
							,'buttons'=>$buttons)));
			$msgContent=json_encode($msgContent);
			
 			$sqls=array();
	        $paras=array();
	        $id=SysSeq::GetSeqNextValue($conn,'we_micro_send_message','id');
			$sqls[]= "INSERT INTO `we_micro_send_message` (`id`, `send_account`, `send_groupid`, `send_datetime`, `send_state`, `send_isbutton`, `send_source`,`send_type`) VALUES (?, ?, ?, now(), ?, ?, ?,?);";
			$paras[]= array($id,$micro_account,$micro_groupid,$send_state,false,'interface',$msgType);
			$msgid=SysSeq::GetSeqNextValue($conn,'we_micro_message','id');
			$sqls[]= "INSERT INTO `we_micro_message` (`id`, `send_id`, `msg_title`, `msg_type`, `msg_text`, `msg_content`, `msg_summary`, `msg_img_type`, `msg_img_url`, `msg_web_url`, `ishead`, `isread`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
			$paras[]= array($msgid,$id,$title,$msgType,null,$content,$summary,'URL',$image,$uniqid,true,false);
		    $fafa_jids=array();
	    	$sqls_staff=array();
	    	$paras_staff=array();
		    $apicontroller = new \Justsy\OpenAPIBundle\Controller\ApiController();
		    $apicontroller->setContainer($this->container);
		    $MicroAccountMgr = new \Justsy\BaseBundle\Management\MicroAccountMgr($conn,$conn_im,$login_account,$this->get("logger"),$this->container);
		    if($this->checkint($micro_groupid)) {
		    	$count = $MicroAccountMgr->check_micro_fans_groupid($micro_account,$micro_groupid);
	            $microdata=array();
	            if($count>0) {//分组主键在数据库不存在
	                $microdata = $MicroAccountMgr->get_micro_fans_group($micro_account,$micro_groupid);
	                for ($i=0; $i < count($microdata); $i++) { 
		              	if(!in_array($microdata[$i]["fafa_jid"],$fafa_jids) && !empty($microdata[$i]["fafa_jid"])) {
		                	if(!in_array($microdata[$i]["fafa_jid"],$fafa_jids)){
			                	array_push($fafa_jids, $microdata[$i]["fafa_jid"]);
			                	$staffid=SysSeq::GetSeqNextValue($conn,'we_micro_message_recipient','id');
			                	$sqls_staff[]= "INSERT INTO `we_micro_message_recipient` (`id`, `send_id`, `eno`, `login_account`, `openid`, `fafa_jid`, `rec_datetime`) VALUES (?, ?, ?, ?, ?, ?, now());";
			                	$paras_staff[]= array($staffid,$id,$microdata[$i]["eno"],$microdata[$i]["login_account"],$microdata[$i]["openid"],$microdata[$i]["fafa_jid"]);
		            		}
		            	}
		            }
	            }
	            if(!empty($recopenid)){
			    	$openids=explode(',',$recopenid);
			    	for ($i=0; $i < count($openids); $i++) { 
			    		$sql_staff="select fafa_jid,login_account,openid,eno from we_staff where openid=?";
			    		$data_staff=$conn->GetData("dt",$sql_staff,array((string)$openids[$i]));
			    		if ($data_staff!=null && count($data_staff["dt"]["rows"])>0) {
			    			if(!in_array($data_staff["dt"]["rows"][0]["fafa_jid"],$fafa_jids)){
				    			array_push($fafa_jids, $data_staff["dt"]["rows"][0]["fafa_jid"]);
				    			$staffid=SysSeq::GetSeqNextValue($conn,'we_micro_message_recipient','id');
			                	$sqls_staff[]= "INSERT INTO `we_micro_message_recipient` (`id`, `send_id`, `eno`, `login_account`, `openid`, `fafa_jid`, `rec_datetime`) VALUES (?, ?, ?, ?, ?, ?, now());";
			                	$paras_staff[]= array($staffid,$id,$data_staff["dt"]["rows"][0]["eno"],$data_staff["dt"]["rows"][0]["login_account"],$data_staff["dt"]["rows"][0]["openid"],$data_staff["dt"]["rows"][0]["fafa_jid"]);
		            		}
		            	}
			    	}
			    }
		    } else {
		    	if(!empty($recopenid)){
			    	$openids=explode(',',$recopenid);
			    	for ($i=0; $i < count($openids); $i++) { 
			    		$sql_staff="select fafa_jid,login_account,openid,eno from we_staff where openid=?";
			    		$data_staff=$conn->GetData("dt",$sql_staff,array((string)$openids[$i]));
			    		if ($data_staff!=null && count($data_staff["dt"]["rows"])>0) {
			    			if(!in_array($data_staff["dt"]["rows"][0]["fafa_jid"],$fafa_jids)){
				    			array_push($fafa_jids, $data_staff["dt"]["rows"][0]["fafa_jid"]);
				    			$staffid=SysSeq::GetSeqNextValue($conn,'we_micro_message_recipient','id');
			                	$sqls_staff[]= "INSERT INTO `we_micro_message_recipient` (`id`, `send_id`, `eno`, `login_account`, `openid`, `fafa_jid`, `rec_datetime`) VALUES (?, ?, ?, ?, ?, ?, now());";
			                	$paras_staff[]= array($staffid,$id,$data_staff["dt"]["rows"][0]["eno"],$data_staff["dt"]["rows"][0]["login_account"],$data_staff["dt"]["rows"][0]["openid"],$data_staff["dt"]["rows"][0]["fafa_jid"]);
		            		}
		            	}
			    	}
			    }else{
			    	$microdata = $MicroAccountMgr->get_micro_all_fans($micro_account);
		            for ($i=0; $i < count($microdata); $i++) { 
		            	if(!in_array($microdata[$i]["fafa_jid"],$fafa_jids) && !empty($microdata[$i]["fafa_jid"])) {
		                	array_push($fafa_jids, $microdata[$i]["fafa_jid"]);
		                	$staffid=SysSeq::GetSeqNextValue($conn,'we_micro_message_recipient','id');
		                	$sqls_staff[]= "INSERT INTO `we_micro_message_recipient` (`id`, `send_id`, `eno`, `login_account`, `openid`, `fafa_jid`, `rec_datetime`) VALUES (?, ?, ?, ?, ?, ?, now());";
		                	$paras_staff[]= array($staffid,$id,$microdata[$i]["eno"],$microdata[$i]["login_account"],$microdata[$i]["openid"],$microdata[$i]["fafa_jid"]);
		            	}
		            }
	        	}
		    }
		    if(!empty($fafa_jids)) {
	            $jids=array();
	            for ($i = 0; $i < count($fafa_jids); $i++) {
	                array_push($jids,(string)$fafa_jids[$i]);
	                if(count($jids)==200) {
	                    $re= $apicontroller->sendMsg2($microOpenid,implode(",",$jids),$msgContent,$msgType);
	                    $jids=array();
	                }
	            }
	            if(!empty($jids)) $re= $apicontroller->sendMsg2($microOpenid,implode(",",$jids),$msgContent,$msgType);
	        }
	        if(!empty($re['returncode']) && $re['returncode']=='0000') {
		        //添加发送消息数据
		        if(!empty($sqls)) $conn->ExecSQLs($sqls,$paras);
		        //添加接收人员
		        if(!empty($sqls_staff)) $conn->ExecSQLs($sqls_staff,$paras_staff);
	        }
        } catch (\Exception $e) {
        	$this->get('logger')->err($e->getMessage());
        }
        return $this->responseJson(json_encode($re));
	}
	//多图文消息发送 接口
	public function TextPictureMsgAction(){
		//if($_SERVER['REQUEST_METHOD']!="POST") 
		//	return $this->responseJson(json_encode(array("error"=>"10009","msg"=>"HTTP请求仅支持POST提交方式")));
		$conn = $this->get("we_data_access");
        $conn_im = $this->get("we_data_access_im");
        $request = $this->getRequest();
        $micro_account=$request->get('micro_account');
        $micro_groupid=$request->get('micro_groupid');
        $appid=$request->get('appid');
        $openid= 'wefafaproxy';
        $recopenid=$request->get('openid');
        $access_token=$request->get('access_token');
        $msg=$request->get('msg');
        if(empty($appid) && empty($micro_account)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'appid不能为空。')));
        if(empty($access_token)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'访问令牌不能为空。')));
        if(empty($msg)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'消息不能为空。')));
        $msgObj=json_decode($msg);
        if(empty($msgObj)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'消息格式不正确。')));
        if(empty($openid)) $openid="wefafaproxy";

        $token_appid = $this->checkAccessToken($conn,$appid,$openid,$access_token);	
	    if($token_appid===false) return $this->responseJson(json_encode(array('returncode'=>'0001','msg'=>'访问令牌已过期。')));
        if($token_appid != $appid) return $this->responseJson(json_encode(array('returncode'=>'0001','msg'=>'appid无效。')));
		//token通过认证
	    if(empty($micro_account))
	    {
		    $sql_micro_account="select number from we_micro_account where micro_source=? ";
		    $data_micro_account=$conn->GetData("dt",$sql_micro_account,array((string)$appid));
		    if($data_micro_account!=null && count($data_micro_account["dt"]["rows"])>0 && !empty($data_micro_account["dt"]["rows"][0]["number"])){
		        $micro_account=$data_micro_account["dt"]["rows"][0]["number"];
		    }
		    else 
		    	return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'应用未开启业务代理，不能推送消息。')));	     
        }
        $login_account= $micro_account;
        if($openid!="wefafaproxy"){
        	$staff = $this->checkOpenid($conn,$openid);
		    if(empty($staff)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'openid不存在。')));
        }

		$sql="SELECT b.openid FROM we_micro_account a LEFT JOIN we_staff b ON b.login_account=a.number AND b.eno=a.eno WHERE a.number=?;";
		$para=array($micro_account);
		$data=$conn->GetData('dt',$sql,$para);
		if($data==null || count($data['dt']['rows'])==0 || empty($data['dt']['rows'][0]['openid'])) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'微应用帐号不存在。')));
		$microOpenid=$data['dt']['rows'][0]['openid'];	
        $re= array('returncode'=>'9999','msg'=>'消息发送失败。');
        try {
		    $msgType='TEXTPICTURE';
		    $send_state='2';
 			$sqls=array();
	        $paras=array();
	        $headitem=array();
			$items=array();
			$file_url=$this->container->getParameter('FILE_WEBSERVER_URL');
			$id=SysSeq::GetSeqNextValue($conn,'we_micro_send_message','id');
			$sqls[]= "INSERT INTO `we_micro_send_message` (`id`, `send_account`, `send_groupid`, `send_datetime`, `send_state`, `send_isbutton`, `send_source`,`send_type`) VALUES (?, ?, ?, now(), ?, ?, ?,?);";
			$paras[]= array($id,$micro_account,$micro_groupid,$send_state,false,'interface',$msgType);
			foreach ($msgObj as $tpmkey => $tpmvalue) {
				if($tpmkey=='headitem') {
					$headitem=$tpmvalue;
					if(empty($headitem)) return $this->responseJson(json_encode($error));
					$head_title='';
					$head_img='';
					$head_contentHtml='';
					$head_link='';
					foreach ($headitem as $hkey => $hvalue) {
						if($hkey=='title') $head_title=$hvalue;
						else if($hkey=='image') $head_img=$hvalue;
						else if($hkey=='content') $head_contentHtml=htmlspecialchars_decode($hvalue);
					}
					if(empty($head_title)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'消息头部标题不能为空。')));
		        	if(empty($head_img)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'消息头部图片不能为空。')));
		        	if(empty($head_contentHtml)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'消息头部内容不能为空。')));
					
					$uniqid=str_replace('.','',uniqid('',true));
					$head_link=$this->getLink($uniqid);
					if(strpos($head_img,$file_url)===false)
					{
						$head_img = $file_url.$head_img;
					}
					$headitem= array('title'=>$head_title
									,'image'=>array('type'=>'URL','value'=>$head_img)
									,'link'=>$head_link);
					$msgid=SysSeq::GetSeqNextValue($conn,'we_micro_message','id');
					$sqls[]= "INSERT INTO `we_micro_message` (`id`, `send_id`, `msg_title`, `msg_type`, `msg_text`, `msg_content`, `msg_summary`, `msg_img_type`, `msg_img_url`, `msg_web_url`, `ishead`, `isread`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
					$paras[]= array($msgid,$id,$head_title,$msgType,null,$head_contentHtml,null,'URL',$head_img,$uniqid,true,false);
				}else if($tpmkey=='items') {
					$item=$tpmvalue;
					if(empty($item)) return $this->responseJson(json_encode($error));
					$item_array=array();
					for ($i=0; $i < count($item); $i++) {
						$item_title='';
						$item_img='';
						$item_contentHtml='';
						$item_link='';
						foreach ($item[$i] as $itemkey => $itemvalue) {
							if($itemkey=='title') $item_title=$itemvalue;
							else if($itemkey=='image') $item_img=$itemvalue;
							else if($itemkey=='content') $item_contentHtml=htmlspecialchars_decode($itemvalue);
						}
						if(empty($item_title)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'消息子项标题不能为空。')));
		        		if(empty($item_img)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'消息子项图片不能为空。')));
		        		if(empty($item_contentHtml)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'消息子项内容不能为空。')));
					
						$uniqid=str_replace('.','',uniqid('',true));
						$item_link=$this->getLink($uniqid);
						if(strpos($item_img,$file_url)===false)
						{				
							$item_img = $file_url.$item_img;
						}
						$item_array= array('title'=>$item_title
									,'image'=>array('type'=>'URL','value'=>$item_img)
									,'link'=>$item_link);
						array_push($items, $item_array);
						$msgid=SysSeq::GetSeqNextValue($conn,'we_micro_message','id');
						$sqls[]= "INSERT INTO `we_micro_message` (`id`, `send_id`, `msg_title`, `msg_type`, `msg_text`, `msg_content`, `msg_summary`, `msg_img_type`, `msg_img_url`, `msg_web_url`, `ishead`, `isread`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
						$paras[]= array($msgid,$id,$item_title,$msgType,null,$item_contentHtml,null,'URL',$item_img,$uniqid,false,false);
					}
				}
			}
			$msgContent= array('textpicturemsg'=>array('headitem'=>$headitem,'item'=>$items));
			$msgContent=json_encode($msgContent);

		    $fafa_jids=array();
	    	$sqls_staff=array();
	    	$paras_staff=array();
		    $apicontroller = new \Justsy\OpenAPIBundle\Controller\ApiController();
		    $apicontroller->setContainer($this->container);
		    $MicroAccountMgr = new \Justsy\BaseBundle\Management\MicroAccountMgr($conn,$conn_im,$login_account,$this->get("logger"),$this->container);
		    if($this->checkint($micro_groupid)) {
		    	$count = $MicroAccountMgr->check_micro_fans_groupid($micro_account,$micro_groupid);
	            $microdata=array();
	            if($count>0) {//分组主键在数据库不存在
	                $microdata = $MicroAccountMgr->get_micro_fans_group($micro_account,$micro_groupid);
	                for ($i=0; $i < count($microdata); $i++) { 
		              	if(!in_array($microdata[$i]["fafa_jid"],$fafa_jids) && !empty($microdata[$i]["fafa_jid"])) {
		                	if(!in_array($microdata[$i]["fafa_jid"],$fafa_jids)){
			                	array_push($fafa_jids, $microdata[$i]["fafa_jid"]);
			                	$staffid=SysSeq::GetSeqNextValue($conn,'we_micro_message_recipient','id');
			                	$sqls_staff[]= "INSERT INTO `we_micro_message_recipient` (`id`, `send_id`, `eno`, `login_account`, `openid`, `fafa_jid`, `rec_datetime`) VALUES (?, ?, ?, ?, ?, ?, now());";
			                	$paras_staff[]= array($staffid,$id,$microdata[$i]["eno"],$microdata[$i]["login_account"],$microdata[$i]["openid"],$microdata[$i]["fafa_jid"]);
		            		}
		            	}
		            }
	            }
	            if(!empty($recopenid)){
			    	$openids=explode(',',$recopenid);
			    	for ($i=0; $i < count($openids); $i++) { 
			    		$sql_staff="select fafa_jid,login_account,openid,eno from we_staff where openid=?";
			    		$data_staff=$conn->GetData("dt",$sql_staff,array((string)$openids[$i]));
			    		if ($data_staff!=null && count($data_staff["dt"]["rows"])>0) {
			    			if(!in_array($data_staff["dt"]["rows"][0]["fafa_jid"],$fafa_jids)){
				    			array_push($fafa_jids, $data_staff["dt"]["rows"][0]["fafa_jid"]);
				    			$staffid=SysSeq::GetSeqNextValue($conn,'we_micro_message_recipient','id');
			                	$sqls_staff[]= "INSERT INTO `we_micro_message_recipient` (`id`, `send_id`, `eno`, `login_account`, `openid`, `fafa_jid`, `rec_datetime`) VALUES (?, ?, ?, ?, ?, ?, now());";
			                	$paras_staff[]= array($staffid,$id,$data_staff["dt"]["rows"][0]["eno"],$data_staff["dt"]["rows"][0]["login_account"],$data_staff["dt"]["rows"][0]["openid"],$data_staff["dt"]["rows"][0]["fafa_jid"]);
		            		}
		            	}
			    	}
			    }
		    } else {
		    	if(!empty($recopenid)){
			    	$openids=explode(',',$recopenid);
			    	for ($i=0; $i < count($openids); $i++) { 
			    		$sql_staff="select fafa_jid,login_account,openid,eno from we_staff where openid=?";
			    		$data_staff=$conn->GetData("dt",$sql_staff,array((string)$openids[$i]));
			    		if ($data_staff!=null && count($data_staff["dt"]["rows"])>0) {
			    			if(!in_array($data_staff["dt"]["rows"][0]["fafa_jid"],$fafa_jids)){
				    			array_push($fafa_jids, $data_staff["dt"]["rows"][0]["fafa_jid"]);
				    			$staffid=SysSeq::GetSeqNextValue($conn,'we_micro_message_recipient','id');
			                	$sqls_staff[]= "INSERT INTO `we_micro_message_recipient` (`id`, `send_id`, `eno`, `login_account`, `openid`, `fafa_jid`, `rec_datetime`) VALUES (?, ?, ?, ?, ?, ?, now());";
			                	$paras_staff[]= array($staffid,$id,$data_staff["dt"]["rows"][0]["eno"],$data_staff["dt"]["rows"][0]["login_account"],$data_staff["dt"]["rows"][0]["openid"],$data_staff["dt"]["rows"][0]["fafa_jid"]);
		            		}
		            	}
			    	}
			    }else{
			    	$microdata = $MicroAccountMgr->get_micro_all_fans($micro_account);
		            for ($i=0; $i < count($microdata); $i++) { 
		            	if(!in_array($microdata[$i]["fafa_jid"],$fafa_jids) && !empty($microdata[$i]["fafa_jid"])) {
		                	array_push($fafa_jids, $microdata[$i]["fafa_jid"]);
		                	$staffid=SysSeq::GetSeqNextValue($conn,'we_micro_message_recipient','id');
		                	$sqls_staff[]= "INSERT INTO `we_micro_message_recipient` (`id`, `send_id`, `eno`, `login_account`, `openid`, `fafa_jid`, `rec_datetime`) VALUES (?, ?, ?, ?, ?, ?, now());";
		                	$paras_staff[]= array($staffid,$id,$microdata[$i]["eno"],$microdata[$i]["login_account"],$microdata[$i]["openid"],$microdata[$i]["fafa_jid"]);
		            	}
		            }
	        	}
		    }
		    if(!empty($fafa_jids)) {
	            $jids=array();
	            for ($i = 0; $i < count($fafa_jids); $i++) {
	                array_push($jids,(string)$fafa_jids[$i]);
	                if(count($jids)==200) {
	                    $re= $apicontroller->sendMsg2($microOpenid,implode(",",$jids),$msgContent,$msgType);
	                    $jids=array();
	                }
	            }
	            if(!empty($jids)) $re= $apicontroller->sendMsg2($microOpenid,implode(",",$jids),$msgContent,$msgType);
	        }
	        if(!empty($re['returncode']) && $re['returncode']=='0000') {
		        //添加发送消息数据
		        if(!empty($sqls)) $conn->ExecSQLs($sqls,$paras);
		        //添加接收人员
		        if(!empty($sqls_staff)) $conn->ExecSQLs($sqls_staff,$paras_staff);
	        }
        } catch (\Exception $e) {
        	$this->get('logger')->err($e->getMessage());
        }
        return $this->responseJson(json_encode($re));
	}
	//获取公众号发送的历史消息 接口
	public function GetMicroMessageAction() {
		$conn = $this->get("we_data_access");
        $conn_im = $this->get("we_data_access_im");
		$currUser = $this->get('security.context')->getToken();
		if(!empty($currUser)) $currUser = $currUser->getUser(); 
		else $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'请您先登录系统。')));
		$request = $this->getRequest();
		$micro_account= $request->get('micro_account');
		if(empty($micro_account)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'微应用帐号不能为空。')));
		$pageindex= $request->get('pageindex');
		if(empty($pageindex)) $pageindex=1;
		if(!preg_match('/^[0-9]+$/',$pageindex)) return $this->responseJson(json_encode(array('returncode'=>'9999','msg'=>'索引页只能是正整数。')));
		$sql_count="select count(*) as count from we_micro_send_message where send_account=? ;";
		$para_count=array($micro_account);
		$data_count=$conn->GetData('dt',$sql_count,$para_count);
		$re= array('returncode'=>'0000','total'=>0,'list'=>array());
		if($data_count!=null && count($data_count['dt']['rows']) > 0 && !empty($data_count['dt']['rows'][0]['count'])) {
			$total=$data_count['dt']['rows'][0]['count'];
			//这里查询数据 每页10条
			$sql="select * from we_micro_send_message where send_account =? order by send_datetime desc LIMIT ".(($pageindex-1)*10).",10 ";
			$para=array($micro_account);
			$data=$conn->GetData('dt',$sql,$para);
			$list=array();
			if($data!=null && count($data['dt']['rows']) > 0 ) {
				for ($i=0; $i < count($data['dt']['rows']) ; $i++) { 
					$sendid=$data['dt']['rows'][$i]['id'];
					$sql_msg="select * from we_micro_message where send_id=?";
					$para_msg=array($sendid);
					$data_msg=$conn->GetData('dt',$sql_msg,$para_msg);
					$msg_type=$data['dt']['rows'][$i]['send_type'];
					$send_datetime=$data['dt']['rows'][$i]['send_datetime'];
					//消息内容
					if($data_msg==null && count($data_msg['dt']['rows']) > 0 ) {
						$data=array();
						switch ($msg_type) {
							case 'TEXT':
								$items=array();
								for ($j=0; $j < count($data_msg['dt']['rows']) ; $j++) { 
									$msg_title=$data_msg['dt']['rows'][$j]['msg_title'];
									$msg_text=$data_msg['dt']['rows'][$j]['msg_text'];
									array_push($items, array('title'=>$msg_title,'content'=>$msg_text));
								}
								$data=array('item'=>$items);
								break;
							case 'PICTURE':
								$msg_title='';
								$msg_img_type='';
								$msg_img_value='';
								$msg_content='';
								$msg_link='';
								for ($k=0; $k < count($data_msg['dt']['rows']) ; $k++) {
									$msg_title=$data_msg['dt']['rows'][$k]['msg_title'];
									$msg_content=$data_msg['dt']['rows'][$k]['msg_summary']; //摘要
									$msg_img_type=$data_msg['dt']['rows'][$k]['msg_img_type'];
									$msg_img_value=$data_msg['dt']['rows'][$k]['msg_img_url'];
									$msg_link=$data_msg['dt']['rows'][$k]['msg_web_url'];
									break;
								}
								$data=array('headitem'=>array('title'=>$msg_title,'image'=>array('type'=>$msg_img_type,'vaue'=>$msg_img_value),'content'=>$msg_content,'link'=>$msg_link));
								break;
							case 'TEXTPICTURE':
								$items=array('headitem'=>array(),'item'=>array());
								for ($h=0; $h < count($data_msg['dt']['rows']) ; $h++) {
									$msg_title=$data_msg['dt']['rows'][$h]['msg_title'];
									$msg_img_type=$data_msg['dt']['rows'][$h]['msg_img_type'];
									$msg_img_value=$data_msg['dt']['rows'][$h]['msg_img_url'];
									$msg_link=$data_msg['dt']['rows'][$h]['msg_web_url'];
									$ishead=$data_msg['dt']['rows'][$h]['ishead'];
									if($ishead || $ishead=='1') $items['headitem']=array('title'=>$msg_title,'image'=>array('type'=>$msg_img_type,'vaue'=>$msg_img_value),'link'=>$msg_link);
									else array_push($items['item'], array('title'=>$msg_title,'image'=>array('type'=>$msg_img_type,'vaue'=>$msg_img_value),'link'=>$msg_link));
									break;
								}
								$data=$items;
								break;
						}
						if(!empty($data)) array_push($list, array('type'=>$msg_type,'data'=>$data,'date'=>$send_datetime));
					}
				}
			}
			$re= array('returncode'=>'0000','total'=>$total,'list'=>$list);
		}
		return $this->responseJson(json_encode($re));
	}
	//上传文件到mongodb
	private function uploadImage($upfile) {
		$re= array('returncode'=>'9999','msg'=>'图片上传失败。');
		try {
			$isimage = preg_match(\Justsy\BaseBundle\Common\MIME::getMIMEImgReg(), strtolower($upfile->getClientOriginalName()));
	        if ($isimage) {
	        	$im = new \Imagick($upfile->getPathname());
        		if ($im->getImageWidth() > 1600 && $im->getImageHeight() > 1200) $re= array('returncode'=>'9999','msg'=>'图片像素太高啦(1600px*1200px)。');
        		else {
        			$dm = $this->get('doctrine.odm.mongodb.document_manager'); 
			        $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
			        $filename = $upfile->getClientOriginalName();
			        $doc->setName($filename);
			        $doc->setFile($upfile->getPathname()); 
			        $dm->persist($doc);
			        $dm->flush();
			        $fileid = $doc->getId();

			        $re= array('returncode'=>'0000','fileid'=>$fileid);
        		}
      		}else $re= array('returncode'=>'9999','msg'=>'只能上传图片类型的文件。');
      		unlink($upfile->getPathname());
		} catch (\Exception $e) {
			$this->get('logger')->err($e->getMessage());
		}
        return $this->responseJson(json_encode($re));
	}
	//读取文件或写入文件$type(w  r) 
	private function readerFile($path,$file,$type,$content){
		//if(!file_exists($path) && !is_dir($path)) mkdir($path);
		try {
			$file_path=$path.$file;
			$handle=fopen($file_path,$type); //写入方式打开文件路径
			fwrite($handle,$content); //把刚才替换的内容写进生成的HTML文件
			fclose($handle);	
		} catch (\Exception $e) {
			$this->get('logger')->err($e->getMessage());
		}
	}
	//创建目录
	private function create_folders($dir) {
       return is_dir($dir) or ($this->create_folders(dirname($dir)) and mkdir($dir, 0777));
	}
	private function getFileId($upfile,$maxwidth=2100,$maxheight=1600) {
		$fileid='';
		if(empty($upfile)){
    		return '';
    	}
        $isimage = preg_match(\Justsy\BaseBundle\Common\MIME::getMIMEImgReg(), strtolower($upfile->getClientOriginalName()));
        
        if ($isimage) {
        	if($maxwidth > 0 && $maxheight > 0) {
        		$im = new \Imagick($upfile->getPathname());
	        	if(!empty($maxwidth) && $im->getImageWidth() > $maxwidth) {
	        		unlink($upfile->getPathname());
					return '';
	        	}
	        	if(!empty($maxheight) && $im->getImageHeight() > $maxheight) {
	        		unlink($upfile->getPathname());
					return '';
	        	}
        	}
        	//开始上传图片
        	$dm = $this->get('doctrine.odm.mongodb.document_manager'); 
	        $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
	        $filename = $upfile->getClientOriginalName();
	        $doc->setName($filename);
	        $doc->setFile($upfile->getPathname()); 
	        $dm->persist($doc);
	        $dm->flush();
	        $fileid = $doc->getId();
        }
        unlink($upfile->getPathname());
        return $fileid;
	}
	//返回json字符串
	private function responseJson($re) {
        $response = new Response($re);
        $response->headers->set('Content-Type', 'text/html');
        return $response;
    }
    //获取电脑IP地址
    private function getIp() {
		if (getenv('HTTP_CLIENT_IP')) $ip = getenv('HTTP_CLIENT_IP');
		elseif (getenv('HTTP_X_FORWARDED_FOR')) $ip = getenv('HTTP_X_FORWARDED_FOR');
		elseif (getenv('HTTP_X_FORWARDED')) $ip = getenv('HTTP_X_FORWARDED');
		elseif (getenv('HTTP_FORWARDED_FOR')) $ip = getenv('HTTP_FORWARDED_FOR');
		elseif (getenv('HTTP_FORWARDED')) $ip = getenv('HTTP_FORWARDED');
		else $ip = $_SERVER['REMOTE_ADDR'];
		return $ip;
	}
	//认证openid
	private function checkOpenid($conn,$openid) {
		$sql ="select eno,fafa_jid,login_account,nick_name,auth_level,mobile from we_staff where openid=? ";
		$data = $conn->getData("dt",$sql,array($openid));
		if($data!=null && count($data["dt"]["rows"])>0) return $data["dt"]["rows"][0];
		else return null;
	}
		  
	//验证授权令牌 true有效 false无效
	private function checkAccessToken($conn,$appid,$openid,$access_token) {
		$sql = "select appid from we_app_oauth_sessions where appid=? and userid=? and user_type='sys' and access_token=? and access_token_expires>=?";
		$para=array((string)$appid,(string)$openid,(string)$access_token,time());
		$data = $conn->getData("dt",$sql,$para);
		if($data!=null && count($data["dt"]["rows"]) > 0) return $data["dt"]["rows"][0]["appid"];
		return false;
	}

	private function checkint($num){
		if (empty($num))return false;
        if (is_int($num) && ($num>=0)) return true;
        return false;
    }
}