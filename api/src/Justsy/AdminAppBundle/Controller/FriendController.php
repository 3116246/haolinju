<?php

namespace Justsy\AdminAppBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Management\Staff;
use Justsy\BaseBundle\Common\Utils;

//好友信息管理
class FriendController extends Controller
{   
    //好友管理
    public function IndexAction()
    {
    	return $this->render("JustsyAdminAppBundle:Sys:friend.html.twig");
    }
    
    //朋友圈动态管理
    public function FriendCircleAction()
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $login_account = $user->getUserName();
        $eno = $user->eno;
        $groupMgr = new \Justsy\BaseBundle\Management\GroupMgr($this->get('we_data_access'),$this->get('we_data_access_im'),$this->container);
        $manager  = $groupMgr->isManager($eno,$login_account);
        $da = $this->get('we_data_access');
        $url = $this->container->getParameter('FILE_WEBSERVER_URL');
        $head_img = "";
        $sql = "select case when ifnull(photo_path,'')='' then '' else concat('$url',photo_path) end head_img from we_staff where login_account=?";
        try
        {       
            $ds = $da->GetData("table",$sql,array((string)$login_account));
            if ( $ds && $ds["table"]["recordcount"]>0)
              $head_img = $ds["table"]["rows"][0]["head_img"];
        }
        catch(\Exception $e)
        {
        }
        return $this->render("JustsyAdminAppBundle:Sys:friendcircle.html.twig",array("manager"=>$manager,"account"=>$user->getUserName(),"head_img"=>$head_img,"nick_name"=>$user->nick_name));
    }
    //保存应用Logo
    public function uploadPhotoAction()
	  { 
	  	 $request = $this->getRequest();
	  	 $session = $this->get('session');
	  	 $login_account = $request->get("login_account");
	     $path =    $session->get("avatar_big");
	     $fileid="";$success = true;$msg="";
	     try
	     {
		      $dm = $this->get('doctrine.odm.mongodb.document_manager');
          $fileid= $this->saveFile($path,$dm);
		      $session->remove("avatar_big");
		      //如果群组已经存在则修改fileid
		      if ( !empty($login_account))
		      {
		      	 $da = $this->get('we_data_access');
             $sql = "update we_staff set photo_path,photo_path_small,photo_path_big  where login_account=?";
             $para = array((string)$fileid,(string)$fileid,(string)$fileid,$login_account);
             try
             {
             	 $da->ExecSQL($sql,$para);
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
	     	  $msg = "上传头像失败";
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
}