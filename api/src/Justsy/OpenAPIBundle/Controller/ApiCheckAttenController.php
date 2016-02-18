<?php

namespace Justsy\OpenAPIBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Management\Staff;
//考勤
class ApiCheckAttenController extends Controller
{
  //取得指定月的考勤数据报表
  public function getMonthAllAttenReportAction()
  {    
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    
    $da = $this->get('we_data_access'); 

    try 
    {
      $openid = $request->get("openid");
      $month = $request->get("month");

      $monthday1 = $month.'01';

      $sql = "select a.staff_id, count(*) m_yd, 
          sum(case when a.atten_state in ('0', '1') then 1 else 0 end) m_sd,
          sum(case when a.atten_state = ('1') then 1 else 0 end) m_cd,
          sum(case when a.atten_state = ('2') then 1 else 0 end) m_qx,
          b.nick_name staff_name 
        from ma_checkatten a
        inner join we_staff b on b.login_account=a.staff_id
        where a.check_date>=? and a.check_date<date_add(?, interval 1 month)
          and a.staff_id in (select login_account from we_staff bb where bb.eno=(select max(eno) from we_staff ws where ws.openid=?))
          and not exists (select 1 from we_micro_account mm where mm.number=a.staff_id)
        group by a.staff_id, b.nick_name
        order by b.nick_name";
      $params = array();
      $params[] = (string)$monthday1;
      $params[] = (string)$monthday1;
      $params[] = (string)$openid;
      
      $ds = $da->GetData("ma_checkatten", $sql, $params);

      $re["rows"] = $ds["ma_checkatten"]["rows"];
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

  //取得指定日，指定人的企业全体成员考勤数据
  public function getDayAllAttenAction()
  {    
    $request = $this->getRequest();
    $check=$this->accessCheck();
    if($check['returncode']!=ReturnCode::$SUCCESS)
    {
        return $this->responseJson($request,$check);
    }
    $staffdata = $check['data'];
    $params=array(
      'user'=>$staffdata,
      'ymd'=>$request->get('ymd')
    );
    $obj = new \Justsy\BaseBundle\Management\HrAttendance($this->container);
    $result = $obj->getAllAttenByDate($params);
    return $this->responseJson($request,$result);
  }
  //获取指定人员，指定日期的考勤数据
  public function getDayAttenByStaffAction()
  {
    $request = $this->getRequest();
    $check=$this->accessCheck();
    if($check['returncode']!=ReturnCode::$SUCCESS)
    {
        return $this->responseJson($request,$check);
    }
    $staffdata = $check['data'];
    $params=array(
      'user'=>$staffdata,
      'staff'=>$request->get('staff'),
      'ymd'=>$request->get('ymd')
    );
    $obj = new \Justsy\BaseBundle\Management\HrAttendance($this->container);
    $result = $obj->getStaffAttenByDate($params);
    return $this->responseJson($request,$result);
  }
  //取得指定周，指定人的考勤数据
  public function getWeekAttenByStaffAction()
  {
    $request = $this->getRequest();
    $check=$this->accessCheck();
    if($check['returncode']!=ReturnCode::$SUCCESS)
    {
        return $this->responseJson($request,$check);
    }
    $staffdata = $check['data'];
    $params=array(
      'user'=>$staffdata,
      'staff'=>$request->get('staff'),
      'ymd'=>$request->get('ymd')
    );
    $obj = new \Justsy\BaseBundle\Management\HrAttendance($this->container);
    $result = $obj->getStaffAttenByWeek($params);
    return $this->responseJson($request,$result);    
  }

  //取得指定月，指定人的考勤数据
  public function getMonthAttenByStaffAction()
  {    
    $request = $this->getRequest();
    $check=$this->accessCheck();
    if($check['returncode']!=ReturnCode::$SUCCESS)
    {
        return $this->responseJson($request,$check);
    }
    $staffdata = $check['data'];
    $params=array(
      'user'=>$staffdata,
      'staff'=>$request->get('staff'),
      'ymd'=>$request->get('ymd')
    );
    $obj = new \Justsy\BaseBundle\Management\HrAttendance($this->container);
    $result = $obj->getStaffAttenByMonth($params);
    return $this->responseJson($request,$result);    
  }


  //提交考勤数据
  public function checkinAttenAction()
  {
    $request = $this->getRequest();
    $check=$this->accessCheck();
    if($check['returncode']!=ReturnCode::$SUCCESS)
    {
        return $this->responseJson($request,$check);
    }
    $staffdata = $check['data'];
    $params=array(
      'user'=>$staffdata,
      'location'=>$request->get('location'),
      'memo'=>$request->get('memo'),
      'attachment'=>$request->get('attachment')
    );
    $obj = new \Justsy\BaseBundle\Management\HrAttendance($this->container);
    $result = $obj->checkinAtten($params);
    return $this->responseJson($request,$result);
  }

  //取得本企业人员及最近审批人员列表
  public function getAuditStaffListAction()
  {    
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    
    $da = $this->get('we_data_access'); 

    try 
    {
      $openid = $request->get("openid");

      $sql = "select a.login_account, a.nick_name, max(b.approval_date) approval_date
      from we_staff a
      left join ma_checkatten_askleave b on a.login_account=b.approval_staff_id 
      left join we_staff c on b.staff_id=c.login_account and c.openid=?
      where a.eno = (select max(eno) from we_staff ws where ws.openid=?)
        and a.state_id <> '3'
        and not exists (select 1 from we_micro_account mm where mm.number=a.login_account)
      group by a.login_account, a.nick_name
      order by approval_date desc, a.nick_name ";
      $params = array();
      $params[] = (string)$openid;
      $params[] = (string)$openid;
      
      $ds = $da->GetData("ma_checkatten", $sql, $params);

      $re["rows"] = $ds["ma_checkatten"]["rows"];
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

  //提交请假数据
  public function askLeaveSubmitAction()
  {    
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    
    $da = $this->get('we_data_access'); 

    try 
    {
      $openid = $request->get("openid");
      $bizdata = $request->get("bizdata");

      $objBizdata = json_decode($bizdata, true); 

      $sql = "select count(*) c
from ma_checkatten_askleave a
inner join we_staff b on b.login_account=a.staff_id and b.openid=?
where a.askleave_start=? and a.askleave_end=?";
      $params = array();
      $params[] = (string)$openid;
      $params[] = (string)$objBizdata["askleave_start"];
      $params[] = (string)$objBizdata["askleave_end"];
      
      $ds = $da->GetData("ma_checkatten_askleave", $sql, $params);
      if ($ds["ma_checkatten_askleave"]["rows"][0]["c"] == 0)
      {
        $askleave_id = \FaFaTime\WeBaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "ma_checkatten_askleave", "askleave_id");

        $sql = "insert into ma_checkatten_askleave(askleave_id, staff_id, ask_date, askleave_start, askleave_end, askleave_reason, approval_staff_id, approval_date, approval_note, state_id)
select ?, b.login_account, now(), ?, ?, ?, ?, null, null, '0'
from we_staff b
where b.openid=?";
        $params = array();
        $params[] = (string)$askleave_id;
        $params[] = (string)$objBizdata["askleave_start"];
        $params[] = (string)$objBizdata["askleave_end"];
        $params[] = (string)$objBizdata["askleave_reason"];
        $params[] = (string)$objBizdata["approval_staff_id"];
        $params[] = (string)$openid;

        $da->ExecSQL($sql, $params);

        //写考勤表
        $sql = "insert into ma_checkatten(check_date, staff_id, atten_date, latitude, longitude, altitude, accuracy, altitudeAccuracy, heading, speed, atten_state)
  select curdate(), b.login_account, now(), 0, 0, 0, 0, 0, 0, 0, '3'
  from we_staff b
  where b.openid=?
    and date(?)>=curdate() and date(?)<date_add(curdate(), interval 1 day)
    and not exists (select 1 from ma_checkatten mca where mca.staff_id=b.login_account and mca.check_date=curdate())";
        $params = array();
        $params[] = (string)$openid;
        $params[] = (string)$objBizdata["askleave_start"];
        $params[] = (string)$objBizdata["askleave_start"];

        $da->ExecSQL($sql, $params);

        //发审批消息
        $sql = "select nick_name from we_staff where openid=?";
        $params = array();
        $params[] = (string)$openid;
        $ds = $da->GetData("nick_name", $sql, $params);
        $askleave_nick_name = $ds["nick_name"]["rows"][0]["nick_name"];

        $sql = "select fafa_jid from we_staff where login_account=?";
        $params = array();
        $params[] = (string)$objBizdata["approval_staff_id"];
        $ds = $da->GetData("fafa_jid", $sql, $params);
        $askleave_fafa_jid = $ds["fafa_jid"]["rows"][0]["fafa_jid"];

        $textmsgitembtn1 = array();
        $textmsgitembtn1["title"] = "同意";
        $textmsgitembtn1["actionurl"] = "https://www.wefafa.com/ma/mca/public/askleaveapproval?apptype=1&askleave_id=$askleave_id";
        $textmsgitembtn2 = array();
        $textmsgitembtn2["title"] = "拒绝";
        $textmsgitembtn2["actionurl"] = "https://www.wefafa.com/ma/mca/public/askleaveapproval?apptype=2&askleave_id=$askleave_id";

        $textmsgitem = array();
        $textmsgitem["title"] = "请假审批";
        $textmsgitem["content"] = "请假人员：".$askleave_nick_name."\n开始时间：".$objBizdata["askleave_start"]."\n结束时间：".$objBizdata["askleave_end"]."\n请假事由：".$objBizdata["askleave_reason"];
        $textmsgitem["buttons"] = array($textmsgitembtn1, $textmsgitembtn2); 

        $msg = array();
        $msg["textmsg"] = array();
        $msg["textmsg"]["item"] = array($textmsgitem); 

        $apiC = new \FaFaTime\WeOpenAPIBundle\Controller\ApiController();
        $apiC->setContainer($this->container);
        $apiC->sendMsg2("10602-100082@fafacn.com", $askleave_fafa_jid, json_encode($msg), "TEXT", true, "0");
      }  
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

  //审批请假
  public function askLeaveApprovalAction()
  {    
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    
    $da = $this->get('we_data_access'); 

    try 
    {
      $openid = $request->get("openid");
      $apptype = $request->get("apptype");
      $askleave_id = $request->get("askleave_id");
 
      $sql = "update ma_checkatten_askleave set state_id=?, approval_date=now() where askleave_id=? and state_id='0'";
      $params = array();
      $params[] = (string)$apptype;
      $params[] = (string)$askleave_id;
       
      $rowcount = $da->ExecSQL($sql, $params);    

      if ($rowcount > 0)
      {
        //发消息通知对方
        $sql = "select b.fafa_jid from ma_checkatten_askleave a, we_staff b where a.askleave_id=? and a.staff_id=b.login_account";
        $params = array();
        $params[] = (string)$askleave_id;
        $ds = $da->GetData("fafa_jid", $sql, $params);
        $askleave_fafa_jid = $ds["fafa_jid"]["rows"][0]["fafa_jid"];
 
        $textmsgitem = array();
        $textmsgitem["title"] = "请假审批结果";
        $textmsgitem["content"] = "您的请假审批已".($apptype=="1"?"通过":"被拒绝");

        $msg = array();
        $msg["textmsg"] = array();
        $msg["textmsg"]["item"] = array($textmsgitem); 

        $apiC = new \FaFaTime\WeOpenAPIBundle\Controller\ApiController();
        $apiC->setContainer($this->container);
        $apiC->sendMsg2("10602-100082@fafacn.com", $askleave_fafa_jid, json_encode($msg), "TEXT", true, "0");
      }
        
      $re = "已完成审批";
    } 
    catch (\Exception $e) 
    {
      $re = "系统故障，请稍候再试";
      $this->get('logger')->err($e);
    }
    
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."("."'".($re)."'".");" : ($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }

  public function accessCheck()
  {
      $da = $this->get("we_data_access");
      $da_im = $this->get('we_data_access_im');
      $request = $this->getRequest();
      //访问权限校验
      $api = new ApiController();
      $api->setContainer($this->container);

      $isWeFaFaDomain = $api->checkWWWDomain(); 
      if(!$isWeFaFaDomain)
      {
           $token = $api->checkAccessToken($request,$da); 
           if(!$token)
           {
                $re = array("returncode"=>"9999");
            $re["code"]="err0105";
                $re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
          return $re;
           }
      }   
      $openid = $request->get("openid"); 
      $staffinfo = new Staff($da,$da_im,$openid,$this->get("logger"),$this->container);
      $staffdata = $staffinfo->getInfo();
      if(empty($staffdata))
      {
        return Utils::WrapResultError("无效操作帐号");
      }  
      return Utils::WrapResultOk($staffinfo->getSessionUser($staffdata));
  }  

  private function responseJson($request,$re)
  {
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json'); 
    return $response;
  }
}
