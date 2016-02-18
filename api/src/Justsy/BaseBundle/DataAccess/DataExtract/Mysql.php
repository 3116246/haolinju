<?php
namespace Justsy\BaseBundle\DataAccess\DataExtract;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Common\DES;
class Mysql implements SourceInf
{
	public function getByURL($url)
	{
		 return null;
	}
	
	//获得数据
	public function getByDsid($user,$re,$parameters,$container)
	{
		if(isset($re["inf_url"]) && isset($re["req_user"]) && isset($re["req_pass"]) && isset($re["req_action"]))
		{
			//{"type":"MySQL","server":"182.92.11.9","port":"3531","charset":"UTF-8","dbname":"we_im"}
			$configure = json_decode($re["inf_url"],true);
			//$configure = explode(";",$url);
			$data = array();
			if ( count($configure)>1){
				$charset =isset($configure["charset"])&&!empty($configure["charset"]) ? $configure["charset"] : "utf8";
				$server = $configure["server"].":".(isset($configure["port"]) && !empty($configure["port"])?$configure["port"] : "3306");
				$dbname = $configure["dbname"];
				$sql = $re["req_action"];
				if ( empty($server)){
					throw new \Exception("请指定连接到的服务器！");
				}
				else if ( empty($dbname)){
					throw new \Exception("请指定连接到服务器的数据库名称！");
				}
				else if ( empty($sql)){
					throw new \Exception("请求的MySQL语句不能为空!");
				}
				else{
					$dbuser = $re["req_user"];
					$dbpassword =DES::decrypt($re["req_pass"]);			
					$conn = mysql_connect($server,$dbuser, $dbpassword);
					if ($conn) {
						mysql_select_db($dbname, $conn);
						$utf = "set names '".$charset."'";
						mysql_query($utf, $conn);
						$sqls = array();
						$need_para = $re["inf_parameter"];
				        if(!empty($need_para) && is_string($need_para))
				        {
				        	$need_para = json_decode($need_para,true);
				        }
						//如果有传入参数请，对传入参数的处理
						if ( strpos($sql,"@")!== false && !empty($need_para)){
							
							$parameters = json_decode($parameters,true); //将json格式的参数转化为数组
							
							for ($i=0; $i <count($need_para) ; $i++)
							{
								$key = $need_para[$i]["paramname"];
								$paraname = "@".$key;
								
								if($paraname=="@pageno" || $paraname=="@pagesize")
								{
									$pagesize =isset($parameters["pagesize"])? $parameters["pagesize"] : $need_para[$i]["paramvalue"];
									$pageno =isset($parameters["pageno"])? $parameters["pageno"] : $need_para[$i]["paramvalue"];
									//翻页参数处理
									$limitstart = $pageno*$pagesize;
									$sql = preg_replace("/@pageno *\* *@pagesize/is", $limitstart, $sql);
								}
								$val =isset($parameters[$key])? $parameters[$key] : $need_para[$i]["paramvalue"];
								
								$sql = preg_replace("/".$paraname."/is", $val, $sql);								
							}
							$sql = rtrim($sql,";");
							$sqls = explode(";",$sql);							
						}
						else{
							$sqls = array($sql);
						}
						$container->get("logger")->err(json_encode($sqls));
						for($i=0;$i< count($sqls);$i++){
							$sql = $sqls[$i];
							$table = mysql_query($sql);
							if ($table === false){
								mysql_close($conn);
								throw new \Exception("SQL[".$sql."]操作失败，请检查！");
							}
							else{
								$temp = array();
								if(mysql_num_rows($table)>0){
							   	 while ($row = mysql_fetch_array($table,MYSQL_ASSOC))
						       {
						    	    array_push($temp,$row);
						       }
						    }
						    if ( count($sqls)==1){
						       $data = array("returncode"=>ReturnCode::$SUCCESS,"data"=>$temp,"msg"=>"操作成功！");
						    }
						    else{
						    	$data["data".$i] = array("returncode"=>ReturnCode::$SUCCESS,"data"=>$temp,"msg"=>"操作成功！");
						    }
					    }							
						}						
				    //关闭数据库连接
				    mysql_close($conn);
				    return $data;
				  }
				  else{
				  	throw new \Exception("连接数据库失败，请检查您的数据库连接配置！");
				  }
		    }
			}
			else{
				throw new \Exception("数据库连接参数inf_url不详细！");
			}
		}
		else
		{
			throw new \Exception("请指定连接属性！");
		}
	}
}
?>