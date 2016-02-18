var service =
{
    sys_manager:"",
    getstaff_url:"",
    url:"",
    identical_url:"",
    type:1,
    fields:{},
    state:"",
    record:12,
    issearch:false,
    issearch2:false,
    staffinfo:{},
    sendtype:1,
    issend:0,  //是否在发送
    msgid:"",
    login_account:"",
    viewCreateAccount:function(type)
    {
        if ( type=="add")
        {
           $("#show_role").hide();
           $("#set_role").show();
           service.staffinfo.micro_id="";
           $("#hint_content input").val("");
           //获得账号
           var parameter = { "module":"service","action":"serviceAccount","params":{} };
    		   $.post(this.identical_url,parameter,function(returndata){
    		      if ( returndata.success)
    		      {
    		         $("#textAccount").val(returndata.account);
    		      }
    		   });
           $("#staff_image").attr("src","/bundles/fafatimewebase/images/no_photo.png");
           $("#staff_image").attr("fileid","");
           $("#dept_area,#staff_area").html("");
        }
        else  //修改
        {
            $("#show_role").show();
            $("#set_role").hide();
            $("#textAccount").val(this.staffinfo.login_account);
            $("#text_name").val(this.staffinfo.nick_name);
             //取数据
             var parameter = { "module":"service","action":"get_service","params":{"login_account":this.staffinfo.login_account } };
    		     $.post(this.identical_url,parameter,function(data){
        		     if ( data.success)
        		     {
        		        var returndata = data.staff_area;
        		        if ( returndata.length>0)
        		        {
        		            var row = null;html_dept = Array();html_staff=Array();html_manager=Array();
        		            for(var i=0;i<returndata.length;i++)
        		            {
        		               row = returndata[i];
        		               if ( row.type=="1")
        		               {
        		                  html_dept.push("<span onmouseout='moveout(this);' onmouseover='moveover(this);' class='group_label_area' deptid='" + row.objid+"'>");
        		                  html_dept.push("  <span>"+row.nick_name+"</span>");
        		                  html_dept.push("  <i onclick='removeItem(this);' title='移除部门' class='delete_lable_empty'></i>");
        		                  html_dept.push("</span>");
        		               }
        		               else if ( row.type=="2")
        		               {
        		                  html_staff.push("<span onmouseout='moveout(this);' onmouseover='moveover(this);' class='group_label_area' login_account='" + row.objid+"'>");
        		                  html_staff.push("   <span>"+row.nick_name+"</span>");
        		                  html_staff.push("   <i onclick='removeItem(this);' title='移除人员' class='delete_lable_empty'></i>");
        		                  html_staff.push("</span>");
        		               }
        		               else if (row.type=="3")
        		               {
        		                  html_manager.push("<span onmouseout='moveout(this);' onmouseover='moveover(this);' class='group_label_area' login_account='" + row.objid+"'>");
        		                  html_manager.push("   <span>"+row.nick_name+"</span>");
        		                  html_manager.push("   <i onclick='removeItem(this);' title='移除人员' class='delete_lable_empty'></i>");
        		                  html_manager.push("</span>");
        		               }
        		               if (html_dept.length>0)
        		                 $("#dept_area").html(html_dept.join(""));
        		               if (html_staff.length>0)
        		                 $("#staff_area").html(html_staff.join(""));
        		               if (html_manager.length>0)
        		                 $("#service_manager").html(html_manager.join(""));
        		            }
        		        }
        		        var staff_basic = data.staff_basic;
        		        var fileid = staff_basic.fileid;
        		        $("#staff_image").attr("fileid",fileid);
        		        if ( fileid=="")
        		           $("#staff_image").attr("src","/bundles/fafatimewebase/images/no_photo.png");
        		        else
        		           $("#staff_image").attr("src",data.url+fileid);
        		        var area = staff_basic.area;
        		        $("#show_role").attr("area",area);
        		        if ( area=="1")
        		           $("#show_role").html("<span style='font-weight:bold;color:black;padding-left:10px;'>允许所有用户关注</span>");
        		        else
        		           $("#show_role").html("<span style='font-weight:bold;color:black;padding-left:10px;'>仅管理员邀请</span>");
        		        $("#textdesc").val(staff_basic.desc);
        		     }
    		     });
        }
        $("#create_service").show();
    },
    checkAcctount:function(mail)
    {
        var result=false;
        var reg = /^[a-zA-Z0-9]+[a-zA-Z0-9_-]*(\.[a-zA-Z0-9_-]+)*@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)*\.([a-zA-Z]+){2,}$/;
        result = reg.test(mail);
        if(!result)
        {
        	reg = /^1[3|4|5|8][0-9]\d{8}$/;
        	result = reg.test(mail);
        }
        return result;
    },
    //注册广播员
    register:function()
    {
        if ( !this.getstaffinfo()) return;
        this.fields.micro_id = this.staffinfo.micro_id;
        var parameter = { "module":"service","action":"register_service","params":this.fields };
    		$.post(this.identical_url,parameter,function(returndata){
    		    if ( returndata.success)
    		    {
    		        $('#create_service').hide();
    		        service.search_service(1);
    		    }
    		});
    },
    hint:function(message)
    {
        $(".hint_text").text(message);
        setTimeout(function() { $(".hint_text").text("");},2000);
    },
    getstaffinfo:function()
    {
        var parameter = {};
        var getvalue = $.trim($("#text_name").val());
        if ( getvalue=="")
        {
            this.hint("请输入服务号名称！");
            $("#text_name").focus();
            return false;
        }
        else{
            parameter.name = getvalue;
        }
        getvalue = $.trim($("#textAccount").val());
        if ( getvalue=="")
        {
            this.hint("请输入服务帐号！");
            $("#textAccount").focus();
            return false;
        }
        parameter.login_account = getvalue;
        parameter.desc = $.trim($("#textdesc").val());
        //管理员        
        var ctl = $("#service_manager>span");
        if (ctl.length==0)
        {
            parameter.manager = Array();
        }
        else
        {
            var manager = Array();
            for(var i=0;i<ctl.length;i++)
            {
                manager.push(ctl.eq(i).attr("login_account"));
            }
            parameter.manager = manager;
        }
        //组织部门
        ctl = $("#dept_area>span");
        if (ctl.length==0)
        {
            parameter.deptid = Array();
        }
        else
        {
            var deptid = Array();
            for(var i=0;i<ctl.length;i++)
            {
                deptid.push(ctl.eq(i).attr("deptid"));
            }
            parameter.deptid = deptid;
        }
        //人员
        ctl = $("#staff_area>span");
        if (ctl.length==0)
        {
            parameter.staffid = Array();
        }
        else
        {
            var staffid = Array();
            for(var i=0;i<ctl.length;i++)
            {
                staffid.push(ctl.eq(i).attr("login_account"));
            }
            parameter.staffid = staffid;
        }
        if ( parameter.deptid.length==0 && parameter.staffid.length==0)
        {
            this.hint("组织机构或人员范围必须选择一项！");
            return false;
        }
        //
        if ( $("#set_role:visible").length==1)
        {
            var area = $("#private").attr("checked");
            if ( area !=null && area!=="")
                parameter.concern_approval = 0;
            else
                parameter.concern_approval = 1;
        }
        else
        {
           parameter.concern_approval = $("#show_role").attr("area");
        }
        parameter.fileid = $("#staff_image").attr("fileid");
        service.fields = parameter;
        return true;
    },
    viewTree:function(evn)
    {
        var state = $(evn).attr("state");
        this.type = state;
    		$('#selectdept').show();
    		var title = "";
    		if ( $("#tree_depart").children().length==0)
    		{
    		   ztreeObj.init_zzjg();
    		}
    		if ( state=="1"){
    			title = "选择组织机构";
    			$(".group_staff_box").hide();
    			$("#tree_depart").show();
    		}
    		else if ( state=="2")
    		{
    			title="选择特定人员";
    			$("#tree_depart").hide();
    			$(".group_staff_box").show();
    		}
    		else if (state=="3")
		    {
		       title="选择服务号管理员";
    			 $("#tree_depart").hide();
    			 $(".group_staff_box").show();
		    }    		
    		$("#selectdept .title").text(title);
    },
    selectdept:function()
    {
        var html = Array();
    		if ( this.type==1){
    			var treeObj = $.fn.zTree.getZTreeObj("tree_depart");
    			var nodes = treeObj.getCheckedNodes(true);
    			var parentid = "";
    			for(var i=0;i<nodes.length;i++)
    			{
    				var node = nodes[i];
    				var deptid = node.id;
    				if ( node.isParent && deptid.indexOf("v")!=-1){
    					html.push("<span deptid='"+deptid+"' class='group_label_area' onmouseover='moveover(this);' onmouseout='moveout(this);'><span>"+node.name+"</span><i class='delete_lable_empty' title='移除部门' onclick='removeItem(this);'></i></span>");
    				  break;
    				}
    				else if (node.isParent){
    					parentid += node.id+";";
    				}
    				var pid = node.pId;
    				if ( parentid.indexOf(pid)==-1)
    				{
    					html.push("<span deptid='"+deptid+"' class='group_label_area' onmouseover='moveover(this);' onmouseout='moveout(this);'><span>"+node.name+"</span><i class='delete_lable_empty' title='移除部门' onclick='removeItem(this);'></i></span>");
            }
    			}
    			$("#dept_area").html(html.join(""));   
    	  }
    	  else
    	  {
    	  	var staffs = $(".group_selected_staff>span");
    	  	for(var i=0;i<staffs.length;i++){
    	  		html.push("<span login_account='"+ staffs.eq(i).attr("login_account") +"' class='group_label_area' onmouseover='moveover(this);' onmouseout='moveout(this);'><span>"+ staffs.eq(i).text() +"</span><i class='delete_lable_empty' title='移除人员' onclick='removeItem(this);'></i></span>");
    	  	}
    	  	if ( this.type==2)
    	  		$("#staff_area").html(html.join(""));    	  	
    	  	else if ( this.type==3)
    	  	   $("#service_manager").html(html.join(""));
    	  }    	 
    		$('#selectdept').hide();
    },
    selectedImg:function(evn)
    {
       $(".selected_pic_box").modal("show");
	  },
	  uploadfile:function(evn)
    {
        $("#uplod_loading").attr("src","/bundles/fafatimewebase/images/loading.gif");
        $("#uplod_loading").show();
        $(".upload_hint").text("正在上传应用Logo，请稍候……");
        uploadObj[0].doSave();
    },
	  window_close:function()
    {
        $('.selected_pic_box').modal('hide');
        $("#uplod_loading").hide();
        $(".upload_hint").text("");
    },
    pageselectCallback:function(pageindex)
    {
    		 if (service.issearch )
    		   service.search_service(pageindex + 1);
    },
	  pageInit:function(){
     	  var opt = {callback: service.pageselectCallback};
        opt.items_per_page = service.record;
        opt.num_display_entries = 3;
        opt.num_edge_entries = 3;
        opt.prev_text="上一页";
        opt.next_text="下一页";
        return opt;
	  },
    search_service:function(pageindex)
    {
        var para = { "staff":$.trim($("#text_servicename").val()),"pageindex":pageindex,"record":this.record };
		    var parameter = { "module":"service","action":"search_service","params":para };
    		$.post(this.identical_url,parameter,function(returndata){
            if ( returndata.success){
            if ( pageindex==1 ){
            	  	if ( returndata.recordcount <= service.record){
            	  		$("#search_page").hide();
            	  	}
            	  	else{
                 	  	service.issearch = false;
                 	  	var optInit = service.pageInit();
                 	  	$("#search_page").show();
                 	  	$("#search_page").pagination(returndata.recordcount,optInit);
                 	  	service.issearch = true;
            	    }
              }
              else{
              	 $("#search_page").show();
              	 service.issearch = true;
              }
              service.full_service(returndata.data);
            }
            else{				
            }    		   
    		});
    },
    full_service:function(data)
    {
        var html = Array();
        if ( data.length==0)
        {
            html.push("<span style='border-bottom:1px solid #ccc;height:32px;line-height:30px;' class='mb_common_table_empty'>未查询到服务号数据记录！</span>");
        }
        else
        {
          for(var i=0;i<data.length;i++)
          {
              var row = data[i];
              html.push("<tr micro_id='"+row.micro_id+"' login_account='"+row.login_account+"' jid='"+row.jid+"' microType='"+row.type+"' openid='"+row.openid+"'>");
              html.push("  <td width='435' align='left'>"+row.login_account+"</td>");
              html.push("  <td class='nickname' width='435' align='left'>"+row.nick_name+"</td>");
              html.push("  <td width='128' align='center'>");
              html.push("    <div style='margin-top:7px;'>");
              var manager = row.manager;
              if ( manager=="sys_manager" || manager=="manager")
              {
                 html.push("      <span title='推送消息'    onclick='service.pushMessage(this)' class='glyphicon glyphicon-send glyphicon_style'></span>");
                 html.push("      <span title='编辑广播帐号' onclick='service.eidt(this)' class='glyphicon glyphicon-pencil glyphicon_style'></span>");
                 if ( manager=="manager")
                    html.push("<span class='glyphicon glyphicon-trash glyphicon_style' style='opacity:0.4;cursor:default;'></span>");
                 else
                    html.push("<span title='删除广播帐号' onclick='service.showdele(this)' class='glyphicon glyphicon-trash glyphicon_style'></span>");
              }
              html.push("    </div>");
              html.push("  </td>");              
              html.push("</tr>");
          }
        }
        $("#table_service tbody").html(html.join(""));
    },
    showdele:function(e)
    {
        service.staffinfo.login_account = $(e).parents("tr").attr("login_account");
        service.staffinfo.micro_id = $(e).parents("tr").attr("micro_id");
        $(e).parent().hide()
        var html = Array();
        html.push("    <div class='service_del'>");
        html.push("      <span class='btnGreen' onclick='service.delete_staff(this);'>确定</span>");
        html.push("      <span class='btnGray' onclick='$(this).parent().prev().show();$(this).parent().remove();'>取消</span>");
        html.push("    </div>");
        $(e).parents("td").append(html.join("")); 
    },
    //删除广播员帐号
    delete_staff:function(e)
    {
        var ctl = $(e).parent();
        ctl.html("<img src='/bundles/fafatimewebase/images/loading.gif' /><span style='margin-left:0px;font-size:12px;'>正在删除...</span>");
        var para = { "login_account":service.staffinfo.login_account,"micro_id":service.staffinfo.micro_id };
		    var parameter = { "module":"service","action":"delete_service","params":para };
    		$.post(this.identical_url,parameter,function(returndata){
    		    if ( returndata.success )
    		    {
    		        ctl.html("删除服务号成功!");
    		        setTimeout(function()
    		        {
    		          ctl.prev().show();
    		          ctl.remove();
    		          $("#table_service tbody tr[login_account='"+service.staffinfo.login_account+"']").remove();
    		        },1000);
    		    }
    		});      
    },
    eidt:function(e)
    {
        service.staffinfo.login_account = $(e).parents("tr").attr("login_account");
        service.staffinfo.nick_name = $(e).parents("tr").find(".nickname").text();
        service.staffinfo.micro_id  = $(e).parents("tr").attr("micro_id");
        service.viewCreateAccount("edit");
    },
    pushMessage:function(evn)
    {
        var control = $(evn).parents("tr");
        microObj = {};
        microObj.microNumber = control.attr("login_account");
        microObj.microJid = control.attr("jid");
        microObj.microName = control.find(".nickname").text();
        microObj.microUse = "1";
        microObj.microType = control.attr("microtype");
        microObj.microOpenid = control.attr("openid");
        $(".micro_obj").text(microObj.microName+"("+microObj.microNumber+")");
        $("#search_body").hide();
        $(".mesagesend").show(); 
    },
    toggle:function(evn)
    {
        $(".group_menu_bar>span").attr("class","menu_item");
        $(evn).attr("class","menu_item_active");
        if ( $(evn).attr("state") =="service_mrg")
        {
            $("#service_mrg").show();
            $("#revoke").hide();            
        }
        else
        {
            $("#service_mrg").hide();
            $("#revoke").show();
            if ( $("#table_message tbody tr").length==0)
                service.search_message(1);
        }
    },
    pageCallback:function(pageindex)
    {
    		 if (service.issearch2 )
    		   service.search_message(pageindex + 1);
    },
	  page_Init:function(){
     	  var opt = {callback: service.pageCallback};
        opt.items_per_page = service.record;
        opt.num_display_entries = 3;
        opt.num_edge_entries = 3;
        opt.prev_text="上一页";
        opt.next_text="下一页";
        return opt;
	  },    
    search_message:function(pageindex)
    {    		
    		var para = { "staff":$.trim($("#text_account").val()),"pageindex":pageindex,"record":this.record };
		    var parameter = { "module":"service","action":"search_sendmessage","params":para };
    		$.post(this.identical_url,parameter,function(returndata){
            if ( returndata.success)
            {
                if ( pageindex==1 )
                {
                	  	if ( returndata.recordcount <= service.record){
                	  		$("#page_message").hide();
                	  	}
                	  	else{
                     	  	service.issearch2 = false;
                     	  	var optInit = service.page_Init();
                     	  	$("#page_message").show();
                     	  	$("#page_message").pagination(returndata.recordcount,optInit);
                     	  	service.issearch2 = true;
                	    }
                }
                else{
                	 $("#search_page").show();
                 	 service.issearch = true;
                }
                service.fullmessage(returndata.data);
            }	     		   
    		});
    		
    		
    },
    fullmessage:function(data)
    {
        var html = Array();
        if ( data.length==0)
        {
            html.push("<span class='mb_common_table_empty' style='border-bottom:1px solid #ccc;height:32px;line-height:30px;'>未查询到推送消息</span>");
        }
        else
        {
            var row = null;
            for(var i=0;i< data.length;i++)
            {
              row = data[i];
              html.push("<tr msgid='"+row.messageid+"' login_account='"+row.send_account+"'>");
              html.push("  <td width='220' align='left'>"+row.send_account+"</td>");
              html.push("  <td class='nickname' width='220' align='left'>"+row.nick_name+"</td>");
              html.push("  <td width='220' align='center'>"+row.senddate+"</td>");
              html.push("  <td width='182' align='left'>"+row.sendtype+"</td>");
              html.push("  <td width='155' align='center'>");
              html.push("    <div class='service_operator'>");
              html.push("      <span onclick='service.send_detail(this);' class='glyphicon glyphicon-list-alt glyphicon_style' title='查看详细'></span>");
              html.push("      <span title='撤回消息' onclick='service.showrevoke(this)' class='glyphicon glyphicon-flash glyphicon_style' style='margin-left:30px;font-size:18px;'></span>");
              html.push("    </div>");
              html.push("  </td>");
              html.push("</tr>");
           }
        }
        $("#table_message tbody").html(html.join(""));
    },
    send_detail:function(evn)
    {
        var msgid = $(evn).parents("tr").attr("msgid");
        $("#send_detail").modal("show");
        var contentObj = $("#send_detail .content");
        $(".sendmsg_picture,.sendmsg_pager,.sendmsg_text").hide();
        var html = Array();
        html.push("<div class='service_getsend'>");
        html.push("  <img src='/bundles/fafatimewebase/images/loading.gif'  /><span>正在获得数据，请稍候……</span>");
        html.push("</div>");
        contentObj.append(html.join(""));        
        var para = { "msgid":msgid };
		    var parameter = { "module":"service","action":"getMessageDetail","params":para };
    		$.post(this.identical_url,parameter,function(data){
    		    $(".service_getsend").remove();
    		    html=[];
    		    if ( data.success)
    		    {
    		        var msg_type = data.msg_type;
    		        var returndata = null;
    		        if ( msg_type=="text")
    		        {
    		            $(".sendmsg_text").show();
    		            returndata = data.returndata[0];
    		            $(".sendmsg_text .sendmsg_title").val(returndata.msg_title);
    		            $(".sendmsg_text .sendmsg_content").val(returndata.msg_content);
    		        }
    		        else if ( msg_type=="picture")
    		        {
    		            $(".sendmsg_picture").show();    		            
    		            $(".sendmsg_picture").html($("#sendmsg_model").html());
    		            $(".sendmsg_pager").hide();
    		            returndata = data.returndata[0];
    		            $(".sendmsg_picture .sendmsg_title").val(returndata.msg_title);
    		            $(".sendmsg_picture .sendmsg_icon").attr("src",returndata.msg_img_url);
    		            $(".sendmsg_picture .sendmsg_summary").val(returndata.msg_summary);
    		            $(".sendmsg_picture .sendmsg_content").html(returndata.msg_content);
    		            $(".sendmsg_picture .sendmsg_content").find("img").css("max-width","400px");
    		        }
    		        else if ( msg_type=="textpicture")
    		        {
    		            $(".sendmsg_picture").show();
    		            returndata = data.returndata;
    		            var len = returndata.length;
    		            var html = Array();
    		            var pager = Array();
    		            for(var j=0;j<len;j++)
    		            {
    		                html.push("<div pageindex='"+j+"'" +(j>0?"style='display:none;'":"")+">" + $("#sendmsg_model").html()+"</div>");    		                
    		                if ( len>1)
    		                {
    		                    pager.push("<span onclick='service.pager(this);'>"+(j+1)+"</span>");
    		                }
    		            }
    		            if ( pager.length==0)
    		            {
    		                $(".sendmsg_pager").hide();
    		            }
    		            else
    		            {
    		                $(".sendmsg_pager").show();
    		                $(".sendmsg_pager").html(pager.join(""));
    		            }
    		            $(".sendmsg_picture").html(html.join(""));
    		            var container = $(".sendmsg_picture>div");	            
    		            for(var i=0;i<len;i++)
    		            {
    		                var row = returndata[i];
    		                container.eq(i).find(".sendmsg_title").val(row.msg_title);
    		                container.eq(i).find(".sendmsg_icon").attr("src",row.msg_img_url);
    		                container.eq(i).find(".sendmsg_summary").val(row.msg_summary);
    		                container.eq(i).find(".sendmsg_content").html(row.msg_content);
    		                container.eq(i).find(".sendmsg_content").find("img").css("max-width","400px");             		                
    		            }
    		        }   		        
    		    }
    		});
    },
    pager:function(evn)
    {
        var pager= $(evn).text();
        pager = parseInt(pager)-1;
        $(".sendmsg_picture>div").hide();
        $(".sendmsg_picture>div[pageindex='"+pager+"']").show();                
    },
    showrevoke:function(evn)
    {
        var control = $(evn).parents("tr");
        this.msgid = control.attr("msgid");
        this.login_account = control.attr("login_account");
        $(evn).parents(".service_operator").hide();
        var html = Array();
        html.push("    <div class='service_del'>");
        html.push("      <span class='btnGreen' style='margin-left:22px;' onclick='service.msg_revoke(this);'>确定</span>");
        html.push("      <span class='btnGray' onclick='$(this).parent().prev().show();$(this).parent().remove();'>取消</span>");
        html.push("    </div>");
        $(evn).parents("td").append(html.join(""));
        return;
    },
    msg_revoke:function(evn)
    {
        var control = $(evn).parent();
        control.html("<img src='/bundles/fafatimewebase/images/loading.gif' /><span style='margin-left:0px;font-size:12px;'>正在撤回...</span>");
        var para = { "msgid":this.msgid,"login_account":this.login_account };
		    var parameter = { "module":"service","action":"service_revoke","params":para };
    		$.post(this.identical_url,parameter,function(returndata){
    		    control.removeAttr("disabled");
    		    if ( returndata.success)
    		    {   
    		        control.html("消息撤回成功!");    		        
    		        setTimeout(function() { service.search_message(1); },2000);
    		    }
    		    else
    		    {
    		        control.html("消息撤回失败!");
    		        setTimeout(function() { control.prev().show();control.remove(); },2000);
    		    }
    		});
    },
    service_change:function(evn)
    {
        var name = $.trim($(evn).val());
        if ( name=="")
        {
        	$(evn).next().hide();
        }
    },
    sevice_check:function(val)
    {
        var oldname = $("#text_name").attr("servicename");
        var newname = $.trim($("#text_name").val());
        if ( newname=="" || (oldname!="" && oldname==newname))
        {
            $(".service_check").hide();
            return;
        }
        var para = { "micro_id":service.staffinfo.micro_id,"name":newname };        
        var parameter = { "module":"service","action":"check_service","params":para };
        $(".service_check").show();
        $(".service_check>img").show();
        $("#check_icon").hide();
        $("#check_msg").text("正在检查");
        $.post(this.identical_url,parameter,function(returndata){
        	$(".service_check>img").hide();
        	$("#check_icon").show();
        	$("#text_name").attr("servicename",newname);
        	if ( returndata.success){
        		if ( returndata.exists)
        		{
        			$("#check_icon").attr("class","common_error_icon");
        			$("#check_msg").text("名称已存在！");
        			$("#btnSave").attr("disabled","disabled");
        		}
        		else
        		{
        			$("#check_icon").attr("class","common_success_icon");
        		  $("#check_msg").text("");
        		  $("#btnSave").removeAttr("disabled");
        		}
          }
          else{
          	$("#check_icon").attr("class","common_error_icon");
        		$("#check_msg").text("发生错误");
          }
        });
    }
}