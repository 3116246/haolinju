
var Attendance = Attendance||{};


Attendance.deptid = "";
Attendance.page_index = 1;
Attendance.limit = 10;
Attendance.count = 0;

Attendance.init = function(args) {

	$("#atten-dept").html("全公司");
	var date = new Date();
	$("#atten-date").html(date.getFullYear()+"-"+(date.getMonth()+1)+"-"+date.getDate());

	$("#btn_staff_search").off('click').on('click',function(evt){
		Attendance.searchStaff();
	});
	$("#btn_send_msg").off('click').on('click',function(evt){
		Attendance.sendMsg();
	});
	$("#btn_exp_excel").off('click').on('click',function(evt){
		Attendance.expExcel();
	});
	$("#staff").off('keypress').on('keypress',function(evt){
		var key = evt.which;
		if(key==13)
		{
			$("#btn_staff_search").trigger('click');
		}
	});
}

Attendance.showDeptTree = function(){
	Staff.tree($("#dept-div #depttreeQ"),Attendance.queryByDept);//按部门查询树
	$("#dept-div").css("display","block");
}

//按部门查询
Attendance.queryByDept = function(event, treeId, treeNode){
	Staff.closeDetpTree();
	//判断是否是根节点
	Attendance.deptid = treeNode.id.substring(0,1)=="v"?"":treeNode.id;
	$("#atten-dept").html(treeNode.name);
	$(document).trigger('click');//以关闭弹出的菜单面板
	Attendance.queryAllCount();
	Attendance.queryAllAtten();
}

//按日期查询考勤
Attendance.queryAllAttenByDate = function(args){
	$("#atten-date").html($("#location_date").val());
	Attendance.queryAllCount();
	Attendance.queryAllAtten();
}
//查询实际考勤
Attendance.queryAllAtten = function(args){
	var params = {
		ymd:$("#location_date").val(),
		type:'allAttenByDate',
		deptid:Attendance.deptid
	}
	Attendance.queryAtten(params);
}
//查看应考勤人（已考勤，未考勤）
Attendance.queryAllByDate = function(args){

	var params = {
		ymd:$("#location_date").val(),
		type:'allByDate',
		deptid:Attendance.deptid
	}
	Attendance.queryAtten(params);
}
//查询当天所有迟到的人
Attendance.queryAllLate = function(args){
	var params = {
		ymd:$("#location_date").val(),
		type:'allLate',
		deptid:Attendance.deptid
	}
	Attendance.queryAtten(params);
}
//查询当天未考勤的人
Attendance.queryNoAtten = function(args){

	var params = {
		ymd:$("#location_date").val(),
		type:'noAtten',
		deptid:Attendance.deptid
	}
	Attendance.queryAtten(params);
}

//查询考勤
Attendance.queryAtten = function(params){

	var dataurl = Index.server+'&module=hrAttendance&action=getAllAtten&jsoncallback=?';
	var counturl = Index.server+'&module=hrAttendance&action=getCount&jsoncallback=?';
	
	params.limit = Attendance.limit;
	params.pageIndex = Attendance.page_index;

	$('#atten-list').parent().prepend(Index.loadDataHtml);

	$.getJSON(dataurl,{'params':params},function(json){
		$('#atten-list').parent().find("div").remove();
		var html = template('atten-list-tmpl',json);
		$('#atten-list tbody').html(html);
		FaFaPresence.AddBind(null, null); //自动绑定

	});

	$.getJSON(counturl,{'params':params},function(json){
		if (json&&parseInt(json.data)!=Attendance.count) {
			Attendance.count = parseInt(json.data);
			Attendance.page_index = 1;
			Attendance.pagination(params);
			
		};
	});
}

//统计：应考勤，实考勤，迟到，未考勤等人数
Attendance.queryAllCount = function(params){
	
	Attendance.staff_count = 0;
	Attendance.atten_count = 0;
	Attendance.late_count = 0;
	Attendance.no_atten_count = 0;

	var counturl = Index.server+'&module=hrAttendance&action=getCount&jsoncallback=?';
	$.getJSON(counturl,{
		'params':{ymd:$("#location_date").val(),type:'allByDate',deptid:Attendance.deptid}
	},function(json){
		if (json&&json.data!=Attendance.staff_count) {
			Attendance.staff_count = json.data;
		};
		$("#allStaffCount").html(Attendance.staff_count);
	});
	
	$.getJSON(counturl,{
		'params':{ymd:$("#location_date").val(),type:'allAttenByDate',deptid:Attendance.deptid}
	},function(json){
		if (json&&json.data!=Attendance.atten_count) {
			Attendance.atten_count = json.data;
		};
		$("#allAttenCount").html(Attendance.atten_count);
	});
	
	$.getJSON(counturl,{
		'params':{ymd:$("#location_date").val(),type:'allLate',deptid:Attendance.deptid}
	},function(json){
		if (json&&json.data!=Attendance.late_count) {
			Attendance.late_count = json.data;
		};
		$("#allLateCount").html(Attendance.late_count);
	});
	
	$.getJSON(counturl,{
		'params':{ymd:$("#location_date").val(),type:'noAtten',deptid:Attendance.deptid}
	},function(json){
		if (json&&json.data!=Attendance.no_atten_count) {
			Attendance.no_atten_count = json.data;
		};
		$("#allNoAttenCount").html(Attendance.no_atten_count);
	});
}

Attendance.pagination = function(args){
	var opt = {
	callback: function(page_index,jq){

		Attendance.page_index = page_index+1;
		Attendance.queryAtten(args);
	},//点击标签后的反应
	items_per_page:Attendance.limit,//每页显示的条数
	num_display_entries:3,//显示几个页的标签
	num_edge_entries:1,//超出部分显示几个页
	prev_text:'上一页',
	next_text:'下一页'
	};
	
	$("#atten-pagination").pagination(Attendance.count, opt);
}

//查询人员
Attendance.searchStaff=function()
{
		
	var dataurl = Index.server+'&module=ApiHR&action=staff_query&jsoncallback=?';
   	var account = $("#staff").val();
    
    var params = {
    		limit:15,
    		deptid:'',
    		page_num:1,
    		search:account
    	}
    	
    $("#btn_staff_search").html('查询中...').attr('disabled',true);
    	//获取内容，刷新页面
    $.getJSON(dataurl, params, function(json) {      
      	$("#btn_staff_search").html('搜索并添加').attr('disabled',false);
      	$("#staff").val('');
      	if(json.data.length==0) return;
      	var jid = json.data[0]['fafa_jid'];
      	var $con = $("#send_staff_list");
      	if($con.find("button").length==0)
      	{
      		$("#send_staff_list").html("");
      	}
		if($con.find("button[staff='"+jid.replace('@',"\\@")+"']").length>0) return;
		var img = "";
		if(json.data[0]['photo_path']=='')
		{
			img = '<img style="width: 48px" src="../assets/admin/pages/media/profile/avatar.png" class="img-circle">';
		}
		else
		{
			img = '<img style="width: 48px" onerror="this.src=\'../assets/admin/pages/media/profile/avatar.png\'" src="'+json.data[0]['photo_path']+'" class="img-circle">';
		}
		var tags='<button title="点击可移除" style="margin: 5px" onClick="$(this).remove()" staff="'+jid+'" class="btn default">'+img+'<br>'+json.data[0]['nick_name']+'</button>';
		$con.append(tags);
    });	
}
//发送当前数据给指定人
Attendance.sendMsg=function(){
	var $send_staff_list = $("#send_staff_list"),$buttons = $send_staff_list.find("button");
	var jids = [];
	for (var i = 0; i < $buttons.length; i++) {
		jids.push($($buttons[i]).attr('staff'));
	};	
	if(jids.length==0)
	{
		$send_staff_list.html("请搜索并添加接收人！");
		return;
	}
	$("#btn_send_msg").html('发送数据中...').attr('disabled',true);
	var dataurl = Index.server+'&module=HrAttendance&action=sendStatData&jsoncallback=?';
	var params={'staff':jids};
	params['deptid'] = Attendance.deptid;
	params['ymd'] = $.trim($("#location_date").val());
	params['all'] = $.trim($("#all_atten .details .number").text());
	params['attendanced'] =  $.trim($("#ed_atten .details .number").text());
	params['delay'] =  $.trim($("#delay_atten .details .number").text());
	params['notattendance'] =  $.trim($("#not_atten .details .number").text());
	params['leave'] =  $.trim($("#leave_atten .details .number").text());
	params['travel'] =  $.trim($("#travel_atten .details .number").text());
	$.getJSON(dataurl,{'params':params},function(json){
		$("#btn_send_msg").html('分享发送').attr('disabled',false);
		
		if(json.returncode=="0000")
		{
			$("#page_alert_info").show().find("label").html("考勤数据已成功发送。");
		}
		else
		{
			$("#page_alert_info").show().find("label").html(json.msg);
		}
		setTimeout(function(){$("#page_alert_info").hide()},3000);
		$(document).trigger('click');//以关闭弹出的菜单面板
	});	
}

Attendance.expExcel=function()
{
	$("#btn_exp_excel").html('导出数据中...').attr('disabled',true);
	var dataurl = Index.server+'&module=HrAttendance&action=export&jsoncallback=?';
	var params={};
	params['deptid'] = Attendance.deptid;
	params['ymd'] = $.trim($("#location_date").val());
	params['all'] = $.trim($("#all_atten .details .number").text());
	params['attendanced'] =  $.trim($("#ed_atten .details .number").text());
	params['delay'] =  $.trim($("#delay_atten .details .number").text());
	params['notattendance'] =  $.trim($("#not_atten .details .number").text());
	params['leave'] =  $.trim($("#leave_atten .details .number").text());
	params['travel'] =  $.trim($("#travel_atten .details .number").text());
	$exp_item = $("#exp_excel_panel input:checked");
	for (var i = 0; i < $exp_item.length; i++) {
		params[$($exp_item[i]).attr('value')] = '1';
	};
	$.getJSON(dataurl,{'params':params},function(json){
		$("#btn_exp_excel").html('开始导出').attr('disabled',false);		
		if(json.returncode=="0000")
		{
			$("#page_alert_info").show().find("label").html("数据已成功导出。<a href='"+json.data+"'>下载Excel文件</a>");
		}
		else
		{
			$("#page_alert_info").show().find("label").html(json.msg);
		}
		$(document).trigger('click');//以关闭弹出的菜单面板
	});		
}

//修改考勤设置
Attendance.updateAttenSetup = function(params){
	var dataurl = Index.server+'&module=hrAttendance&action=updateAttenSetup&jsoncallback=?';
	
	$.getJSON(dataurl,{params:params},function(json){
		if (json.returncode="0000") {
			$('.dropdown-menu div').hide();
		}else{
			$("#page_alert_info").show().find("label").html(json.msg);
		}
	})
}

//新增一条考勤设置
Attendance.addAttenSetup = function(params){
	var dataurl = Index.server+'&module=hrAttendance&action=addAttenSetup&jsoncallback=?';
	
	$.getJSON(dataurl,{params:params},function(json){
		if (json.returncode="0000") {
			$("#page_alert_info").show().find("label").html("新增成功！");
		}else{
			$("#page_alert_info").show().find("label").html(json.msg);
		}
	})
}

//根据id获取考勤设置信息
Attendance.getAttenSetup = function(){
	var dataurl = Index.server+'&module=hrAttendance&action=getatten_setup&jsoncallback=?';
	var params = {};
	$.getJSON(dataurl,params,function(json){
		if (json.returncode="0000") {
			var data = json.data[0];

			$("#atten_setup_id").val(data.setup_id);
			$("#atten_setup_worktime").val(data.work_time.substr(0,5));
			$("#atten_setup_latetime").val(data.late_time.substr(0,5));
			$("#atten_setup_offworktime").val(data.offwork_time.substr(0,5));
			
		}else{
			$("#page_alert_info").show().find("label").html(json.msg);
		}
	})
}

Attendance.saveAtten = function(){
	var setupId = $.trim($("#atten_setup_id").val());
	var workTime = $.trim($("#atten_setup_worktime").val());
	var lateTime = $.trim($("#atten_setup_latetime").val());
	var offworkTime = $.trim($("#atten_setup_offworktime").val());

	var params = {
		setupId:setupId,
		workTime:workTime,
		lateTime:lateTime,
		offworkTime:offworkTime
	}

	if (setupId) {
		Attendance.updateAttenSetup(params);
	}else{
		Attendance.addAttenSetup(params);
	}
}


