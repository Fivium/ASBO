<?php
#
# $Id: //Infrastructure/GitHub/Database/avo/web/sql_details.php#2 $
#
# Information and history on this statment
# - T Dale 2010
#

include 'start.php';
$sql_id = u::request_val( 'sql_id' );
#
# links to previous sql for sessions
# good for recursive lob sql
#
# - need to change to look at v$open_cursor and find the lob object
# - this would be better, I think
#
$enterprise = $db_obj->enterprise_edition();

$sql     = "select distinct prev_sql_id prev_sql_id from v\$session where sql_id = :sql_id and prev_sql_id != :sql_id";
$binds   = '';
$binds[] = array( ":sql_id", $sql_id );
$cur     = $db_obj->exec_sql( $sql, $binds );

while ( ( $rec = oci_fetch_object( $cur ) ) ){
    p( "Prev sql's : " . sql_details_link( $sql_id, $db ) . "\n" );
}
#
# Hidden sql
#
#include 'look_for_hidden_sql.php';
#
# Sql text
#
$sql     = "select sql_fulltext sql_fulltext from v\$sqlarea where sql_id = :sql_id";
$binds   = '';
$binds[] = array( ":sql_id", $sql_id );

p('<pre>');
#
# Links more sql details
#

if( $enterprise ){
    u::a('Monitor SQL Execution', "sql_monitor.php?db=$db&sql_id=$sql_id"        );
}
u::a('SQL Plan Management'  , "sql_plan_management.php?db=$db&sql_id=$sql_id");

p('');
#
# The sql
#
if($rec = $db_obj->single_rec($sql,$binds) ){
    while(!$rec->SQL_FULLTEXT->eof()){ echo $rec->SQL_FULLTEXT->read(2000); }
}
p("\n");

$create_outline_link = u::a('Create Outline', "show_page.php?db=$db&page=run_sql.php&sql_file=create_outline&param1=$sql_id&param2='||child_number||'&dbms_output=1",1);
$create_outline_link = "'".$create_outline_link."'";
#
# Total stats for this sql
#
p('Sql details from sga');

if( $enterprise ){
    $sql_extra = ', sql_plan_baseline, sql_profile';
}else{
    $sql_extra = ", $create_outline_link create_outline, outline_category"; 
}
#
# from sqlstat to get IO info
#
$sql ="
SELECT 
  physical_read_requests
, physical_read_bytes 
, physical_write_requests
, physical_write_bytes
, executions
, ROUND( physical_read_requests /decode(executions,0,1,executions) ) avg_reads
, ROUND( physical_read_bytes    /decode(executions,0,1,executions) ) avg_read_bytes
, ROUND( physical_write_requests/decode(executions,0,1,executions) ) avg_writes
, ROUND( physical_write_bytes   /decode(executions,0,1,executions) ) avg_write_bytes
 FROM
      v\$sqlarea
WHERE sql_id = :sql_id";

$binds   = '';
$binds[] = array( ':sql_id', $sql_id );

$cur     = $db_obj->exec_sql( $sql, $binds );
$db_obj->html_results( $cur );
#
# By child cursors
#
$sql     = "
select
  --
  -- get child info
  --
  sql_id
, executions
, plan_hash_value
, child_number
, optimizer_cost
, to_char(last_active_time,'dd-mon-yyyy hh24:mi:ss') last_active_time
, round(   buffer_gets    /decode(executions,0,1,executions)          , 2 ) avg_buffer_gets
, round( ( plsql_exec_time/decode(executions,0,1,executions) )/1000000, 2 ) avg_plsql_time_sec
, round(   disk_reads     /decode(executions,0,1,executions)          , 2 ) avg_disk_reads
, round(   direct_writes  /decode(executions,0,1,executions)          , 2 ) avg_direct_writes
, round( ( elapsed_time                                      )/1000000, 2 ) elapsed_secs
, round( ( elapsed_time   /decode(executions,0,1,executions) )/1000000, 2 ) avg_time_sec
, is_bind_aware
$sql_extra
from v\$sql
where sql_id = :sql_id
order by last_active_time desc";

$binds   = '';
$binds[] = array( ':sql_id', $sql_id );

$cur     = $db_obj->exec_sql( $sql, $binds );
$db_obj->html_results( $cur );
#
# Check for outlines, profiles etc
#
if( !$enterprise ){
    p('');
    p('Outline Details');
    $sql = file_get_contents( "./sql/display_outline.sql" );
    $db_obj->dbms_output_on();
    $binds   = '';
    $binds[] = array( ":param1"  , $sql_id   );
    $cur = $db_obj->exec_sql( $sql, $binds );
    $db_obj->get_dbms_output();
    oci_free_statement($cur);
}


p('');
p('Sql History');

#
# Now history, if there is any caught in awr
#
$sql = <<<END_SQL
select  
  ss.plan_hash_value phv
, to_char(s.begin_interval_time, 'DD-MON HH24:MI') snap_time
,          ss.executions_delta                                                                         execs
, round(   ss.buffer_gets_delta            /decode( ss.executions_delta,0,1,ss.executions_delta )    ) lio_per_exec
, round(   ss.disk_reads_delta             /decode( ss.executions_delta,0,1,ss.executions_delta )    ) pio_per_exec
, round( ( ss.cpu_time_delta     /1000000 )/decode( ss.executions_delta,0,1,ss.executions_delta ), 2 ) cpu_per_exec
, round( ( ss.elapsed_time_delta /1000000 )/decode( ss.executions_delta,0,1,ss.executions_delta ), 2 ) elapsed_per_exec
, round(   ss.direct_writes_delta          /decode( ss.executions_delta,0,1,ss.executions_delta )    ) direct_writes_per_exec
, round( ( ss.plsexec_time_delta /1000000 )/decode( ss.executions_delta,0,1,ss.executions_delta ), 2 ) plsql_time_per_exec
from
  dba_hist_snapshot s,
  dba_hist_sqlstat  ss
where   
  ss.dbid = s.dbid and     
  ss.instance_number = s.instance_number and     
  ss.snap_id = s.snap_id and     
  ss.sql_id = :sql_id and     
  ss.executions_delta > 0 and     
  s.begin_interval_time >= sysdate - 7
order by  
  s.snap_id desc
, ss.plan_hash_value
END_SQL;

#
# If we are using oracle standard with snapper
# then need to change the tables in the query
#
include 'subs_for_local_awr_tabs.php';

$binds   = '';
$binds[] = array( ':sql_id', $sql_id );
$cur     = $db_obj->exec_sql( $sql, $binds );

$last_snap_time = $oracle_sysdate;

$db_obj->html_results( $cur );

p('Current sql details');
p('');
#
# Current execution plans
#
$sql     = "select plan_table_output plan_line from table(dbms_xplan.display_cursor(:sql_id, null, 'Advanced'))";
$binds   = '';
$binds[] = array( ":sql_id", $sql_id );
$cur     = $db_obj->exec_sql( $sql, $binds );

while ( ( $rec = oci_fetch_object( $cur ) ) ){
    p( $rec->PLAN_LINE );
}

include 'end.php';

?>
