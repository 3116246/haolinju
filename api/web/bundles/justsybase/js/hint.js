
/*****************
*全局通用模块
*温馨提示请勿随意修改原有内容,会影像系统的正常使用
******************/

//清空字符串前后空格
var trim = function (val) {
    return val.replace(/(^[\s\xA0]+|[\s\xA0]+$)/g, '');
};
//图片类型
var imageType = {
    jpg: "jpg",
    jpeg: "jpeg",
    gif: 'gif',
    png: "png"
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
                title = '提示消息';
                break;
            case "warning":
                title = '温馨提示';
                break;
            case "error":
                title = '错误消息';
                break;
            case "question":
                title = '确认消息';
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


//检测是否是邮箱格式
var check_mail = function (val) {
    var re = /\w@\w*\.\w/;
    if (re.test(val)) return true;
    else return false;
};
//检测是否是手机号码
var check_phone = function (val) {
    var re = /^(13[0-9]{9})|(15[0-9]{9})|(18[0-9]{9})$/;
    if (re.test(val)) return true;
    else return false;
};
//检测是否是邮政编码
var check_code = function (val) {
    var re = /^[1-9][0-9]{5}$/;
    if (re.test(val)) return true;
    else return false;
};

var createconverdiv = function (classname) {
    var html = [];
    html.push("<div class='currConverDiv' onclick=\"focusCurr('" + classname + "')\"></div>");
    $(document.body).append(html.join(''));
}
var ajaxLoading = function (msg) {
    if (typeof (msg) == 'undefined' || msg == '' || msg == null) msg = "正在处理,请稍候...";
    var html = [];
    var width = '300px';
    if (arguments[1] != null) width = arguments[1] + 'px';
    if ($(".currAjax").length == 0) {
        html.push("<div class='modal currAjax' style='top:20%;left:60%;z-index:200000;width:" + width + ";'>");
        html.push("<div class='modal-body' style='overflow:hidden;'><div style='float:left;line-height:48px;'><img src='/bundles/fafatimewebase/images/loading.gif' style='width:48px;height:48px;'/><span>" + msg + "</span></div></div>");
        html.push("</div>");
        $(document.body).append(html.join(''));
        createconverdiv("currAjax");
    }
    else {
        $(".currAjax .modal-body p").html(msg);
    }
}

var ajaxLoadEnd = function () {
    $(".currAjax").remove();
    $(".currConverDiv").remove();
}

var showConfirmBox = function () {
    var msg = arguments[0] ? arguments[0] : '确定操作选中数据吗?';
    var html = [];
    var width = '300px';
    if (arguments[2] != null) width = arguments[2] + 'px';
    html.push('<div class="modal currConfirm" style="top:60%;left:60%;z-index:20000;width: ' + width + ';">');
    html.push('<div class="modal-header"><span>确认消息</span></div>');
    html.push('<div class="modal-body" style="overflow: hidden; text-align: center;"><div style="position: relative;float: left; "><img style="height:25px;width:25px;position:relative;padding-right:5px;" src="/bundles/fafatimewebase/images/ask64.png"></div><div style="position: relative;float: left; "><p>' + msg + '</p></div></div>');
    html.push('<div class="modal-footer">');
    html.push('<button class="btn" type="button" onclick="' + arguments[1] + '">确定</button>');
    html.push('<button class="btn" data-dismiss="modal" aria-hidden="true" onclick=\'$(this).parent().parent().remove();$(".currConverDiv").remove();\'>关闭</button></div>');
    html.push('</div>');
    $(document.body).append(html.join(''));
    createconverdiv("currConfirm");
}

var wefafaWin2 = {
    confirm_sender: null,
    confirm_ok_fun: null,
    confirm_para: null,
    weconfirm: function (sender, title, txt, ok_fun, para) {
        var $wefafa_confirm = $("#wefafa_confirm");
        if ($wefafa_confirm.length > 0) return;
        var modal = '';
        moda = '<div class="wefafa_confirm modal"  id="wefafa_confirm" data-backdrop=false style="z-index:20000;display:none;margin-left: -150px;width: 300px;" show=false>' +
				    '  <div class="modal-header" style="border-bottom:1px solid #ddd;">' +
				    '  	<span>&nbsp;&nbsp;{title}</span>' +
				    '  </div>' +
					  '  <div class="modal-body">' +
					  '    <div class="doc_rd_deleteconfirm_left"></div>' +
					  '    <div>' +
					  '        <div class="doc_rd_deleteconfirm_text" style="line-height:22px;">{txt}</div>' +
					  '    </div>' +
				      '  </div>' +
                      '  <div class="modal-footer" style="padding:10px 15px 5px 0px;">' +
                      '        <div><span id="wefafa_confirm_sureBtn" onclick="wefafaWin2.confirm_ok()" class="btn">确定</span><span id="wefafa_confirm_cancelBtn" onclick="wefafaWin2.confirm_cancel()" class="btn">取消</span></div>' +
					  '        <div class="wefafa_confirm_hint" style="margin-left: 70px;height:10px;width:130px;float:left"></div>' +
			          '  </div>'
        '</div>';
        $(document.body).append(moda.replace("{title}", title).replace("{txt}", txt));
        $(document.body).append("<div id='wefafa_confirm_cover' style='background-color: #111111;height: 100%;left: 0;opacity: 0.5;filter:alpha(opacity=50);-moz-opacity:0.5;-khtml-opacity:0.5; position: fixed; top: 0;width: 100%;z-index: 10000;'>");
        $wefafa_confirm = $("#wefafa_confirm");
        $wefafa_confirm.show();
        this.confirm_ok_fun = ok_fun;
        this.confirm_sender = sender;
        this.confirm_para = para;
    },
    confirm_ok: function () {
        $("#wefafa_confirm_cover").remove();
        this.confirm_ok_fun(this.confirm_para == null ? this.confirm_sender : this.confirm_para);
        $("#wefafa_confirm").remove();
        return true;
    },
    confirm_cancel: function () {
        $("#wefafa_confirm_cover").remove();
        $("#wefafa_confirm").remove();
        return false;
    }
};


var hideConfirmBox = function () {
    $(".currConfirm").remove();
    $(".currConverDiv").remove();
}

var showErrBox = function (msg) {
    var time = arguments[1] ? parseInt(arguments[1]) : -1;
    //if (typeof (msg) == 'undefined' || msg == '' || msg == null) msg = "抱歉,操作失败!";
    var html = [];
    var width = '300px';
    if (arguments[2] != null) width = arguments[2] + 'px';
    html.push('<div class="modal currErr" style="top:65%;left:60%;z-index:200000;width: ' + width + ';">');
    html.push('<div class="modal-header"><p>' + '提醒消息' + '</p></div>');
    html.push('<div class="modal-body" style="overflow: hidden; color: #FF2401; text-align: center;"><div style="position: relative;float: left; "><img style="position:relative;height:16px;width:16px;padding-right:5px;" src="/bundles/fafatimewebase/images/ts.png"></div><div style="position: relative;float: left; "><p>' + msg + '</p></div></div>');
    html.push('<div class="modal-footer"><button class="btn" data-dismiss="modal" aria-hidden="true" onclick=\'$(this).parent().parent().remove();$(".currConverDiv").remove();\'>确定</button></div>');
    html.push('</div>');

    $(document.body).append(html.join(''));
    createconverdiv("currErr");
    if (time > -1) {
        setTimeout(function () {
            $(".currErr").remove();
            $(".currConverDiv").remove();
        }, time);
    }
}
var showSuccessBox = function (msg) {
    var time = arguments[1] ? parseInt(arguments[1]) : -1;
    if (typeof (msg) == 'undefined' || msg == '' || msg == null) msg = "恭喜,操作成功!";
    var html = [];
    var width = '300px';
    if (arguments[2] != null) width = arguments[2] + 'px';
    html.push('<div class="modal currSuccess" style="top:65%;z-index:200000;left:60%;width: ' + width + '; ">');
    html.push('<div class="modal-header"><p>' + '提醒消息' + '</p></div>');
    html.push('<div class="modal-body" style="overflow: hidden; color: rgb(0, 153, 0); text-align:center;min-height:50px;line-height:50px;"><div style="position: relative;"><img style="margin-right:5px;" src="/bundles/fafatimewebase/images/zq.png"><span>' + msg + '</span></div></div>');
    html.push('<div class="modal-footer"><button class="btn" data-dismiss="modal" aria-hidden="true" onclick=\'$(this).parent().parent().remove();$(".currConverDiv").remove();\'>确定</button></div>');
    html.push('</div>');
    $(document.body).append(html.join(''));
    createconverdiv("currSuccess");
    if (time > -1) {
        setTimeout(function () {
            $(".currSuccess").remove();
            $(".currConverDiv").remove();
        }, time);
    }
}

function showloaddata($ele, msg) {
    if(msg==null || msg=="")
        $ele.html("<div class='urlloading'><div /></div>");
    else
        $ele.html("<div class='urlloading'><div /><span style='padding-top: 20px; display: block; color: rgb(102, 102, 102);'>"+msg+"</span></div>");
}