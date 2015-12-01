<?php
#
# $Id: //Infrastructure/GitHub/Database/avo/web/sql_hist.php#2 $
#
include 'start.php';

$sql = <<<END_SQL
select  
  ss.plan_hash_value phv
, to_char(s.begin_interval_time, 'DD-MON HH24:MI') snap_time
, ss.executions_delta execs
, round(ss.buffer_gets_delta/decode(ss.executions_delta,0,1,ss.executions_delta))              lio_per_exec
, round(ss.disk_reads_delta/decode(ss.executions_delta,0,1,ss.executions_delta))               pio_per_exec
, round((ss.cpu_time_delta/1000000)/decode(ss.executions_delta,0,1,ss.executions_delta),2)     cpu_per_exec
, round((ss.elapsed_time_delta/1000000)/decode(ss.executions_delta,0,1,ss.executions_delta),2) elapsed_per_exec
, round(ss.direct_writes_delta/ss.executions_delta)                                            direct_writes_per_exec
, round((ss.plsexec_time_delta/1000000)/decode(ss.executions_delta,0,1,ss.executions_delta),2) plsql_time_per_exec
from
  __hist_snapshot__ s,
  __hist_sqlstat__  ss
where   
  ss.dbid               = s.dbid and     
  ss.instance_number    = s.instance_number and     
  ss.snap_id            = s.snap_id and     
  ss.sql_id             = :sql_id and     
  ss.executions_delta   > 0 and     
  s.begin_interval_time >= sysdate - 7
order by  
  s.snap_id desc
, ss.plan_hash_value
END_SQL;

include 'subs_for_histoty_tables.php';

$cur = oci_parse( $conn, $sql );
oci_bind_by_name( $cur, ":sql_id", $_GET['sql_id'] );
oci_execute( $cur );

p('<!--');
p($sql);
p('-->');

$last_snap_time = $oracle_sysdate;

p('<style>table td {white-space: nowrap;}</style>');
p('<table>');
$print_header = 1;
while ( ( $rec = oci_fetch_object( $cur ) ) ){
    p('<tr>');
    #
    # Display column headers
    #
    if( $print_header ){
        p('<tr>');
        foreach ( $rec as $key => $val ){ td($key);}
        p('</tr>');
        $print_header = 0;
    }
    #
    # Row data
    #
    p('<tr>');
    foreach ( $rec as $key => $val ){ td($val); }
    p('</tr>');
}
p('</table>');

include 'end.php';

?>
