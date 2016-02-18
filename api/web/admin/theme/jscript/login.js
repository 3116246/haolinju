
var USER_LOGIN="/interface/logincheck?jsoncallback=?";
var LOGIN_HOST = window.location.protocol=="file:"?"http://112.126.77.162:8000":window.location.protocol+"//"+window.location.host;
var Appid = "35dc24ba28ee06ce8741259c19fbc8e8";
var Login = {};
Login.reset = false;
Login.login = function(){
	
	var $form = $(".login-form");
	var data = Login.getFromVal($form);
	var validate = Login.validate(data);
	if(!validate) return;
	
	var params = {
		login_account:data.username,
		password:data.password,
		comefrom:'00', //集成登录
		datascope:'datascope',
		appid:Appid,
		clientdatetime:new Date().getTime()
	}
	
	var url = LOGIN_HOST+USER_LOGIN;
	$("#alert").show().children('span').html("正在登录系统...");
	$("#btn_login").attr("disabled",true);
	$.getJSON(url,params,function(json){
		$("#btn_login").attr("disabled",false);
		if(json.returncode=="0000")
		{
			$.getJSON(LOGIN_HOST+"/api/http/exec_dataaccess?module=enterprise&action=IsManager&openid="+json.openid+"&jsoncallback=?",{},function(data){
				if(data.returncode!="0000" || !data.data)
				{
					$("#alert").show().children('span').html("登录失败：权限不足，请联系管理员！");
					return;
				}
				$.cookie("openid",json.openid);
				$.cookie("__auth2_code",json.auth2_code);
				$.cookie("nickname",json.info.nick_name);
				$.cookie("photo",json.info.photo_pth);
				$.cookie("login_account",params.login_account);
				if(!Login.reset)
					window.location.href="index.html";
				else
				{
					Index.openid=$.cookie("openid");
					Index.server = Index.path+Index.openid;
					$("#static").modal('hide');
					mointor();
				}
			});	
		}
		else if(json.returncode=="0002")
			$("#alert").show().children('span').html("登录失败：帐号或密码不正确！");
		else
			$("#alert").show().children('span').html("登录失败：系统错误！");
	});
}
Login.logout=function()
{
	$.cookie("openid",null);
	$.cookie("nickname",null);
	$.cookie("photo",null);
	FaFaPresence.Disconnect();
	window.location.href="login.html";
}
Login.getFromVal = function($form){
	var form = $form.serializeArray();
	var data = {};
	$.each(form,function(){
	  
	 	data[this.name]=$.trim(this.value)
	})
	return data;
}

Login.validate = function(data){
	
	if(data.username&&data.password){
		$("#alert").hide();
		return true;
	}else{
		$("#alert").show().children('span').html("账号或者密码不能为空");
		return false;
	}
	
}