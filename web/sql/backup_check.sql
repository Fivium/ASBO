--
-- $Id: //Infrastructure/GitHub/Database/asbo/web/sql/backup_check.sql $
--
WITH 
  backup_details
AS
(
  SELECT
    TO_CHAR(start_time,'dd-mon hh24:mi:ss') bk_start
  , TO_CHAR(end_time  ,'dd-mon hh24:mi:ss') bk_end
  , (end_time - start_time) * 3600 * 24     duration_mins
  , status full_backup_status
  , CASE
      WHEN status = 'COMPLETED WITH WARNINGS' THEN 'WARNING'
      WHEN status NOT IN ('COMPLETED','RUNNING') THEN 'CRITICAL'
      ELSE 'OK'
    END bk_status
  --, '<a href=rman_progress.php?db='||:db_str||'>Details</a>' rman_details
  , '<a href=rman_log.php?db='||:db_str||'&session_recid='||session_recid||'>log</a>' backup_log
  FROM 
    v$rman_backup_job_details
  WHERE 
    input_type = 'DB FULL' AND 
    start_time > SYSDATE - 2 
  ORDER BY 
    start_time DESC
)
SELECT
  bk_start
, bk_end
, full_backup_status
--, rman_details
--, backup_log
, TO_CHAR(TRUNC(duration_mins/3600),'FM9900')       || 'hr '   ||
  TO_CHAR(TRUNC(MOD(duration_mins,3600)/60),'FM00') || 'mins ' ||
  TO_CHAR(MOD(duration_mins,60),'FM00')             || 'secs'  duration
, bk_status
FROM
  backup_details bd

