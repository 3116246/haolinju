<?php

namespace Justsy\BaseBundle\Business;

class WeStaff
{
  // 取得人员个人设置, 返回 [array(para_id => para_value)]
  public static function getPreference($da, $login_account) 
  {
    $list = array();
    
    $sql = "select para_id, para_value from we_staff_para where login_account=?";
    $params = array();
    $params[] = (string)$login_account;
    
    $ds = $da->GetData("we_staff_para", $sql, $params);
    foreach ($ds["we_staff_para"]["rows"] as &$value) 
    {
      $list[$value["para_id"]] = $value["para_value"];
    }
    
    return $list;
  }
}
