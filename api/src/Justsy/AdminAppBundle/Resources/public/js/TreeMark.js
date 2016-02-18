//方法作用：为节点有子节点的树加上文件夹图标及+号
//以及第一次点击+有事件反应
function setMark(Nodes)
{
    if (Nodes.length==0) return;
    for(var i=0;i<Nodes.length;i++)
    {
        var node = Nodes[i];
        if ( node.state!="0")  //state不为0时表示有子节点
        {
            $("#"+node.tId+"_switch").attr("class","button level1 switch center_close");
            $("#"+node.tId+"_switch").attr("flag","close");
            $("#"+node.tId+"_ico").attr("class","button ico_close");
            //加号时的事件
            $("#"+node.tId+"_switch").bind("click",function(data){
              var flag = $(this).attr("flag");
              if (flag=="close")
              {
                  $(this).attr("flag","open");
                  $(this).parent().find("a").trigger("click");
              }
            });
        }
    }
}