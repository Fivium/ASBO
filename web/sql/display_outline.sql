--
-- $Id: //Infrastructure/GitHub/Database/avo/web/sql/display_outline.sql#3 $
--
-- Details of outline on this sql
--
DECLARE
  l_sql_sig   RAW(32);
  l_sql_text  v$sqlarea.sql_fulltext%TYPE;

  PROCEDURE p(p_str VARCHAR2) IS BEGIN DBMS_OUTPUT.PUT_LINE(p_str); END;
    
BEGIN
  SELECT sql_fulltext INTO l_sql_text FROM v$sqlarea WHERE sql_id = :param1;
  
  sys.outln_edit_pkg.generate_signature(l_sql_text,l_sql_sig);

  FOR l_rec IN (
    SELECT 
      o.category
    , ol_name name
    , decode(bitand(flags, 1), 0, 'UNUSED' , 1, 'USED'   )  used
    , decode(bitand(flags, 4), 0, 'ENABLED', 4, 'DISABLED') enabled
    , hintcount hints 
    FROM 
      outln.ol$ o 
    WHERE signature = l_sql_sig
  ) LOOP

   p('Name : ' || l_rec.name || ', Category : ' || l_rec.category || ', Used : ' || l_rec.used || ', Enabled : ' || l_rec.enabled || ', Hintcount ' || l_rec.hints ); 
    
  END LOOP;

END;
