<?php

namespace Justsy\InterfaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Management\Enterprise;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Login\UserSession;
use Justsy\BaseBundle\Login\UserProvider;
use Justsy\BaseBundle\Management\Identify;
use Justsy\BaseBundle\Management\MicroAccountMgr;
use Justsy\BaseBundle\Rbac\StaffRole;

class MobileRegisterController extends Controller
{  
 
  //手机号注册
  public function mobilenumregAction()
  {
    $logger = $this->get("logger");
    $request = $this->getRequest();
    $txtmobile = trim($request->get('mobile_num'));
    $response = $this->getResponse(ReturnCode::$SUCCESS, "");
    //验证手机号是否合法
    if (!Utils::validateMobile($txtmobile))
    {
      $logger->err("请输入正确的手机号[".$txtmobile."]");
      return $this->getResponse(ReturnCode::$OTHERERROR, "请输入正确的手机号！");
    }
    $login_account = $txtmobile;
    $domain =  $this->container->getParameter('edomain');
    if ( !strpos($login_account,"@"))
      $login_account .= "@".$domain;
    
    try
    {
      //手机号是否已经被使用
      $da = $this->get('we_data_access');
      $sql="select count(1) as cnt from we_staff where mobile_bind=? and state_id!='3'";
      $ds = $da->GetData("we_staff", $sql, array($txtmobile));
      if ($ds && $ds['we_staff']['rows'][0]['cnt']>0)
      {
        $this->get("logger")->err("手机号已被使用");
        return $this->getResponse(ReturnCode::$OTHERERROR, "该手机号已注册，继续使用请先找回密码");
      }
      $active_code = rand(100000, 999999);
      $sql = "select submit_num,state_id,last_reg_date,timestampdiff(second,last_reg_date,now()) as dif"
        ." from we_register where login_account=?";
      $ds = $da->GetData("we_register", $sql, array($login_account));
      
      //$logger->err("记录数：".$ds["we_register"]["recordcount"]);
      
      if ($ds && $ds['we_register']['recordcount'] > 0)
      {
        if ($ds['we_register']['rows'][0]['state_id'] == '3')
        {
          return $this->getResponse(ReturnCode::$OTHERERROR, "该手机号已注册，继续使用请先找回密码");
        }
        if ($ds['we_register']['rows'][0]['dif'] <= 60)
        {
          return $this->getResponse(ReturnCode::$OTHERERROR, "你获取验证码的次数太频繁！一分钟只能取一次！");
        }
        if ($ds['we_register']['rows'][0]['submit_num'] > 5 && $ds['we_register']['rows'][0]['dif'] <= 60*60*24) //最多三次
        {
          return $this->getResponse(ReturnCode::$OTHERERROR, "抱歉，验证码请求次数过多，如果获取不到验证码请根据下方提示与我们联系");
        }
        else if($ds['we_register']['rows'][0]['dif'] > 60*60*24)
        {
          //一天以后重置
          $sql = "update we_register set submit_num=0 where login_account=?";
          $da->ExecSQL($sql,array($login_account));
        }
        $sql = "update we_register set active_code=?,last_reg_date=now(),submit_num=ifnull(submit_num,0)+1,"
          ."state_id='0',review_note='0' where login_account=?";
      }
      else
      {
        $sql = "insert into we_register (active_code,login_account,submit_num,state_id,first_reg_date,last_reg_date,"
          ."register_date,review_note) values (?,?,1,'0',now(),now(),now(),'0')";
      }
      $para = array($active_code,$login_account);
      $da->ExecSQL($sql,$para);      
      $content = "验证码：".$active_code."，2分钟内有效，仅用于注册。【企业】";
  	  $ec = new \Justsy\BaseBundle\Controller\SendSMSController();  		
      $ec->setContainer($this->container);
           
      $ret = $ec->sendSMSAction($txtmobile,$content);
      if( $ret['returncode']!='0000')
      {
        $response = $this->getResponse(ReturnCode::$OTHERERROR, json_encode($ret)); 
        $this->get('logger')->err($ret);
      }      
    }
    catch (\Exception $e) 
    {
      $response = $this->getResponse(ReturnCode::$OTHERERROR, "获取验证码失败！请重试");
      $this->get('logger')->err($e);
    }    
    return $response; 
  }
  
  //手机号激活
  public function mobilenumactiveAction()
  {
     
    $deploy_mode = $this->container->getParameter('deploy_mode');

    $request = $this->getRequest();
    $mobile_num = $request->get('mobile_num');
    $mobile_pwd = $request->get('mobile_pwd');
    //$eno = $request->get('eno');
    $eno = $deploy_mode=="C" ? Utils::$PUBLIC_ENO : $this->container->getParameter("ENO");//企业独立部署时企业不设置，从配置文件中获取固定的企业号
    $ename = $request->get('ename');
    $nick_name = $request->get('nick_name');
    $active_code = $request->get('active_code');
    $login_account = $mobile_num;
    $ldap_uid = $request->get("ldap_uid");
       
    if (empty($active_code))
    {
      return $this->getResponse(ReturnCode::$OTHERERROR, "请输入短信验证码！");
    }
    if (empty($mobile_pwd))
    {
      return $this->getResponse(ReturnCode::$OTHERERROR, "请输入密码！");
    }
    if (empty($nick_name))
    {
      return $this->getResponse(ReturnCode::$OTHERERROR, "请输入姓名！");
    }

    $da = $this->get('we_data_access');
    $da_im = $this->get('we_data_access_im'); 
       
    $cacheobj = new \Justsy\BaseBundle\Management\Enterprise($da,$this->get("logger"),$this->container);   
      
    if ( !strpos($login_account,"@"))
    {
      $domain =  $this->container->getParameter('edomain');
      $login_account .= "@".$domain;
    }
    $staffMgr = new \Justsy\BaseBundle\Management\Staff($da,$da_im,$login_account,$this->get("logger"),$this->container);
    $had = $staffMgr->getInfo();
    if(!empty($had))
    {
      return $this->getResponse(ReturnCode::$OTHERERROR, "该手机号已注册，继续使用请先找回密码");
    }
    //判断手机号是否已经被使用
    if($staffMgr->checkUser($mobile_num))
    {
      return $this->getResponse(ReturnCode::$OTHERERROR, "该手机号已被绑定，请解绑后重试");
    }
    $sysparam = new \Justsy\BaseBundle\DataAccess\SysParam($this->container);
    $wn_code = $sysparam->GetSysParam("mobile_active_code");
    try
    {
      	if($wn_code!=$active_code)
      	{
      		$sql = "select state_id,active_code,review_note from we_register where login_account=?";
	        $ds = $da->GetData("we_register", $sql, array($login_account));
	        if ($ds && $ds['we_register']['recordcount'] <= 0)
	        {
	          return $this->getResponse(ReturnCode::$OTHERERROR, "未找到该手机号的注册信息！");
	        }
	        if ($ds['we_register']['rows'][0]['state_id'] == '3')
	        {
	          return $this->getResponse(ReturnCode::$OTHERERROR, "该手机号已被注册！");
	        }
	        if ((empty($ds['we_register']['rows'][0]['review_note']) ? 0 : $ds['we_register']['rows'][0]['review_note']) >= 5)
	        {
	          return $this->getResponse(ReturnCode::$OTHERERROR, "抱歉，验证码请求次数过多，如果获取不到验证码请根据下方提示与我们联系");
	        }     
	        if ($ds['we_register']['rows'][0]['active_code'] != $active_code)
	        {
	          $num = 5 - (empty($ds['we_register']['rows'][0]['review_note']) ? 0 : $ds['we_register']['rows'][0]['review_note']);
	          $sql = "update we_register set review_note=ifnull(review_note,0)+1 where login_account=?";
	          $da->ExecSQL($sql, array($login_account));
	          return $this->getResponse(ReturnCode::$OTHERERROR, "验证码错误，请重新输入。");
	        }
    	}
        $para = array();
        $para['account']=$mobile_num;
        $para['password']=$mobile_pwd;
        $para['deptid']='';
        $para['nick_name']= $nick_name;
        $para['ldap_uid']='';
      	$re = $staffMgr->createstaff($para);
    }
    catch(\Exception $e)
    {
      	$re = Utils::WrapResultError($e->getMessage());
    }
    return $this->getResponse($re['returncode'],$re['msg']);
  }

  //截取utf8字符串
  private function utf8Substr($str, $from, $len){
    return preg_replace('#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'.$from.'}'.
                       '((?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'.$len.'}).*#s',
                       '$1',$str);
  }
  private function getResponse($returncode, $msg)
  {
	  $response=new Response(json_encode(array("returncode" => $returncode,'msg' => $msg)));
	  $response->headers->set('Content-Type', 'text/json');
	  return $response; 
  }
  
  //根据企业号获得部门id
  private function getDeptId($eno){
  	$deptinfo = new \Justsy\BaseBundle\Management\Dept($this->get('we_data_access'),$this->get('we_data_access_im'));
    return $deptinfo->getDefaultDept($eno);
  }  
}