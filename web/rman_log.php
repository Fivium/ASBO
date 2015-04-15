<?php
#
# $Id$
#
include 'start.php';

$binds = array(array(':session_recid', u::request_val('session_recid')));

$cur     = $db_obj->exec_sql( 'select output from v$rman_output where session_recid=:session_recid',$binds );

$db_obj->html_results( $cur, 0 );

include 'end.php';
?>
