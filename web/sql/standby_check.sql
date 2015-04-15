--
-- $Id: //Infrastructure/GitHub/Database/avo/web/sql/standby_check.sql#2 $
--
WITH
  sb_details
AS
(
  SELECT
    ROUND((SYSDATE - ds.standby_applied_until_date)*24*60)                                     mins_lag
  , ds.primary_current_redolog_seq - ds.standby_applied_redolog_seq                            redo_lag 
  , ds.standby_server                                                                          sb_server
  , ds.standby_sid                                                                             sb_sid
  , to_char(ds.last_full_refresh_start,'DD-Mon hh24:MI:SS')                                    full_rebuild_start
  , to_char(ds.last_full_refresh_end  ,'DD-Mon hh24:MI:SS')                                    full_rebuild_end
  , '<a href=dbsync_log.php?db='||:db_str||'&type=full&standby_id='||ds.standby_id||'>log</a>' full_log
  , NVL(ds.last_full_refresh_status,'OK')                                                      full_status
  , to_char(ds.last_rollforward_start,'DD-Mon hh24:MI:SS')                                     rollforward
  , '<a href=dbsync_log.php?db='||:db_str||'&type=roll&standby_id='||ds.standby_id||'>log</a>' log
  , NVL(ds.last_rollforward_status,'OK')                                                       roll_status
  FROM 
    dbamgr.dbsync_standby ds
)
SELECT
  CASE
    WHEN sb_details.full_status = 'ERROR!!!' THEN 'CRITICAL'
    WHEN sb_details.mins_lag    > 30         THEN 'CRITICAL'
    WHEN sb_details.roll_status = 'ERROR!!!' THEN 'CRITICAL'
    ELSE 'OK'
  END status
, sb_details.*
FROM
  sb_details
