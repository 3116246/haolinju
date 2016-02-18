<?php

namespace Justsy\BaseBundle\Management;
use Justsy\BaseBundle\Common\Utils;

class CircleMgr 
{
	  private $conn=null;
	  private $conn_im=null; 
    private $circleid =null;
	  private $grouid = null;
	  
	  public function __construct($_db,$_db_im,$_circleid=null)
	  {
	    $this->conn = $_db;
	    $this->conn_im = $_db_im;	    
	    $this->circleid=$_circleid;
	    $obj = $this->Get(); 
	    if(!empty($obj)) $this->groupid = $obj["fafa_groupid"];
	  }
	  //获取指定圈子的成员总数的最近活跃前5名成员
	  public function GetTopMemberAndCount($circleid=null)
	  {
			  $sql = "select  count(1) member_cnt,ifnull(a.circle_recommend,1) circle_recommend from  we_circle a,we_circle_staff c, we_staff b where a.circle_id=c.circle_id and b.login_account=c.login_account and a.circle_id=?";
		    $params = array();
		    $params[] = empty($circleid)? (string)$this->circleid :(string)$circleid;
		     $ds = $this->conn->GetData("we_count", $sql, $params);
		    $sql1 = "select b.login_account, b.nick_name,b.photo_path,b.photo_path_small,b.photo_path_big from  we_circle a,we_circle_staff c, we_staff b where a.circle_id=c.circle_id and b.login_account=c.login_account and a.circle_id=? order by b.prev_login_date desc limit 0,5";
		    $ds1 = $this->conn->GetData("we_topmember", $sql1, $params);
		    if ($ds["we_count"]["recordcount"] > 0)
		    {
		    	 $re = $ds["we_count"]["rows"][0];
		    	 $re["member_top5"] = $ds1["we_topmember"]["rows"];
		    	 return $re;	
		    }
		    return null;	  	  
	  }
	  
	  public function Get($circleid=null)
	  {
	       $sql = "select a.*, b.nick_name create_staff_name,b.fafa_jid,(select eshortname from we_enterprise where eno=b.eno) create_staff_ename,
  date_format(a.create_date, '%Y-%c-%e') create_date_d 
from we_circle a
left join we_staff b on b.login_account=a.create_staff
where circle_id=?";
    $params = array();
    $params[] = empty($circleid)? (string)$this->circleid :(string)$circleid;
    
    $ds = $this->conn->GetData("we_groups", $sql, $params);
    if (count($ds["we_groups"]["rows"]) > 0) return $ds["we_groups"]["rows"][0];	
    return null;
	  }
	  //判断指定的圈子中是否存在指定的帐号
	  public function IsExist($account)
	  {
	  	  $sql = "select count(1) cnt from we_circle_staff where circle_id=? and login_account=(select distinct login_account from we_staff where login_account=? or openid=? or fafa_jid=?)";
		    $params = array();
		    $params[] = (string)$this->circleid;
		    $params[] = (string)$account;
		    $params[] = (string)$account;
		    $params[] = (string)$account;
		    $ds = $this->conn->GetData("we_groups", $sql, $params);
		    if (count($ds["we_groups"]["rows"]) > 0 && $ds["we_groups"]["rows"][0]["cnt"]>0) return true;	
		    return false;	  	  
	  }
	  
	  public function NicknameIsExist($nickName)
	  {
	  	  $sql = "select count(1) cnt from we_circle_staff where circle_id=? and nick_name=?";
		    $params = array();
		    $params[] = (string)$this->circleid;
		    $params[] = (string)$nickName;
		    $ds = $this->conn->GetData("we_groups", $sql, $params);
		    if (count($ds["we_groups"]["rows"]) > 0 && $ds["we_groups"]["rows"][0]["cnt"]>0) return true;	
		    return false;	  	  
	  }
	  
		//加入圈子
		public function joinCircle($account,$nick_name=null)
		{
	    	$falg = $this->IsExist($account);
	    	if($falg) return false;
	    	$staffMgr = new \Justsy\BaseBundle\Management\Staff($this->conn,$this->conn_im,$account);
	    	$staffObj = $staffMgr->getInfo();
	    	if($staffObj==null) return false;
	    	if(empty($nick_name))
	    	{	    		  
	    	    $nick_name= $staffObj["nick_name"];
	    	}
	    	$falg = $this->NicknameIsExist($nick_name);
	    	if($falg)
	    	{          
	    	  $enoInfo = $staffMgr->getEnInfo();
	        $nick_name = $nick_name."(".$enoInfo["eshortname"].")";
	    	}
        $sql = "insert into we_circle_staff (circle_id,login_account,nick_name) values (?,?,?)";
        $this->conn->ExecSQL($sql,array((string)$this->circleid,(string)$account,(string)$nick_name));
        return true;
	  }	
	  //批量添加多个帐号到圈子
	  public function batchJoinCircle($list)
	  {
	      	$ary = explode(",",$list);
	      	$len = count($ary);	      	
	      	for($i=0; $i< $len; $i++)
	      	{
	      		   $account = $ary[$i];
	      		   $this->joinCircle($account);
	      	}
	      	return true;
	  }
	  
	public function getCircleMembersJid($circleid,$flag_receive_trend=null)
  {
  	  try{
  	  	$fafa_jid = array();
      	$sql = "select c.fafa_jid from we_circle_staff a,we_staff c where a.login_account=c.login_account and a.circle_id=? and c.fafa_jid is not null";
      	$da = $this->conn;
        if(!empty($flag_receive_trend))
        {
            	if($flag_receive_trend=="1") //
            	{
            	   	$sql .= " and (flag_receive_trend is null or flag_receive_trend='".$flag_receive_trend."')";//未设置是否接收动态通知时，默认为允许接收
            	}
            	else
            	{
            	    $sql .= " and flag_receive_trend='".$flag_receive_trend."'";	
            	}
        }      	
      	$ds = $da->GetData("ims",$sql,array((string)$circleid));
      	for($i=0;$i<count($ds["ims"]["rows"]); $i++)
      	{
      		  $fafa_jid[]=$ds["ims"]["rows"][$i]["fafa_jid"];
      	}
      	return $fafa_jid;
      }
      catch(\Exception $e)
      {
      	  return  null;
      }
  }	 
  
	public function getCircleMembers($circleid,$staff=null)
  {
  	  try{
  	    $da = $this->conn;
  	    if($circleid=="9999")
  	    {
	        $sql = " select a.login_account,c.nick_name,c.photo_path,c.eno,c.fafa_jid from we_staff_atten a,we_staff c where a.login_account=c.login_account and a.atten_type='01' and a.atten_id=?";
	        $table = $da->GetData("group",$sql,array((String)$staff));
	        return $table["group"]["rows"];  	    	
  	    }
  	    else{
	        $sql = " select a.login_account,c.nick_name,c.photo_path,c.eno,c.fafa_jid from we_circle_staff a,we_circle b,we_staff c where b.circle_id=a.circle_id and a.login_account=c.login_account and b.circle_id=?";
	        $table = $da->GetData("group",$sql,array((String)$circleid));
	        return $table["group"]["rows"];
        }
      }
      catch(\Exception $e)
      {
      	  return  null;
      }
  }

  public function getCircleList($login_account){
  	$sql="select DISTINCT a.circle_name,a.circle_id,b.flag_receive_trend as hint from we_circle a LEFT JOIN we_circle_staff b ON a.circle_id=b.circle_id WHERE b.login_account=? and a.circle_id!=10000";
  	$para=array($login_account);
  	$data=$this->conn->GetData('dt',$sql,$para);
  	$list['rows']=array();
  	$list['count']=0;
  	if($data!=null && count($data['dt']['rows'])>0) {
  		$list['rows']=$data['dt']['rows'];
  		$list['count']=count($data['dt']['rows']);
  	}
  	return $list;
  }

  public function setHint($user,$action)
  {
  	try {
      	$sql = "update we_circle_staff set flag_receive_trend=? where circle_id=? and login_account=?";
      	$para=array((string)$action,(string)$this->circleid,(string)$user->getUserName());
      	$this->conn->ExecSQL($sql,$para);
      	
      	$sql_im = "update im_groupemployee set flag_receive_trend=? where groupid=? and employeeid=?";
      	$para_im=array((string)$action,(string)$this->groupid,(string)$user->fafa_jid);
      	//var_dump($sql_im,$para_im);
      	$this->conn_im->ExecSQL($sql_im,$para_im);  
      	return true;
  	} catch (\Exception $e) {
  		//$this->get('logger')->err($e->getMessage());
  	} 
  	return false;
  }  
}
