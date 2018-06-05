CREATE OR REPLACE PACKAGE BODY DBAMGR.snap
--
-- $Id: //Infrastructure/GitHub/Database/asbo/osse/SNAP.pkb#7 $
--
-- T Dale 2012
-- Snapping for Oracle standard
--
AS
  c_instance_number            CONSTANT INT := 0;
  c_snap_history_days_to_keep  CONSTANT INT := 8;
  c_snap_days_to_keep          CONSTANT INT := 3;

  PROCEDURE p(p_str VARCHAR2) IS BEGIN DBMS_OUTPUT.PUT_LINE( p_str ); END;

  PROCEDURE session_snap
  IS
    l_samples INT    := 0;
    l_start   NUMBER := dbms_utility.get_time;
    l_dbid    NUMBER;
  BEGIN
    SELECT
      dbid
    INTO
      l_dbid
    FROM
      v$database;
    --
    -- Sample...
    --
    FOR i IN 1..360 LOOP
      FOR i IN 1..10 LOOP
        INSERT INTO dbamgr.session_snaps
        (
          SELECT
            l_dbid
          , c_instance_number
          , sysdate sample_time
          , NULL
          , session_state
          , wait_class
          , event
          , sql_id
          , session_id
          , user_id
          , NULL
          , program
          , module
          , blocking_session
          , NULL
          , NULL
          , NULL
          , NULL
          , NULL
          , NULL
          , NULL
          , NULL
          , NULL
          , NULL
          , NULL
          , NULL
          , NULL
          , NULL
          , NULL
          , NULL
          , NULL
          , NULL
          , NULL
          , NULL
          , NULL
          , NULL
          , NULL
          , NULL
          FROM
            sys.active_session_details
        );
        l_samples := l_samples + 1;
        --
        -- Wait a sec
        --
        COMMIT;
        DBMS_LOCK.SLEEP(1);
      END LOOP;
      --
      -- Save new recs to the history table
      --
      MERGE INTO
        dbamgr.session_snaps_hist sh
      USING
        (SELECT * FROM dbamgr.session_snaps WHERE sample_time > SYSDATE - 10/(24*60)  ) ss ON (sh.sample_time=ss.sample_time AND sh.session_id=ss.session_id)
      WHEN NOT MATCHED THEN
        INSERT (
          sh.sample_time
        , sh.session_type
        , sh.session_state
        , sh.wait_class
        , sh.event
        , sh.sql_id
        , sh.session_id
        , sh.user_id
        , sh.program
        , sh.module
        , sh.wait_class#
        , sh.what_session_is_doing_class
        , sh.what_session_is_doing_class#
        , sh.blocker
        , sh.client_info
        , sh.logon_time
        , sh.prev_sql_id
        , sh.plsql_entry_object_id
        , sh.plsql_entry_subprogram_id
        , sh.plsql_object_id
        , sh.plsql_subprogram_id
        , sh.type
        , sh.serial#
        , sh.p1
        , sh.p1text
        , sh.p1raw
        , sh.p2
        , sh.p2text
        , sh.p2raw
        , sh.p3
        , sh.p3text
        , sh.p3raw
        )
        VALUES (
          ss.sample_time
        , ss.session_type
        , ss.session_state
        , ss.wait_class
        , ss.event
        , ss.sql_id
        , ss.session_id
        , ss.user_id
        , ss.program
        , ss.module
        , ss.wait_class#
        , ss.what_session_is_doing_class
        , ss.what_session_is_doing_class#
        , ss.blocker
        , ss.client_info
        , ss.logon_time
        , ss.prev_sql_id
        , ss.plsql_entry_object_id
        , ss.plsql_entry_subprogram_id
        , ss.plsql_object_id
        , ss.plsql_subprogram_id
        , ss.type
        , ss.serial#
        , ss.p1
        , ss.p1text
        , ss.p1raw
        , ss.p2
        , ss.p2text
        , ss.p2raw
        , ss.p3
        , ss.p3text
        , ss.p3raw
        );

    END LOOP;
    --
    -- Remove old snaps
    --
    DELETE FROM dbamgr.SESSION_SNAPS      WHERE sample_time < SYSDATE - c_snap_days_to_keep;
    DELETE FROM dbamgr.SESSION_SNAPS_HIST WHERE sample_time < SYSDATE - c_snap_history_days_to_keep;
    COMMIT;

  END;

  PROCEDURE snap
  --
  -- Snap both the sql stats and os stat counters
  --
  -- Place holder to tidy up code
  --
  IS
  BEGIN
    NULL;
  END;

  PROCEDURE sql_snap
  AS
    c_this_snap_end_time   CONSTANT DATE         := SYSDATE;
    c_date_time_fmt        CONSTANT VARCHAR2(17) := 'DD-Mon HH24:MI:SS';
    c_days_to_keep         CONSTANT INT          := 4;
    l_this_snap_begin_time          DATE;
    l_last_snap_end_time            DATE;
    l_last_snap_id                  dbamgr.snap_details.snap_id%TYPE;
    l_this_snap_id                  dbamgr.snap_details.snap_id%TYPE;
    l_dbid                          NUMBER;

  BEGIN
    SELECT dbid INTO l_dbid FROM v$database;
    --
    -- Current snap details
    --
    BEGIN
      SELECT
        snap_id
      , begin_interval_time
      , end_interval_time
      INTO
        l_last_snap_id
      , l_this_snap_begin_time
      , l_last_snap_end_time
      FROM
        dbamgr.snap_details
      WHERE
        snap_id = ( SELECT MAX( snap_id ) FROM dbamgr.snap_details );
    EXCEPTION
       WHEN NO_DATA_FOUND THEN
         p('No snaps found');
         l_last_snap_end_time   := NULL;
         l_this_snap_begin_time := NULL;
         l_last_snap_id         := NULL;
    END;
    --
    -- Is there a current rec for this hour?
    --
    IF TRUNC( l_last_snap_end_time, 'HH24' ) = TRUNC( c_this_snap_end_time, 'HH24' ) THEN
      p( 'Update current snap' );
      --
      -- We will update data for this current snap
      --
      l_this_snap_id := l_last_snap_id;

      UPDATE
        dbamgr.snap_details
      SET
        end_interval_time = c_this_snap_end_time
      WHERE
        snap_id = l_this_snap_id;
      --
      -- Remove records for this hour
      -- we will insert new records
      --
      DELETE FROM dbamgr.hist_sqlstat WHERE snap_id = l_this_snap_id;
      p('Removed records : ' || SQL%ROWCOUNT );
    ELSE
      p( 'New snap' );
      INSERT INTO
        dbamgr.snap_details
        (
          dbid
        , instance_number
        , snap_id
        , begin_interval_time
        , end_interval_time
        )
      VALUES
        (
          l_dbid
        , c_instance_number
        , dbamgr.snap_id_seq.NEXTVAL
        , l_last_snap_end_time
        , c_this_snap_end_time
        )
      RETURNING snap_id INTO l_this_snap_id;

      l_this_snap_begin_time := l_last_snap_end_time;
    END IF;


    p( 'Save sql stats for snap : ' || l_this_snap_id );
    p( 'Getting last recorded values for deltas from snap : ' || l_last_snap_id );
    p( 'Current period ' || TO_CHAR( l_this_snap_begin_time, c_date_time_fmt ) || ' - ' || TO_CHAR( c_this_snap_end_time, c_date_time_fmt ) );
    --
    -- Record new sql stats
    --
    INSERT INTO dbamgr.hist_sqlstat
    (
      dbid
    , instance_number
    , snap_id
    , sql_id
    , module
    , action
    , plan_hash_value
    , executions_delta
    , executions_total
    , elapsed_time_delta
    , elapsed_time_total
    , cpu_time_delta
    , cpu_time_total
    , buffer_gets_delta
    , buffer_gets_total
    , disk_reads_delta
    , disk_reads_total
    , direct_writes_delta
    , direct_writes_total
    , plsexec_time_delta
    , plsexec_time_total
    )
    WITH
      sql_details
    AS
    (
      SELECT
        --
        -- Current sql details
        --
        sa.sql_id
      , sa.module
      , sa.action
      , sa.plan_hash_value
      , sa.executions
      , sa.elapsed_time
      , sa.cpu_time
      , sa.buffer_gets
      , sa.disk_reads
      , sa.direct_writes
      , sa.plsql_exec_time plsexec_time
      FROM
        v$sqlarea sa
      WHERE
        sa.last_active_time > SYSDATE - 1
    )
    , last_snap_sql_details
    AS
    (
      SELECT
        --
        -- Need last totals to calc new deltas
        --
        hs.sql_id
      , hs.module
      , hs.action
      , hs.plan_hash_value
      , hs.executions_total
      , hs.elapsed_time_total
      , hs.cpu_time_total
      , hs.buffer_gets_total
      , hs.disk_reads_total
      , hs.direct_writes_total
      , hs.direct_writes_delta
      , hs.plsexec_time_total
      , hs.plsexec_time_delta
      FROM
        dbamgr.hist_sqlstat hs
      WHERE
        hs.snap_id = ( SELECT MAX(snap_id) FROM dbamgr.snap_details WHERE snap_id < ( SELECT MAX(snap_id) FROM  dbamgr.snap_details) )
    )
    SELECT
      l_dbid
    , c_instance_number
    , l_this_snap_id
    , sd.sql_id
    , sd.module
    , sd.action
    , sd.plan_hash_value
    , sd.executions    - NVL( lsd.executions_total   , 0 ) executions_delta
    , sd.executions                                        executions_total
    , sd.elapsed_time  - NVL( lsd.elapsed_time_total , 0 ) elapsed_time_delta
    , sd.elapsed_time                                      elapsed_time_total
    , sd.cpu_time      - NVL( lsd.cpu_time_total     , 0 ) cpu_time_delta
    , sd.cpu_time                                          cpu_time_total
    , sd.buffer_gets   - NVL( lsd.buffer_gets_total  , 0 ) buffer_gets_delta
    , sd.buffer_gets                                       buffer_gets_total
    , sd.disk_reads    - NVL( lsd.disk_reads_total   , 0 ) disk_reads_delta
    , sd.disk_reads                                        disk_reads_total
    , sd.direct_writes - NVL( lsd.direct_writes_total, 0 ) direct_writes_delta
    , sd.direct_writes                                     direct_writes_total
    , sd.plsexec_time  - NVL( lsd.plsexec_time_total , 0 ) plsexec_time_delta
    , sd.plsexec_time                                      plsexec_time_total
    FROM
      sql_details sd
    LEFT OUTER JOIN
      last_snap_sql_details lsd ON sd.sql_id = lsd.sql_id
    ORDER BY
      sd.sql_id;

    p( 'New records : ' || SQL%ROWCOUNT );

    --
    -- Delete old records
    --
    DELETE FROM dbamgr.hist_sqlstat WHERE snap_id < ( SELECT MAX( snap_id ) FROM dbamgr.snap_details WHERE end_interval_time < SYSDATE - c_days_to_keep );

    p( 'Old sql stats deleted : ' || SQL%ROWCOUNT );

    --
    -- Delete old sql hist
    --
    DELETE FROM dbamgr.hist_sqltext WHERE last_executed_date < SYSDATE - c_days_to_keep;
    --
    -- Merge in new sql text
    --
    MERGE INTO
      dbamgr.hist_sqltext hs
    USING
      (SELECT DISTINCT sql_id, sql_text, last_active_time FROM v$sqlarea) sa ON (sa.sql_id=hs.sql_id)
    WHEN     MATCHED THEN UPDATE SET hs.last_executed_date = sa.last_active_time
    WHEN NOT MATCHED THEN INSERT
      (
        sql_id
      , sql_text
      , last_executed_date
      )
    VALUES
    (
      sa.sql_id
    , sa.sql_text
    , sa.last_active_time
    );

    COMMIT;
  END;

END;
/
