select * from v$session_longops where nvl(time_remaining,1) > 0
