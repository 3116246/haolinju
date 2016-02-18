<?php

namespace WebIM\ImChatBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
    	  $eno=$this->getRequest()->query->get('eno', "");
    	  $from=$this->getRequest()->query->get('from', "");
    	  $from_pass=$this->getRequest()->query->get('p', "888888");
    	  $to=$this->getRequest()->query->get('to', "");
    	  $nick=$this->getRequest()->query->get('nick', "");
    	  $faceMapping = $this->getFaceMappingAction();
        return $this->render('WebIMImChatBundle:Default:index.html.twig', array('welcome'=>'您好','faceMapping'=>$faceMapping,'name' => $name,'from'=> $from.'/FaFaWeb'.$this->get('session')->getId(),'to'=> $to,'nick'=> $nick,'p'=> $from_pass));
    }
    //获取web端与pc端的表情名称影射
    public function getFaceMappingAction()
    {
    	  $request = $this->getRequest();
	    	try 
	      {
	    	  $thispath = $_SERVER['DOCUMENT_ROOT']."/bundles/fafawebimimchat/images/face/mapping.txt";
	    	  $result = file_get_contents($thispath);
	    	}
	      catch (\Exception $ex) 
	      {
	         $this->get('logger')->err($ex);
	         $result="{}";	
	      }
				$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($result).");" : json_encode($result));
				$response->headers->set('Content-Type', 'text/json');
				return $response;	
    }
}
