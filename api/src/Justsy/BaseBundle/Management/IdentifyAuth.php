<?php
namespace Justsy\BaseBundle\Management;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Management\Identify;

class IdentifyAuth
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
	public function checkAuth($obj,$type)
	{
		try{
			$sql='';
			if($type=='eno')//企业
			{
				$sql="select 1 from we_enterprise where eno=? and eno_level=?";
				$params=array($obj,Identify::$MIdent);
			}
			else if($type=='user')
			{
				$sql="select 1 from we_staff where login_account=? and auth_level=?";
				$params=array($obj,Identify::$SIdent);
			}
			$ds=$this->da->Getdata('check',$sql,$params);
			if($ds['check']['recordcount']>0)
				return true;
			else
				return false;
		}
		catch(\Exception $e)
		{
			$this->writelog($e);
			throw new Exception("系统错误");
			//return false;
		}
	}
	public function getVerifyButton($apply_id)
	{
		$home=$this->containter->getParameter('open_api_url');
		$url=$home."/identify/user/agree"."?apply_id=".$apply_id;
		$buttons=array(array("text"=>"拒绝","code"=>"re","value"=>"0","link"=> $url),array("text"=>"同意","code"=>"re","value"=>"1","link"=> $url));
		return $buttons;
	}
	public function getMinAreeNum($eno)
	{
		try{
			$sql="select count(*) as num from we_staff a where a.eno=? and (a.auth_level<>? and a.auth_level<>?) and not exists(select 1 from we_micro_account b where b.number=a.login_account)";
	  	$params=array($eno,Identify::$SIdent,Identify::$MIdent);
	  	$ds=$this->da->Getdata('num',$sql,$params);
	  	$num=$ds['num']['rows'][0]['num'];
	  	if($num >=5)
	  		return 3;
	  	else
	  		return 1;
	  }
	  catch(\Exception $e)
	  {
	  	$this->writelog($e);
	  	return 1;
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