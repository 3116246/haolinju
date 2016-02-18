var OnlineList =
{
  LI_TEMPLATE : '<li><a href="#" class="employee_name" login_account="[LOGIN_ACCOUNT]"><span style="display:none;">[NICK_NAME]</span><img width="24" height="24" title="[NICK_NAME]" src="[PHOTOURL]" onerror="this.src=\'[ERRORIMG]\'"></a></li>',
  LI_HEIGHT : 44,
  $right_onlinelist : null,
  onlineurl : "",
  friendurl : "",
  
  init : function (sender) 
  {
    var $sender = $(sender);    
    
    this.$right_onlinelist = $sender.find("#right_onlinelist");
    this.onlineurl = $sender.attr("onlineurl");
    this.friendurl = $sender.attr("friendurl");
    
//    this.$right_onlinelist.mousewheel(this.right_onlinelist_mousewheel);
//    $(window).resize(this.onwindowresize);
//    this.onwindowresize();
    this.getFriendList();
  },
  
  onwindowresize : function () 
  {
    OnlineList.$right_onlinelist.css("max-height", $(window).height() - 160);
    
    var $ul = OnlineList.$right_onlinelist.children("ul");
    var t = $ul.offset();
    var delta = (t.top - $ul.parent().offset().top) + $ul.height() - $ul.parent().height();
  
    if  (delta > 0) OnlineList.$right_onlinelist.siblings().show();
    else OnlineList.$right_onlinelist.siblings().hide();        
  },
  
  getFriendList : function () 
  {
    $.getJSON(OnlineList.friendurl, {t : new Date().getTime()}, function (data) 
    {
//      var $ul = OnlineList.$right_onlinelist.children("ul").empty();
//      for (var i = 0; i<data.length; i++)
//      {
//        $ul.append(OnlineList.LI_TEMPLATE.replace("[LOGIN_ACCOUNT]", data[i].login_account) 
//                                         .replace("[NICK_NAME]", data[i].nick_name).replace("[NICK_NAME]", data[i].nick_name)
//                                         .replace("[PHOTOURL]", data[i].photo_url)
//                                         .replace("[ERRORIMG]", g_resource_context+'bundles/fafatimewebase/images/no_photo.png'));
//      }
//      
//      OnlineList.onwindowresize();
      OnlineList.getOnlineList();
    });
  },
  
  getOnlineList : function () 
  {
    $.getJSON(OnlineList.onlineurl, {t : new Date().getTime()}, function (data) 
    {
//      var $ul = OnlineList.$right_onlinelist.children("ul");
//      $ul.children("li").addClass("gray");
//      for (var i = 0; i<data.length; i++)
//      {
//        var $lis = $ul.children("li").children("a[login_account='"+data[i].login_account+"']").parent().detach();
//        $lis.removeClass("gray");
//        $ul.prepend($lis);
//      }
      window.setTimeout(OnlineList.getOnlineList, 60*1000);
    });
  },
  
  right_onlinelist_mousewheel:function(e, delta)
  {
    if (delta > 0)
    {
      OnlineList.online_up_OnClick(OnlineList.$right_onlinelist.siblings("#online_up")[0]);
    }
    else if (delta < 0)
    {
      OnlineList.online_down_OnClick(OnlineList.$right_onlinelist.siblings("#online_down")[0]);
    }
    e.preventDefault();
  },
  
  online_up_OnClick : function (sender) 
  {
    var $sender = $(sender);
    var $ul = $sender.siblings().children(".online-list-user");
    
    var t = $ul.offset();
    var delta = -(t.top - $ul.parent().offset().top);
    
    if (delta > 0)
    {
      if (delta > OnlineList.LI_HEIGHT) $ul.offset({top: t.top + OnlineList.LI_HEIGHT});
      else $ul.offset({top: t.top + delta});
    }
  },
  
  online_down_OnClick : function (sender) 
  {
    var $sender = $(sender);
    var $ul = $sender.siblings().children(".online-list-user");
    
    var t = $ul.offset();
    var delta = (t.top - $ul.parent().offset().top) + $ul.height() - $ul.parent().height();
    
    if (delta > 0)
    {
      if (delta > OnlineList.LI_HEIGHT) $ul.offset({top: t.top - OnlineList.LI_HEIGHT});
      else $ul.offset({top: t.top - delta});
    }
  },
  
  online_expand_OnClick: function (sender) 
  {
    var $sender = $(sender);
    
    $sender.hide().siblings().show().parent().siblings("div.online-list-box").show();
  },
  
  online_collapse_OnClick: function (sender) 
  {
    var $sender = $(sender);
    
    $sender.hide().siblings().show().parent().siblings("div.online-list-box").hide();
  }
};