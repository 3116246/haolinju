<?php

namespace Justsy\InterfaceBundle\Controller;

use Justsy\BaseBundle\Meeting\MeetingPlanMgr;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

//会议日程/计划管理
class MeetingPlanController extends Controller {

    //根据日程/计划主键获取会议计划表的数据
    public function GetByIdAction(Request $request) {
        $id = $request->get("id");
        $da = $this->get('we_data_access');
        $da_im = $this->get('we_data_access_im');
        $user = $this->get('security.context')->getToken()->getUser();
        $meetingPlanMgr = new MeetingPlanMgr($da, $da_im, $user, $this->container);
        return $meetingPlanMgr->GetById($id);
    }
    //获取会议详细计划   根据群组ID获取最近一条数据
    public function GetPlanByGroupidAction(Request $request) {
        $groupid = $request->get("groupid");
        $da = $this->get('we_data_access');
        $da_im = $this->get('we_data_access_im');
        $user = $this->get('security.context')->getToken()->getUser();
        $meetingPlanMgr = new MeetingPlanMgr($da, $da_im, $user, $this->container);
        return $meetingPlanMgr->GetPlanByGroupid($groupid);
    }
    //根据会议组编号获取会议计划表的数据
    public function GetByGroupIdAction(Request $request) {
        $groupid = $request->get("groupid");
        $da = $this->get('we_data_access');
        $da_im = $this->get('we_data_access_im');
        $user = $this->get('security.context')->getToken()->getUser();
        $meetingPlanMgr = new MeetingPlanMgr($da, $da_im, $user, $this->container);
        return $meetingPlanMgr->GetByGroupid($groupid);
    }

    //读取例会历史数据
    public function GetHistoryMeetingAction(Request $r) {
        $groupid = $r->get("groupid");
        $da = $this->get('we_data_access');
        $da_im = $this->get('we_data_access_im');
        $user = $this->get('security.context')->getToken()->getUser();
        $meetingPlanMgr = new MeetingPlanMgr($da, $da_im, $user, $this->container);
        return $meetingPlanMgr->GetHistoryMeeting($groupid);
    }

    ///开始会议
    public function StartPlanAction(Request $request) {
        $planid = $request->get("id");
        $da = $this->get('we_data_access');
        $da_im = $this->get('we_data_access_im');
        $user = $this->get('security.context')->getToken()->getUser();
        $meetingPlanMgr = new MeetingPlanMgr($da, $da_im, $user, $this->container);
        return $meetingPlanMgr->StartPlan($planid);
    }

    public function StartEndPlanAction(Request $request) {
        $planid = $request->get("id");
        $realmeetingstartdate = $request->get("realmeetingstartdate");
        $realmeetingenddate = $request->get("realmeetingenddate");
        $da = $this->get('we_data_access');
        $da_im = $this->get('we_data_access_im');
        $user = $this->get('security.context')->getToken()->getUser();
        $meetingPlanMgr = new MeetingPlanMgr($da, $da_im, $user, $this->container);
        return $meetingPlanMgr->StartEndPlan($planid, $realmeetingstartdate, $realmeetingenddate);
    }

    //介绍会议接口
    public function EndPlanAction(Request $request) {
        $planid = $request->get("id");
        $da = $this->get('we_data_access');
        $da_im = $this->get('we_data_access_im');
        $user = $this->get('security.context')->getToken()->getUser();
        $meetingPlanMgr = new MeetingPlanMgr($da, $da_im, $user, $this->container);
        return $meetingPlanMgr->EndPlan($planid);
    }

    //根据会议组编号获取对应时间段的会议计划表的数据
    public function GetByWhereAction(Request $request) {
        $paras = $request->get("parameter");
        $da = $this->get('we_data_access');
        $da_im = $this->get('we_data_access_im');
        $user = $this->get('security.context')->getToken()->getUser();
        $meetingPlanMgr = new MeetingPlanMgr($da, $da_im, $user, $this->container);
        return $meetingPlanMgr->GetByWhere($paras);
    }

    //添加一条会议计划表的数据 返回主键
    public function AddSingletonAction(Request $request) {
        $paras = $request->get("parameter");
        $staffid = $request->get("staffid");
        $stafftype = $request->get("stafftype");
        $da = $this->get('we_data_access');
        $da_im = $this->get('we_data_access_im');
        $user = $this->get('security.context')->getToken()->getUser();
        $meetingPlanMgr = new MeetingPlanMgr($da, $da_im, $user, $this->container);
        return $meetingPlanMgr->AddSingleton($paras, $staffid, $stafftype);
    }

    public function AddRegularAction(Request $request) {
        $paras = $request->get("parameter");
        $staffid = $request->get("staffid");
        $stafftype = $request->get("stafftype");
        $da = $this->get('we_data_access');
        $da_im = $this->get('we_data_access_im');
        $user = $this->get('security.context')->getToken()->getUser();
        $meetingPlanMgr = new MeetingPlanMgr($da, $da_im, $user, $this->container);
        return $meetingPlanMgr->AddRegular($paras, $staffid, $stafftype);
    }

    public function GetPlanidByGroupIdAction(Request $request) {
        $groupid = $request->get("groupid");
        $da = $this->get('we_data_access');
        $da_im = $this->get('we_data_access_im');
        $user = $this->get('security.context')->getToken()->getUser();
        $meetingPlanMgr = new MeetingPlanMgr($da, $da_im, $user, $this->container);
        return $meetingPlanMgr->GetPlanidByGroupId($groupid);
    }

    //修改一条会议计划表的数据 返回主键
    public function UpdateAction(Request $request) {
        $id = $request->get("id");
        $paras = $request->get("parameter");
        $staffid = $request->get("staffid");
        $stafftype = $request->get("stafftype");
        $c = $request->get("c");
        $da = $this->get('we_data_access');
        $da_im = $this->get('we_data_access_im');
        $user = $this->get('security.context')->getToken()->getUser();
        $meetingPlanMgr = new MeetingPlanMgr($da, $da_im, $user, $this->container);
        return $meetingPlanMgr->Update($id, $paras, $staffid, $stafftype, $c);
    }

    //删除一条会议计划表的数据
    public function DeleteAction(Request $request) {
        $id = $request->get("id");
        $da = $this->get('we_data_access');
        $da_im = $this->get('we_data_access_im');
        $user = $this->get('security.context')->getToken()->getUser();
        $meetingPlanMgr = new MeetingPlanMgr($da, $da_im, $user, $this->container);
        return $meetingPlanMgr->Del($id);
    }

    //解散会议计划
    public function DelPlanAction(Request $request) {
        $planid = $request->get("id");
        $da = $this->get('we_data_access');
        $da_im = $this->get('we_data_access_im');
        $user = $this->get('security.context')->getToken()->getUser();
        $meetingPlanMgr = new MeetingPlanMgr($da, $da_im, $user, $this->container);
        return $meetingPlanMgr->DelPlan($planid);
    }
    
    //根据id判断会议是否存在
    public function ExistsMeetingAction(Request $request){
    	 $planid = $request->get("id");
    	 $da = $this->get('we_data_access');
    	 $da_im = $this->get('we_data_access_im');
       $user = $this->get('security.context')->getToken()->getUser();
       $meetingPlanMgr = new MeetingPlanMgr($da,$da_im,$user, $this->container);
       return $meetingPlanMgr->ExistsMeeting($planid);
    }
}

