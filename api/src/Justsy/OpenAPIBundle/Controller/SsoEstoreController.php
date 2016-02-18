<?php

namespace Justsy\OpenAPIBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Management\Staff;
use SoapClient;

class SsoEstoreController implements ISso
{
	public static $bind_type="ecstore";
	public static $module_name="优选";
	public static $bind_url="http://172.168.95.68/index.php/api?method=b2c.member.auto_register&format=json";
	public static function ssoAction($container,$conn,$appid,$openid,$token,$encrypt)
	{
			$da=$conn;
	    
	    $sql = "select authkey,bind_uid from we_staff_account_bind a,we_staff b where a.bind_account=b.openid and a.bind_account=? and a.bind_type=?";
			$ds = $da->GetData("tb",$sql,array((string)$openid,self::$bind_type));
			if(count($ds["tb"]["rows"])>0){
				  //解析autokey
					$bind_uid=$ds['tb']['rows'][0]["bind_uid"];
					$sql = "select appkey from we_appcenter_apps where appid=?";
					$ds = $da->GetData("t",$sql,array((string)$appid));
					$appkey=$ds['t']['recordcount']>0 ? $ds['t']['rows'][0]['appkey'] : '';
					if($encrypt=='1')
						$bind_uid=DES::decrypt2($bind_uid,$appkey);
					
		    	$EmployeeNO=$bind_uid;
		    	$login_url=self::$login_url;
		    	$params=array(
		    		"Channel"=>"",
		    		"AccessUserID"=>self::$AccessUK,
		    		"AccessPassword"=>self::$AccessPK,
		    		"token"=>$token,
		    		"EmployeeID"=>$EmployeeNO,
		    		"AppID"=>self::$AccessAppid,
		    		"InitPage"=>"Home"
		    	);
		    	return array("login_url"=> $login_url,"params"=> $params);
		    	//Utils::do_post_request(self::$login_url,"Channel=&AccessUserID=".self::$AccessUK."&AccessPassword=".self::$AccessPK."&token=".$token."&EmployeeID=".$EmployeeNO."&AppID=".self::$AccessAppid."&InitPage=Home");
			}
    	$page = self::$xc_homepage;
    	return Utils::http_redirect($page);
	}

	public static function tokenAction($controller,$con,$appid,$openid,$encrypt)
	{
			$da = $con;
    	//$result = Utils::do_post_request("http://www.wefafa.com", array());
			$sql = "select appkey from we_appcenter_apps where appid=?";
			$ds = $da->GetData("t",$sql,array((string)$appid));
			$result="";
			$json=array("error"=>"bad error");
			try{
				if(count($ds["t"]["rows"])==0)
			{
				$json=array("error"=>"invalid appid");
			}
			else
			{
				$appkey = $ds["t"]["rows"][0]["appkey"];
				$sql = "select authkey,bind_uid from we_staff_account_bind a,we_staff b where a.bind_account=b.openid and a.bind_account=? and a.bind_type=?";
				$ds = $da->GetData("tb",$sql,array((string)$openid,self::$bind_type));
				if(count($ds["tb"]["rows"])>0)
				{
					//$api = new \Justsy\OpenAPIBundle\Controller\ApiController();
					//$api->setContainer($controller->container);					
					$code = md5($appid.$appkey);
					
					//解析autokey
					$bind_uid=$ds['tb']['rows'][0]["bind_uid"];
					if($encrypt=='1')
						$bind_uid=DES::decrypt2($bind_uid,$appkey);
					
					//获取携程令牌
		    		$EmployeeNO=$bind_uid;
					
					$paraXml='<SSOAuthRequest>'.
									 '<Language>Chinese</Language>'.
									 '<SSOAuth>'.
									 '<AccessUK>'.self::$AccessUK.'</AccessUK>'.
									 '<AccessPK>'.self::$AccessPK.'</AccessPK>'.
									 '<EmployeeNO>'.$EmployeeNO.'</EmployeeNO>'.
									 '</SSOAuth>'.
									 '</SSOAuthRequest>';
					
		    	$soap=new SoapClient(self::$get_token_url."?WSDL");
		    	$para=array("requestXMLString"=>array(
		    			"SSOAuthRequest"=>array(
			    			"Language"=>"Chinese",
				    		"SSOAuth"=>array(
				    			"AccessUK"=>self::$AccessUK,
				    			"AccessPK"=>self::$AccessPK,
				    			"EmployeeNO"=>$EmployeeNO,
				    		)
			    		)
		    		)
		    	);
		    	$para=array("requestXMLString"=>$paraXml);
		    	error_reporting(E_ERROR|E_WARNING|E_PARSE);
		    	$result=$soap->SSOAuthenticaionWithXML($para);
		    	error_reporting(E_ERROR|E_WARNING|E_PARSE|E_NOTICE);
		    	//$controller->get("logger")->err($result);
		    	$accesstoken='';
		    	//解析result
		    	if(isset($result->SSOAuthenticaionWithXMLResult)){
		    		$str=$result->SSOAuthenticaionWithXMLResult;
		    		$arr1=explode('&',$str);
		    		for($i=0;$i<count($arr1);$i++){
		    			$arr2=explode('=',$arr1[$i]);
		    			if($arr2[0]=='AccessToken'){
		    				$accesstoken=$arr2[1];
		    				break;
		    			}
		    		}
		    		if(empty($accesstoken)){
		    			$json=array("error"=>"您的账号激活周期为24小时，如有疑问请拨打：010-67876363-2， 如需出行服务请拨打：400-920-0670或400-820-6699。");
		    		}
		    		else
		    			$json=array('token'=>$accesstoken);
		    	}
		    	else{
		    		$json=array("error"=>"您的账号激活周期为24小时，如有疑问请拨打：010-67876363-2， 如需出行服务请拨打：400-920-0670或400-820-6699。");
		    	}
				}
				else
				{
					$json=array("error"=>"您的账号激活周期为24小时，如有疑问请拨打：010-67876363-2， 如需出行服务请拨打：400-920-0670或400-820-6699。");
				}
			}
			}
			catch(\Exception $e){
				$json['error']=$e->getMessage();
			}
			return $json;	
	}
	public static function bindTitleAction($controller,$con,$appid,$openid,$encrypt)
	{
		return "请输入员工工号：";
	}
	public static function directUrlAction()
	{
		return self::$direct_url;
	}
	public static function bindAction($controller,$con,$appid,$openid,$params)
	{
		$re = array("returncode"=>"0000");
		try
		{
			$authcode = $params->get("auth");
			$sql = "select appkey from we_appcenter_apps where appid=?";
			$ds = $con->GetData("t",$sql,array((string)$appid));
			if(count($ds["t"]["rows"])==0)
			{
				$re = array("returncode"=>"9999","msg"=>"appid is not found");
			}
			else
			{
				$appkey = $ds["t"]["rows"][0]["appkey"];
				$sql = "delete from we_staff_account_bind where bind_account=? and bind_type=? and appid=?";
				$con->ExecSQL($sql,array((string)$openid,self::$bind_type,$appid));
				if($params->get('encrypt')=='1'){
					//$authcode=DES::encrypt2($authcode,'_sddb74+');
				}
				else{
					$authcode=DES::decrypt2($authcode,'_sddb74+');
				}
				$authkey=$authcode;//DES::decrypt2($authcode,'_sddb74+');
				$bind_uid=json_decode($authkey,true);
				$bind_uid=$bind_uid['userid'];
				$authcode=DES::encrypt2($authcode,$appkey);
				$sql = "insert into we_staff_account_bind(bind_account,appid,bind_uid,authkey,bind_type,bind_created)values(?,?,?,?,?,now())";
				$con->ExecSQL($sql,array(
					(string)$openid,
					(string)$appid,
					(string)$bind_uid,
					(string)$authcode,
					self::$bind_type
				));
			}
		}
		catch(\Exception $e)
		{
			$re = array("returncode"=>"9999","msg"=>$e->getMessage());
		}
		return $re;
	}
	public static function bindBatAction($controller,$con,$appid,$eno,$encrypt,$params){
		$re=array('s'=>'1','m'=>'');
		try{
			$openids=$params->get("openids");
			if($openids=='all'){
				$sql="select openid from we_staff where eno=? and auth_level!='J'";
				$param=array($eno);
				$ds=$con->getdata('info',$sql,$param);
				$rows=$ds['info']['rows'];
				foreach($rows as $row){
					$openidArr[]=$row['openid'];
				}
			}
			else{
				$openidArr=explode(',',$openids);
			}
			$sql = "select appkey from we_appcenter_apps where appid=?";
			$ds = $con->GetData("t",$sql,array((string)$appid));
			$appkey='';
			if(count($ds["t"]["rows"])>0)
			{
				$appkey = $ds["t"]["rows"][0]["appkey"];
			}
			$sqls=[];
			$paras=[];
			for($i=0;$i<count($openidArr);$i++){
				$sql="select mobile,nick_name,birthday,sex_id,login_account from we_staff where openid=?";
				$params=array($openidArr[$i]);
				$ds=$con->getdata('info',$sql,$params);
				if($ds['info']['recordcount']>0){
					$pam_account=array();
					$pam_account['login_name']=$ds['info']['rows'][0]['login_account'];
					$pam_account['login_password']='123456';
					$pam_account['psw_confirm']='123456';
					
					$auth=array(
						'userid'=>$pam_account['login_name'],
						'passwd'=>$pam_account['login_password']
					);
					$auth=json_encode($auth);
					//$auth=DES::encrypt2($auth,'_sddb74+');
					$auth=DES::encrypt2($auth,$appkey);
					
					$pam_account=json_encode($pam_account);
					$pam_account=DES::encrypt2($pam_account,'ecstore');
					
					$addr="";
					$name=$ds['info']['rows'][0]['nick_name'];
					$phone=$ds['info']['rows'][0]['phone'];
					$qq="";
					$zipcode="";
					$birthday=$ds['info']['rows'][0]['birthday'];
					$gender=$ds['info']['rows'][0]['sex_id']=='女'?'female':'male';
					
					$data="pam_account=$pam_account&addr=$addr&name=$name&phone=$phone&qq=$qq&zipcode=$zipcode&birthday=$birthday&gender=$gender";
					$result=Utils::do_post_request(self::$bind_url."&".$data);
					$result=json_decode($result,true);
					if($result['rsp']!='fail'){
						$sql = "delete from we_staff_account_bind where bind_account=? and bind_type=? and appid=?";
								$params=array($openidArr[$i],self::$bind_type,$appid);
								array_push($sqls,$sql);
								array_push($paras,$params);
								$sql = "insert into we_staff_account_bind(bind_account,appid,bind_uid,authkey,bind_type,bind_created)values(?,?,?,?,?,now())";
								$params=array(
									$openidArr[$i],
									(string)$appid,
									$ds['info']['rows'][0]['login_account'],
									(string)$auth,
									self::$bind_type
								);
								array_push($sqls,$sql);
								array_push($paras,$params);
					}
				}
			}
			if(count($sqls)>0){
				if(!$con->ExecSQLs($sqls,$paras)){
					$re=array('s'=>'0','m'=>'操作失败');
				}
			}
		}
		catch(\Exception $e){
			$re=array('s'=>'0','m'=> $e->getMessage());
		}
		return $re;
	}
}