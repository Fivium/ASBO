<?php
#
# $Id: //Infrastructure/GitHub/Database/avo/web/table_move.php#2 $
#
include 'start.php';
#
# TBS stuff
#
$owner      = u::request_val( 'owner' );
$table_name = u::request_val( 'table_name' );

$sql = "
with move_sql
as(
select 1 build_order, t.table_name segment_name, t.owner, t.table_name, 'alter table \"'||t.owner||'\".\"'||t.table_name||'\" move tablespace tbsdata' cmd from dba_tables t
union
select 2 build_order, l.segment_name,l.owner, l.table_name,' lob (\"'||l.column_name||'\") store as ( tablespace tbsclob index tbsclob )' cmd from dba_lobs l
union
select 3 build_order, '' segment_name,:owner, :table_name, ';' cmd from dual
union
select 4 build_order, i.index_name segment_name, i.owner, i.table_name,'alter index \"'||owner||'\".\"'||i.index_name||'\" rebuild tablespace tbsidx;' cmd from dba_indexes i where index_type != 'LOB'
)
select 
  ms.cmd || case when s.bytes is not null then '-- size mb : ' || round( s.bytes/1024/1024 ) end move_sql
from
  move_sql ms 
  left outer join dba_segments s on ms.segment_name = s.segment_name and ms.owner = s.owner 
where
  ms.owner = :owner and ms.table_name = :table_name
order by 
  ms.build_order
";
#p($sql);

$binds   = '';
$binds[] = array( ":owner"     , $owner      );
$binds[] = array( ":table_name", $table_name );
$cur     = $db_obj->exec_sql( $sql, $binds );
$db_obj->html_results( $cur );

include 'end.php';
?>
