--
-- $Id: //Infrastructure/Database/scripts/oav/sql/backup_check.sql#3 $
--
WITH
  backup_details
AS
(
  SELECT
   TO_CHAR(start_time,'dd-mon hh24:mi:ss') bk_start
  ,TO_CHAR(end_time  ,'dd-mon hh24:mi:ss') bk_end
  ,(end_time - start_time) * 3600 * 24     duration_mins
  , status full_backup_status
  , CASE
      WHEN status = 'COMPLETED WITH WARNINGS' OR status = 'RUNNING WITH WARNINGS' THEN 'WARNING'
      WHEN status NOT IN ('COMPLETED','RUNNING') then 'CRITICAL'
      ELSE 'OK'
    END bk_status
  , '<a href=rman_log.php?db='||:db_str||'&session_recid='||session_recid||'>log</a>' backup_log
  FROM
    v$rman_backup_job_details 
  WHERE
    input_type = 'DB FULL' AND
    end_time > SYSDATE - 1
  
  UNION ALL
   
   select 
     'NO BACKUP'  bk_start
    , NULL  bk_end
    , NULL  duration_mins
    , NULL  full_backup_status
    , 'CRITICAL'  bk_status
    , NULL backup_log
    from dual 
   where not exists 
       (  
            SELECT * FROM v$rman_backup_job_details WHERE
            input_type = 'DB FULL' AND
            end_time > SYSDATE - 1
       )
)
select 
  bk_start
, bk_end
, full_backup_status
, bk_status
, backup_log
, TO_CHAR(TRUNC(duration_mins/3600),'FM9900')       || 'hr '   ||
  TO_CHAR(TRUNC(MOD(duration_mins,3600)/60),'FM00') || 'mins ' ||
  TO_CHAR(MOD(duration_mins,60),'FM00')             || 'secs'  duration
From  backup_details bd
WHERE
ROWNUM < 2
order by bk_start
