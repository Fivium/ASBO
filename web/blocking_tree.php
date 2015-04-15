<?php
#
# $Id: //Infrastructure/GitHub/Database/avo/web/blocking_tree.php#2 $
#
include 'start.php';
#
# Locks
#
$link = "'<a href=session_drilldown.php?db=$db&snaps_table=$snaps_table&sid='||";
$sql  = "
select * from (  
  select 
    level l
  , $link sid||'>'||sid||'</a>' sid
  , $link blocking_session||'>'||blocking_session||'</a>' blocking_session
  , SYS_CONNECT_BY_PATH(sid, '/') path   
  from 
    v\$session
  start with blocking_session is null
  connect by prior sid = blocking_session
)
where l > 1
order by path, l
";
$sql = "
with blocking_details
as(
  select 
    substr( b.path, instr( b.path, '/' ) + 1, instr( b.path, '/', 2 ) - 2 ) root_blocker
  , b.blocked_sid
  , b.username
  , machine blocked_machine
  , b.sql_id blocked_sql_id
  , b.path blocking_tree
  from (  
    select 
      level l
    , sid blocked_sid
    , machine
    , username
    , SYS_CONNECT_BY_PATH(sid, '/') path
    , serial#    
    , sql_id
    from 
      v\$session
    start with blocking_session is null
    connect by prior sid = blocking_session
  ) b
  where l >= 1
  order by l desc, path
)
select
  $link sid||'>'||bd.root_blocker||'</a>' root_blocker
, root_blocker_sess.schemaname blocker_user
, root_blocker_sess.machine root_blocker_machine
, root_blocker_sess.program root_blocker_program
, root_blocker_sess.module || ' ' || root_blocker_sess.client_info root_blocker_info
, root_blocker_sess.event   root_blocker_waiting_for
, 'is blocking' is_blocking
, bd.username 
, $link sid||'>'||bd.blocked_sid||'</a>' blocked_sid
, bd.blocking_tree
, bd.blocked_machine 
, (select substr(sql_text,1,100) from v\$sqlarea where sql_id = bd.blocked_sql_id) blocked_sql  
, root_blocker_sess.prev_sql_id root_blocker_prev_sql_id
, (select substr(sql_text,1,100) from v\$sqlarea where sql_id = root_blocker_sess.prev_sql_id) root_blocker_prev_sql
, root_blocker_sess.sql_id      root_blocker_current_sql_id
, (select substr(sql_text,1,100) from v\$sqlarea where sql_id = root_blocker_sess.sql_id) root_blocker_current_sql 
, 'alter system kill session '||chr(39)||root_blocker_sess.sid||','||root_blocker_sess.serial#||chr(39)||';' kill_root_blocker
from 
     blocking_details bd
join v\$session root_blocker_sess on root_blocker_sess.sid = bd.root_blocker  
";
#p($sql);
$cur     = $db_obj->exec_sql( $sql, '' );
$db_obj->html_results( $cur );
include 'end.php';
?>
