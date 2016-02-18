var syslog = {
	search_url:"",
	issearch:false,
	record:15,
	search_state:true,
	mindate:"",
	maxdate:"",
	init:function(data){
		if (data.length>0){
			var html = Array();
			html.push("<option value=''></option>");
			for(i=0;i<data.length;i++)
			{
				html.push("<option value='"+data[i].type+"'>"+data[i].type+"</option>");		 				 
			}
			$("#combox_type").html(html.join(""));
		}
	},
  sel_start:function(){
	 	 var date = new Date();
	 	 var setting = {
	 	 	               "skin":"whyGreen",
	 	 	               "minDate":this.mindate,
	 	 	               "maxDate":this.maxdate,
	 	 	               "dateFmt":"yyyy-MM-dd"
	 	               };
	 	 WdatePicker(setting);
	},
  sel_end:function(){
	 	 var date = new Date();
	 	 var setting = {
	 	 	               "skin":"whyGreen",
	 	 	               "minDate":this.mindate,
	 	 	               "maxDate":this.maxdate,
	 	 	               "dateFmt":"yyyy-MM-dd"
	 	               };
	 	 WdatePicker(setting);
	},	
  pageselectCallback:function(page_index){
	 	 if ( syslog.issearch )
	 	   syslog.SearchData(page_index+1);
	},
	pageInit:function(){
	 	  var opt = {callback: syslog.pageselectCallback};
      opt.items_per_page = syslog.record;
      opt.num_display_entries = 5;
      opt.num_edge_entries=5;
      opt.prev_text="上一页";
      opt.next_text="下一页";
      return opt;
	},
	SearchData:function(pageindex){
	 	 if ( !this.search_state) return;
     this.search_state = false;          
     var parameter = { "startdate":$("#input_start").val(),"enddate":$("#input_end").val(),"staff":$("#input_staff").val(),"type":$("#combox_type").val(),"pageindex":pageindex,"record":syslog.record };
	 	 $.post(this.search_url,parameter,function(returndata){
	 	 	  syslog.search_state = true;
	 	 	  if ( pageindex==1 ){
	 	 	  	if ( returndata.recordcount <= syslog.record){
	 	 	  		$(".pagestyle").hide();
	 	 	  	}
	 	 	  	else{
		 	 	  	syslog.issearch = false;
		 	 	  	var optInit = syslog.pageInit();
		 	 	  	$(".pagestyle").show();
		 	 	  	$(".pagestyle").pagination(returndata.recordcount,optInit);
		 	 	  	syslog.issearch = true;
	 	 	    }	 	 	  	
	 	 	  }
	 	 	  else{
	 	 	  	syslog.issearch = true;
	 	 	  }
	 	 	  var data = returndata.datasource;
	 	 	  var html = new Array();
	 	 	  if ( data != null && data.length>0){
	 	 	  	var row = null;
	 	 	  	var styles = "";
		 	 	  for(var i=0;i<data.length;i++){
		 	 	  	row = data[i];
		 	 	  	html.push("<tr id='"+row.logid+"'>");
		 	 	  	html.push(" <td align='center' width='135'>"+row.date+"</td>");
		 	 	  	html.push(" <td align='center' width='80'>"+row.type+"</td>");		 	 	  	
		 	 	  	html.push(" <td align='left' width='608'><span class='syslog_content'>"+row.description+"</span></td>");
		 	 	  	html.push(" <td align='center' width='90'>"+row.work_num+"</td>");
		 	 	  	html.push(" <td align='center' width='85' style='border-right:none;'>"+row.nick_name+"</td>");
		 	 	  	html.push("</tr>");
		 	 	  }
	 	 	  }
	 	 	  else{
	 	 	  	html.push("<span class='mb_common_table_empty'>未查询到发布数据记录</span>");
	 	 	  }
	 	 	  $(".mb_common_table tbody").html(html.join(""));
	 	 });
	}
};
