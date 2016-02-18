DELIMITER //

drop procedure if exists onlinestatistical //
CREATE PROCEDURE onlinestatistical(eno varchar(20),pageno int,pagesize int,resource varchar(20))
BEGIN
   declare totalEno int default(0);
   declare totalUser int default(0);
   declare startRec int default(0);
   set startRec = ((pageno-1)*pagesize);
   set @s1 = startRec;
   set @s2 = pagesize;
  -- gloable_session
  if eno='' then
       set @stmt=' SELECT count( 1 ) jid,'''' cnt  FROM (SELECT count( 1 ) FROM global_session GROUP BY SUBSTRING_INDEX( us, ''-'', -1 )) a union 
	   select count(distinct us)  jid,'''' cnt  from global_session union 
       SELECT min(SUBSTRING_INDEX( us, ''-'', -1 ))  jid,count(distinct us) cnt FROM global_session GROUP BY SUBSTRING_INDEX( us, ''-'', -1 ) limit ?,?';-- startRec, pagesize;
else
	if resource='' then
       set @stmt=concat(' SELECT count(distinct us)  jid,'''' as last_date  FROM global_session WHERE us LIKE ''%',eno,'@fafacn.com'' union 
       SELECT distinct us as  jid,max(login_date) as last_date FROM global_session WHERE us LIKE ''%',eno,'@fafacn.com'' group by jid limit ?,?'); -- startRec, pagesize;
    else
       set @stmt=concat(' SELECT count( us)  jid,'''' as last_date  FROM global_session WHERE us LIKE ''%',eno,'@fafacn.com'' and res=''',resource,''' union 
       SELECT us as  jid,login_date as last_date FROM global_session WHERE us LIKE ''%',eno,'@fafacn.com''  and res=''',resource,''' limit ?,?');-- startRec, pagesize;
    end if;    
  end if;
  prepare s1 from @stmt;
  execute s1 using @s1, @s2;
  deallocate prepare s1;  
END //
 
DELIMITER ;