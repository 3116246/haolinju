<?php

namespace Justsy\BaseBundle\DataAccess;
use Justsy\BaseBundle\Common\Cache_Enterprise;
class SysParam
{
  protected $container;
  
  public function __construct($container)
  {
    $this->container = $container;
  }
  
  public function GetSysParam($ParamName,$defaultvalue='',$isfresh=false) 
  {
    $data = "";
    try
    {
      $data=Cache_Enterprise::get(Cache_Enterprise::$SYS,$ParamName,$this->container);
    }
    catch(\Exception $e)
    {
      $this->logger->err($e);
      $data=null;
    }
    if(empty($data) || $isfresh)
    {
      $dataaccess = $this->container->get('we_data_access');
      $dataset = $dataaccess->GetData("we_sys_param", "select param_name, param_value from we_sys_param where param_name = ? ", array(((string)$ParamName)));
      
      if ($dataset && count($dataset["we_sys_param"]["rows"]) > 0)
      {
        $data = $dataset["we_sys_param"]["rows"][0]["param_value"];
        Cache_Enterprise::set(Cache_Enterprise::$SYS,$ParamName,$data,0,$this->container);
      }
      else
      {
      	$dataaccess->ExecSQL('insert into we_sys_param(param_name, param_value)values(?,?)',
      		array((string)$ParamName,(string)$defaultvalue)
      	);
      	$data = $defaultvalue;
      }
    }
    return $data;
  }
}