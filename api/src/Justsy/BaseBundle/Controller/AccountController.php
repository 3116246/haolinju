<?php

namespace Justsy\BaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\Login\UserSession;
use Justsy\BaseBundle\Login\UserProvider;
use Justsy\BaseBundle\Common\DES;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Management\Staff;
use PHPExcel;
use PHPExcel_IOFactory;
 
//帐户管理
class AccountController extends Controller
{
  public function basicAction($network_domain) 
  {
    $user = $this->get('security.context')->getToken()->getUser();
    $list["image_path"] = $user->photo_path_big;

    if ( $list["image_path"]=="")
      $list["path"]= $this->get('templating.helper.assets')->geturl('bundles/fafatimewebase/images/no_photo.png');
    else 
      $list["path"] = $this->container->getParameter('FILE_WEBSERVER_URL').$list["image_path"];
    $list["msg"]=null;
    $list["curr_network_domain"]=$network_domain;    
    $list["account"] = $user->getUsername();
    $list["name"] = $user->nick_name;
    $list["deptid"] = $user->dept_id;    
    $list["deptname"] = $user->dept_name;    
    $list["duty"] = $user->duty;
    $list["mobile"] =  $user->mobile;
    $da = $this->get("we_data_access");
    $table = $da->GetData("staff","select self_desc,sex_id from we_staff  where login_account=?",array((String)$user->getUsername()));
    if ($table && $table["staff"]["recordcount"] >0 )
    {
    	$list["self"] = $table["staff"]["rows"][0]["self_desc"];
    	$list["sex_id"] = $table["staff"]["rows"][0]["sex_id"];
    }
    $sql = "select mobile, mobile_bind
from we_staff a
where a.login_account=?";
    $params = array();
    $params[] = $user->getUserName();   
    
    $ds = $da->GetData("we_staff_mobile", $sql, $params);
    $list["we_staff_mobile"] = $ds["we_staff_mobile"];
    $list["IS_ORG_VIEW"]=$user->IsExistsFunction("ORG_VIEW")?'1':'0';
    $list["fileurl"]=$this->container->getParameter('FILE_WEBSERVER_URL');
    
    return $this->render("JustsyBaseBundle:Account:basic.html.twig",$list);
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
  
  //保存数据
  public function saveBasicAction(Request $request)
  {
     $session = $this->get('session'); 
     $filename120 = $session->get("avatar_big");
     $filename48 = $session->get("avatar_middle");
     $filename24 = $session->get("avatar_small");
     $new_dept_id= $request->get("txtdeptid");
     $user = $this->get('security.context')->getToken()->getUser();
     $dm = $this->get('doctrine.odm.mongodb.document_manager');
     if (!empty($filename120)) $filename120= $this->saveFile($filename120,$dm);
     if (!empty($filename48)) $filename48=   $this->saveFile($filename48,$dm);
     if (!empty($filename24)) $filename24=   $this->saveFile($filename24,$dm);
     $session->remove("avatar_big");
     $session->remove("avatar_middle");
     $session->remove("avatar_small");      
     $da = $this->get("we_data_access");
     $para["account"] = $user->getUsername();

     $table = $da->GetData("staff","select fafa_deptid from we_department where dept_id=?",
        array((string)$new_dept_id));
     $Jid = $user->fafa_jid;
     if (!empty($filename120))
     {
       if (!empty($user->photo_path))  //如果用户原来有头像则删除
       {
         $this->removeFile($user->photo_path,$dm);
         $this->removeFile($user->photo_path_small,$dm);
         $this->removeFile($user->photo_path_big,$dm);
       }
     }
     $old_nick_name = $user->nick_name;
     $old_self_desc = $user->self_desc;
     $nick_name=$request->get("txtname");
     $self_dsc = str_replace("&","",str_replace(",","，",$request->get("txtself")));
     if (empty($filename120))
     {
        $sql = "update we_staff set self_desc=?,duty=?,nick_name=?,mobile=?,dept_id=?,sex_id=? where login_account=?";
        $paras[]= $self_dsc;
        $paras[]= str_replace("&","",str_replace(",","，",$request->get("txtduty")));
        $paras[]= $nick_name;
        $paras[]= $request->get("txtmobile") == "" ? null : $request->get("txtmobile");
        $paras[] = $request->get("txtdeptid"); 
        $paras[]= str_replace("&","",str_replace(",","，",$request->get("txtsex_id")));
        $paras[]= $para["account"];    
   
     }
     else
     {
        $sql = "update we_staff set self_desc=?,duty=?,nick_name=?,mobile=?,photo_path=?,photo_path_small=?,photo_path_big=?,dept_id=?,sex_id=? where login_account=?";
        $paras[]= str_replace("&","",str_replace(",","，",$request->get("txtself")));
        $paras[]= str_replace("&","",str_replace(",","，",$request->get("txtduty")));
        $paras[]= $nick_name;
        $paras[]= $request->get("txtmobile") == "" ? null : $request->get("txtmobile");
        
        $paras[]= $filename48;
        $paras[]= $filename24;
        $paras[]= $filename120;        
        $paras[] = $request->get("txtdeptid");
        $paras[]= str_replace("&","",str_replace(",","，",$request->get("txtsex_id")));
        $paras[]= $para["account"];   
     }
     try
     {
        $para["name"] = $nick_name;
        $para["year"]  =  $request->get("dateYear");
        $para["month"]  =  $request->get("dateMonth");
        $para["day"]  =  $request->get("dateDay");
        if ( empty($filename120))
        {
        	if (!empty($user->photo_path))
             $para["path"] = $this->container->getParameter('FILE_WEBSERVER_URL').$user->photo_path_big;
          else
             $para["path"]=$this->get('templating.helper.assets')->geturl('bundles/fafatimewebase/images/no_photo.png');
        }
        else
          $para["path"] = $this->container->getParameter('FILE_WEBSERVER_URL').$filename120;
        $da->ExecSQL($sql,$paras);
        if(!empty($filename120)){
        	$friendevent=new \Justsy\BaseBundle\Management\FriendEvent($da,$this->get('logger'),$this->container);
        	$friendevent->photochange($user->getUserName(),$user->nick_name);
        }
        $da_im = $this->get('we_data_access_im');
//        //回写ejabberd
        $fafaDeptid = $new_dept_id;
        if(count($table["staff"]["rows"])>0)
           $fafaDeptid = $table["staff"]["rows"][0]["fafa_deptid"]; //获取部门在ejabberd中的对应的部门ID
	      $old_dept_id = $user->dept_id;
	      if($old_dept_id!=$new_dept_id)
	      {
			      $sql_im_employee = "update im_employee set deptid=? where loginname=?";
			      $para_im_employee = array();
			      $para_im_employee[] = (string)$fafaDeptid;
			      $para_im_employee[] = (string)$user->fafa_jid;
			      //重置IM数据版本
    	      $sql_im = "delete from im_dept_version where us in(SELECT loginname FROM we_im.im_employee a, im_base_dept b where a.deptid=b.deptid and b.path like ? )";
    	      $para_im = array();
    	      $para_im[] = "/-10000/v".$user->eno."/%";
			      $da_im->ExecSQLs(array($sql_im_employee,$sql_im), array($para_im_employee,$para_im));
			      $da_im->ExecSQL("call dept_emp_stat(?)",array((string)$user->eno));
	      }
	      $sql_im_employee = "delete from im_employee_version where us=?";
	      $para_im_employee = array();
	      $para_im_employee[] = (string)$user->fafa_jid;	      
	      $da_im->ExecSQL($sql_im_employee, $para_im_employee);	 
	      if($old_nick_name!=$nick_name)
	      	$da_im->ExecSQL("call emp_change_name(?,?)",array((string)$user->fafa_jid,(string)$nick_name)); 
	      if($old_self_desc!=$self_dsc)  //签名发生变化事，处理人脉事件
	      {
            $friendevent=new \Justsy\BaseBundle\Management\FriendEvent($da,$this->get('logger'),$this->container);
            $friendevent->signchange($user->getUserName(),$user->nick_name,$self_dsc);     	  
	      }
	     //发送个人资料编辑通知
          try{ 
            //发送即时消息
	          $message = "{\"path\":\"".$para["path"]."\",\"desc\":\"".strtr($request->get("txtself"),array("\""=>"“"))."\"}";
	          Utils::sendImPresence("",$user->fafa_jid,"staff-changeinfo",$message,$this->container,"","",false,Utils::$systemmessage_code);
	        }catch (\Exception $e) 
		      {
		          $this->get('logger')->err($e);
		      }
			  $response = new Response(("{\"succeed\":1,\"path\":\"".$para["path"]."\"}"));
			  $response->headers->set('Content-Type', 'text/json');
			  return $response;        
     }
     catch(\Exception $e)
     {
        //return $this->render('JustsyBaseBundle:login:index.html.twig', array('name' => 'err'));
       $response = new Response(("{\"succeed\":0,\"e\":$e}"));
			 $response->headers->set('Content-Type', 'text/json');
			 return $response;
     }
  }  
  
  //详细信息
  public function detailAction($network_domain) 
  {
    $user = $this->get('security.context')->getToken()->getUser(); 
    $da = $this->get("we_data_access");
    $table = $da->GetData("staff","select self_desc,specialty,hobby,hometown,graduated,work_his,sex_id from we_staff  where login_account=?",array((String)$user->getUsername()));
    if ($table && $table["staff"]["recordcount"] >0 )
    {

       $list["specialty"] =  $table["staff"]["rows"][0]["specialty"];
       $list["hobby"] =  $table["staff"]["rows"][0]["hobby"];
       $list["hometown"] =  $table["staff"]["rows"][0]["hometown"];
       $list["graduated"] =  $table["staff"]["rows"][0]["graduated"];
       $list["work_his"] =  $table["staff"]["rows"][0]["work_his"];
       $list["sex_id"] =  $table["staff"]["rows"][0]["sex_id"];
       $list["work_phone"] =  $user->work_phone;
       
       //$list["fafa"] = $user->fafa_jid;
       $list["msg"] = null;
       $list["curr_network_domain"]=$network_domain;
    }
    $list["account"] = $user->getUsername();
    $list["year"]  = trim((string)(date('Y',strtotime($user->birthday))),'-');
    $list["month"] = date('n',strtotime($user->birthday));
    $list["day"]   = date('j',strtotime($user->birthday));
    $list["direct_manages"]=$user->direct_manages;
    $list["report_object"]=$user->report_object;
    $list["direct_manages"]=explode(';',$list["direct_manages"]);
    $num_direct_manages='';
    for($i=0;$i<count($list["direct_manages"]);$i++)
    {
    	$num_direct_manages.='?,';
    }
    $num_direct_manages=substr($num_direct_manages,0,strlen($num_direct_manages)-1);
    $sql=array(
              'select login_account,nick_name from we_staff where login_account in ('.$num_direct_manages.')',
              'select login_account,nick_name from we_staff where login_account =?'
    );
    $DataAccess=$this->get('we_data_access');
    $dataset=$DataAccess->GetDatas(array('we_staff1','we_staff2'),$sql,array($list["direct_manages"],array($list["report_object"])));
    $list["direct_manages"]=$dataset['we_staff1']['recordcount']>0?$dataset['we_staff1']['rows']:array();
    $list["report_object"] =$dataset['we_staff2']['recordcount']>0?$dataset['we_staff2']['rows'][0]:array();
        
    $sql = "select mobile, mobile_bind
from we_staff a
where a.login_account=?";
    $params = array();
    $params[] = $user->getUserName();
    
    $da = $this->get("we_data_access");
    $ds = $da->GetData("we_staff_mobile", $sql, $params);
    $list["we_staff_mobile"] = $ds["we_staff_mobile"];
    
    return $this->render("JustsyBaseBundle:Account:detail.html.twig",$list);
  }
  public function searchAction($network_domain)
  {
  	$DataAccess=$this->get('we_data_access');
  	$eno=$this->get('security.context')->getToken()->getUser()->getEno();
  	$login_account=$this->get('security.context')->getToken()->getUser()->getUsername();
  	$q=$this->getRequest()->get('q');
  	if(empty($q))
  	{
  		$sql='select a.login_account,ifnull(a.nick_name,"") as nick_name from we_staff a where a.login_account!=? limit 0,100';
  		$dataset=$DataAccess->GetData('we_staff',$sql,(string)$login_account);
  	}
  	else
  	{

  	$sql='select a.login_account,ifnull(a.nick_name,"") as nick_name from we_staff a where 
  	     ( substring_index(a.login_account,"@",1) like ? or a.nick_name like ?) and a.eno=? and a.login_account!=? limit 0,100';
  	$dataset=$DataAccess->GetData('we_staff',$sql,array('%'.$q.'%','%'.$q.'%',(string)$eno,(string)$login_account));
   }
  	$data=array();
  	if(count($dataset['we_staff']['rows'])>0)
  	{
  		$data=$dataset['we_staff']['rows'];
  	}
  	$response = new Response(json_encode($data));
		$response->headers->set('Content-Type', 'text/json');
		return $response;                     
  }
  public function saveDetailAction(Request $request)
  {
  	$user = $this->get('security.context')->getToken()->getUser();
    $account = $user->getUsername();
    $birthday=$request->get("dateYear")."-".$request->get("dateMonth")."-".$request->get("dateDay");
    $da = $this->get("we_data_access");
    $sql = "update we_staff set birthday=?,specialty=?,hobby=?,work_phone=?,hometown=?,graduated=?,work_his=?,report_object=?,direct_manages=? where login_account=?";
    $paras = array();   
    $paras[]= $birthday;
    $paras[]= str_replace("&","",str_replace(",","，",$request->get("txtspecialty")));
    $paras[]= str_replace("&","",str_replace(",","，",$request->get("txthobby")));
    $paras[]= str_replace("&","",str_replace(",","，",$request->get("txtworkphone")));
    
    $paras[]= str_replace("&","",str_replace(",","，",$request->get("txthometown")));
    $paras[]= str_replace("&","",str_replace(",","，",$request->get("txtgraduated")));
    $paras[]= str_replace("&","",str_replace(",","，",$request->get("txtwork_his")));
    $paras[]=$request->get('txtreport_object');
    $paras[]=$request->get('array_direct_manages'); 
    $paras[]= $account;

    try
    {
        $da->ExecSQL($sql,$paras); 
			  $response = new Response(("{\"succeed\":1}"));
			  $response->headers->set('Content-Type', 'text/json');
			  return $response;                     
    }
    catch(\Exception $e)
    {
       //return $this->render('JustsyBaseBundle:login:index.html.twig', array('name' => 'err'));
			  $response = new Response(("{\"succeed\":0}"));
			  $response->headers->set('Content-Type', 'text/json');
			  return $response;        
    }
  }  
  
  //密码修改
  public function pwdAction($network_domain) 
  {
    $list["curr_network_domain"]=$network_domain;
    $list["msg"] = null;
    return $this->render("JustsyBaseBundle:Account:updatepwd.html.twig",$list);
  }
  
  //检验密码
  public function checkPwdAction(Request $request)
  {   
     $password = $request->get("id");     
     $user = $this->get('security.context')->getToken()->getUser();
     $factory = $this->get('security.encoder_factory');
     $encoder = $factory->getEncoder($user);
     $password = $encoder->encodePassword($password, $user->getSalt());
     $result = 0;   
     $da = $this->get("we_data_access");
     $table = $da->GetData("staff","select `password` from we_staff where login_account=?",array((String)$user->getUsername()));
     if ($table && $table["staff"]["recordcount"] >0 )  //如果用户原来有头像则删除
     {
        if ($password == $table['staff']['rows'][0]['password'])
          $result = 1;
     }
     return new Response($result);
  }
    
  //修改密码
  public function updatePwdAction(Request $request)
  {
     $oldpwd = $request->get('txtoldpwd');
     $pwd = $request->get("txtnewpwd2");   
     $user = $this->get('security.context')->getToken()->getUser();    
     $factory = $this->get('security.encoder_factory');
     $encoder = $factory->getEncoder($user);
     $da = $this->get("we_data_access");
     $table = $da->GetData("staff","select eno, password, fafa_jid,t_code from we_staff where login_account=?",array((String)$user->getUsername()));
     $Jid = $table["staff"]["rows"][0]["fafa_jid"];  
     $eno = $table["staff"]["rows"][0]["eno"]; 
     $OldPass = $table["staff"]["rows"][0]["password"];  
     $Old_t_code = $table["staff"]["rows"][0]["t_code"];     
     
     $oldpwd = $encoder->encodePassword($oldpwd, $user->getSalt());
     if ($oldpwd != $OldPass)
     {
        $response = new Response('{"succeed":0, "msg": "原始密码不正确"}');
    	  $response->headers->set('Content-Type', 'text/json');
        return $response;
     }
     
     $sql = "update we_staff set password=?,t_code=? where login_account=?";
     $paras[0] = $encoder->encodePassword($pwd, $user->getSalt());
     $paras[1] = DES::encrypt($pwd);
     $paras[2] = $user->getUsername();
     try
     {
        $da->ExecSQL($sql,$paras);
        //同步ejabberd
        try{
          $sql_im = "update users set password=? where username=?";
  	      $para_im = array();
  	      $para_im[] = (string)$pwd;
  	      $para_im[] = (string)$user->fafa_jid;
  	      $da_im = $this->get('we_data_access_im');
  	      $da_im->ExecSQL($sql_im, $para_im);	 
          
//			        $ejabberdSvrUrl = $this->container->getParameter("FAFA_REG_JID_URL");
//			        $encode = ",".DES::encrypt("$eno,$Jid,$pwd,,");
//			        $regUrlAcc = "$ejabberdSvrUrl/empservice.yaws?changeemployeePass=$encode";
//			        $result = trim(\Justsy\BaseBundle\Common\Utils::getUrlContent($regUrlAcc,$this->get('logger')));
//			        $this->get('logger')->err($regUrlAcc);
//			        $this->get('logger')->err("=============".$result);
//			        $resultAcc = json_decode($result);
//			        if (!$resultAcc->{"succeed"})
//			        {
//			        	  //还原原密码
//			        	  $sql = "update we_staff set password=?,t_code=? where login_account=?";
//			        	  $paras[0] = $OldPass;
//			        	  $paras[1] = $Old_t_code;
//			        	  $paras[2] = $user->getUsername();
//			        	  $da->ExecSQL($sql,$paras);
//			        	  $response = new Response($result);
//			        	  $response->headers->set('Content-Type', 'text/json');
//			            return $response;
//			        }
//			        else
//			        {
			            $response = new Response(("{\"succeed\":1}"));
			        	  $response->headers->set('Content-Type', 'text/json');
			            return $response;
//			        } 
        }
        catch(\Exception $e)
        {
        	        //还原原密码
			        	  $sql = "update we_staff set password=?,t_code=? where login_account=?";
			        	  $paras[0] = $OldPass;
			        	  $paras[1] = $Old_t_code;
			        	  $paras[2] = $user->getUsername();
			        	  $da->ExecSQL($sql,$paras);
                  $response = new Response(('{"succeed":0, "msg": "同步密码出错"}'));
			        	  $response->headers->set('Content-Type', 'text/json');
			            return $response;
        }
     }
     catch(\Exception $e)
     {
        $response = new Response(('{"succeed":0, "msg": "系统出错"}'));
			  $response->headers->set('Content-Type', 'text/json');
			  return $response;
     }
  }  
  
  //个人设置
  public function preferenceAction($network_domain) 
  {
    $da = $this->get("we_data_access");
    $user = $this->get('security.context')->getToken()->getUser();
    $list = array();
    
    $sql = "select para_id, para_value from we_staff_para where login_account=?";
    $params = array();
    $params[] = (string)$user->getUserName();
    
    $ds = $da->GetData("we_staff_para", $sql, $params);
    foreach ($ds["we_staff_para"]["rows"] as &$value) 
    {
      $list[$value["para_id"]] = $value["para_value"];
    }
    
    $list["curr_network_domain"]=$network_domain;
    return $this->render("JustsyBaseBundle:Account:preference.html.twig",$list);
  } 
  public function savePreferenceAction($network_domain)
  {
    $re = array();
    $user = $this->get('security.context')->getToken()->getUser();
    $request = $this->getRequest();
    $da = $this->get("we_data_access");
    
    $prefs = array();
    $prefs["pref_externview_dept"] = $request->get("pref_externview_dept") == "1" ? "1" : "0";
    $prefs["pref_externview_sex"] = $request->get("pref_externview_sex") == "1" ? "1" : "0";
    $prefs["pref_externview_duty"] = $request->get("pref_externview_duty") == "1" ? "1" : "0";
    $prefs["pref_externview_work_phone"] = $request->get("pref_externview_work_phone") == "1" ? "1" : "0";
    $prefs["pref_externview_mobile"] = $request->get("pref_externview_mobile") == "1" ? "1" : "0";
    $prefs["pref_externview_hometown"] = $request->get("pref_externview_hometown") == "1" ? "1" : "0";
    $prefs["pref_externview_graduated"] = $request->get("pref_externview_graduated") == "1" ? "1" : "0";
    $prefs["pref_externview_work_his"] = $request->get("pref_externview_work_his") == "1" ? "1" : "0";
    
    try
    {
      $sqls = array();
      $all_params = array();
        
      $sql = "delete from we_staff_para where login_account=? and para_id like 'pref_%'";
      $params = array();
      $params[] = (string)$user->getUserName();
              
      $sqls[] = $sql;
      $all_params[] = $params;
      
      foreach ($prefs as $key => $value) 
      {
        $sql = "insert into we_staff_para(login_account, para_id, para_value) values(?, ?, ?)";
        $params = array();
        $params[] = (string)$user->getUserName();
        $params[] = (string)$key;
        $params[] = (string)$value;
                
        $sqls[] = $sql;
        $all_params[] = $params;        
      }      
      
      $da = $this->get("we_data_access");
      $da->ExecSQLs($sqls, $all_params); 
      
      $re["success"] = "1";  
    } 
    catch (\Exception $e) 
    {
      $re["success"] = "0";  
      $re["msg"] = "保存失败";  
      $this->get('logger')->err($e);
    }
    
    $response = new Response(json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response; 
  }
  
  private function checkDeptEmployee($deptid)
  {
  	    $da = $this->get("we_data_access");  
  	    $da_im = $this->get("we_data_access_im");
  	    //判断部门下是否有人员
  	    $sqls = "select fafa_deptid from we_department where dept_id= ?";
  	    $ds = $da->GetData("dept",$sqls,array((string)$deptid));
  	    if(count($ds["dept"]["rows"])>0)
  	    {
  	    	$fafa_deptid = $ds["dept"]["rows"][0]["fafa_deptid"];
  	    	$sql = "select path from im_base_dept where deptid=?";
  	    	$ds= $da_im->GetData("h",$sql,array((string)$fafa_deptid));
  	    	if(count($ds["h"]["rows"])>0)
  	    	{  	    		
  	    	    $sql = "select count(0) cnt from im_employee where deptid in(select deptid from im_base_dept where path like concat(?,'%'))";
  	    	    $ds2= $da_im->GetData("h1",$sql,array((string)$ds["h"]["rows"][0]["path"]));
  	    	    if($ds2["h1"]["rows"][0]["cnt"]>0)
  	    	       return true;
  	      }
  	    }
  	    return false;  	
  }
  
  public function chkDeptEmpAction($network_domain)
  {
  	    $deptid = $this->get("request")->get("deptid");
	  	  //$user = $this->get('security.context')->getToken()->getUser();  	
        $isHasEmp = $this->checkDeptEmployee($deptid);
        if($isHasEmp)
        {
		        $response = new Response(json_encode(array("s"=>0)));
		        $response->headers->set('Content-Type', 'text/json');  	    	
  	    	  return $response;
        }
        else
        {
		        $response = new Response(json_encode(array("s"=>1)));
		        $response->headers->set('Content-Type', 'text/json');  	    	
  	    	  return $response;
        }
  }
  //搜索员工
  public function searchEmpAction($network_domain){
    $user = $this->get('security.context')->getToken()->getUser();
    $deptid = trim($this->get("request")->get("deptid")); 
    $state = trim($this->get("request")->get("state"));
    $search = trim($this->get("request")->get("search"));     
    $da = $this->get("we_data_access");
    $da_im = $this->get('we_data_access_im');
    $result=array();
    
    $staffMgr = new Staff($da,$da_im,$user);
    $result = $staffMgr->querySearchBaseInfo($deptid,$state,$search);
    $response = new Response(json_encode($result));
    $response->headers->set('Content-Type', 'text/json');
    return $response;        
  }
  //获取指定部门下的所有人员
  public function getDeptEmpAction($network_domain)
  {
  	  $user = $this->get('security.context')->getToken()->getUser();
  	  $deptid = trim($this->get("request")->get("deptid")); 
  	  $state = trim($this->get("request")->get("state"));
      //$search = trim($this->get("request")->get("search"));  	  
  	  $da = $this->get("we_data_access");
  	  $da_im = $this->get('we_data_access_im');
  	  $result=array();
  	  
  	  $staffMgr = new Staff($da,$da_im,$user->getUserName());
  	  $result = $staffMgr->queryAllBaseInfo($deptid,$state);
      $response = new Response(json_encode($result));
		  $response->headers->set('Content-Type', 'text/json');
  	  return $response;  	     
  }
  
  public function employeemgrAction($network_domain)
  {
  	$request = $this->get("request");
  	$user = $this->get('security.context')->getToken()->getUser();  
  	$action = $request->get("action");	
  	$account = $request->get("account");	
  	$re=array('s'=>1);
  	try
  	{
  		  $accounts = explode(",",$account);
  		  for($i=0; $i<count($accounts); $i++)
  		  {
				  	$staffmgr = new Staff($this->get("we_data_access"),$this->get("we_data_access_im"),$accounts[$i]);
				  	if($action=="disable") $staffmgr->disable(null);
				  	else if($action=="delete") $staffmgr->deleteImportPhy();
				  	else if($action=="leave"){
				  		 //
				  		 $staffmgr->leave();				  		
				  	}
				  	else if($action=="invite")
				  	{				  		   
				  		   try{
				  		     $staffmgr->invite();
				  		   }
				  		   catch(\Exception $e){
				  		   	  //已注册的用户不能再邀请
				  		      continue;	
				  		   }
				  		   //发送邮件
						     $activeurl = $this->generateUrl("JustsyBaseBundle_empimport_setpass",array('account'=>DES::encrypt($account)),true);
						     $txt=$this->renderView('JustsyBaseBundle:Register:mail.html.twig',
						                array('realName'=>$user->nick_name,'account'=>$account,'activeurl'=>$activeurl));
						     Utils::saveMail($this->get("we_data_access"),$this->container->getParameter('mailer_user'),$account,"欢迎加入Wefafa企业协作网络",$txt);
				  	}
		    }
    }
    catch(\Exception $e)
    {
    	$re=array('s'=>0,'msg'=> "操作失败");
    }
		$response = new Response(json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;    
  }
  //导入人员
  public function importEmployeeAction($network_domain)
  {
  	
  	$request = $this->get("request");
  	$user = $this->get('security.context')->getToken()->getUser();
  	//判断当前导入人员是否是企业邮箱
  	$userDomain = explode("@",$user->getUserName());
  	$da=$this->get("we_data_access");
    $sql = "select 1 from we_public_domain where domain_name=?";
    $ds = $da->GetData("mt",$sql,array((string)$userDomain[1]));
    $mailType = count($ds["mt"]["rows"])>0 ? "0":"1"; //1表示是企业邮箱
  	try{
		  	$upfile = $request->files->get("filedata");
		  	$tmpPath = $upfile->getPathname();
		    $oldName=$upfile->getClientOriginalName();
		    $fixs = explode(".",strtolower($oldName));
		    if(count($fixs)<2) 
		    {
		    	 $re=array('s'=>0,'message'=>"文件类型不正确");
		    }
		    else
		    {
				    $fixedType = $fixs[count($fixs)-1];
				    if($fixedType!="xlsx" && $fixedType!="xls")
				    {
				    	$re=array('s'=>0,'message'=>"文件类型不正确");
				    }
				    else
				    {
						    $newFileName=$user->openid.date('y-m-d-H-m-s').".".$fixedType;
						    if(move_uploaded_file($tmpPath,'upload/'.$newFileName))
							{
									  
								$da=$this->container->get('we_data_access');
								$objReader = \PHPExcel_IOFactory::createReader( $fixedType=="xlsx"? 'Excel2007':"Excel5") ;//use excel2007 for 2007 format
						        $objPHPExcel = $objReader->load($_SERVER['DOCUMENT_ROOT'].'/upload/'.$newFileName); 
								$objWorksheet = $objPHPExcel->getActiveSheet();
						        $highestRow = $objWorksheet->getHighestRow(); 
						        $highestColumn = $objWorksheet->getHighestColumn();
						        $highestColumnIndex = \PHPExcel_Cell::columnIndexFromString($highestColumn);//总列数
						        //获取标题行
						        $titleAry = array();
						        $account_index = 0;
						        $name_index = 0;
						        $mobile_index=0;
						        $pwd_index = 0;
								for ($row = 0;$row <= 1;$row++) 
						        {
						            for ($col = 0;$col < $highestColumnIndex;$col++)
						            {
						                $titleAry[$col] =$objWorksheet->getCellByColumnAndRow($col, $row)->getValue();	
						                if(strpos($titleAry[$col],"邮箱")!==false) $account_index=$col;		
						                else if(strpos($titleAry[$col],"姓名")!==false) $name_index=$col;	 
						                else if(strpos($titleAry[$col],"手机")!==false) $mobile_index=$col;	 
						                else if(strpos($titleAry[$col],"密码")!==false) $pwd_index=$col;               
						            }
						        }
		                		$titleAry[]="eno";
		                		$err_list=array();
		                		$da = $this->get("we_data_access");
		                		$dm = $this->get("we_data_access_im");
		                		//获取数据行
				            	for ($row = 2;$row <= $highestRow;$row++) 
						        {
						            $strs=array();
						            for ($col = 0;$col < $highestColumnIndex;$col++)
						            {
						                $strs[$col] =trim((string)$objWorksheet->getCellByColumnAndRow($col, $row)->getValue());
						            }
						            $strs[]=$user->eno;
						            $name = $strs[$name_index];
						            if(empty($name))
						            {
						            	 $err_list[]=array("name"=>"","row"=>($row),"msg"=>"姓名不能为空");		
						               continue;
						            }
						            if(strlen($name)==1)
						            {
						            	 $err_list[]=array("name"=>"","row"=>($row),"msg"=>"姓名不能少于2个字符");		
						               continue;
						            }
						            
						            //获取填写的帐号
						            $account = $strs[$account_index];	
						            
						            if(empty($account))
						            {
						            	 $err_list[]=array("name"=>$name,"row"=>($row),"msg"=>"邮箱帐号不能为空");		
						               continue;						            	
						            }  
						            if(!Utils::validateEmail($account))
						            {
						            	 $err_list[]=array("name"=>$name,"row"=>($row),"msg"=>"邮箱帐号格式不正确");		
						               continue;
						            }
						            $staffmgr = new Staff($da,$dm,$account);
						            if($staffmgr->checkNickname($user->eno,$name)===true)
						            {
						                     $err_list[]=array("name"=>"","row"=>($row),"msg"=> "[".$name."]已经注册，请检查！");		
						                     continue;
						            }
						            //if($mailType=="1" && explode("@",$account)[1]!=$userDomain[1] )
						            //{
						            //	 $err_list[]=array("name"=>$name,"row"=>($row),"msg"=>"不允许导入公共邮箱$account");
						            //   continue;
						            //}
						            $mobile = $strs[$mobile_index];
						            if(!empty($mobile))
						            {
						            	  if(!Utils::validateMobile($mobile))
						            	  {
						            	     $err_list[]=array("name"=>$name,"row"=>($row),"msg"=>"手机号码格式不正确");		
						                   continue;						            	  	
						            	  }
						            }
						            
						            //判断帐号是否已经注册
						            $isexist = $staffmgr->isExist($mobile);
						            if(!empty($isexist)){
						            	 //已注册
						            	 $err_list[]=array("name"=>$name,"msg"=>"邮箱或手机号已被使用");		
						               continue;	
						            }
						            //判断是否已导入，已导入，则不再发邮件
						            $isImport=false;
						            try{
						               	$isImport=$staffmgr->getImportInfo();
						            }
						            catch(\Exception $err)
						            {
						            }
						            try{
							            $staffmgr->importReg($titleAry,$strs);
							            //判断是否设置了密码
							            $pwd = $strs[$pwd_index];
							            if(!empty($pwd))
							            {
							            	$sql = "select ename from we_enterprise where eno=?";
							            	$ds = $da->GetData("t",$sql,array((string)$user->eno));
							            	//自动激活
							            	$active=new \Justsy\BaseBundle\Controller\ActiveController();
				                            $active->setContainer($this->container);
				                            $active->doSave(array(
				                            	'account'=> $account,
				                            	'realName'=>$name,
				                            	'passWord'=> $pwd,
				                            	'eno'=> $user->eno,
				                            	'ename'=> $ds["t"]["rows"][0]["ename"],
				                            	'isNew'=>'0',
				                            	'mailtype'=> "1",
				                            	'isSendMessage'=>"N",
				                            	'import'=>'1'
				                            ));
				                            $staffmgr = new Staff($da,$dm,$account);
				                            $importData=$staffmgr->getImportInfo();
				                            $staffmgr->updateByImport($importData);
	  	    								$staffmgr->deleteImportPhy();
							            }
							        	else
							        	{
							            	if($isImport===false)
							            	{
									            //发送邮件
									            $activeurl = $this->generateUrl("JustsyBaseBundle_empimport_setpass",array('account'=>DES::encrypt($account)),true);
									            $txt=$this->renderView('JustsyBaseBundle:Register:mail.html.twig',
									                array('realName'=>$user->nick_name,'account'=>$account,'activeurl'=>$activeurl));
									            Utils::saveMail($da,$this->container->getParameter('mailer_user'),$account,"欢迎加入Wefafa企业协作网络",$txt);
							           		}
							        	}
						            }
						            catch(\Exception $err)
						            {                          
						            	   //写导入数据发生异常
						            	   $err_list[]=array("name"=>$name,"msg"=>"导入失败:".$err->getMessage());
						                 continue;
						            }
						        }
						        $re=array('s'=>1,'error_list'=>$err_list);
								}
								else
								{
										$re=array('s'=>0,'message'=>"文件上传失败");
								}
								try{
						       unlink($tmpPath);
						    }
						    catch(\Exception $e){}
						    
				    }
		}
    }catch(\Exception $ex)
    {
    	 $re=array('s'=>0,'message'=>"导入失败");
    }
	$response = new Response("<script>parent.import_callback(".json_encode($re).")</script>");
    $response->headers->set('Content-Type', 'text/html');
    return $response;
  }
  //导入人员密码设置保存
  public function importActiveSaveAction()
  {
  	  $request = $this->get("request");
  	  $account=trim($request->get("account"));
  	  $passWord=trim($request->get("passWord"));
  	  if(empty($account)||empty($passWord))
  	  {
  	  	  return $this->render('JustsyBaseBundle:Error:index.html.twig', array('error'=> "帐号或者密码不能为空"));	
  	  }
  	  $staffmgr = new Staff($this->get("we_data_access"),$this->get("we_data_access_im"),$account);
  	  $isexist = $staffmgr->isExist();
  	  if($isexist!=null)
  	     return $this->render('JustsyBaseBundle:Error:index.html.twig', array('error'=> "帐号已可以正常使用"));
  	  try{
  	      	$importData=$staffmgr->getImportInfo();
  	      	////判断帐号、姓名、手机是否重复或已使用
 			$arrayName = array((string)$importData["login_account"],(string)$importData["eno"],(string)$importData["nick_name"] );
		    $sql = "select  (select nick_name from we_staff where login_account=?) accountcheck ,";
		    $sql = $sql ."  (select nick_name from we_staff where eno=? and nick_name=?) namecheck ";
		    $mobileNO = trim($importData["mobile"]);
		    if(!empty($mobileNO))
		    {
		    	$sql = $sql ." ,(select nick_name from we_staff where mobile=?) mobilecheck ";
		    	$arrayName[]= (string)$mobileNO;
			}
			$da = $this->get("we_data_access");
		    $ds = $da->getdata("t",$sql,$arrayName);
		    if($ds)
		    {
		    	$dr = $ds["t"]["rows"][0];
		    	if(!empty($dr["accountcheck"])) 
		    	{
		    		if($dr["accountcheck"]==$importData["nick_name"]) 
		    			throw new \Exception("您的帐号的已激活，可以正常使用。");
		    		else
		    			throw new \Exception("该帐号已被".$dr["accountcheck"]."使用！");
		    	}
		    	else if(!empty($dr["namecheck"]))
		    	{
		    		throw new \Exception("姓名已存在，不能重复激活！");
		    	}
		    	else if(!empty($dr["mobilecheck"]))
		    	{
		    		throw new \Exception("手机号已被".$dr["mobilecheck"]."使用，请使用其他有效手机号码注册！");
		    	}
		    }
	  	    //激活人员帐号
	  	    $sdo=new \Justsy\BaseBundle\Controller\ActiveController();
	  	    $sdo->setContainer($this->container);
	  	    $sdo->doSaveAction();
	  	    //根据导入信息更新注册信息
	  	    $staffmgr->updateByImport($importData);
	  	    $staffmgr->deleteImportPhy();
  	  }
  	  catch(\Exception $e)
  	  {
  	  	 $this->get("logger")->err($e);
  	  	 return $this->render('JustsyBaseBundle:Error:index.html.twig', array('error'=> $e->getMessage()));
  	  }
  	  $data = $staffmgr->getInfo();
  	  $data["t_code"] = substr($passWord,0,1)."******".substr($passWord,-1);
  	  $data["password"] = $passWord;
  	  return $this->render('JustsyBaseBundle:Active:import_succeed.html.twig', array('edomain'=> $data["eno"],'data'=> $data));
  }
  //获取部门列表
  public function myDeptsAction($network_domain) 
  {
  	$user = $this->get('security.context')->getToken()->getUser();
  	$root = "v".$user->eno;
    $sql = "select dept_id,fafa_deptid ,dept_name,case dept_id when parent_dept_id then '".$root."' else parent_dept_id end parent_dept_id,create_staff from we_department where eno=?";
    $da = $this->get("we_data_access");
    $ds = $da->GetData("dept", $sql, array((string)$user->eno));
    $result=array();
    $result[]=array("open"=>true,"id"=> $root,"name"=> $user->eshortname,"pId"=>"0","owner"=>"");
    for($i = 0; $i<count($ds["dept"]["rows"]); $i++)
    {
    	$deptid = $ds["dept"]["rows"][$i]["fafa_deptid"];
    	if($deptid == $root) continue;//把根节点排除，因为默认都初始化了一条根数据
    	if($deptid == $root."999" ||$deptid == $root."999888" ) continue;//把公共部门排除
      $result[]= array("open"=>true,
                       "id"=> $ds["dept"]["rows"][$i]["dept_id"],
                       "name"=> $ds["dept"]["rows"][$i]["dept_name"],
                       "pId"=> $ds["dept"]["rows"][$i]["parent_dept_id"],
                       "owner"=>$ds["dept"]["rows"][$i]["create_staff"]);
    }
    $response = new Response(json_encode($result));
		$response->headers->set('Content-Type', 'text/json');  	    	
  	return $response;
  }
  
  public function changeStaffDeptAction($network_domain)
  {
       $res = $this->get("request");
  	   $account = trim($res->get("staff"));
  	   $deptid = trim($res->get("deptid"));
  	   if(empty($account) || empty($deptid))
  	   {
            $response = new Response(json_encode(array("s"=>0,
		                                                   "msg"=>"人员帐号或部门不能为空")));
		        $response->headers->set('Content-Type', 'text/json');  	    	
  	    	  return $response;  	   	
  	   }
  	   try{
            $da = $this->get("we_data_access");
  	        $da_im = $this->get('we_data_access_im');  	   	
  	   	    $accountList = explode(",",$account);
  	   	    $user = $this->get('security.context')->getToken()->getUser();
            $deptmgr = new \Justsy\BaseBundle\Management\Dept($da,$da_im);
		  	    $deptInfo=$deptmgr->getinfo($deptid);
		  	    if($deptInfo!=null)
		  	    {
		  	   	    for($i=0;$i<count($accountList);$i++)
		  	   	    {
				  	        $staffmgr = new \Justsy\BaseBundle\Management\Staff($da,$da_im,$accountList[$i]);
				  	        $result_jid=$staffmgr->moveToDept($deptid,$deptInfo["fafa_deptid"] );
				  	        //如果是其他人员更新了自己的部门，则向被移动的人员发条消息		  	        
				  	        if($user->getUsername()!=$accountList[$i])
				  	        {
							          try{
							            //发送即时消息
								          $message = "你的所属部门已变更为【".$deptInfo["dept_name"]."】";
								          Utils::sendImMessage($user->fafa_jid,$result_jid,"资料变更",$message,$this->container,"","",false,Utils::$systemmessage_code);
								        }catch (\Exception $e) 
									      {
									          $this->get('logger')->err($e);
									      }   	            	
				  	        }
		  	        }
		  	        $response = new Response(json_encode(array("s"=>1,
		                                                   "msg"=>"")));
		            $response->headers->set('Content-Type', 'text/json');  	    	
  	    	      return $response;
  	        }
  	        else
  	        {
		            $response = new Response(json_encode(array("s"=>0,
				                                                   "msg"=>"部门编号无效")));
				        $response->headers->set('Content-Type', 'text/json');  	    	
		  	    	  return $response;
  	    	  }
  	   }
  	   catch(\Exception $e)
  	   {
  	   		  $this->get("logger")->err($e);
            $response = new Response(json_encode(array("s"=>0,
		                                                   "msg"=>"数据操作失败")));
		        $response->headers->set('Content-Type', 'text/json');  	    	
  	    	  return $response;  	   		
  	   }
  }
  
  public function deptsaveAction($network_domain)
  {
  	    $res = $this->get("request");
  	    $deptname = trim($res->get("deptname"));
  	    $pid = trim($res->get("pid"));
  	    $deptid = trim($res->get("deptid"));
	  	  $user = $this->get('security.context')->getToken()->getUser();
  	    $da = $this->get("we_data_access");
  	    $da_im = $this->get('we_data_access_im');
  	    //判断是否已存在
  	    $sqls = "select * from we_department where eno=? and dept_name=?";
  	    $ds = $da->GetData("dept",$sqls,array((string)$user->eno,(string)$deptname));
  	    if($ds && count($ds["dept"]["rows"])>0)
  	    {
  	    	  if($ds["dept"]["rows"][0]["dept_id"]==$deptid)
		        		$response = new Response(json_encode(array("s"=>1,
		                                                   "id"=>$ds["dept"]["rows"][0]["dept_id"],
		                                                   "name"=>$ds["dept"]["rows"][0]["dept_name"],
		                                                   "pId"=>$ds["dept"]["rows"][0]["parent_dept_id"],
		                                                   "owner"=>$ds["dept"]["rows"][0]["create_staff"])));
		        else 
		            $response = new Response(json_encode(array("s"=>0,
		                                                   "msg"=>"部门名称已存在","deptid"=> $deptid)));
		        $response->headers->set('Content-Type', 'text/json');  	    	
  	    	  return $response;
  	    }
  	    if(empty($deptname))
  	    {
		        $response = new Response(json_encode(array("s"=>0,
		                                                   "msg"=>"部门名称不能为空")));
		        $response->headers->set('Content-Type', 'text/json');  	    	
  	    	  return $response;  	    	 
  	    }
  	    if($pid=="")
  	    {
		        $response = new Response(json_encode(array("s"=>0,
		                                                   "msg"=>"无效的父级部门")));
		        $response->headers->set('Content-Type', 'text/json');
  	    	  return $response;
  	    }
  	    if(!empty($deptid))
  	    {
  	    	 $sqls = "select fafa_deptid from we_department where eno=? and dept_id=?";
  	    	 $ds = $da->GetData("dept2",$sqls,array((string)$user->eno,(string)$deptid));		
  	    	 if($ds==null || count($ds["dept2"]["rows"])==0 && $deptid!="v".$user->eno)
  	    	 {
	  	    	 	$response = new Response(json_encode(array("s"=>0,
			                                                   "msg"=>"无效的部门信息")));
			        $response->headers->set('Content-Type', 'text/json');
	  	    	  return $response;
  	    	 }
  	    	 if($deptid=="v".$user->eno){
  	    	 	$fafa_deptid = $deptid;
  	    	 	$sqls_1=array();
  	    	 	$paras_1=array();
  	    	 	
  	    	 	$sqls_1[]="update we_enterprise_stored set eshortname=? where enoname=?";
  	    	 	$paras_1[]=array($deptname,$user->ename);
  	    	 	
  	    	 	$sqls_1[]="update we_enterprise set eshortname=? where eno=?";
  	    	 	$paras_1[]=array($deptname,$user->eno);
  	    	 	
  	    	 	$sqls_1[]="update we_micro_account set name=? where eno=? and locate('_weixin_',number)>0";
  	    	 	$paras_1[]=array($deptname,$user->eno);
  	    	 	$da->ExecSQLs($sqls_1,$paras_1);
  	    	 }
  	    	 else{
	  	    	 	$fafa_deptid = $ds["dept2"]["rows"][0]["fafa_deptid"];
	  	    	 //编辑部门名称
	  	    	 $sql = "update we_department set dept_name=? where dept_id=?";
	  	    	 $da->ExecSQL($sql,array((string)$deptname,(string)$deptid));
  	    	 }
  	    	 //同步IM库
  	    	 $sql_ims=array();
  	    	 $para_ims=array();
  	    	 $sql_ims[] = "update im_base_dept set deptname=? where deptid=? ";
  	    	 $para_ims[]=array((string)$deptname,(string)$fafa_deptid);
  	    	 
  	    	 $sql_ims[]="update rostergroups set grp=? where grp=?";
  	    	 $para_ims[]=array($deptname,$user->eshortname);
  	    	 $da_im->ExecSQLs($sql_ims,$para_ims);
  	    	 //重置IM数据版本
    	     $sql_im = "delete from im_dept_version where us in(SELECT loginname FROM we_im.im_employee a, im_base_dept b where a.deptid=b.deptid and b.path like ? )";
    	     $para_im = array();
    	     $para_im[] = "/-10000/v".$user->eno."/%";
    	     $da_im->ExecSQL($sql_im, $para_im);  	    	 
  	    }
  	    else{
		  	    $deptid = SysSeq::GetSeqNextValue($da,"we_department","dept_id");
			      $fafa_deptid = SysSeq::GetSeqNextValue($da_im,"im_base_dept","deptid");  
			      $sqls = "insert into we_department (eno,dept_id,dept_name,parent_dept_id,fafa_deptid,create_staff) values (?,?,?,?,?,?)";
			      $paras = array(
			          (string)$user->eno,
			          (string)$deptid,
			          (string)$deptname,
			          (string)$pid,	          
			          (string)$fafa_deptid,
			          (string)$user->getUserName()
			      );
			      $da->ExecSQL($sqls,$paras);
			      $sqls = "select fafa_deptid from we_department where eno=? and dept_id=?";
			      $ds = $da->GetData("dept2",$sqls,array((string)$user->eno,(string)$pid));		 

            $sql_im = "insert im_base_dept(deptid, deptname, pid, path, noorder, manager, remark) 
select ?, ?, deptid, concat(path, '".$fafa_deptid."/'), (select count(*)+1 from im_base_dept where pid=?) noorder, null, null 
from im_base_dept 
where deptid=? ";
    	      $para_im = array();
    	      $para_im[] = (string)$fafa_deptid;
    	      $para_im[] = (string)$deptname;
    	      $para_im[] = (string)(count($ds["dept2"]["rows"])>0? $ds["dept2"]["rows"][0]["fafa_deptid"] : $pid );
    	      $para_im[] = (string)(count($ds["dept2"]["rows"])>0? $ds["dept2"]["rows"][0]["fafa_deptid"] : $pid );    	      
    	      $da_im->ExecSQL($sql_im, $para_im);

    	      $sql_im= "insert into im_dept_stat(deptid,empcount) values(?,0)";
              $para_im = array(
                    (string)$fafa_deptid
               );
    	      $da_im->ExecSQL($sql_im, $para_im);
            
    	      $sql_im = "delete from im_dept_version where us in(SELECT loginname FROM we_im.im_employee a, im_base_dept b where a.deptid=b.deptid and b.path like ? )";
    	      $para_im = array();
    	      $para_im[] = "/-10000/v".$user->eno."/%";
    	      $da_im->ExecSQL($sql_im, $para_im);	 	
        }
		    $response = new Response(json_encode(array("s"=>1,
		                                                   "id"=> $deptid,
		                                                   "name"=> $deptname,
		                                                   "pId"=> $pid,
		                                                   "owner"=> $user->getUserName())));
		    $response->headers->set('Content-Type', 'text/json');  	    	
  	    return $response;        
  }
  
  public function deptdelAction($network_domain)
  {
  	    $deptid = $this->get("request")->get("deptid");
	  	  $user = $this->get('security.context')->getToken()->getUser();  
	  	  //判断部门下是否有人员
	  	  $isHasEmp = $this->checkDeptEmployee($deptid);
        if($isHasEmp)
  	    {
		        $response = new Response(json_encode(array("s"=>0)));
		        $response->headers->set('Content-Type', 'text/json');  	    	
  	    	  return $response;
  	    }	  	  
  	    else
  	    {
  	    	$da = $this->get("we_data_access");
  	      $sql = "select fafa_deptid from we_department where eno=? and dept_id=?";
			    $ds = $da->GetData("dept2",$sql,array((string)$user->eno,(string)$deptid));
			    if(count($ds["dept2"]["rows"])>0)
			    {
						    $fafa_deptid = $ds["dept2"]["rows"][0]["fafa_deptid"];
						    //优先删除当前指定部门。然后再删除其下级子部门
				        $da->ExecSQL("delete from we_department where eno=? and dept_id=?",array((string)$user->eno,(string)$deptid));
				        //从im库中查找其path路径，根据path快速获取其所有子部门
				        $da_im = $this->get('we_data_access_im');
				        $sql = "select path from im_base_dept where deptid=?";
				        $ds = $da_im->GetData("im_path",$sql,array((string)$fafa_deptid));
				        if(count( $ds["im_path"]["rows"])>0)
				        {
						        $dept_path = $ds["im_path"]["rows"][0]["path"];
						        $sql = "select deptid from im_base_dept where path like concat(?,'%')";
						        $ds = $da_im->GetData("imdeptbypath",$sql,array((string)$dept_path));
						        
						        //从IM库中删除部门及子部门的人员数统计数据
						        $sql_im = "delete from im_dept_stat where deptid in (select deptid from im_base_dept where path like concat(?,'%'))";
					  	      $para_im = array();
					  	      $para_im[] = (string)$dept_path;
					  	      $da_im->ExecSQL($sql_im, $para_im);		
						        
						        //从IM库中删除部门及子部门
						        $sql_im = "delete from im_base_dept where path like concat(?,'%')";
					  	      $para_im = array();
					  	      $para_im[] = (string)$dept_path;
					  	      $da_im->ExecSQL($sql_im, $para_im);	
				  	      
					          //删除版本信息    	    
					    	    $sql_im = "delete from im_dept_version where us in(SELECT loginname FROM we_im.im_employee a, im_base_dept b where a.deptid=b.deptid and b.path like ? )";
					    	    $para_im = array();
					    	    $para_im[] = "/-10000/v".$user->eno."/%";
					    	    $da_im->ExecSQL($sql_im, $para_im);	 
					    	    //从sns库中删除子部门
					    	    $sqls = array();
					    	    $paras=array();
					    	    for($i=0;$i<count($ds["imdeptbypath"]["rows"]);$i++)
					    	    {
					    	    	   $sqls[]= "delete from we_department where fafa_deptid=?";
					    	    	   $paras[] = array((string)$ds["imdeptbypath"]["rows"][$i]["deptid"]);
					    	    }
					    	    $da->ExecSQLs($sqls, $paras);	
			    	  	}
    	    } 
		      $response = new Response(json_encode(array("s"=>1)));
		      $response->headers->set('Content-Type', 'text/json');  	    	
  	    	return $response;
  	    }
  }
  
  
  /*  
  //我的等级
  public function mylevelAction($network_domain) 
  {
    $user = $this->get('security.context')->getToken()->getUser();
    $cpbi = new CPerBaseInfoController();
    
    $list["curr_network_domain"]=$network_domain;
    $list["ExprienceAround"] = $cpbi->getExprienceAround($user->total_point);    
    //计算昨日得分
    $sql = "select a.point_type, sum(a.point) sum_point, b.name
from we_staff_points a, we_code_all_code b
where a.point_type = b.id and b.class<>b.id and b.class='积分类别'
  and a.login_account=?
  and a.point_date>=date_add(CURDATE(), interval -1 day) and a.point_date<CURDATE()
group by a.point_type
order by a.point_type";
    $params = array();
    $params[] = $user->getUserName();
    
    $da = $this->get("we_data_access");
    $ds = $da->GetData("we_staff_points_yesterday", $sql, $params);
          
    $list["ds"] = $ds;
    
    $sum_points = 0;
    for($i = 0; $i<count($ds["we_staff_points_yesterday"]["rows"]); $i++)
    {
      $sum_points += $ds["we_staff_points_yesterday"]["rows"][$i]["sum_point"];
    }
    $list["yesterday_points"] = $sum_points;
    
    //取得前10位
    $sql = "select a.login_account, a.nick_name, a.total_point, a.photo_path
from we_staff a
where a.eno=?
order by a.total_point desc
limit 0, 10";
    $params = array();
    $params[] = $user->eno; 
    
    $da = $this->get("we_data_access");
    $ds1 = $da->GetData("we_staff_points_top10", $sql, $params);
    $list["ds"]["we_staff_points_top10"] = $ds1["we_staff_points_top10"];
    $list["this"] = $this;
    
    return $this->render("JustsyBaseBundle:Account:mylevel.html.twig",$list);
  }
  */
   
  public function mylevelAction($network_domain)
  {
  	 $user = $this->get('security.context')->getToken()->getUser();
  	 $cpbi = new CPerBaseInfoController();
  	 $curent_point = $user->total_point;
  	 
  	 $total_point = $cpbi->getExprienceAround($curent_point);
  	 $total_point = $total_point["NextLevelExperience"];
  	 $point = $total_point - $curent_point;
     $list["curr_network_domain"]=$network_domain;
     $list["level_point"] = ceil($point);
          
     if (count($user->role_codes)==0)
     {
     	 $list["cur_level"] = "J1";
     	 $list["next_level"] = "V1";
       $list["level_1"] = "J1";
       $list["level_2"] = "V1";
       $list["level_3"] = "V2";
       $list["level_4"] = "S1";
     }
     else
     {
     	 $list["cur_level"] = $user->role_codes[0];
     	 if ($list!="S1")
     	 {
	       $result =  $this ->getLevelStrng( $list["cur_level"]);
	       $list["level_1"] = $result["level_1"];
	       $list["level_2"] = $result["level_2"];
	       $list["level_3"] = $result["level_3"];
	       if($list["cur_level"]==$list["level_1"])
	         $list["next_level"] = $list["level_2"];
	       else if ($list["cur_level"]==$list["level_2"])
	         $list["next_level"] = $list["level_3"];
	       else if ($list["cur_level"]==$list["level_3"])
	         $list["next_level"] = "S1";
       }
     }
     $list["level_4"] = "S1";
  	 return $this->render("JustsyBaseBundle:Account:mylevel.html.twig",$list);
  }
  
  //获得用户权限
  public function LevelDescAction(Request $request)
  {
  	  $user = $this->get('security.context')->getToken()->getUser();
  	  $da = $this->get("we_data_access");
  	  $result = array(); 
  	  //用户当前权限
  	  $current_level = ""; 
     if (count($user->role_codes)==0)
       $current_level = "J1";
     else
       $current_level = $user->role_codes[0];      
      $staffrole = new \Justsy\BaseBundle\Rbac\StaffRole($da,$this->get("we_data_access_im"),$user);
      
      if ( $current_level=="J1")
      {
      	 $result["level1"] = $staffrole->getFunctionCodes($current_level);
      	 $result["level2"] = $staffrole->getFunctionCodes("V1");
      	 $result["level3"] = $staffrole->getFunctionCodes("V2");
      }
      else
      {
      	 if ( $current_level!="S1")
      	 {
      	 	  $levelstring =  $this ->getLevelStrng( $current_level);
      	    $result["level1"] = $staffrole->getFunctionCodes( $levelstring["level_1"]);
      	    $result["level2"] = $staffrole->getFunctionCodes( $levelstring["level_2"]);
      	    $result["level3"] = $staffrole->getFunctionCodes( $levelstring["level_3"]);
      	}
      }
      $result["level4"] = $staffrole->getFunctionCodes( "S1" );
      $response = new Response(json_encode($result));
		  $response->headers->set('Content-Type', 'text/json');  	    	
  	  return $response;
  }
  
  //根据当前级计算前后级别
  private function getLevelStrng($level)
  {
  	   $list = array();
  	   if ($level=="V9" || $level=="V10" || $level == "S1")
  	     $level = "V8"; 
  	   $list["level_1"] = $level;
       if ($level=="J1")
       {
          $list["level_2"] = "V1";
          $list["level_3"] = "V2";
       }
       else
       {
       	 $number = substr($level,-1);
         $list["level_2"]=substr($level,0,1).($number+1);
         $list["level_3"]=substr($level,0,1).($number+2);
       }
       return $list;
  }
  
  public function calcLevel($exprience) 
  {
    return \Justsy\BaseBundle\Common\ExperienceLevel::getLevel($exprience);
  }
  
  //等级规则
  public function levelruleAction($network_domain) 
  {
    $list["curr_network_domain"]=$network_domain;
    $list["ExperienceLevels"] = \Justsy\BaseBundle\Common\ExperienceLevel::getExperienceLevels();
    return $this->render("JustsyBaseBundle:Account:levelrule.html.twig",$list);
  } 
  
  public function myPointsHisAction($network_domain)
  {
    $user = $this->get('security.context')->getToken()->getUser();
    $request = $this->getRequest();
    $list["curr_network_domain"]=$network_domain;
    
    $pagesize = 15;
    $pageindex = $request->get('pageindex');
    if ($pageindex){}else{ $pageindex = 1; }
    
    $sql = "select a.point_date, a.point_type, a.point_desc, a.point
from we_staff_points a
where a.login_account=?
order by a.point_date desc";
    $params = array();
    $params[] = $user->getUserName();
    
    $da = $this->get("we_data_access");
    $da->PageIndex = $pageindex - 1;
    $da->PageSize = $pagesize;
    $ds = $da->GetData("we_staff_points_his", $sql, $params);
    
    $list["ds"] = $ds;
    $list["pageindex"] = $pageindex;
    $list["pagecount"] = ceil($ds["we_staff_points_his"]["recordcount"]/($pagesize));
    
    return $this->render("JustsyBaseBundle:Account:mypointshis.html.twig",$list);
  }
  
  //手机绑定
  public function mobilebindAction($network_domain) 
  {    
    $user = $this->get('security.context')->getToken()->getUser();
    $request = $this->getRequest();
    //$mobile=$request->get('m');

    $sql = "select mobile, mobile_bind
from we_staff a
where a.login_account=?";
    $params = array();
    $params[] = $user->getUserName();
    
    $da = $this->get("we_data_access");
    $ds = $da->GetData("we_staff", $sql, $params);
    
    $a = array();
    $a["curr_network_domain"]=$network_domain;
    $a["ds"] = $ds;
    //$pattern=$this->checkmobile();
    //if(!preg_match($pattern,$mobile)) $a['mobile']=$mobile;
    //else $a['mobile']='';
    
    return $this->render("JustsyBaseBundle:Account:mobilebind.html.twig", $a);
  } 
  private function checkmobile(){
      return "/^13[0-9]{9}$|15[0-9]{9}$|18[0-9]{9}$|19[0-9]{9}$/";
  }
  public function mobilebindforpcAction() 
  {
    $user = $this->get('security.context')->getToken()->getUser();
    $request = $this->getRequest();
    
    $txtmobile = $request->get("no");
    $sql = "select mobile, mobile_bind
from we_staff a
where a.login_account=?";
    $params = array();
    $params[] = $user->getUserName();
    
    $da = $this->get("we_data_access");
    $ds = $da->GetData("we_staff", $sql, $params);
    
    $a = array();
    $a["ds"] = $ds;
    $a["mobile"]=$txtmobile;
    return $this->render("JustsyBaseBundle:Account:mobilebindforpc.html.twig", $a);  	
  }
  public function savemobilebindAction($network_domain) 
  {    
    $re = array();
    $user = $this->get('security.context')->getToken()->getUser();
    $request = $this->getRequest();
    
    $txtmobile = $request->get("txtmobile");
    $txtvaildcode = $request->get("txtvaildcode");
    
    if (empty($txtmobile))
    {
      $re["success"] = "0";  
      $re["msg"] = "请输入正确的手机号！";  
      
		  $response = new Response(json_encode($re));
		  $response->headers->set('Content-Type', 'text/json');
		  return $response; 
    }
    if($txtmobile != $request->getSession()->get("txtmobile"))
    {
    	$re["success"] = "0";  
      $re["msg"] = "两次手机号输入不一致！";  
      
		  $response = new Response(json_encode($re));
		  $response->headers->set('Content-Type', 'text/json');
		  return $response;
    }
    if (empty($txtvaildcode) || $txtvaildcode != $request->getSession()->get("mobilevaildcode"))
    {
      $re["success"] = "0";  
      $re["msg"] = "请输入正确的验证码！";  
      
		  $response = new Response(json_encode($re));
		  $response->headers->set('Content-Type', 'text/json');
		  return $response; 
    }
    try
    {
      $sqls = array();
      $all_params = array();
        
      $sql = "update we_staff set mobile_bind=null where mobile_bind=?";
      $params = array();
      $params[] = $txtmobile;
              
      $sqls[] = $sql;
      $all_params[] = $params;
        
      $sql = "update we_staff set mobile=?, mobile_bind=? where login_account=?";
      $params = array();
      $params[] = $txtmobile;
      $params[] = $txtmobile;
      $params[] = $user->getUserName();
              
      $sqls[] = $sql;
      $all_params[] = $params;    
      
      $da = $this->get("we_data_access");
      $da->ExecSQLs($sqls, $all_params); 
      
      //发送手机绑定通知
      try{
	      $noticeMsg = array();
	      $noticeMsg["login_account"]=$user->fafa_jid;
	      $noticeMsg["nick_name"]=$user->nick_name;
	      $noticeMsg["mobile_bind"]="1";
	      $noticeMsg["mobile"]=$txtmobile;
		    $message = json_encode($noticeMsg);
		    $staffMgr=new \Justsy\BaseBundle\Management\Staff($da,$this->get("we_data_access_im"),$user);
		    $recv=$staffMgr->getFriendAndColleagueJid();
	    	array_push($recv,$user->fafa_jid);
		    Utils::sendImPresence("",implode(",",$recv),"mobile_bind",$message,$this->container,"","",false,Utils::$systemmessage_code);      
	    }
	    catch (\Exception $e) {
	        $this->get("logger")->err($e);	
	    }
      $re["success"] = "1";  
    } 
    catch (\Exception $e) 
    {
      $re["success"] = "0";  
      $re["msg"] = "绑定手机号失败！请重试";  
      $this->get('logger')->err($e);
    }
    
    $response = new Response(json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response; 
  } 
  
  public function mobileunbindAction($network_domain) 
  {  
    $user = $this->get('security.context')->getToken()->getUser();
    
    $sql = "select mobile, mobile_bind
from we_staff a
where a.login_account=?";
    $params = array();
    $params[] = $user->getUserName();
    
    $da = $this->get("we_data_access");
    $ds = $da->GetData("we_staff", $sql, $params);
    
    $a = array();
    $a["curr_network_domain"]=$network_domain;
    $a["ds"] = $ds;
    
    return $this->render("JustsyBaseBundle:Account:mobileunbind.html.twig", $a);
  } 
  public function savemobileunbindAction($network_domain) 
  {  
    $re = array();
    $user = $this->get('security.context')->getToken()->getUser();
    
    try
    {
      $sqls = array();
      $all_params = array();
        
      $sql = "update we_staff set mobile_bind=null where login_account=?";
      $params = array();
      $params[] = $user->getUserName();
              
      $sqls[] = $sql;
      $all_params[] = $params;
      
      $da = $this->get("we_data_access");
      $da->ExecSQLs($sqls, $all_params); 
      //发送手机解除绑定通知
      try{
      $noticeMsg = array();
      $noticeMsg["login_account"]=$user->fafa_jid;
      $noticeMsg["nick_name"]=$user->nick_name;
      $noticeMsg["mobile_bind"]="0";
	    $message = json_encode($noticeMsg);
	    $staffMgr=new \Justsy\BaseBundle\Management\Staff($da,$this->get("we_data_access_im"),$user);
	    $recv=$staffMgr->getFriendAndColleagueJid();
	    array_push($recv,$user->fafa_jid);
	    Utils::sendImPresence("",implode(",",$recv),"mobile_bind",$message,$this->container,"","",false,Utils::$systemmessage_code);     
	    }
	    catch (\Exception $e) {
	        $this->get("logger")->err($e);	
	    }      
      $re["success"] = "1";  
    } 
    catch (\Exception $e) 
    {
      $re["success"] = "0";  
      $re["msg"] = "取消绑定失败！请重试";  
      $this->get('logger')->err($e);
    }
    
    $response = new Response(json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response; 
  }   
  public function getmobilevaildcodeAction($network_domain) 
  {  
    $re = array();
    $da = $this->get("we_data_access");
    $user = $this->get('security.context')->getToken()->getUser();    
    $request = $this->getRequest();
    $session = $request->getSession();
    
    $txtmobile = $request->get("txtmobile");
    
    if (empty($txtmobile) || !preg_match("/^(1[8|3|5][0-9]|15[0|3|6|7|8|9]|18[2|5|6|8|9])\d{8}$/",$txtmobile))
    {
      $re["success"] = "0";  
      $re["msg"] = "请输入正确的手机号！";  
      
		  $response = new Response(json_encode($re));
		  $response->headers->set('Content-Type', 'text/json');
		  return $response; 
    }
    //判断是否已绑定手机号
    /*
    if(!empty($user->mobile_bind))
    {
    	$re["success"] = "0";  
	      $re["msg"] = "您已绑定了手机号，请先解除原有绑定！";  
	      
			  $response = new Response(json_encode($re));
			  $response->headers->set('Content-Type', 'text/json');
			  return $response; 
    }
    */
    //判断此手机已被绑定
    $sql="select 1 from we_staff where mobile_bind=?";
    $params=array($txtmobile);
    $ds=$da->Getdata('lo',$sql,$params);
    if($ds['lo']['recordcount'] > 0)
    {
    	$re["success"] = "0";  
	      $re["msg"] = "该手机号已被绑定！";  
	      
			  $response = new Response(json_encode($re));
			  $response->headers->set('Content-Type', 'text/json');
			  return $response; 
    }
    $lastgetmobilevaildcodetime = $session->get("lastgetmobilevaildcodetime");
    $getmobilevaildcodenums = $session->get("getmobilevaildcodenums");
    
    if (empty($lastgetmobilevaildcodetime)) $lastgetmobilevaildcodetime = time()-60*60;
    if (empty($getmobilevaildcodenums)) $getmobilevaildcodenums = 0;    
    
    try
    {
      if ($lastgetmobilevaildcodetime + 120 > time()) //2分钟只能取一次
      {
        $re["success"] = "0";  
        $re["msg"] = "你获取验证码的次数太频繁！120秒钟只能取一次!";  
        
  		  $response = new Response(json_encode($re));
  		  $response->headers->set('Content-Type', 'text/json');
  		  return $response; 
      }
      if ($getmobilevaildcodenums >= 5 && $lastgetmobilevaildcodetime + 60*60*24 > time()) //最多三次
      {
        $re["success"] = "0";  
        $re["msg"] = "你获取验证码的次数太多！每天最多只能取5次!";  
        
  		  $response = new Response(json_encode($re));
  		  $response->headers->set('Content-Type', 'text/json');
  		  return $response; 
      }
      
      $mobilevaildcode = rand(100000, 999999);
      
      $user = $this->container->getParameter("SMS_ACT");
      $pass  = md5($this->container->getParameter("SMS_PWD"));//需要MD5
      $phone  = $txtmobile;
      $content = "验证码：".$mobilevaildcode."，2分钟内有效，仅用于绑定手机操作。 【Wefafa】";
      $content = urlEncode(urlEncode(mb_convert_encoding($content, 'gb2312' ,'utf-8')));
      $apidata="func=sendsms&username=$user&password=$pass&mobiles=$phone&message=$content&smstype=0&timerflag=0&timervalue=&timertype=0&timerid=0";
      $apiurl = $this->container->getParameter("SMS_URL");
      $ret = $this->do_post_request($apiurl,$apidata);
      if(strpos($ret,"<errorcode>0</errorcode>")>0)
      {      
        $session->set("mobilevaildcode", $mobilevaildcode);
        $session->set("lastgetmobilevaildcodetime", time());
        $session->set("getmobilevaildcodenums", $getmobilevaildcodenums+1);
        $session->set("txtmobile",$txtmobile);
        
        $re["success"] = "1";  
        //$re["code"] =$mobilevaildcode;
      }
      else
      {
        $re["success"] = "0";  
        $re["msg"] = "短信发送失败！请稍后重试"; 
        $this->get('logger')->info($ret);
      }
    } 
    catch (\Exception $e) 
    {
      $re["success"] = "0";  
      $re["msg"] = "获取并发送短信验证码失败！请稍后重试";  
      $this->get('logger')->err($e);
    }
    
    $response = new Response(json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response; 
  } 
  

  //专门为第三方应用进行手机绑定提供的接口：绑定。不需要登录
  //验证码获取采用/interface/validcode?type=MB获取
  public function savemobilebind2Action($user) 
  {
    $re = array(); 
    $request = $this->getRequest();
    
    $txtmobile = $request->get("txtmobile");
    $txtvaildcode = $request->get("txtvaildcode");
    $da = $this->get("we_data_access");
    if (empty($txtmobile) || !Utils::validateMobile($txtmobile))
    {
      $re["success"] = "0";  
      $re["msg"] = "请输入正确的手机号！";  
      
		  $response = new Response(json_encode($re));
		  $response->headers->set('Content-Type', 'text/json');
		  return $response; 
    }
    //根据帐号和手机号获取验证码
    $sql = "select * from we_mobilebind_validcode where login_account=? and mobileno=? and actiontype='MB' order by req_date desc limit 0,1";
    $ds = $da->GetData("t",$sql,array((string)$user["login_account"],(string)$txtmobile));
    if(empty($ds) || count($ds["t"]["rows"])==0)
    {
	      $re["success"] = "0";
	      $re["msg"] = "请输入获取验证码时的手机号！";
			  $response = new Response(json_encode($re));
			  $response->headers->set('Content-Type', 'text/json');
			  return $response;
    }
    if (empty($txtvaildcode) || $txtvaildcode != $ds["t"]["rows"][0]["validcode"])
    {
      $re["success"] = "0";  
      $re["msg"] = "请输入正确的验证码！";        
		  $response = new Response(json_encode($re));
		  $response->headers->set('Content-Type', 'text/json');
		  return $response; 
    }
    try
    {
      $sqls = array();
      $all_params = array();
        
      $sql = "update we_staff set mobile_bind=null where mobile_bind=?";
      $params = array();
      $params[] = $txtmobile;
              
      $sqls[] = $sql;
      $all_params[] = $params;
        
      $sql = "update we_staff set mobile=?, mobile_bind=? where login_account=?";
      $params = array();
      $params[] = $txtmobile;
      $params[] = $txtmobile;
      $params[] = $user["login_account"];
              
      $sqls[] = $sql;
      $all_params[] = $params;    
      
      
      $da->ExecSQLs($sqls, $all_params); 
      
      //发送手机绑定通知
      try{
	      $noticeMsg = array();
	      $noticeMsg["login_account"]=$user["login_account"];
	      $noticeMsg["nick_name"]=$user["nick_name"];
	      $noticeMsg["mobile_bind"]="1";
		    $message = json_encode($noticeMsg);
		    $staffMgr=new \Justsy\BaseBundle\Management\Staff($da,$this->get("we_data_access_im"),$user["login_account"]);
		    Utils::sendImPresence("",implode(",", $staffMgr->getFriendAndColleagueJid()),"mobile_bind",$message,$this->container,"","",false,Utils::$systemmessage_code);      
	    }
	    catch (\Exception $e) {
	        $this->get("logger")->err($e);	
	    }
      $re["success"] = "1";  
    } 
    catch (\Exception $e) 
    {
      $re["success"] = "0";  
      $re["msg"] = "绑定手机号失败！请重试";  
      $this->get('logger')->err($e);
    }
    
    $response = new Response(json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;   	
  }
  //专门为第三方应用进行手机绑定提供的接口：解绑。不需要登录
  public function savemobileunbind2Action($user) 
  {
    $re = array();
    try
    {
      $sqls = array();
      $all_params = array();
        
      $sql = "update we_staff set mobile_bind=null where login_account=?";
      $params = array();
      $params[] = $user["login_account"];
              
      $sqls[] = $sql;
      $all_params[] = $params;
      
      $da = $this->get("we_data_access");
      $da->ExecSQLs($sqls, $all_params); 
      //发送手机解除绑定通知
      try{
      $noticeMsg = array();
      $noticeMsg["login_account"]=$user["login_account"];
      $noticeMsg["nick_name"]=$user["nick_name"];
      $noticeMsg["mobile_bind"]="0";
	    $message = json_encode($noticeMsg);
	    $staffMgr=new \Justsy\BaseBundle\Management\Staff($da,$this->get("we_data_access_im"),$user["login_account"]);
	    Utils::sendImPresence("",implode(",", $staffMgr->getFriendAndColleagueJid()),"mobile_bind",$message,$this->container,"","",false,Utils::$systemmessage_code);      
	    }
	    catch (\Exception $e) {
	        $this->get("logger")->err($e);	
	    }      
      $re["success"] = "1";  
    } 
    catch (\Exception $e) 
    {
      $re["success"] = "0";  
      $re["msg"] = "取消绑定失败！请重试";  
      $this->get('logger')->err($e);
    }
    
    $response = new Response(json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;   	
  }
  
  //功能：发送短信
  //使用GB2312编码  
  function do_post_request($url, $data, $optional_headers = null)
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

public function updateLastLoginAction()
{
	$sql = "update we_staff set prev_login_date=now() where login_account=?";
	$user = $this->get('security.context')->getToken()->getUser();
	$da = $this->get("we_data_access");
  $da->ExecSQL($sql, array((string)$user->getUserName())); 
	$response = new Response("1");
	return $response;
}

//保存用户的个人描述/个性签名
public function saveselfdescAction()
{
    $res = $this->get("request");
  	$auth = $res->get("authcode"); 
  	$openid= $res->get("p"); 
  	$desc= $res->get("desc");
	  if($auth==null || $auth=="")
	  {
			$response = new Response("{\"s\":0,\"msg\":\"authcode is null\"}");
			return $response;	  	
	  }
	  try{	  	
	      $auth = trim(DES::decrypt($auth));
	      //解密参数串
	      $openid =  trim(DES::decrypt($openid));
	      $desc =  trim(DES::decrypt($desc));
	      //授权码已过期
	      $lng = time()-(int)$auth;
	      if($lng>30 || $lng<0) 
	      {
					$response = new Response("{\"s\":0,\"msg\":\"authcode date out!\"}");
					return $response;
	      }
	      if(empty($openid))
	      {
					$response = new Response("{\"s\":0,\"msg\":\"openid is null\"}");
					return $response;	      	
	      }
  	    $sql = "update  we_staff set self_desc=? where openid=?";
        $da = $this->get("we_data_access");
        $da->ExecSQL($sql, array((string)$desc,(string)$openid));
        $staffMgr = new \Justsy\BaseBundle\Management\Staff($da,null,$openid,$this->get('logger'));
        $staffinfo=$staffMgr->getInfo();
        if($staffinfo!=null)
        {
            $friendevent=new \Justsy\BaseBundle\Management\FriendEvent($da,$this->get('logger'),$this->container);
            $friendevent->signchange($staffinfo["login_account"],$staffinfo["nick_name"],$desc);
        }
        //$this->get('session')->migrate();
				$response = new Response("{\"s\":1,\"msg\":\"\"}");
				return $response;              
	  }
	  catch(\Exception $e)
  	{
  		    $this->get("logger")->err($e);
					$response = new Response("{\"s\":0,\"msg\":\"exception\"}");
					return $response;	
  	}  	
}

  //保存头像数据
  public function savePhotoAction(Request $request)
  {
  	 try
     {
     $session = $this->get('session'); 
     $filename120 = $session->get("avatar_big");
     $filename48 = $session->get("avatar_middle");
     $filename24 = $session->get("avatar_small");
     $user = $this->get('security.context')->getToken()->getUser();
     $dm = $this->get('doctrine.odm.mongodb.document_manager');
     if (!empty($filename120)) $filename120= $this->saveFile($filename120,$dm);
     if (!empty($filename48)) $filename48=   $this->saveFile($filename48,$dm);
     if (!empty($filename24)) $filename24=   $this->saveFile($filename24,$dm);
     $session->remove("avatar_big");
     $session->remove("avatar_middle");
     $session->remove("avatar_small");      
     $da = $this->get("we_data_access");
     $para["account"] = $user->getUsername();

     $table = $da->GetData("staff","select photo_path,photo_path_small,photo_path_big,fafa_jid from we_staff where login_account=?",array((String)$para["account"]));
     //$Jid = $table["staff"]["rows"][0]["fafa_jid"];
     if (!empty($filename120))
     {
       if ($table && $table["staff"]["recordcount"] >0 )  //如果用户原来有头像则删除
       {
         $this->removeFile($table["staff"]["rows"][0]["photo_path"],$dm);
         $this->removeFile($table["staff"]["rows"][0]["photo_path_small"],$dm);
         $this->removeFile($table["staff"]["rows"][0]["photo_path_big"],$dm);
       }
     }    
     
        $sql = "update we_staff set photo_path=?,photo_path_small=?,photo_path_big=? where login_account=?";
        $paras[]= $filename48;
        $paras[]= $filename24;
        $paras[]= $filename120;        
        $paras[]= $para["account"];
    
        $para["path"] = $this->container->getParameter('FILE_WEBSERVER_URL').$filename120;
        $da->ExecSQL($sql,$paras);        
        
        if ( empty($filename120))
        {
        	if ($table && $table["staff"]["recordcount"] >0 )
             $para["path"] = $this->container->getParameter('FILE_WEBSERVER_URL').$table["staff"]["rows"][0]["photo_path_big"];
          else
             $para["path"]=$this->get('templating.helper.assets')->geturl('bundles/fafatimewebase/images/no_photo.png');
        }
        else
        {
          $para["path"] = $this->container->getParameter('FILE_WEBSERVER_URL').$filename120;
        	$friendevent=new \Justsy\BaseBundle\Management\FriendEvent($da,$this->get('logger'),$this->container);
        	$friendevent->photochange($user->getUserName(),$user->nick_name);
        }
        //发送个人资料编辑通知
        //发送即时消息
	      $message = "{\"path\":\"".$para["path"]."\"}";
	      $staffMgr=new \Justsy\BaseBundle\Management\Staff($da,$this->get("we_data_access_im"),$user);
	      Utils::sendImPresence($user->fafa_jid,implode(",", $staffMgr->getFriendAndColleagueJid()),"staff-changeinfo",$message,$this->container,"","",false,Utils::$systemmessage_code);        
			  $response = new Response(("{\"succeed\":1,\"path\":\"".$para["path"]."\"}"));
			  $response->headers->set('Content-Type', 'text/json');
			  return $response;        
     }
     catch(\Exception $e)
     {
     	 $this->get("logger")->err($e);
       $response = new Response(("{\"succeed\":0,\"e\":$e}"));
			 $response->headers->set('Content-Type', 'text/json');
			 return $response;
     }
  }  
  
//获取个人资料页面模板-可编辑的。专门为PC端提供
public function photoEditForPcAction()
{
  	return $this->render('JustsyBaseBundle:Account:photo_edit_pc.html.twig');
}
//获取个人资料页面模板-只读的。专门为PC端提供
public function pcSyncTemplateReadonlyAction()
{
  	return $this->render('JustsyBaseBundle:Account:pcsync_template_readonly.html.twig');
}
//编辑个人资料。专门为PC端提供
public function pcSyncAction()
{
  	$res = $this->get("request");
  	$auth = $res->get("authcode"); 
  	$interviewee= $res->get("interviewee"); 
  	//$paras =  explode(",", trim(DES::decrypt($interviewee)));
  	
  		
	  if($auth==null || $auth==""){
	  	 $this->get("logger")->err("=====pcSyncAction Error：authcode为空！");
	  	 return $this->render('JustsyBaseBundle:Account:pcsync_error.html.twig');//$this->redirect($this->generateUrl('JustsyBaseBundle_login'));
	  }
	  try{	  	
	      $auth = trim(DES::decrypt($auth));
	      //解密参数串
	      $paras =  explode(",", trim(DES::decrypt($interviewee)));
	      //授权码已过期
	      $lng = time()-(int)$auth;
	      if($lng>30 || $lng<0)
	      {
	      	 $this->get("logger")->err("=====pcSyncAction Error：授权码已过期！");
	      	 return $this->render('JustsyBaseBundle:Account:pcsync_error.html.twig');//$this->redirect($this->generateUrl('JustsyBaseBundle_login'));
	      }
	  }
	  catch(\Exception $e)
  	{
  		$this->get("logger")->err($e);
  		return $this->render('JustsyBaseBundle:Account:pcsync_error.html.twig');//$this->redirect($this->generateUrl('JustsyBaseBundle_login'));
  	}    
	  try
	  {
      if(count($paras)!=2 && count($paras)!=1)
      {
      	 $this->get("logger")->err("=====pcSyncAction Error：参数$paras不正确！");
      	 return $this->render('JustsyBaseBundle:Account:pcsync_error.html.twig');//$this->redirect($this->generateUrl('JustsyBaseBundle_login'));
      }
      $ec = new \Justsy\BaseBundle\Controller\PersonalHomeController();
      $ec->setContainer($this->container);

	  	//通过openID获取用户信息
  	  $user = $ec->loadUserByUsername($paras[0]);  
  	   
  	  if($user==null){
  	   $this->get("logger")->err("=====pcSyncAction Error：$paras用户信息未找到！");
  	   return $this->render('JustsyBaseBundle:Account:pcsync_error.html.twig');//$this->redirect($this->generateUrl('JustsyBaseBundle_login')); 
  	  }
      $network_domain = $user->edomain; 
  	  //登记seesion
  	  $token = new UsernamePasswordToken($user, $user->getPassword(), "secured_area", $user->getRoles());
  	  $this->get("security.context")->setToken($token);
  	  $session = $res->getSession()->set('_security_'.'secured_area',  serialize($token));
  	  $event = new InteractiveLoginEvent($this->get("request"), $token);
  	  $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);        
      $account = ""; 	   
  	  if(count($paras)==1)
  	  {
  	      $photo_url = $this->container->getParameter('FILE_WEBSERVER_URL').$user->photo_path_big; 
  	      $account = 	$user->getUsername();
			    $list["account"] = $account;
			    $list["name"] = $user->nick_name;
			    $list["deptid"] = $user->dept_id;
			    $list["deptname"] = $user->dept_name;
			    $list["birthday"] = $user->birthday;
			    $list["year"]  = date('Y',strtotime($user->birthday));
			    $list["month"] = date('n',strtotime($user->birthday));
			    $list["day"]   = date('j',strtotime($user->birthday));
			    $list["birthday"] = $list["year"]."年".$list["month"]."月".$list["day"]."日";
			    $list["work_phone"] = $user->work_phone;
			    $list["mobile"] = $user->mobile;
			    $list["duty"] = $user->duty;
			    $list["sex_id"] = empty($user->sex_id)?"":$user->sex_id;
			    $list["isself"] = "1";
  	  }
  	  else
  	  {
  	  	  $user = $ec->getUserInfo($paras[1]);
  	      $user = $user["we_staff"]["rows"][0]; 
  	      $photo_url = $this->container->getParameter('FILE_WEBSERVER_URL').$user["photo_path_big"]; 
  	      $account = 	$user["login_account"]; 
			    $list["account"] = $account;
			    $list["name"] = $user["nick_name"];
			    $list["deptid"] = $user["dept_id"];
			    $list["deptname"] = $user["dept_name"];
			    $list["birthday"] = $user["birthday"];
			    $list["year"]  = date('Y',strtotime($list["birthday"]));
			    $list["month"] = date('n',strtotime($list["birthday"]));
			    $list["day"]   = date('j',strtotime($list["birthday"]));
			    $list["birthday"] = $list["year"]."年".$list["month"]."月".$list["day"]."日";
			    $list["work_phone"] = $user["work_phone"];
			    $list["mobile"] = $user["mobile"];
          $list["duty"] = $user["duty"];
          $list["sex_id"] = empty($user["sex_id"])?"":$user["sex_id"];
          $list["isself"] = "0";
  	  }
  	  $sql = "select a.mobile, a.mobile_bind,a.hometown,a.graduated,a.work_his,a.self_desc,a.specialty,a.hobby from we_staff a where a.login_account=?";
      $params = array();
      $params[] = $account;
    
      $da = $this->get("we_data_access");
      $ds = $da->GetData("we_staff_mobile", $sql, $params);
      $ds = $ds["we_staff_mobile"]["rows"][0];
      $list["mobile_bind"]=$ds["mobile_bind"];
      $list["self_desc"] = $ds["self_desc"];
			$list["hometown"] = $ds["hometown"];
			$list["graduated"] = $ds["graduated"];
			$list["work_his"] = $ds["work_his"];
			$list["specialty"] = $ds["specialty"];
			$list["hobby"] = $ds["hobby"];
      $list["direct_manages"]="";
      $list["report_object"]="";
      $list["path"] = $photo_url;
      $list["msg"]=null;
      $list["curr_network_domain"]=$network_domain;
      
      $perBase = new \Justsy\BaseBundle\Controller\CPerBaseInfoController();
      $perBase->setContainer($this->container);
      $list["InfoCompletePercent"]   = $perBase->GetInfoCompletePercent($account);      
      
      return $this->render('JustsyBaseBundle:Account:pcsync.html.twig',$list);
    }
  	catch(\Exception $e)
  	{
  		$this->get("logger")->err($e);
      return $this->render('JustsyBaseBundle:Account:pcsync_error.html.twig');//$this->redirect($this->generateUrl('JustsyBaseBundle_login'));
  	}  	
}


  //保存数据
  public function savePcSyncAction(Request $request)
  {
     $session = $this->get('session'); 
     $filename120 = $session->get("avatar_big");
     $filename48 = $session->get("avatar_middle");
     $filename24 = $session->get("avatar_small");
     $user = $this->get('security.context')->getToken()->getUser();
     $dm = $this->get('doctrine.odm.mongodb.document_manager');
     if (!empty($filename120)) $filename120= $this->saveFile($filename120,$dm);
     if (!empty($filename48)) $filename48=   $this->saveFile($filename48,$dm);
     if (!empty($filename24)) $filename24=   $this->saveFile($filename24,$dm);
     $session->remove("avatar_big");
     $session->remove("avatar_middle");
     $session->remove("avatar_small");
        
     $da = $this->get("we_data_access");
     $da_im = $this->get('we_data_access_im');
     $para["account"] = $user->getUsername();

     $table = $da->GetData("staff","select nick_name,photo_path,photo_path_small,photo_path_big,fafa_jid from we_staff where login_account=?",array((String)$para["account"]));
     $oldRow = $table["staff"]["rows"][0];     
     if (!empty($filename120))
     {
       if ($table && $table["staff"]["recordcount"] >0 )  //如果用户原来有头像则删除
       {
         $this->removeFile($table["staff"]["rows"][0]["photo_path"],$dm);
         $this->removeFile($table["staff"]["rows"][0]["photo_path_small"],$dm);
         $this->removeFile($table["staff"]["rows"][0]["photo_path_big"],$dm);
       }
     }
     $old_nick_name = $oldRow["nick_name"];
     $Jid = $oldRow["fafa_jid"];
     $y = $request->get("dateYear");
     $birthday=(empty($y)||$y=="0000")? "": $y."-".$request->get("dateMonth")."-".$request->get("dateDay");
     $nick_name=$request->get("txtname");
     if (empty($filename120))
     {
        $sql = "update we_staff set nick_name=?,birthday=?,dept_id=?,work_phone=?,mobile=?,self_desc=?,specialty=?,hobby=?,hometown=?,graduated=?,work_his=?,sex_id=? where login_account=?";
        $paras[]= $nick_name;
        $paras[]= $birthday;
        $paras[] = $request->get("txtdeptid"); 
        $paras[]= $request->get("txtwork_phone");
        $paras[]= $request->get("txtmobile");
        $paras[]= $request->get("txtself_desc");
        $paras[]= $request->get("txtspecialty");
        $paras[]= $request->get("txthobby");
        $paras[]= $request->get("txthometown");
        $paras[]= $request->get("txtgraduated");
        $paras[]= $request->get("txtwork_his");
        $paras[]= $request->get("txtsex");
        $paras[]= $para["account"];
   
     }
     else
     {
        $sql = "update we_staff set nick_name=?,birthday=?,photo_path=?,photo_path_small=?,photo_path_big=?,dept_id=?,work_phone=?,mobile=?,self_desc=?,specialty=?,hobby=?,hometown=?,graduated=?,work_his=?,sex_id=?  where login_account=?";
        $paras[]= $nick_name;
        $paras[]= $birthday;
        $paras[]= $filename48;
        $paras[]= $filename24;
        $paras[]= $filename120;
        $paras[] = $request->get("txtdeptid");
        $paras[]= $request->get("txtwork_phone");
        $paras[]= $request->get("txtmobile");
        $paras[]= $request->get("txtself_desc"); 
        $paras[]= $request->get("txtspecialty");
        $paras[]= $request->get("txthobby");
        $paras[]= $request->get("txthometown");
        $paras[]= $request->get("txtgraduated");
        $paras[]= $request->get("txtwork_his"); 
        $paras[]= $request->get("txtsex");              
        $paras[]= $para["account"];   
     }
     try
     {
        if ( empty($filename120))
        {
        	if ($table && $table["staff"]["recordcount"] >0 )
             $para["path"] = $this->container->getParameter('FILE_WEBSERVER_URL').$table["staff"]["rows"][0]["photo_path_big"];
          else
             $para["path"]=$this->get('templating.helper.assets')->geturl('bundles/fafatimewebase/images/no_photo.png');
        }
        else
        {
          $para["path"] = $this->container->getParameter('FILE_WEBSERVER_URL').$filename120;
        	$friendevent=new \Justsy\BaseBundle\Management\FriendEvent($da,$this->get('logger'),$this->container);
        	$friendevent->photochange($user->getUserName(),$user->nick_name);          
        }
        try{
        		$da->ExecSQL($sql,$paras);
        }
        catch(\Exception $ex)
        {
            $this->get("logger")->err("========保存人员资料时错误：".$ex);	
            $this->get("logger")->err("========保存人员资料时错误-SQL：".$sql);
            $this->get("logger")->err("========保存人员资料时错误-DATA：".$paras);
            Utils::sendImPresence("","10004-100082@fafacn.com","保存人员资料时错误","AccountController->savePcSyncAction:<br>".$sql."<br>".$paras,$this->container);
        }
        //如果更改了姓名时，需要同步到im库中并更新相关引用
        if($old_nick_name != $nick_name)
        	$da_im->ExecSQL("call emp_change_name(?,?)",array((string)$user->fafa_jid,(string)$nick_name));   
        //发送个人资料编辑通知
          try{
            //发送即时消息
            $staffMgr=new \Justsy\BaseBundle\Management\Staff($da,$da_im,$user);
	          $message = "{\"path\":\"".$para["path"]."\",\"desc\":\"".strtr($request->get("txtself_desc"),array("\""=>"“"))."\"}";
	          Utils::sendImPresence($user->fafa_jid,implode(",", $staffMgr->getFriendAndColleagueJid()),"staff-changeinfo",$message,$this->container,"","",false,Utils::$systemmessage_code);
	        }catch (\Exception $e) 
		      {
		          $this->get('logger')->err($e);
		      }         
			  $response = new Response(("{\"succeed\":1,\"path\":\"".$para["path"]."\"}"));
			  $response->headers->set('Content-Type', 'text/json');
			  return $response;        
     }
     catch(\Exception $e)
     {
        //return $this->render('JustsyBaseBundle:login:index.html.twig', array('name' => 'err'));
       $response = new Response(("{\"succeed\":0,\"e\":$e}"));
			 $response->headers->set('Content-Type', 'text/json');
			 return $response;
     }
  }  
  
  //修改账户部门id
  public function UpDepartAction(Request $request)
  {
  	 $orgid = $request->get("orgid");
  	 $da = $this->get('we_data_access');
     $user = $this->get('security.context')->getToken()->getUser();
     $sql = "";
     $parameter = array();
     if ( $orgid=="manager")
     {
        $sql ="update we_staff set duty='总经理' where login_account=?";
        $parameter = array((string)$user->getUserName());
     }
     else
     {
       $sql = "update we_staff set dept_id=? where login_account=?";
       $parameter = array((string)$orgid,(string)$user->getUserName());
     }
     $result=array("succeed"=>1);
     try
     {
       $da->ExecSQL($sql,$parameter);
     }
     catch(\Exception $e)
     {
     	 $result=array("succeed"=>0);
     }
     $response = new Response(json_encode($result));
		 $response->headers->set('Content-Type', 'text/json');
		 return $response;
  }

  public function circleAction($network_domain){
    $conn = $this->get('we_data_access');
    $conn_im = $this->get('we_data_access_im');
    $user = $this->get('security.context')->getToken()->getUser();
    $circleMgr=new \Justsy\BaseBundle\Management\CircleMgr($conn,$conn_im);

    $array["curr_network_domain"]=$network_domain;
    $data=$circleMgr->getCircleList($user->getUserName());
    $array['list']=$data['rows'];
    $array['count']=$data['count'];
    return $this->render("JustsyBaseBundle:Account:circle.html.twig",$array);
  }

  public function setcircleAction(Request $request){
    $conn = $this->get('we_data_access');
    $conn_im = $this->get('we_data_access_im');
    $user = $this->get('security.context')->getToken()->getUser();
    $circleobj=$request->get('circleobj'); 
    $circleobj=explode(';',$circleobj);
    $result=array("success"=>false,"msg"=>"变更圈子动态设置失败");
    
    //var_dump($circleobj);
    if(!empty($circleobj)){
      for ($i=0; $i < count($circleobj); $i++) {  
        $circlehint=explode('#',$circleobj[$i]);
        //var_dump($circlehint);
        if(count($circlehint)==2) {
          //var_dump($circlehint[1],$circlehint[0]);
          $circleMgr=new \Justsy\BaseBundle\Management\CircleMgr($conn,$conn_im,$circlehint[0]);
          $success=$circleMgr->setHint($user,$circlehint[1]);
          //var_dump($success);
          if($success) {
            $result=array("success"=>true,"msg"=>"变更圈子动态设置成功");
          }
        }
      }
    }
    $response = new Response(json_encode($result));
    $response->headers->set('Content-Type', 'text/html');
    return $response;
  }
  public function groupAction($network_domain){
    $conn = $this->get('we_data_access');
    $conn_im = $this->get('we_data_access_im');
    $user = $this->get('security.context')->getToken()->getUser();
    $groupMgr=new \Justsy\BaseBundle\Management\GroupMgr($conn,$conn_im);

    $array["curr_network_domain"]=$network_domain;
    $data=$groupMgr->getGroupList($user->getUserName());
    $array['list']=$data['rows'];
    $array['count']=$data['count'];
    return $this->render("JustsyBaseBundle:Account:group.html.twig",$array);
  }
  public function setgroupAction(Request $request){
    $conn = $this->get('we_data_access');
    $conn_im = $this->get('we_data_access_im');
    $user = $this->get('security.context')->getToken()->getUser();
    $groupobj=$request->get('groupobj');
    $groupobj=explode(';',$groupobj);
    $result=array("success"=>false,"msg"=>"变更群组动态设置失败");
    //var_dump($groupobj);
    if(!empty($groupobj)){
      $groupMgr=new \Justsy\BaseBundle\Management\GroupMgr($conn,$conn_im);
      for ($i=0; $i < count($groupobj); $i++) { 
        $grouphint=explode('#',$groupobj[$i]);
        //var_dump($grouphint);
        if(count($grouphint)==2) {
          $success=$groupMgr->setHint($grouphint[0],$user,$grouphint[1]);
          if($success) {
            $result=array("success"=>true,"msg"=>"变更群组动态设置成功");
          }
        }
      }
    }
    $response = new Response(json_encode($result));
    $response->headers->set('Content-Type', 'text/html');
    return $response;
  }
}