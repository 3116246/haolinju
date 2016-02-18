//温馨提示请勿随意修改原有内容,会影像系统的正常使用

//加载文本编辑器
var editor_text_image;
var editor_text_images;
var maxlength = 500; //最多输入字数
var text_maxlength = 1000;//最大输入多少字符
var text_image_maxlength = 2048;//图文混合最多输入多少文字
var text_desc_maxlength = 140;
var wordCount = 0;//已输入字数
var fristload = true;//是否首次加载
var textCount = 50;//截取消息内容的字数
var sendtype =1;//1 文字类型 2 图片类型 3 图文类型 4 多图文类型
var more_image_item_count = 4; //多图文共多少数据
var more_image_edit_index = 1;
var more_image_item_maxcount = 4;
var prompt_msg = "提示消息";
var image_error_msg = "只支持图片(" + AllowExt + ")";
var max_image_size = 5120;
var image_size_error_msg = "图片超过规定的" + max_image_size / 1024 + "MB";
var send_type = { image: "image", textimage: "textimage", textimages: "textimages" };
var msg_uploading_content = "正在上传图片...您可以继续填写其他内容";
var msg_upload_warning= "图片像素超过指定像素范围";
var msg_upload_success = "图片上传成功";
var msg_upload_error = "图片上传失败";
var editorItems = ['undo', 'redo',
    '|', 'formatblock', 'fontname', 'fontsize', 'bold', 'forecolor', 'hilitecolor',
    '|', 'justifyleft', 'justifycenter', 'justifyright', 'justifyfull',
    '|', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent',
    '|', 'subscript', 'superscript',
    '|', 'cut', 'copy', 'paste', 'plainpaste', 'wordpaste',
    '|', 'image', 'quickformat', 'source', 'clearhtml',
    '|', 'preview', 'fullscreen'];
KindEditor.ready(function(K) {
    editor_text_image = K.create('textarea[name="text_image_msgcount"]', {
        width: "410px",
        height: "188px",
        minWidth: 400,
        minHeight: 178,
        resizeType: 0,
        filterMode: false,
        urlType: "domain",
        filePostName: "keImg",
        //fillDescAfterUploadImage:true,
        imageTabIndex: 1,
        //fileManagerJson: editor_image_upload,
        uploadJson: editor_image_upload,
        items: editorItems,
        afterChange: function() {
            wordCalculate(this);
        }
    });
    editor_text_images = K.create('textarea[name="text_images_msgcount"]', {
        width: "410px",
        height: "240px",
        minWidth: 400,
        minHeight: 230,
        resizeType: 0,
        filterMode: false,
        urlType: "domain",
        filePostName: "keImg",
        imageTabIndex: 1,
        uploadJson: editor_image_upload,
        items: editorItems,
        afterChange: function() {
            wordCalculate_textImages(this);
        }
    });
    fristload = false;
});
var wordCalculate = function(zthis) {
    var textCount = zthis.count("text");
    wordCount = text_image_maxlength - textCount;
    $("#text_image_msgcount_massSendTimesLeft").text(wordCount);
};
var wordCalculate_textImages = function(zthis) {
    var textCount = zthis.count("text");
    wordCount = text_image_maxlength - textCount;
    $("#text_images_msgcount_massSendTimesLeft").text(wordCount);
    if (editor_text_images != null) {
        switch (more_image_edit_index) {
        case 1:
            $("#text_images_content_preview").html(editor_text_images.html());
            break;
        case 2:
            $("#text_images_content1_preview").html(editor_text_images.html());
            break;
        case 3:
            $("#text_images_content2_preview").html(editor_text_images.html());
            break;
        case 4:
            $("#text_images_content3_preview").html(editor_text_images.html());
            break;
        }
    }
};

$(function() {
    //var date = getYearMonthDay();
    //$("#text_image_date_preview,#text_images_date_preview").text(date);
    //图片类型删除图片事件
    $("#image_delImg").bind("click", function() {
        //删除图片
        $("#image_img").attr("src", "");
        $("#image_img_preview").attr("src", "").hide();
        $("#image_p").hide();
        upload_msg("#image_upload_load_img", msg_success_img, "图片删除成功");
    });
    //图片类型上传图片事件
    $("#image_uploadFile").bind("change", function() {
        CheckImgExt(this);
        if (IsImg) {
            upload_msg("#image_upload_load_img", msg_load_img, msg_uploading_content);
            
            $("form#image_form").ajaxSubmit({
                url: image_upload + "?maxwidth=600&maxheight=400&type=" + send_type.image,
                success: function(r) {
                    if (typeof(r) != "object") r = eval("(" + r + ")");
                    if (r.success) {
                        if (r.filepath != "") {
                            $("#image_img").attr("src", r.filepath);
                            $("#image_img_preview").attr("src", r.filepath).show();
                            $("#image_p").show();
                            upload_msg("#image_upload_load_img",msg_success_img,r.msg);
                        } else {
                            upload_msg("#image_upload_load_img",msg_error_img,r.msg);
                        }
                    } else {
                       upload_msg("#image_upload_load_img",msg_error_img,r.msg);
                    }
                },
                error: function() {
                   upload_msg("#image_upload_load_img",msg_error_img,r.msg);
                }
            });
        } else {
           upload_msg("#image_upload_load_img", msg_error_img, image_error_msg);
        }
    });
    
    //图文类型删除图片事件
    $("#text_image_delImg").bind("click", function() {
        //删除图片
        $("#text_image_img").attr("src", "");
        $("#text_image_img_preview").attr("src", "").hide();
        $("#text_image_imgArea").hide();
        upload_msg("#text_image_upload_load_img", "", "图片删除成功", true);
    });
    //图文类型上传图片事件
    $("#text_image_uploadFile").bind("change",function() {
        CheckImgExt(this);
        if (IsImg) {
            //if (ImgFileSize > max_image_size) {
            //    upload_msg("#text_image_upload_load_img", msg_error_img, image_size_error_msg);
            //    return;
            //}

            upload_msg("#text_image_upload_load_img", msg_load_img, msg_uploading_content);
            $("form#text_image_form").ajaxSubmit({
                url: image_upload + "?maxwidth=600&maxheight=800&type=" + send_type.textimage,
                success: function(r) {
                    if (typeof(r) != "object") r = eval("(" + r + ")");
                    if (r.success) {
                        if (r.filepath != "") {
                            $("#text_image_img").attr("src", r.filepath);
                            $("#text_image_img_preview").attr("src", r.filepath).show();
                            $("#text_image_imgArea").show();
                            upload_msg("#text_image_upload_load_img", msg_success_img, r.msg);
                        } else {
                            upload_msg("#text_image_upload_load_img", msg_error_img, r.msg);
                        }
                    } else {
                        upload_msg("#text_image_upload_load_img", msg_error_img, r.msg);
                    }
                },
                error: function() {
                    upload_msg("#text_image_upload_load_img", msg_error_img, r.msg);
                }
            });
        } else {
            upload_msg("#text_image_upload_load_img", msg_error_img, image_error_msg);
        }
    });
    //多图文上传控件删除事件
    $("#text_images_delImg").bind("click", function() {
        $("#text_images_imgArea").hide();
        $("#text_images_img").attr("src", "");
        upload_msg("#text_images_upload_load_img", "", "图片删除成功", true);
        switch (more_image_edit_index) {
        case 1: 
            $("#text_images_img_preview").attr("src", "").hide();
            break;
        case 2: 
            $("#text_images_img1_preview").attr("src", "").hide();
            break;
        case 3: 
            $("#text_images_img2_preview").attr("src", "").hide();
            break;
        case 4: 
            $("#text_images_img3_preview").attr("src", "").hide();
            break;
        }
    });
    //多图文上传控件事件
    $("#text_images_uploadfile").bind("change", function() {
        CheckImgExt(this);
        if (IsImg) {
            upload_msg("#text_images_upload_load_img", msg_load_img, msg_uploading_content);
            
            var url;
            switch (more_image_edit_index) {
            case 1:
                url = image_upload + "?maxwidth=600&maxheight=400&type=" + send_type.image;
                break;
            default:
                url = image_upload + "?maxwidth=80&maxheight=80&type=" + send_type.image;
                break;
            }
            $("form#text_images_form").ajaxSubmit({
                url: url,
                success: function(r) {
                    if (typeof(r) != "object") r = eval("(" + r + ")");
                    if (r.success) {
                        if (r.filepath != "") {
                            switch (more_image_edit_index) {
                            case 1:
                                $("#text_images_img_preview").attr("src", r.filepath).show();
                                break;
                            case 2:
                                $("#text_images_img1_preview").attr("src", r.filepath).show();
                                break;
                            case 3:
                                $("#text_images_img2_preview").attr("src", r.filepath).show();
                                break;
                            case 4:
                                $("#text_images_img3_preview").attr("src", r.filepath).show();
                                break;
                            }
                            $("#text_images_img").attr("src", r.filepath);
                            $("#text_images_imgArea").show();
                            upload_msg("#text_images_upload_load_img", msg_success_img, r.msg);
                        } else {
                            upload_msg("#text_images_upload_load_img", msg_error_img, r.msg);
                        }
                    } else {
                        upload_msg("#text_images_upload_load_img", msg_error_img, r.msg);
                    }
                },
                error: function() {
                    upload_msg("#text_images_upload_load_img", msg_error_img, r.msg);
                }
            });
        } else {
            upload_msg("#text_images_upload_load_img", msg_error_img, image_error_msg);
        }
    });
    //图片标题的事件
    $("#image_title").bind("keyup", function() {
        $("#image_title_preview").text($(this).val());
    });
    //提示窗口的事件
    $("#dialogTopClose,#dialogClear").bind("click", function() {
        dialogBoxMsg.hide();
    });
    //图文标题的事件
    $("#text_image_title").bind("keyup",function() {
        $("#text_image_title_preview").text($(this).val());
    });
    //多图文标题的事件
    $("#text_images_title").bind("keyup",function() {
        switch (more_image_edit_index) {
        case 1:
            $("#text_images_upload_preview").val("");
            $("#text_images_title_preview").text($(this).val());
            break;
        case 2:
            $("#text_images_upload1_preview").val("");
            $("#text_images_title1_preview").text($(this).val());
            break;
        case 3:
            $("#text_images_upload2_preview").val("");
            $("#text_images_title2_preview").text($(this).val());
            break;
        case 4:
             $("#text_images_title3_preview").text($(this).val());
            $("#text_images_upload3_preview").val("");
            break;
        }
    });
    //多图文图片的事件
    $("#text_images_img").bind("change", function() {
        switch (more_image_edit_index) {
        case 1:
            $("#text_images_img").attr("src", $("#text_images_img1_preview").attr("src"));
            break;
        case 2:
             $("#text_images_img").attr("src", $("#text_images_img2_preview").attr("src"));
            break;
        case 3:
            $("#text_images_img").attr("src", $("#text_images_img3_preview").attr("src"));
            break;
        case 4:
             $("#text_images_img").attr("src", $("#text_images_img_preview").attr("src"));
            break;
        }
    });
    //多图文选项的事件
    $("#appmsgItem,#appmsgItem1,#appmsgItem2,#appmsgItem3").bind("mousemove", function() {
        $(this).addClass("sub-msg-opr-show");
    });
    //多图文选项的事件
    $("#appmsgItem,#appmsgItem1,#appmsgItem2,#appmsgItem3").bind("mouseout", function() {
        $(this).removeClass("sub-msg-opr-show");
    });
    //文本内容的事件
    $("#text_content").bind("keyup", function() {
        $("#text_content_massSendTimesLeft").text(text_maxlength - $(this).val().length);
        if ($(this).val().length > text_maxlength) {
            divmsgshow("内容已超过指定长度(" + text_maxlength + "),请精简消息内容",  msg_error_img);
        } else {
            divmsgshow( "", "");
        }
    });
    //图文摘要的事件
    $("#text_image_desc").bind("keyup", function() {
        var descCount = text_desc_maxlength - $(this).val().length;
        if (descCount > 0) {
            $("#text_image_desc_massSendTimesLeft").text(descCount);
            $("#text_image_desc_preview").text($(this).val());
        } else {
            $("#text_image_desc_massSendTimesLeft").text(0);
            $(this).val($(this).val().substr(0, text_desc_maxlength));
            $("#text_image_desc_preview").text($(this).val());
        }
    });
    //点击编辑改变事件
    $("#edit_appmsgItem,#edit_appmsgItem1,#edit_appmsgItem2,#edit_appmsgItem3,#ul_edit_appmsgItem,#ul_edit_appmsgItem1").bind("click", function() {
        var id = $(this).attr("id");
        var idIndex = id.substr(id.length - 1, 1);
        switch (idIndex) {
        case "1":
            more_image_edit_index = 2;
            $("#text_images_out").attr("style", "margin-top:190px;");
            $("#text_images_in").attr("style", "margin-top:190px;");
            //$("#div_msg_edit_area").attr("style", "margin-top: 200px");
            editor_text_images.html($doc_html("text_images_content1_preview"));
            $("#text_images_title").val($("#text_images_title1_preview").text());
            $("#text_images_img").attr("src", $("#text_images_img1_preview").attr("src"));
            if ($("#text_images_img1_preview").attr("src") != "") $("#text_images_imgArea").show();
            else $("#text_images_imgArea").hide();
            $("#text_images_maxpx").text(80);
            $("#text_images_minpx").text(80);
            $("#text_images_upload1_preview").val($("#text_images_uploadfile").val());
            break;
        case "2":
            more_image_edit_index = 3;
            $("#text_images_out").attr("style", "margin-top:290px;");
            $("#text_images_in").attr("style", "margin-top:290px;");
            //$("#div_msg_edit_area").attr("style", "margin-top: 295px");
            editor_text_images.html($doc_html("text_images_content2_preview"));
            $("#text_images_title").val($("#text_images_title2_preview").text());
            $("#text_images_img").attr("src", $("#text_images_img2_preview").attr("src"));
            if ($("#text_images_img2_preview").attr("src") != "") $("#text_images_imgArea").show();
            else $("#text_images_imgArea").hide();
            $("#text_images_maxpx").text(80);
            $("#text_images_minpx").text(80);
            $("#text_images_upload2_preview").val($("#text_images_uploadfile").val());
            break;
        case "3":
            more_image_edit_index = 4;
            //第二条隐藏
            if (!$("#appmsgItem2").is(":visible")) {
                $("#text_images_out").attr("style", "margin-top:290px;");
                $("#text_images_in").attr("style", "margin-top:290px;");
            } else {
                $("#text_images_out").attr("style", "margin-top:360px;");
                $("#text_images_in").attr("style", "margin-top:360px;");
            }
            //if (!$("#appmsgItem2").is(":visible")) $("#div_msg_edit_area").attr("style", "margin-top: 295px");
            //else $("#div_msg_edit_area").attr("style", "margin-top: 390px");
            editor_text_images.html($doc_html("text_images_content3_preview"));
            $("#text_images_title").val($("#text_images_title3_preview").text());
            $("#text_images_img").attr("src", $("#text_images_img3_preview").attr("src"));
            if ($("#text_images_img3_preview").attr("src") != "") $("#text_images_imgArea").show();
            else $("#text_images_imgArea").hide();
            $("#text_images_maxpx").text(80);
            $("#text_images_minpx").text(80);
            $("#text_images_upload3_preview").val($("#text_images_uploadfile").val());
            break;
        default:
            more_image_edit_index = 1;
            $("#text_images_out").attr("style", "margin-top:0px;");
            $("#text_images_in").attr("style", "margin-top:0px;");
            //$("#div_msg_edit_area").attr("style", "margin-top: 0px");
            editor_text_images.html($doc_html("text_images_content_preview"));
            $("#text_images_title").val($("#text_images_title_preview").text());
            $("#text_images_img").attr("src", $("#text_images_img_preview").attr("src"));
            if ($("#text_images_img_preview").attr("src") != "") $("#text_images_imgArea").show();
            else $("#text_images_imgArea").hide();
            $("#text_images_maxpx").text(600);
            $("#text_images_minpx").text(400);
            $("#text_images_upload_preview").val($("#text_images_uploadfile").val());
            break;
        }
        $("#text_images_upload_load_img").parent().hide();
    });
    //多图文添加子项事件
    $(".sub-add").bind("click", function() {
        if (more_image_item_count >= more_image_item_maxcount) {
            divmsgshow("无法继续添加,最多不能超过" + more_image_item_maxcount + "条消息",  msg_error_img);
            return;
        } else {
            if (!$("#appmsgItem2").is(":visible")) {
                $("#appmsgItem2").show();//显示第二条数据
                if (more_image_edit_index == 4) {
                    $("#text_images_out").attr("style", "margin-top:360px;");
                    $("#text_images_in").attr("style", "margin-top:360px;");
                }
            } else if (!$("#appmsgItem3").is(":visible")) {
                $("#appmsgItem3").show();//显示第三条数据
            }
            more_image_item_count++;
        }
    });
    //多图文子项删除事件
    $("#del_appmsgItem2,#del_appmsgItem3").bind("click", function() {
        var id = $(this).attr("id");
        var idIndex = id.substr(id.length - 1, 1);
        $("#appmsgItem" + idIndex).hide();
        more_image_item_count--;
        switch (idIndex) {
        case "2":
            //alert("2 more_image_edit_index:" + more_image_edit_index+"visible:"+$("#appmsgItem3").is(":visible"));
            if (more_image_edit_index == 4) {//当前编辑最后一个的时候
                if (!$("#appmsgItem3").is(":visible")) {//判断最后一个是否已经删除
                    editor_text_images.html($doc_html("text_images_content1_preview"));
                    $("#text_images_title").val($("#text_images_title1_preview").text());
                    $("#text_images_img").attr("src", $("#text_images_img1_preview").attr("src"));
                    $("#text_images_out").attr("style", "margin-top:190px;");
                    $("#text_images_in").attr("style", "margin-top:190px;");
                    more_image_edit_index = 2;
                } else {
                    editor_text_images.html($doc_html("text_images_content3_preview"));
                    $("#text_images_title").val($("#text_images_title3_preview").text());
                    $("#text_images_img").attr("src", $("#text_images_img3_preview").attr("src"));
                    $("#text_images_out").attr("style", "margin-top:290px;");
                    $("#text_images_in").attr("style", "margin-top:290px;");
                }
                //$("#div_msg_edit_area").attr("style", "margin-top: 200px");
            } else if (more_image_edit_index ==3){//可能编辑的是第一或第二或第三
                if (!$("#appmsgItem3").is(":visible")) {
                    editor_text_images.html($doc_html("text_images_content1_preview"));
                    $("#text_images_title").val($("#text_images_title1_preview").text());
                    $("#text_images_img").attr("src", $("#text_images_img1_preview").attr("src"));
                    $("#text_images_out").attr("style", "margin-top:190px;");
                    $("#text_images_in").attr("style", "margin-top:190px;");
                    more_image_edit_index = 2;
                } else {
                    editor_text_images.html($doc_html("text_images_content3_preview"));
                    $("#text_images_title").val($("#text_images_title3_preview").text());
                    $("#text_images_img").attr("src", $("#text_images_img3_preview").attr("src"));
                    more_image_edit_index = 4;
                }
            }
            $("#text_images_content2_preview").html("");
            $("#text_images_title2_preview").text("");
            $("#text_images_img2_preview").attr("src", "");
            break;
        case "3":
            //alert("3 more_image_edit_index:" + more_image_edit_index+"visible:"+$("#appmsgItem2").is(":visible"));
            if (more_image_edit_index == 4) {
                if (!$("#appmsgItem2").is(":visible")) {
                    editor_text_images.html($doc_html("text_images_content1_preview"));
                    $("#text_images_title").val($("#text_images_title1_preview").text());
                    $("#text_images_img").attr("src", $("#text_images_img1_preview").attr("src"));
                    $("#text_images_out").attr("style", "margin-top:190px;");
                    $("#text_images_in").attr("style", "margin-top:190px;");
                    more_image_edit_index = 2;
                } else {
                    editor_text_images.html($doc_html("text_images_content2_preview"));
                    $("#text_images_title").val($("#text_images_title2_preview").text());
                    $("#text_images_img").attr("src", $("#text_images_img2_preview").attr("src"));
                    $("#text_images_out").attr("style", "margin-top:290px;");
                    $("#text_images_in").attr("style", "margin-top:290px;");
                    more_image_edit_index = 3;
                }
                //$("#div_msg_edit_area").attr("style", "margin-top: 295px");
            }
            $("#text_images_content3_preview").html("");
            $("#text_images_title3_preview").text("");
            $("#text_images_img3_preview").attr("src", "");
            break;
        }
    });
    //点击导航事件
    $(".a-nav").bind("click", function() {
        $(".catalogList ul li").removeClass("selected");
        $(this).parent().addClass("selected");
        switch ($(this).parent().attr("id")) {
        case "li_text":
            $("#div_text").show();
            $("#div_image").hide();
            $("#div_text_image").hide();
            $("#div_text_images").hide();
            sendtype = 1;
            break;
        case "li_image":
            $("#div_text").hide();
            $("#div_image").show();
            $("#div_text_image").hide();
            $("#div_text_images").hide();
            sendtype = 2;
            break;
        case "li_text_image":
            $("#div_text").hide();
            $("#div_image").hide();
            $("#div_text_image").show();
            $("#div_text_images").hide();
            sendtype = 3;
            break;
        case "li_text_images":
            $("#div_text").hide();
            $("#div_image").hide();
            $("#div_text_image").hide();
            $("#div_text_images").show();
            sendtype = 4;
            break;
        }
    });
});
//检测接收对象
var checkobj = function() {
    if (IsNullOrEmpty(microJid)) {
        divmsgshow("发送对象不能为空",  msg_error_img);
        return false;
    } //else $(".tips").fadeOut("slow");
    return true;
};
//检测接收人员范围
var checkSelUser = function(seluservalue) {
    if (!seluservalue 
        || (seluservalue.zzjg.length == 0 && seluservalue.zjwd.length == 0
            && seluservalue.ryfl.length == 0 && seluservalue.ygh.length == 0)) {
        divmsgshow("请选择要发送的人员范围",  msg_error_img);
        return false;
    } //else $(".tips").fadeOut("slow");
    return true;
};
//检测消息内容
var checkcontent = function(editor) {
    if (IsNullOrEmpty(editor) || editor.isEmpty()) {
        divmsgshow("消息内容不能为空",  msg_error_img);
        return false;
    } //else $(".tips").fadeOut("slow");
    return true;
};
//验证自定义控件是否规范
var checkCustom = function() {
    var custom = $("#txtWriting").val();
    var action = $("#txtAction").val();
    if (!IsNullOrEmpty(custom) && defaultWriting != custom
        && !IsNullOrEmpty(action) && defaultAction != action) {
        var regZh = /^[\u4E00-\u9FA5\uf900-\ufa2d]{2,4}$/;
        if (regZh.test(custom)) { 
            var regUrl = /^(\w+:\/\/)?\w+(\.\w+)+.*$/;
            if (!regUrl.test(action)) {
                divmsgshow("自定义按钮,动作为Web地址或WebService连接地址",  msg_error_img);
                return false;
            }
        } else {
            divmsgshow("自定义按钮,文字为2至4个中文",  msg_error_img);
            return false;
        }
    }
    //$(".tips").fadeOut("slow");
    return true;
};
//显示提示消息
var upload_msg = function(id, url, content, hidden) {
    if (content == "") $(id).parent().hide();
    else {
        $(id).attr("src", url).parent().show();
        $(id).parent().find("span").text(content);
        if (url == "") $(id).hide();
        if (hidden) {
            setTimeout(function() {
                //如果对象是显示就隐藏
                if ($(id).parent().is(":visible")) $(id).parent().hide();
            }, 10000);
        }
    }
};
//time 以秒为单位
var divmsgshow = function(msg, url, time) {
    var id = "#send_msg_img";
    if (msg == "") $(id).parent().hide();
    else {
        $(id).parent().find("span").text(msg);
        $(id).attr("src", url).parent().show();
        if (url == "") $(id).hide();
        if (IsNullOrEmpty(time)) {
            setTimeout(function() {
                //如果对象是显示就隐藏
                if ($(id).parent().is(":visible")) $(id).parent().hide();
                else $(id).parent().show();
            }, 10000);
        }
    }
    return;
};
var dialogBoxMsg = {
    show: function(title, content, type) {
        if (type) {
            $("#dialogTitle").text(title);
            $("#dialogContent").text(content);
            $(".dialogBox").show();
            $("#dialogClear").show();
        } else {
            $("#dialogTitle").text(title);
            $("#dialogContent").text(content);
            $(".dialogBox").show();
            $("#dialogClear").hide();
        }
    },
    hide: function() {
        $(".dialogBox").hide();
    }
};
var sendclick = 0;//记录用于不能频繁提交数据
var msgtokenserver = "认证授权已过期或使用其他方式登录,需要重新认证";
var msgtoken = msgtokenserver + " <a id=\"authenticate\" href=\"javascript:void(0)\">立即认证</a>";
var microObj = {};
$(function() { 
    $("#btnPublish").live("click", function() {
        sendclick = 0;
        var zthis = this;
        if (sendclick == 0) {
            //发送/接收对象
            var ajaxdata = { microObj: microObj, msgObj: null };            
            //1 文字消息 2 图片消息 3 图文消息 4 多图文消息
            switch (sendtype) {
            case 1:
                var title = $("#text_title").val();
                //var contentHtml = $("#text_content").html();
                var contentText = $("#text_content").val();
                if (title == "") {
                    divmsgshow("文字消息,标题不能为空",  msg_error_img);
                    return false;
                }
                if (contentText == "") {
                    divmsgshow("文字消息,内容不能为空",  msg_error_img);
                    return false;
                }
                if (contentText.length > text_maxlength) {
                    divmsgshow("内容已超过指定长度(" + text_maxlength + "),请精简消息内容",  msg_error_img);
                    return false;
                }
                var msgContent = { textmsg: { item: [{ title: title, content: contentText }] } };
                //消息对象
                var msgObj = { type: 'TEXT', msgContent: msgContent, contentHtml: contentText, imgUrl: '', title: title };
                //Json Data msgAdvanced: msgAdvanced, 
                ajaxdata = { microObj: microObj, msgObj: msgObj, r: Math.random() };
                break;
            case 2:
                title = $("#image_title").val();
                var imageurl = $("#image_img").attr("src");
                if (title == "") {
                    divmsgshow("图片消息,请上传图片",  msg_error_img);
                    return false;
                }
                if (imageurl == "") {
                    divmsgshow("图片消息,标题不能为空",  msg_error_img);
                    return false;
                }
                msgContent = { picturemsg: { headitem: { title: title, image: { type: 'URL', value: imageurl }, content: '' } } };
                //消息对象
                msgObj = { type: 'PICTURE', msgContent: msgContent, contentHtml: '', imgUrl: imageurl, title: title };
                ajaxdata = { microObj: microObj, msgObj: msgObj, r: Math.random() };
                break;
            case 3:
                title = $("#text_image_title").val();
                var desc = $("#text_image_desc").val();
                imageurl = $("#text_image_img").attr("src");
                if (title == "") {
                    divmsgshow("图文消息,标题不能为空",  msg_error_img);
                    return false;
                }
                if (desc == "") {
                    divmsgshow("图文消息,摘要不能为空",  msg_error_img);
                    return false;
                }
                if (imageurl == "") {
                    divmsgshow("图文消息,请上传图片",  msg_error_img);
                    return false;
                }
                if (editor_text_image == null || editor_text_image.text() == "") {
                    divmsgshow("图文消息,正文不能为空",  msg_error_img);
                    return false;
                }
                msgContent = { picturemsg: { headitem: { title: title, image: { type: 'URL', value: imageurl }, content: desc, link: '' } } };
                //消息对象
                msgObj = { type: 'PICTURE', msgContent: msgContent, contentHtml: editor_text_image.html(), imgUrl: imageurl, title: title };
                ajaxdata = { microObj: microObj, msgObj: msgObj, r: Math.random() };
                break;
            case 4:
                msgContent = {};
                title = $("#text_images_title_preview").text();
                var title1 = $("#text_images_title1_preview").text();
                var title2 = $("#text_images_title2_preview").text();
                var title3 = $("#text_images_title3_preview").text();
                var imgUrl = $("#text_images_img_preview").attr("src");
                var imgUrl1 = $("#text_images_img1_preview").attr("src");
                var imgUrl2 = $("#text_images_img2_preview").attr("src");
                var imgUrl3 = $("#text_images_img3_preview").attr("src");
                var content = $("#text_images_content_preview").html();
                var content1 = $("#text_images_content1_preview").html();
                var content2 = $("#text_images_content2_preview").html();
                var content3 = $("#text_images_content3_preview").html();
                var contenttext=$("#text_images_content_preview").text();
                var contenttext1 = $("#text_images_content1_preview").text();
                var contenttext2 = $("#text_images_content2_preview").text();
                var contenttext3 = $("#text_images_content3_preview").text();
                if (more_image_item_count == 4) {
                    if (title == "" || title1 == "" || title2 == "" || title3 == "") {
                        divmsgshow("多图文消息,有标题为空,请检查",  msg_error_img);
                        return false;
                    }
                    if (imgUrl == "" || imgUrl1 == "" || imgUrl2 == "" || imgUrl3 == "") {
                        divmsgshow("多图文消息,有图片未上传,请检查",  msg_error_img);
                        return false;
                    }
                    if ($.trim(contenttext) == "" || $.trim(contenttext1) == "" || $.trim(contenttext2) == "" || $.trim(contenttext3) == "") {
                        divmsgshow("多图文消息,有内容为空,请检查",  msg_error_img);
                        return false;
                    }
                    var items = [{ title: title1, image: { type: 'URL', value: imgUrl1 }, link: '', content: content1 },
                        { title: title2, image: { type: 'URL', value: imgUrl2 }, link: '', content: content2 },
                        { title: title3, image: { type: 'URL', value: imgUrl3 }, link: '', content: content3 }];
                    msgContent = { textpicturemsg: { headitem: { title: title, image: { type: 'URL', value: imgUrl }, link: '', content: content }, item: items } };
                } else if (more_image_item_count == 3) {
                    var index = 0;
                    if (title == "") index++;
                    if (title1 == "") index++;
                    if (title2 == "") index++;
                    if (title3 == "") index++;
                    if (index != 1) {
                        divmsgshow("多图文消息,有标题为空,请检查",  msg_error_img);
                        return false;
                    }
                    index = 0;
                    if (imgUrl == "") index++;
                    if (imgUrl1 == "") index++;
                    if (imgUrl2 == "") index++;
                    if (imgUrl3 == "") index++;
                    if (index != 1) {
                        divmsgshow("多图文消息,有图片未上传,请检查",  msg_error_img);
                        return false;
                    }
                    index = 0;
                    if ($.trim(contenttext) == "") index++;
                    if ($.trim(contenttext1) == "") index++;
                    if ($.trim(contenttext2) == "") index++;
                    if ($.trim(contenttext3) == "") index++;
                    if (index != 1) {
                        divmsgshow("多图文消息,有内容为空,请检查",  msg_error_img);
                        return false;
                    }
                    if (imgUrl3 == "") {
                        items = [{ title: title1, image: { type: 'URL', value: imgUrl1 }, link: '', content: content1 },
                            { title: title2, image: { type: 'URL', value: imgUrl2 }, link: '', content: content2 }];
                        msgContent = { textpicturemsg: { headitem: { title: title, image: { type: 'URL', value: imgUrl }, link: '', content: content }, item: items } };
                    } else if (imgUrl2 == "") {
                        items = [{ title: title1, image: { type: 'URL', value: imgUrl1 }, link: '', content: content1 },
                            { title: title3, image: { type: 'URL', value: imgUrl3 }, link: '', content: content3 }];
                        msgContent = { textpicturemsg: { headitem: { title: title, image: { type: 'URL', value: imgUrl }, link: '', content: content }, item: items } };
                    }
                } else if (more_image_item_count == 2) {
                    index = 0;
                    if (title == "") index++;
                    if (title1 == "") index++;
                    if (title2 == "") index++;
                    if (title3 == "") index++;
                    if (index != 2) {
                        divmsgshow("多图文消息,有标题为空,请检查",  msg_error_img);
                        return false;
                    }
                    index = 0;
                    if (imgUrl == "") index++;
                    if (imgUrl1 == "") index++;
                    if (imgUrl2 == "") index++;
                    if (imgUrl3 == "") index++;
                    if (index != 2) {
                        divmsgshow("多图文消息,有图片未上传,请检查",  msg_error_img);
                        return false;
                    }
                    index = 0;
                    if ($.trim(contenttext) == "") index++;
                    if ($.trim(contenttext1) == "") index++;
                    if ($.trim(contenttext2) == "") index++;
                    if ($.trim(contenttext3) == "") index++;
                    if (index != 2) {
                        divmsgshow("多图文消息,有内容为空,请检查",  msg_error_img);
                        return false;
                    }
                    items = [{ title: title1, image: { type: 'URL', value: imgUrl1 }, link: '', content: content1 }];
                    msgContent = { textpicturemsg: { headitem: { title: title, image: { type: 'URL', value: imgUrl }, link: '', content: content }, item: items } };
                }

                //消息对象
                msgObj = { type: 'TEXTPICTURE', msgContent: msgContent, itemcount: more_image_item_count };
                ajaxdata = { microObj: microObj, msgObj: msgObj, r: Math.random() };
                //alert(more_image_item_count);
                break;
            }
            //return false;
            divmsgshow("正在发送消息,请耐心等待", msg_load_img, "111");
            $(zthis).attr("disabled", true);
            sendclick = 1;
            $.post(sendmsg_path, ajaxdata, function(objmsg) {
                try{
                    sendclick = 0;
                    $(zthis).attr("disabled", false);
                    if (IsNullOrEmpty(objmsg)) {
                        divmsgshow("消息发送失败", msg_error_img);
                        return;
                    }
                    if (typeof(objmsg) != "object") objmsg = eval("(" + objmsg + ")");
                    switch (objmsg.returncode) {
                    case "0000":
                        //处理未发送成功的成员
                        if (!IsNullOrEmpty(objmsg.nosend)&&objmsg.nosend!=",") 
                           divmsgshow("部分消息发送成功",msg_success_img);
                        else
                        {
                            divmsgshow("消息已发送成功",msg_success_img);
                        }                            
                        break;
                    case "9999":
                        if (IsNullOrEmpty(objmsg.msg)) divmsgshow("消息已发送,请勿重复发送",  msg_warning_img);
                        else if (!IsNullOrEmpty(objmsg.code)) { //objmsg.msg
                            if (!isAuth) divmsgshow(msgtoken,  msg_error_img);
                            else divmsgshow(msgtokenserver,  msg_error_img);
                            sendclick = 0;
                        } else divmsgshow(objmsg.msg,  msg_error_img);
                        break;
                    }
                }
                catch (Exception) {divmsgshow("消息发送失败", msg_error_img);}
            }).fail(function(){
                divmsgshow("消息发送超时", msg_error_img);
            });
        } else divmsgshow("正在发送消息,请耐心等待",  msg_load_img);
    });

    $("#btnClear").bind("click", function() {
//        $("#dialogTitle").text("确认消息");
//        $("#dialogContent").text("确认重置当前所有内容？");
//        $("#dialogClear").show();
//        $(".dialogBox").show();
      showDialog.Query("","确定要重置当前所有内容吗?");
  	  showDialog.callback=function(result){
  	  	 if(result=="Yes"){
  	  	 	 confirm_dialog();
  	  	 }
  	  };
    });
    function confirm_dialog(){
      switch (sendtype) {
      case 1:
          $("#text_title").val("");
          $("#text_content").val("");
          $("#text_content_massSendTimesLeft").text(text_maxlength);
          break;
      case 2:
          $("#image_title_preview").text("标题");
          $("#image_img_preview").attr("src", "").hide();
          $("#image_title").val("");
          $('#image_form')[0].reset();
          $("#image_img").attr("src", "").parent().hide();
          break;
      case 3:
          $("#text_image_title_preview").text("");
          $("#text_image_date_preview").text("");
          $("#text_image_img_preview").attr("src", "").hide();
          $("#text_image_desc_preview").text("摘要");
          $("#text_image_title").val("");
          $("#text_image_form")[0].reset();
          $("#text_image_img").attr("src", "").parent().hide();
          $("#text_image_desc").val("");
          $("#text_image_desc_massSendTimesLeft").text(text_desc_maxlength);
          if(editor_text_image!=null)editor_text_image.html("");
          $("#text_image_msgcount_massSendTimesLeft").text(text_image_maxlength);
          break;
      case 4:
          $("#text_images_date_preview").text("");
          $("#text_images_title_preview").text('');
          $("#text_images_img_preview").attr("src", "").hide();
          $("#text_images_upload_preview").val('');
          $("#text_images_content_preview").text('');
          $("#text_images_title1_preview").text('');
          $("#text_images_img1_preview").attr("src", "").hide();
          $("#text_images_upload1_preview").val('');
          $("#text_images_content1_preview").text('');
          $("#text_images_title2_preview").text('');
          $("#text_images_img2_preview").attr("src", "").hide();
          $("#text_images_upload2_preview").val('');
          $("#text_images_content2_preview").text('');
          $("#text_images_title3_preview").text('');
          $("#text_images_img3_preview").attr("src", "").hide();
          $("#text_images_upload3_preview").val('');
          $("#text_images_content3_preview").text('');
          $("#text_images_title").val("");
          $("#text_images_form")[0].reset();
          $("#text_images_img").attr("src", "").parent().hide();
          if(editor_text_images!=null)editor_text_images.html("");
          $("#text_images_msgcount_massSendTimesLeft").text(text_image_maxlength);
          break;
      }
      $(".dialogBox").hide();
    }
});

//设置默认文字
var defaultWriting = "2至4个中文字符";
var defaultAction = "Web地址或WebService连接地址";
$(function() {
    $("#spword").text(maxlength);
    $("#txtWriting").val(defaultWriting);
    $("#txtAction").val(defaultAction);
    //注册按钮事件
    //delegateEvent(Array("txtAction", "txtWriting"), { focus: checkfocus, blur: checkblur, });
});
//判断内容处理内容
var checkfocus = function(zthis) {
    switch (zthis.id) {
    case "txtWriting":
        if (defaultWriting == ($("#txtWriting").val()))
            $("#txtWriting").val("");
        break;
    case "txtAction":
        if (defaultAction == ($("#txtAction").val()))
            $("#txtAction").val("");
        break;
    }
};
//判断内容处理内容
var checkblur = function(zthis) {
    switch (zthis.id) {
    case "txtWriting":
        if (IsNullOrEmpty($("#txtWriting").val()))
            $("#txtWriting").val(defaultWriting);
        break;
    case "txtAction":
        if (IsNullOrEmpty($("#txtAction").val()))
            $("#txtAction").val(defaultAction);
        break;
    }
};

//历史数据
var HistoryData = {
	pushmonth:null,
	search_url:"",
	detail_url:"",
	pagerecord:15,
	view:function(){
		$("#search_push").show();
		$("#content_push").hide();
		if($("#search_date").children().length==0){
			 var dates = this.pushmonth;
			 var html = Array();
			 html.push("<option value=''>全部日期</option>");
			 var maxdate = "";
			 if ( dates!=null && dates.length>0){
		 	  for(var i=0;i<dates.length;i++){
		 	  	if ( i==0 ) maxdate = dates[i].val;
		 	  	html.push("<option "+(i==0 ? "selected='selected'":"")+" value='"+dates[i].val+"'>"+dates[i].s_date+"</option>");
		 	  }
		   }
		   $("#search_date").html(html.join(""));
		   if ( maxdate!="")
		 	    HistoryData.Search(1);
		}
	},
	Search:function(pageindex){
		var parameter = {"date":$("#search_date").val(),"title":$("#msg_title").val(),"pageindex":pageindex,"pagerecord":this.pagerecord };
		$.post(HistoryData.search_url,parameter,function(calldata){
			 pageControl.status = false;
			 var html = Array();
			 if ( calldata.data!=null && calldata.data.length>0){
			 	 for(var i=0;i<calldata.data.length;i++){
			 	 	 row = calldata.data[i];
			 	   html.push("<div class='mb_table_tr' pushid='"+row.id+"'>");
			 	   html.push("  <span class='mb_rightLing sendname' style='width:100px;'>"+row.sendname+"</span>");
			 	   html.push("  <span class='mb_rightLing date' style='width:130px;'>"+row.send_datetime+"</span>");
			 	   html.push("  <span class='mb_rightLing title' style='width: 435px; text-align: left; padding-left: 5px;'>"+row.msg_title+"</span>");
			 	   html.push("  <span class='mb_rightLing' style='width:70px;'>"+row.state+"</span>");
			 	   html.push("  <span class='mb_rightLing' style='width:90px;'>"+row.sendtype+"</span>");
			 	   html.push("  <img onclick='HistoryData.viewDetail(this);'  src='/bundles/fafatimewebase/images/message_mode.jpg' style='float:left;cursor:pointer;margin:2px 0px 0px 25px;' title='查看详细'>");
			 	   html.push("</div>");
			 	 }
				 //分页管理
			   if (pageindex==1){
					 var record = parseInt(calldata.recordcount);
					 if (record>0 && record > HistoryData.pagerecord){
						 pageControl.every = HistoryData.pagerecord;
						 pageControl.maxIndex = 10;
						 pageControl.status = false;
						 pageControl.control = $("#search_push .mb_table");
						 pageControl.totalIndex = Math.ceil(record /HistoryData.pagerecord);
						 pageControl.container = $("#search_push .push_search_page");
						 pageControl.callback = function(index){
						 	  HistoryData.Search(index);
						 };
						 pageControl.setting();
						 $("#search_push .push_search_page").show();
					 }
					 else{
						 $("#search_push .push_search_page").hide();
					 }
			   }
			 }
			 else{
			 	 html.push("<div class='search_empty'>未查询到符合条件的数据记录</div>");
			 	 $(".push_search_page").hide();
			 }
			 $("#table_content").html(html.join(""));
		});
	},
	viewDetail:function(ev){
		$("#push_detail #basic_info").html("&nbsp;");
		$("#push_detail #pushcontent").html("&nbsp;");
		$("#push_detail #getstaff").html("&nbsp;");
		$('#push_detail').show();
		var html = Array();
		var curObj = $(ev).parents(".mb_table_tr");
		var pushid = curObj.attr("pushid");
		var date = curObj.find(".date").text();
		var sendname = curObj.find(".sendname").text();
		var title = curObj.find(".title").text();		
		html.push("<div class='detail_row'><span>推送时间：</span><span class='field_color'>"+date+"</span>");
		html.push("<span style='float:right;margin-right:5px;'> <span>发送对象：</span><span class='field_color'>" + sendname+"</span></span></div>");
		html.push("<div class='detail_row'><span>推送标题：</span><span class='field_color'>"+title+"</span></div>");
		
		$("#push_detail #basic_info").html(html.join(""));
		$.post(HistoryData.detail_url,{"pushid":pushid},function(data){
			 html = [];
			 if ( data.success){
			 	  var table = data.data.push;
			 	  if ( table!=null && table.length>0){
			 	  	var row = table[0];
			 	  	if ( table[0].msg_type=="text"){
			 	  		 html.push("<div class='detail_row' style='margin-bottom:0px;'><span style='float:left;'>推送内容：</span></div>");
			 	  		 html.push("<div style='float:left;width:100%;color:black;min-height:50px;padding-left:40px;padding-right:10px;'>"+table[0].msg_text+"</div>");
			 	  	}
			 	  	else if ( table[0].msg_type=="picture" || (table.length==1 && table[0].msg_type=="textpicture" )){
			 	  		if ( row.msg_summary!=""){
			 	  		  html.push("<div class='detail_row' style='margin-bottom:0px;'><span style='float:left;'>推送摘要：</span></div>");
			 	  		  html.push("<div style='float:left;width:100%;color:black;padding-left:40px;padding-right:10px;'>"+row.msg_summary+"</div>");
			 	  		}
			 	  		if ( row.msg_content!=""){
			 	  		  html.push("<div class='detail_row' style='margin-bottom:0px;'><span style='float:left;'>推送正文：</span></div>");
			 	  		  html.push("<div style='float:left;width:100%;color:black;padding-left:40px;padding-right:10px;'>"+row.msg_content+"</div>");
			 	  		}
			 	  		if ( row.msg_img_url!=""){
			 	  			html.push("<div class='detail_row' style='margin-bottom:0px;'><span style='float:left;'>封面图片：</span></div>");
			 	  		  html.push("<img style='float:left;margin-left:20px;max-height:200px;' src='"+row.msg_img_url+"'>");
			 	  		}			 	  		
			 	  	}
			 	  	else if ( table[0].msg_type=="textpicture"){
			 	  		var html2 = Array();
			 	  		html.push("<div style='float:right;position:relative;'>");
			 	  		for(var i=0;i<table.length;i++){
			 	  			row = table[i];
			 	  			html.push("<span onclick='HistoryData.selectdetail(this)' class='label_page"+(i==0 ? " label_page_active":"")+"'>"+(i+1)+"</span>");
			 	  			
			 	  			html2.push("<div class='textpicture' page='"+(i+1)+"' style='float:left;margin-top:5px;" + (i>0 ? "display:none;":"")+"'>");
				 	  		if (row.msg_summary!=null && row.msg_summary!=""){
				 	  		  html2.push("<div class='detail_row' style='margin-bottom:0px;'><span style='float:left;'>推送摘要：</span></div>");
				 	  		  html2.push("<div style='float:left;width:100%;color:black;padding-left:40px;padding-right:10px;'>"+row.msg_summary+"</div>");
				 	  		}
				 	  		if (row.msg_content!=null && row.msg_content!=""){
				 	  		  html2.push("<div class='detail_row' style='margin-bottom:0px;'><span style='float:left;'>推送正文：</span></div>");
				 	  		  html2.push("<div style='float:left;width:100%;color:black;padding-left:40px;padding-right:10px;'>"+row.msg_content+"</div>");
				 	  		}
				 	  		if (row.msg_img_url!=null && row.msg_img_url!=""){
				 	  			html2.push("<div class='detail_row' style='margin-bottom:0px;'><span style='float:left;'>封面图片：</span></div>");
				 	  		  html2.push("<img style='float:left;margin-left:20px;max-height:120px;' src='"+row.msg_img_url+"'>");
				 	  		}
				 	  		html2.push("</div>");
			 	  	  }
			 	  	  html.push("</div>");
			 	  	  html.push(html2.join(""));
			 	  	}
			 	  	$("#push_detail #pushcontent").html(html.join(""));
			 	  }
			 	  html = [];
			 	  table = data.data.staff;
			 	  if ( table!=null && table.length>0){
			 	  	html.push("<span style='float:left;'>接收人员：</span>");
			 	  	var person = "";
			 	  	for(var i=0;i<table.length;i++){
			 	  		 person += table[i].nick_name+(i+1==table.length ? "":"、");
			 	  	}
			 	  	html.push("<span class='field_color'>"+person+"</span>");
			 	  	$("#push_detail #getstaff").html(html.join(""));
			 	  }
			 }
			 else{
			 }
		});
	},
	selectdetail:function(ev){
		var num = $(ev).text();
		$(".label_page").removeClass("label_page_active");
		$(ev).addClass("label_page_active");
		$("#pushcontent .textpicture").hide();
		$("#pushcontent>div[page='"+num+"']").show();
	}
};