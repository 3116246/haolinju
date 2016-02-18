var ReplyIn = {
  //收到的评论 相关的JS
  
  ReplyTemplate : '<div class="detailinput replyinputarea" style="background-color: #F8F8F8;padding: 4px 10px 0; position: relative;"><div class="reply_to_box" style="display:block;"><span class="reply_to_close" onclick="ReplyIn.reply_to_close_OnClick(this)">×</span><span>你对<a href="#" class="reply_to_staff employee_name" login_account="[LOGIN_ACCOUNT]">[REPLY_TO_STAFF]</a>说：</span></div><textarea rows="1" class="comtextarea userAutoTips FaceAutoTips" style="height:36px;border: 1px solid  #4D9CD4;margin: 0;" onkeyup="ReplyIn.setReplyEnable(this)" onblur="ReplyIn._setReplyDisable(this)"></textarea><div style="text-align: right; margin: 2px auto;"><input type="button" class="comenter" value="确 定" disabled="disabled" onclick="ReplyIn.ReplyConv(this)"></div></div>',
  ReplyTipTemplate : '<div style="font-weight: bolder; position: absolute; text-align: center; top: 33px; width: 405px; left: 16px; font-size: 16px; color: #0088CC; border: 1px solid #F8F09E; background-color: #FEFFE2; line-height: 31px;">发布成功</div>',
  
  reply_to_link_OnClick : function (sender) {
    var $sender = $(sender);
    var $replylist_item_content = $sender.parents(".replylist-item-content");
    var $replyinputarea = $replylist_item_content.children(".replyinputarea");
    var $speak_staff = $replylist_item_content.find(".speak_staff");
    
    if ($replyinputarea.length == 0)
    {
      $replylist_item_content.append(
        this.ReplyTemplate.replace("[LOGIN_ACCOUNT]", $speak_staff.attr("login_account"))
                          .replace("[REPLY_TO_STAFF]", $speak_staff.text()));
    }
    else
    {
      $replyinputarea.toggle();  
    }
    $replylist_item_content.find("textarea.comtextarea").focus();
  },
  
  reply_to_close_OnClick : function (sender) {
    $(sender).parents(".replyinputarea").toggle();
  },
  
  setReplyEnable : function (sender) {
    $(sender).parents("div.replyinputarea").find("input.comenter").removeAttr("disabled");  
  },
  
  _setReplyDisable : function (sender) {
    var $sender = $(sender);
    if ($sender.val() == "")
    {
      this.setReplyDisable(sender);
    }
  },    
    
  setReplyDisable : function (sender) 
  {
     $(sender).parents("div.replyinputarea").find("input.comenter").attr("disabled", "disabled");
  },
  
  ReplyConv : function (sender) {
    var $sender = $(sender);
    var $replyinputarea = $sender.parents("div.replyinputarea");
    var $comtextarea = $replyinputarea.find("textarea.comtextarea");
    var ReplayValue = $comtextarea.val();
    var reply_to = $comtextarea.siblings("div.reply_to_box").find("a.reply_to_staff").attr("login_account");
    var reply_to_name = $comtextarea.siblings("div.reply_to_box").find("a.reply_to_staff").text();
    
    if (ReplayValue == "") return;
    
    var Aurl = $("#replyurl").val();
    var conv_root_id = $sender.parents(".replylist-item").children(".conv_root_id").val();
    
    this.setReplyDisable(sender);
    this.setReplying(sender, true);
    var that = this;
    $.post(Aurl,
      {
        conv_root_id : conv_root_id,
        replayvalue : ReplayValue,
        reply_to : reply_to,
        reply_to_name : reply_to_name,
  //      notifystaff : GetNotifyStaff(),
  //      attachs : GetInputAttach(),
  //      post_to_group: $("#hpost_to_group").val()
        t : new Date().getTime()
      },
      function (data) 
      {
          $comtextarea.val("");
          that.setReplying(sender, false);
          $(that.ReplyTipTemplate).appendTo($replyinputarea).toggle().fadeIn("slow", function () {
            $replyinputarea.remove();
          });          
      }
    );  
  },  
    
  setReplying : function (sender, isstart) 
  {
    $(sender).val(isstart ? "发布..." : "确 定");
  }

};

var  ReplyOut = {
  //发出的评论 相关的JS
  
  TemplateDelConv : '<div class="del"><p class="deltext">您确认要删除这条信息吗？</p><p class="delbox"><img class="deling" src="[DELING_IMG_URL]" style="display:none; height: 24px; width: 24px; margin-left:20px; margin-right: 30px;"><input type="button" class="delbutton" value="删 除" onclick="ReplyOut.DelReplyOK(this)"><input type="button" class="escbutton" value="取 消" onclick="ReplyOut.DelReplyCancel(this)"></p></div>',
  
  DelReply : function (sender) { 
    $(sender).parent().parent().append(this.TemplateDelConv.replace("[DELING_IMG_URL]", g_resource_context + "bundles/fafatimewebase/images/loading.gif"));
  },
  
  DelReplyOK : function (sender) {
    var $sender = $(sender);
    var $replylist_item = $sender.parents(".replylist-item");
    var Aurl = $("#delreplyurl").val();
    var conv_id = $replylist_item.children(".conv_id").val();
    
    $(sender).hide();
    $(sender).siblings("img.deling").show();
    $.getJSON(Aurl, {conv_id : conv_id, t: new Date().getTime()}, function (data) 
    {
      //成功后删除显示
      $replylist_item.fadeOut("slow", function () 
      {        
        $replylist_item.remove();
      })
    });
  },
  
  DelReplyCancel : function (sender) {
    $(sender).parent().parent().remove();
  }
};