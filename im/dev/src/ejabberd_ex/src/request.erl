-module(request).
-compile(export_all).

-include("../../ejabberd-2.1.11/include/ejabberd.hrl").
getparameter(Request,Parameter,Encode)->
   case yaws_api:getvar(Request,Parameter) of
      {ok,Value} ->
           Ecodestr = getencode(Value),
           if Ecodestr==gbk->iconv:convert("gbk", Encode,Value);
           true->
                 iconv:convert("utf-8", Encode,Value)
           end;
      undefined ->
             Queryvalue = yaws_api:queryvar(Request,Parameter),
             case Queryvalue of 
                   {ok,Value} -> 
                      Ecodestr = getencode(Value),
                      if Ecodestr==gbk->iconv:convert("gbk", Encode,Value);
                      true->
						              iconv:convert("utf-8", Encode,Value)
                      end;
                   undefined ->  "null"
             end
   end
.

getparameter(Request,Parameter)->
   case yaws_api:getvar(Request,Parameter) of
      {ok,Value} ->
           Ecodestr = getencode(Value),
           if Ecodestr==gbk->Value;
           true->
              iconv:convert("utf-8", "gbk",Value)
           end;
      undefined ->
             Queryvalue = yaws_api:queryvar(Request,Parameter),
             case Queryvalue of 
                   {ok,Value} -> 
                      Ecodestr = getencode(Value),
                      if Ecodestr==gbk->Value;
                      true->
                         iconv:convert("utf-8", "gbk",Value)
                      end;
                   undefined ->  "null"
             end
   end
.
%%按GBK编码解析出新增的数据信息
parse_insertrows(Datastr)->   
   {_,Lst} = regexp:split(hd(Datastr),"insertrows:\\[\\{"),  
   if length(Lst)<2 ->[];
   true             ->
       Insertdatas = hd(tl(Lst)),
       {_,Insertdatas2}=regexp:split(Insertdatas,"\\}\\]"),
       {_,Rows}=regexp:split(hd(Insertdatas2),"}|{"),
       transtuplelst(Rows,[])
   end   
.
%%按GBK编码解析出新增的数据信息
parse_insertrows(Datastr,F_outorder)->
   {_,Lst} = regexp:split(hd(Datastr),"insertrows:\\[{"),
   if length(Lst)<2 ->[];
   true             ->
       Insertdatas = hd(tl(Lst)),
       {_,Insertdatas2}=regexp:split(Insertdatas,"\\}\\]"),
       {_,Rows}=regexp:split(hd(Insertdatas2),"}|{"),
       transtuplelst(Rows,[],F_outorder)
   end   
.
%%按GBK编码解析出编辑的数据信息
parse_editrows(Datastr)->
   {_,Lst} = regexp:split(hd(Datastr),"editrows:\\[\\{"),
   if length(Lst)<2 ->[];
   true             ->
       Insertdatas = hd(tl(Lst)),
       {_,Insertdatas2}=regexp:split(Insertdatas,"\\}\\]"),
       {_,Rows}=regexp:split(hd(Insertdatas2),"}|{"),
       transtuplelst(Rows,[])
   end
.
%%按GBK编码解析出编辑的数据信息
parse_editrows(Datastr,F_outorder)->
   {_,Lst} = regexp:split(hd(Datastr),"editrows:\\[\\{"),
   if length(Lst)<2 ->[];
   true             ->
       Insertdatas = hd(tl(Lst)),
       {_,Insertdatas2}=regexp:split(Insertdatas,"\\}\\]"),
       {_,Rows}=regexp:split(hd(Insertdatas2),"}|{"),
       transtuplelst(Rows,[],F_outorder)
   end
.
%%按GBK编码解析出删除的数据信息
parse_deleterows(Datastr)->
   {_,Lst} = regexp:split(hd(Datastr),"deleterows:"),
   if length(Lst)<2 ->[];
   true             ->
       Insertdatas = hd(tl(Lst)),
       {_,Rows}=regexp:split(Insertdatas,","),
       Rows
   end   
.
%%按GBK编码解析出删除的数据信息
parse_deleterows(Datastr,F_outorder)->
   {_,Lst} = regexp:split(hd(Datastr),"deleterows:\\[\\{"),
   if length(Lst)<2 ->[];
   true             ->
       Insertdatas = hd(tl(Lst)),
       {_,Insertdatas2}=regexp:split(Insertdatas,"\\}\\]"),
       {_,Rows}=regexp:split(hd(Insertdatas2),"}|{"),
       transtuplelst(Rows,[],F_outorder)
   end
.
%%F_outorder:字段值的输出顺序[]
transtuplelst(Rs,Lst,F_outorder)->
   if Rs==[] -> Lst;
   true      ->
       First_element = hd(Rs),
       if First_element /= "," ->       
	       TempLst = [getposotionvalue(First_element,Element)||Element<-F_outorder],
         
	       transtuplelst(tl(Rs),[TempLst|Lst],F_outorder);
	     true ->
	        transtuplelst(tl(Rs),Lst,F_outorder)
       end
   end
.
getposotionvalue(Source_str,F_name)->
   case regexp:first_match(Source_str,F_name++":(.*),?") of
      {nomatch} ->
          "";
      {match,S,L} ->
         {_,Lst}=regexp:split(string:substr(Source_str,S,L),"[:|,]"),
         if Lst/=[] ->
            Result=element(2,list_to_tuple(Lst)),
            Intl = string:len(Result),
            if Intl>1 ->  %%获取最后一个字符，判断是否是","
	            Lastchar = string:sub_string(Result,Intl-1,Intl);
	          true  ->
	            Lastchar = Result
	          end,
	          if Lastchar=="," ->
	               X=string:substr(Result,1,Intl-1);
	            true ->
	               X=Result
	          end,
	          {_,X1}=regexp:split(X,"\""),
	          if length(X1)>=3 ->
	             lists:nth(2,X1);
	          true->
	             X
	          end;
         true ->
            ""
         end;
      _->
        ""
   end
.
transtuplelst(Rs,Lst)->
   if Rs==[] -> Lst;
   true      ->
       if hd(Rs) /= "," -> 
	       {_,Fil_lst} = regexp:split(hd(Rs),","),
	       TempLst = [fun_getelementvalue(Element)||Element<-Fil_lst],
	       transtuplelst(tl(Rs),[TempLst|Lst]);
	     true ->
	        transtuplelst(tl(Rs),Lst)
       end
   end
.
%%返回字段值
fun_getelementvalue(E)->
   {_,Tm} =regexp:split(E,":"),  
   Tmp=list_to_tuple(Tm),
   {element(2,Tmp)}
.
%%返回包含字段名和值的{}
fun_getelementtuple(E)->
   {_,Tm} =regexp:split(E,":"),  
   Tmp=list_to_tuple(Tm),
   {list_to_atom(element(1,Tmp)),element(2,Tmp)}
.

transtuple(Key,Value)->
   {list_to_atom(Key),Value}
.

returnerror(Errmsg)->
   Utf8=getencode(Errmsg),
   if Utf8==gbk->
      "({\"succeed\":false,\"msg\":\""++iconv:convert("gbk","utf-8", Errmsg)++"\"})";
   true->
      "({\"succeed\":false,\"msg\":\""++Errmsg++"\"})"
   end. 
   
return()->
   "{\"succeed\":true}". 
return(Msg)->
   Utf8=getencode(Msg),
   if Utf8==gbk->
      "({\"succeed\":true,\"msg\":\""++iconv:convert("gbk","utf-8", Msg)++"\"})";
   true->
      "({\"succeed\":true,\"msg\":\""++Msg++"\"})"
   end.    
returndata(Data)->
   "({\"succeed\":true,\"data\":[[]"++out_str(Data,"")++"]})".        
returndata(Data,OutName)->
   "({\"succeed\":true,\"data\":[{}"++out_str(Data,OutName,"")++"]})".  
   
returndata_des(Data)->
   Text = request:des_enc("[[]"++out_str(Data,"")++"]"),
   "({\"succeed\":true,\"data\":\""++Text++"\"})".        
returndata_des(Data,OutName)->
   Text = request:des_enc("[{}"++out_str(Data,OutName,"")++"]"),
   "({\"succeed\":true,\"data\":\""++Text++"\"})".  
   
%%使用第三方应用key对结果进行加密
returndata_des_appkey(Data,Appkey)->
   Text = request:des_enc("[[]"++out_str(Data,"")++"]",Appkey),
   "({\"succeed\":true,\"data\":\""++Text++"\"})". 
%%使用第三方应用key对结果进行加密          
returndata_des_appkey(Data,OutName,Appkey)->
   Text = request:des_enc("[{}"++out_str(Data,OutName,"")++"]",Appkey),
   "({\"succeed\":true,\"data\":\""++Text++"\"})".      

%%直接输出。输出utf-8编码
out_str(Lst,Str)->
   if Lst==[] -> Str;
   true->
      Fir = hd(Lst),
      if is_tuple(Fir)==true -> out_str(tl(Lst),Str++out_list(tuple_to_list(Fir),"",null));
      true->
         out_str(tl(Lst),Str++ out_list(Fir,"",null))
      end
   end
. 
%%直接输出。输出GBK编码
out_en_str(Lst,Str)->
   if Lst==[] -> Str;
   true->
      Fir = hd(Lst),
      if is_tuple(Fir)==true -> out_str(tl(Lst),Str++out_list(tuple_to_list(Fir),"",1));
      true->
         out_str(tl(Lst),Str++ out_list(Fir,"",1))
      end
   end
.    
%%指定列名输出。默认编码方式utf-8
out_str(Lst,OutName,Str)->
   if Lst==[] -> Str;
   true->
      Fir = hd(Lst),
      OutName2 = OutName,
      if is_tuple(Fir)==true -> out_str(tl(Lst),OutName2,Str++out_list(tuple_to_list(Fir),OutName2,"",null));
      true->
         out_str(tl(Lst),OutName2,Str++ out_list(Fir,OutName2,"",null))
      end
   end
.
%%指定列名输出。显式进行输出GBK编码
out_en_str(Lst,OutName,Str)->
   if Lst==[] -> Str;
   true->
      Fir = hd(Lst),
      OutName2 = OutName,
      if is_tuple(Fir)==true -> out_en_str(tl(Lst),OutName2,Str++out_list(tuple_to_list(Fir),OutName2,"",1));
      true->
         out_en_str(tl(Lst),OutName2,Str++ out_list(Fir,OutName2,"",1))
      end
   end
.

trans(A) when A==[] -> "\"\"";
trans(A1) when is_atom(A1) -> "\""++atom_to_list(A1)++"\"";
trans(A1) when is_integer(A1) -> "\""++integer_to_list(A1)++"\"";
trans(A1) when is_tuple(A1) -> "["++[trans(T)||T<-tuple_to_list(A1)]++"]";
trans(A1) when is_list(A1)  ->
   Tmp=lists:nth(1,A1),
   if is_integer(Tmp)==true->
                              Ecodestr = getencode(A1),
							                if Ecodestr==gbk->
							                    "\""++iconv:convert("gbk", "utf-8",A1)++"\"";
							                true ->
							                    "\""++A1++"\""
							                end;
   true->
      "["++[trans(T)||T<-A1]++"]"
   end
.

out_list(Lst,Result,Encode)->
   if Lst==[]-> 
      if Result/= "" -> Result++"]";
      true ->
         ""
      end;
   true ->
      Result_temp = case Result of "" -> ",[";
                    _->
           Result++","
      end,
      Fir =case is_list(Lst) of true-> hd(Lst); _-> Lst end,
      if Fir==[]->Po = Result_temp++"\"\"";
      true->
      if is_atom(Fir)==true ->Po = Result_temp++"\""++atom_to_list(Fir)++"\"";
      true ->
          if is_integer(Fir)==true ->Po=Result_temp++"\""++integer_to_list(Fir)++"\"";
          true ->
             if is_tuple(Fir)==true-> 
						             Poo = out_list(tuple_to_list(Fir),"",Encode),
						             Len = length(Poo),
						             Po=Result_temp++ string:substr(Poo,2,Len);
             true->
                 Tmp=lists:nth(1,Fir),
		             %%判断当前是字符串的list还是列表的list，如果第一个字符是用ascii码表示，则是字符串，否则为列表
	               if is_integer(Tmp)==true,Tmp>9->
	             				 Ecodestr = getencode(Fir),
					             if Encode==null ->                                
							                if Ecodestr==gbk->
							                    Po=Result_temp++"\""++iconv:convert("gbk", "utf-8",Fir)++"\"";
							                true ->
							                    Po=Result_temp++"\""++Fir++"\""
							                end;
					             true ->
							               if Ecodestr==gbk->
							                  Po=Result_temp++"\""++Fir++"\"";
							               true->
							                  Po=Result_temp++iconv:convert("utf-8","gbk", Fir)
							               end
					             end;
					       true->
						             Poo = out_list(Fir,"",Encode),
						             Len = length(Poo),
						             Po=Result_temp++ string:substr(Poo,2,Len)
	               end %%end is_integer
             end %% end is_tuple
          end%end is_integer
      end %%end is_atom
      end,%%end []      
      out_list(case is_list(Lst) of true->tl(Lst) ;_-> [] end,Po,Encode)
   end
.
out_list(Lst,OutName3,Result,Encode)->
   if Lst==[]-> 
      if Result/= "" -> Result++"}";
      true ->
         ""
      end;
   true ->
      if Result=="" -> Result_temp=",{";
      true ->
          Result_temp = Result++","
      end,
      Fir = case is_list(Lst) of true-> hd(Lst); _-> Lst end,
      Out_name = hd(OutName3),
      if Fir==[]-> Po = Result_temp++"\""++Out_name++"\":\"\"";
      true->      
		      if is_atom(Fir)==true -> Po = Result_temp++"\""++Out_name++"\":\""++atom_to_list(Fir)++"\"";
		      true ->
		          if is_integer(Fir)==true -> Po=Result_temp++"\""++Out_name++"\":\""++integer_to_list(Fir)++"\"";
		          true ->
		             if is_tuple(Fir)->  
						             Poo = out_list(tuple_to_list(Fir),"",Encode),
						             Len = length(Poo),
						             Po=Result_temp++"\""++Out_name++"\":"++ string:substr(Poo,2,Len);
		             true->
		                 Tmp=lists:nth(1,Fir),
		                 %%判断当前是字符串的list还是列表的list，如果第一个字符是用ascii码表示，则是字符串，否则为列表
	                   if is_integer(Tmp)==true,Tmp>9->
					              %%判断当前内容的编码是否是utf-8
					              Ecodestr = getencode(Fir),
					              %%没有指定输出编码时，默认为Utf-8输出
					              if Encode==null ->
							                if Ecodestr==gbk->
							                   %%不是utf-8编码串时，默认为gbk编码，并把gbk转换成utf-8输出
							                   Po=Result_temp++"\""++Out_name++"\":\""++iconv:convert("gbk", "utf-8",Fir)++"\"";
							                true ->
							                   %%是utf-8编码时，则直接输出
							                   Po=Result_temp++"\""++Out_name++"\":\""++Fir++"\""
							                end;
					              true ->
							                %%指定gbk编码输出时
							                if Ecodestr==gbk->
							                   Po=Result_temp++"\""++Out_name++"\":\""++Fir++"\"";
							                true ->
							                   Po=Result_temp++"\""++Out_name++"\":\""++iconv:convert("utf-8","gbk", Fir)++"\""
							                end
					              end; 
						       true->
						             Poo = out_list(Fir,"",Encode),
						             Len = length(Poo),
						             Po=Result_temp++"\""++Out_name++"\":"++ string:substr(Poo,2,Len)
		               end %%end is_integer		              
		            end            
		          end
		      end
		  end,
      out_list(case is_list(Lst) of true->tl(Lst) ;_-> [] end,tl(OutName3),Po,Encode)
   end
.
redirect(Url)->
   %%获取url的前7个字符，如果是http://则使用redirect
   %%否则使用本地重定向redirect_local
   case Url of
       undefined-> {error,"url is undefined"};
       _->
         Urllen = string:len(Url),
			   if Urllen<8 ->
			           {redirect_local,Url};
			      true              ->
			           Httpflag = string:substr(Url,1,7),
			           if (Httpflag =:= "http://") or (Httpflag =:= "https:/")-> {redirect,Url};
			              true                   -> {redirect_local,Url}
			           end 
         end
   end
.

getencode(Fir)->
  Ecodestr = iconv:convert("utf-8", "utf-8",Fir),
  if length(Ecodestr)/=length(Fir)->
     gbk;
  true->
     utf8
  end
.
-define(KEY,"_sddb74+").
des_enc(Text)->
  des_enc(Text,?KEY)
.
des_enc(Text,Key)->
   K = list_to_binary(Key),
   V=K,
   T1 =case getencode(Text) of gbk -> list_to_binary(iconv:convert("gbk", "utf-8",Text));
      _-> list_to_binary(Text)
   end,   
   R=crypto:des_cbc_encrypt(K, V, fillBit(T1)),
   bin_to_hexstr(R)
.

des_dectogbk(Pass)->
   V = des_dec(Pass),
   iconv:convert("utf-8", "gbk",V)
.
des_dec(Pass)->
   des_dec(Pass,?KEY)
.
des_dec(Pass ,Key)->
   K = list_to_binary(Key),
   V=K,
   try
   ByteLst = hexstr_to_bin(Pass),   
   R=crypto:des_cbc_decrypt(K, V, ByteLst),
   R1=binary_to_list(R),
   F=fun(G)->
         G>16
   end,   
   lists:filter(F,R1)  
   catch
      Ec:Ex -> 
        ?ERROR_MSG("~p:~p~n  StackTrace:~p", [Ec, Ex, erlang:get_stacktrace()]),
        null
   end 
.

fillBit(Bin)->
   L = size(Bin),
   if L rem 8 ==0 -> Bin;
   true->
      fillBit(list_to_binary([Bin,0]))
   end
.

bin_to_hexstr(Bin) ->
  lists:flatten([io_lib:format("~2.16.0B", [X]) ||
    X <- binary_to_list(Bin)]).

hexstr_to_bin(S) ->
  hexstr_to_bin(S, []).
hexstr_to_bin([], Acc) ->
  list_to_binary(lists:reverse(Acc));
hexstr_to_bin([X,Y|T], Acc) ->
  {ok, [V], []} = io_lib:fread("~16u", [X,Y]),
  hexstr_to_bin(T, [V | Acc]).


convertUTF8(Value)->
                      Ecodestr = getencode(Value),
                      if Ecodestr==gbk->iconv:convert("gbk", "utf-8",Value);
                      true->
						              Value
                      end
.