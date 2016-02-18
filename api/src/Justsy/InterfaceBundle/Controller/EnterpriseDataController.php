<?php

namespace Justsy\InterfaceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\DataAccess\SysSeq;

class EnterpriseDataController extends Controller{
	
	public function OperateEnterpriseDataAction(){
  		$da = $this->get("we_data_access");
  		$request = $this->getRequest();
  		$r["returncode"]=ReturnCode::$SUCCESS;
  		$r["msg"]="";
  		$cus_content=$request->get("cus");
    	if(empty($cus_content)){
    		$r["returncode"]=ReturnCode::$SYSERROR;
    		$r["msg"]="参数不能为空";
    	}
    	else{
    		$cus=json_decode($cus_content);
    		if(empty($cus)){
    			$r["returncode"]=ReturnCode::$SYSERROR;
    			$r["msg"]="参数格式有误,请确认";
    		}else{
    			if(empty($cus["ename"])||empty($cus["e_mail"])){
    				$r["returncode"]=ReturnCode::$SYSERROR;
    				$r["msg"]="企业名称和邮箱不能为空";
    			}else{
    				$sql_sel="select count(1) as count from we_enterprise_stored where enoname=? or eno_mail=?";
    				$para_sel=array((string)$cus["ename"],(string)$cus["e_mail"]);
    				$data_sel=$da->GetData("dt",$sql_sel,$para_sel);
    				if($data_sel!=null&&count($data_sel["dt"]["rows"][0]["count"])>0){
    					$r["returncode"]=ReturnCode::$SYSERROR;
    					$r["msg"]="企业名称和邮箱已经存在";
    				}else{
    					$id=SysSeq::GetSeqNextValue($da,"we_enterprise_stored","id");
    					$sql ="INSERT INTO `we_sns`.`we_enterprise_stored` (`id`, `enoname`, `eno_city`, `eno_website`, `eno_phone`, `eno_mail`, ";
    					$sql.="`eno_fax`, `eno_introduction`,`leaders_account`, `leaders_phone`, `leaders_mobile`, `leaders_mail`,) ";
    					$sql.="VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?);";
    					$para=array((string)$id
    										,(string)$cus["ename"]
    										,(string)$cus["address"]
    										,(string)$cus["website"]
    										,(string)$cus["phone"]
    										,(string)$cus["e_mail"]
    										,(string)$cus["fax"]
    										,(string)$cus["industry"]
    										,(string)$cus["contact"]
    										,(string)$cus["contact_phone"]
    										,(string)$cus["contact_mobile"]
    										,(string)$cus["contact_mail"]);
    					try {
    						$dataexec=$da->ExecSQL($sql,$para);
    						if(!$dataexec){
    							$re['returncode'] = ReturnCode::$SYSERROR;
        					$r["msg"]="保存企业数据失败,请确认信息";
    						}else{
    							$r["returncode"]=ReturnCode::$SUCCESS;
    							$r["msg"]="保存企业数据成功";
    						}
    					}
    					catch(\Exception $e){
        				$this->get('logger')->err($e);
        				$re['returncode'] = ReturnCode::$SYSERROR;
        				$r["msg"]="保存企业数据出现异常,请确认信息";
      				}
    				}
    			}
    		}
    	}
    	
  		$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
  		$response->headers->set('Content-Type', 'text/json');
  		return $response;
  }

   
}
