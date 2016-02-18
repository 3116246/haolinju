<?php

namespace Justsy\BaseBundle\Common;

use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\DataAccess\SysSeq;

//该类用户发送消息或出席
class SendMessage
{
	private $conn=null;
	private $conn_im=null;
	private $container=null;
	public function __construct($_db,$_db_im)
	{
	    $this->conn = $_db;
	    $this->conn_im = $_db_im;
	}
	  
	//发送出席
	public function sendImPresence($parameter)
	{
	  	$result = array("success"=>true,"msg"=>"");
	  	$flag = isset($parameter["flag"]) ? $parameter["flag"] : "online";
	  	$myjid = isset($parameter["myjid"]) ? $parameter["myjid"] : "";
	  	$title = isset($parameter["title"]) ? $parameter["title"] : "";
	  	$container = isset($parameter["container"]) ? $parameter["container"] : null;
	  	$this->container=$container;
	  	if ( empty($flag)){
	  	   $result = array("success"=>false,"msg"=>"请选择发送出席标识(online:在线手机客户端用户；onself:向自己发送;all:向全体人员发送)");
	  	}
	  	else if ( empty($title)){
	  	 	 $result = array("success"=>false,"msg"=>"请确认发送的标题Caption");
	  	}
	  	else if ( $flag=="onself" && empty($myjid) ){
	  	 	 $result = array("success"=>false,"msg"=>"请确认自己的fafa_jid");
	  	}
	  	else if ( empty($container)){
	  	 	 $result = array("success"=>false,"msg"=>"请定义container属性");
	  	}
	  	else{
	  	 	 $flag = strtolower($flag);
		  	 $fromjid = isset($parameter["fromjid"]) ? $parameter["fromjid"] : "";
		  	 $message = isset($parameter["message"]) ? $parameter["message"] : "";
		  	 $link = isset($parameter["link"]) ? $parameter["link"] : null;
		  	 $linktext = isset($parameter["linktext"]) ? $parameter["linktext"]:null;
		  	 $ischeckjid = isset($parameter["ischeckjid"]) ? (boolean)$parameter["ischeckjid"]:false;
		  	 $type = isset($parameter["type"]) ? $parameter["type"] : Utils::$systemmessage_code;
		  	 $cctomail = isset($parameter["cctomail"]) ? $parameter["cctomail"] : 0;
		  	 $eno = isset($parameter["eno"]) ? $parameter["eno"] : null;
    		 if(empty($eno) && !empty($container->get('security.context')->getToken()))
    		 {
    		  	$user = $container->get('security.context')->getToken()->getUser();
    		  	$eno = $user->eno;
    		 }
    		 if(empty($eno))
    		 {
    		  	$eno = $container->getParameter('ENO');
    		 }
    		$domain =  $container->getParameter('edomain');
    		$deploy_mode =  $container->getParameter('deploy_mode');
		  	//确定发送对象
		  	$ds = null;
		  	$tojids = array();
		  	$ary_micro_account=array();//企业公众号，需要排除的
		  	if ($flag=="onself"){
		  	 	 array_push($tojids,$myjid);
		  	}
		  	else if($flag=="dept")
		  	{
		  	 	//给部门发
		  	 	
		  	}
		  	else if($flag=="roster")
		  	{
		  	 	//给好友发
		  	 	
		  	}
		  	else if($flag=="to_jids")
		  	{
		  	 	//给指定的一个或者多个jid发
		  	 	
		  	}
		  	else
		  	{
		  		$this->write_msg($fromjid,$message,'');
		  		
		  	 	//给全企业发
		  	 	if($deploy_mode=="E")
		  	 		$sql = "select distinct us as fafa_jid from global_session";
		  	 	else
		  	 		$sql = "select distinct us as fafa_jid from global_session where us like '%{$eno}@{$domain}'";
		  	 	$ds = $this->conn_im->GetData("table",$sql);
		  	 	try
		  	 	{
					$sql = "select jid from we_micro_account where eno=?";
	    		  	$ds_micro_account = $this->conn->GetData("micro",$sql,array((string)$eno));
	    		  	for ($i=0; $i < count($ds_micro_account["micro"]["rows"]); $i++)
	    		  	{
	    		  		$ary_micro_account[] = $ds_micro_account["micro"]["rows"][$i]["jid"];
	    		  	}	
    		  	}
    		  	catch(\Exception $e)
    		  	{
    		  		$container->get("logger")->err($e);
    		  	}  	 	
		  	 }
		  	 /*else if ($flag=="all"){
		  	 	 $sql = "select fafa_jid from we_staff where eno='{$eno}' and not exists(select 1 from we_micro_account b where b.eno=we_staff.eno and b.number=we_staff.login_account)";
		  	 	 $ds = $this->conn->GetData("table",$sql);		  	 	
		  	 }*/
		  	//发送出席
			$presence = new \Justsy\OpenAPIBundle\Controller\ApiController();
		    $presence->setContainer($container);
		    if(!empty($ds))
		    {
		    	$rows = $ds["table"]["rows"];
		    	$count = count($rows);
			    for ($i=0; $i < $count; $i++) {
			    	$jid =  $rows[$i]["fafa_jid"];
    			    if(count($ary_micro_account)>0)
    			    {
    			    	if(in_array($jid, $ary_micro_account)) continue;
    			    }
			    	$tojids[] = $jid;
			    	if($i>0 && $i%5000==0)
			    	{
			    	 	$to_jid = implode(",",$tojids);
			    	 	$tojids=array();
			 			$presence->sendPresence($fromjid,$to_jid,$title,$message,$link,$linktext,$ischeckjid,$type,$cctomail);
			    	}
			    }
			}
			if ( count($tojids)>0){
			 	$to_jid = implode(",",$tojids);
			 	$presence->sendPresence($fromjid,$to_jid,$title,$message,$link,$linktext,$ischeckjid,$type,$cctomail);  	 	 	 
			}
	  	}
	  	return $result;
	}

	public function sendImMessage($parameter)
	{
	  	$result = array("success"=>true,"msg"=>"");
	  	$flag = isset($parameter["flag"]) ? $parameter["flag"] : "all";
	  	$myjid = isset($parameter["myjid"]) ? $parameter["myjid"] : "";
	  	$title = isset($parameter["title"]) ? $parameter["title"] : "";
	  	$container = isset($parameter["container"]) ? $parameter["container"] : null;
	  	$this->container=$container;
	  	if ( empty($flag)){
	  	   $result = array("success"=>false,"msg"=>"请选择发送出席标识(online:在线手机客户端用户；onself:向自己发送;all:向全体人员发送)");
	  	}
	  	else if ( empty($title)){
	  	 	 $result = array("success"=>false,"msg"=>"请确认发送的标题Caption");
	  	}
	  	else if ( $flag=="onself" && empty($myjid) ){
	  	 	 $result = array("success"=>false,"msg"=>"请确认自己的fafa_jid");
	  	}
	  	else if ( empty($container)){
	  	 	 $result = array("success"=>false,"msg"=>"请定义container属性");
	  	}
	  	else
	  	{
    	  	 	 $flag = strtolower($flag);
    		  	 $fromjid = isset($parameter["fromjid"]) ? $parameter["fromjid"] : "";
    		  	 $message = isset($parameter["message"]) ? $parameter["message"] : "";
    		  	 $link = isset($parameter["link"]) ? $parameter["link"] : null;
    		  	 $linktext = isset($parameter["linktext"]) ? $parameter["linktext"]:null;
    		  	 $ischeckjid = isset($parameter["ischeckjid"]) ? (boolean)$parameter["ischeckjid"]:false;
    		  	 $type = isset($parameter["type"]) ? $parameter["type"] : Utils::$systemmessage_code;
    		  	 $cctomail = isset($parameter["cctomail"]) ? $parameter["cctomail"] : 0;
    		  	 $eno = isset($parameter["eno"]) ? $parameter["eno"] : null;
    		  	 if(empty($eno) && !empty($container->get('security.context')->getToken()))
    		  	 {
    		  	 	$user = $container->get('security.context')->getToken()->getUser();
    		  	 	$eno = $user->eno;
    		  	 }
    		  	 if(empty($eno))
    		  	 {
    		  	 	$eno = $container->getParameter('ENO');
    		  	 }
    		  	 $domain =  $container->getParameter('edomain');
    		  	 $deploy_mode =  $container->getParameter('deploy_mode');
    		  	 //确定发送对象
    		  	 $ds = null;
    		  	 $tojids = array();
    		  	 //优先给在线的发
    		  	 $ary_micro_account=array();//企业公众号，需要排除的
    		  	 if ($flag == "online"){    		  	 	
    		  	 	if($deploy_mode=="E")
    		  	 		$sql = "select distinct us as fafa_jid from global_session";
    		  	 	else
    		  	 		$sql = "select distinct us as fafa_jid from global_session where us like '%{$eno}@{$domain}'";
    		  	 	$ds = $this->conn_im->GetData("table",$sql);
    		  	 }
    		  	 else if ($flag=="onself"){
    		  	 	 array_push($tojids,$myjid);
    		  	 }
    		  	 else if ($flag=="all"){

    		  	 	$this->write_msg($fromjid,$message,'');

    		  	 	if($deploy_mode=="E")
    		  	 		$sql = "SELECT a.username fafa_jid,b.priority FROM users a inner join global_session b on a.username=b.us order by b.priority desc";
    		  	 	else
    		  	 		$sql = "SELECT a.username fafa_jid,b.priority FROM users a inner join global_session b on a.username=b.us where a.username like '%{$eno}@{$domain}' order by b.priority desc";
    		  	 	//$sql = "select fafa_jid from we_staff where eno='{$eno}' and not exists(select 1 from we_micro_account b where b.eno=we_staff.eno and b.number=we_staff.login_account)";
    		  	 	$ds = $this->conn_im->GetData("table",$sql);
    		  	 	$sql = "select jid from we_micro_account where eno=?";
    		  	 	try
    		  	 	{
	    		  	 	$ds_micro_account = $this->conn->GetData("micro",$sql,array((string)$eno));
	    		  	 	for ($i=0; $i < count($ds_micro_account["micro"]["rows"]); $i++)
	    		  	 	{
	    		  	 		$ary_micro_account[] = $ds_micro_account["micro"]['rows'][$i]["jid"];
	    		  	 	}
    		  	 	}
    		  	 	catch(\Exception $e)
	    		  	{
	    		  		$container->get("logger")->err($e);
	    		  	} 
    		  	 }
    		  	//发送出席
    			$presence = new \Justsy\OpenAPIBundle\Controller\ApiController();
    		    $presence->setContainer($container);
    		    if(!empty($ds))
    		    {
					$rows = $ds["table"]["rows"];
		    		$count = count($rows);
    			    for ($i=0; $i < $count; $i++) { 
    			    	$jid =  $rows[$i]["fafa_jid"]; 
    			    	if(count($ary_micro_account)>0)
    			    	{
    			    	 	if(in_array($jid, $ary_micro_account)) continue;
    			    	}
    			    	$tojids[] =$jid;
    			    	if($i>0 && $i%5000==0)
    			    	{
    			    	 	$to_jid = implode(",",$tojids);
    			    	 	$tojids=array();
    			 			$presence->sendMsg($fromjid,$to_jid,$title,$message,$link,$linktext,$ischeckjid,$type,$cctomail);
    			    	}
    			    }
    			}
    			if ( count($tojids)>0){
    			 	$to_jid = implode(",",$tojids);
    			 	$presence->sendMsg($fromjid,$to_jid,$title,$message,$link,$linktext,$ischeckjid,$type,$cctomail);  	 	 	 
    			}
	  	}
	  	return $result;
	}

	public function write_msg($fromjid,$msgxml,$msgid)
	{
	  	if(empty($fromjid))
	  	{
	  		$domain =  $this->container->getParameter('edomain');
	  		$staffinfo = new \Justsy\BaseBundle\Management\Staff($this->conn,$this->conn_im,'admin@'.$domain,$this->container->get("logger"),$this->container);
			$staffdata = $staffinfo->getInfo();
			$fromjid = $staffdata['jid'];
	  	}
	  	if(empty($msgid))
	  	{
		  	$msgid = split("@", $fromjid);
		  	$msgid = $msgid[0].time();
	  	}
		//存储业务消息
		$xml = Utils::WrapMessageXml($fromjid,$msgxml,$msgid);
		$sql = 'insert into im_b_msg(msg,created,us,msgid)values(?,now(),?,?)';
		$para=array((string)$xml,(string)$fromjid,(string)$msgid);
		$this->conn_im->ExecSQL($sql,$para);
	}
}
