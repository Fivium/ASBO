<?php
#
# $Id: //Infrastructure/GitHub/Database/avo/web/top_sql.php#2 $
#
# Busy sql details
#
#
$sql = <<<__SQL_END
WITH top_sql
AS
( SELECT
    ash.sql_id sql_id
  , count(1)   busy_count
  FROM
    $snaps_table ash
  WHERE
    sample_time > SYSDATE - :sql_mins_hist/(24*60) AND
    sql_id IS NOT NULL AND
    ( ash.session_type = :session_type OR :session_type IS NULL )
  GROUP BY
    sql_id
  ORDER BY
    busy_count desc
)
, top_sql_detail
AS
( SELECT
    ash.sql_id sql_id
  , count(1)   busy_count
  , session_state
  , wait_class
  , event
  , blocking_session
  FROM
    $snaps_table ash
  WHERE
    sample_time > sysdate - :sql_mins_hist/(24*60) and
    sql_id is not null and
    ( ash.session_type = :session_type OR :session_type IS NULL )
  GROUP BY
    sql_id
  , session_state
  , wait_class
  , event
  , blocking_session
)
, top_sql_agg 
as
( SELECT
    ts.sql_id
  , busy_count
  , (SELECT SUM(busy_count) FROM top_sql_detail tsd WHERE tsd.sql_id = ts.sql_id AND tsd.session_state = 'ON CPU'    ) on_cpu_count
  , (SELECT SUM(busy_count) FROM top_sql_detail tsd WHERE tsd.sql_id = ts.sql_id AND tsd.wait_class    = 'User I/O'  ) user_io_count
  , (SELECT SUM(busy_count) FROM top_sql_detail tsd WHERE tsd.sql_id = ts.sql_id AND tsd.wait_class    = 'System I/O') system_io_count
  , (SELECT SUM(busy_count) FROM top_sql_detail tsd WHERE tsd.sql_id = ts.sql_id AND tsd.blocking_session IS NOT NULL) blocked_count
  , s.executions execs
  , CASE WHEN s.executions > 0 THEN TO_CHAR(ROUND(s.elapsed_time/s.executions/1000000,2),'99990.00') END avg_secs
  , CASE WHEN s.executions > 0 THEN ROUND(s.disk_reads/s.executions) END avg_disk_reads
  , REPLACE( REGEXP_REPLACE(s.sql_text, '( ){2,}', ' '), CHR(10), ' ' )    sql_text
  FROM
       top_sql   ts
  JOIN v\$sqlarea s  on s.sql_id = ts.sql_id
  WHERE
    rownum <= 10
)
SELECT
  sql_id
, busy_count
, ROUND((busy_count     /(:sql_mins_hist*60))*100) percent_busy 
, ROUND((on_cpu_count   /(:sql_mins_hist*60))*100) percent_on_cpu
, ROUND((user_io_count  /(:sql_mins_hist*60))*100) percent_user_io
, ROUND((system_io_count/(:sql_mins_hist*60))*100) percent_system_io
, ROUND((blocked_count  /(:sql_mins_hist*60))*100) percent_blocked
, ROUND(((busy_count-NVL(on_cpu_count,0)-NVL(user_io_count,0)-NVL(system_io_count,0)-NVL(blocked_count,0))/(:sql_mins_hist*60))*100) percent_other
, execs
, avg_secs
, avg_disk_reads
, CASE 
    when length( sql_text ) < 200 then sql_text 
    else substr( sql_text, 1, 197 ) || '...'
  end sql_text
FROM
  top_sql_agg
ORDER BY
  busy_count DESC
__SQL_END;

$binds   = '';
$binds[] = array( ":sql_mins_hist", $sql_mins_hist );
$binds[] = array( ":session_type" , $session_type  );

$cur = $db_obj->exec_sql( $sql, $binds );

u::start_tag('div','style="'.$div_styles_top_10s.'"');

u::start_tag('table','id="data1"');
u::tr('<th>sql_id</th><th>% of '.$sql_mins_hist.' mins busy</th><th>Execs</th><th>Avg Disk Reads</th><th>Avg Secs</th><th>SQL Text</th>','',1);

$i=0;
while ( ( $rec = oci_fetch_object( $cur ) ) ){
    #
    # Row formatting
    #
    $class_str=u::odd_even_class_str($i++);

    u::start_tag('tr',$class_str);    
    $highlight_graph_str = "onmouseover=\"highlight('{$rec->SQL_ID}');\" ";
    $sql_cell            = "<div $highlight_graph_str>".sql_details_link( $rec->SQL_ID, $db )."</div>";

    u::td( $sql_cell,'',1 );
    #
    # Barchart
    #
    $values  = array(
      $rec->PERCENT_ON_CPU
    , $rec->PERCENT_USER_IO
    , $rec->PERCENT_SYSTEM_IO
    , $rec->PERCENT_BLOCKED
    , $rec->PERCENT_OTHER
    );
    
    u::start_tag('td','style="padding:0px 0px 0px 0px"');
    include 'bar_chart.php';
    u::end_tag('td');
    u::td($rec->EXECS,'',1);
    u::td($rec->AVG_DISK_READS,'',1);
    u::td($rec->AVG_SECS,'',1);
    u::tagged_item('td',$rec->SQL_TEXT,'style="border-right:0px;font-size:xx-small;font-family:Courier New;"',1);
    u::end_tag('tr');
}
#
# Fill table up if needed
#
u::filler_rows( $table_rows, $i, 6);
u::end_tag('table');
u::end_tag('div');
?>
