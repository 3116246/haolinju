<?php

namespace Justsy\AdminAppBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Management\Staff;
use Justsy\BaseBundle\Common\Utils;

//服务号管理
class ServiceController extends Controller
{   
    //朋友圈动态管理
    public function IndexAction()
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $login_account = $user->getUserName();
        $eno = $user->eno;        
        $da = $this->get('we_data_access');
        $url = $this->container->getParameter('FILE_WEBSERVER_URL');
        $groupMgr = new \Justsy\BaseBundle\Management\GroupMgr($this->get('we_data_access'),$this->get('we_data_access_im'),$this->container);
        $manager  = $groupMgr->isManager($eno,$login_account);
        $request = $this->getRequest();
        $type = $request->get("type");
        if ( empty($type))
           return $this->render("JustsyAdminAppBundle:Sys:service.html.twig",array("manager"=>$manager));
        else
           return $this->render("JustsyAdminAppBundle:Sys:service2.html.twig",array("manager"=>$manager));
    }
}