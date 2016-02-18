//参数
//1、orgid。认证企业号。可选
//2、show。连接类别。包括0：在线客服；1：销售；2：前台。默认为0
//3、style。外观样式。以从0开始的序号标识的不同显示样式。默认为0
//4、parentid。父容器id.必填
//5、text。显示文本内容。默认为：在线客服
//6、acc。指定获取的用户的jid帐号
//7、user。当前登录用户。默认为guest

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
        if (scripts[i].src.split('?')[0].indexOf('fafaonline10.js') > 1) {
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
                ocsDiv = pc;
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
var ocsDiv =null,loadTimer=null,paras = new HashMap();
function fafa_webim_chat(obj)
{
	 var rosterObj=new roster();
	 var toJid =Jid.Parse( obj.getAttribute("to"));
	 rosterObj.jid = toJid.Bear();
	 rosterObj.resource = toJid.resource;
	 rosterObj.state = obj.children[0].getAttribute("state");
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
function getCount(paras) {
    var oHead = document.getElementsByTagName('HEAD').item(0);
    var showtype = (paras.get("show") == null ? "0" : paras.get("show"));
    var eno = paras.get("orgid");
    var accs = paras.get("acc");
    if (accs == null || accs == "") {
        accs = [];
        var list = $("[fafa_online_user]");
        for (var i = 0; i < list.length; i++)
            accs.push(list.eq(i).attr("fafa_online_user"));
        accs = accs.join(",");
    }
    var frmsrc = fafaonline_domain+"/ocs/style?acc=" + accs + "&style=10&eno=" + eno;
    if(paras.get("user")!=null) frmsrc = frmsrc+"&user=" + paras.get("user");
    var oScript2 = document.createElement("script");
    oScript2.type = "text/javascript";
    oScript2.src = frmsrc;
    oHead.appendChild(oScript2);
    oScript2.onload = oScript2.onreadystatechange = function () {
        if (oScript2.onreadystatechange && oScript2.readyState != null) {
            var state = oScript2.readyState;
            if (state != 'complete' && state != 'loaded') {
                return;
            }
        }
        t();
        oScript2.onload = oScript2.onreadystatechange = null;
        if (oHead && oScript2.parentNode) { oHead.removeChild(oScript2); }
    }
}
var serviceData = null;
function t() {
    //ocsDiv.innerHTML=(s);
    serviceData = (s);    
    //根据返回的json串，更新对应的人员的在线状态
    
        var f = serviceData[0].split("/");
        FaFaChatWin.init(f[0] + "/" + f[1]);
        //连接服务器
        FaFa_Online.Conn();
        FaFa_Online.UpdateState();
}
var FaFa_Online = {list:new HashMap()};
FaFa_Online.UpdateState = function () {
    //获取页面上的满足更新状态元素
    var list = $("[fafa_online_state]");
    if (serviceData == null) {
        //全部设置为离线
        list.attr("class", "fafa_webim_ocs_offline");
        list.attr("title", "离线");
        return;
    }
    var states = serviceData[1];
    for (var i = 0; i < states.length; i++) {
        var jid = states[i].to;
        var JidObj = Jid.Parse(jid);
        var ctl = $("[fafa_online_user='" + JidObj.user + "']");
        if (ctl.length == 0) continue;
        ctl.attr("to", jid);
        ctl.css({ "position": "relative", "cursor": "pointer" });
        ctl.unbind().bind("click", function () { fafa_webim_chat(this)});
        var _roster = new roster();
        _roster.jid = JidObj.Bear();
        _roster.name = states[i].name;
        _roster.resource = [states[i].resource];
        _roster.state = states[i].state == "1" ? "online" : "offline";
        FaFa_Online.list.put(_roster.jid, _roster);
        changeState(ctl, _roster.state, "", "");
    }
};
FaFa_Online.Conn = function conn() {
    if (typeof (FaFaMessage) == "undefined") {
        setTimeout('FaFa_Online.Conn()', 200);
    }
    else {
        var f = serviceData[0].split("/");
        window.onbeforeunload = function () {
            FaFaMessage.Disconnect("manual");
        }
        FaFaMessage.GetPresence(function (pre) {
            var jidObj = Jid.Parse(pre.From);
            var spans = $("[fafa_online_user='" + jidObj.user + "']");
            if (spans.length == 0) return;
            spans.attr("to", Jid.toString(pre.From));
            var bear = jidObj.Bear();
            var rs = FaFaChatWin.allJid.get(bear);
            if (rs == null) {
                rs = FaFa_Online.list.get(bear) != null ? FaFa_Online.list.get(bear) : new roster();
                rs.jid = bear;
                FaFaChatWin.allJid.put(bear,rs);
            }
            if (pre.Type == "online") {
            	  rs.state = pre.Type;
                rs.addResource(jidObj.resource);
                changeState(spans, pre.Type, pre.Show, pre.Status);
            }
            else {
                //判断还有设备在线没 有
                rs.removeResource(jidObj.resource);
                if (rs.resource.length > 0) {
                    spans.attr("to", rs.GetJid());
                }
                else {
                	  rs.state = pre.Type;
                    changeState(spans, pre.Type, "", "");
                }
            }
        });
        if (typeof (FaFaMessage) != "undefined" && FaFaMessage._conn == null) {
           FaFaMessage.Connection(f[0] + "/" + f[1], f[2]);
        }
    }
}

function changeState(_ctl, type, show, status) {
    var ctl = _ctl.find("[class^='fafa_webim_ocs']");
    if (ctl.length == 0) {
        ctl = "<span class='' style='top:0px'></span>" + _ctl.html();
        _ctl.html(ctl);
        ctl = _ctl.find("span:first");
    }
    if (type == "online") {
        if (show == "") {
            setClass(ctl, "fafa_webim_ocs_online");
            ctl.attr("title", "在线");
        }
        else if (show == "dnd" && (status == "" || status == "null")) {
            setClass(ctl, "fafa_webim_ocs_busy");
            ctl.attr("title", "忙碌");
        }
        else if (show == "dnd" && status == "请勿打扰") {
            setClass(ctl, "fafa_webim_ocs_disturb");
            ctl.attr("title", "请勿打扰");
        }
        else if (show == "away") {
            setClass(ctl, "fafa_webim_ocs_leave");
            ctl.attr("title", "离开");
        }
    }
    else if (type == "offline") {
        setClass(ctl, "fafa_webim_ocs_offline");
        ctl.attr("title", "离线");
    }
    ctl.attr("state", type);
}
function setClass(ctl, cls) {
    ctl.attr("class", cls);
}
$(document).ready(function(){par();});