<?php

namespace Justsy\BaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Login\UserSession;
use Justsy\BaseBundle\Login\UserProvider;

class PersonTaskController extends Controller
{
	public function indexAction($network_domain)
	{
		$DataAccess=$this->get('we_data_access');
		$login_account=$this->get('security.context')->getToken()->getUser()->getUsername();
		return $this->render('JustsyBaseBundle:PersonTask:index.html.twig',array
									(
		               'curr_network_domain'=> $network_domain,
		               'login_account'			=> $login_account,
		               'day'                => date('m')
		               ));
	}

	public function updateAction($network_domain)
	{
		$DataAccess		 =$this->get('we_data_access');
		$id        		 =$this->getRequest()->get('id');
		$content   		 =$this->getRequest()->get('content');
		$start_time    =$this->getRequest()->get('start_time');
		$end_time      =$this->getRequest()->get('end_time');
		$title         =substr($content,0,20);
		$login_account =$this->get('security.context')->getToken()->getUser()->getUsername();
		$sql='select 1 from we_persontask where id=? and login_account=?';
		$dataset=$DataAccess->GetData('we_persontask',$sql,array((string)$id,(string)$login_account));
		if($dataset['we_persontask']['recordcount']>0)
		{
			$sql='update we_persontask set title=?, content=?,start_time=?,end_time=? where id=? ';
			$dataexec=$DataAccess->ExecSQL($sql,array((string)$title,(string)$content,(string)$start_time,(string)$end_time,(string)$id));
		}
		else
		{
			$id=(string)SysSeq::GetSeqNextValue($DataAccess,"we_persontask","id");
			$sql='insert into we_persontask(id,login_account,title,content,start_time,end_time,is_finish)values(?,?,?,?,?,?,"0")';
			$dataexec=$DataAccess->ExecSQL($sql,array((string)$id,(string)$login_account,(string)$title,(string)$content,(string)$start_time,(string)$end_time));
		}
		if($dataexec>0)
		{
			$res= new Response('{"success":1}');
		}
		else
		{
			$res=new Response('{"success":0}');
		}
		$res->headers->set('Content-Type', 'text/json');
		return $res;
	}
	public function deleteAction($network_domain)
	{
		$DataAccess=$this->get('we_data_access');
		$id=$this->getRequest()->get('id');
		$sql='delete we_persontask from we_persontask where id=?';
		$dataexec=$DataAccess->ExecSQL($sql,array((string)$id));
		if($dataexec>0)
		{
			$res=new Response('{"success":1}');
		}
		else
		{
			$res=new Response('{"success":0}');
		}
		$res->headers->set('Content-Type','text/json');
		return $res;
  }
  public function next7daystaskAction($network_domain)
  {
  	$DataAccess=$this->get('we_data_access');
  	$pageIndex=$this->getRequest()->get('pageindex');
  	if(!(isset($pageIndex)&&strlen($pageIndex)))
  	{
  		$pageIndex=1;
  	}
  	$DataAccess->PageIndex=$pageIndex-1;
  	$DataAccess->PageSize=11;
  	$login_account=$this->get('security.context')->getToken()->getUser()->getUsername();
  	$sql='select id,login_account,title,content,date_format(start_time,"%Y-%m-%d %H:%i") as start_time,date_format(end_time,"%H:%i") as end_time,( case(is_finish) when "0" then "进行中" when "1" then "已完成" end ) as is_finish,to_days(start_time)-to_days(now()) as is_after_start_time,to_days(end_time)-to_days(now()) as is_after_end_time from we_persontask where login_account=? and ( yearweek(date_format(start_time,"%Y-%m-%d"))=yearweek(now()) or yearweek(date_format(end_time,"%Y-%m-%d"))=yearweek(now()) )  order by start_time asc';
  	$dataset=$DataAccess->GetData('we_persontask',$sql,array((string)$login_account));
  	$recordcount  =(int)$dataset['we_persontask']['recordcount']>0?(int)$dataset['we_persontask']['recordcount']:0;
  	$pagecount=ceil($recordcount/$DataAccess->PageSize);
  	$next7daystask=(int)$dataset['we_persontask']['recordcount']>0?$dataset['we_persontask']['rows']:array();
  	return $this->render('JustsyBaseBundle:PersonTask:right.html.twig',array
									(
		               'curr_network_domain'=> $network_domain,
		               'login_account'=> $login_account,
		               'next7daystask'=> $next7daystask,
		               'pagecount'    => $pagecount,
		               'pageindex'    => $pageIndex
		               ));
  }
  public function searchAction($network_domain)
  {
  	$DataAccess=$this->get('we_data_access');
  	$pageIndex=$this->getRequest()->get('pageindex');
  	if(!(isset($pageIndex)&&strlen($pageIndex)))
  	{
  		$pageIndex=1;
  	}
  	$DataAccess->PageIndex=$pageIndex-1;
  	$DataAccess->PageSize=5;
  	$login_account=$this->get('security.context')->getToken()->getUser()->getUsername();
  	$search_date  =$this->getRequest()->get('search_date');
  	$sql='select id,login_account,title,content,date_format(start_time,"%H:%i") as start_time,date_format(end_time,"%H:%i") as end_time,(case is_finish  when "0" then "进行中" when "1" then "已完成" end ) as is_finish,to_days(start_time)-to_days(now()) as is_after_start_time,to_days(end_time)-to_days(now()) as is_after_end_time from we_persontask where login_account=? and ( cast(date_format(start_time,"%Y-%m-%d") as char)=? or cast(date_format(end_time,"%Y-%m-%d") as char)=? )';
  	//$sql='select id,login_account,title,content,date_format(start_time,"%H:%i") as start_time,date_format(end_time,"%H:%i") as end_time,(case is_finish  when "0" then "进行中" when "1" then "已完成" end ) as is_finish from we_persontask where login_account=? and start_time>? and end_time>? order by start_time asc';
  	$dataset=$DataAccess->GetData('we_persontask',$sql,array((string)$login_account,(string)$search_date,(string)$search_date));
  	$data=(int)$dataset['we_persontask']['recordcount']>0?$dataset['we_persontask']['rows']:array();
  	$recordcount  =(int)$dataset['we_persontask']['recordcount']>0?(int)$dataset['we_persontask']['recordcount']:0;
  	$pagecount=ceil($recordcount/$DataAccess->PageSize);
  	return $this->render('JustsyBaseBundle:PersonTask:table.html.twig',array(
  	              'curr_network_domain'=> $network_domain,
  	              'login_account'   	 => $login_account,
  	              'data'               => $data,
  	              'pagecount'    			 => $pagecount,
		              'pageindex'    			 => $pageIndex
  	              ));
  }
  public function get_one_task_contentAction($network_domain)
  {
  	$DataAccess=$this->get('we_data_access');
  	$login_account=$this->get('security.context')->getToken()->getUser()->getUsername();
  	$id=$this->getRequest()->get('id');
  	if(!(isset($id)&&strlen($id)))
  	{
  		$res= new Response('');
  	}
  	$sql='select id,title,content,date_format(start_time,"%Y-%m-%d %H:%i") as start_time,date_format(end_time,"%Y-%m-%d %H:%i") as end_time,is_finish from we_persontask where id=? and login_account=? limit 1';
  	$dataset=$DataAccess->GetData('we_persontask',$sql,array((string)$id,(string)$login_account));
  	if((int)$dataset['we_persontask']['recordcount']>0)
  	{
  		$res=$this->render('JustsyBaseBundle:PersonTask:taskcard.html.twig',array(
  		                    'curr_network_domain'=> $network_domain,
  		                    'login_account'      => $login_account,
  		                    'id'                 => $dataset['we_persontask']['rows'][0]['id'],
  		                    'title'              => $dataset['we_persontask']['rows'][0]['title'],
  		                    'content'            => $dataset['we_persontask']['rows'][0]['content'],
  		                    'start_time'            => $dataset['we_persontask']['rows'][0]['start_time'],
  		                    'end_time'            => $dataset['we_persontask']['rows'][0]['end_time'],
  		                    'is_finish'            => $dataset['we_persontask']['rows'][0]['is_finish'],
  		                  ));
  	}
  	else
  	{
  		$res=new Response('');
  	}
  	return $res;
  }
  public function state_settingAction($network_domain)
  {
  	$DataAccess=$this->get('we_data_access');
  	$login_account=$this->get('security.context')->getToken()->getUser()->getUsername();
  	$id=$this->getRequest()->get('id');
  	$is_finish=$this->getRequest()->get('is_finish');
  	if(!(isset($id)&&strlen($id)&&isset($is_finish)&&in_array($is_finish,array('0','1'))))
  	{
  		$res=new Response('{"success":0,"msg":""}','text/josn');
  	}
  	$sql='update we_persontask set is_finish=? where id=? and login_account=? ';
  	$dataexec=$DataAccess->ExecSQL($sql,array((string)$is_finish,(string)$id,(string)$login_account));
  	if($dataexec>0)
  	{
  		$response=new Response('{"success":1,"msg":""}');
  	}
  	else
  	{
  		$response=new Response('{"success":0,"msg":""}');
  	}
  	$response->headers->set('Content-Type','text/json');
    $response->headers->set('charset', 'utf-8');
  	return $response;
  }
}