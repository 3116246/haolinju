DELIMITER //

drop trigger if exists t_global_session_after_d ;

create trigger t_global_session_after_d after delete
on global_session for each row
begin
  update global_session_his set logout_date=now()
  where us=old.us and res=old.res and logout_date is null;  
end;

//
DELIMITER ;