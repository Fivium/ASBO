<?php
#
# $Id: //Infrastructure/GitHub/Database/asbo/web/top_sql_report.php#2 $
#
# Display some sql stats
#
include 'start.php';

$db_obj->dbms_output_on();

$sql = file_get_contents( "./sql/top_sql_report.sql" );

$start = u::request_val( 'start' );
$end   = u::request_val( 'end'   );

$binds = '';

#
# Using our own history tables?
#

include 'subs_for_history_tables.php';
$binds[] = array( ":os_start_snap", '0' );
$binds[] = array( ":os_end_snap"  , '0' );

#p('---SQL------------------');
#p($sql);
#p('---SQL------------------');

p("<title>Quick SQL Report for $db</title>");

$binds[] = array( ":from_date_str"          , $start                             );
$binds[] = array( ":to_date_str"            , $end                               );
$binds[] = array( ":order_by_id"            , u::request_val( 'order_by_id', 1 ) );
$binds[] = array( ":db_str"                 , u::request_val( 'db'             ) );
#
# These binds should be give as options in the page - Todo
#
#$binds[] = array( ":ignore_plsql"           , 1                         );
#$binds[] = array( ":ignore_support_activity", 1                         );
#$binds[] = array( ":ignore_reportmgr"       , 1                         );
#$binds[] = array( ":result_count"           , 20                        );

$cur     = $db_obj->exec_sql( $sql, $binds );

$db_obj->get_dbms_output();

include 'end.php';
?>
