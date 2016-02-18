<?php

namespace Justsy\BaseBundle\DataAccess;

class DataAccess
{
  protected $container;
  protected $logger;
  protected $conn;
  
  
  public function __construct($container, $conn_name,$dbtype=null,$host=null,$dbname=null,$password=null,$user=null)
  {
    $this->container = $container;
    $this->logger = $this->container->get('logger');
    if(empty($conn_name))
      $this->conn($dbtype,$host,$dbname,$password,$user);   
    else
      $this->conn = $this->container->get("doctrine.dbal.".$conn_name."_connection");
  }
  
  public function conn($dbtype,$host,$dbname,$password,$user)
  {
  	try{
     $driver = "";
     if($dbtype=="oracle") $driver="pdo_oci";
     else if($dbtype=="mysql") $driver="pdo_mysql";
     else if($dbtype=="sqlserver2005" || $dbtype=="sqlserver2008") $driver="pdo_sqlsrv";
     $config = new \Doctrine\DBAL\Configuration();
     $connectionParams = array(
        'dbname' => $dbname,
        'user' => $user,
        'password' => $password,
        'host' => $host,
        'driver' => $driver,
        'charset' => 'UTF8'
     );
      $this->conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);     
    }
    catch(\Exception $e){
    	
    	$this->logger->err($e->getMessage());
    }
  }
  
  public function __destruct() 
  {
    if ($this->conn) 
    {
      $this->conn->close();
      $this->conn = null;
    }
  }
  
  // 分页信息
  public $PageSize = -1;
  public $PageIndex = 0;
  public $RecordCount = 0; // 当分页信息有用时，该值为总记录数

  //生成取记录总数的SQL    
  public function GenCountSQL($sql)
  {
    return "select count(*) c from ($sql) as a";
  }
  
  //取数
  //$MappingTable 逻辑表名
  //$SQL 查询SQL，可参数化
  //$Params 若有参数，给出参数值
  //$Types  若有参数，在需要的情况下可给出参数类型，若无，则按PDO规则猜测
  //Return  array("$MappingTable" => $rows, "recordcount" => $this->RecordCount)，$rows pdo查询返回的数组，$this->RecordCount记录总数（非当前返回记录数）
  public function GetData($MappingTable, $SQL, array $Params = array(), array $Types = array())
  {
    $dataset = null;
    $rows = null;
    try 
    {
      if ($this->PageSize > -1)
      {
        $sqlcount = $this->GenCountSQL($SQL);
        $sql = $SQL.sprintf(" limit %d, %d ", $this->PageIndex*$this->PageSize, $this->PageSize);
        
        $rows = $this->conn->executeQuery($sql, $Params, $Types)->fetchAll(\PDO::FETCH_ASSOC);
        $this->RecordCount = $this->conn->executeQuery($sqlcount, $Params, $Types)->fetchColumn(0);
      }
      else
      {
        $rows = $this->conn->executeQuery($SQL, $Params, $Types)->fetchAll(\PDO::FETCH_ASSOC);
        $this->RecordCount = count($rows);
      }
      
      $dataset[$MappingTable] = array('recordcount' => $this->RecordCount, 'rows' => $rows);
    } 
    catch (\Exception $e) 
    {    
      //$this->logger->err($SQL);
      $this->logger->err($e);
      throw $e;
    }
    
    return $dataset;
  }
  
  // 取得多个数据集,MappingTables和SQLs必须一一对应
  public function GetDatas(array $MappingTables, array $SQLs, array $Params = array(), array $Types = array())
  {
    $dataset = null;
    
    $count = count($MappingTables);
    $params_count = count($Params);
    $types_count = count($Types);
    for ($i = 0; $i < $count; $i++) 
    {
      $ds = $this->GetData($MappingTables[$i], $SQLs[$i], $params_count > 0 ? $Params[$i] : array(), $types_count > 0 ? $Types[$i] : array());
      $dataset[$MappingTables[$i]] = $ds[$MappingTables[$i]]; 
    }
    
    return $dataset;
  }
  
  // 执行SQL语句
  // 返回影响行数
  public function ExecSQL($SQL, array $Params = array(), array $Types = array()) 
  {
    $re = -1;
    try 
    {
      $re = $this->conn->executeUpdate($SQL, $Params, $Types);
    } 
    catch (\Exception $e) 
    {
      $this->logger->err("ExecSQL Error SQL>>>>>".$SQL);
      $this->logger->err("ExecSQL Error Params>>>>>".json_encode($Params));
      $this->logger->err($e);
      throw $e;
    }
    
    return $re;
  }
  public function ExecSQLs(array $SQLs, array $Params = array(), array $Types = array()) 
  {
    $re = array();
    
    try 
    {
      $this->conn->beginTransaction();
      $count = count($SQLs);
      $params_count = count($Params);
      $types_count = count($Types);
      for ($i = 0; $i < $count; $i++) 
      {
        $re[$i] = $this->ExecSQL($SQLs[$i], $params_count > 0 ? $Params[$i] : array(), $types_count > 0 ? $Types[$i] : array());
      }
      
      $this->conn->commit();
    } 
    catch (\Exception $e) 
    {
      $this->conn->rollback();
      throw $e;
    }

    return $re;
  }  
}