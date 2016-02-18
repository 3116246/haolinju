<?php

namespace Justsy\BaseBundle\Meeting;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Management\Staff;

class MeetingRoomMgr {

    private $conn = null;
    private $conn_im = null;
    private $user = null;
    private $container = null;

    public function __construct($_db, $_db_im, $user,$_logger, $container) {
        $this->conn = $_db;
        $this->conn_im = $_db_im;
        //$this->user = $user;
        if(is_string($user)){
	  		 //$this->account=$user;
	  		 $staff = new Staff($_db,$_db_im,$user,$_logger);
	  		 $this->user = $staff->getSessionUser();
	  		}else $this->user=$user;
        $this->container = $container;
    }

    //发送即时消息(只发送在线的人)
    private function SendImPresence($roomid, $title, $message) {
        if (empty($message)) {
            //获取消息内容
            $ds = $this->GetRoom($roomid);
            $message = json_encode($ds[0]);
        }
        if (!empty($this->user)) {
            $eno = $this->user->eno;
            //根据企业号获取接收人
            $sqltojid = " select GROUP_CONCAT(distinct fafa_jid) as login_account from we_circle t1 left join we_circle_staff t2 on t1.circle_id=t2.circle_id  inner join we_staff t3 on t1.enterprise_no=t3.eno where t1.enterprise_no=? ";
            $parastojid = array((string) $eno);
            $dstojid = $this->conn->GetData("result", $sqltojid, $parastojid);
            $tojid = $dstojid["result"]["rows"][0]["login_account"];
            $title.="_meetingroom";
            $staffinfo = "{\"nick_name\":\"" . $this->user->nick_name . "\",";
            if (!empty($message))
                $message = str_replace("{", $staffinfo, $message);
            //开始发送消息
            Utils::sendImPresence($this->user->fafa_jid, $tojid, $title, $message, $this->container,"","",false,Utils::$systemmessage_code);
        }
    }

    private function GetRoom($roomid) {
        $sql = "select * from we_meetingroom where roomid=? ";
        $paras = array((string) $roomid);
        $ds = $this->conn->GetData("result", $sql, $paras);
        return $ds["result"]["rows"];
    }

    public function Get($roomid) {
        $result = $this->GetRoom($roomid);
        $resp = new Response(json_encode($result));
        $resp->headers->set('Content-Type', 'text/json');
        return $resp;
    }

    public function GetAll() {
        $sql = "select * from we_meetingroom where eno=? order by `status` asc";
        $ds = $this->conn->GetData("result", $sql, array($this->user->eno));
        $result = $ds["result"]["rows"];
        $resp = new Response(json_encode($result));
        $resp->headers->set('Content-Type', 'text/json');
        return $resp;
    }

    //获取各个群组需要召开的会议计划
    public function GetRecently() {
        $sql = "select t1.name as roomname,t1.roomtype,t2.id as planid,t2.groupid,t2.name as planname ,t2.master,t2.cycle";
        $sql.=",t2.subject,t2.meetingstartdate,t2.meetingenddate,t2.realmeetingstartdate,t2.realmeetingenddate,t2.meetingtype ";
        $sql.=",t2.addrType,t2.defaultAddr,t2.create_staff ,t3.staffid,t3.stafftype from we_meeting_plan t2 ";
        $sql.="left join  we_meetingroom t1 on t1.roomid=t2.defaultAddr ";
        $sql.="left join we_meeting_member t3 on t2.id=t3.planid ";
        $sql.="where (t2.realmeetingenddate is null  or date(t2.realmeetingenddate)>date(DATE_ADD(now(),INTERVAL -1 DAY)) ) and  t3.staffid=? ";
        $sql.="order by t2.meetingstartdate  ";

        $paras = array((string) $this->user->fafa_jid);

        $ds = $this->conn->GetData("result", $sql, $paras);
        $result = $ds["result"]["rows"];

        $resp = new Response(json_encode($result));
        $resp->headers->set('Content-Type', 'text/json');
        return $resp;
    }

    //添加会议室字段 参数Json格式
    public function Add($content) {
        $fields = json_decode($content, true);
        $roomid = 'we' . SysSeq::GetSeqNextValue($this->conn, "we_meetingroom", "roomid");
        $fieldslist = "";
        $fieldval = "";
        $roomname = "";
        $address = "";
        $roomtype = "";
        $para = array((string) $roomid);
        foreach ($fields as $key => $val) {
            if ($val != "") {
                if (!empty($fieldslist))
                    $fieldslist.=",";
                $fieldslist .= $key;
                array_push($para, (string) $val);
                if (!empty($fieldval))
                    $fieldval.=",";
                $fieldval.= "?";
                if ($key == "name")
                    $roomname = $val;
                if ($key == "address")
                    $address = $val;
                if ($key == "roomtype")
                    $roomtype = $val;
            }
        }
        $sqls = array();
        $paras = array();
        $sqls[] = "insert into we_meetingroom(roomid," . $fieldslist . ")values(?," . $fieldval . ")";
        $paras[] = $para;
        //会议常用地址不为空并且类型为外部地址的时候
        if (!empty($address) && $roomtype == "2") {
            $sqls[] = "update we_meeting_plan set defaultAddr=?  where defaultAddr=? and create_staff like concat('%-',?,'@fafacn.com') ";
            $paras[] = array((string) $roomid, (string) $address, (string) $this->user->eno);
        }
        //执行结果(执行成功返回roomid,失败返回0);
        try {

            $this->conn->ExecSQLs($sqls, $paras);

            //发送即时消息(只发送在线的人)
            $this->SendImPresence($roomid, "add", "");
        } catch (\Exception $exc) {
            $this->container->get('logger')->err($exc);
            $roomid = 0;
        }
        $resp = new Response($roomid);
        $resp->headers->set('Content-Type', 'text');
        return $resp;
    }

    //修改会议室字段 参数Json格式
    public function Update($roomid, $content) {
        $fields = json_decode($content, true);
        $sql = "update we_meetingroom set ";
        $upFields = "";
        $paras = array();
        foreach ($fields as $key => $val) {
            if ($upFields != "")
                $upFields .= ", ";
            $upFields.=$key . "=?";
            array_push($paras, (string) $val);
        }
        $sql .= $upFields . " where roomid=?";
        array_push($paras, (string) $roomid);
        try {
            $this->conn->ExecSQL($sql, $paras);

            //发送即时消息(只发送在线的人)
            $this->SendImPresence($roomid, "upd", "");
        } catch (\Exception $exc) {
            $this->container->get('logger')->err($exc);
            $roomid = 0;
        }
        $resp = new Response($roomid);
        $resp->headers->set('Content-Type', 'text');
        return $resp;
    }

    //修改会议室状态
    public function ChangeStatus($roomid, $status) {
        $sqls = "update we_meetingroom set status=? where roomid=? ";
        $paras = array(
            (string) $status,
            (string) $roomid);
        try {
            $this->conn->ExecSQL($sqls, $paras);

            //发送即时消息(只发送在线的人)
            $this->SendImPresence($roomid, "status", "");
        } catch (\Exception $exc) {
            $this->container->get('logger')->err($exc);
            $roomid = 0;
        }
        $resp = new Response($roomid);
        $resp->headers->set('Content-Type', 'text');
        return $resp;
    }

    //删除闲置的会议室
    public function Del($roomid) {
        $sqls = "delete from we_meetingroom where roomid=? and status='0'";
        try {
            $dsmsg = $this->GetRoom($roomid);
            $message = json_encode($dsmsg[0]);

            $this->conn->ExecSQL($sqls, array((string) $roomid));

            //发送即时消息(只发送在线的人)
            $this->SendImPresence($roomid, "del", $message);
        } catch (\Exception $exc) {
            $this->container->get('logger')->err($exc);
            $roomid = 0;
        }
        $resp = new Response($roomid);
        $resp->headers->set('Content-Type', 'text');
        return $resp;
    }

    //修改会议室LOGO的方法
    public function ChangeLogo($roomid, $file_id, $fileurl) {
        $sqls = "update we_meetingroom set image_path=? where roomid=? ";
        $paras = array(
            (string) $file_id,
            (string) $roomid);
        $result = $fileurl . $file_id;
        try {
            $this->conn->ExecSQL($sqls, $paras);

            //发送即时消息(只发送在线的人)
            $this->SendImPresence($roomid, "logo", "");
        } catch (\Exception $exc) {
            $this->container->get('logger')->err($exc);
            $result = 0;
        }
        $resp = new Response($result);
        $resp->headers->set('Content-Type', 'text');
        return $resp;
    }

}

