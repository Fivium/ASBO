<?php
#
# $Id: //Infrastructure/GitHub/Database/avo/web/sql_plan_management.php#2 $
#
# SPM
# - T Dale 2011
#
include 'start.php';
$sql_id = u::request_val( 'sql_id' );
#
# Baseline parameters 
#
$sql     = "select * from v\$parameter where name like '%baseline%'";
$cur     = $db_obj->exec_sql( $sql, '' );
$db_obj->html_results( $cur );
#
# Check if this sql is adaptive
#
$sql     = "select sa.is_bind_aware, sa.is_bind_sensitive, sa.sql_plan_baseline baseline from v\$sqlarea sa where sql_id = :sql_id";
$binds   = '';
$binds[] = array( ":sql_id", $sql_id );
$cur     = $db_obj->exec_sql( $sql, $binds );
p('Check v$sqlarea for this sql');
$db_obj->html_results( $cur );

@$baseline = $db_obj->single_rec( $sql, $binds )->BASELINE;
#
# Check selectivity
#
$tables = array('v$sql_cs_histogram','v$sql_cs_statistics','v$sql_cs_selectivity');

foreach ( $tables as $table ){
    p("Check for histograms in $table");
    $sql = "select * from $table where sql_id = :sql_id";
    $cur = $db_obj->exec_sql( $sql, $binds );
    $db_obj->html_results( $cur );
}

p("Check binds captured");
$sql = "select hash_value,child_number,name,position,datatype_string,was_captured,to_char(last_captured,'dd-mon-yyyy hh24:mi:ss') capture_time,value_string from v\$sql_bind_capture where sql_id = :sql_id";
$cur = $db_obj->exec_sql( $sql, $binds );
$db_obj->html_results( $cur );
#
# SQL plan managment details
#
$to_drop_link = "'<a href=show_page.php?db=$db&page=run_sql.php&sql_file=sql_plan_baseline_drop&param1='||sql_handle||'&param2='||plan_name||'&dbms_output=1>To Drop</a>'";
$plan_link    = "'<a href=spm_details.php?db=$db&sql_handle='||sql_handle||'&plan_name='||plan_name||'>'||plan_name||'</a>'";
$date_fmt="'dd-Mon-yyyy hh24:mi'";
p("What baseline plans do we have");
$sql     = "select ";
$sql    .= "  $to_drop_link to_drop,sql_handle,$plan_link plan_name,origin,to_char(created,$date_fmt) created,last_executed,enabled,accepted,optimizer_cost,executions,elapsed_time,cpu_time,buffer_gets,disk_reads,rows_processed ";
$sql    .= "from ";
$sql    .= "  dba_sql_plan_baselines ";
$sql    .= "where ";
$sql    .= "  sql_handle = (select sql_handle from dba_sql_plan_baselines where plan_name = :plan_name )";
$binds   = '';
$binds[] = array( ":plan_name", $baseline );
$cur     = $db_obj->exec_sql( $sql, $binds );
$db_obj->html_results( $cur );
#
# Link for the baseline report
#
p('<pre>');
@$sql_handle = $db_obj->single_rec( $sql, $binds )->SQL_HANDLE;
p("<a href=\"spm_details.php?db=$db&sql_handle=$sql_handle\">All baseline plans for this sql</a>");
p('</pre>');
#
# SQL Tune
#
?>
<pre>
--SQL tuning task
--------------------------

SET PAGESIZE 999
SET LINESIZE 200
SET SERVEROUTPUT ON
SET TRIMSPOOL ON
SPOOL sql_tune_rep_for_<?echo $sql_id;?>.log
DECLARE

  l_task_name   VARCHAR2(30);
  l_report_clob CLOB;

  PROCEDURE show_rep(p_rep CLOB) IS
    c_max_out CONSTANT INT := 32767;
  BEGIN
     IF dbms_lob.getlength(p_rep) > c_max_out THEN
       dbms_output.put_line(SUBSTR(p_rep,1          ,c_max_out));
       dbms_output.put_line(SUBSTR(p_rep,c_max_out+1,c_max_out));
     ELSE
       dbms_output.put_line(p_rep);
     END IF;
  END;  

BEGIN
  l_task_name := dbms_sqltune.create_tuning_task(sql_id => '<?php echo $sql_id;?>');

  dbms_sqltune.execute_tuning_task(task_name => l_task_name);

  l_report_clob := dbms_sqltune.report_tuning_task(l_task_name, 'TEXT', 'ALL');
  
  show_rep(l_report_clob);

END;
/
SPOOL OFF
<?php
#
# Baseline evolve
#
p("--------------------------");
p("Evolve the baseline");
p("--------------------------");
p("set linesize 150");
p("set serveroutput on");
p("set long 10000");
p("DECLARE");
p("    report clob;");
p("BEGIN");
p("    report := DBMS_SPM.EVOLVE_SQL_PLAN_BASELINE(sql_handle => '$sql_handle');");
p("    DBMS_OUTPUT.PUT_LINE(report);");
p("END;");
p("/");
p('</pre>');

include 'end.php';
?>
