
$(document).ready(function () {
	mb_seluser.init_zzjg();
	mb_seluser.init_zjwd();
	mb_seluser.init_ryfl();
});

var mb_seluser = {
	sel_zzjg : [],
	sel_zjwd : [],
	sel_ryfl : [],
	init_zzjg : function () {
		var zTreeSetting = {
	    	check:{
	    		enable:true,
	    		chkboxType:{ "Y": "s", "N": "s"}
	    	},
	    	data:{
	    		simpleData:{
	    			enable:true
	    		}
	    	},
	    	callback: {
				onCheck: this.zzjg_zTreeOnCheck
			}
	    };
	    $.getJSON(mb_seluser_urls.deptquery, 
	    	{ t:new Date().getTime() }, 
	    	function(data, textStatus) {
				$.fn.zTree.init($("#treezzjg"), zTreeSetting, data); 
	    });
	},
	zzjg_zTreeOnCheck : function (event, treeId, treeNode) {
		var allCheckedNodes = $.fn.zTree.getZTreeObj("treezzjg").getCheckedNodes();
    if (allCheckedNodes.length==0)
		  $("#btn_zzjg").removeClass("select_status");
		else
			$("#btn_zzjg").addClass("select_status");			
		mb_seluser.sel_zzjg = $.map(allCheckedNodes, function(item, index) {
			return item.id;
		});
	},
	txfilterzzjg_OnChange : function (sender) {
		var $sender = $(sender);
		var $treezzjg = $("#treezzjg");
		var v = $sender.val();
		if (v.length == 0)
		{
			$treezzjg.find("li").show();
		}
		else
		{
			$treezzjg.find("li").hide();
			$treezzjg.find("a[title*='" + v + "']").parent().show().parents("li").show();
		}
	},
	init_zjwd : function () {
		var zTreeSetting = {
	    	check:{
	    		enable:true
	    		//chkboxType:{ "Y": "s", "N": "s"}
	    	},
	    	data:{
	    		 simpleData:{
	    		 	enable:true
	    		 }
	    	},
	    	callback: {
				onCheck: this.zjwd_zTreeOnCheck
			}
	    };
	    var data = [
			{id:"M0601", name:"董事长级类", open:false, children:[
				{id:"L1301", name:"集团董事长级"},
		  		{id:"L1201", name:"集团行政总裁级 "}
			]},
	  		{id:"M0501", name:"总裁级类", open:false, children:[
		  		{id:"L1101", name:"总裁级"},
		  		{id:"L1001", name:"副总裁级"},
		  		{id:"L0901", name:"总裁助理级 "},
	  			{id:"L0101", name:"员工级", open:false, children:[
					{id:"P1001", name:"业务副总裁"},
			  		{id:"P0901", name:"业务总裁助理"}
	  			]}
	  		]},
	  		{id:"M0401", name:"总监级类", open:false, children:[
		  		{id:"L0801", name:"总监级 "},
		  		{id:"L0701", name:"副总监级 "},
	  			{id:"L0101", name:"员工级", open:false, children:[
			  		{id:"P0801", name:"业务总监"},
			  		{id:"P0701", name:"业务副总监"}
	  			]}
	  		]},
	  		{id:"M0301", name:"经理级类", open:false, children:[
		  		{id:"L0601", name:"部门总经理级"},
		  		{id:"L0501", name:"部门副总经理级"},
		  		{id:"L0401", name:"部门总经理助理级"},
	  			{id:"L0101", name:"员工级", open:false, children:[
			  		{id:"P0601", name:"业务总经理"},
			  		{id:"P0501", name:"业务副总经理"},
			  		{id:"P0401", name:"业务总经理助理"}
	  			]}
	  		]},
	  		{id:"M0201", name:"主管级类", open:false, children:[
		  		{id:"L0301", name:"经理级"},
		  		{id:"L0201", name:"副经理级"},
	  			{id:"L0101", name:"员工级", open:false, children:[
			  		{id:"P0301", name:"业务经理"},
			  		{id:"P0201", name:"业务副经理"}
	  			]}
	  		]},
	  		{id:"M0101", name:"员工级类", open:false, children:[
	  			{id:"L0101", name:"员工级", open:false, children:[
			  		{id:"P0113", name:"员工三级"},
			  		{id:"P0112", name:"员工二级"},
			  		{id:"P0111", name:"员工一级"}
	  			]}
	  		]}
	    ];
		$.fn.zTree.init($("#treezjwd"), zTreeSetting, data); 
	},
	zjwd_zTreeOnCheck : function (event, treeId, treeNode) {
		mb_seluser.sel_zjwd = [];
		var allCheckedNodes = $.fn.zTree.getZTreeObj("treezjwd").getCheckedNodes();
		for (var i = 0; i < allCheckedNodes.length; i++) {
			var item = allCheckedNodes[i];
			var checkstatus = item.getCheckStatus();
			if (item.children && checkstatus.half) continue;
			var p = item.getParentNode();
			if (p && !p.getCheckStatus().half) continue;
			if (item.level == 0)
			{
				mb_seluser.sel_zjwd.push({
					zjlb : item.id
				});				
			}
			else if (item.level == 1)
			{
				mb_seluser.sel_zjwd.push({
					zjlb : item.getParentNode().id,
					glzj : item.id
				});
			}
			else if (item.level == 2)
			{
				mb_seluser.sel_zjwd.push({
					zjlb : item.getParentNode().getParentNode().id,
					glzj : item.getParentNode().id,
					ywzj : item.id
				});
			}
		}
		if ( mb_seluser.sel_zjwd.length==0)
		  $("#btn_zjwd").removeClass("select_status");
		else
			$("#btn_zjwd").addClass("select_status");
	},
	init_ryfl : function () {
		var zTreeSetting = {
	    	check:{
	    		enable:true
	    	},
	    	data:{
	    		 simpleData:{
	    		 	enable:true
	    		 }
	    	},
	    	callback: {
				onCheck: this.ryfl_zTreeOnCheck
			}
	    };
	    var data = [
		    {id:"01", name:"行政类", open:false, children:[
		    	{id:"01", name:"专业技术类", open:false, children:[
		    		{id:"01", name:"工程管理类"},
		    		{id:"02", name:"产品开发类", open:false, children:[
		    			{id:"01", name:"产品设计类"},
		    			{id:"02", name:"商品企划类"},
		    			{id:"03", name:"生产类"},
		    			{id:"04", name:"产品管理类"}
		    		]},
		    		{id:"03", name:"销售支持类", open:false, children:[
		    			{id:"01", name:"店铺设计类"},
		    			{id:"02", name:"商品管理类"},
		    			{id:"03", name:"店铺发展类"},
		    			{id:"04", name:"视觉陈列类"},
		    			{id:"05", name:"品牌营销类"}
		    		]},
		    		{id:"04", name:"销售管理类", open:false, children:[
		    			{id:"01", name:"零售管理类"},
		    			{id:"02", name:"客户服务类"}
		    		]},
		    		{id:"05", name:"物流类"},
		    		{id:"06", name:"职能类", open:false, children:[
		    			{id:"01", name:"战略类"},
		    			{id:"02", name:"证券法务金融类"},
		    			{id:"03", name:"人力资源类"},
		    			{id:"04", name:"审计类"},
		    			{id:"05", name:"财务类"},
		    			{id:"06", name:"信息技术类"},
		    			{id:"07", name:"行政支持类"},
		    			{id:"08", name:"博物馆类"},
		    			{id:"09", name:"审批类"},
		    			{id:"10", name:"综合采购类"}
		    		]}
		    	]},
		    	{id:"02", name:"操作类", open:false, children:[
		    		{id:"01", name:"物流操作类"},
		    		{id:"02", name:"行政后勤类"},
		    		{id:"03", name:"工艺操作类"}
		    	]}
		    ]},
		    {id:"02", name:"店铺类", open:false, children:[
		    	{id:"01", name:"店铺管理类", open:false, children:[
		    		{id:"01", name:"店经理"},
		    		{id:"02", name:"店经理助理"},
		    		{id:"03", name:"店长"}
		    	]},
		    	{id:"02", name:"店铺专业技术类", open:false, children:[
		    		{id:"01", name:"陈列专员"},
		    		{id:"02", name:"商品专员"},
		    		{id:"03", name:"培训专员"},
		    		{id:"04", name:"内务专员"}
		    	]},
		    	{id:"03", name:"店铺店员类", open:false, children:[
		    		{id:"01", name:"店助"},
		    		{id:"02", name:"导购"},
		    		{id:"03", name:"收银"},
		    		{id:"04", name:"试衣专管"},
		    		{id:"05", name:"仓管"},
		    		{id:"06", name:"时尚顾问"},
		    		{id:"07", name:"陈列助手"},
		    		{id:"08", name:"客服专员"}
		    	]},
		    	{id:"04", name:"店铺后勤类", open:false, children:[
		    		{id:"01", name:"保安"},
		    		{id:"02", name:"保洁"},
		    		{id:"03", name:"改裤"},
		    		{id:"04", name:"电工"}
		    	]}
		    ]}
	    ];
		$.fn.zTree.init($("#treeryfl"), zTreeSetting, data); 
	},
	ryfl_zTreeOnCheck : function (event, treeId, treeNode) {
		mb_seluser.sel_ryfl = [];
		var allCheckedNodes = $.fn.zTree.getZTreeObj("treeryfl").getCheckedNodes();
		for (var i = 0; i < allCheckedNodes.length; i++) {
			var item = allCheckedNodes[i];
			var checkstatus = item.getCheckStatus();
			if (item.children && checkstatus.half) continue;
			var p = item.getParentNode();
			if (p && !p.getCheckStatus().half) continue;
			if (item.level == 0)
			{
				mb_seluser.sel_ryfl.push({
					level1 : item.id
				});				
			}
			else if (item.level == 1)
			{
				mb_seluser.sel_ryfl.push({
					level1 : item.getParentNode().id,
					level2 : item.id
				});
			}
			else if (item.level == 2)
			{
				mb_seluser.sel_ryfl.push({
					level1 : item.getParentNode().getParentNode().id,
					level2 : item.getParentNode().id,
					level3 : item.id
				});
			}
			else if (item.level == 3)
			{
				mb_seluser.sel_ryfl.push({
					level1 : item.getParentNode().getParentNode().getParentNode().id,
					level2 : item.getParentNode().getParentNode().id,
					level3 : item.getParentNode().id,
					level4 : item.id
				});
			}
		}
	  if ( mb_seluser.sel_ryfl.length==0)
		  $("#btn_ryfl").removeClass("select_status");
		else
			$("#btn_ryfl").addClass("select_status");
	},
	displayResult: function () {
		var t = "";
		if (mb_seluser.sel_zzjg.length > 0) t += "{组织机构: ...}";
		if (mb_seluser.sel_zjwd.length > 0) t += "{职级维度: ...}";
		if (mb_seluser.sel_ryfl.length > 0) t += "{人员分类: ...}";
		if ($("#sel_ygh").val().length > 0) t += "{员工号: ...}";
		if ($("#sel_noygh").val().length > 0) t += "{排除员工号: ...}";

		$(".mb_seluser .result").text("[" + t + "]");
	},
	getSelValue : function () {
		return {
			zzjg : mb_seluser.sel_zzjg,
			zjwd : mb_seluser.sel_zjwd,
			ryfl : mb_seluser.sel_ryfl,
			ygh  : $("#sel_ygh").val().split(/[,;，；\n ]/).filter(function(item){return item != "";}),
			noygh: $("#sel_noygh").val().split(/[,;，；\n ]/).filter(function(item){return item != "";})
		};
	}
};