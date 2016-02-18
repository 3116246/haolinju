var Friend =
{
	identical_url:"",
	getdept_url:"",
	deptid:Array(),
	loadChildrenDept:function(event, treeId, treeNode)
	{
		 var id = treeNode.id;
		 var treeObj = $.fn.zTree.getZTreeObj("tree_depart");
		 if ( !treeNode.isParent)
		 {
		    treeNode.checked = true;
		 	 if ( treeNode.state == "0")
		 	 {
		 	    treeObj.refresh();
		 	    return;
		 	 }
		 	 var parameter = { "module":"Dept","action":"getFriendDept","params":{"deptid":treeNode.id},t:new Date().getTime()};
		 	 $.getJSON(Friend.identical_url,parameter,function(data) {
		 	 	  if (data.success)
		 	 	  {
		 	 	  	if (data.datasource.length > 0)
		 	 	  	{
			 	 	    
	            treeObj.addNodes(treeNode,data.datasource);
            }
            treeNode.state = 0;
          }
		 	 });
		 }
		 else
		 {
		    treeNode.checked = true;
		    treeObj.refresh();
		 }		 
	},
  search_tree:function()
  {
    var deptname = $.trim($("#txt_deptname").val());
    Friend.load_tree("",deptname);
  },
	load_tree:function(deptid,deptname)
	{
	    var zTreeSetting = {
    	   check:{
    	  		enable: true,
    				chkboxType: { "Y" : "", "N" : "" }
    	  	},
    	  	data:{
    	  		simpleData:{
    	  			enable:true
    	  		}
    	  	},
    	  	callback: {
    	  		onClick:Friend.loadChildrenDept
    		  }
      };
	    var para = { "deptid":deptid,"deptname":deptname };
		  var parameter = { "module":"Dept","action":"getFriendDept","params":para,t:new Date().getTime()};
		  $.post(this.identical_url,parameter,function(returndata){
		      $.fn.zTree.init($("#tree_depart"), zTreeSetting, returndata.datasource);
      });
	},
	setFriend:function()
	{
	    var deptids = Array();
	    var ctl = $(".common_ul>li");
	    if (ctl.length==0)
	    {
	        $(".message").html("请选择组织机构");
	        return;
	    }
	    else
	    {
	        for( var i=0;i<ctl.length;i++)
	        {
	            if (ctl.eq(i).attr("number")==0) continue;
	            deptids.push(ctl.eq(i).attr("deptid"));
	        }
	    }
	    if ( deptids.length==0 )
	    {
	        $(".message").html("所选择组织机构无人员！");
	        return; 
	    } 
	    var parameter = { "module":"Dept","action":"setFriend","params":{"deptid":deptids} };
      $.post(this.identical_url,parameter,function(returndata){
          if ( returndata.success)
          {
              $("#prompt .hint_message").html("部门互为好友操作成功！");
              Friend.deptid = Array();
              setTimeout(function() { $("#prompt .hint_message").html(""); $("#prompt").hide();},2000);
          }
          var message = returndata.message,html = "";
          if ( message.length>0)
          {
              for(var i=0;i<message.length;i++)
              {
                 html += message[i];
              }
        	    $("#prompt .hint_message").html(html);		
          }	    
      });
	},
	//选择部门	
	selecteddept:function()
	{
	    $("#prompt .hint_message").html("");
		  var treeObj = $.fn.zTree.getZTreeObj("tree_depart");
		  var nodes = treeObj.getCheckedNodes(true);
		  var html = Array();
		  if ( nodes.length==0) 
		  {
        
		  }
		  else
		  {
		    var node = null;
		    for (var i=0;i<nodes.length;i++)
		    {
		        node = nodes[i];
		        html.push("<li deptid='"+ node.id+"' number='"+node.number+"'>");
		        html.push("  <span class='deptname'>"+node.name+"</span>");
		        html.push("  <span class='glyphicon glyphicon-remove gly_del' onclick = 'Friend.deldept(this);'></span>");
		        html.push("</li>");
		    }
		    $(".common_ul").html(html.join(""));
    	}
  },
  deldept:function(evn)
  {
    $(evn).parents("li").remove();    
  }
}

var FriendCircle =
{
    getstaff_url:"",
    url:"",
    identical_url:"",
    type:1,
    fields:{},
    state:"",
    record:14,
    issearch:false,
    staffinfo:{},
    viewCreateAccount:function(type)
    {
        this.state = type;
        $("#hint_content input").val("");
        $("#dept_area,#staff_area").html("");
        if ( type=="add")
        {
           //获得随机帐号
		       var parameter = { "module":"Announcer","action":"announcerAccount","params":{} };
    		   $.post(this.identical_url,parameter,function(returndata){
    		      $("#textAccount").val(returndata.account);
    		   });
           $("#staff_image").attr("src","/bundles/fafatimewebase/images/no_photo.png");
           $("#staff_image").attr("fileid","");
        }
        else
        {
           $("#textAccount").val(this.staffinfo.login_account);
           $("#text_name").val(this.staffinfo.nick_name);
           //取数据
           var parameter = { "module":"Announcer","action":"get_announcer","params":{"login_account":this.staffinfo.login_account } };
    		   $.post(this.identical_url,parameter,function(data){
    		     if ( data.success)
    		     {
    		        var returndata = data.returndata;
    		        if ( returndata.length>0)
    		        {
    		            var row = null;html_dept = Array();html_staff=Array();
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
    		               else
    		               {
    		                  html_staff.push("<span onmouseout='moveout(this);' onmouseover='moveover(this);' class='group_label_area' fafa_jid='" + row.objid+"'>");
    		                  html_staff.push("   <span>"+row.nick_name+"</span>");
    		                  html_staff.push("   <i onclick='removeItem(this);' title='移除人员' class='delete_lable_empty'></i>");
    		                  html_staff.push("</span>");
    		               }
    		               if (html_dept.length>0)
    		                 $("#dept_area").html(html_dept.join(""));
    		               if (html_staff.length>0)
    		                 $("#staff_area").html(html_staff.join(""));
    		            }
    		        }
    		        var fileid = data.fileid;
    		        $("#staff_image").attr("fileid",fileid);
    		        if ( fileid=="")
    		           $("#staff_image").attr("src","/bundles/fafatimewebase/images/no_photo.png");
    		        else
    		           $("#staff_image").attr("src",data.url+fileid);
    		     }
    		   });
        }
        $('#prompt').show();
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
        this.fields.state = this.state;
        var parameter = { "module":"Announcer","action":"register_announcer","params":this.fields };
    		$.post(this.identical_url,parameter,function(returndata){
    		    if ( returndata.success)
    		    {
    		        $('#prompt').hide();
    		        FriendCircle.search_broadcaster(1);
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
            this.hint("请输入广播名称！");
            $("#text_name").focus();
            return false;
        }
        else{
            parameter.name = getvalue;
        }
        parameter.login_account = $.trim($("#textAccount").val());
        getvalue = $.trim($("#textpassword").val());
        if ( this.state=="add")
        {
            if ( getvalue=="")
            {
                this.hint("请输入登录密码！");
                $("#textpassword").focus();
                return false;
            }
            else{
                if (getvalue.length<6)
                {
                    this.hint("登录密码至少六位！");
                    $("#textpassword").focus();
                    return false;
                }
                var pas2 = $.trim($("#textpassword2").val());
                if ( pas2=="")
                {
                    this.hint("请输入确认密码！");
                    $("#textpassword2").focus();
                    return false;
                }
                else if ( pas2 != getvalue)
                {
                    this.hint("两次密码不一致，请重新输入！");
                    $("#textpassword").focus();
                    return false;
                }
                parameter.password = getvalue;
            }
        }
        else  //修改时的密码判断
        {
            if ( getvalue !="" )
            {
                if (  getvalue.length<6)
                {
                    this.hint("登录密码至少六位！");
                    $("#textpassword").focus();
                    return false;
                }
                else
                {
                    var password2 = $.trim($("#textpassword2").val());
                    if ( password2=="")
                    {
                        this.hint("请输入确认密码！");
                        $("#textpassword2").focus();
                        return false;                        
                    }
                    else if ( getvalue != password2)
                    {
                        this.hint("两次密码不一致，请重新输入！");
                        $("#textpassword").focus();
                        return false;                                                
                    }
                }
                parameter.password = getvalue;
            }
        }
        //组织部门
        var ctl = $("#dept_area>span");
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
        var ctl = $("#staff_area>span");
        if (ctl.length==0)
        {
            parameter.staffid = Array();
        }
        else
        {
            var staffid = Array();
            for(var i=0;i<ctl.length;i++)
            {
                staffid.push(ctl.eq(i).attr("fafa_jid"));
            }
            parameter.staffid = staffid;
        }
        if ( parameter.deptid.length==0 && parameter.staffid.length==0)
        {
            this.hint("组织机构或人员范围必须选择一项！");
            return false;
        }
        parameter.fileid = $("#staff_image").attr("fileid");
        FriendCircle.fields = parameter;
        return true;
    },
    viewTree:function(evn)
    {
        var state = $(evn).attr("state");
    		this.type = state;
    		$('#selectdept').show();
    		var title = "";
    		if ( $("#tree_depart").children().length==0)
    		    ztreeObj.init_zzjg();
    		if ( state=="1"){
    		    title = "选择组织机构";
    		    $(".group_staff_box").hide();
    		    $("#tree_depart").show();
    		}
    		else{
    			if ( state=="2")
    			  title="选择特定人员";
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
    	  else{
    	  	var staffs = $(".group_selected_staff>span");
    	  	for(var i=0;i<staffs.length;i++){
    	  		html.push("<span fafa_jid='"+ staffs.eq(i).attr("fafa_jid") +"' class='group_label_area' onmouseover='moveover(this);' onmouseout='moveout(this);'><span>"+ staffs.eq(i).text() +"</span><i class='delete_lable_empty' title='移除人员' onclick='removeItem(this);'></i></span>");
    	  	}
    	  	if ( this.type==2){
    	  		$("#staff_area").html(html.join(""));
    	  	}
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
    setPublishEnable:function()
    {
        var content = $.trim($("#text_publish").val());
        if ( content.length>0)
        {
           $("#btn_publish").attr("class","button_publish_yes");
           $("#btn_publish").removeAttr("title");
        }
        else
        {
           $("#btn_publish").attr("class","button_publish_no");
           $("#btn_publish").attr("title","请输入广播内容！");
        }
    },
    //发布广播
    publish:function(evn)
    {
        var class_name = $(evn).attr("class");
        if ( class_name=="button_publish_yes")
        {
            $(evn).attr("class","button_publish_no");
            var fileid = Array();
            var fields = { "content":$.trim($("#text_publish").val()),"fileid":fileid};
            var parameter = { "module":"Announcer","action":"publishFriendCircle","params":fields };
        		$.post(this.identical_url,parameter,function(returndata){
        		    var message = "";
        		    if ( returndata.success)
        		    {
        		        $(".publish_message").text("广播发布成功！");
        		        $("#text_publish").val("");
        		        $("#btn_publish").attr("class","button_publish_no");
        		    }
        		    else
        		    {
        		        $("#btn_publish").attr("class","button_publish_yes");
        		        $(".publish_message").text("广播发布失败！");
        		    }
        		    setTimeout(function() { $(".publish_message").text(""); },3000);        		      		    
        		});
        }
    },
    //加载图标
    loadFace:function(evn)
    {
        if ( $("#FaceEmote").children().length==0)
        {
            var url = $(evn).attr("url");
            var p = "/bundles/fafatimewebase/images/face/";
            $.post(url,function(data){
                var html = Array(),row = null;
                for(var i=0;i< data.length;i++)
                {
                    row = data[i];
                    html.push("<li onclick='FriendCircle.getFace(this);' title='"+row.key+"'><img src='"+(p+row.value)+"' /></li>");
                }
                $("#FaceEmote").html(html.join(""));
                $("#FaceEmote").show();
            });
        }
        else
        {
            $("#FaceEmote").toggle();
        }
    },    
    getFace:function(e)
    {
        var face = $(e).attr("title");
        var content = $("#text_publish").val();
        content += "["+face+"]";
        $("#text_publish").val(content);
        $("#btn_publish").attr("class","button_publish_yes");
    },
    pageselectCallback:function(pageindex)
    {
    		 if (FriendCircle.issearch )
    		   FriendCircle.search_broadcaster(pageindex + 1);
    },
	  pageInit:function(){
     	  var opt = {callback: FriendCircle.pageselectCallback};
        opt.items_per_page = FriendCircle.record;
        opt.num_display_entries = 3;
        opt.num_edge_entries = 3;
        opt.prev_text="上一页";
        opt.next_text="下一页";
        return opt;
	  },    
    search_broadcaster:function(pageindex)
    {
        var para = { "staff":$.trim($("#textname").val()),"pageindex":pageindex,"record":this.record };
		    var parameter = { "module":"Announcer","action":"search_announcer","params":para };
    		$.post(this.identical_url,parameter,function(returndata){
            if ( returndata.success){
            if ( pageindex==1 ){
            	  	if ( returndata.recordcount <= FriendCircle.record){
            	  		$("#search_page").hide();
            	  	}
            	  	else{
                 	  	FriendCircle.issearch = false;
                 	  	var optInit = FriendCircle.pageInit();
                 	  	$("#search_page").show();
                 	  	$("#search_page").pagination(returndata.recordcount,optInit);
                 	  	FriendCircle.issearch = true;
            	    }
              }
              else{
              	 $("#search_page").show();
              	 FriendCircle.issearch = true;
              }
              FriendCircle.full_broadcaster(returndata.data);
            }
            else{				
            }    		   
    		});
    },
    full_broadcaster:function(data)
    {
        var html = Array();
        if ( data.length==0)
        {
            html.push("<span style='border-bottom:1px solid #ccc;height:32px;line-height:30px;' class='mb_common_table_empty'>未查询到广播员数据记录！</span>");
        }
        else
        {
          for(var i=0;i<data.length;i++)
          {
              var row = data[i];
              html.push("<tr login_account='"+row.login_account+"'>");
              html.push("  <td width='420' align='left'>"+row.login_account+"</td>");
              html.push("  <td class='nickname' width='420' align='left'>"+row.nick_name+"</td>");
              html.push("  <td width='158' align='center' style='padding-top:8px;padding-left:55px;'>");
              html.push("   <i title='编辑广播帐号' onclick='FriendCircle.edit(this)' class='glyphicon glyphicon-pencil' style='float:left;margin-top:0px;cursor:pointer;'></i>");
              html.push("   <i class='glyphicon glyphicon-trash' onclick='FriendCircle.showdele(this);' title='删除广播帐号'  style='float:left;margin-top:0px;cursor:pointer;margin-left:30px;'></i>");
              html.push("</tr>");
          }
        }
        $(".mb_common_table tbody").html(html.join(""));
    },
    showdele:function(e)
    {
        FriendCircle.staffinfo.login_account = $(e).parents("tr").attr("login_account");
        var nick_name = $(e).parents("tr").find(".nickname").text();
        var html = "确定要删除广播员帐号<span style='font-weight:bold;color:#0088cc;padding-left:2px;padding-right:2px;'>"+nick_name+"</span>吗？";
        $("#delete_staff .content").html(html);
        $("#delete_staff").modal("show");
    },
    //删除广播员帐号
    delete_staff:function(e)
    {
        var para = { "login_account":FriendCircle.staffinfo.login_account };
		    var parameter = { "module":"Announcer","action":"delete_announcer","params":para };
    		$.post(this.identical_url,parameter,function(returndata){
    		    if ( returndata.success )
    		    {
    		        $("#delete_staff .content").html("删除用户帐号成功！");
    		        $(".mb_common_table tbody tr[login_account='"+FriendCircle.staffinfo.login_account+"']").remove();
    		        setTimeout(function() { $("#delete_staff").modal("hide");},2000);
    		    }
    		});      
    },
    edit:function(e)
    {
        FriendCircle.staffinfo.login_account = $(e).parents("tr").attr("login_account");
        FriendCircle.staffinfo.nick_name = $(e).parents("tr").find(".nickname").text();
        FriendCircle.viewCreateAccount("edit");
    }
}