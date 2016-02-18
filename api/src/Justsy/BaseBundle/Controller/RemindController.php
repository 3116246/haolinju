<?php
namespace Justsy\BaseBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;

class RemindController extends Controller
{
	//添加或编辑提醒信息
  public function editremindAction()
  {   
			$this->cur_user = $this->get('security.context')->getToken()->getUser();
      $request = $this->getRequest();      
      //获取的字段
      $keyid = $request ->get("remindid");
      $week = $request->get("week");
      //如果为按周提醒，年月日不需要值
      $year = null;
      $month = null;
      $day = null;
      if ($week == null || empty($week))
      {
      	$year =  $request ->get("year");
			  $month = $request->get("month");
			  $day = $request->get("day");
		  }
			$hour = $request->get('hour');
			$minute = $request ->get("minute");
			$content = $request->get("remind_content");
			$remind_type = $request->get("remind_type");
			$send_type = $request->get("send_type");
			$remind_staffid = $request->get("remind_staffid");
			$staff_type = $request->get("staff_type");
			$remind_category = $request->get("remind_category");
			$mobile = $request->get("remind_mobile");
      $result = $this->Modify($keyid,$year,$month,$day,$hour,$minute,$week,$content,$remind_type,$send_type,$remind_staffid,$staff_type,$remind_category,$mobile);
			$response=new Response(json_encode($result));
		  $response->headers->set('Content-Type', 'text/json');
	    return $response;
  }
  
  //添加或修改提醒信息函数
  public function Modify($keyid,$year,$month,$day,$hour,$minute,$week,$content,$remind_type,$send_type,$remind_staffid,$staff_type,$remind_category,$mobile)
  { 
  	$this->cur_user = $this->get('security.context')->getToken()->getUser();
  	$create_staff =	$this->cur_user->getUserName();
  	if ( $remind_staffid==null || empty($remind_staffid))
  	{
  		 $remind_staffid = $create_staff;
  		 $staff_type=1;
  	}
  	$da = $this->get('we_data_access');
  	//如果手机号不为空则修改用户手机号码
  	if ($mobile != null && !empty($mobile))
  	{
  		 $sql = "update we_staff set mobile=? where login_account=?";
		   $params = array((string)$mobile,(string)$create_staff);
		   $da->ExecSQL($sql,$params);
  	}
  	$edit = false; //新增或修改标志，如为false表示添加数据记录
  	if ($keyid==null || empty($eyid))
  	  $keyid = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_remind", "id");
    else
      $edit = true;
    //添加we_remind表
    $sqls = array();
    $parameters = array();
    $parameter = array();
    if (!$edit)
    {
      $sql = "insert into we_remind(id,`year`,`month`,`day`,`hour`,`minute`,week,remind_content,remind_type,send_type,create_staffid,create_date)value(?,?,?,?,?,?,?,?,?,?,?,now())";
      $parameter = array($keyid,$year,$month,$day,$hour,$minute,$week,$content,$remind_type,$send_type,$create_staff);
    }
    else
    {
    	$sql = "update we_remind set `year`=?,`month`=?,`day`=?,`hour`=?,`minute`=?,week=?,remind_content=?,remind_type=?,send_type=? where id=?";
    	$parameter = array($year,$month,$day,$hour,$minute,$week,$content,$remind_type,$send_type,$create_staff);
    }
    array_push($sqls,$sql);
    array_push($parameters,$parameter);
    //添加we_remind_details表
    $staff =  explode(",",$remind_staffid);
    $stafftype = explode(",",$staff_type);
    $detailsid = null;
    $date = $this->SetRemindDate($year,$month,$day,$hour,$minute,$week);
    for($i=0;$i< count($staff);$i++)
    {
    	if (!$edit) //添加
    	{
    		$detailsid = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_remind_details", "detailsid");
    		$sql = "insert into we_remind_details(detailsid,remindid,remindcontent,remind_date,state,remind_staffid,staff_type,remind_category)values(?,?,?,?,1,?,?,?)";
    		$parameter = array((string)$detailsid,(string)$keyid,(string)$content,(string)$date,(string)$staff[$i],(string)$stafftype[$i],(string)$remind_category);
    		array_push($sqls,$sql);
    		array_push($parameters,$parameter);
    	}
    	else //修改
    	{
    	}
    }
    $result = true;
    try
    {
      $da->ExecSQLs($sqls,$parameters);
    }
    catch (\Exception $e) 
    {
    	 $result = false;
    }
    return $result;
  }
  
  //根据条件动态生产提醒时间函数
  public function SetRemindDate($year,$month,$day,$hour,$minute,$week)
  {
	   $year   = $year == null ?"":$year;
	   $month  = $month == null ?"":$month;
	   $day    = $day == null ?"":$day;
	   $hour   = $hour == null ?"":$hour;
	   $minute = $minute == null ?"":$minute;
	   $week   = $week == null ?"":$week;
  	 $array = array();
  	 $curdate = date("Y-m-d H:i");
  	 $result = 0;
  	 //设置按日期提醒时间
  	 if (empty($week))
  	 {  	 	 
  	 	 //分钟
  	 	 if ($minute=="-1" || empty($minute))
  	 	   $minute="00";
  	 	 else if (strlen($minute)==2)
  	 	   $result = -1;
  	 	 else
  	 	 {
  	 	 	  $array = explode(",",$minute);
  	 	 	  $result = $this->getNextObj("minute",$array);
  	 	 	  if ($result == 0)
  	 	 	    $minute = $array[0];
  	 	 	  else
  	 	 	    $minute = $result;
  	 	 }
  	 	 //小时
  	 	 if ($hour == "-1" || empty($minute))
  	 	 {
  	 	 	 $hour = date("H");
  	 	 	 if ($result == 0 || ($result==-1 && (int)$minute<=date("i")))
  	 	 	   $hour += 1;
  	 	 	 $result = -2;
  	 	 }
  	 	 else if (strlen($hour)==2)
  	 	   $result = -1;
  	 	 else
  	 	 {
  	 	 	 $array = explode(",",$hour);
  	 	 	 $result =  $this->getNextObj("hour",$array);
  	 	 	 if($result == 0)
  	 	 	   $hour = $array[0];
  	 	 	 else
  	 	 	   $hour = $result;
  	 	 }
  	 	 //天
  	 	 if($day == "-1" || empty($day))
  	 	 {
  	 	 	 $day = date("d");
  	 	 	 if ($result==0 || ($result==-1 && (int)$hour < date("H")))
  	 	 	   $day += 1;
  	 	 	 $result = -2;
  	 	 }
  	 	 else if (strlen($day)==2)
  	 	   $result = -1;
  	 	 else
  	 	 {
  	 	 	  $array = explode(",",$day);
  	 	 	  $result = $this->getNextObj("day",$array);
  	 	 	  if ($result ==0 )
  	 	 	    $day = $array[0];
  	 	 	  else
  	 	 	    $day = $result;
  	 	 }
  	 	 //月
  	 	 if ($month=="-1" || empty($month))
  	 	 {
  	 	 	 $month = date("m");
  	 	 	 if ($result==0 || ( $result==-1 && (int)$day < date("d")))
  	 	 	   $month += 1;
  	 	 	 $result = -2;
  	 	 }
  	 	 else if (strlen($month)==2)
  	 	    $result = -1;
  	 	 else
  	 	 {
  	 	 	  $array = explode(",",$month);
  	 	 	  $result = $this->getNextObj("month",$array);
  	 	 	  if ($result==0)
  	 	 	    $month = $array[0];
  	 	 	  else
  	 	 	    $month = $result;
  	 	 }
  	 	 //年
  	 	 if ($year == "-1" || empty($year))
  	 	 {
  	 	 	  $year = date("Y");
  	 	 	if ($result == 0 || ($result == -1 && (int)$month < date("m")))
  	 	 	  $year += 1;
  	 	 }
  	 	 else if (strlen($year)==4)
  	 	   $result = -1;
  	 	 else
  	 	 {
  	 	 	  $array = explode(",",$year);
  	 	 	  $result = $this->getNextObj("year",$array);
  	 	 	  if ($result==0)
  	 	 	    $year = $array[0];
  	 	 	  else
  	 	 	    $year = $result;
  	 	 }
  	 	 $date = $year."-".$month."-".$day." ".$hour.":".$minute;
  	 }
  	 else
  	 {
  	 	 $min_hour = 0;
  	 	 $min_minute = 0;
  	 	 //分钟
  	 	 if ($minute=="-1" || empty($minute))
  	 	   $minute="00";
  	 	 else if (strlen($minute)==2)
  	 	   $result = -1;
  	 	 else
  	 	 {
  	 	 	  $array = explode(",",$minute);
  	 	 	  $min_minute = $array[0];
  	 	 	  $result = $this->getNextObj("minute",$array);
  	 	 	  if ($result == 0)
  	 	 	    $minute = $array[0];
  	 	 	  else
  	 	 	    $minute = $result;
  	 	 }
  	 	 //小时
  	 	 if ($hour == "-1" || empty($minute))
  	 	 {
  	 	 	 $hour = date("H");  	 	 	 
  	 	 	 if ($result == 0 || ($result==-1 && (int)$minute<=date("i")))
  	 	 	 {
  	 	 	   $hour += 1;
  	 	 	   $minute = $min;
  	 	 	 }
  	 	 }
  	 	 else if (strlen($hour)>2)
  	 	 {
  	 	 	 $array = explode(",",$hour);
  	 	 	 $min_hour = $array[0];
  	 	 	 $result =  $this->getNextObj("hour",$array);
  	 	 	 if($result == 0)
  	 	 	   $hour = $array[0];  	 	 	 
  	 	 	 else
  	 	 	   $hour = $result;
  	 	 }
  	 	 $weeks = explode(",",$week);
     	 $curweek = (int)date("w");
     	 $curweek= $curweek==0 ? 7:$curweek;
     	 $state = false;
     	 if ( date("Y-m-d")." ".$hour.":".$minute < date("Y-m-d H:i"))
     	   $state = true;
     	 
     	 $result = $this->getNextObj(($state ? $curweek + 1 :$curweek),$weeks);
     	 
     	 if ($result != $curweek) //不等于今天
     	 {
     	 	  if ($min_hour > 0)
     	 	    $hour = $min_hour;
     	 	  if ($min_minute>0)
     	 	    $minute = $min_minute;
     	 }
     	 $add_day = 0;
     	 if ($result == 0)
     	 	  $add_day = (7 - $curweek) + (int)$weeks[0]; 	 
     	 else
     	 	  $add_day = (int)$result - $curweek;
       $date = date("Y-m-d")." ".$hour.":".$minute;
     	 $para = "+".$add_day." day";
       $date = date("Y-m-d H:i",strtotime($para,strtotime($date)));
  	 }
  	 return $date;
  }
  
  //获得数组下一个对像
  private function getNextObj($type,$array)
  {
  	$number = 0;
  	$result = 0;
  	switch($type)
  	{
  		case "minute":
  		  $number=date("i")+1;
  		  break;
  		case "hour":
  		  $number=date("H")+1;
  		  break;
  		case "day":
  		  $number=date("d");
  		  break;
  	  case "month":
  	    $number=date("m");
  	    break;
  	  case "year":
  	    $number=date("Y");
  	  default:
  	    $number=(int)$type;
  	    break;
  	}
  	for($i=0;$i < count($array);$i++)
  	{
  		if ((int)$array[$i] >= $number)
  		{
  			 $result = (int)$array[$i];
  			 break;
  		}
  	}
  	return $result;
  }
  
  //更改是否提醒标志
  public function UpdateRemindState($detailsid,$state)
  {
  	 $da = $this->get('we_data_access');
  	 $result = true;
  	 $sql ="update we_remind_details set state=? where detailsid=?";
  	 $params = array((int)$state,(string)$detailsid);
  	 try
     {
        $da->ExecSQL($sql,$params);
     }
     catch (\Exception $e) 
     {
    	 $result = false;
     }
     return $result;
  }
  
  //系统提醒信息
  public function remindHintAction(Request $request)
  {
  	 $this->cur_user = $this->get('security.context')->getToken()->getUser();
     $user =	$this->cur_user->getUserName();
     $eno = $this->cur_user->eno;      
     $top = $request->get("top");
     if ($top > 0) //为0时表示默认的提醒
       $top = "  limit ".($top+1);
     else
       $top = "";
     $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');    
     $da = $this->get('we_data_access');
	   $sql = "select remindid,remindcontent,nick_name,case when photo_path is null or photo_path='' then null else concat('$FILE_WEBSERVER_URL',photo_path) end img,date_format(remind_date,'%Y-%m-%d %H:%i') remind_date,remind_category as category
             from we_remind_details a inner join we_staff b on a.remind_staffid=b.login_account where remind_staffid=? and remind_date between now() and date_add(curdate(),interval 7 day) and a.state=1
             union
             select remindid,remindcontent,addr_name,null img,date_format(remind_date,'%Y-%m-%d %H:%i') remind_date,remind_category as category
             from we_remind_details a inner join we_addrlist_addition b on b.id=a.remind_staffid where b.owner=? and remind_date between now() and date_add(curdate(),interval 7 day) and a.state=1 order by remind_date asc".$top;
	   $ds = $da->GetData("remind_hint",$sql,array((string)$user,(string)$user));
     $response = new Response(json_encode($ds["remind_hint"]["rows"]));
	   $response->headers->set('Content-Type', 'text/json');
     return $response;
  }
}