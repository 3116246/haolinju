var LoadJs = {}, LoadCSS = {};
var Apis = new HashMap();
Apis.put("OnlineService", "faonlineserviceapi"); //在线客服API
Apis.put("RosterList", "farosterapi"); //好友列表API
Apis.put("Message", "famessageapi"); //消息API
Apis.put("Im-Server", "http://justchat.justsy.com:5281/http-bind");
Apis.put("Im-Server-https", "https://justchat.justsy.com:5281/http-bind");
Apis.put("Im-Server-http", "http://justchat.justsy.com:5280/http-bind");
window.MessageAPI = {};
window.OnlineServiceAPI = {};
var srcs = [],
    currentScriptSrc = "",
    apiName = "",
    apiVersion = "";
if (document.currentScript == null) {
    var scripts = document.getElementsByTagName("script");
    var reg = /faapi([.-]\d)*\.js(\W|$)/i
    for (var i = 0, n = scripts.length; i < n; i++) {
        var src = !! document.querySelector ? scripts[i].src : scripts[i].getAttribute("src", 4);
        if (src && reg.test(src)) {
            currentScriptSrc = src;
            srcs = currentScriptSrc.split("/");
            break;
        }
    }
} else {
    currentScriptSrc = document.currentScript.src;
    srcs = currentScriptSrc.split("/");
}
LoadJs.domain = srcs[0] + "//" + srcs[2];
if (currentScriptSrc.indexOf("?") > -1) {
    // 获取链接中参数部分    
    var queryString = currentScriptSrc.substring(currentScriptSrc.indexOf("?") + 1);
    // 分离参数对 ?key=value&key2=value2    
    var parameters = queryString.split("&");
    var pos, paraName, paraValue;
    for (var i = 0; i < parameters.length; i++) {
        // 获取等号位置
        pos = parameters[i].indexOf('=');
        if (pos == -1) {
            continue;
        }
        // 获取name 和 value        
        paraName = parameters[i].substring(0, pos);
        paraValue = parameters[i].substring(pos + 1);
        if (paraName == "server") {
            var s = (paraValue.indexOf("http") == -1 ? "http://" : "") + paraValue;
            s = s + (s.split(":").length == 2 ? ":5280" : "");
            Apis.put("Im-Server", s + "/http-bind");
        }
        // 如果查询的name等于当前name，就返回当前值，同时，将链接中的+号还原成空格
        else if (paraName == "api") {
            apiName = unescape(paraValue.replace(/\+/g, " "));
        } else if (paraName == "v") {
            apiVersion = unescape(paraValue.replace(/\+/g, " "));
        }
    }
}
LoadCSS.load = function(cssfile) {
    var isRef = false;
    var scripts = document.getElementsByTagName("LINK");
    if (scripts != null) {
        for (var i = 0; i < scripts.length; i++) {
            if (scripts[i].href.split("?")[0].indexOf(cssfile) > -1) {
                isRef = true;
                break;
            }
        }
    }
    if (!isRef) {
        var head = document.getElementsByTagName('head').item(0);
        css = document.createElement('link');
        css.href = cssfile.indexOf("/") == -1 ? (LoadJs.domain + "/bundles/justsyopenapi/css/" + cssfile) : cssfile;
        css.rel = 'stylesheet';
        css.type = 'text/css';
        head.appendChild(css);
    }
}
LoadJs.load = function(jsName) {
    //判断是否引入了对应的api JS文件
    var isRef = false;
    var scripts = document.getElementsByTagName("SCRIPT");
    var jsNameTmp = jsName;
    if (scripts != null) {
        if (jsName.indexOf("/") > 0) {
            jsName = jsName.split("?")[0]; //去除参数
            jsName = jsName.split("/").pop(); //取出文件名
        }
        for (var i = 0; i < scripts.length; i++) {
            if (scripts[i].src.split("?")[0].indexOf(jsName) > -1) {
                isRef = true;
                break;
            }
        }
    }
    jsName = jsNameTmp;
    if (!isRef) {
        var oHead = document.getElementsByTagName('HEAD').item(0);
        var oScript = document.createElement("script");
        oScript.type = "text/javascript";
        oScript.src = (jsName.indexOf("/") == -1 ? (LoadJs.domain + "/bundles/justsyopenapi/js/" + jsName) : (jsName)) + "?t=" + (new Date().getMonth() + "" + new Date().getDate());
        oHead.appendChild(oScript);
        oScript.onload = oScript.onreadystatechange = function() {
            if (oScript.onreadystatechange && oScript.readyState != null) {
                var state = oScript.readyState;
                if (state != 'complete' && state != 'loaded') {
                    return;
                }
            }
            if (jsName.indexOf("facore") == 0) //当核心程序加载完成后才能加载其他程序
            {
                if (jsName == "facore2.0.js") {
                    var loca = document.location;
                    loca = loca.protocol + "//" + loca.host;
                    var flxhrPath = loca + "/flxhr/";
                    LoadJs.load(flxhrPath + 'flensed.js');
                    LoadJs.load(flxhrPath + 'flXHR.js');
                    LoadJs.load(flxhrPath + 'strophe.flxhr.js');
                    LoadJs.load(flxhrPath + 'checkplayer.js');
                    LoadJs.load(flxhrPath + 'swfobject.js');
                } else if (jsName == "facore.js")
                    LoadJs.load('famessageapi.js'); //如果采用get方式，立即加载api库；如果采用2.0的post方式，则在strophe.flxhr.js完成时加载
            } else if (jsName.indexOf("strophe.flxhr.js") > -1)
                LoadJs.load('famessageapi.js'); //如果采用2.0的post方式，则在strophe.flxhr.js完成时加载
        }
    }
}

function HashMap() {
    this.hashTable = new Array();
    this.put = function(k, v) {
        if (this.hashTable == null)
            this.hashTable = new Array();
        var vv = typeof(v) == "string" ? v.replace(/\"/g, "&cemh") : v;
        //if(typeof(k)=="string" && isNaN(k))
        //   eval("this.hashTable."+k.replace(/[`|@|#|%|(|)|\[|\]|\\|:|;|\.]/g,"")+"=''");        
        this.hashTable[k] = vv;
    };
    this.get = function(k) {
        var resutl = this.hashTable[k];
        return typeof(resutl) == "string" ? resutl.replace(/&cemh/g, "\"") : resutl;
    };
    this.containsKey = function(k) {
        return this.hashTable[k];
    }
    this.keyString = function() {
        var str = "";
        for (var i in this.hashTable) {
            if (i == "indexOf" || i == "toJSON" || this.hashTable[i] == null || this.hashTable[i] == undefined) continue;
            str += "," + i;
        }
        return str.substring(1);
    }
    this.valueString = function() {
        var str = "";
        for (var i in this.hashTable) {
            if (i == "indexOf" || i == "toJSON" || this.hashTable[i] == null || this.hashTable[i] == undefined) continue;
            str += "," + this.hashTable[i];
        }
        return str.substring(1);
    }
    this.toString = function() {
        var str = "";
        for (var i in this.hashTable) {
            if (i == "indexOf" || i == "toJSON" || this.hashTable[i] == null || this.hashTable[i] == undefined) continue;
            str += ";{";
            for (var j in this.hashTable[i]) {
                str += j + ":\"" + this.hashTable[i][j] + "\",";
            }
            str += "object:self";
            str += "}";
        }
        return str;
    }
    this.count = function() {
        var cnt = 0;
        for (var i in this.hashTable) {
            if (i != "indexOf" && i != "toJSON" && this.hashTable[i] != undefined)
                cnt++;
        }
        return cnt;
    }
    this.keySet = function() {
        return this.hashTable;
    }
}

//获取auth2认证所需要的参数，主要包括appid,token,openid
function GetAuth2Info(appid,para) {
    if(appid==null ||appid=="")
        return para;
    //从cookice中获取token及openid
    var arrStr = document.cookie.split("; ");
    for (var i = 0; i < arrStr.length; i++) {
        var temp = arrStr[i].split("=");
        if (temp[0] == ("fa" + appid)) {
            if (temp.length < 3) break;
            var tmpPara = {
                "access_token": temp[2],
                "openid": temp[1],
                "appid": appid
            };
            if(para!=null)
            {
                para.appid=tmpPara.appid;
                para.access_token=tmpPara.access_token;
                para.openid=tmpPara.openid;
                return para;
            }
            return tmpPara;
        }
    }
    if(para!=null && para.code!=null)
    {
    	//获取代理token
    	$.post('/api/http/proxytoken', {appid: appid,code:para.code,grant_type:"proxy",state:"_getpro_23sd4ew3r3"}, function(data) {
    			if(para.func!=null)
    				para.func({appid:para.appid,openid:para.openid,access_token:data.access_token});
    	});
    }
    return null;
}
var browserName = navigator.userAgent.toLowerCase();
var browser = {
    version: (browserName.match(/.+(?:rv|it|ra|ie)[\/: ]([\d.]+)/) || [0, '0'])[1],
    safari: /webkit/i.test(browserName) && !this.chrome,
    opera: /opera/i.test(browserName),
    firefox: /firefox/i.test(browserName),
    ie: /msie/i.test(browserName) && !/opera/.test(browserName),
    mozilla: /mozilla/i.test(browserName) && !/(compatible|webkit)/.test(browserName) && !this.chrome,
    chrome: /chrome/i.test(browserName) && /webkit/i.test(browserName) && /mozilla/i.test(browserName)
};
//加载核心库,原始库是get方式实现的，如果需要引用post方式实现的，需要指定版本号为2.0
LoadJs.load("2.0" != apiVersion ? "facore.js" : "facore" + apiVersion + ".js");