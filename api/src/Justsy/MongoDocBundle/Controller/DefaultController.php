<?php

namespace Justsy\MongoDocBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
      $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
      $doc->setName("InitOracleENV.sql");
      $doc->setFile("e:/ttttt/InitOracleENV.sql");
      
      $dm = $this->get('doctrine.odm.mongodb.document_manager');
      $dm->persist($doc);
      $dm->flush();
      
      return $this->render('JustsyMongoDocBundle:Default:index.html.twig', array('name' => $doc->getId()));
    }
    
    public function getFileInfo($id)
    {
      $doc = $this->get('doctrine.odm.mongodb.document_manager')
        ->getRepository('JustsyMongoDocBundle:WeDocument')
        ->find($id);
      if (!$doc) 
      {
        return null;
      }
      $filename = $doc->getName();      
      return array('filename'=>$filename,'size'=>$doc->getLength());
    }
    
    //下载最新的android客户端安装包
    //由于android需要提供二维码，所以对外的下载地址必须要固定
    public function getAndroidSetup()
    {
        $da = $this->get("we_data_access");
        $sql = "select fileid from we_version where type=1 order by `date` desc limit 1;";
        $f = $da->GetData("data",$sql);
        $id = "";
        if($f["data"]["recordcount"]==0)
        {
        	 return new Response("", 404); 
        }
        $id = $f["data"]["rows"]["0"]["fileid"];
        if(empty($id))
        {
        	 return new Response("", 404); 
        }
        $doc = $this->get('doctrine.odm.mongodb.document_manager')
                    ->getRepository('JustsyMongoDocBundle:WeDocument')
                    ->find($id);
        if (!$doc) 
        {
            //throw $this->createNotFoundException('No file found for id '.$id);
            return new Response("", 404); 
        }
        $filename = $doc->getName();
        if(isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER["HTTP_USER_AGENT"], "MSIE")) $filename = urlencode($filename);
        $finfo = new \finfo(FILEINFO_MIME);
        $response = new Response($doc->getFile()->getBytes());
        $response->headers->set('Content-Type', "application/octet-stream");
        $response->headers->set('Accept-Ranges','bytes');
        $response->headers->set('Content-Length',$doc->getLength());
        $response->headers->set('Content-Disposition','size='.$doc->getLength().'; filename="'.$filename.'"');
        return $response;
    }

    //获得最新IOS安装包
    public function getIosSetup()
    {
        $da = $this->get("we_data_access");
        $sql = "select fileid from we_version where type=2 order by `date` desc limit 1;";
        $f = $da->GetData("data",$sql);
        $id = "";
        if($f["data"]["recordcount"]==0)
        {
        	 return new Response("", 404); 
        }
        $id = $f["data"]["rows"]["0"]["fileid"];
        if(empty($id))
        {
        	 return new Response("", 404); 
        }
        $doc = $this->get('doctrine.odm.mongodb.document_manager')
                    ->getRepository('JustsyMongoDocBundle:WeDocument')
                    ->find($id);
        if (!$doc) 
        {
            //throw $this->createNotFoundException('No file found for id '.$id);
            return new Response("", 404); 
        }
        $filename = $doc->getName();
        if(isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER["HTTP_USER_AGENT"], "MSIE")) $filename = urlencode($filename);
        $finfo = new \finfo(FILEINFO_MIME);
        $response = new Response($doc->getFile()->getBytes());
        $response->headers->set('Content-Type', "application/octet-stream");
        $response->headers->set('Accept-Ranges','bytes');
        $response->headers->set('Content-Length',$doc->getLength());
        $response->headers->set('Content-Disposition','size='.$doc->getLength().'; filename="'.$filename.'"');
        return $response;    
    }

    public function getPcSetup()
    {
        	$da = $this->get("we_data_access");
        	$sql = " select * from we_download where filetype='PC' order by publishdate desc limit 0,1";
        $f = $da->GetData("data",$sql,array());
        $id = "";
        if(count($f["data"]["rows"])==0)
        {
        	 return new Response("", 404); 
        }
        $id = $f["data"]["rows"]["0"]["fileid"];
        if(empty($id))
        {
        	 return new Response("", 404); 
        }
        $doc = $this->get('doctrine.odm.mongodb.document_manager')
        ->getRepository('JustsyMongoDocBundle:WeDocument')
        ->find($id);
      if (!$doc) 
      {
        //throw $this->createNotFoundException('No file found for id '.$id);
        return new Response("", 404); 
      }
      $filename = $doc->getName();
      if(isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER["HTTP_USER_AGENT"], "MSIE")) $filename = urlencode($filename);
       
      $finfo = new \finfo(FILEINFO_MIME);
      $response = new Response($doc->getFile()->getBytes());
      $response->headers->set('Content-Type', "application/octet-stream");
      $response->headers->set('Accept-Ranges','bytes');
      $response->headers->set('Accept-Length',$doc->getLength());
      $response->headers->set('Content-Disposition','size='.$doc->getLength().'; filename="'.$filename.'"');
      return $response;        
    }
    
    public function downFileAction($id)
    {
      $doc = $this->get('doctrine.odm.mongodb.document_manager')
        ->getRepository('JustsyMongoDocBundle:WeDocument')
        ->find($id);
      if (!$doc) 
      {
        //throw $this->createNotFoundException('No file found for id '.$id);
        return new Response("", 404); 
      }
      $filename = $doc->getName();
      if(isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER["HTTP_USER_AGENT"], "MSIE")) $filename = urlencode($filename);
       
      $finfo = new \finfo(FILEINFO_MIME);
      $response = new Response($doc->getFile()->getBytes());
      $response->headers->set('Content-Type', "application/octet-stream");
      $response->headers->set('Accept-Ranges','bytes');
      $response->headers->set('Accept-Length',$doc->getLength());
      $response->headers->set('Content-Disposition','attachment; filename=\"'.$filename.'\"');
      return $response;
    }
   
    public function getFileAction($id)
    {    		
    	if($id=="androidsetup")
    	{
    	  return  $this->getAndroidSetup();
    	}
      if($id=="iossetup")
      {
        return  $this->getIosSetup();
      }      
    	if($id=="pcsetup")
    	{
    	  return  $this->getPcSetup();
    	}
      //缓存
      if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']))
      {
        $response = new Response("", 304);      
        $response->headers->set('Last-Modified', $_SERVER['HTTP_IF_MODIFIED_SINCE']);
        return $response;
      }
      
      if (empty($id) || $id == "0" || $id == 0)
      {
        return new Response("not found file", 404); 
      }
      
      $doc = $this->get('doctrine.odm.mongodb.document_manager')
        ->getRepository('JustsyMongoDocBundle:WeDocument')
        ->find($id);

      if (!$doc) 
      {
        //throw $this->createNotFoundException('No file found for id '.$id);
        return new Response("", 404); 
      }
      $request=$this->get("request");
      //获取返回形式：流和文本（需注意转换成utf-8）
      $r_f = $request->get("r_f");
      $filename = $doc->getName();
      if(isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER["HTTP_USER_AGENT"], "MSIE")) $filename = urlencode($filename);
       
      $finfo = new \finfo(FILEINFO_MIME);
      $doc_content = $doc->getFile()->getBytes();
      if(!empty($r_f) && $r_f=="text")
      {
      	 	//$doc_content=array_map('chr',$doc_content);   
				  //$str=implode('',$doc_content);  
				  $c = $this->CheckC($doc_content);
				  if($c===false)//未知编码
				  {
				  }
				  else
				  {
						  if($c && $c!="utf-8")
		    			   $str = iconv($c,"utf-8//ignore",$doc_content);  
		    			else $str =     $doc_content;	  
		      	  $response = new Response($str);
		          $response->headers->set('Content-Type', 'text/plain');
		          return $response;
          }
      }

      //直接输出流
      $response = new Response($doc_content);      
      $response->headers->set('Content-Type', $finfo->buffer($doc_content));
      $response->headers->set('Content-Length', $doc->getLength());
      $response->headers->set('Content-Disposition', "attachment;size=".$doc->getLength().";filename=\"".($filename)."\"");

      if (strpos($filename, '.apk') !== false) 
      {
        $response->headers->set('Content-Type', "application/vnd.android.package-archive");
      }

      //缓存
      $validtime = 72 * 60 * 60;    // 72小时
      $response->setMaxAge($validtime);
      $response->setPublic();
      $response->headers->set('Expires', preg_replace('/.{5}$/', 'GMT', gmdate('r', time()+ $validtime)));
      $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s', time()).' GMT');
      
      return $response;
    }

    public function uploadfileAction()
    {
      $re=array('s'=>'1','m'=>'',"fileid"=>'',"filename"=>'');
      $fileElementName = "file";
      if(!empty($_FILES[$fileElementName]['error']))
      {
            switch($_FILES[$fileElementName]['error'])
            {

              case '1':
                $error = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
                break;
              case '2':
                $error = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
                break;
              case '3':
                $error = 'The uploaded file was only partially uploaded';
                break;
              case '4':
                $error = 'No file was uploaded.';
                break;

              case '6':
                $error = 'Missing a temporary folder';
                break;
              case '7':
                $error = 'Failed to write file to disk';
                break;
              case '8':
                $error = 'File upload stopped by extension';
                break;
              case '999':
              default:
                $error = 'No error code avaiable';
            }
      }else if(empty($_FILES[$fileElementName]['tmp_name']) || $_FILES[$fileElementName]['tmp_name'] == 'none')
      {
            $error = 'No file was uploaded..';
      }
      else
      {
              try
              {
                  $filename=$_FILES[$fileElementName]['name'];
                  $filesize=$_FILES[$fileElementName]['size'];
                  $filetemp=$_FILES[$fileElementName]['tmp_name'];
                  $dm = $this->get('doctrine.odm.mongodb.document_manager'); 
                  $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
                  $doc->setName($filename);
                  $doc->setFile($filetemp); 
                  $dm->persist($doc);
                  $dm->flush();
                  $fileid = $doc->getId();
                  @unlink($filetemp);
                  if(empty($fileid))
                  {
                      $re['s']='0';
                      $re['msg']='文件上传失败';
                  }
                  else
                  {
                      $re['fileid']=$fileid;
                      $re['filename']=$filename;
                  }
              }
              catch(\Exception $e){
              }              
      }
      $result =array("error"=>$error,"msg"=>$re['msg'],"s"=>$re["s"],"fileid"=>$re['fileid'],"filename"=>$re['filename']);
      $response = new Response(json_encode($result));
      $response->headers->set('Content-Type', 'text/json');
      return $response;            
    }    
    //根据文件id删除文件
    //参数可为文件id或文件id数组
    public static function removeFileAction()
    {
      $request=$this->get("request");
      $filelist=$this->get("filelist");
      $re = array();
      $re["returncode"] = "0000";
      if(empty($filelist))
      {           
          $re["returncode"] = "9999";
          $re["msg"] = "要删除的文件列表不能为空";
      } 
      else
      {
        $path = explode(",", $filelist);
        try
        {
          $dm = $this->get('doctrine.odm.mongodb.document_manager');
          $repo=$dm->getRepository('JustsyMongoDocBundle:WeDocument');
          for($i=0;$i< count($path);$i++)
          {
              $fileid = $path[$i];
              if ( !empty($fileid ))
              {
                    $doc = $repo->find($fileid);
                    if(!empty($doc)) 
                    {
                      $dm->remove($doc);
                    }
              }
          }
          $dm->flush();
        }
        catch(\Exception $err)
        {
          $re["returncode"] = "9999";
          $re["msg"] = $err->getMessage();
        }
      }
      $resp = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
      $resp->headers->set('Content-Type', 'text/json');
      return $resp;
    }
    
    public function getImageAction($sizemode, $id) 
    {
      if ($sizemode != "small" && $sizemode != "middle")
      {
        return $this->getFileAction($id);
      }
      else
      {
        $sql = "select file_id, file_id_small, file_id_middle from we_files_image where file_id=?";
        $params = array();
        $params[] = (string)$id;
        
        $da = $this->get("we_data_access");
        $ds = $da->GetData("we_files_image", $sql, $params);  
        if ($ds["we_files_image"]["recordcount"] == 0) return  $this->getFileAction($id);
        else return $this->getFileAction($ds["we_files_image"]["rows"][0][$sizemode == "small" ? "file_id_small" : "file_id_middle"]);
      }
    }
    
		function CheckC($str)
		{
				$array = array('ASCII','GBK','UTF-8');
				foreach ($array as $value)
				{
						if ($str === mb_convert_encoding(mb_convert_encoding($str, "UTF-32", $value), $value, "UTF-32"))
						return $value;
				}
				return false;
		}    
}
