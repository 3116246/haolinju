
$(document).ready(function(){
	$(window).resize(function(){
		$("#mainDiv").height($("body").height() - $("#topDiv").height() - 2);

		var $tabs = $("#MainTabs"); 
		$(".ui-tabs-panel", $tabs).height($tabs.height()-$("li", $tabs).height()-6);
    });
    $(window).resize();

    $("#MainTabs").tabs({
			tabTemplate: "<li><a href='#{href}'>#{label}</a> <span class='ui-icon ui-icon-close'>Remove Tab</span></li>",
			panelTemplate: '<div style="width:100%; margin:0px; padding:0px;overflow:hidden;"><iframe style="width:100%; height:100%;" frameborder="0" width="100%" height="100%"></iframe></div>',
			add: function( event, ui ) {
			    var $tabs = $("#MainTabs");
				$(ui.panel).height($tabs.height()-$("li", $tabs).height()-6);
				$tabs.tabs("select", '#'+ui.panel.id);
			}
	});

	// close icon: removing the tab on click
	// note: closable tabs gonna be an option in the future - see http://dev.jqueryui.com/ticket/3924
	$( "#MainTabs span.ui-icon-close" ).live( "click", function() {
	    var $tabs = $("#MainTabs");
		var index = $("li", $tabs ).index( $( this ).parent());
        var divA = $($("li>a:eq("+index+")", $tabs).attr("href"), $tabs);
		$("iframe", divA).get(0).src="";
		$tabs.tabs( "remove", index );
	});

	$("#MainTabs").tabs( "remove", 0 );

	window.AddTab =  function(id, url, title, reset)
    {
        //基于某些**要求，关掉已打开的窗口
        //$( "#MainTabs span.ui-icon-close" ).click();
        
        var $tabs = $("#MainTabs");
	    var selDIV = $("div#"+id, $tabs).get(0);
	    if (selDIV)
	    {
	        $tabs.tabs("select", '#'+id);
	        if (reset) $("iframe", $("#"+id, $tabs)).get(0).src=host+"/"+url;
	    }
	    else
	    {
	        $tabs.tabs("add", "#"+id, title);
	        $("iframe", $("#"+id, $tabs)).get(0).src=host+"/"+url;
        }
    }
    window.RemoveCurrTab =  function()
    {
        var $tabs = $("#MainTabs");
        var index = $tabs.tabs('option', 'selected');
        var divA = $($("li>a:eq("+index+")", $tabs).attr("href"), $tabs);
		$("iframe", divA).get(0).src="";
        $tabs.tabs("remove", index);
    }
	
	window.zTreeOnClick = function (event, treeId, treeNode) {
		if (treeNode.m_url) AddTab(treeNode.id, treeNode.m_url, treeNode.name);
	};

    var zTreeSetting = {
    	data:{
    		simpleData:{
    			enable:true
    		}
    	},
		callback: {
			onClick: zTreeOnClick
		}
    };
 //    var zTreeNodes = [
	// 	{id:1, pId:0, name: "父节点1", open:true},
	// 	{id:11, pId:1, name: "子节点1子节点1"},
	// 	{id:12, pId:1, name: "子节点2"},
	// 	{id:121, pId:1, name: "子节点2"},
	// 	{id:122, pId:1, name: "子节点2"},
	// 	{id:123, pId:1, name: "子节点2"},
	// 	{id:124, pId:1, name: "子节点2"},
	// 	{id:125, pId:1, name: "子节点2"},
	// 	{id:126, pId:1, name: "子节点2"},
	// 	{id:127, pId:1, name: "子节点2"},
	// 	{id:1360, pId:1, name: "子节点2"},
	// 	{id:1361, pId:1, name: "子节点2"},
	// 	{id:1362, pId:1, name: "子节点2"},
	// 	{id:1363, pId:1, name: "子节点2"},
	// 	{id:1364, pId:1, name: "子节点2"},
	// 	{id:1365, pId:1, name: "子节点2"},
	// 	{id:1366, pId:1, name: "子节点2"},
	// 	{id:1367, pId:1, name: "子节点2"},
	// 	{id:"adsf", pId:"sdf", name: "子节点2"}
	// ];
	// var zTreeObj;
 //    zTreeObj = $.fn.zTree.init($("#treemenu"), zTreeSetting, zTreeNodes);

 	$.getJSON(url_getmenu, 
 		{t: new Date().getTime()}, 
 		function(data, textStatus) {
			$.fn.zTree.init($("#treemenu"), zTreeSetting, data.menus);
			if ( data.menus.length==1)
			{
			    var row = data.menus[0];
			    AddTab(row.id, row.m_url, row.name);
		  }
 		}
 	);
});

