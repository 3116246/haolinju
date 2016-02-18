<?php

namespace Justsy\BaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class CPerBaseInfoController extends Controller
{
    public $photo_url = "";
    public $InfoCompletePercent = 0;

    public function indexAction($name)
    {
    	
      $user = $this->get('security.context')->getToken()->getUser();    
      $this->photo_url = $this->container->getParameter('FILE_WEBSERVER_URL').$user->photo_path_big;
      $this->InfoCompletePercent = $this->GetInfoCompletePercent($user->getUsername());
      
      //发升级通知
      if ($user->level > 1 && $user->level > $user->we_level)
      {        
        $user->we_level = $user->level;
        
        $sqls = array();
        $all_params = array();  
        
        $sql = "update we_staff set we_level = ? where login_account=?";
        $params = array();
        $params[] = (string)$user->level;
        $params[] = (string)$user->getUserName();
        
        $sqls[] = $sql;
        $all_params[] = $params;
                
        $da = $this->get('we_data_access'); 
        $ds = $da->ExecSQLs($sqls, $all_params);
        
        $conv_id = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_convers_list", "conv_id");
        $conv_content = "【喜讯】@$user->nick_name 的Wefafa等级已升至 $user->level 级！[强] ";
        $circle_id = $user->get_circle_id($user->edomain);
        $group_id = "ALL";
        
        $conv = new \Justsy\BaseBundle\Business\Conv();
        $conv->newSysTrend($da, $conv_id, $conv_content, $circle_id, $group_id, array(), array());
      }
      
      return $this->render('JustsyBaseBundle:CPerBaseInfo:index.html.twig', array('this' => $this));
    }
    
    public function GetInfoCompletePercent($login_account)
    {
      $dataaccess = $this->get('we_dataaccess');
      $dataset = $dataaccess->GetData('we_staff', 'select * from we_staff where login_account=?', array((string)$login_account));
      $percents = array(
      									'nick_name'           => 8, //昵称+真实姓名
                        'birthday'   	 			  => 3,// 出生日期
                        'hometown'    				=> 5, //籍贯
                        'mobile'      				=> 8, //手机号
                        'self_desc'  					=> 10, //个人简介+个人标签
                        'photo_path'          => 8, //头像
                        'login_account'       => 3, //邮箱、即登陆账号
                        'eno'                 => 10, //公司名称+地址
                        'work_phone'					=> 5, //工作电话
                        'dept_id'             => 5, //部门
                        'duty'                => 5, //职位
                        'report_object'				=> 5,  //汇报对象
                        'direct_manages'      => 5 , //直接下属
                        'graduated'					  => 10, //毕业学校
                        'work_his'            => 10  //工作经历
                        );
      $percent = 0;
      foreach ($percents as $key => $value) 
      {
        if ($dataset["we_staff"]["rows"][0][$key] != '')
        {
          $percent += $value;
        }
      }
      
      return $percent;
    } 
    
    public function getExprienceAround($exprience) 
    {
      $re = array();
      
      $level = \Justsy\BaseBundle\Common\ExperienceLevel::getLevel($exprience);
      $ExperienceLevels = \Justsy\BaseBundle\Common\ExperienceLevel::getExperienceLevels();
      
      $re["PreLevelExperience"] = $ExperienceLevels[$level];
      $re["NextLevelExperience"] = (!array_key_exists($level+1, $ExperienceLevels) ? $ExperienceLevels[$level] : $ExperienceLevels[$level+1]);
      return $re;
    }   
}
