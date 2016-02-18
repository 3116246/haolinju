<?php

namespace Justsy\BaseBundle\DataAccess\DataExtract;

use Justsy\BaseBundle\DataAccess\Mapping\MappingMgr;
use Justsy\BaseBundle\Common\Cache_Enterprise;
use Justsy\BaseBundle\DataAccess\DataTransfer\ZtreeTransfer;

class Extract
{
  protected $container;
  
  public function __construct($container)
  {
    $this->container = $container;
  }
  
  //按组件id获得数据
  public function execute($user,$dsid,$inf_parameters,&$err)
  {
    $logger = $this->container->get("logger");
    //判断是否有缓存数据
    $cache_key = md5($dsid.$inf_parameters);
    try
    {      
      $data=Cache_Enterprise::get(Cache_Enterprise::$EN_DATAINF,$cache_key,$this->container);
    }
    catch(\Exception $e)
    {
      $logger->err($e);
      $data=null;
    }
    if(!empty($data)) //直接返回缓存数据
    {
      $logger->err(Cache_Enterprise::$EN_DATAINF.$cache_key."-------------get cache data------------");
      $result = json_decode($data,true);
      return $result;
    }
    try{
    	$re = "";
	    $dataaccess = $this->container->get('we_data_access');
	    
	    $sql = "select 1 from mapp_component_datasource a,mapp_datasource b where a.datasourceid=b.id and a.id=?";
	    $ds = $dataaccess->GetData("table",$sql,array((string)$dsid));
	    if ( $ds && $ds["table"]["recordcount"]>0)
	    {
	    	$sql ="SELECT b.id,a.eno,a.systemid,b.inf_type,concat(a.inf_url,b.inf_url) inf_url,b.is_auth,a.return_type,a.req_user,a.req_pass,a.req_method,b.req_action,a.authtype,b.iscache,b.refrsh_time,b.return_mapping,b.inf_parameter ".
                  " FROM mapp_interface_system a,mapp_interface_list b,mapp_component_datasource c,mapp_datasource d where a.systemid=b.systemid and b.inf_type=a.inf_type and b.id=d.inf_code and d.id=c.datasourceid and c.id=?";
	    }
	    else
	    {
	    	$sql ="SELECT b.id,a.eno,a.systemid,b.inf_type,concat(a.inf_url,b.inf_url) inf_url,b.is_auth,a.return_type,a.req_user,a.req_pass,a.req_method,b.req_action,a.authtype,b.iscache,b.refrsh_time,b.return_mapping,b.inf_parameter ".
                  " FROM mapp_interface_system a,mapp_interface_list b where a.systemid=b.systemid and b.inf_type=a.inf_type and b.id=?";
	    }
	    $dataset = $dataaccess->GetData("ds", $sql, array(((string)$dsid)));
	    if ($dataset && count($dataset["ds"]["rows"]) > 0)
	    {
	    	//确认所传参数是否匹配
	    	$mappingMgr=new MappingMgr($this->container);
	        $mappDetail = $mappingMgr->getMapping(array("datasourceid"=>$dsid,"mapping_type"=>"1"));
	        if(!empty($mappDetail))
	        {
	        	 //参数影射转换
	           $inf_parameters = $this->parametersTransfer($inf_parameters,$mappDetail);           
	        }
	      	$re = $dataset["ds"]["rows"][0];
	      	//静态数据直接返回
	      	/*if($re["inf_type"]=="Static")
	      	{
	      		$result = array(
				          "returncode"=>"0000",
				          "msg"=>"",
				          "data"=> json_decode($re["inf_url"],true)
				        );
	      		return $result;
	      	}*/
	     	//判断数据类型
        	$http_data_type = $re["return_type"];
        	if(empty($http_data_type))
          		$http_data_type = "json"; //默认为json类型      
		    $inf_type = $re["inf_type"];
		    //判断是否是请求的wefafa接口
		    if($inf_type=="Rest")
		    {
		    	$url = $re["inf_url"];
		    	if(!empty($url) && strpos($url,"interface/data_access")!==false)
		    	{
		    		preg_match("/module=.*?&/i", $url,$mt1);
		    		preg_match("/action=.*?&/i", $url,$mt2);
		    		$inf_type = "Wefafa";
		    		$re["module_name"] =str_replace("&","", str_replace("module=", "", $mt1[0]));
		    		$re["req_action"] = str_replace("&","", str_replace("action=", "", $mt2[0]));
		    	}
		    }
		    $classname = "\Justsy\BaseBundle\DataAccess\DataExtract";
		    $classname = $classname."\\".ucfirst($inf_type);
		    try
		    {
		    	if($re["inf_type"]!="Static")
		    	{
		    		if($re["inf_type"]=="Database")
		    		{
		    			$conninfo =json_decode($re["inf_url"],true);
		    			$classname = "\Justsy\BaseBundle\DataAccess\DataExtract"."\\".ucfirst(strtolower($conninfo["type"]));
		    		}
			      	$http_data = call_user_func(array($classname,'getByDsid'),$user,$re,$inf_parameters,$this->container);
			    	if(!empty($http_data) && isset($http_data["returncode"]))
			    	{
			    		if($http_data["returncode"]=="0000")
			    		{
			    			$http_data = $http_data["data"];
			    		}
			    		else
			    			return $http_data;
			    	}
			    }
			    else
			    {
			    	$http_data = $re["inf_url"];
			    }
			        //转换成json
			        $mappDetail = $mappingMgr->getMapping(array("datasourceid"=>$dsid,"mapping_type"=>"2"));
			        //如果没找属性影射时，获取数据源的结果转换配置
			        if(empty($mappDetail) && !empty($re["return_mapping"]))
			        {
			        	$mappDetail = json_decode($re["return_mapping"],true);
			        }
			        try
			        {
			        	$transferinfo=\Justsy\BaseBundle\DataAccess\DataTransfer\TransferFac::getTransferObj($this->container,$http_data_type);
			        	$re = $transferinfo->toJson($http_data,$mappDetail);
			        	if(empty($re))
			        	{
			        		$logger->err("----DataTransfer err----org data:".json_encode($http_data));
			        	}

			    	}
			    	catch(\Exception $e)
				    {
				    	$logger->err($e);
				    	$logger->err(json_encode($http_data));
				    	$logger->err(json_encode($mappDetail));
				    }
			        //判断处理结果
			        if ( $re["successcode"]===true ){
				        $result = array(
				          "returncode"=>"0000",
				          "msg"=>"",
				          "data"=> $re["data"]
				        );
				        //缓存数据。默认30秒后立即过期
				        Cache_Enterprise::set(Cache_Enterprise::$EN_DATAINF,$cache_key,json_encode($result),30,$this->container);
			        }
			        else{
			        	$result = array(
				          "returncode"=>"9999",
				          "msg"=>$re["message"],
				        );
			        }
			        return $result;	        
		    }
		    catch(\Exception $e)
		    {
		    	$re=array("returncode"=>"9999","msg"=>$e->getMessage());
		    }
	    }
	    else
	    {
	    	$re=array("returncode"=>"9999","msg"=>"");
	    }
    	return $re;
    }
    catch(\Exception $e){
    	return array("returncode"=>"9999","msg"=>$e->getMessage());
    }
  }

  //按数据源id获得数据
  public function executeBydsid($user,$dsid,$inf_parameters,&$err)
  {
    $logger = $this->container->get("logger");
    //判断是否有缓存数据
    $cache_key = md5($dsid.$inf_parameters);
    try
    {
      $data=Cache_Enterprise::get(Cache_Enterprise::$EN_DATAINF,$cache_key,$this->container);
    }
    catch(\Exception $e)
    {
      $logger->err($e);
      $data=null;
    }
    if(!empty($data)) //直接返回缓存数据
    {
      $logger->err(Cache_Enterprise::$EN_DATAINF.$cache_key."-------------get cache data------------");
      $result = json_decode($data,true);
      return $result;
    }
    try{
    	$re = "";
	    $dataaccess = $this->container->get('we_data_access');
	    $dataset = $dataaccess->GetData("ds", "select * from mapp_datasource where id=? ", array(((string)$dsid)));
	    if ($dataset && count($dataset["ds"]["rows"]) > 0)
	    {
	      	$re = $dataset["ds"]["rows"][0];             
	      	$inf_type = $re["inf_type"];
	      	//静态数据直接返回
	      	if($re["inf_type"]=="Static")
	      	{
	      		$result = json_decode($re["inf_url"],true);
	      		$ztree = new ZtreeTransfer($this->container);
		      	$returndata = $ztree->dataToTree($result);
	      		return array("returncode"=>"0000","list"=>$returndata);
	      	}	      
	      $classname = "\Justsy\BaseBundle\DataAccess\DataExtract";
	      $classname = $classname."\\".ucfirst($inf_type);
	      try
	      {
	      	$returndata =  null;
	      	$http_data = call_user_func(array($classname,'getByDsid'),$user,$re,$inf_parameters,$this->container);
		      if (is_string($http_data)){
		      	$returndata = json_decode($http_data,true);
		      }
		      else{
		      	$returndata = $http_data;
		      }
		      //转换数据
		      $ztree = new ZtreeTransfer($this->container);
		      $returndata = $ztree->dataToTree($returndata);
		      		      		      
		      $result = array("returncode"=>"0000","list"=>$returndata);
	      	Cache_Enterprise::set(Cache_Enterprise::$EN_DATAINF,$cache_key,json_encode($result),30,$this->container);
	        return $result;
	      }
	      catch(\Exception $e){
	      	$re=array("returncode"=>"9999","msg"=>$e->getMessage());
	      }
	    }
	    else
	    {
	    	$re=array("returncode"=>"9999","msg"=>"数据源id不存在");
	    }
    	return $re;
    }
    catch(\Exception $e){
    	return array("returncode"=>"9999","msg"=>$e->getMessage());
    }
  }
    
  public function executeByURL($url) 
  {
    $re = "";    
    return $re;
  }
  
  //参数影射关系
  private function parametersTransfer($v_value,$v_mapp)
  {
    foreach ($v_mapp as $key => $value)
    {     	
      if(stripos($v_value,$key)!==false)
      {
        $v_value = str_replace($key,$value,$v_value);
      }
    }
    return $v_value;
  }
}