<?php

namespace Justsy\AdminAppBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Management\Staff;
use Justsy\BaseBundle\Common\Utils;

//好友信息管理
class PortalController extends Controller
{    
    
    public function IndexAction()
    {
        $da = $this->get("we_data_access");
        $user=$this->get('security.context')->getToken()->getUser();
        $eno = $user->eno;
        $username=$user->getUsername();
        $request=$this->getRequest();
        $logo=$request->get("fileid");
        $crop=$request->get("crop");
        $type=$request->get("type");
        $index=$request->get("index");
        if ( empty($type))
        {
            $re = array("success"=>false,"msg"=>"请输入type参数值");
            $result = new Response(json_encode($re));
            $result->headers->set('Content-Type', 'text/json');
            return $result;
        }
        $width = 0;$height = 0;
        if ( $type==1)  //180*180
        {
            $width=180;
            $height=180;
        }
        else if ( $type==2) //400*130
        {
            $width=400;
            $height=130;
        }
        else if ( $type==3) //启动图片
        {
            $width = 640;
            $height = 1136;
        }    
        if(!empty($crop))
        {
        	 $crop = json_decode($crop,true);
        }
        $appid=$request->get("appid");
        $success = true;
        $newfileid = "";
        if(!empty($logo) && !empty($crop))
        {
        	//源图像另存为
        	 $doc = $this->get('doctrine.odm.mongodb.document_manager')
                  ->getRepository('JustsyMongoDocBundle:WeDocument')
                  ->find($logo);
            if (!empty($doc))
            {
                $filename1 = strtolower( $doc->getName());
            	  $expname =explode(".", $filename1);
            	  $expname = $expname[1];            	
            	  $src = tempnam(sys_get_temp_dir(), "tmp").".".$expname;
            	  $file = $doc->getFile();		    	
            	  $filename2 = $file->getFilename();
            	  $tybes = $file->getBytes();
        		    $cont =fopen($src,'w');
        		    fwrite($cont,$tybes);
        		    fclose($cont);        
        		    $gd = new \Justsy\BaseBundle\Common\Gd();
        		    $gd->open( $src );        		    
        		    if( $gd->is_image() )
        		    {
        		        $gd->crop((int)$crop["x"], (int)$crop["y"], (int)$crop["w"], (int)$crop["h"]);
        			      $gd->resize_to($width,$height, 'force');
        			      $gd->save_to($src);        
        			      $dm = $this->get('doctrine.odm.mongodb.document_manager');
            		    $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
            		    $doc->setName(basename($src));
            		    $doc->setFile($src);
            		    $dm->persist($doc);
            		    $dm->flush();
            		    $newfileid  = $doc->getId();
          		  }
        		    unlink($src);
        		    //数据记录操作处理
        		    $field = "";   		    
        		    if ( $type==1)
        		    {
        		        $sql="update we_apps_portalconfig set logo=? where appid=?";
        		        $field = "logo as fileid";
        		    }
        		    else if ( $type==2) //登录界面图片
        		    {
        		        $sql="update we_apps_portalconfig set login_image=? where appid=?";
        		        $field = "login_image as fileid";
        		    }
        		    else if ( $type==3) //启动图片
        		    {
        		        $sql="update we_apps_portalconfig set start_image=? where appid=?";
        		        $field = "start_image as fileid";
        		    }
        		    else if ( $type==4)
        		    {
        		        $sql="update we_apps_portalconfig set guide".$index."=? where appid=?";
        		        $field = "guide".$index." as fileid";
        		    }
                $params=array((string)$newfileid ,$appid);
                //记录原来文件id
                $remove_fileid ="";
                try
                {
                    $remove_sql = "select ".$field." from we_apps_portalconfig where appid=?;";
                    $ds = $da->GetData("table",$remove_sql,array((string)$appid));
                    if ( $ds && $ds["table"]["recordcount"]>0)
                       $remove_fileid = $ds["table"]["rows"][0]["fileid"];
                }
                catch(\Exception $e)
                {
                }
                $dm = $this->get('doctrine.odm.mongodb.document_manager');
                Utils::removeFile($logo,$dm);                
                try
                {
                    $da->ExecSQL($sql,$params);
                    //更改成功后删除原mogo文件
                    Utils::removeFile($remove_fileid,$dm);
                }
                catch(\Exception $e)
                {
                    $success = false;
                    $this->get("logger")->err($e->getMessage());                        
                }        
            }
        }
        $re = array("success"=>$success,"fileid"=>$newfileid);
        $result = new Response(json_encode($re));
        $result->headers->set('Content-Type', 'text/json');
        return $result;
    }
    
    /*    
    public function IndexAction()
    {
        return $this->render("JustsyAdminAppBundle:Sys:portal.html.twig");
    }
    */
}