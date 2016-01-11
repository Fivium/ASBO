--
-- $Id: //Infrastructure/GitHub/Database/asbo/osse/create_active_session_details_view.sql#3 $
--
-- T Dale 2012
--
-- Snaping view for Oracle standard edition



--
-- Needs to be run as sys
--

CREATE OR REPLACE VIEW sys.active_session_details
AS
SELECT
    s.ksuseflg session_type
,   s.ksuseopc event
,   s.ksusesqi sql_id
,   s.indx     session_id
,   s.ksuudlui user_id
,   s.ksusepnm program
,   s.ksuseapp module
,   DECODE(s.ksusetim, 0,'WAITING','ON CPU')  session_state
,   CASE s.ksusetim WHEN 0 THEN ( SELECT wait_class FROM v$event_name WHERE event# = s.ksuseopc ) 
    ELSE NULL END wait_class 
FROM
    x$ksuse s
WHERE
    s.indx != ( SELECT DISTINCT sid FROM v$mystat WHERE ROWNUM < 2 ) -- not me
AND bitand(s.ksspaflg,1)!=0
AND bitand(s.ksuseflg,1)!=0
AND
(
  ( s.ksusetim != 0 AND bitand(s.ksuseidl,11)=1 ) -- on cpu and active
  OR
  s.ksuseopc NOT IN (  select event# from v$event_name where wait_class='Idle' ) -- not idle
);
