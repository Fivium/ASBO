<?php
#
# $Id: //Infrastructure/GitHub/Database/avo/web/tbs_contents.php#2 $
#
include 'start.php';
#
# TBS stuff
#
$tbs = u::request_val( 'tbs' );

$sql1 = "
select 
--  '<a href=datafile_contents.php?db=$db&tbs=$tbs&file_id='||df.file_id||'>'||df.file_name||'</a>' file_name
  df.file_name file_name
, file_id,round(bytes/1024/1024/1024) size_gb,autoextensible auto_extend,round(maxbytes/1024/1024/1024) max_size_gb
, (select round(sum(fs.bytes) / (1024 * 1024)) free_mb from dba_free_space fs where fs.tablespace_name = :tbs and fs.file_id = df.file_id) free_mb
, '<a href=run_sql.php?db=$db&param1=8192&sql_file=resize_datafile&param2='||df.file_id||'>Resize</a>' resize_datafile_cmd
from
( 
  select tablespace_name, file_name,maxbytes,file_id, bytes, autoextensible from dba_data_files
  union all
  select tablespace_name, file_name,maxbytes,file_id, bytes, autoextensible from dba_temp_files
) df
where 
  tablespace_name=upper(:tbs)
";

$sql2 = "
select 
  s.owner || '.' || s.segment_name name
, s.segment_type
, round( s.bytes/(1024*1024) ) size_mb
, '<a href=table_move.php?db=$db&owner='||s.owner||'&table_name='||nvl( l.table_name, nvl( i.table_name, s.segment_name ) )||'>'||nvl( l.table_name, nvl( i.table_name, s.segment_name ) )||'</a>' move_table_sql
from
                dba_segments s
left outer join dba_lobs     l on l.owner = s.owner and l.segment_name = s.segment_name
left outer join dba_indexes  i on i.owner = s.owner and i.index_name   = s.segment_name
where 
  s.tablespace_name = upper(:tbs)
order by 
  s.bytes desc
";
#p($sql);

if( $tbs ){
    $binds   = '';
    $binds[] = array( ":tbs", $tbs );
    $cur     = $db_obj->exec_sql( $sql1, $binds );
    $db_obj->html_results( $cur );
    $cur     = $db_obj->exec_sql( $sql2, $binds );
    $db_obj->html_results( $cur );

}
include 'end.php';
?>
