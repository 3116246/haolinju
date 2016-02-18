<?php
namespace Justsy\BaseBundle\Management;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\Cache_Enterprise;
use Justsy\BaseBundle\Management\Dept;
class HrAttendance implements IBusObject
{
	private $container=null;
	private $logger=null;
	private $conn=null;
	private $conn_im=null;

	public function __construct($container)
	{
		$this->container=$container;
		$this->logger=$container->get("logger");
		$this->conn = $container->get("we_data_access");
		$this->conn_im = $container->get("we_data_access_im");
	}

	public function getInstance($container)
	{
  		return new self($container);
	}

	public function checkinAtten($paramObj)
	{
	    try 
	    {
	    	$user = $paramObj["user"];
	    	$eno = $user->eno;
		    $bizdata = $paramObj["location"];
		    $memo = trim($paramObj["memo"]);
		    $attachment = $paramObj["attachment"];

		    $this->logger->err("openid:".$user->getUsername());
		    $this->logger->err("location:".$bizdata);
		    $arrBizdata = explode(',', $bizdata);
		    if (count($arrBizdata) < 3)
		    {
		        return Utils::WrapResultError('位置数据不正确');
		    }
		    $check = $this->getStaffAttenByDate(array('user'=>$user));		    
		    if($check['returncode']!='0000')
		    	return $check;
		    $params = array();
		    if(count($check['data'])>1)
		    {
		    	//已经打过卡了，则覆盖最后一条记录
		    	$lastid = $check['data'][1]['id'];
		    	$sql = 'update ma_checkatten set atten_date=now(),latitude=?, longitude=?, address=?,memo=?,  attachment=?,atten_state=? where id=?';
			    $params[] = (float)$arrBizdata[0];
			    $params[] = (float)$arrBizdata[1];
			    $params[] = (string)$arrBizdata[2];
			    $params[] = (string)$memo;
			    $params[] = (string)$attachment;
			    $params[] = '0';
			    $params[] = (string)$lastid; 
		    }
		    else
		    {
			    $sql = "insert into ma_checkatten(eno,check_date, staff_id, atten_date, latitude, longitude, address,memo,  attachment, atten_state)
			       select ?, curdate(), ?, now(), ?, ?, ?, ?, ?, case when now() > date_add(curdate(), interval 9 hour) then '1' else '0' end";
			   
			   $params[] = (string)$user->eno;
			    $params[] = (string)$user->getUsername();
			    $params[] = (float)$arrBizdata[0];
			    $params[] = (float)$arrBizdata[1];
			    $params[] = (string)$arrBizdata[2];
			    $params[] = (string)$memo;
			    $params[] = (string)$attachment;
		    }
		    $this->conn->ExecSQL($sql, $params);    
	    } 
	    catch (\Exception $e) 
	    {
	      $this->logger->err($e);
	      return Utils::WrapResultError($e->getMessage());
	    }
	    return Utils::WrapResultOK('');
	}

	//获取指定人员指定日期的考勤记录
	//默认为当天
	public function getStaffAttenByDate($paramObj)
	{
		$user =  $paramObj['user'];
		$staff = isset($paramObj['staff']) ? $paramObj['staff'] : '';
		$nick_name = '';
		$eno = '';
		if(empty($staff))
		{
			$staff = $user->getUsername();
			$nick_name = $user->nick_name;
			$eno = $user->eno;
		}
		else
		{
			$staffObj = new Staff($this->conn,$this->conn_im,$staff,$this->logger,$this->container);
			$user = $staffObj->getInfo();
			if(empty($user))
			{
				return Utils::WrapResultError('无效的人员帐号');
			}
			$staff = $user['login_account'];
			$nick_name = $user['nick_name'];
			$eno = $user['eno'];
		}
		$ymd =isset($paramObj['ymd']) ? $paramObj['ymd'] : '';
		if(empty($ymd))
		{
			$ymd = date('Y-m-d');
		}
		$fileurl=$this->container->getParameter("FILE_WEBSERVER_URL");
		$sql = 'select a.*,? nick_name,case attachment when \'\' then \'\' else concat(\''.$fileurl.'\',attachment) end file from (select * from ma_checkatten where staff_id=? and check_date=? and eno=?) a order by a.id';
		$ds = $this->conn->GetData('t',$sql,array((string)$nick_name,(string)$staff,(string)$ymd,$eno));
		return Utils::WrapResultOK( count($ds['t']['rows']) >0 ? $ds['t']['rows'] : array());
	}
	//获取指定人员指定周的考勤记录
	//默认为当前周
	public function getStaffAttenByWeek($paramObj)
	{
		$user =  $paramObj['user'];
		$staff = isset($paramObj['staff']) ? $paramObj['staff'] : '';
		$eno = $user->eno;
		if(empty($staff))
		{
			$staff = $user->getUsername();
			$nick_name = $user->nick_name;
		}
		else
		{
			$staffObj = new Staff($this->conn,$this->conn_im,$staff,$this->logger,$this->container);
			$user = $staffObj->getInfo();
			if(empty($user))
			{
				return Utils::WrapResultError('无效的人员帐号');
			}
			$staff = $user['login_account'];
			$nick_name = $user['nick_name'];
		}
		$ymd = $paramObj['ymd'];
		if(empty($ymd))
		{
			$date=date('Y-m-d');
			$week=$this->getYearWeek($date);
		}
		else
		{
			$week=$this->getYearWeek($ymd);
		}
		
		$fileurl=$this->container->getParameter("FILE_WEBSERVER_URL");
		$sql = 'select a.*,? nick_name,case attachment when \'\' then \'\' else concat(\''.$fileurl.'\',attachment) end file from (select *,date_format(atten_date,\'%X%V\') week,dayofweek(atten_date)-1 weekday from ma_checkatten where staff_id=? and eno=?) a where a.week=? order by a.id';
		$ds = $this->conn->GetData('t',$sql,array((string)$nick_name,(string)$staff,(string)$eno,(string)$week));
		return Utils::WrapResultOK( count($ds['t']['rows']) >0 ? $ds['t']['rows'] : array());
	}

	public function getStaffAttenByMonth($paramObj)
	{
		$user =  $paramObj['user'];
		$staff = isset($paramObj['staff']) ? $paramObj['staff'] : '';
		$eno = $user->eno;
		if(empty($staff))
		{
			$staff = $user->getUsername();
			$nick_name = $user->nick_name;
		}
		else
		{
			$staffObj = new Staff($this->conn,$this->conn_im,$staff,$this->logger,$this->container);
			$user = $staffObj->getInfo();
			if(empty($user))
			{
				return Utils::WrapResultError('无效的人员帐号');
			}
			$staff = $user['login_account'];
			$nick_name = $user['nick_name'];
		}
		$ymd = $paramObj['ymd'];
		if(empty($ymd))
		{
			$date=strtotime(date('Y-m-01'));
		}
		else
		{
			$date = strtotime($ymd);
		}
		$first = date('Y-m-01', $date).' 00:00:01';
		$last = date('Y-m-d', strtotime($first . ' +1 month -1 day')).' 23:59:59';
		$fileurl=$this->container->getParameter("FILE_WEBSERVER_URL");
		$sql = 'select a.*,? nick_name,case attachment when \'\' then \'\' else concat(\''.$fileurl.'\',attachment) end file from (select *,date_format(atten_date,\'%Y-%m-%d\') day from ma_checkatten where staff_id=? and eno=? and atten_date between ? and ?) a order by a.id';
		$ds = $this->conn->GetData('t',$sql,array((string)$nick_name,(string)$staff,(string)$eno,(string)$first,(string)$last));
		return Utils::WrapResultOK( count($ds['t']['rows']) >0 ? $ds['t']['rows'] : array());		
	}	

	//统计所有人员指定日期的考勤记录
	//默认为当天(手机端)
	public function getAllAttenByDate($paramObj)
	{

		$user =  $paramObj['user'];
		$ymd = $paramObj['ymd'];
		$eno = $user->eno;
		// $limit = $paramObj['limit'];
		// $pageIndex = $paramObj['pageIndex'];
		if(empty($ymd))
		{
			$ymd = date('Y-m-d');
		}
		$sql = 'select a.* from (select * from ma_checkatten where check_date=? and eno=?) a group by staff_id order by a.id';
		/*
		$sql = ' select c.staff_id,
					case when min(time(c.atten_date))<max(time(c.atten_date)) then concat(\'签到:\',min(time(c.atten_date)),\' — 签退:\',max(time(c.atten_date))) else concat(\'签到:\',time(c.atten_date)) end atten_date,
				    c.address,
				    case
			        when time(min(c.atten_date)) > TIME(\''.$late_time.'\') then \'迟到\'
			        when time(max(c.atten_date)) < TIME(\''.$offwork_time.'\') then \'早退\'
			        when time(now())>TIME(\''.$offwork_time.'\') and min(c.atten_date) = max(c.atten_date) then \'矿工\'
			        else \'正常\'
			   		end as state

				 from ma_checkatten c , ma_checkatten_setup s
				 where c.check_date=? order by c.atten_date, c.staff_id 
				 limit '
				 .($pageIndex-1)*$limit.' , '.$limit;
				 */
		$da = $this->conn;
		$da_im = $this->conn_im;
		$ds = $da->GetData('t',$sql,array((string)$ymd,(string)$user->eno));
		
		$staff = new Staff($da,$da_im,$user,$this->container->get("logger"),$this->container);

		foreach ($ds['t']['rows'] as $key => $value) {
			$staffinfo = $staff->getStaffInfo($ds['t']['rows'][$key]['staff_id']);
			$ds['t']['rows'][$key]['nick_name'] = $staffinfo['nick_name'];
			$ds['t']['rows'][$key]['dept_name'] = $staffinfo['dept_name'];
			$ds['t']['rows'][$key]['mobile_bind'] = $staffinfo['mobile_bind'];
			$ds['t']['rows'][$key]['photo_path'] = $staffinfo['photo_path'];
		}

		return Utils::WrapResultOK( count($ds['t']['rows']) >0 ? $ds['t']['rows'] : array());
	}

	private function getDeptSql($deptid){
		$da = $this->conn;
		$da_im = $this->conn_im;
		$dept = new Dept($da,$da_im);
		$deptids = $dept->getAllChild($deptid);

		if(empty($deptid))
		{
			$deptid = '1=1';
		}else{
			$deptid = 's.dept_id in (';
			$isFirst = true;
			foreach ($deptids as $key => $value) {
				if($isFirst){
					$isFirst = false;
				}else{
					$deptid.=',';
				}
				$deptid.='"';
				$deptid.=$value['deptid'];
				$deptid.='"';
			}
			$deptid.=')';
		}
		return $deptid;
	}

	//查询指定日期的实际考勤人员记录
	//默认为当天(web端)
	public function getAllAttend($paramObj)
	{

		$user =  $paramObj['user'];
		$eno = $user->eno;
		$ymd = $paramObj['ymd'];
		$limit = $paramObj['limit'];
		$deptid = $this->getDeptSql($paramObj['deptid']);
		$pageIndex = $paramObj['pageIndex'];

		$da = $this->conn;
		$da_im = $this->conn_im;

		if(empty($ymd))
		{
			$ymd = date('Y-m-d');
		}
		$getatten_setup = $this->getatten_setup($paramObj);
		if($getatten_setup['returncode']!='0000' || count($getatten_setup['data'])==0)
		{
			return Utils::WrapResultError('未正确的设置考勤参数');
		}
		$late_time = $getatten_setup['data'][0]['late_time'];
		$offwork_time = $getatten_setup['data'][0]['offwork_time'];		
		// $sql = 'select a.* from (select * from ma_checkatten where check_date=?) a group by staff_id order by a.id';
		
		$sql ='	
			select 
			    c.staff_id,
			    case when min(time(c.atten_date))<max(time(c.atten_date)) then concat(\'签到:\',min(time(c.atten_date)),\' — 签退:\',max(time(c.atten_date))) else concat(\'签到:\',time(c.atten_date)) end atten_date,
			    c.address,
			    case
			    	when date(c.atten_date)<date (now()) or time(now())>"'.$offwork_time.'"
			    		then (
						 	case 
							 	when count(1)<2 then "旷工"
						 		when min(time(c.atten_date))>=time("'.$late_time.'") then "迟到"
						 		when max(time(c.atten_date))<=time("'.$offwork_time.'") then "早退"
						 		else "正常"
						 		end
						 )
			      else 
			      	(
						 	case 
						 		when min(time(c.atten_date))<time("0'.$late_time.'") then "正常"
						 		when min(time(c.atten_date))>=time("'.$late_time.'") then "迟到"
						 		when max(time(c.atten_date))<=time("'.$offwork_time.'") then "早退"
						 		else "正常"
						 		end
						 )
			    end as state
			from
			    ma_checkatten c inner join we_staff s on c.staff_id=s.login_account 
			where
			    c.check_date = ? 
			    and c.eno = ?
			and #deptid
			group by staff_id 
			order by c.atten_date 
			limit '.($pageIndex-1)*$limit.' , '.$limit;

			$sql = str_replace("#deptid", $deptid, $sql);
			
		/*
		$sql = ' select c.staff_id,time(c.atten_date) atten_date,'
					.' case '
					.' when TIME_TO_SEC (time(min(c.atten_date)))>TIME_TO_SEC(\''.$late_time.'\') then "迟到"'
					.' when TIME_TO_SEC (time(c.atten_date))>=TIME_TO_SEC(\''.$offwork_time.'\') then "正常"'
					.' else "打卡异常"'
					.' end as state '
				.' from ma_checkatten c'
				.' where c.check_date=? group by staff_id order by c.atten_date, c.staff_id '
				.' limit '.($pageIndex-1)*$limit.' , '.$limit;
		*/
		
		$ds = $da->GetData('t',$sql,array((string)$ymd,(string)$eno));
		
		$staff = new Staff($da,$da_im,$user,$this->container->get("logger"),$this->container);

		foreach ($ds['t']['rows'] as $key => $value) {
			$staffinfo = $staff->getStaffInfo($ds['t']['rows'][$key]['staff_id']);
			$ds['t']['rows'][$key]['nick_name'] = $staffinfo['nick_name'];
			$ds['t']['rows'][$key]['dept_name'] = $staffinfo['dept_name'];
			$ds['t']['rows'][$key]['mobile_bind'] = $staffinfo['mobile_bind'];
			$ds['t']['rows'][$key]['photo_path'] = $staffinfo['photo_path'];
		}
		
		return Utils::WrapResultOK( count($ds['t']['rows']) >0 ? $ds['t']['rows'] : array());
	}
	//查询指定日期的实际考勤人员记录数量
	//默认为当天(web端)
	public function getCountAllAttend($paramObj){
		
		$ymd = $paramObj['ymd'];
		$deptid = $this->getDeptSql($paramObj['deptid']);
		$user =  $paramObj['user'];
		$eno = $user->eno;

		if(empty($ymd))
		{
			$ymd = date('Y-m-d');
		}

		$sql ='select count(1) count
			from (select c.staff_id
			from
			    ma_checkatten c left join we_staff s on c.staff_id=s.login_account 
			    and s.login_account not in (select login_account from we_service)
			where
			    c.check_date = ?
			    and c.eno = ?
			    and #deptid
			group by c.staff_id ) temp';
			
		$sql = str_replace("#deptid", $deptid, $sql);
		$da = $this->conn;
		$ds = $da->GetData('t',$sql,array((string)$ymd,(string)$eno));
		return Utils::WrapResultOK( count($ds['t']['rows']) >0 ? $ds['t']['rows'][0]['count'] : array());
	}

	//统计所有人（已考勤，未考勤）的记录
	public function getAllByDate($paramObj){
		$user =  $paramObj['user'];
		$eno = $user->eno;
		$ymd = $paramObj['ymd'];
		$limit = $paramObj['limit'];
		$pageIndex = $paramObj['pageIndex'];
		$deptid = $this->getDeptSql($paramObj['deptid']);
		
		$da = $this->conn;
		$da_im = $this->conn_im;
		

		if(empty($ymd))
		{
			$ymd = date('Y-m-d');
		}
		$getatten_setup = $this->getatten_setup($paramObj);
		if($getatten_setup['returncode']!='0000' || count($getatten_setup['data'])==0)
		{
			return Utils::WrapResultError('未正确的设置考勤参数');
		}
		$late_time = $getatten_setup['data'][0]['late_time'];
		$offwork_time = $getatten_setup['data'][0]['offwork_time'];
		
		$sql = ' select temp.login_account,c.address,
				if(c.staff_id is null ,"未考勤",
			    (case
			    	when date(c.atten_date)<date (now()) or time(now())>"18:00"
			    		then (
						 	case 
							 	when count(1)<2 then "旷工"
							 	when min(time(c.atten_date))<time("'.$late_time.'") then "正常"
						 		when min(time(c.atten_date))>=time("'.$late_time.'") then "迟到"
						 		when max(time(c.atten_date))<=time("'.$offwork_time.'") then "早退"
						 		else "正常"
						 		end
						 )
			      else 
			      	(
						 	case 
						 		when min(time(c.atten_date))<time("'.$late_time.'") then "正常"
						 		when min(time(c.atten_date))>=time("'.$late_time.'") then "迟到"
						 		when max(time(c.atten_date))<=time("'.$offwork_time.'") then "早退"
						 		else "正常"
						 		end
						 )
			    end
			    ))  state,
				if(c.staff_id is null ,"未考勤",
			    (case
			    	when min(time(c.atten_date))<max(time(c.atten_date)) then concat(\'签到:\',min(time(c.atten_date)),\' — 签退:\',max(time(c.atten_date))) 
			    	else concat(\'签到:\',time(c.atten_date)) 
			    end)) atten_date

				 from ma_checkatten c right join (select login_account
				 from we_staff s
				 where #deptid and s.eno=? 
				 and s.login_account not in (select login_account from we_service) 
				 limit '
				 .($pageIndex-1)*$limit.', '.$limit.
				 ') temp on c.check_date=? and c.staff_id=temp.login_account and c.eno = ?
				  group by temp.login_account';
		$sql = str_replace("#deptid", $deptid, $sql);
		$ds = $da->GetData('t',$sql,array((string)$eno,(string)$ymd,(string)$eno));

		$staff = new Staff($da,$da_im,$user,$this->container->get("logger"),$this->container);

		foreach ($ds['t']['rows'] as $key => $value) {
			$staffinfo = $staff->getStaffInfo($value['login_account']);
			$ds['t']['rows'][$key]['nick_name'] = $staffinfo['nick_name'];
			$ds['t']['rows'][$key]['dept_name'] = $staffinfo['dept_name'];
			$ds['t']['rows'][$key]['mobile_bind'] = $staffinfo['mobile_bind'];
			$ds['t']['rows'][$key]['photo_path'] = $staffinfo['photo_path'];
		}

		return Utils::WrapResultOK( count($ds['t']['rows']) >0 ? $ds['t']['rows'] : array());
						
	}
	//统计所有人（已考勤，未考勤）的数量
	public function getCountAllByDate($paramObj){

		$sql ='	select count(1) count from we_staff s where #deptid and eno=? 
				and s.login_account not in (select login_account from we_service)';
		$user =  $paramObj['user'];
		$eno = $user->eno;
		$deptid = $this->getDeptSql($paramObj['deptid']);
		
		$sql = str_replace("#deptid", $deptid, $sql);
		$da = $this->conn;
		$ds = $da->GetData('t',$sql,array((string)$eno));
		return Utils::WrapResultOK( count($ds['t']['rows']) >0 ? $ds['t']['rows'][0]['count'] : array());
	}

	//统计当天所有迟到的人
	public function getAllLate($paramObj){

		$user =  $paramObj['user'];
		$eno = $user->eno;
		$ymd = $paramObj['ymd'];
		$limit = $paramObj['limit'];
		$pageIndex = $paramObj['pageIndex'];
		$deptid = $this->getDeptSql($paramObj['deptid']);
		$da = $this->conn;
		$da_im = $this->conn_im;
		
		if(empty($ymd))
		{
			$ymd = date('Y-m-d');
		}
		$getatten_setup = $this->getatten_setup($paramObj);
		if($getatten_setup['returncode']!='0000' || count($getatten_setup['data'])==0)
		{
			return Utils::WrapResultError('未正确的设置考勤参数');
		}
		$late_time = $getatten_setup['data'][0]['late_time'];
		$offwork_time = $getatten_setup['data'][0]['offwork_time'];	
		
		$sql = 'select temp.staff_id,"迟到" state,concat("签到:",time(min(temp.atten_date))) atten_date,temp.atten_date
				from (select c.staff_id,c.atten_date,c.address
					from ma_checkatten c 
					where c.check_date=? and c.eno = ?) as temp
				left join we_staff s on temp.staff_id = s.login_account
				where #deptid
				and s.eno = ?
				group by temp.staff_id
				having  count(1)>(
					case 
					when date(temp.atten_date)<date(now()) then 1 
					when time(now())<time("'.$offwork_time.'") then 0
					else 1
					end
				) and time(min(temp.atten_date))>="'.$late_time.'"'
				.' limit '.($pageIndex-1)*$limit.', '.$limit;
		$sql = str_replace("#deptid", $deptid, $sql);
		$ds = $da->GetData('t',$sql,array((string)$ymd,(string)$eno,(string)$eno));

		$staff = new Staff($da,$da_im,$user,$this->container->get("logger"),$this->container);

		foreach ($ds['t']['rows'] as $key => $value) {
			$staffinfo = $staff->getStaffInfo($ds['t']['rows'][$key]['staff_id']);
			$ds['t']['rows'][$key]['nick_name'] = $staffinfo['nick_name'];
			$ds['t']['rows'][$key]['dept_name'] = $staffinfo['dept_name'];
			$ds['t']['rows'][$key]['mobile_bind'] = $staffinfo['mobile_bind'];
			$ds['t']['rows'][$key]['photo_path'] = $staffinfo['photo_path'];
		}

		return Utils::WrapResultOK( count($ds['t']['rows']) >0 ? $ds['t']['rows'] : array());
	}

	//统计当天所有迟到人的数量
	public function getCountAllLate($paramObj){
		$ymd = $paramObj['ymd'];
		$deptid = $this->getDeptSql($paramObj['deptid']);
		$user =  $paramObj['user'];
		$eno = $user->eno;
		if(empty($ymd))
		{
			$ymd = date('Y-m-d');
		}
		
		$getatten_setup = $this->getatten_setup($paramObj);
		if($getatten_setup['returncode']!='0000' || count($getatten_setup['data'])==0)
		{
			return Utils::WrapResultError('未正确的设置考勤参数');
		}
		$late_time = $getatten_setup['data'][0]['late_time'];
		$offwork_time = $getatten_setup['data'][0]['offwork_time'];	

		$sql = 'select count(1) count
				from 
				(select c.staff_id,c.atten_date
					from ma_checkatten c 
					left join we_staff s on c.staff_id = s.login_account
					where c.check_date=?
					and  #deptid
					and c.eno = ?
					group by staff_id
					having count(1)>(
						case 
						when date(c.atten_date)<date(now()) then 1 
						when time(now())<time("'.$offwork_time.'") then 0
						else 1
						end
					) '.' and time(min(c.atten_date))>='.'"'.$late_time.'"'
				
				.' ) as temp';
		$sql = str_replace("#deptid", $deptid, $sql);
		$da = $this->conn;
		$ds = $da->GetData('t',$sql,array((string)$ymd,(string)$eno));
		return Utils::WrapResultOK( count($ds['t']['rows']) >0 ? $ds['t']['rows'][0]['count'] : array());
	}

	//统计当天未考勤的人
	public function getNoAtten($paramObj){
		$user =  $paramObj['user'];
		$eno = $user->eno;
		$ymd = $paramObj['ymd'];
		$deptid = $this->getDeptSql($paramObj['deptid']);
		$limit = $paramObj['limit'];
		$pageIndex = $paramObj['pageIndex'];
		$da = $this->conn;
		$da_im = $this->conn_im;
		if(empty($ymd))
		{
			$ymd = date('Y-m-d');
		}
		
		$getatten_setup = $this->getatten_setup($paramObj);
		if($getatten_setup['returncode']!='0000' || count($getatten_setup['data'])==0)
		{
			return Utils::WrapResultError('未正确的设置考勤参数');
		}
		$late_time = $getatten_setup['data'][0]['late_time'];
		$offwork_time = $getatten_setup['data'][0]['offwork_time'];	
		
		$sql = 'select temp.staff_id ,"未考勤" state ,"未考勤" atten_date
				from (
					 select s.login_account staff_id
					 from we_staff s
					 where #deptid
					 and s.eno=?
					 and s.login_account not in (
						 select c.staff_id
						 from ma_checkatten c
						 where c.check_date=?
						 and c.eno = ?
						 group by c.staff_id
						 union all
						 select login_account from we_service
					 )
					 ) temp
					 limit '
					.($pageIndex-1)*$limit.' , '.$limit;
		$sql = str_replace("#deptid", $deptid, $sql);
		$ds = $da->GetData('t',$sql,array((string)$eno,(string)$ymd,(string)$eno));

		$staff = new Staff($da,$da_im,$user,$this->container->get("logger"),$this->container);

		foreach ($ds['t']['rows'] as $key => $value) {
			$staffinfo = $staff->getStaffInfo($ds['t']['rows'][$key]['staff_id']);
			$ds['t']['rows'][$key]['nick_name'] = $staffinfo['nick_name'];
			$ds['t']['rows'][$key]['dept_name'] = $staffinfo['dept_name'];
			$ds['t']['rows'][$key]['mobile_bind'] = $staffinfo['mobile_bind'];
			$ds['t']['rows'][$key]['photo_path'] = $staffinfo['photo_path'];
		}

		return Utils::WrapResultOK( count($ds['t']['rows']) >0 ? $ds['t']['rows'] : array());
	}

	//统计当天未考勤人的数量
	public function getCountNoAtten($paramObj){
		$ymd = $paramObj['ymd'];
		$deptid = $this->getDeptSql($paramObj['deptid']);
		$user =  $paramObj['user'];
		$eno = $user->eno;
		if(empty($ymd))
		{
			$ymd = date('Y-m-d');
		}

		
		$getatten_setup = $this->getatten_setup($paramObj);
		if($getatten_setup['returncode']!='0000' || count($getatten_setup['data'])==0)
		{
			return Utils::WrapResultError('未正确的设置考勤参数');
		}
		$late_time = $getatten_setup['data'][0]['late_time'];
		$offwork_time = $getatten_setup['data'][0]['offwork_time'];	

		$sql = 'select count(1) count
				from (
					 select s.login_account staff_id
					 from we_staff s
					 where  #deptid
					 and s.eno = ?
					 and s.login_account not in (
						 select c.staff_id
						 from ma_checkatten c
						 where c.check_date=?
						 and c.eno = ?
						 group by c.staff_id
						 union all
						 select login_account from we_service
					 )
					 ) temp ';
		$sql = str_replace("#deptid", $deptid, $sql);
		$da = $this->conn;
		$ds = $da->GetData('t',$sql,array((string)$eno,(string)$ymd,(string)$eno));
		return Utils::WrapResultOK( count($ds['t']['rows']) >0 ? $ds['t']['rows'][0]['count'] : array());
	}

	public function getAllAtten($params){
		$type = $params['type'];
		
		if($type=="allAttenByDate"){
			return $this->getAllAttend($params);
		}
		if($type=="allByDate"){
			return $this->getAllByDate($params);
		}
		if($type=="allLate"){
			return $this->getAllLate($params);
		}
		if($type=="noAtten"){
			return $this->getNoAtten($params);
		}

	}

	public function getCount($params){
		$type = $params['type'];
		
		if($type=="allAttenByDate"){
			return $this->getCountAllAttend($params);
		}
		if($type=="allByDate"){
			return $this->getCountAllByDate($params);
		}
		if($type=="allLate"){
			return $this->getCountAllLate($params);
		}
		if($type=="noAtten"){
			return $this->getCountNoAtten($params);
		}
	}

	public function sendStatData($paramObj)
	{
		$user = $paramObj['user'];
		$ymd = $paramObj['ymd'];
		$eno = $user->eno;
		if(empty($ymd))
			$ymd = date('Y-m-d');
		$deptid = $paramObj['deptid'];
		$staffs = $paramObj['staff'];
		if(empty($deptid))
		{
			$dept_name = '公司全员';
		}
		else
		{
			$deptMgr = new Dept($this->conn,$this->conn_im,$this->container);
			$deptdata = $deptMgr->getInfo($deptid);
			$dept_name = $deptdata['deptname'];
		}
		$delay = $paramObj['delay']; //迟到人数
		$notattendance = $paramObj['notattendance'];//未考勤人数
		$hosturl = $_SERVER['HTTP_HOST'];

		$http = $_SERVER['HTTPS']!='on' ? 'http://' : 'https://';
		$senddata = array();// 'this is a test.click here http://112.126.77.162:8000/admin/theme/index.html';
		$senddata['title'] = $dept_name.'考勤统计报表-'.$ymd;
		$senddata['text'] = '今日迟到 '.$delay.' 人，未考勤 '.$notattendance.' 人，...';
		$senddata['img'] = $http.$hosturl.'/admin/theme/images/notes.png';
		$senddata['source'] = '考勤管理系统';
		$senddata['link'] = $http.$hosturl.'/admin/theme/modules/mobile/index.html?page=hr_attendance&ymd='.$ymd.'&deptid='.$deptid;

		$messagedata = Utils::WrapShearMessage('eim-chat-link',$senddata,$senddata['title']);
		
		$msg = Utils::WrapChatMessageXml($user->fafa_jid,$user->nick_name,$messagedata);
		Utils::sendChatMessage($user->fafa_jid,$staffs,$msg,$this->container);
		return Utils::WrapResultOK('');
	}

	public function export($paramObj)
	{
		$hosturl = $_SERVER['HTTP_HOST'];
		$now=date('Ymd');
		$dir =explode('src', __DIR__);
		$source = $dir[0].'web/EN_Hr_attendanceTemplate.xls';
		$tmpFile = $dir[0].'web/upload/attendance'.$now.'.xls';
		if(file_exists($tmpFile))
		{
			
			//return Utils::WrapResultOK($hosturl.'/upload/attendance'.$now.'.xls');
		}
		$user = $paramObj['user'];
		$ymd = $paramObj['ymd'];
		if(empty($ymd))
			$ymd = date('Y-m-d');
		$deptid = $paramObj['deptid'];
		if(empty($deptid))
		{
			$dept_name = '公司全员';
		}
		else
		{
			$deptMgr = new Dept($this->conn,$this->conn_im,$this->container);
			$deptdata = $deptMgr->getInfo($deptid);
			$dept_name = $deptdata['deptname'];
		}
		$all = $paramObj['all'];//应考勤人数
		$attendanced = $paramObj['attendanced'];//实际考勤人数
		$delay = $paramObj['delay']; //迟到人数
		$notattendance = $paramObj['notattendance'];//未考勤人数		
		@copy($source, $tmpFile);
		$objReader = \PHPExcel_IOFactory::createReader( 'Excel5'); 

		$objPHPExcel = $objReader->load($tmpFile);
		$objPHPExcel->setActiveSheetIndex(0);
		$objWorksheet = $objPHPExcel->getActiveSheet();
		$str  = '考勤统计';//iconv('gb2312', 'utf-8', '考勤统计');
		$objWorksheet->setTitle($str);
		$objPHPExcel->setActiveSheetIndex(1);
		$objWorksheet = $objPHPExcel->getActiveSheet();
		$str  = '实际考勤人数明细';//iconv('gb2312', 'utf-8', '实际考勤人数明细');
		$objWorksheet->setTitle($str);
		$objPHPExcel->setActiveSheetIndex(2);
		$objWorksheet = $objPHPExcel->getActiveSheet();
		$str  = '未考勤人数明细';//iconv('gb2312', 'utf-8', '未考勤人数明细');
		$objWorksheet->setTitle($str);
		$objPHPExcel->setActiveSheetIndex(3);
		$objWorksheet = $objPHPExcel->getActiveSheet();
		$str  = '迟到考勤人数明细';//iconv('gb2312', 'utf-8', '迟到考勤人数明细');
		$objWorksheet->setTitle($str);
		$objPHPExcel->setActiveSheetIndex(0);
		$objWorksheet = $objPHPExcel->getActiveSheet();
		//写入部门和日期
		$objWorksheet->unmergeCells('F3:I3');
		$objWorksheet->unmergeCells('J3:L3');
		$objWorksheet->setCellValue('F3', ($dept_name));
		$objWorksheet->setCellValue('J3', ($ymd));
		$objWorksheet->mergeCells('F3:I3');
		$objWorksheet->mergeCells('J3:L3');
		//统计 人
		$objWorksheet->setCellValue('K27', ($user->nick_name));
		//写入应考勤人数
		$objWorksheet->setCellValue('G10', $all);
		//实际考勤人数
		$objWorksheet->setCellValue('K10', $attendanced);
		//未考勤人数
		$objWorksheet->setCellValue('G16', $notattendance);
		//迟到考勤人数
		$objWorksheet->setCellValue('K16', $delay);

		$objWorksheet->setCellValue('G22', ('—'));
		$objWorksheet->setCellValue('K22', ('—'));
		$paramObj['pageIndex']=1;
		$paramObj['limit']=1000000;
		//判断是否需要导入实际考勤明细
		$expFlag = isset($paramObj['expdetail_attendanced']) ? (int)$paramObj['expdetail_attendanced'] : 0;
		if($expFlag===1)
		{
			$objPHPExcel->setActiveSheetIndex(1);
			$objWorksheet = $objPHPExcel->getActiveSheet();
			$data = $this->getAllAttend($paramObj);
			$i=3;
			foreach ($data['data'] as $key => $value) {
				$objWorksheet->setCellValue('A'.$i, $i-2);
				$objWorksheet->setCellValue('B'.$i, ($value['nick_name']));
				$objWorksheet->setCellValue('C'.$i, ($value['dept_name']));
				$objWorksheet->setCellValue('D'.$i, ($value['mobile_bind']));
				$objWorksheet->setCellValue('E'.$i, ($value['state']));
				$i++;
			}
		}
		//判断是否需要未考勤明细
		$expFlag = isset($paramObj['expdetail_notattendanced']) ? (int)$paramObj['expdetail_notattendanced'] : 0;
		if($expFlag===1)
		{
			$data = $this->getNoAtten($paramObj);
			$objPHPExcel->setActiveSheetIndex(2);
			$objWorksheet = $objPHPExcel->getActiveSheet();
			$i=3;
			foreach ($data['data'] as $key => $value) {
				$objWorksheet->setCellValue('A'.$i, $i-2);
				$objWorksheet->setCellValue('B'.$i, ($value['nick_name']));
				$objWorksheet->setCellValue('C'.$i, ($value['dept_name']));
				$objWorksheet->setCellValue('D'.$i, ($value['mobile_bind']));
				$objWorksheet->setCellValue('E'.$i, ('state'));
				$i++;
			}
		}
		//判断是否需要迟到考勤明细
		$expFlag = isset($paramObj['expdetail_delay']) ? (int)$paramObj['expdetail_delay'] : 0;
		if($expFlag===1)
		{
			$data = $this->getAllLate($paramObj);
			$objPHPExcel->setActiveSheetIndex(3);
			$objWorksheet = $objPHPExcel->getActiveSheet();
			$i=3;
			foreach ($data['data'] as $key => $value) {
				$objWorksheet->setCellValue('A'.$i, $i-2);
				$objWorksheet->setCellValue('B'.$i, ($value['nick_name']));
				$objWorksheet->setCellValue('C'.$i, ($value['dept_name']));
				$objWorksheet->setCellValue('D'.$i, ($value['mobile_bind']));
				$objWorksheet->setCellValue('E'.$i, ($value['state']));
				$objWorksheet->setCellValue('F'.$i, ($value['atten_date']));
				$i++;
			}
		}
		\PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5')->save($tmpFile); //根据需要也可以是 Excel2007
        //获取协议
        $http = $_SERVER['HTTPS']!='on' ? 'http://' : 'https://';
		return Utils::WrapResultOK($http.$hosturl.'/upload/attendance'.$now.'.xls');
	}

	public function getatten_setup($paramObj)
	{
		$sql = 'select * from ma_checkatten_setup where object_type=1 and setup_statu=1';
		$ds = $this->conn->GetData('t',$sql,array());
		return Utils::WrapResultOK($ds['t']['rows']);
	}

	public function updateAttenSetup($paramObj){

		$sql = 'update ma_checkatten_setup set
				work_time=?
				,late_time=?
				,offwork_time=?
			 where setup_id=?';

		$workTime = $paramObj['workTime'];
		$lateTime = $paramObj['lateTime'];
		$offworkTime = $paramObj['offworkTime'];
		$setupId = $paramObj['setupId'];
		$ds = $this->conn->ExecSQL($sql,array((string)$workTime,(string)$lateTime
			,(string)$offworkTime,(string)$setupId));
		return Utils::WrapResultOK('');
	}

	public function addAttenSetup($paramObj){
		$sql = 'inset into ma_checkatten_setup(work_time,late_time,offwork_time,eno)
				values(?,?,?,?)';

		$workTime = $paramObj['workTime'];
		$lateTime = $paramObj['lateTime'];
		$offworkTime = $paramObj['offworkTime'];
		$user = $paramObj["user"];
	    $eno = $user->eno;	
		$ds = $this->conn->ExecSQL($sql,array((string)$workTime,(string)$lateTime
			,(string)$offworkTime,(string)$eno));
		return Utils::WrapResultOK('');
	}

    //如果没有则获取针对所在部门的设置
    //如果没有则获取所在企业的全局设置
	public function getatten_dept_setup($paramObj)
	{
		$deptid = $paramObj['deptid'];
		$user = $paramObj['user'];
		$eno = $user->eno;
		$sql = 'select * from ma_checkatten_setup where object_id=? and eno=? and object_type=2 and setup_statu=1';
		$ds = $this->conn->GetData('t',$sql,array((string)$deptid,(string)$user->eno));
		if(count($ds['t']['rows'])==0)
		{
			return $this->getatten_setup($paramObj);
		}		
		return Utils::WrapResultOK($ds['t']['rows']);
	}	

    //获取针对指定人员的考勤设置
    //如果没有则获取针对所在部门的设置
    //如果没有则获取所在企业的全局设置
	public function getatten_staff_setup($paramObj)
	{
		$staff = $paramObj['staff'];
		$user = $paramObj['user'];
		$eno = $user->eno;
		$sql = 'select * from ma_checkatten_setup where object_id=? and eno=? and object_type=3 and setup_statu=1';
		$ds = $this->conn->GetData('t',$sql,array((string)$staff,(string)$user->eno));
		if(count($ds['t']['rows'])==0)
		{
			$staffMgr = new Staff($this->conn,$this->conn_im,$staff,$this->logger,$this->container);
			$data=$staffMgr->getInfo();
			$paramObj['deptid'] = $data['dept_id'];
			return $this->getatten_dept_setup($paramObj);
		}
		return Utils::WrapResultOK($ds['t']['rows']);
	}

	private function convertUTF8($str)
	{
	   if(empty($str)) return '';
	   return  iconv('gb2312', 'utf-8', $str);
	}

	private function getYearWeek($date)
	{
	    // 获取当前php版本  
	    $version = explode('.', PHP_VERSION);  
	    $phpVersion = floatval($version[0].".".$version[1]);  
	      
	    // php4.1以下版本  
	    if ($phpVersion < 4.1){  
	        return false;  
	    }  
	      
	    // php5.1及以上版本  
	    if ($phpVersion >= 5.1){  
	        return date('oW', strtotime($date));  
	    }  
	      
	    // php其它版本  
	    $dateInfo = getdate(strtotime($date));  
	    $week = date('W',strtotime($date));  
	    $year = $dateInfo['year'];  
	    if($week == 1 && $dateInfo['mon'] == 12){  
	        $year= $dateInfo['year']+1;  
	    }	  
	    return $year.'-'.$week;  
	}
	
	private function writelog($e)
	{
		if(!empty($this->logger))
		{
			$this->logger->err($e);
		}
	}
}
?>