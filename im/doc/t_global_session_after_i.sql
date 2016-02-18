DELIMITER //

drop trigger if exists t_global_session_after_i ;

create trigger t_global_session_after_i after insert
on global_session for each row
begin
  declare v_count numeric(8) default 0;
  
  select count(*) into v_count from global_session_his a where a.us=new.us and a.res=new.res and a.login_date>=adddate(new.login_date,interval -10 minute);
  if v_count = 0 then
  	select count(*) into v_count from global_session_his a where a.us=new.us and a.res=new.res and a.logout_date=new.login_date;
  	if v_count = 0 then
    	insert into global_session_his(us, res, login_date) values (new.us, new.res, new.login_date); 
    else
    	update global_session_his set logout_date=null where us=new.us and res=new.res and logout_date=new.login_date;
    end if;
  else
    update global_session_his set logout_date=null where us=new.us and res=new.res and login_date>=adddate(new.login_date,interval -10 minute);
  end if;  
end;

//
DELIMITER ;