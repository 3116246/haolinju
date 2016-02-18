<?php
namespace Justsy\BaseBundle\Management;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Common\Utils;
class EnoParamManager
{
	private $da=null;
	private $logger=null;
	private $user=null;
	protected $container;
	private $circle_create_count="circle_create_count";//创建外部圈子
	private $circle_join_count="circle_join_count";//加入圈子
	private $group_create_count="group_create_count";//创建群组
	private $group_join_count="group_join_count";//加入群组
	private $meeting_create_count="meeting_create_count";//创建会议室
	private $micro_external_count="micro_external_count";//创建外部公众号
	private $micro_internal_count="micro_internal_count";//创建内部公众号
	private $circle_member_count="circle_member_count";//圈子成员数
	private $meeting_member_count="meeting_member_count";//会议组成员数
	private $group_member_count="group_member_count";//群组成员数
	private $micro_app_count="micro_app_count";//微应用
	
	public function __construct($dataaccess,$log=null,$container=null)
	{
		$this->da=$dataaccess;
		$this->logger=$container->get('logger');
		$this->container=$container;
	}
	public function getInstance($container)
	{
	  	$db = $container->get("we_data_access");	  	
	  	$logger = $container->get("logger");
	  	return new self($db,$logger,$container);
	}

	public function getEjabberdParam($paraObj)
	{
		$syspara =new \Justsy\BaseBundle\DataAccess\SysParam($this->container);
        $ejabberd_server_path = $syspara->GetSysParam('ejabberd_server_path','');
        if(!empty($ejabberd_server_path))
        {
        	//加载im服务器参数配置
        	$data = array();
        	$cfgPath = $ejabberd_server_path.'/conf/ejabberdctl.cfg';
        	$handle = @fopen($cfgPath, "r");
			if ($handle) {
			    while (($buffer = fgets($handle)) !== false) {
			    	$buffer = trim($buffer);
			    	if(empty($buffer) || substr($buffer, 0,1)=='#')
			    	{
			        	//$data[] = $buffer;
			        }
			        else
			        {
			        	$ps =explode('=', $buffer);
			        	$data[] =array('param_name'=>$ps[0],'param_value'=>$ps[1],'type'=>'ctl');
			        }
			    }
			    if (!feof($handle)) {
			        return Utils::WrapResultError($cfgPath.'文件上调用fgets函数失败！');
			    }
			    fclose($handle);			    
			}
			else
			{
				return Utils::WrapResultError($cfgPath.'文件打开失败，请检查参数ejabberd_server_path设置及文件是否存在！');
			}
			$cfgPath = $ejabberd_server_path.'/conf/ejabberd.cfg';
			$cfgList = $this->parseEjabberdCfg($cfgPath); 
			if($cfgList['returncode']!='0000')
			{
				return Utils::WrapResultError($cfgList['msg']);
			}
			$finds = array('loglevel','hosts','auth_method','odbc_server','language','listen','modules');
			foreach ($finds as $key => $value) {
				$cfg_para_v=$this->ejabberdcfg_val($cfgList['data'],$value);
				if($value=='modules')
				{
					$cfg_para_v =substr(rtrim(ltrim($cfg_para_v,'['),']'),0,-2);
					$cfg_para_v = explode(']},', $cfg_para_v);
				}
				$data[] =array('param_name'=>$value,'param_value'=>$cfg_para_v,'type'=>'');
			}			
			return  Utils::WrapResultOK($data);
        }
        else
        {
        	return Utils::WrapResultError('获取IM参数配置失败，请先正确设置参数ejabberd_server_path');
        }
	}
	//获取系统参数
	public function getSysparam($paraObj)
	{
		$sql = 'select *,\'db\' type from we_sys_param';
		$ds = $this->da->Getdata('t',$sql,array());
		$dir = explode("src", __DIR__);
		$path = $dir[0].'app/config/parameters.ini';
		$data = parse_ini_file($path);
		foreach ($data as $key => $value) {
			$ds['t']['rows'][]=array('param_name'=>$key ,'param_value'=> $value,'type'=>'');
		}
		return Utils::WrapResultOK($ds['t']['rows']);
	}

	public function saveEjabberdparam($paraObj)
	{
		$syspara =new \Justsy\BaseBundle\DataAccess\SysParam($this->container);
        $ejabberd_server_path = $syspara->GetSysParam('ejabberd_server_path','');
		$list=$paraObj['list'];
        if(!empty($ejabberd_server_path))
        {
        	$ejabberd_server_path = rtrim($ejabberd_server_path,'/');
        	//加载im服务器参数配置
        	$ctlcfgdata = array();
        	$ctlPath = $ejabberd_server_path.'/conf/ejabberdctl.cfg';
        	$handle = @fopen($ctlPath, "r");
			if ($handle) {
			    while (($buffer = fgets($handle)) !== false) {
			    	$buffer = trim($buffer);
			    	if(empty($buffer) || substr($buffer, 0,1)=='#')
			    	{
			        	//$data[] = $buffer;
			        }
			        else
			        {
			        	$ps =explode('=', $buffer);
			        	$ctlcfgdata[$ps[0]] =$ps[1];
			        }
			    }
			    if (!feof($handle)) {
			        return Utils::WrapResultError($ctlPath.'文件上调用fgets函数失败！');
			    }
			    fclose($handle);			    
			}
			else
			{
				return Utils::WrapResultError($ctlPath.'文件打开失败，请检查参数ejabberd_server_path设置及文件是否存在！');
			}
			$cfgPath = $ejabberd_server_path.'/conf/ejabberd.cfg';
			$cfgList = $this->parseEjabberdCfg($cfgPath); 
			if($cfgList['returncode']!='0000')
			{
				return Utils::WrapResultError($cfgList['msg']);
			}
			$writeCfgFlag=false;
			$nodenameChange = false;//是否更改了im节点名称
			$nodenameOldValue = ''; //原来的节点名称值
			foreach ($list as $key => $value) {
				if(strpos($key, 'ctl_')===0 )
				{
					if($key==='ctl_ERLANG_NODE')
					{
						$nodenameChange=$value;
						$nodenameOldValue = $ctlcfgdata['ERLANG_NODE'];
					}
					$ctlcfgdata[str_replace('ctl_', '', $key)] = $value;
					continue;
				}
				$writeCfgFlag=true;
				$this->ejabberdcfg_val($cfgList['data'],substr($key,1),$value);
			}
			if(count($ctlcfgdata)>0)
			{				
				$contentdata=array();
				foreach ($ctlcfgdata as $key => $value) {
					$contentdata[] = $key.'='.$value;					
				}
				if (!$handle = fopen($ctlPath, 'w+')) { 
			        return Utils::WrapResultError('打开配置文件'.$ctlPath.'失败，请检查文件权限是否正确！');
			    } 
			    if (!fwrite($handle,implode("\n", $contentdata))) { 
			        return Utils::WrapResultError('写入配置文件'.$ctlPath.'失败，请检查文件权限是否正确！');
			    } 
			    fclose($handle);
			    if($nodenameChange!==false)
			    {
			    	//更改节点地址，同步更新数据库配置
			    	$da_im=$this->container->get('we_data_access_im');
			    	$newip = explode('@', $nodenameChange);
			    	$newip = $newip[1];
			    	$da_im->ExecSQL('update cluster_node set nodename=?,ip=? where nodename=?',array((string)$nodenameChange,(string)$newip,(string)$nodenameOldValue));
			    	$da_im->ExecSQL('update cluster_node_media set nodename=?,extern_ip=? where nodename=?',array((string)$nodenameChange,(string)$newip,(string)$nodenameOldValue));
			    }
			}
			if($writeCfgFlag)
			{
				if (!$handle = fopen($cfgPath, 'w+')) { 
			        return Utils::WrapResultError('打开配置文件'.$cfgPath.'失败，请检查文件权限是否正确！');
			    } 
			    $this->logger->err("ejabberd_server_path:".$ejabberd_server_path);
			    $cfgContent = implode(".\n", $cfgList['data']);
			    $cfgContent = preg_replace('/"\/.*?\/conf\//', '"'.$ejabberd_server_path.'/conf/', $cfgContent);
			    $this->logger->err("cfgContent:".$cfgContent);
			    if (!fwrite($handle,$cfgContent)) { 
			        return Utils::WrapResultError('写入配置文件'.$cfgPath.'失败，请检查文件权限是否正确！');
			    } 
			    fclose($handle);	
		    }		
			return  Utils::WrapResultOK('');
        }
        else
        {
        	return Utils::WrapResultError('获取IM参数配置失败，请先正确设置参数ejabberd_server_path');
        }		
		return Utils::WrapResultOK('');
	}

	public function saveSysparam($paraObj)
	{
		$syspara =new \Justsy\BaseBundle\DataAccess\SysParam($this->container);
		$list=$paraObj['list'];
		$content=array('[parameters]');
		$sqls=array();
		$dir = explode("src", __DIR__);
		$path = $dir[0].'app/config/parameters.ini';
		$data = parse_ini_file($path);

		$needUpdateCache=array();
		foreach ($list as $key => $value) {
			if(strpos($key, 'db_')===0 )
			{
				$key = str_replace('db_', '', $key);
				$sqls[] = 'update we_sys_param set param_value=\''.$value.'\' where param_name=\''.$key.'\'';
				$needUpdateCache[] = $key;				
				continue;
			}
			$data[substr($key,1)] = $value;
		}
		if(count($sqls)>0)
		{
			try
			{
				$this->da->ExecSQLs($sqls,array());
				foreach ($needUpdateCache as $key => $value) {
					$syspara->GetSysParam($value,'',true);//更新缓存
				}
			}
			catch(\Exception $e)
			{
				$this->writelog($e);
			}
		}
		if(count($sqls)==count($list))
		{
			return Utils::WrapResultOK('');
		}
		foreach ($data as $key => $value) {
			$content[] = $key.'= "'.$value.'"';
		}
		if (!$handle = fopen($path, 'w+')) { 
	        return Utils::WrapResultError('打开参数文件失败！');
	    } 
	    if (!fwrite($handle,implode("\n", $content))) { 
	        return Utils::WrapResultError('写入参数文件失败！');
	    } 
	    fclose($handle);
	    //如果修改了im数据连接，同步更改ejabberd配置
	    if(isset($data['database_host_im']) ||isset($data['database_port_im'])||isset($data['database_name_im'])||isset($data['database_user_im'])||isset($data['database_password_im']))
	    {

	    }
	    try
	    {
		    //发布php		    
		    $str = "php {$dir[0]}app/console cache:clear --env=prod --no-debug\nchmod -R 777 {$dir[0]}app";
		    
		    $command = $dir[0].'clear_cache_prod.sh';
			if (!$handle = fopen($command, 'w+')) {
			    throw new Exception("脚本文件[{$command}]打开失败，请检查文件是否有效!"); 
			}
			if (!fwrite($handle,$str)) {
			    throw new Exception("脚本文件[{$command}]写入失败，请检查文件是否有效或权限是否正确!"); 
			}
			fclose($handle);
	        $data=shell_exec($command);
	        if(strpos($data, 'Clearing the cache for the prod environment with debug false')===false)
	        {
	        	throw new Exception($data);
	        }
    	}
    	catch(\Exception $e)
    	{
    		return Utils::WrapResultError('发布系统错误：'.$e->getMessage());
    	}
		return Utils::WrapResultOK('');
	}

	private function parseEjabberdCfg($cfgPath)
	{
		$data = array();
        $handle = @fopen($cfgPath, "r");
		if ($handle) {
			    while (($buffer = fgets($handle)) !== false) {
			    	$buffer = trim($buffer);
			    	if(empty($buffer) || $buffer{0}=='%')
			    	{
			        	//$data[] = $buffer;
			        }
			        else
			        {
			        	$data[] = $buffer{strlen($buffer)-1} =='.' ? rtrim($buffer,'.').'\n' : $buffer;
			        }
			    }
			    if (!feof($handle)) {
			        return Utils::WrapResultError($cfgPath.'文件上调用fgets函数失败！');
			    }
			    fclose($handle);
			    $formatContent = implode('', $data);
			    $data = explode('\n', $formatContent);
			    return Utils::WrapResultOK($data);			    
		}
		else
		{
			return Utils::WrapResultError($cfgPath.'文件打开失败，请检查参数ejabberd_server_path设置及文件是否存在！');
		}
	}

	private function ejabberdcfg_val(&$cfgdata,$key,$setvalue=null)
	{
		$key = '{'.$key.',';
		foreach ($cfgdata as $pos => $value) {
			if(strpos($value, $key)===false) continue;
			if($setvalue===null)
			{
				return ltrim(substr($value,0,-1),$key);
			}
			$cfgdata[$pos] = $key.$setvalue.'}';
		}
	}

	protected function writelog($e)
	{
		if(!empty($this->logger))
		{
			$this->logger->err($e);
		}
	}
}
?>