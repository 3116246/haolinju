<?php

namespace Justsy\BaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\BaseBundle\Login\UserSession;
use Justsy\BaseBundle\Login\UserProvider;
use Justsy\BaseBundle\Common\DES;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Justsy\BaseBundle\Management\RelationMgr;

class PersonalHomeController extends Controller
{
  public $groups,$account,$network_domain;
  
  public function homeAction()
  {
  	$request=$this->get("request");
  	$this->network_domain = $request->get("network_domain");
    $user = $this->get('security.context')->getToken()->getUser();
    //取出当前圈子的群组
    $circleId = $user->get_circle_id($request->get("network_domain"));  
    $this->getGroupByUser($circleId,$user->getUserName());    
    return $this->render('JustsyBaseBundle:PersonalHome:index.html.twig',array('this' =>$this,'curr_network_domain'=>$this->network_domain,'group_s'=>  $user->IsExistsFunction("GROUP_S")));
  }
  
  public function relationAttenAction($network_domain,$attentype)
  {
  	$request=$this->get("request");
    $user = $this->get('security.context')->getToken()->getUser();
    $relationMgr = new RelationMgr($this->get('we_data_access'),$this->get('we_data_access_im'),$user);
    $count=0;
    if($attentype=="atten_me")
       $count = $relationMgr->getAttenMeCount();
    else
       $count = $relationMgr->getMeAttenCount();
    
    return $this->render('JustsyBaseBundle:PersonalHome:myrelation.html.twig',array('type' => $attentype,'count'=> $count,'curr_network_domain'=> $network_domain));  	  
  }
  //我的粉丝
  public function relationAttenMeAction()
  {
  	  $request=$this->get("request");
  	  $order=$request->get('order');
  	  $order=empty($order)?'date':$order;//默认按关注时间倒序排列
  	  $searchby=$request->get('searchby');
      $user = $this->get('security.context')->getToken()->getUser();
      $relationMgr = new RelationMgr($this->get('we_data_access'),$this->get('we_data_access_im'),$user);  	
      //$count = $relationMgr->getAttenMeCount();
      $data = $relationMgr->getAttenMeList(0,500,$this->container->getParameter('FILE_WEBSERVER_URL'),$order,$searchby);
      $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($data).");" : json_encode($data));
			$response->headers->set('Content-Type', 'text/json');
			return $response;
  }  
  //我关注的
  public function relationMeAttenAction()
  {
  	  $request=$this->get("request");
  	  $order=$request->get('order');
  	  $order=empty($order)?'date':$order;//默认按关注时间倒序排列
  	  $searchby=$request->get('searchby');
      $user = $this->get('security.context')->getToken()->getUser();
      $relationMgr = new RelationMgr($this->get('we_data_access'),$this->get('we_data_access_im'),$user);  	  
      //$count = $relationMgr->getMeAttenCount();
      $data = $relationMgr->getMeAttenList(0,500,$this->container->getParameter('FILE_WEBSERVER_URL'),$order,$searchby);
  	  $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($data).");" : json_encode($data));
			$response->headers->set('Content-Type', 'text/json');
			return $response; 
  }   
  private function getGroupByUser($circle_id, $username) 
  {
    $da = $this->get('we_data_access');
//    $sql = "select a.group_id, a.circle_id, a.group_name 
//      from we_groups a, we_circle b, we_group_staff c
//      where a.circle_id=b.circle_id 
//      and a.group_id=c.group_id
//      and a.circle_id=? 
//      and c.login_account=?
//      limit 0, 10";
      
        $sql = "select a.group_id, a.circle_id, a.group_name ,'' applying
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
								  and c.is_valid='1' 
						";      
    $params = array();
    $params[] = (string)$circle_id;
    $params[] = (string)$username;
    $params[] = (string)$circle_id;
    $params[] = (string)$username;    
    $ds = $da->GetData("we_groups", $sql, $params);
    $this->groups = $ds["we_groups"]["rows"];
  }
  
  /*
   * 查看自己的主页 获取会话，type all:所有动态，atten:关注的，publish:发布的
   * 除了type=all 其余未用
   */
  public function getConversAction($type, $network_domain)
  {
  	$request=$this->get("request");
  	$da = $this->get('we_data_access');
  	$user = $this->get('security.context')->getToken()->getUser();
    $circleId = $user->get_circle_id($network_domain);   	
    $endid = $request->get('endid');
    $pre450num = $request->get('pre450num');
    $pageindex = $request->get('pageindex');
    $pagesize  = 45;
    $sql = '';
    $para = array();
    if ($type == 'all')
    {
      $sql = "select a.conv_root_id from we_convers_list a 
        where a.conv_id=a.conv_root_id and a.post_to_circle=?  
        and a.post_to_group in ('ALL', case when a.login_account=? then 'PRIVATE' else '' end)
        and login_account=?";
      $para[] = (string)$circleId;      
      $para[] = (string)$user->getUsername();
      $para[] = (string)$user->getUsername();
    }
    else if ($type == 'atten')
    {
      $sql = "select a.conv_root_id 
        from we_convers_list a, we_staff_atten b
        where a.conv_id=a.conv_root_id
          and a.login_account=b.atten_id 
          and b.atten_type='01'
          and a.post_to_group = 'ALL'
          and a.post_to_circle=?
          and b.login_account=?";
      $para[] = (string)$circleId;
      $para[] = (string)$user->getUsername();
    }
    else if ($type == 'publish')
    {
      $sql = "select a.conv_root_id 
        from we_convers_list a
        where a.conv_id=a.conv_root_id
          and a.post_to_circle=?
          and a.login_account=?";
      $para[] = (string)$circleId;
      $para[] = (string)$user->getUsername();
    }
    if ($pre450num)
    {
      $sql = "select count(*) c from ($sql limit 0, 450) as _ttt_";
      $ds = $da->GetData("we_convers_list", $sql, $para);
      $re = array("pre450num" => $ds["we_convers_list"]["rows"][0]["c"]);
      $response = new Response(json_encode($re));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }

    $sql .= " order by a.post_date desc";
    if ($pageindex){}else {$pageindex=1;}
    $pagestart = ($pageindex-1)*$pagesize;
    $sql .= " limit $pagestart, 100 ";  
    
    $sql = " select * from ($sql) as _ttt_ where 1=1 ";
    if ($endid)
    {
      $sql .= " and (0+conv_root_id)<? ";
      $para[] = (float)$endid;
    }      
    
    $sql .= " limit 0, 15 ";

    $da = $this->get('we_data_access');
    $ds = $da->GetData("we_convers_list", $sql, $para);
    //生成html返回
    $conv_root_ids = array_map(function($row)
    {
      return $row["conv_root_id"];
    }, $ds["we_convers_list"]["rows"]);
    return $this->forward("JustsyBaseBundle:CDisplayArea:getConv", array("conv_root_ids"=>$conv_root_ids,'trend'=> true));
  }
  //查看自己的主页 获取未读会话，type all:所有动态，atten:关注的，publish:发布的
  public function getConversUnreadAction($type, $network_domain)
  {
    $da = $this->get('we_data_access');
    $user = $this->get('security.context')->getToken()->getUser();
    $request = $this->getRequest();
    
    $maxid = $request->get('maxid');
    $onlycount = $request->get('onlycount');
    $params = array();
    if ($type=='all')
    {
      $sql = "select a.conv_root_id 
        from we_convers_list a
        where a.conv_id=a.conv_root_id
        and a.post_to_circle=?
        and a.post_to_group in ('ALL', case when a.login_account=? then 'PRIVATE' else '' end)
        and (0+a.conv_root_id)>?
        and a.login_account=?";
      $params[] = (string)$user->get_circle_id($network_domain);
      $params[] = (string)$user->getUserName();
      $params[] = (float)$maxid;
      $params[] = (string)$user->getUserName();
    }
    
    if ($onlycount)
    {
      $sql = "select count(*) c from ($sql) as _ttt_";
      $ds = $da->GetData("we_convers_list", $sql, $params);
          
      $re = array("unreadcount" => $ds["we_convers_list"]["rows"][0]["c"]);
      $response = new Response(json_encode($re));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }

    $sql .= " order by a.post_date desc";

    $da = $this->get('we_data_access');
    $ds = $da->GetData("we_convers_list", $sql, $params);

    //生成html返回
    $conv_root_ids = array_map(function ($row) {
        return $row["conv_root_id"];
      }, 
      $ds["we_convers_list"]["rows"]);
    
    return $this->forward("JustsyBaseBundle:CDisplayArea:getConv", array("conv_root_ids" => $conv_root_ids,'trend'=> true));
  }
  //从外部系统直接访问个人主页。需要自动登录
  public function intervieweeByOutsideAction()
  {
  	$res = $this->get("request");
  	$auth = $res->get("authcode");
  	$interviewee = $res->get("interviewee");
	  if($auth==null || $auth=="") return $this->redirect($this->generateUrl('JustsyBaseBundle_login'));
	  if($interviewee==null || $interviewee=="") return $this->redirect($this->generateUrl('JustsyBaseBundle_login'));
	  try{
	  	
	      $auth = trim(DES::decrypt($auth));
	      //解密参数串
	      $paras =  explode(",", trim(DES::decrypt($interviewee)));
	      //授权码已过期
	      $lng = time()-(int)$auth;
	      if($lng>30 || $lng<0) return $this->redirect($this->generateUrl('JustsyBaseBundle_login'));
	  }
	  catch(\Exception $e)
  	{
  		return $this->redirect($this->generateUrl('JustsyBaseBundle_login'));
  	}
	  try
	  {
      if(count($paras)!=2 && count($paras)!=1) return $this->redirect($this->generateUrl('JustsyBaseBundle_login'));

	  	//通过openID获取用户信息
  	  $user = $this->loadUserByUsername($paras[0]);  
  	  if($user==null) return $this->redirect($this->generateUrl('JustsyBaseBundle_login')); 

  	  //登记seesion
  	  $token = new UsernamePasswordToken($user, $user->getPassword(), "secured_area", $user->getRoles());
  	  $this->get("security.context")->setToken($token);
  	  $session = $res->getSession()->set('_security_'.'secured_area',  serialize($token));
  	  $event = new InteractiveLoginEvent($this->get("request"), $token);
  	  $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);
  
  	  $da = $this->get('we_data_access');
  	  $sql ="select edomain from we_enterprise a,we_staff b where a.eno=b.eno and (b.login_account=? or b.openid=?)";
  	  $ds = $da->GetData("edoamin",$sql,array((string)$paras[0],(string)$paras[0]));
  	  $this->network_domain = $ds["edoamin"]["rows"][0]["edomain"];
  	  if(count($paras)==1)//打开 圈子首页
  	  {
  	  	 return $this->redirect($this->generateUrl('JustsyBaseBundle_enterprise',array("network_domain"=>$this->network_domain)));
  	  }  	  
      //打开个人主页
      $circleId = $user->get_circle_id($this->network_domain); 	  	  
  	  $this->getGroupByUser($circleId ,$user->getUserName());
  	  $this->account = $paras[1];
  	  $openuser = $this->getUserInfo($paras[1]);
  	  $openuser = $openuser["we_staff"]["rows"][0];       
      $photo_url = $this->container->getParameter('FILE_WEBSERVER_URL').$openuser["photo_path"];

      $ds = $da->GetData("both", "select f_checkAttentionWithAccount(?,?) cnt", array((string)$user->getUsername(),(string)$this->account));
      $state =  $ds["both"]["rows"][0]["cnt"];
      $trend = $user->IsFunctionTrend($this->network_domain);
    	$view = $user->IsFunctionViewTrend($this->network_domain);
      return $this->render('JustsyBaseBundle:PersonalHome:interviewee.html.twig',
        array('photo_url'=> $photo_url,'this'=>$this,'userinfo'=> $openuser,
        'curr_network_domain'=> $this->network_domain,'state'=>$state,'trend'=>($trend ?'1':'0'),'view'=>($view ?'1':'0')));
    }
  	catch(\Exception $e)
  	{
      return $this->redirect($this->generateUrl('JustsyBaseBundle_login'));
  	}
  }
  
  public function getUserInfo($username)
  {
    $DataAccess = $this->get('we_data_access');
    if(strpos($username,"@"))
    {
    	//login_account
      $sqls = array(
      "select a.login_account,a.auth_level,b.eno_level,b.vip_level,a.nick_name, a.photo_path, a.photo_path_small, a.photo_path_big, a.password, a.sex_id,
			a.dept_id, a.eno,a.fafa_jid,a.duty,a.work_phone,a.mobile, a.total_point,
			date_format(a.birthday,'%Y-%c-%d') birthday, b.edomain, b.ename, b.eshortname, c.dept_name ,a.openid,a.t_code, a.we_level
			from we_staff a
			  join we_enterprise b on a.eno=b.eno
			  left join we_department c on a.eno=c.eno and a.dept_id=c.dept_id 
			where a.state_id='1' and a.login_account=?",
			      "select a.login_account, c.circle_id, c.circle_name, c.network_domain, c.logo_path_small from we_staff a, we_circle_staff b, we_circle c where a.login_account=b.login_account and a.state_id='1' and b.circle_id=c.circle_id and a.login_account=?"
			    );
	  }
	  else
	  {
	  	//openid
	    $sqls = array(
	      "select a.login_account,a.auth_level,b.eno_level,b.vip_level,a.nick_name, a.photo_path, a.photo_path_small, a.photo_path_big, a.password, a.sex_id,
				a.dept_id, a.eno,a.fafa_jid,a.duty,a.work_phone,a.mobile, a.total_point,
				date_format(a.birthday,'%Y-%c-%d') birthday, b.edomain, b.ename, b.eshortname, c.dept_name ,a.openid,a.t_code, a.we_level
				from we_staff a
				  join we_enterprise b on a.eno=b.eno
				  left join we_department c on a.eno=c.eno and a.dept_id=c.dept_id 
				where a.state_id='1' and a.openid=?",
	      "select a.login_account, c.circle_id, c.circle_name, c.network_domain, c.logo_path_small from we_staff a, we_circle_staff b, we_circle c where a.login_account=b.login_account and a.state_id='1' and b.circle_id=c.circle_id and a.openid=?"
	    );	
		}  
	  $params = array(
      array(((String)$username)),
      array(((String)$username))
    );
    $dataset = $DataAccess->GetDatas(array("we_staff", "we_circle"), $sqls, $params);
    return  $dataset;	
  }
  
  public function loadUserByUsername($username)
  {
    $dataset = $this->getUserInfo($username);
    $DataAccess = $this->get('we_data_access');
    if ($dataset && $dataset["we_staff"]["recordcount"] > 0)
    {
      $we_staff_row = $dataset["we_staff"]["rows"][0];
      
      $password = $we_staff_row['password'];
      $salt = $we_staff_row['login_account'];
      $roles = array('ROLE_USER');
      
      $sqls=array();
	    $params=array();
	    //获取用户角色和对应功能点数据
	    $sqls[]="select DISTINCT d.`code`,d.`name`
	    from we_staff_role a ,we_role b, we_role_function c,we_function d 
			where a.roleid=b.id and b.id=c.roleid and c.functionid=d.id and a.staff=? ;";
			//获取用户角色
			$sqls[]="select DISTINCT b.name,b.code from we_staff_role a,we_role b where a.roleid=b.id and a.staff=?";
			$params[] = array(((String)$salt));
	    $params[] = array(((String)$salt));
	    $ds=$DataAccess->GetDatas(array("we_function","we_role"),$sqls,$params);
      
      $us = new UserSession($we_staff_row['login_account'], $password, $salt, $roles);
      $us->nick_name = $we_staff_row['nick_name'];
      //$us->identify = $we_staff_row['identify'];
      $us->photo_path = $we_staff_row['photo_path'];
      $us->photo_path_small = $we_staff_row['photo_path_small'];
      $us->photo_path_big = $we_staff_row['photo_path_big'];
      $us->dept_id = $we_staff_row['dept_id'];
      $us->dept_name = $we_staff_row['dept_name'];
      $us->dept_name = $us->dept_name==null?"[未设置部门]":$us->dept_name;
      $us->eno = $we_staff_row['eno'];
      $us->fafa_jid = $we_staff_row['fafa_jid'];
      $us->duty = $we_staff_row['duty'];
      $us->work_phone = $we_staff_row['work_phone'];
      $us->mobile = $we_staff_row['mobile'];
      $us->birthday = $we_staff_row['birthday'];
      $us->sex_id = $we_staff_row['sex_id'];
      $us->openid = $we_staff_row['openid'];
      $us->t_code = trim(DES::decrypt($we_staff_row['t_code']));
      $us->edomain = $we_staff_row['edomain'];
      $us->ename = $we_staff_row['ename'];
      $us->eshortname = $we_staff_row['eshortname'];
      $us->total_point = $we_staff_row['total_point'];
      $us->level = \Justsy\BaseBundle\Common\ExperienceLevel::getLevel($us->total_point);
      $us->vip_level = empty($we_staff_row['vip_level'])?'1':$we_staff_row['vip_level'];
      $us->auth_level= empty($we_staff_row['auth_level'])?'J':$we_staff_row['auth_level'];
      if($us->auth_level!='S')
      {
          	$us->vip_level =  $us->level;
      }
      $us->eno_level= $we_staff_row['eno_level'];
      $us->we_level = $we_staff_row['we_level'];
      foreach ($dataset["we_circle"]["rows"] as &$row) 
      {
        $us->circle_ids[] = $row['circle_id'];
        $us->circle_names[] = $row['circle_name'];
        $us->network_domains[] = $row['network_domain'];
        $us->circle_logo_path_small[] = $row['logo_path_small'];    
      }
      $us->manager_circles=$this->get_manager_circles($we_staff_row['login_account']);
      foreach ($ds["we_function"]["rows"] as &$row){
      	$us->function_names[] = $row['name'];
      	$us->function_codes[] = $row['code'];
      	$us->function_array[]=array("name"=>$row['name'],"code"=>$row['code']);
      }
      foreach ($ds["we_role"]["rows"] as &$row){
      	$us->role_names[] = $row['name'];
      	$us->role_codes[] = $row['code'];
      	$us->role_array[]=array("name"=>$row['name'],"code"=>$row['code']);
      }     
      return $us;
    }
    else
    {
      return null;
    }
  }
      public function get_manager_circles($username)
    {
    	$DataAccess = $this->get('we_data_access');
    	$sql='select network_domain
    	   from we_circle where  create_staff = ? or manager=? ';
    	$dataset=$DataAccess->GetData('we_circle',$sql,array((string)$username,(string)$username));
    	
    	if($dataset['we_circle']['recordcount']>0)
    	{
    		return array_map(function ($row) 
          {
           return $row["network_domain"];
          }, $dataset['we_circle']['rows']);
    		}
    	else
    	{
    		  return array();
    	}
    	
    }
  //访问他人页面 $account被访问者的账号
  public function intervieweeAction($account)
  {
  	$request=$this->get("request");
    $this->network_domain=$request->get("network_domain");   	
    $user = $this->get('security.context')->getToken()->getUser();
    $circleId = $user->get_circle_id($request->get("network_domain"));  
    $this->getGroupByUser($circleId,$user->getUserName());
    $this->account = $account;
    $Obj = new UserProvider($this);
    $userinfo = $Obj->loadUserByUsername($this->account);
    $da = $this->get('we_data_access');
    $sql = "select count(1) as cnt from we_staff_atten where login_account=? and atten_id=? and atten_type='01'";
    $ds = $da->GetData("we_staff_atten",$sql,array((string)$user->getUserName(),(string)$account));
    if ($ds && $ds['we_staff_atten']['rows'][0]['cnt'] > 0)
    {
      $state = '1';
      $sql="select count(1) as snt from we_staff_atten where login_account=? and atten_id=? and atten_type='01'";
      $ds = $da->GetData("we_staff_atten",$sql,array((string)$account,(string)$user->getUserName()));
      if($ds['we_staff_atten']['recordcount']>0)
      	$state='2';
    }
    else
    {
      $state = '0';
    }
    $self = $user->getUserName()==$account;
    $photo_url = $this->container->getParameter('FILE_WEBSERVER_URL').$userinfo->photo_path;
    $trend = $user->IsFunctionTrend($this->network_domain);
    $view = $user->IsFunctionViewTrend($this->network_domain);
    return $this->render('JustsyBaseBundle:PersonalHome:interviewee.html.twig',
      array('photo_url'=> $photo_url,'this'=>$this,'userinfo'=>$userinfo,
      'curr_network_domain'=>$this->network_domain,'state'=>$state,'self'=>$self,'view'=>($view?'1':'0'),'trend'=>($trend?1:0),'group_s'=>$user->IsExistsFunction("GROUP_S"),'group_c'=>$user->IsFunctionCreateGroup($this->network_domain)));
  }
  //查看他人的主页 获取会话，type all:所有动态，atten:关注的，publish:发布的
  public function getConversVisitorAction($type, $network_domain, $account)
  {
  	$request=$this->get("request");
  	$da = $this->get('we_data_access');
  	$user = $this->get('security.context')->getToken()->getUser();
    $circleId = $user->get_circle_id($network_domain);   	
    $endid = $request->get('endid');
    $pre450num = $request->get('pre450num');
    $pageindex = $request->get('pageindex');
    $pagesize  = 45;
    $sql = '';
    $trend = $user->IsFunctionTrend($network_domain);
    //判断是否同一企业
    $same_eno = "select (select eno from we_staff a where a.login_account=?) me_eno,(select eno from we_staff a where a.login_account=?) ta_eno";
    $d_tmp = $da->GetData("eno",$same_eno,array((string)$user->getUserName(),(string)$account));
    $issameeno = false;
    if($d_tmp["eno"]["rows"][0]["me_eno"]==$d_tmp["eno"]["rows"][0]["ta_eno"])
       $issameeno = true;
    $para = array();
    if ($type == 'all')
    {
      $sql = "select a.conv_root_id from we_convers_list a 
        where a.conv_id=a.conv_root_id and a.post_to_circle=?  
        and a.post_to_group in ('ALL', case when a.login_account=? then 'PRIVATE' else '' end)
        and login_account=?";
      $para[] = (string)$circleId;      
      $para[] = (string)$user->getUserName();  
      $para[] = (string)$account;
      if(!$issameeno)
      {
          //过滤官方发布
          $sql.= " and not exists(select 1 from  we_official_publish where info_id=a.conv_root_id)";
      }
    }
    else if ($type == 'atten')
    {
      $sql = "select a.conv_root_id 
        from we_convers_list a, we_staff_atten b
        where a.conv_id=a.conv_root_id
          and a.login_account=b.atten_id 
          and b.atten_type='01'
          and a.post_to_group = 'ALL'
          and a.post_to_circle=?
          and b.login_account=?";
      $para[] = (string)$circleId;
      $para[] = (string)$account;
    }
    else if ($type == 'publish')
    {
      $sql = "select a.conv_root_id 
        from we_convers_list a
        where a.conv_id=a.conv_root_id
          and a.post_to_circle=?
          and a.login_account=?";
      $para[] = (string)$circleId;
      $para[] = (string)$account;
      if(!$issameeno)
      {
          //过滤官方发布
          $sql.= " and not exists(select 1 from  we_official_publish where info_id=a.conv_root_id)";
      }      
    }
    if ($pre450num)
    {
      $sql = "select count(*) c from ($sql limit 0, 450) as _ttt_";
      $ds = $da->GetData("we_convers_list", $sql, $para);
      $re = array("pre450num" => $ds["we_convers_list"]["rows"][0]["c"]);
      $response = new Response(json_encode($re));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }

    $sql .= " order by a.post_date desc";
    if ($pageindex){}else {$pageindex=1;}
    $pagestart = ($pageindex-1)*$pagesize;
    $sql .= " limit $pagestart, 100 ";  
    
    $sql = " select * from ($sql) as _ttt_ where 1=1 ";
    if ($endid)
    {
      $sql .= " and (0+conv_root_id)<? ";
      $para[] = (float)$endid;
    }      
    
    $sql .= " limit 0, 15 ";

    $da = $this->get('we_data_access');
    $ds = $da->GetData("we_convers_list", $sql, $para);
    //生成html返回
    $conv_root_ids = array_map(function($row)
    {
      return $row["conv_root_id"];
    }, $ds["we_convers_list"]["rows"]);
    return $this->forward("JustsyBaseBundle:CDisplayArea:getConv", array("conv_root_ids"=>$conv_root_ids,'trend'=> $trend));
  }
  //访问群组页面
  public function groupAction($network_domain,$groupid)
  {
  	$request=$this->get("request");
    $user = $this->get('security.context')->getToken()->getUser();
    $this->network_domain=$network_domain;
    $circleId = $user->get_circle_id($this->network_domain);
    $this->getGroupByUser($circleId,$user->getUserName());
    //获取群的信息
    $da = $this->get('we_data_access');
    $sql = "select * from we_groups where group_id=?";
    $groupinfo = $da->GetData("info",$sql,array((string)$groupid));
    $sql = "select b.login_account,b.nick_name,b.photo_path from we_group_staff a,we_staff b where a.login_account=b.login_account and a.group_id=?";
    $groupstaffs = $da->GetData("groupstaffs",$sql,array((string)$groupid));
    return $this->render('JustsyBaseBundle:PersonalHome:group.html.twig',array('curr_network_domain'=>$this->network_domain, 'this'=>$this,'path'=> $this->container->getParameter('FILE_WEBSERVER_URL'),'staffs'=>$groupstaffs["groupstaffs"]["rows"],'groupinfo'=> (count($groupinfo["info"]["rows"])? $groupinfo["info"]["rows"][0]:array())));
  }
  
//  public function addrlistAction()
//  {
//  	$request=$this->get("request");
//    $user = $this->get('security.context')->getToken()->getUser();
//    return $this->render('JustsyBaseBundle:Addrlist:index.html.twig',array('network_domain'=>$user->{"edomain"},'eno'=>$user->{"eno"},'uid'=>$user->getUserName()));
//  }
}