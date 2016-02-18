var staff =
{
    pid:"",
    dele_url:"",
    record:12,
    search_state:false,
    issearch:false,
    deptid:"",
    login_account:"",
    e_mail:"",
    nick_name:"",
    update_url:"",
    pagetype:"new",
    pageindex:1,
    staffinfo:{"deptid":"","nick_name":"","sex":"","login_account":"","duty":"","mobile":""},
    pageselectCallback:function(page_index){
        if ( staff.issearch )
        {
            var page_index = page_index + 1;
            if ( $("#staff_body table tbody tr[pageindex='"+page_index+"']").length>0)
            {
                $("#staff_body table tbody tr:visible").hide();
                $("#staff_body table tbody tr[pageindex='"+page_index+"']").show();
                if ( $("#staff_body tbody tr:visible").find(".check_box[checked='checked']").length==staff.record)
                    $("#all_selected").attr("checked",true);
                else
                    $("#all_selected").attr("checked",false);
            }
            else
            {
                $("#all_selected").attr("checked",false);
                staff.search_staff(page_index,"append");
            }
        }
    },
    pageInit:function(){
        var opt = {callback: staff.pageselectCallback};
        opt.items_per_page = staff.record;
        opt.num_display_entries = 3;
        opt.num_edge_entries=3;
        opt.prev_text="上一页";
        opt.next_text="下一页";
        return opt;
    },
    query_staff:function(){
        $("#all_selected").attr("checked",false);
        $(".batch_area").hide();
        staff.nick_name = $.trim($("#text_search_account").val());
        if ( staff.nick_name != "")
            this.deptid = "";
        this.search_staff(1,"new");
    },
    search_staff:function(pageindex,pagetype)
    {
        this.pagetype = pagetype;
        this.pageindex = pageindex;
        if ( this.search_state==true) return;
        this.search_state = true;
        var parameter = { "record":staff.record,"pageindex":pageindex,"login_account":this.nick_name,"dept_id":this.deptid};
        $(".search_staff_area").show();
        $.post(this.search_staff_url,parameter,function(returndata){
            staff.search_state = false;
            $(".search_staff_area").hide();
            if ( returndata.success)
            {
                if ( pageindex==1 )
                {
                    if ( parseInt(returndata.recordcount) <= staff.record)
                    {
                        $("#Pagination").hide();
                    }
                    else{
                        staff.issearch = false;
                        var optInit = staff.pageInit();
                        $("#Pagination").show();
                        $("#Pagination").pagination(returndata.recordcount,optInit);
                        staff.issearch = true;
                    }
                }
        	 	 	  else
        	 	 	  {
        	 	 	  	staff.issearch = true;
        	 	 	  }
                staff.full_staff_table(returndata.datasource);
            }
        });
    },
    full_staff_table:function(data)
    {
        var html = Array();
        var len = data.length;
        if ( len==0)
        {
           html.push("<span class='mb_common_table_empty'>未搜索到指定的账号信息！</span>");
        }
        else
        {
            for(var i=0;i<len;i++)
            {
                var row = data[i];
                html.push("<tr style='height:28px;line-height:28px;' e_mail='"+row.e_mail+"' login_account='"+row.login_account+"' jid='"+row.jid+"' dept_id='"+row.dept_id+"' dept_name='"+row.dept_name+"' sex='"+row.sex+"' pageindex='"+ this.pageindex+"'>");
                html.push("  <td style='height:28px;padding-left:0px;' width='55'  align='center'><input onclick='staff.checked_selected(this);' class='check_box' type='checkbox' /></td>");
                html.push("  <td style='height:28px;' width='115' class='nick_name' align='left'>"+row.nick_name+"</td>");
                html.push("  <td style='height:28px;padding-left:0px;' width='45' class='sex' align='center'>"+row.sex+"</td>");
                html.push("  <td style='height:28px;' width='105' class='duty' align='left'>"+row.duty+"</td>");
                html.push("  <td style='height:28px;' width='115' class='mobile' align='left'>"+row.mobile+"</td>");
                html.push("  <td style='height:28px;' width='222' align='left'>"+row.e_mail+"</td>");
                html.push("  <td style='height:28px;padding-left:0px;' width='100' align='center'>");
                html.push("     <span class='edit_info' onclick='staff.viewdialog(this,\"staff\");'>编辑</span>");
                html.push("     <span class='dele_info' onclick='staff.remove(this,0);'>删除</span>");
                html.push("</tr>");
            }             
        }
        if ( this.pagetype=="new")
          $("#staff_body table tbody").html(html.join(""));               
        else
        {
            $("#staff_body table tbody tr:visible").hide();
            $("#staff_body table tbody").append(html.join("")); 
        }
            
    },
    remove:function(ev,type)
    {
        //type:0表示单个;1表示选中的多个
        $("#window_hint .delete_hint").hide();
        if ( type==0)
          this.login_account = $(ev).parents("tr").attr("login_account");
        if ( type==0)
            html = "确定要删除用户账号<span style='font-weight:bold;color:#0088cc;padding-left:2px;padding-right:2px;'>"+this.login_account+"</span>";
        else
            html = "确定要删除选中的<span style='font-weight:bold;color:#0088cc;padding-left:2px;padding-right:2px;'>多个</span>用户吗？";
        $("#window_hint .showmessage").html(html);
        $("#window_hint").attr("remove_type","staff");
        $("#window_hint").attr("single",type);
        $("#window_hint").show();
    },
    del:function(){
        var account = Array();
        var single = $("#window_hint").attr("single");
        if ( single =="0")
            account.push(this.login_account);
        else
        {
            var checkbox = $("#staff_body tbody tr").find(".check_box[checked='checked']").parents("tr");
            for(var i=0;i < checkbox.length;i++)
            {
                account.push(checkbox.eq(i).attr("login_account"));
            }
        }
        var html = "<img src='/bundles/fafatimewebase/images/loading.gif'/><span>正在删除……</span>";
        $("#window_hint .delete_hint").html(html);
        $("#window_hint .delete_hint").show();        
        $.post(staff.dele_url,{"login_account":account},function(data) {
            if (data.success){  	 
                html = "<span>删除成功</span>";
                $("#window_hint .delete_hint").html(html);
                if (single=="0")
                   $("#staff_body table tbody tr[login_account='"+staff.login_account+"']").remove();
                else
                    $("#staff_body tbody tr").find(".check_box[checked='checked']").parents("tr").remove();
                setTimeout(function() { $("#window_hint .delete_hint").html("").hide();;$("#window_hint").hide(); },2000);
            }
            else{
               html = "<span>删除失败</span>";
               $("#window_hint .delete_hint").html(html);	 
            }
        });
    },
    viewdialog:function(evn,type) {
        this.showhint("");
        $("#dept_area").hide();
        $("#staff_edit").attr("type",type);
        if ( type=="staff")
        {
            $("#staff_field").show();
            $("#movedept_staff").hide();
            $("#pass").val("");
            this.login_account="";        
            if ( $(evn).attr("class")=="edit_info")
            {
                var parents = $(evn).parents("tr");
                $("#staff_edit .title").text("修改用户信息");
                staff.login_account = $.trim(parents.attr("login_account"));
                staff.e_mail        = $.trim(parents.attr("e_mail"));
                var deptid = parents.attr("dept_id");
                $("#txtdept").attr("deptid",deptid);
                //性别的处理
                var sex = parents.attr("sex");
                $("#checkboxsex1,#checkboxsex0").attr("checked",false);
                if ( sex=="女")
                    $("#checkboxsex0").attr("checked",true);
                else
                    $("#checkboxsex1").attr("checked",true);
                var mobile = $.trim(parents.find(".mobile").text());
                if ( mobile != null && mobile !="")
                    $("#staff_edit #mobile").attr("disabled","disabled");
                else
                    $("#staff_edit #mobile").removeAttr("disabled");
                $("#staff_edit #mobile").attr("title","手机号只能由该员工通过手机客户端修改并重新绑定");
                $("#txtdept").val(parents.attr("dept_name"));
                $("#realName").val(parents.find(".nick_name").text());
                $("#account").val(staff.e_mail);
                $("#account").attr("login_account",staff.login_account);                
                $("#duty").val(parents.find(".duty").text());
                $("#mobile").val(mobile);
                //缓存用户信息
                this.staffinfo = {"deptid":deptid,"login_account":staff.login_account,"nick_name":$("#realName").val(),"sex":sex,"duty":$("#duty").val(),"mobile":$("#mobile").val()};
            }
            else{
                $("#staff_edit #mobile").removeAttr("disabled");
                $("#staff_edit #mobile").removeAttr("title");
                this.staffinfo = null;
                this.edit_row = null;
                $("#staff_edit .title").text("添加用户信息");
                $("#checkboxsex1").attr("checked",true);
                $("#password_row").show();
                $(".staff_add_row input").val("");
                $("#staff_edit #txtdept").val($(".header_dept_name>span").text());
                $("#staff_edit #txtdept").attr("deptid",$(".header_dept_name>span").attr("deptid"));
                $(".staff_add_hint").text("");
            }
        }
        else
        {
            $("#staff_edit .title").text("批量更改部门");
            $("#txtdept").val("");
            $("#txtdept").attr("deptid","");
            $("#staff_field").hide();
            $("#movedept_staff").show();
            var ctl = $("#staff_body tbody tr").find(".check_box[checked='checked']").parents("tr");
            var html = Array();
            for(var i=0;i< ctl.length;i++)
            {
                var childer = ctl.eq(i);
                html.push("<span class='movedept_staff' jid='"+childer.attr("jid")+"'>"+childer.find(".nick_name").text()+"<span onclick=\"$(this).parents('span').remove();\">X</span></span>");
            }
            $("#movedept_staff").html(html.join(""));
        }
        $("#staff_edit").show();
    },    
    validEmail:function(mail)
    {
        var result=false;
        var reg = /^[a-zA-Z0-9]+[a-zA-Z0-9_-]*(\.[a-zA-Z0-9_-]+)*@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)*\.([a-zA-Z]+){2,}$/;
        result = reg.test(mail);
        if(!result)
        {
            result = reg.test(mail);
        }
        return result;
    },
    movedept:function()
    {
        var deptid = $("#txtdept").attr("deptid");
        if ( deptid == "")
        {
            this.showhint("请选择部门");
            return;
        }
        else if ( deptid==department.deptid)
        {
            this.showhint("请选择其他部门，所选人员已属该部门了！");
            return;                        
        }
        var ctl = $("#movedept_staff>span");
        if ( ctl.length==0)
        {
            this.showhint("没有人员需要更改部门");
            return;            
        }
        var jid = Array();
        for(var i=0;i<ctl.length;i++)
        {
            jid.push(ctl.eq(i).attr("jid"));
        }        
        var para = { "deptid":deptid,"jid":jid };
        var parameter = { "module":"Backstage","action":"MoveDepart" ,"params":para};
        $.post(access_url,parameter,function(data) {
            if ( data.success)
            {
                staff.showhint("批量更新部门成功!");
                setTimeout(function() { 
                   department.loadTree(); 
                   $("#staff_edit").hide();
                },1000);
            }
            else
            {
                staff.showhint("批量更新部门失败!");
            }            
        });      
    },
    show_message:function()
    {
        var ctl = $("#staff_body tbody tr .check_box[checked='checked']");
        if ( ctl.length > 0)
        {
          $(".staff_sendMessage").show();
          staff.send_status = true;
          $(".sendmessage_hint").hide();
          $(".sendmessage_content").val("");
          $(".sendmessage_content").focus();
        }
    },
    send_status:true,
    sendMessage:function()
    {
        if ( !this.send_status ) return;
        var message = $.trim($(".sendmessage_content").val());
        if ( message=="")
        {
            $(".sendmessage_content").focus();
            $(".sendmessage_hint").html("请输入要发送的消息！").show();
            setTimeout(function() {
                $(".sendmessage_hint").html("").hide();
            },1000);
            return;
        }
        var jid=Array();
        var ctl = $("#staff_body tbody tr .check_box[checked='checked']").parents("tr");
        for(var i=0;i< ctl.length;i++)
        {
            jid.push(ctl.attr("jid"));
        }        
        var parameter = { "module":"Backstage","action":"SendMessage" ,"params":{"jid":jid,"message":message }};
        this.send_status = false;
        $.post(access_url,parameter,function(data) {
            staff.send_status = true;
            if ( data.success)
            {
                $(".sendmessage_content").val("");
                $(".sendmessage_content").focus();
                $(".sendmessage_hint").html("消息发送成功！").show();
                setTimeout(function() {
                  $(".sendmessage_hint").html("").hide();
                },1000);     
            }
            else
            {
               $(".sendmessage_hint").html("消息发送失败！").show();
            }
        });
    },
    ResetPass:function()
    {
        var pass1 = $("#resetpass1").val();
        var pass2 = $("#resetpass2").val()
        if ( pass1 == "")
        {
            $("#resetpwd_hind").html("请输入用户密码");
            $("#resetpass1").focus();
            return;
        }
        if ( pass2 == "")
        {
            $("#resetpwd_hind").html("请输入确认密码");
            $("#resetpass2").focus();
            return;
        }
        if ( pass1 != pass2)
        {
            $("#resetpwd_hind").html("两次密码不一致，请重新输入！");
            $("#resetpass1").focus();
            return;            
        }
        else if ( pass1.length<6)
        {
            $("#resetpwd_hind").html("密码至少为六位！");
            $("#resetpass1").focus();
            return;
        }
        $("#resetpwd_hind").html("");
        var login_account=Array();
        var jid=Array();
        var ctl = $("#staff_body tbody tr .check_box[checked='checked']").parents("tr");
        for(var i=0;i< ctl.length;i++)
        {
            login_account.push(ctl.attr("login_account"));
            jid.push(ctl.attr("jid"));
        }
        var para = { "login_account":login_account,"jid":jid,"password":pass1 };
        var parameter = { "module":"Backstage","action":"ResetPassWord" ,"params":para};
        $.post(access_url,parameter,function(data) {
            if ( data.success)
            {
                $("#resetpwd_hind").html("重置密码成功！");
                setTimeout(function()
                {
                    $("#resetpwd_hind").html("");
                    $("#reset_pwd").hide();
                },2000);
            }
            else
            {
                $("#resetpwd_hind").html("重置密码失败！");
            }            
        });
    },
    Save:function()
    {
        var type = $("#staff_edit").attr("type");
        if ( type=="movedept")
        {
            this.movedept();
            return;
        }
        var state =  staff.login_account == "" ? "add":"edit";
        if ( $.trim($("#realName").val())=="")
        {
            $("#realName").focus();
            this.showhint("请输入用户姓名！");
            return;
        }
        if ( $.trim($("#account").val())=="")
        {
            $("#account").focus();
            this.showhint("请输入登录账号！");
            return;
        }        
        var pass = $.trim($("#pass").val());
        if ( state=="add" && pass =="")
        {
            $("#pass").focus();
            this.showhint("请输入登录密码！");
            return;
        }        
        if ( pass!="" && pass.length<6)
        {
            $("#pass").focus();
            this.showhint("登录密码必须最小达到6位！");
            return;
        }
        if(!this.validEmail($("#account").val()))
        {
            $("#account").focus();
            this.showhint("登录账号为邮箱格式，请检查！");
            return;
        }
        var sex = "";
        if ( $("#checkboxsex1").attr("checked")==null && $("#checkboxsex0").attr("checked")==null){
            this.showhint("请选择性别！");
            return;
        }
        else if ($("#checkboxsex1").attr("checked")==null)
            sex = "女";
        else
            sex = "男";
        var mobile = $.trim($("#mobile").val());
        if ( mobile != "")
        {
            if ( mobile.length != 11)
            {
                this.showhint("手机号必须为11位的数字！");
                $("#mobile").focus();
                return;
            }
        }
        else if ( state =="edit" && mobile=="")
        {
            this.showhint("请输入手机号码？");
            $("#mobile").focus();
            return;
        }
        //参数
        var parameter = { "state":state,"dept_id":$("#txtdept").attr("deptid"),"mobile":mobile,
                          "nick_name":$.trim($("#realName").val()),"login_account":this.login_account,
                          "password":$("#pass").val(),"sex":sex,"duty":$.trim($("#duty").val()),
                          "e_mail":$.trim($("#account").val())
                        };
        if ( parameter.state=="add")
           parameter.login_account = parameter.e_mail;
        //判断是否修改了内容
        if ( state == "edit" && this.login_account==parameter.e_mail  && this.login_account==parameter.login_account && this.staffinfo.deptid==parameter.dept_id && this.staffinfo.nick_name==parameter.nick_name && this.staffinfo.sex==parameter.sex && this.staffinfo.duty==parameter.duty &&  this.staffinfo.mobile==parameter.mobile && parameter.password=="")
        {
            this.showhint("你未修改任何数据项！");
            return;
        }
        $("#btnSave").attr("disabled","disabled");
        $("#btnCancle").attr("disabled","disabled");
        $.post(staff.update_url,parameter,function(data){
            $("#btnSave").removeAttr("disabled");
            $("#btnCancle").removeAttr("disabled");
            if (data.success)
            {
                if ( state=="edit")
                {
                    var cur_row = $("#staff_body table tr[login_account='"+parameter.login_account+"']");
                    cur_row.attr("dept_name",$.trim($("#txtdept").val()));
                    cur_row.attr("dept_id",parameter.dept_id);                    
                    cur_row.attr("sex",parameter.sex);
                    cur_row.find(".nick_name").text(parameter.nick_name);
                    cur_row.find(".duty").text(parameter.duty);
                    cur_row.find(".mobile").text(parameter.mobile);
                    $("#staff_edit").hide();
                }
                else
                {
                    var html=Array();
                    html.push("<tr style='height:28px;line-height:28px;' e_mail='"+parameter.login_account+"' login_account='"+parameter.login_account+"' dept_id='"+parameter.dept_id+"' dept_name='"+$.trim($("#staff_edit #txtdept").val())+"' sex='"+parameter.sex+"'>");
                    html.push("  <td style='height:28px;padding-left:0px;' width='55'  align='center'><input type='checkbox' /></td>");
                    html.push("  <td style='height:28px;' width='115' class='nick_name' align='left'>"+parameter.nick_name+"</td>");
                    html.push("  <td style='height:28px;padding-left:0px;' width='45' class='sex' align='center'>"+parameter.sex+"</td>");
                    html.push("  <td style='height:28px;' width='105' class='duty' align='left'>"+parameter.duty+"</td>");
                    html.push("  <td style='height:28px;' width='115' class='mobile' align='left'>"+parameter.mobile+"</td>");
                    html.push("  <td style='height:28px;' width='222' align='left'>"+parameter.login_account+"</td>");
                    html.push("  <td style='height:28px;padding-left:0px;' width='100' align='center'>");
                    html.push("     <span class='edit_info' onclick='staff.viewdialog(this);'>编辑</span>");
                    html.push("     <span class='dele_info' onclick='staff.remove(this);'>删除</span>");
                    html.push("</tr>");        
                    var len = $("#staff_body table tbody tr").length;
                    if (len == 0)
                    {
                        $("#staff_body table tbody").html(html.join(""));
                    }
                    else
                    {
                        if ( len  == staff.record)
                          $("#staff_body table tbody tr:last").remove();
                        $("#staff_body table tbody tr:first").before(html.join(""));
                    }
                    $(".staff_add_hint").text("添加用户账号成功！");
                    //初始化
                    $("#checkboxsex1").attr("checked",true);
                    $("#account").css("background-color","white");
                    $(".staff_add_row input").val("");
                    $("#txtdept").attr("deptid","");
                    setTimeout(function() { $(".staff_add_hint").text("");},2000);
                }
            }
            else{
                staff.showhint(data.msg);
            }
        });
    },
    showhint:function(message)
    {
        $("#staff_edit .staff_add_hint").text(message);
        setTimeout(function() { $("#staff_edit .staff_add_hint").text("");},3000);
    },
    checkbox_selected:function(evn)
    {
        var state = $(evn).attr("checked");
        var checkControl = $("#staff_body tbody tr:visible").find(".check_box");
        if ( state == null)
            checkControl.attr("checked",false);
        else
            checkControl.attr("checked",true);
        if ( $("#staff_body tbody tr").find(".check_box[checked='checked']").length > 0)
            $(".batch_area").show();
        else
            $(".batch_area").hide();
    },
    checked_selected:function(evn)
    {
        var state = $(evn).attr("checked");
        if ( state == null)
            $(evn).attr("checked",false);
        else
            $(evn).attr("checked",true);
        if ( $("#staff_body tbody tr").find(".check_box[checked='checked']").length==0)
        {
            $(".batch_area").hide();
        }
        else
        {
            if ( $("#staff_body tbody tr:visible").find(".check_box[checked='checked']").length==staff.record)
               $("#all_selected").attr("checked",true);
            else
               $("#all_selected").attr("checked",false);
            $(".batch_area").show();
        }
    }    
}

var staff_import = {
	 import_url:"",
	 sql_url:"",
	 search_url:"",
	 datasource:null,
	 import_type:1,
	 filepath:"",
	 totalrecord:"",
	 totalpage:0,
	 search_state:true,
	 isDel:0, //是否删除文件标
	 Field : {
            	 login_account:{"zh_name":"用户账号","index":"","isNull":0},
            	 nick_name:    {"zh_name":"用户昵称","index":"","isNull":0},
            	 dept_id:      {"zh_name":"部门ID或名称","index":"","isNull":1},
            	 password:     {"zh_name":"用户密码","index":"","isNull":0},
            	 mobile:       {"zh_name":"手机号码","index":"","isNull":1},
            	 duty:         {"zh_name":"职务","index":"","isNull":1}
            },
	 selectdept:function(ev){
	 	 var dept_id = $(ev).attr("dept_id");
		 $("#text_parent_name").attr("dept_id",dept_id);
		 $("#text_parent_name").val($(ev).text());
		 $(".depart_searchArea").hide();
	 },
	 show_import:function(){
	    
	    staff_import.step('P');
	 	   $("#view_staff_import").show();
	 },
	 step:function(state){
	    $("#view_staff_import #filedata").val("");
	 	  $("#view_staff_import .staffmanager_hint").html("");
	 	  if ( state=="N"){
    	 	 	 $("#view_staff_import .staff_field_setting,#view_staff_import .staff_field_content").hide();
    	 	 	 $("#view_staff_import #select_datasorce tbody").html("");
    	 	 	 $("#view_staff_import #btnPre").show();
    	 	 	 $("#view_staff_import #btnNext").hide();
           var selectval = $("#excel_type1").attr("checked");
           if ( selectval!=null ){
           	 this.import_type = 1;
           	 $("#excel_setting1").show();
           	 return;     	      	      	 
           }
           selectval = $("#ldap_type1").attr("checked");
             if ( selectval!=null ){
             this.import_type = 2;
           	 $("#excel_setting1").show();
           	 return;
           }
           selectval = $("#view_staff_import #database_type").attr("checked");
             if ( selectval!=null ){
             this.import_type = 3;
           	 $("#database_setting1").show();
           	 return;	      	      	 
           }
           selectval = $("#view_staff_import #api_type").attr("checked");
             if ( selectval!=null ){
             	 this.import_type = 4;
             	 $("#api_setting1").show();
           	   return;	      	      	 
           }
	 	  }
	 	  else if ( state=="P"){
	 	 	  $("#view_staff_import #btnPre").hide();
	 	 	  $("#view_staff_import #btnNext").show();
	 	  }
	 },
	 checkinput:function(id){
	 	  if (id=="excel_type"){
	 	  	var file = $("#filedata").val();
	 	 	 	if ( file == ""){
	 	 	 		 this.showhint("请选择导入的Excel文件！",true);
	 	 	 		 return false;
	 	 	 	}
	 	 	 	else{	 	 	 		 
	 	 	 		 var suffix = file.substring(file.lastIndexOf(".")+1);
	 	       if ( suffix!="xls" && suffix!="xlsx"){
	 	 	        this.showhint("请选择Excel文件(*.xls或*.xlsx)！",true);
	 	 	       return false;
	 	       }
	 	 	 	}
	 	 	}
	 	 	else if (id=="lsap_type"){
	 	 	}
	 	 	else if (id=="database_type"){
	 	 	}
	 	 	else if (id = "api_type"){
	 	 	}
	 	 return true;
	 },
	 showhint:function(message,isempty){
	 	 if ( message == null || message =="")
	 	  $(".staffmanager_hint").text("");
	 	 else {
	 	 	$(".staffmanager_hint").html(message);
	 	 	if ( isempty )
	 	 	  setTimeout(function(){ $(".staffmanager_hint").text(""); },2000);
	 	 }
	 },
	 viewData:function(){
	 	 //初始相关操作
	 	 staff_import.Field.login_account = {"zh_name":"用户账号","index":"","isNull":1};
	   staff_import.Field.nick_name     = {"zh_name":"用户昵称","index":"","isNull":0},
	   staff_import.Field.dept_id       = {"zh_name":"部门ID或名称","index":"","isNull":1},
	   staff_import.Field.password      = {"zh_name":"用户密码","index":"","isNull":0};
	   staff_import.Field.mobile        = {"zh_name":"手机号码","index":"","isNull":1};
	   staff_import.Field.duty          = {"zh_name":"职务",    "index":"","isNull":1};
	   $("#view_staff_import .staff_field_content").hide();
	   $("#view_staff_import .staff_field_row .mb_combox").html("");	 	 
	 	 
	 	 $("#view_staff_import #select_datasorce tbody").html("");
	 	 var html_body = new Array();
	 	 var data = this.datasource;
	 	 var  row = null;
	 	 var td_style = "";
	 	 if ( data!=null && data.length>0){
	 	 	 $("#view_staff_import .staff_field_setting").show();
		 	 for(var i=0;i< data.length;i++){
		 	 	 row = data[i];
		 	 	 if ( i== 0 ){
		 	 	 	 var temphtml = new Array();
		 	 	 	 var len = 0;
		 	 	 	 for(j in row){
		 	 	 	 	 len += 1;
		 	 	 	 }
		 	 	 	 var td_width = 100 / len;
		 	 	 	 td_style = "style='float:left;width:"+td_width+"%;'";
		 	   	 html_body.push("<tr class='staff_table_head'>");
		 	   	 temphtml.push("<tr class='staff_table_row'>");
		 	   	 for(j in row){
		 	   	 	 html_body.push("<td title='设置映射字段' "+td_style+">"+j+"</td>");
		 	   	 	 temphtml.push("<td "+td_style+">"+ row[j] +"</td>");	 	   	 	 
		 	   	 }
		 	   	 html_body.push("</tr>");
		 	   	 temphtml.push("</tr>");
		 	   	 html_body.push(temphtml.join(""));  	 
		 	   }
		 	   else{
		 	   	 html_body.push("<tr class='staff_table_row'>");
		 	   	 for(k in row){
		 	   	 	 html_body.push("<td "+td_style+">"+ row[k] +"</td>");	 	
		 	   	 }
		 	   	 html_body.push("</tr>");
		 	   }
		 	 }
	   }
	   else{
	   	 $("#view_staff_import .staff_field_setting").hide();
	   }
	 	 $("#view_staff_import #select_datasorce tbody").html(html_body.join(""));
	 },
	 //显示字段映射关系面板
	 viewField:function(){	 	 	 
	 	 $("#view_staff_import .staff_field_content").show();
     var html = new Array();
     if ( $("#view_staff_import .staff_field_content .staff_field_row").length==0){
			 for(j in staff_import.Field){
			 	 var item = staff_import.Field[j];
			 	 html.push("<div class='staff_field_row'>");
			 	 html.push("<div>");
			 	 var must = false;
			 	 var fieldname = "";
			 	 for(i in item){
			 	 	 if ( i=="zh_name"){
			 	 	  html.push("<span class='staff_fieldname'>请指定<span style='padding-left:2px;padding-right:2px;color:black;'>"+item[i]+"</span>字段映射关系：</span>");
			 	 	  fieldname = item[i];
           }
			 	 	 if ( i=="isNull" && item[i]==0){
			 	 	 	 must = true;
			 	 	   html.push("<span class='staff_field_desc'>*该字段必须指定映射关系</span>");
			 	 	 }
			 	 }
			 	 html.push("</div>")
			 	 html.push("<select class='mb_combox' must='"+(must ? 1:0)+"' fieldname='"+fieldname+"' field='"+j+"'></select>");
			 	 html.push("</div>");
			 }
			 $("#view_staff_import .staff_field_content #setcontent").html(html.join(""));
			 this.loadCombox();
		 }
		 else{
		 	 if ($("#view_staff_import .staff_field_row .mb_combox:first").children().length==0)
		 	  this.loadCombox();
		 }		 		
	 },
	 loadCombox:function(){
	 	 var html = new Array();
	 	 html.push("<option value=''></option>");
	 	 var data = this.datasource;
	 	 if ( data != null && data.length>0){
	 	 	 for(var i=0;i<1;i++){
	 	 	 	 var row = data[i];
	 	 	 	 var index = 0;
	 	 	 	 for(j in row){
	 	 	 	 	 html.push("<option value='"+index+"'>"+j+"</option>");
	 	 	 	 	 index++;
	 	 	 	 }
	 	 	 } 	 	 
	 	 }
	 	 $("#view_staff_import .staff_field_row .mb_combox").html(html.join(""));
	 }, 
	 setField:function(){
	    $("#view_staff_import #btn_start_import").attr("start","0");
	 	  var control = $("#view_staff_import .staff_field_row .mb_combox");
	 	 //判断是否选择
	 	 var val = "";
	 	 var flag = 0;
	 	 for(var i=0;i<control.length;i++){
	 	 	 val =  control.eq(i).val();
	 	 	 if ( val==""){
	 	 	    var filedname = control.eq(i).attr("fieldname");
	 	 	    if ( control.eq(i).attr("must")=="1")
	 	 	    {
    	 	 	    $(".setfield_hint").text("必须指定【"+filedname+"】字段的映射关系！");
    	 	 	 	  control.eq(i).focus();
    	 	 	 	  return;
	 	 	 	  }
	 	 	 	  else
	 	 	 	  {
	 	 	 	     if ( filedname=="用户账号" || filedname=="手机号码")
	 	 	 	       flag += 1;
	 	 	 	  }
	 	 	 }
	 	 }
	 	 if ( flag==2)
	 	 {
	 	    $("#view_staff_import .setfield_hint").text("【用户账号】或【手机号码】必须选择一项！");
    	 	$("#view_staff_import #setcontent select:first").focus();
    	 	return;
	 	 }
	 	 staff_import.Field.login_account.index = "";
	 	 staff_import.Field.nick_name.index = "";
	 	 staff_import.Field.dept_id.index = "";
	 	 staff_import.Field.password.index = "";
	 	 staff_import.Field.mobile.index = "";
	 	 staff_import.Field.duty.index = "";
     //更改Field对象
	 	 for(var j=0;j<control.length;j++){
	 	 	 val = control.eq(j).val();
	 	 	 var field = control.eq(j).attr("field");
	 	 	 if ( field=="login_account")
	 	 	   staff_import.Field.login_account.index = val;
	 	 	 if ( field=="nick_name")
	 	 	   staff_import.Field.nick_name.index = val;	 
	 	 	 if ( field=="dept_id")
	 	 	   staff_import.Field.dept_id.index = val;
	 	 	 if ( field=="password")
	 	 	   staff_import.Field.password.index = val;
	 	 	 if ( field=="mobile")
	 	 	   staff_import.Field.mobile.index = val;		 	 	
	 	 	 if ( field=="duty")
	 	 	   staff_import.Field.duty.index = val;
	 	 }
	 	 $("#view_staff_import #btn_start_import").attr("start","1");
	 	 $(".staff_field_content").hide();
	 }, 
	 //开始导入数据
	 start:function(index){
	 	  if ( staff_import.filepath=="" || staff_import.filepath==null)
	 	  {
	 	  	staff_import.showhint("请选择要导入的Excel文件！" ,false); 
	 	  	return;
	 	  }
	    if ( $("#view_staff_import #btn_start_import").attr("start")=="0")
	    {
	        staff_import.showhint("请选择对应的映射关系！" ,false);
	        return;
	    }	 	  
	 	  if ($(".loading").length>0) $(".loading").remove();	 	  
	 	  $(".register_progress").show();	 	  
	 	  $("#btn_start_import").attr("disabled","disabled");
	 	  var parameter = { "relation":staff_import.Field,"file":staff_import.filepath,"totalrecord":staff_import.totalrecord,"index":index,"isDel":0 };
	 	 	$.post(this.import_url,parameter,function(data){
	 	 		  //处理错误数据
	 	 		  if ( index==1)
				 	{
				 		$(".error_content").html("");
				  }
 	 		  	var errordata = data.errorData;
 	 		  	if ( errordata.length>0)
 	 		  	{
 	 		  		 var html = Array();
 	 		  		 for(var i=0;i<errordata.length;i++)
 	 		  		 {
 	 		  		 	 html.push("<span>"+errordata[i]+"</span>");
 	 		  		 }
 	 		  	   $(".error_content").html(html.join(""));
 	 		    }
 	 		     	 		    
	 	 		  var pageindex = data.index;
		 	 	 	if ( pageindex <= staff_import.totalpage){
		 	 	 	 	 staff_import.start(pageindex);
		 	 	 	 	 var percent = Math.round(pageindex/staff_import.totalpage*100);
		 	 	 	 	 $(".register_progress_number").text(percent+"%");
		 	 	 	 	 $(".register_progress_percent").css("width",percent+"%");
		 	 	 	}
		 	 	 	else {
            //是否有错误提示
		 	 	 		if ( $(".error_content").children().length==0)
		 	 	 		{
		 	 	 			staff_import.showhint("导入人员数据成功！" ,false);
		 	 	 		}
		 	 	 		else
		 	 	 		{
		 	 	 		  var err_title ="<span>操作已完成，但有<span onclick=\"$('.error_box').toggle();\" class='error_text'>错误</span>发生。</span>";
		 	 	 			$(".staffmanager_hint").html(err_title);
		 	 	 			$('.error_box').show();		 	 	 			
		 	 	 		}
		 	 	 		$("#btn_start_import").removeAttr("disabled");
		 	 	 	 	$(".register_progress_number").text("100.00%");
		 	 	 	  $(".register_progress_percent").css("width","100%");		 	 	 	 	 
		 	 	 	 	setTimeout(function() {
		 	 	 	 		 $(".register_progress").hide();
		 	 	 	 		 $(".register_progress_number").text("");
		 	 	 	     $(".register_progress_percent").css("width","0px");
		 	 	 	  },2000);
		 	 	 		//删除文件
		 	 	 		var para = { "relation":staff_import.Field,"file":staff_import.filepath,"totalrecord":staff_import.totalrecord,"index":index,"isDel":1};
	 	 	      $.post(staff_import.import_url,para,function(data){
	 	 	      	 staff_import.filepath = "";
	 	 	      	 $("#filedata").val("");
		 	 	 	  });
		 	 	 	}
	 	 	});
	 },
	 ExecSQL:function(){
	 	 var dbtype = $("#dbtype").val();
	 	 if (dbtype==""){
	 	 	 this.showhint("请选择【数据库类型】",true);
	 	 	 return;
	 	 }
	 	 var server = $.trim($("#text_server").val());
	 	 if ( server==""){
	 	 	 this.showhint("请输入【数据库服务器】",true);
	 	 	 $("#text_server").focus();
	 	 	 return;
	 	 }
	 	 var database = $.trim($("#text_database").val());
	 	 if ( database==""){
	 	 	 this.showhint("请输入【数据库名】",true);
	 	 	 $("#text_database").focus();
	 	 	 return;
	 	 }	 	 
	 	 var user = $.trim($("#text_user").val());
	 	 if ( user==""){
	 	 	 this.showhint("请输入【用户账号】",true);
	 	 	 $("#text_user").focus();
	 	 	 return;
	 	 }		 	 
	 	 var pass = $.trim($("#text_password").val());
	 	 if ( pass==""){
	 	 	 this.showhint("请输入【用户密码】",true);
	 	 	 $("#text_password").focus();
	 	 	 return;
	 	 }	 	 
	 	 var sqlcomment = $.trim($("#text_sql").val());
	 	 if ( sqlcomment==""){
	 	 	 this.showhint("请输入【查询SQL语句】",true);
	 	 	 $("#text_sql").focus();
	 	 	 return;
	 	 }
	 	 var parameter = {  "dbtype":dbtype,"url":server,"dbname":database,"dbuser":user,"dbpwd":pass,"sqlcomment":sqlcomment};
	 	 $.post(this.sql_url,parameter,function(data){
	 	 	 	 if ( data.success){
		        staff_import.datasource = data.DataSource;
		        staff_import.viewData();
	       }
	       else{
		       staff_import.showhint(data.msg,false);
	       }
	 	 });
	 	  	 
	 },
   //上传excel文件
   uploadfile:function(){
    	var file = $("#filedata").val();
    	if ( file == ""){
    		 staff_import.showhint("请选择导入的Excel文件！",true);
    		 return;
    	}
    	else{	 	 	 		 
    	   var suffix = file.substring(file.lastIndexOf(".")+1);
       if ( suffix!="xls" && suffix!="xlsx"){
           staff_import.showhint("请选择Excel文件(*.xls或*.xlsx)！",true);
          return;
       }
    	}
    	$("#view_staff_import #btn_start_import").attr("start","0");
      $("#frm_staff_import").submit();
   }
};

//上传文件后的返回数据
function staff_import_callback(data)
{
	if ( data.success){
		staff_import.datasource = data.DataSource;
		staff_import.filepath = data.filepath;
		staff_import.totalrecord = data.recordcount;
		staff_import.totalpage = data.total_page;
		staff_import.viewData();
		var row = data.recordcount > 1 ? data.recordcount - 1 : 0;
		staff_import.showhint("上传文件共计"+ row +"条数据记录，目前仅显示前"+data.DataSource.length+"条数据记录！" ,false);
	}
	else{
		 staff_import.showhint("上传文件出现错误，请重试！",false);
	}
}


var department =
{
    editorder_url:"",
    getdepart_url:"",
    add_url:"",
    dele_url:"",
    record:12,
    search_state:false,
    issearch:false,
    deptid:"",
    deptname:"",
    dept_info: {"dept_id":"","parent_dept_id":"","dept_name":"","parent_dept_name":"","friend":0,"show":0,"manager_jid":""},
    pageindex:1,
    remove_deptid:"", 
    loadTree:function(){
        var parameter = { "module":"Backstage","action":"searchdeptByTree" ,"params":{"number":1}};
        $(".loadding_tree").html("<img src='/bundles/fafatimewebase/images/loading.gif' /><span>正在加载组织机构</span>");
	      $(".loadding_tree").show();
        $("#tree_department").hide();
        $.post(access_url,parameter,function(data) {
            $(".loadding_tree").hide();
            $("#tree_department").show();
        	  if (data.success){
        	    var zTreeSetting = {
        	    	data:{
        	    		simpleData:{
        	    			enable:true
        	    		}
        	    	},
        	    	callback:{
        	    		onClick:department.TreeClick
        	    	}
        	    };
        		  $.fn.zTree.init($("#tree_department"), zTreeSetting, data.datasource);
        		  var treeObj = $.fn.zTree.getZTreeObj("tree_department");
        		  var nodes = treeObj.getNodes();
        		  if ( nodes.length>0)
        		  {
        		     MarkObj.setMark(nodes[0].children);
        		  }
        	  }
        	  //加载人员列表及
        	  var root = "v"+enterprise_eno;
        	  staff.deptid=root;
        	  staff.nick_name = "";
        	  staff.search_staff(1,"new");
        	  //加载根节点下子部门
        	  department.deptname = "";
        	  department.deptid = root;
        	  department.search_dept(1);
        });
    },
    TreeClick:function(event, treeId, treeNode)
    {
         var id = treeNode.id;
         if ( !staff.search_state && id!=staff.deptid)
         {
            var deptname = treeNode.name.split("(");
            var dept_name = treeNode.name.replace("("+deptname[deptname.length-1],"");
            $(".header_dept_name>span").html(dept_name);
            $(".header_dept_name>span").attr("deptid",id);
            $(".header_dept_name>span,.header_dept_name>a").show();
            if ( treeNode.pId==null || (treeNode.pId=='v'+enterprise_eno && dept_name=="体验部门" ))
               $(".header_dept_name>a").hide();
            $(".header_dept_name>input").hide();
            $("#update_dept_button").hide();
             //查询人员
             $("#text_search_account").val("");
             staff.nick_name = "";             
             staff.deptid = id;
             staff.search_staff(1,"new");
         }
         if ( !treeNode.isParent)
         {
         	 if ( treeNode.state != "0")
         	 {
             	 var para = {"deptid":treeNode.id,"number":1 };
             	 var parameter = { "module":"Backstage","action":"searchdeptByTree" ,"params":para };
               $.post(access_url,parameter,function(data) {
             	 	  if (data.success)
             	 	  {
             	 	  	if (data.datasource.length==0)
             	 	  	{
             	 	  		treeNode.state = 0;
             	 	  	}
             	 	  	else
             	 	  	{
                        var treeObj = $.fn.zTree.getZTreeObj("tree_department");
                        treeObj.addNodes(treeNode,data.datasource);
                        treeNode.state = 0;
                        MarkObj.setMark(treeNode.children);
                    }
                  }
             	 });
           }
         }
         //查询部门数据
          if ( !department.search_state && id!=department.deptid)
          {
              $("#text_search_deptname").val("");
              department.deptname = "";
              department.deptid = id;
              department.search_dept(1);
          }
    },
    pageselectCallback:function(page_index){
        if ( department.issearch )
            department.search_dept(page_index+1);
    },
    pageInit:function(){
        var opt = {callback: department.pageselectCallback};
        opt.items_per_page = department.record;
        opt.num_display_entries = 5;
        opt.num_edge_entries=5;
        opt.prev_text="上一页";
        opt.next_text="下一页";
        return opt;
    },
    searchdata:function()
    {        
        department.deptname = $.trim($("#text_search_deptname").val());
        if ( department.deptname !="")
          department.deptid="";
        department.search_dept(1);
    },
    search_dept:function(pageindex)
    {
        if ( this.search_state==true) return;
        this.search_state = true;
        this.pageindex = pageindex;
        var parameter = { "pageindex":pageindex,"pid":this.deptid,"dept_name":this.deptname,"record":this.record };     	    
        var method_parameter = { "module":"backstage","action":"search_depart" ,"params":parameter }
        $.post(access_url,method_parameter,function(returndata) {
            department.search_state = false;
            if ( returndata.success)
            {
                if ( pageindex==1 )
                {
                    if ( parseInt(returndata.recordcount) <= department.record)
                    {
                        $("#dept_page").hide();
                    }
                    else{
                        department.issearch = false;
                        var optInit = department.pageInit();
                        $("#dept_page").show();
                        $("#dept_page").pagination(returndata.recordcount,optInit);
                        department.issearch = true;
                    }
                }
        	 	 	  else
        	 	 	  {
        	 	 	  	department.issearch = true;
        	 	 	  }
                department.full_dept_table(returndata.datasource);
            }
        });
    },
    search_manager:function()
    {
        var name = $.trim($(".search_manager_header input").val());
        var method_parameter = { "module":"backstage","action":"search_manager" ,"params":{"name":name}};
        $.post(access_url,method_parameter,function(returndata) {
            var html = Array();
            if ( returndata.success)
            {
                var listdata = returndata.listdata;
                if ( listdata.length==0)
                {
                }
                else
                {
                    for(var i=0;i<listdata.length;i++)
                    {
                        var row = listdata[i];
                        html.push("<li jid='"+row.jid+"' onclick='department.selected_manager(this);'>"+row.nick_name+"</li>");
                    }
                }
                $(".search_manager_area>ul").html(html.join(""));
            }
        });
    },
    selected_manager:function(evn)
    {
        $("#text_dept_manager").val($(evn).text());
        $("#text_dept_manager").attr("jid",$(evn).attr("jid"));
        $(".search_manager_area").hide();
    },
    full_dept_table:function(data)
    {
        var html = Array();
        var len = data.length;
        if ( len==0)
        {
           html.push("<span class='mb_common_table_empty'>未搜索到下级部门！</span>");
        }
        else
        {
            for(var i=0;i<len;i++)
            {
                var row = data[i];
                html.push("<tr style='height:28px;line-height:28px;' deptid='"+row.deptid+"' deptname='"+row.deptname+"' parent_dept_id='"+row.parent_dept_id+"' parent_dept_name='"+row.parent_dept_name+"' friend='"+ row.friend+"'>");
                html.push("  <td class='deptname' style='height:28px;' width='350' align='left'>"+row.deptname+"</td>");
                html.push("  <td class='manager' style='height:28px;' width='87' align='left' manager_jid='"+row.manager_jid+"'>"+row.manager+"</td>");
                html.push("  <td style='height:28px;padding-left:0px;' width='70' align='center'>"+row.number+"</td>");
                html.push("  <td style='height:28px;padding-left:0px;' width='100' align='left'>");
                html.push("    <input deptid='"+row.deptid+"' maxlength='10' onkeyup='department.enableNaN(this);' type='text' class='dept_noorder' value='"+row.noorder+"'/></td>");
                if ( row.show=="1")
                {
                    html.push("  <td title='单击设为隐藏' class='showhide' style='padding-left:0px;height:28px;cursor:pointer;' width='50' onclick='department.dialog_hint(this,\"setshow\");' state='1' align='center'><img src='/bundles/fafatimewebase/images/zq.png' /></td>");
                }
                else
                    html.push("  <td title='单击设为显示' class='showhide' style='padding-left:0px;height:28px;cursor:pointer;' width='50' onclick='department.dialog_hint(this,\"setshow\");' state='0' align='center'></td>");
                html.push("  <td style='height:28px;padding-left:0px;' width='100' align='center'>");
                html.push("     <span class='edit_info' onclick='department.edit(this);'>编辑</span>");
                html.push("     <span class='dele_info' onclick='department.dialog_hint(this,\"delete\");'>删除</span>");
                html.push("</tr>");
            }
        }
        $("#dept_body table tbody").html(html.join(""));               
    },
    enableNaN:function(evn)
    {
       var tmptxt= $.trim($(evn).val());
       if ( tmptxt != "")
       $(evn).val(tmptxt.replace(/\D|^0/g,''));
    },
    edit:function(ev) {
        
        $("#dept_edit #dept_area").hide();
        $(".search_manager_area").hide();     
        if ($(ev).attr("class")=="edit_info")
        {
            var editobj = $(ev).parents("tr");
            $("#dept_edit .title").text("修改组织部门");
            $("#dept_edit").show();
            department.dept_info.parent_dept_id = editobj.attr("parent_dept_id");
            department.dept_info.parent_dept_name = editobj.attr("parent_dept_name");
            department.dept_info.dept_id = editobj.attr("deptid");
            department.dept_info.dept_name = editobj.attr("deptname");
            department.dept_info.friend = editobj.attr("friend");
            department.dept_info.show = editobj.find(".showhide").attr("state");
            department.dept_info.manager_jid = editobj.find(".manager").attr("manager_jid");
            $("#text_parent_name").val(department.dept_info.parent_dept_name);            
            $("#text_parent_name").attr("deptid",department.dept_info.parent_dept_id);
            $("#text_dept_name").val(department.dept_info.dept_name);
            $("#text_dept_name").attr("deptid",department.dept_info.dept_id);
            $("#text_dept_manager").val(editobj.find(".manager").text());
            $("#text_dept_manager").attr("jid",department.dept_info.manager_jid);
            if ( department.dept_info.friend=="1")
               $("#check_friend").attr("checked",true);
            else
               $("#check_friend").attr("checked",false);
            if ( department.dept_info.show=="1")
               $("#check_show").attr("checked",true);
            else
               $("#check_show").attr("checked",false);
        }
        else
        {   
            $(".staff_add_row input").val("");
            $("#text_parent_name").val($(".header_dept_name>span").text()); 
            $("#text_parent_name").attr("deptid",$(".header_dept_name>span").attr("deptid"));
            $("#text_dept_name").attr("deptid","");
            $("#text_dept_manager").attr("jid","");
            $("#dept_edit .title").text("添加组织部门");            
            $(".staff_add_hint").text("");
            $("#check_friend,#check_show").attr("checked",false);
            this.dept_info = {"dept_id":"","parent_dept_id":"","dept_name":"","parent_dept_name":"","friend":0,"show":0,"manager_jid":""};
            $("#dept_edit").show();
        }
    },
    Save:function(){
        var parent_id = $("#text_parent_name").attr("deptid");
        if ( parent_id=="")
        {
            $("#text_parent_name").focus();
            this.showhint("请选择部门所属的上级部门");
            return;
        }
        var dept_name = $.trim($("#text_dept_name").val());
        if ( dept_name==""){
            $("#text_dept_name").focus();
            this.showhint("请输入部门名称!");
            return;
        }
        var friend = $("#check_friend").attr("checked") == null ? 0 : 1;
        var show = $("#check_show").attr("checked")==null ? 0 : 1;
        var manager_jid = $("#text_dept_manager").attr("jid");        
        var dept_info = this.dept_info;
        if (dept_info.dept_id!="")
        {
            if ( dept_info.parent_dept_id == parent_id && dept_info.dept_name == dept_name  && dept_info.show==show && dept_info.friend==friend && dept_info.manager_jid==manager_jid  )
            {
                this.showhint("您没有做任何修改!");
                $("#text_dept_name").focus();
                return;
            }
        }        
        var parameter = { "p_deptid":parent_id,"deptid":department.dept_info.dept_id,"deptname":dept_name,"manager":manager_jid,"friend":friend,"show":show };
        var method_parameter = { "module":"Backstage","action":"updateDepartment" ,"params":parameter }
        $.post(access_url,method_parameter,function(data) {
            if (data.success)
            {
                if ( parameter.deptid=="")
                {
                    department.showhint("添加组织部门成功！");
                    var html=Array();
                    html.push("<tr friend='"+parameter.friend+"' parent_dept_name='"+$("#text_parent_name").val()+"' parent_dept_id='"+parameter.p_deptid+"'");
                    html.push("    deptname='"+parameter.deptname+"' deptid='"+data.deptid+"' style='height:28px;line-height:28px;'>");
                    html.push("  <td align='left' style='height:28px;width:350px;' class='deptname'>"+parameter.deptname+"</td>");
                    html.push("  <td align='left' manager_jid='"+parameter.manager+"' style='height:28px;width:87px;' class='manager'>"+$("#text_dept_manager").val()+"</td>");
                    html.push("  <td align='center' style='height:28px;padding-left:0px;width:70px;'></td>");
                    html.push("  <td align='left' style='height:28px;padding-left:0px;width:100px;'>");
                    html.push("     <input type='text' value='"+ data.number+"' class='dept_noorder' onkeyup='department.enableNaN(this);' maxlength='10' deptid='"+data.deptid+"'></td>");
                    var showtitle = show==1 ? "单击设为隐藏":"单击设为显示";
                    html.push("  <td align='center' state='"+show+"' onclick='department.dialog_hint(this,\"setshow\");' style='padding-left:0px;height:28px;cursor:pointer;width:50px;' title='"+showtitle+"'>");
                    if ( show==1)
                        html.push(" <img src='/bundles/fafatimewebase/images/zq.png'></td>");
                    else
                        html.push("</td>");
                    html.push("<td align='center' style='width:100px;height:28px;padding-left:0px;'>");
                    html.push(" <span onclick='department.edit(this);' class='edit_info'>编辑</span>");
                    html.push(" <span onclick='department.dialog_hint(this,\"delete\");' class='dele_info'>删除</span></td></tr>");
                    var len = $("#dept_body table tbody tr").length;
                    if ( len == 0)
                    {
                        $("#dept_body table tbody").html(html.join(""));
                    }
                    else if ( len < department.record)
                    {
                        $("#dept_body table tbody tr:first").before(html.join(""));
                    }
                    else
                    {
                        $("#dept_body table tbody tr:last").remove();
                        $("#dept_body table tbody tr:first").before(html.join(""));
                    }
                    
                    $("#text_dept_name,#text_dept_manager").val("");
                    $("#text_dept_name").attr("deptid","");
                    $("#text_dept_manager").attr("jid","");                    
                    $("#check_friend").attr("checked",false);
                    $("#check_show").attr("checked",true);
                }
                else
                {	   	 	
                    var trObject =  $("#dept_body table tbody tr[deptid='"+parameter.deptid+"']");
                    trObject.find(".deptname").text(parameter.deptname);
                    trObject.find(".manager").text($.trim($("#text_dept_manager").val()));                    
                    trObject.find(".showhide").attr("state",show);
                    if ( show==0)
                    {
                        trObject.find(".showhide").html("");
                    }
                    else
                    {
                        trObject.find(".showhide").html("<img src='/bundles/fafatimewebase/images/zq.png' />");
                    }
                    trObject.attr("parent_dept_name",$("#text_parent_name").val());
                    trObject.attr("parent_dept_id",parameter.p_deptid);
                    trObject.attr("deptname",parameter.deptname);
                    $("#dept_edit").hide();
                }
            }
            else
            {
                department.showhint(data.msg);
            }
        });
	  },
	  showhint:function(message){
    		$("#dept_edit .staff_add_hint").text(message);
    		setTimeout(function() { $("#dept_edit .staff_add_hint").text("");},2000);
	  },
    edit_order:function(){
        var control = $("#dept_body table tbody tr input");
        var noorders = Array();
        var deptid="",val="";
        for(var i=0;i<control.length;i++){
            val = control.eq(i).val();
            if ( val=="") continue;
            deptid = control.eq(i).attr("deptid");
            noorders.push({"deptid":deptid,"noorder":val});
        }
        $.post(this.editorder_url,{"noorders":noorders},function(data){
            if ( data.success)
            {
                $("#window_hint .showmessage").html("更新顺序号成功");
                window.setTimeout(function(){
                  $("#window_hint .showmessage").html("");
                  $("#window_hint").hide();
                },1000);
            }
            else
            {
                $("#window_hint .showmessage").html("更新顺序号失败");
            }
        });
    },
    dialog_hint:function(evn,type){
       var html = "";
       $("#window_hint .delete_hint").html("").hide();
       if ( type=="delete")
       {
            this.remove_deptid = $(evn).parents("tr").attr("deptid");
            html = "确定要删除用户部门：<span style='font-weight:bold;color:#0088cc;padding-left:2px;padding-right:2px;'>"+$(evn).parents("tr").attr("deptname")+"</span>？";
            $("#window_hint").attr("remove_type","dept");
       }
       else if ( type=="setshow")
       {
          this.remove_deptid = $(evn).parents("tr").attr("deptid");
          var state = $(evn).attr("state");
          state = state=="1" ? "0" : "1";
          var desc = state=="1"?"显示":"隐藏";
          html = "是否设置<span style='font-weight:bold;color:#0088cc;padding-left:2px;padding-right:2px;'>"+$(evn).parents("tr").attr("deptname")+"</span>为<span style='padding:0 5px;color:#cc3300;'>"+desc+"</span>状态？";
          $("#window_hint").attr("remove_type","showhide");
          $("#window_hint").attr("showhide",state);
       }
       else
       {
          html = "是否更新<span style='font-weight:bold;color:#0088cc;padding-left:2px;padding-right:2px;'>"+$(".header_dept_name>span").text()+"</span>下的部门顺序号？";
          $("#window_hint").attr("remove_type","number");
       }
       $("#window_hint .showmessage").html(html);
       $("#window_hint").show();
	  },
	  setShowHide:function()
	  {
        var state = $("#window_hint").attr("showhide");
        var para = {"deptid":this.remove_deptid,"state":state };
        var parameter = { "module":"Backstage","action":"setDeptShowHide" ,"params":para };
        $.post(access_url,parameter,function(data) {
            if ( data.success)
            {
                var td = $("#dept_body tr[deptid='"+para.deptid+"']").find(".showhide");
                td.attr("state",state);
                if ( state=="1")
                {
                    td.html("<img src='/bundles/fafatimewebase/images/zq.png' />");
                    td.attr("title","单击设为隐藏");
                }
                else
                {
                    td.html("");
                    td.attr("title","单击设为显示");
                }
                $("#window_hint").hide();
            }
        });
	  },
	  searchinfo:{},
	  searchTree:function(pageindex,type)
	  {
	     if ( type=="search")
	     {
    	     var deptname = $.trim($("#tree_deptname").val());
    	     if ( deptname=="")
    	     {
    	        $(".dept_search").hide();
    	        $(".dept_tree").show();
    	        return;
    	     }
    	     else
    	     {
    	        this.searchinfo.deptname = deptname;
    	        $(".dept_search").show();
    	        $(".dept_tree").hide();
           }
       }
       this.searchinfo.pageindex = pageindex;
	     var para = {"deptname":this.searchinfo.deptname,"number":1,"pageindex":pageindex,"pagenumber":14 }; 
	     var parameter = { "module":"Backstage","action":"searchdeptByTree" ,"params":para }
	     var html = Array();
	     $(".dept_tree").hide();
	     $(".dept_search").show();
	     $(".loadding_tree1").show();
	     $(".loadding_tree1>img").show();
	     $(".loadding_tree1>span").html("正在查询组织数据……");
       $.post(access_url,parameter,function(data) {
            $(".loadding_tree1").hide();
            html = [];
        	  if (data.success)
        	  {
        	     var listdata = data.datasource;
        	     if ( listdata.length==0)
        	     {
        	        $(".loadding_tree1").show();
        	        $(".loadding_tree1>img").hide();
        	        $(".loadding_tree1>span").html("未搜索到部门记录");
        	        $(".result_search").html("");
        	     }
        	     else
        	     {
            	     for(var i=0;i<listdata.length;i++)
            	     {
            	        row = listdata[i];
            	        html.push("<li pageindex='"+pageindex+"' onmouseover='$(this).addClass(\"dept_search_hover\");' onmouseout='$(this).removeClass(\"dept_search_hover\");' deptid='"+row.deptid+"' issearch='0' onclick='department.search_deptandstaff(this);'>"+row.deptname+"</li>");
            	     }
            	     if ( type=="search")
            	        $(".result_search").html(html.join(""));
            	     else
            	     {
            	        $(".result_search>li:visible").hide();
            	        $(".result_search").append(html.join(""));
            	     }
            	     $(".result_search").show(); 
            	     if ( type =="search")
            	     {
                	     //对翻页的处理
                	     var pagecount = data.pagecount;
                	     department.searchinfo.pagecount = pagecount;
                	     if ( data.pagecount > 0)
                	     {            	        
                	        html=[];
                	        html.push("<span flag='prev' class='page_butoon_disble' state='0' onclick='department.search_page(this);' >上一页</span>");
                	        html.push("<span class='page_title'>当前第<span>" + pageindex + "</span>页</span>");
                	        html.push("<span flag='next' class='page_butoon' state='1' onclick='department.search_page(this);'>下一页</span>");
                	        $(".dept_page").html(html.join("")).show();
                	     }
                	     else
                	     {
                	        $(".dept_page").html("");
                	     }
            	     }
            	     else
            	     {
            	        $(".page_title>span").text(department.searchinfo.pageindex);
            	     }
        	     }
        	  }
        	  else
        	  {        	     
        	  }        	  
        });
	  },
	  search_page:function(evn)
	  {
	     if ( $(evn).attr("state")=="0") return;
	     var pageindex = this.searchinfo.pageindex;
	     var flag = $(evn).attr("flag");
       if ( flag=="prev")
	        pageindex = pageindex - 1;
	     else
	        pageindex = pageindex + 1;
	     if ( pageindex < 1 || this.searchinfo.pageindex > this.searchinfo.pagecount  ) return;	     
	     if ( pageindex ==1 || pageindex >= this.searchinfo.pagecount)
	     {
	        $(evn).attr("state",0);
	        $(evn).removeClass().addClass("page_butoon_disble");
	        return;
	     }
	     this.searchinfo.pageindex = pageindex;	     
	     if ( pageindex > 1)
	     {
	        $(".dept_page span[flag='prev']").removeClass().addClass("page_butoon");
	        $(".dept_page span[flag='prev']").attr("state",1);
	     }
	     else
	     {
	        $(".dept_page span[flag='prev']").removeClass().addClass("page_butoon_disble");
	        $(".dept_page span[flag='prev']").attr("state",0);
	     }
	     if ( pageindex == this.searchinfo.pagecount)
	     {
	        $(".dept_page span[flag='next']").removeClass().addClass("page_butoon_disble");
	        $(".dept_page span[flag='next']").attr("state",0);
	     }
	     else
	     {
	        $(".dept_page span[flag='next']").removeClass().addClass("page_butoon");
	        $(".dept_page span[flag='next']").attr("state",1)
	     }
	     var ul = $(".result_search>li[pageindex='"+pageindex+"']");
	     if ( ul.length>0)
	     {
	        $(".result_search>li:visible").hide();
	        $(".page_title>span").text(pageindex);
	        ul.show();       
	     }
	     else
	     {
	        this.searchTree(pageindex,"page");
	     }
	  },
	  search_deptandstaff:function(evn)
	  {
	      if ( $(evn).attr("issearch")=="1") return;
	      $(".result_search>li").removeClass();
	      $(".result_search>li").attr("issearch",0);
	      $(evn).attr("class","dept_search_selected");
	      var deptid = $(evn).attr("deptid");
	      var deptname = $(evn).text();
        $(".header_dept_name>span").html(deptname);
        $(".header_dept_name>span").attr("deptid",deptid);
        $(".header_dept_name>span,.header_dept_name>a").show();
        if ( deptname=="体验部门" )
           $(".header_dept_name>a").hide();
        $(".header_dept_name>input").hide();
        $("#update_dept_button").hide();
         //查询人员
         staff.deptid = deptid;
         staff.search_staff(1);
         //查询下级部门
         department.deptid = deptid;
         department.search_dept(1);
         $(evn).attr("issearch",1);
	  },
	  del:function()
	  {
	      var html = "<img src='/bundles/fafatimewebase/images/loading.gif'/><span>正在删除……</span>";
        $("#window_hint .delete_hint").html(html).show();
        $.post(department.dele_url,{"dept_id":this.remove_deptid},function(data) {
            if (data.success){  	 
                html = "<span>删除成功</span>";
                $("#window_hint .delete_hint").html(html);
                $("#dept_body table tbody tr[deptid='"+department.remove_deptid+"']").remove();
                //同时删除节点
  	 	 	 	 	    var treeObj = $.fn.zTree.getZTreeObj("tree_department");
                var node = treeObj.getNodeByParam("id",department.remove_deptid, null);
                treeObj.removeNode(node);
                setTimeout(function() { $("#window_hint .delete_hint").hide();$("#window_hint").hide(); },2000);
            }
            else{
               html = "<span>"+data.msg+"</span>";
               $("#window_hint .delete_hint").html(html);	 
            }
        });
	  },
	  view_dept:function(type)
	  {
	     if ( type==0)
	     {
	        $(".header_dept_name>span,.header_dept_name>a").hide();
	        $(".header_dept_name>input").show();
	        $(".header_dept_name>input").val($(".header_dept_name>span").text());
	        $(".header_dept_name>input").attr("deptid",$(".header_dept_name>span").attr("deptid"));
	        $("#update_dept_button").show();
	     }
	     else if (type==1)
	     {
	        var text_val = $.trim($(".header_dept_name>input").val());
	        var span_val  = $(".header_dept_name>span").text();
	        if ( text_val=="" || text_val == span_val)
	        {
	            return false;
	        }
	        var para = {"deptid":$(".header_dept_name>input").attr("deptid"),"deptname":text_val};
	        var parameter = { "module":"Dept","action":"update_dept" ,"params":para };
	        $("#update_dept_button>a:last").hide();
	        var html = "<img src='/bundles/fafatimewebase/images/loadingsmall.gif' style='width:16px;height:16px;' />保存中……";
	        $("#update_dept_button>a:first").html(html);
	        $.post(access_url,parameter,function(data) {
	            $("#update_dept_button>a:first").html("保存");
	            $("#update_dept_button>a:last").show();
	            if ( data.success)
	            {
	                $(".header_dept_name>span,.header_dept_name>a").show();
    	            $("#update_dept_button,.header_dept_name>input").hide();
	                $(".header_dept_name>span").text(text_val);
	                if ( $(".dept_tree").is(":visible"))
	                {
    	                //同时更改节点名称
      	 	 	 	 	      var treeObj = $.fn.zTree.getZTreeObj("tree_department");
                      var node = treeObj.getNodeByParam("id",para.deptid, null);
                      var old = node.name.split("(");
                      node.name =  para.deptname+"("+old[old.length-1];
                      treeObj.updateNode(node);
                  }
                  else
                  {
                     $(".result_search>li[deptid='"+para.deptid+"']").text(para.deptname);
                  }
	            }
	        });
	     }
	     else if (type==2)
	     {
	         $(".header_dept_name>span,.header_dept_name>a").show();
    	     $("#update_dept_button,.header_dept_name>input").hide();
	     }
	  }
}

var dept_import = {
	 error_data:"",
	 import_url:"",
	 sql_url:"",
	 search_url:"",
	 statistics_url:"",
	 dele_url:"",
	 identical_url:"",
	 datasource:null,
	 import_type:1,
	 search_state:true,
	 issearch:false,
	 record:16,
	 filepath:"",
	 totalrecord:0,
	 totalpage:0,
	 importdata_type:true,
	 pageindex:0,
	 export_err:Array(),
	 Field : { 
	             dept_id: {"zh_name":"部门ID","index":"","isNull":0,"hidden":true},
            	 dept_name:   {"zh_name":"部门名称","index":"","isNull":0,"hidden":false},
            	 parent_dept: {"zh_name":"上级部门","index":"","isNull":0,"hidden":false},
            	 parent_dept_id: {"zh_name":"上级部门ID","index":"","isNull":0,"hidden":true},
            },
	 radio_change:function(ev){
	 	 var id = $(ev).attr("id");
	 	 $("#view_dept_import #setcontent>div").hide();
	 	 if ( id=="type1"){
	 	   $("#view_dept_import #setcontent>div").eq(1).show();	 
	 	   $("#view_dept_import #setcontent>div").eq(2).show();
	 	 }
	 	 else{
	 	 	 $("#view_dept_import #setcontent>div").eq(0).show();	 
	 	   $("#view_dept_import #setcontent>div").eq(1).show();
	 	   $("#view_dept_import #setcontent>div").eq(3).show();
	 	 }	 	 
	 },
	 viewdialog:function(){
	 	 $("#viewImport").show();	 	 
	 },
	 step:function(state){
	 	 $(".staffmanager_hint").html("");
	 	 if ( state=="N"){
	 	 	 $(".staff_field_setting,.staff_field_content").hide();
	 	 	 $("#select_datasorce tbody").html("");
	 	 	 $("#btnPre").show();
	 	 	 $("#btnNext").hide();
       var selectval = $("#excel_type").attr("checked");
       if ( selectval!=null ){
       	 this.import_type = 1;
       	 $("#excel_setting").show();
       	 return;     	      	      	 
       }
       selectval = $("#ldap_type").attr("checked");
         if ( selectval!=null ){
         this.import_type = 2;
       	 $("#excel_setting").show();
       	 return;
       }
       selectval = $("#database_type").attr("checked");
         if ( selectval!=null ){
         this.import_type = 3;
       	 $("#database_setting").show();
       	 return;	      	      	 
       }
       selectval = $("#api_type").attr("checked");
         if ( selectval!=null ){
         	 this.import_type = 4;
         	 $("#api_setting").show();
       	   return;	      	      	 
       }
	 	 }
	 	 else if ( state=="P"){
	 	 	 $("#btnPre").hide();
	 	 	 $("#btnNext").show();
	 	 }
	 },
	 checkinput:function(id){
	 	  if (id=="excel_type"){
	 	  	var file = $("#filedata").val();
	 	 	 	if ( file == ""){
	 	 	 		 this.showhint("请选择导入的Excel文件！",true);
	 	 	 		 return false;
	 	 	 	}
	 	 	 	else{	 	 	 		 
	 	 	 		 var suffix = file.substring(file.lastIndexOf(".")+1);
	 	       if ( suffix!="xls" && suffix!="xlsx"){
	 	 	        this.showhint("请选择Excel文件(*.xls或*.xlsx)！",true);
	 	 	       return false;
	 	       }
	 	 	 	}
	 	 	}
	 	 	else if (id=="lsap_type"){
	 	 	}
	 	 	else if (id=="database_type"){
	 	 	}
	 	 	else if (id = "api_type"){
	 	 	}
	 	 return true;
	 },
	 //isempty:显示提示后是否清空;true:清空;false:不清空
	 showhint:function(message,isempty){
	 	 if ( message == null || message =="")
	 	  $("#view_dept_import .staffmanager_hint").html("");
	 	 else {
	 	 	$("#view_dept_import .staffmanager_hint").html(message);
	 	 	if ( isempty )
	 	 	  setTimeout(function(){ $("#view_dept_import .staffmanager_hint").html(""); },2000);
	 	 }
	 },
	 viewData:function(){	 	
	 	 //初始相关操作
	   dept_import.Field.dept_id = {"zh_name":"部门ID","index":"","isNull":0,"hidden":true};
	   dept_import.Field.dept_name = {"zh_name":"部门名称","index":"","isNull":0,"hidden":false};
	   dept_import.Field.parent_dept = {"zh_name":"上级部门","index":"","isNull":0,"hidden":false};
	   dept_import.Field.parent_dept_id = {"zh_name":"上级部门ID","index":"","isNull":0,"hidden":true};
	   $("#view_dept_import .staff_field_content").hide();
	   $("#view_dept_import .staff_field_row .mb_combox").html("");	 	 
	 	 $("#view_dept_import #select_datasorce tbody").html("");
	 	 var html_body = new Array();
	 	 var data = this.datasource;
	 	 var  row = null;
	 	 var td_style = "";
	 	 if ( data!=null && data.length>0){
	 	 	 $("#view_dept_import .staff_field_setting").show();
		 	 for(var i=0;i< data.length;i++){
		 	 	 row = data[i];
		 	 	 if ( i== 0 ){
		 	 	 	 var temphtml = new Array();
		 	 	 	 var len = 0;
		 	 	 	 for(j in row){
		 	 	 	 	 len += 1;
		 	 	 	 }
		 	 	 	 var td_width = 100 / len;
		 	 	 	 td_style = "style='float:left;width:"+td_width+"%;'";
		 	   	 html_body.push("<tr class='staff_table_head'>");
		 	   	 temphtml.push("<tr class='staff_table_row'>");
		 	   	 for(j in row){
		 	   	 	 html_body.push("<td title='设置映射字段' "+td_style+">"+j+"</td>");
		 	   	 	 temphtml.push("<td ondblclick='dept_import.setRoot(this);' "+td_style+">"+ row[j] +"</td>");	 	   	 	 
		 	   	 }
		 	   	 html_body.push("</tr>");
		 	   	 temphtml.push("</tr>");
		 	   	 html_body.push(temphtml.join(""));
		 	   }
		 	   else{
		 	   	 html_body.push("<tr class='staff_table_row'>");
		 	   	 for(k in row){
		 	   	 	 html_body.push("<td ondblclick='dept_import.setRoot(this);'"+td_style+">"+ row[k] +"</td>");	 	
		 	   	 }
		 	   	 html_body.push("</tr>");
		 	   }
		 	 }
	   }
	   else{
	   	 $("#view_dept_import .staff_field_setting").hide();
	   }
	 	 $("#view_dept_import #select_datasorce tbody").html(html_body.join(""));
	 },
	 setRoot:function(evn)
	 {
	    if ( $.trim($("#rootdeptid").val())=="")
	      $("#rootdeptid").val($.trim($(evn).text()));
	 },
	 //显示字段映射关系面板
	 viewField:function(){
	 	 $("#view_dept_import .staff_field_content").show();
     var html = new Array();
     if ( $("#view_dept_import .staff_field_content .staff_field_row").length==0){
			 for(j in dept_import.Field){
			 	 var item = dept_import.Field[j];
			 	 html.push("<div class='staff_field_row'"+(item.hidden?" style='display:none;'":"")+">");
			 	 html.push("<div>");
			 	 var must = false;
			 	 var fieldname = "";
			 	 for(i in item){
			 	 	 if ( i=="zh_name"){
			 	 	  html.push("<span class='staff_fieldname'>请指定<span style='padding-left:2px;padding-right:2px;color:black;'>"+item[i]+"</span>映射关系：</span>");
			 	 	  fieldname = item[i];
           }
			 	 	 if ( i=="isNull" && item[i]==0){
			 	 	 	 must = true;
			 	 	   html.push("<span class='staff_field_desc'>*该字段必须指定映射关系</span>");
			 	 	 }
			 	 }
			 	 html.push("</div>")
			 	 html.push("<select class='mb_combox' must='"+(must ? 1:0)+"' fieldname='"+fieldname+"' field='"+j+"'></select>");
			 	 html.push("</div>");
			 }
			 $("#view_dept_import .staff_field_content #setcontent").html(html.join(""));
			 this.loadCombox();
		 }
		 else{
		 	 if ($("#view_dept_import .staff_field_row .mb_combox:first").children().length==0)
		 	  this.loadCombox();
		 }		 		
	 },
	 loadCombox:function(){
	 	 var html = new Array();
	 	 html.push("<option value=''></option>");
	 	 var data = this.datasource;
	 	 if ( data != null && data.length>0){
	 	 	 for(var i=0;i<1;i++){
	 	 	 	 var row = data[i];
	 	 	 	 var index = 0;
	 	 	 	 for(j in row){
	 	 	 	 	 html.push("<option value='"+index+"'>"+j+"</option>");
	 	 	 	 	 index++;
	 	 	 	 }
	 	 	 }	 	 	 
	 	 }
	 	 $("#view_dept_import .staff_field_row .mb_combox").html(html.join(""));
	 },
	 setField:function(){
	    $("#view_dept_import #btn_start_import").attr("start","0");
	 	 var control = $("#view_dept_import .staff_field_row .mb_combox:visible");
	 	 //判断是否选择
	 	 var val = "";
	 	 for(var i=0;i<control.length;i++){
	 	 	 val =  control.eq(i).val();
	 	 	 if ( val=="" && control.eq(i).attr("must")=="1"){
	 	 	 	 $("#view_dept_import .setfield_hint").text("必须指定【"+control.eq(i).attr("fieldname")+"】字段的映射关系！");
	 	 	 	 control.eq(i).focus();
	 	 	 	 return;
	 	 	 }
	 	 }
	 	 dept_import.Field.dept_id.index = "";
	 	 dept_import.Field.dept_name.index = "";
	 	 dept_import.Field.parent_dept.index = "";
	 	 dept_import.Field.parent_dept_id.index = ""; 
     //更改Field对象
	 	 for(var j=0;j<control.length;j++){
	 	 	 val = control.eq(j).val();
	 	 	 var field = control.eq(j).attr("field");
	 	 	 if ( field=="dept_id")
	 	 	   dept_import.Field.dept_id.index = val;
	 	 	 if ( field=="dept_name")
	 	 	   dept_import.Field.dept_name.index = val;
	 	 	 if ( field=="parent_dept")
	 	 	   dept_import.Field.parent_dept.index = val;
	 	 	 if ( field=="parent_dept_id")
	 	 	   dept_import.Field.parent_dept_id.index = val;	 	 	   
	 	 }
	 	 if ( $("#view_dept_import .dept_setField_hint #type2").attr("checked")==null)
	 	    dept_import.Field.importdata_type = true ;
	 	 else
	 	 	  dept_import.Field.importdata_type = false;
	 	 
	 	 $("#view_dept_import #btn_start_import").attr("start","1");
	 	 $("#view_dept_import").show();
	 	 $("#view_dept_import .staff_field_content").hide();
	 },
	 //开始导入数据
	 start:function(index){
	 	  var rootdeptid = $.trim($("#view_dept_import #rootdeptid").val());
	 	  if ( rootdeptid=="")
	 	  {
	 	  	$("#view_dept_import #viewImport .staffmanager_hint").text("请选择根部门id");
	 	  	$("#view_dept_import #rootdeptid").focus();
	 	  	return;
	 	  }
	 	  else
 	  	{
 	  		$("#view_dept_import #viewImport .staffmanager_hint").text("");
 	  	}
 	  	if ( $("#view_dept_import #btn_start_import").attr("start")=="0")
 	  	{
 	  	    $("#view_dept_import #viewImport .staffmanager_hint").text("请设置字段映射关系");
 	  	    return;
 	  	}
      if ( index==1)
      {
      	$("#view_dept_import #btn_start_import").attr("disabled","disabled");
	      $("#view_dept_import .import_process").show();
	      $("#view_dept_import .import_process_bar").css("width","1px");
	      $("#view_dept_import .import_process_text").text("正在开始导入数据，请稍候...");
      }
	 	  var parameter = { "rootdeptid":rootdeptid,"relation":dept_import.Field,"datatype":dept_import.importdata_type ? 1 : 0,"file":dept_import.filepath,"totalrecord":dept_import.totalrecord,"index":index};
	 	 	$.post(this.import_url,parameter,function(data){
	 	 		$("#view_dept_import .loading").hide();
	 	 	  var pageindex = data.index;
	 	 	  var errlist = data.errorData;
	 	 	  if ( errlist.length>0)
	 	 	  {
	 	 	  	 var html = Array();
	 	 	  	 for(var i=0;i< errlist.length;i++)
	 	 	  	 {
	 	 	  	 }
	 	 	  	 dept_import.export_err.push();	 	 	  	
	 	 	  }
	 	 	 	if ( pageindex <= dept_import.totalpage){
	 	 	 	 	 dept_import.start(pageindex);
	 	 	 	 	 //显示进度
	 	 	 	 	 var _width = (index / dept_import.totalpage)*100;
	 	       $("#view_dept_import .import_process_bar").css("width",_width+"%");
	 	       _width = _width.toFixed(2)+"%";
	 	       $("@view_dept_import .import_process_text").text("当前导入进度: "+_width);
	 	 	 	}
	 	 	 	else{
	 	 	 	  $.post(dept_import.statistics_url,{"file":dept_import.filepath},function(data){
	 	 	 	    if ( data.success){
	 	 	 	   	  dept_import.showhint("导入部门数据成功！",true);
	 	 	 	   	  $("#btn_start_import").removeAttr("disabled");
	 	          $(".import_process_bar").css("width","100%");
	 	          $(".import_process_text").text("100.00%");
	 	          setTimeout(function() {
	 	          	 $(".import_process").hide();   
	 	          	 $(".import_process_bar").css("width","1px");
	 	          	 $(".import_process_text").text(" ");
	 	          	 dept_import.setdeptpath();
	 	          },2000);
	 	 	 	   	}
	 	 	 	   	else{
	 	 	 	   	  dept_import.showhint("导入部门数据失败！",false);
	 	 	 	   	}
	 	 	 	  });	 	 	 	   	 
	 	 	 	}
	 	 	});
	 },	 
	 //设置部门path
	 setdeptpath:function()
	 {
	 	  var iscomplete = 0;
			var parameter = { "module":"Dept","action":"setDeptPath" };
			$.post(access_url,parameter,function(returndata){
				 if ( iscomplete==0 && returndata.success )
				 {
				 	 if (  returndata.iscomplete==false)
				 	  dept_import.setdeptpath();
				 }
			});
	 },
	 //查看错误提示
	 viewError:function(){
	 	 var error = this.error_data;
	 	 if ( error.length>0){
	 	 	 $("#dept_import #viewError").show();
	 	 	 var html = new Array();
	 	 	 for(var i=0;i<error.length;i++){
	 	 	 	 html.push("<span class='depart_errorhint'>"+error[i]+"</span>");
	 	 	 }
	 	 	 $("#dept_import #viewError .content").html(html.join(""));
	 	 }
	 },
	 ExecSQL:function(){
	 	 var dbtype = $("#dbtype").val();
	 	 if (dbtype==""){
	 	 	 this.showhint("请选择【数据库类型】",true);
	 	 	 return;
	 	 }
	 	 var server = $.trim($("#text_server").val());
	 	 if ( server==""){
	 	 	 this.showhint("请输入【数据库服务器】",true);
	 	 	 $("#text_server").focus();
	 	 	 return;
	 	 }
	 	 var database = $.trim($("#text_database").val());
	 	 if ( database==""){
	 	 	 this.showhint("请输入【数据库名】",true);
	 	 	 $("#text_database").focus();
	 	 	 return;
	 	 }	 	 
	 	 var user = $.trim($("#text_user").val());
	 	 if ( user==""){
	 	 	 this.showhint("请输入【用户账号】",true);
	 	 	 $("#text_user").focus();
	 	 	 return;
	 	 }		 	 
	 	 var pass = $.trim($("#text_password").val());
	 	 if ( pass==""){
	 	 	 this.showhint("请输入【用户密码】",true);
	 	 	 $("#text_password").focus();
	 	 	 return;
	 	 }	 	 
	 	 var sqlcomment = $.trim($("#text_sql").val());
	 	 if ( sqlcomment==""){
	 	 	 this.showhint("请输入【查询SQL语句】",true);
	 	 	 $("#text_sql").focus();
	 	 	 return;
	 	 }
	 	 var parameter = {  "dbtype":dbtype,"url":server,"dbname":database,"dbuser":user,"dbpwd":pass,"sqlcomment":sqlcomment};
	 	 $.post(this.sql_url,parameter,function(data){
	 	 	 	 if ( data.success){
		        dept_import.datasource = data.DataSource;
		        dept_import.viewData();
	       }
	       else{
		       dept_import.showhint(data.msg,false);
	       }
	 	 });
	 	  	 
	 },
	 enableNaN:function(evn)
	 {
	    var tmptxt= $.trim($(evn).val());
      if ( tmptxt != "")
      $(evn).val(tmptxt.replace(/\D|^0/g,''));
   },
   //上传excel文件
   uploadfile:function(){
        var file = $("#view_dept_import #filedata").val();
        if ( file == ""){
            dept_import.showhint("请选择导入的Excel文件！",true);
            return;
        }
        else{	 	 	 		 
            var suffix = file.substring(file.lastIndexOf(".")+1);
            if ( suffix!="xls" && suffix!="xlsx"){
                dept_import.showhint("请选择Excel文件(*.xls或*.xlsx)！",true);
                return;
            }
        }
        $("#view_dept_import #btn_start_import").attr("start","0");
        $("#frm_dept_import").submit();
   }
		  
};

//上传文件后的返回数据
function dept_import_callback(data)
{
	if ( data.success){
		dept_import.datasource = data.DataSource;
		dept_import.filepath = data.filepath;
		dept_import.totalrecord = data.recordcount;
		dept_import.totalpage = data.total_page;
		dept_import.viewData();
		var row = data.recordcount > 1 ? data.recordcount - 1 : 0;
		dept_import.showhint("上传文件共计"+ row +"条数据记录，目前仅显示前"+data.DataSource.length+"条数据记录！" ,false);
	}
	else{
		 dept_import.showhint("上传文件出现错误，请重试！",false);
	}
}

//加载部门
var LoadDept = {
    tree_Id:"",
    deptId:"",
    url:"",
    loadTree:function()
    {        
        LoadDept.url = department.getdepart_url;
        $.getJSON(LoadDept.url,{ "number": 0},function(data)
        {
            if (data.success){
                var zTreeSetting = {
                    data:{
                            simpleData:{
                                enable:true
                             }
                    },
                    callback:{
                        onClick:LoadDept.TreeClick
                    }
                };  	    
                $("#"+LoadDept.tree_Id).show();
                $.fn.zTree.init($("#"+LoadDept.tree_Id),zTreeSetting, data.datasource);
                var treeObj = $.fn.zTree.getZTreeObj(LoadDept.tree_Id);
                var nodes = treeObj.getNodes();
                if ( nodes.length>0 )
                {
                    MarkObj.setMark(nodes[0].children);
                }
            }
        });
    },
    TreeClick:function(event, treeId, treeNode)
    { 
        var id = treeNode.id;
        if ( MarkObj.type)
        {
            MarkObj.textObj.val(treeNode.name);
            MarkObj.textObj.attr("deptid",id);
            MarkObj.areaObj.hide();
            return;
        }
        if ( !treeNode.isParent)
        {
            if ( treeNode.state == "0") return;
            var parameter = {"deptid":treeNode.id,"number":0 };
            $.getJSON(LoadDept.url,parameter,function(data) {
                MarkObj.type = true;
                if (data.success)
                {
                    if (data.datasource.length==0)
                    {
                        treeNode.state = 0;
                    }
                    else
                    {
                        var treeObj = $.fn.zTree.getZTreeObj(LoadDept.tree_Id);
                        treeObj.addNodes(treeNode,data.datasource);
                        treeNode.state = 0;
                        if ( treeNode.length>0)
                            MarkObj.setMark(treeNode.children);
                    }
                }
            });
        }
    },
    showdept:function(id,evn)
    {
        MarkObj.textObj = $(evn);
        MarkObj.areaObj = $("#"+id);
        if ( $("#"+id).children().length==0)
        {
            LoadDept.tree_Id = id;
            LoadDept.loadTree();
            $("#"+id +"_area").show();
        }
        else
        {
            $("#"+id).toggle();
        }
    },
    selected_dept:function(id)
    {
        var treeObj = $.fn.zTree.getZTreeObj(this.tree_Id);
        var node = treeObj.getSelectedNodes();
        if ( node.length>0)
        {
            var treenode = node[0];
            $("#"+id).val(treenode.name);
            $("#"+id).attr("deptid",treenode.id);
            $("#"+this.tree_Id+"_area").hide();
        }
    }
};

//删除数据
function DeleteData()
{
    var type = $("#window_hint").attr("remove_type");
    if ( type=="staff")
    {
        
          staff.del();
       
    }
    else if (type=="dept")
        department.del();
    else if ( type=="number")
        department.edit_order();
    else if ( type=="showhide")
        department.setShowHide();
}