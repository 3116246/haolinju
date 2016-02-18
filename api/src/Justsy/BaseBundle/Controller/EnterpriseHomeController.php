<?php

namespace Justsy\BaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Management\MicroAccountMgr;
use Justsy\BaseBundle\Management\EnoParamManager;

class EnterpriseHomeController extends Controller {

    public $groups;
    public function indexAction($network_domain, $name) 
    {
        $user = $this->get('security.context')->getToken()->getUser();
        if ($user->state_id == '3') { // 已注销
            return $this->redirect($this->generateUrl('JustsyBaseBundle_register', array('type' => 0, 'mail' => $user->getUserName())));
        }
        //判断是否第一次进入wefafa,显示圈子默认页面
        if(empty($user->prev_login_date) || "0000-00-00 00:00:00"==$user->prev_login_date)
        {        	  
        	 	$sql = "update we_staff set prev_login_date=now() where login_account=?";
						$da = $this->get("we_data_access");
					  $da->ExecSQL($sql, array((string)$user->getUserName())); 
					  $UserProvider=new \Justsy\BaseBundle\Login\UserProvider($this->container);
					  $UserProvider->refreshUser($user);        	  
        	  return $this->redirect($this->generateUrl('JustsyBaseBundle_enterprise',array('network_domain'=>9999)));
        }
        $para=array('this' => $this, 'curr_network_domain' => $network_domain,"publish"=>true,"trend"=>true,"group_c"=> $user->IsExistsFunction("GROUP_C"),"group_s"=>  $user->IsExistsFunction("GROUP_S"));
        //if ( $network_domain != $user->edomain && !in_array($network_domain, $user->network_domains)) {
        //    $para= $this->getRole($para);
        //    return $this->redirect($this->generateUrl('JustsyBaseBundle_enterprise_home', $para));
        //}
        //企业圈子ID
        $en_circle_id = $user->get_circle_id($user->edomain);
        //取出当前圈子的群组
        $circle_id = $user->get_circle_id($network_domain);
        $this->getGroupByCircle($circle_id, $user->getUserName());
        if ($circle_id == "10000"){
        	//we广场也需要限制
        	$para["publish"]= $user->IsExistsFunction("PUBLISH_WE");
        	$para["trend"]= $user->IsExistsFunction("TREND_WE");
        	$para["view"]= $user->IsExistsFunction("TREND_VIEW_WE");
        	$para["group_c"]= $user->IsExistsFunction("GROUP_C_WE");
          $template_twig = "JustsyBaseBundle:EnterpriseHome:index_10000.html.twig";
        }
        else if ($circle_id == "9999"){
        	//人脉
        	$para["group_c"]= true; //不限制
          $template_twig = "JustsyBaseBundle:FriendCircle:index_9999.html.twig";
        }
        else if($circle_id != $en_circle_id)
        {
        	//外部圈子
        	$para["publish"]= $user->IsExistsFunction("CIRCLE_PUBLISH_TREND");
        	$para["trend"]= $user->IsExistsFunction("CIRCLE_REPLY_TREND");
        	$para["view"]= $user->IsExistsFunction("CIRCLE_VIEW_TREND");
        	$para["group_c"]= $user->IsExistsFunction("GROUP_C");
          $template_twig = "JustsyBaseBundle:EnterpriseHome:index.html.twig";          
        }
        else
        {
        	//企业
        	$para["publish"]= $user->IsExistsFunction("PUBLISH_EN");
        	$para["trend"]= $user->IsExistsFunction("EN_TREND");
        	$para["view"]= $user->IsExistsFunction("EN_CIRCLE_VIEW");
        	$para["group_c"]= $user->IsExistsFunction("GROUP_C_EN");
          $template_twig = "JustsyBaseBundle:EnterpriseHome:index.html.twig";
        }
        return $this->render($template_twig, $para);
    }

    public function wefafaregAction()
    {
        $DataAccess = $this->get('we_data_access');
        $dataset = $DataAccess->GetData("domain","select domain_name from we_public_domain order by order_num limit 0,10");
        $emials=($dataset["domain"]["rows"]);
        $huowa = array();
        for($i=0;$i<count($emials);$i++)
        {
           $huowa[]=$emials[$i]["domain_name"];
        }
        return $this->render('JustsyBaseBundle:Home:reg.html.twig',array('domain'=> json_encode($huowa)));
    }    

    public function indexpcAction($network_domain) {
        $user = $this->get('security.context')->getToken()->getUser();
        if ($user->state_id == '3') { // 已注销
            return new Response("对不起！您的帐号已被禁用！");
        }
        $template_twig = "JustsyBaseBundle:EnterpriseHome:index_pc.html.twig";
        $request = $this->getRequest();
        $showAllCircle = $request->get("showcircle");
        if ($network_domain != $user->edomain && !in_array($network_domain, $user->network_domains)) {
            return $this->redirect($this->generateUrl('JustsyBaseBundle_enterprise_home_forpc', array('showcircle' => $showAllCircle, 'network_domain' => $user->edomain)));
        }
        $para=array('this' => $this, 'curr_network_domain' => $network_domain,"publish"=>true,"trend"=>true,"group_c"=> $user->IsExistsFunction("GROUP_C"),"group_s"=>  $user->IsExistsFunction("GROUP_S"));
				//企业圈子ID
        $en_circle_id = $user->get_circle_id($user->edomain);
        //取出当前圈子的群组
        $circle_id = $user->get_circle_id($network_domain);
        $this->getGroupByCircle($circle_id, $user->getUserName());
        if ($circle_id == "10000"){
        	//we广场也需要限制
        	$para["publish"]= $user->IsExistsFunction("PUBLISH_WE");
        	$para["trend"]= $user->IsExistsFunction("TREND_WE");
        	$para["view"]= $user->IsExistsFunction("TREND_VIEW_WE");
        	$para["group_c"]= $user->IsExistsFunction("GROUP_C_WE");
          $template_twig = "JustsyBaseBundle:EnterpriseHome:index_10000.html.twig";
        }
        else if ($circle_id == "9999"){
        	//人脉
        	$para["group_c"]= true; //不限制
          //$template_twig = "JustsyBaseBundle:FriendCircle:index_9999.html.twig";
        }
        else if($circle_id != $en_circle_id)
        {
        	//外部圈子
        	$para["publish"]= $user->IsExistsFunction("CIRCLE_PUBLISH_TREND");
        	$para["trend"]= $user->IsExistsFunction("CIRCLE_REPLY_TREND");
        	$para["view"]= $user->IsExistsFunction("CIRCLE_VIEW_TREND");
        	$para["group_c"]= $user->IsExistsFunction("GROUP_C");
          //$template_twig = "JustsyBaseBundle:EnterpriseHome:index_pc.html.twig";          
        }
        else
        {
        	//企业
        	$para["publish"]= $user->IsExistsFunction("PUBLISH_EN");
        	$para["trend"]= $user->IsExistsFunction("EN_TREND");
        	$para["view"]= $user->IsExistsFunction("EN_CIRCLE_VIEW");
        	$para["group_c"]= $user->IsExistsFunction("GROUP_C_EN");
          //$template_twig = "JustsyBaseBundle:EnterpriseHome:index_pc.html.twig";
        }
        $para['showcircle']=$showAllCircle;
        $para['account']=$user->getUserName();
        $para['passWord']=$user->t_code;
        return $this->render($template_twig,$para);
    }

    public function mappAction()
    {
        return $this->render('JustsyBaseBundle:Home:appmgr.html.twig');
    }

    public function homeAction($name) 
    {
        //获取配置的start_model属性，决定加载对应模块，默认为加载mapp
        $start_model= "";//$this->container->getParameter('start_model');
        $start_model = strtolower($start_model);
        $user = $this->get('security.context')->getToken()->getUser();
        if ( !empty($start_model) && $start_model=="mapp" )
        {
            return $this->render('JustsyBaseBundle:Home:appmgr.html.twig');
        }
        else if (!empty($start_model) && $start_model=="home")
        {
            $webserver_url = $this->container->getParameter('FILE_WEBSERVER_URL');
            $da = $this->get("we_data_access");
            $da_im = $this->get("we_data_access_im");
            $manager = new \Justsy\BaseBundle\Management\Staff($da,$da_im,$user,$this->get("logger"),$this->container);
            $isAdmin =  $manager->isAdmin();
            $isAdmin=true;
            return $this->render("JustsyBaseBundle:Home:home.html.twig",array("isAdmin"=>$isAdmin,"webserver_url"=>$webserver_url,"network_domain"=>$user->edomain,"ename"=>$user->ename,"staff"=>$user->nick_name,"eno"=>$user->eno));
        }
        else
        {
            return $this->redirect($this->generateUrl('JustsyBaseBundle_enterprise', array('network_domain' => $user->edomain)));
        }
    }
    
    public function toAdvAction()
    {
        $user = $this->get('security.context')->getToken()->getUser();
        //判断企业是否已认证
        if($user->vip_level!="0")
            return $this->render("JustsyBaseBundle:EnterpriseHome:update_adv.html.twig",array('this'=>$this,'curr_network_domain' => $user->edomain));
        else
            return $this->redirect($this->generateUrl('JustsyBaseBundle_identify_auth_eno', array('curr_network_domain' => $user->edomain)));
    }

    public function getGroupByCircle($circle_id, $username) {
        $da = $this->get('we_data_access');

        $sql = "select * from (select a.group_id, a.circle_id, a.group_name ,'' applying
								from we_groups a, we_circle b, we_group_staff c
								where a.circle_id=b.circle_id 
								  and a.group_id=c.group_id
								  and a.circle_id=? 
								  and c.login_account=?  
								limit 0, 1000 union
								select a.group_id, a.circle_id, a.group_name ,'1' applying
								from we_groups a, we_circle b, we_apply c
								where a.circle_id=b.circle_id 
								  and a.group_id=c.recv_id
								  and a.circle_id=? 
								  and c.account=?
								  and c.recv_type='g'
								  and c.is_valid='1' ) b order by convert(group_name USING gbk)
						";
        $params = array();
        $params[] = (string) $circle_id;
        $params[] = (string) $username;
        $params[] = (string) $circle_id;
        $params[] = (string) $username;
        $ds = $da->GetData("we_groups", $sql, $params);

        $this->groups = $ds["we_groups"]["rows"];

        return;
    }

    //取出当前圈子当前用户所能看到的所有会话
    public function getAllConvAction($class) {
    		$trend=	$this->getRequest()->get('trend');
        if ($this->getRequest()->get('network_domain') == "9999"){
            return $this->forward("JustsyBaseBundle:FriendCircle:getAllConv", array("class" => $class,"trend"=>$trend));
        }

        $user = $this->get('security.context')->getToken()->getUser();
        $request = $this->getRequest();
        $network_domain = $request->get('network_domain');
        $endid = $request->get('endid');
        $pre450num = $request->get('pre450num');
        $pageindex = $request->get('pageindex');
        $pagesize = 45;
        $sql = "select a.conv_root_id   
from we_convers_list a 
where a.conv_id=a.conv_root_id
  and a.post_to_circle=?
  and (a.post_to_group in ('ALL', case when a.login_account=? then 'PRIVATE' else '' end)
    or exists(select 1 from we_convers_notify wcn where wcn.conv_id=a.conv_id and wcn.cc_login_account=?))
    and not exists(select 1 from we_conv_top p where p.conv_id=a.conv_id and p.timeout>now())";

        if ($class == "conv") {//所有会话，无类别区分
            $sql.=" and not exists(select 1 from we_conv_hide c where c.conv_id=a.conv_id and c.login_account=?)";
        } else if ($class == "official") {//官方的
            $sql.=" and a.conv_type_id='06'";
        } else if ($class == "ask") {//所有提问
            $sql.=" and a.conv_type_id='01'";
        } else if ($class == "together") {//所有活动
            $sql.=" and a.conv_type_id='02'";
            ;
        } else if ($class == "vote") {//所有投票
            $sql.=" and a.conv_type_id='03'";
        } else if ($class == "trend") {
            $sql.=" and (a.conv_type_id='00' or a.conv_type_id='05')";
        }
        $params = array();
        $params[] = (string) $user->get_circle_id($network_domain);
        $params[] = (string) $user->getUserName();
        $params[] = (string) $user->getUserName();
        if ($class == 'conv') {
            $params[] = (string) $user->getUserName();
        }

        if ($pre450num) {
            $sql = "select count(*) c from ($sql limit 0, 450) as _ttt_";
            $da = $this->get('we_data_access');
            $ds = $da->GetData("we_convers_list", $sql, $params);

            $re = array("pre450num" => $ds["we_convers_list"]["rows"][0]["c"]);
            $response = new Response(json_encode($re));
            $response->headers->set('Content-Type', 'text/json');
            return $response;
        }

        $sql .= " order by (0+a.conv_id) desc";
        if ($pageindex) {
            
        } else {
            $pageindex = 1;
        }
        $pagestart = ($pageindex - 1) * $pagesize;
        $sql .= " limit $pagestart, 100 ";

        $sql = " select * from ($sql) as _ttt_ where 1=1 ";
        if ($endid) {
            $sql .= " and (0+conv_root_id)<? ";
            $params[] = (float) $endid;
        }

        $sql .= " limit 0, 15 ";

        $da = $this->get('we_data_access');
        $ds = $da->GetData("we_convers_list", $sql, $params);

        //生成html返回
        $conv_root_ids = array_map(function ($row) {
                    return $row["conv_root_id"];
                }, $ds["we_convers_list"]["rows"]);

        if ($pageindex == 1 && empty($endid) && count($conv_root_ids) > 0) {
            //更新用户最后读的信息ID
            $conv = new \Justsy\BaseBundle\Business\Conv();
            $conv->updateLastReadID_Circle($da, $user, $user->get_circle_id($network_domain), $conv_root_ids[0]);
        }

        return $this->forward("JustsyBaseBundle:CDisplayArea:getConv", array("conv_root_ids" => $conv_root_ids,"trend"=>$trend));
    }

    //PC端取出当前圈子当前用户所能看到的所有会话
    public function getAllConvPcAction($class) {
    		$trend=	$this->getRequest()->get('trend');
        if ($this->getRequest()->get('network_domain') == "9999") 
        {
            return $this->forward("JustsyBaseBundle:FriendCircle:getAllConvPc", array("class" => $class));
        }
        $user = $this->get('security.context')->getToken()->getUser();
        $request = $this->getRequest();
        $network_domain = $request->get('network_domain');
        $endid = $request->get('endid');
        $pre450num = $request->get('pre450num');
        $pageindex = $request->get('pageindex');
        $pagesize = 45;
        $sql = "select a.conv_root_id   
from we_convers_list a 
where a.conv_id=a.conv_root_id
  and a.post_to_circle=?
  and (a.post_to_group in ('ALL', case when a.login_account=? then 'PRIVATE' else '' end)
    or exists(select 1 from we_convers_notify wcn where wcn.conv_id=a.conv_id and wcn.cc_login_account=?))
    and not exists(select 1 from we_conv_top p where p.conv_id=a.conv_id and p.timeout>now())";

        if ($class == "conv") {//所有会话，无类别区分
            $sql.=" and not exists(select 1 from we_conv_hide c where c.conv_id=a.conv_id and c.login_account=?)";
        } else if ($class == "official") {//官方的
            $sql.=" and a.conv_type_id='06'";
        } else if ($class == "ask") {//所有提问
            $sql.=" and a.conv_type_id='01'";
        } else if ($class == "together") {//所有活动
            $sql.=" and a.conv_type_id='02'";
            ;
        } else if ($class == "vote") {//所有投票
            $sql.=" and a.conv_type_id='03'";
        } else if ($class == "trend") {
            $sql.=" and (a.conv_type_id='00' or a.conv_type_id='05')";
        }
        $params = array();
        $params[] = (string) $user->get_circle_id($network_domain);
        $params[] = (string) $user->getUserName();
        $params[] = (string) $user->getUserName();
        if ($class == 'conv') {
            $params[] = (string) $user->getUserName();
        }

        if ($pre450num) {
            $sql = "select count(*) c from ($sql limit 0, 450) as _ttt_";
            $da = $this->get('we_data_access');
            $ds = $da->GetData("we_convers_list", $sql, $params);

            $re = array("pre450num" => $ds["we_convers_list"]["rows"][0]["c"]);
            $response = new Response(json_encode($re));
            $response->headers->set('Content-Type', 'text/json');
            return $response;
        }

        $sql .= " order by (0+a.conv_id) desc";
        if ($pageindex) {
            
        } else {
            $pageindex = 1;
        }
        $pagestart = ($pageindex - 1) * $pagesize;
        $sql .= " limit $pagestart, 100 ";

        $sql = " select * from ($sql) as _ttt_ where 1=1 ";
        if ($endid) {
            $sql .= " and (0+conv_root_id)<? ";
            $params[] = (float) $endid;
        }

        $sql .= " limit 0, 15 ";

        $da = $this->get('we_data_access');
        $ds = $da->GetData("we_convers_list", $sql, $params);

        //生成html返回
        $conv_root_ids = array_map(function ($row) {
                    return $row["conv_root_id"];
                }, $ds["we_convers_list"]["rows"]);

        if ($pageindex == 1 && empty($endid) && count($conv_root_ids) > 0) {
            //更新用户最后读的信息ID
            $conv = new \Justsy\BaseBundle\Business\Conv();
            $conv->updateLastReadID_Circle($da, $user, $user->get_circle_id($network_domain), $conv_root_ids[0]);
        }

        return $this->forward("JustsyBaseBundle:CDisplayArea:getConvPc", array("conv_root_ids" => $conv_root_ids,'trend'=>$trend));
    }

    //取出当前圈子当前用户所能看到的所有未读会话
    public function getAllConvUnreadAction() {
        if ($this->getRequest()->get('network_domain') == "9999") 
        {
            return $this->forward("JustsyBaseBundle:FriendCircle:getAllConvUnread", array());
        }
        $user = $this->get('security.context')->getToken()->getUser();
        $request = $this->getRequest();

        $network_domain = $request->get('network_domain');
        $maxid = $request->get('maxid');
        $onlycount = $request->get('onlycount');
        $class = $request->get('class');

        $sql = "select a.conv_root_id 
from we_convers_list a
where a.conv_id=a.conv_root_id
  and a.post_to_circle=?
  and (a.post_to_group in ('ALL', case when a.login_account=? then 'PRIVATE' else '' end)
    or exists(select 1 from we_convers_notify wcn where wcn.conv_id=a.conv_id and wcn.cc_login_account=?))
  and (0+a.conv_root_id)>? and 0<>?";

        if ($class == "conv") {//所有会话，无类别区分
            $sql.=" and not exists(select 1 from we_conv_hide b where b.conv_id=a.conv_id and b.login_account=?)";
        } else if ($class == "official") {//官方的
            $sql.=" and a.conv_type_id='06'";
        } else if ($class == "ask") {//提问
            $sql.=" and a.conv_type_id='01'";
        } else if ($class == "together") {//活动
            $sql.=" and a.conv_type_id='02'";
            ;
        } else if ($class == "vote") {//投票
            $sql.=" and a.conv_type_id='03'";
        } else if ($class == "trend") {//动态
            $sql.=" and (a.conv_type_id='00' or a.conv_type_id='05')";
        }
        $params = array();
        $params[] = (string) $user->get_circle_id($network_domain);
        $params[] = (string) $user->getUserName();
        $params[] = (string) $user->getUserName();
        $params[] = (float) $maxid;
        $params[] = (float) $maxid;
        if ($class == 'conv') {
            $params[] = (string) $user->getUserName();
        }

        if ($onlycount) {
            $sql .= " and a.login_account<>? ";
            $params[] = (string) $user->getUserName();

            $sql = "select count(*) c from ($sql) as _ttt_";
            $da = $this->get('we_data_access');
            $ds = $da->GetData("we_convers_list", $sql, $params);

            $re = array("unreadcount" => $ds["we_convers_list"]["rows"][0]["c"]);
            $response = new Response(json_encode($re));
            $response->headers->set('Content-Type', 'text/json');
            return $response;
        }

        $sql .= " order by (0+a.conv_id) desc";

        $da = $this->get('we_data_access');
        $ds = $da->GetData("we_convers_list", $sql, $params);

        //生成html返回
        $conv_root_ids = array_map(function ($row) {
                    return $row["conv_root_id"];
                }, $ds["we_convers_list"]["rows"]);

        if (count($conv_root_ids) > 0) {
            //更新用户最后读的信息ID
            $conv = new \Justsy\BaseBundle\Business\Conv();
            $conv->updateLastReadID_Circle($da, $user, $user->get_circle_id($network_domain), $conv_root_ids[0]);
        }
        $trend = $request->get('trend');
        return $this->forward("JustsyBaseBundle:CDisplayArea:getConv", array("conv_root_ids" => $conv_root_ids,'trend'=>$trend));
    }
    
    public function getAllConvUnreadPcAction() {
        if ($this->getRequest()->get('network_domain') == "9999") 
        {
            return $this->forward("JustsyBaseBundle:FriendCircle:getAllConvUnreadPc", array());
        }
        $user = $this->get('security.context')->getToken()->getUser();
        $request = $this->getRequest();

        $network_domain = $request->get('network_domain');
        $maxid = $request->get('maxid');
        $onlycount = $request->get('onlycount');
        $class = $request->get('class');

        $sql = "select a.conv_root_id 
from we_convers_list a
where a.conv_id=a.conv_root_id
  and a.post_to_circle=?
  and (a.post_to_group in ('ALL', case when a.login_account=? then 'PRIVATE' else '' end)
    or exists(select 1 from we_convers_notify wcn where wcn.conv_id=a.conv_id and wcn.cc_login_account=?))
  and (0+a.conv_root_id)>? and 0<>?";

        if ($class == "conv") {//所有会话，无类别区分
            $sql.=" and not exists(select 1 from we_conv_hide b where b.conv_id=a.conv_id and b.login_account=?)";
        } else if ($class == "official") {//官方的
            $sql.=" and a.conv_type_id='06'";
        } else if ($class == "ask") {//提问
            $sql.=" and a.conv_type_id='01'";
        } else if ($class == "together") {//活动
            $sql.=" and a.conv_type_id='02'";
            ;
        } else if ($class == "vote") {//投票
            $sql.=" and a.conv_type_id='03'";
        } else if ($class == "trend") {//动态
            $sql.=" and (a.conv_type_id='00' or a.conv_type_id='05')";
        }
        $params = array();
        $params[] = (string) $user->get_circle_id($network_domain);
        $params[] = (string) $user->getUserName();
        $params[] = (string) $user->getUserName();
        $params[] = (float) $maxid;
        $params[] = (float) $maxid;
        if ($class == 'conv') {
            $params[] = (string) $user->getUserName();
        }

        if ($onlycount) {
            $sql .= " and a.login_account<>? ";
            $params[] = (string) $user->getUserName();

            $sql = "select count(*) c from ($sql) as _ttt_";
            $da = $this->get('we_data_access');
            $ds = $da->GetData("we_convers_list", $sql, $params);

            $re = array("unreadcount" => $ds["we_convers_list"]["rows"][0]["c"]);
            $response = new Response(json_encode($re));
            $response->headers->set('Content-Type', 'text/json');
            return $response;
        }

        $sql .= " order by (0+a.conv_id) desc";

        $da = $this->get('we_data_access');
        $ds = $da->GetData("we_convers_list", $sql, $params);

        //生成html返回
        $conv_root_ids = array_map(function ($row) {
                    return $row["conv_root_id"];
                }, $ds["we_convers_list"]["rows"]);

        if (count($conv_root_ids) > 0) {
            //更新用户最后读的信息ID
            $conv = new \Justsy\BaseBundle\Business\Conv();
            $conv->updateLastReadID_Circle($da, $user, $user->get_circle_id($network_domain), $conv_root_ids[0]);
        }

        return $this->forward("JustsyBaseBundle:CDisplayArea:getConvPc", array("conv_root_ids" => $conv_root_ids));
    }

    //取出当前圈子当前用户关注的所有会话
    public function getAttenConvAction() {
        $user = $this->get('security.context')->getToken()->getUser();
        $request = $this->getRequest();

        $network_domain = $request->get('network_domain');
        $endid = $request->get('endid');
        $pre450num = $request->get('pre450num');
        $pageindex = $request->get('pageindex');
        $pagesize = 45;

        $sql = "select a.conv_root_id 
from we_convers_list a, we_staff_atten b
where a.conv_id=a.conv_root_id
  and a.login_account=b.atten_id 
  and b.atten_type='01'
  and a.post_to_group = 'ALL'
  and a.post_to_circle=?
  and b.login_account=?";
        $params = array();
        $params[] = (string) $user->get_circle_id($network_domain);
        $params[] = (string) $user->getUserName();

        if ($pre450num) {
            $sql = "select count(*) c from ($sql limit 0, 450) as _ttt_";
            $da = $this->get('we_data_access');
            $ds = $da->GetData("we_convers_list", $sql, $params);

            $re = array("pre450num" => $ds["we_convers_list"]["rows"][0]["c"]);
            $response = new Response(json_encode($re));
            $response->headers->set('Content-Type', 'text/json');
            return $response;
        }

        $sql .= " order by (0+a.conv_id) desc";
        if ($pageindex) {
            
        } else {
            $pageindex = 1;
        }
        $pagestart = ($pageindex - 1) * $pagesize;
        $sql .= " limit $pagestart, 100 ";

        $sql = " select * from ($sql) as _ttt_ where 1=1 ";
        if ($endid) {
            $sql .= " and (0+conv_root_id)<? ";
            $params[] = (float) $endid;
        }

        $sql .= " limit 0, 15 ";

        $da = $this->get('we_data_access');
        $ds = $da->GetData("we_convers_list", $sql, $params);

        //生成html返回
        $conv_root_ids = array_map(function ($row) {
                    return $row["conv_root_id"];
                }, $ds["we_convers_list"]["rows"]);
        $trend = $request->get('trend');
        return $this->forward("JustsyBaseBundle:CDisplayArea:getConv", array("conv_root_ids" => $conv_root_ids,'trend'=>$trend));
    }

    //取出当前圈子当前用户关注的所有未读会话
    public function getAttenConvUnreadAction() {
        $user = $this->get('security.context')->getToken()->getUser();
        $request = $this->getRequest();

        $network_domain = $request->get('network_domain');
        $maxid = $request->get('maxid');
        $onlycount = $request->get('onlycount');

        $sql = "select a.conv_root_id 
from we_convers_list a, we_staff_atten b
where a.conv_id=a.conv_root_id
  and a.login_account=b.atten_id 
  and b.atten_type='01'
  and a.post_to_group='ALL'
  and a.post_to_circle=?
  and b.login_account=?
  and (0+a.conv_root_id)>? and 0<>?";
        $params = array();
        $params[] = (string) $user->get_circle_id($network_domain);
        $params[] = (string) $user->getUserName();
        $params[] = (float) $maxid;
        $params[] = (float) $maxid;

        if ($onlycount) {
            $sql .= " and a.login_account<>? ";
            $params[] = (string) $user->getUserName();

            $sql = "select count(*) c from ($sql) as _ttt_";
            $da = $this->get('we_data_access');
            $ds = $da->GetData("we_convers_list", $sql, $params);

            $re = array("unreadcount" => $ds["we_convers_list"]["rows"][0]["c"]);
            $response = new Response(json_encode($re));
            $response->headers->set('Content-Type', 'text/json');
            return $response;
        }

        $sql .= " order by (0+a.conv_id) desc";

        $da = $this->get('we_data_access');
        $ds = $da->GetData("we_convers_list", $sql, $params);

        //生成html返回
        $conv_root_ids = array_map(function ($row) {
                    return $row["conv_root_id"];
                }, $ds["we_convers_list"]["rows"]);
        $trend = $request->get('trend');
        return $this->forward("JustsyBaseBundle:CDisplayArea:getConv", array("conv_root_ids" => $conv_root_ids,'trend'=> $trend));
    }

    //取出当前圈子当前用户所发布的所有会话
    public function getPublishConvAction() {
        $user = $this->get('security.context')->getToken()->getUser();
        $request = $this->getRequest();

        $network_domain = $request->get('network_domain');
        $endid = $request->get('endid');
        $pre450num = $request->get('pre450num');
        $pageindex = $request->get('pageindex');
        $pagesize = 45;

        $sql = "select a.conv_root_id 
from we_convers_list a
where a.conv_id=a.conv_root_id
  and a.post_to_circle=?
  and a.login_account=?";
        $params = array();
        $params[] = (string) $user->get_circle_id($network_domain);
        $params[] = (string) $user->getUserName();

        if ($pre450num) {
            $sql = "select count(*) c from ($sql limit 0, 450) as _ttt_";
            $da = $this->get('we_data_access');
            $ds = $da->GetData("we_convers_list", $sql, $params);

            $re = array("pre450num" => $ds["we_convers_list"]["rows"][0]["c"]);
            $response = new Response(json_encode($re));
            $response->headers->set('Content-Type', 'text/json');
            return $response;
        }

        $sql .= " order by (0+a.conv_id) desc";
        if ($pageindex) {
            
        } else {
            $pageindex = 1;
        }
        $pagestart = ($pageindex - 1) * $pagesize;
        $sql .= " limit $pagestart, 100 ";

        $sql = " select * from ($sql) as _ttt_ where 1=1 ";
        if ($endid) {
            $sql .= " and (0+conv_root_id)<? ";
            $params[] = (float) $endid;
        }

        $sql .= " limit 0, 15 ";

        $da = $this->get('we_data_access');
        $ds = $da->GetData("we_convers_list", $sql, $params);

        $conv_root_ids = array_map(function ($row) {
                    return $row["conv_root_id"];
                }, $ds["we_convers_list"]["rows"]);

        //调用会话模板，生成html返回
        return $this->forward("JustsyBaseBundle:CDisplayArea:getConv", array("conv_root_ids" => $conv_root_ids,'trend'=>true));
    }

    //取出当前圈子当前用户所发布的所有未读会话
    public function getPublishConvUnreadAction() {
        $user = $this->get('security.context')->getToken()->getUser();
        $request = $this->getRequest();

        $network_domain = $request->get('network_domain');
        $maxid = $request->get('maxid');
        $onlycount = $request->get('onlycount');

        $sql = "select a.conv_root_id 
from we_convers_list a
where a.conv_id=a.conv_root_id
  and a.post_to_circle=?
  and a.login_account=?
  and (0+a.conv_root_id)>? and 0<>?";
        $params = array();
        $params[] = (string) $user->get_circle_id($network_domain);
        $params[] = (string) $user->getUserName();
        $params[] = (float) $maxid;
        $params[] = (float) $maxid;

        if ($onlycount) {
            $sql .= " and a.login_account<>? ";
            $params[] = (string) $user->getUserName();

            $sql = "select count(*) c from ($sql) as _ttt_";
            $da = $this->get('we_data_access');
            $ds = $da->GetData("we_convers_list", $sql, $params);

            $re = array("unreadcount" => $ds["we_convers_list"]["rows"][0]["c"]);
            $response = new Response(json_encode($re));
            $response->headers->set('Content-Type', 'text/json');
            return $response;
        }

        $sql .= " order by (0+a.conv_id) desc";

        $da = $this->get('we_data_access');
        $ds = $da->GetData("we_convers_list", $sql, $params);

        $conv_root_ids = array_map(function ($row) {
                    return $row["conv_root_id"];
                }, $ds["we_convers_list"]["rows"]);

        //调用会话模板，生成html返回
        return $this->forward("JustsyBaseBundle:CDisplayArea:getConv", array("conv_root_ids" => $conv_root_ids,'trend'=> $trend));
    }

    public function getAtMeNumAction() {
        $user = $this->get('security.context')->getToken()->getUser();

        $sql = "select b.post_to_circle circle_id, count(*) num
from we_notify a, we_convers_list b
where a.msg_id=b.conv_id
  and a.notify_type='03'
  and a.notify_staff=?
group by b.post_to_circle ";
        $params = array();
        $params[] = (string) $user->getUserName();

        $da = $this->get('we_data_access');
        $ds = $da->GetData("we_notify", $sql, $params);

        $re = $ds["we_notify"]["rows"];
        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }

    //未读数量
    public function getReplyMeNumAction() {
        $user = $this->get('security.context')->getToken()->getUser();

        $sql = "select b.post_to_circle circle_id, count(*) num
from we_notify a, we_convers_list b
where a.msg_id=b.conv_id
  and a.notify_type='04'
  and a.notify_staff=?
group by b.post_to_circle ";
        $params = array();
        $params[] = (string) $user->getUserName();

        $da = $this->get('we_data_access');
        $ds = $da->GetData("we_notify", $sql, $params);

        $re = $ds["we_notify"]["rows"];
        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }

    public function getUnreadCircleConversNum($username,$filter) {
        $da = $this->get('we_data_access');

        $sql = "select b.circle_id, wc.circle_name, count(*) num 
from we_convers_list a, we_circle_staff b, we_circle wc
where a.conv_id=a.conv_root_id  
  and b.circle_id=wc.circle_id
  and b.circle_id = a.post_to_circle and b.login_account=?
  and (a.post_to_group in ('ALL', case when a.login_account=b.login_account then 'PRIVATE' else '' end)
    or exists(select 1 from we_convers_notify wcn where wcn.conv_id=a.conv_id and wcn.cc_login_account=b.login_account))
  and (0+a.conv_id) > (0+ifnull(b.last_read_id, 0))
  and a.login_account<>? and a.post_to_circle!='9999'
  and 1 = (case when a.post_to_circle='10000' then (case when a.login_account in ('service@fafatime.com', 'pm@fafatime.com', 'corp@fafatime.com') then 1 else 0 end) else 1 end) 
  and not exists(select 1 from we_conv_top p where p.conv_id=a.conv_id and p.timeout>now()) ";
    if(!empty($filter)){
        $sql.=" and a.conv_type_id <>'{$filter}' ";
    }
    $sql .=" and a.conv_type_id<>'99' ";
    //从im库中查询好友
    $staffmgr = new \Justsy\BaseBundle\Management\Staff($da,$this->get('we_data_access_im'),$username,$this->get("logger"),$this->container);
    $getfriendList = $staffmgr->getFriendLoginAccountList("1");
    $roster_sql = "";
    if($getfriendList && count($getfriendList)>0)
    {
        $roster_sql .= " and a.login_account in ('".implode("','",$getfriendList)."')";
    }
    else
    {
        $roster_sql .= "  and a.login_account=''";
    }
    $sql_9999 = " union select '9999' circle_id,(select circle_name from we_circle where circle_id='9999') circle_name,count(1) num from we_convers_list a,
      (select ifnull(min(last_read_id),0) last_read_id from we_circle_staff cs where cs.login_account=? and circle_id='9999') cl  where a.post_to_circle='9999' and a.login_account<>?    
      ".$roster_sql." and (0+a.conv_id)>cl.last_read_id"; 
    if(!empty($filter)){
        $sql_9999.=" and a.conv_type_id <>'{$filter}' ";
    }
    $sql_9999 .= " and a.conv_type_id<>'99'";
    $sql .= $sql_9999;
    $params = array();
    $params[] = (string) $username;
    $params[] = (string) $username;
    $params[] = (string) $username;
    $params[] = (string) $username;
        
    $ds = $da->GetData("we_convers_list", $sql, $params);

        return $ds;
    }

    public function getUnreadCircleConversNumAction() {
        $user = $this->get('security.context')->getToken()->getUser();
        $request = $this->get("request");
        $filter = $request->get('filter');
        $ds = $this->getUnreadCircleConversNum($user->getUserName(),$filter);

        $re= $ds["we_convers_list"]["rows"];
        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }

    //PC端取未读数
    public function getUnreadCircleConversNumByUserAction() {
        $request = $this->get("request");        
        $username = $request->get('username');
        $filter = $request->get('filter');
        $ds = $this->getUnreadCircleConversNum($username,$filter);
        
        $re = $ds["we_convers_list"]["rows"];
        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }

    public function getUnreadGroupConversNum($username,$filter){
    $sql = "select b.group_id, wc.group_name, count(*) num 
    from we_convers_list a, we_group_staff b, we_groups wc
    where a.conv_id=a.conv_root_id  
        and b.group_id=wc.group_id
        and a.post_to_group=b.group_id
        and b.login_account=?
        and (0+a.conv_id) > (0+ifnull(b.last_read_id, 0))
        and a.login_account<>?
        and not exists(select 1 from we_conv_top p where p.conv_id=a.conv_id and p.timeout>now())";
        if(!empty($filter)){
            $sql.=" and a.conv_type_id <>'{$filter}' ";
        }
    $sql.="group by b.group_id, wc.group_name";
        $params = array();
        $params[] = (string) $username;
        $params[] = (string) $username;

        $da = $this->get('we_data_access');
        $ds = $da->GetData("we_convers_list", $sql, $params);

        return $ds;
    }

    public function getUnreadGroupConversNumAction(){
        $user = $this->get('security.context')->getToken()->getUser();
        $request = $this->get("request");
        $filter = $request->get('filter');
        $ds = $this->getUnreadGroupConversNum($user->getUserName(),$filter);
        $re= $ds["we_convers_list"]["rows"];
        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json'); 
        return $response;
    }

    //pc获取群组动态未读数
    public function getUnreadGroupConversNumByUserAction(){
        $request = $this->get("request");
        $username = $request->get('username');
        $filter = $request->get('filter');
        $ds = $this->getUnreadGroupConversNum($username,$filter);
        $re = $ds["we_convers_list"]["rows"];
        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }


    //查看会话详细信息
    public function getOneConvAction($conv_root_id) {
        $user = $this->get('security.context')->getToken()->getUser();

        $sqls = array();
        $all_params = array();

        //取出这个会话所在圈子
        $sql = "select b.network_domain, a.reply_num
from we_convers_list a, we_circle b
where a.post_to_circle=b.circle_id
  and a.conv_id=?";
        $params = array();
        $params[] = (string) $conv_root_id;

        $sqls[] = $sql;
        $all_params[] = $params;

        //判断是否有权限查看
        $sql = "select count(*) c
from we_convers_list a
where a.conv_id=?
  and exists(select 1 from dual where a.login_account=? 
       union select 1 from we_staff_atten wsa where wsa.login_account=? and wsa.atten_type='01' and wsa.atten_id=a.login_account and a.post_to_circle='9999'
       union select 1 from we_circle_staff b 
						 where b.circle_id=a.post_to_circle 
               and b.login_account=?
               and (a.post_to_group in (select 'ALL' from dual 
                                       union select case when a.login_account=b.login_account then 'PRIVATE' else '' end from dual
                                       union select c.group_id from we_group_staff c where c.login_account=b.login_account)
                 or exists(select 1 from we_convers_notify wcn where wcn.conv_id=a.conv_id and wcn.cc_login_account=b.login_account)))";
        $params = array();
        $params[] = (string) $conv_root_id;
        $params[] = (string) $user->getUserName();
        $params[] = (string) $user->getUserName();
        $params[] = (string) $user->getUserName();

        $sqls[] = $sql;
        $all_params[] = $params;

        $da = $this->get('we_data_access');
        $ds = $da->GetDatas(array("network_domain", "CanView"), $sqls, $all_params);

        $network_domain = $user->edomain;
        $reply_num = 0;
        if ($ds["network_domain"]["recordcount"] > 0) {
            $network_domain = $ds["network_domain"]["rows"][0]["network_domain"];
            $reply_num = $ds["network_domain"]["rows"][0]["reply_num"];
        }

        //删除评论我的通知
        $da->ExecSQL("delete from we_notify 
where notify_type='04' and notify_staff=? 
  and exists(select 1 from we_convers_list where we_convers_list.conv_id=we_notify.msg_id and we_convers_list.conv_root_id=?)", array((string) $user->getUserName(), (string) $conv_root_id));
  			//删除提到我的通知
		$da->ExecSQL("delete from we_notify 
where notify_type='03' and notify_staff=? and msg_id=? 
  and exists(select 1 from we_convers_list where we_convers_list.conv_id=we_notify.msg_id and we_convers_list.post_to_circle=?)", 
		  array((string)$user->getUserName(),$conv_root_id, (string)($user->get_circle_id($network_domain))));
      //获取是否可以评论、转发这条消息
	    $trend = $user->IsFunctionTrend($network_domain);
        return $this->render('JustsyBaseBundle:EnterpriseHome:conv.html.twig', array('this' => $this, 'curr_network_domain' => $network_domain, 'CanView' => $ds["CanView"]["rows"][0]["c"], 'conv_root_id' => $conv_root_id,'trend'=> $trend));
    }

    //查看会话转发列表
    public function getOneConvCopyListAction($conv_root_id) {
        $user = $this->get('security.context')->getToken()->getUser();

        $sqls = array();
        $all_params = array();

        //取出这个会话所在圈子
        $sql = "select b.network_domain
from we_convers_list a, we_circle b
where a.post_to_circle=b.circle_id
  and a.conv_id=?";
        $params = array();
        $params[] = (string) $conv_root_id;

        $sqls[] = $sql;
        $all_params[] = $params;

        //判断是否有权限查看
        $sql = "select count(*) c
from we_convers_list a
where a.conv_id=?
  and exists(select 1 from dual where a.login_account=? 
       union select 1 from we_staff_atten wsa where wsa.login_account=? and wsa.atten_type='01' and wsa.atten_id=a.login_account and a.post_to_circle='9999'
       union select 1 from we_circle_staff b 
						 where b.circle_id=a.post_to_circle 
               and b.login_account=?
               and (a.post_to_group in (select 'ALL' from dual 
                                       union select case when a.login_account=b.login_account then 'PRIVATE' else '' end from dual
                                       union select c.group_id from we_group_staff c where c.login_account=b.login_account)
                 or exists(select 1 from we_convers_notify wcn where wcn.conv_id=a.conv_id and wcn.cc_login_account=b.login_account)))";
        $params = array();
        $params[] = (string) $conv_root_id;
        $params[] = (string) $user->getUserName();
        $params[] = (string) $user->getUserName();
        $params[] = (string) $user->getUserName();

        $sqls[] = $sql;
        $all_params[] = $params;

        $da = $this->get('we_data_access');
        $ds = $da->GetDatas(array("network_domain", "CanView"), $sqls, $all_params);

        $network_domain = $user->edomain;
        if ($ds["network_domain"]["recordcount"] > 0) {
            $network_domain = $ds["network_domain"]["rows"][0]["network_domain"];
        }

        return $this->render('JustsyBaseBundle:EnterpriseHome:conv_copylist.html.twig', array('this' => $this, 'curr_network_domain' => $network_domain, 'CanView' => $ds["CanView"]["rows"][0]["c"], 'conv_root_id' => $conv_root_id));
    }

    //公共圈子
    public function circle10000Action() {
        $DataAccess = $this->get('we_data_access');
        $sql = array(
            'select circle_desc from we_circle where circle_id=?', //还需添加常见问题列表
            'select a.login_account,a.nick_name,a.photo_path from we_staff a  where a.login_account=? or a.login_account=? or a.login_account=?',
            'select atten_id,label_name from we_convers_label where login_account=?',
        );
        $sql_tables = array('we_circle', 'we_staff', 'we_convers_label');
        $para = array(
            array('10000'),
            array('corp@fafatime.com', 'pm@fafatime.com', 'service@fafatime.com'),
            array('pm@fafatime.com')
        );
        $dataset = $DataAccess->GetDatas($sql_tables, $sql, $para);
        for ($i = 0; $i < $dataset['we_staff']['recordcount']; $i++) {
            $dataset['we_staff']['rows'][$i]['photo_path'] = $this->ifPicNull($dataset['we_staff']['rows'][$i]['photo_path']);
        }
        $data = array(
            'we_circle' => $dataset['we_circle']['rows'][0]['circle_desc'],
            'we_staff' => $dataset['we_staff']['rows'],
            'we_convers_label' => $dataset['we_convers_label']['rows']
        );
        //热点问题
        $sql = "select id,faq_title from faq_content where is_hover='1' order by order_num";
        $dataset = $DataAccess->GetData('faq_content', $sql);
        if ($dataset && $dataset['faq_content']['recordcount'] > 0) {
            $data['faq_title'] = $dataset['faq_content']['rows'];
        }
        $data['trend']=$this->getRequest()->get("trend");
        return $this->render('JustsyBaseBundle:EnterpriseHome:index_10000_right.html.twig', $data);
    }
    //人脉圈子
    public function circle9999Action()
    {
    	$data=array();
    	 $data['trend']=$this->getRequest()->get("trend");
    	 $data['publish']=$this->getRequest()->get("publish");
    	return $this->render('JustsyBaseBundle:FriendCircle:index_9999_right.html.twig', $data);
    }

    public function ifPicNull($pic) {
        if (empty($pic)) {
            $pic = $this->get('templating.helper.assets')->getUrl('bundles/fafatimewebase/images/no_photo.png');
        } else {
            $pic = $this->container->getParameter('FILE_WEBSERVER_URL') . $pic;
        }
        return $pic;
    }

    //生成主页右侧内容
    public function rightAction($network_domain) {
        $data = array();
        $data['curr_network_domain'] = $network_domain;
        $data['DOC']=true;
         $user = $this->get('security.context')->getToken()->getUser();
        if(!in_array(\Justsy\BaseBundle\Management\FunctionCode::$DOC,$user->getFunctionCodes())){
                $data["DOC"]= false;
            }
        return $this->render('JustsyBaseBundle:EnterpriseHome:right.html.twig', $data);
    }

    public function rightMicroAppAction($network_domain) {
        $conn = $this->get('we_data_access');
        $conn_im = $this->get('we_data_access_im');
        $userinfo = $this->get('security.context')->getToken()->getUser();
        $eno=$userinfo->getEno();
        $logger=$this->get("logger");
        $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
        $listMA = $MicroAccountMgr->getmicroaccount(false,1);
        $micro_app_count = count($listMA);

        $EnoParamManager=new EnoParamManager($conn,$logger);
        $micro_app_allow_count=$EnoParamManager->getCountCreateAppMicroAccount($eno);

        $data = array();
        $data['curr_network_domain'] = $network_domain;
        $data["listMA"] = $listMA;
        $data['micro_app_cancount'] = (int)$micro_app_allow_count - (int)$micro_app_count;
        if ($data['micro_app_cancount'] < 0) $data['micro_app_cancount'] = 0;

        $htmlfile = 'JustsyBaseBundle:EnterpriseHome:right_microapp_0.html.twig';
        if ($micro_app_count > 5) $htmlfile = 'JustsyBaseBundle:EnterpriseHome:right_microapp_n.html.twig';
        else if ($micro_app_count > 0) $htmlfile = 'JustsyBaseBundle:EnterpriseHome:right_microapp_5.html.twig';
        else $htmlfile = 'JustsyBaseBundle:EnterpriseHome:right_microapp_0.html.twig';

        return $this->render($htmlfile, $data);
    }

    public function rightuserguidAction($network_domain) {
        $data = array();
        $data['curr_network_domain'] = $network_domain;
        $perBaseInfo = new \Justsy\BaseBundle\Controller\CPerBaseInfoController();
        $perBaseInfo->setContainer($this->container);
        $user = $this->get('security.context')->getToken()->getUser();
        $InfoCompletePercent = $perBaseInfo->GetInfoCompletePercent($user->getUsername());
        $data['InfoCompletePercent'] = $InfoCompletePercent;
         $data["ROSTER_INVITE"]= true;
         $data["DOC"]= true;
        if(!in_array(\Justsy\BaseBundle\Management\FunctionCode::$ROSTER_INVITE,$user->getFunctionCodes())){
           $data["ROSTER_INVITE"]= false;
        }
        if(!in_array(\Justsy\BaseBundle\Management\FunctionCode::$ROSTER_INVITE,$user->getFunctionCodes())){
           $data["DOC"]= false;
        }
        return $this->render('JustsyBaseBundle:EnterpriseHome:right_operation.html.twig', $data);
    }

    //取出最新的事件（活动、投票）
    public function righteventAction($network_domain) {
        $user = $this->get('security.context')->getToken()->getUser();
        $request = $this->getRequest();
        $circle_id = $user->get_circle_id($network_domain);
        $group_id = $request->get("group_id");

        if (empty($group_id))
            $group_id = "ALL";

        $sql = "select * from(
	select a.conv_id, a.conv_type_id, b.title, 
		TIMESTAMPDIFF(minute, now(), b.will_date) minutes,
		(select count(1) from we_together_staff wts where wts.together_id=a.conv_id and wts.login_account=?) ismember
	from we_convers_list a
	inner join we_together b on b.together_id=a.conv_id and b.will_date>=now()
	where a.conv_type_id='02'
		and a.post_to_circle=?
		and a.post_to_group=?
	order by minutes 
	limit 0, 5
) as aaa
union 
select * from 
(
	select a.conv_id, a.conv_type_id, b.title, 
		TIMESTAMPDIFF(minute, now(), b.finishdate) minutes,
		(select count(1) from we_vote_user wvu where wvu.vote_id=a.conv_id and wvu.login_account=?) ismember
	from we_convers_list a
	inner join we_vote b on b.vote_id=a.conv_id and b.finishdate>=now()
	where a.conv_type_id='03'
		and a.post_to_circle=?
		and a.post_to_group=?
	order by minutes 
	limit 0, 5
) as bbb
order by minutes 
limit 0, 5";
        $params = array();
        $params[] = (string) $user->getUserName();
        $params[] = (string) $circle_id;
        $params[] = (string) $group_id;
        $params[] = (string) $user->getUserName();
        $params[] = (string) $circle_id;
        $params[] = (string) $group_id;

        $da = $this->get('we_data_access');
        $ds = $da->GetData("we_convers_list", $sql, $params);

        $a = array();
        $a["this"] = $this;
        $a["ds"] = $ds;

        return $this->render('JustsyBaseBundle:EnterpriseHome:right_event.html.twig', $a);
    }

    public function formatDayHourMin($mins) {
        $re = "";

        if ($mins > 1440) {
            $re = ((string) floor($mins / 1440)) . "天" . ((string) floor(($mins % 1440) / 60)) . "小时";
        } else if ($mins > 60) {
            $re = ((string) floor($mins / 60)) . "小时" . ((string) floor($mins % 60)) . "分钟";
        } else {
            $re = ((string) $mins) . "分钟";
        }

        return $re;
    }
    //根据当前用户认证等级显示升级特权
    //J用户：不用显示
    //N用户：显示V1功能
    //V用户：显示上一级新增功能
    public function rightVipFunctionAction($network_domain)
    {
    	   $user = $this->get('security.context')->getToken()->getUser();
    	   $a=array();
    	   $a[]= array("title"=>"增强的企业管理功能");
    	   $a[]= array("title"=>"企业微信推送平台");
    	   $a[]= array("title"=>"移动互联微应用");
    	   return $this->render('JustsyBaseBundle:EnterpriseHome:right_auth_function.html.twig', array("functionlist"=>  $a));
    }
    //取出推荐的圈子
    public function rightrecommcircleAction($network_domain) {
        $user = $this->get('security.context')->getToken()->getUser();

        $sql = "select a.circle_id, a.circle_name, a.logo_path,a.logo_path_small, a.create_staff  
from we_circle a 
where a.enterprise_no is null and a.join_method='0'
  and not exists(select 1 from we_circle_staff wcs where wcs.circle_id=a.circle_id and wcs.login_account=?)
  and not exists(select 1 from we_apply wa where wa.recv_type='c' and wa.recv_id=a.circle_id and wa.account=?)
  and circle_recommend is not null
order by circle_recommend , a.create_date
limit 0, 25";
        $params = array();
        $params[] = (string) $user->getUserName();
        $params[] = (string) $user->getUserName();
        $da = $this->get('we_data_access');
        $ds = $da->GetData("we_circle", $sql, $params);

        $a = array();
        $a["this"] = $this;
        $a["curr_network_domain"] = $network_domain;
        $a["ds"] = $ds;
         $a["circle_join_c"]=true;
        if(!in_array(\Justsy\BaseBundle\Management\FunctionCode::$CIRCLE_JOIN_C,$user->getFunctionCodes())){
            $a["circle_join_c"]= false;
        }
        return $this->render('JustsyBaseBundle:EnterpriseHome:right_recomm_circle.html.twig', $a);
    }

    //取出推荐的内部群组
    public function rightrecommgroupAction($network_domain) {
        $user = $this->get('security.context')->getToken()->getUser();

        $sql = "select a.group_id, a.group_name, a.group_photo_path, a.create_staff  
from we_groups a ,we_circle b
where a.circle_id = b.circle_id and b.enterprise_no=?
  and a.join_method<>'1'
  and not exists(select 1 from we_group_staff wcs where wcs.group_id=a.group_id and wcs.login_account=?)
order by a.create_date
limit 0, 5";
        $params = array();
        $params[] = (string) $user->eno;
        $params[] = (string) $user->getUserName();

        $da = $this->get('we_data_access');
        $ds = $da->GetData("we_group", $sql, $params);

        $a = array();
        $a["this"] = $this;
        $a["curr_network_domain"] = $network_domain;
        $a["ds"] = $ds;

        return $this->render('JustsyBaseBundle:EnterpriseHome:right_recomm_group.html.twig', $a);
    }

    public function needTipDownload() {
        $re = true;
        $user = $this->get('security.context')->getToken()->getUser();
        $TipDownloadParaID = "TIP_DOWNLOAD";

        $sql = "select count(*) c from we_staff_para where login_account=? and para_id=? and para_value=?";
        $params = array();
        $params[] = (string) $user->getUserName();
        $params[] = (string) $TipDownloadParaID;
        $params[] = "1";

        $da = $this->get('we_data_access');
        $ds = $da->GetData("we_staff_para", $sql, $params);

        if ($ds["we_staff_para"]["rows"][0]["c"] > 0)
            $re = false;

        return $re;
    }

    public function getTipDownloadAction($network_domain) {
        $a = array();
        $a["this"] = $this;
        $a["curr_network_domain"] = $network_domain;

        return $this->render('JustsyBaseBundle:EnterpriseHome:tip_download.html.twig', $a);
    }

    public function setTipDownloadAction($network_domain) {
        $user = $this->get('security.context')->getToken()->getUser();
        $TipDownloadParaID = "TIP_DOWNLOAD";

        $sqls = array();
        $all_params = array();

        $sql = "delete from we_staff_para where login_account=? and para_id=? ";
        $params = array();
        $params[] = (string) $user->getUserName();
        $params[] = (string) $TipDownloadParaID;

        $sqls[] = $sql;
        $all_params[] = $params;

        $sql = "insert into we_staff_para (login_account, para_id, para_value) values(?, ?, ?) ";
        $params = array();
        $params[] = (string) $user->getUserName();
        $params[] = (string) $TipDownloadParaID;
        $params[] = "1";

        $sqls[] = $sql;
        $all_params[] = $params;

        $da = $this->get('we_data_access');
        $da->ExecSQLs($sqls, $all_params);

        $re = array();
        $re["success"] = "1";
        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }

    public function quitCircleAction($network_domain) {
        $re = array();
        $user = $this->get('security.context')->getToken()->getUser();
        $request = $this->getRequest();
        $circle_id = $user->get_circle_id($network_domain);

        $da = $this->get('we_data_access');
				$da_im = $this->get('we_data_access_im');
        $sql = "call p_quitcircle(?, ?, 0)";
        $params = array();
        $params[] = (string) $circle_id;
        $params[] = (string) $user->getUserName();
        $ds = $da->GetData("p_quitcircle", $sql, $params);

        if ($ds["p_quitcircle"]["rows"][0]["recode"] == "0") {
        	  //向成员发送通知 
        	  $circleMgr=new \Justsy\BaseBundle\Management\CircleMgr($da,$da_im,$circle_id);
        	  $circleObj = $circleMgr->Get();
        	  if(!empty($circleObj)){
        	  	  $goupCtl = new GroupController();
        	  	  $goupCtl->setContainer($this->container);
                $message = Utils::makeHTMLElementTag('employee',$user->fafa_jid,$user->nick_name)."退出了圈子【".$circleObj["circle_name"]."】";
                $goupCtl->sendPresenceGroup($circleObj["fafa_groupid"],"group_deletemeber",$message);  
            }         	  
            $re = array('success' => '1');
        } else {
            $re = array('success' => '0');
            $logger = $this->container->get('logger');
            $logger->err("quitCircle Error circle_id:" . $circle_id . " msg:" . $ds["p_quitgroup"]["rows"][0]["remsg"]);
        }

        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }

    public function quitEntpAction($network_domain) {
        $re = array();
        $user = $this->get('security.context')->getToken()->getUser();
        $request = $this->getRequest();
        $circle_id = $user->get_circle_id($network_domain);

        $da = $this->get('we_data_access');

        $sql = "call p_quitentp(?, ?, 0)";
        $params = array();
        $params[] = (string) $circle_id;
        $params[] = (string) $user->getUserName();
        $ds = $da->GetData("p_quitentp", $sql, $params);

        if ($ds["p_quitentp"]["rows"][0]["recode"] == "0") {
            $re = array('success' => '1');
        } else {
            $re = array('success' => '0');
            $logger = $this->container->get('logger');
            $logger->err("quitEntp Error circle_id:" . $circle_id . " msg:" . $ds["p_quitgroup"]["rows"][0]["remsg"]);
        }

        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }
    
    public function staffInviteAction($network_domain)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $circle_id = $user->get_circle_id($network_domain);
        if($circle_id!=$user->get_circle_id($user->edomain))
        {
		        $response = new Response("");
		        $response->headers->set('Content-Type', 'text/html');
		        return $response;
        }
        else
        {
        	  $a = array();
        	  //判断是否有同事
        	  $staffMgr = new \Justsy\BaseBundle\Management\Staff($this->get('we_data_access'),$this->get('we_data_access_im'),$user->getUserName(),$this->get("logger"));
           	$a["list"] = $staffMgr->getColleague(7);
           	return $this->render('JustsyBaseBundle:EnterpriseHome:staff_invite.html.twig', $a);
        }
    }

    public function tipInviteAction($network_domain) {
        $a = array();
        $a["curr_network_domain"] = $network_domain;

        $user = $this->get('security.context')->getToken()->getUser();
        $circle_id = $user->get_circle_id($network_domain);

        $da = $this->get('we_data_access');

        if ($circle_id == "9999")
        {
            $a["circle_staff_count"] = $user->fans_num+1;
        }
        else
        {
            $sql = "select count(*) c from we_circle_staff where circle_id=?";
            $params = array();
            $params[] = (string) $circle_id;
            $ds = $da->GetData("we_circle_staff", $sql, $params);
            $a["circle_staff_count"] = $ds["we_circle_staff"]["rows"][0]["c"];            
        }
         $a["roster_invite"]= true;
        if(!in_array(\Justsy\BaseBundle\Management\FunctionCode::$ROSTER_INVITE,$user->getFunctionCodes())){
            $a["roster_invite"]= false;
        }
        return $this->render('JustsyBaseBundle:EnterpriseHome:tip_invite.html.twig', $a);
    }

    public function getTopConvAction() {
    	$trend=	$this->getRequest()->get('trend');
        $request = $this->getRequest();
        $network_domain = $request->get('network_domain');
        $user = $this->get('security.context')->getToken()->getUser();
        $class = $request->get('class');
        $da = $this->get('we_data_access');

        $sql = "select a.conv_id,a.conv_type_id, d.template_controller,a.post_date  
from we_convers_list a,we_conv_template d,we_official_publish pu  
where a.conv_id=a.conv_root_id
  and a.post_to_circle=?
  and (a.post_to_group in ('ALL', case when a.login_account=? then 'PRIVATE' else '' end)
    or exists(select 1 from we_convers_notify wcn where wcn.conv_id=a.conv_id and wcn.cc_login_account=?))
     and exists(select 1 from we_conv_top e where e.conv_id=a.conv_id and e.timeout>now())
     and a.conv_type_id=d.conv_type_id
     and pu.info_id=a.conv_id and pu.info_type='notice'";

        if ($class == "conv") {//所有会话，无类别区分
            $sql.=" and not exists(select 1 from we_conv_hide b where b.conv_id=a.conv_id and b.login_account=?)";
        } else if ($class == "official") {//官方的
            $sql.=" and a.conv_type_id='06'";
        } else if ($class == "ask") {//提问
            $sql.=" and a.conv_type_id='01'";
        } else if ($class == "together") {//活动
            $sql.=" and a.conv_type_id='02'";
            ;
        } else if ($class == "vote") {//投票
            $sql.=" and a.conv_type_id='03'";
        } else if ($class == "trend") {//动态
            $sql.=" and (a.conv_type_id='00' or a.conv_type_id='05')";
        }

        $params = array();
        $params[] = (string) $user->get_circle_id($network_domain);
        $params[] = (string) $user->getUserName();
        $params[] = (string) $user->getUserName();
        if ($class == 'conv') {
            $params[] = (string) $user->getUserName();
        }

        $sql .=" order by (0+a.conv_id) desc limit 0,1";
        $ds_1 = $da->GetData("we_convers_list", $sql, $params);

        $sql2 = "select a.conv_id,a.conv_type_id, d.template_controller,a.post_date   
from we_convers_list a,we_conv_template d,we_official_publish pu  
where a.conv_id=a.conv_root_id
  and a.post_to_circle=?
  and (a.post_to_group in ('ALL', case when a.login_account=? then 'PRIVATE' else '' end)
    or exists(select 1 from we_convers_notify wcn where wcn.conv_id=a.conv_id and wcn.cc_login_account=?))
     and exists(select 1 from we_conv_top e where e.conv_id=a.conv_id and e.timeout>now())
     and a.conv_type_id=d.conv_type_id
     and pu.info_id=a.conv_id and pu.info_type='bulletin'";

        if ($class == "conv") {//所有会话，无类别区分
            $sql2.=" and not exists(select 1 from we_conv_hide b where b.conv_id=a.conv_id and b.login_account=?)";
        } else if ($class == "official") {//官方的
            $sql2.=" and a.conv_type_id='06'";
        } else if ($class == "ask") {//提问
            $sql2.=" and a.conv_type_id='01'";
        } else if ($class == "together") {//活动
            $sql2.=" and a.conv_type_id='02'";
        } else if ($class == "vote") {//投票
            $sql2.=" and a.conv_type_id='03'";
        } else if ($class == "trend") {//动态
            $sql2.=" and (a.conv_type_id='00' or a.conv_type_id='05')";
        }

        $params2 = array();
        $params2[] = (string) $user->get_circle_id($network_domain);
        $params2[] = (string) $user->getUserName();
        $params2[] = (string) $user->getUserName();
        if ($class == 'conv') {
            $params2[] = (string) $user->getUserName();
        }

        $sql2 .=" order by (0+a.conv_id) desc limit 0,1";
        $ds_2 = $da->GetData("we_convers_list_2", $sql2, $params2);
        $ds = array();
        if ($ds_1["we_convers_list"]["recordcount"] == 1) {
            $ds[] = $ds_1["we_convers_list"]["rows"][0];
        }
        if ($ds_2["we_convers_list_2"]["recordcount"] == 1) {
            if (count($ds) == 1 && $ds[0]['post_date'] < $ds_2["we_convers_list_2"]["rows"][0]['post_date']) {
                array_unshift($ds, $ds_2["we_convers_list_2"]["rows"][0]);
            } else {
                $ds[] = $ds_2["we_convers_list_2"]["rows"][0];
            }
        }
        return $this->render('JustsyBaseBundle:CDisplayArea:hastop.html.twig', array('we_convers_list' => $ds,'trend'=> $trend));
    }
		public function getTopConvPcAction(){
			$trend=	$this->getRequest()->get('trend');
			$request = $this->getRequest();
        $network_domain = $request->get('network_domain');
        $user = $this->get('security.context')->getToken()->getUser();
        $class = $request->get('class');
        $da = $this->get('we_data_access');

        $sql = "select a.conv_id,a.conv_type_id, concat(d.template_controller,'Pc') as template_controller,a.post_date  
from we_convers_list a,we_conv_template d,we_official_publish pu  
where a.conv_id=a.conv_root_id
  and a.post_to_circle=?
  and (a.post_to_group in ('ALL', case when a.login_account=? then 'PRIVATE' else '' end)
    or exists(select 1 from we_convers_notify wcn where wcn.conv_id=a.conv_id and wcn.cc_login_account=?))
     and exists(select 1 from we_conv_top e where e.conv_id=a.conv_id and e.timeout>now())
     and a.conv_type_id=d.conv_type_id
     and pu.info_id=a.conv_id and pu.info_type='notice'";

        if ($class == "conv") {//所有会话，无类别区分
            $sql.=" and not exists(select 1 from we_conv_hide b where b.conv_id=a.conv_id and b.login_account=?)";
        } else if ($class == "official") {//官方的
            $sql.=" and a.conv_type_id='06'";
        } else if ($class == "ask") {//提问
            $sql.=" and a.conv_type_id='01'";
        } else if ($class == "together") {//活动
            $sql.=" and a.conv_type_id='02'";
        } else if ($class == "vote") {//投票
            $sql.=" and a.conv_type_id='03'";
        } else if ($class == "trend") {//动态
            $sql.=" and (a.conv_type_id='00' or a.conv_type_id='05')";
        }

        $params = array();
        $params[] = (string) $user->get_circle_id($network_domain);
        $params[] = (string) $user->getUserName();
        $params[] = (string) $user->getUserName();
        if ($class == 'conv') {
            $params[] = (string) $user->getUserName();
        }

        $sql .=" order by (0+a.conv_id) desc limit 0,1";
        $ds_1 = $da->GetData("we_convers_list", $sql, $params);

        $sql2 = "select a.conv_id,a.conv_type_id, concat(d.template_controller,'Pc') as template_controller,a.post_date   
from we_convers_list a,we_conv_template d,we_official_publish pu  
where a.conv_id=a.conv_root_id
  and a.post_to_circle=?
  and (a.post_to_group in ('ALL', case when a.login_account=? then 'PRIVATE' else '' end)
    or exists(select 1 from we_convers_notify wcn where wcn.conv_id=a.conv_id and wcn.cc_login_account=?))
     and exists(select 1 from we_conv_top e where e.conv_id=a.conv_id and e.timeout>now())
     and a.conv_type_id=d.conv_type_id
     and pu.info_id=a.conv_id and pu.info_type='bulletin'";

        if ($class == "conv") {//所有会话，无类别区分
            $sql2.=" and not exists(select 1 from we_conv_hide b where b.conv_id=a.conv_id and b.login_account=?)";
        } else if ($class == "official") {//官方的
            $sql2.=" and a.conv_type_id='06'";
        } else if ($class == "ask") {//提问
            $sql2.=" and a.conv_type_id='01'";
        } else if ($class == "together") {//活动
            $sql2.=" and a.conv_type_id='02'";
            ;
        } else if ($class == "vote") {//投票
            $sql2.=" and a.conv_type_id='03'";
        } else if ($class == "trend") {//动态
            $sql2.=" and (a.conv_type_id='00' or a.conv_type_id='05')";
        }

        $params2 = array();
        $params2[] = (string) $user->get_circle_id($network_domain);
        $params2[] = (string) $user->getUserName();
        $params2[] = (string) $user->getUserName();
        if ($class == 'conv') {
            $params2[] = (string) $user->getUserName();
        }

        $sql2 .=" order by (0+a.conv_id) desc limit 0,1";
        $ds_2 = $da->GetData("we_convers_list_2", $sql2, $params2);
        $ds = array();
        if ($ds_1["we_convers_list"]["recordcount"] == 1) {
            $ds[] = $ds_1["we_convers_list"]["rows"][0];
        }
        if ($ds_2["we_convers_list_2"]["recordcount"] == 1) {
            if (count($ds) == 1 && $ds[0]['post_date'] < $ds_2["we_convers_list_2"]["rows"][0]['post_date']) {
                array_unshift($ds, $ds_2["we_convers_list_2"]["rows"][0]);
            } else {
                $ds[] = $ds_2["we_convers_list_2"]["rows"][0];
            }
        }
        return $this->render('JustsyBaseBundle:CDisplayArea:hastop.html.twig', array('we_convers_list' => $ds,'trend'=>$trend));
		}
    public function getOfficialConvAction($network_domain) {
        $request = $this->getRequest();
        $user = $this->get('security.context')->getToken()->getUser();
        $network_domain = $request->get('network_domain');
        $da = $this->get('we_data_access');

        $sql = "select a.conv_root_id   
from we_convers_list a 
where a.conv_id=a.conv_root_id
  and a.post_to_circle=?
  and (a.post_to_group in ('ALL', case when a.login_account=? then 'PRIVATE' else '' end)
    or exists(select 1 from we_convers_notify wcn where wcn.conv_id=a.conv_id and wcn.cc_login_account=?))
      and a.conv_type_id='06'";

        $params = array();
        $params[] = (string) $user->get_circle_id($network_domain);
        $params[] = (string) $user->getUserName();
        $params[] = (string) $user->getUserName();

        $sql .= " order by (0+a.conv_id) desc";

        $ds = $da->GetData("we_convers_list", $sql, $params);

        //生成html返回
        $conv_root_ids = array_map(function ($row) {
                    return $row["conv_root_id"];
                }, $ds["we_convers_list"]["rows"]);
       $trend = $request->get('trend');
        return $this->forward("JustsyBaseBundle:CDisplayArea:getConv", array("conv_root_ids" => $conv_root_ids,'trend'=> $trend, "curr_network_domain" => $network_domain));
    }

    public function showOfficialConvAction($network_domain) {
        return $this->render('JustsyBaseBundle:EnterpriseHome:official.html.twig', array("curr_network_domain" => $network_domain));
    }

}