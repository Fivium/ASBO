<?php
#
# $Id: //Infrastructure/GitHub/Database/avo/web/session_drilldown.php#2 $
#
# Locks and active session history for the session
#
include 'start.php';

$sid  = u::request_val('sid');
$sql  = 'select s.sid, s.serial# serial,';
$sql .= "'kill -9 ' || p.spid kill_os" ;
$sql .= ' from v$session s left outer join v$process p on s.paddr=p.addr';
$sql .= ' where s.sid = :sid';

$rec = $db_obj->single_rec($sql,array( array( ":sid", $sid ) ));

p('<pre>');
if ($rec){
    p('<a href="kill_session.php?db='.$db.'&sid='.$rec->SID.'&serial='.$rec->SERIAL.'">Kill session oracle</a>');
    p($rec->KILL_OS);
}
p("<a href=\"sql_monitor.php?db=$db&sid=$sid\">Monitor SQL</a> for this session");

#
# Locks
#
$sql = "
select
  l.*
, (select owner || '.' || object_name from dba_objects where object_id = lock_id1) object_name
from dba_locks l
where session_id = :sid
";

$cur = $db_obj->exec_sql( $sql, array( array( ":sid", u::request_val('sid') ) ) );
$db_obj->html_results( $cur );

$sql = "select * from v\$session where sid = :sid";

$cur = $db_obj->exec_sql( $sql, array( array( ":sid", u::request_val('sid') ) ) );
$db_obj->html_results( $cur );

$snaps_table = u::request_val('snaps_table');

$sql = <<<END_SQL
select
/*
** session drill down ash
*/
  TO_CHAR( sample_time, 'HH24:MI:SS') hour_min_sec
, a.*
from 
  $snaps_table a
where 
  a.session_id = :sid and 
  a.sample_time > sysdate - 10/(24*60)
order by a.sample_time desc
END_SQL;


$cur = $db_obj->exec_sql( $sql, array(array(':sid',$sid)) );

$last_snap_time = $oracle_sysdate;
p('<style>table td {white-space: nowrap;border: 1px solid silver;font-family:Courier New}</style>');
p('<table>');
$print_header = 1;
while ( ( $rec = oci_fetch_object( $cur ) ) ){
    #
    # Display column headers
    #
    if( $print_header ){
        p('<tr>');
        foreach ( $rec as $key => $val ){ td($key);}
        p('</tr>');
        $print_header = 0;
    }
    if ( @$_GET['all_secs'] ){
        u::fill_in_missing_snaps( $last_snap_time, $rec->HOUR_MIN_SEC );
        $last_snap_time = $rec->HOUR_MIN_SEC;
    }
    #
    # Row data
    #
    p('<tr>');
    foreach ( $rec as $key => $val ){
        if ( $key === 'SQL_ID' ){
            td( sql_details_link( $val, $db ) );
        }else if( $key === 'CURRENT_OBJ#' and ($val > 0) ){
            td( "<a href=./obj_details.php?db=$db&obj_id=$val>$val</a>" );
        }else if( $key === 'BLOCKING_SESSION' and $val ){
            td( "<a href=./session_drilldown.php?db=$db&sid=$val&snaps_table=$snaps_table>$val</a>" );       
        }else{
            td($val); 
        }
    }
    p('</tr>');

    #p( "<tr><td>$rec->S_TIME</td><td>$rec->SESSION_STATE</td></tr>" );
}
p('</table>');

include 'end.php';

?>
