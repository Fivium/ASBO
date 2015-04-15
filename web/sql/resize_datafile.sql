--
-- $Id: //Infrastructure/GitHub/Database/avo/web/sql/resize_datafile.sql#2 $
--
with file_hwm as( select file_id, max(block_id+blocks-1) hwm from dba_extents group by file_id ) 
select 
  'alter database datafile '''||file_name||''' resize ' || ceil( (nvl(hwm,1)*:param1)/1024/1024 )  || 'm;' cmd
from 
     dba_data_files f
join file_hwm       hwm on hwm.file_id = f.file_id
where 
  f.file_id = :param2
