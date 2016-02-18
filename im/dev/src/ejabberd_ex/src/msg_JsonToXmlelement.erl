-module(msg_JsonToXmlelement).  
-include("../../ejabberd-2.1.11/include/ejabberd.hrl").  

%% Exported Functions  
%%{\"textmsg\":{\"item\":[{\"title\":\"TEST1\",\"content\":\"TEST1TEST1\",\"buttons\":[{\"title\":\"btn1\",\"actionurl\":\"http://www.wefafa.com\"}]},{\"title\":\"TEST2\",\"content\":\"TEST2TEST2\"}]}}
-export([paraseDecode/1,msg_to_xml/1,paraElements/1]).  
  
%%  
%% API Functions  
%%    

-record(msg,{
       type,
       headitem,  %%type²»ÊÇtextmsgÊ±£¬¸ÃÊôÐÔÓÐÐ§ 
       item,
       shareitem  %%typeÊÇsnssharemsgÊ±£¬¸ÃÊôÐÔÓÐÐ§ 
}).  
-record(item,{
    title,
    content,
    buttons,
    bizdata,
    image,
    link
}).

-record(shareitem,{
	groupid,
    content,
    iosclass,
    bizdata,
    image,
    androidclass
}).

-record(headitem,{
    title,
    content,
    buttons,
    bizdata,
    image,
    link
}).

-record(item_buttons,{
    title,
    actionurl,
    androidpkg,
    androidclass,
    androiddownurl,
    hplugin_id,
    hplugin_ver,
    hplugin_startpage,
    hplugin_downurl
}).

-record(item_image,{
    type,
    value
}).
  
msg_to_xml(Msg)->
   case Msg#msg.type of "textmsg"->
   {xmlelement, "textmsg", 
    [], 
    [text_item_to_xml(Item)||Item<-Msg#msg.item]
   };
   "picturemsg"->
   {xmlelement, "picturemsg", 
    [], 
    [picture_headitem_to_xml(Msg#msg.headitem)]
   };
   "textpicturemsg"->
   {xmlelement, "textpicturemsg", 
    [], 
    [picture_headitem_to_xml(Msg#msg.headitem)]++[picture_item_to_xml(Item)||Item<-Msg#msg.item] 
   };
   "snssharemsg"->
   {xmlelement, "snssharemsg", 
    [], 
    [shareitem_to_xml(Msg#msg.shareitem)]
   };   
   _->
      ?ERROR_MSG("message error:~p~n", [Msg])
   end
.

text_item_to_xml(Item)->
   {xmlelement, "item", 
    [], 
    [{xmlelement,"title",[],xmlData(Item#item.title)}, 
     {xmlelement,"content",[],xmlData(Item#item.content)}, 
     {xmlelement,"bizdata",[],xmlData(Item#item.bizdata)}, 
     {xmlelement,"buttons",[],[button_to_xml(Btn)||Btn<-xmlRecord(Item#item.buttons)]}
     ]
   }   
.
picture_headitem_to_xml(Item)->
   {xmlelement, "headitem", 
    [], 
    [{xmlelement,"title",[],xmlData(Item#headitem.title)}, 
     {xmlelement,"content",[],xmlData(Item#headitem.content)}, 
     {xmlelement,"bizdata",[],xmlData(Item#headitem.bizdata)}, 
     {xmlelement,"link",[],xmlData(Item#headitem.link)},
     {xmlelement,"buttons",[],[button_to_xml(Btn)||Btn<-xmlRecord(Item#headitem.buttons)]},
     {xmlelement,"image",img_to_xml(xmlRecord(Item#headitem.image)),[]}
     ]
   }   
.
picture_item_to_xml(Item)->
   {xmlelement, "item", 
    [], 
    [{xmlelement,"title",[],xmlData(Item#item.title)}, 
     {xmlelement,"content",[],xmlData(Item#item.content)}, 
     {xmlelement,"bizdata",[],xmlData(Item#item.bizdata)}, 
     {xmlelement,"link",[],xmlData(Item#item.link)},
     {xmlelement,"buttons",[],[button_to_xml(Btn)||Btn<-xmlRecord(Item#item.buttons)]},
     {xmlelement,"image",img_to_xml(xmlRecord(Item#item.image)),[]}
     ]
   }   
.

shareitem_to_xml(Item)->
   {xmlelement, "shareitem", 
    case formatData(Item#shareitem.groupid) of []->[]; A-> [{"groupid",A}] end, 
    [{xmlelement,"iosclass",[],xmlData(Item#shareitem.iosclass)}, 
     {xmlelement,"content",[],xmlData(Item#shareitem.content)}, 
     {xmlelement,"bizdata",[],xmlData(Item#shareitem.bizdata)}, 
     {xmlelement,"androidclass",[],xmlData(Item#shareitem.androidclass)},
     {xmlelement,"image",img_to_xml(xmlRecord(Item#shareitem.image)),[]}
     ]
   }   
.

button_to_xml(Btn) ->
   {xmlelement, "button",
    [{"title",formatData(Btn#item_buttons.title)}, 
     {"actionurl",formatData(Btn#item_buttons.actionurl)}, 
     {"androidpkg",formatData(Btn#item_buttons.androidpkg)},
     {"androidclass",formatData(Btn#item_buttons.androidclass)}, 
     {"androiddownurl",formatData(Btn#item_buttons.androiddownurl)},
     {"hplugin_id",formatData(Btn#item_buttons.hplugin_id)},
     {"hplugin_ver",formatData(Btn#item_buttons.hplugin_ver)},
     {"hplugin_startpage",formatData(Btn#item_buttons.hplugin_startpage)},
     {"hplugin_downurl",formatData(Btn#item_buttons.hplugin_downurl)}
    ],[]
   }
.

img_to_xml(Btn) -> 
   case Btn of []->[];
   _->
    [{"type",formatData(Btn#item_image.type)}, 
     {"value",formatData(Btn#item_image.value)}
    ]
  end
.


formatData(A)->
   case A of undefined->[];
   null->[];
   "undefined"->[];
   "null"->[];
   []->[];
   _-> A
   end
.
xmlData(A)->
   case A of undefined->[];
   null->[];
   "undefined"->[];
   "null"->[];
   []->[];
   _-> [{xmlcdata, A}]
   end
.

xmlRecord(A)->
   case A of undefined->[];
   null->[];
   "undefined"->[];
   "null"->[];
   []->[];
   _-> A
   end
.

  
%%  
%% Local Functions  
%%  
%paras json data to message  
paraseDecode(Bin)->  
    case rfc4627:decode(list_to_binary(Bin)) of  
        {ok,Obj,_Re}->
            msg_to_xml(paraElements(Obj));  
        {error,Reason}->  
            {error,Reason}  
    end  
.  
  
%we get elements from decoded json,  
%it has to be 7 elements  
  
paraElements(Obj)->  
    {obj,List}=Obj,  
    Data =#msg{},  
    %catch exception here  
    try paraEle(List,Data)        
    catch  
        {error,Reason,NewData}->  
            io:format("Format"),  
            {error,Reason,NewData}  
    end  
.  
  
paraEle({obj,List},Data)->
    paraEle(List,Data)  
; 
paraEle([Ele|Els],Data)->  
    NewData=para(Ele,Data),  
    paraEle(Els,NewData)  
;  
paraEle([],Data)->  
    Data  
.  
para({"textmsg",Val},Data) ->  
    NewData=Data#msg{type="textmsg"},
    paraEle(Val,NewData)
;
para({"picturemsg",Val},Data) ->  
    NewData=Data#msg{type="picturemsg"},
    paraEle(Val,NewData)
;
para({"textpicturemsg",Val},Data) ->  
    NewData=Data#msg{type="textpicturemsg"},
    paraEle(Val,NewData)
;
para({"snssharemsg",Val},Data) ->  
    NewData=Data#msg{type="snssharemsg"},
    paraEle(Val,NewData)
;

para({"item",Val},Data) ->
    Itmes = [paraEle(Item,#item{})||Item<-Val],
    NewData=Data#msg{item=Itmes},
    NewData
;
para({"headitem",Val},Data) ->
    Itme=paraEle(Val,#headitem{}),
    NewData=Data#msg{headitem=Itme},
    NewData
;
para({"shareitem",Val},Data) ->
    Itme=paraEle(Val,#shareitem{}),
    NewData=Data#msg{shareitem=Itme},
    NewData
;
para({"obj",Val},Data) ->
    paraEle(Val,Data)
;

para({"title",Val},Data) when is_record(Data,item) ->  
    NewData=Data#item{title=tostring(Val)},
    NewData
; 
para({"title",Val},Data) when is_record(Data,item_buttons) ->  
    NewData=Data#item_buttons{title=tostring(Val)},
    NewData
; 
para({"title",Val},Data) when is_record(Data,headitem) ->  
    NewData=Data#headitem{title=tostring(Val)},
    NewData
;

para({"content",Val},Data) when is_record(Data,headitem) ->  
    NewData=Data#headitem{content=tostring(Val)},
    NewData
;
para({"content",Val},Data) when is_record(Data,shareitem) ->  
    NewData=Data#shareitem{content=tostring(Val)},
    NewData
;
para({"content",Val},Data) ->  
    NewData=Data#item{content=tostring(Val)},
    NewData
;
para({"groupid",Val},Data) when is_record(Data,shareitem) -> 
    NewData=Data#shareitem{groupid=tostring(Val)},
    NewData
;
para({"bizdata",Val},Data) when is_record(Data,headitem) -> 
    NewData=Data#headitem{bizdata=tostring(Val)},
    NewData
;
para({"bizdata",Val},Data) when is_record(Data,shareitem) -> 
    NewData=Data#shareitem{bizdata=tostring(Val)},
    NewData
;
para({"bizdata",Val},Data)-> 
    NewData=Data#item{bizdata=tostring(Val)},
    NewData
;

para({"link",Val},Data) when is_record(Data,headitem) -> 
    NewData=Data#headitem{link=tostring(Val)},
    NewData
;
para({"link",Val},Data)-> 
    NewData=Data#item{link=tostring(Val)},
    NewData
;

para({"buttons",Val},Data) when is_record(Data,headitem) -> 
    Item_buttons= [paraEle(Btn,#item_buttons{})||Btn<-Val],
    NewData=Data#headitem{buttons=Item_buttons} ,
    NewData
;
para({"buttons",Val},Data)-> 
    Item_buttons= [paraEle(Btn,#item_buttons{})||Btn<-Val],
    NewData=Data#item{buttons=Item_buttons} ,
    NewData
;

para({"image",Val},Data) when is_record(Data,headitem)-> 
    Item_image= paraEle(Val,#item_image{}),
    NewData=Data#headitem{image=Item_image} ,
    NewData
;
para({"image",Val},Data) when is_record(Data,shareitem)-> 
    Item_image= paraEle(Val,#item_image{}),
    NewData=Data#shareitem{image=Item_image} ,
    NewData
;
para({"image",Val},Data)-> 
    Item_image= paraEle(Val,#item_image{}),
    NewData=Data#item{image=Item_image} ,
    NewData
;
para({"iosclass",Val},Data)-> 
    NewData=Data#shareitem{iosclass=tostring(Val)},
    NewData
;
para({"type",Val},Data)-> 
    NewData=Data#item_image{type=tostring(Val)},
    NewData
;

para({"value",Val},Data)-> 
    NewData=Data#item_image{value=tostring(Val)},
    NewData
;

para({"actionurl",Val},Data)-> 
    NewData=Data#item_buttons{actionurl=tostring(Val)},
    NewData
;
para({"androidclass",Val},Data) when is_record(Data,shareitem)-> 
    NewData=Data#shareitem{androidclass=tostring(Val)},
    NewData
;
para({"androidclass",Val},Data)-> 
    NewData=Data#item_buttons{androidclass=tostring(Val)},
    NewData
;
para({"androiddownurl",Val},Data)-> 
    NewData=Data#item_buttons{androiddownurl=tostring(Val)},
    NewData
;
para({"androidpkg",Val},Data)-> 
    NewData=Data#item_buttons{androidpkg=tostring(Val)},
    NewData
;
para({"hplugin_id",Val},Data)-> 
    NewData=Data#item_buttons{hplugin_id=tostring(Val)},
    NewData
;
para({"hplugin_ver",Val},Data)-> 
    NewData=Data#item_buttons{hplugin_ver=tostring(Val)},
    NewData
;
para({"hplugin_startpage",Val},Data)-> 
    NewData=Data#item_buttons{hplugin_startpage=tostring(Val)},
    NewData
;
para({"hplugin_downurl",Val},Data)-> 
    NewData=Data#item_buttons{hplugin_downurl=tostring(Val)},
    NewData
;
para({_Key,_Val},Data)->
 Data
.

tostring(Para) when is_integer(Para)->
  integer_to_list(Para)
;
tostring(Para) when is_float(Para)->
  float_to_list(Para)
;
tostring(Para) when is_atom(Para)->
  atom_to_list(Para)
;
tostring(Para) when is_binary(Para)->
  binary_to_list(Para)
;
tostring(Para)->
  Para
.