-module(im_sys_monitor).
-behaviour(supervisor).
 
-export([start_link/0]).
-export([init/1]).
 
start_link() ->
  supervisor:start_link({local,?MODULE},?MODULE, []).

init([])->
%%¼à¿ØµÇÂ¼·þÎñ
    io:format("============="),
    {ok, {
      {one_for_one,10,60},
      [
       {im_service, {im_service, start_link, []},
                     permanent, brutal_kill, worker, [im_service]
       },
       {server_task, {server_task, start_link, []},
                     permanent, brutal_kill, worker, [server_task]
       }
      ]
      }}.   