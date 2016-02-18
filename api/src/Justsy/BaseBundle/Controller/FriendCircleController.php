<?php

namespace Justsy\BaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Management\FriendCircle;
use Justsy\BaseBundle\Management\UserTag;
use Justsy\BaseBundle\Management\FriendEvent;

class FriendCircleController extends Controller {

    //取出当前圈子当前用户所能看到的所有会话
    public function getAllConvAction($class) {
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
  and not exists(select 1 from we_conv_top p where p.conv_id=a.conv_id and p.timeout>now())
  and exists(select 1 from dual where a.login_account=? union select 1 from we_staff_atten wsa,we_staff_atten wsb where wsa.login_account=? and wsa.atten_type='01' and wsa.atten_id=a.login_account and wsb.login_account=wsa.atten_id  and wsb.atten_id=? and wsb.atten_type='01')";

        if ($class == "conv") {//所有会话，无类别区分
            $sql.=" and not exists(select 1 from we_conv_hide c where c.conv_id=a.conv_id and c.login_account=?)";
            //广播信息
            $Announcer = new \Justsy\BaseBundle\Management\Announcer($this->container);
            $loign_accounts = $Announcer->broadcaster_staff($user->eno,$user->dept_id,$user->fafa_jid);
            if ( count($loign_accounts)>0)
            {
                $sql .= " or a.login_account in ('".implode("','",$loign_accounts)."') ";
            }
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
            $sql.=" and a.conv_type_id='00'";
        }
        $params = array();
        $params[] = (string) $user->get_circle_id($network_domain);
        $params[] = (string) $user->getUserName();
        $params[] = (string) $user->getUserName();
        $params[] = (string) $user->getUserName();
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
        $isshow_relation_static_trend=false;
        if(count($conv_root_ids)<14 && empty($endid))  //是否显示系统动态
        {
        	 $isshow_relation_static_trend=true;
        }
        if ($pageindex == 1 && empty($endid) && count($conv_root_ids) > 0) {
            //更新用户最后读的信息ID
            $conv = new \Justsy\BaseBundle\Business\Conv();
            $conv->updateLastReadID_Circle($da, $user, $user->get_circle_id($network_domain), $conv_root_ids[0]);
        }

        return $this->forward("JustsyBaseBundle:CDisplayArea:getConv", array("conv_root_ids" => $conv_root_ids,"isshow_relation_static_trend"=> $isshow_relation_static_trend,"trend"=>true));
    }


    //PC端取出当前圈子当前用户所能看到的所有会话
    public function getAllConvPcAction($class) {
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
  and not exists(select 1 from we_conv_top p where p.conv_id=a.conv_id and p.timeout>now())
  and exists(select 1 from dual where a.login_account=? union select 1 from we_staff_atten wsa where wsa.login_account=? and wsa.atten_type='01' and wsa.atten_id=a.login_account)";

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
            $sql.=" and a.conv_type_id='00'";
        }
        $params = array();
        $params[] = (string) $user->get_circle_id($network_domain);
        $params[] = (string) $user->getUserName();
        $params[] = (string) $user->getUserName();
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

        return $this->forward("JustsyBaseBundle:CDisplayArea:getConvPc", array("conv_root_ids" => $conv_root_ids,"trend"=>true));
    }

    //取出当前圈子当前用户所能看到的所有未读会话
    public function getAllConvUnreadAction() {
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
  and exists(select 1 from dual where a.login_account=? union select 1 from we_staff_atten wsa where wsa.login_account=? and wsa.atten_type='01' and wsa.atten_id=a.login_account)
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
            $sql.=" and a.conv_type_id='00'";
        }
        $params = array();
        $params[] = (string) $user->get_circle_id($network_domain);
        $params[] = (string) $user->getUserName();
        $params[] = (string) $user->getUserName();
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

        return $this->forward("JustsyBaseBundle:CDisplayArea:getConv", array("conv_root_ids" => $conv_root_ids,"trend"=>true));
    }
    
    public function getAllConvUnreadPcAction() {
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
  and exists(select 1 from dual where a.login_account=? union select 1 from we_staff_atten wsa where wsa.login_account=? and wsa.atten_type='01' and wsa.atten_id=a.login_account)
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
            $sql.=" and a.conv_type_id='00'";
        }
        $params = array();
        $params[] = (string) $user->get_circle_id($network_domain);
        $params[] = (string) $user->getUserName();
        $params[] = (string) $user->getUserName();
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

        return $this->forward("JustsyBaseBundle:CDisplayArea:getConvPc", array("conv_root_ids" => $conv_root_ids,"trend"=>true));
    }
    public function getRecomContactsAction()
    {
    	 $user = $this->get('security.context')->getToken()->getUser();
       $request = $this->getRequest();
       $da = $this->get('we_data_access');
       $da->PageSize  =$request->get('pagesize',10);
	  	 $da->PageIndex = $request->get('pageindex')?$request->get('pageindex')-1:0;
       
       $friendcircle=new FriendCircle($da,$this->get('logger'),$this->container);
       $rows=$friendcircle->getRemAccount($user->eno,$user->getUserName());
       
       $response=new Response(json_encode($rows));
	   	 $response->headers->set('Content-Type','Application/json');
	     return $response;
    }
    public function RemAccountsAction()
    {
    	$user = $this->get('security.context')->getToken()->getUser();
      $request = $this->getRequest();
      $da = $this->get('we_data_access');
      
      $response=$this->getRecomContactsAction();
      $json=$response->getContent();
      $rows=json_decode($json,true);
      return $this->render("JustsyBaseBundle:FriendCircle:remaccounts.html.twig",array('rows'=> $rows));
    }
    public function getRecomEnoAction()
    {
    	$user = $this->get('security.context')->getToken()->getUser();
      $request = $this->getRequest();
      $da = $this->get('we_data_access');
      
      $da->PageSize  =$request->get('pagesize',30);
	  	$da->PageIndex = $request->get('pageindex')?$request->get('pageindex')-1:0;
	  	//$this->get("logger")->err("1.1");
	  	$friendcircle=new FriendCircle($da,$this->get('logger'),$this->container);
	  	//$this->get("logger")->err("1.2");
	  	$rows=$friendcircle->getRecomEno($user->getUserName());
	  	//$this->get("logger")->err("1.3");
	  	$response=new Response(json_encode($rows));
	  	//$this->get("logger")->err("1.4");
	   	$response->headers->set('Content-Type','Application/json');
	    return $response;
    }
    public function RemEnterpriseAction()
    {
    	$user = $this->get('security.context')->getToken()->getUser();
      $request = $this->getRequest();
      $da = $this->get('we_data_access');
      //$this->get("logger")->err("1");
      $response=$this->getRecomEnoAction();
      $json=$response->getContent();
      //$this->get("logger")->err("2");
      $rows=json_decode($json,true);
      //$this->get("logger")->err("3");
      return $this->render("JustsyBaseBundle:FriendCircle:rementerprise.html.twig",array('rows'=> $rows));
    }
    public function recommendAction()
    {
    	$user = $this->get('security.context')->getToken()->getUser();
      $request = $this->getRequest();
      $da = $this->get('we_data_access');
      
      $response=$this->getRecommendAction();
      $json=$response->getContent();
      $rows=json_decode($json,true);
      return $this->render("JustsyBaseBundle:FriendCircle:recommend.html.twig",array('rows'=> $rows));
    }
    public function getRecommendAction()
    {
    	$user = $this->get('security.context')->getToken()->getUser();
      $request = $this->getRequest();
      
      $da = $this->get('we_data_access');
      $da->PageSize  =$request->get('pagesize',10);
	  	$da->PageIndex = $request->get('pageindex')?$request->get('pageindex')-1:0;
	  	
      $friendcircle=new FriendCircle($da,$this->get('logger'),$this->container);
      $rows=$friendcircle->getRecommend($user->getUserName());
      $response=new Response(json_encode($rows));
	   	$response->headers->set('Content-Type','Application/json');
	    return $response;
    }
    public function UserTagAction()
    {
    	$user = $this->get('security.context')->getToken()->getUser();
      $request = $this->getRequest();
      
      $da = $this->get('we_data_access');
      
      $usertag=new UserTag($da,$this->get('logger'),$this->container);
      $rows=$usertag->gettag($user->getUserName());
      $have=count($rows);
      return $this->render("JustsyBaseBundle:FriendCircle:usertag.html.twig",array('rows'=>$rows,'count'=>$have));
    }
    public function FriendEventAction()
    {
    	$user = $this->get('security.context')->getToken()->getUser();
      $request = $this->getRequest();
      
      $da = $this->get('we_data_access');
      $FriendEvent=new FriendEvent($da,$this->get('logger'),$this->container);
      $rows=$FriendEvent->getEvents($user->getUserName());
      
      return $this->render("JustsyBaseBundle:FriendCircle:friendevent.html.twig",array('rows'=> $rows));
    }
    public function getSupperUserAction()
    {
    	$user = $this->get('security.context')->getToken()->getUser();
      $request = $this->getRequest();
      
      $da = $this->get('we_data_access');
      $da->PageSize  =$request->get('pagesize',18);
	  	$da->PageIndex = $request->get('pageindex')?$request->get('pageindex')-1:0;
      $friendcircle=new FriendCircle($da,$this->get('logger'),$this->container);
      $rows=$friendcircle->getSupperUser($user->getUserName());
      $response=new Response(json_encode($rows));
	   	$response->headers->set('Content-Type','Application/json');
	    return $response;
    }
    public function SupperUserAction()
    {
    	$user = $this->get('security.context')->getToken()->getUser();
      $request = $this->getRequest();
      
      $da = $this->get('we_data_access');
      $response=$this->getSupperUserAction();
      $json=$response->getContent();
      $rows=json_decode($json,true);
      return $this->render("JustsyBaseBundle:FriendCircle:supperuser.html.twig",array('rows'=> $rows,'json'=> json_encode($rows)));
    }
}