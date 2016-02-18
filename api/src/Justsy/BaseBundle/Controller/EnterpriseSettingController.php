<?php

namespace Justsy\BaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\BaseBundle\Meeting\MeetingManager;
use Justsy\BaseBundle\Management\MicroAccountMgr;
use Justsy\BaseBundle\Management\EnoParamManager;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\weibo\WeiboMgr;

class EnterpriseSettingController extends Controller {

    public $credential_path;
    public $logo_path;
    public $logo_path_small;
    public $logo_path_big;

    public function employeemgrAction($network_domain) {
        $user = $this->get('security.context')->getToken()->getUser();
        //判断当前导入人员是否是企业邮箱
        $userDomain = explode("@", $user->getUserName());
        $da = $this->get("we_data_access");
        $sql = "select 1 from we_public_domain where domain_name=?";
        $ds = $da->GetData("mt", $sql, array((string) $userDomain[1]));
        $mailType = count($ds["mt"]["rows"]) > 0 ? "0" : "1"; //1表示是企业邮箱		
        //获取现有人员
        return $this->render('JustsyBaseBundle:EnterpriseSetting:employee.html.twig', array("userDomain" => $userDomain[1], "mailType" => $mailType, "curr_network_domain" => $network_domain));
    }

    public function deptmgrAction($network_domain) {
        //获取现有部门
        return $this->render('JustsyBaseBundle:EnterpriseSetting:dept.html.twig', array("curr_network_domain" => $network_domain));
    }

    //获取省份数据集合
    public function getProvinceListAction(){
        $da = $this->container->get('we_data_access');
        $sql="SELECT province as text,provinceID as value FROM we_province";
        $data=$da->GetData("dt",$sql,array());
        return $this->res(json_encode($data["dt"]["rows"]), 'json');
    }
    //获取城市数据集合
    public function getCityListAction(){
        $da = $this->container->get('we_data_access');
        $res=$this->getRequest();
        $pid =trim($res->get("pid"));
        $sql="SELECT city as text,cityID as value FROM we_city WHERE father=?";
        $para=array($pid);
        $data=$da->GetData("dt",$sql,$para);
        return $this->res(json_encode($data["dt"]["rows"]), 'json');
    }
    public function getAreaListAction(){
        $da = $this->container->get('we_data_access');
        $res=$this->getRequest();
        $cid =trim($res->get("cid"));
        $sql="SELECT area as text,areaID as value FROM we_area WHERE father=?";
        $para=array($cid);
        $data=$da->GetData("dt",$sql,$para);
        return $this->res(json_encode($data["dt"]["rows"]), 'json');
    }
		public function saveEnoLogAction(){
			$da = $this->container->get('we_data_access');
      $request=$this->getRequest();
      $user = $this->get('security.context')->getToken()->getUser();
      $filename=$_FILES['en_logo_file']['name'];
		$filesize=$_FILES['en_logo_file']['size'];
		$filetemp=$_FILES['en_logo_file']['tmp_name'];
		$re=array('s'=>'1','m'=>'');
		if((int)$filesize > 1024*1024)
		{
			$re=array('s'=>'0','m'=>'上传的证件不能大于1M！');
		}
		if($re['s']=='1'){
			$fileid=$this->saveCertificate($filetemp,$filename);
			if(empty($fileid))
			{
				$re=array('s'=>'0','m'=>'有效证件提交失败!');
				//$fileid="523fe22a7d274a2d01000000";
			}
			if($re['s']=='1')
			{
				$sql[]="update we_enterprise set logo_path=?,logo_path_small=?,logo_path_big=? where eno=?";
				$params[]=array($fileid,$fileid,$fileid,$user->eno);
				$sql[]="update we_enterprise_stored set eno_logo_path=?,eno_logo_path_small=?,eno_logo_path_big=? where enoname=?";
				$params[]=array($fileid,$fileid,$fileid,$user->ename);
				if(!$da->ExecSQLs($sql,$params))
				{
					$re=array('s'=>'0','m'=>'有效证件提交失败!');
				}
				else{
					$re['file']=array('filename'=> $filename,'filepath'=> $fileid);
				}
			}
		}
		$response = new Response(json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
		}
		protected function saveCertificate($filetemp,$filename)
	  {
	  	try{
		  	$upfile = tempnam(sys_get_temp_dir(), "we");
		    unlink($upfile);
		    /*
		    $somecontent1 = base64_decode($filedata);
		    if ($handle = fopen($upfile, "w+")) {   
		      if (!fwrite($handle, $somecontent1) == FALSE) {   
		        fclose($handle);  
		      }  
		    }
		    */
		    if(move_uploaded_file($filetemp,$upfile)){
			    $dm = $this->get('doctrine.odm.mongodb.document_manager'); 
			    $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
			    $doc->setName($filename);
			    $doc->setFile($upfile); 
			    $dm->persist($doc);
			    $dm->flush();
			    $fileid = $doc->getId();
			    return $fileid;
			  }
			  else{
			  	return "";
			  }
		  }
		  catch(\Exception $e)
		  {
		  	$this->get('logger')->err($e);
		  	return "";
		  }
	  }
    public function basicAction($network_domain) {
        //判断是否具有权限
        if (0 == $this->get('security.context')->getToken()->getUser()->is_in_manager_circles($network_domain)) {
            return $this->render('JustsyBaseBundle:EnterpriseSetting:no_rights.html.twig');
        }
        $DataAccess = $this->container->get('we_data_access');
        $login_account = $this->get('security.context')->getToken()->getUser()->getUsername();
        $sql = array(
            'select a.*,d.classify_id as parent_industry_id,d.classify_name as parent_industry_name,e.nick_name create_statff_name
		      from we_staff b,we_enterprise a
		      left join we_industry_class c on a.industry=c.classify_id
		      left join we_industry_class d on c.parent_classify_id=d.classify_id
		      left join we_staff e on a.create_staff=e.login_account
		      where b.eno=a.eno and b.login_account=?',
            'select * from we_industry_class where 1=? order by classify_id asc'
        );
        $dataset = $DataAccess->GetDatas(array('we_enterprise', 'we_industry_class'), $sql, array(array((string) $login_account), array((int) 1)));
        $this->logo_path = $dataset['we_enterprise']['rows'][0]['logo_path'];
        $this->logo_path_small = $dataset['we_enterprise']['rows'][0]['logo_path_small'];
        $this->logo_path_big = $dataset['we_enterprise']['rows'][0]['logo_path_big'];
        //$this->$sys_manager=$dataset['we_enterprise']['rows'][0]['sys_manager'];
        $dataset['we_enterprise']['rows'][0]['haslogo'] = empty($dataset['we_enterprise']['rows'][0]['logo_path'])?'0':'1';
        $dataset['we_enterprise']['rows'][0]['logo_path'] = $this->ifPicNull($dataset['we_enterprise']['rows'][0]['logo_path']);
        $dataset['we_enterprise']['rows'][0]['logo_path_small'] = $this->ifPicNull($dataset['we_enterprise']['rows'][0]['logo_path_small']);
        $dataset['we_enterprise']['rows'][0]['logo_path_big'] = $this->ifPicNull($dataset['we_enterprise']['rows'][0]['logo_path_big']);
        $dataset['we_enterprise']['rows'][0]['curr_network_domain'] = $network_domain;
        $dataset['we_enterprise']['rows'][0]['classify'] = $dataset['we_industry_class']['rows'];
        $dataset['we_enterprise']['rows'][0]['sys_manager'] = explode(';', $dataset['we_enterprise']['rows'][0]['sys_manager']);
        $num_sys_manager = '';
        for ($i = 0; $i < count($dataset['we_enterprise']['rows'][0]['sys_manager']); $i++) {
            $num_sys_manager.='?,';
        }
        $num_sys_manager = substr($num_sys_manager, 0, strlen($num_sys_manager) - 1);
        $sql = 'select login_account,nick_name from we_staff where login_account in (' . $num_sys_manager . ')';
        $dataset2 = $DataAccess->GetData('we_staff', $sql, $dataset['we_enterprise']['rows'][0]['sys_manager']);
        $dataset['we_enterprise']['rows'][0]['sys_manager'] = $dataset2['we_staff']['recordcount'] > 0 ? $dataset2['we_staff']['rows'] : array();
        $dataset['we_enterprise']['rows'][0]['fileurl']=$this->container->getParameter('FILE_WEBSERVER_URL');
        $data = $dataset['we_enterprise']['rows'][0];
        return $this->render('JustsyBaseBundle:EnterpriseSetting:basic.html.twig', $data);
    }

    public function saveBasicAction($network_domain) {
        $DataAccess = $this->get('we_data_access');
        //$eno=$this->get('security.context')->getToken()->getUser()->getEno();
        $sql = 'select enterprise_no,circle_id from we_circle where network_domain=?';
        $dataset = $DataAccess->GetData('we_circle', $sql, array((string) $network_domain));
        if ($dataset['we_circle']['recordcount'] > 0) {
            $enterprise_no = $dataset['we_circle']['rows'][0]['enterprise_no'];
            $circle_id = $dataset['we_circle']['rows'][0]['circle_id'];
        }
        else
            return '';

       $dm = $this->get('doctrine.odm.mongodb.document_manager');
         if (isset($_FILES["en_logo_file"]) && $_FILES["en_logo_file"]["name"] != "") {
            $tmpName = $_FILES['en_logo_file']['tmp_name'];
            $fileid = $this->saveFile($tmpName, $dm);
            $logo_path = $fileid;
            $logo_path_big = $fileid;
            $logo_path_small = $fileid;
        }
        $logo_path ="";
        $logo_path_big ="";
        $logo_path_small = "";
        $ename = $this->getRequest()->get('ename');
        $eshortname = $this->getRequest()->get('eshortname');
        $industry = $this->getRequest()->get('industry');
        $eidcard = $this->getRequest()->get('eidcard');
        $telephone = $this->getRequest()->get('telephone');
        $fax = $this->getRequest()->get('fax');
        $ewww = $this->getRequest()->get('ewww');

        $session = $this->get('session');

        if (empty($ename) == true || empty($eshortname) == true)
            return $this->res('{"success":0}', 'json');
        $sqls = array();
        $paras = array();
        if (empty($logo_path)) {
            $sqls = array(
                'update we_enterprise set ename=?,eshortname=?,industry=?,eidcard=?,telephone=?,fax=?,ewww=? where eno=?',
                'update we_circle set circle_name=? where circle_id=?'
            );
            $paras = array(
                array((string) trim($ename),
                    (string) trim($eshortname),
                    (string) $industry,
                    (string) $eidcard,
                    (string) $telephone,
                    (string) $fax,
                    (string) $ewww,
                    (string) $enterprise_no),
                array((string) trim($ename),
                    (string) $circle_id
                )
            );
        } else {
            $sqls = array(
                'update we_enterprise set ename=?,eshortname=?,industry=?,eidcard=?,telephone=?,fax=?,ewww=?,
  	  logo_path=?,logo_path_big=?,logo_path_small=?  where eno=?',
                'update we_circle set circle_name=? where circle_id=?'
            );
            $paras = array(
                array((string) trim($ename),
                    (string) trim($eshortname),
                    (string) $industry,
                    (string) $eidcard,
                    (string) $telephone,
                    (string) $fax,
                    (string) $ewww,
                    (string) $logo_path,
                    (string) $logo_path_big,
                    (string) $logo_path_small,
                    (string) $enterprise_no),
                array((string) trim($ename),
                    (string) $circle_id)
            );
        }
        $dataexec = $DataAccess->ExecSQLs($sqls, $paras);
        if ($dataexec) {
            if (!empty($logo_path)) {
                $logo_path = $this->container->getParameter('FILE_WEBSERVER_URL') . $logo_path;
            }
            return $this->res('{"success":1,"logo_path":"' . $logo_path . '"}', 'json');
        }
        else
            return $this->res('{"success":0}', 'json');
    }

    public function detailAction($network_domain) {
        if (0 == $this->get('security.context')->getToken()->getUser()->is_in_manager_circles($network_domain)) {
            return $this->render('JustsyBaseBundle:EnterpriseSetting:no_rights.html.twig');
        }
        $login_account = $this->get('security.context')->getToken()->getUser()->getUsername();
        $DataAccess = $this->get('we_data_access');
        $sql = 'select a.*,b.* from we_circle b,we_enterprise a
  	      inner join we_staff c  on a.eno=c.eno
  	 where a.eno=b.enterprise_no and c.login_account=?';
        $dataset = $DataAccess->GetData('we_enterprise', $sql, array((string) $login_account));
        $this->credential_path = $dataset['we_enterprise']['rows'][0]['credential_path'];
        $dataset['we_enterprise']['rows'][0]['credential_path'] = $this->ifPicNull($dataset['we_enterprise']['rows'][0]['credential_path']);
        $dataset['we_enterprise']['rows'][0]['curr_network_domain'] = $network_domain;
        $data = $dataset['we_enterprise']['rows'][0];
        return $this->render('JustsyBaseBundle:EnterpriseSetting:detail.html.twig', $data);
    }
		
    public function saveDetailAction($network_domain) {
        $DataAccess = $this->get('we_data_access');
        $eno = $this->get('security.context')->getToken()->getUser()->getEno();
        $sql = 'select circle_id from we_circle where enterprise_no=?';
        $dataset = $DataAccess->GetData('we_circle', $sql, array((string) $eno));
        $circle_id = $dataset['we_circle']['rows'][0]['circle_id'];

        $postcode = $this->getRequest()->get('postcode');
        $addr = $this->getRequest()->get('addr');
        $ad_desc = $this->getRequest()->get('ad_desc');
        $edesc = $this->getRequest()->get('edesc');
        //$join_method=$this->getRequest()->get('join_method');
        $join_method = 1;
        $allow_copy = $this->getRequest()->get('allow_copy');
        //$session = $this->get('session');
        $credential_path ="";//$session->get('avatar_middle');

        $province= $this->getRequest()->get('province');
        $city= $this->getRequest()->get('city');
        $area= $this->getRequest()->get('area');

        //$dm = $this->get('doctrine.odm.mongodb.document_manager');
        //if (!empty($credential_path)) {
        //    $credential_path = $this->saveFile($credential_path, $dm);
        //    $session->remove('avatar_middle');
        //    $session->remove('avatar_big');
        //    $session->remove('avatar_small');
        //}
        if (empty($credential_path)) {
            $sqls = array(
                'update we_enterprise set postcode=?,addr=?,ad_desc=?,edesc=?,province=?,city=?,area=?   where eno=?',
                'update we_circle set join_method=?,allow_copy=?,circle_desc=? where circle_id=?'
            );
            $paras = array(
                array(
                    (string) $postcode,
                    (string) $addr,
                    (string) $ad_desc,
                    (string) $edesc,
                    (string) $province,
                    (string) $city,
                    (string) $area,
                    (string) $eno
                ),
                array(
                    (string) $join_method,
                    (string) $allow_copy,
                    (string) $edesc,
                    (string) $circle_id)
            );
        } else {
            $sqls = array(
                'update we_enterprise set postcode=?,addr=?,ad_desc=?,edesc=?,credential_path=? ,province=?,city=?,area=?  where eno=?',
                'update we_circle set join_method=?,allow_copy=?,circle_desc=? where circle_id=?'
            );
            $paras = array(
                array(
                    (string) $postcode,
                    (string) $addr,
                    (string) $ad_desc,
                    (string) $edesc,
                    (string) $credential_path,
                    (string) $province,
                    (string) $city,
                    (string) $area,
                    (string) $eno
                ),
                array(
                    (string) $join_method,
                    (string) $allow_copy,
                    (string) $edesc,
                    (string) $circle_id)
            );
        }
        $dataexec = $DataAccess->ExecSQLs($sqls, $paras);
        if ($dataexec) {
            if (!empty($this->credential_path)) {
                $this->deleteFile($this->credential_path, $dm);
            }
            return $this->res('{"success":1}', 'json');
        }
        else
            return $this->res('{"success":0}', 'json');
    }

    public function tagAction($network_domain){
        if (0 == $this->get('security.context')->getToken()->getUser()->is_in_manager_circles($network_domain)) {
            return $this->render('JustsyBaseBundle:EnterpriseSetting:no_rights.html.twig');
        }
        //$login_account = $this->get('security.context')->getToken()->getUser()->getUsername();
        $eno=$this->get('security.context')->getToken()->getUser()->getEno();
        $da = $this->get('we_data_access');
        $sql="sELECT tag_id as tagid,tag_name as tagname FROM we_tag WHERE owner_id=(SELECT id FROM we_enterprise_stored WHERE eno=? LIMIT 0,1) AND owner_type='02' ";
        $data =$da->GetData("dt",$sql,array($eno));
        $array =array("curr_network_domain"=>$network_domain,"tagdata"=>$data["dt"]["rows"]);
        //var_dump($data["dt"]["rows"]);
        return $this->render('JustsyBaseBundle:EnterpriseSetting:tag.html.twig', $array);
    }
    public function savetagAction($network_domain){
        $da = $this->get('we_data_access');
        $tagname =trim($this->getRequest()->get('tagname'));
        $eno = $this->get('security.context')->getToken()->getUser()->getEno();
        $array=array("success"=>false,"data"=>null);
        $tagtype="02";//01 是个人标签 02 是企业标签
        $sql_count="sELECT b.id,a.tag_id FROM  we_enterprise_stored b LEFT JOIN we_tag a ON a.owner_id=b.id and a.owner_type=? and a.tag_name=? WHERE  b.eno=? ";
        $para_count=array($tagtype,$tagname,$eno);
        $data_count=$da->GetData("dt",$sql_count,$para_count);
        //var_dump($data_count["dt"]["rows"][0]["count"]);
        //var_dump($sql_count,$para_count);
        if($data_count!=null && count($data_count["dt"]["rows"]) > 0 && empty($data_count["dt"]["rows"][0]["tag_id"])){
            $sql ="iNSERT INTO we_tag(tag_id,tag_name,owner_id,owner_type,create_date) VALUES(?,?,?,?,now());";
            $tagid= SysSeq::GetSeqNextValue($da, "we_tag", "tag_id");
            $para =array($tagid,$tagname,$data_count["dt"]["rows"][0]["id"],$tagtype);
            try{
                //var_dump($sql,$para);
                $data=$da->ExecSQL($sql,$para);
                $array["success"]=true;
                $array["data"]=array("tagid"=>$tagid,"tagname"=>$tagname);
            }catch(\Exception $e){}  
        }else{
            $array["success"]=true;
        }
        return $this->res(json_encode($array));
    }
    public function deltagAction($network_domain){
        $da = $this->get('we_data_access');
        $tagid =trim($this->getRequest()->get('tagid'));
        $eno = $this->get('security.context')->getToken()->getUser()->getEno();
        $array=array("success"=>false);
        $tagtype="02";//01 是个人标签 02 是企业标签
        $sql="dELETE FROM we_tag WHERE tag_id=? AND owner_id=(SELECT id FROM we_enterprise_stored WHERE eno=? LIMIT 0,1) AND owner_type=?";
        $para=array($tagid,$eno,$tagtype);
        try{
            $data=$da->ExecSQL($sql,$para);
            $array["success"]=true;
        }catch(Exception $e){}
        return $this->res(json_encode($array));
    }

    public function manager_settingAction($network_domain) {
        if (0 == $this->get('security.context')->getToken()->getUser()->is_in_manager_circles($network_domain)) {
            return $this->render('JustsyBaseBundle:EnterpriseSetting:no_rights.html.twig');
        }
        $DataAccess = $this->get('we_data_access');
        $sql = 'select manager from we_circle where network_domain=?';
        $dataset = $DataAccess->GetData('we_circle', $sql, array((string) $network_domain));
        $dataset['we_circle']['rows'][0]['manager'] = explode(';', $dataset['we_circle']['rows'][0]['manager']);
        $manager_string = '';
        for ($i = 0; $i < count($dataset['we_circle']['rows'][0]['manager']); $i++) {
            $manager_string.='?,';
        }
        $manager_string = substr($manager_string, 0, strlen($manager_string) - 1);
        $sql = 'select a.login_account,ifnull(a.nick_name,"") as nick_name from we_staff a where a.login_account in (' . $manager_string . ')';
        $dataset = $DataAccess->GetData('we_staff', $sql, $dataset['we_circle']['rows'][0]['manager']);
        $data = array(
            'manager' => array(),
            'curr_network_domain' => $network_domain
        );
        $data['manager'] = $dataset['we_staff']['recordcount'] > 0 ? $dataset['we_staff']['rows'] : array();

        //获取指定企业的会议管理专员
        $eno = $this->get('security.context')->getToken()->getUser()->eno;
        $da = $this->get("we_data_access");
        $da_im = $this->get("we_data_access_im");
        $meetingManager = new MeetingManager($da, $da_im);
        $data['meeting_manager'] = $meetingManager->Get($eno);

        //获取指定的移动门户管理员
        $set = new \Justsy\BaseBundle\Management\StaffCompetenceMgr($da, $da_im);
        $data['mobile_manager'] = $set->Get($eno,"mapp-manager");

        return $this->render('JustsyBaseBundle:EnterpriseSetting:manager_setting.html.twig', $data);
    }

    public function savemanager_settingAction($network_domain) {
        $DataAccess = $this->get('we_data_access');
        $manager = $this->getRequest()->get('array_manager');
        $meeting_manager = $this->getRequest()->get('array_meeting_manager');
        $mobile_manager = $this->getRequest()->get('array_mobile_manager');
        $sql = 'select a.enterprise_no,a.circle_id,b.ename from we_circle a,we_enterprise b where a.network_domain=? and a.enterprise_no=b.eno ';
        $dataset = $DataAccess->GetData('we_circle', $sql, array((string) $network_domain));
        if ($dataset['we_circle']['recordcount'] > 0) {
            $enterprise_no = $dataset['we_circle']['rows'][0]['enterprise_no'];
            $circle_id = $dataset['we_circle']['rows'][0]['circle_id'];
            $ename = $dataset['we_circle']['rows'][0]['ename'];
        }
        else
            return '';

        //设置指定企业的会议管理专员
        $da = $this->get('we_data_access');
        $da_im = $this->get("we_data_access_im");
        $meetingManager = new MeetingManager($da, $da_im);
        $meetingManager->Set($enterprise_no, $meeting_manager);

        //设置指定的移动门户管理员
        $set = new \Justsy\BaseBundle\Management\StaffCompetenceMgr($da, $da_im);
        $sql = "delete from we_function_onoff where functionid='MAPP_ADMIN' and eno=?";
        $da->ExecSQL($sql,array((string)$enterprise_no));
        if(!empty($mobile_manager))
        {
        	$mobile_manager_array = explode(';', $mobile_manager);
	        foreach ($mobile_manager_array as $key => $value) {
	            $set->Set($enterprise_no, "mapp-manager", $value);  
	            $sql = "insert into we_function_onoff(functionid,login_account,state,eno)values('MAPP_ADMIN',?,'1',?)";
	            $da->ExecSQL($sql,array((string)$value,(string)$enterprise_no));
	        }
    	}
        //设置指定的企业管理员      
        $sqls = array(
            'update we_enterprise set sys_manager=? where eno=?',
            'update we_circle     set     manager=? where circle_id=?'
        );
        $paras = array(
            array(
                (string) $manager,
                (string) $enterprise_no
            ),
            array(
                (string) $manager,
                (string) $circle_id
            )
        );

        $new_manager_array = array();
        $old_manager_array = array();
        $new_manager_array = explode(';', $manager);
        $sql = 'select manager from we_circle where network_domain=?';
        $dataset = $DataAccess->GetData('we_circle', $sql, array((string) $network_domain));
        if ($dataset['we_circle']['recordcount'] > 0) {
            $old_manager_array = explode(';', $dataset['we_circle']['rows'][0]['manager']);
        }
        //var_dump($new_manager_array);
        //var_dump($old_manager_array);
        //exit;
        $new_manager = array_diff($new_manager_array, $old_manager_array); // 新增管理员
        $old_manager = array_diff($old_manager_array, $new_manager_array);  //取消了的管理员
        $dataexec = $DataAccess->ExecSQLs($sqls, $paras);
        if ($dataexec) {
            if (count($new_manager) > 0 || count($old_manager) > 0) {
                $sqls = array(
                    'insert into we_message(msg_id,sender,send_date,title,content,isread,recver) values(?,?,CURRENT_TIMESTAMP(),?,?,?,?)',
                    'insert into we_notify(notify_type,msg_id,notify_staff) values(?,?,?)'
                );
                $login_account = $this->get('security.context')->getToken()->getUser()->getUsername();
                $FAFA_CIRCLE_URL = $this->generateUrl('JustsyBaseBundle_enterprise_home', array('network_domain' => $network_domain), true);
                foreach ($new_manager as $key => $value) {
                    $msg_id = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($DataAccess, "we_message", "msg_id");
                    $sys_manager = $value;
                    $title = '您被设置为管理员';
                    $content = '您被设置为企业' . '<a target="_blank" href="' . $FAFA_CIRCLE_URL . '">【' . $ename . '】</a>的管理员！';
                    $paras = array(
                        array(
                            (string) $msg_id,
                            (string) $login_account,
                            (string) $title,
                            (string) $content,
                            '0',
                            (string) $sys_manager
                        ),
                        array(
                            '02',
                            (string) $msg_id,
                            (string) $sys_manager
                        )
                    );
                    $dataexec1 = $DataAccess->ExecSQLs($sqls, $paras);
                }
                foreach ($old_manager as $key => $value) {
                    $msg_id = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($DataAccess, "we_message", "msg_id");
                    $sys_manager = $value;
                    $title = '您被取消了管理员';
                    $content = '您被取消了企业' . '<a target="_blank" href="' . $FAFA_CIRCLE_URL . '">【' . $ename . '】</a>的管理员！';
                    $paras = array(
                        array(
                            (string) $msg_id,
                            (string) $login_account,
                            (string) $title,
                            (string) $content,
                            '0',
                            (string) $sys_manager
                        ),
                        array(
                            '02',
                            (string) $msg_id,
                            (string) $sys_manager
                        )
                    );
                    $dataexec2 = $DataAccess->ExecSQLs($sqls, $paras);
                }
                //更新企业缓存
                $enMgr = new \Justsy\BaseBundle\Management\Enterprise($DataAccess,$this->get("logger"),$this->container);
                $enMgr->refresh($enterprise_no);
            }
            return $this->res('{"success":1}', 'json');
        } else {
            return $this->res('{"success":0}', 'json');
        }
    }

    public function checkEnameAction() {
        $DataAccess = $this->get('we_data_access');
        $eno = $this->get('security.context')->getToken()->getUser()->getEno();
        $ename = $this->getRequest()->get('ename');
        $sql = 'select 1 from we_enterprise where ename=? and eno!=?';
        $dataset1 = $DataAccess->GetData('we_enterprise', $sql, array((string) $ename, (string) $eno));
        $sql = 'select 1 from we_circle where circle_name=? and enterprise_no!=?';
        $dataset2 = $DataAccess->GetData('we_circle', $sql, array((string) $ename, (string) $eno));
        if ((int) $dataset1['we_enterprise']['recordcount'] > 0 || (int) $dataset2['we_circle']['recordcount'] > 0)
            return $this->res('{"exist":1}', 'json');
        else
            return $this->res('{"exist":0}', 'json');
    }

    public function checkEshortnameAction() {
        $DataAccess = $this->get('we_data_access');
        $eno = $this->get('security.context')->getToken()->getUser()->getEno();
        $eshortname = $this->getRequest()->get('eshortname');
        $sql = 'select 1 from we_enterprise where eshortname=? and eno!=?';
        $dataset1 = $DataAccess->GetData('we_enterprise', $sql, array((string) $eshortname, (string) $eno));
        if ((int) $dataset1['we_enterprise']['recordcount'] > 0)
            return $this->res('{"exist":1}', 'json');
        else
            return $this->res('{"exist":0}', 'json');
    }

    public function queryEnterprisemanagerAction() {
        $DataAccess = $this->get('we_data_access');
        $eno = $this->get('security.context')->getToken()->getUser()->getEno();
        $network_domain = $this->getRequest()->get('network_domain');
        $q = $this->getRequest()->get('q');
        if (empty($q)) {
            $sql = 'select a.login_account,ifnull(a.nick_name,"") as nick_name from we_circle_staff a,we_circle b where  a.circle_id=b.circle_id and b.network_domain= ? and a.login_account!=b.create_staff limit 0,100';
            $dataset = $DataAccess->GetData('enterprise_manager', $sql, array((string) $network_domain));
        } else {
            $sql = 'select a.login_account,ifnull(a.nick_name,"") as nick_name from we_circle_staff a,we_circle b where  a.circle_id=b.circle_id and b.network_domain= ? 
  		     and (  a.login_account like BINARY ? or a.nick_name like ? ) and a.login_account!=b.create_staff limit 0,100';
            $dataset = $DataAccess->GetData('enterprise_manager', $sql, array((string) $network_domain, '%' . $q . '%', '%' . $q . '%'));
        }
        $data = array();
        if (count($dataset['enterprise_manager']['rows']) > 0) {
            $data = $dataset['enterprise_manager']['rows'];
        }
        return $this->res(json_encode($data), 'json');
    }

    public function online_serviceAction($network_domain) {
        if (0 == $this->get('security.context')->getToken()->getUser()->is_in_manager_circles($network_domain)) {
            return $this->render('JustsyBaseBundle:EnterpriseSetting:no_rights.html.twig');
        }
        $DataAccess = $this->get('we_data_access');
        $sql = 'select enterprise_no from we_circle where network_domain=? limit 1';
        $dataset = $DataAccess->GetData('we_circle', $sql, array((string) $network_domain));
        if ($dataset['we_circle']['recordcount'] > 0) {
            $eno = $dataset['we_circle']['rows'][0]['enterprise_no'];
        }
        $sql = 'select id,eno,words from we_ocs_word where eno=?';
        $dataset = $DataAccess->GetData('we_circle', $sql, array((string) $eno));
        $msg_textarea = array();
        if ($dataset['we_circle']['recordcount'] > 0) {
            foreach ($dataset['we_circle']['rows'] as $key => $value) {
                $msg_textarea[] = $value['words'];
            }
        }
        array_walk($msg_textarea, function(&$value, $key) {
                    $value.=';';
                });
        $da_im = $this->get('we_data_access_im'); //从im数据库中取得公共帐号。包括前台、销售和客服 
        $sql = "select employeeid,employeename,loginname from im_employee where loginname like 'front-$eno%' union " .
                "select employeeid,employeename,loginname from im_employee where loginname like 'sale-$eno%' union " .
                "select employeeid,employeename,loginname from im_employee where loginname like 'service-$eno%' ";
        $pubAccount = $da_im->GetData("accs", $sql, array());
        //var_dump($msg_textarea);
        //$msg_textarea=join($msg_textarea,'；');
        $msg_textarea = join($msg_textarea, "\r\n");
//  	  //$eno='100093';
//  	  $url='http://www.fafacn.com:800/controller.yaws?eno='.$eno.'&method=service_org:getPublicAccount';
//  	  $ch=curl_init();
//  	  curl_setopt($ch,CURLOPT_URL,$url);
//  	  curl_setopt($ch, CURLOPT_HEADER, 0);
//  	  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//  	  $data=curl_exec($ch);
//  	  curl_close($ch);
        $sale_array = array();
        $service_array = array();
        $front_array = array();
        if ($pubAccount && count($pubAccount)) {
            $data = $pubAccount["accs"]["rows"];
            for ($i = 0; $i < count($data); $i++) {
                $value1 = $data[$i];
                $value = $value1['employeeid'];
                if (preg_match('/sale-/', $value)) {
                    $sale_array[] = $value1;
                }
                if (preg_match('/service-/', $value)) {
                    $service_array[] = $value1;
                }
                if (preg_match('/front-/', $value)) {
                    $front_array[] = $value1;
                }
            }
        }
        return $this->render('JustsyBaseBundle:EnterpriseSetting:online_service.html.twig', array('curr_network_domain' => $network_domain, 'sale' => $sale_array, 'service' => $service_array, 'front' => $front_array, 'msg_textarea' => $msg_textarea));
    }

    public function online_service_queryAction() {
        $DataAccess = $this->get('we_data_access');
        $network_domain = $this->getRequest()->get('network_domain');
        $sql = 'select enterprise_no from we_circle where network_domain=? limit 1';
        $dataset = $DataAccess->GetData('we_circle', $sql, array((string) $network_domain));
        if ($dataset['we_circle']['recordcount'] > 0) {
            $eno = $dataset['we_circle']['rows'][0]['enterprise_no'];
        }
        //$eno='100093';
        if (empty($q)) {
            $sql = 'select fafa_jid,login_account,ifnull(nick_name,"") as nick_name from we_staff where eno=? limit 0,100';
            $dataset = $DataAccess->GetData('we_staff', $sql, array((string) $eno));
        } else {
            $sql = 'select fafa_jid,login_account,ifnull(nick_name,"") as nick_name from we_staff where eno=? and ( substring_index(login_account,"@",1) like ? or nick_name like ?) limit 0,100';
            $dataset = $DataAccess->GetData('we_staff', $sql, array((string) $eno, '%' . $q . '%', '%' . $q . '%'));
        }
        $data = array();
        if (count($dataset['we_staff']['rows']) > 0) {
            $data = $dataset['we_staff']['rows'];
        }
        return $this->res(json_encode($data), 'json');
    }

    public function new_public_accountAction() {
        //通过network_domain 获得eno
        $DataAccess = $this->get('we_data_access');
        $network_domain = $this->getRequest()->get('network_domain');
        $sql = 'select enterprise_no from we_circle where network_domain=? limit 1';
        $dataset = $DataAccess->GetData('we_circle', $sql, array((string) $network_domain));
        if ($dataset['we_circle']['recordcount'] > 0) {
            $eno = $dataset['we_circle']['rows'][0]['enterprise_no'];
        }
        $ename = $this->getRequest()->get('ename');
        $etype = $this->getRequest()->get('etype');
        $empid = $this->getRequest()->get('empid');
        //$empid='v100085-sale-1353489523056779';
        //$eno='100093';
        if (empty($empid)) {
            $url = 'http://www.fafacn.com:800/controller.yaws?eno=' . $eno . '&name=' . $ename . '&type=' . $etype . '&method=service_org:savePublicAccount';
        } else {
            $url = 'http://www.fafacn.com:800/controller.yaws?empid=' . $empid . '&eno=' . $eno . '&name=' . $ename . '&type=' . $etype . '&method=service_org:savePublicAccount';
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($ch);
        curl_close($ch);
        $data1 = array();
        if (preg_match('/"succeed":true,/', $data)) {
            $data = substr($data, 1, strlen($data) - 6);
            $data = json_decode($data);
            $data = get_object_vars($data);
            $data = $data['data'];
            foreach ($data as $key => $value) {
                if (!is_array($value) || count($value) == 0)
                    continue;
                $data1[] = $value;
            }
            return $this->res(json_encode($data1[0]), 'json');
        }
        else {
            return $this->res(json_encode($data1), 'json');
        }
    }

    public function save_public_accountAction() {
        $network_domain = $this->getRequest()->get('network_domain');
        $empid = $this->getRequest()->get('empid');
        $account = $this->getRequest()->get('account');
        $data2 = array();
        //判断账号是否在在当前企业内
        $DataAccess = $this->get('we_data_access');
        $sql = 'select a.login_account from we_staff a,we_circle b where a.fafa_jid=? and  b.enterprise_no=a.eno and 					b.network_domain=?';
        $dataset = $DataAccess->GetData('we_staff', $sql, array((string) $account, (string) $network_domain));
        if ($dataset['we_staff']['recordcount'] <= 0) {
            return $this->res(json_encode($data2), 'json');
        }
        //$empid='v100085-service-1353480249089557';
        $url = 'http://www.fafacn.com:800/controller.yaws?empid=' . $empid . '&account=' . $account . '&method=service_org:savePublicAccount';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($ch);
        curl_close($ch);
        if (preg_match('/"succeed":true,/', $data)) {
            $data1 = $this->format_conv($data);
            foreach ($data1 as $key => $value) {
                if (is_array($value) && count($value) > 0)
                    $data2 = $value;
                else
                    continue;
            }
        }
        return $this->res(json_encode($data2), 'json');
    }

    public function save_default_wordsAction($network_domain) {
        $DataAccess = $this->get('we_data_access');
        $sql = 'select enterprise_no from we_circle where network_domain=?';
        $dataset = $DataAccess->GetData('we_circle', $sql, array((string) $network_domain));
        if ($dataset['we_circle']['recordcount'] <= 0)
            return $this->res('{"success":0}', 'json');
        $eno = $dataset['we_circle']['rows'][0]['enterprise_no'];
        $msg_textarea = $this->getRequest()->get('msg_textarea');
        $msg_textarea = trim($msg_textarea);
        if (preg_match('/;/', $msg_textarea)) {
            $msg_textarea = str_replace(';', '；', $msg_textarea);
        }
        $msg_array = explode('；', $msg_textarea);
        array_walk($msg_array, function(&$value, $key) {
                    $value = trim($value);
                });
        $msg_array = array_filter($msg_array, function($value) {
                    return strlen($value) > 0 ? true : false;
                });
        try {
            $sql = 'delete we_ocs_word from we_ocs_word where eno=?';
            $dataset = $DataAccess->ExecSQL($sql, array((string) $eno));
            foreach ($msg_array as $value) {
                $id = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($DataAccess, "we_ocs_word", "id");
                $words = $value;
                $sql = 'insert into we_ocs_word(id,eno,words) values(?,?,?)';
                $DataAccess->ExecSQL($sql, array((string) $id, (string) $eno, (string) $words));
            }
            return $this->res('{"success":1}', 'json');
        } catch (\Exception $e) {
            return $this->res('{"success:0"}');
        }
    }

    public function ifPicNull($pic) {
        if (empty($pic)) {
            $pic = $this->get('templating.helper.assets')->getUrl('bundles/fafatimewebase/images/downphoto.png');
        } else {
            $pic = $this->container->getParameter('FILE_WEBSERVER_URL') . $pic;
        }
        return $pic;
    }

    public function saveFile($filePath, $dm) {
        $doc = new \Justsy\MongoDocBundle\Document\WeDocument;
        $doc->setName(basename($filePath));
        $doc->setFile($filePath);
        $dm->persist($doc);
        $dm->flush();
        unlink($filePath);
        return $doc->getId();
    }

    public function deleteFile($fileId, $dm) {
        $doc = $dm->getRepository('JustsyMongoDocBundle:WeDocument')->find($fileId);
        if (!empty($doc)) {
            $dm->remove($doc);
            $dm->flush();
        }
        return true;
    }

    public function res($content, $type = 'html') {
        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/' . $type);
        $response->headers->set('charset', 'utf-8');
        return $response;
    }

    public function format_conv($data1) {
        $data = substr($data1, 1, strlen($data1) - 6);
        $data = json_decode($data);
        $data = get_object_vars($data);
        $data = $data['data'];
        return $data;
    }
    
    //删除公众号
    public function microaccount_deleteAction()
    {
    		$conn = $this->get('we_data_access');
        $conn_im = $this->get('we_data_access_im');
        $userinfo = $this->get('security.context')->getToken()->getUser();
        $logger=$this->get("logger");
        $getRequest=$this->getRequest();
        
        $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
        $micro_id=$getRequest->get("micro_id");
        $microFlag =$MicroAccountMgr->removeByID($micro_id);
        $re = array("success"=>$microFlag);
        $re["micro_id"] = $micro_id;
        return $this->res(json_encode($re),'json');
    }
    
    //搜索公众号接口 暂时没用
    public function microaccount_searchAction(){
    		$conn = $this->get('we_data_access');
        $conn_im = $this->get('we_data_access_im');
        $userinfo = $this->get('security.context')->getToken()->getUser();
        $logger=$this->get("logger");
        $getRequest=$this->getRequest();
        
        $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
        $micro_search=$getRequest->get("txtsearch");
        $micro =$MicroAccountMgr->microaccount_search($micro_search);
        return $this->res(json_encode($micro),'json');
    }
    
    //公众号列表数据管理页面
    public function microitemAction($network_domain){
    		$conn = $this->get('we_data_access');
        $conn_im = $this->get('we_data_access_im');
        $userinfo = $this->get('security.context')->getToken()->getUser();
        $logger=$this->get("logger");
        
        $micro_data=$this->getRequest()->get("micro_data");
         
        $array["curr_network_domain"]=$network_domain;
        if(!empty($micro_data)){
        	$array["micro_data"]=$micro_data;
        }else{
        	$array["micro_data"]=array();
        }
        
        return $this->render('JustsyBaseBundle:EnterpriseSetting:microitem.html.twig', $array);
    }
    
    //粉丝页面
    public function microfansAction($network_domain){
    		$conn = $this->get('we_data_access');
        $conn_im = $this->get('we_data_access_im');
        $userinfo = $this->get('security.context')->getToken()->getUser();
        $logger=$this->get("logger");
        $getRequest=$this->getRequest();
        
        $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
        $micro_account=$getRequest->get("micro_number");
        $micro_name=$getRequest->get("micro_name");
        $micro_concern_approval=$getRequest->get("micro_concern_approval");
        $micro_type=$getRequest->get("micro_type");
        $micro_page_size=$getRequest->get("micro_pagesize");
	  		$micro_page_index=$getRequest->get("micro_pageindex");
	  		$groupid=$getRequest->get("groupid");
	  		if(empty($groupid))$groupid=0;
	  		$txtsearch=$getRequest->get("txtsearch");
	  		if(empty($micro_account)){
	  			$txtsearch="";
	  			$micro_name="";
	  			$micro_concern_approval=false;
	  			$micro_type=0;
	  			$micro_page_size=10;
	  			$micro_page_index=1;
	  		}
	  		$micro_fans["micro_concern_approval"]=$micro_concern_approval;
	  		$micro_fans["micro_type"]=$micro_type;
	  		$micro_fans["micro_pageindex"]=$micro_page_index;
	  		$micro_fans["micro_page_size"]=$micro_page_size;
        $micro_fans["curr_network_domain"]=$network_domain;
        $micro_fans["txtsearch"]=$txtsearch;
        $micro_fans["micro_name"]=$micro_name;
        $micro_fans["micro_account"]=$micro_account; 
        $micro_fans["groupid"]=$groupid; 
        
        $data =$MicroAccountMgr->get_micro_fans($micro_account,$txtsearch,$micro_page_size,$micro_page_index,$groupid);
        //粉丝总记录数
        $micro_fans["micro_fans_count"]=$data["micro_fans_count"];
        //最大页数
        $micro_fans["micro_page_max_index"]=$data["micro_page_max_index"];
        //粉丝列表
        $micro_fans["micro_fans_data"]=$data["micro_fans_data"];
        
        $micro_fans_ungrouped_count =$MicroAccountMgr->get_fans_ungrouped_count($micro_account);
        //未分组成员数
        $micro_fans["micro_fans_ungrouped_count"]=$micro_fans_ungrouped_count["max_count"]-$micro_fans_ungrouped_count["group_count"];
        $micro_fans["micro_fans_max_count"]=$micro_fans_ungrouped_count["max_count"];
        $groupdata =$MicroAccountMgr->grouplist($micro_account);
        //分组数据集合
        $micro_fans["micro_fans_groupdata"]=$groupdata;
        
        return $this->render('JustsyBaseBundle:EnterpriseSetting:microfans.html.twig', $micro_fans);
    }
    
    //初始化公众号页面
    public function microaccountAction($network_domain) {
        if (0 == $this->get('security.context')->getToken()->getUser()->is_in_manager_circles($network_domain)) {
            return $this->render('JustsyBaseBundle:EnterpriseSetting:no_rights.html.twig');
        }
        $conn = $this->get('we_data_access');
        $conn_im = $this->get('we_data_access_im');
        $userinfo = $this->get('security.context')->getToken()->getUser();
        $logger=$this->get("logger");
        
        $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
        $data =$MicroAccountMgr->getmicroaccount();
        
        $array["curr_network_domain"]=$network_domain;
        if(!empty($data)){
        	$photo_url = $this->container->getParameter('FILE_WEBSERVER_URL');
        	for($i=0;$i<count($data);$i++)
        	{
        	   $data[$i]["logo_path"] = $photo_url.$data[$i]["logo_path"];
        	   $data[$i]["logo_path_big"] = $photo_url.$data[$i]["logo_path_big"];
        	   $data[$i]["logo_path_small"] = $photo_url.$data[$i]["logo_path_small"];
        	}
        	$array["micro_json_data"]=json_encode($data);
        	$array["micro_data"]=$data;
        }else{
        	$array["micro_json_data"]="[]";
        	$array["micro_data"]=array();
        }
        $array["path"]="";
        
        $EnoParamManager=new EnoParamManager($conn,$logger);
        //外部公众号企业对应参数
        $enoparam_external=$EnoParamManager->getParamByEno($userinfo->getEno(),'micro_external_count');
        //内部公众号企业对应参数
        $enoparam_internal=$EnoParamManager->getParamByEno($userinfo->getEno(),'micro_internal_count');
        
        $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
        //获取所有公众号已经创建个数和内部公众号已经创建个数
        $micro_count=$MicroAccountMgr->getmicrocount();
        //获取外部公众号一共能创建多少个数
        if(!empty($enoparam_external)){
        	$micro_external_param_value=$enoparam_external["micro_external_count"]["param_value"];
        	$array["micro_external_param_value"]=$micro_external_param_value;
        }else{
        	$array["micro_external_param_value"]=0;
        }
        //获取内部公众号一共能创建多少个数
        if(!empty($enoparam_internal)){
        	$micro_internal_param_value=$enoparam_internal["micro_internal_count"]["param_value"];
        	$array["micro_internal_param_value"]=$micro_internal_param_value;
        }else{
        	$array["micro_internal_param_value"]=0;
        }
        $array["micro_internal_count"]=$micro_count["count"];
        $array["micro_external_count"]=$micro_count["allcount"]-$micro_count["count"];
        
        return $this->render('JustsyBaseBundle:EnterpriseSetting:microaccount.html.twig', $array);
    }
    
		//添加或修改化公众号页面
    public function microaccount_addAction($network_domain) {
        if (0 == $this->get('security.context')->getToken()->getUser()->is_in_manager_circles($network_domain)) {
            return $this->render('JustsyBaseBundle:EnterpriseSetting:no_rights.html.twig');
        }
        $conn = $this->get('we_data_access');
        $conn_im = $this->get('we_data_access_im');
        $userinfo = $this->get('security.context')->getToken()->getUser();
        $eno=$userinfo->getEno();
        $logger=$this->get("logger");
        $micro_id=$this->getRequest()->get("micro_id");
        
        if(!empty($micro_id)){
	        $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
	        $data =$MicroAccountMgr->get_micro_data_id($micro_id);
	        if(!empty($data)){
	        	$data[0]["logo_path"] = $this->container->getParameter('FILE_WEBSERVER_URL').$data[0]["logo_path"];
	        	$data[0]["logo_path_big"] = $this->container->getParameter('FILE_WEBSERVER_URL').$data[0]["logo_path_big"];
	        	$data[0]["logo_path_small"] = $this->container->getParameter('FILE_WEBSERVER_URL').$data[0]["logo_path_small"];
        		$array["micro_data"]=json_encode($data);
	        }else{
	        	$array["micro_data"]="[]";
	        }
        }else{
        	$array["micro_data"]="[]";
        }
        $array["curr_network_domain"]=$network_domain;
        
        $path=$this->get('templating.helper.assets')->geturl('bundles/fafatimewebase/images/no_photo.png');
        $array["path"]=$path;
        
        $EnoParamManager=new EnoParamManager($conn,$logger);
        //外部公众号企业对应参数
        $enoparam_external=$EnoParamManager->getParamByEno($userinfo->getEno(),'micro_external_count');
        //内部公众号企业对应参数
        $enoparam_internal=$EnoParamManager->getParamByEno($userinfo->getEno(),'micro_internal_count');
        
        $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
        //获取所有公众号已经创建个数和内部公众号已经创建个数
        $micro_count=$MicroAccountMgr->getmicrocount();
        //获取外部公众号一共能创建多少个数
        if(!empty($enoparam_external)){
        	$micro_external_param_value=$enoparam_external["micro_external_count"]["param_value"];
        	$array["micro_external_param_value"]=$micro_external_param_value;
        }else{
        	$array["micro_external_param_value"]=0;
        }
        //获取内部公众号一共能创建多少个数
        if(!empty($enoparam_internal)){
        	$micro_internal_param_value=$enoparam_internal["micro_internal_count"]["param_value"];
        	$array["micro_internal_param_value"]=$micro_internal_param_value;
        }else{
        	$array["micro_internal_param_value"]=0;
        }
        $array["micro_internal_count"]=$micro_count["count"];
        $array["micro_external_count"]=$micro_count["allcount"]-$micro_count["count"];
        
        return $this->render('JustsyBaseBundle:EnterpriseSetting:microaccount_add.html.twig', $array);
    } 
    
		//新增或修改公众号数据
    public function savemicroaccountAction($network_domain) {
    		$conn = $this->get('we_data_access');
        $conn_im = $this->get('we_data_access_im');
        $userinfo = $this->get('security.context')->getToken()->getUser();
        $logger=$this->get("logger");
        
        $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
        $getRequest=$this->getRequest();
        
	      $session = $this->get('session'); 
		    $filename120 = $session->get("avatar_big");
		    $filename48 = $session->get("avatar_middle");
		    $filename24 = $session->get("avatar_small");
		    
		    $dm = $this->get('doctrine.odm.mongodb.document_manager');
		    if (!empty($filename120)) $filename120= $this->saveFile($filename120,$dm);
		    if (!empty($filename48)) $filename48=   $this->saveFile($filename48,$dm);
		    if (!empty($filename24)) $filename24=   $this->saveFile($filename24,$dm);
		    $session->remove("avatar_big");
		    $session->remove("avatar_middle");
		    $session->remove("avatar_small");      
		    
		    $factory = $this->get('security.encoder_factory');
		    $micro_id = $getRequest->get("id");
        $number = $getRequest->get('micro_number');
        $name= $getRequest->get('micro_name');
        $type= $getRequest->get('type');
        $introduction= $getRequest->get('introduction');
        $concern_approval= $getRequest->get('concern_approval');
        $salutatory= $getRequest->get('salutatory');
        $level= $getRequest->get('send_status');
        $password=$getRequest->get('password');
        $micro_use=$getRequest->get('micro_use');
		    
        $dataexec =$MicroAccountMgr->register($micro_id,$number,$name,$type,$micro_use,$introduction,$concern_approval,$salutatory,$level,$password,$filename48,$filename120,$filename24,$factory,$dm);
        //$dataexec =$MicroAccountMgr->register($request,"","","");
        $r = array("success"=> false);
        
        if ($dataexec) {
        	  $r["success"]=true;
        	  $r["id"] = $dataexec;
        	  $r["logo_path"] = $this->container->getParameter('FILE_WEBSERVER_URL').$filename120;
        }
        return $this->res(json_encode($r),'json');
    }
    
    //检测公众号帐号是否存在
    public function check_micro_numberAction(){
	    	$request=$this->getRequest();
	    	$number=$request->get("micro_number");
	    	$conn = $this->get('we_data_access');
	      $conn_im = $this->get('we_data_access_im');
	      $userinfo = $this->get('security.context')->getToken()->getUser();
	      $logger=$this->get("logger");
    	  $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
    	  
      	$number_count=$MicroAccountMgr->check_micro_number($number);
	      if ($number_count>0) {
            return $this->res('{success:true}', 'json');//不可以
        } else {
            return $this->res('{success:false}', 'json');
        }
    }
    //检测公众号名称是否存在
    public function check_micro_nameAction(){
	    	$request=$this->getRequest();
	    	$name=$request->get("micro_name");
	    	$old_name=$request->get("micro_old_name");
	    	$conn = $this->get('we_data_access');
	      $conn_im = $this->get('we_data_access_im');
	      $userinfo = $this->get('security.context')->getToken()->getUser();
	      $logger=$this->get("logger");
	      
    	  $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
      	$number_count=$MicroAccountMgr->check_micro_name($name,$old_name,$userinfo->getEno());
      	
	      if ($number_count>0) {
            return $this->res('{success:true}', 'json');//不可以
        } else {
            return $this->res('{success:false}', 'json');
        }
    }
    
    //粉丝关注并修改对应粉丝数
    public function change_micro_fansAction(){
    	  $conn = $this->get('we_data_access');
        $conn_im = $this->get('we_data_access_im');
        $userinfo = $this->get('security.context')->getToken()->getUser();
        $logger=$this->get("logger");
        $request=$this->getRequest();
        $login_account=$userinfo->getUsername();
        
        $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
        
		  	$micro_number=$request->get("micro_number");
		  	$obj=$request->get("obj");
		  	$obj_type=$request->get("obj_type");
		  	$array["success"]=0;
		  	$array["login_account"]=array();
		  	switch ($obj_type) { 	
		  		case "friend": 		
		  			$dataexec =$MicroAccountMgr->micro_fans_friend($micro_number,$obj);
		  			$array["success"]=$dataexec["success"];
		  			$array["login_account"]=$dataexec["login_account"];
		  		break; 	
		  		case "group": 		
		  			$dataexec =$MicroAccountMgr->micro_fans_group($micro_number,$obj);
		  			$array["success"]=$dataexec["success"];
		  			$array["login_account"]=$dataexec["login_account"];
		  		break; 	
		  		case "circle": 		
		  			$dataexec =$MicroAccountMgr->micro_fans_circle($micro_number,$obj);
		  			$array["success"]=$dataexec["success"];
		  			$array["login_account"]=$dataexec["login_account"];
		  		break; 	
		  		case "enterprise": 		
		  			$dataexec =$MicroAccountMgr->micro_fans_enterprise($micro_number,$obj);
		  			$array["success"]=$dataexec["success"];
		  			$array["login_account"]=$dataexec["login_account"];
		  		break; 	
		  		default:
		  			$array["success"] =$MicroAccountMgr->micro_fans_attention($micro_number,$login_account);
		  		break;
		    }
        $returnstring="{success:".$array["success"].",login_account:'".json_encode($array["login_account"])."'}";
        return $this->res($returnstring, 'json');
    }
    
    //获取对应公众号粉丝列表
    public function get_micro_fansAction(){
    		$conn = $this->get('we_data_access');
        $conn_im = $this->get('we_data_access_im');
        $userinfo = $this->get('security.context')->getToken()->getUser();
        $logger=$this->get("logger");
        $request=$this->getRequest();
        
        $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
        $micro_account=$request->get("micro_account");
        $micro_page_size=$request->get("micro_pagesize");
	  		$micro_page_index=$request->get("micro_pageindex");
	  		$txtsearch=$request->get("txtsearch");
	  		
        $micro_fans =$MicroAccountMgr->get_micro_fans($micro_account,$txtsearch,$micro_page_size,$micro_page_index);
        
        return $this->res(json_encode($micro_fans),'json');
    }
    
    //修改公众号LOGO标志接口
    public function change_micro_logoAction(){
    	$conn = $this->get('we_data_access');
      $conn_im = $this->get('we_data_access_im');
      $userinfo = $this->get('security.context')->getToken()->getUser();
      $logger=$this->get("logger");
      $request=$this->getRequest();
      
      $session = $this->get('session'); 
	    $filename120 = $session->get("avatar_big");
	    $filename48 = $session->get("avatar_middle");
	    $filename24 = $session->get("avatar_small");
	    
	    $dm = $this->get('doctrine.odm.mongodb.document_manager');
	    if (!empty($filename120)) $filename120= $this->saveFile($filename120,$dm);
	    if (!empty($filename48)) $filename48=   $this->saveFile($filename48,$dm);
	    if (!empty($filename24)) $filename24=   $this->saveFile($filename24,$dm);
	    $session->remove("avatar_big");
	    $session->remove("avatar_middle");
	    $session->remove("avatar_small");   
	    
	    if(empty($filename48)){
      	return $this->res('{success:2}', 'json');
      }
      $micro_id=$this->getRequest()->get("micro_id");
      if(empty($micro_id)){
      	return $this->res('{success:3}', 'json');
      }
      $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
      $dataexec =$MicroAccountMgr->change_logo_path($micro_id,$filename120,$filename48,$filename24);
      $r = array("success"=> false);
       if ($dataexec) {
        	  $r["success"]=true;
        	  $r["id"] = $micro_id;
        	  $r["logo_path"] = $this->container->getParameter('FILE_WEBSERVER_URL').$filename120;
       }
       return $this->res(json_encode($r),'json');
    }
    
    //获取所有公众号数据
    public function getmicroaccountAction(){
    	 $conn = $this->get('we_data_access');
    	 $conn_im = $this->get('we_data_access_im');
    	 $userinfo = $this->get('security.context')->getToken()->getUser();
       $logger=$this->get("logger");
       
       $MicroAccountMgr=new MicroAccountMgr($conn,$conn_im,$userinfo,$logger, $this->container);
       $data =$MicroAccountMgr->getmicroaccount();
       
       return $this->res(json_encode($data), 'json');
    }
    //ad数据同步
    public function syncLdapAction(){
    	$conn = $this->get('we_data_access');
    	
    }

    private function saveldapstaffmapping($res)
    {
    	$conn = $this->get('we_data_access');
    	$uid = $res->get("uid");
    	$ou = $res->get("ou");
    	$reg_name = $res->get("reg_name");
    	$email = $res->get("email");
    	$mobile = $res->get("mobile");
    	$sql = "delete from we_datasync_mapping where typecode='1'";
    	$conn->ExecSQL($sql,array());
    	$ary=array();
    	$ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code)values('1','uid','".$uid."')";
    	$ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code)values('1','ou','".$ou."')";
    	$ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code)values('1','email','".$email."')";
    	$ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code)values('1','reg_name','".$reg_name."')";
    	$ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code)values('1','mobile','".$mobile."')";
    	$conn->ExecSQLs($ary,array());
    	$re=array("s"=>"1","m"=>"");
    	$response = new Response(json_encode($re));
	    $response->headers->set('Content-Type', 'text/json');
	    return $response;    	
    }

    private function saveldaporgmapping($res)
    {
    	$conn = $this->get('we_data_access');
    	$uid = $res->get("uid");
    	$ou = $res->get("ou");
    	$parentdn = $res->get("parentdn");
    	$orgpath = $res->get("orgpath");
    	$sql = "delete from we_datasync_mapping where typecode='2'";
    	$conn->ExecSQL($sql,array());
    	$ary=array();
    	$ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code)values('2','uid','".$uid."')";
    	$ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code)values('2','ou','".$ou."')";
    	$ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code)values('2','parentdn','".$parentdn."')";
    	$ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code)values('2','orgpath','".$orgpath."')";
    	
    	$conn->ExecSQLs($ary,array());
    	$re=array("s"=>"1","m"=>"");
    	$response = new Response(json_encode($re));
	    $response->headers->set('Content-Type', 'text/json');
	    return $response;
    }

    public function saveLdapDataAction()
    {

    	$request=$this->getRequest();
    	$action=$request->get("action");

    	if($action=="staffmapping")
    	{
    		return $this->saveldapstaffmapping($request);
    	}

		if($action=="orgmapping")
    	{
    		return $this->saveldaporgmapping($request);
    	}


    	$conn = $this->get('we_data_access');
    	$user = $this->get('security.context')->getToken()->getUser();
    	$re=array("s"=>"1","m"=>"");
    	
    	$ipaddress=$request->get("ipaddress");
    	$userid=$request->get("userid");
    	$pwd=$request->get("pwd");
    	$basedn=$request->get("basedn");
    	$path_org=$request->get("path_org");
    	$path_staff=$request->get("path_staff");
    	$system=$request->get("system");
    	$domain=$request->get("domain");
    	
    	$filter_exp=$request->get("filter_exp");
    	$filter_exp_org=$request->get("filter_exp_org");
    	//查询是否存在
    	$sql="select id from we_ad_config";
    	$ds=$conn->Getdata('info',$sql);
    	if($ds['info']['recordcount']>0){
    		$id=$ds["info"]["rows"][0]["id"];
    		$sql="update we_ad_config set userid=?,pwd=?,ipaddress=?,eno=?,domain=?,system=?,basedn=?,path_org=?,path_staff=?,filter_exp=?,filter_exp_org=? where id=?";
    		$params=array($userid,$pwd,$ipaddress,$user->eno,$domain,$system,$basedn,$path_org,$path_staff,$filter_exp,$filter_exp_org,$id);
    		$conn->ExecSQL($sql,$params);
    	}
    	else{
    		$id=\Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($conn, "we_ad_config", "id");
    	
	    	$sql="insert into we_ad_config (id,eno,ipaddress,userid,pwd,basedn,system,domain,path_org,path_staff,filter_exp,filter_exp_org) values(?,?,?,?,?,?,?,?,?,?,?,?)";
	    	$params=array($id,$user->eno,$ipaddress,$userid,$pwd,$basedn,$system,$domain,$path_org,$path_staff,$filter_exp,$filter_exp_org);
	    	
	    	$conn->ExecSQL($sql,$params);
    	}
    	
		$response = new Response(json_encode($re));
	    $response->headers->set('Content-Type', 'text/json');
	    return $response;
    }
    public function ldapDataAction($network_domain)
    {
    	$request=$this->getRequest();
    	$conn = $this->get('we_data_access');
    	$user = $this->get('security.context')->getToken()->getUser();
    	
    	$sql="select * from we_ad_config limit 0,1";
    	$ds=$conn->Getdata("info",$sql);
    	$ldap=array("ipaddress"=> "","pwd"=> "","userid"=> "");
    	if($ds["info"]["recordcount"]>0){
    		$ldap=$ds["info"]["rows"][0];
    	}

    	$sql = "select * from we_datasync_mapping where typecode='1'";
    	$ds=$conn->Getdata("info2",$sql);
    	$staffmapping=array();
    	if($ds["info2"]["recordcount"]>0){
    		$staffmapping=$ds["info2"]["rows"];
    	}

    	$sql = "select * from we_datasync_mapping where typecode='2'";
    	$ds=$conn->Getdata("info3",$sql);
    	$orgmapping=array();
    	if($ds["info3"]["recordcount"]>0){
    		$orgmapping=$ds["info3"]["rows"];
    	}
    	return $this->render('JustsyBaseBundle:EnterpriseSetting:ldapdata.html.twig',array("curr_network_domain"=> $network_domain,"ldap"=> $ldap,"staffmapping"=>json_encode( $staffmapping),"orgmapping"=>json_encode($orgmapping)));
    }

    public function syncstafforgDataAction($network_domain)
    {
        $conn = $this->get('we_data_access');
        $sql = "select * from we_datasync_mapping where typecode='3'";
        $ds=$conn->Getdata("info2",$sql);
        $staffmapping=array();
        if($ds["info2"]["recordcount"]>0){
            $staffmapping=$ds["info2"]["rows"];
        }

        $sql = "select * from we_datasync_mapping where typecode='4'";
        $ds=$conn->Getdata("info3",$sql);
        $orgmapping=array();
        if($ds["info3"]["recordcount"]>0){
            $orgmapping=$ds["info3"]["rows"];
        }
        return $this->render('JustsyBaseBundle:EnterpriseSetting:syncstafforgdata.html.twig',array("curr_network_domain"=> $network_domain,"staffmapping"=>json_encode( $staffmapping),"orgmapping"=>json_encode($orgmapping)));
    }

    public function syncdatabasestafforgDataAction($network_domain)
    {
        $conn = $this->get('we_data_access');
        $sql = "select * from we_datasync_mapping where typecode='db'";
        $ds=$conn->Getdata("dbconninfo",$sql);
        $dbconninfo=array();
        if($ds["dbconninfo"]["recordcount"]>0){
            $dbconninfo=$ds["dbconninfo"]["rows"];
        }

        $sql = "select * from we_datasync_mapping where typecode='ds'";
        $ds=$conn->Getdata("info2",$sql);
        $staffmapping=array();
        if($ds["info2"]["recordcount"]>0){
            $staffmapping=$ds["info2"]["rows"];
        }

        $sql = "select * from we_datasync_mapping where typecode='do'";
        $ds=$conn->Getdata("info3",$sql);
        $orgmapping=array();
        if($ds["info3"]["recordcount"]>0){
            $orgmapping=$ds["info3"]["rows"];
        }
        return $this->render('JustsyBaseBundle:EnterpriseSetting:syncdbstafforgdata.html.twig',array("curr_network_domain"=> $network_domain,"dbconninfo"=>json_encode( $dbconninfo),"staffmapping"=>json_encode( $staffmapping),"orgmapping"=>json_encode($orgmapping)));
    }

    //保存数据库同步配置
    public function savedatabasesyncAction(){
        $request=$this->getRequest();
        $action=$request->get("action");
        if($action=="staffmapping")
        {
            return $this->savedatabasesyncstaffmapping($request);
        }

        if($action=="orgmapping")
        {
            return $this->savedatabasesyncorgmapping($request);
        }
        $conn = $this->get('we_data_access');
        $user = $this->get('security.context')->getToken()->getUser();
        $re=array("s"=>"1","m"=>"");
        
        $dbtype=$request->get("dbtype");
        $dbname=$request->get("dbname");
        $dbuser=$request->get("dbuser");
        $dbpwd=$request->get("dbpwd");
        $dburl=$request->get("dburl");

        $staff_query=$request->get("staff_query");
        $org_query=$request->get("org_query");
        //$staff_parserule=$request->get("staff_parserule");
        //$org_parserule=$request->get("org_parserule");

        $sql = "delete from we_datasync_mapping where (typecode='db') ";
        $conn->ExecSQL($sql,array());
        $ary=array();
        $ary_paras = array();
        $ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code,parse_rule)values('db','type',?,'')";
        $ary_paras[] = array((string)$dbtype);
        $ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code,parse_rule)values('db','dburl',?,'')";
        $ary_paras[] = array((string)$dburl);
        $ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code,parse_rule)values('db','dbname',?,'')";
        $ary_paras[] = array((string)$dbname);
        $ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code,parse_rule)values('db','dbuser',?,'')";
        $ary_paras[] = array((string)$dbuser);
        $ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code,parse_rule)values('db','dbpwd',?,'')";
        $ary_paras[] = array((string)$dbpwd);
        $ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code,parse_rule)values('db','staff_query',?,'')";
        $ary_paras[] = array((string)$staff_query);
        $ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code,parse_rule)values('db','org_query',?,'')";
        $ary_paras[] = array((string)$org_query);
        $conn->ExecSQLs($ary,$ary_paras);
        $re=array("s"=>"1","m"=>"");
        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }

    private function savedatabasesyncstaffmapping($res)
    {
        $conn = $this->get('we_data_access');
        $uid = $res->get("uid");
        $ou = $res->get("ou");
        $reg_name = $res->get("reg_name");
        $email = $res->get("email");
        $mobile = $res->get("mobile");
        $pwd = $res->get("pwd");
        $sql = "delete from we_datasync_mapping where typecode='ds' ";
        $conn->ExecSQL($sql,array());
        $ary=array();
        $ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code)values('ds','uid','".$uid."')";
        $ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code)values('ds','ou','".$ou."')";
        $ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code)values('ds','email','".$email."')";
        $ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code)values('ds','pwd','".$pwd."')";
        $ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code)values('ds','reg_name','".$reg_name."')";
        $ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code)values('ds','mobile','".$mobile."')";
        $conn->ExecSQLs($ary,array());
        $re=array("s"=>"1","m"=>"");
        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;       
    }

    private function savedatabasesyncorgmapping($res)
    {
        $conn = $this->get('we_data_access');
        $uid = $res->get("uid");
        $ou = $res->get("ou");
        $parentdn = $res->get("parentdn");
        $orgpath = $res->get("orgpath");
        $sql = "delete from we_datasync_mapping where typecode='do'";
        $conn->ExecSQL($sql,array());
        $ary=array();
        $ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code)values('do','uid','".$uid."')";
        $ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code)values('do','ou','".$ou."')";
        $ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code)values('do','parentdn','".$parentdn."')";
        $ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code)values('do','orgpath','".$orgpath."')";
        
        $conn->ExecSQLs($ary,array());
        $re=array("s"=>"1","m"=>"");
        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }

    public function savesyncstafforgDataAction(){
        $request=$this->getRequest();
        $action=$request->get("action");
        if($action=="staffmapping")
        {
            return $this->savesyncstaffmapping($request);
        }

        if($action=="orgmapping")
        {
            return $this->savesyncorgmapping($request);
        }
        $conn = $this->get('we_data_access');
        $user = $this->get('security.context')->getToken()->getUser();
        $re=array("s"=>"1","m"=>"");
        
        $staffurl=$request->get("staffurl");
        $orgurl=$request->get("orgurl");

        $staff_parserule=$request->get("staff_parserule");
        $org_parserule=$request->get("org_parserule");

        $sql = "delete from we_datasync_mapping where (typecode='3' or typecode='4') and wefafa_attr_code='infurl'";
        $conn->ExecSQL($sql,array());
        $ary=array();
        $ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code,parse_rule)values('3','infurl','".$staffurl."','".$staff_parserule."')";
        $ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code,parse_rule)values('4','infurl','".$orgurl."','".$org_parserule."')";
        $conn->ExecSQLs($ary,array());
        $re=array("s"=>"1","m"=>"");
        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;             
    }
		public function weiboMgrAction($network_domain)
		{
			$da = $this->get('we_data_access');
			$user = $this->get('security.context')->getToken()->getUser();
			$weiboMgr=new WeiboMgr($da,$this->get('logger'));
			$accounts=$weiboMgr->getAccounts($user->eno);
			return $this->render("JustsyBaseBundle:EnterpriseSetting:weibomgr.html.twig",array("curr_network_domain"=> $network_domain,'accounts'=> $accounts));
		}
    private function savesyncstaffmapping($res)
    {
        $conn = $this->get('we_data_access');
        $uid = $res->get("uid");
        $ou = $res->get("ou");
        $reg_name = $res->get("reg_name");
        $email = $res->get("email");
        $mobile = $res->get("mobile");
        $pwd = $res->get("pwd");
        $sql = "delete from we_datasync_mapping where typecode='3' and wefafa_attr_code!='infurl'";
        $conn->ExecSQL($sql,array());
        $ary=array();
        $ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code)values('3','uid','".$uid."')";
        $ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code)values('3','ou','".$ou."')";
        $ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code)values('3','email','".$email."')";
        $ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code)values('3','pwd','".$pwd."')";
        $ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code)values('3','reg_name','".$reg_name."')";
        $ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code)values('3','mobile','".$mobile."')";
        $conn->ExecSQLs($ary,array());
        $re=array("s"=>"1","m"=>"");
        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;       
    }

    private function savesyncorgmapping($res)
    {
        $conn = $this->get('we_data_access');
        $uid = $res->get("uid");
        $ou = $res->get("ou");
        $parentdn = $res->get("parentdn");
        $orgpath = $res->get("orgpath");
        $sql = "delete from we_datasync_mapping where typecode='4' and wefafa_attr_code!='infurl'";
        $conn->ExecSQL($sql,array());
        $ary=array();
        $ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code)values('4','uid','".$uid."')";
        $ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code)values('4','ou','".$ou."')";
        $ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code)values('4','parentdn','".$parentdn."')";
        $ary[]="insert into we_datasync_mapping(typecode,wefafa_attr_code,source_code)values('4','orgpath','".$orgpath."')";
        
        $conn->ExecSQLs($ary,array());
        $re=array("s"=>"1","m"=>"");
        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }
}