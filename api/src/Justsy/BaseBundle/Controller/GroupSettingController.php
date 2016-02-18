<?php

namespace Justsy\BaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\BaseBundle\Common\Utils;

class GroupSettingController extends Controller
{
	public $group_photo_path;
  public function groupAction($network_domain)
  {
  	$DataAccess=$this->get('we_data_access');
    $da_im = $this->get('we_data_access_im');
  	$group_id    				=$this->getRequest()->get('group_id');
		$sql='select we_groups.*,we_circle.circle_name from we_groups,we_circle where we_groups.circle_id and we_groups.group_id=?';
		$dataset=$DataAccess->GetData('we_groups',$sql,array((string)$group_id));
		for($i=0;$i<count($dataset['we_groups']['rows']);$i++)
  	{
  		 $dataset['we_groups']['rows'][$i]['group_photo_path']=$this->ifPicNull($dataset['we_groups']['rows'][$i]['group_photo_path']);
  	}
		$data=array();
		if(count($dataset['we_groups']['rows'])>0)
		{
			$data=$dataset['we_groups']['rows'][0];
		}
		$data['curr_network_domain']=$network_domain;

    //»ñÈ¡im grouptype
    $sql = "select typeid, typename from im_grouptype";
    $ds = $da_im->GetData('im_grouptype',$sql);
    $grouptype = ($ds && $ds['im_grouptype']['recordcount']>0) ? $ds['im_grouptype']['rows'] : array();
    $data["grouptype"] = $grouptype;
    $data["fileurl"]=$this->container->getParameter('FILE_WEBSERVER_URL');
    
		return $this->render('JustsyBaseBundle:GroupSetting:group.html.twig',$data);
  }
  public function saveLogoAction()
  {
  	$DataAccess=$this->get('we_data_access');
    $da_im = $this->get('we_data_access_im');
    $request = $this->getRequest();
    $group_id = $request->get("groupid");
    $group_photo_path='';
  	try{
  		$session=$this->get('session');
  		$dm = $this->get('doctrine.odm.mongodb.document_manager');
	    $group_photo_path        =$session->get('avatar_middle');  
	    $group_photo_path_big    =$session->get('avatar_big');
	    $group_photo_path_small  =$session->get('avatar_small');
	    
	    $group_photo_path=Utils::saveFile($group_photo_path,$dm);
		//$group_photo_path_big=$this->saveFile($group_photo_path_big,$dm);
        unlink($group_photo_path_big);
		//$group_photo_path_small=$this->saveFile($group_photo_path_small,$dm);
        unlink($group_photo_path_small);
  	    $session->remove('avatar_middle');
	  	$session->remove('avatar_big');
	  	$session->remove('avatar_small');
			  	 
	    $session->set('group_photo_path',$group_photo_path);
	    if(!empty($group_id)){
	    	$sql='select group_id,group_photo_path,fafa_groupid,group_name from we_groups where group_id=?';
			$dataset=$DataAccess->GetData('we_groups',$sql,array((string)$group_id));
		    if($dataset!=null && count($dataset['we_groups']['rows'])>0) {
		  		$this->deleteFile($dataset['we_groups']["rows"][0]["group_photo_path"],$dm);
		    
		    	$sql="update we_groups set group_photo_path=? where group_id=? ";
		    	$para=array($group_photo_path,$group_id);
		    	$DataAccess->ExecSQL($sql,$para);
		    	//取群成员
			  	$sql="SELECT b.fafa_jid FROM we_group_staff a LEFT JOIN we_staff b ON a.login_account=b.login_account WHERE a.group_id=? AND b.fafa_jid IS NOT NULL";
			  	$para=array($group_id);
			  	$data=$DataAccess->GetData('dt',$sql,$para);
			  	//var_dump($sql,$para);
			  	if($data!=null && count($data['dt']['rows'])>0 && !empty($group_photo_path)) {
			  		//修改头像之后需要发出席消息。让各端及时修改头像
			  		$user = $this->get('security.context')->getToken()->getUser();
			  		$fafa_jid=$user->fafa_jid;
			  		$tojid=array();
			        for ($i=0; $i < count($data['dt']['rows']); $i++) { 
		        	    array_push($tojid, $data['dt']['rows'][$i]['fafa_jid']);
			        }
	        		$groupjid= $dataset['we_groups']["rows"][0]["fafa_groupid"];
	        		$groupname= $dataset['we_groups']["rows"][0]["group_name"];
		    		$message=json_encode(array('group_id'=>$group_id
		    			,'logo_path'=>$group_photo_path
		    			,'jid'=>$groupjid
		    			,'group_name'=>$groupname));
	        		//var_dump($groupjid,$group_id,$tojid,$fafa_jid);
	        		Utils::sendImMessage($fafa_jid,implode(",",$tojid),"group_info_change",$message, $this->container,"","",false,Utils::$systemmessage_code);
			  	}
		    }
	    } 
  	}
  	catch(\Exception $e){
  		$this->get('logger')->err($e);
  		$group_photo_path='';
  	}
  	$response = new Response(json_encode(array('group_photo_path'=> $group_photo_path)));
	  $response->headers->set('Content-Type', 'text/json');
	  return $response;
  }
  public function saveGroupAction($network_domain)
  {
  	try{
	  	$DataAccess=$this->get('we_data_access');
	
	    $group_id    				=$this->getRequest()->get('group_id');
		$circle_id  				=$this->getRequest()->get('circle_id');
		$circle_name				=$this->getRequest()->get('circle_name');
		$group_name					=$this->getRequest()->get('group_name');
		$group_desc					=$this->getRequest()->get('group_desc');
		//$group_photo_path		=$this->getRequest()->get('group_photo_path');
		$join_method				=$this->getRequest()->get('join_method');
		$create_date				=$this->getRequest()->get('create_date');
		$fafa_groupid				=$this->getRequest()->get('fafa_groupid');
		$group_class				=$this->getRequest()->get('classify');
			
	    $session=$this->get('session');
	    $group_photo_path        =$session->get('group_photo_path');  
	    $group_photo_path_big    =$session->get('avatar_big');
	    $group_photo_path_small  =$session->get('avatar_small');
	    
	    if(empty($group_id)==true||empty($group_name)==true) return $this->res('{"success":0}','json');
	  	//$dm=$this->get('doctrine.odm.mongodb.document_manager');
	  	/*
	  	 if(!empty($group_photo_path))
	  	 {
	  	     //$group_photo_path					=$this->saveFile($group_photo_path,$dm);
	//  	     $group_photo_path_big			=$this->saveFile($group_photo_path_big,$dm);
	          unlink($group_photo_path_big);
	//  	     $group_photo_path_small		=$this->saveFile($group_photo_path_small,$dm);
	          unlink($group_photo_path_small);
	  	     $session->remove('avatar_middle');
			  	 $session->remove('avatar_big');
			  	 $session->remove('avatar_small');
			  	 
				  $sql='select group_photo_path from we_groups where group_id=?';
				  $dataset=$DataAccess->GetData('we_groups',$sql,array((string)$group_id));
				  if($dataset['we_groups']['recordcount']>0)
				  {
				  		$this->deleteFile($dataset['we_groups']["rows"][0]["group_photo_path"],$dm);
				  }
	  	 }
	  	 */
	  	$sql='select fafa_groupid from we_groups where group_id=?';
		$dataset=$DataAccess->GetData('we_groups',$sql,array((string)$group_id));
	    if($dataset!=null && count($dataset['we_groups']['rows'])>0) {
		  	if(empty($group_photo_path)) {
		  	 	$sql='update we_groups set group_name=?,group_desc=?,group_class=?,join_method=? where group_id=?';
	  			$para=array((string)$group_name,
					  	(string)$group_desc,
					  	(string)$group_class,
					  	(string)$join_method,
					  	(string)$group_id);
		  	} else {
		  	 	$sql='update we_groups set group_name=?,group_desc=?,group_class=?,group_photo_path=?,join_method=? where group_id=?';
		  		$para=array((string)$group_name,
					  	(string)$group_desc,
						(string)$group_class,
					  	(string)$group_photo_path,
					  	(string)$join_method,
					  	(string)$group_id);
		  	}
		  	
		    $dataexec=$DataAccess->ExecSQL($sql,$para);
		  	$path = empty($group_photo_path)?"":$this->ifPicNull($group_photo_path);

		  	//取群成员
		  	$sql="SELECT b.fafa_jid FROM we_group_staff a LEFT JOIN we_staff b ON a.login_account=b.login_account WHERE a.group_id=? AND b.fafa_jid IS NOT NULL";
		  	$para=array($group_id);
		  	$data=$DataAccess->GetData('dt',$sql,$para);
		  	//var_dump($sql,$para);
		  	if($data!=null && count($data['dt']['rows'])>0) {
		  		//修改头像之后需要发出席消息。让各端及时修改头像
		  		$user = $this->get('security.context')->getToken()->getUser();
		  		$fafa_jid=$user->fafa_jid;
		  		$tojid=array();
		        for ($i=0; $i < count($data['dt']['rows']); $i++) { 
	        	    array_push($tojid, $data['dt']['rows'][$i]['fafa_jid']);
		        }
	    		$groupjid= $dataset['we_groups']["rows"][0]["fafa_groupid"];
	    		$message=json_encode(array('group_id'=>$group_id
	    			,'logo_path'=>$group_photo_path
	    			,'jid'=>$groupjid
	    			,'group_name'=>$group_name));
	    		//group_id,group_photo_path,fafa_groupid,group_name
	    		//var_dump($groupjid,$group_id,$tojid,$fafa_jid);
	    		Utils::sendImMessage($fafa_jid,implode(",",$tojid),"group_info_change",$message, $this->container,"","",false,Utils::$systemmessage_code);
		  	}

		  	return $this->res('{"success":1,"logo_path":"'.$path.'"}','json'); 	
	  	}
	  	return $this->res('{"success":1,"logo_path":""}','json'); 	
	  }
	  catch(\Exception $e)
	  {
	  	$this->get('logger')->err($e);
	  	return $this->res('{"success":0}','json');
	  }
  }

  public function checkGroupnameAction($network_domain)
  {
     $DataAccess=$this->get('we_data_access');
     $group_id=$this->getRequest()->get('group_id');
     $group_name=$this->getRequest()->get('group_name');
     $sql='select we_groups.* from we_groups,we_circle where we_groups.group_name=? and we_groups.group_id!=? and we_groups.circle_id=we_circle.circle_id and we_circle.network_domain=?';
     $dataset=$DataAccess->GetData('we_groups',$sql,array((string)$group_name,(string)$group_id,(string)$network_domain));
     if((int)$dataset['we_groups']['recordcount']>0)
       return $this->res('{"exist":1}','json');
     else
       return $this->res('{"exist":0}','json');
  }
  public function ifPicNull($pic)
  {
  	if(empty($pic))
  	{
  		$pic=$this->get('templating.helper.assets')->getUrl('bundles/fafatimewebase/images/downphoto.png');
  	}
  	else
  	{
  		$pic=$this->container->getParameter('FILE_WEBSERVER_URL').$pic;
  	}
  	return $pic;
  }
  public function saveFile($filePath,$dm)
  {
  	$doc=new \Justsy\MongoDocBundle\Document\WeDocument;
  	$doc->setName(basename($filePath));
  	$doc->setFile($filePath);
  	$dm->persist($doc);
  	$dm->flush();
  	unlink($filePath);
  	return $doc->getId();
  }
  public function deleteFile($fileId,$dm)
  {
  	$doc = $dm->getRepository('JustsyMongoDocBundle:WeDocument')->find($fileId);
  	if(!empty($doc))
  	{
  		$dm->remove($doc);
  		$dm->flush();
  	}
  	return true;
  }
    public function res($content,$type='html')
    {
    	$response=new Response($content);
    	$response->headers->set('Content-Type','text/'.$type);
    	$response->headers->set('charset', 'utf-8');
    	return $response;
    }
}
