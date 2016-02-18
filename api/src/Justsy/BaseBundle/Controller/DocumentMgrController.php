<?php
namespace Justsy\BaseBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Justsy\BaseBundle\Common\Utils;

class DocumentMgrController extends Controller
{
	  var $network_domain;
	  var $docs;
	  var $scope_type,$scope_id,$groups_array,$circles_array;
	  public function indexAction($network_domain)
    {
    	$request=$this->get("request");
      $user = $this->get('security.context')->getToken()->getUser();
    	$this->network_domain = $network_domain;
			$para=array('curr_network_domain'=>$this->network_domain,
    	        'this'=> $this,
    	        'group_id' => $request->get("group_id"),
    	        'group_name' => $request->get("group_name"),"isdoc"=>false);
  	  if(in_array(\Justsy\BaseBundle\Management\FunctionCode::$DOC,$user->getFunctionCodes())){
		  	$para["isdoc"]=true;
		  }
    	return $this->render('JustsyBaseBundle:DocumentMgr:index.html.twig',$para);
    }
	  public function selectAction($network_domain)
    {   	
    	return $this->render('JustsyBaseBundle:DocumentMgr:select.html.twig',array('network_domain'=> $network_domain));
    }    
    

  public function fileuploadAction() 
  {
    $re = array("s" => 1);
    $request = $this->getRequest();
	  $user = $this->get('security.context')->getToken()->getUser();
    
    $filename = $request->get("filename");
    $circleid= $user->get_circle_id("");//获取企业圈子id
    $dirid = $request->get("dirid");
    
    try 
    {
      if (empty($filename) || empty($dirid) ) throw new \Exception("param is null");
      
      $upfile = tempnam(sys_get_temp_dir(), "we");
      unlink($upfile);
      $somecontent1 = base64_decode($request->get('filedata'));
      if ($handle = fopen($upfile, "w+")) {   
        if (!fwrite($handle, $somecontent1) == FALSE) {   
          fclose($handle);  
        }  
      }
      
      $isimage = preg_match(\Justsy\BaseBundle\Common\MIME::getMIMEImgReg(), strtolower($filename));
      $imagefilename_small = preg_replace("/(\.[^\.]*)$/", '_small\1', $filename);
      $imagefilename_middle = preg_replace("/(\.[^\.]*)$/", '_middle\1', $filename);
      $imagefilepath_small = $upfile.".".$imagefilename_small;
      $imagefilepath_middle = $upfile.".".$imagefilename_middle;
      
      $dm = $this->get('doctrine.odm.mongodb.document_manager'); 
      $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
      $doc->setName($filename);
      $doc->setFile($upfile); 
      $dm->persist($doc);
      $dm->flush();
      $fileid = $doc->getId();
      
      $fileid_small = "";
      $fileid_middle = "";
      if ($isimage)
      {
        $im = new \Imagick($upfile);
        if ($im->getImageWidth() > 100)
        {
          if ($im->getImageWidth() >= $im->getImageHeight())
            $im->scaleImage(100, 0);
          else
            $im->scaleImage(0, 100);
          $im->writeImage($imagefilepath_small);
          $im->destroy();
        }
        else
        {
          copy($upfile, $imagefilepath_small);
        }
        
        $im = new \Imagick($upfile);
        if ($im->getImageWidth() > 400)
        {
          $im->scaleImage(400, 0);
          $im->writeImage($imagefilepath_middle);
          $im->destroy();
        }
        else
        {
          copy($upfile, $imagefilepath_middle);
        }
        
        $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
        $doc->setName($imagefilename_small);
        $doc->setFile($imagefilepath_small); 
        $dm->persist($doc);
        $dm->flush();
        $fileid_small = $doc->getId();  
        unlink($imagefilepath_small);
        
        $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
        $doc->setName($imagefilename_middle);
        $doc->setFile($imagefilepath_middle); 
        $dm->persist($doc);
        $dm->flush();
        $fileid_middle = $doc->getId();  
        unlink($imagefilepath_middle);
      }
      
      unlink($upfile);
      
      //д��ݿ⣺we_files
      $da = $this->get("we_data_access");
      $fixs = explode(".",strtolower($filename));
      
      $sqls = array();
      $all_params = array();
      
      $sqls[] ="insert into we_files(`circle_id`,`file_ext`,`file_id`,`file_name`,`post_to_group`,`up_by_staff`,`up_date`,`dir`)values(?,?,?,?,?,?,now(),?)";
      $all_params[]=array((String)$circleid,(String)$fixs[count($fixs)-1],(String)$fileid,(String)$filename,'ALL',(String)$user->getUsername(),(string)$dirid);
      
      if ($isimage)
      {
        $sqls[] = "insert into we_files_image(file_id, file_id_small, file_id_middle) values(?, ?, ?)";
        $all_params[]=array((string)$fileid, (string)$fileid_small, (string)$fileid_middle);
      }
      $da->ExecSQLs($sqls, $all_params);
      $re["file_id"] = $fileid;
    } 
    catch (\Exception $e) 
    {
      $re["s"] = 0;
      $this->get('logger')->err($e);
    }
    
    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
    $response->headers->set('Content-Type', 'text/json');
    return $response;
  }    
    
	  public function uploadAction($network_domain)
    {
    	try{
        $request = $this->get("request");
        $user = $this->get('security.context')->getToken()->getUser();
        $circleId = $user->get_circle_id($network_domain);
        $uploadSourcePage = $request->get("uploadSourcePage");
        
        $upfile = $request->files->get("filedata");
        $isimage = preg_match(\Justsy\BaseBundle\Common\MIME::getMIMEImgReg(), strtolower($upfile->getClientOriginalName()));
        $imagefilename_small = preg_replace("/(\.[^\.]*)$/", '_small\1', $upfile->getClientOriginalName());
        $imagefilename_middle = preg_replace("/(\.[^\.]*)$/", '_middle\1', $upfile->getClientOriginalName());
        $imagefilepath_small = $upfile->getPathname().".".$imagefilename_small;
        $imagefilepath_middle = $upfile->getPathname().".".$imagefilename_middle;
        
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); 
        $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
        $filename = $upfile->getClientOriginalName();
        $doc->setName($filename);
        $doc->setFile($upfile->getPathname()); 
        $dm->persist($doc);
        $dm->flush();
        $fileid = $doc->getId();
        
        $fileid_small = "";
        $fileid_middle = "";
        if ($isimage)
        {
          $im = new \Imagick($upfile->getPathname());
          if ($im->getImageWidth() > 100)
          {
            if ($im->getImageWidth() >= $im->getImageHeight())
              $im->scaleImage(100, 0);
            else
              $im->scaleImage(0, 100);
            $im->writeImage($imagefilepath_small);
            $im->destroy();
          }
          else
          {
            copy($upfile->getPathname(), $imagefilepath_small);
          }
          
          $im = new \Imagick($upfile->getPathname());
          if ($im->getImageWidth() > 400)
          {
            $im->scaleImage(400, 0);
            $im->writeImage($imagefilepath_middle);
            $im->destroy();
          }
          else
          {
            copy($upfile->getPathname(), $imagefilepath_middle);
          }
          
          $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
          $doc->setName($imagefilename_small);
          $doc->setFile($imagefilepath_small); 
          $dm->persist($doc);
          $dm->flush();
          $fileid_small = $doc->getId();  
          unlink($imagefilepath_small);
          
          $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
          $doc->setName($imagefilename_middle);
          $doc->setFile($imagefilepath_middle); 
          $dm->persist($doc);
          $dm->flush();
          $fileid_middle = $doc->getId();  
          unlink($imagefilepath_middle);
        }
        
        unlink($upfile->getPathname());
        
        //写数据库：we_files
        $da = $this->get("we_data_access");
        $fixs = explode(".",strtolower($filename));
        $gorupid = $request->get("hpost_to_group");        
        $hpost_to_dir = $request->get("hpost_to_dir");
        if("ALL"==$gorupid) $hpost_to_dir="c".$circleId;//如果是通过发动态上传文件，且发布到圈子时，存储目录默认为圈子目录
        if(empty($hpost_to_dir))
        {
        	 $hpost_to_dir = strlen($gorupid)==0? "c".$circleId: 'g'.$gorupid;//如果没指定上传目录时，默认为当前圈子或者群组
        }
        $firstChar = substr($hpost_to_dir,0,1);
        if($uploadSourcePage=="dir" ){ //直接上传文档
        	//直接上传文档（uploadSourcePage为dir）到圈子/群组时，需要自动发布一条动态
	        if($firstChar!="c" && $firstChar!="g")
	        	 $uploadSourcePage="home"; //上传到子目录内时，不发布动态
        }
        $gorupid = strlen($gorupid)==0? "ALL":$gorupid;
        $sqls = array();
        $all_params = array();
        
        $sqls[] ="insert into we_files(`circle_id`,`file_ext`,`file_id`,`file_name`,`post_to_group`,`up_by_staff`,`up_date`,`dir`)values(?,?,?,?,?,?,now(),?)";
        $all_params[]=array((String)$circleId,(String)$fixs[count($fixs)-1],(String)$fileid,(String)$filename,(String)$gorupid,(String)$user->getUsername(),(string)$hpost_to_dir);
        if($uploadSourcePage==null || $uploadSourcePage!="home")
        {
             $conv_id = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_convers_list", "conv_id");
            
             $sqlInsert = 'insert into we_convers_list (conv_id, login_account, post_date, conv_type_id, conv_root_id, conv_content, post_to_group, post_to_circle, copy_num, reply_num) values (?, ?, CURRENT_TIMESTAMP(), ?, ?, ?, ?, ?, 0, 0)';
             $params = array();
             $params[] = (string)$conv_id;
             $params[] = (string)$user->getUserName();
             $params[] = (string)'00';
             $params[] = (string)$conv_id;
             $params[] = "上传了新文件";
             $params[] = (string)$gorupid;
             $params[] = (string)$circleId;
             $sqls[] = $sqlInsert;
             $all_params[] = $params;
            
              $sqlInsert = "insert into we_convers_attach (conv_id, attach_type, attach_id) values (?, '0', ?)";
              $params = array();
              $params[] = (string)$conv_id;
              $params[] = (String)$fileid;			              
              $sqls[] = $sqlInsert;
              $all_params[] = $params;
        }
        if ($isimage)
        {
          $sqls[] = "insert into we_files_image(file_id, file_id_small, file_id_middle) values(?, ?, ?)";
          $all_params[]=array((string)$fileid, (string)$fileid_small, (string)$fileid_middle);
        }
        $da->ExecSQLs($sqls, $all_params);
        //当上传到圈子/群组目录时，默认共享到该圈子/群组中
        if($firstChar=='c' || $firstChar=='g')
            $this->saveShare($fileid,"1",substr($hpost_to_dir,1),$firstChar,'r');
        $response = new Response(("{\"succeed\":1,\"fileid\":\"$fileid\"}"));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
		 }
     catch(\Exception $e)
     {
     	$this->get('logger')->err($e);
       $response = new Response(("{\"succeed\":0}"));
			 $response->headers->set('Content-Type', 'text/json');
			 return $response;
     }	
			     
    } 
    
    public function deleteAction(request $request)
    {
       $fileid = $request->get("fileid");
       $dm = $this->get('doctrine.odm.mongodb.document_manager');
       if (strlen($fileid) >0)
       {
       	 $da = $this->get("we_data_access");
         $table = $da->GetData("group","select file_id from we_files where file_id=?",array((String)$fileid));
         if ($table && $table["group"]["recordcount"] >0 )  //如果文件存在则删除
         {        
           $doc = $dm->getRepository('JustsyMongoDocBundle:WeDocument')->find($table["group"]["rows"][0]["file_id"]);
           $dm->remove($doc);
           $dm->flush();
           $da = $this->get("we_data_access");
           $da->ExecSQL("delete from we_files where file_id=?",array((String)$fileid));      
           //删除共享设置
           $da->ExecSQL("delete from we_doc_share where resourcetype='1' and resourceid=?",array((String)$fileid)); 
           //删除缩略图
           $ds = $da->GetData("we_files_image","select file_id, file_id_small, file_id_middle from we_files_image where file_id=?",array((String)$fileid));
           if ($ds["we_files_image"]["recordcount"] > 0)
           {
             $rep = $dm->getRepository('JustsyMongoDocBundle:WeDocument');
             
             $doc = $rep->find($ds["we_files_image"]["rows"][0]["file_id_small"]);
             $dm->remove($doc);
             $dm->flush();
             
             $doc = $rep->find($ds["we_files_image"]["rows"][0]["file_id_middle"]);
             $dm->remove($doc);
             $dm->flush();
             
             $da->ExecSQL("delete from we_files_image where file_id=?",array((String)$fileid));    
           }
         }
       }
       $response = new Response(("{\"succeed\":1,\"fileid\":\"$fileid\"}"));
			 $response->headers->set('Content-Type', 'text/json');
			 return $response;              
    }  
    public function grouplistAction($network_domain)
    {
    	  $request = $this->get("request");
    	  $user = $this->get('security.context')->getToken()->getUser();
    	  $circleId = $user->get_circle_id($network_domain);    	  
    	  $this->getGroupByUser($circleId,$user->getUserName());
    	  $this->network_domain = $network_domain;
    	  return $this->render('JustsyBaseBundle:DocumentMgr:group.html.twig', array('mode'=>$request->get("mode"),'this' => $this));
    }
    public function listAction($network_domain)
    {
    	  $request = $this->get("request");
    	  $user = $this->get('security.context')->getToken()->getUser();
	    	//获取当前用户所有的圈子及该圈子下的群组。企业圈子特别标明
	    	$sql1 = "SELECT a.circle_id,a.circle_name FROM we_circle a, we_circle_staff b where a.circle_id=b.circle_id and login_account=?";
	    	$sql2 = "SELECT c.group_id, c.group_name, c.circle_id FROM we_circle a,we_circle_staff b,we_groups c,we_group_staff d where a.circle_id=c.circle_id and c.group_id = d.group_id and a.circle_id = b.circle_id and b.login_account = ? and d.login_account = ?";
	    	$da = $this->get("we_data_access");
	    	$circles = $da->GetData("circles",$sql1,array((string)$user->getUserName()));
	    	$groups = $da->GetData("groups",$sql2,array((string)$user->getUserName(),(string)$user->getUserName()));
	    	$this->circles_array=$circles["circles"]["rows"];
	    	$this->groups_array=$groups["groups"]["rows"];
    	  $this->network_domain = $network_domain;
    	  return $this->render('JustsyBaseBundle:DocumentMgr:doclist.html.twig', array('account'=>$user->getUsername(),'mode'=>$request->get("mode"),'path'=>$this->container->getParameter('FILE_WEBSERVER_URL'),'this' => $this,
    	        'group_id' => $request->get("group_id"),
    	        'group_name' => $request->get("group_name")));
    }
    
    public function listJsonAction($network_domain)
    {
    	  $user = $this->get('security.context')->getToken()->getUser();
    	  $circleId = $user->get_circle_id($network_domain);     	
    	  $request=$this->get("request");
    	  
    	  $type=$request->get("scope_type");//查询文档的类型,0为所有文档; 1为圈子；2为群组；为空表示查询我的文档
    	  $id=$request->get("scope_id");//圈子/群组编号。
    	  $this->scope_type=$type;
    	  $this->scope_id=$id;
    	  $file_type = $request->get("file_type");//文件类型分类。为分全部(0)、文档(1)、图片(2)、音视频(3)3类
    	  $file_type = strlen($file_type)==0? "0": $file_type;
    	  $sql = "";
    	  $params = array();
    	  $fileFixed = array("voice"=>"'aif','snd','mid','avi','mpeg','mp4','mp3','wav','tiff','aiff','mov','mpg','ram','rm','ra','rmvb','asf','wmv','wma'",
    	    "img"=>"'gif','bmp','jpeg','jpg','png'",
    	    "doc"=>"'aif','snd','mid','avi','mpeg','mp4','mp3','wav','tiff','aiff','mov','mpg','ram','rm','ra','rmvb','asf','wmv','wma','gif','bmp','jpeg','jpg','png','mac'"
    	  );
    	  if($file_type=="3") $file_type = " and file_ext in (".$fileFixed["voice"].")";
    	  else if($file_type=="2") $file_type = " and file_ext in(".$fileFixed["img"].")";
    	  else if($file_type=="1") $file_type = " and file_ext not in(".$fileFixed["doc"].")";
    	  else $file_type = "";    	  
    	  
    	  if(strlen($type)==0)
    	  {
    	     $sql = "select file_id,file_name,".
    	            "case post_to_group when 'ALL' then (select circle_name from we_circle where circle_id=we_files.circle_id)".
    	            " else (select group_name from we_groups where group_id=we_files.post_to_group) end as post_to_group,".
    	            "we_staff.nick_name as up_by_staff,we_staff.login_account,up_date".
    	            " from we_files,we_staff where we_files.up_by_staff=we_staff.login_account and we_files.circle_id=? and  up_by_staff=? ".$file_type." order by up_date desc";
    	     $params[]=(string)$circleId;
    	     $params[] = (string)$user->getUserName();
    	  }
    	  else if($type=="0")
    	  {
    	     $sql = "select file_id,file_name,".
    	            "case post_to_group when 'ALL' then (select circle_name from we_circle where circle_id=we_files.circle_id)".
    	            " else (select group_name from we_groups where group_id=we_files.post_to_group) end as post_to_group,".
    	            "we_staff.nick_name as up_by_staff,we_staff.login_account ,up_date ".
    	            " from we_files,we_staff where we_files.up_by_staff=we_staff.login_account " .$file_type." and we_files.circle_id=? 
and we_files.post_to_group in ( select group_id from we_group_staff where we_group_staff.login_account=we_files.up_by_staff union select 'ALL' from dual) order by up_date desc";
    	     $params[]=(string)$circleId;
    	  }
    	  else if(strlen($id)>0){
    	  	 if($type=="1")
    	  	 {
    	        $sql = "select file_id,file_name,".
    	            "case post_to_group when 'ALL' then (select circle_name from we_circle where circle_id=we_files.circle_id)".
    	            " else (select group_name from we_groups where group_id=we_files.post_to_group) end as post_to_group,".
    	            "we_staff.nick_name as up_by_staff,we_staff.login_account,up_date".
    	            " from we_files,we_staff where we_files.up_by_staff=we_staff.login_account and post_to_group='ALL' and circle_id=? ".$file_type." order by up_date desc";
    	        $params[]=(string)$circleId;
    	     }
    	     else
    	     {
    	        $sql = "select file_id,file_name,".
    	            "case post_to_group when 'ALL' then (select circle_name from we_circle where circle_id=we_files.circle_id)".
    	            " else (select group_name from we_groups where group_id=we_files.post_to_group) end as post_to_group,".
    	            "we_staff.nick_name as up_by_staff,we_staff.login_account,up_date".
    	            " from we_files,we_staff where we_files.up_by_staff=we_staff.login_account and we_files.circle_id=? and post_to_group=? ".$file_type." order by up_date desc";
    	        $params[]=(string)$circleId;
    	        $params[] = (string)$id;
    	     }
    	  }
    	  else{
    	  	$response = new Response("");
    	  	$response->headers->set('Content-Type', 'text/json');
    	  	return $response;
    	  }
    	  $da = $this->get('we_data_access');
    	  $da->PageSize=10;
    	  $pageno = $id=$request->get("pageno");
    	  $da->PageIndex=strLen($pageno)==0? 0 : (int)$pageno;
    	  $ds = $da->GetData("we_groups", $sql, $params);
        $this->docs = $ds["we_groups"]["rows"];
    	  if(count($this->docs)==0)
    	     $response = new Response("<div style='text-align:center;height:50px;line-height:50px'>没有数据</div><script>updaterecordcount()</script>");
    	  else{
    	  	 $recordcount = $ds["we_groups"]["recordcount"];
    	  	 $pagecount = $recordcount>$da->PageSize? ceil($recordcount/$da->PageSize) : 0;
    	  	 $pageHTml = array();
    	  	 if($pagecount>0){
    	  	 	   $pageHTml[]="<div class=\"pagination\" style='text-align:right'><ul>";
    	  	 	   $pageHTml[]="<li ".($pageno <= 0 ? 'class="disabled"' : '')."><a href='".($pageno > 0 ? 'javascript:getPage('.($pageno-1).')' : '#')."'>上一页</a></li>";
    	  	 	   $pagestart = $pageno < 5 ? 0 : $pageno-5;
    	  	 	   $pageend = $pagestart+9 > $pagecount ? $pagecount : $pagestart+9;
    	  	     for($i=$pagestart;$i<$pageend;$i++)
    	  	     {
    	  	 	       $pageHTml[]= ($pageno==$i)?"<li class='active'><a href='javascript:getPage($i)'>".($i+1)."</a></li>": "<li><a href='javascript:getPage($i)'>".($i+1)."</a></li>";
    	  	     }
    	  	     $pageHTml[]="<li ".($pageno >= $pagecount - 1 ? 'class="disabled"' : '')."><a href='".($pageno < $pagecount - 1 ? 'javascript:getPage('.($pageno+1).')' : '#')."'>下一页</a></li>";
    	  	     $pageHTml[]="</ul></div>";
    	  	 }
    	  	 $result = array();
    	  	 $file_type=$request->get("file_type");
    	  	 for($i=0;$i<count($this->docs);$i++)
    	  	 {
    	  	 	   $imgPath = $this->container->getParameter('FILE_WEBSERVER_URL').$this->docs[$i]["file_id"];
    	  	 	   $rec = $this->docs[$i];   
    	  	 	   $filename= $rec["file_name"];
    	  	 	   $tmp = explode(".",$filename);
    	  	 	   if(count($tmp)==1) $tmp[1]="";
    	  	 	   if(mb_strlen( $tmp[0],'utf-8')>13)
    	  	 	     $filename =  mb_substr($filename,0,13,'utf-8')."....".$tmp[1];
 	  	 	       $iconFlag = "fileicon.png";
 	  	 	       if( stripos($fileFixed["voice"],$tmp[1]))
 	  	 	          $iconFlag = "videoicon.png";
 	  	 	       else if( stripos($fileFixed["img"],$tmp[1]))
 	  	 	          $iconFlag = "imgicon.png";
 	  	 	       if($rec["login_account"]==$user->getUserName())//判断是否可以删除
 	  	 	       {
 	  	 	           $filename = "<span class='document_delete'>".$filename."</span>";
 	  	 	       }
    	  	 	   if($file_type=="2")
    	  	 	   {    	  	 	   	  
    	  	        $result[$i]= "<tr id='".$rec["file_id"]."' text='".$rec["file_name"]."'><td height=32><a title='点击查看原图' target=_blank href='$imgPath'><img border=1 style='border:1px solid black' width=32 src='".$imgPath."'></a>&nbsp;&nbsp;".$filename."</td><td>".$rec["post_to_group"]."</td><td><a class=\"employee_name\" style='color:#0088CC;' login_account=\"".$rec["login_account"]."\">".$rec["up_by_staff"]."</a></td><td>".$rec["up_date"]."</td></tr>";
    	  	     }
    	  	     else if($file_type=="3")
    	  	        $result[$i]= "<tr id='".$rec["file_id"]."' text='".$rec["file_name"]."'><td height=32><img class=\"fileicon\" width=\"16\" height=\"15\" src='/bundles/fafatimewebase/images/$iconFlag'><A target=_blank href='$imgPath'>".$filename."</a></td><td>".$rec["post_to_group"]."</td><td ><a class=\"employee_name\" style='color:#0088CC;' login_account=\"".$rec["login_account"]."\">".$rec["up_by_staff"]."</a></td><td>".$rec["up_date"]."</td></tr>";
               else
    	  	       $result[$i]= "<tr id='".$rec["file_id"]."' text='".$rec["file_name"]."'><td><img class=\"fileicon\" width=\"16\" height=\"15\" src='/bundles/fafatimewebase/images/$iconFlag'><A target=_blank href='$imgPath'>".$filename."</a></td><td>".$rec["post_to_group"]."</td><td><a class=\"employee_name\" style='color:#0088CC;' login_account=\"".$rec["login_account"]."\">".$rec["up_by_staff"]."</a></td><td>".$rec["up_date"]."</td></tr>";
    	  	 }
    	     $response = new Response( "<table recordcount='$recordcount' class=\"table\"><tr><td>名称</td><td>所属群组</td><td>上传人</td><td>上传日期</td></tr>".implode("",$result)."</table><script>updaterecordcount()</script>".implode("",$pageHTml));
    	  }
    	  $response->headers->set('Content-Type', 'text/html');
    	  return $response;
    } 
    
    //获取最新文件
    public function getTopNewAction($network_domain,$scope_type)
    {
    	  $request=$this->get("request");
    	  $user = $this->get('security.context')->getToken()->getUser();
    	  $type=$scope_type;//查询文档的类型, 1为圈子；2为群组；为空表示查询我的文档
    	  $id=$request->get("scope_id");//圈子/群组编号。
    	  $circleId = $user->get_circle_id($network_domain);  	  
    	  $id = strlen($id)==0? $circleId :$id;
    	  $this->scope_type=$type;
    	  $this->scope_id=$id;
    	  $sql = "";
    	  
    	  $params = array();
    	  $fileFixed = array("voice"=>"'aif','snd','mid','avi','mpeg','mp4','mp3','wav','tiff','aiff','mov','mpg','ram','rm','ra','rmvb','asf','wmv','wma'",
    	    "img"=>"'gif','bmp','jpeg','jpg','png'",
    	    "doc"=>"'aif','snd','mid','avi','mpeg','mp4','mp3','wav','tiff','aiff','mov','mpg','ram','rm','ra','rmvb','asf','wmv','wma','gif','bmp','jpeg','jpg','png','mac'"
    	  );
    	  if(strlen($type)==0)
    	  {
    	     $sql = "select file_id,file_name,we_staff.login_account,".
    	            "we_staff.nick_name as up_by_staff,we_staff.fafa_jid,up_date".
    	            " from we_files,we_staff where we_files.up_by_staff=we_staff.login_account and up_by_staff=? and file_ext not in (".$fileFixed["img"].") order by up_date desc limit 0,10";
    	     $params[] = (string)$user->getUserName();
    	  }
    	  else if(strlen($id)>0){
    	  	 if($type=="1")
    	        $sql = "select file_id,file_name,we_staff.login_account,".    	            
    	            "we_staff.nick_name as up_by_staff,we_staff.fafa_jid,up_date".
    	            " from we_files,we_staff where we_files.up_by_staff=we_staff.login_account and post_to_group='ALL' and circle_id=? and dir=? and file_ext not in (".$fileFixed["img"].") order by up_date desc limit 0,10";
    	     else
    	        $sql = "select file_id,file_name,we_staff.login_account,".
    	            "we_staff.nick_name as up_by_staff,we_staff.fafa_jid,up_date".
    	            " from we_files,we_staff where we_files.up_by_staff=we_staff.login_account  and post_to_group=? and dir=? and file_ext not in (".$fileFixed["img"].") order by up_date desc limit 0,10";
    	     $params[] = (string)$id;
    	     $params[] = (string)($type=="1" ? 'c'.$id : 'g'.$id);
    	  }
    	  else{
    	  	$response = new Response("");
    	  	$response->headers->set('Content-Type', 'text/json');
    	  	return $response;
    	  }

    	  $da = $this->get('we_data_access');
    	  $ds = $da->GetData("we_groups", $sql, $params);
        $this->docs = $ds["we_groups"]["rows"];
        
        $a = array();
        $a["this"] = $this;
        $a["curr_network_domain"] = $network_domain;
        $a["we_doc"] = $this->docs;
        $a["DOC"]=true;
         if(!in_array(\Justsy\BaseBundle\Management\FunctionCode::$DOC,$user->getFunctionCodes())){
                $a["DOC"]= false;
            }
        return $this->render('JustsyBaseBundle:DocumentMgr:right_doc.html.twig', $a);  
    }
    
    public function getFileIcon($filename) 
    {
      return \Justsy\BaseBundle\Common\MIME::getFileIcon($filename);
    }
    //在指定的圈子中搜索文档
    private function searchFiles($name,$circleid,$parentid)
    {
    	 $da = $this->get("we_data_access");
    	 $user = $this->get('security.context')->getToken()->getUser();
    	 $userAccount = $user->getUsername();
			 if(substr($parentid,0,1)=="g")
			 {
		       $sql = "select a.*,b.nick_name,b.fafa_jid from we_files a left join we_staff b on a.up_by_staff=b.login_account where b.state_id='1' and a.post_to_group =? and  a.up_by_staff=? and a.file_name like concat('%', ?, '%') union ".
		              " select a.*,b.nick_name,b.fafa_jid from  we_files a,we_staff b,we_doc_share b5 where a.up_by_staff=b.login_account and b.state_id='1' and a.file_id=b5.resourceid and b5.resourcetype='1' and b5.objectid=? and b5.objecttype='g' and a.file_name like concat('%', ?, '%') ";
				   $para[]=(string)substr($parentid,1);	
				   $para[]=(string)$userAccount;		
				   $para[]=(string)$name;		 
				   $para[]=(string)substr($parentid,1);			
				   $para[]=(string)$name;						   
			 }
			 else if(substr($parentid,0,1)=="c")
			 {
				 $sql_group = "SELECT a.group_id FROM we_groups a, we_group_staff b where a.group_id=b.group_id and a.circle_id=? and b.login_account=?";
	       $ds_group = $da->GetData('we_groups',$sql_group,array((string)$circleid,(string)$userAccount));
				    		  $groupids_contion ="";
				    		  if(count($ds_group["we_groups"]["rows"])>0)
				    		  {    		  	
							      foreach ($ds_group["we_groups"]["rows"] as &$row) 
							      {
							        $groupids[] = "'".$row['group_id']."'";
							      }    		  	
				    		  	$groupids_contion=" select a.*,b.nick_name,b.fafa_jid from we_files a,we_staff b,we_doc_share b2 where a.up_by_staff=b.login_account and a.file_id=b2.resourceid and b2.resourcetype='1' and b2.objectid in (".implode(",",$groupids).") and b2.objecttype='g' and b.state_id='1' and a.file_name like concat('%', ?, '%') ";
				    		  }  
			 	
	       $sql = "select a.*,b.nick_name,b.fafa_jid from we_files a left join we_staff b on a.up_by_staff=b.login_account where b.state_id='1' and a.circle_id=? and  a.up_by_staff=? and a.file_name like concat('%', ?, '%') union ".
	              " select a.*,b.nick_name,b.fafa_jid from we_files a,we_staff b,we_doc_share b3 where a.up_by_staff=b.login_account and b.state_id='1' and a.file_id=b3.resourceid and b3.resourcetype='1' and b3.objectid=? and b3.objecttype='c' and a.file_name like concat('%', ?, '%') union ".
	              " select a.*,b.nick_name,b.fafa_jid from  we_files a,we_staff b,we_doc_share b4 where a.up_by_staff=b.login_account and b.state_id='1' and a.file_id=b4.resourceid and b4.resourcetype='1' and b4.objectid=? and b4.objecttype='p' and a.file_name like concat('%', ?, '%') union ".
	              " select a.*,b.nick_name,b.fafa_jid from  we_files a,we_staff b,we_doc_share b5 where a.up_by_staff=b.login_account and b.state_id='1' and a.file_id=b5.resourceid and b5.resourcetype='1' and b5.objectid=? and b5.objecttype='d' and a.file_name like concat('%', ?, '%') union ".
	              $groupids_contion;
		     $para[]=(string)$circleid;
		     $para[]=(string)$userAccount;
		     $para[]=(string)$name;
		     $para[]=(string)$circleid;
		     $para[]=(string)$name;
		     $para[]=(string)$userAccount;
		     $para[]=(string)$name;
		     $para[]=(string)$user->dept_id;
				 $para[]=(string)$name;		
				 $para[]=(string)$name;	 	
			 }
			 else  
			 {
		       $sql = "select a.*,b.nick_name,b.fafa_jid from we_files a left join we_staff b on a.up_by_staff=b.login_account where b.state_id='1' and a.dir =? and a.file_name like concat('%', ?, '%') ";
				   $para[]=(string)$parentid;			
				   $para[]=(string)$name;		 		 	
			 }
			 $sql .= " order by up_date desc limit 0,100";
			 //var_dump($sql);
			 $ds = $da->GetData('data',$sql,$para);
			 return $ds["data"]["rows"];
    }
    public function getFileAction($network_domain)
    {
    	$request = $this->get("request"); 
			$name = $request->get("name");   	
    	$user = $this->get('security.context')->getToken()->getUser();
    	$circleid= $user->get_circle_id($network_domain);
    	$parentid = $request->get("parentid");
    	if(empty($parentid)) $parentid='c'.$circleid;
			if(!empty($name))//搜索文档
			{
				  $ds = $this->searchFiles($name,$circleid,$parentid); 
					foreach ($ds as &$row) 
					{
							        $row['ext_icon']=$this->getFileIcon($row['file_name']);
					}	    
				  $response = new Response(json_encode(array("data"=>$ds,"info"=>"")));
				  $response->headers->set('Content-Type', 'text/json');
					return $response;				  
			}     	
    	$para=array();
    	$da = $this->get("we_data_access");
    	//获取指定目录下的文档列表
    	//如果目录编号是特定的以s开头的，表示是获取其他人员的共享目录文件
    	//   如果编号格式为s|parentid|loginaccount，表示该目录是系统自动生成的，专门用于存放其他人员共享的文件
    	//如果目录编号的第一个字符是g或者c，表示是获取群组根目录或者圈子根目录
    	//   此时需要额外获取共享文件/目录的人员列表，页面上并生成对应的以s_c[g]_dirID_帐号形式为ID的目录。
    	//其他情况则表示获取子目录的文件列表
    	$firstChar = substr($parentid,0,1);
    	if($firstChar!="s")
    	{
				    	if($firstChar=="g")  //当前查询的指定群下面的目录
				    	{
						    	$sql = "select a.*,b.nick_name,b.fafa_jid from we_files a left join we_staff b on a.up_by_staff=b.login_account where b.state_id='1' and a.circle_id=? and ( a.up_by_staff=? or a.file_id in(".
						    	            "select b2.resourceid from we_doc_share b2 where b2.resourcetype='1' and b2.objectid=? and b2.objecttype='g' ".
						    	       "))";
						    	$para[]=(string)$circleid;
						    	$para[]=(string)$user->getUsername();
						    	$para[]=(string)substr($parentid,1);//群ID
				    	}    	
				    	else if($firstChar=="c")
				    	{
				    		  //查询结果：1、查询当前圈子自己上传的文件；2、查询当前圈子中，其他人共享到根目录的文件。
						    	$sql = "select a.*,b.nick_name,b.fafa_jid from we_files a left join we_staff b on a.up_by_staff=b.login_account where b.state_id='1' and a.circle_id=? and ( a.up_by_staff=? or a.file_id in(select b3.resourceid from we_doc_share b3 where b3.resourcetype='1' and b3.objectid=? and b3.objecttype='c'))";
						    	$para[]=(string)$circleid;
						    	$para[]=(string)$user->getUsername();
						    	$para[]=(string)$circleid;
				      }
				      else   //查询圈子/群组的非根目录。
				      {
						    	$sql = "select a.*,b.nick_name,b.fafa_jid from we_files a left join we_staff b on a.up_by_staff=b.login_account where b.state_id='1'";	   
				      }

				    	$sql = $sql." and a.dir=? ";    	     
						  $para[]=(string)$parentid;
				    	$fileid = $request->get("fileid");
				    	if(!empty($fileid))
				    	{
				    	    	$sql = $sql." and file_id =?";
				    	    	$para[]=(string)$fileid;
				    	}    	
				    	$page = $request->get("pageno");
				    	if(empty($page)) $page="0,50";
				    	$sql = $sql." order by up_date desc limit ".$page;

				    	//查询出共享的文件信息。只有查询圈子/群组根目录时才获取共享信息，查询子目录是不用获取共享信息
				    	$para2=array();
				    	$shareinfo="";
				    	if($firstChar=="g")
				    	{
				    		  //获取共享了文件到该群组的所有人员。页面上根据共享人员生成对应的共享目录
				    	    $shareinfo = "select distinct concat('s|', ?, '|',b.login_account) as id, b.login_account,b.nick_name,b.fafa_jid from we_files a, we_doc_share b2,we_staff b where a.up_by_staff=b.login_account and  a.file_id=b2.resourceid and b2.resourcetype='1' and a.up_by_staff!=? and b2.objectid=? and b2.objecttype='g' and a.dir!=?";
				    	    $para2[]=(string)$parentid;
				    	    $para2[]=(string)$user->getUsername();
				    	    $para2[]=(string)substr($parentid,1);//群ID			
				    	    $para2[]=(string)$parentid;	    	    
				      }
				    	else if($firstChar=="c")
				    	{
				    		  //获取共享了文件到当前部门、帐号、以及所在群组的所有人员。页面上根据人员生成对应的共享目录
    		  
				    		  $shareinfo ="select distinct concat('s|', ?, '|',b.login_account) as id, b.login_account,b.nick_name,b.fafa_jid from we_files a inner join we_staff b on a.up_by_staff=b.login_account".
				    		              " inner join ( select b1.resourceid from we_doc_share b1 where b1.resourcetype='1' and b1.objectid=? and b1.objecttype='p' union ".
						    	            //" select b2.resourceid from we_doc_share b2 where b2.resourcetype='1' and b2.objectid=? and b2.objecttype='c' union ".
						    	            " select b4.resourceid from we_doc_share b4 where b4.resourcetype='1' and b4.objectid=? and b4.objecttype='d') c on a.file_id=c.resourceid where a.up_by_staff!=?";
				    	    $para2[]=(string)$parentid;
				    	    $para2[]=(string)$user->getUsername();
				    	    //$para2[]=(string)substr($parentid,1);
						    	$para2[]=(string)$user->dept_id;
						    	$para2[]=(string)$user->getUsername();
				    	}
				    	
					    $ds =$shareinfo==""? $da->GetData('we_dir',$sql,$para): $da->GetDatas(array('we_dir','info'),array($sql,$shareinfo),array($para,$para2));
							foreach ($ds['we_dir']['rows'] as &$row) 
							{
							        $row['ext_icon']=$this->getFileIcon($row['file_name']);
							}	    
				      $response = new Response(json_encode(array("data"=>$ds['we_dir']['rows'],"info"=>($shareinfo==""?"":$ds['info']['rows']))));
							$response->headers->set('Content-Type', 'text/json');
							return $response;  
		  }
		  else
		  {
		  	   //获取指定共享目录的文件列表
		  	   $chars = explode("|",$parentid);		  	   
		  	   $parentid=$chars[1];      //共享者所在的圈子/群组
		  	   $loginaccount = $chars[2];//获取共享者帐号
		  	   if(empty($parentid)) $parentid='c'.$circleid;
		  	   $firstChar = substr($parentid,0,1);
				   if($firstChar=="g")
				   {
				    		  //获取共享了文件到该群组的所有人员。页面上根据共享人员生成对应的共享目录
				    	    $shareinfo = "select a.*,b.nick_name,b.fafa_jid from we_files a left join we_staff b on a.up_by_staff=b.login_account inner join we_doc_share b2 on b2.resourceid =a.file_id  where b2.resourcetype='1' and b2.objectid=? and b2.objecttype='g' and a.up_by_staff=? and a.dir!=?";
				    	    $para2[]=(string)substr($parentid,1);//群ID
				    	    $para2[]=(string)$loginaccount;
				    	    $para2[]=(string)$parentid;	 
				   }
				   else if($firstChar=="c")
				   {
				    		  //获取共享了文件到当前部门、帐号 		  
				    		  $shareinfo ="select a.*,b.nick_name,b.fafa_jid from we_files a left join we_staff b on a.up_by_staff=b.login_account inner join  (select b1.resourceid from we_doc_share b1 where b1.resourcetype='1' and b1.objectid=? and b1.objecttype='p' union ".
						    	            //"select b2.resourceid from we_doc_share b2 where b2.resourcetype='1' and b2.objectid=? and b2.objecttype='c' union ".
						    	            "select b4.resourceid from we_doc_share b4 where b4.resourcetype='1' and b4.objectid=? and b4.objecttype='d') b on b.resourceid =a.file_id where a.up_by_staff=?";
				    	    $para2[]=(string)$user->getUsername();
				    	    //$para2[]=(string)substr($parentid,1);
						    	$para2[]=(string)$user->dept_id;
						    	$para2[]=(string)$loginaccount;
				   }
				  $ds =$da->GetData('we_dir',$shareinfo,$para2);
					foreach ($ds['we_dir']['rows'] as &$row) 
					{
							        $row['ext_icon']=$this->getFileIcon($row['file_name']);
					}	    
				  $response = new Response(json_encode(array("data"=>$ds['we_dir']['rows'],"info"=>"")));
				  $response->headers->set('Content-Type', 'text/json');
					return $response;				    
		  }
    }
    private function searchDirss($name,$circleid,$parentid)
    {
    	 $user = $this->get('security.context')->getToken()->getUser();
    	 $da = $this->get("we_data_access"); 
		   $sql = "select a.*,b.nick_name,b.fafa_jid from ( select a.id,a.name, a.createdate, a.owner,'0' isshare  from we_doc_dir a  where a.circleid=? and a.owner=? and name like concat('%', ?, '%') union ".
		    	       "select b.id,b.name, b.createdate, b.owner,'1' isshare  from we_doc_dir b where b.circleid=? and b.owner!=? and b.id in(".
		    	            "select b1.resourceid from we_doc_share b1 where b1.resourcetype='0' and b1.objectid=? and b1.objecttype='p' union ".
		    	            "select b3.resourceid from we_doc_share b3 where b3.resourcetype='0' and b3.objectid=? and b3.objecttype='c' union ".
		    	            "select b4.resourceid from we_doc_share b4 where b4.resourcetype='0' and b4.objectid=? and b4.objecttype='d')".
		    	       ") a left join we_staff b on a.owner=b.login_account where b.state_id='1' and name like concat('%', ?, '%')";
		    	$para[]=(string)$circleid;
		    	$para[]=(string)$user->getUsername();
		    	$para[]=(string)$name;
		    	$para[]=(string)$circleid;
		    	$para[]=(string)$user->getUsername();
		    	$para[]=(string)$user->getUsername();
		    	$para[]=(string)$circleid;
		    	$para[]=(string)$user->dept_id;	
		    	$para[]=(string)$name;		
			 $sql .= " order by createdate desc";
			 $ds = $da->GetData('data',$sql,$para);
			 return $ds["data"]["rows"];		    	     	  
    }
    
    public function getDirAction($network_domain)
    {
    	$request = $this->get("request"); 
    	$user = $this->get('security.context')->getToken()->getUser();
    	$circleid= $user->get_circle_id($network_domain);
    	$parentid = $request->get("parentid");
    	if(empty($parentid)) $parentid='c'.$circleid;
    	$name = $request->get("name");
    	if(!empty($name))
    	{
				  $ds = $this->searchDirss($name,$circleid,$parentid);     
				  $response = new Response(json_encode(array("data"=>$ds,"info"=>"")));
				  $response->headers->set('Content-Type', 'text/json');
					return $response;	
    	}	
    	$para=array();
    	$da = $this->get("we_data_access");
    	$firstChar = substr($parentid,0,1);
    	if($firstChar=="g")  
    	{
    		  //1、查询当前群下面的自己创建的子目录；2、查询出共享给该群的目录
		    	$sql = "select a.*,b.nick_name,b.fafa_jid,'0' isshare from we_doc_dir a left join we_staff b on a.owner=b.login_account where b.state_id='1' and a.circleid=? and a.parentid=? and a.owner=? union ".
		    	     "  select a.*,b.nick_name,b.fafa_jid,'1' isshare from we_doc_dir a left join we_staff b on a.owner=b.login_account left join we_doc_share b2 on a.id= b2.resourceid  where b.state_id='1' and b2.resourcetype='0' and b2.objectid=? and b2.objecttype='g' and a.owner!=?";
		    	$para[]=(string)$circleid;
		    	$para[]=(string)$parentid;
		    	$para[]=(string)$user->getUsername();
		    	$para[]=(string)substr($parentid,1);//群ID
		    	$para[]=(string)$user->getUsername();
    	}
    	else if($firstChar=="c")
    	{
    		  //查询出自己创建的目录；共享给自己的目录；共享给当前圈子的目录；共享给自己所在部门的目录
		    	$sql = "select a.*,b.nick_name,b.fafa_jid from ( select a.id,a.name, a.createdate, a.owner,'0' isshare  from we_doc_dir a  where a.circleid=? and a.parentid=? and a.owner=? union ".
		    	       "select b.id,b.name, b.createdate, b.owner,'1' isshare  from we_doc_dir b where b.circleid=? and b.owner!=? and b.id in(".
		    	            "select b1.resourceid from we_doc_share b1 where b1.resourcetype='0' and b1.objectid=? and b1.objecttype='p' union ".
		    	            "select b3.resourceid from we_doc_share b3 where b3.resourcetype='0' and b3.objectid=? and b3.objecttype='c' union ".
		    	            "select b4.resourceid from we_doc_share b4 where b4.resourcetype='0' and b4.objectid=? and b4.objecttype='d')".
		    	       ") a left join we_staff b on a.owner=b.login_account where b.state_id='1'";
		    	$para[]=(string)$circleid;
		    	$para[]=(string)$parentid;		    	
		    	$para[]=(string)$user->getUsername();
		    	$para[]=(string)$circleid;
		    	$para[]=(string)$user->getUsername();
		    	$para[]=(string)$user->getUsername();
		    	$para[]=(string)$circleid;
		    	$para[]=(string)$user->dept_id;
      }  
      else
      {
          //查询出当前目录下的子目录
		    	$sql = "select a.*,b.nick_name,b.fafa_jid from we_doc_dir a left join we_staff b on a.owner=b.login_account where b.state_id='1' and a.circleid=? and a.parentid=? and a.owner=? ";
		    	$para[]=(string)$circleid;
		    	$para[]=(string)$parentid;
		    	$para[]=(string)$user->getUsername();          	
      }  	

    	$dirid = $request->get("dirid");
    	if(!empty($dirid))
    	{
    	    	$sql = $sql." and id =?";
    	    	$para[]=(string)$dirid;
    	}
	    $ds = $da->GetData('we_dir',$sql,$para);
      $response = new Response(json_encode(array("data"=>$ds['we_dir']['rows'],"info"=>"")));
			$response->headers->set('Content-Type', 'text/json');
			return $response; 
    }
    
    public function getshareinfoAction($network_domain)
    {
        $request = $this->get("request");
        $id = $request->get("id");
        if(empty($id))
        {
		        $response = new Response(("{\"succeed\":0,\"msg\":\"无效的资源\"}"));
					  $response->headers->set('Content-Type', 'text/json');
					  return $response;        	
        }
        $sql = "select *,case objecttype when 'p' then (select nick_name  from we_staff      where login_account=objectid)".
               "                         when 'g' then (select group_name from we_groups      where group_id=objectid)".
               "                         when 'd' then (select dept_name  from we_department where dept_id=objectid) end objectname from we_doc_share where resourceid=?";
        $da = $this->get('we_data_access');
        $ds = $da->GetData("we_dir",$sql,array((string)$id));
		    $response = new Response(json_encode($ds['we_dir']['rows']));
				$response->headers->set('Content-Type', 'text/json');
				return $response;        
    }
    //将文件分享到指定的圈子/群组
    public function publishfileAction($network_domain)
    {
    	  $request = $this->get("request");
    	  $user = $this->get('security.context')->getToken()->getUser();
    	  $circleId = $request->get("circleId");
    	  $gorupid = $request->get("gorupid");
    	  if(empty($gorupid)) $gorupid = "ALL";
    	  $fileid = $request->get("fileid");
    	  $remark = $request->get("remark");
    	  if(empty($remark)) $remark = "好东西要分享!";
        $sqls = array();
        $all_params = array();
        $da = $this->get("we_data_access");
        $conv_id = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_convers_list", "conv_id");
            
        $sqlInsert = 'insert into we_convers_list (conv_id, login_account, post_date, conv_type_id, conv_root_id, conv_content, post_to_group, post_to_circle, copy_num, reply_num) values (?, ?, CURRENT_TIMESTAMP(), ?, ?, ?, ?, ?, 0, 0)';
        $params = array();
        $params[] = (string)$conv_id;
        $params[] = (string)$user->getUserName();
        $params[] = (string)'00';
        $params[] = (string)$conv_id;
        $params[] = (string)$remark;
        $params[] = (string)$gorupid;
        $params[] = (string)$circleId;
        $sqls[] = $sqlInsert;
        $all_params[] = $params;
            
        $sqlInsert = "insert into we_convers_attach (conv_id, attach_type, attach_id) values (?, '0', ?)";
        $params = array();
        $params[] = (string)$conv_id;
        $params[] = (String)$fileid;			              
        $sqls[] = $sqlInsert;
        $all_params[] = $params;  
        $da->ExecSQLs($sqls, $all_params);
        
        $response = new Response(("{\"succeed\":1,\"fileid\":\"$fileid\"}"));
        $response->headers->set('Content-Type', 'text/json');
        return $response;                
    }
    
    public function saveshareinfoAction()
    {
        $request = $this->get("request");
        $resourceid = $request->get("resourceid");
        $resourcetype = $request->get("resourcetype");
        $objecttype = $request->get("objecttype");
        $objectid = $request->get("objectid");
        $privacy = $request->get("privacy");
        $r= $this->saveShare($resourceid,$resourcetype,$objectid,$objecttype,$privacy);
        $response = new Response(("{\"succeed\":$r}"));
			  $response->headers->set('Content-Type', 'text/json');
			  return $response;        
    }
    
    public function saveShare($resourceid,$resourcetype,$objectid,$objecttype,$privacy)
    {
        $da = $this->get("we_data_access");
        $id=(String)SysSeq::GetSeqNextValue($da,"we_doc_share","id");
    	try{
		    	$sql = "insert into we_doc_share(id,resourceid,resourcetype,objectid,objecttype,privacy,createdate)values(?,?,?,?,?,?,now())";
		    	$da->ExecSQL($sql,array((String)$id,(string)$resourceid,(string)$resourcetype,(string)$objectid,(string)$objecttype,(string)$privacy)); 
		    	return 1;
	    }
	    catch(\Exception $e)
	    {
	      $this->get("logger")->err($e);
	      return 0;
	    }    	
    }
    
    public function delshareinfoAction()
    {
        $request = $this->get("request");
        $id = $request->get("id");
        if(empty($id))
        {
		        $response = new Response(("{\"succeed\":0,\"msg\":\"无效的操作\"}"));
					  $response->headers->set('Content-Type', 'text/json');
					  return $response;        	
        }
        $da = $this->get('we_data_access');
        $sql = "delete from we_doc_share where id=?";
        $da->ExecSQL($sql,array((string)$id));
		    $response = new Response(("{\"succeed\":1}"));
				$response->headers->set('Content-Type', 'text/json');
				return $response;        
    }     
    //创建新目录
    public function saveDirAction($network_domain)
    {
        $request = $this->get("request");        
        $name = $request->get("name");
        if(empty($name))
        {
		        $response = new Response(("{\"succeed\":0,\"msg\":\"目录名不能为空\"}"));
					  $response->headers->set('Content-Type', 'text/json');
					  return $response;
        }
        $dirid = $request->get("id");
        if(!empty($dirid))
        {
           return $this->updateDir($dirid,$name);
        }
        $parentid = $request->get("parentid");
        $user = $this->get('security.context')->getToken()->getUser();
        $circleid=$user->get_circle_id($network_domain);
        if(empty($parentid)) $parentid= 'c'.$circleid;
        $r = $this->createDir("",$parentid,$name,$circleid);
        $response = new Response(("{\"succeed\":1,\"id\":\"$r\"}"));
			  $response->headers->set('Content-Type', 'text/json');
			  return $response;        	
    }
    
    public function updateDir($dirid,$name)
    {
        $da = $this->get('we_data_access');
        $user = $this->get('security.context')->getToken()->getUser();
        $sql = "update we_doc_dir set name=? where owner=? and id=?";
        $da->ExecSQL($sql,array((string)$name,(string)$user->getusername(),(string)$dirid));
		    $response = new Response(("{\"succeed\":1,\"id\":\"$dirid\"}"));
				$response->headers->set('Content-Type', 'text/json');
				return $response;        
    }
    
    public function delDirAction()
    {
        $request = $this->get("request");
        $dirid = $request->get("fileid");
        if(empty($dirid))
        {
		        $response = new Response(("{\"succeed\":0,\"msg\":\"无效的目录\"}"));
					  $response->headers->set('Content-Type', 'text/json');
					  return $response;        	
        }
        $da = $this->get('we_data_access');
        $user = $this->get('security.context')->getToken()->getUser();
        //判断是否有子目录/文件
        $sql = "select (select count(1) from we_doc_dir where parentid=?)+(select count(1) from we_files where dir=?) cnt";
        $ds = $da->GetData("che",$sql,array((string)$dirid,(string)$dirid));
        if($ds["che"]["rows"][0]["cnt"]>0)
        {
		        $response = new Response(("{\"succeed\":0,\"msg\":\"有子目录或文件\"}"));
					  $response->headers->set('Content-Type', 'text/json');
					  return $response;
        }
        $sql = "delete from we_doc_dir where owner=? and id=?";
        $da->ExecSQL($sql,array((string)$user->getusername(),(string)$dirid));
        //删除共享设置
        $da->ExecSQL("delete from we_doc_share where resourcetype='0' and resourceid=?",array((String)$dirid));         
		    $response = new Response(("{\"succeed\":1}"));
				$response->headers->set('Content-Type', 'text/json');
				return $response;        
    }
    //检查目录是否可以删除
    public function checkDirByDelAction()
    {
    	  $request = $this->get("request");
    	  $dirid = $request->get("dirid");
        if(empty($dirid))
        {
		        $response = new Response(("{\"succeed\":0,\"msg\":\"无效的文件夹\"}"));
					  $response->headers->set('Content-Type', 'text/json');
					  return $response;        	
        }    	  
        $da = $this->get('we_data_access');
        //判断是否有子目录/文件
        $sql = "select (select count(1) from we_doc_dir where parentid=?)+(select count(1) from we_files where dir=?) cnt";
        $ds = $da->GetData("che",$sql,array((string)$dirid,(string)$dirid));
        if($ds["che"]["rows"][0]["cnt"]>0)
        {
		        $response = new Response(("{\"succeed\":0,\"msg\":\"文件夹不为空,不能删除\"}"));
					  $response->headers->set('Content-Type', 'text/json');
					  return $response;
        }
		    $response = new Response(("{\"succeed\":1}"));
			  $response->headers->set('Content-Type', 'text/json');
				return $response;        
    }
    //检查文件是否可以删除
    public function checkFileByDelAction()
    {
    	  $request = $this->get("request");
    	  $fileid = $request->get("fileid");
        if(empty($fileid))
        {
		        $response = new Response(("{\"succeed\":0}"));
					  $response->headers->set('Content-Type', 'text/json');
					  return $response;        	
        }    	  
        $da = $this->get('we_data_access');
        //判断是否有子目录/文件
        $sql = "select count(1) cnt from we_convers_attach where attach_id=?";
        $ds = $da->GetData("che",$sql,array((string)$fileid));
        if($ds["che"]["rows"][0]["cnt"]>0)
        {
		        $response = new Response(("{\"succeed\":0,\"msg\":\"文件使用中，不能删除\"}"));
					  $response->headers->set('Content-Type', 'text/json');
					  return $response;
        }
		    $response = new Response(("{\"succeed\":1}"));
			  $response->headers->set('Content-Type', 'text/json');
				return $response;        
    }    
    
    //获得当前用户在当前圈子下的部门信息和群组信息,共享信息
    public function getdgAction($network_domain)
    {
    	$login_account = $this->get('security.context')->getToken()->getUser()->getUserName();
    	$request = $this->get("request");
    	$curr_circle=$this->get('security.context')->getToken()->getUser()->get_circle_id($network_domain);
    	$da = $this->get("we_data_access");
    	$sql="select a.group_id,a.group_name from we_groups a where a.circle_id=? and exists (select 1 from we_group_staff b where b.login_account=? and b.group_id=a.group_id)";
    	$params=array($curr_circle,$login_account);
    	$ds=$da->GetData('group',$sql,$params);
    	$groups=$ds['group']['rows'];
    	$sql="select a.dept_id,a.dept_name from we_department a where a.eno=(select b.eno from we_staff b where b.login_account=?)";
    	$params=array($login_account);
    	$ds=$da->Getdata('dept',$sql,$params);
    	$depts=$ds['dept']['rows'];
    	
    	//获得共享信息
    	$isdir=$request->get('isdir');
    	$isfile=$request->get('isfile');
    	$resourceid=$request->get('resourceid');
    	$filetype=($isfile=='1'?$isfile:'0');
    	$sql="select a.*,ifnull(ifnull(ifnull(b.circle_name,c.group_name),d.nick_name),e.dept_name) as object_name from we_doc_share a 
    				left join we_circle b on b.circle_id=a.objectid and a.objecttype='c' 
    				left join we_groups c on c.group_id=a.objectid and a.objecttype='g'
    				left join we_staff d on d.login_account=a.objectid and a.objecttype='p'
    				left join we_department e on e.dept_id=a.objectid and a.objecttype='d' where a.resourceid=? and a.resourcetype=? order by a.objecttype asc";
    	$params=array($resourceid,$filetype);
    	$ds=$da->Getdata('share',$sql,$params);
    	$shareinfo=$ds['share']['rows'];
    	$selected_circle=array();
    	$selected_dept=array();
    	$selected_group=array();
    	$selected_person=array();
    	foreach($shareinfo as $item)
    	{
    		if($item['objecttype']=='c')
    		{
    			array_push($selected_circle,$item);
    		}
    		else if($item['objecttype']=='d')
    		{
    			array_push($selected_dept,$item);
    		}
    		else if($item['objecttype']=='g')
    		{
    			array_push($selected_group,$item);
    		}
    		else if($item['objecttype']=='p')
    		{
    			array_push($selected_person,$item);
    		}
    	}
    	$re=array('groups'=>$groups,'depts'=>$depts,'shareinfo'=> array('circles'=>$selected_circle,'depts'=>$selected_dept,'groups'=>$selected_group,'persons'=>$selected_person));
    	$response=new Response(json_encode($re));
    	$response->headers->set('Content-Type', 'text/json');
    	return $response;
    }
    public function getPersonAction($network_domain)
    {
    	$login_account = $this->get('security.context')->getToken()->getUser()->getUserName();
    	$request = $this->get("request");
    	$curr_circle=$this->get('security.context')->getToken()->getUser()->get_circle_id($network_domain);
    	$da = $this->get("we_data_access");
    	$keyword=$request->get('keyword');
    	$keytype=$request->get('keytype');
    	
    	$sql="select distinct a.nick_name,a.login_account from we_staff a,we_circle_staff b where a.login_account=b.login_account and b.circle_id=? and ";
    	$params=array();
    	array_push($params,$curr_circle);
    	if($keytype=='name')
    	{
    		$sql.="a.nick_name like ? and a.login_account!=? limit 0,10";
    		array_push($params,$keyword."%");
    	}
    	else
    	{
    		$sql.="(a.login_account like ? or a.nick_name like ?) and a.login_account!=? limit 0,10";
    		array_push($params,$keyword."%");
    		array_push($params,$keyword."%");
    	}
    	array_push($params,$login_account);
    	$ds=$da->Getdata('login_account',$sql,$params);
    	$users=$ds['login_account']['rows'];
    	$response=new Response(json_encode($users));
    	$response->headers->set('Content-Type', 'text/json');
    	return $response;
    }
    public function shareSetAction($network_domain)
    {
    	$login_account = $this->get('security.context')->getToken()->getUser()->getUserName();
    	$request = $this->get("request");
    	$isdir=$request->get('isdir');
    	$isfile=$request->get('isfile');
    	$resourceid=$request->get('resourceid');
    	$user_list=$request->get('user_list');
    	$group_list=$request->get('group_list');
    	$dept_list=$request->get('dept_list');
    	$isshare=$request->get('isshare');
    	$resourcetype='0';
      if($isfile=='1')
      {$resourcetype='1';}
      $re=array('s'=>1,'message'=>'');
      if($isshare=='0')
    	{
    		$da = $this->get("we_data_access");
    		$sql="delete from we_doc_share where resourceid=? and resourcetype=? and objecttype!='c'";
    		$params=array($resourceid,$resourcetype);
    	  $da->ExecSQL($sql,$params);
    	}
    	else
    	{
	      try{
		      $this->shareSet('p',$user_list,$resourcetype,$resourceid);
		      $this->shareSet('g',$group_list,$resourcetype,$resourceid);
		      $this->shareSet('d',$dept_list,$resourcetype,$resourceid);
		    }
		    catch(Exception $e){
		    	$re['s']=0;
		    	$re['message']=(string)$e;
		    }
		  }
	    if($re['s']==1){
	    	$arrayJson=$this->getdgAction($network_domain)->getContent();
				$re=json_decode($arrayJson,true);
				$re['s']=1;
	    }
      $response=new Response(json_encode($re));
    	$response->headers->set('Content-Type', 'text/json');
    	return $response;
    }
    public function getConvAction($network_domain)
    {
    	$request = $this->get("request");
    	$isdir=$request->get('isdir');
    	$isfile=$request->get('isfile');
    	$resourceid=$request->get('resourceid');
    	$gettype=$request->get('gettype');
    	if(empty($gettype))
    	{$gettype='recently';}
    	$resourcetype=(!empty($isdir)?'0':'1');
    	$da = $this->get("we_data_access");
    	$sql="select a.*,case when b.login_account=a.conv_account then b.nick_name end as conv_nickname,case
    	 when b.login_account=a.conv_to then b.nick_name end as to_nickname from we_doc_convers a,we_staff b where a.resourceid=? and a.resourcetype=? 
    	 and b.login_account in(a.conv_account,a.conv_to) order by a.conv_time desc";
    	if($gettype=='recently'){
    		$sql.=" limit 0,5";
    	}
    	$params=array($resourceid,$resourcetype);
    	$ds=$da->Getdata('conv',$sql,$params);
    	$re=array('cominfo'=>$ds['conv']['rows']);
    	$response=new Response(json_encode($re));
    	$response->headers->set('Content-Type', 'text/json');
    	return $response; 
    }
    public function publishConvAction($network_domain)
    {
    	$login_account = $this->get('security.context')->getToken()->getUser()->getUserName();
    	$request = $this->get("request");
    	$conv_to=$request->get('conv_to');
    	$isdir=$request->get('isdir');
    	$isfile=$request->get('isfile');
    	$content=$request->get('content');
    	$resourceid=$request->get('resourceid');
    	$da = $this->get("we_data_access");
    	$conv_id=\Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_doc_convers", "conv_id");
    	$sql="insert into we_doc_convers (conv_id,resourceid,resourcetype,conv_account,conv_to,conv_time,content) values(?,?,?,?,?,now(),?)";
    	$params=array();
    	array_push($params,$conv_id);
    	array_push($params,$resourceid);
    	array_push($params,(!empty($isdir)?'0':'1'));
    	array_push($params,$login_account);
    	array_push($params,$conv_to);
    	array_push($params,$content);
    	$re=array('s'=>1,'message'=>'');
    	if(!$da->ExecSQL($sql,$params)){
    		$re['s']='0';
    	}
    	else{
    		$arrayJson=$this->getConvAction($network_domain)->getContent();
    		$re=json_decode($arrayJson,true);
				$re['s']=1;
    	}
    	$response=new Response(json_encode($re));
    	$response->headers->set('Content-Type', 'text/json');
    	return $response;
    }
    public function shareSet($objecttype,$array_list,$resourcetype,$resourceid)
    {
    	    $da = $this->get("we_data_access");
    	    $sql = $resourcetype=="1" ? "select file_name as name from we_files where file_id=? " : "select name from we_doc_dir where id=?";
	    		$ds=$da->Getdata('rn',$sql,array((string)$resourceid)); 
	    		$resourceName =   $ds['rn']['rows'][0]["name"];
	    		 
    			$sql="select objectid from we_doc_share where resourceid=? and objecttype=? and resourcetype=?";
	    		$params=array($resourceid,$objecttype,$resourcetype);
	    		$ds=$da->Getdata('ee',$sql,$params);
	    		$items=$ds['ee']['rows'];
	    		$items_=array();
	    		foreach($items as $item)
	    		{
	    			array_push($items_,$item['objectid']);
	    		}
	    		if(empty($array_list))
	    		{
	    			$array_list=array();
	    		}
    			$result1=array_diff($array_list,$items_);
    			$result2=array_diff($items_,$array_list);
    			$user=$this->get('security.context')->getToken()->getUser();
    			$message="[".$user->nick_name."]共享了".($resourcetype=="1"?"文档":"目录")."[$resourceName]给你！";
    			foreach($result1 as $value)
    			{
    				$id=\Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_doc_share", "id");
    				$sql="insert into we_doc_share (id,resourceid,resourcetype,objecttype,objectid,privacy,createdate) values(?,?,?,?,?,?,now())";
    				$params=array($id,$resourceid,$resourcetype,$objecttype,$value,'r');
    				$da->ExecSQL($sql,$params);
    				$row = array();
    				if($objecttype=="p")  //共享给个人用户
    				{
    					  $ds=$da->Getdata('getjid',"select fafa_jid from we_staff where login_account=?",array((string)$value));
    					  $row = $ds["getjid"]["rows"];
    				}
    				else if($objecttype=="g") //共享给群组
    				{
    					  $ds=$da->Getdata('getjid',"select b.fafa_jid from we_group_staff a, we_staff b where a.login_account=b.login_account and a.group_id=?",array((string)$value));
    					  $row = $ds["getjid"]["rows"];
    				}
    				else if($objecttype=="c") //共享给圈子
    				{
    					  $ds=$da->Getdata('getjid',"select b.fafa_jid from we_circle_staff a, we_staff b where a.login_account=b.login_account and a.circle_id=?",array((string)$value));
    					  $row = $ds["getjid"]["rows"];
    				}
    				else if($objecttype=="d") //共享给部门
    				{
    					  $ds=$da->Getdata('getjid',"select b.fafa_jid from we_staff b where b.dept_id=?",array((string)$value));
    					  $row = $ds["getjid"]["rows"];
    				}    				
    				if( count($row))
    				{
    					  	 foreach($row as $staff) $this->sendMessage($staff["fafa_jid"],$message);
    				}   				
    			}
    			foreach($result2 as $value)
    			{
    				$sql="delete from we_doc_share where resourceid=? and objecttype=? and objectid=? and resourcetype=?";
    				$params=array($resourceid,$objecttype,$value,$resourcetype);
    				$da->ExecSQL($sql,$params);
    			}
    }
    //创建新目录
    public function createDir($id,$parentid,$name,$circleid)
    {
    	try{
    		  $user = $this->get('security.context')->getToken()->getUser();
    		  $da = $this->get("we_data_access");
    		  //判断目录是否已存在
    		  $sql="select id from we_doc_dir where parentid=? and name=? and owner=?";
    		  $rs = $da->GetData("dirs",$sql,array((string)$parentid,(string)$name,(string)$user->getusername()));
    		  if($rs && count($rs["dirs"]["rows"])>0)
    		  {
    		  	return $rs["dirs"]["rows"][0]["id"]; //如果目录已存在，则直接返回目录编号
    		  }
		    	if(empty($id)) $id=(String)SysSeq::GetSeqNextValue($da,"we_doc_dir","id");	    	
		    	
		    	$sql = "insert into we_doc_dir(id,parentid,name,owner,circleid,createdate)values(?,?,?,?,?,now())";
		    	$da->ExecSQL($sql,array((String)$id,(string)$parentid,(string)$name,(string)$user->getusername(),(string)$circleid)); 
		    	return $id;
	    }
	    catch(\Exception $e)
	    {
	      $this->get("logger")->err($e);
	      return 0;
	    }      
    }
    
    public function getGroupByUser($circle_id,$userId) 
    {
      $da = $this->get('we_data_access');
      
      $sql = "select a.group_id, a.circle_id, a.group_name from we_groups a,we_group_staff b where a.group_id=b.group_id and a.circle_id=? and b.login_account=?";
 
      $params = array();
      $params[] = (string)$circle_id;
      $params[] = (string)$userId;
      
      $ds = $da->GetData("we_groups", $sql, $params);
      
      $this->groups = $ds["we_groups"]["rows"];
      
      return;
    }  
    
    private function sendMessage($jid,$message)
    {
        //发送共享通知
          try{
            //发送即时消息
	          Utils::sendImMessage("",$jid,"文档共享通知",$message,$this->container,"","",false,Utils::$systemmessage_code);
	        }catch (\Exception $e) 
		      {
		          $this->get('logger')->err($e);
		      }     	
    }
    
    
    //////////////////////文件查看/////////////////////
    //图片查看
    public function viewAction()
    {
    	$request=$this->get("request");
    	$fileid = $request->get("fileid");
    	$fileinfo = new \Justsy\MongoDocBundle\Controller\DefaultController();
    	$fileinfo->setContainer($this->container);
    	$info = $fileinfo->getFileInfo($fileid);
    	$downpath = $this->generateUrl('JustsyMongoDocBundle_downfile',array('id'=>$fileid));
    	if($info==null) $path="";
    	else{
    	   $s = strtolower($info["filename"]);
         $path = $this->container->getParameter('FILE_WEBSERVER_URL').$fileid;
         if (preg_match("/\.jpg|\.bmp|\.gif|\.jpeg|\.png$/", $s)) return $this->render('JustsyBaseBundle:DocumentMgr:view_image.html.twig',array('path'=> $path));
         //判断大小
          $extend =explode("." , $s);
          if(count($extend)==1) $fix="";
          else $fix=$extend[count($extend)-1];
          try{
             $fix = \Justsy\BaseBundle\Common\MIME::$MIMEOtherTable[".".$fix];
          }
          catch(\Exception $e){}
		      if(("text/plain"==$fix||"text/html"==$fix||"application/x-javascript"==$fix||"*/*"==$fix||empty($fix)) && ((int)$info["size"])<(200*1024))
		      {
		          	return $this->render('JustsyBaseBundle:DocumentMgr:view_other.html.twig',array('type'=>$fix,'path'=> $path,'downpath'=>""));
		      }
		      return $this->render('JustsyBaseBundle:DocumentMgr:view_other.html.twig',array('type'=>$fix,'path'=>"",'downpath'=> $downpath));
      }
    	return $this->render('JustsyBaseBundle:DocumentMgr:view_other.html.twig',array('path'=>"",'downpath'=> ""));
    }
    
    public function savenewdocAction($network_domain)
    {
        //	
        $request=$this->get("request");
        $name=$request->get("newdoc_name");
        $newdoc_parent_dir = $request->get("newdoc_parent_dir");
        
        $user = $this->get('security.context')->getToken()->getUser();
        $login_account = $user->getUserName();
        $circleId = $user->get_circle_id($network_domain);        
        if(empty($newdoc_parent_dir)) $newdoc_parent_dir="c".$circleId;
        $firstChar = substr($newdoc_parent_dir,0,1);
        if($firstChar=="g")
           $gorupid = substr($newdoc_parent_dir,1);
        else 
           $gorupid = "ALL";
        $name =$name.".html" ;
        $path = "/tmp/".$name;
        $txt=$request->get("newdoc_content");
        $txt = iconv("utf-8","gbk",$txt);
				$fp=fopen($path,"w+"); 
				$v = "0";             
				flock($fp,LOCK_EX);                    
				if(fwrite($fp,$txt)) {                 
				    flock($fp,LOCK_UN);                  
				    fclose($fp); 
		        $dm = $this->get('doctrine.odm.mongodb.document_manager'); 
		        $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
		        $doc->setName($name);
		        $doc->setFile($path); 
		        $dm->persist($doc);
		        $dm->flush();
		        $fileid = $doc->getId();	
		        unlink($path);		
		        $sql ="insert into we_files(`circle_id`,`file_ext`,`file_id`,`file_name`,`post_to_group`,`up_by_staff`,`up_date`,`dir`)values(?,?,?,?,?,?,now(),?)";
            $all_params=array((String)$circleId,"html",(String)$fileid,(String)$name,(String)$gorupid,(String)$login_account,(string)$newdoc_parent_dir);
            $da = $this->get('we_data_access');
            $da->ExecSQL($sql,$all_params);
            //当上传到圈子/群组目录时，默认共享到该圈子/群组中
            if($firstChar=='c' || $firstChar=='g')
                $this->saveShare($fileid,"1",substr($newdoc_parent_dir,1),$firstChar,'r');
            $response = new Response(json_encode(array("succeed"=>1,"fileid"=>$fileid)));
			  }			  
			  else
           $response=new Response(json_encode(array("succeed"=>0)));
    	  $response->headers->set('Content-Type', 'text/json');
    	  return $response;        
    }
    
}

