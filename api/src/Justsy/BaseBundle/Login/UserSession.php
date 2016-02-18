<?php
namespace Justsy\BaseBundle\Login;

use Symfony\Component\Security\Core\User\UserInterface;
use Justsy\BaseBundle\Common\DES;

class UserSession implements UserInterface
{
  private $username;
  private $password;
  private $salt;
  private $roles;
  
  public $identify;
  public $t_code;         //des加密的密码
  public $nick_name;      //呢称
  public $photo_path;     //图片路径
  public $photo_path_small;     //图片路径
  public $photo_path_big;     //图片路径
  public $dept_id;        //部门编码
  public $dept_name;      //部门名称 
  public $eno;            //企业号
  public $edomain;        //企业邮箱域名 string
  public $ename;          //企业全称
  public $eshortname;     //企业简称
  public $total_point;    //积分
  public $level;          //级别
  public $we_level;       //wefafa级别
  public $eno_level;			//企业认证等级
  public $vip_level; //企业等级
  public $auth_level;//人员认证状态 V J N
  public $mstyle;
  public $openid;            //

  public $hideModules=array();
  public $circle_ids = array();   					//圈子编号 array of string
  public $circle_names = array();   				//圈子名称 array of string
  public $network_domains = array(); 				//圈子外部域名
  public $circle_logo_path = array(); //圈子小图标
  public $circle_apply_status = array();    //圈子申请状态
  public $hometown;                         //籍贯
  public $graduated;                        //毕业院校
  public $work_his;                         //工作经历
  public $report_object;                    //汇报对象
  public $direct_manages;                   //直接下属
  public $attenstaff_num;
  public $fans_num;
  public $publish_num;
  public $state_id;
  public $comefrom;
  public $ldap_uid;
  
  public $function_array=array(); //用户对应功能点 键值对存储形式
  public $function_names=array(); //用户对应功能点名称
  public $function_codes=array(); //用户对应功能点代码
  
  public $role_array=array();//用户对应角色  键值对存储形式
  public $role_names=array(); //用户对应角色名称
  public $role_codes=array(); //用户对应角色代码
  
  public $manager_circles=array();     //取得有管理权限的圈子

  public $functionOn = array();  //单独赋予的功能点
	
	//用户对应功能点 键值对存储形式
	public function getFunctionArray(){
		return $this->function_array;
	}
	//用户对应功能点名称
	public function getFunctionNames(){
		return $this->function_names;
	}
	//用户对应功能点代码
	public function getFunctionCodes(){
		return $this->function_codes;
	}
	
	//用户对应角色  键值对存储形式
	public function getRoleArray(){
		return $this->role_array;
	}
	//用户对应角色名称
	public function getRoleNames(){
		return $this->role_names;
	}
	//用户对应角色代码
	public function getRoleCodes(){
		return $this->role_codes;
	}
	
	public function IsExistsFunction($code)
	{
		if($this->identify=='manager') return true;

		if(in_array(strtoupper($code),$this->getFunctionCodes()))
		{
//			if($this->mstyle=="inpriv" && $this->eno_level=="S") //由管理员统一控制权限
//			{
//				return in_array(strtoupper($code),$this->functionOn);
//			}
//			else
				return true;
		}
		else
			return false;
	}
	public function IsShowThisModule($code)
	{
		return (!in_array(strtoupper($code),$this->hideModules) && $this->IsExistsFunction($code));
	}
	
	//用户是否包含文档权限
	public function IsFunctionDoc($network_domain){
		$circledId = $this->get_circle_id($network_domain); 
		$en_circledId = $this->get_circle_id($this->edomain);
		return ($circledId == $en_circledId && $this->IsExistsFunction("DOC_EN"))||
		       ($circledId == "9999" && $this->IsExistsFunction("DOC_9999"))||
		       ($circledId == "10000" && $this->IsExistsFunction("DOC_10000"))||
		       ($circledId != $en_circledId && $circledId != "9999" && $circledId != "10000" && $this->IsExistsFunction("DOC"));
	}
	//用户是否包含文档权限
	public function IsFunctionCreateGroup($network_domain){
		$circledId = $this->get_circle_id($network_domain);
		$en_circledId = $this->get_circle_id($this->edomain);
		return ($circledId == $en_circledId && $this->IsExistsFunction("GROUP_C_EN"))||
		       ($circledId == "9999")||
		       ($circledId == "10000" && $this->IsExistsFunction("GROUP_C_WE"))||
		       ($circledId != $en_circledId && $circledId != "9999" && $circledId != "10000" && $this->IsExistsFunction("GROUP_C"));
	}	
	
	//用户是否可以在当前圈子中评论
	public function IsFunctionTrend($network_domain){
		$circledId = $this->get_circle_id($network_domain);
		$en_circledId = $this->get_circle_id($this->edomain);
		return ($circledId == $en_circledId && $this->IsExistsFunction("EN_TREND"))||
		       ($circledId == "9999")||
		       ($circledId == "10000" && $this->IsExistsFunction("TREND_WE"))||
		       ($circledId != $en_circledId && $circledId != "9999" && $circledId != "10000" && $this->IsExistsFunction("CIRCLE_REPLY_TREND"));
	}	
	//用户是否可以在当前圈子中查看动态
	public function IsFunctionViewTrend($network_domain){
		$circledId = $this->get_circle_id($network_domain);
		$en_circledId = $this->get_circle_id($this->edomain);
		return ($circledId == $en_circledId && $this->IsExistsFunction("EN_CIRCLE_VIEW"))||
		       ($circledId == "9999")||
		       ($circledId == "10000")||
		       ($circledId != $en_circledId && $circledId != "9999" && $circledId != "10000" && $this->IsExistsFunction("CIRCLE_VIEW_TREND"));
	}
	//用户是否有邀请权限
	//人脉圈默认打开
	public function IsFunctionRosterInvite($network_domain=null){
		if(($network_domain!=null && $network_domain=="9999") || in_array(\Justsy\BaseBundle\Management\FunctionCode::$ROSTER_INVITE,$this->getFunctionCodes())){
    	return true;
    }else{
    	return false;
    }
	}
	//用户是否拥有企业管理权限
	public function IsFunctionManagerEn(){
		if(in_array(\Justsy\BaseBundle\Management\FunctionCode::$MANAGER_EN,$this->getFunctionCodes())){
    	return true;
    }else{
    	return false;
    }
	}
	//用户是否拥有应用中心权限
	public function IsFunctionAppCenter(){
		if(in_array(\Justsy\BaseBundle\Management\FunctionCode::$APPCENTER,$this->getFunctionCodes())){
    	return true;
    }else{
    	return false;
    }
	}
	//是否显示用户个人资料完善模块
	public function IsShowPersonInfoEditer()
	{
		return $this->IsShowThisModule(\Justsy\BaseBundle\Management\FunctionCode::$FINISH_PERSON_INFO);
	}
	//是否显示企业微应用模块
	public function IsShowWeiApp()
	{
		return $this->IsShowThisModule(\Justsy\BaseBundle\Management\FunctionCode::$VIEW_WEI_APP);
	}
	//用户是否拥有创建圈子的权限
	public function IsFunctionCreateCircle(){
		if(in_array(\Justsy\BaseBundle\Management\FunctionCode::$CIRCLE_C,$this->getFunctionCodes())){
    	return true;
    }else{
    	return false;
    }
	}
	public function getIdentify(){
		return $this->auth_level.$this->vip_level;
	}
	public function IsCertification(){
		return strtoupper(trim($this->auth_level));
	}
  //根据圈子外部域名取得圈子ID，若无该域名，则返回企业圈子ID
  public function get_circle_id($network_domain)
  {
    $eno_circle_id = "";
    for ($i = 0; $i < count($this->network_domains); $i++) 
    {
      if ($network_domain == $this->network_domains[$i]) 
      {
        return $this->circle_ids[$i];
      }
      else if ($this->edomain == $this->network_domains[$i])
      {
        $eno_circle_id = $this->circle_ids[$i];
      }
    }
    
    return $eno_circle_id;
  }
  //根据圈子外部域名取得圈子名称，若无该域名，则返回企业圈子简称
  public function get_circle_name($network_domain)
  {
    for ($i = 0; $i < count($this->network_domains); $i++) 
    {
      if ($network_domain == $this->network_domains[$i]) 
      {
        return $this->circle_names[$i];
      }
    }
    
    return $this->eshortname;
  }

  public function __construct($username, $password, $salt, array $roles)
  {
    $this->username = $username;
    $this->password = $password;
    $this->salt = $salt;
    $this->roles = $roles;
  }

  public function getRoles()
  {
    return $this->roles;
  }

  public function getPassword()
  {
    return $this->password;
  }

  public function getSalt()
  {
    return $this->salt;
  }

  public function getUsername()
  {
    return $this->username;
  }
  //获得企业编号
  public function getEno()
  {
  	return $this->eno;
  }
  public function eraseCredentials()
  {
  }

  public function equals(UserInterface $user)
  {
    if (!$user instanceof UserSession)
    { return false;	}
    if ($this->password !== $user->getPassword())
    { return false; }
    if ($this->getSalt() !== $user->getSalt())
    { return false; }
    if ($this->username !== $user->getUsername())
    { return false; }
    return true;
  }
  //生成当前用户在指定应用中的认证码（签名）
  public function getAppSig($appid,$appkey)
  {
    //格式orgid,acc,pass,p1,p2  des加密
    try{
    	  //获取当前用户在该应用中的唯一标识
    	  if(!empty($this->openid))
    	  {
		        $resultAcc = DES::encrypt2($this->eno.",".$this->openid.",".$this->nick_name.",".$this->identify.",FaFa:SNS",$appkey);
		        return $resultAcc;
        }
        else return "";
    }
    catch(Exception $e)
    {
       return "";	
    }
  }
    //判断当前圈子是否在有管理权限的圈子之内
    public function is_in_manager_circles($network_domain)
    {
    	return in_array($network_domain, $this->manager_circles) ? 1 : 0;
    }
}