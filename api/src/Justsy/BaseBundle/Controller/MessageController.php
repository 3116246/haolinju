<?php

namespace Justsy\BaseBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\Common\DES;

class MessageController extends Controller
{   public $groups;
    public $staffs; 
    public $FaceEmotes;

  public function indexAction($network_domain)
  {
     return $this->render("JustsyBaseBundle:Message:index.html.twig",array('this'=>$this,"curr_network_domain"=>$network_domain));
  }
  //信息发布组件
  public function pushMsgAction($network_domain)
  {
  	  $user = $this->get('security.context')->getToken()->getUser();
      $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
      $request = $this->getRequest();
      $msg_id=$request->get('msg_id');
      $sqls = array();
      $params = array();
      
      //取出当前圈子中该用户参与的群组
      $sql1 = "select a.group_id, a.group_name, a.group_photo_path, concat('$FILE_WEBSERVER_URL', ifnull(a.group_photo_path, '')) group_photo_url
from we_groups a, we_group_staff b 
where a.group_id=b.group_id
  and b.login_account=?
  and a.circle_id=?";
      $params1 = array();
      $params1[] = (string)$user->getUserName();
      $params1[] = (string)$user->get_circle_id($network_domain);
      
      $sqls[] = $sql1;
      $params[] = $params1;
      
      //当用户数小于100时，取出当前圈子中所有用户
      $sql1 = "select a.login_account, ifnull(a.nick_name, a.login_account) nick_name
from we_staff a, we_circle_staff b
where a.login_account=b.login_account
  and b.circle_id=?";
      $params1 = array();
      $params1[] = (string)$user->get_circle_id($network_domain);
      
      $sqls[] = $sql1;
      $params[] = $params1;
      
      $da = $this->get('we_data_access');
      $da->PageSize = 100;
      $da->PageIndex = 0;
      $ds = $da->GetDatas(array("we_groups", "we_staff"), $sqls, $params);
            
      //$this->groups = json_encode($ds["we_groups"]["rows"]);
      $this->staffs = json_encode($ds["we_staff"]["recordcount"] > count($ds["we_staff"]["rows"]) ? array() : $ds["we_staff"]["rows"]);
      $a['this']=$this;
      $a['row']='';
      $a['curr_network_domain']=$network_domain;
      if($msg_id!=null)
      {
      	$sql="select a.*,b.nick_name from we_message a,we_staff b where a.msg_id=? and b.login_account=a.sender";
      	$param=array((string)$msg_id);
      	$d=$da->Getdata("mes",$sql,$param);
      	$a['row']=$d['mes']['rows'][0];
      }
     return $this->render("JustsyBaseBundle:Message:pushMsg.html.twig",$a);
  }
  public function updateAction(Request $request)
  {
  	$da=$this->container->get('we_data_access');
  	$msg_id = "";
  	$sender = $this->get('security.context')->getToken()->getUser()->getUserName();
  	$content=$request->get('msg');
  	$recver=$request->get('txtNotify');
  	$attachs=$request->get('attachs');
  	$attachsName=$request->get('attachsName');
  	$title=$request->get('titl');
  	$countRecver=count($recver);
  	$countAttachs=count($attachs);
  	
    $params = array();
    $params[] = (string)$msg_id;
    $params[] = (string)$sender;
    $params[] = (string)$title;
    $params[] = (string)$content;
    $params[] = (string)'0';
  	$sql='insert into we_message(msg_id,sender,send_date,title,content,isread,recver) values(?,?,CURRENT_TIMESTAMP(),?,?,?,?)';
  	for($i=0;$i<$countRecver;$i++)
  	{
  	  $msg_id = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_message", "msg_id");
  	  $params[0] = $msg_id;
  		$params[5] = (string)$recver[$i];
  		$da->ExecSQL($sql,$params);
  		
    	$sql2=' into we_message_attach(msg_id, attach_type, attach_id) values(?,?,?)';
    	$params2=array();
    	$params2[] = (string)$msg_id;
    	$params2[] = (string)'0';
    	for($j=0;$j<$countAttachs;$j++)
    	{
    		$params2[2] = (string)$attachs[$j];
    		$da->ExecSQL($sql2,$params);
    	}
  	}
  	
  	$re=array('success'=>'1');
  	return new Response(json_encode($re));	
  }
  //得到消息
  public function getMsgAction($network_domain)
  {
  	 $user = $this->get('security.context')->getToken()->getUser();
  	 $username=$user->getUserName();
  	 $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
  	 $request = $this->getRequest();
  	 $type=$request->get('type');
  	 $pageindex = (int)($request->get('pageindex'))-1;
  	 $da = $this->get('we_data_access');
  	 $da->PageSize=10;
		 $da->PageIndex=$pageindex;
     
     $isreadWhere = "";
     if($type == '0')
     {
       $isreadWhere = " ifnull(a.isread, '') in ('0', '')  ";
     }
     else if ($type == '1')
     {
       $isreadWhere = " a.isread = '1' ";
     }
     else
     {
       $isreadWhere = " 1=1 ";
     }
 	   $sql="select a.msg_id, a.sender, a.recver, a.send_date, a.title, a.isread, 
b.nick_name,b.photo_path,concat('$FILE_WEBSERVER_URL',ifnull(b.photo_path_small, ifnull(b.photo_path,''))) photo_url,b.login_account 
from we_message a,we_staff b 
where b.login_account=a.sender 
  and a.recver=? and $isreadWhere
order by send_date desc, a.msg_id desc";
	 	 $ds=$da->GetData('msg',$sql,array((string)$username));
     $pagecount=ceil($ds["msg"]["recordcount"]/($da->PageSize));
//  	 $attacheNames=array();
//  	 $da->PageSize= -1;
//  	 $da->PageIndex= 0;
//  	 for($i=0;$i<$ds['msg']['recordcount'];$i++)
//  	 {
//  	   $sql='select a.file_name, b.attach_id from we_files a,we_message_attach b where a.file_id=b.attach_id and b.msg_id=?';
//  	   $ds2=$da->GetData('file',$sql,array($ds['msg']['rows'][$i]['msg_id']));
//  	   $file=array();
//  	   for($j=0;$j<$ds2['file']['recordcount'];$j++)
//  	   {
//  	   	$file[$j]['filename']=$ds2['file']['rows'][$j]['file_name'];
//  	   	$file[$j]['fileurl']  =$FILE_WEBSERVER_URL.$ds2['file']['rows'][$j]['attach_id'];
//  	   }
//  	   $ds['msg']['rows'][$i]['file']=$file;
//  	 }

     //计算未读数
     $unreadnum = $ds['msg']['recordcount'];
     $readnum = $ds['msg']['recordcount'];
     if ($type != '0')
     {
       $sql = "select count(*) c
from we_message a,we_staff b 
where b.login_account=a.sender 
  and a.recver=? and ifnull(a.isread, '') in ('0', '')";
       $da->PageSize= -1;
    	 $da->PageIndex= 0;
       $ds1 = $da->GetData('msg', $sql, array((string)$username));
       $unreadnum = $ds1["msg"]["rows"][0]["c"];
     }
     if($type != '1'){
     	 $sql = "select count(*) c
from we_message a,we_staff b 
where b.login_account=a.sender 
  and a.recver=? and a.isread='1'";
       $da->PageSize= -1;
    	 $da->PageIndex= 0;
       $ds1 = $da->GetData('msg', $sql, array((string)$username));
       $readnum = $ds1["msg"]["rows"][0]["c"];
     }
     
  	 return $this->render('JustsyBaseBundle:Message:msg.html.twig',array('unreadnum' => $unreadnum,'readnum'=>$readnum, 'num'=>$ds['msg']['recordcount'], 'msg'=>$ds['msg'], 'pageindex' => $pageindex+1, 'pagecount'=>$pagecount,'type'=>$type,'curr_network_domain'=>$network_domain));
  }
  public function oneMsgAction($network_domain){
  	$request = $this->getRequest();
  	$username=$this->get('security.context')->getToken()->getUser()->getUserName();
  	$FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
  	$da=$this->container->get('we_data_access');
  	$msg_id=$request->get('msg_id');
  	
  	$re_type = $request->get('re_type');
  	$re_pageindex = $request->get('re_pageindex');
  	
  	$sql="";
  	$params=array((string)$msg_id);
  	$sql="select a.*,b.nick_name,b.photo_path,concat('$FILE_WEBSERVER_URL',ifnull(b.photo_path,'')) photo_url,b.login_account from we_message a,we_staff b where
  	 				 a.msg_id=? and b.login_account=a.sender";
  	//var_dump($msg_id);
  	$ds=$da->Getdata("msg",$sql,$params);
  	//var_dump($ds['msg']['recordcount']);
  	$a=array();
  	if($ds['msg']['recordcount']==1)
  	{
  		$sql='select a.file_name, b.attach_id from we_files a,we_message_attach b where a.file_id=b.attach_id and b.msg_id=?';
  	  $ds2=$da->GetData('file',$sql,array($ds['msg']['rows'][0]['msg_id']));
  	  $file=array();
  	  $file_count=0;
  	  for($j=0;$j<$ds2['file']['recordcount'];$j++)
  	  {
  	  	$file_count+=1;
  	    $file[$j]['filename']=$ds2['file']['rows'][$j]['file_name'];
  	    $file[$j]['fileurl']  =$FILE_WEBSERVER_URL.$ds2['file']['rows'][$j]['attach_id'];
  	  }
  	  $ds['msg']['rows'][0]['file']=$file;
  	  $a['file_count']=$file_count;
  	  $a['row']=$ds['msg']['rows'][0];
  	  
  	  if ($ds['msg']['rows'][0]["isread"] != "1")
  	  {
    	  //标记已读
    	  $sql_insert=array();
        $all_param=array();
        $sql="update we_message set isread='1' where msg_id=?";
        $sql_insert[]=$sql;
        $all_param[]=array((string)$ds['msg']['rows'][0]['msg_id']);
        $da->ExecSQLs($sql_insert,$all_param);
        
        $a["needsubmsgnum"] = "1";
      }
  	}
  	$a['msg_id']=$msg_id;
  	$a['lastone']=$this->lastone($re_type, $msg_id);
  	$a['nextone']=$this->nextone($re_type, $msg_id);
  	$a['curr_network_domain']=$network_domain;
  	$a['re_type'] = $re_type;
  	$a['re_pageindex'] = $re_pageindex;
  	return $this->render('JustsyBaseBundle:Message:oneMsg.html.twig',$a);
  }
  //从数据库中删除某条消息
  public function deleteAction()
  {
  	$request = $this->getRequest();
  	$login_account=$this->get('security.context')->getToken()->getUser()->getUserName();
  	$msg_id_str=$request->get('msg_id_str');
  	$msg_ids=explode(',',trim($msg_id_str,","));
  	$params1=array();
  	$params2=array();
  	$sql="";
  	array_push($params1,(string)$login_account);
  	for($i=0;$i<count($msg_ids);$i++)
  	{
  		$sql.=",?";
  		array_push($params1,(string)$msg_ids[$i]);
  		array_push($params2,(string)$msg_ids[$i]);
  	}
  	$da = $this->get('we_data_access');
  	$sql1="delete from we_message where recver=? and msg_id in (".trim($sql,",").")";
  	$da->ExecSQL($sql1,$params1);
  	
  	$sql2="select attach_id from we_message_attach where msg_id in (".trim($sql,",").")";
  	$ds=$da->Getdata("we_message_attach",$sql2,$params2);
  	$attach_ids=$ds["we_message_attach"]["rows"];
  	
  	$dm = $this->get('doctrine.odm.mongodb.document_manager');
  	foreach($attach_ids as $row)
  	{
  	  //这里不删除具体文档，应在文档管理器中删除
//  		$doc = $dm->getRepository('JustsyMongoDocBundle:WeDocument')->find($row['attach_id']);
//      $dm->remove($doc);
//      $dm->flush();
      $sql3="delete from we_message_attach where msg_id=? and attach_id=?";
      $params3=array((string)$row['msg_id'], (string)$row['attach_id']);
      $da->ExecSQL($sql3,$params3);
  	}
  	$re=array("success"=>"1");
  	$response = new Response(json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  function lastone($type, $msg_id)
  {
  	$FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
  	
  	$isreadWhere = "";
    if($type == '0')
    {
      $isreadWhere = " ifnull(a.isread, '') in ('0', '')  ";
    }
    else if ($type == '1')
    {
      $isreadWhere = " a.isread = '1' ";
    }
    else
    {
      $isreadWhere = " 1=1 ";
    }
  	
  	$da = $this->get('we_data_access');
  	$sql="select a.*,b.nick_name,b.photo_path,concat('$FILE_WEBSERVER_URL',ifnull(b.photo_path,'')) photo_url,b.login_account from we_message a,we_staff b where
  	 				 a.msg_id>? and a.send_date>=(select send_date from we_message where we_message.msg_id=?) and b.login_account=a.sender and a.recver=(select recver from we_message where we_message.msg_id=?) and $isreadWhere order by a.send_date asc, a.msg_id asc limit 0,1";
  	$params=array((string)$msg_id,(string)$msg_id,(string)$msg_id);
  	$ds=$da->Getdata("msg",$sql,$params);
  	if($ds['msg']['recordcount']!=0)
  	{
  		return $ds['msg']['rows'][0]['msg_id'];
  	}
  	else
  	{
  	 return '';
  	}
  }
  function nextone($type, $msg_id)
  {
  	$FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
  	
  	$isreadWhere = "";
    if($type == '0')
    {
      $isreadWhere = " ifnull(a.isread, '') in ('0', '')  ";
    }
    else if ($type == '1')
    {
      $isreadWhere = " a.isread = '1' ";
    }
    else
    {
      $isreadWhere = " 1=1 ";
    }
    
  	$da = $this->get('we_data_access');
  	$sql="select a.*,b.nick_name,b.photo_path,concat('$FILE_WEBSERVER_URL',ifnull(b.photo_path,'')) photo_url,b.login_account from we_message a,we_staff b where
  	 				 a.msg_id<? and a.send_date<=(select send_date from we_message where we_message.msg_id=?) and b.login_account=a.sender and a.recver=(select recver from we_message where we_message.msg_id=?) and $isreadWhere order by a.send_date desc, a.msg_id desc limit 0,1";
  	$params=array((string)$msg_id,(string)$msg_id,(string)$msg_id);
  	$ds=$da->Getdata("msg",$sql,$params);
  	if($ds['msg']['recordcount']!=0)
  	{
  		return $ds['msg']['rows'][0]['msg_id'];
  	}
  	else
  	{
  	 return '';
  	}
  }
  public function getUnreadCountAction()
  {
  	$user = $this->get('security.context')->getToken()->getUser();
		$login_account=$user->getUserName();
		$da = $this->get('we_data_access');
		
    $sql="select count(*) num from we_message a,we_staff b where
  	 				 a.recver=? and ifnull(a.isread, '') in ('0', '')  and b.login_account=a.sender order by send_date desc";
    $params=array((string)$login_account);
	  $ds=$da->GetData("we_bulletin",$sql,$params);
		$response = new Response($ds['we_bulletin']['rows'][0]['num']);
    $response->headers->set('Content-Type', 'text/html');
    return $response;
  }
}