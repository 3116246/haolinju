%%%----------------------------------------------------------------------
%%% ¶àÈËÁ÷Êý¾Ý×ª·¢Ä£¿é
%%%----------------------------------------------------------------------

%%% @doc ¶àÈËÁ÷Êý¾Ý×ª·¢Ä£¿é.
%%%
%%%----------------------------------------------------------------------
-module(ejabberdex_c2c_odbc).
-author('feihu929@sina.com').

-behaviour(gen_fsm).

%% API
-export([start_link/2,
	 start/2,
	 socket_type/0,
	 udp_recv/5, process_checkcycle/0, process_checkcycle_lib_files_opened/0, process_checkcycle_lib_files/0,
	 get_realfilepath/1, get_lib_files_mongo2local/2, del_lib_files_offlinefile/1, index_of_binary/3, del_lib_files_sharefile/1]).

%% gen_fsm callbacks
-export([init/1,
	 handle_event/3,
	 handle_sync_event/4,
	 handle_info/3,
	 terminate/3,
	 code_change/4]).

%% gen_fsm states
-export([wait_for_tls/2,
	 session_established/2]).

-include("../../stdlib-1.17.5/include/qlc.hrl").

-include("../../ejabberd-2.1.11/include/ejabberd.hrl").
-include("../include/mod_group.hrl").
-include("../include/mod_ejabberdex_init.hrl").

-define(MAX_BUF_SIZE, 64*1024). %% 64kb
-define(TIMEOUT, 60000). %% 60 sec

-record(state, {sock,
		sock_mod = gen_tcp,
		certfile,
		peer,
		tref,
		buf = <<>>,
		io_device}).
%°ü¸ñÊ½¶¨Òå		
-record(c2c_package, 
  {
    command,    %ÃüÁî string
    len,        %content×Ö½Ú³¤¶È int
    content     %¾ßÌåÄÚÈÝ  binary
  }).		

%%====================================================================
%% API
%%====================================================================
start({gen_tcp, Sock}, Opts) ->
    supervisor:start_child(ejabberdex_c2c_odbc_sup, [Sock, Opts]).

start_link(Sock, Opts) ->
    gen_fsm:start_link(?MODULE, [Sock, Opts], []).

socket_type() ->
    raw.

udp_recv(Sock, Addr, Port, Data, _Opts) ->
    case decode(Data) of
	{ok, Msg, <<>>} ->
	    ?DEBUG("got:~n~p", [Msg]),
	    case process(Sock, Addr, Port, Msg) of
		RespMsg when is_record(RespMsg, c2c_package) ->
		    ?DEBUG("sent:~n~p", [RespMsg]),
		    Data1 = encode(RespMsg),
		    gen_udp:send(Sock, Addr, Port, Data1);
		_ ->
		    ok
	    end;
	_ ->
	    ok
    end.

%%====================================================================
%% gen_fsm callbacks
%%====================================================================
init([Sock, Opts]) ->
    case inet:peername(Sock) of
	{ok, Addr} ->
	    inet:setopts(Sock, [{active, once}]),
	    TRef = erlang:start_timer(?TIMEOUT, self(), stop),
	    State = #state{sock = Sock, peer = Addr, tref = TRef},
	    case proplists:get_value(certfile, Opts) of
		undefined ->
		    {ok, session_established, State};
		CertFile ->
		    {ok, wait_for_tls, State#state{certfile = CertFile}}
	    end;
	Err ->
	    Err
    end.

wait_for_tls(Event, State) ->
    ?INFO_MSG("unexpected event in wait_for_tls: ~p", [Event]),
    {next_state, wait_for_tls, State}.

session_established(Msg, State) when is_record(Msg, c2c_package) ->
    ?DEBUG("got:~n~p", [Msg]),
    {Addr, Port} = State#state.peer,
    NewState = case process(State#state.sock, Addr, Port, Msg, State) of
	Resp when is_record(Resp, c2c_package) ->
	    ?DEBUG("sent:~n~p", [Resp]),
	    Data = encode(Resp),
	    (State#state.sock_mod):send(State#state.sock, Data),
	    State;
	{Resp, S} when is_record(Resp, c2c_package) ->
	    ?DEBUG("sent:~n~p", [Resp]),
	    Data = encode(Resp),
	    (State#state.sock_mod):send(State#state.sock, Data),
	    S;
	{_, S} ->
	    S;
	_ ->
	    State
    end,
    {next_state, session_established, NewState};
session_established(Event, State) ->
    ?INFO_MSG("unexpected event in session_established: ~p", [Event]),
    {next_state, session_established, State}.

handle_event(_Event, StateName, State) ->
    {next_state, StateName, State}.

handle_sync_event(_Event, _From, StateName, State) ->
    {reply, {error, badarg}, StateName, State}.

handle_info({tcp, Sock, TLSData}, wait_for_tls, State) ->
    Buf = <<(State#state.buf)/binary, TLSData/binary>>,
    %% Check if the initial message is a TLS handshake
    case Buf of
	_ when size(Buf) < 3 ->
	    {next_state, wait_for_tls,
	     update_state(State#state{buf = Buf})};
	<<_:16, 1, _/binary>> ->
	    TLSOpts = [{certfile, State#state.certfile}],
	    {ok, TLSSock} = tls:tcp_to_tls(Sock, TLSOpts),
	    NewState = State#state{sock = TLSSock,
				   buf = <<>>,
				   sock_mod = tls},
	    case tls:recv_data(TLSSock, Buf) of
		{ok, Data} ->
		    process_data(session_established, NewState, Data);
		_Err ->
		    {stop, normal, NewState}
	    end;
	_ ->
	    process_data(session_established, State, TLSData)
    end;
handle_info({tcp, _Sock, TLSData}, StateName,
	    #state{sock_mod = tls} = State) ->
    case tls:recv_data(State#state.sock, TLSData) of
	{ok, Data} ->
	    process_data(StateName, State, Data);
	_Err ->
	    {stop, normal, State}
    end;
handle_info({tcp, _Sock, Data}, StateName, State) ->
    process_data(StateName, State, Data);
handle_info({tcp_closed, _Sock}, _StateName, State) ->
    ?DEBUG("connection reset by peer", []),
    {stop, normal, State};
handle_info({tcp_error, _Sock, Reason}, _StateName, State) ->
    ?DEBUG("connection error: ~p", [Reason]),
    {stop, normal, State};
handle_info({timeout, TRef, stop}, _StateName,
	    #state{tref = TRef} = State) ->
    ?INFO_MSG("timeout: ~p", [State]), 
    {stop, normal, State};
handle_info(Info, StateName, State) ->
    ?INFO_MSG("unexpected info: ~p", [Info]),
    {next_state, StateName, State}.

terminate(_Reason, _StateName, State) ->
    catch (State#state.sock_mod):close(State#state.sock),
    catch file:close(State#state.io_device),
    ok.

code_change(_OldVsn, StateName, State, _Extra) ->
    {ok, StateName, State}.

%%--------------------------------------------------------------------
%%% Internal functions
%%--------------------------------------------------------------------
%% 00	´«ÊäÊý¾Ý
process(Sock, Addr, Port, #c2c_package{command = "00", content = Content} = Msg) ->
  case catch string:tokens(binary_to_list(Content), "|") of
  [Qgroupid|_] ->
    %×ª·¢
    ALgroup_raw_swap = mnesia:dirty_index_match_object(#group_raw_swap{groupid=Qgroupid, _ = '_'}, groupid),
    Data = encode(Msg),
    lists:foreach(fun(EItem) -> 
                    if 
                      EItem#group_raw_swap.jid == "" ->
                        continue;
                      EItem#group_raw_swap.ip == Addr andalso EItem#group_raw_swap.port == Port ->
                        %Î´±ÜÃâÆµ·±¸üÐÂ£¬30Ãë¸üÐÂÒ»´ÎÊý¾Ý
                        {_, NowSec, _} = now(),
                        if 
                          (element(2, EItem#group_raw_swap.lasttime) + 30) < NowSec  ->
                            mnesia:dirty_delete_object(EItem),
                            mnesia:dirty_write(EItem#group_raw_swap{lasttime = now()});
                          true ->
                            continue
                        end;
                      true ->
                        gen_udp:send(Sock, EItem#group_raw_swap.ip, EItem#group_raw_swap.port, Data)
                    end
                  end, 
                  ALgroup_raw_swap),
    pass;
  _ ->
    pass
  end;
%% 01	×¢²áÇëÇó
process(Sock, Addr, Port, #c2c_package{command = "01", content = Content} = Msg) ->  
  case catch string:tokens(binary_to_list(Content), "|\0 ") of
  [Qgroupid, Qjid|Other] ->
    %É¾³ýÒÔÇ°µÄ×¢²á
    ALDelgroup_raw_swap = mnesia:dirty_index_match_object(#group_raw_swap{groupid=Qgroupid, jid = Qjid, _ = '_'}, jid),
    lists:foreach(fun(EItem) -> mnesia:dirty_delete_object(EItem) end, ALDelgroup_raw_swap),
    %±£´æ×¢²á
    mnesia:dirty_write(#group_raw_swap{groupid = Qgroupid, jid = Qjid, ip = Addr, port = Port, other = Other, lasttime = now()}),
    %×ª·¢×¢²á
    ALgroup_raw_swap = mnesia:dirty_index_match_object(#group_raw_swap{groupid=Qgroupid, _ = '_'}, groupid),
    Data = encode(Msg),
    lists:foreach(fun(EItem) -> 
                    if 
                      EItem#group_raw_swap.jid == "" ->
                        continue;
                      EItem#group_raw_swap.jid == Qjid ->
                        continue;
                      true ->
                        gen_udp:send(Sock, Addr, Port, encode(#c2c_package{command = "01", content = list_to_binary([EItem#group_raw_swap.groupid, "|", EItem#group_raw_swap.jid, "|", EItem#group_raw_swap.other])})),
                        gen_udp:send(Sock, EItem#group_raw_swap.ip, EItem#group_raw_swap.port, Data)
                    end
                  end, 
                  ALgroup_raw_swap),
    pass;
  _ ->
    pass
  end;
%% 99	ÐÄÌø
process(_Sock, Addr, Port, #c2c_package{command = "99"}) ->
  ALgroup_raw_swap = mnesia:dirty_index_match_object(#group_raw_swap{ip=Addr, port=Port, _ = '_'}, ip),
  lists:foreach(fun(EItem) -> mnesia:dirty_delete_object(EItem) end, ALgroup_raw_swap),
  lists:foreach(fun(EItem) -> mnesia:dirty_write(EItem#group_raw_swap{lasttime = now()}) end, ALgroup_raw_swap),
  pass;
%% 20	´«ÊäÊý¾Ý
process(Sock, _Addr, _Port, #c2c_package{command = "20", content = Content} = Msg) ->
  case catch string:tokens(binary_to_list(Content), "|\0 ") of
  [SendToAddr, SendToPort|_] ->
    Data = encode(Msg),
    gen_udp:send(Sock, SendToAddr, list_to_integer(SendToPort), Data),
    pass;
  _ ->
    pass
  end;
%% 21	»ñÈ¡Íâ²¿µØÖ·
process(Sock, Addr, Port, #c2c_package{command = "21"}) ->
  Data = encode(#c2c_package{command = "21", content=list_to_binary(io_lib:format("~s|~p", [inet_parse:ntoa(Addr), Port]))}),
  gen_udp:send(Sock, Addr, Port, Data),
  pass;
%% 30	ÉêÇëÉÏ´«ÎÄ¼þ
process(Sock, Addr, Port, #c2c_package{command = "30", content = Content} = _Msg) ->
  case catch string:tokens(binary_to_list(Content), "|") of
  [FileHashValue, FileSize, FileName|_] ->
    %²éÕÒÎÄ¼þÊÇ·ñÒÑ´ò¿ª
    FileSizeOnServer = case mnesia:dirty_index_match_object(#lib_files_opened{fileid=FileHashValue, _ = '_'}, fileid) of
    [Alib_files_opened|_] ->   
      %Èô´ò¿ª£¬·µ»Øµ±Ç°ÎÄ¼þ´óÐ¡
      case file:position(Alib_files_opened#lib_files_opened.io_device, cur) of
      {ok, NewPosition} ->
        NewPosition + 1;
      Err ->
         ?ERROR_MSG("~p~n  StackTrace:~p", [Err, erlang:get_stacktrace()]),
        0
      end;
    _ ->
      %²éÕÒÎÄ¼þÊÇ·ñ´æÔÚ±¾µØ
      FilePath = case mnesia:dirty_index_match_object(#lib_files{fileid=FileHashValue, _ = '_'}, fileid) of
      [_Alib_files|_] -> %Èô´æÔÚ
        get_realfilepath(FileHashValue);
      _ ->              %Èô²»´æÔÚ£¬Ôò´´½¨
        case ejabberdex_odbc_query:get_lib_file(FileHashValue) of
          [Alib_files_cache|_] ->  %Èç¹ûÒÑÓÉ±ðÈËÉÏ´«£¬Ôò»º´æÖÁ±¾µØ
            get_lib_files_mongo(Alib_files_cache, FileName, FileHashValue, Alib_files_cache#lib_files.filepath);
          _ ->
            AddStaff = io_lib:format("~s:~p", [inet_parse:ntoa(Addr), Port]),
            create_lib_files(FileHashValue, FileSize, FileName, AddStaff)
        end
      end,      
      %´ò¿ªÎÄ¼þ
      open_lib_files(FileHashValue, FilePath),
      filelib:file_size(FilePath)
    end,
    %·µ»ØÊý¾Ý    
    Data = encode(#c2c_package{command = "30", content=list_to_binary(io_lib:format("~s|~p", [FileHashValue, FileSizeOnServer]))}),
    gen_udp:send(Sock, Addr, Port, Data),
    pass;
  _ ->
    pass
  end;
%% 31	ÉÏ´«ÎÄ¼þÄÚÈÝ
process(Sock, Addr, Port, #c2c_package{command = "31", content = Content} = _Msg) ->
  FoundIndex = index_of_binary(Content, <<"|">>, 2),
  LeftBitSize = FoundIndex * 8,
  <<Content1:LeftBitSize/bitstring, "|", Content2/binary>> = Content,
  case catch string:tokens(binary_to_list(Content1), "|") of
  [FileHashValue, Offset|_] ->
    %ÕÒµ½´ò¿ªµÄÎÄ¼þ
    case mnesia:dirty_index_match_object(#lib_files_opened{fileid=FileHashValue, _ = '_'}, fileid) of
    [Alib_files_opened|_] ->
      file:position(Alib_files_opened#lib_files_opened.io_device, list_to_integer(Offset)),
      case file:write(Alib_files_opened#lib_files_opened.io_device, Content2) of
      {error, Reason} ->
        ?ERROR_MSG("~p~n  StackTrace:~p", [Reason, erlang:get_stacktrace()]);
      _ ->
        continue
      end,
      %Î´±ÜÃâÆµ·±¸üÐÂ£¬30Ãë¸üÐÂÒ»´ÎÊ±¼äÊý¾Ý
      update_lib_files_opened_lasttime(Alib_files_opened),
      %·µ»ØÊý¾Ý
      Data = encode(#c2c_package{command = "31", content = list_to_binary(io_lib:format("~s|~s", [FileHashValue, Offset]))}),
      gen_udp:send(Sock, Addr, Port, Data);
    _ ->
      pass
    end;
  _ ->
    pass
  end;
%% 32	ÉÏ´«Íê±Ï
process(Sock, Addr, Port, #c2c_package{command = "32", content = Content} = _Msg) ->
  case catch string:tokens(binary_to_list(Content), "|") of
  [FileHashValue, _FileSize|_] ->
    %ÕÒµ½´ò¿ªµÄÎÄ¼þ
    case mnesia:dirty_index_match_object(#lib_files_opened{fileid=FileHashValue, _ = '_'}, fileid) of
    [Alib_files_opened|_] ->
      {ok, NewPosition} = file:position(Alib_files_opened#lib_files_opened.io_device, eof),
      FileSizeOnServer = NewPosition,
      mnesia:dirty_delete_object(Alib_files_opened),
      save_lib_files(Alib_files_opened#lib_files_opened.io_device, FileHashValue),
      file:close(Alib_files_opened#lib_files_opened.io_device),
      
      %·µ»ØÊý¾Ý
      Data = encode(#c2c_package{command = "32", content=list_to_binary(io_lib:format("~s|~p", [FileHashValue, FileSizeOnServer]))}),
      gen_udp:send(Sock, Addr, Port, Data);
    _ ->
      pass
    end;
  _ ->
    pass
  end;
%% 33	ÉêÇëÏÂÔØÎÄ¼þ
process(Sock, Addr, Port, #c2c_package{command = "33", content = Content} = _Msg) ->
  case catch string:tokens(binary_to_list(Content), "|") of
  [FileHashValue|_] ->
    %ÕÒµ½ÎÄ¼þ£¬ÈôÎÄ¼þ²»´æÔÚ£¬Ôò·µ»ØÎÄ¼þ´óÐ¡Îª0
    FileSizeOnServer = case ejabberdex_odbc_query:get_lib_file(FileHashValue) of
    [Alib_files|_] -> %Èô´æÔÚ
      try
        Connection = mongo_pool:get(?MONGOPOOL),
        FilePid = gridfs:do(safe, master, Connection, ?MONGODBNAME, fun()->
          Pid = gridfs:find_one(?MONGOCOLLECTION, {'_id', {bson:to_bin(Alib_files#lib_files.filepath)}}),
          gridfs_file:set_timeout(Pid, 60000),
          Pid 
      	end),
      	{ok, AFileSize} = gridfs_file:file_size(FilePid),
        gridfs_file:close(FilePid),
        AFileSize
      catch 
        Ec:Ex -> ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
        0
      end;
    _ ->              %Èô²»´æÔÚ
      0
    end,
    %·µ»ØÊý¾Ý
    Data = encode(#c2c_package{command = "33", content=list_to_binary(io_lib:format("~s|~p", [FileHashValue, FileSizeOnServer]))}),
    gen_udp:send(Sock, Addr, Port, Data),
    pass;
  _ ->
    pass
  end;
%% 34	ÏÂÔØÎÄ¼þÄÚÈÝ
process(Sock, Addr, Port, #c2c_package{command = "34", content = Content} = _Msg) ->
  case catch string:tokens(binary_to_list(Content), "|") of
  [FileHashValue, Offset|_] ->
    case ejabberdex_odbc_query:get_lib_file(FileHashValue) of
    [Alib_files|_] -> %Èô´æÔÚ
      try
        Connection = mongo_pool:get(?MONGOPOOL),
        FilePid = gridfs:do(safe, master, Connection, ?MONGODBNAME, fun()->
          Pid = gridfs:find_one(?MONGOCOLLECTION, {'_id', {bson:to_bin(Alib_files#lib_files.filepath)}}),
          gridfs_file:set_timeout(Pid, 60000),
          Pid 
      	end),
      	case gridfs_file:pread(FilePid, Offset, 1024) of
      	{ok, ReadData} ->
          %·µ»ØÊý¾Ý
          Data = encode(#c2c_package{command = "34", content = <<Content/binary, "|", ReadData/binary>>}),
          gen_udp:send(Sock, Addr, Port, Data);
        eof ->
          %·µ»ØÊý¾Ý    
          Data = encode(#c2c_package{command = "34", content = <<Content/binary, "|">>}),
          gen_udp:send(Sock, Addr, Port, Data)
      	end,
        gridfs_file:close(FilePid)
        %Î´±ÜÃâÆµ·±¸üÐÂ£¬30Ãë¸üÐÂÒ»´ÎÊ±¼äÊý¾Ý
        %update_lib_files_opened_lasttime(Alib_files_opened)
      catch 
        Ec:Ex -> ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()])
      end;
    _ ->              %Èô²»´æÔÚ
      pass
    end;
  _ ->
    pass
  end;
%% 35	ÏÂÔØÍê±Ï
process(Sock, Addr, Port, #c2c_package{command = "35", content = Content} = _Msg) ->
  case catch string:tokens(binary_to_list(Content), "|") of
  [FileHashValue, _FileSize|_] ->
    FileSizeOnServer = case ejabberdex_odbc_query:get_lib_file(FileHashValue) of
    [Alib_files|_] -> %Èô´æÔÚ
      try
        Connection = mongo_pool:get(?MONGOPOOL),
        FilePid = gridfs:do(safe, master, Connection, ?MONGODBNAME, fun()->
          Pid = gridfs:find_one(?MONGOCOLLECTION, {'_id', {bson:to_bin(Alib_files#lib_files.filepath)}}),
          gridfs_file:set_timeout(Pid, 60000),
          Pid 
      	end),
      	{ok, AFileSize} = gridfs_file:file_size(FilePid),
        gridfs_file:close(FilePid),
        AFileSize
      catch 
        Ec:Ex -> ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
        0
      end;
    _ ->              %Èô²»´æÔÚ
      0
    end,
    %·µ»ØÊý¾Ý
    Data = encode(#c2c_package{command = "35", content=list_to_binary(io_lib:format("~s|~p", [FileHashValue, FileSizeOnServer]))}),
    gen_udp:send(Sock, Addr, Port, Data);
  _ ->
    pass
  end;
%% Ä¬ÈÏ
process(_Sock, _Addr, _Port, _Msg) ->
  pass.

%% 40	ÉêÇëÉÏ´«ÎÄ¼þ
process(_Sock, Addr, Port, #c2c_package{command = "40", content = Content} = _Msg, State) ->
  ?ERROR_MSG("upload file start:~p", []),
  case catch string:tokens(binary_to_list(Content), "|") of
  [FileHashValue, FileSize, FileName|_] ->
    ?ERROR_MSG("file info:~p ~p~n", [FileName,FileHashValue]),
    %²éÕÒÎÄ¼þÊÇ·ñ´æÔÚ±¾µØ
    FilePath = case mnesia:dirty_index_match_object(#lib_files{fileid=FileHashValue, _ = '_'}, fileid) of
    [_Alib_files|_] -> %Èô´æÔÚ
      get_realfilepath(FileHashValue);
    _ ->              %Èô²»´æÔÚ£¬Ôò´´½¨
      case ejabberdex_odbc_query:get_lib_file(FileHashValue) of
        [Alib_files_cache|_] ->  %Èç¹ûÒÑÓÉ±ðÈËÉÏ´«£¬Ôò»º´æÖÁ±¾µØ
          get_lib_files_mongo(Alib_files_cache, FileName, FileHashValue, Alib_files_cache#lib_files.filepath);
        _ ->
          AddStaff = io_lib:format("~s:~p", [inet_parse:ntoa(Addr), Port]),
          create_lib_files(FileHashValue, FileSize, FileName, AddStaff)
      end
    end,      
    %´ò¿ªÎÄ¼þ
    IODevice = case file:open(FilePath, [read, write, binary]) of
    {ok, IDev} ->
      IDev;
    Err ->
      ?ERROR_MSG("~p~n  StackTrace:~p", [Err, erlang:get_stacktrace()]),
      0
    end,
    FileSizeOnServer = filelib:file_size(FilePath),
    %·µ»ØÊý¾Ý
    Data = #c2c_package{command = "40", content=list_to_binary(io_lib:format("~s|~p", [FileHashValue, FileSizeOnServer]))},
    {Data, State#state{io_device = IODevice}};
  _ ->
    pass
  end;
%% 41	ÉÏ´«ÎÄ¼þÄÚÈÝ
process(Sock, _Addr, _Port, #c2c_package{command = "41", content = Content} = _Msg, State) ->
  ?ERROR_MSG("upload file...~p", [func_utils:time()]),
  FoundIndex = index_of_binary(Content, <<"|">>, 2),
  LeftBitSize = FoundIndex * 8,
  <<Content1:LeftBitSize/bitstring, "|", Content2/binary>> = Content,
  case catch string:tokens(binary_to_list(Content1), "|") of
  [FileHashValue, Offset|_] ->
    ?ERROR_MSG("...offset：~p", [Offset]),
    file:position(State#state.io_device, list_to_integer(Offset)),
    case file:write(State#state.io_device, Content2) of
    {error, Reason} ->
      ?ERROR_MSG("~p~n  StackTrace:~p", [Reason, erlang:get_stacktrace()]),
      Data = encode(#c2c_package{command = "41", content=list_to_binary(io_lib:format("~s|~p", [FileHashValue, 0]))});
    _ ->
      Data = encode(#c2c_package{command = "41", content=list_to_binary(io_lib:format("~s|~p", [FileHashValue, 1]))})
    end,
    ?ERROR_MSG("...return：~p~n", ["41"]),
    %·µ»ØÊý¾Ý    
    (State#state.sock_mod):send(Sock, Data);
  _ ->
    ?ERROR_MSG("...return：~p~n", ["pass"]),
    pass
  end;
%% 42	ÉÏ´«Íê±Ï
process(Sock, _Addr, _Port, #c2c_package{command = "42", content = Content} = _Msg, State) ->
  ?ERROR_MSG("upload file finish£¡~p~n", [func_utils:time()]),
  case catch string:tokens(binary_to_list(Content), "|") of
  [FileHashValue, _FileSize|_] ->
    %È¡µÃÎÄ¼þ³ß´ç
    {ok, NewPosition} = file:position(State#state.io_device, eof),
    FileSizeOnServer = NewPosition,
    %±£´æÖÁMONGO¼°ODBC
    ?ERROR_MSG("save mongo and mysql£¡~p~n", []),
    save_lib_files(State#state.io_device, FileHashValue),
    %¹Ø±ÕÎÄ¼þ
    file:close(State#state.io_device),
    %·µ»ØÊý¾Ý
    Data = encode(#c2c_package{command = "42", content=list_to_binary(io_lib:format("~s|~p", [FileHashValue, FileSizeOnServer]))}),
    (State#state.sock_mod):send(Sock, Data),
    pass;
  _ ->
    pass
  end;
%% 43	ÉêÇëÏÂÔØÎÄ¼þ
process(_Sock, _Addr, _Port, #c2c_package{command = "43", content = Content} = _Msg, State) ->
  case catch string:tokens(binary_to_list(Content), "|") of
  [FileHashValue|_] ->
    %ÕÒµ½ÎÄ¼þ£¬ÈôÎÄ¼þ²»´æÔÚ£¬Ôò·µ»ØÎÄ¼þ´óÐ¡Îª0
    {FileSizeOnServer, IODevice} = case ejabberdex_odbc_query:get_lib_file(FileHashValue) of
    [Alib_files|_] -> %Èô´æÔÚ
      try
        Connection = mongo_pool:get(?MONGOPOOL),
        FilePid = gridfs:do(safe, master, Connection, ?MONGODBNAME, fun()->
          Pid = gridfs:find_one(?MONGOCOLLECTION, {'_id', {bson:to_bin(Alib_files#lib_files.filepath)}}),
          gridfs_file:set_timeout(Pid, 60000),
          Pid 
      	end),
       {ok, F1} = gridfs_file:file_size(FilePid),
       {F1, FilePid}
      catch 
        Ec:Ex -> ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
        {0, 0}
      end;
    _ ->              %Èô²»´æÔÚ
      {0, 0}
    end,
    %·µ»ØÊý¾Ý
    Data = #c2c_package{command = "43", content=list_to_binary(io_lib:format("~s|~p", [FileHashValue, FileSizeOnServer]))},
    {Data, State#state{io_device = IODevice}};
  _ ->
    pass
  end;
%% 44	ÏÂÔØÎÄ¼þÄÚÈÝ
process(_Sock, _Addr, _Port, #c2c_package{command = "44", content = Content} = _Msg, State) ->
  case catch string:tokens(binary_to_list(Content), "|") of
  [_FileHashValue, Offset|_] ->
    read_send_44(Content, State, list_to_integer(Offset)),
    pass;
  _ ->
    pass
  end;
%% 45	ÏÂÔØÍê±Ï
process(Sock, _Addr, _Port, #c2c_package{command = "45", content = Content} = _Msg, State) ->
  case catch string:tokens(binary_to_list(Content), "|") of
  [FileHashValue, _FileSize|_] ->
    FileSizeOnServer = case catch gridfs_file:file_size(State#state.io_device) of
    {ok, S} -> S;
    _ -> 0
    end,
    catch gridfs_file:close(State#state.io_device),
    %·µ»ØÊý¾Ý
    Data = encode(#c2c_package{command = "45", content=list_to_binary(io_lib:format("~s|~p", [FileHashValue, FileSizeOnServer]))}),
    (State#state.sock_mod):send(Sock, Data),
    pass;
  _ ->
    pass
  end;
%% Ä¬ÈÏ
process(_Sock, _Addr, _Port, _Msg, _State) ->
  pass.

%% Ã¿1·ÖÖÓ¼ì²éµ½1·ÖÖÓÄÚ´ÓÎ´·¢ËÍÈÎºÎÊý¾ÝµÄ¿Í»§¶Ë£¬ÔòÉ¾³ýÆä×¢²á£¬²¢ÏòÆäËü¿Í»§¶Ë·¢ËÍ¸Ã¿Í»§¶ËµÄÍË³öÐÅÏ¢
%% ÔÚmod_groupÖÐ¶¨Òå¶¨Ê±Æ÷
process_checkcycle() ->
  F = fun() -> 
    {_, NowSec, _} = now(),
    QH1 = qlc:q([X || X <- mnesia:table(group_raw_swap), 
      (element(2, X#group_raw_swap.lasttime) + 60) < NowSec]),
    QC = qlc:cursor(QH1),
    R = qlc:next_answers(QC),
    qlc:delete_cursor(QC),
    R
  end,
  {atomic, ALDelgroup_raw_swap} = mnesia:transaction(F),
  
  lists:foreach(
    fun(EItem) -> 
      mnesia:dirty_delete_object(EItem),
      %%·¢ËÍÍË³öÐÅÏ¢
      ALgroup_raw_swap = mnesia:dirty_index_match_object(#group_raw_swap{groupid=EItem#group_raw_swap.groupid, _ = '_'}, groupid),
      if 
        length(ALgroup_raw_swap) == 1 ->
          lists:foreach(fun(EItem2) -> mnesia:dirty_delete_object(EItem2) end, ALgroup_raw_swap),
          ejabberdex_odbc_query:del_group_raw_swap(EItem#group_raw_swap.groupid);
        true ->
          Pres = {xmlelement, "presence", [], 
            [{xmlelement, "rawquit", [{"xmlns", ?NS_GROUP}, {"groupid", EItem#group_raw_swap.groupid}], []}]},
          lists:foreach(
            fun(EItem2) -> 
              if
                EItem2#group_raw_swap.jid == "" ->
                  continue;
                true ->
                  ejabberd_sm:route(jlib:make_jid("", "", ""), jlib:string_to_jid(EItem2#group_raw_swap.jid), Pres) 
              end
            end, 
            ALgroup_raw_swap)
      end
    end, 
    ALDelgroup_raw_swap),
    
    case mnesia:dirty_first(group_raw_swap) of 
    '$end_of_table' ->
      ejabberdex_odbc_query:del_group_raw_swap_bynode(atom_to_list(node()));
    _ ->
      ok
    end.

%%Ã¿10·ÖÖÓ¼ì²éÒ»´ÎÊÇ·ñÓÐÎ´¹Ø±ÕµÄÎÄ¼þ£¬¹Ø±ÕËü¡£
%%ÔÚmod_offlinefileÖÐ¶¨Òå¶¨Ê±Æ÷
process_checkcycle_lib_files_opened() ->
  F = fun() -> 
    {_, NowSec, _} = now(),
    QH1 = qlc:q([X || X <- mnesia:table(lib_files_opened), 
      (element(2, X#lib_files_opened.lasttime) + 60*10) < NowSec]),
    QC = qlc:cursor(QH1),
    R = qlc:next_answers(QC),
    qlc:delete_cursor(QC),
    R
  end,
  {atomic, ALlib_files_opened} = mnesia:transaction(F),
  
  lists:foreach(
    fun(EItem) -> 
      mnesia:dirty_delete_object(EItem),
      %%¹Ø±ÕÎÄ¼þ
      file:close(EItem#lib_files_opened.io_device)
    end, 
    ALlib_files_opened).

%%Ã¿1Ìì¼ì²éÒ»´ÎÊÇ·ñ³¬¹ý7ÌìµÄÁÙÊ±ÎÄ¼þ£¬É¾³ýÖ®
%%ÔÚmod_offlinefileÖÐ¶¨Òå¶¨Ê±Æ÷
process_checkcycle_lib_files() ->
  F = fun() -> 
    {YYYY, MM, DD} = date(),
    QH1 = qlc:q([X || X <- mnesia:table(lib_files), 
      X#lib_files.savelevel == "0"
      andalso (((element(1, X#lib_files.lastdate)) < YYYY) 
                orelse ((element(2, X#lib_files.lastdate)) < MM)
                orelse ((element(3, X#lib_files.lastdate)+7) < DD))]),
    QC = qlc:cursor(QH1),
    R = qlc:next_answers(QC),
    qlc:delete_cursor(QC),
    R
  end,
  {atomic, ALlib_files} = mnesia:transaction(F),
  
  lists:foreach(
  fun(EItemX) -> 
    mnesia:dirty_delete_object(EItemX),
    file:delete(get_realfilepath(EItemX#lib_files.fileid))
  end, 
  ALlib_files),
  
  del_lib_filesout7().

process_data(NextStateName, #state{buf = Buf} = State, Data) ->
    NewBuf = <<Buf/binary, Data/binary>>,
    case decode(NewBuf) of
	{ok, Msg, Tail} ->
	    gen_fsm:send_event(self(), Msg),
	    process_data(NextStateName, State#state{buf = <<>>}, Tail);
	empty ->
	    NewState = State#state{buf = <<>>},
	    {next_state, NextStateName, update_state(NewState)};
	more when size(NewBuf) < ?MAX_BUF_SIZE ->
	    NewState = State#state{buf = NewBuf},
	    {next_state, NextStateName, update_state(NewState)};
	_ ->
	    {stop, normal, State}
    end.

update_state(#state{sock = Sock} = State) ->
    case State#state.sock_mod of
	gen_tcp ->
	    inet:setopts(Sock, [{active, once}]);
	SockMod ->
	    SockMod:setopts(Sock, [{active, once}])
    end,
    cancel_timer(State#state.tref),
    TRef = erlang:start_timer(?TIMEOUT, self(), stop),
    State#state{tref = TRef}.

cancel_timer(TRef) ->
    case erlang:cancel_timer(TRef) of
	false ->
	    receive
                {timeout, TRef, _} ->
                    ok
            after 0 ->
                    ok
            end;
        _ ->
            ok
    end.

%%--------------------------------------------------------------------
%%% °ü¸ñÊ½º¯Êý
%%--------------------------------------------------------------------
decode(<<Command:16/bitstring, Len:32, Content:Len/binary, Tail/binary>>) ->
  {ok, #c2c_package{command = binary_to_list(Command), len = Len, content = Content}, Tail};
decode(<<Head/binary>>) when size(Head) < 6 -> 
  more;
decode(<<_Command:16/bitstring, _Len:32, _/binary>>) ->
  more;
decode(<<>>) ->
  empty;
decode(_) ->
  {error, unparsed}.

encode(#c2c_package{command = Command, content = Content} = _Msg) ->
  Len = size(Content),
  C = list_to_binary(Command),
  <<C/binary, Len:32, Content/binary>>.  

%%--------------------------------------------------------------------
%%Ä¬ÈÏÎÄ¼þ»º´æÂ·¾¶
-define(DEFAULTROOTPATH, code:root_dir() ++ "/fileupload/"). 
%%--------------------------------------------------------------------
%%% È¡µÃÎÄ¼þÕæÊµÂ·¾¶
%%--------------------------------------------------------------------
get_realfilepath(Fileid) ->
  ?DEFAULTROOTPATH ++ Fileid.
  
%%--------------------------------------------------------------------
%%% ´ÓMONGOÖÐÈ¡µÃÎÄ¼þ»º´æÖÁ±¾µØ
get_lib_files_mongo(Alib_files_cache, FileName, FileHashValue, QMongoId) ->
  RealFilePath = get_lib_files_mongo2local(FileHashValue, QMongoId),
  mnesia:dirty_write(Alib_files_cache#lib_files{filepath = FileName, savelevel = "0"}),
  RealFilePath.
  
get_lib_files_mongo2local(FileHashValue, QMongoId) ->
  filelib:ensure_dir(get_realfilepath("")),
  RealFilePath = get_realfilepath(FileHashValue),
  try
    Connection = mongo_pool:get(?MONGOPOOL),
    FilePid = gridfs:do(safe, master, Connection, ?MONGODBNAME, fun()->
      Pid = gridfs:find_one(?MONGOCOLLECTION, {'_id', {bson:to_bin(QMongoId)}}),
      gridfs_file:set_timeout(Pid, 60000),
      Pid 
  	end),
  	{ok, IODevice} = file:open(RealFilePath, [read, write, binary]),
  	read_write_mongo2local(FilePid, IODevice, 0),  	
  	gridfs_file:close(FilePid),
  	file:close(IODevice)
  catch 
    Ec:Ex -> ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()])
  end,
  RealFilePath.
  
read_write_mongo2local(FilePid, IODevice, Offset) ->
  case gridfs_file:pread(FilePid, Offset, 256*1024) of
  {ok, ReadData} ->
    file:write(IODevice, ReadData),
    read_write_mongo2local(FilePid, IODevice, Offset + size(ReadData));
  eof ->
    continue;
  _ ->
    continue
  end.

%%--------------------------------------------------------------------
%%% ´´½¨ÎÄ¼þ
%%% FileHashValue ÎÄ¼þHASHÖµ
%%% FileSize ÎÄ¼þ³ß´ç
%%% FileName ÎÄ¼þÃû
%%% AddStaff ¼ÓÈëÈËÔ±
%%% ·µ»Ø´´½¨µÄÎÄ¼þÃû£¬´øÂ·¾¶
%%% ½ö´´½¨±¾µØ»º´æÎÄ¼þ£¬´ýÉÏ´«Íê±ÏºóÔÙÐ´ÈëÊý¾Ý¿â¼°MONGOÖÐ
%%% ËùÓÐ»º´æÎÄ¼þ´æ·ÅÓÚ $EJABBERD_HOME/fileupload/Ä¿Â¼£¬ÒÔFileHashValueÎª´æ·ÅÎÄ¼þµÄÊµ¼ÊÎÄ¼þÃû£¬ÎÞÀ©Õ¹Ãû
%%--------------------------------------------------------------------
create_lib_files(FileHashValue, _FileSize, FileName, AddStaff) ->
  filelib:ensure_dir(get_realfilepath("")),
  RealFilePath = get_realfilepath(FileHashValue),
  case file:write_file(RealFilePath, []) of
  {error, Reason} ->
    ?ERROR_MSG("~p~n  StackTrace:~p", [Reason, erlang:get_stacktrace()]);
  _ ->
    continue
  end,
  %%±¾µØ»º´æÖÐfilepathÊÇÎÄ¼þÃû£¬ODBCÊý¾Ý¿âÖÐÊÇMONGOµÄID
  mnesia:dirty_write(#lib_files{fileid = FileHashValue, filepath = FileName, filedesc = "", addstaff = AddStaff, savelevel = "0", lastdate = date()}),
  RealFilePath.
  
%%--------------------------------------------------------------------
%% ´ò¿ªÎÄ¼þ
%% FileHashValue
%% FilePath ´øÂ·¾¶ÎÄ¼þÃû
%%--------------------------------------------------------------------
open_lib_files(FileHashValue, FilePath) ->
  case mnesia:dirty_index_match_object(#lib_files_opened{fileid = FileHashValue, _ = '_'}, fileid) of
  [] ->
    case file:open(FilePath, [read, write, binary]) of
    {ok, IODevice} ->
      mnesia:dirty_write(#lib_files_opened{fileid = FileHashValue, io_device = IODevice, lasttime = now()});
    Err ->
      ?ERROR_MSG("~p~n  StackTrace:~p", [Err, erlang:get_stacktrace()])
    end;
  _ ->
    continue  
  end.

%%--------------------------------------------------------------------
%%% ´æ´¢ÎÄ¼þÈëmongo£¬²¢¼ÇÂ¼£¬Í¬Ê±É¾³ý±¾µØ¼ÇÂ¼
save_lib_files(IODevice, FileHashValue) ->
  try
    Connection = mongo_pool:get(?MONGOPOOL),
    ?ERROR_MSG("mongo Connectioned~p~n", [Connection]),
    case mnesia:dirty_index_match_object(#lib_files{fileid = FileHashValue, _ = '_'}, fileid) of
    [Alib_files|_] ->
      %ÊÇ·ñÒÑÓÉÉÏ´«ÍêÕûµÄÎÄ¼þ
      {Smd5, Asavelevel} = case ejabberdex_odbc_query:get_lib_file(FileHashValue) of
      [Alib_files_odbc|_] ->
        ?ERROR_MSG("mongo gridfs write ~p~n", [func_utils:time()]),
        FilePid = gridfs:do(safe, master, Connection, ?MONGODBNAME, fun()->
          Pid = gridfs:find_one(?MONGOCOLLECTION, {'_id', {bson:to_bin(Alib_files_odbc#lib_files.filepath)}}),
          gridfs_file:set_timeout(Pid, 60000),
          Pid 
      	end),
      	{ok, Bmd5} = gridfs_file:md5(FilePid),
      	gridfs_file:close(FilePid),
        ?ERROR_MSG("mongo gridfs write finish ~p~n", [func_utils:time()]),
      	{binary_to_list(Bmd5), Alib_files_odbc#lib_files.savelevel};
      _ ->
        {"", "0"}
      end,
      case string:to_lower(FileHashValue) of
      Smd5 -> %ÒÑÓÐÉÏ´«ÍêÕûµÄÎÄ¼þ
        continue;
      _ ->      
        %É¾³ýODBCÖÐ¿ÉÄÜÖØ¸´µÄÎÄ¼þ
        del_lib_files(FileHashValue),
        %´æÈëmongo
        Bfilename = list_to_binary(Alib_files#lib_files.filepath),
        Acollection = gridfs:do(safe, master, Connection, ?MONGODBNAME, fun()->
          gridfs:insert(?MONGOCOLLECTION, Bfilename, IODevice)
        end), 
        %´æÈëodbc
        Sid = bson:bin_to_hexstr(element(1, bson:lookup('_id', Acollection, {<<>>}))),
        ejabberdex_odbc_query:ins_lib_files(Alib_files#lib_files{filepath = Sid, savelevel = Asavelevel, lastdate = date()})     
      end,
      %É¾³ý±¾µØ¼ÇÂ¼
      mnesia:dirty_delete_object(Alib_files),
      file:delete(get_realfilepath(Alib_files#lib_files.fileid));
    _ ->
      continue  
    end
  catch 
    Ec:Ex -> ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()])
  end.

%%--------------------------------------------------------------------
%%% É¾³ýÎÄµµ
del_lib_files(FileHashValue) ->
  try
    case ejabberdex_odbc_query:get_lib_file(FileHashValue) of 
      [Alib_files|_] ->  
        Connection = mongo_pool:get(?MONGOPOOL),
        gridfs:do(safe, master, Connection, ?MONGODBNAME, fun()->
          gridfs:delete(?MONGOCOLLECTION, {'_id',{bson:to_bin(Alib_files#lib_files.filepath)}})      
        end),        
        ejabberdex_odbc_query:del_lib_file(FileHashValue);
      _ ->
        continue
    end
  catch 
    Ec:Ex -> ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()])
  end.
  
%%--------------------------------------------------------------------
%%% É¾³ý³¬¹ý7ÌìµÄÁÙÊ±ÎÄ¼þ
del_lib_filesout7() ->
  try
    Connection = mongo_pool:get(?MONGOPOOL),
    case ejabberdex_odbc_query:get_lib_fileout7(10) of
      [] ->
        continue;
      ALlibfile ->
        lists:foreach(fun(EItemX) -> 
          gridfs:do(safe, master, Connection, ?MONGODBNAME, fun()->
            gridfs:delete(?MONGOCOLLECTION, {'_id',{bson:to_bin(element(2, EItemX))}})      
          end),        
          ejabberdex_odbc_query:del_lib_file(element(1, EItemX))
        end,
        ALlibfile),
        del_lib_filesout7()
    end
  catch 
    Ec:Ex -> ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()])
  end.
  
%%--------------------------------------------------------------------
%% Î´±ÜÃâÆµ·±¸üÐÂ£¬30Ãë¸üÐÂÒ»´ÎÊ±¼äÊý¾Ý
%% Alib_files_opened ´ò¿ªµÄÎÄ¼þ¼ÇÂ¼
%%--------------------------------------------------------------------
update_lib_files_opened_lasttime(Alib_files_opened) ->
  {_, NowSec, _} = now(),
  if 
    (element(2, Alib_files_opened#lib_files_opened.lasttime) + 30) < NowSec  ->
      mnesia:dirty_delete_object(Alib_files_opened),
      mnesia:dirty_write(Alib_files_opened#lib_files_opened{lasttime = now()});
    true ->
      continue
  end.
  
%%%----------------------------------------------------------------------
%%% ¸ù¾ÝÎÄ¼þIDÉ¾³ýlib_filesÖÐµÄÎÄ¼þ
del_lib_files_offlinefile(FileID) ->
  try
    Connection = mongo_pool:get(?MONGOPOOL),
    ALlib_files = ejabberdex_odbc_query:get_lib_file_offlinefile(FileID),
    lists:foreach(
    fun(EItemX) -> 
      gridfs:do(safe, master, Connection, ?MONGODBNAME, fun()->
        gridfs:delete(?MONGOCOLLECTION, {'_id',{bson:to_bin(EItemX#lib_files.filepath)}})      
      end),        
      ejabberdex_odbc_query:del_lib_file(EItemX#lib_files.fileid)
    end, 
    ALlib_files)
  catch 
    Ec:Ex -> ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()])
  end.
  
%%%----------------------------------------------------------------------
%%% ¸ù¾ÝÎÄ¼þIDÉ¾³ýlib_filesÖÐµÄÎÄ¼þ
del_lib_files_sharefile(FileID) ->
  try
    Connection = mongo_pool:get(?MONGOPOOL),
    ALlib_files = ejabberdex_odbc_query:get_lib_file_sharefile(FileID),
    lists:foreach(
    fun(EItemX) -> 
      gridfs:do(safe, master, Connection, ?MONGODBNAME, fun()->
        gridfs:delete(?MONGOCOLLECTION, {'_id',{bson:to_bin(EItemX#lib_files.filepath)}})      
      end),        
      ejabberdex_odbc_query:del_lib_file(EItemX#lib_files.fileid)
    end, 
    ALlib_files)
  catch 
    Ec:Ex -> ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()])
  end.
  
%%%----------------------------------------------------------------------
%%% ¼ÆËãBPartÔÚBDataµÚRepeatCount´Î³öÏÖµÄÎ»ÖÃ
%%% BData binary()
%%% BPart binary()
%%% RepeatCount int()
index_of_binary(BData, BPart, RepeatCount) ->
  BPartBitSize = bit_size(BPart),
  index_of_binary_inner(BData, BPart, RepeatCount, BPartBitSize, 0, 0).
index_of_binary_inner(BData, BPart, RepeatCount, BPartBitSize, StartIndex, NowCount) ->
  case BData of 
    <<>> ->
      -1;
    <<BPart:BPartBitSize/bitstring, _Other/binary>> when NowCount == RepeatCount - 1 ->
      StartIndex;
    <<BPart:BPartBitSize/bitstring, _Other/binary>> -> 
        <<_:8, Other/binary>> = BData,
        index_of_binary_inner(Other, BPart, RepeatCount, BPartBitSize, StartIndex+1, NowCount+1);
    <<_:8, Other/binary>> ->
      index_of_binary_inner(Other, BPart, RepeatCount, BPartBitSize, StartIndex+1, NowCount);
    _ ->
      -2
  end.
  
%%%----------------------------------------------------------------------
%%% ¶ÁÈ¡ÎÄ¼þÄÚÈÝ²¢·¢ËÍ
%%% ÓÃÓÚ44	ÏÂÔØÎÄ¼þÄÚÈÝ
read_send_44(Content, State, Offset) ->
  %Ò»´ÎÐÔ´ÓMONGOÖÐ¶ÁÈ¡256K£¨Ò»¿é£©³öÀ´
  case catch gridfs_file:pread(State#state.io_device, Offset, 1024*256) of
  {ok, ReadData} ->
    %·µ»ØÊý¾Ý
    read_send_44_256k(Content, State, ReadData),
    read_send_44(Content, State, Offset+size(ReadData));
  eof ->
    %·µ»ØÊý¾Ý    
    Data = encode(#c2c_package{command = "44", content = <<Content/binary, "|">>}),
    (State#state.sock_mod):send(State#state.sock, Data);
  {error, Reason} ->
    ?ERROR_MSG("~p~n  StackTrace:~p", [Reason, erlang:get_stacktrace()]);
  _ ->
    continue
  end.
  
read_send_44_256k(_Content, _State, <<>>) ->
  ok;
read_send_44_256k(Content, State, <<ReadData:8192/binary, Other/binary>>) ->
  read_send_44_1k(Content, State, ReadData),
  read_send_44_256k(Content, State, Other);
read_send_44_256k(Content, State, ReadData) ->
  read_send_44_1k(Content, State, ReadData).
  
read_send_44_1k(Content, State, ReadData) ->  
  Data = encode(#c2c_package{command = "44", content = <<Content/binary, "|", ReadData/binary>>}),
  (State#state.sock_mod):send(State#state.sock, Data).
  
%%%----------------------------------------------------------------------
  
  