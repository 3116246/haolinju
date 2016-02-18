<?php

namespace Justsy\BaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\BaseBundle\Common\Utils;

//会话展示
class CDisplayAreaController extends Controller {

    //替换表情、链接、关键词过滤等
    public function replaceFaceEmote($str) {
        $pre = $this->get('templating.helper.assets')->geturl('bundles/fafatimewebase/images/face/');

        $conv = new \Justsy\BaseBundle\Business\Conv();
        $str1 = $conv->replaceContent($str, $pre);

        return $str1;
    }

    //判断文档类型
    //返回 文档(1)、图片(2)、音视频(3)3类
    public function getFileType($filename) {
        $re = "1";

        if (preg_match(\Justsy\BaseBundle\Common\MIME::getMIMEImgReg(), strtolower($filename)))
            $re = "2";
        else if (preg_match(\Justsy\BaseBundle\Common\MIME::getMIMEMediaReg(), strtolower($filename)))
            $re = "3";

        return $re;
    }

    //返回文件名
    public function getFileIcon($filename) {
        return \Justsy\BaseBundle\Common\MIME::getFileIcon($filename);
    }

    //根据request指定的自己新建conv_id，取得该会话并返回
    public function getNewOneConvAction() {
        $request = $this->getRequest();
        $user = $this->get('security.context')->getToken()->getUser();
        $conv_id = $request->get('conv_id');

        $sql = "select a.conv_id, a.conv_type_id, b.template_controller,case when ifnull(c.conv_id,'0')='0' then '0' else '1' end istop 
from we_conv_template b,we_convers_list a 
left join we_conv_top c on a.conv_id=c.conv_id and c.timeout>now() 
where a.conv_type_id=b.conv_type_id
  and a.conv_id = ?
  and a.login_account=?
order by a.post_date desc, (0+a.conv_id) desc";
        $params = array();
        $params[] = (string) $conv_id;
        $params[] = (string) $user->getUserName();

        $da = $this->get('we_data_access');
        $ds = $da->GetData("we_convers_list", $sql, $params);
 $trend=$this->getRequest()->get("trend");
 
        return $this->render('JustsyBaseBundle:CDisplayArea:index.html.twig', array('we_convers_list' => $ds["we_convers_list"]["rows"],'trend'=>$trend));
    }
  public function getNewOneConvPcAction(){
  	 $request = $this->getRequest();
        $user = $this->get('security.context')->getToken()->getUser();
        $conv_id = $request->get('conv_id');

        $sql = "select a.conv_id, a.conv_type_id, concat(b.template_controller,'Pc') as template_controller,case when ifnull(c.conv_id,'0')='0' then '0' else '1' end istop 
from we_conv_template b,we_convers_list a 
left join we_conv_top c on a.conv_id=c.conv_id and c.timeout>now() 
where a.conv_type_id=b.conv_type_id
  and a.conv_id = ?
  and a.login_account=?
order by a.post_date desc, (0+a.conv_id) desc";
        $params = array();
        $params[] = (string) $conv_id;
        $params[] = (string) $user->getUserName();

        $da = $this->get('we_data_access');
        $ds = $da->GetData("we_convers_list", $sql, $params);
				$trend = $this->getRequest()->get("trend");
        return $this->render('JustsyBaseBundle:CDisplayArea:index.html.twig', array('we_convers_list' => $ds["we_convers_list"]["rows"],'trend'=>$trend));
  }
    public function getOneConv($conv_id) {
        $sql = "select a.conv_id, a.conv_type_id, b.template_controller
from we_convers_list a, we_conv_template b
where a.conv_type_id=b.conv_type_id
  and a.conv_id = ?
order by a.post_date desc, (0+a.conv_id) desc";
        $params = array();
        $params[] = (string) $conv_id;

        $da = $this->get('we_data_access');
        $ds = $da->GetData("we_convers_list", $sql, $params);
 	$trend = $this->getRequest()->get("trend");
        return $this->render('JustsyBaseBundle:CDisplayArea:index.html.twig', array('we_convers_list' => $ds["we_convers_list"]["rows"],'trend'=>$trend));
    }

    //根据conv_root_ids展示会话
    //$conv_root_ids 会话根ID string array
    //$isshow_relation_static_trend 是否获取人脉圈子系统固定动态。只有当打开人脉圈子并且动态数少于15条时才会有
    public function getConvAction($conv_root_ids,$isshow_relation_static_trend=false) {
        //取出每个根会话
        $sqlwherein = array();
        $params = array();
        
        $sqlwherein[] = '?';
        $params[] = '###';
        for ($i = 0; $i < count($conv_root_ids); $i++) {
            $sqlwherein[] = '?';
            $params[] = (string) $conv_root_ids[$i];
        }

        $sqlwhereinstr = join(',', $sqlwherein);
        $da = $this->get('we_data_access');
        
        $sql = "select a.conv_id, a.conv_type_id, b.template_controller  
from we_conv_template b,we_convers_list a 
where a.conv_type_id=b.conv_type_id
  and a.conv_id in ($sqlwhereinstr)
order by a.post_date desc";
        
        $ds = $da->GetData("we_convers_list", $sql, $params);
        
        $tmpArr = array("we_convers_list"=>array("rows"=>array()));
        if($isshow_relation_static_trend)
        {
        	 $tmp = new \Justsy\BaseBundle\Management\StaticTrendMgr($da,null);
        	 $list=$tmp->GetStaticRelationTrend();
        	 for($i=0; $i<count($list); $i++)
        	 {
               //$sql += " union select '".$list[$i]["info_id"]."' conv_id, '00' conv_type_id, 'JustsyBaseBundle:CDisplayArea:getTrend' template_controller  ";
               $ds["we_convers_list"]["rows"][]=array("conv_id"=> $list[$i]["info_id"],"conv_type_id"=>"00","template_controller"=>"JustsyBaseBundle:CDisplayArea:getTrend");
           }
        }
        $trend = $this->getRequest()->get("trend");
        return $this->render('JustsyBaseBundle:CDisplayArea:index.html.twig', array('we_convers_list' => $ds["we_convers_list"]["rows"],'trend'=>$trend));
    }
    
    //PC端
    //根据conv_root_ids展示会话
    //$conv_root_ids 会话根ID string array
    public function getConvPcAction($conv_root_ids) {
    		$user = $this->get('security.context')->getToken()->getUser();
        //取出每个根会话
        $sqlwherein = array();
        $params = array();

        $sqlwherein[] = '?';
        $params[] = '###';
        for ($i = 0; $i < count($conv_root_ids); $i++) {
            $sqlwherein[] = '?';
            $params[] = (string) $conv_root_ids[$i];
        }

        $sqlwhereinstr = join(',', $sqlwherein);
        $sql = "select a.conv_id, a.conv_type_id, concat(b.template_controller,'Pc') as  template_controller 
from we_conv_template b,we_convers_list a 
where a.conv_type_id=b.conv_type_id
  and a.conv_id in ($sqlwhereinstr)
order by a.post_date desc";

        $da = $this->get('we_data_access');
        $ds = $da->GetData("we_convers_list", $sql, $params);
				$trend = $this->getRequest()->get("trend");
        return $this->render('JustsyBaseBundle:CDisplayArea:index.html.twig', array('we_convers_list' => $ds["we_convers_list"]["rows"],'trend'=> $trend));
    }

    //根据conv_id过滤赞美
    public function filterLikeRows($conv_id, $rows) {
        return array_values(array_filter($rows, function ($row) use ($conv_id) {
                            return $row["conv_id"] == $conv_id;
                        }));
    }

    //获取正在进行的投票
    public function getTopVoteAction($network_domain) {
        $req = $this->get("request");
        $group_id = $req->get("groupid");
        $id = 0;
        $content = "";
        $sql = "select conv_root_id,login_account,nick_name,date_format(v.finishdate,'%Y年%m月%d日 %H时%i分') finishdate,conv_content," .
                "f_cal_date_section(post_date) `hour`,TIMESTAMPDIFF(minute,now(),v.finishdate) minutes," .
                "(select count(0) from we_vote_user where conv_id=vote_id ) usercount, (select count(0) from we_vote_user where conv_id=vote_id and login_account=?) isin" .
                " from we_convers_list inner join we_staff using(login_account) inner join we_vote v on conv_id=v.vote_id 
             where conv_type_id='03' and finishdate >= now() ";
        $user = $this->get('security.context')->getToken()->getUser();
        if (empty($group_id)) {

            $circleId = $user->get_circle_id($req->get("network_domain"));
            $id = $circleId;
            $sql = $sql . " and post_to_circle=? order by minutes asc,post_date desc limit 0,6";
        } else {
            $id = $group_id;
            $sql = $sql . " and post_to_group=? order by minutes asc,post_date desc limit 0,6";
        }
        $params = array((String) $user->getUserName(), (String) $id);
        $da = $this->get('we_data_access');
        $table = $da->GetData("vote", $sql, $params);
        if ($table && $table["vote"]["recordcount"] > 0) {
            for ($i = 0; $i < $table["vote"]["recordcount"]; $i++) {
                $currow = $table["vote"]["rows"][$i];
                $title = $currow["conv_content"];
                $title2 = $title;
                $id = $currow["conv_root_id"];
                $minute = (int) $currow["minutes"];
                $distance = '';
                //if (mb_strlen($title2,'utf-8')>15)
                //     $title2 = mb_substr($title2,0,15,"utf-8")."...";
                $url = $this->get('router')->generate('JustsyBaseBundle_component_cdisparea_getvote', array('conv_root_id' => $id));
                if ($i == 0) {
                    if (floor($minute / 1440) > 0)
                        $distance = "还有 <B>" . ((String) floor($minute / 1440)) . "天" . ((String) floor(($minute % 1440) / 60)) . "小时</B> 截止";
                    else {
                        if (floor($minute / 60) == 0)
                            $distance = "还有 <B><span style='color:red;'>" . ((String) floor($minute % 60)) . "分钟</span></B> 截止";
                        else if (floor($minute / 60) < 5)
                            $distance = "还有 <B><span style='color:red;'>" . ((String) floor($minute / 60)) . "小时" . ((String) floor($minute % 60)) . "分钟</span></B> 截止";
                        else
                            $distance = "还有 <B>" . ((String) floor($minute / 60)) . "小时" . ((String) floor($minute % 60)) . "分钟</B> 截止";
                    }
                    $content .= "<div class='right-vote-title'><a title=\"" . $title . "\" style=\"cursor:pointer;\" data-toggle=\"modal\" show=false onclick=\"ViewVoteDetails('" . $url . "','$id')\">" . $title . "</a></div>" .
                            "<div class='right-vote-person'>由 <a class='employee_name' login_account='" . $currow["login_account"] . "'>" . $currow["nick_name"] . "</a> 发起</div>" .
                            "<div class='rightallbox clearfix'><span class='rightallboxpart'></span><div class='right-time'>" . $distance . "</div>" .
                            "<div class='right-vote-list'><a href='javascript:;' class='right-vote-button' onclick='ViewVoteDetails(\"" . $url . "\",\"$id\")'>" . ($currow["isin"] == 1 ? "投票结果" : "我要投票") . "</a></div>" .
                            "<div class='right-vote-number'><span class='right-vote-blue'>" . $currow["usercount"] . "</span>人投票</div>" .
                            "</div><ul class='right-vote-other'>";
                }
                else {
                    $content .= "<li><a title=\"" . $title . "\" style=\"cursor:pointer;\" data-toggle=\"modal\" show=false onclick=\"ViewVoteDetails('" . $url . "','$id')\">" . $title2 . "</a></li>";
                }
            }
            $content .= "</ul>";
        } else {
            $content = "<div style='text-align:center'><span>当前无最新投票</span></div>";
        }
        return new Response($content);
    }

    //动态信息展示
    public function getTrendAction($conv_root_id) {
        $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
        $user = $this->get('security.context')->getToken()->getUser();

        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();
        if($conv_root_id<=1)
        {
        	  $ds = $conv->getRelationSysTrend($da, $user, $conv_root_id, $FILE_WEBSERVER_URL);
        }
        else $ds = $conv->getTrend($da, $user, $conv_root_id, $FILE_WEBSERVER_URL);

        if (count($ds["we_convers_list"]["rows"]) == 0)
            return new Response("");
        if($ds["we_convers_list"]["rows"][0]["auth_level"]!='S')
        {
        	  $ds["we_convers_list"]["rows"][0]["vip_level"] = \Justsy\BaseBundle\Common\ExperienceLevel::getLevel($ds["we_convers_list"]["rows"][0]["total_point"]);
        }
        else
        {
            $ds["we_convers_list"]["rows"][0]["vip_level"] ="1";//
        }
        $css_level = (int) ($ds["we_convers_list"]["rows"][0]["we_level"] / 10);
        //var_dump($this->getRequest()->get("trend")." 官方动态");
        $para= array('this' => $this, 'ds' => $ds, 'css_level' => $css_level,"trend"=>$this->getRequest()->get("trend"));
        return $this->render('JustsyBaseBundle:CDisplayArea:trend.html.twig',$para);
    }
    
    //PC端动态信息展示
    public function getTrendPcAction($conv_root_id) {
    		
        $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
        $user = $this->get('security.context')->getToken()->getUser();
    		$trend = $this->getRequest()->get("trend");
        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();
        $ds = $conv->getTrend($da, $user, $conv_root_id, $FILE_WEBSERVER_URL);

        if (count($ds["we_convers_list"]["rows"]) == 0)
            return new Response("");
        if($ds["we_convers_list"]["rows"][0]["auth_level"]!='S')
        {
        	  $ds["we_convers_list"]["rows"][0]["vip_level"] = \Justsy\BaseBundle\Common\ExperienceLevel::getLevel($ds["we_convers_list"]["rows"][0]["total_point"]);
        }
        else
        {
            $ds["we_convers_list"]["rows"][0]["vip_level"] ="1";//
        }
        $css_level = (int) ($ds["we_convers_list"]["rows"][0]["we_level"] / 10);
        return $this->render('JustsyBaseBundle:CDisplayArea:trend_pc.html.twig', array('this' => $this, 'ds' => $ds, 'css_level' => $css_level,'trend'=> $trend));
    }
    //动态信息展示
    public function getTrendWinAction($conv_root_id) {
        $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
        $user = $this->get('security.context')->getToken()->getUser();

        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();
        if($conv_root_id<=1)
        {
        	  $ds = $conv->getRelationSysTrend($da, $user, $conv_root_id, $FILE_WEBSERVER_URL);
        }
        else $ds = $conv->getTrend($da, $user, $conv_root_id, $FILE_WEBSERVER_URL);

        if (count($ds["we_convers_list"]["rows"]) == 0)
            return new Response("");
        if($ds["we_convers_list"]["rows"][0]["auth_level"]!='S')
        {
        	  $ds["we_convers_list"]["rows"][0]["vip_level"] = \Justsy\BaseBundle\Common\ExperienceLevel::getLevel($ds["we_convers_list"]["rows"][0]["total_point"]);
        }
        else
        {
            $ds["we_convers_list"]["rows"][0]["vip_level"] ="1";//
        }
        $css_level = (int) ($ds["we_convers_list"]["rows"][0]["we_level"] / 10);
        //var_dump($this->getRequest()->get("trend")." 官方动态");
        $para= array('this' => $this, 'ds' => $ds, 'css_level' => $css_level,"trend"=>$this->getRequest()->get("trend"));
        return $this->render('JustsyBaseBundle:CDisplayArea:trend_win.html.twig',$para);
    }
    //官方动态信息展示
    public function getOfficialTrendAction($conv_root_id) {
        $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
        $user = $this->get('security.context')->getToken()->getUser();

        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();
        $ds = $conv->getOfficialTrend($da, $user, $conv_root_id, $FILE_WEBSERVER_URL);

        if (count($ds["we_convers_list"]["rows"]) == 0)
            return new Response("");
        
        $isGroup = $ds["we_convers_list"]["rows"][0]["post_to_group"];
        if($isGroup!="ALL") //群组
        {
            	//获取群组logo
            	$groupmgr = new \Justsy\BaseBundle\Management\GroupMgr($da,null);
            	$groupdata = $groupmgr->Get($isGroup);
            	if($groupdata!=null) $ds["we_convers_list"]["rows"][0]["en_logo_path"] = $FILE_WEBSERVER_URL . (empty($groupdata["group_photo_path"])?"default_group.png":$groupdata["group_photo_path"]);
        }
        else
        {
        	   //判断是否是非企业圈子
             $circleid = $ds["we_convers_list"]["rows"][0]["post_to_circle"];
             if($user->get_circle_id("")!=$circleid)
             {
                 	    $circlemgr = new \Justsy\BaseBundle\Management\CircleMgr($da,null);
                      $data = $circlemgr->Get($circleid);
                      if($data!=null) $ds["we_convers_list"]["rows"][0]["en_logo_path"] = $FILE_WEBSERVER_URL . (empty($data["logo_path"])? "default_cirle.png":$data["logo_path"]);
             }
        }
        if($ds["we_convers_list"]["rows"][0]["auth_level"]!='S')
        {
        	  $ds["we_convers_list"]["rows"][0]["vip_level"] = \Justsy\BaseBundle\Common\ExperienceLevel::getLevel($ds["we_convers_list"]["rows"][0]["total_point"]);
        }
        else
        {
            $ds["we_convers_list"]["rows"][0]["vip_level"] ="1";//
        }
        $css_level = (int) ($ds["we_convers_list"]["rows"][0]["we_level"] / 10);
        
        $para=  array('this' => $this, 'ds' => $ds, 'css_level' => $css_level,"trend"=>$this->getRequest()->get("trend"));
        return $this->render('JustsyBaseBundle:CDisplayArea:officialtrend.html.twig', $para);
    }
    
    //PC端官方动态信息展示
    public function getOfficialTrendPcAction($conv_root_id) {
        $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
        $user = $this->get('security.context')->getToken()->getUser();
				$trend = $this->getRequest()->get("trend");
        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();
        $ds = $conv->getOfficialTrend($da, $user, $conv_root_id, $FILE_WEBSERVER_URL);

        if (count($ds["we_convers_list"]["rows"]) == 0)
            return new Response("");
        $isGroup = $ds["we_convers_list"]["rows"][0]["post_to_group"];
        if($isGroup!="ALL") //群组
        {
            	//获取群组logo
            	$groupmgr = new \Justsy\BaseBundle\Management\GroupMgr($da,null);
            	$groupdata = $groupmgr->Get($isGroup);
            	if($groupdata!=null) $ds["we_convers_list"]["rows"][0]["en_logo_path"] = $FILE_WEBSERVER_URL . (empty($groupdata["group_photo_path"])?"default_group.png":$groupdata["group_photo_path"]);
        }
        else
        {
        	   //判断是否是非企业圈子
             $circleid = $ds["we_convers_list"]["rows"][0]["post_to_circle"];
             if($user->get_circle_id("")!=$circleid)
             {
                 	    $circlemgr = new \Justsy\BaseBundle\Management\CircleMgr($da,null);
                      $data = $circlemgr->Get($circleid);
                      if($data!=null) $ds["we_convers_list"]["rows"][0]["en_logo_path"] = $FILE_WEBSERVER_URL . (empty($data["logo_path"])? "default_cirle.png":$data["logo_path"]);
             }
        }            
        if($ds["we_convers_list"]["rows"][0]["auth_level"]!='S')
        {
        	  $ds["we_convers_list"]["rows"][0]["vip_level"] = \Justsy\BaseBundle\Common\ExperienceLevel::getLevel($ds["we_convers_list"]["rows"][0]["total_point"]);
        }
        else
        {
            $ds["we_convers_list"]["rows"][0]["vip_level"] ="1";//
        }
        $css_level = (int) ($ds["we_convers_list"]["rows"][0]["we_level"] / 10);
        return $this->render('JustsyBaseBundle:CDisplayArea:officialtrend_pc.html.twig', array('this' => $this, 'ds' => $ds, 'css_level' => $css_level,'trend'=>$trend));
    }
    //官方动态信息展示
    public function getOfficialTrendWinAction($conv_root_id) {
        $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
        $user = $this->get('security.context')->getToken()->getUser();

        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();
        $ds = $conv->getOfficialTrend($da, $user, $conv_root_id, $FILE_WEBSERVER_URL);

        if (count($ds["we_convers_list"]["rows"]) == 0)
            return new Response("");
        
        $isGroup = $ds["we_convers_list"]["rows"][0]["post_to_group"];
        if($isGroup!="ALL") //群组
        {
            	//获取群组logo
            	$groupmgr = new \Justsy\BaseBundle\Management\GroupMgr($da,null);
            	$groupdata = $groupmgr->Get($isGroup);
            	if($groupdata!=null) $ds["we_convers_list"]["rows"][0]["en_logo_path"] = $FILE_WEBSERVER_URL . (empty($groupdata["group_photo_path"])?"default_group.png":$groupdata["group_photo_path"]);
        }
        else
        {
        	   //判断是否是非企业圈子
             $circleid = $ds["we_convers_list"]["rows"][0]["post_to_circle"];
             if($user->get_circle_id("")!=$circleid)
             {
                 	    $circlemgr = new \Justsy\BaseBundle\Management\CircleMgr($da,null);
                      $data = $circlemgr->Get($circleid);
                      if($data!=null) $ds["we_convers_list"]["rows"][0]["en_logo_path"] = $FILE_WEBSERVER_URL . (empty($data["logo_path"])? "default_cirle.png":$data["logo_path"]);
             }
        }
        if($ds["we_convers_list"]["rows"][0]["auth_level"]!='S')
        {
        	  $ds["we_convers_list"]["rows"][0]["vip_level"] = \Justsy\BaseBundle\Common\ExperienceLevel::getLevel($ds["we_convers_list"]["rows"][0]["total_point"]);
        }
        else
        {
            $ds["we_convers_list"]["rows"][0]["vip_level"] ="1";//
        }
        $css_level = (int) ($ds["we_convers_list"]["rows"][0]["we_level"] / 10);
        
        $para=  array('this' => $this, 'ds' => $ds, 'css_level' => $css_level,"trend"=>$this->getRequest()->get("trend"));
        return $this->render('JustsyBaseBundle:CDisplayArea:officialtrend_win.html.twig', $para);
    }

    //提问展示
    public function getAskAction($conv_root_id) {
        $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
        $user = $this->get('security.context')->getToken()->getUser();

        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();
        $ds = $conv->getAsk($da, $user, $conv_root_id, $FILE_WEBSERVER_URL);

        if (count($ds["we_convers_list"]["rows"]) == 0)
            return new Response("");
        if($ds["we_convers_list"]["rows"][0]["auth_level"]!='S')
        {
        	  $ds["we_convers_list"]["rows"][0]["vip_level"] = \Justsy\BaseBundle\Common\ExperienceLevel::getLevel($ds["we_convers_list"]["rows"][0]["total_point"]);
        }
        else
        {
            $ds["we_convers_list"]["rows"][0]["vip_level"] ="1";//
        }
        $css_level = (int) ($ds["we_convers_list"]["rows"][0]["we_level"] / 10);
        $para= array('this' => $this, 'ds' => $ds, 'css_level' => $css_level,"trend"=>$this->getRequest()->get("trend"));
        return $this->render('JustsyBaseBundle:CDisplayArea:ask.html.twig',$para);
    }

    //PC端提问展示
    public function getAskPcAction($conv_root_id) {
        $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
        $user = $this->get('security.context')->getToken()->getUser();
				$trend = $this->getRequest()->get("trend");
        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();
        $ds = $conv->getAsk($da, $user, $conv_root_id, $FILE_WEBSERVER_URL);

        if (count($ds["we_convers_list"]["rows"]) == 0)
            return new Response("");
        if($ds["we_convers_list"]["rows"][0]["auth_level"]!='S')
        {
        	  $ds["we_convers_list"]["rows"][0]["vip_level"] = \Justsy\BaseBundle\Common\ExperienceLevel::getLevel($ds["we_convers_list"]["rows"][0]["total_point"]);
        }
        else
        {
            $ds["we_convers_list"]["rows"][0]["vip_level"] ="1";//
        }
        $css_level = (int) ($ds["we_convers_list"]["rows"][0]["we_level"] / 10);
        return $this->render('JustsyBaseBundle:CDisplayArea:ask_pc.html.twig', array('this' => $this, 'ds' => $ds, 'css_level' => $css_level,'trend'=>$trend));
    }
		
		//提问展示
    public function getAskWinAction($conv_root_id) {
        $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
        $user = $this->get('security.context')->getToken()->getUser();

        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();
        $ds = $conv->getAsk($da, $user, $conv_root_id, $FILE_WEBSERVER_URL);

        if (count($ds["we_convers_list"]["rows"]) == 0)
            return new Response("");
        if($ds["we_convers_list"]["rows"][0]["auth_level"]!='S')
        {
        	  $ds["we_convers_list"]["rows"][0]["vip_level"] = \Justsy\BaseBundle\Common\ExperienceLevel::getLevel($ds["we_convers_list"]["rows"][0]["total_point"]);
        }
        else
        {
            $ds["we_convers_list"]["rows"][0]["vip_level"] ="1";//
        }
        $css_level = (int) ($ds["we_convers_list"]["rows"][0]["we_level"] / 10);
        $para= array('this' => $this, 'ds' => $ds, 'css_level' => $css_level,"trend"=>$this->getRequest()->get("trend"));
        return $this->render('JustsyBaseBundle:CDisplayArea:ask_win.html.twig',$para);
    }
    //活动展示
    public function getTogetherAction($conv_root_id) {
        $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
        $user = $this->get('security.context')->getToken()->getUser();

        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();
        $ds = $conv->getTogether($da, $user, $conv_root_id, $FILE_WEBSERVER_URL);

        if (count($ds["we_convers_list"]["rows"]) == 0 || count($ds["we_together"]["rows"]) == 0)
            return new Response("");
        if($ds["we_convers_list"]["rows"][0]["auth_level"]!='S')
        {
        	  $ds["we_convers_list"]["rows"][0]["vip_level"] = \Justsy\BaseBundle\Common\ExperienceLevel::getLevel($ds["we_convers_list"]["rows"][0]["total_point"]);
        }
        else
        {
            $ds["we_convers_list"]["rows"][0]["vip_level"] ="1";//
        }
        $css_level = (int) ($ds["we_convers_list"]["rows"][0]["we_level"] / 10);
        
        return $this->render('JustsyBaseBundle:CDisplayArea:together.html.twig', array('this' => $this, 'ds' => $ds, 'css_level' => $css_level,"trend"=>$this->getRequest()->get("trend")));
    }

    //PC端活动展示
    public function getTogetherPcAction($conv_root_id) {
        $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
        $user = $this->get('security.context')->getToken()->getUser();
				$trend = $this->getRequest()->get("trend");
        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();
        $ds = $conv->getTogether($da, $user, $conv_root_id, $FILE_WEBSERVER_URL);

        if (count($ds["we_convers_list"]["rows"]) == 0 || count($ds["we_together"]["rows"]) == 0)
            return new Response("");
        if($ds["we_convers_list"]["rows"][0]["auth_level"]!='S')
        {
        	  $ds["we_convers_list"]["rows"][0]["vip_level"] = \Justsy\BaseBundle\Common\ExperienceLevel::getLevel($ds["we_convers_list"]["rows"][0]["total_point"]);
        }
        else
        {
            $ds["we_convers_list"]["rows"][0]["vip_level"] ="1";//
        }
        $css_level = (int) ($ds["we_convers_list"]["rows"][0]["we_level"] / 10);
        return $this->render('JustsyBaseBundle:CDisplayArea:together_pc.html.twig', array('this' => $this, 'ds' => $ds, 'css_level' => $css_level,'trend'=>$trend));
    }
		//活动展示
    public function getTogetherWinAction($conv_root_id) {
        $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
        $user = $this->get('security.context')->getToken()->getUser();

        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();
        $ds = $conv->getTogether($da, $user, $conv_root_id, $FILE_WEBSERVER_URL);

        if (count($ds["we_convers_list"]["rows"]) == 0 || count($ds["we_together"]["rows"]) == 0)
            return new Response("");
        if($ds["we_convers_list"]["rows"][0]["auth_level"]!='S')
        {
        	  $ds["we_convers_list"]["rows"][0]["vip_level"] = \Justsy\BaseBundle\Common\ExperienceLevel::getLevel($ds["we_convers_list"]["rows"][0]["total_point"]);
        }
        else
        {
            $ds["we_convers_list"]["rows"][0]["vip_level"] ="1";//
        }
        $css_level = (int) ($ds["we_convers_list"]["rows"][0]["we_level"] / 10);
        
        return $this->render('JustsyBaseBundle:CDisplayArea:together_win.html.twig', array('this' => $this, 'ds' => $ds, 'css_level' => $css_level,"trend"=>$this->getRequest()->get("trend")));
    }
    //投票展示
    public function getVoteAction($conv_root_id) {
        $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
        $user = $this->get('security.context')->getToken()->getUser();

        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();
        $ds = $conv->getVote($da, $user, $conv_root_id, $FILE_WEBSERVER_URL);

        if (count($ds["we_convers_list"]["rows"]) == 0 || count($ds["we_vote"]["rows"]) == 0 || count($ds["we_vote_option"]["rows"]) == 0)
            return new Response("");
        if($ds["we_convers_list"]["rows"][0]["auth_level"]!='S')
        {
        	  $ds["we_convers_list"]["rows"][0]["vip_level"] = \Justsy\BaseBundle\Common\ExperienceLevel::getLevel($ds["we_convers_list"]["rows"][0]["total_point"]);
        }
        else
        {
            $ds["we_convers_list"]["rows"][0]["vip_level"] ="1";//
        }
        $css_level = (int) ($ds["we_convers_list"]["rows"][0]["we_level"] / 10);
        $para=array('this' => $this, 'ds' => $ds, 'css_level' => $css_level,"trend"=>$this->getRequest()->get("trend"));
        return $this->render('JustsyBaseBundle:CDisplayArea:vote.html.twig',$para );
    }
    
    //投票展示
    public function getVotePcAction($conv_root_id) {
        $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
        $user = $this->get('security.context')->getToken()->getUser();
				$trend = $this->getRequest()->get("trend");
        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();
        $ds = $conv->getVote($da, $user, $conv_root_id, $FILE_WEBSERVER_URL);

        if (count($ds["we_convers_list"]["rows"]) == 0 || count($ds["we_vote"]["rows"]) == 0 || count($ds["we_vote_option"]["rows"]) == 0)
            return new Response("");
        if($ds["we_convers_list"]["rows"][0]["auth_level"]!='S')
        {
        	  $ds["we_convers_list"]["rows"][0]["vip_level"] = \Justsy\BaseBundle\Common\ExperienceLevel::getLevel($ds["we_convers_list"]["rows"][0]["total_point"]);
        }
        else
        {
            $ds["we_convers_list"]["rows"][0]["vip_level"] ="1";//
        }
        $css_level = (int) ($ds["we_convers_list"]["rows"][0]["we_level"] / 10);
        return $this->render('JustsyBaseBundle:CDisplayArea:vote_pc.html.twig', array('this' => $this, 'ds' => $ds, 'css_level' => $css_level,'trend'=>$trend));
    }
    //投票展示
    public function getVoteWinAction($conv_root_id) {
        $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
        $user = $this->get('security.context')->getToken()->getUser();

        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();
        $ds = $conv->getVote($da, $user, $conv_root_id, $FILE_WEBSERVER_URL);

        if (count($ds["we_convers_list"]["rows"]) == 0 || count($ds["we_vote"]["rows"]) == 0 || count($ds["we_vote_option"]["rows"]) == 0)
            return new Response("");
        if($ds["we_convers_list"]["rows"][0]["auth_level"]!='S')
        {
        	  $ds["we_convers_list"]["rows"][0]["vip_level"] = \Justsy\BaseBundle\Common\ExperienceLevel::getLevel($ds["we_convers_list"]["rows"][0]["total_point"]);
        }
        else
        {
            $ds["we_convers_list"]["rows"][0]["vip_level"] ="1";//
        }
        $css_level = (int) ($ds["we_convers_list"]["rows"][0]["we_level"] / 10);
        $para=array('this' => $this, 'ds' => $ds, 'css_level' => $css_level,"trend"=>$this->getRequest()->get("trend"));
        return $this->render('JustsyBaseBundle:CDisplayArea:vote_win.html.twig',$para );
    }
    //转发信息展示
    public function getCopyAction($conv_root_id) {
        $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
        $user = $this->get('security.context')->getToken()->getUser();

        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();
        $ds = $conv->getCopy($da, $user, $conv_root_id, $FILE_WEBSERVER_URL);

        if (count($ds["we_convers_list"]["rows"]) == 0)
            return new Response("");
        if($ds["we_convers_list"]["rows"][0]["auth_level"]!='S')
        {
        	  $ds["we_convers_list"]["rows"][0]["vip_level"] = \Justsy\BaseBundle\Common\ExperienceLevel::getLevel($ds["we_convers_list"]["rows"][0]["total_point"]);
        }
        else
        {
            $ds["we_convers_list"]["rows"][0]["vip_level"] ="1";//
        }
        $css_level = (int) ($ds["we_convers_list"]["rows"][0]["we_level"] / 10);
        
        return $this->render('JustsyBaseBundle:CDisplayArea:copy_d.html.twig', array('this' => $this, 'ds' => $ds, 'css_level' => $css_level,"trend"=>$this->getRequest()->get("trend")));
    }
		//转发信息展示
    public function getCopyWinAction($conv_root_id) {
        $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
        $user = $this->get('security.context')->getToken()->getUser();

        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();
        $ds = $conv->getCopy($da, $user, $conv_root_id, $FILE_WEBSERVER_URL);

        if (count($ds["we_convers_list"]["rows"]) == 0)
            return new Response("");
        if($ds["we_convers_list"]["rows"][0]["auth_level"]!='S')
        {
        	  $ds["we_convers_list"]["rows"][0]["vip_level"] = \Justsy\BaseBundle\Common\ExperienceLevel::getLevel($ds["we_convers_list"]["rows"][0]["total_point"]);
        }
        else
        {
            $ds["we_convers_list"]["rows"][0]["vip_level"] ="1";//
        }
        $css_level = (int) ($ds["we_convers_list"]["rows"][0]["we_level"] / 10);
        
        return $this->render('JustsyBaseBundle:CDisplayArea:copy_d_win.html.twig', array('this' => $this, 'ds' => $ds, 'css_level' => $css_level,"trend"=>$this->getRequest()->get("trend")));
    }
    //PC端转发信息展示
    public function getCopyPcAction($conv_root_id) {
        $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
        $user = $this->get('security.context')->getToken()->getUser();
				$trend = $this->getRequest()->get("trend");
        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();
        $ds = $conv->getCopy($da, $user, $conv_root_id, $FILE_WEBSERVER_URL);

        if (count($ds["we_convers_list"]["rows"]) == 0)
            return new Response("");
        if($ds["we_convers_list"]["rows"][0]["auth_level"]!='S')
        {
        	  $ds["we_convers_list"]["rows"][0]["vip_level"] = \Justsy\BaseBundle\Common\ExperienceLevel::getLevel($ds["we_convers_list"]["rows"][0]["total_point"]);
        }
        else
        {
            $ds["we_convers_list"]["rows"][0]["vip_level"] ="1";//
        }
        $css_level = (int) ($ds["we_convers_list"]["rows"][0]["we_level"] / 10);
        return $this->render('JustsyBaseBundle:CDisplayArea:copy_d_pc.html.twig', array('this' => $this, 'ds' => $ds, 'css_level' => $css_level,'trend'=>$trend));
    }
    
    //分享信息展示
    public function getShareTrendAction($conv_root_id) {
        $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
        $user = $this->get('security.context')->getToken()->getUser();

        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();
        $ds = $conv->getShareTrend($da, $user, $conv_root_id, $FILE_WEBSERVER_URL);

        if (count($ds["we_convers_list"]["rows"]) == 0)
            return new Response("");
        if($ds["we_convers_list"]["rows"][0]["auth_level"]!='S')
        {
        	  $ds["we_convers_list"]["rows"][0]["vip_level"] = \Justsy\BaseBundle\Common\ExperienceLevel::getLevel($ds["we_convers_list"]["rows"][0]["total_point"]);
        }
        else
        {
            $ds["we_convers_list"]["rows"][0]["vip_level"] ="1";//
        }
        $css_level = (int) ($ds["we_convers_list"]["rows"][0]["we_level"] / 10);
        
        return $this->render('JustsyBaseBundle:CDisplayArea:sharetrend.html.twig', array('this' => $this, 'ds' => $ds, 'css_level' => $css_level,'trend'=> $this->getRequest()->get("trend")));
    }

    //动态信息删除
    public function delTrendAction() {
        $re = array();
        $user = $this->get('security.context')->getToken()->getUser();
        $request = $this->getRequest();
        $conv_root_id = $request->get('conv_root_id');
        $da = $this->get('we_data_access');
        $conv = new \Justsy\BaseBundle\Business\Conv();
        //不是自己的不能删除
        if ($conv->checkIsOwenConv($da, $conv_root_id, $user->getUserName())) {
            $result = $conv->delConvByRootID($da, $conv_root_id);
            if ( $result )
            {
                //出席接收人员
                $staffmgr = new \Justsy\BaseBundle\Management\Staff($da,$this->get('we_data_access_im'), $user,$this->get("logger"),$this->container);
                $send_jid = $staffmgr->getFriendJidList($conv_root_id);
                if($send_jid && count($send_jid)>0)
                {
                    Utils::sendImPresence($user->fafa_jid,implode(",", $send_jid),"del_dynamic",$conv_root_id,$this->container,"","",false,Utils::$systemmessage_code);       
                }
                $AnnouncerMgr = new \Justsy\BaseBundle\Management\Announcer($this->container);   
                $AnnouncerMgr->delConvers($conv_root_id);
                $re = array('success' => '1');
            }
            else
            {
                $re = array('success' => '0');
            }
        } else {
            $re = array('success' => '0');
        }

        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }
    
    //提问删除
    public function delAskAction() {
        $re = array();
        $user = $this->get('security.context')->getToken()->getUser();
        $request = $this->getRequest();
        $conv_root_id = $request->get('conv_root_id');

        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();

        //不是自己的不能删除
        if ($conv->checkIsOwenConv($da, $conv_root_id, $user->getUserName())) {
            $conv->delConvByRootID($da, $conv_root_id);
            $re = array('success' => '1');
        } else {
            $re = array('success' => '0');
        }

        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }

    //活动删除
    public function delTogetherAction() {
        $re = array();
        $user = $this->get('security.context')->getToken()->getUser();
        $request = $this->getRequest();
        $conv_root_id = $request->get('conv_root_id');

        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();

        //不是自己的不能删除
        if ($conv->checkIsOwenConv($da, $conv_root_id, $user->getUserName())) {
            $conv->delTogether($da, $conv_root_id);
            $re = array('success' => '1');
        } else {
            $re = array('success' => '0');
        }

        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }

    //投票删除
    public function delVoteAction() {
        $re = array();
        $user = $this->get('security.context')->getToken()->getUser();
        $request = $this->getRequest();
        $conv_root_id = $request->get('conv_root_id');

        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();

        //不是自己的不能删除
        if ($conv->checkIsOwenConv($da, $conv_root_id, $user->getUserName())) {
            $conv->delVote($da, $conv_root_id);
            $re = array('success' => '1');
        } else {
            $re = array('success' => '0');
        }

        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }

    //回复删除
    public function delReplyAction() {
        $re = array();
        $user = $this->get('security.context')->getToken()->getUser();
        $request = $this->getRequest();
        $conv_id = $request->get('conv_id');

        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();

        //不是自己的不能删除
        if ($conv->checkIsOwenConv($da, $conv_id, $user->getUserName())) {
            $conv->delReplyByID($da, $conv_id);
            $re = array('success' => '1');
        } else {
            $re = array('success' => '0');
        }

        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }

    //赞
    public function likeConvAction() {
        $re = array();
        $user = $this->get('security.context')->getToken()->getUser();
        $request = $this->getRequest();
        $conv_root_id = $request->get('conv_root_id');

        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();
        $conv->likeConv($da, $user, $conv_root_id);
        $re = array('success' => '1', 'like_staff' => $user->getUserName(), 'nick_name' => $user->nick_name);

        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }

    //取消赞
    public function unlikeConvAction() {
        $re = array();
        $user = $this->get('security.context')->getToken()->getUser();
        $request = $this->getRequest();
        $conv_root_id = $request->get('conv_root_id');

        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();
        $conv->unlikeConv($da, $user, $conv_root_id);
        $re = array('success' => '1', 'like_staff' => $user->getUserName(), 'nick_name' => $user->nick_name);

        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }

    //发布回复
    public function replyConvAction() {
        $re = array();
        $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
        $request = $this->getRequest();
        $user = $this->get('security.context')->getToken()->getUser();
        $conv_root_id = $request->get('conv_root_id');

        $da = $this->get('we_data_access');

        $conv_content = $request->get('replayvalue');
        $reply_to = $request->get('reply_to');
        $reply_to_name = $request->get('reply_to_name');
//    $notifystaff = $request->get('notifystaff');
   $attachs = $request->get('attachs');
   $attachs_name = $request->get('attachs_name');
//    $post_to_group = $request->get('post_to_group');
        $conv_id = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_convers_list", "conv_id");

        $conv = new \Justsy\BaseBundle\Business\Conv();
        $conv->replyConv($da, $user, $conv_root_id, $conv_id, $conv_content, $reply_to, "00", $this->container, $attachs);

        $a = array();
        $a['this'] = $this;
        $row = array();
        $row["conv_id"] = $conv_id;
        $row["photo_url"] = "${FILE_WEBSERVER_URL}$user->photo_path";
        $row["nick_name"] = $user->nick_name;
        $row["login_account"] = $user->getUserName();
        $row["reply_to"] = $reply_to;
        $row["reply_to_name"] = $reply_to_name;
        $row["conv_content"] = $conv_content;
        $row["post_date_d"] = "10秒前";
        $row["comefrom"] = "00";
        $row["comefrom_d"] = "Wefafa Web";
        $a['row'] = $row;
        $ds = array();
        $ds["we_convers_like"] = array();
        $ds["we_convers_like"]["rows"] = array();
        $ds["we_convers_attach_reply"] = array();
        $rowsattchs = array();
        for ($i=0; $i < count($attachs); $i++) {
            $rowsattchs[] = array("conv_id"=>$conv_id,
                "attach_id"=>$attachs[$i],
                "file_name"=>$attachs_name[$i],
                "file_ext"=>"",
                "up_by_staff"=>$user->getUserName(),
                "up_date"=>""
                );
        }
        $ds["we_convers_attach_reply"]["rows"] = $rowsattchs;

        $a['ds'] = $ds;
        $request = $this->getRequest();
        $a['trend'] = $request->get("trend");

        return $this->render('JustsyBaseBundle:CDisplayArea:reply_item.html.twig', $a);
    }
	  //发表回复
	  public function replyConvPcAction()
	  {
	  	  $re = array();
        $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
        $request = $this->getRequest();
        $user = $this->get('security.context')->getToken()->getUser();
        $conv_root_id = $request->get('conv_root_id');

        $da = $this->get('we_data_access');

        $conv_content = $request->get('replayvalue');
        $reply_to = $request->get('reply_to');
        $reply_to_name = $request->get('reply_to_name');
//    $notifystaff = $request->get('notifystaff');
   $attachs = $request->get('attachs');
   $attachs_name = $request->get('attachs_name');
//    $post_to_group = $request->get('post_to_group');
        $conv_id = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_convers_list", "conv_id");

        $conv = new \Justsy\BaseBundle\Business\Conv();
        $conv->replyConv($da, $user, $conv_root_id, $conv_id, $conv_content, $reply_to, "00", $this->container, $attachs);

        $a = array();
        $a['this'] = $this;
        $row = array();
        $row["conv_id"] = $conv_id;
        $row["photo_url"] = "${FILE_WEBSERVER_URL}$user->photo_path";
        $row["nick_name"] = $user->nick_name;
        $row["login_account"] = $user->getUserName();
        $row["reply_to"] = $reply_to;
        $row["reply_to_name"] = $reply_to_name;
        $row["conv_content"] = $conv_content;
        $row["post_date_d"] = "10秒前";
        $row["comefrom"] = "00";
        $row["comefrom_d"] = "Wefafa Web";
        $a['row'] = $row;
        $ds = array();
        $ds["we_convers_like"] = array();
        $ds["we_convers_like"]["rows"] = array();
        $ds["we_convers_attach_reply"] = array();
        $rowsattchs = array();
        for ($i=0; $i < count($attachs); $i++) {
            $rowsattchs[] = array("conv_id"=>$conv_id,
                "attach_id"=>$attachs[$i],
                "file_name"=>$attachs_name[$i],
                "file_ext"=>"",
                "up_by_staff"=>$user->getUserName(),
                "up_date"=>""
                );
        }
        $ds["we_convers_attach_reply"]["rows"] = $rowsattchs;
        $a['ds'] = $ds;

        return $this->render('JustsyBaseBundle:CDisplayArea:reply_item_pc.html.twig', $a);
	  }
    public function voteAction() {
        $request = $this->getRequest();
        $user = $this->get('security.context')->getToken()->getUser();
        $vote_id = $request->get('vote_id');
        $is_multi = $request->get('is_multi');
        $optionids = $request->get('optionids');

        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();

        //查询是否已投票
        if ($conv->checkIsVoted($da, $vote_id, $user->getUserName()) || ($is_multi == "1" && (!is_array($optionids) || count($optionids) == 0))) {
            
        } else {
            $conv->vote($da, $user, $vote_id, $is_multi, $optionids);
        }

        //返回
        return $this->getOneConv($vote_id);
    }

    public function getCopyTemplateAction() {
    		$para= array('this' => $this);
        return $this->render('JustsyBaseBundle:CDisplayArea:copy.html.twig', $para);
    }
    
    public function getCopyTemplatePcAction() {
        return $this->render('JustsyBaseBundle:CDisplayArea:copy_pc.html.twig', array('this' => $this));
    }

    //取出消息是否能转发
    public function getConvLimitAction() {
        $re = "";
        $user = $this->get('security.context')->getToken()->getUser();
        $request = $this->getRequest();

        $network_domain = $request->get('network_domain');

        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();
        $re = $conv->getCircleLimit($da, $user->get_circle_id($network_domain));

        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }

    //取出用户在指定圈子中所加入的群组
    public function getGroupByCircleAndGroupNameAction() {
        $re = array();
        $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
        $request = $this->getRequest();
        $user = $this->get('security.context')->getToken()->getUser();

        $circle_id = $request->get('circle');
        $queryname = $request->get('query');

        $da = $this->get('we_data_access');

        $sql = "select a.group_id, a.circle_id, a.group_name, a.group_photo_path, concat('$FILE_WEBSERVER_URL', ifnull(a.group_photo_path, '')) group_photo_url 
from we_groups a, we_group_staff b
where a.group_id=b.group_id
  and a.circle_id=?
  and b.login_account=?";
        $params = array();
        $params[] = (string) $circle_id;
        $params[] = (string) $user->getUserName();

        if ($queryname) { //如果是查询特定群名称，则返回符合条件的群，否则返回全部群，或全部群大于100个，则返回[]
            $sql .= " and a.group_name like concat('%', ?, '%') ";
            $params[] = (string) $queryname;

            $sql .= " limit 0, 100 ";
        } else {
            $da->PageSize = 100;
            $da->PageIndex = 0;
        }

        $ds = $da->GetData("we_groups", $sql, $params);

        if ($queryname)
            $re = $ds["we_groups"]["rows"];
        else
            $re = $ds["we_groups"]["recordcount"] > count($ds["we_groups"]["rows"]) ? array() : $ds["we_groups"]["rows"];

        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }

    //转发
    public function copyConvAction() {
        $request = $this->getRequest();
        $user = $this->get('security.context')->getToken()->getUser();

        $da = $this->get('we_data_access');

        $conv_content = $request->get('copy_content');
        $post_to_circle = $request->get('post_to_circle');
        $post_to_group = $request->get('post_to_group');
        $copy_id = $request->get('copy_id');
        $copy_last_id = $request->get('copy_last_id');

        $conv = new \Justsy\BaseBundle\Business\Conv();

        //检查该会话是否允许转发
        $allowcopy = $conv->getConvLimit($da, $copy_id, $post_to_circle);

        if ($allowcopy["allow_copy"] == "1") {
            $re = array('success' => '0', 'msg' => "您无权转发该信息");
            $response = new Response(json_encode($re));
            $response->headers->set('Content-Type', 'text/json');
            return $response;
        }

        $conv_id = \Justsy\BaseBundle\DataAccess\SysSeq::GetSeqNextValue($da, "we_convers_list", "conv_id");

        $conv->copyConv($da, $user, $conv_id, $conv_content, $post_to_circle, $post_to_group, $copy_id, $copy_last_id);

        $re = array('success' => '1', 'conv_id' => $conv_id);
        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }

    //收藏
    public function attenConvAction() {
        $re = array();
        $user = $this->get('security.context')->getToken()->getUser();
        $request = $this->getRequest();
        $conv_root_id = $request->get('conv_root_id');

        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();

        //检查是否有权限
        if ($conv->checkCanViewConv($da, $conv_root_id, $user->getUserName())) {
            $ds = $conv->attenConv($da, $user, $conv_root_id);

            $re = array('success' => ($ds[1] > 0 ? '1' : '0'));
        } else {
            $re = array('success' => '0');
        }


        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }

    //取消收藏
    public function unattenConvAction() {
        $re = array();
        $user = $this->get('security.context')->getToken()->getUser();
        $request = $this->getRequest();
        $conv_root_id = $request->get('conv_root_id');

        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();

        $ds = $conv->unattenConv($da, $user, $conv_root_id);

        $re = array('success' => ($ds[1] > 0 ? '1' : '0'));

        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }

    //加入活动
    public function joinTogetherAction() {
        $re = array();
        $user = $this->get('security.context')->getToken()->getUser();
        $request = $this->getRequest();
        $together_id = $request->get('together_id');

        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();
        $conv->joinTogether($da, $user, $together_id);

        $re = array('success' => '1', 'join_staff' => $user->getUserName(), 'nick_name' => $user->nick_name);

        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }

    //退出活动
    public function unjoinTogetherAction() {
        $re = array();
        $user = $this->get('security.context')->getToken()->getUser();
        $request = $this->getRequest();
        $together_id = $request->get('together_id');

        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();
        $conv->unjoinTogether($da, $user, $together_id);

        $re = array('success' => '1', 'join_staff' => $user->getUserName(), 'nick_name' => $user->nick_name);

        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }

    //查看原图
    public function viewImageAction($id) {
        return $this->render('JustsyBaseBundle:CDisplayArea:viewimage.html.twig', array('id' => $id));
    }

    //取出已有标签
    public function getLabelAction() {
        $re = array();
        $user = $this->get('security.context')->getToken()->getUser();
        $request = $this->getRequest();

        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();
        $re = $conv->getLabel($da, $user);

        $response = new Response(json_encode($re["we_convers_label"]["rows"]));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }

    //保存标签
    public function saveLabelAction() {
        $re = array();
        $user = $this->get('security.context')->getToken()->getUser();
        $request = $this->getRequest();
        $atten_id = $request->get("atten_id");
        $label_names = $request->get("label_names");

        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();
        $conv->saveLabel($da, $user, $atten_id, $label_names);

        $re = array('success' => '1');

        $response = new Response(json_encode($re));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }

    //取评论
    public function getReplyAction() {
        $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
        $user = $this->get('security.context')->getToken()->getUser();
        $request = $this->getRequest();
        $conv_root_id = $request->get("conv_root_id");
        $pagesize = 50;
        $pageindex = $request->get("pageindex");

        if (empty($pageindex))
            $pageindex = 1;

        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();
        $ds = $conv->getReply($da, $user, $conv_root_id, $FILE_WEBSERVER_URL, $pageindex, $pagesize);

        $pagecount = ceil($ds["we_convers_list"]["recordcount"] / $pagesize);

        return $this->render('JustsyBaseBundle:CDisplayArea:reply_pagelist.html.twig', array('trend'=> $request->get("trend"),'this' => $this, 'ds' => $ds, 'pageindex' => $pageindex, 'pagecount' => $pagecount));
    }
		//通过id获取一条动态用户显示在大图查看右侧
		public function getConvByIdAction($network_domain)
		{
			$user = $this->get('security.context')->getToken()->getUser();
      $request = $this->getRequest();
      $da = $this->get('we_data_access');
      $conv_id=$request->get("convId");
      $trend = $user->IsFunctionTrend($network_domain);
      //判断会话类型
      $sql="select conv_type_id from we_convers_list where conv_id=?";
      $params=array($conv_id);
      $ds=$da->Getdata('conv',$sql,$params);
      if($ds['conv']['recordcount']>0){
      	$conv_type=$ds['conv']['rows'][0]['conv_type_id'];
      	if($conv_type=='00'){
      		return $this->forward("JustsyBaseBundle:CDisplayArea:getTrendWin",array('conv_root_id'=> $conv_id,'trend'=>$trend));
      	}
      	else if($conv_type=='01'){
      		return $this->forward("JustsyBaseBundle:CDisplayArea:getAskWin",array('conv_root_id'=> $conv_id,'trend'=>$trend));
      	}
      	else if($conv_type=='02'){
      		return $this->forward("JustsyBaseBundle:CDisplayArea:getTogetherWin",array('conv_root_id'=> $conv_id,'trend'=>$trend));
      	}
      	else if($conv_type=='03'){
      		return $this->forward("JustsyBaseBundle:CDisplayArea:getVoteWin",array('conv_root_id'=> $conv_id,'trend'=>$trend));
      	}
      	else if($conv_type=='05'){
      		return $this->forward("JustsyBaseBundle:CDisplayArea:getCopyWin",array('conv_root_id'=> $conv_id,'trend'=>$trend));
      	}
      	else if($conv_type=='06'){
      		return $this->forward("JustsyBaseBundle:CDisplayArea:getOfficialTrendWin",array('conv_root_id'=> $conv_id,'trend'=>$trend));
      	}
      }
      else{
      	$response = new Response("该条动态信息不存在或已被删除。");
        return $response;
      }
		}
    //取转发列表
    public function getCopyListAction() {
        $FILE_WEBSERVER_URL = $this->container->getParameter('FILE_WEBSERVER_URL');
        $user = $this->get('security.context')->getToken()->getUser();
        $request = $this->getRequest();
        $conv_root_id = $request->get("conv_root_id");
        $pagesize = 50;
        $pageindex = $request->get("pageindex");

        if (empty($pageindex))
            $pageindex = 1;

        $da = $this->get('we_data_access');

        $conv = new \Justsy\BaseBundle\Business\Conv();
        $ds = $conv->getCopyList($da, $user, $conv_root_id, $FILE_WEBSERVER_URL, $pageindex, $pagesize);

        $pagecount = ceil($ds["we_convers_list"]["recordcount"] / $pagesize);

        return $this->render('JustsyBaseBundle:CDisplayArea:copy_pagelist.html.twig', array('this' => $this, 'ds' => $ds, 'pageindex' => $pageindex, 'pagecount' => $pagecount, 'copy_id' => $conv_root_id));
    }

    //关于会话置顶
    public function convTopAction($oper = 'true', $conv_id = '') {
        $request = $this->getRequest();
        $user = $this->get('security.context')->getToken()->getUser();
        $oper = $request->get("oper");
        $conv_id = $request->get("conv_id");
        $network_domain = $request->get('network_domain');
        $time = $request->get("time");
        $time = empty($time) ? 1 : $time;
        $da = $this->get('we_data_access');
        $conv = new \Justsy\BaseBundle\Business\Conv();
        $s = '1';
        $m = '';
        //判断是否有权限
        if (!$user->is_in_manager_circles($network_domain)) {
            $s = '0';
            $m = '没有权限';
        } else {
            if ($oper == 'true') {
                if (!$conv->convTop($da, $conv_id, $time)) {
                    $s = '0';
                    $m = '操作失败';
                }
            } else if ($oper == 'false') {
                if (!$conv->convCancelTop($da, $conv_id)) {
                    $s = '0';
                    $m = '操作失败';
                }
            }
        }
        $response = new Response(json_encode(array('s' => $s, 'm' => $m)));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }

    //关于隐藏会话
    public function convHideAction() {
        $request = $this->getRequest();
        $user = $this->get('security.context')->getToken()->getUser();
        $oper = $request->get("oper");
        $conv_id = $request->get("conv_id");
        $conv = new \Justsy\BaseBundle\Business\Conv();
        $da = $this->get('we_data_access');
        $s = '1';
        $m = '';
        if ($oper == 'true') {
            if (!$conv->convHide($da, $conv_id, $user->getUserName())) {
                $s = '0';
                $m = '操作失败';
            }
        } else if ($oper == 'false') {
            if (!$conv->convCancelHide($da, $conv_id, $user->getUserName())) {
                $s = '0';
                $m = '操作失败';
            }
        }
        $response = new Response(json_encode(array('s' => $s, 'm' => $m)));
        $response->headers->set('Content-Type', 'text/json');
        return $response;
    }

}