<?php
namespace Justsy\BaseBundle\Addrlist;

class OperSave
{
	public static function addInternal($da,$login_account,$typeid,$addr_account)
	{
		$s='1';
		$message='';
		if(!empty($addr_account))
			{
					try{
					//判断是否已添加了
					$sql = "select status from we_addrlist_main where owner=? and addr_account=? and status<>'delete'";
					$ds = $da->GetData("has",$sql,array((string)$login_account,(string)$addr_account));
					if($ds && count($ds["has"]["rows"])==0) 
					{
						  $sql="select 1 from we_addrlist_main where owner=? and addr_account=? and typeid=? and status='delete'";
						  $params=array($login_account,$addr_account,$typeid);
						  $ds=$da->Getdata('1',$sql,$params);
						  if($ds['1']['recordcount']==0){
								$id="";
								$sql="insert into we_addrlist_main (id,owner,addr_account,typeid,mid_time,status) values(?,?,?,?,?,'add')";
								$params=array();
								array_push($params,$id);
								array_push($params,$login_account);
								array_push($params,$addr_account);
								array_push($params,$typeid);
								array_push($params,self::microtime_float());
								if(!$da->ExecSQL($sql,$params))
								{
									$s='0';
									$message="添加失败";
								}
							}
							else{
								$sql="update we_addrlist_main set status='add',mid_time=? where owner=? and typeid=? and addr_account=?";
								$params=array(self::microtime_float(),$login_account,$typeid,$addr_account);
								if(!$da->ExecSQL($sql,$params)){
									$s='0';
									$message="添加失败";
								}
							}
				  }
				  else
				  {
				  	$s='0';
				  	$message="该联系人已存在";
				  }
				}
				catch(\Exception $e)
				{
					$s='0';
					$message=$e->getMessage();
				}
			}
			else
			{
				$s='0';
				$message="联系人帐号不能为空";
			}
			return array('s'=>$s,'m'=>$message);
	}
	
	
	public static function addExternal($da,$login_account,$typeid,$name,$unit,$phone,$mobile,$mail)
	{
			$s='1';
			$message="";
			if(!self::validate('mobile',$mobile))
			{
				$s="0";
				$message="不是有效的手机号码";
			}
			else if(!self::validate('mail',$mail))
			{
				$s="0";
				$message="邮箱地址格式不正确";
			}
			else
			{
					try{
					//验证该联系人是否有可能是wefafa
					
					//判断该外部联系人所填信息是否已在于数据库中。
					$sql="select id,status from we_addrlist_addition where owner=? and addr_name=? and addr_unit=? and addr_phone=? and addr_mobile=? and addr_mail=? and status<>'delete'";
					$params=array();
					array_push($params,$login_account);
					array_push($params,$name);
					array_push($params,$unit);
					array_push($params,$phone);
					array_push($params,$mobile);
					array_push($params,$mail);
					$ds=$da->Getdata('status',$sql,$params);
					if($ds['status']['recordcount']==0){
						$sqls=array();
						$params=array();
						$id=\Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_addrlist_addition", "id");
						$sqls[]="insert into we_addrlist_addition (id,owner,addr_name,addr_unit,addr_phone,addr_mobile,addr_mail,mid_time,status) values(?,?,?,?,?,?,?,?,'add')";
						$param=array();
						array_push($param,$id);
						array_push($param,$login_account);
						array_push($param,$name);
						array_push($param,$unit);
						array_push($param,$phone);
						array_push($param,$mobile);
						array_push($param,$mail);
						array_push($param,self::microtime_float());
						$params[]=$param;
						$sqls[]="insert into we_addrlist_main (id,addr_account,owner,typeid,mid_time,status) values(?,'',?,?,?,'add')";
						$params[]=array($id,$login_account,$typeid,self::microtime_float());
						if(!$da->ExecSQLs($sqls,$params))
						{
							$s="0";
							$message="添加失败";
						}
					}
					else{
						$s='0';
						$message="该联系人已存在";
					}
				}
				catch(\Exception $e)
				{
					$s='0';
					$message=$e->getMessage();
				}
			}
			return array('s'=>$s,'m'=>$message);
	}
	
	
	//新加添加联系人
	public static function addExternalPerson($da,$login_account,$typeid,$name,$unit,$phone,$mobile,$mail,$depart,$job,$birthday)
	{
			$s='1';
			$message="";
			$id="";
			if(!empty($mobile) && !self::validate('mobile',$mobile))
			{
				$s="0";
				$message="不是有效的手机号码";
			}
			else if(!empty($mail) && !self::validate('mail',$mail))
			{
				$s="0";
				$message="邮箱地址格式不正确";
			}
			else if (!empty($phone) && self::validate('phone',$phone))
			{
				  $s="0";
				  $message="电话号码格式错误";
			}
			else
			{
					try{
					//验证该联系人是否有可能是wefafa
					
					//判断该外部联系人所填信息是否已在于数据库中。
					$sql="select id,status from we_addrlist_addition where owner=? and addr_name=? and addr_unit=? and addr_phone=? and addr_mobile=? and addr_mail=? and depart=? and job=? and birthday=? and status<>'delete'";
					$params=array();
				  array_push($params,$login_account);
					array_push($params,$name);
					array_push($params,$unit);
					array_push($params,$phone);
					array_push($params,$mobile);
					array_push($params,$mail);
					array_push($params,$depart);
					array_push($params,$job);
					array_push($params,$birthday);
					$ds=$da->Getdata('status',$sql,$params);
					if($ds['status']['recordcount']==0){
						$sqls=array();
						$params=array();
						$id=\Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_addrlist_addition", "id");
						$sqls[]="insert into we_addrlist_addition (id,owner,addr_name,addr_unit,depart,job,addr_phone,addr_mobile,addr_mail,birthday,mid_time,status) values(?,?,?,?,?,?,?,?,?,?,?,'add')";
						$param=array();
						array_push($param,$id);
						array_push($param,$login_account);
						array_push($param,$name);
						array_push($param,$unit);
						array_push($param,$depart);
						array_push($param,$job);
						array_push($param,$phone);
						array_push($param,$mobile);
						array_push($param,$mail);
						array_push($param,$birthday);
						array_push($param,self::microtime_float());
						$params[]=$param;
						$sqls[]="insert into we_addrlist_main (id,addr_account,owner,typeid,mid_time,status) values(?,'',?,?,?,'add')";
						$params[]=array($id,$login_account,$typeid,self::microtime_float());
						if(!$da->ExecSQLs($sqls,$params))
						{
							$s="0";
							$message="添加失败";
						}
					}
					else{
						$s='0';
						$message="该联系人已存在";
					}
				}
				catch(\Exception $e)
				{
					$s='0';
					$message=$e->getMessage();
				}
			}
			return array('s'=>$s,'m'=>$message,'id'=>$id);
	}
	
	public static function delete()
	{
	}
	public static function edit()
	{
	}
	public static function get()
	{
	}
	private static function microtime_float()
	{
   list($usec, $sec) = explode(" ", microtime());
   return (round(((float)$usec + (float)$sec)*1000));
	}
	public static function getTypeId($da)
	{
		$id=\Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_addrlist_type", "typeid");
		return "A".(string)$id;
	}
	public static function validate($classify='',$content='')
	{
		if($classify=='mail')
		{
			return filter_var($content,FILTER_VALIDATE_EMAIL);
		}
		else if($classify=='phone')
		{
			return preg_match("/^((\(\d{2,3}\))|(\d{3}\-))?(\(0\d{2,3}\)|0\d{2,3}-)?[1-9]\d{6,7}(\-\d{1,4})?$/",$content)==1;
		}
		else if($classify=='mobile')
		{
			return preg_match("/^(1[1-9][0-9])\d{8}$/",$content)==1;
		}
//		else if($classify=='phone')
//		{
//			return preg_match("/13[0-9]{9}/",$content)==1|| preg_match("(^(\d{3,4}-)?\d{7,8})",$content)==1;
//		}		  
	}
	public static function getDefaultType()
	{
		
	}
}
?>