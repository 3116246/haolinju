var MobileApp =
{
    LoadApp:function()
    {
        var parameter = { "module":"App","action":"get_AppList","params":{} };
        $.post(access_url,parameter,function(returndata)
        {
            if ( returndata.success)
              MobileApp.full_AppList(returndata.appinfo);
        });
    },
    full_AppList:function(appinfo)
    {
        //左边应用列表
        var leftHtml = [],rightHtml=[],pageHtml=[],per_page=6;
        var pagecount = Math.ceil(appinfo.length / per_page);
        for (var i = 0; i < appinfo.length; i++) {
            var item = appinfo[i];
            leftHtml.push("<a href='#'>");
            leftHtml.push("  <li class='app_item'>");
            leftHtml.push("  <p align='center'><img src='"+ webserver_url+item.fileid +"' onerror=\"this.src='/bundles/fafatimewebase/images/app_logo_defaultbg.png'\" class='app_icon'></p>");
            leftHtml.push("  <p align='center' class='app_text'>"+item.appname+"</p></li></a>");
            
            var pageindex = Math.ceil((i+1)/per_page);
            if ( pageindex==1)
                rightHtml.push("<div class='applist_item' pageindex='1' appid='"+item.appid+"' fileid='"+item.fileid+"'>");
            else
                rightHtml.push("<div class='applist_item' pageindex='" + pageindex +"' style='display:none;' appid='"+item.appid+"' fileid='"+item.fileid+"'>");
            rightHtml.push(" <div class='applist_icon'>");
            rightHtml.push("   <img class='app_icon' onerror='this.src=\"/bundles/fafatimewebase/images/app_logo_defaultbg.png\"' src='"+webserver_url+item.fileid+"'/>");
            rightHtml.push(" </div>");
            rightHtml.push(" <div class='applist_info'>");
            rightHtml.push("   <a style='cursor:pointer;' title='修改应用' onclick='javascript:application.showAppWindow(this,0);'>"+item.appname+"</a><br>");
            var date = item.publishdate;
            var versionhtml = "";
            if ( item.configfileid!=null && item.configfileid!="")
               versionhtml = "<span class='app_configer'><a title='查看XML配置文件' href='"+webserver_url+item.configfileid+"'>"+item.version+"</a></span>";
            else
               versionhtml = "<span class='app_configer'>"+item.version+"</span>";
            var iscreate = item.iscreate;
            if ( date=="")
            {
               rightHtml.push("<p>"+versionhtml+"<br>最后更新：");
               rightHtml.push("  <span onclick=\"MobileHomeClass.testApp('"+item.appid+"','App');\" class='operator_label'>测&nbsp;&nbsp;试</span>");
               rightHtml.push("  <span onclick=\"publish.toMall.show('"+item.appid+"','"+item.appname+"','App')\" class='operator_label'>发布到商店</span>"); 
               if (item.iscreate=="1")
                 rightHtml.push(" <span onclick=\"application.deleteApp('" + item.appid+"','App');\"  class='operator_label'>删除</span>");
               rightHtml.push("</p>");
            }
            else
            {
               rightHtml.push("<p>"+versionhtml+"<br>最后更新："+item.publishdate+" by "+item.publishstaff);
               rightHtml.push("  <span onclick=\"MobileHomeClass.testApp('"+item.appid+"','App');\" class='operator_label'>测&nbsp;&nbsp;试</span>");
               rightHtml.push("  <span onclick=\"publish.toMall.show('"+item.appid+"','"+item.appname+"','App')\" class='operator_label'>发布到商店</span>");
               if (item.iscreate=="1")
                 rightHtml.push(" <span onclick=\"application.deleteApp('" + item.appid+"','App');\"  class='operator_label'>删除</span>");
               rightHtml.push("</p>");
            }
            rightHtml.push("  </div>");
            rightHtml.push("  <div class='applist_manager'>");
            rightHtml.push("    <a style='text-decoration:none;' href='#' onclick='application.setAppRole(this);'><i class='iconfont we-icon-menu'>&#xe627;</i>用户管理</a><br>");
            rightHtml.push("    <a style='text-decoration:none;' href='#'><i class='iconfont we-icon-menu'>&#xe64b</i>后台管理</a>");
            rightHtml.push("  </div>");
            rightHtml.push("</div>"); 
        };
        $("#app_item_add").before(leftHtml.join(""));
        
        //右边应用列表
        $("#applist_content").html(rightHtml.join(""));
        return;
        if ( pagecount>1)
        {
         //前一页
         pageHtml.push("<li class='disabled' id='prev'>");
         pageHtml.push("  <a onclick='javascript:application.paging(this);'><span>&laquo;</span></a></li>");
         for(var i=1;i<=pagecount;i++)
         {
            if (i==1)
              pageHtml.push("<li class='active' id='pageindex1' islast='0' pageindex='1'><a style='cursor:pointer;' onclick='javascript:application.paging(this);'>1</a></li>");
            else
            {
              pageHtml.push("<li id='pageindex"+i+"' islast='" + (i==pagecount ? 1:0)+"' pageindex='"+i+"'><a style='cursor:pointer;' onclick='javascript:application.paging(this);'>"+i+"</a></li>");
            }
         }
         //后一页
         pageHtml.push("<li id='next'><a style='cursor:pointer;' pageindex='next' onclick='javascript:application.paging(this);'>");
         pageHtml.push("  <span>»</span></a></li>");
         $(".pagination").html(pageHtml.join(""));
        }
        /*
        //加载组织机构树
        if ($("#tree_depart").children().length==0)
        {
            application.ztreeObj.treeId = "tree_depart";
            application.ztreeObj.init_zzjg();
        }
        */    
    }
};