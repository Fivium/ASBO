<?php
#
# $Id: //Infrastructure/GitHub/Database/avo/web/active_sessions_details.php#2 $
# Details for each cell shown in the active sessions view
#
$sql = <<<__SQL_END
SELECT
  sample_time
, session_state
, wait_class
, sql_id
, session_id
, nvl(program,'Unknown') program
, blocking_session
FROM
  $snaps_table sd
WHERE
  sample_time > sysdate - :mins_hist/(24*60)
ORDER BY
  sample_time
, session_id
__SQL_END;

$binds   = '';
$binds[] = array( ":mins_hist", $mins_hist );
$cur = $db_obj->exec_sql( $sql, $binds );

$last_snap_time = $oracle_sysdate;

while ( ( $rec = oci_fetch_object( $cur ) ) ){
    $col++;

    $div_id = $rec->SAMPLE_TIME . '_' . $rec->SESSION_ID;
    u::start_tag('div', 'id="'.$div_id.'" class="sess_detail" style="display:none;position:absolute;top:270px;left:100px;border:2px solid black"');
    u::start_tag('table', 'class="data1"');
    u::tr("<th>Info</th><th>Value</th>");
    u::tr("<td>Time</td><td>".$rec->SAMPLE_TIME."</td>",'class=alt');
    u::tr("<td>State</td><td>".$rec->SESSION_STATE."</td>");
    u::tr("<td>Waitclass</td><td>".$rec->WAIT_CLASS."</td>",'class=alt');
    u::tr("<td>SID</td><td>".$rec->SESSION_ID."</td>");
    u::tr("<td>Program</td><td>".$rec->PROGRAM."</td>",'class=alt');
    #
    # session running some sql?
    #
    if( $rec->SQL_ID ) u::tr("<td>sqlid</td><td>".$rec->SQL_ID."</td>");
    #
    # Blocked?
    #
    if( $rec->BLOCKING_SESSION ) u::tr("<td>Blocked by</td><td>".$rec->BLOCKING_SESSION."</td>");
    u::end_tag('table');
    u::end_tag('div');
}

oci_free_statement($cur);

?>
