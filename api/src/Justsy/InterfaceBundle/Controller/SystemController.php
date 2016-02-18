<?php

namespace Justsy\InterfaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContext;
use Justsy\BaseBundle\Common\DES;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Login\UserSession;
use Justsy\BaseBundle\Controller\AccountController;
use Justsy\InterfaceBundle\SsoAuth\SsoUserAuthController;

class SystemController extends Controller
{   
  public function logincheckAction() 
  {
    $re = array("returncode" => ReturnCode::$SYSERROR);
    $request = $this->getRequest();    
    $login_account = ($request->get("login_account"));
    $password = $request->get("password");
    $comefrom = $request->get("comefrom");
    $datascope = $request->get("datascope");//登录成功后，顺便返回的数据范围,以加快客户端速度。暂时支持none(返回登录成功必须信息)\all(返回当前登录人的信息及好友列表数据)
    $portalversion = $request->get("portalversion");
    if(empty($datascope)) $datascope = "";
    if (empty($comefrom)) $comefrom = "00";
    $this->get('logger')->err("login_account.>>>>>>>>>>>>>>>>>>>{$login_account}");
    $this->get('logger')->err("password.>>>>>>>>>>>>>>>>>>>{$password}");
    $this->get('logger')->err("comefrom.>>>>>>>>>>>>>>>>>>>{$comefrom}");
    $request->getSession()->set('comefrom',  $comefrom); 
    $authController = new SsoUserAuthController();
    $authController->setContainer($this->container);
    $re = $authController->dispatchAction($this,$login_account,$password,$comefrom,$datascope,$portalversion);    
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }

  public function sendfileAction() 
  {
    $re = array();
    $request = $this->getRequest();
	    
    $filename = $request->get("filename");
    $hashvalue = $request->get("hashvalue");

    $this->get('logger')->err("sendfile.>>>>>>>>>>>>>>>>>>>{$filename}({$hashvalue})");
    $type = $request->get("type"); //发送类型。GROUP/CHAT
    $type = empty($type)?"CHAT":$type;
    $from = $request->get("from");//为空时表示是自己
    if(empty($from)) $from=$request->get("openid");
    $user = $this->get('security.context')->getToken();
    if (empty($filename))
    {
    	return $this->responseJson(Utils::WrapResultError("参数不正确"));
    }
    if(empty($hashvalue))
    {
    	$hashvalue = md5($filename);
    }
    if(empty($user))
    {
    	//匹配jid
    	if(!preg_match("/^[0-9]{5,}-[0-9]{6}@/",$from))
    	{
	    	$staffMgr = new \Justsy\BaseBundle\Management\Staff($this->get("we_data_access"),$this->get("we_data_access_im"),$from,$this->get("logger"),$this->container);
			$staffinfo = $staffMgr->getInfo();
			if(empty($staffinfo))
			{
		    	return $this->responseJson(Utils::WrapResultError("from参数无效或不存在"));
			}
			$from = $staffinfo["fafa_jid"];
		}
	}
	else
	{
		$user = $user->getUser();
		$from = empty($from) ? $user->fafa_jid : $from;
	}
    $to = $request->get("to");
    // multipart/form-data
    
    try 
    {
      if (!isset($_FILES['userfile']))
      {
        $upfile = tempnam(sys_get_temp_dir(), "logo");
        $somecontent1 = base64_decode($request->get('filedata'));
        if ($handle = fopen($upfile, "w+")) {   
          if (!fwrite($handle, $somecontent1) == FALSE) {   
            fclose($handle);  
          }  
        }
      }
      else $upfile = $_FILES['userfile']['tmp_name'];
     
      $dm = $this->get('doctrine.odm.mongodb.document_manager'); 
      $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
      $doc->setName($filename);
      $doc->setFile($upfile); 
      $dm->persist($doc);
      $dm->flush();
      $fileid = $doc->getId();
      //缩略图处理
      
      $thumb = tempnam(sys_get_temp_dir(), 'thumb');
      @copy($upfile,$thumb);
      $thumb_fileid = '';
      $gd = new \Justsy\BaseBundle\Common\lib_image_imagick();
      $ispic=$gd->open($thumb);
      $gen_thumb_flag = false;
      if($ispic)
      {
	      $gd->resize_to(320,320);
	      $gd->save_to($thumb);	
        $gen_thumb_flag=true;      
      }
      else
      {
        $fix = explode(".", $filename);
        $fix =strtolower($fix[count($fix)-1]);
        if($fix=="mp4")
        {
          try
          {
            exec ('ffmpeg -v 0 -i '.$upfile.' -y -f image2 -ss 0.2 -vframes 1 -s 320*320 '.$thumb);
            //更新$filename的后缀，否则会自动使用.mp4
            $filename = str_replace('.mp4', '.jpg', $filename);
            $gen_thumb_flag=true;
          }
          catch(\Exception $e)
          {
            $this->get('logger')->err($e);
          }
        }
      }
      if($gen_thumb_flag)
      {
        $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
        $doc->setName('thumb_'.$filename);
        $doc->setFile($thumb); 
        $dm->persist($doc);
        $dm->flush();
        $thumb_fileid = $doc->getId();
      }
      unlink($upfile);
      unlink($thumb);
      
      //д��ݿ⣺we_files
      $da = $this->get("we_data_access_im");
      
      $sqls ="insert into im_lib_files(fileid, filepath, filedesc, addstaff, savelevel, lastdate)values(?,?,'',?,'2',now())";
      $all_params=array((String)$fileid,(String)$hashvalue,(String)$from);
      $da->ExecSQL($sqls,$all_params);
      $message = $filename;
      $path = $this->container->getParameter('FILE_WEBSERVER_URL').$fileid;
      $title = $type."|".$to;
      //发送消息
      /*
      if($type=="CHAT")
      {
      	Utils::sendImMessage($from,$to,$title,$message,$this->container,$path,"",false,"file");
      }
      else
      {
      	$groupMgr = new \Justsy\BaseBundle\Management\GroupMgr($this->get("we_data_access"),$this->get("we_data_access_im"));
      	$jids = $groupMgr->getGroupMembersJidByIM($to);
      	if($jids && count($jids)>0)
      		Utils::sendImMessage($from,str_replace(",,",",",str_replace($from, "", implode(",", $jids))),$title,$message,$this->container,$path,"",false,"file");
      }*/
      $re["type"] = $type;
      $re["path"] = $path;
      $re["to"] = $to;
      $re["filename"] = $filename;
      $re["fileid"] = $fileid;
      $re['thumb'] = $thumb_fileid;
      $re['thumb_path'] =empty($thumb_fileid) ? "": $this->container->getParameter('FILE_WEBSERVER_URL').$thumb_fileid;
      $re["hashvalue"] = $hashvalue;
      $re = Utils::WrapResultOK($re);
    } 
    catch (\Exception $e) 
    {
      $re["returncode"] = ReturnCode::$SYSERROR;
      $this->get('logger')->err($e);
      $re = Utils::WrapResultError($e->getMessage());
    }
    $this->get('logger')->err("sendfile.>>>>>>>>>>>>>>>>>>>".json_encode($re));
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }

  public function getfileAction($hashvalue)
  {
      $da_im = $this->get("we_data_access_im");
      
      $sqls ="select fileid from im_lib_files where filepath=?";
      $all_params=array((String)$hashvalue);
      $ds = $da_im->getData("t",$sqls,$all_params);
      if($ds && count($ds["t"]["rows"])>0)
      {
        $mongoDoc = new \Justsy\MongoDocBundle\Controller\DefaultController();
        $mongoDoc->setContainer($this->container);
        return $mongoDoc->getFileAction($ds["t"]["rows"][0]["fileid"]);
      }
      else
      {
        $response = new Response("", 404);      
        //$response->headers->set('Last-Modified', $_SERVER['HTTP_IF_MODIFIED_SINCE']);
        return $response;        
      }
  }
  
  public function fileuploadAction() 
  {
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
	  $user = $this->get('security.context')->getToken()->getUser();
    
    $filename = $request->get("filename");
    $circle_id = $request->get("circle_id");
    $group_id = $request->get("group_id");
    // multipart/form-data
    
    try 
    {
      if (empty($filename) || empty($circle_id) || empty($group_id)) throw new \Exception("param is null");
      
      if (!isset($_FILES['userfile']))
      {
        $upfile = tempnam(sys_get_temp_dir(), "we");
        unlink($upfile);
        $somecontent1 = base64_decode($request->get('filedata'));
        if ($handle = fopen($upfile, "w+")) {   
          if (!fwrite($handle, $somecontent1) == FALSE) {   
            fclose($handle);  
          }  
        }
      }
      else $upfile = $_FILES['userfile']['tmp_name'];
      
      $isimage = preg_match(\Justsy\BaseBundle\Common\MIME::getMIMEImgReg(), strtolower($filename));
      $imagefilename_small = preg_replace("/(\.[^\.]*)$/", '_small\1', $filename);
      $imagefilename_middle = preg_replace("/(\.[^\.]*)$/", '_middle\1', $filename);
      $imagefilepath_small = $upfile.".".$imagefilename_small;
      $imagefilepath_middle = $upfile.".".$imagefilename_middle;
      
      $dm = $this->get('doctrine.odm.mongodb.document_manager'); 
      $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
      $doc->setName($filename);
      $doc->setFile($upfile); 
      $dm->persist($doc);
      $dm->flush();
      $fileid = $doc->getId();
      
      $fileid_small = "";
      $fileid_middle = "";
      if ($isimage)
      {
        $im = new \Imagick($upfile);
        if ($im->getImageWidth() > 100)
        {
          if ($im->getImageWidth() >= $im->getImageHeight())
            $im->scaleImage(100, 0);
          else
            $im->scaleImage(0, 100);
          $im->writeImage($imagefilepath_small);
          $im->destroy();
        }
        else
        {
          copy($upfile, $imagefilepath_small);
        }
        
        $im = new \Imagick($upfile);
        if ($im->getImageWidth() > 400)
        {
          $im->scaleImage(400, 0);
          $im->writeImage($imagefilepath_middle);
          $im->destroy();
        }
        else
        {
          copy($upfile, $imagefilepath_middle);
        }
        
        $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
        $doc->setName($imagefilename_small);
        $doc->setFile($imagefilepath_small); 
        $dm->persist($doc);
        $dm->flush();
        $fileid_small = $doc->getId();  
        unlink($imagefilepath_small);
        
        $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
        $doc->setName($imagefilename_middle);
        $doc->setFile($imagefilepath_middle); 
        $dm->persist($doc);
        $dm->flush();
        $fileid_middle = $doc->getId();  
        unlink($imagefilepath_middle);
      }
      
      unlink($upfile);
      
      //д��ݿ⣺we_files
      $da = $this->get("we_data_access");
      $fixs = explode(".",strtolower($filename));
      
      $sqls = array();
      $all_params = array();
      
      $sqls[] ="insert into we_files(`circle_id`,`file_ext`,`file_id`,`file_name`,`post_to_group`,`up_by_staff`,`up_date`)values(?,?,?,?,?,?,now())";
      $all_params[]=array((String)$circle_id,(String)$fixs[count($fixs)-1],(String)$fileid,(String)$filename,(String)$group_id,(String)$user->getUsername());
      
      if ($isimage)
      {
        $sqls[] = "insert into we_files_image(file_id, file_id_small, file_id_middle) values(?, ?, ?)";
        $all_params[]=array((string)$fileid, (string)$fileid_small, (string)$fileid_middle);
      }
      $da->ExecSQLs($sqls, $all_params);
      
      $re["returncode"] = ReturnCode::$SUCCESS;
      $re["file_id"] = $fileid;
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
  
  public function getServerDiffTimeAction(){
      $re = array("returncode" => ReturnCode::$SUCCESS);
      $request = $this->getRequest();
      $time = $request->get("time");
      $re['server_time']=$this->getMillisecond($time);
      $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
  }

  //根据姓名查询联系人staff
  public function nameToStaffsAction(){
  	$request = $this->getRequest();
  	$name = $request->get("name");
  	$page=$request->get("page");
  	$da = $this->get("we_data_access");
  	$re = array("returncode" => ReturnCode::$SUCCESS);
  	if(strLen($name)==0)
  	{
  		$re['returncode']=ReturnCode::$OTHERERROR;
  	}
  	else{
  		$sql=" select a.login_account,a.fafa_jid jid, a.nick_name, a.photo_path, a.photo_path_small, a.photo_path_big,ep.eshortname
  		from we_staff  a    inner join we_enterprise ep on a.eno=ep.eno
  		where a.nick_name like CONCAT('%','{$name}','%') and not exists(select 1 from we_micro_account  where we_micro_account.number=a.login_account) order by 1";
  		if(!empty($page)){
	      	$page=((float)$page)*20;
	      	$sql.=" limit {$page}, 20";
	      }else{
	      	$sql.=" limit 0, 20";
	      }
  		$dataset = $da->GetData("staffs",$sql);
  		$rows =  $dataset["staffs"]["rows"];
  		$re['staffs']=$rows;
  	}
  	$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
  	$response->headers->set('Content-Type', 'text/json');
  			return $response;
  }
  
  //根据邮箱查询联系人staff
  public function emailToStaffsAction(){
  	$request = $this->getRequest();
  	$mail = $request->get("email");
  	$page=$request->get("page");
  	$da = $this->get("we_data_access");
  	$re = array("returncode" => ReturnCode::$SUCCESS);
  	if(empty($mail)||strLen($mail)==0)
  	{
  		$re['returncode']=ReturnCode::$OTHERERROR;
  	}
  	else{
  		$array=explode("@",$mail);
  		$sql=" select a.login_account,a.fafa_jid jid, a.nick_name, a.photo_path, a.photo_path_small, a.photo_path_big,ep.eshortname
  		from we_staff  a    inner join we_enterprise ep on a.eno=ep.eno ";
  		if(!empty($array)&&count($array)>1){
  			$sql.="where a.login_account like CONCAT('%','{$mail}','%') and not exists(select 1 from we_micro_account  where we_micro_account.number=a.login_account) order by 1 ";
  		}else{
  			$sql.="where substring_index(a.login_account,'@',1) like CONCAT('%','{$mail}','%') and not exists(select 1 from we_micro_account  where we_micro_account.number=a.login_account) order by 1 ";
  		}
  		if(!empty($page)){
	      	$page=((float)$page)*20;
	      	$sql.=" limit {$page}, 20";
	      }else{
	      	$sql.=" limit 0, 20";
	      }
  		$dataset = $da->GetData("staffs",$sql);
  		$rows =  $dataset["staffs"]["rows"];
  		$re['staffs']=$rows;
  	}
    	$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    	$response->headers->set('Content-Type', 'text/json');
    			return $response;
  }
  
//根据手机号查询联系人staff
	public function mobileToStaffsAction(){
  		$request = $this->getRequest();
  		$mobile = $request->get("mobile");
  		$page=$request->get("page");
  		$da = $this->get("we_data_access");
  		$re = array("returncode" => ReturnCode::$SUCCESS);
    	if(strLen($mobile)==0)
    	{
    	$re['returncode']=ReturnCode::$OTHERERROR;
  		}
  		else{
  		$sql=" select a.login_account,a.fafa_jid jid, a.nick_name, a.photo_path, a.photo_path_small, a.photo_path_big,ep.eshortname
  		from we_staff  a    inner join we_enterprise ep on a.eno=ep.eno where a.mobile_bind like '%{$mobile}%' and not exists(select 1 from we_micro_account  where we_micro_account.number=a.login_account)  order by 1 ";
  		if(!empty($page)){
	      	$page=((float)$page)*20;
	      	$sql.=" limit {$page}, 20";
	      }else{
	      	$sql.=" limit 0, 20";
	      }
  		$dataset = $da->GetData("staffs",$sql);
  		$rows =  $dataset["staffs"]["rows"];
  		$re['staffs']=$rows;
  		}
  		$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
  		$response->headers->set('Content-Type', 'text/json');
  		return $response;
  }
  
  public function validcodeAction(){
  	$request = $this->getRequest();
  	$account = $request->get("account");
    $type = $request->get("type");
    if(empty($type))
    {
      $type = "FP"; //未指定验证码类型时，默认为找回密码类型
    }
  	$da = $this->get("we_data_access");
  	$re = array("returncode" => ReturnCode::$SUCCESS);
  	if(empty($account)){
      return $this->responseJson(Utils::WrapResultError("帐号不能为空"),$request->get('jsoncallback'));
  	}
  	try {
      $isEmail = Utils::validateEmail($account);
      $isMobile= Utils::validateMobile($account);
      if(!$isEmail && !$isMobile) 
      {
        return $this->responseJson(Utils::WrapResultError("帐号格式不正确，仅支持邮箱或手机帐号"),$request->get('jsoncallback'));
      }

      $u_staff = new \Justsy\BaseBundle\Management\Staff($da,$this->get("we_data_access_im"),$account,$this->get('logger'));
      $staffinfo = $u_staff->getInfo();
      if(empty($staffinfo))
      {
        return $this->responseJson(Utils::WrapResultError("帐号不正确，请您重新输入"),$request->get('jsoncallback'));
      }
      
      if($isEmail)
      {
        $mobile = $staffinfo["mobile_bind"]; 
        if(empty($mobile)) //邮件找回密码
        {
            return $this->responseJson(Utils::WrapResultOK("该帐号未绑定手机号，你可以通过网页版找回密码"),$request->get('jsoncallback'));
        }
      }
      else
      {
        $mobile = $account;
      }
      //验证码获取检查
      $sql = "select (select unix_timestamp( now())-unix_timestamp(req_date) maxlong from we_mobilebind_validcode where login_account=? and actiontype='".$type."' and req_date>=SUBDATE(now(),INTERVAL 8 HOUR) order by req_date desc limit 0,1) maxlong,(select count(1) cnt FROM we_mobilebind_validcode where login_account=? and actiontype='".$type."' and date(req_date)=date(now())) num";
      $ds = $da->Getdata('wnvc',$sql,array((string)$account,(string)$account));
      $lastgetmobilevaildcodetime =  $ds["wnvc"]["rows"][0]["maxlong"];
      $getmobilevaildcodenums = $ds["wnvc"]["rows"][0]["num"];
      if (!empty($lastgetmobilevaildcodetime) && $lastgetmobilevaildcodetime<60 ) //1分钟只能取一次
      {
          return $this->responseJson(Utils::WrapResultError("你获取验证码的次数太频繁！每分钟内只能取一次!"),$request->get('jsoncallback'));
      }
      if ($getmobilevaildcodenums >= 5 ) //最多5次
      {
          return $this->responseJson(Utils::WrapResultError("你获取验证码的次数太多！每天最多只能取5次!"),$request->get('jsoncallback'));
      }
      $mobilevaildcode = rand(100000, 999999);      
      //根据邮箱找回且没有绑定手机时，发送邮件
      $id = SysSeq::GetSeqNextValue($da,"we_mobilebind_validcode","id");
      $req_date = getdate();
  		if(!empty($mobile))
      {
          $content = "验证码：".$mobilevaildcode."，2分钟内有效，仅用于".($type == "FP"?"找回密码":"绑定手机号")."。 【Wefafa】";
          $ec = new \Justsy\BaseBundle\Controller\SendSMSController();      
          $ec->setContainer($this->container);
          $ret = $ec->sendSMSAction($mobile,$content);
  				if(strpos($ret,"<errorcode>0</errorcode>")>0)
  				{
  					$da->ExecSQLs(array("delete from we_mobilebind_validcode where login_account=? and actiontype='".$type."' and req_date<date(now())",
                                "insert into we_mobilebind_validcode (id,login_account,req_date,valid_date,validcode,actiontype,mobileno) values
              (?,?,now(),date_add(now(),interval 2 minute),?,?,?)"),
  							array(
  									array((string)$account),
  									array(
  											(string)$id,
  											(string)$account,
  											(string)$mobilevaildcode,
                        (string)$type,
  											(string)$mobile
  									)
  					));
            return $this->responseJson(Utils::WrapResultOK("验证码已发送到您的手机，收到验证码后进行".($type == "FP"?"重置密码":"绑定手机号")."操作"),$request->get('jsoncallback'));
  				}
          else
          {
            return $this->responseJson(Utils::WrapResultError("验证码短信发送失败！请稍后重试"),$request->get('jsoncallback'));
  				}
  		}
  	} catch (Exception $e) {
  		$this->get('logger')->err($e);
      return $this->responseJson(Utils::WrapResultError("获取验证码错误！请稍后重试"),$request->get('jsoncallback'));
  	}
  }
  //手机端通过手机号找回密码
  public function resetpwdAction(){
  	$request = $this->getRequest();
  	$account = $request->get("account");
  	$txtvaildcode=$request->get("txtvaildcode");
  	$pwd = $request->get("txtnewpwd");
  	$pwd_im=$pwd;
  	$da = $this->get("we_data_access");
    $da_im = $this->get("we_data_access_im");
  	$re = array("returncode" => ReturnCode::$SUCCESS);
  	if(empty($account)){
      return $this->responseJson(Utils::WrapResultError("帐号不能为空"),$request->get('jsoncallback'));
  	}
  	if(empty($txtvaildcode)){
      return $this->responseJson(Utils::WrapResultError("验证码不能为空"),$request->get('jsoncallback'));
  	}
    //验证帐号及验证码
    $isEmail = Utils::validateEmail($account);
    $isMobile= Utils::validateMobile($account);
    if(!$isEmail && !$isMobile) 
    {
        return $this->responseJson(Utils::WrapResultError("帐号格式不正确，仅支持邮箱或手机帐号"),$request->get('jsoncallback'));
    }
    $u_staff = new \Justsy\BaseBundle\Management\Staff($da,$da_im,$account,$this->get('logger'),$this->container);
    $targetStaffInfo = $u_staff->getInfo();
    if (empty($targetStaffInfo))
    {
        return $this->responseJson(Utils::WrapResultError("帐号无效"),$request->get('jsoncallback'));
    }    
    $sysparam = new \Justsy\BaseBundle\DataAccess\SysParam($this->container);
    $wn_code = $sysparam->GetSysParam("mobile_active_code");
    if($txtvaildcode!=$wn_code)
    {
      $sql = "select * from we_mobilebind_validcode where login_account=? and actiontype='FP' and valid_date>now() order by valid_date desc limit 0,1";
      $ds = $da->GetData('t',$sql,array((string)$account));
      if ($txtvaildcode != $ds["t"]["rows"][0]["validcode"])
      {
          return $this->responseJson(Utils::WrapResultError("验证码无效"),$request->get('jsoncallback'));
      }
    }
  	try{
          $login_account=$targetStaffInfo['login_account'];
          $re = $u_staff->changepassword($login_account,$pwd,$this->get('security.encoder_factory'));
          return $this->responseJson($re,$request->get('jsoncallback'));
  	}
    catch (Exception $e) 
    {
      return $this->responseJson(Utils::WrapResultError("重置密码失败，请稍后重试"),$request->get('jsoncallback'));
  	}
  }
  
  public function getMillisecond($c_time)
  {
      $da = $this->get('we_data_access');
      $sql = "select CASE when FROM_UNIXTIME('{$c_time}') is null then '{$c_time}'-UNIX_TIMESTAMP()*1000 
      else ('{$c_time}'-UNIX_TIMESTAMP())*1000 end as time_stamp";
      $ds = $da->GetData('time',$sql);
      $time = $ds['time']['rows'][0]['time_stamp'];
      return $time;
  }

  private function responseJson($data,$jsopfunc=null)
  {
    $resp = new Response( empty($jsopfunc) ? json_encode($data): $jsopfunc."(".json_encode($data).")");
      $resp->headers->set('Content-Type', 'text/json');
      return $resp;
  }
}
