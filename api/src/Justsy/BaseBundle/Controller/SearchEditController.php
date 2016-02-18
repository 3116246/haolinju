<?php

namespace Justsy\BaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class SearchEditController extends Controller
{
    //查询出前100个人员、群组、圈子，以json返回
    //人员 {datatype: 1, login_account: "", nick_name: "", photo_path: "", photo_url: ""}
    //群组 {datatype: 2, group_id:"", group_name: "", group_photo_path: "", group_photo_url: ""}
    //圈子 {datatype: 3, network_domain:"", circle_name: "", logo_path: "", logo_url: ""}
    public function queryAction($name)
    {
      $user = $this->get('security.context')->getToken()->getUser();
      $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
      $network_domain = $this->getRequest()->get('network_domain');
      $circle_id = $user->get_circle_id($network_domain);

      $sqls = array(
        "select 1 datatype, a.login_account, ifnull(a.nick_name, '') nick_name, a.photo_path, concat('$FILE_WEBSERVER_URL', ifnull(a.photo_path, '')) photo_url from we_staff a, we_circle_staff b where a.login_account=b.login_account and b.circle_id = ? and not exists (select 1 from we_micro_account m where m.eno=a.eno and a.login_account= m.number)",
        "select 2 datatype, c.group_id, c.group_name, c.group_photo_path, concat('$FILE_WEBSERVER_URL', ifnull(group_photo_path, '')) group_photo_url from we_staff a, we_group_staff b, we_groups c where a.login_account=b.login_account and b.group_id=c.group_id and a.login_account=? and c.circle_id=?",
        "select 3 datatype, c.network_domain, c.circle_name, c.logo_path, concat('$FILE_WEBSERVER_URL', ifnull(logo_path, '')) logo_url from we_staff a, we_circle_staff b, we_circle c where a.login_account=b.login_account and b.circle_id=c.circle_id and a.login_account=?"
        );
      $params = array(
        array((string)($circle_id)),
        array((string)($user->getUserName()), (string)($circle_id)),
        array((string)($user->getUserName()))
      );
        
      $da = $this->get('we_data_access');
      $da->PageSize = 100;    
      $da->PageIndex = 0;
      $dataset = $da->GetDatas(array("we_staff", "we_groups", "we_circle"), $sqls, $params);
      
      $rows = array_merge($dataset["we_staff"]["rows"], $dataset["we_groups"]["rows"], $dataset["we_circle"]["rows"]);

      $response = new Response(json_encode($rows));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }
    
    public function searchAction($network_domain) 
    {
      $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
      $user = $this->get('security.context')->getToken()->getUser();
      $request = $this->getRequest();
      $circle_id = $user->get_circle_id($network_domain);
      $pagesize = 15;
      
      $q = $request->get("q");
      $pageindex = $request->get("p");
      if (empty($pageindex)) $pageindex = 1;
      
      $sqls = array();
      $all_params = array();

      //计算总数
      $sql = "select count(distinct a.conv_id) c
from we_convers_index a, we_convers_list b
where a.conv_id=b.conv_id and b.conv_id=b.conv_root_id
  and a.circle_id=? 
  and a.token=?";
      $params = array();
      $params[] = (string)$circle_id;
      $params[] = (string)$q;
      
      $sqls[] = $sql;
      $all_params[] = $params;
      
      $pagestart = ($pageindex-1)*$pagesize;
      $sql = "select distinct b.conv_id, b.login_account, b.post_date, b.conv_type_id, b.conv_root_id, substr(b.conv_content, 1, 100) conv_content, 
  b.post_to_group, b.post_to_circle, b.reply_to, b.copy_num, b.reply_num, b.atten_num,
  c.nick_name, c.photo_path, concat('$FILE_WEBSERVER_URL', ifnull(c.photo_path, '')) photo_url,
  f_cal_date_section(b.post_date) post_date_d
from we_convers_index a
inner join we_convers_list b on b.conv_id=a.conv_id and b.conv_id=b.conv_root_id
inner join we_staff c on b.login_account=c.login_account
where a.circle_id=? 
  and a.token=?
limit $pagestart, $pagesize
";
      $params = array();
      $params[] = (string)$circle_id;
      $params[] = (string)$q;
      
      $sqls[] = $sql;
      $all_params[] = $params;
      
      $da = $this->get('we_data_access');
      $ds = $da->GetDatas(array("count", "we_convers_list"), $sqls, $all_params);
    
      $pagecount = ceil($ds["count"]["rows"][0]["c"]/$pagesize);
      
      return $this->render('JustsyBaseBundle:Search:search.html.twig', array('this' => $this, 'curr_network_domain' => $network_domain, 'q' => $q, 'pageindex' => $pageindex, 'pagecount' => $pagecount, 'ds' => $ds));
    }
    
    public function mytrendAction()
    {
      $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
      $user = $this->get('security.context')->getToken()->getUser();
      $request = $this->getRequest();
      $network_domain=$request->get("network_domain");
      $pagesize = 45;
      
      $q = $request->get("q");
      $pageindex = $request->get("p");
      if (empty($pageindex)) $pageindex = 1;
      $account = $user->getUserName();
      $sqls = array();
      $all_params = array();

      //计算总数
      $sql = "select count(1) c
from  we_convers_list b
where b.conv_id=b.conv_root_id and b.login_account=?";
      $params = array();
      //$params[] = (string)$circle_id;
      $params[] = (string)$account;
      
      $sqls[] = $sql;
      $all_params[] = $params;
      
      $pagestart = ($pageindex-1)*$pagesize;
      $sql = "select distinct b.conv_id, b.login_account, b.post_date, b.conv_type_id, b.conv_root_id, substr(b.conv_content, 1, 100) conv_content, 
  b.post_to_group, b.post_to_circle, b.reply_to, b.copy_num, b.reply_num, b.atten_num,
  c.nick_name, c.photo_path, concat('$FILE_WEBSERVER_URL', ifnull(c.photo_path, '')) photo_url,
  f_cal_date_section(b.post_date) post_date_d
from  we_convers_list b  
inner join we_staff c on b.login_account=c.login_account
where b.conv_id=b.conv_root_id and b.login_account=? order by b.post_date desc 
limit $pagestart, $pagesize 
";
      $params = array();
      $params[] = (string)$account;
      
      $sqls[] = $sql;
      $all_params[] = $params;
      
      $da = $this->get('we_data_access');
      $ds = $da->GetDatas(array("count", "we_convers_list"), $sqls, $all_params);
    
      $pagecount = ceil($ds["count"]["rows"][0]["c"]/$pagesize);
      
      return $this->render('JustsyBaseBundle:PersonalHome:mytrend.html.twig', array('this' => $this, 'curr_network_domain' => $network_domain, 'pageindex' => $pageindex, 'pagecount' => $pagecount, 'ds' => $ds));
        	  
    }
    
    public function myrelationAction()
    {
        	$user = $this->get('security.context')->getToken()->getUser();
          $r = new \Justsy\BaseBundle\Management\RelationMgr($this->get('we_data_access'),null,$user );
          $rows = $r->query($this->getRequest()->get("v"));
		      $response = new Response(json_encode($rows));
		      $response->headers->set('Content-Type', 'text/json');
		      return $response;          
    }
}
