<?php

namespace Justsy\AdminAppBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Common\Utils;

class HomeController extends Controller
{    
    public function indexAction()
    {
        $da = $this->get('we_data_access');
        $da_im = $this->get('we_data_access_im');
        $user = $this->get('security.context')->getToken()->getUser();
        $login_account = $user->getUserName();
        $groupMgr = new \Justsy\BaseBundle\Management\GroupMgr($da,$da_im,$this->container);
        $manager  = $groupMgr->isManager($user->eno,$login_account);
        $exist = true;
        if (empty($manager))
        {
            $sql="select 1 from mb_staff_menu where staff_id=?;";
            try
            {
                $ds = $da->GetData("table",$sql,array((string)$login_account));
                if ( $ds && $ds["table"]["recordcount"]==0)
                   $exist = false;
            }
            catch(\Exception $e)
            {            
            }
        }
        if ( $exist)
           return $this->render('JustsyAdminAppBundle:Home:index.html.twig', array());
        else
           return $this->render('JustsyAdminAppBundle:Home:error.html.twig', array());
    }
    // 取菜单
    public function getMenuAction()
    {
        $re = array("returncode" => ReturnCode::$SUCCESS);
        $request = $this->getRequest();
        $user = $this->get('security.context')->getToken()->getUser();
        $da = $this->get('we_data_access'); 
        $da_im = $this->get('we_data_access_im');
        try 
        {
            $mode = $this->container->getParameter('deploy_mode');
            //判断用户是否系统管理员
            $staffMgr = new \Justsy\BaseBundle\Management\Staff($da,$da_im,$user,$this->get("logger"),$this->container);
            $isAdmin = $staffMgr->isAdmin();
            if ( !empty($mode) && $isAdmin && ( strtolower($mode)=="e" || (strtolower($mode)=="c" && Utils::$PUBLIC_ENO==$user->eno)))
            {
                $sql = "select distinct menu_id id, parent_menu_id pId, menu_name name, 'true' open, url m_url from mb_menus
                        where exists(select 1 from mb_staff_menu b where b.menu_id=mb_menus.menu_id and b.staff_id=?
                        union select 1 from we_enterprise b where b.eno=? and b.create_staff=?
                        union select 1 from we_enterprise b where b.eno=? and position(? in b.sys_manager)>0 ) order by order_no asc";
            }
            else
            {
                $sql = "select distinct menu_id id, parent_menu_id pId, menu_name name, 'true' open, url m_url from mb_menus
                        where exists(select 1 from mb_staff_menu b where b.menu_id=mb_menus.menu_id and b.staff_id=?
                        union select 1 from we_enterprise b where b.eno=? and b.create_staff=?
                        union select 1 from we_enterprise b where b.eno=? and position(? in b.sys_manager)>0)
                        order by order_no asc";
            }
            $params = array(); 
            $params[] = (string)$user->getUserName();
            $params[] = (string)$user->eno;
            $params[] = (string)$user->getUserName();
            $params[] = (string)$user->eno;
            $params[] = (string)$user->getUserName();
            $ds = $da->GetData("menus", $sql, $params);
            $re["menus"] = $ds["menus"]["rows"];
        } 
        catch (\Exception $e) 
        {
            $re["returncode"] = ReturnCode::$SYSERROR;
            $this->get('logger')->err($e->getMessage());
        }
        $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }
}
