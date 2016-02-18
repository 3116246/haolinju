<?php

namespace Justsy\BaseBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Management\FriendCircle;
use Justsy\BaseBundle\Management\FriendEvent;
use Justsy\BaseBundle\Login\UserProvider;
use Justsy\BaseBundle\Management\Identify;

class CircleController extends Controller
{
	public function ceshiAction()
	{
		$request = $this->getRequest();
		$listdata=$request->get("listdata");
		$ittt=$request->get("ittt");
		if(!empty($listdata)){
			$result=array();
			$result[]=array(
				"id"=>"1",
				"title"=>"杰克脑",
				"icon"=>"http://we.fafatime.com/getfile/534ba34c7c274a1445000000",
				"pid"=>"2"
			);
			$result[]=array(
				"id"=>"1",
				"title"=>"杰克脑",
				"icon"=>"http://we.fafatime.com/getfile/534ba34c7c274a1445000000",
				"pid"=>"2"
			);
			$result[]=array(
				"id"=>"1",
				"title"=>"杰克脑",
				"icon"=>"http://we.fafatime.com/getfile/534ba34c7c274a1445000000",
				"pid"=>"2"
			);
			$result[]=array(
				"id"=>"1",
				"title"=>"杰克脑",
				"icon"=>"http://we.fafatime.com/getfile/534ba34c7c274a1445000000",
				"pid"=>"2"
			);
			if(empty($ittt)){
				$result[]=array(
					"id"=>"1",
					"title"=>"杰克脑",
					"icon"=>"http://we.fafatime.com/getfile/534ba34c7c274a1445000000",
					"pid"=>"2"
				);
			}
			$response = new Response($request->get('callback') ? $request->get('callback')."(".json_encode($result).");" : json_encode($result));
			$response->headers->set('Content-Type', 'text/json');
			return $response;
		}
		return $this->render("JustsyBaseBundle:Circle:ceshi.html.twig");
	}
	public function defaultAction()
	{
      $da = $this->get('we_data_access');
      $user = $this->get('security.context')->getToken()->getUser();	
      //判断用户是否设置了不显示默认页面
      $ds = $da->getData("d","select para_value from we_staff_para where login_account=? and para_id='SHOW_CIRCLE_DEFAULT'",array((string)$user->getUserName()));	
      //判断用户是否显示默认页面
      if(empty($ds) || count($ds["d"]["rows"])==0 || $ds["d"]["rows"][0]["para_value"]=="0")
      {
      	 $list["curr_network_domain"]=$user->edomain;
      	 $sql = "select ifnull(dept_id,'') deptid,nick_name,photo_path_small,photo_path_big from we_staff where login_account=?";
      	 $ds = $da->getData("we_staff",$sql,array((string)$user->getUserName()));
      	 $list["photo_big"] = "";
      	 $list["photo_small"] = "";
      	 $list["deptid"]="";
      	 $list["nick_name"]="";
      	 if ($ds["we_staff"]["recordcount"]>0)
      	 {
      	    $list["photo_big"] = $ds["we_staff"]["rows"][0]["photo_path_big"];
      	    $list["photo_small"] = $ds["we_staff"]["rows"][0]["photo_path_small"];
      	    $list["nick_name"] = $ds["we_staff"]["rows"][0]["nick_name"];
      	    $list["deptid"] = $ds["we_staff"]["rows"][0]["deptid"];      	    
      	 }
      	 //获取特定圈子成员数
      	 $sql = " select circle_id,count(*) scalar from we_circle_staff ".
  	            " where circle_id=(select circle_id from we_circle inner join we_staff on create_staff=login_account and eno=enterprise_no where login_account=?) or circle_id in(9999,10000) group by circle_id ";
  	     $ds = $da->getData("circledata",$sql,array((string)$user->getUserName()));
  	     $list["circleid9999"]="";
  	     $list["circleid10000"]="";
  	     $list["circleideno"]="";
  	     for($i=0;$i<$ds["circledata"]["recordcount"];$i++)
         {
        	 $circleid = $ds["circledata"]["rows"][$i]["circle_id"];
        	 if($circleid=="9999")
        	   $list["circleid9999"]=$ds["circledata"]["rows"][$i]["scalar"];
        	 else if ($circleid =="10000")
        	   $list["circleid10000"]=$ds["circledata"]["rows"][$i]["scalar"];
        	 else
        	   $list["circleideno"]=$ds["circledata"]["rows"][$i]["scalar"];        	 
         }
         $role = $user->role_codes;
         if (count($role)==0)
           $list["user_role"]="j";
         else
           $list["user_role"] = substr(strtolower($user->role_codes[0]),0,1);
		     return $this->render("JustsyBaseBundle:Circle:default.html.twig",$list);
		  }
		  else
		  {
		     return $this->redirect($this->generateUrl("JustsyBaseBundle_circle_search",array('network_domain'=> $user->edomain,'account'=> $user->getUsername())));
		  }
	}
	
  public function indexAction(Request $request)
  {
    $da = $this->get('we_data_access');
    $user = $this->get('security.context')->getToken()->getUser();
    //获取我的圈子
    $this->getEnterpriseCircle($user->getUsername());
    //获取圈子分类
    $this->circleType=$this->getCircleType();
    $list['this']= $this;
    $list["curr_network_domain"]=$request->get("network_domain");
    //返回100个同事供邀请成员时使用
    $sql = "select login_account,nick_name,fafa_jid from we_staff where eno=? and login_account!=? limit 0,100";
    $ds = $da->GetData("we_staff",$sql,array((string)$user->eno,(string)$user->getUsername()));
    $list['staff'] = ($ds && $ds['we_staff']['recordcount']>0) ? json_encode($ds['we_staff']['rows']) : json_encode(array());
    $list['fileurl'] = $this->container->getParameter('FILE_WEBSERVER_URL');
    $da->PageSize= $request->get('pagesize',8);
	  $da->PageIndex =$request->get('pageindex')?$request->get('pageindex')-1:0;
    $dp=$this->getInviteMember($da);
    $list['pagecount']=ceil($dp['recordcount']/8);
    $list['invitemembers']=$dp['rows'];
    $ec=new \Justsy\BaseBundle\Management\EnoParamManager($da,$this->get('logger'));
    if($ec->IsBeyondCreateCircle($user->getUserName())){
    	$list['IsBeyondCreateCircle']=true;
    	$list['CountCreateCircle']=$ec->getCountCreateCircle($user->getUserName());
    }
    else
    	$list['IsBeyondCreateCircle']=false; 
    return $this->render("JustsyBaseBundle:Circle:circle_create.html.twig",$list);
  }
  //根据帐号查找人员
  public function queryStaffAction()
  {
    $da = $this->get('we_data_access');
    $user = $this->get('security.context')->getToken()->getUser();
    $account = $this->get('request')->request->get('account');
    $sql = "select login_account,nick_name,fafa_jid,eshortname from we_staff a 
      inner join we_enterprise b on a.eno=b.eno where not exists (select 1 from we_micro_account m where m.eno=a.eno and a.login_account= m.number) and login_account!=? 
      and nick_name like concat('%',?,'%') limit 0,8";
    $ds = $da->GetData("we_staff",$sql,array(
      (string)$user->getUsername(),
      (string)$account
    ));
    $staff = ($ds && $ds['we_staff']['recordcount']>0) ? $ds['we_staff']['rows'] : array();
    $resp = new Response(json_encode($staff));
    $resp->headers->set('Content-Type', 'text/json');
    return $resp;
  }
    
    public function getCircleType()
    {
        $sql = "select * from we_circle_class order by classify_order_by";	
        $da = $this->get('we_data_access');
        $ds = $da->GetData("data",$sql);
        $r="";
        for($i=0;$i<count($ds["data"]["rows"]);$i++)
        {
        	 $row = $ds["data"]["rows"][$i];
        	 if(!empty($r)) $r.=",";
        	 $r .= "{\"id\":\"".$row["classify_id"]."\",\"name\":\"".$row["classify_name"]."\",\"parent\":\"".$row["parent_classify_id"]."\"}";
        }
        return "[".$r."]";
    }
    
    public function getEnterpriseCircle($userId)
    {
    	$user = $this->get('security.context')->getToken()->getUser();
      $da = $this->get('we_data_access');
      $sql = "select a.circle_id, circle_name,
                     case when create_staff = login_account then 1 else 0 end IsAdmin,case when manager = login_account then 1 else 0 end Manager,
              ifnull((select 1 from we_enterprise where a.network_domain= edomain),0) default_circle
              from we_circle a inner join we_circle_staff b on a.circle_id=b.circle_id where b.login_account=? order by default_circle desc";
      $params = array((string)$userId);      
      $ds = $da->GetData("circle", $sql, $params);
      
      $this->circles = $ds["circle"]["rows"];
      
      return;
    }
    //检查圈子名称是否存在
    public function checkAction(Request $request)
    {
       $da = $this->get("we_data_access");
       $result;
       $sql = "";
       if ($request->get('type')=="1")
         $sql = "select circle_id from we_circle where circle_name=?";
       else
         $sql = "select circle_id from we_circle where network_domain=?";
       $table = $da->GetData("circle",$sql,array((String)$request->get('parameter')));
       if ($table && $table["circle"]["recordcount"] >0 )
         $result = array('exist'=>1,'id'=>$table["circle"]["rows"][0]["circle_id"]);
       else
         $result = array('exist'=>0);
       $response = new Response(json_encode($result));
  		 $response->headers->set('Content-Type', 'text/json');
  		 return $response;
    }
    
    //返回修改的数据
    public function infoAction(Request $request)
    {
       $circleid = $request->get('id');
       $da = $this->get("we_data_access");
       $sql = " select circle_name,circle_desc,logo_path,logo_path_big,create_staff,create_date,manager,ifnull(join_method,1) join_method ,group_concat(login_account) account,network_domain,ifnull(allow_copy,1) allow_copy,circle_class_id,(select classify_id from we_circle_class where parent_classify_id=circle_class_id) circle_class_parent
               from we_circle  inner join we_circle_staff using(circle_id) where circle_id=?";
       $table = $da->GetData("circle",$sql,array((String)$circleid));
       $result;
       if ($table && $table["circle"]["recordcount"] >0 )
       {
         $img = $table["circle"]["rows"][0]["logo_path_big"];         
         $result = array("circlename" => $table["circle"]["rows"][0]["circle_name"],
                         "desc" => $table["circle"]["rows"][0]["circle_desc"],
                         "img"  => empty($img)?"":$this->container->getParameter('FILE_WEBSERVER_URL').$img,
                         "manager" => $table["circle"]["rows"][0]["manager"],
                         "join_method" => $table["circle"]["rows"][0]["join_method"],
                         "account" => $table["circle"]["rows"][0]["account"],
                         "network" => $table["circle"]["rows"][0]["network_domain"],
                         "allow_copy" => $table["circle"]["rows"][0]["allow_copy"],
                         "circle_class_id"=> $table["circle"]["rows"][0]["circle_class_id"],
                         "circle_class_parent"=> $table["circle"]["rows"][0]["circle_class_parent"],
                         "exist" => "1");
  		 }
  		 else
  		 {
  		   $result = array("exist" => "0" );
  		 }
  		 $response = new Response(json_encode($result));
  		 $response->headers->set('Content-Type', 'text/json');
  		 return $response;       		 
    }  
  private function saveFile($path, $dm)
  {
    $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
    $doc->setName(basename($path));
    $doc->setFile($path);
    $dm->persist($doc);
    $dm->flush();
    unlink($path);
    return $doc->getId();
  }
  private function removeFile($path, $dm)
  {
         if (!empty($path))
         {
            $doc = $dm->getRepository('JustsyMongoDocBundle:WeDocument')->find($path);
            if(!empty($doc))
               $dm->remove($doc);
            $dm->flush();
         }
         return true;
  }
	
	public function saveLogoAction()
	{
		$da = $this->get("we_data_access");
		$request = $this->getRequest();
		$circleid = $request->get("circleid");
		$user = $this->get('security.context')->getToken()->getUser();     
    $da_im = $this->get('we_data_access_im');
    $session = $this->get('session');
    $filename120 = $session->get("avatar_big");
    $filename48 = $session->get("avatar_middle");
    $filename24 = $session->get("avatar_small");
    $im_sender = $this->container->getParameter('im_sender');
    $circle_photo_path='';
  	try{
	    $dm = $this->get('doctrine.odm.mongodb.document_manager');
	    if (!empty($filename120)) $filename120= $this->saveFile($filename120,$dm);
	    if (!empty($filename48)) $filename48= $this->saveFile($filename48,$dm);
	    if (!empty($filename24)) $filename24=$this->saveFile($filename24,$dm);
	    $session->remove("avatar_big");
	    $session->remove("avatar_middle");
	    $session->remove("avatar_small");
	    //判断是添加还是修改
	    $table = $da->GetData("circle","select circle_name,circle_id,logo_path,logo_path_small,logo_path_big,fafa_groupid from we_circle where manager=? and circle_id=?",array((string)$user->getUserName(),(String)$circleid ));
	    if ($table && $table["circle"]["recordcount"] == 0 ) $circleid = 0;  //circleid=0表示添加群组管理
	    else $fafa_groupid =$table["circle"]["rows"][0]["fafa_groupid"];
	    if (!empty($filename120))  //对于上传图片的处理
	    {
	      if ($table && $table["circle"]["recordcount"] >0 )  //如果用户原来有头像则删除
	      { 
	        $this->removeFile($table["circle"]["rows"][0]["logo_path"],$dm);
	        $this->removeFile($table["circle"]["rows"][0]["logo_path_small"],$dm);
	        $this->removeFile($table["circle"]["rows"][0]["logo_path_big"],$dm);
	      }
	    }
	    if(!empty($circleid)){
        //$this->deleteFile($dataset['we_groups']["rows"][0]["group_photo_path"],$dm);
	    	$sql="update we_circle set logo_path=?,logo_path_small=?,logo_path_big=? where circle_id=?";
	    	$params=array($filename48,$filename24,$filename120,$circleid);
	    	$da->ExecSQL($sql,$params);

        //取圈子成员
        $sql="SELECT b.fafa_jid FROM we_circle_staff a LEFT JOIN we_staff b ON a.login_account=b.login_account WHERE a.circle_id=? AND b.fafa_jid IS NOT NULL";
        $para=array($circleid);
        $data=$da->GetData('dt',$sql,$para);
        if($data!=null && count($data['dt']['rows'])>0) {
          //修改头像之后需要发出席消息。让各端及时修改头像
          $user = $this->get('security.context')->getToken()->getUser();
          $fafa_jid=$user->fafa_jid;
          $tojid=array();
          for ($i=0; $i < count($data['dt']['rows']); $i++) { 
            array_push($tojid, $data['dt']['rows'][$i]['fafa_jid']);
          }
          if($table!=null && count($table['circle']['rows'])>0) {
            $circlejid= $table["circle"]["rows"][0]["fafa_groupid"];
            $circlename= $table["circle"]["rows"][0]["circle_name"];
            $message=json_encode(array('circle_id'=>$circleid
              ,'logo_path'=>$filename120
              ,'circle_name'=>$circlename
              ,'jid'=>$circlejid));
            Utils::sendImMessage($fafa_jid,implode(",",$tojid),"circle_info_change",$message, $this->container,"","",false,Utils::$systemmessage_code);
          }
        }
	    }
	    else{
	    	$session->set('circle_filename24',$filename24);
	    	$session->set('circle_filename48',$filename48);
	    	$session->set('circle_filename120',$filename120);
	    }
	  }
	  catch(\Exception $e){
	  	$this->get('logger')->err($e);
	  }
    $response = new Response(json_encode(array('circle_photo_path'=> $filename120)));
	  $response->headers->set('Content-Type', 'text/json');
	  return $response;
	}
    //添加或修改圈子信息
  public function updateAction(Request $request)
  {
    $user = $this->get('security.context')->getToken()->getUser();
    $circleid = $request->get("id");
    $da = $this->get("we_data_access");     
    $da_im = $this->get('we_data_access_im');
    
    $session = $this->get('session');
    $filename120 = $session->get("circle_filename120");
    $filename48 = $session->get("circle_filename48");
    $filename24 = $session->get("circle_filename24");
    $im_sender = $this->container->getParameter('im_sender');
  	/*
    $dm = $this->get('doctrine.odm.mongodb.document_manager');
    if (!empty($filename120)) $filename120= $this->saveFile($filename120,$dm);
    if (!empty($filename48)) $filename48= $this->saveFile($filename48,$dm);
    if (!empty($filename24)) $filename24=$this->saveFile($filename24,$dm);
    $session->remove("avatar_big");
    $session->remove("avatar_middle");
    $session->remove("avatar_small");
    */ 
    $fileid = "";
    $fafa_groupid = "";
    //判断是添加还是修改
    $table = $da->GetData("circle","select circle_id,logo_path,logo_path_small,logo_path_big,fafa_groupid from we_circle where manager=? and circle_id=?",array((string)$user->getUserName(),(String)$circleid ));
    if ($table && $table["circle"]["recordcount"] == 0 ) $circleid = 0;  //circleid=0表示添加群组管理
    else $fafa_groupid =      $table["circle"]["rows"][0]["fafa_groupid"]   ;
    //判断是否能创建圈子
    $ec=new \Justsy\BaseBundle\Management\EnoParamManager($da,$this->get('logger'));
    if($ec->IsBeyondCreateCircle($user->getUserName())){
    	return $this->render('JustsyBaseBundle:login:index.html.twig', array('name' => 'err'));
    }
    if (!empty($filename120))  //对于上传图片的处理
    {
      if ($table && $table["circle"]["recordcount"] >0 )  //如果用户原来有头像则删除
      { 
        $this->removeFile($table["circle"]["rows"][0]["logo_path"],$dm);
        $this->removeFile($table["circle"]["rows"][0]["logo_path_small"],$dm);
        $this->removeFile($table["circle"]["rows"][0]["logo_path_big"],$dm);
      }
    }
    $classify = $session->get("classify");
    //$classify_childer = $session->get("classify-childer");
    $classify_childer=$request->get("classify-childer");
    //对数据的操作
    $sqls = "";
    $paras = "";
    if ( $circleid == 0)
    { 
      $circle_id = (String)SysSeq::GetSeqNextValue($da,"we_circle","circle_id");
      $fafa_groupid = SysSeq::GetSeqNextValue($da_im,"im_group","groupid");
      $network =$circle_id;
      $sqls = array
      (
        "insert into we_circle(circle_id,circle_name,circle_desc,logo_path,logo_path_big,logo_path_small,create_staff,create_date,manager,join_method,network_domain,allow_copy,circle_class_id,fafa_groupid)value(?,?,?,?,?,?,?,now(),?,?,?,?,?,?)",
        "insert into we_circle_staff(circle_id,login_account,nick_name)values(?,?,?)"
      );
      $paras = array
      (
        array((String)$circle_id,(String)$request->get("txtcircle"),(String)$request->get("txtdesc"),
               (String)$filename48,(string)$filename120,(string)$filename24,(String)$user->getUsername(),(String)$user->getUsername(),
               (String)$request->get("radjoin"),$network,(String)$request->get("radcopy"),(string)$classify_childer,(string)$fafa_groupid),
        array((String)$circle_id,(String)$user->getUsername(),(String)$user->nick_name)
      );
    }
    else
    {
      if (!empty($filename120))
      {
        $sqls = "update we_circle set circle_name=?,circle_desc=?,logo_path=?,logo_path_big=?,logo_path_small=?,join_method=?,allow_copy=?,circle_class_id=? where circle_id=?";
        $paras = array
        (
          (String)$request->get("txtcircle"),(String)$request->get("txtdesc"),(String)$filename48,(String)$filename120,(String)$filename24,
          (String)$request->get("radjoin"),(String)$request->get("radcopy"),(String)$circleid,(string)$classify_childer
        );   
      }
      else
      {
        $sqls = "update we_circle set circle_name=?,circle_desc=?,join_method=?,allow_copy=?,circle_class_id=? where circle_id=?";
        $paras = array
        (
          (String)$request->get("txtcircle"),(String)$request->get("txtdesc"),
          (String)$request->get("radjoin"),(String)$request->get("radcopy"),(String)$circleid,(string)$classify_childer
        );               
      }
      $circle_id = $circleid;
    }
    try
    {
      if ( $circleid ==0)
      {
        $da->ExecSQLs($sqls,$paras);
        //创建文档根目录
        $docCtl = new \Justsy\BaseBundle\Controller\DocumentMgrController();
        $docCtl->setContainer($this->container);
        if($docCtl->createDir("c".$circle_id,"",$request->get("txtcircle"),$circle_id)>0)
        {
        	  $docCtl->saveShare("c".$circle_id,"0",$circle_id,"c","w");//将圈子目录共享给该圈子成员
        }
      }
      else
      {      	
        $da->ExecSQL($sqls,$paras);
      }
      //给创建者发送创建群组成功出席
      Utils::sendImPresence($im_sender,$user->fafa_jid,"creategroup",json_encode(array("groupid"=> $fafa_groupid,"groupname"=> $request->get("txtcircle"))),$this->container,"","",false,Utils::$systemmessage_code);      
      //发送邀请邮件
      $circleId=$circle_id;
      $circleName = $request->get("txtcircle");
      $invitedmemebers = $request->get('invitedmemebers');
      if(!empty($invitedmemebers))
      {
        $user = $this->get('security.context')->getToken()->getUser();
        $invInfo = array(
          'inv_send_acc' => $user->getUsername(),
          'inv_recv_acc' => '',
          'eno' => '',
          'inv_rela' => '',
          'inv_title' => '',
          'inv_content' => '',
          'active_addr' => '');
        $invitedmemebersLst = explode(";",$invitedmemebers);
        foreach($invitedmemebersLst as $key => $value)
        {
          $invacc = trim($value);
          if (empty($invacc)) continue;
          $invInfo['inv_recv_acc'] = $invacc;
          $sql = "select eno,fafa_jid from we_staff where login_account=?";
          $ds = $da->GetData("we_staff",$sql,array((string)$invacc));
          //帐号存在
          if ($ds && $ds['we_staff']['recordcount']>0)
          {
            //1.帐号存在，直接加入圈子
            //受邀人员帐号,圈子id,邀请人帐号
            $encode = DES::encrypt("$invacc,$circleId,".$user->getUsername());
            $activeurl = $this->generateUrl("JustsyBaseBundle_invite_agreejoincircle",array('para'=>$encode), true);
            $rejectactiveurl = $this->generateUrl("JustsyBaseBundle_invite_refuse",array('para'=>$encode), true);
            $txt = $this->renderView('JustsyBaseBundle:Invite:circle_invitation_msg.html.twig', array(
              "ename" => $user->ename,
              "nick_name" => $user->nick_name,
              "activeurl" => $activeurl,
              'circle_name' => $circleName,
              'invMsg' => '',
              'staff'=>array()));
            $invInfo['eno'] = "c$circleId";
            $invInfo['inv_title'] = "邀请加入圈子【".Utils::makeCircleTipHTMLTag($circleid==0 ? $circle_id : $circleid, $circleName)."】";
            $invInfo['inv_content'] = '';
            $invInfo['active_addr'] = $activeurl;
            //保存邀请信息
            InviteController::saveWeInvInfo($da, $invInfo);
            //发送即时消息
            $fafa_jid = $ds['we_staff']['rows'][0]['fafa_jid'];
            $message = Utils::makeHTMLElementTag('employee',$user->fafa_jid,$user->nick_name)."邀请您加入圈子【".Utils::makeHTMLElementTag('circle',$fafa_groupid,$circleName)."】";
            $buttons = array();            
            $buttons[]=array("text"=>"拒绝","code"=>"agree","value"=>"0","link"=> $rejectactiveurl);
            $buttons[]=array("text"=>"立即加入","code"=>"agree","value"=>"1","link"=> $activeurl);
            Utils::sendImMessage($im_sender,$fafa_jid,"邀请加入圈子",$message,$this->container,"",Utils::makeBusButton($buttons),false,Utils::$systemmessage_code);
          }
          else
          {
            //2.帐号不存在
            $tmp = explode("@",$invacc);      
            $tmp = (count($tmp) > 1) ? $tmp[1] : 'fafatime.com';
            $sql = "select count(1) as cnt from we_public_domain where domain_name=?";
            $ds = $da->GetData("we_public_domain",$sql,array((string)$tmp));
            if ($ds && $ds['we_public_domain']['rows'][0]['cnt']==0)
            {
              //2.1企业邮箱
              $sql = "select eno from we_enterprise where edomain=?";
              $ds = $da->GetData("we_enterprise",$sql,array((string)$tmp));
              if ($ds && $ds['we_enterprise']['recordcount']>0)
              {
                //2.1.1企业已创建 帐号,圈子id,企业edomain des encode
                $eno = $ds['we_enterprise']['rows'][0]['eno'];
                $encode = DES::encrypt($user->getUsername().",$circleId,$eno");
                $activeurl = $this->generateUrl("JustsyBaseBundle_active_inv_s1", array(
                  'account' => DES::encrypt($invacc),
                  'invacc' => $encode), true);
              }
              else
              {
                //2.1.2企业未创建
                $sql = "insert into we_register (login_account,ename,credential_path,active_code,ip,email_type,first_reg_date,last_reg_date,register_date,state_id)"
                  ." values (?,?,?,?,?,?,now(),now(),now(),'0')";
                $para = array($invacc,'','',strtoupper(substr(uniqid(),3,10)),$_SERVER['REMOTE_ADDR'],'1');
                $da->ExecSQL($sql,$para);
                //发送邮件 帐号,圈子id,邀请发送者帐号,邀请人企业名 des encode
                $encode = DES::encrypt("$invacc,$circleId,".$user->getUserName().",".$user->ename);
                $activeurl = $this->generateUrl("JustsyBaseBundle_active_reg_s1", array('account' => $encode), true);
              }
              //保存邀请信息 circleid保存到eno字段，以字母'c'开头
              $invInfo['eno'] = "c$circleId";
              $title = $user->nick_name." 邀请您加入 ".Utils::makeCircleTipHTMLTag($circleid==0 ? $circle_id : $circleid, $circleName)." 协作网络";
              $txt = $this->renderView('JustsyBaseBundle:Invite:circle_invitation.html.twig', array(
                "ename" => $user->ename,
                "nick_name" => $user->nick_name,
                "activeurl" => $activeurl,
                'circle_name' => $circleName,
                'invMsg' => '',
                'staff'=>array()));
              $invInfo['inv_title'] = $title;
              $invInfo['inv_content'] = $txt;
              $invInfo['active_addr'] = $activeurl;
              InviteController::saveWeInvInfo($da, $invInfo);
              Utils::saveMail($da,$user->getUsername(),$invacc,$title,$txt,$invInfo['eno']);
              //Utils::sendMail($this->get('mailer'),$title,$this->container->getParameter('mailer_user'),null,$invacc,$txt);
            }
            else
            {
              //公共邮箱
              $sql = "insert into we_register (login_account,ename,credential_path,active_code,ip,email_type,first_reg_date,last_reg_date,register_date,state_id) "
                ."select ?,'','','".strtoupper(substr(uniqid(),3,10))."','".$_SERVER['REMOTE_ADDR']."','0',now(),now(),now(),'2' from dual "
                ."where not exists (select 1 from we_register where login_account=?)";
              $para = array($invacc,$invacc);
              $da->ExecSQL($sql,$para);
              //发送邮件 帐号,圈子id,邀请发送者帐号,邀请人企业名 des encode
              $encode = DES::encrypt("$invacc,$circleId,".$user->getUserName().",".$user->ename);
              $activeurl = $this->generateUrl("JustsyBaseBundle_active_reg_s1",array('account'=>$encode),true);
              $invInfo['eno'] = "c$circleId";
              $title = $user->nick_name." 邀请您加入 ".Utils::makeCircleTipHTMLTag($circleid==0 ? $circle_id : $circleid, $circleName)." 协作网络";
              $txt = $this->renderView('JustsyBaseBundle:Invite:circle_invitation.html.twig', array(
                "ename" => $user->ename,
                "nick_name" => $user->nick_name,
                "activeurl" => $activeurl,
                'circle_name' => $circleName,
                'invMsg' => '',
                'staff'=> array()));
              //保存邀请信息
              $invInfo['inv_title'] = $title;
              $invInfo['inv_content'] = $txt;
              $invInfo['active_addr'] = $activeurl;
              InviteController::saveWeInvInfo($da, $invInfo);
							Utils::saveMail($da,$user->getUsername(),$invacc,$title,$txt,$invInfo['eno']);
              //Utils::sendMail($this->get('mailer'),$title,$this->container->getParameter('mailer_user'),null,$invacc,$txt);
            }
          }
        }
      }
      return $this->redirect($this->generateUrl("JustsyBaseBundle_enterprise",array('network_domain'=> $circle_id),true));
    }
    catch(Exception $e)
    {
      return $this->render('JustsyBaseBundle:login:index.html.twig', array('name' => 'err'));
    }
  }
    
  //加入圈子
  public function addCircleAction($network_domain,$account)
  {
    $da = $this->get("we_data_access");
    $user = $this->get('security.context')->getToken()->getUser();
    $account = trim(DES::decrypt($account));
    //判断圈子是否有效
    $sql = "select circle_id,circle_name from we_circle where network_domain=?";
    $ds = $da->GetData("we_circle",$sql,array((string)$network_domain));
    if ($ds && $ds['we_circle']['recordcount']>0)
    {
      $circle_id = $ds['we_circle']['rows'][0]['circle_id'];
      $circle_name = $ds['we_circle']['rows'][0]['circle_name'];
      //是否已加入圈子
      $sql = "select count(1) as cnt from we_circle_staff where circle_id=? and login_account=?";
      $ds = $da->GetData("we_circle_staff",$sql,array((string)$circle_id,(string)$account));
      if ($ds && $ds['we_circle_staff']['rows'][0]['cnt']>0)
      {
        return $this->render('JustsyBaseBundle:Error:index.html.twig',array('error'=>'您已经加入该圈子！'));
      }
      //判断帐号是否有效
      $sql = "select a.nick_name,a.eno,b.eshortname from we_staff a ,we_enterprise b where a.eno=b.eno and a.login_account=?";
      $ds = $da->GetData("we_staff",$sql,array((string)$account));
      if ($ds && $ds['we_staff']['recordcount']>0)
      {
      	//判断是否已超出加入圈子数量的限制
				$ec=new \Justsy\BaseBundle\Management\EnoParamManager($da,$this->get('logger'));
		    if($ec->IsBeyondJoinCircle($account)){
		    	return $this->render('JustsyBaseBundle:Error:index.html.twig',array('error'=>'您加入的圈子过多，已达到了等级限制。'));
		    }
		    //判断改圈子成员数是否已满
		    if($ec->IsBeyondCircleMembers($circle_id)){
		    	return $this->render('JustsyBaseBundle:Error:index.html.twig',array('error'=>'抱歉，该圈子已满员。'));
		    }
        //判断圈子是否有人
        $sql = "select count(1) as cnt from we_circle_staff where circle_id=?";
        $ds = $da->GetData('we_circle_staff',$sql,array((string)$circle_id));
        if ($ds && $ds['we_circle_staff']['rows'][0]['cnt']==0)
        {
          $sql = "update we_circle set create_staff=? where circle_id=?";
          $da->ExecSQL($sql,array((string)$account,(string)$circle_id));
        }
        $nick_name = $ds['we_staff']['rows'][0]['nick_name'];
        //判断是否重名,重名时加上企业标识
        $sql = "select 1 from we_circle_staff where circle_id=? and nick_name=?";
        $isDouble = $da->GetData("db",$sql,array((string)$circle_id,(string)$nick_name));
        if($isDouble  && count($isDouble["db"]["rows"])>0)
        {
        	$eno = "(".$ds['we_staff']['rows'][0]['eshortname'].")";
        	$nick_name=$nick_name.$eno;
        }        
  	 	  $sql="insert into we_circle_staff(circle_id,login_account,nick_name)values(?,?,?) ";
  	 	  $da->ExecSQL($sql,array((string)$circle_id,(string)$account,(string)$nick_name));
  	 	  //发公告
  	 	  $txt = "新成员 <a class='employee_name' login_account='".$account."'>".$nick_name."</a> 加入了圈子【".$circle_name."】";
        //发送站内消息
			  $msgId = SysSeq::GetSeqNextValue($da,"we_bulletin","bulletin_id");
				$sql = "insert into we_bulletin(bulletin_id,circle_id,group_id,bulletin_date,bulletin_desc)values(?,?,?,now(),?)";
				$da->ExecSQL($sql,array((int)$msgId,(string)$circle_id,"ALL",$txt));	  	 	  
  	 	  //通知圈子成员
  	 	  $members = $this->notifyCircleMember($da,$circle_id);
  	 	  $fafa_jids=array();
  	 	  for($i=0;$i<count($members);$i++)
  	 	  {
          $membersrow = $members[$i];
          if($membersrow["login_account"]==$user->getUserName()) continue; 
          //$sql = "insert into we_notify(notify_type, msg_id,notify_staff)values('01',?,?)";
          //$da->ExecSQL($sql,array((int)$msgId,(string)$account));
          $fafa_jids[]=$membersrow["fafa_jid"];
  	 	  }
        //变更版本信息
        $eno = $user->eno;
        $verchange = new \Justsy\BaseBundle\Management\VersionChange($da,$this->get("logger"));
    	  $result = $verchange->SetVersionChange(3,$circle_id,$eno);
  	 	  
        //发送即时消息通知申请人及成员
        $message = Utils::makeHTMLElementTag('employee',$appy_user_jid,$nick_name)."加入了圈子【".Utils::makeHTMLElementTag('circle',$fafa_groupid,$circle_name)."】";
    	  Utils::sendImMessage($im_sender,implode(",",$fafa_jids),"圈子消息",$message,$this->container,"",Utils::makeBusButton($buttons),false,Utils::$systemmessage_code);
  	 	  return $this->redirect($this->generateUrl("JustsyBaseBundle_enterprise",array('network_domain'=> $network_domain),true));
      }
      else
      {
        return $this->render('JustsyBaseBundle:Error:index.html.twig',array('error'=>'您还未注册微发发，请先注册！'));
      }
    }
    else
    {
      return $this->render('JustsyBaseBundle:Error:index.html.twig',array('error'=>'未找到该圈子！'));
    }
  }
  public function notifyCircleMember($da,$circleid)
  {
    $sql = "select a.login_account,b.fafa_jid,b.nick_name from we_circle_staff a,we_staff b where a.login_account=b.login_account and a.circle_id=?";
    $ds = $da->GetData("dataset",$sql,array((string)$circleid));
    return $ds["dataset"]["rows"];
  }
  
  public function getCirclesAction()
  {
  	$da = $this->get("we_data_access");
    $user = $this->get('security.context')->getToken()->getUser();
    $request = $this->getRequest();
    $classify=$request->get('classify');
    $searchby=$request->get('searchby');
    $da->PageSize=21;
    $pageindex=((int)($request->get('pageindex'))>0?(int)($request->get('pageindex')):1)-1;
		$da->PageIndex=$pageindex;
    $rr=$this->getCircles($da,$user->getUserName(),$classify,$searchby);
    $rows=$rr['rows'];
    $circlenum=$rr['recordcount'];
    $pagecount=ceil($rr['recordcount']/21);
    return $this->render('JustsyBaseBundle:Circle:rem_circle_list.html.twig',array('circlerecommend'=> $rows,'pagecount'=> $pagecount,'circlenum'=> $circlenum,'pageindex'=> ($pageindex+1)));
  }
  //查找圈子首页
  public function searchIndexAction($network_domain, $account)
  {
    $da = $this->get("we_data_access");
    $user = $this->get('security.context')->getToken()->getUser();
    $request = $this->getRequest();
    $curr_network_domain = $user->edomain;
    $this->getEnterpriseCircle($user->getUsername());
    //圈子分类
    $circleclass = $this->getCircleClass($da);
    $circlerecommend_s = $this->getRecommendCircle($da, $account);
    $circlerecommend=$circlerecommend_s['rows'];
    $da->PageSize=21;
		$da->PageIndex=((int)($request->get('pageindex'))>0?(int)($request->get('pageindex')):1)-1;
    $circleindustry_s = $this->getCircles($da, $account,'');
    $circleindustry=$circleindustry_s['rows'];
    //返回
    $para = array();
    $para['curr_network_domain'] = $network_domain;
    $para['this'] = $this;
    $para['circleclass'] = $circleclass;
    $para['circlerecommend'] = $circlerecommend;
    $para['circleindustry'] = $circleindustry;
    $para['recommendpage']=ceil($circleindustry_s['recordcount']/21);
    $para['circlenum']=$circleindustry_s['recordcount'];
    if(in_array(\Justsy\BaseBundle\Management\FunctionCode::$CIRCLE_C,$user->getFunctionCodes())){
    	$para['createcircle'] = true;
    }else{
    	$para['createcircle'] = false;
    }
    $para['circleindustry'] = $circleindustry;
    return $this->render('JustsyBaseBundle:Circle:search_circles_new.html.twig',$para);
  }
  
  public function  applyCancelAction($circleid)
  {
      $da = $this->get("we_data_access");
      $user = $this->get('security.context')->getToken()->getUser();  	
      $apply = new \Justsy\BaseBundle\Management\ApplyMgr($da,null);
      $apply->SetCircleApplyInvalid($user->GetUserName(),$circleid);
      $re = array();
      $re["success"] = "1";
      $response = new Response(json_encode($re));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
  }
  //申请加入
  public function applyJoinAction()
  {
    $da = $this->get("we_data_access");
    $user = $this->get('security.context')->getToken()->getUser();
    $circleId = $this->get('request')->request->get('circleId');
    $apply =new \Justsy\BaseBundle\Management\ApplyMgr($da,null);   
    //判断是否已加入该圈子
    $sql = "select count(1) as cnt from we_circle_staff where circle_id=? and login_account=?";
    $ds = $da->GetData('we_circle_staff',$sql,array((string)$circleId,(string)$user->getUserName()));
    if (!$ds || $ds['we_circle_staff']['recordcount']==0)
    {
    	  //已经是该圈子成员
        return new Response("-1");
    }

    //判断是否已超出加入圈子数量的限制
		$ec=new \Justsy\BaseBundle\Management\EnoParamManager($da,$this->get('logger'));
    if($ec->IsBeyondJoinCircle($user->getUserName())){
    	return new Response("-2");
    }
    //判断改圈子成员数是否已满
    if($ec->IsBeyondCircleMembers($circleId)){
    	return new Response("-3");
    }
    //判断是否已申请或者超出申请限制
    $result=$apply->ApplyJoinCircle($user->getUsername(),$circleId,"");
    if($result==0 || $result==99999)
    {
       return new Response((string)$result);
    }    
    $circleObj = new \Justsy\BaseBundle\Management\CircleMgr($da,null,$circleId);
    $circle = $circleObj->Get();
    if($circle==null)
    {
    	return new Response("0");
    }
    $createStaff = $circle["create_staff"];
    $circleName=$circle["circle_name"];
    //para 圈子ID,申请人帐号,申请人姓名,圈子名称 DES加密
    $para = DES::encrypt($circleId.",".$user->getUserName().",".$user->nick_name.",".$circleName);
    $addurl = $this->generateUrl("JustsyBaseBundle_publicpage_agreejoincircle",array('para'=>$para),true);
    $refuseurl = $this->generateUrl("JustsyBaseBundle_circle_refusejoincircle",array(),true);
    $txt = $this->renderView("JustsyBaseBundle:Circle:mail_apply_join.html.twig",
      array("ename" => $user->ename,
        "realName" => $user->nick_name,
        "account" => DES::encrypt($user->getUserName()),
        "activeurl" => $addurl,
        "circlename" => $circleName,
        "refuseurl" => $refuseurl,
        "para" => $para));
    //发送站内消息
    $sqls = array(); $paras = array();
    $msgId = SysSeq::GetSeqNextValue($da,"we_message","msg_id");
    $sqls[] = "insert into we_message(msg_id,sender,recver,send_date,title,content)values(?,?,?,now(),?,?)";
    //$sqls[] = "insert into we_notify(notify_type, msg_id,notify_staff)values('01',?,?)";
    $paras[] = array((int)$msgId,(string)$user->getUserName(),(string)$createStaff,"申请加入圈子",$txt);
    //$paras[] = array((int)$msgId,(string)$createStaff);
    $da->ExecSQLs($sqls,$paras);
    
    Utils::saveMail($da,$user->getUsername(),$createStaff,"申请加入圈子",$txt,$circleId);
    //Utils::sendMail($this->get('mailer'),"申请加入微发发企业社交圈子",$this->container->getParameter('mailer_user'),null,$createStaff,$txt);
    //发送即时消息
    $im_sender = $this->container->getParameter('im_sender');
    $fafa_jid = $circle["fafa_jid"];
    $message = Utils::makeHTMLElementTag('employee',$user->fafa_jid,$user->nick_name)."申请加入您的圈子【".$circleName."】";
    $buttons = array();
    $buttons[]=array("text"=>"拒绝","code"=>"agree","value"=>"0","link"=> $refuseurl."?para=".$para);
    $buttons[]=array("text"=>"同意","code"=>"agree","value"=>"1","link"=> $addurl);
    Utils::sendImMessage($im_sender,$fafa_jid,"申请加入圈子",$message,$this->container,"",Utils::makeBusButton($buttons),false,Utils::$systemmessage_code);
    return new Response("1");
  }
  //同意加入圈子
  public function agreeJoinAction($para)
  {
  	//_urlSource
  	$res = $this->get('request');
  	$urlSource = $res->get("_urlSource"); //获取操作源。FaFaWin:从PC客户端操作的
    $paraArr = explode(",",trim(DES::decrypt($para)));
    $da = $this->get("we_data_access");
    //检查帐号是否存在
    $sql = "select a.eno,a.fafa_jid,a.nick_name,b.eshortname,a.login_account from we_staff a,we_enterprise b where a.eno=b.eno and login_account=?";
    $ds = $da->GetData('we_staff',$sql,array((string)$paraArr[1]));
    if (!$ds || $ds['we_staff']['recordcount']==0)
    {
      if(empty($urlSource)) return $this->render('JustsyBaseBundle:Circle:join_err.html.twig',array('error'=>'申请人帐号不存在！'));
      else
      {
		      $response = new Response(("{\"succeed\":0,\"msg\":\"申请人帐号不存在！\"}"));
					$response->headers->set('Content-Type', 'text/json');
					return $response;   
		  }    
    }
    $row = $ds['we_staff']['rows'][0];
    $eno = $row['eno'];
    $appy_user_jid = $row['fafa_jid'];
    $nick_name = $row['nick_name'];
    $eshortname = $row['eshortname'];
    $appy_login_account = $row['login_account'];
    $sql = "select count(1) as cnt from we_circle_staff where circle_id=? and login_account=?";
    $ds = $da->GetData('we_circle_staff',$sql,array((string)$paraArr[0],(string)$paraArr[1]));
    if ($ds && $ds['we_circle_staff']['rows'][0]['cnt']>0)
    {
      if(empty($urlSource)) return $this->render('JustsyBaseBundle:Circle:join_err.html.twig',array('error'=>'申请人已加入该圈子！'));
      else
      {
	      $response = new Response(("{\"succeed\":0,\"msg\":\"申请人已加入该圈子！\"}"));
				$response->headers->set('Content-Type', 'text/json');
				return $response; 
		  }     
    }
    else
    {
      $sqls = array(); $paras = array();
      //判断圈子是否有人
      $sql = "select min(a.circle_name) circle_name,min(a.fafa_groupid) fafa_groupid, count(1) as cnt from we_circle a, we_circle_staff b where a.circle_id=b.circle_id and b.circle_id=?";
      $ds = $da->GetData('we_circle_staff',$sql,array((string)$paraArr[0]));
      
      $circle_name= $ds['we_circle_staff']['rows'][0]['circle_name'];
      $fafa_groupid = $ds['we_circle_staff']['rows'][0]['fafa_groupid'];
      
      if ($ds && $ds['we_circle_staff']['rows'][0]['cnt']==0)
      {
        $sqls[] = "update we_circle set create_staff=? where circle_id=?";
        $paras[] = array((string)$paraArr[1],(string)$paraArr[0]);
      }
      //圈子id+nick_name不能重复
	    $sql = "select count(1) cnt from we_circle_staff where circle_id=? and nick_name=?";
	    $ds = $da->GetData("cnt", $sql, array((string)$paraArr[0],(string)$nick_name));
	    if ($ds && $ds['cnt']['rows'][0]['cnt']>0)
	    {
	      $nick_name = $nick_name."(".$eshortname.")";
	    }
      $sqls[] = "insert into we_circle_staff (circle_id,login_account,nick_name) values (?,?,?)";
      $paras[] = array((string)$paraArr[0],(string)$paraArr[1],(string)$nick_name);
      //10－加入外部圈子－5
      $sqls[] = "insert into we_staff_points (login_account,point_type,point_desc,point,point_date)
        values (?,?,?,?,now())";
      $paras[] = array(
        (string)$paraArr[1],
        (string)'10',
        (string)'成功加入外部圈子'.$circle_name.'，获得积分5',
        (int)5);
      $da->ExecSQLs($sqls,$paras);
      
      $apply = new \Justsy\BaseBundle\Management\ApplyMgr($da,null);
      $apply->SetCircleApplyInvalid($paraArr[1],$paraArr[0]);
      //变更版本信息
      //$curuser = $this->get('security.context')->getToken()->getUser();
      //$eno = $curuser->eno;
      $verchange = new \Justsy\BaseBundle\Management\VersionChange($da,$this->get("logger"));
  	  $result = $verchange->SetVersionChange(3,$paraArr[0],$eno);      
      //发送即时消息通知申请人及成员
      $message = Utils::makeHTMLElementTag('employee',$appy_user_jid,$nick_name)."加入了圈子【".Utils::makeHTMLElementTag('circle',$fafa_groupid,$circle_name)."】";
      $excludeLst=array();
      $excludeLst[] = $appy_login_account; //排除自己    	
      $this->sendPresenceCirlce($paraArr[0],"circle_addmember",$message,"",$excludeLst);
      
      $message = "你已成功加入圈子【".$circle_name."】。";
      $im_sender = $this->container->getParameter('im_sender');
      Utils::sendImMessage($im_sender,$appy_user_jid,"圈子消息",$message,$this->container,"","",false,Utils::$systemmessage_code);      
      
      $backurl = $this->generateUrl("JustsyBaseBundle_enterprise",array('network_domain'=>$paraArr[0]),true);
      if(empty($urlSource)) return $this->render('JustsyBaseBundle:Error:success.html.twig',array('backurl'=>$backurl));
      else
      {
		      $response = new Response(("{\"succeed\":1,\"name\":\"".$circle_name."\",\"circleurl\":\"".$backurl."\"}"));
					$response->headers->set('Content-Type', 'text/json');
					return $response;
		  }
    }
  }
  //拒绝加入
  public function refuseJoinAction()
  {
    $para = $this->getRequest()->get('para');
    $paraArr = explode(",",trim(DES::decrypt($para)));
    $da = $this->get("we_data_access");
    $user = $this->get('security.context')->getToken()->getUser();
    //检查帐号是否存在
    $sql = "select eno from we_staff where login_account=?";
    $ds = $da->GetData('we_staff',$sql,array((string)$paraArr[1]));
    if (!$ds || $ds['we_staff']['recordcount']==0)
    {
      return new Response("0");
    }
    $eno = $ds['we_staff']['rows'][0]['eno'];
    $sql = "select count(1) as cnt from we_circle_staff where circle_id=? and login_account=?";
    $ds = $da->GetData('we_circle_staff',$sql,array((string)$paraArr[0],(string)$paraArr[1]));
    if ($ds && $ds['we_circle_staff']['rows'][0]['cnt']>0)
    {
      return new Response("0");
    }
    else
    {
      $txt = "您加入圈子【".$paraArr[3]."】的请求被拒绝了！";
      $msgId = SysSeq::GetSeqNextValue($da,"we_message","msg_id");
      $sql = "insert into we_message(msg_id,sender,recver,send_date,title,content)values(?,?,?,now(),?,?)";
      $param = array((int)$msgId,(string)$user->getUserName(),(string)$paraArr[1],"申请加入圈子被拒绝",$txt);
      $da->ExecSQL($sql,$param);
      
      $apply =new \Justsy\BaseBundle\Management\ApplyMgr($da,null);
      $apply->SetCircleApplyInvalid($paraArr[1],$paraArr[0]);
      
      //发送即时消息通知申请人
    	$im_sender = $this->container->getParameter('im_sender');
      $message =  $txt;
      Utils::sendImMessage($im_sender,$paraArr[1],"圈子消息",$message,$this->container,"","",true,Utils::$systemmessage_code);      
      return new Response("1");
    }
  }
  //查找圈子
  public function findAction($network_domain)
  {
    $result = array();
    $user = $this->get('security.context')->getToken()->getUser();
    $this->getEnterpriseCircle($user->getUsername());
    $result['curr_network_domain'] = '';
    $result['this'] = $this;
    $result['rows'] = array();
    $result['curr_network_domain'] = $network_domain;
    $para = $this->get("request")->request->get("searchCondition");
    $da = $this->get("we_data_access");
    $sql = "select a.circle_id,circle_name,circle_desc,create_staff,
      (select count(1) from we_circle_staff b where a.circle_id=b.circle_id and login_account=?) as is_join 
      from we_circle a where circle_name like concat('%', ?, '%') and ifnull(enterprise_no,'')='' and join_method='0'
      and 0+a.circle_id>10000";
    $ds = $da->GetData('we_circle',$sql,array((string)$user->getUsername(),(string)$para));
    if ($ds && $ds['we_circle']['recordcount']>0)
    {
      $result['rows'] = $ds['we_circle']['rows'];
    }
    return $this->render('JustsyBaseBundle:Circle:search_result.html.twig',$result);
  }
  //获取圈子分类
  public function getCircleClass($da)
  {
    $da = $this->get("we_data_access");
    $re = array();
    $fileurl = $this->container->getParameter('FILE_WEBSERVER_URL');
    $sql = "select classify_id,classify_name,parent_classify_id,
      concat('".$fileurl."',case trim(icon_path) when '' then null else icon_path end) as icon_path 
      from we_circle_class order by classify_order_by";
    $ds = $da->GetData('we_circle_class',$sql);
    if ($ds && $ds['we_circle_class']['recordcount']>0)
    {
      $root = array_filter($ds['we_circle_class']['rows'],function($v)
      {
        return $v['classify_id'] == $v['parent_classify_id'];
      });
      $i = 0;
      foreach($root as $key=>$value)
      {
        $re[$i]['parent'] = $value;
        $re[$i]['child'] = array_filter($ds['we_circle_class']['rows'],function($v) use($value)
        {
          return $v['parent_classify_id'] == $value['classify_id'] && $v['classify_id'] != $value['classify_id'];
        });
        $i++;
      }
    }
    return $re;
  }
  //推荐圈子
  public function getRecommendCircle($da, $account)
  {
    $cache = new \Justsy\BaseBundle\DataAccess\DACache($this->container);

    $da = $this->get("we_data_access");
    $re = array();
    $fileurl = $this->container->getParameter('FILE_WEBSERVER_URL');
    $sql = "select a.circle_id,circle_name,a.circle_desc,
      concat('".$fileurl."',case trim(logo_path_big) when '' then '' else logo_path_big end) as logo_path_big, 
      circle_recommend,b.nick_name,create_staff,
      (select count(*) from we_circle_staff c where c.circle_id=a.circle_id) as cnt,
      case ifnull(c.circle_id,'') when '' then '0' else '1' end as isjoin
      from we_circle a left join we_staff b on a.create_staff=b.login_account
      left join we_circle_staff c on a.circle_id=c.circle_id and c.login_account=?
      where enterprise_no is null and join_method='0' and 0+a.circle_id>10000 
      and not exists(select 1 from we_circle_staff wcs where wcs.circle_id=a.circle_id and wcs.login_account=?)
      and not exists(select 1 from we_apply wa where wa.recv_type='c' and wa.recv_id=a.circle_id and wa.account=?)
      and circle_recommend is not null
      order by circle_recommend desc, a.create_date";
//    $ds = $da->GetData('we_circle',$sql,array((string)$account,(string)$account,(string)$account));

    $ds = $cache->get("getRecommendCircle", $account);
    if (!$ds)
    {
      $ds = $da->GetData('we_circle',$sql,array((string)$account,(string)$account,(string)$account));
      $cache->set("getRecommendCircle", $account, $ds, array());  //不设置依赖表，相当于30分钟才更新一次
    }

    if ($ds && $ds['we_circle']['recordcount']>0)
    {
      $re = $ds['we_circle'];
    }
    else{
    	$re=array('rows'=> array(),'recordcount'=>0);
    }
    return $re;
  }
  
  public function recommendlistAction()
  {
  	  $user = $this->get('security.context')->getToken()->getUser();
  	  $recommendlist= $this->getRecommendCircle($this->get('we_data_access'),$user->getUserName()); 
	    $resp = new Response(json_encode($recommendlist['rows']));
	    $resp->headers->set('Content-Type', 'text/json');
	    return $resp;
  }
  public function getCircles($da, $account,$classify,$searchby=''){
  	$da = $this->get("we_data_access");
    $re = array('recordcount'=>0,'rows'=> array());
    $fileurl = $this->container->getParameter('FILE_WEBSERVER_URL');
    $sql = "select a.circle_id,circle_name,a.circle_desc,
      concat('".$fileurl."',case trim(logo_path_big) when '' then null else logo_path_big end) as logo_path_big, 
      circle_recommend,b.nick_name,create_staff,
      (select count(*) from we_circle_staff c where c.circle_id=a.circle_id) as cnt,
      case ifnull(c.circle_id,'') when '' then '0' else '1' end as isjoin,
      case ifnull(d.recv_id,'') when '' then '0' else '1' end as isapply
      from we_circle a left join we_staff b on a.create_staff=b.login_account
      left join we_circle_staff c on a.circle_id=c.circle_id and c.login_account=?
      left join we_apply d on a.circle_id=d.recv_id and d.is_valid='1' and d.recv_type='c' and account=?
      where enterprise_no is null and join_method='0' and 0+a.circle_id>10000 ".(empty($classify)?"":"and a.circle_class_id=? ").(empty($searchby)?"":" and a.circle_name like ? ").
      " order by circle_recommend , a.create_date";
      $params=array((string)$account,(string)$account);
      if(!empty($classify))
       array_push($params,$classify);
      if(!empty($searchby))
      	array_push($params,'%'.$searchby.'%');
    $ds = $da->GetData('we_circle',$sql,$params);
    if ($ds && $ds['we_circle']['recordcount']>0)
    {
      $re = $ds['we_circle'];
    }
    return $re;
  }
  //行业圈子
  public function getIndustryCircle($da, $account)
  {
    $da = $this->get("we_data_access");
    $re = array();
    $fileurl = $this->container->getParameter('FILE_WEBSERVER_URL');
    $sql = "select a.circle_id,circle_name,a.circle_desc,
      concat('".$fileurl."',case trim(a.logo_path_big) when '' then null else a.logo_path_big end) as logo_path_big, 
      circle_recommend,b.nick_name,a.create_staff,
      (select count(*) from we_circle_staff c where c.circle_id=a.circle_id) as cnt,
      case ifnull(f.circle_id,'') when '' then '0' else '1' end as isjoin
      from we_circle a 
      inner join we_circle_class d on a.circle_class_id=d.classify_id
      left join we_staff b on a.create_staff=b.login_account
      left join we_enterprise c on a.circle_class_id=c.industry
      left join we_staff e on c.eno=e.eno
      left join we_circle_staff f on a.circle_id=f.circle_id and f.login_account=?
      where ifnull(enterprise_no,'')='' and d.parent_classify_id='001' and e.login_account=?
      order by circle_recommend desc";
    $ds = $da->GetData('we_circle',$sql,array((string)$account,(string)$account));
    if ($ds && $ds['we_circle']['recordcount']>0)
    {
      $re = $ds['we_circle'];
    }
    return $re;
  }
  //检查邀请帐号是否已经加入圈子
  public function checkInvMailAction()
  {
    $da = $this->get("we_data_access");
    $circleId = $this->get('request')->request->get('circleId');
    $account = $this->get('request')->request->get('mail');
    $sql = "select count(1) as cnt from we_circle_staff where login_account=? and circle_id=?";
    $ds = $da->GetData('we_circle_staff',$sql,array((string)$account,(string)$circleId));
    return new Response($ds['we_circle_staff']['rows'][0]['cnt']);
  }
  
  public function getCircleCardAction()
  {
    $list = array();
    $request = $this->getRequest();
    $circle_id = $request->get('circle_id');
    
    $da = $this->get("we_data_access");    
    $user = $this->get('security.context')->getToken()->getUser();
    $circlemgr = new \Justsy\BaseBundle\Management\CircleMgr($da,null);
    $data = $circlemgr->Get($circle_id);
    $data2 = $circlemgr->GetTopMemberAndCount($circle_id);
    $list["we_circle"] = $data;
    $list["detail"] = $data2;
    $list["circle_join"] = true;
    if(!in_array(\Justsy\BaseBundle\Management\FunctionCode::$CIRCLE_JOIN_C,$user->getFunctionCodes())){
    	$list["circle_join"]= false;
    }
    return $this->render("JustsyBaseBundle:Circle:circle_card.html.twig", $list);   
  }
  
  //向指定的圈子的成员发送出席
  public function sendPresenceCirlce($circleid,$caption="circle_addmember",$msg,$buttons="",$excludeLst=null)
  {
      	$sql = "select c.fafa_jid,c.login_account from we_circle_staff a,we_circle b,we_staff c where b.circle_id=a.circle_id and a.login_account=c.login_account and b.circle_id=? and c.fafa_jid is not null";
      	$da = $this->get("we_data_access");
      	$ds = $da->GetData("ims",$sql,array((string)$circleid));
      	$staffArr = array();
      	$len = count($ds["ims"]["rows"]);
      	for($i=0;$i<$len; $i++)
      	{
			if(!empty($excludeLst) && in_array($ds["ims"]["rows"][$i]["login_account"],$excludeLst)) continue;
      		  $staffArr[]=$ds["ims"]["rows"][$i]["fafa_jid"];
      	}
      	$sender = $this->container->getParameter('im_sender');
      	$staffArr=null;
      	$onlinejid = Utils::findonlinejid($this->get("we_data_access_im"),$staffArr);
      	$jidArr = array();
      	$len = count($onlinejid);
      	for($i=0;$i<$len; $i++)
      	{
      		$jidArr[] = $onlinejid[$i];
      		if($i>0 && $i%5000==0)
      		{
      		  	Utils::sendImPresence($sender,implode(",",$jidArr),$caption,$msg, $this->container,"",$buttons,false,Utils::$systemmessage_code);  
      		  	$jidArr = array();
      		}
      	}
      	if(count($jidArr)>0)
      	    Utils::sendImPresence($sender,implode(",",$jidArr),$caption,$msg, $this->container,"",$buttons,false,Utils::$systemmessage_code);  
  }
  
  //获得圈子成员
  public function getCircleMemberAction(Request $request)
  {
  	$user = $this->get('security.context')->getToken()->getUser();
  	$circleMgr = new \Justsy\BaseBundle\Management\CircleMgr($this->get("we_data_access"),$this->get("we_data_access_im"));
  	$circleid = $request->get("circleid");
    $result = $circleMgr->getCircleMembers($circleid,$user->getUserName());
    $response = new Response(json_encode($result));
		$response->headers->set('Content-Type', 'text/json');
		return $response;
  }  
  
  
  public function getCircleStaffScalarAction()
  {
  	$sql = " select circle_id, count(*) scalar from we_circle_staff ".
  	       " where circle_id = (select circle_id from we_circle  inner join we_staff on create_staff=login_account and eno=enterprise_no where login_account=?') or circle_id in(9999,10000) group by circle_id ";
  	       
  	$da = $this->get("we_data_access");    
    $user = $this->get('security.context')->getToken()->getUser();
    $data2 = $circlemgr->GetTopMemberAndCount($circle_id);
  	
  }
  
  //保存头像
  public function savePhotoAction()
  {
  	$da = $this->get('we_data_access');
  	$request = $this->getRequest();
  	$user=$this->get('security.context')->getToken()->getUser();  	  
  	$fileurl = $this->container->getParameter('FILE_WEBSERVER_URL');  	
  	$session = $this->get('session');
  	$re=array('s'=>1,'m'=>'','file'=>'');
  	try{
  		if($user->getUserName()!=null || $user->getUserName()!="")
  		{
		  	$filename120 = $session->get("avatar_big"); 
		  	$filename48 = $session->get("avatar_middle"); 
		  	$filename24 = $session->get("avatar_small");
		  	$dm = $this->get('doctrine.odm.mongodb.document_manager');
		  	$file_middle=$this->saveFile($filename48,$dm);
		  	$file_small=$this->saveFile($filename24,$dm);
		  	$file_big=$this->saveFile($filename120,$dm);
		  	//更新头像
		  	$sql="update we_staff set photo_path=?,photo_path_small=?,photo_path_big=? where login_account=?";
		  	$params=array($file_middle,$file_small,$file_big,$user->getUserName());
		  	if(!$da->ExecSQL($sql,$params))
		  		$re=array('s'=>0,'m'=>'上传失败','file'=>'');
		  	else
		  		$re=array('s'=>1,'m'=>'','file'=> $file_big);
	    }
	    else
	    {
	    	$re=array('s'=>0,'m'=>'上传失败','file'=>'');
	    }
	  }
	  catch(\Exception $e)
	  {
	  	$re=array('s'=>0,'m'=>'上传失败','file'=>'');
	  	$this->get("logger")->err($e);
	  }
	  $response = new Response(json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;	
  }
    
  //获得人脉圈子五个推荐企业及五个人脉
  public function getConnectionAction()
  {
  	$user = $this->get('security.context')->getToken()->getUser();
    $request = $this->getRequest();
    $da = $this->get('we_data_access');
    
    $da->PageSize= $request->get('pagesize',5);
	  $da->PageIndex = 0;	  	
	  $friendcircle=new FriendCircle($da,$this->get('logger'),$this->container);
	  $rows=$friendcircle->getRecomEno($user->getUserName());
	  $member = $this->getConnectionMember($user->eno,$user->getUserName());
	  $result = array("enterprise" => $rows,"member" => $member);
	  $response=new Response(json_encode($result));
	  $response->headers->set('Content-Type','Application/json');
	  return $response;
  }
  
  //获取邀请对象
  public function getInviteMemberAction()
  {
  	$user = $this->get('security.context')->getToken()->getUser();
    $request = $this->getRequest();
    $da = $this->get('we_data_access');
    
    $da->PageSize= $request->get('pagesize',8);
	  $da->PageIndex =$request->get('pageindex')?$request->get('pageindex')-1:0;
	  $ds=$this->getInviteMember($da);
	  $rows=$ds['rows'];
	  $response=new Response(json_encode($rows));
	  $response->headers->set('Content-Type','Application/json');
	  return $response;
  }
  
  public function getInviteMember($da)
  {
  	$user = $this->get('security.context')->getToken()->getUser();
  	$account=$user->getUserName();
  	$eno=$user->eno;
  	$members=array('recordcount'=>0,'rows'=>array());
  	$fileurl = $this->container->getParameter('FILE_WEBSERVER_URL');
    $sql = "select a.login_account,a.nick_name,fafa_jid,
      concat('".$fileurl."',case trim(ifnull(a.photo_path,'')) when '' then null else a.photo_path end) as photo_path
      from we_staff a where a.eno=? and a.login_account!=? and a.state_id<>'3' and a.login_account not in('corp@fafatime.com','service@fafatime.com','sysadmin@fafatime.com') and a.auth_level<>? and not exists(select 1 from we_micro_account b where b.number=a.login_account) order by a.login_account";
    $ds = $da->GetData('we_staff',$sql,array((string)$eno,(string)$account,Identify::$SIdent));
    if ($ds && $ds['we_staff']['recordcount']>0)
    {
      $members = $ds['we_staff'];
    }
    return $members;
  }
  public function getMindWordAction()
  {
  	$user = $this->get('security.context')->getToken()->getUser();
    $request = $this->getRequest();
    $searchby=$request->get('searchby');
    $da = $this->get('we_data_access');
	  $rows=array();
	  if(!empty($searchby)){
		 	$sql="select a.login_account,a.nick_name from we_staff a where not exists(select 1 from we_micro_account b where b.number=a.login_account) and a.auth_level<>?";
			$sql.=(empty($searchby)?"":((strlen($searchby)>mb_strlen($searchby,'utf8'))?" and a.nick_name like ? ":" and (a.nick_name like ? or a.login_account like ?) "));
			$sql.=" limit 0,10";
			$params=array(Identify::$SIdent);
			if(!empty($searchby)){
	          	array_push($params,"%".$searchby."%");
	          	if(strlen($searchby)==mb_strlen($searchby,'utf8'))
	          		array_push($params,"%".$searchby."%");
	          }
		  $ds=$da->Getdata('mind',$sql,$params);
		  $rows=$ds['mind']['rows'];
		}
	  $response=new Response(json_encode($rows));
	  $response->headers->set('Content-Type','Application/json');
	  return $response;
  }
  
  //获得五个推荐人脉成员
  public function getConnectionMember($eno,$account)
  {
     $request = $this->getRequest();
     $da = $this->get('we_data_access');
     $da->PageSize =$request->get('pagesize',5);
	   $da->PageIndex = 0;
       
     $friendcircle=new FriendCircle($da,$this->get('logger'),$this->container);
     $rows=$friendcircle->getRemAccount($eno,$account);
     return $rows;
  }
  
  //取部门信息
  public function getdepartAction()
  {
  	$da = $this->get("we_data_access");
  	$user = $this->get('security.context')->getToken()->getUser();
  	$sql =  " select c.eno,c.dept_id,c.dept_name,ifnull((select count(*) from we_staff d where d.dept_id=c.dept_id group by d.dept_id),0) member,'' photo ".
  	        " from we_enterprise a inner join we_staff b on a.eno=b.eno ".
  	        "      inner join we_department c on c.create_staff=case when a.sys_manager is null or a.sys_manager='' then a.create_staff else a.sys_manager end ".
  	        "where login_account=? and left(fafa_deptid,1)<>'v' order by c.dept_id asc limit 6";
    $ds = $da->GetData("we_depart",$sql,array((string)$user->getUserName()));
    if($ds && $ds["we_depart"]["recordcount"]>0)
    {
    	 for($i=0;$i< $ds["we_depart"]["recordcount"];$i++)
    	 {
    	 	 if ($ds["we_depart"]["rows"][$i]["member"]>0)
    	 	 {
    	 	 	  $data = $this->getMemberInfo($user->eno,$ds["we_depart"]["rows"][$i]["dept_id"]);
    	 	 	  if(!empty($data))
    	 	 	  {
    	 	 	  	$ds["we_depart"]["rows"][$i]["dept_id"]=$data[0];
    	 	 	  	$ds["we_depart"]["rows"][$i]["dept_name"]=$data[1];
    	 	 	  	$ds["we_depart"]["rows"][$i]["photo"]=$data[2];
    	 	 	  }
    	 	 } 	  
    	 }
    }
    $manager = $this -> getManager($user->eno);
    $result = array("managerTable"=> $manager,"departTable"=> $ds);
    $response=new Response(json_encode($result));
	  $response->headers->set('Content-Type','Application/json');
	  return $response;
  }
  
  private function getMemberInfo($eno,$deptid)
  {
  	$da = $this->get("we_data_access");
  	$sql = "select login_account,nick_name,photo_path_small photo from we_staff where eno=? and dept_id=? order by active_date asc limit 1";
  	$ds = $da->GetData("we_staff",$sql,array((string)$eno,(string)$deptid));
  	$result = array();
  	if($ds && $ds["we_staff"]["recordcount"]>0)
  	{
  		 $result[0] = $ds["we_staff"]["rows"][0]["login_account"];
  		 $result[1] = $ds["we_staff"]["rows"][0]["nick_name"];
  		 $result[2] = $ds["we_staff"]["rows"][0]["photo"];
  	}
  	return $result;
  }
  
  //获得企业总经理职务
  private function getManager($eno)
  {
  	$da = $this->get("we_data_access");
  	$sql = "select login_account,nick_name,photo_path_small from we_staff where replace(replace(duty,' ',''),'　','')='总经理' and eno=? order by active_date asc limit 1;";
  	$ds = $da->GetData("we_manager",$sql,array((string)$eno));
  	return $ds;
  }
  
  //设置下次不再显示该页面
  public function setparaAction()
  {
  	 $da = $this->get('we_data_access');
     $user = $this->get('security.context')->getToken()->getUser();
     $sql = "select para_value from we_staff_para where login_account=? and para_id='SHOW_CIRCLE_DEFAULT'";
     $ds = $da->getData("we_para",$sql,array((string)$user->getUserName()));
     $parameter = array();
     if ( $ds && $ds["we_para"]["recordcount"]==0)
       $sql = "insert we_staff_para values(?,'SHOW_CIRCLE_DEFAULT',1)";
     else
       $sql = "update we_staff_para set para_value=1 where login_account=? and para_id='SHOW_CIRCLE_DEFAULT'";
     $result = array("succeed" =>1);
     try
     {
       $da->ExecSQL($sql,array((string)$user->getUserName()));
     }
     catch(\Exception $e)
     {
     	 $result = array("succeed" =>0);
     }
     $response = new Response( json_encode($result));
		 $response->headers->set('Content-Type', 'text/json');
		 return $response;
  }
}