var ExceptionLogs = ExceptionLogs||{};

// Index.server = "http://localhost/api/http/exec_dataaccess?openid=chenjd@justsy.com";//for test

ExceptionLogs.getExceptionList = function()
{	
	var url = Index.server+'&module=app&action=getExceptionList&jsoncallback=?&params={\"limit\":15,\"page_index\":1}';
	$.getJSON(url,function(json){
		if(json)
		{
			var html = template('exptionlist-tmpl', json);
			$('#exceptionList tbody').html(html);
		}
		else
		{
			$("#page_alert_info").show().find("label").html("获取APP异常信息失败："+json.msg);
		}
	});	
}

ExceptionLogs.del = function(e,reportId){
	bootbox.confirm("你确定要删除这条数据吗?", function(result) {
        if(result){
            var url = Index.server+'&module=app&action=delExceptionLog&jsoncallback=?';
			var params = {reportId:reportId};

			$.getJSON(url,{'params':params},function(json){
				if(json.returncode=="0000"){
					$(e).parent().parent().remove();
				}
			});
        }
    });

}