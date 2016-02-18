-module(Button_JsonToXmlelement).  
  

%% Exported Functions  
%%<<"{\"content\":\"aaa\",\"creationDate\":{\"date\":27,\"day\":1,\"hours\":18,\"minutes\":8,\"month\":1,\"seconds\":26,\"time\":1330337306984,\"timezoneOffset\":-480,\"year\":112},\"from\":\"client1\",\"id\":\"289n-2\",\"subject\":\"chat\",\"to\":\"\",\"type\":\"msg\"}">>  
-export([paraseDecode/1]).  
  
%%  
%% API Functions  
%%  
  
-record(button,{
      name,
      code,
      value,
      
}).  
  
%%  
%% Local Functions  
%%  
%paras json data to message  
paraseDecode(Bin)->  
    case rfc4627:decode(Bin) of  
        {ok,Obj,_Re}->  
            paraElements(Obj);  
        {error,Reason}->  
            {error,Reason}  
    end  
.  
  
%we get elements from decoded json,  
%it has to be 7 elements  
  
paraElements(Obj)->  
    {obj,List}=Obj,  
    Data =#message{},  
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
  
%length of content should not more than 1000  
para({"content",Val},Data) when is_binary(Val)->  
    io:format("para content:~p~n",[Data]),  
    Content=binary_to_list(Val),  
    if length(Content)<1000 ->  
               NewData=Data#message{content=Content},  
               io:format("paraed content:~p~n",[NewData]),  
               NewData;  
       true ->                
               throw({error,"illegal Content value",Data})                
    end  
;  
para({"to",Val},Data) when is_binary(Val)->  
    io:format("para to:~p~n",[Data]),  
    To =binary_to_list(Val),  
    NewData=Data#message{to=To}  
;  
para({"id",Val},Data) when is_binary(Val)->  
    io:format("para id:~p~n",[Data]),  
    Id=binary_to_list(Val),  
    NewData=Data#message{id=Id}  
;  
para({"subject",Val},Data) when is_binary(Val)->  
    io:format("para subject:~p~n",[Data]),  
    Sub=binary_to_list(Val),  
    %we should validate subject here   
    if Sub=:="chat" ->  
           NewData=Data#message{subject=Sub};  
       true ->  
         %throw exception  
         throw({error,"illegal subject value",Data})  
    end  
;  
para({"type",Val},Data) when is_binary(Val)->  
    io:format("para type:~p~n",[Data]),  
    Type = binary_to_list(Val),  
    if Type=:="msg"->  
           NewData=Data#message{type=Type};  
       true ->  
         %throw exception  
         throw({error,"illegal type value",Data})  
    end  
;  
para({"from",Val},Data) when is_binary(Val)->  
    io:format("para from:~p~n",[Data]),  
    From=binary_to_list(Val),  
    NewData=Data#message{from=From}  
;  
para({"creationDate",Val},Data)->  
    Data  
;  
para({Key,Val},Data)->  
    %no mache  
    %throw exception  
    throw({error,"unkown element",Data})  
.  
  
  
paraseEncode()->  
    ok  
.  