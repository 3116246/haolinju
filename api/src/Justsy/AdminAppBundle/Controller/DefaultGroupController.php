<?php

namespace Justsy\AdminAppBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Management\Staff;
use Justsy\BaseBundle\Common\Utils;

use Justsy\BaseBundle\Controller\EmployeeCardController;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Float;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Controller\AccountController;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Management\Enterprise;

//群组信息管理
class DefaultGroupController extends Controller
{    
    public function IndexAction()
    {
    	$da = $this->get('we_data_access');
    	$da_im = $this->get('we_data_access_im');
    	$groupMgr = new \Justsy\BaseBundle\Management\GroupMgr($da,$da_im,$this->container);
  	  $member = $groupMgr->getEnoParameter("group_member_count");
  	  $minval = $member["minval"];
  	  $maxval = $member["maxval"];
  	  $manager = $this->isManager();
  	  $request = $this->getRequest();
  	  $type = $request->get("type");
  	  //$type="";
  	  if ( empty($type))
  	    return $this->render("JustsyAdminAppBundle:Sys:group.html.twig",array("min_member"=>$minval,"max_member"=>$maxval,"manager"=>$manager));
  	  else
  	    return $this->render("JustsyAdminAppBundle:Sys:group2.html.twig",array("min_member"=>$minval,"max_member"=>$maxval,"manager"=>$manager));
    }
    
    //当前用户是否系统管理员
    private function isManager()
    {
    	$da = $this->get("we_data_access");
    	$user = $this->get('security.context')->getToken()->getUser();
    	$login_account = $user->getUserName();
    	$eno = $user->eno;
    	$manager = "0";
    	$sql = "select case when position(? in sys_manager)>0 then 'manager' else 'normal' end manager from we_enterprise where eno=?;";
    	$para = array((string)$login_account,(string)$eno);
    	try
    	{
	    	$ds = $da->GetData("table",$sql,$para);
	    	if ( $ds && $ds["table"]["recordcount"]>0)
	    	  $manager = $ds["table"]["rows"][0]["manager"];
    	}
    	catch(\Exception $e)
    	{
    		$this->get("logger")->err($e->getMessage());
    	}
    	return $manager;
    }
    
    //创建默认群组
    public function creategroupAction()
    {
			$user = $this->get('security.context')->getToken()->getUser();			
	    $request = $this->getRequest();
	    $groupid = $request->get("groupid");
	    $groupname=$request->get("group_name");
	    $deptid = $request->get("deptid");
	    $allow_jid = $request->get("allow_jid");
	    $allow_del = $request->get("allow_del");
	    $remove_jid = $request->get("remove_jid");
	    $remove_del = $request->get("remove_del");	    
	    $groupdesc=$request->get("group_desc");
	    $max_number = $request->get("max_number");
	    $logo = $request->get("logo");	    	    
	    $groupMgr = new \Justsy\BaseBundle\Management\GroupMgr($this->get('we_data_access'),$this->get('we_data_access_im'),$this->container);
	    $parameter = array("user"=>$user,
	                       "groupid"=>$groupid,
	                       "logo"=>$logo,
	                       "groupname"=>$groupname,                 
	                       "groupdesc"=>$groupdesc,
	                       "max_number"=>$max_number,
	                       "deptid"=>$deptid,
	                       "allow_jid"=> empty($allow_jid) ? array() : $allow_jid,
	                       "allow_del"=> empty($allow_del) ? array() : $allow_del,
	                       "remove_jid"=>empty($remove_jid) ? array() : $remove_jid,
	                       "remove_del"=>empty($remove_del) ? array() : $remove_del
	                      );                
  	  $result = $groupMgr->editGroup($parameter);
		  $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($result).");" : json_encode($result));
	    $response->headers->set('Content-Type', 'text/json');
	    return $response;
	  }
    
    //保存应用Logo
    public function uploadLogoAction()
	  { 
	  	 $request = $this->getRequest();
	  	 $session = $this->get('session');
	  	 $groupid = $request->get("groupid");
	     $path =    $session->get("avatar_big");
	     $fileid="";$success = true;$msg="";
	     try
	     {
		      $dm = $this->get('doctrine.odm.mongodb.document_manager');
          $fileid= $this->saveFile($path,$dm);
		      $session->remove("avatar_big");
		      //如果群组已经存在则修改fileid
		      if ( !empty($groupid))
		      {
		      	 $da_im = $this->get('we_data_access_im');
             $sql = "update im_group set logo=? where groupid=?";
             $para = array((string)$fileid,(string)$groupid);
             try
             {
             	  $da_im->ExecSQL($sql,$para);
             	  //修改图标成功后发送出席
        	      $groupMgr = new \Justsy\BaseBundle\Management\GroupMgr($this->get('we_data_access'),$da_im,$this->container);
        	      $groupObj = $groupMgr->getGroupMemberJid($groupid);
        	      $to_jid = $groupObj["member_jid"];
        	      $groupname = $groupObj["groupname"];
        	      $user = $this->get('security.context')->getToken()->getUser();
            	  $nick_name = $user->nick_name;
        	      $send_jid =  $user->fafa_jid;
        	      if ( !empty($to_jid))
        	      { 
        		      $title = "group-changelogo";
        		      $message = $nick_name."修改了群(".$groupname.")头像！";
        		  	 	Utils::sendImPresence($send_jid,$to_jid,$title,$message,$this->container,"","",false,Utils::$systemmessage_code);
        	  	  }
             }
             catch(\Exception $e)
             {
             	 $this->get("logger")->err($e->getMessage());
             }
		      }
	     }
	     catch(\Exception $e)
	     {
	     	  $success = false;
	     	  $msg = "上传群图Logo失败";
	       	$this->get("logger")->err($e);
	     }
	     $result = array("success"=>$success,"msg"=>$msg,"fileid"=>$fileid);	     
		   $response = new Response(json_encode($result));
	     $response->headers->set('Content-Type', 'text/json');
	     return $response;
	  }
	  
	  //保存图片
	  private function saveFile($path, $dm)
    { 
	    $doc = new \Justsy\MongoDocBundle\Document\WeDocument();    
	    $doc->setName(basename($path));
	    $doc->setFile($path);
	    $dm->persist($doc);
	    $dm->flush();
	    unlink($path);
	    return $doc->getId();
    }
    
    //获得部门底下人员
    public function getStaffAction()
    {
        $da = $this->get('we_data_access');
	      $request = $this->getRequest();
        $deptid = $request->get("deptid");
        $account = $request->get("account");    
        $record    = $request->get("record");
        $record    = empty($record) ? 8 : $record;
        $pageindex = $request->get("pageindex");
        $pageindex = (empty($pageindex) || (int)$pageindex==0) ? 1 : $pageindex;
        $limit = " limit ".(($pageindex - 1) * $record).",".$record;
        $sql ="select login_account,fafa_jid,nick_name 
               from we_staff a where dept_id=(select dept_id from we_department where fafa_deptid=?) 
                    and not exists(select 1 from we_micro_account b where a.login_account=b.number)
                    and not exists(select 1 from we_announcer c where a.login_account=c.login_account);";
        $condition = "";$para = array((string)$deptid);
        //用户账号或昵称不为空
        if (!empty($account)){
            $account = str_replace("%","\%",$account);
            $account = str_replace("_","\_",$account);
            $account = str_replace("[","\[",$account);
            $account = str_replace("]","\]",$account);        
            if (strlen($account)>mb_strlen($account,'utf8')){
              $condition = " and nick_name like concat('%',?,'%') ";
              array_push($para,(string)$account);
            }
            else {
              $condition = " and (login_account like concat('%',?,'%') or nick_name like concat('%',?,'%')) ";
              array_push($para,(string)$account,(string)$account);
            }
        }
	    $data = array();$success = true;$msg = "";
	    try
	    {
	        $ds = $da->GetData("table",$sql,array((string)$deptid));
	    	  if ( $ds && $ds["table"]["recordcount"]>0)
	    	    $data = $ds["table"]["rows"];
	    }
	    catch(\Exception $e){
	    	$success = false;
	    	$msg = "获得部门人员失败！";
	    	$this->get("logger")->err($e->getMessage());
	    }
	    $result = array("success"=>$success,"msg"=>$msg,"list"=>$data);
		  $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($result).");" : json_encode($result));
	    $response->headers->set('Content-Type', 'text/json');
	    return $response;
    }
    
    //手机端设置群组logo
		public function setgrouplogoAction()
	  { 
	  	$request = $this->getRequest();
	  	$groupid = $request->get("groupid");
	    $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
	    $da = $this->get('we_data_access_im');
	    $photofile = "";
	    try
	    {
	    	$photofile = empty($_FILES['photofile']) ? null : $_FILES['photofile']['tmp_name'];
	    }
	    catch(\Exception $e)
	    {
	    }
	    if(empty($photofile))
	    {
	        $photofile = tempnam(sys_get_temp_dir(), "we");
	        unlink($photofile);
	        $somecontent1 = base64_decode($request->get('photodata'));
	        if ($handle = fopen($photofile, "w+")) {
	          if (!fwrite($handle, $somecontent1) == FALSE) {
	              fclose($handle);  
	          }
	        }
	    }
	    $returncode = "0000";$path="";
	    try
	    {
	      if (empty($photofile)) throw new \Exception("param is null");
	      $im = new \Imagick($photofile);
	      $im->scaleImage(120,120);
	      $im->writeImage($photofile);
	      $im->destroy();	      
	      $sql = "select logo from im_group where groupid=?;";
	      $table = $da->GetData("group",$sql,array((string)$groupid));
	      if ($table && $table["group"]["recordcount"] > 0)
	      {
	        $file = $table["group"]["rows"][0]["logo"];
	        if ( !empty($file))
	            Utils::removeFile($table["group"]["rows"][0]["logo"],$dm);
	      }
	      $fileid = "";
	      if (!empty($photofile)) 
	      {
	        $fileid = Utils::saveFile($photofile,$dm);	
	      }
	      $sql = "update im_group set logo=? where groupid=?;";
	      try
	      {
    	      $da->ExecSQL($sql,array((string)$fileid,(string)$groupid));
	      }
	      catch(\Exception $e)
	      {
	        $this->get("logger")->err($e->getMessage());
	      }
	      if (!empty($fileid))
	      {
	        $path = $this->container->getParameter('FILE_WEBSERVER_URL');
	        $path = $path.$fileid;
	      }
	      $path = $path.$fileid;
	      //发送出席
	      $groupMgr = new \Justsy\BaseBundle\Management\GroupMgr($this->get('we_data_access'),$da,$this->container);
	      $groupObj = $groupMgr->getGroupMemberJid($groupid);
	      $to_jid = $groupObj["member_jid"];
	      $groupname = $groupObj["groupname"];
	      $user = $this->get('security.context')->getToken()->getUser();
    	  $nick_name = $user->nick_name;
	      $send_jid =  $user->fafa_jid;
	      if ( !empty($to_jid))
	      { 
		      $title = "group-changelogo";
		      $message = $nick_name."修改了群(".$groupname.")头像！";
		  	 	Utils::sendImPresence($send_jid,$to_jid,$title,$message,$this->container,"","",false,Utils::$systemmessage_code);
	  	  }
	    }
	    catch (\Exception $e)
	    {
	      $returncode = "9999";
	      $this->get("logger")->err($e->getMessage());
	    }
	    $result = array("returncode"=>$returncode,"fileid"=>$fileid,"path"=>$path);
		  $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($result).");" : json_encode($result));
	    $response->headers->set('Content-Type', 'text/json');
	    return $response;
	  }
}