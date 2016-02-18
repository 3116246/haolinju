%%%----------------------------------------------------------------------
%%% ¼¯ÈºÏûÏ¢Â·ÓÉ
%%%----------------------------------------------------------------------

%%% @doc ¼¯ÈºÏûÏ¢Â·ÓÉ.
%%%
%%% Ö÷ÒªÊµÏÖ·þÎñÆ÷¼äÏûÏ¢×ª·¢¡£
%%%----------------------------------------------------------------------

-module(ejabberdex_cluster_router).
-author('feihu929@sina.com').

-behaviour(gen_server).

%% API
-export([route/4,
    list_to_atom_ex/1,
    get_cluster_node/0
  ]).

-export([start_link/0]).

%% gen_server callbacks
-export([init/1, handle_call/3, handle_cast/2, handle_info/2,
   terminate/2, code_change/3]).

-include("../../ejabberd-2.1.11/include/ejabberd.hrl").
-include("../../ejabberd-2.1.11/include/jlib.hrl").

%%»º´æÔÚÏß½Úµã
-record(cluster_node, {node, nodetype, isstart, ip, ismonitor}).
-record(state, {nodetype}).

%%====================================================================
%% API
%%====================================================================
%%--------------------------------------------------------------------
%% Function: start_link() -> {ok,Pid} | ignore | {error,Error}
%% Description: Starts the server
%%--------------------------------------------------------------------
start_link() ->
    gen_server:start_link({local, ?MODULE}, ?MODULE, [], []).

%%--------------------------------------------------------------------
%% µ±MsgÂ·ÓÉÖÁNode½ÚµãµÄus+resÓÃ»§
%% Node node() ½ÚµãÃû Èç 'ejabberd@192.168.10.10'
%% US   string() ÓÃ»§ÃûBareJID£¬Èç£º"aaaa@fafacn.com"
%% Res  string() ×ÊÔ´Ãû£¬Èç£º"FaFaWin"
%% Msg  anytype  ·¢ËÍµÄÐÅÏ¢
route(Node, US, Res, Msg) when is_list(Node) ->
  route(list_to_atom_ex(Node), US, Res, Msg);
route(Node, US, Res, Msg) ->
    case catch do_route_out(Node, US, Res, Msg) of
      {'EXIT', Reason} ->
        ?ERROR_MSG("~p~nwhen processing: ~p",
           [Reason, {Node, US, Res, Msg}]);
      _ ->
        ok
    end.

%%½«NodeName string() ×ª»»Îªatom
list_to_atom_ex(NodeName) when is_list(NodeName) ->
  case catch list_to_existing_atom(NodeName) of
    {'EXIT', _Reason} ->
        list_to_atom(NodeName);
    Atom ->
        Atom
  end.

%% ·µ»Ø¼¯Èº½ÚµãÐÅÏ¢[#cluster_node{}]    
get_cluster_node() ->
  mnesia:dirty_match_object(#cluster_node{_ = '_'}).

%%====================================================================
%% gen_server callbacks
%%====================================================================

%%--------------------------------------------------------------------
%% Function: init(Args) -> {ok, State} |
%%                         {ok, State, Timeout} |
%%                         ignore               |
%%                         {stop, Reason}
%% Description: Initiates the server
%%--------------------------------------------------------------------
init([]) ->
    update_tables(),
    NodeName = atom_to_list(node()),
    mnesia:create_table(cluster_node,
      [{ram_copies, [node()]},
       {type, bag},
       {attributes, record_info(fields, cluster_node)},
       {local_content, true}]),

	  Nodes = load_cluster_node(),
    CacheServers=[{A#cluster_node.ip,11211,1}||A<-Nodes],
    erlmc:start(case CacheServers of []-> [{"127.0.0.1", 11211, 1}]; _-> CacheServers end),
    Rs = ejabberd_odbc:sql_query(
      "",
      ["select us,res from global_session where  node='",NodeName,"' "]),
    case Rs of
      {selected, _, Items} ->
        lists:map(
          fun(Item) -> 
            User = element(1, Item),
            LResource = element(2, Item),
            Key = User++"/"++LResource,
            Key2 = User++"/"++LResource++NodeName,
            erlmc:delete(User),
            erlmc:delete(Key),
            erlmc:delete(Key2)
          end, 
          Items);
      _ ->
        []
    end,
    ejabberd_odbc:sql_query(
      "",
      ["call p_del_global_session('", NodeName, "')"]),

    io:format("cache service init finished~n"),    
    self() ! init_montitor,
    {ok, #state{}}.

%%--------------------------------------------------------------------
%% Function: %% handle_call(Request, From, State) -> {reply, Reply, State} |
%%                                      {reply, Reply, State, Timeout} |
%%                                      {noreply, State} |
%%                                      {noreply, State, Timeout} |
%%                                      {stop, Reason, Reply, State} |
%%                                      {stop, Reason, State}
%% Description: Handling call messages
%%--------------------------------------------------------------------
handle_call(_Request, _From, State) ->
    Reply = ok,
    {reply, Reply, State}.

%%--------------------------------------------------------------------
%% Function: handle_cast(Msg, State) -> {noreply, State} |
%%                                      {noreply, State, Timeout} |
%%                                      {stop, Reason, State}
%% Description: Handling cast messages
%%--------------------------------------------------------------------
handle_cast(_Msg, State) ->
    {noreply, State}.

%%--------------------------------------------------------------------
%% Function: handle_info(Info, State) -> {noreply, State} |
%%                                       {noreply, State, Timeout} |
%%                                       {stop, Reason, State}
%% Description: Handling all non call/cast messages
%%--------------------------------------------------------------------
handle_info(init_montitor, State) ->
	erlmc:set(atom_to_list(node()),list_to_binary("1")),
    {ALcluster_node_self, ALcluster_node_other} = lists:partition(fun(Item) -> Item#cluster_node.node == node() end, load_cluster_node()),
    SelfNodeType = case ALcluster_node_self of
      [] ->
        nodedisabled;
      [SelfNode] ->
        SelfNode#cluster_node.nodetype;
      _ ->
        "0"
    end,
    
    case SelfNodeType of
      nodedisabled ->
        continue;
      "1" ->    %%Íø¹Ø
        %%¼à¿ØÆÕÍ¨½áµã£¬²¢Í¨ÖªÆä×Ô¼ºÉÏÏßÁË
        case [X || X <- ALcluster_node_other, X#cluster_node.nodetype /= "1"] of
        Items when is_list(Items) ->
          lists:foreach(fun(Item) ->
              {?MODULE, Item#cluster_node.node} ! {gateway_node_up, node()},
              F = fun() ->
                mnesia:delete_object(Item),
                erlmc:set(Item#cluster_node.node,list_to_binary("1")),
                mnesia:write(Item#cluster_node{ismonitor="1"})
              end,
              mnesia:transaction(F),
              erlang:monitor(process, {?MODULE, Item#cluster_node.node})
            end,
            Items);
        _ ->
          continue
        end;
      _ ->      %%ÆÕÍ¨½Úµã
        %%¼à¿Ø¿ÉÓÃµÄÍø¹Ø½áµã£¬Èç¹ûÃ»ÓÐÍø¹Ø½Úµã£¬ÓÉÆÕÍ¨½ÚµãÏà»¥¼à¿ØÊÇ·ñÔÚÏß
        case [X || X <- ALcluster_node_other, X#cluster_node.nodetype == "1"] of
        [] ->
          lists:foreach(fun(Item) -> 
              {?MODULE, Item#cluster_node.node} ! {normal_node_up, node()},
              F = fun() ->
                mnesia:delete_object(Item),
                erlmc:set(Item#cluster_node.node,list_to_binary("1")),
                mnesia:write(Item#cluster_node{ismonitor="1"})
              end,
              mnesia:transaction(F),
              erlang:monitor(process, {?MODULE, Item#cluster_node.node})
            end,
            ALcluster_node_other);
        Items ->
          lists:foreach(fun(Item) ->
              {?MODULE, Item#cluster_node.node} ! {normal_node_up, node()},
              F = fun() ->
                mnesia:delete_object(Item),
                erlmc:set(Item#cluster_node.node,list_to_binary("1")),
                mnesia:write(Item#cluster_node{ismonitor="1"})
              end,
              mnesia:transaction(F),
              erlang:monitor(process, {?MODULE, Item#cluster_node.node})
            end,
            Items)
        end
    end,
    {noreply, State#state{nodetype = SelfNodeType}};
handle_info({gateway_node_up, FromNode}, State) ->
    ?INFO_MSG("cluster gateway_node_up: ~p", [FromNode]),
    F = fun() ->
      mnesia:delete({cluster_node, FromNode}),
      erlmc:set(FromNode,list_to_binary("1")),
      mnesia:write(#cluster_node{node = FromNode, nodetype="1", isstart="1", ip="", ismonitor="1"})
    end,
    mnesia:transaction(F),
    erlang:monitor(process, {?MODULE, FromNode}),
    {noreply, State};
handle_info({normal_node_up, FromNode}, State) ->
    ?INFO_MSG("cluster normal_node_up: ~p", [FromNode]),
    F = fun() ->
      mnesia:delete({cluster_node, FromNode}),
      erlmc:set(FromNode,list_to_binary("1")),
      mnesia:write(#cluster_node{node = FromNode, nodetype="0", isstart="1", ip="", ismonitor="1"})
    end,
    mnesia:transaction(F),
    erlang:monitor(process, {?MODULE, FromNode}),
    {noreply, State};
handle_info({'DOWN', _Ref, _Type, {?MODULE, Node}, _Info}, State) ->
    ?INFO_MSG("cluster node DOWN: ~p", [Node]),
    F = fun() ->
      erlmc:delete(Node),
      mnesia:delete({cluster_node, Node})      
    end,
    mnesia:transaction(F),
    stop_node(atom_to_list(Node)),
    {noreply, State};
handle_info({m_route_out, IsOnlineNode, Node, US, Res, Msg}, State) ->
    F = fun() ->
      mnesia:delete_object(IsOnlineNode),
      mnesia:write(IsOnlineNode#cluster_node{ismonitor="1"})
    end,
    mnesia:transaction(F),
    erlang:monitor(process, {?MODULE, Node}),
    {?MODULE, node()} ! {route_out, Node, US, Res, Msg},
    {noreply, State};
handle_info({route_out, Node, US, Res, Msg}, State) ->
    case catch do_route_out(Node, US, Res, Msg) of
      {'EXIT', Reason} ->
          ?ERROR_MSG("~p~nwhen processing: ~p",
               [Reason, {Node, US, Res, Msg}]);
      _ ->
          ok
    end,
    {noreply, State};
handle_info({route_in, FromNode, Node, US, Res, Msg}, State) when State#state.nodetype == "1" ->
    case catch do_route_in_gateway(FromNode, Node, US, Res, Msg) of
      {'EXIT', Reason} ->
          ?ERROR_MSG("~p~nwhen processing: ~p",
               [Reason, {FromNode, Node, US, Res, Msg}]);
      _ ->
          ok
    end,
    {noreply, State};
handle_info({route_in, FromNode, Node, US, Res, Msg}, State) when Node == node() ->
    case catch do_route_in_normal(FromNode, Node, US, Res, Msg) of
      {'EXIT', Reason} ->
          ?ERROR_MSG("~p~nwhen processing: ~p",
               [Reason, {FromNode, Node, US, Res, Msg}]);
      _ ->
          ok
    end,
    {noreply, State};
handle_info(_Info, State) ->
    {noreply, State}.

%%--------------------------------------------------------------------
%% Function: terminate(Reason, State) -> void()
%% Description: This function is called by a gen_server when it is about to
%% terminate. It should be the opposite of Module:init/1 and do any necessary
%% cleaning up. When it returns, the gen_server terminates with Reason.
%% The return value is ignored.
%%--------------------------------------------------------------------
terminate(_Reason, _State) ->
    ok.

%%--------------------------------------------------------------------
%% Func: code_change(OldVsn, State, Extra) -> {ok, NewState}
%% Description: Convert process state when code is changed
%%--------------------------------------------------------------------
code_change(_OldVsn, State, _Extra) ->
    {ok, State}.

%%--------------------------------------------------------------------
%%% Internal functions
%%--------------------------------------------------------------------
do_route_out(Node, US, Res, Msg) when not is_atom(Node) orelse Node == node() ->
    ?DEBUG("cluster route~n\tnode ~p~n\tus ~p~n\tres ~p~n\tmsg ~p~n", [Node, US, Res, Msg]),
    drop;
do_route_out(Node, US, Res, Msg) ->
    ?DEBUG("cluster route~n\tnode ~p~n\tus ~p~n\tres ~p~n\tmsg ~p~n", [Node, US, Res, Msg]),
        
    %%²éÕÒÍø¹Ø½Úµã£¬ÈôÓÐÍø¹Ø£¬ÔòÑ¡ÔñÒ»¸öÍø¹Ø·¢ËÍ£¬·ñÔòÖ±½Ó·¢¸øÄ¿±ê½Úµã
    ALcluster_node = get_cluster_node(),
    case lists:partition(fun(Item) -> Item#cluster_node.nodetype == "1" end, ALcluster_node) of
      {ALcluster_node_gateway, _} when ALcluster_node_gateway /= [] ->
        #cluster_node{node = NodeGateway} = lists:nth(erlang:phash(now(), length(ALcluster_node_gateway)), ALcluster_node_gateway),
        {?MODULE, NodeGateway} ! {route_in, node(), Node, US, Res, Msg};
      {[], ALcluster_node_normal} ->
        case lists:filter(fun(XItem) -> XItem#cluster_node.node == Node end, ALcluster_node_normal) of
          [IsOnlineNode|_] when IsOnlineNode#cluster_node.ismonitor == "1" ->
            {?MODULE, Node} ! {route_in, node(), Node, US, Res, Msg};
          [IsOnlineNode|_] when IsOnlineNode#cluster_node.ismonitor /= "1" ->
            {?MODULE, node()} ! {m_route_out, IsOnlineNode, Node, US, Res, Msg};
          _ -> %%È«¾ÖsessionÖÐ´æÔÚ£¬µ«½ÚµãÓÖ²»ÔÚÏß
            do_route_node_not_online(Node, US, Res, Msg)
        end;
      _ ->     %%È«¾ÖsessionÖÐ´æÔÚ£¬µ«½ÚµãÓÖ²»ÔÚÏß
        do_route_node_not_online(Node, US, Res, Msg)
    end.
    
do_route_node_not_online(Node, US, Res, Msg) ->
  erlmc:delete(Node),
  ejabberd_odbc:sql_query(
      "",
      ["delete from global_session "
       "where us='", US, "' and res='", Res, "' and node='", atom_to_list(Node), "'"]),
  handle_node_not_online_route_msg(US, Res, Msg).
    
do_route_in_gateway(_FromNode, Node, US, Res, Msg) when Node == node() ->   %%±ÜÃâÑ­»·£¬¶ªÆúÄ¿µÄ½áµãÎªÍø¹ØµÄroute_msg
  ?WARNING_MSG("cluster drop routing to gateway node msg: ~p~n ~p~n ~p~n", [US, Res, Msg]),
  drop;
do_route_in_gateway(_FromNode, Node, US, Res, Msg) ->
  case mnesia:dirty_read(cluster_node, Node) of
    [_Acluster_node|_] ->    %%Õý³£Çé¿ö£¬Ö±½Ó×ª·¢
      {?MODULE, Node} ! {route_in, node(), Node, US, Res, Msg};
    _ ->  %%¿ÉÄÜÊÇÈ«¾ÖsessionÖÐÓÐ£¬µ«¸Ã½ÚµãÓÖÏÂÏß
      do_route_node_not_online(Node, US, Res, Msg)
  end.

do_route_in_normal(_FromNode, _Node, US, Res, Msg) ->
  handle_route_msg(US, Res, Msg).

handle_node_not_online_route_msg(US, Res, {_From, _To, _Packet}=Msg) ->
  handle_route_msg(US, Res, Msg);
handle_node_not_online_route_msg(_US, _Res, _Msg) ->
  drop. 
  
handle_route_msg(US, Res, replaced) ->
  [U, S] = string:tokens(US, "@"),
  SIDs = ejabberd_sm:get_resource_sessions(U, S, Res),
  lists:foreach(
	      fun({_, Pid}) ->
		      Pid ! replaced;
		    (_) -> ok
	      end, SIDs);
handle_route_msg(_US, _Res, {From, To, Packet}) ->
  ejabberd_router:route(From, To, Packet);
handle_route_msg(US, Res, Msg) ->
  ?WARNING_MSG("cluster drop not deal route_msg: ~p~n ~p~n ~p~n", [US, Res, Msg]),
  drop.  
  

%% ´ÓmysqlÖÐ³õÊ¼»¯Êý¾ÝÖÁmnesiaÖÐ£¬»º´æ£¬·µ»Ø[#cluster_node{}]
load_cluster_node() ->  
    ejabberd_odbc:sql_query(
      "",
      ["update cluster_node set isstart='1', start_time=now() "
       "where  isenabled='1' and nodename='", atom_to_list(node()), "'"]),
    Rs = ejabberd_odbc:sql_query(
      "",
      ["select nodename, nodetype, isstart, ip from cluster_node "
       "where  isenabled='1' and isstart='1'"]),
    case Rs of
      {selected, _, Items} ->
        lists:map(
          fun(Item) -> 
            Acluster_node = #cluster_node{node = list_to_atom_ex(element(1, Item)), nodetype = element(2, Item), isstart = element(3, Item), ip = element(4, Item), ismonitor="0"},
            mnesia:dirty_delete(cluster_node, Acluster_node#cluster_node.node), 
            mnesia:dirty_write(Acluster_node), 
            Acluster_node
          end, 
          Items);
      _ ->
        []
    end.

%%µ±Í£Ö¹½áµãÊ±µ÷ÓÃ£¬¸üÐÂ½áµã×´Ì¬£¬ÒÔ¼°É¾³ýÈ«¾Ösession
%%NodeName string()
stop_node(NodeName) ->  
    ejabberd_odbc:sql_query(
      "",
      ["update cluster_node set isstart='0' "
       "where  nodename='", NodeName, "'"]),
    erlmc:delete(NodeName),
    ejabberd_odbc:sql_query(
      "",
      ["call p_del_global_session('", NodeName, "')"]),
    Ip = binary_to_list(lists:last(re:split(NodeName,"@"))),
    erlmc:remove_server(Ip,11211)
    .

update_tables() ->
    case catch mnesia:table_info(cluster_node, attributes) of
      [node, nodetype, isstart, ip, ismonitor] ->
        ok;
      [_Attr1|_] ->
        mnesia:delete_table(cluster_node);
      {'EXIT', _} ->
        ok
    end.

