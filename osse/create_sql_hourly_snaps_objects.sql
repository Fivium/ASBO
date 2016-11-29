--
-- $Id: //Infrastructure/GitHub/Database/avo/osse/create_sql_hourly_snaps_objects.sql#1 $
--
-- T Dale 2012
-- Objects for storing snap hist
--
CREATE TABLE dbamgr.snap_details
( dbid                NUMBER
, instance_number     NUMBER
, snap_id             NUMBER 
, begin_interval_time DATE
, end_interval_time   DATE
)
TABLESPACE users
/
ALTER TABLE dbamgr.snap_details ADD CONSTRAINT snap_details_pk PRIMARY KEY ( snap_id )
/
CREATE SEQUENCE dbamgr.snap_id_seq START WITH 1 INCREMENT BY 1
/
CREATE TABLE dbamgr.hist_sqlstat
( dbid                  NUMBER
, instance_number       NUMBER
, snap_id               NUMBER
, sql_id                VARCHAR2(13)
, plan_hash_value       NUMBER
, executions_delta      NUMBER
, executions_total      NUMBER
, module                VARCHAR2(500)
, action                VARCHAR2(500)      
, elapsed_time_delta    NUMBER
, elapsed_time_total    NUMBER
, cpu_time_delta        NUMBER
, cpu_time_total        NUMBER
, buffer_gets_delta     NUMBER
, buffer_gets_total     NUMBER
, disk_reads_delta      NUMBER
, disk_reads_total      NUMBER
, direct_writes_delta   NUMBER
, direct_writes_total   NUMBER
, plsexec_time_delta    NUMBER
, plsexec_time_total    NUMBER
) TABLESPACE users
/
ALTER TABLE dbamgr.hist_sqlstat ADD CONSTRAINT hist_sqlstat_pk PRIMARY KEY (snap_id,sql_id)
/
ALTER TABLE dbamgr.hist_sqlstat ADD CONSTRAINT snap_id_fk      FOREIGN KEY (snap_id) REFERENCES dbamgr.snap_details(snap_id)
/
CREATE TABLE dbamgr.hist_sqltext
( 
  sql_id                VARCHAR2(13)
, sql_text              CLOB
, last_executed_date    DATE
) TABLESPACE tbsdata
LOB (sql_text) STORE AS SECUREFILE (cache)
/
ALTER TABLE dbamgr.hist_sqltext ADD CONSTRAINT hist_sqltext_pk PRIMARY KEY (sql_id)
/
--
-- Snap sys stat
--
CREATE TABLE dbamgr.hist_sysmetric_summary(
  metric_id    NUMBER
, metric_name  VARCHAR2(256)
, metric_unit  VARCHAR2(64)
, begin_time   DATE
, maxval       NUMBER
, average      NUMBER
, snap_id      NUMBER
)
TABLESPACE users
/
