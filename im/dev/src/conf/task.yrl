%%% -------------------------------------------------------------------
%%% Author  : zhys9
%%% Description : erlang版crontab
%%%
%%% Created : 2010-12-8
%%% -------------------------------------------------------------------
%%% erlang版crontab配置文件
%%% 长期的定时任务配置
%%% 也可以不在这里配置，而直接通过接口调用，向server_crond增加新任务，格式与此相同
%%% 取值：
%%% 		分：[0-59]
%%%		时：[0-23]
%%%		日: [1-31]
%%%		月：[1-12]
%%%		周：[1-7]
%%%
%%%  {[分]	[时]	[日]	[月]	[周]	{module, function, [arguments]}}
%%%  {[],	[],		[],		[],		[],		{io, format, ["hello, crond. task:~p~n", [1]]}}.	%% 每分钟输出 hello,crond. task:1
%%%  {[10],	[5],	[],		[],		[],		{io, format, ["hello, crond. task:2~n"]}}.			%% 每天临晨5点10分输出 hello,crond. task:2
%%%  {[0,10,20,30,40,50], [], [], [], [],	{io, format, ["hello, crond. task:3~n"]}}.			%% 每格10分钟输出 hello,crond. task:3
%%%	 {[10],	[5],	[],		[12],	[2],	{io, format, ["hello, crond. task:4~n"]}}.			%% 12月份，每个周二的临晨5点10分输出 hello,crond. task:4

%%%{[],[],[],[],[],{io, format, ["hello, crond. task:~p~n", [1]]}}.		%% 测试