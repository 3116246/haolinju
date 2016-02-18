%%%----------------------------------------------------------------------
%%% @doc ejabberex��ʼ�����
%%% mod������{mongo, {Server::string(), Port::integer(), DataBase::atom(), User::string(), Password::string()}} mongodb���Ӳ���
%%%----------------------------------------------------------------------
-module(mod_ejabberdex_init).
-author('krislee@sina.com').

-behaviour(gen_mod).

-export([start/2, stop/1,
         initdb/0
         ]).


-include("../../ejabberd-2.1.11/include/ejabberd.hrl").
-include("../include/mod_ejabberdex_init.hrl").

%%%----------------------------------------------------------------------
start(_Host, Opts) ->
  %����������ֻ����һ��
  HasLoaded = case catch mnesia:dirty_first(has_loaded_mod_ex) of
    {'EXIT', _Reason} ->
      0;
    '$end_of_table' -> 
      0;
    _ ->
      1
  end,  
  case HasLoaded of 
    0 ->
      initdb(),    
      application:start(bson),
      application:start(mongodb),
      Amongoconn_opt = gen_mod:get_opt(mongo, Opts, ?MONGOCONN_OPT_D),
      %%mongo_sup:start_pool(?MONGOPOOL, ?MONGOPOOLSIZE, {element(1, Amongoconn_opt), element(2, Amongoconn_opt), element(4, Amongoconn_opt), element(5, Amongoconn_opt)}),
      ejabberdex_cluster_router_sup:start_link(),  %%����Ⱥ��·�ɹ۲��߽���
      apns:start(), %%����apns
      % apns:connect(apns_conn), %%����apns���ӣ�������Ĭ��Ϊapns_conn
      timer:apply_after(1, apns, connect, [apns_conn]),
      ok;
    _ ->
      ok
  end,
  ok.
%%%----------------------------------------------------------------------
stop(_Host) ->
  ok.
  
%%%----------------------------------------------------------------------
%%% ��ʼ�����д洢��mnesia�еı� 
initdb() ->
  try     
    %%����ʶ��ǰģ���Ƿ��Ѽ���
    mnesia:create_table(has_loaded_mod_ex, [{ram_copies,[node()]},{local_content, true}, {attributes, [key, val]} ]),
    mnesia:dirty_write({has_loaded_mod_ex, loaded, 1}),
    
    mnesia:create_table(group_raw_swap, [{disc_copies,[node()]},{type, bag}, {attributes, record_info(fields, group_raw_swap)}]),
    mnesia:add_table_index(group_raw_swap, groupid),
    mnesia:add_table_index(group_raw_swap, jid),
    mnesia:add_table_index(group_raw_swap, ip),

    mnesia:create_table(lib_files, [{disc_copies,[node()]},{type, bag}, {attributes, record_info(fields, lib_files)}]),
    mnesia:add_table_index(lib_files, fileid),
    
    mnesia:create_table(lib_files_opened, [{disc_copies,[node()]},{type, bag}, {attributes, record_info(fields, lib_files_opened)}]),
    mnesia:add_table_index(lib_files_opened, fileid),

    ok    
  catch
    Ec:Ex ->
      ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()])
  end.