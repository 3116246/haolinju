<?php

namespace Justsy\BaseBundle\Meeting;

use Justsy\BaseBundle\Management\StaffCompetenceMgr;

//会议管理员
//会议专员角色标识：meetingmanager
//会议专员管理直接写到im库中的im_employeerole表，其中属性employeeid为人员jid，roleid为拥有的角色标识
class MeetingManager {

    private $conn = null;
    private $conn_im = null;
    private $roleid = "MEETINGMANAGER";

    public function __construct($_db, $_db_im) {
        $this->conn = $_db;
        $this->conn_im = $_db_im;
    }

    //获取指定企业的会议管理专员
    public function Get($eno) {

        $get = new StaffCompetenceMgr($this->conn, $this->conn_im);

        return $get->Get($eno, $this->roleid);
    }

    //设置指定企业的会议管理专员
    //$managrlist：管理专员列表
    public function Set($eno, $managrlist) {

        $set = new StaffCompetenceMgr($this->conn, $this->conn_im);

        return $set->Set($eno, $this->roleid, $managrlist);
    }

}
