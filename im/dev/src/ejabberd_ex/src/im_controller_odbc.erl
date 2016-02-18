%%====================================================================
%% im控制器模块。
%% 负责接入到ejabberd服务器
%%====================================================================
-module(im_controller_odbc).
-author('liling@lli2').
-include("../../ejabberd-2.1.11/include/ejabberd.hrl").
-behaviour(gen_mod).

-export([start/2,stop/1]).
	 
%%====================================================================
%%  实现gen_mod行为包的sart方法
%%  1、负责启动im的所有service。这些service由C/S通信实现的S(服务器)端调用
%%  2、在ejabberd启动时，同时启动yaws服务器
%%====================================================================	 
start(Host, Opts) ->  
    iconv:start(),
    MainDoamin=func_utils:getlocalserver(),
    if MainDoamin=:=Host->
		    io:format(binary_to_list(func_utils:date())++":debug info:~w,~w~n",[Host,Opts]), 
				try
				    {A1,A2}=case  Opts of
				     [Ele1]-> {Ele1,[]};
				     [Ele2,{domain,Domain}]->{Ele2,Domain}
				    end,
				    im_syscode_odbc:put(A2,"","domain"),
				    case updateConfig([A1]) of true->
								io:format(binary_to_list(func_utils:date())++" create process monitor tree...~n"),
								try
								       im_sys_monitor:start_link() 
								catch
								       _:Err ->io:format(binary_to_list(func_utils:date())++":start sys model exception:~w~n",[Err])
								end,
						    io:format(binary_to_list(func_utils:date())++":starting yaws...~n"),
						    try 
						       yaws:start(),
						       io:format(binary_to_list(func_utils:date())++":yaws is started...~n")
						    catch
						       Ec:Ex-> ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
						              io:format(binary_to_list(func_utils:date())++":when yaws start exception:~w~n",[Ex])
						    end;
						{error,Why}->
						   throw({error,Why});
				    _->
				       io:format(binary_to_list(func_utils:date())++" error:ejabberd/yaws server can't start~n")
				    end		    
		    catch
		       Ecc:Exc-> ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ecc, Exc, erlang:get_stacktrace()]),
						         io:format(binary_to_list(func_utils:date())++":when yaws start exception:~w~n",[Exc])				               
		   end;
	 true->
	    ok
	 end
.

stop(_Host) ->    
    io:format("=====im登录服务停止完成!~n"),
    io:format("yaws停止完成!~n").	 
    
updateConfig(Config) when Config/=[] ->
   {_,TopPath} = func_utils:getCurrentPath(),
   Path = TopPath++"/lib/Yaws-1.89/yaws.conf",
   TmpPath = TopPath++"/lib/Yaws-1.89/yaws_tmp.conf",
   AppConfigPath = TopPath++"/lib/Yaws-1.89/ebin/yaws.app",
   %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
   {Flag,Result}=file:consult(AppConfigPath),
   if Flag==error-> throw({error,"open "++AppConfigPath++" then exception:"++Result});   
   true->
       Result1 =element(3,hd(Result)),
       F=fun(E)->case is_tuple(E) of 
                 true->
          			                 if element(1,E)==env->
          			                    {env,[{conf,Path}]};
          			                 true->
          			                    E
          			                 end;
          			 _-> E 
          			 end
         end,
       AppFile=case lists:keymember(env,1,Result1) of true->          
          			    [F(E)||E<-Result1];
				       _->
				            [{env,[{conf,Path}]}|Result1]
				       end,
			file:write_file(AppConfigPath,io_lib:fwrite("~p.\n",[{application,yaws,AppFile}]))
   end,
   %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
   {Ha,V} = file:open(Path,read),
   if Ha==error ->
      throw({error,Path++" not found!"});
   true->
		   {_,Handle} = file:open(TmpPath,write),
		   R=updateFile(V,Handle),
		   case R of true->           
		           file:close(V),
						   %%write new config
						   case catch writeNewConfig(Handle,Config) of 
						     {error,Why}-> throw({error,Why});
						     _->
						           file:close(Handle),
						           {_,OldText} = file:read_file(Path),
						           {_,NewText} = file:read_file(TmpPath),
						           %%io:format("~w",[NewText]),
						           file:write_file(Path,NewText),
						           file:write_file(TmpPath,OldText),
						           true
		           end;
		   _->
		      io:format(binary_to_list(func_utils:date())++" :error for update yaws's config~n"),
		      false
		   end
   end
;
updateConfig(Config) when Config==[] ->  
   true
.    

updateFile(V,V1)->
   Line = io:get_line(V,""),
   updateFile(V,V1,Line)
.
updateFile(V,V1,L) when L/=eof->   
   WriteFlag0 =  case regexp:first_match(L,"<server localhost>") of nomatch->
     write;
   _-> nowrite
   end,
   WriteFlag1 =  case regexp:first_match(L," port.*?=") of nomatch->
     write;
   _-> nowrite
   end,
   WriteFlag2 =  case regexp:first_match(L," listen.*?=") of nomatch->
     write;
   _-> nowrite
   end,
   WriteFlag3 =  case regexp:first_match(L," docroot.*?=") of nomatch->
     write;
   _-> nowrite
   end,
   WriteFlag4 =  case regexp:first_match(L," appmods.*?=") of nomatch->
     write;
   _-> nowrite
   end,
   WriteFlag5 =  case regexp:first_match(L," partial_post_size.*?=") of nomatch->
     write;
   _-> nowrite
   end,   
   WriteFlag6 =  case regexp:first_match(L,"</server>") of nomatch->
     write;
   _-> nowrite
   end,
   if WriteFlag0==nowrite;WriteFlag1==nowrite;WriteFlag2==nowrite;WriteFlag3==nowrite;WriteFlag4==nowrite;WriteFlag5==nowrite;WriteFlag6==nowrite ->
       skip;
   true->
       io:format(V1,"~s",[L])
   end,
   Line = io:get_line(V,""),
   updateFile(V,V1,Line)
;
updateFile(_,_,L) when L==eof-> 
   true
.

writeNewConfig(Handle,[H|T])when H/=[],is_tuple(H)==true->
   WebApp = element(1,H),
   if WebApp/=webapp->
      writeNewConfig(Handle,T);
   true->
      [_|ConfLst] = tuple_to_list(H),
      writeNewWebConfig(Handle,ConfLst)      
   end
;
writeNewConfig(_,[H|_T])when H/=[],is_tuple(H)==false->
   {error,"config parameter error.exp:[{Config},{Config}]"}
;
writeNewConfig(_,[H|_T])when H==[]->
   true
.

%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
%%WebConf format:{name/dirName,attrFlag,value}
writeNewWebConfig(Handle,[WebConf|T]) when WebConf/=[]->  
  {_,TopPath} = func_utils:getCurrentPath(),
  Name = element(1,WebConf),
  Name1 =case is_atom(Name) of true-> atom_to_list(Name);
         _->
             element(1,WebConf)
         end,
  %%check dir is exist
  case file:list_dir(TopPath++"/lib/Yaws-1.89/"++Name1)   of
  {error,_Why}-> throw({error,TopPath++"/lib/Yaws-1.89/"++Name1++" not exist"});
  _->    
		  code:add_path(TopPath++"/lib/Yaws-1.89/"++Name1++"/ebin"),
		  io:format(Handle,"~s~n",["\<server localhost\>"]),
		  io:format(Handle,"~s~n",["    port = "++integer_to_list(element(3,WebConf))]),%%write webApp port
		  io:format(Handle,"~s~n",["    listen = 0.0.0.0"]),
      io:format(Handle,"~s~n",["    partial_post_size = \"nolimit\""]),
		  io:format(Handle,"~s~n",["    docroot = \""++TopPath++"/lib/Yaws-1.89/"++Name1++"\""]),%%write webApp name
		  io:format(Handle,"~s~n",["    appmods = <cgi-bin, yaws_appmod_cgi>"]),
		  io:format(Handle,"~s~n",["\</server\>"]),
		  writeNewWebConfig(Handle,T)
  end
;
writeNewWebConfig(_Handle,WebConf) when WebConf==[]->
  true  
.

