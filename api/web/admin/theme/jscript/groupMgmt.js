
var Group = {};
Group.page_index = 1;
Group.limit = 8;
Group.count = 0;


var GROUP_CREATE = "mb/defaultgroup/create";
var GROUP_INTERFACE = "interface/data_access";

Group.init = function(){
	var obj = $($(".selected_pic_box .modal-body")[0].children[0].children[0]);
    var obj_ff = obj.find("embed");
    Group.uploadObj = document.all==null? obj_ff : obj;
    Group.uploadObj.css({"height":"475px","width":"515px","margin-left":"-10px","margin-top":"-15px" }); 

    $("#btn_logo_upload").on("click",function(){
		$(".selected_pic_box").modal("show");
	});

    $("#add_staff").on('keypress',function(evt){
    	if(evt.which==13)
    	{
    		$("#btn_search_staff").trigger('click');
    	}
    });
	Index.search.config({"text":"搜索:群组名称","callback":this.search});
	Group.query();
	Group.tree($("#newGroup-dlg #depttree"));
}

Group.del = function(el,id){
	
	bootbox.confirm("你确定要删除这条数据吗?", function(result) {
		if (result) {
			var url = Index.server+"&jsoncallback=?&action=delDefaultGroup&module=group";
			var params = {'groupid':id};

			$.getJSON(url,{'params':params},function(json){
			if (json.returncode=="0000") {
				$(el).parent().parent().remove();
			};

			});       
		};
        
        
    }); 
	
}

Group.staff = Group.staff||{};
Group.staff.$grid_div = "";
Group.staff.$after_target = "";//the staff.grid_div append after the target.
Group.staff.count = 0;
Group.staff.page_index = 1;
Group.staff.limit = 5;
Group.staff.groupid = "";

Group.showGroupStaff = function(e,groupid,num){
	//两个地方进入方法：点击成员数量，点击分页标签
	if (e) {
		//点击成员数量
		Group.staff.$grid_div = "";
		Group.staff.$after_target = $(e);
		Group.staff.page_index = 1;
		Group.staff.count = num;
		Group.staff.groupid = groupid;
	};

	// Index.server = "http://localhost/api/http/exec_dataaccess?openid=chenjd@justsy.com";//for test
	var dataurl = Index.server+'&module=group&action=getGroupMembers&jsoncallback=?&params={\"groupid\":\"'
	+Group.staff.groupid+'\",\"limit\":\"'
	+Group.staff.limit+'\",\"page_index\":\"'
	+Group.staff.page_index+'\"}';
	

	$.getJSON(dataurl,function(json){
		var html = template("group-stafflist-tmpl",json);
		if (!Group.staff.$grid_div) {
			//点击成员数量标签进入
			Group.staff.$grid_div = $("#group-staff-model").clone(true).removeClass("hide").attr("id","group-staff-div")
			Group.staff.pagination(Group.staff.$grid_div);
		}
		
		Group.staff.$grid_div.find("#group-staff-grid tbody").html(html);
		Group.staff.$after_target.after(Group.staff.$grid_div);
		FaFaPresence.AddBind(null, null); //自动绑定
		
	});

}

Group.staff.del_el = "";

Group.staff.dissolve = function(){
	//解散群
	Group.staff.$grid_div.remove();
	Group.query();
}

Group.staff.del = function(e,jid,groupid){
	// Index.server = "http://localhost/api/http/exec_dataaccess?openid=chenjd@justsy.com";//for test
	var dataurl = Index.server+'&module=group&action=delGroupMember&jsoncallback=?';
	var params = {
		jid:jid,
		groupid:groupid
	}
	
	Group.staff.del_el = e;

	$.getJSON(dataurl,{'params':params},function(json){
		if (!json) {
			Group.staff.$grid_div.find("#group-alert-danger").show().find("label").html("服务器异常，请稍后再试！");
			return;
		};

		if (json.data&&json.data=="false") {
			Group.staff.$grid_div.find("#group-alert-danger").show().find("label").html(json.msg);
		}else if(json.data&&json.data=="dissolve"){
			//解散群
			Group.staff.$grid_div.find("#group-alert-success").show().find("label").html(json.msg);
			setTimeout(Group.staff.dissolve,500);

		}
		$(Group.staff.del_el).parent().parent().remove();
		
	});
}

Group.staff.pagination = function($e){
	var opt = {
		callback: function(page_index,jq){
	
			Group.staff.page_index = page_index+1;
			Group.showGroupStaff();
		},//点击标签后的反应
		items_per_page:Group.staff.limit,//每页显示的条数
		num_display_entries:3,//显示几个页的标签
		num_edge_entries:1,//超出部分显示几个页
		prev_text:'上一页',
		next_text:'下一页'
		};
		
	if ($e){ 
		$e.find("#Pagination").pagination(Group.staff.count, opt);
		$e.find("#Pagination a").attr("href","javascript:;");

	}
	else{ 
		$("#Pagination").pagination(Group.staff.count, opt);
		$("#Pagination a").attr("href","javascript:;");
	}
}

Group.search=function(value)
{
	Group.page_index=1;
	Group.query();
}

Group.query = function(){
	
	var url = Index.server+"&jsoncallback=?&action=searchDefaultGroup&module=group";
	var params = {
		groupname:encodeURIComponent($("#in_global_search").val()),
		pageindex:Group.page_index,
		record:Group.limit
	};
	
	$.getJSON(url,{"params":params},function(json){
			var html = template('grouplist-tmpl', json);
			$('#groupList tbody').html(html);
			
	});
		
	$.getJSON(url.replace("searchDefaultGroup","count"),{"params":params},function(json){
			if(Group.count != json.data){
				Group.pagination(json.data);
				Group.count = json.data;
			}	
	});	
}

Group.editForm = function(el,id){
	Group._new();
		
		
		
		var url = Index.server+"&jsoncallback=?&action=getGroupInfo&module=group&params={\"groupid\":\""+id+"\"}";
		
		$.getJSON(url, function(json) {
			
			if(json.returncode=="0000")
		   	{
		  		var group = json.data.basic;

		  		var form = $("#newGroup-form");
		  		form.attr("groupid",id);
				form.find("#group_name").val(group.groupname);
				form.find("#group_desc").val(group.groupdesc);
				form.find("#limit").val(group.max_number);

				var memberArea = json.data.member_area;
				for(var i=0;i<memberArea.length;i++){
					var member = memberArea[i];
					var status = member.status;

					if (status=="1") {
						//包含的部门
						$("#newGroup-form #dept_area_sel").attr("deptid",member.objid).val(member.objname);						
					}else if (status=="2") {
						//包含的人员
						var $con = $("#newGroup-form #staff_area_sel");
						if($con.find("button[staffid='"+member.objid+"']").length>0) return; 	
						var tags='<button onClick="$(this).remove()" style="margin-left: 5px;margin-top: 5px;"  staffid="'+member.objid+'" class="btn default">'+member.objname+'</button>';
						$con.append(tags);
					};
				};
				$("#newGroup-form #logo").attr("src",group.url).attr('fileid',group.logo);

			}else{
			 	form.find(".alert-success").show().find("label").html("正在群组数据异常："+json.msg);
			}
		});
		
}

Group._new = function(){
	
	$("#newGroup-dlg").clone(true).removeClass('hide').attr("id","newGroup-form").insertBefore($(".page-bar"));
}

Group.save = function(){


	var form = $("#newGroup-form");
	
	var groupName = form.find("#group_name").val();
	var groupDesc = form.find("#group_desc").val();
	var limit = form.find("#limit").val();

	var deptEl = $("#dept_area_sel");
	var staffEl = $("#staff_area_sel");
	var logo = $("#newGroup-form #logo").attr("fileid");

	var deptid = $.trim($("#dept_area_sel").attr("deptid"));
	deptid = deptid==""? [] : [deptid];
	var staffid = [];

	$.each($("#staff_area_sel button"),function(){

  		staffid.push($(this).attr("staffid"));

	});

	var groupid = $("#newGroup-form").attr("groupid");
	if (!groupid) {groupid="";};
	
	var params = {
		deptid:deptid,
		groupdesc:encodeURIComponent(groupDesc),
		group_logo:logo,
		groupname:encodeURIComponent(groupName),
		groupid:groupid,
		logo:logo,
		max_number:limit,
		allow_jid:staffid
	};
	var url = Index.server+"&jsoncallback=?&module=group&action=editGroup&params="+jsonToStr(params);

	form.find(".alert-danger").hide();
	form.find("#btn_save,#btn_cancel").attr("disabled",true);
	$("html,body").animate({scrollTop:form.offset().top-100},200);

	if (!validate(params)) {return;};

	form.find(".alert-success").show().find("label").html("正在保存数据....");
	$.getJSON(url,function(json){
		if(json.returncode=="0000"){
			form.find(".alert-success").show().find("label").html("群组创建成功");
			form.remove();
			Group.query();
		}
		else
		{
			form.find(".alert-success").show().find("label").html("保存数据异常："+json.msg);
		}
	});

}

Group.addStaffList=function(evt){
	var add_staff=$("#add_staff");
	var account = $.trim(add_staff.val());
	if(account=="")
	{
		add_staff.focus();
		return;
	}
	$(evt).attr("disabled",true);
	var url = Index.server+'&module=ApiHR&action=staff_search&jsoncallback=?';
	$.getJSON(url,{"search":account}, function(json) {	
		$(evt).attr("disabled",false);		
			if(json.returncode=="0000")
		   	{
		   		if(json.data.staffs.length==0)
		   		{
		   			add_staff.focus();
		   			add_staff.val().attr("placeholder","未查找到人员，请重新输入");
		   			return;
		   		}
		   		var staff = json.data.staffs[0];
				var staff_id = staff.jid;
				var staff_name = staff.nick_name;
				var $con = $("#newGroup-form #staff_area_sel");
				if($con.find("button[staffid='"+staff_id+"']").length>0) return; 
					
				var tags='<button onClick="$(this).remove()" style="margin-left: 5px;margin-top: 5px;"  staffid="'+staff_id+'" class="btn default">'+staff_name+'</button>';
				$con.append(tags);	
				add_staff.focus();
		   		add_staff.val("");			
			 }else{
			 	add_staff.focus();
		   		add_staff.val().attr("placeholder","未查找到人员，请重新输入");
		   		return;
		 	}
	});
}

Group.pagination = function(count){
		var opt = {
			callback: function(page_index,jq){
		
				Group.page_index = page_index+1;
				Group.query();
			},//点击标签后的反应
			items_per_page:Group.limit,//每页显示的条数
			num_display_entries:6,//显示几个页的标签
			num_edge_entries:2,//超出部分显示几个页
			prev_text:'上一页',
			next_text:'下一页'
			};
	
		$("#Pagination").pagination(count, opt);
	};



//=============部门树================
Group.tree=function($treeEle)
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
			    		onClick:Group.TreeClick,
			    		beforeExpand:Group.TreeExpand
			    	}
	};
	var url = Index.server+'&module=ApiHR&action=org_query&jsoncallback=?';
	Group.dataurl = url;
    $.getJSON(url, {"deptid":""}, function(json) {
        var jsondata=[];
    	for (var i = 0; i < json.data.length; i++) {
    		jsondata[i] = {"id":json.data[i]["id"],"pid":json.data[i]["parent"],"name":json.data[i]["text"],"open":json.data[i]["parent"]=="-10000"?true:false,"isParent":json.data[i]["children"]>0?true:false};
    	};     	
        $.fn.zTree.init($treeEle, zTreeSetting, jsondata);   
        //$.fn.zTree.init($("#filter_depttree"), zTreeSetting, jsondata);
		Group.treeObj = $.fn.zTree.getZTreeObj($treeEle.attr("id"));
    });
}
Group.TreeExpand=function( treeId, treeNode)
{
	Group.TreeClick(null,treeId, treeNode);
	return true;
}

Group.TreeClick=function(event, treeId, treeNode)
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
            $.getJSON(Group.dataurl,parameter,function(json) {               
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
                        Group.treeObj.addNodes(treeNode,jsondata);
                    }
                }
            });
        }
        $("#"+Group.treeObj.setting.treeId).slimScroll({"height":"200px"});  
}

function avatar_success()
{
    var parameter = {"module":"staff","action":"save_Photo","params":{"login_account":"" }};
    $.getJSON(Index.server+"&jsoncallback=?",parameter,function(returndata){
            if (returndata.success)
            {
            	$logo = $("#newGroup-form #logo");
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

Group.uploadfile=function(evn)
{
        $("#uplod_loading").attr("src","images/loader.gif");
        $("#uplod_loading").show();
        $(".upload_hint").text("正在上传，请稍候……");
        Group.uploadObj[0].doSave();
};

Group.window_close=function()
{
        $('.selected_pic_box').modal('hide');
        $("#uplod_loading").hide();
        $(".upload_hint").text("");
};


function jsonToStr(params){
	var str = "{";
	var isFirst = true;
	for(var o in params){
		if (isFirst) {
			isFirst = false;
		}else{
			str+=",";
		}
		if (params[o] instanceof Array && params[o].length>0) {
			var aray = params[o];
			str+="\""+o+"\"";
			str+=":[";
			var first = true;
			for(var i=0;i<aray.length;i++){
				if (first) {
					first = false;
				}else{
					str+=",";
				}

				str+="\""+aray[i]+"\"";
			}
			str+="]";
			
		}else if (!params[o]) {
			params[o]="";
			str+="\""+o+"\"";
			str+=":";
			str+="\""+params[o]+"\"";
		}else{
			str+="\""+o+"\"";
			str+=":";
			str+="\""+params[o]+"\"";
		}
		

	}

	str+="}";

	return str;
}

function validate(params){
	var $form = $("#newGroup-form");
	
	if (!params) {
		$form.find(".alert-danger").show().find("label").html("未填数据！");
		return false;
	};

	if (!params.groupname) {
		$form.find(".alert-danger").show().find("label").html("未填群组名称！");
		return false;
	};

	if (!params.max_number) {
		$form.find(".alert-danger").show().find("label").html("未填群组人员上限！");
		return false;
	};

	if (params.deptid.length<1&&params.allow_jid.length<1) {
		$form.find(".alert-danger").show().find("label").html("至少选择一个部门或一个人员！");
		return false;
	};

	return true;

};
