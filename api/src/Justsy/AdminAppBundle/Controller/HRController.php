<?php

namespace Justsy\AdminAppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;

class HRController extends Controller
{
	
	//获得数据库连接及设置为utf-8格式
	private function getConnection()
	{
		$conn = null;
	 	try
		{
			
			$dbserver ="192.168.203.33";
			$dbuser ="root";
			$dbpassword="Password01!";
			$conn = mysql_pconnect($dbserver, $dbuser, $dbpassword);		
			if ( $conn==false) return null;
			mysql_select_db("mb_view", $conn);
			$utf8 = "SET NAMES 'utf8'";
			mysql_query($utf8, $conn);
			
			/*						
			$dbserver ="127.0.0.1";
			$dbuser ="root";
			$dbpassword="zhangbo";
			$conn = mysql_pconnect($dbserver, $dbuser, $dbpassword);		
			if ( $conn==false) return null;
			mysql_select_db("we_sns", $conn);
			$utf8 = "SET NAMES 'utf8'";
			mysql_query($utf8, $conn);
			*/
	  }
	  catch(\Exception $e){	  	
	  	$this->get("logger")->err($e->getMessage());
	  }	 
		return $conn;
	}	
	
	private function getHRDataAccess()
	{
		return new \Justsy\BaseBundle\DataAccess\DataAccess($this->container, null, 
			array(
				'driver' => "pdo_dblib",
				'host' => "192.168.204.52",
				'port' => 1433,
				'dbname' => "webone_mtsbw_kf",
				'user' => "sa",
				'password' => "mtsbw!@#098",
				'charset' => 'UTF8'   // 好像无效，mssql的字符集转不过来，估计pdo_dblib驱动有问题
				));
	}

	// sqlserver取出来的数据似乎一直是gbk，将其转成utf8
	private function gbk2utf8(&$ds)
	{
		foreach ($ds as &$table) {
			foreach ($table["rows"] as &$row) {
				foreach ($row as $key => $value) {
					if (is_string($value)) 
						$row[$key] = iconv('gbk', 'utf8', $value);
				}
			}
		}
		return $ds;
	}

	// 测试SQL Server用
	public function testsqlAction() 
	{
		$re = array("returncode" => ReturnCode::$SUCCESS);
		$request = $this->getRequest();
		$user = $this->get('security.context')->getToken()->getUser();
 
		try 
		{ 
			$daHR = $this->getHRDataAccess();
			$ds = $daHR->GetData("sss", "select  user_code, user_name, rq, sj, zt  from         view_user_kq_data where     (user_code = 'HQ01U8095') ");
			
			$re["sssss"] = $this->gbk2utf8($ds);//["sss"]["rows"];
			 
		} 
		catch (\Exception $e) 
		{
			$re["returncode"] = ReturnCode::$SYSERROR;
			$re["err"] = $e->getMessage();
			$this->get('logger')->err($e);
		}

		$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		$response->headers->set('Content-Type', 'text/json');
		return $response;
	}

	// 5.1出勤记录(新)
	public function cqjlAction()
	{		
		$re = array("returncode" => ReturnCode::$SUCCESS);
		$request = $this->getRequest();
		$user = $this->get('security.context')->getToken()->getUser();
		$da = $this->get('we_data_access');
		$datestart = $request->get("datestart");
		$dateend = $request->get("dateend");
		$user_code = explode('@', $user->getUserName());
		$user_code = strtoupper($user_code[0]);
		$conn = null;
		try
		{      
      $conn = $this->getConnection();
		  $query = "select user_code, user_name, date as rq, max(sbsj) sbsj, max(sbzt) sbzt, max(xbsj) xbsj, max(xbzt) xbzt, max(qj) qj, max(qjsc) qjsc, max(qjdw) qjdw
							from (
							    select t1.user_code, t1.user_name, t1.date, t1.sbsj, t2.zt sbzt, t1.xbsj, t3.zt xbzt, null qj, null qjsc, null qjdw
							    from (
							        select user_code, user_name,case when hour(timestamp(concat(date(rq), ' ', sj)))<5 then date_add(rq, interval -1 day) else rq end date,date_format(min(concat(date(rq), ' ', sj)), '%H:%i') sbsj,
							               case when count(sj) > 1 then date_format(max(concat(date(rq), ' ', sj)), '%H:%i') else null end xbsj
							        from mb_hr_kq_data a where a.user_code='".$user_code."'
							          and timestamp(concat(date(rq), ' ', sj))>=concat('".$datestart."', ' ', '05:00') 
							          and timestamp(concat(date(rq), ' ', sj))<=date_add(concat('".$dateend."', ' ', '05:00'), interval 1 day) 
							          and rq <= curdate()
							        group by user_code, user_name,date) as t1
							    left join mb_hr_kq_data t2 on t2.user_code=t1.user_code and t2.rq=t1.date and t2.sj=t1.sbsj
							    left join mb_hr_kq_data t3 on t3.user_code=t1.user_code and t3.rq=t1.date and t3.sj=t1.xbsj
							    union
							    select user_code, user_name, rq, null sbsj, null sbzt, null xbsj, null xbzt, leave_text qj, sc qjsc, unit qjdw
							    from mb_hr_leave where leave_name not in('qj18','qj19') and user_code='".$user_code."' and rq>='".$datestart."' and rq<='".$dateend."' and rq <= curdate()
							    union
							    select user_code, user_name, rq, null sbsj, null sbzt, null xbsj, null xbzt,'调休' qj, sum(sc) qjsc, unit qjdw
							    from mb_hr_leave 
							    where leave_name in('qj18','qj19') and user_code='".$user_code."' and rq>='".$datestart."' and rq<='".$dateend."' and rq <= curdate() group by rq 
							    ) as ttt
							group by user_code, user_name, rq
							order by rq desc ";							
		   $table = mysql_query($query);		 
		   $datasource = array();		   
		   if(mysql_num_rows($table)>0){
		   	 while ($row = mysql_fetch_array($table))
	       {
	       	 $record = array("user_code"=>$row["user_code"],"user_name"=>$row["user_name"],"rq"=>$row["rq"],
	       	                 "sbsj"=>$row["sbsj"],"sbzt"=>$row["sbzt"],"xbsj"=>$row["xbsj"],"xbzt"=>$row["xbzt"],"qj"=>$row["qj"],"qjsc"=>$row["qjsc"],"qjdw"=>$row["qjdw"]);
	    	    array_push($datasource,$record);
	       }
		   }
		   else{
		   	 $re["msg"]="没有查询到数据记录！";
		   }
		   $re["cqjl"] = $datasource;
		   //写入日志信息
       $syslog = new \Justsy\AdminAppBundle\Controller\SysLogController();
    	 $syslog->setContainer($this->container);
    	 $desc =  $user->nick_name."查看了【出勤记录】";
       $syslog->AddSysLog($desc,"出勤记录");
		}
		catch(\Exception $e){
		   $re["returncode"] = ReturnCode::$SYSERROR;
		   $re["msg"]=$e->getMessage();
		 	 $this->get("logger")->err($e->getMessage());
		}
		if (!empty($conn))
		  mysql_close($conn);
		$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		$response->headers->set('Content-Type', 'text/json');
		return $response;
	}
	
	// 5.2	假期库存(新)
	public function jqkcAction() 
	{
		$re = array("returncode" => ReturnCode::$SUCCESS);
		$request = $this->getRequest();
		$user = $this->get('security.context')->getToken()->getUser();
		$da = $this->get('we_data_access'); 
		$leave_id = $request->get("leave_id");
		$user_code = explode('@', $user->getUserName());
		$user_code = strtoupper($user_code[0]);
		if (empty($leave_id)) throw new \Exception("param is null");    
	  //返回数据
	  $sql1="";$sql2="";
	  //当前
		try {
			$conn = $this->getConnection();
	    if ($leave_id == "qj14") {
	    	//将qj18、qj19合并
			  $sql1    = "select user_code, user_name, leave_name, '调休' leave_text, sum(zl) zl, sum(xf_sl) xf_sl, sum(sy_sl) sy_sl
			              from mb_hr_kucun a where  a.user_code='".$user_code."' and leave_name in('qj19','qj14','qj18') group by user_code;";
        $sql2 = "select rq, 'qj14' leave_id, '调休' leave_text,sc qjsc, unit qjdw 
                 from mb_hr_leave a
                 where a.user_code='".$user_code."' and leave_name in('qj14','qj18','qj19') and rq>=concat(year(curdate()),'-01-01') group by rq having sum(sc)>0 order by rq desc";
      }
			else
			{
				 $sql1 = "select user_code,user_name,leave_name,leave_text,zl,xf_sl,sy_sl 
				         from mb_hr_kucun where user_code='".$user_code."' and sync_date>concat(year(curdate()),'-01-01') and leave_name='".$leave_id."'";
				         
				 $sql2 = "select rq, leave_name leave_id, leave_text, sc qjsc, unit qjdw from mb_hr_leave a 
				         where sc>0 and rq>=concat(year(curdate()),'-01-01') and a.user_code='".$user_code."' 
				         and leave_name='".$leave_id."' order by rq desc";
			}
			$table = mysql_query($sql1); 
		  if(mysql_num_rows($table)>0){
		   	 while ($row1 = mysql_fetch_array($table))
	       {
	    	    $re["kc_total"] = $row1["zl"];
				    $re["kc_use"] = $row1["xf_sl"];
				    $re["kc_unuse"] = $row1["sy_sl"];		        
	       }
		   }
		   else{
		   	 $re["kc_total"] = 0;
				 $re["kc_use"] = 0;
				 $re["kc_unuse"] = 0;		
		   }
		   $table2 = mysql_query($sql2);
		   $datasource = array();
       if(mysql_num_rows($table2)>0){
		   	 while ($row = mysql_fetch_array($table2))
	       {
	       	 $record = array("rq"=>$row["rq"],"leave_id"=>$row["leave_id"],"leave_text"=>$row["leave_text"],"qjsc"=>$row["qjsc"],"qjdw"=>$row["qjdw"]);
	    	   array_push($datasource,$record);
	       }
		   }
			$re["detail"] = $datasource;
		  //写入日志信息
      $syslog = new \Justsy\AdminAppBundle\Controller\SysLogController();
    	$syslog->setContainer($this->container);
    	$desc =  $user->nick_name."查看了【假期库存】";
      $syslog->AddSysLog($desc,"假期库存");
		}
		catch (\Exception $e) 
		{
			$re["returncode"] = ReturnCode::$SYSERROR;
			$this->get('logger')->err($e);
		}
		if (!empty($conn))
		  mysql_close($conn);
		$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		$response->headers->set('Content-Type', 'text/json');
		return $response;
	}
	
	// 5.3	月度考勤公示
	public function kqgsAction() 
	{
		$re = array("returncode" => ReturnCode::$SUCCESS);
		$request = $this->getRequest();
		$user = $this->get('security.context')->getToken()->getUser();
		$da = $this->get('we_data_access'); 
		$kqlb = $request->get("kqlb");
		$user_code = explode('@', $user->getUserName());
		$user_code = strtoupper($user_code[0]);
		try
		{
			if (empty($kqlb)) throw new \Exception("param is null");
			$conn = $this->getConnection();
			// 返回数据
			$sql = "select user_code, user_name, qishu, ldk_dp, ldk_zc, cd_1,cd_2, cd_3, cd_4, zt_1, zt_2, dp_cd_1, dp_cd_2, dp_cd_3, dp_cd_4, dp_zt_1, 
                     dp_zt_2, kongqin, kg, qj01, qj02, qj03, qj04, qj05, qj06, qj07, qj08, qj09,qj10, qj11, qj12, qj13, qj14, qj15, qj16, qj17, qj18, qj19, hx_qq_1, hx_qq_2,hx_qq_3, qq_gs
              from mb_hr_kqhz where user_code='".$user_code."' order by qishu desc limit 1";
			$table = mysql_query($sql);
 			$kqgs = array();
 			if (mysql_num_rows($table)>0)
 			{
 				 $r_kqhz = null;
 				 while ($rows = mysql_fetch_array($table))
	       {
	       	  $r_kqhz = $rows;
	       } 				 
	 			 if ($kqlb == "01")  //01－迟到早退未打卡
	 			 {
	 				 $lbmcs = array("ldk_dp" => "店铺未打卡（次）",
								 				  "ldk_zc" => "非店铺未打卡（次）",
								 				  "cd_1" => "非店铺迟到（0-30分钟）",
								 				  "cd_2" => "非店铺迟到（31-60分钟）",
								 					"cd_3" => "非店铺迟到（61-120分钟）",
								 					"cd_4" => "非店铺迟到（120分钟以上）",
								 				  "zt_1" => "非店铺早退(30分钟以内）",
								 				  "zt_2" => "非店铺早退（30分钟以上）",
								 				  "dp_cd_1" => "店铺迟到（0-10分钟）", 
								 				  "dp_cd_2" => "店铺迟到（11-30分钟）",
								 				  "dp_cd_3" => "店铺迟到（31-60分钟）",
								 				  "dp_cd_4" => "店铺迟到（61-120分钟）",
								 				  "dp_zt_1" => "店铺早退（1小时内）",
								 				  "dp_zt_2" => "店铺早退（1小时以上）");								 				  
	 				foreach ($lbmcs as $key => &$value) 
	 				{
	 					if ($r_kqhz[$key] <= 0) continue;
		 				$item = array();
		 				$item["lbmc"] = $value;
		 				$item["kqnum"] = $r_kqhz[$key];
						$item["kqdates"] = $this->getkqgsDetail($user_code, $r_kqhz["qishu"], $key);  //array();
		 				$kqgs[] = $item;	 					
	 				}
	 			 }
	 		   else if ($kqlb == "02")  //02－空勤&旷工
	 			 {
	 				  $lbmcs = array("kongqin" => "空勤","kg" => "旷工");
		 				foreach ($lbmcs as $key => &$value) 
		 				{
		 					if ($r_kqhz[$key] <= 0) continue;
			 				$item = array();
			 				$item["lbmc"] = $value;
			 				$item["kqnum"] = $r_kqhz[$key];
							$item["kqdates"] = $this->getkqgsDetail($user_code, $r_kqhz["qishu"], $key);  //array();
			 				$kqgs[] = $item;	 					
		 				}
	 			 }
	 			 else if ($kqlb == "03")  //03-请假&出差
	 			 {
	 				 $lbmcs = array(
								 					"qj01" => "年休假",
								 					"qj02" => "有薪事假",
								 					"qj03" => "无薪事假",
								 					"qj04" => "病假",
								 					"qj05" => "探亲假",
								 					"qj06" => "献血假",
								 					"qj07" => "长期服务奖假",
								 					"qj08" => "婚假",
								 					"qj09" => "产假",
								 					"qj10" => "丧假",
								 					"qj11" => "工伤假",
								 					"qj12" => "产检假",
								 					"qj13" => "陪产假",
								 					"qj14" => "调休",
								 					"qj15" => "哺乳假",
								 					"qj16" => "出差",
								 					"qj17" => "特殊假",
								 					"qj18" => "周末加班调休",
								 					"qj19" => "平时加班调休");
	 				foreach ($lbmcs as $key => &$value) 
	 				{
	 					if ($r_kqhz[$key] <= 0) continue;
	 					$item = array();
		 				$item["lbmc"] = $value;
		 				$item["kqnum"] = $r_kqhz[$key];
						$item["kqdates"] = $this->getkqgsDetail($user_code, $r_kqhz["qishu"], $key);  //array();
		 				$kqgs[] = $item;	 					
	 				}
	 			 }
	 			 else if ($kqlb == "04")  //04－核心&非核心缺勤
	 			 {
	 				  $lbmcs = array("hx_qq_1" => "核心缺勤（1小时以内）次数",
	 					               "hx_qq_2" => "核心缺勤（1到4小时）",
	 					               "hx_qq_3" => "核心缺勤（4小时以上）",
	 					               "qq_gs" => "非核心缺勤小时数");
		 				foreach ($lbmcs as $key => &$value) 
		 				{
		 					if ($r_kqhz[$key] <= 0) continue;	
			 				$item = array();
			 				$item["lbmc"] = $value;
			 				$item["kqnum"] = $r_kqhz[$key];
							$item["kqdates"] = $this->getkqgsDetail($user_code, $r_kqhz["qishu"], $key);
			 				$kqgs[] = $item;	 					
		 				}
	 			}
			}
			$re["kqgs"] = $kqgs;
			//写入日志信息
      $syslog = new \Justsy\AdminAppBundle\Controller\SysLogController();
    	$syslog->setContainer($this->container);
    	$desc =  $user->nick_name."查看了【月度考勤】";
      $syslog->AddSysLog($desc,"月度考勤");
		} 
		catch (\Exception $e) 
		{
			$re["returncode"] = ReturnCode::$SYSERROR;
			$this->get('logger')->err($e);
		}
		if (!empty($conn))
		  mysql_close($conn);
		$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		$response->headers->set('Content-Type', 'text/json');
		return $response;
	}

	private function getkqgsDetail($user_code, $ym, $lbmc)
	{
		$result = array();		
		$sql = "select distinct date_format(rq, '%Y-%m-%d') rq from mb_hr_kqmx  where user_code='".$user_code."' and $lbmc>0 order by rq";
	  $table = mysql_query($sql); 
		if(mysql_num_rows($table)>0){
		  while ($row = mysql_fetch_array($table))
      {
      	array_push($result,$row["rq"]);
      }
		}
		return $result;
	}

	// 5.4.1	查询
	public function selfinfo_qryAction() 
	{
		$re = array("returncode" => ReturnCode::$SUCCESS);
		$request = $this->getRequest();
		$user = $this->get('security.context')->getToken()->getUser();

		$da = $this->get('we_data_access'); 

		$user_code = explode('@', $user->getUserName());
		$user_code = strtoupper($user_code[0]);

		try 
		{
			// 返回数据
			$tablenames = array();
		    $sqls = array();
		    $all_params = array();
        
			$sql = "select distinct a.sapid_num, a.nachn, a.vorna, a.rufnm, a.gesch, a.gbdat, a.famst, a.famdt, a.anzkd, a.zhr_pa000204, a.zhr_hjd, a.zhr_hjyb, a.zhr_pa000205, a.zhr_pa000201, a.zhr_pa000202, a.zhr_pa000203, a.zhr_pa000209, a.zhr_pa000210, a.zhr_pa000211, a.zhr_pa000212, a.zhr_mz, a.zhr_xx, a.icnum1, a.icnum2, a.icnum3, b.orgeh, b.orgeh_d, b.stell, b.stell_d, b.zhr_pa903112
from mb_hr_6 a, mb_hr_7 b 
where a.sapid_num=b.sapid_num
  and b.zhr_pa903112=? ";
		    $params = array();
		    $params[] = (string)$user_code;

		    $tablenames[] = "baseinfo";
		    $sqls[] = $sql;
		    $all_params[] = $params;
        
			$sql = "select distinct a.zhr_pa902201, a.zhr_pa902202, a.zhr_pa902203, a.zhr_pa902204, a.zhr_pa902205
from mb_hr_6 a, mb_hr_7 b 
where a.sapid_num=b.sapid_num
  and b.zhr_pa903112=? ";
		    $params = array();
		    $params[] = (string)$user_code;

		    $tablenames[] = "txfs";
		    $sqls[] = $sql;
		    $all_params[] = $params;
        
			$sql = "select distinct a.id, a.zhr_pa902301, a.zhr_pa902302, a.zhr_pa902303, a.zhr_pa902304, a.zhr_pa902305, a.zhr_pa902306 
from mb_hr_8 a, mb_hr_7 b 
where a.sapid_num=b.sapid_num
  and b.zhr_pa903112=? 
order by a.id";
		    $params = array();
		    $params[] = (string)$user_code;

		    $tablenames[] = "jjlxr";
		    $sqls[] = $sql;
		    $all_params[] = $params;
        
			$sql = "select distinct a.id, a.begda, a.endda, a.zhr_xl, a.zhr_xx, a.zhr_zy 
from mb_hr_5 a, mb_hr_7 b 
where a.sapid_num=b.sapid_num
  and b.zhr_pa903112=? 
order by a.begda";
		    $params = array();
		    $params[] = (string)$user_code;

		    $tablenames[] = "jyjl";
		    $sqls[] = $sql;
		    $all_params[] = $params;
        
			$sql = "select distinct a.id, a.begda, a.endda, a.cttyp, a.cttyp_d, a.prbzt, a.prbeh, a.konsl, a.konsl_d 
from mb_hr_1 a, mb_hr_7 b 
where a.sapid_num=b.sapid_num
  and b.zhr_pa903112=? 
order by a.begda";
		    $params = array();
		    $params[] = (string)$user_code;

		    $tablenames[] = "htxx";
		    $sqls[] = $sql;
		    $all_params[] = $params;
        
			$sql = "select distinct a.id, a.begda, a.endda, a.arbgb, a.ort01, a.zhr_pa002302, a.zhr_zw, a.zhr_zmr, a.zhr_dh 
from mb_hr_2 a, mb_hr_7 b 
where a.sapid_num=b.sapid_num
  and b.zhr_pa903112=? 
order by a.begda";
		    $params = array();
		    $params[] = (string)$user_code;

		    $tablenames[] = "gzjl";
		    $sqls[] = $sql;
		    $all_params[] = $params;
    
			$ds = $da->GetDatas($tablenames, $sqls, $all_params);
			
			if (0 == count($ds["baseinfo"]["rows"])) $re["returncode"] = ReturnCode::$OTHERERROR;
			else
				$re["mb_hr_info"] = array(
					"baseinfo" => $ds["baseinfo"]["rows"][0],
					"txfs" => $ds["txfs"]["rows"][0],
					"jjlxr" => $ds["jjlxr"]["rows"],
					"jyjl" => $ds["jyjl"]["rows"],
					"htxx" => $ds["htxx"]["rows"],
					"gzjl" => $ds["gzjl"]["rows"]
					);
		}
		catch (\Exception $e) 
		{
			$re["returncode"] = ReturnCode::$SYSERROR;
			$this->get('logger')->err($e);
		}

		$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		$response->headers->set('Content-Type', 'text/json');
		return $response;
	}

	// 5.4.2	更新
	public function selfinfo_saveAction() 
	{
		$re = array("returncode" => ReturnCode::$SUCCESS);
		$request = $this->getRequest();
		$user = $this->get('security.context')->getToken()->getUser();

		$da = $this->get('we_data_access'); 

		$data = $request->get("data");
		$user_code = explode('@', $user->getUserName());
		$user_code = strtoupper($user_code[0]);

		try 
		{
			$objdata = json_decode($data, true); 
			
			if (empty($objdata)) throw new \Exception("param is null");

			// 更新
		    $sqls = array();
		    $all_params = array();

		    $sql = "update mb_hr_6 set vorna=?, famst=?, famdt=?, anzkd=?, zhr_pa000204=?, zhr_hjd=?, zhr_hjyb=?, zhr_pa000205=?, zhr_pa000209=?, zhr_pa000210=?, zhr_pa000211=?, zhr_pa000212=?, zhr_mz=?, zhr_xx=?, icnum1=?, icnum2=?, icnum3=?, 
zhr_pa902201=?, zhr_pa902202=?, zhr_pa902203=?, zhr_pa902204=?, zhr_pa902205=? 
where sapid_num=?";
		    $params = array();
		    $params[] = (string)$objdata["baseinfo"]["vorna"];
		    $params[] = (string)$objdata["baseinfo"]["famst"];
		    $params[] = (string)$objdata["baseinfo"]["famdt"];
		    $params[] = (string)$objdata["baseinfo"]["anzkd"];
		    $params[] = (string)$objdata["baseinfo"]["zhr_pa000204"];
		    $params[] = (string)$objdata["baseinfo"]["zhr_hjd"];
		    $params[] = (string)$objdata["baseinfo"]["zhr_hjyb"];
		    $params[] = (string)$objdata["baseinfo"]["zhr_pa000205"];
		    $params[] = (string)$objdata["baseinfo"]["zhr_pa000209"];
		    $params[] = (string)$objdata["baseinfo"]["zhr_pa000210"];
		    $params[] = (string)$objdata["baseinfo"]["zhr_pa000211"];
		    $params[] = (string)$objdata["baseinfo"]["zhr_pa000212"];
		    $params[] = (string)$objdata["baseinfo"]["zhr_mz"];
		    $params[] = (string)$objdata["baseinfo"]["zhr_xx"];
		    $params[] = (string)$objdata["baseinfo"]["icnum1"];
		    $params[] = (string)$objdata["baseinfo"]["icnum2"];
		    $params[] = (string)$objdata["baseinfo"]["icnum3"];
		    $params[] = (string)$objdata["txfs"]["zhr_pa902201"];
		    $params[] = (string)$objdata["txfs"]["zhr_pa902202"];
		    $params[] = (string)$objdata["txfs"]["zhr_pa902203"];
		    $params[] = (string)$objdata["txfs"]["zhr_pa902204"];
		    $params[] = (string)$objdata["txfs"]["zhr_pa902205"];
		    $params[] = (string)$objdata["baseinfo"]["sapid_num"];

		    $sqls[] = $sql;
		    $all_params[] = $params;

		    $sql = "update mb_hr_8 set zhr_pa902301=?, zhr_pa902302=?, zhr_pa902303=?, zhr_pa902304=?, zhr_pa902305=?, zhr_pa902306=? 
where id=?";
			foreach ($objdata["jjlxr"] as &$row) {
			    $params = array();
			    $params[] = (string)$row["zhr_pa902301"];
			    $params[] = (string)$row["zhr_pa902302"];
			    $params[] = (string)$row["zhr_pa902303"];
			    $params[] = (string)$row["zhr_pa902304"];
			    $params[] = (string)$row["zhr_pa902305"];
			    $params[] = (string)$row["zhr_pa902306"];
			    $params[] = (string)$row["id"];

			    $sqls[] = $sql;
			    $all_params[] = $params;				
			}

		    $sql = "update mb_hr_5 set begda=?, endda=?, zhr_xl=?, zhr_xx=?, zhr_zy=?
where id=?";
			foreach ($objdata["jyjl"] as &$row) {
			    $params = array();
			    $params[] = (string)$row["begda"];
			    $params[] = (string)$row["endda"];
			    $params[] = (string)$row["zhr_xl"];
			    $params[] = (string)$row["zhr_xx"];
			    $params[] = (string)$row["zhr_zy"];
			    $params[] = (string)$row["id"];

			    $sqls[] = $sql;
			    $all_params[] = $params;				
			}

		    $sql = "update mb_hr_2 set begda=?, endda=?, arbgb=?, ort01=?, zhr_pa002302=?, zhr_zw=?, zhr_zmr=?, zhr_dh=?
where id=?";
			foreach ($objdata["gzjl"] as &$row) {
			    $params = array();
			    $params[] = (string)$row["begda"];
			    $params[] = (string)$row["endda"];
			    $params[] = (string)$row["arbgb"];
			    $params[] = (string)$row["ort01"];
			    $params[] = (string)$row["zhr_pa002302"];
			    $params[] = (string)$row["zhr_zmr"];
			    $params[] = (string)$row["zhr_zmr"];
			    $params[] = (string)$row["zhr_dh"];
			    $params[] = (string)$row["id"];

			    $sqls[] = $sql;
			    $all_params[] = $params;				
			}
    
    		$da->ExecSQLs($sqls, $all_params);
		} 
		catch (\Exception $e) 
		{
			$re["returncode"] = ReturnCode::$SYSERROR;
			$this->get('logger')->err($e);
		}

		$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		$response->headers->set('Content-Type', 'text/json');
		return $response;
	}
}
