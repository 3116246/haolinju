<?php

namespace Justsy\AdminAppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Login\UserProvider;
use Justsy\BaseBundle\Login\UserSession;
use Justsy\BaseBundle\DataAccess\SysSeq;

class MsgPushController extends Controller
{    
	//返回json字符串
	private function responseJson($re) {
        $response = new Response($re);
        $response->headers->set('Content-Type', 'text/html');
        return $response;
    }
    
	public function getLink($uniqid) {
		$web_url=$this->container->getParameter('open_api_url');
		return $web_url.'/api/http/getpagepath/'.$uniqid;
	}

	//生成表单地址
	public function getWebFormLink($formid) {
		$web_url=$this->container->getParameter('push_webform_makeurl');
		return $web_url."?formid=".$formid;
	}	

  public function pushAction()
  {
	  $re = array("returncode" => ReturnCode::$SUCCESS);
	  $request = $this->getRequest();
	  $user = $this->get('security.context')->getToken()->getUser();
  	$da = $this->get('we_data_access'); 
	  $micro_number = $request->get("m");
	  try 
	  {
		  // 公众号列表
  		$sql = "select case when a.micro_use=1 then concat(name,'【微应用】') else case when type=0 then concat(name,'【内部公众号】') else concat(name,'【外部公众号】') end end as sayname,
                     a.name,a.type,a.number,a.jid,b.openid,a.micro_use
              from we_micro_account a inner join we_staff b on a.number=b.login_account and a.eno=b.eno where a.eno=? and a.name is not null";	
		  $params = array(); 
		  $params[] = (string)$user->eno;
		  if (!empty($micro_number))
		  {
			  $sql .= " and a.number=? ";
			  $params[] = (string)$micro_number;
		  }
		  $sql .= " order by a.type,a.create_datetime";
		  $ds = $da->GetData("microlist", $sql, $params);
		  $re["microlist"] = $ds["microlist"]["rows"];
	  } 
	  catch (\Exception $e) 
	  {
		  $re["returncode"] = ReturnCode::$SYSERROR;
		  $this->get('logger')->err($e);
	  }
	  $sql = "select date_format(send_datetime,'%Y-%m-01') val,date_format(send_datetime,'%Y-%m') s_date from we_micro_send_message group by val order by val desc;";
	  $ds = $da->GetData("table",$sql);
	  $re["pushmonth"] = json_encode($ds["table"]["rows"]);		
	  return $this->render('JustsyAdminAppBundle:MsgPush:push.html.twig', $re);
  }
	
		//服务号消息推送
    public function SendMsgAction()
	  {
        $conn = $this->get("we_data_access");
        $conn_im = $this->get("we_data_access_im");
        $request = $this->getRequest();  
        $currUser = $this->get('security.context')->getToken();
        if($currUser==null)
        {
        	$openid= $request->get('openid');
        	$staffinfo = new \Justsy\BaseBundle\Management\Staff($conn,$conn_im,$openid,$this->get("logger"),$this->container);
			$staffdata = $staffinfo->getInfo();
			if(empty($staffdata))
			{
				$re= array('returncode'=>'9999','msg'=>'无效的操作人');
	        	return $this->responseJson(json_encode($re));
			}
			$user = $staffinfo->getSessionUser($staffdata);
        }
        else
        {
        	$user = $this->get('security.context')->getToken()->getUser();
    	}
              
        //公众号相关参数
        $microObj= $request->get('microObj');
        $microName = $microObj["microName"]; //接收对象(公众号名称)
        $microNumber= $microObj["microNumber"]; //接收对象(公众号帐号)
        $microOpenid= $microObj["microOpenid"]; //接收对象(公众号Openid)
        $microType= $microObj["microType"]; //接收对象(公众号类型,内部或外部)
        $microUse= $microObj["microUse"]; //接收对象(是公众号还是微应用)
        $microGroupId= ""; //$microObj["microGroupId"]; //接收对象(公众号分组主键)

        //消息参数
        $msgType= ""; //消息类型
        $msgContent= ""; //消息内容(XML拼接Json字符串,包括标题,图片,摘要等)
        $msgContentHtml= ""; //消息内容(HTML内容)
        $msgTitle= ""; //消息标题
        $imgUrl= ""; //图片地址	  
        $formid = "";//表单编号。推送表单时设置
        $webpage_url = "";//网页地址。推送网页地址时设置
        $msgObj_list= $request->get('msgObj'); //消息对象	    
        if(!empty($msgObj_list)) {
            foreach ($msgObj_list as $key => $val){
            	if($key=="type") $msgType=$val;
            	else if($key=="msgContent") $msgContent=$val;
            	else if($key=="contentHtml") $msgContentHtml=$val;
            	else if($key=="title") $msgTitle=$val;
            	else if($key=="imgUrl") $imgUrl=$val;
            	else if($key=="formid") $formid=$val;
            	else if($key=="webpage_url") $webpage_url=$val;
            }
        }
        $staffinfo = new \Justsy\BaseBundle\Management\Staff($conn,$conn_im,empty($microNumber)? $microOpenid : $microNumber,$this->get("logger"),$this->container);
		$staffdata = $staffinfo->getInfo();
        if(empty($staffdata)) {
	        $re= array('returncode'=>'9999','msg'=>'请选择接收对象');
	        return $this->responseJson(json_encode($re));
        }
        else
        {
        	$microOpenid = $staffdata["openid"];
        	$microNumber = $staffdata["login_account"];
        }
        $re= array('returncode'=>'0000');
        $sqls=array();
        $paras=array();
        $send_state='2';
        $id=SysSeq::GetSeqNextValue($conn,'we_micro_send_message','id');
        $sqls[]= "insert into `we_micro_send_message` (`id`, `send_account`, `send_groupid`, `send_datetime`, `send_state`, `send_isbutton`, `send_source`,`send_type`) VALUES (?, ?, ?, now(), ?, ?, ?,?);";
        $paras[]= array($id,$microNumber,$microGroupId,$send_state,false,'wefafa',$msgType);
        $error= array('returncode'=>'9999','msg'=>'消息内容有误,请检查');
        //处理消息
        switch ($msgType) 
        {
            case 'PICTURE':
            	$title= ''; //标题
            	$image_type= ''; //图片类型  URL或CODE
              	$image_value= ''; //图片地址
              	$content= ''; //摘要
              	$link= ''; //手机端点击之后连接地址
              	try
              	{
            	  	foreach ($msgContent as $key => $value) 
            	  	{
            			if($key=='picturemsg') {
            				$picturemsg = $value;
            				//判断参数是否为空。并返回错误提示
            				if(empty($picturemsg)) return $this->responseJson(json_encode($error));
            				foreach ($picturemsg as $pkey => $pvalue) {
            					if($pkey=='headitem') {
            						$headitem= $pvalue;
            						//判断参数是否为空。并返回错误提示
            						if(empty($headitem)) return $this->responseJson(json_encode($error));
            						foreach ($headitem as $hkey => $hvalue) {
            							if($hkey=='title') $title=$hvalue;
            							else if($hkey=='image') {
            								$image=$hvalue;
            								//判断参数是否为空。并返回错误提示
            								if(empty($image)) return $this->responseJson(json_encode($error));
            								foreach ($image as $ikey => $ivalue) {
            									if($ikey=='type') $image_type=$ivalue;
            									else if($ikey=='value') $image_value=$ivalue;
            								}
            							}else if($hkey=='content') $content=$hvalue;
            						}
            					}
            				}
            			}
            		}
              	} 
              	catch (Exception $e) 
              	{
            	  	$this->get('logger')->err($e->getMessage());
            		return $this->responseJson(json_encode($error));
              	}
              	if(!empty($webpage_url))
              	{
              		$link = $webpage_url;
              		$uniqid= $webpage_url;
              	}
              	else
              	{
              		$uniqid=str_replace('.','',uniqid('',true));
              		$link=!empty($formid) ? $this->getWebFormLink($formid) : $this->getLink($uniqid);
              		if(!empty($formid))
              		{
              			$uniqid = $link;
              		}
              	}
              	$noticeinfo = Utils::WrapMessageNoticeinfo($title,$microName);
              	$msgContent=Utils::WrapMessage("mm-picturemsg",
              		array('headitem'=>array('title'=>$title,
				              				'image'=>array('type'=>$image_type,'value'=>$image_value),
				              				'content'=>$content,
				              				'link'=>$link)
              			),
              		$noticeinfo);
              	//$msgContent= array('picturemsg'=>array('headitem'=>array('title'=>$title,'image'=>array('type'=>$image_type,'value'=>$image_value),'content'=>$content,'link'=>$link)));
              	$msgid=SysSeq::GetSeqNextValue($conn,'we_micro_message','id');
              	$sqls[]= "insert into `we_micro_message` (`id`, `send_id`, `msg_title`, `msg_type`, `msg_text`, `msg_content`, `msg_summary`, `msg_img_type`, `msg_img_url`, `msg_web_url`, `ishead`, `isread`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
              	$msgContentHtml = $this->SetElementStyle($msgContentHtml);
              	$paras[]= array($msgid,$id,$title,$msgType,null,$msgContentHtml,$content,$image_type,$image_value,$uniqid,true,false);
            	break;
            case 'TEXTPICTURE':
            	try {
            		$headitem=array();
            		$items=array();
            		foreach ($msgContent as $key => $value) {
            			if($key=='textpicturemsg') {
            				$textpicturemsg=$value;
            				if(empty($textpicturemsg)) return $this->responseJson(json_encode($error));
            				foreach ($textpicturemsg as $tpmkey => $tpmvalue) {
            					if($tpmkey=='headitem') {
            						$headitem=$tpmvalue;
            						if(empty($headitem)) return $this->responseJson(json_encode($error));
            						$head_title='';
            						$head_img_type='';
            						$head_img_url='';
            						$head_contentHtml='';
            						$head_link='';
            						$formid = "";
            						foreach ($headitem as $hkey => $hvalue) {
            							if($hkey=='title') $head_title=$hvalue;
            							else if($hkey=='image') {
            								$image=$hvalue;
            								if(empty($image)) return $this->responseJson(json_encode($error));
            								foreach ($image as $imgkey => $imgvalue) {
            									if($imgkey=='type') $head_img_type=$imgvalue;
            									else if($imgkey=='value') $head_img_url=$imgvalue;
            								}
            							}
            							else if($hkey=='content') $head_contentHtml=$hvalue;
            							else if($hkey=='formid')	$formid=$hvalue;
            						}
            						$uniqid=str_replace('.','',uniqid('',true));
            						$head_link=!empty($formid) ? $this->getWebFormLink($formid) : $this->getLink($uniqid);
            
            						$headitem= array('title'=>$head_title
            									,'image'=>array('type'=>$head_img_type,'value'=>$head_img_url)
            									,'link'=>$head_link);
            						$msgid=SysSeq::GetSeqNextValue($conn,'we_micro_message','id');
            						$sqls[]= "insert into `we_micro_message` (`id`, `send_id`, `msg_title`, `msg_type`, `msg_text`, `msg_content`, `msg_summary`, `msg_img_type`, `msg_img_url`, `msg_web_url`, `ishead`, `isread`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
            						$head_contentHtml = $this->SetElementStyle($head_contentHtml);
            						$paras[]= array($msgid,$id,$head_title,$msgType,null,$head_contentHtml,null,$head_img_type,$head_img_url,$uniqid,true,false);
            		    		
            					}else if($tpmkey=='item') {
            						$item=$tpmvalue;
            						if(empty($item)) return $this->responseJson(json_encode($error));
            						$item_array=array();
            						for ($i=0; $i < count($item); $i++) { 
            							$item_title='';
            							$item_img_type='';
            							$item_img_url='';
            							$item_contentHtml='';
            							$item_link='';
            							$formid = "";
            							foreach ($item[$i] as $itemkey => $itemvalue) {
            								if($itemkey=='title') $item_title=$itemvalue;
            								else if($itemkey=='image') {
            									$image=$itemvalue;
            									if(empty($image)) return $this->responseJson(json_encode($error));
            									foreach ($image as $imgkey => $imgvalue) {
            										if($imgkey=='type') $item_img_type=$imgvalue;
            										else if($imgkey=='value') $item_img_url=$imgvalue;
            									}
            								}
            								else if($itemkey=='content') $item_contentHtml=$itemvalue;
            								else if($itemkey=='formid')	$formid=$itemvalue;
            							}
            							$uniqid=str_replace('.','',uniqid('',true));
            							$item_link=!empty($formid) ? $this->getWebFormLink($formid) : $this->getLink($uniqid);
            
            							$item_array= array('title'=>$item_title
            									,'image'=>array('type'=>$item_img_type,'value'=>$item_img_url)
            									,'link'=>$item_link);
            							array_push($items, $item_array);
            							$msgid=SysSeq::GetSeqNextValue($conn,'we_micro_message','id');
            							$sqls[]= "INSERT INTO `we_micro_message` (`id`, `send_id`, `msg_title`, `msg_type`, `msg_text`, `msg_content`, `msg_summary`, `msg_img_type`, `msg_img_url`, `msg_web_url`, `ishead`, `isread`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
            							$paras[]= array($msgid,$id,$item_title,$msgType,null,$item_contentHtml,null,$item_img_type,$item_img_url,$uniqid,false,false);
            						}
            					}
            				}
            			}
            		}
            		$noticeinfo = Utils::WrapMessageNoticeinfo($headitem["title"],$microName);
              		$msgContent=Utils::WrapMessage("mm-textpicturemsg",
              							array('headitem'=>$headitem,'item'=>$items),
              					$noticeinfo);
            		//$msgContent= array('textpicturemsg'=>array('headitem'=>$headitem,'item'=>$items));
            	} catch (\Exception $e) {
            		$this->get('logger')->err($e->getMessage());
            		return $this->responseJson(json_encode($error));
            	}
            	break;
            case 'TEXT':
            	foreach ($msgContent as $key => $value) {
            		if($key=='textmsg') {
            			$textmsg= $value;
            			//判断参数是否为空。并返回错误提示
            			if(empty($textmsg)) return $this->responseJson(json_encode($error));
            			foreach ($textmsg as $tkey => $tvalue) {
            				if($tkey=='item') {
            					$items=$tvalue;
            					//判断参数是否为空。并返回错误提示
            					if(empty($items)) return $this->responseJson(json_encode($error));
            					$new_items=array();
            					for ($i=0; $i < count($items); $i++) 
            					{
	              					$title='';
	              					$content='';
	              					foreach ($items[$i] as $itemkey => $itemvalue) {
	              						if($itemkey=='title') $title=$itemvalue;
	              						else if($itemkey=='content') $content=$itemvalue;
	              					}
	              					if(empty($title)) return $this->responseJson(json_encode($error));
	              					if(empty($content)) return $this->responseJson(json_encode($error));
	            
	              					array_push($new_items, array('title'=>$title,'content'=>$content));
	                				$msgid=SysSeq::GetSeqNextValue($conn,'we_micro_message','id');
	                				$sqls[]= "INSERT INTO `we_micro_message` (`id`, `send_id`, `msg_title`, `msg_type`, `msg_text`, `msg_content`, `msg_summary`, `msg_img_type`, `msg_img_url`, `msg_web_url`, `ishead`, `isread`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
	                				$paras[]= array($msgid,$id,$title,$msgType,$content,null,null,null,null,null,false,false);
              					}
              					$noticeinfo = Utils::WrapMessageNoticeinfo($title,$microName);
              					$msgContent=Utils::WrapMessage("mm-textmsg",array('item'=>$new_items),$noticeinfo);// array('code'=>'textmsg','data'=>array('item'=>$new_items),'noticeinfo'=>'');
            				}
            			}
            		}
            	}
            	break;
            default: //消息类型有误
            	return $this->responseJson(json_encode($error));
            	break;
        }
        $msgContent=json_encode($msgContent);
        try 
        {
        	$staffMgr = new \Justsy\BaseBundle\Management\Staff($conn,$conn_im,$microNumber,$this->get("logger"),$this->container);   
            $microData = $staffMgr->getInfo();
            $mic_jid = $microData["fafa_jid"];
            $msgxml = Utils::WrapMicroMessageXml($mic_jid,$msgContent,$id);
        	$im_msg_sql = "insert into im_microaccount_msg(microaccount,msg,created,us,msgid)values(?,?,now(),?,?)";
            $conn_im->ExecSQL($im_msg_sql,array(
            	(string)$mic_jid,
            	(string)$msgxml,
            	"",
            	(string)$id
            ));
            $fafa_jids=array();
            $sqls_staff=array();
            $paras_staff=array();
            $apicontroller = new \Justsy\OpenAPIBundle\Controller\ApiController();
            $apicontroller->setContainer($this->container);
            
            $serviceMgr = new \Justsy\BaseBundle\Management\Service($this->container);
            //$this->get('logger')->err("================1:".time());
            
            $fafa_jids = $serviceMgr->service_sendjid($mic_jid,true);
            //$this->get('logger')->err("================:".json_encode($fafa_jids));
            if(!empty($fafa_jids))
            {                
                  $jids=array();
                  $count = count($fafa_jids);
                  for ($i = 0; $i < $count; $i++) {
                      array_push($jids,(string)$fafa_jids[$i]);
                      if($i>0 && $i%5000==0) {
                          $re= $apicontroller->sendMsg2($microOpenid,implode(",",$jids),$msgContent,$msgType,false,"0",$id);
                          $jids=array();
                      }
                  }
                  if(!empty($jids) && count($jids) > 0) $re= $apicontroller->sendMsg2($microOpenid,implode(",",$jids),$msgContent,$msgType,false,"0",$id);
            }
            //$this->get('logger')->err("================3:".time());
            if(!empty($re['returncode']) && $re['returncode']=='0000') 
            {
              //添加发送消息数据
              if(!empty($sqls)) $conn->ExecSQLs($sqls,$paras);
              //添加接收人员
              if(!empty($sqls_staff)) $conn->ExecSQLs($sqls_staff,$paras_staff);
            }
        }
        catch (\Exception $e) 
        {
        $this->get('logger')->err($e->getMessage());
        $re= array('returncode'=>'9999','msg'=>'消息发送失败');
        }
        return $this->responseJson(json_encode($re));
	  }
	  
    private function SetElementStyle($content)
    {
        if ( strpos($content,"<img")===false)
        {
            return $content;
        }
        else
        {
            $result = Array();
            $content = explode("<img",$content);            
            for($i=0;$i< count($content);$i++)
            {
                $element = $content[$i];
                if ( strpos($element,"src")===false)
                {
                    array_push($result,$element);
                }
                else
                {
                    $element = "<img style=\"max-width:100%;\" ".$element;
                    array_push($result,$element);
                }               
            }
            return implode("",$result);
        }
    }
  
	private function getJidsFromSelUser($da, $eno, $mb_seluser)
	{
		$re = array();

		if (empty($mb_seluser)) return $re;
		if (0 == count($mb_seluser["zzjg"]) 
			&& 0 == count($mb_seluser["zjwd"])
			&& 0 == count($mb_seluser["ryfl"]) 
			&& 0 == count($mb_seluser["ygh"]))
			return $re;

		$sql = "select fafa_jid from we_staff where 1=0 ";
		$params = array(); 

		if (0 < count($mb_seluser["zzjg"]))
		{
			$wheresql = implode(",", array_map(function ($item) { return "?"; }, $mb_seluser["zzjg"]));
			$sqlx = " union select a.fafa_jid from we_staff a where a.eno=? and a.dept_id in ( $wheresql )";
			$sql .= $sqlx;
			$params[] = (string)$eno;
			foreach ($mb_seluser["zzjg"] as &$item) {
				$params[] = (string)$item;
			}
		}
		if (0 < count($mb_seluser["zjwd"]))
		{
			$sqlx = " union select b.fafa_jid
from mb_hr_7 a
inner join we_staff b on b.ldap_uid = a.zhr_pa903112
where 1=1 and a.zhr_pa903101=?";
			foreach ($mb_seluser["zjwd"] as &$item) {
				$sqly = $sqlx;
				$params[] = (string)$item["zjlb"];
				if (!empty($item["glzj"]))
				{
					$sqly .= " and a.zhr_pa903102=?";
					$params[] = (string)$item["glzj"];
				}
				if (!empty($item["ywzj"]))
				{
					$sqly .= " and a.zhr_pa903113=?";
					$params[] = (string)$item["ywzj"];
				}
				$sql .= $sqly;
			}
		}
		if (0 < count($mb_seluser["ryfl"]))
		{
			$sqlx = " union select b.fafa_jid
from mb_hr_7 a
inner join we_staff b on b.ldap_uid = a.zhr_pa903112
where 1=1 and a.zhr_pa903116=?";
			foreach ($mb_seluser["ryfl"] as &$item) {
				$sqly = $sqlx;
				$params[] = (string)$item["level1"];
				if (!empty($item["level2"]))
				{
					$sqly .= " and a.zhr_pa903117=?";
					$params[] = (string)$item["level2"];
				}
				if (!empty($item["level3"]))
				{
					$sqly .= " and a.zhr_pa903118=?";
					$params[] = (string)$item["level3"];
				}
				if (!empty($item["level4"]))
				{
					$sqly .= " and a.zhr_pa903119=?";
					$params[] = (string)$item["level4"];
				}
				$sql .= $sqly;
			}
		}
		if (0 < count($mb_seluser["ygh"]))
		{
			$wheresql = implode(",", array_map(function ($item) { return "?"; }, $mb_seluser["ygh"]));
			$sqlx = " union select a.fafa_jid
from we_staff a
where a.login_account in ( $wheresql )";
			$sql .= $sqlx;
			foreach ($mb_seluser["ygh"] as &$item) {
				$params[] = (string)strtolower($item) . "@mb.com";
			}
		}
		if (0 < count($mb_seluser["noygh"]))
		{
			$wheresql = implode(",", array_map(function ($item) { return "?"; }, $mb_seluser["noygh"]));
			$sql = "select fafa_jid 
from ( $sql ) as ttt 
where not exists (select 1 from we_staff b 
                  where b.fafa_jid=ttt.fafa_jid 
                    and b.login_account in ( $wheresql ))";
			foreach ($mb_seluser["noygh"] as &$item) {
				$params[] = (string)strtolower($item) . "@mb.com";
			}
		}

		$ds = $da->GetData("jid", $sql, $params);
		$re = array_map(function ($item) { return $item["fafa_jid"]; }, $ds["jid"]["rows"]);

		return $re;
	}
	
	//查询消息推送
	public function SearchPushAction()
	{		
		 $request = $this->getRequest();
		 $start = $request->get("date");
		 $title = $request->get("title");
		 $pageindex = $request->get("pageindex");   //当前页
		 $pagerecord = $request->get("pagerecord"); //每页显示多少行		 
		 $limit = " limit ".(($pageindex - 1) * $pagerecord).",".$pagerecord;
		 $da = $this->get("we_data_access");
		 $re = array("returncode" => ReturnCode::$SUCCESS);
		 try
		 {
			 $sql="select distinct a.id,nick_name sendname,date_format(send_datetime,'%Y-%m-%d %H:%i') send_datetime,msg_title,case send_state when 1 then '未发送' when 2 then '成功' when 3 then '失败' else '' end state,
	                  case send_type when 'text' then '文字消息'  when 'PICTURE' then '图文消息' when 'TEXTPICTURE' then '多图文消息' else null end sendtype
	          from we_micro_send_message a inner join we_micro_message b on a.id=b.send_id  inner join we_staff c on a.send_account=c.login_account where 1=1 ";
       $para = array();
		 	 $condition = "";$condition2="";
		 	 if ( !empty($start)){
		 	 	 $end = $enddate = date("Y-m-d",strtotime("$start +1 month"));
		 	 	 $condition .= " and send_datetime between ? and ?";
		 	 	 $condition2 .= $condition;
		 	 	 array_push($para,(string)$start,(string)$end);
		 	 }
		 	 if ( !empty($title)){
		 	 	 $condition .=" and msg_title like concat(?,'%')";
		 	 	 $condition2 .= " and exists (select 1 from we_micro_message b where a.id=b.send_id and msg_title like concat(?,'%'))";
		 	 	 array_push($para,(string)$title);
		 	 }
		 	 $ds = null;
		 	 if ( count($para)>0){
		 	 	 $sql .= $condition;
		 	 	 $sql .= "order by send_datetime desc ".$limit;
		 	 	 $ds = $da->GetData("table",$sql,$para);	 	 	 
		 	 }
		 	 else{
		 	 	 $sql .= "order by send_datetime desc ".$limit;
		 	 	 $ds = $da->GetData("table",$sql);
		 	 }
		 	 $re["data"] = $ds["table"]["rows"];
		 	 //计算总记录条数
		 	 if ( $pageindex==1){
		 	 	 $sql = "select count(*) total from we_micro_send_message a where a.send_account in('gg@mb.com','zf@mb.com') ";
		 	 	 if ( count($para>0)){
		 	 	 	 $sql .= $condition2;		 	 	 	 
		 	 	 	 $ds = $da->GetData("t",$sql,$para);
		 	 	 }
		 	 	 else{
		 	 	 	 $ds = $da->GetData("t",$sql,$para);
		 	 	 }
		 	 	 $re["recordcount"] = $ds["t"]["rows"][0]["total"];
		 	 }
		 	 else{
		 	 	 $re["recordcount"] = array();
		 	 }	     
	   }
	   catch (\Exception $e){	   	 
	   	 $this->get("logger")->err($e->getMessage());
	   	 $re["returncode"] = ReturnCode::$SYSERROR;
	   	 $re["data"] = array();
	   	 $re["recordcount"] = array();
	   }
	   $response = new Response(json_encode($re));
     $response->headers->set('Content-Type', 'text/json');
     return $response;
	}		
	//推送消息详细
	public function detailPushAction()
	{
		 $request = $this->getRequest();
		 $da = $this->get("we_data_access");
		 $pushid = $request->get("pushid");
		 $sqls = array();
		 $paras = array();
		 $sql = "select lower(msg_type) msg_type,msg_text,msg_content,case when msg_content=msg_summary then '' else msg_summary end msg_summary,msg_img_type,msg_img_url from we_micro_message where send_id=?";
		 $para = array((string)$pushid);
		 array_push($sqls,$sql);
		 array_push($paras,$para);		 
		 $sql="select nick_name from mb_pushobj a inner join we_staff b on a.jid=b.fafa_jid where pushid=?";
		 $para = array((string)$pushid);
		 array_push($sqls,$sql);
		 array_push($paras,$para);
		 $table = array("push","staff");
		 $data = array();
		 $success = true;
		 try
		 {
		   $ds = $da->GetDatas($table,$sqls,$paras);
		   $data = array("push"=>$ds["push"]["rows"],"staff"=>$ds["staff"]["rows"]);
		 }
		 catch(\Exception $e){
		 	 $this->get("logger").err($e->getMessage());
		   $success = false;	 
		 }
		 $result = array("success"=>$success,"data"=>$data);
		 $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;
	}
	
	//获取部门列表
  public function GetDepartmentAction() 
  {
  	$user = $this->get('security.context')->getToken()->getUser();
  	$root = "v".$user->eno;
    $sql = "select case when fafa_deptid='".$root."' then fafa_deptid else dept_id end dept_id,case when fafa_deptid='".$root."' then -10000 else parent_dept_id end parent_dept_id,dept_name,fafa_deptid from we_department where eno=?";
    $da = $this->get("we_data_access");
    $ds = $da->GetData("dept", $sql, array((string)$user->eno));
      
    $result=array();
    for($i = 0; $i<count($ds["dept"]["rows"]); $i++)
    {
    	$fafa_deptid = $ds["dept"]["rows"][$i]["fafa_deptid"];  	
    	if($fafa_deptid == $root."999" ||$fafa_deptid == $root."999888" ) continue;//把公共部门排除
    	
    	$pid = $ds["dept"]["rows"][$i]["parent_dept_id"];
    	$deptid = $ds["dept"]["rows"][$i]["dept_id"];
    	$deptname =  $ds["dept"]["rows"][$i]["dept_name"];
    	$statues = ($pid=="-10000" || $pid==$root) ? true :false;
      $result[]= array("open"=>$statues,"id"=> $deptid,"name"=> $deptname,"pId"=> $pid,"fafa_deptid" =>$fafa_deptid);
    }
    
    
    $response = new Response(json_encode($result));
		$response->headers->set('Content-Type', 'text/json');  	    	
  	return $response;
  }
}
