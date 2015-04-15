<?php
#
# $Id: //Infrastructure/GitHub/Database/avo/web/locks.php#2 $
#
# Locks for the session
# T Dale 
#
include 'start.php';
#
# Locks
#
$sql = "
select 
  l.*
, (select object_name from dba_objects where object_id = lock_id1) object_name  
from dba_locks l 
where session_id = :sid
";

$cur = $db_obj->exec_sql( $sql, array( array( ":sid", u::request_val('sid') ) ) );
$db_obj->html_results( $cur );
include 'end.php';
?>
