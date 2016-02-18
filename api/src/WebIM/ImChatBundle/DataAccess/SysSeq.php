<?php

namespace WebIM\ImChatBundle\DataAccess;

class SysSeq
{
  public static function GetSeqNextValue(DataAccess $dataaccess, $table_name, $col_name)
  {
    $ds = $dataaccess->GetData("seq", "call p_seq_nextvalue(?, ?, 0, @nextvalue)", array((string)$table_name, (string)$col_name));
    return $ds["seq"]["rows"][0]["nextvalue"];
  }
}