<?php
#
# $Id: //Infrastructure/GitHub/Database/asbo/web/db_checks.php#7 $
#
if( !isset( $just_checks ) ) $just_checks = 0;

if(!function_exists("display")) {
    function display($cur,$db_obj,&$any_crits){

        #
        # Buffer the check output
        #
        ob_start();

        if( u::request_val('html') ){
           $db_obj->html_results( $cur );
        }else{
            $db_obj->text_results( $cur );
        }

        $html_buffer = ob_get_contents();
        ob_end_clean();
        #
        # Only criticals
        #
        if( u::request_val('suppress_ok') ){
            #
            # Any criticals?
            #
            if( strpos( $html_buffer, 'CRITICAL' ) ){
                #
                # Display the check result
                #
                echo $html_buffer;
                $any_crits = 1;
            }
        }else{
            echo $html_buffer;
        }
    }
}

if( $just_checks !== 1 ) include 'start.php';
    $any_crits=0;
    $crit_count=0;
    #
    # Check full backup status
    #
    $sql = file_get_contents("./sql/backup_check.sql");
    $binds   = '';
    $binds[] = array( ":db_str", $db );
    $cur = $db_obj->exec_sql( $sql, $binds );
    display( $cur, $db_obj, $any_crits );
    $crit_count += $any_crits;
    #
    # Database space
    #
    $sql = file_get_contents("./sql/tablespace_check.sql");
    $cur = $db_obj->exec_sql( $sql, $binds );
    display( $cur, $db_obj, $any_crits );
    $crit_count += $any_crits;
    #
    # Process count check
    #
    $sql = file_get_contents("./sql/process_count_check.sql");
    $cur = $db_obj->exec_sql( $sql, '' );
    display( $cur, $db_obj, $any_crits );
    $crit_count += $any_crits;
    #
    # Standby status
    #
    if( $db_obj->using_dbsync() ){
        $cur = $db_obj->exec_sql( 'BEGIN dbamgr.dbsync.process_logs; END;', '' );
        $binds   = '';
        $binds[] = array( ":db_str", $db );
        $sql = file_get_contents( "./sql/standby_check.sql" );
        $cur = $db_obj->exec_sql( $sql, $binds );
        display( $cur, $db_obj, $any_crits );
        $crit_count += $any_crits;
    }
    #
    # Datagaurd?
    #
    $dg_check = $db_obj->single_rec('select \'YES\' using_dg from v$parameter where name=\'log_archive_config\' and length(value)>4 and upper(value) not like \'%NODG_CONFIG%\' ');

    if( isset( $dg_check->USING_DG ) ){

        $sql = file_get_contents("./sql/datagaurd_check.sql");
        $cur = $db_obj->exec_sql( $sql );
        display( $cur, $db_obj, $any_crits );
        $crit_count += $any_crits;
    }
    #
    # Log trimming?
    #
    $using_table_trimming = $db_obj->single_rec("select 1 yes from dba_tables WHERE owner='DBAMGR' AND table_name='TABLE_HOUSEKEEPING' ");

    if( isset( $using_table_trimming->YES ) ){
        u::p('Using table trimming');
        $sql = file_get_contents("./sql/table_trim_check.sql");
        $cur = $db_obj->exec_sql( $sql );
        display( $cur, $db_obj, $any_crits );
        $crit_count += $any_crits;
    }
    u::flush();

    $sql = file_get_contents("./sql/fra_space_check.sql");
    $cur = $db_obj->exec_sql( $sql );
    display( $cur, $db_obj, $any_crits );
    $crit_count += $any_crits;
    u::flush();

    if( u::request_val('suppress_ok') and $crit_count === 0 ){
       u::p(' - All checks OK');
    }

if( $just_checks !== 1 ) include 'end.php';
?>
