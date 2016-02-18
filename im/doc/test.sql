DELIMITER //
create procedure pro10()
     begin
     declare i int;
     set i=0;
     while i<1001 do
         insert into spool(username, xml) values ('username@LServer', 'test');
         set i=i+1;
     end while;
     end;
//
DELIMITER ;