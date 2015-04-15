<?php
#
# $Id$
#
include 'start.php';

$sid    = u::request_val('sid');
$serial = u::request_val('serial');

if ( $sid and $serial ){
  $kill_sql = "alter system kill session '$sid,$serial'";
  p('SQL : '.$kill_sql);
  $cur = $db_obj->exec_sql( $kill_sql );
  p(' DONE!');
}else{
  p( 'Pamarms not given' );
}

include 'end.php';

?>
