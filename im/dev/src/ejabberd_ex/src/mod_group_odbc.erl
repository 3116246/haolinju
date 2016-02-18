%%%----------------------------------------------------------------------
%%% ÈºÏà¹Ø¹¦ÄÜ
%%%----------------------------------------------------------------------

%%% @doc ÈºÏà¹Ø¹¦ÄÜ (´æ´¢ÓÚodbcÊý¾Ý¿âÖÐ).
%%%
%%% mod²ÎÊý£º{versioning, true|false} Ïà¹ØÐÅÏ¢²éÑ¯ÊÇ·ñÔÊÐíÊ¹ÓÃ°æ±¾£¬Ä¬ÈÏÎªtrue
%%%----------------------------------------------------------------------
-module(mod_group_odbc).
-author('krislee').

-behaviour(gen_mod).

-export([start/2, stop/1,
         process_iq/3,
         process_local_iq/3, process_iq_get/3, process_iq_set/3,
         querygroup/3, querygroupmember/3, searchgroup/3, 
         creategroup/3, deletegroup/3, updategroup/3, invitegroupmember/3, 
         feedbackinvite/3, applygroupmember/3, feedbackapply/3,
         removegroupmember/3, setgroupmemberrole/3, setgroupmemberinfo/3,
         groupchat/3, rawrequest/3, rawquit/3, 
         querygroupsharefile/3, takesharefile/3, delsharefile/3, 
         new_group_id/0, get_employeename/1, get_groupemployee/1, get_groupemployee/2, 
         is_groupowner/2, is_groupownerormanager/2, send_msg/2, send_msg/3, is_groupmember/2, get_group/1,
         add_groupmember_notify/2, get_nextgroupowner/1,
         group_versioning_enabled/1, get_groupbyemployeeid/1,
         groupitem_to_xml/1, groupemployeeitem_to_xml/1,
         delete_groupversion/1, delete_groupemployee_version/1,
         user_offline/3,user_online/3]).

-include("../../ejabberd-2.1.11/include/ejabberd.hrl").
-include("../../ejabberd-2.1.11/include/jlib.hrl").
-include("../include/mod_group.hrl").
-include("../include/mod_ejabberdex_init.hrl").

%%%----------------------------------------------------------------------
start(Host, Opts) ->
    ejabberd_hooks:add(sm_remove_connection_hook, Host,?MODULE, user_offline, 50),
    IQDisc = gen_mod:get_opt(iqdisc, Opts, one_queue),    
    gen_iq_handler:add_iq_handler(ejabberd_sm, Host, ?NS_GROUP,
                                  ?MODULE, process_iq, IQDisc),
    ejabberd_hooks:add(sm_register_connection_hook, Host,
                               ?MODULE, user_online, 60),
    %%% Ã¿1·ÖÖÓÖ´ÐÐÒ»´Î,¼ì²éÁ÷½»»»³¬Ê±
    timer:apply_interval(60*1000, ejabberdex_c2c_odbc, process_checkcycle, []).

%%%----------------------------------------------------------------------
stop(Host) ->
    ejabberd_hooks:delete(sm_remove_connection_hook, Host,
              ?MODULE, user_offline, 50),
    ejabberd_hooks:delete(sm_register_connection_hook, Host,
                               ?MODULE, user_online, 60),
    gen_iq_handler:remove_iq_handler(ejabberd_sm, Host, ?NS_GROUP).

user_online(_SID, JID, _Info) ->
	Operator = JID#jid.luser ++ "@" ++ JID#jid.lserver,
	Groups = ejabberdex_odbc_query:get_unread_groups(Operator),
	case Groups of []-> [];
	_->
		lists:map(fun(Gp)->
			Groupid = element(1,Gp),     	 	
			Msgs=ejabberdex_odbc_query:get_group_message(Groupid,Operator,[]),

		    ejabberdex_odbc_query:set_group_lastreadid(Groupid,[Operator]),
		    lists:map(fun(Item)->
		      		{xmlelement,Name,Attrs,Text}=xml_stream:parse_element(element(1,Item)),	
		      		%%NewAtts=lists:keyreplace("sendtime",1,Attrs,{"sendtime",element(2,Item)}),
		      		[Y,M,D,Hh,Mi,Ss]=string:tokens(element(2,Item),"- :"),
		      		NowTime=hd(calendar:local_time_to_universal_time_dst({{list_to_integer(Y),list_to_integer(M),list_to_integer(D)},{list_to_integer(Hh),list_to_integer(Mi),list_to_integer(Ss)}})),
        			OfflineAttr = [
        		    	jlib:timestamp_to_xml(NowTime,utc, jlib:make_jid("", JID#jid.server, ""),"Offline Storage"),
						jlib:timestamp_to_xml(NowTime)],
		      		Ms={xmlelement,Name,Attrs,Text++OfflineAttr},
		      		ejabberd_sm:route(jlib:string_to_jid(element(2,lists:keyfind("from",1,Attrs))), JID, Ms)
		    end,Msgs) 
		end,Groups)
	end,
  []
.

%%´¦ÀíÓÃ»§ÉÏÏßÊÂ¼þ.Ö÷Òª´¦ÀíÓÃ»§ÉÏÏßÊ±µÄ·ÇÕý³£ÍË³ö»áÒéÊý¾Ý
user_offline(_Sid,Jid,_Info)->
    Account = jlib:jid_to_string(Jid),
    AllMeetings = ejabberdex_odbc_query:get_meeting_run_by_us(Account),
    case AllMeetings of []-> ok;
    _->
        %É¾³ý¶©ÔÄ
        ejabberdex_odbc_query:del_subscribe_ex(Jid),
        DelUserFromMeeting=fun(Meet)->
            MeetingId=Meet#meeting_run.meetingemp,
            case MeetingId of {Gid,Account}->
               %%´Óµ±Ç°»áÒéÊÒÖÐÒÆ³ý¸Ã³ÉÔ±
               ejabberdex_odbc_query:del_meeting_run(Gid, Account),               
               %%ÅÐ¶Ïµ±Ç°»áÒéÊÇ·ñÊÇÁÙÊ±»áÒé£¬ÊÇÔòÅÐ¶Ï»áÒé³ÉÔ±ÊÇ·ñÒÑÈ«²¿ÍË³ö£¬È«²¿ÍË³öÊ±ÔòÉ¾³ý»áÒé
               case Gid of 
                  [$d,$e,$p,$t,$m,$e,$e,$t,$i,$n,$g|_]->
                     case ejabberdex_odbc_query:get_meeting_run_by_groupid(Gid) of
                      []->deletegroup(Gid);
                      _-> ok
                     end;
                  _->
			               %%%%ÅÐ¶Ï»áÒéÊÇ·ñ´æÔÚÖ÷³ÖÈË
			               case Meet#meeting_run.emprole of "1"->
			                 %%ÅÐ¶ÏÊÇ·ñ»¹ÓÐÈËÔÚ»áÒéÖÐ,Ö¸¶¨ÕÒµ½µÄµÚÒ»¸ö»áÒéÈËÔ±ÎªÁÙÊ±Ö÷³ÖÈË
			                 case ejabberdex_odbc_query:get_meeting_run_by_groupid(Gid) of
			                     []->skip;
			                     ALgroupemployee->
			                           MeetingMember = hd(ALgroupemployee),
			                           Meetingemp = MeetingMember#meeting_run.meetingemp,
			                           Qgroupid = element(1, Meetingemp),
			                           NewHost = element(2, Meetingemp),
								                 Asqls = [
								                          ["update im_meeting_run set emprole='1',state='2' where groupid='", Qgroupid , "' and us = '",NewHost,"'"]
								                       ],
								                 ejabberdex_odbc_query:sql_transaction(Asqls),
																  %·¢ËÍÖ÷³ÖÈË±ä¸üÏûÏ¢
																  Pres = {xmlelement, "presence", [], 
																    [{xmlelement, "hostchanged", [{"xmlns", ?NS_GROUP}], 
																      [{xmlelement, "item", 
																        [{"meetingid", Qgroupid}, 
																         {"employeeid", NewHost}, 
																         {"employeename",get_employeename(NewHost)},
																         {"role", "1"},
																         {"in_date", MeetingMember#meeting_run.in_date},
																         {"state", "2"}], []}]}]},
																  [send_msg_to(Jid,Pres,element(2,ToJid#meeting_run.meetingemp)) || ToJid<- ALgroupemployee]								                 
			                 end;
			               _->
			                 skip
			               end,                     
                     ok
               end;
            _->
               ok
            end
        end,
        [DelUserFromMeeting(Meeting)||Meeting<-AllMeetings],
        ok
    end
.
%%%----------------------------------------------------------------------
%%% ´¦ÀíIQ½ÚÄÚÈÝ
process_iq(From, To, IQ) ->
    #iq{sub_el = SubEl} = IQ,
    #jid{lserver = LServer} = From,
    case lists:member(LServer, ?MYHOSTS) of
	true ->
	    process_local_iq(From, To, IQ);
	_ ->
	    IQ#iq{type = error, sub_el = [SubEl, ?ERR_ITEM_NOT_FOUND]}
    end.

process_local_iq(From, To, #iq{type = Type} = IQ) ->
    case Type of
	set ->
	    process_iq_set(From, To, IQ);
	get ->
	    process_iq_get(From, To, IQ)
    end.

%%%----------------------------------------------------------------------
%% GETÏà¹Ø¹¦ÄÜ
%% ½öµ±ÏÂÁÐÇé¿ö²Å·µ»ØÊý¾Ý
%%     - mod²ÎÊý versioning ±»ÉèÖÃÎªfalse »ò
%%     - ¿Í»§¶Ë·¢À´µÄÇëÇóÖÐÎ´ÉèÖÃverÊôÐÔ »ò
%%     - ·þÎñÆ÷ÉÏÃ»ÓÐ´æ´¢µ±Ç°°æ±¾ »ò
%%     - µ±Ç°°æ±¾Óë¿Í»§ÇëÇóµÄ°æ±¾²»Ò»ÖÂ
process_iq_get(From, To, #iq{sub_el = SubEl} = IQ) ->
    try
        {xmlelement, Name, _Attrs, _Items} = SubEl,
        case Name of
          "querygroup" -> querygroup(From, To, IQ);
          "querygroupmember" -> querygroupmember(From, To, IQ);
          "querymeeting" -> querymeeting(From, To, IQ);             %%²éÑ¯»áÒéÕÙ¿ªÇé¿ö
          "querymeetingmember" -> querymeetingmember(From, To, IQ); %%²éÑ¯ÒÑ²Î»á³ÉÔ±
          "searchgroup" -> searchgroup(From, To, IQ);
          "querygroupsharefile" -> querygroupsharefile(From, To, IQ);
          "querygroupclasses" -> querygroupclasses(From, To, IQ);
          _ -> IQ#iq{type = error, sub_el=[SubEl, ?ERR_ITEM_NOT_FOUND]}  
        end
    catch 
    	Ec:Ex ->  
    	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
		IQ#iq{type = error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
    end.

%%%----------------------------------------------------------------------
%%% SETÏà¹Ø¹¦ÄÜ
process_iq_set(From, To, #iq{sub_el = SubEl} = IQ) ->
    try
        {xmlelement, Name, _Attrs, _Items} = SubEl,
        case Name of        
          "inmeeting" -> inmeeting(From, To, IQ);   %%¼ÓÈë»áÒé
          "quitmeeting" -> quitmeeting(From, To, IQ);   %%ÍË³ö»áÒé
          "startmeeting" -> startmeeting(From, To, IQ);   %%¿ªÊ¼»áÒé
          "finishmeeting" -> finishmeeting(From, To, IQ);   %%½áÊø»áÒé
          "applytalk" -> applytalk(From, To, IQ);   %%ÉêÇë·¢ÑÔ
          "stoptalk" -> stoptalk(From, To, IQ);   %%½ûÑÔ
          "maytalk" -> maytalk(From, To, IQ);   %%Ö¸¶¨·¢ÑÔ
          "creategroup" -> creategroup(From, To, IQ);
          "deletegroup" -> deletegroup(From, To, IQ);
          "updategroup" -> updategroup(From, To, IQ);
          "invitegroupmember" -> invitegroupmember(From, To, IQ);
          "feedbackinvite" -> feedbackinvite(From, To, IQ);
          "applygroupmember" -> applygroupmember(From, To, IQ);
          "quitgroup" -> quitgroup(From, To, IQ); %%³ÉÔ±ÍË³öÈº
          "feedbackapply" -> feedbackapply(From, To, IQ);
          "removegroupmember" -> removegroupmember(From, To, IQ);
          "setgroupmemberrole" -> setgroupmemberrole(From, To, IQ);
          "setgroupmemberinfo" -> setgroupmemberinfo(From, To, IQ);
          "groupchat" -> groupchat(From, To, IQ);
          "rawrequest" -> rawrequest(From, To, IQ);
          "rawquit" -> rawquit(From, To, IQ);
          "takesharefile" -> takesharefile(From, To, IQ);
          "delsharefile" -> delsharefile(From, To, IQ);
          _ -> IQ#iq{type = error, sub_el=[SubEl, ?ERR_ITEM_NOT_FOUND]}  
        end
    catch 
    	Ec:Ex ->  
    	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
		IQ#iq{type = error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
    end.
%%¼ÓÈë»áÒé
inmeeting(From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    Operator = jlib:jid_to_string(From),
    Qgroupid = xml:get_tag_attr_s("groupid", SubEl),
    IsMember = ejabberdex_odbc_query:get_meeting_run_by_key(Qgroupid, Operator),
    case IsMember of 
         [_H]-> IQ#iq{type = result, sub_el = []};
         _->              
              Rec = #meeting_run{meetingemp={Qgroupid,Operator},meetingid=Qgroupid,emprole="0",in_date=binary_to_list(func_utils:date()),quit_date=[],state="1"},
              ejabberdex_odbc_query:save_meeting_run(Rec),
						  %·¢ËÍ³ÉÔ±¼ÓÈëÏûÏ¢
						  ALgroupemployee = ejabberdex_odbc_query:get_meeting_run_by_groupid(Qgroupid),
						  Pres = {xmlelement, "presence", [], 
						    [{xmlelement, "inmeeting", [{"xmlns", ?NS_GROUP}], 
						      [{xmlelement, "item", 
						        [{"meetingid", Qgroupid}, 
						         {"employeeid", Operator}, 
						         {"employeename",get_employeename(Operator)},
						         {"role", Rec#meeting_run.emprole},
						         {"in_date", Rec#meeting_run.in_date},
						         {"state", Rec#meeting_run.state}], []}]}]},
						  [send_msg_to(From,Pres,element(2,ToJid#meeting_run.meetingemp)) || ToJid<- ALgroupemployee],
						  IQ#iq{type = result, sub_el = []}
    end       
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]} 
end.
%%¿ªÊ¼»áÒé
startmeeting(From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    Operator = jlib:jid_to_string(From),
    Qgroupid = xml:get_tag_attr_s("groupid", SubEl),
    IsMember = ejabberdex_odbc_query:get_meeting_run_by_key(Qgroupid, Operator),
    case IsMember of 
         [_H]-> IQ#iq{type = result, sub_el = []};
         _->
              Rec = #meeting_run{meetingemp={Qgroupid,Operator},meetingid=Qgroupid,emprole="1",in_date=binary_to_list(func_utils:date()),quit_date=[],state="1"},
              ejabberdex_odbc_query:save_meeting_run(Rec),
						  %·¢ËÍ³ÉÔ±ÏûÏ¢
						  ALgroupemployee = get_groupemployee(Qgroupid),
						  Pres = {xmlelement, "presence", [], 
						    [{xmlelement, "startmeeting", [{"xmlns", ?NS_GROUP}], 
						      [{xmlelement, "item", 
						        [{"meetingid", Qgroupid}, 
						         {"employeeid", Operator}, 
						         {"employeename",get_employeename(Operator)},
						         {"role", Rec#meeting_run.emprole},
						         {"in_date", Rec#meeting_run.in_date},
						         {"state", Rec#meeting_run.state}], []}]}]},
						  send_msg(Pres, ALgroupemployee),
						  IQ#iq{type = result, sub_el = []}
    end       
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}    
end.
%%ÍË³ö»áÒé
quitmeeting(From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    Operator = jlib:jid_to_string(From),
    Qgroupid = xml:get_tag_attr_s("groupid", SubEl),
    IsMember = ejabberdex_odbc_query:get_meeting_run_by_key(Qgroupid, Operator),
    case IsMember of 
         []->
            case Qgroupid of 
		                  [$d,$e,$p,$t,$m,$e,$e,$t,$i,$n,$g|_]->
		                     ALgroupemployee = ejabberdex_odbc_query:get_meeting_run_by_groupid(Qgroupid),
		                     case ALgroupemployee of []->deletegroup(Qgroupid);
		                     _->skip
		                     end,
		                     IQ#iq{type = result, sub_el = []};
		                  _->
		                     IQ#iq{type = error, sub_el = [SubEl, ?ERR_ITEM_NOT_FOUND]}
		               end;            
         _->
              Rec = hd(IsMember),
              ejabberdex_odbc_query:del_meeting_run(Qgroupid, Operator),
						  %·¢ËÍ³ÉÔ±ÍË³öÏûÏ¢
						  ALgroupemployee = ejabberdex_odbc_query:get_meeting_run_by_groupid(Qgroupid),
						  case ALgroupemployee of []-> 
		              %%ÅÐ¶Ïµ±Ç°»áÒéÊÇ·ñÊÇÁÙÊ±»áÒé£¬ÊÇÔòÅÐ¶Ï»áÒé³ÉÔ±ÊÇ·ñÒÑÈ«²¿ÍË³ö£¬È«²¿ÍË³öÊ±ÔòÉ¾³ý»áÒé
		               case Qgroupid of 
		                  [$d,$e,$p,$t,$m,$e,$e,$t,$i,$n,$g|_]->
		                     deletegroup(Qgroupid);
		                  _->
		                     %%½áÊø»áÒé
						             finishmeeting(From, _To,IQ)
		               end;
						  _->
								  Pres = {xmlelement, "presence", [], 
								    [{xmlelement, "quitmeeting", [{"xmlns", ?NS_GROUP}], 
								      [{xmlelement, "item", 
								        [{"meetingid", Qgroupid}, 
								         {"employeeid", Operator}, 
								         {"employeename",get_employeename(Operator)},
								         {"role", Rec#meeting_run.emprole},
								         {"quit_date", binary_to_list(func_utils:date())},
								         {"state", "0"}], []}]}]},
								  [send_msg_to(From,Pres,element(2,ToJid#meeting_run.meetingemp)) || ToJid<- ALgroupemployee]
						  end,
						  IQ#iq{type = result, sub_el = [{xmlelement, "quitmeeting", [], []}]}
    end       
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]} 
end.
%%½áÊø»áÒé
finishmeeting(From, _To, #iq{sub_el = SubEl} = IQ) ->
  try    
    Operator = jlib:jid_to_string(From),
    Qgroupid = xml:get_tag_attr_s("groupid", SubEl),
    IsMember = ejabberdex_odbc_query:get_meeting_run_by_key(Qgroupid, Operator),
    ALgroupemployee = get_groupemployee(Qgroupid),
    case IsMember of 
         []-> skip;
         _-> ejabberdex_odbc_query:del_meeting_run(Qgroupid)
    end,
		%%¸øËùÓÐ»áÒé³ÉÔ±·¢ËÍ»áÒé½áÊøÏûÏ¢
		Pres = {xmlelement, "presence", [], 
						    [{xmlelement, "finishmeeting", [{"xmlns", ?NS_GROUP}], 
						      [{xmlelement, "item", 
						        [{"meetingid", Qgroupid}, 
						         {"employeeid", Operator}, 
						         {"employeename",get_employeename(Operator)},
						         {"role", ""},
						         {"quit_date", binary_to_list(func_utils:date())},
						         {"state", "-1"}], []}]}]},
		send_msg(Pres,ALgroupemployee),
		IQ#iq{type = result, sub_el = [{xmlelement, "finishmeeting", [],[]}]}           
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]} 
end.

%%ÉêÇë·¢ÑÔ
applytalk(From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    Operator = jlib:jid_to_string(From),
    Qgroupid = xml:get_tag_attr_s("groupid", SubEl),
    IsMember = ejabberdex_odbc_query:get_meeting_run_by_key(Qgroupid, Operator),
    case IsMember of 
         []-> IQ#iq{type = error, sub_el = [SubEl, ?ERR_ITEM_NOT_FOUND]};
         _->
              Rec = hd(IsMember),
              ALgroupemployee = ejabberdex_odbc_query:get_meeting_run_by_groupid(Qgroupid),
						  %·¢ËÍ³ÉÔ±ÍË³öÏûÏ¢						  
						  Pres = {xmlelement, "presence", [], 
						    [{xmlelement, "applytalk", [{"xmlns", ?NS_GROUP}], 
						      [{xmlelement, "item", 
						        [{"meetingid", Qgroupid}, 
						         {"employeeid", Operator}, 
						         {"employeename",get_employeename(Operator)},
						         {"role", Rec#meeting_run.emprole},
						         {"member", Operator},
						         {"state", "3"}], []}]}]},
						  [send_msg_to(jlib:make_jid("", "", ""),Pres,element(2,ToJid#meeting_run.meetingemp)) || ToJid<- ALgroupemployee],
						  IQ#iq{type = result, sub_el = []}
    end
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]} 
end.
%%½ûÑÔ
stoptalk(From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    Operator = From#jid.luser ++ "@" ++ From#jid.lserver,
    Qgroupid = xml:get_tag_attr_s("groupid", SubEl),
    Touser = xml:get_tag_attr_s("member", SubEl),
    IsMember = ejabberdex_odbc_query:get_meeting_run_by_key(Qgroupid, Touser),
    case IsMember of 
         []-> IQ#iq{type = result, sub_el = []};
         _->
              Rec = hd(IsMember),
              ejabberdex_odbc_query:save_meeting_run(Rec#meeting_run{state="3"}),
              ALgroupemployee = ejabberdex_odbc_query:get_meeting_run_by_groupid(Qgroupid),
						  %·¢ËÍ³ÉÔ±ÍË³öÏûÏ¢						  
						  Pres = {xmlelement, "presence", [], 
						    [{xmlelement, "stoptalk", [{"xmlns", ?NS_GROUP}], 
						      [{xmlelement, "item", 
						        [{"meetingid", Qgroupid}, 
						         {"employeeid", Operator}, 
						         {"employeename",get_employeename(Operator)},
						         {"role", Rec#meeting_run.emprole},
						         {"member", Touser},
						         {"state", Rec#meeting_run.state}], []}]}]},
						  [send_msg_to(jlib:make_jid("", "", ""),Pres,element(2,ToJid#meeting_run.meetingemp)) || ToJid<- ALgroupemployee],
						  IQ#iq{type = result, sub_el = []}
    end
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]} 
end.
%%ÔÊÐí·¢ÑÔ
maytalk(From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    Operator = From#jid.luser ++ "@" ++ From#jid.lserver,
    Qgroupid = xml:get_tag_attr_s("groupid", SubEl),
    Touser = xml:get_tag_attr_s("member", SubEl),
    IsMember = ejabberdex_odbc_query:get_meeting_run_by_key(Qgroupid, Touser),
    case IsMember of 
         []-> IQ#iq{type = result, sub_el = []};
         _->
              Rec = hd(IsMember),
              ejabberdex_odbc_query:save_meeting_run(Rec#meeting_run{state="2"}),
              ALgroupemployee = ejabberdex_odbc_query:get_meeting_run_by_groupid(Qgroupid),
						  %·¢ËÍ³ÉÔ±ÍË³öÏûÏ¢						  
						  Pres = {xmlelement, "presence", [], 
						    [{xmlelement, "maytalk", [{"xmlns", ?NS_GROUP}], 
						      [{xmlelement, "item", 
						        [{"meetingid", Qgroupid}, 
						         {"employeeid", Operator}, 
						         {"employeename",get_employeename(Operator)},
						         {"role", Rec#meeting_run.emprole},
						         {"member", Touser},
						         {"state", "2"}], []}]}]},
						  [send_msg_to(jlib:make_jid("", "", ""),Pres,element(2,ToJid#meeting_run.meetingemp)) || ToJid<- ALgroupemployee],
						  IQ#iq{type = result, sub_el = []}
    end       
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]} 
end.
querymeeting(From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    Operator = From#jid.luser ++ "@" ++ From#jid.lserver,
    Items = lists:map(fun groupitem_to_xml/1,
                               lists:filter(fun(Item)-> (Item#group.groupclass=="meeting") or (Item#group.groupclass=="discussgroup") end, get_groupbyemployeeid(Operator))
                            ),
	IQ#iq{type = result, sub_el = case Items of
				 	  [] ->  [];
					  _ -> [{xmlelement, "querymeeting", [{"xmlns", ?NS_GROUP}], Items}]
				      end}
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}    
end.
querymeetingmember(_From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    %%Operator = From#jid.luser ++ "@" ++ From#jid.lserver,
    Qgroupid = xml:get_tag_attr_s("groupid", SubEl),
    MeetingMembers = ejabberdex_odbc_query:get_meeting_run_by_groupid(Qgroupid),
    ItemsToSend = lists:map(fun meetingemployeeitem_to_xml/1, MeetingMembers),	
	IQ#iq{type = result, sub_el = case ItemsToSend of
				 	 [] ->  [];
					 _-> [{xmlelement, "querymeetingmember", [{"xmlns", ?NS_GROUP}], ItemsToSend}]
					end}
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}   
end.
meetingemployeeitem_to_xml(Item) ->
  {xmlelement, "item", 
    [{"meetingid", Item#meeting_run.meetingid}, 
     {"emprole", Item#meeting_run.emprole}, 
     {"employee",element(2,Item#meeting_run.meetingemp)}, 
     {"state", Item#meeting_run.state}], 
    []}.
%%%----------------------------------------------------------------------
%%% 3.2.9	²éÑ¯ÈºÐÅÏ¢
%%% From ·¢ËÍ·½JID
%%% IQ ·¢ËÍÀ´µÄXMLÄÚÈÝ£¬Àý£º
%%% <iq from='userloginname@example.com/xxxxxx123456' type='get' id='XXXXXX'>
%%%   <querygroup xmlns='http://im.private-en.com/namespace/group' ver='xxx'>
%%%   </querygroup>
%%% </iq>
%%% ·µ»ØÖµ IQ½Ú
querygroup(From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    Operator = From#jid.luser ++ "@" ++ From#jid.lserver,

    {ItemsToSend, VersionToSend} = 
	case {xml:get_tag_attr("ver", SubEl), 
	      group_versioning_enabled(From#jid.lserver)} of
	{{value, RequestedVersion}, true} ->
		%% ´ÓÊý¾Ý¿âÖÐÈ¡³öµ±Ç°°æ±¾
		case ejabberdex_odbc_query:get_group_version(Operator) of
			[#group_version{version = RequestedVersion}] ->
				{false, false};
			[#group_version{version = NewVersion}] ->
			        {lists:map(fun groupitem_to_xml/1, get_groupbyemployeeid(Operator)), NewVersion};
			[] ->
				GroupVersion = sha:sha(term_to_binary(now())),
				ejabberdex_odbc_query:set_group_version(Operator, GroupVersion),
				{lists:map(fun groupitem_to_xml/1, get_groupbyemployeeid(Operator)), GroupVersion}
		end;
	_ ->
	        {lists:map(fun groupitem_to_xml/1, get_groupbyemployeeid(Operator)), false}
	end,
	
	IQ#iq{type = result, sub_el = case {ItemsToSend, VersionToSend} of
				 	 {false, false} ->  [];
					 {Items, false} -> [{xmlelement, "groupinfo", [{"xmlns", ?NS_GROUP}], Items}];
					 {Items, Version} -> [{xmlelement, "groupinfo", [{"xmlns", ?NS_GROUP}, {"ver", Version}], Items}]
				      end}
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
  end.
%%%----------------------------------------------------------------------
%%% groupitem×ª»»ÎªXML
%%% Item #group{}
groupitem_to_xml(Item) when (Item#group.groupclass=="meeting") or (Item#group.groupclass=="discussgroup") ->
   MeetingStart = ejabberdex_odbc_query:get_meeting_run_by_groupid(Item#group.groupid),
   Groupmanagers = ejabberdex_odbc_query:get_groupmanagers(Item#group.groupid),
  {xmlelement, "item", 
    [{"groupid", Item#group.groupid}, 
     {"groupname", Item#group.groupname}, 
     {"groupclass", Item#group.groupclass}, 
     {"groupdesc", Item#group.groupdesc}, 
     {"grouppost", Item#group.grouppost}, 
     {"creator", Item#group.creator}, 
     {"managers",Groupmanagers},
     {"logo", Item#group.logo}, 
     {"number", Item#group.number}, 
     {"max_number", Item#group.max_number}, 
     {"isstart", case MeetingStart of []-> "0"; _-> "1" end}, 
     {"add_member_method", Item#group.add_member_method}], 
    []};
groupitem_to_xml(Item) ->
   Groupmanagers = ejabberdex_odbc_query:get_groupmanagers(Item#group.groupid),
  {xmlelement, "item", 
    [{"groupid", Item#group.groupid}, 
     {"groupname", Item#group.groupname}, 
     {"groupclass", Item#group.groupclass}, 
     {"groupdesc", Item#group.groupdesc}, 
     {"grouppost", Item#group.grouppost}, 
     {"creator", Item#group.creator}, 
     {"managers",Groupmanagers},
     {"logo", Item#group.logo}, 
     {"number", Item#group.number}, 
     {"max_number", Item#group.max_number},      
     {"add_member_method", Item#group.add_member_method}], 
    []}.
%%%----------------------------------------------------------------------
%%% È¡µÃÊÇ·ñÔÊÐíÊ¹ÓÃ°æ±¾µÄmod²ÎÊýversioning£¬true|false
group_versioning_enabled(Host) ->
    gen_mod:get_module_opt(Host, ?MODULE, versioning, true).

%%%----------------------------------------------------------------------
%%% 3.2.10	²éÑ¯Èº³ÉÔ±ÐÅÏ¢
%%% From ·¢ËÍ·½JID
%%% IQ ·¢ËÍÀ´µÄXMLÄÚÈÝ£¬Àý£º
%%% <iq from='userloginname@example.com/xxxxxx123456' type='get' id='XXXXXX'>
%%%   <querygroupmember xmlns='http://im.private-en.com/namespace/group' groupid='123456' ver='xxx'>
%%%   </ querygroupmember >
%%% </iq>
%%% ·µ»ØÖµ IQ½Ú
querygroupmember(From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    Operator = From#jid.luser ++ "@" ++ From#jid.lserver,
    Qgroupid = xml:get_tag_attr_s("groupid", SubEl),
    Qlimit = case xml:get_tag_attr_s("limit", SubEl) of false->""; Limit-> Limit end,
    {ItemsToSend, VersionToSend} = 
	case {xml:get_tag_attr("ver", SubEl), 
	      group_versioning_enabled(From#jid.lserver)} of
	{{value, RequestedVersion}, true} ->
		%% ´ÓÊý¾Ý¿âÖÐÈ¡³öµ±Ç°°æ±¾
		ALgroupemployee_version = ejabberdex_odbc_query:get_groupemployee_version(Operator, Qgroupid),
		case ALgroupemployee_version of
			[#groupemployee_version{version = RequestedVersion}] ->
				{false, false};
			[#groupemployee_version{version = NewVersion}] ->
			        {lists:map(fun groupemployeeitem_to_xml/1, get_groupemployeeByPage(Qgroupid,Qlimit)), NewVersion};
			[] ->
				GroupEmpVersion = sha:sha(term_to_binary(now())),
				ejabberdex_odbc_query:set_groupemployee_version(Operator, Qgroupid, GroupEmpVersion),
				{lists:map(fun groupemployeeitem_to_xml/1, get_groupemployeeByPage(Qgroupid,Qlimit)), GroupEmpVersion}
		end;
	_ ->
	        {lists:map(fun groupemployeeitem_to_xml/1, get_groupemployeeByPage(Qgroupid,Qlimit)), false}
	end,
	
	IQ#iq{type = result, sub_el = case {ItemsToSend, VersionToSend} of
				 	 {false, false} ->  [];
					 {Items, false} -> [{xmlelement, "groupmember", [{"xmlns", ?NS_GROUP}], Items}];
					 {Items, Version} -> [{xmlelement, "groupmember", [{"xmlns", ?NS_GROUP}, {"ver", Version}], Items}]
				      end}
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
  end.
  
%%%----------------------------------------------------------------------
%%% groupemployeeitem×ª»»ÎªXML
%%% Item #groupemployee{}
groupemployeeitem_to_xml(Item) ->
  {xmlelement, "item", 
    [{"groupid", Item#groupemployee.groupid}, 
     {"employeeid", Item#groupemployee.employeeid}, 
     {"grouprole", Item#groupemployee.grouprole}, 
     {"employeenick", Item#groupemployee.employeenick}, 
     {"employeenote", Item#groupemployee.employeenote},
     {"photo", Item#groupemployee.employeenote}], 
    []}.

%%%----------------------------------------------------------------------
%%% 3.2.11	ÈºËÑË÷
%%% From ·¢ËÍ·½JID
%%% IQ ·¢ËÍÀ´µÄXMLÄÚÈÝ£¬Àý£º
%%% <iq from='userloginname@example.com/xxxxxx123456' type='get' id='XXXXXX'>
%%%   <searchgroup xmlns='http://im.private-en.com/namespace/group' groupid=''
%%%     groupname='' groupclass='' start='100' count='20'>
%%%   </searchgroup>
%%% </iq>
%%% »ò
%%% <iq from='userloginname@example.com/xxxxxx123456' type='get' id='XXXXXX'>
%%%   <searchgroup xmlns='http://im.private-en.com/namespace/group' groupid='100000'>
%%%   </searchgroup>
%%% </iq>
%%% ·µ»ØÖµ IQ½Ú
searchgroup(_From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    %Operator = From#jid.luser ++ "@" ++ From#jid.lserver,

    Qgroupid = xml:get_tag_attr_s("groupid", SubEl),
    Qgroupname = xml:get_tag_attr_s("groupname", SubEl),
    Qgroupclass = xml:get_tag_attr_s("groupclass", SubEl),
    Qstart = xml:get_tag_attr_s("start", SubEl),
    Istart = case Qstart of "" -> 0; _ -> list_to_integer(Qstart) end,
    Qcount = xml:get_tag_attr_s("count", SubEl),
    Xcount = case Qcount of "" -> 20; _ -> list_to_integer(Qcount) end,
    Icount = if Xcount == 0 -> 20; true  -> Xcount end,
    
    ALgroup = ejabberdex_odbc_query:query_group(Qgroupid, Qgroupname, Qgroupclass, Istart, Icount),
    
    HasMore = {"hasmore", if length(ALgroup)<Xcount -> "false"; true -> "true" end}, 
    SGResult = {xmlelement, "searchgroupresult", [{"xmlns", ?NS_GROUP}, HasMore], lists:map(fun groupitem_to_xml/1, ALgroup)},
    IQ#iq{type = result, sub_el = [SGResult]}
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
  end.

%%%----------------------------------------------------------------------
%%% 3.3.2	²éÑ¯
%%% From ·¢ËÍ·½JID
%%% IQ ·¢ËÍÀ´µÄXMLÄÚÈÝ£¬Àý£º
%%% <iq from='userloginname@example.com/xxxxxx123456' type='get' id='XXXXXX'>
%%%   <querygroupsharefile xmlns='http://im.private-en.com/namespace/group' groupid='100000'
%%%     pageindex='0' pagesize='20'>
%%%   </querygroupsharefile>
%%% </iq>
%%% »ò
%%% <iq from='userloginname@example.com/xxxxxx123456' type='get' id='XXXXXX'>
%%%   <querygroupsharefile xmlns='http://im.private-en.com/namespace/group' groupid='100000'>
%%%   </querygroupsharefile>
%%% </iq>
%%% ·µ»ØÖµ IQ½Ú
querygroupsharefile(_From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    %Operator = From#jid.luser ++ "@" ++ From#jid.lserver,

    Qgroupid = xml:get_tag_attr_s("groupid", SubEl),
    Qpageindex = xml:get_tag_attr_s("pageindex", SubEl),
    Ipageindex = case Qpageindex of "" -> 0; _ -> list_to_integer(Qpageindex) end,
    Qpagesize = xml:get_tag_attr_s("pagesize", SubEl),
    Xpagesize = case Qpagesize of "" -> 20; _ -> list_to_integer(Qpagesize) end,
    Ipagesize = if Xpagesize == 0 -> 20; true  -> Xpagesize end,
    
    Istart = Ipageindex * Ipagesize,
        
    {ALgroupshare_file, RecordCount} = ejabberdex_odbc_query:query_groupsharefile(Qgroupid, Istart, Ipagesize),
    
    SGResult = {xmlelement, "sharefile", 
      [{"xmlns", ?NS_GROUP}, {"recordcount", integer_to_list(RecordCount)}, {"pageindex", integer_to_list(Ipageindex)}, {"pagesize", integer_to_list(Ipagesize)}], 
      lists:map(fun groupshare_fileitem_to_xml/1, ALgroupshare_file)},
    IQ#iq{type = result, sub_el = [SGResult]}
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
  end.
  
%%%----------------------------------------------------------------------
%%% groupshare_fileitem×ª»»ÎªXML
%%% Item #groupshare_file{}
groupshare_fileitem_to_xml(Item) ->
  {xmlelement, "sharefileitem", 
    [{"groupid", Item#groupshare_file.groupid}, 
     {"fileid", Item#groupshare_file.fileid}, 
     {"filename", Item#groupshare_file.filename}, 
     {"addstaff", Item#groupshare_file.addstaff}, 
     {"adddate", io_lib:format("~p-~p-~p", [element(1, Item#groupshare_file.adddate), element(2, Item#groupshare_file.adddate), element(3, Item#groupshare_file.adddate)])}, 
     {"filesize", integer_to_list(Item#groupshare_file.filesize)}], 
    []}.

%%%----------------------------------------------------------------------
%%% 3.2.12	È¡µÃÈº·ÖÀà
%%% From ·¢ËÍ·½JID
%%% IQ ·¢ËÍÀ´µÄXMLÄÚÈÝ£¬Àý£º
%%% <iq from='userloginname@example.com/xxxxxx123456' type='get' id='employee_1'>
%%%   <querygroupclasses xmlns='http://im.private-en.com/namespace/group'/>
%%% </iq>
%%% ·µ»ØÖµ IQ½Ú
querygroupclasses(_From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    ALgroupclass = ejabberdex_odbc_query:get_grouptype(),
    Items = lists:map(fun groupclassitem_to_xml/1, ALgroupclass),
    IQ#iq{type = result, sub_el = [{xmlelement, "querygroupclasses", [{"xmlns", ?NS_GROUP}], Items}]}
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
  end.
  
%%%----------------------------------------------------------------------
%%% groupclassitem_to_xml×ª»»ÎªXML
%%% Item #grouptype{}
groupclassitem_to_xml(Item) ->
  {xmlelement, "groupclassitem", 
    [
      {"typeid", Item#grouptype.typeid}, 
      {"typename", Item#grouptype.typename}, 
      {"pid", Item#grouptype.pid}, 
      {"remark", Item#grouptype.remark}
    ], 
    []}.

%%%----------------------------------------------------------------------
%%% 3.2.1	´´½¨Èº
%%% From ·¢ËÍ·½JID
%%% IQ ·¢ËÍÀ´µÄXMLÄÚÈÝ£¬Àý£º
%%% <iq from='userloginname@example.com/xxxxxx123456' type='set' id='XXXXXX'>
%%%   <creategroup xmlns='http://im.private-en.com/namespace/group'>
%%%     <item
%%%         groupname='½ñÍí´òÀÏ»¢' groupclass='Í¬Ñ§' 
%%%         groupdesc='Ã¿Íí´òÒ»´ÎÀÏ»¢' grouppost='ÔÝÎÞÈº¹«¸æ' 
%%%         add_member_method='1' />
%%%   </creategroup>
%%% </iq>
%%% ·µ»ØÖµ IQ½Ú
creategroup(From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    Operator = From#jid.luser ++ "@" ++ From#jid.lserver,
    
    Item = xml:get_subtag(SubEl, "item"),
    Agroupclass = xml:get_tag_attr_s("groupclass", Item),
    %% ÑéÖ¤FromÊÇ·ñÓÐgroupcreate½ÇÉ«£¬ÈôÀà±ðÎªdiscussgroup£¬Ôò²»ÅÐ¶ÏÈ¨ÏÞ
    HasRole_groupcreate = case ejabberdex_odbc_query:get_employeerole(Operator, "GROUP_C") of
      [_] ->
        true;
      _ when Agroupclass == "discussgroup" ->
        true;
      _ when Agroupclass == "meeting" ->
        true;
      _ ->
        false
    end,
    
    ReIQ = if 
      HasRole_groupcreate == false ->
        IQ#iq{type = error, sub_el = [SubEl, ?ERR_FORBIDDEN]};
      Item == false ->
        IQ#iq{type = error, sub_el = [SubEl, ?ERR_ITEM_NOT_FOUND]};
      true ->
        Agroupname = xml:get_tag_attr_s("groupname", Item),
        Agroupdesc = xml:get_tag_attr_s("groupdesc", Item),
        Agrouppost = xml:get_tag_attr_s("grouppost", Item),
        Deptid     = xml:get_tag_attr_s("from-dept", Item),                %%´´½¨²¿ÃÅÄÚ²¿Èº
        MeetingType = xml:get_tag_attr_s("type", Item), %%´´½¨»áÒéÊ±µÄ»áÒéÀàÐÍ¡£·ÖÎªÓÀ¾ÃÐÍºÍÁÙÊ±»áÒé£¬Ä¬ÈÏÎªÓÀ¾Ã
        Aadd_member_method = case xml:get_tag_attr_s("add_member_method", Item) of "1" -> ?GROUP_AUTH_CHECK; "2" -> ?GROUP_AUTH_REJECT; _ -> ?GROUP_AUTH_ALL end,
        Agroupid =if (Agroupclass == "meeting") and (MeetingType=="temporary") -> %%ÅÐ¶ÏÊÇ·ñÊÇÕë¶Ô²¿ÃÅ´´½¨µÄÁÙÊ±»áÒé
                      "deptmeeting-"++xml:get_tag_attr_s("depts", Item);
                  true->
								        case Deptid of 
								          []->
								          	Lst=re:split(From#jid.luser,"-",[{return,list}]),
								        	integer_to_list(list_to_integer(hd(Lst))+new_group_id());
								           _-> "dept-"++Deptid   %%²¿ÃÅÄÚ²¿ÈºµÄÈº±àºÅ¾ÍÒÔ²¿ÃÅ±àºÅÃüÃû£¬Í¬Ê±Ò²ÒÔ´ËÇø·Ö²¿ÃÅÈºÓëÒ»°ãÈº
								        end
        end,
        Members = xml:get_tag_attr_s("members", Item),
        Aemployeenick = get_employeename(Operator),
        Agroup = #group{
          groupid = Agroupid,
          groupname = Agroupname,
          groupclass = Agroupclass,
          groupdesc = Agroupdesc,
          grouppost = Agrouppost,
          creator = Operator,
          add_member_method = Aadd_member_method,
          accessright = ?GROUP_ACCESS_ANY,
          logo = [],
          number = "0",
          max_number = "100"
        },
        %%´´½¨ÈËÄ¬ÈÏ³ÉÎª¹ÜÀíÔ±
        Agroupemployee = #groupemployee{
          employeeid = Operator,
          groupid = Agroupid,
          grouprole = ?GROUP_ROLE_OWNER,
          employeenick = Aemployeenick,
          employeenote = ""
        },
        Asqls = lists:merge(ejabberdex_odbc_query:ins_group_sql(Agroup), 
                            ejabberdex_odbc_query:ins_groupemployee_sql(Agroupemployee)),
        ejabberdex_odbc_query:sql_transaction(Asqls),
        
        delete_groupversion(Agroup#group.groupid),
        delete_groupemployee_version(Agroup#group.groupid),
        %ÒòÎªÊÇÐÂÈº£¬ËùÒÔÒªÊÖ¶¯¸üÐÂµ±Ç°ÓÃ»§Èº°æ±¾
        ejabberdex_odbc_query:del_group_version(Operator),
        %%´´½¨»áÒé×éÊ±£¬Á¢¼´Ìí¼ÓÈº³ÉÔ±²¢·¢ËÍÐÅÏ¢
        if Agroupclass == "meeting"->
				    %%µ±´´½¨»áÒé×éÊ±£¬»ñÈ¡²Î»áÈËÔ±»òÕß²¿ÃÅ
				    Ameetingemployees = xml:get_tag_attr_s("employees", Item),
				    Ameetingdepts = xml:get_tag_attr_s("depts", Item),
				    GroupMemebers = re:split(Ameetingemployees,",",[{return,list}]), 
				    [fadd(Agroupid,Agroupname,Lst,"meetingmember") ||Lst<-GroupMemebers],
				    case Ameetingdepts of 
				        []-> skip;
				        _->
				            GroupDepts = re:split(Ameetingdepts,",",[{return,list}]), 				            
                    AllEmps_tmp = [im_employee_odbc:getuserbydeptid(all,GDeptid)||GDeptid<-GroupDepts],
                    %È¥³ý¹«¹²ÕÊºÅ
                    AllEmps = lists:filter(fun(ItemX)-> case ItemX#employee.deptid of [$v|_] ->false;_-> true end end,AllEmps_tmp),
                    [fadd(Agroupid,Agroupname,Lst#employee.loginname,"meetingmember") ||Lst<-lists:flatten(AllEmps)]
				    end;
        true->
		        if Deptid=/=[]->  %%´´½¨²¿ÃÅÈº
		            %%»ñÈ¡²¿ÃÅÈËÔ±¡£¸ù¾ÝÖ¸¶¨µÄ·¶Î§£¨scope=all£©»ñÈ¡ËùÓÐ×Ó²¿ÃÅÈËÔ±»ò£¨scope=none/²»Ö¸¶¨£©²¿ÃÅÈËÔ±
		            DeptEmployees_tmp=case xml:get_tag_attr_s("scope", Item) of 
		                        "all"-> im_employee_odbc:getuserbydeptid(all,Deptid);
		                        _->im_employee_odbc:getuserbydeptid(Deptid)
		            end,
		            DeptEmployees = lists:filter(fun(ItemX)-> case ItemX#employee.deptid of [$v|_] ->false;_-> true end end,DeptEmployees_tmp),
		            [fadd(Agroupid,Agroupname,Lst#employee.loginname,"deptgroupmember") ||Lst<-DeptEmployees];
		        true-> 
		        	skip
		        end,
		        if Members=/=[] ->
		        	GroupMemebers = re:split(Members,",",[{return,list}]), 		        	
				    [fadd(Agroupid,Agroupname,Lst,"groupmember") ||Lst<-GroupMemebers];
				true->
					skip
				end
        end,

        %% ·µ»ØÈºÄÚÈÝ
        Pres = {xmlelement, "presence", [], 
          [{xmlelement, "groupmember", [{"xmlns", ?NS_GROUP}], 
            [{xmlelement, "item", 
              [{"groupid", Agroupemployee#groupemployee.groupid}, 
               {"employeeid", Agroupemployee#groupemployee.employeeid}, 
               {"grouprole", Agroupemployee#groupemployee.grouprole}, 
               {"employeenick", Agroupemployee#groupemployee.employeenick}, 
               {"employeenote", Agroupemployee#groupemployee.employeenote}], []}]}]},
        ejabberd_sm:route(jlib:make_jid("", "", ""), jlib:make_jid(From#jid.luser, From#jid.lserver, ""), Pres),
        ALgroup = hd(ejabberdex_odbc_query:query_group(Agroupid, [], [], 0, 1)),
        IQ#iq{
          type = result, 
          sub_el = [{xmlelement, "groupinfo", [{"xmlns", ?NS_GROUP}], 
            [{xmlelement, "item", 
              [{"groupid", ALgroup#group.groupid}, 
               {"groupname", ALgroup#group.groupname}, 
               {"groupclass", ALgroup#group.groupclass}, 
               {"groupdesc", ALgroup#group.groupdesc}, 
               {"grouppost", ALgroup#group.grouppost}, 
               {"creator", ALgroup#group.creator}, 
               {"logo", ALgroup#group.logo},
               {"number", ALgroup#group.number},
               {"max_number", ALgroup#group.max_number},
               {"add_member_method", ALgroup#group.add_member_method}, 
               {"state", "add"}], 
              []}]}]}
    end,
    
    ReIQ
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
  end.
%%%»ñÈ¡Ö¸¶¨²¿ÃÅµÄËùÓÐ³ÉÔ±£¬°üÀ¨×Ó²¿ÃÅ³ÉÔ±¡£  
%getEmpsByDept(Depts)->
%    getEmpsByDept(Depts,[])
%.   
%getEmpsByDept(H,R) when H==[]->
%  R
%;
%getEmpsByDept([H|T],R) ->
%    Emps = im_employee_odbc:getuserbydeptid(H),
%    if Emps/=[]->
%        Accounts = lists:map(fun(K)-> K#employee.loginname end,Emps),
%        getEmpsByDept(T,R++Accounts);
%    true->
%        getEmpsByDept(T,R)
%    end
%.  
%%Ìí¼Ó³ÉÔ±µ½Ö¸¶¨Èº×é£¬²¢Á¢¼´·¢ËÍÍ¨Öª  
fadd(Agroupid,Agroupname,Qemployeeid,NodeType)->
                case Qemployeeid of []-> ok;
                            _->  %%add_groupmember_notify(Agroupid,Acc)
																  USJID = jlib:string_to_jid(Qemployeeid),
																  Aemployeenick2 = get_employeename(USJID#jid.luser++"@"++USJID#jid.lserver),
																  Agroupemployee2 = #groupemployee{employeeid=Qemployeeid, groupid=Agroupid, grouprole=?GROUP_ROLE_NORMAL, employeenick=Aemployeenick2, employeenote=""},
                                  Asqls = ejabberdex_odbc_query:ins_groupemployee_sql(Agroupemployee2),
                                  ejabberdex_odbc_query:sql_transaction(Asqls),
																  delete_groupemployee_version(Agroupid),																  
																  %·¢ËÍ³ÉÔ±¼ÓÈëÏûÏ¢
																  Pres = {xmlelement, "presence", [], 
																    [{xmlelement, NodeType, [{"xmlns", ?NS_GROUP}], 
																      [{xmlelement, "item", 
																        [{"groupid", Agroupid}, 
																         {"groupname", Agroupname}, 
																         {"employeeid", Agroupemployee2#groupemployee.employeeid}, 
																         {"grouprole", Agroupemployee2#groupemployee.grouprole}, 
																         {"employeenick", Aemployeenick2}, 
																         {"employeenote", []},
																         {"state", "add"}], []}]}]},
																  ejabberd_sm:route(jlib:make_jid("", "", ""), USJID, Pres)                           
                end
 .  
%%%----------------------------------------------------------------------
%%% ¸ù¾ÝÈººÅÂë£¬É¾³ýÈºÏà¹Ø³ÉÔ±°æ±¾ÐÅÏ¢
%%% Qgroupid ÈººÅÂë
delete_groupversion(Qgroupid) ->
  ejabberdex_odbc_query:del_group_version_by_groupid(Qgroupid).
   
%%%----------------------------------------------------------------------
%%% ¸ù¾ÝÈººÅÂë£¬É¾³ýÈº³ÉÔ±°æ±¾ÐÅÏ¢
%%% Qgroupid ÈººÅÂë
delete_groupemployee_version(Qgroupid) ->  
  ejabberdex_odbc_query:del_groupemployee_version_by_groupid(Qgroupid).

%%%----------------------------------------------------------------------
%%% È¡µÃÈËÔ±Ãû³Æ
%%% LoginName µÇÂ¼Ãû
%%% ·µ»Ø string
get_employeename(LoginName)->
  case ejabberdex_odbc_query:get_emp_by_account(LoginName) of
    []->         
        "";
    Rs->
       Rs#employee.employeename
  end.
%%%----------------------------------------------------------------------
%%% Éú³ÉÐÂµÄÈººÅÂë
%%% ÈººÅÂë´Ó100000¿ªÊ¼
new_group_id() ->
  {M,S,T} = os:timestamp(),
  NewID =(M*1000000+S+T),
  NewID.  
  
%%%----------------------------------------------------------------------
%%% 3.2.2	½âÉ¢Èº
%%% From ·¢ËÍ·½JID
%%% IQ ·¢ËÍÀ´µÄXMLÄÚÈÝ£¬Àý£º
%%% <iq from='userloginname@example.com/xxxxxx123456' type='set' id='XXXXXX'>
%%%   <deletegroup xmlns='http://im.private-en.com/namespace/group'>
%%%     <item groupid='123456'/>
%%%   </deletegroup>
%%% </iq>
%%% ·µ»ØÖµ IQ½Ú
deletegroup(From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    Operator = From#jid.luser ++ "@" ++ From#jid.lserver,    
    %% È¡³öItem
    Item = xml:get_subtag(SubEl, "item"),
    Qgroupid = if Item == false -> ""; true -> xml:get_tag_attr_s("groupid", Item) end,
    
    %% ÑéÖ¤ÊÇ·ñÈºÖ÷
    IsGroupOwner = is_groupowner(Qgroupid, Operator),
    ReIQ = if
      IsGroupOwner == false ->
        IQ#iq{type = error, sub_el = [SubEl, ?ERR_FORBIDDEN]};
      true ->
        deletegroup(Qgroupid),
        IQ#iq{type = result, sub_el = []}
    end,
    
    ReIQ
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
  end.
  
deletegroup(Qgroupid)->        
        ALgroupemployee = get_groupemployee(Qgroupid),
        delete_groupversion(Qgroupid),
        delete_groupemployee_version(Qgroupid),
        ejabberdex_odbc_query:sql_transaction(ejabberdex_odbc_query:del_group_sql(Qgroupid)),
        
        %·¢ËÍÈº½âÉ¢ÏûÏ¢
        Pres = {xmlelement, "presence", [], 
          [{xmlelement, "deletegroup", [{"xmlns", ?NS_GROUP}], 
            [{xmlelement, "item", [{"groupid", Qgroupid}], []}]}]},
        send_msg(Pres, ALgroupemployee).
        
%%%----------------------------------------------------------------------
%%% ·¢ËÍÌØ¶¨µÄÏûÏ¢¸øÖ¸¶¨µÄÈËÔ±
%%% Msg ´ý·¢ËÍµÄXML
%%% LEmployees ´ý·¢ËÍµÄÈËÔ±ÁÐ±í [#groupemployee{}]
send_msg(Msg, LEmployees) ->
  send_msg(jlib:make_jid("", "", ""), Msg, LEmployees).
  
%%% From ·¢ËÍ·½ jid
send_msg(From, Msg, LEmployees) ->
  FSend = fun(EItem, AccIn) ->
    To = jlib:string_to_jid(EItem#groupemployee.employeeid),
    ejabberd_sm:route(From, To, AccIn),
    AccIn      
  end,
  lists:foldl(FSend, Msg, LEmployees).
  
send_msg_to(From, Msg,To)->
    ejabberd_sm:route(From, jlib:string_to_jid(To), Msg)
.  
%%%----------------------------------------------------------------------
%%% ÊÇ·ñÈºÖ÷
%%% Qgroupid ÈººÅÂë
%%% Qemployeeid ³ÉÔ±JID
%%% ·µ»Ø true|false
is_groupowner(Qgroupid, Qemployeeid) ->
  case get_groupemployee(Qgroupid, Qemployeeid) of
    [#groupemployee{grouprole=?GROUP_ROLE_OWNER}] ->
      true;
    _ ->
      false
  end.

%%%----------------------------------------------------------------------
%%% È¡µÃÈº³ÉÔ±
%%% Qgroupid ÈººÅÂë
%%% ·µ»Ø [#groupemployee{}]|[]
get_groupemployee(Qgroupid) ->
  ejabberdex_odbc_query:get_groupemployee(Qgroupid).
%%% Qemployeeid ³ÉÔ±JID
get_groupemployee(Qgroupid, Qemployeeid) ->
  ejabberdex_odbc_query:get_groupemployee(Qgroupid, Qemployeeid).

get_groupemployeeByPage(Qgroupid, Qlimit) ->
  ejabberdex_odbc_query:get_groupemployeePage(Qgroupid, Qlimit).  

%%%----------------------------------------------------------------------
%%% 3.2.3	¸üÐÂÈºÐÅÏ¢
%%% From ·¢ËÍ·½JID
%%% IQ ·¢ËÍÀ´µÄXMLÄÚÈÝ£¬Àý£º
%%% <iq from='userloginname@example.com/xxxxxx123456' type='set' id='XXXXXX'>
%%%   <updategroup xmlns='http://im.private-en.com/namespace/group'>
%%%     <item groupid='123456'
%%%         groupname='½ñÍí´òÀÏ»¢' groupclass='Í¬Ñ§' 
%%%         groupdesc='Ã¿Íí´òÒ»´ÎÀÏ»¢' grouppost='ÔÝÎÞÈº¹«¸æ' 
%%%         add_member_method='1' />
%%%   </updategroup>
%%% </iq>
%%% ·µ»ØÖµ IQ½Ú
updategroup(From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    Operator = From#jid.luser ++ "@" ++ From#jid.lserver,    
    %% È¡³öItem
    Item = xml:get_subtag(SubEl, "item"),
    Qgroupid = if Item == false -> ""; true -> xml:get_tag_attr_s("groupid", Item) end,
    %% ÑéÖ¤ÊÇ·ñÈºÖ÷»ò¹ÜÀíÔ±
    IsGroupOwnerOrManager = is_groupownerormanager(Qgroupid, Operator),
    ReIQ = if
      IsGroupOwnerOrManager == false ->
        IQ#iq{type = error, sub_el = [SubEl, ?ERR_FORBIDDEN]};
      true ->
        AGroup=hd(get_group(Qgroupid)),
        Agroupname =case xml:get_tag_attr_s("groupname", Item) of []->AGroup#group.groupname;Value-> Value end,
        Agroupclass =case xml:get_tag_attr_s("groupclass", Item) of []->AGroup#group.groupclass;Value1-> Value1 end,
        Agroupdesc = case xml:get_tag_attr_s("groupdesc", Item) of []->AGroup#group.groupdesc;Value2-> Value2 end,
        Agrouppost = case xml:get_tag_attr_s("grouppost", Item) of []->AGroup#group.grouppost;Value3-> Value3 end,
        Aadd_member_method = case xml:get_tag_attr_s("add_member_method", Item) of "0" -> ?GROUP_AUTH_ALL; "2" -> ?GROUP_AUTH_REJECT; _ -> ?GROUP_AUTH_CHECK end,
        %¸üÐÂÈºÐÅÏ¢
        [Bgroup] = ejabberdex_odbc_query:get_group(Qgroupid),
        Agroup = Bgroup#group{
            groupname = Agroupname,
            groupclass = Agroupclass,
            groupdesc = Agroupdesc,
            grouppost = Agrouppost,
            add_member_method = Aadd_member_method
          },
        ejabberdex_odbc_query:sql_transaction(ejabberdex_odbc_query:update_group_sql(Agroup)),          
        delete_groupversion(Qgroupid),
        
        %·¢ËÍÈº¸üÐÂÏûÏ¢
        ALgroupemployee = get_groupemployee(Qgroupid),
        Pres = {xmlelement, "presence", [], 
          [{xmlelement, "groupinfo", [{"xmlns", ?NS_GROUP}], 
            [{xmlelement, "item", 
              [{"groupid", Agroup#group.groupid},
               {"groupname", Agroup#group.groupname}, 
               {"groupclass", Agroup#group.groupclass}, 
               {"groupdesc", Agroup#group.groupdesc}, 
               {"grouppost", Agroup#group.grouppost}, 
               {"creator", Agroup#group.creator}, 
               {"logo", Agroup#group.logo},
               {"number", Agroup#group.number},
               {"max_number", Agroup#group.max_number},
               {"add_member_method", Agroup#group.add_member_method}, 
               {"state", "modified"}],
              []}]}]},
        send_msg(Pres, ALgroupemployee),
        IQ#iq{
          type = result, 
          sub_el = [{xmlelement, "groupinfo", [{"xmlns", ?NS_GROUP}], 
            [{xmlelement, "item", 
              [{"groupid", Agroup#group.groupid}, 
               {"groupname", Agroup#group.groupname}, 
               {"groupclass", Agroup#group.groupclass}, 
               {"groupdesc", Agroup#group.groupdesc}, 
               {"grouppost", Agroup#group.grouppost}, 
               {"creator", Agroup#group.creator}, 
               {"logo", Agroup#group.logo},
               {"number", Agroup#group.number},
               {"max_number", Agroup#group.max_number},
               {"add_member_method", Agroup#group.add_member_method}, 
               {"state", "modified"}], 
              []}]}]}
    end,
    
    ReIQ
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
  end.
%%%----------------------------------------------------------------------
%%% ÊÇ·ñÈºÖ÷»ò¹ÜÀíÔ±
%%% Qgroupid ÈººÅÂë
%%% Qemployeeid ³ÉÔ±JID
%%% ÈôÎªÌÖÂÛ×é discussgroup ÔòËùÓÐÈË¶¼ÊÇ¹ÜÀíÔ±
%%% ·µ»Ø true|false
is_groupownerormanager(Qgroupid, Qemployeeid) ->
  case get_group(Qgroupid) of
    [Agroup|_] when Agroup#group.groupclass == "discussgroup" ->
      true;
    _ ->
      case get_groupemployee(Qgroupid, Qemployeeid) of
        [#groupemployee{grouprole=?GROUP_ROLE_OWNER}] ->
          true;
        [#groupemployee{grouprole=?GROUP_ROLE_MANAGER}] ->
          true;
        _ ->
          false
      end
  end.

%%%----------------------------------------------------------------------
%%% 3.2.4	ÑûÇë³ÉÔ±
%%% From ·¢ËÍ·½JID
%%% IQ ·¢ËÍÀ´µÄXMLÄÚÈÝ£¬Àý£º
%%% <iq from='userloginname@example.com/xxxxxx123456' type='set' id='XXXXXX'>
%%%   <invitegroupmember xmlns='http://im.private-en.com/namespace/group' groupid='123456' employeeid='zhangs@xx.com '>
%%%   </invitegroupmember >
%%% </iq>
%%% ·µ»ØÖµ IQ½Ú
invitegroupmember(From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    Operator = From#jid.luser ++ "@" ++ From#jid.lserver,    
    
    Qgroupid = xml:get_tag_attr_s("groupid", SubEl),
    Qemployeeid = xml:get_tag_attr_s("employeeid", SubEl),
    
    %% ÑéÖ¤ÊÇ·ñÈºÖ÷»ò¹ÜÀíÔ±
    IsGroupOwnerOrManager = is_groupownerormanager(Qgroupid, Operator),
    Agroups = get_group(Qgroupid),
    Agroup = hd(Agroups),
    ReIQ = if
      IsGroupOwnerOrManager == false ->
        IQ#iq{type = error, sub_el = [SubEl, ?ERR_FORBIDDEN]};
      Agroup#group.groupclass == "discussgroup" ->
        %Èç¹ûÊÇÌÖÂÛ×é
        add_groupmember_notify(Qgroupid, Qemployeeid),
        Agroup_new = hd(get_group(Qgroupid)),
        IQ#iq{
          type = result, 
          sub_el = [{xmlelement, "groupinfo", [{"xmlns", ?NS_GROUP}], 
            [{xmlelement, "item", 
              [{"groupid", Agroup_new#group.groupid}, 
               {"groupname", Agroup_new#group.groupname}, 
               {"groupclass", Agroup_new#group.groupclass}, 
               {"groupdesc", Agroup_new#group.groupdesc}, 
               {"grouppost", Agroup_new#group.grouppost}, 
               {"creator", Agroup_new#group.creator}, 
               {"logo", Agroup_new#group.logo},
               {"number", Agroup_new#group.number},
               {"max_number", Agroup_new#group.max_number},
               {"add_member_method", Agroup_new#group.add_member_method}, 
               {"state", "modified"}], 
              []}]}]};
      true ->
        %%ÅÐ¶ÏÊÇ·ñÊÇ²¿ÃÅÄÚ²¿Èº£¬ÊÇÄÚ²¿ÈºÔò²»ÓÃµÈ´ý³ÉÔ±Í¬Òâ
        case Qgroupid of 
           [$d,$e,$p,$t|_]->
              add_groupmember_notify(Qgroupid, Qemployeeid),
              IQ#iq{type = result, sub_el = []};
           _->
			        %·¢ËÍÑûÇëÏûÏ¢
			        Msg = {xmlelement, "message", [], 
			          [{xmlelement, "invitegroupmember", 
			            [{"xmlns", ?NS_GROUP},
			             {"groupid", Qgroupid},
			             {"groupclass",Agroup#group.groupclass},
			             {"groupname",Agroup#group.groupname},
			             {"groupdesc", Agroup#group.groupdesc}, 
                   {"grouppost", Agroup#group.grouppost}, 
                   {"creator", Agroup#group.creator}, 
                   {"logo", Agroup#group.logo},
                   {"number", Agroup#group.number},
                   {"max_number", Agroup#group.max_number},                   
                   {"add_member_method", Agroup#group.add_member_method},
                   {"employeename",get_employeename(Operator)},
			             {"employeeid", Operator}], 
			            []}]},
			        ejabberd_sm:route(jlib:make_jid("", "", ""), jlib:string_to_jid(Qemployeeid), Msg),
			        IQ#iq{type = result, sub_el = []}
        end
    end,
    
    ReIQ
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
  end.
  
%%³ÉÔ±ÍË³öÈº  
quitgroup(From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    Operator = From#jid.luser ++ "@" ++ From#jid.lserver, 
    Qgroupid = xml:get_tag_attr_s("groupid", SubEl),
    
    %ÅÐ¶ÏÈºÊÇ·ñ´æÔÚ
    Agroup = get_group(Qgroupid),
    %ÅÐ¶ÏOperatorÊÇ·ñÒÑÊÇ³ÉÔ±
    ALdelete_groupemployee = get_groupemployee(Qgroupid, Operator),
    ReIQ = if
      Agroup == [] ->
        IQ#iq{type = error, sub_el = [SubEl, ?ERR_FORBIDDEN]};
      ALdelete_groupemployee ==[] ->
        IQ#iq{type = result, sub_el = []};      
      true ->
        IsGroupOwnerOrManager = is_groupownerormanager(Qgroupid, Operator),
        %%´ÓÈºÖÐÉ¾³ý³ÉÔ±
        Adelete_groupemployee = hd(ALdelete_groupemployee),
        Asql = ejabberdex_odbc_query:del_groupemployee_sql(Adelete_groupemployee#groupemployee.groupid, Adelete_groupemployee#groupemployee.employeeid),
        ejabberdex_odbc_query:sql_transaction(Asql),
			  %·¢ËÍ³ÉÔ±ÍËÈºÏûÏ¢
			  ALgroupemployee = get_groupemployee(Qgroupid),
        if ALgroupemployee == [] ->
            %½âÉ¢Èº
            ejabberdex_odbc_query:sql_transaction(ejabberdex_odbc_query:del_group_sql(Qgroupid));
        true->
            NextMember = hd(ALgroupemployee),
            if IsGroupOwnerOrManager== true andalso NextMember=/=[] ->              
              GroupOwnerEle = NextMember#groupemployee.employeenick,
              Asql1 = ejabberdex_odbc_query:update_groupemployee_sql(NextMember#groupemployee{grouprole = ?GROUP_ROLE_OWNER}),
              ejabberdex_odbc_query:sql_transaction(Asql1);
            true->
              GroupOwnerEle = []
            end,
            GroupInfo = hd(Agroup),
    			  Pres = {xmlelement, "presence", [], 
    			    [{xmlelement, "groupmember", [{"xmlns", ?NS_GROUP}], 
    			      [{xmlelement, "item", 
    			        [{"groupid", Adelete_groupemployee#groupemployee.groupid}, 
    			         {"groupclass", GroupInfo#group.groupclass},
                    {"number", GroupInfo#group.number},
                    {"logo", GroupInfo#group.logo},
                    {"max_number", GroupInfo#group.max_number},
    			         {"employeeid", Adelete_groupemployee#groupemployee.employeeid}, 
    			         {"grouprole", Adelete_groupemployee#groupemployee.grouprole}, 
    			         {"employeenick", Adelete_groupemployee#groupemployee.employeenick}, 
    			         {"employeenote", Adelete_groupemployee#groupemployee.employeenote},
                   {"groupowner", GroupOwnerEle},
    			         {"state", "quit"}], []}]}]},
    			  send_msg(Pres, ALgroupemployee)
        end,
        IQ#iq{type = result, sub_el = []}
    end,
    
    ReIQ
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}    
end.

feedbackinvite(From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    Operator = From#jid.luser ++ "@" ++ From#jid.lserver,    
    
    Qgroupid = xml:get_tag_attr_s("groupid", SubEl),
    Qemployeeid = xml:get_tag_attr_s("employeeid", SubEl),
    Qaccept = xml:get_tag_attr_s("accept", SubEl),
    
    %ÅÐ¶ÏÈºÊÇ·ñ´æÔÚ
    Agroup = get_group(Qgroupid),
    %ÅÐ¶ÏOperatorÊÇ·ñÒÑÊÇ³ÉÔ±
    IsGroupMember = is_groupmember(Qgroupid, Operator),
    
    ReIQ = if
      Agroup == [] ->
        IQ#iq{type = error, sub_el = [SubEl, ?ERR_FORBIDDEN]};
      IsGroupMember == true ->
        IQ#iq{type = result, sub_el = []};
      Qaccept == "1" ->
        add_groupmember_notify(Qgroupid, Operator),
        IQ#iq{type = result, sub_el = []};
      true ->
        Pres = {xmlelement, "presence", [], [SubEl]},
        ejabberd_sm:route(From, jlib:string_to_jid(Qemployeeid), Pres),      
        IQ#iq{type = result, sub_el = []}
    end,
    
    ReIQ
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
  end.

add_groupmember_notify(Qgroupid, Qemployeeid) ->
  GroupInfo = hd(get_group(Qgroupid)),  
  ALgroupemployee = get_groupemployee(Qgroupid),
  %%判断是否是多个帐号
  Jids = re:split(Qemployeeid,",",[{return,list}]),
  Number = GroupInfo#group.number,
  Mnumber=case is_list(Number) of true -> list_to_integer(Number)+length(Jids);
    _->Number+length(Jids)
  end,
  Items=lists:map(fun(Item)->
      USJID = jlib:string_to_jid(Item),
      Aemployeenick = get_employeename(USJID#jid.luser++"@"++USJID#jid.lserver),
      Agroupemployee = #groupemployee{employeeid=Item, groupid=Qgroupid, grouprole=?GROUP_ROLE_NORMAL, employeenick=Aemployeenick, employeenote=""},
      ejabberdex_odbc_query:sql_transaction(ejabberdex_odbc_query:ins_groupemployee_sql(Agroupemployee)),
      {Agroupemployee,{xmlelement, "item", 
                    [{"groupid", Qgroupid}, 
                     {"number",integer_to_list(Mnumber)},
                     {"logo", GroupInfo#group.logo},
                     {"max_number", GroupInfo#group.max_number},
                     {"employeeid", Agroupemployee#groupemployee.employeeid}, 
                     {"groupclass", GroupInfo#group.groupclass},
                     {"grouprole", ?GROUP_ROLE_NORMAL}, 
                     {"employeenick", Agroupemployee#groupemployee.employeenick}, 
                     {"employeenote", Agroupemployee#groupemployee.employeenote},
                     {"state", "add"}], []}
      }
  end,Jids),
  
  delete_groupemployee_version(Qgroupid),
  ItemXmls = lists:map(fun({_,Ixml})-> Ixml end,Items),
  NewMembers = lists:map(fun({M,_})-> M end,Items),
  Pres = {xmlelement, "presence", [], 
    [{xmlelement, "groupmember", [{"xmlns", ?NS_GROUP}], ItemXmls}]},
  send_msg(Pres, ALgroupemployee++NewMembers).
  
%%%----------------------------------------------------------------------
%%% È¡µÃÈºÐÅÏ¢
%%% Qgroupid ÈººÅÂë
%%% ·µ»Ø [#group{}]|[]
get_group(Qgroupid) ->
  ejabberdex_odbc_query:get_group(Qgroupid).
    
%%%----------------------------------------------------------------------
%%% È¡µÃÈºÐÅÏ¢
%%% Qemployeeid ³ÉÔ±
%%% ·µ»Ø [#group{}]|[]
get_groupbyemployeeid(Qemployeeid) ->
  ejabberdex_odbc_query:get_groupbyemployeeid(Qemployeeid).
  
%%%----------------------------------------------------------------------
%%% ÊÇ·ñÈº³ÉÔ±
%%% Qgroupid ÈººÅÂë
%%% Qemployeeid ³ÉÔ±JID
%%% ·µ»Ø true|false
is_groupmember(Qgroupid, Qemployeeid) ->
  case get_groupemployee(Qgroupid, Qemployeeid) of
    [_] ->
      true;
    _ ->
      false
  end.
  
%%%----------------------------------------------------------------------
%%% 3.2.5	ÉêÇëÈëÈº
%%% From ·¢ËÍ·½JID
%%% IQ ·¢ËÍÀ´µÄXMLÄÚÈÝ£¬Àý£º
%%% <iq from='userloginname@example.com/xxxxxx123456' type='set' id='XXXXXX'>
%%%   <applygroupmember xmlns='http://im.private-en.com/namespace/group' groupid='123456' employeename='ÉêÇëÕß' verification='Ê¶±ðÐÅÏ¢(Ò»°ãÎªÉêÇëÕßµ¥Î»)'>
%%%   </applygroupmember >
%%% </iq>
%%% ·µ»ØÖµ IQ½Ú
applygroupmember(From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    Operator = From#jid.luser ++ "@" ++ From#jid.lserver,    
    
    Qgroupid = xml:get_tag_attr_s("groupid", SubEl),
    
    %ÅÐ¶ÏOperatorÊÇ·ñÒÑÊÇ³ÉÔ±
    IsGroupMember = is_groupmember(Qgroupid, Operator),
    %È¡³öÈº
    Agroup = get_group(Qgroupid),
        
    ReIQ = if
      IsGroupMember == true ->
        IQ#iq{type = result, sub_el = []};
      Agroup == [] ->
        IQ#iq{type = error, sub_el = [SubEl, ?ERR_FORBIDDEN]};
      (hd(Agroup))#group.add_member_method == "2" ->
        IQ#iq{type = error, sub_el = [SubEl, ?ERR_FORBIDDEN]};
      true ->
        ALgroupemployee = lists:filter(
          fun(EItem) -> 
            EItem#groupemployee.grouprole == ?GROUP_ROLE_OWNER 
            orelse EItem#groupemployee.grouprole == ?GROUP_ROLE_MANAGER 
          end, 
          get_groupemployee(Qgroupid)),
        Msg = {xmlelement, "message", [], 
          [{xmlelement, "applygroupmember", 
            [{"xmlns", ?NS_GROUP},
             {"groupid", Qgroupid},
             {"employeename",xml:get_tag_attr_s("employeename", SubEl)},
             {"verification",xml:get_tag_attr_s("verification", SubEl)},
             {"employeeid", Operator}], 
            []}]},
        send_msg(Msg, ALgroupemployee),
        IQ#iq{type = result, sub_el = []}
    end,
    
    ReIQ
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
  end.
%%%----------------------------------------------------------------------
%%% ¿Í»§¶Ë·¢À´ÉêÇëÈëÈºÉóºË½Ú<feedbackapply/>
%%% From ·¢ËÍ·½JID
%%% IQ ·¢ËÍÀ´µÄXMLÄÚÈÝ£¬Àý£º
%%% <iq from='admin1@example.com/xxxxxx123456' type='set' id='XXXXXX'>
%%%   <feedbackapply xmlns='http://im.private-en.com/namespace/group' groupid='123456' employeeid='XXX@XX.com'
%%%                 accept='1'>
%%%   </feedbackapply>
%%% </iq>
%%% ·µ»ØÖµ IQ½Ú
feedbackapply(From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    Operator = From#jid.luser ++ "@" ++ From#jid.lserver,    
    
    Qgroupid = xml:get_tag_attr_s("groupid", SubEl),
    Qemployeeid = string:to_lower(xml:get_tag_attr_s("employeeid", SubEl)),
    Qaccept = xml:get_tag_attr_s("accept", SubEl),
    
    %ÅÐ¶ÏÈºÊÇ·ñ´æÔÚ
    Agroup = get_group(Qgroupid),
    %% ÑéÖ¤ÊÇ·ñÈºÖ÷»ò¹ÜÀíÔ±
    IsGroupOwnerOrManager = is_groupownerormanager(Qgroupid, Operator),
    %ÅÐ¶ÏÊÇ·ñÒÑÊÇ³ÉÔ±
    IsGroupMember = is_groupmember(Qgroupid, Qemployeeid),
    
    ReIQ = if
      Agroup == [] ->
        IQ#iq{type = error, sub_el = [SubEl, ?ERR_FORBIDDEN]};
      IsGroupOwnerOrManager == false ->
        IQ#iq{type = error, sub_el = [SubEl, ?ERR_FORBIDDEN]};
      IsGroupMember == true ->
        IQ#iq{type = result, sub_el = []};
      Qaccept == "1" ->
        add_groupmember_notify(Qgroupid, Qemployeeid),
        IQ#iq{type = result, sub_el = []};
      true ->
        Pres = {xmlelement, "presence", [], [SubEl]},
        ejabberd_sm:route(From, jlib:string_to_jid(Qemployeeid), Pres),
        IQ#iq{type = result, sub_el = []}
    end,
    
    ReIQ
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
  end.
%%%----------------------------------------------------------------------
%%% 3.2.6	Ìß³ý³ÉÔ±
%%% From ·¢ËÍ·½JID
%%% IQ ·¢ËÍÀ´µÄXMLÄÚÈÝ£¬Àý£º
%%% <iq from='userloginname@example.com/xxxxxx123456' type='set' id='XXXXXX'>
%%%   <removegroupmember xmlns='http://im.private-en.com/namespace/group' groupid='123456' employeeid='zhangs@ XX.com '>
%%%   </removegroupmember >
%%% </iq>
%%% ·µ»ØÖµ IQ½Ú
removegroupmember(From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    Operator = From#jid.luser ++ "@" ++ From#jid.lserver,    
    
    Qgroupid = xml:get_tag_attr_s("groupid", SubEl),
    Qemployeeid = string:to_lower(xml:get_tag_attr_s("employeeid", SubEl)),
    
    %% ÑéÖ¤ÊÇ·ñÈºÖ÷»ò¹ÜÀíÔ±
    IsGroupOwnerOrManager = is_groupownerormanager(Qgroupid, Operator),
    ReIQ = if
      %% Èç¹û²»ÊÇÈºÖ÷£¬²¢ÇÒÒ²²»ÊÇ×Ô¼ºÍË³ö
      IsGroupOwnerOrManager == false andalso Operator /= Qemployeeid ->
        IQ#iq{type = error, sub_el = [SubEl, ?ERR_FORBIDDEN]};
      true ->
        Allmembers = get_groupemployee(Qgroupid),
        case length(Allmembers) of 2->
        	deletegroup(Qgroupid);
        _->
	        %È¡³ö´ýÍË³ö³ÉÔ±
	        ALdelete_groupemployee = get_groupemployee(Qgroupid, Qemployeeid),
	        if 
	          ALdelete_groupemployee == [] ->
	            IQ#iq{type = error, sub_el = [SubEl, ?ERR_FORBIDDEN]};
	          true ->
	            [Adelete_groupemployee|_] = ALdelete_groupemployee,
	            %È¡³öËùÓÐ³ÉÔ±£¬²¢ÕÒ³öÏÂÒ»¸öÈºÖ÷
	            ALgroupemployee = lists:delete(Adelete_groupemployee, Allmembers),
	            NextGroupOwner = if 
	              Adelete_groupemployee#groupemployee.grouprole == ?GROUP_ROLE_OWNER ->
	                get_nextgroupowner(ALgroupemployee);
	              true ->
	                null
	            end,
	            Asql = ejabberdex_odbc_query:del_groupemployee_sql(Adelete_groupemployee#groupemployee.groupid, Adelete_groupemployee#groupemployee.employeeid),
	            ejabberdex_odbc_query:sql_transaction(Asql),
	            PresNext = if 
	              NextGroupOwner /= null ->
	                Asql1 = ejabberdex_odbc_query:update_groupemployee_sql(NextGroupOwner#groupemployee{grouprole = ?GROUP_ROLE_OWNER}),
	                ejabberdex_odbc_query:sql_transaction(Asql1),
                  Agroup = get_group(Qgroupid),
	                GroupInfo = hd(Agroup),
	                [{xmlelement, "item", 
	                  [ {"groupid", NextGroupOwner#groupemployee.groupid}, 
	                    {"number", GroupInfo#group.number},
	                    {"logo", GroupInfo#group.logo},
	                    {"max_number", GroupInfo#group.max_number},
	                   {"employeeid", NextGroupOwner#groupemployee.employeeid}, 
	                   {"groupclass", GroupInfo#group.groupclass},
	                   {"grouprole", ?GROUP_ROLE_OWNER}, 
	                   {"employeenick", NextGroupOwner#groupemployee.employeenick}, 
	                   {"employeenote", NextGroupOwner#groupemployee.employeenote},
	                   {"state", "modified"}], []}];
	              NextGroupOwner == null andalso ALgroupemployee == [] ->
	                deletegroup(Qgroupid),
	                [];
	              true ->
	                []
	            end,
	            ejabberdex_odbc_query:del_group_version(Qemployeeid),
	            delete_groupemployee_version(Qgroupid),
	            Agroup_new = get_group(Qgroupid),
              case Agroup_new of []->
                IQ#iq{type =result, sub_el = []};
              _->
                  LAgroup_new = hd(Agroup_new),
    	            Pres = {xmlelement, "presence", [], 
    	              [{xmlelement, "groupmember", [{"xmlns", ?NS_GROUP}], 
    	                [{xmlelement, "item", 
    	                  [{"groupid", Adelete_groupemployee#groupemployee.groupid}, 
    	                   {"employeeid", Adelete_groupemployee#groupemployee.employeeid},
    	                   {"employeenick", Adelete_groupemployee#groupemployee.employeenick},
    	                   {"groupclass", LAgroup_new#group.groupclass},
    	                   {"state", "delete"}], []} 
    	                |PresNext]}]},
    	            send_msg(Pres, [Adelete_groupemployee]),
    	            send_msg(Pres, ALgroupemployee),
    	            IQ#iq{
                    type = result, 
                    sub_el = [{xmlelement, "groupinfo", [{"xmlns", ?NS_GROUP}], 
                      [{xmlelement, "item", 
                        [{"groupid", LAgroup_new#group.groupid}, 
                         {"groupname", LAgroup_new#group.groupname}, 
                         {"groupclass", LAgroup_new#group.groupclass}, 
                         {"groupdesc", LAgroup_new#group.groupdesc}, 
                         {"grouppost", LAgroup_new#group.grouppost}, 
                         {"creator", LAgroup_new#group.creator}, 
                         {"logo", LAgroup_new#group.logo},
                         {"number", LAgroup_new#group.number},
                         {"max_number", LAgroup_new#group.max_number},
                         {"add_member_method", LAgroup_new#group.add_member_method}, 
                         {"state", "modified"}], 
                        []}]}]}
	           end
          end
    	end
    end,
    
    ReIQ
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
  end.
  
%%%----------------------------------------------------------------------
%%% È¡³öÏÂÒ»ÈÎÈºÖ÷
%%% ALgroupemployee [#groupemployee{}]|[]
%%% ·µ»Ø #groupemployee{}|null
get_nextgroupowner(ALgroupemployee) ->
  if
    ALgroupemployee == [] ->
      null;
    true ->
      Next = begin
        ALManager = lists:filter(fun(EItem) -> EItem#groupemployee.grouprole == ?GROUP_ROLE_MANAGER end, ALgroupemployee),
        if 
          ALManager == [] ->
            [X|_]  = ALgroupemployee,
            X;
          true ->
            [X|_] = ALManager,
            X
        end
      end,
      Next
  end.
%%%----------------------------------------------------------------------
%%% 3.2.7	ÉèÖÃ³ÉÔ±½ÇÉ«
%%% From ·¢ËÍ·½JID
%%% IQ ·¢ËÍÀ´µÄXMLÄÚÈÝ£¬Àý£º
%%% <iq from='userloginname@example.com/xxxxxx123456' type='set' id='XXXXXX'>
%%%   <setgroupmemberrole xmlns='http://im.private-en.com/namespace/group' groupid='123456' employeeid='zhangs@ XX.com ' 
%%%                     grouprole='owner'>
%%%   </setgroupmemberrole>
%%% </iq>
%%% ·µ»ØÖµ IQ½Ú
setgroupmemberrole(From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    Operator = From#jid.luser ++ "@" ++ From#jid.lserver,    

    Qgroupid = xml:get_tag_attr_s("groupid", SubEl),
    Qemployeeid = string:to_lower(xml:get_tag_attr_s("employeeid", SubEl)),
    Qgrouprole = string:to_lower(xml:get_tag_attr_s("grouprole", SubEl)),

    %% ÑéÖ¤ÊÇ·ñÈºÖ÷
    IsGroupOwner = is_groupowner(Qgroupid, Operator),
    ReIQ = if
      IsGroupOwner == false ->
        IQ#iq{type = error, sub_el = [SubEl, ?ERR_FORBIDDEN]};
      true ->
        %ÕÒ³öËùÓÐ³ÉÔ±¡¢¼°µ±Ç°²Ù×÷ÈËÔ±ºÍÒªÉèÖÃµÄÈËÔ±
        ALgroupemployee = get_groupemployee(Qgroupid),
        ALgrouponwer = lists:filter(
          fun(EItem) -> 
            EItem#groupemployee.employeeid == Operator
          end,
          ALgroupemployee),
        ALgroupemployee_set = lists:filter(
          fun(EItem) -> 
            EItem#groupemployee.employeeid == Qemployeeid
          end,
          ALgroupemployee),
        if 
          ALgrouponwer == [] orelse ALgroupemployee_set == [] ->
            IQ#iq{type = error, sub_el = [SubEl, ?ERR_FORBIDDEN]};
          true ->
            %¸üÐÂ¸÷³ÉÔ±½ÇÉ«£¬²¢·¢ÏûÏ¢
            [Agrouponwer|_] = ALgrouponwer,
            [Agroupemployee_set|_] = ALgroupemployee_set,
            PresNext = if
              Qgrouprole == ?GROUP_ROLE_OWNER ->
                Asql1 = ejabberdex_odbc_query:update_groupemployee_sql(Agrouponwer#groupemployee{grouprole = ?GROUP_ROLE_NORMAL}),
                ejabberdex_odbc_query:sql_transaction(Asql1),
                [{xmlelement, "item", 
                  [{"groupid", Agrouponwer#groupemployee.groupid}, 
                   {"employeeid", Agrouponwer#groupemployee.employeeid}, 
                   {"grouprole", ?GROUP_ROLE_NORMAL}, 
                   {"employeenick", Agrouponwer#groupemployee.employeenick}, 
                   {"employeenote", Agrouponwer#groupemployee.employeenote},
                   {"state", "modified"}], []}];
              true ->                
                []
            end,
            Asql2 = ejabberdex_odbc_query:update_groupemployee_sql(Agroupemployee_set#groupemployee{grouprole = Qgrouprole}),
            ejabberdex_odbc_query:sql_transaction(Asql2),
            
            delete_groupemployee_version(Qgroupid),
        
            %·¢ËÍÑûÇëÏûÏ¢
            Pres = {xmlelement, "presence", [], 
              [{xmlelement, "groupmember", [{"xmlns", ?NS_GROUP}], 
                [{xmlelement, "item", 
                  [{"groupid", Agroupemployee_set#groupemployee.groupid}, 
                   {"employeeid", Agroupemployee_set#groupemployee.employeeid}, 
                   {"grouprole", Qgrouprole}, 
                   {"employeenick", Agroupemployee_set#groupemployee.employeenick}, 
                   {"employeenote", Agroupemployee_set#groupemployee.employeenote},
                   {"state", "modified"}], []} 
                |PresNext]}]},
            send_msg(Pres, ALgroupemployee),
            IQ#iq{type = result, sub_el = []}
        end
    end,
    
    ReIQ
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
  end.
%%%----------------------------------------------------------------------
%%% 3.2.8	¸üÐÂÈºÃûÆ¬
%%% From ·¢ËÍ·½JID
%%% IQ ·¢ËÍÀ´µÄXMLÄÚÈÝ£¬Àý£º
%%% <iq from='userloginname@example.com/xxxxxx123456' type='set' id='XXXXXX'>
%%%   <setgroupmemberinfo xmlns='http://im.private-en.com/namespace/group' groupid='123456' employeeid='userloginname@ XX.com ' 
%%%                    employeenick='XXXXX' employeenote='XXXXX'>
%%%   </setgroupmemberinfo>
%%% </iq>
%%% ·µ»ØÖµ IQ½Ú
setgroupmemberinfo(From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    Operator = From#jid.luser ++ "@" ++ From#jid.lserver,    

    Qgroupid = xml:get_tag_attr_s("groupid", SubEl),
    Qemployeeid = string:to_lower(xml:get_tag_attr_s("employeeid", SubEl)),
    Qemployeenick =case xml:get_tag_attr_s("employeenick", SubEl) of
                   []->
                      get_employeename(Qemployeeid);
                   Nick-> Nick
                   end,
    Qemployeenote = xml:get_tag_attr_s("employeenote", SubEl),
    %% ÑéÖ¤ÊÇ·ñÈºÖ÷»ò¹ÜÀíÔ±
    IsGroupOwnerOrManager = is_groupownerormanager(Qgroupid, Operator),
    ReIQ = if
      %% Èç¹û²»ÊÇÈºÖ÷£¬²¢ÇÒÒ²²»ÊÇ×Ô¼º
      IsGroupOwnerOrManager == false andalso Operator /= Qemployeeid ->
        IQ#iq{type = error, sub_el = [SubEl, ?ERR_FORBIDDEN]};
      true ->
        ALupdate_groupemployee = get_groupemployee(Qgroupid, Qemployeeid),
        if 
          ALupdate_groupemployee == [] ->
            IQ#iq{type = error, sub_el = [SubEl, ?ERR_FORBIDDEN]};
          true ->
            [Aupdate_groupemployee|_] = ALupdate_groupemployee,
            Bupdate_groupemployee = Aupdate_groupemployee#groupemployee{employeenick = Qemployeenick, employeenote = Qemployeenote},
            Asql1 = ejabberdex_odbc_query:update_groupemployee_sql(Bupdate_groupemployee),
            ejabberdex_odbc_query:sql_transaction(Asql1),
            delete_groupemployee_version(Qgroupid),
            
            %·¢ËÍÑûÇëÏûÏ¢
            ALgroupemployee = get_groupemployee(Qgroupid),
            Pres = {xmlelement, "presence", [], 
              [{xmlelement, "groupmember", [{"xmlns", ?NS_GROUP}], 
                [{xmlelement, "item", 
                  [{"groupid", Bupdate_groupemployee#groupemployee.groupid}, 
                   {"employeeid", Bupdate_groupemployee#groupemployee.employeeid}, 
                   {"grouprole", Bupdate_groupemployee#groupemployee.grouprole}, 
                   {"employeenick", Bupdate_groupemployee#groupemployee.employeenick}, 
                   {"employeenote", Bupdate_groupemployee#groupemployee.employeenote},
                   {"state", "modified"}], []}]}]},
            send_msg(Pres, ALgroupemployee),
            IQ#iq{type = result, sub_el = []}            
        end
    end,
    
    ReIQ
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
  end.

%%%----------------------------------------------------------------------
%%% 3.3	ÈºÍ¨Ñ¶¹¦ÄÜ
%%% From ·¢ËÍ·½JID
%%% IQ ·¢ËÍÀ´µÄXMLÄÚÈÝ£¬Àý£º
%%% <iq from='userloginname@example.com/xxxxxx123456' type='set' id='XXXXXX'>
%%%   <groupchat xmlns='http://im.private-en.com/namespace/group' groupid='123456'>
%%%     <text>this is a test.</text>
%%%   </groupchat>
%%% </iq>
%%% ·µ»ØÖµ IQ½Ú
groupchat(From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
  	IQId = IQ#iq.id,
    Operator = From#jid.luser ++ "@" ++ From#jid.lserver,    
    Qgroupid = xml:get_tag_attr_s("groupid", SubEl),  
    QMsgid = xml:get_tag_attr_s("id", SubEl),   
    IsGroupMember = is_groupmember(Qgroupid, Operator),
    ReIQ = if
      IsGroupMember == false ->
        IQ#iq{type = error, sub_el = [SubEl, ?ERR_FORBIDDEN]};
      true ->
      	Action = xml:get_tag_attr_s("action", SubEl), 
      	case Action of 
      	"query"->    
      		MsgID = xml:get_tag_attr_s("msgid", SubEl) ,
      		Msgs=ejabberdex_odbc_query:get_group_message(Qgroupid,Operator,MsgID),
      		Items = lists:map(fun(Item)->
      			{xmlelement,Name,Attrs,Text}=xml_stream:parse_element(element(1,Item)), 
      			NewAtts=lists:keyreplace("sendtime",1,Attrs,{"sendtime",element(2,Item)}),
      			{xmlelement,Name,NewAtts,Text}
      		end,Msgs),
      		IQ#iq{type = result, sub_el = [{xmlelement, "groupchat", [{"xmlns", ?NS_GROUP}], Items}]};
      	"revoke"->
      		MsgID = xml:get_tag_attr_s("msgid", SubEl) ,
      		ejabberdex_odbc_query:del_group_message(MsgID),
      		ALgroupemployee=get_groupemployee(Qgroupid),
      		Msg =  {xmlelement, "message", [{"from", Operator},{"id","b_del_"++MsgID}], 
                    	[{xmlelement, "business", [{"xmlns","http://im.private-en.com/namespace/business"}],
	                        [{xmlelement,"caption",[],[{xmlcdata,"message_revoke"}]},
	                        {xmlelement,"type",[],[{xmlcdata,"message_revoke"}]},
	                        {xmlelement,"sendername",[],[{xmlcdata,""}]},
	                        {xmlelement,"sendtime",[],[{xmlcdata, binary_to_list( func_utils:date())}]},
	                        {xmlelement,"link",[],[{xmlcdata,[]}]},
	                        {xmlelement,"buttons",[],[]},
	                        {xmlelement,"body",[],[{xmlcdata,MsgID}]}
	                        ]
                    	}]
                },
      		send_msg(From, Msg, ALgroupemployee),
      		IQ#iq{type = result, sub_el = []};
      	"delete"->
      		MsgID = xml:get_tag_attr_s("msgid", SubEl) ,
      		ejabberdex_odbc_query:del_group_message(MsgID),
      		IQ#iq{type = result, sub_el = []};
      	_->
      	 	Agroup = hd(get_group(Qgroupid)),
      	 	{xmlelement,"groupchat",GAttr,GMsg}=SubEl,
      	 	GAttr2 = GAttr++[{"groupname",Agroup#group.groupname}],
      	 	SubEl2 = {xmlelement,"groupchat",GAttr2,GMsg},
      		Msgid =case QMsgid of []-> From#jid.luser++"-"++IQId; _-> QMsgid end,
      		Msg = {xmlelement, "message", [{"from", Operator},{"id",Msgid}], [SubEl2]},
      		ejabberdex_odbc_query:save_group_msg(Qgroupid,Operator,Msg,Msgid),
      		ejabberdex_odbc_query:update_group_lastdate(Qgroupid),
      		ejabberdex_odbc_query:set_group_lastreadid(Qgroupid,[Operator]),
      		%%只向在线的帐号发送消息
      		ALgroupemployee = lists:filter(fun(EItem) -> EItem#groupemployee.employeeid /= Operator andalso binary_to_list(erlmc:get(EItem#groupemployee.employeeid))/=[] end, get_groupemployee(Qgroupid)),
	        if 
	          ALgroupemployee == [] ->
	            skip;
	          true ->
	            send_msg(From, Msg, ALgroupemployee),  
	            spawn(fun()-> ejabberdex_odbc_query:set_group_lastreadid(Qgroupid,[A#groupemployee.employeeid||A<-ALgroupemployee]) end)            
	        end,
	        IQ#iq{type = result, sub_el = [{xmlelement, "groupchat", [{"xmlns", ?NS_GROUP}],[{xmlcdata,Msgid}]}]}
    	end
    end,    
    ReIQ
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
  end.
%%%----------------------------------------------------------------------
%%% 5.2.1	Á÷½»»»ÇëÇó
%%% ¿Í»§¶Ë½øÈëÈºÁÄÄ£¿éºó£¬¿É·¢ÆðÁ÷½»»»£¨ÒôÆµ¡¢ÊÓÆµ£©ÇëÇó¡£ÇëÇó¾ßÌåÄÚÈÝ¶¨ÒåÎª< rawrequest/>µÄÊôÐÔ£¬ÆäÖÐÈººÅÎªÊôÐÔgroupid£¬ÇëÇóµÄÁ÷½»»»ÀàÐÍÎªrawtype£¬½¨ÒéÀàÐÍ¶¨ÒåÎªÒôÆµ-audio¡¢ÊÓÆµ-video¡¢ÒôÆµÊÓÆµ-audiovideo¼°ÆäËü×Ô¶¨ÒåÀàÐÍ¡£
%%% From ·¢ËÍ·½JID
%%% IQ ·¢ËÍÀ´µÄXMLÄÚÈÝ£¬Àý£º
%%% <iq from='userloginname@example.com/xxxxxx123456' type='set' id='XXXXXX'>
%%%   <rawrequest xmlns='http://im.private-en.com/namespace/group' groupid='100000' rawtype='audiovideo'>
%%%   </rawrequest>
%%% </iq>
%%% ·µ»ØÖµ IQ½Ú
rawrequest(From, To, #iq{sub_el = SubEl} = IQ) ->
  try
    Operator = From#jid.luser ++ "@" ++ From#jid.lserver,    
    
    Qgroupid = xml:get_tag_attr_s("groupid", SubEl),
    Qrawtype = string:to_lower(xml:get_tag_attr_s("rawtype", SubEl)),

    %ÅÐ¶ÏOperatorÊÇ·ñÒÑÊÇ³ÉÔ±
    IsGroupMember = is_groupmember(Qgroupid, Operator),
    %ÅÐ¶Ï¸ÃÈºÊÇ·ñÒÑ¿ªÊ¼Ò»¸öÁ÷½»»»
    ALgroup_raw_swap = mnesia:dirty_index_match_object(#group_raw_swap{groupid=Qgroupid, jid="", _ = '_'}, groupid),
    ALim_group_raw_swap = ejabberdex_odbc_query:get_group_raw_swap(Qgroupid),
    
    SelfNodeStr = atom_to_list(node()),
    ReIQ = if
      IsGroupMember == false ->
        IQ#iq{type = error, sub_el = [SubEl, ?ERR_FORBIDDEN]};
      ALim_group_raw_swap == [] ->
        %%Î´¿ªÊ¼Ò»¸öÁ÷½»»»£¬ÉêÇëÒ»¸öÁ÷·þÎñÆ÷£¬½«IQ×ªÖÁ¸Ã·þÎñÆ÷¼ÌÐø´¦Àí
        {ANode, _AServer, _APort} = mod_offlinefile_odbc:querymediaserver("group_raw_swap", Qgroupid),
        ejabberdex_odbc_query:update_group_raw_swap(Qgroupid, Qrawtype, ANode),
        case ANode of
        N when N == SelfNodeStr ->
          rawrequest(From, To, IQ);
        N ->
          ejabberdex_cluster_router:route(N, Operator, From#jid.resource, {From, To, jlib:iq_to_xml(IQ#iq{sub_el = [SubEl]})}),
          ignore
        end;
      element(3, hd(ALim_group_raw_swap)) /= SelfNodeStr ->
        ejabberdex_cluster_router:route(element(3, hd(ALim_group_raw_swap)), Operator, From#jid.resource, {From, To, jlib:iq_to_xml(IQ#iq{sub_el = [SubEl]})}),
        ignore;
      ALgroup_raw_swap == [] ->
        %¼ÇÂ¼Èº¿ªÊ¼Ò»¸öÁ÷½»»»
        mnesia:dirty_write(#group_raw_swap{groupid = Qgroupid, rawtype = Qrawtype, jid = ""}),
        %È¡³öÈºÖÐÓÃ»§£¬È¥µô×Ô¼º
        ALgroupemployee1 = get_groupemployee(Qgroupid),
        ALgroupemployee = lists:filter(fun(EItemX) -> EItemX#groupemployee.employeeid /= Operator end, ALgroupemployee1),
        %·¢ËÍPres
        Pres = {xmlelement, "presence", [], [SubEl]},
        send_msg(From, Pres, ALgroupemployee),
        IQ#iq{type = result, sub_el = []};
      true ->  %ÒÑ´¦ÔÚÁ÷½»»»¹ý³ÌÖÐ£¬Ö±½Ó·µ»Øµ±Ç°ÕýÔÚ½øÐÐÁ÷½»»»ÀàÐÍ
        [Agroup_raw_swap|_] = ALgroup_raw_swap,
        IQ#iq{type = result, sub_el = [xml:replace_tag_attr("rawtype", Agroup_raw_swap#group_raw_swap.rawtype, SubEl)]}
    end,
    
    ReIQ
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
  end.
%%%----------------------------------------------------------------------
%%% 5.2.3	ÍË³öÁ÷½»»»
%%% ¿Í»§¶ËÍË³öÁ÷½»»»Ê±£¨±ÈÈçÍË³öÒôÆµ¡¢ÊÓÆµ»áÒé£©£¬Ó¦·¢ËÍÒ»¸öº¬ÓÐrawquitµÄIQ½Ú£¬ÒÔÍ¨Öª·þÎñÆ÷¼°ÆäËü¿Í»§¶Ë¡£
%%% From ·¢ËÍ·½JID
%%% IQ ·¢ËÍÀ´µÄXMLÄÚÈÝ£¬Àý£º
%%% <iq from='userloginname@example.com/xxxxxx123456' type='set' id='XXXXXX'>
%%%   <rawquit xmlns='http://im.private-en.com/namespace/group' groupid='100000'>
%%%   </rawquit>
%%% </iq>
%%% ·µ»ØÖµ IQ½Ú
rawquit(From, To, #iq{sub_el = SubEl} = IQ) ->
  try
    Operator = From#jid.luser ++ "@" ++ From#jid.lserver,    
    
    Qgroupid = xml:get_tag_attr_s("groupid", SubEl),

    %ÈºµÄÁ÷½»»»¼ÇÂ¼
    ALgroup_raw_swap = mnesia:dirty_index_match_object(#group_raw_swap{groupid=Qgroupid, _ = '_'}, groupid),
    ALim_group_raw_swap = ejabberdex_odbc_query:get_group_raw_swap(Qgroupid),
    
    SelfNodeStr = atom_to_list(node()),
    ReIQ = case ALim_group_raw_swap of
    [Aim_group_raw_swap|_] when element(3, Aim_group_raw_swap) /= SelfNodeStr ->
      ejabberdex_cluster_router:route(element(3, Aim_group_raw_swap), Operator, From#jid.resource, {From, To, jlib:iq_to_xml(IQ#iq{sub_el = [SubEl]})}),
      ignore;
    _ ->
      %Èç¹û½öÁ½Ìõ£¬ËµÃ÷¸Ã´Î½»»»½öÊ£ÏÂÒ»¸ö£¬É¾³ýÈ«²¿Êý¾Ý£¬·ñÔò½öÉ¾³ý×ÔÉí
      if 
        length(ALgroup_raw_swap) =< 2 ->
          lists:foreach(fun(EItem) -> mnesia:dirty_delete_object(EItem) end, ALgroup_raw_swap),
          ejabberdex_odbc_query:del_group_raw_swap(Qgroupid);
        true ->
          Pres = {xmlelement, "presence", [{"from", From}], [SubEl]},
          lists:foreach(fun(EItem) -> 
                          if 
                            EItem#group_raw_swap.jid == Operator -> 
                              mnesia:dirty_delete_object(EItem); 
                            EItem#group_raw_swap.jid == "" ->
                              continue;
                            true -> 
                              ejabberd_sm:route(From, jlib:string_to_jid(EItem#group_raw_swap.jid), Pres)
                          end
                        end, 
                        ALgroup_raw_swap)
      end,      
      IQ#iq{type = result, sub_el = []}
    end,
    
    ReIQ
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
  end.
  
%%%----------------------------------------------------------------------
%%% 3.4.2	ÉÏ´«
%%% From ·¢ËÍ·½JID
%%% IQ ·¢ËÍÀ´µÄXMLÄÚÈÝ£¬Àý£º
%%% <iq from='userloginname@example.com/xxxxxx123456' type='set' id='XXXXXX'>
%%%   <takesharefile xmlns='http://im.private-en.com/namespace/group' 
%%%     groupid='100000'
%%%     filehashvalue='04E6C4F1B181AA52FA26786C2094B3C3'
%%%     filename='abcd.doc'>
%%%   </takesharefile>
%%% </iq>
%%% ·µ»ØÖµ IQ½Ú
takesharefile(From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    Operator = From#jid.luser ++ "@" ++ From#jid.lserver,    
    
    GroupID = xml:get_tag_attr_s("groupid", SubEl),
    FileHashValue = xml:get_tag_attr_s("filehashvalue", SubEl),
    FileName = xml:get_tag_attr_s("filename", SubEl),
    
    %²éÕÒÎÄ¼þÊÇ·ñ´æÔÚ
    case ejabberdex_odbc_query:get_lib_file(FileHashValue) of
    [Alib_files|_] -> %Èô´æÔÚ
      %Èç¹ûÎÄ¼þÎªÁÙÊ±ÎÄ¼þÔòÐÞ¸ÄÎÄ¼þÎªÈº¹²ÏíÎÄ¼þ£¨ÓÀ¾Ã£©
      Asql = ejabberdex_odbc_query:update_lib_files_sql(Alib_files#lib_files{savelevel = "2", lastdate = date()}),
      ejabberdex_odbc_query:sql_transaction(Asql),
      %È¡µÃÎÄ¼þ´óÐ¡
      Connection = mongo_pool:get(?MONGOPOOL),
      FilePid = gridfs:do(safe, master, Connection, ?MONGODBNAME, fun()->
        Pid = gridfs:find_one(?MONGOCOLLECTION, {'_id', {bson:to_bin(Alib_files#lib_files.filepath)}}),
        gridfs_file:set_timeout(Pid, 60000),
        Pid 
    	end),
    	{ok, FileSize} = gridfs_file:file_size(FilePid),
    	gridfs_file:close(FilePid),
      %¼ÇÂ¼
      ejabberdex_odbc_query:ins_groupshare_file(#groupshare_file{groupid = GroupID, fileid = FileHashValue, filename = FileName, addstaff = Operator, adddate = date(), filesize = FileSize}),
      %¸ø³ý¿ª×Ô¼ºÍâµÄËùÓÐ³ÉÔ±·¢ÎÄ¼þÉÏ´«Í¨Öª
      ALowner_groupemployee = get_groupemployee(GroupID, Operator),
      ALgroupemployee = lists:delete(ALowner_groupemployee, get_groupemployee(GroupID)),
      %·¢ËÍÏûÏ¢
      Pres = {xmlelement, "presence", [], 
              [{xmlelement, "takesharefile", [{"xmlns", ?NS_GROUP}], 
                [{xmlelement, "item", 
                  [{"groupid", GroupID},
                   {"employeeid", Operator},
                   {"employeename", get_employeename(Operator)},
                   {"state","add"},
                   {"filename", FileName}], []} 
                ]}]},
      send_msg(Pres, ALgroupemployee);
    _ ->              %Èô²»´æÔÚ
      continue
    end,
    
    IQ#iq{type = result, sub_el = []}
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
  end.

%%%----------------------------------------------------------------------
%%% 3.4.4	É¾³ý
%%% From ·¢ËÍ·½JID
%%% IQ ·¢ËÍÀ´µÄXMLÄÚÈÝ£¬Àý£º
%%% <iq from='userloginname@example.com/xxxxxx123456' type='set' id='XXXXXX'>
%%%   <delsharefile xmlns='http://im.private-en.com/namespace/group' 
%%%     groupid='100000'
%%%     filehashvalue='04E6C4F1B181AA52FA26786C2094B3C3'>
%%%   </delsharefile>
%%% </iq>
%%% ·µ»ØÖµ IQ½Ú
delsharefile(_From, _To, #iq{sub_el = SubEl} = IQ) ->
  try
    %Operator = From#jid.luser ++ "@" ++ From#jid.lserver,    
    
    GroupID = xml:get_tag_attr_s("groupid", SubEl),
    FileHashValue = xml:get_tag_attr_s("filehashvalue", SubEl),
    
    %É¾³ýÈº¹²Ïí¼ÇÂ¼
    ejabberdex_odbc_query:del_groupshare_file(GroupID, FileHashValue),
    %É¾³ýÈº¹²ÏíÎÄ¼þ
    ejabberdex_c2c_odbc:del_lib_files_sharefile(FileHashValue),
    
    IQ#iq{type = result, sub_el = []}
  catch 
  	Ec:Ex ->  
  	        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
	IQ#iq{type =error, sub_el = [SubEl, ?ERR_INTERNAL_SERVER_ERROR]}
  end.
%%%----------------------------------------------------------------------