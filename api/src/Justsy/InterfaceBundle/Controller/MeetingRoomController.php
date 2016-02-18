<?php

namespace Justsy\InterfaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Justsy\BaseBundle\Meeting\MeetingRoomMgr;

///会议接口
class MeetingRoomController extends Controller {

    //获得单条会议室记录
    public function getMeetingRoomAction(Request $request) {
        $roomid = $request->get("roomid");
        $da = $this->get('we_data_access');
        $da_im = $this->get('we_data_access_im');
        $user = $this->get('security.context')->getToken()->getUser(); 
        
        $meetingroomMgr = new MeetingRoomMgr($da, $da_im, $user,$this->get("logger"), $this->container);
        return $meetingroomMgr->get($roomid);
    }

    //查询企业所有会议室记录
    public function getAllMeetingRoomAction(Request $request) {
        $da = $this->get('we_data_access');
        $da_im = $this->get('we_data_access_im');
        $user = $this->get('security.context')->getToken()->getUser();
        $meetingroomMgr = new MeetingRoomMgr($da, $da_im, $user,$this->get("logger"), $this->container);
        return $meetingroomMgr->GetAll();
    }

    //添加会议室字段 参数Json格式
    public function InsertAction(Request $request) {
        //转换成json格式
        $parameter = $request->get("parameter");
        $da = $this->get('we_data_access');
        $da_im = $this->get('we_data_access_im');
        $user = $this->get('security.context')->getToken()->getUser();
        $meetingroomMgr = new MeetingRoomMgr($da, $da_im, $user,$this->get("logger"), $this->container);
        return $meetingroomMgr->Add($parameter);
    }

    //添加会议室字段 参数Json格式
    public function GetRecentlyAction() {
        $da = $this->get('we_data_access');
        $da_im = $this->get('we_data_access_im');
        $user = $this->get('security.context')->getToken()->getUser();
        $meetingroomMgr = new MeetingRoomMgr($da, $da_im, $user,$this->get("logger"), $this->container);
        return $meetingroomMgr->GetRecently();
    }

    //修改会议室字段 参数Json格式
    public function UpdateAction(Request $request) {
        $roomid = $request->get("roomid");
        $parameter = $request->get("parameter");
        $da = $this->get('we_data_access');
        $da_im = $this->get('we_data_access_im');
        $user = $this->get('security.context')->getToken()->getUser();
        $meetingroomMgr = new MeetingRoomMgr($da, $da_im, $user,$this->get("logger"), $this->container);
        return $meetingroomMgr->Update($roomid, $parameter);
    }

    //删除会议室记录
    public function DeleteAction(Request $request) {
        $roomid = $request->get("roomid");
        $da = $this->get('we_data_access');
        $da_im = $this->get('we_data_access_im');
        $user = $this->get('security.context')->getToken()->getUser();
        $meetingroomMgr = new MeetingRoomMgr($da, $da_im, $user, $this->get("logger"),$this->container);
        return $meetingroomMgr->Del($roomid);
    }

    //保存文件上传的接口
    public function FileUploadAction(Request $request) {
        $roomid = $request->get("roomid");
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $fileid = "";
        if ($_FILES["room_logo_file"]["name"] != "") {
            $tmpName = $_FILES['room_logo_file']['tmp_name'];
            $fileid = $this->saveFile($tmpName, $dm);
            $fileurl = $this->container->getParameter('FILE_WEBSERVER_URL');
            $da = $this->get('we_data_access');
            $da_im = $this->get('we_data_access_im');
            $user = $this->get('security.context')->getToken()->getUser();
            $meetingroomMgr = new MeetingRoomMgr($da, $da_im, $user,$this->get("logger"), $this->container);
            return $meetingroomMgr->ChangeLogo($roomid, $fileid, $fileurl);
        } else {
            $resp = new Response(-1);
            $resp->headers->set('Content-Type', 'text');
            return $resp;
        }
    }

    public function saveFile($filePath, $dm) {
        $doc = new \Justsy\MongoDocBundle\Document\WeDocument;
        $doc->setName(basename($filePath));
        $doc->setFile($filePath);
        $dm->persist($doc);
        $dm->flush();
        unlink($filePath);
        return $doc->getId();
    }

    //修改会议室状态
    public function ChangeStatusAction(Request $request) {
        $roomid = $request->get("roomid");
        $status = $request->get("status");
        $da = $this->get('we_data_access');
        $da_im = $this->get('we_data_access_im');
        $user = $this->get('security.context')->getToken()->getUser();
        $meetingroomMgr = new MeetingRoomMgr($da, $da_im, $user,$this->get("logger"), $this->container);
        return $meetingroomMgr->ChangeStatus($roomid, $status);
    }

}