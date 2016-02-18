<?php

namespace Justsy\BaseBundle\Management;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Common\Cache_Enterprise;
use PHPExcel;
use PHPExcel_IOFactory;

class Dept
{
	  private $conn=null;
	  private $conn_im=null;
	  private $container = null;
	  private $logger = null;

	  private $deptInfo=null; //用户对象
	  
	  public function __construct($_db,$_db_im,$container=null)
	  {
	    $this->conn = $_db;
	    $this->conn_im = $_db_im;
	    $this->container = $container;
	    if ( !empty($container))
	      $this->logger = $container->get("logger");
	  }
	  ///在指定的企业创建虚拟部门：公众号
	  public function createMicromessageDept($eno)
	  {
	  	    $pub = "v".$eno."999";
	      	//判断是否存在
	      	$deptid = $pub."888";
	      	$sql = "select dept_id,fafa_deptid from we_department where eno=? and fafa_deptid=?";
          $ds = $this->conn->GetData("d",$sql,array((string)$eno,(string)$deptid));
          
	  	    if(count($ds["d"]["rows"])>0) return $ds["d"]["rows"][0];
	  	    //判断是否存在公共帐号虚拟部门	  	    
          $ds = $this->conn->GetData("d",$sql,array((string)$eno,(string)$pub));
          if(count($ds["d"]["rows"])==0)
          {
              //创建一个
              $a_deptid = SysSeq::GetSeqNextValue($this->conn,"we_department","dept_id");
              $sql = "insert into we_department (eno,dept_id,dept_name,parent_dept_id,fafa_deptid,create_staff)values(?,?,?,?,?,null)";
              $this->conn->ExecSQL($sql,array(
              	(string)$eno,
              	(string)$a_deptid ,
              	"公共帐号",
              	"v".$eno,
              	(string)$pub,
              ));
          }
          
	  	    $sql1 = "insert into im_base_dept (deptid, deptname, pid, path, noorder) select ? deptid,? deptname,deptid pid,concat(path,?,'/') path,1 noorder from im_base_dept where deptid=?";
	  	    $sql1_paras=array(
	  	       (string)$deptid,
	  	       "公众号",
	  	       (string)$deptid,
	  	       (string)$pub
	  	    );
	  	    $a_deptid = SysSeq::GetSeqNextValue($this->conn,"we_department","dept_id");
	  	    $sql2 = "insert into we_department (eno,dept_id,dept_name,parent_dept_id,fafa_deptid,create_staff) select ? eno,? dept_id,? dept_name,dept_id parent_dept_id,? fafa_deptid,create_staff from we_department where fafa_deptid=?";
	  	    $sql2_paras=array(
	  	       (string)$eno,
	  	       (string)$a_deptid,
	  	       "公众号",
	  	       (string)$deptid,
	  	       (string)$pub
	  	    );
	  	    $this->conn->ExecSQL($sql2,$sql2_paras);
	  	    $this->conn_im->ExecSQL($sql1,$sql1_paras);
	  	    return array("dept_id"=> $a_deptid,"fafa_deptid"=> $deptid);
	  }
	  
	public function getinfo($id,$isfresh=false)
	{
		try
		{
			$data=Cache_Enterprise::get(Cache_Enterprise::$EN_DEPT,$id,$this->container);
		}
		catch(\Exception $e)
		{
			$this->logger->err($e);
			$data=null;
		}
		if(empty($data) || $isfresh)
		{
	      	$sql = "select  *,deptid fafa_deptid from im_base_dept where deptid=?";
		  	$ds = $this->conn_im->GetData("d",$sql,array(
		  	    (string)$id
		  	));
		  	if(count($ds['d']['rows'])>0)
		  	{
		  		$deptinfo = $ds['d']['rows'][0];
		  		Cache_Enterprise::set(Cache_Enterprise::$EN_DEPT,$id,json_encode($deptinfo),0,$this->container);
		  		return $deptinfo;
		  	}
		  	else
		  	{
		  		Cache_Enterprise::delete(Cache_Enterprise::$EN_DEPT,$id,$this->container);
		  		return null;
		  	}
	  	}
	  	return json_decode($data,true);
	}

	  //返回所属部门允许加入的默认群组
  	public function getDeptDefaultGroup($deptid,$isfresh=false)
  	{
	  	$result = array();
	  	$da_im = $this->conn_im;
		try
		{
			$result=Cache_Enterprise::get(Cache_Enterprise::$EN_DEPT."_defaultgroup",$deptid,$this->container);
		}
		catch(\Exception $e)
		{
			$this->logger->err($e);
			$result=null;
		}
		if(empty($result) || $isfresh)
		{
			$sql = 'select groupid from im_group_memberarea where objid=? and status=1';
		  	$g_ds = $this->conn_im->GetData("g",$sql,array((string)$id));
		  	$result = array();
		  	foreach ($g_ds['g']['rows'] as $key => $value) {
		  		$result[] = $value['groupid'];
		  	}
		  	Cache_Enterprise::set(Cache_Enterprise::$EN_DEPT."_defaultgroup",$deptid,json_encode($result),0,$this->container);
	  	}
	  	else
	  	{
	  		$result = json_decode($result,true);
	  	}
	  	return $result;
  	}
	  
	  public function getIdByName($eno,$deptname)
	  {
	  		$this->conn_im->ExecSQL("SET NAMES 'utf8'");//加这句，以便在多线程中访问这个方法时，支持中文参数
	  	  	$sql = "select deptid,path from im_base_dept where deptname=? and path like concat('/-10000/v',?,'%')";
	  	  	$ds = $this->conn_im->GetData("d",$sql,array(
	  	  		(string)$deptname,
	  	        (string)$eno
	  	  	));
	  	  	if(count($ds["d"]["rows"])>0)
	  	  	{
	  	      	$fafa_deptid= 	$ds["d"]["rows"][0]["deptid"];
	  	      	$path= 	$ds["d"]["rows"][0]["path"];
	  	      	$sql = "select dept_id from we_department where fafa_deptid=?";
			  	$ds = $this->conn->GetData("d",$sql,array(
			  	         (string)$fafa_deptid
			  	));
			  	if(count($ds["d"]["rows"])>0) return array("fafa_deptid"=>$fafa_deptid,"deptid"=>$ds["d"]["rows"][0]["dept_id"],'path'=>$path);
	  	  	}
	  	  	return null;
	  }

	  public function getDefaultDept($eno)
	  {
		try
		{
			$result=Cache_Enterprise::get(Cache_Enterprise::$EN_DEPT."_defaultdept",$eno,$this->container);
		}
		catch(\Exception $e)
		{
			$this->logger->err($e);
			$result=null;
		}
		if(!empty($result)) 
			return json_decode($result,true);
		$result = $this->getIdByName($eno,"体验部门");
		if(!empty($result))
			$result["dept_id"] =$result["deptid"];
		else
		{
			//没有体验部门时获取根部门
			$rootdeptid = 'v'.$eno;
			$result = $this->getinfo($rootdeptid);
			$result["fafa_deptid"] = $rootdeptid;
		}
		Cache_Enterprise::set(Cache_Enterprise::$EN_DEPT."_defaultdept",$eno,json_encode($result),0,$this->container);
		return $result;
	  }

	  //获取指定部门的所属企业号
	  public function getEno($deptid)
	  {
	  	  $eno="";
	  	  $deptdata = $this->getinfo($deptid);
	  	  if(!empty($deptdata))
	  	  {
	  	  	return $deptdata["eno"];
	  	  }
	  	  return null;
	  }

	public function getChild($eno,$deptid,$search)
	{
		$sql = 'select id,parent,parentname,text,friend,a.noorder,a.count children,ifnull(a.empcount,0) empcount from (';
		$para=array();
	  	if(empty($deptid))
	  	{
	  		$deptid = "v".$eno;
	  		$sql .= "select a.deptid id,a.pid parent,'' parentname,a.deptname text,friend,a.noorder,(select count(1) from im_base_dept b where a.deptid=b.pid) count,b.empcount from im_base_dept a left join im_dept_stat b on a.deptid=b.deptid where a.deptid='{$deptid}' union";
	  		$para[]=(string)$deptid;
	  	}
	  	$pinfo = $this->getInfo($deptid);

	  	$sql .= " select a.deptid id,a.pid parent,'".$pinfo['deptname']."' parentname,a.deptname text,friend,a.noorder,(select count(1) from im_base_dept b where a.deptid=b.pid) count,b.empcount from im_base_dept a left join im_dept_stat b on a.deptid=b.deptid  where a.pid='{$deptid}' ";
	  	$sql .= ') a where 1=1 ';
		$sql .= " order by a.parent,a.noorder";
	  	if(!empty($search))
	  	{
	  		$sql = 'select a.deptid id,a.pid parent,\'\' parentname,a.deptname text,friend,a.noorder,(select count(1) from im_base_dept c where a.deptid=c.pid) children,ifnull(b.empcount,0) empcount from im_base_dept a left join im_dept_stat b on a.deptid=b.deptid where a.path like \'/-10000/v'.$eno.'/%\'';
	  		$sql .= " and a.deptname like '%".$search."%' order by a.pid,a.noorder";
	  	}	  	
	  	$ds= $this->conn_im->GetData("h",$sql,array());
	  	if($ds && count($ds["h"]["rows"])>0) return $ds["h"]["rows"];
      	else return array();  
	}
	  
	  public function getAllChild($deptid)
	  {
	  	  
	  	  
  	  	$sql = 'select p.* 
				from im_base_dept p
				where path like concat(
						(select d.path
					from im_base_dept d
					where d.deptid=?),"%"
				)';
		$ds = $this->conn_im->GetData('t',$sql,array((string)$deptid));
	  	  
  	  return $ds['t']['rows'];
	  }
	  
	public function getStaffJid($deptid)
	{
	  	$sql ="select loginname jid from im_employee where deptid =?";
	    $ds= $this->conn_im->GetData("h",$sql,array((string)$deptid));
	    if($ds && count($ds["h"]["rows"])>0)
	    {
	    	$r = array();
	    	foreach ($ds["h"]["rows"] as $key => $value) {
	    		$r[] = $value['jid'];
	    	}
	       return $r;
	   	}
	    else return array();       	 
	}
	  
	  //根据企业号获得企业根部门id
	  public function getRootDeptId($eno){
	  	if (empty($eno)) return null;
	  	$sql = "select dept_id from im_base_dept where deptid=?";
	  	$para = array((string)"v".$eno);
	  	$deptid = null;
	  	try
	  	{
	  		 $ds = $this->conn->GetData("dept",$sql,$para);
	  		 if ($ds && $ds["dept"]["recordcount"]>0)
	  		   $deptid = $ds["dept"]["rows"][0]["dept_id"];
	  		 else
	  		   $deptid = $eno;
	  	}
	  	catch (\Exception $e){
	  	}
	  	return $deptid;
	  }

	  //导入部门数据（根据部门名称和上级部门名称)
  	public function import($eno,$filename)
  	{
	    $fixedType = explode(".",basename($filename));
	  	$fixedType = $fixedType[count($fixedType)-1];
	  	$objReader = \PHPExcel_IOFactory::createReader( $fixedType=="xlsx"? 'Excel2007':"Excel5"); 
	    $objPHPExcel = $objReader->load($filename);
		$objWorksheet = $objPHPExcel->getActiveSheet();
		$keys = array("deptid","deptname","pid","pname","orderno");
	    $excelDataObj = array();
	    $i=false;
	 	foreach ($objWorksheet->getRowIterator() as $row) 
	 	{
	 		if(!$i)
	 		{
	 			$i=true;
	 			continue;
	 		}
	        $cellIterator = $row->getCellIterator();
	        $cellIterator->setIterateOnlyExistingCells(false);
	        $item=array();
	        $cell_pos=0;
	        foreach ($cellIterator as $cell) {
	        	if(!$keys[$cell_pos]) break;
	        	$item[$keys[$cell_pos]]=$cell->getCalculatedValue();
	        	$cell_pos++;
	        }
	        if(empty($item['deptname']))
		    {
		       	continue;
		    }
	        $excelDataObj[] = $item;
	    }
	    if(empty($excelDataObj)) return Utils::WrapResultError("文件内容为空");
		if(count($excelDataObj)>600)
		{
			$weburl = $this->container->getParameter('open_api_url');
			$admin_openid = 'admin@'.$this->container->getParameter('edomain');
			//导入部门太多（>600）时，采用线程
			$dir =explode('src',  __DIR__);
			$command = $dir[0].'app/threads.sh "dept" "'.(str_replace('"', '\"', json_encode($excelDataObj)).'" "'.$weburl.'" "'.$admin_openid.'"');
			$data = shell_exec($command);
			return Utils::WrapResultOK($data);
		}
		else
		{	    
	    	return $this->createBatchDepartment($eno,$excelDataObj);
	    }
  	}

	public function createBatchDepartment($eno,$deptinfo)
	{
	  	$deptSql="insert into we_department(eno,dept_id,dept_name,parent_dept_id,fafa_deptid)values";
	  	$deptSqlValues=array();
	  	$imDeptSql  = "insert into im_base_dept(deptid,deptname,pid,noorder,path)values";
		$imDeptSqlValues=array();
		$c_count = count($deptinfo);
		$deptMap = array();
	    $dept_id = SysSeq::GetSeqNextValue($this->conn,"we_department","dept_id",$c_count);	
	    $dept2 = array();
	    $keyType = !empty($deptinfo[0]['deptid'])?'deptid':'deptname';
	    foreach ($deptinfo as $key => $value) {
	    	$deptid   = !empty($value["deptid"])?$value["deptid"]: $dept_id++;
	    	$mapkey = empty($value["deptid"]) ? $value['deptname'] : $deptid;
	    	if(empty($value['pname']) && empty($value['pid']))
	    	{
	    		$dept2[$mapkey] = array('deptid'=>$deptid,'deptname'=>$value['deptname'],'pname'=>'','path'=>'/-10000/v'.$eno.'/'.$deptid.'/','pid'=> 'v'.$eno);
	    	}
	    	else
	    		$dept2[$mapkey] = array('deptid'=>$deptid,'deptname'=>$value['deptname'],'pname'=>$value['pname'],'path'=>'','pid'=> $value['pid']);
	    	if(empty($value["deptid"]))
	    		$deptinfo[$key]['deptid']=$deptid;
	    }	    
		if($keyType=='deptid')
		{
			$this->settempathBypid($eno,$dept2);
		}
		else
		{
			$this->settempathBypname($eno,$dept2);
		}
		$cleansql = 'delete from we_department where eno=? and fafa_deptid!=?';
		$im_cleansql = 'delete from im_base_dept where deptid!=? and path like concat(\'/-10000/\',?,\'/%\')';
		//$this->conn->ExecSQL($cleansql,array((string)$eno,'v'.$eno));
		//$this->conn_im->ExecSQL($im_cleansql,array('v'.$eno,'v'.$eno));
		
		for ($i=0; $i < $c_count; $i++) 
		{
					$obj = $deptinfo[$i];
				    $deptname =str_replace('\'', '\'\'', $obj["deptname"]) ;
				    $deptid   = $obj["deptid"];
				    $pid      = $keyType=='deptid' ? $dept2[$deptid]["pid"] : $dept2[$deptname]["pid"];
				    $path     = $keyType=='deptid' ? $dept2[$deptid]["path"] : $dept2[$deptname]["path"];
				    $orderno  = isset($obj["orderno"])? $obj["orderno"] : '0';
				    //业务处理
					$deptSqlValues[]   ='(\''.$eno.'\',\''.$deptid.'\',\''.$deptname.'\',\''.$pid.'\',\''.$deptid.'\')';
					$imDeptSqlValues[]  ='(\''.$deptid.'\',\''.$deptname.'\',\''.$pid.'\','.(empty($orderno)?0:$orderno).',\''.$path.'\')';					
		}
		try
		{
			if(count($deptSqlValues)>0)
			{
				$this->conn->ExecSQL($deptSql.implode(",", $deptSqlValues),array());
				//$logger->err("sql3:".microtime());
				$this->conn_im->ExecSQL($imDeptSql.implode(",", $imDeptSqlValues),array());
				//$logger->err("sql4:".microtime());
				//$logger->err("sql5:".microtime());
			}
		}
		catch(\Exception $e)
		{
			return Utils::WrapResultError($e->getMessage());
		}
		unset($deptSqlValues);
		unset($imDeptSqlValues);				
	    return Utils::WrapResultOK("");		
	}

	public function settempathBypid($eno,&$deptlst)
	{
		foreach ($deptlst as $key => $value) {
			//获取当前部门的pid，并判断path
			if(!empty($value['path'])) continue;
			$pid = $value['pid'];
			if(!isset($deptlst[$pid]))
			{
				$tmp=$this->getinfo($pid);
				if(empty($tmp))
				{
					$deptlst[$key]['pid'] = 'v'.$eno;
					$deptlst[$key]['path']='/-10000/v'.$eno.'/'.$value['deptid'].'/';
				}
				else $deptlst[$key]['path'] = $tmp['path'].$value['deptid'].'/';
				continue;
			}
			$path = '';
			while (1) {
				$p = $deptlst[$pid]['path'];
				if(empty($p)) $path .= $deptlst[$pid]['deptid'].'/';
				else
				{
					$path = $p.$path.$value['deptid'].'/';
					$deptlst[$key]['path'] = $path;
					break;
				}
				$pid = $deptlst[$pid]['pid'];
				if(!isset($deptlst[$pid]))
				{
					$tmp=$this->getinfo($pid);
					if(empty($tmp))
					{
						$deptlst[$pid]['pid'] = 'v'.$eno;
						$deptlst[$pid]['path']='/-10000/v'.$eno.'/'.$pid.'/';
					}
					else $deptlst[$pid]['path'] = $tmp['path'].$deptlst[$pid]['deptid'].'/';					 
				}
			}
		}
	}
	public function settempathBypname($eno,&$deptlst)
	{
		foreach ($deptlst as $key => $value) {
			if(!empty($value['path'])) continue;
			$name = $value['pname'];
			if(empty($name))
			{
				$deptlst[$key]['pid'] = 'v'.$eno;
				$deptlst[$key]['path']='/-10000/v'.$eno.'/'.$value['deptid'].'/';
				continue;				
			}
			if(!isset($deptlst[$name]))
			{
				$tmp=$this->getIdByName($eno,$name);
				if(empty($tmp))
				{
					$deptlst[$key]['pid'] = 'v'.$eno;
					$deptlst[$key]['path']='/-10000/v'.$eno.'/'.$value['deptid'].'/';
				}
				else 
				{
					$deptlst[$key]['pid'] = $tmp['fafa_deptid'];
					$deptlst[$key]['path'] = $tmp['path'].$value['deptid'].'/';
				}
				continue;					 
			}
			$deptlst[$key]['pid'] = $deptlst[$name]['deptid'];
			$path = '';
			while (1) {
				$p = $deptlst[$name]['path'];
				if(empty($p)) $path .= $deptlst[$name]['deptid'].'/';
				else
				{
					$path = $p.$path.$value['deptid'].'/';
					$deptlst[$key]['path'] = $path;
					break;
				}
				$name = $deptlst[$name]['deptname'];
				if(!isset($deptlst[$name]))
				{
					$tmp=$this->getIdByName($eno,$name);
					if(empty($tmp))
					{
						$deptlst[$name]['pid'] = 'v'.$eno;
						$deptlst[$name]['path']='/-10000/v'.$eno.'/'.$deptlst[$name]['deptid'].'/';
					}
					else $deptlst[$name]['path'] = $tmp['path'].$deptlst[$name]['deptid'].'/';					 
				}
			}
		}		
	}	

	  //创建企业部门
	  public function createDepartment($deptinfo)
	  {
	  	 $result = array("success"=>true,"msg"=>"添加部门成功");
	  	 $eno = isset($deptinfo["eno"]) ? $deptinfo["eno"]:""; //企业号
	  	 $dept_id = isset($deptinfo["deptid"]) ? $deptinfo["deptid"]:null;
	  	 $deptname = isset($deptinfo["deptname"]) ? $deptinfo["deptname"]:null;
	  	 $p_deptid = isset($deptinfo["p_deptid"]) ? $deptinfo["p_deptid"]:null;
	  	 $manager = isset($deptinfo["manager"]) ? $deptinfo["manager"]:null;
	  	 $friend  = isset($deptinfo["friend"]) ? $deptinfo["friend"] : null;
	  	 $show    = isset($deptinfo["show"]) ? $deptinfo["show"] : null;
	  	 if ( empty($eno) || empty($deptname))
	  	 {
	  	 	 $result = array("success"=>false,"msg"=>"企业号、部门名称不能为空");
	  	 }
	  	 else
	  	 {
	  	    $fafa_pid  = "";
		  	 	if(empty($p_deptid))
		  	 	{
		  	 		//设置为一级部门
		  	 		$p_deptid = $eno;
		  	 		$fafa_pid = "v".$eno;
		  	 	}
		  	 	else
		  	 	{
		  	 		//获得pid
		  	 		$fafa_pid = $this->getImDeptid($p_deptid);
		  	 	}
		  	 	if ( empty($dept_id))
	          		$dept_id = SysSeq::GetSeqNextValue($this->conn,"we_department","dept_id");
		  	 	$im_deptid = SysSeq::GetSeqNextValue($this->conn_im,"im_base_dept","deptid");
		  	 	
		  	 	$sql_sns  = "insert into we_department(eno,dept_id,dept_name,parent_dept_id,fafa_deptid)values(?,?,?,?,?)";
		  	 	$para_sns = array((string)$eno,(string)$dept_id,(string)$deptname,(string)$p_deptid,(string)$im_deptid);
		  	 	
		  	 	$number = $this->getChildrenLength($fafa_pid);
		  	 	$sql_im  = "insert into im_base_dept(deptid,deptname,pid,manager,friend,`show`,noorder)values(?,?,?,?,?,?,?);";
		  	 	$para_im = array((string)$im_deptid,(string)$deptname,(string)$fafa_pid,(string)$manager,(string)$friend,(string)$show,(string)$number);
		  	 	try
		  	 	{
			  	 	  $this->conn->ExecSQL($sql_sns,$para_sns);
			      	$this->conn_im->ExecSQL($sql_im,$para_im);
			      	//计算path路
		  	    	$this->reset_deptpath($eno,$im_deptid);
		  	    	$result["data"] = $this->getinfo($dept_id);
		  	    	$result["data"]["pid"] = $fafa_pid;

		  	    	$VersionChange = new VersionChange($this->conn,$this->logger,$this->container);
		  	    	$VersionChange->deptchange($dept_id);
		      }
		      catch(\Exception $e){
		       	$result = array("success"=>false,"msg"=>$e->getMessage());
		      }
	  	 }
	  	 return $result;
	  }
	  
	  //获得部门子部门个数
	  private function getChildrenLength($pid)
	  {
	     $number = 0;
	     $sql = "select count(*)+1 number from im_base_dept where pid=?;";
	     try
	     {
	        $ds = $this->conn_im->getData("table",$sql,array((string)$pid));
	        if ( $ds && $ds["table"]["recordcount"]>0)
	          $number= $ds["table"]["rows"][0]["number"];
	     }
	     catch(\Exception $e)
	     {
	        $this->logger->err($e->getMessage());
	     }
	     return $number;
	  }
	  
	//删除部门数据
	public function delDepartment($deptid)
	{
	  	 $result = array("success"=>true,"msg"=>"删除部门成功！");
	  	 $im_deptid = $this->getImDeptid($deptid);
	  	 $sql_sns  = array('delete from we_department where dept_id=?');
	  	 //删除关联部门的公众号
	  	 $sql_sns[]= 'delete from we_service where type=1 and objid=?';
	  	 $para_sns = array();
	  	 $para_sns[] = array((string)$deptid);
	  	 $para_sns[] = array((string)$deptid);
	  	 $sql_im   = array('delete from im_base_dept where deptid=?');
	  	 //删除部门统计信息
	  	 $sql_im[] = 'delete from im_dept_stat where deptid=?';
	  	 //删除关联群组的部门
	  	 $sql_im[] = 'delete from im_group_memberarea where objid=? and status=1';
	  	 //删除部门创建的自动好友数据
	  	 $sql_im[] = 'delete from rosterdept where deptid=?';
	  	 $para = array();
	  	 $para[] = array((string)$deptid);
	  	 $para[] = array((string)$deptid);
	  	 $para[] = array((string)$deptid);
	  	 $para[] = array((string)$deptid);
	  	 try
	  	 {
	  	 	$result["data"] = $this->getinfo($deptid);
	  	 	//先处理版本数据的，再物理删除
			$VersionChange = new VersionChange($this->conn,$this->logger,$this->container);
		  	$VersionChange->deptchange($deptid);
		  	$VersionChange->deleteDeptVersion($deptid);

	  	 	$this->conn->ExecSQLs($sql_sns,$para_sns);
	  	 	$this->conn_im->ExecSQLs($sql_im,$para);
	  	 }
	  	 catch(\Exception $e){
	  	 	return Utils::WrapResultError($e->getMessage());
	  	 }
	  	 return Utils::WrapResultOK("");	  	
	}
	  
	//修改部门
	public function updateDepartment($deptinfo)
	{
	  	$result = array();
	  	$deptid =   isset($deptinfo["deptid"]) ? $deptinfo["deptid"] : null;
	  	$p_deptid = isset($deptinfo["p_deptid"]) ? $deptinfo["p_deptid"] : null;
	  	$deptname = isset($deptinfo["deptname"]) ? $deptinfo["deptname"] : null;
	  	$manager  = isset($deptinfo["manager"]) ? $deptinfo["manager"] : null;
	  	$show     = isset($deptinfo["show"]) ? $deptinfo["show"] : null;
	  	$friend   = isset($deptinfi["friend"]) ? $deptinfo["friend"] : null;
	  	if ( empty($deptid)){
	  		return Utils::WrapResultError('部门id不能为空'); 
	  	}
	  	else{
	  		$VersionChange = new VersionChange($this->conn,$this->logger,$this->container);
	  		$sql_sns="";
	  		$sql_im="";
	  		$para_sns = array();
	  		$para_im = array();
	  	 	$deptData = $this->getinfo($deptid);
	  	 	$oldPid = $deptData["pid"]; //原来的上级部门
	  		//获得im中的deptid和pid
	  		$fafa_deptid = $deptid;// $this->getImDeptid($deptid);
	  		$fafa_pid    = $p_deptid;//empty($p_deptid)? "" : $this->getImDeptid($p_deptid);
	  		if (empty($deptname) && !empty($p_deptid)){
	  			$sql_sns="update we_department set parent_dept_id=? where dept_id=?";
	  			$para_sns = array((string)$p_deptid,(string)$deptid);
	  			$sql_im = "update im_base_dept set pid=?,manager=?,friend=?,`show`=? where deptid=?;";
	  			$para_im = array((string)$fafa_pid,(string)$manager,(string)$friend,(string)$show,(string)$fafa_deptid);
	  		}
	  		else if (!empty($deptname) && empty($p_deptid)){
	  			$sql_sns="update we_department set dept_name=? where dept_id=?";
	  			$para_sns = array((string)$deptname,(string)$deptid);
	  			$sql_im = "update im_base_dept set deptname=? where deptid=?";
	  			$para_im = array((string)$deptname,(string)$fafa_deptid);
	  		}
	  		else{
	  			$sql_sns="update we_department set dept_name=?,parent_dept_id=? where dept_id=?";
	  			$para_sns = array((string)$deptname,(string)$p_deptid,(string)$deptid);
	  			$sql_im ="update im_base_dept set deptname=?,pid=?,manager=?,friend=?,`show`=? where deptid=?;";
	  			$para_im = array((string)$deptname,(string)$fafa_pid,(string)$manager,(string)$friend,(string)$show,(string)$fafa_deptid);
	  		}
	  		try
	  		{
	  			//部门变更前变化一下缓存及版本，以便于当更新上级部门时原上级部门的版本更改
	  			$VersionChange->deptchange($deptid);
	  			$this->conn->ExecSQL($sql_sns,$para_sns);
	  			$this->conn_im->ExecSQL($sql_im,$para_im);
	  			if(!empty($p_deptid) && $p_deptid!=$oldPid)
	  			{
	  			    $eno = $deptData["eno"];
	  				$this->reset_deptpath($eno,$fafa_pid);
	  			}
	  			//删除原来的缓存再重新获得
	  			$result = $this->getinfo($deptid,true);
				//部门变更成功后再更新一次缓存
		  		$VersionChange->deptchange($deptid);
	  		}
	  		catch(\Exception $e){
	  			return Utils::WrapResultError($e->getMessage()); 
	  		}
	  		return Utils::WrapResultOK($result);
	  	}
	}
	  
	  //计算im_base_dept中的path字段
	  public function reset_deptpath($eno,$deptid)
	  {
	      $sql_im = "call p_reset_deptpath(?,?);";
	      $para_im = array((string)$eno,(string)$deptid);
	      $this->conn_im->ExecSQL($sql_im,$para_im);
	      $this->getinfo($deptid,true);
	  }
	  
	  //根据sns里的部门id获得im库部门的上级部门pid
	  private function getPid($deptid)
	  {
	  	 $pid = "";
	  	 $sql = "select fafa_deptid from we_department where dept_id=?";
	  	 $ds = $this->conn->GetData("t",$sql,array((string)$deptid));
	  	 if ( $ds && $ds["t"]["recordcount"]>0){
	  	 	 $deptid = $ds["t"]["rows"][0]["fafa_deptid"];
	  	 	 $sql = "select pid from im_base_dept where deptid=?";
	  	 	 $ds = $this->conn_im->GetData("t",$sql,array((string)$deptid));
	  	 	 if ( $ds && $ds["t"]["recordcount"]>0){
	  	 	 	 $pid = $ds["t"]["rows"][0]["pid"];
	  	 	 }
	  	 }
	  	 return $pid;
	  }
	  
	  //根据sns库的部门id获得对应im库的部门id
	  private function getEnoByDeptid($deptid)
	  {
	  	 $eno = "";
	  	 $sql = "select eno from we_department where dept_id=?;";
	  	 $ds = $this->conn->GetData("table",$sql,array((string)$deptid));
	  	 if ($ds && $ds["table"]["recordcount"]>0){
	  	 	 $eno = $ds["table"]["rows"][0]["eno"];
	  	 }
	  	 return $eno;	  	 
	  }
	  
	  //根据sns库的部门id获得对应im库的部门id
	  private function getImDeptid($deptid)
	  {
	  	 $dept_id = "";
	  	 $sql = "select fafa_deptid from we_department where dept_id=?;";
	  	 $ds = $this->conn->GetData("table",$sql,array((string)$deptid));
	  	 if ($ds && $ds["table"]["recordcount"]>0){
	  	 	 $dept_id = $ds["table"]["rows"][0]["fafa_deptid"];
	  	 }
	  	 return $dept_id; 
	  } 
	  
	  //设置部门路径
	  public function setDeptPath($parameter)
	  {
	  	 $userinfo = $parameter["user"];
	     $eno = $userinfo->eno;
	  	 $da = $this->conn_im;
	  	 $sql = "select distinct pid from im_base_dept where remark is null and pid!='-10000' and path is null limit 100;";
	  	 $ds = $da->GetData("table",$sql);
	  	 $success = true;$iscomplete = false;
	  	 if ( $ds && $ds["table"]["recordcount"]>0)
	  	 {
	  	 	  for($i=0;$i< $ds["table"]["recordcount"];$i++)
	  	 	  {
	  	 	     $pid = $ds["table"]["rows"][$i]["pid"];
	  	 	     $sql = "call p_reset_deptpath(?,?);";
			  	   $para = array((string)$eno,(string)$pid);
			  	   try
			  	   {
			  	   	  $da->ExecSQL($sql,$para);
			  	   	  $sql = "update im_base_dept set remark='1' where pid=?";
			  	   	  $da->ExecSQL($sql,array((string)$pid));
			  	   }
			  	   catch(\Exception $e)
			  	   {
			  	   	  $success = false;
			  	   }
	  	 	  }
	  	 }
	  	 else
	  	 {
	  	 	 $iscomplete = true;
	  	 }	  	 
	  	 return array("success"=>$success,"iscomplete"=>$iscomplete);
	  }
	  
	//将部门底下人员设置为互为好友
	  public function setFriend($parameter)
	  {
	  	 $userinfo = $parameter["user"];
	  	 $user = Array();
	     $user["login_account"] = $userinfo->getUserName();
	     $user["fafa_jid"] = $userinfo->fafa_jid;	     
	  	 $deptids = $parameter["deptid"];
	  	 $success = true;
	  	 $message = array();
	  	 for($i=0;$i< count($deptids);$i++)
	  	 {
	  	    $deptid = $deptids[$i];
	  	   	$sql = 'update im_base_dept set friend=1 where deptid=?';
		  	$this->conn_im->ExecSQL($sql,array((string)$deptid));
		  	$this->getinfo($deptid,true);
	  	    $re = $this->setFriendByDept($deptid);
	  	    if ( !$re["success"])
	  	    { 
	  	       return Utils::WrapResultError($re["message"]);
	  	    }
	  	 }
	  	 return Utils::WrapResultOK('');
	  }	
	  //取消部门成员的自动好友关系
	  public function cancelAutoFriend($parameter)
	  {
	  	 $userinfo = $parameter["user"];
	  	 $user = Array();
	     $user["login_account"] = $userinfo->getUserName();
	     $user["fafa_jid"] = $userinfo->fafa_jid;	     
	  	 $deptids = $parameter["deptid"];
	  	 $success = true;
	  	 $message = array();
	  	 for($i=0;$i< count($deptids);$i++)
	  	 {
	  	    $deptid = $deptids[$i];
	  	   	$sql = 'update im_base_dept set friend=0 where deptid=?';
		  	$this->conn_im->ExecSQL($sql,array((string)$deptid));
		  	$this->getinfo($deptid,true);
	  	 }
	  	 return Utils::WrapResultOK('');
	  }
	  
	//设置部门下人员互为好友
	//$user:指定帐号。将指定的帐号与部门下的所以非好友成为好友
	//$user=null:将指定部门下的人员互相加为好友
	public function setFriendByDept($deptid,$user=null)
	{
		//判断部门是否设置了自动好友
		$deptinfo = $this->getInfo($deptid);
		if($deptinfo['friend']!='1') return;
	  	$da = $this->conn_im;
	  	$friendLst = array();
	  	$success = true;
	  	$msg = "";
	  	if(!empty($user))
	  	{
		    $login_account = $user["login_account"];
		    $fafa_jid = array( isset($user["fafa_jid"]) ? $user["fafa_jid"] : null );
		    $staffMgr = new Staff($this->conn,$this->conn_im,$login_account,$this->container->get("logger"),$this->container);
		    if(empty($fafa_jid))
		    {
			    $user = $staffMgr->getInfo();
		 	}
		    $friendLst =array(array("jid"=> $user["fafa_jid"],"nick_name"=>$user["nick_name"]));
		}
	 	else
	 	{
	 		$domain =  $this->container->getParameter('edomain'); 
	 		$staffMgr = new Staff($this->conn,$this->conn_im,"admin@".$domain,$this->container->get("logger"),$this->container);
		     
	 	 	$sql = 'SELECT a.loginname jid,a.employeename nick_name FROM im_employee a where a.deptid=? and not exists(select jid from rosterdept where deptid=? and a.loginname=jid)';
		 	$ds = $da->GetData("t",$sql,array((string)$deptid,(string)$deptid));
	 	 	$friendLst = $ds["t"]["rows"];
	 	}
	    $to_jid = array();
	    $staffcount = count($friendLst);
	    if($staffcount==0)
	    {
	    	return array("success"=>false,"message"=>'该部门下没有人员，请进入子部门进行设置');
	    }
	 	for ($i=0; $i < $staffcount; $i++) 
	 	{	 
	 		$fafa_jid = $friendLst[$i]["jid"];
	 		$nick_name = $friendLst[$i]["nick_name"];
	 		//判断是否已全部成为好友
		  	$sql = "SELECT a.loginname jid,a.employeename nick_name FROM im_employee a where a.deptid=? and not exists (select jid from rosterusers b where a.loginname=b.jid and b.username=? and b.subscription='B')";
		  	 
		  	try
		  	{
		  	 	$ds_member = $da->GetData("member",$sql,array((string)$deptid,(string)$fafa_jid));
			  	if ( $ds_member && count($ds_member["member"]["rows"]>0))
			  	{
			  		if(!empty($user))
			  		{
				  		//如果是指定人员与部门人员成为好友，通知对象为部门中还未不是该人的帐号jid
				  		for ($ic=0; $ic < count($ds_member["member"]["rows"]); $ic++) { 
				  			array_push($to_jid,$ds_member["member"]["rows"][$ic]["jid"]);
				  		}
			  		}
			  		$state = $staffMgr->DeptAddFriend($this->container,$deptid,$fafa_jid,$nick_name,$ds_member["member"]["rows"]);
			  	}			  	
		  	}
		  	catch(\Exception $e)
		  	{
		  	 	$success = false;
		  	 	$msg = "设置部门人员互为好友出错！";
		  	 	$this->container->get("logger")->err($e->getMessage());
		  	 	return array("success"=>$success,"message"=>$msg);
		  	}
	  	}
	  	$to_jid = $this->getStaffJid($deptid);
	  	if(count($to_jid)>0)
	  	{
	  		//获取在线帐号
	  		Utils::findonlinejid($da,$to_jid);
	  		//向成员发送出席
	    	$message = array('deptid'=>$deptid);
	    	$msg = json_encode(Utils::WrapMessage('dept_friend',$message,array()));
	 		Utils::sendImPresence($fafa_jid,$to_jid,"dept_friend",$msg,$this->container,"","",false,'','0');
		}
	  	return array("success"=>$success,"message"=>$msg);
	}

	///设置部门成员为其默认群组成员
	public function setGroupMemberByDept($deptid)
	{
		try
		{
			//获取部门关联的群组
			$sql = 'select groupid from im_group_memberarea where status=1 and objid=?';
			$ds = $this->conn_im->GetData("table",$sql,array((string)$deptid));
			if ( $ds && $ds["table"]["recordcount"]>0)
		    {
		    	$imGroupJoinSql = 'insert into im_groupemployee(employeeid,groupid,grouprole,employeenick,lastreadid)values';
				$values=array();
				$newMember = array();
		        foreach ($ds["table"]["rows"] as $key => $value) {
		         	$groupid = $value['groupid'];
		         	//判断是否是该群组成员
		         	$sql = 'SELECT a.loginname jid,a.employeename nick_name FROM im_employee a where a.deptid=? and not exists(select 1 from im_groupemployee where groupid=? and a.loginname=employeeid)';
		         	$h1 = $this->conn_im->GetData("table2",$sql,array((string)$deptid,(string)$groupid));
		         	foreach ($h1['table2']['rows'] as $key => $value) {
		         		$values[] = '(\''.$value['jid'].'\',\''.$groupid.'\',\'normal\',\''.$value['nick_name'].'\',0)';
		         		$newMember[] = $value['jid'];
		         	}
		        }
		        if(count($values)>0)
		        {
		        	$this->conn_im->ExecSQL($imGroupJoinSql.implode(',', $values),array());
		        }

		        $groupmgr = new GroupMgr($this->conn,$this->conn_im,$this->container);
		        foreach ($ds["table"]["rows"] as $key => $value)
		        {		        	
		        	$members = $groupmgr->getGroupMembersJidByIM($value['groupid']);
		        	$this->conn_im->ExecSQL('update im_group set max_number=(select count(1) from im_groupemployee where groupid=?) where groupid=?',array((string)$value['groupid'],(string)$value['groupid']));
			        $groupinfo = $groupmgr->GetByIM($value['groupid'],true);
			        if(!empty($members))
	                {
	                    //通知这部分成员需要更新群信息
	                    $noticeinfo = array();
	                    $msg = Utils::WrapMessage("update_group",$groupinfo,$noticeinfo);
	                    Utils::sendImMessage('',implode(',', $members),"update_group",json_encode($msg),$this->container,"","",false,'');
	                }
	               	if(count($newMember))
			        {
				        //向新成员发送入群通知 
				        $iconUrl = $groupinfo['logo'];
		                $noticeinfo = Utils::WrapMessageNoticeinfo('你已自动进入群组 '.$groupinfo['groupname'],'系统消息',null,$iconUrl);
		                $msg = Utils::WrapMessage("join_group",$groupinfo,$noticeinfo);
		                //添加成员成功发送消息
		                Utils::sendImMessage('',$newMember,"join_group",json_encode($msg),$this->container,"","",false,'');
			        }
            	}
		    }
		}
		catch(\Exception $e)
		{
			$this->container->get("logger")->err($e);
			return false;
		}
	    return true;		
	}
	  
	  	  
	///修改部门名称
	public function update_dept($parameter)
	{
	     $deptid = $parameter["deptid"];
	     $deptname = $parameter["deptname"];
	     $sql = "select dept_id,parent_dept_id from we_department where fafa_deptid=?;";
	     $result = array();
	     try
	     {
	        $ds = $this->conn->GetData("table",$sql,array((string)$deptid));
	        if ( $ds && $ds["table"]["recordcount"]>0)
	        {
	            $row = $ds["table"]["rows"][0];
	            $result = $this->updateDepartment(array("deptid"=>$row["dept_id"],"p_deptid"=>$row["parent_dept_id"],"deptname"=>$deptname));
	        }
	     }
	     catch(\Exception $e)
	     {
	        $result = array("success"=>true,"msg"=>"修改部门数据成功！");
	     }
	     return $result;  		    
	}
	  
    //设置部门名称显示或隐藏
    public function setDeptShowHide($deptid,$state)
    {
        $sql = "update im_base_dept set `show`=? where deptid=?;";
        $para = array((string)$state,(string)$deptid);
        $success = true;
        try
        {
            $this->conn_im->ExecSQL($sql,$para);        
        }
        catch(\Exception $e)
        {
            $success = false;
            $this->logger->err($e->getMessage());
        }
        return array("success"=>$success);
    }
    
    
}
