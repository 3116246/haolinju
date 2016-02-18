<?php
namespace Justsy\BaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Login\UserSession;
use Justsy\BaseBundle\Management\Enterprise;

class EnterpriseController extends Controller
{
	public function indexAction()
	{
		return $this->render("JustsyBaseBundle:Enterprise:index.html.twig");
	}
	public function searchAction()
	{
		$da = $this->get('we_data_access');
  	$request = $this->getRequest();
  	$user = $this->get('security.context')->getToken()->getUser();
  	$industry=$request->get('industry');
  	$ename=$request->get('ename');
  	$area=$request->get('area');
  	$create_date=$request->get('create_date');
  	$staff_num=$request->get('staff_num');
  	
  	$where='';
  	$params=array();
  	
  	
  	$enterprise=new Enterprise($da,$this->get('logger'),$this->container);
  	$rows=$enterprise->search($where,$params);
  	$response=new Response(json_encode($rows));
	  $response->headers->set('Content-Type','Application/json');
	  return $response;
	}
	public function attenAction()
	{
		$da = $this->get('we_data_access');
  	$request = $this->getRequest();
  	$user = $this->get('security.context')->getToken()->getUser();
  	$atten=$request->get('atten');
  	$eno=$request->get('eno');
  	$enterprise=new Enterprise($da,$this->get('logger'),$this->container);
  	$bool=false;
  	if($atten=='1')
  		$bool=$enterprise->attenEno($user->getUserName(),$eno);
  	else if($atten=='0')
  		$bool=$enterprise->cancelatten($user->getUserName(),$eno);
    $re=($bool ? array('s'=>1,'m'=>'') : array('s'=>0,'m'=>'操作失败'));
  	$response=new Response(json_encode($re));
	  $response->headers->set('Content-Type','Application/json');
	  return $response;
	}
	public function getEnterpriseCardAction()
	{
		$da = $this->get('we_data_access');
		$user = $this->get('security.context')->getToken()->getUser();
  	$request = $this->getRequest();
  	$eno=$request->get('eno');
  	$enterprise=new Enterprise($da,$this->get('logger'),$this->container);
  	$row=$enterprise->getInfoByEno($user->getUserName(),$eno);
  	//获取关注成员
  	$atten = $enterprise->getAtten($eno);
  	//获取标签
  	$tag = new \Justsy\BaseBundle\Management\UserTag($da,$this->get("logger"));
  	$tags = $tag->getentag($eno);
		return $this->render("JustsyBaseBundle:Enterprise:enterprise_card.html.twig",array('row'=> $row,'atten'=> $atten,'tag'=> $tags));
	}
}
?>