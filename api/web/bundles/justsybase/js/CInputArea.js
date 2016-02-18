/** 
 * 下面是一些基础函数，解决mouseover与mouserout事件不停切换的问题（问题不是由冒泡产生的） 
 */
function checkHover(e, target) {
    if (getEvent(e).type == "mouseover") {
        return !contains(target, getEvent(e).relatedTarget
                || getEvent(e).fromElement)
                && !((getEvent(e).relatedTarget || getEvent(e).fromElement) === target);
    } else {
        return !contains(target, getEvent(e).relatedTarget
                || getEvent(e).toElement)
                && !((getEvent(e).relatedTarget || getEvent(e).toElement) === target);
    }
}

function contains(parentNode, childNode) {
    if (parentNode.contains) {
        return parentNode != childNode && parentNode.contains(childNode);
    } else {
        return !!(parentNode.compareDocumentPosition(childNode) & 16);
    }
}
//取得当前window对象的事件  
function getEvent(e) {
    return e || window.event;
}
//-------------Tab选择----------
function setTab(name, cursel, n)
{
    for (i = 1; i <= n; i++)
    {
        var menu = document.getElementById(name + i);
        var con = document.getElementById("con_" + name + "_" + i);
        menu.className = i == cursel ? "hover" : "";
        con.style.display = i == cursel ? "block" : "none";
    }
}
function setInputTab(sender)
{
    var $sender = $(sender);
    $sender.siblings().removeClass("hover");
    $sender.siblings().find("span").removeClass("input_tab_hover");
    $sender.addClass("hover");
    $sender.find("span").attr("class", "input_tab_hover");
    $(".lib_Contentbox .input_con_area").hide();
    $(".lib_Contentbox #con_" + $sender.attr("id")).show();
    var $sendertext =$.trim( $sender.text());
    $sendertext == "动态" ? $("#li_facemenu").show() : $("#li_facemenu").hide();
    $sendertext == "动态" ? $("#li_at").show() : $("#li_at").hide();
    $sendertext == "官方" ? $(".set_top").show() : $(".set_top").hide();
    $sendertext == "官方" ? $("ul.post_list").find("a[group_value='PRIVATE']").parent().hide() : $("ul.post_list").find("a[group_value='PRIVATE']").parent().show();
    $sendertext == "官方" ? $(".publish[type='group'] input#txtNotify").parent().hide():$(".publish[type='group'] input#txtNotify").parent().show();
    window.publish_event = $sender.attr("publish_event");
    window.publish_event_param = $sender.attr("publish_event_param");
}
function setInputTabForPC(sender)
{
    var $sender = $(sender).parent();
    $(".lib_Contentbox .input_con_area").hide();
    $(".lib_Contentbox #con_" + $sender.attr("id")).show();
    var $sendertext =$.trim( $sender.text());
    $sendertext == "动态" ? $("#li_facemenu").show() : $("#li_facemenu").hide();
    $sendertext == "动态" ? $("#li_at").show() : $("#li_at").hide();
    $sendertext == "官方" ? $(".set_top").show() : $(".set_top").hide();
    $sendertext == "官方" ? $("ul.post_list").find("a[group_value='PRIVATE']").parent().hide() : $("ul.post_list").find("a[group_value='PRIVATE']").parent().show();
    $sendertext == "官方" ? $(".publish[type='group'] input#txtNotify").parent().hide():$(".publish[type='group'] input#txtNotify").parent().show();
    window.publish_event = $sender.attr("publish_event");
    window.publish_event_param = $sender.attr("publish_event_param");
}

//-------------发布按钮状态----------
function setPublishEnable()
{
	if(typeof(SNSComFileUpload)!='undefined' && SNSComFileUpload!=null && SNSComFileUpload.loading.length >0)return;
  $hover=$("#one1.hover,#one2.hover,#one3.hover,#one4.hover,#one5.hover");
  var txt=$hover.find("span").text();
  if(txt=="动态" || txt=="提问" || txt=="官方" || txt=="活动"){
 	 	$("#btnPublish").removeAttr("disabled");
 	}
 	else if(txt=="投票"){
 		var inputs=$("div#divvote_options").find("input[type='text']");
 		var optiontxt="";
 		for(var i=0;i<inputs.length;i++){
 			optiontxt+=$(inputs[i]).val();
 		}
 		if(optiontxt=="")return;
 		if($("input#txtvote_title").val()=="")return;
 		$("#btnPublish").removeAttr("disabled");
 	}
}
function _setPublishDisable()
{
    $("#btnPublish").attr("disabled", "disabled");
}
function setPublishDisable(sender)
{
    if ($(sender).val() == "" || $(sender).val() == $(sender).attr("placeholder"))
        _setPublishDisable();
}
function setPublishing(isstart)
{
    $("#btnPublish").val(isstart ? "发布..." : "发  布");
}

//-------------通知对象选择事件----------
function txtNotify_source(query, process)
{
    if (txtNotify_source_all.length > 0)
        return txtNotify_source_all;

    $.getJSON(txtNotify_source_url, {network_domain: g_curr_network_domain, query: this.query, t: new Date().getTime()}, function(data)
    {
        txtNotify_source_100 = data;
        for (var i = 0; i < txtNotify_source_100.length; i++)
        {
            txtNotify_source_100[i].index = i;
            txtNotify_source_100[i].toString = function() {
                return this.index;
            };
        }
        process(txtNotify_source_100);
    })

    return null;
}
function txtNotify_highlighter(item)
{
    return "<span>" + item.nick_name + "(" + item.login_account + ")</span>";
}
function txtNotify_matcher(item)
{
    if (this.query)
        return ~item.login_account.toLowerCase().indexOf(this.query.toLowerCase()) || ~item.nick_name.toLowerCase().indexOf(this.query.toLowerCase());
    else
        return true;
}
function txtNotify_sorter(items)
{
    return items;
}
function txtNotify_updater(item)
{
    var $InputNotifyArea = $("#InputNotifyArea");
    var source = txtNotify_source_all.length > 0 ? txtNotify_source_all : txtNotify_source_100;

    if ($("input[value='" + source[item].login_account + "']", $InputNotifyArea).length == 0)
        $InputNotifyArea.append(GetNotifyTemplate(source[item].login_account, source[item].nick_name));
    return "";
}
function GetNotifyTemplate(Alogin_account, Anick_name)
{
    var Template1 = '<span class="NotifyObj"><input  type="hidden" value="';
    var Template2 = '"><span class="NotifyPerson">';
    var Template3 = '</span><span class="NotifyClose" onclick="NotifyClose_OnClick(this)">×</span></span>';

    return Template1 + Alogin_account + Template2 + Anick_name + Template3;
}
function NotifyClose_OnClick(sender)
{
    $sender = $(sender);
    $sender.parent().remove();
}
function txtNotify_OnKeyUp(e)
{
    if (e.keyCode == 8) // backspace
    {
        if ($(this).val() == "")
            $("#InputNotifyArea .NotifyObj:last").remove();
    }
    else if (e.keyCode == 13)
    {
        var $this = $(this);
        v = $this.val();
        if (v == "")
            return;
        if (v.indexOf("@") <= 0)
        {
            $this.val("");
            return;
        }
        var $InputNotifyArea = $("#InputNotifyArea");
        if ($("input[value='" + v + "']", $InputNotifyArea).length == 0)
            $InputNotifyArea.append(GetNotifyTemplate(v, v));
        $this.val("");
    }
}
function GetNotifyStaff()
{
    var re = $.unique($("#InputNotifyArea input")
            .map(function()
    {
        return $(this).val();
    }))
            .toArray();
    return re;
}
function ClearNotifyStaff()
{
    $("#InputNotifyArea").empty();
}

//-------------发布给XX群组的输入选择事件----------
function txtpost_to_group_highlighter(item)
{
    return "<span><img src='" + item.group_photo_url + "' style='width:48px; height:48px; margin-right:10px'/>" + item.group_name + "</span>";
    ;
}
function txtpost_to_group_matcher(item)
{
    if (this.query)
        return ~item.group_name.toLowerCase().indexOf(this.query.toLowerCase());
    else
        return true;
}
function txtpost_to_group_sorter(items)
{
    return items;
}
function txtpost_to_group_updater(item)
{
    $("#apost_to_group").text("发布给：" + this.source[item].group_name).show();
    $("#hpost_to_group").val(this.source[item].group_id);
    $("#txtpost_to_group").hide();
    $("#bcancel_post_to_group").show();
    $(".lib_notify").show();
    this.query = "";
    return "";
}

//--------------发布给XX群组的点击事件--------------  
function apost_to_group_OnClick()
{
    $("#apost_to_group").hide();
    $("#bcancel_post_to_group").hide();
    var txtpost_to_group = $("#txtpost_to_group").show().focus();
    var thpost_to_group = txtpost_to_group.data('typeahead');
    thpost_to_group.process(thpost_to_group.source).show();
}
function bcancel_post_to_group_OnClick()
{
    $("#apost_to_group").text("发布给：全部同事").show();
    $("#hpost_to_group").val("ALL");
    $("#bcancel_post_to_group").hide();
    $("#InputNotifyArea .NotifyObj").remove();
    $(".lib_notify").hide();
}

function mi_post_to_group_OnClick(sender)
{
    var $sender = $(sender);
    var group_value = $sender.attr("group_value");
    $("#hpost_to_group").val(group_value);
    $("#spost_to_group_name").text($sender.text());

    if (group_value == "ALL")
    {
        $("#InputNotifyArea .NotifyObj").remove();
        $(".lib_notify").hide();
    }
    else
    {
        $(".lib_notify").show();
    }
}
function publishNote(e)
{
	if($("#Trend").val()==''){
		var notice="";
		var $hover=$("#one1.hover,#one2.hover,#one3.hover,#one4.hover,#one5.hover");
		var tex =$.trim( $hover.find("span").text());
		if(tex=='动态'){
			notice="请先输入动态信息";	
		}
		else if(tex=='提问'){
			notice="请先输入想提的问题";
		}
		else if(tex=='活动'){
			notice="请先输入活动内容";
		}
		else if(tex=='投票'){
			notice="请先填写投票内容";
		}
		else if(tex=='官方'){
			notice="请先输入官方信息";
		}
		$(e).attr('title',notice);
		return;
	}
	else if(typeof(SNSComFileUpload)!='undefined' && SNSComFileUpload!=null && SNSComFileUpload.loading.length >0){
		$(e).attr('title',"有文件正在上传，若要立即发布请先取消正在上传的文件。");
	}
	else{
		$(e).attr('title',"发布");
	}
}
//--------------发布函数--------------
function publishInput()
{
	var a=$("#btnPublish").attr("disabled");
		if(typeof(a)!="undefined" && a.indexOf("disabled")> -1)return;
    //该属性在setInputTab，切换TAB时设置
    if (window[window.publish_event])
    {
    		$("#uploadfilecontainer").slideUp();
    		$("#uploadfilecontainer").children('div.file_upload_box').remove();
    		//$("span#upload_file_sel").append("<input type='file' multiple='' style='cursor: pointer; left: 24.5px; width: 70px; opacity: 0; position: absolute; top: 8px; z-index: 1000002; outline: medium none;' size='1' id='filedata' onchange=\"fileSelect('/fafatime.com/documnet/upload')\" hidefocus='hidefocus' name='filedata' tabindex='-1'>");
    		SNSComFileUpload=null;
        window[window.publish_event](window.publish_event_param);
    }
}

//--------------附件--------------
function openfiledigl_OnShown(param)
{
    $("#li_picmenu").removeClass('open');
    if ($(this).attr("isloaded") != "1")
    {
        LoadComponent("files", param.data.Aurl);
        $(this).attr("isloaded", "1");
    }
}

//将选择的文件编号返回并插入到当前输入框中
function insertFileID()
{
    var f = getCurrentSelectedFileID();
    if (f.fileId && f.fileId != "")
        addFile(f.fileName, f.fileId);
    $('#openfiledigl').modal('hide');
}

function fileSelect(Aurl)
{
		if(typeof(SNSComFileUpload)=='undefined' || SNSComFileUpload==null){
		  if(typeof(SNSComFileUpload)=='undefined'){
		  	window.SNSComFileUpload=new ComFileUpload();
		  	SNSComFileUpload.registerEvent();
		  }
		  else{
		  	window.SNSComFileUpload=new ComFileUpload();
		  }
		  SNSComFileUpload.init({
		  	container:$("#uploadfilecontainer")[0],
		  	currfile:$("input#filedata")[0],
		  	upload_url:Aurl
		  });
		  SNSComFileUpload.fileselect(null,function(){
				$("span#upload_file_sel").append("<input type='file' style='cursor: pointer; left: 24.5px; width: 70px; opacity: 0; position: absolute; top: 8px; z-index: 1000002; outline: medium none;' size='1' id='filedata' onchange=\"fileSelect('/fafatime.com/documnet/upload')\" hidefocus='hidefocus' name='filedata' tabindex='-1'>");
			});
		  $("#li_picmenu").removeClass('open');
		  $("#uploadfilecontainer").slideDown();
		}
		else{
			SNSComFileUpload.fileselect($("input#filedata")[0],function(){
				$("span#upload_file_sel").append("<input type='file' style='cursor: pointer; left: 24.5px; width: 70px; opacity: 0; position: absolute; top: 8px; z-index: 1000002; outline: medium none;' size='1' id='filedata' onchange=\"fileSelect('/fafatime.com/documnet/upload')\" hidefocus='hidefocus' name='filedata' tabindex='-1'>");
			});
			$("#li_picmenu").removeClass('open');
		  $("#uploadfilecontainer").slideDown();
		}
	  /*
    var fns = document.getElementById("filedata").value.split("\\");
    fn = fns[fns.length - 1];
//     $("#upload_file_sel").hide();
//     $("#upload_file_ing").show();
    $("#li_picmenu").removeClass('open');
    $(".uploadingfilebox").show().find(".uploadingfilename").text(fn);
    $("form#upload_file").ajaxSubmit({
        dataType: 'json', //返回的数据类型
        url: Aurl, //表单的action
        method: 'post',
        uploadProgress: function(e, p, t, per){
            $("#li_picmenu").find(".uploadingfileprogress").css("width", per+"%"); 
        },
        success: function(r) {
            if (r.succeed)
            {
                $('#upload_file_ing').text('文件上传成功！');
                addFile(fn, r.fileid);
                setTimeout("$('#upload_file_ing').hide();$('#upload_file_sel').show()", 2000);
            }
            else
            {
                $('#upload_file_ing').text('文件上传失败！');
                setTimeout("$('#upload_file_ing').hide()", 2000);
            }
            $(".uploadingfilebox").hide();
            $("#li_picmenu").find(".uploadingfileprogress").css("width", "0%"); 
        }
    });
    */
}
function addFile(fn, fileid)
{
    if ($("#filesList input[value='" + fileid + "']").length > 0)
        return;

    var Template1 = '<span class="NotifyObj"><input type="hidden" value="' + fileid + '"><span class="NotifyPerson">' + fn + '</span><span class="NotifyClose" onclick="AttachClose_OnClick(this)">×</span></span>';
    $("#filesList").append(Template1);
    $("#filesList").parent().show();
}
function AttachClose_OnClick(sender)
{
    $sender = $(sender);
    $sender.parent().remove();
    if ($("#filesList").children().length == 0)
        $("#filesList").parent().hide();
    if(typeof(SNSComFileUpload)!='undefined' && SNSComFileUpload!=null)
    	SNSComFileUpload.removeFile($sender.siblings("input[type='hidden']").val());
}
function GetInputAttach()
{
    var re = $.unique($("#filesList input")
            .map(function()
    {
        return $(this).val();
    }))
            .toArray();
    return re;
}
function ClearInputAttach()
{
    $("#filesList").empty().parent().hide();
}
//--------------发布动态--------------
function publishTrend(Aurl)
{
    var TrendValue = $("#Trend").val();

    if (!TrendValue || TrendValue == $("#Trend").attr("placeholder"))
        return;

    _setPublishDisable();
    setPublishing(true);
    $.post(Aurl,
            {
                network_domain: g_curr_network_domain,
                trend: TrendValue,
                notifystaff: GetNotifyStaff(),
                attachs: GetInputAttach(),
                post_to_group: $("#hpost_to_group").val(),
                t: new Date().getTime()
            },
    function(data)
    {
        if (data.success == "1")
        {
            $("#Trend").val("");
            ClearNotifyStaff();
            ClearInputAttach();
            setPublishing(false);
            if (window.OnPublished)
                window.OnPublished(data.conv_id, "trend");
        }
    },
            "json"
            );
}
//--------------发布官方动态--------------
function publishOfficialTrend(Aurl)
{
    var TrendValue = $("#officialTrend").val();

    if (!TrendValue || TrendValue == $("#officialTrend").attr("placeholder"))
        return;

    _setPublishDisable();
    setPublishing(true);
    $.post(Aurl,
            {
                network_domain: g_curr_network_domain,
                trend: TrendValue,
                notifystaff: GetNotifyStaff(),
                attachs: GetInputAttach(),
                post_to_group: $("#hpost_to_group").val(),
                infotype: $("#divofficial_ismulti input:checked[name='official_sel_type']").val(),
                top: $("#istop").attr('checked') == 'checked' ? '1' : '0',
                time: $("#serTime").attr('timeout'),
                t: new Date().getTime()
            },
    function(data)
    {
        if (data.success == "1")
        {
            $("#officialTrend").val("");
            ClearNotifyStaff();
            ClearInputAttach();
            setPublishing(false);
            if (window.OnPublished)
                window.OnPublished(data.conv_id, "official");
        }
    },
            "json"
            );
}
//--------------发布提问--------------
function publishAsk(Aurl)
{
    var AskQuestionValue = $("#AskQuestion").val();

    if (!AskQuestionValue || AskQuestionValue == $("#AskQuestion").attr("placeholder"))
        return;

    _setPublishDisable();
    setPublishing(true);
    $.post(Aurl,
            {
                network_domain: g_curr_network_domain,
                question: AskQuestionValue,
                notifystaff: GetNotifyStaff(),
                attachs: GetInputAttach(),
                post_to_group: $("#hpost_to_group").val(),
                t: new Date().getTime()
            },
    function(data)
    {
        if (data.success == "1")
        {
            $("#AskQuestion").val("");
            ClearNotifyStaff();
            ClearInputAttach();
            setPublishing(false);
            if (window.OnPublished)
                window.OnPublished(data.conv_id, "ask");
        }
    },
            "json"
            );
}

//--------------发布活动--------------
function seltogether_m_OnChange()
{
    var m = new Number($("#seltogether_m").val());
    var days = new Date((m < new Date().getMonth() ? new Date().getFullYear() + 1 : new Date().getFullYear()), m, "0").getDate();

    $seltogether_d = $("#seltogether_d");
    var oldday = $seltogether_d.val();
    if (!oldday)
        oldday = new Date().getDate();
    $seltogether_d.empty();
    for (var i = 1; i <= days; i++)
    {
        $seltogether_d.append("<option value='" + i + "'>" + i + "日</option>");
    }
    $seltogether_d.val(oldday);
}

function publishTogether(Aurl)
{
    var together_titleValue = $("#together_title").val();

    if (!together_titleValue || together_titleValue == $("#together_title").attr("placeholder"))
        return;

    var m = new Number($("#seltogether_m").val());
    _setPublishDisable();
    setPublishing(true);
    $.post(Aurl,
            {
                network_domain: g_curr_network_domain,
                title: together_titleValue,
                will_date: (m < new Date().getMonth() ? new Date().getFullYear() + 1 : new Date().getFullYear()) + "/" + (m) + "/" + $("#seltogether_d").val() + " " + $("#seltogether_hm").val(),
                will_dur: $("#seltogether_dur").val(),
                will_addr: $("#together_addr").val(),
                together_desc: $("#together_desc").val(),
                notifystaff: GetNotifyStaff(),
                attachs: GetInputAttach(),
                post_to_group: $("#hpost_to_group").val(),
                map_point: $("#together_addr_map_point").val(),
                t: new Date().getTime()
            },
    function(data)
    {
        if (data.success == "1")
        {
            $("#together_title").val("");
            $("#together_addr").val("");
            $("#together_desc").val("");
            ClearNotifyStaff();
            ClearInputAttach();
            setPublishing(false);
            if (window.OnPublished)
                window.OnPublished(data.conv_id, "together");
        }
    },
            "json"
            );
}

//显示地图
function together_map() {

    var scripts = document.getElementsByTagName("SCRIPT"), isRef = false;
    if (scripts != null) {
        for (var i = 0; i < scripts.length; i++) {
            if (scripts[i].src.split("?")[0].indexOf("Map.js") > -1) {
                isRef = true;
                break;
            }
        }
    }
    var point = $("#together_addr_map_point").val();
    if (point != null && point.length > 0)
    {
        point = point.split(",");
        point = {x: point[0] * 1, y: point[1] * 1};
    }
    if (!isRef) {
        var oHead = document.getElementsByTagName('HEAD').item(0);
        var oScript = document.createElement("script");
        oScript.type = "text/javascript";
        oScript.src = "/bundles/fafatimewebase/js/Map.js";
        oHead.appendChild(oScript);
        oScript.onload = oScript.onreadystatechange = function() {
            setTimeout(' fafaMap.Show("togetherMap_map","' + point + '",false)', 100);
        }
    }
    else
        fafaMap.Show("togetherMap_map", point, false);
}

function togetherMap_save()
{
    fafaMap.save(function(d) {
        var point = d.x + "," + d.y;
        $("#together_addr_map_point").val(point);
        var add = $("#together_addr").val();
        $("#together_addr").val(d.addr);
        $("#togetherMap").modal("hide");
    });
}


//--------------发布投票--------------
var ChineseNum = ["一", "二", "三", "四", "五", "六", "七", "八", "九", "十"];
function vote_option_OnFocus(sender)
{
    var TemplateVoteOptionInput1 = '<div class="ask_div4"><input type="text" onkeyup="setPublishEnable()" onblur="setPublishDisable(this)" class="text_input" placeholder="选项';
    var TemplateVoteOptionInput2 = '" onfocus="vote_option_OnFocus(this)"></div>'
    var $divvote_options = $(sender).parent().parent();
    var optnum = $divvote_options.children().length;

    if (optnum >= ChineseNum.length)
        return;

    $(sender).attr("onfocus", "");
    $divvote_options.append(TemplateVoteOptionInput1 + ChineseNum[optnum] + TemplateVoteOptionInput2);

    //因为IE不支持HTML5，所以
    $('input[placeholder]', $divvote_options).placeholder();
}

function selvote_m_OnChange()
{
    var m = new Number($("#selvote_m").val());
    var days = new Date((m < new Date().getMonth() ? new Date().getFullYear() + 1 : new Date().getFullYear()), m, "0").getDate();

    $selvote_d = $("#selvote_d");
    var oldday = $selvote_d.val();
    if (!oldday)
        oldday = new Date().getDate() + 3; //投票截止时间默认为3天后,如果已是月底，则默认当天。
    $selvote_d.empty();
    for (var i = 1; i <= days; i++)
    {
        $selvote_d.append("<option value='" + i + "'>" + i + "日</option>");
    }
    oldday = oldday > days ? days : oldday;
    $selvote_d.val(oldday);
}

function publishVote(Aurl)
{
    var vote_titleValue = $("#txtvote_title").val();

    if (!vote_titleValue || vote_titleValue == $("#txtvote_title").attr("placeholder"))
        return;

    //取出所有选项值
    $optionvalues = $("#divvote_options input")
            .filter(function(i)
    {
        var $this = $(this);
        var v = $this.val();
        return v != "" && v != $this.attr("placeholder");
    })
            .map(function()
    {
        return $(this).val();
    });
    var m = new Number($("#selvote_m").val());
    _setPublishDisable();
    setPublishing(true);
    $.post(Aurl,
            {
                network_domain: g_curr_network_domain,
                title: vote_titleValue,
                is_multi: $("#divvote_ismulti input[name='vote_sel_type']:checked").val(),
                optionvalues: $optionvalues.toArray(),
                notifystaff: GetNotifyStaff(),
                attachs: GetInputAttach(),
                finishdate: (m < new Date().getMonth() ? new Date().getFullYear() + 1 : new Date().getFullYear()) + "/" + (m) + "/" + $("#selvote_d").val() + " " + $("#selvote_hm").val(),
                post_to_group: $("#hpost_to_group").val()
            },
    function(data)
    {
        if (data.success == "1")
        {
            $("#txtvote_title").val("");
            $("#divvote_options input").val("");
            $("#divvote_options").children(":gt(2)").remove();
            $("#divvote_options").children(":last").children("input").attr("onfocus", "vote_option_OnFocus(this)");
            ClearNotifyStaff();
            ClearInputAttach();
            setPublishing(false);
            if (window.OnPublished)
                window.OnPublished(data.conv_id, "vote");
        }
    },
            "json"
            );
}

//发布公告
function publishNotice(Aurl)
{
    var notice_content = $("#Notice").val();
    if (!notice_content || notice_content == $("#Notice").attr("placeholder"))
        return;
    _setPublishDisable();
    $.post(Aurl,
            {
                notice: $("#Notice").val(),
                post_to_group: $("#hpost_to_group").val() == "" ? "all" : $("#hpost_to_group").val(),
                t: new Date().getTime()
            },
    function(data)
    {
        if (data.success == "1")
        {
            $("#Notice").val("");
            setPublishEnable();
            if (window.OnPublished)
                window.OnPublished(data.bulletin_id);
        }
    },
            "json"
            );
}
//关于置顶时间的下拉菜单
function setTopTime() {
    //注册事件
    $("div.set_top span.set_time").bind('mouseover', function(event) {
        if (checkHover(event, this)) {
            if (typeof(set_top_timer) != 'undefined') {
                clearTimeout(set_top_timer);
            }
            var $this = $(this), $ul = $this.siblings('ul.time_ul');
            var le = $this.offset().left;
            var to = $this.offset().top;

            $ul.css({left: (le + $this.width() - $ul.width() + 6).toString() + "px", top: (to + $this.height() + 2).toString() + "px"});
            set_top_timer = setTimeout(function() {
                $ul.slideDown(50);
            }, 500);
        }
    });
    $("div.set_top span.set_time").bind('mouseout', function(event) {
        if (checkHover(event, this)) {
            if (typeof(set_top_timer) != 'undefined') {
                clearTimeout(set_top_timer);
            }
            set_top_timer = setTimeout(function() {
                $("div.set_top ul.time_ul").slideUp(50);
            }, 500);
        }
    });
    var $ul = $("div.set_top ul.time_ul");
    $ul.bind('mouseover', function(event) {
        if (checkHover(event, this)) {
            if (typeof(set_top_timer) != 'undefined') {
                clearTimeout(set_top_timer);
            }
        }
    });
    $ul.bind('mouseout', function(event) {
        if (checkHover(event, this)) {
            $this = $(this);
            set_top_timer = setTimeout(function() {
                $this.slideUp(50);
            }, 500);
        }
    });
    $ul.find('li').bind('click', function() {
        var date = $(this).attr('timeout');
        var time = 0;
        if (date == '1d') {
            time = 1;
        }
        else if (date == '1w') {
            time = 7;
        }
        else if (date == '1m') {
            time = 30;
        }
        $("#serTime").attr('timeout', time);
        $("#serTime").text($(this).text());
        $(this).parent().hide();
    });

}
//at功能优化
function AtSomebody()
{
	var $this = $("#Trend");
	if($this.length==0)return;
	var t = $this[0];

  var cp = t.selectionStart;
  var ubbLength = t.value.length;
  t.value = t.value.slice(0, t.selectionStart) + "@" + t.value.slice(t.selectionStart, ubbLength);
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
	//$this.val($this.val()+"@");
  setTimeout(function(){$this.focus().keyup();},500);
}
