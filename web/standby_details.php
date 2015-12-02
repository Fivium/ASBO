<?php
#
# $Id: //Infrastructure/GitHub/Database/asbo/web/standby_details.php#1 $
#
include 'start.php';
#
# Standby status
#
if( $db_obj->using_dbsync() ){

    $cur = $db_obj->exec_sql( 'BEGIN dbamgr.dbsync.process_logs; END;', '' );

    u::p('Current Details');
    $binds   = '';
    $binds[] = array( ":db_str", $db );
    $sql = file_get_contents( "./sql/standby_check.sql" );
    $cur = $db_obj->exec_sql( $sql, $binds );
    $db_obj->html_results( $cur );
    u::p('Standby Action History');
    $sql = file_get_contents( "./sql/standby_history.sql" );
    $cur = $db_obj->exec_sql( $sql, $binds );
    $db_obj->html_results( $cur );

}else{
    u::p('No standby for this database');
}




include 'end.php';
?>
