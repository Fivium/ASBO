--
-- $Id: //Infrastructure/GitHub/Database/avo/web/sql/standby_history.sql#1 $
--
SELECT
     ds.standby_sid                                                                        sb_sid
,    dsh.action                                                                            action
,    to_char(dsh.apply_start,'DD-Mon hh24:MI:SS')                                          apply_start
,    to_char(dsh.apply_end  ,'DD-Mon hh24:MI:SS')                                          apply_end
,    '<a href=dbsync_log.php?db='||:db_str||'&type=hist&hist_id='||dsh.hist_id||'>log</a>' action_log
,    NVL(dsh.status,'OK')                                                                  status
,    dsh.standby_applied_redolog_seq
,    dsh.standby_applied_until_scn
,    dsh.standby_applied_until_date
,    dsh.primary_current_redolog_seq
,    dsh.primary_current_scn
,    dsh.primary_current_date
,    dsh.hist_id
FROM 
     dbamgr.dbsync_standby_hist dsh
JOIN dbamgr.dbsync_standby      ds  ON ds.standby_id = dsh.standby_id
ORDER BY
     dsh.hist_id DESC
