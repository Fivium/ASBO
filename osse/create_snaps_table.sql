--
-- $Id: //Infrastructure/GitHub/Database/asbo/osse/create_snaps_table.sql#5 $
--
-- T Dale 2012
-- Save snaps table and job
--



--
-- Active session snaps table
--
CREATE TABLE dbamgr.session_snaps(
  DBID                          NUMBER,
  INSTANCE_NUMBER               NUMBER,
  SAMPLE_TIME                   DATE,
  SESSION_TYPE                  VARCHAR2(64 BYTE),
  SESSION_STATE                 VARCHAR2(64 BYTE),
  WAIT_CLASS                    VARCHAR2(64 BYTE),
  EVENT                         VARCHAR2(64 BYTE),
  SQL_ID                        VARCHAR2(64 BYTE),
  SESSION_ID                    VARCHAR2(64 BYTE),
  USER_ID                       VARCHAR2(64 BYTE),
  USERNAME                      VARCHAR2(128 BYTE),
  PROGRAM                       VARCHAR2(64 BYTE),
  MODULE                        VARCHAR2(64 BYTE),
  BLOCKING_SESSION              NUMBER,
  MACHINE                       VARCHAR2(64 BYTE),
  WAIT_CLASS#                   INTEGER,
  EVENT#                        INTEGER,
  WHAT_SESSION_IS_DOING_CLASS   VARCHAR2(64 BYTE),
  WHAT_SESSION_IS_DOING_CLASS#  INTEGER,
  BLOCKER                       VARCHAR2(64 BYTE),
  CLIENT_INFO                   VARCHAR2(100 BYTE),
  LOGON_TIME                    DATE,
  PREV_SQL_ID                   VARCHAR2(64 BYTE),
  PLSQL_ENTRY_OBJECT_ID         VARCHAR2(64 BYTE),
  PLSQL_ENTRY_SUBPROGRAM_ID     VARCHAR2(64 BYTE),
  PLSQL_OBJECT_ID               VARCHAR2(64 BYTE),
  PLSQL_SUBPROGRAM_ID           VARCHAR2(64 BYTE),
  TYPE                          VARCHAR2(64 BYTE),
  SERIAL#                       NUMBER,
  P1                            NUMBER,
  P1TEXT                        VARCHAR2(64 BYTE),
  P1RAW                         RAW(8),
  P2                            NUMBER,
  P2TEXT                        VARCHAR2(64 BYTE),
  P2RAW                         RAW(8),
  P3                            NUMBER,
  P3TEXT                        VARCHAR2(64 BYTE),
  P3RAW                         RAW(8)	
)
TABLESPACE users
/
CREATE INDEX dbamgr.session_snaps_idx1 ON dbamgr.session_snaps(sample_time) TABLESPACE users
/
CREATE TABLE dbamgr.session_snaps_hist TABLESPACE users AS SELECT * FROM dbamgr.session_snaps 
/
CREATE INDEX dbamgr.session_snaps_idx3 ON session_snaps( sample_time, wait_class,session_state, blocking_session,session_id, sql_id) 
/
