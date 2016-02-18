<?php

namespace WebIM\ImMainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    
    public function loginpageAction()
    {
        return $this->render('WebIMImMainBundle:Default:login.html.twig');
    }
    
    public function autologinpageAction()
    {
    	  $account = $this->getRequest()->query->get("account");
    	  $pass = $this->getRequest()->query->get("pass");
    	  
    	  if($account==null || $account=="")
          return $this->render('WebIMImMainBundle:Default:login2.html.twig');
        else
        {
        	//JustsyBaseBundle_reg_getjid
        	$jid = $this->getJid($account);
          return $this->render('WebIMImMainBundle:Default:main.html.twig',array('jid'=> $jid==""? $account:$jid,'pass'=> $pass));
        }
    }    
    
    public function rosterpageAction()
    {
    	  //需要获取机构列表并输出到页面
    	  $outHtml = "";
        return $this->render('WebIMImMainBundle:Default:roster.html.twig',array('outHtml'=> $outHtml));
    }
    
    public function chatwindowAction()
    {
    	  $faceMapping = $this->getFaceMapping();
        return $this->render('WebIMImMainBundle:Default:chatwindow.html.twig',array('faceMapping'=>$faceMapping));
    }
    
    private function getJid($account)
    {
      try 
      {
      	$wefafaUrl = $this->container->getParameter("FAFA_WEFAFA_URL");
      	$wefafaUrl = $wefafaUrl."/register/jid/get/".$account;
        $re_reg_str = Utils::getUrlContent($wefafaUrl);
        return $re_reg_str;
      } 
      catch (\Exception $ex) 
      {
         $this->get('logger')->err($ex);
         //$this->hintmsg = "提示：服务器忙，请稍后再试！";
         return "";
      }
    }
    //获取web端与pc端的表情名称影射
    private function getFaceMapping()
    {
	    	try 
	      {
	    	  $thispath = $_SERVER['DOCUMENT_ROOT']."/bundles/fafawebimimchat/images/face/mapping.txt";
	    	  $result = Utils::getUrlContent($thispath);
	    	}
	      catch (\Exception $ex) 
	      {
	         $this->get('logger')->err($ex);
	         return "{}";	
	      }    	  
        return $result;	
    }    
}
