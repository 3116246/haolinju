<?php

namespace Justsy\BaseBundle\Management;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\Cache_Enterprise;
use Justsy\BaseBundle\DataAccess\SysSeq;

//请假申请管理
class ApplyLeaveMgr implements IBusObject
{
	private $conn=null;
	private $conn_im=null; 
	private $container = null;
	private $logger = null;
    private $wf_type = "APPLY_LEAVE"; //流程类型
    public function __construct($container)
    {
        $this->container = $container;
        $this->conn    = $container->get('we_data_access');
        $this->conn_im = $container->get('we_data_access_im');
        $this->logger  = $container->get('logger');
    }
    public function getInstance($container)
    {
        return new self($container);
    }

    public function commit($parameters)
    {
        if(empty($parameters))
        {
            $result = Utils::WrapResultError("无效的数据");
            return $result;
        }
        $currUser = $parameters["user"];
        if(empty($currUser))
        {
            return Utils::WrapResultError("请登录后重试",ReturnCode::$NOTLOGIN);
        }
        $audit_staffs = $parameters["audit_staffs"]; //审批人。多个审批人使用;分隔
        if(empty($audit_staffs))
        {
            return Utils::WrapResultError("审批人不能为空");
        }        
        $wfl = new \Justsy\BaseBundle\Business\WeWorkflow($this->container);        
        try
        {
            $content = $currUser->nick_name."申请请假".$parameters["days"]."天";
            //创建新流程
            $result = $wfl->createWorkflow(array(
                "appid"=>$parameters["appid"],
                "user"=>$currUser,
                "to"=>$audit_staffs,
                "wf_name"=>"请假申请",
                "wf_content"=>$content,
                "wf_type"=>$wf_type,
                "attachment"=>$parameters["attachment"],
            ));
            if(!empty($result))
            {
                try
                {
                    //写业务表
                    $apply_id = SysSeq::GetSeqNextValue($this->conn,"we_app_apply_leave","id");
                    $sql = "insert into we_app_apply_leave(id,wf_id,leavetype,start_date,end_date,days,reason,create_datetime,staff,eno,appid)values(?,?,?,?,?,?,?,now(),?,?,?)";
                    $this->conn->ExecSQL($sql,array(
                        (string)$apply_id,
                        (string)$result["wf_id"],
                        (string)$parameters["leavetype"],
                        (string)$parameters["start_date"],
                        (string)$parameters["end_date"],
                        (float)$parameters["days"],
                        (string)$parameters["reason"],
                        (string)$currUser->getUserName(),
                        (string)$currUser->eno,
                        (string)$parameters["appid"]
                    ));
                }
                catch(\Exception $e)
                {
                    $wfl->removeWorkflow(array("wf_id"=>$result["wf_id"]));
                    throw new \Exception($e);
                }
                //获取审批人的jid
                $to = explode(";", $audit_staffs);
                $tojids = array();
                for ($i=0; $i < count($to); $i++) { 
                    $staff = new Staff($this->conn,$this->conn_im,$to[$i],$this->logger,$this->container);
                    $staffdata = $staff->getInfo();
                    if(empty($staffdata)) continue;
                    $tojids[] = $staffdata["fafa_jid"];
                }
                //向审批人发送消息
                Utils::sendImMessage("",$tojids,"bus_apply",json_encode($re),$this->container,"","",false,'','0');
            }
            
            return $result;
        }
        catch(\Exception $e)
        {
            $this->logger->err($e);
            $result = Utils::WrapResultError($e->getMessage());            
        }
        return $result;
    }
    public function agree($paraObj)
    {
        $currUser = $paraObj["user"];
        if(empty($currUser))
        {
            return Utils::WrapResultError("请登录后重试",ReturnCode::$NOTLOGIN);
        }

        $wfl = new \Justsy\BaseBundle\Business\WeWorkflow($this->container);
        //根据申请帐号处理
        $account = isset($paraObj["staff"]) ? $paraObj["staff"] : "";
        if(!empty($account))
        {
            $paraObj["submit_staff"] = $account;
        }
        $nodeinfo = $wfl->getNode($paraObj);
        
        if(empty($nodeinfo))
        {
            return Utils::WrapResultError("申请已被取消或删除");
        }
        //判断申请状态
         if($nodeinfo["status"]!="9")
        {
            return Utils::WrapResultError("该申请已处理");
        }
        $paraObj["node_id"] = $nodeinfo["node_id"];
        $applystaff = $nodeinfo["submit_staff"];
        //向申请人发送处理消息 
        $message = "你的请假申请已由【".$currUser->nick_name."】审批通过";
        Utils::sendImMessage("",$applystaff ,"bus_apply_agree",$message,$this->container,"","",true,'','0');

        //申请状态处理        
        $re = $wfl->agree($paraObj);
        if(!empty($re))
        {
            //通知所有的节点处理人
            $dealstaffJids = isset($re["dealstaffs"]) ? $re["dealstaffs"] : "";
            if(!empty($dealstaffJids))
            {
                $to = explode(",", $dealstaffJids);
                Utils::sendImMessage("",$to ,"bus_apply_agree",json_encode($re),$this->container,"","",false,'','0');
            }
        }
        return Utils::WrapResultOK($re);
    }

    public function reject($paraObj)
    {
        $currUser = $paraObj["user"];
        if(empty($currUser))
        {
            return Utils::WrapResultError("请登录后重试",ReturnCode::$NOTLOGIN);
        }       
        $wfl = new \Justsy\BaseBundle\Business\WeWorkflow($this->container);
        //根据申请帐号处理
        $account = isset($paraObj["staff"]) ? $paraObj["staff"] : "";
        if(!empty($account))
        {
            $paraObj["submit_staff"] = $account;
        }
        $nodeinfo = $wfl->getNode($paraObj);
        if(empty($nodeinfo))
        {
            return Utils::WrapResultError("申请已被取消或删除");
        }
        //判断申请状态
        if($nodeinfo["status"]!="9")
        {
            return Utils::WrapResultError("该申请已处理");
        }        
        //向申请人发送处理消息 
        $message = "你的请假申请已由【".$currUser->nick_name."】驳回";
        Utils::sendImMessage("",$applystaff ,"bus_apply_reject",$message,$this->container,"","",true,'','0');
      
        $paraObj["node_id"] = $nodeinfo["node_id"];
        //申请状态处理        
        $re = $wfl->reject($paraObj);

        //消息通知 
        if(!empty($re))
        {
            //通知所有的节点处理人
            $dealstaffJids = isset($re["dealstaffs"]) ? $re["dealstaffs"] : "";
            if(!empty($dealstaffJids))
            {
                $to = explode(",", $dealstaffJids);
                Utils::sendImMessage("",$to ,"bus_apply_reject",json_encode($re),$this->container,"","",false,'','0');
            }
        }
        return Utils::WrapResultOK($re);
    }

    public function removeapply($paraObj)
    {
        $wfl = new \Justsy\BaseBundle\Business\WeWorkflow($this->container);
        $re = $wfl->cancel($paraObj);
        //消息通知 
        if($re)
        {
            $message = "申请取消成功";
            Utils::sendImMessage("",$paraObj["user"]->fafa_jid ,"bus_apply_remove",$message,$this->container,"","",false,'','0');
        
            //通知所有的节点处理人
            $dealstaffJids = isset($re["dealstaffs"]) ? $re["dealstaffs"] : "";
            if(!empty($dealstaffJids))
            {
                $to = explode(",", $dealstaffJids);
                Utils::sendImMessage("",$to ,"bus_apply_remove",json_encode($re),$this->container,"","",false,'','0');
            }
        }
        return Utils::WrapResultOK($re);
    }

}
