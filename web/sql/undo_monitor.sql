--
-- $Id: //Infrastructure/Database/scripts/oav/sql/undo_monitor.sql#2 $
--
set serveroutput on
declare
  l_new_undo_blk_count  int;
  c_wait_sec            constant int := 1;
  l_secs_remaining      number;
  l_undo_blocks_per_sec int;
  l_sessions_sampled    int := 0;
    
  procedure p(p_str varchar2) is begin dbms_output.put_line( p_str ); end;
begin
  p('Looking for sessions with undo usage...');
  --
  -- Sessions using undo
  --
  for l_rec in ( select t.addr, s.username, s.sid, t.used_ublk from v$transaction t, v$session  s where s.saddr = t.ses_addr and t.used_ublk > 0 ) loop
    l_sessions_sampled := l_sessions_sampled + 1;
    --
    -- Current undo use
    --
    p( 'Sid ' || l_rec.sid || ' User ' || l_rec.username || ' undo blocks ' || l_rec.used_ublk );
    dbms_lock.sleep(c_wait_sec);
    --
    -- How much is now used?
    --
    begin
      select t.used_ublk into l_new_undo_blk_count from v$transaction t where t.addr = l_rec.addr;
    exception
      when no_data_found then l_new_undo_blk_count := 0;
    end;
    
    l_undo_blocks_per_sec := ( l_rec.used_ublk - l_new_undo_blk_count ) / c_wait_sec;
    --
    -- Report rate of change
    -- 
    if( l_new_undo_blk_count < l_rec.used_ublk ) then
      l_secs_remaining := round( l_new_undo_blk_count / l_undo_blocks_per_sec ); 
      p( 'session rolling back, undo blocks per sec ' || l_undo_blocks_per_sec || ', secs remaining aprox : ' || l_secs_remaining );
    else
      p( 'session creating undo data at : ' || to_char( l_undo_blocks_per_sec - 2*l_undo_blocks_per_sec ) || ' undo blocks per sec' );
    end if;     
  end loop;

  if l_sessions_sampled = 0 then
    p('No sessions with undo found');
  end if;
end;  
