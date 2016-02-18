var Orgs = new HashMap();

function Menu(outTarget) {
    //__Menus.put(outTarget,this);
    this.menu = document.createElement("DIV");
    this.menu.style.display = "none";
    this.menu.id = "org_menu";
    var menuReg = document.createElement("DIV");
    menuReg.id = "org_menu_div";

    var imgDiv = document.createElement("DIV");
    imgDiv.setAttribute("class", "org_menu_img_left");
    this.menu.appendChild(imgDiv);
    this.menu.appendChild(menuReg);
    var icon = document.createElement("img");
    icon.src = "../images/org_edit_icon.png";
    imgDiv.appendChild(icon);
    this.currentItem = null;
    this.owner = outTarget;
    menuReg.onmouseleave = function () {
        this.parentElement.style.display = "none";
    }
    this.Add = function (id, imgsrc, text, callerFunc) {
        var item = document.createElement("DIV");
        item.innerHTML = text;
        item.id = this.owner + "__" + id;
        this.menu.children[1].appendChild(item);
        this.menu.style.height = ((this.menu.children[1].children.length + 1) * 20) + "px";
        $(item).attr("class", "org_menu_item");
        item.onclick = function () {
            if (callerFunc != null) callerFunc(this.id);
        }
        item.onmouseenter = function () {
            $(this).attr("class", "org_menu_item_active");
        }
        item.onmouseleave = function () {
            $(this).attr("class", "org_menu_item");
        }
    }

    this.Show = function (srcEle, cx, xy) {
        if ((cx + 118) > document.getElementById(this.owner).offsetWidth) {
            $(this.menu.children[0]).attr("class", "org_menu_img_right");
            this.menu.style.left = (cx - 118 + 22) + "px";
        }
        else {
            $(this.menu.children[0]).attr("class", "org_menu_img_left");
            this.menu.style.left = cx + "px";
        }
        if ((xy + this.menu.offsetHeight) > document.getElementById(this.owner).offsetHeight) {
            this.menu.style.top = (document.getElementById(this.owner).offsetHeight - this.menu.offsetHeight) + "px";
        }
        else {
            this.menu.style.top = xy + "px";
        }
        this.menu.style.display = "";
    }
    this.Hide = function () { this.menu.style.display = 'none'; }
}
function OrgView(outTarget) {
    this.out = document.getElementById(outTarget);
    Orgs.put(outTarget, this);
    this.onSelected = null; //节点选择事件
    this.drawRootId = "";
    this.addOrgImg = document.getElementById("addorg");
    this.deleteOrgImg = document.getElementById("delorg");
    this.RootID = function (id) {
        this.drawRootId = id;
    }
    this.option = ["edit", "delete", "insert"]; //操作选项。包括编辑、删除、新增
    this.cell = [];
    this.data = null;
    this.editMode = "MENU"; //编辑模式。分为菜单模式和快捷模式（通过鼠标移动上去出现操作铵钮）
    this.handleWarp=function(node){
			//判断内容是否换行,未换行是要处理成垂直居中
			var $node = $(node);
			$node.find(".div_subwrap").css({"line-height":"17px","display":"table-cell","vertical-align":"middle"}); //先统一设置17
			//var nodeH = $node.height() ;
			//if($node.find(".div_subwrap").height()<nodeH)
			//	$node.find(".div_subwrap").css("line-height","34px");				
			//$node.css("height","34px"); //必须设置。且只能在判断是否换行后再设置    	  
    }
    this.DataSource = function (newdata) {
        if (this.out.children.length > 0) {
            this.out.innerHTML = "";
            if (this.editMode == "MENU") MenuInit();
        }
        else
            this.out.innerHTML = "";
        var isEditOption = this.option.join(",").indexOf("edit") > -1 ? true : false;
        this.cell = [];
        this.data = newdata;
        if (newdata == null || newdata.length == 0) return
        var ds = newdata.slice(0);
        var isFinish = true;
        var startID = this.drawRootId;
        this.cell.push([startID]); //根
        var level = 1;
        while (isFinish) {
            var tmpIds = [];
            this.cell[level] = [];
            isFinish = false;
            for (var i = 0; i < ds.length; i++) {
                if (ds[i] != null) isFinish = true;
                else {
                    if (!isFinish) isFinish = false;
                    continue;
                }
                if (ds[i].deptid == this.drawRootId) {
                    this.cell[0] = [ds[i]];
                    ds[i] = null;
                    continue;
                }
                if (ds[i].deptid == "-10000" || ds[i].deptid == "-1") {
                    ds[i] = null;
                    continue;
                }
                if ((","+startID+",").indexOf(","+ds[i].pid+",") > -1) {
                    this.cell[level].push(ds[i]);
                    tmpIds.push(ds[i].deptid); //作为下一次查找的上级节点编号组
                    ds[i] = null;
                }
            }
            startID = tmpIds.join(",");
            if (startID == "") break;
            level++;
        }
        //绘制每层的节点
        var totalLevelCount = this.cell.length;
        for (var i = 0; i < this.cell.length; i++) {
            //var leftX = (chlidDiv.offsetWidth-this.cell[i].length*120)/2;
            for (var j = 0; j < this.cell[i].length; j++) {
                var node = this.drawNode(this.cell[i][j]);
                if ((j != 0 || i != 0) && isEditOption) node.title = "点击编辑该部门";
                var childTdHeight = ((totalLevelCount - i) * 70 - 70);
                childTdHeight = childTdHeight == 0 ? 1 : childTdHeight;
                if (i == 0) {
                    var chlidDiv = document.createElement("table");
                    chlidDiv.border = 0;
                    chlidDiv.width = "100%";
                    chlidDiv.cellPadding = "0";
                    chlidDiv.cellSpacing = "0";
                    chlidDiv.style.position = "relative";
                    //childDiv.style.top="24px";
                    var row = chlidDiv.insertRow(-1);
                    var cell = row.insertCell(-1);
                    cell.id = this.out.id + "__td__" + this.cell[i][j].deptid;
                    cell.width = "100%";
                    cell.height = "70px";
                    cell.align = "center";
                    cell.valign = "top";
                    if (j == 0) node.setAttribute("root", true);
                    cell.appendChild(node);
                    row = chlidDiv.insertRow(-1);
                    cell = row.insertCell(-1);
                    cell.width = "100%";
                    cell.height = childTdHeight + "px";
                    cell.id = this.out.id + "__" + this.cell[i][j].deptid + "__child";
                    cell.valign = "top";
                    this.out.appendChild(chlidDiv);
                    node.children[0].style.top = (node.offsetHeight - node.children[0].offsetHeight) / 2;
                    node.children[0].style.left = (node.offsetWidth - node.children[0].offsetWidth) / 2;
                }
                else {
                    var t = this.__get(this.out.id + "__" + this.cell[i][j].pid + "__child");
                    if (t.children.length == 0) {
                        var chlidDiv = document.createElement("table");
                        chlidDiv.border = 0;
                        chlidDiv.cellPadding = "0";
                        chlidDiv.cellSpacing = "0";
                        chlidDiv.style.position = "relative";
                        chlidDiv.width = "100%";
                        var row = chlidDiv.insertRow(-1);
                        var cell = row.insertCell(-1);
                        cell.align = "center";
                        cell.valign = "top";
                        cell.height = "70px";
                        //cell.width = "50%";
                        cell.id = this.out.id + "__td__" + this.cell[i][j].deptid;
                        cell.appendChild(node);
                        row = chlidDiv.insertRow(-1);
                        cell = row.insertCell(-1);
                        cell.valign = "top";
                        //cell.width = "50%";
                        cell.height = childTdHeight + "px";
                        cell.id = this.out.id + "__" + this.cell[i][j].deptid + "__child";
                        //创建新表格	
                        t.appendChild(chlidDiv);
                        node.children[0].style.top = (node.offsetHeight - node.children[0].offsetHeight) / 2;
                        node.children[0].style.left = (node.offsetWidth - node.children[0].offsetWidth) / 2;
                    }
                    else {
                        t = t.children[0];
                        for (var r = 0; r < t.rows.length; r++) {
                            var cx = t.rows[r].insertCell(-1);
                            cx.align = "center";
                            cx.valign = "top";

                            if (r == 0) {
                                cx.height = "70px";
                                cx.id = this.out.id + "__td__" + this.cell[i][j].deptid;
                                cx.appendChild(node);
                                node.children[0].style.top = (node.offsetHeight - node.children[0].offsetHeight) / 2;
                                node.children[0].style.left = (node.offsetWidth - node.children[0].offsetWidth) / 2;
                            }
                            else { cx.height = childTdHeight + "px"; cx.id = this.out.id + "__" + this.cell[i][j].deptid + "__child"; }
                        }
                    }
                }
                //判断内容是否换行,未换行是要处理成垂直居中
                this.handleWarp($(node));
            }
        }
        //从下到上绘制连接线
        var pid = "";
        for (var i = this.cell.length - 1; i >= 0; i--) {
            //对当前层按上级节点进行顺序排序
            var childrenByPidOrder = new parent.HashMap();
            for (var j = 0; j < this.cell[i].length; j++) {
                if (pid == "") {
                    pid = this.cell[i][j].pid;
                    childrenByPidOrder.put(pid, [this.cell[i][j]]);
                }
                else if (pid != this.cell[i][j].pid) {
                    pid = this.cell[i][j].pid;
                    var ary = childrenByPidOrder.get(pid);
                    if (ary == null)
                        childrenByPidOrder.put(pid, [this.cell[i][j]]);
                    else {
                        ary.push(this.cell[i][j]);
                        childrenByPidOrder.put(pid, ary);
                    }
                }
                else {
                    var ary = childrenByPidOrder.get(pid);
                    ary.push(this.cell[i][j]);
                    childrenByPidOrder.put(pid, ary);
                }
            }
            for (var pid in childrenByPidOrder.keySet()) {
            	  var eleType = typeof(childrenByPidOrder.get(pid));
                if (pid == null || eleType=="function" ) {
                    continue;
                }
                else {
                    //画线
                    var childrens = childrenByPidOrder.get(pid);
                    if (childrens.length == 1)//只有一个子节点时只绘一条垂直连线
                        this.drawLine(childrens[0].deptid, childrens[0].pid, "0");
                    else {
                        //有多个子节点时
                        //绘制水平线
                        var line = this.drawLine(childrens[0].deptid, childrens[childrens.length - 1].deptid, "1");
                        //绘制水平线与父节点连接线
                        this.drawLineToXY(childrens[0].pid, line.offsetTop, "0");
                        //绘制水平线与子节点连接线
                        for (var p = 1; p < childrens.length - 1; p++)
                            this.drawXYToLine(childrens[p].deptid, line.offsetTop, "0");
                    }
                    childrens = null;
                }
            }
            childrenByPidOrder = null;
        }
        var $out = $(this.out);
        if($out.find("table:eq(0)").width()>$out.width())
        	$out.css("border","1px solid #CCCCCC");
        else
        	$out.css("border","0px solid #CCCCCC");
    }
    this.initMenu = function () {
        var orgMenu = new Menu(outTarget);
        this.out.appendChild(orgMenu.menu);
        return orgMenu;
    }
    this.lineHeigth = 18; //水平线矩形高度18.计算公式：（td高度70-显示图片高度34）/2
    //绘制水平线与子节点连接线
    this.drawXYToLine = function (toId, cY) {
        var ele = this.__get(this.out.id + "__" + toId);
        var y2 = this.getOffsetTop(toId);
        ele.setAttribute("topY", y2);
        var leftX = this.getOffsetLeft(toId);
        var line = document.createElement("DIV");
        with (line.style) {
            position = "absolute";
            top = (cY) + "px"; //FF下，从内边框算起，所以要加1像素的边框
            left = (leftX + ele.offsetWidth / 2) + "px";
            width = "1px";
            borderLeft = "1px solid #c0c0c0";
            height = (this.lineHeigth) + "px";  //IE下2是单元格之间的cellSpacing属性值			          
        }
        this.out.appendChild(line);
        return line;
    }
    //绘制水平线与父节点连接线
    this.drawLineToXY = function (fromId, cY) {
        var fromEle = this.__get(this.out.id + "__" + fromId);
        var y2 = this.getOffsetTop(fromId);
        fromEle.setAttribute("topY", y2);
        y2 = y2 + fromEle.offsetHeight;
        var leftX = this.getOffsetLeft(fromId);
        var line = document.createElement("DIV");
        with (line.style) {
            position = "absolute";
            top = (cY - this.lineHeigth) + "px";
            left = (leftX + fromEle.offsetWidth / 2) + "px";
            width = "1px";
            borderLeft = "1px solid #c0c0c0";
            height = this.lineHeigth + "px";
        }
        this.out.appendChild(line);
        return line;
    }
    this.drawLine = function (fromId, toId, lineType) {
        var fromEle = this.__get(this.out.id + "__" + fromId);
        var toEle = this.__get(this.out.id + "__" + toId);
        if(toEle==null) return;
        var y2 = this.getOffsetTop(toId);
        toEle.setAttribute("top-to", y2);
        var y1 = this.getOffsetTop(fromId);
        fromEle.setAttribute("top-from", y1);
        y1 = y1 - fromEle.offsetHeight + 1;
        if (lineType == "0") //垂直线
        {
            var line = document.createElement("DIV");
            var leftX = this.getOffsetLeft(toId);
            with (line.style) {
                position = "absolute";
                top = (y2 + toEle.offsetHeight + this.lineHeigth) + "px";
                left = (leftX + toEle.offsetWidth / 2) + "px";
                width = "1px";
                borderLeft = "1px solid #c0c0c0";
                height = (y1 - y2) + "px"; //2是单元格之间的cellSpacing属性值

            }
            this.out.appendChild(line);
        }
        else {
            var leftX1 = this.getOffsetLeft(toId);
            var leftX2 = this.getOffsetLeft(fromId);
            var line = document.createElement("DIV");

            with (line.style) {
                position = "absolute";
                top = (y2) + "px";
                left = (leftX2 + fromEle.offsetWidth / 2) + "px";
                width = (leftX1 - leftX2) + "px";
                border = "1px solid #c0c0c0";
                height = this.lineHeigth + "px";
                borderBottom = "0px";
            }
            this.out.appendChild(line);
        }
        return line
    }
    this.getOffsetTop = function (id) {
        var cur = this.__get(this.out.id + "__" + id);
        var topY = 0;
        while (cur != null && cur.id != this.out.id) {
            if (cur.tagName == "TABLE")
                topY += cur.offsetTop;
            cur = cur.offsetParent;
        }
        return topY;
    }
    this.getOffsetLeft = function (id) {
        var cur = this.__get(this.out.id + "__" + id);
        var leftX = 0;
        while (cur != null && cur.id != this.out.id) {
            if (cur.tagName == "TABLE" || cur.tagName == "DIV")
                leftX += cur.offsetLeft;
            cur = cur.offsetParent;
        }
        return leftX;
    }
    this.selectedNode = null; //当前选择是节点
    this.getSelectedOrg = function () {
        if (this.selectedNode == null) return null;
        return { id: this.selectedNode.id.replace(this.out.id + "__", ""), text: this.selectedNode.innerText };
    }
    this.drawNode = function (nodeData) {
        var parendChildDiv = this.__get(this.out.id + "__" + nodeData.pid + "__child");
        var result = $("<DIV class='div_wrap' old='"+nodeData.deptname+"' pid='"+nodeData.pid+"'></DIV>");
        result.attr("id", this.out.id + "__" + nodeData.deptid); 
        result.append(this.drawText(nodeData.deptname));
        if (this.editMode != "MENU") {
            result.bind("mouseenter",function (event) {
                if (this.getAttribute("edit") == "1") return;
                var orgID = this.id.split("__")[0];
                var orgObj = Orgs.get(orgID);
                if (orgObj == null) return;
                var optionItem = orgObj.option.join(","); //获取当前可操作项
                orgObj.selectedNode = this;
                var cur_obj = $(this);
                var loca = cur_obj.offset();
                var leftX = loca.left ;
                var topY = $(this).position().top + loca.top ;
                if (optionItem.indexOf("delete") > -1 && this.getAttribute("root")!="true" && this.getAttribute("root")!=true) {
                    orgObj.deleteOrgImg.style.left = (leftX - 3) + "px";
                    orgObj.deleteOrgImg.style.top = (topY + 3) + "px";
                    orgObj.deleteOrgImg.style.display = '';
                }
                if (this.id.indexOf("empty") > -1) return;
                if (optionItem.indexOf("insert") > -1) {
                    orgObj.addOrgImg.style.left = (leftX + this.offsetWidth - 13) + "px";
                    orgObj.addOrgImg.style.top = (topY + 3) + "px";
                    orgObj.addOrgImg.style.display = '';
                }
            });
            result.bind("mouseleave", function (event) {
                if (this.getAttribute("edit") == "1") return;
                var orgID = this.id.split("__")[0];
                var orgObj = Orgs.get(orgID);
                if (orgObj == null) return;
                var e = event || window.event;
                var src = e.relatedTarget || e.toElement;
                if (src == null) return;
                if (src.id == orgObj.addOrgImg.id || src.id == orgObj.deleteOrgImg.id) return;
                orgObj.selectedNode = null;
                orgObj.addOrgImg.style.display = 'none';
                orgObj.deleteOrgImg.style.display = 'none';
            });
        }
        result.bind("click",function (event) {
            if (this.getAttribute("edit") == "1") return;
            var orgID = this.id.split("__")[0];
            var orgObj = Orgs.get(orgID);
            if (orgObj == null) return;
            //是否具有编辑权限
            var optionItem = orgObj.option.join(",");
            if (optionItem.indexOf("edit") == -1) return;
            if (orgObj.onSelected != null) {
                /*
                if(orgObj.selectedNode!=null)
                {
                ClassStyle.setClass(orgObj.selectedNode,"org_div_none");
                }
                ClassStyle.setClass(this,"org_div_active");*/
                orgObj.selectedNode = this;
                orgObj.onSelected(this, { x: orgObj.getOffsetLeft(this.id.replace("org_view__", "")), y: this.offsetTop + orgObj.getOffsetTop(this.id.replace("org_view__", "")) });
            }
        });
        return result[0];
    }

    this.drawText = function (nodeText) {
        var span = document.createElement("DIV");
        $(span).attr("class", "div_subwrap");
        span.innerHTML = (this.editMode == "MENU") ? "<a href=#>" + nodeText + "</a>" : nodeText;
        return span;
    }

    this.setText = function (id, txt) {
        var node = this.__get(id);
        if (node == null) return;
        node.children[0].innerHTML = txt;
        node.children[0].style.left = (node.offsetWidth - node.children[0].offsetWidth) / 2;
        node.children[0].style.top = (node.offsetHeight - node.children[0].offsetHeight) / 2;
        //判断内容是否换行,未换行是要处理成垂直居中
        this.handleWarp($(node));
    }
    
    this.removeDept=function(deptid)
    {
	    	  var node = $("#"+this.out.id+ "__"+deptid);
	    	  node.remove();
	    	  var newDs = [];
	    	  for(var i=0; i< this.data.length; i++)
	    	  {
	    	      if(this.data[i].deptid!=deptid)   newDs.push(this.data[i]);	
	    	  }
	    	  this.DataSource( newDs);    	 
    }

    this.setEdit = function (id) {
        var node = $("#"+id);
        if (node.length == 0) return;
        if (orgView.editMode == "MENU") return;
        node.attr("edit", "1");
        var input = $("<input type='text' style='padding:4px 1px;margin:0px;width:67px;*width:66px;height:25px;*height:23px;border:1px solid #CCCCCC;' maxLength=10 value='"+node.text()+"'>");
        
        this.addOrgImg.style.display = 'none';
        this.deleteOrgImg.style.display = 'none';
        input[0].onblur = function () {
        	  var thisPObj = this.parentElement,thisPObjID = thisPObj.id;        	  
            var orgID = thisPObjID.split("__")[0];
            var orgObj = Orgs.get(orgID);
            if (orgObj == null) return;
            var text = this.value.replace(/[ |"|'|,|\.|&]/g, "");
            thisPObj.setAttribute("edit", "0");
            $(thisPObj.children[1]).remove();
            thisPObj.children[0].style.display = '';            
            orgObj.setText(thisPObjID, text);
            if (text != "" && text != thisPObj.getAttribute("old")) {
                var deptid = thisPObjID.indexOf("empty") > -1 ? "" : thisPObjID.split("__")[1];
                var Pid = thisPObj.getAttribute("pid");
                $(".div_subwrap").append(" ");
                orgObj.saveDept(thisPObjID,{"deptid":deptid ,"deptname": text ,"pid": Pid });                
            }
            
        }
        node.find(".div_subwrap").hide();
        node.append(input);
        input[0].focus();
    }
    
    this.saveAfter=function(ctlid,data)
    {
                    for (var p = 0; p < this.data.length; p++) {
                        if (this.data[p].deptid == ctlid.replace("org_view__", "")) {
                            this.data[p].deptname = data.deptname;
                            this.data[p].deptid = data.deptid;
                            break;
                        }
                    }
                    var pnode = $("#"+ctlid);
                    pnode.attr("old", data.deptname);
                    pnode.attr("id", "org_view__" + data.deptid);
                    pnode.parent().attr("id", "org_view__td__" + data.deptid);    	  
    }
    
    this.saveDept=null;

    this.__get = function (id) {
        return document.getElementById(id);
    }

    //---------------操作方法--------------------
    this.Del = function (callFunc) {
        var curdept = this.getSelectedOrg();
        if (curdept == null) return;
        $.ligerDialog.confirm('确定要删除部门［' + curdept.text + '］吗？', function (result) {
            if (!result) return;
            $.ligerDialog.waitting('正在删除中,请稍候...');
            var obj = 'deleterows:' + curdept.id;
            try {
                $.getJSON("/"+g_curr_network_domain+"/user/account/mydepts",function(data){
		                $.ligerDialog.closeWaitting();		                
		                if (callFunc != null)
		                    callFunc();
		                              	  
                });
            }
            catch (w) {
                alert(w);
            }
        });
    }

    this.AppendChildDept = function (callFunc) {
        var curdept = this.getSelectedOrg();
        $.ligerDialog.open({ name: "org_edit", title: curdept.text + ' 增加下级部门', url: 'org_edit.yaws?pid=' + curdept.id, height: 150, width: 350,
            buttons: [{ text: '确定', onclick: function (item, dialog) {
                $("#org_edit")[0].contentWindow.save();
                if (callFunc != null) callFunc();
            } 
            },
				             { text: '取消', onclick: function (item, dialog) { dialog.close(); } }]
        });
    }
    this.Edit = function (callFunc) {
        var curdept = this.getSelectedOrg();
        $.ligerDialog.open({ name: "org_edit", title: curdept.text + ' 编辑', url: 'org_edit.yaws?cur_dept=' + curdept.id, height: 150, width: 350,
            buttons: [{ text: '确定', onclick: function (item, dialog) {
                $("#org_edit")[0].contentWindow.save();
                if (callFunc != null) callFunc();
            } 
            },
				             { text: '取消', onclick: function (item, dialog) { dialog.close(); } }]
        });
    }
}

var OrgTrees = new HashMap();
var __manaer =null;
function OrgTree(outTarget) {
    this.out = document.getElementById(outTarget);
    this.setting = {
        data: {
            key: { name: "deptname" },
            simpleData: {
                enable: true,
                idKey: "deptid",
                pIdKey: "pid",
                rootPId: "-10000"
            }
        },
        callback: {
            onClick: function () { var obj = OrgTrees.get(outTarget); if (obj.onSelected == null) obj.getNode(); else obj.onSelected(); }
        }
    };
    OrgTrees.put(outTarget, this);
    this.__get = function (id) {
        return document.getElementById(id);
    }
    this.getManager = function () {
        if (__manaer == null)
            __manaer = $.fn.zTree.getZTreeObj(outTarget); // $("#"+outTarget).ligerGetTreeManager();
        return __manaer;
    };
    this.onSelected = null;
    this.DataSource = function (newData, func) {
        $.fn.zTree.init($("#" + outTarget), this.setting, newData);
        /*
        $(function ()
        {
        __manaer=$("#"+outTarget).ligerTree(
        {
        data: newData,  
        checkbox:false,
        treeLine:true,
        textFieldName:'deptname',
        idFieldName :'deptid',
        parentIDFieldName :'pid',
        onSelect : function(){var obj=OrgTrees.get(this.id);if(obj.onSelected==null)obj.getNode(); else obj.onSelected();}
        });			                            
        });*/
        if (func != null)
            func();
    }

    this.__getDeptEmployees = function (node) {
        this.getEmployees(node.deptid, node.treedataindex);
        if (node.children != null && node.children.length > 0) {
            for (var i = 0; i < node.children.length; i++) {
                this.__getDeptEmployees(node.children[i]);
            }
        }
    }

    this.getNode = function () {
        var curNode = this.getManager().getSelectedNodes();
        if (curNode == null || curNode.length < 1) return;
        curNode = curNode[0];
        var id = curNode.deptid + "";
        if (id.indexOf("_emp") > 0) {
            Employee.init();
            id = id.replace("_emp", "");
            if (id.indexOf("-9997") > 0 || id.indexOf("-9999") > 0 || id.indexOf("-9998") > 0 || id.indexOf("-10000") > 0) {
                pid = id.split("-")[1]; //岗位代码
                return;
            }
            $("#user_view")[0].style.display = "";
            $("#org_view")[0].style.display = "none";
            ToolButton.setEnable("reset_btn");
            Employee.queryEmployee(id);
            return;
        }
        ToolButton.setDisable("reset_btn");
        if ($("#org_view")[0].style.display == "none") {
            $("#user_view")[0].style.display = "none";
            $("#org_view")[0].style.display = "";
            orgView.DataSource(orgView.data);
        }
        //if(curNode.data.children!=null && curNode.data.children.length>0) return;   
        this.getEmployees(id);
    }
    this.getEmployees = function (id, treeIndex) { }
}

var Employee = {
    id: "",
    parentDeptID: "",
    empdata: {},
    setDept: function (pid, deptname) {
        this.parentDeptID = pid;
        setControlValue("deptname", deptname);
    },
    init: function () {
        this.id = "";
        this.parentDeptID = "";
        this.empdata = {};
        setControlValue("loginname", "");
        setControlValue("name", "");
        setControlValue("nickname", "");
        setControlValue("bday", "");
        setControlValue("email", "");
        setControlValue("role", "");
        setControlValue("reportto", "");
        setControlValue("reporttoId", "");
        setControlValue("desc", "");
        jobcode = "";
        setControlValue("dept_name", "");
        setControlValue("deptname", "");
        setControlValue("emp_name", "");
        setControlValue("emp_name2", "");
        setControlValue("role", "员工");
        setControlValue("phone_work_voice", "");
        setControlValue("phone_work_cell", "");
        document.getElementById("photo_url").src = "../images/headPhoto.png";
        setControlValue("photo", "");
    },
    queryEmployee: function (empid) {
        this.init();
        this.id = empid;
        ajaxSubmit("im_vcard:get", "Empid=" + empid, function (data) {
            if (!data.succeed) {
                alert(data.message);
                return;
            }
            method = "edit";
            ToolButton.setEnable("deldept_btn");
            ToolButton.setEnable("reset_btn");
            document.getElementById("photo_url").src = "../images/headPhoto.png";
            setControlValue("photo", "");
            if (data.msg == null || data.msg == "") return;
            var ds = eval("(" + data.msg + ")");
            Employee.empdata = ds;
            var domainS = ds.Account.nullTo().split("@");
            setControlValue("loginname", domainS[0]);
            if (domainS.length > 1)
                setControlValue("domain", "@" + domainS[1]);
            try {
                jobcode = ds.jobcode;
                setControlValue("name", ds.FN.nullTo());
                setControlValue("nickname", ds.NickName.nullTo());
                //setControlValue("bday",ds.bday.nullTo());
                setControlValue("sex", ds.sex.nullTo());
                setControlValue("phone_work_cell", ds.call_phone.nullTo());
                setControlValue("phone_work_voice", ds.phone_work.nullTo());
                setControlValue("email", ds.email.nullTo());
                setControlValue("role", ds.role.nullTo());
                var reportto = ds.reportto.nullTo().split(",");
                if (reportto.length > 1) {
                    setControlValue("reporttoId", reportto[0]);
                    setControlValue("reportto", reportto[1]);
                }
            }
            catch (e) { }
            if (ds.photo_url != null && ds.photo_url != "" && ds.photo_url != "null") {
                document.getElementById("photo_url").src = ds.photo_url;
                setControlValue("photo", ds.photo_url);
            }
            //获取部门名称
            var dept_data = Service.service().getempinfo(empid);
            var dept_ds = (eval(dept_data));
            //Pid = dept_ds[1].deptid; 
            dept_data = Service.service().childrenlist(dept_ds[1].deptid);
            dept_ds = (eval(dept_data));
            var deptname = dept_ds[1].deptname;
            Employee.setDept(dept_ds[1].deptid, deptname),
						      setControlValue("dept_name", deptname);
            setControlValue("emp_name", ds.FN);
            setControlValue("emp_name2", ds.FN);
        });
    },
    saveEmployee: function (callFunc) {
        if (this.parentDeptID.nullTo() == "") {
            alert("请选择组织机构或部门！");
            return;
        }
        var Dname = getControlValue("name");
        if (Dname.nullTo() == "") {
            alert("请输入真实姓名！");
            return;
        }
        var Daccount = getControlValue("loginname");
        if (Daccount.nullTo() == "") {
            alert("请输入登录名！");
            return;
        }
        var email = getControlValue("email");
        if (email.nullTo() == "") {
            alert("请输入员工的电子邮箱！");
            return;
        }
        if (email.search(/^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/) == -1) {
            alert("员工的电子邮箱格式不正确！");
            return;
        }
        nickname = this.empdata.NickName == null ? Dname : this.empdata.NickName;
        var domainS = getControlValue("domain");
        domainS = domainS == "@" ? "" : domainS;
        var para = ["Empid=" + this.id];
        para.push("ejabberdid=" + Daccount + domainS);
        para.push("Account=" + Daccount + domainS);
        para.push("FN=" + Dname);
        para.push("Name_first=" + Dname);
        para.push("Name_last=");
        para.push("sex=" + getControlValue("sex"));
        para.push("NickName=" + nickname);
        para.push("Url=" + (this.empdata.Url == null ? "" : this.empdata.Url));
        para.push("Address_city=");
        para.push("Address_region=");
        para.push("Address_locality=");
        para.push("Address_street=");
        para.push("email=" + getControlValue("email"));
        para.push("bday=" + (this.empdata.bday == null ? "" : this.empdata.bday));
        para.push("orgname=" + (this.empdata.orgname == null ? "" : this.empdata.orgname));
        para.push("deptid=" + this.parentDeptID);
        para.push("deptname=" + getControlValue("deptname"));
        para.push("phone_home=");
        para.push("voice_phone_work=" + getControlValue("phone_work_voice"));
        para.push("call_phone_work=" + getControlValue("phone_work_cell"));
        para.push("role=" + getControlValue("role"));
        para.push("reportto=" + (getControlValue("reporttoId") + "," + getControlValue("reportto")));
        para.push("desc=" + (this.empdata.desc == null ? "" : this.empdata.desc));
        para.push("photo=" + (this.empdata.photo_url == null ? "" : this.empdata.photo_url));
        ajaxSubmit("im_vcard:set", para.join("&"), function (data) {
            var hintCtl = document.getElementById("message");
            if (!data.succeed) {
                if (hintCtl != null) setControlValue(hintCtl, msg[data.msg])
                else alert(msg[data.msg]);
                return;
            }
            if (hintCtl != null) setControlValue(hintCtl, "保存成功！")
            else alert("保存成功！");
            if (callFunc != null)
                callFunc();
        });
    },
    passReset: function () {
        if (this.id.nullTo() == "") {
            alert("请选择员工！");
            return;
        }
        $.ligerDialog.confirm('该员工密码将被重置为：888888，继续吗？', function (result) {
            if (!result) return;
        });
        ajaxSubmit("im_vcard:get", "Empid=" + this.id, function (data) {
            if (!data.succeed) {
                alert(msg[data.msg]);
                return;
            }
        });
    },
    del: function (callFunc) {
        if (Employee.id.nullTo() == "") {
            alert("请选择需删除的人员！");
            return;
        }
        $.ligerDialog.confirm('将删除该人员及所有信息，确定删除吗？', function (result) {
            if (!result) return;
            var obj = 'deleterows:' + Employee.id;
            try {
                var result = Service.service().saveemp(obj);
                var r = eval(result);
                alert(msg[r.msg]);
                if (r.succeed) {
                    if (callFunc != null) callFunc();
                    Employee.init();
                }
            }
            catch (w) {
                alert(w);
            }
        });
    }
}