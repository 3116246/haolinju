<?php
namespace Justsy\BaseBundle\DataAccess\DataTransfer;

class	JsonTransfer implements TransferInf
{
	private $container=null;

	public function toJson($json,$mapping)
	{
		//mapping: title=A.A1,total=B.B1->C,list=[B->B1]
		if(empty($json)) return array("data"=>array(),"successcode"=>true,"message"=>"");		
		if(is_string($json))
		{
			$jsonObj =json_decode($json,true);
			if(empty($jsonObj)) //测试是否是纯文本，是则直接返回
			{
				return array("data"=>array($json),"successcode"=>true,"message"=>"");
			}
			$json = $jsonObj;
		}
		else if(is_bool($json) || is_numeric($json))
		{
			return array("data"=>array($json),"successcode"=>true,"message"=>"");
		}
		//没有配置 影射时，直接返回结果。
		//结果的类型由数据本身进行标识和封装。
		//如果是列表类型，$json参数格式应为:array("list"=>DATAROWS,...);
		//如果是对象类型，$json参数直接为一个OBJECT-DATA;
		if(empty($mapping)){
			$keys=array_keys($json);
			$chartPos = $keys[0];
      		return array("data"=>is_int($chartPos) ? $json : array($json),"successcode"=>true,"message"=>"");
	  	}
	  
	  /*
		//判断返回结果是否有错误
		$success = $this->isSuccess($json,$mapping);
		if ( !$success["successcode"]){
			$json["successcode"]=false;
			if ( isset($success["message"])){
				$json["message"] = $success["message"];
			}
			else{
				$json["message"] = "获取数据失败";
			}			
			return $json;
		}
		*/
		//判断数据是否成功数据
		$oprateFalg = $this->isSuccess($json,$mapping);
		if($oprateFalg["flag"]!==true)
		{
			$re = array("data"=>array(),"successcode"=>false,"message"=>$oprateFalg["msg"]);
			return $re;
		}
		$data = array();
		if(isset($mapping["__result"]) && !empty($mapping["__result"]))
		{
			//获取转换后的最终结果集
			$resultKey =explode(".", $mapping["__result"]);
			for ($i=0; $i < count($resultKey); $i++) {
				$json = $json[$resultKey[$i]];
			}

			//判断结果集是否是list格式
			$keys=array_keys($json);
			$chartPos = $keys[0];
			if(is_int($chartPos))
				$data = $json;
			else
				$data =array(0=>$json);
		}
		
		$mappingtype = $this->parseMappingType($mapping);
			
		//判断结果是返回列表还是对象
		
		if(count($mappingtype["object"])>0)
		{
			$data = array();
			$data[0] = $this->toObject($json,$mapping);
		}
		if(count($mappingtype["list"])>0)
		{ 
			$data =$this->toList($json,$mapping);
		}
		
		$re = array("data"=>$data,"successcode"=>true,"message"=>$oprateFalg["msg"]);
		return $re;
	}
	//从影射配置中按列表配置类型和属性配置类型分组
	public function parseMappingType($mapping)
	{
		$result = array("list"=>array(),"object"=>array());
		$keys = array_keys($mapping);
		for ($i=0; $i<count($mapping); $i++) {
			if($keys[$i]=="__result" || $keys[$i]=="__success" || $keys[$i]=="__msg") continue;
			$islist = false;
			$islist = $this->isList($keys[$i]);			
			if($islist===true)
			{
			  	$result["list"][] = $mapping[$keys[$i]];
			}			
			else $result["object"][] = $mapping[$keys[$i]];
		}
		return $result;
	}
	
	public function init($container)
	{		
		$obj= new self();
		$obj->container = $container;
		return $obj;
	}
	
	//判断结果是否成功
	//配置格式：{"__success":"errNum=0","__errormsg":"errMsg","__result":"retData"}
	private function isSuccess($json,$mapping)
	{
		$result = array("flag"=>true,"msg"=>"");
		if(isset($mapping["__success"]))
		{
			$flag = $mapping["__success"];
			$flag = explode("=", $flag);
			$flagvalue = $flag[1];
			$value = $json[$flag[0]];
			if($value==$flagvalue) 
			{
				$result["flag"] = true;
			}
			else
			{
				$result["flag"] = false;
			}
		}
		else
		{
			$result["flag"] = true;
		}
		$msgfalg =isset( $mapping["__msg"])? $mapping["__msg"] : "";
		if(!empty($msgfalg))
		{
			$msgfalg = $json[$msgfalg];
			$result["msg"] = $msgfalg;
		}		
		return $result;
	}
	
	private function toList($json,$mapping)
	{	    
		$result = array();
		$path = array_keys($mapping);
		$pathtemp = explode("->",$path[0]);
		$result = array();
		if ( empty($pathtemp[0]) || isset($json[$pathtemp[0]]))
		{
			if(!empty($pathtemp[0]))
			    $json = $json[$pathtemp[0]];
			for ($i=0; $i < count($json); $i++) { 
				array_push($result,$this->getAttrListValue($json[$i],$mapping));
			}
	  }
	  else{
	  	$result = $json;
	  }
		return $result;
	}
	
	private function toObject($json,$mapping)
	{
	    
		$result = array();
		foreach ($mapping as $key => $value) {
      		$jsonvalue = $this->getAttrValue($json,$key);
		    if (!empty($jsonvalue)){
		       $result[$value] = $jsonvalue;
		    }
		}
		return $result;
	}
	
	private function getAttrListValue($jsondata,$mapping)
	{
		$return = array();
		
		foreach ($mapping as $key => $value) {
			$tmpdata = $jsondata;
			$temp = preg_split("/->|\./",$key);	
			if ( count($temp)==1) $tmpdata = $tmpdata[$key];
			else{
				for ($i=1; $i < count($temp); $i++) { 
					$key = $temp[$i];
					if(isset($tmpdata[$key]))
						$tmpdata =is_array($tmpdata[$key])&& !empty($tmpdata[$key][0]) ? $tmpdata[$key][0] : $tmpdata[$key];
					else
						$tmpdata=null;			
				}
			}
			$return[$value]= $tmpdata;
			/*
			foreach($jsondata as $key1 => $value1){
				if ($key==$key1){
					$return[$value]=$value1;
				}
			}*/
		}
		return $return;
	}

	private function getAttrValue($jsondata,$valuepath)
	{	    
	  
	    $pathSplit = preg_split("/->|\./",$valuepath);
		$Len = count($pathSplit);
		if ( $Len==1) return $jsondata[$valuepath];
		
		for ($i=0; $i < $Len; $i++) {
			$key =$pathSplit[$i];
			if ( isset($jsondata[$key])){
			  $jsondata =is_array($jsondata[$key]) && !empty($jsondata[$key][0])? $jsondata[$key][0] : $jsondata[$key];
			}
			else
			  $jsondata = null;
		}
		return $jsondata;
	}
	
	private function isList($mapping0)
	{
		if(strpos($mapping0, "->") !==false ) return true;
		return false;
	}
}
?>