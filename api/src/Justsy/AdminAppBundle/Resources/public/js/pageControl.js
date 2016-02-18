var pageControl = {
	control:null,
	container:null,
	totalIndex:8,
	maxIndex:5,
	curIndex:1,
	callback:null,
	para:new Object,
	status: false,
	every:6,
	pageprev:function(){
		this.pagechange(this.curIndex - 1);
	},
	pagenext:function(){
		this.pagechange(this.curIndex + 1);
	},
	setting:function(){
		this.curIndex = 1;
		var html = new Array();
		if ( this.control != null)  //隐藏第二页开始的内容
		  this.control.slice(this.every).hide();
    if ( this.maxIndex > this.totalIndex ) this.maxIndex = this.totalIndex;    
		if ( this.totalIndex > this.maxIndex){
		   html.push("<span id='pageprev' onclick='pageControl.pageprev()' style='display:none;' class='pagesprev'><span class='pagesprev_arrow'></span>");
		   html.push("<sapn UNSELECTABLE='on' style='float:left;margin-right:2px;'>上一页</span></span>");
		}
		html.push("<div style='float:left;' id='pagearea'>");
		for(var i=1;i <= this.maxIndex;i++){
			if ( i==1)
			  html.push("<span class='pagestyle_active' value='" + i + "' onclick=\"pageControl.pagechange('"+i+"')\">"+ i+"</span>");
			else
				html.push("<span class='pagestyle' value='" + i + "' onclick=\"pageControl.pagechange('" + i +"')\">"+ i+"</span>");
		}
		html.push("</div>");
		if ( this.totalIndex > this.maxIndex)
		  html.push("<span id='pagenext' onclick='pageControl.pagenext()' class='pagesprev'><span UNSELECTABLE='on' style='float:left;margin-left:2px;'>下一页</span><span class='pagesnext_arrow'></span></span>");
		this.container.html(html.join(''));
		var _left = Math.ceil(this.maxIndex/2); //左边临界点
		var _right = this.totalIndex - ( this.maxIndex - 2) + 1;  //右边临界点
		this.para = { "left":_left,"right":_right,"middle":this.maxIndex-4 };		
	},
	pagechange:function(pageno) {		
	  if ( this.status ) return;
		if ( this.curIndex == pageno) return;
		this.curIndex = pageno;
		$("#pagearea .pagestyle_active").attr("class","pagestyle");
		var currentControl = $("#pagearea .pagestyle[value='" + pageno +"']");	
		if ( this.maxIndex <= this.totalIndex ) {
				if ( pageno==1)
					$("#pageprev").hide();
				else if ( pageno == this.totalIndex)
					$("#pagenext").hide();
				else if ( pageno<this.totalIndex && $("#pageprev").is(":hidden")) {
					$("#pageprev").show();
				}
				var index = currentControl.index() + 1;
				var start =0,end = 0;
				var html = new Array();
				if ( (index==1 && pageno>=this.maxIndex ) || ( index==this.maxIndex && pageno<=this.totalIndex) ){
					 start = pageno - Math.ceil(this.maxIndex/2);
					 end = start + 5;
				}
				else if ( index==1 && pageno<this.maxIndex){
					 start = 1;
					 end = start + 5;
					 if (end>this.maxIndex) end = this.maxIndex;
				}
				if ( end>=this.maxIndex){
					end = this.maxIndex;
					start = end-5;
				}
				if (   start>0 && end>0){
					while (start<=end){
						  html.push("<span class='pagestyle' value='" + start + "' onclick=\"pageControl.pagechange('"+start+"')\">"+ start +"</span>");
						  start = start + 1;
					}
					if ( html.length>0)
					  $("#pagearea").html(html.join(""));	
			  }
				currentControl = $("#pagearea .pagestyle[value='" + pageno +"']");
				currentControl.attr("class","pagestyle_active");
	  }
		if ( this.callback == null){
			currentControl.attr("class","pagestyle_active");
			this.control.hide();
  	  var start = (pageno-1) * this.every;
	    var end =   start + this.every;
		  this.control.slice(start,end).show();
		}
		else {
		   this.status = true;
			 this.callback(this.curIndex);//选中回调事件
		}
	}
}