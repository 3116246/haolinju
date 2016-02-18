<?php

namespace Justsy\BaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class ReplyController extends Controller
{
  public function replaceFaceEmote($str) 
  {
    $pre = $this->get('templating.helper.assets')->geturl('bundles/fafatimewebase/images/face/');
    
    $conv = new \Justsy\BaseBundle\Business\Conv();
    $str1 = $conv->replaceContent($str, $pre);
    
    return $str1;
  }
  
  public function inAction($network_domain)
  {
		$request = $this->getRequest();
		$user = $this->get('security.context')->getToken()->getUser();
		$curr_circle_id=$user->get_circle_id($network_domain);
	  //获取是否可以评论、转发
	  $trend = $user->IsFunctionTrend($network_domain);		
		$pageindex = $request->get('pageindex');
		if ($pageindex){} else { $pageindex = 1; }
		$pagesize = 10;
		$maxlimit = $pagesize*10;
		$q = $request->get('q');
		
		$nick_nameWhere = ($q && $q != "" ? " c.nick_name like concat('%', ?, '%') " : " '1'<>? ");
		$contentWhere = ($q && $q != "" ? " b.conv_content like concat('%', ?, '%') " : " '1'<>? ");
		
		$sql = "select * from (
select b.conv_id, b.login_account, b.post_date, b.conv_type_id, b.conv_root_id, 
  case when length(b.conv_content)>90 then concat(substr(b.conv_content, 1, 27), '...') else b.conv_content end conv_content,
  b.post_to_group, b.post_to_circle, b.reply_to, b.comefrom,
  c.nick_name reply_staff_name, c.photo_path, f_cal_date_section(b.post_date) post_date_d, cf.name comefrom_d,
  case when length(bb.conv_content)>90 then concat(substr(bb.conv_content, 1, 27), '...') else bb.conv_content end root_conv_content
from we_convers_list b 
inner join we_convers_list bb on bb.conv_id=b.conv_root_id
inner join we_staff c on c.login_account=b.login_account 
left  join we_code_all_code cf on cf.id=b.comefrom and cf.class='来自于'
where b.post_to_circle=? 
  and b.conv_type_id='99'
  and b.login_account<>?
  and (b.reply_to=? or (ifnull(b.reply_to, '')='' and bb.login_account=?))
  and ($nick_nameWhere or $contentWhere)
order by b.post_date desc
limit 0, $maxlimit 
) as fffa
";
		$params = array();
		$params[] = (string)$curr_circle_id;
		$params[] = (string)$user->getUserName();
		$params[] = (string)$user->getUserName();
		$params[] = (string)$user->getUserName();
		$params[] = (string)$q;
		$params[] = (string)$q;
		
		$da = $this->get('we_data_access');
		
		//删除评论我的通知
		$da->ExecSQL("delete from we_notify 
where notify_type='04' and notify_staff=? 
  and exists(select 1 from we_convers_list where we_convers_list.conv_id=we_notify.msg_id and we_convers_list.post_to_circle=?)", 
		  array((string)$user->getUserName(), (string)$curr_circle_id));

		$da->PageIndex = $pageindex - 1;
		$da->PageSize = $pagesize;
		$ds=$da->Getdata("we_convers_list", $sql, $params);
		
		$a = array();
		$a["curr_network_domain"] = $network_domain;
		$a["this"] = $this;
		$a["pagesize"] = $pagesize;
		$a["pageindex"] = $pageindex;
		$a["q"] = $q;
		$a["ds"] = $ds;
		$a["trend"] = $trend;
		return $this->render('JustsyBaseBundle:Reply:in.html.twig', $a);
  }
  
  public function outAction($network_domain)
  {
		$request = $this->getRequest();
		$user = $this->get('security.context')->getToken()->getUser();
		$curr_circle_id=$user->get_circle_id($network_domain);
		
		$pageindex = $request->get('pageindex');
		if ($pageindex){} else { $pageindex = 1; }
		$pagesize = 10;
		$maxlimit = $pagesize*10;
		$q = $request->get('q');
		
		$nick_nameWhere = ($q && $q != "" ? " c.nick_name like concat('%', ?, '%') " : " '1'<>? ");
		$contentWhere = ($q && $q != "" ? " b.conv_content like concat('%', ?, '%') " : " '1'<>? ");
		
		$sql = "select * from (
select b.conv_id, b.login_account, b.post_date, b.conv_type_id, b.conv_root_id, 
  case when length(b.conv_content)>90 then concat(substr(b.conv_content, 1, 27), '...') else b.conv_content end conv_content,
  b.post_to_group, b.post_to_circle, b.reply_to, b.comefrom,
  c.nick_name root_staff_name, c.photo_path root_photo_path, f_cal_date_section(b.post_date) post_date_d, cf.name comefrom_d,
  case when length(bb.conv_content)>90 then concat(substr(bb.conv_content, 1, 27), '...') else bb.conv_content end root_conv_content,
  d.nick_name reply_staff_name, bb.login_account root_login_account
from we_convers_list b 
inner join we_convers_list bb on bb.conv_id=b.conv_root_id
inner join we_staff c on c.login_account=bb.login_account 
left  join we_code_all_code cf on cf.id=b.comefrom and cf.class='来自于'
left  join we_staff d on d.login_account=b.reply_to 
where b.post_to_circle=? 
  and b.conv_type_id='99'
  and b.login_account=?
  and ($nick_nameWhere or $contentWhere)
order by b.post_date desc
limit 0, $maxlimit 
) as fffa
";
		$params = array();
		$params[] = (string)$curr_circle_id;
		$params[] = (string)$user->getUserName();
		$params[] = (string)$q;
		$params[] = (string)$q;
		
		$da = $this->get('we_data_access');
		
		$da->PageIndex = $pageindex - 1;
		$da->PageSize = $pagesize;
		$ds=$da->Getdata("we_convers_list", $sql, $params);
		
		$a = array();
		$a["curr_network_domain"] = $network_domain;
		$a["this"] = $this;
		$a["pagesize"] = $pagesize;
		$a["pageindex"] = $pageindex;
		$a["q"] = $q;
		$a["ds"] = $ds;
		return $this->render('JustsyBaseBundle:Reply:out.html.twig', $a);
  }
}
