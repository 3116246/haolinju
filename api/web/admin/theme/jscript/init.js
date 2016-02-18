var Init={};
Init.host = window.location.protocol=="file:"?"http://112.126.77.162:8000":window.location.protocol+"//"+window.location.host;

Init.init=function(){
	$("#hint").hide();
	$("#check_config #btn_refresh").on('click',function(){
		Init.getConfig();
	});
	this.getConfig();
}

Init.getConfig=function(){
	var url = Init.host+"/check.php?opt=cfg";
	$("#check_config .portlet-body").load(url).slimScroll({"max-height":"300px"}); 
	$("#param_config .portlet-body").slimScroll({"max-height":"300px"});
	$("#init_data .portlet-body").slimScroll({"max-height":"300px"});
}

Init.in2=function(){
	var key="ERROR";
	var  txt = $("#check_config #sys_config").text();
	if(txt.indexOf(key)>0)
	{
		$("#check_config #hint").html("请确定以上配置项没有<strong style='color:red'>ERROR</strong>项！").show();
		return;
	}
	$("#check_config").parent().parent().hide();
	$("#param_config").parent().parent().show();
	var $ip = window.location.protocol+"//"+window.location.hostname;
	$("#server_host").val(window.location.protocol+"//"+window.location.host);
	$("#im_host").val($ip);
	$("#db_host").val(window.location.hostname);
	$("#mongodb_host").val(window.location.hostname);
}

Init.in3=function(){
	var data={};
	data['server_domain'] = $.trim($("#server_domain").val());
	data['server_host'] = $.trim($("#server_host").val());
	data['im_host'] = $.trim($("#im_host").val());
	data['db_host'] = $.trim($("#db_host").val());
	data['db_port'] = $.trim($("#db_port").val());
	data['sns_db_user'] = $.trim($("#sns_user").val());
	data['sns_db_pwd'] = $.trim($("#sns_pwd").val());
	data['sns_db_dbname'] = $.trim($("#sns_dbname").val());
	data['im_db_user'] = $.trim($("#im_user").val());
	data['im_db_pwd'] = $.trim($("#im_pwd").val());
	data['im_db_dbname'] = $.trim($("#im_dbname").val());
	data['mongodb_host'] = $.trim($("#mongodb_host").val());
	data['mongodb_port'] = $.trim($("#mongodb_port").val());
	data['mongodb_user'] = $.trim($("#mongodb_user").val());
	data['mongodb_pwd'] = $.trim($("#mongodb_pwd").val());
	data['mongodb_name'] = $.trim($("#mongodb_name").val());
	data['mongodb_auth'] = $("input[name='mongodb_auth'][checked]").attr("value");

	data['mailer_transport'] = $.trim($("#mailer_transport").val());
	data['mailer_host'] = $.trim($("#mailer_host").val());
	data['mailer_user'] = $.trim($("#mailer_user").val());
	data['mailer_password'] = $.trim($("#mailer_password").val());
	if(data['server_domain']=='')
	{
		$("#param_config").next("#hint").html('企业域不能为空!');
		$("#server_domain").focus();
		return;
	}	
	if(data['server_host']=='')
	{
		$("#param_config").next("#hint").html('服务器地址不能为空!');
		$("#server_host").focus();
		return;
	}
	if(data['im_host']=='')
	{
		$("#param_config").next("#hint").html('IM服务器IP不能为空!');
		$("#im_host").focus();
		return;
	}
	if(data['sns_db_user']=='' || data['sns_db_pwd']=='' || data['sns_db_dbname']=='')
	{
		$("#param_config").next("#hint").html('接口数据库信息不全!');
		$("#sns_user").focus();
		return;
	}
	if(data['im_db_user']=='' || data['im_db_pwd']=='' || data['im_db_dbname']=='')
	{
		$("#param_config").next("#hint").html('IM数据库信息不全!');
		$("#im_user").focus();
		return;
	}	
	if(data['mongodb_host']=='')
	{
		$("#param_config").next("#hint").html('Mongodb数据库IP地址不能为空!');
		$("#mongodb_host").focus();
		return;
	}
	if(data['mongodb_port']=='')
	{
		$("#param_config").next("#hint").html('Mongodb数据库端口不能为空!');
		$("#mongodb_port").focus();
		return;
	}
	if(data['db_host']=='')
	{
		$("#param_config").next("#hint").html('数据库IP地址不能为空!');
		$("#db_host").focus();
		return;
	}
	if(data['db_port']=='')
	{
		$("#param_config").next("#hint").html('数据库端口不能为空!');
		$("#db_port").focus();
		return;
	}
	$("#param_config").next("#hint").html('正在保存配置...');
	var url = Init.host+"/check.php?opt=para";
	$.getJSON(url, data, function(json) {
		if(json.returncode=="0000")
		{
			$("#param_config").parent().parent().hide();
			$("#init_data").parent().parent().show();
		}
		else
		{
			$("#param_config").next("#hint").html('保存错误：'+json.msg);
		}
	});	
}

Init.ok=function(){
	var data={};
	var url = Init.host+"/check.php?opt=init";
	data['en_name'] =encodeURIComponent($.trim($("#en_name").val()));
	data['en_domain'] =$.trim($("#en_domain").val());
	data['admin_account'] =$.trim($("#admin_account").val());
	data['admin_pwd'] =$.trim($("#admin_pwd").val());
	data['admin_name'] =encodeURIComponent($.trim($("#admin_name").val()));
	if(data['en_name']=='')
	{
		$("#init_data").next("#hint").html('企业名称不能为空!');
		$("#en_name").focus();
		return;
	}
	if(data['en_domain']=='')
	{
		$("#init_data").next("#hint").html('企业邮箱域不能为空!');
		$("#en_domain").focus();
		return;
	}
	if(checkEmail(data['en_domain']))
	{
		$("#init_data").next("#hint").html('企业邮箱域格式不正确，请使用@后面的域名!');
		$("#admin_account").focus();
		return;
	}	
	if(data['admin_account']=='')
	{
		$("#init_data").next("#hint").html('管理帐号不能为空!');
		$("#admin_account").focus();
		return;
	}
	if(!checkEmail(data['admin_account']))
	{
		$("#init_data").next("#hint").html('管理帐号格式不正确，请使用email格式!');
		$("#admin_account").focus();
		return;
	}
	if(data['admin_pwd']=='')
	{
		$("#init_data").next("#hint").html('管理密码不能为空!');
		$("#admin_pwd").focus();
		return;
	}
	if(data['admin_name']=='')
	{
		$("#init_data").next("#hint").html('管理员姓名不能为空!');
		$("#admin_name").focus();
		return;
	}	
	$("#init_data").next("#hint").html('正在初始化数据...');	
	$.getJSON(url,data,function(json){
		if(json.returncode=="0000")
		{
			$("#init_data").parent().parent().hide();
			$("#init_success").parent().parent().show();
		}
		else
		{
			$("#init_data").next("#hint").html('初始化数据错误：'+json.msg);
		}
	});
}

Init.go=function(){
	window.location.href='index.html';
}