<?php

namespace Justsy\InterfaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Controller\EnterpriseHomeController;
use Justsy\BaseBundle\Controller\CInputAreaController;
use Justsy\BaseBundle\Common\Utils;

class ConvInfoController extends Controller
{  
  //7.1.1 新增动态
  public function newConvTrendAction()
  {
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();    
    $da = $this->get('we_data_access');     
    $conv_content = $request->get("conv_content");
    $attachs = $request->get("attachs");
    $circle_id = $request->get("circle_id");
    $group_id = $request->get("group_id");
    $notifystaffs = $request->get("notifystaffs");
    //判断是否为公众企业
    $mode = $this->container->getParameter('deploy_mode');
    $mode = "e";
    if ( strtolower($mode)=="c" && Utils::$PUBLIC_ENO==$user->eno)
    {   
       $re["returncode"] = ReturnCode::$SYSERROR;  
       $re["msg"] = "创建/加入企业后才能发布动态！";    
    }
    else
    {
        try 
        {
          if (empty($conv_content) || empty($circle_id) ) throw new \Exception("param is null");
        
          $attachs = array_map(function ($item) 
            {
              return trim($item);
            }, 
            (empty($attachs) ? array() : explode(',', $attachs)));
            
          $notifystaffs = array_map(function ($item) 
            {
              return trim($item);
            }, 
            (empty($notifystaffs) ? array() : explode(',', $notifystaffs)));
        
          $conv_id = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_convers_list", "conv_id");
          $conv = new \Justsy\BaseBundle\Business\Conv();
          $conv->newTrend($da, $user, $conv_id, $conv_content, $circle_id, $group_id, $notifystaffs, $attachs, $request->getSession()->get('comefrom'),$this->container);
          $cInput=new CInputAreaController();
          $cInput->setContainer($this->container);
          $cInput->sendPresence($conv_id,$da,$circle_id,$group_id,"trend");
          $re["conv"] = $this->getTrend($da, $user, $conv_id, "");
        } 
        catch (\Exception $e) 
        {
          $re["returncode"] = ReturnCode::$SYSERROR;
          $this->get('logger')->err($e);
        }
    }
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  
  //7.1.2 新增提问
  public function newConvAskAction() 
  {
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();
    
    $da = $this->get('we_data_access'); 
    
    $conv_content = $request->get("conv_content");
    $attachs = $request->get("attachs");
    $circle_id = $request->get("circle_id");
    $group_id = $request->get("group_id");
    $notifystaffs = $request->get("notifystaffs");

    try 
    {
      if (empty($conv_content) || empty($circle_id) || empty($group_id)) throw new \Exception("param is null");
    
      $attachs = array_map(function ($item) 
        {
          return trim($item);
        }, 
        (empty($attachs) ? array() : explode(',', $attachs)));
        
      $notifystaffs = array_map(function ($item) 
        {
          return trim($item);
        }, 
        (empty($notifystaffs) ? array() : explode(',', $notifystaffs)));
    
      $conv_id = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_convers_list", "conv_id");
      
      $conv = new \Justsy\BaseBundle\Business\Conv();
      $conv->newAsk($da, $user, $conv_id, $conv_content, $circle_id, $group_id, $notifystaffs, $attachs, $request->getSession()->get('comefrom'),$this->container);
      $cInput=new CInputAreaController();
      $cInput->setContainer($this->container);
      $cInput->sendPresence($conv_id,$da,$circle_id,$group_id,"ask");
      $re["conv"] = $this->getAsk($da, $user, $conv_id, "");
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
  
  //7.1.3 新增活动
  public function newConvTogetherAction() 
  {
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();
    
    $da = $this->get('we_data_access'); 
    
    $conv_content = $request->get("title");
    $will_date = $request->get("will_date");
    $will_dur = $request->get("will_dur");
    $will_addr = $request->get("will_addr");
    $together_desc = $request->get("together_desc");
    $attachs = $request->get("attachs");
    $circle_id = $request->get("circle_id");
    $group_id = $request->get("group_id");
    $notifystaffs = $request->get("notifystaffs");

    try 
    {
      if (empty($conv_content) || empty($circle_id) || empty($group_id)) throw new \Exception("param is null");
    
      $attachs = array_map(function ($item) 
        {
          return trim($item);
        }, 
        (empty($attachs) ? array() : explode(',', $attachs)));
        
      $notifystaffs = array_map(function ($item) 
        {
          return trim($item);
        }, 
        (empty($notifystaffs) ? array() : explode(',', $notifystaffs)));
    
      $conv_id = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_convers_list", "conv_id");
      
      $conv = new \Justsy\BaseBundle\Business\Conv();
      $conv->newTogether($da, $user, $conv_id, $conv_content, $will_date, $will_dur, $will_addr, $together_desc, $circle_id, $group_id, $notifystaffs, $attachs, "", $request->getSession()->get('comefrom'),$this->container);
      
      $cInput=new CInputAreaController();
      $cInput->setContainer($this->container);
      $cInput->sendPresence($conv_id,$da,$circle_id,$group_id,"together");
      $re["conv"] = $this->getTogether($da, $user, $conv_id, "");
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
  
  //7.1.4 新增投票
  public function newConvVoteAction() 
  {
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();
    
    $da = $this->get('we_data_access'); 
    
    $conv_content = $request->get("conv_content");
    $is_multi = $request->get("is_multi");
    $finishdate = $request->get("finishdate");
    $optionvalues = $request->get("optionvalues");
    $attachs = $request->get("attachs");
    $circle_id = $request->get("circle_id");
    $group_id = $request->get("group_id");
    $notifystaffs = $request->get("notifystaffs");

    try 
    {
      if (empty($conv_content) || empty($circle_id) || empty($group_id) || empty($optionvalues)) throw new \Exception("param is null");
    
      $attachs = array_map(function ($item) 
        {
          return trim($item);
        }, 
        (empty($attachs) ? array() : explode(',', $attachs)));
        
      $notifystaffs = array_map(function ($item) 
        {
          return trim($item);
        }, 
        (empty($notifystaffs) ? array() : explode(',', $notifystaffs)));
        
      $optionvalues = array_map(function ($item) 
        {
          return trim($item);
        }, 
        (empty($optionvalues) ? array() : explode(',', $optionvalues)));
      
      $is_multi = ($is_multi == "1"? "1" : "0");
    
      $conv_id = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_convers_list", "conv_id");
      
      $conv = new \Justsy\BaseBundle\Business\Conv();
      $conv->newVote($da, $user, $conv_id, $conv_content, $is_multi, $finishdate, $optionvalues, $circle_id, $group_id, $notifystaffs, $attachs, $request->getSession()->get('comefrom'),$this->container);
      
      $cInput=new CInputAreaController();
      $cInput->setContainer($this->container);
      $cInput->sendPresence($conv_id,$da,$circle_id,$group_id,"vote");
      $re["conv"] = $this->getVote($da, $user, $conv_id, "");
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
  
  //7.2.1 取得15条信息
  public function getConvsAction()
  {    
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();
    $login_account = $user->getUserName();
    $da = $this->get('we_data_access');
    $circle_id = $request->get("circle_id");
    $group_id = $request->get("group_id");
    $last_end_id = $request->get("last_end_id");
   try 
   {
      if (empty($circle_id)) throw new \Exception("param is null");
      $sql = "select a.conv_root_id from we_convers_list a where a.conv_id=a.conv_root_id and a.post_to_circle=?";
      $params = array();
      array_push($params,$circle_id);
      if ($circle_id == "9999")
      {
        //从im库中查询好友
        $staffmgr = new \Justsy\BaseBundle\Management\Staff($da,$this->get('we_data_access_im'), $user,$this->get("logger"),$this->container);
        $getfriendList = $staffmgr->getFriendLoginAccountList("1");        
        if($getfriendList && count($getfriendList)>0)
        {
            $sql .= " and a.login_account in ('".implode("','",$getfriendList)."','".$login_account."')";
        }
        else
        {
            $sql .= " and a.login_account=? ";
            array_push($params,(string)$login_account);
        }  
      }
      if (!empty($group_id))
      {
        $sql .= " and a.post_to_group = ? ";
        array_push($params,(string)$group_id);
      }
      else
      {
        $sql .= " and (a.post_to_group in ('ALL', case when a.login_account=? then 'PRIVATE' else '' end)
                    or exists(select 1 from we_convers_notify wcn where wcn.conv_id=a.conv_id and wcn.cc_login_account=?)) ";
        array_push($params,(string)$user->getUserName());
        array_push($params,(string)$user->getUserName());
      }
      
      if (!empty($last_end_id))
      {
        $sql .= " and (0+conv_root_id)<? ";
        array_push($params,(float)$last_end_id);
      }
      $sql .=" and a.conv_type_id<>'06'";
      $sql .= " order by (0+a.conv_id) desc";
      $sql .= " limit 0, 15 ";
      $da = $this->get('we_data_access'); 
      $ds = $da->GetData("we_convers_list", $sql, $params);      
      $conv_root_ids = array_map(function ($row) {
          return $row["conv_root_id"];
        }, 
        $ds["we_convers_list"]["rows"]);
      $re["convs"] = $this->getConvAction($conv_root_ids);
      if (empty($last_end_id) && count($conv_root_ids) > 0)
      {
        //更新用户最后读的信息ID
        $conv = new \Justsy\BaseBundle\Business\Conv();
        if (empty($group_id))
          $conv->updateLastReadID_Circle($da, $user, $circle_id, $conv_root_ids[0]);
        else
          $conv->updateLastReadID_Group($da, $user, $group_id, $conv_root_ids[0]);
      }
   }
   catch (\Exception $e) 
   {      
     $re["returncode"] = ReturnCode::$SYSERROR;
     $this->get('logger')->err($e->getMessage());
   }
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  
  //7.2.2 取最新未读信息数
  public function getUnreadConvNumAction()
  {    
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();
    $da = $this->get('we_data_access');     
    $circle_id = $request->get("circle_id");
    $group_id = $request->get("group_id");
    $max_id = $request->get("max_id");
    try
    {
      if (empty($circle_id)) throw new \Exception("param is null");
      $sql = "select a.conv_root_id from we_convers_list a
              where a.conv_id=a.conv_root_id and a.post_to_circle=?";
      $params = array();
      $params[] = (string)$circle_id;
      
      if ($circle_id == "9999") 
      {
          //从im库中查询好友
          $staffmgr = new \Justsy\BaseBundle\Management\Staff($da,$this->get('we_data_access_im'), $user,$this->get("logger"),$this->container);
          $getfriendList = $staffmgr->getFriendLoginAccountList("1");
          if($getfriendList && count($getfriendList)>0)
          {
            $sql .= " and a.login_account in ('".implode("','",$getfriendList)."','".$user->getUserName()."')";
          }
          else
          {
            $sql .= " and a.login_account=?";
            $params[] = (string)$user->getUserName();
          }
      }
      if (!empty($group_id))
      {
        $sql .= " and a.post_to_group = ? ";
        $params[] = (string)$group_id;
      }
      else
      {
        $sql .= " and (a.post_to_group in ('ALL', case when a.login_account=? then 'PRIVATE' else '' end)
                    or exists(select 1 from we_convers_notify wcn where wcn.conv_id=a.conv_id and wcn.cc_login_account=?)) ";
        $params[] = (string)$user->getUserName();
        $params[] = (string)$user->getUserName();
      }
      
      $sql .= " and a.conv_type_id<>'06' ";
      $sql .= " and (0+conv_root_id)>? and 0<>?";
      $params[] = (float)$max_id;
      $params[] = (float)$max_id;
    
      $sql = "select count(*) c from ($sql) as _ttt_";

      $da = $this->get('we_data_access');
      $ds = $da->GetData("we_convers_list", $sql, $params);
      
      $re["num"] = $ds["we_convers_list"]["rows"][0]["c"];
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
  
  //7.2.3 取得最新未读信息
  public function getUnreadConvAction()
  {    
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();
    
    $da = $this->get('we_data_access'); 
    
    $circle_id = $request->get("circle_id");
    $group_id = $request->get("group_id");
    $max_id = $request->get("max_id");
    $last_end_id = $request->get("last_end_id");

    try 
    {
      if (empty($circle_id)) throw new \Exception("param is null");
      
      $sql = "select a.conv_root_id 
from we_convers_list a
where a.conv_id=a.conv_root_id
  and a.post_to_circle=?";
      $params = array();
      $params[] = (string)$circle_id;
      
      if ($circle_id == "9999") 
      {
          //从im库中查询好友
          $staffmgr = new \Justsy\BaseBundle\Management\Staff($da,$this->get('we_data_access_im'), $user,$this->get("logger"),$this->container);
          $getfriendList = $staffmgr->getFriendLoginAccountList("1");
          if($getfriendList && count($getfriendList)>0)
          {
            $sql .= " and a.login_account in ('".implode("','",$getfriendList)."','".$user->getUserName()."')";
          }
          else
          {
            $sql .= " and a.login_account=?";
            $params[] = (string)$user->getUserName();
          }
      }
      if (!empty($group_id))
      {
        $sql .= " and a.post_to_group = ? ";
        $params[] = (string)$group_id;
      }
      else
      {
        $sql .= " and (a.post_to_group in ('ALL', case when a.login_account=? then 'PRIVATE' else '' end)
                    or exists(select 1 from we_convers_notify wcn where wcn.conv_id=a.conv_id and wcn.cc_login_account=?)) ";
        $params[] = (string)$user->getUserName();
        $params[] = (string)$user->getUserName();
      }
      
      $sql .= " and (0+conv_root_id)>? and 0<>?";
      $params[] = (float)$max_id;
      $params[] = (float)$max_id;
    
      if (!empty($last_end_id))
      {
        $sql .= " and (0+conv_root_id)<? ";
        $params[] = (float)$last_end_id;
      }
      
      $sql .= " and a.conv_type_id<>'06' ";
      $sql .= " order by (0+a.conv_id) desc";
      $sql .= " limit 0, 15 ";

      $da = $this->get('we_data_access');
      $ds = $da->GetData("we_convers_list", $sql, $params);

      $conv_root_ids = array_map(function ($row) {
          return $row["conv_root_id"];
        }, 
        $ds["we_convers_list"]["rows"]);
      
      $re["convs"] = $this->getConvAction($conv_root_ids);
      
      if (count($conv_root_ids) > 0 && empty($last_end_id))
      {        
        //更新用户最后读的信息ID
        $conv = new \Justsy\BaseBundle\Business\Conv();
        if (empty($group_id))
          $conv->updateLastReadID_Circle($da, $user, $circle_id, $conv_root_ids[0]);
        else
          $conv->updateLastReadID_Group($da, $user, $group_id, $conv_root_ids[0]);
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
  
  //8.2.4 取得某条信息
  public function getOneConvAction()
  {    
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();
    
    $da = $this->get('we_data_access'); 
    
    $conv_id = $request->get("conv_id");

    try 
    {
      if (empty($conv_id)) throw new \Exception("param is null");
            
      $sqls = array();
      $all_params = array();

      //判断是否有权限查看
      $sql = "select count(*) c
from we_convers_list a
where a.conv_id=?
  and exists(select 1 from dual where a.login_account=? 
       union select 1 from we_staff_atten wsa where wsa.login_account=? and wsa.atten_type='01' and wsa.atten_id=a.login_account and a.post_to_circle='9999'
       union select 1 from we_circle_staff b 
             where b.circle_id=a.post_to_circle 
               and b.login_account=?
               and (a.post_to_group in (select 'ALL' from dual 
                                       union select case when a.login_account=b.login_account then 'PRIVATE' else '' end from dual
                                       union select c.group_id from we_group_staff c where c.login_account=b.login_account)
                 or exists(select 1 from we_convers_notify wcn where wcn.conv_id=a.conv_id and wcn.cc_login_account=b.login_account)))";
      $params = array();
      $params[] = (string) $conv_id;
      $params[] = (string) $user->getUserName();
      $params[] = (string) $user->getUserName();
      $params[] = (string) $user->getUserName();

      $sqls[] = $sql;
      $all_params[] = $params;

      $ds = $da->GetDatas(array("CanView"), $sqls, $all_params);
      if ($ds["CanView"]["rows"][0]["c"] == 0)
      {
        $re["returncode"] = ReturnCode::$NOTAUTHORIZED;        
      }
      else
      {
        //删除评论我的通知
        $da->ExecSQL("delete from we_notify 
where notify_type='04' and notify_staff=? 
  and exists(select 1 from we_convers_list where we_convers_list.conv_id=we_notify.msg_id and we_convers_list.conv_root_id=?)", 
          array((string) $user->getUserName(), (string) $conv_id));

        $convs = $this->getConvAction(array($conv_id));
        if (count($convs) > 0)
        {
          $re["conv"] = $convs[0];
        }
        else
        {
          $re["returncode"] = ReturnCode::$NOTAUTHORIZED;    
        }
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
  
  //7.2.4.1 删除动态
  public function delConvTrendAction()
  {    
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();    
    $da = $this->get('we_data_access');     
    $conv_id = $request->get("conv_id");
    try 
    {
      if (empty($conv_id)) throw new \Exception("param is null");      
      $conv = new \Justsy\BaseBundle\Business\Conv();      
      //不是自己的不能删除
      if ($conv->checkIsOwenConv($da, $conv_id, $user->getUserName()))
      {
          $result = $conv->delConvByRootID($da, $conv_id);
          if ( $result )
          {
              //出席接收人员
              $staffMgr = new \Justsy\BaseBundle\Management\Staff($da,$this->get("we_data_access_im"),$user,$this->get("logger"),$this->container);
              $send_jid = $staffMgr->getFriendJidList($conv_id);
              if($send_jid && count($send_jid)>0)
              {
                  Utils::sendImPresence($user->fafa_jid,implode(",", $send_jid),"del_dynamic",$conv_id,$this->container,"","",false,Utils::$systemmessage_code);       
              }              
              //删除动态人员范围表(后台广播)
              $announcerMgr = new \Justsy\BaseBundle\Management\Announcer($this->container);
              $announcerMgr->delConvers($conv_id);
              $re["returncode"] = ReturnCode::$SUCCESS;
          }
          else
          {
             $re["returncode"] = ReturnCode::$NOTAUTHORIZED;
          }        
          $re["returncode"] = ReturnCode::$SUCCESS;
      }
      else
      {
        $re["returncode"] = ReturnCode::$NOTAUTHORIZED;
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
  
  //7.2.4.2 删除提问
  public function delConvAskAction()
  {    
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();
    
    $da = $this->get('we_data_access'); 
    
    $conv_id = $request->get("conv_id");

    try 
    {
      if (empty($conv_id)) throw new \Exception("param is null");
      
      $conv = new \Justsy\BaseBundle\Business\Conv();
      
      //不是自己的不能删除
      if ($conv->checkIsOwenConv($da, $conv_id, $user->getUserName()))
      {
        $conv->delConvByRootID($da, $conv_id);
        $re["returncode"] = ReturnCode::$SUCCESS;
      }
      else
      {
        $re["returncode"] = ReturnCode::$NOTAUTHORIZED;
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
  
  //7.2.4.3 删除活动
  public function delConvTogetherAction()
  {    
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();
    
    $da = $this->get('we_data_access'); 
    
    $conv_id = $request->get("conv_id");

    try 
    {
      if (empty($conv_id)) throw new \Exception("param is null");
      
      $conv = new \Justsy\BaseBundle\Business\Conv();
      
      //不是自己的不能删除
      if ($conv->checkIsOwenConv($da, $conv_id, $user->getUserName()))
      {
        $conv->delTogether($da, $conv_id);
        $re["returncode"] = ReturnCode::$SUCCESS;
      }
      else
      {
        $re["returncode"] = ReturnCode::$NOTAUTHORIZED;
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
  
  //7.2.4.4 删除投票
  public function delConvVoteAction()
  {    
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();
    
    $da = $this->get('we_data_access'); 
    
    $conv_id = $request->get("conv_id");

    try 
    {
      if (empty($conv_id)) throw new \Exception("param is null");
      
      $conv = new \Justsy\BaseBundle\Business\Conv();
      
      //不是自己的不能删除
      if ($conv->checkIsOwenConv($da, $conv_id, $user->getUserName()))
      {
        $conv->delVote($da, $conv_id);
        $re["returncode"] = ReturnCode::$SUCCESS;
      }
      else
      {
        $re["returncode"] = ReturnCode::$NOTAUTHORIZED;
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
  
  //8.2.4.5 删除回复
  public function delConvReplyAction()
  {    
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();
    
    $da = $this->get('we_data_access'); 
    
    $conv_id = $request->get("conv_id");

    try 
    {
      if (empty($conv_id)) throw new \Exception("param is null");
      
      $conv = new \Justsy\BaseBundle\Business\Conv();
      
      //不是自己的不能删除
      if ($conv->checkIsOwenConv($da, $conv_id, $user->getUserName()))
      {
        $conv->delReplyByID($da, $conv_id);
        $re["returncode"] = ReturnCode::$SUCCESS;
      }
      else
      {
        $re["returncode"] = ReturnCode::$NOTAUTHORIZED;
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
  
  //7.2.5 赞
  public function likeConvAction()
  {    
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();
    
    $da = $this->get('we_data_access'); 
    
    $conv_id = $request->get("conv_id");

    try 
    {
      if (empty($conv_id)) throw new \Exception("param is null");
      
      $conv = new \Justsy\BaseBundle\Business\Conv();
      $conv->likeConv($da, $user, $conv_id);
      $re["like_staff"] = $user->getUserName();
      $re["nick_name"] = $user->nick_name;
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
  
  //7.2.6 取消赞
  public function unlikeConvAction()
  {    
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();
    
    $da = $this->get('we_data_access'); 
    
    $conv_id = $request->get("conv_id");

    try 
    {
      if (empty($conv_id)) throw new \Exception("param is null");
      
      $conv = new \Justsy\BaseBundle\Business\Conv();
      $conv->unlikeConv($da, $user, $conv_id);
      $re["like_staff"] = $user->getUserName();
      $re["nick_name"] = $user->nick_name;
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
  
  //7.2.7 评论/回复
  public function replyConvAction()
  {  
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();    
    $da = $this->get('we_data_access');    
    $conv_root_id = $request->get("conv_id");
    $replayvalue = $request->get("replayvalue");
    $reply_to = $request->get("reply_to");
    $reply_to_name = $request->get("reply_to_name");
    $attachs = $request->get("attachs");
    try 
    {
      if (empty($conv_root_id) || empty($replayvalue)) throw new \Exception("param is null");
      
      $attachs = array_map(function ($item) 
        {
          return trim($item);
        }, 
        (empty($attachs) ? array() : explode(',', $attachs)));

      $conv_id = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_convers_list", "conv_id");      
      $conv = new \Justsy\BaseBundle\Business\Conv();
      $conv->replyConv($da, $user, $conv_root_id, $conv_id, $replayvalue, $reply_to, $request->getSession()->get('comefrom'), $this->container, $attachs);
      
      $reply = array();
      $reply["reply_id"] = $conv_id;
      $reply["reply_staff"] = $user->getUserName();
      
      $reply_staff_obj = array();
      $reply_staff_obj["login_account"] = $user->getUserName();
      $reply_staff_obj["nick_name"] = $user->nick_name;
      $reply_staff_obj["photo_path"] = $user->photo_path;
      $reply_staff_obj["photo_path_small"] = $user->photo_path_small;
      $reply_staff_obj["photo_path_big"] = $user->photo_path_big;
      $reply["reply_staff_obj"] = $reply_staff_obj;      
      $reply["reply_date"] = date('Y-m-d H:i:00');
      //处理返回内容
      $face = $this->get('templating.helper.assets')->geturl('bundles/fafatimewebase/images/face/');
      $reply["reply_content"] = $conv->replaceContent($replayvalue, $face);
      $reply["reply_to"] = $reply_to;
      $reply["reply_to_nickname"] = $reply_to_name;
      $reply["likes"] = array();
      
      $sql = "select a.conv_id, a.attach_id, b.file_name, b.file_ext, b.up_by_staff, b.up_date
        from we_convers_attach a, we_files b
        where a.attach_id=b.file_id
        and a.attach_type='0' and a.conv_id = ?";
      $params = array(); 
      $params[] = $conv_id;
      $ds1 = $da->GetData("we_convers_attach_reply", $sql, $params);
      $reply["attachs"] = $this->genAttachs($ds1["we_convers_attach_reply"]["rows"]);

      $re["reply"] = $reply;
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
  

  //7.2.7 业务系统中的业务数据评论/回复
  public function appReplyConvAction() 
  {  
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();

    try 
    {
      $user = $this->get('security.context')->getToken()->getUser();
      
      $da = $this->get('we_data_access'); 
      
      $app_id = $request->get("appid");
      $conv_type = $request->get("conv_type");
      $conv_root_id = $request->get("conv_id");
      $replayvalue = $request->get("replayvalue");
      $reply_to = $request->get("reply_to");
    

      if (empty($app_id) || empty($conv_root_id) || empty($replayvalue)) throw new \Exception("param is null");
      
      $resourceid= $app_id.'_'.$conv_root_id;
      $conv_id = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_doc_convers", "conv_id");
      
      $sql="insert into we_doc_convers (conv_id,resourceid,resourcetype,conv_account,conv_to,conv_time,content) values(?,?,?,?,?,now(),?)";
      $params=array();
      array_push($params,$conv_id);
      array_push($params,$resourceid);
      array_push($params,$conv_type);
      array_push($params,$user->getUserName());
      array_push($params,$reply_to);
      array_push($params,$replayvalue);
      
      $da->ExecSQL($sql,$params);
    } 
    catch (\Exception $e) 
    {
      $re["returncode"] = ReturnCode::$SYSERROR;
      $re["message"] = $e->getMessage();
      $this->get('logger')->err($e);
    }
    
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  public function getAppReplyConvAction()
  {
  
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();

    try 
    {
      $user = $this->get('security.context')->getToken()->getUser();
      
      $da = $this->get('we_data_access'); 
      
      $app_id = $request->get("appid");
      
      $conv_root_id = $request->get("conv_id");
    
      $pageindex = $request->get("pageindex");
      $pagesize = 15;
      if (empty($app_id) || empty($conv_root_id) ) throw new \Exception("param is null");
      if(empty($pageindex)) $pageindex=1;
      $pagestart = ($pageindex-1)*$pagesize;

      $resourceid= $app_id.'_'.$conv_root_id;
      
      $sql="select a.*,b.nick_name,concat('".$this->container->getParameter('FILE_WEBSERVER_URL')."',b.photo_path_big) photo from we_doc_convers  a left join we_staff b on a.conv_account=b.login_account where resourceid=? order by a.conv_id desc limit $pagestart,$pagesize";
      $params=array();
      array_push($params,$resourceid);
      
      $result = $da->GetData("t",$sql,$params);
      $re["list"] = $result["t"]["rows"];
    } 
    catch (\Exception $e) 
    {
      $re["returncode"] = ReturnCode::$SYSERROR;
      $re["message"] = $e->getMessage();
      $this->get('logger')->err($e);
    }
    
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }

   //7.2.7 业务系统中的业务数据收藏
  public function appCollectConvAction() 
  {  
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();

    try 
    {
      $user = $this->get('security.context')->getToken()->getUser();
      
      $da = $this->get('we_data_access'); 
      
      $app_id = $request->get("appid");
      $conv_type = $request->get("conv_type");
      $sourcetype = $request->get("sourcetype");
      $replayvalue = $request->get("replayvalue");
      $sourceid = $request->get("sourceid");
    

      if (empty($app_id) || empty($conv_root_id) || empty($replayvalue)) throw new \Exception("param is null");
      
      
      $sql="insert into we_collect (appid,collect_content,collect_date,collect_person,collect_type,eno,sourceid,sourcetype) values".
           "(?,?,now(),?,?,?,?,?)";
      $params=array();
      array_push($params,$app_id);
      array_push($params,$replayvalue);
      array_push($params,$user->getUserName());
      array_push($params,$conv_type);
      array_push($params,$user->eno);
      array_push($params,$sourceid);
      array_push($params,$sourcetype);
      $da->ExecSQL($sql,$params);
    } 
    catch (\Exception $e) 
    {
      $re["returncode"] = ReturnCode::$SYSERROR;
      $re["message"] = $e->getMessage();
      $this->get('logger')->err($e);
    }
    
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  public function getAppCollectConvAction()
  {
  
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();

    try 
    {
      $user = $this->get('security.context')->getToken()->getUser();
      
      $da = $this->get('we_data_access'); 
      
      $app_id = $request->get("appid");

    
      $pageindex = $request->get("pageindex");
      $pagesize = 15;
      if (empty($app_id) ) throw new \Exception("param is null");
      if(empty($pageindex)) $pageindex=1;
      $pagestart = ($pageindex-1)*$pagesize;

      
      $sql="select a.*,b.nick_name,concat('".$this->container->getParameter('FILE_WEBSERVER_URL')."',b.photo_path_big) photo from we_collect  a left join we_staff b on a.collect_person=b.login_account where a.appid=? and a.collect_person=? order by a.id desc limit $pagestart,$pagesize";
      $params=array();
      array_push($params,$app_id);
      array_push($params,$user->getUserName());
      $result = $da->GetData("t",$sql,$params);
      $re["list"] = $result["t"]["rows"];
    } 
    catch (\Exception $e) 
    {
      $re["returncode"] = ReturnCode::$SYSERROR;
      $re["message"] = $e->getMessage();
      $this->get('logger')->err($e);
    }
    
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }

  public function cancelAppCollectConvAction()
  {
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();

    try 
    {
      $user = $this->get('security.context')->getToken()->getUser();
      
      $da = $this->get('we_data_access'); 
      
      $app_id = $request->get("appid");

      $collectid = $request->get("collectid");

      if (empty($collectid) ) throw new \Exception("param is null");
      $sql="delete from we_collect  a  where a.collect_person=? and a.appid=? and a.id=? ";
      $params=array();
      array_push($params,$user->getUserName());
      array_push($params,$app_id);
      array_push($params,$collectid);
      $da->ExecSQL($sql,$params);
    } 
    catch (\Exception $e) 
    {
      $re["returncode"] = ReturnCode::$SYSERROR;
      $re["message"] = $e->getMessage();
      $this->get('logger')->err($e);
    }
    
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;  	 
  }


  //7.2.8 投票
  public function voteAction() 
  {  
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();
    
    $da = $this->get('we_data_access'); 
    
    $conv_root_id = $request->get("conv_id");
    $is_multi = $request->get("is_multi");
    $optionids = $request->get("optionids");

    try 
    {
      if (empty($conv_root_id) || $optionids=="" ) throw new \Exception("param is null");
            
      $conv = new \Justsy\BaseBundle\Business\Conv();
      
      if ($is_multi=="1")
      {
          $optionids = array_map(function ($item) 
          {
            return trim($item);
          },
          ( $optionids=="" ? array() : explode(',', $optionids)));
      }
      //查询是否已投票
      if ($conv->checkIsVoted($da, $conv_root_id, $user->getUserName())
        || ($is_multi=="1" && (!is_array($optionids) || count($optionids) == 0)))
      {        
      }
      else
      {        
        $conv->vote($da, $user, $conv_root_id, $is_multi, $optionids);
      }
      $re["conv"] = $this->getVote($da, $user, $conv_root_id, "");
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
  
  //7.2.9 取得转发权限
  public function getConvLimitAction() 
  {  
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();
    
    $da = $this->get('we_data_access'); 
    
    $circle_id = $request->get("circle_id");

    try 
    {
      if (empty($circle_id)) throw new \Exception("param is null");
      
      $conv = new \Justsy\BaseBundle\Business\Conv();
      $allowcopy = $conv->getCircleLimit($da, $circle_id);
      
      $re["allow_copy"] = $allowcopy["allow_copy"];
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
  
  //7.2.10  转发
  public function copyAction() 
  {  
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();
    
    $da = $this->get('we_data_access'); 
    
    $conv_content = $request->get("conv_content");
    $circle_id = $request->get("circle_id");
    $group_id = $request->get("group_id");
    $copy_id = $request->get("copy_id");
    $copy_last_id = $request->get("copy_last_id");

    try 
    {
      if (empty($conv_content) || empty($circle_id) || empty($group_id) || empty($copy_id)) throw new \Exception("param is null");
      
      $conv = new \Justsy\BaseBundle\Business\Conv();
      //检查该会话是否允许转发
      $allowcopy = $conv->getConvLimit($da, $copy_id, $circle_id);
      if ($allowcopy["allow_copy"] == "1")
      {
        $re["returncode"] = ReturnCode::$NOTAUTHORIZED;
      }
      else
      {
        $conv_id = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_convers_list", "conv_id");
        $conv->copyConv($da, $user, $conv_id, $conv_content, $circle_id, $group_id, $copy_id, $copy_last_id, $request->getSession()->get('comefrom'));  
        $convobj= $this->getConvAction($conv_id);
        $re["conv"] = $convobj[0];
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
  
  //7.2.11  收藏
  public function attenConvAction() 
  {  
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();
    
    $da = $this->get('we_data_access'); 
    
    $conv_id = $request->get("conv_id");

    try 
    {
      if (empty($conv_id)) throw new \Exception("param is null");
      
      $conv = new \Justsy\BaseBundle\Business\Conv();
     
      //检查是否有权限
      if ($conv->checkCanViewConv($da, $conv_id, $user->getUserName()))
      {
        $ds = $conv->attenConv($da, $user, $conv_id);
      }
      else
      {
        $re["returncode"] = ReturnCode::$NOTAUTHORIZED;
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
  
  //7.2.12  取消收藏
  public function unattenConvAction() 
  {  
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();
    
    $da = $this->get('we_data_access'); 
    
    $conv_id = $request->get("conv_id");

    try 
    {
      if (empty($conv_id)) throw new \Exception("param is null");
      
      $conv = new \Justsy\BaseBundle\Business\Conv();
     
      $ds = $conv->unattenConv($da, $user, $conv_id);
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
  
  //7.2.13  加入活动
  public function joinTogetherAction() 
  {  
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();
    
    $da = $this->get('we_data_access'); 
    
    $conv_id = $request->get("conv_id");

    try 
    {
      if (empty($conv_id)) throw new \Exception("param is null");
      
      $conv = new \Justsy\BaseBundle\Business\Conv();
     
      $ds = $conv->joinTogether($da, $user, $conv_id);
      
      $re["join_staff"] = $user->getUserName();
      $re["nick_name"] = $user->nick_name;
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
  
  //7.2.14  退出活动
  public function unjoinTogetherAction() 
  {  
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();
    
    $da = $this->get('we_data_access'); 
    
    $conv_id = $request->get("conv_id");

    try 
    {
      if (empty($conv_id)) throw new \Exception("param is null");
      
      $conv = new \Justsy\BaseBundle\Business\Conv();
     
      $ds = $conv->unjoinTogether($da, $user, $conv_id);
      
      $re["join_staff"] = $user->getUserName();
      $re["nick_name"] = $user->nick_name;
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
  
    public function getConvAction($conv_root_ids)
    {        
        $re = array();
        $user = $this->get('security.context')->getToken()->getUser();
        //取出每个根会话
        $sqlwherein = array();
        $params = array();
        $sqlwherein[] = '?';
        $params[] = '###';   
        for ($i=0; $i<count($conv_root_ids); $i++)
        {
            $sqlwherein[] = '?';
            $params[] = (string)$conv_root_ids[$i];
        }    
        $sqlwhereinstr = join(',', $sqlwherein);
        $sql = "select a.conv_id, a.conv_type_id, b.template_controller from we_convers_list a, we_conv_template b
                where a.conv_type_id=b.conv_type_id  and a.conv_id in ($sqlwhereinstr) order by a.post_date desc,(0+a.conv_id) desc";
        $da = $this->get('we_data_access');
        $ds = $da->GetData("we_convers_list", $sql, $params);
        foreach ($ds["we_convers_list"]["rows"] as &$row) 
        {   
            if ($row["conv_type_id"] == "00")
            {
                $re[] = $this->getTrend($da, $user, $row["conv_id"], "");
            }
            else if ($row["conv_type_id"] == "01")
            {
                $re[] = $this->getAsk($da, $user, $row["conv_id"], "");
            }
            else if ($row["conv_type_id"] == "02")
            {
                $re[] = $this->getTogether($da, $user, $row["conv_id"], "");
            }
            else if ($row["conv_type_id"] == "03")
            {
                $re[] = $this->getVote($da, $user, $row["conv_id"], "");
            }
            else if ($row["conv_type_id"] == "04")
            {
            }
            else if ($row["conv_type_id"] == "05")
            {
                $re[] = $this->getCopy($da, $user, $row["conv_id"], "");
            }
            else if ($row["conv_type_id"] == "98")
            {
                $re[] = $this->getShareTrend($da, $user, $row["conv_id"], "");
            }
        }
        return $re;
    }
  
  private function genStaff(&$row) 
  {
    $re = array();
    $re["login_account"] = $row["login_account"];
    $re["nick_name"] = $row["nick_name"];
    $re["photo_path"] = $row["photo_path"];
    $re["photo_path_small"] = $row["photo_path_small"];
    $re["photo_path_big"] = $row["photo_path_big"];
    $re["eshortname"] = empty($row["eshortname"]) ? "" : $row["eshortname"];
    
    return $re;
  }
  private function genLikes(&$rows, $conv_id) 
  {
    $likes = array();
    if(empty($rows)) return $likes;
    $likerows = array_values(array_filter($rows, function ($row) use ($conv_id)
    {
      return $row["conv_id"] == $conv_id;
    }));
    foreach ($likerows as &$row) 
    {
      $like = array();
      $like["like_staff"] = $row["like_staff"];
      $like["like_staff_nickname"] = $row["nick_name"];
      $like["like_date"] = $row["like_date"];
      $likes[] = $like;
    } 
    
    return $likes;
  }
  private function genAttachs(&$rows) 
  {
    $attachs = array();
    foreach ($rows as &$row) 
    {
      $attach = array();
      $attach["attach_id"] = $row["attach_id"];
      $attach["file_name"] = $row["file_name"];
      $attach["file_ext"] = $row["file_ext"];
      $attach["up_by_staff"] = $row["up_by_staff"];
      $attach["up_date"] = $row["up_date"];
      $attachs[] = $attach;
    }
    return $attachs;
  }
  
  private function genReplys($bus_conv, $facepath_pre, &$conv_rows, &$like_rows, &$attach_rows) 
  {
    $replys = array();
    for ($i = 1; $i < count($conv_rows); $i++)
    {
      $row = $conv_rows[$i];
      $reply = array();
      $reply["reply_id"] = $row["conv_id"];
      $reply["reply_staff"] = $row["login_account"];
      $reply["reply_staff_obj"] = $this->genStaff($row);
      $reply["reply_date"] = $row["post_date"];
      $reply["reply_content"] = $bus_conv->replaceContent($row["conv_content"], $facepath_pre);
      $reply["reply_to"] = $row["reply_to"];
      $reply["reply_to_nickname"] = $row["reply_to_name"];
      $reply["like_num"] = $row["like_num"];
      $reply["comefrom"] = $row["comefrom_d"];
      $reply["likes"] = $this->genLikes($like_rows, $row["conv_id"]);
      $conv_idA = $row["conv_id"];
      $replyattachs = array_values(array_filter($attach_rows, function ($rowA) use ($conv_idA)
        {
          return $rowA["conv_id"] == $conv_idA;
        }));
      $reply["attachs"] = $this->genAttachs($replyattachs);
      $replys[] = $reply;
    }
    
    return $replys;
  }
  
    public function getTrend($da, $user, $conv_root_id, $FILE_WEBSERVER_URL) 
    {
        $re = array();    
        $pre = $this->get('templating.helper.assets')->geturl('bundles/fafatimewebase/images/face/');
        $conv = new \Justsy\BaseBundle\Business\Conv();
        $ds = $conv->getTrend($da, $user, $conv_root_id, $FILE_WEBSERVER_URL);
        $rowRoot = $ds["we_convers_list"]["rows"][0];        
        $re["conv_id"] = $rowRoot["conv_root_id"];
        $re["create_staff"] = $rowRoot["login_account"];
        $re["create_staff_obj"] = $this->genStaff($rowRoot);
        $re["post_date"] = $rowRoot["post_date"];
        $re["conv_type_id"] = $rowRoot["conv_type_id"];
        $re["conv_content"] = $conv->replaceContent($rowRoot["conv_content"], $pre);
        $re["copy_num"] = $rowRoot["copy_num"];
        $re["reply_num"] = $rowRoot["reply_num"];
        $re["atten_num"] = $rowRoot["atten_num"];
        $re["like_num"] = (float)$rowRoot["like_num"];
        $re["iscollect"] = empty($rowRoot["atten_id"]) ? "0" : "1";
        $re["comefrom"] = $rowRoot["comefrom_d"];
        $re["likes"] = $this->genLikes($ds["we_convers_like"]["rows"], $conv_root_id);
        $re["attachs"] = $this->genAttachs($ds["we_convers_attach"]["rows"]);
        $re["replys"] = $this->genReplys($conv, $pre, $ds["we_convers_list"]["rows"], $ds["we_convers_like"]["rows"], $ds["we_convers_attach_reply"]["rows"]);
        $re["post_to_group"] = $rowRoot["post_to_group"];
        return $re;
    }
  
  public function getAsk($da, $user, $conv_root_id, $FILE_WEBSERVER_URL) 
  {
    $re = array();
    
    $pre = $this->get('templating.helper.assets')->geturl('bundles/fafatimewebase/images/face/');
    
    $conv = new \Justsy\BaseBundle\Business\Conv();
    $ds = $conv->getAsk($da, $user, $conv_root_id, $FILE_WEBSERVER_URL);
    
    $rowRoot = $ds["we_convers_list"]["rows"][0];
    
    $re["conv_id"] = $rowRoot["conv_root_id"];
    $re["create_staff"] = $rowRoot["login_account"];
    $re["create_staff_obj"] = $this->genStaff($rowRoot);
    $re["post_date"] = $rowRoot["post_date"];
    $re["conv_type_id"] = $rowRoot["conv_type_id"];
    $re["conv_content"] = $conv->replaceContent($rowRoot["conv_content"], $pre);
    $re["copy_num"] = $rowRoot["copy_num"];
    $re["reply_num"] = $rowRoot["reply_num"];
    $re["atten_num"] = $rowRoot["atten_num"];
    $re["like_num"] = (float)$rowRoot["like_num"];
    $re["iscollect"] = empty($rowRoot["atten_id"]) ? "0" : "1";
    $re["comefrom"] = $rowRoot["comefrom_d"];
    $re["likes"] = $this->genLikes($ds["we_convers_like"]["rows"], $conv_root_id);
    $re["attachs"] = $this->genAttachs($ds["we_convers_attach"]["rows"]);
    $re["replys"] = $this->genReplys($conv, $pre, $ds["we_convers_list"]["rows"], $ds["we_convers_like"]["rows"], $ds["we_convers_attach_reply"]["rows"]);
    $re["post_to_group"] = $rowRoot["post_to_group"];
    
    return $re;
  }
  
  public function getTogether($da, $user, $conv_root_id, $FILE_WEBSERVER_URL) 
  {
    $re = array();
    
    $pre = $this->get('templating.helper.assets')->geturl('bundles/fafatimewebase/images/face/');
    
    $conv = new \Justsy\BaseBundle\Business\Conv();
    $ds = $conv->getTogether($da, $user, $conv_root_id, $FILE_WEBSERVER_URL);
    
    $rowRoot = $ds["we_convers_list"]["rows"][0];
    
    $re["conv_id"] = $rowRoot["conv_root_id"];
    $re["create_staff"] = $rowRoot["login_account"];
    $re["create_staff_obj"] = $this->genStaff($rowRoot);
    $re["post_date"] = $rowRoot["post_date"];
    $re["conv_type_id"] = $rowRoot["conv_type_id"];
    $re["conv_content"] = $conv->replaceContent($rowRoot["conv_content"], $pre);
    $re["copy_num"] = $rowRoot["copy_num"];
    $re["reply_num"] = $rowRoot["reply_num"];
    $re["atten_num"] = $rowRoot["atten_num"];
    $re["like_num"] = (float)$rowRoot["like_num"];
    $re["iscollect"] = empty($rowRoot["atten_id"]) ? "0" : "1";
    $re["comefrom"] = $rowRoot["comefrom_d"];
    $re["likes"] = $this->genLikes($ds["we_convers_like"]["rows"], $conv_root_id);
    $re["attachs"] = $this->genAttachs($ds["we_convers_attach"]["rows"]);
    $re["replys"] = $this->genReplys($conv, $pre, $ds["we_convers_list"]["rows"], $ds["we_convers_like"]["rows"], $ds["we_convers_attach_reply"]["rows"]);
    $re["post_to_group"] = $rowRoot["post_to_group"];
    
    $together_row = $ds["we_together"]["rows"][0];
    $together = array();
    $together["title"] = $together_row["title"];
    $together["will_date"] = $together_row["will_date"];
    $together["will_dur"] = $together_row["will_dur"];
    $together["will_addr"] = $together_row["will_addr"];
    $together["together_desc"] = $conv->replaceContent($together_row["together_desc"], $pre);
    
    $together_staffs = array();
    foreach ($ds["we_together_staff"]["rows"] as &$row) 
    {
      $together_staff = array();
      $together_staff["staff_id"] = $row["login_account"];
      $together_staff["staff_name"] = $row["nick_name"];
      $together_staffs[] = $together_staff;
    }
    $together["together_staffs"] = $together_staffs;
    
    $re["together"] = $together;
    
    return $re;
  }
  
  public function getVote($da, $user, $conv_root_id, $FILE_WEBSERVER_URL) 
  {
    $re = array();
    
    $pre = $this->get('templating.helper.assets')->geturl('bundles/fafatimewebase/images/face/');
    
    $conv = new \Justsy\BaseBundle\Business\Conv();
    $ds = $conv->getVote($da, $user, $conv_root_id, $FILE_WEBSERVER_URL);
    
    $re = array();
    if ( $ds && $ds["we_convers_list"]["recordcount"]>0){
    	$rowRoot = $ds["we_convers_list"]["rows"][0];
	    $re["conv_id"] = $rowRoot["conv_root_id"];
	    $re["create_staff"] = $rowRoot["login_account"];
	    $re["create_staff_obj"] = $this->genStaff($rowRoot);
	    $re["post_date"] = $rowRoot["post_date"];
	    $re["conv_type_id"] = $rowRoot["conv_type_id"];
	    $re["conv_content"] = $conv->replaceContent($rowRoot["conv_content"], $pre);
	    $re["copy_num"] = $rowRoot["copy_num"];
	    $re["reply_num"] = $rowRoot["reply_num"];
	    $re["atten_num"] = $rowRoot["atten_num"];
	    $re["like_num"] = (float)$rowRoot["like_num"];
	    $re["iscollect"] = empty($rowRoot["atten_id"]) ? "0" : "1";
	    $re["comefrom"] = $rowRoot["comefrom_d"];
	    $re["likes"] = $this->genLikes($ds["we_convers_like"]["rows"], $conv_root_id);
	    $re["attachs"] = $this->genAttachs($ds["we_convers_attach"]["rows"]);
	    $re["replys"] = $this->genReplys($conv, $pre, $ds["we_convers_list"]["rows"], $ds["we_convers_like"]["rows"], $ds["we_convers_attach_reply"]["rows"]);
	    $re["post_to_group"] = $rowRoot["post_to_group"];
	    
	    $vote_row = $ds["we_vote"]["rows"][0];
	    $vote = array();
	    $vote["title"] = $vote_row["title"];
	    $vote["vote_all_num"] = $vote_row["vote_all_num"];
	    $vote["is_multi"] = $vote_row["is_multi"];
	    $vote["finishdate"] = $vote_row["finishdate"];
	    
	    $vote_options = array();
	    foreach ($ds["we_vote_option"]["rows"] as &$row) 
	    {
	      $vote_option = array();
	      $vote_option["option_id"] = $row["option_id"];
	      $vote_option["option_desc"] = $row["option_desc"];
	      $vote_option["vote_num"] = $row["vote_num"];
	      $vote_options[] = $vote_option;
	    }
	    $vote["vote_options"] = $vote_options;
	    
	    $vote["isvoted"] = $ds["we_vote_user"]["rows"][0]["c"] == 0 ? "0" : "1";
	    $vote["vote_user_num"] = $vote_row["vote_user_num"];
	    
	    $re["vote"] = $vote;
    }
    return $re;
  }
  
  public function getCopy($da, $user, $conv_root_id, $FILE_WEBSERVER_URL) 
  {
    $re = array();
    
    $pre = $this->get('templating.helper.assets')->geturl('bundles/fafatimewebase/images/face/');
    
    $conv = new \Justsy\BaseBundle\Business\Conv();
    $ds = $conv->getCopy($da, $user, $conv_root_id, $FILE_WEBSERVER_URL);
    
    $rowRoot = $ds["we_convers_list"]["rows"][0];
    
    $re["conv_id"] = $rowRoot["conv_root_id"];
    $re["create_staff"] = $rowRoot["login_account"];
    $re["create_staff_obj"] = $this->genStaff($rowRoot);
    $re["post_date"] = $rowRoot["post_date"];
    $re["conv_type_id"] = $rowRoot["conv_type_id"];
    $re["conv_content"] = $conv->replaceContent($rowRoot["conv_content"], $pre);
    $re["copy_num"] = $rowRoot["copy_num"];
    $re["reply_num"] = $rowRoot["reply_num"];
    $re["atten_num"] = $rowRoot["atten_num"];
    $re["like_num"] = (float)$rowRoot["like_num"];
    $re["iscollect"] = empty($rowRoot["atten_id"]) ? "0" : "1";
    $re["comefrom"] = $rowRoot["comefrom_d"];
    $re["likes"] = $this->genLikes($ds["we_convers_like"]["rows"], $conv_root_id);
    $re["attachs"] = $this->genAttachs($ds["we_convers_attach"]["rows"]);
    $re["replys"] = $this->genReplys($conv, $pre, $ds["we_convers_list"]["rows"], $ds["we_convers_like"]["rows"], $ds["we_convers_attach_reply"]["rows"]);
    $re["post_to_group"] = $rowRoot["post_to_group"];
    
    $conv_copy = array();
    if ($ds["we_convers_list_copy"]["recordcount"] > 0)
    {
      $conv_copy_row = $ds["we_convers_list_copy"]["rows"][0];
      $conv_copy["conv_id"] = $conv_copy_row["conv_id"];
      $conv_copy["create_staff"] = $conv_copy_row["login_account"];
      $conv_copy["create_staff_obj"] = $this->genStaff($conv_copy_row);
      $conv_copy["post_date"] = $conv_copy_row["post_date"];
      $conv_copy["conv_type_id"] = $conv_copy_row["conv_type_id"];
      $conv_copy["conv_content"] = $conv->replaceContent($conv_copy_row["conv_content"], $pre);
      $conv_copy["copy_num"] = $conv_copy_row["copy_num"];
      $conv_copy["reply_num"] = $conv_copy_row["reply_num"];
      $conv_copy["atten_num"] = $conv_copy_row["atten_num"];
      $conv_copy["like_num"] = (float)$conv_copy_row["like_num"];
      $conv_copy["comefrom"] = $conv_copy_row["comefrom_d"];
      $conv_copy["likes"] = $this->genLikes($ds["we_convers_like_copy"]["rows"], $conv_copy_row["conv_id"]);
      $conv_copy["attachs"] = $this->genAttachs($ds["we_convers_attach_copy"]["rows"]);
    }
    else
    {
      $conv_copy["conv_id"] = null;  
    }
    $re["conv_copy"] = $conv_copy;
    
    return $re;
  }
  
  public function getShareTrend($da, $user, $conv_root_id, $FILE_WEBSERVER_URL) {
        $pre = $this->get('templating.helper.assets')->geturl('bundles/fafatimewebase/images/face/'); 
        $re = array();
        
        $conv = new \Justsy\BaseBundle\Business\Conv();
        $ds = $conv->getShareTrend($da, $user, $conv_root_id, $FILE_WEBSERVER_URL);

        if (count($ds["we_convers_list"]["rows"]) == 0)
            return new Response("");
        $rowRoot = $ds["we_convers_list"]["rows"][0];
            $re["link_url"] = $rowRoot["link_url"];
            $re["reason"] = $rowRoot["reason"];
            $re["conv_id"] = $rowRoot["conv_root_id"];
            $re["create_staff"] = $rowRoot["login_account"];
            $re["create_staff_obj"] = $this->genStaff($rowRoot);
            $re["post_date"] = $rowRoot["post_date"];
            $re["conv_type_id"] = $rowRoot["conv_type_id"];
            $re["conv_content"] = $rowRoot["conv_content"];
            $re["copy_num"] = $rowRoot["copy_num"];
            $re["reply_num"] = $rowRoot["reply_num"];
            $re["atten_num"] = $rowRoot["atten_num"];
            $re["like_num"] = (float)$rowRoot["like_num"];
            $re["iscollect"] = empty($rowRoot["atten_id"]) ? "0" : "1";
            $re["comefrom"] = $rowRoot["comefrom_d"];
            $re["likes"] = $this->genLikes($ds["we_convers_like"]["rows"], $conv_root_id);
            $re["attachs"] = $this->genAttachs($ds["we_convers_attach"]["rows"]);
            $re["replys"] = $this->genReplys($conv, $pre, $ds["we_convers_list"]["rows"], $ds["we_convers_like"]["rows"], $ds["we_convers_attach_reply"]["rows"]);
            $re["post_to_group"] = $rowRoot["post_to_group"];

            
            return $re;
    }
  
  //8.2.15  取评论/回复
  public function getReplyAction()
  {    
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();
    
    $da = $this->get('we_data_access'); 
    
    $conv_id = $request->get("conv_id");
    $pageindex = $request->get("pageindex");
    
    $pagesize = 15;
    $pre = $this->get('templating.helper.assets')->geturl('bundles/fafatimewebase/images/face/');

    try 
    {
      if (empty($pageindex)) $pageindex = 1;
      
      $conv = new \Justsy\BaseBundle\Business\Conv();
      $ds = $conv->getReply($da, $user, $conv_id, "", $pageindex, $pagesize);
      $we_convers_lists = array_merge(array(0), $ds["we_convers_list"]["rows"]);
      $re["replys"] = $this->genReplys($conv, $pre, $we_convers_lists, $ds["we_convers_like"]["rows"], $ds["we_convers_attach_reply"]["rows"]);
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
  
  //8.2.16  取得指定圈子某人发布的15条信息
  public function getUserConvsAction()
  {    
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();
    
    $da = $this->get('we_data_access'); 
    
    $circle_id = $request->get("circle_id");
    $staff = $request->get("staff");
    $last_end_id = $request->get("last_end_id");

    try 
    {
      if (empty($staff) ) throw new \Exception("param is null");
      
      $sql = "select a.conv_root_id 
        from we_convers_list a
        where a.conv_id=a.conv_root_id ";
      $params = array();
      if(!empty($circle_id)){
          $sql.=" and a.post_to_circle=?
              and a.post_to_group='ALL'
              and a.login_account=?
              and exists(select 1 from we_circle_staff wcs where wcs.circle_id=a.post_to_circle and wcs.login_account=?)";
          $params[] = (string)$circle_id;
          $params[] = (string)$staff;
          $params[] = (string)$user->getUserName();
      }else{
          $sql.=" and a.login_account=? ";
          $params[] = (string)$staff;
      }      
      if (!empty($last_end_id))
      {
        $sql .= " and (0+conv_root_id)<? ";
        $params[] = (float)$last_end_id;
      }
    
      $sql .= " order by (0+a.conv_id) desc";
      $sql .= " limit 0, 15 ";

      $da = $this->get('we_data_access');
      $ds = $da->GetData("we_convers_list", $sql, $params);

      $conv_root_ids = array_map(function ($row) {
          return $row["conv_root_id"];
        }, 
        $ds["we_convers_list"]["rows"]);
      
      $re["convs"] = $this->getConvAction($conv_root_ids);            
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
  
  //8.2.17 取得指定圈子某人收藏的15条信息
  public function getAttenConvsAction()
  {
    $da = $this->get('we_data_access');
    $login_account = $this->get('security.context')->getToken()->getUser()->getUserName();
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $circle_id = $request->get("circle_id");
    $last_end_id = $request->get("last_end_id");  //页号
    $record    = $request->get("record",15);     //每页记录数
    $limit = " limit ".$record;
    $sql = "";
    $parameter = array();
    try 
    {
    	$condition = "";
      $sql = "select a.atten_id conv_root_id from we_staff_atten a inner join we_convers_list b on b.conv_id=a.atten_id 
              where a.login_account=? and a.atten_type='02'";
      array_push($parameter,(string)$login_account);
      if (!empty($circle_id)){
      	$condition =" and b.post_to_circle=? ";
        array_push($parameter,(string)$circle_id);
      }
      if (!empty($last_end_id)){
      	$condition =" and b.conv_id>? ";
        array_push($parameter,(string)$last_end_id);
      }
      $sql .= $condition." order by (0+b.conv_id) desc ".$limit;
      $ds = $da->GetData("we_convers_list", $sql, $parameter);
      $conv_root_ids = array_map(function ($row) {
          return $row["conv_root_id"];
      },$ds["we_convers_list"]["rows"]); 
      $re["convs"] = $this->getConvAction($conv_root_ids);
    }
    catch (\Exception $e)
    {
      $re["returncode"] = ReturnCode::$SYSERROR;
      $re["msg"] = $e->getMessage();
      $this->get('logger')->err($e-getMessage());
    } 
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  
  //8.2.18.1  取未读的提到我的数量
  public function getAtmeUnreadNumAction()
  {    
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();
    
    $da = $this->get('we_data_access'); 
    
    try 
    {
      $sql = "select count(*) c from we_notify where notify_type='03' and notify_staff=?";
      $params = array();
      $params[] = (string)$user->getUserName();
      
      $ds = $da->GetData("we_notify", $sql, $params);
      
      $re["num"] = $ds["we_notify"]["rows"][0]["c"];
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
  
    //8.2.18.2  取得15条提到我的信息
    public function getAtmeConvsAction()
    {    
        $re = array("returncode" => ReturnCode::$SUCCESS);
        $request = $this->getRequest();
        $user = $this->get('security.context')->getToken()->getUser();    
        $da = $this->get('we_data_access');
        $last_end_id = $request->get("last_end_id");
        try 
        {
            $sql = "select distinct b.conv_root_id from we_staff_at_me a inner join we_convers_list b on b.conv_id=a.conv_id 
                    inner join we_staff c on c.login_account=b.login_account where a.login_account=?";
            $params = array();
            $params[] = (string)$user->getUserName();            
            if (!empty($last_end_id))
            {
                $sql .= " and (0+conv_root_id)<? ";
                $params[] = (float)$last_end_id;
            }
            else
            {
                //删除提到我的通知
                $da->ExecSQL("delete from we_notify where notify_type='03' and notify_staff=?", array((string)$user->getUserName()));       
            }
            $sql .= " order by (0+b.conv_id) desc limit 0, 15 ";           
            $ds = $da->GetData("we_convers_list", $sql, $params);
            $conv_root_ids = array_map(function ($row) {
               return $row["conv_root_id"];
            },$ds["we_convers_list"]["rows"]);
            $re["convs"] = $this->getConvAction($conv_root_ids);
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
  
  //发布新的企业动态.用于第三方应用发布动态到企业圈子
  public function newEnterpriseTrendAction() 
  {
    $re = array("returncode" => "0000");
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();
    
    $da = $this->get('we_data_access'); 
    
    $conv_content = $request->get("conv_content");
    $attachs = $request->get("attachs");
    $circle_id = $user->get_circle_id("");//获取企业圈子编号

    try 
    {
      if (empty($conv_content)) throw new \Exception("param is null");
    
      $attachs = array_map(function ($item) 
        {
          return trim($item);
        }, 
        (empty($attachs) ? array() : explode(',', $attachs)));
    
      $conv_id = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_convers_list", "conv_id");
      
      $conv = new \Justsy\BaseBundle\Business\Conv();
      $conv->newTrend($da, $user, $conv_id, $conv_content, $circle_id, "ALL", array(), $attachs, $request->getSession()->get('comefrom'),$this->container);
      
      $re["conv"] = $conv_id;
    } 
    catch (\Exception $e) 
    {
      $re["returncode"] = "9999";
      $this->get('logger')->err($e);
    }
    
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
    
  //发布新的私有动态.用于第三方应用发布动态到指定人员
  public function newPrivateTrendAction() 
  {
    $re = array("returncode"=>"0000");
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();
    
    $da = $this->get('we_data_access'); 
    
    $conv_content = $request->get("conv_content");
    $attachs = $request->get("attachs");
    $circle_id = $user->get_circle_id("");//获取企业圈子编号
    $notifystaffs = $request->get("notifystaffs");
    try 
    {
      if (empty($conv_content)) throw new \Exception("param is null");
    
      $attachs = array_map(function ($item) 
        {
          return trim($item);
        }, 
        (empty($attachs) ? array() : explode(',', $attachs)));
      
      $notifystaffs = array_map(function ($item) 
        {
          //判断是指定的Openidg还是帐号，是OPENID则要转换为帐号
          if(strpos("@",$item)===false)
          {
             $sql = "select login_account from we_staff where openid=?";
             $ds = $da->GetData("item",$sql,array((string)$item));
             if($ds && count($ds["item"]["rows"])>0) return $ds["item"]["rows"][0]["login_account"];
             else return "";
          }
          return trim($item);
        }, 
        (empty($notifystaffs) ? array() : explode(',', $notifystaffs)));
      
      $conv_id = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_convers_list", "conv_id");
      
      $conv = new \Justsy\BaseBundle\Business\Conv();
      $conv->newTrend($da, $user, $conv_id, $conv_content, $circle_id, "PRIVATE", $notifystaffs, $attachs, $request->getSession()->get('comefrom'),$this->container);
      
      $re["conv"] = $conv_id;
    } 
    catch (\Exception $e) 
    {
      $re["returncode"] = "9999";
      $this->get('logger')->err($e);
    }
    
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  
  public function getMLiveProductList100Action()
  {    
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();
    
    $da = $this->get('we_data_access'); 

    $barcode = $request->get("barcode");
    try 
    { 
      $barcode = empty($barcode) ? "%" : $barcode;
      
      $sql = "select a.id, a.code, a.product_name, a.designuserid, a.designfeeling, a.salessuggest, a.type, a.category, a.remark, 
  a.create_user, a.create_date, a.last_modified_user, a.last_modified_date, a.conv_id, 
  case when b.conv_id is null then '0' else '1' end hasconv
from mlive_product a
left join we_convers_list b on a.conv_id=b.conv_id
where a.code like ?
limit 0, 100";
      $params = array();
      $params[] = (string)$barcode;       

      $ds = $da->GetData("mlive_product", $sql, $params);
      
      $conv = new \Justsy\BaseBundle\Business\Conv();
      foreach ($ds["mlive_product"]["rows"] as &$row) 
      { 
        if ($row["hasconv"] == "0")
        {          
          $conv_id = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_convers_list", "conv_id");
          $conv->newTrend($da, $user, $conv_id, 
            "来自".$row["designuserid"]."的新设计：".$row["product_name"]."\n设计理念：".$row["designfeeling"], 
            $user->get_circle_id(""), "ALL", array(), array(), $request->getSession()->get('comefrom'), $this->container);  
          $row["conv_id"] = $conv_id;
          
          $da->ExecSQL("update mlive_product set conv_id=? where id=?", array((string)$conv_id, (string)$row["id"]));
        }  
      }
      
      $re["mlive_product_list"] = $ds["mlive_product"]["rows"];
      
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
    //查询当前登录人圈子最新动态数
    public function getUnreadCircleConversNumAction() {
        $re = array("returncode" => ReturnCode::$SUCCESS);
        $user = $this->get('security.context')->getToken()->getUser();
        $request = $this->get("request");
        $filter = $request->get('filter');
        $ehc=new EnterpriseHomeController();
        $ehc->setContainer($this->container);
        try {
            $ds = $ehc->getUnreadCircleConversNum($user->getUserName(),$filter);
            $re["conv_nums"] = $ds["we_convers_list"]["rows"];
        } catch (Exception $e) {
            $re["returncode"] = ReturnCode::$SYSERROR;
        }
        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }
    
    //查询当前登录人群组最新动态数
    public function getUnreadGroupConversNumAction() {
        $re = array("returncode" => ReturnCode::$SUCCESS);
        $user = $this->get('security.context')->getToken()->getUser();
        $request = $this->get("request");
        $filter = $request->get('filter');
        $ehc=new EnterpriseHomeController();
        $ehc->setContainer($this->container);
        try {
            $ds = $ehc->getUnreadGroupConversNum($user->getUserName(),$filter);
            $re["conv_nums"] = $ds["we_convers_list"]["rows"];
        } catch (Exception $e) {
            $re["returncode"] = ReturnCode::$SYSERROR;
        }
        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }  
  
  //8.2.22  取得15条私密信息
  public function getPrivateConvsAction()
  {    
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();
    
    $da = $this->get('we_data_access'); 
    
    $circle_id = $request->get("circle_id");
    $last_end_id = $request->get("last_end_id");

   try 
   {
      if (empty($circle_id)) throw new \Exception("param is null");
      
      $sql = "select a.conv_root_id 
from we_convers_list a
where a.conv_id=a.conv_root_id
  and a.post_to_circle=? and a.post_to_group='PRIVATE' and a.login_account=?";
      $params = array();
      $params[] = (string)$circle_id;
      $params[] = (string)$user->getUserName();
      
      if ($circle_id == "9999") 
      {
          //从im库中查询好友
          $staffmgr = new \Justsy\BaseBundle\Management\Staff($da,$this->get('we_data_access_im'), $user);
          $getfriendList = $staffmgr->getFriendLoginAccountList("1");
          if($getfriendList && count($getfriendList)>0)
          {
            $sql .= " and a.login_account in ('".implode("','",$getfriendList)."','".$user->getUserName()."')";
          }
          else
          {
            $sql .= " and a.login_account=?";
            $params[] = (string)$user->getUserName();
          }
      }
      
      if (!empty($last_end_id))
      {
        $sql .= " and (0+conv_root_id)<? ";
        $params[] = (float)$last_end_id;
      }
      $sql .=" and a.conv_type_id<>'06'";
      $sql .= " order by (0+a.conv_id) desc";
      $sql .= " limit 0, 15 ";

      $da = $this->get('we_data_access');
      $ds = $da->GetData("we_convers_list", $sql, $params);

      $conv_root_ids = array_map(function ($row) {
          return $row["conv_root_id"];
        }, 
        $ds["we_convers_list"]["rows"]);
      
      $re["convs"] = $this->getConvAction($conv_root_ids);
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
  

}
