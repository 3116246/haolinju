<?php
namespace Justsy\BaseBundle\DataAccess\DataExtract;
use SoapClient;

class Soap  implements SourceInf
{
	public function getByURL($url)
	{
		return null;
	}
	public function getByDsid($user,$re,$parameters,$container)
	{
		if(isset($re["req_action"]))
		{
			if(empty($re["req_action"]))
			{
				throw new \Exception("未指定请求的方法!");
			}
		}
		if(isset($re["inf_url"]))
		{
			$url = $re["inf_url"];
			try{
				$options = array('connection_timeout'=>3);
				$soap=new SoapClient($url,$options);
			}
			catch(\Exception $e)
			{
				throw new \Exception("未正确装载SoapClient模块或者wsdl地址无效");
			}
			error_reporting(E_ERROR|E_WARNING|E_PARSE);
			//$result=$soap->invoke($paras);
			$api_parameters=$parameters["parameters"];
			if ( !empty($api_parameters))
			{
				if(is_string($api_parameters))
					$inf_parameters = json_decode($api_parameters,true);
				else
					$inf_parameters = json_decode(json_encode($api_parameters),true);
			}
			else
			{
				$inf_parameters = array();
			}
			$container->get("logger")->err("soap inf_parameters:".json_encode($inf_parameters));
			$http_data = $soap->__call($re["req_action"],array($inf_parameters));
			error_reporting(E_ERROR|E_WARNING|E_PARSE|E_NOTICE);
			return $http_data;
		}
		else
		{
			throw new \Exception("未指定请求的WSDL地址");
		}
	}
}
?>