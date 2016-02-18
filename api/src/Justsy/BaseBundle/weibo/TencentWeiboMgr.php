<?php
namespace Justsy\BaseBundle\weibo;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\weibo\TencentClient;
use Justsy\BaseBundle\weibo\TencentOAuth;
use Justsy\BaseBundle\DataAccess\SysSeq;
class TencentWeiboMgr{
	  private $conn = null;
	  private $uid = "";
	  private $token = "";
		public function __construct($_db,$_uid='',$_token='')
	  {
	    $this->conn = $_db;
	    $this->uid = $_uid;	   
	    $this->token =  $_token;
	  }
	  public function saveToken($token,$openid,$openkey,$login_account,$eno)
	  {
	  	try{
	  		$openid=strtolower($openid);
		  	$access_token=$token["access_token"];
		  	$expire_in=$token["expires_in"];
		  	$refresh_token=$token["refresh_token"];
		  	//获取用户基本信息
		  	$client=new TencentClient(TencentOAuth::$client_id,TencentOAuth::$client_key,$openid,$access_token);
		  	$userinfo=$client->get_user_baseinfo();
		  	$id=SysSeq::GetSeqNextValue($this->conn,"we_weibo_account","id");
		  	$sql="insert into we_weibo_account (id,uid,access_token,expires_in,nick_name,user_name,appid,appkey,followers_count,favourites_count,created_at,verified,refresh_token,openid,openkey,head_url,owner_staff,type,eno) 
		  	values(?,?,?,date_add(now(),interval ? second),?,?,?,?,?,?,now(),?,?,?,?,?,?,?,?)";
		  	$params=array($id,$openid,$access_token,(int)$expire_in,$userinfo['nick'],$userinfo['name'],
		  	TencentOAuth::$client_id,TencentOAuth::$client_key,$userinfo['fansnum'],$userinfo['favnum'],($userinfo['isvip']=='1'? true:false),$refresh_token,$openid,$openkey,$userinfo['head'],$login_account,'tencent',$eno);
		  	$this->conn->ExecSQL($sql,$params);
		  	return true;
	  	}
	  	catch(\Exception $e){
	  		var_dump($e->getMessage());
	  	}
	  }
	  public function getToken($uid,$eno)
	  {
	  	$sql="select access_token,expires_in,openid,openkey,refresh_token from we_weibo_account where uid=? and eno=?";
	  	$params=array($uid,$eno);
	  	$ds=$this->conn->Getdata('info',$sql,$params);
	  	if($ds['info']['recordcount']>0)
	  		return $ds['info']['rows'][0];
	  	else
	  		return null;
	  }
}
?>