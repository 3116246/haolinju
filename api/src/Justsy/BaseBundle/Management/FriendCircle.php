<?php
namespace Justsy\BaseBundle\Management;
use Justsy\BaseBundle\Common\Utils;

class FriendCircle
{
	protected $da;
	protected $dm;
	protected $logger;
	protected $container;
	public static $sql_friendtofriend="select distinct c.login_account from we_staff_atten c inner join 
(select a.login_account as atten,a.atten_id as attened from we_staff_atten a inner join 
(select b.atten_id as atten,b.login_account as attened from we_staff_atten b where b.atten_id=? and b.atten_type='01')t 
on t.atten=a.login_account and t.attened=a.atten_id where a.login_account=? and a.atten_type='01')t2
on c.atten_id=t2.attened and exists(select 1 from we_staff_atten d where d.login_account=t2.attened and d.atten_id=c.login_account)
where c.atten_type='01' and not exists(select 1 from we_staff_atten e where e.login_account=? and e.atten_id=c.login_account) and c.login_account<>?";

	public static $sql_employeenotfriend="select a.login_account from we_staff a where a.eno=? and not exists(select 1 from we_staff_atten c where c.atten_id=a.login_account and c.login_account=?)
	 and not exists(select 1 from we_micro_account b where b.number=a.login_account ) and a.login_account<>?";
	
	public static $sql_topstaff="select a.login_account from we_staff a where
			not exists(select 1 from we_staff_atten c where c.atten_id=a.login_account and c.login_account=?)
			 and not exists(select 1 from we_micro_account b where b.number=a.login_account) and a.login_account<>? 
order by a.total_point desc,a.attenstaff_num desc,a.fans_num desc limit 0,20";

	public function __construct($da,$logger=null,$container=null,$dm=null)
	{
		$this->da=$da;
		$this->dm=$dm;
		$this->logger=$logger;
		$this->container=$container;
	}
	public function getfriend($login_account)
	{
		try{
			$sql="select distinct attened as login_account from (select a.login_account as atten,a.atten_id as attened from we_staff_atten a inner join 
(select b.atten_id as atten,b.login_account as attened from we_staff_atten b where b.atten_id=? and b.atten_type='01')t 
on t.atten=a.login_account and t.attened=a.atten_id where a.login_account=? and a.atten_type='01')t1";
			$params=array($login_account,$login_account);
			$ds=$da->Getdata('friend',$sql,$params);
			return $ds['friend']['rows'];
		}
		catch(\Exception $e)
		{
			//var_dump($e->getMessage());
			$this->writelog($e);
			return array();
		}
	}
	public function getUserInfo($list)
	{
		try{
			$this->da->PageSize  =-1;
	  	$this->da->PageIndex =-1;
			$photo_url=$this->container->getParameter('FILE_WEBSERVER_URL');
			$sql="select a.login_account,a.self_desc,a.photo_path,a.photo_path_small,a.photo_path_big,a.nick_name,b.eno,b.ename,b.eshortname,a.duty,a.hobby,a.sex_id,
			a.mobile,c.classify_name,concat('$photo_url',ifnull(a.photo_path,'')) as logo,(select d.classify_name 
			from we_industry_class d where d.classify_id=c.parent_classify_id) as classify_parent_name,d.dept_id,d.dept_name from we_staff a left join we_enterprise b on b.eno=a.eno left join we_industry_class
			c on c.classify_id=b.industry left join we_department d on d.dept_id=a.dept_id where a.state_id<>'3' and a.login_account in (";
			$params=array();
			for($i=0;$i<count($list);$i++)
			{
				if($i==(count($list)-1))
				{
					$sql.='?';
				}
				else{
					$sql.='?,';
				}
				array_push($params,$list[$i]['login_account']);
			}
			$sql.=")";
			$ds=$this->da->Getdata('info',$sql,$params);
			return $ds['info']['rows'];
		}
		catch(\Exception $e)
		{
			//var_dump($e->getMessage());
			$this->writelog($e);
			return array();
		}
	}
	public function getfriendtofriend($login_account)
	{
		try{
			$sql=self::$sql_friendtofriend;
			$params=array($login_account,$login_account,$login_account,$login_account);
			$ds=$this->da->Getdata('contacts',$sql,$params);
			return $ds['contacts']['rows'];
		}
		catch(\Exception $e)
		{
			//var_dump($e->getMessage());
			$this->writelog($e);
			return array();
		}
	}
	public function getemployeenotfriend($eno,$login_account)
	{
		try{
			$sql=self::$sql_employeenotfriend;
			$params=array($eno,$login_account,$login_account);
			$ds=$this->da->Getdata('employee',$sql,$params);
			return $ds['employee']['rows'];
		}
		catch(\Exception $e)
		{
			//var_dump($e->getMessage());
			$this->writelog($e);
			return array();
		}
	}
	public function gettopstaff($login_account)
	{
		try{
			$sql=self::$sql_topstaff;
			$params=array($login_account,$login_account);
			$ds=$this->da->Getdata('staff',$sql,$params);
			return $ds['staff']['rows'];
		}
		catch(\Exception $e)
		{
			//var_dump($e->getMessage());
		  $this->writelog($e);
			return array();
		}
	}
	public function getRemAccount($eno,$login_account)
	{
		try{
//			$arr1=$this->getfriendtofriend($login_account);
//			$arr2=$this->getemployeenotfriend($eno,$login_account);
//			$arr3=$this->gettopstaff($login_account);
			$sql=self::$sql_friendtofriend." union ".self::$sql_employeenotfriend." union "."select h.login_account from (".self::$sql_topstaff.")h";
			$arr1=array($login_account,$login_account,$login_account,$login_account);
			$arr2=array($eno,$login_account,$login_account);
			$arr3=array($login_account,$login_account);
			$ds=$this->da->Getdata('info',$sql,array_merge($arr1,$arr2,$arr3));
			$accounts=$this->getUserInfo($ds['info']['rows']);
			return $accounts;
		}
		catch(\Exception $e)
		{
			//var_dump($e->getMessage());
			$this->writelog($e);
			return array();
		}
	}
	public function getRecomEno($login_account)
	{
		try{
			$photo_url=$this->container->getParameter('FILE_WEBSERVER_URL');
//			$sql="select b.eno,b.ename,b.eshortname,concat('$photo_url',ifnull(b.logo_path,'')) as logo,
//a.circle_recommend,b.create_staff,b.create_date,
//concat('V',b.vip_level) as vip,
//(select count(1) from we_staff_atten c where c.atten_id=b.eno and c.atten_type='04') as atten_num
//from we_circle a inner join we_enterprise b 
//on b.eno=a.enterprise_no 
//where a.enterprise_no is not null and a.enterprise_no<>''
//and not exists(select 1 from we_staff_atten d where d.login_account=? and atten_type='04' and atten_id=b.eno) order by a.circle_recommend desc";
      $sql="SELECT a.id,a.eno,a.enoname,a.eshortname,classify_name trade,concat(b.eno_level,b.vip_level) vip,concat('$photo_url',ifnull(a.eno_logo_path_big,'')) as logo FROM we_enterprise_stored a left join we_enterprise b on a.eno=b.eno  left join we_industry_class c on a.eno_trade = c.classify_id where not exists(select 1 from we_staff_atten d where d.login_account=? and atten_type='04' and atten_id=a.id) and not exists(select 1 from we_staff_atten d,we_micro_account wma where wma.number=d.atten_id and d.login_account=? and atten_type='05' and wma.eno=a.eno and micro_use='9') and a.rem_point is not null order by a.rem_point desc ";
			$params=array((string)$login_account,(string)$login_account);
  	  $ds=$this->da->Getdata('info5',$sql,$params);
  	  $rows=$ds['info5']['rows'];
  	  return $rows;
		}
		catch(\Exception $e)
		{
			//var_dump($sql);
			$this->writelog($e);
			return array();
		}
	}
	public function getRecommend($login_account)
	{
		try{
			$sql="select a.msg_id,a.sender,a.send_date,a.title,a.content,b.nick_name from we_message a  
			left join we_staff b on b.login_account=a.sender where a.recver=? and a.msg_type='02' and (a.isread is null or a.isread='')";
			$params=array($login_account);
			$ds=$this->da->Getdata('recommend',$sql,$params);
			//¸úÐÂÎªÒÑ¶Á
			$sql="update we_message set isread='1' where recver=? and msg_type='02' and (isread is null or isread='')";
			$params=array($login_account);
			$this->da->ExecSQL($sql,$params);
			return $ds['recommend']['rows'];
		}
		catch(\Exception $e)
		{
			$this->writelog($e);
			return array();
		}	
	}
	public function getSupperUser($login_account)
	{
		try{
			$photo_url=$this->container->getParameter('FILE_WEBSERVER_URL');
			$sql="select a.login_account,a.nick_name,concat('$photo_url',ifnull(a.photo_path,'')) as logo from we_staff a where 
			not exists(select 1 from we_micro_account b where b.number=a.login_account)
       and a.login_account<>? and a.login_account<>? order by a.fans_num desc";
			$params=array('sysadmin@fafatime.com','pm@fafatime.com');
			$ds=$this->da->Getdata('info',$sql,$params);
			return $ds['info']['rows'];
		}
		catch(\Exception $e)
		{
			//var_dump($e->getMessage());
			$this->writelog($e);
			return array();
		}
	}
	protected function writelog($e)
	{
		if(empty($this->logger))
		{
			$this->logger->err($e);
		}
	}
}
?>