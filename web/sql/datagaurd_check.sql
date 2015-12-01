--
-- $Id: //Infrastructure/GitHub/Database/asbo/web/sql/datagaurd_check.sql#2 $
--
WITH
  --dg_param AS ( SELECT 'DG_CONFIG=(db1,db2,db3)' p FROM dual )
  dg_param AS ( SELECT value p FROM v$parameter WHERE name='log_archive_config' )
, sb_list  AS ( SELECT RTRIM( SUBSTR(p, INSTR(p, '(')+1 ), ')') l FROM dg_param )
, sb_rows  AS (
SELECT
  REGEXP_SUBSTR(l,'[^,]+', 1, LEVEL) sb_name
FROM
  sb_list
CONNECT BY REGEXP_SUBSTR(l, '[^,]+', 1, LEVEL) IS NOT NULL
)
, sb_status AS(
SELECT
  sbr.sb_name
, NVL( ( SELECT MAX(sequence#) s FROM v$archived_log WHERE name = sbr.sb_name AND completion_time > SYSDATE-1 AND applied='YES'), 0              ) sb_last_applied_seq
, NVL( ( SELECT MAX(next_time) s FROM v$archived_log WHERE name = sbr.sb_name AND completion_time > SYSDATE-1 AND applied='YES'), SYSDATE - 999  ) sb_last_applied_date
, (SELECT sequence# FROM v$log WHERE status='CURRENT') primary_applied_seq
, (SELECT CAST( SCN_TO_TIMESTAMP(current_scn) AS DATE ) FROM v$database) primary_applied_date
FROM sb_rows sbr
WHERE sbr.sb_name IS NOT NULL
)
, lag_check AS(
SELECT
  sb_name
, primary_applied_seq
, sb_last_applied_seq
, primary_applied_seq  - sb_last_applied_seq                 sb_log_lag
, primary_applied_date
, sb_last_applied_date
, ROUND((primary_applied_date - sb_last_applied_date)*24*60) sb_mins_lag
FROM
  sb_status
)
SELECT
  lc.*
, CASE WHEN (
    SYSDATE > TRUNC(SYSDATE) + 20/24 OR
    SYSDATE < TRUNC(SYSDATE) + 07/24
  ) THEN 'BLACKOUT'
  ELSE
    CASE
      WHEN NVL(lc.sb_mins_lag,999) > 60 THEN 'CRITICAL'
      WHEN NVL(lc.sb_log_lag ,999) > 20 THEN 'CRITICAL'
      ELSE 'OK'
    END
  END status
FROM
  lag_check lc
