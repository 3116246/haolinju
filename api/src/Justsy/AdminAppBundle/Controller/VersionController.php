<?php

namespace Justsy\AdminAppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;
use Justsy\BaseBundle\DataAccess\SysSeq;

class VersionController extends Controller
{
    public function indexAction() 
    {
        $url = $this->container->getParameter('open_api_url');
        $url = str_replace("https","http",$url);
        return $this->render('JustsyAdminAppBundle:Sys:Version.html.twig',array("download_url"=>$url));
    }
  
    //上传版本发布
    public function UploadFileAction()
    {
        $da = $this->get('we_data_access');
        $da_im = $this->get('we_data_access_im');
        $request = $this->getRequest();
        $content = $request->get("update_content");
        $version1 = $request->get("version_1");
        $version2 = $request->get("version_2");
        $version3 = $request->get("version_3");
        $version4 = $request->get("version_4"); 
        $version = $version1.".".$version2.".".$version3.".".$version4;
        $plist_url = $request->get("plist_url");
        $plist_url = empty($plist_url) ? null:$plist_url;
        $openid = $request->get('openid');
        $staffinfo = new \Justsy\BaseBundle\Management\Staff($da,$this->get("we_data_access_im"),$openid,$this->get("logger"),$this->container);
        $staffdata = $staffinfo->getInfo();    
        $login_account= $staffdata["login_account"];    
        $fileElementName = 'filedata';
        $success = true;$msg = "";
        try
        {
            $filename=$_FILES[$fileElementName]['name'];
            $filesize=$_FILES[$fileElementName]['size'];
            $filetemp=$_FILES[$fileElementName]['tmp_name'];
            //判断文件类型
            $file_name = basename($filename);
            $fixedType = explode(".",strtolower($file_name));
            $fixedType = $fixedType[count($fixedType)-1];
            $type = 0;
            if ( $fixedType=="apk")
                $type = 1;
            else if ($fixedType=="ipa")
                $type = 2;
            //比较版本号
            $sql="select max(replace(version,'.','')) version from we_version where type=?;";
            try
            {
                $ds=$da->GetData("table",$sql,array((string)$type));
                if ( $ds && $ds["table"]["recordcount"]>0)
                {
                    $old_ver = (int)$ds["table"]["rows"][0]["version"];
                    $new_ver = $version1.$version2.$version3.$version4;
                    $new_ver=(int)$new_ver;
                    if ( $new_ver<=$old_ver)
                    {
                        return Utils::WrapResultError("你输入的版本号比数据库里的小，请重新输入");
                    }
                }
            }
            catch(\Exception $e)
            {
            	return Utils::WrapResultError($e->getMessage());
            }
            $fileid = $this->saveCertificate($filetemp,$filename);
            if(!empty($fileid)) {  //上传文件成功后返回文件id
                $url = $this->container->getParameter('FILE_WEBSERVER_URL');
                $path = $_SERVER['DOCUMENT_ROOT']."/download/app";
                $dir = explode('src', __DIR__);
                if (!is_dir($dir[0].'/download/app'))
                {
                    mkdir($dir[0].'/download/app',0777,true); 
                }
                $path = $path."/";                
                $dowurl = $url.$fileid;
                $id = SysSeq::GetSeqNextValue($da,"we_version","id");
                $sql = "insert into we_version(id,version,type,update_content,filename,date,staffid,fileid,plist_url)values(?,?,?,?,?,now(),?,?,?)";
                $para = array((string)$id,(string)$version,(string)$type,(string)$content,(string)$file_name,(string)$login_account,(string)$fileid,$plist_url);
                try
                {
                    $da->ExecSQL($sql,$para);
                    //发送出席
                    $presence = new \Justsy\OpenAPIBundle\Controller\ApiController();
                    $presence->setContainer($this->container);
                    $my_jid = $staffdata['jid']; 
                    $sql = "select distinct us from global_session;";
                    $ds = $da_im->GetData("us",$sql);
                    $tojids = array();
                    $title = "";
                    if ( $type==1)
                        $title = "andorid";
                    else if ($type==2)
                        $title ="ios";
                    else 
                    	 $title = "pc";

                    $sendMessage = new \Justsy\BaseBundle\Common\SendMessage($da,$da_im);
                    $body = "有新版本(".$version."),请及时更新！";
                    $notice = Utils::WrapMessageNoticeinfo($body,'系统通知',null,null);
					$msg = json_encode(Utils::WrapMessage('newversion',array('type'=>$title),$notice));
					$parameter = array("eno"=>$staffdata['eno'],"flag"=>"all","title"=>"newversion","message"=>$msg,"container"=>$this->container);
					$sendMessage->sendImMessage($parameter);
                }
                catch(\Exception $e)
                {
                    $success = false;
                    $this->get("logger")->err($e->getMessage());
                    $msg = "添加数据记录失败！";	
                    return Utils::WrapResultError($e->getMessage());    	 	 
                }
            }
            else
            {
                $success = false;
                $msg = "上传安装包文件失败";
                return Utils::WrapResultError($msg);
            }
        }
        catch(\Exception $e)
        {
            $success = false;
            $msg = "上传安装包失败。";
            $this->get("logger")->err($e->getMessage());
            return Utils::WrapResultError($e->getMessage());
        }
        //删除上传的文件		
        @unlink($_FILES[$fileElementName]);
        return Utils::WrapResultOK("");
    }

    //删除安装包
    public function DelVersionAction()
    {
        $da = $this->get("we_data_access");
        $request = $this->getRequest();
        $id = $request->get("id");
        //删除文件
        $sql = "select fileid from we_version where id=?";
        try
        {
            $ds = $da->GetData("t",$sql,array((string)$id));
            if ( $ds && $ds["t"]["recordcount"]>0){
                $fileid = $ds["t"]["rows"][0]["fileid"];
            if ( !empty($fileid))
                $this->deleteFile($fileid);
            }
        }
        catch(\Exception $e){
            $this->get("logger")->err($e->getMessage());
            return Utils::WrapResultError($e->getMessage());
        }
        $success = true;
        $msg = "";
        //删除数据记录
        $sql = "delete from we_version where id=?";
        try
        {
            $da->ExecSQL($sql,array((string)$id));
        }
        catch (\Exception $e){
            $success = false;
            $msg = "删除记录失败！";
            $this->get("logger")->err($e->getMessage());
            return Utils::WrapResultError($e->getMessage());
        }
        return Utils::WrapResultOK("");
    }

    //保存mongo文件
    protected function saveCertificate($filetemp,$filename)
    {
        $result = array();
        try
        {
            $upfile = tempnam(sys_get_temp_dir(), "we");
            unlink($upfile);
            if(move_uploaded_file($filetemp,$upfile))
            {
                $dm = $this->get('doctrine.odm.mongodb.document_manager'); 
                $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
                $doc->setName($filename);
                $doc->setFile($upfile);
                $dm->persist($doc);
                $dm->flush();
                $fileid = $doc->getId();
                return $fileid;
            }
            else
            {
                return "";
            }
        }
        catch(\Exception $e)
        {
            $this->get('logger')->err($e);
            $result = array("fileid"=>"","filepath"=>"");
            return "";
        }
    }

    //将文件保存到mogo
    private function saveFile($filename)
    {
        $fileid = "";
        try
        {
            if (!empty($filename) && file_exists($filename))
            { 
                $newfile = sys_get_temp_dir()."/".basename($filename);
                if (rename($filename,$newfile))
                {			    
                    //进行mongo操作
                    $dm = $this->get('doctrine.odm.mongodb.document_manager'); 
                    $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
                    $doc->setName(basename($newfile));
                    $doc->setFile($newfile);
                    $dm->persist($doc);
                    $dm->flush();
                    $fileid = $doc->getId();
                    if (file_exists($filename))
                    unlink($filename);
                }
            }
        }
        catch(\Exception $e){
            $this->get("logger")->err($e);
            $fileid = "";
        }
        return $fileid;
    }  

    //删除mongo文件
    private function deleteFile($fileid)
    {
        $result = true;
        try
        {
        if (!empty($fileid)) 
        {
            $dm = $this->get('doctrine.odm.mongodb.document_manager');
            $doc = $dm->getRepository('JustsyMongoDocBundle:WeDocument')->find($fileid);
            if(!empty($doc))
            {
                $dm->remove($doc);
                $dm->flush();
            }
        }
        }
        catch(\Exception $e){
            $result = false;
            $this->get("logger")->err($e->getMessage());
        }
        return true;
    }  
 
    //查询返回的数据
    public function SearchVersionAction()
    {
        $da = $this->get("we_data_access");
        $request = $this->getRequest();
        $pageindex = (int)$request->get("pageindex");
        $pagenumber = (int)$request->get("record");
        $success = true;
        $msg = "";
        $limit = " limit ".(($pageindex - 1) * $pagenumber).",".$pagenumber;
        $para = array();  	 
        $url = $this->container->getParameter('FILE_WEBSERVER_URL');  	 
        $sql ="select a.id,version,case type when 0 then 'PC版' when 1 then 'Android版' when 2 then 'IOS版' end apptype,update_content,
                   concat('$url',fileid) down_url,date_format(date,'%Y-%m-%d %H:%i') date,nick_name
            from we_version a inner join we_staff b on staffid=login_account order by date desc,type desc ".$limit;
        try
        {
            $ds = $da->GetData("table",$sql,$para);
        }
        catch(\Exception $e)
        {
            $this->get("logger")->err($e->getMessage());
            return Utils::WrapResultError($e->getMessage());
        }
        $data = $ds["table"]["rows"];
        $recordcount = 0;
        if ( $pageindex==1){  //如果为第一页时返回记录总数
        $sql = " select count(*) recordcount from we_version;";
            if ( count($para)>0)
                $ds = $da->GetData("table",$sql,$para);
            else
                $ds = $da->GetData("table",$sql);
            if ( $ds && $ds["table"]["recordcount"]>0)
                $recordcount = $ds["table"]["rows"][0]["recordcount"];
        }
        return Utils::WrapResultOK($data);
    }
}