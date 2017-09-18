<?php
#
# $Id: //Infrastructure/GitHub/Database/asbo/web/kill_session.php#3 $
#
include 'start.php';

$sid    = u::request_val('sid');
$serial = u::request_val('serial');

if ( $sid and $serial ){

  $kill_sql = "
BEGIN
  FOR l_rec IN (SELECT sid,serial# FROM v\$session WHERE sid = :sid AND serial# = :serial) LOOP
    EXECUTE IMMEDIATE 'alter system kill session '||CHR(39)||l_rec.sid||','||l_rec.serial#||CHR(39);
  END LOOP;
END;
";

  p('SQL : '.$kill_sql);
  $binds = '';
  $binds[] = array(':sid',$sid);
  $binds[] = array(':serial',$serial);

  $cur = $db_obj->exec_sql( $kill_sql, $binds );
  p("\n\n\n<br><br>DONE!");
}else{
  p( 'Pamarms not given' );
}

include 'end.php';

?>
