<?php

namespace Justsy\AdminAppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\Login\UserProvider;
use Justsy\BaseBundle\Login\UserSession;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Management\Staff;

class CMSManageController extends Controller
{
  public function publishAction()
  {
  	$request = $this->getRequest();
   	$type = $request->get("m");
   	$typename = "";
    if ($type=="1")
   	  $typename="制度";
   	else if($type=="2")
   	  $typename="福利";
   	$data = json_encode($this->InitData($type));
  	return $this->render('JustsyAdminAppBundle:Publish:publish.html.twig',array("type"=>$type,"typename"=>$typename,"data"=> $data));
  }
  
  //为页面初始化数据
  public function InitData($type){
  	$da = $this->get("we_data_access");
   	$request = $this->getRequest();
   	//初始化日期范围数据
  	$sql="select date_format(`date`,'%Y 年 %m 月') s_date,date_format(`date`,'%Y-%m-01') val from mb_content_publish where type=? group by s_date order by date desc";
  	$ds = $da->GetData("table",$sql,array((string)$type));
  	$datedata = array();
  	if ( $ds && $ds["table"]["recordcount"]>0)
  	  $datedata = $ds["table"]["rows"];
  	//如果为制度，返回制度类型
  	$zdlb = array();
  	if ( $type=="1"){
  		$sql="select * from mb_institution_code union select '0','' order by codeid asc;";
  		$ds=$da->GetData("table",$sql);
  		if ($ds && $ds["table"]["recordcount"]>0)
  		  $zdlb = $ds["table"]["rows"];
  	}
  	//处理返回结果
  	$getdata = $this->searchpublish($type,null,null,null,1,14);
  	$dataSource = array();
  	$recordcount = array();
  	if ($getdata!=null && count($getdata)>0){
  		$dataSource = $getdata["dataSource"];
  		$recordcount = $getdata["recordcount"];
  	}
  	$result = array("dates"=> $datedata,"dataSource"=> $dataSource,"recordcount"=> $recordcount ,"zdtype"=> $zdlb );
    return $result;
  }
  
  //查询数据
  public function searchpublish($type,$date,$title,$zdtype,$pageindex,$rowrecord)
  {
  	 $da = $this->get("we_data_access");
  	 $limit = " limit ".(($pageindex - 1) * $rowrecord).",".$rowrecord;
  	 //条件
  	 $condition = "";
  	 $parameter = array();
  	 if ($title!=null && !empty($title)){
  	   $condition = " and `title` like concat(?,'%')";
  	   array_push($parameter,(string)$title);
  	 }
  	 if ($date!= null && !empty($date)){
  	 	 $enddate = date("Y-m-d",strtotime("$date +1 month"));
  	 	 $condition = $condition." and (date between ? and ?)";
  	 	 array_push($parameter,(string)$date,(string)$enddate);
  	 }
  	 if ($zdtype!=null && !empty($zdtype) && $zdtype!="0") {
  	 	  $condition = $condition." and zdtype=? ";
  	 	  array_push($parameter,(string)$zdtype);
  	 }
  	 if ($type!=null && !empty($type)){
  	 	 $condition = $condition." and `type`=?";
  	 	 array_push($parameter,(string)$type);
  	 }
  	 $sql = "select a.id,title,date,date_format(`date`,'%Y-%m-%d %H:%i') 'date' ,nick_name,
  	                case when type=1 then ifnull((select name from mb_institution_code where codeid=zdtype),'') else case zdtype when 1 then '福利项目' when 2 then '福利关怀' else '' end end typename
  	        from mb_content_publish a inner join we_staff b on a.publish_staffid=b.login_account where 1=1 ".
  	        $condition." order by date desc ".$limit;
  	 $ds = ($parameter!=null && count($parameter)>0) ? $da->GetData("table",$sql,$parameter) : $da->GetData("table",$sql);
  	 
  	 $data = null;
  	 $recordcount = 0;
  	 if ( $ds && $ds["table"]["recordcount"]>0){
  	    $data = $ds["table"]["rows"];
  	    if ( $pageindex==1){
  	    	$sql = "select count(*) recordcount from mb_content_publish a inner join we_staff b on a.publish_staffid=b.login_account where 1=1 ".$condition;
  	    	$ds = ($parameter!=null && count($parameter)>0) ? $da->GetData("table",$sql,$parameter) : $da->GetData("table",$sql);
  	    	$recordcount = $ds["table"]["rows"][0]["recordcount"];
  	    }
  	 }
  	 $result = array("dataSource"=> $data,"recordcount"=> $recordcount);
     return $result;  	
  }
  
  //查询数据（由页面触发）
  public function searchPulishAction()
  {
  	 $da = $this->get("we_data_access");
  	 $request = $this->getRequest();
  	 $type = $request->get("type");
  	 $date = $request->get("date");
  	 $title = $request->get("title");
  	 $zdtype = $request->get("zdtype");
  	 $pageindex = $request->get("pageindex");
  	 $rowrecord = $request->get("rowrecord");//每页显示记录条数  	 
  	 $result = $this->searchpublish($type,$date,$title,$zdtype,$pageindex,$rowrecord);
  	 $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;
  }
  
  //根据publishid查询数据
  public function searchPulishByIdAction(){
  	$da = $this->get("we_data_access");
  	$request = $this->getRequest();
  	$id = $request->get("id");
  	$sqls = array();
  	$paras = array();
  	array_push($sqls,"select title,zdtype,content from mb_content_publish where id=?");
  	$sql2 = "select level1,case when leveltype=3 and ifnull(level2,'')!='' then concat(level1,'-',level2) else level2 end level2,
                    case when leveltype=3 and ifnull(level3,'')!='' then concat(level1,'-',level2,'-',level3) else level3 end level3,
                    case when leveltype=3 and ifnull(level4,'')!='' then concat(level1,'-',level2,'-',level3,'-',level4) else level4 end level4,leveltype as type
            from mb_content_publish_sub where publishid=?";
  	array_push($sqls,$sql2);
  	array_push($paras,array((string)$id));
  	array_push($paras,array((string)$id));
  	$ds = $da->GetDatas(array("table1","table2"),$sqls,$paras);  		
  	$result = array("main"=> $ds["table1"]["rows"],"child"=>$ds["table2"]["rows"]);  	
  	$response = new Response(json_encode($result));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  	
  }
  
  //保存内容发送数据记录
  public function editPublishAction(){ 
  	$da = $this->get("we_data_access");
  	$request = $this->getRequest();
  	$publishid = $request->get("publishid");
  	$title = $request->get("title");
  	$type = $request->get("type");
  	$content = $request->get("content");
  	$staffobj = $request->get("staffobj");
  	$zdtype = $request->get("zdtype");
  	$currUser = $this->get('security.context')->getToken();
		$user = $currUser->getUser()->getUserName();
		$sql = "";$para = array();
		$result = array();
		$rowrecord = null;
		try
		{
			if($publishid==null || empty($publishid)){
				$publishid = SysSeq::GetSeqNextValue($da,"mb_content_publish","id");
				$sql = "insert into mb_content_publish(id,title,content,zdtype,type,date,publish_staffid)values(?,?,?,?,?,now(),?)";
				$para = array((string)$publishid,(string)$title,(string)$content,(string)$zdtype,(string)$type,(string)$user);
				$da->ExecSQL($sql,$para);
				$this->editPublishSub(false,$publishid,$staffobj);
			}
			else{ //修改
				$sql = "update mb_content_publish set title=?,content=?,zdtype=? where id=?";
				$para = array((string)$title,(string)$content,(string)$zdtype,(string)$publishid);
				$da->ExecSQL($sql,$para);
				$this->editPublishSub(true,$publishid,$staffobj);
			}
			$sql = "select a.id,title,content,date_format(`date`,'%Y-%m-%d %H:%i') 'date' ,nick_name from mb_content_publish a inner join we_staff b on a.publish_staffid=b.login_account where id=?";
			$para = array((string)$publishid);
			$ds = $da->GetData("table",$sql,$para);
			if ($ds && $ds["table"]["recordcount"]>0)
			  $rowrecord = $ds["table"]["rows"][0];
			$result = array("success"=>true,"message"=>"","table"=> $rowrecord);			
	  }
	  catch (Exception $e) {
		  $this->get('logger')->err($e->getMessage());
			$result=array("success"=> false,"message"=>$e->getMessage(),"table"=> null);
	  }
    $response = new Response(json_encode($result));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }
  
  //维护内容发布子表
  public function editPublishSub($isedit,$publishid,$staffobj){
  	$da = $this->get("we_data_access");
  	$sqls=array();
  	$paras=array();
  	$staffids = null;
  	$obj = null;
  	if ($isedit){  //修改数据时先删除原来的数据记录
  		$sql="delete from mb_content_publish_sub where publishid=?";
  		$para=array((string)$publishid);
  		array_push($sqls,$sql);
	  	array_push($paras,$para);
  	}
  	//组织机构id
  	if ( isset($staffobj["zzjg"])){
	  	$staffids = $staffobj["zzjg"];
	  	if ($staffids!=null && count($staffids)>0){
	  		for($i=0;$i< count($staffids);$i++){
	  			$id = SysSeq::GetSeqNextValue($da,"mb_content_publish_sub","id");
	  			$sql="insert into mb_content_publish_sub(id,publishid,level1,leveltype)values(?,?,?,1)";
	  			$para = array((string)$id,(string)$publishid,(string)$staffids[$i]);
	  			array_push($sqls,$sql);
	  			array_push($paras,$para);
	  		}
	  	}
    }
  	//职级维度
  	if ( isset($staffobj["zjwd"])){
	  	$staffids = $staffobj["zjwd"];	  	
	  	if ($staffids!=null && count($staffids)>0){
	  		for($i=0;$i< count($staffids);$i++){
	  			$zjlb=null;
	  	    $glzj=null;
	  	    $ywzj=null;
	  			$id = SysSeq::GetSeqNextValue($da,"mb_content_publish_sub","id");
	  			$sql="insert into mb_content_publish_sub(id,publishid,level1,level2,level3,leveltype)values(?,?,?,?,?,2)";
	  			$obj = $staffids[$i];
	  			$zjlb = $obj["zjlb"];
	  			if ( isset($obj["glzj"]))
	  			  $glzj = $obj["glzj"];
	  			if (isset($obj["ywzj"]))
	  			  $ywzj = $obj["ywzj"];	
	  			$para = array((string)$id,(string)$publishid,$zjlb,$glzj,$ywzj);
	  			array_push($sqls,$sql);
	  			array_push($paras,$para);
	  		}
	  	}
    }    
  	//人员分类
  	if ( isset($staffobj["ryfl"])){
	  	 $staffids = $staffobj["ryfl"];
	  	 if ($staffids!=null && count($staffids)>0){
	  	 	$level_array = array();
	  		for($i=0;$i< count($staffids);$i++){
	  			$level1 = null;$level2 = null;$level3 = null;$level4 = null;
	  			$id = SysSeq::GetSeqNextValue($da,"mb_content_publish_sub","id");
	  			$sql="insert into mb_content_publish_sub(id,publishid,level1,level2,level3,level4,leveltype)values(?,?,?,?,?,?,3)";
	  			$obj = $staffids[$i];
	  			if (isset($obj["level1"]))
	  			  $level1=$obj["level1"];
	  			if (isset($obj["level2"])){
	  			  $level2=$obj["level2"];
		  			$level_array=explode("-",$level2);
		  			if ($level_array!=null && count($level_array)>0)
		  			  $level2 = end($level_array);	  			  
	  			}
	  			if (isset($obj["level3"])){
	  			  $level3=$obj["level3"];
		  			$level_array=explode("-",$level3);
		  			if ($level_array!=null && count($level_array)>0)
		  			  $level3 = end($level_array);	 
		  		}	  			  
	  			if (isset($obj["level4"])){
	  			  $level4=$obj["level4"];
		  			$level_array=explode("-",$level4);
		  			if ($level_array!=null && count($level_array)>0)
		  			  $level4 = end($level_array);	 	  			  
          }
	  			$para = array((string)$id,(string)$publishid,$level1,$level2,$level3,$level4);
	  			array_push($sqls,$sql);
	  			array_push($paras,$para);
	  		}
	  	}
	  }
  	//员工号
  	if ( isset($staffobj["ygh"])){
	  	$staffids = $staffobj["ygh"];
	  	if ($staffids!=null && count($staffids)>0){
	  		for($i=0;$i< count($staffids);$i++){
	  			$id = SysSeq::GetSeqNextValue($da,"mb_content_publish_sub","id");
	  			$sql="insert into mb_content_publish_sub(id,publishid,level1,leveltype)values(?,?,?,4)";
	  			$para = array((string)$id,(string)$publishid,(string)$staffids[$i]);
	  			array_push($sqls,$sql);
	  			array_push($paras,$para);
	  		}
	  	}
	  }
  	//排除员工号
  	if ( isset($staffobj["noygh"])){
	  	$staffids = $staffobj["noygh"];
	  	if ($staffids!=null && count($staffids)>0){
	  		for($i=0;$i< count($staffids);$i++){
	  			$id = SysSeq::GetSeqNextValue($da,"mb_content_publish_sub","id");
	  			$sql="insert into mb_content_publish_sub(id,publishid,level1,leveltype)values(?,?,?,5)";
	  			$para = array((string)$id,(string)$publishid,(string)$staffids[$i]);
	  			array_push($sqls,$sql);
	  			array_push($paras,$para);
	  		}
	  	}
	  }
  	$result = true;
  	try{
  		if (count($sqls)>0 && count($paras)>0)
  	    $da->ExecSQLS($sqls,$paras);
  	}
  	catch (Exception $e) {
  		$result = false;
  	}
  	return $result;
  }
  
  //接口查询内容发布信息
	public function detailContentPublishAction()
	{
		 $da = $this->get('we_data_access');
		 $request = $this->getRequest();
		 $id = $request->get("id");		 
		 $code=ReturnCode::$SUCCESS;
		 $data = array();
		 $msg = "";
		 $result = null;		 
		 if ($id!=null && !empty($id)){
		 	 $sql="select title as news_title,content news_content,date_format(date,'%Y-%m-%d %H:%i') news_date,nick_name as news_author,'' news_subtitle,type 
             from mb_content_publish a inner join we_staff b on a.publish_staffid=b.login_account where id=?";
       try
       {
       	  $ds = $da->GetData("detail",$sql,array((string)$id));
          if ($ds && $ds["detail"]["recordcount"]>0){
            $data = $ds["detail"]["rows"][0];
            //写入日志信息
            $syslog = new \Justsy\AdminAppBundle\Controller\SysLogController();
    	      $syslog->setContainer($this->container);
    	      $user = $this->get('security.context')->getToken()->getUser();
    	      $type = "";
    	      if ($ds["detail"]["rows"][0]["type"]=="1")
    	        $type = "制度";
    	      else
    	        $type = "福利";
    	      $desc =  $user->nick_name."查看了标题为【".$ds["detail"]["rows"][0]["news_title"]."】的".$type."。";
    	      $type = "查看".$type;
    	      $syslog->AddSysLog($desc,$type);
          }
       }
   	   catch(\Exception $e) {
			   $this->get('logger')->err($e);
		   }	   
	   }
     $response = new Response(json_encode($data));
     $response->headers->set('Content-Type', 'text/json');
     return $response;
	}
	
	//删除发布内容(接口)
	public function deletePublishAction()
	{
		 $request = $this->getRequest();
		 $publishid = $request->get("publishid");
		 $returncode = ReturnCode::$SUCCESS;$msg="";
		 if (empty($publishid)){
		 	 $returncode = ReturnCode::$SYSERROR;
		 	 $msg = "请传入publishid参数！";
		 }
		 else
		 {
			 $da = $this->get('we_data_access');
			 $sqls = array();
			 $paras = array();
			 $sql = "delete from mb_content_publish_sub where publishid=?;";
			 $para = array((string)$publishid);
			 array_push($sqls,$sql);
			 array_push($paras,$para);
			 $sql = "delete from mb_content_publish where id=?;";
			 $para = array((string)$publishid);
			 array_push($sqls,$sql);
			 array_push($paras,$para);			 
			 try
			 {
			 	 $da->ExecSQLS($sqls,$paras);
			 	 $msg = "删除数据记录成功！";
			 }
			 catch(\Exception $e) {
			 	 $returncode = ReturnCode::$SYSERROR;
			 	 $msg = "删除内容发布出错！";
			 }
		 }
		 $result = array("returncode"=>$returncode,"msg"=>$msg);
		 $response = new Response(json_encode($result));
     $response->headers->set('Content-Type', 'text/json');
     return $response;
	}
	
	//获得内容发布列表接口
	public function getApi_contentlistAction(){
		$request = $this->getRequest();
		$type = $request->get("type");
		$msg="操作成功";
		$result = null;
		$code=ReturnCode::$SUCCESS;
		if ($type!=null && !empty($type)){
			$da = $this->get('we_data_access');
			$title = $request->get("title");
			$zdtype = $request->get("small_type");			
			$top = $request->get("top");
			$user = $this->get('security.context')->getToken()->getUser();
			$staffid = $user->getUsername();
			$data = array();
			$total = 0;
			try
			{			
				$staff = explode("@",$staffid);
		    $staffid = $staff[0];
				$getsql = $this->getSql($staffid);
				$exists = $getsql["sql"];
				$parameter = $getsql["para"];
				$condition = "";
				$sql = "select id,concat(nick_name,'　',date_format(`date`,'%Y-%m-%d %H:%i')) as summary,title,'' icon,'' subtitle,0 comment_num,0 copy_num,date_format(`date`,'%Y-%m-%d %H:%i') post_date
		            from mb_content_publish a inner join we_staff b on a.publish_staffid=b.login_account  
				        inner join (".$exists.") publish_role on a.id= publish_role.publishid ";
				//拼接条件
				if (!empty($title)){
					$condition = " and `title` like concat(?,'%')";
					array_push($parameter,(string)$title);
				}
				if (!empty($zdtype)){
					$condition .= " and zdtype=? ";
					array_push($parameter,(string)$zdtype);
				}
				if (!empty($type)){
					$condition .= " and `type`=? ";
					array_push($parameter,(string)$type);
				}
				//排除人员
				$condition .= " and not exists (select 1 from mb_content_publish_sub where publishid=a.id and level1=? and leveltype=5) ";
				array_push($parameter,(string)$staffid);
				$condition .=" order by id desc ";
				$sql .= $condition;								
				$ds = $da->GetData("table",$sql,$parameter);
				$total = $ds["table"]["recordcount"];
				if ($ds && $total >0)					
				  $data = $ds["table"]["rows"];
			}
			catch(\Exception $e) {
			  $this->get('logger')->err($e);
			  $code=ReturnCode::$SYSERROR;
			  $msg="系统错误";
		  }
		  $result = json_encode(array('total'=>$total,"number"=>$top,'listitems'=> $data,"msg"=> $msg));
	  }
	  else{
	  	 
	  	 $code= ReturnCode::$OTHERERROR;
	  	 $msg = "参数type必须传入且不允许为空！";
	  	 $result = json_encode(array('total'=>0,"number"=>0,'listitems'=> array(),"msg"=> $msg));
	  }
	  $response = new Response($result);
    $response->headers->set('Content-Type','text/json');
    return $response;		 
	}
	
	//获得人员范围sql
	private function getSql($staffid){
		$sql = "";
		$sqls = array();
		$paras = array();
		//选择全部部门时所有人员可见
		$eno = $this->get('security.context')->getToken()->getUser()->eno;
	  $eno = "v".$eno;		
		$sql = "select publishid from mb_content_publish_sub where level1=? and leveltype=1 ";
		array_push($sqls,$sql);
		array_push($paras,(string)$eno);
		//组织机构
		$sql ="union select publishid from mb_content_publish_sub a inner join mb_hr_7 b on level1=b.orgeh where zhr_pa903112=? and leveltype=1 ";
		array_push($sqls ,$sql);
		array_push($paras,(string)$staffid);	
		//职级维度
		$sql = " union select publishid from mb_hr_7 a inner join mb_content_publish_sub b on zhr_pa903101=level1 
		         where case when level2 is null then 1=1 else zhr_pa903102=level2 end and 
		               case when level3 is null then 1=1 else zhr_pa903113=level3 end and 
		               a.zhr_pa903112=? and leveltype=2 ";
		array_push($sqls ,$sql);
		array_push($paras,(string)$staffid);		
		//人员类别
		$sql = " union select publishid from mb_hr_7 a inner join mb_content_publish_sub b on zhr_pa903116=level1 
		         where case when level2 is null then 1=1 else zhr_pa903117=level2 end and case when level3 is null then 1=1 else zhr_pa903118=level3 end 
		           and case when level4 is null then 1=1 else zhr_pa903119=level4 end and a.zhr_pa903112=? and leveltype=3 ";
		array_push($sqls ,$sql);
		array_push($paras,(string)$staffid);
		//员工号
		$sql = " union select publishid from mb_content_publish_sub where level1=? and leveltype=4 ";
		array_push($sqls ,$sql);
		array_push($paras,(string)$staffid);
		$sql = null;
		if ( count($sqls)>0)
		  $sql = implode(" ",$sqls);		
		$result = array("sql"=> $sql,"para"=> $paras);
		return $result;
	}
}