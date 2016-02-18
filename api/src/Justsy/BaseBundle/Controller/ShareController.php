<?php
namespace Justsy\BaseBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Justsy\BaseBundle\DataAccess\SysSeq;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ShareController extends Controller
{  
  public function indexAction()
  {
    $request = $this->getRequest();
    $user = $this->get('security.context')->getToken()->getUser();
    $list["account"] = $user->getUsername();
    $list["nick"] = $user->nick_name;
    $list["share_content"] = $request->get("title");
    //$http = (isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!='off')?'https://':'http://'; 
    //$port = $_SERVER["SERVER_PORT"]==80?'':':'.$_SERVER["SERVER_PORT"];  
    //$ref_url =$_SERVER['SERVER_NAME'].$port;
    $list["ref_url"] = $_SERVER['HTTP_REFERER'];
    return $this->render('JustsyBaseBundle:Share:index.html.twig',$list);
  }
  
  public function shareloginAction(Request $request)
  {
    if ($this->get('request')->attributes->has(SecurityContext::AUTHENTICATION_ERROR))
      $error = $this->get('request')->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
    else
      $error = $this->get('request')->getSession()->get(SecurityContext::AUTHENTICATION_ERROR);
    $u = $this->get('request')->getSession()->get(SecurityContext::LAST_USERNAME);
    $http = (isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!='off')?'https://':'http://'; 
    $port = $_SERVER["SERVER_PORT"]==80?'':':'.$_SERVER["SERVER_PORT"];  
    $ref_url = $_SERVER['SERVER_NAME'].$port;
    return $this->render('JustsyBaseBundle:Share:sharelogin.html.twig', array('error' => $error,'_user'=> $u, 'ref_url'=>$ref_url));
  }
  
  public function login_checkAction($name) {}
  
  //写动态到wefafa，对应表we_conv_list
	public function wefafaShareAction(Request $request){
	  $result = true;
	  $da = $this->container->get('we_data_access');
	  $conv_id = SysSeq::GetSeqNextValue($da,"we_convers_list","conv_id");
	  $account = $request->get("account");
	  $content = $request->get("content");
	  $reason = $request->get("reason"); //分享理由
	  $group_id = $request->get("group_id");
	  $circle_id = $request->get("circle_id");
	  $ref_url = $request->get("ref_url");
    $tmp = parse_url($ref_url);
		$host = $tmp["host"];
		$attachs = null;
//		if(!empty($host) && !Utils::is_ip($host))
//		{
//			  $host = strpos($host,".")===false? $host : substr($host, strpos($host,".")+1);
//		}
    $conv = new \Justsy\BaseBundle\Business\Conv();
    $conv->newShareTrend($da, $account, $conv_id, $reason, $content, $circle_id, $group_id, $ref_url, $attachs, $host ,$this->container);
      	  
//	  $sql = "insert into we_convers_list(conv_id,login_account,post_date,conv_type_id,conv_root_id,conv_content,post_to_group,post_to_circle,comefrom)values(?,?,now(),?,?,?,?,?,?)";
//	  $parameter = array($conv_id,$account,"98",$conv_id,$content,$group_id,$circle_id,$ref_url);
//	  try{
//	    $da->ExecSQL($sql,$parameter);
//	  }
//	  catch (\Exception $e){
//	    $result = false;
//	  }
	  $response = new Response($request->get('jsoncallback') ? $request->get('jsoncallback')."(".json_encode($result).");" : json_encode($result));
	  $response->headers->set('Content-Type', 'text/json');
	  return $response;
	}
}