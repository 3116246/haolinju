<?php

namespace Justsy\BaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Common\Utils;

class HelpController extends Controller
{
  public function indexAction($id)
  {
    $da = $this->get("we_data_access");
    $menu_items = array();
    $sql = "select '00a' as classify_id,'热点问题' as classify_name,'00a' as parent_classify_id from dual
      union all select '00b','快速入门','00b' from dual union all select * from faq_class";
    $ds = $da->GetData('faq_class',$sql);
    if ($ds && $ds['faq_class']['recordcount']>0)
    {
      $root = array_filter($ds['faq_class']['rows'],function($v)
      {
        return $v['classify_id'] == $v['parent_classify_id'];
      });
      $i = 0;
      foreach($root as $key => $value)
      {
        $menu_items[$i]['parent'] = $value;
        $menu_items[$i]['child'] = array_filter($ds['faq_class']['rows'],function($v) use($value)
        {
          return $v['parent_classify_id'] == $value['classify_id'] && $v['classify_id'] != $value['classify_id'];
        });
        $i++;
      }
    }
    
    return $this->render('JustsyBaseBundle:Help:help_index.html.twig', array(
      'menu_items' => $menu_items,
      'id' => $id
    ));
  }
  public function getListAction($type, $pageindex)
  {
    $da = $this->get("we_data_access");
    $title_name = '';
    $sql = "select faq_title,faq_content from faq_content where ifnull(review_date,'')!='' ";
    $sql_count = "select count(1) as cnt from faq_content where ifnull(review_date,'')!='' ";
    $start = ($pageindex-1)*5;
    if ($type=="00a")
    {
      $title_name = '热点问题';
      $sql .= "and is_hover='1' order by order_num limit $start,5";
      $sql_count .= "and is_hover='1'";
      $ds = $da->GetData('faq_content',$sql);
      $ds_count = $da->GetData('faq_content_count',$sql_count);
    }
    else if ($type=="00b")
    {
      $title_name = '快速入门';
      $sql .= "and is_fast_learn='1' order by order_num limit $start,5";
      $sql_count .= "and is_fast_learn='1'";
      $ds = $da->GetData('faq_content',$sql);
      $ds_count = $da->GetData('faq_content_count',$sql_count);
    }
    else
    {
      $ds = $da->GetData('tn',"select classify_name from faq_class where classify_id=?",array((string)$type));
      if ($ds && $ds['tn']['recordcount']>0) $title_name = $ds['tn']['rows'][0]['classify_name'];
      $sql .= "and class_id=? order by order_num limit $start,5";
      $sql_count .= "and class_id=?";
      $ds = $da->GetData('faq_content',$sql,array((string)$type));
      $ds_count = $da->GetData('faq_content_count',$sql_count,array((string)$type));
    }
    if ($ds_count)
    {
      $pages = ceil($ds_count['faq_content_count']['rows'][0]['cnt']/5);
    }
    else
    {
      $pages = 0;
    }
    
    return $this->render('JustsyBaseBundle:Help:help_list.html.twig', array(
      'title_name' => $title_name,
      'rows' => $ds['faq_content']['rows'],
      'pagecount' => $pages,
      'pageindex' => $pageindex
    ));
  }
	
	public function getHotHelpScrollAction($network_domain)
	{
		$user = $this->get('security.context')->getToken()->getUser();
		$curr_circle_id=$user->get_circle_id($network_domain);
	  	  
//	  $sql = "select bulletin_id, SUBSTR(bulletin_desc, 1, 100) bulletin_desc 
//from we_bulletin 
//where circle_id=? and group_id='ALL' order by bulletin_date desc limit 0, 1";
//    $params = array();
//    $params[] = (string)$curr_circle_id;
//    
//    $da = $this->get('we_data_access');
//    $ds = $da->Getdata('we_bulletin', $sql, $params);
    
    $a = array();
//    $a['ds'] = $ds;
    
    return $this->render('JustsyBaseBundle:Help:HotHelpScroll.html.twig',$a);
	}
	
	public function showAction($id)
	{
    $da = $this->get("we_data_access");
    $title_name = '热点问题';
    //$id对应的问题，所在的页数
    $sql = "select id from faq_content where ifnull(review_date,'')!='' and is_hover='1' order by order_num";
    $ds = $da->GetData('faq_content',$sql);
    $pageindex = 0;
    if ($ds && $ds['faq_content']['recordcount']>0)
    {
      $pageindex = array_search(array('id' => $id),$ds['faq_content']['rows']);
    }
    $pageindex = ceil(($pageindex+1)/5);
    
    $sql = "select faq_title,faq_content from faq_content where ifnull(review_date,'')!='' ";
    $sql_count = "select count(1) as cnt from faq_content where ifnull(review_date,'')!='' ";
    $start = ($pageindex-1)*5;
    $sql .= "and is_hover='1' order by order_num limit $start,5";
    $sql_count .= "and is_hover='1'";
    $ds = $da->GetData('faq_content',$sql);
    $ds_count = $da->GetData('faq_content_count',$sql_count);
    if ($ds_count)
    {
      $pages = ceil($ds_count['faq_content_count']['rows'][0]['cnt']/5);
    }
    else
    {
      $pages = 0;
    }
    
    return $this->render('JustsyBaseBundle:Help:help_list.html.twig', array(
      'title_name' => $title_name,
      'rows' => $ds['faq_content']['rows'],
      'pagecount' => $pages,
      'pageindex' => $pageindex
    ));
	}

	public function microappAction()
	{
		$menudata = array(
			"微应用简介" => "JustsyBaseBundle:Help:microapp_wyyjj.html.twig",
			"业务系统接入" => "JustsyBaseBundle:Help:microapp_ywxtjr.html.twig",
			"典型案例" => "JustsyBaseBundle:Help:microapp_dxal.html.twig",
			"接入流程" => "JustsyBaseBundle:Help:microapp_jrlc.html.twig",
			"微应用注册" => "JustsyBaseBundle:Help:microapp_wyyzc.html.twig",
			"token获取" => "JustsyBaseBundle:Help:microapp_token.html.twig",
			"图片上传" => "JustsyBaseBundle:Help:microapp_tpsc.html.twig",
			"关注成员列表" => "JustsyBaseBundle:Help:microapp_gzcylb.html.twig",
			"人员详细信息" => "JustsyBaseBundle:Help:microapp_ryxxxx.html.twig",
			"纯文本消息" => "JustsyBaseBundle:Help:microapp_cwbxx.html.twig",
			"单图文消息" => "JustsyBaseBundle:Help:microapp_dtwxx.html.twig",
			"单图文消息(文件流)" => "JustsyBaseBundle:Help:microapp_dtwxxwjl.html.twig",
			"多图文消息" => "JustsyBaseBundle:Help:microapp_duotwxx.html.twig",
      "业务代理简介" => "JustsyBaseBundle:Help:microapp_ywdljj.html.twig",
      "微应用示例" => "JustsyBaseBundle:Help:microapp_wyysl.html.twig");

        $request = $this->getRequest();
        $title = $request->get("title");
        if (empty($menudata[$title])) $title = "微应用简介";
        $helptwig = $menudata[$title];
	    return $this->render('JustsyBaseBundle:Help:microapp.html.twig', array(
	    	"title" => $title,
	    	"helptwig" => $helptwig));
	}
}