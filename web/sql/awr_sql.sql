DECLARE
--
-- $Id: //Infrastructure/GitHub/Database/avo/web/sql/awr_sql.sql#2 $
--
-- 2012 - T Dale - quick AWR report, runs on oracle standard, with a snapper
--
  TYPE cur_type IS REF CURSOR;
  TYPE top_sql_rec_type 
  IS
    RECORD(
      row_count    INT
    , executions   INT
    , module       VARCHAR2(100)
    , action       VARCHAR2(100)
    , sql_text     VARCHAR2(2000)
    , sql_id       VARCHAR2(20)
    , elapsed_time NUMBER
    , cpu_time     NUMBER
    , buffer_gets  NUMBER
    , disk_reads   NUMBER
    , sql_rank     INT
    );
  
  c_top_sql                     cur_type;
  r_top_sql                     top_sql_rec_type;
  l_start_snap_id               NUMBER;
  l_end_snap_id                 NUMBER;
  l_os_start_snap_id            NUMBER := TO_NUMBER(:os_start_snap);
  l_os_end_snap_id              NUMBER := TO_NUMBER(:os_end_snap);
  l_count                       NUMBER := 0;
  l_begin_time                  TIMESTAMP;
  l_end_time                    TIMESTAMP;
  l_percent                     NUMBER := 0;
  l_execs                       INTEGER;
  l_plan_line_count             INTEGER;
  l_output_plan                 BOOLEAN;
  l_avg_str                     VARCHAR2(20);
  l_row                         INT := 0;
  l_hours_str                   VARCHAR2(1000);
  l_hour                        VARCHAR2(10);
  l_order_by_id                 INT := :order_by_id;
  l_db_str                      VARCHAR2(100) := :db_str;
  l_ignore_plsql                INT := 1;
  l_ignore_support_activity     INT := 0;
  l_ignore_support_activity_str VARCHAR2(1000);
  l_ignore_reportmgr            INT := 0;
  l_ignore_reportmgr_str        VARCHAR2(1000);
  l_result_count                INT := 10;
  l_order_by_str                VARCHAR2(100);
  l_report_title                VARCHAR2(2000);
  
  c_ms_sec             CONSTANT INTEGER      := 1000000;
  c_sql_display_length CONSTANT INTEGER      := 220;
  c_max_plan_lines     CONSTANT INTEGER      := 30;
  c_stat_padding       CONSTANT INTEGER      := 11;
  c_desc_padding       CONSTANT INTEGER      := 35;  
  c_max_top_sql        CONSTANT INTEGER      := l_result_count;
  c_more_txt_str       CONSTANT VARCHAR2(30) := '...there is more...';
  c_date_fmt           CONSTANT VARCHAR2(16) := 'yyyy_mm_dd__hh24';
  c_date_display_fmt   CONSTANT VARCHAR2(19) := 'DD/MM/YY HH24:MI:SS';
  c_default_start      CONSTANT DATE         := TRUNC(SYSDATE-1)+9/24;
  c_default_end        CONSTANT DATE         := TRUNC(SYSDATE-1)+17/24;   
  c_sql_snip_length    CONSTANT INTEGER      := 1000;

  --LOCAL PROCS-----------------
  PROCEDURE p(p_str varchar2, p_bold BOOLEAN := FALSE) 
  IS
    c_line CONSTANT VARCHAR2(200) := LPAD('-',200,'-'); 
  BEGIN 
    IF p_bold THEN p('-'); p(c_line); END IF;
    DBMS_OUTPUT.PUT_LINE(p_str); 
    IF p_bold THEN p(c_line); p('-'); END IF;
  END;
  --LOCAL-----------------  
  FUNCTION tidy_fmt(p_num NUMBER) RETURN VARCHAR2
  IS
    l_num_str VARCHAR2(100);
  BEGIN
    CASE 
      WHEN p_num < 10   THEN l_num_str := TO_CHAR( p_num, '0.99'   );
      WHEN p_num < 100  THEN l_num_str := TO_CHAR( p_num, '00.99'  );
      WHEN p_num < 1000 THEN l_num_str := TO_CHAR( p_num, '000.99' );
      ELSE l_num_str := TO_CHAR( ROUND( p_num ) ); 
    END CASE;
    
    RETURN l_num_str;
  END;
  --LOCAL-----------------
  FUNCTION sql_link( p_sql_id VARCHAR2 ) RETURN VARCHAR2
  IS
  BEGIN
    RETURN '<a href=sql_details.php?db=' || l_db_str || '&sql_id=' || p_sql_id || ' target="_blank">' || p_sql_id || '</a>'; 
  END;
  --LOCAL-----------------  
  FUNCTION secs_per_exec(p_time NUMBER, p_execs NUMBER) RETURN VARCHAR2
  IS
  BEGIN
    IF p_execs IS NULL OR p_execs = 0 THEN 
      RETURN '';
    ELSE 
      RETURN LTRIM(tidy_fmt(ROUND((p_time/p_execs)/c_ms_sec,2)));
    END IF;
  END;
  --LOCAL-----------------  
  FUNCTION per_exec(p_total NUMBER, p_execs NUMBER) RETURN NUMBER
  IS
  BEGIN
    IF p_execs IS NULL OR p_execs = 0 THEN 
      RETURN 0;
    ELSE 
      RETURN ROUND((p_total/p_execs),2);
    END IF;     
  END;
  --LOCAL-----------------  
  FUNCTION num_tidy( p_num NUMBER) RETURN VARCHAR2
  IS
    c_k        INT := 1000;
    c_million  INT := 1000 * c_k;
    c_billion  INT := 1000 * c_million;
    c_trillion INT := 1000 * c_billion;
    
    l_num  NUMBER;
    l_unit VARCHAR(30);
  BEGIN
    CASE      
      WHEN p_num > c_k THEN
        l_num  := p_num/c_k;
        l_unit := 'k';
      WHEN p_num > c_million THEN
        l_num  := p_num/c_million;
        l_unit := ' Million';
      WHEN p_num > c_billion THEN
        l_num  := p_num/c_billion;
        l_unit := ' Billion';          
      ELSE 
        l_num  := p_num;
        l_unit := '';      
    END CASE;
    
    RETURN ROUND(l_num) || l_unit;
  END;
  --LOCAL-----------------  
  FUNCTION sql_by_hr_tidy( p_execs NUMBER, p_total_time NUMBER ) RETURN VARCHAR2
  IS
    l_execs    VARCHAR2(100);
    l_avg      VARCHAR2(100);
    l_per_exec NUMBER;
  BEGIN
       
    IF p_execs > 1000 THEN
      l_execs := ROUND(p_execs/1000) || 'k';
    ELSE
      l_execs := p_execs;
    END IF;
    
    l_per_exec := per_exec( p_total_time , p_execs );
        
    RETURN( LPAD( l_execs, c_stat_padding ) || LPAD( l_per_exec, c_stat_padding ) );
  END; 
  --LOCAL-----------------  
  PROCEDURE system_wide_stats_by_snap(p_start_snap_id NUMBER, p_end_snap_id NUMBER )
  IS
  BEGIN
    FOR l_rec IN (
      WITH snap_data AS
      (
        SELECT
          metric_id
        , REPLACE( REPLACE( REPLACE( metric_name, 'CPU Usage Per Sec', '% Of CPU Cores, eg 200 = 2 cores' ), 'Bytes', 'MB' ), 'Physical ', '' ) metric_name
        , metric_unit
        , TO_CHAR( begin_time, 'HH24' ) hr
        , ( CASE WHEN metric_name LIKE '%Bytes%' THEN maxval/(1024*1024)  ELSE maxval  END ) maxval
        , ( CASE WHEN metric_name LIKE '%Bytes%' THEN average/(1024*1024) ELSE average END ) avg
        , snap_id
        FROM
          dba_hist_sysmetric_summary
        WHERE
          snap_id >= p_start_snap_id AND 
          snap_id <= p_end_snap_id+1 AND
          metric_id IN (2075,2135,2092,2093,2012,2124,2100,2014)
      )
      SELECT 
        metric_id
      , metric_name
      , metric_unit
      , SUM( (case when snap_id = p_start_snap_id     then maxval ELSE 0 END) ) snap_1_max
      , SUM( (case when snap_id = p_start_snap_id     then avg    ELSE 0 END) ) snap_1_avg
      , SUM( (case when snap_id = p_start_snap_id + 1 then maxval ELSE 0 END) ) snap_2_max
      , SUM( (case when snap_id = p_start_snap_id + 1 then avg    ELSE 0 END) ) snap_2_avg    
      , SUM( (case when snap_id = p_start_snap_id + 2 then maxval ELSE 0 END) ) snap_3_max
      , SUM( (case when snap_id = p_start_snap_id + 2 then avg    ELSE 0 END) ) snap_3_avg
      , SUM( (case when snap_id = p_start_snap_id + 3 then maxval ELSE 0 END) ) snap_4_max
      , SUM( (case when snap_id = p_start_snap_id + 3 then avg    ELSE 0 END) ) snap_4_avg
      , SUM( (case when snap_id = p_start_snap_id + 4 then maxval ELSE 0 END) ) snap_5_max
      , SUM( (case when snap_id = p_start_snap_id + 4 then avg    ELSE 0 END) ) snap_5_avg
      , SUM( (case when snap_id = p_start_snap_id + 5 then maxval ELSE 0 END) ) snap_6_max
      , SUM( (case when snap_id = p_start_snap_id + 5 then avg    ELSE 0 END) ) snap_6_avg
      , SUM( (case when snap_id = p_start_snap_id + 6 then maxval ELSE 0 END) ) snap_7_max
      , SUM( (case when snap_id = p_start_snap_id + 6 then avg    ELSE 0 END) ) snap_7_avg
      , SUM( (case when snap_id = p_start_snap_id + 7 then maxval ELSE 0 END) ) snap_8_max
      , SUM( (case when snap_id = p_start_snap_id + 7 then avg    ELSE 0 END) ) snap_8_avg
      , SUM( (case when snap_id = p_start_snap_id + 8 then maxval ELSE 0 END) ) snap_9_max
      , SUM( (case when snap_id = p_start_snap_id + 8 then avg    ELSE 0 END) ) snap_9_avg
      FROM
        snap_data
      GROUP BY
        metric_id
      , metric_name
      , metric_unit
      ORDER BY
        metric_name     
    ) LOOP
      --
      -- Display the system wide stats by snap
      --    
      p( 
        RPAD( l_rec.metric_name, c_desc_padding )            || 
        LPAD( tidy_fmt( l_rec.snap_1_avg ), c_stat_padding ) || 
        LPAD( tidy_fmt( l_rec.snap_1_max ), c_stat_padding ) ||  
        LPAD( tidy_fmt( l_rec.snap_2_avg ), c_stat_padding ) || 
        LPAD( tidy_fmt( l_rec.snap_2_max ), c_stat_padding ) ||
        LPAD( tidy_fmt( l_rec.snap_3_avg ), c_stat_padding ) || 
        LPAD( tidy_fmt( l_rec.snap_3_max ), c_stat_padding ) ||
        LPAD( tidy_fmt( l_rec.snap_4_avg ), c_stat_padding ) || 
        LPAD( tidy_fmt( l_rec.snap_4_max ), c_stat_padding ) ||
        LPAD( tidy_fmt( l_rec.snap_5_avg ), c_stat_padding ) || 
        LPAD( tidy_fmt( l_rec.snap_5_max ), c_stat_padding ) ||
        LPAD( tidy_fmt( l_rec.snap_6_avg ), c_stat_padding ) || 
        LPAD( tidy_fmt( l_rec.snap_6_max ), c_stat_padding ) ||
        LPAD( tidy_fmt( l_rec.snap_7_avg ), c_stat_padding ) || 
        LPAD( tidy_fmt( l_rec.snap_7_max ), c_stat_padding ) ||
        LPAD( tidy_fmt( l_rec.snap_8_avg ), c_stat_padding ) || 
        LPAD( tidy_fmt( l_rec.snap_8_max ), c_stat_padding ) 
      );  
    END LOOP;
  
  END;
  --LOCAL-----------------  
  FUNCTION order_report_by RETURN VARCHAR2
  IS
    l_order_by_str VARCHAR2(200);
  BEGIN
    --
    -- How are we ranking the sql?
    --
    CASE l_order_by_id
      WHEN 1 THEN l_order_by_str := 'SUM(elapsed_time_delta) DESC';
      WHEN 2 THEN l_order_by_str := 'SUM(disk_reads_delta) DESC';
      WHEN 3 THEN l_order_by_str := 'SUM(elapsed_time_delta)/DECODE(SUM(executions_delta),0,1,SUM(executions_delta)) DESC';
    END CASE;

    RETURN l_order_by_str ;

  END;
  --LOCAL-----------------  
  PROCEDURE sql_stats_by_snap( p_start_snap_id INT, p_end_snap_id INT, p_ignore_plsql INT, p_order_by_str VARCHAR2 )
  IS
    TYPE cur_type IS REF CURSOR;

    TYPE top_sql_rec_type 
    IS
      RECORD(
        rownum       INT
      , sql_id       VARCHAR2(30)
      , sql_rank     INT
      , snap_1_execs INT
      , snap_1_time  NUMBER
      , snap_2_execs INT
      , snap_2_time  NUMBER
      , snap_3_execs INT
      , snap_3_time  NUMBER
      , snap_4_execs INT
      , snap_4_time  NUMBER
      , snap_5_execs INT
      , snap_5_time  NUMBER
      , snap_6_execs INT
      , snap_6_time  NUMBER
      , snap_7_execs INT
      , snap_7_time  NUMBER
      , snap_8_execs INT
      , snap_8_time  NUMBER
      , snap_9_execs INT
      , snap_9_time  NUMBER
      );
  
    c_top_sql cur_type;
    r_data    top_sql_rec_type;

  BEGIN
    OPEN c_top_sql FOR
      'SELECT 
        ROWNUM
      , s.*
      FROM (
        SELECT 
          sqlstat.sql_id                                       sql_id
        , RANK () OVER (ORDER BY ' || order_report_by  || ' ) sql_rank
        , SUM( (case when snap_id = start_snap.id     then sqlstat.execs     ELSE 0 END) ) snap_1_execs
        , SUM( (case when snap_id = start_snap.id     then sqlstat.time_secs ELSE 0 END) ) snap_1_time
        , SUM( (case when snap_id = start_snap.id + 1 then sqlstat.execs     ELSE 0 END) ) snap_2_execs
        , SUM( (case when snap_id = start_snap.id + 1 then sqlstat.time_secs ELSE 0 END) ) snap_2_time
        , SUM( (case when snap_id = start_snap.id + 2 then sqlstat.execs     ELSE 0 END) ) snap_3_execs
        , SUM( (case when snap_id = start_snap.id + 2 then sqlstat.time_secs ELSE 0 END) ) snap_3_time
        , SUM( (case when snap_id = start_snap.id + 3 then sqlstat.execs     ELSE 0 END) ) snap_4_execs
        , SUM( (case when snap_id = start_snap.id + 3 then sqlstat.time_secs ELSE 0 END) ) snap_4_time
        , SUM( (case when snap_id = start_snap.id + 4 then sqlstat.execs     ELSE 0 END) ) snap_5_execs
        , SUM( (case when snap_id = start_snap.id + 4 then sqlstat.time_secs ELSE 0 END) ) snap_5_time
        , SUM( (case when snap_id = start_snap.id + 5 then sqlstat.execs     ELSE 0 END) ) snap_6_execs
        , SUM( (case when snap_id = start_snap.id + 5 then sqlstat.time_secs ELSE 0 END) ) snap_6_time
        , SUM( (case when snap_id = start_snap.id + 6 then sqlstat.execs     ELSE 0 END) ) snap_7_execs
        , SUM( (case when snap_id = start_snap.id + 6 then sqlstat.time_secs ELSE 0 END) ) snap_7_time
        , SUM( (case when snap_id = start_snap.id + 7 then sqlstat.execs     ELSE 0 END) ) snap_8_execs
        , SUM( (case when snap_id = start_snap.id + 7 then sqlstat.time_secs ELSE 0 END) ) snap_8_time
        , SUM( (case when snap_id = start_snap.id + 8 then sqlstat.execs     ELSE 0 END) ) snap_9_execs
        , SUM( (case when snap_id = start_snap.id + 8 then sqlstat.time_secs ELSE 0 END) ) snap_9_time
        FROM 
          ( 
            SELECT 
              hs.*
            , ROUND(elapsed_time_delta/1000000)             time_secs
            , DECODE(executions_delta,0,1,executions_delta) execs 
            FROM 
              dba_hist_sqlstat hs
           ) sqlstat
        ,  ( SELECT :p_start_snap_id id FROM DUAL ) start_snap
        WHERE 
          snap_id         >= :p_start_snap_id AND
          snap_id         <= :p_end_snap_id   AND
          plan_hash_value != CASE :p_ignore_plsql WHEN 1 THEN 0 ELSE 1 END 
        GROUP BY
          sqlstat.sql_id
        ORDER BY
        ' || order_report_by || '
      ) s
      WHERE 
        sql_rank <= ' || c_max_top_sql
      USING p_start_snap_id, p_start_snap_id, p_end_snap_id, p_ignore_plsql;

    LOOP
      FETCH c_top_sql INTO r_data;
      EXIT WHEN c_top_sql%NOTFOUND;
      p( RPAD( 'Rank ' || LPAD( r_data.sql_rank, 2 ) || ' - ' || r_data.sql_id, c_desc_padding ) || 
        sql_by_hr_tidy( r_data.snap_1_execs, r_data.snap_1_time ) ||
        sql_by_hr_tidy( r_data.snap_2_execs, r_data.snap_2_time ) ||
        sql_by_hr_tidy( r_data.snap_3_execs, r_data.snap_3_time ) ||
        sql_by_hr_tidy( r_data.snap_4_execs, r_data.snap_4_time ) ||
        sql_by_hr_tidy( r_data.snap_5_execs, r_data.snap_5_time ) ||
        sql_by_hr_tidy( r_data.snap_6_execs, r_data.snap_6_time ) ||
        sql_by_hr_tidy( r_data.snap_7_execs, r_data.snap_7_time ) ||
        sql_by_hr_tidy( r_data.snap_8_execs, r_data.snap_8_time ) 
      );
    
    END LOOP; 
  
  END;
  --LOCAL-----------------  
  FUNCTION exec_rate( p_execs INT, p_start DATE, p_end DATE )
  RETURN VARCHAR2
  IS
    c_secs_in_hour      CONSTANT INT := 3600;
    c_mins_in_hour      CONSTANT INT := 60;
    l_hours             NUMBER;
    l_exec_rate_by_hour NUMBER;
    l_exec_rate         NUMBER;
    l_unit              VARCHAR2(60);    
  BEGIN
    l_hours             := round((p_end - p_start)*24);
    l_exec_rate_by_hour := p_execs/l_hours;
    
    CASE 
      WHEN l_exec_rate_by_hour > c_secs_in_hour THEN
        l_exec_rate := l_exec_rate_by_hour / c_secs_in_hour;
        l_unit      := ' per Second';
      WHEN l_exec_rate_by_hour > c_mins_in_hour THEN
        l_exec_rate := l_exec_rate_by_hour / c_mins_in_hour;
        l_unit      := ' per Minute';
      WHEN l_exec_rate_by_hour > 1 THEN          
        l_exec_rate := l_exec_rate_by_hour;
        l_unit      := ' per Hour';   
      ELSE
        l_exec_rate := p_execs;
        l_unit      := ' in '||l_hours||' Hours';   
    END CASE;
    
    RETURN ROUND(l_exec_rate) || l_unit;
  END; 
  --LOCAL-----------------  
  PROCEDURE output_sql_history( p_sql_id VARCHAR2, p_start_snap_id INT, p_end_snap_id INT )
  IS
    CURSOR c_sql_hist 
    IS
      SELECT  
        TO_CHAR( ss.plan_hash_value ) phv
      , TO_CHAR( s.begin_interval_time, 'DD-MON HH24:MI' ) snap_time
      ,          ss.executions_delta                                                                         execs
      , ROUND(   ss.buffer_gets_delta            /DECODE( ss.executions_delta,0,1,ss.executions_delta )    ) lio_per_exec
      , ROUND(   ss.disk_reads_delta             /DECODE( ss.executions_delta,0,1,ss.executions_delta )    ) pio_per_exec
      , ROUND( ( ss.cpu_time_delta     /1000000 )/DECODE( ss.executions_delta,0,1,ss.executions_delta ), 2 ) cpu_per_exec
      , ROUND( ( ss.elapsed_time_delta /1000000 )/DECODE( ss.executions_delta,0,1,ss.executions_delta ), 2 ) elapsed_per_exec
      , ROUND(   ss.direct_writes_delta          /DECODE( ss.executions_delta,0,1,ss.executions_delta )    ) direct_writes_per_exec
      , ROUND( ( ss.plsexec_time_delta /1000000 )/DECODE( ss.executions_delta,0,1,ss.executions_delta ), 2 ) plsql_time_per_exec
      FROM
        dba_hist_snapshot s
      , dba_hist_sqlstat  ss
      WHERE   
        ss.dbid             = s.dbid            AND     
        ss.instance_number  = s.instance_number AND     
        ss.snap_id          = s.snap_id         AND     
        ss.sql_id           = p_sql_id          AND     
        ss.executions_delta > 0                 AND
        ss.snap_id          >= p_start_snap_id  AND
        ss.snap_id          <= p_end_snap_id      
      ORDER BY  
        s.snap_id
      , ss.plan_hash_value;

      l_rec c_sql_hist%ROWTYPE;
  BEGIN
    OPEN c_sql_hist;
      FETCH c_sql_hist INTO l_rec;
      --
      -- Any History?
      --
      IF c_sql_hist%FOUND THEN
        p('Sql statistics by snap - Times are per execution');
        p('Plan Hash   |   Snap Time |  Execs | logical IO | Physical IO | CPU Time | Elapsed | Direct Writes | PLSQL Time', TRUE  );
      ELSE
        p('No sql history in snap tables');
      END IF;
      
      WHILE c_sql_hist%FOUND LOOP 

        p( '-'||
           LPAD(l_rec.phv,11)                           || ' ' ||
           LPAD(l_rec.snap_time,12)                     || ' ' ||
           LPAD(l_rec.execs,8)                          || ' ' ||
           LPAD(num_tidy(l_rec.lio_per_exec),12)        || ' ' ||
           LPAD(num_tidy(l_rec.pio_per_exec),13)        || ' ' ||
           LPAD(tidy_fmt(l_rec.cpu_per_exec),10)        || ' ' ||
           LPAD(tidy_fmt(l_rec.elapsed_per_exec),9)     || ' ' ||
           LPAD(l_rec.direct_writes_per_exec,15)        || ' ' ||
           LPAD(tidy_fmt(l_rec.plsql_time_per_exec),12) || ' '
        );
        FETCH c_sql_hist INTO l_rec;      
        
      END LOOP;
 
    CLOSE c_sql_hist;

  END;  
  --LOCAL-----------------    
  FUNCTION simple_time( p_time_ms NUMBER ) RETURN VARCHAR2
  IS
  --
  -- Simple to read formated time
  --
    c_ms_in_sec CONSTANT INT := 1000000;
    c_ms_in_min CONSTANT INT := c_ms_in_sec * 60;   
    c_ms_in_hr  CONSTANT INT := c_ms_in_min * 60;   
    
    l_time   NUMBER;
    l_unit   VARCHAR(20);
  BEGIN
    CASE
      WHEN p_time_ms > c_ms_in_hr THEN  
        l_time := p_time_ms/c_ms_in_hr;
        l_unit := 'Hours';
      WHEN p_time_ms > c_ms_in_min THEN
        l_time := p_time_ms/c_ms_in_min;
        l_unit := 'Mins';     
      WHEN p_time_ms > c_ms_sec THEN
        l_time := p_time_ms/c_ms_sec;
        l_unit := 'Secs';
      ELSE
        l_time := p_time_ms;
        l_unit := 'ms';      
    END CASE;    
    
    RETURN ROUND(l_time,2) || ' ' || l_unit;
  END;  
  --LOCAL-----------------    
  PROCEDURE baseline_details( p_sql_id VARCHAR2 )
  IS
    l_plan_baseline VARCHAR2(100);
    l_missing       BOOLEAN := TRUE;
    l_execs         INT;
    c_date_fmt      CONSTANT VARCHAR2(20) := 'dd-Mon-yyyy';
  BEGIN
    --
    -- sql plan details
    --
    FOR r_rec IN ( SELECT sa.is_bind_aware, sa.is_bind_sensitive, sa.sql_plan_baseline plan_baseline, sa.sql_profile FROM v$sqlarea sa WHERE sql_id = p_sql_id )
    LOOP
      p( 'Bind Aware : ' || r_rec.is_bind_aware || ' - Baseline : ' || NVL( r_rec.plan_baseline, 'None' ) || ' - SQL Profile : ' || NVL( r_rec.sql_profile, 'None' ), TRUE );
      l_plan_baseline := r_rec.plan_baseline;
      l_missing       := FALSE;
    END LOOP;
    --
    -- Missing from shared area?
    --
    IF l_missing THEN
      p('Sql not in the shared pool');
    END IF;
    --
    -- Display baseline details
    --
    IF l_plan_baseline IS NOT NULL THEN
      p( 'Origin       |     Created |   Last Used | Enabled | Accepted | Optimizer Cost | Executions | Avg Secs Elapsed | Avg Secs CPU | Avg Buffers | Avg Disk Reads | Plan_name', TRUE ); 

      FOR r_rec IN ( 
        SELECT 
          plan_name
        , origin
        , created
        , last_executed
        , enabled
        , accepted
        , optimizer_cost
        , executions
        , elapsed_time
        , cpu_time
        , buffer_gets
        , disk_reads
        FROM
          dba_sql_plan_baselines 
        WHERE
          sql_handle = (SELECT sql_handle FROM dba_sql_plan_baselines WHERE plan_name = l_plan_baseline )
        ORDER BY
          created DESC
        ) LOOP
          l_execs := r_rec.executions;
          p( 
            r_rec.origin                                                     || '   ' ||
            TO_CHAR( r_rec.created, c_date_fmt )                             || '   ' ||
            NVL( TO_CHAR( r_rec.last_executed, c_date_fmt ), '      Never' ) || '   ' ||
            LPAD( r_rec.enabled , 7 )                                        ||
            LPAD( r_rec.accepted, 11 )                                       || 
            LPAD( num_tidy( r_rec.optimizer_cost ), 17 )                     || 
            LPAD( num_tidy( r_rec.executions ), 13 )                         || 
            LPAD( num_tidy( per_exec( r_rec.elapsed_time, l_execs ) ), 19 )  || 
            LPAD( num_tidy( per_exec( r_rec.cpu_time    , l_execs ) ), 15 )  ||
            LPAD( num_tidy( per_exec( r_rec.buffer_gets , l_execs ) ), 14 )  ||
            LPAD( num_tidy( per_exec( r_rec.disk_reads  , l_execs ) ), 17 )  || '   '  ||        
            r_rec.plan_name           
          );
      END LOOP;
      p('-');
    END IF;

  END;
  --END LOCAL PROCS-----------------

BEGIN

  --
  -- Find snaps for the date range
  --
  FOR l_rec IN (
    SELECT
      snap_id             snap_id
    , begin_interval_time begin_time
    , END_interval_time   end_time
    FROM
      dba_hist_snapshot
    WHERE
      begin_interval_time      > NVL( TO_DATE( :from_date_str, c_date_fmt ), c_default_end   ) AND
      end_interval_time - 1/24 < NVL( TO_DATE( :to_date_str  , c_date_fmt ), c_default_end   ) 
    ORDER BY
        snap_id ASC
   ) LOOP 
    l_row := l_row + 1;
    IF l_row = 1 THEN
      l_start_snap_id := l_rec.snap_id;
      l_begin_time    := l_rec.begin_time;
    END IF;

    l_end_snap_id := l_rec.snap_id;
    l_end_time    := l_rec.end_time;
    l_hour        := TO_CHAR(l_rec.begin_time,'HH24AM'); 
    l_hours_str   := l_hours_str || LPAD( 'Avg ' || l_hour, c_stat_padding )|| LPAD( 'Max ' || l_hour, c_stat_padding ); 
  END LOOP;    

  --
  -- Report Title
  --
  l_report_title := 'Report  '||
    TO_CHAR( l_begin_time, c_date_display_fmt ) || ' to ' ||  TO_CHAR( l_end_time, c_date_display_fmt )   ||
    ' Report Ordered by ' || order_report_by    ||
    ' ( snaps '           || l_start_snap_id    || ' to ' || l_end_snap_id     || ' ) '
  ;
  --
  -- Use the os snap ids if given
  --
  IF l_os_start_snap_id = 0 and l_os_end_snap_id = 0 THEN
    --
    -- Use same snap_id's
    --
    l_os_start_snap_id := l_start_snap_id;
    l_os_end_snap_id   := l_end_snap_id;
  ELSE
    --
    -- Use specific snaps given for os
    --
    l_report_title := l_report_title || ' ( OS snaps '        || l_os_start_snap_id || ' to ' || l_os_end_snap_id  || ' ) ';
  END IF;


  p( l_report_title, TRUE );
  p('-');
  
  --
  -- System wide stats by snap
  --
  p('System wide stats by snapshot');

  p( RPAD( 'Stat', c_desc_padding ) || l_hours_str, TRUE );  

  --
  -- Use the os load breakdown
  --
  system_wide_stats_by_snap( l_os_start_snap_id, l_os_end_snap_id );
  --
  -- top sql stats by snap
  --
  p('-');
  p('Sql stats by snapshot, showing executions and average execution time');
  p( RPAD( 'SQLID', c_desc_padding ) || REPLACE( REPLACE( l_hours_str, 'Max', 'Sec' ), 'Avg', 'Num'), TRUE );  
  p('-');

  sql_stats_by_snap( l_start_snap_id, l_end_snap_id, l_ignore_plsql, order_report_by );

  p('-');
  p('- To get current sql  - SELECT TO_CHAR(sql_fulltext) sql_fulltext from v$sqlarea where sql_id = SQL_ID' );
  p('- To get awr plan     - SELECT plan_table_output from table(dbms_xplan.display_awr(   SQL_ID, PLAN_HASH, null, ''ALL -ALIAS'')) ' );
  p('- or get current plan - SELECT plan_table_output from table(dbms_xplan.display_cursor(SQL_ID           , null, ''ALL -ALIAS'')) ' );
  p('-');
  
  IF l_ignore_support_activity = 1 THEN
    l_ignore_support_activity_str :=
      'AND lower(sqlstat.module) NOT LIKE ''%toad%''
       AND lower(sqlstat.module) NOT LIKE ''%oracle enterprise manager%''
       AND lower(sqlstat.module) NOT LIKE ''%sqlplus%''
       AND lower(sqlstat.module) NOT LIKE ''%realtime connection%''
       AND lower(sqlstat.module) NOT LIKE ''%admin connection%''
       AND lower(sqlstat.module) NOT LIKE ''%dbms_scheduler%''';
  END IF;
  
  IF l_ignore_reportmgr = 1 THEN
    l_ignore_reportmgr_str := 'AND sqlstat.parsing_schema_name != ''REPORTMGR''';
  END IF;
  
  --
  -- Top sql details
  --
  p( 'Top sql', TRUE );
  OPEN c_top_sql FOR
    'SELECT 
      ROWNUM row_count
    , s.*
    FROM (
      SELECT 
        SUM(sqlstat.executions_delta)                         executions
      , sqlstat.module                                        module
      , sqlstat.action                                        action
      , TO_CHAR(substr(sqltext.sql_text,1,'||c_sql_snip_length||')) sql_text
      , sqlstat.sql_id                                        sql_id
      , SUM(sqlstat.elapsed_time_delta)                       elapsed_time
      , SUM(sqlstat.cpu_time_delta)                           cpu_time
      , SUM(sqlstat.buffer_gets_delta)                        buffer_gets
      , SUM(sqlstat.disk_reads_delta)                         disk_reads
      , RANK () OVER (ORDER BY '|| order_report_by ||')        sql_rank
      FROM 
        dba_hist_sqlstat sqlstat
      , dba_hist_sqltext sqltext
      WHERE 
        sqlstat.sql_id           = sqltext.sql_id   AND 
        snap_id                 >= :l_start_snap_id AND
        snap_id                 <= :l_end_snap_id   AND
        sqlstat.plan_hash_value != CASE :l_ignore_plsql WHEN 1 THEN 0 ELSE 1 END
        '||l_ignore_support_activity_str||'
        '||l_ignore_reportmgr_str||'
      GROUP BY
        sqlstat.module
      , sqlstat.action
      , TO_CHAR(substr(sqltext.sql_text,1,'||c_sql_snip_length||'))
      , sqlstat.sql_id
      ORDER BY '|| order_report_by ||'
    ) s
    WHERE 
      sql_rank <= :c_max_top_sql'
    USING l_start_snap_id, l_end_snap_id, l_ignore_plsql, c_max_top_sql;
      
  LOOP
    FETCH c_top_sql INTO r_top_sql;
    EXIT WHEN c_top_sql%NOTFOUND;
    p('-');
    p('-');
    p('Rank '||r_top_sql.row_count||' Consuming SQL', TRUE);
    p('-');
    p('sql_id    : ' || sql_link(r_top_sql.sql_id));
    p('Module    : ' || r_top_sql.module);
    p('Action    : ' || r_top_sql.action);
    p('-');  
    p('Total');
    p('--------');      
    p('CPU burn  : ' || simple_time(r_top_sql.cpu_time) ); 
    p('Elap Time : ' || simple_time(r_top_sql.elapsed_time) ); 
    p('Buffers   : ' || num_tidy(r_top_sql.buffer_gets) );
    p('Disk Rds  : ' || num_tidy(r_top_sql.disk_reads) );
    p('Execs     : ' || num_tidy(r_top_sql.executions) );
    p('-');  
    --
    -- Multiple executions?
    --
    IF r_top_sql.executions > 1 THEN
      p('Per Execution');
      p('--------');      
      p('Rate      : ' || exec_rate( r_top_sql.executions, l_begin_time, l_end_time ) );
      p('CPU burn  : ' || secs_per_exec( r_top_sql.cpu_time    , r_top_sql.executions ) || ' Secs' );  
      p('Elap Time : ' || secs_per_exec( r_top_sql.elapsed_time, r_top_sql.executions ) || ' Secs' );  
      p('Buffers   : ' || num_tidy( per_exec( r_top_sql.buffer_gets      , r_top_sql.executions ) ) );
      p('Disk Rds  : ' || num_tidy( per_exec( r_top_sql.disk_reads       , r_top_sql.executions ) ) );
      p('-');
    END IF;
    --
    -- Baselines and plans
    --
    baseline_details( p_sql_id => r_top_sql.sql_id );
    --
    -- SQL stat history throughout the period, if snaped
    --
    output_sql_history( r_top_sql.sql_id, l_start_snap_id, l_end_snap_id );
    p( '-' );    
    p( '- Rank '||r_top_sql.row_count||' SQL Text' );    
    p( '-' );    
    --p( 'Sql length : ' || LENGTH(r_top_sql.sql_text) );
    
    IF LENGTH(r_top_sql.sql_text) >= c_sql_display_length THEN
      p( SUBSTR( r_top_sql.sql_text, 1, c_sql_display_length ) );
      p( c_more_txt_str );
      p( 'full sql - SELECT sql_fulltext from v$sqlarea where sql_id = ''' || r_top_sql.sql_id || '''' );
    ELSE 
      p(r_top_sql.sql_text);
    END IF;
    
  END LOOP;
  CLOSE c_top_sql;
END;
