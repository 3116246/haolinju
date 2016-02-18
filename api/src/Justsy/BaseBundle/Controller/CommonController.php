<?php

namespace Justsy\BaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

//存放一些公共的action
class CommonController extends Controller
{    
  //取WEFAFA LOGO，没得地方放，先放这
  public function getWefafaLogoAction()
  {
    $we_sys_param = $this->container->get('we_sys_param');
    
    $wefafalogo = $we_sys_param->GetSysParam("wefafalogo");
    
    return $this->render('JustsyBaseBundle::master_logo.html.twig', array('wefafalogo' => $wefafalogo));  
  }  
}
