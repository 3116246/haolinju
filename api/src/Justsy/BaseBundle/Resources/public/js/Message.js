var Message={
	 maxNum :99,
	 mRefreshTimer:null,
	 nRefreshTimer:null,
	 pRefreshTimer:null,
	 refreshTimerLong:1000*60,//定时获取时间间隔
	 _messageUrl:"",      //消息更新地址
	 _noticeUrl:"",       //通知更新地址
	 _privateMsgUrl:"",   //私信更新地址
   load:function(mUrl,nUrl,pUrl)
   {
      this._messageUrl = mUrl;
      this._noticeUrl = nUrl;
      this._privateMsgUrl = pUrl;
      if(mUrl!=null) this.loadMessage();
      if(nUrl!=null) this.loadNotice();
      if(pUrl!=null) this.loadPrivateMsg();
   },
   loadMessage:function()
   {
   	  if(Message.mRefreshTimer!=null) clearTimeout(Message.mRefreshTimer);
	    $.post(Message._messageUrl,"",function(data){
	    	  var num = data*1>Message.maxNum?Message.maxNum:data*1;  //最多显示maxNum条
		    	if(num==0)
		    	  $(".icon_message span").hide();
		    	else
	    		$(".icon_message span").show();
	    	  $(".icon_message span").text(num);
	    	  Message.mRefreshTimer = setTimeout("Message.loadMessage()",Message.refreshTimerLong);
      })	
   },
   loadNotice:function()
   {
   	  if(Message.nRefreshTimer!=null) clearTimeout(Message.nRefreshTimer);
	    $.post(Message._noticeUrl,"",function(data){
	       var num = data*1>Message.maxNum?Message.maxNum:data*1;  //最多显示maxNum条
	    	if(num==0)
	    	  $(".icon_notice span").hide();  
	    	else
    		$(".icon_notice span").show();
    	  $(".icon_notice span").text(num);
    	  Message.nRefreshTimer = setTimeout("Message.loadNotice()",Message.refreshTimerLong);
      });   	
   },
   loadPrivateMsg:function()
   {
      	
   },
   deleteMsg:function(Aurl, msgIdStr,fuc)
   {
   	  var url = Aurl;
   	  $.getJSON(url,
   	  {
   	  	msg_id_str:msgIdStr
   	  },
   	  function(data)
   	  {
   	  	if (data.success == "1")
   	  	{
   	  		fuc();
   	  	}
   	  });
   },
   
  getPage : function (i)
  {
    var $messagebox = $("#messagebox");
    var msgtype = $messagebox.children("input.msgtype:hidden").val();
    $messagebox.empty();
  	LoadComponent("messagebox", $messagebox.attr("getmsgurl")+"/"+ msgtype +"/"+i.toString());
  },
  deleteAll:function ()
  {
    var msg_ids = $(".messagelist input:checkbox:checked").parent().siblings("input.msg_id:hidden").map(function () 
      {
        return $(this).val();
      })
      .toArray().join(",");
      
    var $messagebox = $("#messagebox");
    var msgtype = $messagebox.children("input.msgtype:hidden").val();
    var pageindex = $messagebox.children("input.pageindex:hidden").val();
    $messagebox.empty();
    $messagebox.prepend("<div class='urlloading'><div /></div>");
  	Message.deleteMsg($messagebox.attr("delmsgurl"),msg_ids,function(){
      $messagebox.empty();
  	  LoadComponent('messagebox', $messagebox.attr("getmsgurl")+"/"+ msgtype +"/"+pageindex);
  	});
  },
  
  toShowDetail:function (e)
  {
    var msg_id = $(e).siblings("input.msg_id:hidden").val()
    var $messagebox = $("#messagebox");
    var re_type =  $messagebox.children("input.msgtype:hidden").val();
    var re_pageindex = $messagebox.children("input.pageindex:hidden").val();
    
    $messagebox.empty();
    
    LoadComponent("messagebox", 
      $messagebox.attr("detailurl")+"/"+msg_id.toString(), 
      {
        re_type: re_type, 
        re_pageindex: re_pageindex
      });
  },
  
  deMsg:  function ()
  {
    var $messagebox = $("#messagebox");
    var nextone = $messagebox.children("input.nextone:hidden").val();
    var re_type =  $messagebox.children("input.re_type:hidden").val();
    var re_pageindex = $messagebox.children("input.re_pageindex:hidden").val();
    var msg_id = $messagebox.children("input.msg_id:hidden").val();
    $messagebox.empty();
    $messagebox.prepend("<div class='urlloading'><div /></div>");
  	Message.deleteMsg($messagebox.attr("delmsgurl"), msg_id, function(){
      $messagebox.empty();
      if (nextone && nextone != "")
      {
        LoadComponent("messagebox", 
          $messagebox.attr("detailurl")+"/"+nextone, 
          {
            re_type: re_type, 
            re_pageindex: re_pageindex
          });
      }
      else
      {
        LoadComponent('messagebox', $messagebox.attr("getmsgurl")+"/"+ 1 +"/"+1);
      }
  	});
  },
  
  returnPrePage: function()
  {
    var $messagebox = $("#messagebox");
    var re_type = $messagebox.children("input.re_type:hidden").val();
    var re_pageindex = $messagebox.children("input.re_pageindex:hidden").val();
    
    $messagebox.empty();
    LoadComponent('messagebox', $messagebox.attr("getmsgurl")+"/"+ re_type +"/"+re_pageindex);
  },
  
  gotoLastMsg : function() 
  {
    var $messagebox = $("#messagebox");
    
    var lastone = $messagebox.children("input.lastone:hidden").val();
    var re_type = $messagebox.children("input.re_type:hidden").val();
    var re_pageindex = $messagebox.children("input.re_pageindex:hidden").val();
    
    $messagebox.empty();
    
    LoadComponent("messagebox", 
      $messagebox.attr("detailurl")+"/"+lastone, 
      {
        re_type: re_type, 
        re_pageindex: re_pageindex
      });
  },
  
  gotoNextMsg : function() 
  {
    var $messagebox = $("#messagebox");
    
    var nextone = $messagebox.children("input.nextone:hidden").val();
    var re_type = $messagebox.children("input.re_type:hidden").val();
    var re_pageindex = $messagebox.children("input.re_pageindex:hidden").val();
    
    $messagebox.empty();
    
    LoadComponent("messagebox", 
      $messagebox.attr("detailurl")+"/"+nextone, 
      {
        re_type: re_type, 
        re_pageindex: re_pageindex
      });
  },
  
  replyMsg : function() 
  {
    var $messagebox = $("#messagebox");
    var msg_id = $messagebox.children("input.msg_id:hidden").val();
    $messagebox.empty();
    LoadComponent('messagebox', $messagebox.attr("pushurl")+'/'+msg_id);
  },
  
  
  publishMsg:function (Aurl)
  {
    	var titleValue=$("#title").val();
    	var MsgValue=$("#Msg").val();
    	if (!MsgValue || MsgValue == $("#Msg").attr("placeholder")||!titleValue||titleValue==$("#title").attr("placeholder")) return;
	    $.getJSON(Aurl,
	    {
	    	 titl:titleValue,
	    	 msg:MsgValue,
	    	 txtNotify:GetNotifyStaff(),
	    	 attachs:GetInputAttach(), 
	    	 t: new Date().getTime()
	    },
	    function(json)
	    {
        var $messagebox = $("#messagebox");
        $messagebox.empty();
	    	$messagebox.append('<div class="no-messages"><img width="26" height="27" class="prompticon">发布成功！</div>');
	    });
  },
  
  mobileRegApprove : function(url,account)
  {
    $.post(url,{account:account},function(data){},"text");
    $(".messagebutton[value='<< 返回']").first().click();
  },
  
  refuseJoinCircle : function(_url,para)
  {
    $.post(_url,{para:para},function(data){},"text");
    $(".messagebutton[value='<< 返回']").first().click();
  }
};