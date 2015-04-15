--
-- $Id: //Infrastructure/GitHub/Database/avo/web/sql/sql_plan_baseline_drop.sql#2 $
--
DECLARE
  l_dropped_count INT           := 0;
  l_s             VARCHAR2(1)   := '';
  l_sql_handle    VARCHAR2(100) := :param1;
  l_sql_plan_name VARCHAR2(100) := :param2;

  PROCEDURE p(p_str VARCHAR) IS BEGIN DBMS_OUTPUT.PUT_LINE( p_str ); END;
BEGIN
  p('Dropping baseline for sql handle : ' || l_sql_handle || ', Plan : ' || l_sql_plan_name);

  l_dropped_count := DBMS_SPM.DROP_SQL_PLAN_BASELINE(l_sql_handle,l_sql_plan_name);

  IF l_dropped_count > 1 THEN
    l_s := 's';
  END IF;
  p('Dropped : ' || l_dropped_count || ' plan' || l_s);
END;
