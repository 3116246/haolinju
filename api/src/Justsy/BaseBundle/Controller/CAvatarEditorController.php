<?php

namespace Justsy\BaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

//头像编辑
//将会在Session中存储 avatar_big－大头像文件地址 avatar_middle－中头像文件地址 avatar_small－小头像文件地址
//使用完后，请删除这三个session及对应文件  
class CAvatarEditorController extends Controller
{
  public function indexAction($name)
  {
    return $this->render('JustsyBaseBundle:CAvatarEditor:avatareditor.html.twig', array('this' => $this));
  }
    
  public function saveAvatarAction() 
  {
    $request = $this->getRequest();
    
    $tmpfname = tempnam(sys_get_temp_dir(), "we");
    unlink($tmpfname);
    $tmpfname = str_replace(strrchr($tmpfname, "."), "", $tmpfname);
    $filename120 = $tmpfname."_120.png"; 
    $filename48 = $tmpfname."_48.png"; 
    $filename24 = $tmpfname."_24.png";   
    $somecontent1 = base64_decode($request->get('png1'));   
    $somecontent2 = base64_decode($request->get('png2'));  
    $somecontent3 = base64_decode($request->get('png3'));      
    if ($handle = fopen($filename120, "w+")) {   
       if (!fwrite($handle, $somecontent1) == FALSE) {   
           fclose($handle);  
       }  
    }  
    if ($handle = fopen($filename48, "w+")) {   
       if (!fwrite($handle, $somecontent2) == FALSE) {   
           fclose($handle);  
       }  
    } 
    if ($handle = fopen($filename24, "w+")) {   
       if (!fwrite($handle, $somecontent3) == FALSE) {   
           fclose($handle);  
       }  
    }

    $this->get('session')->set("avatar_big", $filename120);
    $this->get('session')->set("avatar_middle", $filename48);
    $this->get('session')->set("avatar_small", $filename24);

    $response = new Response("imageurl=".$filename120);
    return $response;
  }
  
  public function getFileInfoAction($size)
  {
  	 $session = $this->get('session');
  	 $src = $session->get("avatar_middle"); //默认返回48
     if($size=="120") $src= $session->get("avatar_big");
     elseif($size=="48") $src = $session->get("avatar_middle");
     elseif($size=="24") $src = $session->get("avatar_small"); 
     $data = file_get_contents($src);
     $path_parts = pathinfo($src); 
     $ftype=$path_parts['extension'];
     $f=array('gif' => 'image/gif',
'jpeg' => 'image/jpeg',
'jpg' => 'image/jpeg',
'jpe' => 'image/jpeg',
'png' => 'image/png');
      $response = new Response($data);
      $response->headers->set('Content-Type', $f[$ftype]);
     return $response;       
  }
  
}
