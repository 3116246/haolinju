<?php
namespace Justsy\BaseBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class HomeController extends Controller
{  
  public function micromessageAction()
  {
    return $this->render('JustsyBaseBundle:Home:home_micromessage.html.twig',array());
  }

  public function footerAction()
  {
		return $this->render('JustsyBaseBundle:Home:footer.html.twig');
  }

  public function aboutsAction()
  {
  		return $this->render('JustsyBaseBundle:Home:aboutus_mapp.html.twig',array('position' => "" ));
  }

  public function corpAction()
  {
  		return $this->render('JustsyBaseBundle:Home:corp_mapp.html.twig');
  }

  public function jobsAction()
  {
  		return $this->render('JustsyBaseBundle:Home:jobs_mapp.html.twig');
  }

  public function licenseAction()
  {
  		return $this->render('JustsyBaseBundle:Home:userlicense_mapp.html.twig');
  }

  public function contactAction()
  {
  		return $this->render('JustsyBaseBundle:Home:aboutus_mapp.html.twig' ,array('position' => "concact" ));
  }

}