--
-- $Id: //Infrastructure/GitHub/Database/asbo/web/sql/unstable_sql.sql#1 $
--
WITH recent_sql
AS
(
  SELECT
  --
  -- Recent sql by plan 
  -- 
       sql_id
  ,    plan_hash_value
  ,    SUM(NVL(executions_delta,0)) execs
  ,    (SUM(elapsed_time_delta)/DECODE(SUM(NVL(executions_delta, 0)), 0, 1, SUM (executions_delta))/1000000) avg_exec_time_secs 
  FROM 
       __hist_sqlstat__  s
  JOIN __hist_snapshot__ ss ON ss.snap_id = s.snap_id AND ss.instance_number = s.instance_number
  WHERE         
       executions_delta > 0
  GROUP BY 
       sql_id
  ,    plan_hash_value
)
, sql_stat
AS
(
  SELECT 
  --
  -- Get some stats on the execution time
  --
    rs.* 
  , STDDEV(avg_exec_time_secs) OVER (PARTITION BY sql_id) stddev_avg_exec_time_secs
  , AVG(avg_exec_time_secs) OVER (PARTITION BY sql_id)    mean_avg_exec_time_secs
  FROM 
    recent_sql rs
)
, sql_stat2
AS
(
  SELECT 
  --
  -- More stats
  --
    sstat.sql_id
  , SUM(execs)     total_execs_captured
  , MIN(avg_exec_time_secs) min_exec_time_secs
  , MAX(avg_exec_time_secs) max_exec_time_secs
  , sstat.stddev_avg_exec_time_secs stddev_exec_time
  , mean_avg_exec_time_secs
  , (sstat.stddev_avg_exec_time_secs/mean_avg_exec_time_secs)*100 pct_stddev_of_mean
  FROM
    sql_stat sstat
  GROUP BY
    sql_id
  , stddev_avg_exec_time_secs
  , mean_avg_exec_time_secs
)
SELECT
--
-- SQL that with
-- standard deviation or over n % of the excution time
--
  '<a href="sql_details.php?db='||:db_str||'&sql_id='||sql_id||'">'||sql_id||'</a>' sql_id
, total_execs_captured "Execs captured"
, TO_CHAR(min_exec_time_secs,'99990.000') "Min exec time secs"
, TO_CHAR(max_exec_time_secs,'99990.000') "Max exec time secs"
, TO_CHAR(stddev_exec_time  ,'99990.000') "Standard Deviation"
, TO_CHAR(pct_stddev_of_mean,'99990.000') "Stddev as % of Mean"
, TO_CHAR(mean_avg_exec_time_secs,'99990.000') "Mean exec time secs" 
, NVL((SELECT TO_CHAR(MAX(last_active_time),'dd-Mon hh24:mm:ss') FROM v$sql WHERE sql_id=ss2.sql_id),'Not in SGA') last_active_time
FROM 
  sql_stat2 ss2
WHERE
  pct_stddev_of_mean > :pct_stddev_of_mean
ORDER BY   
  pct_stddev_of_mean DESC
  
