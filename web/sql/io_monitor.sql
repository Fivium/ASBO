DECLARE
  --
  -- $Id: //Infrastructure/GitHub/Database/avo/web/sql/io_monitor.sql#2 $
  -- 
  c_wait_secs CONSTANT INT := :wait_secs;
  c_top_n     CONSTANT INT := :top_n;
  c_order_by  CONSTANT INT := :order_by;
  l_loop               INT := 0;
  l_idx                INT := 0;
  l_change             INT := 0;
  l_sql_id             v$sqlarea.sql_id%TYPE;
  
  
  CURSOR sqlstat_cur 
  IS
    SELECT
      plan_hash_value
    , sql_id
    , physical_read_requests
    , physical_read_bytes 
    , physical_write_requests
    , physical_write_bytes
    FROM
      v$sqlarea;

  TYPE sqlstat_rec
  IS
    RECORD(
      plan_hash_value         v$sqlarea.plan_hash_value%TYPE         := NULL
    , sql_id                  v$sqlarea.sql_id%TYPE                  := NULL
    , physical_read_requests  v$sqlarea.physical_read_requests%TYPE  := NULL
    , physical_read_bytes     v$sqlarea.physical_read_bytes%TYPE     := NULL
    , physical_write_requests v$sqlarea.physical_write_requests%TYPE := NULL
    , physical_write_bytes    v$sqlarea.physical_write_bytes%TYPE    := NULL     
    );

  TYPE sqlstat_tab IS TABLE OF sqlstat_rec INDEX BY PLS_INTEGER;

  l_io_changes_rec     sqlstat_rec;
    
  l_sqlstat_start_tab  sqlstat_tab;
  l_sqlstat_end_tab    sqlstat_tab;
  l_sqlstat_sorted_tab sqlstat_tab;
  
    
  PROCEDURE p(p_str VARCHAR2) IS BEGIN DBMS_OUTPUT.PUT_LINE( p_str ); END;

  FUNCTION end_value( p_sql_id v$sqlarea.sql_id%TYPE )
    RETURN sqlstat_rec
  IS
    l_sqlstat_rec sqlstat_rec;
  BEGIN
    --
    -- Look for stats on this sql
    --
    FOR i IN 1..l_sqlstat_end_tab.COUNT LOOP
      IF p_sql_id = l_sqlstat_end_tab(i).sql_id THEN
        l_sqlstat_rec := l_sqlstat_end_tab(i);
        EXIT;
      END IF;
    END LOOP;
    
    RETURN l_sqlstat_rec;
  END;         
BEGIN
   --
   -- First sample
   --
   OPEN sqlstat_cur;
     FETCH sqlstat_cur BULK COLLECT INTO l_sqlstat_start_tab;
   CLOSE sqlstat_cur;    

  dbms_lock.sleep(c_wait_secs);
   --
   -- Second sample
   --
   OPEN sqlstat_cur;
     FETCH sqlstat_cur BULK COLLECT INTO l_sqlstat_end_tab;
   CLOSE sqlstat_cur;    
   --
   -- Look for changes
   --
   FOR i IN 1..l_sqlstat_start_tab.COUNT LOOP
     l_sql_id := l_sqlstat_start_tab(i).sql_id;
     
     l_io_changes_rec.sql_id                  := l_sql_id;
     l_io_changes_rec.physical_read_requests  := end_value( l_sql_id ).physical_read_requests  - l_sqlstat_start_tab(i).physical_read_requests;
     l_io_changes_rec.physical_read_bytes     := end_value( l_sql_id ).physical_read_bytes     - l_sqlstat_start_tab(i).physical_read_bytes; 
     l_io_changes_rec.physical_write_requests := end_value( l_sql_id ).physical_write_requests - l_sqlstat_start_tab(i).physical_write_requests;
     l_io_changes_rec.physical_write_bytes    := end_value( l_sql_id ).physical_write_bytes    - l_sqlstat_start_tab(i).physical_write_bytes;
     --
     -- What change are we ording by?
     --
     CASE c_order_by
       WHEN 1 THEN l_change := l_io_changes_rec.physical_read_requests;
       WHEN 2 THEN l_change := l_io_changes_rec.physical_read_bytes;
       WHEN 3 THEN l_change := l_io_changes_rec.physical_write_requests;
       WHEN 4 THEN l_change := l_io_changes_rec.physical_write_bytes; 
     END CASE;     
     --
     -- Simple sort by using the plsql table index
     --
     IF NVL(l_change,0) > 0 THEN
       l_sqlstat_sorted_tab(l_change) := l_io_changes_rec;
     END IF;  
   END LOOP;   
   --
   -- Report top
   --
   l_idx := l_sqlstat_sorted_tab.LAST;
   IF l_idx IS NULL THEN 
     p('Nothing found using physical IO'); 
   ELSE
     p('2 Samples taken '||c_wait_secs||' seconds apart, IO shown as total changed for this period');
     p(LPAD('=',80,'='));
   END IF;

   LOOP
     l_loop := l_loop + 1;
     EXIT WHEN l_loop = c_top_n OR l_idx IS NULL;     

     l_sql_id := l_sqlstat_sorted_tab(l_idx).sql_id;
     p(
       'SQLID : '      || '<a href=sql_details.php?db='||:db_str||'&sql_id='||l_sql_id||'>'|| l_sql_id || '</a>' ||
       ', Reads : '    || lpad( l_sqlstat_sorted_tab(l_idx).physical_read_requests          , 5 )   ||
       ', Read  KB : ' || lpad( ROUND(l_sqlstat_sorted_tab(l_idx).physical_read_bytes/1024) , 5 )   ||
       ', Writes : '   || lpad( l_sqlstat_sorted_tab(l_idx).physical_write_requests         , 5 )   ||
       ', Write KB : ' || lpad( ROUND(l_sqlstat_sorted_tab(l_idx).physical_write_bytes/1024), 5 ) 
     );
     l_idx := l_sqlstat_sorted_tab.PRIOR(l_idx);
   END LOOP; 

END;  
