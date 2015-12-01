<?php
#
# $Id$
#
if ( $use_our_own_history_tables ){
  #
  # SQL text convert to use new local tables
  # - These are tables populated by a session snapper, not enterprise edition tables and views
  #
  $sql = str_replace( '__hist_snapshot__'         , 'dbamgr.snap_details'          ,$sql );
  $sql = str_replace( '__hist_sqlstat__'          , 'dbamgr.hist_sqlstat'          ,$sql );
  $sql = str_replace( '__hist_sqltext__'          , 'dbamgr.hist_sqltext'          ,$sql );
  $sql = str_replace( '__hist_sysmetric_summary__', 'dbamgr.hist_sysmetric_summary',$sql );

}else{
  #
  # Use EE views
  #
  $sql = str_replace( '__hist_snapshot__'         , 'dba_hist_snapshot'         , $sql );
  $sql = str_replace( '__hist_sqlstat__'          , 'dba_hist_sqlstat'          , $sql );
  $sql = str_replace( '__hist_sqltext__'          , 'dba_hist_sqltext'          , $sql );
  $sql = str_replace( '__hist_sysmetric_summary__', 'dba_hist_sysmetric_summary', $sql );

}

?>
