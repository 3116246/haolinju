<?php

namespace Justsy\InterfaceBundle\Controller;

use Justsy\BaseBundle\Meeting\MeetingMemberMgr;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class MeetingMemberController extends Controller {

    //根据会议计划表主键获取成员信息
    public function GetStaffAction(Request $request) {
        $planid = $request->get("planid");
        $da = $this->get('we_data_access');
        $da_im = $this->get('we_data_access_im');
        $user = $this->get('security.context')->getToken()->getUser();
        $meetingroomMgr = new MeetingMemberMgr($da, $da_im, $user, $this->container);
        return $meetingroomMgr->Sel($planid);
    }
    //删除指定会议计划的指定人员
    public function DelByStaffIdAction(Request $request) {
        $planid = $request->get("planid");
        $staffid = $request->get("staffid");
        $da = $this->get('we_data_access');
        $da_im = $this->get('we_data_access_im');
        $user = $this->get('security.context')->getToken()->getUser();
        $meetingroomMgr = new MeetingMemberMgr($da, $da_im, $user, $this->container);
        return $meetingroomMgr->DelByStaffId($planid, $staffid);
    }

    public function AddAction(Request $request) {
        $planid = $request->get("planid");
        $staffid = $request->get("staffid");
        $stafftype = $request->get("stafftype");
        $type = $request->get("type");
        $da = $this->get('we_data_access');
        $da_im = $this->get('we_data_access_im');
        $user = $this->get('security.context')->getToken()->getUser();
        $meetingroomMgr = new MeetingMemberMgr($da, $da_im, $user, $this->container);
        if ($type=="add")
           return $meetingroomMgr->Add($planid, $staffid, $stafftype);
        else  //append方式
           return $meetingroomMgr->AddOneMember($planid, $staffid, $stafftype);
    }

    //修改多条会议计划成员信息表数据  $staffid多个成员用逗号分隔开
    public function UpdateAction(Request $request) {
        $planid = $request->get("planid");
        $parameter = $request->get("parameter");
        $da = $this->get('we_data_access');
        $da_im = $this->get('we_data_access_im');
        $user = $this->get('security.context')->getToken()->getUser();
        $meetingroomMgr = new MeetingMemberMgr($da, $da_im, $user, $this->container);
        return $meetingroomMgr->Upd($planid, $parameter);
    }

    //根据会议计划表主键删除多条会议计划成员信息表数据 
    public function DeleteAction(Request $request) {
        $planid = $request->get("planid");
        $da = $this->get('we_data_access');
        $da_im = $this->get('we_data_access_im');
        $user = $this->get('security.context')->getToken()->getUser();
        $meetingroomMgr = new MeetingMemberMgr($da, $da_im, $user, $this->container);
        return $meetingroomMgr->Del($planid);
    }

    //删除会议计划临时成员信息
    public function DelByStaffTypeAction(Request $request) {
        $planid = $request->get("planid");
        $da = $this->get('we_data_access');
        $da_im = $this->get('we_data_access_im');
        $user = $this->get('security.context')->getToken()->getUser();
        $meetingroomMgr = new MeetingMemberMgr($da, $da_im, $user, $this->container);
        return $meetingroomMgr->DelByStaffType($planid);
    }

    //根据会议计划ID获得参会成员及主持人 
    public function GetMemberAndMasterAction(Request $request) {
        $planid = $request->get("planid");
        $da = $this->get('we_data_access');
        $da_im = $this->get('we_data_access_im');
        $user = $this->get('security.context')->getToken()->getUser();
        $meetingroomMgr = new MeetingMemberMgr($da, $da_im, $user, $this->container);
        return $meetingroomMgr->GetMemberAndMaster($planid);
    }

}

