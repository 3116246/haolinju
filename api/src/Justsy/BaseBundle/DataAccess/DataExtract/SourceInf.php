<?php
namespace Justsy\BaseBundle\DataAccess\DataExtract;

interface SourceInf
{
	public function getByURL($url);
	public function getByDsid($user,$re,$parameters,$container);
}
?>