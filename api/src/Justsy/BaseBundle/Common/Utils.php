<?php

namespace Justsy\BaseBundle\Common;

use Symfony\Component\HttpFoundation\Response;
use Justsy\BaseBundle\DataAccess\SysSeq;

class Utils
{
	public static $PUBLIC_ENO = "100000";
	public static $systemmessage_code = "system-message";
	public static $atme_code = "atme";
	public static $eno_identify_auth="eno-identify-auth";
	
	public static $WB_AKEY="981792452";
  public static $WB_SKEY="99c31e1fd397f35b3ab2729f44f93abf";
  public static $WB_CALLBACK_URL= "http://xxxxxxxxxxxx/callback.php";
  
	public static $WX_APPID="wx4a02fdf389f65218";
  public static $WX_APPKEY="824004549ecbe16de5c5d92ce44a3121";  
  public static $WX_GET_TOKEN_URL = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=APPID&secret=APPKEY";
	public static $WX_GET_ATTEN_URL = "https://api.weixin.qq.com/cgi-bin/user/get?access_token=TOKEN&next_openid=NEXT_OPENID";
	public static $WX_GET_USERINFO_URL = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=TOKEN&openid=OPENID";
	
  public static function getUrlContent($url,$logger=null) 
  {
    try
    {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT,10);
      curl_setopt($ch, CURLOPT_FORBID_REUSE, true); //处理完后，关闭连接，释放资源
      $content = curl_exec($ch);
      if ($logger) $logger->alert("getUrlContent:".$content);
      return $content;
    }
    catch(\Exception $e)
    {
      if ($logger) $logger->err($e);
      return null;
    }
  }
  
    public static function do_get_request_cookie($url, $data, $optional_headers = null,$cookiefile=null,$method='POST')
    {
        $cookie_jar = '/tmp/'.(empty($cookiefile)?"wefafa":$cookiefile).".cookie";
        $ch = curl_init($url);
        //curl_setopt($ch,CURLOPT_HEADER,1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS,is_array($data)?http_build_query($data):$data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_COOKIEJAR,$cookie_jar);
        if(!empty($optional_headers))
        {
          curl_setopt($ch, CURLOPT_HTTPHEADER, $optional_headers);
        }
        $result = curl_exec($ch); 

        curl_close($ch);
        return $result;
    }

    public static function do_post_request_cookie($url, $data, $optional_headers = null,$cookiefile=null,$method='POST')
    {
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS,is_array($data)?http_build_query($data):$data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        if(!empty($cookiefile) && is_array($cookiefile))
        {
          $cs = array();
          foreach ($cookiefile as $key => $value) {
            $cs[] = $key."=".$cookiefile[$key];
          }
          curl_setopt($ch, CURLOPT_COOKIE,implode(";", $cs));
        }
        else
        {
          $cookie_jar = '/tmp/'.(empty($cookiefile)?"wefafa":$cookiefile).".cookie";
          curl_setopt($ch, CURLOPT_COOKIEFILE,$cookie_jar);
        }
        if(!empty($optional_headers))
        {
          curl_setopt($ch, CURLOPT_HTTPHEADER, $optional_headers);
        }
        $result = curl_exec($ch); 
        curl_close($ch);
        return $result;
    }

    public static function do_post_request($url, $data, $optional_headers = null)
    {
        $params = array('http' => array(
                'method' => 'POST',
                'content' => $data
              ));
        if ($optional_headers !== null) {
            $params['http']['header'] = $optional_headers;
        }
        $ctx = stream_context_create($params);
        $fp = @fopen($url, 'r', false, $ctx);
        if (!$fp) {
            throw new \Exception("Problem with $url, $php_errormsg");
        }
        $response = @stream_get_contents($fp);
        if ($response === false) {
            throw new \Exception("Problem reading data from $url, $php_errormsg");
        }
        return $response;
    } 

  public static function http_redirect($weburl)
	{
		$resp = new \Symfony\Component\HttpFoundation\Response("");
	   	$resp->headers->set('Content-Type', 'text/html');
	   	$resp->headers->set('Location', $weburl);
	   	return $resp;
	}

  public static function WrapResultOK($data,$msg="")
  {
    return array("returncode"=>"0000","data"=>$data,"msg"=>$msg);
  }

  public static function WrapResultError($msg,$errcode="9999")
  {
    return array("returncode"=>$errcode,"msg"=>$msg);
  }


  //实时聊天消息
  public static function WrapChatMessageXml($fromjid,$nickname,$messagedata,$msgid=null)
  {
  	if(empty($msgid))
  	{
	  	$msgid = split("@", $fromjid);
	  	$msgid = $msgid[0].time();
  	}
	if(is_array($messagedata))
	{
		$messagedata = json_encode($messagedata);
	}
  	$msgxml = "<message from='".$fromjid."' id='".$msgid."' type='chat'><body>".htmlentities($messagedata)."</body><nick xmlns='http://jabber.org/protocol/nick'>".$nickname."</nick></message>";
  	return $msgxml;
  }

  public static function WrapMessageXml($fromjid,$messagedata,$msgid=null)
  {
  	if(empty($msgid))
  	{
	  	$msgid = split("@", $fromjid);
	  	$msgid = $msgid[0].time();
  	}
	if(is_array($messagedata))
	{
		$messagedata = json_encode($messagedata);
	}  	
  	$msgxml = "<message from='".$fromjid."' id='".$msgid."'><business xmlns='http://im.private-en.com/namespace/business'>".htmlentities($messagedata)."</business></message>";
  	return $msgxml;
  }

  //包装公众号消息
  public static  function WrapMicroMessageXml($fromjid,$messagedata,$msgid=null)
  {
  	if(empty($msgid))
  	{
	  	$msgid = split("@", $fromjid);
	  	$msgid = $msgid[0].time();
  	}
	if(is_array($messagedata))
	{
		$messagedata = json_encode($messagedata);
	}  	
  	$msgxml = "<message from='".$fromjid."' id='".$msgid."'><serviceaccount xmlns='http://im.private-en.com/namespace/serviceaccount'>".htmlentities($messagedata)."</serviceaccount></message>";
  	return $msgxml;
  }

  public static function WrapMessage($code,$data,$noticeinfo=array())
  {
  	return array("code"=>$code,"data"=>$data,"noticeinfo"=>$noticeinfo);
  }

  //分享类实时消息数据包装
  //通过chat发送
  public static function WrapShearMessage($code,$data,$summary)
  {
  	return array("code"=>$code,"data"=>$data,"summary"=>$summary);
  }
  
  public static function WrapMessageNoticeinfo($title,$sendername,$sendtime=null,$iconUrl=null,$extendObject=null)
  {
  	date_default_timezone_set('Etc/GMT-8');
  	return array("summary"=>$title,
  		"nickname"=>$sendername,
  		"sendtime"=>!empty($sendtime)? $sendtime : date("Y-m-d H:i:s",time()),
  		"msgicon"=>empty($iconUrl)? "" : $iconUrl,
  		"extend"=>empty($extendObject)?"" : $extendObject
  		);
  }
  //生成客户端能识别的HTML标签
  public static function makeHTMLElementTag($type,$eleid,$elename)
  {
     return "<span type='".$type."' id='".$eleid."'>".$elename."</span>";
  }
  
  public static function makeBusButton($buttons)
  {
  	   return json_encode($buttons);
  }  
  
  // 生成圈子鼠标提示标签
  public static function makeCircleTipHTMLTag($circle_id, $circle_name) 
  {
    return "<a circle_id='$circle_id' class='circle_name' href='javascript:;'>$circle_name</a>";
  }
  
  
  //使用正则表达式判断Email地址是否合法的函数
  public static function validateEmail($email)
  {
    return preg_match("/^[a-z0-9]+[a-z0-9_-]*(\.[a-z0-9_-]+)*@[a-z0-9_-]+(\.[a-z0-9_-]+)*\.([a-z]+){2,}$/",strtolower($email));
  }
  
  //使用正则表达式判断手机是否合法的函数
  public static function validateMobile($mobilephone)
  {
    return preg_match("/^1[0-9]{10}$/",$mobilephone);
  }  
  
  public static function is_ip($gonten)
  {
    	$ip=explode(".",$gonten);  
	    for($i=0;$i<count($ip);$i++)  
	    {  
		    if($ip[$i]>255){  
		     return false;  
		    }
	    }
      return preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/',$gonten);  
  }

  public static function sendChatMessage($fromjid,$tojid,$message,$container)
  {
	  	try
	  	{
		    if ( is_array($tojid))
		        $tojid = implode(",",$tojid);
		  	$ec = new \Justsy\OpenAPIBundle\Controller\ApiController();  		
		    $ec->setContainer($container);
		  	$s = $ec->sendChat($fromjid,$tojid,$message);
		  	$jo = json_decode($s);
		  	if ($jo && $jo->{'returncode'}=="0000") return true;
		  	else return false;
	    }
	    catch(\Exception $e)
	    {
	      	$container->get('logger')->err($e);
	      	return false;
	    }
  }
  
  //发送即时消息
  public static function sendImMessage($fromjid,$tojid,$title,$message,$container,$link="",$linktext="",$ischeckjid=true,$type='',$cctomail='0')
  {
  	try
  	{
      if ( is_array($tojid))
         $tojid = implode(",",$tojid);
  		$ec = new \Justsy\OpenAPIBundle\Controller\ApiController();  		
      $ec->setContainer($container);
  	  if(!empty($linktext) && is_array($linktext)) $linktext = Utils::makeBusButton($linktext);
  	  $s = $ec->sendMsg($fromjid,$tojid,$title,$message,$link,$linktext,$ischeckjid,$type,$cctomail);
  	  $jo = json_decode($s);
  	  if ($jo && $jo->{'returncode'}=="0000") return true;
  	  else return false;
    }
    catch(\Exception $e)
    {
      $container->get('logger')->err($e);
      return false;
    }
  }
  
  public static function sendImPresence($fromjid,$tojid,$title,$message,$container,$link="",$linktext="",$ischeckjid=true,$type='',$cctomail='0')
  {
  	try
  	{
  	  if ( is_array($tojid))
  	     $tojid = implode(",",$tojid);
  		$ec = new \Justsy\OpenAPIBundle\Controller\ApiController();  		
      $ec->setContainer($container);
      /*
  	  $url = $controller->generateUrl("JustsyOpenAPIBundle_api_sendpresence",array(
  	    'From' => $fromjid,
  	    'To' => $tojid,
  	    'Title' => $title,
  	    'Message' => $message,
  	    'Link' => $link,
  	    'LinkText' => $linktext
  	  ),true);
  	  $s = Utils::getUrlContent($url);
  	  */
  	  $s = $ec->sendPresence($fromjid,$tojid,$title,$message,$link,$linktext,$ischeckjid,$type,$cctomail);
  	  $jo = json_decode($s);
  	  if ($jo && $jo->{'returncode'}=="0000") return true;
  	  else return false;
    }
    catch(\Exception $e)
    {
      //var_dump($e->getMessage());
      $container->get('logger')->err($e);
      return false;
    }
  }

  //按在线状态重排指定的人员列表
  public static function resortjid($conn_im,&$staffLst)
  {
  	if(empty($staffLst)) return array();
  	if(count($staffLst)<=1000) return $staffLst; //人员列表小于1000时，不用重排顺序
  	if(empty($conn_im))
  	{
  		throw new \Exception("未指定im库连接对象");
  	}  	
	$online_jids = array();
    $onlinesql = "select distinct us from global_session";
    $onlinedata = $conn_im->GetData("online",$onlinesql,array());
    $onlinedata = $onlinedata["online"]["rows"];
    foreach ($onlinedata as $key => $value)
    {
        $online_jids[]= $value["us"];
    }
    unset($onlinedata);
	//在线的排数组前面
    $intersect = Utils::array_intersect_ex($online_jids,$staffLst);
    $diff = Utils::array_diff_ex($staffLst,$intersect);
    $staffLst = array_merge($intersect,$diff);
  }

  public static function findonlinejid($conn_im,&$staffLst)
  {
  	if(empty($staffLst)) return array();
  	if(count($staffLst)<=5) return $staffLst; //人员列表小于5时，不用过滤在线人员
  	if(empty($conn_im))
  	{
  		throw new \Exception("未指定im库连接对象");
  	}
	$online_jids = array();
    $onlinesql = "select distinct us from global_session";
    $onlinedata = $conn_im->GetData("online",$onlinesql,array());
    $onlinedata = $onlinedata["online"]["rows"];
    foreach ($onlinedata as $key => $value) {
        $online_jids[]= $value["us"];
    }
    unset($onlinedata);
    $staffLst = Utils::array_intersect_ex($online_jids,$staffLst);
    return $staffLst;
  }

public static function array_intersect_ex($arr1,$arr2)
{
	foreach ($arr2 as $key => $value) {
		$arr1[] = $value;
	}
	sort($arr1);
	$get=array();
	$l = sizeof($arr1);
	for($i=0;$i<$l-1;$i++)
	{
		if($arr1[$i]==$arr1[$i+1])
			$get[]=$arr1[$i];
	}
	unset($arr1);
	unset($arr2);
	return $get;
}

public static function array_diff_ex($array_1, $array_2) {
    $array_2 = array_flip($array_2);
    foreach ($array_1 as $key => $item) {
        if (isset($array_2[$item])) {
            unset($array_1[$key]);
        }
    }
    unset($array_2);
    return $array_1;
}
  
  public static function getJidByAccount($da, $login_account)
  {
    $sql = "select fafa_jid from we_staff where login_account=?";
    $ds = $da->GetData("we_staff",$sql,array((string)$login_account));
    if ($ds && $ds['we_staff']['recordcount']>0)
    {
      return $ds['we_staff']['rows'][0]['fafa_jid'];
    }
    return null;
  }
  
  public static function sendMail($mailer,$title,$send,$sendername,$target,$content)
  {
    $sender = empty($sendername)?"管理员":$sendername;
    //$send = $this->container->getParameter('mailer_user');
    $mailtext = \Swift_Message::newInstance()
      ->setSubject($title)
      ->setFrom(array($send=> $sender))
      ->setTo($target)
      ->setContentType('text/html')
      ->setBody($content);
    $mailer->send($mailtext);    	
  }
  
  //保存待发送的邮件
  public static function saveMail($da,$send_email,$recv_email,$title,$content,$remark=null)
  {
    $id = SysSeq::GetSeqNextValue($da,"we_mails","id");
    $sql = "insert into we_mails (id,send_email,recv_email,title,content,remark,is_send,into_date) values (?,?,?,?,?,?,'0',now())";
    $da->ExecSQL($sql,array(
      (string)$id,
      (string)$send_email,
      (string)$recv_email,
      (string)$title,
      (string)$content,
      (string)$remark
    ));
  }
  
  //保存文件
  public static function saveFile($path, $dm)
  {
    $doc = new \Justsy\MongoDocBundle\Document\WeDocument();
    $doc->setName(basename($path));
    $doc->setFile($path);
    $dm->persist($doc);
    $dm->flush();
    unlink($path);
    return $doc->getId();
  }

  //根据文件id删除文件
  //参数可为文件id或文件id数组
  public static function removeFile($path, $dm)
  {
    if ( is_string($path) && !empty($path) )
    {        
      $doc = $dm->getRepository('JustsyMongoDocBundle:WeDocument')->find($path);
      if(!empty($doc)) 
      {
        $dm->remove($doc);
        $dm->flush();
      }
    }
    else if ( is_array($path) && count($path)>0)
    {
        for($i=0;$i< count($path);$i++)
        {
            $fileid = $path[$i];
            if ( !empty($fileid ))
            {
              $doc = $dm->getRepository('JustsyMongoDocBundle:WeDocument')->find($fileid);
              if(!empty($doc)) 
              {
                $dm->remove($doc);
                $dm->flush();
              }
            }          
        }
    }
    return true;
  }


  //appid生成规则 16位
  public static function getAppid($eno,$username)
  {
    return md5($eno.(string)time().$username);
  }
  //appkey生成规则
  public static function getAppkey()
  {
    $searcharray=array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h','i', 'j', 'k', 'l','m', 'n', 'o', 'p', 'q', 'r', 's', 
    't', 'u', 'v', 'w', 'x', 'y','z', 'A', 'B', 'C', 'D', 
    'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L','M', 'N', 'O', 
    'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y','Z', 
    '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '!', 
    '@','#', '^',  '*', '(', ')', '-', '_', 
    '[', ']', '{', '}', '<', '>', '~', '=',':', '/', '|');
    $key="";
    for($i=0;$i<8;$i++){
      $int=mt_rand(0,count($searcharray)-1);
      $key.=$searcharray[$int];
    }
    return $key;
  }

  public static	function Pinyin($_String, $_Code='UTF8'){ //GBK页面可改为gb2312，其他随意填写为UTF8
	        $_DataKey = "a|ai|an|ang|ao|ba|bai|ban|bang|bao|bei|ben|beng|bi|bian|biao|bie|bin|bing|bo|bu|ca|cai|can|cang|cao|ce|ceng|cha".
	                        "|chai|chan|chang|chao|che|chen|cheng|chi|chong|chou|chu|chuai|chuan|chuang|chui|chun|chuo|ci|cong|cou|cu|".
	                        "cuan|cui|cun|cuo|da|dai|dan|dang|dao|de|deng|di|dian|diao|die|ding|diu|dong|dou|du|duan|dui|dun|duo|e|en|er".
	                        "|fa|fan|fang|fei|fen|feng|fo|fou|fu|ga|gai|gan|gang|gao|ge|gei|gen|geng|gong|gou|gu|gua|guai|guan|guang|gui".
	                        "|gun|guo|ha|hai|han|hang|hao|he|hei|hen|heng|hong|hou|hu|hua|huai|huan|huang|hui|hun|huo|ji|jia|jian|jiang".
	                        "|jiao|jie|jin|jing|jiong|jiu|ju|juan|jue|jun|ka|kai|kan|kang|kao|ke|ken|keng|kong|kou|ku|kua|kuai|kuan|kuang".
	                        "|kui|kun|kuo|la|lai|lan|lang|lao|le|lei|leng|li|lia|lian|liang|liao|lie|lin|ling|liu|long|lou|lu|lv|luan|lue".
	                        "|lun|luo|ma|mai|man|mang|mao|me|mei|men|meng|mi|mian|miao|mie|min|ming|miu|mo|mou|mu|na|nai|nan|nang|nao|ne".
	                        "|nei|nen|neng|ni|nian|niang|niao|nie|nin|ning|niu|nong|nu|nv|nuan|nue|nuo|o|ou|pa|pai|pan|pang|pao|pei|pen".
	                        "|peng|pi|pian|piao|pie|pin|ping|po|pu|qi|qia|qian|qiang|qiao|qie|qin|qing|qiong|qiu|qu|quan|que|qun|ran|rang".
	                        "|rao|re|ren|reng|ri|rong|rou|ru|ruan|rui|run|ruo|sa|sai|san|sang|sao|se|sen|seng|sha|shai|shan|shang|shao|".
	                        "she|shen|sheng|shi|shou|shu|shua|shuai|shuan|shuang|shui|shun|shuo|si|song|sou|su|suan|sui|sun|suo|ta|tai|".
	                        "tan|tang|tao|te|teng|ti|tian|tiao|tie|ting|tong|tou|tu|tuan|tui|tun|tuo|wa|wai|wan|wang|wei|wen|weng|wo|wu".
	                        "|xi|xia|xian|xiang|xiao|xie|xin|xing|xiong|xiu|xu|xuan|xue|xun|ya|yan|yang|yao|ye|yi|yin|ying|yo|yong|you".
	                        "|yu|yuan|yue|yun|za|zai|zan|zang|zao|ze|zei|zen|zeng|zha|zhai|zhan|zhang|zhao|zhe|zhen|zheng|zhi|zhong|".
	                        "zhou|zhu|zhua|zhuai|zhuan|zhuang|zhui|zhun|zhuo|zi|zong|zou|zu|zuan|zui|zun|zuo";
	        $_DataValue = "-20319|-20317|-20304|-20295|-20292|-20283|-20265|-20257|-20242|-20230|-20051|-20036|-20032|-20026|-20002|-19990".
	                        "|-19986|-19982|-19976|-19805|-19784|-19775|-19774|-19763|-19756|-19751|-19746|-19741|-19739|-19728|-19725".
	                        "|-19715|-19540|-19531|-19525|-19515|-19500|-19484|-19479|-19467|-19289|-19288|-19281|-19275|-19270|-19263".
	                        "|-19261|-19249|-19243|-19242|-19238|-19235|-19227|-19224|-19218|-19212|-19038|-19023|-19018|-19006|-19003".
	                        "|-18996|-18977|-18961|-18952|-18783|-18774|-18773|-18763|-18756|-18741|-18735|-18731|-18722|-18710|-18697".
	                        "|-18696|-18526|-18518|-18501|-18490|-18478|-18463|-18448|-18447|-18446|-18239|-18237|-18231|-18220|-18211".
	                        "|-18201|-18184|-18183|-18181|-18012|-17997|-17988|-17970|-17964|-17961|-17950|-17947|-17931|-17928|-17922".
	                        "|-17759|-17752|-17733|-17730|-17721|-17703|-17701|-17697|-17692|-17683|-17676|-17496|-17487|-17482|-17468".
	                        "|-17454|-17433|-17427|-17417|-17202|-17185|-16983|-16970|-16942|-16915|-16733|-16708|-16706|-16689|-16664".
	                        "|-16657|-16647|-16474|-16470|-16465|-16459|-16452|-16448|-16433|-16429|-16427|-16423|-16419|-16412|-16407".
	                        "|-16403|-16401|-16393|-16220|-16216|-16212|-16205|-16202|-16187|-16180|-16171|-16169|-16158|-16155|-15959".
	                        "|-15958|-15944|-15933|-15920|-15915|-15903|-15889|-15878|-15707|-15701|-15681|-15667|-15661|-15659|-15652".
	                        "|-15640|-15631|-15625|-15454|-15448|-15436|-15435|-15419|-15416|-15408|-15394|-15385|-15377|-15375|-15369".
	                        "|-15363|-15362|-15183|-15180|-15165|-15158|-15153|-15150|-15149|-15144|-15143|-15141|-15140|-15139|-15128".
	                        "|-15121|-15119|-15117|-15110|-15109|-14941|-14937|-14933|-14930|-14929|-14928|-14926|-14922|-14921|-14914".
	                        "|-14908|-14902|-14894|-14889|-14882|-14873|-14871|-14857|-14678|-14674|-14670|-14668|-14663|-14654|-14645".
	                        "|-14630|-14594|-14429|-14407|-14399|-14384|-14379|-14368|-14355|-14353|-14345|-14170|-14159|-14151|-14149".
	                        "|-14145|-14140|-14137|-14135|-14125|-14123|-14122|-14112|-14109|-14099|-14097|-14094|-14092|-14090|-14087".
	                        "|-14083|-13917|-13914|-13910|-13907|-13906|-13905|-13896|-13894|-13878|-13870|-13859|-13847|-13831|-13658".
	                        "|-13611|-13601|-13406|-13404|-13400|-13398|-13395|-13391|-13387|-13383|-13367|-13359|-13356|-13343|-13340".
	                        "|-13329|-13326|-13318|-13147|-13138|-13120|-13107|-13096|-13095|-13091|-13076|-13068|-13063|-13060|-12888".
	                        "|-12875|-12871|-12860|-12858|-12852|-12849|-12838|-12831|-12829|-12812|-12802|-12607|-12597|-12594|-12585".
	                        "|-12556|-12359|-12346|-12320|-12300|-12120|-12099|-12089|-12074|-12067|-12058|-12039|-11867|-11861|-11847".
	                        "|-11831|-11798|-11781|-11604|-11589|-11536|-11358|-11340|-11339|-11324|-11303|-11097|-11077|-11067|-11055".
	                        "|-11052|-11045|-11041|-11038|-11024|-11020|-11019|-11018|-11014|-10838|-10832|-10815|-10800|-10790|-10780".
	                        "|-10764|-10587|-10544|-10533|-10519|-10331|-10329|-10328|-10322|-10315|-10309|-10307|-10296|-10281|-10274".
	                        "|-10270|-10262|-10260|-10256|-10254";
	        $_TDataKey   = explode('|', $_DataKey);
	        $_TDataValue = explode('|', $_DataValue);
	        $_Data = array_combine($_TDataKey, $_TDataValue);
	        arsort($_Data);
	        reset($_Data);
	        $_String = preg_replace("/^[!@#$%\^&*()_+0-9]*/", '', $_String);
	        if($_Code!= 'gb2312') $_String = Utils::_U2_Utf8_Gb($_String);
	        $_Res = '';
	        for($i=0; $i<strlen($_String); $i++) {
	                $_P = ord(substr($_String, $i, 1));
	                if($_P>160) {
	                        $_Q = ord(substr($_String, ++$i, 1)); $_P = $_P*256 + $_Q - 65536;
	                }
	                $_Res .= Utils::_Pinyin($_P, $_Data);
	        }
	        return  $_Res;
	}
	public static function _Pinyin($_Num, $_Data){
	        if($_Num>0 && $_Num<160 ){
	                return chr($_Num);
	        }elseif($_Num<-20319 || $_Num>-10247){
	                return '';
	        }else{
	                foreach($_Data as $k=>$v){ if($v<=$_Num) break; }
	                return $k;
	        }
	}
	public static function _U2_Utf8_Gb($_C){
	        $_String = '';
	        if($_C < 0x80){
	                $_String .= $_C;
	        }elseif($_C < 0x800) {
	                $_String .= chr(0xC0 | $_C>>6);
	                $_String .= chr(0x80 | $_C & 0x3F);
	        }elseif($_C < 0x10000){
	                $_String .= chr(0xE0 | $_C>>12);
	                $_String .= chr(0x80 | $_C>>6 & 0x3F);
	                $_String .= chr(0x80 | $_C & 0x3F);
	        }elseif($_C < 0x200000) {
	                $_String .= chr(0xF0 | $_C>>18);
	                $_String .= chr(0x80 | $_C>>12 & 0x3F);
	                $_String .= chr(0x80 | $_C>>6 & 0x3F);
	                $_String .= chr(0x80 | $_C & 0x3F);
	        }
          try
          {
	           return iconv('UTF-8', 'GB2312//ignore', $_String);
          }
          catch(\Exception $e)
          {
            return $_String;
          }
	}

  //根据文件id删除文件
  //参数可为文件id或文件id数组
  public static function removeFile_new($path, $container)
  {
    if(empty($container)) return;
    $dm = $container->get('doctrine.odm.mongodb.document_manager');
    if ( is_string($path) && !empty($path) )
    {
      $doc = $dm->getRepository('JustsyMongoDocBundle:WeDocument')->find($path);
      if(!empty($doc)) 
      {
        $dm->remove($doc);
        $dm->flush();
      }
    }
    else if ( is_array($path) && count($path)>0)
    {
        for($i=0;$i< count($path);$i++)
        {
            $fileid = $path[$i];
            if ( !empty($fileid ))
            {
              $doc = $dm->getRepository('JustsyMongoDocBundle:WeDocument')->find($fileid);
              if(!empty($doc)) 
              {
                $dm->remove($doc);
                $dm->flush();
              }
            }          
        }
    }
    return true;
  }  

}