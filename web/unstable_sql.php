<?php
#
# $Id: //Infrastructure/GitHub/Database/asbo/web/unstable_sql.php#1 $
#
#

include 'start.php';

$enterprise = $db_obj->enterprise_edition();
$sql = file_get_contents("./sql/unstable_sql.sql");
include 'subs_for_history_tables.php';
$binds = array();
$binds[] = array( 'db_str', $db );
$binds[] = array( 'pct_stddev_of_mean', u::request_val('pct_stddev_of_mean',50) );
$cur = $db_obj->exec_sql( $sql, $binds );
$db_obj->html_results( $cur );

include 'end.php';

?>
