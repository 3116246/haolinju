/**
 * jQuery Maxlength plugin
 * @version		$Id: jquery.maxlength.js 18 2009-05-16 15:37:08Z emil@anon-design.se $
 * @package		jQuery maxlength 1.0.5
 * @copyright	Copyright (C) 2009 Emil Stjerneman / http://www.anon-design.se
 * @license		GNU/GPL, see LICENSE.txt
 */
(function(A) {
    A.fn.maxlength = function(B) {
        var C = jQuery.extend({events: [], maxCharacters: 200, status: true, statusClass: "status", statusText: "还能输入字符数:", notificationClass: "notification", showAlert: false, alertText: "You have typed too many characters.", slider: false}, B);
        A.merge(C.events, ["keyup"]);
        return this.each(function() {
            var G = A(this);
            var J = A(this).val().length;
            function D() {
                var K = C.maxCharacters - J;
                if (K < 0) {
                    K = 0
                }
                G.next("div").html(C.statusText + " " + K)
            }
            function E() {
                var K = true;
                if (J >= C.maxCharacters) {
                    K = false;
                    G.addClass(C.notificationClass);
                    G.val(G.val().substr(0, C.maxCharacters));
                    I()
                } else {
                    if (G.hasClass(C.notificationClass)) {
                        G.removeClass(C.notificationClass)
                    }
                }
                if (C.status) {
                    D()
                }
            }
            function I() {
                if (C.showAlert) {
                    alert(C.alertText)
                }
            }
            function F() {
                var K = false;
                if (G.is("textarea")) {
                    K = true
                } else {
                    if (G.filter("input[type=text]")) {
                        K = true
                    } else {
                        if (G.filter("input[type=password]")) {
                            K = true
                        }
                    }
                }
                return K
            }
            if (!F()) {
                return false
            }
            A.each(C.events, function(K, L) {
                G.bind(L, function(M) {
                    J = G.val().length;
                    E()
                })
            });
            if (C.status) {
                G.after(A("<div/>").addClass(C.statusClass).html("-"));
                D()
            }
            if (!C.status) {
                var H = G.next("div." + C.statusClass);
                if (H) {
                    H.remove()
                }
            }
            if (C.slider) {
                G.next().hide();
                G.focus(function() {
                    G.next().slideDown("fast")
                });
                G.blur(function() {
                    G.next().slideUp("fast")
                })
            }
        })
    }
})(jQuery);

var currentSelectedFileID = "";
var currentSelectedFileName = "";
var $docshowlist = null, fileListOffset = 0, minHeight = 0, issearch = false, circles = [], groups = [], moveingFileID = "", doc_hoverTimer = null, $deleteIcon = null, dirsCache = new HashMap(), filesCache = new HashMap(), shareDirs = new HashMap(), currentDir = "", dochoverTimer = null, docoutTimer;
$(document).ready(function() {
    $docshowlist = $("#docshowlist");
    if ($docshowlist.length == 0)
        return;
    fileListOffset = $docshowlist.offset();
    minHeight = $docshowlist.css("min-Height").replace("px", "") * 1;
});
function loadGroupDocList(parentid, txt)
{
    $("#middlepath").html("");
    setDirPath(parentid, txt);
    $("#hpost_to_group").val(parentid.substring(1));
    loadDocList(parentid);
    issearch = false;
}
function loadDocList(parentid)
{
    var tct = $("#con_mgr_two1");
    tct.html("<div class='urlloading'><div />");
    currentDir = parentid;//当前文件夹
    //判断是否已缓存
    if (dirsCache != null && dirsCache.get(currentDir) != null)
    {
        showDoc(filesCache.get(currentDir), dirsCache.get(currentDir));
        return;
    }
    var dirs = null, files = null;
    $.get($docshowlist.attr("dirurl") + "?parentid=" + parentid, "", function(result)
    {
        dirs = result.data;
        dirsCache.put(currentDir, dirs);
        if (files == null)
            return;
        showDoc(files, dirs);
    });
    $.get($docshowlist.attr("fileurl") + "?parentid=" + parentid, "", function(result) {
        files = result.data;
        if (result.info != null && result.info.length > 0) //缓存共享目录
        {
            shareDirs.put(currentDir, result.info);
        }
        filesCache.put(currentDir, files);
        if (dirs == null)
            return;
        showDoc(files, dirs);
    });
}
function showDoc(_fs, _ds)
{
    var $con_mgr_two1 = $("#con_mgr_two1");
    $con_mgr_two1.find("urlloading").remove();
    var sds = shareDirs.get(currentDir);
    sds = sds == null ? [] : sds;
    if (_fs.length == 0 && _ds.length == 0 && sds.length == 0)
    {
        $con_mgr_two1.html("<span style='color:#999999;text-align:center;line-height: 30px;height: 30px;display: block;width:100%'>该文件夹为空</span>");
        return;
    }
    var html = [];
    for (var i = 0; i < _ds.length; i++)
    {
        var ext = _ds[i].isshare == "1" ? "共享文件夹" : "文件夹";
        html.push("<div class='list_tr doc_row' style='height:25px' acc='" + _ds[i].owner + "' user='" + _ds[i].nick_name + "' jid='" + _ds[i].fafa_jid + "'><span class='doc_info_name' dir_id='" + _ds[i].id + "' style='border:0px;'><img src='/bundles/fafatimewebase/images/folder.png'>&nbsp;<a title='点击进入文件夹'>" + _ds[i].name + "</a></span>");
        html.push("<span class='doc_info_owner' style='cursor:default'>" + ext + "</span>");
        html.push("<span class='doc_info_cdate' style='cursor:default'>" + _ds[i].createdate + "</span></div>");
    }
    for (var i = 0; i < sds.length; i++)
    {
        var ext = "共享文件夹";
        html.push("<div class='list_tr doc_row' style='height:25px' acc='" + sds[i].login_account + "' user='" + sds[i].nick_name + "' jid='" + sds[i].fafa_jid + "'><span class='doc_info_name' dir_id='" + sds[i].id + "' style='border:0px;'><img src='/bundles/fafatimewebase/images/folder.png'>&nbsp;<a title='点击进入文件夹'>共享文件-" + sds[i].nick_name + "</a></span>");
        html.push("<span class='doc_info_owner' style='cursor:default'>" + ext + "</span>");
        html.push("<span class='doc_info_cdate' style='cursor:default'></span></div>");
    }
    for (var i = 0; i < _fs.length; i++)
    {
        var ext = "";
        if (_fs[i].file_ext == "")
            ext = "未知类型";
        else
        {
            if ("'gif','bmp','jpeg','jpg','png'".indexOf(_fs[i].file_ext) > -1)
                ext = _fs[i].file_ext.toUpperCase() + " 图像";
            else if ("'aif','snd','mid','avi','mpeg','mp4','mp3','wav','tiff','aiff','mov','mpg','ram','rm','ra','rmvb','asf','wmv','wma'".indexOf(_fs[i].file_ext) > -1)
                ext = _fs[i].file_ext.toUpperCase() + " 媒体文件";
            else
                ext = _fs[i].file_ext.toUpperCase() + " 文件";
        }
        var icon = "";
        if ("'gif','bmp','jpeg','jpg','png'".indexOf(_fs[i].file_ext) > -1)
            icon = "/getfile/image/small/" + _fs[i].file_id;
        else
            icon = "/bundles/fafatimewebase/images/" + _fs[i].ext_icon;
        html.push("<div class='list_tr doc_row' style='height:25px' acc='" + _fs[i].up_by_staff + "' user='" + _fs[i].nick_name + "' jid='" + _fs[i].fafa_jid + "'><span class='doc_info_name' file_id='" + _fs[i].file_id + "' style='border:0px;'><img onerror=\"this.src='/bundles/fafatimewebase/images/imgicon.png'\" src='" + icon + "'>&nbsp;<a href='javascript:openfile(\"" + _fs[i].file_id + "\")' title='点击查看文件'>" + _fs[i].file_name + "</a></span>");
        html.push("<span class='doc_info_owner' style='cursor:default'>" + ext + "</span>");
        html.push("<span class='doc_info_cdate' style='cursor:default'>" + _fs[i].up_date + "</span></div>");
    }
    $con_mgr_two1.html(html.join(""));
}
function loadAttributes(id)
{

}
function setDirPath(dirid, txt)
{
    clearTimeout(dochoverTimer);
    clearTimeout(docoutTimer);
    if (dirid.indexOf("|") > 0)
        $(".doc_tools_cfile").hide(); //虚拟的共享目录不能创建文档
    else
        $(".doc_tools_cfile").show();
    $('#doc_tools').hide();
    var dirlist = $(".dirlist");
    var middlepath = $("#middlepath");
    var _dir = dirlist.find("[dir_id='" + dirid + "']");
    if (_dir.length == 0)
        middlepath.append('<span class="dirsplit">/</span><span dir_id="' + dirid + '" class="dirpath">' + txt + '</span>');
    else
    {
        var countSpan = dirlist.find("[dir_id]");
        for (var i = countSpan.length - 1; i > 0; i--)
        {
            var _t = $(countSpan[i]);
            if (_t.attr("dir_id") == dirid)
                break;
            _t.prev().remove();
            _t.remove();
        }
    }
    var tLen = middlepath.width();
    //判断总路径是否超长
    var countSpan = middlepath.find("span");
    var hiddenSpan = countSpan.find(":hidden");
    var width = 0;
    for (var i = 0; i < countSpan.length; i++)
        width += (countSpan[i]).offsetWidth;
    if (width <= tLen) {
        hiddenSpan.css("display", "block");
        dirlist.find("[dir_id='']").text("文档库");
        return;
    }
    var i = 0;
    while (width > tLen) {
        (countSpan[i]).css("display", "none");
        i++;
        (countSpan[i]).css("display", "none");
        i++;
        width = 0;
        for (var j = i; j < countSpan.length; j++)
            width += (countSpan[j]).offsetWidth;
    }
    for (var j = i; j < countSpan.length; j++)
        (countSpan[j]).css("display", "block");
    dirlist.find("[dir_id='']").text("文档库/..");
}
function show_tools_item(x, y, isDir, id)
{
    //判断有没有对话框显示，有则不显示工具条
    var shareDiv = document.getElementById("doc_share"), shareDiv = shareDiv == null ? "none" : shareDiv.style.display;
    if (document.getElementById("doc_open").style.display != "none" ||
            document.getElementById("doc_createDir").style.display != "none" ||
            document.getElementById("doc_delete").style.display != "none" ||
            document.getElementById("doc_publish").style.display != "none" ||
            document.getElementById("doc_create_content").style.display != "none" ||
            shareDiv != "none")
        return;
    var ctl_tools = $('#doc_tools'), ctl_tools_div = ctl_tools.find("div");
    ctl_tools_div.attr("objectid", id);
    if (isDir)
    {
        var acc = $(".doc_info_name[dir_id='" + id + "']").parent().attr("jid");
        ctl_tools_div.attr("isdir", "1");
        ctl_tools_div.attr("isfile", "");
        ctl_tools_div.hide();
        if (g_owner != acc)
            return;
        ctl_tools.find('.doc_tools_rename').show();
        ctl_tools.find('.doc_tools_share').show();
        ctl_tools.find('.doc_tools_delete').show();
    }
    else
    {
        var acc = $(".doc_info_name[file_id='" + id + "']").parent().attr("jid");
        ctl_tools_div.attr("isfile", "1");
        ctl_tools_div.attr("isdir", "");
        ctl_tools_div.show();
        if (g_owner != acc) {
            ctl_tools.find('.doc_tools_share').hide();
            ctl_tools.find('.doc_tools_publish').hide();
            ctl_tools.find('.doc_tools_delete').hide();
        }
        ctl_tools.find('.doc_tools_rename').hide();
    }
    ctl_tools.css({'left': x + 'px', 'top': y + 'px', 'display': 'block', 'z-index': '10000'});
}
function openfile(dirid)
{
    $('#doc_tools').hide();
    var win = $(".doc_open");
    win.find(".doc_window_title span:eq(0)").html("&nbsp;&nbsp;文件查看-" + $(".doc_info_name[file_id='" + dirid + "'] a").text());
    $("#filefrm")[0].src = win.attr("url") + "?fileid=" + dirid;
    $("#download_to_location").attr("href", path.replace("getfile", "downloadfile") + dirid);
    var _copy_url = path.indexOf("http") > -1 ? path + dirid :
            document.location.protocol + "//" + document.location.host + (document.location.port != "" && document.location.port != "80" ? ":" + document.location.port : "") + path + dirid;
    $("#file_addr_text").val(_copy_url);
    win.show();
    Clipboardinit();
    //获得全部评论
    var cominfo = filecomcache.get(dirid);
    $("#allcontent").find('.com_one').unbind().remove();
    $("#com_all_note .com_all").hide();
    if (typeof(cominfo) == 'undefined' || cominfo.length >= 5) {
        LoadData(null, getcomUrl + "?isdir=&isfile=1&resourceid=" + dirid + "&gettype=all", function(d) {
            showAllComment(d.cominfo, dirid);
        });
    }
    else {
        showAllComment(cominfo, dirid);
    }
}
function showAllComment(cominfo, dirid) {
    var $allcontent = $("#content_list");
    $allcontent.find('.com_one').unbind().remove();
    if (cominfo.length == 0) {
        $("#com_all_note").text('暂无评论!').show();
    }
    else {
        $("#com_all_note").hide();
        $(".com_all").show();
        $allcontent.append(getComHTML(cominfo));
        $(".com_all a").unbind('click').click(function() {
            showPutCard(dirid, '', '');
        });
        $allcontent.find('.com_reply').bind('click', function(event) {
            var e = event || window.event;
            if (checkHover(event, this)) {
                var $conv = $(this).parent().parent().find("a[value='conv_account']");
                var conv_to = $conv.attr('login_account');
                var to_nickname = $conv.text();
                showPutCard(dirid, conv_to, to_nickname);
            }
        });
    }
}
function showPutCard(dirid, conv_to, to_nickname) {
    $("#putcard").unbind().remove();
    var html = [];
    html.push("<div class='modal doc_comment' isdir='' isfile='1' objectid='" + dirid + "' id='putcard'>");
    html.push("<div class='doc_window_title'><span>&nbsp;&nbsp;发表评论</span><span onclick='$(\"#putcard\").unbind().remove();' style='cursor:pointer;float:right;font-size:15px;margin-right:5px;'>×</span></div><div class='comment_list'>");
    html.push("<div style='width:100%;text-align:center;display:none;padding-top:50px;' id='sub'>正在保存...</div>");
    html.push("<div class='com_write_all'><div class='com_write_title_all'>");
    if (conv_to != '') {
        html.push("<span>我对<a login_account='" + conv_to + "'>" + to_nickname + "</a>说:</span>");
    }
    else {
        html.push("<span>我说:</span>");
    }
    html.push("</div><textarea maxlength='50' placeholder='写下你想说的!'>");
    html.push("</textarea><div class='sta_all'>还能输入字符数:<span class='com_letter'>50</span>");
    html.push("<input class='doc_md_content_right_btn putCom' type='button' value='提交' style='float:right;margin-right:4px;'/></div></div></div></div>");
    $(document.body).append(html.join(''));
    $(".com_write_all textarea").bind('keyup change', function() {
        var max_len = parseInt($(this).attr('maxlength'));
        var len = $(this).val().length;
        var tex = $(this).val();
        if (len < max_len) {
            $("#putcard .com_letter").text(max_len - len);
        }
        else {
            $(this).val(tex.substring(0, max_len));
            $("#putcard .com_letter").text('0');
        }
    });
    $(".putCom").click(function() {
        submitCom($(".com_write_title_all").find('a'), $('.com_write_all textarea').val(), $('#putcard'), '0');
    });
}
function submitCom()
{
    var $conv = arguments[0] ? arguments[0] : $(".com_write_title").find('a');
    var tex = arguments[1] ? arguments[1] : $(".com_write textarea").val();
    if (tex == '')
        return;
    var $doc_comment = arguments[2] ? arguments[2] : $("#doc_comment");
    var conv_to = ($conv.length == 1 ? $conv.attr('login_account') : '');
    var tocache = arguments[3] ? arguments[3] : '1';
    $.getJSON(putComUrl + (tocache == '1' ? '' : '?gettype=all'), {
        isdir: $doc_comment.attr('isdir'),
        isfile: $doc_comment.attr('isfile'),
        resourceid: $doc_comment.attr('objectid'),
        content: tex,
        conv_to: conv_to
    }, function(d) {
        if (d.s == '1') {
            if (tocache == '1') {
                if ($doc_comment.attr('isdir') == '1') {
                    dircomcache.put($doc_comment.attr('objectid'), d.cominfo);
                }
                else {
                    filecomcache.put($doc_comment.attr('objectid'), d.cominfo);
                }
                showComment(d.cominfo);
            }
            else {
                $("#putcard").unbind().remove();
                showAllComment(d.cominfo);
            }
        }
        else {
        }
    });
    if (tocache == '1') {
        $("#com_note").text('正在保存...').show().siblings().hide();
        $(".com_write").hide();
        $(".com_content").show();
    }
    else {
        $("#sub").show();
        $(".com_write_all").hide();
    }
}
function changeComCard() {
    var $com_write = $("div.com_write");
    var $com_content = $("div.com_content");
    if ($("#doc_comment").attr('active') == '0' || $com_write.find('.com_write_title').text() == '')
        return;
    if ($com_write.css('display') == 'none') {
        $com_write.show();
        $com_content.hide();
        $com_write.find('textarea').focus();
    }
    else {
        $com_write.hide();
        $com_content.show();
    }
}
function LoadData(e_curr, url, callback) {
    var loadimg = arguments[3] ? arguments[3] : '/bundles/fafatimewebase/images/loading.gif';
    var loadcontainter = arguments[4] ? arguments[4] : e_curr;
    $.post(url, {}, function(data) {
        $(loadcontainter).siblings('.LoadPage_loadcontainter').remove();
        $(e_curr).show();
        callback(data);
    });
    $(loadcontainter).after("<div class='LoadPage_loadcontainter' style='width:auto;height:auto;text-align:center;'><img align='center' src='" + loadimg + "'/></div>");
    $('.LoadPage_loadcontainter').css({width: $(e_curr).width() + 'px', height: $(e_curr).height()});
}
function closeSS() {
    $(".share_set_div").hide();
    $(".share_group_select").remove();
    $(".share_group_div,.share_dept_div").unbind();
    $(".share_user_list").remove();
    $("#estop").remove();
}
function submitSS() {
    $("#estop").remove();
    $(".share_content").hide();
    $(".share_load").show().text("正在保存...");
    var group_list = [];
    var dept_list = [];
    var user_list = [];
    if ($(".share_set_ul input:checkbox[value='group']").attr("checked")) {
        var items = $(".share_group_select li[isattach='1']");
        for (var i = 0; i < items.length; i++) {
            group_list.push($(items[i]).attr('val'));
        }
    }
    if ($(".share_set_ul input:checkbox[value='dept']").attr("checked")) {
        var items = $(".dept_list").find('.t');
        for (var j = 0; j < items.length; j++) {
            dept_list.push($(items[j]).attr('val'));
        }
    }
    if ($(".share_set_ul input:checkbox[value='user']").attr("checked")) {
        var items = $(".user_list").children('span');
        for (var i = 0; i < items.length; i++) {
            user_list.push($(items[i]).attr('val'));
        }
    }
    $.getJSON(shareSetUrl, {
        group_list: group_list,
        dept_list: dept_list,
        user_list: user_list,
        isdir: $(".share_set_div").attr('isdir'),
        isfile: $(".share_set_div").attr('isfile'),
        resourceid: $(".share_set_div").attr('objectid'),
        isshare: $(".isshare_ul input:checked").attr('isshare')
    },
    function(d) {
        if (d.s == '1') {
            if ($(".share_set_div").attr('isdir') == '1') {
                dirsharecache.put($(".share_set_div").attr('objectid'), d.shareinfo);
            }
            else {
                filesharecache.put($(".share_set_div").attr('objectid'), d.shareinfo);
            }
            closeSS();
        }
        else {
        }
    });
}
function estop(i) {
    if (i) {
        var html = "<div style='display:none' id='estop'></div>";
        $(document.body).append(html);
        var $table = $(".share_set_table");
        var wid = $table.width();
        var hei = $table.height();
        var le = $table.offset().left;
        var to = $table.offset().top;
        $("#estop").css({position: 'absolute', 'left': le + 'px', 'top': to + 'px', 'width': wid + 'px', 'height': hei + 'px', 'background-color': '#CCC', 'opacity': '0.5', 'cursor': 'not-allowed', 'z-index': '1090'});
        $("#estop").show();
    }
    else {
        $("#estop").remove();
    }
}
var ElementAction = {
    ppmList: Array(),
    ppmAction: function(e_curr, e_aim) {
        var locate = arguments[2] ? arguments[2] : 'blow';
        var operate = arguments[3] ? arguments[3] : 'click';
        var e_by = arguments[4] ? arguments[4] : e_curr;
        $(e_curr).bind(operate, function(event) {
            if (event.type=="click" || checkHover(event, this)) {
                var e_top = $(e_by).offset().top;
                var e_left = $(e_by).offset().left;
                var e_height = e_by.offsetHeight;
                var sheet_x = e_left;
                var sheet_y = e_top + e_height;
                $(e_aim).css({'left': sheet_x, 'top': sheet_y});
                if ($(e_aim).parent().length == 0) {
                    $(document.body).append(e_aim);
                }
                else {
                    $(e_aim).show();
                }
                $(this).siblings().find('input').attr('checked', true);
                var i = ElementAction.ppmList.length;
                var j = 0;
                while (i > -1) {
                    i--;
                    if (ElementAction.ppmList[i] == e_aim) {
                        j++;
                        break;
                    }
                }
                if (j == 1)
                    return;
                ElementAction.ppmList.push(e_aim);
            }
        });
        $(e_curr).bind('mouseout', function(event) {
            if (checkHover(event, this)) {
                sheetClose = setTimeout(function(event) {
                    ElementAction.ppmRemove(null);
                }, 300);
            }
        });
        $(e_aim).bind('mouseover', function(event) {
            if (checkHover(event, this)) {
                if (typeof(sheetClose) != 'undefined') {
                    clearTimeout(sheetClose);
                }
                ElementAction.ppmRemove(this);
            }
        });
        $(e_aim).bind('mouseout', function(event) {
            if (checkHover(event, this)) {
                sheetClose = setTimeout(function() {
                    ElementAction.ppmRemove(null);
                }, 300);
            }
        });
    },
    ppmRemove: function(e_aim) {
        var i = -1;
        for (i = ElementAction.ppmList.length - 1; i > -1; i--) {
            if (ElementAction.ppmList[i] == e_aim)
                break;
            $(ElementAction.ppmList[i]).hide();
        }
        if (ElementAction.ppmList.length > 0) {
            ElementAction.ppmList.splice(i + 1, ElementAction.ppmList.length - i - 1);
        }
    }
}
function createUserList(keyword) {
    $('.share_user_list').remove();
    var userHTML = "<ul style='display:none' class='share_user_list'>";
    var listLen = 0;
    for (var i = 0; i < usercontainter.length; i++) {
        if (usercontainter[i]['login_account'].indexOf(keyword) > -1 || usercontainter[i]['nick_name'].indexOf(keyword) > -1) {
            userHTML += "<li val='" + usercontainter[i]['login_account'] + "'><span>" + usercontainter[i]['nick_name'] + "</span>(" + usercontainter[i]['login_account'] + ")" + "</li>";
            listLen++;
        }
    }
    userHTML += "</ul>";
    if (listLen == 0) {
        return false;
    }
    else {
        $(document.body).append(userHTML);
        var $share_user_div = $('.share_user_div'), le = $share_user_div.offset().left, to = $share_user_div.offset().top, hei = $share_user_div.height();
        var $share_user_list = $('.share_user_list');
        $share_user_list.css({left: le + 'px', top: (to + hei + 3) + 'px'});
        $share_user_list.find('li:first').addClass('user_isattach').attr('isattach', '1');
        $share_user_list.find('li').bind('click', function() {
            $this = $(this);
            $this.siblings('.user_isattach').removeClass('user_isattach').attr('isattach', '0');
            $this.addClass('user_isattach');
            $this.attr('isattach', '1');
            var val = $this.attr('val');
            if ($share_user_div.find("span[val='" + val + "']").length == 0) {
                $(".user_list").append("<span class='t share_active' val='" + $this.attr('val') + "'>" + $this.find('span').text() + "<span class='c' onclick='$(this).parent().remove()'>×</span></span>");
                $(".share_user_list").remove();
                $(".share_user_text").focus();
                setShareUserLayout();
            }
        });
        $share_user_list.show();
        return true;
    }
}
function setShareUserLayout() {
    var wid = 160 - $(".user_list").width() - 5;
    if (wid <= 0) {

    }
    $(".share_user_text").css('width', wid + 'px').val('');
}
function setGDLayout(a, b) {
    var a_len = a.scrollWidth;
    var b_len = $(b).width();
    $(a).css('margin-left', (a_len - b_len > 0 ? b_len - a_len : 0) + 'px');
}
function groupRe(ev, e) {
    $parent = $(e).parent();
    $(".share_group_select li[val='" + $parent.attr('val') + "']").attr('isattach', '0').css('background-color', '');
    $parent.remove();
    if ($(".select_group").siblings().length == 0) {
        $(".select_group").show();
    }
    setGDLayout($(".group_list")[0], $(".share_group_div")[0]);
    if (ev && ev.stopPropagation) {
        ev.stopPropagation();
    }
    else {
        window.event.cancelBubble = true;
    }
}
function deptRe(ev, e) {
    $parent = $(e).parent();
    $parent.remove();
    if ($(".select_dept").siblings().length == 0) {
        $(".select_dept").show();
    }
    setGDLayout($(".dept_list")[0], $(".share_dept_div")[0]);
    if (ev && ev.stopPropagation) {
        ev.stopPropagation();
    }
    else {
        window.event.cancelBubble = true;
    }
}
function showShareContainter(strJson) {
    if ($(".share_set_div").length == 0) {
        var html = [];
        html.push("<div id='doc_share' style='display:none' isdir='" + strJson[0] + "' isfile='" + strJson[1] + "' objectid='" + strJson[2] + "' class='modal share_set_div'>");
        html.push("<div class='doc_window_title'><span>&nbsp;&nbsp;共享设置</span></div>");
        html.push("<div style='width:100%;height:50px;border-bottom:1px solid #CCC'><div class='share_image_div'></div><div class='share_name_div'>" + strJson[3] + "</div></div>");
        html.push("<div class='share_load'>正在加载共享信息...</div><div style='display:none' class='share_content'>");
        html.push("<ul class='isshare_ul'><li><input type='radio' name='fff' isshare='0'/>不共享</li><li><input type='radio' name='fff' isshare='1'/>共享</li></ul>");
        html.push("<table class='share_set_table'><tr><td width='60px'>共享范围：</td>");
        html.push("<td><ul class='share_set_ul'><li><div><input type='checkbox' value='user'/>指定用户：</div><div class='share_user_div'><span class='user_list'></span><input type='text' style='border:0;padding:0;padding-left:3px;height:24px;background-color:white;margin-bottom:0;box-shadow:none;' class='share_user_text'/></div></li>" +
                "<li><div><input type='checkbox' value='group'/>指定群组：</div><div class='share_group_div'><span class='group_list'><span class='select_group'>选择群组</span></span></div></li>" +
                "<li><div><input type='checkbox' value='dept'/>指定部门：</div><div class='share_dept_div'><span class='dept_list'><span class='select_dept'>选择部门</span></span></div></li></ul></td></tr></table>");
        html.push("<div class='share_bar_div'><span class='doc_md_content_right_btn' onclick='submitSS()' >保存</span><span class='doc_md_content_right_btn' onclick='closeSS()'>取消</span></div></div></div>");
        $docshowlist.append(html.join(''));
        $(".isshare_ul input:radio").click(function() {
            if ($(this).attr('isshare') == '1') {
                estop(false);
            }
            else {
                estop(true);
            }
        });
        $(".share_set_ul input:checkbox").click(function() {
            var $this = $(this);
            if (!!$this.attr('checked')) {
                if ($this.val() == 'user') {
                    $(".share_user_text").attr('disabled', false);
                    $(".share_user_div").css('cursor', 'text');
                }
            }
            else {
                if ($this.val() == 'user') {
                    $(".share_user_text").attr('disabled', true);
                    $(".share_user_div").css('cursor', 'not-allowed');
                }
            }
        });
        $(".share_user_text").bind("keydown", function(event) {
            var $share_user_div = $('.share_user_div'), $share_user_list = $('.share_user_list'), $this = $share_user_list.find("li[isattach='1']"), $this_n = $this.next(), $this_p = $this.prev();
            var e = event || window.event;
            if (event.keyCode == 40) {//上
                $this.attr('isattach', '0');
                $this.removeClass('user_isattach');
                if ($this_n.length == 1) {
                    $this_n.attr('isattach', '1');
                    $this_n.addClass('user_isattach');
                }
                else {
                    var $list_first = $share_user_list.find('li:eq(0)');
                    $list_first.attr('isattach', '1');
                    $list_first.addClass('user_isattach');
                }
                return false;
            }
            else if (event.keyCode == 38) {//下
                $this.attr('isattach', '0');
                $this.removeClass('user_isattach');
                if ($this_p.length == 1) {
                    $this_p.attr('isattach', '1');
                    $this_p.addClass('user_isattach');
                }
                else {
                    $share_user_list.find('li:last').attr('isattach', '1');
                    $share_user_list.find('li:last').addClass('user_isattach');
                }
                return false;
            }
            else if (event.keyCode == 13) {//enter
                var val = $this.attr('val');
                if ($share_user_div.find("span[val='" + val + "']").length == 0) {
                    $(".user_list .share_active").removeClass('share_active');
                    $(".user_list").append("<span class='t share_active' val='" + val + "'>" + $this.find('span').text() + "<span class='c' onclick='$(this).parent().remove()'>×</span></span>");
                    $(".share_user_list").remove();
                    setShareUserLayout();
                }
            }
        });
        $(".share_user_div").click(function(event) {
            if (checkHover(event, this)) {
                $(".share_user_text").focus();
            }
        });
        $(".share_user_text").die('keyup').live('keyup', function(event) {
            //if(checkHover(event,this))
            {
                if (typeof(keyword) != 'undefined' && $('.share_user_list').length == 1 && keyword == $(".share_user_text").val())
                    return;
                keyword = $(".share_user_text").val();
                var el = keyword.match(/^[A-Za-z0-9]{1,}$/), nchar = keyword.match(/^[A-Za-z0-9\u4e00-\u9fa5]{1,}$/);
                if (nchar == null && el == null) {
                    $(".share_user_list").remove();
                    return;
                }
                if (typeof(usercontainter) == 'undefined') {
                    window.usercontainter = new Array();
                }
                if (!createUserList(keyword)) {
                    LoadData(null, getPersonUrl + "?keyword=" + keyword + "&keytype=" + (el == null ? 'name' : ''), function(d) {
                        if (d.length > 0) {
                            usercontainter = d;
                            createUserList(keyword);
                        }
                    });
                }
            }
        });
    }
    else {
        $(".share_content").hide();
        $(".share_set_div").attr({'isdir': strJson[0], 'isfile': strJson[1], 'objectid': strJson[2]});
        $(".share_name_div").text(strJson[3]);
        $(".share_load").show().text("正在加载共享信息...");
    }
    var selectLine;
    if (strJson[0] == '1') {
        selectLine = $("span.doc_info_name[dir_id='" + strJson[2] + "']").parent();
    }
    else {
        selectLine = $("span.doc_info_name[file_id='" + strJson[2] + "']").parent();
    }
    var to = selectLine[0].offsetTop;
    var cardhei = $("#doc_share").height();
    $("#doc_share").css('top', (to - cardhei) > 0 ? ((to - cardhei) + "px") : '0px');
    $(".share_set_div").show();
}
window.filesharecache = new HashMap();
window.dirsharecache = new HashMap();
window.filecomcache = new HashMap();
window.dircomcache = new HashMap();
function getShareCard(d, strJson) {
    var shareinfo = null;
    if (strJson[0] == '1') {
        shareinfo = dirsharecache.get(strJson[2]);
    }
    else {
        shareinfo = filesharecache.get(strJson[2]);
    }
    if (typeof(groups) == 'undefined' || typeof(depts) == 'undefined' || typeof(shareinfo) == 'undefined') {
        window.groups = d.groups;
        window.depts = d.depts;
        shareinfo = d.shareinfo;
    }
    var group_list = "<ul style='display:none' class='share_group_select'>";
    for (var item = 0; item < groups.length; item++) {
        var isattach = 0;
        for (var i = 0; i < shareinfo.groups.length; i++) {
            if (shareinfo.groups[i]['objectid'] == groups[item]['group_id']) {
                isattach = 1;
                break;
            }
        }
        group_list += "<li isattach='" + isattach + "' val='" + groups[item]['group_id'] + "'>" + groups[item]['group_name'] + "</li>";
    }
    group_list += "</ul>";
    var dept_list = "<ul style='display:none' class='share_dept_select'>";
    for (var item = 0; item < depts.length; item++) {
        var isattach = 0;
        for (var i = 0; i < shareinfo.depts.length; i++) {
            if (shareinfo.depts[i]['objectid'] == depts[item]['dept_id']) {
                isattach = 1;
                break;
            }
        }
        dept_list += "<li isattach='" + isattach + "' val='" + depts[item]['dept_id'] + "'>" + depts[item]['dept_name'] + "</li>";
    }
    dept_list += "</ul>";
    if (d != '') {
        if (strJson[0] == '1') {
            dirsharecache.put(strJson[2], shareinfo);
        }
        else {
            filesharecache.put(strJson[2], shareinfo);
        }
    }
    $(document.body).append(group_list);
    $(".select_group").show().siblings('span').remove();
    $(".select_dept").show().siblings('span').remove();
    $(".user_list").find('span').remove();
    $(".share_set_ul input:checkbox").attr('checked', false);
    if (shareinfo.groups.length > 0) {
        $(".share_set_ul input:checkbox[value='group']").attr('checked', true);
        $(".select_group").hide();
        for (var i = 0; i < shareinfo.groups.length; i++) {
            $(".group_list").append("<span class='t' val='" + shareinfo.groups[i]['objectid'] + "'>" + shareinfo.groups[i]['object_name'] + "<span onclick='groupRe(event,this)' class='c'>×</span></span>");
        }
        setGDLayout($(".group_list")[0], $('.share_group_div')[0]);
    }
    if (shareinfo.depts.length > 0) {
        $(".share_set_ul input:checkbox[value='dept']").attr('checked', true);
        $(".select_dept").hide();
        for (var i = 0; i < shareinfo.depts.length; i++) {
            $(".dept_list").append("<span class='t' val='" + shareinfo.depts[i]['objectid'] + "'>" + shareinfo.depts[i]['object_name'] + "<span onclick='deptRe(event,this)' class='c'>×</span></span>");
        }
        setGDLayout($(".dept_list")[0], $('.share_dept_div')[0]);
    }
    if (shareinfo.persons.length == 0) {
        $(".share_user_text").attr('disabled', true);
        $(".share_user_div").css('cursor', 'not-allowed');
    }
    else {
        $(".share_set_ul input:checkbox[value='user']").attr('checked', true);
        $(".share_user_text").attr('disabled', false);
        var $user_list = $(".user_list");
        var span = [];
        for (var i = 0; i < shareinfo.persons.length; i++) {
            span.push("<span class='t' val='" + shareinfo.persons[i]['objectid'] + "'>" + shareinfo.persons[i]['object_name'] + "<span onclick='$(this).parent().remove()' class='c'>×</span></span>");
        }
        $user_list.append(span.join(''));
        setShareUserLayout();
    }
    $(".share_group_select li[isattach='1'],.share_dept_select li[isattach='1']").css('background-color', '#dcdcdc');
    $(".share_group_select li").click(function() {
        var $this = $(this);
        if ($this.attr('isattach') == '1') {
            $this.css('background-color', '');
            $this.attr('isattach', '0');
            $(".group_list").children("span[val='" + $this.attr('val') + "']").remove();
            if ($(".select_group").siblings().length == 0) {
                $(".select_group").show();
            }
        }
        else {
            $this.css('background-color', '#dcdcdc');
            $this.attr('isattach', '1');
            $(".select_group").hide();
            $(".group_list").append("<span class='t' val='" + $this.attr('val') + "'>" + $this.text() + "<span onclick='groupRe(event,this)' class='c'>×</span></span>");
            setGDLayout($(".group_list")[0], $('.share_group_div')[0]);
        }
    });
    ElementAction.ppmAction($(".share_group_div")[0], $(".share_group_select")[0]);
    ElementAction.ppmAction($(".share_dept_div")[0], $("#deptlist")[0]);
    $(".dept_list .c").bind('click', function() {
        $(this).parent().remove();
        if ($(".select_dept").siblings().length == 0) {
            $(".select_dept").show();
        }
    });
    $(".share_load").hide();
    $(".share_content").show();
    if (shareinfo.depts.length + shareinfo.groups.length + shareinfo.persons.length > 0) {
        $(".isshare_ul input:radio[isshare='1']").attr('checked', true);
        estop(false);
    }
    else {
        $(".isshare_ul input:radio[isshare='0']").attr('checked', true);
        estop(true);
    }
}
function getMoreCom() {
    openfile($("#doc_comment").attr('objectid'));
}
function getComHTML(cominfo) {
    var html = [];
    for (var i = 0; i < cominfo.length; i++) {
        html.push("<div class='com_one'><div><span class='t'>" +
                "<a value='conv_account' class='employee_name' login_account='" +
                cominfo[i]['conv_account'] + "'>" + cominfo[i]['conv_nickname'] + "</a>");
        if (cominfo[i]['conv_to'] != '') {
            html.push("对<a value='conv_to' class='employee_name' login_account='" +
                    cominfo[i]['conv_to'] + "'>" + cominfo[i]['to_nickname'] + "</a>");
        }
        html.push("说:</span></div><div><p>" + cominfo[i]['content'] +
                "</p></div><div><span>" + cominfo[i]['conv_time'] + "</span><a class='com_reply'>回复</a></div></div>");
    }
    return html.join('');
}
function showComment(cominfo) {
    var $com_content = $("#conv_div"), $com_note = $("#com_note"), $textarea = $(".com_write textarea");
    $com_content.find('.com_one').unbind().remove();
    if (cominfo.length == 0) {
        $com_note.text('暂无评论!');
        $com_note.show().siblings().hide();
        return;
    }
    else {
        $com_note.hide();
        $(".com_5").show();
        $("#conv_content").append(getComHTML(cominfo)).css('top', '0px');
//		cl=0,_=Function;
//		with(o=document.getElementById("conv_content")){ innerHTML+=innerHTML; onmouseover=_("cl=1"); onmouseout=_("cl=0");} 
//		(F=_("if(#%68||!cl){#++;console.info(o.scrollHeight);}o.style.top=(o.scrollHeight>>1)+'px';console.info(o.scrollTop);setTimeout(F,#%68?10:3500);".replace(/#/g,"o.scrollTop")))();
        $("#conv_content a.com_reply").bind('click', function(event) {
            var ev = event || window.event;
            if (checkHover(event, this)) {
                var $conv = $(this).parent().parent().find("a[value='conv_account']");
                var conv_to = $conv.attr('login_account');
                var to_nickname = $conv.text();
                $(".com_write_title").html('').append("<span>我对<a login_account='" + conv_to + "'>" + to_nickname + "</a>说:</span>");
                $textarea.val('');
                $("#conv_div .com_letter").text($textarea.attr('maxlength'));
                changeComCard();
                if (ev && ev.stopPropagation) {
                    ev.stopPropagation();
                }
                else {
                    window.event.cancelBubble = true;
                }
            }
        });
    }
    $(".com_write").hide();
    $com_content.show();
    $("div.com_content").show();
}
function showshare(shareinfo)
{
    var ps = [];
    if (shareinfo.persons.length > 0) {
        ps.push("<p>共享用户:");
        for (var i = 0; i < shareinfo.persons.length; i++) {
            if (i == shareinfo.persons.length - 1) {
                ps.push("<a class='employee_name' login_account='" + shareinfo.persons[i]['objectid'] + "'>" + shareinfo.persons[i]['object_name'] + "</a>");
            }
            else {
                ps.push("<a class='employee_name' login_account='" + shareinfo.persons[i]['objectid'] + "'>" + shareinfo.persons[i]['object_name'] + "</a>,");
            }
        }
        ps.push("</p>");
    }
    if (shareinfo.groups.length > 0) {
        ps.push("<p>共享群组:");
        for (var i = 0; i < shareinfo.groups.length; i++) {
            if (i == shareinfo.groups.length - 1) {
                ps.push("<a objectid='" + shareinfo.groups[i]['object_id'] + "'>" + shareinfo.groups[i]['object_name'] + "</a>");
            }
            else {
                ps.push("<a objectid='" + shareinfo.groups[i]['object_id'] + "'>" + shareinfo.groups[i]['object_name'] + "</a>,");
            }
        }
        ps.push("</p>");
    }
    if (shareinfo.depts.length > 0) {
        ps.push("<p>共享群组:");
        for (var i = 0; i < shareinfo.depts.length; i++) {
            if (i == shareinfo.depts.length - 1) {
                ps.push("<a objectid='" + shareinfo.depts[i]['object_id'] + "'>" + shareinfo.depts[i]['object_name'] + "</a>");
            }
            else {
                ps.push("<a objectid='" + shareinfo.depts[i]['object_id'] + "'>" + shareinfo.depts[i]['object_name'] + "</a>,");
            }
        }
        ps.push("</p>");
    }
    $("#docinfo_share").html('').append(ps.join(''));
}
function winScroll(e) {
    if (e.preventDefault) {
        e.preventDefault();
    }
    e.returnValue = false;
}
function convScroll(e) {
    var $this = document.getElementById('conv_content');
    var pra_wid = $("#conv_containter").height();
    var th_wid = $this.scrollHeight;
    if (th_wid > pra_wid) {
        var ev = e || window.event, go_f, speed = 10;
        if (ev.wheelDelta) {
            go_f = ev.wheelDeilta;
        }
        else if (ev.detail) {
            go_f = ev.detail;
        }
        var scrollT = $this.style.top == '' ? 0 : (($this.style.top.match(/\d+/)[0]) * (-1));
        if (go_f > 0) {
            if (scrollT > pra_wid - th_wid) {
                if (scrollT - speed > (pra_wid - th_wid)) {
                    $this.style.top = (scrollT - speed) + 'px';
                }
                else {
                    $this.style.top = (pra_wid - th_wid) + 'px';
                }
            }
        }
        else {
            if (scrollT < 0) {
                if (scrollT + speed < 0) {
                    $this.style.top = (scrollT + speed) + 'px';
                }
                else {
                    $this.style.top = '0px';
                }
            }
        }
    }
    if (ev.preventDefault) {
        ev.preventDefault();
    }
    ev.returnValue = false;
}
function init(_deleteUrl, getdgUrl, group_id, group_name)
{
    if (group_id)
        loadGroupDocList(group_id, group_name);
    else
        loadDocList("");

    $(".doc_info_name").live("click", function() {
        var $this = $(this);
        var driid = $this.attr("dir_id");
        if (typeof(driid) != "undefined" && driid != "")
        {
            //进入目录
            setDirPath(driid, $.trim($this.text()));
            loadDocList(driid);
            return;
        }
        var fileid = $this.attr("file_id");
        if (typeof(fileid) == "undefined" || fileid == "")
        {
            return;
        }
    });
    $(".com_write textarea").bind('keyup change', function() {
        var max_len = parseInt($(this).attr('maxlength'));
        var len = $(this).val().length;
        var tex = $(this).val();
        if (len < max_len) {
            $("#conv_div .com_letter").text(max_len - len);
        }
        else {
            $(this).val(tex.substring(0, max_len));
            $("#conv_div .com_letter").text('0');
        }
    });
    if (mode != "select")
    {
        var conv_content = document.getElementById('conv_content');
        if (conv_content.addEventListener) {
            conv_content.addEventListener('DOMMouseScroll', convScroll, false);
        }
        conv_content.onmousewheel = convScroll;
        window.moves = 0;
        $("#com_note,#com_put").live('click', function() {
            if ($('.com_write').css('display') != 'none')
                return;
            if ($("#doc_comment").attr('active') == '1') {
                $('div.com_write_title').html('').append("<span>我说:</span>");
                changeComCard();
                $('.com_write').find('textarea').focus();
            }
        });
        $('.doc_tools_share').live('click', function() {
            var $this = $(this);
            var objectid = $this.attr('objectid');
            var strJson = [];
            strJson.push($this.attr('isdir'));
            strJson.push($this.attr('isfile'));
            strJson.push(objectid);
            var shareinfo = null;
            if (strJson[0] == '1') {
                shareinfo = dirsharecache.get(strJson[2]);
                strJson.push($(".doc_info_name[dir_id='" + objectid + "']").text());
            }
            else {
                shareinfo = filesharecache.get(strJson[2]);
                strJson.push($(".doc_info_name[file_id='" + objectid + "']").text());
            }
            $('#doc_tools').hide();
            showShareContainter(strJson);
            if (typeof(window.groups) != 'undefined' && typeof(window.depts) != 'undefined' && typeof(shareinfo) != 'undefined') {
                getShareCard('', strJson);
                return;
            }

            LoadData(null, getdgUrl + "?isdir=" + strJson[0] + "&isfile=" + strJson[1] + "&resourceid=" + strJson[2], function(d) {
                getShareCard(d, strJson);
            });
        });
        $(".list_tr").live("mouseout", function(e) {
            clearTimeout(dochoverTimer);
            clearTimeout(docoutTimer);
            if (checkHover(e, this)) {
                docoutTimer = setTimeout("$('#doc_tools').hide()", 100);
            }
        });
        $(".list_tr").live("mouseover", function(e) {
            clearTimeout(docoutTimer);
            if (checkHover(e, this)) {
                var et = $(this).find(".doc_info_name");
                var ex = et.offset();//getEventCoord(e);
                var wx = {w: et.find("a").width() + 30, h: et.height()};
                var isDir = et.attr("dir_id");
                dochoverTimer = setTimeout("show_tools_item(" + (ex.left + wx.w) + "," + (ex.top + ((wx.h - 20) / 2)) + "," + (isDir == null ? false : true) + ",'" + (et.attr("dir_id") || et.attr("file_id")) + "')", 100);
            }
        });
        $(".doc_info_name a").live("mouseout", function(e) {
            clearTimeout(dochoverTimer);
            clearTimeout(docoutTimer);
            if (checkHover(e, this)) {
                docoutTimer = setTimeout("$('#doc_tools').hide()", 100);
            }
        });
        $(".doc_info_name a").live("mouseover", function(e) {
            clearTimeout(docoutTimer);
            if (checkHover(e, this)) {
                var s = $(this);
                var et = s.parent();
                var ex = et.offset();//getEventCoord(e);
                var wx = {w: s.width() + 30, h: et.height()};
                var isDir = et.attr("dir_id");
                dochoverTimer = setTimeout("show_tools_item(" + (ex.left + wx.w) + "," + (ex.top + ((wx.h - 20) / 2)) + "," + (isDir == null ? false : true) + ",'" + (et.attr("dir_id") || et.attr("file_id")) + "')", 100);
            }
        });
        $(".doc_info_name img").live("mouseout", function(e) {
            clearTimeout(dochoverTimer);
            clearTimeout(docoutTimer);
            if (checkHover(e, this)) {
                docoutTimer = setTimeout("$('#doc_tools').hide()", 100);
            }
        });
        $(".doc_info_name img").live("mouseover", function(e) {
            clearTimeout(docoutTimer);
            if (checkHover(e, this)) {
                var et = $(this).parent();
                var ex = et.offset();//getEventCoord(e);
                var wx = {w: et.find("a").width() + 30, h: et.height()};
                var isDir = et.attr("dir_id");
                dochoverTimer = setTimeout("show_tools_item(" + (ex.left + wx.w) + "," + (ex.top + ((wx.h - 20) / 2)) + "," + (isDir == null ? false : true) + ",'" + (et.attr("dir_id") || et.attr("file_id")) + "')", 100);
            }
        });
        $("#doc_tools").live("mouseover", function(e) {
            clearTimeout(docoutTimer);
        });
        $(".doc_tools_rename").live("click", function() {
            $('#doc_tools').hide();
            var dirid = $(this).attr("objectid");
            var $doc_createDir = $(".doc_createDir");
            $doc_createDir.find("input").val($(".doc_info_name[dir_id='" + dirid + "']").text());
            $doc_createDir.find("input")[0].focus();
            $(".savehint").html("");
            $doc_createDir.find(".doc_window_title span").html("&nbsp;&nbsp;重命名文件夹");
            $("#saveDir").attr("dir_id", dirid);
            $doc_createDir.show();
        });
        $(".doc_tools_delete").live("click", function() {
            $('#doc_tools').hide();
            var id = $(this).attr("objectid");
            var isdir = $(this).attr("isdir");
            var btn_ok = $("#deleteDoc"), btn_cancel = $("#cancelDelete"), $dlg_deleteconfirm = $(".doc_deleteconfirm");
            //检查是否可以删除
            btn_ok.attr("objectid", id);
            var checkUrl = (isdir != null && isdir != "") ? $dlg_deleteconfirm.attr("checkdirurl") + "?dirid=" + id : $dlg_deleteconfirm.attr("checkfileurl") + "?fileid=" + id;
            btn_ok.hide();
            btn_cancel.text("关闭");
            $dlg_deleteconfirm.find(".doc_rd_deleteconfirm_text").html("<IMG class=loadingimg src=\"/bundles/fafatimewebase/images/loadingsmall.gif\" width=16 height=16>&nbsp;删除前信息收集...");
            $dlg_deleteconfirm.show();
            $.get(checkUrl, "", function(d) {
                if (d.succeed == 0) {
                    $dlg_deleteconfirm.find(".doc_rd_deleteconfirm_text").html(d.msg);
                    return;
                }
                btn_ok.show();
                btn_cancel.text("取消");
                if (isdir != null && isdir != "")
                {
                    //删除目录
                    $dlg_deleteconfirm.find(".doc_window_title span").html("&nbsp;&nbsp;删除文件夹");
                    $dlg_deleteconfirm.find(".doc_rd_deleteconfirm_text").html("确定要删除该文件夹？");
                    btn_ok.attr("url_name", "deletedirurl");
                }
                else
                {
                    //删除文件		
                    $dlg_deleteconfirm.find(".doc_window_title span").html("&nbsp;&nbsp;删除文件");
                    $dlg_deleteconfirm.find(".doc_rd_deleteconfirm_text").html("确定要删除该文件？");
                    btn_ok.attr("url_name", "deletefileurl");
                }
            });
        });
        $(".doc_tools_open").live("click", function() {
            $('#doc_tools').hide();
            openfile($(this).attr("objectid"));
        });
        $(".doc_tools_download").live("click", function() {
            $('#doc_tools').hide();
            $("#filefrm").attr("src", path.replace("getfile", "downloadfile") + $(this).attr("objectid"));
        });
        $(".doc_open_file_download").live("click", function() {
            $("#filefrm").attr("src", $(this).attr("src"));
            $('#doc_tools').hide();
        });
        $(".doc_tools_publish").live("click", function() {
            var id = $(this).attr("objectid");
            $("#savePublish").attr("objectid", id);
            $('#doc_tools').hide();
            //$(".doc_publish").show();
            $file_text = $(".doc_info_name[file_id='" + id + "']").text();
            FaFaShare.share_Show_Window($file_text + "  打开:" + (path + id) + " 下载：" + (path.replace("getfile", "downloadfile") + id));
        });
        $(".doc_tools_cfile").unbind("click").bind("click", function() {
            if (currentDir.indexOf("|") > 0)
            {
                return;
            }
            $("#save_new_doc_hint").html("");
            $("#doc_create_content").show();
        });
        $("#saveNewDoc").unbind("click").bind("click", function() {
            var doc_name = $.trim($("#newdoc_name").val()), $save_new_doc_hint = $("#save_new_doc_hint");
            if (doc_name == "")
            {
                $save_new_doc_hint.html("<font color=red>&nbsp;&nbsp;提示：文档名称不能为空！</font>").show();
                setTimeout(function() {
                    $("#save_new_doc_hint").hide()
                }, 3000);
                return;
            }
            var msg = $.trim(newDoc_Input.getSource());
            if (msg == "")
            {
                $save_new_doc_hint.html("<font color=red>&nbsp;&nbsp;提示：文档正文不能为空！</font>").show();
                setTimeout(function() {
                    $("#save_new_doc_hint").hide()
                }, 3000);
                return;
            }
            $("#newdoc_content").val(msg);
            $("#newdoc_parent_dir").val(currentDir);
            $("#frm_newdoc").ajaxSubmit({
                dataType: 'json', //返回的数据类型
                success: function(r) {
                    if (r.succeed)
                    {
                        $save_new_doc_hint.html('&nbsp;&nbsp;提示：文档保存成功').show();
                        setTimeout(function() {
                            $('#doc_create_content').hide();
                            newDoc_Input.setSource('')
                        }, 2000);
                        //获取当前上传的文件
                        $.get($docshowlist.attr("fileurl") + "?parentid=" + currentDir + "&fileid=" + r.fileid, "", function(result) {
                            if (result.data.length > 0) {
                                files = result.data.concat(filesCache.get(currentDir));
                                filesCache.put(currentDir, files);
                                showDoc(files, dirsCache.get(currentDir));
                            }
                        });
                    }
                    else
                    {
                        $save_new_doc_hint.html('<font color=red>&nbsp;&nbsp;提示：保存失败</font>').show();
                        setTimeout(function() {
                            $('#doc_create_content').hide();
                            newDoc_Input.setSource('')
                        }, 3000);
                    }
                },
                error: function(e)
                {
                    $save_new_doc_hint.html('<font color=red>&nbsp;&nbsp;提示：保存失败</font>').show();
                    setTimeout(function() {
                        $('#doc_create_content').hide();
                        newDoc_Input.setSource('')
                    }, 3000);
                }
            });
        });
    }
    $("#docSearchCondition").unbind().bind("keypress", function(e) {
        if (e.keyCode == 13)
            $("#btnDocSearch").trigger("click");
    });
    $("#btnDocSearch").live("click", function() {
        var txt = $.trim($("#docSearchCondition").val());
        if (txt == "")
            return;
        var tct = $("#con_mgr_two1");
        tct.html("<div class='urlloading'><div />");
        var shearch_files = [], shearch_dirs = [];
        var pid = $("#hpost_to_group").val();//在当前圈子或者群组中搜索
        if (pid != null && pid != "")
            pid = "g" + pid;
        else
            pid = "";
//			  $.get($docshowlist.attr("dirurl")+"?parentid="+pid+(txt!=null?"&name="+txt:""),"",function(result)
//			   {
//			   	 shearch_dirs=result.data;
//			   	 if(shearch_files==null) return;
//			   	 showDoc(shearch_files,shearch_dirs);
//			   	 issearch=true;
//			  });
        $.get($docshowlist.attr("fileurl") + "?parentid=" + pid + (txt != null ? "&name=" + txt : ""), "", function(result) {
            shearch_files = result.data;
            currentDir = "search";
            showDoc(shearch_files, []);
            issearch = true;
        });
    });
    $(".list_tr").live("click", function() {
        var obj = $(this);
        $(".doc_row_active").attr("class", "list_tr doc_row");
        obj.attr("class", "list_tr doc_row_active");
        var curDoc = obj.find(".doc_info_name");
        if (curDoc.length == 0)
        {

            return;
        }
        var fid = curDoc.attr("file_id");
        if (mode == "select")
        {
            if (typeof(fid) == "undefined" || fid == "") {
                $(".insertSelFile").hide();
                return;
            }
            //选择文件模式
            currentSelectedFileID = fid;
            currentSelectedFileName = curDoc.text();
            $(".insertSelFile").show();
            return;
        }
        if ($("#attributes_list_hint").length > 0) {
            $("#attributes_list_content").show();
            $("#attributes_list_hint").remove();
        }
        //定位属性面板和评论面板
        var obj_Y = obj.offset().top, position_Y = obj_Y - fileListOffset.top, $attributes = $(".doc_attributes"), $comment = $(".doc_comment");
        if (position_Y > minHeight)
        {
            //定位到底边
            obj_Y = position_Y + obj.height() - $attributes.height() - $comment.height();
            $attributes.css("top", obj_Y + "px");
            $comment.css("top", obj_Y + "px");
        }
        else {
            $attributes.css("top", "0px");
            $comment.css("top", "0px");
        }
        var chatHtml = "", jid = obj.attr("jid");
        if (typeof(jid) != "undefined" && jid != "" && jid != g_owner)
            chatHtml = "<img style='cursor:pointer;width:16px;height:16px;margin-left: 5px;' title='给Ta发送消息' src='/bundles/fafatimewebase/images/reg_t0.png' onclick=\"FaFaChatWin.ShowRoster('" + jid + "')\">";

        $("#docinfo_name span:eq(1)").text(curDoc.text());
        $("#docinfo_owner span:eq(1)").html("<a class='employee_name' login_account='" + obj.attr("acc") + "'>" + obj.attr("user") + "</a>" + chatHtml);
        $("#docinfo_date span:eq(1)").text(obj.find(".doc_info_cdate").text());
        var _url = $attributes.attr("url");
        //获取属性
        var $dir = $(this).find('.doc_info_name[dir_id]');
        var $file = $(this).find('.doc_info_name[file_id]'), id, shareinfo;
        if ($dir.length != 0) {
            id = $dir.attr('dir_id');
            shareinfo = dirsharecache.get(id);
        }
        else {
            id = $file.attr('file_id');
            shareinfo = filesharecache.get(id);
        }
        if (typeof(shareinfo) == 'undefined') {
            LoadData(null, getdgUrl + "?isdir=" + ($dir.length == 0 ? '0' : '1') + "&isfile=" + ($dir.length == 0 ? '1' : '0') + "&resourceid=" + id, function(d) {
                shareinfo = d.shareinfo;
                window.groups = d.groups;
                window.depts = d.depts;
                if ($dir.length != 0) {
                    dirsharecache.put(id, shareinfo);
                }
                else {
                    filesharecache.put(id, shareinfo);
                }
                showshare(shareinfo);
            });
        }
        else {
            showshare(shareinfo);
        }
        //获取评论
        var $doc_comment = $("#doc_comment"), $comfor = $doc_comment.find(".comfor"), cominfo, $com_write_title = $(".com_write_title");
        $comfor.text('/' + curDoc.text());
        $comfor.attr('title', curDoc.text());
        $doc_comment.attr('active', '1');
        $com_write_title.text('');
        $com_write_title.siblings('textarea').val('');
        $("#conv_div .com_letter").text($com_write_title.siblings('textarea').attr('maxlength'));
        if ($dir.length != 0) {
            id = $dir.attr('dir_id');
            cominfo = dircomcache.get(id);
        }
        else {
            id = $file.attr('file_id');
            cominfo = filecomcache.get(id);
        }
        $doc_comment.attr('objectid', id);
        if ($dir.length != 0) {
            $doc_comment.attr('isdir', '1');
        }
        else {
            $doc_comment.attr('isfile', '1');
        }
        if (typeof(cominfo) == 'undefined') {
            LoadData(null, getcomUrl + "?isdir=" + ($dir.length == 0 ? '0' : '1') + "&isfile=" + ($dir.length == 0 ? '1' : '0') + "&resourceid=" + id, function(d) {
                cominfo = d.cominfo;
                if ($dir.length != 0) {
                    dircomcache.put(id, cominfo);
                }
                else {
                    filecomcache.put(id, cominfo);
                }
                showComment(cominfo);
            });
        }
        else {
            showComment(cominfo);
        }
    });
    $(".dirpath").live("click", function() {
        var driid = $(this).attr("dir_id");
        if (currentDir == driid && !issearch)
            return;
        issearch = false;
        if (driid == null || driid == "")
            $("#hpost_to_group").val("");
        //更新路径列表
        setDirPath(driid);
        loadDocList(driid);
    });
    $(".dir_tools_returnup").live("click", function() {
        if (currentDir == "" && !issearch)
            return;
        var _dir = $(".dirlist [dir_id='" + currentDir + "']");
        driid = _dir.prev().prev().attr("dir_id");
        if (driid == null)
            driid = "";
        //更新路径列表
        setDirPath(driid);
        loadDocList(driid);
    });
    $(".dir_tools_refresh").live("click", function() {
        dirsCache.put(currentDir, null);
        shareDirs.put(currentDir, null);
        loadDocList(currentDir);
    });
    if (mode != "select")
    {
        $(".doc_tools_cdir").bind("click", function() {
        	  
            var $doc_createDir = $(".doc_createDir");
            $doc_createDir.show();
            $doc_createDir.find("input").val("");
            $doc_createDir.find("input")[0].focus();
            $(".savehint").html("");
            $doc_createDir.find(".doc_window_title span").html("&nbsp;&nbsp;创建新文件夹");
            $("#saveDir").attr("dir_id", "");
            
        });
        $("#cancelDir").bind("click", function() {
            $(".doc_createDir").hide();
        });
        $("#cancelDelete").bind("click", function() {
            $(".doc_deleteconfirm").hide();
        });
        $("#cancelPublish").bind("click", function() {
            $(".doc_publish").hide();
            publish_commit = false;
        });
        //删除目录/文件
        $("#deleteDoc").bind("click", function() {
            var t = $(this);
            var url_name = t.attr("url_name");
            var _url = t.attr(url_name);
            $(".deletehint").html("<IMG class=loadingimg src=\"/bundles/fafatimewebase/images/loadingsmall.gif\" width=16 height=16>提交中...");
            $.post(_url, "fileid=" + t.attr("objectid"), function(d) {
                $("#deleteDoc").hide()
                $("#cancelDelete").text("关闭");
                $(".deletehint").html("");
                if (d.succeed == 1)
                {
                    $(".doc_rd_deleteconfirm_left").attr("class", "doc_rd_deleteconfirm_left_ok");
                    $(".doc_deleteconfirm .doc_rd_deleteconfirm_text").html("删除成功！");
                    setTimeout('$(".doc_deleteconfirm").hide();$(".doc_rd_deleteconfirm_left_ok").attr("class","doc_rd_deleteconfirm_left");', 1000);
                    dirsCache.put(currentDir, null);
                    loadDocList(currentDir);
                }
                else
                {
                    $(".doc_rd_deleteconfirm_left").attr("class", "doc_rd_deleteconfirm_left_error");
                    $(".doc_deleteconfirm .doc_rd_deleteconfirm_text").html(d.msg);
                    setTimeout('$(".doc_deleteconfirm").hide();$(".doc_rd_deleteconfirm_left_error").attr("class","doc_rd_deleteconfirm_left");', 5000);
                }
            });
        });
        //保存目录
        $("#saveDir").bind("click", function() {
            var name = $.trim($(".doc_createDir input").val());
            var $savehint = $(".savehint");
            if (name == "")
            {
                $savehint.html("<div><font style='color:red'>提示：名称不能为空</font></div>");
                $(".doc_createDir input")[0].focus();
                return;
            }
            $savehint.html("<IMG class=loadingimg src=\"/bundles/fafatimewebase/images/loadingsmall.gif\" width=16 height=16>提交中...");
            var editid = $(this).attr("dir_id");
            $.post($(this).attr("url"), "name=" + name + "&parentid=" + currentDir + (editid != "" ? "&id=" + editid : ""), function(d) {
                if (d.succeed == 1)
                {
                    $savehint.html("<div><font style='color:red'>提示：保存成功！</font></div>");
                    if (editid != null && editid != "")
                    {
                        $(".doc_info_name[dir_id='" + editid + "'] A").text(name);
                        $(".doc_createDir").hide();
                        return;
                    }
                    //获取当前创建的目录
                    $.get($docshowlist.attr("dirurl") + "?parentid=" + currentDir + "&dirid=" + d.id, "", function(result) {
                        if (result.data.length > 0) {
                            dirs = result.data.concat(dirsCache.get(currentDir));
                            dirsCache.put(currentDir, dirs);
                            showDoc(filesCache.get(currentDir), dirs);
                        }
                    });
                    $(".doc_createDir").hide();
                }
                else
                    $savehint.html("<div><font style='color:red'>提示：保存失败！</font></div>");
            });
        });
        //分享文件
        var publish_commit = false;
        $("#savePublish").bind("click", function() {
            if (publish_commit)
                return;
            var circle = $(".circle_list_item[select='1']").attr("circle_id");
            if (circle == null)
                circle = "";
            var group = $(".group_list_item[select='1']").attr("group_id");
            if (group == null)
                group = "";
            var publishsavehint = $(".publishsavehint");
            if (circle == "")
            {
                publishsavehint.html("<div><font style='color:red'>提示：无效的圈子</font></div>");
                return;
            }
            publishsavehint.html("<IMG class=loadingimg src=\"/bundles/fafatimewebase/images/loadingsmall.gif\" width=16 height=16>提交中...");
            publish_commit = true;
            $.post($(this).attr("url"), "circleId=" + circle + "&gorupid=" + group + "&fileid=" + $(this).attr("objectid") + "&remark=" + $("#doc_publish_remark").val(), function(d) {
                publish_commit = false;
                if (d.succeed == 1)
                {
                    publishsavehint.html("<IMG src=\"/bundles/fafatimewebase/images/ok.png\" width=16 height=16>&nbsp;分享成功！");
                    setTimeout("$('.doc_publish').hide();$('.publishsavehint').html('')", 2000);
                }
                else
                    publishsavehint.html("<IMG src=\"/bundles/fafatimewebase/images/error.png\" width=16 height=16>&nbsp;分享失败！");
            });
        });
        //初始化分享圈子及群组
        var circle_list = $("#doc_publish_circle_list");
        var group_list = $("#doc_publish_group_list");
        for (var i = 0; i < circles.length; i++)
        {
            var checked = 0;
            if (g_curr_circle_id == circles[i].circle_id)
            {
                $("#spost_to_circle_name").text(circles[i].circle_name);
                for (var j = 0; j < groups.length; j++)
                {
                    if (groups[j].circle_id == g_curr_circle_id)
                        group_list.append("<li group_id='" + groups[j].group_id + "'><a href='javascript:void(0);' title='分享给(" + groups[j].group_name + ")的全部成员' group_value='" + groups[j].group_id + "' ><div class='postto_group icon16'></div><span select='0' class='group_list_item'>" + groups[j].group_name + "</span></a></li>");
                }
                checked = 1;
            }
            circle_list.append("<li circle_id='" + circles[i].circle_id + "'><a href='javascript:void(0);' title='分享给(" + circles[i].circle_name + ")的全部成员' group_value='ALL' onclick='mi_post_to_group_OnClick(this)'><div class='postto_all icon16'></div><span select='" + checked + "' class='circle_list_item'>" + circles[i].circle_name + "</span></a></li>");
        }
        $(".circle_list_item").unbind("click").bind("click", function() {
            var th = $(this);
            $("#spost_to_circle_name").text(th.text());
            $(".circle_list_item").attr("select", "0");
            th.attr("select", "1");
            //填充该圈子群组
            var cid = th.attr("circle_id");
            group_list.html('<li style="background-color:#99CCCC; text-align:center; color:#fff;">分享到 …</li>');
            group_list.append('<li group_id="" ><a href="javascript:void(0);" ><div class="postto_group icon16"></div><span select="1" class="group_list_item">全部成员</span></a></li>');
            $("#spost_to_group_name").text("全部成员");
            for (var j = 0; j < groups.length; j++)
            {
                if (groups[j].circle_id == cid)
                    group_list.append("<li group_id='" + groups[j].group_id + "'><a href='javascript:void(0);' title='分享给(" + groups[j].group_name + ")的全部成员'><div class='postto_group icon16'></div><span class='group_list_item' select='0'>" + groups[j].group_name + "</span></a></li>");
            }
        });
        $(".group_list_item").unbind("click").bind("click", function() {
            $("#spost_to_group_name").text($(this).text());
            $(".group_list_item").attr("select", "0");
            $(this).attr("select", "1");
        });
        $(".docpublishcirclelist").live("mouseover", function(e) {
            var h = $("#doc_publish_circle_list").height();
            h = h < 40 ? 0 : h;
            $(".doc_publish").css("height", (195 + h) + "px");
        });
        $(".docpublishcirclelist").live("mouseout", function(e) {
            if (!checkHover(e, this))
                return;
            var p = $(".doc_publish");
            p.css("height", "195px");
        });
        $(".docpublishgrouplist").live("mouseover", function(e) {
            var h = $("#doc_publish_group_list").height();
            h = h < 40 ? 0 : h;
            $(".doc_publish").css("height", (195 + h) + "px");
        });
        $(".docpublishgrouplist").live("mouseout", function(e) {
            if (!checkHover(e, this))
                return;
            var p = $(".doc_publish");
            p.css("height", "195px");
        });
    }
}

function getCurrentSelectedFileID()
{
    return {fileId: currentSelectedFileID, fileName: currentSelectedFileName};
}

function fileSelect_ext(_url)
{
    var fns = document.getElementById("filedata").value.split("\\");
    fn = fns[fns.length - 1];
    var $upload_hint = $('#mgr_upload_file_ing');
    $upload_hint.html("<img width=16 height=16 src='/bundles/fafatimewebase/images/loadingsmall.gif'><a>上传中</a>");
    $("#mgr_upload_file_sel").hide();
    $upload_hint.show();
    $("#hpost_to_dir").val(currentDir);
    $("form#mgr_upload_file").ajaxSubmit({
        dataType: 'json', //返回的数据类型
        url: _url, //表单的action
        method: 'post',
        success: function(r) {
            if (r.succeed)
            {
                $upload_hint.text('已上传');
                //addFile(fn,r.fileid);
                setTimeout(function() {
                    $('#mgr_upload_file_ing').hide();
                    $('#mgr_upload_file_sel').show()
                }, 3000);
                //获取当前上传的文件
                $.get($docshowlist.attr("fileurl") + "?parentid=" + currentDir + "&fileid=" + r.fileid, "", function(result) {
                    if (result.data.length > 0) {
                        files = result.data.concat(filesCache.get(currentDir));
                        filesCache.put(currentDir, files);
                        showDoc(files, dirsCache.get(currentDir));
                    }
                });
            }
            else
            {
                $upload_hint.html('<font color=red>上传失败</font>');
                setTimeout(function() {
                    $('#mgr_upload_file_ing').hide();
                    $('#mgr_upload_file_sel').show()
                }, 3000);
            }
        },
        error: function(e)
        {
            $upload_hint.html('<font color=red>上传失败</font>');
            setTimeout(function() {
                $('#mgr_upload_file_ing').hide();
                $('#mgr_upload_file_sel').show()
            }, 3000);
        }
    });
}


