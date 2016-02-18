<?php

namespace Justsy\BaseBundle\Business;
use Justsy\BaseBundle\Common\Utils;
class Conv
{
  //替换表情、链接、关键词过滤等
  public function replaceContent($str, $facepath_pre) 
  {
    $pre = $facepath_pre;
    
    $str1 = $str;
    $str1 = \Justsy\BaseBundle\Common\KeywordFilter::filterKeyword($str1);
    $str1 = htmlentities($str1, ENT_QUOTES, "UTF-8");
    $str1 = preg_replace('/((?:https?|mailto):\/\/.*?)(\s|&nbsp;|<br|\'|\"|：|，|。|！|$)/',
                        '<a href="\1" title="\1" target="_blank" style="padding: 2px; font-weight: normal;"><img src="'.$pre.'../link16.png"> 链接地址</a>\2',
                        $str1);
    $str1 = preg_replace("/@(.*?)((\{[^\{\}]*\})|(?=&nbsp;|<br|@|\'|\"| |\.|\,|\/|\!|\:|$))/",
                        '@<a href="#" class="employee_name">\1\3</a>\4',
                        $str1);
    $str1 = preg_replace(\Justsy\BaseBundle\Common\Face::getFaceEmoteReg_GIF(), 
                        \Justsy\BaseBundle\Common\Face::getFaceEmoteImg_GIF("<img alt='' style='height: 24px; width: 24px;' src='${pre}[IMGSRC]'>"), 
                        $str1);
    return $str1;
  }
  
  public function newSysTrend($da, $conv_id, $conv_content, $post_to_circle, $post_to_group, $notifystaff, $attachs, $comefrom="00") 
  {
      $sqls = array();
      $all_params = array();
      
      $sqlInsert = 'insert into we_convers_list (conv_id, login_account, post_date, conv_type_id, conv_root_id, conv_content, post_to_group, post_to_circle, copy_num, reply_num, comefrom) values (?, ?, CURRENT_TIMESTAMP(), ?, ?, ?, ?, ?, 0, 0, ?)';
      $params = array();
      $params[] = (string)$conv_id;
      $params[] = (string)"sysadmin@fafatime.com";
      $params[] = (string)'00';
      $params[] = (string)$conv_id;
      $params[] = (string)$conv_content;
      $params[] = (string)$post_to_group;
      $params[] = (string)$post_to_circle;
      $params[] = (string)$comefrom;
            
      $sqls[] = $sqlInsert;
      $all_params[] = $params;
      
      for ($i=0; $i<count($notifystaff); $i++)
      {
        $sqlInsert = 'insert into we_convers_notify (conv_id, cc_login_account) values (?, ?)';
        $params = array();
        $params[] = (string)$conv_id;
        $params[] = (string)$notifystaff[$i];
              
        $sqls[] = $sqlInsert;
        $all_params[] = $params;
      }
      
      for ($i=0; $i<count($attachs); $i++)
      {
        $sqlInsert = "insert into we_convers_attach (conv_id, attach_type, attach_id) values (?, '0', ?)";
        $params = array();
        $params[] = (string)$conv_id;
        $params[] = (string)$attachs[$i];
              
        $sqls[] = $sqlInsert;
        $all_params[] = $params;
      }
    
      $da->ExecSQLs($sqls, $all_params); 
  }
  
  public function newTrend($da, $user, $conv_id, $conv_content, $post_to_circle, $post_to_group, $notifystaff, $attachs, $comefrom="00",$ctl=null) 
  {
      $sqls = array();
      $all_params = array();
      
      $sqlInsert = 'insert into we_convers_list (conv_id, login_account, post_date, conv_type_id, conv_root_id, conv_content, post_to_group, post_to_circle, copy_num, reply_num, comefrom) values (?, ?, CURRENT_TIMESTAMP(), ?, ?, ?, ?, ?, 0, 0, ?)';
      $params = array();
      $params[] = (string)$conv_id;
      $params[] = (string)$user->getUserName();
      $params[] = (string)'00';
      $params[] = (string)$conv_id;
      $params[] = (string)$conv_content;
      $params[] = (string)$post_to_group;
      $params[] = (string)$post_to_circle;
      $params[] = (string)$comefrom;
            
      $sqls[] = $sqlInsert;
      $all_params[] = $params;
      
      for ($i=0; $i<count($notifystaff); $i++)
      {
        $sqlInsert = 'insert into we_convers_notify (conv_id, cc_login_account) values (?, ?)';
        $params = array();
        $params[] = (string)$conv_id;
        $params[] = (string)$notifystaff[$i];
              
        $sqls[] = $sqlInsert;
        $all_params[] = $params;
      }
      
      for ($i=0; $i< count($attachs); $i++)
      {
        $sqlInsert = "insert into we_convers_attach (conv_id, attach_type, attach_id) values (?, '0', ?)";
        $params = array();
        $params[] = (string)$conv_id;
        $params[] = (string)$attachs[$i];
              
        $sqls[] = $sqlInsert;
        $all_params[] = $params;
      }
    
      $da->ExecSQLs($sqls, $all_params);
      if($comefrom=="01" || $comefrom=="02")
      {
        //手机发动态。对at的人员处理逻辑和web端不一样
        if("9999"==$post_to_circle)
          \Justsy\BaseBundle\Controller\CInputAreaController::genAtMe9999WithMobile($da, $conv_content, $conv_id, $user,$ctl);      
        else
          \Justsy\BaseBundle\Controller\CInputAreaController::genAtMeWithMobile($da, $conv_content, $conv_id, $user,$ctl);      
      }
      else
      {
        if("9999"==$post_to_circle)
        	\Justsy\BaseBundle\Controller\CInputAreaController::genAtMe9999($da, $conv_content, $conv_id, $user,$ctl);      
        else
        	\Justsy\BaseBundle\Controller\CInputAreaController::genAtMe($da, $conv_content, $conv_id, $user,$ctl);      
      }
  }
 
  //官方发言
  public function newOfficialTrend($da, $user, $conv_id, $conv_content, $post_to_circle, $post_to_group, $notifystaff, $attachs,$infoType, $comefrom="00",$ctl=null) 
  {
      $sqls = array();
      $all_params = array();
      
      $sqlInsert = 'insert into we_convers_list (conv_id, login_account, post_date, conv_type_id, conv_root_id, conv_content, post_to_group, post_to_circle, copy_num, reply_num, comefrom) values (?, ?, CURRENT_TIMESTAMP(), ?, ?, ?, ?, ?, 0, 0, ?)';
      $params = array();
      $params[] = (string)$conv_id;
      $params[] = (string)$user->getUserName();
      $params[] = (string)'06';
      $params[] = (string)$conv_id;
      $params[] = (string)$conv_content;
      $params[] = (string)$post_to_group;
      $params[] = (string)$post_to_circle;
      $params[] = (string)$comefrom;
            
      $sqls[] = $sqlInsert;
      $all_params[] = $params;
      
	    $sqlInsert = 'insert into we_official_publish (info_id, info_type, content) values (?, ?, ?)';
	    $params = array();
	    $params[] = (string)$conv_id;
	    $params[] = (string)$infoType;
	    $params[] = "";
	    $sqls[] = $sqlInsert;
	    $all_params[] = $params;      
      
//      for ($i=0; $i<count($notifystaff); $i++)
//      {
//        $sqlInsert = 'insert into we_convers_notify (conv_id, cc_login_account) values (?, ?)';
//        $params = array();
//        $params[] = (string)$conv_id;
//        $params[] = (string)$notifystaff[$i];
//              
//        $sqls[] = $sqlInsert;
//        $all_params[] = $params;
//      }
//      
      for ($i=0; $i<count($attachs); $i++)
      {
        $sqlInsert = "insert into we_convers_attach (conv_id, attach_type, attach_id) values (?, '0', ?)";
        $params = array();
        $params[] = (string)$conv_id;
        $params[] = (string)$attachs[$i];
              
        $sqls[] = $sqlInsert;
        $all_params[] = $params;
      }
    
      return $da->ExecSQLs($sqls, $all_params);
      
      //\Justsy\BaseBundle\Controller\CInputAreaController::genAtMe($da, $conv_content, $conv_id, $user,$ctl);      
  }  
  
  public function newAsk($da, $user, $conv_id, $conv_content, $post_to_circle, $post_to_group, $notifystaff, $attachs, $comefrom="00") 
  {
    $sqls = array();
    $all_params = array();
    
    $sqlInsert = 'insert into we_convers_list (conv_id, login_account, post_date, conv_type_id, conv_root_id, conv_content, post_to_group, post_to_circle, copy_num, reply_num, comefrom) values (?, ?, CURRENT_TIMESTAMP(), ?, ?, ?, ?, ?, 0, 0, ?)';
    $params = array();
    $params[] = (string)$conv_id;
    $params[] = (string)$user->getUserName();
    $params[] = (string)'01';
    $params[] = (string)$conv_id;
    $params[] = (string)$conv_content;
    $params[] = (string)$post_to_group;
    $params[] = (string)$post_to_circle;
    $params[] = (string)$comefrom;
          
    $sqls[] = $sqlInsert;
    $all_params[] = $params;
    
    for ($i=0; $i<count($notifystaff); $i++)
    {
      $sqlInsert = 'insert into we_convers_notify (conv_id, cc_login_account) values (?, ?)';
      $params = array();
      $params[] = (string)$conv_id;
      $params[] = (string)$notifystaff[$i];
            
      $sqls[] = $sqlInsert;
      $all_params[] = $params;
    }
    
    for ($i=0; $i<count($attachs); $i++)
    {
      $sqlInsert = "insert into we_convers_attach (conv_id, attach_type, attach_id) values (?, '0', ?)";
      $params = array();
      $params[] = (string)$conv_id;
      $params[] = (string)$attachs[$i];
            
      $sqls[] = $sqlInsert;
      $all_params[] = $params;
    }
    
    $da->ExecSQLs($sqls, $all_params);
  }
  
  public function newTogether($da, $user, $conv_id, $conv_content, $will_date, $will_dur, $will_addr, $together_desc, $post_to_circle, $post_to_group, $notifystaff, $attachs,$will_addr_map="", $comefrom="00") 
  {
    $sqls = array();
    $all_params = array();
    
    $sqlInsert = 'insert into we_convers_list (conv_id, login_account, post_date, conv_type_id, conv_root_id, conv_content, post_to_group, post_to_circle, copy_num, reply_num, comefrom) values (?, ?, CURRENT_TIMESTAMP(), ?, ?, ?, ?, ?, 0, 0, ?)';
    $params = array();
    $params[] = (string)$conv_id;
    $params[] = (string)$user->getUserName();
    $params[] = (string)'02';
    $params[] = (string)$conv_id;
    $params[] = (string)$conv_content;
    $params[] = (string)$post_to_group;
    $params[] = (string)$post_to_circle;
    $params[] = (string)$comefrom;
          
    $sqls[] = $sqlInsert;
    $all_params[] = $params;
    
    $sqlInsert = 'insert into we_together (together_id, title, will_date, will_dur, will_addr, together_desc,will_addr_map) values (?, ?, ?, ?, ?, ?,?)';
    $params = array();
    $params[] = (string)$conv_id;
    $params[] = (string)$conv_content;
    $params[] = (string)$will_date;
    $params[] = (string)$will_dur;
    $params[] = (string)$will_addr;
    $params[] = (string)$together_desc;
    $params[] = (string)$will_addr_map;
    $sqls[] = $sqlInsert;
    $all_params[] = $params;
    
    for ($i=0; $i<count($notifystaff); $i++)
    {
      $sqlInsert = 'insert into we_convers_notify (conv_id, cc_login_account) values (?, ?)';
      $params = array();
      $params[] = (string)$conv_id;
      $params[] = (string)$notifystaff[$i];
            
      $sqls[] = $sqlInsert;
      $all_params[] = $params;
    }
    
    for ($i=0; $i<count($attachs); $i++)
    {
      $sqlInsert = "insert into we_convers_attach (conv_id, attach_type, attach_id) values (?, '0', ?)";
      $params = array();
      $params[] = (string)$conv_id;
      $params[] = (string)$attachs[$i];
            
      $sqls[] = $sqlInsert;
      $all_params[] = $params;
    }
    
    $da->ExecSQLs($sqls, $all_params);
  }
  
  public function newVote($da, $user, $conv_id, $conv_content, $is_multi, $finishdate, $optionvalues, $post_to_circle, $post_to_group, $notifystaff, $attachs, $comefrom="00") 
  {
    $sqls = array();
    $all_params = array();
    
    $sqlInsert = 'insert into we_convers_list (conv_id, login_account, post_date, conv_type_id, conv_root_id, conv_content, post_to_group, post_to_circle, copy_num, reply_num, comefrom) values (?, ?, CURRENT_TIMESTAMP(), ?, ?, ?, ?, ?, 0, 0, ?)';
    $params = array();
    $params[] = (string)$conv_id;
    $params[] = (string)$user->getUserName();
    $params[] = (string)'03';
    $params[] = (string)$conv_id;
    $params[] = (string)$conv_content;
    $params[] = (string)$post_to_group;
    $params[] = (string)$post_to_circle;
    $params[] = (string)$comefrom;
          
    $sqls[] = $sqlInsert;
    $all_params[] = $params;
          
    $sqlInsert = 'insert into we_vote (vote_id, title, vote_all_num, is_multi,finishdate) values (?, ?, 0, ?,?)';
    $params = array();
    $params[] = (string)$conv_id;
    $params[] = (string)$conv_content;
    $params[] = (string)$is_multi;
    $params[] = (string)$finishdate;
    $sqls[] = $sqlInsert;
    $all_params[] = $params;
          
    for($i = 0; $i < count($optionvalues); $i++)
    {
      $sqlInsert = 'insert into we_vote_option (vote_id, option_id, option_desc, vote_num) values (?, ?, ?, 0)';
      $params = array();
      $params[] = (string)$conv_id;
      $params[] = (string)$i;
      $params[] = (string)$optionvalues[$i];
      
      $sqls[] = $sqlInsert;
      $all_params[] = $params;
    }
    
    for ($i=0; $i<count($notifystaff); $i++)
    {
      $sqlInsert = 'insert into we_convers_notify (conv_id, cc_login_account) values (?, ?)';
      $params = array();
      $params[] = (string)$conv_id;
      $params[] = (string)$notifystaff[$i];
            
      $sqls[] = $sqlInsert;
      $all_params[] = $params;
    }
    
    for ($i=0; $i<count($attachs); $i++)
    {
      $sqlInsert = "insert into we_convers_attach (conv_id, attach_type, attach_id) values (?, '0', ?)";
      $params = array();
      $params[] = (string)$conv_id;
      $params[] = (string)$attachs[$i];
            
      $sqls[] = $sqlInsert;
      $all_params[] = $params;
    }
          
    $da->ExecSQLs($sqls, $all_params);
  }
  //保存分享信息
  public function newShareTrend($da, $user, $conv_id,$reason, $conv_content, $post_to_circle, $post_to_group, $ref_url, $attachs, $comefrom="00",$ctl=null) 
  {
      $sqls = array();
      $all_params = array();
      
      $sqlInsert = 'insert into we_convers_list (conv_id, login_account, post_date, conv_type_id, conv_root_id, conv_content, post_to_group, post_to_circle, copy_num, reply_num, comefrom) values (?, ?, CURRENT_TIMESTAMP(), ?, ?, ?, ?, ?, 0, 0, ?)';
      $params = array();
      $params[] = (string)$conv_id;
      $params[] = (string)$user;
      $params[] = (string)'98';
      $params[] = (string)$conv_id;
      $params[] = (string)$conv_content;
      $params[] = (string)$post_to_group;
      $params[] = (string)$post_to_circle;
      $params[] = (string)$comefrom;
            
      $sqls[] = $sqlInsert;
      $all_params[] = $params;
      
    $sqlInsert = 'insert into we_share_info (info_id, info_from, content, reason, link_url) values (?, ?, ?, ?, ?)';
    $params = array();
    $params[] = (string)$conv_id;
    $params[] = (string)$comefrom;
    $params[] = "";    
    $params[] = (string)$reason;
    $params[] = (string)$ref_url;
    $sqls[] = $sqlInsert;
    $all_params[] = $params;
      
//      for ($i=0; $i<count($notifystaff); $i++)
//      {
//        $sqlInsert = 'insert into we_convers_notify (conv_id, cc_login_account) values (?, ?)';
//        $params = array();
//        $params[] = (string)$conv_id;
//        $params[] = (string)$notifystaff[$i];
//              
//        $sqls[] = $sqlInsert;
//        $all_params[] = $params;
//      }
      
      for ($i=0; $i<count($attachs); $i++)
      {
        $sqlInsert = "insert into we_convers_attach (conv_id, attach_type, attach_id) values (?, '0', ?)";
        $params = array();
        $params[] = (string)$conv_id;
        $params[] = (string)$attachs[$i];
              
        $sqls[] = $sqlInsert;
        $all_params[] = $params;
      }
    
      $da->ExecSQLs($sqls, $all_params);
      
      //\Justsy\BaseBundle\Controller\CInputAreaController::genAtMe($da, $conv_content, $conv_id, $user,$ctl);      
  }
  
  public function getTrend($da, $user, $conv_root_id, $FILE_WEBSERVER_URL)
  {
    $sqls = array();
    $all_params = array();
    
    $sql = "select a.conv_id, a.login_account, a.post_date, a.conv_type_id, a.conv_root_id, a.conv_content, 
  a.post_to_group, a.post_to_circle, a.reply_to, a.copy_num, a.reply_num, a.atten_num, a.like_num, a.comefrom,
  b.nick_name, b.photo_path, concat('$FILE_WEBSERVER_URL', ifnull(b.photo_path, '')) photo_url, b.photo_path_small, b.photo_path_big,b.auth_level, b.we_level,b.total_point,
  f_cal_date_section(a.post_date) post_date_d, ep.eshortname, ep.vip_level , rp.nick_name reply_to_name, wsa.atten_id, 
  wsa.atten_date, f_cal_date_section(wsa.atten_date) atten_date_d, cf.name comefrom_d, 
  case when a.login_account like concat('%@',ep.edomain) then '1' else '0' end isvip
from we_convers_list a
inner join we_staff b on a.login_account=b.login_account
inner join we_enterprise ep on b.eno=ep.eno
left  join we_staff rp on rp.login_account=a.reply_to
left  join we_staff_atten wsa on wsa.login_account=? and wsa.atten_type='02' and wsa.atten_id=a.conv_id
left  join we_code_all_code cf on cf.id=a.comefrom and cf.class='来自于'
where a.conv_root_id=?
order by (conv_id = conv_root_id) desc, (0+conv_id) desc";
    $params = array();
    $params[] = (string)$user->getUserName();
    $params[] = (string)$conv_root_id;
    
    $sql .=" limit 0,7; ";  //最多显示10条回复
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    $sql = "select a.attach_id, b.file_name, b.file_ext, b.up_by_staff, b.up_date
from we_convers_attach a, we_files b
where a.attach_id=b.file_id
  and a.attach_type='0' and a.conv_id=?;";
    $params = array();
    $params[] = (string)$conv_root_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    //标签
    $sql = "select a.label_name from we_convers_label a where a.login_account=? and a.atten_id=?;";
    $params = array();
    $params[] = (string)$user->getUserName();
    $params[] = (string)$conv_root_id;    
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    //CC
    $sql = "select a.cc_login_account, b.nick_name from we_convers_notify a, we_staff b where a.cc_login_account=b.login_account and a.conv_id=?;";
    $params = array();
    $params[] = (string)$conv_root_id;    
    
    $sqls[] = $sql;
    $all_params[] = $params;    
    try
    {
       $ds = $da->GetDatas(array("we_convers_list", "we_convers_attach", "we_convers_label", "we_convers_notify"), $sqls, $all_params);
    }
    catch(\Exception $e)
    {
    }
    //取like
    $sql = "call p_we_getlikes ( ? , ? , ? , ? , ? , ? , ? , ? );";
    $params = array();
    $params[] = (string)$user->getUserName();    
    $params[] = "";        
    $params[] = "";
    $params[] = "";        
    $params[] = "";        
    $params[] = "";
    $params[] = "";
    $params[] = "";
    $i = 0;
    foreach ($ds["we_convers_list"]["rows"] as &$row) 
    {
        $params[++$i] = $row["conv_id"];
    }
    try
    {
        $ds1 = $da->GetData("we_convers_like", $sql, $params);
        $ds["we_convers_like"] = $ds1["we_convers_like"];   
    }
    catch(\Exception $e)
    {       
    }    
    //取reply attach   
    $whereWH = "'x'";
    $params = array(); 
    for ($i=1; $i < count($ds["we_convers_list"]["rows"]); $i++) {
      $whereWH .= ",?";
      $params[$i-1] = $ds["we_convers_list"]["rows"][$i]["conv_id"];
    } 
    $sql = "select a.conv_id, a.attach_id, b.file_name, b.file_ext, b.up_by_staff, b.up_date
from we_convers_attach a, we_files b
where a.attach_id=b.file_id
  and a.attach_type='0' and a.conv_id in ($whereWH)";
  try
  {
    $ds1 = $da->GetData("we_convers_attach_reply", $sql, $params);
    $ds["we_convers_attach_reply"] = $ds1["we_convers_attach_reply"];
  }
  catch(\Exception $e)
  {
  }
    return $ds;
  }
  //获取指定的分享动态信息
  public function getShareTrend($da, $user, $conv_root_id, $FILE_WEBSERVER_URL)
  {
    $sqls = array();
    $all_params = array();
    
    $sql = "select a.conv_id, a.login_account, a.post_date, a.conv_type_id, a.conv_root_id, a.conv_content, 
  a.post_to_group, a.post_to_circle, a.reply_to, a.copy_num, a.reply_num, a.atten_num, a.like_num, a.comefrom,
  b.nick_name, b.photo_path, concat('$FILE_WEBSERVER_URL', ifnull(b.photo_path, '')) photo_url, b.photo_path_small, b.photo_path_big,b.auth_level, b.we_level,b.total_point,
  f_cal_date_section(a.post_date) post_date_d, ep.eshortname, rp.nick_name reply_to_name, wsa.atten_id, 
  wsa.atten_date, f_cal_date_section(wsa.atten_date) atten_date_d, cf.name comefrom_d, 
  case when a.login_account like concat('%@',ep.edomain) then '1' else '0' end isvip,wsi.reason,wsi.link_url
from we_convers_list a
inner join we_staff b on a.login_account=b.login_account
inner join we_enterprise ep on b.eno=ep.eno
inner join we_share_info wsi on a.conv_id=wsi.info_id
left  join we_staff rp on rp.login_account=a.reply_to
left  join we_staff_atten wsa on wsa.login_account=? and wsa.atten_type='02' and wsa.atten_id=a.conv_id
left  join we_code_all_code cf on cf.id=a.comefrom and cf.class='分享自'
where a.conv_root_id=?
order by (conv_id = conv_root_id) desc, (0+conv_id) desc";
    $params = array();
    $params[] = (string)$user->getUserName();
    $params[] = (string)$conv_root_id;
    
    $sql .= " limit 0, 6 "; //最多显示5条回复
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    $sql = "select a.attach_id, b.file_name, b.file_ext, b.up_by_staff, b.up_date
from we_convers_attach a, we_files b
where a.attach_id=b.file_id
  and a.attach_type='0' and a.conv_id=?";
    $params = array();
    $params[] = (string)$conv_root_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    //标签
    $sql = "select a.label_name from we_convers_label a where a.login_account=? and a.atten_id=?";
    $params = array();
    $params[] = (string)$user->getUserName();
    $params[] = (string)$conv_root_id;    
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    //CC
//    $sql = "select a.cc_login_account, b.nick_name from we_convers_notify a, we_staff b where a.cc_login_account=b.login_account and a.conv_id=?";
//    $params = array();
//    $params[] = (string)$conv_root_id;    
//    
//    $sqls[] = $sql;
//    $all_params[] = $params;
    
    $ds = $da->GetDatas(array("we_convers_list", "we_convers_attach", "we_convers_label"), $sqls, $all_params);
    
    //取like
    $sql = "call p_we_getlikes ( ? , ? , ? , ? , ? , ? , ? , ? );";
    $params = array();
    $params[] = (string)$user->getUserName();    
    $params[] = "";        
    $params[] = "";
    $params[] = "";        
    $params[] = "";        
    $params[] = "";
    $params[] = "";
    $params[] = "";
    $i = 0;
    foreach ($ds["we_convers_list"]["rows"] as &$row) 
    {
      $params[++$i] = $row["conv_id"];
    } 
    $ds1 = $da->GetData("we_convers_like", $sql, $params);
    $ds["we_convers_like"] = $ds1["we_convers_like"];
    
    //取reply attach   
    $whereWH = "'x'";
    $params = array(); 
    for ($i=1; $i < count($ds["we_convers_list"]["rows"]); $i++) {
      $whereWH .= ",?";
      $params[$i-1] = $ds["we_convers_list"]["rows"][$i]["conv_id"];
    } 
    $sql = "select a.conv_id, a.attach_id, b.file_name, b.file_ext, b.up_by_staff, b.up_date
from we_convers_attach a, we_files b
where a.attach_id=b.file_id
  and a.attach_type='0' and a.conv_id in ($whereWH)";
    $ds1 = $da->GetData("we_convers_attach_reply", $sql, $params);
    $ds["we_convers_attach_reply"] = $ds1["we_convers_attach_reply"];
    
    return $ds;
  }
    

  public function getOfficialTrend($da, $user, $conv_root_id, $FILE_WEBSERVER_URL)
  {
    $sqls = array();
    $all_params = array();
    
    $sql = "select a.conv_id, a.login_account, a.post_date, a.conv_type_id, a.conv_root_id, a.conv_content, 
  a.post_to_group, a.post_to_circle, a.reply_to, a.copy_num, a.reply_num, a.atten_num, a.like_num, a.comefrom,
  b.nick_name, b.photo_path, concat('$FILE_WEBSERVER_URL', ifnull(b.photo_path, '')) photo_url, b.photo_path_small, b.photo_path_big,b.auth_level, b.we_level,b.total_point,
  f_cal_date_section(a.post_date) post_date_d, ep.eshortname, rp.nick_name reply_to_name, wsa.atten_id, 
  wsa.atten_date, f_cal_date_section(wsa.atten_date) atten_date_d, cf.name comefrom_d,
  case a.post_to_group when 'ALL' then concat('$FILE_WEBSERVER_URL', ifnull(ep.logo_path, '')) else (select concat('$FILE_WEBSERVER_URL',ifnull(group_photo_path,'')) from we_groups where a.post_to_group=we_groups.group_id) end en_logo_path,
  case when a.login_account like concat('%@',ep.edomain) then '1' else '0' end isvip,wop.info_type,case wop.info_type when 'notice' then '通知' else '公告' end info_type_name,ifnull(hi.conv_id,'') as hide,ifnull(p.conv_id,'') as top 
from we_convers_list a
left join we_conv_hide hi on hi.login_account=? and hi.conv_id=a.conv_id 
left join we_conv_top p on p.conv_id=a.conv_id and p.timeout>now() 
inner join we_staff b on a.login_account=b.login_account
inner join we_enterprise ep on b.eno=ep.eno
inner join we_official_publish wop on a.conv_root_id=wop.info_id
left  join we_staff rp on rp.login_account=a.reply_to
left  join we_staff_atten wsa on wsa.login_account=? and wsa.atten_type='02' and wsa.atten_id=a.conv_id
left  join we_code_all_code cf on cf.id=a.comefrom and cf.class='来自于'
where a.conv_root_id=?
order by (a.conv_id = a.conv_root_id) desc, (0+a.conv_id) desc";
    $params = array();
    $params[] = (string)$user->getUserName();
    $params[] = (string)$user->getUserName();
    $params[] = (string)$conv_root_id;
    
    $sql .= " limit 0, 6 "; //最多显示5条回复
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    $sql = "select a.attach_id, b.file_name, b.file_ext, b.up_by_staff, b.up_date
from we_convers_attach a, we_files b
where a.attach_id=b.file_id
  and a.attach_type='0' and a.conv_id=?";
    $params = array();
    $params[] = (string)$conv_root_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    //标签
    $sql = "select a.label_name from we_convers_label a where a.login_account=? and a.atten_id=?";
    $params = array();
    $params[] = (string)$user->getUserName();
    $params[] = (string)$conv_root_id;    
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    //CC
//    $sql = "select a.cc_login_account, b.nick_name from we_convers_notify a, we_staff b where a.cc_login_account=b.login_account and a.conv_id=?";
//    $params = array();
//    $params[] = (string)$conv_root_id;    
//    
//    $sqls[] = $sql;
//    $all_params[] = $params;
    
    $ds = $da->GetDatas(array("we_convers_list", "we_convers_attach", "we_convers_label"), $sqls, $all_params);
    
    //取like
    $sql = "call p_we_getlikes ( ? , ? , ? , ? , ? , ? , ? , ? );";
    $params = array();
    $params[] = (string)$user->getUserName();    
    $params[] = "";        
    $params[] = "";
    $params[] = "";        
    $params[] = "";        
    $params[] = "";
    $params[] = "";
    $params[] = "";
    $i = 0;
    foreach ($ds["we_convers_list"]["rows"] as &$row) 
    {
      $params[++$i] = $row["conv_id"];
    } 
    $ds1 = $da->GetData("we_convers_like", $sql, $params);
    $ds["we_convers_like"] = $ds1["we_convers_like"];
    
    //取reply attach   
    $whereWH = "'x'";
    $params = array(); 
    for ($i=1; $i < count($ds["we_convers_list"]["rows"]); $i++) {
      $whereWH .= ",?";
      $params[$i-1] = $ds["we_convers_list"]["rows"][$i]["conv_id"];
    } 
    $sql = "select a.conv_id, a.attach_id, b.file_name, b.file_ext, b.up_by_staff, b.up_date
from we_convers_attach a, we_files b
where a.attach_id=b.file_id
  and a.attach_type='0' and a.conv_id in ($whereWH)";
    $ds1 = $da->GetData("we_convers_attach_reply", $sql, $params);
    $ds["we_convers_attach_reply"] = $ds1["we_convers_attach_reply"];
    
    return $ds;
  } 
  
  public function getRelationSysTrend($da, $user, $conv_root_id, $FILE_WEBSERVER_URL)
  {
    $sqls = array();
    $all_params = array();
    
    $sql = "select '".$conv_root_id."' conv_id, 'sysadmin@fafatime.com' login_account, '' post_date, '00' conv_type_id, '".$conv_root_id."' conv_root_id, (select content from we_official_publish where info_id='".$conv_root_id."') conv_content, 
  'ALL' post_to_group, '9999' post_to_circle, '' reply_to, 0 copy_num, 0 reply_num, 0 atten_num, 0 like_num, 'Wefafa团队' comefrom,
   b.nick_name, b.photo_path, concat('FILE_WEBSERVER_URL', ifnull(b.photo_path, '')) photo_url, b.photo_path_small, b.photo_path_big,b.auth_level, b.we_level,b.total_point,
  '' post_date_d, '' eshortname, '' reply_to_name, '' atten_id, 
  '' atten_date, '' atten_date_d, 'Wefafa团队' comefrom_d, '1' isvip
from we_staff b where b.login_account='sysadmin@fafatime.com'";
    $params = array();
    $ds = $da->GetData("we_convers_list", $sql, $params);
    $ds["we_convers_attach"] = array("recordcount"=>0,"rows"=>[]);
    $ds["we_convers_label"] =  array("recordcount"=>0,"rows"=>[]);
    $ds["we_convers_notify"] = array("recordcount"=>0,"rows"=>[]);
    $ds["we_convers_like"] =  array("recordcount"=>0,"rows"=>[]);
    $ds["we_convers_attach_reply"] = array("recordcount"=>0,"rows"=>[]);

    return $ds;      	
  }
  
  public function getAsk($da, $user, $conv_root_id, $FILE_WEBSERVER_URL)
  {
    $sqls = array();
    $all_params = array();
    
    $sql = "select a.conv_id, a.login_account, a.post_date, a.conv_type_id, a.conv_root_id, a.conv_content, 
  a.post_to_group, a.post_to_circle, a.reply_to, a.copy_num, a.reply_num, a.atten_num, a.like_num, a.comefrom,
  b.nick_name, b.photo_path, concat('$FILE_WEBSERVER_URL', ifnull(b.photo_path, '')) photo_url, b.photo_path_small, b.photo_path_big,b.auth_level,ep.vip_level,b.we_level,b.total_point,
  f_cal_date_section(a.post_date) post_date_d, ep.eshortname, rp.nick_name reply_to_name, wsa.atten_id, 
  wsa.atten_date, f_cal_date_section(wsa.atten_date) atten_date_d, cf.name comefrom_d, 
  case when a.login_account like concat('%@',ep.edomain) then '1' else '0' end isvip
from we_convers_list a
inner join we_staff b on a.login_account=b.login_account
inner join we_enterprise ep on b.eno=ep.eno
left  join we_staff rp on rp.login_account=a.reply_to
left  join we_staff_atten wsa on wsa.login_account=? and wsa.atten_type='02' and wsa.atten_id=a.conv_id
left  join we_code_all_code cf on cf.id=a.comefrom and cf.class='来自于'
where a.conv_root_id=?
order by (conv_id = conv_root_id) desc, (0+conv_id) desc";
    $params = array();
    $params[] = (string)$user->getUserName();
    $params[] = (string)$conv_root_id;
    
    $sql .= " limit 0, 6 "; //最多显示5条回复
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    $sql = "select a.attach_id, b.file_name, b.file_ext, b.up_by_staff, b.up_date
from we_convers_attach a, we_files b
where a.attach_id=b.file_id
  and a.attach_type='0' and a.conv_id=?";
    $params = array();
    $params[] = (string)$conv_root_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    //标签
    $sql = "select a.label_name from we_convers_label a where a.login_account=? and a.atten_id=?";
    $params = array();
    $params[] = (string)$user->getUserName();
    $params[] = (string)$conv_root_id;    
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    //CC
    $sql = "select a.cc_login_account, b.nick_name from we_convers_notify a, we_staff b where a.cc_login_account=b.login_account and a.conv_id=?";
    $params = array();
    $params[] = (string)$conv_root_id;    
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    $ds = $da->GetDatas(array("we_convers_list", "we_convers_attach", "we_convers_label", "we_convers_notify"), $sqls, $all_params);
    
    //取like
    $sql = "call p_we_getlikes ( ? , ? , ? , ? , ? , ? , ? , ? );";
    $params = array();
    $params[] = (string)$user->getUserName();    
    $params[] = "";        
    $params[] = "";
    $params[] = "";        
    $params[] = "";        
    $params[] = "";
    $params[] = "";
    $params[] = "";
    $i = 0;
    foreach ($ds["we_convers_list"]["rows"] as &$row) 
    {
      $params[++$i] = $row["conv_id"];
    } 
    $ds1 = $da->GetData("we_convers_like", $sql, $params);
    $ds["we_convers_like"] = $ds1["we_convers_like"];
    
    //取reply attach   
    $whereWH = "'x'";
    $params = array(); 
    for ($i=1; $i < count($ds["we_convers_list"]["rows"]); $i++) {
      $whereWH .= ",?";
      $params[$i-1] = $ds["we_convers_list"]["rows"][$i]["conv_id"];
    } 
    $sql = "select a.conv_id, a.attach_id, b.file_name, b.file_ext, b.up_by_staff, b.up_date
from we_convers_attach a, we_files b
where a.attach_id=b.file_id
  and a.attach_type='0' and a.conv_id in ($whereWH)";
    $ds1 = $da->GetData("we_convers_attach_reply", $sql, $params);
    $ds["we_convers_attach_reply"] = $ds1["we_convers_attach_reply"];
    
    return $ds;
  }
  
  public function getTogether($da, $user, $conv_root_id, $FILE_WEBSERVER_URL)
  {
    $sqls = array();
    $all_params = array();
    
    $sql = "select a.conv_id, a.login_account, a.post_date, a.conv_type_id, a.conv_root_id, a.conv_content, 
  a.post_to_group, a.post_to_circle, a.reply_to, a.copy_num, a.reply_num, a.atten_num, a.like_num, a.comefrom,
  b.nick_name, b.photo_path, concat('$FILE_WEBSERVER_URL', ifnull(b.photo_path, '')) photo_url, b.photo_path_small, b.photo_path_big,b.auth_level,ep.vip_level, b.we_level,b.total_point,
  f_cal_date_section(a.post_date) post_date_d, ep.eshortname, rp.nick_name reply_to_name, wsa.atten_id, 
  wsa.atten_date, f_cal_date_section(wsa.atten_date) atten_date_d, cf.name comefrom_d, 
  case when a.login_account like concat('%@',ep.edomain) then '1' else '0' end isvip
from we_convers_list a
inner join we_staff b on a.login_account=b.login_account
inner join we_enterprise ep on b.eno=ep.eno
left  join we_staff rp on rp.login_account=a.reply_to
left  join we_staff_atten wsa on wsa.login_account=? and wsa.atten_type='02' and wsa.atten_id=a.conv_id
left  join we_code_all_code cf on cf.id=a.comefrom and cf.class='来自于'
where a.conv_root_id=?
order by (conv_id = conv_root_id) desc, (0+conv_id) desc";
    $params = array();
    $params[] = (string)$user->getUserName();
    $params[] = (string)$conv_root_id;
    
    $sql .= " limit 0, 6 "; //最多显示5条回复
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    $sql = "select a.attach_id, b.file_name, b.file_ext, b.up_by_staff, b.up_date
from we_convers_attach a, we_files b
where a.attach_id=b.file_id
  and a.attach_type='0' and a.conv_id=?";
    $params = array();
    $params[] = (string)$conv_root_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    $sql = "select together_id, title, will_date, will_dur, will_addr, together_desc,will_addr_map
from we_together a
where a.together_id=?";
    $params = array();
    $params[] = (string)$conv_root_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    $sql = "select a.login_account, b.nick_name 
from we_together_staff a, we_staff b 
where a.login_account=b.login_account
  and a.together_id=?";
    $params = array();
    $params[] = (string)$conv_root_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    //标签
    $sql = "select a.label_name from we_convers_label a where a.login_account=? and a.atten_id=?";
    $params = array();
    $params[] = (string)$user->getUserName();
    $params[] = (string)$conv_root_id;    
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    //CC
    $sql = "select a.cc_login_account, b.nick_name from we_convers_notify a, we_staff b where a.cc_login_account=b.login_account and a.conv_id=?";
    $params = array();
    $params[] = (string)$conv_root_id;    
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    $ds = $da->GetDatas(array("we_convers_list", "we_convers_attach", "we_together", "we_together_staff", "we_convers_label", "we_convers_notify"), $sqls, $all_params);
    
    //取like
    $sql = "call p_we_getlikes ( ? , ? , ? , ? , ? , ? , ? , ? );";
    $params = array();
    $params[] = (string)$user->getUserName();    
    $params[] = "";        
    $params[] = "";
    $params[] = "";        
    $params[] = "";        
    $params[] = "";
    $params[] = "";
    $params[] = "";
    $i = 0;
    foreach ($ds["we_convers_list"]["rows"] as &$row) 
    {
      $params[++$i] = $row["conv_id"];
    } 
    $ds1 = $da->GetData("we_convers_like", $sql, $params);
    $ds["we_convers_like"] = $ds1["we_convers_like"];
    
    //取reply attach   
    $whereWH = "'x'";
    $params = array(); 
    for ($i=1; $i < count($ds["we_convers_list"]["rows"]); $i++) {
      $whereWH .= ",?";
      $params[$i-1] = $ds["we_convers_list"]["rows"][$i]["conv_id"];
    } 
    $sql = "select a.conv_id, a.attach_id, b.file_name, b.file_ext, b.up_by_staff, b.up_date
from we_convers_attach a, we_files b
where a.attach_id=b.file_id
  and a.attach_type='0' and a.conv_id in ($whereWH)";
    $ds1 = $da->GetData("we_convers_attach_reply", $sql, $params);
    $ds["we_convers_attach_reply"] = $ds1["we_convers_attach_reply"];
    
    return $ds;
  }
  
  public function getVote($da, $user, $conv_root_id, $FILE_WEBSERVER_URL)
  {
    $sqls = array();
    $all_params = array();
    
    $sql = "select a.conv_id, a.login_account, a.post_date, a.conv_type_id, a.conv_root_id, a.conv_content, 
  a.post_to_group, a.post_to_circle, a.reply_to, a.copy_num, a.reply_num, a.atten_num, a.like_num, a.comefrom,
  b.nick_name, b.photo_path, concat('$FILE_WEBSERVER_URL', ifnull(b.photo_path, '')) photo_url, b.photo_path_small, b.photo_path_big,b.auth_level,ep.vip_level, b.we_level,b.total_point,
  f_cal_date_section(a.post_date) post_date_d, ep.eshortname, rp.nick_name reply_to_name, wsa.atten_id, 
  wsa.atten_date, f_cal_date_section(wsa.atten_date) atten_date_d, cf.name comefrom_d, 
  case when a.login_account like concat('%@',ep.edomain) then '1' else '0' end isvip
from we_convers_list a
inner join we_staff b on a.login_account=b.login_account
inner join we_enterprise ep on b.eno=ep.eno
left  join we_staff rp on rp.login_account=a.reply_to
left  join we_staff_atten wsa on wsa.login_account=? and wsa.atten_type='02' and wsa.atten_id=a.conv_id
left  join we_code_all_code cf on cf.id=a.comefrom and cf.class='来自于'
where a.conv_root_id=?
order by (conv_id = conv_root_id) desc, (0+conv_id) desc";
    $params = array();
    $params[] = (string)$user->getUserName();
    $params[] = (string)$conv_root_id;
    
    $sql .= " limit 0, 6 "; //最多显示5条回复
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    $sql = "select a.attach_id, b.file_name, b.file_ext, b.up_by_staff, b.up_date
from we_convers_attach a, we_files b
where a.attach_id=b.file_id
  and a.attach_type='0' and a.conv_id=?";
    $params = array();
    $params[] = (string)$conv_root_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    $sql = "select vote_id, title, vote_all_num, is_multi, finishdate, (select count(DISTINCT b.login_account) c from we_vote_user b where b.vote_id=a.vote_id) vote_user_num
from we_vote a
where a.vote_id=?";
    $params = array();
    $params[] = (string)$conv_root_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    $sql = "select vote_id, option_id, option_desc, vote_num
from we_vote_option a
where a.vote_id=?
order by option_id";
    $params = array();
    $params[] = (string)$conv_root_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    $sql = "select count(*) c from we_vote_user where vote_id=? and login_account=?";
    $params = array();
    $params[] = (string)$conv_root_id;
    $params[] = (string)$user->getUserName();
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    //标签
    $sql = "select a.label_name from we_convers_label a where a.login_account=? and a.atten_id=?";
    $params = array();
    $params[] = (string)$user->getUserName();
    $params[] = (string)$conv_root_id;    
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    //CC
    $sql = "select a.cc_login_account, b.nick_name from we_convers_notify a, we_staff b where a.cc_login_account=b.login_account and a.conv_id=?";
    $params = array();
    $params[] = (string)$conv_root_id;    
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    $ds = $da->GetDatas(array("we_convers_list", "we_convers_attach", "we_vote", "we_vote_option", "we_vote_user", "we_convers_label", "we_convers_notify"), $sqls, $all_params);
    
    //取like
    $sql = "call p_we_getlikes ( ? , ? , ? , ? , ? , ? , ? , ? );";
    $params = array();
    $params[] = (string)$user->getUserName();    
    $params[] = "";        
    $params[] = "";
    $params[] = "";        
    $params[] = "";        
    $params[] = "";
    $params[] = "";
    $params[] = "";
    $i = 0;
    foreach ($ds["we_convers_list"]["rows"] as &$row) 
    {
      $params[++$i] = $row["conv_id"];
    } 
    $ds1 = $da->GetData("we_convers_like", $sql, $params);
    $ds["we_convers_like"] = $ds1["we_convers_like"];
    
    //取reply attach   
    $whereWH = "'x'";
    $params = array(); 
    for ($i=1; $i < count($ds["we_convers_list"]["rows"]); $i++) {
      $whereWH .= ",?";
      $params[$i-1] = $ds["we_convers_list"]["rows"][$i]["conv_id"];
    } 
    $sql = "select a.conv_id, a.attach_id, b.file_name, b.file_ext, b.up_by_staff, b.up_date
from we_convers_attach a, we_files b
where a.attach_id=b.file_id
  and a.attach_type='0' and a.conv_id in ($whereWH)";
    $ds1 = $da->GetData("we_convers_attach_reply", $sql, $params);
    $ds["we_convers_attach_reply"] = $ds1["we_convers_attach_reply"];
    
    return $ds;
  }
  
  public function getCopy($da, $user, $conv_root_id, $FILE_WEBSERVER_URL)
  {
    $sqls = array();
    $all_params = array();
    
    $sql = "select a.conv_id, a.login_account, a.post_date, a.conv_type_id, a.conv_root_id, a.conv_content, 
  a.post_to_group, a.post_to_circle, a.reply_to, a.copy_num, a.reply_num, a.atten_num, a.like_num, a.copy_id, a.comefrom,
  b.nick_name, b.photo_path, concat('$FILE_WEBSERVER_URL', ifnull(b.photo_path, '')) photo_url, b.photo_path_small, b.photo_path_big,b.auth_level,ep.vip_level,b.we_level,b.total_point,
  f_cal_date_section(a.post_date) post_date_d, ep.eshortname, rp.nick_name reply_to_name, wsa.atten_id, 
  wsa.atten_date, f_cal_date_section(wsa.atten_date) atten_date_d, cf.name comefrom_d, 
  case when a.login_account like concat('%@',ep.edomain) then '1' else '0' end isvip
from we_convers_list a
inner join we_staff b on a.login_account=b.login_account
inner join we_enterprise ep on b.eno=ep.eno
left  join we_staff rp on rp.login_account=a.reply_to
left  join we_staff_atten wsa on wsa.login_account=? and wsa.atten_type='02' and wsa.atten_id=a.conv_id
left  join we_code_all_code cf on cf.id=a.comefrom and cf.class='来自于'
where a.conv_root_id=?
order by (conv_id = conv_root_id) desc, (0+conv_id) desc";
    $params = array();
    $params[] = (string)$user->getUserName();
    $params[] = (string)$conv_root_id;
    
    $sql .= " limit 0, 6 "; //最多显示5条回复
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    $sql = "select a.attach_id, b.file_name, b.file_ext, b.up_by_staff, b.up_date
from we_convers_attach a, we_files b
where a.attach_id=b.file_id
  and a.attach_type='0' and a.conv_id=?";
    $params = array();
    $params[] = (string)$conv_root_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    $sql = "select a.conv_id, a.login_account, a.post_date, a.conv_type_id, a.conv_root_id, a.conv_content, 
  a.post_to_group, a.post_to_circle, a.reply_to, a.copy_num, a.reply_num, a.atten_num, a.like_num, a.copy_id, a.comefrom,
  b.nick_name, b.photo_path, concat('$FILE_WEBSERVER_URL', ifnull(b.photo_path, '')) photo_url, b.photo_path_small, b.photo_path_big,
  f_cal_date_section(a.post_date) post_date_d, ep.eshortname, cf.name comefrom_d
from we_convers_list a, we_staff b, we_enterprise ep, we_convers_list c, we_code_all_code cf 
where a.login_account=b.login_account
  and b.eno=ep.eno
  and a.conv_id=c.copy_id
  and cf.id=a.comefrom and cf.class='来自于'
  and c.conv_id=?";
    $params = array();
    $params[] = (string)$conv_root_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    $sql = "select a.attach_id, b.file_name, b.file_ext, b.up_by_staff, b.up_date
from we_convers_attach a, we_files b, we_convers_list c
where a.attach_id=b.file_id
  and a.attach_type='0' 
  and a.conv_id=c.copy_id
  and c.conv_id=?";
    $params = array();
    $params[] = (string)$conv_root_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    $sql = "select a.conv_id, a.like_staff, a.like_date, b.nick_name
from we_convers_like a, we_staff b, we_convers_list c 
where a.like_staff=b.login_account
  and a.conv_id=c.copy_id
  and c.conv_id=? 
order by (a.like_staff=?) desc, a.like_date desc
limit 0, 10";   //最多显示10个赞美人
    $params = array();
    $params[] = (string)$conv_root_id;
    $params[] = (string)$user->getUserName();
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    //标签
    $sql = "select a.label_name from we_convers_label a where a.login_account=? and a.atten_id=?";
    $params = array();
    $params[] = (string)$user->getUserName();
    $params[] = (string)$conv_root_id;    
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    //CC
    $sql = "select a.cc_login_account, b.nick_name from we_convers_notify a, we_staff b where a.cc_login_account=b.login_account and a.conv_id=?";
    $params = array();
    $params[] = (string)$conv_root_id;    
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    $ds = $da->GetDatas(array("we_convers_list", "we_convers_attach", "we_convers_list_copy", "we_convers_attach_copy", "we_convers_like_copy", "we_convers_label", "we_convers_notify"), $sqls, $all_params);
    
    //取like
    $sql = "call p_we_getlikes ( ? , ? , ? , ? , ? , ? , ? , ? );";
    $params = array();
    $params[] = (string)$user->getUserName();    
    $params[] = "";        
    $params[] = "";
    $params[] = "";        
    $params[] = "";        
    $params[] = "";
    $params[] = "";
    $params[] = "";
    $i = 0;
    foreach ($ds["we_convers_list"]["rows"] as &$row) 
    {
      $params[++$i] = $row["conv_id"];
    } 
    $ds1 = $da->GetData("we_convers_like", $sql, $params);
    $ds["we_convers_like"] = $ds1["we_convers_like"];
    
    //取reply attach   
    $whereWH = "'x'";
    $params = array(); 
    for ($i=1; $i < count($ds["we_convers_list"]["rows"]); $i++) {
      $whereWH .= ",?";
      $params[$i-1] = $ds["we_convers_list"]["rows"][$i]["conv_id"];
    } 
    $sql = "select a.conv_id, a.attach_id, b.file_name, b.file_ext, b.up_by_staff, b.up_date
from we_convers_attach a, we_files b
where a.attach_id=b.file_id
  and a.attach_type='0' and a.conv_id in ($whereWH)";
    $ds1 = $da->GetData("we_convers_attach_reply", $sql, $params);
    $ds["we_convers_attach_reply"] = $ds1["we_convers_attach_reply"];

    return $ds;
  }
  
  //检查是否用户的会话
  public function checkIsOwenConv($da, $conv_id, $login_account) 
  {
    $sql = "select login_account,(select instr(concat(';',manager,';',create_staff),?) managers from we_circle where circle_id=post_to_circle) managers from we_convers_list where conv_id=?";
    $params = array();
    $params[] = (string)$login_account;
    $params[] = (string)$conv_id; 
    
    $ds = $da->GetData("we_convers_list", $sql, $params);
    
    //判断是否是管理员删除 
    $result = (count($ds["we_convers_list"]["rows"])>0 && $ds["we_convers_list"]["rows"][0]["managers"]>0); 
    if(!$result)
    {
        $result = ($ds["we_convers_list"]["rows"][0]["login_account"] == $login_account);
    }
    return $result;
  }
  
  //检查是否可浏览会话
  public function checkCanViewConv($da, $conv_id, $login_account) 
  {
    $sql = "select count(*) c 
from we_convers_list a 
where conv_id=? 
  and exists(select 1 from dual where a.login_account=? 
       union select 1 from we_staff_atten wsa where wsa.login_account=? and wsa.atten_type='01' and wsa.atten_id=a.login_account and a.post_to_circle='9999'
       union select 1 from we_circle_staff b where b.login_account=? and b.circle_id=a.post_to_circle) ";
    $params = array();
    $params[] = (string)$conv_id;
    $params[] = (string)$login_account;
    $params[] = (string)$login_account;
    $params[] = (string)$login_account;
    
    $ds = $da->GetData("we_convers_list", $sql, $params);
    
    return ($ds["we_convers_list"]["rows"][0]["c"] > 0);
  }
  
  //删除会话通用信息，包含会话信息we_convers_list、人员关注we_staff_atten、通知对象we_convers_notify、会话附件we_convers_attach、会话_赞we_convers_like
  //对于其它信息，应先删除，再调用本函数，比如投票
  public function delConvByRootID($da,$conv_root_id)
  {
    $sqls = array();
    $all_params = array();    
    //关注
    $sql = "delete a from we_staff_atten a inner join we_convers_list b on b.conv_id=a.atten_id and b.conv_root_id=? where a.atten_type='02'";
    $params = array();
    $params[] = (string)$conv_root_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    //标签
    $sql = "delete a from we_convers_label a inner join we_convers_list b on b.conv_id=a.atten_id and b.conv_root_id=?";
    $params = array();
    $params[] = (string)$conv_root_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    //提到我的
    $sql = "delete a from we_staff_at_me a inner join we_convers_list b on b.conv_id=a.conv_id and b.conv_root_id=?";
    $params = array();
    $params[] = (string)$conv_root_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    //会话附件
    $sql = "delete a from we_convers_attach a inner join we_convers_list b on b.conv_id=a.conv_id and b.conv_root_id=? where a.attach_type='0'";
    $params = array();
    $params[] = (string)$conv_root_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    //通知对象
    $sql = "delete a from we_convers_notify a inner join we_convers_list b on b.conv_id=a.conv_id and b.conv_root_id=?";
    $params = array();
    $params[] = (string)$conv_root_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    //会话_赞
    $sql = "delete a from we_convers_like a inner join we_convers_list b on b.conv_id=a.conv_id and b.conv_root_id=?";
    $params = array();
    $params[] = (string)$conv_root_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    //会话
    $sql = "delete from we_convers_list where conv_root_id=?";
    $params = array();
    $params[] = (string)$conv_root_id;
      
    $sqls[] = $sql;
    $all_params[] = $params;
    $success = true;
    try
    {
        $da->ExecSQLs($sqls, $all_params);        
    }
    catch(\Exception $e)
    {
        $success = false;        
    }
    return $success;
  }
  
  public function delTogether($da, $conv_root_id) 
  {
    $sqls = array();
    $all_params = array();
    
    $sql = "delete a from we_together a where a.together_id = ? ";
    $params = array();
    $params[] = (string)$conv_root_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    $sql = "delete a from we_together_staff a where a.together_id = ? ";
    $params = array();
    $params[] = (string)$conv_root_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
 
    $da->ExecSQLs($sqls, $all_params);
    
    return $this->delConvByRootID($da, $conv_root_id);
  }
  
  public function delVote($da, $conv_root_id) 
  {
    $sqls = array();
    $all_params = array();
  
    $sql = "delete a from we_vote a where a.vote_id = ? ";
    $params = array();
    $params[] = (string)$conv_root_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
  
    $sql = "delete a from we_vote_option a where a.vote_id = ? ";
    $params = array();
    $params[] = (string)$conv_root_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
  
    $sql = "delete a from we_vote_user a where a.vote_id = ? ";
    $params = array();
    $params[] = (string)$conv_root_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
 
    $da->ExecSQLs($sqls, $all_params);
    
    return $this->delConvByRootID($da, $conv_root_id);
  }
  
  //删除回复信息，包含会话信息we_convers_list、人员关注we_staff_atten、通知对象we_convers_notify、会话附件we_convers_attach、会话_赞we_convers_like
  public function delReplyByID($da, $conv_id)
  {
    $sqls = array();
    $all_params = array();
    
    //更新评论数
    $sql = "update we_convers_list a, we_convers_list b set a.reply_num=a.reply_num-1 
where a.conv_id=b.conv_root_id 
  and b.conv_id=?";
    $params = array();
    $params[] = (string)$conv_id;
          
    $sqls[] = $sql;
    $all_params[] = $params;
    
//    //关注
//    $sql = "delete a from we_staff_atten a inner join we_convers_list b on b.conv_id=a.atten_id and b.conv_id=? where a.atten_type='02'";
//    $params = array();
//    $params[] = (string)$conv_id;
//    
//    $sqls[] = $sql;
//    $all_params[] = $params;
//    
//    //标签
//    $sql = "delete a from we_convers_label a inner join we_convers_list b on b.conv_id=a.atten_id and b.conv_id=?";
//    $params = array();
//    $params[] = (string)$conv_id;
//    
//    $sqls[] = $sql;
//    $all_params[] = $params;
    
    //提到我的
    $sql = "delete a from we_staff_at_me a inner join we_convers_list b on b.conv_id=a.conv_id and b.conv_id=?";
    $params = array();
    $params[] = (string)$conv_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    //会话附件
    $sql = "delete a from we_convers_attach a inner join we_convers_list b on b.conv_id=a.conv_id and b.conv_id=? where a.attach_type='0'";
    $params = array();
    $params[] = (string)$conv_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    //通知对象
    $sql = "delete a from we_convers_notify a inner join we_convers_list b on b.conv_id=a.conv_id and b.conv_id=?";
    $params = array();
    $params[] = (string)$conv_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    //会话_赞
    $sql = "delete a from we_convers_like a inner join we_convers_list b on b.conv_id=a.conv_id and b.conv_id=?";
    $params = array();
    $params[] = (string)$conv_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    //会话
    $sql = "delete from we_convers_list where conv_id=?";
    $params = array();
    $params[] = (string)$conv_id;
      
    $sqls[] = $sql;
    $all_params[] = $params;
    
    return $da->ExecSQLs($sqls, $all_params);
  }
  
  public function likeConv($da, $user, $conv_root_id) 
  {
    $sqls = array();
    $all_params = array();

    $sql = "insert into we_convers_like (conv_id, like_staff, like_date)
select ?, ?, current_timestamp()
from dual
where not exists(select 1 from we_convers_like a where a.conv_id=? and a.like_staff=?)";
    $params = array();
    $params[] = (string)$conv_root_id;
    $params[] = (string)$user->getUserName();
    $params[] = (string)$conv_root_id;
    $params[] = (string)$user->getUserName();
    
    $sqls[] = $sql;
    $all_params[] = $params;

    $sql = "update we_convers_list set like_num=ifnull(like_num, 0) + 1 where conv_id=?";
    $params = array();
    $params[] = (string)$conv_root_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    return $da->ExecSQLs($sqls, $all_params);
  }
  
  public function unlikeConv($da, $user, $conv_root_id) 
  {
    $sqls = array();
    $all_params = array();

    $sql = "delete a from we_convers_like a
where a.conv_id=? and a.like_staff=?";
    $params = array();
    $params[] = (string)$conv_root_id;
    $params[] = (string)$user->getUserName();
    
    $sqls[] = $sql;
    $all_params[] = $params;

    $sql = "update we_convers_list set like_num=like_num - 1 where conv_id=?";
    $params = array();
    $params[] = (string)$conv_root_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    return $da->ExecSQLs($sqls, $all_params);
  }
  
  public function replyConv($da, $user, $conv_root_id, $conv_id, $conv_content, $reply_to, $comefrom="00", $ownerContoller=null, $attachs=[]) 
  {
    $sqls = array();
    $all_params = array();
    
    $sql = "update we_convers_list set reply_num=reply_num+1 where conv_id=?";
    $params = array();
    $params[] = (string)$conv_root_id;
          
    $sqls[] = $sql;
    $all_params[] = $params;
    
    $sqlInsert = 'insert into we_convers_list (conv_id, login_account, post_date, conv_type_id, conv_root_id, conv_content, post_to_group, post_to_circle, reply_to, copy_num, reply_num, comefrom) 
select ?, ?, CURRENT_TIMESTAMP(), ?, ?, ?, a.post_to_group, a.post_to_circle, ?, 0, 0, ?
from we_convers_list a
where a.conv_id=?';
    $params = array();
    $params[] = (string)$conv_id;
    $params[] = (string)$user->getUserName();
    $params[] = (string)'99';
    $params[] = (string)$conv_root_id;
    $params[] = (string)$conv_content;
    $params[] = (string)$reply_to;
    $params[] = (string)$comefrom;
    $params[] = (string)$conv_root_id;
          
    $sqls[] = $sqlInsert;
    $all_params[] = $params;
    
//    for ($i=0; $i<count($notifystaff); $i++)
//    {
//      $sqlInsert = 'insert into we_convers_notify (conv_id, cc_login_account) values (?, ?)';
//      $params = array();
//      $params[] = (string)$conv_id;
//      $params[] = (string)$notifystaff[$i];
//            
//      $sqls[] = $sqlInsert;
//      $all_params[] = $params;
//    }
   
   for ($i=0; $i<count($attachs); $i++)
   {
     $sqlInsert = "insert into we_convers_attach (conv_id, attach_type, attach_id) values (?, '0', ?)";
     $params = array();
     $params[] = (string)$conv_id;
     $params[] = (string)$attachs[$i];
           
     $sqls[] = $sqlInsert;
     $all_params[] = $params;
   }
  
    $da->ExecSQLs($sqls, $all_params);
    if($ownerContoller!=null && $reply_to!=$user->getUserName()) {
	    try {
  	      $link = $ownerContoller->get('router')->generate("JustsyBaseBundle_view_oneconv",array("conv_root_id"=> $conv_root_id),true);
          //发送即时消息
          //$link = $this->generateUrl("JustsyBaseBundle_view_oneconv",array("conv_root_id"=> $conv_root_id),true);
          $linkButtons=Utils::makeBusButton(array(array("code"=>"action","text"=>"查看","blank"=>"1","value"=> "")));
          if(empty($reply_to)) {
	           $message = "好友".Utils::makeHTMLElementTag('employee',$user->fafa_jid,$user->nick_name)."评论了您的动态！";
	           $tmp_rs = $da->GetData("tmp","SELECT b.fafa_jid,b.login_account FROM we_convers_list a,we_staff b where a.login_account=b.login_account and a.conv_id=?",array((string)$conv_root_id));
             //var_dump($tmp_rs["tmp"]["rows"][0]["login_account"],$user->getUserName());
             if($tmp_rs!=null && count($tmp_rs["tmp"]["rows"])>0 && $tmp_rs["tmp"]["rows"][0]["login_account"]!=$user->getUserName()) { //回复自己发的动态不发消息
	              $to_jid = $tmp_rs["tmp"]["rows"][0]["fafa_jid"];
	              Utils::sendImMessage($user->fafa_jid,$to_jid ,"trend-reply",$message,$ownerContoller,$link,$linkButtons,false,Utils::$systemmessage_code);
	           }
          } else {
             $message = "好友".Utils::makeHTMLElementTag('employee',$user->fafa_jid,$user->nick_name)."回复了您的评论！";
             $tmp_rs = $da->GetData("tmp","SELECT b.fafa_jid FROM we_staff b where b.login_account=?",array((string)$reply_to));
             //var_dump($reply_to,$tmp_rs["tmp"]["rows"][0]["fafa_jid"],$user->getUserName());
             if($tmp_rs!=null && count($tmp_rs["tmp"]["rows"])>0) {
               $to_jid = $tmp_rs["tmp"]["rows"][0]["fafa_jid"];
               Utils::sendImMessage($user->fafa_jid,$to_jid ,"trend-reply",$message,$ownerContoller,$link,$linkButtons,false,Utils::$systemmessage_code);
             }
          }
		  } catch (\Exception $e) {
          $this->get('logger')->err($e);
			}
	  }
	  
	  //生成评论数
	  if ($reply_to != $user->getUserName())
	  {
      $sql = "";
      $params = array();
      
	    if(empty($reply_to))
	    {
  	    $sql = "insert into we_notify(notify_type, msg_id, notify_staff) 
select '04', ?, login_account 
from we_convers_list
where conv_id=? and login_account<>?";
        $params = array();
        $params[] = (string)$conv_id;
        $params[] = (string)$conv_root_id;
        $params[] = (string)$user->getUserName();
	    }
	    else
	    {
  	    $sql = "insert into we_notify(notify_type, msg_id, notify_staff) values('04', ?, ?)";
        $params = array();
        $params[] = (string)$conv_id;
        $params[] = (string)$reply_to;
	    }
	    
	    $da->ExecSQL($sql, $params);
	  }	  
    \Justsy\BaseBundle\Controller\CInputAreaController::genAtMe($da, $conv_content, $conv_id, $user,$ownerContoller);
  }
  
  //查询是否已投票
  public function checkIsVoted($da, $vote_id, $login_account) 
  {
    $sql = "select count(*) c from we_vote_user where vote_id=? and login_account=?";
    $params = array();
    $params[] = (string)$vote_id;
    $params[] = (string)$login_account;
    
    $ds = $da->GetData("we_vote_user", $sql, $params);
    
    return ($ds["we_vote_user"]["rows"][0]["c"] > 0);
  }
  
  public function vote($da, $user, $vote_id, $is_multi, $optionids) 
  {
    //更新投票数
    $sqls = array();
    $all_params = array();
    
    //we_vote
    $sql = "update we_vote set vote_all_num = vote_all_num + ? where vote_id=?";
    $params = array();
    $params[] = (int)($is_multi=="1" ? (count($optionids)) : 1);
    $params[] = (string)$vote_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    //we_vote_option
    $sqlwhere = "?";
    $params = array();
    if ($is_multi=="1")
    {
      $sqlwhere = array();
      $params = array();
      
      for($i=0; $i<count($optionids); $i++)
      {
        $sqlwhere[] = "?";
        $params[] = (string)$optionids[$i];
      }
      $sqlwhere = join(",", $sqlwhere);
    }
    else
    {        
      $params[] = (string)$optionids;
    }
    $sql = "update we_vote_option set vote_num = vote_num + 1 where option_id in ($sqlwhere) and vote_id=?";
    $params[] = (string)$vote_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    //we_vote_user
    if ($is_multi=="1")
    {
      for($i=0; $i<count($optionids); $i++)
      {
        $sql = "insert into we_vote_user(vote_id, option_id, login_account, vote_date) values(?, ?, ?, current_timestamp())";
        $params = array();
        $params[] = (string)$vote_id;
        $params[] = (string)$optionids[$i];
        $params[] = (string)$user->getUserName();
        
        $sqls[] = $sql;
        $all_params[] = $params;
      }        
    }
    else
    {          
        $sql = "insert into we_vote_user(vote_id, option_id, login_account, vote_date) values(?, ?, ?, current_timestamp())";
        $params = array();
        $params[] = (string)$vote_id;
        $params[] = (string)$optionids;
        $params[] = (string)$user->getUserName();
        
        $sqls[] = $sql;
        $all_params[] = $params;
    }
    
    //投票积分0.1 每日前5次
    $sql = "insert into we_staff_points(login_account, point_date, point_type, point_desc, point)
select ?, current_timestamp(), '06', '您参与一次投票，获得积分0.1', 0.1
from dual
where 5 > (select count(*) from we_staff_points where login_account=? and point_date>=current_date() and point_type='06')";
    $params = array();
    $params[] = (string)$user->getUserName();
    $params[] = (string)$user->getUserName();
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    return $da->ExecSQLs($sqls, $all_params);
  }
  
  //取出圈子消息是否能转发
  public function getCircleLimit($da, $circle_id)
  {
    $re = array();
    
    $sql = "select ifnull(a.allow_copy, '0') allow_copy
from we_circle a
where a.circle_id=?";
    $params = array();
    $params[] = (string)$circle_id;
    
    $ds = $da->GetData("we_circle", $sql, $params);
    
    if ($ds["we_circle"]["recordcount"] > 0)
    {
      $re["allow_copy"] = $ds["we_circle"]["rows"][0]["allow_copy"];
    }
    else
    {
      $re["allow_copy"] = "1";   //不允许转发
    }
    
    return $re;
  }
  
  //取出消息是否能转发
  public function getConvLimit($da, $conv_id, $post_to_circle)
  {
    $re = array();
    
    $sql = "select sum(cc) c from (
select count(*) cc
from we_circle a, we_convers_list b
where a.circle_id=b.post_to_circle
  and b.conv_id=?
  and b.post_to_circle<>?
  and a.allow_copy='1'
) as _ttt_";
    $params = array();
    $params[] = (string)$conv_id;
    $params[] = (string)$post_to_circle;
    
    $ds = $da->GetData("we_convers_list", $sql, $params);
    
    if ($ds["we_convers_list"]["rows"][0]["c"] > 0)
    {
      $re["allow_copy"] = "1"; //不允许转发
    }
    else
    {
      $re["allow_copy"] = "0";  
    }
    
    return $re;
  }
  
  public function copyConv($da, $user, $conv_id, $conv_content, $post_to_circle, $post_to_group, $copy_id, $copy_last_id, $comefrom="00") 
  {
    $sqls = array();
    $all_params = array();
    
    $sqlInsert = 'insert into we_convers_list (conv_id, login_account, post_date, conv_type_id, conv_root_id, conv_content, post_to_group, post_to_circle, copy_num, reply_num, copy_id, copy_last_id, comefrom) values (?, ?, CURRENT_TIMESTAMP(), ?, ?, ?, ?, ?, 0, 0, ?, ?, ?)';
    $params = array();
    $params[] = (string)$conv_id;
    $params[] = (string)$user->getUserName();
    $params[] = (string)'05';
    $params[] = (string)$conv_id;
    $params[] = (string)$conv_content;
    $params[] = (string)$post_to_group;
    $params[] = (string)$post_to_circle;
    $params[] = (string)$copy_id;
    $params[] = (string)$copy_last_id;
    $params[] = (string)$comefrom;
          
    $sqls[] = $sqlInsert;
    $all_params[] = $params;
    
    $sqlUpdate = "update we_convers_list set copy_num=ifnull(copy_num, 0) + 1 where conv_id=?";
    $params = array();
    $params[] = (string)$copy_id;
    
    $sqls[] = $sqlUpdate;
    $all_params[] = $params;
  
    if ($copy_last_id && $copy_last_id != $copy_id)
    {    
      $sqlUpdate = "update we_convers_list set copy_num=ifnull(copy_num, 0) + 1 where conv_id=?";
      $params = array();
      $params[] = (string)$copy_last_id;
      
      $sqls[] = $sqlUpdate;
      $all_params[] = $params;      
    }
  
    $da->ExecSQLs($sqls, $all_params);
    
    \Justsy\BaseBundle\Controller\CInputAreaController::genAtMe($da, $conv_content, $conv_id, $user);
  }
  
  public function attenConv($da, $user, $conv_root_id)
  {
    $sqls = array();
    $all_params = array();

    $sql = "update we_convers_list set atten_num=ifnull(atten_num,0)+1 
where conv_id=? 
  and not exists(select 1 from we_staff_atten b where b.login_account=? and b.atten_type='02' and b.atten_id = we_convers_list.conv_id)";
    $params = array();
    $params[] = (string)$conv_root_id;
    $params[] = (string)$user->getUserName();
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    $sql = "insert into we_staff_atten(login_account, atten_type, atten_id, atten_date)
select ?, '02', ?, now()
from dual
where not exists(select 1 from we_staff_atten b where b.login_account=? and b.atten_type='02' and b.atten_id=?)";
    $params = array();
    $params[] = (string)$user->getUserName();
    $params[] = (string)$conv_root_id;
    $params[] = (string)$user->getUserName();
    $params[] = (string)$conv_root_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    //积分收藏09 0.1
    $sqls[] = "insert into we_staff_points (login_account,point_type,point_desc,point,point_date)
      values (?,?,?,?,now())";
    $all_params[] = array(
      (string)$user->getUserName(),
      (string)'09',
      (string)'收藏'.$conv_root_id.'，获得积分0.1',
      (float)0.1);
    
    $ds = $da->ExecSQLs($sqls, $all_params);  
    
    return $ds;
  }
   
  public function unattenConv($da, $user, $conv_root_id)
  {
    $sqls = array();
    $all_params = array();

    $sql = "update we_convers_list set atten_num=ifnull(atten_num,0)-1 
where conv_id=? 
and exists(select 1 from we_staff_atten b where b.login_account=? and b.atten_type='02' and b.atten_id = we_convers_list.conv_id)";
    $params = array();
    $params[] = (string)$conv_root_id;
    $params[] = (string)$user->getUserName();
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    $sql = "delete from we_staff_atten where login_account=? and atten_type='02' and atten_id=?";
    $params = array();
    $params[] = (string)$user->getUserName();
    $params[] = (string)$conv_root_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    $sql = "delete from we_convers_label where login_account=? and atten_id=?";
    $params = array();
    $params[] = (string)$user->getUserName();
    $params[] = (string)$conv_root_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    //积分收藏09 0.1
    $sqls[] = "insert into we_staff_points (login_account,point_type,point_desc,point,point_date)
      values (?,?,?,?,now())";
    $all_params[] = array(
      (string)$user->getUserName(),
      (string)'09',
      (string)'取消收藏'.$conv_root_id.'，扣除积分0.1',
      (float)-0.1);
    
    $ds = $da->ExecSQLs($sqls, $all_params);
    
    return $ds;
  }
  
  public function joinTogether($da, $user, $together_id) 
  {
    $sqls = array();
    $all_params = array();
  
    $sql = "insert into we_together_staff(together_id, login_account)
select ?, ?
from dual
where not exists(select 1 from we_together_staff b where b.together_id=? and b.login_account=?)";
    $params = array();
    $params[] = (string)$together_id;
    $params[] = (string)$user->getUserName();
    $params[] = (string)$together_id;
    $params[] = (string)$user->getUserName();
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    $ds = $da->ExecSQLs($sqls, $all_params);
    
    return $ds;
  }
  
  public function unjoinTogether($da, $user, $together_id) 
  {
    $sqls = array();
    $all_params = array();
  
    $sql = "delete from we_together_staff where together_id=? and login_account=?";
    $params = array();
    $params[] = (string)$together_id;
    $params[] = (string)$user->getUserName();
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    $ds = $da->ExecSQLs($sqls, $all_params);
    
    return $ds;
  }
  
  //更新用户最后读的信息ID
  public function updateLastReadID_Circle($da, $user, $circle_id, $conv_id) 
  {
    $sqls = array();
    $all_params = array();
    if($circle_id=="9999")
    {
       //判断是否已加入成员信息，没有则insert
       $sql = "select 1 from we_circle_staff where circle_id=? and login_account=?";
       $existDs = $da->GetData("t",$sql,array((string)$circle_id,(string)$user->getUserName()));
       if($existDs && $existDs["t"]["recordcount"]==0)
       {
          $sqls = "insert into we_circle_staff(circle_id,login_account,last_read_id)values(?,?,(select conv_id from we_convers_list order by post_date desc limit 1))";
          $da->ExecSQL($sqls, array(
              (string)$circle_id,
              (string)$user->getUserName()
          ));
          return 1;
       } 
    }

    $sql = "update we_circle_staff set last_read_id=(select conv_id from we_convers_list order by post_date desc limit 1)
where circle_id=? and login_account=?";
    $params = array();
    $params[] = (string)$circle_id;
    $params[] = (string)$user->getUserName();
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    $ds = $da->ExecSQLs($sqls, $all_params);
    
    return $ds;    
  }
  
  //更新用户最后读的信息ID
  public function updateLastReadID_Group($da, $user, $group_id, $conv_id) 
  {
    $sqls = array();
    $all_params = array();
    
    $sql = "update we_group_staff set last_read_id=?
where group_id=? and login_account=?";
    $params = array();
    $params[] = (string)$conv_id;
    $params[] = (string)$group_id;
    $params[] = (string)$user->getUserName();
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    $ds = $da->ExecSQLs($sqls, $all_params);
    
    return $ds;  
  }
  
  //取得标签
  public function getLabel($da, $user)
  {
    $sqls = array();
    $all_params = array();
    
    $sql = "select distinct a.label_name
from we_convers_label a
where a.login_account=?
limit 0, 10";
    $params = array();
    $params[] = (string)$user->getUserName();
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    $ds = $da->GetDatas(array("we_convers_label"), $sqls, $all_params);
    
    return $ds;  
  }
  
  //保存标签
  public function saveLabel($da, $user, $atten_id, $label_names)
  {
    $sqls = array();
    $all_params = array();
    
    $sql = "delete from we_convers_label where login_account=? and atten_id=?";
    $params = array();
    $params[] = (string)$user->getUserName();
    $params[] = (string)$atten_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    for ($i=0; $i<count($label_names); $i++)
    {
    	if($label_names[$i]) continue;
      $sql = 'insert into we_convers_label (login_account, atten_id, label_name) values (?, ?, ?)';
      $params = array();
      $params[] = (string)$user->getUserName();
      $params[] = (string)$atten_id;
      $params[] = (string)$label_names[$i];
            
      $sqls[] = $sql;
      $all_params[] = $params;
    }
    
    $ds = $da->ExecSQLs($sqls, $all_params);
    
    return $ds;      
  }
  
  //取评论
  public function getReply($da, $user, $conv_root_id, $FILE_WEBSERVER_URL, $pageindex, $pagesize) 
  {
    $sqls = array();
    $all_params = array();
    
    $sql = "select a.conv_id, a.login_account, a.post_date, a.conv_type_id, a.conv_root_id, a.conv_content, 
  a.post_to_group, a.post_to_circle, a.reply_to, a.copy_num, a.reply_num, a.atten_num, a.like_num, a.comefrom,
  b.nick_name, b.photo_path, concat('$FILE_WEBSERVER_URL', ifnull(b.photo_path, '')) photo_url, b.photo_path_small, b.photo_path_big, 
  f_cal_date_section(a.post_date) post_date_d, ep.eshortname, rp.nick_name reply_to_name, wsa.atten_id, 
  wsa.atten_date, f_cal_date_section(wsa.atten_date) atten_date_d, cf.name comefrom_d
from we_convers_list a
inner join we_staff b on a.login_account=b.login_account
inner join we_enterprise ep on b.eno=ep.eno
left  join we_staff rp on rp.login_account=a.reply_to
left  join we_staff_atten wsa on wsa.login_account=? and wsa.atten_type='02' and wsa.atten_id=a.conv_id
left  join we_code_all_code cf on cf.id=a.comefrom and cf.class='来自于'
where a.conv_root_id=? and a.conv_id<>a.conv_root_id
order by (conv_id = conv_root_id) desc, (0+conv_id) desc";
    $params = array();
    $params[] = (string)$user->getUserName();
    $params[] = (string)$conv_root_id;
    
    $pagestart = ($pageindex-1)*$pagesize;
    $sql .= " limit $pagestart, $pagesize ";
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    $sql = "select a.reply_num
from we_convers_list a
where a.conv_id=?";
    $params = array();
    $params[] = (string)$conv_root_id;
          
    $sqls[] = $sql;
    $all_params[] = $params;
    
    $ds = $da->GetDatas(array("we_convers_list", "reply_num"), $sqls, $all_params);
    
    if ($ds["reply_num"]["recordcount"] > 0) $ds["we_convers_list"]["recordcount"] = $ds["reply_num"]["rows"][0]["reply_num"];
    
    //取like
    $sql = "call p_we_getlikes ( ? , ? , ? , ? , ? , ? , ? , ? );";
    $params = array();
    $params[] = (string)$user->getUserName();    
    $params[] = "";        
    $params[] = "";
    $params[] = "";        
    $params[] = "";        
    $params[] = "";
    $params[] = "";
    $params[] = "";
    $i = 0;
    $rowcount = count($ds["we_convers_list"]["rows"]);
    for ($j = 1; $i < $rowcount && $j <= 6; $i++, $j++ )
    {
      $params[$j] = $ds["we_convers_list"]["rows"][$i]["conv_id"];
    }
    $ds1 = $da->GetData("we_convers_like", $sql, $params);
    $ds["we_convers_like"] = $ds1["we_convers_like"];
    
    while ($i < $rowcount)
    {
        $sql = "call p_we_getlikes ( ? , ? , ? , ? , ? , ? , ? , ? );";
        $params = array();
        $params[] = (string)$user->getUserName();    
        $params[] = "";        
        $params[] = "";
        $params[] = "";        
        $params[] = "";        
        $params[] = "";
        $params[] = "";
        $params[] = "";
      for ($j = 1; $i < $rowcount && $j <= 6; $i++, $j++ )
      {
        $params[$j] = $ds["we_convers_list"]["rows"][$i]["conv_id"];
      }
      $ds1 = $da->GetData("we_convers_like", $sql, $params);
      $ds["we_convers_like"]["rows"] = array_merge($ds["we_convers_like"]["rows"], $ds1["we_convers_like"]["rows"]);
    }
    
    //取reply attach   
    $whereWH = "'x'";
    $params = array(); 
    for ($i=0; $i < count($ds["we_convers_list"]["rows"]); $i++) {
      $whereWH .= ",?";
      $params[$i] = $ds["we_convers_list"]["rows"][$i]["conv_id"];
    } 
    $sql = "select a.conv_id, a.attach_id, b.file_name, b.file_ext, b.up_by_staff, b.up_date
from we_convers_attach a, we_files b
where a.attach_id=b.file_id
  and a.attach_type='0' and a.conv_id in ($whereWH)";
    $ds1 = $da->GetData("we_convers_attach_reply", $sql, $params);
    $ds["we_convers_attach_reply"] = $ds1["we_convers_attach_reply"];
      
		//删除评论我的通知
		$da->ExecSQL("delete from we_notify 
where notify_type='04' and notify_staff=? 
  and exists(select 1 from we_convers_list where we_convers_list.conv_id=we_notify.msg_id and we_convers_list.conv_root_id=?)", 
		  array((string)$user->getUserName(), (string)$conv_root_id));
    
    return $ds;
  }
  
  //取转发列表
  public function getCopyList($da, $user, $conv_root_id, $FILE_WEBSERVER_URL, $pageindex, $pagesize) 
  {
    $sqls = array();
    $all_params = array();
    
    $sql = "select a.conv_id, a.login_account, a.post_date, a.conv_type_id, a.conv_root_id, a.conv_content, 
  a.post_to_group, a.post_to_circle, a.reply_to, a.copy_num, a.reply_num, a.atten_num, a.like_num, a.comefrom,
  b.nick_name, b.photo_path, concat('$FILE_WEBSERVER_URL', ifnull(b.photo_path, '')) photo_url, b.photo_path_small, b.photo_path_big, 
  f_cal_date_section(a.post_date) post_date_d, ep.eshortname, cf.name comefrom_d
from we_convers_list a
inner join we_staff b on a.login_account=b.login_account
inner join we_enterprise ep on b.eno=ep.eno
left  join we_code_all_code cf on cf.id=a.comefrom and cf.class='来自于'
where ? in (a.copy_id, a.copy_last_id) and a.conv_id=a.conv_root_id
order by (0+conv_id) desc";
    $params = array();
    $params[] = (string)$conv_root_id;
    
    $pagestart = ($pageindex-1)*$pagesize;
    $sql .= " limit $pagestart, $pagesize ";
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    $sql = "select a.copy_num
from we_convers_list a
where a.conv_id=?";
    $params = array();
    $params[] = (string)$conv_root_id;
          
    $sqls[] = $sql;
    $all_params[] = $params;
    
    $ds = $da->GetDatas(array("we_convers_list", "copy_num"), $sqls, $all_params);
    
    if ($ds["copy_num"]["recordcount"] > 0) $ds["we_convers_list"]["recordcount"] = $ds["copy_num"]["rows"][0]["copy_num"];
    
    return $ds;
  }
  //关于会话置顶
  public function convTop($da,$conv_id='',$seconds=0){
    $sql="select 1 from we_conv_top where conv_id=?";
    $params=array($conv_id);
    $ds=$da->Getdata("conv_id",$sql,$params);
    $count=$ds["conv_id"]["recordcount"];
    if($count==0){
  	  $sql="insert into we_conv_top (conv_id,timeout) values(?,now()+interval ? DAY)";
  	  $params=array($conv_id,$seconds);
    }
    else{
    	$sql="update we_conv_top set timeout=(now()+interval ? DAY) where conv_id=?";
    	$params=array($seconds,$conv_id);
    }
  	return $da->ExecSQL($sql,$params);
  }
  public function convCancelTop($da,$conv_id){
  	$sql="delete from we_conv_top where conv_id=?";
  	$params=array($conv_id);
  	return $da->ExecSQL($sql,$params);
  }
  //关于隐藏会话
  public function convHide($da,$conv_id,$login_account){
  	$sql="insert into we_conv_hide (conv_id,login_account) values(?,?)";
  	$params=array($conv_id,$login_account);
  	return $da->ExecSQL($sql,$params);
  }
  public function convCancelHide($da ,$conv_id,$login_account){
  	$sql="delete from we_conv_hide where conv_id=? and login_account=?";
  	$params=array($conv_id,$login_account);
  	return $da->ExecSQL($sql,$params);
  }
  
  //发布广播
  public function Broadcast($da,$da_im,$user,$conv_id,$conv_type_id,$conv_content,$post_to_group,$post_to_circle,$fileids,$comefrom="00",$container=null) 
  {
      $sqls = array();
      $all_params = array();
      $im_sqls = array();
      $im_params = array();      
      $login_account = is_array($user)?$user["login_account"]:$user->getUserName();
      $sqlInsert = 'insert into we_convers_list (conv_id,login_account,post_date,conv_type_id,conv_root_id,conv_content,post_to_group,post_to_circle,copy_num,reply_num,comefrom) 
                    values(?,?,CURRENT_TIMESTAMP(),?,?,?,?,?,0,0,?)';
      $params = array();
      $params[] = (string)$conv_id;
      $params[] = (string)$login_account;
      $params[] = (string)$conv_type_id;
      $params[] = (string)$conv_id;
      $params[] = (string)$conv_content;
      $params[] = (string)$post_to_group;
      $params[] = (string)$post_to_circle;
      $params[] = (string)$comefrom;
      $sqls[] = $sqlInsert;
      $all_params[] = $params;
      //存储接收动态的部门或人员id
      $sql = "select objid,type from we_announcer where login_account=?;";
      $ds = $da->GetData("table",$sql,array((string)$login_account));
      if ( $ds && $ds["table"]["recordcount"]>0)
      {
          for ($i=0; $i< $ds["table"]["recordcount"];$i++)
          {
            $rows = $ds["table"]["rows"][$i];
            $objid = $rows["objid"];
            $type = $rows["type"];
            $sqlInsert = "insert into im_convers_announcer(conv_id,objid,type)values(?,?,?);";
            $params = array((string)$conv_id,(string)$objid,(string)$type);
            $im_sqls[] = $sqlInsert;
            $im_params[] = $params;
          }
      }
      //上传的文件      
      for ($i=0; $i<count($fileids); $i++)
      {
        $sqlInsert = "insert into we_convers_attach (conv_id, attach_type, attach_id) values (?, '0', ?)";
        $params = array();
        $params[] = (string)$conv_id;
        $params[] = (string)$fileids[$i];              
        $sqls[] = $sqlInsert;
        $all_params[] = $params;
      }
      $success = true;
      try
      {
        $da->ExecSQLs($sqls, $all_params);
        $da_im->ExecSQLs($im_sqls,$im_params);
      }
      catch(\Exception $e)
      {
        $success = false;
        $container->get("logger")->err($e->getMessage());
      }
      return $success;
            
  }  
}
