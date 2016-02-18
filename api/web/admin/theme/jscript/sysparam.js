var SysParam={};
SysParam.init=function(){
	$("#btn_sys_save,#btn_sys_save_2").hide();
	$("#btn_push_save,#btn_push_save_2").hide();
	$("#btn_sys_save,#btn_sys_save_2").on('click',function(){
		var data={},tr=$("#paramlist tr"),cnt=0;
		for (var i = 1; i < tr.length; i++) {
			var $tr=$(tr[i]), oldvalue = $.trim($tr.attr('value')),newvalue = $.trim($tr.find('input').val());
			if(oldvalue==newvalue) continue;
			cnt++;
			data[$tr.attr('id')] = newvalue;
		};
		if(cnt==0)
		{
			$("#page_alert_info").show().find('label').html('未更改任何数据，无需保存！');
			$("html,body").animate({scrollTop:$("#page_alert_info").offset().top-150},200);
			return;
		}
		if(data['db_imserver']!=null)
		{
			var v = data['db_imserver'].split(':')[0];
			data['_ejabberd-server-http'] = 'http://'+v+':5280';
			data['_FAFA_REG_JID_URL'] = 'http://'+v+':9527';
		}
		bootbox.confirm("请再次确认对接口服务参数的修改?", function(result) {
	        if(result){	        	
	        	$("#page_alert_info").show().find('label').html('正在保存数据...');
	        	$("html,body").animate({scrollTop:$("#page_alert_info").offset().top-150},200);
	        	$("#btn_sys_save,#btn_sys_save_2").attr('disabled',true);
	           	var url= Index.server+'&module=sysparam&action=saveSysparam&jsoncallback=?';
	           	
				$.getJSON(url,{'params':{'list':data}}, function(json) {
					$("#btn_sys_save,#btn_sys_save_2").attr('disabled',false);
					if(json.returncode=="0000")
					{
						$("#page_alert_info").show().find('label').html('接口服务参数保存成功，建议重新发布服务接口！&nbsp;&nbsp;<div class="btn-group" onclick="SysParam.restartWeb()"><button class="btn green">立即发布</button></div>');
					}
					else
					{
						$("#page_alert_info").show().find('label').html('接口服务参数保存失败：'+json.msg);
					}
				});
	        }
	    });		
	});
	$("#btn_push_save,#btn_push_save_2").on('click',function(){
		var data={},tr=$("#im_paramlist tr"),cnt=0;
		for (var i = 1; i < tr.length; i++) {
			var $tr=$(tr[i]), oldvalue = $.trim($tr.attr('value')),newvalue = $.trim($tr.find('input').val());
			if(oldvalue==newvalue) continue;
			cnt++;
			data[$tr.attr('id')] = newvalue;
		};
		tr=$("#im_modulelist tr");
		var modules = [],num=0;
		for (var i = 1; i < tr.length; i++) {
			var $tr=$(tr[i]), oldvalue = $.trim($tr.attr('value')),newvalue = $.trim($tr.find('input').val());
			modules.push("{"+$tr.attr('id')+","+newvalue+"}");
			if(oldvalue==newvalue) continue;
			num++;
		};
		if(cnt==0 && num==0)
		{
			$("#page_alert_info").show().find('label').html('未更改任何数据，无需保存！');
			$("html,body").animate({scrollTop:$("#page_alert_info").offset().top-150},200);
			return;
		}
		if(num>0)
			data['_modules'] = "["+modules.join(",")+"]";
		bootbox.confirm("请再次确认对PUSH服务参数的修改?", function(result) {
	        if(result){	        	
	        	$("#page_alert_info").show().find('label').html('正在保存数据...');
	        	$("html,body").animate({scrollTop:$("#page_alert_info").offset().top-150},200);
	        	$("#btn_push_save,#btn_push_save_2").attr('disabled',true);
	           	var url= Index.server+'&module=sysparam&action=saveEjabberdparam&jsoncallback=?';
	           	
				$.post(url,{'params':{'list':data}}, function(json) {
					$("#btn_push_save,#btn_push_save_2").attr('disabled',false);
					if(json.returncode=="0000")
					{
						$("#page_alert_info").show().find('label').html('PUSH服务参数保存成功，建议重启服务器！&nbsp;&nbsp;<div class="btn-group" onclick="SysParam.restartIm()"><button class="btn green">立即重启</button></div>');
					}
					else
					{
						$("#page_alert_info").show().find('label').html('PUSH服务参数保存失败：'+json.msg);
					}
				},"json");
	        }
	    });		
	});
	Index.search.config(null);
	this.search();
}

SysParam.restartIm=function()
{
	$("#page_alert_info").show().find('label').html('正在重新启动Push服务...');
	var dataurl = Index.server+'&module=ServerMonitor&action=imServerCtl&jsoncallback=?';
    
    var params = {"params":{"command":"start"}};
    $.getJSON(dataurl,params,function(json){
    	if(json.returncode=='0000')
    	{
    		$("#page_alert_info").show().find('label').html(json.data);
    	}
    	else
    	{
    		$("#page_alert_info").show().find('label').html(json.msg);
    	}
    });
}

SysParam.restartWeb=function()
{
	$("#page_alert_info").show().find('label').html('正在发布接口服务...');
	var dataurl = Index.server+'&module=ServerMonitor&action=webServerCtl&jsoncallback=?';
    
    var params = {"params":{"command":"start"}};
    $.getJSON(dataurl,params,function(json){
    	if(json.returncode=='0000')
    	{
    		$("#page_alert_info").show().find('label').html('接口服务已发布成功！');
    	}
    	else
    	{
    		$("#page_alert_info").show().find('label').html(json.msg);
    	}
    });	
}

SysParam.search=function(){
	var dataurl = Index.server+'&module=sysparam&action=getSysparam&jsoncallback=?';
    
    var params = {};
    	//获取内容，刷新页面
    $.getJSON(dataurl, params, function(json) {
    	if(json['returncode']=='0000')
    	{
    		$("#btn_sys_save,#btn_sys_save_2").show();
	      	var html = template('paramlist-tmpl', json);
			$('#paramlist').html(html);
		}
		else
		{
			$("#im_paramlist div").html(json.msg);
		}
    });	

	var dataurl = Index.server+'&module=sysparam&action=getEjabberdParam&jsoncallback=?';
    
    var params = {};
    	//获取内容，刷新页面
    $.getJSON(dataurl, params, function(json) {
    	if(json['returncode']=='0000')
    	{
    		$("#btn_push_save,#btn_push_save_2").show();
    		var modules = null,listen=null;
    		//获取监听和模块配置
    		for (var i = 0; i < json['data'].length; i++) {
    			var pn = json['data'][i]['param_name'];
    			if(pn=='modules')
    			{
    				modules = json['data'][i]['param_value'];
    				json['data'][i]=null;
    			}
    			else if(pn=='listen')
    			{
    				listen = json['data'][i]['param_value'];
    				json['data'][i]=null;
    			}
    		};
	      	var html = template('im_paramlist-tmpl', json);
			$('#im_paramlist').html(html);
			var modules_data = {"data":[]};
			for (var i = 0; i < modules.length; i++) {
				var tmp=$.trim(modules[i]);
				if(tmp.substr(0,1)=="%") continue;
				tmp=tmp.substr(1)+"]";
				var splPos = tmp.indexOf(",");
				var module_name = $.trim(tmp.substr(0,splPos));
				if(module_name=="mod_register")
				{
					tmp += "},"+$.trim(modules[++i])+"]";
				}
				modules_data.data.push({"module_name":module_name,"start_value":$.trim(tmp.substr(splPos+1))});
			};
			var html = template('im_modulelist-tmpl', modules_data);
			$('#im_modulelist').html(html);
		}
		else
		{
			$("#im_paramlist div").html(json.msg);
		}
    });    
}

SysParam.addModule=function()
{
	var $dlg=$('#newmodule-form');
	if($dlg.length>0) return;
	$("#newmodule-dlg").clone(true).removeClass('hide').attr("id","newmodule-form").insertBefore($("#im_modulelist"));	
}

SysParam.saveModule=function(){
	var $dlg=$('#newmodule-form');
	var modulename = $.trim($dlg.find("#modulename").val());
	var startvalue = $.trim($dlg.find("#start_value").val());
	if(modulename=="")
	{
		$dlg.find('.alert-danger').show().find("label").html("模块名称不能为空");
	}
	if(startvalue=="") startvalue="[]";
	var html = '<tr value="" id="'+modulename+'"><td width="20%" style="line-height: 32px;">'+modulename+'</td><td><div><input type="text" class="form-control form-control-solid placeholder-no-fix" value="'+startvalue+'" placeholder="请输入启动参数配置" maxlength="350" style="background-color: #fff"></div></td></tr>';
	$(html).insertBefore($("#im_modulelist tbody tr:first"));
	$dlg.remove();
}

SysParam.pushcodeMgr=function()
{
	var $dlg=$('#pushcode-form');
	if($dlg.length>0) return;
	$("#pushcode-dlg").clone(true).removeClass('hide').attr("id","pushcode-form").insertBefore($("#im_modulelist"));	
	var api_v = $.trim($("#service_api input").val());
	var re = /b_mods,\[.*?\]\]\]/ig;
	var b_mods = api_v.match(re);
	var data ={"data":[]};
	if(b_mods.length>0)
	{
		b_mods = b_mods[0];
		b_mods = b_mods.match(/\[.*?\]/ig);
		for (var i = 0; i < b_mods.length; i++) {
			var tmp = b_mods[i];
			tmp = tmp.replace(/[\[\]]/gi,"")
			var pos = tmp.indexOf(",");
			data.data.push({"module_name":tmp.substr(0,pos),"codes":tmp.substr(pos+1)});
		};
	}
	var html = template('im_codelist-tmpl', data);
	$('#pushcode-form .form-body').append(html);
}

SysParam.savepushcode=function()
{
	var $dlg=$('#pushcode-form');
	var $list = $('#pushcode-form .form-body .form-group');
	var mod_codes=[];
	for (var i = 0; i < $list.length; i++) {
		var $input = $($list[i]).find("input");
		var module_name=$.trim($input[0].value),codes=$.trim($input[1].value);
		if(module_name=="" && codes=="")
		{
			continue;
		}
		if(module_name=="" || codes=="")
		{
			$dlg.find('.alert-danger').show().find("label").html("模块名称或业务代码不能为空");
			return;
		}
		mod_codes.push("["+module_name+",["+codes+"]]");
	};
	mod_codes = "b_mods,["+ mod_codes.join(",")+"]";
	var v = $("#service_api input").val();
	v = v.replace(/b_mods,\[.*?\]\]\]/gi,mod_codes);
	$("#service_api input").val(v);
	$dlg.remove();
}