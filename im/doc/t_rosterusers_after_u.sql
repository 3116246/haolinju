DELIMITER //

drop trigger if exists t_rosterusers_after_u ;

create trigger t_rosterusers_after_u after update
on rosterusers for each row
begin
  declare v_sql_text text;
    
  -- 同步至we
  if substring_index(user(),'@',1) <> 'we_rep' then
    if new.subscription <> 'N' then
      select concat('call p_attenbyjid(\'', new.username, '\', \'', new.jid, '\') ')
      into v_sql_text
      from dual;    
      insert into log_rep_disp(sql_text, target_service, create_date) values(v_sql_text, 'we', current_timestamp());
    end if;
  end if;
  
end;

//
DELIMITER ;