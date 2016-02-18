<?php
$paras = parse_ini_file("../app/config/parameters.ini", true);

$opts = getopt("s:");   // s 数据源名称 we/im
$dbsource = $opts["s"];
$logname = $dbsource;
if (empty($dbsource) || $dbsource == "we") { $dbsource = ""; $logname = "we";}
else $dbsource = "_$dbsource"; 

$dbserver = $paras['parameters']["database_host$dbsource"];
$dbname = $paras['parameters']["database_name$dbsource"];
$dbuser = "we_rep";
$dbpassword = "we_rep";

while(true)
{
  $conn = mysql_connect($dbserver, $dbuser, $dbpassword, true);
  if (!$conn)
  {
    sleep(5); 
    continue;   
  }
    
  mysql_select_db($dbname, $conn);
    
  $sql = "SET NAMES 'utf8'";
  mysql_query($sql, $conn);
  
  $dbserver_dest = "";
  $dbname_dest = "";
  $dbuser_dest = "we_rep";
  $dbpassword_dest = "we_rep";
  $old_target_service = "-1";
  $conn_dest = null;
  while(true)
  {
    //读取1条信息
    $sql = "select id, sql_text, target_service from log_rep_disp where state_id<>'1'";
    $result = mysql_query($sql, $conn);
    
    if (mysql_num_rows($result) == 0) break;

    while ($row = mysql_fetch_array($result)) 
    {  
      //查找目标数据源
      if ($old_target_service != $row["target_service"])
      {
        $dbdest = $row["target_service"];
        if (empty($dbdest) || $dbdest == "we") $dbdest = ""; 
        else $dbdest = "_$dbdest";
        
        $dbserver_dest = $paras['parameters']["database_host$dbdest"];
        $dbname_dest = $paras['parameters']["database_name$dbdest"];
  
        if ($conn_dest) mysql_close($conn_dest);
        $conn_dest = mysql_connect($dbserver_dest, $dbuser_dest, $dbpassword_dest, true);
        if (!$conn_dest)
        {
          sleep(5);
          continue;
        }
          
        mysql_select_db($dbname_dest, $conn_dest);
          
        $sql = "SET NAMES 'utf8'";
        mysql_query($sql, $conn_dest);
  
        $old_target_service = $row["target_service"];
      }
      
      //执行SQL
      $sql = $row["sql_text"];
      if(strpos($sql, "insert into we_group_staff")!==false)
      {
          $m =array();
          preg_match ("/fafa_groupid='\d{1,}'/", $sql, $m);
          if(count($m)==0)
          {
            $sql = "delete from log_rep_disp where id=".$row["id"];
            mysql_query($sql, $conn);
            continue;
          }
          $fafa_groupid = str_replace("fafa_groupid=","",$m[0]);
          $fafa_groupid = str_replace("'","",$fafa_groupid);
          $doit = mysql_query("select count(1) cnt from we_groups where fafa_groupid='".$fafa_groupid."'", $conn_dest);
          $rowdoit = mysql_fetch_array($doit);
          $ishave = $rowdoit["cnt"];
          if($ishave==0)
          {
            //group is not created,wait for it create...
            continue;
          }
      }
      else if(strpos($sql, "insert into we_groups")!==false)
      {
      	  $m =array();
      	  preg_match ("/\[.*?@fafacn.com\]/", $sql, $m);
      	  $jid =str_replace("]","", str_replace("[","",$m[0]));
      	  //获取fafa_groupid
      	  preg_match ("/fafa_groupid=\d{1,}/", $sql, $m);
      	  if(count($m)==0)
      	  {
			      $sql = "delete from log_rep_disp where id=".$row["id"];
        	  mysql_query($sql, $conn);
      	  	continue;
      	  }
      	  $fafa_groupid = str_replace("fafa_groupid=","",$m[0]);
      	  $sql = str_replace("fafa_groupid=", "",$sql); 
      	  $doit = mysql_query("select count(1) cnt from we_groups where fafa_groupid='".$fafa_groupid."'", $conn_dest);
      	  $rowdoit = mysql_fetch_array($doit);
          $ishave = $rowdoit["cnt"];
          if($ishave==1)
          {
          	$sql = "delete from log_rep_disp where id=".$row["id"];
        	  mysql_query($sql, $conn);
          	continue;
          }
      	  $dsplan_data= mysql_query("select a.circle_id,c.login_account  from  we_circle a,we_enterprise b,we_staff c  where a.network_domain=b.edomain and b.eno=c.eno and c.fafa_jid='".$jid."'", $conn_dest);
		      $account="";
		      $circle_id = "";
		      while ($rowplan_data = mysql_fetch_array($dsplan_data)) {
            $account = $rowplan_data["login_account"];
            $circle_id = $rowplan_data["circle_id"];
          }
          $sql = str_replace("@eno",$circle_id, str_replace("[".$jid."]",$account,$sql));
      	  //新增IM群组
      	  $sqlprocmember = "call p_seq_nextvalue('we_groups','group_id', 1, @nextvalue)";
          mysql_query($sqlprocmember, $conn_dest);
          $sqlprocmemberval = "select @nextvalue as nextvalue";
          $dsmemeberval = mysql_query($sqlprocmemberval, $conn_dest);
          $rowmemeberid = mysql_fetch_array($dsmemeberval);
          $memberid = $rowmemeberid["nextvalue"];
      	  $sql = str_replace("@groupid", $memberid,$sql);      	  
      }
      $re = mysql_query($sql, $conn_dest);  
      if ($re)
      {
        $sql = "delete from log_rep_disp where id=".$row["id"];
        mysql_query($sql, $conn);
      }
      else
      {        
        $flog = fopen("./$logname".date("Ymd").".log", "a");
        fwrite($flog, date("Y-m-d H:i:s")." error:".mysql_error()); 
        fwrite($flog, "\n");
        fwrite($flog, $sql);
        fwrite($flog, "\n");
        fclose($flog);
        
        $sql = "update log_rep_disp set state_id='1' where id=".$row["id"];
        mysql_query($sql, $conn);
      }      
    }
  }
  
  if ($conn_dest) mysql_close($conn_dest);
  mysql_close($conn);
  sleep(5);
}