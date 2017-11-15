--
-- $Id: //Infrastructure/GitHub/Database/asbo/web/sql/last_login_check.sql#1 $
--
SELECT 
  * 
FROM 
  (
    SELECT 
      s.last_access_date, 
      s.last_accessed_by
    FROM  
      securemgr.web_user_sessions s
    WHERE 
      s.last_access_date > trunc(sysdate) - 1
    ORDER BY 
      s.last_access_date DESC
  ) 
WHERE 
  ROWNUM < 50
