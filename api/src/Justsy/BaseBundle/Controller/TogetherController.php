<?php

namespace Justsy\BaseBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;


class TogetherController extends Controller
{
  public $groups;
  private $pageCount = 5; //每页显示的记录数
    
  public function indexAction($network_domain)
  {
  	$req = $this->get("request");
  	$user = $this->get('security.context')->getToken()->getUser();
    $circleId = $user->get_circle_id($req->get("network_domain"));   	
    
    $list['this']= $this;
    $list['curr_network_domain']=$network_domain;
    $this->getGroupByCircle($circleId,$user->getUserName());
    return $this->render('JustsyBaseBundle:Together:index.html.twig',$list);
  }
  //替换表情、链接、关键词过滤等
  public function replaceFaceEmote($str) 
  {
    $pre = $this->get('templating.helper.assets')->geturl('bundles/fafatimewebase/images/face/');
    
    $str1 = $str;
    $str1 = \Justsy\BaseBundle\Common\KeywordFilter::filterKeyword($str1);
    $str1 = htmlentities($str1, ENT_QUOTES, "UTF-8");
    $str1 = preg_replace('/((?:https?|mailto):\/\/.*?)(\s|&nbsp;|<br|\'|\"|$)/',
                        '<a href="\1" target="_blank">\1</a>\2',
                        $str1);
    $str1 = preg_replace("/@(.*?)(&nbsp;|<br|\'|\"| |\.|\,|\/|\!|\:|\;|：|，|。|！|$)/", 
                        '@<a href="#" class="employee_name">\1</a>\2',
                        $str1);
    $str1 = preg_replace(\Justsy\BaseBundle\Common\Face::getFaceEmoteReg(), 
                        \Justsy\BaseBundle\Common\Face::getFaceEmoteImg("<img alt='' src='${pre}[IMGSRC]'>"), 
                        $str1);
    return $str1;
  }  
  //获得群组
  public function getGroupByCircle($circleId,$user_id) 
  {
      $da = $this->get('we_data_access');
      
      $sql = "select a.group_id, a.circle_id, a.group_name from we_groups a,we_group_staff b where a.group_id=b.group_id and a.circle_id=? and b.login_account=?";
 
      $params = array();
      $params[] = (string)$circleId;
      $params[] = (string)$user_id;
      
      $ds = $da->GetData("we_groups", $sql, $params);
      
      $this->groups = $ds["we_groups"]["rows"];
      
      return;
  }
  
  //置顶活动
  public function getTopAction($network_domain)
  {
  	 $req = $this->get("request");
  	 $group_id = $req->get("groupid");
     $id = 0;
     $content ="";
     $sql = "select conv_root_id,login_account,nick_name,date_format(will_date,'%Y年%m月%d日 %H时%i分') will_date,conv_content,f_cal_date_section(post_date) `hour`,TIMESTAMPDIFF(minute,now(),will_date) minutes,(select count(0) from we_together_staff where conv_id=together_id)usercount
             from we_convers_list inner join we_staff using(login_account) inner join we_together on conv_id=together_id
             where conv_type_id='02' and will_date >= now() ";
     if (empty($group_id))
     {
  	   $user = $this->get('security.context')->getToken()->getUser();       
       $circleId = $user->get_circle_id($req->get("network_domain"));
       $id = $circleId;
       $sql = $sql." and post_to_circle=? order by minutes asc,post_date desc limit 0,5";       
     }
     else
     {
       $id = $group_id ;
       $sql = $sql." and post_to_group=? order by minutes asc,post_date desc limit 0,5";
     }
     $params = array((String)$id);     
     $da = $this->get('we_data_access');
     $table = $da->GetData("together", $sql, $params);
     if ( $table && $table["together"]["recordcount"] >0 )
     {
        for( $i =0 ;$i < $table["together"]["recordcount"]; $i++)
        {
          $title = $table["together"]["rows"][$i]["conv_content"];
          $id = $table["together"]["rows"][$i]["conv_root_id"];
          $minute = (int)$table["together"]["rows"][$i]["minutes"];
          $distance ='';
          //if (mb_strlen($title,"utf-8")>15)
          //   $title = mb_substr($title,0,15,"utf-8")."...";
          $url = $this->get('router')->generate('JustsyBaseBundle_together_getSigne', array('id' => $id));
          if($i==0)
          {
		          if ( floor($minute/1440) > 0)
		             $distance ="距活动：".((String)floor($minute/1440))."天".((String)floor(($minute%1440)/60))."小时";
		          else
		          {
		             if ( floor($minute/60 ) == 0)
		               $distance ="距活动：<span style='color:red;'>".((String)floor($minute%60))."分钟</span>";
		             else if ( floor($minute/60 ) < 5)
		               $distance ="距活动：<span style='color:red;'>".((String)floor($minute/60))."小时".((String)floor($minute%60))."分钟</span>";
		             else
		               $distance ="距活动：".((String)floor($minute/60))."小时".((String)floor($minute%60))."分钟";
		          }
		          $content .= "<div class='right-activity-title'><a style=\"cursor:pointer;\" title=\"".$title."\" data-toggle=\"modal\" show=false onclick=\"ViewTogetherDetails('".$url."')\">".$title."</a></div>".
		                      "<div class='right-activity-person'>由 <a class='employee_name' login_account='".$table["together"]["rows"][$i]["login_account"]."'>".$table["together"]["rows"][$i]["nick_name"]."</a> 发起</div>".
		                      " <div class='rightallbox clearfix'> <span class='rightallboxpart'></span>".
		                      "<div class='right-time'>".$table["together"]["rows"][$i]["will_date"]."</div>".
		                      "<div class='right-activity-list'>".$distance."</div>".
		                      "<div class='right-vote-list'><a href='javascript:;' class='right-vote-button' onclick='ViewTogetherDetails(\"".$url."\")'>我要参加</a></div>".
		                      "<div class='right-vote-number'><span class='right-vote-blue'>".$table["together"]["rows"][$i]["usercount"]."</span>人参与</div></div>";
		      }
          else
          {
	          $content .= "<div class='right-activity-title'><a title=\"".$title."\" style=\"cursor:pointer;\" data-toggle=\"modal\" show=false onclick=\"ViewTogetherDetails('".$url."')\">".$title."</a></div>";
          }
        }
     }
     else
     {
        $content = "<div style='text-align:center'><span>当前无最新活动</span></div>";
     }
  	 return new Response($content);
  }
  
  //活动详细
  public function getSigneAction(Request $request)
  {
    $conv_root_id = $request->get('id');

    $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
    $user = $this->get('security.context')->getToken()->getUser();
    $sqls = array();
    $all_params = array();    
    $sql = "select a.conv_id, a.login_account, a.post_date, a.conv_type_id, a.conv_root_id, a.conv_content, 
  a.post_to_group, a.post_to_circle, a.reply_to, a.copy_num, a.reply_num, a.atten_num,
  b.nick_name, b.photo_path, concat('$FILE_WEBSERVER_URL', ifnull(b.photo_path, '')) photo_url,
  f_cal_date_section(a.post_date) post_date_d, ep.eshortname, rp.nick_name reply_to_name, wsa.atten_id
from we_convers_list a
inner join we_staff b on a.login_account=b.login_account
inner join we_enterprise ep on b.eno=ep.eno
left  join we_staff rp on rp.login_account=a.reply_to
left  join we_staff_atten wsa on wsa.login_account=? and wsa.atten_type='02' and wsa.atten_id=a.conv_id
where a.conv_root_id=?
order by (conv_id = conv_root_id) desc, (0+conv_id) desc";
    $params = array();
    $params[] = (string)$user->getUserName();
    $params[] = (string)$conv_root_id;
    
    $sql .= " limit 0, 10 "; //最多显示10条回复
    
    $sqls[] = $sql;
    $all_params[] = $params;
$sql = "select a.attach_id, b.file_name, b.file_ext
from we_convers_attach a, we_files b
where a.attach_id=b.file_id
  and a.attach_type='0' and a.conv_id=?";
    $params = array();
    $params[] = (string)$conv_root_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;    
    $sql = "select together_id, title, will_date, will_dur, will_addr, together_desc
from we_together a
where a.together_id=?";
    $params = array();
    $params[] = (string)$conv_root_id;
    
    $sqls[] = $sql;
    $all_params[] = $params;
    
    $da = $this->get('we_data_access');
    $ds = $da->GetDatas(array("we_convers_list","we_convers_attach", "we_together"), $sqls, $all_params);
    
    if (count($ds["we_convers_list"]["rows"]) == 0 || count($ds["we_together"]["rows"]) == 0) return new Response("");

    return $this->render('JustsyBaseBundle:CDisplayArea:together.html.twig', array('this' => $this, 'ds' => $ds));
  }
  
  //活动展示
  public function getAllTogetherAction($network_domain) 
  {
  	 $request = $this->get("request");
     $pageIndex = $request->get('startIndex');
     $groupid = $request-> get('groupid');
     $onself = $request-> get('onself');  //我发布的
     $user = $this->get('security.context')->getToken()->getUser();
     $sql="";
     $params;
     if ($groupid == null or $groupid=="")
     {
        $circleId = $user->get_circle_id($request->get("network_domain"));
        if ($onself ==null or $onself=="")
        {
          $sql = "select a.conv_root_id from we_convers_list a inner join we_together b on a.conv_root_id=b.together_id
                where a.post_to_circle=? and conv_type_id='02' 
                order by b.will_date desc";
          $params = array((string)$circleId);
        }
        else  //以下为我发布的活动
        {
          $sql = "select a.conv_root_id from we_convers_list a inner join we_together b on a.conv_root_id=b.together_id
                where a.login_account=? and a.post_to_circle=? and conv_type_id='02' 
                order by b.will_date desc";        
          $params = array((String)$user->getUsername(),(string)$circleId);
        }
     }
     else
     {
        $sql = "select a.conv_root_id  from we_convers_list a inner join we_together b on a.conv_root_id=b.together_id
                where a.post_to_group=? and conv_type_id='02' order by b.will_date desc";             
        $params = array((String)$groupid);
     }
     $da = $this->get('we_data_access');
     $pageIndex = $pageIndex*$this->pageCount;
     $sql .= " limit ".$pageIndex.",".$this->pageCount;
     $ds = $da->GetData("we_convers_list", $sql, $params);
     //生成html返回
     $conv_root_ids = array_map(function ($row){ return $row["conv_root_id"];},$ds["we_convers_list"]["rows"]);
     $trend = $request->get('trend');
     return $this->forward("JustsyBaseBundle:CDisplayArea:getConv", array("conv_root_ids" => $conv_root_ids,'trend'=> $trend));
  }
  
  //页数管理
  public function pageControlAction(Request $request)
  {
     $groupid = $request-> get('groupid');
     $onself = $request-> get('onself');  //我发布的
     $user = $this->get('security.context')->getToken()->getUser();
     $sql="";
     $params;
     if ($groupid == null or $groupid=="")  //圈子
     {
     	  $user = $this->get('security.context')->getToken()->getUser();
     	  $circleId = $user->get_circle_id($request->get("network_domain"));
        if ($onself ==null or $onself=="")   //判断是否是自己发布的
        {
          $sql = "select count(*) rowscount from we_convers_list a inner join we_together b on a.conv_root_id=b.together_id
                 where a.post_to_circle=? and conv_type_id='02'";
          $params = array((String)$circleId);
        }
        else  //以下为我发布的活动
        {
          $sql = "select count(*) rowscount from we_convers_list a inner join we_together b on a.conv_root_id=b.together_id
                where a.login_account=? and a.post_to_circle=? and conv_type_id='02'";            
          $params = array((String)$user->getUsername(),(string)$circleId);
        }
     }
     else  //群组发布的
     {
        $sql = "select count(*) rowscount from we_convers_list a inner join we_together b on a.conv_root_id=b.together_id
                where a.post_to_group=? and conv_type_id='02'";                
        $params = array((String)$groupid);
     } 
     $da = $this->get('we_data_access');
     $dataset = $da->GetData("table", $sql, $params);
     $count = (int)$dataset["table"]["rows"][0]["rowscount"];
     
     if ($count == 0)
     {
        $html = "<br>你还没有发布或参加过任何活动！尽快参与吧！";
  	    return new Response($html);       
     }
     
     $count = ceil($count / $this->pageCount);
     $html="";
     if ( $count > 1)
     {
       $html = "<div class=\"pagination pagination-right\"><ul>";
       for($i=0;$i< $count;$i++)
       {
          $html = $html."<li><a href='javascript:getPage($i)'>".($i+1)."</a></li>";
       }
       $html .= "</ul></div>";
     } 
  	 return new Response($html);
  }
}