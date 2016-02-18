//-------------Tab选择----------
function setDisplayTab(sender)
{
    var $sender;
    if (sender == null) {//代表是直接刷新该tab
        $sender = $("div.tabbox li.hover");
        $sender.attr('isloadtop', '0');
        $("#con_" + $sender.attr("id")).html(null);
    }
    else {
        $sender = $(sender);
        $sender.siblings().removeClass("hover");
        $sender.addClass("hover");

        //$(".contentbox .display_con_area").hide();
        $(".contentbox #con_" + $sender.attr("id")).show().siblings().hide();

        if ($sender.attr("isloaded"))
            return;

        $sender.attr("isloaded", "1");
    }
    LoadComponent("con_" + $sender.attr("id"), $sender.attr("loadurl"), {network_domain: g_curr_network_domain,trend:manager_trend});
}

//-------------------------------------------
function DelConv(sender, Aurl, conv_root_id, deling_img_url)
{
    var TemplateDelConv = '<div class="del"><p class="deltext">您确认要删除这条信息吗？</p><p class="delbox"><img class="deling" src="' + deling_img_url + '" style="display:none; height: 24px; width: 24px; margin-left:20px; margin-right: 30px;"><input type="button" class="delbutton" value="删 除" onclick="DelConvOK(this, \'' + Aurl + '\', \'' + conv_root_id + '\')"><input type="button" class="escbutton" value="取 消" onclick="DelConvCancel(this)"></p></div>';
    $(sender).parent().parent().parent().append(TemplateDelConv);
}
function DelConvOK(sender, Aurl, conv_root_id)
{
    $(sender).hide();
    $(sender).siblings("img.deling").show();
    $.getJSON(Aurl, {conv_root_id: conv_root_id, t: new Date().getTime()}, function(data)
    {
        //成功后删除li
        var $li = $(sender).parent().parent().parent().parent().parent().fadeOut("slow", function()
        {
            $li.remove();
        })
    });
}
function DelConvCancel(sender)
{
    $(sender).parent().parent().remove();
}

function DelTrend(sender, Aurl, conv_root_id, deling_img_url)
{
    return DelConv(sender, Aurl, conv_root_id, deling_img_url);
}

function DelAsk(sender, Aurl, conv_root_id, deling_img_url)
{
    return DelConv(sender, Aurl, conv_root_id, deling_img_url);
}

function DelTogether(sender, Aurl, conv_root_id, deling_img_url)
{
    return DelConv(sender, Aurl, conv_root_id, deling_img_url);
}

function DelVote(sender, Aurl, conv_root_id, deling_img_url)
{
    return DelConv(sender, Aurl, conv_root_id, deling_img_url);
}

function DelReply(sender, Aurl, conv_id, deling_img_url)
{
    var TemplateDelConv = '<div class="del"><p class="deltext">您确认要删除这条信息吗？</p><p class="delbox"><img class="deling" src="' + deling_img_url + '" style="display:none; height: 24px; width: 24px; margin-left:20px; margin-right: 30px;"><input type="button" class="delbutton" value="删 除" onclick="DelReplyOK(this, \'' + Aurl + '\', \'' + conv_id + '\')"><input type="button" class="escbutton" value="取 消" onclick="DelReplyCancel(this)"></p></div>';
    $(sender).parent().parent().append(TemplateDelConv);
}
function DelReplyOK(sender, Aurl, conv_id)
{
    $(sender).hide();
    $(sender).siblings("img.deling").show();
    $.getJSON(Aurl, {conv_id: conv_id, t: new Date().getTime()}, function(data)
    {
        //成功后删除li
        var $li = $(sender).parent().parent().parent().parent().parent().parent().fadeOut("slow", function()
        {
            $replynum = $(sender).parents("div.convbox").find("span.replynum");
            var n = new Number($replynum.first().text());
            $replynum.text(n - 1);

            $li.remove();
        })
    });
}
function DelReplyCancel(sender)
{
    $(sender).parent().parent().remove();
}

//-------------------------------------------
function LikeConv(sender, conv_root_id)
{
    var $sender = $(sender);
    $sender.hide();
    $sender.siblings("img.liking").show();
    var Aurl = $sender.attr("likeurl");
    $.getJSON(Aurl, {conv_root_id: conv_root_id, t: new Date().getTime()}, function(data)
    {
        //成功
        if (data.success == "1")
        {
            $sender.text("取消赞");
            $sender.attr("onclick", "UnLikeConv(this, '" + conv_root_id + "')");
            $sender.show();
            $sender.siblings("img.liking").hide();

            var $likebox = $sender.parents("div.convbox").find(".convdetail").children("div.like_outbox");
            $likebox.fadeOut("slow", function()
            {
                $likebox.find(".likeboxpart").after('<span class="likename" staff="' + data.like_staff + '"><a class="employee_name" login_account="' + data.like_staff + '" href="#">' + data.nick_name + '</a></span>');
                $likebox.fadeIn("slow");
            })
        }
    });
}

function UnLikeConv(sender, conv_root_id)
{
    var $sender = $(sender);
    $sender.hide();
    $sender.siblings("img.liking").show();
    var Aurl = $sender.attr("unlikeurl");
    $.getJSON(Aurl, {conv_root_id: conv_root_id, t: new Date().getTime()}, function(data)
    {
        //成功
        if (data.success == "1")
        {
            $sender.text("赞");
            $sender.attr("onclick", "LikeConv(this, '" + conv_root_id + "')");
            $sender.show();
            $sender.siblings("img.liking").hide();

            var $likebox = $sender.parents("div.convbox").find(".convdetail").children("div.like_outbox");
            $likebox.fadeOut("slow", function()
            {
                $likebox.find("span[staff='" + data.like_staff + "']").remove();
                if ($likebox.find("div.likebox").children().length > 1)
                    $likebox.fadeIn("slow");
            })
        }
    });
}
function ReplyLikeConv(sender, conv_root_id)
{
    var $sender = $(sender);
    $sender.hide();
    $sender.siblings("img.liking").show();
    var Aurl = $sender.attr("likeurl");
    $.getJSON(Aurl, {conv_root_id: conv_root_id, t: new Date().getTime()}, function(data)
    {
        //成功
        if (data.success == "1")
        {
            $sender.text("取消赞");
            $sender.attr("onclick", "ReplyUnLikeConv(this, '" + conv_root_id + "')");
            $sender.show();
            $sender.siblings("img.liking").hide();

            var $likebox = $sender.parents(".replyfunbox").siblings("div.like_outbox");
            $likebox.fadeOut("slow", function()
            {
                $likebox.find(".likeboxpart").after('<span class="likename" staff="' + data.like_staff + '"><a class="employee_name" login_account="' + data.like_staff + '" href="#">' + data.nick_name + '</a></span>');
                $likebox.fadeIn("slow");
            })
        }
    });
}

function ReplyUnLikeConv(sender, conv_root_id)
{
    var $sender = $(sender);
    $sender.hide();
    $sender.siblings("img.liking").show();
    var Aurl = $sender.attr("unlikeurl");
    $.getJSON(Aurl, {conv_root_id: conv_root_id, t: new Date().getTime()}, function(data)
    {
        //成功
        if (data.success == "1")
        {
            $sender.text("赞");
            $sender.attr("onclick", "ReplyLikeConv(this, '" + conv_root_id + "')");
            $sender.show();
            $sender.siblings("img.liking").hide();

            var $likebox = $sender.parents(".replyfunbox").siblings("div.like_outbox");
            $likebox.fadeOut("slow", function()
            {
                $likebox.find("span[staff='" + data.like_staff + "']").remove();
                if ($likebox.find("div.likebox").children().length > 1)
                    $likebox.fadeIn("slow");
            })
        }
    });
}

//-------------------------------------------
//回复链接点击
function ReplyLink_OnClick(sender)
{
    $divcomment = $(sender).parents("div.convbox").find("div.comment");
    if ($.browser.msie && $.browser.version == 7)
        $divcomment.toggle().toggle().toggle();
    else
        $divcomment.toggle();
    if ($divcomment.css("display") != "none")
    {
        $divcomment.find("textarea.comtextarea").focus();
    }
    var $relay=$divcomment.find("span.reply_to_close");
    if($relay.length >0)
    	reply_to_close_OnClick($relay[0]);
}

function replyinputarea_pre_OnClick(sender)
{
    var $sender = $(sender);
    $sender.hide();
    $sender.siblings(".replyinputarea").show().children("textarea.comtextarea").focus();
}

//设置允许回复
function setReplyEnable(sender)
{
    $(sender).parents("div.commentbox").find("input.comenter").removeAttr("disabled");
}

//设置不许回复
function setReplyDisable(sender)
{
    $(sender).parents("div.commentbox").find("input.comenter").attr("disabled", "disabled");
}
function _setReplyDisable(sender)
{
    setTimeout(function() {
        var $sender = $(sender);
        if ($sender.val() == "" && $sender.siblings("div.reply_to_box").css("display") == "none")
        {
            setReplyDisable(sender);
            var $replyinputarea_pre = $(sender).parent().siblings(".replyinputarea_pre");
            $replyinputarea_pre.show().siblings(".replyinputarea").hide();
        }
    }, 100);
}

function reply_insertFace(sender)
{
    var t = $(sender).parents(".commentbox").find(".comtextarea")[0];

    var cp = t.selectionStart;
    var ubbLength = t.value.length;
    t.value = t.value.slice(0, t.selectionStart) + "[" + t.value.slice(t.selectionStart, ubbLength);
    if (document.selection)
    {
        if (!cp)
            cp = 0;
        var range = t.createTextRange();
//			range.moveEnd('character', -t.value.length);         
        range.moveEnd('character', cp + 1);
        range.moveStart('character', cp + 1);
        range.select();
        t.selectionStart = cp + 1;
    }
    else
    {
        t.setSelectionRange(cp + 1, cp + 1);
    }
    setTimeout(function() {
        $(t).focus().keyup();
    }, 500);
}

var insertFile_menu_Template = '<a id="[DROPDOWNID]" class="dropdown-toggle" style="float:left;width:100%;height:100%;" role="button" data-toggle="dropdown" href="[MENUPICID]"></a><div class="replyuploadingfilebox" style="display: none;font-style: normal;text-align: left;width: 300px;"><div style="border: 1px solid #CCCCCC;display: inline-block;height: 20px;line-height: 20px;margin-left: 4px;margin-top: -4px;max-width: 270px;overflow: hidden;padding: 0 4px;word-break: break-all;position: relative;"><div style="position: absolute; width: 100%; background-color: red;"></div><img src="[LOADINGSMALLSRC]"/><span>正在上传：</span><span class="uploadingfilename"></span><div class="uploadingfileprogress"></div></div></div><ul id="[MENUPICID]" class="dropdown-menu picmenu" role="menu" aria-labelledby="[DROPDOWNID]"><li><a class="replyfilesel_a" tabindex="-1" style="overflow:hidden;"><form name="upload_file"  method="post" enctype="multipart/form-data"><input name="hpost_to_group" type="hidden" value="[HPOST_TO_GROUP]"/><span style="cursor:pointer;display: inline-block;position: relative;width: 154px;"><input type=hidden name="uploadSourcePage" value="home"><input tabindex="-1" name="filedata" hidefocus="hidefocus"  onchange="replyfileSelect(this)" size="1" style="cursor: pointer;left: 0;filter:alpha(opacity = 0); width:70px;opacity:0;-moz-opacity:0;position: absolute;top: 0;z-index: 1000002;outline: none;" multiple="" type="file">上传一个图片/文件</span></form></a></li><li class="picmenuspliter"><a tabindex="-1" href="#" class="picmenuspliter_a"></a></li><li><a tabindex="-1" data-toggle="modal" show=false href="#openfiledig_replyfile" >选择一个已有图片/文件</a></li></ul>';
var insertFile_filediag_Template = '<div class="modal" id="openfiledig_replyfile" data-backdrop=false style="display:none;width:735px;margin-left: -430px;" show=false> <div class="modal-header" style="padding: 0;"> <a class="close" data-dismiss="modal">×</a> <h5 style="margin: 0 0 0 10px;line-height: 25px;">选择一个文档</h5> </div> <div id="openfiledig_replyfile_files" class="modal-body" style="padding:1px;max-height: 450px;"> </div> <div class="modal-footer" style="padding: 5px 5px 5px 0;"> <a href="javascript:insertReplyFileID();" class="btn btn-primary insertSelFile" style="color:white;display:none;">添加附件</a> <A class=btn href="#" data-dismiss="modal" >关闭</A> </div> </div>';
function reply_insertFile(sender)
{
    var $sender = $(sender);
    var $t = $(sender).parents(".commentbox").find(".comtextarea");

    if ($t.val() == "") $t.val(" ");

    //增加菜单
    if (!$sender.hasClass('dropdown')) 
    {
        var dropdownid = "drop_picmenu_" + new Date().getTime();
        var menuPicid = "menuPic_" + new Date().getTime();      
        $sender.append(insertFile_menu_Template
            .replace("[MENUPICID]", menuPicid)
            .replace("[MENUPICID]", menuPicid)
            .replace("[DROPDOWNID]", dropdownid)
            .replace("[DROPDOWNID]", dropdownid)
            .replace("[LOADINGSMALLSRC]", g_resource_context+"bundles/fafatimewebase/images/loadingsmall.gif")
            .replace("[HPOST_TO_GROUP]", $("#hpost_to_group").val()));

        $sender.find(".replyfilesel_a").mousemove(function (e) 
        { 
            var $document = $(document); //document
            var offsetA = $(this).offset();
            var newX = e.clientX - (offsetA.left - $document.scrollLeft()) - 50;
            var newY = e.clientY - (offsetA.top - $document.scrollTop()) - 20;
            
            $(this).find("input:file").css("left", newX);
            $(this).find("input:file").css("top",  newY);
        });

        $sender.addClass('dropdown').addClass('open'); 

        if ($("#openfiledig_replyfile").length == 0)
        {
            $("body").append(insertFile_filediag_Template);
        }
    };
    //显示
    var dropdownid = $sender.children('a').first().attr('id');
    $("#openfiledig_replyfile")
        .attr('dropdownid', dropdownid)
        .off('shown')
        .on('shown', 
            {Aurl:g_documnet_selectUrl, dropdownid:dropdownid}, 
            openfiledig_replyfile_OnShown);
}

function openfiledig_replyfile_OnShown(param)
{
    $("#"+param.data.dropdownid).parent().removeClass('open');
    if ($("#openfiledig_replyfile").attr("isloaded") != "1")
    {
        LoadComponent("openfiledig_replyfile_files", param.data.Aurl);
        $("#openfiledig_replyfile").attr("isloaded", "1");
    }
}

function insertReplyFileID()
{
    var f = getCurrentSelectedFileID();
    if (f.fileId && f.fileId != "")
        addReplyFile(f.fileName, f.fileId);
    $('#openfiledig_replyfile').modal('hide');
}

function addReplyFile(fn, fileid)
{
    var $lib_attachs_filesList = $("#"+$("#openfiledig_replyfile").attr("dropdownid")).parents(".commentbox").find(".lib_attachs_filesList");

    if ($lib_attachs_filesList.find("input[value='" + fileid + "']").length > 0)
        return;

    var Template1 = '<span class="NotifyObj"><input type="hidden" value="' + fileid + '"><span class="NotifyPerson">' + fn + '</span><span class="NotifyClose" onclick="ReplyAttachClose_OnClick(this)">×</span></span>';
    $lib_attachs_filesList.append(Template1);
    $lib_attachs_filesList.parent().show();
}

function ReplyAttachClose_OnClick(sender)
{
    var $sender = $(sender);
    var $lib_attachs_filesList = $sender.parents(".lib_attachs_filesList");

    $sender.parent().remove();
    if ($lib_attachs_filesList.children().length == 0)
        $lib_attachs_filesList.parent().hide();
}

function replyfileSelect(sender)
{
    var $sender = $(sender);
    var fns = $sender.val().split("\\");
    fn = fns[fns.length - 1]; 
    var $dropdown_box = $sender.parents(".dropdown");
    $dropdown_box.removeClass('open');
    $dropdown_box.find(".replyuploadingfilebox").show().find(".uploadingfilename").text(fn);
    $dropdown_box.find("form").ajaxSubmit({
        dataType: 'json', //返回的数据类型
        url: g_documnet_uploadUrl, //表单的action
        method: 'post',
        uploadProgress: function(e, p, t, per){
            $dropdown_box.find(".uploadingfileprogress").css("width", per+"%"); 
        },
        success: function(r) {
            if (r.succeed)
            {
                addReplyFile(fn, r.fileid); 
                $dropdown_box.find(".replyuploadingfilebox").hide().find(".uploadingfilename").text("");
            }
            else
            {
                $dropdown_box.find(".replyuploadingfilebox").find(".uploadingfilename").text('文件上传失败！'); 
            }
            $dropdown_box.find(".uploadingfileprogress").css("width", "0%"); 
        }
    });
}

function GetReplyInputAttach(jsender)
{
    var re = $.unique(jsender.parents("div.commentbox").find(".lib_attachs_filesList input")
        .map(function() {
            return $(this).val();
        }))
        .toArray();
    return re;
}

function GetReplyInputAttachName(jsender)
{
    var re = $.unique(jsender.parents("div.commentbox").find(".lib_attachs_filesList span.NotifyPerson")
        .map(function() {
            return $(this).text();
        }))
        .toArray();
    return re;
}

function ClearReplyInputAttach(jsender)
{
    jsender.parents("div.commentbox").find(".lib_attachs_filesList").empty().parent().hide();
}

//回复
function ReplyConv(sender)
{
    var $sender = $(sender);
    var $comtextarea = $sender.parents("div.commentbox").find("textarea.comtextarea");
    var ReplayValue = $comtextarea.val();
    var reply_to = $comtextarea.siblings("div.reply_to_box").find("a.reply_to_staff").attr("login_account");
    var reply_to_name = $comtextarea.siblings("div.reply_to_box").find("a.reply_to_staff").text();

    if (ReplayValue == "")
        return;

    var Aurl = $sender.attr("replyurl");
    var conv_root_id = $sender.attr("conv_root_id");

    setReplyDisable(sender);
    setReplying(sender, true);
    $.post(Aurl,
            {
            	  trend: manager_trend,
                conv_root_id: conv_root_id,
                replayvalue: ReplayValue,
                reply_to: reply_to,
                reply_to_name: reply_to_name,
//      notifystaff : GetNotifyStaff(),
     attachs : GetReplyInputAttach($sender),
     attachs_name: GetReplyInputAttachName($sender),
//      post_to_group: $("#hpost_to_group").val(),
                t: new Date().getTime()
            },
    function(data)
    {
        $comtextarea.val("");
        setReplying(sender, false);

        var $replyinputarea_pre = $comtextarea.parent().siblings(".replyinputarea_pre");
        $replyinputarea_pre.show().siblings(".replyinputarea").hide();

        ClearReplyInputAttach($sender);

        //显示新增回复
        var $replyul = $sender.parents("div.commentbox").find("div.smalltopic").children("ul");
        $replyul.prepend(data);
        $replynum = $(sender).parents("div.convbox").find("span.replynum");
        var n = new Number($replynum.first().text());
        $replynum.text(n + 1);
        $replyul.children("li").first().css("display", "none").fadeIn("slow");
    }
    );
}
function setReplying(sender, isstart)
{
    $(sender).val(isstart ? "发布..." : "确 定");
}

function reply_to_close_OnClick(sender)
{
    var $sender = $(sender);
    $sender.siblings("span").children(".reply_to_staff").attr("login_account", "").text("");
    $sender.parent().hide();
}
function reply_to_link_OnClick(sender)
{
    var $sender = $(sender);
    var $reply_to_box = $sender.parents("div.commentbox").find("div.reply_to_box").show();
    var $reply_to_staff = $reply_to_box.find("a.reply_to_staff");
    var $target_staff = $sender.parent().parent().siblings("p.reply_content").children("a.employee_name").first();

    $reply_to_staff.attr("login_account", $target_staff.attr("login_account")).text($target_staff.text());
    replyinputarea_pre_OnClick($sender.parents("div.commentbox").children("div.replyinputarea_pre")[0]);
}

//-------------------------------------------
//投票
function btnVote_OnClick(sender)
{
    var $sender = $(sender);
    var Aurl = $sender.siblings(".ht_vote_url").val();
    var vote_id = $sender.siblings(".ht_vote_id").val();
    var is_multi = $sender.siblings(".ht_is_multi").val();
    var optionids = "";

    if (is_multi == "0")
    {
        optionids = $sender.parent().siblings(".votelist").find("input:radio:checked").val();
    }
    else
    {
        optionids = $sender.parent().siblings(".votelist").find("input:checkbox:checked").map(function() {
            return $(this).val();
        }).toArray();
    }
		if(optionids=="" || typeof(optionids)=='undefined'){
    	$(sender).siblings("span.vote_notice").css('color','red').text('请选择投票选项！').show();
    	var $e=$(sender).siblings("span.vote_notice");
    	setTimeout(function(){
    		$e.hide();
    	},3000);
    	return;
    }
    $sender.attr("disabled", "disabled");
    $sender.val("投票中...");
    $.post(Aurl,
            {
            	  trend:manager_trend,
                vote_id: vote_id,
                is_multi: is_multi,
                optionids: optionids
            },
    function(data)
    {
        var $li = $(data).children().children("li").css("display", "none");
        var $oldli = $sender.parent().parent().parent().parent();
        $oldli.after($li);
        $oldli.remove();
        $li.fadeIn("slow");
    }
    );
}

//------------转发----------------------
var $_CopyConvModal = null;
var _allow_copy = "1"; //1为不允许
var _curr_copyid = null;
var _curr_copylastid = null;
var _curr_copy_circle = null;
var _curr_copy_group = null;
var $_curr_copy_sender = null;
function CopyConvPc_OnClick(sender)
{
    var $sender = $(sender);

    $_curr_copy_sender = $sender;
    _curr_copyid = $sender.attr("copyid");
    _curr_copylastid = $sender.attr("copylastid");
    _curr_copy_circle = $sender.attr("circle_id");
    _curr_copy_group = $sender.attr("group_id");
    $_CopyConvModal = $("#_CopyConvModal");
    if ($_CopyConvModal.length == 0)
    {
        var t1 = '<div class="modal" id="_CopyConvModal" style="display:none;width:320px;margin-left: -245px;" show="false"><div class="modal-header"><a class="close" data-dismiss="modal">×</a>转发</div><div id="_CopyConvModalBody" class="modal-body" style="padding:14px;"><div class="urlloading"><div /></div></div></div>';
        $("body").append(t1);
        $_CopyConvModal = $("#_CopyConvModal");
        $_CopyConvModal.modal();
        $.get($sender.attr("tplurl"), {t: new Date().getTime()}, function(data)
        {
            $_CopyConvModal.children("#_CopyConvModalBody").empty().append(data);
            $_CopyConvModal.on('shown', _CopyConvModal_OnShown);
            _CopyConvModal_OnShown();
        });
    }
    else
        $_CopyConvModal.modal();
}
function CopyConv_OnClick(sender)
{
    var $sender = $(sender);

    $_curr_copy_sender = $sender;
    _curr_copyid = $sender.attr("copyid");
    _curr_copylastid = $sender.attr("copylastid");
    _curr_copy_circle = $sender.attr("circle_id");
    _curr_copy_group = $sender.attr("group_id");
    $_CopyConvModal = $("#_CopyConvModal");
    if ($_CopyConvModal.length == 0)
    {
        var t1 = '<div class="modal" id="_CopyConvModal" style="display:none;width:490px;margin-left: -245px;" show="false"><div class="modal-header"><a class="close" data-dismiss="modal">×</a>转发</div><div id="_CopyConvModalBody" class="modal-body" style="padding:14px;"><div class="urlloading"><div /></div></div></div>';
        $("body").append(t1);
        $_CopyConvModal = $("#_CopyConvModal");
        $_CopyConvModal.modal();
        $.get($sender.attr("tplurl"), {t: new Date().getTime()}, function(data)
        {
            $_CopyConvModal.children("#_CopyConvModalBody").empty().append(data);
            $_CopyConvModal.on('shown', _CopyConvModal_OnShown);
            _CopyConvModal_OnShown();
        });
    }
    else
        $_CopyConvModal.modal();
}
function _CopyConvModal_OnShown()
{
    //初始化
    HideCopyConvErr();
    $_CopyConvModal.find("#btnCopyConv").attr("disabled", "disabled");

    var $convbox = $_curr_copy_sender.parents(".convbox");
    var conv_content = $convbox.find("span.conv_content").text();
    if (conv_content.length > 100)
        conv_content = conv_content.substr(0, 100) + "...";
    var $post_staffname = $convbox.find(".post_staffname");

    $_CopyConvModal.find("#copy_content").val("转发");
    if ($convbox.find("div.copyconv_d").length > 0) //如果已是转发信息
    {
        $_CopyConvModal.find("#copy_content").val("//@" + $post_staffname.text() + "{" + $post_staffname.attr("eshortname") + "}:" + conv_content);

        var oldconv_content = $convbox.find(".copyconv_d").children("span.repost").children("span").text();
        if (oldconv_content.length > 100)
            oldconv_content = oldconv_content.substr(0, 100) + "...";
        var $employee_name = $convbox.find(".copyconv_d").children("span.repost").children("a.employee_name");
        $_CopyConvModal.find(".oldconv_content").text("@" + $employee_name.text() + "{" + $employee_name.attr("eshortname") + "}:" + oldconv_content);
    }
    else
    {
        $_CopyConvModal.find(".oldconv_content").text("@" + $post_staffname.text() + "{" + $post_staffname.attr("eshortname") + "}:" + conv_content);
    }

    var $copy_circle_sel = $_CopyConvModal.find("#copy_circle_sel");
    var $copy_circle_sel_options = $copy_circle_sel.find("option");
    if (_curr_copy_group == "ALL")
    {
        if ($copy_circle_sel.val() == _curr_copy_circle && $copy_circle_sel_options.length <= 1)
        {
            //暂时去掉该限制，同一圈子内可以随便转 
//      ShowCopyConvErr("消息不能在同一个圈子或群组里转哟！");
//      return;
        }
        else
        {
            for (var i = 0; i < $copy_circle_sel_options.length; i++)
            {
                if ($copy_circle_sel_options.eq(i).val() != _curr_copy_circle)
                {
                    $copy_circle_sel.val($copy_circle_sel_options.eq(i).val());
                    break;
                }
            }
        }
    }
    else
    {
    }
    getAllowCopy($copy_circle_sel[0]);
    copy_circle_sel_OnChange($copy_circle_sel[0]);
}
function HideCopyConvErr()
{
    $_CopyConvModal.find("div.copyerrmsg").hide();
    $_CopyConvModal.find("#btnCopyConv").removeAttr("disabled");
    $_CopyConvModal.find("#copy_content").removeAttr("disabled");
}
function ShowCopyConvErr(msg)
{
    $_CopyConvModal.find("div.copyerrmsg").text(msg).show();
    $_CopyConvModal.find("#btnCopyConv").attr("disabled", "disabled");
    $_CopyConvModal.find("#copy_content").attr("disabled", "disabled");
}

function getAllowCopy(sender)
{
    $.getJSON($(sender).attr("allowurl"), {network_domain: g_curr_network_domain, t: new Date().getTime()}, function(data)
    {
        _allow_copy = data.allow_copy;
        copy_group_sel_OnChange();
    });
}

var txtcopy_to_group_source_url = "";
var txtcopy_to_group_source_circle = "";
function copy_circle_sel_OnChange(sender)
{
    var $sender = $(sender);
    var $copy_group_sel = $sender.siblings("#copy_group_sel");
    $copy_group_sel.children(":not(#grouploading)").hide();
    $copy_group_sel.children("#grouploading").show();

    //取得该圈子的该用户的群组
    txtcopy_to_group_source_url = $sender.attr("groupurl");
    txtcopy_to_group_source_circle = $sender.val();
    $.getJSON(txtcopy_to_group_source_url, {circle: txtcopy_to_group_source_circle, t: new Date().getTime()}, function(data)
    {
        txtcopy_to_group_source_all = data;
        for (var i = 0; i < txtcopy_to_group_source_all.length; i++)
        {
            txtcopy_to_group_source_all[i].index = i;
            txtcopy_to_group_source_all[i].toString = function() {
                return this.index;
            };
        }

        $copy_group_sel.children("#grouploading").hide();
        $("#acopy_to_group").text("全体成员").show().parent().parent().show();
        $("#hcopy_to_group").val("ALL");

        copy_group_sel_OnChange();
    });
}
function copy_group_sel_OnChange()
{
    //暂时去掉该限制，同一圈子内可以随便转 
//  if (txtcopy_to_group_source_circle == _curr_copy_circle
//    && (_curr_copy_group == "ALL" || _curr_copy_group == $("#hcopy_to_group").val()))
//  {
//    ShowCopyConvErr("消息不能在同一个圈子或群组里转哟！");
//  }
//  else 
    if (txtcopy_to_group_source_circle != _curr_copy_circle && _allow_copy == "1") //如果不是一个圈子，并且当前圈子不允许转发
    {
        ShowCopyConvErr("该条消息不能被转发哟！");
    }
    else
    {
        HideCopyConvErr();
    }
}

var txtcopy_to_group_source_all = [];
var txtcopy_to_group_source_100 = [];
function txtcopy_to_group_source(query, process)
{
    if (txtcopy_to_group_source_all.length > 0)
        return txtcopy_to_group_source_all;

    $.getJSON(txtcopy_to_group_source_url, {circle: txtcopy_to_group_source_circle, query: this.query, t: new Date().getTime()}, function(data)
    {
        txtcopy_to_group_source_100 = data;
        for (var i = 0; i < txtcopy_to_group_source_100.length; i++)
        {
            txtcopy_to_group_source_100[i].index = i;
            txtcopy_to_group_source_100[i].toString = function() {
                return this.index;
            };
        }
        process(txtcopy_to_group_source_100);
    })

    return null;
}
function txtcopy_to_group_highlighter(item)
{
    return "<span><img src='" + item.group_photo_url + "' style='width:48px; height:48px; margin-right:10px' onerror=\"this.src='" + g_resource_context + 'bundles/fafatimewebase/images/no_photo.png' + "'\"/>" + item.group_name + "</span>";
}
function txtcopy_to_group_matcher(item)
{
    if (this.query)
        return ~item.group_name.toLowerCase().indexOf(this.query.toLowerCase());
    else
        return true;
}
function txtcopy_to_group_sorter(items)
{
    return items;
}
function txtcopy_to_group_updater(item)
{
    var source = txtcopy_to_group_source_all.length > 0 ? txtcopy_to_group_source_all : txtcopy_to_group_source_100;
    $("#acopy_to_group").text(source[item].group_name).show();
    $("#hcopy_to_group").val(source[item].group_id);
    $("#txtcopy_to_group").hide();
    $("#bcancel_copy_to_group").show();
    this.query = "";
    copy_group_sel_OnChange();
    return "";
}
function acopy_to_group_OnClick()
{
    //如果未加入群，不可选
    if (txtcopy_to_group_source_all.length == 0)
    {
        $("#acopy_to_group").attr("title", "你还未加入该圈子的任何群，只能转给全体成员！");
        return;
    }
    else
    {
        $("#acopy_to_group").removeAttr("title");
    }

    $("#acopy_to_group").hide();
    $("#bcancel_copy_to_group").hide();
    var txtcopy_to_group = $("#txtcopy_to_group").show().focus();
    var thcopy_to_group = txtcopy_to_group.data('typeahead');
    thcopy_to_group.process(txtcopy_to_group_source_all);
}
function bcancel_copy_to_group_OnClick()
{
    $("#acopy_to_group").text("全体成员").show();
    $("#hcopy_to_group").val("ALL");
    $("#bcancel_copy_to_group").hide();
    copy_group_sel_OnChange();
}

function copy_content_OnBlur(sender)
{
    var $sender = $(sender);
    if ($sender.val().length == 0)
        $_CopyConvModal.find("#btnCopyConv").attr("disabled", "disabled");
    else
        $_CopyConvModal.find("#btnCopyConv").removeAttr("disabled");
}
//转发
function btnCopyConv_OnClick(sender)
{
    var $sender = $(sender);
    var copy_contentValue = $_CopyConvModal.find("#copy_content").val();

    if (!copy_contentValue)
        return;

    var hcopy_to_group_value = $("#hcopy_to_group").val();
    if (txtcopy_to_group_source_circle || txtcopy_to_group_source_circle != ""
            || hcopy_to_group_value || hcopy_to_group_value != "")
    {
    }
    else
    {
        alert("系统出错？无法转发");
        return;
    }

    $_CopyConvModal.modal('hide');
    $.post($sender.attr("copyurl"),
            {
            	  trend:manager_trend,
                copy_content: copy_contentValue,
                post_to_circle: txtcopy_to_group_source_circle,
                post_to_group: hcopy_to_group_value,
                copy_id: _curr_copyid,
                copy_last_id: _curr_copylastid,
                t: new Date().getTime()
            },
    function(data)
    {
        if (data.success == "1")
        {
            $sender.parents("div.convbox").find("span.copynum").each(function(i, el)
            {
                var $el = $(el);
                var n = new Number($el.text());
                $el.text(n + 1);
            });

            if (window.OnPublished)
                window.OnPublished(data.conv_id);
        }
        else
        {
            console.debug(data);
        }
    },
            "json"
            );
}

//------------收藏----------------------
function AttenConv_OnClick(sender)
{
    var TemplateAttenConv = '<div class="label_outbox AttenLabelBox" style="position: absolute; background: white; z-index: 999; right: 0px; bottom: 20px;"><span class="label_box label_box_edit" style="line-height: 24px; "><div style="color: #888; border-bottom: 1px solid;">收藏成功！你可输入标签以便分类</div><span style="color: #888;">标签：</span><span class="label_allname"></span><span class="label_delname"></span><span class="label_edit"> <input class="label_input" type="text" style="border: 1px solid;" onfocus="CollectLabel.label_input_OnFocus(this)"/> <input class="label_save" type="button" value="保存" atten_id="[ATTEN_ID]" onclick="AttenConvLabelSave_OnClick(this)"/> <input class="label_cancel" type="button" value="取消" onclick="AttenConvLabelCancel_OnClick(this)" /> </span></span></div>';

    var $sender = $(sender);
    $sender.children("span").hide();
    $sender.children("img.attening").show();
    var Aurl = $sender.attr("attenurl");
    var attenid = $sender.attr("attenid");
    $.getJSON(Aurl, {conv_root_id: attenid, t: new Date().getTime()}, function(data)
    {
        //成功
        if (data.success == "1")
        {
            var n = new Number($sender.find("span.attennum").text());
            $sender.find("span.attennum").text(n + 1);

            $sender.parent().parent().append(TemplateAttenConv.replace("[ATTEN_ID]", attenid));
            $sender.parent().parent().find("input.label_input").focus();
        }
        $sender.find("span.attentext").text("取消收藏");
        $sender.attr("onclick", "UnAttenConv_OnClick(this)");
        $sender.children("span").show();
        $sender.children("img.attening").hide();
    });
}
function AttenConv_OnClick_Pc(sender) {
    var TemplateAttenConv = '<div class="label_outbox AttenLabelBox" style="position: absolute; background: white; z-index: 999; right: 0px; bottom: 20px;"><span class="label_box label_box_edit" style="line-height: 24px; "><div style="color: #888; border-bottom: 1px solid;">收藏成功！你可输入标签以便分类</div><span style="color: #888;">标签：</span><span class="label_allname"></span><span class="label_delname"></span><span class="label_edit"> <input class="label_input" type="text" style="border: 1px solid;" onfocus="CollectLabel.label_input_OnFocus(this)"/> <input class="label_save" type="button" value="保存" atten_id="[ATTEN_ID]" onclick="AttenConvLabelSave_OnClick(this)"/> <input class="label_cancel" type="button" value="取消" onclick="AttenConvLabelCancel_OnClick(this)" /> </span></span></div>';

    var $sender = $(sender);
    $sender.children("span").hide();
    $sender.children("img.attening").show();
    var Aurl = $sender.attr("attenurl");
    var attenid = $sender.attr("attenid");
    $.getJSON(Aurl, {conv_root_id: attenid, t: new Date().getTime()}, function(data)
    {
        //成功
        if (data.success == "1")
        {
            var n = new Number($sender.find("span.attennum").text());
            if (n == null)
                n = 0;
            $sender.find("span.attennum").text(n + 1);
            var $p = $sender.parent().parent().parent();
            var le = 80;
            var to = $p.offset().top + $p.height() + 5;
            var $tem = $(TemplateAttenConv.replace("[ATTEN_ID]", attenid));
            $tem.css({left: (le.toString() + "px"), top: to.toString() + "px"});
            $(document.body).append($tem);
            $(document.body).find("input.label_input").focus();
        }
        $sender.find("span.attentext").text("取消收藏");
        $sender.attr("onclick", "UnAttenConv_OnClick_Pc(this)");
        $sender.children("span").show();
        $sender.children("img.attening").hide();
    });
}
function UnAttenConv_OnClick(sender)
{
    var $sender = $(sender);

    $sender.parent().parent().children("div.AttenLabelBox").remove();

    $sender.children("span").hide();
    $sender.children("img.attening").show();
    var Aurl = $sender.attr("unattenurl");
    $.getJSON(Aurl, {conv_root_id: $sender.attr("attenid"), t: new Date().getTime()}, function(data)
    {
        //成功
        if (data.success == "1")
        {
            var n = new Number($sender.find("span.attennum").text());
            $sender.find("span.attennum").text(n - 1);
            $(".convbox[conv_id='"+$sender.attr("attenid")+"']").children(".convdetail").find("div.label_outbox").remove();
        }
        $sender.find("span.attentext").text("收藏");
        $sender.attr("onclick", "AttenConv_OnClick(this)");
        $sender.children("span").show();
        $sender.children("img.attening").hide();
    });
}
function UnAttenConv_OnClick_Pc(sender)
{
    var $sender = $(sender);

    $sender.parent().parent().children("div.AttenLabelBox").remove();

    $sender.children("span").hide();
    $sender.children("img.attening").show();
    var Aurl = $sender.attr("unattenurl");
    $.getJSON(Aurl, {conv_root_id: $sender.attr("attenid"), t: new Date().getTime()}, function(data)
    {
        //成功
        if (data.success == "1")
        {
            var n = new Number($sender.find("span.attennum").text());
            $sender.find("span.attennum").text(n - 1);
        }
        $sender.find("span.attentext").text("收藏");
        $sender.attr("onclick", "AttenConv_OnClick_Pc(this)");
        $sender.children("span").show();
        $sender.children("img.attening").hide();
    });
}
function AttenConvLabelSave_OnClick(sender)
{
    var $sender = $(sender);
    var $label_allname = $sender.parent().siblings(".label_allname");
    var cur_text=$sender.siblings("input.label_input").val();
    if(cur_text!="")
    	$label_allname.append("<span class='label_name'>"+cur_text+"</span>");
    var label_names = $label_allname.children("span.label_name").map(function() {
        return $(this).text();
    }).toArray();
    var atten_id = $sender.attr("atten_id");

    $.getJSON(g_saveLabelUrl, {atten_id: atten_id, label_names: label_names, t: new Date().getTime()}, function(data)
    {
        if (data.success == "1")
        {
        	if(label_names[label_names.length-1]!=""){
	        	var dom=GetAttenConvLabelDom(atten_id,label_names[label_names.length-1]);
	        	$(".convbox[conv_id='"+atten_id+"']").children(".convdetail").append(dom);
	        	dom.fadeIn(200);
	        }
        }
        $sender.attr('disabled',false).val("保存");
        $sender.parents("div.AttenLabelBox").remove();
    });
    $sender.siblings("input.label_input").val("");
    $sender.attr('disabled','disabled').val("保存中");
}
function GetAttenConvLabelDom(atten_id,label)
{
	var atten_date=arguments[2]?arguments[2]:"1分钟前";
	var html=[];
	html.push("<div style='display:none;' class='label_outbox'>");
  html.push("<span class='label_title'>标签：</span>");
  html.push("<span class='label_box'>");
  html.push("<span class='label_allname'><span class='label_name'>"+label+"</span></span>");
  html.push("<span class='label_delname'></span>");
  html.push("<span class='label_edit'>");
  html.push("<span onclick='CollectLabel.label_add_OnClick(this)' class='label_add'> + 加标签</span>");
  html.push("<input type='text' onfocus='CollectLabel.label_input_OnFocus(this)' class='label_input' style='border: 1px solid;'>");
  html.push("<input type='button' atten_id='"+atten_id+"' onclick='CollectLabel.label_save_OnClick(this)' value='保存' class='label_save'>");
  html.push("<input type='button' onclick='CollectLabel.label_cancel_OnClick(this)' value='取消' class='label_cancel'>");
  html.push("</span></span><span class='label_date'>收藏："+atten_date+"</span></div>");
  return $(html.join(''));
}
function AttenConvLabelCancel_OnClick(sender)
{
    var $sender = $(sender);

    $sender.parents("div.AttenLabelBox").remove();
}
//------------参加活动----------------------
function JoinTogether_OnClick(sender)
{
    var $sender = $(sender);
    $sender.text("我要退出");
    $sender.attr("onclick", "UnJoinTogether_OnClick(this)");
    var Aurl = $sender.attr("joinurl");
    $.getJSON(Aurl, {together_id: $sender.attr("together_id"), t: new Date().getTime()}, function(data)
    {
        //成功
        if (data.success == "1")
        {
            var $together_staffsbox = $sender.parents("div.convbox").find(".convdetail").children().children("div.together_staffs_outbox");
            $together_staffsbox.fadeOut("slow", function()
            {
                $together_staffsbox.find(".together_staffsboxpart").after('<span class="together_staff" staff="' + data.join_staff + '"><a class="employee_name" login_account="' + data.join_staff + '" href="#">' + data.nick_name + '</a></span>');
                $together_staffsbox.fadeIn("slow");
            })
        }
    });
}
function UnJoinTogether_OnClick(sender)
{
    var $sender = $(sender);
    $sender.text("我要参加");
    $sender.attr("onclick", "JoinTogether_OnClick(this)");
    var Aurl = $sender.attr("unjoinurl");
    $.getJSON(Aurl, {together_id: $sender.attr("together_id"), t: new Date().getTime()}, function(data)
    {
        //成功
        if (data.success == "1")
        {
            var $together_staffsbox = $sender.parents("div.convbox").find(".convdetail").children().children("div.together_staffs_outbox");
            $together_staffsbox.fadeOut("slow", function()
            {
                $together_staffsbox.find("span[staff='" + data.join_staff + "']").remove();
                if ($together_staffsbox.find("div.together_staffsbox").children().length > 1)
                    $together_staffsbox.fadeIn("slow");
            })
        }
    });
}
window.picviewer=[];
//------------图片点击放大缩小----------------------
function conv_img_zoomout_OnClick(sender)
{
		$images=$(sender).parent().parent().find('img');
		var pars=$(sender).parentsUntil("div.convbox[conv_id]");
		var convid=$(pars[pars.length-1]).parent().attr('conv_id');
		var imgurls=[];
		for(var i=0;i<$images.length;i++){
			imgurls.push({'convid':convid,'url':$($images[i]).attr('src')});
		}
		$(sender).parent().parent().children().hide();
		var SNSPCTUREVIEWER=new PictureSNSViewer();
		SNSPCTUREVIEWER.boxshow({'ImgList':imgurls,'res':$(sender).parent().parent()[0],'sender':sender,'load_conv_url':g_load_conv_url,'onClose':function(){
			$(sender).parent().parent().children().show();
		}});
		picviewer.push(SNSPCTUREVIEWER);
		/*
    var conv_img_template1 = '<img class="conv_img_loading" src="' + g_resource_context + 'bundles/fafatimewebase/images/loadingsmall.gif" style="position: absolute; left: 0px; margin: 30px 42px 0px;"/>';
    var conv_img_template2 = '<div><div style="max-width:100%;width: 400px; background-color: #eee;"><a href="javascript:void(0)" style="padding:0 4px;" onclick="conv_img_zoomin_a_OnClick(this)">收起</a><a href="' + g_viewimageurl + '/[IMGID]" target="_blank" style="padding:0 4px;">查看原图</a></div><img class="imgzoomsmall imgzoonbig" src="[SRC]" alt=="[ALT]" onclick="conv_img_zoomin_OnClick(this)"/></div>';
    var $sender = $(sender);

    if ($sender.siblings().length == 0)
    {
        $sender.parent().append(conv_img_template1);

        var newimgurl = $sender.attr("src");
        newimgurl = newimgurl.replace(/\/small\//, "/middle/");
        var imgid = newimgurl.replace(/^.*\//, "");

        var img = new Image(); //创建一个Image对象，实现图片的预下载
        img.src = newimgurl;

        if (img.complete) { // 如果图片已经存在于浏览器缓存，直接调用回调函数
            $sender.siblings("img.conv_img_loading").hide();
            $sender.hide();
            $sender.parent().append(conv_img_template2.replace("[IMGID]", imgid).replace("[SRC]", newimgurl).replace("[ALT]", $sender.attr("alt")));
            return; // 直接返回，不用再处理onload事件
        }
        img.onload = function() { //图片下载完毕时异步调用callback函数。
            $sender.siblings("img.conv_img_loading").hide();
            $sender.hide();
            $sender.parent().append(conv_img_template2.replace("[IMGID]", imgid).replace("[SRC]", newimgurl).replace("[ALT]", $sender.attr("alt")));
        };
    }
    if ($sender.siblings().length == 1)
    {
        return;
    }
    else
    {
        $sender.hide();
        $sender.siblings("div").show();
    }
    */
}

function conv_img_zoomin_OnClick(sender)
{
    var $sender = $(sender);

    $sender.parent().hide();
    $sender.parent().siblings("img.imgzoomsmall").show();

    var t = $sender.parents(".convbox").offset().top - 50;
    if ($(document).scrollTop() > t)
        $(document).scrollTop(t);
}

function conv_img_zoomin_a_OnClick(sender)
{
    conv_img_zoomin_OnClick($(sender).parent().siblings("img.imgzoonbig")[0]);
}

//------------加标签----------------------
var CollectLabel = {
    $element: null,
    $menu: null,
    shown: false,
    label_add_OnClick: function(sender)
    {
        var $sender = $(sender);

        $sender.parents(".label_box").addClass("label_box_edit");
        $sender.siblings(".label_input").focus();
    },
    label_cancel_OnClick: function(sender)
    {
        var $sender = $(sender);

        var $label_allname = this.$element.parent().siblings(".label_allname");
        var $label_delname = $label_allname.siblings(".label_delname");

        $label_allname.children("span.new_label_name").remove();
        $label_delname.children().detach().appendTo($label_allname);

        $sender.siblings(".label_input").val("");
        $sender.parents(".label_box").removeClass("label_box_edit");
    },
    label_save_OnClick: function(sender)
    {
        this.addlabel();

        var $sender = $(sender);
        var $label_allname = $sender.parent().siblings(".label_allname");
        var label_names = $label_allname.children("span.label_name").map(function() {
            return $(this).text();
        }).toArray();

        var that = this;
        $.getJSON(g_saveLabelUrl, {atten_id: $sender.attr("atten_id"), label_names: label_names, t: new Date().getTime()}, function(data)
        {
            if (data.success == "1")
            {
                $label_allname.children("span.new_label_name").removeClass("new_label_name");
                $label_allname.siblings(".label_delname").empty();

                for (var i = 0; i < label_names.length; i++)
                {
                    if (that.$menu.children("li").children("a:contains('" + label_names[i] + "')").length == 0 && label_names[i]!="")
                        that.$menu.append("<li><a>" + label_names[i] + "</a></li>");
                }
            }
        });

        $sender.siblings(".label_input").val("");
        $sender.parents(".label_box").removeClass("label_box_edit");
    },
    label_input_OnFocus: function(sender)
    {
        var $sender = $(sender);

        if ($sender.data("hasInited"))
        {
            this.$element = $sender;
            //this.show();
            var that = this;
            setTimeout(function() {
                that.show()
            }, 150);
            return;
        }

        $sender.data("hasInited", 1);

        if (this.$menu) {
        }
        else
        {
            this.$menu = $('<ul class="collectlabel-menu"><li class="title">不超过3个标签</li></ul>').appendTo('body');
            var that = this;
            $.getJSON(g_getLabelUrl, {t: new Date().getTime()}, function(data)
            {
                for (var i = 0; i < data.length; i++)
                {
                    that.$menu.append("<li><a>" + data[i].label_name + "</a></li>");
                }
            });
        }

        this.$element = $sender;
        this.$element
                .on('blur', $.proxy(this.blur, this))
                .on('keypress', $.proxy(this.keypress, this))
                .on('keyup', $.proxy(this.keyup, this));

        if ($.browser.webkit || $.browser.msie) {
            $sender.on('keydown', $.proxy(this.keydown, this));
        }

        this.$menu
                .on('click', $.proxy(this.click, this))
                .on('mouseenter', 'li', $.proxy(this.mouseenter, this));
    },
    show: function() {
        var pos = $.extend({}, this.$element.offset(), {
            height: this.$element[0].offsetHeight
        });

        this.$menu.css({
            top: pos.top + pos.height
                    , left: pos.left
        });

        this.$menu.show();
        this.shown = true;
        return this;
    },
    hide: function() {
        this.$menu.hide();
        this.shown = false;
        return this;
    },
    click: function(e) {
        e.stopPropagation();
        e.preventDefault();
        this.select();
    },
    mouseenter: function(e) {
        this.$menu.find('.active').removeClass('active');
        $(e.currentTarget).filter(":not(.title)").addClass('active');
    },
    select: function() {
        var $active = this.$menu.find('.active');

        if ($active.length == 0)
            return;

        var val = $active.text();
        this.$element.val(val).change();
        this.addlabel();
        return this.hide();
    },
    addlabel: function()
    {
        var new_lablename = this.$element.val().replace(/\s\s*$/, '').replace(/\'|\"/g, "").substr(0, 6);
        var $label_allname = this.$element.parent().siblings(".label_allname");
        var $label_delname = $label_allname.siblings(".label_delname");

        this.$element.val("");
        if (new_lablename == "")
            return;
        if ($label_allname.children().length >= 3)
            return;
        if ($label_allname.children("span.label_name:contains('" + new_lablename + "')").length > 0)
            return;

        var $delname = $label_delname.children("span.label_name:contains('" + new_lablename + "')");
        if ($delname.length > 0)
        {
            $delname.detach().appendTo($label_allname);
        }
        else
        {
            $label_allname.append('<span class="label_name new_label_name">' + new_lablename + '</span>');
        }
        if (this.shown)
            this.show();
    },
    blur: function(e) {
        var that = this;
        setTimeout(function() {
            that.hide()
        }, 150);
    },
    move: function(e) {
        if (!this.shown)
            return;

        switch (e.keyCode) {
            case 9: // tab
            case 13: // enter
            case 27: // escape
                e.preventDefault();
                break;

            case 38: // up arrow;
                e.preventDefault();
                this.prev();
                break;

            case 40: // down arrow
                e.preventDefault();
                this.next();
                break;
        }

        e.stopPropagation();
    },
    backspace: function()
    {
        var v = this.$element.val();

        if (v != "")
            return;

        var $label_allname = this.$element.parent().siblings(".label_allname");
        var $label_delname = $label_allname.siblings(".label_delname");

        var $last = $label_allname.children().last();
        if ($last.hasClass("new_label_name") || $label_delname.children("span.label_name:contains('" + $last.text() + "')") > 0)
        {
            $last.remove();
        }
        else
        {
            $last.detach().appendTo($label_delname);
        }

        if (this.shown)
            this.show();
    },
    keydown: function(e) {
        this.iskeypress = true;
        this.LastKeyDownCode = e.keyCode;
        this.suppressKeyPressRepeat = !~$.inArray(e.keyCode, [40, 38, 9, 13, 27]);
        this.move(e);
        if (e.keyCode == 8)
            this.backspace(); //backspace
    },
    keypress: function(e) {
        this.iskeypress = true;
        if (this.suppressKeyPressRepeat)
            return;
        this.move(e);
        if (e.keyCode == 8)
            this.backspace(); //backspace
    },
    keyup: function(e) {
        if (!this.iskeypress)
            return;
        this.iskeypress = false;
        if (this.LastKeyDownCode == 229)
            return;
        switch (e.keyCode) {
            case 40: // down arrow
            case 38: // up arrow
                break;

            case 9: // tab
            case 13: // enter
                if (!this.shown || this.$element.val().length > 0)
                {
                    this.addlabel();
                    return;
                }
                this.select();
                break;

            case 27: // escape
                this.$element.blur();
                this.label_cancel_OnClick(this.$element.siblings("input.label_cancel"));
                if (!this.shown)
                    return;
                this.hide();
                break;

            case 32: // space
                this.addlabel();
                break;
        }

        e.stopPropagation();
        e.preventDefault();
    },
    next: function(event) {
        var active = this.$menu.find('.active').removeClass('active')
                , next = active.next();

        if (!next.length) {
            next = $(this.$menu.find('li:not(.title)')[0]);
        }

        next.addClass('active');
    },
    prev: function(event) {
        var active = this.$menu.find('.active').removeClass('active')
                , prev = active.prev();

        if (prev.hasClass("title"))
            prev = prev.prev();
        if (!prev.length) {
            prev = this.$menu.find('li:not(.title)').last();
        }

        prev.addClass('active');
    }
};

//评论翻页
var ReplyList = {
    getPage: function(pageindex)
    {
        var id = new Date().getTime();
        var $relaydetail_list = $(".relaydetail_list").first();
        var $reply_pager = $relaydetail_list.find(".reply_pager");
        var url = $reply_pager.attr("url");
        var conv_root_id = $reply_pager.attr("conv_root_id");

        $relaydetail_list.empty().append("<br>").attr("id", id);
        LoadComponent(id, url, {conv_root_id: conv_root_id, pageindex: pageindex, t: new Date().getTime()});
    }
};

//转发翻页
var CopyList = {
    getPage: function(pageindex)
    {
        var $convcopylist = $("#convcopylist");
        var $pager = $convcopylist.find(".copylist_pager");
        var url = $pager.attr("url");
        var conv_root_id = $pager.attr("conv_root_id");

        $convcopylist.empty().append("<br>");
        LoadComponent("convcopylist", url, {conv_root_id: conv_root_id, pageindex: pageindex, t: new Date().getTime()});
    }
};
//刷新置顶列表
function getTopConv($curr_tab) {
    var $curr_content = $("#con_" + $curr_tab.attr('id'));
    var $curr_ul = $curr_content.find("ul.conv_box[type='topbox']");
    if (typeof($curr_tab.attr('gettopurl')) == 'undefined' || $curr_tab.attr('gettopurl') == '')
        return;
    $.post($curr_tab.attr('gettopurl'), {},
            function(data) {
                if ($curr_ul.length > 0)
                    $curr_ul.remove();
                $ul = $(data).css('display', 'none');
                if ($ul.find("li").length == 0)
                    $ul.css('height', '0px');//IE7
                $curr_content.find("div.topicbox").prepend($ul);
                $ul.fadeIn("slow");
                overLengthAct();
                setListAct();
                registermoreoper();
                $curr_tab.attr('isloadtop', '1');
            });
}
//置顶动态控制
function topControl(conv_id, act) {
    var $hover_tab = $("#two1.hover[gettopurl],#two2.hover[gettopurl],#two3.hover[gettopurl],#two4.hover[gettopurl],#two5.hover[gettopurl],#two6.hover[gettopurl]");
    if ($hover_tab.length > 0)
        getTopConv($hover_tab);
}
//获取当前tab的总加载次数
function totalLoad($e) {
    var pageindex = $e.attr('pageindex');
    if (!pageindex)
        pageindex = 1;
    var scrollloadnum = $e.attr('scrollloadnum');
    if (!scrollloadnum)
        scrollloadnum = 1;
    return (pageindex * 1 - 1) * 3 + (scrollloadnum * 1);
}
function act_register($e, ev, callback) {
    $e.live(ev, function() {
        $this = $(this);
        var act_url = $this.attr("act_url");
        var conv_id = $this.parent().parent().attr("conv_id");
        var da = {'conv_id': conv_id, 'network_domain': g_curr_network_domain};
        $.post(act_url, da, function(d) {
            callback(conv_id, d);
        });
    });
}
//用于统一获取更多设置的下拉列表dom对象
function getMoreSetUrl(conv_id)
{
    return $("ul.set_list[conv_id='" + conv_id + "']");
}
//更多设置下列表项的隐藏与显示
function setHS(conv_id, t)
{
    var $this = getMoreSetUrl(conv_id).find("span[act='" + t + "']");
    $this.parent().siblings("li").find("span[act*='" + t.replace("cancel_", "") + "']").parent().show();
    $this.parent().hide();
}
//official_publish_type_action
function setListAct() {
    var $moresets = $("span.official_publish_type_action");
    for (var i = 0; i < $moresets.length; i++) {
        var $moreset = $($moresets[i]);
        if ($moreset.attr('register') == '1')
            continue;//说明已注册以下事件，无需重复注册
        //mouseover事件
        if (ismanager != '1') {
            $moreset.siblings("ul.set_list").find("span[act*='top']").parent().remove();
            $moreset.siblings("ul.set_list").find("span[act*='hide']").css("margin-left", "-20px");
        }
        $moreset.bind('mouseover', function(event) {
            if (checkHover(event, this)) {
                if (typeof(set_ul_timer) != 'undefined') {
                    clearTimeout(set_ul_timer);
                }
                var $this = $(this);
                var $set_ul = $(this).siblings("ul.set_list");
                var le = $this.css("left").replace('px', '');
                var to = $this.css("top").replace('px', '');
                $set_ul.css({
                    left: (le * 1 + $this.parent().width() - $set_ul.width()).toString() + "px",
                    top: (to * 1 + $this.height()).toString() + "px"}
                );
                //下拉菜单的显示
                set_ul_timer = setTimeout(function() {
                    $set_ul.show();
                }, 500);
            }
        });
        $moreset.bind('mouseout', function(event) {
            if (checkHover(event, this)) {
                $this = $(this);
                if (typeof(set_ul_timer) != 'undefined') {
                    clearTimeout(set_ul_timer);
                }
                set_ul_timer = setTimeout(function() {
                    //下拉菜单的隐藏
                    $this.siblings("ul.set_list").hide();
                }, 500);
            }
        });
        var $uls = $moreset.siblings("ul.set_list");
        //下拉菜单的事件
        $uls.bind('mouseover', function(event) {
            if (checkHover(event, this)) {
                if (typeof(set_ul_timer) != 'undefined') {
                    clearTimeout(set_ul_timer);
                }
            }
        });
        $uls.bind('mouseout', function(event) {
            if (checkHover(event, this)) {
                $this = $(this);
                set_ul_timer = setTimeout(function() {
                    $this.hide();
                }, 500);
            }
        });
        //下拉菜单选项的显示
        $uls.attr("top") == $uls.attr("conv_id") ? $uls.find("span[act='top']").parent().hide() : $uls.find("span[act='cancel_top']").parent().hide();
        $uls.attr("hide") == $uls.attr("conv_id") ? $uls.find("span[act='hide']").parent().hide() : $uls.find("span[act='cancel_hide']").parent().hide();
        //下拉菜单选项单击事件
        $links = $uls.find("span.oneset");
        for (var j = 0; j < $links.length; j++) {
            $link = $($links[j]);
            var act = $link.attr('act');
            var callback = function(conv_id, d) {
            }, ev = 'click';
            if (act == 'top') {
                callback = function(conv_id, d) {
                    setHS(conv_id, 'top');
                    topControl(conv_id, '1');
                }
            }
            else if (act == "cancel_top") {
                callback = function(conv_id, d) {
                    setHS(conv_id, "cancel_top");
                    topControl(conv_id, '0');
                }
            }
            else if (act == 'hide') {
                callback = function(conv_id, d) {
                    if (d.s == '1') {
                        setHS(conv_id, 'hide');
                        var $two1_conv = $("#con_two1 div[conv_id='" + conv_id + "']");
                        if ($two1_conv.length > 0) {
                            $two1_conv.attr('hide', '').parent().remove();
                            if ($two1_conv.parent().parent().attr('type') == 'topbox')
                                topControl(conv_id, '1');
                            //loadOneNearConv($("#con_two1 ul:first"),'conv','after');
                        }
                    }
                }
            }
            else if (act == 'cancel_hide') {
                callback = function(conv_id, d) {
                    if (d.s == '1') {
                        setHS(conv_id, 'cancel_hide');
                        $("#two1").attr("isloaded", '0');
                    }
                }
            }
            act_register($link, ev, callback);
        }
        $moreset.attr('register', '1');
    }
}
function registermoreoper() {
    var $collection = $("span.moreoper");
    for (var i = 0; i < $collection.length; i++) {
        var $span = $($collection[i]);
        if ($span.register == '1')
            continue;
        //事件
        $span.bind('mouseover', function(event) {
            if (checkHover(event, this)) {
                if (typeof(moreoper_timer) != 'undefined')
                    clearTimeout(moreoper_timer);
                var $this = $(this);
                moreoper_timer = setTimeout(function() {
                    $this.siblings("ul.convoper").slideDown(200);
                }, 500);
            }
        });
        $span.bind('mouseout', function(event) {
            if (checkHover(event, this)) {
                if (typeof(moreoper_timer) != 'undefined')
                    clearTimeout(moreoper_timer);
                var $this = $(this);
                moreoper_timer = setTimeout(function() {
                    $this.siblings("ul.convoper").slideUp(200);
                }, 500);
            }
        });
        var $ul = $span.siblings("ul.convoper");
        $ul.bind('mouseover', function(event) {
            if (checkHover(event, this)) {
                if (typeof(moreoper_timer) != 'undefined')
                    clearTimeout(moreoper_timer);
            }
        });
        $ul.bind('mouseout', function(event) {
            if (checkHover(event, this)) {
                $this = $(this);
                moreoper_timer = setTimeout(function() {
                    $this.slideUp(200);
                }, 500);
            }
        });
        $span.attr('register', '1');
    }
}