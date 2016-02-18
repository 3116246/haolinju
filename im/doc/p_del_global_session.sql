DELIMITER //

drop procedure if exists p_del_global_session //
create procedure p_del_global_session (
-- 删除global_session
  in v_node varchar(200)
)
begin 
  repeat
    delete from global_session where node=v_node limit 1000;    
  until row_count() <= 0 end repeat;
end //
 
DELIMITER ;
