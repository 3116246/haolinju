var mobile_client = {
	search_url:"",
	execute_url:"",
	issearch:false,
	record:14,
	search_state:true,
	fafa_jid:"",
	login_account:"",
	
  pageselectCallback:function(pageindex){
		 if (mobile_client.issearch )
		   mobile_client.Search(pageindex+1);
	},
	pageInit:function(){
 	  var opt = {callback: mobile_client.pageselectCallback};
    opt.items_per_page = mobile_client.record;
    opt.num_display_entries = 3;
    opt.num_edge_entries = 3;
    opt.prev_text="上一页";
    opt.next_text="下一页";
    return opt;
	},
	Search:function(pageindex){
		if ( !this.search_state) return;
     this.search_state = false;
     var parameter = { "staff":$.trim($("#text_staff").val()),"pageindex":pageindex,"record":this.record };
	 	 $.post(this.search_url,parameter,function(returndata){
	 	 	  mobile_client.search_state = true;
	 	 	  if (returndata.success){
		 	 	   if ( pageindex==1 ){
			 	 	  	if ( returndata.recordcount <= mobile_client.record){
			 	 	  		$(".pagestyle").hide();
			 	 	  	}
			 	 	  	else{
				 	 	  	mobile_client.issearch = false;
				 	 	  	var optInit = mobile_client.pageInit();
				 	 	  	$(".pagestyle").show();
				 	 	  	$(".pagestyle").pagination(returndata.recordcount,optInit);
				 	 	  	mobile_client.issearch = true;
			 	 	    }
		 	 	   }
		 	 	   else{
		 	 	   	mobile_client.issearch = true;
		 	 	   }
		 	 	   mobile_client.fulldata(returndata.datasource);
	 	 	  }
	 	 	  else{
	 	 	  }
	 	 });
	},
	fulldata:function(data){		 
	   var html = new Array();
  	 if ( data != null && data.length>0){
 	 	  	var row = null;
	 	 	  for(var i=0;i<data.length;i++){
	 	 	  	row = data[i];
	 	 	  	html.push("<tr>");
	 	 	  	html.push(" <td width='248' align='left'>"+row.login_account+"</td>");
  		    html.push(" <td width='248' align='left'>"+row.staff+"</td>");
  		    html.push(" <td width='103' align='center'><span style='margin-left:40px;margin-top:7px;' staff='"+row.staff + "' login_account='" +  row.login_account+"' fafa_jid='"+row.fafa_jid+"' onclick='mobile_client.show_setting(this)' title='设置用户称动端' class='mb_role_button'></span></td>");
  		    html.push("</tr>");
 	 	   }
		 }
	  else {
	  	html.push("<span class='mb_common_table_empty'>未查询到数据记录</span>");
		}
		$(".mb_common_table tbody").html(html.join(""));
	},
	show_setting:function(ev){
		mobile_client.fafa_jid = $(ev).attr("fafa_jid");
		mobile_client.login_account = $(ev).attr("login_account");
		var staff = $(ev).attr("staff");
		$("#staffname").html("<span style='color:red;padding-left:5px;padding-right:5px;'>"+staff+"</span>移动设备管理");
		$(".area_right").show();
		$(".area_right>div").show();
		$(".password_area").hide();
	},
	mobile_submit:function(){
		var selectval = $("#mobile_lock").attr("checked");
		var type = "",password="";
		if ( selectval!=null){
			type = "lock";
		}
		selectval = $("#mobile_wipe").attr("checked");
		if ( selectval!=null){
			type = "wipe";
		}
	  selectval = $("#mobile_clearpwd").attr("checked");
		if ( selectval!=null){
			type = "clearPassword";
		}
		selectval = $("#mobile_newpwd").attr("checked");
		if ( selectval!=null){
			type = "adminLock";
			var pwd1 = $("#password1").val();
			var pwd2 = $("#password2").val();
			if ( pwd1==""){
				this.showhint($("#password1"),"请输入用户密码!");
				return;
			}
			else if ( pwd2==""){
				this.showhint($("#password2"),"请输入确认密码!");
				return;
			}
			else if ( pwd1!=pwd2){
				this.showhint($("#password1"),"两次密码不一致，请重新输入!");
				return;
			}
			else if ( pwd1.length<6){
				this.showhint($("#password1"),"密码最小长度必须为6位!");
				return;
			}
			else{
				 password = pwd1;
			}
		}
		if ( type==""){
			this.showhint(null,"请选择操作类型!");
			return;
		}
		var parameter = {"login_account":this.login_account,"fafa_jid":this.fafa_jid,"type":type,"password":password};
		$.post(this.execute_url,parameter,function(data){
			 if ( data.success){
			 	 mobile_client.showhint(null,"消息发送成功！");
			 	 setTimeout(function() {$(".area_right").hide();},1000);
			 }
			 else{
			 	 if (data.msg!=""){
			 	 	 mobile_client.showhint(null,data.msg);
			 	 }
			 	 else{
			 	 	 mobile_client.showhint(null,"消息发送失败！");
			 	 }
			 }
		});	
	},
	showhint:function(control,message){
		$(".message_box").text(message);
		if ( control != null ) control.focus();
		setTimeout(function(){
			$(".message_box").text("");
		},2000);
	},
	selectype:function(ev){
		var id = $(ev).attr("id");
		if ( id=="mobile_newpwd"){
			 $(".password_area").show();
			 $("#password1,#password2").val("");
			 $("#password1").focus();
		}
		else{
			$(".password_area").hide();
		}		
	}
};

