<?php
namespace Justsy\InterfaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;
/*
	* @author liling@fafatime.com
	* @copyright Copyright(c) 2012-2015 www.wefafa.com
	* @param string dsid datasource ID
	* 统一数据访问总控制器
	* 移动端所有数据访问全部请求该方法
	*/
class DataInterfaceController extends Controller
{

	public function dataAccessAction()
	{
	    $request = $this->getRequest();
	    $openid = $request->get("openid"); //可以为空。如果是未登录直接访问时，标识当前获取数据的人员
	    $dsid = $request->get("dsid"); //请求的数据源标识
	    $id = $request->get("id");     //按数据源id获得数据
	    $this->get("logger")->err("dataAccess dsid:".$dsid);
	    $parameters = $request->get("parameters"); //json格式
	    $this->get("logger")->err("dataAccess parameters:".$parameters);
	    $parameters = json_decode($parameters,true);
	    if(!isset($parameters["appid"]) || empty($parameters["appid"]))
	    {
	    	$appid = $request->get("appid");
	   		$parameters["appid"] = $appid;
	    }
	    $parameters = json_encode($parameters);

	    $this->get("logger")->err("dataAccess parameters:".$parameters);
	    $code=ReturnCode::$SUCCESS;
	    $rows=array();
	    try
	    {
		    if(empty($dsid) && empty($id))
		    {
		    	$re = array();
		    	$re["returncode"] = ReturnCode::$SYSERROR;
		    	$re["msg"] = "参数dsid或id未指定";
		    	return $this->responseJson($request,$re);		     	
		    }
		}
	    catch(\Exception $e)
	    {
	    	$this->get('logger')->err($e);
			$code=ReturnCode::$SYSERROR;
			$rows=array();
	    }
	    $currUser = $this->get('security.context')->getToken();	    
	    if(empty($currUser))
	    {
	    	if(empty($openid))
	    	{
	    		$re = array();
	    		$re["returncode"] = ReturnCode::$SYSERROR;
	    		$re["msg"] = "openid不能为空";
	    		return $this->responseJson($request,$re);
	    	}
	    	$staffObj = new \Justsy\BaseBundle\Management\Staff(
	    			$this->get('we_data_access'),
	    			$this->get('we_data_access_im'),
	    			$openid,
	    			$this->get("logger"));
	    	$currUser = $staffObj->getSessionUser();
	    }
	    else
	    {
	    	$currUser = $currUser->getUser();
	    	//$currUser = json_decode(json_encode($userObj),true);
	    	//$currUser["login_account"] = $userObj->getUserName();
	    }
		  $extractObj = new \Justsy\BaseBundle\DataAccess\DataExtract\Extract($this->container);
		  $rows = null;
		  if ( !empty($dsid)){  
		    $rows = $extractObj->execute($currUser,$dsid,$parameters,$error);
		    return $this->responseJson($request,$rows);
		    //$response=new Response(json_encode($rows));
	  	  	//$response->headers->set('Content-Type','Application/json');
	  	  	//return $response;
		  }
		  else if (!empty($id))
		  {
		    $rows = $extractObj->executeBydsid($currUser,$id,$parameters,$error);	
		    header('Content-Encoding: plain');
		    return $this->responseJson($request,$rows);     	
		  }
	}

  	//数据访问接口
	public function getDataAccessAction()
  	{
	  	$request = $this->get("request");
	  	$module = $request->get("module");
	  	$action = $request->get("action");
	  	$params = $request->get("params");
	  	$class = null;
	  	$re["action"] = $action;
	  	if(empty($params)) 
	  		$params=array();
	  	else if(is_string($params))
	  		$params = json_decode($params,true);
	    $currUser = $this->get('security.context')->getToken();
	    if(empty($currUser))
	    {
	    	$openid = $request->get("openid");
	    	if(empty($openid))
	    	{
	    		$re = array();
	    		$re["returncode"] = ReturnCode::$SYSERROR;
	    		$re["msg"] = "openid不能为空";
	    		return $this->responseJson($request,$re);
	    	}
	    	$staffObj = new \Justsy\BaseBundle\Management\Staff(
	    			$this->get('we_data_access'),
	    			$this->get('we_data_access_im'),
	    			$openid,
	    			$this->get("logger"),
	    			$this->container);
	    	$currUser = $staffObj->getSessionUser();
	    }
	    else
	    {
	    	$currUser = $currUser->getUser();
	    }
	  	$params["user"] = $currUser;
	  	$module = strtolower($module);  //转化为小写
	  	if($module=="app")
	  	{
	  		$class = new \Justsy\BaseBundle\Management\App($this->container);
	  	}
	  	else if ( $module=="staff")
	  	{
	  	   $class = new \Justsy\BaseBundle\Management\Staff($this->get('we_data_access'),$this->get('we_data_access_im'),$currUser->getUserName(),$this->get("logger"),$this->container);
	  	}
	    else if ( $module=="enterprise")
	  	{
	  	   $class = new \Justsy\BaseBundle\Management\Enterprise($this->get('we_data_access'),$this->get("logger"),$this->container);
	  	}
	  	else if ( $module=="dept")
	  	{
	  		$class = new \Justsy\BaseBundle\Management\Dept($this->get('we_data_access'),$this->get('we_data_access_im'),$this->container);
	  	}
	  	else if ($module=="group")
	  	{
	  		$class = new \Justsy\BaseBundle\Management\GroupMgr($this->get('we_data_access'),$this->get('we_data_access_im'),$this->container);
	  	}
	  	else if ($module=="microaccount")
	  	{
	  		$class = new \Justsy\BaseBundle\Management\MicroAccountMgr($this->get('we_data_access'),$this->get('we_data_access_im'),$currUser->getUserName(),$this->get("logger"),$this->container);
	  	}
	  	else if ($module=="service")  //服务号管理
	  	{
	  		$class = new \Justsy\BaseBundle\Management\Service($this->container);
	  	}
	  	else if ($module=="announcer")  //广播管理
	  	{
	  		$class = new \Justsy\BaseBundle\Management\Announcer($this->container);
	  	}
	  	else if ($module=="rolefunc")  //权限、功能管理
	  	{
	  		$class = new \Justsy\BaseBundle\Management\RoleFunc($this->container);
	  	}
	  	else if ($module=="role")
	  	{
	  	  $class = new \Justsy\BaseBundle\Management\Role($this->container);
	    }
	    else if ( $module=="portal")
	    {
	        $class = new \Justsy\BaseBundle\Management\Portal($this->container);
	    }
	    else if ( $module=="sysparam")
	    {
	        $class = new \Justsy\BaseBundle\Management\EnoParamManager($this->get('we_data_access'),$this->get('we_data_access_im'),$this->container);
	    }
	    else if ( $module=="servermonitor")
	    {
	        $class = new \Justsy\BaseBundle\Management\ServerMonitor($this->container);
	    }
	    else if ( $module=="hrattendance")
	    {
	        $class = new \Justsy\BaseBundle\Management\HrAttendance($this->container);
	    }
	  	else
	  	{
	  	    $result = array("returncode"=>"9999","msg"=>"请转入正确的模块名称！");
	  	    return $this->responseJson($request,$result);
	  	}
	  	$result = call_user_func_array(array($class,$action),array($params));
	  	return $this->responseJson($request,$result);
  	}
    
	private function responseJson($request,$re)
	{
		$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		$response->headers->set('Content-Type', 'text/json');
		return $response;
	}
}
?>