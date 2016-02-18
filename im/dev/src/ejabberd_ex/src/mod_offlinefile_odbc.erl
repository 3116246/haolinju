%%%----------------------------------------------------------------------
%%% 9	离线文件
%%%----------------------------------------------------------------------

%%% @doc 离线文件.
%%%
%%%----------------------------------------------------------------------
-module(mod_offlinefile_odbc).

-behaviour(gen_mod).

-export([start/2, stop/1,
         process_iq/3,
         process_local_iq/3, process_iq_get/3, process_iq_set/3,
         takeofflinefile/3, delofflinefile/3,delofflinefile_record/3, on_user_available/1,
         process_checkcycle_offline_file/0,process_checkcycle_web_files/0,
         querymediaserver/2]).

-include("../../ejabberd-2.1.11/include/ejabberd.hrl").
-include("../../ejabberd-2.1.11/include/jlib.hrl").
-include("../include/mod_offlinefile.hrl").
-include("../include/mod_ejabberdex_init.hrl").

%%%----------------------------------------------------------------------
start(Host, Opts) ->
    IQDisc = gen_mod:get_opt(iqdisc, Opts, one_queue),    
    gen_iq_handler:add_iq_handler(ejabberd_sm, Host, ?NS_OFFLINEFILE,
                                  ?MODULE, process_iq, IQDisc),
    ejabberd_hooks:add(user_available_hook, Host,
		       ?MODULE, on_user_available, 50),
    %%% 每10分钟执行一次,检查文件打开超时
    %%timer:apply_interval(60*1000*10, ejabberdex_c2c_odbc, process_checkcycle_lib_files_opened, []),
    %%每1天检查一次是否有超过7天的临时文件，删除之
    %%timer:apply_interval(60*1000*60*24, ejabberdex_c2c_odbc, process_checkcycle_lib_files, []),
    %%每1天删除前一天发送到web在线咨询帐号上的离线文件
    %%timer:apply_interval(60*1000*60*24, mod_offlinefile_odbc, process_checkcycle_web_files, []),    
    %%每1天检查一次是否有超过7天的离线文件，删除之
    %%timer:apply_interval(60*1000*60*24, mod_offlinefile_odbc, process_checkcycle_offline_file, []).

%%%----------------------------------------------------------------------
stop(Host) ->
    gen_iq_handler:remove_iq_handler(ejabberd_sm, Host, ?NS_OFFLINEFILE),
    ejabberd_hooks:delete(user_available_hook, Host,
			  ?MODULE, on_user_available, 50).

%%%----------------------------------------------------------------------
%%% 处理IQ节内容
process_iq(From, To, IQ) ->
    #iq{sub_el = SubEl} = IQ,
    #jid{lserver = LServer} = From,
    case lists:member(LServer, ?MYHOSTS) of
	true ->
	    process_local_iq(From, To, IQ);
	_ ->
	    IQ#iq{type = error, sub_el = [SubEl, ?ERR_ITEM_NOT_FOUND]}
    end.

process_local_iq(From, To, #iq{type = Type} = IQ) ->
    case Type of
	set ->
	    process_iq_set(From, To, IQ);
	get ->
	    process_iq_get(From, To, IQ)
    end.

%%%----------------------------------------------------------------------
%% GET相关功能
process_iq_get(_From, _To, #iq{sub_el = SubEl} = IQ) ->
    try
        {xmlelement, Name, _Attrs, _Items} = SubEl,
        case Name of
          "querymediaserver" -> querymediaserver(_From, _To, IQ);
          _ -> IQ#iq{type = error, sub_el=[SubEl, ?ERR_ITEM_NOT_FOUND]}  
        end
    catch 
    	Ec:Ex ->  
    	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
		IQ#iq{type = error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
    end.

%%%----------------------------------------------------------------------
%%% SET相关功能
process_iq_set(From, To, #iq{sub_el = SubEl} = IQ) ->
    try
        {xmlelement, Name, _Attrs, _Items} = SubEl,
        case Name of
          "takeofflinefile"     -> takeofflinefile(From, To, IQ);
          "delofflinefile"      -> delofflinefile(From, To, IQ);
          "delofflinefilerecord"-> delofflinefile_record(From, To, IQ); %%删除离线文件记录。WEB IM端专用。web端确认离线文件后，由于无法下载到本地，所以不能删除文件本身。
          _ -> IQ#iq{type = error, sub_el=[SubEl, ?ERR_ITEM_NOT_FOUND]}  
        end
    catch 
    	Ec:Ex ->  
    	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
		IQ#iq{type = error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
    end.

%%%----------------------------------------------------------------------
%%% 14	流服务器分配
querymediaserver(From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    Atype = xml:get_tag_attr_s("type", SubEl),
    Avalue = case Atype of 
             "group_raw_swap" -> xml:get_tag_attr_s("groupid", SubEl); 
             "fileproxy" -> jlib:jid_to_string(From);
              _ -> "" end,
    
    {_ANode, AServer, APort} = querymediaserver(Atype, Avalue),
    ASubEl = xml:replace_tag_attr("server", AServer, SubEl),
    XSubEl = xml:replace_tag_attr("port", APort, ASubEl),
    
    IQ#iq{type = result, sub_el = [XSubEl]}
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
  end.
  
%%分配一个流服务器
%%返回 {nodename::string(), server::string(), port::string()} 
querymediaserver("group_raw_swap" = _Qtype, Qgroupid) ->
  case ejabberdex_odbc_query:get_online_cluster_node_media_group(Qgroupid) of
  [] -> 
    X = querymediaserver("udp", Qgroupid),
    ejabberdex_odbc_query:ins_group_raw_swap(Qgroupid, "audio", element(1, X)),
    X;
  [R|_] -> {element(1, R), element(4, R), element(5, R)}
  end;
querymediaserver("fileproxy" = Qtype, QFromStr) ->
  Are = case ejabberdex_odbc_query:get_online_cluster_node_media(Qtype) of
  [] -> {atom_to_list(node()), "localhost", "1234"};
  [R] -> {element(1, R), element(4, R), element(5, R)};
  Rs -> R = lists:nth(erlang:phash(now(), length(Rs)), Rs), {element(1, R), element(4, R), element(5, R)}
  end,
  ejabberdex_odbc_query:ins_fileproxy_node(QFromStr, element(1, Are)),
  Are;
querymediaserver("udp" = Qtype, _Qvalue) ->
  case ejabberdex_odbc_query:get_online_cluster_node_media(Qtype) of
  [] -> {atom_to_list(node()), "localhost", "1234"};
  [R] -> {element(1, R), element(4, R), element(5, R)};
  Rs -> R = lists:nth(erlang:phash(now(), length(Rs)), Rs), {element(1, R), element(4, R), element(5, R)}
  end;
querymediaserver("tcp" = Qtype, _Qvalue) ->
  case ejabberdex_odbc_query:get_online_cluster_node_media(Qtype) of
  [] -> {atom_to_list(node()), "localhost", "1234"};
  [R] -> {element(1, R), element(4, R), element(5, R)};
  Rs -> R = lists:nth(erlang:phash(now(), length(Rs)), Rs), {element(1, R), element(4, R), element(5, R)}
  end.
  
%%%----------------------------------------------------------------------
%%% 9.2.1	更改临时文件为离线文件
%%% From 发送方JID
%%% IQ 发送来的XML内容，例：
%%% <iq from='userloginname@example.com/xxxxxx123456' type='set' id='XXXXXX'>
%%%   <takeofflinefile xmlns='http://im.fafacn.com/namespace/offlinefile' 
%%%     filehashvalue='04E6C4F1B181AA52FA26786C2094B3C3'
%%%     filename='abcd.doc' sendto='xxx@example.com'>
%%%   </takeofflinefile>
%%% </iq>
%%% 返回值 IQ节
takeofflinefile(From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    Operator = From#jid.luser ++ "@" ++ From#jid.lserver,    
    
    FileHashValue = xml:get_tag_attr_s("filehashvalue", SubEl),
    FileName = xml:get_tag_attr_s("filename", SubEl),
    SendTo = xml:get_tag_attr_s("sendto", SubEl),
    
    %查找文件是否存在
    case ejabberdex_odbc_query:get_lib_file(FileHashValue) of
    [Alib_files|_] -> %若存在
      %如果文件为临时文件则修改文件为离线文件
      if 
      Alib_files#lib_files.savelevel == "0" ->
        ejabberdex_odbc_query:update_lib_files_sql(Alib_files#lib_files{savelevel = "1", lastdate = date()});
      true ->
        continue
      end,
      %记录
      ejabberdex_odbc_query:set_offline_file(#offline_file{fileid = FileHashValue, filename = FileName, from = Operator, sendto = SendTo, lastdate = date()}),
      %发送出席
      Pres = {xmlelement, "presence", [], 
        [{xmlelement, "hasofflinefile", 
           [{"xmlns", ?NS_OFFLINEFILE}, 
            {"filehashvalue", FileHashValue}, 
            {"filename", FileName}, 
            {"senddate", io_lib:format("~p-~p-~p", [element(1, date()), element(2, date()), element(3, date())])}], []}]},
      ejabberd_sm:route(From, jlib:string_to_jid(SendTo), Pres);
    _ ->              %若不存在
      continue
    end,
    
    IQ#iq{type = result, sub_el = []}
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
  end.

%%%----------------------------------------------------------------------
%%% 9.2.2	删除离线文件
%%% From 发送方JID
%%% IQ 发送来的XML内容，例：
%%% <iq from='userloginname@example.com/xxxxxx123456' type='set' id='XXXXXX'>
%%%   <delofflinefile xmlns='http://im.fafacn.com/namespace/offlinefile' 
%%%     filehashvalue='04E6C4F1B181AA52FA26786C2094B3C3'>
%%%   </delofflinefile>
%%% </iq>
%%% 返回值 IQ节
delofflinefile(From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    LUser = From#jid.luser ,
    LServer = From#jid.lserver,    
    Operator =case From#jid.lresource of
      [$F,$a,$F,$a,$W,$e,$b|T1]->
         case T1 of
           []-> LUser ++ "@" ++ LServer;
           _->  LUser ++ "@" ++ LServer++"/"++From#jid.lresource
         end;
      _->LUser ++ "@" ++ LServer
    end,   
     
    FileHashValue = xml:get_tag_attr_s("filehashvalue", SubEl),
    %删除离线记录
    ejabberdex_odbc_query:del_offline_file(FileHashValue, Operator),
    %删除离线文件
    ejabberdex_c2c_odbc:del_lib_files_offlinefile(FileHashValue),
    
    IQ#iq{type = result, sub_el = []}
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
  end.
%%%----------------------------------------------------------------------
%%% 9.2.2	删除离线文件记录。不删除文件本身。一般只由web im调用
%%% From 发送方JID
%%% IQ 发送来的XML内容，例：
%%% <iq from='userloginname@example.com/xxxxxx123456' type='set' id='XXXXXX'>
%%%   <delofflinefile xmlns='http://im.fafacn.com/namespace/offlinefile' 
%%%     filehashvalue='04E6C4F1B181AA52FA26786C2094B3C3'>
%%%   </delofflinefile>
%%% </iq>
%%% 返回值 IQ节
delofflinefile_record(From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    LUser = From#jid.luser ,
    LServer = From#jid.lserver,    
    Operator =case From#jid.lresource of
      [$F,$a,$F,$a,$W,$e,$b|T1]->
         case T1 of
           []-> LUser ++ "@" ++ LServer;
           _->  LUser ++ "@" ++ LServer++"/"++From#jid.lresource
         end;
      _->LUser ++ "@" ++ LServer
    end,   
     
    FileHashValue = xml:get_tag_attr_s("filehashvalue", SubEl),
    %删除离线记录
    ejabberdex_odbc_query:del_offline_file(FileHashValue, Operator),
    
    IQ#iq{type = result, sub_el = []}
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
end.
%%%----------------------------------------------------------------------
%%% 服务器监测每个客户端成为可用时，发送离线文件信息
on_user_available(#jid{luser = LUser, lserver = LServer,lresource=LResource} = JID) ->  
  try  
    Operator =case LResource of
      [$F,$a,$F,$a,$W,$e,$b|T1]->
         case T1 of
           []-> LUser ++ "@" ++ LServer;
           _->  LUser ++ "@" ++ LServer++"/"++LResource
         end;
      _->LUser ++ "@" ++ LServer
    end,
    %查找是否有离线文件需要接收
    ALoffline_file = ejabberdex_odbc_query:get_offline_file_bysendto(Operator,"1"),
    %发送出席
    lists:foreach(
      fun(EItem) ->
        Pres = {xmlelement, "presence", [], 
          [{xmlelement, "hasofflinefile", 
             [{"xmlns", ?NS_OFFLINEFILE}, 
              {"filehashvalue", EItem#offline_file.fileid}, 
              {"filename", EItem#offline_file.filename},
              {"senddate", io_lib:format("~p-~p-~p", [element(1, EItem#offline_file.lastdate), element(2, EItem#offline_file.lastdate), element(3, EItem#offline_file.lastdate)])}], []}]},
        ejabberd_sm:route(jlib:string_to_jid(EItem#offline_file.from), JID, Pres)
      end, 
      ALoffline_file),
    ok
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()])
  end.

%%%----------------------------------------------------------------------
%%每1天检查一次是否有超过7天的离线文件，删除之
%%在mod_offlinefile中定义定时器
process_checkcycle_offline_file() ->
  try
    Connection = mongo_pool:get(?MONGOPOOL),
    ALlib_files = ejabberdex_odbc_query:get_lib_file_offlineout7(),
    gridfs:do(safe, master, Connection, ?MONGODBNAME, fun()->    
      lists:foreach(
          fun(EItemX) -> 
            gridfs:delete(?MONGOCOLLECTION, {'_id',{bson:to_bin(element(2, EItemX))}})    
          end, 
          ALlib_files)
    end),  
    ejabberdex_odbc_query:del_lib_file_offlineout7()
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()])
  end.

%%%----------------------------------------------------------------------
process_checkcycle_web_files()->
   {D1,D2,_} = now(),
   Seconds1 = calendar:datetime_to_gregorian_seconds({{1970,1,1}, {0,0,0}}),
   {{YYYY, MM, DD},{_,_,_}}=calendar:gregorian_seconds_to_datetime(Seconds1+(D1*1000000+D2-86400+(8*60*60))),%取昨天日期
   Month=if MM<10 -> "0"++integer_to_list(MM);
            true->integer_to_list(MM)
   end,
   Day=if DD<10 -> "0"++integer_to_list(DD);
            true->integer_to_list(DD)
   end,
   Dir = "../lib/Yaws-1.89/im/offlinefile/"++integer_to_list(YYYY)++Month++Day++"/",
   case file:list_dir(Dir) of {ok,Lst}->
       [file:delete(Dir++"/"++F)||F<-Lst],
       file:del_dir(Dir);
   _->
     skip
   end
.