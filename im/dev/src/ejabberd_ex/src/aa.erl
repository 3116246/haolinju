-module(aa).

-export([c/0, c/2, select/1,
         exp/0, export_passwd/2, export_employeerole/2, export_enterprise_reg/2, export_friendgroups/2, export_group/2, export_groupemployee/2, export_grouptype/2, export_syscode/2, 
         tt/0, ttt/0]).

%-include("../include/mod_ejabberdex_init.hrl").

%writedb() ->
%  mnesia:dirty_write(#base_dept{deptid="001", deptname="TEST111", pid="000", path="000", noorder="1"}),
%  %% mnesia:dirty_write(#employee{employeeid="admin", deptid="001", loginname="admin", password="admin", employeename="admin管理"}).
%  mnesia:dirty_write(#base_dept{deptid="001001", deptname="TEST111-111", pid="001", path="000/001", noorder="1"}),
%  mnesia:dirty_write(#base_dept{deptid="001002", deptname="TEST111-222", pid="001", path="000/001", noorder="2"}).

c() ->
  c("aa", "ejabberd_ex"),
  
  c("bson", "bson"),
  c("bson_binary", "bson"),
  
%  c("ejabberd_c2s", "ejabberd-2.1.10"),
%  c("ejabberd_config", "ejabberd-2.1.10"),
%  c("ejabberd_http", "ejabberd-2.1.10"),
%  c("ejabberd_http_bind", "ejabberd-2.1.10"),
%  c("ejabberd_odbc_sup", "ejabberd-2.1.10"),
%  c("ejabberd_sm", "ejabberd-2.1.10"),
%  c("ejd2odbc", "ejabberd-2.1.10"),
%  c("mod_http_bind", "ejabberd-2.1.10"),
%  c("mod_offline_odbc", "ejabberd-2.1.10"),
%  c("mod_privacy_odbc", "ejabberd-2.1.10"),
%  c("mod_pubsub_odbc", "ejabberd-2.1.10"),
%  c("mod_roster_odbc", "ejabberd-2.1.10"),
%  c("odbc_queries", "ejabberd-2.1.10"),

  c("ejabberd_c2s", "ejabberd-2.1.11"),
  c("ejabberd_config", "ejabberd-2.1.11"),
  c("ejabberd_ctl", "ejabberd-2.1.11"),
  c("ejabberd_http", "ejabberd-2.1.11"),
  c("ejabberd_http_bind", "ejabberd-2.1.11"),
  c("ejabberd_odbc_sup", "ejabberd-2.1.11"),
  c("ejabberd_sm", "ejabberd-2.1.11"),
  c("ejabberd_update", "ejabberd-2.1.11"),
  c("ejd2odbc", "ejabberd-2.1.11"),
  c("jlib", "ejabberd-2.1.11"),
  c("mod_offline_odbc", "ejabberd-2.1.11"),
  c("mod_privacy_odbc", "ejabberd-2.1.11"),
  c("mod_proxy65_service", "ejabberd-2.1.11"),
  c("mod_roster_odbc", "ejabberd-2.1.11"),
  c("odbc_queries", "ejabberd-2.1.11"),
    
  c("ejabberdex_c2c_odbc", "ejabberd_ex"),
  c("ejabberdex_cluster_router", "ejabberd_ex"),
  c("ejabberdex_cluster_router_sup", "ejabberd_ex"),
  c("ejabberdex_odbc_query", "ejabberd_ex"),
  c("func_utils", "ejabberd_ex"),
  c("im_controller_odbc", "ejabberd_ex"),
  c("im_employee_odbc", "ejabberd_ex"),
  c("im_group_odbc", "ejabberd_ex"),
  c("im_organ_odbc", "ejabberd_ex"),
  c("im_service", "ejabberd_ex"),
  c("im_sys_monitor", "ejabberd_ex"),
  c("im_syscode_odbc", "ejabberd_ex"),
  c("img", "ejabberd_ex"),
  c("message_action_odbc", "ejabberd_ex"),
  c("mod_dept_odbc", "ejabberd_ex"),
  c("mod_ejabberdex_init", "ejabberd_ex"),
  c("mod_employee_odbc", "ejabberd_ex"),
  c("mod_group_odbc", "ejabberd_ex"),
  c("mod_offlinefile_odbc", "ejabberd_ex"),
  c("mod_presence_ex_odbc", "ejabberd_ex"),
  c("request", "ejabberd_ex"),
  c("sendmail", "ejabberd_ex"),
  c("server_task", "ejabberd_ex"),
  c("service_api", "ejabberd_ex"),
  
  c("gridfs", "mongodb"),
  c("gridfs_cursor", "mongodb"),
  c("gridfs_file", "mongodb"),
  c("mongo", "mongodb"),
  c("mongo_app", "mongodb"),
  c("mongo_connection", "mongodb"),
  c("mongo_cursor", "mongodb"),
  c("mongo_id_server", "mongodb"),
  c("mongo_pool", "mongodb"),
  c("mongo_protocol", "mongodb"),
  c("mongo_sup", "mongodb"),
  
  c("mysql_auth", "mysql-2011.0825"),

  c("apns", "apns4erl"),
  c("apns_app", "apns4erl"),
  c("apns_connection", "apns4erl"),
  c("apns_mochijson2", "apns4erl"),
  c("apns_mochinum", "apns4erl"),
  c("apns_sup", "apns4erl"),

  ok.

%% ejabberd_ex, ejabberd-2.1.10
c(ErlFile, ParentDir) ->
  try
    ErlFileName = (case is_list(ErlFile) of true -> ErlFile; _ -> atom_to_list(ErlFile) end),
    c:c("/opt/fafa/lib/"++ ParentDir ++"/src/"++ ErlFileName ++".erl"),
    os:cmd("mv "++ ErlFileName ++".beam /opt/fafa/lib/"++ ParentDir ++"/ebin/")
  catch 
    Ec:Ex ->  
    	        io:format("~p:~p", [Ec, Ex])
  end.
  
select(TableName) ->
  catch mnesia:dirty_select(TableName, [{mnesia:table_info(TableName, wild_pattern), [], ['$_']}]).  

exp() ->
  export_passwd("", "/tmp/passwd.sql"),
  export_roster("", "/tmp/roster.sql"),
  export_base_dept("", "/tmp/base_dept.sql"),
  export_dept_stat("", "/tmp/dept_stat.sql"),
  export_employee("", "/tmp/employee.sql"),
  export_employeerole("", "/tmp/employeerole.sql"),
  export_enterprise_reg("", "/tmp/enterprise_reg.sql"),
  export_friendgroups("", "/tmp/friendgroups.sql"),
  export_group("", "/tmp/group.sql"),
  export_groupemployee("", "/tmp/groupemployee.sql"),
  export_grouptype("", "/tmp/grouptype.sql"),
  export_syscode("", "/tmp/syscode.sql"),
  ok.

%%--------------------------------------------------------------------
%%% How to use:
%%% A table can be converted from Mnesia to an ODBC database by calling
%%% one of the API function with the following parameters:
%%% - Server is the server domain you want to convert or "" to conver all
%%% - Output can be either odbc to export to the configured relational
%%%   database or "Filename" to export to text file.
		 
export_passwd(Server, Output) ->
    export_common(
      Server, passwd, Output,
      fun(Host, {passwd, {LUser, LServer}, Password} = _R)
	 when Server == "" orelse LServer == Host ->
	      Username = ejabberd_odbc:escape(LUser++"@"++LServer),
	      Pass = ejabberd_odbc:escape(Password),
	      ["delete from users where username='", Username ,"';"
	       "insert into users(username, password) "
	       "values ('", Username, "', '", Pass, "');"];
	 (_Host, _R) ->
	      []
      end).

-record(roster, {usj,
		 us,
		 jid,
		 name = "",
		 subscription = none,
		 ask = none,
		 groups = [],
		 askmessage = [],
		 xs = []}).
		 
export_roster(Server, Output) ->
    export_common(
      Server, roster, Output,
      fun(Host, #roster{usj = {LUser, LServer, LJID}} = R)
	 when Server == "" orelse LServer == Host ->
	      Username = ejabberd_odbc:escape(LUser++"@"++LServer),
	      SJID = ejabberd_odbc:escape(jlib:jid_to_string(LJID)),
	      ItemVals = record_to_string(R),
	      ItemGroups = groups_to_string(R),
	      ["delete from rosterusers "
	       "      where username='", Username, "' "
	       "        and jid='", SJID, "';"
	       "insert into rosterusers("
	       "              username, jid, nick, "
	       "              subscription, ask, askmessage, "
	       "              server, subscribe, type) "
	       " values ", ItemVals, ";"
	       "delete from rostergroups "
	       "      where username='", Username, "' "
	       "        and jid='", SJID, "';",
	       [["insert into rostergroups("
		 "              username, jid, grp) "
		 " values ", ItemGroup, ";"] ||
		   ItemGroup <- ItemGroups]];
	 (_Host, _R) ->
	      []
      end).

-record(base_dept,   
        {
         deptid,     
         deptname,   
         pid,        
         path,       
         noorder,
         manager="",
         remark=""}).     

export_base_dept(Server, Output) ->
    export_common(
      Server, base_dept, Output,
      fun(Host, R)
	 when Server == "" orelse "" == Host ->
	      XAdeptid = ejabberd_odbc:escape(R#base_dept.deptid),
	      XAdeptname = ejabberd_odbc:escape(R#base_dept.deptname),
	      XApid = ejabberd_odbc:escape(R#base_dept.pid),
	      XApath = ejabberd_odbc:escape(R#base_dept.path),
	      XAnoorder = ejabberd_odbc:escape(R#base_dept.noorder),
	      XAmanager = ejabberd_odbc:escape(R#base_dept.manager), 
	      XAremark = ejabberd_odbc:escape(R#base_dept.remark),
	      ["delete from im_base_dept where deptid='", XAdeptid ,"';"
	       "insert into im_base_dept(deptid, deptname, pid, path, noorder, manager, remark) "
	       "values ('", XAdeptid, "', '", XAdeptname, "', '", XApid, "', '", XApath, "', '", XAnoorder, "', '", XAmanager, "', '", XAremark, "');"];
	 (_Host, _R) ->
	      []
      end).
              
-record(dept_stat,{  
   deptid,
   empcount,          
   online=0,             
   childdept=0             
}).    
export_dept_stat(Server, Output) ->
    export_common(
      Server, dept_stat, Output,
      fun(Host, R)
	 when Server == "" orelse "" == Host ->
	      XAdeptid = ejabberd_odbc:escape(R#dept_stat.deptid),
	      XAempcount = io_lib:format("~p", [R#dept_stat.empcount]),
	      XAonline = io_lib:format("~p", [R#dept_stat.online]),
	      XAchilddept = io_lib:format("~p", [R#dept_stat.childdept]),
	      ["delete from im_dept_stat where deptid='", XAdeptid ,"';"
	       "insert into im_dept_stat(deptid, empcount, online, childdept) "
	       "values ('", XAdeptid, "', ", XAempcount, ", ", XAonline, ", ", XAchilddept, ");"];
	 (_Host, _R) ->
	      []
      end).
         
-record(employee,    
        {
          employeeid,    
          deptid,    
          loginname, 
          password,  
          employeename  
        }).  
export_employee(Server, Output) ->
    export_common(
      Server, employee, Output,
      fun(Host, R)
	 when Server == "" orelse "" == Host ->
	      XAemployeeid = ejabberd_odbc:escape(R#employee.employeeid),
	      XAdeptid = ejabberd_odbc:escape(R#employee.deptid),
	      XAloginname = ejabberd_odbc:escape(R#employee.loginname),
	      XApassword = ejabberd_odbc:escape(R#employee.password),
	      XAemployeename = ejabberd_odbc:escape(R#employee.employeename),
	      ["delete from im_employee where employeeid='", XAemployeeid ,"';"
	       "insert into im_employee(employeeid, deptid, loginname, password, employeename) "
	       "values ('", XAemployeeid, "', '", XAdeptid, "', '", XAloginname, "', '", XApassword, "', '", XAemployeename, "');"];
	 (_Host, _R) ->
	      []
      end).

-record(employeerole,
        {
          employeeid,  
          roleid      
        }). 
export_employeerole(Server, Output) ->
    export_common(
      Server, employeerole, Output,
      fun(Host, R)
	 when Server == "" orelse "" == Host ->
	      XAemployeeid = ejabberd_odbc:escape(R#employeerole.employeeid),
	      XAroleid = ejabberd_odbc:escape(R#employeerole.roleid),
	      ["delete from im_employeerole where employeeid='", XAemployeeid ,"' and roleid='", XAroleid ,"';"
	       "insert into im_employeerole(employeeid, roleid) "
	       "values ('", XAemployeeid, "', '", XAroleid, "');"];
	 (_Host, _R) ->
	      []
      end).
      
-record(enterprise_reg, {eno,mail,subdomain,admin,phone,pass,fullname,name,contact,sex,website,addr,gsno,gsno2,industry}).
export_enterprise_reg(Server, Output) ->
    export_common(
      Server, enterprise_reg, Output,
      fun(Host, R)
	 when Server == "" orelse "" == Host ->
	      XAeno = escape(R#enterprise_reg.eno),
	      XAmail = escape(R#enterprise_reg.mail),
	      XAsubdomain = escape(R#enterprise_reg.subdomain),
	      XAadmin = escape(R#enterprise_reg.admin),
%	      XAphone = escape(R#enterprise_reg.phone),
%	      XApass = escape(R#enterprise_reg.pass),
	      XAfullname = escape(R#enterprise_reg.fullname),
	      XAname = escape(R#enterprise_reg.name),
%	      XAcontact = escape(R#enterprise_reg.contact),
%	      XAsex = escape(R#enterprise_reg.sex),
%	      XAwebsite = escape(R#enterprise_reg.website),
%	      XAaddr = escape(R#enterprise_reg.addr),
%	      XAgsno = escape(R#enterprise_reg.gsno),
%	      XAgsno2 = escape(R#enterprise_reg.gsno2),
%	      XAindustry = escape(R#enterprise_reg.industry),
	      ["delete from im_enterprise_reg where eno='", XAeno ,"';"
	       "insert into im_enterprise_reg(eno,mail,subdomain,admin,fullname,name) "
	       "values ('", XAeno, "', '", XAmail, "', '", XAsubdomain, "', '", XAadmin, "', '", XAfullname, "', '", XAname, "');"];
	 (_Host, _R) ->
	      []
      end).
      
-record(friendgroups,{  
    empg,               
    loginname,
    groupname
}).      
export_friendgroups(Server, Output) ->
    export_common(
      Server, friendgroups, Output,
      fun(Host, R)
	 when Server == "" orelse "" == Host ->
	      XAempg = lists:flatten([io_lib:format("~2.16.0b", [X]) || X <- binary_to_list(crypto:md5(element(1, R#friendgroups.empg) ++ element(2, R#friendgroups.empg)), 1, 8)]) ++ integer_to_list(random:uniform(1000)),
	      XAloginname = escape(R#friendgroups.loginname),
	      XAgroupname = escape(R#friendgroups.groupname),
	      ["delete from im_friendgroups where empg='", XAempg ,"';"
	       "insert into im_friendgroups(empg,loginname,groupname) "
	       "values ('", XAempg, "', '", XAloginname, "', '", XAgroupname, "');"];
	 (_Host, _R) ->
	      []
      end).

-record(group,    
        {
         groupid,      
         groupname,   
         groupclass,        
         groupdesc,       
         grouppost,   
         creator,         
         add_member_method, 
         accessright      
         }).  
export_group(Server, Output) ->
    export_common(
      Server, group, Output,
      fun(Host, R)
	 when Server == "" orelse "" == Host ->
	      XAgroupid = escape(R#group.groupid),
	      XAgroupname = escape(R#group.groupname),
	      XAgroupclass = escape(R#group.groupclass),
	      XAgroupdesc = escape(R#group.groupdesc),
	      XAgrouppost = escape(R#group.grouppost),
	      XAcreator = escape(R#group.creator),
	      XAadd_member_method = escape(R#group.add_member_method),
	      XAaccessright = escape(R#group.accessright),
	      ["delete from im_group where groupid='", XAgroupid ,"';"
	       "insert into im_group(groupid,groupname,groupclass, groupdesc, grouppost, creator, add_member_method, accessright) "
	       "values ('", XAgroupid, "', '", XAgroupname, "', '", XAgroupclass, "', '", XAgroupdesc, "', '", XAgrouppost, "', '", XAcreator, "', '", XAadd_member_method, "', '", XAaccessright, "');"];
	 (_Host, _R) ->
	      []
      end).
      
-record (groupemployee,
         {employeeid, 
          groupid,
          grouprole,       
          employeenick,    
          employeenote    
          }).  
export_groupemployee(Server, Output) ->
    export_common(
      Server, groupemployee, Output,
      fun(Host, R)
	 when Server == "" orelse "" == Host ->
	      XAemployeeid = escape(R#groupemployee.employeeid),
	      XAgroupid = escape(R#groupemployee.groupid),
	      XAgrouprole = escape(R#groupemployee.grouprole),
	      XAemployeenick = escape(R#groupemployee.employeenick),
	      XAemployeenote = escape(R#groupemployee.employeenote),
	      ["delete from im_groupemployee where employeeid='", XAemployeeid ,"' and groupid='", XAgroupid ,"';"
	       "insert into im_groupemployee(employeeid,groupid,grouprole,employeenick, employeenote) "
	       "values ('", XAemployeeid, "', '", XAgroupid, "', '", XAgrouprole, "', '", XAemployeenick, "', '", XAemployeenote, "');"];
	 (_Host, _R) ->
	      []
      end).  

-record(grouptype,   
{
         typeid,      
         typename,    
         pid,        
         remark=""    
}).
export_grouptype(Server, Output) ->
    export_common(
      Server, grouptype, Output,
      fun(Host, R)
	 when Server == "" orelse "" == Host ->
	      XAtypeid = escape(R#grouptype.typeid),
	      XAtypename = escape(R#grouptype.typename),
	      XApid = escape(R#grouptype.pid),
	      XAremark = escape(R#grouptype.remark),
	      ["delete from im_grouptype where typeid='", XAtypeid ,"';"
	       "insert into im_grouptype(typeid,typename,pid,remark) "
	       "values ('", XAtypeid, "', '", XAtypename, "', '", XApid, "', '", XAremark, "');"];
	 (_Host, _R) ->
	      []
      end).  

-record(syscode,{code,    
                  desc,   
                  codetype}). 

export_syscode(Server, Output) ->
    export_common(
      Server, syscode, Output,
      fun(Host, R)
	 when is_list(R#syscode.code) andalso (Server == "" orelse "" == Host) ->
	      XAcode = escape(R#syscode.code),
	      XAdesc = escape(R#syscode.desc),
	      XAcodetype = escape(R#syscode.codetype),
	      ["delete from im_syscode where code='", XAcode ,"';"
	       "insert into im_syscode(code,codedesc,codetype) "
	       "values ('", XAcode, "', '", XAdesc, "', '", XAcodetype, "');"];
	 (_Host, _R) ->
	      []
      end).
          
-define(MAX_RECORDS_PER_TRANSACTION, 1000).
export_common(Server, Table, Output, ConvertFun) ->
    IO = case Output of
	     odbc ->
		 odbc;
	     _ ->
		 {ok, IODevice} = file:open(Output, [write, raw]),
		 IODevice
	 end,
    mnesia:transaction(
      fun() ->
	      mnesia:read_lock_table(Table),
	      LServer = jlib:nameprep(Server),
	      {_N, SQLs} =
		  mnesia:foldl(
		    fun(R, {N, SQLs} = Acc) ->
			    case ConvertFun(LServer, R) of
				[] ->
				    Acc;
				SQL ->
				    if
					N < ?MAX_RECORDS_PER_TRANSACTION - 1 ->
					    {N + 1, [SQL | SQLs]};
					true ->
					    %% Execute full SQL transaction
					    output(LServer, IO,
						   ["begin;",
						    lists:reverse([SQL | SQLs]),
						    "commit"]),
					    {0, []}
				    end
			    end
		    end, {0, []}, Table),
		  %% Execute SQL transaction with remaining records
	      output(LServer, IO,
		     ["begin;",
		      lists:reverse(SQLs),
		      "commit"])
      end).
      
output(LServer, IO, SQL) ->
    case IO of
	odbc ->
	    catch ejabberd_odbc:sql_query(LServer, SQL);
	_ ->
	    file:write(IO, [SQL, $;, $\n])
    end.
    
record_to_string(#roster{usj = {User, Server, JID},
			 name = Name,
			 subscription = Subscription,
			 ask = Ask,
			 askmessage = AskMessage}) ->
    Username = ejabberd_odbc:escape(User++"@"++Server),
    SJID = ejabberd_odbc:escape(jlib:jid_to_string(JID)),
    Nick = ejabberd_odbc:escape(Name),
    SSubscription = case Subscription of
			both -> "B";
			to   -> "T";
			from -> "F";
			none -> "N"
		    end,
    SAsk = case Ask of
	       subscribe   -> "S";
	       unsubscribe -> "U";
	       both	   -> "B";
	       out	   -> "O";
	       in	   -> "I";
	       none	   -> "N"
	   end,
    SAskMessage =
	case catch ejabberd_odbc:escape(
		     binary_to_list(list_to_binary([AskMessage]))) of
	    {'EXIT', _Reason} ->
		[];
	    SAM ->
		SAM
	end,
    ["("
     "'", Username, "',"
     "'", SJID, "',"
     "'", Nick, "',"
     "'", SSubscription, "',"
     "'", SAsk, "',"
     "'", SAskMessage, "',"
     "'N', '', 'item')"].

groups_to_string(#roster{usj = {User, Server, JID},
			 groups = Groups}) ->
    Username = ejabberd_odbc:escape(User++"@"++Server),
    SJID = ejabberd_odbc:escape(jlib:jid_to_string(JID)),
    [["("
      "'", Username, "',"
      "'", SJID, "',"
      "'", ejabberd_odbc:escape(Group), "')"] || Group <- Groups].

escape(undefined) ->
  "";
escape(S) ->  
  ejabberd_odbc:escape(S).
  
%%--------------------------------------------------------------------

tt() ->
  io:format("~p:~p~n", [234, 532]).
  
ttt() -> 
  {ok, Connection} = mongo_connection:start_link({"update.wefafa.com", 27017}, []),

  mongo:do(safe, master, Connection, we, fun() ->
		mongo:find_one('WeDocument.chunks', {'files_id', {bson:to_bin("507d27d7e4b26be43a000006")}, n, 0})
	end).

%	FilePid = gridfs:do(safe, master, Connection, we, fun()->
%	  Pid = gridfs:find_one('WeDocument', {'_id', {bson:to_bin("507d27d7e4b26be43a000006")}}),
%	  gridfs_file:set_timeout(Pid,60000),
%	  Pid 
%	end),
%	{ok, D} = gridfs_file:read_file(FilePid),
%	file:write_file(<<"/tmp/j.jpg">>, D).
  
%  {ok,File}=file:open(<<"/tmp/Winter.jpg">>, [read, binary]),
%  gridfs:do(safe, master, Connection, we, fun()->
%    gridfs:insert('WeDocument', <<"Winter.jpg">>, File)      
%  end).

%  gridfs:do(safe, master, Connection, we, fun()->
%    gridfs:delete('WeDocument', {'_id',{bson:to_bin("511f6a1a3493582807000008")}})      
%  end).

ios_push() ->
  % {ok,Sock} = gen_tcp:connect("gateway.sandbox.push.apple.com", 2195, [{active, true}, {send_timeout, 5}, binary], 10000),
  % {ok, TLSSock} = tls:tcp_to_tls(Sock, [{certfile, "/opt/fafa/conf/ios_push_dev.pem"}, verify_none, connect]),
  % tls:recv(TLSSock, 1, 3000),
  % tls:get_peer_certificate(TLSSock),
  {ok, TLSSock} = ssl:connect("gateway.sandbox.push.apple.com", 2195, [{certfile, "/opt/fafa/conf/ios_push_dev.pem"}], 30000),
  AppID = bson:to_bin("4E493BDC0D0B8EC25C57AFA2AA9759A16B67E20DC992BB7C4C4CAB73516A136A"),
  Message = <<1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 32, AppID/binary, 0, 26, "{\"aps\":{\"alert\":\"123456\"}}">>,
  tls:send(TLSSock, Message),
  tls:close(TLSSock),
  ok.