<?php
namespace Justsy\BaseBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class ApplyController extends Controller
{
	  public function applyAction(request $request)
    {  
       var_dump('xy');
    	///return $this->render('JustsyBaseBundle:register:apply.html.twig', array('email' => $email,'ename'=>$ename));
    }
}