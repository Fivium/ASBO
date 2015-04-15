--
-- $Id: //Infrastructure/GitHub/Database/avo/web/sql/datafile_contents.sql#2 $
--
    select 
    --
    -- Segment blocks
    --
      e.file_id
    , e.block_id first_block
    , e.block_id+e.blocks-1 last_block
    , e.blocks
    , round((e.blocks*8)/1024) extent_size_mb
    , e.segment_name
    , e.owner
    , s.segment_type
    , round(s.bytes/1024/1024) segment_size_mb
    , case s.segment_type when 'LOBSEGMENT' then (select table_name from dba_lobs l where l.owner = e.owner and l.segment_name = e.segment_name ) else '' end lob_table
    , case s.segment_type when 'LOBINDEX'   then (select table_name from dba_lobs l where l.owner = e.owner and l.index_name   = e.segment_name ) else '' end lob_index_table    
    from 
         dba_extents  e
    join dba_segments s on s.segment_name = e.segment_name 
    where 
      e.tablespace_name = :tbs and 
      e.file_id         = :file_id
  union all
    select
    --
    -- Free space under the hwm
    --     
      file_id
    , block_id first_block
    , block_id+blocks-1 last_block
    , blocks
    , round((blocks*8)/1024) extent_size_mb
    , 'free' segment_name
    , 'none' owner
    , 'free' segment_type
    , 0      segment_size_mb
    , ''     lob_table
    , ''     lob_index_table
    from 
      dba_free_space
    where 
      tablespace_name = :tbs and 
      file_id         = :file_id
  order by
    first_block desc
