<?php
namespace Justsy\BaseBundle\Management;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\DataAccess\SysSeq;

class Active
{
	protected $logger;
	protected $da;
	protected $containter;
	public function __construct($dataaccess,$log='',$containter='')
	{
		$this->logger=$log;
		$this->da=$dataaccess;
		$this->containter=$containter;
	}
	public function getDomain($mail)
	{
		try{
			$arr1=explode('@',$mail);
			if(count($arr1)!=2)return false;
			$str1=$arr1[1];
//			$arr2=explode('.',$str1);
//			if(count($arr2)!=2)return false;
			return $str1;
		}
		catch(\Exception $e)
		{
			$this->writelog($e);
			return false;
		}
	}
	public function checkEname($ename)
	{
		try{
			$sql="select 1 from we_enterprise_stored where enoname=?";
			$params=array($ename);
			$ds=$this->da->Getdata('tt',$sql,$params);
			if($ds['tt']['recordcount']>0)return false;
			return true;
		}
		catch(\Exception $e)
		{
			$this->writelog($e);
			return false;
		}
	}
	public function getInfoByDomain($edomain)
	{
		try{
			$sql="select enoname,eshortname,auth from we_enterprise_stored where eno_mail like ?";
			$params=array('%@'.$edomain);
			$ds=$this->da->Getdata('info',$sql,$params);
			if($ds['info']['recordcount']==0)return array();
			return $ds['info']['rows'][0];
		}
		catch(\Exception $e)
		{
			$this->writelog($e);
			return array();
		}
	}
	public function searchByEname($ename)
	{
		try{
			if(empty($ename))return array();
			$sql="select enoname,eshortname from we_enterprise_stored where enoname like ?";
			$params=array('%'.$ename.'%');
			$ds=$this->da->Getdata('search',$sql,$params);
			if($ds['search']['recordcount']==0)return array();
			return $ds['search']['rows'];
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