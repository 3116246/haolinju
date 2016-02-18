var parameter = {
	save_url:"",
	view_url:"",
	toggle:function(ev){
	  var id = $(ev).attr("parameter_type");
	  $(".mb_menu_area .mb_menu_active").attr("class","mb_menu");
	  $(ev).attr("class","mb_menu_active");
	  $(".parameter_content>div").hide();
	  $(".parameter_content [para_type='"+id+"']").show();
	},
	Save:function(type){
		var data = Array();
		var self,org;
		if (type==1){
			var number = $.trim($("#text_number1").val());
			if ( number==""){
				this.showhint("请输入合同到期提前数量");
				$("#text_number1").focus();
				return;
			}
			var unit = $("#combox_unit1").val();
			if ( unit == "") {
				this.showhint("请输入合同到期提前计量单位");
				$("#combox_unit1").focus();
				return;				
			}
			self = $("#check_self1").attr("checked");
			org = $("#check_org1").attr("checked");
			self = self==null ? 0 : 1;
			org = org==null ? 0 : 1;			
			if ( self == 0 && org == 0){
				this.showhint($("#hint1"),"请选择合同到期消息接收对象！");
				return;
			}
			var sendmessage = $.trim($("#text_sendmessage1").val());
			if ( sendmessage==""){
				this.showhint($("#hint1"),"请输入合同到期提醒消息！");
				return;
			}
			var sendtype = "";
			var control = $("#sendtype1 input");
			for(var i=0;i<control.length;i++){
				if ( control.eq(i).attr("checked")!=null){
		    	sendtype += control.eq(i).attr("value")+",";
		    }
			}
			if ( sendtype==""){
				this.showhint($("#hint2"),"接选择消息提醒方式！");
				return;
			}
		  data.push({ "type":1,"number":number,"unit":unit,"self":self,"org":org,"other":"","sendmessage":sendmessage,"sendtype":sendtype});
			number = $.trim($("#text_number2").val());
			if ( number==""){
				this.showhint($("#hint1"),"请输入合同转正提前数量");
				$("#text_number2").focus();
				return;
			}
			unit = $("#combox_unit2").val();
			if ( unit == "") {
				this.showhint($("#hint1"),"请输入合同转正提前计量单位");
				return;				
			}
			self = $("#check_self2").attr("checked");
			org = $("#check_org2").attr("checked");
			self = self==null ? 0:1;
			org  = org ==null ? 0:1;
			if ( self == 0 && org == 0){
				this.showhint($("#hint1"),"请选择合同转正消息接收对象！");
				return;
			}
			sendmessage = $.trim($("#text_sendmessage2").val());
			if ( sendmessage==""){
				this.showhint($("#hint1"),"请输入合同转正提醒消息！");
				return;
			}
			sendtype = "";
			var control = $("#sendtype2 input");
			for(var i=0;i<control.length;i++){
				if ( control.eq(i).attr("checked")!=null){
					sendtype += control.eq(i).attr("value")+",";
		    }
			}
			if ( sendtype==""){
				this.showhint($("#hint2"),"接选择消息提醒方式！");
				return;	
			}			
			data.push({ "type":2,"number":number,"unit":unit,"self":self,"org":org,"other":"","sendmessage":sendmessage,"sendtype":sendtype});	
		}
		else{
		  self = $("#check_self3").attr("checked");
			org = $("#check_org3").attr("checked");
			self = self==null ? 0:1;
			org  = org ==null ? 0:1;
			if ( self==0 && org==0){
				this.showhint($("#hint2"),"接选择提醒消息接收对象！");
				return;						
			}
			var sendmessage = $.trim($("#text_sendmessage3").val());
			if ( sendmessage==""){
				this.showhint($("#hint2"),"请输入生日提醒消息！");
				return;
			}
      var sendtype = "";
			var control = $("#sendtype3 input");
			for(var i=0;i<control.length;i++){
				if ( control.eq(i).attr("checked")!=null){
					if ( i == control.length-1)
				    sendtype += control.eq(i).attr("value");
		      else
		    	  sendtype += control.eq(i).attr("value")+",";
		    }
			}
			if ( sendtype==""){
				this.showhint($("#hint2"),"接选择消息提醒方式！");
				return;	
			}			
			data.push({ "type":3,"number":"","unit":"","self":self,"org":org,"other":"","sendmessage":sendmessage,"sendtype":sendtype});
		}
		//保存参数设置
		$.post(this.save_url,{"data":data},function(returndata){
			 if ( returndata.success){
			   if ( data.length==1)
			     parameter.showhint($("#hint2"),"保存生日提醒参数成功！");
			   else
			   	 parameter.showhint($("#hint1"),"保存合同提醒参数成功！");
			 }
			 else{
			 	 if ( data.length==1)
			     parameter.showhint($("#hint2"),returndata.msg);
			   else
			   	 parameter.showhint($("#hint1"),returndata.msg);
			 }
		});		
	},
	showhint:function(control,message) {
		message = message==null ? "":message;
		  control.text(message);
		if ( message!=""){
			setTimeout(function(){
				control.text("");
			},2000);
		}
	},
	view:function(){
    var row = null;
    var sendtype = "";
  　//获得合同到期提醒参数
		$.getJSON(this.view_url,{"type":1},function(data){
			if (data.success && data.datasource.length>0){
				 row = data.datasource[0];
				 $("#parameter_type1 input[type='text']").val("");
				 $("#parameter_type1 select").val("");
				 $("#parameter_type1 input[type='checkbox']").attr("checked",false);
				 $("#text_number1").val(row.number);
				 $("#combox_unit1").val(row.unit);
				 $("#check_self1").attr("checked",(row.self==1?true:false));
				 $("#check_org1").attr("checked",(row.org==1?true:false));
				 $("#text_sendmessage1").val(row.sendmessage);
				 sendtype = row.sendtype;
				 if (sendtype.indexOf("1")>-1)
				   $("#sendtype1 input[value='1']").attr("checked",true);
				 if (sendtype.indexOf("2")>-1)
				   $("#sendtype1 input[value='2']").attr("checked",true);
				 if (sendtype.indexOf("3")>-1)
				   $("#sendtype1 input[value='3']").attr("checked",true);
			}
		});
		//获得合同转正提醒参数
		$.getJSON(this.view_url,{"type":2},function(data){		
			if (data.success && data.datasource.length>0){
				 row = data.datasource[0];
				 $("#parameter_type2 input[type='text']").val("");
				 $("#parameter_type2 select").val("");
				 $("#parameter_type2 input[type='checkbox']").attr("checked",false);
				 $("#text_number2").val(row.number);
				 $("#combox_unit2").val(row.unit);
				 $("#check_self2").attr("checked",(row.self==1?true:false));
				 $("#check_org2").attr("checked",(row.org==1?true:false));
				 $("#text_sendmessage2").val(row.sendmessage);
				 sendtype = row.sendtype;
				 if (sendtype.indexOf("1")>-1)
				   $("#sendtype2 input[value='1']").attr("checked",true);
				 if (sendtype.indexOf("2")>-1)
				   $("#sendtype2 input[value='2']").attr("checked",true);
				 if (sendtype.indexOf("3")>-1)
				   $("#sendtype2 input[value='3']").attr("checked",true);
			}						
		});
		//获得生日提醒参数
		$.getJSON(this.view_url,{"type":3},function(data){
			if (data.success && data.datasource.length>0){
				row = data.datasource[0];
			  $("#parameter_birthday input[type='text']").val("");
			  $("#parameter_birthday input[type='checkbox']").attr("checked",false);
			  $("#check_self3").attr("checked",(row.self==1?true:false));
				$("#check_org3").attr("checked",(row.org==1?true:false));
				$("#text_sendmessage3").val(row.sendmessage);
				sendtype = row.sendtype;
				if (sendtype.indexOf("1")>-1)
				  $("#sendtype3 input[value='1']").attr("checked",true);
				if (sendtype.indexOf("2")>-1)
				  $("#sendtype3 input[value='2']").attr("checked",true);
				if (sendtype.indexOf("3")>-1)
				  $("#sendtype3 input[value='3']").attr("checked",true); 
		  }
		});
	}
};