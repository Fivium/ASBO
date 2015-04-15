<?php
#
# $Id: //Infrastructure/GitHub/Database/avo/web/db_size.php#2 $
#
include 'start.php';
#
# Database size details
#
$sqls[] = "SELECT ROUND( SUM(bytes)/1024/1024/1024, 2 ) \"Segment Size Gb\"  FROM dba_segments ";
$sqls[] = "SELECT ROUND( SUM(bytes)/1024/1024/1024, 2 ) \"Datafile Size Gb\" FROM dba_data_files";
$sqls[] = "SELECT ROUND( nvl(sum(bytes),0)/1024/1024/1024, 2 ) \"Temp size Gb\" FROM dba_temp_files";

foreach( $sqls as $sql ){
    $cur     = $db_obj->exec_sql( $sql, '' );
    $db_obj->html_results( $cur );
}

$sql = "
select
  NVL( b.tablespace_name, NVL( a.tablespace_name, '??' ) )            tablespace_name
, ROUND( kbytes_alloc/1024, 2)                                        mb
, ROUND( (kbytes_alloc-nvl(kbytes_free,0))/1024, 2)                   used
, ROUND( ((kbytes_alloc-nvl(kbytes_free,0))/kbytes_alloc)*100, 2)     pct_used
from
  ( select
      sum(bytes)/1024 Kbytes_free
    , max(bytes)/1024 largest
    , tablespace_name
    from
      sys.dba_free_space
    group by
      tablespace_name
  ) a
, (   select
        sum(bytes)/1024 Kbytes_alloc
      , sum(maxbytes)/1024 Kbytes_max
      , tablespace_name
      from
        sys.dba_data_files
      group by
        tablespace_name
    union all
      select
        sum(bytes)/1024 Kbytes_alloc
      , sum(maxbytes)/1024 Kbytes_max
      , tablespace_name
      from
        sys.dba_temp_files
      group by
        tablespace_name
  ) b
where a.tablespace_name (+) = b.tablespace_name
order by 2 desc
";

$cur = $db_obj->exec_sql( $sql, '' );
$db_obj->html_results( $cur, 1, 1, './tbs_contents.php?db='.$db.'&tbs=__VAL__' );

include 'end.php';
?>
