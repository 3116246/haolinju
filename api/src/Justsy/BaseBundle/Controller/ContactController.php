<?php
namespace Justsy\BaseBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;

class ContactController extends Controller
{
	public $network_domain=null;
	public $cur_user="";
		
	public function indexAction($network_domain)
	{
		$this->cur_user = $this->get('security.context')->getToken()->getUser();
    $circleId = $this->cur_user->get_circle_id($network_domain);  		

		$this->getGroupByUser($circleId,$this->cur_user->getUserName());
		$a["curr_network_domain"]=$network_domain;	  
	  $a["contactType"] = $this->getSubGroupTag($this->cur_user->getUserName());
	  $a["recordcount"] = $this->getContactRecordCount(0,0,null);
	  $a["photo_path"] =  $this->container->getParameter('FILE_WEBSERVER_URL').$this->cur_user->photo_path_big;
	  $a["mobile"] = $this->cur_user->mobile;
	  return $this->render('JustsyBaseBundle:FriendCircle:index_contact.html.twig',$a);
	}
	
  public function propelling_indexAction($network_domain)
	{
    $list["PropellingList"] = $this->PropellingList(1);
    $rows = $this->Propelling_rowcount();
    $list["rowcount"] = $rows;
    $list["page"] = ceil($rows/15);
	  return $this->render('JustsyBaseBundle:FriendCircle:index_msg_propelling.html.twig',$list);
	}
	
	public function getGroupByUser($circleId,$user_name) 
  {
      $da = $this->get('we_data_access');
      
      $sql = "select a.group_id, a.circle_id, a.group_name from we_groups a,we_group_staff b where a.group_id=b.group_id and a.circle_id=? and b.login_account=?";
 
      $params = array();
      $params[] = (string)$circleId;
      $params[] = (string)$user_name;
      
      $ds = $da->GetData("we_groups", $sql, $params);
      
      $this->groups = $ds["we_groups"]["rows"];
      
      return;
  }
   
   //获得分组标签
   public function getSubGroupTag($user)
   {
    //联系人分组
   	$sql = " select typeid,typename from we_addrlist_type where typeid not in('M001','M002') and owner='all'".
   	       " union ".
   	       " select distinct b.typeid,typename from we_addrlist_main a inner join we_addrlist_type b on a.owner=b.owner  where a.`owner`=? order by typeid desc";
    $da = $this->get('we_data_access');
    $ds = $da->GetData("contactType",$sql,array((string)$user));
    return $ds["contactType"]["rows"];
   }
   
   //获得组织部门
   public function GetDepartMentAction()
   {
      $this->cur_user = $this->get('security.context')->getToken()->getUser();
      $eno =	$this->cur_user->eno;
   	  $sql = "select case when substring(fafa_deptid,2)=eno then fafa_deptid else dept_id end dept_id,dept_name,parent_dept_id as parentid,case when dept_id<> parent_dept_id then (select dept_name from we_department b where a.parent_dept_id=b.dept_id) else null end parent
              from we_department a 
              where dept_id not in (select c.parent_dept_id from we_department c where a.eno=c.eno group by c.parent_dept_id having count(*)>1) and 
	                  parent_dept_id not in(select dept_id from we_department d where a.eno=d.eno and right(fafa_deptid,3) in('888','999')) and
                    (position('v' in fafa_deptid)=0 or substring(fafa_deptid,2)=eno) and eno=? order by parent desc";
      $da = $this->get('we_data_access');
      $ds = $da->GetData("depart",$sql,array((string)$eno));
      $response = new Response(json_encode($ds["depart"]["rows"]));
	    $response->headers->set('Content-Type', 'text/json');
      return $response; 
   }
   
    //我的联系人
    public function SearchContactAction(Request $request)
    {
    	 $this->cur_user = $this->get('security.context')->getToken()->getUser();
       $user =	$this->cur_user->getUserName();
       $eno =   $this->cur_user->eno;
       $pageindex = $request->get("index");
       $type = $request->get("type");
       $id = $request->get("id");
       $keyword  = $request->get("keyword");
       $every = $request ->get("every");
       //计算分页起始
       $limit = "limit ".(($pageindex - 1) * $every).",".$every;       
       $da = $this->get('we_data_access');
       $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL'); 
       $parameter = array();
       if ( $type == 0)  //所有
       {
	       $sql = " select eshortname,dept_name,duty,a.login_account,nick_name,case when photo_path_big is null or photo_path_big='' then null else concat('$FILE_WEBSERVER_URL',photo_path_big) end  headerImage,work_phone,mobile,(select code from we_role inner join we_staff_role on we_role.id=we_staff_role.roleid where staff=a.login_account limit 1) level ".
	              " from we_staff a inner join we_staff_atten b on a.login_account=b.atten_id inner join we_enterprise c on a.eno=c.eno left join we_department d on a.dept_id=d.dept_id where not exists (select 1 from we_micro_account m where b.atten_id = m.number) and b.login_account=? ".
	              " union select eshortname,dept_name,duty,login_account,nick_name,case when photo_path_big is null or photo_path_big='' then null else concat('$FILE_WEBSERVER_URL',photo_path_big) end headerImage,work_phone,mobile,(select code from we_role inner join we_staff_role on we_role.id=we_staff_role.roleid where staff=c.login_account limit 1) level from we_staff c left join we_department b on b.dept_id=c.dept_id inner join we_enterprise a on c.eno=a.eno where not exists (select 1 from we_micro_account m where c.login_account=m.number) and c.eno=? and c.login_account!=? ".
	              " union select addr_unit,depart,job,addr_mail,addr_name,null,addr_phone,addr_mobile,null from we_sns.we_addrlist_addition where status<>'delete' and owner=? order by nick_name asc ".$limit;
	       $parameter = array((string)$user,(string)$eno,(string)$user,(string)$user);
       }
       else if ( $type == 1)  //按分组查询
       {
       	  if ($id =="my_friend")
       	  {
       	  	  $sql = " select eshortname,dept_name,duty,a.login_account,nick_name,case when photo_path_big is null or photo_path_big='' then null else concat('$FILE_WEBSERVER_URL',photo_path_big) end  headerImage,work_phone,mobile,(select code from we_role inner join we_staff_role on we_role.id=we_staff_role.roleid where staff=a.login_account limit 1) level ".
	                   " from we_staff a inner join we_staff_atten b on a.login_account=b.atten_id inner join we_enterprise c on a.eno=c.eno left join we_department d on a.dept_id=d.dept_id where not exists (select 1 from we_micro_account m where b.atten_id = m.number) and b.login_account=? ".$limit;
	            $parameter = array((string)$user);
       	  }
       	  else 
       	  {
       	  	 $sql = " select addr_unit as eshortname,depart as dept_name,job as duty,addr_mail as login_account,addr_name as nick_name,null as photo_path_big,addr_phone as work_phone,addr_mobile mobile,null  'level' from we_addrlist_addition a inner join we_addrlist_main b on a.id=b.id ".
       	  	        " where a.status<>'delete' and b.typeid=? and a.owner=? order by a.id asc  ".$limit;
       	  	 $parameter = array((string)$id,(string)$user);
       	  }
       }
       else if ( $type == 2 ) //按部门查询
       {
       	  if ($id == "v".$eno)
       	  {
             $sql = "select eshortname,dept_name,duty,login_account,nick_name,case when photo_path_big is null or photo_path_big='' then null else concat('$FILE_WEBSERVER_URL',photo_path_big) end headerImage,work_phone,mobile,(select code from we_role inner join we_staff_role on we_role.id=we_staff_role.roleid where staff=c.login_account limit 1) level from we_staff c left join we_department b on b.dept_id=c.dept_id inner join we_enterprise a on c.eno=a.eno where not exists (select 1 from we_micro_account m where c.login_account=m.number) and c.eno=? ".$limit;
	           $parameter = array((string)$eno);       	  	
       	  }
       	  else
       	  {
       	  	 $sql = "select eshortname,dept_name,duty,login_account,nick_name,case when photo_path_big is null or photo_path_big='' then null else concat('$FILE_WEBSERVER_URL',photo_path_big) end headerImage,work_phone,mobile,(select code from we_role inner join we_staff_role on we_role.id=we_staff_role.roleid where staff=c.login_account limit 1) level from we_staff c left join we_department b on b.dept_id=c.dept_id inner join we_enterprise a on c.eno=a.eno where not exists (select 1 from we_micro_account m where c.login_account=m.number) and c.login_account!=? and c.dept_id=? and c.eno=? ".$limit;
	           $parameter = array((string)$user,(string)$id,(string)$eno);
	        }
	     }
       else if ( $type == 3)  //按拼音首定母查询
       {
	        $sql = " select eshortname,dept_name,duty,a.login_account,nick_name,case when photo_path_big is null or photo_path_big='' then null else concat('$FILE_WEBSERVER_URL',photo_path_big) end  headerImage,work_phone,mobile,(select code from we_role inner join we_staff_role on we_role.id=we_staff_role.roleid where staff=a.login_account limit 1) level ".
	               " from we_staff a inner join we_staff_atten b on a.login_account=b.atten_id inner join we_enterprise c on a.eno=c.eno left join we_department d on a.dept_id=d.dept_id where not exists (select 1 from we_micro_account m where b.atten_id = m.number) and chinesstoletter(left(nick_name,1))=? and b.login_account=? ".
	               " union select eshortname,dept_name,duty,login_account,nick_name,case when photo_path_big is null or photo_path_big='' then null else concat('$FILE_WEBSERVER_URL',photo_path_big) end headerImage,work_phone,mobile,(select code from we_role inner join we_staff_role on we_role.id=we_staff_role.roleid where staff=c.login_account limit 1) level from we_staff c left join we_department b on b.dept_id=c.dept_id inner join we_enterprise a on c.eno=a.eno where not exists (select 1 from we_micro_account m where c.login_account=m.number) and chinesstoletter(left(nick_name,1))=? and c.eno=? and c.login_account!=? ".
	               " union select addr_unit,depart,job,addr_mail,addr_name,null,addr_phone,addr_mobile,null from we_sns.we_addrlist_addition where status<>'delete' and chinesstoletter(left(addr_name,1))=? and owner=? order by nick_name asc ".$limit;
	        $parameter = array((string)$id,(string)$user,(string)$id,(string)$eno,(string)$user,(string)$id,(string)$user);
       }
       else if ( $type==4)
       {
       	  $sql = " select eshortname,dept_name,duty,a.login_account,nick_name,case when photo_path_big is null or photo_path_big='' then null else concat('$FILE_WEBSERVER_URL',photo_path_big) end  headerImage,work_phone,mobile,(select code from we_role inner join we_staff_role on we_role.id=we_staff_role.roleid where staff=a.login_account limit 1) level ".
	               " from we_staff a inner join we_staff_atten b on a.login_account=b.atten_id inner join we_enterprise c on a.eno=c.eno left join we_department d on a.dept_id=d.dept_id where not exists (select 1 from we_micro_account m where b.atten_id=m.number) and nick_name like concat('%',?,'%') and b.login_account=? ".
	               " union select eshortname,dept_name,duty,login_account,nick_name,case when photo_path_big is null or photo_path_big='' then null else concat('$FILE_WEBSERVER_URL',photo_path_big) end headerImage,work_phone,mobile,(select code from we_role inner join we_staff_role on we_role.id=we_staff_role.roleid where staff=c.login_account limit 1) level from we_staff c left join we_department b on b.dept_id=c.dept_id inner join we_enterprise a on c.eno=a.eno where not exists (select 1 from we_micro_account m where c.login_account=m.number) and nick_name like concat('%',?,'%') and c.eno=? and c.login_account!=? ".
	               " union select addr_unit,depart,job,addr_mail,addr_name,null,addr_phone,addr_mobile,null from we_sns.we_addrlist_addition where status<>'delete' and addr_name like concat('%',?,'%') and owner=? order by nick_name asc ".$limit;
	        $parameter = array((string)$keyword,(string)$user,(string)$keyword,(string)$eno,(string)$user,(string)$keyword,(string)$user);
       }
       $ds = $da->GetData("table",$sql,$parameter);
       $result["table"] = $ds["table"]["rows"];
       if ( $pageindex == 1)
         $result["recordcount"] = $this->getContactRecordCount($type,$id,$keyword); 
       $response = new Response(json_encode($result));
	     $response->headers->set('Content-Type', 'text/json');
	     return $response; 
    }
    
    //获得联系人总数
    public function getContactRecordCount($type,$id,$keyword)
    {
    	 $this->cur_user = $this->get('security.context')->getToken()->getUser();
    	 $eno = $this->cur_user->eno;
       $user =	$this->cur_user->getUserName();
       $da = $this->get('we_data_access');
       $parameter = array();
       if ($type == 0)
       { 
         $sql = "select a.login_account from we_staff a inner join we_staff_atten b on a.login_account=b.atten_id where not exists (select 1 from we_micro_account m where b.atten_id=m.number) and b.login_account=? ".
	              "union select c.login_account from we_staff c where not exists (select 1 from we_micro_account m where c.login_account=m.number) and c.eno=? and c.login_account!=? ".
	              "union select id from we_addrlist_addition where status<>'delete' and owner=?";
	       $parameter = array((string)$user,(string)$eno,(string)$user,(string)$user);
       }
       else if ( $type == 1) //按分组
       {
       	  if ($id =="my_friend")
       	  {
             $sql = "select a.login_account from we_staff a inner join we_staff_atten b on a.login_account=b.atten_id ".
                    "where not exists (select 1 from we_micro_account m where a.login_account=m.number) and b.login_account=?";
	           $parameter = array((string)$user);
       	  }
       	  else
       	  {
       	  	 $sql = " select a.id from we_addrlist_addition a inner join we_addrlist_main b on a.id=b.id ".
       	  	        " where a.status<>'delete' and b.typeid=? and a.owner=? ";
       	  	 $parameter = array((string)$id,(string)$user);
       	  }
       }
       else if ( $type==2)  //按部门
       {
       	  if ($id == "v".$eno)
       	  {
       	    $sql = "select a.login_account from we_staff a where not exists(select 1 from we_micro_account m where a.login_account=m.number) and  eno=?";
	          $parameter = array((string)$eno);
       	  }
       	  else
       	  {
       	     $sql = "select a.login_account from we_staff a where not exists(select 1 from we_micro_account m where a.login_account=m.number) and login_account!=? and dept_id=? and eno=?";
	           $parameter = array((string)$user,(string)$id,(string)$eno);   
	        }
       }
       else if ( $type==3 ) //按拼音
       {
          $sql = "select a.login_account from we_staff a inner join we_staff_atten b on a.login_account=b.atten_id where not exists (select 1 from we_micro_account m where b.atten_id = m.number) and chinesstoletter(left(nick_name,1))=? and b.login_account=? ".
	               "union select c.login_account from we_staff c where not exists (select 1 from we_micro_account m where c.login_account=m.number) and chinesstoletter(left(nick_name,1))=? and c.login_account!=? and c.eno=? ".
	               "union select id from we_sns.we_addrlist_addition where chinesstoletter(left(addr_name,1))=? and status<>'delete'and owner=?";
	       $parameter = array((string)$id,(string)$user,(string)$id,(string)$user,(string)$eno,(string)$id,(string)$user);
       }
       else if ( $type == 4 ) //按关键字查询
       {
          $sql = "select a.login_account from we_staff a inner join we_staff_atten b on a.login_account=b.atten_id where not exists (select 1 from we_micro_account m where b.atten_id=m.number) and nick_name like concat('%',?,'%') and b.login_account=? ".
	               "union select c.login_account from we_staff c where not exists (select 1 from we_micro_account m where c.login_account=m.number) and nick_name like concat('%',?,'%') and c.login_account!=? and c.eno=? ".
	               "union select id from we_sns.we_addrlist_addition where addr_name like concat('%',?,'%')  and status<>'delete'and owner=?";
	        $parameter = array((string)$keyword,(string)$user,(string)$keyword,(string)$user,(string)$eno,(string)$keyword,(string)$user);
       } 
       //执行查询并返回结果
    	 $ds = $da->GetData("table",$sql,$parameter);
     	 if ($ds && $ds["table"]["recordcount"]>0)
     	   $result = $ds["table"]["recordcount"];  
     	 else
     	   $result = 0;       
       return $result;
    }
    
    //人脉请求
    public function contactRequestAction(Request $request)
    {
    	 $this->cur_user = $this->get('security.context')->getToken()->getUser();
    	 $type = $request->get("type");
    	 $limit = "";
    	 if ( $type==0)
    	   $limit = " limit 6";
    	 
    	 $da = $this->get('we_data_access');
       $user =	$this->cur_user->getUserName();
       
       $sql ="select sender,recver,(select nick_name from we_staff b where a.sender=login_account) sender_name,(select nick_name from we_staff c where a.recver=login_account) recver_name,".
             " case when sender=? then 1 else 0 end state ".
             "from we_message a where a.recver not in(select case when sender=? then recver else sender end from we_message where (recver=? or sender=?) and title='好友消息' and msg_type='02') ".
             " and (sender=? or recver=?) and title='好友请求' and msg_type='02' order by send_date desc ".$limit;
       
       $parameter = array((string)$user,(string)$user,(string)$user,(string)$user,(string)$user,(string)$user);
       $ds = $da->GetData("we_message",$sql,$parameter); 
       $result = $ds["we_message"]["rows"];       
       $response = new Response(json_encode($result));
	     $response->headers->set('Content-Type', 'text/json');
	     return $response;
    }
    
    //查询消息推送人员
    public function SearchPropellingAction(Request $request)
    {       
    	 $this->cur_user = $this->get('security.context')->getToken()->getUser();
       $user =	$this->cur_user->getUserName();
       $eno =   $this->cur_user->eno;
       $type = $request->get("type");
       $id = $request->get("id");
       $keyword  = $request->get("keyword");
       $da = $this->get('we_data_access');     
       $parameter = array();
       $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL'); 
       if ( $type==0)
       {
	       $sql = "select a.login_account id,nick_name name,1 staff_type from we_staff a inner join we_staff_atten b on a.login_account=b.atten_id 
                where not exists (select 1 from we_micro_account m where b.atten_id = m.number) and b.login_account=?
                union select login_account id,nick_name name,1 from we_staff c where not exists (select 1 from we_micro_account m where c.login_account=m.number) and c.eno=? and c.login_account!=? 
                union select id,addr_name,0 staff_type from we_addrlist_addition where status<>'delete' and owner=? order by staff_type desc ";
	       $parameter = array((string)$user,(string)$eno,(string)$user,(string)$user);
       }
       else if ($type==1)
       {
       	  if ($id =="my_friend")
       	  {
       	  	  $sql = "select a.login_account id,nick_name name,chinesstoletter(left(nick_name,1)) letter,case when photo_path_small is null or photo_path_small='' then '' else concat('$FILE_WEBSERVER_URL',photo_path_small) end  img,1 staff_type from we_staff a inner join we_staff_atten b on a.login_account=b.atten_id ".
                     "where not exists (select 1 from we_micro_account m where b.atten_id = m.number) and b.login_account=? order by letter asc ";
	            $parameter = array((string)$user);
       	  }
       	  else 
       	  {
       	  	 $sql = "select a.id,addr_name name,chinesstoletter(left(addr_name,1)) letter,'' img,0 staff_type ".
       	  	        "from we_addrlist_addition a inner join we_addrlist_main b on a.id=b.id where a.status<>'delete' and b.typeid=? and a.owner=? order by letter asc ";
       	  	 $parameter = array((string)$id,(string)$user);
       	  }
       }
       else if ( $type == 2 ) //按部门查询
       {
       	  if ($id == "v".$eno)
       	  {
             $sql = "select login_account id,nick_name name,chinesstoletter(left(nick_name,1)) letter,case when photo_path_small is null or photo_path_small='' then '' else concat('$FILE_WEBSERVER_URL',photo_path_small) end img,1 staff_type from we_staff c where not exists (select 1 from we_micro_account m where c.login_account=m.number) and c.eno=? order by letter asc";
	           $parameter = array((string)$eno);       	  	
       	  }
       	  else
       	  {
       	  	 $sql = "select c.login_account id,nick_name name,chinesstoletter(left(nick_name,1)) letter,case when photo_path_small is null or photo_path_small='' then '' else concat('$FILE_WEBSERVER_URL',photo_path_small) end img,1 staff_type from we_staff c left join we_department b on b.dept_id=c.dept_id 
                     where not exists (select 1 from we_micro_account m where c.login_account=m.number) and c.login_account!=? and c.dept_id=? and c.eno=? order by letter asc";
	           $parameter = array((string)$user,(string)$id,(string)$eno);
	        }
	     }
       else if ( $type == 3)  //按拼音首定母查询
       {
	        $sql = "select a.login_account id,nick_name name,chinesstoletter(left(nick_name,1)) letter,case when photo_path_small is null or photo_path_small='' then '' else concat('$FILE_WEBSERVER_URL',photo_path_small) end img,1 staff_type from we_staff a inner join we_staff_atten b on a.login_account=b.atten_id
                 where not exists (select 1 from we_micro_account m where b.atten_id = m.number) and chinesstoletter(left(nick_name,1))=? and b.login_account=? 
                 union select login_account id,nick_name name,chinesstoletter(left(nick_name,1)) letter,case when photo_path_small is null or photo_path_small='' then '' else concat('$FILE_WEBSERVER_URL',photo_path_small) end img,1 staff_type from we_staff where not exists (select 1 from we_micro_account where login_account=number) and chinesstoletter(left(nick_name,1))=? and eno=? and login_account!=? 
                 union select id,addr_name name,chinesstoletter(left(addr_name,1)) letter,'' img,0  from we_addrlist_addition where status<>'delete' and chinesstoletter(left(addr_name,1))=? and owner=? order by letter asc ";
	        $parameter = array((string)$id,(string)$user,(string)$id,(string)$eno,(string)$user,(string)$id,(string)$user);
       }
       else if($type==4)
       {
	        $sql = "select a.login_account id,nick_name name,chinesstoletter(left(nick_name,1)) letter,case when photo_path_small is null or photo_path_small='' then '' else concat('$FILE_WEBSERVER_URL',photo_path_small) end img,1 staff_type from we_staff a inner join we_staff_atten b on a.login_account=b.atten_id
                 where not exists (select 1 from we_micro_account m where b.atten_id = m.number) and nick_name like concat('%',?,'%') and b.login_account=? 
                 union select login_account id,nick_name name,chinesstoletter(left(nick_name,1)) letter,case when photo_path_small is null or photo_path_small='' then '' else concat('$FILE_WEBSERVER_URL',photo_path_small) end img,1 staff_type from we_staff where not exists (select 1 from we_micro_account where login_account=number) and nick_name like concat('%',?,'%') and eno=? and login_account!=? 
                 union select id,addr_name name,chinesstoletter(left(addr_name,1)) letter,'' img,0  from we_addrlist_addition where status<>'delete' and addr_name like concat('%',?,'%') and owner=? order by letter asc ";
	        $parameter = array((string)$keyword,(string)$user,(string)$keyword,(string)$eno,(string)$user,(string)$keyword,(string)$user);
       }
       $ds = $da->GetData("table",$sql,$parameter);
       $result["table"] = $ds["table"]["rows"];
       $response = new Response(json_encode($result));
	     $response->headers->set('Content-Type', 'text/json');
	     return $response; 
    }
    
    public function AddPropellingAction()
    {
    	$this->cur_user = $this->get('security.context')->getToken()->getUser();
      $request = $this->getRequest();
      //获取的字段
      $keyid = $request ->get("remind_id");
      $detailsid = $request ->get("remind_detailsid");
      $immediately = $request->get("immediately");
      $year = null;
      $month = null;
      $day = null;
      $hour=null;
      $minute=null;
      if ($immediately=="false") //如果为定时发送
      {
      	$year =  $request ->get("_year");
			  $month = $request->get("_month");
			  $day = $request->get("_day");
			  $hour = $request->get('_hour');
			  $minute = $request ->get("_minute");
		  }
			$content = $request->get("propelling_content");			
			$staff_jid = $request->get("staff_jid");
			$staff_type = $request->get("staff_jid_type");
		  if ($keyid==null && empty($keyid)) //添加
		    $result = $this->AddRemind($year,$month,$day,$hour,$minute,$content,$staff_jid,$staff_type,$immediately);      
		  else
		  {
		  	$edit_staff = $request->get("edit_staff");
		    $result = $this->EditRemind($keyid,$detailsid,$edit_staff,$year,$month,$day,$hour,$minute,$content,$staff_jid,$staff_type,$immediately);
		  }
			$response=new Response(json_encode($result));
		  $response->headers->set('Content-Type', 'text/json');
	    return $response;
    }
    
    //添加或修改提醒信息表
    private function AddRemind($year,$month,$day,$hour,$minute,$content,$staff_jid,$staff_type,$immediately)
    {
    	$this->cur_user = $this->get('security.context')->getToken()->getUser();
	  	$create_staff =	$this->cur_user->getUserName();
	  	$da = $this->get('we_data_access');
	  	$keyid = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_remind", "id");
	    //添加we_remind表
	    $sql="";
	    $sqls = array();
	    $parameters = array();
	    $parameter = array();
	    if ($immediately=="true")  //即时发送
	    {
	       $sql = "insert into we_remind(id,year,month,day,hour,minute,remind_content,remind_type,send_type,create_staffid,create_date)value(?,year(curdate()),month(curdate()),day(curdate()),hour(now()),minute(now()),?,'0','0,1,2',?,now())";
		     $parameter = array($keyid,$content,$create_staff);
	    }
	    else
	    {
		    $sql = "insert into we_remind(id,year,month,day,hour,minute,remind_content,remind_type,send_type,create_staffid,create_date)value(?,?,?,?,?,?,?,'0','0,1,2',?,now())";
		    $parameter = array($keyid,$year,$month,$day,$hour,$minute,$content,$create_staff);
	    }
	    array_push($sqls,$sql);
	    array_push($parameters,$parameter);
	    //添加we_remind_details表
	    $staff =  explode(",",$staff_jid);
	    $stafftype = explode(",",$staff_type);
	    $detailsid = null;
	    
	    if ($year!=null)
	       $date = $year."-".$month."-".$day." ".$hour.":".$minute;
	       
	    for($i=0;$i< count($staff);$i++)
	    {
	    	 $detailsid = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_remind_details", "detailsid");
	    	 if ($immediately=="true")  //即时发送
	    	 {
		    	  $sql = "insert into we_remind_details(detailsid,remindid,remindcontent,remind_date,state,remind_staffid,staff_type,remind_category)values(?,?,?,date_add(now(),interval 20 second),1,?,?,2)";
		    		$parameter = array((string)$detailsid,(string)$keyid,(string)$content,(string)$staff[$i],(string)$stafftype[$i]);
		    		array_push($sqls,$sql);
		    		array_push($parameters,$parameter);	    			
	    	 }
	    	 else
	    	 {
		    		$sql = "insert into we_remind_details(detailsid,remindid,remindcontent,remind_date,state,remind_staffid,staff_type,remind_category)values(?,?,?,?,1,?,?,2)";
		    		$parameter = array((string)$detailsid,(string)$keyid,(string)$content,(string)$date,(string)$staff[$i],(string)$stafftype[$i]);
		    		array_push($sqls,$sql);
		    		array_push($parameters,$parameter);
	    	 }
	    }
	    $result = true;
	    try
	    {
	      $da->ExecSQLs($sqls,$parameters);
	    }
	    catch (\Exception $e) 
	    {
	    	 $result = false;
	    }
	    return $result;
    }
    
    //修改提醒信息表
    private function EditRemind($keyid,$detailsid,$edit_staff,$year,$month,$day,$hour,$minute,$content,$staff_jid,$staff_type,$immediately)
    {
	  	$da = $this->get('we_data_access');
	    $sql="";
	    $sqls = array();
	    $parameters = array();
	    $parameter = array();	    
	    //添加we_remind_details表
	    $staff =  explode(",",$staff_jid);
	    $stafftype = explode(",",$staff_type);	    
		  for($i=0;$i< count($staff);$i++)
		  { 
	    	 if ($immediately=="true")  //即时发送
	    	 {
	    	 	  if ( count($staff)==1 || $edit_staff==$staff[$i])
	    	 	  {
			    	  $sql = "update we_remind_details set remindcontent=?,remind_date=date_add(now(),interval 20 second),remind_staffid=?,staff_type=? where detailsid=?";
			    		$parameter = array((string)$content,(string)$staff[$i],(string)$stafftype[$i],(string)$detailsid);
			    		array_push($sqls,$sql);
			    		array_push($parameters,$parameter);
	    	 	  }
	    	 	  else
	    	 	  {
		    	 	  $newid = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_remind_details", "detailsid");
			    	  $sql = "insert into we_remind_details(detailsid,remindid,remindcontent,remind_date,state,remind_staffid,staff_type,remind_category)values(?,?,?,date_add(now(),interval 20 second),1,?,?,2)";
			    		$parameter = array((string)$newid,(string)$keyid,(string)$content,(string)$staff[$i],(string)$stafftype[$i]);
			    		array_push($sqls,$sql);
			    		array_push($parameters,$parameter);
		    	  }
	    	 }
	    	 else
	    	 {
	    	 	  $date = $year."-".$month."-".$day." ".$hour.":".$minute;
	    	 	  if ( count($staff)==1 || $edit_staff==$staff[$i])
	    	 	  {
			    	  $sql = "update we_remind_details set remindcontent=?,remind_date=?,remind_staffid=?,staff_type=? where detailsid=?";
			    		$parameter = array((string)$content,(string)$date,(string)$staff[$i],(string)$stafftype[$i],(string)$detailsid);
			    		array_push($sqls,$sql);
			    		array_push($parameters,$parameter);
	    	 	  }
	    	 	  else
	    	 	  {
	    	 	  	 $newid = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_remind_details", "detailsid");
	    	 	  	 $sql = "insert into we_remind_details(detailsid,remindid,remindcontent,remind_date,state,remind_staffid,staff_type,remind_category)values(?,?,?,?,1,?,?,2)";
		    		   $parameter = array((string)$newid,(string)$keyid,(string)$content,(string)$date,(string)$staff[$i],(string)$stafftype[$i]);
		    		   array_push($sqls,$sql);
		    		   array_push($parameters,$parameter);
		    		}
	    	 }
		  }
	    $result = true;
	    try
	    {
	      $da->ExecSQLs($sqls,$parameters);
	    }
	    catch (\Exception $e) 
	    {
	    	 $result = false;
	    }
	    return $result;
    }
    
    //推送消息列表
    public function PropellingList($pageindex)
    {
    	$everypage = 15;
    	$limit = " limit ".(($pageindex - 1) * $everypage).",".$everypage;
    	$this->cur_user = $this->get('security.context')->getToken()->getUser();
	  	$ownerid =	$this->cur_user->getUserName();
	  	$da = $this->get('we_data_access');
	  	$sql = "select remindid,detailsid,nick_name,date_format(remind_date,'%Y-%m-%d %H:%i') remind_date,remindcontent,remind_staffid,staff_type,a.state 
	  	        from we_remind_details a inner join we_remind b on remindid=id inner join we_staff c on a.remind_staffid=c.login_account
              where staff_type=1 and remind_category=2 and create_staffid=? 
              union 
              select remindid,detailsid,addr_name,date_format(remind_date,'%Y-%m-%d %H:%i') remind_date,remindcontent,remind_staffid,staff_type,a.state
              from we_remind_details a inner join we_remind b on a.remindid=b.id inner join we_addrlist_addition c on a.remind_staffid=c.id
              where staff_type=0 and remind_category=2 and create_staffid=? order by state desc,remind_date asc ".$limit;
      $ds = $da->GetData("table",$sql,array((string)$ownerid,(string)$ownerid));
	    return $ds["table"]["rows"];
    }
    
    //
    //获得推送消息总记录数
    //
    private function Propelling_rowcount()
    {  
    	$this->cur_user = $this->get('security.context')->getToken()->getUser();
	  	$ownerid =	$this->cur_user->getUserName();
	  	$da = $this->get('we_data_access');
	  	$sql = "select count(*) 'rows' from we_remind_details a inner join we_remind b on remindid=id where remind_category=2 and create_staffid=?";
      $ds = $da->GetData("table",$sql,array((string)$ownerid));    
      if ($ds && $ds["table"]["recordcount"]>0)
     	   $result = $ds["table"]["rows"][0]["rows"]; 
     	 else
     	   $result = 0;
	    return $result;
    }
    
    public function SearchMessageAction(Request $request)
    {
    	$pageindex = $request->get("pageindex");
    	$result = $this->PropellingList($pageindex);
			$response=new Response(json_encode($result));
		  $response->headers->set('Content-Type', 'text/json');
	    return $response;
    }
    
    //删除推送消息
    public function DeleteMessageAction(Request $request)
    {
    	 $detailsid = $request->get("detailsid");
    	 $remindid = $request->get("remindid");
    	 $da = $this->get('we_data_access');
    	 $sql = "select count(*) `count` from we_remind_details where remindid=?";
    	 $ds = $da->GetData("table",$sql,array((string)$remindid));
    	 $result = false;
    	 if ($ds && $ds["table"]["recordcount"]>0)
    	 {
    	 	  try
    	 	  {
	    	 	  if ( (int)$ds["table"]["rows"][0]["count"]==1)  //只有一条详细时还得删除主表数据
	    	 	  {
	    	 	  	$sqls = array();
	    	 	  	$paras = array();
	    	 	  	$sql = "delete from we_remind where id=?";
	    	 	  	array_push($sqls,$sql);
	    	 	  	array_push($paras,array((string)$remindid));
	    	 	  	$sql = "delete from we_remind_details where detailsid=?";
	    	 	  	array_push($sqls,$sql);
	    	 	  	array_push($paras,array((string)$detailsid));
	    	 	  	$da->ExecSQLs($sqls,$paras);
	    	 	  }
	    	 	  else
	    	 	  {
	    	 	    $sql = "delete from we_remind_details where detailsid=?";
	    	 	    $da->ExecSQL($sql,array((string)$detailsid));
	    	 	  }
	    	 	  $result = true;
    	 	  }
    	 	  catch (\Exception $e)
    	 	  {
	    	    $result = false;
	        }
    	 }
			 $response=new Response(json_encode($result));
		   $response->headers->set('Content-Type', 'text/json');
	     return $response;
    }
}