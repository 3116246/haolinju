<?php

require __DIR__.'/../vendor/swiftmailer/lib/swift_required.php';
require __DIR__.'/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;

$paras = parse_ini_file('../app/config/parameters.ini', true);
//$paras = parse_ini_file('F:\we\dev\app\config\parameters.ini', true);
$dbserver = $paras['parameters']['database_host'];
$dbname = $paras['parameters']['database_name'];
$dbuser = $paras['parameters']['database_user'];
$dbpassword = $paras['parameters']['database_password'];
$mailer_host = $paras['parameters']['mailer_host'];
$mailer_user = $paras['parameters']['mailer_user'];
$mailer_password = $paras['parameters']['mailer_password'];
$SMS_ACT = $paras['parameters']['SMS_ACT'];
$SMS_PWD = $paras['parameters']['SMS_PWD'];
$SMS_URL = $paras['parameters']['SMS_URL'];
$SERVER_URL = $paras['parameters']['FAFA_REG_JID_URL'];

$logger = new Logger('wefafa_remind_month');
$logger->pushHandler(new StreamHandler(__DIR__.'/wefafa_remind_month.log', Logger::DEBUG));
$logger->pushHandler(new FirePHPHandler());

while(true)
{
  $conn = mysql_connect($dbserver, $dbuser, $dbpassword);
  if (!$conn)
  {
    sleep(60);
    continue;
  }
  mysql_select_db($dbname, $conn);
  $sql = "SET NAMES 'utf8'";
  mysql_query($sql, $conn);
      
  $transport = \Swift_SmtpTransport::newInstance($mailer_host)
    ->setUsername($mailer_user)
    ->setPassword($mailer_password);
  $mailer = \Swift_Mailer::newInstance($transport);
 	//确定需要提醒的数据表
  $condition = " where (unix_timestamp(remind_date)-unix_timestamp(now()))/60 between 0 and 1 and remind_date between curdate() and date_add(curdate(),interval 1 day) and state=1 ";
  //具体月或包含月条件
  $condition .= " and year='-1' and position(case when month(curdate())<10 then concat('0',month(curdate())) else month(curdate()) end in month)>0";
  
  $sql = "select remindid,detailsid,nick_name from_name,(select fafa_jid from we_staff where we_staff.login_account=create_staffid) from_jid,".
        "  remind_staffid,year,month,day,hour,minute,week,remindcontent,remind_type,remind_date,case when find_in_set('0',send_type) then login_account else null end to_email,case when find_in_set('1',send_type) then mobile end to_mobile,case when find_in_set('2',send_type) then fafa_jid end to_jid,staff_type ".
        "from we_remind a inner join we_remind_details b on a.id=b.remindid inner join we_staff c on b.remind_staffid=c.login_account ".
        $condition." and staff_type=1 ".
        "union ".
        "select remindid,detailsid,(select nick_name from we_staff where we_staff.login_account=create_staffid) from_name,(select fafa_jid from we_staff where we_staff.login_account=create_staffid) from_jid,remind_staffid,year,month,day,hour,minute,week,remindcontent,remind_type,remind_date,case when find_in_set('0',send_type) then addr_mail end to_email,case when find_in_set('1',send_type) then addr_mobile end to_mobile,null,staff_type ".
        "from we_remind a inner join we_remind_details b on a.id=b.remindid inner join we_addrlist_addition c on b.remind_staffid=c.id ".
        $condition." and staff_type=0";
  $table = mysql_query($sql, $conn);
  if (mysql_num_rows($table) == 0) 
  {
  	 sleep(60);//暂停60秒后继续执行
     continue;  
  }
  while ($row = mysql_fetch_array($table)) 
  {
    $to_email  = $row["to_email"];
    $to_mobile = $row["to_mobile"];
    $to_jid    = $row["to_jid"];
    $content  = $row["remindcontent"];    
    //发送邮件
    if ( $to_email != null && !empty($to_email))
    {
    	$fromname =  $row["from_name"];
      try
      {
      	$title = mb_substr($content,0,20,'utf-8');
        $mailtext = \Swift_Message::newInstance()
          ->setSubject($title)
          ->setFrom(array($mailer_user => $fromname))
          ->setTo($to_email)
          ->setContentType('text/html')
          ->setBody($content."【Wefafa企业协作平台】");
        $mailer->send($mailtext);
      }
      catch(\Exception $e)
      {
        $logger->err($e);
      }
    }
    //发送手机短信
    if ($to_mobile !=null && !empty($to_mobile))
    {
    	if ($content != null || !empty($content))
    	  call_user_func("sendMobile",$SMS_ACT,$SMS_PWD,$SMS_URL,$to_mobile,$content."【发发时代】");
    }
    //发送wefafa消息
    if ($to_jid != null && !empty($to_jid))
    {
    	$from_jid = $row["from_jid"];
      if ($from_jid !=null || !empty($from_jid))
       call_user_func("sendMsg",$SERVER_URL,$from_jid,$to_jid,"remind_msg",$content,"","","");
    }
    //设置成已发送标志及发送日期(暂时注销)
    $sql = "update we_remind_details set state = 0,send_date = now() where detailsid='".$row["detailsid"]."'";
    mysql_query($sql, $conn);
    //如果为重复提醒则新加一条提醒数据记录
    if ((int)$row["remind_type"]==1)
    {
    	//获得下次提醒日期时间
      $date =	call_user_func("GetRemindDate",$row["remind_date"],$row["year"],$row["month"],$row["day"],$row["hour"],$row["minute"],$row["week"]);
	    //获得新的提醒详细id
  	  $sqlprocmember = "call p_seq_nextvalue('we_remind_details','detailsid', 1, @nextvalue)";
      mysql_query($sqlprocmember, $conn);
      $sqlprocmemberval = "select @nextvalue as nextvalue";
      $dsmemeberval = mysql_query($sqlprocmemberval, $conn);
      $detaislid = mysql_fetch_array($dsmemeberval);
      $detaislid = $detaislid["nextvalue"];
      //插入一条新的下次提醒信息
      $sql = "insert into we_remind_details(detailsid,remindid,remindcontent,remind_date,state,remind_staffid,staff_type)values('".$detaislid."','".$row["remindid"]."','".$content."','".$date."',1,'".$row["remind_staffid"]."','".$row["staff_type"]."')";
    	mysql_query($sql,$conn);
    }
  }
  mysql_close($conn);
  sleep(60);
}

//获得下次提醒时间
function GetRemindDate($reminddate,$year,$month,$day,$hour,$minute,$week)
{ 
  $date = "";
  $dateArray = date_parse($reminddate);
  if ( $week != null && !empty($week)) //按周提醒
  {
  	$cur = "";
    $continue = false;
    //计算分钟
    if ($minute=="-1" || empty($minute))
    {
      $minute="00";
      $continue = true;
    }
    else if (strlen($minute)==2)
      $continue = true;
    else if (strlen($minute)>2)
    {
     	 $cur =(string)($dateArray["minute"]<10?"0".$dateArray["minute"]:$dateArray["minute"]);
       $result = call_user_func("getNextString",$cur,explode(",",$minute));
       $continue = $result["continue"];
       $minute = $result["value"];
       if ($continue ==false)
         $date = $dateArray["year"]."-".$dateArray["month"]."-".$dateArray["day"]." ".$dateArray["hour"].":".$minute;
    }
    if($continue)
    {
     	 //计算小时
     	 if ($hour=="-1" || empty($hour))
     	 {
     	    $hour=$dateArray["hour"]+1;
     	    $date = $dateArray["year"]."-".$dateArray["month"]."-".$dateArray["day"]." ".$hour.":".$minute;
     	    $continue = false;
     	 }
     	 else if(strlen($hour)>2)
     	 {
     	 	 $cur =(string)($dateArray["hour"]<10?"0".$dateArray["hour"]:$dateArray["hour"]);
     	 	 $result = call_user_func("getNextString",$cur,explode(",",$hour));
     	 	 $continue = $result["continue"];
         $hour = $result["value"];
         if ($continue ==false)
           $date = $dateArray["year"]."-".$dateArray["month"]."-".$dateArray["day"]." ".$hour.":".$minute;
     	 }
     	 if($continue)
     	 {
     	 	  $weeks = explode(",",$week);
     	 	  $curweek = (int)date("w",strtotime($reminddate));
     	 	  $curweek= $curweek==0 ? 7:$curweek;
     	 	  $result = call_user_func("getNextString",$curweek,$weeks);
     	 	  $continue = $result["continue"];
          $endweek = $result["value"];
          $add_day = 0;
          if($continue)
          	$add_day = (7 - $curweek) + $endweek;
          else
            $add_day = $endweek - $curweek;
            
          $date = $dateArray["year"]."-".$dateArray["month"]."-".$dateArray["day"]." ".$hour.":".$minute;
          
          $para = "+".$add_day." day";
          $date = date("Y-m-d H:i",strtotime($para,strtotime($date)));
          
     	 }
    }       		      		
  }
	else  //按日期提醒
	{
     $cur = "";
     $continue = false;
     //取分钟
     if ($minute=="-1" || empty($minute))
     {
        $minute="00";
        $continue = true;
     }
     else if (strlen($minute)==2)
       $continue = true;
     else if (strlen($minute)>2)
     {
     	 $cur =(string)($dateArray["minute"]<10?"0".$dateArray["minute"]:$dateArray["minute"]);
       $result = call_user_func("getNextString",$cur,explode(",",$minute));
       $continue = $result["continue"];
       $minute = $result["value"];
       if ($continue ==false)
         $date = $dateArray["year"]."-".$dateArray["month"]."-".$dateArray["day"]." ".$dateArray["hour"].":".$minute;
     }
     if ($continue)
     {
     	 //计算小时
     	 if ($hour=="-1" || empty($hour))
     	 {
     	    $hour=$dateArray["hour"]+1;
     	    $date = $dateArray["year"]."-".$dateArray["month"]."-".$dateArray["day"]." ".$hour.":".$minute;
     	    $continue = false;
     	 }
     	 else if(strlen($hour)>2)
     	 {
     	 	 $cur =(string)($dateArray["hour"]<10?"0".$dateArray["hour"]:$dateArray["hour"]);
     	 	 $result = call_user_func("getNextString",$cur,explode(",",$hour));
     	 	 $continue = $result["continue"];
         $hour = $result["value"];
         if ($continue ==false)
           $date = $dateArray["year"]."-".$dateArray["month"]."-".$dateArray["day"]." ".$hour.":".$minute;
     	 }
     	 if ($continue)
     	 {
	     	 //计算天
	     	 if ($day=="-1" || empty($day))
	     	 {
	     	    $day=$dateArray["day"]+1;
	     	    $date = $dateArray["year"]."-".$dateArray["month"]."-".$day." ".$hour.":".$minute;
	     	    $continue = false;
	     	 }
	     	 else if(strlen($day)>2)
	     	 {
	     	 	 $cur =(string)($dateArray["day"]<10?"0".$dateArray["day"]:$dateArray["day"]);
	     	 	 $result = call_user_func("getNextString",$cur,explode(",",$day));
	     	 	 $continue = $result["continue"];
	         $day = $result["value"];
	         if ($continue ==false)
	           $date = $dateArray["year"]."-".$dateArray["month"]."-".$day." ".$hour.":".$minute;
	     	 }	     	 
	     	 if ($continue)
	     	 {
	     	 	  //计算月
	     	 	  if ($month == "-1" || empty($month))
	     	 	  {
	     	 	  	 $month = $dateArray["month"]+1;
	     	 	  	 $date  = $dateArray["year"]."-".$month."-".$day." ".$hour.":".$minute;
	     	 	  	 $continue = false;
	     	 	  }
	     	 	  else if (strlen($month)>2)
	     	 	  {
	     	 	  	 $cur =(string)($dateArray["month"]<10?"0".$dateArray["month"]:$dateArray["month"]);
	     	 	     $result = call_user_func("getNextString",$cur,explode(",",$month));
	     	 	     $continue = $result["continue"];
	             $month = $result["value"];
	             if ($continue ==false)
	               $date = $dateArray["year"]."-".$month."-".$day." ".$hour.":".$minute;
	     	 	  }
	     	 	  //计算年
	     	 	  if ( $continue)
	     	 	  {
	     	 	  	 if ( $year==-1 || empty($year))
	     	 	  	 {
	     	 	  	 	 $year = $dateArray["year"]+1;
	     	 	  	 	 $date  = $year."-".$month."-".$day." ".$hour.":".$minute;
	     	 	  	   $continue = false;
	     	 	  	 }
	     	 	  	 else if (strlen($year)>4)
	     	 	  	 {
	     	 	  	 	 $cur =(string)$dateArray["year"];
	     	 	       $result = call_user_func("getNextString",$cur,explode(",",$year));
	     	 	       $continue = $result["continue"];
	               $year = $result["value"];
	               if ($continue ==false)
	                 $date = $year."-".$month."-".$day." ".$hour.":".$minute;	     	 	  	 	 	     	 	  	 	 
	     	 	  	 }
	     	 	  }
	     	 }
     	 }     	 
     }
	}
	return $date;
}

//获得下一个字符
function getNextString($curstring,$arraylist)
{
	 $result = array();
	 $count = count($arraylist);
	 for($i=0;$i< $count;$i++)
	 {
	 	 if ($curstring==$arraylist[$i])
	 	 {
	 	 	 if($i==$count-1) //最后一个
	 	 	   $result = array("continue"=>true,"value"=>$arraylist[0]);
	 	 	 else
	 	 	   $result = array("continue"=>false,"value"=>$arraylist[$i+1]);
	 	 	 break;
	 	 }
	 }
	 return $result;
}

//发送手机信息函数
function sendMobile($SMS_ACT,$SMS_PWD,$SMS_URL,$mobiles,$content)
{
   $content = urlEncode(urlEncode(mb_convert_encoding($content, 'gb2312' ,'utf-8')));
   $pwd = md5($SMS_PWD);
   $apidata = "func=sendsms&username=$SMS_ACT&password=$pwd&mobiles=$mobiles&message=$content&smstype=0&timerflag=0&timervalue=&timertype=0&timerid=0";
   $obj = call_user_func("do_post_request",$SMS_URL,$apidata);
   $result = mb_convert_encoding($obj,'utf-8','gb2312');
   return $result;
}

//发送wefafa消息
function sendMsg($server_url,$from,$to,$title,$msg,$link,$linktext,$type="")
{
	//获取发送人和接收人jid或者openid
	$from = trim($from);
	$to = trim($to);
	$title = urlencode(trim($title));
	$msg = urlencode(trim($msg));
	$link = urlencode(trim($link));
	$linktext =urlencode(is_array($linktext)? json_encode($linktext) : trim($linktext));
	if($from==null || empty($from))
	{
     $from="admin@100082.fafacn.com"; 		
	}
	if(empty($msg))
	{
     return ("{\"returncode\":\"9999\",\"code\":\"err1001\",\"msg\":\"消息内容不能为空\"}");   		
	}
  $regUrlOrg = $server_url."/service.yaws";
  $data="sendMsg=1&from=$from&to=$to&msg=$msg&type=$type&title=$title&link=$link&linktext=$linktext";		  
  $obj = call_user_func("do_post_request",$regUrlOrg,$data);
}

function do_post_request($url, $data, $optional_headers = null)
{
	    $params = array('http' => array(
                'method' => 'POST',
                'content' => $data
              ));
    if ($optional_headers !== null) 
    {
      $params['http']['header'] = $optional_headers;
    }
    $ctx = stream_context_create($params);
    $fp = @fopen($url, 'r', false, $ctx);
    if (!$fp) 
    {
      throw new Exception("Problem with $url, $php_errormsg");
    }
    $response = @stream_get_contents($fp);
    if ($response === false) 
    {
      throw new Exception("Problem reading data from $url, $php_errormsg");
    }
    return $response;
}