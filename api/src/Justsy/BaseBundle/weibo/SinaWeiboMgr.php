<?php
namespace Justsy\BaseBundle\weibo;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\weibo\SaeTOAuthV2;
use Justsy\BaseBundle\weibo\SaeTClientV2;
/**
 * @ignore
 */
class SinaWeiboMgr  {
	  private $conn = null;
	  private $uid = "";
	  private $token = "";
		public function __construct($_db,$_uid,$_token)
	  {
	    $this->conn = $_db;
	    $this->uid = $_uid;	   
	    $this->token =  $_token;
	  }
	  public function isExistsAccount($uid)
	  {
	  	$sql="select 1 from we_weibo_account where uid=?";
	  	$params=array($uid);
	  	$ds=$this->conn->Getdata('info',$sql,$params);
	  	return $ds['info']['recordcount']>0;
	  }
	 	public function saveToken($token,$login_account,$eno)
		{
			try{
				$access_token=$token["Token"];
	  	$expire_in=$token["ExpiresIn"];
		  	$uid=$token["UID"];
		  	//获取用户基本信息
		  	$client=new SaeTClientV2(SaeTOAuthV2::$appid,SaeTOAuthV2::$appkey,$access_token);
		  	$userinfo=$client->get_user_baseinfo($uid);
		  	var_dump($userinfo);
		  	$id=SysSeq::GetSeqNextValue($da,"we_weibo_account","id");
		  	$sql="insert into we_weibo_account (id,uid,access_token,expires_in,nick_name,user_name,appid,appkey,followers_count,friends_count,statuses_count,favourites_count,created_at,verified,refresh_token,head_url,owner_staff,type,eno) 
		  	values(?,?,?,FROM_UNIXTIME($expire_in,'%Y-%m-%d %H:%i:%S'),?,?,?,?,?,?,?,?,now(),?,?,?,?,?,?)";
		  	$params=array($id,$uid,$access_token,$userinfo['screen_name'],$userinfo['screen_name'],
		  	SaeTOAuthV2::$appid,SaeTOAuthV2::$appkey,$userinfo['followers_count'],$userinfo['friends_count'],
		  	$userinfo['statuses_count'],$userinfo['favourites_count'],$userinfo['verified'],'',$userinfo['head_url'],$login_account,'sina',$eno);
		  	$this->conn->ExecSQL($sql,$params);
		  	return true;
			}
			catch(\Exception $e){
				var_dump($e->getMessage());
				die();
				return false;
			}
		}
		public function getToken($uid,$eno)
	  {
	  	$sql="select access_token,expires_in,refresh_token from we_weibo_account where uid=? and eno=?";
	  	$params=array($uid,$eno);
	  	$ds=$this->conn->Getdata('info',$sql,$params);
	  	if($ds['info']['recordcount']>0)
	  		return $ds['info']['rows'][0];
	  	else
	  		return null;
	  }
	  public function getlist($pageno=1,$where=null,$pagesize=50)
	  {
	  	    //每页50条
	  	    $start = ($pageno-1)*$pagesize;
	  	    $end = $pageno*$pagesize;
	  	    $wh = " and 1=1 ";
	  	    $count_para=array((int)$this->uid);
	  	    $para=array((int)$this->uid);
	  	    if(!empty($where))
	  	    {
	  	        if(!empty($where["id"])){
	  	        		$wh .= "and id=?";
	  	        		$count_para[]= (int)$where["id"];
	  	        		$para[] = (int)$where["id"];
	  	        }
	  	        if(!empty($where["screen_name"])){
	  	        		$wh .= "and screen_name like concat('%',?,'%')";
	  	        		$count_para[]= (string)$where["screen_name"];
	  	        		$para[] = (string)$where["screen_name"];
	  	        }	
	  	        if(!empty($where["verified"])){
	  	        		$wh .= "and verified =?";
	  	        		$count_para[]= (int)$where["verified"];
	  	        		$para[] = (int)$where["verified"];
	  	        }	
	  	        if(!empty($where["gender"])){
	  	        		$wh .= "and gender =?";
	  	        		$count_para[]= (string)$where["gender"];
	  	        		$para[] = (string)$where["gender"];
	  	        }	
	  	        if(!empty($where["province"])){
	  	        		$wh .= "and province =?";
	  	        		$count_para[]= (int)$where["province"];
	  	        		$para[] = (int)$where["province"];
	  	        }	
	  	        if(!empty($where["city"])){
	  	        		$wh .= "and city =?";
	  	        		$count_para[]= (int)$where["city"];
	  	        		$para[] = (int)$where["city"];
	  	        }	  	          	          	          	          	        
	  	    }
	  	    $sql_count = "select count(*) cnt from we_weibo_sina_fans where uid=?".$wh;
	  	    //var_dump($sql_count);
	      	$sql = "select * from we_weibo_sina_fans where uid=? ".$wh." order by created_at desc limit ".$start.",".$end;
	      	$ds_p= $this->conn->GetData("page",$sql_count,$count_para);
	      	$ds_d= $this->conn->GetData("data",$sql,$para);
	      	return array("count"=> $ds_p["page"]["rows"][0]["cnt"],"list"=> $ds_d["data"]["rows"]);
	  }
	  
	  public function getFansCountByBindUid($uid)
	  {
	      	$sql_count = "select count(*) cnt from we_weibo_sina_fans where uid=?";
	      	$ds_p= $this->conn->GetData("page",$sql_count,array((string)$uid));
	      	return $ds_p["page"]["rows"][0]["cnt"];
	  }

	  
	  //从weibo接口获取粉丝列表
	  public function synclistbysina($logger,$next_cursor=0)
	  {
	  	  $total_number = 0;
	  	  $error_count = 0;//粉丝写库错误计数器，当计数大于100时，停止写入数据
	  	  $single_count = 0; 
	  	  try{
	  	  $c = new SaeTClientV2( Utils::$WB_AKEY , Utils::$WB_SKEY ,$this->token);
	  	  while(true){
	  	  	  if($single_count>=1000)//返回客户端一次，以避免30秒超时错误
	  	  	  {
	  	  	  	  break;
	  	  	  }
			  	  $myfans = $c->followers_by_id($this->uid,$next_cursor,200);
			  	  //$logger->err("this->uid==================================".$this->uid);
			  	  //$logger->err(json_encode($myfans));
			  	  if(!empty($myfans["error"]))
			  	  {
			  	  	$logger->err(json_encode($myfans));
			  	  	throw $myfans;	
			  	  }
			  	  //解析列表 并写入数据库
			  	  $users = $myfans["users"];
			  	  for($i=0; $i<count($users); $i++)
			  	  {
			  	  	  $item = $users[$i];
			  	  	  try{
			  	     			//获取注册时间	
			  	     			$ins = "insert into we_weibo_sina_fans(uid,id,idstr,screen_name,name,province,city,gender,verified,followers_count,favourites_count,statuses_count,friends_count,created_at )values(?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
			  	  		    $para = array(
			  	  		        (string)$this->uid,
			  	  		        (int)$item["id"],
			  	  		        (string)$item["idstr"],
			  	  		        (string)$item["screen_name"],
			  	  		        (string)$item["name"],
			  	  		        (int)$item["province"],
			  	  		        (int)$item["city"],
			  	  		        (string)$item["gender"],
			  	  		        (string)$item["verified"],
			  	  		        (int)$item["followers_count"],
			  	  		        (int)$item["favourites_count"],
			  	  		        (int)$item["statuses_count"],
			  	  		        (int)$item["friends_count"],
			  	  		        date("Y-m-d H:i:s",strtotime($item["created_at"]))
			  	  		    );
			  	  		    $this->conn->ExecSQL($ins,$para);
			  	  		    $single_count++;
	  	  		    }
	  	  		    catch(\Exception $e)
	  	  		    {
	  	  		        	$error_count++;
	  	  		        	if($error_count>=100)
	  	  		        	{
	  	  		        	    return array("s"=>1,"next_cursor"=> 0,"total_number"=> $total_number);	
	  	  		        	}
	  	  		    }
	  	  		}
	  	  		$next_cursor = $myfans["next_cursor"];
	  	  		$total_number = $myfans["total_number"];
	  	  		//$logger->err("this->uid==================================".$this->uid." next_cursor:".$next_cursor);
			  	  if(empty($next_cursor) || (int)$next_cursor==0) break;
	  	  }
	  	}
	  	catch(\Exception $e)
	  	{
	  		$logger->err($e);
	  		throw $e;
	  	}
	  	return array("s"=>1,"next_cursor"=> $next_cursor,"total_number"=> $total_number);	
	  }
}
