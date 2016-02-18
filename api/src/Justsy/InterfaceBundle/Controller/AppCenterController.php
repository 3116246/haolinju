<?php

namespace Justsy\InterfaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\DataAccess\SysSeq;

class AppCenterController extends Controller
{   
  public function autoinAction($appid) 
  {
    $request = $this->getRequest();
	  $user = $this->get('security.context')->getToken()->getUser();
	  $encode = $user->eno.",$user->openid,x,y,".time();
	  $appid2=DES::decrypt($appid);
	  $da = $this->get('we_data_access');
    $ds = $da->GetData("app", "select appkey from we_appcenter_apps where appid=? ", array((string)$appid2));
	  $auth = DES::encrypt2($encode,$ds["app"]["rows"][0]["appkey"]);
    //获取url
    $url = $_SERVER['QUERY_STRING'];
    //$urlsource=$request->get("_urlSource");
    //$this->get("logger")->err("wefafa=>".$url);
    if(!empty($url))
       $url = "&url=".$this->parseurl(str_replace(array("url=","%25"),array("","%"),$url));
    //if(!empty($urlsource))
    //   $url .= "%2526urlsource=".($urlsource);       
    //$this->get("logger")->err("query_string=>".$_SERVER['QUERY_STRING']);
    //生成应用中心地址
    $appcenterUrl = $this->container->getParameter('fafa_appcenter_url')."/appcenter/link/$appid";
    $appcenterUrl .= "?auth=$auth".$url;
    $this->get("logger")->err("appcenterUrl=>".$appcenterUrl);
    return $this->redirect($appcenterUrl);
  }

  function parseurl($url="")
	{
			$url = rawurlencode(mb_convert_encoding($url, 'gb2312', 'utf-8'));
			$a = array("%3A", "%2F", "%40","%26","%2B","%20","%25");
			$b = array(":", "/", "@","&","+"," ","%");
			$url = str_replace($b,$a,  $url);
			return $url;
	}
  
	public function authorizeUser($appid,$staff_loginname)
	{
		try {
			$da = $this->get('we_data_access');
			$appinfo = $da->GetData("t","select 1 from  we_appcenter_apps where appid=? and apptype!='00'",array((string)$appid));
			if($appinfo!=null && $appinfo["t"]["recordcount"] > 0) {
			$id = SysSeq::GetSeqNextValue($da,"we_app_userpriv","id");
			$sql="insert into we_app_userpriv(id,login_account,appid,role) values(?,?,?,0) ";
			$da->ExecSQL($sql,array(
				(string)$id,
				(string)$staff_loginname,
				(string)$appid
			));
			}
		} catch (\Exception $e) {
			$this->get("logger")->err($e->getMessage());
		}
	}
	//用户应用列表接口
	public function getAppsByAction()
	{
		$request = $this->getRequest();
		$da = $this->get('we_data_access');
		$user = $this->get('security.context')->getToken()->getUser();
		//$sql="select distinct a.*,ifnull(d.typename,'') as typename from we_app_userpriv b,we_staff c,we_appcenter_apps a left join we_app_type d on d.typeid=a.apptype where a.appid=b.appid and b.login_account=c.login_account and c.login_account=?";
    $sql="select a.* from we_appcenter_apps a ,we_app_type b where a.apptype=b.typeid and (b.typeid='4' or b.parentid=4 ) and state='1' and appdeveloper=(SELECT eno FROM we_enterprise where edomain='fafatime.com') union ".   //获取平台开发商提供的个人类应用
            "select a.* from we_appcenter_apps a  where a.apptype!='00' and a.appdeveloper=? union ".   //获取本企业正在开发但不是微应用的应用
            "select a1.* FROM we_appcenter_apps a1 ,we_app_subscibe a,we_app_userpriv b where a1.apptype!='00' and a1.appid=a.appid and a.appid=b.appid and a.objecttype='1' and a.objectid=? and b.login_account=? union ".   //获取所在企业订阅的并取得使用权限的企业类但不是微应用应用
            "select a1.* FROM we_appcenter_apps a1 ,we_app_subscibe a,we_app_userpriv b where a1.apptype!='00' and a1.appid=a.appid and a.appid=b.appid and a.objecttype='2' and a.objectid=? and b.login_account=? union ".   //获取自己订阅的个人类但不是微应用应用
            "select a1.* FROM we_appcenter_apps a1 ,we_app_subscibe a where a1.apptype!='00' and a1.appid=a.appid and a.objecttype='1' and a.objectid=? ";//获取企业订阅的自己未取得使用授权但不是微应用的应用
		$params=array(
  	       (string)$user->eno,
  	       (string)$user->eno,
  	       (string)$user->getUsername(),
  	       (string)$user->getUsername(),
  	       (string)$user->getUsername(),
  	       (string)$user->eno
  	       );
		$ds=$da->Getdata('apps',$sql,$params);
		$rows=$ds['apps']['rows'];
  	 for($i=0;$i<count($rows);$i++)
  	 {
  	 	 $r = $rows[$i];
  	 	 $checkcode = $user->getAppSig($r["appid"],$r["appkey"]);
  	 	 $rows[$i]["auth"] = $checkcode;
  	 }
		$result=array();
		$result['applist']=$rows;
		$result['appdomain']=$this->getAppcenterDomain();
		$re=array('s'=>1,'result'=> $result);
		$response=new Response(json_encode($re));
		//$response->headers->set('Content-Type', 'text/json');
		return $response;
	}
	//获取应用中心域名地址
	private function getAppcenterDomain()
	{
		return $this->container->getParameter('fafa_appcenter_url');
	}
}
