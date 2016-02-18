<?php

namespace WebIM\ImChatBundle\DataAccess;

class DataAccess
{
  protected $container;
  protected $logger;
  protected $conn;
  
  public function __construct($container, $conn_name)
  {
    $this->container = $container;
    $this->logger = $this->container->get('logger');
    $this->conn = $this->container->get("doctrine.dbal.".$conn_name."_connection");
  }
  
  public function __destruct() 
  {
    if ($this->conn) 
    {
      $this->conn->close();
      $this->conn = null;
    }
  }
  
  // ��ҳ��Ϣ
  public $PageSize = -1;
  public $PageIndex = 0;
  public $RecordCount = 0; // ����ҳ��Ϣ����ʱ����ֵΪ�ܼ�¼��

  //����ȡ��¼������SQL    
  public function GenCountSQL($sql)
  {
    return "select count(*) c from ($sql) as a";
  }
  
  //ȡ��
  //$MappingTable �߼�����
  //$SQL ��ѯSQL���ɲ�����
  //$Params ���в�������������ֵ
  //$Types  ���в���������Ҫ������¿ɸ����������ͣ����ޣ���PDO����²�
  //Return  array("$MappingTable" => $rows, "recordcount" => $this->RecordCount)��$rows pdo��ѯ���ص����飬$this->RecordCount��¼�������ǵ�ǰ���ؼ�¼����
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
  
  // ȡ�ö�����ݼ�,MappingTables��SQLs����һһ��Ӧ
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
  
  // ִ��SQL���
  // ����Ӱ������
  public function ExecSQL($SQL, array $Params = array(), array $Types = array()) 
  {
    $re = -1;
    try 
    {
      $re = $this->conn->executeUpdate($SQL, $Params, $Types);
    } 
    catch (\Exception $e) 
    {
      //$this->logger->err($SQL);
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