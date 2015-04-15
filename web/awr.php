<?php
#
# $Id: //Infrastructure/GitHub/Database/avo/web/awr.php#2 $
#
# Display some sql stats
#
include 'start.php';

$db_obj->dbms_output_on();

$sql = file_get_contents( "./sql/awr_sql.sql" );

$start = u::request_val( 'start' );
$end   = u::request_val( 'end'   );

$binds = '';

#
# Using our own awr tables?
#
if( u::request_val( 'sub', 1 ) ){
    include 'subs_for_local_awr_tabs.php';
    #
    # Still use oracle tables for the os stats
    # - so get these snap endpoints
    #    
    $awr_date_fmt  = 'YYYY_MM_DD__HH24';
    $start_sql     = "SELECT min(snap_id) max_snap_id FROM dba_hist_snapshot WHERE begin_interval_time >= TO_DATE(:p_start,'$awr_date_fmt')";
    $end_sql       = "SELECT max(snap_id) min_snap_id FROM dba_hist_snapshot WHERE end_interval_time   <= TO_DATE(:p_end,'$awr_date_fmt')";
    $os_start_snap = $db_obj->single_rec( $start_sql, array( array( ':p_start', $start ) ) )->MAX_SNAP_ID;
    $os_end_snap   = $db_obj->single_rec( $end_sql  , array( array( ':p_end'  , $end   ) ) )->MIN_SNAP_ID;

    #u::p("OS Stat Snaps $os_start_snap to $os_end_snap");

    $binds[] = array( ":os_start_snap", $os_start_snap );
    $binds[] = array( ":os_end_snap"  , $os_end_snap   );      
}else{
    #
    # Using awr snaps
    #
    $binds[] = array( ":os_start_snap", '0' );
    $binds[] = array( ":os_end_snap"  , '0' );    
}

#p('---SQL------------------');
#p($sql);
#p('---SQL------------------');

p("<title>Quick AWR Report for $db</title>");

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
