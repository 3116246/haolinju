

var Location = {};

// Index.server = "http://localhost/api/http/exec_dataaccess?openid=chenjd@justsy.com";

Location.LOCATION_PATH = Index.server+"&module=ApiLocation&jsoncallback=?";
Location.PAGE_INDEX = 1;
Location.TOTAL_COUNT = 0 ;
Location.LIMIT = 5;
Location.cache = {};


//查看位置
Location.search = function(value)
{

	var date = $.trim($("#location_date").val());
	var startdate = date +" "+ $.trim($("#location_sh").val());
	var enddate = date +" "+ $.trim($("#location_eh").val());

	var url = Location.LOCATION_PATH+"&action=query";
	$.getJSON(url,{"staff":value,"startdate":startdate,"enddate":enddate},function(json){
		// setTimeout(function(){search(value)},60*1000);
		if(json["returncode"]=="0000")
		{
			Location.deleteMarker();
			if(json.data.length==0)
			{
				$("#page_alert_info").show().find("label").html("暂时还未上报任何位置信息");
				return;
			}
		    for (var i = 0; i < json.data.length-1; i++) {
		    	var tmpMarker = json.data[i];
		    	var ctime = tmpMarker["ctime"];
		    	ctime = ctime.split(" ")[1];
		    	ctime = ctime.split(":");
		    	Location.addMarker([tmpMarker["y"]*1,tmpMarker["x"]*1],ctime[0]+":"+ctime[1]);
		    	Location.line([json.data[i]["y"],json.data[i]["x"]],[json.data[i+1]["y"],json.data[i+1]["x"]]);
		    };
		    // Location.line([json.data[0]["y"],json.data[0]["x"]],[json.data[i-1]["y"],json.data[i-1]["x"]]);
		    
		    $("#location_user").text(json.data[0].nick_name);
		}
		else
		{
			$("#page_alert_info").show().find("label").html("获取位置信息失败:"+json.msg);
		}

		
	});
}

//用户点击“查看位置”按钮时的方法
Location.search1 = function(){
	var v = $.trim($("#staffvalue").val());
	if (!v) {
		$("#page_alert_info").show().find("label").html("请输入帐号或手机号");
		return;
	}else{
		Location.cache = {};
		$("#page_alert_info").hide();
	};

	Location.search(v);
}

//用户点击列表中的查看位置时的方法
Location.search2 = function(v,name){
	$("#staffvalue").val("");
	
	$("#page_alert_info").hide();
	$("#location_user").text(name);

	Location.cache.staffvalue = v;
	Location.cache.staffname = name;

	Location.search(v);
}

//用户点击日期旁的查看按钮是的
Location.search3 = function(){

	var v1 = Location.cache.staffvalue;

	if (v1) {
		Location.search2(v1,Location.cache.staffname);
	}else  {
		Location.search1();
	}

}

Location.checkLoadMap = function()
{
	setTimeout(function(){
		if(AMap!=null) Location.init();
		else Location.checkLoadMap();
	},100);
}

Location.init = function()
{
	Index.search.config(null);
	map = new AMap.Map('container', {
        resizeEnable: true,
        zoom:11
    });
    Location.queryList();

    var cur = new Date();
    $("#location_date").val((cur.getFullYear())+"-"+(cur.getMonth()+1)+"-"+cur.getDate());
    $("#location_sh").val("9:00");
    $("#location_eh").val("18:00");
}

//获取被监控人的列表
Location.queryList = function(){

	var dataurl = Location.LOCATION_PATH+"&action=monitorlist";
	var params = {
		limit:Location.LIMIT,
		page_index:Location.PAGE_INDEX
	}
	
	$.getJSON(dataurl,params, function(json) {
      var html = template('locationlist-tmpl', json);
			$('#locationlist tbody').html(html);
			FaFaPresence.AddBind(null, null); //在线状态感知。自动绑定
    });	

    //获取总记录数，刷新分页标签
    var counturl = Location.LOCATION_PATH+"&action=monitorcount";
    $.getJSON(counturl, function(json) {
    	var count = parseInt(json.data);
    	
    	if(Location.TOTAL_COUNT!=json.data){
    		Location.TOTAL_COUNT = json.data;
    		Location.pagination();
    	}
				
    });	
}

Location.line = function(s1,s2)
{
	var walking = new AMap.Walking();
	 // Location.line([json.data[0]["y"],json.data[0]["x"]],[json.data[i-1]["y"],json.data[i-1]["x"]]);
 	walking.search(new AMap.LngLat(s1[0]*1,s1[1]*1), new AMap.LngLat(s2[0]*1,s2[1]*1), function(status, result){
		if(status === 'complete'){
			(new Lib.AMap.WalkingRender()).autoRender({
				data: result,
                map: map,
                panel: "line_panel"
			});
        }
    });
}

// 实例化点标记
Location.addMarker = function(xy,txt) {
        var t_marker = new AMap.Marker({
            icon: "http://webapi.amap.com/theme/v1.3/markers/n/mark_b.png",
            position: xy
        });
        t_marker.setMap(map);
        Location.updateMarker(t_marker,txt);
        marker.push(t_marker);
}
Location.updateMarker = function(t_marker,clock) {
        // 自定义点标记内容
        var markerContent = document.createElement("div");

        // 点标记中的图标
        var markerImg = document.createElement("img");
        markerImg.className = "markerlnglat";
        markerImg.src = "http://webapi.amap.com/theme/v1.3/markers/n/mark_r.png";
        markerContent.appendChild(markerImg);

        // 点标记中的文本
        var markerSpan = document.createElement("span");
        markerSpan.className = 'marker';
        markerSpan.innerHTML = clock;
        markerContent.appendChild(markerSpan);

        t_marker.setContent(markerContent); //更新点标记内容
        //t_marker.setPosition([116.391467, 39.927761]); //更新点标记位置
}

Location.deleteMarker = function()
{
	  /*if (marker.length>0) {
	  		for (var i = 0; i < marker.length; i++) {
	  			marker[i].setMap(null);
	  			marker[i]=null;
	  		};
	  		marker = [];
        }*/
    map.remove(marker);
    marker=[];
}

var obj = "";
//开始收集地理位置
Location.startCollect = function(e,login_account){
	var dataurl = Location.LOCATION_PATH+"&action=startCollect";
	obj = $(e);

	var params = {
		to:login_account,
		speed:300//默认5分钟采集一次
	}
	$.getJSON(dataurl,params,function(json){
		if (json.returncode=="0000") {
			var html = "<a class=\"label label-sm label-success\" style=\"color:red;\" href=\"javascript:;\" onclick=\"stopCollect(this,'"
				+login_account+"')\">"
				+"停止 </a>";
			obj.replaceWith(html);

		};
	});
	
		
}

//停止收集地理位置
Location.stopCollect = function(e,login_account){
	var dataurl = Location.LOCATION_PATH+"&action=stopCollect";
	obj = $(e);
	
	var params = {
		to:login_account
	}
	$.getJSON(dataurl,params,function(json){
		
		if (json.returncode=="0000") {
			
			
			var html = "<a class=\"label label-sm label-success\" style=\"color:green;\" href=\"javascript:;\" onclick=\"startCollect(this,'"
				+login_account+"')\">"
				+"开始 </a>";
			obj.replaceWith(html);

		};
		
	});


}

//再输入账号或手机号后，点击开始获取位置
Location.startCollect_f = function(){
	$("#page_alert_info").hide();

	var $staffvalue = $.trim($("#staffvalue").val());
		if($staffvalue=="")
		{
			$("#page_alert_info").show().find("label").html("请输入帐号或手机号");
			$("#staffvalue").focus();
			return;
		}
		$("#page_alert_info").show().find("label").html("正在发送位置查看指令");
		var url = Index.path+'chenjd@justsy.com&module=Api&action=getstaffcard&jsoncallback=?';
		$.getJSON(url,{"staff":$staffvalue},function(json){
			if(json.returncode=="0000")
			{
				$("#location_user").text(json.staff_full.nick_name);
				
				var url = serverurl+'&module=ApiLocation&action=startCollect&jsoncallback=?';
				$.getJSON(url, {"to": json.staff_full.login_account}, function(json) {
					$("#page_alert_info").show().find("label").html("位置查看指令已发送，请等待位置信息收集");

					// append(json.staff_full);
					if(json.returncode=="0000")
					Location.queryList();

					setTimeout(function(){$("#page_alert_info").hide()},3000);
				});

			}
			else
			{
				$("#page_alert_info").show().find("label").html("输入帐号或手机号不正确");
			}
		});
	return;
	//startCollect(login_account,append);
}

//分页
Location.pagination = function(){
	var opt = {
		callback: function(page_index,jq){
	
			Location.PAGE_INDEX = page_index+1;
			Location.queryList();
		},//点击标签后的反应
		items_per_page:Location.LIMIT,//每页显示的条数
		num_display_entries:6,//显示几个页的标签
		num_edge_entries:2,//超出部分显示几个页
		prev_text:'上一页',
		next_text:'下一页'
		};

	$("#Pagination").pagination(Location.TOTAL_COUNT, opt);
};

//删除监控
Location.deleteMonitor = function(e,value){

	bootbox.confirm("你确定要删除这条数据吗?", function(result) {
        if(result){
            

            var url = Location.LOCATION_PATH+"&action=removeMonitor";
			$.getJSON(url,{"to":value},function(json){
				if (json.returncode=="0000") {
					$(el).parent().parent().remove();
					$("#page_alert_info").show().find("label").html("删除成功！");
				}else
				{
					$("#page_alert_info").show().find("label").html("删除失败，请稍后操作！");
					
				}
			});
        }
    });

	
}


