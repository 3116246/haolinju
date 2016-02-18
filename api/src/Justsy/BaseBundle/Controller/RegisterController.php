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

use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Login\UserProvider;
use Justsy\BaseBundle\Management\Identify;


class RegisterController extends Controller
{
	  //用于客户端取IMSERVER
	  public function getIMServerAction()
	  {
	    $re = "";	    
	    $we_sys_param = $this->container->get('we_sys_param'); 
	       
      $imserver = $we_sys_param->GetSysParam("imserver");
      if (empty($imserver)) $imserver = "localhost:5222";
      $re .= "imserver=".$imserver;
      $re .= "\n";
      
      $url = $this->container->getParameter('FILE_WEBSERVER_URL');
	  	$url = str_replace("/getfile/","",$url)."/api/http/version/check";  
      $re .= "imupdateserver=".$url;
	    return new Response($re);
	  }
	  //用于客户端根据邮箱获取对应的jid帐号
	  public function getJidAction($email)
	  {	  	
	     	$mail = $email;
	     	if(strLen($mail)==0)
	     	{
	     	    return new Response("");	
	     	}
	     	else{
            $DataAccess = $this->container->get('we_data_access');
            $dataset = $DataAccess->GetData("jid","select fafa_jid from we_staff where login_account=?",array((String)$mail));
            $rows =  $dataset["jid"]["rows"]; 
            if(count($rows)==0)
               return new Response("");
            else
               return new Response($rows[0]["fafa_jid"]);
	     	}
	  }
	  //用于客户端根据手机获取对应的jid帐号
	  public function getJidMobileAction($mobile)
	  {
	     	if(strLen($mobile)==0)
	     	{
	     	    return new Response("");	
	     	}
	     	else{
            $DataAccess = $this->container->get('we_data_access');
            $dataset = $DataAccess->GetData("jid","select fafa_jid from we_staff where mobile_bind=?",array((String)$mobile));
            $rows =  $dataset["jid"]["rows"]; 
            if(count($rows)==0)
               return new Response("");
            else
               return new Response($rows[0]["fafa_jid"]);
	     	}	  	 
	  }

	  //用于客户端根据Jid获取对应的手机号
	  public function getMobileAction($jid)
	  {
	     	if(strLen($jid)==0)
	     	{
	     	    return new Response("");	
	     	}
	     	else{
            $DataAccess = $this->container->get('we_data_access');
            $dataset = $DataAccess->GetData("mobile","select mobile_bind mobile from we_staff where fafa_jid=?",array((String)$jid));
            $rows =  $dataset["mobile"]["rows"]; 
            if(count($rows)==0)
               return new Response("");
            else
               return new Response($rows[0]["mobile"]);
	     	}	  	
	  }
	  //用于客户端根据Jid获取对应的邮箱
	  public function getEmailAction($jid)
	  {
	     	$mail = $jid;
	     	if(strLen($mail)==0)
	     	{
	     	    return new Response("");	
	     	}
	     	else{
            $DataAccess = $this->container->get('we_data_access');
            $dataset = $DataAccess->GetData("jid","select login_account from we_staff where fafa_jid=?",array((String)$mail));
            $rows =  $dataset["jid"]["rows"]; 
            if(count($rows)==0)
               return new Response("");
            else
               return new Response($rows[0]["login_account"]);
	     	}
	  }	  
	  	
	  public function indexAction(Request $request)
    { 
      $mail = $request->get('mail');
      $DataAccess = $this->container->get('we_data_access');
      $dataset = $DataAccess->GetData("domain","select domain_name from we_public_domain order by order_num limit 0,10");
      $emials=($dataset["domain"]["rows"]);
      $huowa = array();
      for($i=0;$i<count($emials);$i++)
      {
      	$huowa[]=$emials[$i]["domain_name"];
      }
      return $this->render('JustsyBaseBundle:Register:register.html.twig', array('mail' => $mail,'domain'=> json_encode($huowa)));
    }
    
    public function batchindexAction(){
    	return $this->render('JustsyBaseBundle:Register:batchregister.html.twig', array());
    }
    
   	//邮箱注册
   	public function applyAction($account)
   	{
   		$im_sender = $this->container->getParameter('im_sender');
      $logger = $this->get('logger');
      $request = $this->get('request');
      $ename = $request->get('txtename');
      $fileid = null;
      $type = null;
      $da = $this->get("we_data_access");
      try
      {
      	//邮箱验证
      	if(!Utils::validateEmail($account)){
      		//向用户提示邮箱格式错误
      		//return $this->render('JustsyBaseBundle:Error:index.html.twig',array('error'=>'注册失败！'));
      	}
        //判断账号是否已激活 state_id 0－已注册、1－已驳回、2－已审核、3－已激活
        $sql = 'select state_id,submit_num from we_register where login_account=?';
        $ds = $da->GetData('we_register',$sql,array((string)$account));
        $para = array();
        if ($ds && $ds['we_register']['recordcount'] > 0)
        {
          if ($ds['we_register']['rows'][0]['state_id'] < 3 && $ds['we_register']['rows'][0]['submit_num'] < 10)
          {
            //如果未激活可注册10次
            $sql = 'update we_register set ename=?,credential_path=?,active_code=?,ip=?,email_type=null,'
              .'last_reg_date=now(),submit_num=ifnull(submit_num,0)+1,state_id="0" where login_account=?';
            $para = array($ename,$fileid,strtoupper(substr(uniqid(),3,10)),$_SERVER['REMOTE_ADDR'],$account);
          }
          else
          {
            return $this->render('JustsyBaseBundle:Register:ApplyError.html.twig', array('email'=>$account));
          }
        }
        else
        {
          $sql = "insert into we_register (login_account,ename,credential_path,active_code,ip,email_type,first_reg_date,last_reg_date,register_date,state_id)"
            ." values (?,?,?,?,?,null,now(),now(),now(),'0')";
          $para = array($account,$ename,$fileid,strtoupper(substr(uniqid(),3,10)),$_SERVER['REMOTE_ADDR']);
        }
        if (count($para) > 0)
        {
          $da->ExecSQL($sql,$para);
        }
        //发送邮件 展现页面
        //企业邮箱注册直接发送激活邮件
        $send = $this->container->getParameter('mailer_user');        
        //发送邮件
        $activeurl = $this->generateUrl("JustsyBaseBundle_active_reg_s1",array('account'=>DES::encrypt($account)),true);
        $txt = $this->renderView('JustsyBaseBundle:Register:mail.html.twig',array(
          'account' => $account,
          'activeurl' => $activeurl
        ));
        Utils::saveMail($da,$send,$account,"欢迎加入Wefafa企业协作网络",$txt);
        $tmp = explode("@",$account);      
        if (count($tmp) > 1){
        	 $tmp = $tmp[1];
           $mailaddr = "http://mail.".$tmp;
        }
        else
           $mailaddr = "http://mail.".$tmp[0];
        return $this->render('JustsyBaseBundle:Register:Apply.html.twig', array('email'=>$account,'type'=>$type,'mailaddr'=>$mailaddr));
      }
      catch(\Exception $e)
      {
        $logger->err($e);
        //var_dump($e->getmessage());
        return $this->render('JustsyBaseBundle:Error:index.html.twig',array('error'=>'注册失败！'));
      }
   	}
    //邮箱注册
//    public function applyAction($type, $account)
//    {
//      $im_sender = $this->container->getParameter('im_sender');
//      $logger = $this->get('logger');
//      $request = $this->get('request');
//      $ename = $request->get('txtename');
//      $da = $this->get("we_data_access");
//      $upfile = $request->files->get('txtfile');
//      $fileid = "";
//      try
//      {
//        //将文件保存到文件服务器
//        if ($upfile != null)
//        {
//          $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
//          $doc->setName($upfile->getClientOriginalName());
//          $doc->setFile($upfile->getPathname());
//          $dm = $this->get('doctrine.odm.mongodb.document_manager');
//          $dm->persist($doc);
//          $dm->flush();
//          $fileid = $doc->getId();
//          unlink($upfile->getPathname());
//        }
//        //判断账号是否已激活 state_id 0－已注册、1－已驳回、2－已审核、3－已激活
//        $sql = 'select state_id,submit_num from we_register where login_account=?';
//        $ds = $da->GetData('we_register',$sql,array((string)$account));
//        $para = array();
//        if ($ds && $ds['we_register']['recordcount'] > 0)
//        {
//          if ($ds['we_register']['rows'][0]['state_id'] < 3 && $ds['we_register']['rows'][0]['submit_num'] < 10)
//          {
//            //如果未激活可注册10次
//            $sql = 'update we_register set ename=?,credential_path=?,active_code=?,ip=?,email_type=?,'
//              .'last_reg_date=now(),submit_num=ifnull(submit_num,0)+1,state_id="0" where login_account=?';
//            $para = array($ename,$fileid,strtoupper(substr(uniqid(),3,10)),$_SERVER['REMOTE_ADDR'],$type,$account);
//          }
//          else
//          {
//            return $this->render('JustsyBaseBundle:Register:ApplyError.html.twig', array('email'=>$account));
//          }
//        }
//        else
//        {
//          $sql = "insert into we_register (login_account,ename,credential_path,active_code,ip,email_type,first_reg_date,last_reg_date,register_date,state_id)"
//            ." values (?,?,?,?,?,?,now(),now(),now(),'0')";
//          $para = array($account,$ename,$fileid,strtoupper(substr(uniqid(),3,10)),$_SERVER['REMOTE_ADDR'],$type);
//        }
//        if (count($para) > 0)
//        {
//          $da->ExecSQL($sql,$para);
//        }
//        //发送邮件 展现页面
//        if ($type == 1)
//        {
//          //企业邮箱注册直接发送激活邮件
//          $send = $this->container->getParameter('mailer_user');        
//          //发送邮件
//          $activeurl = $this->generateUrl("JustsyBaseBundle_active_reg_s1",array('account'=>DES::encrypt($account)),true);
//          $txt = $this->renderView('JustsyBaseBundle:Register:mail.html.twig',array(
//            'account' => $account,
//            'activeurl' => $activeurl
//          ));
//          //$this->get('mailer')->send($mailtext);
//          Utils::saveMail($da,$send,$account,"欢迎加入Wefafa企业协作网络",$txt);
//          $tmp = explode("@",$account);      
//          if (count($tmp) > 1) $tmp = $tmp[1];
//          $mailaddr = "http://mail.$tmp";
//          return $this->render('JustsyBaseBundle:Register:Apply.html.twig', array('email'=>$account,'type'=>$type,'mailaddr'=>$mailaddr));
//        }
//        else
//        {
//        	//判断企业是否已存在
//        	$dataset = $da->GetData("enterprise","select eno from we_enterprise where ename=?",array((String)$ename));
//        	//企业已存在时：向管理人员发送请求加入邮件。同时向pc端、管理后台发送数据
//        	 if ($dataset && count($dataset["enterprise"]["rows"]) > 0)
//        	 {
//        	 	   $eno = $dataset["enterprise"]["rows"][0]["eno"];      	 	        	 	   
//        	     //写申请加入信息。管理后台应陏时查询到这些申请
//        	     $sql = "update we_register set eno = ? where login_account=? and state_id='0'";
//        	     $da->ExecSQL($sql,array((string)$eno,(string)$account));
//        	     //查询出管理员帐号及jid
//        	 	   $sql = "SELECT B.login_account,B.fafa_jid FROM we_enterprise A ,we_staff B where A.create_staff=B.login_account and A.eno=?";
//        	 	   $dataset = $da->GetData("mgr_emps",$sql,array((String)$eno)); 
//        	     $activeurl = $this->generateUrl("JustsyBaseBundle_active_agree",array('str'=>DES::encrypt($account.",".$eno)),true);
//        	     $rejecturl = $this->generateUrl("JustsyBaseBundle_active_reject",array('str'=>DES::encrypt($account.",".$eno)),true);   	     
//        	     foreach($dataset["mgr_emps"]["rows"] as $key=>$value)
//        	     {
//                  //向管理员发送邮件
//                  $txt = $this->renderView('JustsyBaseBundle:Register:confirmMail.html.twig',array(
//                    'account' => $account,
//                    'activeurl' => $activeurl,
//                    'rejecturl' => $rejecturl,
//                    'ename' => $ename));
//                  Utils::saveMail($da,$account,$value["login_account"],"申请加入Wefafa",$txt,$eno);
//                  //Utils::sendMail($this->get('mailer'),"请求加入微发发",$this->container->getParameter('mailer_user'),null,$value["login_account"],$txt);
//                  //发送即时消息
//                  $message = $account."申请加入您的企业【".$ename."】请登录您的注册邮箱进行确认";
//                  Utils::sendImMessage($im_sender,$value["fafa_jid"],"申请加入微发发",$message,$this->container,"","",false,Utils::$systemmessage_code);
//        	     }
//        	     return $this->render('JustsyBaseBundle:Register:ApplyNotEnterprise.html.twig',array('email'=>$account));
//        	 }
//        	 else
//        	 {
//              //发送即时消息
//              $im_receiver = $this->container->getParameter('im_receiver');
//              $recvs = explode(',',$im_receiver);
//              $message = $account."申请创建企业【".$ename."】请及时审批";
//              foreach($recvs as $key => $value)
//              {
//                Utils::sendImMessage($im_sender,$value,'申请创建企业',$message,$this->container,"","",true,Utils::$systemmessage_code);
//              }
//              
//        	    //企业不存在时：提示需要审核
//              return $this->render('JustsyBaseBundle:Register:ApplyNotEnterprise.html.twig',array('email'=>$account));
//           }
//        }
//      }
//      catch(\Exception $e)
//      {
//        $logger->err($e);
//        return $this->render('JustsyBaseBundle:Error:index.html.twig',array('error'=>'注册失败！'));
//      }
//    }
    
    public function caUrlAction($account)
    {
    	 $activeurl = $this->generateUrl("JustsyBaseBundle_active_reg_s1",array('account'=>DES::encrypt($account)),true);
       $response = new Response($activeurl);
       $response->headers->set('Content-Type', 'text/html');
       return $response;    	 
    }
    
    //输入邮箱地址时的处理
	  public function delete_checkAction(Request $request)
    {       
       $mail = $request->get("id");
    	 $domain = substr($mail,strpos($mail,'@')+1);
       $DataAccess = $this->container->get('we_data_access');
       $sql = "select case when state_id < 2 then '系统禁止频繁注册。' when state_id=2 then '该邮箱已通过审核，请勿再次注册！'  when 3 then '该邮箱已被激活使用，请勿再次注册！' end 'msg' from we_register where submit_num>=10 and login_account=?";
       $dataset = $DataAccess->GetData("reg_state",$sql,array((String)$mail));
       if ($dataset && $dataset["reg_state"]["recordcount"] > 0 )  //注册超过10次
       {
          $array["msg"]= $dataset["reg_state"]["rows"][0]["msg"];
       }
       else
       {
         $array["msg"]="";
         $dataset = $DataAccess->GetData("domain","select domain_name from we_public_domain where domain_name=?",array((String)$domain));
         if ($dataset && $dataset["domain"]["recordcount"] > 0 )  //公共邮箱
         {
            $array['types'] = "0";
         }
         else  //企业邮箱
         {
           $array['types'] = "1";
           $dataset = $DataAccess->GetData("main","select eno from we_enterprise where edomain=?",array((String)$domain));
           if ($dataset && $dataset["main"]["recordcount"] > 0) //已注册企业邮箱
           {
             $dataset = $DataAccess->GetData("einfo","select a.eno eno,ename,group_concat(ifnull(login_account,'')) account,group_concat(ifnull(nick_name,'')) nick,group_concat(ifnull(photo_path,'')) path from we_enterprise a inner join we_staff using(eno) where edomain=?",array((String)$domain));
             $account = explode(",",$dataset["einfo"]["rows"][0]["account"]);
             $nick = explode(",",$dataset["einfo"]["rows"][0]["nick"]);
             $path = explode(",",$dataset["einfo"]["rows"][0]["path"]);
             $html_image="<div style='align:center;'>";
             $html_nick= "<div style='align:center;'>";
             $html = "<div><strong>他们都在等你</strong></div>";
             $image ="";
             for($i=0;$i<count($account);$i++)
             {
               if( $path[$i]=="" )
                 $image="/bundles/fafatimewebase/images/tx.jpg";
               else
                 $image=$path[$i];
               if (($i + 1) % 3 == 0)
               {
                 $html_image = $html_image."<image style='width:60px;height:60px;' src=\"".$image."\"></image></div>";
                 $html_nick  = $html_nick."<span style='width:65px;height:18px;align:center;' >".$nick[$i]."</span></div>";
                 $html = $html.$html_image.$html_nick;
                 $html_image="<div style='align:center;'>";
                 $html_nick ="<div style='align:center;'>";
               }
               else
               {                
                 $html_image = $html_image."<image style='width:60px;height:60px;' src=\"".$image."\"></image>&nbsp;&nbsp;";
                 $html_nick  = $html_nick."<span style='width:65px;height:18px;align:center;'>".$nick[$i]."</span>&nbsp;&nbsp;";
               }  
             }
             if ( $html_image!="<div style='align:center;'>")
             $html = $html.$html_image.$html_nick;             
             $array['state'] = "1";
             $array['eno'] = $dataset["einfo"]["rows"][0]["eno"];
             $array['ename'] = $dataset["einfo"]["rows"][0]["ename"];
             $array['html'] = $html;
             $array['s']=$path[0];
           }
         }
       }
      $response = new Response(json_encode($array));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }
        
    //企业名称处理
    public function enameAction(Request $request)
    {
         $ename = $request->get('ename');
         $DataAccess = $this->container->get('we_data_access');
         $dataset = $DataAccess->GetData("enterprise","select ename from we_enterprise where ename=?",array((String)$ename));
         if ($dataset && count($dataset["enterprise"]["rows"]) > 0)
         {
           $response = new Response(json_encode(array("exist"=>1)));
         }
         else
         {
           $array = array("exist" => "0");
           $response = new Response(json_encode($array));
         }
         $response->headers->set('Content-Type', 'text/json');
         return $response;         
    }
    
  //搜索企业名称
  public function searchAction(Request $request)
  {
    $ename = $request->get('ename');
    $DataAccess = $this->container->get('we_data_access');
    $sql = "select eno,ename from we_enterprise where ename like concat('%',?,'%') limit 0,10";
    $dataset = $DataAccess->GetData("en",$sql,array((String)$ename));
    if ($dataset && $dataset["en"]["recordcount"] > 0)
    {                        
      $response = new Response(json_encode($dataset["en"]["rows"]));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }
    else
    {
      $array["html"] = "";
      $response = new Response(json_encode($array));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }
  }
  
    //获取指定企业已注册成功的前12名人员
    public function getRegPerAction()
    {
    	  $dataaccess = $this->get('we_dataaccess');
        $dataset = $dataaccess->GetData('we_staff', 'select nick_name,photo_path from we_staff order by staff_id desc limit 0,12');
	      $satts=($dataset["domain"]["rows"]);
        $response = new Response(json_encode($satts));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }
         
    //上传执照
    private function uploadPermit()
    {
       $result = true;
       //上传文件
       if($_FILES["txtfile"]["name"]!="")
       {
         $tmp_name = $_FILES['txtfile']['name'];
         $thispath = $_SERVER['DOCUMENT_ROOT']."/Enterprise";                  
         if (!file_exists($thispath))
             mkdir($thispath,0777);         
         $fix = pathinfo($tmp_name , PATHINFO_EXTENSION);
         $thispath = $thispath."/".$tmp_name;
         if(!move_uploaded_file($_FILES['txtfile']['tmp_name'] ,$thispath))
         {
           if (!copy($_FILES['txtfile']['tmp_name'] ,$thispath))
           {
              $result = false;
           }
         }
       }
       return $result;
    }

    //输入邮箱地址时的处理
	  public function checkAction(Request $request)
    {       
      $mail = $request->get("id"); 

      //检查地址是否合法
      if(!Utils::validateEmail($mail))
      {
	      $response = new Response(json_encode(array("succeed"=>false,"msg"=>"error01")));
	      $response->headers->set('Content-Type', 'text/json');
	      return $response;      	
      }
      $DataAccess = $this->container->get('we_data_access');
      //检查该邮箱是否已经使用
      $sql = "SELECT count(0) cnt FROM we_staff where login_account=? and state_id!='3'";
      $result = $DataAccess->GetData("checkresult",$sql,array((string)$mail));
      if($result["checkresult"]["rows"][0]["cnt"]>0)
      {
	       $response = new Response(json_encode(array("succeed"=>false,"msg"=>"error03")));
	       $response->headers->set('Content-Type', 'text/json');
	       return $response;
      }
      //检查是否被列为黑名单
      $sql = "select count(0) cnt from we_blacklist where blacklist_type ='02' and blacklist_value =?";
      $result = $DataAccess->GetData("checkresult",$sql,array((string)$mail));
      if($result["checkresult"]["rows"][0]["cnt"]>0)
      {
	       $response = new Response(json_encode(array("succeed"=>false,"msg"=>"error05")));
	       $response->headers->set('Content-Type', 'text/json');
	       return $response;
      }
      $mailDomain = explode("@",$mail);
      $sql = "select count(0) cnt from we_blacklist where blacklist_type ='01' and blacklist_value =?";
      $result = $DataAccess->GetData("checkresult",$sql,array((string)$mailDomain[1]));
      if($result["checkresult"]["rows"][0]["cnt"]>0)
      {
	       $response = new Response(json_encode(array("succeed"=>false,"msg"=>"error05")));
	       $response->headers->set('Content-Type', 'text/json');
	       return $response;
      }      

		      //未激活注册，检查是否已提交及审核情况
		      $sql = "select * from we_register where login_account=?";
		      $result = $DataAccess->GetData("checkresult",$sql,array((string)$mail));
		      if(count($result["checkresult"]["rows"])==0)//邮箱可用
		      {
		      	  //检查是否公共邮箱
				      $tmp = explode("@",$mail);
				      $sql = "select count(0) cnt from we_public_domain where domain_name=?";
				      $result = $DataAccess->GetData("checkresult",$sql,array((string)$tmp[1]));
				      if($result["checkresult"]["rows"][0]["cnt"]==0)
				      {
					       $response = new Response(json_encode(array("succeed"=>false,"msg"=>"error02")));
					       $response->headers->set('Content-Type', 'text/json');
					       return $response;
				      }  
			       	$response = new Response(json_encode(array("succeed"=>true)));
			       	$response->headers->set('Content-Type', 'text/json');
			       	return $response;
		      }      
		      $Rec = $result["checkresult"]["rows"][0];
		      if($Rec["state_id"]=="0")
		      {
		      	//审核中
		      	$response = new Response(json_encode(array("succeed"=>false,"msg"=>"error0401")));
		      }
		      else if($Rec["state_id"]=="2")
		      {
		      	 //已审核通过
		      	 $response = new Response(json_encode(array("succeed"=>false,"msg"=>"error0403")));
		      }
		      else if ($Rec["submit_num"]>9)
		      {
		      	 //判断提交次数
		      	 $response = new Response(json_encode(array("succeed"=>false,"msg"=>"error0402")));		      	 
		      }
		      else 
		      {
              //检查是否公共邮箱
				      $tmp = explode("@",$mail);
				      $sql = "select count(0) cnt from we_public_domain where domain_name=?";
				      $result = $DataAccess->GetData("checkresult",$sql,array((string)$tmp[1]));
				      if($result["checkresult"]["rows"][0]["cnt"]==0)
				      {
					       $response = new Response(json_encode(array("succeed"=>false,"msg"=>"error02")));
					       $response->headers->set('Content-Type', 'text/json');
					       return $response;
				      }
		          $response = new Response(json_encode(array("succeed"=>true)));//邮件只是人员注册，非企业注册
		      }
		      $response->headers->set('Content-Type', 'text/json');      
		      return $response;   
    }
  //找回密码
  public function retrievePwdAction(Request $request)
  {
  	//输出调用
     $checkcode = $this->make_rand(4);
     $login_account = $request->get("login_account"); 
     return $this->render('JustsyBaseBundle:Register:retrieve_pwd_index.html.twig', array('login_account'=>$login_account));
  }
  
  public function retrievePwd_3GAction()
  {
    return $this->render('JustsyBaseBundle:Register:retrieve_pwd_index_3g.html.twig', array(
      'err' => '',
      'login_account' => ''
    ));
  }
  
  /*
  //保存找回密码验证信息
  public function saveRetrieveReqAction(Request $request)
  {  	
    $da = $this->get('we_data_access');
    $login_account = $this->get('request')->request->get('login_account'); 
    if($login_account==null)
    {
    	 return $this->render('JustsyBaseBundle:Register:retrieve_pwd_index.html.twig', array(
        'err' => '请输入账号邮件地址！',
        'login_account' => $login_account
      ));    	
    }
    $sql = "select nick_name from we_staff where login_account=?";
    $ds = $da->GetData('we_staff',$sql,array((string)$login_account));
    if (!$ds || $ds['we_staff']['recordcount']==0)
    {
      return $this->render('JustsyBaseBundle:Register:retrieve_pwd_index.html.twig', array(
        'err' => '您输入的帐号不存在！',
        'login_account' => $login_account
      ));
    }
    $nick_name = $ds['we_staff']['rows'][0]['nick_name'];
    $id = SysSeq::GetSeqNextValue($da,"we_retrieve_password","id");
    $sql = "insert into we_retrieve_password (id,login_account,req_date,valid_date,valid) values 
      (?,?,now(),adddate(now(),1),'1')";
    $da->ExecSQL($sql,array($id,$login_account));
    $req_date = getdate();
    //帐号,申请id
    $repwd_url = $this->generateUrl("JustsyBaseBundle_reg_resetpwd",
      array('para' => DES::encrypt("$login_account,$id")),true);
    $txt = $this->renderView("JustsyBaseBundle:Register:retrieve_pwd_mail.html.twig",array(
      "nick_name" => $nick_name,
      "req_date" => $req_date['year'].'年'.$req_date['mon'].'月'.$req_date['mday'].'日'.$req_date['hours'].'时'.$req_date['minutes'].'分',
      "repwd_url" => $repwd_url
    ));
    $title = "Wefafa密码找回邮件";
    Utils::saveMail($da,$this->container->getParameter('mailer_user'),$login_account,$title,$txt);
    $tmp = explode("@",$login_account);      
    if (count($tmp) > 1) $tmp = $tmp[1];
    $mailaddr = "http://mail.$tmp";
    return $this->render('JustsyBaseBundle:Register:retrieve_pwd_req_end.html.twig',array(
      'login_account' => $login_account,
      'mailaddr' => $mailaddr
    ));
  }
  */
  
  //保存找回密码验证信息
  public function saveRetrieveReqAction(Request $request)
  {  	
    $da = $this->get('we_data_access');
    $login_account = $request->get("login_account");
    $result = array();
     if($login_account==null)
    {
    	   $result = array("succeed" => false);
    }
    $sql = "select nick_name from we_staff where login_account=?";
    $ds = $da->GetData('we_staff',$sql,array((string)$login_account));
    if (!$ds || $ds['we_staff']['recordcount']==0)
    {
      return $this->render('JustsyBaseBundle:Register:retrieve_pwd_index.html.twig', array(
        'err' => '您输入的帐号不存在！',
        'login_account' => $login_account
      ));
    }
    
    $nick_name = $ds['we_staff']['rows'][0]['nick_name'];
    $id = SysSeq::GetSeqNextValue($da,"we_retrieve_password","id");
    $sql = "insert into we_retrieve_password (id,login_account,req_date,valid_date,valid) values 
      (?,?,now(),adddate(now(),1),'1')";
    $da->ExecSQL($sql,array($id,$login_account));
    $req_date = getdate();
    //帐号,申请id
    $repwd_url = $this->generateUrl("JustsyBaseBundle_reg_resetpwd",
      array('para' => DES::encrypt("$login_account,$id")),true);
    $txt = $this->renderView("JustsyBaseBundle:Register:retrieve_pwd_mail.html.twig",array(
      "nick_name" => $nick_name,
      "req_date" => $req_date['year'].'年'.$req_date['mon'].'月'.$req_date['mday'].'日'.$req_date['hours'].'时'.$req_date['minutes'].'分',
      "repwd_url" => $repwd_url
    ));
    $title = "Wefafa密码找回邮件";
    Utils::saveMail($da,$this->container->getParameter('mailer_user'),$login_account,$title,$txt);
    $tmp = explode("@",$login_account);      
    if (count($tmp) > 1) $tmp = $tmp[1];
    $mailaddr = "http://mail.$tmp";
//    return $this->render('JustsyBaseBundle:Register:retrieve_pwd_req_end.html.twig',array(
//      'login_account' => $login_account,
//      'mailaddr' => $mailaddr
//    ));
    $result = array("succeed" => true,"mailaddr" => $mailaddr);
    $response = new Response(json_encode($result));
		$response->headers->set('Content-Type', 'text/json');
		return $response;    
  }
  
  
  
  public function saveRetrieveReq_3GAction()
  {
    $da = $this->get('we_data_access');
    $login_account = $this->get('request')->request->get('login_account');
    
    $sql = "select nick_name from we_staff where login_account=?";
    $ds = $da->GetData('we_staff',$sql,array((string)$login_account));
    if (!$ds || $ds['we_staff']['recordcount']==0)
    {
      return $this->render('JustsyBaseBundle:Register:retrieve_pwd_index_3g.html.twig', array(
        'err' => '您输入的帐号不存在！',
        'login_account' => $login_account
      ));
    }
    
    $nick_name = $ds['we_staff']['rows'][0]['nick_name'];
    $id = SysSeq::GetSeqNextValue($da,"we_retrieve_password","id");
    $sql = "insert into we_retrieve_password (id,login_account,req_date,valid_date,valid) values (?,?,now(),adddate(now(),1),'1')";
    $da->ExecSQL($sql,array($id,$login_account));
    $req_date = getdate();
    
    //帐号,申请id
    $repwd_url = $this->generateUrl("JustsyBaseBundle_reg_resetpwd", array('para' => DES::encrypt("$login_account,$id")),true);
    $txt = $this->renderView("JustsyBaseBundle:Register:retrieve_pwd_mail.html.twig",array(
      "nick_name" => $nick_name,
      "req_date" => $req_date['year'].'年'.$req_date['mon'].'月'.$req_date['mday'].'日'.$req_date['hours'].'时'.$req_date['minutes'].'分',
      "repwd_url" => $repwd_url
    ));
    $title = "Wefafa密码找回邮件";
    Utils::saveMail($da,$this->container->getParameter('mailer_user'),$login_account,$title,$txt);
    
    $tmp = explode("@",$login_account);      
    if (count($tmp) > 1) $tmp = $tmp[1];
    $mailaddr = "http://mail.$tmp";
    
    return $this->render('JustsyBaseBundle:Register:retrieve_pwd_req_end_3g.html.twig',array(
      'login_account' => $login_account,
      'mailaddr' => $mailaddr
    ));
  }
  //重置密码
  public function resetPwdAction($para)
  {
    $da = $this->get('we_data_access');
    $state = 1;
    try
    {
    	$arr = explode(",",trim(DES::decrypt($para)));
	    $sql = "select count(1) as cnt from we_retrieve_password where id=? and login_account=? 
	      and now()<valid_date and valid='1'";
	    $ds = $da->GetData('we_retrieve_password',$sql,array(
	      (string)$arr[1],
	      (string)$arr[0]
	    ));    
	     
	    if (!$ds || $ds['we_retrieve_password']['rows'][0]['cnt']==0)
	      $state = 0;
	    return $this->render('JustsyBaseBundle:Register:retrieve_pwd_modify.html.twig',array(
      'login_account' => $arr[0],
      'id' => $arr[1],
      'state' => $state
      ));	      
    }
    catch(\Exception $e) 
    {
	    return $this->render('JustsyBaseBundle:Register:retrieve_pwd_modify.html.twig',array(
      'login_account' => '',
      'id' => '',
      'state' => 0
      ));	 
    }    

  }
  /*
  //重置密码保存
  public function resetPwdSaveAction()
  {
    $da = $this->get('we_data_access');
    $login_account = $this->get('request')->request->get('login_account');
    $pwd = $this->get('request')->request->get('pwd');
    $id = $this->get('request')->request->get('id');
    $t_code = DES::encrypt($pwd);
    $user = new UserSession($login_account, $pwd, $login_account, array("ROLE_USER"));
    $factory = $this->get("security.encoder_factory");
    $encoder = $factory->getEncoder($user);
    $pwd = $encoder->encodePassword($pwd,$user->getSalt());
    $sql = "update we_staff set password=?,t_code=? where login_account=?";
    $da->ExecSQL($sql,array(
      (string)$pwd,
      (string)$t_code,
      (string)$login_account
    ));
    $sql = "update we_retrieve_password set valid='0' where id=? and login_account=?";
    $da->ExecSQL($sql,array(
      (string)$id,
      (string)$login_account
    ));
    //更改im密码
    $da_im = $this->get('we_data_access_im');
    $pwd = $this->get('request')->request->get('pwd');
    $sql = "select fafa_jid from we_staff where login_account=?";
    $ds = $da->GetData('we_staff',$sql,array((string)$login_account));
    if ($ds && $ds['we_staff']['recordcount']>0)
    {
      $fafa_jid = $ds['we_staff']['rows'][0]['fafa_jid'];
      $sqls[] = "update im_employee set password=? where loginname=?";
      $sqls[] = "update users set password=? where username=?";
      $paras[] = array((string)$pwd,(string)$fafa_jid);
      $paras[] = array((string)$pwd,(string)$fafa_jid);
      $da_im->ExecSQLs($sqls, $paras);
    }
    $root_url = $this->generateUrl('root');
    return $this->render('JustsyBaseBundle:Register:retrieve_pwd_modify_end.html.twig',array(
      'root_url' => $root_url
    ));
  }
  */
  
  
  //重置密码保存
  public function resetPwdSaveAction(Request $request)
  {
    $da = $this->get('we_data_access');
    $login_account = $request->get('login_account');
    $pwd = $request->request->get('pwd');
    $id = $request->get('id');
    $t_code = DES::encrypt($pwd);
    $user = new UserSession($login_account, $pwd, $login_account, array("ROLE_USER"));
    $factory = $this->get("security.encoder_factory");
    $encoder = $factory->getEncoder($user);
    $pwd = $encoder->encodePassword($pwd,$user->getSalt());
    $sql = "update we_staff set password=?,t_code=? where login_account=?";
    $da->ExecSQL($sql,array(
      (string)$pwd,
      (string)$t_code,
      (string)$login_account
    ));
    $sql = "update we_retrieve_password set valid='0' where id=? and login_account=?";
    $da->ExecSQL($sql,array(
      (string)$id,
      (string)$login_account
    ));
    //更改im密码
    $da_im = $this->get('we_data_access_im');
    $pwd = $this->get('request')->request->get('pwd');
    $sql = "select fafa_jid from we_staff where login_account=?";
    $ds = $da->GetData('we_staff',$sql,array((string)$login_account));
    if ($ds && $ds['we_staff']['recordcount']>0)
    {
      $fafa_jid = $ds['we_staff']['rows'][0]['fafa_jid'];
      $sqls[] = "update im_employee set password=? where loginname=?";
      $sqls[] = "update users set password=? where username=?";
      $paras[] = array((string)$pwd,(string)$fafa_jid);
      $paras[] = array((string)$pwd,(string)$fafa_jid);
      $da_im->ExecSQLs($sqls, $paras);
    }
    $result = array("succeed" => true,"url" => $this->generateUrl('root'));
    $response = new Response(json_encode($result));
		$response->headers->set('Content-Type', 'text/json');
		return $response;	
  }

  
    //判断是否为企业邮箱
    public function mailtypeAction(Request $request)
    {
       $mail = $request->get("id");
      $vcode = strtolower($request->get("vcode")); 
      //var_dump( $vcode);
      $session = $this->get('session');
      //var_dump( $session->get("code"));
      if(!empty($vcode))
      {
          //检查验证码
          if($vcode!=$session->get("code"))
          {
          	$this->get("logger")->err("$vcode:".$vcode."========session code:".$session->get("code"));
            $array = array("succeed"=>false,"msg"=>"error11");
            $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($array).");" : json_encode($array));

		     $response->headers->set('Content-Type', 'text/json');
		     return $response; 
          }
      }       
	     //检查地址是否合法
	     if(!Utils::validateEmail($mail))
	     {
		    //$response = new Response(json_encode(array("succeed"=>false,"msg"=>"error01")));
            $array = array("succeed"=>false,"msg"=>"error01");
            $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($array).");" : json_encode($array));

		     $response->headers->set('Content-Type', 'text/json');
		     return $response;      	
	     }
	     $DataAccess = $this->container->get('we_data_access');
//    	 $domain = substr($mail,strpos($mail,'@')+1);
//       $dataset = $DataAccess->GetData("domain","select domain_name from we_public_domain where domain_name=?",array((String)$domain));
//       if ($dataset && $dataset["domain"]["recordcount"] > 0 )  //公共邮箱
//       	   $array['type'] = "0";
//       }
	     $array['type'] = "1";
         $array['succeed'] = true; //默认为正常
       //$response = new Response(json_encode($array));	        
	     //判断邮箱是否已经使用
	     $sql = "select count(0) cnt from we_staff where login_account=? ";
		 $result = $DataAccess->GetData("checkresult",$sql,array((string)$mail));
		   if($result["checkresult"]["rows"][0]["cnt"]>0)
		   {
                $array = array("succeed"=>false,"msg"=>"error03");
			    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($array).");" : json_encode($array));
             
			    $response->headers->set('Content-Type', 'text/json');
			    return $response;
		   }
		   //检查是否被列为黑名单
		   $sql = "select count(0) cnt from we_blacklist where blacklist_type ='02' and blacklist_value =?";
		   $result = $DataAccess->GetData("checkresult",$sql,array((string)$mail));
		   if($result["checkresult"]["rows"][0]["cnt"]>0)
		   {
			   $array = array("succeed"=>false,"msg"=>"error03");
                $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($array).");" : json_encode($array));
             $response->headers->set('Content-Type', 'text/json');
			   return $response;
		   }
		   $mailDomain = explode("@",$mail);
		   $sql = "select count(0) cnt from we_blacklist where blacklist_type ='01' and blacklist_value =?";
		   $result = $DataAccess->GetData("checkresult",$sql,array((string)$mailDomain[1]));
		   if($result["checkresult"]["rows"][0]["cnt"]>0)
		   {
                $array = array("succeed"=>false,"msg"=>"error05");
                $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($array).");" : json_encode($array));
             
			    $response->headers->set('Content-Type', 'text/json');
			    return $response;
		   }	      
		   //未激活注册，检查是否已提交及审核情况
		   $sql = "select * from we_register where login_account=?";
		   $result = $DataAccess->GetData("checkresult",$sql,array((string)$mail));
		   $Rec = ($result && $result['checkresult']['recordcount']>0) ? $result["checkresult"]["rows"][0] : 0;
		   
           if($Rec["state_id"]=="2")
		   {            
            $array = array("succeed"=>false,"msg"=>"error0403");
		     //已审核通过
		     //$response = new Response(json_encode());
		   }
		   else if ($Rec["submit_num"]>9)
		   {
		     //判断提交次数
             $array = array("succeed"=>false,"msg"=>"error0402");    //
		     //$response = new Response(json_encode(array("succeed"=>false,"msg"=>"error0402")));		      	 
		   }
           $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($array).");" : json_encode($array));
       $response->headers->set('Content-Type', 'text/json');     
       return $response;
    } 
  
  //设置验证码图片
  public function CodeAction()
  {
  	$im_x = 100;
		$im_y = 35;
		$im = imagecreatetruecolor($im_x,$im_y);
		$text_c = ImageColorAllocate($im, mt_rand(0,100),mt_rand(0,100),mt_rand(0,100));
		$tmpC0=mt_rand(100,255);
		$tmpC1=mt_rand(100,255);
		$tmpC2=mt_rand(100,255);
		$buttum_c = ImageColorAllocate($im,$tmpC0,$tmpC1,$tmpC2);
		imagefill($im, 16, 1, $buttum_c);
		$font = 't1.ttf';
		$text = $this->make_rand(4);
		$session = $this->get('session');
    $session->set("code",strtolower($text));  
    $this->get("logger")->err("========session code:".$session->get("code"));  
		$white = ImageColorAllocate ($im, 0, 0, 0);
		$printtext = "";
		for($index=0;$index<4;$index++)
		{
			 $printtext .= $text[$index]." ";
		}
		ImageString($im, 10, 10, 10, $printtext, $white);
		$distortion_im = imagecreatetruecolor ($im_x, $im_y);
		imagefill($distortion_im, 16, 13, $buttum_c);
		for ( $i=0; $i<$im_x; $i++) {
			for ( $j=0; $j<$im_y; $j++) {
				$rgb = imagecolorat($im, $i , $j);
				if( (int)($i+20+sin($j/$im_y*2*M_PI)*10) <= imagesx($distortion_im)&& (int)($i+20+sin($j/$im_y*2*M_PI)*10) >=0 ) {
					imagesetpixel ($distortion_im, (int)($i+10+sin($j/$im_y*2*M_PI-M_PI*0.1)*4) , $j , $rgb);
				}
			}
		}
		//加入干扰象素;
		$count = 150;//干扰像素的数量
		for($i=0; $i<$count; $i++){
			$randcolor = ImageColorallocate($distortion_im,mt_rand(0,255),mt_rand(0,255),mt_rand(0,255));
			imagesetpixel($distortion_im, mt_rand()%$im_x , mt_rand()%$im_y , $randcolor);
		}	
		$rand = mt_rand(5,35);
		$rand1 = mt_rand(15,25);
		$rand2 = mt_rand(5,10);
		for ($yy=$rand; $yy<=+$rand+2; $yy++){
			for ($px=-80;$px<=80;$px=$px+0.1)
			{
				$x=$px/$rand1;
				if ($x!=0)
					$y=sin($x);
				$py=$y*$rand2;	
				imagesetpixel($distortion_im, $px+50, $py+$yy, $text_c);
			}
		}	
		//设置文件头;
		Header("Content-type: image/png");		
		ImagePNG($distortion_im);
	  //销毁一图像,释放与image关联的内存;
		ImageDestroy($distortion_im);
		ImageDestroy($im);		
    $response = new Response(json_encode(""));
		$response->headers->set('Content-Type', 'text/json');
		return $response;
  }

  public function make_rand($length="32"){//验证码文字生成函数
	  $str="ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
	  $result="";
	  for($i=0;$i<$length;$i++){
		  $num[$i]=rand(0,35);
		  $result.=$str[$num[$i]];
	  }
	  return $result;
  } 
  
  //检查用户账号及验证码
  public function checkAccountAndCodeAction(Request $request)
  {
    $login_account = $request->get("login_account");
    $code =  strtolower($request->get("code"));
    $session = $this->get('session');
    $result = array();
    if ( $code!=$session->get("code"))
    {
    	 $result = array("succeed" => false);
    }
    else
    {
    	 $da = $this->get('we_data_access');
    	 $sql = "select login_account,case when mobile_bind is not null then CONCAT('*******',right(mobile_bind,4)) else '' end mobile
    	         from we_staff where login_account=? or mobile_bind=? or ldap_uid=?;";
    	 $para = array((string)$login_account,(string)$login_account,(string)$login_account);
		   $ds = $da->GetData('we_staff',$sql,$para);
		   if (!$ds || $ds['we_staff']['recordcount']==0)
		      $result = array("succeed" => true,"Exists"=> false);
		   else
		   {
		   	 $mobile = $ds["we_staff"]["rows"][0]["mobile"];
		   	 $login_account = $ds["we_staff"]["rows"][0]["login_account"];
		   	 $result = array("succeed" => true,"Exists"=> true,"mobile"=> $mobile,"email"=>$login_account );
		   }
    }   
    $response = new Response(json_encode($result));
		$response->headers->set('Content-Type', 'text/json');
		return $response;
  }
  
  //获得手机验证码
  public function getMobileCodeAction(Request $request)
  {
  	 //查询出用户的手机号码
  	  $login_account = $request->get("login_account");  	  
  	  $result = $this->getMobileCode($login_account);		   
		  $response = new Response(json_encode($result));
		  $response->headers->set('Content-Type', 'text/json');
		  return $response;		    	 
  }
  
  //获得手机短信验证码
  private function getMobileCode($login_account)
  {
  	  $da = $this->get('we_data_access');
    	$sql = "select login_account,mobile_bind from we_staff where login_account=? or mobile_bind=? or ldap_uid=?;";
    	$para = array((string)$login_account,(string)$login_account,(string)$login_account);
		  $ds = $da->GetData('we_staff',$sql,$para);
		  $result = array();
		  if (!$ds || $ds['we_staff']['recordcount']==0)
		    $result = array("succeed" => false,"content" => "未存在的Wefafa账号！");
		  else
		  {
		   	 $mobilenumber = $ds["we_staff"]["rows"][0]["mobile_bind"];
		   	 $login_account =$ds["we_staff"]["rows"][0]["login_account"];
		   	 //验证手机号是否合法
			   if (!Utils::validateMobile($mobilenumber))
			      $result = array("succeed" => false,"content" => "绑定的手机号不正确！");
			   else
			   {
			   	 try
			   	 {
			   	 	 $active_code = rand(100000, 999999);
			   	 	 //发送短信前选判断
			   	 	 $sql = "select submit_num,state_id,last_reg_date,timestampdiff(second,last_reg_date,now()) as dif"
             ." from we_register where login_account=?";
             $ds = $da->GetData("we_register", $sql, array($txtmobile));
             $issend = true;
			       if ($ds && $ds['we_register']['recordcount'] > 0)
			       {
				        if ($ds['we_register']['rows'][0]['dif'] <= 60)
				        {
				           $result = array("succeed" => false,"content" => "你获取验证码的次数太频繁！一分钟只能取一次！!");
				           $issend = false;
				        }
				        if ($ds['we_register']['rows'][0]['submit_num'] >= 3 && $ds['we_register']['rows'][0]['dif'] <= 60*60*24) //最多三次
				        {
				           $result = array("succeed" => false,"content" => "你获取验证码的次数太多！每天最多只能取三次！!");
				           $issend = false;
				        }
				        else if($ds['we_register']['rows'][0]['dif'] > 60*60*24)
				        {
				          //一天以后重置
				          $sql = "update we_register set submit_num=0 where login_account=?";
				          $da->ExecSQL($sql,array($login_account));
				        }
			       }
			       if ($issend)
			       {
			       	  $content = "您正在使用Wefafa手机密码找回功能，请您在收到本条短信后尽快进行密码修改。本次获得验证码：".$active_code."。【发发时代】";
			  		    $ec = new \Justsy\BaseBundle\Controller\SendSMSController();  		
			          $ec->setContainer($this->container);
			          $ret = $ec->sendSMSAction($mobilenumber,$content);
						    if(strpos($ret->getContent(),"<errorcode>0</errorcode>")===false)
						    {				         
						      $result = array("succeed" => false,"content" => "获取并发送短信验证码失败，请重试!");				       
						    }
						    else
						    {
						    	$sql = "insert into we_retrieve_password (id,login_account,req_date,valid_date,valid) values (?,?,now(),adddate(now(),1),'1')";
                  $da->ExecSQL($sql,array($active_code,$login_account));
    
						      //发送成功后存active_code码
						      $sql = "update we_register set active_code=?,last_reg_date=now(),submit_num=ifnull(submit_num,0)+1,"
						              ."state_id='0',review_note='0' where login_account=?";
						      $para = array($active_code,$login_account);
						      $da->ExecSQL($sql,$para);
		              $result = array("succeed" => true,"content" => "短信验证码已成功发送，请注意查收");
						    }
				     }
			     }
			     catch (\Exception $e) 
           {
             $result = array("succeed" => false,"content" => "获取并发送短信验证码失败，请重试!");
           }  
			   }
		  }
		  return $result;
  }
  
  //设置密码（手机方式找回密码）
  public function updatePassByMobileAction(Request $request)
  {
  	$login_account = $request->get("login_account");
  	$pwd    = $request->get("pwd");
  	$active  = $request->get("active_code");
    $result = array();
    $state = $this->checkLose($login_account,$active);
    if ( $state == 2 )
      $result = array("succeed" => false,"err" => "短信验证码错误！");
    else if ($state == 0 )
      $result = array("succeed" => false,"err" => "短信验证码已过期！");
    else if ( $state == 1 )
    {
        $da = $this->get('we_data_access');
        $da_im = $this->get('we_data_access_im');
        $pwdMgr = new \Justsy\BaseBundle\Management\Staff($da,$da_im,$login_account,$this->get("logger"),$this->container);
        $factory = $this->container->get("security.encoder_factory");
        $result = $pwdMgr->changepassword($login_account,$pwd,$factory);
        $success = isset($result["returncode"]) ? $result["returncode"] : "9999";
        if ( $success=="0000")
        {
            $sql = "update we_retrieve_password set valid='0' where id=? and login_account=?";
	          $da->ExecSQL($sql,array((string)$active,(string)$login_account));
	          $result = array("succeed" => true,"url" => $this->generateUrl('root'));
	      }
	      else
	      {
	          $result = array("succeed" =>false,"err"=>"修改密码失败");	        
	      }
    }
    $response = new Response(json_encode($result));
		$response->headers->set('Content-Type', 'text/json');
		return $response;
  }
  
  //检查验证码是否失效
  public function checkLose($account,$active_code)
  {
  	//1:未过期；0:已过期；2:不存在
  	$result = 1;
    $da = $this->get('we_data_access'); 
    $sql = "select case when now()<valid_date and valid='1' then 1 else 0 end state ".
           " from we_retrieve_password where id=? and login_account=?";
    $ds = $da->GetData('we_retrieve_password',$sql,array($active_code,$account));
    if (!$ds || $ds['we_retrieve_password']['recordcount']==0)
      $result = 2;
    else
    	$result = $ds['we_retrieve_password']['rows'][0]['state'];
    return $result;
  }
  
  
  //检查用户账号及验证码并判断用户手机号是还绑定
  public function checkVerifycodeAction(Request $request)
  {
    $login_account = $request->get("login_account");
    $code =  strtolower($request->get("code"));
    $session = $this->get('session');
    $result = array();
    if ( $code!=$session->get("verifycode"))
    	 $result = array("succeed" => false,"Exists"=>false,"msg"=>"验证码失效！");
    else {
    	 $da = $this->get('we_data_access');
    	 $sql = "select mobile_bind from we_staff where login_account=?";
		   $ds = $da->GetData('we_staff',$sql,array((string)$login_account));
		   if (!$ds || $ds['we_staff']['recordcount']==0)
		      $result = array("succeed" => true,"Exists"=> false,"msg"=>"未查询到数据记录！");
		   else
		   {
		   	 $mobile_bind = $ds["we_staff"]["rows"][0]["mobile_bind"]; //用户手机号
		   	 if ( $mobile_bind==null || empty($mobile_bind) )
		   	 	 $result = array("succeed" => true,"Exists"=> false,"msg"=>"未绑定手机号码，不能发送短消息！");
		   	 else{
		   	 	 //同时发送手机验证码
		   	 	 $sendMessage = $this->getMobileCode($login_account);
		   	 	 if ($sendMessage["succeed"])
		   	 	 	 $result = array("succeed" => true,"Exists"=> true,"msg"=>"发送短消息成功！");
		   	 	 else
		   	 	   $result = array("succeed" => false,"Exists"=> true,"msg"=> $sendMessage["content"]);
		   	 }
		   }
    } 
    $response = new Response(json_encode($result));
		$response->headers->set('Content-Type', 'text/json');
		return $response;
  }
}