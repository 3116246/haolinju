<?php
namespace Justsy\BaseBundle\Management;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\DataAccess\SysSeq;

class UserTag
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
	public function havetag($login_account,$tag_name)
	{
		//判断是否存在
		$sql="select 1 from we_tag where tag_name=? and owner_id=? and owner_type='01'";
		$params=array($tag_name,$login_account);
		$ds=$this->da->Getdata('tag',$sql,$params);
		if($ds['tag']['recordcount']==0)
			return false;
		else
		  return true;
	}
	public function checknum($login_account)
	{
		$sql="select 1 from we_tag where owner_id=? and owner_type='01'";
		$params=array($login_account);
		$ds=$this->da->Getdata('num',$sql,$params);
		if($ds['num']['recordcount']>=5)
			return false;
		else
			return true;
	}
	public function addtag($login_account,$tag_name,$tag_desc)
	{
		try{
			$tag_id=SysSeq::GetSeqNextValue($this->da,"we_tag","tag_id");
			$sql="insert into we_tag (tag_id,tag_name,owner_id,owner_type,tag_desc,create_date) values(?,?,?,?,?,now())";
			$params=array($tag_id,$tag_name,$login_account,'01',$tag_desc);
			if(!$this->da->ExecSQL($sql,$params))
			{
				return null;
			}
			else{
				$friendevent=new \Justsy\BaseBundle\Management\FriendEvent($this->da,$this->logger,$this->container);
      	$friendevent->addtag($login_account,$tag_id,$tag_name,$tag_desc);
				return $tag_id;
			}
		}
		catch(\Exception $e)
		{
			//var_dump($e->getMessage());
			$this->writelog($e);
			return null;
		}
	}
	public function edittag($login_account,$tag_id,$tag_name,$tag_desc)
	{
		try{
			$sql="select 1 from we_tag where tag_name=? and tag_id<>? and owner_id=? and owner_type='01'";
			$params=array($tag_name,$tag_id,$login_account);
			$ds=$this->da->Getdata('tag',$sql,$params);
			if($ds['tag']['recordcount']==0)
			{
				$sql="update we_tag set tag_name=?,tag_desc=? where tag_id=?";
				$params=array($tag_name,$tag_desc,$tag_id);
				if(!$this->da->ExecSQL($sql,$params))
				{
					return false;
				}
				else
					return true;
			}
		}
		catch(\Exception $e)
		{
			$this->writelog($e);
			return false;
		}
	}
	public function deltag($tag_id)
	{
		try{
			$sql="delete from we_tag where tag_id=?";
			$params=array($tag_id);
			if(!$this->da->ExecSQL($sql,$params))
			{
				return false;
			}
			else
				return true;
		}
		catch(\Exception $e)
		{
			//var_dump($e->getMessage());
			$this->writelog($e);
			return false;
		}
	}
	public function gettag($login_account)
	{
		try{
			$sql="select tag_id,tag_name,tag_desc,owner_id as login_account,create_date from we_tag where owner_type='01' and owner_id=?";
			$params=array($login_account);
			$ds=$this->da->Getdata('tag',$sql,$params);
			return $ds['tag']['rows'];
		}
		catch(\Exception $e)
		{
			$this->writelog($e);
			return array();
		}
	}
	//获取企业标签
	public function getentag($eno)
	{
		try{
			$sql="select tag_id,tag_name,tag_desc,create_date from we_tag where owner_type='02' and owner_id=?";
			$params=array($eno);
			$ds=$this->da->Getdata('tag',$sql,$params);
			return $ds['tag']['rows'];
		}
		catch(\Exception $e)
		{
			$this->writelog($e);
			return array();
		}
	}
	//获取具有相同标签的人，前10个
	public function getSameTagList($tagname)
	{
			try{
				$sql="select we_staff.* from we_tag,we_staff where owner_id=login_account and state_id='1' and  owner_type='01' and tag_name=?";
				$params=array($tagname);
				$ds=$this->da->Getdata('tag',$sql,$params);
				return $ds['tag']['rows'];
			}
			catch(\Exception $e)
			{
				$this->writelog($e);
				return array();
			}		 
	}
	
	protected function writelog($e)
	{
		if(!empty($logger))
		{
			$this->logger->err($e);
		}
	}
}
?>