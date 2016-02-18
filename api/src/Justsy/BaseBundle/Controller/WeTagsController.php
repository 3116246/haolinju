<?php
namespace Justsy\BaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Management\UserTag;

class WeTagsController extends Controller
{
	public function getUserTagAction()
	{
		$user = $this->get('security.context')->getToken()->getUser();
    $request = $this->getRequest();
    $da = $this->get('we_data_access');
    
    $usertag=new UserTag($da,$this->get('logger'),$this->container);
    $rows=$usertag->gettag($user->getUserName());
    $response=new Response(json_encode($rows));
	  $response->headers->set('Content-Type','Application/json');
	  return $response;
	}
	public function addUserTagAction()
	{
		$user = $this->get('security.context')->getToken()->getUser();
    $request = $this->getRequest();
    $da = $this->get('we_data_access');
    $tag_name=$request->get('tag_name');
    $tag_desc=$request->get('tag_desc');
    $s='1';
    $m='';
    $tag_id='';
    $usertag=new UserTag($da,$this->get('logger'),$this->container);
    if($usertag->checknum($user->getUserName())){
	    if(!$usertag->havetag($user->getUserName(),$tag_name)){
	    	$tag_id=$usertag->addtag($user->getUserName(),$tag_name,$tag_desc);
		    if(empty($tag_id))
		    {
		    	$s='0';
		    	$m='操作失败';
		    }
		  }
		  else{
		  	$s='0';
		    $m='该标签已存在';
		  }
		}
		else{
			$s='0';
		  $m='最多可以添加五个标签';
		}
    $response=new Response(json_encode(array('s'=> $s,'m'=> $m,'tag_id'=> $tag_id)));
	  $response->headers->set('Content-Type','Application/json');
	  return $response;
	}
	public function editUserTagAction()
	{
		$user = $this->get('security.context')->getToken()->getUser();
    $request = $this->getRequest();
    $da = $this->get('we_data_access');
    $tag_name=$request->get('tag_name');
    $tag_desc=$request->get('tag_desc');
    $tag_id=$request->get('tag_id');
    $s='1';
    $m='';
    $usertag=new UserTag($da,$this->get('logger'),$this->container);
    if(!$usertag->edittag($user->getUserName(),$tag_id,$tag_name,$tag_desc))
    {
    	$s='0';
	    $m='操作失败';
    }
    $response=new Response(json_encode(array('s'=> $s,'m'=> $m)));
	  $response->headers->set('Content-Type','Application/json');
	  return $response;
	}
	public function delUserTagAction()
	{
		$user = $this->get('security.context')->getToken()->getUser();
    $request = $this->getRequest();
    $da = $this->get('we_data_access');
    $tag_id=$request->get('tag_id');
    $s='1';
    $m='';
    $usertag=new UserTag($da,$this->get('logger'),$this->container);
    if(!$usertag->deltag($tag_id))
    {
    	$s='0';
	    $m='操作失败';
    }
    $response=new Response(json_encode(array('s'=> $s,'m'=> $m)));
	  $response->headers->set('Content-Type','Application/json');
	  return $response;
	}
}
?>