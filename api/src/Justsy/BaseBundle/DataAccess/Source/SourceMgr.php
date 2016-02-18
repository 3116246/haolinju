<?php

namespace Justsy\BaseBundle\DataAccess\Source;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\DataAccess\Source\SourceFilter;

class SourceMgr
{
  protected $container;
  protected $logger;
  protected $da;
  public function __construct($_container,$da,$_logger)
  {
    $this->container = $_container;
    $this->logger = $_logger;
  	$this->da=$da;
  }
  
  public function addRest($v_data,&$err)
  {
		try{
			SourceFilter::filterData($this->da,"REST",&$v_data,&$err);
			if($err)return false;
			$sourceid=$this->getCurrentSourceID();
			
			$sql="insert into mapp_datasource (id,eno,appid,inf_type,inf_code,inf_name,module_name,inf_url,iscache,refresh_time,orderno) values(?,?,?,?,?,?,?,?,?,?,?)";
			$params=array($sourceid,
										$v_data["eno"],
										$v_data["appid"],
										$v_data["inf_type"],
										$v_data["inf_code"],
										$v_data["inf_name"],
										$v_data["module_name"],
										$v_data["inf_url"],
										$v_data["iscache"],
										$v_data["refresh_time"],
										$v_data["orderno"]
			);
			if(!$this->da->ExecSQL($sql,$params)){
				return false;
			}
			return true;
		}
		catch(\Exception $e){
			$this->logger->err($e);
		}
  }

  public function addSoap($v_data,&$err)
  {
		try{
			SourceFilter::filterData($this->da,"SOAP",&$v_data,&$err);
			if($err)return false;
			$sourceid=$this->getCurrentSourceID();
			
			$sql="insert into mapp_datasource (id,eno,appid,inf_type,inf_code,inf_name,module_name,inf_url,iscache,refresh_time,req_action,orderno) values(?,?,?,?,?,?,?,?,?,?,?)";
			$params=array($sourceid,
										$v_data["eno"],
										$v_data["appid"],
										$v_data["inf_type"],
										$v_data["inf_code"],
										$v_data["inf_name"],
										$v_data["module_name"],
										$v_data["inf_url"],
										$v_data["iscache"],
										$v_data["refresh_time"],
										$v_data["req_action"],
										$v_data["orderno"]
			);
			if(!$this->da->ExecSQL($sql,$params)){
				return false;
			}
			return true;
		}
		catch(\Exception $e){
			$this->logger->err($e);
		}
  }

  public function addDB($v_data,&$err)
  {
		try{
			SourceFilter::filterData($this->da,"REST",&$v_data,&$err);
			if($err)return false;
			$sourceid=$this->getCurrentSourceID();
			
			$sql="insert into mapp_datasource (id,eno,appid,inf_type,inf_code,inf_name,module_name,inf_url,iscache,refresh_time,orderno) values(?,?,?,?,?,?,?,?,?,?,?)";
			$params=array($sourceid,
										$v_data["eno"],
										$v_data["appid"],
										$v_data["inf_type"],
										$v_data["inf_code"],
										$v_data["inf_name"],
										$v_data["module_name"],
										$v_data["inf_url"],
										$v_data["iscache"],
										$v_data["refresh_time"],
										$v_data["orderno"]
			);
			if(!$this->da->ExecSQL($sql,$params)){
				return false;
			}
			return true;
		}
		catch(\Exception $e){
			$this->logger->err($e);
		}
  }
  
  
  private function getCurrentSourceID()
  {
  	$id = SysSeq::GetSeqNextValue($this->da,"mapp_datasource","id");  	
  	return $id;
  }
}