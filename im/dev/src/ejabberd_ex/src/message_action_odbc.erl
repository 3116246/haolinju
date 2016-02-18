-module(message_action_odbc).

-compile(export_all).

-include("../../ejabberd_ex/include/mod_ejabberdex_init.hrl").
-include("../../ejabberd-2.1.11/include/ejabberd.hrl").

remove(N)->
   Busid=request:getparameter(N,"id"),
   if (Busid/=[]) and (Busid/="null") and (Busid/=null)->
	    case ejabberdex_odbc_query:get_broadcast_message(Busid) of
	     []-> skip;
	     [H]->
	        Timer = H#broadcast.sendtype,
	        {_,[Mht,Dayt,Hot,Mit,Wkt,_Cntt]} = regexp:split(Timer,","),        
	        DelTaskFormat = {case Mit of []->[]; _->(Mit) end,  %%分
	                  case Hot of []->[]; _->(Hot) end,  %%时            
	                  case Dayt of []->[];_->(Dayt) end, %%日
	                  case Mht of []->[]; _->(Mht) end,  %%月
	                  case Wkt of []->[]; _->(Wkt) end,{message_action,send_msg,[Busid]}},
	        server_task:del(DelTaskFormat),
	        ejabberdex_odbc_query:del_broadcast_message(Busid)
	    end,      
   	  request:return(Busid);
   true->
      request:return()
   end
.
broadcast(N) ->
         Tcaption=request:convertUTF8(request:getparameter(N,"caption")), %%标题
         Treceive=request:getparameter(N,"receive"),
         Tmssage=request:getparameter(N,"mssage"),
         Tsendtype=request:getparameter(N,"sendtype"),
         Tsendemp=request:getparameter(N,"sendemp"),
         Treceivedept=request:getparameter(N,"receivedept"),
         Link=request:getparameter(N,"link"),
         Linktext=request:getparameter(N,"linktext"),
         if (Treceive/=[]) and (Treceive/="null") and (Treceive/=null)->
         {_,Tr1} = regexp:split(Treceive,",");
         true->
             Tr1 = []
         end,
         if (Treceivedept/=[]) and (Treceivedept/="null") and (Treceivedept/=null)->
            {_,Depts} = regexp:split(Treceivedept,","),
            Tr2 = getEmpsByDept(Depts);
         true->
             Tr2 = []
         end,         
         Result = case Tsendtype of
            "1"->
               %%立即发送 
               send_once(Tcaption,Tr1++Tr2,request:convertUTF8(Tmssage),Tsendemp,Link,Linktext);
            _->
               %%定时发送
               Tsendtime=request:getparameter(N,"sendtime"),
               case Tsendtime of "null" -> request:returnerror("sendtime is null");
                 []->request:returnerror("sendtime is null");
                 _->
                   TbusId=request:getparameter(N,"busid"),
                   send_task(Tcaption,case TbusId of "null" -> [];_-> TbusId end, Tr1++Tr2,request:convertUTF8(Tmssage),Tsendemp,Tsendtime,Link,Linktext)
               end
         end,
         case Result of {error,Err} ->   
             request:returnerror(Err);
         _->
             request:return(Result)
         end
.
%%立即发送广播消息给指定的联系人或者部门
broadcast_once(Tcaption,SendFrom,SendMsg,SendToRoster,SendToDept)->
         if (SendToRoster/=[]) and (SendToRoster/="null") and (SendToRoster/=null)->
         {_,Tr1} = regexp:split(SendToRoster,";");
         true->
             Tr1 = []
         end,
         if (SendToDept/=[]) and (SendToDept/="null") and (SendToDept/=null)->
            {_,Depts} = regexp:split(SendToDept,";"),
            Tr2 = getEmpsByDept(Depts);
         true->
             Tr2 = []
         end, 
   Lstset = sets:from_list(Tr1++Tr2),  %%去除重复的帐号
   %%去除公共帐号
   Lst = sets:to_list(Lstset),
   Lst2 = lists:filter(fun(E)-> (hd(E)>=48) and (hd(E)=<57) end,Lst),
   send_once(Tcaption,Lst2,SendMsg,SendFrom,"","")
.
%%立即发送   
%%发送：Msg = {xmlelement, "message", [{"from", Operator}], [SubEl]},
%%      ejabberd_sm:route(From, jlib:string_to_jid(EItem#groupemployee.employeeid), Msg)      
send_once(Tcaption,Treceive,Tmssage,Tsendemp,Link,Linktext)->
    Id = ejabberdex_odbc_query:get_seq_nextvalue("im_broadcast","ctime"),
    ejabberdex_odbc_query:save_broadcast_message(Id,"1",Treceive,[Tcaption,"|",Tmssage],Tsendemp,"0",Link,Linktext),
    %%获取发送人姓名
    SenderName=case im_employee_odbc:getuserbyaccount(Tsendemp) of
        []->
                 case Tsendemp of [$a,$d,$m,$i,$n,$@|_] -> iconv:convert("gbk","utf-8","管理员");
					                     [$s,$e,$r,$v,$i,$c,$e,$@|_] -> iconv:convert("gbk","utf-8","客服服务");
					                     [$f,$r,$o,$n,$t,$@|_] -> iconv:convert("gbk","utf-8","前台人员");
					                     [$s,$a,$l,$e,$@|_] -> iconv:convert("gbk","utf-8","销售人员");
					                     _-> Tsendemp
					        end;
        [Rec]->Rec#employee.employeename
    end,
    send_msg(Tcaption,Tsendemp,SenderName,Tmssage,Treceive,"broadcast",Link,Linktext),
    ejabberdex_odbc_query:update_broadcast_state(Id,"1"),
    Id
.  
%%定时发送      
%%Tsendtime格式参考：task.yrl
send_task(Tcaption,Id,Treceive,Tmssage,Tsendemp,Tsendtime,Link,Linktext)->   
    %%判断是否已存在标识对应的提醒信息，已存在则更新原有的设置
    case ejabberdex_odbc_query:get_broadcast_message(Id) of
     []-> skip;
     [H]->
        Timer = H#broadcast.sendtype,
        {_,[Mht,Dayt,Hot,Mit,Wkt,_Cntt]} = regexp:split(Timer,","),        
        DelTaskFormat = {case Mit of []->[]; _->(Mit) end,  %%分
                  case Hot of []->[]; _->(Hot) end,  %%时            
                  case Dayt of []->[];_->(Dayt) end, %%日
                  case Mht of []->[]; _->(Mht) end,  %%月
                  case Wkt of []->[]; _->(Wkt) end,{message_action,send_msg,[Id]}},
        server_task:del(DelTaskFormat)        
    end,
    RId = case Id of []-> ejabberdex_odbc_query:get_seq_nextvalue("im_broadcast","ctime");_-> Id end,
    ejabberdex_odbc_query:save_broadcast_message(RId,Tsendtime,Treceive,[Tcaption,"|",Tmssage],Tsendemp,"0",Link,Linktext),
    {_,[Mh,Day,Ho,Mi,Wk,_Cnt]} = regexp:split(Tsendtime,","), %%串格式为月,日,时,分,周,次数（为0时表示周期轮循发送，默认为发送1次）
    TaskFormat = {case Mi of []->[]; _->(Mi) end,  %%分
                  case Ho of []->[]; _->(Ho) end,  %%时            
                  case Day of []->[];_->(Day) end, %%日
                  case Mh of []->[]; _->(Mh) end,  %%月
                  case Wk of []->[]; _->(Wk) end,{message_action,send_msg,[Id]}},
    %%加入到任务队列中
    server_task:add(TaskFormat),
    RId
. 
%%主要用于定时发送。由任务队列回调
send_msg(FlagId)->
   Rs = ejabberdex_odbc_query:get_broadcast_message(FlagId),
   case Rs of []->
      ?ERROR_MSG("send broadcast message error:not found~n",[]),
      {error,"not found"};
   _->
       Broadcast = hd(Rs),
       {_,[Mh,Day,Ho,Mi,Wk,Cnt]} = regexp:split(Broadcast#broadcast.sendtype,",") ,
       SendCnt=case Cnt of []-> -1;
                           "0"-> 1;
                           _->
                              list_to_integer(Cnt)-1
               end,
       %io:format("======send count:~p~n",[SendCnt]),
	    %%获取发送人姓名
	    SenderName=case im_employee_odbc:getuserbyaccount(Broadcast#broadcast.sendemp) of
	        []->[];
	        [Rec]->Rec#employee.employeename
	    end,       
	     {_,TStrs} = regexp:split(Broadcast#broadcast.mssage,"|"),
	     [Tcaption,Tmsg] = case TStrs of [Ca|Cm]-> [Ca,hd(Cm)];
	                _-> ["",hd(TStrs)]
	     end,
       case send_msg(Tcaption,Broadcast#broadcast.sendemp,SenderName,Tmsg,Broadcast#broadcast.receiveemp,"remind",Broadcast#broadcast.url,Broadcast#broadcast.buttons) of ok ->
          if SendCnt=<1 ->
              ejabberdex_odbc_query:update_broadcast_state(FlagId,"1"),%%更新任务状态
              %%从任务队列中删除任务
					    TaskFormat = {case Mi of []->[]; _->(Mi) end,  %%分
					                  case Ho of []->[]; _->(Ho) end,  %%时            
					                  case Day of []->[];_->(Day) end, %%日
					                  case Mh of []->[]; _->(Mh) end,  %%月
					                  case Wk of []->[]; _->(Wk) end,{message_action,send_msg,[FlagId]}},  
					   %io:format("======del TaskFormat:~p~n",[TaskFormat]),           
             server_task:del(TaskFormat),
             {ok};
          true->
             ejabberdex_odbc_query:update_broadcast_sendcount(FlagId,Mh++","++Day++","++Ho++","++Mi++","++Wk++","++integer_to_list((list_to_integer(Cnt)-1))),
             {ok}
          end;
       _->
            ?ERROR_MSG("send broadcast message error:~p~n",[Broadcast]),
            {error,Broadcast}
       end
   end
.

%%主要用于立即发送
send_msg(Tcaption,_SendEmp,_S,_Msg,Receives,_Type,_Link,_Linktext) when Receives==[]->
 ok
;
send_msg(Tcaption,SendEmp,SenderName,TMsg,[Receive|T],Type,Link,Linktext) ->
    case Receive of []-> send_msg(Tcaption,SendEmp,SenderName,TMsg,T,Type,Link,Linktext);
    _->
        {Usr,Serv} = func_utils:jid(SendEmp),
        Operator = jlib:string_to_jid(Usr++"@"++Serv),
        SubEl =TMsg,
        {Usr1,Serv1} = func_utils:jid(Receive),
        To = jlib:string_to_jid(Usr1++"@"++Serv1),
        IsEle=is_tuple(hd(SubEl)),
        Buttons=case button_JsonToXmlelement:paraseDecode(request:convertUTF8(Linktext)) of
                 {error,_}->[];
                 ButtonsList->
                     ButtonsList
                end,
        Msg =  {xmlelement, "message", [{"type","chat"}], 
                                       [{xmlelement, "business", [{"xmlns","http://im.fafacn.com/namespace/business"}],
                                                                 [{xmlelement,"caption",[],[{xmlcdata,Tcaption}]},
                                                                  {xmlelement,"sendername",[],[{xmlcdata,SenderName}]},
                                                                  {xmlelement,"sendtime",[],[{xmlcdata, binary_to_list( func_utils:date())}]},
                                                                  {xmlelement,"type",[],[{xmlcdata,Type}]},
                                                                  {xmlelement,"link",[],[{xmlcdata,Link}]},
                                                                  {xmlelement,"buttons",[],Buttons},
                                                                  case IsEle of true-> SubEl;_-> {xmlelement,"body",[],[{xmlcdata,SubEl}]} end
                                                                 ]
                                        }]
               },
       case xml:element_to_string(Msg) of A when is_list(A)   ->                
				            ejabberd_sm:route(Operator, To, Msg);
				     _-> 
				           ?ERROR_MSG("business Presence error(element_to_string):~p~n", [Msg])
				end,
        send_msg(Tcaption,SendEmp,SenderName,TMsg,T,Type,Link,Linktext)
    end
.  

getEmpsByDept(Depts)->
    getEmpsByDept(Depts,[])
.   
getEmpsByDept(H,R) when H==[]->
  R
;
getEmpsByDept([H|T],R) ->
    Emps = im_employee_odbc:getuserbydeptid2(all,H),
    if Emps/=[]->
        Lst=lists:filter(fun(Item)-> case Item#employee.employeeid of [$v|_]->false;_-> true end end,Emps),
        Accounts = lists:map(fun(K)-> K#employee.loginname end,Lst),        
        getEmpsByDept(T,R++Accounts);
    true->
        getEmpsByDept(T,R)
    end
.

queryNoSendMsg()->
    Rs = ejabberdex_odbc_query:get_broadcast_message_by_state("0"),
    parseMsgTime(Rs,[])
. 

parseMsgTime(Rs,Lst) when Rs==[]->
   Lst;
parseMsgTime([H|T],Lst)->
   case regexp:split(H#broadcast.sendtype,",") of {ok,["1"]} -> [];
   {_,[Mh,Day,Ho,Mi,Wk,_Cnt]}-> 
   parseMsgTime(T,[{H#broadcast.ctime,
                    case Mh of []->[]; _->(Mh) end,
                    case Day of []->[]; _->(Day) end,
                    case Ho of []->[]; _->(Ho) end,
                    case Mi of []->[]; _->(Mi) end,
                    case Wk of []->[]; _->(Wk) end}|Lst])
   end
.                                     