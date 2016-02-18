<?php

namespace WebIM\ImChatBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class FileUpLoadController extends Controller
{    
    var $MgrObject;
    var $error='';
    var $Type;
    
    public function indexAction()
    {
    	 //获取请求域
    	 $request = $this->get("request");
    	 return $this->render("WebIMImChatBundle:Default:file.html.twig");
    }
    
    public function getFilePathAction()
    {
        $da = $this->get('we_data_access_im');
        $request = $this->get("request");
        $fileid = $request->get("fileid");
        $wefafa_domain=$this->container->getParameter('FAFA_WEFAFA_URL');  
        $sql = "select filepath from im_lib_files where fileid=?";	
        $rs = $da->GetData("f",$sql,array((string)$fileid));
        $result=array();
        $result["fileid"]=$fileid;
        $result["path"]="";
        if($rs && count($rs["f"]["rows"])>0)
        {
        	$id = $rs["f"]["rows"][0]["filepath"];
        	$doc = $this->get('doctrine.odm.mongodb.document_manager')->getRepository('WebIMImChatBundle:WeDocument')->find($id);
        	$filename = $doc->getFile()->getFileName();
        	$result["name"] = $filename;
        	$result["path"]=$wefafa_domain."/getfile/".$id;        	
        }
        
				$response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($result).");" : json_encode($result));
				$response->headers->set('Content-Type', 'text/json');
				return $response;	        
    }

    public function saveAction()
    {
        $id=strtotime("now");
        $request = $this->get("request");
        $c = $request->get("c");
        $fileid = $request->get("fileid");
        $r="";
        if($_FILES["fafa_webim_filedata"]["name"]!="")
        {
            $filesize=($_FILES["fafa_webim_filedata"]["size"]);
            $tmp_name = $_FILES['fafa_webim_filedata']['name'];
            //过滤特殊字符
            $chr = array("/&/","/#/","/\?/","/'/","/\"/","/</","/>/","/%/");
            $chr2 = array("＆","＃","？","＇","＂","＜","＞","％");
            $tmp_name = preg_replace($chr,$chr2,$tmp_name);
            $newid=$this->removeToMongodb(null,$_FILES['fafa_webim_filedata']['tmp_name'],$fileid,$tmp_name);
            /*
            $thispath = $_SERVER['DOCUMENT_ROOT']."/upload/";
            if (!file_exists($thispath)) 
                mkdir($thispath,0777); 
            $fix = pathinfo($tmp_name , PATHINFO_EXTENSION);
            $url = "$id.$fix";
           
            if(!move_uploaded_file($_FILES['fafa_webim_filedata']['tmp_name'] ,$thispath.$url))
            {
                if (!copy($_FILES['fafa_webim_filedata']['tmp_name'] ,$thispath.$url))
                {
                        $r=("{\"err\":\"$tmp_name\",\"msg\":\"\",\"fileid\":\"$fileid\"}");
                        return $this->redirect($c."?r=".$r);
                }
            }*/
            $newfileurl = $this->container->getParameter('FAFA_WEFAFA_URL')."/getfile/".$newid;
            
            $r= ("{\"err\":\"\",\"msg\":\"1\",\"oldfile\":\"$tmp_name\",\"newfile\":\"$newfileurl\",\"fileid\":\"$fileid\"}");
        }
        else
          $r= ("{\"err\":\"file is empty\",\"msg\":\"\",\"fileid\":\"$fileid\"}");
        return $this->redirect($c."?r=".$r);
    }
    
	  function removeToMongodb($login_account,$path,$hashID,$filename){
	   $dm = $this->get('doctrine.odm.mongodb.document_manager'); 
		 $doc = new \WebIM\ImChatBundle\Document\WeDocument();
		 $doc->setName($filename);
		 $doc->setFile($path); 
		 $dm->persist($doc);
		 $dm->flush();
		 $fileid = $doc->getId();	
		 unlink($path);		
		 try{
		 $sql ="insert into im_lib_files(fileid,filepath,savelevel,lastdate)values(?,?,'2',now())";
     $all_params=array((String)$hashID,(string)$fileid);
     $da = $this->get('we_data_access_im');
     $da->ExecSQL($sql,$all_params);
     }
     catch(\Exception $e){}
     return $fileid;
	}    
}
