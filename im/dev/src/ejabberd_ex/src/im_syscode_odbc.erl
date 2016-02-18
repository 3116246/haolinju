-module(im_syscode_odbc).

-export([put/3,get/1]).
-include("../include/mod_ejabberdex_init.hrl").

put(Key,Value,CodeType)->
    %%mnesia:dirty_write(#syscode{code=Key,desc=request:convertUTF8(Value),codetype=CodeType})
    ejabberdex_odbc_query:ins_syscode(Key,Value,CodeType)
.

get(A)->
  Type= request:getparameter(A,"codetype","utf-8"),
  Rs=ejabberdex_odbc_query:get_syscode_by_type(Type),
  request:returndata(Rs,["table","code","desc","codetype"])
.
