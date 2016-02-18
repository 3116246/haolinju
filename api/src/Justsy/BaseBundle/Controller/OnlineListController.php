<?php

namespace Justsy\BaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class OnlineListController extends Controller
{    
    public function getFriendListAction() 
    {
    	$user = $this->get('security.context')->getToken()->getUser();
      $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
  		$login_account=$user->getUserName();
  		
  		$da = $this->get('we_data_access');
  		
  		//取出好友列表
  		$sql = "select b.atten_id login_account, c.nick_name, concat('$FILE_WEBSERVER_URL', ifnull(c.photo_path, '')) photo_url
from we_staff_atten b, we_staff c
where b.atten_type='01'
  and b.atten_id = c.login_account 
  and b.login_account=? ";
      $params = array();
      $params[] = (string)$login_account;
      
      $ds = $da->GetData("we_staff_online", $sql, $params);
      
      $response = new Response(json_encode($ds["we_staff_online"]["rows"]));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }
    
    public function getOnlineListAction() 
    {
    	$user = $this->get('security.context')->getToken()->getUser();
      $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
  		$login_account=$user->getUserName();
  		
  		$da = $this->get('we_data_access');
  		
  		//更新在线时间
  		$da->ExecSQL("call p_we_staff_online(?)", array((string)$login_account));
  		
  		//取出在线列表，最近三分钟更新的
  		$sql = "select a.login_account, c.nick_name, concat('$FILE_WEBSERVER_URL', ifnull(c.photo_path, '')) photo_url
from we_staff_online a, we_staff_atten b, we_staff c
where a.login_account = b.atten_id and b.atten_type='01'
  and a.login_account = c.login_account 
  and b.login_account=?
  and a.last_online_date>=current_date() and a.last_online_date>date_add(now(), interval -3 MINUTE) ";
      $params = array();
      $params[] = (string)$login_account;
      
      $ds = $da->GetData("we_staff_online", $sql, $params);
      
      $response = new Response(json_encode($ds["we_staff_online"]["rows"]));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }
}
