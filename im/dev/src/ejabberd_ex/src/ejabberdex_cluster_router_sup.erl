-module(ejabberdex_cluster_router_sup).
-author('feihu929@sina.com').

%% API
-export([start_link/0,
   init/1
  ]).

-include("../../ejabberd-2.1.11/include/ejabberd.hrl").

start_link() ->
    supervisor:start_link(?MODULE, []).

init([]) ->
    {ok, {{one_for_one, 10, 1},
    [{1,
      {ejabberdex_cluster_router, start_link, []},
      permanent,
      2000,
      worker,
      [?MODULE]
     }]}}.
