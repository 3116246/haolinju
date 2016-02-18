<?php
namespace Justsy\BaseBundle\Management;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Common\Cache_Enterprise;

//维护app版本、数据版本
class VersionChange
{
	protected $logger;
	protected $da;
	protected $containter;
	public function __construct($dataaccess,$log='',$containter='')
	{
		$this->logger=$log;
		$this->da = $dataaccess;
		$this->containter=$containter;
	}

	public function staffchange($staff)
	{
		$conn_im = $this->containter->get("we_data_access_im");
		if(empty($staff)) return;
		$deptid = $staff["dept_id"];
		$this->deptchange($deptid);
	}

	public function deptchange($deptid)
	{
		$conn_im = $this->containter->get("we_data_access_im");
		if(empty($deptid)) return;
		$dept = new Dept($this->da,$conn_im,$this->containter);
		$deptinfo = $dept->getinfo(is_string($deptid) ? $deptid: $deptid['dept_id']);
		$paths = explode('/', $deptinfo["path"]);
		if(count($paths)==0) return;
		foreach ($paths as $key => $value) {
			if(empty($value)) continue;
			$this->updatedeptversion($value);
		}
	}

	public function groupchange($groupid)
	{
		$conn_im = $this->containter->get("we_data_access_im");
		$cache = Cache_Enterprise::get("grp_ver_",$groupid,$this->containter);
		if(!empty($cache))
		{
			$cache = ((int)$cache)+1;
			$sql = "";
		}
		else
		{
			$sql = "select * from im_group_version where us=?";
			$v_ds=$conn_im->GetData("t",$sql,array((string)$groupid));
			if(count($v_ds["t"]["rows"])==0)
			{
				$cache=1;
				$conn_im->ExecSQL("insert into im_group_version(us,version)values(?,?)",array(
					(string)$groupid,
					(string)$cache
				));
			}
			else
			{
				$cache=((int)$v_ds["t"]["rows"][0]["version"])+1;
				$conn_im->ExecSQL("update im_group_version set version=? where us=?",array(
					(string)$cache,
					(string)$groupid
				));
			}
			Cache_Enterprise::set("grp_ver_",$groupid,(string)$cache,0,$this->containter);
		}		
	}

	public function deleteDeptVersion($deptid)
	{
		$conn_im = $this->containter->get("we_data_access_im");
		$conn_im->ExecSQL("delete from im_dept_version where us=?",array(
					(string)$deptid
		));
		Cache_Enterprise::delete("d_ver_",$deptid,$this->containter);
	}

	public function deleteGroupVersion($groupid)
	{
		$conn_im = $this->containter->get("we_data_access_im");
		$conn_im->ExecSQL("delete from im_group_version where us=?",array(
					(string)$groupid
		));
		Cache_Enterprise::delete("grp_ver_",$groupid,$this->containter);
	}
	
	//设置版本变化情况表
	public function SetVersionChange($type,$number,$eno)
	{
		$da = $this->da;
		$sql = "select 1 from we_version_change where `type`=? and number=? and eno=?;";
		$para = array($type,$number,$eno);
		$success =true;
		try{
			$ds = $da->GetData("table",$sql,$para);
			if ( $ds && $ds["table"]["recordcount"]>0){
				$sql = "update we_version_change set version = ifnull(version,0)+1 where number=? and `type`=? and eno=?;";
				$para = array((string)$number,(string)$type,(string)$eno);
			}
			else{
				$sql = "insert into we_version_change(`type`,number,version,eno)values(?,?,1,?);";
				$para = array((string)$type,(string)$number,(string)$eno);
			}
			try{
				 $da->ExecSQL($sql,$para);
			}
			catch(\Exception $e){
				$this->writelog($e->getMessage());
				$success = false;
			}
		}
		catch(\Exception $e){
			$this->writelog($e->getMessage());
			$success = false;
		}
		return $success;
	}
	
	//获得人员变化情况版本
	public function GetVersionChange($type,$number,$eno)
	{
		$da = $this->da;
		$sql = "select version from we_version_change where `type`=? and number=? and eno=?;";
		$para = array($type,$number,$eno);
		$version = "";
		try
		{			 
			 $ds = $da->GetData("table",$sql,$para);
			 if ($ds && $ds["table"]["recordcount"]>0)
			   $version = $ds["table"]["rows"][0]["version"];
		}
		catch(\Exception $e){
			$version = "";
			$this->writelog($e->getMessage());
		}
		$version = md5($version);
		return $version;
	}

	private function updatedeptversion($im_deptid)
	{
		$conn_im = $this->containter->get("we_data_access_im");
		$cache = Cache_Enterprise::get("d_ver_",$im_deptid,$this->containter);
		if(!empty($cache))
		{
			$cache = ((int)$cache)+1;
			$conn_im->ExecSQL("update im_dept_version set version=? where us=?",array(
					(string)$cache,
					(string)$im_deptid
			));
		}
		else
		{
			$conn_im = $this->containter->get("we_data_access_im");
			$sql = "select * from im_dept_version where us=?";
			$v_ds=$conn_im->GetData("t",$sql,array((string)$im_deptid));
			if(count($v_ds["t"]["rows"])==0)
			{
				$cache=1;
				$conn_im->ExecSQL("insert into im_dept_version(us,version)values(?,?)",array(
					(string)$im_deptid,
					(string)$cache
				));
			}
			else
			{
				$cache=((int)$v_ds["t"]["rows"][0]["version"])+1;
				$conn_im->ExecSQL("update im_dept_version set version=? where us=?",array(
					(string)$cache,
					(string)$im_deptid
				));
			}
			Cache_Enterprise::set("d_ver_",$im_deptid,(string)$cache,0,$this->containter);
		}		
	}
	
	protected function writelog($e)
	{
		if(!empty($this->logger))	{
			$this->logger->err($e);
		}
	}
}
?>