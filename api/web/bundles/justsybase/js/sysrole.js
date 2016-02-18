//系统权限管理
var Role =
{
    common_url:"",
    roleid:"",
    de_val:{"staff":"/bundles/fafatimewebase/images/no_photo.png"},
    USER_ROLELIST:Array(),
    loadRoleList:function()
    {
        //加载角色列表
        var htm = "<div class='role_loadding'><img src='/bundles/fafatimewebase/images/loadingsmall.gif' /><span>正在加载角色</span></div>";
        $(".menu_nav").before(htm);        
        var parameter = {"module":"role","action":"getRoleList","params":{}};
        $.post(Role.common_url,parameter,function(data){
            if ( data.success)
            {
                $(".role_loadding").remove();
                var html=Array();
                var rolelist = data.returndata;
                if ( rolelist.length==0)
                {
                }
                else
                {
                    var row = null;
                    for(var i=0;i<rolelist.length;i++)
                    {
                        row = rolelist[i];
                        html.push("<li onclick='Role.menuClick(this);' roleid='"+row.id+"'>"+row.name+"</li>");
                    }                
                    $(".menu_nav").html(html.join(""));
                }
            }
            else
            {
                $(".role_loadding img").remove();
                $(".role_loadding span").text("加载角色数据失败！");
            }
            //加载功能列表
            htm = "<div class='role_loadding' style='margin-top:10px;'><img src='/bundles/fafatimewebase/images/loadingsmall.gif' /><span>正在加载权限数据</span></div>";
            $("#functionlist").html(htm);
            parameter = {"module":"role","action":"getFunctionList","params":{}};
            $.post(Role.common_url,parameter,function(data){
                if ( data.success)
                {
                    var html=Array();
                    var rolelist = data.returndata;
                    if ( rolelist.length==0)
                    {
                    }
                    else
                    {
                        var row = null;
                        for(var i=0;i<rolelist.length;i++)
                        {
                            row = rolelist[i];
                            html.push("<label class='checkbox_role'>");
                            html.push("  <input style='float:left;' type='checkbox' value='"+row.functionid+"' id='function_"+row.functionid+"' name='function'><span>"+row.name+"</span>");
                            html.push("</label>");
                        }
                        $("#functionlist").html(html.join(""));
                        Role.menuClick($(".menu_nav li:first"));
                    }
                }          
            });             
        });     
    },
    menuClick:function(evn)
    {
        this.roleid = $(evn).attr("roleid");        
        if ($(evn).attr("class")=="menu_nav_active") return;
        $(".menu_nav>li").removeClass();
        $(evn).attr("class","menu_nav_active");
        var menutext = $(evn).text();
        $("#functionid .panel-heading").text(menutext);
        var parameter = {"module":"role","action":"getRoleFunction","params":{"roleid":this.roleid}};
        $.post(this.common_url,parameter,function(data){
            if ( data.success)
            {        
                Role.setFunctionid(data.f_data);
                Role.setStaff(data.u_data);
                $(".role_bottom").show();
            }
        });
    },
    //设置功能点
    setFunctionid:function(data)
    {
       $("#functionlist input").attr("checked",false);
       $("#functionlist input").attr("keyid","");
       for(var i=0;i<data.length;i++)
       {
          $("#function_"+data[i].functionid)[0].checked = true;
          $($("#function_"+data[i].functionid)[0]).attr("keyid",data[i].id);
       }
    },
    setStaff:function(data)
    {
        $(".role_user").html("");
        var html = Array();
        if ( data.length==0)
        {
        }
        else
        {
            for(var i=0;i<data.length;i++)
            {
                var row = data[i];
                html.push("<div class='role_user_info' onmouseover='Role.staff_focus(this);' onmouseout='Role.staff_leave(this);' keyid='"+row.id+"' login_account='"+row.login_account+"'>");
                html.push("  <span>"+row.nick_name+"</span>");
                html.push("  <span style='text-align:center;display:inline-block;width:16px;'>");
                html.push("    <span class='glyphicon glyphicon-remove role_delstaff' onclick='Role.deleStaff(this);' title='删除人员'></span>");
                html.push("  </span>");
                html.push("</div>");
            }
            $(".role_user").html(html.join(""));
        }        
    },
    staff_focus:function(evn)
    {
        $(evn).find(".glyphicon").show();
    },
    staff_leave:function(evn)
    {
        $(evn).find(".glyphicon").hide();
    },
    deleStaff:function(evn)
    {
        var currElement = $(evn).parents(".role_user_info");
        var keyid=currElement.attr("keyid");
        if (keyid!=null && keyid!="")
        {
            currElement.find("span").hide();
            var html = Array();
            html.push("<div class='role_animation'>");
            html.push("  <img src='/bundles/fafatimewebase/images/loadingsmall.gif' />");
            html.push("  <span>删除中</span>");
            html.push("</div>");
            currElement.append(html.join(""));
            var parameter = {"module":"role","action":"delRole","params":{"id":keyid}};
            $.post(this.common_url,parameter,function(data){
                if ( data.returncode=="0000" && data.data.success)
                {
                    currElement.find(".role_animation").html("<span>删除成功</span>");
                    setTimeout(function()
                    {
                        currElement.remove();               
                    },1000);
                }
                else
                {
                    currElement.find(".role_animation").html("<span>删除失败</span>");
                    setTimeout(function()
                    {
                        currElement.find(".role_animation").remove();
                        currElement.find("span").show();
                                                
                    },1000);
                }
            });                   
        }
        else
        {
            currElement.remove();
        }
    }, 
    viewWindow:function()
    {
        $("#setManager").show();
        $("#textAccount").val("");
        Role.search_staff();
        $(".search_right .staff_list").html("");        
    },
    search_staff:function()
    {
        var html= Array();
        var para = {"staff":$.trim($("#textAccount").val()),"roleid":this.roleid };
        var parameter = {"module":"role","action":"search_staff","params":para };
        if ($(".search_left .search_loadding").length==1) 
          $(".search_left .search_loadding").remove();
        var htm = "<div class='search_loadding'><img src='/bundles/fafatimewebase/images/loading.gif' /><span>正在查询用户……</span></div>";
        $(".search_left ul").hide();
        $(".search_left").append(htm);
        $.post(this.common_url,parameter,function(data){
            $(".search_left .search_loadding").remove();
            if ( data.success)
            {
                var stafflist = data.returndata;
                if ( stafflist.length==0)
                {
                    htm = "<div class='search_loadding'><img src='/bundles/fafatimewebase/images/ts.png' /><span>未查询到数据</span></div>";
                    $(".search_left").append(htm);
                }
                else
                {
                    $(".search_left ul").show();                    
                    html=[];
                    var row = null;header="";
                    for(var i=0;i<stafflist.length;i++)
                    {
                        row=stafflist[i];
                        if ( row.header=="")
                          header = Role.de_val.staff;
                        else
                          header = row.header;
                       
                       html.push("<li onclick='Role.staff_selected(this);' login_account='"+row.login_account+"'>");
                       html.push("  <img src='"+header+"' />");
                       html.push("  <span>"+row.nick_name+"</span>");
                       html.push("</li>");
                    }
                    $(".search_left .staff_list").html(html.join(""));
                }
            }
            else
            {
                htm = "<div class='search_loadding'><img src='/bundles/fafatimewebase/images/ts.png' /><span>查询出现错误，请重试工！</span></div>";
                $(".search_left").append(htm);
            }
        });
        
    },
    staff_selected:function(evn)
    {
       var html = Array();
       var account = $(evn).attr("login_account");
       var img = $(evn).find("img").attr("src");
       var nick_name = $(evn).find("span").text();
       if ( $(".search_right .staff_list>li[login_account='"+account+"']").length==0)
       {
           html.push("<li onclick='Role.del_selected(this);' login_account='"+account+"'>");
           html.push("  <img src='"+img+"' />");
           html.push("  <span>"+nick_name+"</span>");
           html.push("</li>");       
           $(".search_right .staff_list").append(html.join(""));
       }
       $(evn).remove();
    },
    del_selected:function(evn)
    {
       var html = Array();
       var account = $(evn).attr("login_account");
       var img = $(evn).find("img").attr("src");
       var nick_name = $(evn).find("span").text();
       if ( $(".search_left .staff_list>li[login_account='"+account+"']").length==0)
       {
           html.push("<li onclick='Role.staff_selected(this);' login_account='"+account+"'>");
           html.push("  <img src='"+img+"' />");
           html.push("  <span>"+nick_name+"</span>");
           html.push("</li>");
           if ( $(".search_left .staff_list>li:first").length>0)
             $(".search_left .staff_list>li:first").before(html.join(""));
           else
             $(".search_left .staff_list").html(html.join(""));
       }       
       $(evn).remove();
    },
    staff_option:function()
    {
        var option = $(".search_right .staff_list>li");
        if (option.length==0)
        {                        
        }
        else
        {
            var account="",nick_name="";
            var html = Array();
            for(var i=0;i<option.length;i++)
            {
                nick_name=option.eq(i).find("span").text();
                account=option.eq(i).attr("login_account");
                if ( $(".role_user .role_user_info[login_account='"+account+"']").length>0) continue;
                html.push("<div login_account='"+account+"' keyid='' onmouseout='Role.staff_leave(this);' onmouseover='Role.staff_focus(this);' class='role_user_info'>");
                html.push("  <span>"+nick_name+"</span>");
                html.push("  <span style='text-align:center;display:inline-block;width:16px;'>");
                html.push("  <span title='删除人员' onclick='Role.deleStaff(this);' class='glyphicon glyphicon-remove role_delstaff' style='display:none;'></span></span>");
                html.push("</div>");
            }
            $(".role_user").append(html.join(""));  
            $("#setManager").hide();
        }  
    },
    saveRole:function()
    {
        var para = this.getval();
        if (para==false) return;
        var parameter = {"module":"role","action":"saveRole","params":para };
        $.post(this.common_url,parameter,function(data){
            if ( data.success)
            {
                Role.showhint(true,"添加用户权限成功！");
                var parameter = {"module":"role","action":"getRoleFunction","params":{"roleid":Role.roleid}};
                $.post(Role.common_url,parameter,function(data){
                    if ( data.returncode=="0000" && data.data.success)
                    {        
                        Role.setFunctionid(data.data.f_data);
                        Role.setStaff(data.data.u_data);
                    }
                });
            }
            else
            {
                Role.showhint(true,"添加用户权限失败！");
            }
        });        
    },
    getval:function()
    {
        var add_function = Array(),del_function=Array(),login_account=Array();
        //用户
        var use = $(".role_user>div");
        if ( use.length==0)
        {
            this.showhint(false,"请选择用户！");            
            return false;
        }
        else
        {
            for(var j=0;j<use.length;j++)
            {
                var keyid = use.eq(j).attr("keyid");
                if ( keyid=="")
                  login_account.push(use.eq(j).attr("login_account"));
            }
        }
        //功能点        
        var fun = $("#functionlist input");
        if ( fun.length==0)
        {
            this.showhint(false,"未有权限设置！");
            return false;
        }
        else
        {
            for(var i=0;i<fun.length;i++)
            {
                var state = fun.eq(i)[0].checked;
                var keyid = $(fun.eq(i)[0]).attr("keyid");
                if (state && keyid=="")
                {
                   add_function.push(fun.eq(i).val());
                }
                else if ( !state && keyid!="")
                {
                   del_function.push(fun.eq(i).val());              
                }
            }
        }
        if ( login_account.length==0 && add_function.length==0 && del_function.length==0)
        {
          this.showhint(false,"未作任何操作！");
          return false;
        }
        return {"roleid":this.roleid,"add_function":add_function,"del_function":del_function,"login_account":login_account};
        
    },
    showhint:function(success,msg)
    {
        var classname = success ? "hint_success":"hint_error";
        var html = "<span class='"+classname+"'>"+msg+"</span>";
        $(".role_save_msg").html(html);
        setTimeout(function(){
            $(".role_save_msg").html("");
        },2000);
    },
    setUserRole:function()
    {
        var parameter = {"module":"role","action":"getUserRole","params":{"type":"list"}};
        $.post(wefafa.UrlMapping.common_url,parameter,function(data){
            if ( data.returncode=="0000" && data.data.success && data.data.isAdmin==false)
            {
                Role.USER_ROLELIST = data.data.returndata;
                var list = data.data.returndata;
                var listStr = list.join(",");
                if ( listStr.indexOf("权限管理")==-1)
                  $("#li_role").remove();
                if ( listStr.indexOf("移动门户定制")==-1)
                  $("#li_mobileportal").remove();
                if ( listStr.indexOf("移动应用开发")==-1 && listStr.indexOf("移动应用发布")==-1 && listStr.indexOf("移动应用测试")==-1)
                  $("#li_mobileapp").remove();
               if ( listStr.indexOf("业务接口管理")==-1)
                  $("#li_interface").remove();
               if ( listStr.indexOf("数据源管理")==-1)
                  $("#li_datasource").remove();
            }
        });
    }
}