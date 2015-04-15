--
-- $Id: //Infrastructure/GitHub/Database/avo/web/sql/create_outline.sql#2 $
-- 
-- Create an outline for this sql
--
DECLARE
  l_sql_id   v$sql.sql_id%TYPE       := :param1;
  l_child_no v$sql.child_number%TYPE := :param2;
  l_sql_hash v$sql.hash_value%TYPE;
  l_sql_sig  RAW(32);
  l_sql_text v$sqlarea.sql_fulltext%TYPE;

  PROCEDURE p(p_str VARCHAR2) IS BEGIN DBMS_OUTPUT.PUT_LINE(p_str); END;
BEGIN
  --
  -- Turn on creation
  --
  p('Enabling outline creation and use - need to issue again after instance bounce ');
  EXECUTE IMMEDIATE 'ALTER SYSTEM SET create_stored_outlines=TRUE';
  EXECUTE IMMEDIATE 'ALTER SYSTEM SET query_rewrite_enabled=TRUE';
  EXECUTE IMMEDIATE 'ALTER SYSTEM SET use_stored_outlines=DEFAULT';
  --
  -- Get the sql hash
  --
  SELECT 
    DISTINCT 
    hash_value
  , child_number 
  INTO 
    l_sql_hash
  , l_child_no 
  FROM 
    v$sql
  WHERE 
    sql_id       = l_sql_id AND
    child_number = l_child_no;

  p('Creating outline for : ' || l_sql_id || ', Child : ' || l_child_no);
  --
  -- Create 
  --
  DBMS_OUTLN.CREATE_OUTLINE(
    hash_value    => l_sql_hash
  , child_number  => l_child_no
  );  
  p('Outline created');
  --
  -- Display outline details
  --
  SELECT sql_fulltext INTO l_sql_text FROM v$sqlarea WHERE sql_id = l_sql_id;
  
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
    p('Name : ' || l_rec.name || ', Category : ' || l_rec.category || ', Used : ' || l_rec.used || ', Enabled : ' || l_rec.enabled || ', Hintcount ' || l_rec.hints  );
  END LOOP;

END;
