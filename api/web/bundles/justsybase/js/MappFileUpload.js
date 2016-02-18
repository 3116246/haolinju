Array.prototype.contains=function(val){
	val=val.toUpperCase();
	for(var i=0;i<this.length;i++){
		if(this[i]==val)
			return true;
	}
	return false;
}
var CHashTable=function(){
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
	this.set=function(key,val){
		if(this.get(key)==null){
			this.push(key,val);
		}
		else{
			for(var i=0;i<this.array.length;i++){
				if(this.array[i].key==key){
					this.array[i].val=val;
				}
			}
		}
	};
	this.remove=function(key){
		var j=-1;
		for(var i=0;i<this.array.length;i++){
			
			if(this.array[i].key==key){
				j=i;
				break;
			}
		}
		if(j> -1){
			this.array.splice(j,1);
			this.length--;
		}
	}
};
var ComFileUpload=function(){
	this.maxCount=11;
	this.upload_url=null;//上传文件的地址
	this.loading=new CHashTable();//正在上传
	this.container=null;
	this.currfile=null;
	this.addfileloadimg_url="/bundles/fafatimewebase/images/addfileupload.png";//继续添加图片路径
	this.fileupload_url="/bundles/fafatimewebase/images/loadingsmall.gif";//ie下无法显示进度条替代方案
	this.imgprex=['JPG','PNG','GIF','JPEG','AG4','BMP','TIFF','TGA','EXIF'];
	this.excelprex=['XLSX','XLS'];
	this.rarprex=['RAR','ZIP'];
	this.docprex=['DOC','DOCX'];
	this.txtprex=['TXT','JS','SQL','CSS','HTM','HTML'];
	this.pptprex=['PPT'];
	this.FileUploadError=function(){};
	this.FileUploadSucess=function(){};
	this.FileRemoveEvent=function(){};
	this.cache=[];
	this.cCode=0;
}
ComFileUpload.prototype={
	init:function(params){
		this.container=params.container;
		this.upload_url=params.upload_url;
		if(params.FileUploadSucess){
			this.FileUploadSucess=params.FileUploadSucess;	
		}
		if(params.FileUploadError){
			this.FileUploadError=params.FileUploadError;
		}
		if(params.cache){
			this.cache=params.cache;
		}
		if(params.maxCount){
			this.maxCount=params.maxCount;
		}
		if(params.FileRemoveEvent){
			this.FileRemoveEvent=params.FileRemoveEvent;
		}
		if($(this.container).find("div.file_upload_box").length==0){
			var html=[];
			html.push("<div class='file_upload_box'><ul>");
			html.push("</ul></div>");
			$div=$(html.join(''));
			$(this.container).append($div);
		}
		else{
			$(this.container).find("div.file_upload_box").children("ul").html(null);
		}
		this.setCache();
		if(this.cache.length<this.maxCount)
			this.addFileLoad();
		//$(this.container).find('li:last').find("input[type='file']").remove();
		//$(this.container).find('li:last').find("div.add_file_load_div").append(this.currfile);
		//this.registerEvent();
		//this.sumitfile(this.currfile);
	},
	setCache:function(){
		if(this.cache.length==0)return;
		for(var i=0;i<this.cache.length;i++){
			addFileLoad();
			
		}
	},
	fileselect:function(e,func){
		var p=this;
		//判断是否能继续上传
		if($(p.container).find('li').length==p.maxCount && $(p.container).find('li:last').find("div.add_file_load_div").css('display').indexOf('none')> -1){
			return;
		}
		if(e!=null)
			this.currfile=e;
		this.currfile.removeAttribute('class');
		this.currfile.removeAttribute('style');
		this.currfile.removeAttribute('id');
		$(this.currfile).addClass('upload_file');
		$(this.container).find('li:last').find("input[type='file']").remove();
		$(this.container).find('li:last').find("div.add_file_load_div").append(this.currfile);
		func();
	},
	getCode:function(){
		this.cCode++;
		return this.cCode;
	},
	changeClass:function(t){
		if(t=='small'){
			var c=$(".file_upload_box,.uploadfilecontainer,.file_upload_box_li,.add_file_load_div,.add_file_loadimg,.add_more_file,.upload_file,.fileuploadone,.upload_abort,.file_thumb,.file_thumb_ie,.file_progress");
			for(var i=0;i<c.length;i++){
				$(c[i]).addClass($(c[i]).attr('class')+"_small");
			}
			$(this.container).find("input.upload_file").css('width','25px');
			$(this.container).find("span.add_more_file").find("a").text("上传");
			$(this.container).find("img.file_thumb[thumb='server']").css({
							'width':'30px',
							'height':'30px',
							'left':'10px',
							'top':'10px'
						});
		}
		else if(t=='normal'){
			var c=$(".file_upload_box,.uploadfilecontainer,.file_upload_box_li,.add_file_load_div,.add_file_loadimg,.add_more_file,.upload_file,.fileuploadone,.upload_abort,.file_thumb,.file_thumb_ie,.file_progress");
			for(var i=0;i<c.length;i++){
				if($(c[i]).attr('class').indexOf('_small')<0)continue;
				var cls=$(c[i]).attr('class').split(' ');
				$(c[i]).removeClass(cls[cls.length-1]);
			}
			$(this.container).find("input.upload_file").css('width','65px');
			$(this.container).find("span.add_more_file").find("a").text("文件上传");
			$(this.container).find("img.file_thumb[thumb='server']").css({
							'width':'50px',
							'height':'50px',
							'left':'15px',
							'top':'15px'
						});
		}
	},
	addFileLoad:function(){
		if($(this.container).find('li').length < this.maxCount){
			if($(this.container).find('li').length>6){
				if($(this.container).find('li').length==7){
					this.changeClass('small');
				}
				this.addFileLoadSmall();
				return;
			}
			var html=[];
			html.push("<li cCode='"+this.getCode()+"' class='file_upload_box_li'><form style='margin:0;'><input type='hidden' name='uploadSourcePage' value='home'/><div class='add_file_load_div'>");
			html.push("<img class='add_file_loadimg' src='"+this.addfileloadimg_url+"'/>");
			html.push("<span class='add_more_file'><a>文件上传</a></span>");
			html.push("<span style='display:none;' class='upload_abort'>×</span>");
			html.push("<input style='width:100%;height:100%;' name='filedata' type='file' class='upload_file'/></div>");
			html.push("<div style='display:none;' class='fileuploadone'>");
			html.push("<span style='display:none;' class='upload_abort'>×</span>");
			html.push("<img class='file_thumb' src=''/>");
			html.push("<div style='display:none;' class='file_thumb_ie'><img src='"+this.fileupload_url+"'/></div>");
			html.push("<div style='display:none;' class='file_progress'><div></div></div>");
			html.push("</form></li>");
			$li=$(html.join(''));
			$(this.container).find('ul').append($li);
		}
	},
	addFileLoadSmall:function(){
		if($(this.container).find('li').length < this.maxCount){
			var html=[];
			html.push("<li cCode='"+this.getCode()+"' class='file_upload_box_li file_upload_box_li_small'><form style='margin:0;'><input type='hidden' name='uploadSourcePage' value='home'/><div class='add_file_load_div add_file_load_div_small'>");
			html.push("<img class='add_file_loadimg add_file_loadimg_small' src='"+this.addfileloadimg_url+"'/>");
			html.push("<span class='add_more_file add_more_file_small'><a>上传</a></span>");
			html.push("<span style='display:none;' class='upload_abort upload_abort_small'>×</span>");
			html.push("<input style='width:100%;height:100%;' type='file' name='filedata' class='upload_file upload_file_small'/></div>");
			html.push("<div style='display:none;' class='fileuploadone fileuploadone_small'>");
			html.push("<span style='display:none;' class='upload_abort upload_abort_small'>×</span>");
			html.push("<img class='file_thumb file_thumb_small' src=''/>");
			html.push("<div style='display:none;' class='file_thumb_ie file_thumb_ie_small'><img src='"+this.fileupload_url+"'/></div>");
			html.push("<div style='display:none;' class='file_progress file_progress_small'><div></div></div>");
			html.push("</form></li>");
			$li=$(html.join(''));
			$(this.container).find('ul').append($li);
		}
	},
	fileTxtChange:function(){//当文件地址改变时
		
	},
	updateProgress:function(){//跟新进度条
		
	},
	del:function(){
		
	},
	registerEvent:function(){
		var p=this;
		//var obj=$(this.container);
		//var lis=obj.find("div.file_upload_box").find("li");
		//上传input位置控制
		$(".file_upload_box_li").live('mousemove',function(event){
			var ev=event||window.event;
			var up=$(this).find("input.upload_file");
			up.css({
				left:Math.max(0,((ev.clientX-$(this).offset().left)-(up.width()/2))).toString()+"px",
				top:Math.max(0,((ev.clientY-$(this).offset().top)-(up.height()/2))).toString()+"px"
			});
		});
		$(".file_upload_box_li").live('mouseout',function(){
			$this=$(this);
			$this.find("input.upload_file").css({'left':'0px','top':'0px'});
			//$this.find(".upload_abort").hide();
		});
		$(".file_upload_box_li").live('mouseover',function(){
			$this=$(this);
			if($this.find("div.fileuploadone").css('display').indexOf('none')< 0){
				//$this.find(".upload_abort").show();
			}
			else if($this.siblings("li").length==0){
				//$this.find(".upload_abort").show();
			}
		});
		//
		$("input.upload_file").live('change',function(){
			 if(p.showClientfile(this)==false) return;
			 p.sumitfile(this);
		});
		//删除
		$("span.upload_abort").live('click',function(){
			var li=$this.parent().parent().parent();
			p.willRemoveFile(li.attr('fileid'));
			return;
			$this=$(this);
			var li=$this.parent().parent().parent();
			if(li.siblings('li').length==0){
				$(p.container).slideUp();
    		$(p.container).children('div.file_upload_box').remove();
				return;
			}
			var ajax=p.loading.get(li.attr('cCode'));
			
			if(ajax!=null){
				ajax.ajaxStop();
				p.loading.remove(li.attr('cCode'));
				setPublishEnable();
			}
			else{
				var $hid=$("#filesList").find("input[type='hidden']");
				for(var i=0;i<$hid.length;i++){
					if($($hid[i]).val()==li.attr('fileid')){
						AttachClose_OnClick($($hid[i]).siblings('span.NotifyClose')[0]);
					}
				}
			}
			li.remove();
			if($(p.container).find('li').length==7){
				p.changeClass('normal');
			}
			if($(p.container).find('li').length==p.maxCount-1 && $(p.container).find('li:last').find("div.add_file_load_div").css('display').indexOf('none')> -1){
				p.addFileLoad();
			}
		});
	},
	willRemoveFile:function(fileid){
		this.FileRemoveEvent(fileid);
	},
	removeFile:function(fileid){
		var li=$(this.container).find("li[fileid='"+fileid+"']");
		if(li.length==0)return;
		var p=this;
		li.remove();
			if($(p.container).find('li').length==7){
				p.changeClass('normal');
			}
			if($(p.container).find('li').length==p.maxCount-1 && $(p.container).find('li:last').find("div.add_file_load_div").css('display').indexOf('none')> -1){
				p.addFileLoad();
			}
	},
	showClientfile:function(e){
		var filepath=e.value;
	  var prex=this.getDprex(filepath);
	  if(!this.imgprex.contains(prex)){
	  	this.FileUploadError("请选择一个图片文件！");
	  	return false;
	  }
		$(e).parent().find("input[type='file']").hide();
		$(e).parent().hide().siblings("div.fileuploadone").show();
		var li=$(e).parent().parent().parent();
		li.css({'border':'1px solid #CCC','background-color':'#F3F9FB'});
		this.setThumbFile(e);
	},
	showClientfile2:function(filepath){
		var e=$(this.container).find("input.upload_file:last")[0];
		$(e).parent().find("input[type='file']").hide();
		$(e).parent().hide().siblings("div.fileuploadone").show();
		var li=$(e).parent().parent().parent();
		li.css({'border':'1px solid #CCC','background-color':'#F3F9FB'});
		this.setThumbFile2(fileid);
	},
	setThumbFile2:function(fileid){
		var sender=$(this.container).find("input.upload_file:last")[0];
		var li=$(sender).parent().parent().parent();
		li.attr('filetype','img');
		li.find('img.file_thumb').attr('src',this.getUploadPath(fileid));
		li.attr('fileid',fileid);
	},
	getDprex:function(filepath){
		var arr=filepath.split('.');
		var prex=arr[arr.length-1];
		return prex;
	},
	finishload:function(li){
		var fileid=li.attr('fileid');
		if(this.FileUploadSucess(fileid)){
			this.removeFile(fileid);
			return;
		}
		li.find(".file_progress").hide();
		li.find(".file_thumb_ie").hide();
		if(li.attr('filetype')=='img'){
			li.find(".file_thumb").attr('src',this.getUploadPath(fileid)).css({'width':'100%','height':'100%','top':'0px','left':'0px'});
		}
	},
	getUploadPath:function(fileid){
		return "/getfile/image/small/"+fileid.toString();
//		var url=window.location.href;
//		if(url.indexOf('www.wefafa.com')>-1){
//			return 
//		}
//		else if(url.indexOf('we.fafatime.com')>-1){
//			
//		}
//		else if(url.indexOf('localhost')>-1 || url.indexOf('127.0.0.1')>-1){
//			
//		}
	},
	setThumbFile:function(sender)
	{
		var li=$(sender).parent().parent().parent();
		var filepath=sender.value;
	  var prex=this.getDprex(filepath);
		if(this.imgprex.contains(prex)){
			li.attr('filetype','img');
	    if( sender.files && sender.files[0]){
	    	var oFReader = new FileReader();
	      oFReader.readAsDataURL(sender.files[0]);
	      oFReader.onload = function (oFREvent) {
	      	li.find('img.file_thumb').attr('src',oFREvent.target.result);
	      }
	    }else{
	    		if($(this.container).find('li').length<6){
		    		li.find('img.file_thumb').css({
							'width':'50px',
							'height':'50px',
							'left':'15px',
							'top':'15px'
						});
					}
					else{
						li.find('img.file_thumb').css({
							'width':'30px',
							'height':'30px',
							'left':'10px',
							'top':'10px'
						});
					}
					li.find('img.file_thumb').attr({'src':'/bundles/fafatimewebase/images/defaultpic.png','thumb':'server'});
	    		/*
	    	  var div=document.createElement("div");
	    	  div.setAttribute('class','thumb_replace_div');
	    	  div.setAttribute('width','80px');
	        div.setAttribute('height','80px');
	        sender.select();
	        sender.blur();
	        var imgSrc = document.selection.createRange().text;
	        li.find("div.fileuploadone").append(div);  
	        li.find("div.thumb_replace_div")[0].filters.item("DXImageTransform.Microsoft.AlphaImageLoader").src = sender.value;
	        li.find('img.file_thumb').remove();
	        */
	    }
	  }
	  else{
			var fileurl='';
	  	if(this.excelprex.contains(prex)){
	  		li.attr('filetype','xls');
				fileurl="/bundles/fafatimewebase/images/xls.png";
			}
			else if(this.rarprex.contains(prex)){
				li.attr('filetype','zip');
				fileurl="/bundles/fafatimewebase/images/zip.png";
			}
			else if(this.docprex.contains(prex)){
				li.attr('filetype','doc');
				fileurl="/bundles/fafatimewebase/images/doc.png";
			}
			else if(this.txtprex.contains(prex)){
				li.attr('filetype','txt');
				fileurl="/bundles/fafatimewebase/images/otherfileicon.png";
			}
			else if(this.pptprex.contains(prex)){
				li.attr('filetype','ppt');
				fileurl="/bundles/fafatimewebase/images/ppt.png";
			}
			else{
				li.attr('filetype','other');
				fileurl="/bundles/fafatimewebase/images/otherfileicon.png";
			}
			if($(this.container).find('li').length>7){
		    		li.find('img.file_thumb').css({
							'width':'30px',
							'height':'30px',
							'left':'10px',
							'top':'10px'
						});
					}
					else{
						li.find('img.file_thumb').css({
							'width':'50px',
							'height':'50px',
							'left':'15px',
							'top':'15px'
						});
					}
			li.find('img.file_thumb').attr({'src':fileurl,'thumb':'server'});
	  }
	  var sp=filepath.split('/');
		var filename=sp[sp.length-1];
	  li.attr('title',filename);
	},   
	sumitfile:function(e){
			var li=$(e).parent().parent().parent();
			var tttt=this.returnfunc(li,e);
			tttt();
	},
	returnfunc:function(li,s){
		var o=this;
		return function(){
			var fns = s.value.split("\\");
    	var fn = fns[fns.length - 1];
			var ajax=$(s).parent().parent().ajaxSubmit({
				  dataType: 'json', //返回的数据类型
	        url: o.upload_url, //表单的action
	        method: 'post',
	        uploadProgress: function(e, p, t, per){
	            li.find("div.file_progress").show().find("div").css('width',per.toString()+"%");
	        },
	        success: function(r) {
            if (r.succeed)
            {
            	  li.attr('fileid',r.fileid);
            	  if(o.loading.get(li.attr('cCode'))!=null){
	            	  addFile(fn, r.fileid);
	           			o.loading.remove(li.attr('cCode'));
	           		}
           			o.finishload(li);
            }
            else
            {
            }
            li.find("div.file_progress").hide();
	        }
			});
			o.loading.push($(s).parent().parent().parent().attr('cCode'),ajax);
			o.addFileLoad();
			if (window.navigator.appName.toString() == "Microsoft Internet Explorer"){
				li.find(".file_progress").hide();
				li.find(".file_thumb_ie").show();
			}
		}
	}
}