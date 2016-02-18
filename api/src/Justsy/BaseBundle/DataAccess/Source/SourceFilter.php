<?php
namespace Justsy\BaseBundle\DataAccess\Source;

class SourceFilter{
	public static filterData($da,$sourceType,&$data,&$err)
	{
		if($sourceType=="REST"){
			//此处还没写
		}
		else if($sourceType=="SOAP"){
			//此处还没写
		}
		else if($sourceType=="DB")
			//此处还没写
		}
		else{
			$err=array("returncode"=>"9999","msg"=>"未知的数据源类型");
		}
	}
}
?>