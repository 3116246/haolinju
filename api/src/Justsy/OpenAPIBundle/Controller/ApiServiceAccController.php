<?php

namespace Justsy\OpenAPIBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\InterfaceBundle\Common\Utils;
use Justsy\BaseBundle\Management\MicroAccountMgr;

///公众号接口类
class ApiServiceAccController extends Controller
{	  
	//获取当前企业的所有公众号列表
	public function getmicroaccountAction()
	{
		  	$request = $this->getRequest();  	
		  	$da = $this->get("we_data_access");
		  	$currUser = $this->get('security.context')->getToken();
		    if(!empty($currUser)){
		       $currUser = $currUser->getUser(); 
		    }
		    else
		    {
		    	  //当应用通过api接口调用时,不用登录,只能通过openid获取人员信息
		    	  $baseinfoCtl = new \Justsy\BaseBundle\Management\Staff($da,null,$request->get("openid"),$this->get("logger"));    	  
		    	  $currUser = $baseinfoCtl->getSessionUser();
		    }
		  	$re = array("returncode" => ReturnCode::$SUCCESS);
		    $micro_use=$request->get('micro_use');
		  	$mode = $request->get("mode");
		  	$mode = empty($mode)||($mode!='EXCLUDE-ATTEN')? false:true;
		  	$mgr = new MicroAccountMgr($da,$this->get("we_data_access_im"),$currUser,$this->get("logger"),$this->container);
		  	$rows = $mgr->getmicroaccount($mode, $micro_use);
		  	for ($i = 0; $i < count($rows); $i++) {
		    		$micro_account=$rows[$i]["number"];
		    		$group=$mgr->getgrouplist($micro_account);
		    		$rows[$i]["grouplist"]=$group;
		    }
		  	$re["list"]=$rows;  	
		    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		    $response->headers->set('Content-Type', 'text/json');
		    return $response;
	}

	public function getattenmicroaccountAction()
	{
		  	$request = $this->getRequest();  	
		  	$da = $this->get("we_data_access");
			//访问权限校验
			$api = new \Justsy\OpenAPIBundle\Controller\ApiController();
		    $api->setContainer($this->container);
		    $isWeFaFaDomain = $api->checkWWWDomain();
		    if(!$isWeFaFaDomain)
		    {
		    	    $token = $api->checkAccessToken($request,$da);	
		    	    if(!$token)
		    	    {
		    	   	   	$re = array("returncode"=>"9999");
					    $re["code"]="err0105";
		    	   	   	$re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
		 				return $this->responseJson($re);
		    	    }
		    }
		    $re = array("returncode" => ReturnCode::$SUCCESS);
		    $baseinfoCtl = new \Justsy\BaseBundle\Management\Staff($da,$this->get("we_data_access_im"),$request->get("openid"),$this->get("logger"),$this->container);        
			$currUser = $baseinfoCtl->getSessionUser();
		  	
		  	$mgr = new MicroAccountMgr($da,$this->get("we_data_access_im"),$currUser,$this->get("logger"),$this->container);
		    
		    $rows = $mgr->getMy();
		  	for ($i = 0; $i < count($rows); $i++) {
		    		$micro_account=$rows[$i]["number"];
		    		$group=$mgr->getgrouplist($micro_account);
		    		$rows[$i]["grouplist"]=$group;
		    }
		    $re["list"] = $rows;
		    $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($re).");" : json_encode($re));
		    $response->headers->set('Content-Type', 'text/json');
		    return $response;
	}

	private function checkmail(){
	      return "/([a-z0-9]*[-_.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[.][a-z]{2,3}([.][a-z]{2})?/i";
	}
    
  //获取公众号历史消息
  public function getHisMessageAction()
  {
	    $request = $this->getRequest();   
	    $conn = $this->get("we_data_access");
	    $conn_im = $this->get("we_data_access_im");
	    $logger=$this->get("logger");
	    $container=$this->container;

		$api = new \Justsy\OpenAPIBundle\Controller\ApiController();
		$api->setContainer($this->container);
		$isWeFaFaDomain = $api->checkWWWDomain();
		if(!$isWeFaFaDomain)
		{
		    	    $token = $api->checkAccessToken($request,$conn);	
		    	    if(!$token)
		    	    {
		    	   	   	$re = array("returncode"=>"9999");
					    $re["code"]="err0105";
		    	   	   	$re["msg"]="参数Appid或Openid或Access_token未指定或无效.";
		 				return $this->responseJson($re);
		    	    }
		}

	      $microaccount=$request->get("microaccount");//微应用帐号
	      $microgroupid=$request->get("microgroupid");//微应用分组ID
	      $pageindex=$request->get("pageindex");//分页索引数
	      $factory = $this->get('security.encoder_factory');

	      if(empty($microaccount)) return $this->responseJson(array("returncode"=>ReturnCode::$SYSERROR,"msg"=>"帐号不能为空."));
	      if(empty($pageindex)) $pageindex=1;

	      
	      $baseinfoCtl = new \Justsy\BaseBundle\Management\Staff($conn,$conn_im,$request->get("openid"),$this->get("logger"),$this->container);        
	      $currUser = $baseinfoCtl->getSessionUser();
	      
	      if(empty($currUser)) return $this->responseJson(array("returncode"=>ReturnCode::$SYSERROR,"msg"=>"无效的操作帐号"));
	      $sql_micro="select number,type from we_micro_account where jid=?";
	      $para_micro=array($microaccount);
	      $data_micro=$conn->GetData("dt",$sql_micro,$para_micro);
	      if($data_micro==null || count($data_micro["dt"]["rows"])==0 || empty($data_micro["dt"]["rows"][0]["number"])){
	          return $this->responseJson(array("returncode"=>ReturnCode::$SYSERROR,"msg"=>"微应用帐号不存在."));        
	      }
	      $microaccount = $data_micro["dt"]["rows"][0]['number'];
	      $login_account=$currUser->getUserName();
	      $micr_type=$data_micro["dt"]["rows"][0]["type"];
	      
	      //var_dump($login_account);
	      
	      $sql_total="select count(1) as count from we_micro_send_message where send_account=? ";
	      $para_total=array($microaccount);
	      if(!empty($microgroupid)) {
	        $sql_total="select count(1) as count from we_micro_send_message where send_account=? and send_groupid=? ";
	        $para_total=array($microaccount,$microgroupid);
	      }
	      $data_total=$conn->GetData("dt",$sql_total,$para_total);
	      $total=0;
	      if($data_total!=null && count($data_total['dt']['rows'])>0) $total=$data_total['dt']['rows'][0]['count'];
	      $totalpage=1;
	      if ($total > 1) $totalpage = ceil($total  / 10  );
	      $startrow=($pageindex-1)*10;
	      $sql="select * from we_micro_send_message where send_account=? order by send_datetime desc LIMIT ".$startrow.",10";
	      $para=array($microaccount);
	      if(!empty($microgroupid)) {
	        $sql="select * from we_micro_send_message where send_account=? and send_groupid=? order by send_datetime desc LIMIT ".$startrow.",10";
	        $para=array($microaccount,$microgroupid);
	      }
	      $re=array('returncode'=>'9999',"msg"=>'消息获取失败');
	      $data_row=$conn->GetData("dt",$sql,$para);
	      //var_dump($sql);
	      if($data_row!=null && count($data_row['dt']['rows'])>0){
	        $objlist=array();
	        $pushMgr = new \Justsy\AdminAppBundle\Controller\MsgPushController();
	        $pushMgr->setContainer($this->container);
	        for ($i=0; $i < count($data_row['dt']['rows']); $i++) { 
	          $send_id=$data_row['dt']['rows'][$i]["id"];
	          $send_type=$data_row['dt']['rows'][$i]["send_type"];
	          $send_datetime=$data_row['dt']['rows'][$i]["send_datetime"];
	          $sql="select * from we_micro_message where send_id=?";
	          $para=array($send_id);
	          $dataitem=$conn->GetData("dt",$sql,$para);
	          if($dataitem!=null && count($dataitem['dt']['rows'])>0) {
	            $list=array("type"=>$send_type,"date"=>$send_datetime);
	            //var_dump($send_type);
	            switch ($send_type) {
	              case 'TEXT':
	                $text_items=array();
	                for ($l=0; $l < count($dataitem['dt']['rows']); $l++) { 
	                  $item=array('title'=>$dataitem['dt']['rows'][$l]["msg_title"]
	                            ,'content'=>$dataitem['dt']['rows'][$l]["msg_text"]);
	                  array_push($text_items, $item);
	                }
	                $list['data']=array('item'=>$text_items);
	                //var_dump($list);
	                break;
	              case 'PICTURE':
	                for ($j=0; $j < count($dataitem['dt']['rows']); $j++) { 
	                  $headitem=array("title"=>$dataitem['dt']['rows'][$j]["msg_title"]
	                    ,'content'=>$dataitem['dt']['rows'][$j]["msg_summary"]
	                    ,'image'=>array('type'=>$dataitem['dt']['rows'][$j]["msg_img_type"],'value'=>$dataitem['dt']['rows'][$j]["msg_img_url"])
	                    ,'link'=>$pushMgr->getLink($dataitem['dt']['rows'][$j]["msg_web_url"]));
	                  $list['data']=array("headitem"=>$headitem);
	                }
	                break;
	              case 'TEXTPICTURE':
	                $items=array();
	                for ($k=0; $k < count($dataitem['dt']['rows']); $k++) { 
	                  $ishead=$dataitem['dt']['rows'][$k]["ishead"];
	                  //var_dump($ishead);
	                  if($ishead=="1"){
	                    $headitem=array("title"=>$dataitem['dt']['rows'][$k]["msg_title"]
	                    ,'content'=>$dataitem['dt']['rows'][$k]["msg_text"]
	                    ,'image'=>array('type'=>$dataitem['dt']['rows'][$k]["msg_img_type"],'value'=>$dataitem['dt']['rows'][$k]["msg_img_url"])
	                    ,'link'=>$pushMgr->getLink($dataitem['dt']['rows'][$k]["msg_web_url"]));
	                    $data['headitem']=$headitem;
	                  }else{
	                    $item=array("title"=>$dataitem['dt']['rows'][$k]["msg_title"]
	                    ,'content'=>$dataitem['dt']['rows'][$k]["msg_text"]
	                    ,'image'=>array('type'=>$dataitem['dt']['rows'][$k]["msg_img_type"],'value'=>$dataitem['dt']['rows'][$k]["msg_img_url"])
	                    ,'link'=>$pushMgr->getLink($dataitem['dt']['rows'][$k]["msg_web_url"]));
	                    array_push($items, $item);
	                  }
	                }
	                if(!empty($items)) $data['item']=$items;
	                $list['data']=$data;
	                break;
	            }
	            array_push($objlist, $list);
	          }
	        }
	        if(!empty($objlist)) $re=array('returncode'=>'0000',"total"=>$total,'totalpage'=>$totalpage,'list'=>$objlist);
	      }else{
	        $re=array('returncode'=>'0000',"total"=>0,'totalpage'=>1,'list'=>array());
	      }
	      return $this->responseJson($re);
  }

  private function responseJson($re,$isjson=true){
    if($isjson){
      $response = new Response(json_encode($re));
      $response->headers->set('Content-Type', 'text/json');
      return $response;
    }else{
      $response = new Response($re);
      $response->headers->set('Content-Type', 'text/html');
      return $response;
    }
  }
  private function do_post_request($url, $data, $optional_headers = null)
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
  //请求URL地址
  private function http_request( 
      $url,                      /* Target IP/Hostname */ 
      $postdata = array(),       /* HTTP POST Data ie. array('var1' => 'val1', 'var2' => 'val2') */ 
      $verb = 'POST',             /* HTTP Request Method (GET and POST supported) */    
      $timeout = 1000,           /* Socket timeout in milliseconds */ 
      $req_hdr = false,          /* Include HTTP request headers */ 
      $res_hdr = false           /* Include HTTP response headers */ 
      ) { 
      $url = parse_url($url);
      //var_dump($url);
      $port = $url['port']==null?(($url['scheme']=='https')?443:80):$url['port'];
      if(!$url) return "couldn't parse url";
      $ip = $url['host'];
      $uri = $url['path'];
      $ret = ''; 
      $verb = strtoupper($verb); 
      $postdata_str = ''; 
      foreach ($postdata as $k => $v){
          $postdata_str .= urlencode($k) .'='. urlencode($v) .'&'; 
      }
      $crlf = "\r\n"; 
      $req = $verb .' '. $uri . $getdata_str .' HTTP/1.1' . $crlf; 
      $req .= 'Host: '. $ip . $crlf; 
      $req .= 'User-Agent: Mozilla/5.0 Firefox/3.6.12' . $crlf; 
      $req .= 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8' . $crlf; 
      $req .= 'Accept-Language: en-us,en;q=0.5' . $crlf; 
      $req .= 'Accept-Encoding: deflate' . $crlf; 
      $req .= 'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7' . $crlf; 
      if ($verb == 'POST' && !empty($postdata_str)) { 
        //$postdata_str = strend($postdata_str,"&");
        //var_dump($postdata_str);
        $postdata_str = substr($postdata_str, 0, -1); 
        $req .= 'Content-Type: application/x-www-form-urlencoded' . $crlf; 
        $req .= 'Content-Length: '. strlen($postdata_str) . $crlf . $crlf; 
        $req .= $postdata_str; 
      } else {
        $req .= $crlf; 
      }  
      if ($req_hdr) $ret .= $req; 
      if (($fp = @fsockopen($ip, $port, $errno, $errstr)) == false) return "Error $errno: $errstr\n"; 
      stream_set_timeout($fp, 0, $timeout * 1000); 
      fputs($fp, $req); 
      while ($line = fgets($fp)){
        //var_dump($line);
        $ret .= $line;
      }
      fclose($fp); 
      if (!$res_hdr){
          $ret = substr($ret, strpos($ret, "\r\n\r\n") +4); 
      }
      return $ret; 
  } 
}
