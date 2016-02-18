<?php

namespace Justsy\BaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

//���һЩ������action
class CommonController extends Controller
{    
  //ȡWEFAFA LOGO��û�õط��ţ��ȷ���
  public function getWefafaLogoAction()
  {
    $we_sys_param = $this->container->get('we_sys_param');
    
    $wefafalogo = $we_sys_param->GetSysParam("wefafalogo");
    
    return $this->render('JustsyBaseBundle::master_logo.html.twig', array('wefafalogo' => $wefafalogo));  
  }  
}
