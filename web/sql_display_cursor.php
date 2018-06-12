<?php
#
# Display last execution stats
#

include 'start.php';
$sql_id = u::request_val( 'sql_id' );
#
# Current execution plans
#
$sql     = "select plan_table_output plan_line from table(dbms_xplan.display_cursor(:sql_id, null, 'ALLSTATS LAST'))";
$binds   = '';
$binds[] = array( ":sql_id", $sql_id );
$cur     = $db_obj->exec_sql( $sql, $binds );

p('<pre>');
while ( ( $rec = oci_fetch_object( $cur ) ) ){
    p( $rec->PLAN_LINE );
}
p('</pre>');

include 'end.php';

?>
