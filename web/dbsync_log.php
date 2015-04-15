<?php
#
# $Id$
#
include 'start.php';

#
# What log do we want?
#
$type = u::request_val('type');

if( $type == 'hist' ){
   u::p('Getting History log');
   $sql     = "select logfile log from dbamgr.dbsync_standby_hist where hist_id = :hist_id";
   $binds   = '';
   $binds[] = array( ":hist_id", u::request_val('hist_id') );
}else{
   u::p('Getting log');
   if( $type == 'full' ){
      $col = 'last_full_refresh_log';    
   }else{
       $col = 'last_rollforward_log';
   }

   $sql     = "select $col log from dbamgr.dbsync_standby where standby_id = :standby_id";
   $binds   = '';
   $binds[] = array( ":standby_id", u::request_val('standby_id') );
}
p('<pre>');
#
# The log
#
if($rec = $db_obj->single_rec($sql,$binds) ){
    while(!$rec->LOG->eof()){ echo $rec->LOG->read(2000); }
}
p("\n");

include 'end.php';

?>

