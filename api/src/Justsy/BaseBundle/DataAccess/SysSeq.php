<?php

namespace Justsy\BaseBundle\DataAccess;

class SysSeq
{
  public static function GetSeqNextValue(DataAccess $dataaccess, $table_name, $col_name,$count=1)
  {
  	if($count==1)
  	{
	    $ds = $dataaccess->GetData("seq", "call p_seq_nextvalue(?, ?, 0, @nextvalue)", array((string)$table_name, (string)$col_name));
	    return $ds["seq"]["rows"][0]["nextvalue"];
	}
	else
	{
	    $ds = $dataaccess->GetData("seq", "call p_seq_batchvalue(?, ?, ?, 0, @nextvalue)", array((string)$table_name, (string)$col_name,(int)$count));
	    return $ds["seq"]["rows"][0]["nextvalue"];		
	}
  }
  
}