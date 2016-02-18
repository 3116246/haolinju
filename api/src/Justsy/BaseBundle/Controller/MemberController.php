<?php
namespace Justsy\BaseBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;

class MemberController extends Controller
{
	public $photo_url="";
	public $network_domain=null;
	public $pagecount=0;
	public $recordcount=0;
	public $groups=null;
	public $cur_user="";
	
	public function memberAction($network_domain)
	{
		$this->cur_user = $this->get('security.context')->getToken()->getUser();
    $circleId = $this->cur_user->get_circle_id($network_domain);  		

		$this->getGroupByUser($circleId,$this->cur_user->getUserName());
		$this->network_domain = $network_domain;
		$a["this"]=$this;
		$a["curr_network_domain"]=$network_domain;
		if(in_array(\Justsy\BaseBundle\Management\FunctionCode::$DOC,$this->cur_user->getFunctionCodes())){
	  	$a["rostreinvite"]=true;
	  }else{
	  	$a["rostreinvite"]=false;
	  }
	  if($circleId=='9999')
	  {
	  	 $a["contactType"] = $this->getSubGroupTag($this->cur_user->getUserName());
	  	 $a["recordcount"] = $this->getContactRecordCount(0,0,null);
	  	 $a["photo_path"] =  $this->container->getParameter('FILE_WEBSERVER_URL').$this->cur_user->photo_path_big;
	  	 $a["mobile"] = $this->cur_user->mobile;
	  	 return $this->render('JustsyBaseBundle:Member:member9999.html.twig',$a);
	  }
		return $this->render('JustsyBaseBundle:Member:member.html.twig',$a);
	}
   
	public function memberMainAction($network_domain)
	{
		$this->cur_user = $this->get('security.context')->getToken()->getUser();
    $circleId = $this->cur_user->get_circle_id($network_domain);
    $this->network_domain = $network_domain;
		$request = $this->get("request");
		$order=$request->get("order");
		$searchby=$request->get("searchby");
		//首先从数据库中获取成员性息
		
		$this->getGroupByUser($circleId,$this->cur_user->getUserName());
		$a=array();
		$pno = $request->get("pageno");
		$groupid = $request->get("groupid");
		$this->getMemberByCirle($circleId,strlen($groupid)==0?"":$groupid,strlen($pno)==0?0:$pno,$order,$searchby);
	  $a["text"]=$this;
	  $a["selectdlg"]=$request->get("selectdlg");
	  $a["pageno"]=$pno;
	  $a["curr_network_domain"]=$network_domain;
	  $this->photo_url = $this->container->getParameter('FILE_WEBSERVER_URL');
	  
	  if($request->get("selectdlg")=="1")
	  {
	  	  //获取当前用户的圈子
	  	  $circles = $this->getCircleByUser($this->cur_user->getUserName());
	  	  $a["circles"] = $circles;
	      return $this->render('JustsyBaseBundle:Member:selectmemberMain.html.twig',$a);
	  }
	  else
		    return $this->render('JustsyBaseBundle:Member:memberMain.html.twig',$a);
	}
	
	public function searchMemberAction($network_domain,$filtervalue)
	{
		 $this->cur_user = $this->get('security.context')->getToken()->getUser();
		 $circleId = $this->cur_user->get_circle_id($network_domain);
		 $u = $this->cur_user->getUserName();
		 $memberinfo=array();
		 $da = $this->get('we_data_access');

     $sql_2="select l.*,f_checkAttentionWithAccount('".$this->cur_user->getUsername()."',login_account) attention from (select we_staff.nick_name,photo_path,A.login_account,date_format(we_staff.active_date,'%Y-%c-%d') register_date,we_staff.duty".
            " from we_circle_staff A, we_staff".
            " where A.login_account=we_staff.login_account and A.login_account!=? and  A.circle_id=? and (A.login_account like concat('%', ?, '%') or we_staff.nick_name like concat('%', ?, '%')) union ".
            " select we_staff.nick_name,photo_path,A.login_account,date_format(we_staff.active_date,'%Y-%c-%d') register_date,we_staff.duty ".
            " from we_group_staff A,we_groups B, we_staff".
            " where B.group_id=A.group_id and A.login_account=we_staff.login_account and A.login_account!=? and  b.circle_id=? and (A.login_account like concat('%', ?, '%') or we_staff.nick_name like concat('%', ?, '%'))".
            " ) l order by register_date desc";    
     $ds=$da->GetData("we_staff",$sql_2,array((string)$u,(string)$circleId,(string)$filtervalue,(string)$filtervalue,(string)$u,(string)$circleId,(string)$filtervalue,(string)$filtervalue));
     $this->accounts=$ds["we_staff"]["rows"];
     $this->pagecount=0;
     $a["text"]=$this;
      $request=$this->get("request");
	  if($request->get("selectdlg")=="1")
	  {
	  	  //获取当前用户的圈子
	  	  $circles = $this->getCircleByUser($this->cur_user->getUserName());
	  	  $a["circles"] = $circles;
	      return $this->render('JustsyBaseBundle:Member:selectmemberMain.html.twig',$a);
	  }
	  else
		    return $this->render('JustsyBaseBundle:Member:memberMain.html.twig',$a);  		
	}
	
	public function searchAllMemberAction($filtervalue)
	{
		 $this->cur_user = $this->get('security.context')->getToken()->getUser();
		 $u = $this->cur_user->getUserName();
		 $memberinfo=array();
		 $da = $this->get('we_data_access');

     $sql_2="select l.*,f_checkAttentionWithAccount('".$this->cur_user->getUsername()."',login_account) attention from (select we_staff.nick_name,photo_path,A.login_account,date_format(we_staff.active_date,'%Y-%c-%d') register_date,we_staff.duty".
            " from we_circle_staff A, we_staff".
            " where A.login_account=we_staff.login_account and A.login_account!=? and (A.login_account like concat('%', ?, '%') or we_staff.nick_name like concat('%', ?, '%')) union ".
            " select we_staff.nick_name,photo_path,A.login_account,date_format(we_staff.active_date,'%Y-%c-%d') register_date,we_staff.duty ".
            " from we_group_staff A,we_groups B, we_staff".
            " where B.group_id=A.group_id and A.login_account=we_staff.login_account  and A.login_account!=? and (A.login_account like concat('%', ?, '%') or we_staff.nick_name like concat('%', ?, '%'))".
            " ) l order by register_date desc";    
     $ds=$da->GetData("we_staff",$sql_2,array((string)$u,(string)$filtervalue,(string)$filtervalue,(string)$u,(string)$filtervalue,(string)$filtervalue));
     $this->accounts=$ds["we_staff"]["rows"];
     $this->pagecount=0;
     $a["text"]=$this;
     $request=$this->get("request");
	  if($request->get("selectdlg")=="1")
	  {
	  	  //获取当前用户的圈子
	  	  $circles = $this->getCircleByUser($this->cur_user->getUserName());
	  	  $a["circles"] = $circles;
	      return $this->render('JustsyBaseBundle:Member:selectmemberMain.html.twig',$a);
	  }
	  else
		    return $this->render('JustsyBaseBundle:Member:memberMain.html.twig',$a);     
	}	
	public function getMemberByCirle($cirle_id,$groupid,$pno,$order='date',$searchby='')
	{
		 $memberinfo=array();
		 $da = $this->get('we_data_access');
     $params_1 = array();
     $ordersql='';
     if($order=='date' or $order==''){
     	 $ordersql="order by register_date desc";
     }
     else if($order=='name'){
     	 $ordersql='order by convert(nick_name using gb2312) asc';
     }
     if($groupid=="")
     {
     	 $params_1[]= (string)$this->cur_user->getUserName();
       array_push($params_1,(string)$cirle_id);
     }
     else
     {        
        $params_1[]= (string)$this->cur_user->getUserName();
        array_push($params_1,(string)$groupid);
     }
     //判断是否是企业圈子
     $iseno = "";
     $ecircleid=$this->cur_user->get_circle_id("d");
     if($ecircleid==$cirle_id) $iseno="1";
     $sql_2= $groupid=="" ? "select l.*,f_checkAttentionWithAccount('".$this->cur_user->getUsername()."',login_account) attention from (select case '".$iseno."' when '1' then we_staff.duty else B.ename end ename, we_staff.nick_name,photo_path_big as photo_path,A.login_account,date_format(we_staff.active_date,'%Y-%c-%d') register_date,we_staff.duty".
            " from we_circle_staff A, we_staff,we_enterprise B".
            " where A.login_account=we_staff.login_account and we_staff.eno=B.eno and A.login_account!=? and A.circle_id=? ) l ".(empty($searchby)?"":(strlen($searchby)>mb_strlen($searchby,'utf8')?"where l.nick_name like ? ":" where l.nick_name like ? or l.login_account like ?")).$ordersql
            :
            "select case '".$iseno."' when '1' then we_staff.duty else B.ename end ename,f_checkAttentionWithAccount('".$this->cur_user->getUsername()."',A.login_account) attention, we_staff.nick_name,photo_path_big as photo_path,A.login_account,date_format(we_staff.active_date,'%Y-%c-%d') register_date,we_staff.duty ".
            " from we_group_staff A, we_staff,we_enterprise B".
            " where A.login_account=we_staff.login_account and we_staff.eno=B.eno and A.login_account!=? and A.group_id=? ".(empty($searchby)?"":(strlen($searchby)>mb_strlen($searchby,'utf8')?" and nick_name like ? ":" and (nick_name like ? or login_account like ?)")).$ordersql;
            if(!empty($searchby)){
            	array_push($params_1,"%".$searchby."%");
            	if(strlen($searchby)==mb_strlen($searchby,'utf8'))
            		array_push($params_1,"%".$searchby."%");
            }
     
     if ($cirle_id == "10000")
     {
         $sql_2 = "select l.*,f_checkAttentionWithAccount('".$this->cur_user->getUsername()."',login_account) attention from (select B.ename, we_staff.nick_name,photo_path_big as photo_path,A.login_account,date_format(we_staff.active_date,'%Y-%c-%d') register_date,we_staff.duty".
            " from we_circle_staff A, we_staff,we_enterprise B".
            " where A.login_account=we_staff.login_account and we_staff.eno=B.eno and A.login_account!=? and A.circle_id=? 
            and A.login_account in ('service@fafatime.com', 'pm@fafatime.com', 'corp@fafatime.com')) l ".$ordersql;            
         $params_1 = array();
       	 $params_1[] = (string)$this->cur_user->getUserName();
         array_push($params_1,(string)$cirle_id);
     }
     if($cirle_id=='9999')
     {
     		$sql_2 = "select l.*,f_checkAttentionWithAccount('".$this->cur_user->getUsername()."',l.login_account) attention from( select c.ename, a.nick_name,a.photo_path_big as photo_path,a.login_account,date_format(a.active_date,'%Y-%c-%d') register_date,a.duty from we_staff a left join we_enterprise c on c.eno=a.eno  where not exists(select 1 from we_micro_account d where d.number=a.login_account) and (exists(select 1 from we_staff_atten b where b.login_account=? and b.atten_id=a.login_account) and exists(select 1 from we_staff_atten c  where c.login_account=a.login_account and c.atten_id=?) ))l ".(empty($searchby)?"":((strlen($searchby)>mb_strlen($searchby,'utf8'))?" where l.nick_name like ? ":" where l.nick_name like ? or l.login_account like ? ")).$ordersql;
     		
     		$params_1 = array();
     		array_push($params_1,$this->cur_user->getUsername());
     		array_push($params_1,$this->cur_user->getUsername());
     		if(!empty($searchby)){
            	array_push($params_1,"%".$searchby."%");
            	if(strlen($searchby)==mb_strlen($searchby,'utf8'))
            		array_push($params_1,"%".$searchby."%");
            }
     }
     $da->PageSize=18;
     $da->PageIndex=$pno;     
     $ds=$da->GetData("we_staff",$sql_2,$params_1);
     $this->accounts=$ds["we_staff"]["rows"];
     $recordcount=$ds["we_staff"]["recordcount"];
     $this->recordcount = $recordcount;
     $this->pagecount = $recordcount>($da->PageSize)? ceil($recordcount/($da->PageSize)) : 0;
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
    
	public function getCircleByUser($user_name) 
    {
      $da = $this->get('we_data_access');
      $sql = "select a.circle_id, a.circle_name,a.network_domain from we_circle a,we_circle_staff b where a.circle_id=b.circle_id and b.login_account=?";
      $params = array();
      $params[] = (string)$user_name;      
      $ds = $da->GetData("we_circle", $sql, $params);      
      return  $ds["we_circle"]["rows"];
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
	       $sql = "select eshortname,dept_name,duty,login_account,nick_name,headerImage,work_phone,mobile,level 
	               from (select eshortname,dept_id,duty,a.login_account,nick_name,case when photo_path_big is null or photo_path_big='' then null else concat('$FILE_WEBSERVER_URL',photo_path_big) end  headerImage,work_phone,mobile,(select code from we_role inner join we_staff_role on we_role.id=we_staff_role.roleid where staff=a.login_account limit 1) level 
	               from we_staff a inner join we_staff_atten b on a.login_account=b.atten_id inner join we_enterprise c on a.eno=c.eno where not exists (select 1 from we_micro_account m where b.atten_id = m.number) and b.login_account=?) basic left join we_department dept on basic.dept_id=dept.dept_id 
	               union select eshortname,dept_name,duty,login_account,nick_name,case when photo_path_big is null or photo_path_big='' then null else concat('$FILE_WEBSERVER_URL',photo_path_big) end headerImage,work_phone,mobile,(select code from we_role inner join we_staff_role on we_role.id=we_staff_role.roleid where staff=c.login_account limit 1) level from we_staff c left join we_department b on b.dept_id=c.dept_id inner join we_enterprise a on c.eno=a.eno where not exists (select 1 from we_micro_account m where c.login_account=m.number) and c.eno=? and c.login_account!=? ".
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
	        $sql = " select eshortname,dept_name,duty,login_account,nick_name,headerImage,work_phone,mobile,level 
	                 from (select eshortname,dept_id,duty,a.login_account,nick_name,case when photo_path_big is null or photo_path_big='' then null else concat('$FILE_WEBSERVER_URL',photo_path_big) end  headerImage,work_phone,mobile,(select code from we_role inner join we_staff_role on we_role.id=we_staff_role.roleid where staff=a.login_account limit 1) level
	                       from we_staff a inner join we_staff_atten b on a.login_account=b.atten_id inner join we_enterprise c on a.eno=c.eno where not exists (select 1 from we_micro_account m where b.atten_id = m.number) and chinesstoletter(left(nick_name,1))=? and b.login_account=?) basic left join we_department dept on basic.dept_id=dept.dept_id 
	                 union 
	                   select eshortname,dept_name,duty,login_account,nick_name,headerImage,work_phone,mobile,level
	                   from (select eshortname,dept_id,duty,login_account,nick_name,case when photo_path_big is null or photo_path_big='' then null else concat('$FILE_WEBSERVER_URL',photo_path_big) end headerImage,work_phone,mobile,(select code from we_role inner join we_staff_role on we_role.id=we_staff_role.roleid where staff=c.login_account limit 1) level from we_staff c inner join we_enterprise a on c.eno=a.eno 
	                         where not exists (select 1 from we_micro_account m where c.login_account=m.number) and chinesstoletter(left(nick_name,1))=? and c.eno=? and c.login_account!=?) bs left join  we_department depart on bs.dept_id=depart.dept_id ".
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
}