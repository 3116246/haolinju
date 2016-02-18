<?php

namespace Justsy\BaseBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\Common\Utils;

use Justsy\BaseBundle\weibo\SaeTOAuthV2;
use Justsy\BaseBundle\weibo\SaeTClientV2;
use Justsy\BaseBundle\weibo\SinaWeiboMgr;
class LoginController extends Controller
{  
    public function loginAction($name)
    {
      $cache = new \Justsy\BaseBundle\DataAccess\DACache($this->container);
//var_dump($name);
      $request = $this->get("request");
      if(preg_match("/\/interface(\/.*)*$/", $request->server->get("ORIG_PATH_INFO"))
        || preg_match("/\/interface(\/.*)*$/", $request->server->get("REDIRECT_URL")))
      {        
        $re = array();
        $re["returncode"] = "0001";
        
        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
      }
      else
      {
        if ($this->get('request')->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) 
        {
          $error = $this->get('request')->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
          $error = $this->get('request')->getSession()->get(SecurityContext::AUTHENTICATION_ERROR);
        }
        if ($error==null)
        {
        	 $DataAccess = $this->container->get('we_data_access');
           // $dataset = $DataAccess->GetData("domain","select domain_name from we_public_domain order by order_num limit 0,10");

           $dataset = $cache->get("LoginDomain10", "AllEn");
           if (!$dataset)
           {
              $dataset = $DataAccess->GetData("domain","select domain_name from we_public_domain order by order_num limit 0,10");
              $arrkeyvalue = array_map(function($item){ return $item["domain_name"];}, $dataset["domain"]["rows"]);
              $cache->set("LoginDomain10", "AllEn", $dataset, array("we_public_domain" => $arrkeyvalue));
           }

           $emials=($dataset["domain"]["rows"]);
           $huowa = array();
			     for($i=0;$i<count($emials);$i++)
			     {
			       $huowa[]=$emials[$i]["domain_name"];
			     }
			     $sql = " select * from (select filetype,fileversion,case when filesize is null then '未知' else  concat(cast(round(filesize/1024) as char(10)),' KB') end filesize,case filetype when 'Iphone' then 'https://itunes.apple.com/cn/app/fafa/id687237651?mt=8' else fileurl end url,versionmemo
	 from we_download  order by publishdate desc  ) a group by filetype";
	        $f = $DataAccess->GetData("data",$sql,array());
	        $pc = null;
	        $android =null;
	        $ios=null;
	        for($i=0;$i<3;$i++)
	        {
	            	$filetype = $f["data"]["rows"][$i]["filetype"];
	            	if($filetype=="PC") $pc = $f["data"]["rows"][$i];
	            	else if($filetype=="Android") $android = $f["data"]["rows"][$i];
	            	else if($filetype=="Iphone") $ios = $f["data"]["rows"][$i];
	        }
	        $start_model=$this->container->getParameter('start_model');
    		if(!empty($start_model) && $start_model=="MAPP")
    			return $this->render('JustsyBaseBundle:Login:index_2.html.twig', array('isreg'=>"",'domain'=> json_encode($huowa),"pc"=>$pc,"android"=>$android,"ios"=>$ios));
    		else
           		return $this->render('JustsyBaseBundle:Login:index_2.html.twig', array('isreg'=>"",'domain'=> json_encode($huowa),"pc"=>$pc,"android"=>$android,"ios"=>$ios));
        }
        else
        {
        	$u = $this->get('request')->getSession()->get(SecurityContext::LAST_USERNAME);
          return $this->render('JustsyBaseBundle:Login:default.html.twig', array('error' => $error,'_user'=> $u, 'this' => $this,"code_url"=> ''));
        }
      }
    }

    public function loginAndRefAction()
    {
      $request = $this->get("request");
      $des = $request->get("auth");
      $url = $request->get("redirect");
      if(empty($des) || empty($url))
      {
        return $this->render('JustsyBaseBundle:Login:default.html.twig', array());
      }
      $des = explode(',', $des);
      $login_account = $des[0];
      $pwd = $des[1];
      $Obj = new \Justsy\BaseBundle\Login\UserProvider($this->container);
      $user = $Obj->loadUserByUsername($login_account,"");  
      $token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken($user, $user->getPassword(), "secured_area", $user->getRoles());
      $this->get("security.context")->setToken($token);        
      $session = $request->getSession()->set('_security_'.'secured_area',  serialize($token));        
      $event = new \Symfony\Component\Security\Http\Event\InteractiveLoginEvent($request, $token);
      $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);
      return $this->redirect($url);
    }

    public function defaultAction()
    {
    	$o = new SaeTOAuthV2( Utils::$WB_AKEY , Utils::$WB_SKEY );
    	$code_url = $o->getAuthorizeURL( "http://we.fafatime.com/api/weibo/callback?_wefafa_t=weibo_sina" );
    	return $this->render('JustsyBaseBundle:Login:default.html.twig', array('error'=>null,'_user'=>null,"code_url"=> $code_url));
    }
    
    public function weibocallbackAction()
    {
    	    $request = $this->get("request");
        	$o = new SaeTOAuthV2( Utils::$WB_AKEY , Utils::$WB_SKEY );
        	$login_type = $request->get('_wefafa_t');
        	$code_url = $o->getAuthorizeURL( "http://we.fafatime.com/api/weibo/callback?_wefafa_t=".$login_type );
        	
					$keys = array();
						$keys['code'] = $request->get('code');
						$keys['redirect_uri'] = "http://we.fafatime.com";
						try {
							$token = $o->getAccessToken( 'code', $keys ) ;
							$c = new SaeTClientV2( Utils::$WB_AKEY , Utils::$WB_SKEY ,$token["access_token"]);
							$info = $c->show_user_by_id($token["uid"]);
							if(!empty($info["error"])){
							    $this->get("logger")->err(json_encode($info));
						  }
    					$province = Utils::do_post_request("http://api.t.sina.com.cn/provinces.json","");
							//查询当前用户的已获取粉丝列表
							//$mgr = new SinaWeiboMgr($this->get('we_data_access'),$token["uid"],$token["access_token"]);
							//$myfans = $mgr->getlist();
							//$wangbin_fans = $c->followers_by_id("2793358674");
							$accountbind =new \Justsy\BaseBundle\Management\StaffAccountBind($this->get('we_data_access'),null,$this->get('logger'));
							$bind=$accountbind->GetBind_By_Uid($login_type,$token["uid"],empty($info["error"]) ? $info : null);
							//判断是否绑定帐号，没有则跳转到绑定页面，已绑定则获取对应wefafa帐号自动登录
							$_SESSION["uid"]= $token["uid"];
							//$_SESSION["weibo_account"]= $info["uid"];
							$_SESSION["token"]= $token["access_token"];
							return $this->render('JustsyBaseBundle:Login:weibo_auth.html.twig',array('code'=> $keys['code'],'token'=> $token["access_token"],
							     "uid"=>$token["uid"],
							     "info"=>($info),
							     "code_url"=> $code_url,
							     "province" => ($province),
							     "isbind"=> empty($bind)?"0":"1",
							     "error" => empty($info["error"])?"":"帐号异常，无法调用微博API！",
							     "error_msg" => empty($info["error"])?"":$info["error"]
							     ));
						} catch (\Exception $e) {
							   $this->get("logger")->err($e);
						}        	
						
						return $this->render('JustsyBaseBundle:Login:default.html.twig',array('code_url'=> $code_url));
    }
    
    public function newloginAction()
    {
    	$request = $this->get("request");
    	$p=$request->get("p");
    	$DataAccess = $this->container->get('we_data_access');
	     $dataset = $DataAccess->GetData("domain","select domain_name from we_public_domain order by order_num limit 0,10");
	     $emials=($dataset["domain"]["rows"]);
	     $huowa = array();
	     for($i=0;$i<count($emials);$i++)
	     {
	       $huowa[]=$emials[$i]["domain_name"];
	     }
	     $start_model=$this->container->getParameter('start_model');
	     $start_model_code = "";
	     if(!empty($start_model) && $start_model!="MAPP")
	     	$start_model_code = "_sns";
    	if($p=='case')
    		return $this->render('JustsyBaseBundle:Login:cases'.$start_model_code.'.html.twig', array('domain'=> json_encode($huowa)));
    	else if($p=='feature')
    	  return $this->render('JustsyBaseBundle:Login:features'.$start_model_code.'.html.twig', array('domain'=> json_encode($huowa)));
    	else if($p=='download')
    	{
        $sql = " select * from (select filetype,fileversion,case when filesize is null then '未知' else  concat(cast(round(filesize/1024) as char(10)),' KB') end filesize,case filetype when 'Iphone' then 'https://itunes.apple.com/cn/app/fafa/id687237651?mt=8' else fileurl end url,versionmemo
 from we_download  order by publishdate desc  ) a group by filetype";
        $f = $DataAccess->GetData("data",$sql,array());
        $pc = null;
        $android =null;
        $ios=null;
        for($i=0;$i<3;$i++)
        {
            	$filetype = $f["data"]["rows"][$i]["filetype"];
            	if($filetype=="PC") $pc = $f["data"]["rows"][$i];
            	else if($filetype=="Android"){
            		 $android = $f["data"]["rows"][$i];
            		 $android["url"]=$this->generateUrl("JustsyMongoDocBundle_getandroidsetup",array(),true);
            	}
            	else if($filetype=="Iphone") $ios = $f["data"]["rows"][$i];
        }
    	  return $this->render('JustsyBaseBundle:Login:download'.$start_model_code.'.html.twig', array('domain'=> json_encode($huowa),"pc"=>$pc,"android"=>$android,"ios"=>$ios));
    	}
    	else if($p=='doc')
    	  return $this->render('JustsyBaseBundle:Login:help.html.twig', array('domain'=> json_encode($huowa)));      	  
    	else{
    		$sql = " select * from (select filetype,fileversion,case when filesize is null then '未知' else  concat(cast(round(filesize/1024) as char(10)),' KB') end filesize,case filetype when 'Iphone' then 'https://itunes.apple.com/cn/app/fafa/id687237651?mt=8' else fileurl end url,versionmemo
 from we_download  order by publishdate desc  ) a group by filetype";
        $f = $DataAccess->GetData("data",$sql,array());
        $pc = null;
        $android =null;
        $ios=null;
        for($i=0;$i<3;$i++)
        {
            	$filetype = $f["data"]["rows"][$i]["filetype"];
            	if($filetype=="PC") $pc = $f["data"]["rows"][$i];
            	else if($filetype=="Android") $android = $f["data"]["rows"][$i];
            	else if($filetype=="Iphone") $ios = $f["data"]["rows"][$i];
        }
        $isreg=$request->get("isreg");
    		return $this->render('JustsyBaseBundle:Login:index_2'.$start_model_code.'.html.twig', array('isreg'=> $isreg,'domain'=> json_encode($huowa),"pc"=>$pc,"android"=>$android,"ios"=>$ios));
    	}
    }
  	
    public function getLoginLogo()
    {
      $we_sys_param = $this->container->get('we_sys_param');
      
      $loginlogo = $we_sys_param->GetSysParam("loginlogo");
      
      return $loginlogo;  
    }  
 
    
    public function logoutAction($name) {}
    
    public function login_checkAction($name) {}
}
