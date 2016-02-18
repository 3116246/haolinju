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
	    		chkboxType:{ "Y": "s", "N": "ps"}
	    	},
	    	data:{
	    		simpleData:{
	    			enable:true
	    		}
	    	},
	    	callback: {
				  onCheck: this.zzjg_zTreeOnCheck,
			  }

	    };
	    $.getJSON(mb_seluser_urls.deptquery, 
	    	{ t:new Date().getTime() }, 
	    	function(data, textStatus) {
				  $.fn.zTree.init($("#treezzjg"), zTreeSetting, data);
				  $.fn.zTree.getZTreeObj("treezzjg").expandAll(false);
	      }	      
	    );
	},
	zzjg_zTreeOnCheck : function (event, treeId, treeNode) {
		if ( treeNode.checked && treeNode.pId==null && treeNode.isParent){
			mb_seluser.sel_zzjg = [];
			$("#btn_zzjg").addClass("select_status");
			mb_seluser.sel_zzjg.push(treeNode.id);
		}
		else{
			var allCheckedNodes = $.fn.zTree.getZTreeObj("treezzjg").getCheckedNodes();
			if (allCheckedNodes.length==0)
			  $("#btn_zzjg").removeClass("select_status");
			else
				$("#btn_zzjg").addClass("select_status");
			mb_seluser.sel_zzjg = $.map(allCheckedNodes, function(item, index) {
				return item.id;
			});
	  }
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
	    	},
	    	data:{	    		
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
		var txt_zjwd = "";
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
	    	},
	    	callback: {
				onCheck: this.ryfl_zTreeOnCheck
			}
	    };
	    var data = [
		    {id:"01", name:"行政类", open:false, children:[
		    	{id:"01-01", name:"专业技术类", open:false, children:[
		    		{id:"01-01-01", name:"工程管理类"},
		    		{id:"01-01-02", name:"产品开发类", open:false, children:[
		    			{id:"01-01-02-01", name:"产品设计类"},
		    			{id:"01-01-02-02", name:"商品企划类"},
		    			{id:"01-01-02-03", name:"生产类"},
		    			{id:"01-01-02-04", name:"产品管理类"}
		    		]},
		    		{id:"01-01-03", name:"销售支持类", open:false, children:[
		    			{id:"01-01-03-01", name:"店铺设计类"},
		    			{id:"01-01-03-02", name:"商品管理类"},
		    			{id:"01-01-03-03", name:"店铺发展类"},
		    			{id:"01-01-03-04", name:"视觉陈列类"},
		    			{id:"01-01-03-05", name:"品牌营销类"}
		    		]},
		    		{id:"01-01-04", name:"销售管理类", open:false, children:[
		    			{id:"01-01-04-01", name:"零售管理类"},
		    			{id:"01-01-04-02", name:"客户服务类"}
		    		]},
		    		{id:"01-01-05", name:"物流类"},
		    		{id:"01-01-06", name:"职能类", open:false, children:[
		    			{id:"01-01-06-01", name:"战略类"},
		    			{id:"01-01-06-02", name:"证券法务金融类"},
		    			{id:"01-01-06-03", name:"人力资源类"},
		    			{id:"01-01-06-04", name:"审计类"},
		    			{id:"01-01-06-05", name:"财务类"},
		    			{id:"01-01-06-06", name:"信息技术类"},
		    			{id:"01-01-06-07", name:"行政支持类"},
		    			{id:"01-01-06-08", name:"博物馆类"},
		    			{id:"01-01-06-09", name:"审批类"},
		    			{id:"01-01-06-10", name:"综合采购类"}
		    		]}
		    	]},
		    	{id:"01-02", name:"操作类", open:false, children:[
		    		{id:"01-02-01", name:"物流操作类"},
		    		{id:"01-02-02", name:"行政后勤类"},
		    		{id:"01-02-03", name:"工艺操作类"}
		    	]}
		    ]},
		    {id:"02", name:"店铺类", open:false, children:[
		    	{id:"02-01", name:"店铺管理类", open:false, children:[
		    		{id:"02-01-01", name:"店经理"},
		    		{id:"02-01-02", name:"店经理助理"},
		    		{id:"02-01-03", name:"店长"}
		    	]},
		    	{id:"02-02", name:"店铺专业技术类", open:false, children:[
		    		{id:"02-02-01", name:"陈列专员"},
		    		{id:"02-02-02", name:"商品专员"},
		    		{id:"02-02-03", name:"培训专员"},
		    		{id:"02-02-04", name:"内务专员"}
		    	]},
		    	{id:"02-03", name:"店铺店员类", open:false, children:[
		    		{id:"02-03-01", name:"店助"},
		    		{id:"02-03-02", name:"导购"},
		    		{id:"02-03-03", name:"收银"},
		    		{id:"02-03-04", name:"试衣专管"},
		    		{id:"02-03-05", name:"仓管"},
		    		{id:"02-03-06", name:"时尚顾问"},
		    		{id:"02-03-07", name:"陈列助手"},
		    		{id:"02-03-08", name:"客服专员"}
		    	]},
		    	{id:"02-04", name:"店铺后勤类", open:false, children:[
		    		{id:"02-04-01", name:"保安"},
		    		{id:"02-04-02", name:"保洁"},
		    		{id:"02-04-03", name:"改裤"},
		    		{id:"02-04-04", name:"电工"}
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
	},
	getSelValue : function () {
		return {
			zzjg : mb_seluser.sel_zzjg,
			zjwd : mb_seluser.sel_zjwd,
			ryfl : mb_seluser.sel_ryfl,
			ygh  : $("#sel_ygh").val().split(/[,;，；\n ]/).filter(function(item){return item != "";}),
			noygh: $("#sel_noygh").val().split(/[,;，；\n ]/).filter(function(item){return item != "";})
		};
	},
	selected:function(ev){
		if ($(ev).siblings(".popover").is(":visible")){
			$(".selcontent .popover").removeClass("show");
		}
		else {
			$(".selcontent .popover").removeClass("show");
			$(ev).siblings('.popover').addClass("show");
		}
	}
};

var zjwdtext=new Array(),ryfltext=new Array();

//设置选择的复选框
function setCheckBox(data){
	var tree_zzjg = $.fn.zTree.getZTreeObj("treezzjg");
	if (tree_zzjg != null)
		  tree_zzjg.checkAllNodes(false);		
	var tree_zjwd = $.fn.zTree.getZTreeObj("treezjwd");
	if (tree_zjwd != null)
	   tree_zjwd.checkAllNodes(false);
	var tree_ryfl = $.fn.zTree.getZTreeObj("treeryfl");
	if (tree_ryfl != null)
	   tree_ryfl.checkAllNodes(false);
	$("#btn_zzjg").removeClass("select_status");
	$("#btn_zjwd").removeClass("select_status");
	$("#btn_ryfl").removeClass("select_status");
	$("#sel_ygh").val("");
	$("#sel_noygh").val("");
	zjwdtext = [];
	ryfltext = [];
	if ( data!=null && data.length>0){
		var selectname1="",selectname2="",selectname3="";
		var zzjg_name = new Array();
		var zjwd_name = new Array();
		var select_ygh = new Array();
		var select_noygh = new Array();
		mb_seluser.sel_zzjg = [];
		mb_seluser.sel_zjwd = [];
		mb_seluser.sel_ryfl = [];
		for(var i=0;i<data.length;i++){
		  var	level1=data[i].level1;
			var level2=data[i].level2;			
			var level3=data[i].level3;
			var level4=data[i].level4;
			var type = data[i].type;
			var parenNode = new Array();
			if (type=="1"){
				 $("#btn_zzjg").addClass("select_status");
				 if (level1 != null)
				   mb_seluser.sel_zzjg.push(level1);
				 var treeNode = tree_zzjg.getNodeByParam("id", level1, null);
				 if ( treeNode!=null && treeNode.pId==null && treeNode.isParent){
				 	 tree_zzjg.checkAllNodes(true);
		     }				 
         else if (treeNode!=null){
           treeNode.checked = true;
           zzjg_name.push(treeNode.name);
           tree_zzjg.updateNode(treeNode);
         }
			}
			else if (type=="2"){ //职级维度
				$("#btn_zjwd").addClass("select_status");
				var zjwdobj = new Object;
				if ( level1 != null)
				  zjwdobj.zjlb = level1;
				if ( level2 != null )
				  zjwdobj.glzj = level2;
				if ( level3 != null )
				  zjwdobj.ywzj = level3;
				mb_seluser.sel_zjwd.push(zjwdobj);				
				setCheckboxSelect(tree_zjwd,level1,level2,level3,level4);
			}
			else if (type=="3"){
				$("#btn_ryfl").addClass("select_status");
				var ryflobj = new Object;
				if ( level1 != null)
				  ryflobj.levle1 = level1;
				if ( level2 != null)
				  ryflobj.levle2 = level2;
				if ( level3 != null)
				  ryflobj.levle3 = level3;
				if ( level4 != null)
				  ryflobj.levle4 = level4;
				mb_seluser.sel_ryfl.push(ryflobj);
				setCheckboxSelect(tree_ryfl,level1,level2,level3,level4);
			}
			else if (type=="4"){
				select_ygh.push(level1);			
			}	
			else if (type=="5"){
				select_noygh.push(level1);
			}					
		}
		if (select_ygh.length >0)
		  $("#sel_ygh").val(select_ygh.join(";"));
		if (select_noygh.length >0)
		  $("#sel_noygh").val(select_noygh.join(";"));		
	}
};

function setCheckboxSelect(tree,level1,level2,level3,level4){
	 var treeid = tree.setting.treeId;
	 var treeNode1 = tree.getNodeByParam("id",level1,null);
	 var treeNode2=null,treeNode3=null,treeNode4=null;
	 if (treeNode1!=null){
	   if (!treeNode1.checked){
			 treeNode1.checked=true;
			 tree.updateNode(treeNode1);
			 if ( treeNode1.isParent && treeid=="treezjwd" )
			   zjwdtext.push("{"+treeNode1.name+"}");
			 if ( treeNode1.isParent && treeid=="treeryfl" )
			   ryfltext.push("{"+treeNode1.name+"}");
		 }
		 if (level2!=null){
		    treeNode2 = tree.getNodeByParam("id",level2,null);
				if (!treeNode2.checked){
				  treeNode2.checked=true;
					tree.updateNode(treeNode2);	
				}
				if (treeNode2.isParent){
				  if ( treeid=="treezjwd" )			       
			       zjwdtext.push("{"+treeNode2.name+"}");
			    if ( treeid=="treeryfl" )
			       ryfltext.push("{"+treeNode2.name+"}");
				  if (level3!=null){
					  treeNode3 = tree.getNodeByParam("id",level3,null);
					  if (treeNode3!=null && !treeNode3.checked){
					  	 treeNode3.checked=true;
					   	 tree.updateNode(treeNode3);
					  }
					  if (treeNode3!=null && treeNode3.isParent){
				      if ( treeid=="treezjwd" )			       
			          zjwdtext.push("{"+treeNode2.name+"}");
			        if ( treeid=="treeryfl" )
			          ryfltext.push("{"+treeNode2.name+"}");
					  	if(level4!=null && level4!=""){
					  		 treeNode4 = tree.getNodeByParam("id",level4,null);
					  		 if (!treeNode4.checked){
					  		 	 treeNode4.checked=true;
					   	     tree.updateNode(treeNode4);
					  		 }
					  	}
					  	else{
					  		tree.checkNode(treeNode3, true, true);
					  	}
					  }
					}
					else{
					  tree.checkNode(treeNode2, true, true);	
					}
				}			   
		 }
		 else{
		 	 tree.checkNode(treeNode1, true, true);
		 }
	 }
}

//判断是否选择了人员范围信息
function checkstaffarea()
{
	var staffval = mb_seluser.getSelValue();
	if (!staffval || (staffval.zzjg.length == 0 && staffval.zjwd.length == 0
          && staffval.ryfl.length == 0 && staffval.ygh.length == 0)) {
      
      return false;
  }
    return true;
}