CREATE OR REPLACE PACKAGE dbamgr.snap
--
-- $Id: //Infrastructure/GitHub/Database/avo/osse/SNAP.pks#1 $
--
-- T Dale 2012
-- Snapping for Oracle standard
--
AS
  PROCEDURE sql_snap;
  PROCEDURE session_snap;  
END;
/