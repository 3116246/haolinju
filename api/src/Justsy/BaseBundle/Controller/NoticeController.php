<?php
namespace Justsy\BaseBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\DataAccess\DataAccess;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
class NoticeController extends Controller
{
	public $groups=null;
	public $staffs=null;
	public $notice_collection=null;
	public $photo_url="";
	public function indexAction($network_domain)
	{
		$user = $this->get('security.context')->getToken()->getUser();
		$curr_circle_id=$user->get_circle_id($network_domain);
		$login_account=$user->getUserName();
	  $num=0;
		
		//控制通知发布权限
		$da = $this->get('we_data_access');
		$sql="select create_staff,manager from we_circle where circle_id=?";
		$params=array((string)$curr_circle_id);
		$ds=$da->Getdata('we_circle',$sql,$params);

	  if($ds['we_circle']['recordcount']>0)
	  {
	  	if($login_account==$ds['we_circle']['rows'][0]['create_staff'])
	  	{
	  		$num++;
	  	}
	  	if($ds['we_circle']['rows'][0]['manager']!=''&&$ds['we_circle']['rows'][0]['manager']!=null)
	  	{
	  		$managers=explode(',',$ds['we_circle']['rows'][0]['manager']);
	  		for($i=0;$i<count($managers);$i++)
	  		{
	  			if($managers[$i]==$login_account)
	  			{
	  				$num++;
	  			}
	  		}
	  	}
	  }
	  
//	  $sql="select create_staff from we_groups where circle_id=?";
//	  $params=array((string)$curr_circle_id);
//	  $ds=$da->Getdata('we_groups',$sql,$params);
//	  if($ds['we_groups']['recordcount']>0)
//	  {
//	  	foreach($ds['we_groups']['rows'] as $row)
//	  	{
//	  		if($row['create_staff']==$login_account)
//	  		{
//	  			$num++;
//	  		}
//	  	}
//	  }
		//$sql="select count(*)as num from( (select count(*)as b from we_circle where ? in(create_staff,manager)) union (select count(*)as b from we_groups where create_staff=?))t where b!=0";
		//$params=array((string)$login_account,(string)$login_account);
		//$ds=$da->Getdata("we",$sql,$params);
		
		return $this->render('JustsyBaseBundle:Notice:index.html.twig',array("count"=> $num,"curr_network_domain"=>$network_domain));//当num的值为0时表示该用户没有发布公告的权限
	}
	// 通知发布组件
	public function pushNoticeAction($network_domain)
	{
		  $user = $this->get('security.context')->getToken()->getUser();
      $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
      $login_account=$user->getUserName();
      
      $da = $this->get('we_data_access');
		  $sql="select create_staff,manager from we_circle where circle_id=?";
		  $params=array((string)$user->get_circle_id($network_domain));
		  $ds=$da->Getdata('we_circle',$sql,$params);
		  $num=0;
		  if($ds['we_circle']['recordcount']>0)
	    {
	  	if($login_account==$ds['we_circle']['rows'][0]['create_staff'])
	  	{
	  		$num++;
	  	}
	  	if($ds['we_circle']['rows'][0]['manager']!=''&&$ds['we_circle']['rows'][0]['manager']!=null)
	  	{
	  		$managers=explode(',',$ds['we_circle']['rows'][0]['manager']);
	  		for($i=0;$i<count($managers);$i++)
	  		{
	  			if($managers[$i]==$login_account)
	  			{
	  				$num++;
	  			}
	  		}
	  	}
	    }
		  if($num==0)
		  {
		  	$sql1 = "select group_id, group_name, group_photo_path, concat('$FILE_WEBSERVER_URL', ifnull(group_photo_path, '')) group_photo_url
from we_groups 
where create_staff=? and circle_id=?";
      $params1 = array();
      $params1[] = (string)$user->getUserName();
      $params1[] = (string)$user->get_circle_id($network_domain);
      
      $ds=$da->Getdata("we_groups",$sql1,$params1);
		  }
		  else
		  {
		  	$sql1="select group_id, group_name, group_photo_path, concat('$FILE_WEBSERVER_URL', ifnull(group_photo_path, '')) group_photo_url
from we_groups where circle_id=?";
				$params1=array((string)$user->get_circle_id($network_domain));
				$ds=$da->Getdata("we_groups",$sql1,$params1);
		  }
      //取出当前圈子中该用户参与的群组     
      $this->groups = json_encode($ds["we_groups"]["rows"]); 
		  $a["this"]=$this;
		  $a["num"]=$num;
		  $a["group"]=$ds["we_groups"]["rows"];
		  $a["curr_network_domain"]=$network_domain;
		return $this->render('JustsyBaseBundle:Notice:pushNotice.html.twig',$a);
	}
	//发布通知
	public function noticePublishAction($network_domain)
	{
		  $request = $this->getRequest();
      $user = $this->get('security.context')->getToken()->getUser();
      $da = $this->get('we_data_access');
      $notice_content = $request->get('notice');
      $post_to_group = $request->get('post_to_group');
      $bulletin_id=\Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_bulletin", "bulletin_id");
      
      //跟新we_bulletin表
      $sqls = array();
      $all_params = array();
      /*
      if(($post_to_group=="all"||$post_to_group=="ALL")&&$this->isPower($network_domain)==0)
      {
      	$sql1 = "select group_id from we_groups where create_staff=? and circle_id=?";
      	$params1 = array();
        $params1[] = (string)$user->getUserName();
        $params1[] = (string)$user->get_circle_id($network_domain);
        $ds=$da->Getdata("we_groups",$sql1,$params1);
        
        foreach($ds['we_groups']['rows'] as $row)
        {
        	$sqlInsert = 'insert into we_bulletin (bulletin_date,bulletin_desc,bulletin_id,group_id,circle_id,bulletin_staff) values (CURRENT_TIMESTAMP(), ?, ?, ?, ?,?)';
          $params = array();
          $params[]=(string)$notice_content;
          $params[] = (string)$bulletin_id;
          $params[] = (string)$post_to_group;
          $params[] = (string)$user->get_circle_id($network_domain);
          $params[]=(string)$user->getUserName();
           
      $sqls[] = $sqlInsert;
      $all_params[] = $params;
        }
      }
      else()
      {
      	
      }
      */
      $sqlInsert = 'insert into we_bulletin (bulletin_date,bulletin_desc,bulletin_id,group_id,circle_id,bulletin_staff) values (CURRENT_TIMESTAMP(), ?, ?, ?, ?,?)';
      $params = array();
      $params[]=(string)$notice_content;
      $params[] = (string)$bulletin_id;
      $params[] = (string)$post_to_group;
      $params[] = (string)$user->get_circle_id($network_domain);
      $params[]=(string)$user->getUserName();
           
      $sqls[] = $sqlInsert;
      $all_params[] = $params;
      $da->ExecSQLs($sqls, $all_params);
      
      //跟新we_notify表，保存未读的通知性息
      $sql_Insert="insert into we_notify (notify_type,msg_id,notify_staff) values('01',?,?)";
      $data=null;
      if($post_to_group=="ALL"||$post_to_group=="all")
      {
      	$sql_str="select login_account from we_circle_staff where circle_id=?";
      	$params_array=array((string)$user->get_circle_id($network_domain));
      	//$param[]=(string)$user->get_circle_id($network_domain);
      	$ds=$da->Getdata("we_circle_staff",$sql_str,$params_array);
      	$data=$ds['we_circle_staff']['rows'];
      }
      else
      {
      	$sql_str="select login_account from we_group_staff where group_id=?";
      	$params_array=array((string)$post_to_group);
      	//$param[]=(string)$post_to_group;
      	$ds=$da->Getdata("we_group_staff",$sql_str,$params_array);
      	$data=$ds['we_group_staff']['rows'];
      }
      foreach($data as $row)
      {
      	$param=array();
      	$param[]=(string)$bulletin_id;
      	$param[]=(string)$row['login_account'];
        $da->ExecSQL($sql_Insert,$param);
      }
      
      $re = array('success' => '1', 'bulletin_id' => $bulletin_id);
      $response = new Response(json_encode($re));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
	}
	//显示通知
	public function noticeShowAction($network_domain)
	{
		$user = $this->get('security.context')->getToken()->getUser();
		$curr_circle_id=$user->get_circle_id($network_domain);
		$request = $this->getRequest();
		$login_account=$user->getUserName();
		$da = $this->get('we_data_access');
		$da->PageSize=10;
		$da->PageIndex=(int)($request->get('pageindex'))-1;
		$type=$request->get('type');
  	$pageindex = (int)($request->get('pageindex'))-1;
		$notice=null;
		$a=array();
		if($type=="all")
		{
			$sql="select a.bulletin_id, a.circle_id, a.group_id, a.bulletin_date, case when LENGTH(a.bulletin_desc) > 300 then concat(SUBSTR(a.bulletin_desc, 1, 297), '...') else a.bulletin_desc end bulletin_desc, bulletin_staff 
from we_bulletin a 
where a.circle_id=?
  and a.group_id in (select 'ALL' group_id from dual
										union
										select wgs.group_id 
                    from we_group_staff wgs, we_groups wg 
                    where wgs.group_id=wg.group_id and wgs.login_account=? and wg.circle_id=a.circle_id)
order by a.bulletin_date desc, a.bulletin_id desc";
			$params=array((string)$curr_circle_id,(string)$login_account);
			$ds=$da->GetData("we_bulletin",$sql,$params);
			$notice=$ds["we_bulletin"]["rows"];
			$a["pagecount"]=ceil($ds["we_bulletin"]["recordcount"]/($da->PageSize));
		}
		else if($type=="1")
		{
			$sql="select a.bulletin_id, a.circle_id, a.group_id, a.bulletin_date, case when LENGTH(a.bulletin_desc) > 300 then concat(SUBSTR(a.bulletin_desc, 1, 297), '...') else a.bulletin_desc end bulletin_desc, bulletin_staff 
from we_bulletin a 
where a.circle_id=?
  and a.group_id in (select 'ALL' group_id from dual
										union
										select wgs.group_id 
                    from we_group_staff wgs, we_groups wg 
                    where wgs.group_id=wg.group_id and wgs.login_account=? and wg.circle_id=a.circle_id)
  and not exists(select 1 from we_notify wn where wn.notify_type='01' and wn.msg_id=a.bulletin_id and wn.notify_staff=?)
order by a.bulletin_date desc, a.bulletin_id desc";
			$params=array((string)$curr_circle_id, (string)$login_account, (string)$login_account);
			$ds=$da->GetData("we_bulletin",$sql,$params);
			$notice=$ds["we_bulletin"]["rows"];
			$a["pagecount"]=ceil($ds["we_bulletin"]["recordcount"]/($da->PageSize));
		}
		else if($type=="0")
		{
			$sql="select a.bulletin_id, a.circle_id, a.group_id, a.bulletin_date, case when LENGTH(a.bulletin_desc) > 300 then concat(SUBSTR(a.bulletin_desc, 1, 297), '...') else a.bulletin_desc end bulletin_desc, bulletin_staff 
from we_bulletin a 
where a.circle_id=?
  and a.group_id in (select 'ALL' group_id from dual
										union
										select wgs.group_id 
                    from we_group_staff wgs, we_groups wg 
                    where wgs.group_id=wg.group_id and wgs.login_account=? and wg.circle_id=a.circle_id)
  and exists(select 1 from we_notify wn where wn.notify_type='01' and wn.msg_id=a.bulletin_id and wn.notify_staff=?)
order by a.bulletin_date desc, a.bulletin_id desc";
			$params=array((string)$curr_circle_id, (string)$login_account, (string)$login_account);
			$ds=$da->GetData("we_bulletin",$sql,$params);
			$notice=$ds["we_bulletin"]["rows"];
			$a["pagecount"]=ceil($ds["we_bulletin"]["recordcount"]/($da->PageSize));
			
			$sql="delete from we_notify where notify_type='01' and notify_staff=?";
      $params=array( (string)$user->getUserName() );
      $da->ExecSQL($sql,$params);
		}
		$da->PageSize= -1;
	  $da->PageIndex= 0;
		foreach($notice as $row)
		{
			$sql_str="select photo_path,nick_name,login_account from we_staff where login_account=?";
			$parameter=array((string)$row["bulletin_staff"]);
			$ds=$da->GetData("we_staff",$sql_str,$parameter);
			if ($ds["we_staff"]["recordcount"] > 0)
			{
  			$row["staff_name"]=$ds["we_staff"]["rows"][0]["nick_name"];
  			$row["photo_path"]=$ds["we_staff"]["rows"][0]["photo_path"];
  			$row["login_account"]=$ds["we_staff"]["rows"][0]["login_account"];
  		}
  		else
  		{
  			$row["staff_name"]="";
  			$row["photo_path"]="";
  			$row["login_account"]="";
  		}
			$this->notice_collection[]=$row;
		}
		$this->photo_url = $this->container->getParameter('FILE_WEBSERVER_URL');
		$a["text"]=$this;
		$a["curr_account"]=$login_account;
		$a["type"]=$type;
		$a["curr_network_domain"]=$network_domain;
		$a["pageindex"] = $pageindex+1;
		return $this->render("JustsyBaseBundle:Notice:showNotice.html.twig",$a);
	}
	//某一条通知的详细性息
	public function oneNoticeAction($network_domain)
	{
		$user = $this->get('security.context')->getToken()->getUser();
		$request = $this->getRequest();
		$bulletin_id=$request->get('bulletin_id');
		$direction=$request->get('direction');
  	$re_type = $request->get('re_type');
  	$re_pageindex = $request->get('re_pageindex');
		$row=array();
		if($bulletin_id!=null)
		{
			$da = $this->get('we_data_access');
			$sql="";
			$params=null;
			$ds=null;
		  $sql="select * from we_bulletin where bulletin_id=?";
		  $params=array((string)$bulletin_id);
		  $d=$da->Getdata("we_bulletin",$sql,$params);	
			if($d['we_bulletin']['recordcount']!=0)
			{
				$row=$d["we_bulletin"]["rows"][0];
			  $sql_str="select photo_path,nick_name,login_account from we_staff where login_account=?";
			  $parameter=array((string)$row["bulletin_staff"]);
			  $ds=$da->Getdata("we_staff",$sql_str,$parameter);
			  if ($ds["we_staff"]["recordcount"] >0)
			  {
  			  $row["nick_name"]=$ds["we_staff"]["rows"][0]["nick_name"];
  			  $row["photo_path"]=$ds["we_staff"]["rows"][0]["photo_path"];
  			  $row["login_account"]=$ds["we_staff"]["rows"][0]["login_account"];
			  }
			  else
			  {
			    $row["nick_name"]="";
  			  $row["photo_path"]="";
  			  $row["login_account"]="";
			  }
			  
			  $sql_insert=array();
        $all_param=array();
        $sql="delete from we_notify where notify_type='01' and msg_id=? and notify_staff=?";
        $sql_insert[]=$sql;
        $all_param[]=array((string)$d['we_bulletin']['rows'][0]['bulletin_id'],(string)$user->getUserName());
        $da->ExecSQLs($sql_insert,$all_param);
			}
			$this->photo_url = $this->container->getParameter('FILE_WEBSERVER_URL');
		}
		$a['this']=$this;
		$a['row']=$row;
		$a['lastone']=$this->lastone($re_type, $bulletin_id,$network_domain);
		$a['nextone']=$this->nextone($re_type, $bulletin_id,$network_domain);
		$a["curr_network_domain"]=$network_domain;
		$a["bulletin_id"] = $bulletin_id;
  	$a['re_type'] = $re_type;
  	$a['re_pageindex'] = $re_pageindex;
		return $this->render('JustsyBaseBundle:Notice:oneNotice.html.twig',$a);
	}
	public function lastone($type, $bulletin_id,$network_domain)
	{
		$da = $this->get('we_data_access');
	  $user = $this->get('security.context')->getToken()->getUser();
		$curr_circle_id=$user->get_circle_id($network_domain);
	  
	  $isreadWhere = ($type == "1" ? "not" : "");
	  
	  $sql = "select a.bulletin_id 
from we_bulletin a 
where a.circle_id=?
  and a.group_id in (select 'ALL' group_id from dual
										union
										select wgs.group_id 
                    from we_group_staff wgs, we_groups wg 
                    where wgs.group_id=wg.group_id and wgs.login_account=? and wg.circle_id=a.circle_id)
  and $isreadWhere exists(select 1 from we_notify wn where wn.notify_type='01' and wn.msg_id=a.bulletin_id and wn.notify_staff=?)
  and a.bulletin_id>? and a.bulletin_date >= (select wb.bulletin_date from we_bulletin wb where wb.bulletin_id=?)
order by a.bulletin_date asc, a.bulletin_id asc";
		$params[]=(string)$curr_circle_id;
		$params[]=(string)$user->getUserName();
		$params[]=(string)$user->getUserName();
		$params[]=(string)$bulletin_id;
		$params[]=(string)$bulletin_id;
		$ds=$da->Getdata("we_bulletin",$sql,$params);
		if($ds['we_bulletin']['recordcount']!=0)
		{
			return $ds['we_bulletin']['rows'][0]['bulletin_id'];
		}
		else
		return '';
	}
	public function nextone($type, $bulletin_id,$network_domain)
	{	
		$da = $this->get('we_data_access');
		$user = $this->get('security.context')->getToken()->getUser();
		$curr_circle_id=$user->get_circle_id($network_domain);
	  
	  $isreadWhere = ($type == "1" ? "not" : "");
	  
	  $sql = "select a.bulletin_id 
from we_bulletin a 
where a.circle_id=?
  and a.group_id in (select 'ALL' group_id from dual
										union
										select wgs.group_id 
                    from we_group_staff wgs, we_groups wg 
                    where wgs.group_id=wg.group_id and wgs.login_account=? and wg.circle_id=a.circle_id)
  and $isreadWhere exists(select 1 from we_notify wn where wn.notify_type='01' and wn.msg_id=a.bulletin_id and wn.notify_staff=?)
  and a.bulletin_id<? and a.bulletin_date <= (select wb.bulletin_date from we_bulletin wb where wb.bulletin_id=?)
order by a.bulletin_date desc, a.bulletin_id desc";
		$params[]=(string)$curr_circle_id;
		$params[]=(string)$user->getUserName();
		$params[]=(string)$user->getUserName();
		$params[]=(string)$bulletin_id;
		$params[]=(string)$bulletin_id;
		$ds=$da->Getdata("we_bulletin",$sql,$params);
		if($ds['we_bulletin']['recordcount']!=0)
		{
			return $ds['we_bulletin']['rows'][0]['bulletin_id'];
		}
		else
		return '';
	}
	//获得新通知个数
	public function getUnreadCountAction($network_domain)
	{
		$user = $this->get('security.context')->getToken()->getUser();
		$login_account=$user->getUserName();
		$da = $this->get('we_data_access');
    $sql="select count(*) num from we_notify a where a.notify_type='01' and a.notify_staff=?";
    $params=array((string)$login_account);
	  $ds=$da->GetData("we_bulletin",$sql,$params);
		$response = new Response($ds['we_bulletin']['rows'][0]['num']);
    $response->headers->set('Content-Type', 'text/html');
    return $response;
	}
	public function isPower($network_domain)
	{
		$user = $this->get('security.context')->getToken()->getUser();
		$curr_circle_id=$user->get_circle_id($network_domain);
		$login_account=$user->getUserName();
		
		$da = $this->get('we_data_access');
		$sql="select create_staff,manager from we_circle where circle_id=?";
		$params=array((string)$curr_circle_id);
		$ds=$da->Getdata('we_circle',$sql,$params);
		$num++;
	  if($ds['we_circle']['recordcount']>0)
	  {
	  	if($login_account==$ds['we_circle']['rows'][0]['create_staff'])
	  	{
	  		$num++;
	  	}
	  	if($ds['we_circle']['rows'][0]['manager']!=''&&$ds['we_circle']['rows'][0]['manager']!=null)
	  	{
	  		$managers=explode(',',$ds['we_circle']['rows'][0]['manager']);
	  		for($i=0;$i<count($managers);$i++)
	  		{
	  			if($managers[$i]==$login_account)
	  			{
	  				$num++;
	  			}
	  		}
	  	}
	  }
	  return $num;
	}
	
	public function getTopNoticeAction($network_domain)
	{
		$user = $this->get('security.context')->getToken()->getUser();
		$curr_circle_id=$user->get_circle_id($network_domain);
	  	  
	  $sql = "select bulletin_id, SUBSTR(bulletin_desc, 1, 100) bulletin_desc 
from we_bulletin 
where circle_id=? and group_id='ALL' order by bulletin_date desc limit 0, 1";
    $params = array();
    $params[] = (string)$curr_circle_id;
    
    $da = $this->get('we_data_access');
    $ds = $da->Getdata('we_bulletin', $sql, $params);
    
    $a = array();
    $a["curr_network_domain"] = $network_domain;
    $a['ds'] = $ds;
    
    return $this->render('JustsyBaseBundle:Notice:TopNewNotice.html.twig',$a);
	}
}
