--
-- $Id: //Infrastructure/GitHub/Database/asbo/web/sql/idle_sessions.sql#1 $
--
WITH inactive_users AS
(
  SELECT 
       s.username,
       s.machine,
       s.type,
       COUNT(1) how_many
  FROM
       v$process p
  JOIN v$session s on s.paddr = p.addr
  WHERE
       --
       -- Days inactive
       --
       floor( s.last_call_et/(24*60*60) ) >= :param1
  GROUP BY
       s.machine,
       s.username,
       s.type
  ORDER BY
       COUNT(1) DESC   
     
) 
SELECT
  i.*
, u.profile
, p.limit idle_timeout_days
, p2.limit default_idle_timeout_days
FROM
     inactive_users i
JOIN dba_users    u  ON u.username = i.username
JOIN dba_profiles p  ON p.profile  = u.profile AND p.resource_name  = 'IDLE_TIME'
JOIN dba_profiles p2 ON p2.profile = 'DEFAULT' AND p2.resource_name = 'IDLE_TIME'
