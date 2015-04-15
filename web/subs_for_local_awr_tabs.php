<?php
#
# $Id$
#
if ( $use_local_awr_tables ){
  #
  # SQL text convert to use new local tables
  #
  $sql = str_replace( 'dba_hist_snapshot', 'dbamgr.snap_details',$sql );
  $sql = str_replace( 'dba_hist_sqlstat' , 'dbamgr.hist_sqlstat',$sql );
  $sql = str_replace( 'dba_hist_sqltext' , 'dbamgr.hist_sqltext',$sql );
  $sql = str_replace( "display_awr(r_data.sql_id, null, null, 'ALL -ALIAS')","display_cursor(r_data.sql_id, null, 'ALL -ALIAS')",$sql );

}

?>
