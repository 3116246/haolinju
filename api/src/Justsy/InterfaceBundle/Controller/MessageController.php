<?php

namespace Justsy\InterfaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;


class MessageController extends Controller
{
  //9.1	取得未读消息数
  public function getUnreadNumAction()
  {    
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
	  $user = $this->get('security.context')->getToken()->getUser();
    
    $da = $this->get('we_data_access'); 

    try 
    {
      $sql = "select count(*) num 
from we_message a 
where a.recver=? and ifnull(a.isread, '') in ('0', '') ";
      $params = array();
      $params[] = (string)$user->getUserName();
      
      $ds = $da->GetData("we_message", $sql, $params);
      
      $re["unreadnum"] = $ds["we_message"]["rows"][0]["num"];
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
  
  //9.2	取得未读消息
  public function getUnreadAction()
  {    
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
	  $user = $this->get('security.context')->getToken()->getUser();
    
    $da = $this->get('we_data_access'); 
    
    try 
    {
      $sql = "select a.msg_id, a.sender, a.recver, a.send_date, a.title, a.isread, a.content, 
b.nick_name sender_nickname, b.photo_path
from we_message a,we_staff b 
where b.login_account=a.sender 
  and a.recver=? and ifnull(a.isread, '') in ('0', '')
order by send_date desc, a.msg_id desc";
      $params = array();
      $params[] = (string)$user->getUserName();
          
      $ds = $da->GetData("we_message", $sql, $params);
      
			$sql="update we_message set isread='1' where recver=? and ifnull(isread, '') in ('0', '')";
      $params = array();
      $params[] = (string)$user->getUserName();
      $da->ExecSQL($sql, $params);
      
      $re["messages"] = $this->genMessage($ds["we_message"]["rows"]);
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
  
  //9.3	取得消息（每次15条）
  public function getMessageAction()
  {    
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
	  $user = $this->get('security.context')->getToken()->getUser();
    
    $da = $this->get('we_data_access'); 
    
    $last_end_id = $request->get("last_end_id");

    try 
    {
      $sql = "select a.msg_id, a.sender, a.recver, a.send_date, a.title, a.isread, a.content, 
b.nick_name sender_nickname, b.photo_path
from we_message a,we_staff b 
where b.login_account=a.sender 
  and a.recver=? ";
      $params = array();
      $params[] = (string)$user->getUserName();
      
      if (!empty($last_end_id))
      {
        $sql .= " and (0+msg_id)<?";
        $params[] = (float)$last_end_id;
      }
    
      $sql .= " order by send_date desc, a.msg_id desc ";
      $sql .= " limit 0, 15 ";

      $ds = $da->GetData("we_message", $sql, $params);
            
			$sql="update we_message set isread='1' where recver=? and ifnull(isread, '') in ('0', '')";
      $params = array();
      $params[] = (string)$user->getUserName();
      $da->ExecSQL($sql, $params);
      
      $re["messages"] = $this->genMessage($ds["we_message"]["rows"]);
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
  
  //9.4	发消息
  public function pushMessageAction()
  {    
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
	  $user = $this->get('security.context')->getToken()->getUser();
    
    $da = $this->get('we_data_access'); 
    
    $staffs = $request->get("staffs");
    $title = $request->get("title");
    $content = $request->get("content");

    try 
    {
      if (empty($staffs)) throw new \Exception("param is null");
      
      $staffs = array_map(function ($item) 
        {
          return trim($item);
        }, 
        (empty($staffs) ? array() : explode(',', $staffs)));
        
      $sqls = array();
      $all_params = array();
    
      foreach ($staffs as &$staff) 
      {
        $msg_id = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_message", "msg_id");
        
        $sql = "insert into we_message(msg_id,sender,send_date,title,content,isread,recver) values(?,?,CURRENT_TIMESTAMP(),?,?,'0',?)";
        $params = array();
        $params[] = (string)$msg_id;
        $params[] = (string)$user->getUserName();
        $params[] = (string)$title;
        $params[] = (string)$content;
        $params[] = (string)$staff;
        
        $sqls[] = $sql;
        $all_params[] = $params;
      } 
      
      $ds = $da->ExecSQLs($sqls, $all_params);
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
  
  public function genMessage(&$rows) 
  {
    $messages = array();
    foreach ($rows as &$row) 
    {
       $message = array();
       $message["msg_id"] = $row["msg_id"];
       $message["sender"] = $row["sender"];
       $message["sender_nickname"] = $row["sender_nickname"];
       $message["sender_photo"] = $row["photo_path"];
       $message["recver"] = $row["recver"];
       $message["send_date"] = $row["send_date"];
       $message["title"] = $row["title"];
       $message["content"] = $row["content"];
       $message["isread"] = $row["isread"];
       $messages[] = $message;
    } 
    
    return $messages;
  }
  
}
