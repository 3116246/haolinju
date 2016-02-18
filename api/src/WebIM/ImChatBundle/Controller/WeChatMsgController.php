<?php
namespace WebIM\ImChatBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use WebIM\ImChatBundle\DataAccess\SysSeq;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class WeChatMsgController extends Controller
{  
  //写入聊天信息对外接口
	public function writemsgAction(Request $request) {
	   $tojid = $request->get("tojid");
	   $fromjid = $request->get("fromjid");
	   $ownerjid = $request->get("ownerjid");
	   $tonick = $request->get("tonick");
	   $fromnick = $request->get("fromnick");
	   $date = $request->get("date");
	   $styletext = $request->get("styletext");
	   $msgtext = $request->get("msgtext");
	   $msgtype = $request->get("msgtype");
	   try {
	     $this->insertAllot($ownerjid);//判断是否写入we_memory_allot表
  	   //达到上限时删除一条数据
  	   $recordcount = $this->userRecordcount($ownerjid);
  	   if($recordcount == 500)
  	      $this->deleteTopLimit($ownerjid);
  	   $da = $this->get('we_data_access');
  	   $id = SysSeq::GetSeqNextValue($da,"we_chatmsg","id");
  	   $this->write_msg($id,$ownerjid,$tojid,$fromjid,$tonick,$fromnick,$date,$styletext,$msgtext,$msgtype);
  	   if ($recordcount < 500){
  	     $da = $this->get('we_data_access');
	       //记录条数加1
	       $sql = "update `we_memory_allot` set `number`=`number`+1 where `jid`=?";
	       $da->ExecSQL($sql,array($ownerjid));
	     }
  	   $result = array("succeed"=> true, "id"=> $id);
  	 }catch (\Exception $e) {
  	   $this->get('logger')->err($e);
  	   $result = array("succeed"=> false, "id"=> 0);
  	 }
  	 $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($result).");" : json_encode($result));
	   $response->headers->set('Content-Type', 'text/json');
     return $response;
	}
	
	//聊天数据信息查询
	public function searchmsgAction(Request $request)	{
	   $ownerjid = $request->get('ownerjid');
	   $jid = $request->get('jid');
	   $content = $request->get('content');
	   $date = $request->get('date');
	   $msgtype = $request->get('msgtype');
	   $result = null;
	   if($this->exists_record($ownerjid)) {
	     $toplimit = $this->GetConditionData("",$ownerjid,$jid,$date,$content,$msgtype,"",3);
	     if($toplimit[0]>0){
	       $area = ceil($toplimit[0]/50);
  	     $area = (String)(($area-1)*50).",".(String)($area*50);
  	     $fileds = " id,tojid as 'to',fromnick,date_format(date,'%Y-%m-%d') 'ymd',date_format(date,'%T') time,styletext,msgtext";
  	     $orderby = " order by date asc,id asc limit ".$area;
  	     $result = $this->GetConditionData($fileds,$ownerjid,$jid,$date,$content,$msgtype,$orderby,2);
	     }
	   }
	   $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($result).");" : json_encode($result));
	   $response->headers->set('Content-Type', 'text/json');
	   return $response;
	}
	
	//写聊天信息数据记录
	public function write_msg($id,$ownerjid,$tojid,$fromjid,$tonick,$fromnick,$date,$styletext,$msgtext,$msgtype) {
	   $da = $this->get('we_data_access');
	   if (strlen($date)<10)
	     $sql = "insert into we_chatmsg(id,ownerjid,tojid,fromjid,tonick,fromnick,date,styletext,msgtext,msgtype)values(?,?,?,?,?,?,(concat(curdate(),' ',?)),?,?,?);";
	   else
	     $sql = "insert into we_chatmsg(id,ownerjid,tojid,fromjid,tonick,fromnick,date,styletext,msgtext,msgtype)values(?,?,?,?,?,?,?,?,?,?);";
	   $parameter = array($id,$ownerjid,$tojid,$fromjid,$tonick,$fromnick,$date,$styletext,$msgtext,$msgtype);
	   $da->ExecSQL($sql,$parameter);
	   return true;
	}
	
	//获得用户聊天记录条数
	public function userRecordcount($jid) {
	  $recordcount = 0;
	  $da = $this->get('we_data_access');
	  $sql="select ifnull(`number`,0) 'number' from we_memory_allot where jid=?";
	  $ds = $da->GetData("memory_allot",$sql,array((String)$jid));
	  if($ds && $ds['memory_allot']['recordcount']>0)
	     $recordcount = $ds["memory_allot"]["rows"][0]["number"];
	  return $recordcount;
  }
  	
  //导出数据记录
	public function exportAction(Request $request)	{
	  $ownerjid = $request->get("ownerjid");
	  $jid = $request->get("jid");
	  $content = $request->get("content");
	  $date = $request->get("date");
	  $msgtype = $request->get("msgtype");
	  $filename = $request->get("currentnick");
	  $txtDocument="";$htmlDocument="";$txtFilename="";$htmlFile="";
	  if($this->exists_record($ownerjid)){
	    $temp = $this->getUserCircleid($ownerjid);
	    $circleid = $temp[0];
	    $fileds = " id,tojid,tonick,fromnick,`date`,date_format(date,'%Y-%m-%d') 'ymd',date_format(date,'%T') time,styletext,msgtext ";
      $orderby = " order by date asc";
	    $data = $this->GetConditionData($fileds,$ownerjid,$jid,$date,$content,$msgtype,$orderby,1);
	    $data = $data[0];
	    if($data && $data["recordcount"] > 0){
	      //创建目录
	      $id =  $this->createDir($circleid,$temp[1]);
	      $obj = $this->documentHtmlOrText($data,$ownerjid,$filename,true);
	      $htmlDocument = $obj[0];//html文档内容	      
	      $htmlFile = $obj[1];
	      //创建html文档
	      $path = $this->createDocument($htmlFile,$htmlDocument);
	      //移动文档
	      $this->removeToMongodb($id,$temp[1],$circleid,$path,$htmlFile);
	      $obj = $this->documentHtmlOrText($data,$ownerjid,$filename,false);	      
	      $txtDocument = $obj[0];
	      $txtFilename = $obj[1];	
	    }
    }
    $response = new Response($txtDocument);
    $response->headers->set('Content-Type', "application/octet-stream");
    $response->headers->set('Accept-Ranges','bytes');
    $response->headers->set('Content-Disposition','attachment; filename='.$txtFilename);
    return $response;
	}
	
	//获得用户圈子id
	public function getUserCircleid($jid){
	   $circleid = "";
	   $user = "";
	   $da = $this->get('we_data_access');
	   $sql ="select circle_id,login_account from we_circle,we_staff where enterprise_no = eno and fafa_jid=?";
	   $ds = $da->GetData("circle",$sql,Array((String)$jid));
	   if($ds && $ds['circle']['recordcount']>0){
	     $circleid = $ds["circle"]["rows"][0]["circle_id"];
	     $user = $ds["circle"]["rows"][0]["login_account"];
	   }
	   return Array($circleid,$user);
	}
	
	
  //根据条件获得查询数据
  //isrecordcount是否返回记录条数
	public function GetConditionData($fileds,$ownerjid,$jid,$date,$content,$msgtype,$orderby,$datatype)
	{
	   $da = $this->get('we_data_access');
	   $sql ="select ".$fileds." from we_chatmsg";
	   //查询条件
	   $condition = " where ownerjid=? ";
	   $parameter = array($ownerjid);
	   
	   if(strlen($content)>0){
	      $condition = $condition." and msgText like concat('%',?,'%')";
	      array_push($parameter,$content);
	   }
	   
	   if(strlen($jid)>0){
	      $condition = $condition." and (tojid=? or fromjid=?)";
	      array_push($parameter,$jid,$jid);
	   }
	   if(strlen($msgtype)>0){
	      $condition = $condition." and msgtype=?";
	      array_push($parameter,$msgtype);
	   }
	   if(strlen($date)>0){
	      $condition = $condition." and date between ? and date_add(curdate(),interval 1 day)";
	      array_push($parameter,$date);
	   }
	   if($datatype<3){
	     $sql = $sql.$condition;
	     if (strlen($orderby)>0)
	      $sql = $sql.$orderby;
	   }
	   $result = array();
	   if($datatype==1){  //只返回数据记录
  	   $ds = $da->GetData("msg",$sql,$parameter);
  	   $result[0] = $ds["msg"];
  	 }
  	 else if ($datatype ==2){ //返回数据记录和记录数
  	   //数据记录
  	   $ds = $da->GetData("msg",$sql,$parameter);
  	   $result[0] = $ds["msg"];
  	   //记录数
  	   $sql = "select count(*) `rows` from we_chatmsg".$condition;
  	   $ds = $da->GetData("recordcount",$sql,$parameter);
  	   $result[1] = $ds["recordcount"]["rows"][0]["rows"];
  	 }
  	 else if($datatype==3){ //仅返回记录数
  	   $sql = "select count(*) `rows` from we_chatmsg".$condition;
  	   $ds = $da->GetData("recordcount",$sql,$parameter);
  	   $result[0] = $ds["recordcount"]["rows"][0]["rows"];
  	 }
  	 return $result;
	}
	
	//分页管理
	public function pagingAction(Request $request){
	  $ownerjid = $request->get('ownerjid');
	  $jid = $request->get('jid');
	  $content = $request->get('content');
	  $date = $request->get('date');
	  $pageindex = $request->get('pageindex');
	  $fileds = " id,tojid as 'to',fromnick,date_format(date,'%Y-%m-%d') 'ymd',date_format(date,'%T') time,styletext,msgtext";
	  $orderby = " order by date asc,id asc limit ".$pageindex;
	  $result = $this->GetConditionData($fileds,$ownerjid,$jid,$date,$content,"",$orderby,1);
	  $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($result).");" : json_encode($result));
	  return $response;
	}
	
	//根据id删除一条数据记录
	public function deleteByIdAction(Request $request){
	   $ownerjid = $request->get("ownerjid");
	   $id = $request->get("id");
	   $this->deleteChatMsgById($id);
	   //相关表处理
     $this->updateAllot($ownerjid);
     $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode("1").");" : json_encode("1"));  
	   $response->headers->set('Content-Type', 'text/json');
     return $response;  
	}
	
	//根据id删除一条数据记录
	public function deleteChatMsgById($id){
	   $da = $this->get('we_data_access');
	   $sql = "delete from we_chatmsg where id=?";
	   $da->ExecSQL($sql,array($id));
     return true;
	}
	
	public function insertAllot($ownerjid){
	  $da = $this->get('we_data_access');
	  $sql = "select jid from we_memory_allot where jid=?";
	  $ds = $da->GetData('allot',$sql,array((String)$ownerjid));
	  if($ds && $ds['allot']['recordcount']==0) {
	    $sql = "insert into we_memory_allot(jid,number)value(?,?)";
	    $da->ExecSQL($sql,array((String)$ownerjid,0));
	  }
	  return true;
	}
	
	//删除数据记录时相关表记录处理
	public function updateAllot($ownerjid) {
	   $da = $this->get('we_data_access');
	   //判断是否还有记录
	   $sql = "select jid from `we_memory_allot` where `number`<=1 and `jid`=?";
	   $ds = $da->GetData('allot',$sql,array($ownerjid));
	   if($ds && $ds['allot']['recordcount']==1) {
	     $sql = "delete from `we_memory_allot` where `jid`=?";
	     $da->ExecSQL($sql,array((String)$ownerjid));
	   }
	   else  {
	     //记录数量减1
	     $sql = "update we_memory_allot set number=number-1 where jid=?";
	     $da->ExecSQL($sql,array($ownerjid));
	   }
	   return true;
	}
	
	//删除上限记录
	//当用户记录数达到用户上限时删除一条数据记录
	public function deleteTopLimit($ownerjid)	{
	   $da = $this->get('we_data_access');
	   $sql = "select `id` from we_chatmsg where ownerjid=? order by id asc limit 1;";
	   $ds = $da->GetData('we_chatmsg',$sql,array((String)$ownerjid));
	   if($ds && $ds['we_chatmsg']['recordcount']==1){
	      $this->deleteChatMsgById($ds['we_chatmsg']['rows'][0]['id']);
	      $this->updateAllot($ownerjid);
	   }
	   return true;
	}
		
	//判断用户分配表是否有记录存在
	public function exists_record($ownerjid){
	  $table_exists = false;
	  $da = $this->get('we_data_access');
	  $sql = "select * from we_memory_allot where jid=?";
	  $ds = $da->GetData('allot',$sql,array((string)$ownerjid));
	  if ($ds && $ds['allot']['recordcount']>0)
	     $table_exists = true;
	  return $table_exists;
	}
	
	
	//创建目录
	public function createDir($circleid,$user){
		      $da = $this->get('we_data_access');
		      $parentid = "c".$circleid;
		      $name="聊天记录";
	        //创建目录 
    		  //判断目录是否已存在
    		  $sql="select id from we_doc_dir where parentid=? and name=? and owner=?";
    		  $rs = $da->GetData("dirs",$sql,array((string)$parentid,(string)$name,(string)$user));
    		  if($rs && count($rs["dirs"]["rows"])>0)
    		  {
    		  	return $rs["dirs"]["rows"][0]["id"]; //如果目录已存在，则直接返回目录编号
    		  }
		    	$id=(String)SysSeq::GetSeqNextValue($da,"we_doc_dir","id");	    	
		    	
		    	$sql = "insert into we_doc_dir(id,parentid,name,owner,circleid,createdate)values(?,?,?,?,?,now())";
		    	$da->ExecSQL($sql,array((String)$id,(string)$parentid,(string)$name,(string)$user,(string)$circleid)); 
		    	return $id;
	}
	
	//创建文档
	public function createDocument($filename,$document){
	   $path = "/tmp/".$filename;
		 $fp=fopen($path,"w+");
		 flock($fp,LOCK_EX);
		 if(fwrite($fp,iconv( 'utf-8','gbk', $document))) {
				flock($fp,LOCK_UN);
			  fclose($fp);
		 }
		 return $path;
	}
	
	public function removeToMongodb($id,$login_account,$circleid,$path,$filename){
	   $dm = $this->get('doctrine.odm.mongodb.document_manager'); 
		 $doc = new \WebIM\ImChatBundle\Document\WeDocument();
		 $doc->setName($filename);
		 $doc->setFile($path); 
		 $dm->persist($doc);
		 $dm->flush();
		 $fileid = $doc->getId();	
		 unlink($path);		
		 $sql ="insert into we_files(`circle_id`,`file_ext`,`file_id`,`file_name`,`post_to_group`,`up_by_staff`,`up_date`,`dir`)values(?,?,?,?,?,?,now(),?)";
     $all_params=array((String)$circleid,"html",(String)$fileid,(String)$filename,"-1",(String)$login_account,(string)$id);
     $da = $this->get('we_data_access');
     $da->ExecSQL($sql,$all_params);
     return true;
	}
	
  //转换成html或文本
	public function documentHtmlOrText($data,$ownerjid,$filename,$isHtml)
	{
	  $fileHeader="";//文件头
	  $document="";  //文档内容
	  $record = "";  //聊天记录
	  $line = "";
	  $nick = "";
	  $id = 0;
	  if($data && $data["recordcount"] > 0){
	    if($isHtml){
	      $fileHeader="<!DOCTYPE html><html xmlns='http://www.w3.org/1999/xhtml'>".
	                 "<html><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8' /><title>聊天记录</title></head>".
	                 "<body style='font-size:12px;font-family:Microsoft YaHei;'>";
	      $date=null;$date2=null;
        for($i=0;$i < $data["recordcount"];$i++) {
          $date = $data["rows"][$i]["ymd"];
          if($date != $date2){
            $line = "<div style='text-align:left'><span style='font-family:Microsoft YaHei;color:blue;font-size:12px;'>".
                      $date."</span><hr style='color:red;height:1px;'></div>";
            $date2 = $date;
          }
          else{
            $line = "";
          }
          $nick = $data["rows"][$i]["fromnick"]." ".$data["rows"][$i]["time"];
          if($data["rows"][$i]["tojid"]==$ownerjid)
            $record = "<div style='color:red;'>".$nick."</div>";
          else
            $record = "<div>".$nick."</div>";
          $record = $record."<div style='margin-left:15px;'>".$data["rows"][$i]["styletext"]."</div>";
          $document = $document.$line.$record;
          //删除数据记录
          $this->deleteChatMsgById($data["rows"][$i]["id"]);
          $this->updateAllot($ownerjid);          
        }
        $filename = $filename.date("YmdHi",time()).".html";
        $document = $fileHeader.$document."</body></html>";
	    }
	    else{
	      $fileHeader = "我的聊天记录消息\r\n\r\n=================================================\r\n聊天对象:".
                      $filename."\r\n\r\n导出时间:".date("Y-m-d H:i:s",time())."\r\n=================================================\r\n\r\n";
        for($i=0;$i < $data["recordcount"];$i++) {
          $record = $data["rows"][$i]["date"]." ".$data["rows"][$i]["fromnick"]."\r\n";
          $record = $record.$data["rows"][$i]["msgtext"]."\r\n\r\n";
          $document = $document.$record;
        }
        $filename = $filename.date("YmdHi",time()).".txt";
        $document = $fileHeader.$document;
	    }
	  }  
	  $result = array($document,$filename);
	  return $result;
	}
}