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

class SsoXiechengController implements ISso
{
	public static $login_url="http://ct.ctrip.com/m/singlesignon/h5signinfo";
	public static $xc_homepage="http://ct.ctrip.com/m";
	public static $AccessUK='obk_eavic_sq';
	public static $AccessPK='obk_eavic_sq';
	public static $AccessAppid='eavic_sq';
	public static $get_token_url="https://www.corporatetravel.ctrip.com/corpservice/CorpSSOAccessCheck.asmx";
	public static $sync_user_url="https://www.corporatetravel.ctrip.com/corpservice/CorpCustService.asmx";
	public static $action="SSOAuthenticaionWithXML";
	public static $direct_url="http://ct.ctrip.com";
	public static $bind_type="ctrip";
	public static $module_name="携程";
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
				$authkey=$authcode;//DES::encrypt2($authcode,$appkey);
//				if($bind_type=='ecstore'){
//					$bind_uid=json_decode($authcode,true);
//					$bind_uid=$bind_uid['userid'];
//				}
//				else
//					$bind_uid=$authkey;
				$bind_uid=$authkey;
				$sql = "insert into we_staff_account_bind(bind_account,appid,bind_uid,authkey,bind_type,bind_created)values(?,?,?,?,?,now())";
				$con->ExecSQL($sql,array(
					(string)$openid,
					(string)$appid,
					(string)$bind_uid,
					(string)$authkey,
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
			else if($openids=='allbind'){
				$sql="select a.bind_account as openid from we_staff_account_bind a where a.bind_type=? and exists(select 1 from we_staff b where b.eno=? and b.openid=a.bind_account)";
				$param=array(self::$bind_type,$eno);
				$ds=$con->getdata('info',$sql,$param);
				$rows=$ds['info']['rows'];
				foreach($rows as $row){
					$openidArr[]=$row['openid'];
				}
			}
			else{
				$openidArr=explode(',',$openids);
			}
			$sqls=[];
			$paras=[];
			for($i=0;$i<count($openidArr);$i++){
				$sql="select a.mobile,a.nick_name,a.birthday,a.sex_id,a.login_account,ifnull(b.dept_name,'') as dept_name from we_staff a left join we_department b on b.dept_id=a.dept_id where a.openid=?";
				$params=array($openidArr[$i]);
				$ds=$con->getdata('info',$sql,$params);
				if($ds['info']['recordcount']>0){
					$nick_name=$ds['info']['rows'][0]['nick_name'];
					$mobile=$ds['info']['rows'][0]['mobile'];
					$login_account=$ds['info']['rows'][0]['login_account'];
					$birthday=$ds['info']['rows'][0]['birthday'];
					$sex_id=$ds['info']['rows'][0]['sex_id']=='女'?'F':'M';
					$dept_name=$ds['info']['rows'][0]['dept_name'];
					$md5openid=substr(md5($openidArr[$i]),8,16);//md5($openidArr[$i],true);
					$paraXml='<AuthenticationRequest>'.
										 '<Authentication>'.
										 '<UserID>'.self::$AccessUK.'</UserID>'.
										 '<Password>'.self::$AccessPK.'</Password>'.
										 '<CorporationID>'.self::$AccessAppid.'</CorporationID>'.
										 '<EmployeeID>'.$md5openid.'</EmployeeID>'.
										 '<SubAccountName>eavic_SQ_现结</SubAccountName>'.
										 '<Email>'.$login_account.'</Email>'.
										 '<Gender>'.$sex_id.'</Gender>'.
										 '<NickName>'.$nick_name.'</NickName>'.
										 (empty($mobile)?'':('<MobilePhone>'.$mobile.'</MobilePhone>')).
										 (empty($birthday)?'':('<Birthday>'.$birthday.'</Birthday>')).
										 (empty($dept_name)?'':('<Dept1>'.$dept_name.'</Dept1>')).
										 '</Authentication>'.
										 '</AuthenticationRequest>';
			    $soap=new SoapClient(self::$sync_user_url."?WSDL");
			    $para=array("requestXMLString"=>$paraXml);
			    error_reporting(E_ERROR|E_WARNING|E_PARSE);
			    $result=$soap->SaveCorpCustInfoWithXML($para);
			    error_reporting(E_ERROR|E_WARNING|E_PARSE|E_NOTICE);
			    if(isset($result->SaveCorpCustInfoWithXMLResult)){
			    	$str=$result->SaveCorpCustInfoWithXMLResult;
		    		if($str=='Success' || $str=='success'){
		    				$sql="select 1 from we_staff_account_bind where bind_account=? and bind_type=? and bind_uid=? and appid=?";
		    				$params=array($openidArr[$i],self::$bind_type,$md5openid,$appid);
		    				$ds=$con->Getdata('one',$sql,$params);
		    				if($ds['one']['recordcount']>0)continue;
		    				//跟新联系人
		    				$sql = "delete from we_staff_account_bind where bind_account=? and bind_type=? and appid=?";
								$params=array($openidArr[$i],self::$bind_type,$appid);
								array_push($sqls,$sql);
								array_push($paras,$params);
								$sql = "insert into we_staff_account_bind(bind_account,appid,bind_uid,authkey,bind_type,bind_created)values(?,?,?,?,?,now())";
								$params=array(
									$openidArr[$i],
									(string)$appid,
									(string)$md5openid,
									(string)$md5openid,
									self::$bind_type
								);
								array_push($sqls,$sql);
								array_push($paras,$params);
		    		}
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