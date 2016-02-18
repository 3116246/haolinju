<?php

namespace Justsy\BaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Login\UserSession;
use Justsy\BaseBundle\Login\UserProvider;

class JournalController extends Controller
{
	public function indexAction($network_domain)
	{
		$DataAccess=$this->get('we_data_access');
		$login_account=$this->get('security.context')->getToken()->getUser()->getUsername();
		$sql='select id,title,login_account, content ,post_time,bell_time from we_journal where login_account=? order by post_time desc limit 1';
		$dataset=$DataAccess->GetData('journal',$sql,array((string)$login_account));
		$frist_journal_id=(int)$dataset['journal']['recordcount']>0?$dataset['journal']['rows'][0]['id']:0;
		return $this->render('JustsyBaseBundle:Journal:index.html.twig',array
									(
		               'curr_network_domain'=> $network_domain,
		               'login_account'=> $login_account,
		               'frist_journal_id'=> $frist_journal_id
		               ));
	}
	public function titleAction($network_domain)
	{
		$DataAccess=$this->get('we_data_access');
		$DataAccess->PageSize=5;
		$pageindex=$this->getRequest()->get('pageindex');
		$pageindex=(int)$pageindex>=1?$pageindex-1:0;
		$DataAccess->PageIndex=$pageindex;
		$login_account=$this->get('security.context')->getToken()->getUser()->getUsername();
		$q=$this->getRequest()->get('q');
		if(!(isset($q)&&strlen($q)))
		{
			$sql="select id,title,login_account, content ,bell_time,DATE_FORMAT(post_time,'%Y-%m-%d') dt,DATE_FORMAT(post_time,'%H:%i') time,TO_DAYS(now())-TO_DAYS(post_time) isafter from we_journal where login_account=? order by post_time desc";
		  $dataset=$DataAccess->GetData('journal',$sql,array((string)$login_account));
		}
		else
		{
			$sql="select id, title,login_account, content ,bell_time,DATE_FORMAT(post_time,'%Y-%m-%d') dt,DATE_FORMAT(post_time,'%H:%i') time,TO_DAYS(now())-TO_DAYS(post_time) isafter from we_journal where login_account=? and title like ? order by post_time desc";
		  $dataset=$DataAccess->GetData('journal',$sql,array((string)$login_account,'%'.$q.'%'));
		}
		$data=array('recordcount'=>0,'rows'=>array());
		$recordcount=0;
		$rows=array();
		$pagecount=0;
		if(count($dataset['journal']['recordcount'])>0)
		{
			$recordcount=(int)$dataset['journal']['recordcount'];
			$rows				=$dataset['journal']['rows'];
			$pagecount  =ceil($recordcount/5);
		}
		return $this->render('JustsyBaseBundle:Journal:title.html.twig',array
									(
		               'curr_network_domain'=> $network_domain,
		               'recordcount'=> $recordcount,
		               'rows'				=> $rows,
		               'pagecount'	=> $pagecount,
		               'pageindex'	=> $pageindex+1,
		               'q'        	=> $q,
		               'login_account'=> $login_account
		               ));
	}
	public function contentAction($network_domain)
	{
		$DataAccess=$this->get('we_data_access');
	  $login_account=$this->get('security.context')->getToken()->getUser()->getUsername();
		$id=$this->getRequest()->get('id');
		if(is_null($id))
		{
			$sql='select id,title, content  from we_journal where login_account=?  order by post_time desc limit 1';
		  $dataset=$DataAccess->GetData('journal',$sql,array((string)$login_account));
		}
		else
		{
			$sql='select id,title, content  from we_journal where id=? and login_account=? limit 1';
			$dataset=$DataAccess->GetData('journal',$sql,array((string)$id,(string)$login_account));
		}
		$content=$dataset['journal']['recordcount']>0?$dataset['journal']['rows'][0]['content']:'';
		$id     =$dataset['journal']['recordcount']>0?$dataset['journal']['rows'][0]['id']:-1;
		$title  =$dataset['journal']['recordcount']>0?$dataset['journal']['rows'][0]['title']:'';
		return $this->render('JustsyBaseBundle:Journal:content.html.twig',array('curr_network_domain'=>$network_domain,'content'=>$content,'id'=>$id,'title'=>$title));
	}
	public function updateAction()
	{
		$DataAccess		 =$this->get('we_data_access');
		$id        		 =$this->getRequest()->get('id');
		//$title         =$this->getRequest()->get('title');
		$content   		 =$this->getRequest()->get('content');
		$datetime      =$this->getRequest()->get('datetime');
		$bell_time     =$this->getRequest()->get('bell_time');
		$type          =$this->getRequest()->get('_type');
		$title         =substr($content,0,20);
		$setbell       ='true';
		$openid        ='';
		$login_account =$this->get('security.context')->getToken()->getUser()->getUsername();
		$sql='select * from we_journal where id=? and login_account=?';
		$dataset=$DataAccess->GetData('journal',$sql,array((string)$id,(string)$login_account));
		if($dataset['journal']['recordcount']>0)
		{
			if($dataset['journal']['rows'][0]['bell_time']==$bell_time)$setbell='false';
			$sql='update we_journal set title=?, content=?,post_time=?,bell_time=? where id=? ';
			$dataexec=$DataAccess->ExecSQL($sql,array((string)$title,(string)$content,$datetime,$bell_time,(string)$id));
		}
		else
		{
			$id=(string)SysSeq::GetSeqNextValue($DataAccess,"we_journal","id");
			$sql='insert into we_journal(id,login_account,title,content,post_time,bell_time)values(?,?,?,?,?,?)';
			$dataexec=$DataAccess->ExecSQL($sql,array((string)$id,(string)$login_account,(string)$title,(string)$content,$datetime,$bell_time));
		}
		if($dataexec>0)
		{
			if($setbell=='true')
			{
				$sql="select openid from we_staff where login_account=?";
				$params=array($login_account);
				$ds=$DataAccess->Getdata('open',$sql,$params);
				$openid=$ds['open']['rows'][0]['openid'];
			}
			$res= new Response(json_encode(array("success"=>1,"_type"=> $type,'bell_time'=>$bell_time,'openid'=>$openid,'id'=>$id,'setbell'=>$setbell,'content'=>$content)));
		}
		else
		{
			$res=new Response(json_encode(array("success"=>0,"_type"=> $type)));
		}
		$res->headers->set('Content-Type', 'text/json');
		return $res;
	}
	public function deleteAction()
	{
		$DataAccess=$this->get('we_data_access');
		$id=$this->getRequest()->get('id');
		$sql='delete we_journal from we_journal where id=?';
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
	public function searchAction()
	{
		$DataAccess=$this->get('we_data_access');
		$q=$this->getRequest()->get('q');
		$login_account=$this->get('security.context')->getToken()->getUser()->getUsername();
		if(isset($q)&&strlen($q))
		{
			$sql="select title,post_time, content,bell_time from we_journal where title like ? order by post_time limit 20";
			$dataset=$DataAcess->GetData('journal',$sql,array('%'.$q.'%'));
		}
		else
		{
			$sql="select title,post_time, content,bell_time from we_journal order by post_time limit 20";
			$dataset=$DataAccess->GetData('journal',$sql);
		}
		$data=count($dataset['journal']['recordcount'])>0?$dataset['journal']['rows']:array();
    $res=new Response(json_encode($data));
    $res->headers->set('Content-Type','text/json');
    return $res;
	}
	public function startBellAction()
	{
		$ec=new \Justsy\OpenAPIBundle\Controller\ApiController;
		$type=$this->getRequest()->get('type');
		$ec->setContainer($this->container);
		$re=$ec->timerRemindTaskAction()->getContent();
		if(json_decode($re)->s=='1')
		{
			$res=new Response(json_encode(array('s'=>1,'message'=>'','_type'=>$type)));
      $res->headers->set('Content-Type','text/json');
      return $res;
		}
		else
		{
			$res=new Response(json_encode(array('s'=>0,'message'=>"提醒未设置成功。<a href=''>重试</a>")));
      $res->headers->set('Content-Type','text/json');
      return $res;
		}
	}
}