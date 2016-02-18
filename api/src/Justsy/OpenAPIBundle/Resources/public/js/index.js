//温馨提示请勿随意修改原有内容,会影像系统的正常使用

//加载文本编辑器
var editor_text_image;
var editor_text_images;
var maxlength = 500; //最多输入字数
var text_maxlength = 500;//最大输入多少字符
var text_image_maxlength = 2048;//图文混合最多输入多少文字
var text_desc_maxlength = 140;
var wordCount = 0;//已输入字数
var fristload = true;//是否首次加载
var textCount = 50;//截取消息内容的字数
var sendtype =3;//1 文字类型 2 图片类型 3 图文类型 4 多图文类型
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
        },
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
        //fillDescAfterUploadImage:true,
        imageTabIndex: 1,
        //fileManagerJson: editor_image_upload,
        uploadJson: editor_image_upload,
        items: editorItems,
        afterChange: function() {
            wordCalculate_textImages(this);
        },
    });
    fristload = false;
});
var wordCalculate = function(zthis) {
    var textCount = zthis.count("text");
    wordCount = text_image_maxlength - textCount;
    $("#text_image_msgcount_massSendTimesLeft").text(wordCount);
    //if (wordCount < 0 && editor_text_image != null) editor_text_image.text(editor_text_image.text().substr(0, text_image_maxlength));
};
var wordCalculate_textImages = function(zthis) {
    var textCount = zthis.count("text");
    wordCount = text_image_maxlength - textCount;
    $("#text_images_msgcount_massSendTimesLeft").text(wordCount);
    //if (wordCount < 0 && editor_text_images != null) editor_text_images.text(editor_text_images.text().substr(0, text_image_maxlength));
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
        divmsgshow("接收对象不能为空",  msg_error_img);
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
$(function() { 
    $("#btnPublish").live("click", function() {
        var zthis = this;
        if (sendclick == 0) {
            if (!checkobj()) return false;
            //if (!checkCustom()) return false;
            //var selReceipt = $("#selReceipt").val(); //回执
            //var selComment = $("#selComment").val(); //评论
            //var custom = $("#txtWriting").val(); //自定义-文字
            //var action = $("#txtAction").val(); //自定义-地址
            //var cctomail = $("#cctomail").attr("checked"); //是否抄送
            //高级选项
            //var msgAdvanced = {receipt:'',comment:'',custom:'',action:'',cctomail:cctomail};
            //if (!IsNullOrEmpty(selReceipt)) msgAdvanced.receipt = selReceipt;
            //if (!IsNullOrEmpty(selComment)) msgAdvanced.comment = selComment;
            //if (!IsNullOrEmpty(custom) && defaultWriting != custom) msgAdvanced.custom = custom;
            //if (!IsNullOrEmpty(action) && defaultAction != action) msgAdvanced.action = action;
            //msgAdvanced = JSON2.stringify(msgAdvanced);
            //发送/接收对象
            var microObj = { microJid: microJid, microName: microName, microNumber: microNumber, microType: microType,microUse:microUse, microOpenid: microOpenid, microGroupId: microGroupId };
            microObj = JSON2.stringify(microObj);
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
                msgObj = JSON2.stringify(msgObj);
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
                msgObj = JSON2.stringify(msgObj);
                //Json Data msgAdvanced: msgAdvanced, 
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
                msgObj = JSON2.stringify(msgObj);
                //Json Data msgAdvanced: msgAdvanced,
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
                msgObj = JSON2.stringify(msgObj);
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
                        if (!IsNullOrEmpty(objmsg.nosend)&&objmsg.nosend!=",") divmsgshow("部分消息发送成功",msg_success_img);
                        else divmsgshow("消息已发送成功",msg_success_img);
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
        $("#dialogTitle").text("确认消息");
        $("#dialogContent").text("确认重置当前所有内容？");
        $("#dialogClear").show();
        $(".dialogBox").show();
    });
    $("#dialogOK").bind("click", function() {
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
            $('#image_form').reset();
            $("#image_img").attr("src", "").parent().hide();
            break;
        case 3:
            $("#text_image_title_preview").text("");
            $("#text_image_date_preview").text("");
            $("#text_image_img_preview").attr("src", "").hide();
            $("#text_image_desc_preview").text("摘要");
            $("#text_image_title").val("");
            $("#text_image_form").reset();
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
            $("#text_images_form").reset();
            $("#text_images_img").attr("src", "").parent().hide();
            if(editor_text_images!=null)editor_text_images.html("");
            $("#text_images_msgcount_massSendTimesLeft").text(text_image_maxlength);
            break;
        }
        $(".dialogBox").hide();
    });
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

//$(function() {
//    if (isFirstLogin) intro();
//});
////每次页面加载时调用即可
//var intro = function() {
//    //这个变量可以用来存取版本号， 系统更新时候改变相应值
//    //var cur_val = 1;
//    //判断函数所接收变量的长度
//    if (arguments.length == 0) {
//        //每个页面设置不同的cookie变量名称，不可以重复，有新版本时，更新cur_val
//        //这里模拟很多网站有新版本更新时才出现一次引导页， 第二次进入进不再出现， 这里有COOKIE来判断
//        //if ($.cookie('intro_cookie_index') == cur_val) {
//        //return;
//        //}
//    }

//    introJs().setOptions({
//        //对应的按钮
//        prevLabel: "上一步",
//        nextLabel: "下一步",
//        skipLabel: "跳过",
//        doneLabel: "立即体验",
//        //对应的数组，顺序出现每一步引导提示
//        steps: [{
//                //第一步引导
//                //这个属性类似于jquery的选择器， 可以通过jquery选择器的方式来选择你需要选中的对象进行指引
//                element: '#sender_line',
//                //这里是每个引导框具体的文字内容，中间可以编写HTML代码
//                intro: '这里可以选择接收对象,并且可以选择对应分组成员哦',
//                //这里可以规定引导框相对于选中对象出现的位置 top,bottom,left,right
//                position: 'bottom'
//            },
//            {
//                element: '#catalogList',
//                intro: '这里可以选择消息类型哦',
//                position: 'right'
//            },
//            {
//                element: '#msgEditArea_Preview',
//                intro: '这里是手机端预览效果哦',
//                position: 'top'
//            },
//            {
//                element: '#msgEditArea',
//                intro: '这里是内容编辑区域哦',
//                position: 'top'
//            },
//            {
//                element: '#btnPublish',
//                intro: '填写好内容,这里可以发布内容哦',
//                position: 'top'
//            },
//            {
//                element: '#msgRecord',
//                intro: '这里可以查看公众号的历史发布记录哦',
//                position: 'bottom'
//            }]
//    }).oncomplete(function() {
//        //点击跳过按钮后执行的事件(这里保存对应的版本号到cookie,并且设置有效期为30天）
//        //$.cookie('intro_cookie_index', cur_val, { expires: 30 });
//    }).onexit(function() {
//        //点击结束按钮后， 执行的事件
//        //$.cookie('intro_cookie_index', cur_val, { expires: 30 });
//    }).onchange(function() {
//        //alert(1);
//    }).start();
//};