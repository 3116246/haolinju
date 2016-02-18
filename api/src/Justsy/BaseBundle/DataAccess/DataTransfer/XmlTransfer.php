<?php
namespace Justsy\BaseBundle\DataAccess\DataTransfer;

class XmlTransfer implements TransferInf
{
	private $container=null;
	public function toJson($xml,$mapping)
	{
		$xml = strtolower($xml);
		$array = preg_split("/:\b/i",$xml);
    for($i=0;$i<count($array);$i++){
   	 $temp = $array[$i];
   	 $ta = explode("</",$temp);
   	 if (is_array($ta) && count($ta)>1){
   	 	 $len = count($ta);
   	 	 $ta[$len-1]="";
   	 	 $array[$i] = implode("</",$ta);
   	 	 continue;
   	 }
   	 $ta = explode("<",$temp);
   	 if (is_array($ta) && count($ta)>1){
   	 	 $len = count($ta);
   	 	 $ta[$len-1]="";
   	 	 $array[$i] = implode("<",$ta);
   	 	 continue;
   	 }
   	 $ta = explode(" ",$temp);
   	 if (is_array($ta) && count($ta)>1){
   	 	 $len = count($ta);
   	 	 $ta[$len-1]="";
   	 	 $array[$i] = implode(" ",$ta);
   	 	 continue;
   	 }     	
    }
    $xml = implode("",$array);
		$result =  simplexml_load_string($xml);
		$result = json_encode($result);
		$result = json_decode($result,true);
		$JsonTransfer = new JsonTransfer();
		$JsonTransfer->init($this->container);
		$result = $JsonTransfer->toJson($result,$mapping);
		return $result;
	}

	public function init($container)
	{
		$obj= new self();
		$obj->container = $container;
		return $obj;
	}
}
?>