<?php

namespace Justsy\BaseBundle\Meeting;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Common\Utils;

class MeetingMemberMgr {

    private $conn = null;
    private $conn_im = null;
    private $user = null;
    private $container = null;

    public function __construct($_db, $_db_im, $_db_user, $_db_container) {
        $this->conn = $_db;
        $this->conn_im = $_db_im;
        $this->user = $_db_user;
        $this->container = $_db_container;
    }

    //发送即时消息(只发送在线的人)
    private function SendImPresence($planid, $title, $tojid, $message) {
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
            $staffinfo = "{\"nick_name\":\"" . $this->user->nick_name . "\",";
            if (!empty($message))
                $message = str_replace("{", $staffinfo, $message);
            //开始发送出席消息
            Utils::sendImPresence($this->user->fafa_jid, $tojid, $title . "_meetingmember", $message, $this->container,"","",false,Utils::$systemmessage_code);
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
                //开始发送消息
                Utils::sendImMessage($this->user->fafa_jid, $tojid, $title . "_meetingmember", $message, $this->container,"","",false,Utils::$systemmessage_code);
            }
        }
    }

    private function GetPlan($planid) {
        $sql = "select * from we_meeting_plan where id=? ";
        $paras = array((string) $planid);
        $ds = $this->conn->GetData("result", $sql, $paras);
        return $ds["result"]["rows"];
    }

    private function GetStaff($planid) {
        //获取修改前的成员集合
        $sql = "select staffid from we_meeting_member where planid=?";
        $paras = array((string) $planid);
        $ds = $this->conn->GetData("result", $sql, $paras);
        $stafflist = $ds["result"]["rows"];
        $staffold = array();
        for ($i = 0; $i < count($stafflist); $i++)
            array_push($staffold, (string) $stafflist[$i]["staffid"]);
        return $staffold;
    }

    private function GetStaffByType($planid) {
        $sql = "select staffid from we_meeting_member where planid=? and stafftype!=1";
        $paras = array((string) $planid);
        $ds = $this->conn->GetData("result", $sql, $paras);
        $stafflist = $ds["result"]["rows"];
        $staffold = array();
        for ($i = 0; $i < count($stafflist); $i++)
            array_push($staffold, (string) $stafflist[$i]["staffid"]);
        return $staffold;
    }

    private function GetImStaff($groupid) {
        $sql = "select employeeid from im_groupemployee where groupid=? and grouprole='normal'";
        $paras = array((string) $groupid);
        $ds = $this->conn_im->GetData("result", $sql, $paras);
        $stafflist = $ds["result"]["rows"];
        $staffold = array();
        for ($i = 0; $i < count($stafflist); $i++)
            array_push($staffold, (string) $stafflist[$i]["employeeid"]);
        return $staffold;
    }

    //删除we_meeting_member中对应的人员数据  
    public function DelByStaffId($planid, $staffid) {
        $sqls = array();
        $paras = array();
        $stafflist = explode(",", $staffid);
        $ount = count($stafflist);
        for ($i = 0; $i < $ount; $i++) {
            if (!empty($stafflist[$i])) {
                $sqls[] = "delete from we_meeting_member where planid=? and staffid=?";
                $paras[] = array((string) $planid, (string) $stafflist[$i]);
            }
        }
        try {
            if (!empty($sqls))
                $this->conn->ExecSQLs($sqls, $paras);
        } catch (\Exception $exc) {
            $this->container->get('logger')->err($exc);
            $planid = 0;
        }
        $resp = new Response($planid);
        $resp->headers->set('Content-Type', 'text');
        return $resp;
    }

    //删除we_meeting_member中对应的人员数据  参数array类型
    private function DelMeetingMemberByIds($memberids) {
        $sqls = array();
        $paras = array();
        $count = count($memberids);
        for ($i = 0; $i < $count; $i++) {
            if (!empty($memberids[$i])) {
                $sqls[] = "delete from we_meeting_member where id=?";
                $paras[] = array((string) $memberids[$i]);
            }
        }
        if (!empty($sqls))
            $this->conn->ExecSQLs($sqls, $paras);
    }

    //只添加会议成员(由主持人在会议界面添加)
    public function AddOneMember($planid, $staffid, $stafftype) {
        $id = SysSeq::GetSeqNextValue($this->conn, "we_meeting_member", "id");
        $sql = "insert into we_meeting_member(id,planid,staffid,stafftype) values(?,?,?,?)";
        $para = array((string) "we" . $id, (string) $planid, (string) $staffid, (string) $stafftype);
        try {
            if (!empty($planid)) {
                $this->conn->ExecSQL($sql, $para);

                $this->SendImPresence($planid, "add", $staffid, "");
            }
        } catch (\Exception $exc) {
            $this->DelMeetingMemberByIds($staffid);
            $this->container->get('logger')->err($exc);
            $planid = 0;
        }
        $resp = new Response($planid);
        $resp->headers->set('Content-Type', 'text');
        return $resp;
    }

    public function Add($planid, $staffid, $stafftype) {
        $stafflist = explode(",", $staffid);
        $sqls = array();
        $paras = array();
        $sqlsim = array();
        $parasim = array();
        $dsim = $this->GetPlan($planid);
        $groupid = $dsim[0]["groupid"];
        $memberids = array();
        $count = count($stafflist);
        for ($i = 0; $i < $count; $i++) {
            if (!empty($stafflist[$i])) {
                $id = SysSeq::GetSeqNextValue($this->conn, "we_meeting_member", "id");
                $sqls[] = "insert into we_meeting_member(id,planid,staffid,stafftype) values(?,?,?,?)";
                $paras[] = array((string) "we" . $id, (string) $planid, (string) $stafflist[$i], (string) $stafftype);
                array_push($memberids, "we" . $id);
                if (!empty($groupid)) {
                    $sqlsim[] = "insert into im_groupemployee(employeeid,groupid,grouprole,employeenick) values(?,?,'normal',(select employeename from im_employee where loginname=? limit 0,1))";
                    $parasim[] = array((string) $stafflist[$i], (string) $groupid, (string) $stafflist[$i]);
                }
            }
        }
        try {
            if (!empty($planid)) {
                $this->conn->ExecSQLs($sqls, $paras);

                //添加IM库中的数据
                if (!empty($groupid)) {

                    if (!empty($sqlsim)) {
                        $this->conn_im->ExecSQLs($sqlsim, $parasim);
                    }
                }
                $this->SendImPresence($planid, "add", $staffid, "");

                //发送即使消息(有关人员)
                $this->SendImMessage($planid, "add", $staffid, "");
            }
        } catch (\Exception $exc) {
            $this->DelMeetingMemberByIds($memberids);
            $this->container->get('logger')->err($exc);
            $planid = 0;
        }
        $resp = new Response($planid);
        $resp->headers->set('Content-Type', 'text');
        return $resp;
    }

//根据会议计划主键修改相关人员信息
    public function Upd($planid, $content) {
        $sqls = array();
        $paras = array();
        $staff = json_decode($content, true);
        $sqls[] = "delete we_meeting_member where planid=? ";
        $paras[] = array((string) $planid);
        //获取修改后的成员集合
        $staffnew = array();
        for ($i = 0; $i < count($staff); $i++) {
            $id = SysSeq::GetSeqNextValue($this->conn, "we_meeting_member", "id");
            $sql = "insert into we_meeting_member(id,planid,";
            $fileds = "";
            $vals = "";
            $para = array();
            array_push($para, (string) "we" . $id);
            array_push($para, (string) $planid);
            foreach ($staff[$i] as $key => $val) {
                if ($val != "") {
                    if (!empty($fileds))
                        $fileds.=",";
                    $fileds.=$key;
                    if (!empty($vals))
                        $vals.=",";
                    $vals.="?";
                    array_push($para, (string) $val);
                    if ($key == "staffid")
                        array_push($staffnew, (string) $val);
                }
            }
            $sql.=$fileds . ") values (?,?," . $vals . ")";
            $sqls[] = $sql;
            $paras[] = $para;
        }
        try {
            if (!empty($planid)) {
                $staffold = $this->GetStaff($planid);

                $this->conn->ExecSQLs($sqls, $paras);

                $dsmsg = $this->GetPlan($planid);
                $message = json_encode($dsmsg[0]);
                if (count($staffold) > 0) {
                    $staff_del = array_diff($staffold, $staffnew); //已删除的会议计划人员
                    $staff_add = array_diff($staffnew, $staffold); //新增的会议计划人员
                    $tojid_del = implode(",", $staff_del);
                    $tojid_add = implode(",", $staff_add);
                    if (!empty($tojid_del)) {
                        $this->SendImPresence($planid, "del", $tojid_add, $message);

                        $this->SendImMessage($planid, "del", $tojid_add, $message);
                    }
                    if (!empty($tojid_add)) {

                        $this->SendImPresence($planid, "add", $tojid_add, $message);

                        $this->SendImMessage($planid, "add", $tojid_add, $message);
                    }
                } else {
                    $tojid = implode(",", $staffold);

                    $this->SendImPresence($planid, "add", $tojid, $message);

                    $this->SendImMessage($planid, "add", $tojid, $message);
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

    //根据会议计划主键获取人员信息
    public function Sel($planid) {
        $sql = "select  GROUP_CONCAT(t2.fafa_jid) as fafa_jid,GROUP_CONCAT(t2.nick_name) as nick_name from we_meeting_member t1 left join we_staff t2 on t1.staffid=t2.fafa_jid where t1.planid=? ";
        $para = array((string) $planid);
        $ds = $this->conn->GetData("result", $sql, $para);
        $resp = new Response(json_encode($ds["result"]["rows"]));
        $resp->headers->set('Content-Type', 'text/json');
        return $resp;
    }

    //根据会议计划主键删除人员信息
    public function Del($planid) {
        $sql = "delete from we_meeting_member where planid=? ";
        $paras = array((string) $planid);
        try {
            $staffold = $this->GetStaff($planid);

            $ds = $this->GetPlan($planid);

            $this->conn->ExecSQL($sql, $paras);

            $sqlim = "delete from im_groupemployee where groupid=?";
            $parasim = array((string) $ds[0]["groupid"]);

            $this->conn_im->ExecSQL($sqlim, $parasim);

            $tojid = implode(",", $staffold);
            if (!empty($tojid)) {

                $message = json_encode($ds[0]);

                $this->SendImPresence($planid, "del", $tojid, $message);

                $this->SendImMessage($planid, "del", $tojid, $message);
            }
        } catch (\Exception $exc) {
            $this->container->get('logger')->err($exc);
            $planid = 0;
        }
        $resp = new Response($planid);
        $resp->headers->set('Content-Type', 'text');
        return $resp;
    }

    //删除会议计划临时人员信息
    public function DelByStaffType($planid) {
        $sql = "delete from we_meeting_member where planid=? and stafftype!='1'";
        $paras = array((string) $planid);
        try {
            $staffold = $this->GetStaffByType($planid);

            $ds = $this->GetPlan($planid);

            $this->conn->ExecSQL($sql, $paras);

            $groupid = $ds[0]["groupid"];
            $sqlsim = array();
            $parasim = array();
            for ($i = 0; $i < count($staffold); $i++) {
                $sqlsim[] = "delete from im_groupemployee where groupid=? and employeeid=?";
                $parasim[] = array((string) $groupid, (string) $staffold[$i]);
            }
            if (empty($sqlsim))
                $this->conn_im->ExecSQLs($sqlsim, $parasim);

            $tojid = implode(",", $staffold);
            if (!empty($tojid)) {
                $message = json_encode($ds[0]);

                $this->SendImPresence($planid, "del", $tojid, $message);

                $this->SendImMessage($planid, "del", $tojid, $message);
            }
        } catch (\Exception $exc) {
            $this->container->get('logger')->err($exc);
            $planid = 0;
        }
        $resp = new Response($planid);
        $resp->headers->set('Content-Type', 'text');
        return $resp;
    }

    //根据会议计划ID获得参会人员及主持人
    public function GetMemberAndMaster($planid) {
        $sql = "select planid,group_concat(staffid) memberid,group_concat(nick_name) member ,master masterid,(select nick_name from we_staff where fafa_jid=master) mastername " .
                "from we_meeting_member a inner join we_meeting_plan b  on planid=b.id inner join we_staff on staffid=fafa_jid " .
                "where planid=?";
        $para = array((string) $planid);
        $ds = $this->conn->GetData("result", $sql, $para);
        $resp = new Response(json_encode($ds["result"]["rows"]));
        $resp->headers->set('Content-Type', 'text/json');
        return $resp;
    }

}
