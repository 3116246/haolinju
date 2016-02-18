<?php
namespace Justsy\BaseBundle\DataAccess\DataTransfer;

interface TransferInf
{
	public function toJson($data,$mapping);
	public function init($container);
}
?>