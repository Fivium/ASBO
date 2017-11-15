--
-- $Id: //Infrastructure/GitHub/Database/asbo/web/sql/logfile_sizes.sql#2 $
--
WITH log_tables AS
(
  SELECT
    --+ materialize
    dt.owner
  , dt.table_name
  FROM
    dba_tables dt
  WHERE
      (
           dt.table_name LIKE '%LOG%'
        OR dt.table_name LIKE '%AUDIT%'
        OR dt.table_name LIKE '%TEMP%'
        OR dt.table_name LIKE '%REPORT%'
        OR dt.table_name LIKE '%ERRORS%'
        OR dt.table_name LIKE '%REQUEST%'
        OR dt.table_name LIKE '%TRACKS%'
        OR dt.table_name LIKE '%PAGINATION%'
      )
  AND ( dt.owner LIKE '%MGR' OR dt.owner LIKE '%ENV' )
  AND dt.table_name NOT LIKE '%TEMPLATE%'
  AND dt.table_name NOT LIKE '%LOGIN%'
)
, log_lobs AS
(
  SELECT
    --+ materialize
    lt.owner
  , lt.table_name
  , tc.column_name
  , dl.segment_name
  FROM
       dba_tab_cols tc
  JOIN log_tables   lt ON lt.owner = tc.owner AND lt.table_name = tc.table_name
  JOIN dba_lobs     dl ON dl.owner = tc.owner AND dl.table_name = tc.table_name AND dl.column_name = tc.column_name
  WHERE
    tc.data_type LIKE '%LOB%'
)
, tab_col_segs AS
(
  SELECT
    l.owner
  , l.segment_name
  , l.table_name
  , l.column_name
  , 'LOB' type
  FROM
    log_lobs l
  UNION ALL
  SELECT
    t.owner
  , t.table_name segment_name
  , t.table_name
  , 'NA'
  , 'TABLE' type
  FROM log_tables t
)
, log_size_mb AS
(
  SELECT
    ds.owner
  , tcs.table_name
  , ds.segment_name
  , ROUND( ds.bytes/POWER(1024,2) ) mb
  FROM
       dba_segments ds
  JOIN tab_col_segs tcs ON tcs.segment_name = ds.segment_name
)
, log_total AS
(
  SELECT --+ materialize
    SUM(mb) total_mb
  FROM
    log_size_mb
)
, log_file_totals AS
(
SELECT
  owner
, table_name
, SUM(mb) mb
, (SELECT total_mb FROM log_total) logfile_total_mb 
, (SELECT ROUND( SUM(bytes)/1024/1024 ) FROM dba_segments) segment_total_mb
, (SELECT ROUND( SUM(bytes)/1024/1024 ) FROM dba_data_files ) datafile_total_mb
FROM
  log_size_mb
WHERE
  mb > 20
GROUP BY
  owner
, table_name  
ORDER BY 
  mb DESC
)
SELECT
  owner
, table_name
, ROUND(mb/1024) gb
, ROUND(mb/logfile_total_mb*100) percentage_of_total_logs
, ROUND(mb/segment_total_mb*100) percentage_of_total_segments
, ROUND(logfile_total_mb/1024) logfile_total_gb
, ROUND(segment_total_mb/1024) segment_total_gb
, ROUND(datafile_total_mb/1024) datafile_total_gb
FROM
  log_file_totals lft
