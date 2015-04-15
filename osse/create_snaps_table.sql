--
-- $Id: //Infrastructure/GitHub/Database/avo/osse/create_snaps_table.sql#1 $
--
-- T Dale 2012
-- Save snaps table and job
--

--
-- Active session snaps table
--
DROP TABLE dbamgr.session_snaps
/
CREATE TABLE dbamgr.session_snaps
( dbid             NUMBER
, instance_number  NUMBER
, sample_time      DATE
, session_type     VARCHAR2(64 BYTE)
, session_state    VARCHAR2(64 BYTE)
, wait_class       VARCHAR2(64 BYTE)
, event            VARCHAR2(64 BYTE)
, sql_id           VARCHAR2(64 BYTE)
, session_id       VARCHAR2(64 BYTE)
, user_id          VARCHAR2(64 BYTE)
, program          VARCHAR2(64 BYTE)
, module           VARCHAR2(64 BYTE)
, blocking_session NUMBER
, machine          VARCHAR2(64 BYTE)
)
/
CREATE INDEX dbamgr.session_snaps_idx1 ON dbamgr.session_snaps(sample_time)
/
CREATE TABLE dbamgr.session_snaps_hist AS SELECT * FROM dbamgr.session_snaps
/
