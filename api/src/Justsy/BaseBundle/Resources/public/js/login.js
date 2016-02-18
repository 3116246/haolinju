//温馨提示请勿随意修改原有内容,会影像系统的正常使用

//登录验证
var formCheck = function () {
    var username = $("#_username");
    var userpwd = $("#_password");
    //var autologin = $("#_autologin");
    var divmsg = $("#_message");
    var msgerror = divmsg.find(".error");
    if (IsNullOrEmpty(username.val())) {
        msgerror.text("Wefafa帐号不能为空");
        divmsg.show();
        username.focus();
        return false;
    }
    if (IsNullOrEmpty(userpwd.val())) {
        msgerror.text("登录密码不能为空");
        divmsg.show();
        userpwd.focus();
        return false;
    }
    if ($("#btnSubmit").text() == "登 录") {
        var autologin = $("#_autologin").attr("checked");
        var autokey = "wefafa_verify_automatic_login";
        if (autologin == "checked") {
            setCookie(autokey, autologin);
        } else {
            delCookie(autokey);
        }
        var keys = new Array();
        var vals = new Array();
        keys.push("wefafa_login_account");
        vals.push(username.val());
        var isset = setCookies(keys, vals);
        if (isset) {
            $("#btnSubmit").text("登 录 中");
            document.getElementById("form1").action="{{ path('JustsyBaseBundle_login_check')}}";//
            document.getElementById("form1").submit();
            return true;
        } else {
            $("#btnSubmit").text("登 录");
            msgerror.text("登录微信平台超时");
            divmsg.show();
            return false;
        }
    }
    return false;
};
$(function () {
    $("#btnSubmit").text("登 录");
    var divmsg = $("#_message");
    var msgerror = divmsg.find(".error");
    if (!IsNullOrEmpty(msgerror.text())) {
        divmsg.show();
    }
    var username = $("#_username");
    if (IsNullOrEmpty(username.val())) {
        username.focus();
    } else {
        $("#_password").focus();
    }
});
