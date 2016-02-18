<?php

namespace WebIM\ImOnlineServiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
/*
   在线服务。标识由类型+样式组成
   类型包括客服、销售、前台，分别对应代码0、1、2
   样式由从0开始的序号标识，0表示0号样式，1表示1号样式，以此类推。
*/
class DefaultController extends Controller
{
	  
    public function testAction()
    {
        return $this->render('WebIMImOnlineServiceBundle:Default:test.html.twig');
    }
    
    var $show;

    public function styleAction()
    {
    	  $this->show=$this->getRequest()->query->get('show', "0");
        $style=$this->getRequest()->query->get('style', "0");
        
        if($style=="0")
           return $this->style0Action();   
        else if($style=="1")
           return $this->style1Action();    
        else if($style=="9")
           return $this->style9twoAction();
        else if($style=="10")
           return $this->style10Action();           
    }    
    private $fafacnDomain = "fafacn.com";
	  private $pass="ljy20080511";
	  private $subject = "在线咨询";
    //在线客服0号样式
    public function style0Action()
    {
    	  $res = $this->getRequest();
    	  $title=($res->query->get('text', "FaFa在线客服"));
    	   
    	  $eno=$res->query->get('eno', "");
    	  $user = $res->query->get('user', "");  
        $resource =        $this->get('session')->getId();
        $resource = substr($resource,10);     	  
        if($user=="") $user = "guest-".$eno."@".$this->fafacnDomain."/FaFaWeb".$resource;
        else
        { 
        	  $user=$this->getUser($user)."/FaFaWeb";
        }
    	  $outHtml="";
    	  $result = $this->getWebData($eno,null);
    	  $isShow = gettype(strripos($this->show,"0"));
    	  if($result=="500"||$result=="404")
    	  {
    	  	$outHtml="<li>提示：</li><li>当前企业还未&nbsp;&nbsp;<a target=_blank href=\"javascript:window.location.href='".$this->container->getParameter('FILE_WEBSERVER_URL')."';\">认证</a>！</li>";
    	  }
    	  else if($result=="")
    	  {
    	      	$outHtml="<li>提示：</li><li>服务器忙，请稍后&nbsp;&nbsp;<a href='javascript:window.location.reload();'>再试</a>！</li>";
    	  }
    	  else{
			    	  if($isShow=="integer"){
			    	      //获取当前企业关联的客服人员列表
			    	      for($i=1;$i<count($result);$i++)
			    	      {
			    	          $account =	$result[$i]['employeeid'];    	          
			    	          if($account=="" || !strpos($account,"service")) continue;
			    	          $showInnerHTML = "<li id=\"$account\">".$this->getResourceImage($result[$i]['resource'],$result[$i]['state']).$this->getStateImage($result[$i]['state'])."<span class='fafa_webim_ocs_employee_name' onclick='fafa_webim_chat(this)' to='".$this->getAccountByResource($result[$i])."' title=\"".($result[$i]['state']=="1"?"点击立即开始咨询":"点击可以给我留言")."\">".$result[$i]['name']."</span></li>";
			    	          $outHtml .= $showInnerHTML;
			    	      }
			    	  }
			    	  $isShow = gettype(strripos($this->show,"1"));
			    	  if($isShow=="integer"){
			    	      //获取当前企业关联的销售人员列表
			    	      for($i=1;$i<count($result);$i++)
			    	      {
			    	          $account =	$result[$i]['employeeid'];    	          
			    	          if($account=="" || !strpos($account,"sale")) continue;
			    	          $showInnerHTML = "<li id=\"$account\">".$this->getResourceImage($result[$i]['resource'],$result[$i]->{'state'}).$this->getStateImage($result[$i]['state'])."<span class='fafa_webim_ocs_employee_name' onclick='fafa_webim_chat(this)' to='".$this->getAccountByResource($result[$i])."' title=\"".($result[$i]['state']=="1"?"点击立即开始咨询":"点击可以给我留言")."\">".$result[$i]['name']."</span></li>";
			    	          $outHtml .= $showInnerHTML;
			    	      }
			    	  }
			    	  $isShow = gettype(strripos($this->show,"2"));
			    	  if($isShow=="integer"){
			    	      //获取当前企业关联的前台人员列表
			    	      for($i=1;$i<count($result);$i++)
			    	      {
			    	          $account =	$result[$i]['employeeid'];
			    	          if($account=="" || !strpos($account,"front")) continue;
			    	          $showInnerHTML = "<li id=\"$account\">".$this->getResourceImage($result[$i]['resource'],$result[$i]['state']).$this->getStateImage($result[$i]['state'])."<span class='fafa_webim_ocs_employee_name' onclick='fafa_webim_chat(this)' to='".$this->getAccountByResource($result[$i])."' title=\"".($result[$i]['state']=="1"?"点击立即开始咨询":"点击可以给我留言")."\">".$result[$i]['name']."</span></li>";
			    	          $outHtml .= $showInnerHTML;
			    	      }
			    	  }
    	  }
    	  $r = $this->renderView('WebIMImOnlineServiceBundle:Default:style0.html.twig', 
                array('outHtml'=> $outHtml,'text'=>$title,'from'=>$user,'p'=> $this->pass,'subject'=> $this->subject)
                );
        $r = str_replace("\n","",$r);
        return new Response("var s='".str_replace("'","\'",$r)."'");
    }
    //在线客服9号样式:新 保留原有样式
    public function style9twoAction()
    {
    	$outHtml="";
     	  $res = $this->getRequest();
        $title=($res->query->get('text', "在线客服"));
    	  $eno=$res->query->get('eno', "100156");
    	  $jidList = $res->query->get('acc', "");
    	  //当前登录的用户帐号.没指定时自动以guest帐号登录
        $user = $res->query->get('user', ""); 
        $resource =        $this->get('session')->getId();
        $resource = substr($resource,10);  //取session的大于10位的后面字符，做为随机资源号
        if($user=="") $user = "guest-".$eno."@".$this->fafacnDomain."/FaFaWeb".$resource;
        else
        { 
        	  $user=$this->getUser($user)."/FaFaWeb";
        }
    	  $jidAry = explode(",",$jidList);
    	  $jids=array();
    	  for($i=0;$i<count($jidAry);$i++)
    	  {
    	  	 //if(strlen($jids)>0) $jids .= ",";
    	  	 if(strpos($jidAry[$i],"@")===false)  //JID-user
    	         $jids[]=$jidAry[$i]."@".$this->fafacnDomain;
    	     else
    	         $jids[]= $this->getUser($jidAry[$i]);//JID|Account    	     
    	  }
    	  //获取指定帐号的在线状态
    	  $result = $this->getWebData($eno,$jids);
    	  $pageURL = 'http';
			    if ($_SERVER["HTTPS"] == "on")
			    {
			        $pageURL .= "s";
			    }
			    $pageURL .= "://";
			
			    if ($_SERVER["SERVER_PORT"] != "80")
			    {
			        $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] ;
			    }
			    else
			    {
			        $pageURL .= $_SERVER["SERVER_NAME"] ;
	        }
   	    for($i=0;$i<count($result);$i++)
    	  {
    	  	 $account =	$result[$i]['employeeid'];
    	  	 if($account=="") continue;
    	  	 if(strpos($account,"sale")===false && strpos($account,"service")===false && strpos($account,"front")===false) continue;
    	  	 
    	     //$showInnerHTML = "<li id=\"$account\">".$this->getResourceImage($result[$i]['resource'],$result[$i]['state'])."<span class='fafa_webim_ocs_employee_name' onclick='fafa_webim_chat(this)' to='".$this->getAccountByResource($result[$i])."' title=\"".($result[$i]['state']=="1"?"点击立即开始咨询":"点击可以给我留言")."\">".$result[$i]['name']."</span>".$this->getStateImage($result[$i]['state'])."</li>";
    	     $showInnerHTML='';
    	     
    	     //客服状态描述
    	     $statedesc='';
    	     $stateimg=$pageURL;
    	     if($result[$i]['resource']=='FaFaWin'){
    	     		$statedesc="PC在线";
    	     		$stateimg="";
    	     }
    	     else if($result[$i]['resource']=='FaFaWeb'){
    	     		$statedesc="Web在线";
    	     		$stateimg="";
    	     }
    	     else if($result[$i]['resource']=='FaFaAndroid'){
    	     		$statedesc="Android在线";
    	     		$stateimg.="/bundles/fafawebimimonlineservice/images/android.png";
    	     }
    	     else if($result[$i]['resource']=='FaFaIPhone'){
    	     	 	$statedesc="IPhone在线";
    	     	 	$stateimg.="/bundles/fafawebimimonlineservice/images/phone.png";
    	     }
    	     else if($result[$i]['resource']=='iPad'){
    	     		$statedesc="iPad在线";
    	     		$stateimg.="/bundles/fafawebimimonlineservice/images/iPad.png";
    	     }
    	     else{
    	     		$statedesc="离线";
    	     		//$stateimg="";
    	     		$stateimg.="/bundles/fafawebimimonlineservice/images/offline.png";
    	     }
    	     if(count($result)>1 && $i!=(count($result)-1))
    	     	$showInnerHTML.="<li onclick='fafa_webim_chat2(this.children[1])' class='someli'>";
    	     else
    	     	$showInnerHTML.="<li onclick='fafa_webim_chat2(this.children[1])'>";
    	     $showInnerHTML.="<div class='service-sidec'><div class='service-name'><a href='javascript:;'>".$result[$i]['name']."</a></div><div class='service-memo'>".$statedesc."</div></div>";
    	     $showInnerHTML.="<div class='fafa_webim_ocs_employee_name' onclick='fafa_webim_chat2(this)' state='".$result[$i]['state']."' to='".$this->getAccountByResource($result[$i])."'><div style='background:url(".$stateimg.") no-repeat scroll 0 0 rgba(0, 0, 0, 0)' class='service-resource'></div><img src='".$pageURL."/bundles/fafawebimimonlineservice/images/service_head.png'></div></div>";
    	     $showInnerHTML.="</li>";
			     $outHtml .=$showInnerHTML;
    	  }  	  
        $r = $this->renderView('WebIMImOnlineServiceBundle:Default:style9_two.html.twig', 
                array('outHtml'=> $outHtml,'text'=>$title,'from'=>$user,'p'=> $this->pass)
                );
        $r = str_replace("\n","",$r);
        return new Response("var s='".str_replace("'","\'",$r)."'"); 
    }
    //在线客服9号样式:显示指定的帐号的人员。wefafa专用
     public function style9Action()
     {
     	  $outHtml="";
     	  $res = $this->getRequest();
        $title=($res->query->get('text', "在线客服"));
    	  $eno=$res->query->get('eno', "100082");
    	  $jidList = $res->query->get('acc', "");
    	  //当前登录的用户帐号.没指定时自动以guest帐号登录
        $user = $res->query->get('user', ""); 
        $resource =        $this->get('session')->getId();
        $resource = substr($resource,10);  //取session的大于10位的后面字符，做为随机资源号
        if($user=="") $user = "guest-".$eno."@".$this->fafacnDomain."/FaFaWeb".$resource;
        else
        { 
        	  $user=$this->getUser($user)."/FaFaWeb";
        }
    	  $jidAry = explode(",",$jidList);
    	  $jids=array();
    	  for($i=0;$i<count($jidAry);$i++)
    	  {
    	  	 if(strlen($jids)>0) $jids .= ",";
    	  	 if(strpos($jidAry[$i],"@")===false)  //JID-user
    	         $jids[]=$jidAry[$i]."@".$this->fafacnDomain;
    	     else
    	         $jids[]= $this->getUser($jidAry[$i]);//JID|Account    	     
    	  }
    	  //获取指定帐号的在线状态
    	  $result = $this->getWebData($eno,$jids);
   	    for($i=0;$i<count($result);$i++)
    	  {
    	  	 $account =	$result[$i]['employeeid'];
    	  	 if($account=="") continue;
    	  	 if(strpos($account,"sale")===false && strpos($account,"service")===false && strpos($account,"front")===false) continue;
    	  	 
    	     $showInnerHTML = "<li id=\"$account\">".$this->getResourceImage($result[$i]['resource'],$result[$i]['state'])."<span class='fafa_webim_ocs_employee_name' onclick='fafa_webim_chat(this)' to='".$this->getAccountByResource($result[$i])."' title=\"".($result[$i]['state']=="1"?"点击立即开始咨询":"点击可以给我留言")."\">".$result[$i]['name']."</span>".$this->getStateImage($result[$i]['state'])."</li>";
			     $outHtml .= $showInnerHTML;
    	  }    	  
        $r = $this->renderView('WebIMImOnlineServiceBundle:Default:style9.html.twig', 
                array('outHtml'=> $outHtml,'text'=>$title,'from'=>$user,'p'=> $this->pass)
                );
        $r = str_replace("\n","",$r);
        return new Response("var s='".str_replace("'","\'",$r)."'");    	  
     }
     
     //在线客服10号样式:显示指定的帐号的人员。
     public function style10Action()
     {
     	  $outHtml="";
     	  $res = $this->getRequest();
    	  $eno=$res->query->get('eno', "");
        $user = $res->query->get('user', "null");  
        $resource =        $this->get('session')->getId();
        $resource = substr($resource,10);              
        if($user=="") $user = "guest-".$eno."@".$this->fafacnDomain."/FaFaWeb".$resource;
        else
        { 
        	  $user=$this->getUser($user)."/FaFaWeb";
        } 	  
    	  $jidList = $res->query->get('acc', "");
    	  $jidAry = explode(",",$jidList);
    	  $jids=array();
    	  for($i=0;$i<count($jidAry);$i++)
    	  {
    	  	 if(strlen($jids)>0) $jids .= ",";
    	  	 if(strpos($jidAry[$i],"@")===false)
    	         $jids[]=$jidAry[$i]."@".$this->fafacnDomain;
    	     else
    	         $jids[]= $this->getUser($jidAry[$i]);
    	  }
    	  $result = $this->getWebData($eno,$jids);
   	    for($i=0;$i<count($result);$i++)
    	  {
    	  	 if(empty($result[$i]))continue;
    	  	 $account =	$result[$i]['employeeid'];
    	  	 if($account=="") continue;
    	     $showInnerHTML = "{\"resource\":\"".$result[$i]['resource']."\",\"to\":\"".$this->getAccountByResource($result[$i])."\",\"name\":\"".$result[$i]['name']."\",\"state\":\"".$result[$i]['state']."\"}";
			     if(strlen($outHtml)>0) $outHtml .= ",";
			     $outHtml .= $showInnerHTML;
    	  }
        $from=$user."/". $this->pass;
        return new Response("var s=[\"$from\",[$outHtml]]");    	  
     }     
    
    private function getUser($user)
    {
    	  $da = $this->get('we_data_access');
    	  if(!empty($user))
    	  {
    	      	$ds=$da->GetData("d","select fafa_jid from we_staff where login_account=?",array((string)$user));    	      	
    	      	if($ds&&count($ds["d"]["rows"])>0)
    	      	{
    	      	   return $ds["d"]["rows"][0]["fafa_jid"];
    	      	}
    	      	else return $user; //jid    	      	
    	  }
    	  return "";
    }
    
    private function getAccountByResource($lst)
    {
        	if($lst['state']=="0")
        	   return $lst['account'];
        	else
        	  return $lst['account']."/".$lst['resource'];
    }
    private function getResourceImage($res,$state)
    {
    	   if($res=="FaFaWin" ||  $res=="FaFaWeb" || $state=="0")
    	      return "<span class=\"fafa_webim_ocs_phone\" style='width: 16px;'><div style=\"width:16px; height:16px;\"></div></span>";
    	   else if($state=="1")
    	   {
				    $pageURL = 'http';
				
				    if ($_SERVER["HTTPS"] == "on")
				    {
				        $pageURL .= "s";
				    }
				    $pageURL .= "://";
				
				    if ($_SERVER["SERVER_PORT"] != "80")
				    {
				        $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] ;
				    }
				    else
				    {
				        $pageURL .= $_SERVER["SERVER_NAME"] ;
		        }    	   	
    	      return "<span class=\"fafa_webim_ocs_phone\" title=\"手机在线\"><div style=\"width:16px; height:16px;\"><img src=\"".$pageURL."/bundles/fafawebimimonlineservice/images/phone.png\" width=\"9\" height=\"16\"/></div></span>";
    	   }
    }
    private function getStateImage($state)
    {
    	   if($state=="1")
    	      return "<span class=\"fafa_webim_ocs_status\"  title=\"在线\"><div state='online' class='fafa_webim_ocs_online'></div></span>";
    	   else
    	      return "<span class=\"fafa_webim_ocs_status\"  title=\"离线\"><div state='offline' class='fafa_webim_ocs_offline'></div></span>";
    }
    public function getCountAction()
    {
    	  $showtype=$this->getRequest()->query->get('show', "0");
    	  $eno=$this->getRequest()->query->get('eno', "");
       	$result = $this->getWebData($eno);
       	$cnt=0;
       	if($result=="500"||$result=="404")
    	  {
    	  	return new Response("var ocscount=".$cnt);
    	  }
    	  else if($result=="")
    	  {
    	      	return new Response("var ocscount=".$cnt);
    	  }
    	  else{
    	  	$cnt=0;
    	  	$state  ="{";
    	  	$isShow = gettype(strripos($showtype,"0")); //因为false与0自动转换，所以当strripos返回位置为0时，必须获取其对象类型进行判断
          if($isShow=="integer"){
			    	      //获取当前企业关联的客服人员列表
			    	      for($i=1;$i<count($result);$i++)
			    	      {
			    	          $account =	$result[$i]->{'employeeid'};
			    	          if($account=="" || !strpos($account,"service")) continue;
			    	          $cnt = $cnt+1;
			    	          if($state !="{")  $state .= ",";
			    	          $state .= "\"$account\":\"".$result[$i]->{'state'}."\",\"".$account."_resource\":\"".$result[$i]->{'resource'}."\"";
			    	      }
			    } 
    	  	$isShow = gettype(strripos($showtype,"1"));
          if($isShow=="integer"){
			    	      //获取当前企业关联的销售人员列表
			    	      for($i=1;$i<count($result);$i++)
			    	      {
			    	          $account =	$result[$i]->{'employeeid'};    	          
			    	          if($account=="" || !strpos($account,"sale")) continue;
			    	          $cnt = $cnt+1;
			    	          if($state !="{")  $state .= ",";
			    	          $state .= "\"$account\":\"".$result[$i]->{'state'}."\",\"".$account."_resource\":\"".$result[$i]->{'resource'}."\"";			    	          
			    	      }
			    }
    	  	$isShow = gettype(strripos($showtype,"2"));
          if($isShow=="integer"){
			    	      //获取当前企业关联的前台人员列表
			    	      for($i=1;$i<count($result);$i++)
			    	      {
			    	          $account =	$result[$i]->{'employeeid'};    	          
			    	          if($account=="" || !strpos($account,"front")) continue;
			    	          $cnt = $cnt+1;
			    	          if($state !="{")  $state .= ",";
			    	          $state .= "\"$account\":\"".$result[$i]->{'state'}."\",\"".$account."_resource\":\"".$result[$i]->{'resource'}."\"";			    	          
			    	      }
			    }
			    $state .= "}";			       	  	
    	  	return new Response("var ocs_state = ".$state.", ocscount=".$cnt);
    	  }
    }        
    private function getWebData($orgid,$jids)
    {
      try 
      {
      	$cond = array();
      	$para=array();
      	$da = $this->get('we_data_access_im');
      	if($jids!=null)
      	{
		      	for($i=0;$i<count($jids);$i++)
		      	{
		      		  $cond[]="?";
		      		  $para[]= (string)$jids[$i];
		      	}
		      	$sql = "select a.employeeid,a.loginname account,a.employeename name,ifnull(max(b.res),'') resource,case when (max(b.res) is null) then '0' else '1' end state from im_employee a left join global_session b on a.loginname=b.us where a.loginname in (".implode(",",$cond).") group by a.employeeid,a.loginname,a.employeename";
        }
        else
        {
            $sql ="select a.employeeid,a.loginname account,a.employeename name,ifnull(max(b.res),'') resource,case when (max(b.res) is null) then '0' else '1' end state from im_employee a left join global_session b on a.loginname=b.us where a.deptid = ? group by a.employeeid,a.loginname,a.employeename";	
            $para[] = "v".$eno."999";
        }
      	$ds = $da->GetData("d",$sql,$para);      	
      	return $ds["d"]["rows"];
      } 
      catch (\Exception $ex) 
      {
         $this->get('logger')->err($ex);
         //$this->hintmsg = "提示：服务器忙，请稍后再试！";
         return null;
      }
    }       
}
