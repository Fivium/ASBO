-- $Id: //Infrastructure/GitHub/Database/avo/osse/snapping_jobs.sql#1 $
--
-- Job to snap session info
--
DECLARE
  l_job_id NUMBER;
  user_name varchar2(30);
BEGIN
  
    SYS.DBMS_JOB.SUBMIT
    ( job       => l_job_id 
     ,what      => 'dbamgr.snap.session_snap;'
     ,next_date => SYSDATE
     ,interval  => 'SYSDATE + 1/24' 
     ,no_parse  => FALSE
    );

  COMMIT;

END;
/
DECLARE
  X NUMBER;
BEGIN
    SYS.DBMS_JOB.SUBMIT
    ( job       => X 
     ,what      => 'dbamgr.snap.sql_snap;'
     ,next_date => sysdate
     ,interval  => 'TO_DATE(SUBSTR(TO_CHAR(SYSDATE,''YYYY-DD-MM-HH24:MI''),1,15) || ''0'',''YYYY-DD-MM-HH24:MI'') + 10/(24*60)'
     ,no_parse  => FALSE
    );
  COMMIT;
END;
/
