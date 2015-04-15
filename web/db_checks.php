<?php
#
# $Id$
#
if( !isset( $just_checks ) ) $just_checks = 0;

if( $just_checks !== 1 ) include 'start.php';

    #
    # Check full backup status
    #
    $sql = file_get_contents("./sql/backup_check.sql");
    $binds   = '';
    $binds[] = array( ":db_str", $db );
    $cur = $db_obj->exec_sql( $sql, $binds );
    $db_obj->html_results( $cur );
    #
    # Database space
    #
    $sql = file_get_contents("./sql/tablespace_check.sql");
    $cur = $db_obj->exec_sql( $sql, $binds );
    $db_obj->html_results( $cur );
    #
    # Standby status
    #
    if( $db_obj->using_dbsync() ){
        $binds   = '';
        $binds[] = array( ":db_str", $db );    
        $sql = file_get_contents( "./sql/standby_check.sql" );
        $cur = $db_obj->exec_sql( $sql, $binds );
        $db_obj->html_results( $cur );
     }

    u::flush();

if( $just_checks !== 1 ) include 'end.php';
?>
