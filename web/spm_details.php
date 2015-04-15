<?php
#
# $Id: //Infrastructure/GitHub/Database/avo/web/spm_details.php#2 $
#
include 'start.php';
#
# SPM Reoprt
#
$sql     = "select * from table( dbms_xplan.display_sql_plan_baseline( sql_handle=>:sql_handle, plan_name=>:plan_name, format=>'TYPICAL' ) )";
$binds   = '';
$binds[] = array( ":sql_handle", u::request_val( 'sql_handle'    ) );
$binds[] = array( ":plan_name" , u::request_val( 'plan_name', '' ) );
$cur     = $db_obj->exec_sql( $sql, $binds );
$db_obj->html_results( $cur, 0 );
include 'end.php';
?>
