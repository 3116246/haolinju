<?php

namespace Justsy\BaseBundle\Management;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Common\Utils;

//微信公众号管理
class MicroAccountMgr implements IBusObject
{
	  private $conn=null;
	  private $conn_im=null;
	  private $userinfo=null;
	  private $logger=null;
	  private $account = "";
	  private $container = null;
	  
	  public function __construct($_db,$_db_im,$user,$_logger,$container){
	  	$this->conn=$_db;
	  	$this->conn_im=$_db_im;
	  	$this->userinfo=$user;
	  	if(!empty($user) )
	  	{
			  	if( is_string($user)){
			  		 $this->account=$user;
			  		 $staff = new Staff($_db,$_db_im,$user,$_logger,$container);
			  		 $this->userinfo = $staff->getSessionUser();
			  	}
			  	else  $this->account=$user->getUserName();
	    }
	  	$this->logger=$_logger;
	  	$this->container = $container;
	  }

    public function getInstance($container)
    {
        $db = $container->get("we_data_access");
        $db_im = $container->get("we_data_access_im");
        $logger = $container->get("logger");
        $token = $container->get('security.context')->getToken();
        if(!empty($token))
          $user = $token->getUser();
        else
          $user = $container->get('request')->get("openid");

        return new self($db,$db_im,$user,$logger,$container);
    }

    //获取指定的公众号信息。支持dsid统一数据访问
    public function getInfo($parameter)
    {
        $microaccount = $parameter["login_account"];
        $user = $parameter["user"];
        $data = $this->microaccount_query($microaccount);
        if(empty($data) || count($data)==0)
        {
            throw new \Exception("无效的公众号帐号");
        }
        $sql = "select 1 from we_staff_atten where login_account=? and atten_type='01' and atten_id=?";
        $paras=array((string)$user->getUserName(),(string)$microaccount);
        $atteninfo=$this->conn->GetData("dt",$sql,$paras);
        if(count($atteninfo["dt"]["rows"])>0)
        {
          $data[0]["atten_state"] = "1";
        }
        else
        {
          $data[0]["atten_state"] = "0";
        }
        return $data[0];
    }

    public function getListByDept($deptid)
    {
        $sql = "select b.* from we_service a,we_micro_account b where b.number=a.login_account and a.type='1' and a.objid=?";
        $ds = $this->conn->GetData('dt', $sql, array((string)$deptid));
        return $ds["dt"]["rows"];
    }

    //获取默认公众号的消息 。当用户第一次登录时应该获取该消息。支持dsid统一数据访问
    public function getmsg($paraObj)
    {
      
      $user = $paraObj["user"];
      $pagesize =isset($paraObj["pagesize"]) ? $paraObj["pagesize"] : 20;
      if(empty($pagesize))
      {
        $pagesize = 20;
      }
      $account =isset($paraObj["publicaccount"]) ?  $paraObj["publicaccount"] : "";
      //获取默认公众号消息
      if(empty($account))
      {
        $account = $this->get_wefafa_publicaccount();
      }
      $sql = "select b.* from we_micro_send_message a,we_micro_message b where a.id=b.send_id and a.send_account=? group by send_id order by send_datetime desc limit 0,".$pagesize;
      $para = array((string)$account);
      $ds = $this->conn->GetData("t",$sql,$para);
      if(count($ds["t"]["rows"])>0)
      {
        $apicontroller = new \Justsy\OpenAPIBundle\Controller\ApiController();
        $apicontroller->setContainer($this->container);
        for ($i=0; $i < count($ds["t"]["rows"]); $i++) 
        {
        	$web_url=$this->container->getParameter('open_api_url');
            $data  = $ds["t"]["rows"][$i];
            $msg_id = "sas-".$data["send_id"];
            if($data["msg_type"]=="TEXT")
            {
              $apicontroller->sendMsg2($account,$user->fafa_jid,$data["msg_content"],$data["msg_type"],false,0,$msg_id);
            }
            else if($data["msg_type"]=="TEXTPICTURE")
            {
              $headitem=array();
              $items=array();
              $sql = "select * from we_micro_message where send_id=?";
              $tmpDs = $this->conn->GetData("t",$sql,array((string)$data["send_id"]));
              for ($j=0; $j < count($tmpDs["t"]["rows"]); $j++) { 
              	  $web_url=$this->container->getParameter('open_api_url');
                  $tmpData = $tmpDs["t"]["rows"][$j];
                  $uniqid = $tmpData["msg_web_url"];
                  $web_url = $web_url.'/api/http/getpagepath/'.$uniqid;
                  if($j==0) //headitem
                  {
                    $headitem= array('title'=>$tmpData["msg_title"]
                      ,'image'=>array('type'=>'URL','value'=>$tmpData["msg_img_url"])
                      ,'link'=>$web_url);
                    continue;
                  }
                  $item_array=array();
                  $item_array= array('title'=>$tmpData["msg_title"]
                      ,'image'=>array('type'=>'URL','value'=>$tmpData["msg_img_url"])
                      ,'link'=>$web_url);
                  array_push($items, $item_array);
              }
              $msgContent= array('textpicturemsg'=>array('headitem'=>$headitem,'item'=>$items));
              $msgContent=json_encode($msgContent);
              $apicontroller->sendMsg2($account,$user->fafa_jid,$msgContent,$data["msg_type"],false,0,$msg_id);
            }
            else if($data["msg_type"]=="PICTURE")
            {          
              $uniqid = $data["msg_web_url"];
              $web_url = $web_url.'/api/http/getpagepath/'.$uniqid;

              $msgContent= array('picturemsg'=>array('headitem'=>array('title'=>$data["msg_title"]
                  ,'image'=>array('type'=>'URL','value'=>$data["msg_img_url"])
                  ,'content'=>$data["msg_summary"]
                  ,'link'=>$web_url)));
              $apicontroller->sendMsg2($account,$user->fafa_jid,json_encode($msgContent),$data["msg_type"],false,0,$msg_id);
            }
        }
      }
      return Utils::WrapResultOK(true);
    }

	  //判断指定人员是否可以使用公众号
	  //规则：每个企业的管理员可使用；公众号本身 
	  public function IsUseUser()
	  {
	  	if(!is_string($this->userinfo))
	  	{
	  	    $acc = $this->userinfo->getUserName();
	  	    $eno = $this->userinfo->eno;
	        $sql = "select concat(',',create_staff,',',sys_manager) staffs,'manager' role from we_enterprise where eno=? union select concat(',',number) staffs ,'' role from we_micro_account where number=?";
          $paras = array((string)$eno ,(string)$acc);	  	    
	    }
	    else
	    {
	    	  $acc = $this->userinfo;
	        $sql = "select concat(',',a.create_staff,',',a.sys_manager) staffs,'manager' role from we_enterprise a,we_staff b where a.eno=b.eno and b.login_account=? union select concat(',',number) staffs ,'' role from we_micro_account where number=?";
          $paras = array((string)$acc ,(string)$acc);	        	
	    }
      $dataset = $this->conn->GetData('dt', $sql, $paras);
      for($i=0; $i<count($dataset["dt"]["rows"]);$i++)
      {
          $data = $dataset["dt"]["rows"][$i]["staffs"];
          if(strpos($data,$acc)!==false) return array("use"=>1,"role"=>$dataset["dt"]["rows"][$i]["role"]);
      }
      return array("use"=>0);  	
	  }
	  //获取我关注的公众号
	  public function getMy()
	  {
	      $sql = "select microaccount from im_microaccount_memebr  where employeeid=? order by microaccount";
		  $paras = array((string) $this->userinfo->fafa_jid);
		  $staffObj = new Staff($this->conn,$this->conn_im,$this->userinfo,$this->logger,$this->container);
	      $dataset = $this->conn_im->GetData('dt', $sql, $paras);
	      $data = $dataset["dt"]["rows"];
	      $result = array();
	      foreach ($data as $key => $value) {
	        $staffinfo = $staffObj->getStaffInfo($value['microaccount']);
	        $tmp=array();
	        $tmp['number'] = $staffinfo['login_account'];
	        $tmp['jid'] = $staffinfo['jid'];
	        $tmp['name'] = $staffinfo['nick_name'];
	        $tmp['logo_path_big'] = $staffinfo['photo_path'];
	        $tmp['introduction'] = $staffinfo['self_desc'];
	        $result[] = $tmp;
	      }
	      return $result;
	  }
    //判断openid是否已经关注指定公众号
	public function checkOpenidMicro($openid,$micronumber)
	{
      $sql="SELECT a.login_account,b.fafa_jid FROM we_staff_atten a LEFT JOIN we_staff b ON a.login_account=b.login_account WHERE a.atten_id=? AND a.atten_type='01' AND b.openid=? ";
      $para=array($micronumber,$openid);
      $data=$this->conn->GetData("dt",$sql,$para);
      return count($data["dt"]["rows"])>0?$data["dt"]["rows"][0]["fafa_jid"]:"";
    }
	public function insertMicroAccount($micro_name,$micro_number,$micro_password,$micro_type,$micro_use,$concern_approval
	  ,$introduction,$salutatory,$logo_path,$logo_path_big,$logo_path_small,$factory){
	  	$eno = $this->userinfo->getEno();
	  	$domain =  $this->container->getParameter('edomain');
      $create_account = $this->userinfo->getUsername();
      $limit= $micro_use; //0 内部公众号 表示无限推送  1 外部公众号 表示 每天只能推送1条消息
      $level=1;
      $window_template=0;
      $sqls = array();
      $sqls_im=array();
      $paras = array();
      $paras_im = array();
      $domain =  $this->container->getParameter('edomain');
      //判断公众号帐号是否已经使用
      $u_staff = new Staff($this->conn,$this->conn_im,$micro_number,$this->logger);
      $u_flag=$u_staff->isExist();
      $array["returncode"]='0000'; //0000表示成功 9999表示失败
      $array["msg"]="";
    	if(!empty($u_flag)){
    		$array["returncode"]='9999';
      	$array["msg"]="公众号帐号已存在";
        if($micro_use==0) $array["msg"]="公众号帐号已存在";
        else if($micro_use==1)$array['msg']='微应用帐号已存在';
        else $array["msg"]="公众号帐号已存在";
    	}else{
    		$micro_jid = SysSeq::GetSeqNextValue($this->conn,"we_staff","fafa_jid");
      	$micro_jid.= "-".$eno."@".$domain;
      	//创建公众号部门 并获取部门ID和JID
        $dept=new Dept($this->conn,$this->conn_im);
        $deptidArr= $dept->createMicromessageDept($eno);
        
        $micro_id= SysSeq::GetSeqNextValue($this->conn, "we_micro_account", "id");
        $sql ="INSERT INTO `we_micro_account` ";
        $sql.="(`id`, `number`, `name`, `jid`, `type`,`micro_use`, `logo_path`, `logo_path_big`, `logo_path_small`, `introduction`, `eno`, ";
        $sql.="`limit`, `concern_approval`, `level`, `fans_count`, `window_template`, `salutatory`, `send_status`, ";
        $sql.="`send_datetime`, `create_account`, `create_datetime`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, false, NULL, ?, now());";
        $sqls[]=$sql;
        $paras[]=array((string)$micro_id,
        						(string)$micro_number,
										(string)$micro_name,
										(string)$micro_jid,
										(string)$micro_type,
										(string)$micro_use,
										(string)$logo_path,
										(string)$logo_path_big,
										(string)$logo_path_small,
										(string)$introduction,
										(string)$eno,
										(string)$limit,
										(string)$concern_approval,
										(string)$level,
										(string)$window_template,
										(string)$salutatory,
										(string)$create_account);
          
          $sqls_im[] ="INSERT INTO `im_employee` (`employeeid`, `deptid`, `loginname`, `password`, `employeename`) VALUES (?, ?, ?, ?, ?);";
          $para_im=array();
          array_push($para_im,(string)$micro_jid);
          array_push($para_im,(string)$deptidArr["fafa_deptid"]);
          array_push($para_im,(string)$micro_jid);
          array_push($para_im,(string)$micro_password);
          array_push($para_im,(string)$micro_name);
          $paras_im[]=$para_im;
          $sqls_im[] = "insert into users (username,password,created_at)values(?,?,now())";
          $paras_im[]=array((string)$micro_jid,(string)$micro_password);
          
          $sql ="INSERT INTO `we_staff` (`dept_id`, `eno`, `login_account`, `nick_name`, `password`, `photo_path`, `fafa_jid`, `state_id`,";
          $sql.=" `login_num`, `total_point`,  `photo_path_small`, `photo_path_big`, `openid`, `t_code`, `we_level`,";
          $sql.=" `attenstaff_num`, `fans_num`, `publish_num`, `sex_id`,self_desc,`register_date`, `active_date`,`auth_level`)";
          $sql.=" VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,now(),now(),'MICRO')";
          $sqls[]=$sql;
          $user = $this->userinfo; 
			    $encoder = $factory->getEncoder($user);
			    $t_code=DES::encrypt($micro_password);
			    $micro_password = $encoder->encodePassword($micro_password, $micro_number);
          $state_id="1";
          $login_num=0;
          $total_point=0;
          $openid=md5($eno.$micro_number);
          $we_level=1;
          $attenstaff_num=0;
          $fans_num=0;
          $publish_num=0;
          $sex_id="男";
          $paras[]=array((string)$deptidArr["dept_id"],
          							(string)$eno,
          							(string)$micro_number,
          							(string)$micro_name,
          							(string)$micro_password,
          							(string)$logo_path,
          							(string)$micro_jid,
          							(string)$state_id,
          							(string)$login_num,
          							(string)$total_point,
          							(string)$logo_path_small,
          							(string)$logo_path_big,
          							(string)$openid,
          							(string)$t_code,
          							(string)$we_level,
          							(string)$attenstaff_num,
          							(string)$fans_num,
          							(string)$publish_num,
          							(string)$sex_id,
          							(string)$introduction);
           //设置企业推送平台授权
           //应用标识 正式环境：8afe9e6f2d8e91dc2ff5  测试环境：c5845cf3331c833cf5d9
           $priv_id= SysSeq::GetSeqNextValue($this->conn, "we_app_userpriv", "id");
           $sql = "insert into we_app_userpriv select ? id, ? login_account,appid,'0' role from we_appcenter_apps where appid in(?,?) limit 0,1";
           $sqls[]=$sql;
           $paras[] =array((string)$priv_id,(string)$micro_number,"8afe9e6f2d8e91dc2ff5","c5845cf3331c833cf5d9");   
           //赋公众号对应的固定角色
           $staffrole_id= SysSeq::GetSeqNextValue($this->conn, "we_staff_role", "id");	
           $sql = "insert into we_staff_role select ? id, ? staff,we_role.id roleid, ? eno from we_role where code='MICRO'";
           $sqls[]=$sql;
           $paras[] =array((string)$staffrole_id,(string)$micro_number,(string)$eno);

    	}
    	try {
      	if(!empty($sqls)){
        	$dataexec = $this->conn->ExecSQLs($sqls, $paras);
        	
        	if($dataexec)$this->conn_im->ExecSQLs($sqls_im, $paras_im);
            //创建者关注  微应用或公众号 
            $this->micro_fans_attention($micro_number,$create_account);
            if($micro_type==="0")
            {
            	$enterObj = new \Justsy\BaseBundle\Management\Enterprise($this->conn,null);
            	$info = $enterObj->getInfo($eno);
            	$this->micro_fans_circle($micro_number,$info["circle_id"]);
            }
        	$array["returncode"]='0000';
          if($micro_use==0) $array["msg"]="成功创建公众号【".$micro_name."】";
          else if($micro_use==1)$array['msg']='成功创建微应用【'.$micro_name.'】';
          else $array["msg"]="成功创建公众号【".$micro_name."】";
      	}else{
      		$array["returncode"]='9999';
      		$array["msg"]="没有需要创建的信息";
      	}
      } catch (\Exception $exc) {
      	$this->logger->err($exc);
      	$array["returncode"]='0000';
      	$array["msg"]="创建过程中,出现异常情况";
      }
    	return $array;
	  }
	  
	  //注册公众号
	  public function register($micro_id,$number,$name,$type,$micro_use,$introduction,$concern_approval,
	  $salutatory,$level,$password,$logo_path,$logo_path_big,$logo_path_small,$factory,$dm,$appid=null){
        $eno = $this->userinfo->getEno();
        $domain =  $this->container->getParameter('edomain');
        $create_account = $this->userinfo->getUsername();
        $limit= $micro_use; //0 内部公众号 表示无限推送  1 外部公众号 表示 每天只能推送1条消息
        $level=1;
        $window_template=0;
        $sqls = array();
        $sqls_im=array();
        $paras = array();
        $paras_im = array();
        $add_micro_id=$micro_id;
        if (empty($micro_id)) {
        	//判断公众号帐号是否已经使用
        	$u_staff = new Staff($this->conn,$this->conn_im,$number,$this->logger);
        	$u_flag=$u_staff->isExist();
        	if(!empty($u_flag))
        	{
        		    //帐号已被占用
        	    	return false;
        	}
          $jid = SysSeq::GetSeqNextValue($this->conn,"we_staff","fafa_jid");
      		$jid.= "-".$eno."@".$domain;
      		
          //创建公众号部门 并获取部门ID和JID
          $dept=new Dept($this->conn,$this->conn_im);
          $deptidArr= $dept->createMicromessageDept($eno);
          
          $micro_id= SysSeq::GetSeqNextValue($this->conn, "we_micro_account", "id");
          
          $sql ="INSERT INTO `we_micro_account` ";
          $sql.="(`id`, `number`, `name`, `jid`, `type`,`micro_use`, `logo_path`, `logo_path_big`, `logo_path_small`, `introduction`, `eno`, ";
          $sql.="`limit`, `concern_approval`, `level`, `fans_count`, `window_template`, `salutatory`, `send_status`, ";
          $sql.="`send_datetime`, `create_account`, `create_datetime`,`micro_source`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, false, NULL, ?, now(),?);";
          $sqls[]=$sql;
          $paras[]=array((string)$micro_id,
          						(string)$number,
											(string)$name,
											(string)$jid,
											(string)$type,
											(string)$micro_use,
											(string)$logo_path,
											(string)$logo_path_big,
											(string)$logo_path_small,
											(string)$introduction,
											(string)$eno,
											(string)$limit,
											(string)$concern_approval,
											(string)$level,
											(string)$window_template,
											(string)$salutatory,
											(string)$create_account,$appid);
          
          $sqls_im[] ="INSERT INTO `im_employee` (`employeeid`, `deptid`, `loginname`, `password`, `employeename`) VALUES (?, ?, ?, ?, ?);";
          $para_im=array();
          array_push($para_im,(string)$jid);
          array_push($para_im,(string)$deptidArr["fafa_deptid"]);
          array_push($para_im,(string)$jid);
          array_push($para_im,(string)$password);
          array_push($para_im,(string)$name);
          $paras_im[]=$para_im;
          $sqls_im[] = "insert into users (username,password,created_at)values(?,?,now())";
          $paras_im[]=array((string)$jid,(string)$password);
          
          
          $sql ="INSERT INTO `we_staff` (`dept_id`, `eno`, `login_account`, `nick_name`, `password`, `photo_path`, `fafa_jid`, `state_id`,";
          $sql.=" `login_num`, `total_point`,  `photo_path_small`, `photo_path_big`, `openid`, `t_code`, `we_level`,";
          $sql.=" `attenstaff_num`, `fans_num`, `publish_num`, `sex_id`,self_desc,`register_date`, `active_date`)";
          $sql.=" VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,now(),now())";
          $sqls[]=$sql;
          $user = $this->userinfo; 
			    $encoder = $factory->getEncoder($user);
			    $t_code=DES::encrypt($password);
			    $password = $encoder->encodePassword($password, $number);
          $state_id="1";
          $login_num=0;
          $total_point=0;
          $openid=md5($eno.$number);
          $we_level=1;
          $attenstaff_num=0;
          $fans_num=0;
          $publish_num=0;
          $sex_id="男";
          $paras[]=array((string)$deptidArr["dept_id"],
          							(string)$eno,
          							(string)$number,
          							(string)$name,
          							(string)$password,
          							(string)$logo_path,
          							(string)$jid,
          							(string)$state_id,
          							(string)$login_num,
          							(string)$total_point,
          							(string)$logo_path_small,
          							(string)$logo_path_big,
          							(string)$openid,
          							(string)$t_code,
          							(string)$we_level,
          							(string)$attenstaff_num,
          							(string)$fans_num,
          							(string)$publish_num,
          							(string)$sex_id,
          							(string)$introduction);
           //设置企业推送平台授权
           //应用标识 正式环境：8afe9e6f2d8e91dc2ff5  测试环境：c5845cf3331c833cf5d9
           $priv_id= SysSeq::GetSeqNextValue($this->conn, "we_app_userpriv", "id");
           $sql = "insert into we_app_userpriv select ? id, ? login_account,appid,'0' role from we_appcenter_apps where appid in(?,?) limit 0,1";
           $sqls[]=$sql;
           $paras[] =array((string)$priv_id,(string)$number,"8afe9e6f2d8e91dc2ff5","c5845cf3331c833cf5d9");
           //赋公众号对应的固定角色
           $staffrole_id= SysSeq::GetSeqNextValue($this->conn, "we_staff_role", "id");	
           $sql = "insert into we_staff_role select ? id, ? staff,we_role.id roleid,? eno from we_role where code='MICRO'";
           $sqls[]=$sql;
           $paras[] =array((string)$staffrole_id,(string)$number,(string)$eno);           
        }else{
          $sql ="UPDATE `we_micro_account` SET `name`=?,`micro_use`=?, `logo_path`=?,";
          $sql.=" `logo_path_big`=?, `logo_path_small`=?, `introduction`=?, `limit`=?, ";
          $sql.="`concern_approval`=?, `level`=?, `window_template`=?, `salutatory`=? WHERE `id`=? ;";
          $sqls[]=$sql;
          $paras[]=array((string)$name,
          				(string)$micro_use,
				          (string)$logo_path,
				          (string)$logo_path_big,
				          (string)$logo_path_small,
				          (string)$introduction,
				          (string)$limit,
				          (string)$concern_approval,
				          (string)$level,
				          (string)$window_template,
				          (string)$salutatory,
				          (string)$micro_id);				  
			$micro_data=$this->get_micro_data_id($micro_id);				  
			$sqls[] ="UPDATE we_staff SET nick_name=?,photo_path=?,photo_path_big=?,photo_path_small=?,self_desc=? where fafa_jid=? and login_account=? and eno=?;";
          	$paras[]=array((string)$name,
          							(string)$logo_path,
          							(string)$logo_path_big,
          							(string)$logo_path_small,
          							(string)$introduction,
          							(string)$micro_data[0]["jid"],
          							(string)$micro_data[0]["number"],
          							(string)$micro_data[0]["eno"]);

			if(empty($micro_data[0]["logo_path"])){
						$this->removeFile($micro_data[0]["logo_path"],$dm);
						$this->removeFile($micro_data[0]["logo_path_big"],$dm);
						$this->removeFile($micro_data[0]["logo_path_small"],$dm);
			}
          
          	$sqls_im[]="UPDATE `im_employee` SET `employeename`=? WHERE (`loginname`=?);";
          	$para_im=array();
          	array_push($para_im,(string)$name);
          	array_push($para_im,(string)$micro_data[0]["jid"]);
          	$paras_im[]=$para_im;
        }
        //var_dump($sqls);
        try {
        	if(!empty($sqls)){
	        	$dataexec = $this->conn->ExecSQLs($sqls, $paras);
	        	
	        	if($dataexec)$this->conn_im->ExecSQLs($sqls_im, $paras_im);
            //创建者关注  微应用或公众号 
            if(empty($add_micro_id)) $this->micro_fans_attention($number,$create_account);
            if(empty($appid) && empty($add_micro_id))
            {
            	//新增公众号
	            if($type==="0")
	            {
	            	$enterObj = new \Justsy\BaseBundle\Management\Enterprise($this->conn,null);
	            	$info = $enterObj->getInfo($eno);
	            	$this->micro_fans_circle($number,$info["circle_id"]);
	            }
            }
            if(!empty($micro_id)){
              
              $sql="SELECT b.fafa_jid FROM we_staff_atten a LEFT JOIN we_staff b ON a.login_account=b.login_account WHERE a.atten_id=? AND b.fafa_jid IS NOT NULL ;";
              $para=array($micro_id);
              $data=$this->conn->GetData("dt",$sql,$para);
              if ($data!=null&&count($data['dt']['rows'])>0) {
                $tojid=array();
                for ($i=0; $i < count($data['dt']['rows']); $i++) { 
                  array_push($tojid, $data['dt']['rows'][$i]['fafa_jid']);
                }
                $micro_data=$this->get_micro_data_id($micro_id);
                $message=json_encode($micro_data);
                Utils::sendImMessage($this->userinfo->fafa_jid, implode(",",$tojid), 'microcount-change', $message,$this->container,'','',false,Utils::$systemmessage_code,0);
              }
            }
	      	}
	        return true;
        } catch (\Exception $exc) {
        	$this->logger->err($exc);
        	//var_dump($exc->getMessage());
        	return false;
        }
	  }
	  public function remove($eno,$number)
	  {
	  		$sql = "select * from we_micro_account where eno=? and number=?";
        $paras = array((string) $eno,(string) $number);
        $dataset = $this->conn->GetData('dt', $sql, $paras);
        $data = $dataset["dt"]["rows"];
        if($data && count($data["dt"]["rows"]))
           return $this->removeByID($data["dt"]["rows"][0]);
        else
           return true;
	  }
	  
	  public function removeByID($micro_id)
	  {
	  		$dr=array();
	  	  if(is_string($micro_id)){
	  	  	$dr = $this->get_micro_data_id($micro_id);
	  	  	$dr=$dr[0];
	  	  }
	  	  else $dr = $micro_id;
	  	  //获取粉丝
	  	  $sql = "select b.fafa_jid from we_staff_atten a,we_staff b where a.login_account=b.login_account and a.atten_id=? and atten_type='01'";
	  	  $fansArr = $this->conn->GetData("d",$sql,array((string)$dr["number"]));
	  	  $jids=array();
	  	  for($i=0;$i<count($fansArr["d"]["rows"]); $i++){
	  	     if(!empty($fansArr["d"]["rows"][$i]["fafa_jid"]))
	  	     	array_push($jids,$fansArr["d"]["rows"][$i]["fafa_jid"]);
	  	  }
	  	  $sqls = array("delete from we_micro_account where id=?");
	  	  $paras =array( array((string) $dr["id"]));
	  	  $sqls[]= "delete from we_staff where login_account=? and eno=?";
	  	  $paras[] = array((string)$dr["number"],(string)$dr["eno"]);
	  	  $sqls[]= "delete from we_staff_atten where atten_id=? and atten_type='01'";
	  	  $paras[] = array((string)$dr["number"]);
	  	  //删除推送平台授权
	  	  $sqls[]= "delete from we_app_userpriv where login_account=?";
	  	  $paras[] = array((string)$dr["number"]);

	  	  $sqls[]= "delete from we_staff_role where staff=? ";
	  	  $paras[] = array((string)$dr["number"]);

	  	  if( $this->conn->ExecSQLs($sqls,$paras)){
	  	  	$fafajid=$this->userinfo->fafa_jid;
  	  	  //发粉丝发送通知消息
  	  	  if(count($jids)>0)
  	  	      Utils::sendImPresence($fafajid,implode(",",$jids),"microaccount-delete",json_encode(array("number"=>$dr["number"],"name"=>$dr["name"],"eno"=>$dr["eno"],"jid"=>$dr["jid"])), $this->container,"","",false,Utils::$systemmessage_code);
  	  	  return $this->conn_im->ExecSQL("delete from im_employee where employeeid=?",array((string)$dr["jid"]));
	  	  }
	  	  else return false;
	  }

    public function microaccount_query($microaccount){      
      $sql ="select a.*,'' as grouplist,'' im_state,'' im_resource,'' im_priority,c.ename,c.eshortname   ";
      $sql.="from  we_micro_account  a ,we_enterprise c ";
      $sql.="where a.eno=c.eno and (a.number =? or a.jid=?)";
      $paras=array((string)$microaccount,(string)$microaccount);
      $data=$this->conn->GetData("dt",$sql,$paras); 
      if(count($data["dt"]["rows"])==0)
        return array();
      $micro_use = $data["dt"]["rows"][0]["micro_use"];
      if($micro_use===1)
      {
        $state = $this->getConnState($data["dt"]["rows"]);
        //var_dump( $data['dt']["rows"]);
        for($i=0; $i<count($data["dt"]["rows"]); $i++)
        {
           $jid = $data["dt"]["rows"][$i]["jid"];
           if(!empty($state[$jid]))
           {
              $data["dt"]["rows"][$i]["im_state"] = $state[$jid]["state"];
              $data["dt"]["rows"][$i]["im_resource"] = $state[$jid]["resource"];
              $data["dt"]["rows"][$i]["im_priority"] = $state[$jid]["priority"];
           }
        }  
      }
      return $data["dt"]["rows"];
    }
	  
	  //搜索开放的公众号，并返回粉丝数最多的前100个号
	public function microaccount_search($value,$isatten=false,$micro_use=0)
	{
	      if(empty($micro_use)) $micro_use=0;
	      $sql ="select a.*,'' as grouplist,'' im_state,'' im_resource,'' im_priority,c.ename,c.eshortname   ";
	      $sql.="from  we_micro_account  a ,we_enterprise c ";
	      $sql.="where a.concern_approval=1 and a.eno=c.eno and (a.name like BINARY concat('%', ?,'%') or a.number =? or a.jid =?) AND a.eno like CONCAT('%',if(a.type=0,?,''),'%') AND a.micro_use=? ";
	      $eno=$this->userinfo->getEno();
	      $paras=array($value,$value,$value,$eno,$micro_use);      
	      if($isatten!=false){
	        $sql.="and not exists (select 1 from we_staff_atten d where d.login_account= ? and d.atten_type='01' and d.atten_id=a.number) ";
	        array_push($paras, (string)$this->account);
	      }
	      $sql.="order by (select count(1) from we_staff_atten where atten_id=a.number and atten_type='01') desc limit 0,100 ";
	  	  $data=$this->conn->GetData("dt",$sql,$paras);
	  	  $state = $this->getConnState($data["dt"]["rows"]);  	  
	      for($i=0; $i<count($data["dt"]["rows"]); $i++)
	      {
	         $jid = $data["dt"]["rows"][$i]["jid"];
	         if(!empty($state[$jid]))
	         {
	            $data["dt"]["rows"][$i]["im_state"] = $state[$jid]["state"];
	            $data["dt"]["rows"][$i]["im_resource"] = $state[$jid]["resource"];
	            $data["dt"]["rows"][$i]["im_priority"] = $state[$jid]["priority"];
	         }
	      }  
	  	return $data["dt"]["rows"];
	}
	  
	  //检测公众号帐号是否存在
	  public function check_micro_number($number){
	  	$sql="select count(*) as count from we_staff where login_account=? ";
	  	$paras=array((string)$number);
	  	$data=$this->conn->GetData("dt",$sql,$paras);
	  	//$sql_staff="select count(*) as count from we_staff where login_account=?";
	  	//$paras_staff=array((string)$number);
	  	//$data_staff=$this->conn->GetData("dtstaff",$sql_staff,$paras_staff);
	  	//$data["dt"]["rows"][0]["count"]>0||
	  	if($data["dt"]["rows"][0]["count"]>0){
	  		return 1;
	  	}
	  	return 0;
	  }
	  //检测公众号名称是否重复
	  public function check_micro_name($name,$old_name,$eno){
			$sql="select count(*) as count from we_micro_account where name=? and eno=? ";
			if(!empty($old_name)){
				$sql.="and name!=?";
			}
			$paras=array((string)$name,(string)$eno);
			if(!empty($old_name)){
				array_push($paras,(string)$old_name);
			}
			$data=$this->conn->GetData("dt",$sql,$paras);
	  	//$sql_staff="select count(*) as count from we_staff where nick_name=? and eno=? ";
	  	//if(!empty($old_name)){
	  	//	$sql_staff.="and nick_name!=?";
	  	//}
	  	//$paras_staff=array((string)$name,(string)$eno);
	  	//if(!empty($old_name)){
	  	//	array_push($paras_staff,(string)$old_name);
	  	//}
	  	//$data_staff=$this->conn->GetData("dtstaff",$sql_staff,$paras_staff);
	  	//$data["dt"]["rows"][0]["count"]>0 || 
	  	if($data["dt"]["rows"][0]["count"]>0){
	  		return 1;
	  	}
	  	return 0;
	  }
    //关注公众号。支持dsid统一数据访问
    public function atten($parameter)
    {
        $user = $parameter["user"];
        $publicaccount = $parameter["publicaccount"];
        if(empty($user))
        {
            return Utils::WrapResultError("请登录后重试");
        }
        if(empty($publicaccount))
        {
            return Utils::WrapResultError("关注的公众号不能为空");
        }
        $this->micro_fans_attention($publicaccount,$user->getUserName());
        return Utils::WrapResultOK("");
    }
	  
	  //粉丝关注并修改对应粉丝数
	  public function micro_fans_attention($micro_account,$login_account){	  
      try
      {
          $sql = "insert into im_microaccount_memebr(employeeid,microaccount,lastreadid,subscribedate)values(?,?,0,now())";
          $staffMgr = new Staff($this->conn,$this->conn_im,$login_account,$this->logger,$this->container);
    	  	$data = $staffMgr->getInfo();
          if(empty($data))
          {
              return 1;
          }
          $emp_jid = $data["fafa_jid"];
          $data = $staffMgr->getStaffInfo($micro_account);
          if(empty($data))
          {
              return 1;
          }
          $micro_jid = $data["fafa_jid"];
          $this->conn_im->ExecSQL($sql, array((string)$emp_jid,(string)$micro_jid));
        
  	  	  $sqls ="update we_micro_account set fans_count=fans_count+1 where number=?";
  	  		$paras=array((string)$micro_account);
          $this->conn->ExecSQL($sqls, $paras);         
          return 0;
      } catch (\Exception $exc) {
      	$this->logger->err($exc);
       	return 1;//系统异常
      }
	  }
	  
	  //关注wefafa企业的默认公众号
	  //每个注册wefafa的帐号默认自动关注
	  public function atten_wefafa_publicaccount(){
	  	//$wefafa_public = $this->get_wefafa_publicaccount();      //we团队
	    //$this->micro_fans_attention($wefafa_public,$this->account);
      //$wefafa_public = "wefafa_helper@fafatime.com"; //we助手
	    //$this->micro_fans_attention($wefafa_public,$this->account);
      //$staffobj = new \Justsy\BaseBundle\Management\Staff($this->conn,$this->conn_im,$this->account,$this->logger,$this->container);
      //$wefafa_public = "we@wefafa.net"; //加好友
      //$staffobj->bothAddFriend($this->container,$wefafa_public);
	    return true;
	  }

    public function get_wefafa_publicaccount(){
      return "fafa@wefafa.net";
    }
	  
	  //粉丝类型为好友
	  public function micro_fans_friend($micro_account,$obj){
  	  $microObj = $this->get_micro_data_account($micro_account);
	  	//判断是不是特殊公众号-微应用。不能添加粉丝
	  	//if($microObj["micro_use"]=="8")
	  	//{
	  	//    	return array("success"=>0,"msg"=>"微应用不能添加粉丝");
	  	//}   	  
  	  $array["success"]=0;
  	  $array["msg"]="";
  	  if($microObj==null)return $array;
	  	$login_account_array=explode(",",$obj);
  		//记录关注上的成员帐号
  		$jids=array();
	  	foreach ($login_account_array as $staff) {
	  		if(!empty($staff)){
	  	 		$dataexec=$this->micro_fans_attention($micro_account,$staff);
	  	 		if($dataexec==0) array_push($jids, $staff);
	  	 		$array["success"]=$dataexec;
	  	 	}
	  	}
	  	$nick_name=$this->userinfo->nick_name;		
      if($microObj["micro_use"]==0) $message="您已被管理员【". $nick_name."】设置关注公众号【".$microObj["name"]."】";
      else if($microObj["micro_use"]==1) $message="您已被管理员【". $nick_name."】设置关注微应用【".$microObj["name"]."】";	
	  	else $message="您已被管理员【". $nick_name."】设置关注公众号【".$microObj["name"]."】";
	  	//向关注成功的帐号发送消息通知
	  	$this->micro_sendMessage($jids,$message);
	  	
	  	return $array;
	  }
	  //邀请成员关注-粉丝类型为好友的情况
	  public function invite_micro_fans_friend($micro_account,$obj){
	  	//判断公众号是否存在
	  	$microObj = $this->get_micro_data_account($micro_account);
	  	//判断是不是特殊公众号-微应用。不能添加粉丝
	  	//if($microObj["micro_use"]=="8")
	  	//{
	  	//    	return array("success"=>0,"msg"=>"微应用不能添加粉丝");
	  	//}
  		$array["success"]=0;
  		$array["msg"]="";
  	  if($microObj==null) return $array;
  	  if(empty($obj))return $array;
  	  $eno=$this->userinfo->getEno();
  	  $login_account_array=explode(",",$obj);
  	  $key=array();
  	  $val=array();
  	  for ($i = 0; $i < count($login_account_array); $i++) {
  	   	  array_push($key,(string)"?");
  	   	  array_push($val,(string)$login_account_array[$i]);
  	  }
  	  array_push($val,(string)$micro_account);
  	  $sql="select a.fafa_jid,a.eno,a.nick_name from we_staff a where fafa_jid in(".implode(",",$key).")";
			$sql.="and not EXISTS (select 1 from we_staff_atten b where a.login_account=b.login_account and b.atten_id=?)";
			
  	  $data=$this->conn->GetData("dt",$sql,$val);
  	  $fafajids=$data==null ? array():$data["dt"]["rows"];
  	  $login_account_array=array();
  	  $login_account_array_eno=array();
  	  $nick_name_array=array();
  	  for ($i = 0; $i < count($fafajids); $i++) {
  	   	  if(!empty($fafajids[$i]["fafa_jid"])&&$fafajids[$i]["eno"]!=$eno){
            if($microObj["micro_use"]==0){
	  	 			  if(!empty($microObj["type"])) array_push($login_account_array,$fafajids[$i]["fafa_jid"]);
  	   	  	  else{$array["msg"]="内部公众号不能邀请外部成员";return $array;}
            }else array_push($login_account_array,$fafajids[$i]["fafa_jid"]);
  	   	  }
  	   	  else array_push($login_account_array_eno,$fafajids[$i]["fafa_jid"]);
  	  }
  	  if(!empty($login_account_array))	$array=$this->inviteatten($micro_account,$login_account_array);
  		if(!empty($login_account_array_eno)){
  			for($j=0;$j < count($login_account_array_eno);$j++)	{
  			  $dataexec=	$this->micro_fans_attention($micro_account,$login_account_array_eno[$j]);
  			  $array["success"]=$dataexec;
  			}
  			$fafa_jid=$this->userinfo->fafa_jid;
		  	 		$container=$this->container;
		  	 		//发送出席消息
		  	  	$dataexec=Utils::sendImPresence($fafa_jid, implode(",",$login_account_array_eno), "agree_invit", 
		  	  	json_encode($microObj), $container,"","",false,Utils::$systemmessage_code);
  			$nick_name=$this->userinfo->nick_name;				
        if($microObj["micro_use"]==0) $message="您已被管理员【". $nick_name."】设置关注公众号【".$microObj["name"]."】";
        else if($microObj["micro_use"]==1) $message="您已被管理员【". $nick_name."】设置关注微应用【".$microObj["name"]."】";  
        else $message="您已被管理员【". $nick_name."】设置关注公众号【".$microObj["name"]."】";	
	  		
		  	//向关注成功的帐号发送消息通知
		  	$this->micro_sendMessage($login_account_array_eno,$message);
  		}
	  	return $array;
	  }
	  //粉丝类型为群组
	  public function micro_fans_group($micro_account,$obj){
  	  $microObj = $this->get_micro_data_account($micro_account);  	
	  	//判断是不是特殊公众号-微应用。不能添加粉丝
	  	//if($microObj["micro_use"]=="8")
	  	//{
	  	//    	return array("success"=>0,"msg"=>"微应用不能添加粉丝");
	  	//}   	    
  	  $array["success"]=0;
  	  $array["msg"]="";
  	  if($microObj==null)return $array;
	  	if(empty($obj))return $array;
	  	
	  	$sql ="select t1.login_account,t2.fafa_jid from we_group_staff t1 left join we_staff t2 on t1.login_account =t2.login_account ";
  		$sql.="where t1.group_id=? and not EXISTS (select 1 from we_staff_atten t3 where t1.login_account=t3.login_account and t3.atten_id=?)";
  		$paras=array((string)$obj,(string)$micro_account);
  		$data=$this->conn->GetData("dt",$sql,$sql);
  		$jids=array();
  		if(!empty($data["dt"]["rows"])){
  			$login_account_array=$data["dt"]["rows"];
  			foreach ($login_account_array as $group) {
  				if(!empty($group["fafa_jid"])){
	  	 			$dataexec=$this->micro_fans_attention($micro_account,$group["login_account"]);
	  	 			if($dataexec==0) array_push($jids, $group["fafa_jid"]);
	  	 			$array["success"]=$dataexec;
	  	 		}
	  		}
  		}
  		$nick_name=$this->userinfo->nick_name;					
      if($microObj["micro_use"]==0) $message="您已被管理员【". $nick_name."】设置关注公众号【".$microObj["name"]."】";
      else if($microObj["micro_use"]==1) $message="您已被管理员【". $nick_name."】设置关注微应用【".$microObj["name"]."】";  
      else $message="您已被管理员【". $nick_name."】设置关注公众号【".$microObj["name"]."】";  
		  	//向关注成功的帐号发送消息通知
		  	$this->micro_sendMessage($jids,$message);
  		//里面是没有关注上的成员帐号
	  	return $array;
	  }
	  public function invite_micro_fans_group($micro_account,$obj){
  	 	$microObj = $this->get_micro_data_account($micro_account);
	  	//判断是不是特殊公众号-微应用。不能添加粉丝
	  	//if($microObj["micro_use"]=="8")
	  	//{
	  	 //   	return array("success"=>0,"msg"=>"微应用不能添加粉丝");
	  	//}  	 	
  	  $array["success"]=0;
  	  $array["msg"]="";
	  	if($microObj==null) return $array;
	  	if(empty($obj))return $array;
	  	
  	  $sql ="select t2.fafa_jid,t2.eno,t2.nick_name from we_group_staff t1 left join we_staff t2 on t1.login_account =t2.login_account ";
  		$sql.="where t1.group_id=? and not EXISTS (select 1 from we_staff_atten t3 where t1.login_account=t3.login_account and t3.atten_id=?)";
  		$paras=array((string)$obj,(string)$micro_account);
  		$data=$this->conn->GetData("dt",$sql,$paras);
  		$jids=array();
  		$login_account_array_eno=array();
  		$eno=$this->userinfo->getEno();
  		if(!empty($data["dt"]["rows"])){
  			$login_account_array=$data["dt"]["rows"];
  			foreach ($login_account_array as $group) {
	  	 		if(!empty($group["fafa_jid"])&&$group["eno"]!=$eno){
            if($microObj["micro_use"]==0){
  	  	 			if(!empty($microObj["type"])) array_push($jids,$group["fafa_jid"]);
  	  	 			else{$array["msg"]="内部公众号不能邀请外部成员";}
            }else  array_push($jids,$group["fafa_jid"]);
	  	 		}
	  	 		else array_push($login_account_array_eno,(string)$group["fafa_jid"]);
	  	 	}
  		}
  		if(!empty($jids)){
  		 $array=$this->inviteatten($micro_account,$jids);
  		}
  		if(!empty($login_account_array_eno)){
  			for($j=0;$j < count($login_account_array_eno);$j++)	{
  			  $dataexec=	$this->micro_fans_attention($micro_account,$login_account_array_eno[$j]);
  			  $array["success"]=$dataexec;
  			}
  			$fafa_jid=$this->userinfo->fafa_jid;
		  	 		$container=$this->container;
		  	 		//发送出席消息
		  	  	$dataexec=Utils::sendImPresence($fafa_jid, implode(",",$login_account_array_eno), "agree_invit", 
		  	  	json_encode($microObj), $container,"","",false,Utils::$systemmessage_code);
  			$nick_name=$this->userinfo->nick_name;					
	  		if($microObj["micro_use"]==0) $message="您已被管理员【". $nick_name."】设置关注公众号【".$microObj["name"]."】";
      else if($microObj["micro_use"]==1) $message="您已被管理员【". $nick_name."】设置关注微应用【".$microObj["name"]."】";  
      else $message="您已被管理员【". $nick_name."】设置关注公众号【".$microObj["name"]."】";  
		  	//向关注成功的帐号发送消息通知
		  	$this->micro_sendMessage($login_account_array_eno,$message);
  		}
  		return $array;
	  }
	  //粉丝类型为圈子
	  public function micro_fans_circle($micro_account,$obj){
	  	$microObj = $this->get_micro_data_account($micro_account);	  
	  	//判断是不是特殊公众号-微应用。不能添加粉丝
	  	//if($microObj["micro_use"]=="8")
	  	//{
	  	 //   	return array("success"=>0,"msg"=>"微应用不能添加粉丝");
	  	//} 	  		  
	  	  $array["success"]=0;
	  	  $array["msg"]="";
	  	  if($microObj==null)return $array;
	  	  if(empty($obj))return $array;
	  	  
	  		$sql ="select t2.fafa_jid,t2.login_account from we_circle_staff t1 left join we_staff t2 on t1.login_account =t2.login_account ";
	  		$sql.="where t1.circle_id=? and not EXISTS (select 1 from we_staff_atten t3 where t1.login_account=t3.login_account and t3.atten_id=?)";
  			$paras=array((string)$obj,(string)$micro_account);
	  		$data=$this->conn->GetData("dt",$sql,$paras);
  			$jids=array();
	  		if(!empty($data["dt"]["rows"])){
	  			$login_account_array=$data["dt"]["rows"];
	  			foreach ($login_account_array as $circle) {
	  				if(!empty($circle["fafa_jid"])){
			  	 		$dataexec=$this->micro_fans_attention($micro_account,$circle["login_account"]);
			  	 		if($dataexec==0) array_push($jids, $circle["fafa_jid"]);
		  	 			$array["success"]=$dataexec;
	  	 			}
	  			}
	  		}
	  		$nick_name=$this->userinfo->nick_name;					
	  		if($microObj["micro_use"]==0) $message="您已被管理员【". $nick_name."】设置关注公众号【".$microObj["name"]."】";
        else if($microObj["micro_use"]==1) $message="您已被管理员【". $nick_name."】设置关注微应用【".$microObj["name"]."】";  
        else $message="您已被管理员【". $nick_name."】设置关注公众号【".$microObj["name"]."】";  
		  	//向关注成功的帐号发送消息通知
		  	$this->micro_sendMessage($jids,$message);
	  		//里面是没有关注上的成员帐号
	  		return $array;
	  }
	  public function invite_micro_fans_circle($micro_account,$obj){
	  	$microObj = $this->get_micro_data_account($micro_account);
	  	//判断是不是特殊公众号-微应用。不能添加粉丝
	  	//if($microObj["micro_use"]=="8")
	  	//{
	  	//    	return array("success"=>0,"msg"=>"微应用不能添加粉丝");
	  	//}  	 	
  	  $array["success"]=0;
  	  $array["msg"]="";
	  	if($microObj==null) return $array;
	  	if(empty($obj))return $array;
	  	
  	  $sql ="select t2.fafa_jid,t2.eno,t2.nick_name from we_circle_staff t1 left join we_staff t2 on t1.login_account =t2.login_account ";
	  	$sql.="where t1.circle_id=? and not EXISTS (select 1 from we_staff_atten t3 where t1.login_account=t3.login_account and t3.atten_id=?)";
  		$paras=array((string)$obj,(string)$micro_account);
  		$data=$this->conn->GetData("dt",$sql,$paras);
  		$jids=array();
  		$login_account_array_eno=array();
  		$eno=$this->userinfo->getEno();
  		if(!empty($data["dt"]["rows"])){
  			$login_account_array=$data["dt"]["rows"];
  			foreach ($login_account_array as $circle) {
  				//判断好友是否同一个企业,并且判断公众号是内部还是外部
	  	 		if(!empty($circle["fafa_jid"])&&$circle["eno"]!=$eno){
            if($microObj["micro_use"]==0){
  	  	 			if(!empty($microObj["type"])) array_push($jids,$circle["fafa_jid"]);
  	  	 			else{$array["msg"]="内部公众号不能邀请外部成员";}
            } else array_push($jids,$circle["fafa_jid"]);
	  	 		}
	  	 		else array_push($login_account_array_eno,(string)$circle["fafa_jid"]);
	  		}
  		}
  		if(!empty($jids)){
  		 $array=$this->inviteatten($micro_account,$jids);
  		}
  		
  		if(!empty($login_account_array_eno)){
  			for($j=0;$j < count($login_account_array_eno);$j++)	{
  			  $dataexec=	$this->micro_fans_attention($micro_account,$login_account_array_eno[$j]);
  			  $array["success"]=$dataexec; 
  			}
  			$fafa_jid=$this->userinfo->fafa_jid;
		  	 		$container=$this->container;
		  	 		//发送出席消息
		  	  	$dataexec=Utils::sendImPresence($fafa_jid, implode(",",$login_account_array_eno), "agree_invit", 
		  	  	json_encode($microObj), $container,"","",false,Utils::$systemmessage_code);
  			$nick_name=$this->userinfo->nick_name;					
	  			if($microObj["micro_use"]==0) $message="您已被管理员【". $nick_name."】设置关注公众号【".$microObj["name"]."】";
        else if($microObj["micro_use"]==1) $message="您已被管理员【". $nick_name."】设置关注微应用【".$microObj["name"]."】";  
        else $message="您已被管理员【". $nick_name."】设置关注公众号【".$microObj["name"]."】";  
		  	//向关注成功的帐号发送消息通知
		  	$this->micro_sendMessage($login_account_array_eno,$message);
  		}
  		return $array;
	  }
	  //粉丝类型为企业
	  public function micro_fans_enterprise($micro_account,$obj) {
	  	  $microObj = $this->get_micro_data_account($micro_account);
  	  	//判断是不是特殊公众号-微应用。不能添加粉丝
  	  	//if($microObj["micro_use"]=="8")
  	  	//{
  	  	//  return array("success"=>0,"msg"=>"微应用不能添加粉丝");
  	  	//} 	  	  
	  	  $array["success"]=0;
	  	  $array["msg"]="";
	  	  if($microObj==null)return $array;
	  	  
	  		$state_id="1";
	  		$sql ="select a.login_account,a.fafa_jid,a.eno from we_staff a ";
	  		$sql.="where not exists (select 1 from we_micro_account b where  b.number=a.login_account) ";
	  		$sql.="and not EXISTS (SELECT 1 from we_staff_atten c where c.login_account=a.login_account and c.atten_id=?) ";
	  		$sql.="and a.state_id=?  and a.eno=?";
	  		$paras=array((string)$micro_account,(string)$state_id);
	  		if(empty($obj))array_push($paras,(string)$this->userinfo->getEno());
	  		else array_push($paras,(string)$obj);
	  		$data=$this->conn->GetData("dt",$sql,$paras);
  			$jids=array();
	  		if(!empty($data["dt"]["rows"])){
	  			$login_account_array=$data["dt"]["rows"];
		  		foreach ($login_account_array as $enterprise) {
		  			if(!empty($enterprise["login_account"])){
			  	 		$dataexec=$this->micro_fans_attention($micro_account,$enterprise["login_account"]);
			  	 		if($dataexec==0) array_push($jids, $enterprise["fafa_jid"]);
		  	 			$array["success"]=$dataexec;
	  	 			}
	  			}
	  			$fafa_jid=$this->userinfo->fafa_jid;
	  	 		$container=$this->container;
	  	 		//发送出席消息
	  	  	$dataexec=Utils::sendImPresence($fafa_jid,implode(",",$jids), "agree_invit", 
	  	  	json_encode($microObj), $container,"","",false,Utils::$systemmessage_code);
	  			 $nick_name=$this->userinfo->nick_name;					
	  			if($microObj["micro_use"]==0) $message="您已被管理员【". $nick_name."】设置关注公众号【".$microObj["name"]."】";
        else if($microObj["micro_use"]==1) $message="您已被管理员【". $nick_name."】设置关注微应用【".$microObj["name"]."】";  
        else $message="您已被管理员【". $nick_name."】设置关注公众号【".$microObj["name"]."】";  
		  	//向关注成功的帐号发送消息通知
		  	$this->micro_sendMessage($jids,$message);
	  		} 
	  		//里面是没有关注上的成员帐号
	  		return $array;
	  }
	  //关注自己所属企业的内部开放公众号和外部公众号
	  public function attenCompanyOpenAccount($micro_account=null){
	  	$login_account=$this->account;
	  	$dataexec = true;
	  	if(empty($micro_account))
	  	{
	  		$list = $this->getmicroaccount();
	  		for($i=0; $i<count($list); $i++)
	  		{
	  			  if( $list[$i]["type"]=="1"  || ($list[$i]["type"]=="0" && $list[$i]["concern_approval"]=="1"))
	  		        $dataexec=$this->micro_fans_attention($list[$i]["number"],$login_account);
	  	  }
	  	}
	  	else
	  	   $dataexec=$this->micro_fans_attention($micro_account,$login_account);
	  	return $dataexec;
	  }
	  //邀请好友关注指定的公众号
	  public function inviteatten($micro_account,$inviteAccount){
	  	 $microObj = $this->get_micro_data_account($micro_account);
	  	 $array["success"]=0;
	  	 if($microObj==null) return $array["success"]=1;
	  	 
	  	 $nick_name=$this->userinfo->nick_name;						
       if($microObj["micro_use"]==0) {
        $title="公众号邀请关注信息";
        $message="【".$nick_name."】邀请您关注公众号【".$microObj["name"]."】";
       }
      else if($microObj["micro_use"]==1) {
        $title="微应用邀请关注信息";
        $message="【".$nick_name."】邀请您关注微应用【".$microObj["name"]."】";
      }
        else {
          $title="公众号邀请关注信息";
        $message="【".$nick_name."】邀请您关注公众号【".$microObj["name"]."】";
        }
       
       
	     $array=$this->inviteatten_micro_message($title,$message,$microObj["number"],$inviteAccount,"1","1");
	     return $array;
	  }
	  
	  //同意关注指定公众号  micro_account公众号 inviteaccount邀请人或申请人  invite(1)邀请关注 为空查询关注
	  public function agree_inviteatten($micro_account,$inviteaccount,$invite){
	  	$microObj=$this->get_micro_data_account($micro_account);
	  	$array["success"]=1;
	  	if($microObj==null) return $array["success"]=1;
	  	 
	  	if($invite=="1"||$invite==1){ //是邀请的 无需管理员审批
	  		$login_account=$this->account; //当前登录人帐号
	  	  $dataexec=$this->micro_fans_attention($micro_account,$login_account);
	  	  $array["success"]=$dataexec;//0 关注成功 1 关注失败 
	  	  
	  	  if($dataexec==0){//关注成功  发送出席消息
  	  		$fafa_jid=$this->userinfo->fafa_jid;
	  	 		$container=$this->container;
	  	 		try {
	  	 			//发送出席消息
	  	  		Utils::sendImPresence($inviteaccount, $fafa_jid, "agree_invit", json_encode($microObj), 
	  	  		$container,"","",false,Utils::$systemmessage_code);
	  	  		$array["success"]=0;
	  	  	} catch (\Exception $exc) {
	  	  		$this->logger->err($exc);
	      		$array["success"]=1;
	  	  	}
  	  	}
	  	}else{ //由用户自助查询关注 需要公众号对应企业的管理员审批 (向管理员发送消息)
	  		if(empty($microObj["concern_approval"])||$microObj["concern_approval"]==0||$microObj["concern_approval"]=="0"){ //私密公众号
	  			//$eno=$this->userinfo->getEno();
		  		$sys_manager=$this->getSysManager($microObj["eno"]);
		  		$nick_name=$this->userinfo->nick_name;
		  		if($microObj["micro_use"]==0) {
            $title="公众号申请关注信息";
            $message="【".$nick_name."】申请关注公众号【".$microObj["name"]."】,请您审批";
          }
          else if($microObj["micro_use"]==1) {
            $title="微应用申请关注信息";
            $message="【".$nick_name."】申请关注微应用【".$microObj["name"]."】,请您审批";
          }
          else {
            $title="公众号申请关注信息";
            $message="【".$nick_name."】申请关注公众号【".$microObj["name"]."】,请您审批";
          }
		  		//var_dump($sys_manager);
		  		$array=$this->inviteatten_micro_message($title,$message,$microObj["number"],$sys_manager,"0","0");
          //var_dump($array,$microObj);
	  		}else{ //开放公众号
	  			$login_account=$this->account; //当前登录人帐号
		  	  $dataexec=$this->micro_fans_attention($micro_account,$login_account);
		  	  $array["success"]=$dataexec;//0 关注成功 1 关注失败 
          if(empty($dataexec)){
            $fafa_jid=$this->userinfo->fafa_jid;
            $container=$this->container;
            //var_dump($fafa_jid,$microObj);
            //发送出席消息
            Utils::sendImPresence($fafa_jid, $fafa_jid, "agree_invit", json_encode($microObj), 
            $container,"","",false,Utils::$systemmessage_code);
          }
	  		}
	  	}
	  	return $array;
	  }
    //获取指定企业的管理员
    private function getSysManager($eno){
        $sql_eno="(SELECT GROUP_CONCAT(a.fafa_jid separator';') as sys_manager FROM we_staff a WHERE (SELECT b.sys_manager FROM we_enterprise b WHERE b.eno=?) LIKE CONCAT('%',a.login_account,'%') AND a.eno=? )";
        $paras_eno=array((string)$eno,(string)$eno);
        $data_eno=$this->conn->GetData("dt",$sql_eno,$paras_eno);
        if($data_eno!=null && count($data_eno["dt"]["rows"])>0 ){
          $sys_manager=$data_eno["dt"]["rows"][0]["sys_manager"];
          $sys_manager=explode(";",$sys_manager);
          return $sys_manager;
        }
        else{
          return "";
        }
    }
	  //管理员同意关注
	  public function manager_agree_inviteatten($micro_account,$inviteaccount,$invite){
	  		$microObj=$this->get_micro_data_account($micro_account);
		  	$array["success"]=0;
		  	if($microObj==null) return $array["success"]=1;
		  	
	  	  $dataexec=$this->micro_fans_attention($micro_account,$inviteaccount);
	  	  $array["success"]=$dataexec;//0 系统异常 1 保存失败 2 保存成功 3 公众号不存在
	  	   
	  	  try { 	
	  	  	$fafa_jid=$this->userinfo->fafa_jid;
          //var_dump($fafa_jid,$inviteaccount,json_encode($microObj));
	  	 		$container=$this->container;
	  	 		//发送出席消息
	  	  	$dataexec=Utils::sendImPresence($fafa_jid, $inviteaccount, "agree_invit", json_encode($microObj), $container,"","",false,Utils::$systemmessage_code);
          //var_dump($dataexec);
	  	  } catch (\Exception $exc) {
	  	  	$this->logger->err($exc);
	      	$array["success"]=1;
	  	  }
	  	  return $array;
	  }
	  //发送消息给操作人   参数说明 title标题 message消息内容 micro_account公众号 
	  //tojid接收人array数组类型 invite(1)无需管理员审核 cc是否抄送邮箱(0不抄送 1要抄送) 
	  private function inviteatten_micro_message($title,$message,$micro_account,$tojid,$invite,$cc){
	  	 $login_account=$this->account; //当前登录人帐号
	  	 $fafa_jid=$this->userinfo->fafa_jid;
	  	 $container=$this->container;
	  	 $agree_link_url="";
	  	 $refuse_link_url=$container->get('router')->generate("JustsyInterfaceBundle_micraccount_invite_reject",array("microaccount"=> $micro_account,"inviteaccount"=>$fafa_jid,"invite"=>$invite),true);
	  	 if(!empty($invite)){
	  	 	//参数说明 micro_account关注公众号  inviteAccount邀请人登录帐号  invite(0)是否是邀请(可能是搜索关注,如果是私密需要管理员审批)
	  	 	$agree_link_url=$container->get('router')->generate("JustsyInterfaceBundle_micraccount_invite_agree",array("microaccount"=> $micro_account,"inviteaccount"=>$fafa_jid,"invite"=>$invite),true);
	  	 }else{
	  	  $agree_link_url=$container->get('router')->generate("JustsyInterfaceBundle_micraccount_manager_agree_invite",array("microaccount"=> $micro_account,"inviteaccount"=>$fafa_jid,"invite"=>$invite),true);
	  	 }
       $linkButtons=Utils::makeBusButton(array(array("code"=>"refuse","text"=>"拒绝","value"=>"refuse","link"=>$refuse_link_url)
       																				,array("code"=>"agree","text"=>"同意","value"=> "agree","link"=>$agree_link_url)));
       $array["success"]=0;
       try {
       	//var_dump($fafa_jid,implode(",",$tojid));
	       	//开始发送消息
        	$dataexec=Utils::sendImMessage($fafa_jid, implode(",",$tojid), $title, $message, $container,"",$linkButtons,false,Utils::$systemmessage_code,$cc);
	      	$array["success"]=0;
	      } catch (\Exception $exc) { 
	      	$this->logger->err($exc);
	      	$array["success"]=1;
	      }
	      return $array;
	  }
	  //拒绝关注邀请  关注指定的公众号
	  public function reject_inviteatten($micro_account,$inviteaccount,$invite){
	  	$microObj=$this->get_micro_data_account($micro_account);
	  	$array["success"]=0;
	  	if($microObj==null) return $array["success"]=1;
	  	if(!empty($invite)){//邀请关注 被拒绝
	  		$nick_name=$this->userinfo->nick_name;
        if($microObj["micro_use"]==0) {
          $title="公众号关注信息";
          $message="【".$nick_name."】已拒绝关注公众号【".$microObj["name"]."】";  
        }else if($microObj["micro_use"]==1){
          $title="微应用关注信息";
          $message="【".$nick_name."】已拒绝关注微应用【".$microObj["name"]."】";
        }else {
          $title="公众号关注信息";
          $message="【".$nick_name."】已拒绝关注公众号【".$microObj["name"]."】";
        }
	  		//$inviteaccount=explode(";",$inviteaccount);
	  		$array=$this->reject_inviteatten_micro_message($title,$message,$inviteaccount);
	  	}else{//主动关注被管理员拒绝
        if($microObj["micro_use"]==0) {
          $title="公众号关注信息";
          $message="公众号【".$microObj["name"]."】拒绝了您的关注请求";
        }else if($microObj["micro_use"]==1){
          $title="微应用关注信息";
          $message="微应用【".$microObj["name"]."】拒绝了您的关注请求"; 
        }else {
          $title="公众号关注信息";
          $message="公众号【".$microObj["name"]."】拒绝了您的关注请求";  
        }
	  		
	  		$array=$this->reject_inviteatten_micro_message($title,$message,$inviteaccount);
	  	}
	  	return $array;
	  }
	  //发送消息给邀请人  参数说明 title标题 message消息内容 micro_account公众号 tojid接收人string类型多个逗号分隔开
	  private function reject_inviteatten_micro_message($title,$message,$tojid){
	  	 $fafa_jid=$this->userinfo->fafa_jid;
	  	 $container=$this->container;
	  	 $array["success"]=0;
       try {
	       	//开始发送消息
        	$dataexec=Utils::sendImMessage($fafa_jid, $tojid, $title, $message, $container,"","",false,Utils::$systemmessage_code,"0");
	      	$array["success"]=0;
	      } catch (\Exception $exc) { 
	      	$this->logger->err($exc);
	      	$array["success"]=1;
	      }
	      return $array;
	  }
	  //同意关注邀请  公众号  邀请人  (公众号邀请)无需发送消息
	  public function agreeinvite($micro_account,$inviteaccount){
  		$login_account=$this->account;
  	  $dataexec=$this->micro_fans_attention($micro_account,$login_account);
  		return $dataexec;
	  }
	  //拒绝关注邀请  公众号  邀请人  (公众号邀请)须发送消息
	  public function rejectinvite($micro_account,$inviteaccount){
	  		$sqls=array();
	  		$paras=array();
	  		$login_account=$this->userinfo->getUsername();
	  		$nick_name=$this->userinfo->nick_name;
	  	  $atten_type="01";
	  	  
	  	  $sql="select fafa_jid from we_staff where login_account=?";
	  	  $paras=array((string)$inviteaccount);
	  	  $data=$this->conn->GetData("dt",$sql,$paras);
	  	  $tofafa_jid=$data["dt"]["rows"][0]["fafa_jid"];
	  	  $sql_micro="select name,micro_use from we_micro_account where number=?";
	  	  $paras_micro=array((string)$micro_account);
	  	  $data_micro=$this->conn->GetData("dt",$sql_micro,$paras_micro);
	  	  $micro_name=$data_micro["dt"]["rows"][0]["name"];
        $micro_use=$data_micro["dt"]["rows"][0]["micro_use"];
        if($micro_use==0){
          $title="公众号关注消息";
          $message="【".$nick_name."】拒绝了您邀请关注的公众号【".$micro_name."】";
        }else if($micro_use==1){
          $title="微应用关注消息";
          $message="【".$nick_name."】拒绝了您邀请关注的微应用【".$micro_name."】";
        }else {
          $title="公众号关注消息";
          $message="【".$nick_name."】拒绝了您邀请关注的公众号【".$micro_name."】";
        }
        
	  	  try { 	
	       	//开始发送内容消息
        	$dataexec=Utils::sendImMessage($this->user->fafa_jid, $tofafa_jid, $title, $message, $this->container,"","",false,Utils::$systemmessage_code,"1");
	      	return $dataexec;
	      } catch (\Exception $exc) {
	      	$this->logger->err($exc);
	      	return false;
	      }
	  }
	  
	  //粉丝取消关注
	  public function micro_fans_unfollow($micro_account,$login_account){
	  		$sqls=array();
	  	  $paras=array();
	  	  $atten_type="01";
        $sqls[] = "delete from we_micro_account_group_re where micro_account=? and login_account=? ";
        $paras[] = array((string)$micro_account,(string)$login_account);
	  	  $sqls[] = "delete from we_staff_atten where login_account=? and  atten_type=? and atten_id=? ";
	      $paras[] = array((string)$login_account,(string)$atten_type,(string)$micro_account);
	      $sqls[] = "update we_micro_account set fans_count=fans_count-1 where number=?";
	  		$paras[]= array((string)$micro_account);
	      //每个取消关注扣除0.2分
	      $sqls[] = "insert into we_staff_points (login_account,point_type,point_desc,point,point_date)
	          values (?,?,?,?,now())";
	      $paras[] = array(
	          (string)$login_account,
	          (string)'08',
	          (string)'取消关注'.$micro_account.'，扣除已获得积分0.2',
	          (float)-0.2);
	      try { 	
	       	$dataexec=$this->conn->ExecSQLs($sqls,$paras);
	      	return $dataexec;
	      } catch (\Exception $exc) { 
	      	$this->logger->err($exc);
	      	return false;
	      }
	  }
	  //粉丝未分组个数
	  public function get_fans_ungrouped_count($micro_account){
	  	$atten_type="01";
	  	$sql="select count(*) as count from we_staff_atten where atten_id=? and atten_type=?";
	  	$paras=array((string)$micro_account,(string)$atten_type);
	  	$data=$this->conn->GetData("dt",$sql,$paras);
	  	$count["max_count"]=$data["dt"]["rows"][0]["count"];
	  	$sql1="select count(*) as count from we_micro_account_group_re where micro_account=? ";
	  	$paras1=array((string)$micro_account);
	  	$data1=$this->conn->GetData("dt",$sql1,$paras1);
	  	$count["group_count"]=$data1["dt"]["rows"][0]["count"];
	  	return $count;
	  }
	  //获取未分组粉丝集合
	  public function get_fans_ungrouped($micro_account){
	  	$atten_type="01";
	  	$sql ="select c.login_account,c.nick_name,c.openid,c.fafa_jid from  we_staff_atten b ";
	  	$sql.="left JOIN we_staff c on b.login_account=c.login_account where b.atten_id=? and b.atten_type=? ";
	  	$sql.="and not EXISTS (select 1 from we_micro_account_group_re a where b.atten_id=a.micro_account and b.login_account=a.login_account);";
	  	$dataexec=$this->conn->GetData("dt",$sql,array((string)$micro_account,(string)$atten_type));
	  	$data=$dataexec==null||count($dataexec["dt"]["rows"])==0 ? null : $dataexec["dt"]["rows"];
	  	return $data;
	  }
	  
	  //获取对应公众号粉丝列表
	  public function get_micro_fans($micro_account,$txtsearch,$micro_page_size,$micro_page_index,$groupid){
	  	$array["micro_fans_count"]=0;
	  	$array["micro_page_max_index"]=0;
	  	$array["micro_fans_data"]=[];
	  	$ischina= preg_match("/[\x80-\xff]./", $txtsearch); 
	  	if(!empty($micro_account)){
	  		$atten_type="01";  
	  		if(empty($groupid)){ //未分组粉丝
		  		$sql_count ="select count(*) as count ";
			  	$sql_count.="from we_staff_atten t1 left join we_staff t2 on t1.login_account=t2.login_account left join we_enterprise t3 on t2.eno=t3.eno ";
		  		//$sql_count ="select count(*) as count from we_staff_atten t1 left join we_staff t2 on t1.login_account=t2.login_account ";
		  		$paras_count=array((string)$micro_account,(string)$atten_type);
		  		if(empty($txtsearch)){
		  			$sql_count.="where t1.atten_id=? and t1.atten_type=? and not exists( select 1 from we_micro_account_group_re a where a.login_account=t1.login_account and  a.micro_account=t1.atten_id);";
		  		}else{
		  			if($ischina){
			  			$sql_count.="where t1.atten_id=? and t1.atten_type=? and  not exists( select 1 from we_micro_account_group_re a where a.login_account=t1.login_account and  a.micro_account=t1.atten_id) and t2.nick_name like BINARY CONCAT('%',?,'%') ;";
			  			array_push($paras_count,(string)$txtsearch);
		  			}else{
		  				$sql_count.="where t1.atten_id=? and t1.atten_type=? and not exists( select 1 from we_micro_account_group_re a where a.login_account=t1.login_account and  a.micro_account=t1.atten_id ) and (t1.login_account like BINARY CONCAT('%',?,'%') or t2.nick_name like BINARY CONCAT('%',?,'%')) ;";
			  			array_push($paras_count,(string)$txtsearch);
			  			array_push($paras_count,(string)$txtsearch);
		  			}
		  		} 
		  		$data_count=$this->conn->GetData("dt",$sql_count,$paras_count);
		  		$array["micro_fans_count"]=$data_count["dt"]["rows"][0]["count"]; 
		  	//var_dump($sql_count);
		  		if($data_count["dt"]["rows"][0]["count"]>0){
		  			//计算总共多少页
		  			$array["micro_page_max_index"]=ceil($data_count["dt"]["rows"][0]["count"]*1.0/$micro_page_size);
			  		$sql ="select t1.login_account,t2.nick_name,t2.eno,t3.ename,t3.eshortname,t1.atten_date,t2.photo_path ";
			  		$sql.="from we_staff_atten t1 left join we_staff t2 on t1.login_account=t2.login_account left join we_enterprise t3 on t2.eno=t3.eno  ";
			  		//$sql ="select t1.login_account,t2.nick_name from we_staff_atten t1 left join we_staff t2 on t1.login_account=t2.login_account ";
			  		$paras=array((string)$micro_account,(string)$atten_type);
			  		//array_push($paras,(string)$micro_account);
			  		//array_push($paras,(string)$atten_type);
			  		$start_index=$micro_page_size * ($micro_page_index - 1);
			  		if(empty($txtsearch)){
			  			$sql.="where t1.atten_id=? and t1.atten_type=? ";
			  			$sql.="and not exists( select 1 from we_micro_account_group_re a ";
			  			$sql.="where a.login_account=t1.login_account and  a.micro_account=t1.atten_id) ";
			  			$sql.="order by t1.atten_date desc,t2.eno,t1.login_account limit ".$start_index.",".$micro_page_size." ";
			  		}else{
			  			if($ischina){
			  				$sql.="where t1.atten_id=? and t1.atten_type=? ";
			  				$sql.="and  not exists( select 1 from we_micro_account_group_re a ";
			  				$sql.="where a.login_account=t1.login_account and  a.micro_account=t1.atten_id) and t2.nick_name like BINARY CONCAT('%',?,'%') ";
			  				$sql.="order by t1.atten_date desc,t2.eno,t1.login_account limit ".$start_index.",".$micro_page_size." ";
			  				array_push($paras,(string)$txtsearch);
			  			}else{
			  				$sql.="where t1.atten_id=? and t1.atten_type=? ";
			  				$sql.="and  not exists( select 1 from we_micro_account_group_re a ";
			  				$sql.="where a.login_account=t1.login_account and  a.micro_account=t1.atten_id) ";
			  				$sql.="and (t1.login_account like BINARY CONCAT('%',?,'%') or t2.nick_name like BINARY CONCAT('%',?,'%')) ";
			  				$sql.="order by t1.atten_date desc,t2.eno,t1.login_account limit ".$start_index.",".$micro_page_size." ";
			  				array_push($paras,(string)$txtsearch);
			  				array_push($paras,(string)$txtsearch);
			  			}
			  		}
			  		//var_dump($sql,$paras);
			  		$data=$this->conn->GetData("dt",$sql,$paras);
					  $array["micro_fans_data"]=$data["dt"]["rows"]; 
		  		}
	  		}else if($groupid==-1){ //所有粉丝
	  			$sql_count ="select count(*) as count ";
			  	$sql_count.="from we_staff_atten t1 left join we_staff t2 on t1.login_account=t2.login_account left join we_enterprise t3 on t2.eno=t3.eno ";
		  		$paras_count=array((string)$micro_account);
		  		if(empty($txtsearch)){
		  			$sql_count.="where t1.atten_id=? ;";
		  		}else{
		  			if($ischina){
			  			$sql_count.="where t1.atten_id=? and t2.nick_name like BINARY CONCAT('%',?,'%') ";
			  			array_push($paras_count,(string)$txtsearch);
		  			}else{
		  				$sql_count.="where t1.atten_id=? and (t1.login_account like BINARY CONCAT('%',?,'%') or t2.nick_name like BINARY CONCAT('%',?,'%')) ";
			  			array_push($paras_count,(string)$txtsearch);
			  			array_push($paras_count,(string)$txtsearch);
		  			}
		  		}
		  		$data_count=$this->conn->GetData("dt",$sql_count,$paras_count);
		  		$array["micro_fans_count"]=$data_count["dt"]["rows"][0]["count"]; 
		  		if($data_count["dt"]["rows"][0]["count"]>0){
		  			//计算总共多少页
		  			$array["micro_page_max_index"]=ceil($data_count["dt"]["rows"][0]["count"]*1.0/$micro_page_size);
			  		$sql ="select t1.login_account,t2.nick_name,t2.eno,t3.ename,t3.eshortname,t2.photo_path,t1.atten_date ";
			  		$sql.="from we_staff_atten t1 left join we_staff t2 on t1.login_account=t2.login_account left join we_enterprise t3 on t2.eno=t3.eno ";
			  		$paras=array((string)$micro_account);
			  		$start_index=$micro_page_size * ($micro_page_index - 1);
			  		if(empty($txtsearch)){
			  			$sql.="where t1.atten_id=? order by atten_date desc,t2.eno,t1.login_account limit ".$start_index.",".$micro_page_size." ";
			  		}else{
			  			if($ischina){
			  				$sql.="where t1.atten_id=? and t2.nick_name like BINARY CONCAT('%',?,'%') ";
			  				$sql.="order by atten_date desc,t2.eno,t1.login_account limit ".$start_index.",".$micro_page_size." ";
			  				array_push($paras,(string)$txtsearch);
			  			}else{
			  				$sql.="where t1.atten_id=? ";
			  				$sql.="and (t1.login_account like BINARY CONCAT('%',?,'%') or t2.nick_name like BINARY CONCAT('%',?,'%')) ";
			  				$sql.="order by atten_date desc,t2.eno,t1.login_account limit ".$start_index.",".$micro_page_size." ";
			  				array_push($paras,(string)$txtsearch);
			  				array_push($paras,(string)$txtsearch);
			  			}
			  		}
			  		//var_dump($sql,$paras);
			  		$data=$this->conn->GetData("dt",$sql,$paras);
					  $array["micro_fans_data"]=$data["dt"]["rows"]; 
		  		}
	  		}else{ //指定分组粉丝
	  			$sql_count ="select count(*) as count ";
			  	$sql_count.="from we_micro_account_group_re t1 left join we_staff t2 on t1.login_account=t2.login_account left join we_enterprise t3 on t2.eno=t3.eno ";
		  		$paras_count=array((string)$micro_account,(string)$groupid);
		  		if(empty($txtsearch)){
		  			$sql_count.="where t1.micro_account=? and t1.groupid=? ;";
		  		}else{
		  			if($ischina){
			  			$sql_count.="where t1.micro_account=? and t1.groupid=? and t2.nick_name like BINARY CONCAT('%',?,'%') ";
			  			array_push($paras_count,(string)$txtsearch);
		  			}else{
		  				$sql_count.="where t1.micro_account=? and t1.groupid=? and (t1.login_account like BINARY CONCAT('%',?,'%') or t2.nick_name like BINARY CONCAT('%',?,'%')) ";
			  			array_push($paras_count,(string)$txtsearch);
			  			array_push($paras_count,(string)$txtsearch);
		  			}
		  		} 
		  		$data_count=$this->conn->GetData("dt",$sql_count,$paras_count);
		  		$array["micro_fans_count"]=$data_count["dt"]["rows"][0]["count"]; 
		  		if($data_count["dt"]["rows"][0]["count"]>0){
		  			//计算总共多少页
		  			$array["micro_page_max_index"]=ceil($data_count["dt"]["rows"][0]["count"]*1.0/$micro_page_size);
			  		$sql ="select t1.login_account,t2.nick_name,t2.eno,t3.ename,t3.eshortname,t2.photo_path,(select atten_date from we_staff_atten a where t1.login_account=a.login_account and t1.micro_account=a.atten_id) as atten_date ";
			  		$sql.="from we_micro_account_group_re t1 left join we_staff t2 on t1.login_account=t2.login_account left join we_enterprise t3 on t2.eno=t3.eno ";
			  		$paras=array((string)$micro_account,(string)$groupid);
			  		$start_index=$micro_page_size * ($micro_page_index - 1);
			  		if(empty($txtsearch)){
			  			$sql.="where t1.micro_account=? and t1.groupid=? order by atten_date desc,t2.eno,t1.login_account limit ".$start_index.",".$micro_page_size." ";
			  		}else{
			  			if($ischina){
			  				$sql.="where t1.micro_account=? and t1.groupid=? and t2.nick_name like BINARY CONCAT('%',?,'%') ";
			  				$sql.="order by atten_date desc,t2.eno,t1.login_account limit ".$start_index.",".$micro_page_size." ";
			  				array_push($paras,(string)$txtsearch);
			  			}else{
			  				$sql.="where t1.micro_account=? and t1.groupid=? ";
			  				$sql.="and (t1.login_account like BINARY CONCAT('%',?,'%') or t2.nick_name like BINARY CONCAT('%',?,'%')) ";
			  				$sql.="order by atten_date desc,t2.eno,t1.login_account limit ".$start_index.",".$micro_page_size." ";
			  				array_push($paras,(string)$txtsearch);
			  				array_push($paras,(string)$txtsearch);
			  			}
			  		}
			  		//var_dump($sql,$paras);
			  		$data=$this->conn->GetData("dt",$sql,$paras);
					  $array["micro_fans_data"]=$data["dt"]["rows"]; 
					}
	  		}
	  	}
	  	return $array;
	  }
	  //根据公众号获取所有粉丝数据(帐号,昵称,openid,fafajid)
	  public function get_micro_all_fans($microaccount){
	  	$atten_type="01";
	  	$sql ="select c.login_account,c.nick_name,c.openid,c.fafa_jid,c.eno ";
	  	$sql.= "from  we_staff_atten b left JOIN we_staff c on b.login_account=c.login_account ";
	  	$sql.= "where b.atten_id=? and b.atten_type=?;";
	  	$dataexec=$this->conn->GetData("dt",$sql,array((string)$microaccount,(string)$atten_type));
	  	$data=$dataexec==null||count($dataexec["dt"]["rows"])==0 ? null : $dataexec["dt"]["rows"];
	  	return $data;
	  }
	  //获取粉丝分组成员
	  public function get_micro_fans_group($micro_account,$group_id){
	  	$sql ="select c.login_account,c.nick_name,c.openid,c.fafa_jid,c.eno ";
	  	$sql.= "from we_micro_account_group_re a left join we_staff c on a.login_account=c.login_account ";
	  	$sql.= "where a.micro_account=? and a.groupid=?";
	  	$paras=array((string)$micro_account,(string)$group_id);
	  	$dataexec=$this->conn->GetData("dt",$sql,$paras);
	  	$data=$dataexec==null||count($dataexec["dt"]["rows"])==0 ? null : $dataexec["dt"]["rows"];
	  	return $data;
	  }
	  //检测分组主键是否存在
	  public function check_micro_fans_groupid($micro_account,$group_id){
	  	$sql="select count(*) as count from we_micro_account_group where micro_account=? and id=?;";
	  	$paras=array((string)$micro_account,(string)$group_id);
	  	$dataexec=$this->conn->GetData("dt",$sql,$paras);
	  	$count=$dataexec==null||count($dataexec["dt"]["rows"])==0 ? 0 : $dataexec["dt"]["rows"][0]["count"];
	  	return $count;
	  }

    public function check_atten($microaccount) {
      $sql="select count(1) as count from we_micro_account a left join we_staff_atten b on a.number=b.atten_id where b.atten_type='01' and b.login_account=? and (a.number=? or a.jid=?);";
      $login_account=$this->userinfo->getUsername();
      $paras=array((string)$login_account,(string)$microaccount,(string)$microaccount);
      try {
        $data = $this->conn->GetData('dt',$sql, $paras);
        if ($data!=null&&count($data['dt']['rows'])>0) return $data['dt']['rows'][0]['count'];
        return 0;
      } catch (\Exception $exc) {
        return -1;
      }
    }
	  
	  //修改公众号LOGO标志接口
	  public function change_logo_path($id,$logo_path_big,$logo_path,$logo_path_small){
	  	$sqls[] ="update we_micro_account set logo_path=?,logo_path_big=?,logo_path_small=? where id=?";
	  	$paras[] =array((string)$logo_path,(string)$logo_path_big,(string)$logo_path_small,(string)$id);
      $sqls[] ="update we_staff SET photo_path=?,photo_path_big=?,photo_path_small=? where login_account=(select b.number from we_micro_account b where b.id=? limit 0,1);";
      $paras[]=array((string)$logo_path,(string)$logo_path_big,(string)$logo_path_small,(string)$id);
	  	try {
       	return $this->conn->ExecSQLs($sqls, $paras);
      } catch (\Exception $exc) {
       	return false;
      }
	  }
	  
	  //初始化读取对应全部公众号
	  public function get_micro_data($micro_use=0){
        if(empty($micro_use)) $micro_use=0;
	  		$sql = "select `id`, `number`, `name`, `jid`, `type`,`micro_use`, `logo_path`, `logo_path_big`, `logo_path_small`";
	  		$sql.= ", `introduction`, `eno`, `limit`, `concern_approval`, `level`,  `window_template`, `salutatory`, `send_status`";
	  		$sql.= ", `send_datetime`, `create_account`, `create_datetime`,`micro_source`";
	  		$sql.= ",(select count(1) as count from we_staff_atten where atten_id=number) as fans_count  from we_micro_account where eno=? and micro_use=?";
        $paras = array((string) $this->userinfo->eno,$micro_use);
        $dataset = $this->conn->GetData('dt', $sql, $paras);
        $data = $dataset["dt"]["rows"];
        return $data;
	 	}
	 	//根据ID获取对应公众号
	 	public function get_micro_data_id($micro_id){
	  		$sql = "select `id`, `number`, `name`, `jid`, `type`,`micro_use`, `logo_path`, `logo_path_big`, `logo_path_small`";
	  		$sql.= ", `introduction`, `eno`, `limit`, `concern_approval`, `level`,  `window_template`, `salutatory`, `send_status`";
	  		$sql.= ", `send_datetime`, `create_account`, `create_datetime`,`micro_source`";
	  		$sql.= ",(select count(*) from we_staff_atten where atten_id=number) as fans_count from we_micro_account where id=?";
        $paras = array((string) $micro_id);
        $dataset = $this->conn->GetData('dt', $sql, $paras);
        $data = $dataset["dt"]["rows"];
        return $data;
	 	}
	 	
	 	//根据帐号获取对应公众号
	 	public function get_micro_data_account($micro_account){
	  		$sql = "select `id`, `number`, `name`, `jid`, `type`,`micro_use`, `logo_path`, `logo_path_big`, `logo_path_small`";
	  		$sql.= ", `introduction`, `eno`, `limit`, `concern_approval`, `level`,  `window_template`, `salutatory` ,`micro_source`";
	  		$sql.= "from we_micro_account where number=? ";
        $paras = array((string) $micro_account);
        $dataset = $this->conn->GetData('dt', $sql, $paras);
        $data = $dataset==null||count($dataset["dt"]["rows"])==0 ? null: $dataset["dt"]["rows"][0];
        return $data;
	 	}
	 	
	 	//获取当前企业拥有对应公众号个数 和内部公众号个数
	 	public function getmicrocount($micro_use=0){
      if(empty($micro_use)) $micro_use=0;
	 		$sql ="select (select count(*) from we_micro_account where eno=? and micro_use=?) as allcount,(select count(*)  ";
	 		$sql.= "from we_micro_account where eno=? and type='0' and micro_use=?) as count ";
	 		$paras=array((string)$this->userinfo->getEno(),$micro_use,(string)$this->userinfo->getEno(),$micro_use);
	 		$dataset = $this->conn->GetData('dt', $sql, $paras);
	 		$data = $dataset==null||count($dataset["dt"]["rows"])==0 ? 0: $dataset["dt"]["rows"][0];
      return $data;
	 	}
	 	
	  //获取当前企业所有公众号
	  //$excludeatten:是否排除当前人员已关注的公众号，默认为不排除
	  public function getmicroaccount($excludeatten=false,$micro_use=0){
      if(empty($micro_use)) $micro_use=0;
	  	$sql = "select a1.*,a2.appkey from (select DISTINCT '' as grouplist,'' im_state,'' im_resource,'' im_priority, t1.id, `number`, `name`, `jid`, `type`";
	  	$sql.= ",`micro_use`, `logo_path`, `logo_path_big`, `logo_path_small`, `introduction`, `limit`, `concern_approval`, `level`,`micro_source`";
	  	$sql.= ", (select count(1) as count from we_staff_atten where atten_id=number) as fans_count, `window_template`, `salutatory`,t2.openid ";
	  	$sql.= "from we_micro_account t1 left join we_staff t2 on t1.number=t2.login_account where t1.concern_approval=1 and t1.eno=? and micro_use=? ";
      $paras = array((string) $this->userinfo->getEno(),$micro_use);
      if($excludeatten!==false){
         	$sql.= " and not exists (select 1 from we_staff_atten where login_account=? and atten_type='01' and atten_id=t1.number)";
         	$paras[]=(string)$this->account;
      }
      $sql.= " order by type asc,concern_approval asc,id asc) as a1 left join we_appcenter_apps a2 on a1.micro_source= a2.appid ;";
      $dataset = $this->conn->GetData('dt', $sql, $paras);
      $data = $dataset["dt"]["rows"];     
      if($micro_use===1)
      {
        //查询微应用时，才获取其在线状态
        $state = $this->getConnState($data);

        for($i=0; $i<count($data); $i++)
        {
        	 $jid = $data[$i]["jid"];
        	 if(!empty($state[$jid]))
        	 {
        	 	  $data[$i]["im_state"] = $state[$jid]["state"];
        	 	  $data[$i]["im_resource"] = $state[$jid]["resource"];
        	 	  $data[$i]["im_priority"] = $state[$jid]["priority"];
        	 }
        }
      }
      return $data;
	  }
	  
	  //保存群组信息
	  public function saveGroup($micro_account,$groupname)
	  {
	  	  $sql = "select 1 from we_micro_account_group where micro_account=? and groupname=?";
        $paras = array((string)$micro_account,(string)$groupname);
        $dataset = $this->conn->GetData('dt', $sql, $paras);
        if($dataset && count($dataset["dt"]["rows"])>0) return false; 	  
	  	  $group_id= SysSeq::GetSeqNextValue($this->conn, "we_micro_account_group", "id");
	  	  $sql = "insert into we_micro_account_group(id,micro_account,groupname)values(?,?,?)";
	  	  $paras = array((string)$group_id,(string)$micro_account,(string)$groupname);
	  	  
	  	  try { 	
	       	$dataexec= $this->conn->ExecSQL($sql, $paras);
	       	if($dataexec){
	       		return $group_id;
	       	}
	       	return -1;
	      } catch (\Exception $exc) {
	      	$this->logger->err($exc);
	       	return -1;
	      }
	  }
	  //检测群组名称是否存在
	  public function checkgroupname($acc,$name,$newname){
	  	$sql="";
	  	$paras=array();
	  	if(empty($name)){
	  		$sql="select count(*) as count from we_micro_account_group where micro_account=? and groupname=?";
	  		array_push($paras,(string)$acc,(string)$newname);
	  	}else{
	  		$sql="select count(*) as count from we_micro_account_group where micro_account=? and groupname=? and groupname!=?";
	  		array_push($paras,(string)$acc,(string)$newname,(string)$name);
	  	}	
     	$data= $this->conn->GetData("dt",$sql, $paras);
     	$count=$data["dt"]["rows"][0]["count"];
     	return $count;
	  }
	  //修改群组信息
	  public function updateGroup($id,$micro_account,$groupname)
	  {
	  	  //$sql = "select id from we_micro_account_group where micro_account=? and groupname=?";
        //$paras = array((string)$micro_account,(string)$groupname);
        //$dataset = $this->conn->GetData('dt', $sql, $paras);
        //if($dataset && count($dataset["dt"]["rows"])>0 && $dataset["dt"]["rows"][0]["id"]==$id) return false; 
	  	  $sql = "update we_micro_account_group set groupname=? where id=?";
	  	  $paras = array((string)$groupname,(string)$id);
	  	  try { 	
		  	  $dataexec= $this->conn->ExecSQL($sql, $paras);
		  	  if($dataexec) return 1;
		  	  return 0;
		  	} catch (\Exception $exc) {
		  		$this->logger->err($exc);
		  		return 0;
		  	}
	  }
	  public function deletegroup($group_id,$rcount)
	  {
	  	$sqls=array();
	  	$paras=array();
	  	$sqls[] = "delete from we_micro_account_group where id=?";
	  	$paras[] = array((string)$group_id);
	  	if(!empty($rcount)){
	  		$sqls[] = "delete from we_micro_account_group_re where groupid=?";
	  		$paras[] = array((string)$group_id);
	  	}
	  	try {
	  	  $dataexec= $this->conn->ExecSQLs($sqls, $paras);
	  	  if($dataexec) return 1;
	  	  return 0;
	  	} catch (\Exception $exc) {
	  		$this->logger->err($exc);
	  		return 0;
	  	}
	  }
	  public function grouplist($micro_account)
	  {
	  	  $sql = "select a.*,(select count(*) from we_micro_account_group_re b where b.groupid=a.id) as re_count from we_micro_account_group a where micro_account=? order by a.groupname asc";
        $paras = array((string)$micro_account);
        $dataset = $this->conn->GetData('dt', $sql, $paras);
        return $dataset["dt"]["rows"];
	  }
	  
	  public function getgrouplist($micro_account){
	  	$sql="select a.id,a.groupname from we_micro_account_group a where micro_account=? and  (select count(*) from we_micro_account_group_re b where b.groupid=a.id)>0 order by a.groupname asc ;";
	  	 $paras = array((string)$micro_account);
        $dataset = $this->conn->GetData('dt', $sql, $paras);
        return $dataset["dt"]["rows"];
	  }
	  
	  public function groupMemberlist($group_id)
	  {
	  	  $sql = "select * from we_micro_account_group_re where groupid=? ";
        $paras = array((string)$group_id);
        $dataset = $this->conn->GetData('dt', $sql, $paras);
        return $dataset["dt"]["rows"];
	  }
	  
	  public function assignGroup($group_id,$accountarr)
	  {
	  	  $sqls =array();
	  	  $paras = array();
	  	  for($i=0;$i<count($accountarr); $i++)
	  	  {
	  	      	$sqls[]="insert into we_micro_account_group_re(id,groupid,login_account)values(?,?,?)";
	  	      	$id= SysSeq::GetSeqNextValue($this->conn, "we_micro_account_group_re", "id");
	  	      	$paras[] = array((string)$id,(string)$group_id,(string)$accountarr[$i]);
	  	  }
	  	  return $this->conn->ExecSQLs($sqls, $paras);
	  }
	  //添加公众号分组的方法
	  public function insert_micro_group($micro_number,$goupname){
	  	$sql_micro_count="select count(1) as count from we_micro_account where number=?";
	  	$para_micro_count=array((string)$micro_number);
	  	$data_micro_count=$this->conn->GetData("dt",$sql_micro_count,$para_micro_count);
	  	if(count($data_micro_count["dt"]["rows"])>0){
		  	$sql_count="select count(1) as count from we_micro_account_group where micro_account=? and goupname=?";
		  	$para_count=array((string)$micro_account,(string)$groupname);
		  	$data_count=$this->conn->GetData("dt",$sql_count,$para_count);
		  	$array["returncode"]='0000';
		  	$array["msg"]='';
		  	if(count($data_count["dt"]["rows"])>0){
		  		$array["returncode"]='9999';
		  		$array["msg"]='分组名称已存在';
		  	}else{
		  		$sql="insert into we_micro_account_group(id,micro_account,goupname) values(?,?,?)";
		  		$para=array((string)$id,(string)$micro_account,(string)$groupname);
		  		try {
		  			if(!empty($sql)){
			  	  	$dataexec=$this->conn->ExecSQL($sql,$para);	
				  		if($dataexec){
				  			$array["returncode"]='0000';
				  			$array["msg"]='成功创建分组【'.$goupname.'】';
				  		}else{
				  			$array["returncode"]='0000';
				  			$array["msg"]='创建分组【'.$goupname.'】失败';
				  		}
			  		}else{
			  			$array["returncode"]='0000';
				  			$array["msg"]='没有需要创建的数据';
			  		}
			  	} catch (\Exception $exc) {
			  		$this->logger->err($exc);
			  		$array["returncode"]='0000';
			  		$array["msg"]='创建过程中,出现异常情况';
			  	}
		  	}
		  }
		  else{
		  	$array["returncode"]='9999';
		  	$array["msg"]='公众号不存在,不能创建分组';
		  }
	  	return $array;
	  }
    //移除公众号分组人员
	  public function movememeber($group_id,$accountarr)
	  {
	  	  $sqls =array();
	  	  $paras = array();
	  	  for($i=0;$i<count($accountarr); $i++)
	  	  {
	  	      	$sqls[]="delete from we_micro_account_group_re where groupid=? and login_account=?";
	  	      	$paras[] = array((string)$group_id,(string)$accountarr[$i]);
	  	  }
	  	  return $this->conn->ExecSQLs($sqls, $paras);	  	
	  }
	  //粉丝分组移动
	  public function movememebers($check_login_accounts,$group_id,$micro_account)
	  {
	  	  $sqls =array();
	  	  $paras = array();
	  	  
	  	  for($i=0;$i<count($check_login_accounts); $i++)
	  	  {
	  	  	$sql="select count(*) as count from we_micro_account_group_re where groupid=? and micro_account=? and login_account=?";
	  	  	$para=array((string)$group_id,(string)$micro_account,(string)$check_login_accounts[$i]);
	  	  	$data=$this->conn->GetData("dt",$sql,$para);
	  	  	$count=$data["dt"]["rows"][0]["count"];
	  	  	if(empty($count)){
	  	  		$id=SysSeq::GetSeqNextValue($this->conn, "we_micro_account_group_re", "id");
	  	  		$sqls[]="insert into we_micro_account_group_re(id,micro_account,groupid,login_account) values(?,?,?,?);";
	  	      $paras[] = array((string)$id,(string)$micro_account,(string)$group_id,(string)$check_login_accounts[$i]);
	  	  	} 
	  	  }
	  	  try { 	
	  	  	if(empty($sqls))return 1;
		  	  $dataexec= $this->conn->ExecSQLs($sqls, $paras);
		  	  if($dataexec) return 1;
		  	  return 0;
		  	} catch (\Exception $exc) {
		  		$this->logger->err($exc);
		  		return 0;
		  	}	
	  }
	  //获取所有分组记录数
	  public function getFansCount($micro_account){
	  	$sql="select count(1) count from we_staff_atten where atten_id=? and atten_type=?";
	  	$para=array((string)$micro_account,"01");
	  	$data=$this->conn->GetData("dt",$sql,$para);
	  	if(count($data["dt"]["rows"])>0){
				return $data["dt"]["rows"][0]["count"];
			}
			return  0;
	  }
	  //获取未分组记录数
	  public function getFansUngroupedCount($micro_account){
	  	$sql="SELECT count(1) count FROM we_staff_atten a  where  not EXISTS (select 1 from we_micro_account_group_re b where a.atten_id=b.micro_account and a.login_account=b.login_account) and a.atten_type='01' and a.atten_id=?";
			$para=array((string)$micro_account);
			$data=$this->conn->GetData("dt",$sql,$para);
			if(count($data["dt"]["rows"])>0){
				return $data["dt"]["rows"][0]["count"];
			}
			return  0;
	  }
	  //根据粉丝分组ID集合删除对应数据
	  public function deleteMembers($check_login_accounts,$micro_account,$url_groupid){
	  	if(count($check_login_accounts)>0 && !empty($micro_account) && !empty($url_groupid)){
	  		$sqls=array();
	  		$paras=array();
	  		for ($i = 0; $i < count($check_login_accounts); $i++) {
	  		 	 $sqls[]="delete from we_micro_account_group_re where micro_account=? and login_account=? and groupid=?";
	  		 	 $paras[]=array((string)$micro_account,(string)$check_login_accounts[$i],(string)$url_groupid);
	  		}
	  		try { 	
	  	  	if(!empty($sqls)){
			  	  $dataexec= $this->conn->ExecSQLs($sqls, $paras);
			  	  if($dataexec) return 1;
		  		}
		  	  return 0;
		  	} catch (\Exception $exc) {
		  		$this->logger->err($exc);
		  		return 0;
		  	}
	  	}else{
	  		return 0;
	  	}
	  }
	  
	  //获取文件地址
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
	  //移除文件地址
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
	  //获取一个或者多个公众号的连接状态
	  private function getConnState($jidRowsArr)
	  {
	  	  if(empty($jidRowsArr) || count($jidRowsArr)==0) return null;
	  	  $q1 = array();
	  	  $jids=array();
	  	  for($i=0; $i<count($jidRowsArr); $i++)
	  	  {
	  	  	$q1[] = "?";
	  	  	$jids[] = (string)$jidRowsArr[$i]["jid"];
	  	  }
	  	  $sql = "select b.us,ifnull(b.res,'') resource,case when (b.res is null) then '0' else '1' end state,b.priority from global_session b where b.us in (".implode(",",$q1).") order by priority desc limit 0,1";
	  	  $dataset = $this->conn_im->GetData("d",$sql,$jids);
	  	  $jidRowsArr = $dataset["d"]["rows"];
	  	  $result=array();
	  	  for($i=0; $i<count($jidRowsArr); $i++)
	  	  {
	  	  	$result[$jidRowsArr[$i]["us"]] = $jidRowsArr[$i];
	  	  }	  	  
	  	  return $result;
	  }
	  //添加公众号数量
	  public function add_micro_quantity($ext_count,$int_count){
	  	$array["success"]=0;
	  	
	  	$container=$this->container;
	  	$ename=$this->userinfo->ename;
	  	$nick_name=$this->userinfo->nick_name;
	  	$login_account=$this->userinfo->getUsername();//登录人帐号
	  	$fafajid=$this->userinfo->fafa_jid;
	  	$tojids=$container->getParameter("im_receiver");//weifafa管理员
	  	//var_dump($tojids);
	  	$title="申请公众号数量";
	  	$message="【".$ename."】企业的【".$nick_name."】于".date("Y-m-d H:i")."申请";
	  	if(!empty($ext_count))$message.="(".$ext_count.")个外部公众号,";
	  	if(!empty($int_count))$message.="(".$ext_count.")个内部公众号,";
	  	$message.="请及时处理并联系该企业";
	  	
	  	Utils::sendImMessage($fafajid,$tojids,$title,$message, $container,"","",false,Utils::$systemmessage_code,"1");
	  	
	  	return $array;
	  }
	  private function sendMessage($jids,$msg)
	  {
	  	  if(count($jids)>0)
      	    Utils::sendImMessage($this->container->getParameter('im_sender'),implode(",",$jids),"microaccount-autoatten",$msg, $this->container,"","",false,Utils::$systemmessage_code,"0");
	  }
	  private function micro_sendMessage($jids,$msg)
	  {
	  	$fafajid=$this->userinfo->fafa_jid;
	  	  if(count($jids)>0)
      	    Utils::sendImMessage($fafajid,implode(",",$jids),"公众号关注消息",$msg, $this->container,"","",false,Utils::$systemmessage_code,"0");
	  }
}
