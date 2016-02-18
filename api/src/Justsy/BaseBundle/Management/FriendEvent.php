<?php
namespace Justsy\BaseBundle\Management;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\DataAccess\SysSeq;

class FriendEvent
{
	protected $logger;
	protected $da;
	protected $container;
	public function __construct($dataaccess,$log='',$containter='')
	{
		$this->logger=$log;
		$this->da=$dataaccess;
		$this->container=$containter;
	}
	//头像更改
	public function photochange($login_account,$nick_name)
	{
		$content="<a login_account='".$login_account."' class='account_baseinfo'>".$nick_name."</a>更新了新头像！";
		$this->RegisterEvent($login_account,null,'photochange',$content);
	}
	//签名变更
	public function signchange($login_account,$nick_name,$sign)
	{
		$content="<a login_account='".$login_account."' class='account_baseinfo'>".$nick_name."</a>更换了更个性的签名：".$sign;
		$this->RegisterEvent($login_account,null,'signchange',$content);
	}
	//人脉关注
	public function attenuser($login_account,$nick_name,$atten_account)
	{
		$atten_name=$this->getName($atten_account);
		if(empty($atten_name))return;
		$sql="select 1 from we_staff_atten where login_account=? and atten_id=?";
		$params=array($atten_account,$login_account);
		$ds=$this->da->Getdata('atten',$sql,$params);
		if($ds['atten']['recordcount']>0){
			$content1="<a login_account='".$login_account."' class='account_baseinfo'>".$nick_name."</a>添加了<a login_account='".$atten_account."' class='account_baseinfo'>".$atten_name."</a>为好友！";
			$content2="<a login_account='".$atten_account."' class='account_baseinfo'>".$atten_name."</a>添加了<a login_account='".$login_account."' class='account_baseinfo'>".$nick_name."</a>为好友！";
			$this->RegisterEvent($login_account,null,'attenuser',$content1);
			$this->RegisterEvent($atten_account,null,'attenuser',$content2);
		}
		else{
			$content="<a login_account='".$login_account."' class='account_baseinfo'>".$nick_name."</a>关注了<a login_account='".$atten_account."' class='account_baseinfo'>".$atten_name."</a>";
			$this->RegisterEvent($login_account,null,'attenuser',$content);
		}
	}
	//关注企业
	public function atteneno($login_account,$eno)
	{
		$nick_name=$this->getName($login_account);
		$ename=$this->getEname($eno);
		if(empty($nick_name) || empty($ename))return;
		$content="<a login_account='".$login_account."' class='account_baseinfo'>".$nick_name."</a>关注了<a circle_id='".$eno."' class='enterprise_name'>".$ename."</a>";
		$this->RegisterEvent($login_account,null,'atteneno',$content);
	}
	//新增标签
	public function addtag($login_account,$tag_id,$tag_name,$tag_desc)
	{
		$nick_name=$this->getName($login_account);
		if(empty($nick_name))return;
		$content="<a login_account='".$login_account."' class='account_baseinfo'>".$nick_name."</a>添加了新的标签：<a tag_id='".$tag_id."' title='".$tag_desc."'>".$tag_name."</a>";
		$this->RegisterEvent($login_account,null,'addtag',$content);
	}
	//创建圈子
	public function createcircle($login_account,$nick_name,$circle_id,$circle_name)
	{
		$content="<a login_account='".$login_account."' class='account_baseinfo'>".$nick_name."</a>创建了圈子：<a class='circle_name' circle_id='".$circle_id."'>".$circle_name."</a>";
		$this->RegisterEvent($login_account,null,'createcircle',$content);
	}
	//等级提升
	public function pointlift($login_account)
	{
	}
	//身份变更
	public function authchange($login_account)
	{
		//$content="<a login_account='".$login_account."' class='account_baseinfo'>".$nick_name."</a>";
	}
	//成为热点人物
	public function gethot($login_account)
	{
		
	}
	public function getEvents($login_account)
	{
		try{
			$sql="select a.msg_id,a.content from we_message a where a.msg_type='03' and
			exists(select 1 from we_staff_atten b where b.login_account=? and b.atten_id=a.sender) and 
			exists(select 1 from we_staff_atten c where c.login_account=a.sender and c.atten_id=?) order by send_date desc limit 0,30";
			$params=array($login_account,$login_account);
			$ds=$this->da->Getdata('event',$sql,$params);
			return $ds['event']['rows'];
		}
		catch(\Exception $e)
		{
			//var_dump($e->getMessage());
			$this->writelog($e);
			return array();
		}
	}
	protected function RegisterEvent($sender,$recver,$title,$content)
	{
		try{
			$msg_id = SysSeq::GetSeqNextValue($this->da,"we_message","msg_id");
      $sql="insert into we_message (msg_id,sender,recver,send_date,title,content,msg_type) values(?,?,?,now(),?,?,'03')";
      $params=array($msg_id,$sender,$recver,$title,$content);
      return $this->da->ExecSQL($sql,$params);
		}
		catch(\Exception $e)
		{
			//var_dump($e->getMessage());
			$this->writelog($e);
			return false;
		}
	}
	protected function getName($login_account)
	{
		try{
			$sql="select nick_name from we_staff where login_account=?";
			$params=array($login_account);
			$ds=$this->da->Getdata('name',$sql,$params);
			if($ds['name']['recordcount']>0)
			{
				return $ds['name']['rows'][0]['nick_name'];
			}
			return null;
		}
		catch(\Exception $e)
		{
			$this->writelog($e);
			return null;
		}
	}
	protected function getEname($eno)
	{
		try{
			$sql="select ename from we_enterprise where eno=?";
			$params=array($eno);
			$ds=$this->da->Getdata('name',$sql,$params);
			if($ds['name']['recordcount']>0)
			{
				return $ds['name']['rows'][0]['ename'];
			}
			return null;
		}
		catch(\Exception $e)
		{
			$this->writelog($e);
			return null;
		}
	}
	protected function writelog($e)
	{
		if(!empty($logger))
		{
			//var_dump($e->getMessage());
			$this->logger->err($e);
		}
	}
}
?>