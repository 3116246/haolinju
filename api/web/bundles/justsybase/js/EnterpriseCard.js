var HashTable=function(){
	this.length=0;
	this.array=new Array();
	this.get=function(key){
		for(var i=0;i<this.array.length;i++){
			if(this.array[i].key==key){
				return this.array[i].val;
			}
		}
		return null;
	};
	this.push=function(key,val){
		var obj={'key':key,'val':val};
		this.array.push(obj);
		this.length++;
	};
	this.clear=function(){
		this.array=[];
		this.length=0;
	};
};
var EnterpriseCard={ 
	 hoverTimer:null,
	 outTimer:null,
	 _circle_id:"",
	 _ajaxObj:null,
	 EnterpriseCards :[],
	 modalDlag:null,
	 Aurl:"",
	 Attenurl:"",
	 
	 bind:function()
	 {
			//鼠标移除层区域后，触发mouseout事件，把整个层隐藏  
			$('.enterprise_name').live('mouseout', function(e) {
				  
			    if(checkHover(e,this)){
			    	   clearTimeout(EnterpriseCard.hoverTimer);
			         EnterpriseCard.outTimer = setTimeout("EnterpriseCard.hide()",500);
			    }
			});
			$('.enterprise_name').live('mouseover',function(e) {  
			        	  clearTimeout(EnterpriseCard.outTimer);
			            if(checkHover(e,this)){
			            	  var ex = getEventCoord(e);			  
//			            	  var txt = $(this).text();  
			            	  var acc = $(this).attr("circle_id");
			            	  var attencall=$(this).attr("attencall");
			            	  if(typeof(attencall)!='undefined')
			            	  {
			            	  	EnterpriseCard._attencall.add(this,attencall);
			            	  }	  
                      if (!$(this).attr("href")) $(this).attr("href", "javascript:;");
			                EnterpriseCard.hoverTimer = setTimeout(" EnterpriseCard.show("+(ex.pageX)+","+(ex.pageY)+",'"+acc+"')",500);
			            }
			});
			//关注或取消关注
			$(".atten_eno_ornot").live('click',function(){
				var $this=$(this),_t=$this.attr('atten');
				if($this.attr('commiting')=="1") return;
				$this.text('提交中...').attr("commiting","1");
				$.post(EnterpriseCard.Attenurl,{eno:EnterpriseCard._circle_id,atten:_t},function(d){					
					if(d.s=='1')
					{
						if(_t=='1')
							$this.text('已关注').attr('atten','0');
						else{
							$this.text('关注').attr('atten','1');
						  $this.attr('commiting',"0");
						}
						EnterpriseCard.EnterpriseCards[EnterpriseCard._circle_id]=null;
					}
					else{
					}
					EnterpriseCard._attencall.execute(_t,d.s);
				});
			});	
	 },
	 
	 load:function(_Aurl,_Attenurl)
	 {
	 	  this.Aurl=_Aurl;
	 	  this.Attenurl=_Attenurl;
	 	  var _cx = $("#eno_card_dlag");
	 	  if(_cx.length==0)
	 	  {
	 	  	_cx = document.createElement("DIV");
	 	  	_cx.id="eno_card_dlag";
	 	  	_cx.className = "modal";
	 	  	with(_cx.style){
	 	  		width="411px";
	 	  		display="none";
	 	  		padding="0px";
	 	  		overflow="hidden";
	 	  		borderRadius="0px 0px 0px 0px";
	 	  		zIndex = 1000000;
	 	  	}
	 	  	_cx.innerHTML="<div id='eno_card_dlag_body' class='modal-body' style='overflow:hidden;padding:0px;'></div>";
	 	  	document.body.appendChild(_cx);
	 	  	this.modalDlag = $("#eno_card_dlag"); 
	 	  	if(this.modalDlag.modal==null) return;
	 	  	this.modalDlag.modal({show:false,backdrop:false});
	 	  	this.modalDlag.on('shown', {Aurl: _Aurl}, EnterpriseCard.getInfo);	 
	 	  }
			this.modalDlag.live('mouseout', function(e) {
				  clearTimeout(EnterpriseCard.hoverTimer);
			    if(checkHover(e,this)){
			         EnterpriseCard.outTimer = setTimeout("EnterpriseCard.hide()",500);
			    }
			});  
			this.modalDlag.live('mouseover',function(e) {  
			        	  clearTimeout(EnterpriseCard.outTimer);			            
			});
			//-----------------------------------------------------------------
			//绑定所有人员姓名标签事件、样式及状态切换
			//-----------------------------------------------------------------
      this.bind();
	 },
	 _attencall:{
	 		_ct:new HashTable(),
	 		contains:function(_e){
	 			var re=this._ct.get(_e);
	 			return re==null?false:true;
	 		},
	 		execute:function(atten,d){
	 			for(var i=0;i<this._ct.length;i++)
	 			{
		 			var re=this._ct.array[i].val;
		 			if(re!=null)
		 				eval(re+"('"+atten+"','"+d+"','"+EnterpriseCard._circle_id+"')");
		 			this._ct.clear();
		 		}
	 		},
	 		add:function(k,v)
	 		{
	 			this._ct.clear();
	 			this._ct.push(k,v);
	 		}
	 	},
	 getInfo:function(para){
	 	  $("#eno_card_dlag_body").html("");
			if (EnterpriseCard.EnterpriseCards[EnterpriseCard._circle_id]==null)
			{
				    $("#eno_card_dlag .modal-footer").css({"display":"none"});
			      $("#eno_card_dlag_body").append("<div class='urlloading'><div /></div>");
				    EnterpriseCard._ajaxObj=$.get( para.data.Aurl, {eno : EnterpriseCard._circle_id, t: new Date().getTime()},
				    function (d) 
				    {
				    	EnterpriseCard._ajaxObj=null;
				    	if(d.length==0) return;
				    	$("#eno_card_dlag_body").html(d);
				    	
				      $("#eno_card_dlag_body urlloading").remove();
				      $("#eno_card_dlag .modal-footer").css({"display":"","padding":"5px"});		
				      EnterpriseCard.autoXY();
							EnterpriseCard.EnterpriseCards[EnterpriseCard._circle_id] = $("#eno_card_dlag_body").html();
					 	});
			}
			else
			{
			    $("#eno_card_dlag_body").html(EnterpriseCard.EnterpriseCards[EnterpriseCard._circle_id]);
          EnterpriseCard.autoXY();
			}
	 },
	 autoXY:function(){
	 	var tmpDlg = 		  EnterpriseCard.modalDlag; 
	  var t =tmpDlg.attr("y")*1,l =tmpDlg.attr("x")*1,ch = tmpDlg.height();
		t=t>((self.innerHeight||$(self).height())-ch)?t-ch-15:t+10;
		l = l<150?l+20:l;
		l = l>((self.innerWidth||$(self).width())-150)?l-300:l-150;
		tmpDlg.css({"top":t,"left":l});		 	 	
	 },
	 show:function(x,y,account){
	 	  this._circle_id = account;
	 	  var l = x-($(document).scrollLeft()),t = y-$(document).scrollTop();
	 	  EnterpriseCard.modalDlag.attr("x",l);
	 	  EnterpriseCard.modalDlag.attr("y",t);
	 	  EnterpriseCard.modalDlag.css({"top":t,"left":l,"margin":0,"z-index":312323232});	 	      	
	    if(EnterpriseCard.modalDlag.css("display")!="none")
	    {
	    	 if (EnterpriseCard.EnterpriseCards[EnterpriseCard._circle_id]!=null){
	    	    $("#eno_card_dlag_body").html(EnterpriseCard.EnterpriseCards[EnterpriseCard._circle_id]);
	    	    EnterpriseCard.autoXY();
	    	 }
	    	 else
	    	 	  EnterpriseCard.modalDlag.trigger("shown");
	    }
	    else
	       EnterpriseCard.modalDlag.modal("show");	
	 },
	 hide:function()
	 {
	 	  this._circle_id = "";
	 	  if(EnterpriseCard._ajaxObj!=null)
	 	  {
	 	  	  $("#eno_card_dlag_body urlloading").remove();
	 	  	  EnterpriseCard._ajaxObj.abort();//立即终止请求
	 	  }
	 	  $("#eno_card_dlag_body").html("");
			EnterpriseCard.modalDlag.modal("hide");
			if($(".circle_list").css("display")!="none")
			{
			    hidePanel($("#topmenu_circle_list"));	
			}
	 }
};