<?php
namespace Justsy\BaseBundle\DataAccess\DataExtract;

use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\Cache_Enterprise;

/**
 * @author liling@fafatime.com
 * 通过http地址获取数据
 * 
 */
class Rest implements SourceInf
{
	public function getByURL($url)
	{
	  	$result = Utils::getUrlContent($url);
	    return $result;				
	}
	
	public function getByDsid($user,$re,$parameters,$container)
	{
		if(isset($re["inf_url"]) && !empty($re["inf_url"]))
		{
        	$url = $re["inf_url"];
        	$is_auth = $re["is_auth"]; //接口是否需要认证，1：需要认证 0：不认证
	        $str_para = "";
	        if(empty($parameters) || $parameters=="{}")
	        {
	            $parameters = $re["inf_parameter"];
	        }

	        $need_para = $re["inf_parameter"];
	        if(!empty($need_para) && is_string($need_para))
	        {
	        	$need_para = json_decode($need_para,true);
	        }
	        $app = new \Justsy\BaseBundle\Management\App($container);
	        //parameters为json数据格式
	        if (empty($parameters))
	        {
	          	throw new \Exception("参数appid不能为空！");
	        }
	        $parameters =is_array($parameters) ? $parameters : json_decode($parameters,true);
	        $appdata = $app->getappinfo(array("appid"=>$parameters["appid"]));
			$authtype =isset($appdata["authtype"]) ? $appdata["authtype"] : "";
			if(!empty($authtype) && $is_auth=="1")
			{
		        $classname = dirname(dirname(dirname(dirname(__FILE__))))."/OpenAPIBundle/Controller/Sso".ucfirst($authtype)."Controller.php";	            
		        //$container->get("logger")->err("===========file_exists ".$classname);
		        if(file_exists($classname))
		        {
		        	$classname = "\Justsy\OpenAPIBundle\Controller\Sso".ucfirst($authtype)."Controller";
		        	//$container->get("logger")->err("===========load ".$classname);
		        	$re = call_user_func(array($classname, 'rest'),$container,$user,$re,$parameters,$need_para);
		        	//$container->get("logger")->err("===========load ".$classname." result:".json_encode($re));
		        	
		        	return $re;
		        }
	    	}
			if (!empty($parameters) )
	        {
	            	//将参数数组转化为字符串
	              if ( is_array($parameters) && !empty($need_para))
	              {
	              	for ($i=0; $i <count($need_para) ; $i++) {
	              		$pname = $need_para[$i]["paramname"];
	              		$val = isset($parameters[$pname]) ? $parameters[$pname] : $need_para[$i]["paramvalue"];
	              	 
	                	$str_para .= $pname."=".$val."&";
	                	
	                }
	                $str_para = rtrim($str_para,"&");
	              }
	        }
	        
	        $method = $re["req_action"];
	        $method = $method!="GET" ? "POST" : "GET";
	        $container->get("logger")->err("authtype:".$authtype);
	        $optional_headers = null;
	        if($authtype=="header")
	        {
	        	$userpara= $appdata["userdefined_para"];
	        	if(!empty($userpara))
	        	{
	        		$optional_headers = json_decode($userpara,true);
	        	}
	        }
	        else if($authtype=="basic")
	        {
	        	$userpara= $appdata["userdefined_para"];
	        	if(!empty($userpara))
	        	{
	        		$userpara = json_decode($userpara,true);
	        		$user = $userpara["user"];
	        		$pass = $userpara["pass"];
	        		$optional_headers=array("Authorization"=>"Basic ".base64_encode("{$user}:{$pass}"));
	        	}
	        }
	        if( $method=="GET")
	        {
	            if ( strpos($url,"?")===false)
	              $url = $url."?".$str_para;
	            else
	              $url = $url."&".$str_para; 
	          	$container->get("logger")->err($url);
	            return Rest::getByURL($url);
	        }
	        else{
	            $method = "POST";
	            if ( strpos($url,"?")===false)
	              $url = $url."?".$str_para;
	            else
	              $url = $url."&".$str_para; 
	              $str_para = "";
	        }
            $container->get("logger")->err($url);
            //,CURLOPT_COOKIE
            $http_data = Utils::do_post_request_cookie($url,null,$optional_headers,$_COOKIE,$method);
            /*
		    $params = array('http' => array('timeout'=>3,'method' => $method,'content' => $str_para));  
		    $ctx = stream_context_create($params);
		    $fp = @fopen($url, 'r', false, $ctx);
		    if (!$fp) 
		    {
		      throw new \Exception("接口地址出错，请检查您的接口地址(".$url.")");
		    }
		    //获取数据
		    $http_data = @stream_get_contents($fp);
		    if ($http_data === false) 
		    {
		      throw new \Exception("访问的接口地址(".$url.")服务器出错！");
		    }*/
			return $http_data;
		}
		else
		{
			throw new \Exception("接口地址为空，请检查！");
		}
	}
}
?>