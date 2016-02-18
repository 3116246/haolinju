var AppCenter = {
    Loading: function (txt) {
        var s = $("#appAuth .modal-footer span");
        var i = $("#appAuth .modal-footer img");
        s.text(txt);
        i.attr("src", "/bundles/fafatimewebase/images/loadingsmall.gif");
        s.show();
        i.show();
    },
    OK: function (txt) {
        var s = $("#appAuth .modal-footer span");
        var i = $("#appAuth .modal-footer img");
        s.text(txt);
        i.attr("src", "/bundles/fafatimewebase/images/ok.png");
        s.show();
        i.show();
    },
    Error: function (txt) {
        var s = $("#appAuth .modal-footer span");
        var i = $("#appAuth .modal-footer img");
        s.text(txt);
        i.attr("src", "/bundles/fafatimewebase/images/error.png");
        s.show();
        i.show();
        setTimeout("AppCenter.Reset()", 5000);
    },
    Reset: function () {
        var s = $("#appAuth .modal-footer span");
        var i = $("#appAuth .modal-footer img");
        s.text("");
        i.attr("src", "/bundles/fafatimewebase/images/error.png");
        s.hide();
        i.hide();
    },
    Auth: function () {
        var appAuth = $("#appAuth");
        this.Loading("认证中...");
        $("#sendBtn").attr("disabled", true);
        $("form#authFrm").ajaxSubmit({
            url: $("#authFrm").attr("action"), //表单的action
            type: 'post',
            dataType: 'json',
            success: function (sText) {
                $("#sendBtn").attr("disabled", false);
                if (sText.s) {
                    var URL = appAuth.attr("url");
                    if (URL == "") {
                        AppCenter.Error("未找到回调地址");
                        return;
                    }
                    window.location = URL + (URL.indexOf("?") == -1 ? "?" : "&") + "openid=" + sText.openid;
                }
                else {
                    AppCenter.Error(sText.msg);
                }
            },
            error: function (e) {
                AppCenter.Error(e.message);
            }
        });
    },
    Bind: function () {
        var u = $("#account").val().replace(/ /g, "");
        var p = $("#password").val().replace(/ /g, "");
        if (u == "" || u == "") {
            this.Error("帐号和密码不能为空！");
            return;
        }
        $("#bindFrm")[0].submit();
    },
    Cj: function (src) {
        $.getJSON("/user/appcenterauth/info?openid=" + $("#openid").val(), "", function (d) {
            if (d != null && d.login_account != "") {
                $("#bindFrm #c_f").remove();
                $("#bindFrm #eno").remove();
                $("#bindFrm #dept_id").remove();
                $("#bindFrm #login_account").remove();
                $("#bindFrm #nick_name").remove();
                $("#bindFrm #active_date").remove();
                $("#bindFrm #dept_list").remove();
                $("#bindFrm").append("<input type='hidden' id='c_f' name='c_f' value='1'>");
                $("#bindFrm").append("<input type='hidden' id='eno' name='eno' value='" + d.eno + "'>");
                $("#bindFrm").append("<input type='hidden' id='dept_id' name='dept_id' value='" + d.dept_id + "'>");
                $("#bindFrm").append("<input type='hidden' id='login_account' name='login_account' value='" + d.login_account + "'>");
                $("#bindFrm").append("<input type='hidden' id='nick_name' name='nick_name' value='" + d.nick_name + "'>");
                $("#bindFrm").append("<input type='hidden' id='active_date' name='active_date' value='" + d.active_date + "'>");
                $("#bindFrm").append("<input type='hidden' id='dept_list' name='dept_list' value='" + d.dept_list + "'>");
                $("#bindFrm")[0].action = $(src).attr("url");
                $("#bindFrm")[0].submit();
            }
        });
    }
};

$(document).ready(function(){
    	var err=$(".modal-footer span").attr("error");
    	if(err=="") return;
    	err = eval("("+err+")");
    	if(err.msg=="") return;
    	$(".modal-footer img").attr("src", $(".modal-footer img").attr("src").replace("ok.png","error.png"));
    	$(".modal-footer img").show();
    	$(".modal-footer span").text(err.msg);
    	$(".modal-footer span").show();
});