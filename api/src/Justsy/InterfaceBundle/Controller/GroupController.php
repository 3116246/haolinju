<?php
namespace Justsy\InterfaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;

class GroupController extends Controller
{
	//根据群id获取群基本资料
	public function getGroupInfoAction()
	{
		$user = $this->get('security.context')->getToken()->getUser();
    $request = $this->getRequest();
    $groupid=$request->get("groupId");
    $code=ReturnCode::$SUCCESS;
    $res=array();
    try{  
	    $da = $this->get('we_data_access');
	    $sql="select circle_id,create_date,create_staff,fafa_groupid,group_class,group_desc,group_id,group_name,group_photo_path,join_method from we_groups where fafa_groupid=?";
	    $params=array($groupid);
	    $ds=$da->Getdata('info',$sql,$params);
	    $res=$ds['info']['rows'];
	  }
	  catch(\Exception $e)
	  {
	  	$this->get('logger')->err($e);
	  	$code=ReturnCode::$SYSERROR;
	  }
	  $re=array('returncode'=> $code,'row'=> $res);
    $response=new Response(json_encode($re));
	  $response->headers->set('Content-Type','Application/json');
	  return $response;
	}

	public function creategroupAction(){
		$re = array("returncode" => ReturnCode::$SUCCESS);
		$user = $this->get('security.context')->getToken()->getUser();
	    $request = $this->getRequest();
	    $circleid=$request->get("circle_id");
	    $groupname=$request->get("group_name");
	    $groupdesc=$request->get("group_desc");
	    $joinmethod=$request->get("join_method","0");
	    $groupclassid=$request->get("group_class_id");
	    $invitedmemebers=$request->get("invitedmemebers");
	    $group_photo_path=$request->get("group_photo_path");
	    $da = $this->get('we_data_access');
	    $da_im = $this->get('we_data_access_im');
	    $ec=new \Justsy\BaseBundle\Management\EnoParamManager($da,$this->get('logger'));
	    try {
	    	if($ec->IsBeyondCreateGroup($user->getUserName())){
		    	$re["returncode"] = ReturnCode::$SYSERROR;
	    		$re["error"]="创建群组数量已超过限制！";
	    		$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		    	$response->headers->set('Content-Type', 'text/json');
		    	return $response;
    		}
	    	if(empty($circleid)||empty($groupname))
	    	{
	    		$re["returncode"] = ReturnCode::$SYSERROR;
	    		$re["error"]="参数传递错误！";
				$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		    	$response->headers->set('Content-Type', 'text/json');
		    	return $response;
	    	}
	    	if(!$this->hasGroup($da,$groupname))
	    	{
			    //根据typeid取出typename
			    $sql = "select typename from im_grouptype where typeid=?";
			    $ds = $da_im->GetData('im_grouptype',$sql,array($groupclassid));    
			    $typename='';
			    if($ds['im_grouptype']["recordcount"]>0)
			    {
			    	$typename=$ds["im_grouptype"]['rows'][0]["typename"];
			    }
			    else {
			    	$typename=$groupclassid;
			    }

		    	 //注册fafa_group
			    $sqls = array(); $paras = array();
			    $fafa_groupid = SysSeq::GetSeqNextValue($da_im,"im_group","groupid");
			    $sqls[] = "insert into im_group (groupid, groupname, groupclass, groupdesc, creator, add_member_method, accessright) 
			        values (?, ?, ?, ?, ?, ?, 'any')";
			    $paras[] = array((string)$fafa_groupid,(string)$groupname,(string)$typename,(string)$groupdesc,
			    	(string)$user->fafa_jid,(string)$joinmethod);
			    $sqls[] = "insert into im_groupemployee (employeeid, groupid, employeenick, grouprole) values (?,?,?,'owner')";
			    $paras[] = array((string)$user->fafa_jid,(string)$fafa_groupid,(string)$user->nick_name);
			    //跟新群组版本号
			    $sqls[] = "delete from im_group_version where us=?";
			    $paras[] = array((string)$user->fafa_jid);
			    $da_im->ExecSQLs($sqls,$paras);
			      //保存图标
			    $sqls = array(); $paras = array();
			    $groupId = SysSeq::GetSeqNextValue($da,"we_groups","group_id");
			    $sqls[] = "insert into we_groups (group_id,circle_id,group_name,group_desc,group_photo_path,join_method,create_staff,fafa_groupid,create_date, group_class)
			        values (?,?,?,?,?,?,?,?,now(),?)";
			    $paras[] = array((string)$groupId,(string)$circleid,(string)$groupname,(string)$groupdesc,(string)$group_photo_path,
			        (string)$joinmethod,(string)$user->getUserName(),(string)$fafa_groupid,(string)$groupclassid);
			    $sqls[] = "insert into we_group_staff (group_id,login_account) values (?,?)";
			    $paras[] = array((string)$groupId,(string)$user->getUserName());
			    $da->ExecSQLs($sqls,$paras);
			      //创建文档根目录
			    $docCtl = new \Justsy\BaseBundle\Controller\DocumentMgrController();
			    $docCtl->setContainer($this->container);
			    if($docCtl->createDir("g".$groupId,"c".$circleid,$groupname,$circleid)>0)
			    {
			        $docCtl->saveShare("g".$groupId,"0",$groupId,"g","w");//将群目录共享给该群组成员
			    }
			      
			    $im_sender = $this->container->getParameter('im_sender');
			      //给创建者发送创建群组成功出席
			    Utils::sendImPresence($im_sender,$user->fafa_jid,"creategroup",json_encode(array("groupid"=> $fafa_groupid,"logoid"=>$group_photo_path,"groupname"=> $groupname)),$this->container,"","",false,Utils::$systemmessage_code);     
			      //给邀请人员发送消息 站内和即时消息 
			    if (!empty($invitedmemebers))
			    {
			    	$invitedmemebers=str_replace("；",";",$invitedmemebers);
			       	$invs = explode(";",$invitedmemebers);
			       	$title = "邀请加入群组";
			        foreach($invs as $key => $value)
			        {
			          if (empty($value)) continue;
			          //群编号,被邀请人帐号,network_domain,fafa_groupid
			          $encode = DES::encrypt("$groupId,$value,$circleid,".$fafa_groupid);
			          $activeurl = $this->generateUrl("JustsyBaseBundle_group_invjoin",array('para'=>$encode), true);
			          $txt = $this->renderView("JustsyBaseBundle:Group:message.html.twig",
			            array("ename"=>$user->ename,"realName"=>$user->nick_name,"activeurl"=>$activeurl,'gname'=>$groupname));
			          //发送站内消息
			          $msgId = SysSeq::GetSeqNextValue($da,"we_message","msg_id");
			          $sql = "insert into we_message(msg_id,sender,recver,title,content,send_date)values(?,?,?,?,?,now())";
			          $da->ExecSQL($sql,array((int)$msgId,(string)$user->getUserName(),(string)$value,"邀请加入群组",$txt));
			          //发送即时消息
			          $fafa_jid = Utils::getJidByAccount($da, $value);
			          //$this->get("logger")->info(Utils::makeHTMLElementTag('employee',$user->fafa_jid,$user->nick_name));
			          $message = Utils::makeHTMLElementTag('employee',$user->fafa_jid,$user->nick_name)."邀请您加入群组【".Utils::makeHTMLElementTag('group',$fafa_groupid,$groupname)."】";
				        $buttons = array();
				        $buttons[]=array("text"=>"立即加入","code"=>"agree","value"=>"1");
				        $buttons[]=array("text"=>"拒绝","code"=>"agree","value"=>"0");
				        Utils::sendImMessage($im_sender,$fafa_jid,$title,$message,$this->container,$activeurl."?invite_user=".$user->fafa_jid,Utils::makeBusButton($buttons),false,Utils::$systemmessage_code,"1");
			        }
			    }
			    $re["group"]=$this->getGroup($da,$user,$circleid,$groupId);
			    //变更版本信息
		      $eno = $user->eno;
		      $verchange = new \Justsy\BaseBundle\Management\VersionChange($da,$this->get("logger"));
		    	$result = $verchange->SetVersionChange(2,$groupId,$eno);
	    	}else
	    	{
	    		$re["returncode"] = ReturnCode::$SYSERROR;
	    		$re["error"]="群组名称已存在！";
	    	}
	    }catch (Exception $e) {
	    	$re["returncode"] = ReturnCode::$SYSERROR;
	    	$re["error"]="系统错误，创建群组失败！";
      		$this->get('logger')->err($e);
	    }
	    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    	$response->headers->set('Content-Type', 'text/json');
    	return $response;
	}

	public function getgroupinvitestaffAction(){
		$re = array("returncode" => ReturnCode::$SUCCESS);
		$user = $this->get('security.context')->getToken()->getUser();
	    $request = $this->getRequest();
	    $circle_id=$request->get("circle_id");
	    $da = $this->get('we_data_access');
	    $da->PageSize= $request->get('pagesize',30);
	  	$da->PageIndex =$request->get('pageindex')?$request->get('pageindex')-1:0;
	  	$search=$request->get("search");
	  	$params=array();
	    $sql = "select a.login_account,a.nick_name,a.photo_path,a.photo_path_small,a.photo_path_big from we_staff a inner join we_circle_staff b on a.login_account=b.login_account where b.circle_id=?
      	and a.login_account!=? ";
      	$params[]=$circle_id;
	    $params[]=$user->getUsername();
      	if(empty($circle_id)){
      		$re["returncode"] = ReturnCode::$SYSERROR;
      		$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
	    	$response->headers->set('Content-Type', 'text/json');
	    	return $response;
      	}
      	if(!empty($search))
	    {
		    $array=explode("@",$search);
	        if(strlen($search)==mb_strlen($search,"utf-8"))
	        {
	            if(!empty($array)&&count($array)>1)
	            {
	                $sql.=" and a.login_account like CONCAT('%',?,'%') ";
	                $params[]=$search;
	            }else
	            {
	                $sql.=" and (substring_index(a.login_account,'@',1) like CONCAT('%',?,'%')";
	                $sql.=" or a.nick_name like CONCAT('%',?,'%') ) ";
					$params[]=$search;
					$params[]=$search;
	            }
	        }else
	        {
	            $sql.=" and a.nick_name like CONCAT('%',?,'%') ";
	            $params[]=$search;
	        }
    	}
        $sql.=" order by a.login_account";
	    try {
	    	$ds = $da->GetData("we_staff",$sql,$params);
	    	$re["staffs"] = $ds["we_staff"]["rows"];
	    } catch (Exception $e) {
	    	$re["returncode"] = ReturnCode::$SYSERROR;
      		$this->get('logger')->err($e);
	    }
	    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    	$response->headers->set('Content-Type', 'text/json');
    	return $response;
	}
	
	public function modifygrouplogoAction(){
		$re = array("returncode" => ReturnCode::$SUCCESS);
	    $user = $this->get('security.context')->getToken()->getUser();
	    $dm = $this->get('doctrine.odm.mongodb.document_manager');
	    $request = $this->getRequest();
	    $circle_id=$request->get("circle_id");
	    $group_id=$request->get("group_id");
	    $filepath = $_FILES['filepath']['tmp_name'];
	    if(empty($filepath)){
	    	$filepath = tempnam(sys_get_temp_dir(), "we");
		    unlink($filepath);
		    $somecontent1 = base64_decode($request->get('filedata'));
		    if ($handle = fopen($filepath, "w+")) {
			    if (!fwrite($handle, $somecontent1) == FALSE) {   
			        fclose($handle);  
			    }
		    }
	    }

	    $da = $this->get("we_data_access");
	    try 
	    {
	    	if(empty($circle_id)||empty($group_id)||empty($filepath))
	    	{
				$re["returncode"] = ReturnCode::$SYSERROR;
	    		$re["error"]="参数传递错误！";
				$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		    	$response->headers->set('Content-Type', 'text/json');
		    	return $response;
	    	}
	      	$filepath = Utils::saveFile($filepath,$dm);
		    $sql='select group_id,group_photo_path,fafa_groupid,group_name from we_groups where group_id=? and circle_id=?';
			$dataset=$da->GetData('we_groups',$sql,array($group_id,$circle_id));
			if($dataset!=null && count($dataset['we_groups']['rows'])>0) 
			{
			  		Utils::removeFile($dataset['we_groups']["rows"][0]["group_photo_path"],$dm);			    
			    	$sql="update we_groups set group_photo_path=? where group_id=? ";
			    	$para=array($filepath,$group_id);
			    	$da->ExecSQL($sql,$para);
			    	//取群成员
				  	$sql="select b.fafa_jid from we_group_staff a left join  we_staff b on a.login_account=b.login_account where a.group_id=? and b.fafa_jid is not null";
				  	$para=array($group_id);
				  	$data=$da->GetData('dt',$sql,$para);
				  	if($data!=null && count($data['dt']['rows'])>0) 
				  	{
				  		//修改头像之后需要发出席消息。让各端及时修改头像
				  		$fafa_jid=$user->fafa_jid;
				  		$tojid=array();
				        for ($i=0; $i < count($data['dt']['rows']); $i++) 
				        { 
			        	    array_push($tojid, $data['dt']['rows'][$i]['fafa_jid']);
				        }
		        		$groupjid= $dataset['we_groups']["rows"][0]["fafa_groupid"];
		        		$groupname= $dataset['we_groups']["rows"][0]["group_name"];
			    		$message=json_encode(array('group_id'=>$group_id
			    			,'logo_path'=>$filepath
			    			,'jid'=>$groupjid
			    			,'group_name'=>$groupname));
		        		Utils::sendImMessage($fafa_jid,implode(",",$tojid),"group_info_change",$message, $this->container,"","",false,Utils::$systemmessage_code);
				  	}
			   }
	      $re["returncode"] = ReturnCode::$SUCCESS;
	      $re["filepath"] = $filepath;
	    }
	    catch (\Exception $e) 
	    {
	      $re["returncode"] = ReturnCode::$SYSERROR;
	      $re["error"]="系统错误，创建圈子失败！";
	      $this->get('logger')->err($e);
	    }
	    
	    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
	    $response->headers->set('Content-Type', 'text/json');
	    return $response;
	}

	public function modifygroupAction(){	    
		$re = array("returncode" => ReturnCode::$SUCCESS);
		$user = $this->get('security.context')->getToken()->getUser();
		$request = $this->getRequest();
		$circle_id=$request->get("circle_id");
		$group_id=$request->get("group_id");
	    $group_name=$request->get("group_name");
	    $group_desc=$request->get("group_desc");
	    $join_method=$request->get("join_method","0");
	    $group_class_id=$request->get("group_class_id");
        $da = $this->get('we_data_access');
        $da_im = $this->get('we_data_access_im');
        try {
        	if(empty($circle_id)||empty($group_id)){
				$re["returncode"] = ReturnCode::$SYSERROR;
	    		$re["error"]="参数传递错误！";
				$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		    	$response->headers->set('Content-Type', 'text/json');
		    	return $response;
	    	}
	    	$sql = "update we_groups set ";
	    	$sql_im = "update im_group set ";
    		$para = array();
    		$para_im = array();
    		if ($group_name !== null)
		    { 
		        $sql .= "group_name=?,";
		        $para[] = $group_name;

		        $sql_im .= "groupname=?,";
		        $para_im[] = $group_name;
		    }
		    if ($group_desc !== null)
		    {
		        $sql .= "group_desc=?,";
		        $para[] = $group_desc;

		        $sql_im .= "groupdesc=?,";
		        $para_im[] = $group_desc;		        
		    }
		    if ($join_method !== null)
		    {
		        $sql .= "join_method=?,";
		        $para[] = $join_method;

		        $sql_im .= "add_member_method=?,";
		        $para_im[] = $join_method;		        
		    }
		    if ($group_class_id !== null)
		    {
		        $sql .= "group_class=?,";
		        $para[] = $group_class_id;

		        $sql_im .= "groupclass=?,";
		        $para_im[] = $group_class_id;		        
		    }
		    if (count($para) === 0) throw new \Exception("param is null");
		    $sql = substr($sql, 0, strlen($sql)-1);		    
		    $sql .= " where circle_id=? and (group_id=? or fafa_groupid=?)";
		    $para[] = $circle_id;
		    $para[] = $group_id;
		    $para[] = $group_id;
		    $da->ExecSQL($sql,$para);

		    $sql_im = substr($sql_im, 0, strlen($sql_im)-1);
		    $sql_im .= " where groupid=?";
		    $para_im[] = $group_id;
			$da_im->ExecSQL($sql_im,$para_im);

			$re["group"]=$this->getGroup($da,$user,$circle_id,$group_id);
			//变更版本信息
		    $eno = $user->eno;
		    $verchange = new \Justsy\BaseBundle\Management\VersionChange($da,$this->get("logger"));
		    $result = $verchange->SetVersionChange(2,$group_id,$eno);
			$groupMgr = new \Justsy\BaseBundle\Controller\GroupController();
			$groupMgr->setContainer($this->container);
		    $message=json_encode(array('group_id'=>$group_id
			    			,'jid'=>$group_id
			    			,'group_name'=>$group_name));
		    $groupMgr->sendPresenceGroup($group_id,"group_info_change",$message);		    
        } catch (\Exception $e) {
        	$re["returncode"] = ReturnCode::$SYSERROR;
        	$re["error"]="群组名称修改失败";
      		$this->get('logger')->err($e);
        }
        $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    	$response->headers->set('Content-Type', 'text/json');
    	return $response;
	}

	public function exitgroupAction(){
		$re = array("returncode" => ReturnCode::$SUCCESS);
		$user = $this->get('security.context')->getToken()->getUser();
		$request = $this->getRequest();
		$group_id=$request->get("group_id");
		$circle_id=$request->get("circle_id");
        $da = $this->get('we_data_access');
        try {
        	if(empty($group_id)){
				$re["returncode"] = ReturnCode::$SYSERROR;
				$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		    	$response->headers->set('Content-Type', 'text/json');
		    	return $response;
	    	}
	    	$this->get("logger")->err("---------quit group --------------".$group_id);
	    	$this->get("logger")->err("---------quit group --------------".$user->getUserName());
        	$sql = "select fafa_groupid,group_name from we_groups where group_id=? or fafa_groupid=? ";
		    $ds = $da->GetData('we_groups',$sql,array((string)$group_id,(string)$group_id));  
		    $fafa_groupid =$ds['we_groups']['rows'][0]['fafa_groupid'];  
		    $group_name = $ds['we_groups']['rows'][0]['group_name'] ;
		    $sql = "call p_quitgroup(?, ?, 0)";
			$params = array();
			$params[] = (string)$group_id;
			$params[] = (string)$user->getUserName();
		    $ds = $da->GetData("p_quitgroup", $sql, $params);
		    
		    
		    
		    //变更版本信息
		    
		    
		    $this->get("logger")->err("---------quit group --------------");
		    		    
		    $eno = $user->eno;
		    $verchange = new \Justsy\BaseBundle\Management\VersionChange($da,$this->get("logger"));
		    $result = $verchange->SetVersionChange(2,$group_id,$eno);
		      
		    
		    if ($ds["p_quitgroup"]["rows"][0]["recode"] == "0")
		    {
		    	$send=new \Justsy\BaseBundle\Controller\GroupController();
		    	$send->setContainer($this->container);
		      	//向客户端发送即时通知
		      	$message = Utils::makeHTMLElementTag('employee',$user->fafa_jid,$user->nick_name)."退出了群组【".Utils::makeHTMLElementTag('group',$fafa_groupid,$group_name)."】";
		      	$send->sendPresenceGroup($fafa_groupid,"group_deletemeber",$message);
		      
		    }
		    else
		    {
		    	$re["returncode"] = ReturnCode::$SYSERROR;
		     	$this->get('logger')->err("quitGroup Error group_id:".$group_id." msg:".$ds["p_quitgroup"]["rows"][0]["remsg"]);
		    }
        } catch (\Exception $e) {
        	$re["returncode"] = ReturnCode::$SYSERROR;
      		$this->get('logger')->err($e);
        }
        $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    	$response->headers->set('Content-Type', 'text/json');
    	return $response;
	}

	public function invitedmemebersAction(){
		$re = array("returncode" => ReturnCode::$SUCCESS);
		$user = $this->get('security.context')->getToken()->getUser();
		$request = $this->getRequest();
		$group_id=$request->get("group_id");
		$circle_id=$request->get("circle_id");
		$group_name=$request->get("group_name");
		$fafa_groupid = $request->get('fafa_groupid');
		$invitedmemebers=$request->get("invitedmemebers");
		$im_sender = $this->container->getParameter('im_sender');
        $da = $this->get('we_data_access');
        try {
        	if(empty($circle_id)||empty($group_id)||empty($fafa_groupid)||empty($invitedmemebers))
        	{
        		$re["returncode"] = ReturnCode::$SYSERROR;
				$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		    	$response->headers->set('Content-Type', 'text/json');
		    	return $response;
        	}
	        $invs = explode(";",$invitedmemebers);
			$title = "邀请加入群组";
			$groupStaffs=array();
			$sql="select login_account from we_group_staff where group_id=?";
			$ds=$da->GetData("group_staffs",$sql,array($group_id));
			if($ds && $ds["group_staffs"]["recordcount"]>0)
			{
				foreach ($ds["group_staffs"]["rows"] as &$row) 
				{
					$groupStaffs[]=$row['login_account'];
                }
			}
			foreach($invs as $key => $value)
			{
				if (empty($value)) continue;
				if(count($groupStaffs)>0 && in_array($value, $groupStaffs))continue;
				//群编号,被邀请人帐号,network_domain,fafa_groupid
				$encode = DES::encrypt("$group_id,$value,$circle_id,".$fafa_groupid);
				$activeurl = $this->generateUrl("JustsyBaseBundle_group_invjoin",array('para'=>$encode), true);
				$txt = $this->renderView("JustsyBaseBundle:Group:message.html.twig",
				array("ename"=>$user->ename,"realName"=>$user->nick_name,"activeurl"=>$activeurl,'gname'=>$group_name));
				//发送站内消息
				$msgId = SysSeq::GetSeqNextValue($da,"we_message","msg_id");
				$sql = "insert into we_message(msg_id,sender,recver,title,content,send_date)values(?,?,?,?,?,now())";
				$da->ExecSQL($sql,array((int)$msgId,(string)$user->getUserName(),(string)$value,"邀请加入群组",$txt));
				//发送即时消息
				$fafa_jid = Utils::getJidByAccount($da, $value);
				//$this->get("logger")->info(Utils::makeHTMLElementTag('employee',$user->fafa_jid,$user->nick_name));
				$message = Utils::makeHTMLElementTag('employee',$user->fafa_jid,$user->nick_name)."邀请您加入群组【".Utils::makeHTMLElementTag('group',$fafa_groupid,$group_name)."】";
				$buttons = array();
				$buttons[]=array("text"=>"拒绝","code"=>"agree","value"=>"0");
				$buttons[]=array("text"=>"立即加入","code"=>"agree","value"=>"1");
				Utils::sendImMessage($im_sender,$fafa_jid,$title,$message,$this->container,$activeurl."?invite_user=".$user->fafa_jid,Utils::makeBusButton($buttons),false,Utils::$systemmessage_code,"1");
			}
	        $re["returncode"]=ReturnCode::$SUCCESS;
        } catch (\Exception $e) {
        	$re["returncode"] = ReturnCode::$SYSERROR;
      		$this->get('logger')->err($e);
        }
        $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    	$response->headers->set('Content-Type', 'text/json');
    	return $response;
	}

	public function hasGroup($da,$groupname){
	    try {
	    	$sql="select group_id from we_groups where group_name=?";
	    	$ds=$da->Getdata('groups',$sql,array($groupname));
	    	 if ($ds && $ds['groups']['recordcount']>0)
	        {
	          return true;
	        }
	    } catch (\Exception $e) {
	        $this->get('logger')->err($e);
	    }
	    	return false;
	}

	private function getGroup($da, $user,$circleid,$groupid){
		$re = array();
		$da_im = $this->get('we_data_access_im');
		$sql = "select * from im_group a where a.groupid=? ";
		$ds = $da_im->GetData("group",$sql,array((string)$groupid));
	    $rowRoot = count($ds["group"]["rows"])>0 ? $ds["group"]["rows"][0] : array();
	    return $rowRoot;
	}
}
