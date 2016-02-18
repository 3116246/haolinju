function visibilit(event,e,i){
		if(checkHover(event,e)){
			if(i==true){
				$(e).find('img').css('visibility','');
			}
			else{
				$(e).find('img').css('visibility','hidden');
			}
		}
	}
	function drop_icon_over(event,e){
				if(checkHover(event,e)){
				$('.drop_list').unbind().remove();
				var $e=$(e).parent().parent();
				drop=setTimeout(function(){
					var html=[];
					html.push("<ul class='drop_list' style='display:none'>");
					html.push("<li onclick=''><span>导出</span></li>");
					if($e.attr('id').substring(0,1)!='M'){
						html.push("<li onclick=\"changeName('"+$e.attr('id')+"')\"><span>更改名称</span></li>");
						html.push("<li onclick=\"deleteGroup('"+$e.attr('id')+"')\"><span>删除分组</span></li>");
					}
					html.push("</ul>");
					$(document.body).append(html.join(''));
					var hei=$e.height();
					var le=$e.offset().left;
					var to=$e.offset().top+hei;
					$('.drop_list').css({left:(le+"px"),top:(to+"px")});
					$('.drop_list').mouseover(function(event){
						if(checkHover(event,this)){
							clearTimeout(drop);
						}
					});
					$('.drop_list').mouseout(function(event){
						if(checkHover(event,this)){
							drop=setTimeout(function(){
								$('.drop_list').unbind().remove();
							},500);
						}
					});
					$('.drop_list').show();
				},500);
			}
	}
	function drop_icon_out(event,e){
		if(checkHover(event,e)){
				clearTimeout(drop);
				if($('.drop_list').length){
					drop=setTimeout(function(){
						$('.drop_list').unbind().remove();
					},500);
				}
			}	
	}
	$(document).ready(function(){
		bar_move={
		$e:$("#typename"),
		$left:$("#nav_pre"),
		$right:$("#nav_next"),
		L_move:function(){
			this.$e.children("#search_result").remove();
			items=this.$e.children("li");
			var t=0;
			for(;t<items.length;t++)
			{
				if($(items[t]).css('display')!='none')
				break;
			}
			items=items.splice(t,items.length);
			if(items.length<7)return;
			for(var i=0;i<6;i++)
			{
				$(items[i]).hide();
			}
			if(items.length-6<7)this.$right.children("a").hide();
			this.$left.children("a").css('display','block');
		},
		R_move:function(){
			this.$e.children("#search_result").remove();
			items=this.$e.children("li[style*='display: none']");
			if(items.length==0)return;
			for(var i=items.length-1;i>items.length-7;i--)
			{
				$(items[i]).show();
			}
			if(items.length-6==0)this.$left.children("a").hide();
			if(items.length!=this.$e.find('li').length){
				this.$right.children("a").css('display','block');
			}
			else{
				this.$right.children("a").hide();
			}
		},
		check:function(){
			this.$e.children("#search_result").remove();
			var dis_num=this.$e.children("li[style*='display: none']").length;
			var num=this.$e.find('li').length-dis_num;
			if(num>6){
				this.$right.children("a").css('display','block');
			}
			else{
				this.$right.children("a").hide();
			}
			if(num==0){
				bar_move.R_move();
			}
		}
		};
		
		var items=$("#typename li"); 
		$(items[0]).children("a").css('border','1px solid #0088CC');
		$(items[0]).children("a").css('border-bottom','');
		$("#typename li").click(function(){$('.letter').css('color','#0088CC');$('.letter:first').css('color','red');setActive(this);});
		$(".file_input").hover(function(){
			
		});
		$("#searchinput2").keydown(function(event){
			if(event.keyCode==13)
			{
				addr_search();
			}
		});
	    $(".letter").hover(function(){
				$(this).css('cursor','pointer');
			});
			$(".letter").click(function(){
				var url="";
				$(this).siblings().css('color','#0088CC');
				$(this).css('color','red');
				if($(this).text()=="全部")
				{
					url=addr_view_url+"?type="+$(".adrlist_active input").val()+"&pageindex=1";
				}
				else{
				  url=addr_search_url+"?type="+$(".adrlist_active input").val()+"&text="+$(this).text()+"&pageindex=1";
				}
				$("#referer").val(url);
				$("#contenter").children().remove();
				$("#contenter").append("<div class='addr_con' id='addr_con_1'></div>");
  	    LoadComponent('addr_con_1',url);
			});	
		$(window).scroll(function(){Addrlist_Document_OnScroll();});
		$(".adrlist_active").find("a").css('height','22px');
		//计算是否显示左右翻动按钮
		var lis = $("#typename li");
		var w=0;
		for(var i=0;i<lis.length;i++)
		{
		    	w += $(lis[i]).width();
		}
		if(w>$("#typename").parent().width())
		{
		    $("#nav_next a").css("display","block");
		    //$("#nav_next").show();
		}
	});
	function Addrlist_Document_OnScroll()
  {
  	var $document = $(document); //document
    if ($document.scrollTop() + $(window).height() >= $document.height())
    {
    	var m=Math.ceil(page_index);
  	  var n=Math.ceil(page_count);
    	if($(".contenter").children('.addr_con').length==((m<n || page_count%3==0)?3:(page_count%3)))return;
  	  if($(".contenter").children('.addr_con:last').children('table')[0]==undefined)return;
    	var url=$("#referer").val();
  	  var param=url.match(/pageindex=\d+/);
  	  var pageindex=param[0].split('=')[1];
  	  url=url.replace(/pageindex=\d+/,"pageindex="+(pageindex-0+1).toString());
  	  $("#referer").val(url);
  	  var items=$(".contenter").children();
  	  $(items[items.length-1]).after("<div class='addr_con' id='addr_con_"+(items.length+1).toString()+"'></div>");
    	LoadComponent("addr_con_"+(items.length+1).toString(),url);
    }
  }
	function setActive(e)
	{
		  setAcCss(e);
			if($("#search_result").length>0){ $("#search_result").remove();}
		  var dtype=$(e).children("input").val();
			var path=addr_view_url;
			var url=path+"?type="+dtype+"&pageindex=1";
			$("#referer").val(url);
			$(".contenter").children().remove();
			$("#contenter").append("<div class='addr_con' id='addr_con_1'></div>");
			LoadComponent('addr_con_1',url);
	}
	function setAcCss(e)
	{
		  $(".adrlist_active").find("a").css('border','');
			$(".adrlist_active").removeAttr("class");
			$(e).attr("class","adrlist_active");
			$(e).find("a").css({'border':'1px solid #0088CC','border-bottom':'','height':'22px'});
	}
	function addr_search(){
	   if($("#typename #search_result").length==0){
	   	  var m= $("#typename").children("li[style*='display: none']").length;
	   	  var n= $("#typename").children("li").length;
		    $("#typename").children("li:eq("+((m+(n-m>5?5:n-m))-1)+")").after("<li id='search_result' onmouseout='visibilit(event,this,false)' onmouseover='visibilit(event,this,true)'><a href='#'>搜索结果</a></li>");
		 }

		var text=$("#searchinput2").val();
		var url=addr_search_url+"?type=all&text="+text+"&pageindex=1";
		$("#referer").val(url);
		$("#contenter").children().remove();
		setAcCss($('#search_result')[0]);
		$("#contenter").append("<div class='addr_con' id='addr_con_1'></div>");
		$('.letter').css('color','#0088CC');
		$('.letter:first').css('color','red');
  	LoadComponent('addr_con_1',url);
	}
	function closeAddType()
	{
		$(".add_addr_type").hide();
		$(".add_addr_type input[type='text']").val('');
		$(".add_addr_type .error_type").text('');
	}
	function submitAddType()
	{
		$(".add_addr_type .error_type").text("");
		$("#add_type_load").show();
		$("#addr_type_form").ajaxSubmit({
		type:'post',
		dataType:'json',
		url:addr_addtype_url,
		success:function(d){
		$("#add_type_load").hide();
		if(d.s=="1")
		{
			$(".add_Addr select").append("<option selected='selected' value='"+d.typeid+"'>"+d.typename+"</option>");
			$("#typename").append("<li onmouseout='visibilit(event,this,false)' onmouseover='visibilit(event,this,true)' id='"+d.typeid+"'><a href='#'>"+d.typename+"<span class='count'>(0)</span><img class='drop_icon' style='visibility:hidden;' onmouseover='drop_icon_over(event,this)' onmouseout='drop_icon_out(event,this)' src='"+drop_icon_url+"'/></a><input type='hidden' value='"+d.typeid+"'/></li>");
			$("#typename li").click(function(){setActive(this);});
			closeAddType();
			bar_move.check();
		}
		else
	  {
	  	$(".add_addr_type .error_type").text(d.message);
	  }
		}	
		});
	}
	function changeName(typeid){
		$(".changeName").find("input[type='hidden']").val(typeid);
		$(".changeName").find(".typename").text($("#"+typeid).attr('typename'));
		$(".changeName").show();		
	}
	function closeChangeName(){
		$(".changeName").hide();
		$(".changeName input[type='text']").val('');
		$(".changeName .error_type").text('');
	}
	function submitChangeName(){
		$(".changeName .error_type").text("");
		$("#change_name_load").show();
		$("#change_name_form").ajaxSubmit({
		type:'post',
		dataType:'json',
		url:change_name_url,
		success:function(d){
		$("#change_name_load").hide();
		if(d.s=="1")
		{
			closeChangeName();
			$(".add_Addr option[value='"+d.typeid+"']").text(d.typename);
			$("#"+d.typeid).find(".typename").text(d.typename);
		}
		else
	  {
	  	$(".changeName .error_type").text(d.message);
	  }
		}	
		});
	}
	function closeAdd()
	{
		$(".add_Addr").hide();
		$(".add_Addr input[type='text']").val('');
		$("#error").text('');
	}
	function submitAdd()
	{
		$("#error").text("");
		$("#add_load").show();
	  $("#addr_Form").ajaxSubmit({
	  type:'post',
	  dataType:'json',
	  url:addr_edit_url+'?editType=add',
	  success:function(d){
	  	$("#add_load").hide();
	  	if(d.s=="1")
	  	{
	  		var text=$("#"+d.typeid+" .count").text();
	  		$("#"+d.typeid+" .count").text(text.replace(/\d+/,text.match(/\d+/)-0+1));
	  	  $("#referer").val(addr_view_url+'?type='+d.typeid+'pageindex=1');
	  	  setActive($("#"+d.typeid)[0]);
	  		closeAdd();
	  		$("#contenter").children().remove();
	  		$("#contenter").append("<div class='addr_con' id='addr_con_1'></div>");
	  		LoadComponent('addr_con_1',$('#referer').val());
	  	}
	  	else
	  	{$("#error").text(d.message);}
	  }
	});
	}
	function closeEdit()
	{
		$(".edit_Addr").hide();
		$("#edit_error").text('');
	}
	function submitEdit()
	{
		$("#edit_error").text("");
		$("#edit_load").show();
		var addr_name=$(".edit_Addr input[name='addr_name']").val();
  	var addr_unit=$(".edit_Addr input[name='addr_unit']").val();
  	var addr_mail=$(".edit_Addr input[name='addr_mail']").val();
  	var addr_phone=$(".edit_Addr input[name='addr_phone']").val();
  	var addr_mobile=$(".edit_Addr input[name='addr_mobile']").val();
	  $("#edit_Form").ajaxSubmit({
	  type:'post',
	  dataType:'json',
	  url:addr_edit_url+'?editType=edit',
	  success:function(d){
	  	$("#edit_load").hide();
	  	if(d.s=="1")
	  	{
	  		closeEdit();
	  		$parent=$("td[login_account=''][addr_id='"+d.id+"']").parent();
	  		$parent.find('.name_unit').text(addr_name);
  	    $parent.find(".unit").children().text(addr_unit);
  	    $parent.children("td:eq(5)").find("a").text(addr_mail);
  	    $parent.children("td:eq(3)").find("a").text(addr_phone);
  	    $parent.children("td:eq(4)").find("a").text(addr_mobile);
	  	}
	  	else
	  	{
	  		$("#edit_error").text(d.message);
	  	}
	  }
	  });
	}
	function closeIntro()
	{
		$("#intro_error").text("");
		$(".intro_excel").hide();
		$(".intro_excel input").val("");
	}
	function submitIntro()
	{
		$("#intro_error").text("");
		$("#intro_load").show();
		$("#intro_Form").ajaxSubmit({
		type:'post',
		dataType:'json',
		url:addr_intro_url,
		success:function(d){
			$("#edit_load").hide();
			if(d.s=="1")
			{
				closeIntro();
				$("#contenter").children().remove();
	  		$("#contenter").append("<div class='addr_con' id='addr_con_1'></div>");
	  		LoadComponent('addr_con_1',$('#referer').val());
			}
			else{
				$("#intro_error").text(d.message);
			}
		}	
		});
	}
	function delAddr(e){
		if(confirm("确定要删除吗?")){
			loading(e);
			var addr_id=$(e).parent().attr('addr_id');
			var addr_account=$(e).parent().attr('addr_account');
			var typeid=$("td[addr_id='"+addr_id+"'][login_account='"+addr_account+"']").find('.typeid').val();
			$.getJSON(addr_edit_url,
			{
				editType:'delete',
				id:addr_id,
				addr_account:addr_account
			},
			function(d){
				if(d.s=='1'){
					for(var i=0;i<d.typeid.length;i++){
						var text=$("#"+d.typeid[i]+" .count").text();
	  				$("#"+d.typeid[i]+" .count").text(text.replace(/\d+/,text.match(/\d+/)-0-1));
					}
					if($('#referer').val()!=""){
	  				$("#contenter").children().remove();
	  				$("#contenter").append("<div class='addr_con' id='addr_con_1'></div>");
	  				LoadComponent('addr_con_1',addr_view_url+"?type="+typeid+"&pageindex=1");
	  				$('#referer').val(addr_view_url+"?type="+typeid+"&pageindex=1");
  				}
				}
			});
		}
	}
	function deleteGroup(typeid){
		var $e=$("#"+typeid);
		var c=$e.find('.count').text();
		var a=$e.find('a').text();
		if(confirm("你确定删除【"+a.substring(0,a.indexOf(c))+"】吗?")){
			$.getJSON(addr_deltype_url,
			{
				typeid:typeid
			},
			function(d){
				if(d.s=='1'){
					$("#"+typeid).unbind().remove();
					$("#addr_Form select option[value='"+typeid+"']").remove();
					bar_move.check();
				}
				else{
					alert(d.message);
				}
			});
		}
	}

// view.html.twig
  function moveoutAddr(e){
  	var addr_account=$(e).parent().attr('addr_account');
  	var id=$(e).parent().attr('addr_id');
  	$.getJSON(addr_edit_url,
  	{
  	 editType:'move',
  	 id:id,
  	 to:'',
  	 typeid:$("td[addr_id='"+id+"'][login_account='"+addr_account+"']").find('.typeid').val(),
  	 addr_account:addr_account
  	},
  	function(json){
  		if(json.s=='1')
  		{
  			var text=$("#"+json.typeid+" .count").text();
	  		$("#"+json.typeid+" .count").text(text.replace(/\d+/,text.match(/\d+/)-0-1));
  			if($('#referer').val()!=""){
  				$("#contenter").children().remove();
  				$("#contenter").append("<div class='addr_con' id='addr_con_1'></div>");
  				LoadComponent('addr_con_1',addr_view_url+"?type="+json.typeid+"&pageindex=1");
  				$('#referer').val(addr_view_url+"?type="+json.typeid+"&pageindex=1");
  			}
  		}
  		else{
  			alert(json.message);
  		}
  	}
  	);
  	loading(e);
  }
  function copyAddr(e){
  	var addr_account=$(e).parent().attr('addr_account');
  	var id=$(e).parent().attr('addr_id');
  	var $parent=$("td[addr_id='"+id+"'][login_account='"+addr_account+"']");
  	var to='';
  	var stype="";
  	if($(e).find(".toCard").length>0){
  		if($parent.find('.isInCard').val()=='yes')return;
  		to="M001";
  		stype='toCard';
  	}
  	if($(e).find(".addr_type").length>0){
  		to=$(e).attr('typeid');
  		stype='copy';
  	}
  	var typeid=$parent.find('.typeid').val();
  	var addr_name=$parent.find('.name_unit').text();
  	var addr_unit=$parent.find(".unit").children().text();
  	var addr_mail=$parent.children("td:eq(5)").find("a").text();
  	var addr_phone=$parent.children("td:eq(3)").find("a").text();
  	var addr_mobile=$parent.children("td:eq(4)").find("a").text();
  	$.getJSON(addr_edit_url,
  	{
  		editType:'copy',
  		typeid:typeid,
  		to:to,
  		addr_account:addr_account,
  	  addr_name:addr_name,
  	  addr_mobile:addr_mobile,
  	  addr_phone:addr_phone,
  	  addr_mail:addr_mail,
  	  addr_unit:addr_unit,
  	  id:id
  	},
  	function(json){
  		loaded(json,stype);
  		if(json.s=='1'){
  			var text=$("#"+json.typeid+" .count").text();
	  		$("#"+json.typeid+" .count").text(text.replace(/\d+/,text.match(/\d+/)-0+1));
  		}
  		else{
  		 alert(json.message);
  		}
  	}
  	);
  	loading(e);
  }
  function movetoAddr(e) 
  {
  	var addr_account=$(e).parent().attr('addr_account');
  	var id=$(e).parent().attr('addr_id');
  	var $parent=$("td[addr_id='"+id+"'][login_account='"+addr_account+"']");
  	var typeid=$parent.find('.typeid').val();
  	var addr_name=$parent.find('.name_unit').text();
  	var addr_unit=$parent.find(".unit").children().text();
  	var addr_mail=$parent.children("td:eq(5)").find("a").text();
  	var addr_phone=$parent.children("td:eq(3)").find("a").text();
  	var addr_mobile=$parent.children("td:eq(4)").find("a").text();
  	var stype="";
  	if($(e).find('.addr_type')){
  		to=$(e).attr('typeid');
  		if(to=='M001'){
  			if($parent.find('.isInCard').val()=='yes')return;
  		}
  		stype='moveto';
  	}
  	$.getJSON(addr_edit_url,
  	{
  		editType:'move',
  		typeid:typeid,
  		to:to,
  		addr_account:addr_account,
  	  addr_name:addr_name,
  	  addr_mobile:addr_mobile,
  	  addr_phone:addr_phone,
  	  addr_mail:addr_mail,
  	  addr_unit:addr_unit,
  	  id:id
  	},
  	function(json){
  		loaded(json,stype);
  		if(json.s=='1'){
  			var text=$("#"+json.typeid+" .count").text();
	  		$("#"+json.typeid+" .count").text(text.replace(/\d+/,text.match(/\d+/)-0-1));
	  		var text=$("#"+to+" .count").text();
	  		$("#"+to+" .count").text(text.replace(/\d+/,text.match(/\d+/)-0+1));
	  		if($('#referer').val()!=""){
  				$("#contenter").children().remove();
  				$("#contenter").append("<div class='addr_con' id='addr_con_1'></div>");
  				LoadComponent('addr_con_1',addr_view_url+"?type="+typeid+"&pageindex=1");
  				$('#referer').val(addr_view_url+"?type="+typeid+"&pageindex=1");
  		  }
  		}
  		else{
  		}
  	}
  	);
  	loading(e);
  }
  function send_msg(e)
  {
  	var $acc = $(e).parent().attr("addr_account");
  	var inpt = $("input.addr_account[value='"+$acc+"']");
  	var jid=inpt.parent().find(".jid").val();
  	FaFaChatWin.ShowRoster(jid);
  }
  function shareAddr(e)
  {
  	 var $acc = $(e).parent().attr("addr_account");
  	 var li = $("input.addr_account[value='"+$acc+"']").parent();
  	 var tr = $("td[login_account='"+$acc+"']").parent();
  	 FaFaShare.share_Show_Window(li.find(".name_unit").text()
  	                            +"： 部门 "+tr.find(".unit").text()
  	                            +"，  电话 "+tr.find("td:eq(3) a").text()
  	                            +"，  手机 "+tr.find("td:eq(4) a").text()
  	                            +"，  邮箱 "+tr.find("td:eq(5) a").text()
  	                            );
  }
  function getpage(pageindex)
  {
  	var url=$("#referer").val();
  	url=url.replace(/pageindex=\d+/,"pageindex="+pageindex);
  	$("#referer").val(url);
  	$("#contenter").children().remove();
  	$("#contenter").append("<div class='addr_con' id='addr_con_1'></div>");
  	LoadComponent('addr_con_1',url);
  }
  function loading(e)
  {
  	if($(e).find('.toCard').length>0){
  		e=$(e).find('.toCard');
  	}
  	else if($(e).find('.goCard').length>0){
  		e=$(e).find('.goCard');
  	}
  	else if($(e).find('.sheet_move').length>0){
  		$(e).parent().unbind().remove();
  		e=$(".buttons").find(".addr_menu");
  	}
  	else if($(e).find('.sheet_edit').length>0){
  		$(e).parent().unbind().remove();
  	}
  	else if($(e).find('.sheet_delete').length>0){
  		$(e).parent().unbind().remove();
  		e=$(".buttons").find(".addr_menu");
  	}
  	else if($(e).find('.addr_type').length>0){
  		$(e).parent().unbind().remove();
  		$(".addr_sheet").unbind().remove();
  		e=$(".buttons").find(".addr_menu");
  	}
//  	if($(e).attr('class').indexOf('addr_sheet')>0){
//  		$(e).unbind().remove();
//  	}
  	$(e).hide();
  	$(e).after($("#forload").html());
  	if($(e).text()=="邀请加入"){
  		$(e).siblings(".bnt_load").css('margin-left','20px');
  	}
  }
  function loaded(json,stype)
  {
  	if(stype=='toCard'){
  		var account=(json.curr_account);
  		$buttons=$(".buttons");
  		$buttons.find(".toCard").removeClass('bnt_5');
  		$buttons.find(".toCard").addClass('bnt_5_grey');
  		$buttons.find(".bnt_load").remove();
  		$buttons.find(".toCard").show();
  		var $td=$("td[id='"+json.id+"',login_account='"+account+"']");
	  	if(json.s=='1'){
	  		$("td[login_account='"+account+"'] .isInCard").val('yes');	
	  	}
	  	if($buttons.find(".mouse").val()=='out')
	  	{	  		
	  		$(".buttons").hide();
	  	}
	  }
	  if(stype=='invite'){
	  	$("li[id='"+json.id+"'] .bnt_load").remove();
	  	$("li[id='"+json.id+"'] a").show();
	  	if(json.s=='1'){
	  		$("li[id='"+json.id+"'] a").after($("#forcorr").html());
	  	}
	  }
	  if(stype=='moveto' || stype=='copy'){
  		$buttons=$(".buttons");
  		$buttons.find(".bnt_load").remove();
  		$buttons.find(".addr_menu").show();
  		$(".buttons").hide();
	  }
  }
  function editAddr(event,e)
  {
  	loading(e);
  	var ev=event||window.event;
  	var x=ev.clientX;
  	var y=ev.clientY+$(document).scrollTop();
  	var addr_account=$(e).parent().attr('addr_account');
  	var id=$(e).parent().attr('addr_id');
  	var $parent=$("td[addr_id='"+id+"'][login_account='"+addr_account+"']").parent();
  	var addr_name=$parent.find('.name_unit').text();
  	var addr_unit=$parent.find(".unit").children().text();
  	var addr_mail=$parent.children("td:eq(5)").find("a").text();
  	var addr_phone=$parent.children("td:eq(3)").find("a").text();
  	var addr_mobile=$parent.children("td:eq(4)").find("a").text();
  	if((y+$(".edit_Addr").height()+50)>($(document).scrollTop()+$(window).height()))y=y-$(".edit_Addr").height();
  	$(".edit_Addr").css({'left':x+'px','top':y+'px'});
  	$(".edit_Addr input[name='addr_name']").val(addr_name);
  	$(".edit_Addr input[name='addr_unit']").val(addr_unit);
  	$(".edit_Addr input[name='addr_mail']").val(addr_mail);
  	$(".edit_Addr input[name='addr_phone']").val(addr_phone);
  	$(".edit_Addr input[name='addr_mobile']").val(addr_mobile);
  	$(".edit_Addr input[name='id']").val(id);
  	$(".edit_Addr").show();
  }
  function showSheet(e){
  	  var addr_id=$(e).parent().attr('addr_id');
  	  var addr_account=$(e).parent().attr('addr_account');
  		$p=$("td[addr_id='"+addr_id+"'][login_account='"+addr_account+"']");
  		var typeid=$p.find('.typeid').val();
  		var html=[];
  		html.push("<ul style='display:none' addr_id='"+addr_id+"' addr_account='"+addr_account+"' class='addr_sheet'>");
  		if(addr_id!='' && addr_account==''){
  			html.push("<li onclick='editAddr(event,this)'><span class='sheet_edit'>编辑</span></li>");
  		}
  		if(typeid!='M002'){
  			html.push("<li onclick='moveoutAddr(this)'><span class='sheet_move'>移除</span></li>");
  			html.push("<li class='sheet_moveto' action='moveto'><span>移动到</span><span class='adpp'></span></li>");
  		}
  		html.push("<li class='sheet_copy' action='copy'><span>添加到</span><span class='adpp'></span></li>");
  		if(typeid!='M002'){
  			html.push("<li onclick='delAddr(this)'><span class='sheet_delete'>删除</span></li>");
  		}
  		html.push("</ul>");
  		var $ul=$(html.join(''));
  		$ul.bind('mouseover',function(event){
  			if(checkHover(event,this)){
  				$(".buttons").show();
  				if(typeof(addr_timer)!="undefined"){
  					clearTimeout(addr_timer);
  				}
  			}
  		});
  		$ul.bind('mouseout',function(event){
  			if(checkHover(event,this)){
  				addr_timer=setTimeout(function(){
  					$(".addr_sheet").unbind().remove();
  				},50);
  			}
  		});
  		$ul.find('.sheet_moveto,.sheet_copy').bind('mouseover',function(event){
  			if(checkHover(event,this)){
  				if(typeof(sheet_timer)!="undefined"){
  					clearTimeout(sheet_timer);
  				}
  				getTypeSheet(event,this,$(this).attr('action'));
  			}
  		});
  		$ul.find(".sheet_moveto,.sheet_copy").bind('mouseout',function(event){
  			if(checkHover(event,this)){
  				sheet_timer=setTimeout(function(){
  					$(".addrtype_sheet").unbind().remove();
  				},50);
  			}
  		});
  		$(document.body).append($ul);
  		le=$(".buttons").offset().left-$ul.width()+$(".buttons").width();
  		to=$(".buttons").offset().top+$(".buttons").height();
  		$ul.css({left:le+'px',top:to+2+'px'});
  		$ul.show();
  }
  function getTypeSheet(ev,e,act){
  	if($(".addrtype_sheet").length>0){
  		$(".addrtype_sheet").unbind().remove();
  	}
  	var addr_id=$(e).parent().attr('addr_id');
  	var addr_account=$(e).parent().attr('addr_account');
  	var typeid=$("td[addr_id='"+addr_id+"'][login_account='"+addr_account+"']").find('.typeid').val();
  	var html=[];
		html.push("<ul style='display:none' action='"+act+"' addr_id='"+addr_id+"' addr_account='"+addr_account+"' class='addrtype_sheet'>");
		var lis=$("#typename li");
		
		for(var i=0;i<lis.length;i++){
			if(lis[i].id!='M002' && lis[i].id!=typeid){
				html.push("<li typeid='"+lis[i].id+"'><span class='addr_type'>"+$(lis[i]).attr('typename')+"</span></li>");
			}
		}
		html.push("</ul>");
		$sheet=$(html.join(''));
		$sheet.bind('mouseout',function(event){
			if(checkHover(event,this)){
				$(this).unbind().remove();
				addr_timer=setTimeout(function(){
					$(".addr_sheet").unbind().remove();
				},50);
				var el=null;
				if($(this).attr('action')=='moveto'){
					el=$(".sheet_moveto")[0];
				}
				else if($(this).attr('action')=='copy'){
					el=$(".sheet_copy")[0];
				}
				$(el).removeClass('mouseover');
			}
		});
		$sheet.bind('mouseover',function(event){
			if(checkHover(event,this)){
				if(typeof(sheet_timer)!='undefined'){
					clearTimeout(sheet_timer);
				}
				if(typeof(addr_timer)!='undefined'){
					clearTimeout(addr_timer);
				}
				var el=null;
				if($(this).attr('action')=='moveto'){
					el=$(".sheet_moveto")[0];
				}
				else if($(this).attr('action')=='copy'){
					el=$(".sheet_copy")[0];
				}
				$(el).addClass('mouseover');
			}
		});
		$sheet.find('li').bind('click',function(){
			if($(this).parent().attr('action')=='moveto'){
				movetoAddr(this);
			}
			else if($(this).parent().attr('action')=='copy'){
				copyAddr(this);
			}
		});
		$(document.body).append($sheet);
		var le=$(e).offset().left+$(e).width();
		var to=$(e).offset().top;
		$sheet.css({left:le+7+'px',top:to+'px'});
		$sheet.show();
  }