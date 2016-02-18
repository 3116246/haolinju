-module(button_JsonToXmlelement).  
  

%% Exported Functions  
%%<<"{\"content\":\"aaa\",\"creationDate\":{\"date\":27,\"day\":1,\"hours\":18,\"minutes\":8,\"month\":1,\"seconds\":26,\"time\":1330337306984,\"timezoneOffset\":-480,\"year\":112},\"from\":\"client1\",\"id\":\"289n-2\",\"subject\":\"chat\",\"to\":\"\",\"type\":\"msg\"}">>  
-export([paraseDecode/1]).  
  
%%  
%% API Functions  
%%  
  
-record(button,{
      text,
      code,
      value,
      link,
      blank,
      m,
      showremark,
      remarklabel
}).  
  
  
button_to_xml(Btn)->
   {xmlelement, "button", 
    [], 
    [{xmlelement,"text",[],xmlData(Btn#button.text)}, 
     {xmlelement,"code",[],xmlData(Btn#button.code)}, 
     {xmlelement,"value",[],xmlData(Btn#button.value)}, 
     {xmlelement,"link",[],xmlData(Btn#button.link)},
     {xmlelement,"m",[],xmlData(Btn#button.m )},
     {xmlelement,"blank",[],xmlData(Btn#button.blank)},
     {xmlelement,"showremark",[],xmlData(Btn#button.showremark)},
     {xmlelement,"remarklabel",[],xmlData(Btn#button.remarklabel)}
     ]
   }
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
  
%%  
%% Local Functions  
%%  
%paras json data to message  
paraseDecode(Bin)->  
    case rfc4627:decode(list_to_binary(Bin)) of  
        {ok,Obj,_Re}->  
            Buttons=[paraElements(Ele) ||Ele<-Obj],            
            [button_to_xml(Item)||Item<-Buttons];  
        {error,Reason}->  
            {error,Reason}  
    end  
.  
  
%we get elements from decoded json,  
%it has to be 7 elements  
  
paraElements(Obj)->  
    {obj,List}=Obj,  
    Data =#button{},  
    %catch exception here  
    try paraEle(List,Data)        
    catch  
        {error,Reason,NewData}->  
            io:format("Format"),  
            {error,Reason,NewData}  
    end  
.  
  
paraEle([Ele|Els],Data)->  
    NewData=para(Ele,Data),  
    paraEle(Els,NewData)  
;  
paraEle([],Data)->  
    Data  
.  

para({"text",Val},Data) ->  
    NewData=Data#button{text=tostring(Val)},
    NewData
; 
para({"code",Val},Data) ->  
    NewData=Data#button{code=tostring(Val)},
    NewData
;
para({"value",Val},Data)-> 
    NewData=Data#button{value=tostring(Val)},
    NewData
;
para({"link",Val},Data)-> 
    NewData=Data#button{link=tostring(Val)},
    NewData
;
para({"blank",Val},Data)-> 
    NewData=Data#button{blank=tostring(Val)} ,
    NewData
;
para({"m",Val},Data)->  
    NewData=Data#button{m=tostring(Val)},
    NewData
;
para({"showremark",Val},Data)-> 
    NewData=Data#button{showremark=tostring(Val)},
    NewData
;
para({"remarklabel",Val},Data)->
    NewData=Data#button{remarklabel=tostring(Val)},
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