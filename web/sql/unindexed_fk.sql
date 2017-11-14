WITH
  fk_cols
AS
(
  SELECT
    cons.owner
  , cons.table_name
  , cons_cols.column_name
  , cons.constraint_name
  FROM dba_constraints cons
    JOIN dba_cons_columns cons_cols ON cons.constraint_name = cons_cols.constraint_name
  WHERE cons.constraint_type= 'R' 
  AND   cons.owner != 'SYS'
)
, unindexed_fk_cols
AS
(
  SELECT
    owner
  , table_name
  , column_name 
  FROM fk_cols 
MINUS
  SELECT 
    table_owner owner
  , table_name
  , column_name 
  FROM dba_ind_columns WHERE column_position=1
)
, sql_using_unindexed_fk_cols
AS
(
  SELECT DISTINCT 
    ufc.owner
  , ufc.table_name
  , ufc.column_name
  , s.sql_id 
  FROM v$sql_plan s 
  JOIN unindexed_fk_cols ufc ON ufc.table_name = s.object_name AND (s.access_predicates LIKE '%'||ufc.column_name||'%' OR s.filter_predicates LIKE '%'||ufc.column_name||'%')
)
  SELECT
    suufc.*
  , fc.constraint_name
  FROM
    sql_using_unindexed_fk_cols suufc
  JOIN fk_cols fc on fc.owner = suufc.owner AND fc.table_name = suufc.table_name AND fc.column_name = suufc.column_name
  ORDER BY
    suufc.owner
  , suufc.table_name
  , suufc.column_name
