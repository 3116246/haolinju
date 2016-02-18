<?php

namespace Justsy\InterfaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Management\MicroAccountMgr;


class MicroMessageController extends Controller
{ 
  //获取当前企业的所有公众号列表
  public function getmicroaccountAction(){
  	$request = $this->getRequest();  	
  	$da = $this->get("we_data_access");
  	$currUser = $this->get('security.context')->getToken();
    if(!empty($currUser)){
       $currUser = $currUser->getUser(); 
    }
    else
    {
    	  //当应用通过api接口调用时,不用登录,只能通过openid获取人员信息
    	  $baseinfoCtl = new \Justsy\BaseBundle\Management\Staff($da,null,$request->get("openid"),$this->get("logger"));    	  
    	  $currUser = $baseinfoCtl->getSessionUser();
    }
  	$re = array("returncode" => ReturnCode::$SUCCESS);
    $micro_use=$request->get('micro_use');
  	$mode = $request->get("mode");
  	$mode = empty($mode)||($mode!='EXCLUDE-ATTEN')? false:true;
  	$mgr = new MicroAccountMgr($da,$this->get("we_data_access_im"),$currUser,$this->get("logger"),$this->container);
  	$rows = $mgr->getmicroaccount($mode, $micro_use);
  	for ($i = 0; $i < count($rows); $i++) {
    		$micro_account=$rows[$i]["number"];
    		$group=$mgr->getgrouplist($micro_account);
    		$rows[$i]["grouplist"]=$group;
    }
  	$re["list"]=$rows;  	
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  //检测公众号和登录人是否关注上
  public function microaccountcheckattenAction(){
    $request = $this->getRequest();
    $da = $this->get("we_data_access");
    $micro_account=$request->get('microaccount');
    $re = array("returncode" => ReturnCode::$SUCCESS,'isatten'=>1);
    $currUser = $this->get('security.context')->getToken();
    if (empty($micro_account)) {
      $re= array("returncode" => ReturnCode::$SYSERROR,"msg"=>"公众号不能为空");  
      $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }
    if(!empty($currUser)) {
       $currUser = $currUser->getUser();
    }
    else
    {
        //当应用通过api接口调用时,不用登录,只能通过openid获取人员信息
        $baseinfoCtl = new \Justsy\BaseBundle\Management\Staff($da,null,$request->get("openid"),$this->get("logger"));        
        $currUser = $baseinfoCtl->getSessionUser();
    }
    if(empty($currUser)) {
      $re = array("returncode" => ReturnCode::$NOTLOGIN,'msg'=>'请先登录系统');
    }
    else{
      $mgr = new MicroAccountMgr($da,$this->get("we_data_access_im"),$currUser,$this->get("logger"),$this->container);

      $count=$mgr->check_atten($micro_account);

      if($count==0) $re = array("returncode" => ReturnCode::$SUCCESS,'isatten'=>0);
      else if($count==-1) $re = array("returncode" => ReturnCode::$SYSERROR,'msg'=>'判断是否存在的过程中,出现错误');
    }
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }

    public function getattenmicroaccountAction(){
  	$request = $this->getRequest();  	
  	$da = $this->get("we_data_access");
    //$micro_use=$request->get('micro_use');
    $re = array("returncode" => ReturnCode::$SUCCESS);
//    if($micro_use!=null && $micro_use!=1 && $micro_use!=0){
//      $re = array("returncode" => ReturnCode::$SYSERROR,'msg'=>'参数micro_use无效');
//      $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
//      $response->headers->set('Content-Type', 'text/json');
//      return $response;
//    }
  	$currUser = $this->get('security.context')->getToken()->getUser();
  	$mgr = new MicroAccountMgr($da,$this->get("we_data_access_im"),$currUser,$this->get("logger"),$this->container);
    
    $rows = $mgr->getMy();
  	for ($i = 0; $i < count($rows); $i++) {
    		$micro_account=$rows[$i]["number"];
    		$group=$mgr->getgrouplist($micro_account);
    		$rows[$i]["grouplist"]=$group;
    }
    $re["list"] = $rows;
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  private function checkmail(){
      return "/([a-z0-9]*[-_.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[.][a-z]{2,3}([.][a-z]{2})?/i";
  }
  //通过指定帐号查询数据
  public function getquerybynumberAction(){
    $request = $this->getRequest();   
    $da = $this->get("we_data_access");
    $currUser = $this->get('security.context')->getToken();
    $re = array("returncode" => ReturnCode::$SUCCESS);
    $microAccount=$request->get("microaccount");
    if (empty($microAccount)) {
      $re= array("returncode" => ReturnCode::$SYSERROR,"msg"=>"帐号不能为空");  
      $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }
    if(!preg_match($this->checkmail(),$microAccount)){
      $re= array("returncode" => ReturnCode::$SYSERROR,"msg"=>"帐号必须是邮箱格式");  
      $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }
    if(!empty($currUser)){
       $currUser = $currUser->getUser(); 
    }
    else
    {
        $baseinfoCtl = new \Justsy\BaseBundle\Management\Staff($da,null,$request->get("openid"),$this->get("logger"));        
        $currUser = $baseinfoCtl->getSessionUser();
    }   
    if(empty($currUser)) {
      $re = array("returncode" => ReturnCode::$NOTLOGIN,'msg'=>'请先登录');
    }
    else{
      $mgr = new MicroAccountMgr($da,$this->get("we_data_access_im"),$currUser,$this->get("logger"),$this->container);
      $result=$mgr->microaccount_query($microAccount);
      //var_dump($result);
      for ($i = 0; $i < count($result); $i++) {
        $micro_account=$result[$i]["number"];
        $group=$mgr->getgrouplist($micro_account);
        $result[$i]["grouplist"]=$group;
      }
      if(count($result)>0) $re["data"]=$result[0];
      else $re["data"]=null;
    }
    
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  //查询公众号
  public function queryAction()
  {
    $request = $this->getRequest();  	
  	$da = $this->get("we_data_access");
  	$currUser = $this->get('security.context')->getToken();  	
    if(!empty($currUser)){
       $currUser = $currUser->getUser(); 
    }
    else
    {
    	  $baseinfoCtl = new \Justsy\BaseBundle\Management\Staff($da,null,$request->get("openid"),$this->get("logger"));    	  
    	  $currUser = $baseinfoCtl->getSessionUser();
    }  	
  	$re = array("returncode" => ReturnCode::$SUCCESS);  	
  	$microAccount=$request->get("microaccount");
  	$micro_use=$request->get('micro_use');
    if($micro_use!=null && $micro_use!=1 && $micro_use!=0){
      $re = array("returncode" => ReturnCode::$SYSERROR,'msg'=>'参数micro_use无效');
      $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }
  	if(empty($microAccount))
  	{
    	 $re["returncode"] = ReturnCode::$SYSERROR;
    	 $re["msg"] = "公众号不能为空";
  	}
  	else
  	{      
    	$mgr = new MicroAccountMgr($da,$this->get("we_data_access_im"),$currUser,$this->get("logger"),$this->container);
    	$mode = $request->get("mode");
      $mode = empty($mode)||($mode!='EXCLUDE-ATTEN')? false:true;
    	$result=$mgr->microaccount_search($microAccount,$mode,$micro_use);
    	for ($i = 0; $i < count($result); $i++) {
    		$micro_account=$result[$i]["number"];
    		$group=$mgr->getgrouplist($micro_account);
    		$result[$i]["grouplist"]=$group;
    	}
    	$re["list"] = $result;
      //var_dump($result);
  	}
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;      	
  }
  
  public function deleteaccountAction()
  {
    $request = $this->getRequest();  	
  	$da = $this->get("we_data_access");
  	$currUser = $this->get('security.context')->getToken()->getUser();
  	$re = array("returncode" => ReturnCode::$SUCCESS);  	
  	$microAccount=$request->get("micro_id");
  	if(empty($microAccount))
  	{
  	    	$re["returncode"] = ReturnCode::$SYSERROR;
  	    	$re["msg"] = "公众号不能为空";
  	}
  	else
  	{
  	    	$mgr = new MicroAccountMgr($da,$this->get("we_data_access_im"),$currUser,$this->get("logger"),$this->container);
  	    	$mgr->removeByID($microAccount);
  	}
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;      	
  }
  
  //关注指定企业的指定公众号
  public function attenmicroaccountAction()
  {
    $request = $this->getRequest();  	
  	$da = $this->get("we_data_access");
  	$currUser = $this->get('security.context')->getToken()->getUser();
  	$re = array("returncode" => ReturnCode::$SUCCESS);  	
  	$microAccount=$request->get("microaccount");
  	if(empty($microAccount))
  	{
  	    	$re["returncode"] = ReturnCode::$SYSERROR;
  	    	$re["msg"] = "关注的公众号不能为空";
  	}
  	else
  	{
  	    	$mgr = new MicroAccountMgr($da,$this->get("we_data_access_im"),$currUser,$this->get("logger"),$this->container);
  	    	$flag=$mgr->agree_inviteatten($microAccount,$currUser->getUserName(),"0");
          //var_dump($flag);
  	    	if($flag["success"]!=0)
  	    	{
  	    		  $re["returncode"] = ReturnCode::$SYSERROR;
  	    	    $re["msg"] = "公众号关注失败";
  	    	}  	    	
  	}
  	//
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/html');
    return $response;
  }
  //取消关注指定的公众号
  public function cancelattenAction()
  {
    $request = $this->getRequest();  	
  	$da = $this->get("we_data_access");
  	$currUser = $this->get('security.context')->getToken()->getUser();
  	$re = array("returncode" => ReturnCode::$SUCCESS);  	
  	$microAccount=$request->get("microaccount");
  	if(empty($microAccount))
  	{
  	    	$re["returncode"] = ReturnCode::$SYSERROR;
  	    	$re["msg"] = "取消关注的公众号不能为空";
  	}
  	else
  	{
  	    	$mgr = new MicroAccountMgr($da,$this->get("we_data_access_im"),$currUser,$this->get("logger"),$this->container);
  	    	$flag=$mgr->micro_fans_unfollow($microAccount,$currUser->getUserName());
  	    	if($flag===false)
  	    	{
  	    		  $re["returncode"] = ReturnCode::$SYSERROR;
  	    	    $re["msg"] = "取消关注失败";
  	    	}
  	}
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;      	
  }
  
  //邀请好友关注指定的公众号
  public function inviteattenAction()
  {
    $request = $this->getRequest();  	
  	$da = $this->get("we_data_access");
  	$currUser = $this->get('security.context')->getToken()->getUser();
  	$re = array("returncode" => ReturnCode::$SUCCESS);  	
  	$microAccount=$request->get("microaccount");
  	$roster=$request->get("roster");
  	if(empty($microAccount))
  	{
  	    	$re["returncode"] = ReturnCode::$SYSERROR;
  	    	$re["msg"] = "关注的公众号不能为空";
  	}
  	else if(empty($roster))
  	{
  	    	$re["returncode"] = ReturnCode::$SYSERROR;
  	    	$re["msg"] = "好友帐号不能为空";
  	}
  	else
  	{
  	    	$mgr = new MicroAccountMgr($da,$this->get("we_data_access_im"),$currUser,$this->get("logger"),$this->container);
  	    	$mgr->inviteatten($microAccount,$roster);
  	}
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;      	
  } 
  //同意关注接口
  public function agreeinviteAction()
  {
    $request = $this->getRequest();  	
  	$da = $this->get("we_data_access");
  	$currUser = $this->get('security.context')->getToken()->getUser();
  	$re = array("returncode" => ReturnCode::$SUCCESS);  	
  	$microAccount=$request->get("microaccount");
  	$roster=$request->get("inviteaccount");
  	$invite=$request->get("invite");
  	if(empty($microAccount)){
  	    	$re["returncode"] = ReturnCode::$SYSERROR;
  	    	$re["msg"] = "公众号不能为空";
  	}
  	else if(empty($roster)){
  	    	$re["returncode"] = ReturnCode::$SYSERROR;
  	    	$re["msg"] = "关注人不能为空";
  	}
  	else{
  	    	$mgr = new MicroAccountMgr($da,$this->get("we_data_access_im"),$currUser,$this->get("logger"),$this->container);
  	    	$mgr->agree_inviteatten($microAccount,$roster,$invite);
  	}
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;      	
  }  
  //管理员审核接口
  public function manageragreeinviteAction()
  {
    $request = $this->getRequest();  	
  	$da = $this->get("we_data_access");
  	$currUser = $this->get('security.context')->getToken()->getUser();
  	$re = array("returncode" => ReturnCode::$SUCCESS);  	
  	$microAccount=$request->get("microaccount");
  	$roster=$request->get("inviteaccount");
  	$invite=$request->get("invite");
  	if(empty($microAccount)){
  	    	$re["returncode"] = ReturnCode::$SYSERROR;
  	    	$re["msg"] = "公众号不能为空";
  	}
  	else if(empty($roster)){
  	    	$re["returncode"] = ReturnCode::$SYSERROR;
  	    	$re["msg"] = "关注人不能为空";
  	}
  	else{
  	    	$mgr = new MicroAccountMgr($da,$this->get("we_data_access_im"),$currUser,$this->get("logger"),$this->container);
  	    	$mgr->manager_agree_inviteatten($microAccount,$roster,$invite);
  	}
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;      	
  }
  
  public function rejectinviteAction()
  {
    $request = $this->getRequest();  	
  	$da = $this->get("we_data_access");
  	$currUser = $this->get('security.context')->getToken()->getUser();
  	$re = array("returncode" => ReturnCode::$SUCCESS);  	
  	$microAccount=$request->get("microaccount");
  	$roster=$request->get("inviteaccount");
  	$invite=$request->get("invite");
  	if(empty($microAccount)){
  	    	$re["returncode"] = ReturnCode::$SYSERROR;
  	    	$re["msg"] = "公众号不能为空";
  	}
  	else if(empty($roster)){
  	    	$re["returncode"] = ReturnCode::$SYSERROR;
  	    	$re["msg"] = "邀请人不能为空";
  	}  	
  	else{
  	    	$mgr = new MicroAccountMgr($da,$this->get("we_data_access_im"),$currUser,$this->get("logger"),$this->container);
  	    	$mgr->reject_inviteatten($microAccount,$roster,$invite);
  	}
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;      	
  }   
   
   //新增公众号接口
   public function Add_MicroAccountAction(){
     	$request = $this->getRequest();  	
    	$da = $this->get("we_data_access");
    	$da_im = $this->get("we_data_access_im");
    	$currUser = $this->get('security.context')->getToken()->getUser();
    	$logger=$this->get("logger");
    	$container=$this->container;
    	
    	$re["returncode"] = ReturnCode::$SUCCESS;
    	$re["msg"]="";
    	
    	$filename=$request->get("filename");//头像图片名称
    	$filedata=$request->get("filedata");//头像图片base64编码
    	$micro_name=$request->get("microname");//公众号名称
    	$micro_number=$request->get("micronumber");//公众号帐号
    	$micro_password=$request->get("micropassword");//公众号名称
    	$micro_type=$request->get("microtype");//0 内部公众号 1 外部公众号
    	$micro_use=$request->get("microuse");//0 推送信息 1 业务代理
    	$concern_approval=$request->get("microaudit");//0  表示私密(需要审核) 1  表示开放(不需要审核)
    	$introduction=$request->get("introduction");//简介
    	$salutatory=$request->get("salutatory");//欢迎词
    	$logo_path="";
    	$logo_path_big="";
    	$logo_path_small="";
    	$factory = $this->get('security.encoder_factory');
    	
    	if(empty($micro_password))$micro_password="123456";
    	if(empty($micro_type))$micro_type="0";
    	if(empty($micro_use))$micro_use="0";
    	if(empty($concern_approval))$concern_approval="0";
    	
    	//需要处理图片上传问题
    	if(!empty($filename)){
    		
    	}
  	 	if(!empty($micro_name)){
  	 	 if(!empty($micro_number)){
  		  	$mgr = new MicroAccountMgr($da,$da_im,$currUser,$logger,$container);
  		  	$re=$mgr->insertMicroAccount($micro_name,$micro_number,$micro_password,$micro_type,$micro_use,$concern_approval
  			  ,$introduction,$salutatory,$logo_path,$logo_path_big,$logo_path_small,$factory);
  		 }
  		 else{
  		 	$re["returncode"] = ReturnCode::$SYSERROR;
  			$re["msg"]="公众号帐号不能为空";
  		 }
  		}else{
  			$re["returncode"] = ReturnCode::$SYSERROR;
    		$re["msg"]="公众号名称不能为空";
  		}
    	$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
   }
   //添加公众号分组接口
   public function Add_MicroGroupAction(){
     	$request = $this->getRequest();  	
    	$da = $this->get("we_data_access");
    	$da_im = $this->get("we_data_access_im");
    	$currUser = $this->get('security.context')->getToken()->getUser();
    	$logger=$this->get("logger");
    	$container=$this->container;
    	
    	$re["returncode"] = ReturnCode::$SUCCESS;
    	$re["msg"]="";
    	
    	$micro_number=$request->get("micronumber");//公众号帐号
    	$micro_groupname=$request->get("microgroupname");//分组名称
    	
    	if(!empty($micro_number)){
    		if(!empty($micro_groupname)){
    			$re["returncode"] = ReturnCode::$SYSERROR;
    			$re["msg"]="分组名称不能为空";
    		}else{
  		  	$mgr = new MicroAccountMgr($da,$da_im,$currUser,$logger,$container);
  		  	$re=$mgr->insert_micro_group($micro_number,$micro_groupname);
  	  	}
    	}else{
    		$re["returncode"] = ReturnCode::$SYSERROR;
    		$re["msg"]="公众号帐号不能为空";
    	}
    	$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
   }
  //获取公众号历史消息
  public function getMicroMessageAction(){
      $request = $this->getRequest();   
      $conn = $this->get("we_data_access");
      $conn_im = $this->get("we_data_access_im");
      $logger=$this->get("logger");
      $container=$this->container;
      $microaccount=$request->get("microaccount");//微应用帐号
      $microgroupid=$request->get("microgroupid");//微应用分组ID
      $pageindex=$request->get("pageindex");//分页索引数
      $factory = $this->get('security.encoder_factory');

      if(empty($microaccount)) return $this->responseJson(array("returncode"=>ReturnCode::$SYSERROR,"msg"=>"微应用帐号不能为空."));
      if(empty($pageindex)) $pageindex=1;

      $currUser = $this->get('security.context')->getToken();
      
      if(!empty($currUser)) $currUser = $currUser->getUser(); 
      else{
          $baseinfoCtl = new \Justsy\BaseBundle\Management\Staff($da,null,$request->get("openid"),$this->get("logger"));        
          $currUser = $baseinfoCtl->getSessionUser();
      }
      if(empty($currUser)) return $this->responseJson(array("returncode"=>ReturnCode::$SYSERROR,"msg"=>"您还没有登录呢."));
      $sql_micro="select number,type from we_micro_account where number=?";
      $para_micro=array($microaccount);
      $data_micro=$conn->GetData("dt",$sql_micro,$para_micro);
      if($data_micro==null || count($data_micro["dt"]["rows"])==0 || empty($data_micro["dt"]["rows"][0]["number"])){
          return $this->responseJson(array("returncode"=>ReturnCode::$SYSERROR,"msg"=>"微应用帐号不存在."));        
      }
      $login_account=$currUser->getUserName();
      $micr_type=$data_micro["dt"]["rows"][0]["type"];
      
      //var_dump($login_account);
      $sql_atten="select count(1) as count from we_staff_atten where atten_type='01' and login_account=? and atten_id=?";
      $para_atten=array($login_account,$microaccount);
      $data_atten=$conn->GetData("dt",$sql_atten,$para_atten);
      $isatten=false;
      if($data_atten==null || count($data_atten["dt"]["rows"])==0 || empty($data_atten["dt"]["rows"][0]["count"])){
        $isatten=true; //没有被关注
      }
      //$microgroupid="";
      if($isatten){ //没有关注
        if($micr_type=="0"){//内部微应用帐号需要判断是否关注该公众号
          return $this->responseJson(array("returncode"=>ReturnCode::$SYSERROR,"msg"=>"未关注微应用帐号."));
        }
      }else{ //已经关注
        //$sql_micro_group="select GROUP_CONCAT(id) as id from we_micro_account_group where micro_account=? ORDER BY id";
        //$para_micro_group=array($microaccount);
        //$data_micro_group=$conn->GetData("dt",$sql_micro_group,$para_micro_group);
        //if($data_micro_group!=null && count($data_micro_group["dt"]["rows"])>0 && !empty($data_micro_group["dt"]["rows"][0]["id"])){
        //  $microgroupid=$data_micro_group["dt"]["rows"][0]["id"];
        //}
      }
      $sql_total="select count(1) as count from we_micro_send_message where send_account=? ";
      $para_total=array($microaccount);
      if(!empty($microgroupid)) {
        $sql_total="select count(1) as count from we_micro_send_message where send_account=? and send_groupid=? ";
        $para_total=array($microaccount,$microgroupid);
      }
      $data_total=$conn->GetData("dt",$sql_total,$para_total);
      $total=0;
      if($data_total!=null && count($data_total['dt']['rows'])>0) $total=$data_total['dt']['rows'][0]['count'];
      $totalpage=1;
      if ($total > 1) $totalpage = ceil($total  / 10  );
      $startrow=($pageindex-1)*10;
      $sql="select * from we_micro_send_message where send_account=? order by send_datetime desc LIMIT ".$startrow.",10";
      $para=array($microaccount);
      if(!empty($microgroupid)) {
        $sql="select * from we_micro_send_message where send_account=? and send_groupid=? order by send_datetime desc LIMIT ".$startrow.",10";
        $para=array($microaccount,$microgroupid);
      }
      $re=array('returncode'=>'9999',"msg"=>'消息获取失败');
      $data_row=$conn->GetData("dt",$sql,$para);
      //var_dump($sql);
      if($data_row!=null && count($data_row['dt']['rows'])>0){
        $objlist=array();
        for ($i=0; $i < count($data_row['dt']['rows']); $i++) { 
          $send_id=$data_row['dt']['rows'][$i]["id"];
          $send_type=$data_row['dt']['rows'][$i]["send_type"];
          $send_datetime=$data_row['dt']['rows'][$i]["send_datetime"];
          $sql="select * from we_micro_message where send_id=?";
          $para=array($send_id);
          $dataitem=$conn->GetData("dt",$sql,$para);
          if($dataitem!=null && count($dataitem['dt']['rows'])>0) {
            $list=array("type"=>$send_type,"date"=>$send_datetime);
            //var_dump($send_type);
            switch ($send_type) {
              case 'TEXT':
                $text_items=array();
                for ($l=0; $l < count($dataitem['dt']['rows']); $l++) { 
                  $item=array('title'=>$dataitem['dt']['rows'][$l]["msg_title"]
                            ,'content'=>$dataitem['dt']['rows'][$l]["msg_text"]);
                  array_push($text_items, $item);
                }
                $list['data']=array('item'=>$text_items);
                //var_dump($list);
                break;
              case 'PICTURE':
                for ($j=0; $j < count($dataitem['dt']['rows']); $j++) { 
                  $headitem=array("title"=>$dataitem['dt']['rows'][$j]["msg_title"]
                    ,'content'=>$dataitem['dt']['rows'][$j]["msg_summary"]
                    ,'image'=>array('type'=>$dataitem['dt']['rows'][$j]["msg_img_type"],'value'=>$dataitem['dt']['rows'][$j]["msg_img_url"])
                    ,'link'=>$dataitem['dt']['rows'][$j]["msg_web_url"]);
                  $list['data']=array("headitem"=>$headitem);
                }
                break;
              case 'TEXTPICTURE':
                $items=array();
                for ($k=0; $k < count($dataitem['dt']['rows']); $k++) { 
                  $ishead=$dataitem['dt']['rows'][$k]["ishead"];
                  //var_dump($ishead);
                  if($ishead=="1"){
                    $headitem=array("title"=>$dataitem['dt']['rows'][$k]["msg_title"]
                    ,'content'=>$dataitem['dt']['rows'][$k]["msg_text"]
                    ,'image'=>array('type'=>$dataitem['dt']['rows'][$k]["msg_img_type"],'value'=>$dataitem['dt']['rows'][$k]["msg_img_url"])
                    ,'link'=>$dataitem['dt']['rows'][$k]["msg_web_url"]);
                    $data['headitem']=$headitem;
                  }else{
                    $item=array("title"=>$dataitem['dt']['rows'][$k]["msg_title"]
                    ,'content'=>$dataitem['dt']['rows'][$k]["msg_text"]
                    ,'image'=>array('type'=>$dataitem['dt']['rows'][$k]["msg_img_type"],'value'=>$dataitem['dt']['rows'][$k]["msg_img_url"])
                    ,'link'=>$dataitem['dt']['rows'][$k]["msg_web_url"]);
                    array_push($items, $item);
                  }
                }
                if(!empty($items)) $data['item']=$items;
                $list['data']=$data;
                break;
            }
            array_push($objlist, $list);
          }
        }
        if(!empty($objlist)) $re=array('returncode'=>'0000',"total"=>$total,'totalpage'=>$totalpage,'list'=>$objlist);
      }else{
        $re=array('returncode'=>'0000',"total"=>0,'totalpage'=>1,'list'=>array());
      }
      //$data=array('microaccount'=>$microaccount,'microgroupid'=>$microgroupid,'pageindex'=>$pageindex);
      //$data='microaccount='.$microaccount.'&microgroupid='.$microgroupid.'&pageindex='.$pageindex;
      //var_dump($data);
      //$re=$this->do_post_request('http://mp.wefafa.com/interface/getmessagelist',$data);

      return $this->responseJson($re);
  }

  private function responseJson($re,$isjson=true){
    if($isjson){
      $response = new Response(json_encode($re));
      $response->headers->set('Content-Type', 'text/html');
      return $response;
    }else{
      $response = new Response($re);
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }
  }
  private function do_post_request($url, $data, $optional_headers = null)
  {
    $params = array('http' => array(
                'method' => 'POST',
                'content' => $data
              ));
    if ($optional_headers !== null) {
      $params['http']['header'] = $optional_headers;
    }
    $ctx = stream_context_create($params);
    $fp = @fopen($url, 'r', false, $ctx);
    if (!$fp) {
      throw new \Exception("Problem with $url, $php_errormsg");
    }
    $response = @stream_get_contents($fp);
    if ($response === false) {
      throw new \Exception("Problem reading data from $url, $php_errormsg");
    }
    return $response;
  }    
  //请求URL地址
  private function http_request( 
      $url,                      /* Target IP/Hostname */ 
      $postdata = array(),       /* HTTP POST Data ie. array('var1' => 'val1', 'var2' => 'val2') */ 
      $verb = 'POST',             /* HTTP Request Method (GET and POST supported) */    
      $timeout = 1000,           /* Socket timeout in milliseconds */ 
      $req_hdr = false,          /* Include HTTP request headers */ 
      $res_hdr = false           /* Include HTTP response headers */ 
      ) { 
      $url = parse_url($url);
      //var_dump($url);
      $port = $url['port']==null?(($url['scheme']=='https')?443:80):$url['port'];
      if(!$url) return "couldn't parse url";
      $ip = $url['host'];
      $uri = $url['path'];
      $ret = ''; 
      $verb = strtoupper($verb); 
      $postdata_str = ''; 
      foreach ($postdata as $k => $v){
          $postdata_str .= urlencode($k) .'='. urlencode($v) .'&'; 
      }
      $crlf = "\r\n"; 
      $req = $verb .' '. $uri . $getdata_str .' HTTP/1.1' . $crlf; 
      $req .= 'Host: '. $ip . $crlf; 
      $req .= 'User-Agent: Mozilla/5.0 Firefox/3.6.12' . $crlf; 
      $req .= 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8' . $crlf; 
      $req .= 'Accept-Language: en-us,en;q=0.5' . $crlf; 
      $req .= 'Accept-Encoding: deflate' . $crlf; 
      $req .= 'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7' . $crlf; 
      if ($verb == 'POST' && !empty($postdata_str)) { 
        //$postdata_str = strend($postdata_str,"&");
        //var_dump($postdata_str);
        $postdata_str = substr($postdata_str, 0, -1); 
        $req .= 'Content-Type: application/x-www-form-urlencoded' . $crlf; 
        $req .= 'Content-Length: '. strlen($postdata_str) . $crlf . $crlf; 
        $req .= $postdata_str; 
      } else {
        $req .= $crlf; 
      }  
      if ($req_hdr) $ret .= $req; 
      if (($fp = @fsockopen($ip, $port, $errno, $errstr)) == false) return "Error $errno: $errstr\n"; 
      stream_set_timeout($fp, 0, $timeout * 1000); 
      fputs($fp, $req); 
      while ($line = fgets($fp)){
        //var_dump($line);
        $ret .= $line;
      }
      fclose($fp); 
      if (!$res_hdr){
          $ret = substr($ret, strpos($ret, "\r\n\r\n") +4); 
      }
      return $ret; 
  } 
}
