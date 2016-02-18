var staffManage = {
	search_url:"",
	udpate_url:"",
	groupid:"",
	search_url_byid:"",
  search:function(){
  	var paramter = { "status":$(".staffinfo_search .mb_combox").val() };
  	$.post(this.search_url,paramter,function(calldata){
  		 if ( calldata.success)
  		   staffManage.fullData(calldata.data);
  		 else{
  		 	 var html = "<div class='mb_table_tr' style='text-align:center;color:#cc3300;'>" + calldata.message + "</div>";
  		 	 $(".mb_table .mb_table_content").html(html);
  		 }
  	});
  },
  update:function(ev){
  	var state = $(ev).val();
  	if (state=="0") return;
  	var groupid = $(ev).parents(".mb_table_tr").attr("groupid");
  	$.post(this.update_url,{ "groupid":groupid },function(calldata){
  		if ( calldata.success){
  			$(ev).parent().html("<span class='staffinfo_statusLable'>已处理</span>");
  		}
  		else{
  		 	 var html = "<div class='mb_table_tr' style='text-align:center;color:#cc3300;'>" + calldata.message + "</div>";
  		 	 $(".mb_table .mb_table_content").html(html);
  		}
  	});
  },
  view:function(ev){
  	var id = $(ev).parents(".mb_table_tr").attr("groupid");
  	var name = $(ev).parents(".mb_table_tr").find(".name").text();
  	var sapid_num = $(ev).parents(".mb_table_tr").find(".sapid_num").text();
  	$("#staffname").text(name);
  	$("#staffsapid").text(sapid_num);
  	$("#viewMessage").show();
  	if ( id==this.groupid) return;
  	$(".content .mb_table_content").html("");
  	$.post(this.search_url_byid,{"groupid":id},function(calldata){
  		 staffManage.groupid = id;
  		 var html = new Array();
  		 if ( calldata.success){
  		 	 var data = calldata.data;
  		 	 if ( data!=null && data.length>0){
	  		 	 for(var i=0;i< data.length;i++){
	  		 	 	 html.push("<div class='mb_table_tr'>");
	  		 	 	 html.push("<span style='width:40%;' class='mb_rightLing'>"+data[i].field_t+"</span>");
	  		 	 	 html.push("<span style='width:60%;border-right:none;padding-left:20px;text-align:left;' class='mb_rightLing'>"+data[i].val+"</span>");
	  		 	 	 html.push("</div>");
	  		 	 }
  		   }
  		   else{
  		   	
  		   }
  		   $(".content .mb_table_content").html(html.join(""));
  		 }
  		 else{
  		 }
  	});
  	
 
  	
  },
	fullData:function(data){
		var html=new Array();
		if ( data!= null && data.length>0){
			for(var i=0;i<data.length;i++){
				var row = data[i];				
				html.push("<div class='mb_table_tr' groupid='" + row["groupid"] + "'>");
				html.push("<span style='width:22%;' class='mb_rightLing'>"+row["date"]+"</span>");
				html.push("<span style='width:20%' class='mb_rightLing name'>"+row["nickname"]+"</span>");
				html.push("<span style='width:22%;' class='mb_rightLing sapid_num'>"+row["sapid_num"]+"</span>");
				var content = "<img src='/bundles/fafatimembapp/images/view.png' title='查看修改信息' onclick='staffManage.view(this);' class='staffinfo_sh_img'>";
			  var head = row["head"];
			  if ( head!= null && head!="")
			     content += "<a href='"+head+"'><img src='/bundles/fafatimembapp/images/head.png' title='头像下载' class='staffinfo_sh_img'></a>";
				html.push("<span style='width:20%;' class='mb_rightLing'>"+content+"</span>");
				var state = row["status"];
				var html2 = "";
				if ( state=="0"){
					html2 = "<select class='mb_combox staffinfo_combox' onchange='staffManage.update(this);'><option value=0>代办</option><option value=1>已处理</option></select>";
				}
				else{
					html2="<span class='staffinfo_statusLable'>已处理</span>";
				}
				html.push("<span class='mb_rightLing staffinfo_status'>"+html2+"</span>");
				html.push("</div>");
			}
		}
		else{
			 html.push("<div class='mb_table_tr' style='text-align:center;color:#cc3300;'>未查询到符合条件的数据记录</div>");
		}
		$(".staffinfo_area .mb_table_content").html(html.join(""));
	}
};