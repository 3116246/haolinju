var Pushservice={};
Pushservice.init=function(opts){
	Index.search.config(null);
	this.query();
	$("#wikidir li").on('click',function(){
		var $this=$(this);
		$this.parent().children("li.active").removeClass('active');
		$this.addClass('active');
		$(".todo-content").hide();
		$("#word_wiki").show().find('.portlet-body').load($this.attr('href'));
	});
	$("#btn_wiki_close").on('click',function(){
		$(".todo-content").show();
		$("#wikidir li.active").removeClass('active');
		$("#word_wiki").hide().find('.portlet-body').html("");
	})
};

Pushservice.Validation=function(data)
{
	if(data==null) return false;
	if(data.appname=="")
	{
		$("#newpushservice-form form .alert-danger").show().find("label").html("PUSH应用名称不能为空！");
		return false;
	}	
	return true;
}

Pushservice.save=function()
{
	var data = {};
	var $infoWin = $("#newpushservice-form");
	if($infoWin.length==0) return;
	data.appid=$.trim($infoWin.find("form").attr("appid"));
	data.appname = $.trim($infoWin.find("#appname").val());
	if(!this.Validation(data))
	{
		return;
	}
	$infoWin.find(".alert-danger").hide();
	$infoWin.find("#btn_save,#btn_cancel").attr("disabled",true);
	$infoWin.find(".alert-success").show().find("label").html("正在保存数据...."); 

	var url = Index.server+'&module=app&action=save&jsoncallback=?';
    $.getJSON(url,{"params":data}, function(json) {
    	$infoWin.find("#btn_save,#btn_cancel").attr("disabled",false);
    	if(json.returncode=="0000")
    	{
        	$infoWin.find(".alert-success").show().find("label").html("数据保存成功");
        	Pushservice.query();        	
    	}
        else
        {
        	$infoWin.find(".alert-success").show().find("label").html("数据保存发生异常："+json.msg);
        }
    });
}
Pushservice.new=function()
{
	$dlg=$('#newpushservice-form');
	if($dlg.length>0) return;
	$("#newpushservice-dlg").clone(true).removeClass('hide').attr("id","newpushservice-form").insertBefore($("#applist"));	

}

Pushservice.query=function()
{
	var url = Index.server+'&module=app&action=search&jsoncallback=?';
    $.getJSON(url, {}, function(json) {
        var html = template('applist-tmpl', json);
        if(json.returncode=="0000")
			$('#applist').html(html);
		else
			$("#page_alert_info").show().find("label").html(json.msg);
    });	
}