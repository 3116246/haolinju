DELIMITER //

drop trigger if exists t_im_groupemployee_after_i ;

create trigger t_im_groupemployee_after_i after insert
on im_groupemployee for each row
begin
  declare v_sql_text text;
  declare v_fafa_jid varchar(200);
  declare v_fafa_groupid varchar(20);
  declare v_grouprole varchar(20);
  declare v_groupclass varchar(20);
  declare v_employeeid varchar(50);
  -- 同步至we
  -- if substring_index(user(),'@',1) <> 'we_rep' then
    select groupclass
    into v_groupclass
    from im_group
    where groupid=new.groupid;
    insert into log_rep_disp(sql_text, target_service, create_date) values('test---------', 'we', current_timestamp());
    -- 如果是普通群组
     if v_groupclass not in ('meeting', 'circlegroup') then
      select REPLACE(REPLACE(REPLACE(new.employeeid,'/FaFaWin',''),'/FaFaIPhone',''),'/FaFaAndroid','') into v_employeeid;
      select concat('insert into we_group_staff(group_id, login_account) ', 
                    'select t1.group_id, t2.login_account ',
                    'from (select max(group_id) group_id from we_groups where fafa_groupid=\'', new.groupid, '\' ) as t1,',
                    '     (select max(login_account) login_account from we_staff where fafa_jid=\'', v_employeeid, '\' ) as t2 ',
                    'where t1.group_id is not null and t2.login_account is not null ',
                    '  and not exists(select 1 from we_group_staff where group_id=t1.group_id and login_account=t2.login_account)')
      into v_sql_text
      from dual;    
      insert into log_rep_disp(sql_text, target_service, create_date) values(v_sql_text, 'we', current_timestamp());
      
      -- 更新群主
      if new.grouprole='owner' then
        select concat('update we_groups set ',
                      '  create_staff=(select max(login_account) from we_staff where fafa_jid=\'', new.employeeid, '\' ) ', 
                      'where fafa_groupid=\'', new.groupid, '\'')
        into v_sql_text
        from dual;    
        insert into log_rep_disp(sql_text, target_service, create_date) values(v_sql_text, 'we', current_timestamp());
        
      end if;
    
     end if;
    
  -- end if;
  
end;

//
DELIMITER ;