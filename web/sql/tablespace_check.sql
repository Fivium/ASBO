--
-- $Id: //Infrastructure/GitHub/Database/avo/web/sql/tablespace_check.sql#2 $
--
with tbs_use
as
(
  select
    NVL( b.tablespace_name, NVL( a.tablespace_name, '??' ) ) tablespace_name
  , ROUND( kbytes_alloc/1024, 0) + ( select NVL(round( ( sum(NVL(maxbytes,0)) - sum(NVL(user_bytes,0)) )/1024/1024 ),0) from dba_data_files where tablespace_name = b.tablespace_name and autoextensible='YES') total_available
  , ROUND( (kbytes_alloc-nvl(kbytes_free,0))/1024, 2)   used 
  , (ROUND( (kbytes_alloc-nvl(kbytes_free,0))/1024, 2))/(ROUND( kbytes_alloc/1024, 0) + ( select NVL(round( ( sum(NVL(maxbytes,0)) - sum(NVL(user_bytes,0)) )/1024/1024 ),0) from dba_data_files where tablespace_name = b.tablespace_name and autoextensible='YES'))*100 pct_used
  from 
   (select
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
  , dba_tablespaces c
where a.tablespace_name (+) = b.tablespace_name and
      a.tablespace_name = c.tablespace_name and
      c.contents        ='PERMANENT'
)
select 
    t.tablespace_name
  , t.total_available
  , t.used
  , round(t.pct_used) pct_used
  , case when pct_used > 93 then 'CRITICAL' when pct_used > 90 then 'WARNING' else 'OK' end prob
  , '<a href=tbs_contents.php?db='||:db_str||'&tbs='||t.tablespace_name||'>Details</a>'  details
from 
  tbs_use t
