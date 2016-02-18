<?php

namespace WebIM\ImOCSManagerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WordController extends Controller
{
	  var $always_word_max_count_limit = 10;//常用语总数量限制。对于所有企业默认最多可设置10条
	  //常用语管理
	  public function amdin_wordAction()
	  {	  	  
	  	  $eno=$this->getRequest()->query->get('eno', "");
	  	  $conn = $this->get('database_connection');
      	$result =$conn->fetchAll("select * from we_ocs_word where eno='$eno'"); 
      	if ($conn)
        {
            $conn->close();
            $conn = null;
        }
	      $list="";
	      $add_innerHtml="";
	      if($result=="500"||$result=="404")
    	  {
    	      $list="该企业还未认证！";
    	  }
    	  else if($result=="")
    	  {
    	      $list="提示：服务器忙，请稍后再试！";
    	  }
    	  else{
    	  	  $list_cnt=0;
    	  	  for($i=0;$i<count($result);$i++)
    	  	  {
    	  	  	  if($list_cnt>=$this->always_word_max_count_limit) break;
    	  	  	  $list .= "<li id='word_".$result[$i]["id"]."'><span>".($i+1)."&nbsp;&nbsp;&nbsp;&nbsp;</span><span><a href=\"javascript:editname('".$result[$i]["id"]."')\">".$result[$i]["words"]."&nbsp;&nbsp;&nbsp;&nbsp;</a></span></li>";
    	  	  	  $list_cnt = $list_cnt+1;
    	  	  }
		        $addImg = "<img style=\"vertical-align:middle\" width=16 height=16 src=\"/bundles/fafawebimimocsmanager/images/add.png\">";
					  $mayCnt = $this->always_word_max_count_limit-$list_cnt;
					  if($mayCnt>0)
					    	  $add_innerHtml="<a href=\"javascript:addEmp('serviceList')\" title=\"添加常用语\">".$addImg."</a><span color='#c0c0c0'>&nbsp;&nbsp;(还可添加<b>".$mayCnt."</b>条常用语)</span>";
					  else
					    	  $add_innerHtml = "<span color='#c0c0c0'>&nbsp;&nbsp;(你已添加了<b>".$this->always_word_max_count_limit."</b>条常用语，不能继续添加)</span>";
					  
    	  }
    	  return $this->render('WebIMImOCSManagerBundle:Default:admin_word.html.twig', array('eno'=> $eno,'error' => "",'list'=> $list,'addinfo'=> $add_innerHtml));
	  }
	  
    public function getJsonDataAction()
    {
      try 
      {
      	$conn = $this->get('database_connection');
      	$eno=$this->getRequest()->query->get('eno', "");
      	$result =$conn->fetchAll("select * from we_ocs_word where eno='$eno'"); 
      	if ($conn)
        {
            $conn->close();
            $conn = null;
        }
        $list = "[";
        for($i=0;$i<count($result);$i++)
    	  {
    	  	 if(strlen($list)>1) $list .= ",";
    	  	 $list .= "\"".$result[$i]["words"]."\"";
    	  }
    	  $list .= "]";     
        return new Response( "({\"succeed\":true,\"data\":$list})");
      } 
      catch (\Exception $ex) 
      {
         $this->get('logger')->err($ex);
         return  new Response("({\"succeed\":false,\"msg\":\"服务器忙，请稍后再试！\"})");
      }
    }
    //保存基本数据
    public function saveDataAction()
    {
      try 
      {
      	$conn = $this->get('database_connection');
      	$id=$this->getRequest()->query->get('wordid', "");
      	if(strlen($id)==0)      	
      	  $conn->executeUpdate("insert into we_ocs_word(eno,words)values('".$this->getRequest()->query->get('eno', "")."','".$this->getRequest()->query->get('words', "")."')"); 
      	else
      	  $conn->executeUpdate("update we_ocs_word set words='".$this->getRequest()->query->get('words', "")."' where id=".$id); 
      	if ($conn)
        {
            $conn->close();
            $conn = null;
        }
        return new Response( "({\"succeed\":true,\"msg\":\"\"})");
      } 
      catch (\Exception $ex) 
      {
         $this->get('logger')->err($ex);
         return  new Response("({\"succeed\":false,\"msg\":\"服务器忙，请稍后再试！\"})");
      }
    }
          
}


