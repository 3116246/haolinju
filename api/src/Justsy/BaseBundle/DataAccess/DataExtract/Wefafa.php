<?php
namespace Justsy\BaseBundle\DataAccess\DataExtract;
use Justsy\BaseBundle\Common\Utils;

/**
 * @author zhangbo@fafatime.com
 * 统一访问数据接口
 * 
 */
class Wefafa implements SourceInf
{
	public function getByURL($url)
	{
	   return;			
	}
	public function getByDsid($user,$re,$parameters,$container)
	{		
		if ( empty($parameters)){
			$parameters = array();
		}
		else if (is_string($parameters))
		{
			$parameters = json_decode($parameters,true);
		}
		if ( !empty($parameters))
		{
		   $parameters = isset($parameters["parameters"]) ? json_decode($parameters["parameters"],true) : $parameters;
		}

		$parameters["user"] = $user;

		if ( isset($re["module_name"]) && isset($re["req_action"])){		
			$module_name = $re["module_name"];
			$action = $re["req_action"];
		    $classname = "\Justsy\BaseBundle\Management";
		    $classname = $classname."\\".ucfirst($module_name);
		    //动态产生指定的管理对象的实例
		    $class = call_user_func(array($classname,"getInstance"),$container);
		    //动态调用指定的方法
			$http_data = call_user_func_array(array($class,$action),array($parameters));
			return $http_data;
		}
		else{
			throw new \Exception("配置错误！");
		}
	}
}
?>