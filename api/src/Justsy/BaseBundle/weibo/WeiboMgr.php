<?php
namespace Justsy\BaseBundle\weibo;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\weibo\TencentClient;
use Justsy\BaseBundle\weibo\TencentOAuth;
use Justsy\BaseBundle\weibo\SaeTOAuthV2;
use Justsy\BaseBundle\weibo\SaeTClientV2;
use Justsy\BaseBundle\DataAccess\SysSeq;
class WeiboMgr{
	private $conn = null;
	private $logger=null;
	public function __construct($da,$logger){
		$this->conn=$da;
		$this->logger=$logger;
	}
	public function getAccounts($eno,$type='')
	{
		$sql="select a.nick_name,a.head_url,a.uid,a.type,case when a.type='sina' then'新浪' when a.type='tencent' then '腾讯' else '微信' end typename,
		case when a.expires_in>now() then '1' else '0' end istoken,a.expires_in,a.owner_staff as create_staff,b.nick_name as create_staff_name from we_weibo_account a left join we_staff b on b.login_account=a.owner_staff where a.eno=?";
		$params=array($eno);
		if(!empty($type)){
			$sql.=" and a.type=?";
			array_push($params,$type);
		}
		$ds=$this->conn->Getdata('info',$sql,$params);
		return $ds['info']["rows"];
	}
	public function getPlatType($uid,$eno)
  {
  	$sql="select type from we_weibo_account where uid=? and eno=?";
  	$params=array($uid,$eno);
  	$ds=$this->conn->Getdata('info',$sql,$params);
  	if($ds['info']['recordcount']>0)
  		return $ds['info']['rows'][0]['type'];
  	else
  		return null;
  }
}
?>