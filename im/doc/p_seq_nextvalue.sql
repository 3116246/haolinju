DELIMITER //

drop procedure if exists p_seq_nextvalue //
create procedure p_seq_nextvalue (
-- 取得指定表、列的下一个序列值
  in v_table_name varchar(200),
  in v_col_name varchar(200),
  in v_isquiet numeric(1),
  out v_nextvalue numeric(30))
begin 
  declare v_count numeric(8) default 0;
  
  start  transaction;
  select count(*) into v_count from im_sys_seq where table_name=v_table_name and col_name=v_col_name;
  if v_count = 0 then
    insert into im_sys_seq (table_name, col_name, name, curr_value, step)
    values (v_table_name, v_col_name, concat(v_table_name, '_', v_col_name), 0, 1);
  end if;

  update im_sys_seq set curr_value=curr_value+step
  where table_name=v_table_name and col_name=v_col_name;

  select curr_value into v_nextvalue from im_sys_seq where table_name=v_table_name and col_name=v_col_name;
  commit;

  if v_isquiet = 0 then
    select v_nextvalue nextvalue from dual;
  end if;
END //
 
DELIMITER ;
