<?php
#
# $Id: //Infrastructure/GitHub/Database/avo/web/sql_monitor.php#2 $
#

include 'start.php';

$sid    = u::request_val('sid');
$sql_id = u::request_val('sql_id');

$sql  = "select DBMS_SQLTUNE.REPORT_SQL_MONITOR(sql_id=>'$sql_id', session_id=>:sid, report_level=>'ALL', type=>'HTML' ) report from dual";

$rec = $db_obj->single_rec($sql,array( array( ":sid", $sid ) ));

if ($rec){
    while(!$rec->REPORT->eof()){ echo $rec->REPORT->read(2000); }
}

include 'end.php';

?>
