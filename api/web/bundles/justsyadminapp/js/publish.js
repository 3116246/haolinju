var publish = {
	publishid:"",
	search_url:"",
	search_url_id:"",
	edit_url:"",
	init_url:"",
	del_url:"",
	type:null,
	issend:0, //是否正在发送
	icon:new Object,
	staffobj:new Object,
	rowrecord:14,
	zdtype:new Array(),
	load:function(type,id){		
		this.publishid=id;
		this.type = type;
		//隐藏查询面板
		$(".publish_main").hide();
		$(".publish_edit").show();
		//总是清空
		$(".publish_edit .publish_search_text").val("");
		$(".mb_seluser>:last").removeClass("show");
		$(".publish_zdlx .publish_combox").val("0");
		$(".publish_edit_hint").html("");
		editorbycontent.text("");
		this.cancelCheck();
		if (id>0){
		  $.getJSON(this.search_url_id,{"id":this.publishid},function(datas){
		  	if (datas!=null){
		  		var data = datas.main;
		  		var selectname = "";
		  		if ( data!=null && data.length>0){
		  			$(".publish_edit .publish_search_text").val(data[0]["title"]);
		  			editorbycontent.html(data[0]["content"]);
		  			$(".publish_edit .publish_zdlx .publish_combox").val(data[0]["zdtype"]);
		  		}
		  		data = datas.child;
		  		if ( data!=null && data.length>0){
		  			setCheckBox(data);
		  		}
		  	}
		  });
		}
	},
	//取消息所有被选择的复选框
	cancelCheck:function(){
		var tree = $.fn.zTree.getZTreeObj("treezzjg");
		if (tree != null)
		  tree.checkAllNodes(false);		
	  tree = $.fn.zTree.getZTreeObj("treezjwd");
	  if (tree != null)
	     tree.checkAllNodes(false);
	  tree = $.fn.zTree.getZTreeObj("treeryfl");
	  if (tree != null)
	     tree.checkAllNodes(false);	 
	  $("#sel_ygh").val("");
	  $("#sel_noygh").val("");
	},
	InitData:function(data){
		 var html = new Array();
		 var dates = data.dates;
		 html.push("<option value='all'>全部日期</option>");
		 if ( dates!=null && dates.length>0){
		 	  for(var i=0;i<dates.length;i++){
		 	    html.push("<option value='"+dates[i].val+"'>"+dates[i].s_date+"</option>");
		 	  }
		 }
		 $("#search_date").html(html.join(""));
		 var html = new Array();
		 if ( this.type=="1"){
		 	 var zdtype = data.zdtype;
			 if (zdtype!=null && zdtype.length>0){
					for(var i=0;i<zdtype.length;i++){
						html.push("<option value='"+zdtype[i].codeid+"'>"+zdtype[i].name+"</option>");
					}
					$(".publish_edit .publish_zdlx .publish_combox").html(html.join(""));
					$("#search_zdtype").html(html.join(""));
			 }		 	 
		 }
		 else{
		 	 html.push("<option value='0'></option>");
		 	 html.push("<option value='1'>福利项目</option>");
		 	 html.push("<option value='2'>福利关怀</option>");
		 	 $("#search_zdtype").html(html.join(""));
		 	 $(".publish_edit .publish_zdlx .publish_combox").html(html.join(""));
		 }
		 this.fulldata(1,{ "dataSource":data.dataSource,"recordcount":data.recordcount });
	},
	searchData:function(pageindex){
		$(".publish_main .publish_search_empty").hide();
	  var searchtitle = $.trim($(".publish_search_area .publish_search_text").val());
	  var date = $(".publish_combox").val();
	  date = date=="all"?"":date;
	  var zdtype = $("#search_zdtype").val();
	  var parameter = {"type":this.type,"date":date,"title":searchtitle,"zdtype":zdtype,"pageindex":pageindex,"rowrecord":this.rowrecord};
	  $.post(this.search_url,parameter,function(data){
	  	 publish.fulldata(pageindex,data);
	  	 pageControl.status = false;
	  });
	},
	//填充数据
	fulldata:function(pageindex,datasorce){
		var data = datasorce.dataSource;
		var html = new Array();
		if ( data!=null && data.length>0){			
			$(".publish_main .publish_table").show();
			html.push("<div style='background-color:#ddd;'>");
			html.push("  <span class='publish_fileds_date'>发布日期</span>");
		  html.push("  <span class='publish_fileds_title' style='width:500px;'>发布标题</span>");
		  html.push("  <span class='publish_fileds_staff' style='width:80px;'>"+(this.type==1?"制度类型":"福利类型")+"</span>");
			html.push("  <span class='publish_fileds_staff'>发布人员</span>");
			html.push("  <span class='publish_fileds_view'>查看详细</span>");
			html.push("</div>");
			for(var i=0;i<data.length;i++){
			  html.push("<div class='data_row' publishid='"+data[i].id+"'>");
			  html.push("  <span class='publish_fileds_date'>"+data[i].date+"</span>");
		    html.push("  <span class='publish_fileds_title' style='width:500px;'>"+data[i].title+"</span>");
		    html.push("  <span class='publish_fileds_staff' style='width:80px;display:block;height:28px;'>"+data[i].typename+"</span>");
			  html.push("  <span class='publish_fileds_staff'>"+data[i].nick_name+"</span>");
			  html.push("  <div style='float:left;margin-top:4px;'><span class='mb_edition_button' title='编辑/查看' onclick=\"publish.load('"+this.type+"',"+data[i].id+")\"></span>");
			  html.push("  <span onclick='publish.Delete(this);' title='删除' class='mb_delete_button'></span></div>");			  
			  html.push("</div>");
		  }
		  $(".publish_main .publish_table").html(html.join(""));
		  //分页管理
		  if (pageindex==1){
				var record = parseInt(datasorce.recordcount);
				if (record>0 && record>publish.rowrecord){
					 pageControl.every = publish.rowrecord;
					 pageControl.maxIndex = 10;
					 pageControl.status = false;
					 pageControl.control = $(".publish_main .publish_table");
					 pageControl.totalIndex = Math.ceil(record /publish.rowrecord);
					 pageControl.container = $(".publish_main .publish_search_page");
					 pageControl.callback = function(index){
					 	  publish.searchData(index);
					 };
					 pageControl.setting();
					 $(".publish_main .publish_search_page").show();
				}
				else{
					$(".publish_main .publish_search_page").hide();
				}
		  }
		}
		else{
			$(".publish_main .publish_search_page").hide();
			$(".publish_main .publish_table").hide();
			$(".publish_main. publish_search_page").hide();
			$(".publish_main .publish_search_empty").show();
			html.push("<div><div style='margin:auto;width:200px;'><img src='"+this.icon.error+"' style='margin-right:2px;'><span>未查询到符合条件的数据！</span></div></div>");
			$(".publish_main .publish_search_empty").html(html.join(""));
			$(".publish_search_page").html("");
		}
		
	},
	checkSelUser:function(seluservalue){
		if (!seluservalue 
        || (seluservalue.zzjg.length == 0 && seluservalue.zjwd.length == 0
            && seluservalue.ryfl.length == 0 && seluservalue.ygh.length == 0 && seluservalue.noygh.length == 0 )) {
        this.showHint("请选择人员范围",this.icon.error);
        return false;
    }
    else if (seluservalue.ygh.length>0 || seluservalue.noygh.length>0){
    	var yg_number= seluservalue.ygh;
    	if ( yg_number!=null && yg_number!="" && String(yg_number).indexOf("@")>-1){
    		this.showHint("员工号格式错误！",this.icon.error);
    		return false;
    	}
    	var no_yg = seluservalue.noygh;
    	if ( no_yg!=null && no_yg!="" && String(no_yg).indexOf("@")>-1){
    		this.showHint("排除员工号格式错误！",this.icon.error);
    		return false;
    	}
    }
    return true;
	},
	showHint:function(message,icon){
		var html = "<img src='"+icon+"'><span>"+message+"</span>";
		$(".publish_edit_hint").html(html);
	},
	EditPublish:function(){
		//保存数据
    if (this.issend == 0) {
			var _title = $.trim($(".publish_edit .publish_search_text").val());
			if ( _title=="") {
				this.showHint($(".publish_edit .publish_search_text").attr("placeholder")+"，此项不允许为空！",this.icon.error);
				$(".publish_edit .publish_search_text").focus();
				return;
			}
			var zdtype = "";
			zdtype = $(".publish_edit .publish_zdlx .publish_combox").val();
			if(zdtype=="0"){
				 var hint = $(".publish_zdlx>span").text();
				 htint = "请选择"+ hint.replace("：","")+"选项！";
				 this.showHint(hint,this.icon.error);
			   $(".publish_edit .publish_zdlx .publish_combox").focus();
			   return;
			}
			var content = $.trim(editorbycontent.text());
		  if ( _title=="") {
				this.showHint("发布内容不允许为空，请输入！",this.icon.error);
				editorbycontent.focus();
				return;
			}		    	
    	var staffobj = mb_seluser.getSelValue();
    	//if ( !this.checkSelUser(staffobj)) return;
    	var parameter = { "publishid":this.publishid,"type":this.type,"title":_title,"content":content,"zdtype":zdtype,"staffobj":staffobj};
    	$(".mb_seluser>:last").removeClass("show");  //将人员选择区域折叠
    	$.post(this.edit_url,parameter,function(data){
    		 if ( data.success){
    		 	 publish.showHint("保存成功！",publish.icon.success);
    		 	 $(".publish_edit .publish_search_text").val("");
    		 	 if (publish.type=="1")
    		 	   $(".publish_edit .publish_zdlx .publish_combox").val(0);
    		 	 editorbycontent.text("");
    		 	 //变更查询面板信息
    		 	 var html = new Array();
  		 	 	 var table = $(".publish_main .publish_table");
  		 	 	 if (table.children.length==0){
  		 	 	 	 	html.push("<div style='background-color:#ddd;'>");
							html.push("  <span class='publish_fileds_date'>发布日期</span>");
							html.push("  <span class='publish_fileds_title'>发布标题</span>");
							html.push("  <span class='publish_fileds_content'>发布内容</span>");
							html.push("  <span class='publish_fileds_staff'>发布人员</span>");
							html.push("  <span class='publish_fileds_view'>查看详细</span>");
							html.push("</div>");								
							html.push("<div pulishid='"+data.table.id+"'>");
							html.push("  <span class='publish_fileds_date'>"+data.table.date+"</span>");
							html.push("  <span class='publish_fileds_title'>"+data.table.title+"</span>");
							html.push("  <span class='publish_fileds_content'>"+data.table.content+"</span>");
							html.push("  <span class='publish_fileds_staff'>"+data.table.nick_name+"</span>");
							html.push("  <a class='publish_fileds_view' onclick=\"publish.load('"+publish.type+"',"+data.table.id+")\">查&nbsp;&nbsp;看</a>");
							html.push("</div>");							
	            $(".publish_main .publish_table").html(html.join(""));
	            $(".publish_main .publish_table").show();
	            $(".publish_main .publish_search_empty").hide();
  		 	 	 }
  		 	 	 else if ( publish.publishid==0){
  		 	 	 	  html.push("<div pulishid='"+data.table.id+"'>");
							html.push("  <span class='publish_fileds_date'>"+data.table.date+"</span>");
							html.push("  <span class='publish_fileds_title'>"+data.table.title+"</span>");
							html.push("  <span class='publish_fileds_content'>"+data.table.content+"</span>");
							html.push("  <span class='publish_fileds_staff'>"+data.table.nick_name+"</span>");
							html.push("  <a class='publish_fileds_view' onclick=\"publish.load('"+publish.type+"',"+data.table.id+")\">查&nbsp;&nbsp;看</a>");
							html.push("</div>");							
	            $(".publish_main .publish_table>div").eq(1).before(html.join(""));
	         }
  		 	 	 else{
  		 	 	 	 table = table.find("div[pulishid='"+data.table.id+"']");
  		 	 	 	 table.find(".publish_fileds_date").text(data.table.date);
  		 	 	 	 table.find(".publish_fileds_title").text(data.table.title);
  		 	 	 	 table.find(".publish_fileds_content").text(data.table.content);
  		 	 	 	 table.find(".publish_fileds_staff").text(data.table.nick_name);  		 	 	 	 	 
  		 	 	 }	     		 	 	 	
  		 	 }
    		 else{
    		 	 publish.showHint(data.message,publish.icon.error);
    		 }
    	}).fail(function(){
    		 publish.showHint("保存超时，请重试！",publish.icon.waring);
    	});
    }
    else{
    	this.showHint("正在保存数据，请勿频繁操作！",this.icon.error);
    }
	},
	ResetContent:function(){
		showDialog.Query("","确定要重置当前所有内容吗?");
    showDialog.callback=function(result){
  	 if(result=="Yes"){
  	 	 $(".publish_search_text").val("");
  	 	 editorbycontent.text("");
  	 	 if (publish.type=="1")
    	   $(".publish_edit .publish_zdlx .publish_combox").val(0); 
  	 }
    };
	},
	//删除数据记录
	Delete:function(ev){
		var typename = "";
		if ( this.type=="1")
		   typename = "制度发布";
		else if ( this.type=="2")
			 typename = "福利发布";
	  else if (this.type=="3")
	  	 typename = "新闻发布";
	  var curid = $(ev).parents(".data_row").attr("publishid");
	  var curRow = $(ev).parents(".data_row");
		showDialog.Query("","确定要删除该条<span style='font-weight:bold;color:#0088cc;padding-left:2px;padding-right:2px;'>"+typename+"</span>记录吗?");
    showDialog.callback=function(result){
  	 if(result=="Yes"){
  	 	 $.post(publish.del_url,{"publishid":curid},function(data){
  	 	 	 if (data.returncode=="0000"){  	 	 	 	 
  	 	 	 	 showDialog.Success("操作成功","删除该条<span style='font-weight:bold;color:#0088cc;padding-left:2px;padding-right:2px;'>"+typename+"</span>记录成功！");
  	 	 	 	 showDialog.callback = function(res){
  	 	 	 	 	 if (result=="Yes") curRow.remove();
  	 	 	 	 };
  	 	 	 }
  	 	 	 else{
  	 	 	 	 showDialog.Success("操作失败",data.msg);
  	 	 	 	 showDialog.callback = null;
  	 	 	 }
  	 	 });
  	 }
    };
	}
};

var editorbycontent = null;
var fristload = true;//是否首次加载
var editorItems = ['undo', 'redo',
    '|', 'formatblock', 'fontname', 'fontsize', 'bold', 'forecolor', 'hilitecolor',
    '|', 'justifyleft', 'justifycenter', 'justifyright', 'justifyfull',
    '|', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent',
    '|', 'subscript', 'superscript',
    '|', 'cut', 'copy', 'paste', 'plainpaste', 'wordpaste',
    '|', 'image', 'quickformat', 'source', 'clearhtml','table','tabledelete',
    '|', 'preview', 'fullscreen'];
function loadEditor(){
	KindEditor.ready(function(K) {
	    editorbycontent = K.create('textarea[name="text_content_publish"]', {
	        width: "100%",
	        height: "200px",
	        minHeight: 200,
	        resizeType: 0,
	        filterMode: false,
	        urlType: "domain",
	        filePostName: "keImg",
	        imageTabIndex: 1,
	        //fileManagerJson: editor_image_upload,
	        uploadJson: editor_image_upload,
	        items: editorItems,
	        afterChange: function() {
	        	  var x=0;
	        }
	    });   
	    fristload = false;
	});
}