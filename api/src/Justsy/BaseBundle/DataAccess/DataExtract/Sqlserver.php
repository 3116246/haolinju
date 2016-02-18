<?php
namespace Justsy\BaseBundle\DataAccess\DataExtract;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Common\DES;
class Sqlserver implements SourceInf
{
	public function getByURL($url)
	{
		 return array();
	}

	//获得数据
	public function getByDsid($user,$re,$parameters,$container)
	{
		if(isset($re["inf_url"]) && isset($re["req_user"]) && isset($re["req_pass"]) && isset($re["req_action"]))
		{
			$configure = json_decode($re["inf_url"],true);
			$data = array();
			if ( count($configure)>1){
				$charset =isset($configure["charset"])&&!empty($configure["charset"]) ? $configure["charset"] : "GBK";
				$server = $configure["server"].(isset($configure["port"]) && !empty($configure["port"])?$configure["port"] : "3306");
				$dbname = $configure["dbname"];
				$sql = $re["req_action"];
				//判断服务器连接属性不允许为空
				if ( empty($server)){
					throw new \Exception("请指定服务器地址！");
				}
				//判断数据库名称不允许为空
				else if ( empty($dbname)){
					throw new \Exception("请指定数据库名称！");
				}
				else if ( empty($sql)){
					throw new \Exception("请求的SQL语句不允许为空！");
				}
				else{
					$uid = $re["req_user"];
					$pwd = DES::decrypt($re["req_pass"]);						
          			$connectionInfo = array( "UID"=>$uid,"PWD"=>$pwd,"Database"=>$dbname);
          			$conn = sqlsrv_connect( $server, $connectionInfo);
          			if( $conn ) 
					{
						$need_para = $re["inf_parameter"];
				        if(!empty($need_para) && is_string($need_para))
				        {
				        	$need_para = json_decode($need_para,true);
				        }
						//如果有传入参数请，对传入参数的处理
						if ( strpos($sql,"@")!== false && !empty($need_para)){
							
							$parameters = json_decode($parameters,true); //将json格式的参数转化为数组
							
							foreach($need_para as $key=>$val)
							{
								$paraname = "@".$key;
								if(isset($parameters[$key]))
								{
									$val = $parameters[$key];
								}
								if($paraname=="@pageno" || $paraname=="@pagesize")
								{
									$pagesize =isset($parameters["pagesize"])? $parameters["pagesize"] : $need_para["pagesize"];
									$pageno =isset($parameters["pageno"])? $parameters["pageno"] : $need_para["pageno"];
									//翻页参数处理
									$limitstart = $pageno*$pagesize;
									$sql = preg_replace("/@pageno *\* *@pagesize/is", $limitstart, $sql);
								}
								$sql = preg_replace("/".$paraname."/is", $val, $sql);								
							}
							$sql = rtrim($sql,";");
							$sqls = explode(";",$sql);							
						}
						else{
							$sqls = array($sql);
						}
						
						//执行有结果集的SQL语句
			            $table = sqlsrv_query($conn,$sql);
			            if ( $table === false){
			            	throw new \Exception(json_encode(sqlsrv_errors()));
			            }
			            else{
			          		$data = array();            	
				            while($row = sqlsrv_fetch_array($table,SQLSRV_FETCH_ASSOC))
				            {
				              	if ($charset != "UTF-8"){
					              	foreach($row as $key => $value){
					              		$row[$key] = iconv($charset,"UTF-8",$value);
					              	}
				                }
				              	array_push($data,$row);              	
				            }
				            return array("returncode"=>ReturnCode::$SUCCESS,"data"=>$data,"msg"=>"");
			            }
					} 
					else
					{
						throw new \Exception(json_encode(sqlsrv_errors()));
					}   
		    }
			}
			else{
				throw new \Exception("请指定数据库连接详细参数！");
			}
		}
		else
		{
			throw new \Exception("请指定数据库连接详细参数！");
		}
	}
}
?>