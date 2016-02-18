<?php

namespace WebIM\ImMainBundle\Controller;

class Utils
{
  public static function getUrlContent($url) 
  { 
    /*$ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT,10);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, true); //处理完后，关闭连接，释放资源
    $content = curl_exec($ch);*/
    $content = file_get_contents($url);
    return $content;
  }
  
  //使用正则表达式判断Email地址是否合法的函数
  public static function validateEmail($email)
  {
    return preg_match("/^[a-z0-9]+[a-z0-9_-]*(\.[a-z0-9_-]+)*@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.([a-z]+){2,}$/",$email);
  }
  //发送及时消息
  public static function sendImMessage($url,$encode,$fromjid,$tojid,$message)
  {	
  	try{
    $regUrl = $url."/service.yaws?sendMsg=<$tojid><加入申请><$message><><审批>auth=$encode";
    $regUrl = json_encode(trim(file_get_contents($regUrl)));  	
    }
    catch(Exception $e)
    {}
	  return true;
  }
  
  public static function sendMail($mailer,$title,$send,$sendername,$target,$content)
  {
    $sender = empty($sendername)?"微发发":$sendername;
    //$send = $this->container->getParameter('mailer_user');
    $mailtext = \Swift_Message::newInstance()
      ->setSubject($title)
      ->setFrom(array($send=> $sender))
      ->setTo($target)
      ->setContentType('text/html')
      ->setBody($content);
    $mailer->send($mailtext);    	
  }
  
  //保存文件
  public static function  saveFile($path, $dm)
  {
    $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
    $doc->setName(basename($path));
    $doc->setFile($path);
    $dm->persist($doc);
    $dm->flush();
    unlink($path);
    return $doc->getId();
  }
}