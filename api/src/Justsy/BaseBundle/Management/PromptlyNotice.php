<?php
namespace Justsy\BaseBundle\Management;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Common\Utils;

class PromptlyNotice
{
	private $container=null;
	private $logger=null;
	private $conn=null;
	private $conn_im=null;

	private $user= null;

	private $module = null; 

	public function __construct($container,$user,$appdata)
	{
		$this->container=$container;
		$this->logger=$container->get("logger");
		$this->conn = $container->get("we_data_access");
		$this->conn_im = $container->get("we_data_access_im");
		$this->user = $user;
		$this->module = $appdata;
	}
	//新通知 
	public function pushNotice($data)
	{
		$receiver = $data["receiver"];
		if(empty($receiver))
		{
			return Utils::WrapResultError("接收人不能为空");	
		}
		$receiver = explode(',',$receiver);
		if(empty($data["text"]))
		{
			return Utils::WrapResultError("内容不能为空");
		}
		$files = isset($data["files"]) ? $data["files"] : '';
		if(!empty($files) && is_array($files))
			$files = implode(',', $files);
		$noticeid = SysSeq::GetSeqNextValue($this->conn_im,"im_pushnotice_msg","id");	
		$filefix=$this->container->getParameter('FILE_WEBSERVER_URL');
		$senddata=array();
		$senddata = array(
			'noticeid'=>$noticeid,
			'text'=>$data["text"],
			'files'=>$files,
			'receivercount'=>count($receiver),
			'receiver'=>implode(',', $receiver) ,
			'sender'=>array('nickname'=>$this->user["nick_name"],
							'photo'=>$this->user["photo_path"],
							'jid'=>$this->user["jid"],
							'date'=>date("Y-m-d H:i:s",time()))
			);
		$notice = Utils::WrapMessageNoticeinfo($data["text"],$this->module["appname"],null,$this->module["logo"]);
		$msg = Utils::WrapMessage('push-notice',$senddata,$notice);
		$msgxml = Utils::WrapMessageXml($this->module["jid"],$msg,'push-notice-'.$noticeid);

		$sql = 'insert into im_pushnotice_msg(id,replyid,msg,created,us,msgid)values(?,0,?,now(),?,?)';
		$para=array((int)$noticeid,(string)$msgxml,(string)$this->user["jid"],'push-notice-'.$noticeid);
		$sql2 = 'insert into im_pushnotice_memebr(noticeid,employeeid,lastread_reply,receive_time)values';
		$sql2Values=array();
		$staffinfo = new \Justsy\BaseBundle\Management\Staff($this->conn,$this->conn_im,$this->user['login_account'],$this->logger,$this->container);
		
		foreach ($receiver as $key => $value) {
			$rdata = $staffinfo->getstaffinfo($value);
			if(!empty($rdata))
			{
				$receiver[$key] = $rdata['jid'];
				$sql2Values[] = '('.$noticeid.',\''.$rdata['jid'].'\',0,null)';
			}
		}
		if(count($sql2Values)>0)
		{
			$sql2 = $sql2.implode(',', $sql2Values);
			$this->conn_im->ExecSQLs(array($sql,$sql2),array($para,array()));
			//发送消息
			$receiver[] = $this->user["jid"];
			Utils::findonlinejid($this->conn_im,$receiver);
			if(count($receiver)>0)
			{
				$presence = new \Justsy\OpenAPIBundle\Controller\ApiController();
		        $presence->setContainer($this->container);
		        $presence->sendMsg($this->module["jid"],$receiver,'新通知送达',json_encode($msg));
	    	}
    	}
		return Utils::WrapResultOK(array('noticeid'=>$noticeid));

	}
	//接收确认
	public function received($data)
	{
		$noticeid = $data["noticeid"];
		if(empty($noticeid))
		{
			return Utils::WrapResultError("noticeid不能为空");	
		}
		$sql = 'update im_pushnotice_memebr set receive_time=now() where noticeid=? and employeeid=?';
		$this->conn_im->ExecSQL($sql,array((int)$noticeid,(string)$this->user["jid"]));
		$sql = 'select employeeid from im_pushnotice_memebr where noticeid=? and receive_time is not null';
		$ds = $this->conn_im->Getdata('t',$sql,array((int)$noticeid));
		$cnt = count($ds['t']['rows']);
		$receiverlist = array();
		foreach ($ds['t']['rows'] as $key => $value) {
			$receiverlist[] = $value['employeeid'];
		}
		$senddata=array();
		$senddata = array(
			'noticeid'=>$noticeid,
			'received'=>$cnt,
			'receiver'=>array('nickname'=>$this->user["nick_name"],
							'photo'=>$this->user["photo_path"],
							'jid'=>$this->user["jid"],
							'state'=>'1'),
			'receiverlist'=>implode(',', $receiverlist)
		);
		$notice = null;
		$msg = Utils::WrapMessage('push-notice-receive',$senddata,$notice);
		$msgxml = Utils::WrapMessageXml($this->module["jid"],$msg,'push-notice-receive-'.$noticeid);
		$noticeinfo = $this->getinfo($noticeid);
		$receiver = $this->getmember($noticeid);
		$receiver[] = $noticeinfo["us"];
		Utils::findonlinejid($this->conn_im,$receiver);
		if(count($receiver)>0)
		{
			//发送消息
			$presence = new \Justsy\OpenAPIBundle\Controller\ApiController();
	        $presence->setContainer($this->container);
	        $presence->sendMsg($this->module["jid"],$receiver,'通知确认',json_encode($msg));
    	}
		return Utils::WrapResultOK(array('noticeid'=>$noticeid));	
	}
	//回复
	public function reply($data)
	{
		$noticeid = $data["noticeid"];
		if(empty($noticeid))
		{
			return Utils::WrapResultError("noticeid不能为空");	
		}
		$files =isset($data["files"]) ? $data["files"] : '';
		if(!empty($files) && is_array($files))
			$files = implode(',', $files);
		$sql = 'update im_pushnotice_memebr set receive_time=now() where noticeid=? and employeeid=? and receive_time is not null';
		$sql1 = 'insert into im_pushnotice_msg(id,replyid,msg,created,us,msgid)values(?,?,?,now(),?,?)';
		$replyid = SysSeq::GetSeqNextValue($this->conn_im,"im_pushnotice_msg","id");
		
		$sql = 'select count(1)+1 cnt from im_pushnotice_msg where replyid=?';
		$ds = $this->conn_im->Getdata('t',$sql,array((int)$noticeid));
		$cnt = $ds['t']['rows'][0]['cnt'];
		$senddata=array();
		$senddata = array(
			'noticeid'=>$noticeid,
			'reply_count'=>$cnt,
			'reply'=>array(
							'id'=>$replyid,
							'nickname'=>$this->user["nick_name"],
							'photo'=>$this->user["photo_path"],
							'jid'=>$this->user["jid"],
							'text'=>$data["reply-text"],
							'files'=>$files,
							'sendtime'=>date("Y-m-d H:i:s",time()))
		);
		$notice = array();// Utils::WrapMessageNoticeinfo($data["reply-text"],$this->module["appname"],null,$this->module["logo"]);
		$msg = Utils::WrapMessage('push-notice-reply',$senddata,$notice);
		$msgxml = Utils::WrapMessageXml($this->module["jid"],$msg,'push-notice-reply-'.$replyid);
		$this->conn_im->ExecSQLs(array($sql,$sql1),
			array(
				array((int)$noticeid,(string)$this->user["jid"]),
				array((int)$replyid,(int)$noticeid,(string)json_encode($senddata['reply']),$this->user['jid'],'push-notice-reply-'.$replyid)
			)
		);		
		$noticeinfo = $this->getinfo($noticeid);
		$receiver = $this->getmember($noticeid);
		$receiver[] = $noticeinfo["us"];
		Utils::findonlinejid($this->conn_im,$receiver);
		if(!empty($receiver))
		{
			//发送消息
			$presence = new \Justsy\OpenAPIBundle\Controller\ApiController();
	        $presence->setContainer($this->container);
	        $presence->sendMsg($this->module["jid"],$receiver,'通知回复',json_encode($msg));
    	}
		return Utils::WrapResultOK(array('noticeid'=>$noticeid));
	}

	public function getList($paraObj)
	{
		$lastid = isset($paraObj['lastid']) ? $paraObj['lastid'] : '';

		$sql = 'select b.id,b.msg,b.created , 1 received from im_pushnotice_msg b where us=? union select b.id,b.msg,b.created,case when a.receive_time is null then 0 else 1 end  received from im_pushnotice_memebr a,im_pushnotice_msg b where a.noticeid=b.id and a.employeeid=?';
		if(empty($lastid))
		{
			$sql = 'select a.*,(select count(1) from im_pushnotice_memebr where a.id=noticeid and receive_time is not null) receivedcount,(select count(1) from im_pushnotice_msg where a.id=replyid)  replycount from ('.$sql.') a order by a.created desc limit 0,20';
		}
		else
		{
			$sql = 'select a.*,(select count(1) from im_pushnotice_memebr where a.id=noticeid and receive_time is not null) receivedcount,(select count(1) from im_pushnotice_msg where a.id=replyid)  replycount from ('.$sql.') a where id<'.$lastid.' order by a.created desc limit 0,20';
		}
		
		$ds = $this->conn_im->Getdata('t',$sql,array((string)$this->user['jid'],(string)$this->user['jid']));
		return Utils::WrapResultOK($ds['t']['rows']);
	}	

	public function getUnreadList($paraObj)
	{
		$lastid = isset($paraObj['lastid']) ? $paraObj['lastid'] : '';

		$sql = 'select b.id,b.msg,b.created from im_pushnotice_memebr a,im_pushnotice_msg b where  a.noticeid=b.id and a.receive_time is null and a.employeeid=?';
		if(empty($lastid))
		{
			$sql = 'select a.*,(select count(1) from im_pushnotice_memebr where a.id=noticeid and receive_time is not null) receivedcount,(select count(1) from im_pushnotice_msg where a.id=replyid)  replycount from ('.$sql.') a order by a.created desc limit 0,20';
		}
		else
		{
			$sql = 'select a.*,(select count(1) from im_pushnotice_memebr where a.id=noticeid and receive_time is not null) receivedcount,(select count(1) from im_pushnotice_msg where a.id=replyid)  replycount from ('.$sql.') a where id<'.$lastid.' order by a.created desc limit 0,20';
		}		

		$ds = $this->conn_im->Getdata('t',$sql,array((string)$this->user['jid']));
		return Utils::WrapResultOK($ds['t']['rows']);
	}

	public function getReceiverList($data)
	{
		$noticeid = $data['noticeid'];
		$sql = 'select a.employeeid jid, b.photo,b.employeename nickname,case when a.receive_time is null then 0 else 1 end state from im_pushnotice_memebr a,im_employee b where a.employeeid=b.loginname and a.noticeid=?';
		$ds = $this->conn_im->Getdata('t',$sql,array((int)$noticeid));	
		return Utils::WrapResultOK($ds['t']['rows']);
	}

	public function getReplyList($data)
	{
		$noticeid = $data['noticeid'];
		$lastreadid =isset($data['lastreadid']) ? $data['lastreadid'] : '';
		if(empty($lastreadid))
		{
			//$sql = 'select ifnull(lastread_reply,0) lastread_reply from im_pushnotice_memebr where noticeid=? and employeeid=?';
			//$ds = $this->conn_im->Getdata('t',$sql,array((int)$noticeid,(string)$this->user['jid']));
			$lastread_reply = 0;//$ds['t']['rows'][0]['lastread_reply'];
			$sql = 'select a.msg from im_pushnotice_msg a where a.replyid=? and a.id>? order by a.id desc limit 0,30';
			$ds = $this->conn_im->Getdata('t',$sql,array((int)$noticeid,(int)$lastread_reply));
		}
		else
		{
			$sql = 'select a.msg from im_pushnotice_msg a where a.replyid=? and a.id<? order by a.id desc limit 0,30';
			$ds = $this->conn_im->Getdata('t',$sql,array((int)$noticeid,(int)$lastreadid));
		}
		if(empty($lastreadid))
		{
			$this->conn_im->ExecSQL('update im_pushnotice_memebr set lastread_reply=(select max(id) from im_pushnotice_msg where replyid=?) where noticeid=? and employeeid=?',array((int)$noticeid,(int)$noticeid,(string)$this->user['jid']));
		}
		$result=array();
		foreach ($ds['t']['rows'] as $key => $value) {
			$result[] = json_decode($value['msg'],true);
		}
		return Utils::WrapResultOK($result);
	}

	protected function writelog($e)
	{
		if(!empty($this->logger))
		{
			$this->logger->err($e);
		}
	}

	private function getmember($noticeid)
	{
		$sql = 'select employeeid from im_pushnotice_memebr where noticeid=?';
		$ds = $this->conn_im->Getdata('t',$sql,array((int)$noticeid));	
		$rs = $ds['t']['rows'];
		$list=array();
		foreach ($rs as $key => $value) {
			$list[] = $value['employeeid'];
		}
		return $list;
	}
	private function getinfo($noticeid)
	{
		$sql = 'select * from im_pushnotice_msg where id=?';
		$ds = $this->conn_im->Getdata('t',$sql,array((int)$noticeid));	
		return $ds['t']['rows'][0];
	}
}
?>