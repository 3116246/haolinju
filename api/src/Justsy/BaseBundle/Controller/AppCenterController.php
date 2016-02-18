<?php

namespace Justsy\BaseBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Login\UserSession;
use Justsy\BaseBundle\Management\Staff;
use Justsy\BaseBundle\DataAccess\SysSeq;


class AppCenterController extends Controller
{   
	public $groups=null;
  public function indexAction($network_domain,$appid)
  {

  	try{
  	 	$user = $this->get('security.context')->getToken()->getUser();
  	 	$this->get("logger")->err($this->getrequest());
  	 	$appid = trim(DES::decrypt($appid));
  	 	//$this->get("logger")->err($appid);
	  	if($appid=="index")
	  	{
	  		  $checkcode = $user->getAppSig($appid,DES::$key);
	  	 	  return $this->render("JustsyBaseBundle:AppCenter:wefafaHeader.html.twig",
	  	 	      array('curr_network_domain'=> $network_domain,
	  	 	      'error'=>"",
	  	 	      'weburl'=>"",
	  	 	      'checkcode'=>$checkcode));
	  	}  	 
  	 $da = $this->get("we_data_access");
  	 $sql = "select a.*,(select eno from we_enterprise where eno=a.appdeveloper and edomain='fafatime.com') dev from we_appcenter_apps a where a.appid=? ";
  	 $ds = $da->GetData("app",$sql,array((string)$appid));  	 
  	 $checkcode = $user->getAppSig($appid,$ds["app"]["rows"][0]["appkey"]);
  	 if($checkcode =="")
  	 {
  	 	  return $this->render("JustsyBaseBundle:AppCenter:index.html.twig",
  	 	      array('curr_network_domain'=> $network_domain,
  	 	      'error'=>"请向管理获取你的openid！",
  	 	      'weburl'=>"",
  	 	      'checkcode'=>""));  	 	
  	 }
  	 else if(empty($ds)|| count($ds["app"]["rows"])==0)
  	 {
  	 	  return $this->render("JustsyBaseBundle:AppCenter:index.html.twig",
  	 	      array('curr_network_domain'=> $network_domain,
  	 	      'error'=>"应用不存在或者未认证！",
  	 	      'weburl'=>"",
  	 	      'checkcode'=>""));
  	 }
  	 else
  	 {
  	 	 $apptype = $ds["app"]["rows"][0]["apptype"];
  	 	 if($ds["app"]["rows"][0]["appdeveloper"]!=$user->eno){
	  	 	 if( $apptype!="4" || ($apptype=="4" && empty($ds["app"]["rows"][0]["dev"])))
	  	 	 {
	  	 	 	   //判断当前企业是否已订阅该应用
	  	 	 	   $sql = "select 1 from we_app_subscibe A ,we_staff B where A.appid=? and A.objectid=B.eno and B.login_account=? and B.state_id='1'";
	  	       $t_ds = $da->GetData("t_app",$sql,array((string)$appid,(string)$user->getUsername()));
	  	       if(count($t_ds["t_app"]["rows"])==0)
	  	       {
	  	       	   return $this->render("JustsyBaseBundle:AppCenter:hint.html.twig",array('curr_network_domain'=>$network_domain,'nick'=>$user->nick_name,'jid'=>$user->fafa_jid));
	  	       }
	  	 	 }
  	 	 }
  	 	 //到应用中心获取当前应用的基本信息，主要获取入口地址
  	 	 $weburl = $ds["app"]["rows"][0]["url"];   	 	 	 	 
  	 	 if(strpos($weburl,"http://")===false && strpos($weburl,"https://")===false) //内部应用：通讯录。直接跳转内部页面
  	 	 {
  	 	 	  if(strpos($weburl,"html.twig")===false)return $this->forward($weburl, array("network_domain" => $network_domain));
  	 		  else return $this->render($weburl,array('this'=> $this,'network_domain'=> $network_domain,'checkcode'=>$checkcode,'appname'=>$ds["app"]["rows"][0]["appname"],'logo'=>$ds["app"]["rows"][0]["logo"]));
  	 	 }
  	 	 else{
  	 	 $weburl = strpos($weburl,"?")>0 ? $weburl: $weburl."?1=1";
       return $this->render("JustsyBaseBundle:AppCenter:index.html.twig",array('curr_network_domain'=> $network_domain,'weburl'=>$weburl,'checkcode'=>$checkcode,'appname'=>$ds["app"]["rows"][0]["appname"],'logo'=>$ds["app"]["rows"][0]["logo"]));
      }
     }
    }
    catch(\Exception $e)
    {
    	  $this->get("logger")->err($e);  	  
  	 	  return $this->render("JustsyBaseBundle:AppCenter:hint.html.twig",array('curr_network_domain'=>$network_domain,'nick'=>$user->nick_name,'jid'=>$user->fafa_jid));
    }
  }
  
  //新移动应用首页
  public function newindexAction()
  {
     return $this->render("JustsyBaseBundle:AppCenter:appindex.html.twig");
  }
  
  //自动跳转到指定的应用地址。主要是处理https访问http网站时，通过自动触发该页面中的a标签实现浏览器不弹出安全警告
  public function appJumpAction()
  {
  	    $request = $this->get("request");
  	    $auth = $request->get("auth");
      	return $this->render("JustsyBaseBundle:AppCenter:autoin.html.twig",array('url'=> $request->get("url")."&auth=".$auth));
  }
  public function authAction()
  {   
  	 $request = $this->get("request");
  	 //获取应用ID和回调地址
  	 $appid = $request->get("appid");
  	 $url = $request->get("url");
     $da = $this->get("we_data_access");
  	 $sql = "select * from we_appcenter_apps where appid=? and state='1'";
  	 $ds = $da->GetData("app",$sql,array((string)$appid));
  	 $error="";
  	 $appname="";
  	 if($ds && count($ds["app"]["rows"])>0){   	 
     		$appname = $ds["app"]["rows"][0]["appname"];
	   }
	   else
	   {
	    	$error = "要认证的应用无效！";
	   }
	   return $this->render("JustsyBaseBundle:AppCenter:auth.html.twig",array('c_url'=>$url,'appname'=>$appname,'error'=> $error));
  }
  
  public function authProcAction()
  {
  	$request = $this->get("request");
  	$u = $request->get("u");
  	$p = $request->get("p");
  	if(empty($u)||empty($p))
  	{
  		$re = array("s"=>0,"msg"=>"帐号或密码不能为空！");
  	}
  	else{
  		  $da = $this->get("we_data_access");
  		  $sql = "select password,openid from we_staff where login_account=?";
  		  $ds = $da->GetData("staff",$sql,array((string)$u));
  		  if($ds && count($ds["staff"]["rows"])>0)
  		  {
  		  	  $password = $ds["staff"]["rows"][0]["password"];
  		  	  $us = new UserSession($u,$password , $u, array('ROLE_USER'));
  		  	  $factory = $this->get('security.encoder_factory');
			      $encoder = $factory->getEncoder($us);
			      $password_enc = $encoder->encodePassword($p, $us->getSalt());
			      if($password!=$password_enc)
			         $re = array("s"=>0,"msg"=>"密码不正确！");
			      else
			         $re = array("s"=>1,"openid"=>$ds["staff"]["rows"][0]["openid"]);
  		  }
  		  else
  		  {
  		      	$re = array("s"=>0,"msg"=>"帐号不存在！");
  		  }        
    }
    $response = new Response(json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }  
  
  public function openidBindAction()
  {
  	 $request = $this->get("request");
  	 //获取应用ID和回调地址
  	 $appid = $request->get("appid");
  	 $openid = $request->get("openid");
  	 $url = $request->get("url");  	
  	 $userurl = $request->get("user_url");
  	 $error = $request->get("error");
     $da = $this->get("we_data_access");
  	 $sql = "select * from we_appcenter_apps where appid=? and state='1'";
  	 $ds = $da->GetData("app",$sql,array((string)$appid));
  	 $appname="";
  	 $name = "";
  	 if($ds && count($ds["app"]["rows"])>0){   	 
     		$appname = $ds["app"]["rows"][0]["appname"];
        $sql = "select nick_name from we_staff where openid=?";
  		  $ds = $da->GetData("staff",$sql,array((string)$openid)); 
  		  if($ds && count($ds["staff"]["rows"]))
  		  {
  		     $name = 	$ds["staff"]["rows"][0]["nick_name"];
  		  }
  		  else
  		     $error = "无效的OpenID！";
	   }
	   else
	   {
	    	$error = "要认证的应用无效！";
	   }  	 
     return $this->render("JustsyBaseBundle:AppCenter:openidBind.html.twig",array('openid'=> $openid,'c_url'=>$url,'u_url'=>$userurl,'nick_name'=>$name,'appname'=>$appname,'error'=> $error));
  }  
  
  public function getOpenidInfAction()
  {
  	  $request = $this->get("request");
  	  $openid = $request->get("openid");
  	  $da = $this->get("we_data_access");
  	  $sql = "select eno,dept_id,login_account,nick_name,active_date from we_staff where openid=? and state_id='1'";
  	  $ds = $da->GetData("staff",$sql,array((string)$openid));
  	  if($ds && count($ds["staff"]["rows"])>0)
  	  {
  	  	 $re = $ds["staff"]["rows"]["0"];
  	  	 $deptinfo = array();
  	  	 $deptid = $re["dept_id"];
  	  	 if(!empty($deptid))
  	  	 {
  	  	     	while(true)
  	  	     	{
  	  	     	    $sql = "select dept_id,dept_name,parent_dept_id from we_department where dept_id=?";
  	  	     	    $ds = $da->GetData("dept",$sql,array((string)$deptid));
  	  	     	    if(count($ds["dept"]["rows"])==0) break;
  	  	     	    $deptinfo[] = (string)json_encode($ds["dept"]["rows"][0]);
  	  	     	    $deptid = $ds["dept"]["rows"][0]["dept_id"];
  	  	     	    if($deptid==$ds["dept"]["rows"][0]["parent_dept_id"]) break;
  	  	     	    $deptid = $ds["dept"]["rows"][0]["parent_dept_id"];
  	  	     	}
  	  	 }
  	  	 $re["dept_list"]=($deptinfo);
  	  }
  	  else
  	     $re=array("login_account"=>"");
  	  $response = new Response(json_encode($re));
      $response->headers->set('Content-Type', 'text/json');
      return $response ;
  }
  
  public function getAppsAction($network_domain)
  {
     $request = $this->get("request");
  	 $user = $this->get('security.context')->getToken()->getUser();  	 
  	 $defaultLogo = '/bundles/fafatimewebase/images/app48.png';
  	 $da = $this->get("we_data_access");
     $sql = "select a.appkey,a.appid,a.url,a.appname,ifnull(case when LENGTH(logo)>1 then CONCAT('".$this->container->getParameter('FILE_WEBSERVER_URL')."',logo) else '$defaultLogo' end,'$defaultLogo') logo,a.show_type from we_appcenter_apps a ,we_app_type b where a.apptype=b.typeid and a.apptype not like '99%' and (b.typeid='4' or b.parentid=4 ) and state='1' and appdeveloper=(SELECT eno FROM we_enterprise where edomain='fafatime.com') union ".   //获取平台开发商提供的个人类应用
     "select a.appkey,a.appid,a.url,a.appname,ifnull(case when LENGTH(logo)>1 then CONCAT('".$this->container->getParameter('FILE_WEBSERVER_URL')."',logo) else '$defaultLogo' end,'$defaultLogo') logo,a.show_type from we_appcenter_apps a  where a.apptype!='00' and a.apptype not like '99%' and a.appdeveloper=? union ".   //获取本企业正在开发但不是微应用的应用
            "select a1.appkey,a1.appid,a1.url,a1.appname,ifnull(case when LENGTH(logo)>1 then CONCAT('".$this->container->getParameter('FILE_WEBSERVER_URL')."',logo) else '$defaultLogo' end,'$defaultLogo') logo,a1.show_type FROM we_appcenter_apps a1 ,we_app_subscibe a,we_app_userpriv b where a1.apptype!='00' and a1.apptype not like '99%' and a1.appid=a.appid and a.appid=b.appid and a.objecttype='1' and a.objectid=? and b.login_account=? union ".   //获取所在企业订阅的并取得使用权限的企业类但不是微应用应用
            "select a1.appkey,a1.appid,a1.url,a1.appname,ifnull(case when LENGTH(logo)>1 then CONCAT('".$this->container->getParameter('FILE_WEBSERVER_URL')."',logo) else '$defaultLogo' end,'$defaultLogo') logo,a1.show_type FROM we_appcenter_apps a1 ,we_app_subscibe a,we_app_userpriv b where a1.apptype!='00' and a1.apptype not like '99%' and a1.appid=a.appid and a.appid=b.appid and a.objecttype='2' and a.objectid=? and b.login_account=? union ".   //获取自己订阅的个人类但不是微应用应用
            "select a1.appkey,a1.appid,a1.url,a1.appname,ifnull(case when LENGTH(logo)>1 then CONCAT('".$this->container->getParameter('FILE_WEBSERVER_URL')."',logo) else '$defaultLogo' end,'$defaultLogo') logo,a1.show_type FROM we_appcenter_apps a1 ,we_app_subscibe a where a1.apptype!='00' and a1.apptype not like '99%' and a1.appid=a.appid and a.objecttype='1' and a.objectid=? ";//获取企业订阅的自己未取得使用授权但不是微应用的应用
  	 //判断是否可以使用企业微信推送平台
  	 $wm = new \Justsy\BaseBundle\Management\MicroAccountMgr($da,null,$user,null,null);
  	 $used = $wm->IsUseUser();
  	 if($used["use"]==1)
  	    $sql .= " union select a1.appkey,a1.appid,a1.url,a1.appname,ifnull(case when LENGTH(logo)>1 then CONCAT('".$this->container->getParameter('FILE_WEBSERVER_URL')."',logo) else '$defaultLogo' end,'$defaultLogo') logo,a1.show_type FROM we_appcenter_apps a1 where appid in('c5845cf3331c833cf5d9','8afe9e6f2d8e91dc2ff5')";//同时包含测试和正式环境的应用ID
  	 $ds = $da->GetData("app","select * from (".$sql.") a order by appname",array(
  	       (string)$user->eno,
  	       (string)$user->eno,
  	       (string)$user->getUsername(),
  	       (string)$user->getUsername(),
  	       (string)$user->getUsername(),
  	       (string)$user->eno
  	  ));  	  
  	 $list=array();
  	 for($i=0;$i<count($ds["app"]["rows"]);$i++)
  	 {
  	 	 $r = $ds["app"]["rows"][$i];
  	 	 $checkcode = $user->getAppSig($r["appid"],$r["appkey"]);
  	 	 $weburl = $r["url"];
			 //判断 是否站内应用(embed)还是外部集成应用，外部集成应用需要新打开窗口加载应用
			 $showtype = $r['show_type'];  	 	 
  	 	 if(( strpos($weburl,"http://")===false && strpos($weburl,"https://")===false)) //内部应用：通讯录。直接跳转内部页面
  	 	 {
	  	 	 	  if(!empty($weburl) && ( strpos($weburl,":")===false))
	             $weburl=$this->generateUrl($weburl,array("network_domain" => $network_domain))."?auth=$checkcode";
	          else
	             $weburl="javascript:inapp('".DES::encrypt($r["appid"])."')";
  	 	 }
  	 	 else
  	 	    $weburl = $this->container->getParameter('fafa_APPCENTER_URL')."/appcenter/load/".DES::encrypt($r["appid"])."?auth=$checkcode";  	 	
  	 	 $list[]= array("logo"=>$r["logo"],"appid"=>$r["appid"],"appname"=>$r["appname"],"url"=>$weburl,"show_type"=>$r["show_type"]); 
  	 }
  	 return $this->render("JustsyBaseBundle:AppCenter:myAppList.html.twig",array('list'=>$list,"checkcode"=> $user->getAppSig("index",DES::$key)));
  }
  
   //编辑应用程序布局页面
  public function appEditLayoutAction()
  {
  	$request = $this->get("request");
  	$re=$this->getBasicAction();
  	$theList=$re->getContent();
  	$maxsortid = $this->getMaxSortId();
  	return $this->render("JustsyBaseBundle:AppCenter:appedition.html.twig",array('theList'=> $theList,'maxsortid'=>$maxsortid));
  }
  
	public function getMaxSortId()
	{
		 $da = $this->get('we_data_access');
		 $curuser = $this->get('security.context')->getToken()->getUser();
	   $eno =	$curuser->eno;
	   $sql = "select max(sortid)+1 maxsortid from we_appcenter_apps where apptype like '99%' and appdeveloper=?";
	   $maxid = "";
	   try{
	   $ds = $da->GetData('table',$sql,array((string)$eno));
	   if ($ds && $ds["table"]["recordcount"]>0 )
	     $maxid = $ds["table"]["rows"][0]["maxsortid"];
	   }
	   catch(\Exception $e){
	   	  $maxid = "";
	   }
	   return $maxid;	
	}
  
public function getAppListAction()
{
	  $list = $this->getAppList();
    $resp = new Response(json_encode($list));
    $resp->headers->set('Content-Type', 'text/json');
	  return $resp;
}

public function getAppList()
{
	 $da = $this->get('we_data_access');
	 $curuser = $this->get('security.context')->getToken()->getUser();
   $eno  = $curuser->eno;
   $user = $curuser->nick_name;
   $account = $curuser->getUsername();
   $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
   $sql = "select appid,appname,apptype,case when logo is null or logo='' then null else concat('$FILE_WEBSERVER_URL',logo) end applogo,
                  ifnull(version,'') version,createstaffid createstaff,publishdate `date`,publishstaff staff,
                  case when createstaffid=? then 1 else 0 end isowner,f_app_role(?,b.appid) role 
           from we_appcenter_apps b where apptype like '99%' and appdeveloper=? order by sortid asc;";
   $para = array((string)$user,(string)$account,(string)$eno);
   $ds = $da->GetData('applist',$sql,$para);
   $list = array();
   if ($ds && $ds["applist"]["recordcount"]>0 )
     $list = $ds["applist"]["rows"];
   return $list;
}

  public function appbizproxyurlAction(Request $request)
  {
      $appid = $request->get("appid");
      $action = $request->get("action");
      $da = $this->get('we_data_access');
      if($action=="download")
      {
        $sql = "select a.*,b.number from we_appcenter_apps a,we_micro_account b where a.appid=b.micro_source and a.appid=?";
        $ds = $da->GetData("t",$sql,array((string)$appid));  
        $ds["t"]["rows"][0]["appkey"] = DES::encrypt($ds["t"]["rows"][0]["appkey"]);    
        $appInfo =array('s'=>1,"data"=> $ds["t"]["rows"][0]);  
        $resp = new Response(json_encode($appInfo));
        $resp->headers->set('Content-Type', 'text/json');
        return $resp; 
      }
      else
      {
          $result = array("s"=>1);
          try{
          	$sql = "select count(1) cnt from we_appcenter_apps a ";
          	$ds = $da->GetData("t",$sql,array());
          	$curuser = $this->get('security.context')->getToken()->getUser();
          	$maxNo = $curuser->eno. ((int)$ds["t"]["rows"][0]["cnt"]+1);
            $sql = "select a.* from we_appcenter_apps a where a.appid=?";
            $ds = $da->GetData("t",$sql,array((string)$appid));

            if($ds && count($ds["t"]["rows"])>0)
            {
              $appInfo = $ds["t"]["rows"][0];
              $factory = $this->get('security.encoder_factory');
              $dm      = $this->get('doctrine.odm.mongodb.document_manager');
              
              $account =    strtolower( "mapp".$maxNo."@".$curuser->edomain);  
              if(strpos($account,".")===false) 
              	    $account = $account.".com";
              $MicroAccountMgr=new \Justsy\BaseBundle\Management\MicroAccountMgr( $da, $this->get('we_data_access_im'),$curuser,$this->get("logger"), $this->container); 
              $re=$MicroAccountMgr->register("",$account,$appInfo["appname"],"1","1","","1","","1",$appInfo["appkey"],$appInfo["logo"],$appInfo["logo"],$appInfo["logo"],$factory,$dm,$appid);
            }
          }
          catch(\Exception $e)
          {
              $result = array("s"=>0,'msg'=>$e->getMessage());
          }
          $resp = new Response(json_encode($result));
          $resp->headers->set('Content-Type', 'text/json');
          return $resp;
      }
  }
  
  
  public function oldeditAppsAction(Request $request)
  {
  	 $appid = $request->get("appid");
  	 $appname = $request->get("appname");
  	 $logo = $request->get("applogo");
  	 $apptype = $request->get("apptype");
  	 $appsubtype = $request->get("appsubtype");
  	 $subscribe = $request->get("subscribe");
  	 $appdesc = $request->get("appdesc");
     $p_account = $request->get("p_account");  //业务代理帐号
     $p_password = $request->get("p_password"); //业务代理密码
     $bindurl = $request->get("bindurl"); 
     $sortid = $request->get("sortid");
     $functiontype=$request->get("functiontype");
     $native_code=$request->get("native_code");

     $curuser = $this->get('security.context')->getToken()->getUser();
     $appdeveloper =	$curuser->eno;
     $username = $curuser->getUsername(); 
     $sql = "";
     $para = array();
     $da = $this->get('we_data_access');
     $result = array();
     try
     {  
     	if ($this->existsAppName($appdeveloper,$appid,$appname))
     	{
	     	  	$result = array("s"=>"exists");
	    }
	    else
	    {
  		    if (empty($appid)) {
  		    	$c_flag=true;
  		    	if (!empty($p_account)) {
	             $u_staff = new Staff($da,$this->get('we_data_access_im'),$curuser,$this->get('logger'));
	             $c_flag =  $u_staff->cratestaff($curuser->eno,$appname,$p_account,$p_password,$this->get('security.encoder_factory'));
            }
            if($c_flag) {
    				   $appid = Utils::getAppid($appdeveloper,$username);
    				   $appkey = Utils::getAppkey();
    			     $sql = "insert into we_appcenter_apps(appid,appkey,logo,appname,state,appdeveloper,subscribe,appdesc,bindurl,apptype,sortid,functiontype,native_code,createstaffid)values(?,?,?,?,1,?,?,?,?,'99',?,?,?,?)";
    			     $para = array((string)$appid,(string)$appkey,(string)$logo,(string)$appname,(string)$appdeveloper,(string)$subscribe,(string)$appdesc,(string)$bindurl,(string)$sortid,$functiontype,$native_code,(string)$curuser->getUserName());
    			     $result = array ("s"=>"add","appid"=>$appid);
            }
            else
              $result = array("s"=>"error","msg"=>"帐号已被使用!");
		    }
		    else
		    {
  		      $sql = "update we_appcenter_apps set appname=?,logo=?,subscribe=?,appdesc=?,bindurl=?,sortid=?,functiontype=?,native_code=? where appid=?";
  		     	$para = array((string)$appname,(string)$logo,(string)$subscribe,(string)$appdesc,(string)$bindurl,(string)$sortid,$functiontype,$native_code,(string)$appid);
  		     	$result = array("s"=>"edit","appid"=>$appid);
		    }
		    if ($sql!="")
		        $da->ExecSQL($sql,$para);
	    }
     }
     catch(\Exception $e)
     {
     	 $result = array("s"=>"error","msg"=>$e->getMessage());
     }     
     $resp = new Response(json_encode($result));
     $resp->headers->set('Content-Type', 'text/json');
     //返回值说明：1＝>添加成功;2=>修改成功;3=>已存在应用名称；0=>出错
		 return $resp;
  }
  
  //创建/编辑移动应用程序
  public function editAppsAction(Request $request)
  {
  	 $appid = $request->get("appid");
  	 $appname = $request->get("appname");
  	 $logo = $request->get("applogo");
  	 $apptype = $request->get("apptype");
  	 $appdesc = $request->get("appdesc");
     $bindurl = $request->get("bindurl"); 
     $sortid = $request->get("sortid");
     $createstaff = $request->get("createstaff");     
     $curuser = $this->get('security.context')->getToken()->getUser();
     $appdeveloper =	$curuser->eno;
     $username = $curuser->getUsername(); 
     $sql = "";
     $para = array();
     $da = $this->get('we_data_access');
     $result = array();
     try
     {  
     	if ($this->existsAppName($appdeveloper,$appid,$appname))
     	{
	     	 $result = array("s"=>"exists");
	    }
	    else
	    {
	      if (empty($appid)) {
	         	$appid = Utils::getAppid($appdeveloper,$username);
    			$appkey = Utils::getAppkey();
    			//$MicroAccountMgr=new MicroAccountMgr($da,$this->get('we_data_access_im'),$curuser,$this->get("logger"), $this->container);
    			//$MicroAccountMgr->register("",$number,$name,$type,$micro_use,$introduction,$concern_approval,$salutatory,$level,$password,$filename48,$filename120,$filename24,$factory,$dm,$appid);
    			$sql = "insert into we_appcenter_apps(appid,appkey,logo,appname,state,appdeveloper,appdesc,bindurl,apptype,sortid,createstaffid)values(?,?,?,?,1,?,?,?,?,?,?)";
    			$para = array($appid,$appkey,$logo,$appname,$appdeveloper,$appdesc,$bindurl,$apptype,$sortid,$createstaff);
    			$result = array ("s"=>"add","appid"=>$appid);
		    }
		    else {
  		     	$sql = "update we_appcenter_apps set appname=?,logo=?,appdesc=?,bindurl=?,sortid=?,createstaffid=? where appid=?";
  		     	$para = array($appname,$logo,$appdesc,$bindurl,$sortid,$createstaff,$appid);
  		     	$result = array("s"=>"edit","appid"=>$appid);
		    }
		    if ($sql!="")
		      $da->ExecSQL($sql,$para);
	    }
     }
     catch(\Exception $e)
     {
     	 $result = array("s"=>"error","msg"=>$e->getMessage());
     }     
     $resp = new Response(json_encode($result));
     $resp->headers->set('Content-Type', 'text/json');
     //返回值说明：1＝>添加成功;2=>修改成功;3=>已存在应用名称；0=>出错
		 return $resp;
  	
  }

  public function deleteAppsAction(Request $request)
  {
    $appid = $request->get("appid");
    $result = array("s"=>1,"msg"=>"");
    if(empty($appid))
    {
      $result["s"]=0;
      $result["msg"]="appid is null";
      $response = new Response(json_encode($result));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }    
    $da = $this->get('we_data_access');
    $da_im = $this->get('we_data_access_im');
    $curuser = $this->get('security.context')->getToken()->getUser();
    //只能删除自己企业的应用 
    $sql="select 1 from we_appcenter_apps where appid=? and appdeveloper=?";
    $ds = $da->GetData("t",$sql,array((string)$appid,(string)$curuser->eno));
    if(count($ds["t"]["rows"])==0)
    {
        $result["s"]=0;
        $result["msg"]="应用未找到或没有删除权限";
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'text/json');
        return $response;          
    }
    try
    {
        $sql = "select * from we_apps_resource where appid=?";
        $ds = $da->GetData("t",$sql,array((string)$appid));
        if(count($ds["t"]["rows"])>0)
        {
          for($i=0;$i<count($ds["t"]["rows"]);$i++)
          {
              $fileid = $ds["t"]["rows"][$i]["fileid"];
              $this->removeFile($fileid);
          }
        }
        $sql = "select number,jid from we_micro_account where micro_source=?";
        $ds = $da->GetData("t",$sql,array((string)$appid));

        $sqls= array();
        $params =  array();
        $sqls[]="delete from we_appcenter_apps where appid=?";
        $params[]= array((string)$appid);

        $sqls[]="delete from we_apps_publish where appid=?";
        $params[]= array((string)$appid);

        $sqls[]="delete from we_app_userpriv where appid=?";
        $params[]= array((string)$appid);    

        $sqls[]="delete from we_app_subscibe where appid=?";
        $params[]= array((string)$appid); 

        $sqls[]="delete from we_app_oauth_sessions where appid=?";
        $params[]= array((string)$appid); 

        $sqls[]="delete from we_apps_resource where appid=?";
        $params[]= array((string)$appid);     

        $sqls[]="delete from we_micro_account where micro_source=?";
        $params[]= array((string)$appid);
        
        $sqls[]="delete from we_app_unitalloc where appid=?";
        $params[]= array((string)$appid);
        
        $sqls[]="delete from we_app_developer where appid=?";
        $params[]= array((string)$appid);
        
        if(count($ds["t"]["rows"])>0)
        {
            $sqls[]="delete from we_staff where login_account=?";
            $params[]= array((string)$ds["t"]["rows"][0]["number"]);
            $da_im->ExecSQL("delete from users where username=?",array((string)$ds["t"]["rows"][0]["jid"]));
        }
        $da->ExecSQLs($sqls,$params);
    }
    catch(\Exception $e)
    {
        $result["s"]=0;
        $result["msg"]=$e->getMessage();
    }
    $response = new Response(json_encode($result));
    $response->headers->set('Content-Type', 'text/json');
    return $response;      
  }
  
  //保存应用Logo
  public function savePhotoAction(Request $request)
  { 
  	 $appid = $request->get("appid");
  	 $session = $this->get('session'); 
     $filename120 = $session->get("avatar_big");
     $result = array();
     try
     {
	      $dm = $this->get('doctrine.odm.mongodb.document_manager');
        if (!empty($filename120)) $filename120= $this->saveFile($filename120,$dm);
	      $session->remove("avatar_big");   
	      $result = array('s'=>1,'file'=> $filename120);	      
	      if ( !empty($appid) )  //修改操作时的处理
	      {
		    //判断原来的是否存在，存在则删除图片
		      $sql="select logo applogo from we_appcenter_apps where appid=?";
	        $da = $this->get('we_data_access');
	        $ds = $da->GetData('table',$sql,array((string)$appid));
	        if ($ds && $ds["table"]["recordcount"]>0)
	        	 $this->removeFile($ds["table"]["rows"][0]["applogo"],$dm);
	        //修改applogo字段
	        $sql = "update we_appcenter_apps set logo=? where appid=?";
	        $da->ExecSQL($sql,array((string)$filename120,(string)$appid));	        
        }
     }
     catch(\Exception $e)
     {
       	$this->get("logger")->err($e);
     	$result = array("s"=>0);
     }
	   $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;
  }

  public function resetpassAction($appid)
  {
  		$request = $this->get("request");
  		$pv = $request->get("pv");
  		$result = array();
  		$result["s"] = "0";
  		if(empty($appid) || empty($pv))
  		{  			
  			$result["msg"] = "参数无效!";
  		}
  		else
  		{
  			$da = $this->get('we_data_access');
  			$curuser = $this->get('security.context')->getToken()->getUser();
  			try
  			{
	  			$sql="select b.login_account from we_appcenter_apps a,we_staff b where a.appname=b.nick_name and a.appid=?";
	  			$ds = $da->GetData("t",$sql,array((string)$appid));
	  			if($ds==null || count($ds["t"]["rows"])==0 )
	  			{	  				
	  				$result["msg"] = "appid参数无效!";
	  			}
	  			else
	  			{
	  				$u_staff = new Staff($da,$this->get('we_data_access_im'),$curuser,$this->get('logger'));
	  				$u_staff->changepassword($ds["t"]["rows"][0]["login_account"],$pv,$this->get('security.encoder_factory'));
	  				$result["s"] = "1";
	  				$result["msg"] = $pv;
	  			}
  			}
  			catch(\Exception $e)
  			{
				$result["msg"] = $e->getMessage();
  			}
  		}
		$response = new Response(json_encode($result));
     	$response->headers->set('Content-Type', 'text/json');
     	return $response;  		
  }

  public function updateconfigfileAction($appid)
  {
		$error = "";
		$msg = "";
		$re=array('s'=>'1','m'=>'',"fileid"=>'',"msg"=>"","filename"=>'');
		$fileElementName = 'configfilename';
		$da = $this->get('we_data_access');
		if(!empty($_FILES[$fileElementName]['error']))
		{
			switch($_FILES[$fileElementName]['error'])
			{

				case '1':
					$error = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
					break;
				case '2':
					$error = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
					break;
				case '3':
					$error = 'The uploaded file was only partially uploaded';
					break;
				case '4':
					$error = 'No file was uploaded.';
					break;

				case '6':
					$error = 'Missing a temporary folder';
					break;
				case '7':
					$error = 'Failed to write file to disk';
					break;
				case '8':
					$error = 'File upload stopped by extension';
					break;
				case '999':
				default:
					$error = 'No error code avaiable';
			}
		}elseif(empty($_FILES[$fileElementName]['tmp_name']) || $_FILES[$fileElementName]['tmp_name'] == 'none')
		{
			$error = 'No file was uploaded..';
		}else 
		{
				try{
					$filename=$_FILES[$fileElementName]['name'];
					$filesize=$_FILES[$fileElementName]['size'];
					$filetemp=$_FILES[$fileElementName]['tmp_name'];
					if($re['s']=='1'){
						$fileid=$this->saveCertificate($filetemp,$filename);
						if(empty($fileid))
						{
							$re['s']='0';
							$re['msg']='文件上传失败';
							//$fileid="523fe22a7d274a2d01000000";
						}
						if($re['s']=='1')
						{
							//取出旧文件
							$sql="select version, configfileid from we_appcenter_apps where appid=? and configfileid is not null and configfileid!=''";
							$params=array($appid);
							$oldfileid='';
              $version = "";
							$ds=$da->Getdata('fileid',$sql,$params);
							if($ds['fileid']['recordcount']>0){
								$oldfileid=$ds['fileid']['rows'][0]['configfileid'];
                $version = $ds['fileid']['rows'][0]["version"];
							}
							if(!empty($oldfileid)){
								//删除monggo
								$this->removeFile($oldfileid);
							}              
              if(empty($version)) $version=1;
              else $version= (int)$version+1;
							$sql="update we_appcenter_apps set configfileid=?,version=? where appid=?";
							$params=array((string)$fileid,(string)$version,(string)$appid);
							if(!$da->ExecSQL($sql,$params))
							{
								$re['s']='0';
								$re['msg']='文件保存失败';
							}
							//else{
								$re['fileid']=$fileid;
								$re['filename']=$filename;
							//}
						}
					}
				}
				catch(\Exception $e){
				}
				$msg .= " File Name: " . $_FILES[$fileElementName]['name'] . ", ";
				$msg .= " File Size: " . @filesize($_FILES[$fileElementName]['tmp_name']);
				//for security reason, we force to remove all uploaded file
				@unlink($_FILES[$fileElementName]);
		}
		$result =array("error"=>$error,"msg"=>$re['msg'],"fileid"=>$re['fileid'],"filename"=>$re['filename']);
		$response = new Response(json_encode($result));
     	$response->headers->set('Content-Type', 'text/html');
     	return $response; 		
  }
  public function saveResFileAction($appid)
  {
  	$da = $this->get('we_data_access');
  	$fileElementName="filedata";

	if($appid=="PORTAL")
	{
		$userinfo = $this->get('security.context')->getToken()->getUser();
		$appid = $appid.$userinfo->eno;
	}
  	$re=array('succeed'=>'1','m'=>'','fileid'=>'','filename'=>'');
  	try{
					$filename=$_FILES[$fileElementName]['name'];
					$filesize=$_FILES[$fileElementName]['size'];
					$filetemp=$_FILES[$fileElementName]['tmp_name'];
					if($re['succeed']=='1'){
						$fileid=$this->saveCertificate($filetemp,$filename);
						if(empty($fileid))
						{
							$re['succeed']='0';
							$re['msg']='文件上传失败';
							//$fileid="523fe22a7d274a2d01000000";
						}
						if($re['succeed']=='1')
						{
							$sql="insert into we_apps_resource (appid,fileid,name,restype,ressize,cdate) values(?,?,?,?,?,now())";
							$params=array($appid,$fileid,$this->getname($filename),$this->getprex($filename),$filesize);
							if(!$da->ExecSQL($sql,$params))
							{
								$re['succeed']='0';
								$re['msg']='文件保存失败';
							}
							else{
								$re['fileid']=$fileid;
								$re['filename']=$filename;
							}
						}
					}
				}
				catch(\Exception $e){
					$this->get('logger')->err($e);
				}
				$response = new Response(json_encode($re));
     	$response->headers->set('Content-Type', 'text/json');
     	return $response; 		
  }
  public function getResAction()
  {
  	$da = $this->get('we_data_access');
  	$request = $this->get("request");
  	
  	$appid=$request->get("appid");
  	$fileid=$request->get("fileid");
	if($appid=="PORTAL")
	{
		$userinfo = $this->get('security.context')->getToken()->getUser();
		$appid = $appid.$userinfo->eno;
	}  	
  	$sql="select * from we_apps_resource where appid=? and fileid=?";
  	$params=array($appid,$fileid);
  	$ds=$da->Getdata('info',$sql,$params);
  	$row=array();
  	if($ds['info']['recordcount']>0){
  		$row=$ds['info']['rows'][0];
  	}
  	$response = new Response(json_encode($row));
     	$response->headers->set('Content-Type', 'text/json');
     	return $response; 	
  }
  public function delResAction()
  {
  	$da = $this->get('we_data_access');
  	$request = $this->get("request");
  	$re=array('s'=>'1','m'=>'');
  	
  	
  	$appid=$request->get("appid");
  	$fileid=$request->get("fileid");
	if($appid=="PORTAL")
	{
		$userinfo = $this->get('security.context')->getToken()->getUser();
		$appid = $appid.$userinfo->eno;
	}  	
  	$sql="delete from we_apps_resource where appid=? and fileid=?";
  	$params=array($appid,$fileid);
  	
  	if(!$da->ExecSQL($sql,$params)){
  		$re['s']='0';
  		$re['m']='删除失败';
  	}
    $this->removeFile($fileid);
  	$response = new Response(json_encode($re));
     	$response->headers->set('Content-Type', 'text/json');
     	return $response; 
  }
  private function getname($filename)
  {
  	$arr=explode('.',$filename);
  	 return $arr[0];
  }
  private function getprex($filename)
  {
  	 $arr=explode('.',$filename);
  	 return $arr[count($arr)-1];
  }
  private function saveFile($path, $dm)
  { 
    $doc = new \Justsy\MongoDocBundle\Document\WeDocument();    
    $doc->setName(basename($path));
    $doc->setFile($path);
    $dm->persist($doc);
    $dm->flush();
    unlink($path);
    return $doc->getId();
  } 
  
  //决断同一用户下是否有相同应用名
  private function existsAppName($appdeveloper,$appid,$appname)
  {
  	 $result = false;
  	 $sql = "";
  	 $para = array();
  	 if ($appid=="0")  //添加时的判断
  	 {
  	 	  $sql = "select appid from we_appcenter_apps where appname=? and appdeveloper=?";
  	 	  $para = array((string)$appname,(string)$appdeveloper);
  	 }
  	 else  //修改时的判断
  	 {
  	 	  $sql = "select appid from we_appcenter_apps where appname=? and appdeveloper=? and appid !=?";
  	 	  $para = array((string)$appname,(string)$appdeveloper,(string)$appid);
  	 }
  	 $da = $this->get('we_data_access');
  	 $ds = $da->GetData('table',$sql,$para);
  	 if ($ds && $ds["table"]["recordcount"]>0)
  	   $result = true;
  	 return $result;
  }
  
  public function getBasicAction()
  {
  	$request = $this->get("request");
  	 $appid = $request->get("appid");
  	 $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
  	 $eno = $this->get('security.context')->getToken()->getUser()->eno;
  	 $da = $this->get('we_data_access');
     $sql = "select appid,appname,functiontype,native_code,logo as applogo,case when logo is null or logo='' then null else concat('$FILE_WEBSERVER_URL',logo) end logo_url,apptype apptypeid,(select typename from we_app_type where typeid=apptype) apptype,
	                  0 subtypeid,(select typename from we_app_type where typeid=apptype) subtype,subscribe,appdesc,bindurl,appid,appkey,configfileid,b.number login_account,sortid,ifnull(createstaffid,'') createstaff
             from we_appcenter_apps a left join we_micro_account b on a.appid=b.micro_source where a.appid=? and a.appdeveloper=?";
     $ds = $da->GetData('applist',$sql,array((string)$appid,(string)$eno));
     $list = array();
     if ($ds && $ds["applist"]["recordcount"]>0  )
       $list = $ds["applist"]["rows"];
     $result = new Response(json_encode($list));
     $result->headers->set('Content-Type', 'text/json');
		 return $result; 
  }
	private function removeFile($path)
	{
	    if (!empty($path))
	    {
	    	$dm = $this->get('doctrine.odm.mongodb.document_manager');
	        $doc = $dm->getRepository('JustsyMongoDocBundle:WeDocument')->find($path);
	        if(!empty($doc))
	            $dm->remove($doc);
	        $dm->flush();
	    }
	    return true;
	}  
  protected function saveCertificate($filetemp,$filename)
  {
  	try{
	  	$upfile = tempnam(sys_get_temp_dir(), "we");
	    unlink($upfile);
	    /*
	    $somecontent1 = base64_decode($filedata);
	    if ($handle = fopen($upfile, "w+")) {   
	      if (!fwrite($handle, $somecontent1) == FALSE) {   
	        fclose($handle);  
	      }  
	    }
	    */
	    if(move_uploaded_file($filetemp,$upfile)){
		    $dm = $this->get('doctrine.odm.mongodb.document_manager'); 
		    $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
		    $doc->setName($filename);
		    $doc->setFile($upfile); 
		    $dm->persist($doc);
		    $dm->flush();
		    $fileid = $doc->getId();
		    return $fileid;
		  }
		  else{
		  	return "";
		  }
	  }
	  catch(\Exception $e)
	  {
	  	$this->get('logger')->err($e);
	  	return "";
	  }
  } 
}