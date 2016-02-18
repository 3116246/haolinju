<?php

namespace Justsy\BaseBundle\Management;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\Cache_Enterprise;
use Justsy\BaseBundle\DataAccess\SysSeq;

//通知公告管理
class App_NoticeMgr implements IBusObject
{
	private $conn=null;
	private $conn_im=null; 
	private $container = null;
	private $logger = null;
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
        
        try
        {
                //写业务表
                $apply_id = SysSeq::GetSeqNextValue($this->conn,"we_app_notice","id");
                $sql = "insert into we_app_notice(id,title,content,author,publishdate,publishstaff,publisharea,isprivate,status,eno,appid)values(?,?,?,?,now(),?,?,?,'1',?,?)";
                $this->conn->ExecSQL($sql,array(
                        (string)$apply_id,
                        (string)$parameters["title"],
                        (string)$parameters["content"],
                        (string)$currUser->getUserName(),
                        (string)$currUser->getUserName(),
                        (string)$parameters["publisharea"],
                        (string)$parameters["isprivate"],
                        (string)$currUser->eno,
                        (string)$parameters["appid"]
                ));
                //消息内容
                $message_body = array(
                    "appid"=>$parameters["appid"],
                    "title"=>$parameters["title"],
                    "id"=>$apply_id
                );
                //获取发布范围内的人员jid
                $toDept = explode(",", $parameters["publisharea"]);
                $tojids = array();
                $deptMgr = new Dept($this->conn,$this->conn_im,$this->container);
                for ($i=0; $i < count($toDept); $i++) 
                {
                    //获取部门下的所有人员jid 
                    $staffjid = $deptMgr->getAllStaffJid($toDept[$i]);
                    for ($i=0; $i < count($staffjid); $i++) 
                    {
                        $tojids[] = $staffjid[$i]["jid"];
                        if(count($tojids)>=500)
                        {
                            //向审批人发送消息,一次性最多推送500个帐号
                            Utils::sendImMessage("",$tojids,"bus_app_msgpush",json_encode($message_body),$this->container,"","",false,'','0');
                            $tojids=array();
                        }
                    }
                }
                if(count($tojids)>0)
                {
                    //向审批人发送消息
                    Utils::sendImMessage("",$tojids,"bus_app_msgpush",json_encode($message_body),$this->container,"","",false,'','0');
                }
                $result=Utils::WrapResultOK("");
        }
        catch(\Exception $e)
        {
            $this->logger->err($e);
            $result = Utils::WrapResultError($e->getMessage());            
        }
        return $result;
    }
    //列表获取
    public function getlist($parameters)
    {
        $currUser = $parameters["user"];
        if(empty($currUser))
        {
            return Utils::WrapResultError("请登录后重试",ReturnCode::$NOTLOGIN);
        }
        $pageno = isset($parameters["pageno"])?(int)$parameters["pageno"]:0;
        $pagesize = isset($parameters["pagesize"])?(int)$parameters["pagesize"]:20;
        $startPos = $pageno*$pagesize;
        $sql = "select id,title,author,publishdate from we_app_notice where eno=? and appid=? order by publishdate desc limit {$startPos},{$pagesize}";
        $ds = $this->conn->GetData("t",$sql,array(
            (string)$currUser->eno,
            (string)$parameters["appid"]
        ));
        return $ds["t"]["rows"];
    }

    //获取详细
    public function getdetail($parameters)
    {
        $currUser = $parameters["user"];
        if(empty($currUser))
        {
            return Utils::WrapResultError("请登录后重试",ReturnCode::$NOTLOGIN);
        }        
        $sql = "select * from we_app_notice where id=?";
        $ds = $this->conn->GetData("t",$sql,array( 
            (string)$parameters["id"]
        ));
        $sql = "select 1 from we_app_bus_receipt where appid=? and bus_id=?";
        $t = $this->conn->GetData("r",$sql,array((string)$parameters["appid"],(string)$parameters["id"]));
        if(count($t["r"]["rows"])==0)
        {
            $this->conn->ExecSQL("insert into we_app_bus_receipt(appid,bus_id,eno,staffid,clickdate)values(?,?,?,?,now())",array(
                (string)$parameters["appid"],
                (string)$parameters["id"],
                (string)$currUser->eno,
                (string)$currUser->getUserName()
            ));
        }
        else
        {
            $this->conn->ExecSQL("update we_app_bus_receipt set clickdate=now() where appid=? and bus_id=? and staffid=?",array(
                (string)$parameters["appid"],
                (string)$parameters["id"],
                (string)$currUser->getUserName()
            ));
        }
        return $ds["t"]["rows"];
    }

    //回执
    public function receipt($parameters)
    {
        $currUser = $parameters["user"];
        if(empty($currUser))
        {
            return Utils::WrapResultError("请登录后重试",ReturnCode::$NOTLOGIN);
        }
        $this->conn->ExecSQL("update we_app_bus_receipt set receiptdate=now() where appid=? and bus_id=? and staffid=?",array(
                (string)$parameters["appid"],
                (string)$parameters["id"],
                (string)$currUser->getUserName()
        ));
        return Utils::WrapResultOK("");
    }

}
