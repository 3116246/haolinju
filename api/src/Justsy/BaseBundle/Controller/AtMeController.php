<?php

namespace Justsy\BaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class AtMeController extends Controller
{
  public $conv_type;
  public $recordcount;
  public $conv_root_ids;
  public $pageindex;
  public $pagesize = 10;
  public $q;
  
	public function indexAction($network_domain)
	{
		$request = $this->getRequest();
		$user = $this->get('security.context')->getToken()->getUser();
	  $this->conv_type = $request->get('conv_type');
		$this->pageindex = $request->get('pageindex');
		if ($this->pageindex){}
		else { $this->pageindex = 1; }
		$this->q = $request->get('q');

	  $this->conv_root_ids = $this->getCollectConv($network_domain, $this->conv_type);
	  //获取是否可以评论、转发
	  $trend = $user->IsFunctionTrend($network_domain);
		return $this->render('JustsyBaseBundle:AtMe:index.html.twig',array('curr_network_domain'=>$network_domain, 'this' => $this,'trend'=> $trend));
	}
	public function indexpcAction($network_domain)
	{
		$request = $this->getRequest();
		
	  $this->conv_type = $request->get('conv_type');
		$this->pageindex = $request->get('pageindex');
		if ($this->pageindex){}
		else { $this->pageindex = 1; }
		$this->q = $request->get('q');

	  $this->conv_root_ids = $this->getCollectConv($network_domain, $this->conv_type);
	  
		return $this->render('JustsyBaseBundle:AtMe:index_pc.html.twig',array('curr_network_domain'=>$network_domain, 'this' => $this));

	}
	public function getCollectConv($network_domain, $conv_type) 
  {		
		$user = $this->get('security.context')->getToken()->getUser();
		$curr_circle_id=$user->get_circle_id($network_domain);
		$conv_typeWhere = $conv_type == "01" ? " b.conv_type_id='99' " : ($conv_type == "00" ? " b.conv_id=b.conv_root_id " : " 1=1 ");
		$nick_nameWhere = ($this->q && $this->q != "" ? " c.nick_name like concat('%', ?, '%') " : " '1'<>? ");
		$contentWhere = ($this->q && $this->q != "" ? " b.conv_content like concat('%', ?, '%') " : " '1'<>? ");
		
		$sql = "select distinct b.conv_root_id
from we_staff_at_me a
inner join we_convers_list b on b.conv_id=a.conv_id and b.post_to_circle=? and $conv_typeWhere 
inner join we_staff c on c.login_account=b.login_account 
where a.login_account=?
  and ($contentWhere or $nick_nameWhere)
order by b.post_date desc
";
		$params = array();
		$params[] = (string)$curr_circle_id;
		$params[] = (string)$user->getUserName();
		$params[] = (string)$this->q;
		$params[] = (string)$this->q;
		
		$da = $this->get('we_data_access');
		
		//删除提到我的通知
		$da->ExecSQL("delete from we_notify 
where notify_type='03' and notify_staff=? 
  and exists(select 1 from we_convers_list where we_convers_list.conv_id=we_notify.msg_id and we_convers_list.post_to_circle=?)", 
		  array((string)$user->getUserName(), (string)$curr_circle_id));
		
		$da->PageIndex = $this->pageindex - 1;
		$da->PageSize = $this->pagesize;
		$ds=$da->Getdata("we_convers_list", $sql, $params);
		
		$this->recordcount = $ds["we_convers_list"]["recordcount"];
		
    $conv_root_ids = array_map(function ($row) {
        return $row["conv_root_id"];
      }, 
      $ds["we_convers_list"]["rows"]);
    
    return $conv_root_ids;
  }

}
