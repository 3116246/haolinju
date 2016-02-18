var Mgmt = {};

Mgmt.init = function(){
	Index.search.config(null);
	$("#staff").on("keypress",function(evt){
		if(evt.which==13)
		{
			Mgmt.query();
		}
	});
	Mgmt.listMgmtor();
}

Mgmt.account = [];
Mgmt.count = 0;

//获取现有的管理员
Mgmt.listMgmtor = function(){
	var url = Index.server+"&jsoncallback=?&module=enterprise&action=getManager";
	var html = "";

	$.getJSON(url,function(json){
		var list = json.data;
		Mgmt.count = list.length;

		for(var i=0;i<list.length;i++){

			var cell = list[i];
			if(cell==null||cell=="null") continue;
			if ($.trim(cell.photo_pth)=='') {cell.photo_pth="../assets/admin/pages/media/profile/avatar.png";};

			Mgmt.account.push(cell.login_account);
			var id="div-"+i;

			var template = 
			"<div class=\"mgmt\" id="+id+">"
				+"<img title=\"删除管理员\" src=\"images/error.png\" class=\"hide\" style=\"margin-left: 62px;cursor:pointer;margin-top: -5px;"
				+"position: absolute;z-index: 99;\" onclick=\"Mgmt.removeMgmtor(this,'"+cell.login_account+"');\">"
				+"<img src=\""+cell.photo_pth+"\" class='img-circle' style=\"margin: 0 8px;\">"
				+"<p style=\"margin-top:10px;\">"+cell.nick_name+"</p>"
			+"</div>";
			html+=template;
		}
		
		$("#mgmtor").html(html);
		
		Mgmt._on();

	});

}
Mgmt.curId=null;
Mgmt._on = function(){
	$(".mgmt").on("mouseover",function(e){
		if(checkHover(e,this)){
			//alert($(this).attr("class"))
			Mgmt.curId = $(this);
	    	 Mgmt.hoverTimer = setTimeout("Mgmt.show();",500);
	         clearTimeout(Mgmt.outTimer);
	    }

	});

	$(".mgmt").on("mouseout",function(e){
		if(checkHover(e,this)){

			
	    	 Mgmt.outTimer = setTimeout("Mgmt.hide()",500);
	         clearTimeout(Mgmt.hoverTimer);
	         
	    }

	});

}

//查询人员
Mgmt.query=function()
{
		
	var dataurl = Index.server+'&module=ApiHR&action=staff_query&jsoncallback=?';
   	var account = $("#staff").val();
    
    var params = {
    		limit:15,
    		deptid:'',
    		page_num:1,
    		search:account
    	}
    	
    $("#btn_search").html('查询中...').attr('disabled',true);
    	//获取内容，刷新页面
    $.getJSON(dataurl, params, function(json) {      
      	$("#btn_search").html('搜索并添加').attr('disabled',false);
      	var html = template('get_stafflist-tmpl', json);
		$('#getstaffList tbody').html(html);
    });	
}

//设为管理员
Mgmt.setMgmtor = function(el,photo,account,nick_name){

	if (Mgmt.count>=5) {

		$(".alert.alert-danger").show().find("label").html("管理员数不能超过5个！");
		return;
	};

	
	var url = Index.server+"&jsoncallback=?&module=enterprise&action=saveManager&params={\"staff\":\""+account+"\"}";

	if (!photo) {
		photo="../assets/admin/pages/media/profile/avatar.png";
	};
	//防止重复设置管理员
	for(var i=0;i<Mgmt.account.length;i++){
		if (account==Mgmt.account[i]) {
			$(".alert.alert-danger").show().find("label").html("该人员已经是管理员了！");
			return;
		}
	}
	Mgmt.account.push(account);
	Mgmt.manager = {
		photo:photo,
		account:account,
		nick_name:nick_name
	};
	$(".btn.purple-plum").attr("disabled","true");
	$.getJSON(url,function(json){
		$(".btn.purple-plum").attr("disabled","false");
		if (json.returncode=="0000") {
			$(el).parent().parent().remove();
			var html = 
			"<div class=\"mgmt\">"
				+"<img title=\"删除管理员\" src=\"images/error.png\" class=\"hide\" style=\"margin-left: 62px;cursor:pointer;margin-top: -5px;"
				+"position: absolute;z-index: 99;\" onclick=\"Mgmt.removeMgmtor(this,'"+Mgmt.manager.account+"');\">"
				+"<img src=\""+Mgmt.manager.photo+"\" class='img-circle' style=\"margin: 0 8px;\">"
				+"<p style=\"margin-top:10px;\">"+Mgmt.manager.nick_name+"</p>"
			+"</div>";

			$("#mgmtor").append(html);
			Mgmt._on();

		};
	});
}

Mgmt.hide = function(el){

	$(Mgmt.curId).find("img:first").addClass("hide");
}
Mgmt.show = function(el){
	
	$(Mgmt.curId).find("img:first").removeClass("hide");
}

//删除管理员
Mgmt.removeMgmtor = function(el,account){

	bootbox.confirm("你确定要删除该人员的管理员权限吗?", function(result) {
        if(result){
        	
           	var url = Index.server+"&jsoncallback=?&module=enterprise&action=delManager&params={\"staff\":\""+account+"\"}";
			$.getJSON(url, function(json) {
				if(json.returncode=="0000")
		    	{
		    		Mgmt.count--;
		    		$(el).parent().remove();
		    	}else{
	    			bootbox.alert("删除失败，请稍后操作！"); 
		    	}
			});

        }
	}); 
}


var getEventCoord = function( e )
{
	var evt = e||event, d = document,
	scrollEl = /^b/i.test( d.compatMode ) ? d.body : d.documentElement,
	supportPage = typeof evt.pageX == 'number',
	supportLayer = typeof evt.layerX == 'number';
	return {
		pageX : supportPage ? evt.pageX : evt.clientX + scrollEl.scrollLeft,
		pageY : supportPage ? evt.pageY : evt.clientY + scrollEl.scrollTop,
		clientX : evt.clientX,
		clientY : evt.clientY,
		layerX : supportLayer ? evt.layerX : evt.offsetX,
		layerY : supportLayer ? evt.layerY : evt.offsetY
	}
};
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

function getEvent(e) {  
    return e || window.event;  
} 
  
function contains(parentNode, childNode) {  
    if (parentNode.contains) {  
        return parentNode != childNode && parentNode.contains(childNode);  
    } else {  
        return !!(parentNode.compareDocumentPosition(childNode) & 16);  
    }  
}  
