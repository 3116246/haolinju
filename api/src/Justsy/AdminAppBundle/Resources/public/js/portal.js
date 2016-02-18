var portal = {
	identical_url:"",
	mallid:"",
	record:14,
	issearch:false,
  pageselectCallback:function(pageindex){
		 if (portal.issearch )
		   portal.Search(pageindex + 1);
	},
	pageInit:function(){
 	  var opt = {callback: portal.pageselectCallback};
    opt.items_per_page = portal.record;
    opt.num_display_entries = 3;
    opt.num_edge_entries = 3;
    opt.prev_text="上一页";
    opt.next_text="下一页";
    return opt;
	},	
	Search:function(pageindex)
	{
		 var para = {"pageindex":pageindex,"ename":$.trim($("#textename").val()),"appname":$.trim($("#textappname").val()),"record":this.record };
		 var parameter = { "module":"app","action":"SearchPortal","params":para }
		 $.post(this.identical_url,parameter,function(returndata){
				if ( pageindex==1 ){
		 	 	  	if ( returndata.recordcount <= portal.record){
		 	 	  		$(".pagestyle").hide();
		 	 	  	}
		 	 	  	else{
			 	 	  	portal.issearch = false;
			 	 	  	var optInit = portal.pageInit();
			 	 	  	$(".pagestyle").show();
			 	 	  	$(".pagestyle").pagination(returndata.recordcount,optInit);
			 	 	  	portal.issearch = true;
		 	 	    }
	 	 	   }
	 	 	   else{
	 	 	   	 $(".pagestyle").show();
	 	 	   	 portal.issearch = true;
	 	 	   }
	 	 	   portal.fulltable(returndata.list,returndata.portal_state,returndata.appid);
		 });
	},
	fulltable:function(data,portal_state,appid)
	{
		var html = Array();
		if (data.length==0)
		{
			 html.push("<span style='border-bottom:1px solid #ccc;height:32px;line-height:30px;' class='mb_common_table_empty'>未查询到数据记录！</span>");
		}
		else
		{
			var row = null;
			for(var i=0;i< data.length;i++)
			{
				row = data[i];
				if (i+1 == data.length)
				  html.push("<tr mallid='"+row.mallid+"' style='border-bottom:none;'>");
				else
					html.push("<tr mallid='"+row.mallid+"'>");
				html.push("<td width='200' style='padding-left:5px;'>"+row.ename+"</td>");
				html.push("<td width='120' align='center'>"+row.portalname+"</td>");
				html.push("<td width='70' align='center'>"+row.version+"</td>");
				html.push("<td width='132' align='center'>"+row.date+"</td>");
				html.push("<td width='316' style='padding-left:5px;'>"+row.desc+"</td>");
				if ( row.preview=="1")
				  html.push("<td width='70' align='center'><span onclick='portal.viewImage(this);' title='查看效果预览图' class='glyphicon glyphicon-picture portal_img'></span></td>");
				else
					html.push("<td width='70' align='center'>&nbsp;</td>");
			  //是否允许订阅
			  if ( row.is_portal=="1" && appid!="" )
			  {
			  	if ( portal_state =="2" && appid==row.appid)
				  {
				  	html.push("<td width='90' state='1' class='subscribe' style='border-right:none;height:100%;' align='center'><button title='您已订阅，请升级到新版本。' style='border:none;line-height:22px;' class='label bg-primary portal_button' onclick='portal.subscribe(this);'>升级版本</button></td>");
				  }
				  else if (portal_state=="1" && appid==row.appid )
				  {
				    html.push("<td width='90' class='subscribe' style='border-right:none;height:100%;' align='center'>已订阅</td>");
				  }
				  else
				  {
				  	html.push("<td width='90' class='subscribe' style='border-right:none;height:100%;' align='center'>&nbsp;</td>");
				  }
				}
			  else
			  {
			  	html.push("<td width='90' state='0' class='subscribe' style='border-right:none;height:100%;' align='center'><button class='btnGreen portal_button' onclick='portal.subscribe(this);' style=''>马上订阅</button></td>");
			  }
			}
		}
		$(".mb_common_table tbody").html(html.join(""));
	},
	viewImage:function(evn)
	{
		$("#viewImage").show();
	},
	subscribe:function(evn)
	{
		 var ctl = $(evn);
		 var state = ctl.parent().attr("state");
		 if ( state=="0")
		   ctl.text("订阅中...");
		 else
		 	 ctl.text("升级中...");
		 ctl.attr("disabled","disabled");
		 var mallid = $(evn).parents("tr").attr("mallid");
		 var para = { "mallid":mallid };
		 var parameter = { "module":"app","action":"portal_subscribe","params":para }
		 $.post(this.identical_url,parameter,function(returndata){
				if ( returndata.success){
					$(".subscribe").html("&nbsp;"); 
					$(".mb_common_table tr[mallid='"+mallid+"']").find(".subscribe").html("<span>"+(state=="1"?"已升级":"已订阅")+"</span>");
					$("#prompt").show();
					$("#prompt #hint_content").text(state=="1"?"升级成功！":"订阅成功！");
			  }
			  else
		  	{
		  		$("#prompt #hint_content").text(state=="1"?"升级失败！":"订阅失败！");
		  	}
		 });
  }
};