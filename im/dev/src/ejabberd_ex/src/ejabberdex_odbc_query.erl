%%%----------------------------------------------------------------------
%%% ejabberexÊý¾Ý¿â²Ù×÷Ïà¹Ø
%%%----------------------------------------------------------------------
%%% @doc ejabberexÊý¾Ý¿â²Ù×÷Ïà¹Ø
%%%----------------------------------------------------------------------
-module(ejabberdex_odbc_query).
-author('feihu929@sina.com').
-compile(export_all).

-include("../../ejabberd-2.1.11/include/ejabberd.hrl").
-include("../include/mod_ejabberdex_init.hrl").
-include("../../ejabberd-2.1.11/include/jlib.hrl").

%%%----------------------------------------------------------------------
%%% ×Ö·û´®×ªÈÕÆÚ
%%% Qdatestr ¸ñÊ½£º"yyyy-MM-dd[ HH:mm:ss]"
%%% ·µ»Ø date() {yyyy, mm ,dd}  
list_to_date(Qdatestr) ->
  [DateStr|_TimeStr] = string:tokens(Qdatestr, " "),
  case string:tokens(DateStr, "-") of
    [Year,Month,Day] ->
      {list_to_integer(Year),
       list_to_integer(Month),
       list_to_integer(Day)};
    _ ->
      {0, 0, 0}
  end.

%%%----------------------------------------------------------------------
%%% ÔÚÊÂÎñÄÚÖ´ÐÐ
%%% Q [sqlstring, sqlstring, ...]»òFun
sql_transaction(Q) ->
  Are = ejabberd_odbc:sql_transaction("", Q),
  case Are of 
    {atomic,ok} -> ok;
    Err -> ?ERROR_MSG("~p ~p~n  StackTrace:~p", [Err,Q, erlang:get_stacktrace()])
  end,
  Are.

%%%----------------------------------------------------------------------
%%% Éú³ÉÐòÁÐÖµ
%%% Qtable_name, Qcol_name string()
%%% ·µ»Ø ÐÂµÄÐòÁÐÖµ  string()
%%% ÔÚ¼¯ÈºÄ£Ê½ÏÂ£¬Ó¦´ÓÄ³Ò»¸öÍ³Ò»µÄ·þÎñ´¦Éú³ÉÐòÁÐ£¬±ÜÃâ¾ºÕù
get_seq_nextvalue(Qtable_name, Qcol_name) ->
  XQtable_name = ejabberd_odbc:escape(Qtable_name),
  XQcol_name = ejabberd_odbc:escape(Qcol_name),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["call p_seq_nextvalue('", XQtable_name, "', '", XQcol_name, "', 0, @nextvalue)"]),
  ARows = extract_resultrows(Ars),
  Are = element(1, hd(ARows)),
  Are.

%%%----------------------------------------------------------------------
%%% ´Ó½á¹û¼¯ÖÐÈ¡³öÐÐ
%%% Qrs ejabberd_odbc:sql_query µÄ·µ»ØÖµ
%%% ·µ»Ø [{col1value, col2value, ...}]
extract_resultrows(Qrs) ->
  case Qrs of
    {selected, _, Items} ->
      Items;
    {error, Err} ->
      ?ERROR_MSG("error ~p~n  StackTrace:~p", [Err, erlang:get_stacktrace()]),
      [];
    _ ->
      []
  end.

%%%----------------------------------------------------------------------
%%% È¡ÊýÀý×Ó
%get_data(LServer, Username) ->
%    ejabberd_odbc:sql_query(
%      LServer,
%      ["select seconds, state from last "
%       "where username='", Username++"@"++LServer, "'"]).
%%%----------------------------------------------------------------------
%%% ÆäËüÀý×Ó
%del_last(LServer, Username) ->
%    ejabberd_odbc:sql_query(
%      LServer,
%      ["delete from last where username='", Username++"@"++LServer, "'"]).
%add_user(LServer, Username, Pass) ->
%    ejabberd_odbc:sql_query(
%      LServer,
%      ["insert into users(username, password) "
%       "values ('", Username++"@"++LServer, "', '", Pass, "');"]).      
%set_password_t(LServer, Username, Pass) ->
%    ejabberd_odbc:sql_transaction(
%      LServer,
%      fun() ->
%	      update_t("users", ["username", "password"],
%		       [Username++"@"++LServer, Pass],
%		       ["username='", Username++"@"++LServer ,"'"])
%      end).

%%%----------------------------------------------------------------------
%%% È¡³öÔÚÏßºÃÓÑ
%%% ·µ»Ø [{us, res, usr}]
get_online_roster(US) ->
  XUS = ejabberd_odbc:escape(US),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select a.us, a.res, concat(a.us, '/', a.res) usr "
       "from global_session a, rosterusers b "
       "where a.us=b.jid "
       "  and b.username='", XUS, "' "
       "  and b.subscription='B'"]),
  ARows = extract_resultrows(Ars),
  Are = ARows,
  Are.
%%%----------------------------------------------------------------------
%%% È¡³öÖ¸¶¨ÓÃ»§µÄ×éÖ¯»ú¹¹°æ±¾
%%% ·µ»Ø [#dept_version{}]
get_dept_version(US) ->
  XUS = ejabberd_odbc:escape(US),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select us, version from im_dept_version "
       "where us='", XUS, "'"]),
  ARows = extract_resultrows(Ars),
  Are = [#dept_version{us = element(1, A), version = element(2, A)} || A <- ARows],
  Are.
%%%----------------------------------------------------------------------
%%% ÉèÖÃÖ¸¶¨ÓÃ»§µÄ×éÖ¯»ú¹¹°æ±¾
set_dept_version(US, Version) ->
  XUS = ejabberd_odbc:escape(US),
  XVersion = ejabberd_odbc:escape(Version),
  Asqls = [
           ["delete from im_dept_version where us='", XUS, "'"],
           ["insert into im_dept_version(us, version) values ('", XUS, "', '", XVersion, "')"]
         ],
  sql_transaction(Asqls).

%%%----------------------------------------------------------------------
%%% È¡³öÖ¸¶¨ÓÃ»§µÄ×éÖ¯»ú¹¹³ÉÔ±°æ±¾
%%% ·µ»Ø [#employee_version{}]
get_employee_version(US) ->
  XUS = ejabberd_odbc:escape(US),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select us, version from im_employee_version "
       "where us='", XUS, "'"]),
  ARows = extract_resultrows(Ars),
  Are = [#employee_version{us = element(1, A), version = element(2, A)} || A <- ARows],
  Are.
%%%----------------------------------------------------------------------
%%% ÉèÖÃÖ¸¶¨ÓÃ»§µÄ×éÖ¯»ú¹¹³ÉÔ±°æ±¾
set_employee_version(US, Version) ->
  XUS = ejabberd_odbc:escape(US),
  XVersion = ejabberd_odbc:escape(Version),
  Asqls = [
           ["delete from im_employee_version where us='", XUS, "'"],
           ["insert into im_employee_version(us, version) values ('", XUS, "', '", XVersion, "')"]
         ],
  sql_transaction(Asqls).
  
%%%----------------------------------------------------------------------
%%% È¡³öÖ¸¶¨»áÒéIDµÄ»áÒé
%%% ·µ»Ø [#meeting_run{}]
get_meeting_run_by_groupid(Qgroupid) ->
  XQgroupid = ejabberd_odbc:escape(Qgroupid),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select groupid, us, emprole, in_date, quit_date, state from im_meeting_run "
       "where groupid='", XQgroupid, "' order by emprole desc,state desc"]),
  ARows = extract_resultrows(Ars),
  Are = [#meeting_run{meetingemp = {element(1, A), element(2, A)}, 
                      meetingid = element(1, A), 
                      emprole = element(3, A), 
                      in_date = element(4, A), 
                      quit_date = element(5, A), 
                      state = element(6, A) } 
           || A <- ARows],
  Are.  
%%%----------------------------------------------------------------------
%%% È¡³öÖ¸¶¨ÓÃ»§ÕýÔÚ²ÎÓëµÄËùÓÐ»áÒé
%%% ·µ»Ø [#meeting_run{}]
get_meeting_run_by_us(US) ->
  XUS = ejabberd_odbc:escape(US),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select groupid, us, emprole, in_date, quit_date, state from im_meeting_run "
       "where us like '", XUS, "%'"]),
  ARows = extract_resultrows(Ars),
  Are = [#meeting_run{meetingemp = {element(1, A), element(2, A)}, 
                      meetingid = element(1, A), 
                      emprole = element(3, A), 
                      in_date = element(4, A), 
                      quit_date = element(5, A), 
                      state = element(6, A) } 
           || A <- ARows],
  Are.  
%%%----------------------------------------------------------------------
%%% È¡³öÖ¸¶¨ÓÃ»§ÕýÔÚ²ÎÓëµÄÖ¸¶¨»áÒé
%%% ·µ»Ø [#meeting_run{}]
get_meeting_run_by_key(Qgroupid, US) ->
  XQgroupid = ejabberd_odbc:escape(Qgroupid),
  XUS = ejabberd_odbc:escape(US),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select groupid, us, emprole, in_date, quit_date, state from im_meeting_run "
       "where groupid='", XQgroupid, "' and us='", XUS, "'"]),
  ARows = extract_resultrows(Ars),
  Are = [#meeting_run{meetingemp = {element(1, A), element(2, A)}, 
                      meetingid = element(1, A), 
                      emprole = element(3, A), 
                      in_date = element(4, A), 
                      quit_date = element(5, A), 
                      state = element(6, A) } 
           || A <- ARows],
  Are.
%%%----------------------------------------------------------------------
%%% É¾³ý»áÒé
del_meeting_run(Qgroupid) ->
  XQgroupid = ejabberd_odbc:escape(Qgroupid),
  Asqls = [
            ["delete from im_meeting_run where groupid='", XQgroupid, "'"]
          ],
  sql_transaction(Asqls).
%%%----------------------------------------------------------------------
%%% ´Óµ±Ç°»áÒéÊÒÖÐÒÆ³ý¸Ã³ÉÔ±
del_meeting_run(Qgroupid, Qus) ->
  XQgroupid = ejabberd_odbc:escape(Qgroupid),
  XQus = ejabberd_odbc:escape(Qus),
  Asqls = [
            ["delete from im_meeting_run where groupid='", XQgroupid, "' and us like '", XQus, "%'"]
          ],
  sql_transaction(Asqls).
%%%----------------------------------------------------------------------
%%% ¼ÓÈë»áÒé³ÉÔ±
%%% Qmeeting_run  #meeting_run{}
save_meeting_run(Qmeeting_run) ->
  Xgroupid = ejabberd_odbc:escape(element(1, Qmeeting_run#meeting_run.meetingemp)),
  Xus = ejabberd_odbc:escape(element(2, Qmeeting_run#meeting_run.meetingemp)),
  Vals = [Xgroupid, 
          Xus, 
          ejabberd_odbc:escape(Qmeeting_run#meeting_run.emprole), 
          ejabberd_odbc:escape(Qmeeting_run#meeting_run.in_date), 
          ejabberd_odbc:escape(Qmeeting_run#meeting_run.quit_date), 
          ejabberd_odbc:escape(Qmeeting_run#meeting_run.state)
          ],
  Asqls = [
            ["delete from im_meeting_run where groupid='", Xgroupid, "' and us='", Xus, "'"],
            ["insert into im_meeting_run (groupid, us, emprole, in_date, quit_date, state)"
             "  values('", odbc_queries:join(Vals, "', '"), "')"]
          ],
  sql_transaction(Asqls).
  
%%%----------------------------------------------------------------------
%%% È¡³öÖ¸¶¨ÓÃ»§µÄÈº°æ±¾
%%% ·µ»Ø [#group_version{}]
get_group_version(US) ->
  XUS = ejabberd_odbc:escape(US),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select us, version from im_group_version "
       "where us='", XUS, "'"]),
  ARows = extract_resultrows(Ars),
  Are = [#group_version{us = element(1, A), version = element(2, A)} || A <- ARows],
  Are.
%%%----------------------------------------------------------------------
%%% ÉèÖÃÖ¸¶¨ÓÃ»§µÄÈº°æ±¾
set_group_version(US, Version) ->
  XUS = ejabberd_odbc:escape(US),
  XVersion = ejabberd_odbc:escape(Version),
  Asqls = [
           ["delete from im_group_version where us='", XUS, "'"],
           ["insert into im_group_version(us, version) values ('", XUS, "', '", XVersion, "')"]
         ],
  sql_transaction(Asqls).  
  
%%%----------------------------------------------------------------------
%%% É¾³ýÖ¸¶¨ÓÃ»§µÄÈº°æ±¾
del_group_version(US) ->
  XUS = ejabberd_odbc:escape(US),
  Asqls = [
           ["delete from im_group_version where us='", XUS, "'"]
         ],
  sql_transaction(Asqls).  

%%%----------------------------------------------------------------------
%%% ¸ù¾ÝÈºID£¬É¾³ýÏà¹ØÓÃ»§µÄÈº°æ±¾
del_group_version_by_groupid(Qgroupid) ->
  XQgroupid = ejabberd_odbc:escape(Qgroupid),
  Asqls = [
           ["delete from im_group_version where us in (select employeeid from im_groupemployee where groupid='", XQgroupid, "' )"]
         ],
  sql_transaction(Asqls).  
  
%%%----------------------------------------------------------------------
%%% È¡³öÖ¸¶¨ÓÃ»§µÄÈº³ÉÔ±°æ±¾
%%% ·µ»Ø [#groupemployee_version{}]
get_groupemployee_version(US, Qgroupid) ->
  XUS = ejabberd_odbc:escape(US),
  XQgroupid = ejabberd_odbc:escape(Qgroupid),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select us, groupid, version from im_groupemployee_version "
       "where us='", XUS, "' and groupid='", XQgroupid, "'"]),
  ARows = extract_resultrows(Ars),
  Are = [#groupemployee_version{us = element(1, A), groupid = element(2, A), version = element(3, A)} || A <- ARows],
  Are.
%%%----------------------------------------------------------------------
%%% ÉèÖÃÖ¸¶¨ÓÃ»§µÄÈº³ÉÔ±°æ±¾
set_groupemployee_version(US, Qgroupid, Version) ->
  XUS = ejabberd_odbc:escape(US),
  XQgroupid = ejabberd_odbc:escape(Qgroupid),
  XVersion = ejabberd_odbc:escape(Version),
  Asqls = [
           ["delete from im_groupemployee_version where us='", XUS, "' and groupid='", XQgroupid, "'"],
           ["insert into im_groupemployee_version(us, groupid, version) values ('", XUS, "', '", XQgroupid, "', '", XVersion, "')"]
         ],
  sql_transaction(Asqls).

%%%----------------------------------------------------------------------
%%% ¸ù¾ÝÈºID£¬É¾³ýÏà¹ØÓÃ»§µÄÈº³ÉÔ±°æ±¾
del_groupemployee_version_by_groupid(Qgroupid) ->
  XQgroupid = ejabberd_odbc:escape(Qgroupid),
  Asqls = [
           ["delete from im_groupemployee_version where groupid='", XQgroupid, "'"]
         ],
  sql_transaction(Asqls).  
        
%%%----------------------------------------------------------------------
%%% ²éÑ¯Èº¹²ÏíÎÄ¼þ
%%% Qgroupidstring()
%%% Qstart, Qcount integer() ·µ»Ø½á¹û¼¯µÄÆðÊ¼Î»ÖÃ¡¢ÊýÁ¿
%%% ·µ»Ø {[#groupshare_file{}], RecordCount}
query_groupsharefile(Qgroupid, Qstart, Qcount) ->
  XQgroupid = ejabberd_odbc:escape(Qgroupid),
  Asql = "select a.groupid, a.fileid, a.filename, a.addstaff, a.adddate, a.filesize 
from im_groupshare_file a 
where a.groupid='"++XQgroupid++"' 
order by a.adddate desc ",
         
  Ars1 = ejabberd_odbc:sql_query(
      "",
      [Asql, " limit ", integer_to_list(Qstart), ", ", integer_to_list(Qcount)]),
  ARows1 = extract_resultrows(Ars1),
  Are1 = [#groupshare_file{groupid = element(1, A), fileid = element(2, A), filename = element(3, A),
                          addstaff = element(4, A), 
                          adddate = list_to_date(element(5, A)), 
                          filesize = round(list_to_float(element(6, A)))} 
         || A <- ARows1],
         
  Ars2 = ejabberd_odbc:sql_query(
      "",
      ["select count(*) c from (", Asql, ") as a_"]),
  RecordCount = case Ars2 of
    {selected, _, Items} ->
      list_to_integer(element(1, hd(Items)));
    {error, Err} ->
      ?ERROR_MSG("error ~p~n  StackTrace:~p", [Err, erlang:get_stacktrace()]),
      0;
    _ ->
      0
  end,
  
  Are = {Are1, RecordCount},
  Are.
  
%%%----------------------------------------------------------------------
%%% ÐÂ¼ÓÈëÈº¹²ÏíÎÄ¼þ
%%% Qgroupshare_file #groupshare_file{}
ins_groupshare_file(Qgroupshare_file) ->
  {_ADate, {AHour, AMin, ASec}} = erlang:localtime(),
  Xgroupid = ejabberd_odbc:escape(Qgroupshare_file#groupshare_file.groupid),
  Xfileid = ejabberd_odbc:escape(Qgroupshare_file#groupshare_file.fileid),
  Vals = [Xgroupid, 
          Xfileid, 
          ejabberd_odbc:escape(Qgroupshare_file#groupshare_file.filename), 
          ejabberd_odbc:escape(Qgroupshare_file#groupshare_file.addstaff), 
          io_lib:format("~p-~p-~p ~p:~p:~p", [element(1, Qgroupshare_file#groupshare_file.adddate), element(2, Qgroupshare_file#groupshare_file.adddate), element(3, Qgroupshare_file#groupshare_file.adddate), AHour, AMin, ASec])
          ],
  Asqls = [
           ["delete from im_groupshare_file where groupid='", Xgroupid, "' and fileid='", Xfileid, "'"],
           ["insert into im_groupshare_file(groupid, fileid, filename, addstaff, adddate, filesize) "
            "  values('", odbc_queries:join(Vals, "', '"), "', ", integer_to_list(Qgroupshare_file#groupshare_file.filesize), ")"]
         ],
  sql_transaction(Asqls).

  
%%%----------------------------------------------------------------------
%%% É¾³ýÈº¹²ÏíÎÄ¼þ
del_groupshare_file(Qgroupid, Qfileid) ->
  XQgroupid = ejabberd_odbc:escape(Qgroupid),
  XQfileid = ejabberd_odbc:escape(Qfileid),
  Asqls = [
           ["delete from im_groupshare_file where groupid='", XQgroupid, "' and fileid='", XQfileid, "'"]
         ],
  sql_transaction(Asqls).

%%%----------------------------------------------------------------------
%%% È¡³öÖ¸¶¨ÓÃ»§µÄÖ¸¶¨½ÇÉ«
%%% ·µ»Ø [#employeerole{}]
get_employeerole(Qemployeeid, Qroleid) ->
  XQemployeeid = ejabberd_odbc:escape(Qemployeeid),
  XQroleid = ejabberd_odbc:escape(Qroleid),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select employeeid, roleid from im_employeerole "
       "where employeeid='", XQemployeeid, "' and roleid='", XQroleid, "'"]),
  ARows = extract_resultrows(Ars),
  Are = [#employeerole{employeeid = element(1, A), roleid = element(2, A)} || A <- ARows],
  Are.
%%%----------------------------------------------------------------------
%%% È¡³öÖ¸¶¨Èº 
%%% ·µ»Ø [#group{}]
get_group(Qgroupid) ->
  XQgroupid = ejabberd_odbc:escape(Qgroupid),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select a.groupid, a.groupname, a.groupclass, ifnull(a.groupdesc, '') groupdesc, ifnull(a.grouppost, '') grouppost, a.creator, a.add_member_method, a.accessright,ifnull(a.logo,'') logo,ifnull(a.number,0) number,ifnull(a.max_number,0) max_number "
       "from im_group a "
       "where groupid='", XQgroupid, "'"]),
  ARows = extract_resultrows(Ars),
  Are = [#group{groupid = element(1, A), groupname = element(2, A), groupclass = element(3, A),
                groupdesc = element(4, A), grouppost = element(5, A), creator = element(6, A), 
                add_member_method = element(7, A), accessright = element(8, A), logo = element(9, A), number = element(10, A), max_number = element(11, A)} 
         || A <- ARows],
  Are.  
%%%----------------------------------------------------------------------
%%% È¡³öÖ¸¶¨Èº 
%%% ·µ»Ø [#group{}]
get_groupbyemployeeid(Qemployeeid) ->
  XQemployeeid = ejabberd_odbc:escape(Qemployeeid),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select a.groupid, a.groupname, a.groupclass, ifnull(a.groupdesc, '') groupdesc, ifnull(a.grouppost, '') grouppost, a.creator, a.add_member_method, a.accessright,ifnull(a.logo,'') logo,ifnull(a.number,0) number,ifnull(a.max_number,0) max_number "
       "from im_group a "
       "where a.groupid in (select b.groupid from im_groupemployee b where b.employeeid='", XQemployeeid, "') order by a.groupid"]),
  ARows = extract_resultrows(Ars),
  Are = [#group{groupid = element(1, A), groupname = element(2, A), groupclass = element(3, A),
                groupdesc = element(4, A), grouppost = element(5, A), creator = element(6, A), 
                add_member_method = element(7, A), accessright = element(8, A), logo = element(9, A), number = element(10, A), max_number = element(11, A)} 
         || A <- ARows],
  Are.
%%%----------------------------------------------------------------------
%%% ËÑË÷Èº¡£ËÑË÷¿ª·ÅÀàÐÍµÄÈº
%%% Qgroupid, Qgroupname, Qgroupclass string() ËÑË÷Ìõ¼þ£¬¿ÉÎª ""
%%% Qstart, Qcount integer() ·µ»Ø½á¹û¼¯µÄÆðÊ¼Î»ÖÃ¡¢ÊýÁ¿
%%% ·µ»Ø [#group{}]
query_group(Qgroupid, Qgroupname, Qgroupclass, Qstart, Qcount) ->
  XQgroupid = ejabberd_odbc:escape(Qgroupid),
  case XQgroupid of []->
				  XQgroupname = ejabberd_odbc:escape(Qgroupname),
				  XQgroupclass = ejabberd_odbc:escape(Qgroupclass),
				  Ars = ejabberd_odbc:sql_query(
				      "",
				      ["select a.groupid, a.groupname, a.groupclass, ifnull(a.groupdesc, '') groupdesc, ifnull(a.grouppost, '') grouppost, a.creator, a.add_member_method, a.accessright,ifnull(a.logo,'') logo,ifnull(a.number,0) number,ifnull(a.max_number,0) max_number "
				       "from im_group a "
				       "where a.add_member_method='0' ",
				       (case XQgroupname of "" -> ""; _ -> "  and a.groupname like '%"++XQgroupname++"%' " end),
				       (case XQgroupclass of "" -> " and ifnull(a.groupclass, '') not in ('discussgroup', 'meeting', 'circlegroup') "; _ -> "  and a.groupclass='"++XQgroupclass++"' " end),
				       " order by a.groupid limit ", integer_to_list(Qstart), ", ", integer_to_list(Qcount)]),
				  ARows = extract_resultrows(Ars),
				  Are = [#group{groupid = element(1, A), groupname = element(2, A), groupclass = element(3, A),
				                groupdesc = element(4, A), grouppost = element(5, A), creator = element(6, A), 
				                add_member_method = element(7, A), accessright = element(8, A), logo = element(9, A), number = element(10, A), max_number = element(11, A)} 
				         || A <- ARows],
				  Are;
  _->
				  Ars = ejabberd_odbc:sql_query(
				      "",
				      ["select a.groupid, a.groupname, a.groupclass, ifnull(a.groupdesc, '') groupdesc, ifnull(a.grouppost, '') grouppost, a.creator, a.add_member_method, a.accessright,ifnull(a.logo,'') logo,ifnull(a.number,0) number,ifnull(a.max_number,0) max_number "
				       "from im_group a where a.groupid='"++XQgroupid++"' "]),
				  ARows = extract_resultrows(Ars),
				  Are = [#group{groupid = element(1, A), groupname = element(2, A), groupclass = element(3, A),
				                groupdesc = element(4, A), grouppost = element(5, A), creator = element(6, A), 
				                add_member_method = element(7, A), accessright = element(8, A), logo = element(9, A), number = element(10, A), max_number = element(11, A)} 
				         || A <- ARows],
				  Are  
  end
  .
  
%%%----------------------------------------------------------------------
%%% Éú³É´æ´¢groupµÄSQL
%%% Qgroup #group{}
%%% ·µ»Ø array of sql string()
ins_group_sql(Qgroup) -> 
  Vals = [ejabberd_odbc:escape(Qgroup#group.groupid), 
          ejabberd_odbc:escape(Qgroup#group.groupname), 
          ejabberd_odbc:escape(Qgroup#group.groupclass), 
          ejabberd_odbc:escape(Qgroup#group.groupdesc), 
          ejabberd_odbc:escape(Qgroup#group.grouppost), 
          ejabberd_odbc:escape(Qgroup#group.creator), 
          ejabberd_odbc:escape(Qgroup#group.add_member_method), 
          ejabberd_odbc:escape(Qgroup#group.accessright),
          ejabberd_odbc:escape(Qgroup#group.logo),
          ejabberd_odbc:escape(Qgroup#group.number),
          ejabberd_odbc:escape(Qgroup#group.max_number)
          ],
  Asqls = [
            ["insert into im_group (groupid, groupname, groupclass, groupdesc, grouppost, creator, add_member_method, accessright,logo,number,max_number,createdate)"
             "  values('", odbc_queries:join(Vals, "', '"), "',now())"]
          ],
  Asqls.
  
%%%----------------------------------------------------------------------
%%% Éú³É´æ´¢groupµÄSQL
%%% Qgroup #group{}
%%% ·µ»Ø array of sql string()
update_group_sql(Qgroup) -> 
  Asqls = [
            ["update im_group set "
             "  groupname='", ejabberd_odbc:escape(Qgroup#group.groupname), "', "
             "  groupclass='", ejabberd_odbc:escape(Qgroup#group.groupclass), "', "
             "  groupdesc='", ejabberd_odbc:escape(Qgroup#group.groupdesc), "', "
             "  grouppost='", ejabberd_odbc:escape(Qgroup#group.grouppost), "', "
             "  creator='", ejabberd_odbc:escape(Qgroup#group.creator), "', "
             "  add_member_method='", ejabberd_odbc:escape(Qgroup#group.add_member_method), "', "
             "  accessright='", ejabberd_odbc:escape(Qgroup#group.accessright), "' "
             "where groupid='", ejabberd_odbc:escape(Qgroup#group.groupid), "'"]
          ],
  Asqls.
  

%%%----------------------------------------------------------------------
%%% Éú³É É¾³ýÈº¼°³ÉÔ± µÄSQL
%%% ·µ»Ø array of sql string()
del_group_sql(Qgroupid) -> 
  XQgroupid = ejabberd_odbc:escape(Qgroupid),
  Asqls = [
            ["delete from im_group where groupid='", XQgroupid, "'"],
            ["delete from im_groupemployee where groupid='", XQgroupid, "'"]
          ],
  Asqls.
  
%%%----------------------------------------------------------------------
%%% È¡³öÖ¸¶¨ÈºµÄ³ÉÔ± 
%%% ·µ»Ø [#groupemployee{}]
get_groupemployee(Qgroupid) ->
  XQgroupid = ejabberd_odbc:escape(Qgroupid),
  Ars = ejabberd_odbc:sql_query(
      "",
      [" select a.employeeid, a.groupid, a.grouprole, ifnull(a.employeenick, '') employeenick, ifnull(a.employeenote, '') employeenote, ifnull(b.photo,'') photo, ifnull(b.spell,'') spell "
       " from im_groupemployee a left join im_employee b"
       " on a.employeeid=b.loginname where a.groupid='", XQgroupid, "' order by a.employeeid"]),
  ARows = extract_resultrows(Ars),
  %%注：employeenote实际存储的是成员头像属性
  Are = [#groupemployee{employeeid = element(1, A), groupid = element(2, A), grouprole = element(3, A),
                       employeenick = element(4, A), employeenote = element(5, A), photo = element(6, A), spell = element(7, A)} 
         || A <- ARows],
  Are.
get_groupmanagers(Qgroupid)->
  XQgroupid = ejabberd_odbc:escape(Qgroupid),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select employeeid from im_groupemployee where groupid='", XQgroupid, "' and grouprole='manager'"]),
  ARows = extract_resultrows(Ars),
  Are = [element(1, A)|| A <- ARows],
  Are
.

%%%----------------------------------------------------------------------
%%% È¡³öÖ¸¶¨ÈºµÄÖ¸¶¨³ÉÔ± 
%%% ·µ»Ø [#groupemployee{}]
get_groupemployee(Qgroupid, Qemployeeid) ->
  XQgroupid = ejabberd_odbc:escape(Qgroupid),
  XQemployeeid = ejabberd_odbc:escape(Qemployeeid),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select employeeid, groupid, grouprole, ifnull(employeenick, '') employeenick, ifnull(employeenote, '') employeenote "
       "from im_groupemployee "
       "where groupid='", XQgroupid, "' and employeeid='", XQemployeeid, "'"]),
  ARows = extract_resultrows(Ars),
  Are = [#groupemployee{employeeid = element(1, A), groupid = element(2, A), grouprole = element(3, A),
                       employeenick = element(4, A), employeenote = element(5, A)} 
         || A <- ARows],
  Are.  

get_groupemployeePage(Qgroupid,Qlimit) ->
  XQgroupid = ejabberd_odbc:escape(Qgroupid),
  Ars = ejabberd_odbc:sql_query(
      "",
      [" select a.employeeid, a.groupid, a.grouprole, ifnull(a.employeenick, '') employeenick, ifnull(a.employeenote, '') employeenote, ifnull(b.photo,'') photo, ifnull(b.spell,'') spell "
       " from im_groupemployee a left join im_employee b"
       " on a.employeeid=b.loginname where a.groupid='", XQgroupid, "' order by a.employeeid ",case Qlimit of ""-> ""; _-> " limit "++Qlimit end]),
  ARows = extract_resultrows(Ars),
  %%注：employeenote实际存储的是成员头像属性
  Are = [#groupemployee{employeeid = element(1, A), groupid = element(2, A), grouprole = element(3, A),
                       employeenick = element(4, A), employeenote = element(5, A), photo = element(6, A), spell = element(7, A)} 
         || A <- ARows],
  Are.
%%%----------------------------------------------------------------------
%%% Éú³É´æ´¢groupemployeeµÄSQL
%%% Qgroupemployee #groupemployee{}
%%% ·µ»Ø array of sql string()
ins_groupemployee_sql(Qgroupemployee) -> 
  Vals = [ejabberd_odbc:escape(Qgroupemployee#groupemployee.employeeid), 
          ejabberd_odbc:escape(Qgroupemployee#groupemployee.groupid), 
          ejabberd_odbc:escape(Qgroupemployee#groupemployee.grouprole), 
          ejabberd_odbc:escape(Qgroupemployee#groupemployee.employeenick), 
          ejabberd_odbc:escape(Qgroupemployee#groupemployee.employeenote)
          ],
  Asqls = [
            ["insert into im_groupemployee (employeeid, groupid, grouprole, employeenick, employeenote)"
             "  values('", odbc_queries:join(Vals, "', '"), "')"],
            ["update im_group set number=number+1 where groupid='",Qgroupemployee#groupemployee.groupid,"'"]
          ],
  Asqls.
%%%----------------------------------------------------------------------
%%% Éú³É É¾³ýÈº³ÉÔ± µÄSQL
%%% ·µ»Ø array of sql string()
del_groupemployee_sql(Qgroupid, Qemployeeid) -> 
  XQgroupid = ejabberd_odbc:escape(Qgroupid),
  XQemployeeid = ejabberd_odbc:escape(Qemployeeid),
  Asqls = [
            ["delete from im_groupemployee where groupid='", XQgroupid, "' and employeeid='", XQemployeeid, "'"],
            ["update im_group set number=number-1 where groupid='",XQgroupid,"'"]
          ],
  Asqls.
%%%----------------------------------------------------------------------
%%% Éú³É´æ´¢groupemployeeµÄSQL
%%% Qgroupemployee #groupemployee{}
%%% ·µ»Ø array of sql string()
update_groupemployee_sql(Qgroupemployee) -> 
  XQgroupid = ejabberd_odbc:escape(Qgroupemployee#groupemployee.groupid),
  Xemployeeid = ejabberd_odbc:escape(Qgroupemployee#groupemployee.employeeid),
  Xrole = ejabberd_odbc:escape(Qgroupemployee#groupemployee.grouprole),
  Asqls = case Xrole of "owner"->
    [
        ["update im_groupemployee set "
                 "  grouprole='", Xrole, "', "
                 "  employeenick='", ejabberd_odbc:escape(Qgroupemployee#groupemployee.employeenick), "', "
                 "  employeenote='", ejabberd_odbc:escape(Qgroupemployee#groupemployee.employeenote), "' "
                 "where employeeid='", Xemployeeid, "' and groupid='", XQgroupid , "'"],
        ["update im_group set creator='",Xemployeeid,"' where groupid='",XQgroupid,"'"]
    ];
  _->
    [
        ["update im_groupemployee set "
             "  grouprole='", Xrole, "', "
             "  employeenick='", ejabberd_odbc:escape(Qgroupemployee#groupemployee.employeenick), "', "
             "  employeenote='", ejabberd_odbc:escape(Qgroupemployee#groupemployee.employeenote), "' "
             "where employeeid='", Xemployeeid, "' and groupid='", XQgroupid , "'"]
    ]
  end,
  Asqls.


get_subscribe_ex(Qjid, Qrtype) ->
  XQjid = Qjid#jid.luser ++ "@" ++ Qjid#jid.lserver,
  XQrtype = ejabberd_odbc:escape(Qrtype),
  Asqls = ejabberd_odbc:sql_query(
      		"",
           	["select rid from im_subscribe_ex where jid='", XQjid, "' and rtype='", XQrtype, "'"]
        ),
  ARows = extract_resultrows(Asqls),
  [element(1,Item)||Item<-ARows].

%%%----------------------------------------------------------------------
%%% É¾³ýÓÃ»§µ¥Î»³öÏ¯¶©ÔÄÐÅÏ¢
%%% Qjid jid
del_subscribe_ex(Qjid) ->
  XQjid = Qjid#jid.luser ++ "@" ++ Qjid#jid.lserver,
  Asqls = [
           ["delete from im_subscribe_ex where jid='", XQjid, "' "]
         ],
  sql_transaction(Asqls).


%%%----------------------------------------------------------------------
%%% É¾³ýÓÃ»§µ¥Î»³öÏ¯¶©ÔÄÐÅÏ¢
%%% Qjid jid
%%% Qrid, Qrtype string()
del_subscribe_ex(Qjid, Qrid, Qrtype) ->
  XQjid = Qjid#jid.luser ++ "@" ++ Qjid#jid.lserver,
  XQrid = ejabberd_odbc:escape(Qrid),
  XQrtype = ejabberd_odbc:escape(Qrtype),
  Asqls = [
           ["delete from im_subscribe_ex where jid='", XQjid, "' and rid='", XQrid, "' and rtype='", XQrtype, "'"]
         ],
  sql_transaction(Asqls).
    
%%%----------------------------------------------------------------------
%%% ÉèÖÃÓÃ»§µ¥Î»³öÏ¯¶©ÔÄÐÅÏ¢
%%% Qsubscribe_ex #subscribe_ex{}
set_subscribe_ex(Qsubscribe_ex) ->
  Qjid = Qsubscribe_ex#subscribe_ex.jid,
  XQjid = Qjid#jid.luser ++ "@" ++ Qjid#jid.lserver,
  XQrid = ejabberd_odbc:escape(Qsubscribe_ex#subscribe_ex.rid),
  XQrtype = ejabberd_odbc:escape(Qsubscribe_ex#subscribe_ex.rtype),
  Vals = [XQjid, 
          XQrid, 
          XQrtype
          ],
  Asqls = [
           ["delete from im_subscribe_ex where jid='", XQjid, "' and rid='", XQrid, "' and rtype='", XQrtype, "'"],
           ["insert into im_subscribe_ex(jid, rid, rtype) values ('", odbc_queries:join(Vals, "', '"), "')"]
         ],
  sql_transaction(Asqls).

%%%----------------------------------------------------------------------
%%% ²éÕÒ¶©ÔÄÁËµ±Ç°ÈËÔ±ËùÊô²¿ÃÅ¡¢Èº¼°ÆäËûÈËÔ±£¬²¢ÇÒ²»ÊÇ¸ÃÈËºÃÓÑµÄÈËÔ±JID
%%% QUS string()
%%% ·µ»Ø [jid]
get_subscribe_ex_jid_byfrom(QUS) ->
  %%%´ÓjidÖÐ»ñÈ¡ÆóÒµºÅ
  JidStrLst=[binary_to_list(Ele)||Ele<-re:split(QUS,"@")],
  JidUser = hd(JidStrLst),
  Eno = lists:nth(2,[binary_to_list(Ele)||Ele<-re:split(JidUser,"-")]),
  XQUS = ejabberd_odbc:escape(QUS),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select distinct jid 
from (
	select b.jid
	from im_employee a, im_subscribe_ex b
	where a.deptid=b.rid and b.rtype='0'
		and a.loginname='", XQUS, "'
	union 
	select b.jid
	from im_groupemployee a, im_subscribe_ex b
	where a.groupid=b.rid and b.rtype='1'
		and a.employeeid='", XQUS, "'
	union 
	select b.jid
	from  im_subscribe_ex b
	where b.rtype='2'	and b.rid='", XQUS, "'
	union 
	select b.jid
	from im_subscribe_ex b
	where b.rid='",Eno,"' and b.rtype='3'
) as t_jid 
where t_jid.jid not like '", XQUS, "%'
  and not exists(select 1 from rosterusers b where b.username='", XQUS, "' and b.subscription='B' and b.jid=substr(t_jid.jid, 1, length(b.jid)))"]),
  ARows = extract_resultrows(Ars),
  Are = [jlib:string_to_jid(element(1, A))
         || A <- ARows],
  Are.

%%%----------------------------------------------------------------------
%%% È¡µÃ¸ÃÈºÖÐ·ÇºÃÓÑÔÚÏß³ÉÔ±µÄJID
%%% QUS string()
%%% ·µ»Ø [jid]
get_online_jid_bygroupfrom(QUS, Qid) ->
  XQUS = ejabberd_odbc:escape(QUS),
  XQid = ejabberd_odbc:escape(Qid),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select concat(gs.us, '/', gs.res) usr 
from im_groupemployee a, global_session gs
where a.groupid='", XQid, "' and a.employeeid<>'", XQUS, "'
  and gs.us=a.employeeid
  and not exists (select 1 from rosterusers b where b.username='", XQUS, "' and b.jid=a.employeeid and b.subscription='B')"]),
  ARows = extract_resultrows(Ars),
  Are = [jlib:string_to_jid(element(1, A))
         || A <- ARows],
  Are.

%%%----------------------------------------------------------------------
%%% È¡µÃ¸Ã²¿ÃÅÖÐ·ÇºÃÓÑÔÚÏß³ÉÔ±µÄJID
%%% QUS string()
%%% ·µ»Ø [jid]
get_online_jid_bydeptfrom(QUS, Qid) ->
  XQUS = ejabberd_odbc:escape(QUS),
  XQid = ejabberd_odbc:escape(Qid),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select concat(gs.us, '/', gs.res) usr 
from im_employee a, global_session gs
where a.deptid='", XQid, "' and a.loginname<>'", XQUS, "'
  and gs.us=a.loginname
  and not exists (select 1 from rosterusers b where b.username='", XQUS, "' and b.jid=a.loginname and b.subscription='B')"]),
  ARows = extract_resultrows(Ars),
  Are = [jlib:string_to_jid(element(1, A))
         || A <- ARows],
  Are.
%%%----------------------------------------------------------------------
%%% È¡µÃ¸ÃÆóÒµÖÐ·ÇºÃÓÑÔÚÏß³ÉÔ±µÄJID
%%% QUS string()
%%% ·µ»Ø [jid]  
get_online_jid_byenterprisefrom(QUS, Qid)->
  XQUS = ejabberd_odbc:escape(QUS),
  XQid = ["-",ejabberd_odbc:escape(Qid),"@fafacn.com"], %%jid:10004-100082@fafacn.com
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select concat(gs.us, '/', gs.res) usr 
from  global_session gs
where gs.us like '%", XQid, "' and gs.us<>'", XQUS, "'
  and not exists (select 1 from rosterusers b where b.username='", XQUS, "' and b.jid=gs.us and b.subscription='B')"]),
  ARows = extract_resultrows(Ars),
  Are = [jlib:string_to_jid(element(1, A))
         || A <- ARows],
  Are.

%%%----------------------------------------------------------------------
%%% ÉèÖÃpushÓÃ»§µÄÉè±¸TOKEN
%%% Qdevtoken string()
set_pushtoken(QUS, QRES, Qdevtoken)-> 
  XQUS = ejabberd_odbc:escape(QUS),
  XQRES = ejabberd_odbc:escape(QRES),
  XQdevtoken = ejabberd_odbc:escape(Qdevtoken), 
  Vals = [XQUS, 
          XQRES, 
          XQdevtoken
          ],
  Asqls = [
           ["delete from im_push where us='", XQUS, "' "],
           ["delete from im_push where devtoken='", XQdevtoken, "' "],
           ["insert into im_push(us, res, devtoken, apply_push_date) values ('", odbc_queries:join(Vals, "', '"), "', CURRENT_TIMESTAMP())"]
         ],
  sql_transaction(Asqls).

%%%----------------------------------------------------------------------
%%% È¡µÃpushÓÃ»§µÄÉè±¸TOKEN
%%% [element]
get_pushtoken(QUS, QRES)->
  XQUS = ejabberd_odbc:escape(QUS),
  XQRES = ejabberd_odbc:escape(QRES),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select a.us, a.res, a.devtoken, a.apply_push_date, (select count(*) from spool b where b.username=a.us) offlinemsgcount "
       "from im_push a "
       "where a.us='", XQUS, "' and a.res='", XQRES, "'"]),
  ARows = extract_resultrows(Ars),
  ARows.

%%%----------------------------------------------------------------------
%%% ÉèÖÃÀëÏßÎÄ¼þ
%%% Qoffline_file #offline_file{}
set_offline_file(Qoffline_file) ->
  {_ADate, {AHour, AMin, ASec}} = erlang:localtime(),
  Xfileid = ejabberd_odbc:escape(Qoffline_file#offline_file.fileid),
  Xsendto = ejabberd_odbc:escape(Qoffline_file#offline_file.sendto),
  Vals = [Xfileid, 
          ejabberd_odbc:escape(Qoffline_file#offline_file.filename), 
          ejabberd_odbc:escape(Qoffline_file#offline_file.from), 
          Xsendto,
          io_lib:format("~p-~p-~p ~p:~p:~p", [element(1, Qoffline_file#offline_file.lastdate), element(2, Qoffline_file#offline_file.lastdate), element(3, Qoffline_file#offline_file.lastdate), AHour, AMin, ASec])
          ],
  Asqls = [
           ["delete from im_offline_file where fileid='", Xfileid, "' and sendto='", Xsendto, "'"],
           ["insert into im_offline_file(fileid, filename, sendfrom, sendto, lastdate) values ('", odbc_queries:join(Vals, "', '"), "')"]
         ],
  sql_transaction(Asqls).

%%%----------------------------------------------------------------------
%%% É¾³ýÀëÏßÎÄ¼þ
del_offline_file(Qfileid, Qsendto) ->
  Xfileid = ejabberd_odbc:escape(Qfileid),
  Xsendto = ejabberd_odbc:escape(Qsendto),
  Asqls = [
           ["delete from im_offline_file where fileid='", Xfileid, "' and sendto='", Xsendto, "'"]
         ],
  sql_transaction(Asqls).
  
%%%----------------------------------------------------------------------
%%% È¡³ö´ý½ÓÊÕµÄÀëÏßÎÄ¼þ
%%% ·µ»Ø [#offline_file{}]
get_offline_file_bysendto(Qsendto,Level) ->
  Xsendto = ejabberd_odbc:escape(Qsendto),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select fileid, filename, sendfrom, sendto, lastdate "
       "from im_offline_file "
       "where sendto='", Xsendto, "'", case Level of []->""; _-> " and savelevel='"++Level++"'" end]),
  ARows = extract_resultrows(Ars),
  Are = [#offline_file{fileid = element(1, A), filename = element(2, A), from = element(3, A),
                       sendto = element(4, A), lastdate = list_to_date(element(5, A))} 
         || A <- ARows],
  Are.
  
%%%----------------------------------------------------------------------
%%% È¡³öÖ¸¶¨ÎÄ¼þ
%%% ·µ»Ø [#lib_files{}]
get_lib_file(Qfileid) ->
  XQfileid = ejabberd_odbc:escape(Qfileid),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select fileid, filepath, ifnull(filedesc, '') filedesc, addstaff, savelevel, lastdate "
       "from im_lib_files "
       "where fileid='", XQfileid, "'"]),
  ARows = extract_resultrows(Ars),
  Are = [#lib_files{fileid = element(1, A), filepath = element(2, A), filedesc = element(3, A),
                       addstaff = element(4, A), savelevel = element(5, A), lastdate = list_to_date(element(6, A))} 
         || A <- ARows],
  Are.
  
%%%----------------------------------------------------------------------
%%% È¡³ö²»ÔÙÊ¹ÓÃµÄÖ¸¶¨ÎÄ¼þ
%%% ·µ»Ø [#lib_files{}]
get_lib_file_offlinefile(Qfileid) ->
  XQfileid = ejabberd_odbc:escape(Qfileid),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select fileid, filepath, ifnull(filedesc, '') filedesc, addstaff, savelevel, lastdate "
       "from im_lib_files "
       "where fileid='", XQfileid, "' and savelevel='1' "
       "  and not exists(select 1 from im_offline_file where im_offline_file.fileid='", XQfileid, "')"]),
  ARows = extract_resultrows(Ars),
  Are = [#lib_files{fileid = element(1, A), filepath = element(2, A), filedesc = element(3, A),
                       addstaff = element(4, A), savelevel = element(5, A), lastdate = list_to_date(element(6, A))} 
         || A <- ARows],
  Are.
%%%----------------------------------------------------------------------
%%% È¡³ö²»ÔÙÊ¹ÓÃµÄÖ¸¶¨ÎÄ¼þ
%%% ·µ»Ø [#lib_files{}]
get_lib_file_sharefile(Qfileid) ->
  XQfileid = ejabberd_odbc:escape(Qfileid),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select fileid, filepath, ifnull(filedesc, '') filedesc, addstaff, savelevel, lastdate "
       "from im_lib_files "
       "where fileid='", XQfileid, "' and savelevel='2' "
       "  and not exists(select 1 from im_groupshare_file where im_groupshare_file.fileid='", XQfileid, "')"]),
  ARows = extract_resultrows(Ars),
  Are = [#lib_files{fileid = element(1, A), filepath = element(2, A), filedesc = element(3, A),
                       addstaff = element(4, A), savelevel = element(5, A), lastdate = list_to_date(element(6, A))} 
         || A <- ARows],
  Are.
%%%----------------------------------------------------------------------
%%% É¾³ýÖ¸¶¨ÎÄ¼þ
del_lib_file(Qfileid) ->
  XQfileid = ejabberd_odbc:escape(Qfileid),
  Asqls = [
           ["delete a from im_lib_files a where a.fileid='", XQfileid, "' "]
         ],
  sql_transaction(Asqls). 

%%%----------------------------------------------------------------------
%%% Ôö¼ÓÎÄ¼þ
%%% Qlib_files #lib_files{}
ins_lib_files(Qlib_files) ->
  {_ADate, {AHour, AMin, ASec}} = erlang:localtime(),
  Vals = [ejabberd_odbc:escape(Qlib_files#lib_files.fileid), 
          ejabberd_odbc:escape(Qlib_files#lib_files.filepath), 
          ejabberd_odbc:escape(case Qlib_files#lib_files.filedesc of undefined -> ""; _ -> Qlib_files#lib_files.filedesc end), 
          ejabberd_odbc:escape(Qlib_files#lib_files.addstaff), 
          ejabberd_odbc:escape(Qlib_files#lib_files.savelevel), 
          io_lib:format("~p-~p-~p ~p:~p:~p", [element(1, Qlib_files#lib_files.lastdate), element(2, Qlib_files#lib_files.lastdate), element(3, Qlib_files#lib_files.lastdate), AHour, AMin, ASec])
          ],
  Asqls = [
           ["insert into im_lib_files(fileid, filepath, filedesc, addstaff, savelevel, lastdate) "
            "  values('", odbc_queries:join(Vals, "', '"), "')"]
         ],
  sql_transaction(Asqls).
  
%%%----------------------------------------------------------------------
%%% È¡³ö³¬¹ý7ÌìµÄÁÙÊ±ÎÄ¼þ
%%% TopNum integer ×îÇ°Ãæ TopNum Ìõ¼ÇÂ¼
%%% ·µ»Ø [{fileid, filepath}]
get_lib_fileout7(TopNum) ->
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select a.fileid, a.filepath "
       "from im_lib_files a "
       "where a.savelevel='0' and a.lastdate < date_add(curdate(), interval -7 day) "
       "limit 0, ", integer_to_list(TopNum)]),
  ARows = extract_resultrows(Ars),
  Are = ARows,
  Are.
  
%%%----------------------------------------------------------------------
%%% È¡³ö³¬¹ý7ÌìµÄÀëÏßÎÄ¼þ
%%% ·µ»Ø [{fileid, filepath}]
get_lib_file_offlineout7() ->
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select a.fileid, a.filepath "
       "from im_lib_files a, im_offline_file b "
       "where a.fileid=b.fileid and b.lastdate < date_add(curdate(), interval -7 day) "]),
  ARows = extract_resultrows(Ars),
  Are = ARows,
  Are.

%%%----------------------------------------------------------------------
%%% É¾³ý³¬¹ý7ÌìµÄÀëÏßÎÄ¼þ
del_lib_file_offlineout7() ->
  Asqls = [
           ["delete a from im_lib_files a, im_offline_file b where a.fileid=b.fileid and b.lastdate < date_add(curdate(), interval -7 day) "],
           ["delete from im_offline_file where lastdate < date_add(curdate(), interval -7 day) "]
         ],
  sql_transaction(Asqls).  
%%%----------------------------------------------------------------------
%%% Éú³É´æ´¢lib_filesµÄSQL
%%% Qlib_files #lib_files{}
%%% ·µ»Ø array of sql string()
update_lib_files_sql(Qlib_files) -> 
  {_ADate, {AHour, AMin, ASec}} = erlang:localtime(),
  Asqls = [
            ["update im_lib_files set "
             "  filepath='", ejabberd_odbc:escape(Qlib_files#lib_files.filepath), "', "
             "  filedesc='", ejabberd_odbc:escape(case Qlib_files#lib_files.filedesc of undefined -> ""; _ -> Qlib_files#lib_files.filedesc end), "', "
             "  addstaff='", ejabberd_odbc:escape(Qlib_files#lib_files.addstaff), "', "
             "  savelevel='", ejabberd_odbc:escape(Qlib_files#lib_files.savelevel), "', "
             "  lastdate='", io_lib:format("~p-~p-~p ~p:~p:~p", [element(1, Qlib_files#lib_files.lastdate), element(2, Qlib_files#lib_files.lastdate), element(3, Qlib_files#lib_files.lastdate), AHour, AMin, ASec]), "' "
             "where fileid='", ejabberd_odbc:escape(Qlib_files#lib_files.fileid), "' "]
          ],
  Asqls.
  
%%%----------------------------------------------------------------------
%%% È¡³öÖ¸¶¨Àà±ðµÄÔÚÏßÁ÷·þÎñÆ÷½Úµã
%%% ·µ»Ø [{nodename, serv_type, port, extern_ip, extern_port}]
get_online_cluster_node_media(Qtype) ->
  XQtype = ejabberd_odbc:escape(Qtype),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select a.nodename, a.serv_type, a.port, a.extern_ip, a.extern_port 
from cluster_node_media a, cluster_node b 
where a.nodename=b.nodename and b.isstart='1' and a.serv_type='", XQtype, "' "]),
  ARows = extract_resultrows(Ars),
  Are = ARows,
  Are.
  
%%%----------------------------------------------------------------------
%%% È¡³öÖ¸¶¨ÈºµÄÔÚÏßÁ÷·þÎñÆ÷½Úµã
%%% ·µ»Ø [{nodename, serv_type, port, extern_ip, extern_port, groupid, rawtype}]
get_online_cluster_node_media_group(Qgroupid) ->
  XQgroupid = ejabberd_odbc:escape(Qgroupid),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select a.nodename, a.serv_type, a.port, a.extern_ip, a.extern_port, c.groupid, c.rawtype 
from cluster_node_media a, cluster_node b, im_group_raw_swap c 
where a.nodename=b.nodename and b.isstart='1' 
  and a.nodename=c.nodename and a.serv_type='udp' and c.groupid='", XQgroupid, "' "]),
  ARows = extract_resultrows(Ars),
  Are = ARows,
  Are.
  

get_microaccount_message(Qmicroaccount,Qusr,QLastid)->
  XQgroupid = ejabberd_odbc:escape(Qmicroaccount),
  Asqls=case QLastid of
  	[]->
      	A = ejabberd_odbc:sql_query("",
           ["select ifnull(lastreadid,0) from im_microaccount_memebr where microaccount='"++XQgroupid++"' and employeeid='"++Qusr++"'"]
        ),
  		Rows = extract_resultrows(A),
  		Id = case Rows of []-> "0";_->(element(1,hd(Rows))) end,
   		ejabberd_odbc:sql_query("",
           ["select msg,created from im_microaccount_msg where microaccount='", XQgroupid, "' and id>",Id," order by id desc limit 10"]
        );
    _->
      	A = ejabberd_odbc:sql_query("",
           ["select id from im_microaccount_msg where msgid='"++QLastid++"'"]
        ),
  		Rows = extract_resultrows(A),
  		Id = case Rows of []-> "0";_->(element(1,hd(Rows))) end,
  		ejabberd_odbc:sql_query("",
           ["select msg,created from im_microaccount_msg where microaccount='", XQgroupid, "' and id<",Id," order by id desc limit 10"]
        )
	end,
  ARows = extract_resultrows(Asqls),
  ARows
.

set_microaccount_message(Qmicroaccount,Qusr,MsgXml,QMsgid)->
	XMsg = ejabberd_odbc:escape(xml:element_to_binary(MsgXml)),
	Asqls = [
	           ["insert into im_microaccount_msg (microaccount,msg,created,us,msgid)values('",Qmicroaccount,"','",XMsg,"',now(),'",Qusr,"','",QMsgid,"')"]
	        ],
	sql_transaction(Asqls)	
.

set_microaccount_lastreadid(Qmicroaccount,Qusr)->
  	XQgroupid = ejabberd_odbc:escape(Qmicroaccount),
  	case is_list(Qusr) of 
  	true->
  		Where = "''"++lists:append([",'"++Jid++"'"||Jid<-Qusr]), %%拼接In条件值
  		Asqls = [
	           ["update im_microaccount_memebr set lastreadid=(select max(id) from im_microaccount_msg where microaccount='", XQgroupid, "') where microaccount='", XQgroupid, "' and employeeid in (",Where,")"]
	         ],
	  sql_transaction(Asqls);
  	_->
	  XQusr = ejabberd_odbc:escape(Qusr),
	  Asqls = [
	           ["update im_microaccount_memebr set lastreadid=(select max(id) from im_microaccount_msg where microaccount='", XQgroupid, "') where microaccount='", XQgroupid, "' and employeeid='",XQusr,"'"]
	         ],
	  sql_transaction(Asqls)
	end
.

get_unread_microaccounts(Qusr)->
  XQusr = ejabberd_odbc:escape(Qusr),
  Asqls = ejabberd_odbc:sql_query("",
           ["SELECT microaccount FROM im_microaccount_memebr where employeeid='",XQusr,"'"]
         ),
  ARows = extract_resultrows(Asqls),
  [element(1,Item) || Item<-ARows]
.



%%%----------------------------------------------------------------------
%%% È¡³öÖ¸¶¨ÈºµÄÕýÔÚÊ¹ÓÃµÄÁ÷·þÎñÆ÷
%%% ·µ»Ø [{groupid, rawtype, nodename}]
get_group_raw_swap(Qgroupid) ->
  XQgroupid = ejabberd_odbc:escape(Qgroupid),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select groupid, rawtype, nodename
from im_group_raw_swap 
where groupid='", XQgroupid, "' "]),
  ARows = extract_resultrows(Ars),
  Are = ARows,
  Are.
  
%%%----------------------------------------------------------------------
%%% ÉèÖÃÕýÔÚÊ¹ÓÃµÄÁ÷·þÎñÆ÷
ins_group_raw_swap(Qgroupid, Qrawtype, QNode) ->  
  XQgroupid = ejabberd_odbc:escape(Qgroupid), 
  XQrawtype = ejabberd_odbc:escape(Qrawtype), 
  XQNode = ejabberd_odbc:escape(QNode), 
  Asqls = [
           ["delete from im_group_raw_swap where groupid='", XQgroupid, "'"],
           ["insert into im_group_raw_swap(groupid, rawtype, nodename) values ('", XQgroupid, "', '", XQrawtype, "', '", XQNode, "')"]
         ],
  sql_transaction(Asqls).
  
%%%----------------------------------------------------------------------
%%% ÉèÖÃÕýÔÚÊ¹ÓÃµÄÁ÷·þÎñÆ÷
update_group_raw_swap(Qgroupid, Qrawtype, QNode) ->  
  XQgroupid = ejabberd_odbc:escape(Qgroupid), 
  XQrawtype = ejabberd_odbc:escape(Qrawtype), 
  XQNode = ejabberd_odbc:escape(QNode), 
  Asqls = [
           ["update im_group_raw_swap set rawtype='", XQrawtype, "', nodename='", XQNode, "' where groupid='", XQgroupid, "' "]
         ],
  sql_transaction(Asqls).

update_group_lastdate(Qgroupid)->
  XQgroupid = ejabberd_odbc:escape(Qgroupid),
  Asqls = [
           ["update im_group set last_date=now() where groupid='", XQgroupid, "' "]
         ],
  sql_transaction(Asqls)
.

get_unread_groups(Qusr)->
  XQusr = ejabberd_odbc:escape(Qusr),
  Asqls = ejabberd_odbc:sql_query("",
           ["SELECT groupid FROM im_groupemployee where employeeid='",XQusr,"'"]
         ),
  ARows = extract_resultrows(Asqls),
  ARows
.

get_group_message(Qgroupid,Qusr,QLastid)->
  XQgroupid = ejabberd_odbc:escape(Qgroupid),
  Asqls=case QLastid of
  	[]->
      	A = ejabberd_odbc:sql_query("",
           ["select ifnull(lastreadid,0) from im_groupemployee where groupid='"++XQgroupid++"' and employeeid='"++Qusr++"'"]
        ),
  		Rows = extract_resultrows(A),
  		Id = case Rows of []-> "0";_->(element(1,hd(Rows))) end,  	
   		ejabberd_odbc:sql_query("",
           ["select msg,created from im_group_msg where groupid='", XQgroupid, "' and id>",Id," order by id desc limit 30"]
        );
    _->
      	A = ejabberd_odbc:sql_query("",
           ["select id from im_group_msg where msgid='"++QLastid++"'"]
        ),
  		Rows = extract_resultrows(A),
  		Id = case Rows of []-> "0";_->(element(1,hd(Rows))) end,
  		ejabberd_odbc:sql_query("",
           ["select msg,created from im_group_msg where groupid='", XQgroupid, "' and id<",Id," order by id desc limit 30"]
        )
	end,
  ARows = extract_resultrows(Asqls),
  ARows
.
del_group_message(Msgid)->
  Asqls = [
           ["delete from im_group_msg where msgid='", Msgid,"'"]
         ],
  sql_transaction(Asqls)
.
set_group_lastreadid(Qgroupid,Qusr)->
  	XQgroupid = ejabberd_odbc:escape(Qgroupid),
  	case is_list(Qusr) of 
  	true->
  		Where = "''"++lists:append([",'"++Jid++"'"||Jid<-Qusr]), %%拼接In条件值
  		Asqls = [
	           ["update im_groupemployee set lastreadid=(select max(id) from im_group_msg where groupid='", XQgroupid, "') where groupid='", XQgroupid, "' and employeeid in (",Where,")"]
	         ],
	  sql_transaction(Asqls);
  	_->
	  XQusr = ejabberd_odbc:escape(Qusr),
	  Asqls = [
	           ["update im_groupemployee set lastreadid=(select max(id) from im_group_msg where groupid='", XQgroupid, "') where groupid='", XQgroupid, "' and employeeid='",XQusr,"'"]
	         ],
	  sql_transaction(Asqls)
	end
.
save_group_msg(Qgroupid,QSender,MsgXml,Msgid)->
	XQgroupid = ejabberd_odbc:escape(Qgroupid),
	XMsg = ejabberd_odbc:escape(xml:element_to_binary(MsgXml)),
	  Asqls = [
	           ["insert im_group_msg (groupid,msg,created,us,msgid)values('",XQgroupid,"','",XMsg,"',now(),'",QSender,"','",Msgid,"')"]
	         ],
	  sql_transaction(Asqls)	
.

%%%----------------------------------------------------------------------
%%% É¾³ýÕýÔÚÊ¹ÓÃµÄÁ÷·þÎñÆ÷
del_group_raw_swap(Qgroupid) ->  
  XQgroupid = ejabberd_odbc:escape(Qgroupid), 
  Asqls = [
           ["delete from im_group_raw_swap where groupid='", XQgroupid, "'"]
         ],
  sql_transaction(Asqls).

%%%----------------------------------------------------------------------
%%% É¾³ýÁ÷·þÎñÆ÷
del_group_raw_swap_bynode(QNode) ->  
  XQNode = ejabberd_odbc:escape(QNode), 
  Asqls = [
           ["delete from im_group_raw_swap where nodename='", XQNode, "'"]
         ],
  sql_transaction(Asqls).
  
%%%----------------------------------------------------------------------
%%% È¡³öÖ¸¶¨ÈËÕýÔÚÊ¹ÓÃµÄ´úÀí·þÎñÆ÷
%%% ·µ»Ø [{usr, nodename}]
get_fileproxy_node(Qusr) ->
  XQusr = ejabberd_odbc:escape(Qusr), 
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select usr, nodename
from im_fileproxy_node 
where usr='", XQusr, "' "]),
  ARows = extract_resultrows(Ars),
  Are = ARows,
  Are.
  
%%%----------------------------------------------------------------------
%%% ÉèÖÃÕýÔÚÊ¹ÓÃµÄ´úÀí·þÎñÆ÷
ins_fileproxy_node(Qusr, QNode) ->  
  XQusr = ejabberd_odbc:escape(Qusr), 
  XQNode = ejabberd_odbc:escape(QNode), 
  Asqls = [
           ["delete from im_fileproxy_node where usr='", XQusr, "'"],
           ["insert into im_fileproxy_node(usr, nodename) values ('", XQusr, "', '", XQNode, "')"]
         ],
  sql_transaction(Asqls).
  
%%%--------------------------------------------------------------------
%%% syscode²Ù×÷
%%%-------------------------------------------------------------------- 
ins_syscode(CodeId,Cdesc,Ctype) -> 
   AInsSql=[["delete from im_syscode where code='",ejabberd_odbc:escape(CodeId),"'"],["insert into im_syscode(code,codedesc,codetype)values('",ejabberd_odbc:escape(CodeId),"','",ejabberd_odbc:escape(Cdesc),"','",ejabberd_odbc:escape(Ctype),"')"]],
   sql_transaction(AInsSql)   
.
get_syscode_by_type(Type)->
  XQtype = ejabberd_odbc:escape(Type),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select code,codedesc,codetype "
       "from im_syscode "
       "where codetype='", XQtype, "'"]),
  ARows = extract_resultrows(Ars),
  Are = [#syscode{code = element(1, A), desc = element(2, A), codetype = element(3, A)} 
         || A <- ARows],
  Are.
get_syscode_by_code(Code)->
  XQcode = ejabberd_odbc:escape(Code),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select code,codedesc,codetype "
       "from im_syscode "
       "where code='", XQcode, "'"]),
  ARows = extract_resultrows(Ars),
  Are = [#syscode{code = element(1, A), desc = element(2, A), codetype = element(3, A)} 
         || A <- ARows],
  Are 
.
%%%--------------------------------------------------------------------
%%% Ô±¹¤±í²Ù×÷
%%%-------------------------------------------------------------------- 
get_emp_by_account(Acc)->
  XQcondition = ejabberd_odbc:escape(Acc),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select employeeid,deptid,loginname,password,employeename,ifnull(photo,'') photo,ifnull(spell,'') spell,ifnull(p_desc,'') p_desc "
       "from im_employee "
       "where loginname='", XQcondition, "'"]),
  ARows = extract_resultrows(Ars),
  Are = [#employee{employeeid = element(1, A), deptid = element(2, A), loginname = element(3, A), password = element(4, A), employeename = element(5, A),photo=element(6, A),spell=element(7, A),p_desc=element(8, A)} 
         || A <- ARows],
  hd(Are)   
.
get_emp_by_id(Acc)->
  XQcondition = ejabberd_odbc:escape(Acc),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select employeeid,deptid,loginname,password,employeename,ifnull(photo,'') photo,ifnull(spell,'') spell,ifnull(p_desc,'') p_desc "
       "from im_employee "
       "where employeeid='", XQcondition, "'"]),
  ARows = extract_resultrows(Ars),
  Are = [#employee{employeeid = element(1, A), deptid = element(2, A), loginname = element(3, A), password = element(4, A), employeename = element(5, A),photo=element(6, A),spell=element(7, A),p_desc=element(8, A)} 
         || A <- ARows],
  Are   
.
get_emp_by_dept(Acc)->
  XQcondition = ejabberd_odbc:escape(Acc),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select employeeid,deptid,loginname,password,employeename,ifnull(photo,'') photo,ifnull(spell,'') spell,ifnull(p_desc,'') p_desc "
       "from im_employee "
       "where deptid='", XQcondition, "'"]),
  ARows = extract_resultrows(Ars),
  Are = [#employee{employeeid = element(1, A), deptid = element(2, A), loginname = element(3, A), password = element(4, A), employeename = element(5, A),photo=element(6, A),spell=element(7, A),p_desc=element(8, A)} 
         || A <- ARows],
  Are   
.
%%---------------------------------------
%%»ñÈ¡Ö¸¶¨ÆóÒµÓòÏÂµÄËùÓÐÈËÔ±
%%---------------------------------------
get_emp_by_server(Server)->
  XQcondition = ejabberd_odbc:escape(Server),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select employeeid,deptid,loginname,password,employeename,ifnull(photo,'') photo,ifnull(spell,'') spell,ifnull(p_desc,'') p_desc "
       "from im_employee "
       "where loginname like '%@", XQcondition, "'"]),
  ARows = extract_resultrows(Ars),
  Are = [#employee{employeeid = element(1, A), deptid = element(2, A), loginname = element(3, A), password = element(4, A), employeename = element(5, A),photo=element(6, A),spell=element(7, A),p_desc=element(8, A)} 
         || A <- ARows],
  Are   
.
ins_emp(Empid,Employeename,DeptID,LoginName,Password)->
   %%»ñÈ¡jidÕÊºÅµÄ·þÎñÓò
   {_U,S}=func_utils:jid(LoginName),
   AInsSql=[
              ["delete from im_employee where employeeid='",ejabberd_odbc:escape(Empid),"'"],
              ["delete from im_employee_version where employeeid like '%@",S,"'"],
              ["insert into im_employee(employeeid,Employeename,DeptID,LoginName,Password)values('",ejabberd_odbc:escape(Empid),"','",ejabberd_odbc:escape(Employeename),"','",ejabberd_odbc:escape(DeptID),"','",ejabberd_odbc:escape(LoginName),"','",ejabberd_odbc:escape(Password),"')"]
           ],
   sql_transaction(AInsSql)
.
update_emp(Employeename,DeptID,LoginName,Password)->
   %%»ñÈ¡jidÕÊºÅµÄ·þÎñÓò
   {_U,S}=func_utils:jid(LoginName),
   AInsSql=[
              ["delete from im_employee_version where employeeid like '%@",S,"'"],
              ["update im_employee set Employeename='",ejabberd_odbc:escape(Employeename),"',DeptID='",ejabberd_odbc:escape(DeptID),"',Password='",ejabberd_odbc:escape(Password),"' where employeeid='",ejabberd_odbc:escape(LoginName),"'"]
           ],
   sql_transaction(AInsSql)
.
del_emp(Empid)->
   Tmp =ejabberd_odbc:escape(Empid),
   %%»ñÈ¡jidÕÊºÅµÄ·þÎñÓò
   {_U,S}=func_utils:jid(Empid),
   AInsSql=[
              ["delete from im_employee where employeeid='",Tmp,"'"],
              ["delete from im_employee_version where employeeid like '%@",S,"'"]
           ],
   sql_transaction(AInsSql)
.
update_emp_pass(LoginName,Password)->
   AInsSql=[
              ["update im_employee set Password='",ejabberd_odbc:escape(Password),"' where employeeid='",ejabberd_odbc:escape(LoginName),"'"]
           ],
   sql_transaction(AInsSql)
.
%%»ñÈ¡Ö¸¶¨ÕÊºÅËùÔÚ²¿ÃÅµÄÂ·¾¶
get_dept_path_byjid(Jid)->
   Ars=ejabberd_odbc:sql_query("",
              ["SELECT path FROM im_employee a,im_base_dept b where a.deptid=b.deptid and loginname='",Jid,"' and b.path like '/-10000/v%' "]
           ),
  ARows = extract_resultrows(Ars),
  [element(1, A) || A <- ARows]
.
%%---------------------------
%% »ñÈ¡Ö¸¶¨ÕÊºÅµÄºÃÓÑ·Ö×éÁÐ±í
%%---------------------------
get_friend_group(Acc)->
  XQcode = ejabberd_odbc:escape(Acc),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select empg,loginname,groupname "
       "from im_friendgroups "
       "where loginname='", XQcode, "'"]),
  ARows = extract_resultrows(Ars),
  Are = [#friendgroups{empg = element(1, A), loginname = element(2, A), groupname = element(3, A)} 
         || A <- ARows],
  Are    
.

ins_friend_group(Acc,Gname)->
   AInsSql=[
              ["delete from im_friendgroups where loginname='",ejabberd_odbc:escape(Acc),"' and groupname='",ejabberd_odbc:escape(Gname),"'"],
              ["insert into im_friendgroups (empg,loginname,groupname)values('",get_seq_nextvalue("im_friendgroups","empg"),"','",ejabberd_odbc:escape(Acc),"','",ejabberd_odbc:escape(Gname),"')"]
           ],
   sql_transaction(AInsSql)    
.
del_friend_group(Acc,Gname)->
   AInsSql=[
              ["delete from im_friendgroups where loginname='",ejabberd_odbc:escape(Acc),"' and groupname='",ejabberd_odbc:escape(Gname),"'"]
           ],
   sql_transaction(AInsSql)    
.
update_friend_group(Acc,OldName,Gname)->
   AInsSql=[
              ["update im_friendgroups set groupname='",ejabberd_odbc:escape(Gname),"' where loginname='",ejabberd_odbc:escape(Acc),"' and groupname='",ejabberd_odbc:escape(OldName),"'"]
           ],
   sql_transaction(AInsSql)    
.
%%-----------------------------------
%% É¾³ýÖ¸¶¨ÆóÒµÓòÏÂµÄËùÓÐÈËÔ±µÄ¸Ã·Ö×é
%% Èçµ±¹ÜÀíÔ±É¾³ýÒ»¸ö¹²Ïí·Ö×é
%%----------------------------------
del_all_group(Server,Gname)->
   AInsSql=[
              ["delete from im_friendgroups where loginname like '%@",ejabberd_odbc:escape(Server),"' and groupname='",ejabberd_odbc:escape(Gname),"'"]
           ],
   sql_transaction(AInsSql)    
.

get_role(Acc)->
  XQcode = ejabberd_odbc:escape(Acc),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select employeeid,roleid "
       "from im_employeerole "
       "where employeeid='", XQcode, "'"]),
  ARows = extract_resultrows(Ars),
  Are = [#employeerole{employeeid = element(1, A), roleid = element(2, A)} 
         || A <- ARows],
  Are
.
ins_role(Acc,Role)->
   AInsSql=[
              ["insert into im_employeerole (employeeid,roleid)values('",ejabberd_odbc:escape(Acc),"','",ejabberd_odbc:escape(Role),"')"]
           ],
   sql_transaction(AInsSql)    
.
del_role(Acc,Role)->
   AInsSql=[
              ["delete from im_employeerole where employeeid='",ejabberd_odbc:escape(Acc),"' and roleid='",ejabberd_odbc:escape(Role),"'"]
           ],
   sql_transaction(AInsSql)    
.

get_dept_empstat_count(Dept)->
  XQcode = ejabberd_odbc:escape(Dept),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select deptid,empcount "
       "from im_dept_stat "
       "where deptid='", XQcode, "'"]),
  ARows = extract_resultrows(Ars),
  Are = [#dept_stat{deptid = element(1, A), empcount = element(2, A)} 
         || A <- ARows],
  Are
.
save_dept_emp_count(Dept,Cnt)->
   AInsSql=[
              ["insert into im_dept_stat (deptid,empcount)values('",ejabberd_odbc:escape(Dept),"',",ejabberd_odbc:escape(Cnt),")"]
           ],
   sql_transaction(AInsSql)
.

get_enterprise()->
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select eno,ifnull(mail, '') mail,subdomain,admin,ifnull(fullname, '') fullname,ifnull(name, '') name "
       "from im_enterprise_reg "]),
  ARows = extract_resultrows(Ars),
  Are = [#enterprise_reg{eno = element(1, A), mail = element(2, A), subdomain = element(3, A),admin=element(4, A), fullname = element(5, A), name = element(6, A)} 
         || A <- ARows],
  Are   
.
get_enterprise_by_admin(Acc)->
  XQcode = ejabberd_odbc:escape(Acc),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select eno,ifnull(mail, '') mail,subdomain,admin,ifnull(fullname, '') fullname,ifnull(name, '') name "
       "from im_enterprise_reg "
       "where admin='", XQcode, "'"]),
  ARows = extract_resultrows(Ars),
  Are = [#enterprise_reg{eno = element(1, A), mail = element(2, A), subdomain = element(3, A),admin=element(4, A), fullname = element(5, A), name = element(6, A)} 
         || A <- ARows],
  Are   
.
get_enterprise_by_email(Acc)->
  XQcode = ejabberd_odbc:escape(Acc),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select eno,ifnull(mail, '') mail,subdomain,admin,ifnull(fullname, '') fullname,ifnull(name, '') name "
       "from im_enterprise_reg "
       "where mail='", XQcode, "'"]),
  ARows = extract_resultrows(Ars),
  Are = [#enterprise_reg{eno = element(1, A), mail = element(2, A), subdomain = element(3, A),admin=element(4, A), fullname = element(5, A), name = element(6, A)} 
         || A <- ARows],
  Are   
.
get_enterprise_by_eno(Eno)->
  XQcode = ejabberd_odbc:escape(Eno),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select eno,ifnull(mail, '') mail,subdomain,admin,ifnull(fullname, '') fullname,ifnull(name, '') name "
       "from im_enterprise_reg "
       "where eno='", XQcode, "'"]),
  ARows = extract_resultrows(Ars),
  Are = [#enterprise_reg{eno = element(1, A), mail = element(2, A), subdomain = element(3, A),admin=element(4, A), fullname = element(5, A), name = element(6, A)} 
         || A <- ARows],
  Are   
.

save_enterprise_reg(Aeno,Amail,Asubdomain,Aadmin,Afullname,Aname)->
   AInsSql=[
              ["insert into im_enterprise_reg (eno,mail,subdomain,admin,fullname,name)values('",
                       ejabberd_odbc:escape(Aeno),"','",
                       ejabberd_odbc:escape(Amail),"','",
                       ejabberd_odbc:escape(Asubdomain),"','",
                       ejabberd_odbc:escape(Aadmin),"','",
                       ejabberd_odbc:escape(Afullname),"','",
                       ejabberd_odbc:escape(Aname),"')"]
           ],
   sql_transaction(AInsSql)
.
del_enterprise_reg(Key)->
   AInsSql=[
              ["delete from im_enterprise_reg where eno='",ejabberd_odbc:escape(Key),"'"]
           ],
   sql_transaction(AInsSql)
.
%%%-------------------
%%·µ»ØÖ¸¶¨²¿ÃÅ
get_dept_by_id(Key)->
  XQcode = ejabberd_odbc:escape(Key),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select deptid,deptname,pid,ifnull(path, '') path,noorder,ifnull(manager, '') manager,ifnull(remark, '') remark "
       "from im_base_dept "
       "where deptid='", XQcode, "'"]),
  ARows = extract_resultrows(Ars),
  Are = [#base_dept{deptid = element(1, A), deptname = element(2, A), pid = element(3, A),path=element(4, A), noorder = element(5, A), manager = element(6, A), remark = element(7, A)} 
         || A <- ARows],
  Are 
.
%%·µ»ØÖ¸¶¨²¿ÃÅ£¬²¢Í³¼Æ³ö¸Ã²¿ÃÅµÄÈËÔ±×ÜÊý
get_deptandstat_by_id(Key)->
  XQcode = ejabberd_odbc:escape(Key),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select a.deptid,deptname,pid,ifnull(path, '') path,noorder,ifnull(manager, '') manager,ifnull(b.empcount,0) empcount "
       "from im_base_dept a left join im_dept_stat b on a.deptid=b.deptid  "
       "where a.deptid='", XQcode, "'"]),
  ARows = extract_resultrows(Ars),
  Are = [#base_dept{deptid = element(1, A), deptname = element(2, A), pid = element(3, A),path=element(4, A), noorder = element(5, A), manager = element(6, A), remark = element(7, A)} 
         || A <- ARows],
  Are 
.
get_dept_emp_count(Key)->
  XQcode = ejabberd_odbc:escape(Key),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select deptid,empcount "
       "from im_dept_stat "
       "where deptid='", XQcode, "'"]),
  ARows = extract_resultrows(Ars),
  Are = [#dept_stat{deptid = element(1, A), empcount = element(2, A)} 
         || A <- ARows],
  Are
.
%%·µ»ØÖ¸¶¨²¿ÃÅµÄÏÂ¼¶²¿ÃÅ
get_dept_by_parentid(Key)->
  XQcode = ejabberd_odbc:escape(Key),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select deptid,deptname,pid,ifnull(path, '') path,noorder,ifnull(manager, '') manager,ifnull(remark, '') remark "
       "from im_base_dept "
       "where pid='", XQcode, "'",case XQcode of [$v|_] -> " union select deptid,deptname,pid,ifnull(path, '') path,noorder,ifnull(manager, '') manager,ifnull(remark, '') remark from im_base_dept where deptid='"++XQcode++"'"; _-> "" end," order by noorder"]),
  ARows = extract_resultrows(Ars),
  Are = [#base_dept{deptid = element(1, A), deptname = element(2, A), pid = element(3, A),path=element(4, A), noorder = element(5, A), manager = element(6, A), remark = element(7, A)} 
         || A <- ARows],
  Are 
.
%%·µ»ØÖ¸¶¨²¿ÃÅµÄÏÂ¼¶²¿ÃÅ£¬²¢Í³¼Æ³öÃ¿¸ö²¿ÃÅµÄÈËÔ±×ÜÊý
get_deptandstat_by_parentid(Key)->
  XQcode = ejabberd_odbc:escape(Key),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select a.deptid,deptname,pid,ifnull(path, '') path,noorder,ifnull(manager, '') manager,ifnull(b.empcount,0) empcount "
       "from im_base_dept a left join im_dept_stat b on a.deptid=b.deptid  "
       "where pid='", XQcode, "' order by noorder"]),

  ARows = extract_resultrows(Ars),
  Are = [#base_dept{deptid = element(1, A), deptname = element(2, A), pid = element(3, A),path=element(4, A), noorder = element(5, A), manager = element(6, A), remark = element(7, A)} 
         || A <- ARows],
  Are 
.
%%·µ»ØÖ¸¶¨²¿ÃÅµÄËùÓÐÏÂ¼¶²¿ÃÅ
get_all_dept_by_parentid(Key)->
  XQcode = ejabberd_odbc:escape(Key),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select deptid,deptname,pid,ifnull(path, '') path,noorder,ifnull(manager, '') manager,ifnull(remark, '') remark "
       "from im_base_dept "
       "where path like '/-10000/", XQcode, "/%' order by noorder"
      ]),
  ARows = extract_resultrows(Ars),
  Are = [#base_dept{deptid = element(1, A), deptname = element(2, A), pid = element(3, A),path=element(4, A), noorder = element(5, A), manager = element(6, A), remark = element(7, A)} 
         || A <- ARows],
  Are 
.
%%·µ»ØÖ¸¶¨²¿ÃÅµÄËùÓÐÏÂ¼¶²¿ÃÅ£¬²¢Í³¼Æ³öÃ¿¸ö²¿ÃÅµÄÈËÔ±×ÜÊý
get_all_deptandstat_by_parentid(Key)->
  XQcode = ejabberd_odbc:escape(Key),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select a.deptid, deptname,pid,ifnull(path, '') path,noorder,ifnull(manager, '') manager,ifnull(b.empcount,0) empcount  "
       "from im_base_dept a left join im_dept_stat b on a.deptid=b.deptid "
       "where  a.path like '/-10000/", XQcode, "/%'  order by noorder"
      ]),
  ARows = extract_resultrows(Ars),
  Are = [#base_dept{deptid = element(1, A), deptname = element(2, A), pid = element(3, A),path=element(4, A), noorder = element(5, A), manager = element(6, A), remark = element(7, A)} 
         || A <- ARows],
  Are 
.
ins_dept(Deptid,Deptname,Pid,Path,OrderNo,Manager,Remark)->
   %%»ñÈ¡µ±Ç°²¿ÃÅËùÔÚÆóÒµµÄ×ÓÓò
   Eno = func_utils:getOrgRootID(Pid),
   AInsSql=[
              ["delete from im_base_dept where deptid='",ejabberd_odbc:escape(Deptid),"'"],
              ["delete from im_dept_version  where us in(SELECT loginname FROM im_employee a, im_base_dept b where a.deptid=b.deptid and (b.path like '/-10000/",Eno,"%'))"],
              ["insert into im_base_dept (deptid,deptname,pid,path,noorder,manager,remark)values('",
                       ejabberd_odbc:escape(Deptid),"','",
                       ejabberd_odbc:escape(Deptname),"','",
                       ejabberd_odbc:escape(Pid),"','",
                       ejabberd_odbc:escape(Path),"/",Deptid,"',",
                       ejabberd_odbc:escape(OrderNo),",'",
                       ejabberd_odbc:escape(Manager),"','",
                       ejabberd_odbc:escape(Remark),"')"]
           ],
   sql_transaction(AInsSql)
.
del_dept(Key)->
   %%»ñÈ¡µ±Ç°²¿ÃÅËùÔÚÆóÒµµÄ×ÓÓò
   Eno = func_utils:getOrgRootIDByDeptid(Key),
   AInsSql=[
              ["delete from im_dept_version  where us in(SELECT loginname FROM im_employee a, im_base_dept b where a.deptid=b.deptid and (b.path like '/-10000/",Eno,"%'))"],
              ["delete from im_base_dept where deptid='",ejabberd_odbc:escape(Key),"'"]
           ],
   sql_transaction(AInsSql)
.
del_dept_by_parentid(Pid)->
   R = get_dept_by_id(Pid),
   case R of []->ok;
   [Rc]->
	   Path = Rc#base_dept.path,
	   Eno = case Path of [] ->"";
	   _->
       Jd = re:split(Path,"/",[{return,list}]),
       lists:nth(3,Jd)
     end,
	   AInsSql=[
	              ["delete from im_dept_version  where us in(SELECT loginname FROM im_employee a, im_base_dept b where a.deptid=b.deptid and (b.path like '/-10000/",Eno,"%'))"],
	              ["delete from im_base_dept where path like '",Path,"%'"]
	           ],
	   sql_transaction(AInsSql)
   end
.

%%%-----------------------------------------------------------
%%%
%%%                         Èº²Ù×÷
%%%
%%%-----------------------------------------------------------

get_group_by_groupid(Key)->
  XQcode = ejabberd_odbc:escape(Key),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select groupid,groupname,groupclass,ifnull(groupdesc,'') groupdesc,ifnull(grouppost,'') grouppost,creator,add_member_method,accessright,ifnull(a.logo,'') logo,ifnull(a.number,0) number,ifnull(a.max_number,0) max_number "
       "from im_group "
       "where groupid = '", XQcode, "'"]),
  ARows = extract_resultrows(Ars),
  Are = [#group{groupid = element(1, A), groupname = element(2, A), groupclass = element(3, A),groupdesc=element(4, A), grouppost = element(5, A), creator = element(6, A), add_member_method = element(7, A), accessright = element(8, A), logo = element(9, A), number = element(10, A), max_number = element(11, A)} 
         || A <- ARows],
  Are 
.
get_member_by_groupid(Key)->
  XQcode = ejabberd_odbc:escape(Key),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select a.employeeid,groupid,grouprole,ifnull(employeenote,'')employeenote,ifnull(employeenick,'')employeenick "
       "from im_groupemployee a,im_employee b"
       "where a.employeeid=b.loginname and a.groupid = '", XQcode, "' order by employeeid"]),
  ARows = extract_resultrows(Ars),
  Are = [#groupemployee{employeeid = element(1, A), groupid = element(2, A), grouprole = element(3, A),employeenote=element(4, A), employeenick = element(5, A)} 
         || A <- ARows],
  Are
.
get_group_by_employeeid(Employeeid)->
  XQcode = ejabberd_odbc:escape(Employeeid),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select a.employeeid,groupid,grouprole,ifnull(employeenote,'')employeenote,ifnull(employeenick,'')employeenick "
       "from im_groupemployee a,im_employee b"
       "where a.employeeid=b.loginname and a.employeeid = '", XQcode, "' order by groupid"]),
  ARows = extract_resultrows(Ars),
  Are = [#groupemployee{employeeid = element(1, A), groupid = element(2, A), grouprole = element(3, A),employeenote=element(4, A), employeenick = element(5, A)} 
         || A <- ARows],
  Are
.
get_grouptype()->
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select typeid,typename,pid,ifnull(remark,'')remark from im_grouptype "]),
  ARows = extract_resultrows(Ars),
  Are = [#grouptype{typeid = element(1, A), typename = element(2, A), pid = element(3, A),remark=element(4, A)} 
         || A <- ARows],
  Are 
.
create_new_group(Id,Gname,Gclass,Gdesc,Gpost,Gcreator,Gauth,Gaccess)->
   Creator = ejabberd_odbc:escape(Gcreator),
   %%»ñÈ¡´´½¨ÈËÐÕÃû
   Emp = get_emp_by_account(Creator),
   case Emp of [Rc]-> 
       Nick = Rc#employee.employeename,
		   AInsSql=[
		              ["insert into im_group (groupid,groupname,groupclass,groupdesc,grouppost,creator,add_member_method,accessright)values('",
		                       Id,"','",
		                       ejabberd_odbc:escape(Gname),"','",
		                       ejabberd_odbc:escape(Gclass),"','",
		                       ejabberd_odbc:escape(Gdesc),"','",
		                       ejabberd_odbc:escape(Gpost),"','",
		                       Creator,"','",
		                       ejabberd_odbc:escape(Gauth),"','",
		                       ejabberd_odbc:escape(Gaccess),"')"],
		              ["insert into groupemployee(employeeid,groupid,grouprole,employeenick,employeenote)values('",Creator,
		                       "','",Id,
		                       "','",?GROUP_ROLE_OWNER,
		                       "','",Nick,
		                       "','')"]
		           ],
		   sql_transaction(AInsSql);
   _->
      error
   end
.
del_broadcast_message(Id)->
     ADelSql=[
              ["delete im_broadcast where ctime='",Id,"'"]
           ],
   sql_transaction(ADelSql)
.
save_broadcast_message(Id,Type,Treceive,Tmssage,Tsendemp,State,Link,Linktext)->
   AInsSql=[
              ["insert into im_broadcast (ctime,sendemp,sendtype,receiveemp,mssage,state,url,buttons)values('",
                       Id,"','",
                       ejabberd_odbc:escape(Tsendemp),"','",
                       ejabberd_odbc:escape(Type),"','",
                       ejabberd_odbc:escape(Treceive),"','",
                       ejabberd_odbc:escape(Tmssage),"','",
                       ejabberd_odbc:escape(State),"','",
                       ejabberd_odbc:escape(Link),"','",
                       ejabberd_odbc:escape(Linktext),"')"]
           ],
   sql_transaction(AInsSql)
.
update_broadcast_state(Key,State)->
   AInsSql=[
              ["update im_broadcast set state='",State,"' where ctime='",ejabberd_odbc:escape(Key),"'"]
           ],
   sql_transaction(AInsSql)
.
update_broadcast_sendcount(Key,SendCnt)->
   AInsSql=[
              ["update im_broadcast set sendtype='",ejabberd_odbc:escape(SendCnt),"' where ctime='",ejabberd_odbc:escape(Key),"'"]
           ],
   sql_transaction(AInsSql)
.
get_broadcast_message(Key)->
  XQcode = ejabberd_odbc:escape(Key),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select ctime,sendemp,sendtype,receiveemp,mssage,state,url,buttons from im_broadcast where ctime='", XQcode, "' "]),
  ARows = extract_resultrows(Ars),
  Are = [#broadcast{ctime = element(1, A), sendemp = element(2, A), sendtype = element(3, A),receiveemp=element(4, A), mssage = element(5, A),state=element(6, A),url=element(7, A),buttons=element(8, A)} 
         || A <- ARows],
  Are 
.

get_broadcast_message_by_state(State)->
  XQcode = ejabberd_odbc:escape(State),
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select ctime,sendemp,sendtype,receiveemp,mssage,state,url,buttons from im_broadcast where state='",XQcode,"'"]),
  ARows = extract_resultrows(Ars),
  Are = [#broadcast{ctime = element(1, A), sendemp = element(2, A), sendtype = element(3, A),receiveemp=element(4, A), mssage = element(5, A),state=element(6, A),url=element(7, A),buttons=element(8, A)} 
         || A <- ARows],
  Are 
.
get_roster_relation(User,Jid)->
  Ars = ejabberd_odbc:sql_query(
      "",
      ["select nick,subscription from rosterusers where username='",User,"' and jid='",Jid,"'"]),
  ARows = extract_resultrows(Ars),
  Are = [{element(1, A),element(2, A)} || A <- ARows],
  Are   
.

add_roster_both(User,Jid,GroupName)->
   Rs = (get_emp_by_account(Jid)),
   Rs_User = (get_emp_by_account(User)),
   AInsSql=case GroupName of[]->
           [
              ["insert into rosterusers (created_at, jid, nick,subscription, type, username,ask,server,askmessage,subscribe)values(now(),'",
                Jid,"','",Rs_User#employee.employeename,"','B','item','",User,"','N','N','','')"],
              ["insert into rosterusers (created_at, jid, nick,subscription, type, username,ask,server,askmessage,subscribe)values(now(),'",
                User,"','",Rs_User#employee.employeename,"','B','item','",Jid,"','N','N','','')"]
           ];
  _->
           [
              ["insert into rosterusers (created_at, jid, nick,subscription, type, username,ask,server,askmessage,subscribe)values(now(),'",
                Jid,"','",Rs#employee.employeename,"','B','item','",User,"','N','N','','')"],
              ["insert into rosterusers (created_at, jid, nick,subscription, type, username,ask,server,askmessage,subscribe)values(now(),'",
                User,"','",Rs#employee.employeename,"','B','item','",Jid,"','N','N','','')"],
              ["insert into rostergroups(username,jid,grp)values('",User,"','",Jid,"','",ejabberd_odbc:escape(GroupName),"')"],
              ["insert into rostergroups(username,jid,grp)values('",Jid,"','",User,"','",ejabberd_odbc:escape(GroupName),"')"]
           ]
   end,
   sql_transaction(AInsSql)
.

add_roster_request(User,Jid,GroupName)->
  Rs_from = get_roster_relation(User,Jid),
  case Rs_from of 
    []->
       Rs = (get_emp_by_account(Jid)),
       AInsSql=case GroupName of []->
                [
                  ["insert into rosterusers (created_at, jid, nick,subscription, type, username,ask,server,askmessage,subscribe)values(now(),'",
                    Jid,"','",Rs#employee.employeename,"','N','item','",User,"','N','N','','')"]
                ];
              _->
                [
                  ["insert into rosterusers (created_at, jid, nick,subscription, type, username,ask,server,askmessage,subscribe)values(now(),'",
                    Jid,"','",Rs#employee.employeename,"','N','item','",User,"','N','N','','')"],
                  ["insert into rostergroups(username,jid,grp)values('",User,"','",Jid,"','",ejabberd_odbc:escape(GroupName),"')"]
                ]
            end,
       sql_transaction(AInsSql);
   _->
   ok
  end
.

add_roster_agreen(User,Jid,GroupName)->
  Rs_from = get_roster_relation(User,Jid), 
  case Rs_from of 
  []->
     Rs = (get_emp_by_account(Jid)),
     AInsSql=case GroupName of []->
              [
                ["insert into rosterusers (created_at, jid, nick,subscription, type, username,ask,askmessage,server,subscribe)values(now(),'",
                  Jid,"','",Rs#employee.employeename,"','B','item','",User,"','N','','N','')"],
                ["update rosterusers set subscription='B' where username='",Jid,"' and jid='",User,"'"]
              ];
            _->
              [
                ["insert into rosterusers (created_at, jid, nick,subscription, type, username,ask,askmessage,server,subscribe)values(now(),'",
                  Jid,"','",Rs#employee.employeename,"','B','item','",User,"','N','','N','')"],
                ["update rosterusers set subscription='B' where username='",Jid,"' and jid='",User,"'"],
                ["insert into rostergroups(username,jid,grp)values('",User,"','",Jid,"','",ejabberd_odbc:escape(GroupName),"')"]
              ]
          end,
      sql_transaction(AInsSql);
    _->
      AInsSql=[
                ["update rosterusers set subscription='B' where username='",User,"' and jid='",Jid,"'"],
                ["update rosterusers set subscription='B' where username='",Jid,"' and jid='",User,"'"]
              ],
      sql_transaction(AInsSql)      
  end
.
add_roster_delete(User,Jid)->
   AInsSql=[
              ["delete from rosterusers where username='",User,"' and jid='",Jid,"'"],
              ["delete from rosterusers where username='",Jid,"' and jid='",User,"'"],
              ["delete from rostergroups where username='",User,"' and jid='",Jid,"'"],
              ["delete from rostergroups where username='",Jid,"' and jid='",User,"'"]
            ],
   sql_transaction(AInsSql)
.
atten(User,Jid)->
   Rs = get_roster_relation(User,Jid),   
   case Rs of 
    []-> 
        RsTo = get_roster_relation(Jid,User),
        Userinfo = (get_emp_by_account(User)),
        case  RsTo of
         [] ->
           AInsSql=[
                  ["insert into rosterusers (created_at, jid, nick,subscription, type, username,ask,askmessage,server,subscribe)values(now(),'",
                    Jid,"','",Userinfo#employee.employeename,"','N','item','",User,"','N','atten','N','')"]
                ];
         _->
           AInsSql=[
                  ["insert into rosterusers (created_at, jid, nick,subscription, type, username,ask,askmessage,server,subscribe)values(now(),'",
                    Jid,"','",Userinfo#employee.employeename,"','B','item','",User,"','N','','N','')"],
                  ["update rosterusers set subscription='B',askmessage='' where username='",Jid,"' and jid='",User,"'"]
                ]
        end,
        sql_transaction(AInsSql);
    _->
     ok
  end
.
delatten(User,Jid)->   
   AInsSql=[
              ["delete from rosterusers where username='",User,"' and jid='",Jid,"'"],
              ["update rosterusers set subscription='N' where username='",Jid,"' and jid='",User,"'"]
           ],
   sql_transaction(AInsSql)    
.