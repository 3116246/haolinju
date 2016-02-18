<?php
$paras = parse_ini_file('../app/config/parameters.ini', true);

$dbserver = $paras['parameters']['database_host'];
$dbname = $paras['parameters']['database_name'];
$dbuser = $paras['parameters']['database_user'];
$dbpassword = $paras['parameters']['database_password'];
$cwsurl = "http://localhost:1985";

function getUrlContent($url, $data) 
{ 
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT,10);
  curl_setopt($ch, CURLOPT_FORBID_REUSE, true); //处理完后，关闭连接，释放资源
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  $content = curl_exec($ch);
  return $content;
}

while(true)
{
  $conn = mysql_connect($dbserver, $dbuser, $dbpassword);
  if (!$conn)
  {
    sleep(5);
    continue;
  }
  
  //读lastconvid
  $file_lastconvid = __DIR__."/lastconvid.txt";
  $lastconvid = "0";
  if(file_exists($file_lastconvid))
  {
    $lastconvid = (float)file_get_contents($file_lastconvid);
  }
  
  mysql_select_db($dbname, $conn);
  
  //如果lastconvid=0，重建索引表
  if ($lastconvid == 0)
  {
    $sql = "truncate table we_convers_index";
    mysql_query($sql, $conn);
  }
  
  $sql = "SET NAMES 'utf8'";
  mysql_query($sql, $conn);
  
  while(true)
  {
    //读取100条信息
    $sql = "select conv_id, post_to_circle circle_id, conv_content from we_convers_list where conv_id=conv_root_id and conv_id > $lastconvid order by 0+conv_id limit 0, 100";
    $result = mysql_query($sql, $conn);
    
    if (mysql_num_rows($result) == 0) break;

    while ($row = mysql_fetch_array($result)) 
    {  
      //分词
      $conv_content = $row["conv_content"];
      $conv_content = iconv("UTF-8", "GBK//IGNORE", $conv_content); 
      $tokenstr = getUrlContent($cwsurl, urlencode($conv_content));
      $tokenstr = iconv("GBK", "UTF-8//IGNORE", $tokenstr);
      $tokenarr = explode(" ", $tokenstr);
      
      //过滤一些
      $tokenarr = array_unique(array_map(function ($item) { return trim($item); }, $tokenarr));      
      $filterstr = "'\"\\/,.!！＂#$%&()*+-_={}[]＃￥％＆｀＇（）〔〕〈〉《》「」『』〖〗【】．＊＋，－．。、？…—·ˉˇ¨‘’“”々～‖∶／：；｜〃＜＝＞？＠［＼］＾＿｀｛｜｝￣";
      $tokenarr = array_filter($tokenarr, function ($item) use (&$filterstr)
      {
        return !empty($item) && strpos($filterstr, $item) === false;
      });    
      
      //写入索引表
      $conv_id = $row["conv_id"];
      $circle_id = $row["circle_id"];
      foreach ($tokenarr as &$token) 
      {
        $sql = "insert into we_convers_index(circle_id, token, conv_id) values('$circle_id', '$token', '$conv_id')";
        mysql_query($sql, $conn);  
      }
      
      $lastconvid = $row["conv_id"];
      
    }
    
    file_put_contents($file_lastconvid, $lastconvid);
  }
  
  mysql_close($conn);
  sleep(5);
}