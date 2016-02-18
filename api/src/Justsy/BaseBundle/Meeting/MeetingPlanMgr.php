<?php

namespace Justsy\BaseBundle\Meeting;

use Symfony\Component\HttpFoundation\Response;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Common\Utils;

//会议日程/计划管理
class MeetingPlanMgr {

    private $conn = null;
    private $conn_im = null;
    private $user = null;
    private $container = null;

    public function __construct($_db, $_db_im, $user, $container) {
        $this->conn = $_db;
        $this->conn_im = $_db_im;
        $this->user = $user;
        $this->container = $container;
    }

    //发送即时消息(只发送在线的人)
    private function SendImPresence($planid, $title, $tojid, $message, $ismsg, $addtojid, $deltojid) {
        if (empty($message)) {
            //获取消息内容
            if (!empty($planid)) {
                $ds = $this->GetPlan($planid);
                if (count($ds) > 0)
                    $message = json_encode($ds[0]);
                else
                    return false;
            }
            else
                return false;
        }
        if (!empty($this->user)) {
            if (empty($tojid)) {
                $eno = $this->user->eno;
                //根据企业号获取接收人
                $sqltojid = " select GROUP_CONCAT(distinct fafa_jid) as login_account from we_circle t1 left join we_circle_staff t2 on t1.circle_id=t2.circle_id  inner join we_staff t3 on t1.enterprise_no=t3.eno" .
                        " where t1.enterprise_no=? ";
                $parastojid = array((string) $eno);

                $dstojid = $this->conn->GetData("result", $sqltojid, $parastojid);
                $tojid = $dstojid["result"]["rows"][0]["login_account"];
            }
            $staffinfo = "{\"nick_name\":\"" . $this->user->nick_name . "\",\"paras_add_staffid\":\"" . $addtojid . "\",\"paras_del_staffid\":\"" . $deltojid . "\",";
            if (!empty($message))
                $message = str_replace("{", $staffinfo, $message);
//            $staffinfo = "{\"nick_name\":\"" . $this->user->nick_name . "\"";
//            if (!empty($message))
//                $message = str_replace("{", $staffinfo, $message);
            //var_dump($tojid);
            //开始发送消息
            $success = Utils::sendImPresence($this->user->fafa_jid, $tojid, $title . "_meetingplan", $message, $this->container,"","",false,Utils::$systemmessage_code);
            if ($success && $ismsg) {
                $this->SendImMessage($planid, $title, "", $message);
            }
        }
    }

    private function SendImMessage($planid, $title, $tojid, $message) {
        if (!empty($planid)) {
            if (empty($message)) {
                //获取消息内容
                if (!empty($planid)) {
                    $ds = $this->GetPlan($planid);
                    if (count($ds) > 0)
                        $message = json_encode($ds[0]);
                    else
                        return false;
                }
                else
                    return false;
            }
            if (!empty($this->user)) {
                if (empty($tojid)) {
                    $sql = "select GROUP_CONCAT(staffid) as staffid from we_meeting_member where planid=? ";
                    $paras = array((string) $planid);
                    $ds = $this->conn->GetData("result", $sql, $paras);
                    $tojid = $ds["result"]["rows"][0]["staffid"];
                }
                $staffinfo = "{\"nick_name\":\"" . $this->user->nick_name . "\",";
                if (!empty($message))
                    $message = str_replace("{", $staffinfo, $message);
                $title.="_meetingmember";
                //开始发送消息
                Utils::sendImMessage($this->user->fafa_jid, $tojid, $title, $message, $this->container);
            }
        }
    }

    private function GetPlan($planid) {
        $sql = "select * from we_meeting_plan where id=? ";
        $paras = array((string) $planid);
        $ds = $this->conn->GetData("result", $sql, $paras);
        return $ds["result"]["rows"];
    }

//根据日程/计划主键获取会议计划表的数据
    public function GetById($planid) {
        $ds = $this->GetPlan($planid);
        $resp = new Response(json_encode($ds));
        $resp->headers->set('Content-Type', 'text/json');
        return $resp;
    }
    //IM获取会议详细计划  根据群组ID获取最近一条数据
    public function GetPlanByGroupid($groupid){
        if(empty($groupid)){
            $resp = new Response($groupid);
            $resp->headers->set('Content-Type', 'text/json');
            return $resp;
        }
        $sql = "SELECT * FROM we_meeting_plan where groupid=? order by meetingstartdate desc limit 0,1 ;";
        $ds = $this->conn->GetData("result", $sql, array((string) $groupid));
        $array=array("returncode"=>"0000","data"=>$ds["result"]["rows"]);
        $resp = new Response(json_encode($array));
        $resp->headers->set('Content-Type', 'text/json');
        return $resp;
    }

//根据会议组编号获取会议计划表的数据
    public function GetByGroupid($groupid) {
        if(empty($groupid)){
            $resp = new Response($groupid);
            $resp->headers->set('Content-Type', 'text');
            return $resp;
        }
        $sql = "SELECT * FROM we_meeting_plan where meetingstartdate> now() and groupid=? order by meetingstartdate desc limit 0,1";
        $ds = $this->conn->GetData("result", $sql, array((string) $groupid));
        $resp = new Response(json_encode($ds["result"]["rows"]));
        $resp->headers->set('Content-Type', 'text/json');
        return $resp;
    }

    //读取例会历史数据
    public function GetHistoryMeeting($groupid) {
        if(empty($groupid)){
            $resp = new Response($groupid);
            $resp->headers->set('Content-Type', 'text');
            return $resp;
        }
        $sql = "select * from we_meeting_plan where groupid=? order by meetingstartdate ";
        $ds = $this->conn->GetData("rt", $sql, array((string) $groupid));
        $resp = new Response(json_encode($ds));
        $resp->headers->set('Content-Type', 'text/json');
        return $resp;
    }

    public function GetPlanidByGroupId($groupid) {
        if(empty($groupid)){
            $resp = new Response($groupid);
            $resp->headers->set('Content-Type', 'text');
            return $resp;
        }
        $sql = "select id from we_meeting_plan where groupid=? order by meetingstartdate desc limit 0,1";
        $ds = $this->conn->GetData("rt", $sql, array((string) $groupid));
        $resp = new Response(json_encode($ds["rt"]["rows"][0]["id"]));
        $resp->headers->set('Content-Type', 'text');
        return $resp;
    }

    public function StartEndPlan($planid, $realmeetingstartdate, $realmeetingenddate) {
        if(empty($planid)){
            $resp = new Response($planid);
            $resp->headers->set('Content-Type', 'text');
            return $resp;
        }
        $sql = "update we_meeting_plan set realmeetingstartdate=?,realmeetingenddate=? ,operationdate=now() where id=?";
        $paras = array((string) $realmeetingstartdate, (string) $realmeetingenddate, (string) $planid);
        try {
            $this->conn->ExecSQL($sql, $paras);

            $ds = $this->GetStaff($planid, "");

            $addtojid = implode(",", $ds);

            //发送出席消息
            $this->SendImPresence($planid, "startend", "", "", false, $addtojid, "");
        } catch (\Exception $exc) {
            $this->container->get('logger')->err($exc);
            $planid = "";
        }
        $resp = new Response($planid);
        $resp->headers->set('Content-Type', 'text');
        return $resp;
    }

    //开始会议
    public function StartPlan($planid) {
        if(empty($planid)){
            $resp = new Response($planid);
            $resp->headers->set('Content-Type', 'text');
            return $resp;
        }
        $sqlsel = "select realmeetingstartdate from we_meeting_plan where id=? ";
        $parasel = array((string) $planid);
        $dssel = $this->conn->GetData("rt", $sqlsel, $parasel);
        //真实开始时间为空才修改真实开始时间
        if (empty($dssel["rt"]["rows"][0]["realmeetingstartdate"])) {
            $sql = "update we_meeting_plan set realmeetingstartdate=now() where id=? ";
            $paras = array((string) $planid);
            try {
                $this->conn->ExecSQL($sql, $paras);

                $ds = $this->GetStaff($planid, "");

                $addtojid = implode(",", $ds);

                //发送出席消息
                $this->SendImPresence($planid, "start", "", "", false, $addtojid, "");
            } catch (\Exception $exc) {
                $this->container->get('logger')->err($exc);
                $planid = "";
            }
        }
        $resp = new Response($planid);
        $resp->headers->set('Content-Type', 'text');
        return $resp;
    }

    //介绍会议接口
    public function EndPlan($planid) {
        if(empty($planid)){
            $resp = new Response($planid);
            $resp->headers->set('Content-Type', 'text');
            return $resp;
        }
        $sql = "update we_meeting_plan set realmeetingenddate=now() ,operationdate=now()  where id=?";
        $paras = array((string) $planid);
        try {
            $this->conn->ExecSQL($sql, $paras);

            $ds = $this->GetStaff($planid, "");

            $addtojid = implode(",", $ds);

            //发送出席消息
            $this->SendImPresence($planid, "end", "", "", false, $addtojid, "");
        } catch (\Exception $exc) {
            $this->container->get('logger')->err($exc);
            $planid = "";
        }
        $resp = new Response($planid);
        $resp->headers->set('Content-Type', 'text');
        return $resp;
    }

    public function GetDefaultAdd($defaultAddr) {
        if(empty($defaultAddr)){
            $resp = new Response('');
            $resp->headers->set('Content-Type', 'text/json');
            return $resp;
        }
        $sql = "select * from we_meeting_plan where  defaultAddr=? and create_staff like concat('%-',?,'@fafacn.com') order by meetingstartdate desc";
        $ds = $this->conn->GetData("result", $sql, array((string) $defaultAddr));
        $resp = new Response(json_encode($ds["result"]["rows"]));
        $resp->headers->set('Content-Type', 'text/json');
        return $resp;
    }

    //根据会议组编号获取对应时间段的会议计划表的数据
    public function GetByWhere($content) {
        $fields = json_decode($content, true);
        $where = array();
        $paras = array();
        $type = "";
        if(empty($fields)){
            $resp = new Response('');
            $resp->headers->set('Content-Type', 'text');
            return $resp;
        }
        foreach ($fields as $key => $val) {
            $where[$key] = $val;
            if ($key == "type")
                $type = $val;
        }
        $sql = "";
        $resp = "";
        switch ($type) {
            case"":
                //查询同一企业下指定会议室对应时间段的记录数个数
                $sql .= "select count(*) as `count` from we_meeting_plan where  defaultAddr=?  and ";
                $sql .=" (meetingstartdate  between ? and  ? or meetingenddate  between ? and ?) and create_staff like concat('%-',?,'@fafacn.com')";
                array_push($paras, (string) $where["defaultAddr"]);
                array_push($paras, (string) $where["meetingstartdate"]); //开始时间
                array_push($paras, (string) $where["meetingenddate"]); //结束时间
                array_push($paras, (string) $where["meetingstartdate"]); //开始时间
                array_push($paras, (string) $where["meetingenddate"]); //结束时间
                array_push($paras, (string) $this->user->eno);
                $ds = $this->conn->GetData("result", $sql, $paras);
                $count = $ds["result"]["rows"][0]["count"];
                $resp = new Response($count);
                $resp->headers->set('Content-Type', 'text');
                break;
            case"1":
                //查询同一企业下指定会议室对应时间段的会议计划
                $sql .= "select * from we_meeting_plan where  defaultAddr=?  and ";
                $sql .="meetingstartdate  between ? and  ?  and create_staff like concat('%-',?,'@fafacn.com')  order by meetingstartdate asc";
                array_push($paras, (string) $where["defaultAddr"]);
                array_push($paras, (string) $where["meetingstartdate"]); //开始时间
                array_push($paras, (string) $where["meetingenddate"]); //结束时间
                array_push($paras, (string) $this->user->eno);
                $ds = $this->conn->GetData("result", $sql, $paras);
                $resp = new Response(json_encode($ds["result"]["rows"]));
                $resp->headers->set('Content-Type', 'text/json');
                break;
            case"2":
                //查询同一企业下登录人在对应时间段的会议计划
                $sql .= "select t1.* from we_meeting_plan t1 left join we_meeting_member t2 on t1.id=t2.planid where t2.staffid=? and ";
                $sql .="t1.meetingstartdate  between ? and  ?   and t1.create_staff like concat('%-',?,'@fafacn.com') order by t1.meetingstartdate asc";
                array_push($paras, (string) $this->user->fafa_jid); //登录人JID
                array_push($paras, (string) $where["meetingstartdate"]); //开始时间
                array_push($paras, (string) $where["meetingenddate"]); //结束时间
                array_push($paras, (string) $this->user->eno);
                $ds = $this->conn->GetData("result", $sql, $paras);
                $resp = new Response(json_encode($ds["result"]["rows"]));
                $resp->headers->set('Content-Type', 'text/json');
                break;
            case "3":
                //查询某个人当天需要开的会议  以结束会议为标准
                $sql = "select count(1) as count from we_meeting_plan t1 left join we_meeting_member t2 on t1.id=t2.planid where t2.staffid=? and ";
                $sql.="t1.meetingstartdate  between date(now()) and  date(adddate(now(),interval 1 day))  ";
                $sql.="and t1.realmeetingenddate is null  ";
                array_push($paras, (string) $this->user->fafa_jid); //登录人JID
                //array_push($paras, (string) $this->user->eno); //登录人企业号
                $ds = $this->conn->GetData("result", $sql, $paras);
                $count = $ds["result"]["rows"][0]["count"];
                $resp = new Response($count);
                $resp->headers->set('Content-Type', 'text');
                break;
        }
        return $resp;
    }

    public function AddSingleton($content, $staffid, $stafftype) {
        return $this->Add($content, "singleton", $staffid, $stafftype);
    }

    public function AddRegular($content, $staffid, $stafftype) {
        return $this->Add($content, "regular", $staffid, $stafftype);
    }

    //添加一条会议计划表的数据 返回主键 
    private function Add($content, $regular, $staffid, $stafftype) {
        $planid = 'we' . SysSeq::GetSeqNextValue($this->conn, "we_meeting_plan", "id");
        $sql = "insert into we_meeting_plan ";
        $para = array();
        $addFields = "id";
        $addValues = "?";
        array_push($para, (string) $planid);
        $fields = json_decode(urldecode($content),true);
        $groupname = "";
        $groupcreate = "";
        $groupid = "";
        $meetingtype = "";
        $master = "";
        //var_dump($content,$fields);
        if(empty($fields)){
            $resp = new Response(0);
            $resp->headers->set('Content-Type', 'text');
            return $resp;
        }
        $where=array();
        foreach ($fields as $key => $val) {
            if ($val != "") {
                $addFields .= "," . $key;
                $addValues .= ",?";
                array_push($para, (string) $val);
                array_push($where, $key."='".$val."'");
                switch ($key) {
                    case 'name':
                        if(empty($val)){
                            $resp = new Response(0);
                            $resp->headers->set('Content-Type', 'text');
                            return $resp;
                        }
                        $groupname = $val;
                        break;
                    case 'create_staff':
                        $groupcreate = $val;
                        break;
                    case 'groupid':
                        $groupid = $val;
                        break;
                    case 'meetingtype':
                        $meetingtype = $val;
                        break;
                    case 'master':
                        $master = $val;
                        break;
                    case 'meetingstartdate':
                        if(empty($val)){
                            $resp = new Response(0);
                            $resp->headers->set('Content-Type', 'text');
                            return $resp;
                        }
                    break;
                    case 'meetingenddate':
                        if(empty($val)){
                            $resp = new Response(0);
                            $resp->headers->set('Content-Type', 'text');
                            return $resp;
                        }
                    break;
                }
            }
        }
        $sql_planid="select id from we_meeting_plan where ".implode(" AND ", $where) ;

        //var_dump($sql_planid);
        $data_planid= $this->conn->GetData('dt',$sql_planid,array());

        if($data_planid!=null && count($data_planid)>0 && !empty($data_planid["dt"]["rows"][0]["id"])) {
            $resp = new Response($data_planid["dt"]["rows"][0]["id"]);
            $resp->headers->set('Content-Type', 'text');

            return $resp;
        }

        $sqls = array();
        $paras = array();
        $sqlsim = array();
        $parasim = array();
        if ($meetingtype != "0") { //线上会议
            $sql = $sql . "(" . $addFields . ",create_date,groupid,identifiers) values (" . $addValues . ",now(),?,?)";
            //判断是否传递有群主键
            if (empty($groupid)) {
                $groupid = SysSeq::GetSeqNextValue($this->conn_im, "im_group", "groupid");

                $sqlsim[] = "insert into im_group(groupid,groupname,groupclass,groupdesc,creator,add_member_method,accessright) values(?,?,'meeting',?,?,'1','none')";
                $parasim[] = array((string) $groupid, (string) $groupname, (string) $regular, (string) $groupcreate);
            }
        } else { //线下会议
            $sql = $sql . "(" . $addFields . ",create_date,identifiers) values (" . $addValues . ",now(),?)";
            $groupid = "";
        }
        //线上会议 添加群组主键
        if ($meetingtype != "0") {
            array_push($para, (string) $groupid);
        }
        //添加唯一标识符的参数
        array_push($para, (string) $planid);
        $sqls[] = $sql;
        $paras[] = $para;

        $stafflist = explode(",", $staffid);
        $memberids = array();
        for ($i = 0; $i < count($stafflist); $i++) {
            $id = "we" . SysSeq::GetSeqNextValue($this->conn, "we_meeting_member", "id");
            $sqls[] = "insert into we_meeting_member(id,planid,staffid,stafftype) values(?,?,?,?)";
            $paras[] = array((string) $id, (string) $planid, (string) $stafflist[$i], (string) $stafftype);
            array_push($memberids, $id);

            if (!empty($groupid)) {
                if ($stafflist[$i] == $master) {
                    $sqlsim[] = "insert into im_groupemployee(employeeid,groupid,grouprole,employeenick) values(?,?,'owner',(select employeename from im_employee where loginname=? limit 0,1))";
                    $parasim[] = array((string) $stafflist[$i], (string) $groupid, (string) $stafflist[$i]);
                } else {
                    $sqlsim[] = "insert into im_groupemployee(employeeid,groupid,grouprole,employeenick) values(?,?,'normal',(select employeename from im_employee where loginname=? limit 0,1))";
                    $parasim[] = array((string) $stafflist[$i], (string) $groupid, (string) $stafflist[$i]);
                }
                //删除IM人员 群组版本号数据
                $sqlsim[]="DELETE FROM im_group_version WHERE us=? ;";
                $parasim[]=array($stafflist[$i]);
                //新增无需删除
                //$sqlsim[]="DELETE FROM im_groupemployee_version WHERE us=? AND groupid=? ;";
                //$parasim[]=array($stafflist[$i],$groupid);
            }
        }
        try {
            if (!empty($sqls)) {
                $this->conn->ExecSQLs($sqls, $paras);
            }
            if (!empty($sqlsim)) {
                $this->conn_im->ExecSQLs($sqlsim, $parasim);
            }

            //发送即使消息(有关人员)
            $this->SendImPresence($planid, "add", "", "", true, $staffid, "");

            //发送即使消息(有关人员)
            //$this->SendImMessage($planid, "add", "", "");
        } catch (\Exception $exc) {
            //做数据删除的操作
            $sqls = array();
            $paras = array();

            $sqls[] = $this->GetDelSql("we_meeting_plan", "id");
            $paras[] = array((string) $planid);

            $sqls[] = $this->GetDelSql("we_meeting_member", "planid");
            $paras[] = array((string) $planid);

            $this->conn->ExecSQLs($sqls, $paras);
            if (!empty($groupid)) {
                $sqlsim = array();
                $parasim = array();

                $sqlsim[] = $this->GetDelSql("im_group", "groupid");
                $parasim[] = array((string) $groupid);

                $sqlsim[] = $this->GetDelSql("im_groupemployee", "groupid");
                $parasim[] = array((string) $groupid);

                $this->conn_im->ExecSQL($sqlsim, $parasim);
            }
            $this->container->get('logger')->err($exc);
            $planid = 0;
        }
        $resp = new Response($planid);
        $resp->headers->set('Content-Type', 'text');

        return $resp;
    }

    private function GetDelSql($tablename, $idname) {
        $sql = "delete from " . $tablename . " where " . $idname . "=?";
        return $sql;
    }

//修改一条会议计划表的数据 返回主键 $c 1 线下修改成线上 2 线上修改成线下
    public function Update($planid, $content, $staffid, $stafftype, $c) {
        $sqls = array();
        $paras = array();
        $sqlsim = array();
        $parasim = array();
        $sql = "update we_meeting_plan set ";
        $fields = json_decode(urldecode($content), true);
        $upFields = "";
        $name = "";
        $groupname = "";
        $para = array();
        if(empty($fields)){
            $resp = new Response(0);
            $resp->headers->set('Content-Type', 'text');
            return $resp;
        }
        $new_master="";
        foreach ($fields as $key => $val) {
            if ($val != "") {
                if ($upFields != "") $upFields.=",";
                $upFields.=$key."=? ";
                array_push($para, (string) $val);
                switch ($key) {
                    case 'name':
                        $name = $val;
                        $groupname = $val;
                        break;
                    case 'master':
                        $new_master = $val;
                        break;
                }
            }
        }

        //删除SNS库群成员
        $sqls[] = "delete from we_meeting_member where planid=?  and stafftype=?";
        $paras[] = array((string) $planid, (string) $stafftype);

        //获取修改后的成员集合
        $staffnew = array();
        if (!empty($staffid)) {
            $stafflist = explode(",", $staffid);
            if (!empty($stafflist))
                for ($i = 0; $i < count($stafflist); $i++) {
                    if (!empty($stafflist[$i])) {
                        //添加会议成员 SNS库
                        $id = "we" . SysSeq::GetSeqNextValue($this->conn, "we_meeting_member", "id");
                        $sqls[] = "insert into we_meeting_member(id,planid,staffid,stafftype) values(?,?,?,?)";
                        $paras[] = array((string) $id, (string) $planid, (string) $stafflist[$i], (string) $stafftype);
                        array_push($staffnew, $stafflist[$i]);
                    }
                }
        }

        try {

            $ds = $this->GetPlan($planid);
            //获取信息
            $groupid = $ds[0]["groupid"];
            $groupcreate = $ds[0]["create_staff"];
            $master = $ds[0]["master"];
            if(!empty($new_master)) $master = $new_master;
            $tojid_del = "";
            $tojid_add = "";
            switch ($c) {
                case 1: //线下修改成线上  groupid  一定为空
                    $groupid = SysSeq::GetSeqNextValue($this->conn_im, "im_group", "groupid");

                    $sqlsim[] = "insert into im_group(groupid,groupname,groupclass,groupdesc,creator,add_member_method,accessright) values(?,?,'meeting','regular',?,'1','none')";
                    $parasim[] = array((string) $groupid, (string) $groupname, (string) $groupcreate);
 
                    //获取修改后的成员集合
                    if (!empty($staffid)) {
                        $stafflist = explode(",", $staffid);
                        if (!empty($stafflist)) {
                            for ($i = 0; $i < count($stafflist); $i++) {
                                if (!empty($stafflist[$i])) {
                                    if (!empty($groupid)) {
                                    	$groupemployee_grouprole='normal';
                                        if ($stafflist[$i] == $master) $groupemployee_grouprole='owner';//会议主持人标志不一样
                                        //添加群成员 IM库
                                        $sqlsim[] = "insert into im_groupemployee(employeeid,groupid,grouprole,employeenick) values(?,?,?,(select employeename from im_employee where loginname=? limit 0,1))";
                                        $parasim[] = array((string)$stafflist[$i], (string)$groupid,(string)$groupemployee_grouprole, (string) $stafflist[$i]);
                                        $sqlsim[]="DELETE FROM im_groupemployee_version WHERE groupid=? AND  us=?;";
                                        $parasim[]=array($groupid,$stafflist[$i]);
                                        $sqlsim[]= "DELETE FROM we_im.im_group_version WHERE us=? ";
                                        $parasim[]=array($stafflist[$i]);
                                    }
                                }
                            }
                        }
                    }
                    $sql = $sql . $upFields . ",groupid=? where id=?";
                    array_push($para, (string) $groupid);
                    array_push($para, (string) $planid);
                    $sqls[] = $sql;
                    $paras[] = $para;
                    break;
                case 2: //线上修改成线下  groupid 一定不为空
                    $sql = $sql . $upFields . ",groupid=? where id=?";
                    array_push($para, (string) "");
                    array_push($para, (string) $planid);
                    $sqls[] = $sql;
                    $paras[] = $para;

                    $sqlsim[] = "delete from im_group where groupid=?";
                    $parasim[] = array((string) $groupid);
                    $sqlsim[] = "delete from im_groupemployee where groupid=?";
                    $parasim[] = array((string) $groupid);
                    $sqlsim[]="DELETE FROM im_groupemployee_version WHERE groupid=? ;";
                    $parasim[]=array($groupid);
                    $sql_im_groupemployee="SELECT employeeid FROM we_im.im_groupemployee WHERE groupid=? AND employeeid IS NOT NULL ;";
                    $para_im_groupemployee=array($groupid);
                    $data_im_groupemployee = $this->conn_im->GetData('dt',$sql_im_groupemployee,$para_im_groupemployee);
                    if($data_im_groupemployee!=null && count($data_im_groupemployee["dt"]["rows"])>0 && $data_im_groupemployee["dt"]["rows"][0]['employeeid']) {
                        for ($i=0; $i < count($data_im_groupemployee["dt"]["rows"]); $i++) { 
                            $sqlsim[]= "DELETE FROM we_im.im_group_version WHERE us=? ";
                            $parasim[]=array($data_im_groupemployee["dt"]["rows"][$i]['employeeid']);
                        }
                    }
                    //$fafa_jid=$this->user->fafa_jid;
                    //$sqlsim[]="DELETE FROM im_group_version WHERE us=? ;";
                    //$parasim[]=array($fafa_jid);
                    break;
                default :
                    $sql = $sql . $upFields . " where id=?";
                    array_push($para, (string) $planid);
                    $sqls[] = $sql;
                    $paras[] = $para;
                    //读取原始成员
                    $staffold = $this->GetStaff($planid, $stafftype);
                    if (!empty($groupid)) {
                    	$sqlsim[] = "delete from im_groupemployee where groupid=?";
                        $parasim[] = array((string) $groupid);
                        $sqlsim[]="DELETE FROM im_groupemployee_version WHERE groupid=? ;";
                        $parasim[]=array($groupid);
                    }
                    //获取修改后的成员集合
                    if (!empty($staffid)&&!empty($groupid)) {
                        $stafflist = explode(",", $staffid);
                        if (!empty($stafflist)){
                            for ($i = 0; $i < count($stafflist); $i++) {
                                if (!empty($stafflist[$i])) {
                                	$groupemployee_grouprole='normal';
                                    if ($stafflist[$i] == $master)$groupemployee_grouprole='owner';//会议主持人标志不一样
                                    //添加群成员 IM库
                                    $sqlsim[] = "insert into im_groupemployee(employeeid,groupid,grouprole,employeenick) values(?,?,?,(select employeename from im_employee where loginname=? limit 0,1))";
                                    $parasim[] = array((string)$stafflist[$i], (string)$groupid,(string)$groupemployee_grouprole, (string) $stafflist[$i]);
                                }
                            }
                         }
                    }
                    $memeber = array();
                    //判断历史人员集合存在并且新成员不为空处理消息发送集合
                    if (count($staffold) > 0 && !empty($staffnew)) {
                        //处理消息发送人
                        $staff_del = array_diff($staffold, $staffnew); //已删除的会议计划人员
                        $staff_add = array_diff($staffnew, $staffold); //新增的会议计划人员
                        $tojid_del = implode(",", $staff_del);
                        $tojid_add = implode(",", $staff_add);
                        if (!empty($tojid_del)) {
                            array_push($memeber, array((string) $tojid_del, "del"));
                        }
                        if (!empty($tojid_add)) {
                            array_push($memeber, array((string) $tojid_add, "add"));
                        }
                    } else {
                        $tojid = implode(",", $staffold);
                        array_push($memeber, array((string) $tojid, "add"));
                    }
                    //判断修改是否包含名称
                    if (!empty($name)) {
                        if (!empty($groupid)) {
                            $sqlsim[] = "update im_group set groupname=? where groupid=?";
                            $parasim[] = array((string) $name, (string) $groupid);
                        }
                    }
                    break;
            }

            if (!empty($sqls))
                $this->conn->ExecSQLs($sqls, $paras);

            if (!empty($sqlsim))
                $this->conn_im->ExecSQLs($sqlsim, $parasim);

            $this->SendImPresence($planid, "upd", "", "", false, $tojid_add, $tojid_del);

            if (!empty($memeber)) {
                $count = count($memeber);
                for ($i = 0; $i < $count; $i++) {
                    $this->SendImMessage($planid, $memeber[$i][1], $memeber[$i][0], "");
                }
            }
        } catch (\Exception $exc) {
            $this->container->get('logger')->err($exc);
            $planid = 0;
        }
        $resp = new Response($planid);
        $resp->headers->set('Content-Type', 'text');
        return $resp;
    }

    private function GetStaffAll($planid, $stafftype) {
        //获取修改前的成员集合
        $sql = "";
        $paras = "";
        if (!empty($stafftype)) {
            $sql = "select * from we_meeting_member where planid=? and stafftype=?";
            $paras = array((string) $planid, (string) $stafftype);
        } else {
            $sql = "select * from we_meeting_member where planid=?";
            $paras = array((string) $planid);
        }
        $ds = $this->conn->GetData("result", $sql, $paras);
        return $ds["result"]["rows"];
    }

    private function GetStaff($planid, $stafftype) {
        //获取修改前的成员集合
        $stafflist = $this->GetStaffAll($planid, $stafftype);
        $staffold = array();
        for ($i = 0; $i < count($stafflist); $i++)
            array_push($staffold, (string) $stafflist[$i]["staffid"]);
        return $staffold;
    }

    private function GetImStaff($groupid) {
        $stafflist = $this->GetImStaffAll($groupid);
        $staffold = array();
        for ($i = 0; $i < count($stafflist); $i++)
            array_push($staffold, (string) $stafflist[$i]["employeeid"]);
        return $staffold;
    }

    private function GetImStaffAll($groupid) {
        $sql = "select * from im_groupemployee where groupid=? and grouprole='normal'";
        $paras = array((string) $groupid);
        $ds = $this->conn_im->GetData("result", $sql, $paras);
        return $ds["result"]["rows"];
    }

    //删除一条会议计划表的数据
    public function Del($planid) {
        if(empty($planid)){
            $resp = new Response('');
            $resp->headers->set('Content-Type', 'text/json');
            return $resp;
        }
        $ds = $this->GetPlan($planid);
        $groupid = $ds[0]["groupid"];
        $sqls = array();
        $paras = array();
        $sqlsim = array();
        $parasim = array();
        $staffold = "";
        $cycle = $ds[0]["cycle"];
        //线上语音会议
        if (!empty($groupid)) {
            //例会   创建新例会  删除临时成员
            if (!empty($cycle)) {
                $newplanid = "we" . SysSeq::GetSeqNextValue($this->conn, "we_meeting_plan", "id");
                //修改例会时间  以周为单位进行修改
                $sqls[] = "insert into we_meeting_plan(id,groupid,name,subject,item,meetingstartdate,meetingenddate,meetingtype,addrType,defaultAddr,cycle,master,remind,create_staff,create_date) values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                $sqlplan_data = "select * from (select meetingstartdate,meetingenddate from we_meeting_plan where groupid='" . $groupid . "' order by meetingstartdate limit 0,1) as t1";
                $sqlplan_data.=" union ";
                $sqlplan_data.="select * from (select meetingstartdate,meetingenddate from we_meeting_plan where groupid='" . $groupid . "' order by meetingstartdate desc  limit 0,1) as t2 ";

                $parasplan_date = array((string) $groupid);
                $dsplan_date = $this->conn->GetData("rt", $sqlplan_data, $parasplan_date);
                $meetingstartdate = "";
                $meetingenddate = "";
                $starttime = "";
                $endtime = "";
                $count = count($dsplan_date);
                if ($count > 0) {
                    for ($i = 0; $i < $count; $i++) {
                        if ($i == 0) {//只有一条数据
                            $meetingstartdate = $dsplan_date["rt"]["rows"][$i]["meetingstartdate"];
                            $meetingenddate = $dsplan_date["rt"]["rows"][$i]["meetingenddate"];

                            $starttime = date("H:i:s", strtotime($meetingstartdate));
                            $endtime = date("H:i:s", strtotime($meetingenddate));
                        } else {
                            $startdate = date("Y-m-d", strtotime($dsplan_date["rt"]["rows"][$i]["meetingstartdate"]));
                            $enddate = date("Y-m-d", strtotime($dsplan_date["rt"]["rows"][$i]["meetingenddate"]));

                            $meetingstartdate = $startdate . " " . $starttime;
                            $meetingenddate = $enddate . " " . $endtime;
                            break;
                        }
                    }
                }
                $cycle = $ds[0]["cycle"];
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
                $name = $ds[0]["name"];
                $subject = $ds[0]["subject"];
                $item = $ds[0]["item"];
                $meetingtype = $ds[0]["meetingtype"];
                $addrType = $ds[0]["addrType"];
                $defaultAddr = $ds[0]["defaultAddr"];
                $master = $ds[0]["master"];
                $remind = $ds[0]["remind"];
                $create_staff = $ds[0]["create_staff"];
                $create_date = $ds[0]["create_date"];

                $paras[] = array((string) $newplanid, $groupid, $name, $subject, $item, $meetingstartdate, $meetingenddate, $meetingtype
                    , $addrType, $defaultAddr, $cycle, $master, $remind, $create_staff, $create_date);
                //删除例会临时成员
                $sqls[] = "delete from we_meeting_member where planid=? and stafftype!='1'";
                $paras[] = array((string) $planid);

                $sqlsim[]="DELETE FROM im_groupemployee_version WHERE groupid=?";
                $parasim[]=array($groupid);
                //需要删除群组的相关人员
                $staffold = $this->GetStaffByType($planid);
                for ($i = 0; $i < count($staffold); $i++) {
                    $sqlsim[] = "delete from im_groupemployee where groupid=? and employeeid=?";
                    $parasim[] = array((string) $groupid, (string) $staffold[$i]);

                    $sqlsim[]= "DELETE FROM we_im.im_group_version WHERE us=? ";
                    $parasim[]=array($staffold[$i]);
                }
            } else {//单例会  删除群组和成员
                //删除会议计划所有成员
                $sqls[] = "delete from we_meeting_member where planid=?";
                $paras[] = array((string) $planid);
                //删除一次性会议计划
                $sqls[] = "delete from we_meeting_plan where id=? ";
                $paras[] = array((string) $planid);

                //删除对应群组的所有人员
                $sqlsim[] = "delete from im_groupemployee where groupid=?";
                $parasim[] = array((string) $groupid);

                //删除会议计划对应的群组
                $sqlsim[] = "delete from im_group where groupid=?";
                $parasim[] = array((string) $groupid);

                $sqlsim[]="DELETE FROM im_groupemployee_version WHERE groupid=?";
                $parasim[]=array($groupid);
                $sql_im_groupemployee="SELECT employeeid FROM we_im.im_groupemployee WHERE groupid=? AND employeeid IS NOT NULL ;";
                $para_im_groupemployee=array($groupid);
                $data_im_groupemployee=$conn_im->GetData('dt',$sql_im_groupemployee,$para_im_groupemployee);
                if($data_im_groupemployee!=null && count($data_im_groupemployee["dt"]["rows"])>0 && $data_im_groupemployee["dt"]["rows"][0]['employeeid']) {
                    for ($i=0; $i < count($data_im_groupemployee["dt"]["rows"]); $i++) { 
                        $sqlsim[]= "DELETE FROM we_im.im_group_version WHERE us=? ";
                        $parasim[]=array($data_im_groupemployee["dt"]["rows"][$i]['employeeid']);
                    }
                }
            }
        } else { //线下会议
            //例会   创建新例会  删除临时成员
            if (!empty($cycle)) {
                
            } else { //单例会
                //删除会议计划所有成员
                $sqls[] = "delete from we_meeting_member where planid=?";
                $paras[] = array((string) $planid);
                //删除一次性会议计划
                $sqls[] = "delete from we_meeting_plan where id=? ";
                $paras[] = array((string) $planid);
            }
        }
        try {
            if (!empty($groupid)) { //线上会议  
                if (!empty($sqls)) {
                    $this->conn->ExecSQLs($sqls, $paras);
                }
                if (!empty($sqlsim)) {
                    $this->conn_im->ExecSQLs($sqlsim, $parasim);
                }
            } else { //线下会议
                if (!empty($sqls)) {
                    $this->conn->ExecSQLs($sqls, $paras);
                }
            }

            //发送出席消息
            $this->SendImPresence($planid, "del", "", "", false, "", "");

            if (!empty($cycle)) { //例会发送提示消息
                if (!empty($staffold)) {
                    $tojid = implode(",", $staffold);

                    $this->SendImMessage($planid, "del", $tojid, "");
                }
            } else { //线下会议
                $ds = $this->GetStaff($planid, "");

                $tojid = implode(",", $ds);

                $this->SendImMessage($planid, "del", $tojid, "");
            }
        } catch (\Exception $exc) {
            $this->container->get('logger')->err($exc);
            $planid = 0;
        }
        $resp = new Response($planid);
        $resp->headers->set('Content-Type', 'text');
        return $resp;
    }

    //获取临时成员
    private function GetStaffByType($planid) {
        $sql = "select staffid from we_meeting_member where planid=? and stafftype!='1'";
        $paras = array((string) $planid);
        $ds = $this->conn->GetData("result", $sql, $paras);
        $stafflist = $ds["result"]["rows"];
        $staffold = array();
        for ($i = 0; $i < count($stafflist); $i++)
            array_push($staffold, (string) $stafflist[$i]["staffid"]);
        return $staffold;
    }

    //获取群信息
    private function GetGroup($groupid) {
        $sqlim = "select * from im_group where groupid=?";
        $parasim = array((string) $groupid);
        $dsim = $this->conn_im->GetData("rt", $sqlim, $parasim);
        return $dsim["rt"]["rows"];
    }

    public function DelPlan($planid) {
        if(empty($planid)){
            $resp = new Response('');
            $resp->headers->set('Content-Type', 'text/json');
            return $resp;
        }
        $sqls = array();
        $paras = array();
        $sqlsim = array();
        $parasim = array();
        $tojid = "";
        //线上语音会议
        if (!empty($planid)) {
            $sqlstaff = "select GROUP_CONCAT(staffid) as staffid from we_meeting_member where planid=?";
            $parastaff = array((string) $planid);
            $dsstaff = $this->conn->GetData("rt", $sqlstaff, $parastaff);
            $tojid = $dsstaff["rt"]["rows"][0]["staffid"];
            //删除一次性会议计划
            $sqls[] = "delete from we_meeting_member where planid=? ";
            $paras[] = array((string) $planid);
            //删除会议计划所有成员
            $sqls[] = "delete from we_meeting_plan where id=?";
            $paras[] = array((string) $planid);

            $ds = $this->GetPlan($planid);

            if (!empty($ds)) {
                $groupid = $ds[0]["groupid"];

                if (!empty($groupid)) {
                    //删除会议计划对应的群组
                    $sqlsim[] = "delete from im_group where groupid=?";
                    $parasim[] = array((string) $groupid);
                    //删除对应群组的所有人员
                    $sqlsim[] = "delete from im_groupemployee where groupid=?";
                    $parasim[] = array((string) $groupid);
                }
            }
        }
        try {
            $sqlmsg = "select * from we_meeting_plan where id=?";
            $paramsg = array((string) $planid);
            $dsmsg = $this->conn->GetData("rt", $sqlmsg, $paramsg);
            $message = "";
            if (!empty($dsmsg)) {
                $message = json_encode($dsmsg["rt"]["rows"]);
            }
            if (!empty($sqls)) {
                $this->conn->ExecSQLs($sqls, $paras);
            }
            if (!empty($sqlsim)) {
                $this->conn_im->ExecSQLs($sqlsim, $parasim);
            }

            //发送出席消息
            $this->SendImPresence($planid, "del", "", $message, false, "", $tojid);

            if (!empty($tojid))
                $this->SendImMessage($planid, "del", $tojid, $message);
        } catch (\Exception $exc) {
            $this->container->get('logger')->err($exc);
            $planid = 0;
        }
        $resp = new Response($planid);
        $resp->headers->set('Content-Type', 'text');
        return $resp;
    }
    
    //根据id判断会议是否存在
    public function ExistsMeeting($planid)
    {
    	  $result = 0;
    	  $sql = "select id from we_meeting_plan where id=? or groupid=?";
        $ds = $this->conn->GetData("result", $sql, array((string)$planid,(string)$planid));
        if ( $ds && count($ds["result"]["rows"])>0)
           $result = 1;
        $resp = new Response($result);
        $resp->headers->set('Content-Type', 'text');
        return $resp;
    }
}