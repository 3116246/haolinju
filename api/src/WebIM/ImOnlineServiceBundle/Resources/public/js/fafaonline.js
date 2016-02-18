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
        if (scripts[i].src.split('?')[0].indexOf('fafaonline.js') > 1) {
            currentScript = scripts[i];
            break;
        }
    }
    var apiName = "";
    var apiVersion = "";
    if (currentScript.src != "") {
        if (currentScript.src.indexOf("?") > -1 ) {
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
                paras.put(paraName,paraValue);
            }
            if(paras.get("orgid")==null || paras.get("orgid")=="") return;
            if(paras.get("parentid")==null || paras.get("parentid")=="" || document.getElementById(paras.get("parentid"))==null){
            	 var pc = document.createElement("DIV");
            	 var dt = new Date();
            	 pc.id="onlineCS"+(""+dt.getFullYear()+dt.getMonth()+dt.getDate());
            	 pc.setAttribute("class","fafa_webim_ocsfontbox_parent");            	 
            	 paras.put("parentid",pc.id);
            	 document.body.appendChild(pc);
            	 ocsDiv = pc;
            }
            getCount(paras);
        }
    }
}
function HashMap() {
    this.hashTable = new Array();
    this.put = function (k, v) {
        if (this.hashTable == null)
            this.hashTable = new Array();
        var vv = typeof (v) == "string" ? v.replace(/\"/g, "&cemh") : v;
        //if(typeof(k)=="string" && isNaN(k))
        //   eval("this.hashTable."+k.replace(/[`|@|#|%|(|)|\[|\]|\\|:|;|\.]/g,"")+"=''");        
        this.hashTable[k] = vv;
    };
    this.get = function (k) {
        var resutl = this.hashTable[k];
        return typeof (resutl) == "string" ? resutl.replace(/&cemh/g, "\"") : resutl;
    };
    this.containsKey = function (k) {
        return this.hashTable[k];
    }
    this.keyString = function () {
        var str = "";
        for (var i in this.hashTable) {
            if (i == "toJSON" || this.hashTable[i] == null || this.hashTable == undefined) continue;
            str += "," + i;
        }
        return str.substring(1);
    }
    this.valueString = function () {
        var str = "";
        for (var i in this.hashTable) {
            if (i == "toJSON" || this.hashTable[i] == null || this.hashTable == undefined) continue;
            str += "," + this.hashTable[i];
        }
        return str.substring(1);
    }
    this.toString = function () {
        var str = "";
        for (var i in this.hashTable) {
            str += ";{";
            for (var j in this.hashTable[i]) {
                str += j + ":\"" + this.hashTable[i][j] + "\",";
            }
            str += "object:self";
            str += "}";
        }
        return str;
    }
    this.count = function () {
        var cnt = 0;
        for (var i in this.hashTable) {
            cnt++;
        }
        return cnt;
    }
    this.keySet = function () {
        return this.hashTable;
    }
}
var ocsDiv =null,loadTimer=null,paras = new HashMap();
function fafa_webim_chat(obj)
{
	 var f = document.getElementById("fafa_webim_ocsfontbox");
	 f = f.getAttribute("fafa").split("/");
	 FaFaChatWin.Connection(f[0]+"/"+f[1],f[2],3);
	 var rosterObj=new roster();
	 var toJid =Jid.Parse( obj.getAttribute("to"));
	 rosterObj.jid = toJid.Bear();
	 rosterObj.resource = toJid.resource;
	 rosterObj.state = obj.parentElement.children[1].children[0].src.indexOf("offline.png")>1 ?"offline":"online";
	 FaFaChatWin.AddRoster(rosterObj);
	 FaFaChatWin.ShowRoster(obj.getAttribute("to"),{marginLeft:-147});
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
        var oScript = document.createElement("script");
        oScript.type = "text/javascript";
        var dt = new Date();
        oScript.src = fafaonline_domain+"/ocs/getcount?eno="+eno+"&show="+showtype+"&t=" + (""+dt.getFullYear()+dt.getMonth()+dt.getDate());
        oHead.appendChild(oScript);
        oScript.onload = oScript.onreadystatechange = function () {
            if (oScript.onreadystatechange && oScript.readyState!=null) {
                var state = oScript.readyState;
                if (state != 'complete' && state != 'loaded') {
                    return;
                }
            }            
            var frmsrc=fafaonline_domain+"/ocs/style?show="+showtype+"&style="+(paras.get("style")==null?"0":paras.get("style"))+"&eno="+eno+(paras.get("text")!=null?"&text="+(paras.get("text")):"");
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
		            t();
		            oScript.onload = oScript.onreadystatechange = null;
		            oScript2.onload = oScript2.onreadystatechange = null;
		            if ( oHead && oScript.parentNode ) { oHead.removeChild( oScript ); } 
		            if ( oHead && oScript2.parentNode ) { oHead.removeChild( oScript2 ); }
		            loadTimer = setTimeout("updateState('"+eno+"','"+showtype+"')",5000);           	
            }
        }
}

function updateState(_eno,_showtype)
{
	  var oHead = document.getElementsByTagName('HEAD').item(0);
    var oScript = document.createElement("script");
    oScript.type = "text/javascript";
    oScript.src = fafaonline_domain+"/ocs/getcount?eno="+_eno+"&show="+_showtype+"&t=" + (new Date()).valueOf();
    oHead.appendChild(oScript);
    oScript.onload = oScript.onreadystatechange = function () {
            if (oScript.onreadystatechange && oScript.readyState!=null) {
                var state = oScript.readyState;
                if (state != 'complete' && state != 'loaded') {
                    return;
                }
            }
            var states = ocs_state;
            oScript.onload = oScript.onreadystatechange = null;
            if (oHead && oScript.parentNode) {
                    oHead.removeChild(oScript);
            }
            if(states==null) return;
            var key;
            for(key in states)
            {
            	  
            	  if(key.indexOf("_resource")>1){
            	  	 var resource = states[key];
            	  	 key = key.replace("_resource","");
            	  	 var eleUrl = document.getElementById(key).children[2].getAttribute("to");            	  	 
            	  	 eleUrl = eleUrl.split("/")[0]+( resource==""?"":"/"+resource);
            	  	 document.getElementById(key).children[2].setAttribute("to",eleUrl);
            	  	 continue;
            	  }
            	  var _Ele =document.getElementById(key);
            	  if(_Ele==null) continue;
                var ele = _Ele.children[1].children[0];
                var srcs = ele.src.split("/");
                var icon = srcs[srcs.length-1];
                if(states[key]=="1" && icon=="online.png") continue;
                if(states[key]=="0" && icon=="offline.png") continue;
                srcs[srcs.length-1]= states[key]=="1"?"online.png":"offline.png";
                ele.src = srcs.join("/");
                if(states[key]=="1")
                   _Ele.children[2].title = "点击立即开始咨询";
                else 
                	 _Ele.children[2].title = "点击可以给我留言";
            }
            clearTimeout(loadTimer);
            loadTimer = setTimeout("updateState('"+_eno+"','"+_showtype+"')",5000); 
    }
}

function t()
{
	  ocsDiv.innerHTML=(s);
	  ocsDiv.setAttribute("class","fafa_webim_ocsfontbox_parent"); 
	  ocsDiv.className="fafa_webim_ocsfontbox_parent";
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

$(document).ready(function(){par();});
