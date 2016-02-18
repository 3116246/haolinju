<?php
namespace Justsy\BaseBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Common\PHPExcel\PHPExcel;
use Justsy\BaseBundle\Common\PHPExcel\PHPExcel\IOFactory;
use Justsy\BaseBundle\Common\PHPExcel\PHPExcel\Reader\Excel5;
use Justsy\BaseBundle\Addrlist\OperSave;

class AddrListController extends Controller
{
	public function indexAction($network_domain)
	{
		//通讯录分类用type表示，M001代表名片薄、M002代表组织机构、M003代表合作伙伴、M004代表客户(默认分类)
		$username=$this->get('security.context')->getToken()->getUser()->getUserName();
		$da=$this->container->get('we_data_access');
		
		//删除数据表中的无效数据
		$sql="update we_addrlist_main set status='delete',mid_time=? where (addr_account<>'' and addr_account<>null) and not exists(select 1 from we_staff where login_account=we_addrlist_main.addr_account)";
		$da->ExecSQL($sql,array($this->microtime_float()));
		
		$sql="select * from(select a.typeid,a.typename from we_addrlist_type a where a.owner='all' order by a.typeid)t union all select typeid,typename from we_addrlist_type where owner=? and status<>'delete'";
		$param=array((string)$username);
		$ds=$da->Getdata('type',$sql,$param); 
		$types=$ds['type']['rows'];
		$alltype=array();
		foreach($types as $item)
		{
			if($item['typeid']=='M002')
			{
				$sql="select count(*)as num from we_staff where eno=(select a.eno from we_staff a where a.login_account=?) and login_account!=?";
				$param1=array();
				array_push($param1,$username);
				array_push($param1,$username);
				$ds=$da->Getdata('M002_count',$sql,$param1);
				$count=$ds['M002_count']['rows'][0]['num'];
				$item['count']=$count;
			}
			else
			{
				$sql="select count(*)as num from we_addrlist_main a where a.owner=? and a.typeid=? and a.status<>'delete'";
				$param2=array();
				array_push($param2,$username);
				array_push($param2,$item['typeid']);
				$ds=$da->Getdata('typeid_count',$sql,$param2);
				$count=$ds['typeid_count']['rows'][0]['num'];
				$item['count']=$count;
			}
			$alltype[]=$item;
		}
		return $this->render('JustsyBaseBundle:AddrList:index.html.twig',array('curr_network_domain'=>$network_domain,'my_addrlist_type'=>$alltype));
	}
	public function viewAction()
	{
		//通讯录分类用type表示，M001代表名片薄、M002代表组织机构、M003代表合作伙伴、M004代表客户(默认分类)
		$username=$this->get('security.context')->getToken()->getUser()->getUserName();
		$request = $this->getRequest();
		$type=$request->get('type');
		$FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
		if($type=='M002')
		{
			$sql="select case when exists(select 1 from we_addrlist_main where addr_account=c.login_account and typeid='M001' and owner=? and status<>'delete') then 'yes' else 'no' end as isInCard, c.fafa_jid as jid, '' as id,'M002' as typeid, 
			b.dept_name as addr_unit, c.login_account as addr_account,c.nick_name as addr_name,
			concat('$FILE_WEBSERVER_URL',ifnull(c.photo_path,''))photo_url,c.work_phone as addr_phone
			,c.mobile as addr_mobile,c.login_account as addr_mail 
			from we_staff c left join we_department b on b.dept_id=c.dept_id where c.eno=(select a.eno from we_staff a where a.login_account=?) and c.login_account!=?";
			$params=array();
			array_push($params,$username);
			array_push($params,$username);
			array_push($params,$username);
		}
		else if(!empty($type))
		{
			$sql="select case when exists(select 1 from we_addrlist_main where addr_account=a.addr_account and typeid='M001' and owner=? and status<>'delete') then 'yes' else 'no' end as isInCard, b.fafa_jid as jid, a.id,a.typeid, c.dept_name as addr_unit, b.login_account as addr_account,b.nick_name as addr_name,concat('$FILE_WEBSERVER_URL',ifnull(b.photo_path,''))photo_url,b.work_phone as addr_phone,b.mobile 
			as addr_mobile,b.login_account as addr_mail from we_addrlist_main a left join we_staff b on a.addr_account=b.login_account left join we_department c  on c.dept_id=b.dept_id where a.owner=? and a.id='' and a.typeid=? and a.status<>'delete' 
			union all select case when exists(select 1 from we_addrlist_addition where addr_mobile=d.addr_mobile and typeid='M001' and owner=? and status<>'delete') then 'yes' else 'no' end as isInCard, '' as jid, d.id,c.typeid, d.addr_unit,'',d.addr_name,'',d.addr_phone,d.addr_mobile,d.addr_mail from we_addrlist_main c,we_addrlist_addition d where c.owner=? and c.id=d.id and c.addr_account='' and c.typeid=? and c.status<>'delete'";
			$params=array();
			array_push($params,$username);
			array_push($params,$username);
			array_push($params,$type);
			array_push($params,$username);
			array_push($params,$username);
			array_push($params,$type);
		}
		$da=$this->container->get('we_data_access');
		$da->PageSize=10;
		$da->PageIndex=(int)($request->get('pageindex'))-1;
		$ds=$da->Getdata('my_addrlist',$sql,$params);
		$my_addrlist=$ds['my_addrlist']['rows'];
		$pagecount=ceil($ds["my_addrlist"]["recordcount"]/($da->PageSize));
		return $this->render('JustsyBaseBundle:AddrList:view.html.twig',array('pagecount'=>$pagecount,'pageindex'=>(($da->PageIndex)+1),'my_addrlist'=>$my_addrlist));
	}
	public function vilidateUser($addr_mail,$addr_mobile){
		$username=$this->get('security.context')->getToken()->getUser()->getUserName();
		$da=$this->container->get('we_data_access');
		$userinfo=null;
		
		$sql="select * from we_staff where login_account=? or mobile_bind=?";
		$params=array($username,$addr_mobile);
		$ds=$da->Getdata('userinfo',$sql,$params);
		if($ds['userinfo']['recordcount']==1){
			$userinfo=$ds['userinfo']['rows'][0];
		}
		return $userinfo;
	}
	public function editAction()
	{		
		$request = $this->getRequest();
		$login_account=$request->get('login_account');
		$username=empty($login_account) ? $this->get('security.context')->getToken()->getUser()->getUserName() : $login_account;
		$editType=strtolower($request->get('editType'));
		$da=$this->container->get('we_data_access');
		$s='1';
		$message="";
		$typeid="";
		$addr_account="";
		$id="";
		
		//新增
		if($editType=='add')
		{
			$typeid=$request->get('typeid');
			$addr_account=$request->get('addr_account');
			if(!empty($addr_account))
			{
				$re=OperSave::addInternal($da,$username,$typeid,$addr_account);
				$s=$re["s"];
				$message=$re["m"];
			}
			else
			{
				$mobile = $request->get('addr_mobile');
				$mail = $request->get('addr_mail');
				$addr_name=$request->get('addr_name');
				$addr_unit=$request->get('addr_unit');
				$addr_phone=$request->get('addr_phone');
				$re=OperSave::addExternal($da,$username,$typeid,$addr_name,$addr_unit,$addr_phone,$mobile,$mail);
				$s=$re["s"];
				$message=$re["m"];
			}
		}
		//彻底删除
		else if($editType=='delete')
		{
			$id=$request->get('id');
			$addr_account=$request->get('addr_account');
			
			$sql="select typeid from we_addrlist_main where id=? and addr_account=? and status<>'delete'";
			$param=array($id,$addr_account);
			$ds=$da->Getdata('typeid',$sql,$param);
			$typeid=array();
			foreach($ds['typeid']['rows'] as $item){
				array_push($typeid,$item['typeid']);
			}
			//
			if(!empty($id)){
				$sqls=array();
				$params=array();
				$sqls[]="delete from we_addrlist_main where id=?";
				$params[]=array($id);
				
				$sqls[]="update we_addrlist_addition set status='delete',mid_time=? where id=?";
				$params[]=array($this->microtime_float(),$id);
				
				if(!$da->ExecSQLs($sqls,$params)){
					$s='0';
					$message='操作失败';
				}
			}
			else if(!empty($addr_account)){
				$sql="update we_addrlist_main set status='delete',mid_time=? where addr_account=? and owner=?";
				$param=array($this->microtime_float(),$addr_account,$username);
				if(!$da->ExecSQL($sql,$param)){
					$s='0';
					$message='操作失败';
				}
			}
		}
		//编辑
		else if($editType=='edit')
		{
			  $phone=$request->get('addr_phone');
			  $mobile = $request->get('addr_mobile');
				$mail = $request->get('addr_mail');
				if(!$this->validate('phone',$phone)){
					$s="0";
					$message="不是有效的电话号码";
				}
				else if(!$this->validate('mobile',$mobile))
				{
					$s="0";
					$message="不是有效的手机号码";
				}
				else if(!$this->validate('mail',$mail))
				{
					$s="0";
					$message="邮箱地址格式不正确";
				}
				else
				{
					//判断是否有相同记录存在于数据库中
					
					
					$id=$request->get('id');
					$sql="update we_addrlist_addition set addr_name=?,addr_unit=?,addr_phone=?,addr_mobile=?,addr_mail=?,mid_time=?,status='update' where id=?";
					$params=array();
					array_push($params,$request->get('addr_name'));
					array_push($params,$request->get('addr_unit'));
					array_push($params,$request->get('addr_phone'));
					array_push($params,$mobile);
					array_push($params,$mail);
					array_push($params,$this->microtime_float());
					array_push($params,$id);
					if(!$da->ExecSQL($sql,$params))
					{
						$s="0";
						$message="更改失败";
					}
				}
		}
		//移动或移除
		else if($editType=='move'){
			$id=$request->get('id');
			$addr_account=$request->get('addr_account');
			$typeid=$request->get('typeid');
			$to=$request->get('to');
			if(!empty($id)){
				//判断是否存在
				$sql="select 1 from we_addrlist_main where id=? and typeid=? and status<>'delete'";
				$param=array($id,$to);
				$ds=$da->Getdata('1',$sql,$param);
				$num=$ds['1']['recordcount'];
				if($num==0){
						$sqls=array();
						$params=array();
						if(!empty($to)){
							$sql="select 1 from we_addrlist_main where id=? and typeid=? and status='delete'";
							$param=array($id,$to);
							$ds=$da->Getdata('1',$sql,$param);
							if($ds['1']['recordcount']==0){
								$sqls[]="insert into we_addrlist_main (id,addr_account,typeid,mid_time,status,owner) values(?,'',?,?,'add',?)";
								$params[]=array($id,$to,$this->microtime_float(),$username);
							}
							else{
								$sqls[]="update we_addrlist_main set status='add',mid_time=? where id=? and typeid=?";
								$params[]=array($this->microtime_float(),$id,$to);
							}
							
							$sqls[]="update we_addrlist_main set status='delete',mid_time=? where id=? and typeid=?";
							$params[]=array($this->microtime_float(),$id,$typeid);
						}
						else{
							$sql="select 1 as num from we_addrlist_main where id=? and status<>'delete'";
							$param=array($id);
							$ds=$da->Getdata('num',$sql,$param);
							if($ds['num']['recordcount']==1){
								$sqls[]="update we_addrlist_addition set status='delete',mid_time=? where id=?";
								$params[]=array($this->microtime_float(),$id);
								
								$sqls[]="delete from we_addrlist_main where id=?";
								$params[]=array($id);
							}
							else{
								$sqls[]="update we_addrlist_main set status='delete',mid_time=? where id=? and typeid=?";
								$params[]=array($this->microtime_float(),$id,$typeid);
							}
						}
						if(!$da->ExecSQLs($sqls,$params)){
							$s='0';
							$message='操作失败';
						}
				}
				else{
					$s='0';
					$message="在目标分组中已存在";
				}
			}
			else if(!empty($addr_account)){
				$sql="select 1 from we_addrlist_main where addr_account=? and typeid=? and status<>'delete' and owner=?";
				$param=array($addr_account,$to,$username);
				$ds=$da->Getdata('1',$sql,$param);
				$num=$ds['1']['recordcount'];
				if($num==0){
					$sqls=array();
					$params=array();
					$sqls[]="update we_addrlist_main set status='delete',mid_time=? where addr_account=? and typeid=? and owner=?";
					$params[]=array($this->microtime_float(),$addr_account,$typeid,$username);
					if(!empty($to)){
						$sql="select 1 from we_addrlist_main where addr_account=? and typeid=? and status='delete' and owner=?";
						$param=array($addr_account,$to,$username);
						$ds=$da->Getdata('1',$sql,$param);
						if($ds['1']['recordcount']==0){
							$sqls[]="insert into we_addrlist_main (id,addr_account,typeid,owner,status,mid_time) values('',?,?,?,'add',?)";
							$params[]=array($addr_account,$to,$username,$this->microtime_float());
						}
						else{
							$sqls[]="update we_addrlist_main set status='add',mid_time=? where addr_account=? and typeid=? and owner=?";
							$params[]=array($this->microtime_float(),$addr_account,$to,$username);
						}
					}
					if(!$da->ExecSQLs($sqls,$params)){
						$s='0';
						$message='操作失败';
					}
				}
				else{
					$s='0';
					$message="在目标分组中已存在";
				}	
			}
		}
		//添加到
		else if($editType=='copy'){
			 $id=$request->get("id");
			 $addr_account=$request->get("addr_account");
			 $to=$request->get("to");
			 $typeid=$request->get("typeid");
			 $typeid=$to;
			 if(!empty($id)){
			 	 //判断是否已存在
			 	 $sqls=array();
			 	 $params=array();
			 	 $sql="select status from we_addrlist_main where id=? and typeid=?";
			 	 $param=array($id,$to);
			 	 $ds=$da->Getdata('status',$sql,$param);
			 	 if($ds['status']['recordcount']==0){
			 	 	  $sqls[]="insert into we_addrlist_main (id,addr_account,typeid,owner,status,mid_time) values(?,'',?,?,'add',?)";
			 	 	  $params[]=array($id,$to,$username,$this->microtime_float());
			 	 }
			 	 else if($ds['status']['rows'][0]['status']=='delete'){
			 	 		$sqls[]="update we_addrlist_main set status='add',mid_time=? where id=? and typeid=?";
			 	 		$params[]=array($this->microtime_float(),$id,$to);
			 	 }
			 	 if(!$da->ExecSQLs($sqls,$params)){
			 	 	 $s='0';
			 	 	 $message='操作失败';
			 	 }
			 }
			 else if(!empty($addr_account)){
			 	 $sqls=array();
			 	 $params=array();
			 	 $sql="select status from we_addrlist_main where addr_account=? and typeid=? and owner=?";
			 	 $param=array($addr_account,$to,$username);
			 	 $ds=$da->Getdata('status',$sql,$param);
			 	 if($ds['status']['recordcount']==0){
			 	 	 $sqls[]="insert into we_addrlist_main (id,addr_account,typeid,owner,status,mid_time) values('',?,?,?,'add',?)";
			 	 	 $params[]=array($addr_account,$to,$username,$this->microtime_float());
			 	 }
			 	 else if($ds['status']['rows'][0]['status']=='delete'){
			 	 	 $sqls[]="update we_addrlist_main set status='add',mid_time=? where addr_account=? and owner=? and typeid=?";
			 	 	 $params[]=array($this->microtime_float(),$addr_account,$username,$to);
			 	 }
			 	 if(!$da->ExecSQLs($sqls,$params)){
			 	 	 $s='0';
			 	 	 $message='操作失败';
			 	 }
			 }
		}
		$re=array('s'=>$s,'message'=>$message,'typeid'=>$typeid,'curr_account'=>$addr_account,'id'=>$id);
		$response = new Response(json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
	}
	
	
	//新加功能
	public function updateAction()
	{		
		$request = $this->getRequest();
		$login_account=$request->get('login_account');
		$username=empty($login_account) ? $this->get('security.context')->getToken()->getUser()->getUserName() : $login_account;
		$editType=strtolower($request->get('editType'));
		$da=$this->container->get('we_data_access');
		$s='1';
		$message="";
		$typeid="";
		$addr_account="";
		$id="";		
		//新增
		if($editType=='add')
		{
			$typeid=$request->get('typeid');
			$addr_account=$request->get('addr_account');
			if(!empty($addr_account))
			{
				$re=OperSave::addInternal($da,$username,$typeid,$addr_account);
				$s=$re["s"];
				$message=$re["m"];
			}
			else
			{
				$mobile = $request->get('addr_mobile');				
				$mobile = empty($mobile) ? null :$mobile;
				$mail = $request->get('addr_mail');
				$mail = empty($mail)?null:$mail;
				$addr_name=$request->get('addr_name');
				$addr_name= empty($addr_name) ? null : $addr_name;
				$addr_unit=$request->get('addr_unit');
				$addr_unit = empty($addr_unit) ? null : $addr_unit;
				$addr_phone=$request->get('addr_phone');
				$addr_phone = empty($addr_phone) ? null : $addr_phone;
				$depart=$request->get('addr_depart');
				$depart = empty($depart) ? null : $depart;
				$job=$request->get('addr_job');
				$job = empty($job) ? null : $job;				
				
				$birthday = ($request->get('birthday_month'))."-".($request->get('birthday_day'));
				$birthday = $birthday=="-" ? null : $birthday;
				
				$re=OperSave::addExternalPerson($da,$username,$typeid,$addr_name,$addr_unit,$addr_phone,$mobile,$mail,$depart,$job,$birthday);
				
				$s=$re["s"];
				$message=$re["m"];
				$id=$re["id"];				
			}
		}
		//彻底删除
		else if($editType=='delete')
		{
			$id=$request->get('id');
			$addr_account=$request->get('addr_account');
			
			$sql="select typeid from we_addrlist_main where id=? and addr_account=? and status<>'delete'";
			$param=array($id,$addr_account);
			$ds=$da->Getdata('typeid',$sql,$param);
			$typeid=array();
			foreach($ds['typeid']['rows'] as $item){
				array_push($typeid,$item['typeid']);
			}
			//
			if(!empty($id)){
				$sqls=array();
				$params=array();
				$sqls[]="delete from we_addrlist_main where id=?";
				$params[]=array($id);
				
				$sqls[]="update we_addrlist_addition set status='delete',mid_time=? where id=?";
				$params[]=array($this->microtime_float(),$id);
				
				if(!$da->ExecSQLs($sqls,$params)){
					$s='0';
					$message='操作失败';
				}
			}
			else if(!empty($addr_account)){
				$sql="update we_addrlist_main set status='delete',mid_time=? where addr_account=? and owner=?";
				$param=array($this->microtime_float(),$addr_account,$username);
				if(!$da->ExecSQL($sql,$param)){
					$s='0';
					$message='操作失败';
				}
			}
		}
		//编辑
		else if($editType=='edit')
		{
			  $phone=$request->get('addr_phone');
			  $mobile = $request->get('addr_mobile');
				$mail = $request->get('addr_mail');
				if(!$this->validate('phone',$phone)){
					$s="0";
					$message="不是有效的电话号码";
				}
				else if(!$this->validate('mobile',$mobile))
				{
					$s="0";
					$message="不是有效的手机号码";
				}
				else if(!$this->validate('mail',$mail))
				{
					$s="0";
					$message="邮箱地址格式不正确";
				}
				else
				{
					//判断是否有相同记录存在于数据库中
					
					
					$id=$request->get('id');
					$sql="update we_addrlist_addition set addr_name=?,addr_unit=?,addr_phone=?,addr_mobile=?,addr_mail=?,mid_time=?,status='update' where id=?";
					$params=array();
					array_push($params,$request->get('addr_name'));
					array_push($params,$request->get('addr_unit'));
					array_push($params,$request->get('addr_phone'));
					array_push($params,$mobile);
					array_push($params,$mail);
					array_push($params,$this->microtime_float());
					array_push($params,$id);
					if(!$da->ExecSQL($sql,$params))
					{
						$s="0";
						$message="更改失败";
					}
				}
		}
		$re=array('s'=>$s,'message'=>$message,'typeid'=>$typeid,'curr_account'=>$addr_account,'id'=>$id);
		$response = new Response(json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
	}
	
	public function searchinfoAction()
	{
		$user=$this->get('security.context')->getToken()->getUser();
		$username=$this->get('security.context')->getToken()->getUser()->getUserName();
		$FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
		$request = $this->getRequest();
		$type=$request->get('type');
		$text=$request->get('text');
		$text=strtolower($text);
		$da=$this->container->get('we_data_access');
		$da->PageSize=10;
		$da->PageIndex=((int)($request->get('pageindex'))>0?(int)($request->get('pageindex')):1)-1;
		$sql="";
		$params=array();
		if(strtolower($type)=='all')
		{
			$sql="select case when exists(select 1 from we_addrlist_main where addr_account=a.login_account and typeid='M001' and owner=? and status<>'delete') 
			then 'yes' else 'no' end as isInCard, a.fafa_jid as jid, ifnull(c.id,'') as id,ifnull(c.typeid,'M002') as typeid ,d.dept_name as addr_unit,a.login_account as addr_account,
  a.nick_name as addr_name,concat('$FILE_WEBSERVER_URL',ifnull(a.photo_path,'')) photo_url,
  a.work_phone as addr_phone, a.mobile as addr_mobile,a.login_account as addr_mail 
from we_staff a 
left join we_department d on d.dept_id=a.dept_id 
left join we_addrlist_main c on c.addr_account=a.login_account and c.owner=? and c.status<>'delete' 
where (a.nick_name like ?".(strlen($text)>mb_strlen($text,'utf8')?"":" or  a.mobile =? or a.login_account like ? ").") 
  and (a.eno=? 
    or exists(select 1 from we_addrlist_main where addr_account=a.login_account and owner=? and status<>'delete')) 
union all 
select case when exists(select 1 from we_addrlist_main where id=e.id and typeid='M001' and owner=? and status<>'delete') then 'yes' else 'no' end as isInCard, '' as jid,f.typeid, e.id, e.addr_unit,'' as addr_account,
  e.addr_name,'' as photo_url,
  e.addr_phone,e.addr_mobile,e.addr_mail 
from we_addrlist_addition e,we_addrlist_main f 
where (e.addr_name like ? ".(strlen($text)>mb_strlen($text,'utf8')?"":"or e.addr_mobile=? ")." 
  or e.addr_unit like ?) 
  and e.owner=? and e.status<>'delete' and f.id=e.id";
      array_push($params,$username);
      array_push($params,$username);
      array_push($params,$text.'%');
      if(strlen($text)==mb_strlen($text,'utf8')){
      	array_push($params,$text);
      	array_push($params,$text.'%');
      }
			array_push($params,$user->eno);
			array_push($params,$username);
			array_push($params,$username);
			array_push($params,$text.'%');
			if(strlen($text)==mb_strlen($text,'utf8'))
				array_push($params,$text);
			array_push($params,$text.'%');
			array_push($params,$username);
		}
		else
		{
			if($text=='all%')
			{
				return $this->redirect($this->generateUrl('JustsyBaseBundle_addrlist_view', array('isedit'=>$isedit,'type'=>$type,'pageindex'=>1)));
			}
			else if($type=='M002')
			{
				$sql="select case when exists(select 1 from we_addrlist_main where addr_account=c.login_account and typeid='M001' and owner=? and status<>'delete') then 'yes' else 'no' end as isInCard, c.fafa_jid as jid, '' as id,'M002' as typeid,b.dept_name as addr_unit,c.login_account as addr_account,c.nick_name as addr_name,
				concat('$FILE_WEBSERVER_URL',ifnull(c.photo_path,''))photo_url,c.work_phone as addr_phone,
			c.mobile as addr_mobile,c.login_account as addr_mail from we_staff c left join we_department b on 
			b.dept_id=c.dept_id 
			where c.login_account like ? and
				 (c.eno=(select a.eno from we_staff a where a.login_account=?))";
				array_push($params,$username);
				array_push($params,$text.'%');
				array_push($params,$username);
			}
			else
			{
				$sql="select case when exists(select 1 from we_addrlist_main where addr_account=a.addr_account and typeid='M001' and owner=? and status<>'delete') then 'yes' else 'no' end as isInCard, b.fafa_jid as jid, a.id,a.typeid, c.dept_name as addr_unit, b.login_account as addr_account,b.nick_name as addr_name,
				concat('$FILE_WEBSERVER_URL',ifnull(b.photo_path,''))photo_url,b.work_phone as addr_phone,b.mobile
			as addr_mobile,b.login_account as addr_mail from we_addrlist_main a,we_staff b left join we_department c on 
			c.dept_id=b.dept_id where a.typeid=? and a.owner=? and 
			a.addr_account like ? and a.addr_account=b.login_account and a.status<>'delete'";
			  array_push($params,$username);
			  array_push($params,$type);
				array_push($params,$username);
				array_push($params,$text.'%');
			}
		}
		$ds=$da->Getdata('my_addrlist',$sql,$params);
		$my_addrlist=$ds['my_addrlist']['rows'];
		$pagecount=ceil($ds["my_addrlist"]["recordcount"]/($da->PageSize));
		return $this->render('JustsyBaseBundle:AddrList:view.html.twig',
		array('pagecount'=>$pagecount,'pageindex'=>(($da->PageIndex)+1),'my_addrlist'=>$my_addrlist));
	}
	public function inviteAction()
	{
		$request = $this->getRequest();
		$id=$request->get("id");
		$ec=new \Justsy\BaseBundle\Controller\InviteController;
		$ec->setContainer($this->container);
	  $return=$ec->sendInvitationAction()->getContent();
	  $response=new Response(json_encode(array('id'=>$id,'s'=>$return)));
	  $response->headers->set('Content-Type', 'text/json');
    return $response;
	}
	public function addTypeAction()
	{
		$username=$this->get('security.context')->getToken()->getUser()->getUserName();
		$request = $this->getRequest();
		$typename=$request->get('typename');
		if(empty($typename))
		{
			$response=new Response(json_encode(array('message'=>'名称不能为空','s'=>0)));
	    $response->headers->set('Content-Type', 'text/json');
      return $response;
		}
		$da=$this->container->get('we_data_access');
		$sql="select typename from we_addrlist_type where typename=? and owner=? and status<>'delete'";
		$params=array();
		array_push($params,$typename);
		array_push($params,$username);
		$ds=$da->Getdata('typename',$sql,$params);
		if($ds['typename']['recordcount']==0)
		{
			$sql="select typeid from we_addrlist_type where typeid like 'A%' order by typeid desc limit 0,1";
			$ds=$da->Getdata('typeid',$sql);
			$typeid="";
			$typeid=$this->getTypeId();
			$sql="insert into we_addrlist_type (typeid,typename,owner,mid_time,status) values(?,?,?,?,'add')";
			$params=array();
			array_push($params,$typeid);
			array_push($params,$typename);
			array_push($params,$username);
			array_push($params,$this->microtime_float());
			$da->ExecSQL($sql,$params);
			$response=new Response(json_encode(array('typeid'=>$typeid,'s'=>1,'typename'=>$typename)));
	    $response->headers->set('Content-Type', 'text/json');
      return $response;
		}
		else
		{
			$response=new Response(json_encode(array('message'=>'该名称已存在','s'=>0)));
	    $response->headers->set('Content-Type', 'text/json');
      return $response;
		}
	}
	
	public function introAction()
	{
		$username=$this->get('security.context')->getToken()->getUser()->getUserName();
		$request = $this->getRequest();
		$file_name=$_FILES['excel']['name'];
		$file_type=$_FILES['excel']['type'];
		$file_size=$_FILES['excel']['size'];
		$file_tmpname=$_FILES['excel']['tmp_name'];
		$time=date('y-m-d-H-m-s');
		if(!file_exists('upload/'.$time.'_'.$file_name))
		{
			if(move_uploaded_file($file_tmpname,'upload/'.$time.'_'.$file_name))
			{
				$da=$this->container->get('we_data_access');
				$objReader = IOFactory::createReader('Excel5');//use excel2007 for 2007 format
        $objPHPExcel = $objReader->load('upload/'.$time.'_'.$file_name); 
				$objWorksheet = $objPHPExcel->getActiveSheet();
        $highestRow = $objWorksheet->getHighestRow(); 
        $highestColumn = $objWorksheet->getHighestColumn();
        $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);//总列数
				for ($row = 1;$row <= $highestRow;$row++) 
        {
            $strs=array();
            for ($col = 0;$col < $highestColumnIndex;$col++)
            {
                $strs[$col] =$objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
            }
        }
			}
			else
			{
				$re=array('s'=>0,'message'=>"文件上传失败");
			}
		}
		else
		{
			$re=array('s'=>0,'message'=>"");
		}
		$response = new Response(json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
	}
	public function oneAddrAction()
	{
		
	}
	public function delTypeAction()
	{
		$username=$this->get('security.context')->getToken()->getUser()->getUserName();
		$request = $this->getRequest();
		$typeid=$request->get('typeid');
		$da=$this->container->get('we_data_access');
		$s=1;
		$message='';
		
		$sql="select 1 from we_addrlist_main a where (a.owner=? and a.typeid=? and status<>'delete')";
		$params=array($username,$typeid);
		$ds=$da->Getdata('deltype',$sql,$params);
		if($ds['deltype']['recordcount']==0){
			$sql="update we_addrlist_type set status='delete',mid_time=? where owner=? and typeid=?";
			$params=array($this->microtime_float(),$username,$typeid);
			if(!$da->ExecSQL($sql,$params)){
				$s=0;
				$message='删除失败!';
			}
		}
		else{
			$s=0;
			$message='只能删除空白分组!';
		}
		$response = new Response(json_encode(array('s'=>$s,'message'=>$message)));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
	}
	public function changeNameAction()
	{
		$username=$this->get('security.context')->getToken()->getUser()->getUserName();
		$request = $this->getRequest();
		$typeid=$request->get('typeid');
		$typename=$request->get('typename');
		$da=$this->container->get('we_data_access');
		$s=1;
		$message='';
		
		$sql="select 1 from we_addrlist_type where owner=? and typename=? and status<>'delete'";
		$params=array($username,$typename);
		$ds=$da->Getdata('typename',$sql,$params);
		if($ds['typename']['recordcount']==0){
			$sql="update we_addrlist_type set typename=?,status='update',mid_time=? where owner=? and typeid=?";
			$params=array($typename,$this->microtime_float(),$username,$typeid);
			if(!$da->ExecSQL($sql,$params)){
				$s=0;
				$message='更改失败!';
			}
		}
		else{
			$s=0;
			$message='该名称已存在!';
		}
		$response = new Response(json_encode(array('s'=>$s,'message'=>$message,'typeid'=>$typeid,'typename'=>$typename)));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
	}
	public function getTypeId()
	{
		$da=$this->container->get('we_data_access');
		$id=\Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_addrlist_type", "typeid");
		return "A".(string)$id;
	}
	public function validate($classify='',$content='')
	{
		if($classify=='mail')
		{
			return filter_var($content,FILTER_VALIDATE_EMAIL);
		}
		else if($classify=='phone')
		{
			return preg_match("/^((\(\d{2,3}\))|(\d{3}\-))?(\(0\d{2,3}\)|0\d{2,3}-)?[1-9]\d{6,7}(\-\d{1,4})?$/",$content)==1;
		}
		else if($classify=='mobile')
		{
			return preg_match("/^(13[0-9]|15[0|3|6|7|8|9]|18[0|2|3|5|6|7|8|9])\d{8}$/",$content)==1;
		}
	}
	function microtime_float()
	{
   list($usec, $sec) = explode(" ", microtime());
   return (round(((float)$usec + (float)$sec)*1000));
	}
}