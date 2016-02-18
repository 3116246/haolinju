
var Attendance = Attendance||{};
// Index.server = "http://localhost/api/http/exec_dataaccess?openid=chenjd@justsy.com" ;

Attendance.deptid = "";
Attendance.ymd = "";
Attendance.init = function(args) {
	Attendance.page_index = 1;
	Attendance.limit = 200;
	Attendance.count = 0;
}

//按日期查询考勤
Attendance.queryAllAttenByDate = function(args){
	Attendance.init();

	var params = {
		ymd:Attendance.ymd,
		type:'allAttenByDate',
		deptid:Attendance.deptid
	}
	Attendance.queryAtten(params);
}

//查看应考勤人（已考勤，未考勤）
Attendance.queryAllByDate = function(args){

	Attendance.init();

	var params = {
		ymd:Attendance.ymd,
		type:'allByDate',
		deptid:Attendance.deptid
	}
	Attendance.queryAtten(params);
}
//查询当天所有迟到的人
Attendance.queryAllLate = function(args){
	Attendance.init();

	var params = {
		ymd:Attendance.ymd,
		type:'allLate',
		deptid:Attendance.deptid
	}
	Attendance.queryAtten(params);
}
//查询当天未考勤的人
Attendance.queryNoAtten = function(args){
	Attendance.init();

	var params = {
		ymd:Attendance.ymd,
		type:'noAtten',
		deptid:Attendance.deptid
	}
	Attendance.queryAtten(params);
}

//查询考勤
Attendance.queryAtten = function(params){

	var dataurl = Index.server+'&module=hrAttendance&action=getAllAtten&jsoncallback=?';
	
	params.limit = Attendance.limit;
	params.pageIndex = Attendance.page_index;
	$("#list").hide();
	$("#detal_list").show();
	$('#atten-list tbody').html(Index.loadDataHtml);
	$.getJSON(dataurl,{'params':params},function(json){
		var html = template('atten-list-tmpl',json);
		$('#atten-list tbody').html(html);		
	});
	/*
	$.getJSON(counturl,{'params':params},function(json){
		if (json&&parseInt(json.data)!=Attendance.count) {
			Attendance.count = parseInt(json.data);
			Attendance.pagination();
			
		};
	});*/
}

//统计：应考勤，实考勤，迟到，未考勤等人数
Attendance.queryAllCount = function(params){
	
	Attendance.staff_count = 0;
	Attendance.atten_count = 0;
	Attendance.late_count = 0;
	Attendance.no_atten_count = 0;

	var counturl = Index.server+'&module=hrAttendance&action=getCount&jsoncallback=?';
	$.getJSON(counturl,{
		'params':{ymd:Attendance.ymd,type:'allByDate',deptid:Attendance.deptid}
	},function(json){
		if (json&&json.data!=Attendance.staff_count) {
			Attendance.staff_count = json.data;
		};
		$("#allStaffCount").html(Attendance.staff_count);
	});
	
	$.getJSON(counturl,{
		'params':{ymd:Attendance.ymd,type:'allAttenByDate',deptid:Attendance.deptid}
	},function(json){
		if (json&&json.data!=Attendance.atten_count) {
			Attendance.atten_count = json.data;
		};
		$("#allAttenCount").html(Attendance.atten_count);
	});
	
	$.getJSON(counturl,{
		'params':{ymd:Attendance.ymd,type:'allLate',deptid:Attendance.deptid}
	},function(json){
		if (json&&json.data!=Attendance.late_count) {
			Attendance.late_count = json.data;
		};
		$("#allLateCount").html(Attendance.late_count);
	});
	
	$.getJSON(counturl,{
		'params':{ymd:Attendance.ymd,type:'noAtten',deptid:Attendance.deptid}
	},function(json){
		if (json&&json.data!=Attendance.no_atten_count) {
			Attendance.no_atten_count = json.data;
		};
		$("#allNoAttenCount").html(Attendance.no_atten_count);
	});
}