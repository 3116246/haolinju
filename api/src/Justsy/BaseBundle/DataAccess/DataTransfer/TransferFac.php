<?php
namespace Justsy\BaseBundle\DataAccess\DataTransfer;

use Justsy\BaseBundle\DataAccess\DataTransfer\TransferInf;
use Justsy\BaseBundle\DataAccess\DataTransfer\XmlTransfer;
use Justsy\BaseBundle\DataAccess\DataTransfer\JsonTransfer;
class TransferFac
{
	public static function getTransferObj($container,$dataType)
	{
		$classname = ucfirst($dataType)."Transfer";
		$classname="\\Justsy\\BaseBundle\\DataAccess\\DataTransfer\\". $classname;
		return call_user_func(array($classname,"init"),$container);
	}
}
?>