<?php
$opt =isset($_GET['opt']) ? $_GET['opt'] : "";
if($opt=='cfg')
{
	getConfig();
	exit;
}
else if($opt=='para')
{
	header('Content-type: text/json');
	getParameter();
	exit;
}
else if($opt=='init')
{
	header('Content-type: text/json');
	init();
	exit;
}
else
{
	//检查安装目录是否存在
	$ok = is_dir(__DIR__.'/install');
	if(!$ok)
	{
		mkdir(__DIR__.'/install');
		//进入安装界面
		in_install();
		exit;
	}
	$filepath = __DIR__.'/install/install.ini';
	//判断安装标志
	$ok=is_file($filepath) ;
	if(!$ok)
	{
		//进入安装界面
		in_install();
		exit;
	}
	//读取安装标识
	$file = file_get_contents($filepath);
	if(empty($file))
	{
		//进入安装界面
		in_install();
		exit;
	}
	if($file=='install=ok')
	{
		//进入主界面
		header("location: admin/theme/index.html");
		exit;
	}
}

function in_install()
{
	header("location: admin/theme/install.html");
}


function getConfig()
{
	if (!$iniPath = get_cfg_var('cfg_file_path')) {
    	$iniPath = '警告: 未找到配置文件 php.ini';
	}
	echo sprintf("php.ini 文件位置: %s<br><br>", $iniPath);

	if ('\\' == DIRECTORY_SEPARATOR) {
	    echo "*  (当前系统： Windows)<br>";
	}

	// mandatory
	echo_title("核心配置");
	check(version_compare(phpversion(), '5.3.2', '>='), sprintf(' PHP 版本 5.3.2及以上 (当前安装 %s )', phpversion()), '支持 PHP 5.3.2 及以上版本 (当前版本 '.phpversion(), true);
	check(ini_get('date.timezone'), '"date.timezone" 已配置', '请在php.ini中配置 "date.timezone" (如：Europe/Paris)', true);
	check(is_writable(__DIR__.'/../app/cache'), sprintf(' app/cache/ 目录可写'), '请更改目录 app/cache/ 的权限为可写', true);
	check(is_writable(__DIR__.'/../app/logs'), sprintf(' app/logs/ 目录可写'), '请更改目录 app/logs/ 的权限为可写', true);
	check(function_exists('json_encode'), 'json_encode() 功能函数有效', '需要安装 json 扩展', true);
	check(class_exists('SQLite3') || in_array('sqlite', PDO::getAvailableDrivers()), 'SQLite3 or PDO_SQLite 扩展已安装', '需要安装 SQLite3 or PDO_SQLite 扩展.', true);
	check(function_exists('session_start'), 'session_start() 功能函数有效', '需要安装 session 扩展', true);

	// warnings
	echo_title("Optional checks");
	check(class_exists('DomDocument'), 'PHP-XML 模块已安装', '需要安装 php-xml 模块', false);
	check(function_exists('token_get_all'), ' token_get_all() 功能函数有效', '需要安装 Tokenizer 扩展', false);
	check(function_exists('mb_strlen'), ' mb_strlen() 功能函数有效', '需要安装 mbstring 扩展', false);
	check(function_exists('iconv'), 'iconv() 功能函数有效', '需要安装 iconv 扩展', false);
	check(function_exists('utf8_decode'), 'utf8_decode()功能函数有效', '需要安装 XML 扩展', false);
	check(function_exists('pcntl_fork'),'多纯程功能有效', '需要安装 maintainer-zts 扩展', true);
	echo_title("Doctrine配置项检查");

	check(class_exists('PDO'), ' PDO 已成功安装', '安装PDO（必须）', true);
	if (class_exists('PDO')) {
	    $drivers = PDO::getAvailableDrivers();
	    check(count($drivers), ' PDO 已安装驱动: '.implode(', ', $drivers), '安装 PDO 驱动 (必须)');
	}

	echo_title("Memcache配置项检查");
	check(class_exists('Memcache'), ' Memcache 安装', '安装Memcache（必须）', true);
	echo_title("Mongo配置项检查");
	check(class_exists('Mongo'), ' Mongo 安装', '安装Mongo（必须）', true);
	echo_title("imagick配置项检查");
	check(class_exists('Imagick'), ' imagick 安装', '安装imagick（必须）', true);
	echo_title("ffmpeg配置项检查");
	check(extension_loaded('ffmpeg'), ' ffmpeg 安装', '安装ffmpeg（建议）', false);
	echo_title("mcrypt配置项检查");
	check(function_exists('mcrypt_cbc'), ' mcrypt 安装', '安装mcrypt（必须）', true);
	echo_title("ZipArchive配置项检查");
	check(class_exists('ZipArchive'), ' ZipArchive 安装', '安装ZipArchive（必须）', true);
	echo_title("Soap配置项检查");
	check(class_exists('SoapClient'), ' SoapClient 安装', '可能需要安装SoapClient', false);
	echo_title("LDAP配置项检查");
	check(function_exists('ldap_connect'), ' LDAP 安装', '可能需要安装LDAP', false);
	echo_title("系统参数配置项检查");
	$path = __DIR__.'/../app/config/parameters.ini';
	echo "parameters文件位置：".(file_exists($path)? $path : "<strong style=\'color:red\'>ERROR </strong>");

	echo "<br>            *** 完整的配置信息参考<a target=_blank href='../../a.php'><strong style='color:red'>phpinfo</strong></a> ***<br>";
}

function getParameter()
{
	try
	{
		$path = __DIR__.'/../app/config/parameters.ini';
		$content=array('[parameters]');
		$tmp=$_GET['server_host'];
		$imTmp = $_GET['im_host'];
		if(empty($tmp) || empty($imTmp))
		{
			throw new Exception("服务器地址不能为空，请检查并重新填写"); 
		}
		if(!empty($imTmp))
		{
			if(strpos('http', $imTmp)===false)
			{
				$imTmp = 'http://'.$imTmp;
			}
			$urls = parse_url($imTmp);
			$content[] = 'ejabberd-server-http = "'.$urls['scheme'].'://'.$urls['host'].':5280"';
			$content[] = 'fafa_webim_url = "'.$tmp.'"';
			$content[] = 'open_api_url = "'.$tmp.'"';
			$content[] = 'FILE_WEBSERVER_URL = "'.$tmp.'/getfile/"';
			
			$content[] = 'FAFA_REG_JID_URL = "'.($urls['scheme'].'://'.$urls['host']).':9527"';
			$content[] = 'fafa_appcenter_url = "'.$tmp.'"';
		}
		if(!empty($tmp=$_GET['mongodb_host']))
		{
			$mongodb_auth = $_GET['mongodb_auth'];
			$mongodb_conn_str = '';
			//测试数据库连接
			//$conn=new Mongo(“xiaocai.loc:10086″); #连接指定端口远程主机
			//$conn=new Mongo(“mongodb://sa:123@localhost”); #带用户名密码
			if($mongodb_auth==="1")
			{
				$mongodb_conn_str = 'mongodb://'.$_GET['mongodb_user'].':'.$_GET['mongodb_pwd'].'@'.$tmp.':'.$_GET['mongodb_port'];
			}
			else
			{
				$mongodb_conn_str = 'mongodb://'.$tmp.':'.$_GET['mongodb_port'];
			}
			try
			{
				$conn=new Mongo($mongodb_conn_str,array('timeout'=>5000,'connect'=>true));
				if(empty($conn))
				{
					throw new Exception("Mongodb连接失败，请检查并重新配置"); 
				}
				$mongodb_database = $_GET['mongodb_name'];
				if(empty($mongodb_database))
				{
					throw new Exception("Mongo数据库未设置，请检查并重新配置");
				}
				//创建数据库
				$conn->selectDB($mongodb_database);
				$conn->close(); 
			}
			catch(Exception $e)
			{
				if(isset($conn) && !empty($conn)) $conn->close(); 
				throw new Exception("Mongo数据库连接失败:".$e->getMessage());
			}
			$content[] = 'mongodb_server = '.$mongodb_conn_str;
			$content[] = 'mongodb_default_database = "'.$_GET['mongodb_name'].'"';
			$content[] = 'mongodb_username = "'.$_GET['mongodb_user'].'"';
			$content[] = 'mongodb_password = "'.$_GET['mongodb_pwd'].'"';
		}
		if(!empty($tmp=$_GET['db_host']))
		{
			$port = $_GET['db_port'];
			$sns_db_user = $_GET['sns_db_user'];
			$sns_db_pwd = $_GET['sns_db_pwd'];
			$sns_db_dbname = $_GET['sns_db_dbname'];
			$im_db_user = $_GET['im_db_user'];
			$im_db_pwd = $_GET['im_db_pwd'];
			$im_db_dbname = $_GET['im_db_dbname'];			
			$content[] = 'database_driver = "pdo_mysql"';
			$content[] = 'database_host = "'.$tmp.'"';
			$content[] = 'database_port = "'.$port.'"';
			$content[] = 'database_name = "'.$sns_db_dbname.'"';
			$content[] = 'database_user = "'.$sns_db_user.'"';
			$content[] = 'database_password = "'.$sns_db_pwd.'"';

			$content[] = 'database_driver_im = "pdo_mysql"';
			$content[] = 'database_host_im = "'.$tmp.'"';
			$content[] = 'database_port_im = "'.$port.'"';
			$content[] = 'database_name_im = "'.$im_db_dbname.'"';
			$content[] = 'database_user_im = "'.$im_db_user.'"';
			$content[] = 'database_password_im = "'.$im_db_pwd.'"';
			//测试数据库连接
			$conn = mysql_connect($tmp.':'.$port, $sns_db_user, $sns_db_pwd,true);
			if (!$conn)
			{
			    throw new Exception("WEB接口服务数据库连接失败，请重新配置"); 
			}
			mysql_select_db($sns_db_dbname, $conn);
			mysql_close($conn);
			$conn = mysql_connect($tmp.':'.$port, $im_db_user, $im_db_pwd,true);
			if (!$conn)
			{
			    throw new Exception("IM数据库连接失败，请重新配置"); 
			}
			mysql_select_db($im_db_dbname, $conn);
			mysql_close($conn);
		}
		if(!empty($tmp=$_GET['mailer_transport']))
		{
			$content[] = 'mailer_transport = "'.$tmp.'"';
			$content[] = 'mailer_host = "'.$_GET['mailer_host'].'"';
			$content[] = 'mailer_user = "'.$_GET['mailer_user'].'"';
			$content[] = 'mailer_password = "'.$_GET['mailer_password'].'"';
		}
		$content[] = 'memcached_servers="[[\"127.0.0.1\",11211]]"';
		$content[] = 'locale="zh"';
		$content[] = 'SMS_ACT=""';
		$content[] = 'SMS_PWD=""';
		$content[] = 'deploy_mode="E"';
		$content[] = 'ssoauthmodule="WefafaAuth"';
		$content[] = 'ENO="100001"';
		$content[] = 'edomain="'.$_GET['server_domain'].'"';		
        $content[] = 'secret="335a052790228abbc6ea61b49ie9280adfaow808"';
        if(file_exists($path))
        {
        	//先备份
        	@copy($path,$path.'_1');
        }
	    if (!$handle = fopen($path, 'w')) { 
	        throw new Exception("配置文件[{$path}]打开失败，请检查文件是否有效!"); 
	    } 
	    if (!fwrite($handle,implode("\n", $content))) { 
	        throw new Exception("配置文件[{$path}]写入失败，请检查文件是否有效或权限是否正确!"); 
	    } 
	    fclose($handle);
	    
	    $dir = explode("web", __DIR__);
	    //更改线程执行脚本
	    $str = "php {$dir[0]}app/threads $1 $2 $3 $4";
	    $command = $dir[0].'threads.sh';
		if (!$handle = fopen($command, 'w+')) {
		    throw new Exception("脚本文件[{$command}]打开失败，请检查文件是否有效!"); 
		}
		if (!fwrite($handle,$str)) {
		    throw new Exception("脚本文件[{$command}]写入失败，请检查文件是否有效或权限是否正确!"); 
		}
		fclose($handle);
	    //发布配置
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
	    $data=array("returncode"=>"0000",'data'=>$data);
	}
	catch(Exception $e)
	{
		$data=array("returncode"=>"9999","msg"=>$e->getMessage());
	}
	echo json_encode($data);
}

function init()
{
	try
	{
		$path = __DIR__.'/../app/config/parameters.ini';
		$data = parse_ini_file($path);
		if(empty($data)) throw new Exception("配置文件或内容损坏，请重新配置"); 
		$database_host = $data['database_host'];
		$database_port = $data['database_port'];
		$database_name = $data['database_name'];
		$database_user = $data['database_user'];
		$database_password = $data['database_password'];
		$database_host_im = $data['database_host_im'];
		$database_port_im = $data['database_port_im'];
		$database_name_im = $data['database_name_im'];
		$database_user_im = $data['database_user_im'];
		$database_password_im = $data['database_password_im'];

		$tmp = parse_url($data['ejabberd-server-http']);
		$host = $tmp["host"];
		$conn = mysql_connect($database_host.':'.$database_port, $database_user, $database_password,true);
		if (!$conn)
		{
			throw new Exception("WEB服务数据库连接失败，请重新配置"); 
		}
		mysql_select_db($database_name, $conn);
		$errmsg = mysql_error();
		if(!empty($errmsg))
		{
			throw new Exception("WEB数据库错误：".$errmsg); 
		}
	  	$sql = "SET NAMES 'utf8'";
	  	mysql_query($sql, $conn);
	  	mysql_query('delete from we_enterprise', $conn);
	  	mysql_query('delete from we_circle', $conn);
	  	mysql_query('delete from we_appcenter_apps', $conn);
	  	mysql_query('delete from we_sys_param', $conn);
	  	mysql_query('delete from we_micro_account', $conn);
	  	mysql_query('delete from we_department', $conn);
	  	//初始化企业 数据 	
	  	$eno = $data['ENO'];  	
	  	$edomain = mysql_real_escape_string($_GET['en_domain']);
	  	$ename = mysql_real_escape_string($_GET['en_name']);
	  	$admin = mysql_real_escape_string($_GET['admin_account']);
	  	$sql = 'insert into we_enterprise(eno,edomain,ename,eshortname,sys_manager)values(\''.$eno.'\',\''.($edomain).'\',\''.($ename).'\',\''.($ename).'\',\''.($admin).'\')';
    	$result = mysql_query($sql, $conn);
    	$sql = 'insert into we_department(eno,dept_id,dept_name,parent_dept_id,fafa_deptid)values(\''.$eno.'\',\'v'.$eno.'\',\''.($ename).'\',\'v'.$eno.'\',\'v'.$eno.'\')';
    	$result = mysql_query($sql, $conn);
	  	$sql = 'insert into we_circle(circle_id,circle_name,create_staff,create_date,manager,enterprise_no,network_domain)values(\'10000\',\''.$ename.'\',\''.$admin.'\',now(),\''.$admin.'\',\''.$eno.'\',\''.$edomain.'\')';
    	$result = mysql_query($sql, $conn); 
    	$sql = 'insert into we_sys_param(param_name,param_value)values(\'ejabberd_server_path\',\'\'),(\'imserver\',\''.$host.':5222\'),(\'mobile_active_code\',\'888888\')';    	
		$result = mysql_query($sql, $conn);
		$sql = 'insert into we_appcenter_apps(appid,appkey,appname,state,appdeveloper,apptype)values(\'35dc24ba28ee06ce8741259c19fbc8e8\',\'61xU54Wx\',\'实时通知\',1,\''.$eno.'\',\'99\')';
		$result = mysql_query($sql, $conn);
		mysql_close($conn);
		
		$conn_im = mysql_connect($database_host_im.':'.$database_port_im, $database_user_im, $database_password_im,true);
		if (!$conn_im)
		{
			throw new Exception("IM服务数据库连接失败，请重新配置"); 
		}
		mysql_select_db($database_name_im, $conn_im);
		$errmsg = mysql_error();
		if(!empty($errmsg))
		{
			throw new Exception("IM数据库错误：".$errmsg); 
		}
	  	$sql = "SET NAMES 'utf8'";
	  	mysql_query($sql, $conn_im);
	  	mysql_query('delete from cluster_node', $conn_im);	
	  	mysql_query('delete from cluster_node_media', $conn_im);
	  	mysql_query('delete from users', $conn_im);	
	  	mysql_query('delete from im_employee', $conn_im);
	  	mysql_query('delete from im_base_dept', $conn_im);	
		$sql = 'insert into cluster_node(nodename,nodetype,isenabled,isstart,ip,start_time)values(\'ejabberd@'.$host.'\',0,1,1,\''.$host.'\',now())';
		$result = mysql_query($sql, $conn_im);
		$sql = 'insert into cluster_node_media(nodename,serv_type,port,extern_ip,extern_port)values(\'ejabberd@'.$host.'\',\'fileproxy\',7777,\''.$host.'\',7777),(\'ejabberd@'.$host.'\',\'tcp\',4478,\''.$host.'\',4478),(\'ejabberd@'.$host.'\',\'udp\',4478,\''.$host.'\',4478)';
		$result = mysql_query($sql, $conn_im);	
		$sql = 'insert into im_base_dept(deptid,deptname,pid,path,noorder)values(\'v'.$eno.'\',\''.$ename.'\',\'-10000\',\'/-10000/v'.$eno.'/\',0)';
		$result = mysql_query($sql, $conn_im);
		$sql = 'insert into im_sys_seq(table_name,col_name,name,curr_value,step)values(\'im_base_dept\',\'deptid\',\'im_base_dept_deptid\',100001,1)';
		$result = mysql_query($sql, $conn_im);		
		mysql_close($conn_im);		
		//初始化一个部门
		$url = $data['open_api_url'].'/api/http/exec_dataaccess?module=ApiHR&action=org_add';
		$url .= '&deptname=体验部门&deptid=&eno='.$eno.'&pid=';
		$re =json_decode(getUrlContent($url),true);
		//初始化管理员帐号
		$admininfo = array();
		$admininfo['eno'] = $eno;
		$admininfo['account'] = $admin;
		$admininfo['deptid'] = '';
		$admininfo['realName'] = $_GET['admin_name'];
		$admininfo['passWord'] = $_GET['admin_pwd'];
		$admininfo['duty'] = '系统管理员';
		$admininfo['mobile'] = '';
		$url = $data['open_api_url'].'/api/http/exec_dataaccess?module=ApiHR&action=staff_add&staffinfo=';
		$url .= json_encode(array($admininfo));
		$re =json_decode(getUrlContent($url),true);
		
		//初始化默认公众号
		$url = $data['open_api_url'].'/api/http/exec_dataaccess?openid='.$admin.'@'.$edomain.'&module=service&action=register_service&params=';
		$params=array();
		$params['concern_approval']='0';
		$params['deptid']=array('v'.$eno);
		$params['desc']='用于发布公司内部管理规章制度';
		$params['login_account']='';
		$params['micro_id']='';
		$params['name']='规章制度';
		$re =json_decode(getUrlContent($url.json_encode($params)),true);
		$params['name']='企业新闻';
		$params['desc']='用于发布公司事件';
		$re =json_decode(getUrlContent($url.json_encode($params)),true);
		$filepath = __DIR__.'/install/install.ini';
		$ft=fopen($filepath,'w+');
		fwrite($ft,"install=ok");
		fclose($ft);		
		$data=array("returncode"=>"0000","msg"=>'');
	}
	catch(Exception $e)
	{
		if(isset($conn) && !empty($conn)) mysql_close($conn);
		$filepath = __DIR__.'/install/install.ini';
		$ft=fopen($filepath,'w+');
		fwrite($ft,$e);
		fclose($ft);
		$data=array("returncode"=>"9999","msg"=>$e->getMessage());
	}
	echo json_encode($data);

}

function getUrlContent($url,$logger=null) 
{
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_REFERER, 'http://127.0.0.1');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT,10);
      curl_setopt($ch, CURLOPT_FORBID_REUSE, true); //处理完后，关闭连接，释放资源
      $content = curl_exec($ch);
      return $content;
}
/**
 * Checks a configuration.
 */
function check($boolean, $message, $help = '', $fatal = false)
{
    echo $boolean ? "  OK        " : sprintf("[[%s]] ", $fatal ? ' <strong style=\'color:red\'>ERROR </strong>' : 'WARNING');
    echo sprintf("$message%s<br>", $boolean ? '' : ': 未安装');

    if (!$boolean) {
        echo "            *** $help ***<br>";
        if ($fatal) {
            exit("你必须解决这个问题，然后重新检查配置.<br>");
        }
    }
}

function echo_title($title)
{
    echo "<br>======================= $title =======================<br><br>";
}
