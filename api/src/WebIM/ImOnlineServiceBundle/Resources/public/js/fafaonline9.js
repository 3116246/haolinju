//参数
//1、orgid。认证企业号。可选
//2、show。连接类别。包括0：在线客服；1：销售；2：前台。默认为0
//3、style。外观样式。以从0开始的序号标识的不同显示样式。默认为0
//4、parentid。父容器id.必填
//5、text。显示文本内容。默认为：在线客服

if(document.currentScript==null)
{
        var scripts = document.getElementsByTagName("script");
        var  reg = /fafaonline9([.-]\d)*\.js(\W|$)/i
        for(var i = 0 , n = scripts.length ; i <n ; i++){
            var src = !!document.querySelector ? scripts[i].src 
                          :scripts[i].getAttribute("src",4);
            if(src && reg.test(src)){
            	  currentScriptSrc = src;
                srcs	 = currentScriptSrc.split("/");
                break;
            }
        } 
}
else
{
	  currentScriptSrc = document.currentScript.src;
	  srcs = currentScriptSrc.split("/");    
}
var fafaonline_domain = srcs[0]+"//"+ srcs[2];
loadOcsCss();

function par() {
    var scripts = document.getElementsByTagName('script');
    var currentScript = "";
    for (var i = 0; i < scripts.length; i++) {
        if (scripts[i].src.split('?')[0].indexOf('fafaonline9.js') > 1) {
            currentScript = scripts[i];
            break;
        }
    }
    var apiName = "";
    var apiVersion = "";
    if (currentScript.src != "") {
        if (currentScript.src.indexOf("?") > -1) {
            // 获取链接中参数部分    
            var queryString = currentScript.src.substring(currentScript.src.indexOf("?") + 1);
            // 分离参数对 ?key=value&key2=value2    
            var parameters = queryString.split("&");
            var pos, paraName, paraValue;
            for (var i = 0; i < parameters.length; i++) {
                // 获取等号位置
                pos = parameters[i].indexOf('=');
                if (pos == -1) { continue; }
                // 获取name 和 value
                paraName = parameters[i].substring(0, pos);
                paraValue = parameters[i].substring(pos + 1);
                // 如果查询的name等于当前name，就返回当前值，同时，将链接中的+号还原成空格
                paraValue = (paraValue.replace(/\+/g, " "));
                paras.put(paraName, paraValue);
            }
            if (paras.get("orgid") == null || paras.get("orgid") == "") return;
            if (paras.get("parentid") == null || paras.get("parentid") == "" || document.getElementById(paras.get("parentid")) == null) {
                var pc = document.createElement("DIV");
                var dt = new Date();
                pc.id = "onlineCS" + ("" + dt.getFullYear() + dt.getMonth() + dt.getDate());
                paras.put("parentid", pc.id);
                document.body.appendChild(pc);
                ocsDiv2 = pc;
            }
            getCount(paras);
        }
    }
}

var getEventCoord = function (e) {
    var evt = e || event, d = document,
	scrollEl = /^b/i.test(d.compatMode) ? d.body : d.documentElement,
	supportPage = typeof evt.pageX == 'number',
	supportLayer = typeof evt.layerX == 'number';
    return {
        pageX: supportPage ? evt.pageX : evt.clientX + scrollEl.scrollLeft,
        pageY: supportPage ? evt.pageY : evt.clientY + scrollEl.scrollTop,
        clientX: evt.clientX,
        clientY: evt.clientY,
        layerX: supportLayer ? evt.layerX : evt.offsetX,
        layerY: supportLayer ? evt.layerY : evt.offsetY
    }
};
/** 
* 下面是一些基础函数，解决mouseover与mouserout事件不停切换的问题（问题不是由冒泡产生的） 
*/
function checkHover(e, target) {
    if (getEvent(e).type == "mouseover") {
        return !contains(target, getEvent(e).relatedTarget
                || getEvent(e).fromElement)
                && !((getEvent(e).relatedTarget || getEvent(e).fromElement) === target);
    } else {
        return !contains(target, getEvent(e).relatedTarget
                || getEvent(e).toElement)
                && !((getEvent(e).relatedTarget || getEvent(e).toElement) === target);
    }
}

function contains(parentNode, childNode) {
    if (parentNode.contains) {
        return parentNode != childNode && parentNode.contains(childNode);
    } else {
        return !!(parentNode.compareDocumentPosition(childNode) & 16);
    }
}
//取得当前window对象的事件  
function getEvent(e) {
    return e || window.event;
}
var ocsDiv =null,ocsDiv2=null,loadTimer=null,paras = new HashMap();

function fafa_webim_chat(obj)
{
	 var rosterObj=new roster();
	 var toJid =Jid.Parse( obj.getAttribute("to"));
	 rosterObj.jid = toJid.Bear();
	 rosterObj.resource = toJid.resource;
	 rosterObj.state = obj.parentElement.children[2].children[0].getAttribute("state");
	 FaFaChatWin.AddRoster(rosterObj);
	 FaFaChatWin.ShowRoster(obj.getAttribute("to"),{marginLeft:-147});
	 var tmp = FaFaChatWin.allJid.get(rosterObj.jid);
	 if(tmp!=null && tmp.state=="offline")
	    FaFaChatWin.Hint(tmp.name+"当前不在线，你可以给TA发送离线消息！",5000);
}
function fafa_webim_chat2(obj){
	var rosterObj=new roster();
 var toJid =Jid.Parse( obj.getAttribute("to"));
 rosterObj.jid = toJid.Bear();
 rosterObj.resource = toJid.resource;
 rosterObj.state = obj.getAttribute("state");
 FaFaChatWin.AddRoster(rosterObj);
 FaFaChatWin.ShowRoster(obj.getAttribute("to"),{marginLeft:-147});
 var tmp = FaFaChatWin.allJid.get(rosterObj.jid);
 if(tmp!=null && tmp.state=="offline")
    FaFaChatWin.Hint(tmp.name+"当前不在线，你可以给TA发送离线消息！",5000);
}
function loadOcsCss()
{
        var oHead = document.getElementsByTagName('HEAD').item(0);
        var cssTag = document.createElement('link');
				cssTag.setAttribute('rel','stylesheet');
				cssTag.setAttribute('type','text/css');
				cssTag.setAttribute('href',fafaonline_domain+'/bundles/fafawebimimonlineservice/css/message.css');
				oHead.appendChild(cssTag);					
}
function getCount(paras)
{
	      var oHead = document.getElementsByTagName('HEAD').item(0);
	      var showtype=(paras.get("show")==null?"0":paras.get("show"));
	      var eno = paras.get("orgid");                   
            var frmsrc=fafaonline_domain+"/ocs/style?acc="+(paras.get("acc")==null?"":paras.get("acc"))+"&show="+showtype+"&style="+(paras.get("style")==null?"0":paras.get("style"))+"&eno="+eno+(paras.get("text")!=null?"&text="+(paras.get("text")):"");
            var oScript2 = document.createElement("script");
            oScript2.type = "text/javascript";
            oScript2.src = frmsrc;
            oHead.appendChild(oScript2); 
            oScript2.onload = oScript2.onreadystatechange =function(){
		            if (oScript2.onreadystatechange && oScript2.readyState!=null) {
		                var state = oScript2.readyState;
		                if (state != 'complete' && state != 'loaded') {
		                    return;
		                }
		            }
		            t2();
		            oScript2.onload = oScript2.onreadystatechange = null;
		            if ( oHead && oScript2.parentNode ) { oHead.removeChild( oScript2 ); }        	
            }
}
var showTimer=null,hieTimer=null;
function showOcs()
{
	  document.getElementById("fafa_webim_ocs_100082_onlineuser").style.display="";
	  ocsDiv.setAttribute("class","fafa_webim_ocs_100082_window2"); 
	  ocsDiv.className="fafa_webim_ocs_100082_window2";
}
function hideOcs()
{
	  document.getElementById("fafa_webim_ocs_100082_onlineuser").style.display="none";
	  ocsDiv.setAttribute("class","fafa_webim_ocs_100082_window"); 
	  ocsDiv.className="fafa_webim_ocs_100082_window";
}
function t2(){
		ocsDiv2.innerHTML=(s);
	  ocsDiv2.setAttribute("class","fafa_webim_ocs_100082_window3"); 
	  ocsDiv2.className="fafa_webim_ocs_100082_window3";
	  ocsDiv2.onmouseover=function(e){
	  	clearTimeout(hieTimer);
	  	if(checkHover(e,this))
	  	{
	  	    	showTimer = setTimeout("showOcs2()",500);
	  	}
	  }
	  ocsDiv2.onmouseout=function(e){
	  	clearTimeout(showTimer);
	  	if(checkHover(e,this))
	  	{
	  	    	hieTimer = setTimeout("hideOcs2()",500);
	  	}
	  }
 
	  if (typeof (FaFaMessage) != "undefined" && FaFaMessage._conn==null){	
		  //连接服务器
	    conn2();
    }
}
function showOcs2()
{
	
}
function hideOcs2()
{
		
}
function t()
{
	  ocsDiv.innerHTML=(s);
	  ocsDiv.setAttribute("class","fafa_webim_ocs_100082_window"); 
	  ocsDiv.className="fafa_webim_ocs_100082_window";
	  ocsDiv.onmouseover=function(e){
	  	clearTimeout(hieTimer);
	  	if(checkHover(e,this))
	  	{
	  	    	showTimer = setTimeout("showOcs()",500);
	  	}
	  }
	  ocsDiv.onmouseout=function(e){
	  	clearTimeout(showTimer);
	  	if(checkHover(e,this))
	  	{
	  	    	hieTimer = setTimeout("hideOcs()",500);
	  	}
	  }
 
	  if (typeof (FaFaMessage) != "undefined" && FaFaMessage._conn==null){	
		  //连接服务器
	    conn();
    }
}
var _u={};
function conn2(){
	if (typeof (FaFaMessage) == "undefined") {
     setTimeout('conn2()', 200);
  }
  else{
  	window.onbeforeunload = function () {
       FaFaMessage.Disconnect("manual");
    }
    var f=document.getElementById("fafa_webim_ocs_100082_users");
    if(f==null)return;
    f = f.getAttribute("fafa").split("/");
    FaFaChatWin.init(f[0]+"/"+f[1]);	
    _u._u = f[0] + "/" + f[1];
    _u._p = f[2];
    FaFaMessage.ConnectionStateChange(function (status, info) {
	     if (status > 5 && status!=8 && info!="manual") {
	     	   FaFaMessage.RestartConn();
	     }
	     else if(status==5 || status==8)
	     {
	     	  var lst = $(".fafa_webim_ocs_employee_name");
	     	  for(var i=0; i<lst.length; i++)
	     	  {
	     	  	 var to = lst[i].getAttribute("to");
	     	  	 if(to==null || to=="") continue;
	           FaFaEmployee.Subscribe(to);	
	        }
	     }
	  });
	  FaFaMessage.GetPresence(function(pre){
	  	
	  	var fromJid = Jid.Bear(pre.From);
      var spans = document.getElementsByTagName("div");
      for (var i = 0; i < spans.length; i++) {
          var to = spans[i].getAttribute("to");
          var li = spans[i].parentElement;
          var classname = spans[i].getAttribute("className") || spans[i].getAttribute("class");
          if (classname == "fafa_webim_ocs_employee_name" && to.indexOf(fromJid) > -1) {
              to = Jid.toString(pre.From);
              spans[i].setAttribute("to", to);
              var jidObj = Jid.Parse(pre.From);
              var rs = FaFaChatWin.allJid.get(jidObj.Bear());
              if(rs==null){
              	 rs=new roster();
								 rs.jid = jidObj.Bear();
              	 FaFaChatWin.allJid.put(rs.jid,rs);
              }
              if (pre.Type == "online") {
              	  $("#fafa_webim_ocs_100082_users").attr("title","你好，我正在为您服务！");
              	  rs.state = pre.Type;
                  rs.addResource(jidObj.resource);
                  var _jid_f = rs.GetJid();  //根据资源优先级返回最优先的jid
                  jidObj = Jid.Parse(_jid_f);
                  if (jidObj.resource == "FaFaWin") {
                      li.children[0].children[1].innerHTML="PC在线";
                      $(li.children[1].children[0]).attr("class","");
                  }
                  else if(jidObj.resource == "FaFaWeb"){
                  	li.children[0].children[1].innerHTML="Web在线";
                  	$(li.children[1].children[0]).attr("class","");
                  }
                  else if(jidObj.resource =="FaFaAndroid"){
                  	li.children[0].children[1].innerHTML="Android在线";
                  	$(li.children[1].children[0]).attr("class","service-resource");
                  	li.children[1].children[0].style.background="url("+fafaonline_domain+"/bundles/fafawebimimonlineservice/images/android.png"+") no-repeat scroll 0 0 rgba(0, 0, 0, 0)";
                  } 
                  else if(jidObj.resource =="FaFaIPhone"){
                  	li.children[0].children[1].innerHTML="IPhone在线";
                  	$(li.children[1].children[0]).attr("class","service-resource");
                  	li.children[1].children[0].style.background="url("+fafaonline_domain+"/bundles/fafawebimimonlineservice/images/phone.png"+") no-repeat scroll 0 0 rgba(0, 0, 0, 0)";
                  }
                  else if(jidObj.resource =="iPad"){
                  	li.children[0].children[1].innerHTML="iPad在线";
                  	$(li.children[1].children[0]).attr("class","service-resource");
                  	li.children[1].children[0].style.background="url("+fafaonline_domain+"/bundles/fafawebimimonlineservice/images/iPad.png"+") no-repeat scroll 0 0 rgba(0, 0, 0, 0)";
                  }
                  else
                      li.children[0].children[1].innerHTML="在线";
                  //li.children[2].children[0].src = li.children[2].children[0].src.replace("offline.png", "online.png");
              }
              else {
                  //判断还有设备在线没 有
                  rs.removeResource(jidObj.resource);
                  if (rs.resource.length > 0) {
                  	  var _jid_f = rs.GetJid();  //根据资源优先级返回最优先的jid
                      to = _jid_f;
                      spans[i].setAttribute("to", to);                      
                        if (to.indexOf("FaFaWin") > -1 ) {
                        	 li.children[0].children[1].innerHTML="PC在线";
                        	 $(li.children[1].children[0]).attr("class",""); 
                        }
			                  else if(to.indexOf("FaFaWeb") > -1){
			                  	li.children[0].children[1].innerHTML="Web在线";
			                  	$(li.children[1].children[0]).attr("class","");
			                  }                        
                      	else if(to.indexOf("FaFaAndroid")>-1){
                      		$(li.children[1].children[0]).attr("class","service-resource");
                      		li.children[0].children[1].innerHTML="Android在线";
          								li.children[1].children[0].style.background="url("+fafaonline_domain+"/bundles/fafawebimimonlineservice/images/android.png"+") no-repeat scroll 0 0 rgba(0, 0, 0, 0)";
          							}
                        else if(to.indexOf("FaFaIPhone")>-1){
                        	$(li.children[1].children[0]).attr("class","service-resource");
                        	li.children[0].children[1].innerHTML="IPhone在线";
                        	li.children[1].children[0].style.background="url("+fafaonline_domain+"/bundles/fafawebimimonlineservice/images/phone.png"+") no-repeat scroll 0 0 rgba(0, 0, 0, 0)";
                        }
                        else if(to.indexOf("iPad")>-1){
                        	$(li.children[1].children[0]).attr("class","service-resource");
                        	li.children[0].children[1].innerHTML="iPad在线";
                        	li.children[1].children[0].style.background="url("+fafaonline_domain+"/bundles/fafawebimimonlineservice/images/iPad.png"+") no-repeat scroll 0 0 rgba(0, 0, 0, 0)";
                        } 
                  }
                  else {
                  	  $("#fafa_webim_ocs_100082_users").attr("title","暂时不在，请给我留言吧！");
                  	  rs.state = pre.Type;
                      li.children[0].children[1].innerHTML="离线";
                      li.children[1].children[0].style.background="url("+fafaonline_domain+"/bundles/fafawebimimonlineservice/images/offline.png"+") no-repeat scroll 0 0 rgba(0, 0, 0, 0)";
                      //li.children[2].children[0].src = li.children[2].children[0].src.replace("online.png", "offline.png");
                  }
              }
              break;
          }
      }
	  });
	  setTimeout(function(){FaFaMessage.Connection(_u._u, _u._p)},100);
	}
}
function conn() {
    if (typeof (FaFaMessage) == "undefined") {
        setTimeout('conn()', 200);
    }
    else {
        window.onbeforeunload = function () {
            FaFaMessage.Disconnect("manual");
        }
        var f = document.getElementById("fafa_webim_ocs_100082_onlineuser");
        if(f==null) return;
        f = f.parentElement;
        f = f.getAttribute("fafa").split("/");
        FaFaChatWin.init(f[0]+"/"+f[1]);	
        _u._u = f[0] + "/" + f[1];
        _u._p = f[2];
        FaFaMessage.ConnectionStateChange(function (status, info) {
           if (status > 5 && status!=8 && info!="manual") {
           	   FaFaMessage.RestartConn();
           }
        });
        FaFaMessage.GetPresence(function (pre) {
            var fromJid = Jid.Bear(pre.From);
            var spans = document.getElementsByTagName("span");
            for (var i = 0; i < spans.length; i++) {
                var to = spans[i].getAttribute("to");
                var li = spans[i].parentElement;
                var classname = spans[i].getAttribute("className") || spans[i].getAttribute("class");
                if (classname == "fafa_webim_ocs_employee_name" && to.indexOf(fromJid) > -1) {
                    to = Jid.toString(pre.From);
                    spans[i].setAttribute("to", to);
                    var jidObj = Jid.Parse(pre.From);
                    var rs = FaFaChatWin.allJid.get(jidObj.Bear());
                    if(rs==null){
                    	 rs=new roster();
											 rs.jid = jidObj.Bear();
                    	 FaFaChatWin.allJid.put(rs.jid,rs);
                    }
                    if (pre.Type == "online") {
                    	  rs.state = pre.Type;
                        rs.addResource(jidObj.resource);
                        if (jidObj.resource != "FaFaWin" && jidObj.resource != "FaFaWeb") {
                            li.children[0].innerHTML = "<div style='width:16px; height:16px;'><img src='"+fafaonline_domain+"/bundles/fafawebimimonlineservice/images/phone.png'></div>";
                        } else
                            li.children[0].innerHTML = "<div style='width:16px; height:16px;'></div>";
                        li.children[2].setAttribute("title", "在线");
                        changeState(li.children[2].children[0], pre.Type, pre.Show, pre.Status);
                        //li.children[2].children[0].src = li.children[2].children[0].src.replace("offline.png", "online.png");
                    }
                    else {
                        //判断还有设备在线没 有
                        rs.removeResource(jidObj.resource);
                        if (rs.resource.length > 0) {
                            to = jidObj.Bear() + "/" + (typeof (rs.resource) == "string" ? rs.resource : rs.resource[0]);
                            spans[i].setAttribute("to", to);
                            if (to.indexOf("FaFaWin") == -1 && to.indexOf("FaFaWeb") == -1) {
                                li.children[0].innerHTML = "<div style='width:16px; height:16px;'><img src='"+fafaonline_domain+"/bundles/fafawebimimonlineservice/images/phone.png'></div>";
                            }
                            else
                                li.children[0].innerHTML = "<div style='width:16px; height:16px;'></div>";
                        }
                        else {
                        	  rs.state = pre.Type;
                            li.children[0].innerHTML = '<div style="width:16px; height:16px;"></div>';
                            li.children[2].setAttribute("title", "离线");
                            changeState(li.children[2].children[0], pre.Type,"","");
                            //li.children[2].children[0].src = li.children[2].children[0].src.replace("online.png", "offline.png");
                        }
                    }
                    break;
                }
            }
        });
        
        setTimeout(function(){FaFaMessage.Connection(_u._u, _u._p)},100);
    }
}

function changeState(ctl,type,show,status) {
    if (type == "online") {
        if (show == "") {
            setClass(ctl, "fafa_webim_ocs_online");
            ctl.parentElement.setAttribute("title", "在线");
        }
        else if (show == "dnd" && (status == "请勿打扰"||status == "会议中")) {
            setClass(ctl, "fafa_webim_ocs_disturb");
            ctl.parentElement.setAttribute("title", "请勿打扰");
        }
        else if (show == "dnd" ) {
            setClass(ctl, "fafa_webim_ocs_busy");
            ctl.parentElement.setAttribute("title", "忙碌");
        }
        else if (show == "away") {
            setClass(ctl, "fafa_webim_ocs_leave");
            ctl.parentElement.setAttribute("title", "离开");
        }
    }
    else if (type == "offline") {
        setClass(ctl, "fafa_webim_ocs_offline");
        ctl.parentElement.setAttribute("title", "离线");
    }
    ctl.setAttribute("state", type);
    ctl.state = type;
}
function setClass(ctl,cls)
{
	  ctl.setAttribute("class",cls); 
	  ctl.className=cls;
}

function minw()
{
	var nar = document.getElementById("fafa_webim_ocsnar");
	if(document.getElementById("fafa_webim_ocsboxcenter").style.display!="none"){
		document.getElementById("fafa_webim_ocsboxcenter").style.display="none";
		document.getElementById("fafa_webim_ocsboxfooter").style.display="none";
		document.getElementById("fafa_webim_ocstitle").style.display="none";			
		nar.title="还原";
	  document.getElementById("fafa_webim_ocsboxtop").style.height="23px";
	  if(document.all==null)
	      nar.setAttribute("class","fafa_webim_ocs_nar_max");
	  else
	  	  nar.className="fafa_webim_ocs_nar_max";
  }
  else
  {
		nar.title="最小化";
	  if (ocsDiv.style.removeProperty) {
        ocsDiv.style.removeProperty ("height");//ie9、 without ie
    } 
    else
    {
        ocsDiv.style.removeAttribute ("height");//IE
    }
	  document.getElementById("fafa_webim_ocsboxtop").style.height="64px";
		document.getElementById("fafa_webim_ocsboxcenter").style.display="";
		document.getElementById("fafa_webim_ocsboxfooter").style.display="";
		document.getElementById("fafa_webim_ocstitle").style.display="";	 
		if(document.all==null)
	      nar.setAttribute("class","fafa_webim_ocs_nar");
	  else
	  	  nar.className="fafa_webim_ocs_nar";  	
  }
}

$(document).ready(function(){
	 window.MessageAPI.init=function(){
	    if (typeof (FaFaMessage) != "undefined" && FaFaMessage._conn==null){
				//连接服务器
	      conn2();
      }
	 }
	 par();
});
