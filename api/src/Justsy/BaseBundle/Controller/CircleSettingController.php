<?php

namespace Justsy\BaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class CircleSettingController extends Controller
{
	public $logo_path;
	public $logo_path_small;
	public $logo_path_big; 

  public function circleAction($network_domain)
  {
    if(0==$this->get('security.context')->getToken()->getUser()->is_in_manager_circles($network_domain))
		{
			return $this->render('JustsyBaseBundle:CircleSetting:no_rights.html.twig');
		}
		$login_account=$this->get('security.context')->getToken()->getUser()->getUsername();
  	$DataAccess=$this->get('we_data_access');
  	$sql=array(
  	     'select a.*,c.classify_id parent_classify_id,c.classify_name parent_classify_name from 
		  	      we_circle a
		  	      left join we_circle_class b on a.circle_class_id=b.classify_id
		  	      left join we_circle_class c on b.parent_classify_id=c.classify_id
		  	      where a.network_domain=?',
		  	 'select * from we_circle_class where 1=?'
  	);
  	$dataset=$DataAccess->GetDatas(array('we_circle','we_circle_class'),$sql,array(array((string)$network_domain),array((int)1)));

  	$this->logo_path=$dataset['we_circle']['rows'][0]['logo_path'];
  	$this->logo_path_small=$dataset['we_circle']['rows'][0]['logo_path_small'];
  	$this->logo_path_big=$dataset['we_circle']['rows'][0]['logo_path_big'];
  	$dataset['we_circle']['rows'][0]['logo_path']      =$this->ifPicNull($dataset['we_circle']['rows'][0]['logo_path']);
		$dataset['we_circle']['rows'][0]['logo_path_small']=$this->ifPicNull($dataset['we_circle']['rows'][0]['logo_path_small']);
		$dataset['we_circle']['rows'][0]['logo_path_big']  =$this->ifPicNull($dataset['we_circle']['rows'][0]['logo_path_big']);
  	$dataset['we_circle']['rows'][0]['curr_network_domain']=$network_domain;
  	$dataset['we_circle']['rows'][0]['circle_class']=$dataset['we_circle_class']['rows'];
  	$dataset['we_circle']['rows'][0]['manager']=explode(';',$dataset['we_circle']['rows'][0]['manager']);
  	$dataset['we_circle']['rows'][0]['fileurl']=$this->container->getParameter('FILE_WEBSERVER_URL');
  	$num_manager='';
		for($i=0;$i<count($dataset['we_circle']['rows'][0]['manager']);$i++)
    {
    	$num_manager.='?,';
    }
    $num_manager=substr($num_manager,0,strlen($num_manager)-1);
    $sql='select login_account,nick_name from we_staff where login_account in ('.$num_manager.')';
    $dataset2=$DataAccess->GetData('we_staff',$sql,$dataset['we_circle']['rows'][0]['manager']);
    $dataset['we_circle']['rows'][0]['manager']=$dataset2['we_staff']['recordcount']>0?$dataset2['we_staff']['rows']:array();
  	$data=$dataset['we_circle']['rows'][0];
  	return $this->render('JustsyBaseBundle:CircleSetting:circle_setting.html.twig',$data);
  }
  public function saveCircleAction($network_domain)
  {
  	$DataAccess=$this->get('we_data_access');
  	//$eno=$this->get('security.context')->getToken()->getUser()->getEno();
    $sql='select circle_id from we_circle where network_domain=?';
    $dataset=$DataAccess->GetData('we_circle',$sql,array((string)$network_domain));
    if($dataset['we_circle']['recordcount']>0)
    {
	    $circle_id=$dataset['we_circle']['rows'][0]['circle_id'];
    }
    else
      return '';
    $circle_name=$this->getRequest()->get('circle_name');
  	$circle_desc=$this->getRequest()->get('circle_desc');
  	$create_staff=$this->getRequest()->get('create_staff');
  	$manager=$this->getRequest()->get('array_manager');
  	$circle_desc=$this->getRequest()->get('circle_desc');
    $create_date='';
    $join_method=$this->getRequest()->get('join_method');
  	$allow_copy=$this->getRequest()->get('allow_copy');
  	$network_domain=$this->getRequest()->get('network_domain');
  	$circle_class_id=$this->getRequest()->get('circle_class_id');
  	
//  	$dm = $this->get('doctrine.odm.mongodb.document_manager');
//    if ($_FILES["en_logo_file"]["name"] != "") {
//            $tmpName = $_FILES['en_logo_file']['tmp_name'];
//            $fileid = $this->saveFile($tmpName, $dm);
//            $logo_path = $fileid;
//            $logo_path_big = $fileid;
//            $logo_path_small = $fileid;
//    }
		/*        
    $session=$this->get('session');
    $logo_path        =$session->get('avatar_middle');  
    $logo_path_big    =$session->get('avatar_big');
    $logo_path_small  =$session->get('avatar_small');
    if(empty($circle_id)===true||empty($circle_name)===true)
       return $this->res('{"success":0}','json');
  $dm=$this->get('doctrine.odm.mongodb.document_manager');
  	 if(!empty($logo_path))
  	 {
  	     $logo_path=$this->saveFile($logo_path,$dm);
  	     $logo_path_big=$this->saveFile($logo_path_big,$dm);
  	     $logo_path_small=$this->saveFile($logo_path_small,$dm);
  	     $session->remove('avatar_middle');
		  	 $session->remove('avatar_big');
		  	 $session->remove('avatar_small');
  	 }
  	 */
  	 $sql='update we_circle set  
							  	circle_name=?,
							  	create_staff=?,
							  	manager=?,
							  	circle_desc=?,
							    join_method=?,
							    network_domain=?,
							  	allow_copy=?,
							  	circle_class_id=? 
									where circle_id=?';
  		$para=array(
  		     			  (string)$circle_name,
							  	(string)$create_staff,
							  	(string)$manager,
							  	(string)$circle_desc,
							    (string)$join_method,
							    (string)$network_domain,
							  	(string)$allow_copy,
							  	(string)$circle_class_id,
							  	(string)$circle_id
  							);
  	 /*
  	 $logo_path='';
  	 if(empty($logo_path))
  	 {
  	 	  $sql='update we_circle set  
							  	circle_name=?,
							  	create_staff=?,
							  	manager=?,
							  	circle_desc=?,
							    join_method=?,
							    network_domain=?,
							  	allow_copy=?,
							  	circle_class_id=? 
									where circle_id=?';
  		$para=array(
  		     			  (string)$circle_name,
							  	(string)$create_staff,
							  	(string)$manager,
							  	(string)$circle_desc,
							    (string)$join_method,
							    (string)$network_domain,
							  	(string)$allow_copy,
							  	(string)$circle_class_id,
							  	(string)$circle_id
  							);
  	 }
  	 else
  	 {
      	$sql='select logo_path, logo_path_small, logo_path_big from we_circle where circle_id=?';
        $dsX=$DataAccess->GetData('we_circle',$sql,array((string)$circle_id));
      	$this->logo_path=$dsX['we_circle']['rows'][0]['logo_path'];
      	$this->logo_path_small=$dsX['we_circle']['rows'][0]['logo_path_small'];
      	$this->logo_path_big=$dsX['we_circle']['rows'][0]['logo_path_big'];
  	
  	 	   $sql='update we_circle set  
							  	circle_name=?,
							  	create_staff=?,
							  	manager=?,
							  	circle_desc=?,
							    join_method=?,
							    network_domain=?,
							  	allow_copy=?,
							  	circle_class_id=?,
							  	logo_path=?,
							  	logo_path_big=?,
							  	logo_path_small=? where circle_id=?';
  		$para=array(
  		     			  (string)$circle_name,
							  	(string)$create_staff,
							  	(string)$manager,
							  	(string)$circle_desc,
							    (string)$join_method,
							    (string)$network_domain,
							  	(string)$allow_copy,
							  	(string)$circle_class_id,
							  	(string)$logo_path,
							  	(string)$logo_path_big,
							  	(string)$logo_path_small,
							  	(string)$circle_id
							  );
  	 }
  	*/
    $sql2='select manager,fafa_groupid from we_circle where circle_id=?';
  	$dataset2=$DataAccess->GetData('we_circle',$sql2,array((string)$circle_id));
  	$fafa_groupid = $dataset2['we_circle']['rows'][0]['fafa_groupid'];
  	$old_manager_array=array();
  	$new_manager_array=array();
  	if($dataset2['we_circle']['recordcount']>0)
  	{
  		$old_manager_array=explode(';',$dataset2['we_circle']['rows'][0]['manager']);
  	}
  	$new_manager_array=explode(';',$manager);
  	$add_manager_array=array_diff($new_manager_array,$old_manager_array);  //新增管理员
  	$del_manager_array=array_diff($old_manager_array,$new_manager_array);  //被取消了管理员
    $dataexec=$DataAccess->ExecSQL($sql,$para);
  	if($dataexec)
  	{
  		if(count($add_manager_array)>0||count($del_manager_array)>0)
  		{
	  		$sqls=array(
	  		    'insert into we_message(msg_id,sender,send_date,title,content,isread,recver) values(?,?,CURRENT_TIMESTAMP(),?,?,?,?)',
	  		    'insert into we_notify(notify_type,msg_id,notify_staff) values(?,?,?)'
	  		);
	  		$login_account=$this->get('security.context')->getToken()->getUser()->getUsername();
	  		$FAFA_CIRCLE_URL=$this->generateUrl('JustsyBaseBundle_enterprise_home',array('network_domain'=>$circle_id),true);
        foreach($add_manager_array as $key=>$value)
        {
        	$msg_id = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($DataAccess, "we_message", "msg_id");
        	$manager=$value;
		  		$title='您被设置为管理员';
		  		$content='您被设置为圈子'.'<a href="'.$FAFA_CIRCLE_URL.'">【'.$circle_name.'】</a>的管理员！';
		  		$paras=array(
		  		       array(
		  		             (string)$msg_id,
		  		             (string)$login_account,
		  		             (string)$title,
		  		             (string)$content,
		  		             '0',
		  		             (string)$manager
		  		       ),
		  		       array(
		  		             '02',
		  		             (string)$msg_id,
		  		             (string)$manager
		  		            )
		  		);
		    	$dataexec1=$DataAccess->ExecSQLs($sqls,$paras);
		    }
		    foreach($del_manager_array as $key=>$value)
		    {
		    	$msg_id = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($DataAccess, "we_message", "msg_id");
		    	$manager=$value;
		  		$title='您被取消了管理员';
		  		$content='您被取消了圈子'.'<a href="'.$FAFA_CIRCLE_URL.'">【'.$circle_name.'】</a>的管理员！';
		  		$paras=array(
		  		       array(
		  		             (string)$msg_id,
		  		             (string)$login_account,
		  		             (string)$title,
		  		             (string)$content,
		  		             '0',
		  		             (string)$manager
		  		       ),
		  		       array(
		  		             '02',
		  		             (string)$msg_id,
		  		             (string)$manager
		  		            )
		  		);
		    	$dataexec2=$DataAccess->ExecSQLs($sqls,$paras);
		    }
  		}
  		/*
  		if(!empty($this->logo_path))
  		{
  				$this->deleteFile($this->logo_path,$dm);
		  		$this->deleteFile($this->logo_path_big,$dm);
		  		$this->deleteFile($this->logo_path_small,$dm);
  		}
  		*/
  		$reX = array();
  		$reX["success"] = "1"; 
  		if (!empty($logo_path))
  		{
  		  $reX["logo_path"] = $logo_path; 
  		  $reX["logo_path_small"] = $logo_path_small; 
  		  $reX["logo_path_big"] = $logo_path_big; 
  		}
  	  return $this->res(json_encode($reX),'json');
  	}
  	else
  	  return $this->res('{"success":0}','json');
  }

  public function checkCirclenameAction($network_domain)
  {
     $DataAccess=$this->get('we_data_access');
     $eno=$this->get('security.context')->getToken()->getUser()->getEno();
     $circle_name=$this->getRequest()->get('circle_name');
     $sql='select 1 from we_circle where circle_name=? and network_domain!=?';
     $dataset=$DataAccess->GetData('we_circle',$sql,array((string)$circle_name,(string)$network_domain));
     if((int)$dataset['we_circle']['recordcount']>0)
       return $this->res('{"exist":1}','json');
     else
       return $this->res('{"exist":0}','json');
  }
  public function queryCirclemanagerAction()
  {
  	$DataAccess=$this->get('we_data_access');
  	$network_domain=$this->getRequest()->get('network_domain');
  	$q=$this->getRequest()->get('q');
  	if(empty($q))
  	{
  		 $sql='select a.login_account,ifnull(a.nick_name,"") as nick_name from we_circle_staff a,we_circle b where b.network_domain=? and b.circle_id=a.circle_id and a.login_account!=b.create_staff limit 0,100';
  	   $dataset=$DataAccess->GetData('circle_manager',$sql,array((string)$network_domain));
  	}
  	else
  	{
			 $sql='select a.login_account,ifnull(a.nick_name,"") as nick_name from we_circle_staff a,we_circle b where b.network_domain=? and b.circle_id=a.circle_id and a.login_account!=b.create_staff and 
			      ( substring_index(a.login_account,"@",1) like ? or a.nick_name like ?) limit 0,100';
  	   $dataset=$DataAccess->GetData('circle_manager',$sql,array((string)$network_domain,'%'.$q.'%','%'.$q.'%'));	
  	}
  	$data=array();
  	if(count($dataset['circle_manager']['rows'])>0)
  	{
  		$data=$dataset['circle_manager']['rows'];
  	} 	
  	return $this->res(json_encode($data),'json');
  }
  
 
  public function ifPicNull($pic)
  {
  	if(empty($pic))
  	{
  		$pic=$this->get('templating.helper.assets')->getUrl('bundles/fafatimewebase/images/downphoto.png');
  	}
  	else
  	{
  		$pic=$this->container->getParameter('FILE_WEBSERVER_URL').$pic;
  	}
  	return $pic;
  }
  public function saveFile($filePath,$dm)
  {
  	$doc=new \Justsy\MongoDocBundle\Document\WeDocument;
  	$doc->setName(basename($filePath));
  	$doc->setFile($filePath);
  	$dm->persist($doc);
  	$dm->flush();
  	unlink($filePath);
  	return $doc->getId();
  }
  public function deleteFile($fileId,$dm)
  {
  	$doc = $dm->getRepository('JustsyMongoDocBundle:WeDocument')->find($fileId);
  	if(!empty($doc))
  	{
  		$dm->remove($doc);
  		$dm->flush();
  	}
  	return true;
  }
    public function res($content,$type='html')
    {
    	$response=new Response($content);
    	$response->headers->set('Content-Type','text/'.$type);
    	$response->headers->set('charset', 'utf-8');
    	return $response;
    }
}
