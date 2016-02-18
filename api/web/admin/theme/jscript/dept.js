var Dept={};
Dept.init=function(opts){
	Index.search.config({"text":"搜索:部门名称",callback:this.search});
	this.query();	
	this.nav.init();
};
Dept.new=function(){
	$dlg=$('#expdept-form');
	if($dlg.length>0){
		$dlg.remove();
	}
	$dlg=$('#newdept-form');
	if($dlg.length>0){
		$dlg.find(".alert-success").hide();
		$dlg.find(".alert-danger").hide();
		return;
	}
	$("#newdept-dlg").clone(true).removeClass('hide').attr("id","newdept-form").insertBefore($(".table-responsive"));
}
Dept.exp=function(){
	$dlg=$('#newdept-form');
	if($dlg.length>0){
		$dlg.remove();
	}
	$dlg=$('#expdept-form');
	if($dlg.length>0){
		$dlg.find(".alert-success").hide();
		$dlg.find(".alert-danger").hide();
		return;
	}
	$("#expdept-dlg").clone(true).removeClass('hide').attr("id","expdept-form").insertBefore($(".table-responsive"));	
	$("#expdept-form form").attr("id","formdata1");
	//$("#expdept-form input[type='file']").attr("id","filedata_1");
	$("#expdept-form #filedata").fileupload({
		maxChunkSize: 1024*1024*5,
		dataType:'json',
		acceptFileTypes:  /(\.|\/)(xls|xlsx)$/i,
	    url:Index.server,//文件上传地址，当然也可以直接写在input的data-url属性内
	    formData:{"module":"ApiHR","action":"org_imp"},//如果需要额外添加参数可以在这里添加
	    done:function(e,result){
	        //done方法就是上传完毕的回调函数，其他回调函数可以自行查看api
	        //注意result要和jquery的ajax的data参数区分，这个对象包含了整个请求信息
	        //返回的数据在result.result中，假设我们服务器返回了一个json对象
	        
	        if(result)
	        {
	        	$("#imping").val("部门数据已成功导入!").parent().show();
	        	setTimeout(function(){$("#imping").val("").parent().hide()},3000);
	        }
	        else
	        {
	        	$("#imping").val("数据导入异常："+result.result["msg"]).parent().show();
	        }
	    },
	    change: function (e, data) {
	        $.each(data.files, function (index, file) {
	        	var fix = file.name.split(".");
	        	fix = fix[fix.length-1];
	        	$("#imping").val("").parent().hide();
	        	if(fix!="xlsx" && fix!="xls")
	        	{
	        		$("#btn_file_upload").attr("disabled",true);
	        		$("#imping").val("文件格式不正确，只支持xlsx和xls。").parent().show();
	        		return false;
	        	}
	        	$("#btn_file_upload").attr("disabled",false);
	            $("#expdept-form #file_metadata").val(file.name+"\t"+(file.size/1024).toFixed(2)+"KB");
	        });
	    },
	    add: function (e, data) {
	    	data.context=$("#btn_file_upload").click(function(){
	    		$("#imping").val("正在导入部门数据中...").parent().show();
	    		data.submit();
	    		//$("#expdept-form form").attr("action",Index.server+'&module=ApiHR&action=org_imp').ajaxForm();
	    	});
        	
    	}
	})
}
Dept.Edit=function(deptid){
	this.new();
	var $infoWin = $("#newdept-form form");
	$infoWin.attr("deptid",deptid);
	var data = $("#"+deptid);
	$infoWin.find("#deptname").val(data.find("th:first").text());
	$infoWin.find("#parentid").attr("pid",data.find("th:eq(2)").attr("pid")).val(data.find("th:eq(2)").text());
	$infoWin.find("#noorder").val( parseInt(data.find("th:eq(4)").text()));
}
Dept.Delete=function(deptid){
	bootbox.confirm({"size":'small',"message":"确定要删除该部门吗?", callback:function(result) {
        if(!result) return;
        var $alterpanl = $("#page_alert_info").show().find("label");
		var url = Index.server+'&module=ApiHR&action=org_del&jsoncallback=?';
		$alterpanl.html("正在删除部门数据...");
	    $.getJSON(url, {"deptid":deptid}, function(json) {	
	    	if(json.returncode=="0000")
	    	{
	        	$alterpanl.html("部门已删除");
	        	$("#"+deptid).remove();
	        	setTimeout(function(){$alterpanl.html("").parent().hide()},3000)
	    	}
	        else
	        {
	        	$alterpanl.html("删除部门时发生异常："+json.msg);
	        	setTimeout(function(){$alterpanl.html("").parent().hide()},10000)        
	        }
	    });        
    }});
}
Dept.save=function()
{
	var data = {};
	var $infoWin = $("#newdept-form");
	if($infoWin.length==0) return;
	data.deptid=$.trim($infoWin.find("form").attr("deptid"));
	data.deptname = $.trim($infoWin.find("#deptname").val());
	data.p_deptid = $.trim($infoWin.find("#parentid").attr("pid"));
	data.noorder=$.trim($infoWin.find("#noorder").val());
	if(data.noorder=="") data.noorder="0";
	if(!this.Validation(data))
	{
		$("html,body").animate({scrollTop:$infoWin.offset().top-100},200);
		return;
	}
	$infoWin.find(".alert-danger").hide();
	$infoWin.find("#btn_save,#btn_cancel").attr("disabled",true);
	$infoWin.find(".alert-success").show().find("label").html("正在保存数据....");
	$("html,body").animate({scrollTop:$infoWin.offset().top-100},200);

	var url = Index.server+'&module=ApiHR&action='+(data.deptid!=""?"org_edit":"org_add")+'&jsoncallback=?';
    $.getJSON(url,data,function(json){
    	$infoWin.find("#btn_save,#btn_cancel").attr("disabled",false);
    	if(json.returncode=='0000')
    	{
		  	$infoWin.find(".alert-success").show().find("label").html("部门数据保存成功");
	        if(data.p_deptid==$(".page-breadcrumb li:last").attr("id").replace("nav_",""))
	        {
	        	Dept.query(data.p_deptid);
	        }
	        setTimeout(function(){ $('#newdept-form').remove()},3000);
    	}
	    else
	    {
	    	$infoWin.find(".alert-success").show().find("label").html("部门数据保存失败："+json.msg);
	    }
    });    
}

Dept.Validation=function(data)
{
	if(data==null) return false;
	if(data.deptname=="")
	{
		$("#newdept-form form .alert-danger").show().find("label").html("部门名称不能为空！");
		return false;
	}
	if(data.pid=="")
	{
		$("#newdept-form form .alert-danger").show().find("label").html("父部门不能为空，请选择！");
		return false;
	}
	if(data.deptid!="")
	{
		var $th = $("#"+data.deptid);
		if($th.length==0) return true;
		var oldDeptName = $th.find("th:eq(0)").text();
		var oldDeptPid = $th.find("th:eq(2)").attr("pid");
		var oldNoorder = parseInt($th.find("th:eq(4)").text());
		if(data.deptname==oldDeptName && data.p_deptid==oldDeptPid && data.noorder==oldNoorder)
		{
			$("#newdept-form form .alert-danger").show().find("label").html("部门数据没有发生更改，提交被拒绝！");
			return false;
		}
	}
	return true;
}

Dept.autoFriend=function(obj,deptid)
{
	var $pagealert = $("#page_alert_info").show(),$infoele = $pagealert.find("label");
	var cls =($(obj).children('i').attr('class')),ischecked=false;
	ischecked = cls.indexOf('fa-circle-o')>0 ? false : true;
	if(!ischecked)
	{
		bootbox.confirm({"size":'small',"message":"当新人员进入部门时，也将自动和成员成为好友，确定吗?", callback:function(result) {
	        if(!result) return;			
			$infoele.html("正在建立成员好友关系....");
			$("html,body").animate({scrollTop:$pagealert.offset().top-100},200);
			var url = Index.server+'&module=dept&action=setFriend&jsoncallback=?';
		    $.getJSON(url,{'params':{'deptid':[deptid]}},function(json){
		    	if(json.returncode=='0000')
		    	{
		    		$(obj).children('i').removeClass('fa-circle-o').addClass('fa-check-circle');
				  	$infoele.html("部门成员好友关系已全部创建成功！");
		    	}
			    else
			    {
			    	$infoele.html("成员好友关系建立失败："+json.msg);
			    }
		    });
	    }});
	}
	else
	{
		$infoele.html("正在撤消成员的好友关系....");
		$("html,body").animate({scrollTop:$pagealert.offset().top-100},200);
		var url = Index.server+'&module=dept&action=cancelAutoFriend&jsoncallback=?';
		    $.getJSON(url,{'params':{'deptid':[deptid]}},function(json){
		    	if(json.returncode=='0000')
		    	{
				  	$(obj).children('i').removeClass('fa-check-circle-o').addClass('fa-circle-o');
				  	$infoele.html("该部门成员好友关系已全部撤消。");
		    	}
			    else
			    {
			    	$infoele.html("撤消成员好友关系失败："+json.msg);
			    }
		    });       
	}
}

Dept.nav={};

Dept.nav.navel=null;
	Dept.nav.init=function(){
		var url = Index.server+'&module=ApiHR&action=org_info&jsoncallback=?';
	    $.getJSON(url, {"deptid": ""}, function(json) {
			var $nav=$(".page-toolbar .page-breadcrumb");
			if($nav.length==0)
			{
				$(".page-toolbar").append('<ul class="page-breadcrumb" style="float:right"></ul>');
				$nav=$(".page-toolbar .page-breadcrumb");				
			}
			Dept.nav.navel=$nav;
			Dept.nav.add(json.data.deptid,json.data.deptname);     
	    });
	},
	Dept.nav.add=function(id,text){
		if($("#nav_"+id).length>0) return;
		this.navel.append('<li id="nav_'+id+'"><a href="javascript:;" onclick="Dept.nav.remove(\''+id+'\');Dept.query(\''+id+'\')">'+text+'</a></i></li>');
		this.navel.children('li:last').prev().append('<i class="fa fa-angle-right"></i>');
	},
	Dept.nav.remove=function(id)
	{
		$cur=$("#nav_"+id);
		while($cur.next().length>0)
		{
			$cur.next().remove();
		}
		$cur.children('i').remove();
	}

Dept.query=function(deptid)
{
	if(deptid!=null)
	{
		var deptname = $("#"+deptid+" th:first").text();
		this.nav.add(deptid,deptname);
	}
	var url = Index.server+'&module=ApiHR&action=org_query&jsoncallback=?';
    Dept.dataurl = url;
    $.getJSON(Dept.dataurl, {"deptid": deptid==null?$("#query_parent_dept").attr("deptid"):deptid}, function(json) {
        var html = template('deptlist-tmpl', json);
		$('#deptlist tbody').html(html);
    });	
}

Dept.search=function()
{
	var deptname = $.trim($("#in_global_search").val());
	if(deptname=="") 
	{
		Dept.query();
		return;
	}
	var url = Index.server+'&module=ApiHR&action=org_query&jsoncallback=?';
    $.getJSON(url, {"search": deptname}, function(json) {
        var html = template('deptlist-tmpl', json);
		$('#deptlist tbody').html(html);
    });		
}

Dept.tree=function($treeEle)
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
			    		onClick:Dept.TreeClick,
			    		beforeExpand:Dept.TreeExpand
			    	}
	};
	var url = Index.server+'&module=ApiHR&action=org_query&jsoncallback=?';
    Dept.dataurl = url;
    $.getJSON(Dept.dataurl, {"deptid":""}, function(json) {
        var jsondata=[];
    	for (var i = 0; i < json.data.length; i++) {
    		jsondata[i] = {"id":json.data[i]["id"],"pid":json.data[i]["parent"],"name":json.data[i]["text"],"open":json.data[i]["parent"]=="-10000"?true:false,"isParent":json.data[i]["children"]>0?true:false};
    	};     	
        $.fn.zTree.init($treeEle, zTreeSetting, jsondata);   
        //$.fn.zTree.init($("#filter_depttree"), zTreeSetting, jsondata);
		Dept.treeObj = $.fn.zTree.getZTreeObj($treeEle.attr("id"));
    });
}
Dept.TreeExpand=function( treeId, treeNode)
{
	Dept.TreeClick(null,treeId, treeNode);
	return true;
}

Dept.TreeClick=function(event, treeId, treeNode)
{
        var id = treeNode.id;
		var childrenEle = $("#"+treeNode.tId+"_ul");
		if(event!=null)
		{
			$parentEl =$("#"+$("#"+Dept.treeObj.setting.treeId).attr("linkinput"));
			$parentEl.val(treeNode.name).attr("pid",treeNode.id);
		}
        if (treeNode.isParent && childrenEle.length==0)
        {
            var parameter = {"deptid":treeNode.id,"number":0 };
            $.getJSON(Dept.dataurl,parameter,function(json) {               
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
                        Dept.treeObj.addNodes(treeNode,jsondata);
                    }
                }
            });
        }
        $("#"+Dept.treeObj.setting.treeId).slimScroll({"max-height":"300px"});  
}

Dept.staff = Dept.staff||{};
Dept.staff.count = 0;
Dept.staff.page_index = 1;
Dept.staff.limit = 8;
Dept.staff.staff_div = "";//staff grid
Dept.staff.staff_target = "";//staff-grid append after the target
Dept.staff.deptid = "";//current deptid;

//查看部门员工列表
Dept.stafflist = function(e,deptid){

	//点击人员数进入方法,点击分页标签进入方法
	if(e){
		//点击人员数进入方法
		//初始化参数
		Dept.staff.staff_target = e;
		Dept.staff.deptid = deptid;

		Dept.staff.count = 0;
		Dept.staff.page_index = 1;
		Dept.staff.staff_div = "";
		if($("#dept-staff-list").length>0) $("#dept-staff-list").remove();
	}
	
	var dataurl = Index.server+'&module=staff&action=queryAllBaseInfo&jsoncallback=?';
	var counturl = Index.server+'&module=staff&action=queryAllCount&jsoncallback=?';
   	
	var params = {
		limit:Dept.staff.limit,
		deptid:Dept.staff.deptid,
		page_num:Dept.staff.page_index
	};
	if($('#dept-staff-list').length==0)
	{
		Dept.staff.staff_div = $("#dept-staff-model").clone(true).removeClass('hide').attr("id","dept-staff-list");
		$(Dept.staff.staff_target).after(Dept.staff.staff_div);
		$("html,body").animate({scrollTop:$("#dept-staff-list").offset().top-70},200);
	}
	$('#dept-staff-list .table-scrollable').append(Index.loadDataHtml);
	
	$.getJSON(dataurl,{'params':params},function(json){
		$('#dept-staff-list .table-scrollable>div').remove();
		if(json.data.length==0)
		{
			$('#dept-staff-list .table-scrollable').append(Index.noDataHtml );			
			return;
		}
		var html = template("dept-stafflist-tmpl",json);
		$("#dept-staff-list tbody").html(html);
		FaFaPresence.AddBind(null, null); //自动绑定
	});

	//分页
	$.getJSON(counturl,{'params':params},function(json){
		var count = parseInt(json);
		if (count!=Dept.staff.count) {
				Dept.staff.count = count;
				Dept.staff.pagination();
				
		}
	});

	
}

Dept.staff.pagination = function(e){
	var con = $("#dept-staff-list #dept-staff-Pagination");
	var opt = {
		callback: function(page_index,jq){
	
			Dept.staff.page_index = page_index+1;
			Dept.stafflist();
			con.find('a').attr('href','javascript:;');
		},//点击标签后的反应
		items_per_page:Dept.staff.limt,//每页显示的条数
		num_display_entries:3,//显示几个页的标签
		num_edge_entries:1,//超出部分显示几个页
		prev_text:'上一页',
		next_text:'下一页'
		};

	// alert(Dept.staff.count)
	con.pagination(Dept.staff.count, opt);	
	con.find('a').attr('href','javascript:;');
}
