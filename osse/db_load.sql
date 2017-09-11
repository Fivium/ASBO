--
-- Database load generator for demos
-- $Id: //Infrastructure/GitHub/Database/asbo/osse/db_load.sql#2 $
--
SET SERVEROUTPUT ON
EXEC DBMS_OUTPUT.PUT_LINE('DBA_SOURCE select')
/
DECLARE
  a INT;
BEGIN
  FOR i IN 1..15 LOOP
    SELECT SUM(LENGTH(TEXT)) INTO a FROM dba_source;
  END LOOP;
END;
/
EXEC DBMS_OUTPUT.PUT_LINE('cpu hog')
/
--
-- CPU Hog
--
DECLARE
  c_cpus_to_hog CONSTANT INTEGER :=8;

  l_job_out    INTEGER;
  l_what       VARCHAR2(1000);
  l_loop_count VARCHAR2(10) := '10000000';
BEGIN
  /*
  ** Create some jobs to load the CPU
  */
  l_what := 'declare a number := 1; begin for i in 1..'||l_loop_count||' loop a := ( a + i )/11; end loop; end;';

  FOR l_job IN 1..c_cpus_to_hog LOOP

    dbms_job.submit(
      job  => l_job_out
    , what => 'declare a number := 1; begin for i in 1..'||l_loop_count||' loop a := ( a + i )/11; end loop; end;'
    );

    COMMIT;

  END LOOP;

END;
/
EXEC DBMS_OUTPUT.PUT_LINE('little io writes')
/
--
-- Some little writes
--
BEGIN
  FOR i IN 1..1000000 LOOP
    COMMIT WRITE WAIT;
  END LOOP;
END;
/
CREATE TABLE dbamgr.for_testing_please_delete( a INT, b VARCHAR2(30), c CLOB )
/
EXEC DBMS_OUTPUT.PUT_LINE('Some inserts and commits')
/
--
-- DML Load
--
DECLARE
  c_char       CONSTANT VARCHAR2(1)    := '1';
  --
  -- Clob that will be out of line
  --
  c_val_big    CONSTANT CLOB           := LPAD(c_char,8000,c_char);
  c_val        CONSTANT VARCHAR2(30)   := LPAD(c_char,30  ,c_char);
BEGIN
    --
    -- Some dummy dml
    --
    DELETE FROM dbamgr.for_testing_please_delete;
    COMMIT WRITE WAIT;

    FOR i IN 1..2000 LOOP
      INSERT INTO dbamgr.for_testing_please_delete VALUES(i,c_val,c_val_big);
      COMMIT WRITE WAIT;
    END LOOP;

END;
/
DROP TABLE dbamgr.for_testing_please_delete
/
EXEC DBMS_OUTPUT.PUT_LINE('all done')
/
