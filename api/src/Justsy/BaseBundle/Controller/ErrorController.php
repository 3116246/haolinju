<?php

namespace Justsy\BaseBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\Common\Utils;

class ErrorController extends Controller
{  
  public function indexAction()
  {
  	$error = $this->get("request")->get("error");
  	$error = empty($error)?"抱歉，您所访问的页面地址有误，或者该页面不存在！":$error;
    return $this->render('JustsyBaseBundle:Error:index.html.twig', array('error' => $error));
  }
  
  public function successAction()
  {
    return $this->render('JustsyBaseBundle:Error:success.html.twig');
  }
}