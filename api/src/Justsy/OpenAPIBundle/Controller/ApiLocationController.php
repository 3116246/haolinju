<?php

namespace Justsy\OpenAPIBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Common\SendMessage;
use Justsy\BaseBundle\Management\Staff;
use Justsy\BaseBundle\Management\Dept;
use Justsy\OpenAPIBundle\Controller\ApiController;

class ApiLocationController extends Controller
{
	//新增通知接口
	public function saveAction()
	{
		$da = $this->get("we_data_access");
		$da_im = $this->get('we_data_access_im');
		$request = $this->getRequest();
		//访问权限校验
		$api = new ApiController();
      	$api->setContainer($this->container);

    	$isWeFaFaDomain = $api->checkWWWDomain(); 
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $api->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   	$re = array("returncode"=>"9999");
			    $re["code"]="err0105";
    	   	   	$re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
 				return $this->responseJson($request,$re);
    	   }
    	}
		
		$openid = $request->get("openid"); 
		$staffinfo = new Staff($da,$da_im,$openid,$this->get("logger"),$this->container);
		$staffdata = $staffinfo->getInfo();
		if(empty($staffdata))
		{
			$result = Utils::WrapResultError("无效操作帐号");
			return $this->responseJson($request,$result);
		}
		$location = $request->get('location');
		if(empty($location))
		{
			$result = Utils::WrapResultError("无效位置信息");
			return $this->responseJson($request,$result);	
		}
		$location = explode(',', $location);
		$sql = 'insert into t_module_location(staff,ctime,x,y,address)values(?,now(),?,?,?)';
		$para = array();
		$para[] = (string)$staffdata["login_account"];
		$para[] = (string)$location[0];
		$para[] = (string)$location[1];
		$para[] = isset($location[2]) ? (string)$location[2] : '';
		$da->ExecSQL($sql,$para);
		return $this->responseJson($request,Utils::WrapResultOK(''));		
	}

	//开始收集指定帐号的当前位置信息
	public function startCollectAction()
	{
		$da = $this->get("we_data_access");
		$da_im = $this->get('we_data_access_im');
		$request = $this->getRequest();
		//访问权限校验
		$api = new ApiController();
      	$api->setContainer($this->container);

    	$isWeFaFaDomain = $api->checkWWWDomain(); 
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $api->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   	$re = array("returncode"=>"9999");
			    $re["code"]="err0105";
    	   	   	$re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
 				return $this->responseJson($request,$re);
    	   }
    	}
		
		$openid = $request->get("openid"); 
		$staffinfo = new Staff($da,$da_im,$openid,$this->get("logger"),$this->container);
		$staffdata = $staffinfo->getInfo();
		if(empty($staffdata))
		{
			$result = Utils::WrapResultError("无效操作帐号");
			return $this->responseJson($request,$result);
		}
		$to = $request->get("to"); 
		if(empty($to))
		{
			$result = Utils::WrapResultError("请设置位置发送者帐号");
			return $this->responseJson($request,$result);			
		}
		$to = $staffinfo->getStaffInfo($to);
		if(empty($to))
		{
			$result = Utils::WrapResultError("无效的位置发送者帐号");
			return $this->responseJson($request,$result);
		}		
		try
		{
			//判断数据表是否有效
			$sql = 'CREATE TABLE  if not exists `t_module_location` (`staff` varchar(50) NOT NULL,`ctime` datetime,`x` varchar(32) DEFAULT \'\',`y` varchar(32) DEFAULT \'\',`address` varchar(255) DEFAULT \'\', PRIMARY KEY (`staff`,`ctime`)) ENGINE=InnoDB DEFAULT CHARSET=utf8';
			$da->ExecSQL($sql,array());
			$sql = 'CREATE TABLE  if not exists `t_module_location_monitor` (eno varchar(10),`staff` varchar(50) NOT NULL,`login_account` varchar(50) NOT NULL,`state` int,`s_h` varchar(32) DEFAULT \'\',`e_h` varchar(32) DEFAULT \'\',jiondate datetime, PRIMARY KEY (`staff`)) ENGINE=InnoDB DEFAULT CHARSET=utf8';
			$da->ExecSQL($sql,array());
			$sql = 'insert into t_module_location_monitor(eno,login_account,staff,state,s_h,e_h,jiondate)values(?,?,?,1,?,?,now())';
			try
			{
				$da->ExecSQL($sql,array((string)$to['eno'],(string)$to['login_account'],(string)$to['jid'],'9:00','18:00'));
			}
			catch(\Exception $e)
			{
				$sql = 'update t_module_location_monitor set state=1 where staff =?';
				$da->ExecSQL($sql,array((string)$to['jid']));
			}
		}
		catch(\Exception $e)
		{
			return Utils::WrapResultError($e->getMessage());
		}
		$speed = $request->get("speed"); 
		if(empty($speed)) $speed = 600;  //默认采集间隔10分钟
		$senddata = array('opt'=>'start','speed'=>(int)$speed,'time_area'=>array('start'=>'9:00','end'=>'18:00'));
		$msg = Utils::WrapMessage('sendlocation',$senddata,array());
		//$msgxml = Utils::WrapMessageXml($this->module["jid"],$msg,'sendlocation-id');
		$api->sendMsg("",$to['jid'],'sendlocation',json_encode($msg));
		return $this->responseJson($request,Utils::WrapResultOk(""));
	}

	public function stopCollectAction()
	{
		$da = $this->get("we_data_access");
		$da_im = $this->get('we_data_access_im');
		$request = $this->getRequest();
		//访问权限校验
		$api = new ApiController();
      	$api->setContainer($this->container);

    	$isWeFaFaDomain = $api->checkWWWDomain(); 
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $api->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   	$re = array("returncode"=>"9999");
			    $re["code"]="err0105";
    	   	   	$re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
 				return $this->responseJson($request,$re);
    	   }
    	}
		
		$openid = $request->get("openid"); 
		$staffinfo = new Staff($da,$da_im,$openid,$this->get("logger"),$this->container);
		$staffdata = $staffinfo->getInfo();
		if(empty($staffdata))
		{
			$result = Utils::WrapResultError("无效操作帐号");
			return $this->responseJson($request,$result);
		}
		$to = $request->get("to"); 
		if(empty($to))
		{
			$result = Utils::WrapResultError("请设置位置发送者帐号");
			return $this->responseJson($request,$result);			
		}
		$to = $staffinfo->getStaffInfo($to);
		if(empty($to))
		{
			$result = Utils::WrapResultError("无效的位置发送者帐号");
			return $this->responseJson($request,$result);
		}
		try
		{
			$sql = 'update t_module_location_monitor set state=0 where staff=?';
			$da->ExecSQL($sql,array((string)$to['jid']));
		}
		catch(\Exception $e)
		{}
		$senddata = array('opt'=>'stop');
		$msg = Utils::WrapMessage('sendlocation',$senddata,array());
		//$msgxml = Utils::WrapMessageXml($this->module["jid"],$msg,'sendlocation-id');
		$api->sendMsg("",$to['jid'],'sendlocation',json_encode($msg));
		return $this->responseJson($request, Utils::WrapResultOk(""));
	}

	public function checkCollectAction()
	{
		$da = $this->get("we_data_access");
		$da_im = $this->get('we_data_access_im');
		$request = $this->getRequest();
		//访问权限校验
		$api = new ApiController();
      	$api->setContainer($this->container);

    	$isWeFaFaDomain = $api->checkWWWDomain(); 
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $api->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   	$re = array("returncode"=>"9999");
			    $re["code"]="err0105";
    	   	   	$re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
 				return $this->responseJson($request,$re);
    	   }
    	}
		
		$openid = $request->get("openid"); 
		$staffinfo = new Staff($da,$da_im,$openid,$this->get("logger"),$this->container);
		$staffdata = $staffinfo->getInfo();
		if(empty($staffdata))
		{
			$result = Utils::WrapResultError("无效操作帐号");
			return $this->responseJson($request,$result);
		}
		$sql ='select *,600 speed from t_module_location_monitor where staff=? and state=1';	
		$ds = $da->GetData('t',$sql,array((string)$staffdata['jid']));
		$result = Utils::WrapResultOK( $ds['t']['recordcount']==0 ? '' : $ds['t']['rows'][0]);
		return $this->responseJson($request,$result);
	}

	public function setTimeAction()
	{
		$da = $this->get("we_data_access");
		$da_im = $this->get('we_data_access_im');
		$request = $this->getRequest();
		//访问权限校验
		$api = new ApiController();
      	$api->setContainer($this->container);

    	$isWeFaFaDomain = $api->checkWWWDomain(); 
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $api->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   	$re = array("returncode"=>"9999");
			    $re["code"]="err0105";
    	   	   	$re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
 				return $this->responseJson($request,$re);
    	   }
    	}
		
		$openid = $request->get("openid"); 
		$staffinfo = new Staff($da,$da_im,$openid,$this->get("logger"),$this->container);
		$staffdata = $staffinfo->getInfo();
		if(empty($staffdata))
		{
			$result = Utils::WrapResultError("无效操作帐号");
			return $this->responseJson($request,$result);
		}
		$to = $request->get("to"); 
		if(empty($to))
		{
			$result = Utils::WrapResultError("无效的帐号");
			return $this->responseJson($request,$result);			
		}
		$to = $staffinfo->getStaffInfo($to);
		if(empty($to))
		{
			$result = Utils::WrapResultError("无效的帐号");
			return $this->responseJson($request,$result);
		}
		$s_h = $request->get("starthour");
		$e_h = $request->get("endhour");
		try
		{
			$sql = 'update t_module_location_monitor set s_h=?,e_h=? where staff=?';
			$da->ExecSQL($sql,array((string)$s_h,(string)$e_h,(string)$to['jid']));
		}
		catch(\Exception $e)
		{}
		$senddata = array('opt'=>'updatetime','time_area'=>array('start'=>$s_h,'end'=>$e_h));
		$msg = Utils::WrapMessage('sendlocation',$senddata,array());
		//$msgxml = Utils::WrapMessageXml($this->module["jid"],$msg,'sendlocation-id');
		$api->sendMsg("",$to['jid'],'sendlocation',json_encode($msg));
		return $this->responseJson($request, Utils::WrapResultOk(""));
	}

	public function queryAction()
	{
		$da = $this->get("we_data_access");
		$da_im = $this->get('we_data_access_im');
		$request = $this->getRequest();
		//访问权限校验
		$api = new ApiController();
      	$api->setContainer($this->container);

    	$isWeFaFaDomain = $api->checkWWWDomain(); 
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $api->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   	$re = array("returncode"=>"9999");
			    $re["code"]="err0105";
    	   	   	$re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
 				return $this->responseJson($request,$re);
    	   }
    	}
		
		$openid = $request->get("openid"); 
		$staffinfo = new Staff($da,$da_im,$openid,$this->get("logger"),$this->container);
		$staffdata = $staffinfo->getInfo();
		if(empty($staffdata))
		{
			$result = Utils::WrapResultError("无效操作帐号");
			return $this->responseJson($request,$result);
		}
		$staff = $request->get("staff");
		if(empty($staff))
		{
			$result = Utils::WrapResultError("未指定查询的帐号");
			return $this->responseJson($request,$result);
		}
		$staffdata = $staffinfo->getStaffInfo($staff);
		$now1 = $request->get("startdate");
		$now2 = $request->get("enddate");
		if(empty($now1) && empty($now2))
		{ 
			$now1 = date("Y-m-d",time()).' 00:00:01';
			$now2 = date("Y-m-d",time()).' 23:59:59';
		}
		$sql = 'select * from ( select l.staff,? nick_name,l.ctime,l.address,l.x,l.y,TRUNCATE(l.x,3) x1,TRUNCATE(l.y,3) y1  from t_module_location l  where l.staff=? and l.ctime between ? and ? order by ctime desc) a group by x1,y1';
		$ds = $da->GetData('t',$sql,array((string)$staffdata['nick_name'],(string)$staffdata["login_account"],(string)$now1,(string)$now2));
		$result = Utils::WrapResultOK($ds['t']['rows']);
		return $this->responseJson($request,$result);
	}

	public function monitorcountAction()
	{
		$da = $this->get("we_data_access");
		$da_im = $this->get('we_data_access_im');
		$request = $this->getRequest();
		//访问权限校验
		$api = new ApiController();
      	$api->setContainer($this->container);

    	$isWeFaFaDomain = $api->checkWWWDomain(); 
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $api->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   	$re = array("returncode"=>"9999");
			    $re["code"]="err0105";
    	   	   	$re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
 				return $this->responseJson($request,$re);
    	   }
    	}
		
		$openid = $request->get("openid"); 
		$staffinfo = new Staff($da,$da_im,$openid,$this->get("logger"),$this->container);
		$staffdata = $staffinfo->getInfo();
		if(empty($staffdata))
		{
			$result = Utils::WrapResultError("无效操作帐号");
			return $this->responseJson($request,$result);
		}
		$sql = 'select count(1) cnt from t_module_location_monitor a where a.eno=?';
		$ds = $da->GetData('t',$sql,array((string)$staffdata["eno"]));
		$result = Utils::WrapResultOK($ds['t']['rows'][0]['cnt']);
		return $this->responseJson($request,$result);		
	}

	public function monitorlistAction()
	{
		$da = $this->get("we_data_access");
		$da_im = $this->get('we_data_access_im');
		$request = $this->getRequest();
		//访问权限校验
		$api = new ApiController();
      	$api->setContainer($this->container);

    	$isWeFaFaDomain = $api->checkWWWDomain(); 
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $api->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   	$re = array("returncode"=>"9999");
			    $re["code"]="err0105";
    	   	   	$re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
 				return $this->responseJson($request,$re);
    	   }
    	}
		
		$openid = $request->get("openid"); 
		$staffinfo = new Staff($da,$da_im,$openid,$this->get("logger"),$this->container);
		$staffdata = $staffinfo->getInfo();
		if(empty($staffdata))
		{
			$result = Utils::WrapResultError("无效操作帐号");
			return $this->responseJson($request,$result);
		}

		$limit = $request->get("limit");
		$pageIndex = $request->get("page_index");
		
		/*$sql = 'select a.*,b.nick_name,b.photo_path_big photo_path,1 online ,c.dept_name,b.login_account ,m.address ,m.ctime'
			.' from t_module_location_monitor a ,we_staff b ,we_department c ,'
			.' (select l.*from t_module_location l,(select max(t.ctime) ctime,t.staff from t_module_location t group by staff) as temp where l.staff = temp.staff and l.ctime = temp.ctime) m '
			.' where a.staff=b.fafa_jid and b.dept_id=c.dept_id and a.eno=? and m.staff=b.login_account order by a.jiondate limit '.($pageIndex-1)*$limit.','.$limit;
		*/
		$sql = " select m.login_account,m.state,1 online,l.address,l.ctime"
				." from t_module_location_monitor m left join "
				." (select l.*from t_module_location l,(select max(t.ctime) ctime,t.staff" 
			 	." from t_module_location t group by staff) as temp where l.staff = temp.staff and l.ctime = temp.ctime) l"
				." on m.login_account=l.staff"
				." where m.eno=?"
				." order by m.jiondate limit ".(($pageIndex-1)*$limit).','.$limit;

		$ds = $da->GetData('t',$sql,array((string)$staffdata["eno"]));
		foreach ($ds['t']['rows'] as $key => $value) {
			$staff = $staffinfo->getStaffInfo($ds['t']['rows'][$key]['login_account']);

			$ds['t']['rows'][$key]['dept_name'] = $staff['dept_name'];
			$ds['t']['rows'][$key]['nick_name'] = $staff['nick_name'];
			$ds['t']['rows'][$key]['photo_path'] = $staff['photo_path'];

		}
		$result = Utils::WrapResultOK($ds['t']['rows']);
		return $this->responseJson($request,$result);		
	}

	public function removeMonitorAction()
	{
		$da = $this->get("we_data_access");
		$da_im = $this->get('we_data_access_im');
		$request = $this->getRequest();
		//访问权限校验
		$api = new ApiController();
      	$api->setContainer($this->container);

    	$isWeFaFaDomain = $api->checkWWWDomain(); 
    	if(!$isWeFaFaDomain)
    	{
    	   $token = $api->checkAccessToken($request,$da);	
    	   if(!$token)
    	   {
    	   	   	$re = array("returncode"=>"9999");
			    $re["code"]="err0105";
    	   	   	$re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
 				return $this->responseJson($request,$re);
    	   }
    	}
		
		$openid = $request->get("openid"); 
		$staffinfo = new Staff($da,$da_im,$openid,$this->get("logger"),$this->container);
		$staffdata = $staffinfo->getInfo();
		if(empty($staffdata))
		{
			$result = Utils::WrapResultError("无效操作帐号");
			return $this->responseJson($request,$result);
		}
		$to = $request->get("to"); 
		if(empty($to))
		{
			$result = Utils::WrapResultError("无效的帐号");
			return $this->responseJson($request,$result);			
		}
		$to = $staffinfo->getStaffInfo($to);
		if(empty($to))
		{
			$result = Utils::WrapResultError("无效的帐号");
			return $this->responseJson($request,$result);
		}
		try
		{
			$sql = 'delete from  t_module_location_monitor where staff=?';
			$da->ExecSQL($sql,array((string)$to['jid']));
			$sql = 'delete from  t_module_location where staff=?';
			$da->ExecSQL($sql,array((string)$to['login_account']));
		}
		catch(\Exception $e)
		{}
		$senddata = array('opt'=>'stop');
		$msg = Utils::WrapMessage('sendlocation',$senddata,array());
		//$msgxml = Utils::WrapMessageXml($this->module["jid"],$msg,'sendlocation-id');
		$api->sendMsg("",$to['jid'],'sendlocation',json_encode($msg));
		return $this->responseJson($request, Utils::WrapResultOk(""));		
	}

	private function responseJson($request,$re)
	{
		$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		$response->headers->set('Content-Type', 'text/json');
		return $response;
	}
	
}