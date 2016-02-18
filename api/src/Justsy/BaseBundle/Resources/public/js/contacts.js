//参数对象
var Init = {
   url_addType:null,
   url_addContact:null,
   url_hint:null,
   url_search:null,
   url_request:null,
   url_depart:null,
   url_remind:null,
   page_every:30,
   depart_data:null,
   url_message:null,
   propelling_staff_search:null,
   propelling_message_search:null,
   propelling_message_delete:null,   
   loadding_image:"/bundles/fafatimewebase/images/loading.gif",
   default_staff_image:"/bundles/fafatimewebase/images/tx.jpg",
   hint_image : "/bundles/fafatimewebase/images/prompt.png",
   error_image: "/bundles/fafatimewebase/images/errow.gif",
   remind_staff_ico: "/bundles/fafatimewebase/images/remind_staff_ico.png",
   remind_birthday_ico: "/bundles/fafatimewebase/images/remind_birthday_ico.png",
   remind_email_ico: "/bundles/fafatimewebase/images/remind_email_ico.png"
};

//菜单项
$(".navigation_menu").live("click",function(){
  var index = $(this).index();
  $(".navigation_menu_area").css("cursor","pointer");
  $(this.children).css("cursor","default")
  
  
  $(".navigation_menu_select").attr("class","navigation_menu");           
  $(this).attr("class","navigation_menu_select");
  if ( $(this).next().length>0)
    $(this).next().css("border-left","none");
  $(this).css("border-left","1px solid #cacbcd");
  if (index==1)
  {
  	$("#Left1").show();
  	$("#Left2").hide();
  	$("#Left3").hide();
  	$("#Left4").hide();  	
  }
  else if (index==4)
  {
  	$("#Left1").hide();
  	$("#Left2").hide();
  	$("#Left3").hide();
  	$("#Left4").show();
  	if ($("#Left4>form").length==0)
  	  $("#Left4").load(Init.url_message,function(){
  	  	var date = new Date();
  	  	var year = date.getFullYear();
  	  	var html ="<option value=''></option>"+
  	  	          "<option value='"+year+"'>"+year+"</option>"+
  	  	          "<option value='"+(year+1)+"'>"+(year+1)+"</option>";
  	  	$("#propelling_year").append(html);
  	  	$("#propelling_year").val(year);
        var month = date.getMonth()+1;
  	  	$("#propelling_month").val(month);
  	  	html = "<option value=''></option>";
  	  	var day = new Date(year,month,0).getDate();
  	  	for(var i=1;i<=day;i++)
  	  	{
  	  		html+="<option value='"+i+"'>"+i+"</option>";
  	  	}
  	  	$("#propelling_day").append(html);
  	  	$("#propelling_day").val(date.getDate());
  	  });
  }  
});

$("#propelling_month").live("change",function(){
	alert("1");
});

$(".propelling_add_staffs").live("click",function(){
	$("#select_staff_box").show();
	 if( $(".propelling_ul").children().length==0)
	 {
	    if (Init.depart_data != null)
	  	{
	  	  load_propelling();
	  	}
	  	else
	    {
	    	  var i=0;
	    	  i = setInterval(function(){
	    		  if (Init.depart_data!=null)
	    		  {
	    		 	 	  load_propelling();
	    		 	 	  clearInterval(i);
	    		 	 };		    		 	
	    		},1000);
	    }
	 }
	 $(this).hide();
});

//加载推送消息选择成员列表
function load_propelling()
{
	 if( $(".propelling_ul").children().length>0) return;
	 //加载分类
	 $(".propelling_ul").html("");
	 var html = "<li class='propelling_li_title' state='group'><span style='float:left;'>分&nbsp;&nbsp;&nbsp;&nbsp;组</span><span class='contact_details_down' style='margin-top:2px;margin-left:5px;float:left;'></span></li>";
 	 $(".propelling_ul").append(html);
 	 var group = $("#sub_group>.contact_subgroup_tag");
 	 var id="";
 	 for(var i=0;i<group.length;i++)
 	 {
 	 	  id = group.eq(i).attr("keyid");
 	 	  html="<li class='propelling_selected_li_group' keyid='"+id+"' type=1 state=0>"+group.eq(i).text()+"</li>";
 	 	  $(".propelling_ul").append(html); 	 	  
 	 } 	 
 	 //加载组织部门
 	 var html = "<li class='propelling_li_title' state='dept'><span style='float:left;'>组织部门</span><span class='contact_details_up' style='margin-top:2px;margin-left:5px;float:left;'></span></li>";
 	 $(".propelling_ul").append(html);
 	 var data = Init.depart_data;
 	 if ( data!=null)
 	 {
	 	 for(var j=0;j<data.length;j++)
	 	 {
	 	 	  var rows = data[j];
		 	  if ( rows.parent==null)
		 	  {
	 	 	    html="<li class='propelling_selected_li_dept' style='display:none;' keyid='"+rows.dept_id+"' type=2 >"+rows.dept_name+"</li>";
	 	 	    $(".propelling_ul").append(html);
		 	  }
		 	  else
		 	  {
		  	 	 id = rows.parentid;
		  	 	 if ( $("#depart_"+id).length==0)
		  	 	 {
		  	 	    html="<li class='propelling_selected_li_dept' style='display:none;' id='depart_"+id+"' keyid='"+rows.dept_id+"' type=2 >"+rows.parent+"</li>";
		          $(".propelling_ul").append(html);
		          
		          html="<li class='propelling_selected_li_dept_children' style='display:none;' keyid='"+rows.dept_id+"' type=2 >"+rows.dept_name+"</li>";
		          $(".propelling_ul").append(html);		          
		  	 	 }
		  	 	 else
		  	 	 {
		  	 	 	  html="<li class='propelling_selected_li_dept_children' style='display:none;' keyid='"+rows.dept_id+"' type=2 >"+rows.dept_name+"</li>";
		          $(".propelling_ul").append(html);
		  	 	 }
		 	  }
	 	 }
   }
 	 //字母
 	 html = "";
 	 for(var k=65;k<91;k=k+2)
   {
   	  var code = String.fromCharCode(k);
      html +="<span class='propelling_lettery' keyid='"+code+"' type=3>"+code+"</span><span style='float:left;'>|</span>";
 	    code = String.fromCharCode(k+1);
 	    html +="<span class='propelling_lettery' keyid='"+code+"' type=3>"+code+"</span>";
   }
   $(".propelling_lettery_area").append(html);
   
   $(".propelling_lettery").live("click",function(){
   	 var keyid = $(this).attr("keyid");
   	 var keyword=$(this).text();
   	 $(".propelling_lettery").css("font-weight","normal");
   	 $(this).css("font-weight","bold");
   	 SearchPropellingStaff(3,keyid,keyword);
   });
   
   html="";
   
   $(".propelling_li_title").live("click",function(){
   	 var state = $(this).attr("state");
   	 $(".propelling_selected_li_"+state).toggle();
   	 if ( state=="dept")
   	   $(".propelling_selected_li_"+state+"_children").toggle();
   	 
   	 $(".propelling_li_title").css({"background-color":"#B5E7EB","color":"#5A5A5A"});
   	 $(this).css({"background-color":"#00AAD8","color":"#ffffff"});
   	 
   	 if ($(".propelling_selected_li_"+state).is(":visible"))
   	   $(this.children[1]).attr("class","contact_details_down"); 	  
   	 else
   	 	 $(this.children[1]).attr("class","contact_details_up");
   });
   
   
   $(".propelling_selected_li_dept,.propelling_selected_li_dept_children,.propelling_selected_li_group").live("click",function() { 	 
   	 var keyid = $(this).attr("keyid");
   	 var type =  $(this).attr("type");
   	 var keyword=$(this).text();
   	 if (type==1)   	 
   	 	 $(".propelling_selected_li_group").removeAttr("style");
   	 else
   	 {
   	 	 $(".propelling_selected_li_dept").removeAttr("style");
   	   $(".propelling_selected_li_dept_children").removeAttr("style");
   	 }
   	 $(this).css("background-color","#EEE8AA");
   	 SearchPropellingStaff(type,keyid,keyword);
   });
   //第一次就开始加载
   $(".propelling_selected_li_group:first").attr("state",1);
   SearchPropellingStaff(1,$(".propelling_selected_li_group:first").attr("keyid"),"");
   $(".propelling_selected_li_group:first").css("background-color","#EEE8AA");
   
};

//查询推送消息人员
function SearchPropellingStaff(type,id,keyword)
{
	   id = id==null ?"":id;
	   keyword = keyword==null?"":keyword;
     var para = "type="+type+"&id=" + id + "&keyword=" + keyword;
     var pageid = "page_"+type+id+keyword;
     if ($("#"+pageid).length==1)
     {
     	  $(".propelling_staff_area>div:visible").hide();
     	  $("#"+pageid).show();
     	  return;
     }
     else
     {
     	 $(".propelling_staff_area>div:visible").hide();
     	 $("#propelling_loadding").show();
     }     
     para = encodeURI(para);
     $.getJSON(Init.propelling_staff_search,para, function(data){
     	  $("#propelling_loadding").hide();
     	  $(".propelling_staff_area>div:visible").hide();
        var html = "<div id='"+pageid+"' class='propelling_staff_page'></div>";
        $(".propelling_staff_area").append(html);
        if (data.table.length==0)
        {        	
          html = "<div class='propelling_loadding' style='display:block;'><img src='"+Init.hint_image+"' /><span>未检索到用户信息！</span></div>";
        	$("#"+pageid).append(html);
        	return;
        }
        var image = Init.default_staff_image;
        var obj = new Array();
        for(var i=0;i<data.table.length;i++)
        {
        	 var row=data.table[i];
        	 var letter = row.letter;
        	 if ($.inArray(letter, obj)==-1)
        	 {
        	 	 obj.push(letter);
        	 	 var letterid = pageid+letter;        	 	 
        	   html = "<div id='letter"+letterid+"' style='float:left;width:100%;' >"+
        	           " <div class='propelling_staff_page_letter'>  <label class='staff_letter'>"+
        	           "    <input type='checkbox' /><span >"+letter+"</span></label></div>"+
        	           "</div>";
        	   $("#"+pageid).append(html);
        	 }        	 
        	 if ( row.img !="")
        	    image = row.img;
        	 
        	 html = "<label class='propelling_staff_info'><input type='checkbox' class='check_propelling_staff' id='"+row.id+"' nick_name='"+row.name+"' staff_type='"+row.staff_type+"' /><img src='" + image + "'/><span style='float:left;'>"+row.name+"</span><span style='float:right;'>"+
        	          (row.staff_type==1 ? "wefafa账号":"联系人")+"</span></label>";
        	 $("#letter"+letterid).append(html);
        }
        
        $(".staff_letter").live("click",function(){
        	 var checkstate = $(this).find("input").attr("checked");
        	 var control = $(this).parent().parent().find(".propelling_staff_info");
        	 control = control.find("input");
        	 control.attr("checked",checkstate=="checked"?true:false);
        	 $("#staff_number").text($(".check_propelling_staff:[checked='checked']").length);
        });
        
        $(".propelling_staff_info").live("click",function(){
        	 var state = $(this).find("input").attr("checked");
        	 if (state==null || state=="")
        	   $(this.parentNode).find(".staff_letter>input").attr("checked",false);        	   
        	 
        	 $("#staff_number").text($(".check_propelling_staff:[checked='checked']").length);
        });
        
        $("#checkbox_allchecked").live("click",function(){
        	 var state = $(this).find("input").attr("checked");
        	 $(".check_propelling_staff:visible").attr("checked",state=="checked"?true:false);
        	 $(".staff_letter>input:visible").attr("checked",state=="checked"?true:false);
        	 $("#staff_number").text($(".check_propelling_staff:[checked='checked']").length);
        });
        
        $("#btnCheckedStaff").live("click",function(){
        	var staffid = "";
        	var html = "";
        	var staff_jid_type = "";
        	var control = $(".check_propelling_staff");
        	for(var i=0;i<control.length;i++)
        	{
        		 if (control[i].checked)
        		 {
        		   staffid += control[i].id+",";
        		   html += "<span class='propelling_selected_staff'><span style='float:left;'>"+ control[i].getAttribute("nick_name") + 
        		           "</span><span class='propelling_remove_staff' title='移除用户' jid='"+control[i].id+"' staff_type='"+control[i].getAttribute("staff_type")+"'>、</span></span>";
        		   staff_jid_type +=control[i].getAttribute("staff_type")+",";
        		 }
        	}
        	//去掉最后一个分号
        	if (staffid.charAt(staffid.length-1)==",")
        	  staffid = staffid.substr(0,staffid.length-1);       
        	if (staff_jid_type.charAt(staff_jid_type.length-1)==",")
        	  staff_jid_type = staff_jid_type.substr(0,staff_jid_type.length-1);
        	          	
        	$(".select_staff_box").hide();        	
        	$("#remind_staffs").html(html);        	
        	$("#staff_jid").val(staffid);
        	$("#staff_jid_type").val(staff_jid_type);
        	$("#propelling_add_staffs").show();
        });
        
        $(".propelling_selected_staff").live("mouseover",function(){
          var ctl =	$(this.children).eq(1);
          ctl.css("color","red");
          ctl.text("×");
        });

        $(".propelling_selected_staff").live("mouseout",function(){
        	var ctl =	$(this.children).eq(1);
          ctl.css("color","#5A5A5A");
          //ctl.("title","移除用户");
          ctl.text("、");
        });
        //移除接收人员
        $(".propelling_remove_staff").live("click",function(){
        	      	 
        	 var staffids = $("#staff_jid").val().split(",");
        	 var types =   $("#staff_jid_type").val().split(",");
        	 var jid = this.getAttribute("jid");
        	 var new_staffid = "";
        	 var new_type = "";
        	 for(var i=0;i<staffids.length;i++)
        	 {
        	 	 if(staffids[i]!=jid)
        	 	 {
        	 	 	 new_staffid += staffids[i]+",";
        	 	 	 new_type += types[i]+",";        	 	 	         	 	 	          	 	 	
        	 	 }
        	 }
        	//去掉最后一个分号
        	if (new_staffid.charAt(new_staffid.length-1)==",")
        	  new_staffid = new_staffid.substr(0,new_staffid.length-1);         	 
        	if (new_type.charAt(new_type.length-1)==",")
        	  new_type = new_type.substr(0,new_type.length-1);            	  
        	$("#staff_jid").val(new_staffid);
        	$("#staff_jid_type").val(new_type);
        	$(this).parent().remove();
        });
     });
};


//切换分组事件
$("#add_subgroup,#btnCancel_subgroup").live("click",function(){
  $("#panel_add_group").toggle();
  if ($("#panel_add_group").css("display")=="block")
  {
  	var area = $("#add_subgroup").offset();  	
    $("#panel_add_group").css({ "left":area.left+"px","top":area.top+"px"});
    
    if ($("#sub_group .contact_subgroup_tag,.contact_subgroup_tag_select").length>=20)
    {
    	 $("#panel_add_group").html("<span style='display:block;width:100%;height:120px;line-height:120px;text-align:center;'><img src='"+Init.hint_image+"' style='margin-top:-2px;' />&nbsp;您已达到最大分组个数(20)。</span>");
    	 setTimeout("$('#panel_add_group').hide();$('#add_subgroup').hide();",3000);
    	 $("#Image_AddType").hide();
    	 $("#select_type").css("width","234px");
    }
    $("#panel_add_group").css({"background-color":"#E6E6E6","border-color":"#FFFFFF #CCCCCC #767877","color":"#5A5A5A"});
    $(".text_AddType").focus();
  }
});


$("#Image_AddType").live("click",function(){
   if ( $("#panel_add_group").css("display")=="none")
   {
       $("#panel_add_group").css({"background-color": "#005C95","color":"#FFFFFF","border":"1px solid #FFFFFF","left":"609px","top":"167px","display":"block"});
       $(".text_AddType").focus();
   }
   else
       $("#panel_add_group").css("display","none");
});

$(".text_AddType").live("keypress",function(event){
   $("#error1").text(""); 
});


//添加分组记录
$("#btnSave_subgroup").live("click",function(){
  var typename = $(".text_AddType").val();
  if ( typename=="")
  {
    $("#error1").text("请输入分组名称！");
    $(".text_AddType").focus();
    return;
  }
  var membergroup =$("#sub_group .contact_subgroup_tag,.contact_subgroup_tag_select");	
  for(var i=0;i<membergroup.length;i++)
  {
    if (membergroup.eq(i).text()==typename)
    {
      $("#error1").text("已存在分组名称！");
      $(".text_AddType").focus();
      return;
    }
  }
  $("#loading1").show();
  $("#error1").text("正在提交,请稍候...");
  $("#from_AddType").ajaxSubmit({
    type:'post',
    dataType:'json',
    url:Init.url_addType,
    success:function(data){
      $("#loading1").hide();
      if(data.s=="1")
      { 
        $(".text_AddType").val("");
        $("#error1").text("");
        $("#panel_add_group").hide();
        var html = "<span class='contact_subgroup_tag' type='1' keyid='" + data.typeid + "'>" + typename + "</span><span style='float:left;'>|</span>";
        $(html).insertBefore($("#add_subgroup"));
        //添加到下拉列表框
        html = "<option value='"+data.typeid+"'>"+typename+"</option>";
        $("#select_type").append(html);
      }
      else
      {
        $("#error1").text(data.message);
        $(".panel_add_group").focus();
      }
    }
  });
});

//添加联系人
$("#btnAdd_Staff,#btnCancel_contact").live("click",function(){
   $("#panel_add_group").hide();
   $("#add_staff").toggle();
});

//编辑联系人
$("#btnSave_contact").live("click",function(){
	
  if( $("#btnSave_contact").attr("state")=="0") return;
  var control = $(".member_contact>input[name='addr_name']");
  if ( control.val()=="")
  {
     $(".member_add_contact_hint>:last").text("请输入姓名");
     control.focus();
     return;
  }
  if ( $(".member_contact>input[name='addr_phone']").val()=="" &&  $(".member_contact>input[name='addr_mobile']").val()=="")
  {
     $(".member_add_contact_hint>:last").text("电话和手机必须输入一项");
      $(".member_contact>input[name='addr_phone']").focus();
     return;
  }
  if ( $("#select_type").val()=="")
  {
     $(".member_add_contact_hint>:last").text("请选择所属分组");
     $(".member_contact>input[name='addr_phone']").focus();
     return;  	
  }
  $(".member_add_contact_hint>:first").show();
  $(".member_add_contact_hint>:last").show();
  $(".member_add_contact_hint>:last").text("正在提交");
  $("#btnSave_contact").attr("state",0);
  
  $("#edit_Form").ajaxSubmit({
    type:'post',
    dataType:'json',
    url:Init.url_addContact+'?editType=add',
    success:function(d){
      $("#btnSave_contact").attr("state",1);
      if(d.s=="1")
      {
         $(".member_add_contact_hint>:first").hide();        
         $(".member_add_contact_hint>:last").text("添加联系人成功");
         setTimeout(function() {
        	 $('.member_add_contact_hint>:last').text('');
        	 if (document.getElementById("is_remind").checked)
        	 {
        	 	  $("#add_staff").hide();
        	 	  $("#remind_year").val("-1");
        	 	  $("#remind_month").val($("#birthday_month").val());
        	 	  $("#remind_day").val($("#birthday_day").val());
        	 	  $(".remindContent").text("Happy Birthday to You！");
        	 	  $("#down_hour").val("09");
        	 	  $("#down_minute").val("00");
     	 	      $("#remind_staffid").val(d.id);
     	 	      $("#remind_type").val(0);
     	 	      $("#remind_category").val(1);     	 	      
     	 	      $(".member_contact>input").val("");
              $("#birthday_month").val("");
              SetMonthDay("");
        	 	  $(".remind_box").show();
        	 }
        	 else
        	 {
        	 	  $(".member_contact>input").val("");
              $("#birthday_month").val("");
              SetMonthDay("");
              $(".member_contact>input:first").focus();
        	 }
        },2000);
      }
      else
      {
        $(".member_add_contact_hint>:first").hide();        
        $(".member_add_contact_hint>:last").text(d.message);
      }
    }
  });
});

//联系人回车事件
$(".member_contact>input").live("keypress",function(event){
   $(".member_add_contact_hint span").text("");
   if ( event.keyCode==13)
   {
      var control = $(".member_contact>input");
      for(var i=0;i<control.length;i++)
      {
         if ( $(control.eq(i)).attr("name") == this.name)
         {
            if ( i+1 != control.length)
            {
              control.eq(i+1).focus();
              break;
            }  
         }         
      }
   }
});


//展开折叠联系人详细信息
$("#contact_details>span").live("click",function(){
   
   if ( $("#contact_details>span:first").attr("class")=="contact_details_up") 
   {
      $("#contact_details>span:first").attr("class","contact_details_down"); 
      $("#add_staff").css("height","345px");
   }
   else
   {
     $("#contact_details>span:first").attr("class","contact_details_up");
     $("#add_staff").css("height","260px");
   }
   $("#contact_details_area").toggle();
   
});

//加载字母列表项及事件
function Loading_Letter()
{
  var html = "";
  for(var i=65;i<91;i++)
  {
    var code = String.fromCharCode(i);
    html += "<span class='contact_subgroup_tag' style='font-size:14px;width:14px;text-align:center;' type='3' keyId='"+code+"'>"+code+"</span><span style='float:left;'>|</span>";
  }
  $("#member_letter").html(html);
}

//选择月
$("#birthday_month").live("change",function(){
   var month = $("#birthday_month").val();
   if ( month=="")
   {
     $("#birthday_day").html("");
     document.getElementById("is_remind").checked=false;
   }     
   else
     SetMonthDay(month);
});

//设置某月对应的天数
function SetMonthDay(month)
{
  var day = 31;
  if ( month ==2)
     day = 28;
  else if ( month == 4 || month == 6 || month == 9 || month == 11)
     day = 30;
  if ( month=="")
  {
  	$("#birthday_day").html("");
  	return;
  }
  //天数基数
  var len = $("#birthday_day").children().length-1;
  if ( len==day) return;
  len = len==-1?0:len;
  if ( len == 0 )
  {
  	 $("#birthday_day").append("<option value=''></option>");
     for(var i=1;i<=day;i++)
     {
        $("#birthday_day").append("<option value='"+(i<10?"0"+i:i)+"'>"+i+"日</option>");
     }
  }
  else if ( len<day)
  {
     for(var j=len+1;j<=day;j++)
     {
        $("#birthday_day").append("<option value='"+j+"'>"+j+"日</option>");
     }
  }
  else if ( len > day)
  {
     for(var k=day+1;k<=len;k++)
     {
       $("#birthday_day option[value='"+k+"']").remove();
     }
  }
};

//获得提醒信息
function GetRemindHint(limit)
{  
   $.getJSON(Init.url_hint,"top="+limit,function(data){
   	   var html = "";
       if ( data.length == 0 )
       {
       	   $("#displayRemind").hide();
       	   $("#remind_area_div>img").attr("src",Init.hint_image);
       	   $("#remind_area_div>img").css({"width":"26px","height":"26px","margin-top":"32px"});
       	   $("#remind_area_div>span").text("目前还没有提醒信息");
       	   html = "<span class='contact_button' id='btnAddHint' style='width:80px;margin-left:10px;margin-top:5px;'> ✚&nbsp;添加提醒</span>";
       	   $("#remind_area_div").append(html);
       	   $("#btnAddHint").live("click",function(){
       	   	$(".remind_box").toggle();       	   	  
       	   });
        	 return;
       }
       $("#remind_area_div").html("");       
       var userimage="";
       html = "<div style='float:left;width:225px;'>";
       for(var i=0;i< data.length;i++)
       {
       	  if ( data[i].img != null)
       	     userimage = data[i].img;
       	  else if (data[i].category=="0")
       	  	userimage = Init.remind_staff_ico;
       	  else if (data[i].category=="1")
       	    userimage = Init.remind_birthday_ico;
          html += "<div class='remind_show_Area'>"+
                 "  <img src='"+userimage+"' onerror=\"javascript:this.src='" + Init.default_staff_image + "'\" />"+
                 "  <div style='float:left;height:35px;width:140px;'>"+
                 "    <span class='remind_show_Area_content' title='" + data[i].remindcontent+"' >"+data[i].remindcontent+"</span>"+
                 "    <span style='float:left;height:15px;line-height:15px;font-size:10px;color:#7B7B7B;'>"+data[i].remind_date+"</span>"+
                 "  </div>"+
                 "  <span class='remind_show_Area_name'>"+data[i].nick_name+"</span>"+
                 "</div> ";
       }
       html +="</div>";
       if ( data.length>limit)
       	  html += "<span style='float:right;width:50px;margin-top:0px;letter-spacing:4px;' class='member_more'>[更多]</span>";    
       $("#remind_area_div").html(html);
      
   });
};

//获得人脉请求
function GetContactRequest(type)
{
   $.getJSON(Init.url_request,"type="+type,function(data){
       if ( data.length == 0 )
       {
       	 $(".personal_relations_area>div>img").attr("src",Init.hint_image);
       	 $(".personal_relations_area>div>img").css({"width":"26px","height":"26px"});
       	 $(".personal_relations_area>div>span").text("目前没有人脉请求");
       	 return;
       }
       else
       {
       	  $(".personal_relations_area").html("");
       	  var html = "";
       	  for(var i=0;i<data.length;i++)
       	  {
       	  	 if (type==0 && i==5) break;
       	  	 var rows = data[i];
       	  	 if(parseInt(rows.state)==1)
       	  	 {
       	  	 	  html +="<span>&gt;&nbsp;请求与<a class='employee_name' login_account='" + rows.recver + "'>" + rows.recver_name + "</a>成为人脉</span>";
       	  	 }
       	  	 else
       	  	 {
       	  	 	  html +="<span>&gt;&nbsp;<a class='employee_name' login_account='"+ rows.sender + "'>" + rows.sender_name + "</a>请求与你成为人脉</span>";
       	  	 }
       	  }
       	  if ( data.length>5)
       	  {
       	  	 html += "<hr class='member_line_dashed' style='width:210px;margin-top:8px;margin-bottom:1px;'>" + 
       	  	         "<span style='float:right;width:50px;margin-top:0px;letter-spacing:4px;' class='member_more'>[更多]</span>";
       	  }
       }
       $(".personal_relations_area").append(html);
   });
};


//按分组标志查询
$(".contact_subgroup_tag").live("click",function(){
	 if ( $(this).parent().attr("class") != "contact_children_depart")
	 {
	     $(".contact_children_depart").hide();
	     if($(".contact_dept_down").length>0)
	       $(".contact_dept_down").attr("class","topmenu_app_triangle contact_dept_down");
	 }
   if ( $(".contact_subgroup_tag_select").length>0)
      $(".contact_subgroup_tag_select").attr("class","contact_subgroup_tag");
   $(this).attr("class","contact_subgroup_tag_select");
   
   var type = parseInt($(this).attr("type"));
   var id = $(this).attr("keyId");
   var keyword = $(this).text();   
   SearchContact(type,id,keyword,1);      
});

//按查询类型、id、关键字获得记录数
function GetRecordCound(type,id,keyword,recordcount)
{
	 $("#pageControl>div:visible").hide();
	 $("#staff_count2>span:visible").hide();
	 type = parseInt(type);
	 keyword = keyword==null?"":keyword;
	 var spanId = "roster_"+type+id;
	 var pageId = "pageControl_"+type+id;
	 if ( type==4)
	 {
	    spanId = spanId + "_" + keyword;
	    pageId = pageId + "_" + keyword;
   }
   var countControl =  $("#"+spanId);
   if ( countControl.length==1)
   {
   	  countControl.show();
   	  $("#"+pageId).show();
   	  return;
   }
   if ( recordcount == null ) return;
   var show_count="";
   if ( type==0)
     show_count = "联系人";
   else if ( type==4)
     show_count = "搜索结果";
   else
     show_count = keyword;   
   $("#staff_count2").append("<span id='"+spanId + "' state='0'>"+ show_count +"</span>");             
       recordcount = parseInt(recordcount);
      if ( $("#"+pageId).length==0)
      	 $("#pageControl").append("<div id='"+pageId+"' type='"+type+"' keyid='"+id+"' keyword='"+keyword+"'></div>");
      if ( type == 0 && $("#staff_count1").text()=="")
        $("#staff_count1").text(recordcount);
        
    var pageSize = Math.ceil(recordcount / Init.page_every);
    $("#"+spanId).text(show_count+"("+recordcount+")");
    if (pageSize==1 ) return;         
    TCircles.add(pageSize,$("#"+pageId),5,'all');    
};

//查询联系人员
function SearchContact(type,id,keyword,index)
{
	   $("#searchContent>div:visible").hide();
	   keyword = keyword==null?"":keyword;	   
	   var pageId = "page_"+type+id+index;
	   if ( type ==4)
	      pageId = pageId + keyword;
	   if ( $("#"+pageId).length==1)
	   {
	   	  $("#"+pageId).show();
	   	  GetRecordCound(type,id,keyword,null);
	   	  return;
	   }
     $("#search_lodding").show();
     var para = "type="+type+"&id=" + id + "&keyword=" + keyword + "&every="+Init.page_every+"&index="+index;
     para = encodeURI(para);  
     $.getJSON(Init.url_search,para, function(data){
        $("#search_lodding").hide();
        var rows = null;
        var html = "";
        var headerImage="";
        var table = data.table;
        if ( index == 1)
          GetRecordCound(type,id,keyword,data.recordcount);
        
        if ( table.length == 0)
        {
        	 $("#searchContent>div:visible").hide();
        	 html = "<div id='"+pageId+"' >" +
        	       " <div class='contact_search_hint' style='display: block;'>"+
        	       "  <img style='width:32px;height:32px;' src='"+Init.hint_image+"' /> <span>没有搜索到联系人员！</span>"+
   	 	           "</div></div>";
        	 $("#searchContent").append(html);
        	 return;
        }
        html = "<div id='"+pageId+"' >";        
        for(var i=0;i<table.length;i++)
        {
            rows = table[i];
            var level = rows.level;
            level = level==null ?"":level;
            if ( rows.headerImage ==null)
               headerImage = Init.default_staff_image;
            else
               headerImage = rows.headerImage;
            var nick_html = "<span class='contact_staff_basic_nick'>"+ rows.nick_name +"</span>";
            if (type==4)
            {
            	  nick_html = "<span style='color:red;width:auto;float:none;'>"+keyword+"</span>";            	  
            	  nick_html = rows.nick_name.replace(keyword,nick_html);            	  
                nick_html = "<span class='contact_staff_basic_nick'>"+ nick_html +"</span>";
            }
            
            html += "<div class='contact_staff_area'>"+
                    "  <div class='contact_staff_head'> "+
                    "    <img src='"+ headerImage +"' onerror=\"javascript:this.src='" + Init.default_staff_image + "'\" />"+
                    "    <div class='contact_staff_basic'> "+nick_html+
                    "      <span style='width:40px;height:14px;line-height:14px;"+(level==""?"background-color:#FAFBFD;":"")+"' class='fafa_level_0 contact_staff_level'>"+level+"</span>"+
                    "      <span>"+ (rows.dept_name==null?"":rows.dept_name) +"</span>"+
                    "      <span>"+ (rows.duty==null?"":rows.duty)+"</span>"+
                    "    </div>"+
                    "  </div> "+
                    "<div class='contact_staff_desc'><div style='font-weight:bold;'><span class='contact_staff_desc_enterprise'> "+(rows.eshortname==null?"&nbsp;":rows.eshortname)+"</span></div>"+
                    " <div>"+
                    "   <span class='contact_member_phone'></span><span class='member_contact_content'>"+(rows.work_phone==null?"":rows.work_phone)+"</span>"+
                    "</div>"+
                    "<div>"+
                    "  <span class='contact_member_mobile'></span><span class='member_contact_content'>"+(rows.mobile==null?"":rows.mobile)+"</span>"+
                    "</div>"+
                    "<div>"+
                    "  <span class='contact_member_email'></span><span class='member_contact_content'>"+ (rows.login_account==null?"":rows.login_account)+"</span>"+
                    "</div></div></div>";
        }
        html = html +"</div>";
        //判断并隐藏之前的
        if ($("#searchContent>div:visible").length >0)
        {
        	$("#searchContent>div:visible").hide();
        	$("#contact_paging .contact_page_select").attr("class","contact_page_default");
        	$("#page_"+index).attr("class","contact_page_select");
        }
        $("#searchContent").append(html);
   });
};



function GetDepartMent()
{
	 $.getJSON(Init.url_depart,function(data){
	 	  Init.depart_data = data;
	 	  var html = "";
	 	  if ( data.length==0)
	 	  {
	 	     $("#group_department>img").attr("src",Init.hint_image);
	 	     $("#group_department>span").text("未获得组织部门数据");
	 	  }
	 	  else
	 	  {
	 	  	 $("#group_department").html("");
	 	  }
	 	  for(var i=0;i<data.length;i++)
	 	  {
	 	  	 var rows = data[i];
	 	  	 if ( rows.parent==null)
	 	  	 {         	 	
         	 	html = "<div class='contact_depart_default' style='position:relative;'><span class='contact_subgroup_tag' type='2' keyId='"+rows.dept_id+"'>"+rows.dept_name+"</span></div><span style='float:left;'>|</span>";
	 	  	 	 	$("#group_department").append(html);
	 	  	 }
	 	  	 else
	 	  	 {
	 	  	 	 var elementid = rows.parentid;
	 	  	 	 if ( $("#d_"+elementid).length==0)
	 	  	 	 {
	 	  	 	 	  html = "<div style='float:left;position:relative;z-index:50;' class='group_depart'><div class='contact_depart_default'><span class='contact_subgroup_tag' type='2' keyId='"+elementid+"'>"+rows.parent+"</span><span class='topmenu_app_triangle contact_dept_down' divId='" + "d_"+elementid + "'></span></div>"+
	 	  	 	 	         "<div class='contact_depart_content' id='" + "d_"+elementid +"'><span class='contact_subgroup_tag' type='2' keyId='"+rows.dept_id+"'>"+rows.dept_name+"</span><span style='float:left;'>|</span></div>"+
	 	  	 	 	         "</div><span style='float: left; position: relative;'>|</span>";	 	  	 	 	         
	 	  	 	 	  $("#group_department").append(html);
	 	  	 	 }
	 	  	 	 else
	 	  	 	 {
	 	  	 	 	  html = "<span class='contact_subgroup_tag' type='2' keyId='"+rows.dept_id+"'>"+rows.dept_name+"</span><span style='float:left;'>|</span>";
	 	  	 	 	  $("#d_"+elementid).append(html);
	 	  	 	 }	 	  	 	 
	 	  	 	 
	 	  	 	 $(".group_depart").live("mouseover",function(){
	 	  	 	 	  $(this).find("div").children(".contact_dept_down").attr("class","topmenu_app_triangle contact_dept_up");
              $(this.firstChild).attr("class","contact_depart_select");
              var _left = $(this).offset().left+"px";
              _left = $(this).width()+"px";
              //$(this.lastChild).css("margin-left","-"+_left);
              $(this.lastChild).show();
	 	  	 	 });
	 	  	 	 
	 	  	 	 $(".group_depart").live("mouseout",function(){
	 	  	 	 	  $(this).find("div").children(".contact_dept_up").attr("class","topmenu_app_triangle contact_dept_down");
              $(this.firstChild).attr("class","contact_depart_default");
              $(this.lastChild).hide();
	 	  	 	 });	 	  	 	 
	 	  	 }	  	
	 	  }
	 });
}

//设置分布
window.TCircles=new Array();
TCircles.add=function(pagecount,container,pagesize,classify){
	var thepagination=new Pagination();
	thepagination.init({pagecount:pagecount,container:container,pagesize:pagesize});
	thepagination.setDefault(1,null);
	thepagination.addPageClick(loadpage);
	thepagination.addPreClick(loadpage);
	thepagination.addNexClick(loadpage);
	this.push({'classify':classify,'pagination':thepagination});
};
window.loadedpages=[];
loadedpages.contains=function(pageindex){
	var classify=getCurrC().attr('classify');
	for(var i=0;i<this.length;i++){
		if(this[i].classify==classify){
			if(this[i].pages.contains(pageindex))
				return true;
		}
	}
	return false;
}
loadedpages.del=function(classify){
	var j=-1;
	for(var i=0;i<this.length;i++){
		if(this[i].classify==classify){
			j=i;
			break;
		}
	}
	if(j> -1)
		this.splice(j,1);
}
loadedpages.add=function(pageindex){
	var classify=getCurrC().attr('classify');
	for(var i=0;i<this.length;i++){
		if(this[i].classify==classify){
			this[i].pages.push(pageindex);
		}
	}
}
//查询数据
function loadpage(pageindex){

   var type = this.container[0].getAttribute("type");
   var keyid = this.container[0].getAttribute("keyid");
   var keyword = this.container[0].getAttribute("keyword");
	 SearchContact(type,keyid,keyword,pageindex);
}
function getParas(pageindex)
{
	var currC=getCurrC();
	searchby=currC.attr('classify')=='search'?$("#searchCondition").attr('keyword'):'';
	classify=(currC.attr('classify')=='all' || currC.attr('classify')=='search')?'':currC.attr('classify');
	return {'pageindex':pageindex,'classify':classify,'searchby':searchby};
}

Array.prototype.contains=function(v){
	for(var i=0;i<this.length;i++){
		if(v==this[i])
			return true;
	}
	return false;
}

var Pagination=function(){
	this.pagecount=0;//页数
	this.pagesize=Init.page_every;
	this.maxsize=7;
	this.container=null;//父容器
	this.pageclickevent=[];//页码点击事件
	this.preclickevent=[];//上一页点击事件
	this.nexclickevent=[];//下一页点击事件
	this.pointclickevent=[];//
	this.currpage=0;
	this.point=[];
}
Pagination.prototype={
	setCount:function(pagecount){
		this.pagecount=pagecount;
	},
	setContainer:function(container){
		this.container=container;
	},
	addPageClick:function(func){
		this.pageclickevent.push(func);
	},
	addPreClick:function(func){
		this.preclickevent.push(func);
	},
	addNexClick:function(func){
		this.nexclickevent.push(func);
	},
	addPointClick:function(func){
		this.pointclickevent.push(func);
	},
	pageClick:function(pageindex){
		for(var i=0;i<this.pageclickevent.length;i++){
			this.pageclickevent[i].apply(this,[pageindex]);
		}
	},
	preClick:function(pageindex){
		for(var i=0;i<this.preclickevent.length;i++){
			this.preclickevent[i].apply(this,[pageindex]);
		}
	},
	nexClick:function(pageindex){
		for(var i=0;i<this.nexclickevent.length;i++){
			this.nexclickevent[i].apply(this,[pageindex]);
		}
	},
	pointClick:function(){
		
	},
	setDefault:function(pageindex,callback){
		var thisindex=pageindex;
		if(thisindex==this.currpage)return;
		this.currpage=thisindex;
		this.setCurrCss();
		if(thisindex==this.pagecount || thisindex==1){
			this.setNoPageCss();
		}
		if(callback!=null)
		callback(thisindex);
	},
	setNoPageCss:function(){
		var nopageCss={
			'background-color':'#c8c8c8',
			'text-shadow':'0 1px 0 #ffffff',
			'color':'#000'
		};
		var pageCss={
		    'background-color': '#00AAD5',		    
			  'color':'#FFF'
		};
		$("span[pagination='pre'],span[pagination='nex']").css(pageCss);
		if ( this.currpage==1)
		{ 
			 $("span[pagination='pre']").css(nopageCss);
			 $("span[pagination='nex']").css("text-shadow","-1px -1px 0 #000000");
		}
		else
		{
		   $("span[pagination='nex']").css(nopageCss);
		   $("span[pagination='pre']").css("text-shadow","-1px -1px 0 #000000");
		   var xy=null;
		}
			
		
	},
	setCurrCss:function(){
		var currCss={
			'background-color':'#00AAD5',
			'text-shadow':'-1px -1px 0 #000000',
			'color':'#FFF'
		};
		var notcurrCss={
			'background-color':'#e6e6e6',
			'text-shadow':'none',
			'color':'#000'
		};
		$("span[pagination='page']").css(notcurrCss);
		$("span[pagination='page'][pageindex='"+this.currpage+"']").css(currCss);
	},
	resetPageList:function(){
		var $p=$(this.container).find(".pagev[pageindex='"+this.currpage+"']").parent();
			var $nex=$p.next();
			var $pre=$p.prev();
			var d='';
			var notarr=[1,this.pagecount,this.currpage];
			if($nex.attr('class')=='pointc'){
				d='nex';
				var html=[];
				for(var i=1;i<=3;i++){
					notarr.push(parseInt(this.currpage)+i);
					html.push("<span class='pagec'><span  class='pagev' style='height:20px;line-height:21px;width:12px;' pagination='page' pageindex='"+(parseInt(this.currpage)+i).toString()+"'>"+(parseInt(this.currpage)+i).toString()+"</span></span>");
					if($(".pagev[pageindex='"+(parseInt(this.currpage)+i+1).toString()+"']").length){
						$nex.remove();
						break;
					}
				}
				$p.after(html.join(''));
			}
			if($pre.attr('class')=='pointc'){
				d='pre';
				var html=[];
				for(var i=1;i<=3;i++){
					notarr.push(parseInt(this.currpage)-i);
					html.unshift("<span class='pagec'><span  class='pagev' style='width:12px;' pagination='page' pageindex='"+(parseInt(this.currpage)-i).toString()+"'>"+(parseInt(this.currpage)-i).toString()+"</span></span>");
					if($(".pagev[pageindex='"+(parseInt(this.currpage)-i-1).toString()+"']").length){
						$pre.remove();
						break;
					}
				}
				$p.before(html.join(''));
			}
			if(d!=''){
				var n=$(this.container).find(".pagev[pageindex]").length-1;
				var $lastpage=$(this.container).find(".pagev[pageindex]:last");
				while(n>-1 && $(this.container).find(".pagev").length>this.maxsize){
					$v=$(this.container).find(".pagev[pageindex]:eq("+n+")");
					if(!notarr.contains(parseInt($v.attr('pageindex')))){
						$v.parent().remove();
					}
					n--;
				}
				var $pages=$(this.container).find(".pagev[pageindex]");
				for(var i=0;i<$pages.length;i++){
					var thisindex=parseInt($($pages[i]).attr('pageindex'));
					var nexindex=parseInt($($pages[i+1]).attr('pageindex'));
					if(thisindex+1!=nexindex){
						if($($pages[i]).parent().next().attr('class')=='pagec')
						$($pages[i]).parent().after("<span class='pointc'><span class='pointv' pagination='point'>●●●</span></span>");
					}
				}
				var pointvs=$(this.container).find("span.pointc");
				for(var i=0;i<pointvs.length;i++){
					if($(pointvs[i]).next().attr('class')=='pointc'){	
						$(pointvs[i]).remove();
						i++;
					}
				}
			}
	},
	init:function(paras){
		this.pagecount=paras.pagecount;//页数
		this.container=paras.container;//父容器
		if(paras.pagesize)
			this.pagesize=paras.pagesize;
		var html=[];
		if(this.pagecount>1){
			html.push("<span class='operc'><span class='pagev' pagination='pre'>上一页</span></span>");
		}
		for(var i=1;i<=this.pagecount;i++){
			if(i==this.pagesize && this.pagecount>this.pagesize)
			{
				html.push("<span class='pointc'><span class='pointv' pagination='point'>●●●</span></span>");
				i=this.pagecount-1;
				this.point.push(i);
			}
			else
				html.push("<span class='pagec'><span  class='pagev' style='height:20px;line-height:21px;width:12px;' pagination='page' pageindex='"+i+"'>"+i+"</span></span>");
		}
		if(this.pagecount>1){
			html.push("<span style='margin-left:5px;'  class='operc'><span  class='pagev' pagination='nex'>下一页</span></span>");
		}
		var $pages=$(html.join(''));
		$(this.container).append($pages);
		var _obj=this;
		$(this.container).find("span[pagination='point']").live('click',function(){
			
		});
		$(this.container).find("span[pagination='page']").live('click',function(){
			var $this=$(this);
			var thisindex=$this.attr('pageindex');
			if(thisindex==_obj.currpage)return;
			_obj.currpage=thisindex;
			_obj.setCurrCss();
			if(thisindex==_obj.pagecount || thisindex==1){
				_obj.setNoPageCss();
			}
			_obj.pageClick(thisindex);
			_obj.resetPageList();
		});
		$(this.container).find("span[pagination='pre']").live('click',function(){
			var $this=$(this);
			if(_obj.currpage==1)return;
			_obj.currpage--;
			_obj.setCurrCss();
			if(_obj.currpage==1){
				_obj.setNoPageCss();
			}
			_obj.preClick(_obj.currpage);
			_obj.resetPageList();
		});
		$(this.container).find("span[pagination='nex']").live('click',function(){
			var $this=$(this);
			if(_obj.currpage==_obj.pagecount)return;
			_obj.currpage++;
			_obj.setCurrCss();
			if(_obj.currpage==_obj.pagecount){
				_obj.setNoPageCss();
			}
			_obj.nexClick(_obj.currpage);
			_obj.resetPageList();
		});
	}
}


$("#bntSearch").live("click",function(){
	 
	 $(".contact_subgroup_tag_select").attr("class","contact_subgroup_tag");
	 var keyword = $(".text_sarch").val();
	 if ( keyword=="")
	 	 SearchContact(0,0,keyword,1);
	 else
	 	 SearchContact(4,0,keyword,1);   
});

$(".text_sarch").live("keypress",function(event){
	 
	 if ( event.keyCode == 13)
	 {
	 	 $(".contact_subgroup_tag_select").attr("class","contact_subgroup_tag");
	   var keyword = $(".text_sarch").val();
	   GetRecordCound(4,0,keyword);	 
	   SearchContact(4,0,keyword,1);
	 }
});


//验证是否是合法邮箱地址
function validEmail(mail)
{
var reg = /^[a-zA-Z0-9]+[a-zA-Z0-9_-]*(\.[a-zA-Z0-9_-]+)*@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)*\.([a-zA-Z]+){2,}$/;
return reg.test(mail);
};

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++提醒
$("#close_remind").live("click",function(){
	 $(".remind_box").hide();
});


$("#displayRemind").live("click",function(){
	 $(".remind_box").toggle();
});

$("#remind_type_date").live("change",function(){
	$(this.parentNode).attr("class","remind_type_select")
	$(this.parentNode).next().attr("class","remind_type_default");
	$("#remind_week").hide();
	$("#remind_date").show();	
});


$("#remind_type_week").live("change",function(){
	$(this.parentNode).attr("class","remind_type_select")
	$(this.parentNode).prev().attr("class","remind_type_default");
	$("#remind_date").hide();
	$("#remind_week").show();
});

$("#remind_repeat_more").live("change",function(){
	if ($("#remind_type_date").attr("checked")=="checked")
	{
		if ($("#remind_year").val()!="-1" && $("#remind_month").val()!="-1" && $("#remind_day").val()!="-1" && $("#down_hour").val()!="-1" )
		{
			$("#select_hint").show();
			setTimeout(function(){$("#select_hint").hide();},5000);
			$("#remind_repeat_single").attr("checked",true);
			return;
		}
	}	
	$(this.parentNode).attr("class","remind_type_select")
	$(this.parentNode).prev().attr("class","remind_type_default");
});

$("#remind_repeat_single").live("change",function(){
	$(this.parentNode).attr("class","remind_type_select")
	$(this.parentNode).next().attr("class","remind_type_default");
});

$("#remind_month").live("change",function(){
	var month = $(this).val();
	var day = 0;
  if ( month =="01" || month =="03" || month =="05" || month =="07" || month =="08" || month =="10" || month =="12")
    day=31;
  else if (month=="02")
     day = 28;
  else
  	day = 31;
  
  $("#remind_day").html("");
  var html = "<option value='-1'></option>";
  for(var i=1;i<=day;i++)
  {
  	 var temp = i<10 ? "0"+i:i;
  	 html += "<option value='"+temp+"'>"+i+"</option>";
  }
  $("#remind_day").html(html);
});

$(".remind_button_edit").live("click",function(){
	if ( $("#down_hour").val()=="-1" && $("#down_minute").val()=="-1")
	{
		$("#img_remind_error").show();
		$("#span_remind_error").text("请选择时或分，至少选择一项！");		  	
    setTimeout(function() { 
    	 $("#img_remind_error").hide();
    	 $("#span_remind_error").html("&nbsp;");
    	 },5000);    	 
		return;
	}
	
	if($(".remindContent").val()=="")
	{
		$("#img_remind_error").show();
		$("#span_remind_error").text("请输入提示内容！");		  	
    setTimeout(function() { 
    	 $("#img_remind_error").hide();
    	 $("#span_remind_error").html("&nbsp;");
    	 $("#span_remind_error").show();
    	 },5000);
		return;
	}	
	//周
	var weeks = "";
	if ($("#remind_type_week").attr("checked")=="checked")
	{
		 var weekArray = $("#remind_week input");		 
		 for(var i=0;i<weekArray.length;i++)
		 {
		 	 if (weekArray[i].checked)
		 	   weeks += weekArray[i].value+",";
		 }
		 if (weeks!="")
		  weeks = weeks.substr(0,weeks.length-1);
	}
	$("#week").val(weeks);
	//重复方式	
	if ($("#remind_repeat_more").attr("checked")=="checked")
	  $("#remind_type").val(1);
	else
		$("#remind_type").val(0);
  //通知方式
  var send_type = "";
  if ($("#send_email").attr("checked")=="checked")
    send_type="0";
  if ($("#send_phone").attr("checked")=="checked")
    send_type+=",1";
  if ($("#send_wefafa").attr("checked")=="checked")
    send_type+=",2";
  if ( send_type=="")
  {
  	$("#img_remind_error").show();
    $("#span_remind_error").text("请选择提醒类型");
    setTimeout(function() { 
    	 $("#img_remind_error").hide();
    	 $("#span_remind_error").html("&nbsp;");
    	 },5000);
		return;
  }
  if ($(".remind_mobile").is(":visible") && $(".remind_mobile").val().length<11)
  {
  	$("#img_remind_error").show();
  	$("#span_remind_error").text("请输入正确的手机号");
  	setTimeout(function() { 
  		 $("#span_remind_error").html("&nbsp;");
  		 $("#img_remind_error").hide();
  		 $(".remind_mobile").focus(); },5000);
  	return; 
  }
   $("#img_hint_loadding").show();
   $("#img_remind_error").hide();
   $("#span_remind_error").text("正在提交，请稍候．．．");  
   $("#send_type").val(send_type); 
	 $("#frmRemind").ajaxSubmit({type:'post',dataType:'json',url:Init.url_remind,success:function(data)
      {        
      	$("#img_hint_loadding").hide();
      	$("#img_remind_error").show();
				$("#span_remind_error").text("提醒保存成功,您可以继续设置！");	
				GetRemindHint(5);	  	
		    setTimeout(function() { 
		    	 $("#img_remind_error").hide();
		    	 $("#span_remind_error").html("&nbsp;");
		    	 $("#span_remind_error").show();
		    	 },3000);         
      }
   });
});

$("#send_phone").live("click",function(){
	if( this.checked)
	{
		 if (this.getAttribute("mobile")=="")
		 {
		 	  $("#input_mobile").show();
		 	  $(".remind_mobile").focus();
		 }
	}
	else
	{
		$("#input_mobile").hide();
	}	
});

function InitDate()
{
	var date = new Date();
  var year = date.getFullYear();
  var html = "";
  for(var i=0;i<5;i++)
  {
  	var _year = year + i;
  	html += "<option value='"+_year+"'>"+_year+"</option>";
  }
  $("#remind_year").append(html);
  $("#remind_year").val(year);	
}

$("#btnAddPropelling").live("click",function(){
	 if (this.getAttribute("state")=="0") return;
	 if ($("#remind_staffs").children().length==0)
	 {
	 	 $(".propelling_hint").children().show();
	 	 
	 	 $("#propelling_hint_span").text("请选择接收人员！");
	 	 setTimeout(function(){
	 	 	   $(".propelling_hint").children().hide();
	 	 },1000);
	 	 return;
	 }
	 if ($(".propelling_remindContent").val()=="")
	 {
	 	 $(".propelling_hint").children().show();
	 	 $("#propelling_hint_span").text("请输入推送内容！");
	 	 setTimeout(function(){
	 	 	   $(".propelling_hint").children().hide();
	 	 	   $(".propelling_remindContent").focus();
	 	 },1000);
	 	 return;
	 }
	 if ($("#immediately0").attr("checked")=="checked")
	 {
	 	  var combox = $(".propelling_date_area>select");
	 	  var hint = "";
	 	  for(var i=0;i<combox.length;i++)
	 	  {
	 	  	 if (combox.eq(i).val()=="")
	 	  	 {
	 	  	 	 switch(i)
	 	  	 	 {
	 	  	 	 	  case 0:
	 	  	 	 	    hint="年";
	 	  	 	 	    break;
	 	  	 	 	  case 1:
	 	  	 	 	    hint="月";
	 	  	 	 	    break;
	 	  	 	 	  case 2:
	 	  	 	 	    hint="日";
	 	  	 	 	    break;
	 	  	 	 	  case 3:
	 	  	 	 	    hint="时";
	 	  	 	 	    break;
	 	  	 	 	  case 4:
	 	  	 	 	    hint="分钟";
	 	  	 	 	    break;	 	 	    	 	  	 	 	    	 	  	 	 	    	 	  	 	 	    
	 	  	 	 }
	 	  	 	 $(".propelling_hint").children().show();
				 	 $("#propelling_hint_span").text("请选择提醒时间中的"+hint);
				 	 setTimeout(function(){
				 	 	   $(".propelling_hint").children().hide();
				 	 	   $(".propelling_remindContent").focus();
				 	 },1500);
				 	 return;
	 	  	 }
	 	  }
	 	  var date = new Date($("#propelling_year").val(),$("#propelling_month").val(),$("#propelling_day").val(),$("#propelling_hour").val(),$("#propelling_minute").val());
	 	  var _date = new Date();
	 	  var today = new Date(_date.getFullYear(),_date.getMonth()+1,_date.getDate(),_date.getHours(),_date.getMinutes());
	 	  if (date<= today)
	 	  {
	 	  	 $(".propelling_hint").children().show();
				 	 $("#propelling_hint_span").text("提醒时间必须大于当前时间");
				 	 setTimeout(function(){
				 	 	   $(".propelling_hint").children().hide();
				 	 	   $(".propelling_remindContent").focus();
				 	 },1500);
				 	 return;
	 	  }
	 }	 
	 //是否立刻发送
	 if ($("#immediately1").attr("checked")=="checked")
	   $("#immediately").val(true);
	 else
	 	 $("#immediately").val(false);
	 //开始提交
	 this.setAttribute("state",0)
	 $(".propelling_hint").children().show();
	 $("#propelling_hint_span").text("正在提交数据。");
	 $("#propelling_hint_img").attr("src",Init.loadding_image);
	 
	 $("#frmpropelling").ajaxSubmit({type:'post',dataType:'json',url:Init.url_propelling_add,success:function(data)
      {
      	$("#propelling_hint_span").text("保存群发信息成功！");
	      $("#propelling_hint_img").attr("src",Init.hint_image);         
        $("#staff_jid").val("");
        $("#staff_jid_type").val("");
        $("#remind_staffs").html("");
        $(".propelling_remindContent").val("");
        if ( $("#immediately0").attr("checked")=="checked")
	      {
	 	      $(".propelling_date_area").hide();
	 	      $("#immediately1").attr("checked",true);
	 	      $("#immediately0").parent().attr("class","propelling_date_area_title");
	      }
        $("#propelling_year").val("");         
        $("#propelling_month").val("");
        $("#propelling_day").val("");
        $("#propelling_hour").val("");
        $("#propelling_minute").val("");         
			 	setTimeout(function(){
			 	  $(".propelling_hint").children().hide();
			 	},1500);    
			 	$("#btnAddPropelling").attr("state",1)     
      }
   });   
});


$("#immediately1").live("change",function(){
	 $(".propelling_date_area").hide();
	 $("#immediately0").parent().attr("class","propelling_date_area_title");
});

$("#immediately0").live("change",function(){
	 $(".propelling_date_area").show();
	 $(this).parent().attr("class","propelling_date_area_title_selected");
});


$(".propelling_list_selectpage>span").live("click",function(){
	 var countpage = parseInt($("#propelling_countpage").text());
	 var curpage = parseInt($("#propelling_pageindex").text());
	 var flag = $(this).attr("flag");
	 switch(flag)
	 {
	 	 case "firstpage":
	 	   if (curpage!=1)	 	   
	 	   	 curpage = 1;
	 	   break;
	 	 case "prevpage":
	 	   if (curpage !=1)
	 	   	  curpage = curpage-1;
	 	   break;
	 	 case "nextpage":
	 	   if (curpage !=countpage)
	 	   	  curpage = curpage + 1;
	 	   break;
	 	 case "lastpage":
	 	   if (curpage != countpage)
	 	     curpage = countpage;
	 	   break;
	 }
	 $("#propelling_pageindex").text(curpage);
	 var pageid = "propelling_page"+curpage;
	 if ($("#"+pageid).length==0)
	 {
	 	  $(".propelling_message_list_box>div:visible").hide();
	 	  $("#propelling_list_loadding").show();
      $.getJSON(Init.propelling_message_search,encodeURI("pageindex="+curpage), function(data){
	      	$("#propelling_list_loadding").hide();
	      	var html = "<div id='"+ pageid +"' style='float:left;width:100%;'></div>";
	      	$(".propelling_message_list_box").append(html);
	      	html = "";
	     	  for(var i=0;i<data.length;i++)
	     	  {
	     	   	  var row = data[i];   	   	  
	     	   	  html += "<div class='propelling_message_list' id='detailsid_"+row.detailsid+"'>"+
	     	   	          "  <span style='width:95px;padding-left:5px;'>"+row.nick_name+"</span><span style='width:110px;'>" +
	     	   	              row.remind_date+"</span><span class='propelling_list_content' title='"+row.remindcontent+"'>"+row.remindcontent+"</span>";
	     	   	  if (row.state=="0")
	     	   	    html+="<span class='propelling_list_delete' style='float:right;' detailsid='"+row.detailsid+"' remindid='"+row.remindid+"' title='删除'></span>";
	     	   	  else
	     	   	  	html+="<span title='编辑' stafftype='" + row.staff_type + "' staffid='" + row.remind_staffid + 
	     	   	  	       "' remindid='" + row.remindid +"' detailsid='" + row.detailsid+"' style='float:right;' class='propelling_list_edit'></span>";
	     	   	  html +="</div>";
	     	  }
	     	  $("#"+pageid).append(html);
     	});
	 }
	 else
	 {
	 	 $(".propelling_message_list_box>div:visible").hide();
	 	 $("#"+pageid).show();
	 }
});

$(".propelling_search_staff").live("click",function(){
   	 var keyword=$(".propelling_text_search").val();
   	 SearchPropellingStaff(4,"",keyword);
});


$(".propelling_list_delete").live("click",function(){
	var removeCtl = $(this).parent();
	var contentCtl = $(this).prev();
	var content = contentCtl.text();
	var html = "<img src='/bundles/fafatimewebase/images/loadingsmall.gif' style='float:left;padding-right:5px;margin-top:5px;' /><span style='float:left;color:red;'>正在删除该条记录！</span>";
	contentCtl.html(html);
	var para = "remindid="+this.getAttribute("remindid")+"&detailsid="+this.getAttribute("detailsid");
	$.getJSON(Init.propelling_message_delete,para, function(data){
	    if ( data)
	    {
	    	html="<img src='/bundles/fafatimewebase/images/zq.png' style='float:left;padding-right:5px;margin-top:5px;' /><span style='float:left;color:red;'>删除记录成功！</span>";
	      contentCtl.html(html);
	    	setTimeout(function(){
	    		removeCtl.hide();
	      },2000);
	    }
	    else
	    {
	    	html="<img src='/bundles/fafatimewebase/images/ts.png' style='float:left;padding-right:5px;margin-top:5px;' /><span style='float:left;color:red;'>删除记录失败！</span>";
	      contentCtl.html(html);
	    	setTimeout(function(){
	    		contentCtl.text(content);
	      },2000);
	    }
  });
});

$(".propelling_list_edit").live("click",function(){
	 $(".propelling_remindContent").val($(this).prev().text());
	 $("#remind_detailsid").val(this.getAttribute("detailsid"));
	 $("#remind_id").val(this.getAttribute("remindid"));
	 var date = $($(this.parentNode).children()[1]).text();
	 date = new Date(Date.parse(date.replace(/-/g,"/")));
	 if ($(".propelling_date_area_title").length==1)
	 {
	 	 $("#immediately0").attr("checked",true);
	 	 $(".propelling_date_area_title").attr("class","propelling_date_area_title_selected");
	 	 $(".propelling_date_area").show();	 	 
	 }
	 $("#propelling_year").val(date.getFullYear());
	 $("#propelling_month").val(date.getMonth()+1);
	 $("#propelling_day").val(date.getDate());
	 $("#propelling_hour").val(date.getHours());
	 $("#propelling_minute").val(date.getMinutes());
	 $("#edit_staff").val(this.getAttribute("staffid"));
	 $("#staff_jid").val(this.getAttribute("staffid"));
	 $("#staff_jid_type").val(this.getAttribute("stafftype"));
	 
	 var html = "<span class='propelling_selected_staff'>"+
	            " <span style='float:left;'>"+$($(this.parentNode).children()[0]).text()+"</span>"+
	            " <span staff_type='"+ this.getAttribute("stafftype") + "'jid='" + this.getAttribute("staffid") +"' title='移除用户' class='propelling_remove_staff' style='color:#5A5A5A;'></span></span>";
	 $("#remind_staffs").html(html);
});