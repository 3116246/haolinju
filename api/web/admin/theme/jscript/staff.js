var ADD_STAFF = "/api/http/staff/new";
var REMOVE_STAFF = "/api/http/staff/remove_phy";
var GET_STAFF = "/api/http/getstaffcard";
var QUERY_STAFF = "/api/http/exec_dataaccess";


var Staff={};

Staff.limit=15;
Staff.page_index = 1;
Staff.count = 0;

Staff.init=function(opts){
	Index.search.config({"text":"搜索:账号昵称","callback":this.query});
	this.query();
	Staff.tree($("#newstaff-dlg #depttree"));
	
};
Staff.closeDetpTree = function(){
	$("#dept-div .remove").off( "click", "**" );
	$("#dept-div").css("display","none");

}
Staff.showDeptTree = function(){
	Staff.tree($("#dept-div #depttreeQ"),Staff.queryByDept);//按部门查询树
	$("#dept-div").css("display","block");
}
Staff.new=function(){
	$dlg=$('#newstaff-form');
	if($dlg.length>0) return;
	$("#newstaff-dlg").clone(true).removeClass('hide').attr("id","newstaff-form").insertBefore($(".table-responsive"));
}

Staff.import = function(){
	
	$dlg=$('#newstaff-form');
	if($dlg.length>0){
		$dlg.remove();
	}
	$dlg=$('#expstaff-form');
	if($dlg.length>0){
		$dlg.find(".alert-success").hide();
		$dlg.find(".alert-danger").hide();
		return;
	}
	var expstaff = $("#exstaff-dlg").clone(true).removeClass('hide').attr("id","expstaff-form");
	$(".page-bar").after(expstaff);	
	//$("#expdept-form form").attr("id","formdata1");
	
	$("#expstaff-form #filedata").fileupload({
		maxChunkSize: 1024*1024*5,
		dataType : 'json',  
	 // jsonp:"jsoncallback",
		acceptFileTypes:  /(\.|\/)(xls|xlsx)$/i,
	    url:Index.server,//文件上传地址，当然也可以直接写在input的data-url属性内
	    formData:{"module":"ApiHR","action":"staff_imp"},//如果需要额外添加参数可以在这里添加
	    done:function(e,result){
	        //done方法就是上传完毕的回调函数，其他回调函数可以自行查看api
	        //注意result要和jquery的ajax的data参数区分，这个对象包含了整个请求信息
	        //返回的数据在result.result中，假设我们服务器返回了一个json对象
	        
	        if(result)
	        {
	        	$("#imping").val("数据已成功导入!").parent().show();
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
	            $("#expstaff-form #file_metadata").val(file.name+"\t"+(file.size/1024).toFixed(2)+"KB");
	        });
	    },
	    add: function (e, data) {
	    	data.context=$("#btn_file_upload").click(function(){
	    		$("#imping").val("正在导入员工数据中...").parent().show();
	    		data.submit();
	    		//$("#expdept-form form").attr("action",Index.server+'&module=ApiHR&action=org_imp').ajaxForm();
	    	});
        	
    	}
	});
	
}

Staff.del = function(el,account){
	bootbox.confirm("你确定要删除这条数据吗?", function(result) {
        if(result){
            var url = Index.host+REMOVE_STAFF+"?openid="+Index.openid+"&staff="+account+"&jsoncallback=?";
			$.getJSON(url,{}, function(json) {
				if(json.returncode=="0000")
				{
					$(el).parent().parent().remove();
				}
				else
				{
					bootbox.alert("删除失败，请稍后操作！"); 
				}
			});
        }
    });
}

Staff.editForm = function(el,account){
		Staff.new();
		
		$("#newstaff-form .form-group #password").parent().parent().parent().remove();
		$("#newstaff-form").attr('mode','edit')
		var url = Index.host+GET_STAFF+'?jsoncallback=?&'+"&openid="+Index.openid+"&staff="+account;
		$.getJSON(url, function(json) {
			var $infoWin = $("#newstaff-form");
			if(json.returncode=="0000")
		   	{
		  		var staff = json.staff_full;
		  		Staff.info = staff;
		  		
		  		if($infoWin.length==0) return;
					
					$infoWin.find("#account").attr("disabled","true").val(staff.login_account);
					$infoWin.find("#realName").val(staff.nick_name);
					//$infoWin.find("#password").val(staff.);
					$infoWin.find("#mobile").val(staff.mobile_bind);
					$infoWin.find("#duty").val(staff.duty);
					$infoWin.find("#dept").val(staff.dept_name).attr('deptid',staff.dept_id);
					$infoWin.find(".form-group input[type='radio'][value="+staff.sex+"]").attr("checked",true);
			}else{
			 		$infoWin.find(".alert-danger").show().find("label").html("系统异常，请稍后再试！"); 
			}
		});
	
}

Staff.edit = function(){
	var staffinfo = "";
	
	var $infoWin = $("#newstaff-form");
	if($infoWin.length==0) return;
	var trim = function(oldStr,newStr){
			return oldStr==newStr? "":newStr;
		};
		
	var mobile = trim(Staff.info.mobile_bind,$.trim($infoWin.find("#mobile").val()));
	var regMobile = /^[1][0-9]{10}$/;
		if(mobile){
			if(!regMobile.test(mobile)){
				$("#newstaff-form form .alert-danger").show().find("label").html("手机号格式不正确！");
				return false;
			}
		}
		
	staffinfo+="nick_name="+encodeURIComponent(trim(Staff.info.nick_name,$.trim($infoWin.find("#realName").val())));
	staffinfo+="&deptid="+trim(Staff.info.dept_id,$.trim($infoWin.find("#dept").attr('deptid')));
	staffinfo+="&duty="+encodeURIComponent(trim(Staff.info.duty,$.trim($infoWin.find("#duty").val())));
	staffinfo+="&sex="+encodeURIComponent(trim(Staff.info.sex_id,$.trim($infoWin.find(".form-group input[name='sex']:checked").val())));
	staffinfo+="&mobile="+mobile;
	staffinfo+="&staff="+trim(Staff.info.account,$.trim($infoWin.find("#account").val()));

	$("html,body").animate({scrollTop:$infoWin.offset().top-100},200);
	$infoWin.find(".alert-success").show().find("label").html("正在保存员工数据...");
	var url = Index.host+QUERY_STAFF+'?jsoncallback=?&module=ApiHR&action=staff_modify&'+staffinfo+"&openid="+Index.openid;
	 $.getJSON(url, function(json) {
				$infoWin.find("#btn_save,#btn_cancel").attr("disabled",false);
    	if(json.returncode=="0000")
    	{    		
    		Staff.query();
        	$infoWin.find(".alert-success").show().find("label").html("员工信息修改成功");
        	$('#newstaff-form').remove();        
    	}
        else
        {
        	$infoWin.find(".alert-danger").show().find("label").html("员工信息修改异常："+json.msg);
        }
	});
}

Staff.save=function()
{
	
	var $infoWin = $("#newstaff-form");
	if($infoWin.length==0) return;
	var mode = $infoWin.attr("mode");
	if(mode){ this.edit();return;};
	
	var staffinfo = "[{";
	//staffinfo.deptid = $.trim($infoWin.find("#parentid").attr("pid"));
	//staffinfo.ldap_uid=$.trim($infoWin.find("#noorder").val());
	staffinfo+="\"import\":\""+1+"\"";
	staffinfo+=",\"isNew\":\""+0+"\"";
	staffinfo+=",\"account\":\""+$.trim($infoWin.find("#account").val())+"\"";
	staffinfo+=",\"realName\":\""+$.trim($infoWin.find("#realName").val())+"\"";
	staffinfo+=",\"passWord\":\""+$.trim($infoWin.find("#password").val())+"\"";
	staffinfo+=",\"mobile\":\""+$.trim($infoWin.find("#mobile").val())+"\"";
	staffinfo+=",\"duty\":\""+$.trim($infoWin.find("#duty").val())+"\"";
	staffinfo+=",\"deptid\":\""+$.trim($infoWin.find("#dept").attr('deptid'))+"\"";
	staffinfo+=",\"sex\":\""+$.trim($("#newstaff-form .form-group input[name='sex']:checked").val())+"\"";
	staffinfo+="}]";
	
	if(!this.Validation(jQuery.parseJSON(staffinfo.slice(staffinfo.indexOf("=")+1))[0]))
	{
		$("html,body").animate({scrollTop:$infoWin.offset().top-100},200);
		return;
	}
	
	$infoWin.find(".alert-danger").hide();
	$infoWin.find("#btn_save,#btn_cancel").attr("disabled",true);
	$infoWin.find(".alert-success").show().find("label").html("正在保存数据....");
	
	$("html,body").animate({scrollTop:$infoWin.offset().top-100},200);

	
	var url= Index.server+'&module=ApiHR&action=staff_add&jsoncallback=?';
	$.post(url, {"staffinfo":staffinfo}, function(datar) {
		if(datar.returncode=='0000')
		{
			$infoWin.find(".alert-success").show().find("label").html("员工新增成功");
        	setTimeout(function(){ $('#newstaff-form').remove()},3000);
		}
		else
		{
			$infoWin.find(".alert-success").show().find("label").html("员工新增异常："+datar.msg);
		}
	},'json');
   
	/* 
    $.getJSON(url,{"staffinfo":staffinfo}, function(json) {
    	
    	$infoWin.find("#btn_save,#btn_cancel").attr("disabled",false);
    	if(json.returncode=="0000")
    	{
        	$infoWin.find(".alert-success").show().find("label").html("员工新增成功");
        	$('#newstaff-form').remove();
    	}
        else
        {
        	$infoWin.find(".alert-success").show().find("label").html("员工新增异常："+json.msg);
        }
    });	*/
}

Staff.Validation=function(data)
{
	
	if(data==null) return false;
	if(data.realName=="")
	{
		$("#newstaff-form form .alert-danger").show().find("label").html("姓名不能为空！");
		return false;
	}
	if(data.dept=="")
	{
		$("#newstaff-form form .alert-danger").show().find("label").html("所属部门不能为空！");
		return false;
	}
	
	if(data.passWord=="")
	{
		$("#newstaff-form form .alert-danger").show().find("label").html("登录密码不能为空！");
		return false;
	}
	
	var regMobile = /^[1][0-9]{10}$/;
	var reg =/^(\w)+(\.\w+)*@(\w)+((\.\w{2,3}){1,3})$/;
	if(!reg.test(data.account)){
		$("#newstaff-form form .alert-danger").show().find("label").html("邮箱格式不正确！");
		return false;
		}
		
		if(data.mobile){
			if(!regMobile.test(data.mobile)){
				$("#newstaff-form form .alert-danger").show().find("label").html("手机号格式不正确！");
				return false;
			}
		}
	
	return true;
}

Staff.queryByDept = function(event, treeId, treeNode){
	$("#in_global_search").val("");
	Staff.closeDetpTree();
	Staff.query(treeNode.id=="v100000"?"":treeNode.id);
}

Staff.query=function(deptid)
{
		
		var dataurl = Index.server+'&module=ApiHR&action=staff_query&jsoncallback=?';
		var counturl = Index.server+'&module=ApiHR&action=staff_count&jsoncallback=?';
   	var account = $("#in_global_search").val();
    
    
    var params = {
    		limit:Staff.limit,
    		deptid:deptid?deptid:"",
    		page_num:Staff.page_index,
    		search:account
    	}
    	
    	//获取总记录数，刷新分页标签
    $.getJSON(counturl, params, function(json) {
    	var count = parseInt(json.data);
    	
    	if(count!=Staff.count){
    		Staff.count = count;
    		Staff.pagination();
    	}
				
    });	
    	
    	//获取内容，刷新页面
    $.getJSON(dataurl, params, function(json) {
      var html = template('stafflist-tmpl', json);
			$('#staffList tbody').html(html);
			FaFaPresence.AddBind(null, null); //自动绑定
    });	
}

Staff.pagination = function(){
		var opt = {
			callback: function(page_index,jq){
		
				Staff.page_index = page_index+1;
				Staff.query();
			},//点击标签后的反应
			items_per_page:Staff.limit,//每页显示的条数
			num_display_entries:6,//显示几个页的标签
			num_edge_entries:2,//超出部分显示几个页
			prev_text:'上一页',
			next_text:'下一页'
			};
	
		$("#Pagination").pagination(Staff.count, opt);
	};
	


//=============部门树================
Staff.tree=function($treeEle,fn)
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
			    		onClick:fn?fn:Staff.TreeClick,
			    		beforeExpand:Staff.TreeExpand
			    	}
	};
	var url = Index.server+'&module=ApiHR&action=org_query&jsoncallback=?';
    $.getJSON(url, {"deptid":""}, function(json) {
        var jsondata=[];
    	for (var i = 0; i < json.data.length; i++) {
    		jsondata[i] = {"id":json.data[i]["id"],"pid":json.data[i]["parent"],"name":json.data[i]["text"],"open":json.data[i]["parent"]=="-10000"?true:false,"isParent":json.data[i]["children"]>0?true:false};
    	};     	
        $.fn.zTree.init($treeEle, zTreeSetting, jsondata);   
        //$.fn.zTree.init($("#filter_depttree"), zTreeSetting, jsondata);
		Staff.treeObj = $.fn.zTree.getZTreeObj($treeEle.attr("id"));
    });
}
Staff.TreeExpand=function( treeId, treeNode)
{
	Staff.TreeClick(null,treeId, treeNode);
	return true;
}

Staff.TreeClick=function(event, treeId, treeNode)
{
        var id = treeNode.id;
		var childrenEle = $("#"+treeNode.tId+"_ul");
		if(event!=null)
		{
			$parentEl =$("#"+$("#"+Dept.treeObj.setting.treeId).attr("linkinput"));
			$parentEl.val(treeNode.name).attr("deptid",treeNode.id);
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
                        Staff.treeObj.addNodes(treeNode,jsondata);
                    }
                }
            });
        }
        $("#"+Dept.treeObj.setting.treeId).slimScroll({"height":"300px"});  
}
