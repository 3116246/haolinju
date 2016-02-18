<?php
namespace Justsy\BaseBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\DataAccess\DataAccess;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class CollectController extends Controller
{
  public $collect_type;
  public $conv_type;
  public $recordcount;
  public $conv_root_ids;
  public $pageindex;
  public $pagesize = 10;
  public $q;
  public $labels;
  public $label_name;
  
	public function indexAction($network_domain)
	{
		$request = $this->getRequest();
		$user = $this->get('security.context')->getToken()->getUser();
		$da = $this->get('we_data_access');
		
	  $this->collect_type = $request->get('collect_type');
	  $this->conv_type = $request->get('conv_type');
		$this->pageindex = $request->get('pageindex');
		if ($this->pageindex){}
		else { $this->pageindex = 1; }
		$this->q = $request->get('q');
		$this->label_name = $request->get('label_name');
	  
    $conv = new \Justsy\BaseBundle\Business\Conv();
    $x = $conv->getLabel($da, $user);
    $this->labels = $x["we_convers_label"];

	  $this->conv_root_ids = $this->getCollectConv($network_domain, $this->conv_type, $this->label_name);
	  //获取是否可以评论、转发
	  $trend = $user->IsFunctionTrend($network_domain);
		return $this->render('JustsyBaseBundle:Collect:index.html.twig',array('curr_network_domain'=>$network_domain, 'this' => $this,'trend'=> $trend));
	}
	public function indexpcAction($network_domain)
	{
		$request = $this->getRequest();
		$user = $this->get('security.context')->getToken()->getUser();
		$da = $this->get('we_data_access');
		
	  $this->collect_type = $request->get('collect_type');
	  $this->conv_type = $request->get('conv_type');
		$this->pageindex = $request->get('pageindex');
		if ($this->pageindex){}
		else { $this->pageindex = 1; }
		$this->q = $request->get('q');
		$this->label_name = $request->get('label_name');
	  
    $conv = new \Justsy\BaseBundle\Business\Conv();
    $x = $conv->getLabel($da, $user);
    $this->labels = $x["we_convers_label"];

	  $this->conv_root_ids = $this->getCollectConv($network_domain, $this->conv_type, $this->label_name);
	  
		return $this->render('JustsyBaseBundle:Collect:index_pc.html.twig',array('curr_network_domain'=>$network_domain, 'this' => $this));

	}
	public function getCollectConv($network_domain, $conv_type, $label_name) 
  {		
		$user = $this->get('security.context')->getToken()->getUser();
		$curr_circle_id=$user->get_circle_id($network_domain);
		$conv_typeWhere = in_array($conv_type, array("00", "01", "02", "03")) ? " b.conv_type_id=? " : " '00'<>? ";
		$nick_nameWhere = ($this->q && $this->q != "" ? " c.nick_name like concat('%', ?, '%') " : " '1'<>? ");
		$contentWhere = ($this->q && $this->q != "" ? " b.conv_content like concat('%', ?, '%') " : " '1'<>? ");
		$labelWhere = empty($label_name) ? " 1<>? " : " exists(select 1 from we_convers_label d where d.login_account=a.login_account and d.atten_id=a.atten_id and d.label_name=?) ";
				
		$sql = "select a.atten_id conv_root_id
from we_staff_atten a
inner join we_convers_list b on b.conv_id=a.atten_id and b.post_to_circle=? and $conv_typeWhere 
inner join we_staff c on c.login_account=b.login_account 
where a.login_account=? and a.atten_type='02'
  and ($contentWhere or $nick_nameWhere)
  and $labelWhere
order by b.post_date desc";
		$params = array();
		$params[] = (string)$curr_circle_id;
		$params[] = (string)$conv_type;
		$params[] = (string)$user->getUserName();
		$params[] = (string)$this->q;
		$params[] = (string)$this->q;
		$params[] = (string)$label_name;
		
		$da = $this->get('we_data_access');
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
	
	public function getDocAction($network_domain)
	{
	}
}