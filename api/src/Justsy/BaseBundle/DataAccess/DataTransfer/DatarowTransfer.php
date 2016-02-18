<?php
namespace Justsy\BaseBundle\DataAccess\DataTransfer;

class	DatarowTransfer implements TransferInf
{
	private $container=null;
	public function toJson($datarows,$mapping)
	{
		//relation: title=A.A1,total=B.B1->C
		if(empty($datarows)) return array();
		if(empty($mapping)) return $datarows;

		$JsonTransfer = new JsonTransfer();
		$JsonTransfer->init($this->container);
		if(empty($mapping))
			$result = $JsonTransfer->toJson(array("list"=>$datarows),null);
		else
			$result = $JsonTransfer->toJson($datarows,$mapping);
		return $result;
	}
	public function init()
	{
		$obj= new self();
		$obj->container = $container;
		return $obj;
	}
}
?>