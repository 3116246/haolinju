<?php

namespace Justsy\BaseBundle\Business;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Common\Cache_Enterprise;
use Justsy\BaseBundle\Management\IBusObject;

class WeWorkflow implements IBusObject
{
    private $containerObj="";
    private $conn=null;
    private $conn_im = null;
    private $logger=null;
        
    public function __construct($container)
    { 
      $this->containerObj = $container;
      $this->conn    = $container->get('we_data_access');
      $this->conn_im = $container->get('we_data_access_im');
      $this->logger=$container->get('logger');
    }

    public function getInstance($container)
    {
        return new self($container);
    }

    //创建并开启新的业务流程
    //参数：name:流程名称
    //      content:流程说明
    //      to     :处理人帐号（建议使用openid）
    //              支持以下常量值：admin或空->表示提交给管理员，这时会自动获取企业管理员帐号
    public function createWorkflow($parameter)
    {
        $appid = $parameter["appid"];        
        $userinfo = $parameter["user"];
        $to = isset($parameter["to"]) ? $parameter["to"] : "";
        $wf_name = $parameter["wf_name"];
        $wf_type = $parameter["wf_type"];
        $wf_content = $parameter["wf_content"];
        if(empty($wf_content))
        {
          $wf_content=$wf_name;
        }
        $wf_remark =isset($parameter["wf_remark"]) ? $parameter["wf_remark"] : "";
        $eno = $userinfo->eno;
        $account = $userinfo->getUserName();
        if(empty($to))
        {
          $enobj = new \Justsy\BaseBundle\Management\Enterprise($this->conn,$this->logger,$this->containerObj);
          $endata = $enobj->getInfo($eno);
          $to = $endata["sys_manager"]||$endata["create_staff"];
          if(empty($to))
          {
            throw new \Exception("提交失败：企业未指定管理员");
          }
        }
        if(empty($appid)||empty($wf_name))
        {
          throw new \Exception("提交失败：请检查是否指定了wf_name和appid参数");
        }
        $wf_id = SysSeq::GetSeqNextValue($this->conn,"we_app_businessworkflow","wf_id");
        $sql =array( "insert into we_app_businessworkflow select ?,?,?,?,now(),?,?,?,?");
        $para =array(
            array(
              (string)$wf_id,
              (string)$wf_name,
              (string)$appid,
              (string)$wf_content,
              (string)$account,
              (string)$wf_remark,
              (string)$eno,
              (string)$wf_type
            )
        );
        $re = null;
        //生成第一个流转节点
        $node_id = SysSeq::GetSeqNextValue($this->conn,"we_app_workflow_node","node_id");
        //节点状态：9->未审批  0->审批拒绝  1->审批同意
        $sql[]= "insert into we_app_workflow_node select ?,?,?,?,now(),9,'','','',''";
        $para[] = array(
          (string)$node_id,
          (string)$wf_name,
          (string)$wf_id,
          (string)$account
        );
        //附件处理
        if(!empty($parameter["attachment"]))
        {
          $attachment = explode(",", $parameter["attachment"]);
          for ($i=0; $i < count($attachment); $i++) { 
            if(empty($attachment[$i])) continue;
            $attachment_id = SysSeq::GetSeqNextValue($this->conn,"we_app_workflow_attachment","id");
            $sql[]= "insert into we_app_workflow_attachment(id,node_id,file_id)values(?,?,?)";
            $para[] = array(
              (string)$attachment_id, 
              (string)$node_id,
              (string)$$attachment[$i]
            ); 
          }           
        }
        //生成审批人列表
        $tos = explode(";", $to);  
        for ($i=0; $i <count($tos) ; $i++) { 

            $staffobj = new \Justsy\BaseBundle\Management\Staff($this->conn,$this->conn_im,$tos[$i],$this->logger,$this->containerObj);
            $staffata = $staffobj->getInfo();
            if(empty($staffata)) continue;
            $id = SysSeq::GetSeqNextValue($this->conn,"we_app_workflow_nodesetting","id");
            $sql[]= "insert into we_app_workflow_nodesetting select ?,?,?,?,?,?,?,?";
            $para[] = array(
              (string)$id,
              (string)$wf_id,
              (string)$node_id,
              (string)$wf_name,
              (string)$tos[$i],
              "0",
              "0",
              (string)$staffata["fafa_jid"]
            );
        }
        $this->conn->ExecSQLs($sql,$para);

        $re = $this->getNode(array("node_id"=>$node_id));
        //$message = ($user->nick_name)."修改了群组(".$groupname.")资料！";
        //Utils::sendImPresence($user->fafa_jid,$to_jid,"edit_groupinfo",$message,$this->container,"","",true,'','0');
        return $re;
    }

    //创建一个流转步骤
    public function createNode($parameter)
    {
        $appid = $parameter["appid"];
        $userinfo = $parameter["user"];
        $to = isset($parameter["to"]) ? $parameter["to"] : "";
        $wf_id = $parameter["wf_id"] ;
        $node_name = $parameter["node_name"] ;
        //$node_content = isset($parameter["node_content"])? $parameter["node_content"]:$node_name ;
        //生成第一个流转节点
        $node_id = SysSeq::GetSeqNextValue($this->conn,"we_app_workflow_node","node_id");
        $sql=array();
        $para=array();
        //节点状态：9->未审批  0->审批拒绝  1->审批同意
        $sql[]= "insert into we_app_workflow_node select ?,?,?,?,now(),9,'','','',''";
        $para[] = array(
          (string)$node_id,
          (string)$node_name,
          (string)$wf_id,
          (string)$userinfo->getUserName()
        );
        //附件处理
        if(!empty($parameter["attachment"]))
        {
          $attachment = explode(",", $parameter["attachment"]);
          for ($i=0; $i < count($attachment); $i++) { 
            if(empty($attachment[$i])) continue;
            $attachment_id = SysSeq::GetSeqNextValue($this->conn,"we_app_workflow_attachment","id");
            $sql[]= "insert into we_app_workflow_attachment(id,node_id,file_id)values(?,?,?)";
            $para[] = array(
              (string)$attachment_id, 
              (string)$node_id,
              (string)$$attachment[$i]
            ); 
          }           
        }        
        //生成审批人列表
        $tos = explode(";", $to);        
        for ($i=0; $i <count($tos) ; $i++) { 
            $staffobj = new \Justsy\BaseBundle\Management\Staff($this->conn,$this->conn_im,$tos[$i],$this->logger,$this->containerObj);
            $staffata = $enobj->getInfo();
            if(empty($staffata)) continue;
            $id = SysSeq::GetSeqNextValue($this->conn,"we_app_workflow_nodesetting","id");
            $sql[]= "insert into we_app_workflow_nodesetting select ?,?,?,?,?,?,?,?";
            $para[] = array(
              (string)$id,
              (string)$wf_id,
              (string)$node_id,
              (string)$wf_name,
              (string)$tos[$i],
              "0",
              "0",
              (string)$staffata["fafa_jid"]
            );
        }
        $this->conn->ExecSQLs($sql,$para);  
        $re = array(
          "node_id"=>$node_id,
          "node_name"=>$wf_name,
          "wf_name"=>$wf_name,
          "wf_id"=>$wf_id,
          "openid"=>$userinfo->getUserName(),
          "username"=>$userinfo->nick_name,
          "attachment"=>isset($parameter["attachment"]) ? $parameter["attachment"]:""
        );
        return $re;      
    }

    public function removeWorkflow($parameter)
    {
        $wf_id = $parameter["wf_id"];
        //获取节点
        $sql ="select node_id from we_app_workflow_node where wf_id=?";
        $ds = $ths->conn->GetData("t",$sql,array((string)$wf_id));
        for ($i=0; $i < count($ds["t"]["rows"]); $i++) { 
           $node_id = $ds["t"]["rows"][$i]["node_id"];
           $this->removeWorkflowNode(array("node_id"=>$node_id));
        }
        $sql1 = "delete from we_app_businessworkflow where wf_id=?";       
        $this->conn->ExecSQL($sql1,array((string)$wf_id));
        return true;
    }

    public function removeWorkflowNode($parameter)
    {      
        $node_id =isset($parameter["node_id"]) ? $parameter["node_id"] : "";
        if(empty($node_id))
        {
          //节点已不存在时，直接返回
          return false;
        }        
        $data = $this->getNode($parameter);
        if(empty($data))
        {
          //节点已不存在时，直接返回
          return null;
        }
        $account = $parameter["user"]->getUserName();
        if($data["submit_staff"]==$account)
        {
          //如果是申请人并且是流程的创建者删除该节点时,删除该 流程所有数据
          $wf = $this->getWorkflow($data);
          if(!empty($wf) && $wf["createstaff"]==$account)
          {
            $this->conn->ExecSQL("delete from we_app_businessworkflow where wf_id=?",array((string)$data["wf_id"]));
          }
        }
        $sql = "select * from we_app_workflow_attachment where node_id=?";
        $attachmentDS = $this->conn->GetData("t",$sql,array(
          (string)$node_id
        ));
        $sql2 = "delete from we_app_workflow_node where node_id=?";
        $sql3 = "delete from we_app_workflow_nodesetting where node_id=?";
        $sql4 = "delete from we_app_workflow_attachment where node_id=?";
        $this->conn->ExecSQLs(
          array($sql2,$sql3,$sql4),
          array(
            array((string)$node_id),
            array((string)$node_id),
            array((string)$node_id)
          )
        );
        if(count($attachmentDS["t"]["rows"])>0)
        {
          for ($i=0; $i < count($attachmentDS["t"]["rows"]); $i++) { 
            $file_id = $attachmentDS["t"]["rows"][$i]["file_id"];
            Utils::removeFile($file_id,$this->containerObj);
          }
        }
        return $data;
    }

    public function cancel($parameter)
    {
      $userinfo = $parameter["user"];
      $node_id = $parameter["node_id"] ;
      $comment =isset($parameter["comment"]) ? $parameter["comment"]:""; 
      $sql = "update we_app_workflow_node set status=-1,comment=?,audit_staff=?,audit_date=now(),audit_staff_jid=? where node_id=?";
      $this->conn->ExecSQL($sql,array(
        (string)$comment,
        (string)$userinfo->openid,
        (string)$userinfo->fafa_jid,
        (string)$node_id
      ));
      return $this->getNode($parameter);
    }

    public function agree($parameter)
    {
      $userinfo = $parameter["user"];
      $node_id = $parameter["node_id"] ;
      $comment =isset($parameter["comment"]) ? $parameter["comment"]:""; 
      $sql = "update we_app_workflow_node set status=1,comment=?,audit_staff=?,audit_date=now(),audit_staff_jid=? where node_id=?";
      $this->conn->ExecSQL($sql,array(
        (string)$comment,
        (string)$userinfo->openid,
        (string)$userinfo->fafa_jid,
        (string)$node_id
      ));
      return $this->getNode($parameter);
    }

    public function reject($parameter)
    {
      $userinfo = $parameter["user"];
      $node_id = $parameter["node_id"] ;
      $comment =isset($parameter["comment"]) ? $parameter["comment"] : ""; 
      $sql = "update we_app_workflow_node set status=0,comment=?,audit_staff=?,audit_date=now(),audit_staff_jid=? where node_id=?";
      $this->conn->ExecSQL($sql,array(
        (string)$comment,
        (string)$userinfo->openid,
        (string)$userinfo->fafa_jid,
        (string)$node_id
      ));
      
      return $this->getNode($parameter);
    }

    public function getNode($parameter)
    {
      $file_url = $this->containerObj->getParameter("FILE_WEBSERVER_URL");
      $node_id =isset( $parameter["node_id"])?$parameter["node_id"]:"";
      $appid = isset( $parameter["appid"])?$parameter["appid"]:"";
      $wftype = isset( $parameter["wf_type"])?$parameter["wf_type"]:"";
      $submit_staff = isset( $parameter["submit_staff"])?$parameter["submit_staff"]:"";
      $sql = "select b.eno,b.appid,e.ename,concat('$file_url',e.logo_path_big) logo_path,a.*,b.wf_name,b.content from we_app_workflow_node a,we_app_businessworkflow b,we_enterprise e where a.wf_id=b.wf_id and b.eno=e.eno ";
      $para=array();
      if(!empty($node_id))
      {
        $sql.= " and a.node_id=?";
        $para[] = (string)$node_id;
      }
      if(!empty($appid))
      {
        $sql.= " and exists (select 1 from we_app_businessworkflow w where a.wf_id=w.wf_id and w.appid=?)";
        $para[] = (string)$appid;
      }     
      if(!empty($submit_staff))
      {
        $sql.= " and a.submit_staff=?";
        $para[] = (string)$submit_staff;
      }
      if(!empty($wftype))
      {
        $sql.= " and exists (select 1 from we_app_businessworkflow w where a.wf_id=w.wf_id and w.wf_type=?)";
        $para[] = (string)$wftype;        
      }
      $re = $this->conn->GetData("t",$sql,$para);
      $re = count($re["t"]["rows"])>0 ? $re["t"]["rows"][0] : null;
      if(!empty($re))
      {
          $staff = new \Justsy\BaseBundle\Management\Staff($this->conn,$this->conn_im,$re["submit_staff"],$this->logger,$this->containerObj);
          $staffdata = $staff->getInfo();
          $re["nick_name"] = empty($staffdata)? "" : $staffdata["nick_name"];
          //获取节点附件
          $sql = "select * from we_app_workflow_attachment where node_id=?";
          $ds = $this->conn->GetData("att",$sql,array((string)$re["node_id"]));
          $attachment = array();
          for ($i=0; $i < count($ds["att"]["rows"]); $i++) { 
            $attachment[] = $ds["att"]["rows"][$i]["file_id"];
          }
          $re["attachment"] =implode(",", $attachment) ;
          //获取共同处理人
          $sql = "select * from we_app_workflow_nodesetting where node_id=?";
          $ds = $this->conn->GetData("att",$sql,array((string)$re["node_id"]));
          $dealstaffs = array();
          for ($i=0; $i < count($ds["att"]["rows"]); $i++) { 
            $dealstaffs[] = $ds["att"]["rows"][$i]["staff_jid"];
          }
          $re["dealstaffs"] =implode(",", $dealstaffs) ;          
      }
      return $re;
    }
    public function getWorkflow($parameter)
    {
      $node_id = $parameter["wf_id"];
      $re = $this->conn->GetData("t","select * from we_app_businessworkflow where wf_id=?",array((string)$node_id));
      return count($re["t"]["rows"])>0 ? $re["t"]["rows"][0] : null;
    }

    public function mylist($parameter)
    {
        $userinfo = $parameter["user"];
        $file_url = $this->containerObj->getParameter("FILE_WEBSERVER_URL");
        $sql = "select b.eno,e.ename,concat('$file_url',e.logo_path_big) logo_path,a.node_id,a.wf_id,a.status,a.node_name,a.submit_date,b.wf_name,b.content from we_app_workflow_node a,we_app_businessworkflow b,we_enterprise e where a.wf_id=b.wf_id and b.eno=e.eno and a.submit_staff=? ";
        $wftype = isset( $parameter["wf_type"])?$parameter["wf_type"]:"";
        if(!empty($wftype))
        {
            $sql.= " and exists(select 1 from we_app_businessworkflow where a.wf_id= wf_id and wf_type=? )";
        }
        $para = array((string)$userinfo->getUserName(),(string)$wftype);
        $returndata = array();
        try
        {
           $ds = $this->conn->GetData("t",$sql,$para);
           if ( $ds && $ds["t"]["recordcount"]>0)
             $returndata = $ds["t"]["rows"];
        }
        catch(\Exception $e)
        {
        }
        return $returndata;
    }

    public function listall($parameter)
    {
      $userinfo = $parameter["user"];
      $file_url = $this->containerObj->getParameter("FILE_WEBSERVER_URL");
      $sql = "select b.eno,e.ename,concat('$file_url',e.logo_path_big) logo_path,c.node_id,c.wf_id,a.status,a.node_name,a.submit_date,b.wf_name,b.content from we_app_workflow_nodesetting c, we_app_workflow_node a,we_app_businessworkflow b,we_enterprise e where a.wf_id=b.wf_id and c.node_id=a.node_id and b.eno=e.eno and c.staff=? ";
      $ds = $this->conn->GetData("t",$sql,array((string)$userinfo->getUserName()));
      return $ds["t"]["rows"];
    }

    public function listtodo($parameter)
    {
      $userinfo = $parameter["user"];
      $file_url = $this->containerObj->getParameter("FILE_WEBSERVER_URL");
      $sql = "select b.eno,e.ename,concat('$file_url',e.logo_path_big) logo_path,c.node_id,c.wf_id,a.status,a.node_name,a.submit_date,b.wf_name,b.content from we_app_workflow_nodesetting c, we_app_workflow_node a,we_app_businessworkflow b,we_enterprise e where a.wf_id=b.wf_id and c.node_id=a.node_id  and b.eno=e.eno and b.status=9 and c.staff=? ";
      $ds = $this->conn->GetData("t",$sql,array((string)$userinfo->getUserName()));
      return $ds["t"]["rows"];
    }

    public function listtdid($parameter)
    {
      $userinfo = $parameter["user"];
      $file_url = $this->containerObj->getParameter("FILE_WEBSERVER_URL");
      $sql = "select b.eno,e.ename,concat('$file_url',e.logo_path_big) logo_path,c.node_id,c.wf_id,a.status,a.node_name,a.submit_date,b.wf_name,b.content from we_app_workflow_nodesetting c, we_app_workflow_node a,we_app_businessworkflow b,we_enterprise e where a.wf_id=b.wf_id and c.node_id=a.node_id and b.eno=e.eno and b.status!=9 and c.staff=? ";
      $ds = $this->conn->GetData("t",$sql,array((string)$userinfo->getUserName()));
      return $ds["t"]["rows"];
    }

    public function detail($parameter)
    {
      $wf_id = $parameter["wf_id"];

      $sql1 = "select *,nodes '' from we_app_businessworkflow b where b.wf_id=? ";
      $sql2 = "select * from we_app_workflow_node b where b.wf_id=? ";
      $ds1 = $this->conn->GetData("t1",$sql,array((string)$wf_id));
      $ds2 = $this->conn->GetData("t2",$sql,array((string)$wf_id));
      $ds1["t1"]["rows"][0]["nodes"] = $ds2["t2"]["rows"];
      return $ds["t1"]["rows"];
    }

}
