var Version = {};
Version.init=function(opts){
	Index.search.config(null);
	this.search();
}

Version.search=function()
{
	var url = Index.server+'&module=ApiVersion&action=list&pageindex=1&record=100&jsoncallback=?';
    $.getJSON(url, {}, function(json) {
        var html = template('versionlist-tmpl', json);
		$('#versionlist').html(html);
    });
}

Version.new=function()
{
	$dlg=$('#newversion-form');
	if($dlg.length>0) return;
	$("#newversion-dlg").clone(true).removeClass('hide').attr("id","newversion-form").insertBefore($("#versionlist"));	
	$("#newversion-form #filedata").fileupload({
		maxChunkSize: 1024*1024*25,
		dataType:'json',
		acceptFileTypes:  /(\.|\/)(ipa|apk|exe)$/i,
	    url:Index.server+"&module=ApiVersion&action=push&jsoncallback=?",//文件上传地址，当然也可以直接写在input的data-url属性内
	    done:function(e,result){
	        //done方法就是上传完毕的回调函数，其他回调函数可以自行查看api
	        //注意result要和jquery的ajax的data参数区分，这个对象包含了整个请求信息
	        //返回的数据在result.result中，假设我们服务器返回了一个json对象
	        Version.search();
	        if(result)
	        {
	        	$("#page_alert_info").show().find("label").html("版本已成功发布");
	        	$("#imping").val("版本已成功发布").parent().show();
	        	setTimeout(function(){
	        		$("#imping").val("").parent().hide();
	        		$("#page_alert_info").hide();
	        		$('#newversion-form').remove();
	        	},3000);
	        }
	        else
	        {
	        	$("#imping").val("发布版本时异常："+result.result["msg"]).parent().show();
	        }
	    },
	    change: function (e, data) {
	        $.each(data.files, function (index, file) {
	        	var fix = file.name.split(".");
	        	fix = fix[fix.length-1].toLowerCase();
	        	$("#imping").val("").parent().hide();
	        	if(fix!="ipa" && fix!="apk" && fix!="exe")
	        	{
	        		$("#btn_file_upload").attr("disabled",true);
	        		$("#imping").val("文件格式不正确，只支持ipa|apk|exe").parent().show();
	        		return false;
	        	}
	        	$("#btn_file_upload").attr("disabled",false);
	            $("#newversion-form #file_metadata").val(file.name+"\t"+(file.size/1024/1024).toFixed(2)+"MB");
	        });
	    },
	    add: function (e, data) {
	    	data.context=$("#btn_file_upload").click(function(){
	    		var $alterpanl = $("#page_alert_info").hide().find("label").html("");
	    		var v = $.trim($("#v1").val());
	    		if(v=="" || isNaN(v))
	    		{
	    			$alterpanl.html("请输入正确的版本号").parent().show();
	    			$("#v1").focus();
	    			return;
	    		}
	    		v = $.trim($("#v2").val());
	    		if(v=="" || isNaN(v))
	    		{
	    			$alterpanl.html("请输入正确的版本号").parent().show();
	    			$("#v2").focus();
	    			return;
	    		}
	    		v = $.trim($("#v3").val());
	    		if(v=="" || isNaN(v))
	    		{
	    			$alterpanl.html("请输入正确的版本号").parent().show();
	    			$("#v3").focus();
	    			return;
	    		}
	    		v = $.trim($("#v4").val());
	    		if(v=="" || isNaN(v))
	    		{
	    			$alterpanl.html("请输入正确的版本号").parent().show();
	    			$("#v4").focus();
	    			return;
	    		}
	    		$alterpanl.html("正在发布新版本...").parent().show();
	    		$("#imping").val("正在发布新版本...").parent().show();	
	    		$("#btn_file_upload").attr("disabled",true);    		
	    		data.submit();
	    		//$("#expdept-form form").attr("action",Index.server+'&module=ApiHR&action=org_imp').ajaxForm();
	    	});
        	
    	}
	});
}

Version.Delete=function(id)
{
	bootbox.confirm({"size":'small',"message":"确定要删除该版本吗?", callback:function(result) {
        if(!result) return;
        var $alterpanl = $("#page_alert_info").show().find("label");
		var url = Index.server+'&module=ApiVersion&action=delete&jsoncallback=?';
		$alterpanl.html("正在删除版本数据...");
	    $.getJSON(url, {"id":id}, function(json) {	
	    	if(json.returncode=="0000")
	    	{
	        	$alterpanl.html("版本已删除");
	        	$("#versionlist table #"+id).remove();
	        	setTimeout(function(){$alterpanl.html("").parent().hide()},3000)
	    	}
	        else
	        {
	        	$alterpanl.html("删除版本时发生异常："+json.msg);
	        	setTimeout(function(){$alterpanl.html("").parent().hide()},10000)        
	        }
	    });        
    }});	
}

Version.save=function()
{

}