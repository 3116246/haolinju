var Serviceno={};
Serviceno.login_account="";
Serviceno.microObj={};
Serviceno.init=function(opts){
	var obj = $($(".selected_pic_box .modal-body")[0].children[0].children[0]);
    var obj_ff = obj.find("embed");
    Serviceno.uploadObj = document.all==null? obj_ff : obj;
    Serviceno.uploadObj.css({"height":"475px","width":"515px","margin-left":"-10px","margin-top":"-15px" }); 
	Index.search.config({"text":"搜索:公众号消息",callback:this.search});
	this.search();
	$("#btn_logo_upload").on("click",function(){
		$(".selected_pic_box").modal("show");
	});
	$(".btnsendmessage").on('click',function(){
		$("#servicelist").show();
		$("#send_content").hide();		
	});
};

Serviceno.uploadfile=function(evn)
{
        $("#uplod_loading").attr("src","images/loader.gif");
        $("#uplod_loading").show();
        $(".upload_hint").text("正在上传，请稍候……");
        Serviceno.uploadObj[0].doSave();
};
Serviceno.window_close=function()
{
        $('.selected_pic_box').modal('hide');
        $("#uplod_loading").hide();
        $(".upload_hint").text("");
};

Serviceno.new=function()
{
	var $dlg=$('#newservice-form');
	if($dlg.length>0){
		$dlg.find(".alert-success").hide();
		$dlg.find(".alert-danger").hide();
		return;
	}
	else
	{
		$("#newservice-dlg").clone(true).removeClass('hide').attr("id","newservice-form").insertBefore($("#servicelist"));
		$dlg=$('#newservice-form');
	}
	$dlg.find("#service_account").attr("placeholder","正在生成帐号");
	var url = Index.server+'&module=service&action=serviceAccount&jsoncallback=?';
	$.getJSON(url,{},function(json){
		if(json.success)
		{
			Serviceno.login_account=json.account;
			$dlg.find("#service_account").val(json.account);
		}
	});
}
Serviceno.hint=function(txt)
{
	var $infoWin = $("#newservice-form");
	$infoWin.find("form .alert-danger label").html(txt).parent().show();
	$("html,body").animate({scrollTop:$infoWin.offset().top-100},200);
}

Serviceno.save=function()
{
	var $infoWin = $("#newservice-form");
    var parameter = {};
    parameter.micro_id = $.trim($infoWin.attr("micro_id"));
    var getvalue = $.trim($infoWin.find("#text_name").val());
    if ( getvalue=="")
    {
            this.hint("请输入公众号名称！");
            $("#text_name").focus();
            return false;
    }
    else{
            parameter.name = getvalue;
    }
    getvalue = $.trim($infoWin.find("#service_account").val());
    if ( getvalue=="")
    {
            this.hint("请输入公众帐号！");
            $("#service_account").focus();
            return false;
    }
    parameter.login_account = getvalue;
    parameter.desc = $.trim($infoWin.find("#textdesc").val());
    //管理员        
    var ctl = $infoWin.find("#service_manager");

    //组织部门
    ctl = $infoWin.find("#dept_area_sel>button");
    if (ctl.length==0)
    {
            parameter.deptid = Array();
    }
    else
    {
            var deptid = Array();
            for(var i=0;i<ctl.length;i++)
            {
                deptid.push(ctl.eq(i).attr("deptid"));
            }
            parameter.deptid = deptid;
    }
    
    parameter.staffid = Array();
    //
    parameter.concern_approval = 0;
            
    parameter.fileid = $infoWin.find("#logo").attr("fileid");	
	var url = Index.server+'&module=service&action=register_service&jsoncallback=?';	
	var parameter = { "params":parameter };
	this.hint("正在保存数据...");
	$.getJSON(url,parameter,function(json){
		if(json.success)
		{
			Serviceno.hint("公众号保存成功");
			setTimeout(function(){
				Serviceno.search();
				$infoWin.hide('400', function() {
					$infoWin.remove();
				});
			},3000);
		}
		else
		{
			Serviceno.hint("数据保存发生异常："+json.msg);
		}
	});
}

Serviceno.Delete=function(account)
{
	bootbox.confirm({"size":'small',"message":"确定要删除该公众号吗?", callback:function(result) {
        if(!result) return;
        var $alterpanl = $("#page_alert_info").show().find("label");
        var accountTr = $("#servicelist div[id='"+account+"']");
		var url = Index.server+'&module=service&action=delete_service&jsoncallback=?';
		$alterpanl.html("正在删除公众号...");
		$("html,body").animate({scrollTop:$("#page_alert_info").offset().top-100},200);
	    $.getJSON(url, {"params":{"login_account":account,"micro_id":accountTr.attr("microid")}}, function(json) {	
	    	if(json.returncode=="0000")
	    	{
	        	$alterpanl.html("公众号已删除");
	        	accountTr.next().remove();
	        	accountTr.remove();
	        	setTimeout(function(){$alterpanl.html("").parent().hide()},3000)
	    	}
	        else
	        {
	        	$alterpanl.html("删除公众号时发生异常："+json.msg);
	        	setTimeout(function(){$alterpanl.html("").parent().hide()},10000)        
	        }
	    });        
    }});	
}

Serviceno.Edit=function(account)
{
	var $dlg=$('#newservice-form');
	if($dlg.length>0){
		$dlg.find(".alert-success").hide();
		$dlg.find(".alert-danger").hide();
		return;
	}
	else
	{
		$("#newservice-dlg").clone(true).removeClass('hide').attr("id","newservice-form").insertBefore($("#servicelist"));
		$dlg=$('#newservice-form');
	}
	var url = Index.server+'&module=service&action=get_service&jsoncallback=?';
	$.getJSON(url,{"params":{"login_account":account}},function(json){
		if(json.success)
		{
			Serviceno.login_account=json.account;
			$microTr = $("#servicelist div[id='"+account+"']");
			$dlg.attr("micro_id",$microTr.attr("microid")) ;
			$dlg.find("#text_name").val($.trim($microTr.children("div:first").text())) ;
			$dlg.find("#service_account").val(account);
			$dlg.find("#textdesc").val(json.staff_basic.desc);
			$dlg.find("#logo").attr({"fileid":json.staff_basic.fileid,"src":Index.host+"/getfile/"+json.staff_basic.fileid});
			var $con = $("#newservice-form #dept_area_sel");
			for (var i = 0; i < json.staff_area.length; i++) {
				if(json.staff_area[i].type!="1") continue;
			 	var tags='<button onClick="$(this).remove()" pid="'+json.staff_area[i].pid+'" deptid="'+json.staff_area[i].objid+'" class="btn default">'+json.staff_area[i].nick_name+'</button>';
				$con.append(tags);
			}
			$("html,body").animate({scrollTop:$dlg.offset().top},200);
		}
	});
}

Serviceno.Sendmsg=function(account)
{
	var control = $("#servicelist div[id='"+account+"']");
    this.microObj = {};
    this.microObj.microNumber = account;
    this.microObj.microJid = "";
    this.microObj.microName = $.trim(control.children('div:first').text());
    this.microObj.microUse = "1";
    this.microObj.microType = "0";
    this.microObj.microOpenid = "";
    $(".micro_obj").text(this.microObj.microName+"("+this.microObj.microNumber+")");	
	$("#servicelist").hide();
	$("#send_content").show();
}

Serviceno.search=function(searchvalue)
{
	var url = Index.server+'&module=service&action=search_service&jsoncallback=?';
    $.getJSON(url, {}, function(json) {
        var html = template('servicelist-tmpl', json);
        if(json.returncode=="0000")
			$('#servicelist').html(html);
		else
			$("#page_alert_info").show().find("label").html(json.msg);
    });	
}

Serviceno.more=function(account)
{
	var url = Index.server+'&module=service&action=search_sendmessage&jsoncallback=?';
	var accountTr = $("#servicelist div[id='"+account+"']");	
	accountTr.find("#pushmessagelist").remove();
	$("#page_alert_info").hide();
	$.getJSON(url,{"params":{"login_account":account}},function(json){
		if(json.returncode=="0000")
		{
			var tbHtml = template('pushmessagelist-tmpl',json);
			accountTr.append(tbHtml);
		}
		else
		{
			$("#page_alert_info").show().find("label").html(json.msg);
		}
	});
	
}
Serviceno.revokeMessage=function(account,msgid) {
	bootbox.confirm({"size":'small',"message":"确定要撤回该消息吗?", callback:function(result) {
        if(!result) return;
        var $alterpanl = $("#page_alert_info").show().find("label");
		var url = Index.server+'&module=service&action=service_revoke&jsoncallback=?';
		$alterpanl.html("正在发送撤回命令...");
		$("html,body").animate({scrollTop:$("#page_alert_info").offset().top-100},200);
	    $.getJSON(url, {"params":{"login_account":account,"msgid":msgid}}, function(json) {	
	    	if(json.returncode=="0000")
	    	{
	        	$alterpanl.html("撤回命令已发送");
	        	var accountTr = $("#servicelist div[id='"+account+"']");
	        	accountTr.find("#pushmessagelist #"+msgid).remove();
	        	setTimeout(function(){$alterpanl.html("").parent().hide()},3000)
	    	}
	        else
	        {
	        	$alterpanl.html("发送撤回命令时发生异常："+json.msg);
	        	setTimeout(function(){$alterpanl.html("").parent().hide()},10000)        
	        }
	    });        
    }});	
}

Serviceno.detailMessage=function(msgid) {
	//getMessageDetail
	var url = Index.server+'&module=service&action=getMessageDetail&jsoncallback=?';
	$.getJSON(url, {"params":{"msgid":msgid}}, function(json) {	
	    	if(json.returncode=="0000")
	    	{
	        	$("#detailmessage .modal-body").html(json.data[0].msg_content);
	        	$("#detailmessage").modal('show');
	    	}
	        else
	        {
	        	$alterpanl.html("获取消息正文时发生异常："+json.msg);
	        	setTimeout(function(){$alterpanl.html("").parent().hide()},10000)        
	        }
	});	
}

Serviceno.tree=function($treeEle)
{	
	var zTreeSetting = {
			    	data:{
			    		simpleData:{
			    			enable:true,
			    			idKey: "id",
							pIdKey: "pid",
							rootPId: "v100000"
			    		}
			    	},
			    	checkable : true,
			    	callback:{
			    		onClick:Serviceno.TreeClick,
			    		beforeExpand:Serviceno.TreeExpand
			    	}
	};
	var url = Index.server+'&module=ApiHR&action=org_query&jsoncallback=?';
	Serviceno.dataurl = url;
    $.getJSON(url, {"deptid":""}, function(json) {
        var jsondata=[];
    	for (var i = 0; i < json.data.length; i++) {
    		jsondata[i] = {"id":json.data[i]["id"],"pid":json.data[i]["parent"],"name":json.data[i]["text"],"open":json.data[i]["parent"]=="-10000"?true:false,"isParent":json.data[i]["children"]>0?true:false};
    	};     	
        $.fn.zTree.init($treeEle, zTreeSetting, jsondata);   
        //$.fn.zTree.init($("#filter_depttree"), zTreeSetting, jsondata);
		Serviceno.treeObj = $.fn.zTree.getZTreeObj($treeEle.attr("id"));
    });
}
Serviceno.TreeExpand=function( treeId, treeNode)
{
	Serviceno.TreeClick(null,treeId, treeNode);
	return true;
}

Serviceno.TreeClick=function(event, treeId, treeNode)
{
        var id = treeNode.id;
		var childrenEle = $("#"+treeNode.tId+"_ul");
		if(event!=null)
		{
			var $con = $("#newservice-form #dept_area_sel");
			if($con.find("button[deptid='"+treeNode.id+"']").length>0) return; 
			//$parentEl =$("#"+$("#"+Dept.treeObj.setting.treeId).attr("linkinput"));
			//$parentEl.val(treeNode.name).attr("pid",treeNode.id);			
			var tags='<button onClick="$(this).remove()" pid="'+treeNode.pid+'" deptid="'+treeNode.id+'" class="btn default">'+treeNode.name+'</button>';
			$con.append(tags);
			$pEl = $con.find("button[deptid='"+treeNode.pid+"']");
			if($pEl.length>0)
			{
				$pEl.remove();
			}
			$childEl = $con.find("button[pid='"+treeNode.id+"']");
			if($childEl.length>0)
			{
				$.each($childEl, function(index, val) {
					  val.remove();
				});				
			}
		}
        if (treeNode.isParent && childrenEle.length==0)
        {
            var parameter = {"deptid":treeNode.id,"number":0 };
            $.getJSON(Serviceno.dataurl,parameter,function(json) {               
                if (json.data)
                {
                    if (json.data.length==0)
                    {
                        
                    }
                    else
                    {
                    	var jsondata=[];
				    	for (var i = 0; i < json.data.length; i++) {
				    		jsondata[i] = {"id":json.data[i]["id"],"pid":json.data[i]["parent"],"name":json.data[i]["text"],"open":json.data[i]["parent"]=="-10000"?true:false,"isParent":json.data[i]["children"]>0?true:false};
				    	};
                        Serviceno.treeObj.addNodes(treeNode,jsondata);
                    }
                }
            });
        }
        $("#"+Serviceno.treeObj.setting.treeId).slimScroll({"height":"200px"});  
}

function avatar_success()
{
    var parameter = {"module":"staff","action":"save_Photo","params":{"login_account":Serviceno.login_account }};
    $.getJSON(Index.server+"&jsoncallback=?",parameter,function(returndata){
            if (returndata.success)
            {
            	$logo = $("#newservice-form #logo");
              	$(".selected_pic_box").modal("hide");
              	var getfile = Index.host+"/getfile/";
              	var fileid = returndata.fileid;
              	if ( fileid !="")
                	$logo.attr("src",getfile + fileid);
              	else
                	$logo.attr("src","images/service_default_logo.jpg");
              	$logo.attr("fileid",fileid);
             	$("#uplod_loading").hide();
             	$(".upload_hint").text("");
            }
            else
            {
             $("#uplod_loading").attr("src","/bundles/fafatimewebase/images/errow.gif");
             $("#uplod_loading").show();
             $(".upload_hint").text("Logo上传失败");
            }
	});
}
