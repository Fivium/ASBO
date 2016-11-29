<?php
#
# $Id: //Infrastructure/GitHub/Database/avo/web/top_sessions.php#2 $
#
# Top sessions 
#

$sql = <<<__SQL_END
WITH session_history_sample
AS(
  SELECT
    *
  FROM
    $snaps_table snaps
  WHERE
    ( session_type = :session_type OR :session_type IS NULL ) AND
    snaps.sample_time  > SYSDATE - :sql_mins_hist/(24*60)
)
, busy_count
AS
( SELECT
  --
  -- Sessions ordered by how active they where
  --
    s.session_id   sid
  , s.user_id      user_id
  , users.username username
  , COUNT(1)       seconds_busy
  FROM
         session_history_sample s
    JOIN dba_users            users ON users.user_id = s.user_id
  WHERE
    ( s.session_type = :session_type OR :session_type IS NULL ) AND
    s.sample_time  > SYSDATE - :sql_mins_hist/(24*60)
  GROUP BY
    s.session_id
  , s.user_id
  , users.username
  ORDER BY
    seconds_busy DESC
)
, session_busy_details
AS(
  SELECT
    bc.sid
  , bc.user_id
  , bc.username
  , bc.seconds_busy
  , (SELECT COUNT(1) from session_history_sample ss WHERE ss.session_id = bc.sid AND ss.user_id = bc.user_id AND ss.session_state = 'ON CPU' AND blocking_session IS NULL ) seconds_on_cpu
  , (SELECT COUNT(1) from session_history_sample ss WHERE ss.session_id = bc.sid AND ss.user_id = bc.user_id AND ss.wait_class    = 'User I/O'  ) seconds_user_io
  , (SELECT COUNT(1) from session_history_sample ss WHERE ss.session_id = bc.sid AND ss.user_id = bc.user_id AND ss.wait_class    = 'System I/O') seconds_system_io
  , (SELECT COUNT(1) from session_history_sample ss WHERE ss.session_id = bc.sid AND ss.user_id = bc.user_id AND NVL(ss.blocking_session,0) > 0 ) seconds_blocked
  FROM
    busy_count bc
  WHERE
      ROWNUM < 10
  ORDER BY 
    bc.seconds_busy DESC
)
SELECT
  sbd.*
, s.blocking_session current_blocking_session
, s.sql_id           current_sql_id
, s.prev_sql_id      prev_sql_id
, s.machine          current_machine
, s.program          current_program
, seconds_busy - seconds_on_cpu - seconds_user_io - seconds_system_io - seconds_blocked seconds_other
FROM
                 session_busy_details sbd
LEFT OUTER JOIN v\$session            s   ON s.sid = sbd.sid AND s.user# = sbd.user_id
ORDER BY 
  sbd.seconds_busy DESC
__SQL_END;

$binds   = '';
$binds[] = array( ":sql_mins_hist", $sql_mins_hist );
$binds[] = array( ":session_type" , $session_type  );

$cur = $db_obj->exec_sql( $sql, $binds );
u::start_tag('div', 'style="'.$div_styles_top_10s.'"' );
$chart_top = $chart_top + $chart_height + 10;
u::start_tag('table','id="data1"');

u::tr('<th>sid</th><th>User</th><th>% of '.$sql_mins_hist.' mins busy</th><th>Blocked by</th><th>Sql ID</th><th>Prev Sql ID</th><th>Program</th><th>Machine</th>');

$i=0;
while ( ( $rec = oci_fetch_object( $cur ) ) ){
    #
    # Row formatting
    #
    $class_str=u::odd_even_class_str($i++);
    
    u::start_tag('tr',$class_str);
    
    $sid_cell            = "<div ".highlight_js_str($rec->SID).">".drilldown_links( $rec->SID, $db, '', $snaps_table )."</div>";

    u::td($sid_cell,'',1);
    u::td($rec->USERNAME,'',1);
    #
    # Covert seconds to % for plotting
    #
    $values  = array(
      $rec->SECONDS_ON_CPU
    , $rec->SECONDS_USER_IO
    , $rec->SECONDS_SYSTEM_IO
    , $rec->SECONDS_BLOCKED
    , $rec->SECONDS_OTHER
    );
    foreach( $values as $key => $value){
        $values[$key] = round( $value*100/($mins_hist*60) );
    }
    u::start_tag('td','style="padding:0;"');
    #
    # Draw percentages as a bar chart
    #
    u::info('Draw Bar chart',1);
    include 'bar_chart.php';
    u::info('Draw Bar chart - Done',1);
    u::end_tag('td');
    
    $highlight_graph_str = "onmouseover=\"highlight('{$rec->CURRENT_BLOCKING_SESSION}');\" ";
    $sid_cell            = "<div $highlight_graph_str>".drilldown_links( $rec->CURRENT_BLOCKING_SESSION, $db, '', $snaps_table )."</div>";

    u::td($sid_cell,'',1);
    u::td("<div ".highlight_js_str($rec->CURRENT_SQL_ID).">".sql_details_link( $rec->CURRENT_SQL_ID, $db )."</div>",'',1);    
    u::td("<div ".highlight_js_str($rec->PREV_SQL_ID)   .">".sql_details_link( $rec->PREV_SQL_ID, $db )   ."</div>",'',1);    
    u::td($rec->CURRENT_PROGRAM,'',1);
    u::td($rec->CURRENT_MACHINE,'style="border-right:0px;"',1);
    u::end_tag('tr');
}
u::filler_rows( $table_rows, $i, 8);
u::end_tag('table');
u::end_tag('div');
?>
