<?php

require __DIR__ . '/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;

$paras = parse_ini_file('../app/config/parameters.ini', true);
$dbserver = $paras['parameters']['database_host'];
$dbname = $paras['parameters']['database_name'];
$dbuser = $paras['parameters']['database_user'];
$dbpassword = $paras['parameters']['database_password'];

$dbserver_im = $paras['parameters']['database_host_im'];
$dbname_im = $paras['parameters']['database_name_im'];
$dbuser_im = $paras['parameters']['database_user_im'];
$dbpassword_im = $paras['parameters']['database_password_im'];

$logger = new Logger('wefafa_meeting');
$logger->pushHandler(new StreamHandler(__DIR__ . '/wefafa_meeting.log', Logger::DEBUG));
$logger->pushHandler(new FirePHPHandler());

$recordtime = "";
$logger->info("===============start_meeting===============");
while (true) {
    $datetime = date("y-m-d", time());
    //时间不为空  并且时间为今天时,不处理任何数据
    //每分钟检查一次，当前日期是否还是上次执行任务的日期，是则不执行任务
    if (!empty($recordtime) && $recordtime == $datetime) {
        sleep(60);
        continue;
    }

    $logger->info("------>run_meeting_date:" . $datetime);

    $conn = mysql_connect($dbserver, $dbuser, $dbpassword, true);
    if (empty($conn)) {
        sleep(5);
        continue;
    }
    mysql_select_db($dbname, $conn);

    $sqlcode = "SET NAMES 'utf8'";
    //设置编码为UTF8
    mysql_query($sqlcode, $conn);

    while (true) {
        //连接IM库
        $conn_im = mysql_connect($dbserver_im, $dbuser_im, $dbpassword_im, true);
        if (empty($conn_im)) {
            sleep(5);
            continue;
        }
        mysql_select_db($dbname_im, $conn_im);

        //设置编码为UTF8
        mysql_query($sqlcode, $conn_im);

        $logger->info("数据库连接成功!");

        //获取预定结束时间已经结束,真实结束之间未指定,真实操作时间未指定

        $sqlupdLine = "update we_meeting_plan set realmeetingstartdate=meetingstartdate,realmeetingenddate=meetingenddate,operationdate=now() ";
        $sqlupdLine.="where meetingenddate>= date(DATE_ADD(now(),INTERVAL -1 DAY)) and meetingenddate<=date(now()) ";
        $sqlupdLine.="and (realmeetingstartdate is null or realmeetingstartdate=''  or realmeetingenddate is null or realmeetingenddate='') ";
        $sqlupdLine.="and (operationdate is null or operationdate='')";

        mysql_query($sqlupdLine, $conn);

        //只处理前几天的会议数据 SNS库
        $sqlsel = "select * from we_meeting_plan where operationdate >= date(DATE_ADD(now(),INTERVAL -1 DAY)) ";
        $sqlsel.="and operationdate is not null  and realmeetingenddate<date(now())";

        $resultplan = mysql_query($sqlsel, $conn);

        $plancount = mysql_num_rows($resultplan);

        $logger->info("可以处理的记录数:" . $plancount . ".");

        if ($plancount == 0)
            break;
        try {
            //有需要处理的数据
            while ($row = mysql_fetch_array($resultplan)) {
                //获取群主键
                $groupid = $row["groupid"];

                $logger->info("会议名称:" . $row["name"] . ".群主键:" . $groupid);

                //获取会议计划主键
                $planid = $row["id"];

                //获取会议周期 不为空为例会  为空是一般会议
                $cycle = $row["cycle"];

                $logger->info("会议周期:" . $cycle . ".");

                //唯一标识符
                $identifiers = $row["identifiers"];

                if (!empty($groupid)) { //线上会议
                    if (empty($cycle)) { //非例会 删除IM库所有成员和群组
                        //删除IM库群成员
                        $sqlimstaff = "delete from im_groupemployee where groupid='" . $groupid . "'";
                        mysql_query($sqlimstaff, $conn_im);
                        //删除IM库群组
                        $sqlim = "delete from im_group where groupid='" . $groupid . "'";
                        mysql_query($sqlim, $conn_im);

                        $logger->info("线上会议(一般会议)-SNS库执行SQL语句:" . $sqlimstaff);
                        $logger->info("线上会议(一般会议)-IM库执行SQL语句:" . $sqlim);
                    } else { //例会
                        //判断是否已经生成下一次例会
                        $sqlselplan = "select count(1) as count from we_meeting_plan where identifiers='" . $identifiers . "' and meetingenddate>now()  and realmeetingenddate is null";
                        $dsselplan = mysql_query($sqlselplan, $conn);
                        $rowselplan = mysql_fetch_array($dsselplan);
                        $countselplan = $rowselplan["count"];
                        mysql_free_result($dsselplan);

                        $logger->info("线上会议(例会)-是否已经生成下次例会:" . $countselplan);

                        //如果存在最新例会不添加 否则添加新例会
                        if ($countselplan == 0) {
                            //获取指定表最大主键值  SNS库
                            $sqlproc = "call p_seq_nextvalue('we_meeting_plan','id', 1, @nextvalue)";
                            mysql_query($sqlproc, $conn);
                            $sqlval = "select @nextvalue as nextvalue";
                            $dsval = mysql_query($sqlval, $conn);
                            $rowval = mysql_fetch_array($dsval);
                            $newplanid = "we" . $rowval["nextvalue"];
                            mysql_free_result($dsval);

                            //取当前群组例会的会议计划
                            $sqlplan_data = "select * from (select meetingstartdate,meetingenddate from we_meeting_plan where identifiers='" . $identifiers . "' order by meetingstartdate limit 0,1) as t1";
                            $sqlplan_data.=" union ";
                            $sqlplan_data.="select * from (select meetingstartdate,meetingenddate from we_meeting_plan where identifiers='" . $identifiers . "' order by meetingstartdate desc  limit 0,1) as t2 ";

                            $dsplan_data = mysql_query($sqlplan_data, $conn);

                            //没有要处理的数据
                            if (mysql_num_rows($dsplan_data) == 0)
                                break;
                            $meetingstartdate = "";
                            $meetingenddate = "";
                            $i = 0;
                            $starttime = "";
                            $endtime = "";
                            while ($rowplan_data = mysql_fetch_array($dsplan_data)) {
                                if ($i == 0) {
                                    $meetingstartdate = $rowplan_data["meetingstartdate"];
                                    $meetingenddate = $rowplan_data["meetingenddate"];

                                    $starttime = date("H:i:s", strtotime($meetingstartdate));
                                    $endtime = date("H:i:s", strtotime($meetingenddate));
                                } else {
                                    $startdate = date("Y-m-d", strtotime($rowplan_data["meetingstartdate"]));
                                    $enddate = date("Y-m-d", strtotime($rowplan_data["meetingenddate"]));

                                    $meetingstartdate = $startdate . " " . $starttime;
                                    $meetingenddate = $enddate . " " . $endtime;
                                    break;
                                }
                                $i++;
                            }
                            mysql_free_result($dsplan_data);
                            switch ($cycle) {
                                case "1":
                                    $meetingstartdate = date("Y-m-d H:i:s", strtotime("+1 months", strtotime($meetingstartdate)));
                                    $meetingenddate = date("Y-m-d H:i:s", strtotime("+1 months", strtotime($meetingenddate)));
                                    break;
                                case "3":
                                    $meetingstartdate = date("Y-m-d H:i:s", strtotime("+3 months", strtotime($meetingstartdate)));
                                    $meetingenddate = date("Y-m-d H:i:s", strtotime("+3 months", strtotime($meetingenddate)));
                                    break;
                                case "7":
                                    $meetingstartdate = date("Y-m-d H:i:s", strtotime("+1 week", strtotime($meetingstartdate)));
                                    $meetingenddate = date("Y-m-d H:i:s", strtotime("+1 week", strtotime($meetingenddate)));
                                    break;
                                case "12":
                                    $meetingstartdate = date("Y-m-d H:i:s", strtotime("+1 year", strtotime($meetingstartdate)));
                                    $meetingenddate = date("Y-m-d H:i:s", strtotime("+1 year", strtotime($meetingenddate)));
                                    break;
                            }
                            //以指定数据为单位创建新例会 SNS库
                            $sqladdplan = "insert into we_meeting_plan(id,groupid,name,subject,item,meetingstartdate,meetingenddate,meetingtype,identifiers,addrType";
                            $sqladdplan.=" ,defaultAddr,cycle,master,remind,create_staff,create_date) values(";
                            $sqladdplan.="'" . $newplanid . "','" . $row["groupid"] . "','" . $row["name"] . "','" . $row["subject"] . "','" . $row["item"] . "','" . $meetingstartdate;
                            $sqladdplan.= "','" . $meetingenddate . "','" . $row["meetingtype"] . "','" . $row["identifiers"] . "','" . $row["addrType"] . "','" . $row["defaultAddr"] . "','" . $row["cycle"] . "','" . $row["master"];
                            $sqladdplan.= "','" . $row["remind"] . "','" . $row["create_staff"] . "',now())";
                            mysql_query($sqladdplan, $conn);

                            $logger->info("线上会议(例会)-SNS库执行SQL语句:" . $sqladdplan);

                            //添加SNS库we_meeting_member表的正式成员
                            $sqlofficial = "select staffid from we_meeting_member where planid='" . $planid . "' and stafftype=1";
                            $dsofficial = mysql_query($sqlofficial, $conn);
                            while ($officialrow = mysql_fetch_array($dsofficial)) {
                                //获取主键
                                $sqlprocmember = "call p_seq_nextvalue('we_meeting_member','id', 1, @nextvalue)";
                                mysql_query($sqlprocmember, $conn);
                                $sqlprocmemberval = "select @nextvalue as nextvalue";
                                $dsmemeberval = mysql_query($sqlprocmemberval, $conn);
                                $rowmemeberid = mysql_fetch_array($dsmemeberval);
                                $memberid = "we" . $rowmemeberid["nextvalue"];
                                mysql_free_result($dsmemeberval);
                                if (!empty($memberid)) {
                                    //添加正式成员 SNS库
                                    $sqladd = "insert into we_meeting_member(id,planid,staffid,stafftype) values('" . $memberid . "','" . $newplanid . "','" . $officialrow["staffid"] . "',1)";
                                    mysql_query($sqladd, $conn);

                                    $logger->info("线上会议(例会)-SNS库执行SQL语句:" . $sqladd);
                                }
                            }
                            mysql_free_result($dsofficial);
                        }
                        //查询临时成员数据  SNS库
                        $sql = "select staffid from we_meeting_member where planid='" . $planid . "' and stafftype!=1";
                        $dsmemeber = mysql_query($sql, $conn);
                        while ($staffrow = mysql_fetch_array($dsmemeber)) {
                            if (!empty($groupid) && !empty($staffrow["staffid"])) {
                                //循环删除IM库中对应的临时成员 IM库
                                $sqlimstaff = "delete from im_groupemployee where groupid='" . $groupid . "' and employeeid='" . $staffrow["staffid"] . "'";
                                mysql_query($sqlimstaff, $conn_im);

                                $logger->info("线上会议(例会)-IM库执行SQL语句:" . $sqlimstaff);
                            }
                        }
                        mysql_free_result($dsmemeber);
                    }
                } else { //线下会议
                    if (!empty($cycle)) { //例会 
                        //$defaultAddr = $row["defaultAddr"];
                        //and cycle='" . $cycle . "' 
                        $sqlselplan = "select count(1) as count from we_meeting_plan where identifiers='" . $identifiers . "'  and meetingenddate>now()  and realmeetingenddate is null";
                        $dsselplan = mysql_query($sqlselplan, $conn);
                        $rowselplan = mysql_fetch_array($dsselplan);
                        $countselplan = $rowselplan["count"];
                        mysql_free_result($dsselplan);

                        $logger->info("线下会议(例会)-是否已经生成下次例会:" . $countselplan);

                        //如果存在最新例会不添加 否则添加新例会
                        if ($countselplan == 0) {
                            //获取指定表最大主键值  SNS库
                            $sqlproc = "call p_seq_nextvalue('we_meeting_plan','id', 1, @nextvalue)";
                            mysql_query($sqlproc, $conn);
                            $sqlval = "select @nextvalue as nextvalue";
                            $dsval = mysql_query($sqlval, $conn);
                            $rowval = mysql_fetch_array($dsval);
                            $newplanid = "we" . $rowval["nextvalue"];
                            mysql_free_result($dsval);

                            //取当前标识符例会的会议计划
                            $sqlplan_data = "select * from (select meetingstartdate,meetingenddate from we_meeting_plan where identifiers='" . $identifiers . "'  order by meetingstartdate limit 0,1) as t1";
                            $sqlplan_data.=" union ";
                            $sqlplan_data.="select * from (select meetingstartdate,meetingenddate from we_meeting_plan where identifiers='" . $identifiers . "' order by meetingstartdate desc  limit 0,1) as t2 ";

                            $dsplan_data = mysql_query($sqlplan_data, $conn);

                            //没有要处理的数据
                            if (mysql_num_rows($dsplan_data) == 0)
                                break;
                            $meetingstartdate = "";
                            $meetingenddate = "";
                            $i = 0;
                            $starttime = "";
                            $endtime = "";
                            while ($rowplan_data = mysql_fetch_array($dsplan_data)) {
                                if ($i == 0) {
                                    $meetingstartdate = $rowplan_data["meetingstartdate"];
                                    $meetingenddate = $rowplan_data["meetingenddate"];

                                    $starttime = date("H:i:s", strtotime($meetingstartdate));
                                    $endtime = date("H:i:s", strtotime($meetingenddate));
                                } else {
                                    $startdate = date("Y-m-d", strtotime($rowplan_data["meetingstartdate"]));
                                    $enddate = date("Y-m-d", strtotime($rowplan_data["meetingenddate"]));

                                    $meetingstartdate = $startdate . " " . $starttime;
                                    $meetingenddate = $enddate . " " . $endtime;
                                    break;
                                }
                                $i++;
                            }
                            mysql_free_result($dsplan_data);
                            switch ($cycle) {
                                case "1":
                                    $meetingstartdate = date("Y-m-d H:i:s", strtotime("+1 months", strtotime($meetingstartdate)));
                                    $meetingenddate = date("Y-m-d H:i:s", strtotime("+1 months", strtotime($meetingenddate)));
                                    break;
                                case "3":
                                    $meetingstartdate = date("Y-m-d H:i:s", strtotime("+3 months", strtotime($meetingstartdate)));
                                    $meetingenddate = date("Y-m-d H:i:s", strtotime("+3 months", strtotime($meetingenddate)));
                                    break;
                                case "7":
                                    $meetingstartdate = date("Y-m-d H:i:s", strtotime("+1 week", strtotime($meetingstartdate)));
                                    $meetingenddate = date("Y-m-d H:i:s", strtotime("+1 week", strtotime($meetingenddate)));
                                    break;
                                case "12":
                                    $meetingstartdate = date("Y-m-d H:i:s", strtotime("+1 year", strtotime($meetingstartdate)));
                                    $meetingenddate = date("Y-m-d H:i:s", strtotime("+1 year", strtotime($meetingenddate)));
                                    break;
                            }
                            //以指定数据为单位创建新例会 SNS库
                            $sqladdplan = "insert into we_meeting_plan(id,name,subject,item,meetingstartdate,meetingenddate,meetingtype,identifiers,addrType";
                            $sqladdplan.=" ,defaultAddr,cycle,master,remind,create_staff,create_date) values(";
                            $sqladdplan.="'" . $newplanid . "','" . $row["name"] . "','" . $row["subject"] . "','" . $row["item"] . "','" . $meetingstartdate;
                            $sqladdplan.= "','" . $meetingenddate . "','" . $row["meetingtype"] . "','" . $row["identifiers"] . "','" . $row["addrType"] . "','" . $row["defaultAddr"] . "','" . $row["cycle"] . "','" . $row["master"];
                            $sqladdplan.= "','" . $row["remind"] . "','" . $row["create_staff"] . "',now())";
                            mysql_query($sqladdplan, $conn);

                            $logger->info("线下会议(例会)-SNS库执行SQL语句:" . $sqladdplan);

                            //添加SNS库we_meeting_member表的正式成员
                            $sqlofficial = "select staffid from we_meeting_member where planid='" . $planid . "' and stafftype=1";
                            $dsofficial = mysql_query($sqlofficial, $conn);
                            while ($officialrow = mysql_fetch_array($dsofficial)) {
                                //获取主键
                                $sqlprocmember = "call p_seq_nextvalue('we_meeting_member','id', 1, @nextvalue)";
                                mysql_query($sqlprocmember, $conn);
                                $sqlprocmemberval = "select @nextvalue as nextvalue";
                                $dsmemeberval = mysql_query($sqlprocmemberval, $conn);
                                $rowmemeberid = mysql_fetch_array($dsmemeberval);
                                $memberid = "we" . $rowmemeberid["nextvalue"];
                                mysql_free_result($dsmemeberval);
                                if (!empty($memberid)) {
                                    //添加正式成员 SNS库
                                    $sqladd = "insert into we_meeting_member(id,planid,staffid,stafftype) values('" . $memberid . "','" . $newplanid . "','" . $officialrow["staffid"] . "',1)";
                                    mysql_query($sqladd, $conn);

                                    $logger->info("线下会议(例会)-SNS库执行SQL语句:" . $sqladd);
                                }
                            }
                            mysql_free_result($dsofficial);
                        }
                    }
                }
                sleep(1);
            }
            mysql_free_result($resultplan);

            //数据处理完成  把时间设置成今天的时间
            $recordtime = $datetime;
        } catch (\Exception $e) {
            $logger->err($e);
            //一旦出现异常设置记录时间为昨天
            $recordtime = "";
            if ($conn)
                mysql_close($conn);
        }
        if ($conn_im)
            mysql_close($conn_im);
        break;
    }
    if ($conn)
        mysql_close($conn);
    sleep(3600);
}