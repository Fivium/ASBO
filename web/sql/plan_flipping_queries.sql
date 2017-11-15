--
-- $Id: //Infrastructure/GitHub/Database/asbo/web/sql/plan_flipping_queries.sql#2 $
--
WITH recent_sql
AS(
  SELECT
  --
  -- Queries that have been run recently
  -- group by hash value since we are looking for flipping plans 
  --
    s.sql_id
  , s.plan_hash_value
  , MAX( s.last_active_time ) last_active_datetime
  , SUM( s.elapsed_time )/1000 plan_exec_time_secs
  , SUM( s.executions ) execs
  , ( SUM( s.elapsed_time )/SUM( s.executions ) )/1000 avg_exec_secs
  FROM 
    v$sql s
  WHERE 
    executions > 0 AND
    last_active_time > SYSDATE - 2
  GROUP BY
    sql_id
  , plan_hash_value
)
, plan_exec_stats 
AS(
  SELECT   
  --
  -- Execution stats on the plans
  --
    sql_id
  , ROUND( ( MAX( avg_exec_secs )/MIN( avg_exec_secs ) ) * 100 ) percentage_variation
  , MAX( avg_exec_secs ) slowest_plan_exec_secs
  , MIN( avg_exec_secs ) fastest_plan_exec_secs
  , SUM( plan_exec_time_secs ) total_exec_secs
  , SUM( execs ) total_execs
  FROM
    recent_sql
  WHERE
    plan_exec_time_secs > 0.5
  GROUP BY
    sql_id
)
, slow_fast_plans
AS(
  SELECT 
  --
  -- Details of the fastest and slowest plans
  -- for a given sql_id
  -- Where there is large difference in the execution times
  --
    pes.*
  , ( SELECT plan_hash_value      FROM recent_sql WHERE sql_id = pes.sql_id AND avg_exec_secs = slowest_plan_exec_secs ) slowest_plan_hash_value
  , ( SELECT last_active_datetime FROM recent_sql WHERE sql_id = pes.sql_id AND avg_exec_secs = slowest_plan_exec_secs ) slowest_plan_last_exec
  , ( SELECT execs                FROM recent_sql WHERE sql_id = pes.sql_id AND avg_exec_secs = slowest_plan_exec_secs ) slowest_plan_execs
  , ( SELECT plan_hash_value      FROM recent_sql WHERE sql_id = pes.sql_id AND avg_exec_secs = fastest_plan_exec_secs ) fastest_plan_hash_value
  , ( SELECT last_active_datetime FROM recent_sql WHERE sql_id = pes.sql_id AND avg_exec_secs = fastest_plan_exec_secs ) fastest_plan_last_exec
  , ( SELECT execs                FROM recent_sql WHERE sql_id = pes.sql_id AND avg_exec_secs = fastest_plan_exec_secs ) fastest_plan_execs
  FROM
    plan_exec_stats pes 
  WHERE
    percentage_variation > 200  
)
SELECT 
--
-- Details of queries that have multiple plans
-- where there is a large variation in the excution time
--
  sfp.sql_id
, sfp.total_exec_secs
, sfp.total_execs
, ROUND( sfp.slowest_plan_exec_secs, 2 ) slowest_plan_exec_secs
, ROUND( sfp.fastest_plan_exec_secs, 2 ) fastest_plan_exec_secs
, sfp.slowest_plan_execs
, sfp.fastest_plan_execs
, sfp.slowest_plan_last_exec
, sfp.fastest_plan_last_exec
, sfp.slowest_plan_hash_value
, sfp.fastest_plan_hash_value
FROM
  slow_fast_plans sfp
WHERE
  slowest_plan_hash_value != fastest_plan_hash_value 
ORDER BY
  total_exec_secs DESC
