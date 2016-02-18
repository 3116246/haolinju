var enterprise_setting={
enterNext:function (obj,nextCtl)
{
    document.getElementById(obj).onkeypress= function(event)
		{
		      if((event||window.event).keyCode==13)
		      {
		            document.getElementById(nextCtl).focus();
				  }
		}	
},
getEvent:function()
{
 if(document.all)    return window.event;//如果是ie
 var func=this.getEvent.caller;
        while(func!=null){
            var arg0=func.arguments[0];
            if(arg0){
            	if((arg0.constructor==Event || arg0.constructor ==MouseEvent) || (typeof(arg0)=="object" && arg0.preventDefault && arg0.stopPropagation))
            	{
            		return arg0;
            	}
            }
            func=func.caller;
        }
       return null;
},
inputPlusInteger:function (ctrl)
{
   var event = this.getEvent();
   var v = event.keyCode+event.charCode;
   if(document.all!=null)
   {   	 
	   if(v<48 || v>57)
	   {
	      event.keyCode=0;
	      return;
	   }
   }
   else
   {
	   if(v<48 || v>57)
	   {
	      return false;
	   }   
   }
   return true;
},
checkEname:function (path,v)
{
	 var ename=$(v).val();
	 var pattern=/\s*\S{2,}\s*/;
	 if(!pattern.test(ename))
	 {
	 	$(v).siblings("span").text("企业名称不能为空！");
	 	$(v).siblings("img").attr("src",g_resource_context+"bundles/fafatimewebase/images/errow.gif");
	 	$(v).siblings("img").show();
	 	$(v).focus();
	 	g_ename_return=false;
	 	return false;
	 }
	 $.post(path,{"ename":ename},function(json){
	 	if(json.exist)
	 	{
	 		$(v).siblings("span").text("企业名称已存在!");
	 	  $(v).siblings("img").attr("src",g_resource_context+"bundles/fafatimewebase/images/errow.gif");
	 		$(v).siblings("img").show();
	 		$(v).focus();
	 		g_ename_return=false;
	 	}
    else
    {
    	g_ename_return=true;
    	$(v).siblings("span").text("");
	 	 	$(v).siblings("img").hide();
	 	 	document.getElementById('btnSave').disabled=false;
    }
	 });
},
checkEshrotname:function (path,v)
{
	 var eshortname=$(v).val();
	 var pattern=/\s*\S{2,}\s*/;
	 if(!pattern.test(eshortname))
	 {
	 	$(v).siblings("span").text("企业简称不能为空！");
	 	$(v).siblings("img").attr("src",g_resource_context+"bundles/fafatimewebase/images/errow.gif");
	 	$(v).siblings("img").show();
	 	$(v).focus();
	 	g_eshortname_return=false;
	 	return false;
	 }
	 $.post(path,{"eshortname":eshortname},function(json){
	 	if(json.exist)
	 	{
	 		$(v).siblings("span").text("企业简称已存在!");
	 		$(v).siblings("img").attr("src",g_resource_context+"bundles/fafatimewebase/images/errow.gif");
	 		$(v).siblings("img").show();
	 		$(v).focus();
	 		g_eshortname_return=false;
	 		return false;
	 	}
    else
    {
    	g_eshortname_return=true;
    	$(v).siblings("span").text("");
	 	 	$(v).siblings("img").hide();
	 	 	document.getElementById('btnSave').disabled=false;
    }
	 	}); 
},
datasource:function(q,process)
{
		$.getJSON(manager_query_url,{q:this.query,network_domain:network_domain,t:new Date().getTime()},function(json)
        	  {
        	  	datasource=json;
        	  	for (var i=0; i<datasource.length; i++)
              {
	              datasource[i].index = i; 
	              datasource[i].toString = function(){return this.index};
              }
              process(datasource);
            });
   return null;
	
},
manager_matcher:function(item){
	 if(this.query)
	 {
	 	return ~item.login_account.toLowerCase().indexOf(this.query.toLowerCase())|| ~item.nick_name.indexOf(this.query);

	 } 
   return false;
},
manager_sorter:function(items ){
	return items;
},
manager_highlighter:function(item)
{
	 		return "<strong>"+item.nick_name+"("+item.login_account+")</strong>";
},
manager_updater:function(itemIndex)
{
	a=datasource;
	var sourceInputId= this.$element.attr("id"),$input = $("#InputNotifyArea");
	if(sourceInputId=="meeting_manager") $input = $("#meeting_InputNotifyArea");
	if(sourceInputId=="mobile_manager") $input = $("#mobile_InputNotifyArea");
	if ($("input[value='"+a[itemIndex].login_account+"']", $input).length == 0)
  {
  		$input.append(GetNotifyTemplate(a[itemIndex].login_account,a[itemIndex].nick_name)); 
	}
	return "";
},
online_service_highlighter:function(item){
	  return "<strong>"+item.nick_name+"("+item.fafa_jid+")</strong>";
},
online_service_updater:function(itemIndex)
{
	var a=datasource;
	var $input=$(this.$element.context);
	var etype=$input.attr("key_type");
	if(etype.match(/name$/))
	{
		$input.val(a[itemIndex].nick_name);
	}
	if(etype.match(/account$/))
	{
		$input.val(a[itemIndex].fafa_jid);
	} 
},
add_a_click:function(clicker,countnum)
{
	var $clicker=$(clicker);
	var a_key_type=$clicker.attr("id").substring(0,$clicker.attr("id").indexOf("_"));
	//alert(a_key_type);
	//var input_num=$clicker.children("input.span2")/2;
	var input_num=$clicker.parent("label.control-label").siblings("div.controls").children("div.input-prepend").children("input.span2").length/2;
	if(input_num<countnum)
	{
		var html='<div class="input-prepend"   style="position:relative;"><span class="add-on">名称</span>'+
		   '<input class="span2" type="text" value="" key_type="'+a_key_type+'_name" id="'+a_key_type+'_name'+(input_num+1)+'"  onblur=enterprise_setting.name_edit_click(this)>'+
		   '<span class="add-on">关联账号</span>'+
		   '<input class="span2" type="text" value="" empid="" data-provide="typehead" key_type="'+a_key_type+'_account" id="'+a_key_type+'_account'+(input_num+1)+'"  onblur=enterprise_setting.account_edit_click(this)>'+
		   '<img   class="error_img" style="display:none;margin-top:8px;" src="" width="16" height="16">'+
       '<span  class="tip_span" style="position:absolute;top:0px;right:10px;margin-top:2px;font-size:14px;height:30px;line-height:30px;color:red;"></span></div>';
		 $clicker.parent("label.control-label").siblings("div.controls").append(html);
		 var $new_name_input=$("#"+a_key_type+"_name"+(input_num+1));
		 var $edit_img=$new_name_input.parent("div.input-prepend").siblings("div.input-prepend").has("img.edit_add_img").children("img.edit_add_img");
		 $new_name_input.siblings("input.span2").after($edit_img[0]);
	}
},
name_edit_click:function(clicker)
{
	 
  	var $name_input=$(clicker);
  	$name_input.siblings("span.tip_span").text("");
		$name_input.siblings("img.error_img").hide();
  	var key_type=$name_input.attr("key_type");
  	var name_input_id=$name_input.attr("id");
  	var return_flag=false;
  	if($name_input.val().length==0)
  	{
  		  $name_input.siblings("span.tip_span").text("该名称不能为空!");
		 	  $name_input.siblings("img.error_img").attr("src",g_resource_context+"bundles/fafatimewebase/images/errow.gif");
		 		$name_input.siblings("img.error_img").show();
		 		return;
  	}
  	$name_input.parent("div.input-prepend").parent("div.controls").children("div.input-prepend").children("input[key_type="+key_type+"][id!="+name_input_id+"]").each(function(){
  	  if(this.value==$name_input.val())
  	  {
  	  	return_flag=true;
  	  	$name_input.siblings("span.tip_span").text("该名称已存在!");
		 	  $name_input.siblings("img.error_img").attr("src",g_resource_context+"bundles/fafatimewebase/images/errow.gif");
		 		$name_input.siblings("img.error_img").show();
		 		return;
  	  }
  	});
  	    if(return_flag) return;
		  	var ename=$name_input.val();
		  	var etype=$name_input.attr("key_type").substring(0,$name_input.attr("key_type").indexOf("_"));
		  	var empid=$name_input.siblings("input").attr("empid");
		  	var account=$name_input.siblings("input.span2").val();
		  	//修改名称、使用新增接口
		  	$.getJSON(new_public_account_url,{ename:ename,etype:etype,empid:empid},function(json){
		  		 if(json.length==0)
		  		 {
		  		 	$name_input.siblings("span.tip_span").text("该项修改失败!");
				 	  $name_input.siblings("img.error_img").attr("src",g_resource_context+"bundles/fafatimewebase/images/errow.gif");
				 		$name_input.siblings("img.error_img").show();
				 	 }
		  		 else
		  		 {
		  		 	$name_input.siblings("span.tip_span").text("");
		 				$name_input.siblings("img.error_img").hide();
		 				for(var i=0;i<json.length;i++)
					  {
				  		if(json[i].match(/^v[\d]+-[a-z]{4,7}/))
				  		{ 
				  			$.getJSON(save_public_account_url,{empid:json[i],account:account},function(json){
				  			 if(json.length==0)
				  			 {
				  			 	$name_input.siblings("span.tip_span").text("该项修改失败!");
							 	  $name_input.siblings("img.error_img").attr("src",g_resource_context+"bundles/fafatimewebase/images/errow.gif");
							 		$name_input.siblings("img.error_img").show();
				  			 }
				  			 else
				  			 {
				  			 	$name_input.siblings("span.tip_span").text("");
		 							$name_input.siblings("img.error_img").hide();
				  			 	$name_input.siblings("input.span2").attr("empid",json[i]);
				  			 }
				  			});
				  	  }
					  }
		  		 }
		  	});


},
account_edit_click:function(clicker)
{
	  var $account_input=$(clicker);
	  $account_input.siblings("span.tip_span").text("");
		$account_input.siblings("img.span2").hide();
	  var key_type=$account_input.attr("key_type");
	  var account_input_id=$account_input.attr("id");
	  var return_flag=false;
  	$account_input.parent("div.input-prepend").parent("div.controls").children("div.input-prepend").children("input[key_type="+key_type+"][id!="+account_input_id+"]").each(function()
  	{
  	  if(this.value==$account_input.val()&&$account_input.val()!="")
  	  {
  	  	return_flag=true;
  	  	$account_input.siblings("span.tip_span").text("该账号已关联!");
		 	  $account_input.siblings("img.error_img").attr("src",g_resource_context+"bundles/fafatimewebase/images/errow.gif");
		 		$account_input.siblings("img.error_img").show();
		 		return;
  	  }
  	});
  	  if(return_flag) return;
		  var account=$account_input.val();
			var empid=$account_input.attr("empid");
		  $.getJSON(save_public_account_url,{empid:empid,account:account},function(json){
		  	if(json.length==0)
		  	{
		  		$account_input.siblings("span.tip_span").text("该项更改失败!");
			 	  $account_input.siblings("img.error_img").attr("src",g_resource_context+"bundles/fafatimewebase/images/errow.gif");
			 		$account_input.siblings("img.error_img").show();
		  	}
		  	else
		  	{
		  		$account_input.siblings("span.tip_span").text("");
		 			$account_input.siblings("img.error_img").hide();
		  	}
		  });
}
};