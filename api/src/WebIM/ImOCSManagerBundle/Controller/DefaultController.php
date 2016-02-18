<?php

namespace WebIM\ImOCSManagerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
	  var $service_max_count_limit = 3;//企业默认在线客服帐号最大数量限制
	  var $sale_max_count_limit = 3;//企业默认虚拟销售帐号最大数量限制
	  var $front_max_count_limit = 2;//企业默认虚拟前台帐号最大数量限制
	  public function admin_seatasaveAction()
	  {
	  	  $eno=$this->getRequest()->query->get('eno', "");
	  	  $name=$this->getRequest()->query->get('name', "");
	  	  $etype=$this->getRequest()->query->get('etype',"");
	  	  $empid = $this->getRequest()->query->get('empid',"");
	  	  
	  	  if($eno=="" || $name=="" || $etype=="")
	  	  {
	  	      return new Response("({\"succeed\":false,\"msg\":\"企业号、昵称不能为空\"})");
	  	  }
	  	  else{
	  	      $result=$this->saveWebData($empid,$eno,$name,$etype);
	  	      return new Response($result);
	  	  }
	  }
	 
	  //坐席分配管理
    public function admin_seatassignAction($eno)
    {
    	  $result = $this->getWebData($eno);
    	  $serviceList="";
    	  $saleList="";
    	  $frontList="";
    	  $service_add_innerHtml = "";
    	  $sale_add_innerHtml = "";
    	  $front_add_innerHtml = "";
       	if($result=="500"||$result=="404")
    	  {
    	      $serviceList="该企业还未认证！";
    	      $saleList="该企业还未认证！";
    	      $frontList="该企业还未认证！";
    	  }
    	  else if($result=="")
    	  {
    	      $serviceList="提示：服务器忙，请稍后再试！";
    	      $saleList="提示：服务器忙，请稍后再试！";
    	      $frontList="提示：服务器忙，请稍后再试！";
    	  }
    	  else{
    	  	        $servicelist_cnt = 0;
    	  	        $salelist_cnt=0;
    	  	        $frontlist_cnt=0;
			    	      for($i=1;$i<count($result);$i++)
			    	      {
			    	          $empid =	$result[$i]->{'employeeid'};
			    	          $account =	str_replace(".fafacn.com","",$result[$i]->{'account'});
			    	          $text = "<li id=\"$empid\"><div style='width:300px;float:left'>".$this->getResourceImage($result[$i]->{'resource'},$result[$i]->{'state'}).$this->getStateImage($result[$i]->{'state'})."<a title=\"\">".$result[$i]->{'name'}."</a><span style='padding-left: 10px;' title='设置聊天时显示的昵称'>(<a href=\"javascript:editname('$empid')\"><img src='/bundles/fafawebimimocsmanager/images/modify.png' style='vertical-align: middle;'/>编辑名称</a>)</span></div><span style='color:#C0C0C0'><img src='/bundles/fafawebimimocsmanager/images/relation.gif' style='vertical-align: middle;'/>&nbsp;&nbsp;&nbsp;&nbsp;";
			    	          $text2 = "<span>未关联帐号&nbsp;&nbsp;</span><span><A href=\"javascript:relation('$empid')\">立即关联</A></span></span></li>";
			    	          $text3 = "<span>已关联帐号&nbsp;&nbsp;</span><span><A title='更改人员' href=\"javascript:changerelation('$empid')\">$account</A></span></span></li>";
			    	          if(strpos($empid,"service") && $servicelist_cnt< $this->service_max_count_limit){
			    	          	  if($account=="" || $account=="service@".$eno.".fafacn.com")
			    	          	      $serviceList .= $text.$text2;
			    	          	  else
			    	                  $serviceList .= $text.$text3;
			    	              $servicelist_cnt = $servicelist_cnt+1;
			    	          }
			    	          else if(strpos($empid,"sale") && $salelist_cnt< $this->sale_max_count_limit){
			    	          	  if($account=="" || $account=="sale@".$eno.".fafacn.com")
			    	          	      $saleList .= $text.$text2;
			    	          	  else    	         
			    	                  $saleList .= $text.$text3;
			    	              $salelist_cnt = $salelist_cnt+1;
			    	          }
			    	          else if(strpos($empid,"front")  && $frontlist_cnt< $this->front_max_count_limit){
			    	          	  if($account=="" || $account=="front@".$eno.".fafacn.com")
			    	          	      $frontList .= $text.$text2;
			    	          	  else 		    	         
			    	                  $frontList .= $text.$text3;
			    	              $frontlist_cnt = $frontlist_cnt+1;
			    	          }
			    	      }
			    	      $addImg = "<img style=\"vertical-align:middle\" width=16 height=16 src=\"/bundles/fafawebimimocsmanager/images/add.png\">";
			    	      $mayCnt = $this->service_max_count_limit-$servicelist_cnt;
			    	      if($mayCnt>0)
			    	         $service_add_innerHtml="<a href=\"javascript:addEmp('serviceList')\" title=\"添加新帐号\">".$addImg."</a><span color='#c0c0c0'>&nbsp;&nbsp;(还可添加<b>".$mayCnt."</b>个在线客服帐号)</span>";
			    	      else
			    	         $service_add_innerHtml = "<span color='#c0c0c0'>&nbsp;&nbsp;(你已添加了<b>".$this->service_max_count_limit."</b>个在线客服帐号，不能继续添加)</span>";
			    	     	$mayCnt = $this->sale_max_count_limit-$salelist_cnt;
			    	      if($mayCnt>0)
			    	         $sale_add_innerHtml="<a href=\"javascript:addEmp('saleList')\" title=\"添加新帐号\">".$addImg."</a><span color='#c0c0c0'>&nbsp;&nbsp;(还可添加<b>".$mayCnt."</b>个在线销售帐号)</span>";
			    	      else
			    	         $sale_add_innerHtml = "<span color='#c0c0c0'>&nbsp;&nbsp;(你已添加了<b>".$this->sale_max_count_limit."</b>个在线销售帐号，不能继续添加)</span>";
			    	     	$mayCnt = $this->front_max_count_limit-$frontlist_cnt;
			    	      if($mayCnt>0)
			    	         $front_add_innerHtml="<a href=\"javascript:addEmp('frontList')\" title=\"添加新帐号\">".$addImg."</a><span color='#c0c0c0'>&nbsp;&nbsp;(还可添加<b>".$mayCnt."</b>个前台服务帐号)</span>";
			    	      else
			    	         $front_add_innerHtml = "<span color='#c0c0c0'>&nbsp;&nbsp;(你已添加了<b>".$this->front_max_count_limit."</b>个前台服务帐号，不能继续添加)</span>";
			    	         
    	  }
        return $this->render('WebIMImOCSManagerBundle:Default:admin_seatassign.html.twig',
              array('eno'=> $eno,'error' => "",'serviceList'=> $serviceList,'saleList'=> $saleList,'frontList'=> $frontList,
                    'cnt_service'=> $service_add_innerHtml,
                    'cnt_sale'=> $sale_add_innerHtml,
                    'cnt_front'=> $front_add_innerHtml
                    ));
    }
    

    private function getAccountByResource($lst)
    {
        	if($lst->{'state'}=="0")
        	   return $lst->{'account'};
        	else
        	  return $lst->{'account'}."/".$lst->{'resource'};
    }
    private function getResourceImage($res,$state)
    {
    	   if($res=="FaFaWin" || $state=="0")
    	      return "<span class=\"phone\" ><img src=\"/bundles/fafawebimimocsmanager/images/blank.png\" width=\"9\" height=\"16\"/></span>";
    	   else if($res=="FaFaWeb")
    	      return "<span class=\"phone\" title=\"WeFaFa在线\"><img src=\"/bundles/fafawebimimocsmanager/images/web.png\" width=\"9\" height=\"16\"/></span>";
    	   else
    	      return "<span class=\"phone\" title=\"手机在线\"><img src=\"/bundles/fafawebimimocsmanager/images/phone.png\" width=\"9\" height=\"16\"/></span>";
    }
    private function getStateImage($state)
    {
    	   if($state=="1")
    	      return "<span class=\"status\"  title=\"在线\"><img src=\"/bundles/fafawebimimocsmanager/images/online.png\" width=\"18\" height=\"18\" /></span>";
    	   else
    	      return "<span class=\"status\"  title=\"离线\"><img src=\"/bundles/fafawebimimocsmanager/images/offline.png\" width=\"18\" height=\"18\" /></span>";
    }
    private function getWebData($orgid)
    {
      try 
      {
        $re_reg_str = file_get_contents("http://www.fafacn.com:800/controller.yaws?eno=".$orgid."&method=service_org:getPublicAccount");
        $re_reg = str_replace("(", "", $re_reg_str);
        $re_reg = str_replace(")", "", $re_reg);
        $re_reg = json_decode($re_reg);
        if (!$re_reg->{'succeed'})
        {
          //可能服务器出错了
          return $re_reg->{'msg'};
        }
        else
        {
          return $re_reg->{'data'};
        }
      } 
      catch (\Exception $ex) 
      {
         $this->get('logger')->err($ex);
         //$this->hintmsg = "提示：服务器忙，请稍后再试！";
         return "";
      }
    }
    //保存基本数据
    private function saveWebData($empid,$orgid,$name,$etype)
    {
      try 
      {
      	$re_reg_str = file_get_contents("http://www.fafacn.com:800/controller.yaws?empid=".$empid."&eno=".$orgid."&name=".$name."&type=".$etype."&method=service_org:savePublicAccount");
        return $re_reg_str;
      } 
      catch (\Exception $ex) 
      {
         $this->get('logger')->err($ex);
         return "({\"succeed\":false,\"msg\":\"服务器忙，请稍后再试！\"})";
      }
    }
    //保存关联的帐号
    public function admin_saveAccountAction()
    {
	  	  $empid=$this->getRequest()->query->get('empid', "");
	  	  $account=$this->getRequest()->query->get('account', "");
	  	  if($empid=="")
	  	  {
	  	      return new Response("({\"succeed\":false,\"msg\":\"人员编号不能为空\"})");
	  	  }else{
			      try 
			      {
			      	$re_reg_str = file_get_contents("http://www.fafacn.com:800/controller.yaws?empid=".$empid."&account=".$account."&method=service_org:savePublicAccount");
			        return new Response($re_reg_str);
			      } 
			      catch (\Exception $ex) 
			      {
                $this->get('logger')->err($ex);
                return new Response( "({\"succeed\":false,\"msg\":\"服务器忙，请稍后再试！\"})");
            }
        }
    }       
}


