--
-- Create a sql patch for a specified sql_id
--
-- - Needs to be run as sys
--
-- - c_sql_patch_text is an example of a patch
-- - query block names need to be use
-- - eg INDEX(@SEL$1 E@SEL$1 EMP_IDX1) to specify index EMP_IDX1 on the first query block
--
DECLARE
  c_new_sql_patch_name CONSTANT VARCHAR2(30) := 'patch_'||TO_CHAR(SYSDATE,'YYYY_MM_DD__HH24_MI_SS');
  c_sql_id             CONSTANT VARCHAR2(30) := '__SQL_ID__';
  c_sql_patch_text     CONSTANT VARCHAR2(999):= 'DYNAMIC_SAMPLING(4) GATHER_PLAN_STATISTICS INDEX(@SEL$1 E@SEL$1 EMP_IDX1)';
  c_drop_patchs_first  CONSTANT VARCHAR2(1)  := 'Y';
  l_sql_text                    CLOB;

  e_patch_exists  EXCEPTION;
  PRAGMA EXCEPTION_INIT(e_patch_exists,-13830);

  PROCEDURE p(p_str VARCHAR2) IS BEGIN DBMS_OUTPUT.PUT_LINE(p_str); END;
BEGIN
  --
  -- Get the sql text
  --
  SELECT sql_fulltext INTO l_sql_text FROM v$sql WHERE sql_id = c_sql_id AND ROWNUM = 1;

  p('SQL_ID         : ' || c_sql_id);
  p('SQL Text       : ' || TO_CHAR(DBMS_LOB.SUBSTR( l_sql_text, 1000, 1 )));

  --
  -- Do we want to drop patch first?
  --
  IF c_drop_patchs_first = 'Y' THEN

    FOR l_rec IN ( SELECT name patch_name, sql_text FROM dba_sql_patches sp ) LOOP

      IF l_rec.sql_text = l_sql_text THEN
        p('Dropping patch : ' || l_rec.patch_name);
        SYS.DBMS_SQLDIAG.DROP_SQL_PATCH(
          name   => l_rec.patch_name
        , ignore => TRUE
        );
      END IF;

    END LOOP;

  END IF;
  --
  -- Create patch
  --
  p('Creating patch : ' || c_new_sql_patch_name);
  p('Patch text     : ' || c_sql_patch_text);

  SYS.DBMS_SQLDIAG_INTERNAL.I_CREATE_PATCH(
    sql_text  => l_sql_text
  , hint_text => c_sql_patch_text
  , name      => c_new_sql_patch_name
  );

EXCEPTION
  WHEN e_patch_exists THEN p('ERROR : A patch already exists on this sql');
END;
/
