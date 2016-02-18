<?php
namespace Justsy\BaseBundle\Common;
use Memcache;
class Cache_Enterprise
{
	public static $SYS="sys_param_";

  public static $EN_INFO="enterpriseinfo"; 
  public static $EN_USER_AUTH="enterpriseuserauth";
  public static $EN_ROLE="role";
  public static $EN_FUNCTION="function";
  public static $EN_STAFF="staff";
  public static $EN_DATAINF="data_inf_";
  public static $EN_DEPT="dept";

  public static $EN_OAUTH2 = "oauth2_";
  public static $EN_APP = "app_";
  public static $EN_APP_BIND= "appbind_";
  
  public static function get($type,$key,$container=null) 
  {
    $server_name = "localhost"; //默认服务器
    $server_port = 11211; //默认端口
	$memcache = new Memcache;
	try {
		if(!$memcache->connect($server_name, $server_port)){	
			if(!empty($container))
			{
			 	$container->get("logger")->err($server_name." Memcache con't Conn!");
			}
			return null;
		}
	}
	catch(\Exception $e)
	{
		if(!empty($container))
			$container->get("logger")->err($server_name." Memcache Conn error:".$e->getMessage());
		return null;
	}
	$newkey = $type.$key;
	$getdata=$memcache->get($newkey);	
	return $getdata;
  }
  
  public static function set($type,$key,$data,$expTime=0,$container=null)
  {
  	$memcached_servers =array("localhost");
    try {
    	if(!empty($container) )
    	{
        	$memcached_servers = $container->getParameter("memcached_servers");
        	if (!empty($memcached_servers))
            	$memcached_servers = json_decode($memcached_servers, true);
    	}
    } 
    catch (\Exception $e)
    {
    }
    //同步数据到多台缓存服务器
    $server_name = "localhost"; //默认服务器
    $server_port = 11211; //默认端口
    for ($i=0; $i < count($memcached_servers); $i++) { 
	  	$memcache = new Memcache;
	  	$server_inf = $memcached_servers[$i];
	  	if(is_string($server_inf))
	  	{
	  		$server_name = $server_inf;
	  	}
	  	else
	  	{
	  		$server_name = $server_inf[0];
	  		if(count($server_inf)==2) 
	  		{
	  			$server_port = $server_inf[1];
	  		}
	  	}
	  	try
	  	{
			if(!$memcache->connect($server_name, $server_port)){
				if(!empty($container))
				{
			 		$container->get("logger")->err($server_name." Memcache con't Conn!");	 	
			 		continue;
			 	}
			}
		}
		catch(\Exception $e)
		{
			if(!empty($container))
				$container->get("logger")->err($server_name." Memcache Conn error:".$e->getMessage());
			continue;
		}
		$newkey = $type.$key;
		if($type==self::$EN_STAFF)
		{
		 	$value = $memcache->get($newkey);
		 	if(empty($value))
		 	{
			 	$staffcount = self::get($type,".count");
			 	$staffcount = empty($staffcount) ? 0 : intval($staffcount);
			 	$memcache->set($type.".count",$staffcount+1);
		 	}
		 	$expTime =15*24*60*60;
		}	     	 
  		$memcache->set($newkey,$data,0,$expTime);
  		$memcache->close();
  	}
  }
 
  public static function delete($type,$key,$container=null)
  {
  	$memcached_servers =array("localhost");
    try {
    	if(!empty($container))
    	{
        	$memcached_servers = $container->getParameter("memcached_servers");
        	if (!empty($memcached_servers))
            	$memcached_servers = json_decode($memcached_servers, true);
    	}
    } 
    catch (\Exception $e)
    {
    }  	
    $server_name = "localhost"; //默认服务器
    $server_port = 11211; //默认端口
    for ($i=0; $i < count($memcached_servers); $i++) { 
		$memcache = new Memcache;
	  	$server_inf = $memcached_servers[$i];
	  	if(is_string($server_inf))
	  	{
	  		$server_name = $server_inf;
	  	}
	  	else
	  	{
	  		$server_name = $server_inf[0];
	  		if(count($server_inf)==2) 
	  		{
	  			$server_port = $server_inf[1];
	  		}
	  	}
	  	try
	  	{
			if(!$memcache->connect($server_name, $server_port)){
				if(!empty($container))
				{
			 		$container->get("logger")->err($server_name." Memcache con't Conn!");	 	
			 		continue;
			 	}
			}
		}
		catch(\Exception $e)
		{
			if(!empty($container))
				$container->get("logger")->err($server_name." Memcache Conn error:".$e->getMessage());
			continue;
		}
		$newkey = $type.$key; 
		if($type==self::$EN_STAFF) //人员缓存
		{
		 	$value = $memcache->get($newkey);
		 	if(!empty($value))
		 	{
			 	$staffcount = self::get($type,".count");
			 	$staffcount = empty($staffcount) ? 0 : intval($staffcount);
			 	if($staffcount>0)
			 	{
		 			$memcache->set($type.".count",$staffcount-1);
		 		}
		 	}
		}	       	 
	  	$memcache->delete($newkey);  
	  	$memcache->close();
  	}
  }
  
	public static function flush($container=null)
	{
		$data=array();
	  	$memcached_servers =array("localhost");
	    try {
	    	if(!empty($container) )
	    	{
	        	$memcached_servers = $container->getParameter("memcached_servers");
	        	if (!empty($memcached_servers))
	            	$memcached_servers = json_decode($memcached_servers, true);
	    	}
	    } 
	    catch (\Exception $e)
	    {
	    }
	    //同步数据到多台缓存服务器
	    $server_name = "localhost"; //默认服务器
	    $server_port = 11211; //默认端口
	    for ($i=0; $i < count($memcached_servers); $i++) { 
		  	$memcache = new Memcache;
		  	$server_inf = $memcached_servers[$i];
		  	if(is_string($server_inf))
		  	{
		  		$server_name = $server_inf;
		  	}
		  	else
		  	{
		  		$server_name = $server_inf[0];
		  		if(count($server_inf)==2) 
		  		{
		  			$server_port = $server_inf[1];
		  		}
		  	}
		  	try
		  	{
				if(!$memcache->connect($server_name, $server_port)){
					$data[] = array($server_name=>'-1');
					if(!empty($container))
					{
				 		$container->get("logger")->err($server_name." Memcache con't Conn!");	 	
				 		continue;
				 	}
				}
			}
			catch(\Exception $e)
			{
				$data[] = array($server_name=>$e->getMessage());
				if(!empty($container))
					$container->get("logger")->err($server_name." Memcache Conn error:".$e->getMessage());
				continue;
			}
	  		$r = $memcache->flush();
	  		$memcache->close();
	  		$data[] = array($server_name=>$r);
	  	}
	  	return $data;
	}

	public static function stat($container=null)
	{
		$data=array();
	  	$memcached_servers =array("localhost");
	    try {
	    	if(!empty($container) )
	    	{
	        	$memcached_servers = $container->getParameter("memcached_servers");
	        	if (!empty($memcached_servers))
	            	$memcached_servers = json_decode($memcached_servers, true);
	    	}
	    } 
	    catch (\Exception $e)
	    {
	    }
	    //同步数据到多台缓存服务器
	    $server_name = "localhost"; //默认服务器
	    $server_port = 11211; //默认端口
	    for ($i=0; $i < count($memcached_servers); $i++) { 
		  	$memcache = new Memcache;
		  	$server_inf = $memcached_servers[$i];
		  	if(is_string($server_inf))
		  	{
		  		$server_name = $server_inf;
		  	}
		  	else
		  	{
		  		$server_name = $server_inf[0];
		  		if(count($server_inf)==2) 
		  		{
		  			$server_port = $server_inf[1];
		  		}
		  	}
		  	$memcache->addServer($server_name, $server_port);
		  	$s = $memcache->getServerStatus($server_name, $server_port);
		  	if(empty($s))
		  	{
		  		$data[]=array($server_name=>'-1');
		  		continue;
		  	}
		  	try
		  	{
				if(!$memcache->connect($server_name, $server_port)){
					$data[]=array($server_name=>'-1');
					if(!empty($container))
					{
				 		$container->get("logger")->err($server_name." Memcache con't Conn!");	 	
				 		continue;
				 	}
				}
			}
			catch(\Exception $e)
			{
				$data[]=array($server_name=>$e->getMessage());
				if(!empty($container))
					$container->get("logger")->err($server_name." Memcache Conn error:".$e->getMessage());
				continue;
			}
	  		$status = $memcache->getStats();

			$percCacheHit=((real)$status ["get_hits"]/ (real)$status ["cmd_get"] *100);
        	$percCacheHit=round($percCacheHit,3);
        	$percCacheMiss=100-$percCacheHit; 

	  		$statusList = array();
	  		$statusList['版本号'] = $status['version'];
	  		$statusList['进程PID'] = $status['pid'];
	  		$statusList['本次运行时长'] = $status['uptime'];
	  		$statusList['缓存总项数'] = $status['total_items'];
	  		$statusList['当前连接数'] = $status['curr_connections'];
	  		$statusList['累积连接数'] = $status['total_connections'];
	  		$statusList['连接池总数'] = $status['connection_structures'];
	  		$statusList['总查询次数'] = $status['cmd_get'];
	  		$statusList['总写入次数'] = $status['cmd_set'];
	  		$statusList['命中成功次数'] = $status['get_hits'].'('.$percCacheHit.'%)';
	  		$statusList['命中失败次数'] = $status['get_misses'].'('.$percCacheMiss.'%)';
	  		$MBRead= (real)$status["bytes_read"]/(1024*1024); 
	  		$statusList['接收数据流量'] = $MBRead;
	  		$MBWrite=(real) $status["bytes_written"]/(1024*1024) ; 
	  		$statusList['发送数据大小'] = $MBWrite;
	  		$MBSize=(real) $status["limit_maxbytes"]/(1024*1024) ; 
	  		$statusList['最大容量限制'] = $MBSize;
	  		$data[]=array($server_name=>$statusList);
	  		$memcache->close();
	  	}
	  	return $data;
	}

}
