-module(sendmail).

-export([sina/0,qq/0,fafa/3]).


	send_no_receive(Conn,Socket, Data) ->
	    %%io:format("return:~p~n", [Data]),
	    Conn:send(Socket, Data ++ "\r\n").
	send(Conn,Socket, Data) ->
	    Conn:send(Socket, Data ++ "\r\n"),    
	    %%io:format("send:~p~n", [Data]),
	    recv(Conn,Socket).
	recv(Conn,Socket) ->
	    case Conn:recv(Socket, 0, 15000) of {ok, _Return} ->
	        %%io:format("return:~p~n", [Return])
	        ok;
	    {error, Reason} -> io:format("ERROR: ~p~n", [Reason])
	end.
fafa(To,Subject,Msg)->
   connect("mail.fafatime.com","webadmin@fafatime.com","J3*3UgBfh*u",To,Subject,Msg)
.	
sina()->
    %%connect("smtp.sina.com","liling5071@sina.com","liling","liling5071@sina.com")
    test
.	
qq()->
    %%connect("smtp.qq.com","3116246@qq.com","......","3113627846@qq.com")
    test
.	
connect(SmtpServer,U,P,To,Subject,Msg) ->
    {ok, Socket} = gen_tcp:connect(SmtpServer, 25, [{active, false}], 15000),    
    recv(gen_tcp,Socket),    
    send(gen_tcp,Socket, "EHLO "++SmtpServer),    
    send(gen_tcp,Socket, "AUTH LOGIN"),    
    send(gen_tcp,Socket, binary_to_list(base64:encode(U))),    
    send(gen_tcp,Socket, binary_to_list(base64:encode(P))),    
    send(gen_tcp,Socket, "MAIL FROM: "++U),    
    send(gen_tcp,Socket, "RCPT TO:"++To),    
    send(gen_tcp,Socket, "DATA"),    
    send_no_receive(gen_tcp,Socket, "From: "++U),    
    send_no_receive(gen_tcp,Socket, "To: "++To),    
    send_no_receive(gen_tcp,Socket, "Date:"++binary_to_list(func_utils:date())),    
    send_no_receive(gen_tcp,Socket, "Subject:=?GB2312?B?"++binary_to_list(base64:encode(Subject))++"?="),
    send_no_receive(gen_tcp,Socket, "Content-Type: text/html;charset=GB2312"), 
    send_no_receive(gen_tcp,Socket, "Content-Transfer-Encoding:base64"), 
    
    send_no_receive(gen_tcp,Socket, ""),    
    send_no_receive(gen_tcp,Socket, binary_to_list(base64:encode(Msg))),     
    send_no_receive(gen_tcp,Socket, ""),
    send(gen_tcp,Socket, "."),    
    send(gen_tcp,Socket, "QUIT"),    
    gen_tcp:close(Socket).		
	
%%FIX	
%connect_ssl(SmtpServer,U,P,To) ->
%    {ok, Socket} = ssl:connect("smtp.gmail.com", 465, [{active, false}], 1000),    
%    recv(ssl,Socket),    
%    send(ssl,Socket, "HELO localhost"),    
%    send(ssl,Socket, "AUTH LOGIN"),    
%    send(ssl,Socket, binary_to_list(base64:encode("___@gmail.com"))),    
%    send(ssl,Socket, binary_to_list(base64:encode("johngalt"))),    
%    send(ssl,Socket, "MAIL FROM: <___@gmail.com>"),    
%    send(ssl,Socket, "RCPT TO:<___@gmail.com>"),    
%    send(ssl,Socket, "DATA"),    
%    send_no_receive(ssl,Socket, "From: <___@gmail.com>"),    
%    send_no_receive(ssl,Socket, "To: <___@gmail.com>"),    
%    send_no_receive(ssl,Socket, "Date: Tue, 15 Jan 2008 16:02:43 +0000"),    
%    send_no_receive(ssl,Socket, "Subject: Test message"),    
%    send_no_receive(ssl,Socket, ""),    
%    send_no_receive(ssl,Socket, "This is a test"),    
%    send_no_receive(ssl,Socket, ""),    
%    send(ssl,Socket, "."),    
%    send(ssl,Socket, "QUIT"),    
%    ssl:close(Socket).	

