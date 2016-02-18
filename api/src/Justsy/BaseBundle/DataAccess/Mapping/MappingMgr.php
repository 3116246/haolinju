<?php
namespace Justsy\BaseBundle\DataAccess\Mapping;

use Justsy\BaseBundle\DataAccess\Mapping\MappingFilter;

class MappingMgr
{
	protected $container;
	protected $logger;
	protected $da;
	public function __construct($_container)
	{
	    $this->container = $_container;
	    $this->da = $_container->get("we_data_access");
	  	$this->logger=$_container->get("logger");
	}
	public function addMapping($v_data)
	{
		$sql = "insert into mapp_datasource_mapping(datasourceid,mapping_type,source_attr,target_attr,inf_code,orderno)values(?,?,?,?,?,?)";
		$this->da->ExecSQL(
			$sql,
			array(
				(int)$v_data["dsid"],
				(string)$v_data["mapping_type"],
				(string)$v_data["source_attr"],
				(string)$v_data["target_attr"],
				(string)$v_data["inf_code"],
				0
			)
		);
		return true;
	}
	public function editMapping($v_data)
	{
		$sql = "update mapp_datasource_mapping set ";
		$cols = array();
		$values = array();
		$key = array( "mapping_type","source_attr","target_attr","inf_code","orderno");
		for ($i=0; $i < count($key); $i++) 
		{
			$col = $key[$i];
			if(isset($v_data[$col]))
			{
				$cols[] = $col."=?"; 
				$values[] = $v_data[$col];
			}
		}
		if(count($cols)>0)
		{
			$sql .= implode(",", $cols)." where id=?";
			$values[] = $v_data["id"];
			$this->da->ExecSQL($sql,$values);
		}
		return true;
	}

	public function deleteMapping($v_data)
	{
		$sql = "delete from mapp_datasource_mapping where id=?";
		$this->da->ExecSQL($sql,array(
			(int)$v_data["id"]
		));
		return true;
	}

	public function getMapping($v_data)
	{
		$sql = "select source_attr,target_attr from mapp_datasource_mapping where ";
		$key = array( "mapping_type","id","datasourceid","inf_code","source_attr");
		for ($i=0; $i < count($key); $i++)
		{
			$col = $key[$i];
			if(isset($v_data[$col]))
			{
				$cols[] = $col."=?";
				$values[] = $v_data[$col];
			}
		}
		if(count($cols)>0)
		{
			$sql .= implode(" and ", $cols); 
			$sql .= " order by orderno";
			$ds = $this->da->GetData("t",$sql,$values);
			$result = array();
			if ( $ds && $ds["t"]["recordcount"]>0){
				for($i=0;$i< $ds["t"]["recordcount"];$i++){
					$key = $ds["t"]["rows"][$i]["source_attr"];
					$result[$key]=$ds["t"]["rows"][$i]["target_attr"];
				}
			}
			return $result;
		}
		else{
			return null;
		}
	}
}
?>