-module(processes_c).
-compile(export_all).
 
 
max(N)->
    Max=erlang:system_info(process_limit),
    io:format("the max processes is ~p ~n",[Max]),
    statistics(runtime),
    statistics(wall_clock),
    L=for(1,N,fun()->spawn(fun()->wait() end) end),
    {_,Time1}=statistics(runtime),
    {_,Time2}=statistics(wall_clock),
    lists:foreach(fun(Pid)->Pid!die end,L),
    U1=Time1*1000/N,
    U2=Time2*1000/N,
    io:format("the proecess time is ~p:~p ~n",[U1,U2]).


mysqlW(N)->
    Xml="<message from='admin@justsy.com' to='100195-100000@justsy.com' id='admin-1445852558317951'><business xmlns='http://im.fafacn.com/namespace/business'><caption>newstaff</caption><type>system-message</type><sendername>绠＄悊鍛</sendername><sendtime>2015-10-26 17:42:38</sendtime><link></link><buttons/><body>{&quot;dept_id&quot;:&quot;100015&quot;,&quot;eno&quot;:&quot;100000&quot;,&quot;login_account&quot;:&quot;10000000150@justsy.com&quot;,&quot;nick_name&quot;:&quot;claire_150&quot;,&quot;password&quot;:&quot;HjsUoJglVRenrLqH5OLlO2umZI9pTLdxCXVYf1vXENm6\/wIumAtY8lYJ4vS4ZNCOiT9J5y7Jn9TAZ7G3AAojlA==&quot;,&quot;photo_path&quot;:&quot;&quot;,&quot;self_desc&quot;:null,&quot;duty&quot;:&quot;test&quot;,&quot;birthday&quot;:null,&quot;specialty&quot;:null,&quot;hobby&quot;:null,&quot;work_phone&quot;:null,&quot;mobile&quot;:&quot;10000000150&quot;,&quot;fafa_jid&quot;:&quot;255576-100000@justsy.com&quot;,&quot;state_id&quot;:&quot;1&quot;,&quot;prev_login_date&quot;:null,&quot;this_login_date&quot;:null,&quot;prev_login_ip&quot;:null,&quot;this_login_ip&quot;:null,&quot;login_num&quot;:null,&quot;login_source&quot;:null,&quot;total_point&quot;:&quot;0.40&quot;,&quot;register_date&quot;:null,&quot;active_date&quot;:&quot;2015-10-26 17:42:28&quot;,&quot;photo_path_small&quot;:&quot;&quot;,&quot;photo_path_big&quot;:&quot;&quot;,&quot;openid&quot;:&quot;046a85fb6907c78a8daf954d42a3b67f&quot;,&quot;t_code&quot;:&quot;35DE68DAB49717C0&quot;,&quot;we_level&quot;:null,&quot;hometown&quot;:null,&quot;report_object&quot;:null,&quot;direct_manages&quot;:null,&quot;graduated&quot;:null,&quot;work_his&quot;:null,&quot;attenstaff_num&quot;:&quot;2.00&quot;,&quot;fans_num&quot;:null,&quot;publish_num&quot;:null,&quot;mobile_bind&quot;:&quot;10000000150&quot;,&quot;sex_id&quot;:null,&quot;auth_level&quot;:&quot;S&quot;,&quot;ldap_uid&quot;:&quot;100150&quot;,&quot;jid&quot;:&quot;255576-100000@justsy.com&quot;,&quot;dept_name&quot;:&quot;\u5f00\u53d1\u90e8&quot;,&quot;edomain&quot;:&quot;justsy.com&quot;,&quot;ename&quot;:&quot;Justsy\u79d1\u6280\u6709\u9650\u516c\u53f8&quot;,&quot;eshortname&quot;:&quot;Jusyst\u79d1\u6280&quot;,&quot;fafa_deptid&quot;:&quot;100011&quot;}</body></business><delay xmlns='urn:xmpp:delay' from='justsy.com' stamp='2015-10-26T09:42:38Z'>Offline Storage</delay><x xmlns='jabber:x:delay' stamp='20151026T09:42:38'/></message>",
	P=fun()->
		Queries = ["insert into spool(username, xml) values ('username@LServer', '"++ejabberd_odbc:escape(Xml)++"');"],
		ejabberd_odbc:sql_transaction([], Queries)
	end,
   for(1,N,fun()->spawn(P) end)
.

mysqlW_S(N)->
Xml="<message from='admin@justsy.com' to='100195-100000@justsy.com' id='admin-1445852558317951'><business xmlns='http://im.fafacn.com/namespace/business'><caption>newstaff</caption><type>system-message</type><sendername>绠＄悊鍛</sendername><sendtime>2015-10-26 17:42:38</sendtime><link></link><buttons/><body>{&quot;dept_id&quot;:&quot;100015&quot;,&quot;eno&quot;:&quot;100000&quot;,&quot;login_account&quot;:&quot;10000000150@justsy.com&quot;,&quot;nick_name&quot;:&quot;claire_150&quot;,&quot;password&quot;:&quot;HjsUoJglVRenrLqH5OLlO2umZI9pTLdxCXVYf1vXENm6\/wIumAtY8lYJ4vS4ZNCOiT9J5y7Jn9TAZ7G3AAojlA==&quot;,&quot;photo_path&quot;:&quot;&quot;,&quot;self_desc&quot;:null,&quot;duty&quot;:&quot;test&quot;,&quot;birthday&quot;:null,&quot;specialty&quot;:null,&quot;hobby&quot;:null,&quot;work_phone&quot;:null,&quot;mobile&quot;:&quot;10000000150&quot;,&quot;fafa_jid&quot;:&quot;255576-100000@justsy.com&quot;,&quot;state_id&quot;:&quot;1&quot;,&quot;prev_login_date&quot;:null,&quot;this_login_date&quot;:null,&quot;prev_login_ip&quot;:null,&quot;this_login_ip&quot;:null,&quot;login_num&quot;:null,&quot;login_source&quot;:null,&quot;total_point&quot;:&quot;0.40&quot;,&quot;register_date&quot;:null,&quot;active_date&quot;:&quot;2015-10-26 17:42:28&quot;,&quot;photo_path_small&quot;:&quot;&quot;,&quot;photo_path_big&quot;:&quot;&quot;,&quot;openid&quot;:&quot;046a85fb6907c78a8daf954d42a3b67f&quot;,&quot;t_code&quot;:&quot;35DE68DAB49717C0&quot;,&quot;we_level&quot;:null,&quot;hometown&quot;:null,&quot;report_object&quot;:null,&quot;direct_manages&quot;:null,&quot;graduated&quot;:null,&quot;work_his&quot;:null,&quot;attenstaff_num&quot;:&quot;2.00&quot;,&quot;fans_num&quot;:null,&quot;publish_num&quot;:null,&quot;mobile_bind&quot;:&quot;10000000150&quot;,&quot;sex_id&quot;:null,&quot;auth_level&quot;:&quot;S&quot;,&quot;ldap_uid&quot;:&quot;100150&quot;,&quot;jid&quot;:&quot;255576-100000@justsy.com&quot;,&quot;dept_name&quot;:&quot;\u5f00\u53d1\u90e8&quot;,&quot;edomain&quot;:&quot;justsy.com&quot;,&quot;ename&quot;:&quot;Justsy\u79d1\u6280\u6709\u9650\u516c\u53f8&quot;,&quot;eshortname&quot;:&quot;Jusyst\u79d1\u6280&quot;,&quot;fafa_deptid&quot;:&quot;100011&quot;}</body></business><delay xmlns='urn:xmpp:delay' from='justsy.com' stamp='2015-10-26T09:42:38Z'>Offline Storage</delay><x xmlns='jabber:x:delay' stamp='20151026T09:42:38'/></message>",

	P=fun()->
		Queries = ["insert into spool(username, xml) values ('username@LServer', '"++ejabberd_odbc:escape(Xml)++"');"],
		ejabberd_odbc:sql_transaction([], Queries)
	end,
	io:format("~p begin:~p~n",[self(),func_utils:date()]),
   for(1,N,P),
   io:format("~p end:~p~n",[self(),func_utils:date()])
.

mysqlW_SM(N)->
Xml="<message from='admin@justsy.com' to='100195-100000@justsy.com' id='admin-1445852558317951'><business xmlns='http://im.fafacn.com/namespace/business'><caption>newstaff</caption><type>system-message</type><sendername>绠＄悊鍛</sendername><sendtime>2015-10-26 17:42:38</sendtime><link></link><buttons/><body>{&quot;dept_id&quot;:&quot;100015&quot;,&quot;eno&quot;:&quot;100000&quot;,&quot;login_account&quot;:&quot;10000000150@justsy.com&quot;,&quot;nick_name&quot;:&quot;claire_150&quot;,&quot;password&quot;:&quot;HjsUoJglVRenrLqH5OLlO2umZI9pTLdxCXVYf1vXENm6\/wIumAtY8lYJ4vS4ZNCOiT9J5y7Jn9TAZ7G3AAojlA==&quot;,&quot;photo_path&quot;:&quot;&quot;,&quot;self_desc&quot;:null,&quot;duty&quot;:&quot;test&quot;,&quot;birthday&quot;:null,&quot;specialty&quot;:null,&quot;hobby&quot;:null,&quot;work_phone&quot;:null,&quot;mobile&quot;:&quot;10000000150&quot;,&quot;fafa_jid&quot;:&quot;255576-100000@justsy.com&quot;,&quot;state_id&quot;:&quot;1&quot;,&quot;prev_login_date&quot;:null,&quot;this_login_date&quot;:null,&quot;prev_login_ip&quot;:null,&quot;this_login_ip&quot;:null,&quot;login_num&quot;:null,&quot;login_source&quot;:null,&quot;total_point&quot;:&quot;0.40&quot;,&quot;register_date&quot;:null,&quot;active_date&quot;:&quot;2015-10-26 17:42:28&quot;,&quot;photo_path_small&quot;:&quot;&quot;,&quot;photo_path_big&quot;:&quot;&quot;,&quot;openid&quot;:&quot;046a85fb6907c78a8daf954d42a3b67f&quot;,&quot;t_code&quot;:&quot;35DE68DAB49717C0&quot;,&quot;we_level&quot;:null,&quot;hometown&quot;:null,&quot;report_object&quot;:null,&quot;direct_manages&quot;:null,&quot;graduated&quot;:null,&quot;work_his&quot;:null,&quot;attenstaff_num&quot;:&quot;2.00&quot;,&quot;fans_num&quot;:null,&quot;publish_num&quot;:null,&quot;mobile_bind&quot;:&quot;10000000150&quot;,&quot;sex_id&quot;:null,&quot;auth_level&quot;:&quot;S&quot;,&quot;ldap_uid&quot;:&quot;100150&quot;,&quot;jid&quot;:&quot;255576-100000@justsy.com&quot;,&quot;dept_name&quot;:&quot;\u5f00\u53d1\u90e8&quot;,&quot;edomain&quot;:&quot;justsy.com&quot;,&quot;ename&quot;:&quot;Justsy\u79d1\u6280\u6709\u9650\u516c\u53f8&quot;,&quot;eshortname&quot;:&quot;Jusyst\u79d1\u6280&quot;,&quot;fafa_deptid&quot;:&quot;100011&quot;}</body></business><delay xmlns='urn:xmpp:delay' from='justsy.com' stamp='2015-10-26T09:42:38Z'>Offline Storage</delay><x xmlns='jabber:x:delay' stamp='20151026T09:42:38'/></message>",

	P=fun()->
		["insert into spool(username, xml) values ('username@LServer', '"++ejabberd_odbc:escape(Xml)++"');"]
	end,
   Sqls=for(1,N,P),
   io:format("~p begin:~p~n",[self(),func_utils:date()]),
   ejabberd_odbc:sql_transaction([], Sqls),
   io:format("~p end:~p~n",[self(),func_utils:date()])
.

mysqlW_ST(N)->
Xml="<message from='admin@justsy.com' to='100195-100000@justsy.com' id='admin-1445852558317951'><business xmlns='http://im.fafacn.com/namespace/business'><caption>newstaff</caption><type>system-message</type><sendername>绠＄悊鍛</sendername><sendtime>2015-10-26 17:42:38</sendtime><link></link><buttons/><body>{&quot;dept_id&quot;:&quot;100015&quot;,&quot;eno&quot;:&quot;100000&quot;,&quot;login_account&quot;:&quot;10000000150@justsy.com&quot;,&quot;nick_name&quot;:&quot;claire_150&quot;,&quot;password&quot;:&quot;HjsUoJglVRenrLqH5OLlO2umZI9pTLdxCXVYf1vXENm6\/wIumAtY8lYJ4vS4ZNCOiT9J5y7Jn9TAZ7G3AAojlA==&quot;,&quot;photo_path&quot;:&quot;&quot;,&quot;self_desc&quot;:null,&quot;duty&quot;:&quot;test&quot;,&quot;birthday&quot;:null,&quot;specialty&quot;:null,&quot;hobby&quot;:null,&quot;work_phone&quot;:null,&quot;mobile&quot;:&quot;10000000150&quot;,&quot;fafa_jid&quot;:&quot;255576-100000@justsy.com&quot;,&quot;state_id&quot;:&quot;1&quot;,&quot;prev_login_date&quot;:null,&quot;this_login_date&quot;:null,&quot;prev_login_ip&quot;:null,&quot;this_login_ip&quot;:null,&quot;login_num&quot;:null,&quot;login_source&quot;:null,&quot;total_point&quot;:&quot;0.40&quot;,&quot;register_date&quot;:null,&quot;active_date&quot;:&quot;2015-10-26 17:42:28&quot;,&quot;photo_path_small&quot;:&quot;&quot;,&quot;photo_path_big&quot;:&quot;&quot;,&quot;openid&quot;:&quot;046a85fb6907c78a8daf954d42a3b67f&quot;,&quot;t_code&quot;:&quot;35DE68DAB49717C0&quot;,&quot;we_level&quot;:null,&quot;hometown&quot;:null,&quot;report_object&quot;:null,&quot;direct_manages&quot;:null,&quot;graduated&quot;:null,&quot;work_his&quot;:null,&quot;attenstaff_num&quot;:&quot;2.00&quot;,&quot;fans_num&quot;:null,&quot;publish_num&quot;:null,&quot;mobile_bind&quot;:&quot;10000000150&quot;,&quot;sex_id&quot;:null,&quot;auth_level&quot;:&quot;S&quot;,&quot;ldap_uid&quot;:&quot;100150&quot;,&quot;jid&quot;:&quot;255576-100000@justsy.com&quot;,&quot;dept_name&quot;:&quot;\u5f00\u53d1\u90e8&quot;,&quot;edomain&quot;:&quot;justsy.com&quot;,&quot;ename&quot;:&quot;Justsy\u79d1\u6280\u6709\u9650\u516c\u53f8&quot;,&quot;eshortname&quot;:&quot;Jusyst\u79d1\u6280&quot;,&quot;fafa_deptid&quot;:&quot;100011&quot;}</body></business><delay xmlns='urn:xmpp:delay' from='justsy.com' stamp='2015-10-26T09:42:38Z'>Offline Storage</delay><x xmlns='jabber:x:delay' stamp='20151026T09:42:38'/></message>",
	
	P=fun()->
		["insert into spool(username, xml) values ('username@LServer', '"++ejabberd_odbc:escape(Xml)++"');"]
	end,
   Sqls=for(1,N,P),
   io:format("~p begin:~p~n",[self(),func_utils:date()]),
   ejabberd_odbc:sql_transaction([], ["SET AUTOCOMMIT = 0;"]++Sqls++["COMMIT;set autocommit = 1;"]),
   io:format("~p end:~p~n",[self(),func_utils:date()])
.
 
wait()->
    receive
        die->void
    end.
 
for(N,N,F)->[F()];
for(I,N,F)->[F()|for(I+1,N,F)]. 

runtime()->
    Bit=erlang:system_info(wordsize),
	SchedId      = erlang:system_info(scheduler_id),
	SchedNum     = erlang:system_info(schedulers),
	ProcCount    = erlang:system_info(process_count),
	ProcLimit    = erlang:system_info(process_limit),
	ProcMemUsed  = erlang:memory(processes_used) div Bit,
	ProcMemAlloc = erlang:memory(processes) div Bit,
	MemTot       = erlang:memory(total) div Bit,
	Reason = "",
	io:format("abormal termination: "
	          "~n   Scheduler id:                         ~p"
	          "~n   Num scheduler:                        ~p"
	          "~n   Process count:                        ~p"
	          "~n   Process limit:                        ~p"
	          "~n   Memory used by erlang processes:      ~p"
	          "~n   Memory allocated by erlang processes: ~p"
	          "~n   The total amount of memory allocated: ~p"
	          "~n~p",
	          [SchedId, SchedNum, ProcCount, ProcLimit,
	           ProcMemUsed, ProcMemAlloc, MemTot, Reason]),
	{{schedId,SchedId}, {schedNum,SchedNum}, {procCount,ProcCount}, {proclimit,ProcLimit},
	 {memused,ProcMemUsed}, {memalloc,ProcMemAlloc}, {memtotal,MemTot}}
.

process_info_memory(Cnt,MaxMem) ->
	Summary = runtime(),

    filelib:ensure_dir("../logs/"),
    File = "../logs/processes_infos.log",
    Now=binary_to_list(func_utils:date()),
    Bit=erlang:system_info(wordsize),
    
    Fun = fun(P) ->

                  %%Info = io_lib:format("=>~p \n\n",[Pi]),
                  Info=erlang:process_info(P),
                  {_,Value}=lists:keyfind(initial_call,1,Info),
                  {_,Value2}=lists:keyfind(current_function,1,Info),
                  {_,Bytes}=process_info(P,memory),
                  {_,Mq}=lists:keyfind(messages,1,Info),
                  Bk=Bytes div Bit,
                  case Bk>MaxMem of true->
                  Pi={Value2,{memory,Bk},{messages,Mq},{caller,Value}},
                  %%
                  %%io:format("=>~p \n\n",[Pi]),                
                  %%Text = io_lib:format("~p. \n\n",[Pi]),
                  %%case  filelib:is_file(File) of
                  %%      true   ->   file:write(Fd, Text);
                  %%      false  ->
                  %%          file:close(Fd),
                  %%          {ok, NewFd} = file:open(File, [write, raw, binary, append]),
                  %%          file:write(NewFd, Text)
                  %% end,
                  %% timer:sleep(1)
                  %%
                  Pi;
                  _->
                  	[]
              	  end
    end,
    Stats=[Now,Summary,lists:filter(fun(A)->A=/=[] end,[Fun(P)||P<-erlang:processes()])],

    Text = io_lib:format("~p. \n\n",[Stats]),
    {ok, Fd} = file:open(File, [write, raw, binary, append]),
	case  filelib:is_file(File) of
                        true   ->   file:write(Fd, Text);
                        false  ->
                            file:close(Fd),
                            {ok, NewFd} = file:open(File, [write, raw, binary, append]),
                            file:write(NewFd, Text)
    end,
    case Cnt of 
    []-> ok;
    0-> ok;
    _->
    	timer:sleep(1000),
    	process_info_memory(Cnt-1,MaxMem)
	end
    . 

process_infos() ->          
    filelib:ensure_dir("./log/"),
    File = "./log/processes_infos.log",
    {ok, Fd} = file:open(File, [write, raw, binary, append]), 
    Fun = fun(Pi) ->
                   Info = io_lib:format("=>~p \n\n",[Pi]),
                  case  filelib:is_file(File) of
                        true   ->   file:write(Fd, Info);
                        false  ->
                            file:close(Fd),
                            {ok, NewFd} = file:open(File, [write, raw, binary, append]),
                            file:write(NewFd, Info)
                     end,
                     timer:sleep(20)
                 end,
    [   Fun(erlang:process_info(P)) ||   P <- erlang:processes()]. 


tcp_conn(Host,Max)->
  tcp_conn(Host,Max,1)
.

tcp_conn(Host,Max,Cur)->
  case Cur of Max-> ok;
    _->
      {Ok,Socket} = gen_tcp:connect(Host,5222,[binary,{packet,4}]),
      tcp_conn(Host,Max,Cur+1)
  end
.