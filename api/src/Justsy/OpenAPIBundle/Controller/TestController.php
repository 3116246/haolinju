<?php

namespace Justsy\OpenAPIBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Login\UserProvider;
use Justsy\BaseBundle\Login\UserSession;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Management\Staff;

class TestController extends Controller
{

	public function makePwdAction()
	{
		$request = $this->get("request");
		$acc = $request->get("account");
		$pwd = $request->get("pwd");
		$user = new UserSession($acc, $pwd, $acc, array("ROLE_USER"));
      	$factory = $this->get("security.encoder_factory");
      	$encoder = $factory->getEncoder($user);
      	$pwd = $encoder->encodePassword($pwd,$user->getSalt());
      	$response = new Response($pwd);
		$response->headers->set('Content-Type', 'text/html');
		return $response;
	}
	
	public function getListDataAction()
	{
	  	$request = $this->get("request");
	  	$retuenAry = $request->get("resulttype");
	  	$FILE_WEBSERVER_URL = $this->container->getParameter('open_api_url');
	  	$re=array();
	  	array_push($re,array(
	  		"id"=>"1001",
	  		"title"=>"销售单",
	  		"icon"=>$FILE_WEBSERVER_URL."/bundles/fafatimewebase/images/demo/temp_icon_dg1.png"
	  	));

	  	array_push($re,array(
	  		"id"=>"1002",
	  		"title"=>"促销",
	  		"icon"=>$FILE_WEBSERVER_URL."/bundles/fafatimewebase/images/demo/temp_icon_dg2.png"
	  	));
	  	

	  	array_push($re,array(
	  		"id"=>"1003",
	  		"title"=>"销售量",
	  		"icon"=>$FILE_WEBSERVER_URL."/bundles/fafatimewebase/images/demo/temp_icon_gl1.png"
	  	));
	  	
	  	array_push($re,array(
	  		"id"=>"1004",
	  		"title"=>"热卖品",
	  		"icon"=>$FILE_WEBSERVER_URL."/bundles/fafatimewebase/images/demo/temp_icon_gl2.png"
	  	));
	  	
	  	array_push($re,array(
	  		"id"=>"1005",
	  		"title"=>"综合分析",
	  		"icon"=>$FILE_WEBSERVER_URL."/bundles/fafatimewebase/images/demo/temp_icon_sj1.png"
	  	));
	  	$result = array("number"=>5,"total"=>5,"listitems" => $re);
	  	if($retuenAry=="list")
	  	{
	  		$result = $re;
	  	}
	  	$response = new Response($request->get('callback') ? $request->get('callback')."(".json_encode($result).");" : json_encode($result));
		$response->headers->set('Content-Type', 'text/json');
		return $response;	
	}
	//商品
	public function getProductListAction()
	{
		$request = $this->get("request");
		$term_id=trim($request->get("term_id"));
		$site_id=trim($request->get("site_id"));
		$pagesize=trim($request->get("pagesize"));
		$pageindex=trim($request->get("pageindex"));
		if(!empty($site_id)){
			if($site_id=='1')
				$site_id='';
			else
				$site_id = $site_id."_";
		}
		if(empty($returnnum))
			$returnnum = 10;
		$da = $this->get("we_data_access_wordpress");
		$da->PageSize=empty($pagesize)? 10:(int)$pagesize;
		$da->PageIndex=empty($pageindex)? 0:((int)$pageindex-1);
		$sql="select distinct t.ID as product_id,t.post_title as product_name,(select c.guid from cms801_".$site_id."posts c where position('image' in c.post_mime_type)!=0 and c.post_parent=t.ID limit 0,1) as product_img,(select d.meta_value from cms801_".$site_id."postmeta d where d.meta_key='_wpsc_price' and d.post_id=t.ID) as product_price,(select e.meta_value from cms801_".$site_id."postmeta e where e.meta_key='_wpsc_special_price' and e.post_id=t.ID) as product_sale_price,(select case when f.meta_value is null or meta_value='' then 'N/A' else f.meta_value end stock from cms801_".$site_id."postmeta f where f.meta_key='_wpsc_stock' and f.post_id=t.ID) as product_stock from cms801_".$site_id."posts t,cms801_".$site_id."term_taxonomy a,cms801_".$site_id."term_relationships b where t.post_status='publish' and t.post_type='wpsc-product' ".(empty($term_id)?"":" and  a.term_id=? and a.term_taxonomy_id=b.term_taxonomy_id and b.object_id=t.id")." order by t.post_date desc";
		$params=array();
		if(!empty($term_id)){
			$params=array($term_id);
		}
		$ds=$da->Getdata('list',$sql,$params);
		$total=$ds['list']['recordcount'];
		$rows=$ds['list']['rows'];
		return $this->responseJson(array('total'=> $total,'rows'=> $rows));
	}
	public function getWordpressHotList()
	{
		$request = $this->get("request");
		$termtype=trim($request->get("termtype"));
		$term_id=trim($request->get("term_id"));
		$returnnum=trim($request->get("returnnum"));
		$site_id=trim($request->get("site_id"));
		$openid=trim($request->get("openid"));
		if(!empty($site_id)){
			if($site_id=='1')
				$site_id='';
			else
				$site_id = $site_id."_";
		}
		if(empty($returnnum))
			$returnnum = 10;
		$da = $this->get("we_data_access_wordpress");
		$da->pageSize=(int)$returnnum;
		
		$totalsql = "select count(1) cnt from cms801_".$site_id."posts t,cms801_".$site_id."term_taxonomy a,cms801_".$site_id."term_relationships b where exists(select 1 from cms801_".$site_id."post_user g where g.term_id='12' and openid=? and g.end_date>now()) and a.term_id=? and a.term_taxonomy_id=b.term_taxonomy_id and b.object_id=t.id and t.post_status!='trash'";
		$sql = " select t.*,case (select 1 from cms801_".$site_id."post_user e where e.post_ID=t.ID and e.openid=? and e.relation='act' and e.term_id=?) when 1 then '1' else '0' end selected,(select count(1) from cms801_".$site_id."comments c where c.comment_post_ID=t.ID) as comment_num,(select count(1) from cms801_wp_copy d where d.sourceid=t.ID) as copy_num,(select display_name from cms801_users where id=t.post_author) author from cms801_".$site_id."posts t,cms801_".$site_id."term_taxonomy a,cms801_".$site_id."term_relationships b where exists(select 1 from cms801_".$site_id."post_user g where g.term_id='12' and openid=? and g.end_date>now()) and a.term_id=? and a.term_taxonomy_id=b.term_taxonomy_id and b.object_id=t.id and t.post_status!='trash' order by t.post_date desc limit 0,".$returnnum;
		$params1=array(
				$openid,
			(int)$term_id,
			$openid,
			(int)$term_id
			);
		$ds = $da->GetData("t",$sql,$params1);
		$params2=array(
				$openid,
				(int)$term_id
			);
		$total_ds = $da->GetData("t2",$totalsql,$params2);
		$data = array();
		$list=array();
		$data["number"] = count($ds["t"]["rows"]);
		$data["total"] = $total_ds["t2"]["rows"][0]["cnt"];
		for($i=0;$i<count($ds["t"]["rows"]);$i++)
		{
			$row = $ds["t"]["rows"][$i];
			$content = $row["post_content"];
			//获取图片
			//$sql = "select guid from cms801_".$site_id."posts where post_parent=? and post_type='attachment' order by post_date desc limit 0,1";
			//$ds_img = $da->GetData("t1",$sql,array(
			//	(int)$row["ID"]
			//));	
			$matches = array();
			//preg_match("/(?<=src/s*=/s*[/'/""]?)(?<url>[http/:////]?[^'""]+)/i", $content, $matches);
			preg_match('/<img.+src=\"?(.+\.(jpg|gif|bmp|bnp|png))\"?.+>/i',$content,$match);
			$imgUrl = count($match)>=1 ? $match[1] : "";
			if($termtype=="news")
			{
				$content = preg_replace("/<[^>]+>/", "", $content);
				$summary = mb_substr(preg_replace("/\r\n/", "",$content),0,40,'utf-8');
				$list[]=array(
					"summary"=>$summary,
					"title" => $row["post_title"],
					"id" =>$row["ID"],
					"subtitle" => $row["author"]."  ". $row["post_date"],
					"icon" =>$imgUrl,
					"comment_num"=> $row["comment_num"],
					"copy_num"=> $row["copy_num"],
					"post_date"=> $row["post_date"],
					"selected"=> $row["selected"]
				);
			}
			else if($termtype=="picnews")
			{
				$list[]=array(
					"title" => $row["post_title"],
					"id" =>$row["ID"],
					"icon" =>$imgUrl,
					"comment_num"=> $row["comment_num"],
					"copy_num"=> $row["copy_num"],
					"post_date"=> $row["post_date"],
					"selected"=> $row["selected"]
				);
			}
		}
		$data["listitems"] = $list;
		return $data;
	}
	public function getWordpressWaiMeiList()
	{
		$request = $this->get("request");
		$termtype=trim($request->get("termtype"));
		$term_id=trim($request->get("term_id"));
		$returnnum=trim($request->get("returnnum"));
		$site_id=trim($request->get("site_id"));
		$openid=trim($request->get("openid"));
		if(!empty($site_id)){
			if($site_id=='1')
				$site_id='';
			else
				$site_id = $site_id."_";
		}
		if(empty($returnnum))
			$returnnum = 10;
		$da = $this->get("we_data_access_wordpress");
		$data=array();
		$totalsql = "select count(1) cnt from cms801_".$site_id."posts t,cms801_".$site_id."term_taxonomy a,cms801_".$site_id."term_relationships b where a.term_id=? and a.term_taxonomy_id=b.term_taxonomy_id and b.object_id=t.id and FROM_UNIXTIME(UNIX_TIMESTAMP(t.post_date)+TIME_TO_SEC('12:00:00'))>now() and t.post_status!='trash'";
		$sql = " select t.*,(select count(1) from cms801_".$site_id."comments c where c.comment_post_ID=t.ID) as comment_num,(select count(1) from cms801_wp_copy d where d.sourceid=t.ID) as copy_num,(select display_name from cms801_users where id=t.post_author) author from cms801_".$site_id."posts t,cms801_".$site_id."term_taxonomy a,cms801_".$site_id."term_relationships b where a.term_id=? and a.term_taxonomy_id=b.term_taxonomy_id and b.object_id=t.id and FROM_UNIXTIME(UNIX_TIMESTAMP(t.post_date)+TIME_TO_SEC('12:00:00'))>now() and t.post_status!='trash' order by t.post_date desc limit 0,".$returnnum;
		$params1=array((int)$term_id);
		$ds = $da->GetData("t",$sql,$params1);
		$params2=array(
				(int)$term_id
			);
		$total_ds = $da->GetData("t2",$totalsql,$params2);

		$data = array();
		$list=array();
		$data["number"] = count($ds["t"]["rows"]);
		$data["total"] = $total_ds["t2"]["rows"][0]["cnt"];
		for($i=0;$i<count($ds["t"]["rows"]);$i++)
		{
			$row = $ds["t"]["rows"][$i];
			$content = $row["post_content"];
			//获取图片
			//$sql = "select guid from cms801_".$site_id."posts where post_parent=? and post_type='attachment' order by post_date desc limit 0,1";
			//$ds_img = $da->GetData("t1",$sql,array(
			//	(int)$row["ID"]
			//));	
			$matches = array();
			//preg_match("/(?<=src/s*=/s*[/'/""]?)(?<url>[http/:////]?[^'""]+)/i", $content, $matches);
			preg_match('/<img.+src=\"?(.+\.(jpg|gif|bmp|bnp|png))\"?.+>/i',$content,$match);
			$imgUrl = count($match)>=1 ? $match[1] : "";
			if($termtype=="news")
			{
				$content = preg_replace("/<[^>]+>/", "", $content);
				$summary = mb_substr(preg_replace("/\r\n/", "",$content),0,40,'utf-8');
				$list[]=array(
					"summary"=>$summary,
					"title" => $row["post_title"],
					"id" =>$row["ID"],
					"subtitle" => $row["author"]."  ". $row["post_date"],
					"icon" =>$imgUrl,
					"comment_num"=> $row["comment_num"],
					"copy_num"=> $row["copy_num"],
					"post_date"=> $row["post_date"]
				);
			}
			else if($termtype=="picnews")
			{
				$list[]=array(
					"title" => $row["post_title"],
					"id" =>$row["ID"],
					"icon" =>$imgUrl,
					"comment_num"=> $row["comment_num"],
					"copy_num"=> $row["copy_num"],
					"post_date"=> $row["post_date"]
				);
			}
			if($term_id=="4"){
				$list[count($list)-1]["post_content"]=$row["post_content"];
			}
		}
		$data["listitems"] = $list;
		return $data;
	}
	public function getWordpressListAction()
	{
		$request = $this->get("request");
		$termtype=trim($request->get("termtype"));
		$term_id=trim($request->get("term_id"));
		$returnnum=trim($request->get("returnnum"));
		$site_id=trim($request->get("site_id"));
		$openid=trim($request->get("openid"));
		if(!empty($site_id)){
			if($site_id=='1')
				$site_id='';
			else
				$site_id = $site_id."_";
		}
		if(empty($returnnum))
			$returnnum = 10;
		$da = $this->get("we_data_access_wordpress");
		$data=array();
		if($term_id=="12"){
			$data=$this->getWordpressHotList();
		}
		else if($term_id=="3" || $term_id=="4"){
			$data=$this->getWordpressWaiMeiList();
		}
		else{
			$totalsql = "select count(1) cnt from cms801_".$site_id."posts t,cms801_".$site_id."term_taxonomy a,cms801_".$site_id."term_relationships b where a.term_id=? and a.term_taxonomy_id=b.term_taxonomy_id and b.object_id=t.id and t.post_status!='trash'";
		$sql = " select t.*,(select count(1) from cms801_".$site_id."comments c where c.comment_post_ID=t.ID) as comment_num,(select count(1) from cms801_wp_copy d where d.sourceid=t.ID) as copy_num,(select display_name from cms801_users where id=t.post_author) author from cms801_".$site_id."posts t,cms801_".$site_id."term_taxonomy a,cms801_".$site_id."term_relationships b where a.term_id=? and a.term_taxonomy_id=b.term_taxonomy_id and b.object_id=t.id and t.post_status!='trash' order by t.post_date desc limit 0,".$returnnum;
		$params1=array((int)$term_id);
		$ds = $da->GetData("t",$sql,$params1);
		$params2=array(
				(int)$term_id
			);
		$total_ds = $da->GetData("t2",$totalsql,$params2);
		$data = array();
		$list=array();
		$data["number"] = count($ds["t"]["rows"]);
		$data["total"] = $total_ds["t2"]["rows"][0]["cnt"];
		for($i=0;$i<count($ds["t"]["rows"]);$i++)
		{
			$row = $ds["t"]["rows"][$i];
			$content = $row["post_content"];
			//获取图片
			//$sql = "select guid from cms801_".$site_id."posts where post_parent=? and post_type='attachment' order by post_date desc limit 0,1";
			//$ds_img = $da->GetData("t1",$sql,array(
			//	(int)$row["ID"]
			//));	
			$matches = array();
			//preg_match("/(?<=src/s*=/s*[/'/""]?)(?<url>[http/:////]?[^'""]+)/i", $content, $matches);
			preg_match('/<img.+src=\"?(.+\.(jpg|gif|bmp|bnp|png))\"?.+>/i',$content,$match);
			$imgUrl = count($match)>=1 ? $match[1] : "";
			if($termtype=="news")
			{
				$content = preg_replace("/<[^>]+>/", "", $content);
				$summary = mb_substr(preg_replace("/\r\n/", "",$content),0,40,'utf-8');
				$list[]=array(
					"summary"=>$summary,
					"title" => $row["post_title"],
					"id" =>$row["ID"],
					"subtitle" => $row["author"]."  ". $row["post_date"],
					"icon" =>$imgUrl,
					"comment_num"=> $row["comment_num"],
					"copy_num"=> $row["copy_num"],
					"post_date"=> $row["post_date"]
				);
			}
			else if($termtype=="picnews")
			{
				$list[]=array(
					"title" => $row["post_title"],
					"id" =>$row["ID"],
					"icon" =>$imgUrl,
					"comment_num"=> $row["comment_num"],
					"copy_num"=> $row["copy_num"],
					"post_date"=> $row["post_date"]
				);
			}
			if($term_id=="47"){
				$list[count($list)-1]["post_content"]=$row["post_content"];
			}
		}
		$data["listitems"] = $list;
		}
		return $this->responseJson($data);
	}
	
	public function getWordpressDetailAction(){
		$request = $this->get("request");
		$news_id=$request->get("id");
		$site_id=trim($request->get("site_id"));
		if(!empty($site_id)){
			if($site_id=='1')
				$site_id='';
			else
				$site_id = $site_id."_";
		}	
		$da = $this->get("we_data_access_wordpress");
		$sql = " select post_title news_title,post_date news_date,post_content news_content,'' news_subtitle,(select display_name from cms801_users where id=t.post_author) news_author from cms801_".$site_id."posts t where t.ID=?";
		$ds = $da->GetData("t",$sql,array(
			(int)$news_id
		));
		$data = array();
		if($ds && count($ds["t"]["rows"])>0) $data= $ds["t"]["rows"][0];
		return $this->responseJson($data);		
	}

	public function getBaoXiao_tmpAction($eno)
	{
		$da = $this->get("we_data_access_test");
		$sql = "select a.*,b.check_date,b.check_memo,b.check_person,b.check_result,b.check_jid from baoxiao a left join baoxiao_check b on a.id=b.orderid where a.eno=? ";
		$ds = $da->GetData("t",$sql,array((string)$eno));
		$data = array();

		if($ds && count($ds["t"]["rows"])>0)
		{
			$data  = 	$ds["t"]["rows"];
		}

		return $this->responseJson($data);
	}

	public function getBaoXiaoDetail_tmpAction($id)
	{
		$da = $this->get("we_data_access_test");
		$sql = "select a.*,b.check_date,b.check_memo,b.check_person,b.check_result,b.check_jid from baoxiao a left join baoxiao_check b on a.id=b.orderid where a.id=? ";
		$ds = $da->GetData("t",$sql,array((int)$id));
		$data = array();

		if($ds && count($ds["t"]["rows"])>0)
		{
			$data  = 	$ds["t"]["rows"][0];
		}

		return $this->responseJson($data);
	}


	public function addBaoXiao_tmpAction()
	{
		$data = array("s"=>1);
		$request = $this->get("request");
		$da = $this->get("we_data_access_test");
		//$bx_data=$request->get("data");
		try
		{
			//$this->get("logger")->err($bx_data);
			//$obj_data = json_decode($bx_data);
			//$this->get("logger")->err($obj_data);
			//$this->get("logger")->err(json_encode( $obj_data));
			$sql = "insert into baoxiao(eno,bx_type,bx_money,bx_memo,apply_person,apply_date,bx_pic,apply_jid,apply_check_person,apply_check_jid)values(?,?,?,?,?,now(),?,?,?,?)";
			$para = array(
				(string)$request->get("bx_eno"),
				(string)$request->get("bx_type"),
				(double)$request->get("bx_money"),
				(string)$request->get("bx_memo"),
				(string)$request->get("apply_person"),
				(string)$request->get("bx_pic"),
				(string)$request->get("apply_jid"),
				(string)$request->get("apply_check_person"),
				(string)$request->get("apply_check_jid")
			);
			$da->ExecSQL($sql,$para);
			//推送消息
			$api = new ApiController();
			$api->setContainer($this->container);
			//$token = $this->getToken("e148ef39fa1235ea4296bfebb94e813f","wJsNWJ4n");
			$msgContent=array();
			$title = $request->get("apply_person")."-新报销单";
			$content = $request->get("bx_type").":".$request->get("bx_money");
			

			$items=array();
        	array_push($items, array('title'=>$title,'content'=>$content));
        	$msgContent= array('textmsg'=>array('item'=>$items));
        	$msgContent=json_encode($msgContent);

			$api->sendMsg2("3b2c1752fdf2f121468e641c1d2c400b",$request->get("apply_check_jid"),($msgContent),"TEXT");
		}
		catch(\Exception $e)
		{
			$data = array("s"=>0,"msg"=>$e->getMessage());
		}
		return $this->responseJson($data);
	}

	public function checkBaoXiao_tmpAction()
	{
		$data = array("s"=>1);
		$da = $this->get("we_data_access_test");
		$request = $this->get("request");
		//$bx_data=$request->get("data");
		try
		{
			$sql = "select apply_jid from baoxiao a where a.id=? ";
			$ds = $da->GetData("t",$sql,array((string)$request->get("orderid")));
			//$obj_data = json_decode($bx_data);
			$sql = "insert into baoxiao_check(orderid,check_date,check_memo,check_person,check_result,check_jid)values(?,now(),?,?,?,?)";
			$para = array(
				(string)$request->get("orderid"),
				(string)$request->get("check_memo"),
				(string)$request->get("check_person"),
				(string)$request->get("check_result"),
				(string)$request->get("check_jid")
			);
			$da->ExecSQL($sql,$para);
			//推送消息
			$api = new ApiController();
			$api->setContainer($this->container);
			//$token = $this->getToken("e148ef39fa1235ea4296bfebb94e813f","wJsNWJ4n");
			$msgContent=array();
			$title = $request->get("apply_person")."-报销单审批";
			$content = "你的报销单已处理,审批意见：".$request->get("check_memo");
			

			$items=array();
        	array_push($items, array('title'=>$title,'content'=>$content));
        	$msgContent= array('textmsg'=>array('item'=>$items));
        	$msgContent=json_encode($msgContent);

			$api->sendMsg2("3b2c1752fdf2f121468e641c1d2c400b",$ds["t"]["rows"][0]["apply_jid"],($msgContent),"TEXT");			
		}
		catch(\Exception $e)
		{
			$data = array("s"=>0,"msg"=>$e->getMessage());
		}
		return $this->responseJson($data);
	}

	private function getToken($appid,$appkey)
	{
		$code = $this->makeCode($appid,$appkey);
		$api = new ApiController();
		$json=$api->getProxySession($appid,$code,"test-fafa-app");
		return $json["access_token"];
	}
	private function makeCode($appid,$appkey)
	{
		return strtolower(md5($appid.$appkey));
	}


	private function getLink($uniqid) {
		$web_url=$this->container->getParameter('open_api_url');
		return $web_url.'/api/http/getpagepath/'.$uniqid;
	}

	private function responseJson($data)
	{
		$resp = new Response(json_encode($data));
	    $resp->headers->set('Content-Type', 'text/json');
	   	return $resp;
	}
	
}