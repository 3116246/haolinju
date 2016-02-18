
/*****************
*全局通用模块
*温馨提示请勿随意修改原有内容,会影像系统的正常使用
******************/

var globalPath = "";
//获取当前访问地址 htttp://www.baidu.com?search=s  返回www.baidu.com
var getHost = function (url) {
    var host = "null";
    if (typeof url == "undefined" || null == url)
        url = window.location.href;
    var regex = /.*\:\/\/([^\/]*).*/;
    var match = url.match(regex);
    if (typeof match != "undefined" && null != match)
        host = match[1];
    return host;
};
//true 为空 false 不为空
var IsNullOrEmpty = function (val) {
    if (val == null || $.trim(val) == "" || $.trim(val) == "undefined" || val == []) {
        return true;
    }
    return false;
};
//清空字符串前后空格
var trim = function (val) {
    return val.replace(/(^[\s\xA0]+|[\s\xA0]+$)/g, '');
};
//提示信息 type error,question,info,warning
var Msg = {
    //在屏幕的右下方显示一个消息窗口 ，options参数是一个可配置的对象:
    //showType：定义如何显示消息窗口，可选值: null,slide,fade,show，默认为slide。
    //showSpeed：定义窗口显示的时间，单位毫秒，默认为 600。
    //width：定义消息窗口的宽度，默认为250。
    //height：定义消息窗口的高度，默认为100。
    //msg：显示在消息窗口的文本。
    //title：显示在窗口头部的标题。
    //timeout：如果定义为0，消息窗口将不会关闭直到用户点击关闭为止。如果定义为非0，消息窗口在超时后将自动关闭。
    show: function (showType, showSpeed, width, height, msgtitle, timeout) {
        $.messager.show(showType, showSpeed, width, height, msgtitle, timeout);
    },
    //显示警告窗口。参数如下:
    //title：显示在窗口头部的标题文本。
    //msg：显示在窗口中的文本。
    //icon：显示的图片，可选值：error,question,info,warning。
    //fn：当窗口关闭时触发的回调函数。
    alert: function (msg, icon) {
        var title = "";
        switch (icon) {
            case "info":
                title = '提示信息';
                break;
            case "warning":
                title = '警告信息';
                break;
            case "error":
                title = '错误信息';
                break;
            case "question":
                title = '确认信息';
                break;
        }
        $.messager.alert(title, msg, icon);
    },
    //显示一个带有确认和取消按钮的确认信息窗口。参数如下：
    //title：显示在窗口头部的文本。
    //msg：显示在窗口中的文本。
    //fn(b)：回调函数，当用户点击确认按钮时，传递一个true值给回调函数，否则传递一个false值。
    confirm: function (title, msg, b, fun) {
        $.messager.confirm(title, msg, function () {
            if (b) {
                //确定按钮执行的函数
                var execfun = fun;
                eval("execfun()");
                setTimeout("execfun()", 0);
            }
        });
    },
    //显示一个带有确认和取消的输入信息窗口。 参数如下：
    //title：显示在窗口头部的标题文本。
    //msg：显示在窗口中的信息
    //fn(val)：接受用户输入作为参数的回调函数。
    prompt: function (title, msg, val) {
        $.messager.prompt(title, msg, function () {
            return val;
        });
    },
    //显示一个带进度条信息的窗口 属性定义如下:
    //title：显示在面板头部的标题文本，默认为''。
    //msg：显示在主窗体的文本，默认为''。
    //text：显示在进度条中的信息默认为undefined。interval：每次进度增加所耗费的时间，单位为毫秒，默认为300.
    progress: function (title, msg, text, interval) {
        $.messager.progress(title, msg, text, interval);
    }
};
//控件注册事件
var delegateEvent = function (id, eve) {
    for (var j = 0; j < id.length; j++) {
        if (!IsNullOrEmpty(eve.focus))
            $("#" + id[j]).live("focus", function () {
                eve.focus(this);
            });
        if (!IsNullOrEmpty(eve.blur))
            $("#" + id[j]).live("blur", function () {
                eve.blur(this);
            });
        if (!IsNullOrEmpty(eve.keyup))
            $("#" + id[j]).live("keyup", function () {
                eve.keyup(this);
            });
        if (!IsNullOrEmpty(eve.click))
            $("#" + id[j]).live("click", function () {
                eve.click(this);
            });
    }
};

var __typeof__ = function (objClass) {
    if (objClass && objClass.constructor) {
        var strFun = objClass.constructor.toString();
        var className = strFun.substr(0, strFun.indexOf('('));
        className = className.replace('function', '');
        return className.replace(/(^\s*)|(\s*$)/ig, '');
    }
    return typeof (objClass);
};

var obj_serialize = function (obj) {
    var returnVal;
    if (obj != undefined) {
        switch (obj.constructor) {
            case Array:
                var vArr = "[";
                for (var i = 0; i < obj.length; i++) {
                    if (i > 0) vArr += ",";
                    vArr += "'" + obj_serialize(obj[i]) + "'";
                }
                vArr += "]";
                return vArr;
            case String:
                returnVal = escape("'" + obj + "'");
                return returnVal;
            case Number:
                returnVal = isFinite(obj) ? obj.toString() : null;
                return returnVal;
            case Date:
                returnVal = "#" + obj + "#";
                return returnVal;
            default:
                if (typeof obj == "object") {
                    var vobj = [];
                    for (attr in obj) {
                        if (typeof obj[attr] != "function") {
                            var val = obj_serialize(obj[attr]);
                            if (typeof val == "string" && !IsNullOrEmpty(val)) {
                                val = val.replace(new RegExp("'", "g"), "");
                                val = val.replace(new RegExp('"', "g"), "");
                                val = val.replace(new RegExp("%27", "g"), "");
                            }
                            vobj.push(attr + ":'" + val + "'");
                        }
                    }
                    vobj = "{" + vobj.join(",") + "}";
                    return vobj.length > 0 ? vobj : "{}";
                } else {
                    return obj.toString();
                }
        }
    }
    return null;
};

/**************
*Cookie公用模块
***************/

//此 cookie 将被保存 30 天
var days = 30;

//两个参数，一个是cookie的名子，一个是值
var setCookie = function (name, value) {
    var exp = new Date();
    exp.setTime(exp.getTime() + days * 24 * 60 * 60 * 1000);
    document.cookie = name + "=" + value + "; expires=" + exp.toGMTString();
    return true;
};
//两个参数，一个是cookies的Keys，一个是Vals
var setCookies = function (keys, vals) {
    var cookie = "";
    for (var i = 0; i < keys.length; i++) {
        if (cookie != "")
            cookie += ";";
        cookie += keys[i] + "=" + vals[i];
    }
    var exp = new Date();
    exp.setTime(exp.getTime() + days * 24 * 60 * 60 * 1000);
    document.cookie = cookie + "; expires=" + exp.toGMTString();
    return true;
};
//取cookies函数
var getCookie = function (name) {
    var arr = document.cookie.match(new RegExp("(^| )" + name + "=([^;]*)(;|$)"));
    if (arr != null)
        return unescape(arr[2]);
    return null;
};
//删除cookie
var delCookie = function (name) {
    var exp = new Date();
    exp.setTime(exp.getTime() - 1);
    var cval = getCookie(name);
    if (cval != null)
        document.cookie = name + "=" + cval + "; expires=" + exp.toGMTString();
};
var getMonthDay = function () {
    var today = new Date();
    var month = today.getMonth() + 1;
    var day = today.getDate();
    var hours = today.getHours();
    var minutes = today.getMinutes();
    var timeValue1 = ((hours < 10) ? "0" : "") + hours + "";
    timeValue1 += ((minutes < 10) ? ":0" : ":") + minutes + "";
    var year = "";
    var timeValue2 = year;
    timeValue2 += ((month < 10) ? "0" : "") + month + "";
    timeValue2 += ((day < 10) ? "-0" : "-") + day + "";
    return timeValue2 + " " + timeValue1;
};
