--
-- Use if you need to stop or start the snapper job
--
SET SERVEROUTPUT ON
DECLARE

  l_disable BOOLEAN := FALSE;  
  PROCEDURE p(p_str VARCHAR2) IS BEGIN DBMS_OUTPUT.PUT_LINE(p_str);END;
  
BEGIN
  FOR r_job IN (SELECT job,what FROM dba_jobs WHERE LOWER(what) LIKE '%dbamgr.snap%') LOOP
  
    IF l_disable THEN
      --
      -- Disable job
      --
      p('Disable job : ' || r_job.job || ' - ' || r_job.what);
      DBMS_JOB.BROKEN(r_job.job,TRUE);
      p('Job disabled');
      --
      -- If running, Kill session
      -- 
      FOR r_sess IN (SELECT s.sid,s.serial# FROM v$session s JOIN dba_jobs_running jr ON s.sid=jr.sid WHERE jr.job = r_job.job) LOOP
        p('This job is running, kill session : '|| r_sess.sid);  
        EXECUTE IMMEDIATE 'ALTER SYSTEM KILL SESSION '||CHR(39)||r_sess.sid||','||r_sess.serial#|| CHR(39);
        p('Job killed'); 
      END LOOP;
      
    ELSE
      --
      -- Enable Job
      --  
      p('Enable job : ' || r_job.job || ' - ' || r_job.what);
      DBMS_JOB.BROKEN(r_job.job,FALSE);
      p('Job enabled');
    END IF;
    p('-'); 
    COMMIT;
  END LOOP;
END;
/
