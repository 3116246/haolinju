-module(func_utils).

-compile(export_all).

-include("../../ejabberd-2.1.11/include/ejabberd.hrl").
-include("../include/mod_ejabberdex_init.hrl").
str_to_date(Date)->
  {_,L}=regexp:split(Date,"[- :]"),
  {{list_to_integer(hd(L)),
       list_to_integer(hd(tl(L))),
       list_to_integer(hd(tl(tl(L))))},
       {list_to_integer(hd(tl(tl(tl(L))))),
       list_to_integer(hd(tl(tl(tl(tl(L)))))),
       list_to_integer(hd(tl(tl(tl(tl(tl(L)))))))}}
.
  

%% 将当前时间转换成通用的时间戳
time()  ->
    %当时时间
    time(1970,1,1,0,0,0)
.
time(YYYY,MM,DD,HH,MI,SS)->
    Dlocal= calendar:universal_time_to_local_time({{YYYY,MM,DD},{HH,MI,SS}}),  %当地1970年
    D1970 = calendar:datetime_to_gregorian_seconds(Dlocal),
    Nlocal= calendar:local_time(),
    Now   = calendar:datetime_to_gregorian_seconds(Nlocal),
    integer_to_list(Now - D1970)
.
%%标准时间戳转日期
date()  ->
    date(0, 0).
date(Time)  ->
    date(Time, 0).
date(Time, Type)  ->
    if Time =:= 0   ->
            Nlocal = calendar:local_time(),
            Ntime  = calendar:datetime_to_gregorian_seconds(Nlocal);
        true    ->
            Dlocal= calendar:universal_time_to_local_time({{1970, 1, 1},{0,0,0}}),
            D1970 = calendar:datetime_to_gregorian_seconds(Dlocal),
            Ntime = D1970 + (if is_list(Time) ->
                                    list_to_integer(Time);
                                true ->
                                    Time
                            end)
    end,
    {{Y2,M2,D2},{H2,I2,S2}} = calendar:gregorian_seconds_to_datetime(Ntime),
    {{Y, M, D}, {H, I, S}}  = {{date_format(Y2),date_format(M2),date_format(D2)},{date_format(H2),date_format(I2),date_format(S2)}},
    case Type of
        1   ->
            Date = Y++"年"++M++"月"++D++"日 "++H++"时"++I++"分"++S++"秒";
        2   ->
            Date = Y++"年"++M++"月"++D++"日";
        3   ->
            Date = Y++"-"++M++"-"++D;
        4   ->
            Date = Y++M++D;
        _   ->
            Date = Y++"-"++M++"-"++D++" "++H++":"++I++":"++S
    end,
    list_to_binary(Date).
date_format(M)   ->
    if M < 10   ->
       N = "0" ++ integer_to_list(M);
       true ->
           N = integer_to_list(M)
    end,
    N.
    
getCurrentPath()->
    {ok, Path} = file:get_cwd(),
    Fixed_path = string:substr(Path,1,string:len(Path)-3),
    {ok,Fixed_path}.    
    
getNextID() ->
    {M, S, L} = now(),  
     integer_to_list(M*1000000000000+S*1000000+L).    
     
getlocalserver()->
   Sn = ?MYHOSTS,
   case Sn of [] -> "";
        _-> hd(Sn)
   end.      
getlocalserver(_Pid)->
   Sn = ?MYHOSTS,
   case Sn of [] -> "";
        _-> hd(Sn)
   end.
replaceNull(V,V1)->
   case V of 
     "null"->[];
     null->[];
     _-> V1
  end
.
getNumber([Min,Max])->
  Mi = case is_list(Min) of 
          true->list_to_integer(Min);
          _->Min
        end,
  Ma = case is_list(Max) of 
          true->list_to_integer(Max);
          _->Max
        end,                       
  if Mi==Ma -> Mi;
  true->
    getNumber(Mi,Ma)
  end
.
getNumber(Min,Max) when is_list(Min),is_list(Max)->
  getNumber([Min,Max]);
getNumber(Min,Max) when is_list(Min)->
  getNumber(list_to_integer(Min),Max);  
getNumber(Min,Max) when is_list(Max)->
  getNumber(Min,list_to_integer(Max));
getNumber(_Min,Max) when Max==0->
  0;    
getNumber(Min,Max)->
  {A,B,C}=now(),
  random:seed(A,B,C),
  random:uniform(Max-Min)+Min
.


indexOf(Element,Lst)->
    indexOf(Element,Lst,0)
.
indexOf(_,Lst,Index) when Lst==[]-> 
   Index   
;
indexOf(Element,[H|_],Index) when H==Element-> 
   if Index==0 -> 1;
   true-> Index+1 
   end
;
indexOf(Element,[H|T],Index) when H/=Element -> 
  case T of
       []->indexOf(Element,T,0);
       _->indexOf(Element,T,Index+1)   
  end
.
-record(x,{x2,x3,x5,f6,x9,x1,m}).

rll()->
        %%record_info(fields,T)
        Female = #x{x2='_',x3='_',x5='_',f6='_',x9='_',x1='_',m='_'},
        %%Female
        mnesia:dirty_select(x,[{Female,[],['$_']}])
.
add(ID)->
	Female = #x{x2=ID,x3='x3',x5='e',f6='e',x9='ee',x1='e3',m='w'},
	mnesia:dirty_write(Female)
.
isRecordX(R)->
  Rs =  hd(getAll(R)),
  is_record(Rs,R)
.
%%删除指定表的指定列。需要先更改记录定义，然后执行本函数
dropColumn(T,Cols) when is_atom(Cols)->
     Rs =  getAll(T),
     OldCols = mnesia:table_info(T,attributes),
     %%mnesia:table_info(base_dept,record_name),
     Bol =lists:member(Cols,OldCols),
     if Bol==false->
        atom_to_list(Cols)++" is not found!";
     true->
       Index = indexOf(Cols,OldCols),
       NewCols = lists:filter(fun(C)-> C/=Cols end,OldCols),
	     mnesia:clear_table(T),
	     mnesia:transform_table(T, ignore,NewCols),
	     F = fun(Row)->
	         NewRow=dropColumnValue(tuple_to_list(Row),Index+1),
	         mnesia:dirty_write(list_to_tuple(NewRow))
	     end,
	     [F(X)||X<-Rs]
     end    
.
%%删除指定行数据中的指定索引位置的值。
dropColumnValue(Row,Ind)->
    dropColumnValue(Row,Ind,1,[])
.
dropColumnValue(Row,_Ind,_I,R) when Row==[]->
    lists:reverse(R)
;
dropColumnValue([_Fc|Row],Ind,I,R) when Ind==I->
    dropColumnValue(Row,Ind,I+1,R)
;
dropColumnValue([Fc|Row],Ind,I,R) when Ind/=I->
    dropColumnValue(Row,Ind,I+1,[Fc|R])
.

%%向指定列中添加一列。需要先更改记录定义，然后执行本函数
addColumn(T,Cols) when is_list(Cols)-> 
     addColumn(T,list_to_atom(Cols),"");
addColumn(T,Cols) when is_atom(Cols)->
     addColumn(T,Cols,"").

addColumn(T,Cols,DefaultValue) when is_list(Cols)-> 
     addColumn(T,list_to_atom(Cols),DefaultValue);
addColumn(T,Cols,DefaultValue) when is_atom(Cols)->
     Rs =  getAll(T),
     OldCols = mnesia:table_info(T,attributes),
     %%mnesia:table_info(base_dept,record_name),
     Bol =lists:member(Cols,OldCols),
     if Bol==true->
        atom_to_list(Cols)++" is has!";
     true->
	     mnesia:clear_table(T),
	     %%mnesia:transform_table(T, ignore,OldCols++[Cols]),
	     mnesia:transform_table(T, ignore,OldCols++[Cols]),
	     F = fun(Row)->
	         mnesia:dirty_write(erlang:append_element(Row,DefaultValue))
	     end,
	     [F(X)||X<-Rs]
     end
.

getAll(T)->
   mnesia:dirty_select(T, [{mnesia:table_info(T, wild_pattern), [], ['$_']}])
.

getAll(T,Id)->
   [io:format("~p~n",[R])||R<-mnesia:dirty_read(T, Id)]
.

delete(T,Id)->
   mnesia:dirty_delete(T, Id)
.

jid(Account)->
    Server = func_utils:getlocalserver(),
    case Account of []-> [];
    _->
	        {_,Jid} = regexp:split(Account,"@"),
	        Len = length(Jid),
	        case Len of 1->
	            Bare = hd(Jid),	            
	            {Bare,Server};              
	        _->
	            Bare = hd(Jid),
	            Server1 = hd(tl(Jid)),
	            case regexp:match(Server1,Server)
	             of {match,1,_}->
	                {Bare,Server};
	             {match,_,_}->
	                {Bare,Server1};
	             _-> 
	                  {Bare,Server1++"."++Server}
	             end
	        end
	  end
.
jid(Account,Server)->
   {Account,Server}
.

is_jid(Jid)->
       case Jid of []-> false;
    _->
	        {_,Jd} = regexp:split(Jid,"@"),
	        Len = length(Jd),
	        case Len of 1->
	             false;
	        _->
	            true
	        end
	  end
.

getOrgRootID(Jid)->           
      Rs= ejabberdex_odbc_query:get_dept_path_byjid(Jid),
	    RootID=case Rs of
            []->[];
            [Path,_]->
                {_,Jd} = regexp:split(Path,"/"),
                lists:nth(3,Jd);   
            [[$/,$-,$1,$0,$0,$0,$0,$/|Root]|_]->
            	{_,Jd2} = regexp:split(Root,"/"),
            	lists:nth(1,Jd2);     %%parse format:/-10000/ROOT-NO
            [Deptid]->Deptid
				end,
		RootID 
.

getOrgRootIDByDeptid(DeptID)->
     DeptRs = im_organ_odbc:getdeptbyid(DeptID),
					                             if DeptRs==[] -> [];
					                             true->
					                                 Path = (hd(DeptRs))#base_dept.path,
					                                 {_,Jd} = regexp:split(Path,"/"),
                                           lists:nth(3,Jd)
					                             end   
.

println(T)->
   Rs = getAll(T),
   [io:format("~p~n",[R])||R<-Rs]
.
%%%重新整理所有部门的path属性
%resetDeptPath()->
%    Rs = getAll(base_dept),
%    Path = getDeptFullPath(Rec#base_dept.deptid,[]),
%    [ejabberdex_odbc_query:ins_dept(Rec#base_dept.deptid,Rec#base_dept.deptname,Rec#base_dept.pid,Path,Rec#base_dept.noorder,Path,Rec#base_dept.manager,Path,Rec#base_dept.remark) || Rec<-Rs]
%.
%%%重新整理所有机构的顺序号
%resetDeptOrderNo()->
%    Rs = getAll(base_dept),
%    [updateDeptOrderNo(im_organ_odbc:getdeptbypid(Rec#base_dept.deptid),1) || Rec<-Rs]
%.

updateDeptOrderNo(DeptRs,_OrderNo) when DeptRs==[]->
    ok;
updateDeptOrderNo([Rec|T],OrderNo)->
    ejabberdex_odbc_query:ins_dept(Rec#base_dept.deptid,Rec#base_dept.deptname,Rec#base_dept.pid,Rec#base_dept.path,integer_to_list(OrderNo),Rec#base_dept.manager,Rec#base_dept.remark),
    updateDeptOrderNo(T,OrderNo+1)
.

getDeptFullPath(Deptid,FullPath)->
    if Deptid=="-10000"-> FullPath;
    true->
        Rs = ejabberdex_odbc_query:get_dept_by_id(Deptid),
        case Rs of []-> "-10000/"++FullPath;
        _->
		        Path = (hd(Rs))#base_dept.pid,
		        case FullPath of []->
		            getDeptFullPath((hd(Rs))#base_dept.pid,Path);
		        _->
		            getDeptFullPath((hd(Rs))#base_dept.pid,Path++"/"++FullPath)
		        end
        end
    end
.
substring(Str,Pos,Len)->
  substring(Str,Pos,1,Len,1,[])
.
substring(Str,_Pos,_CurPos,_Len,_CurLen,CurStr) when Str==[]->
  lists:reverse( CurStr)
;
substring(_Str,_Pos,_CurPos,Len,CurLen,CurStr) when Len<CurLen->
  lists:reverse( CurStr)
;
substring([Str|T],Pos,CurPos,Len,CurLen,_CurStr)when Pos>CurPos->
  Ascii = Str,
  if Ascii>=228 ->
     substring(tl(tl(T)),Pos,CurPos+1,Len,CurLen,[]);
  true->
     substring(T,Pos,CurPos+1,Len,CurLen,[])
  end
;
substring([Str|T],Pos,CurPos,Len,CurLen,CurStr)->
  Ascii = Str,
  if Ascii>=228 ->
     F1 = [Str|CurStr],
     F2 = [hd(T)|F1],
     F3 = [hd(tl(T))|F2],
     substring(tl(tl(T)),Pos,CurPos,Len,CurLen+1,F3);
  true->
     substring(T,Pos,CurPos,Len,CurLen+1,[Str|CurStr])
  end
.
%%授权码验证。未通过返回{no,R};通过返回{Appid,Orgid,Userid,Seesionid}
auth(Code) when Code==[]->
   {error,"unauthorized"}
;
auth(Code)->
   {_,D} =regexp:split(Code,","),
   case length(D) of 1 -> {error,"unauthorized"};
       _->
          [AppId,Code1] = D,
          auth(AppId,Code1)
   end
.
auth(AppId,Code)->
          case Code of []-> {error,"unauthorized"};
          _->
		          CodeText=request:des_dec(Code),
		          {_,D2} =regexp:split(CodeText,","),
		          if length(D2)/=5 -> {error,"unauthorized"};
		          true->
		             [Orgid,Userid,Pass,Session,Appkey] = D2,
		             {AppId,Orgid,Userid,Pass,Session,Appkey}
		          end
          end
.
%%验证第三方应用
%%使用外部应用的appkey对授权码解密
%%授权码的内容应包括：应用号，加密串(企业号，用户帐号,密码,sessionID，none)
outsideAuth(Code,Key)->
   {_,D} =regexp:split(Code,","),
   case length(D) of 1 -> {error,"unauthorized"};
       _->
              [AppId,Code1] = D,
		          CodeText=request:des_dec(Code1,Key),
		          {_,D2} =regexp:split(CodeText,","),
		          if length(D2)/=5 -> {error,"unauthorized"};
		          true->
		             [Orgid,Userid,Pass,Session,_Appkey] = D2,
		             %%根据企业号，获取对应的域
		             case ejabberdex_odbc_query:get_enterprise_by_eno(Orgid) of
		                 []-> {error,"unauthorized"};
		                 [Rec]->
		                     {AppId,Rec#enterprise_reg.subdomain,Userid,Pass,Session,Key}
		             end
		          end
   end
.

getServerAddress()->
   R=case ejabberdex_odbc_query:get_syscode_by_type("domain") of
    []-> "fafaim.com";
    [Rec]->
       Rec#syscode.code;
    Rs->
       Rec=hd(Rs),
       Rec#syscode.code
   end,
   case R of []-> "fafaim.com";
   _-> R
   end
.

lists_delete([_H|T],Index,_Result) when Index==1->
    T
;
lists_delete(Item,Index,Result)->
    lists_delete(Item,Index,Result,1)
.

lists_delete([_H|T],Index,Result,Pos)when Index==Pos->
    lists_delete(T,Index,Result,Pos+1)
;
lists_delete([H|T],Index,Result,Pos)->
    lists_delete(T,Index,[H|Result],Pos+1)
;
lists_delete([],_Index,Result,_Pos)->
    lists:reverse(Result)
.
%%初始化机构模板数据
initOrgTemplate()->
   %%Delete=#syscode{codetype = "template_org", _ = '_'},
   %%mnesia:dirty_delete_object(Delete),   
   OrgType="民营企业",  
   syscode:put([OrgType,1],"高层管理","template_org"), 
   syscode:put([OrgType,2],"人力资源部","template_org"),
   syscode:put([OrgType,3],"财务部","template_org"),
   syscode:put([OrgType,4],"销售部","template_org"),
   syscode:put([OrgType,5],"行政部","template_org"),   
   OrgType2="国有企业", 
   syscode:put([OrgType2,1],"公司领导","template_org"),  
   syscode:put([OrgType2,2],"人力资源部","template_org"),
   syscode:put([OrgType2,3],"财务部","template_org"),
   syscode:put([OrgType2,4],"监察审计部","template_org"),
   syscode:put([OrgType2,5],"科技信息部","template_org"),
   syscode:put([OrgType2,6],"办公室","template_org"),   
   syscode:put("template_org_list",[OrgType,OrgType2],"template_org")
   %%mnesia:dirty_write(#syscode{code="template_org_list",desc=[OrgType,OrgType2],codetype="template_org"})
.

