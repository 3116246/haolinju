<?php

require __DIR__.'/../vendor/swiftmailer/lib/swift_required.php';
require __DIR__.'/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;

$paras = parse_ini_file('../app/config/parameters.ini', true);
$dbserver = $paras['parameters']['database_host'];
$dbname = $paras['parameters']['database_name'];
$dbuser = $paras['parameters']['database_user'];
$dbpassword = $paras['parameters']['database_password'];
$mailer_host = $paras['parameters']['mailer_host'];
$mailer_user = $paras['parameters']['mailer_user'];
$mailer_password = $paras['parameters']['mailer_password'];

$logger = new Logger('wefafa_mails');
$logger->pushHandler(new StreamHandler(__DIR__.'/wefafa_mails.log', Logger::DEBUG));
$logger->pushHandler(new FirePHPHandler());

while(true)
{
  $conn = mysql_connect($dbserver, $dbuser, $dbpassword);
  if (!$conn)
  {
    sleep(5);
    continue;
  }
  
  mysql_select_db($dbname, $conn);
  
  $sql = "SET NAMES 'utf8'";
  mysql_query($sql, $conn);
  
  $transport = \Swift_SmtpTransport::newInstance($mailer_host)
    ->setUsername($mailer_user)
    ->setPassword($mailer_password);
  $mailer = \Swift_Mailer::newInstance($transport);
  
  while(true)
  {
    $sql = "select a.*, b.nick_name from we_mails a left join we_staff b on b.login_account=a.send_email where a.is_send='0'";
    $result = mysql_query($sql, $conn);
    
    if (mysql_num_rows($result) == 0) break;

    while ($row = mysql_fetch_array($result)) 
    {
      $invite_recv_email = $row["recv_email"];
      $inv_content = $row["content"];
      $inv_title = $row["title"];
      $remark = $row["remark"];
      try
      {
      	if($remark=="business-message")
      	{
      		//业务邮件以消息发送人真实身份发送邮件
      		$send_email = $row["send_email"];
      		$send_name = $row["nick_name"];
          $mailtext = \Swift_Message::newInstance()
          ->setSubject($inv_title)
          ->setFrom(array($send_email => $send_name))
          ->setTo($invite_recv_email)
          ->setContentType('text/html')
          ->setBody($inv_content);      		
      	}
      	else
      	{
          $mailtext = \Swift_Message::newInstance()
          ->setSubject($inv_title)
          ->setFrom(array($mailer_user => 'Wefafa'))
          ->setTo($invite_recv_email)
          ->setContentType('text/html')
          ->setBody($inv_content);
        }
        $pos = strpos($inv_title, "邀请您加入");
        if ($inv_title=="微发发激活邮件" || $inv_title=="邀请加入微发发" || $inv_title=="欢迎加入WeFaFa"
          || $inv_title=="欢迎加入Wefafa企业协作网络" || $pos!==false)
        {
          $mailtext->setBcc("qiyb@fafatime.com");
          $mailtext->setReadReceiptTo("noreply@wefafa.com");
        }
        $mailer->send($mailtext);
      }
      catch(\Exception $e)
      {
        $logger->err($e);
      }
      
      $sql = "update we_mails set is_send='1',send_date=now() where id='".$row["id"]."' and is_send='0'";
      mysql_query($sql, $conn);
      sleep(1);
    }
  }
  
  mysql_close($conn);
  sleep(30);
}