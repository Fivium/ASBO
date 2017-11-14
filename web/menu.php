<?php
#
# Database and links drop downs
#
# $Id: //Infrastructure/GitHub/Database/avo/web/menu.php#3 $
#

#
# Get the saved database details
#
include 'conf/db_lookup.php';
$db_lookup = simplexml_load_string($db_look_up_xml);
#
# Top container
#
u::start_tag('div','style="position:relative;background-color:#98bf21;height:30px;width:100%;overflow:hidden;"');
#
# Drop down to select db's
#
u::start_tag('form','action="'.$form_name.'" style="display:inline"');

$values=array();
$labels=array();
foreach($db_lookup as $key0 => $db_obj){
    $values[]=array((string) $db_obj['name'],(string) $db_obj->display_name);
}
#
# Sort
#
foreach ($values as $key => $row) {
    $db_name[$key]      = $row[0];
    $display_name[$key] = strtolower($row[1]);
}
#$display_name_lower = array_map('strtolower', $display_name);
array_multisort($display_name, SORT_ASC, SORT_STRING, $values);
#
# Add choose
#
$choose_str='Choose';
array_unshift($values,array($choose_str,$choose_str));
if( !$db ) $db = $choose_str;

u::select_list('db',$values,$db);
u::end_tag('form');

#
# Reports now
#
u::start_tag('form', 'action="show_page.php" style="display:inline"');
u::tag('input','type="hidden" name="db" value="'.$db.'"');
$values=array();
$values[]=array( 'db_monitor.php'      , 'DB Monitoring'     );
$values[]=array( 'blocking_tree.php'   , 'Blocking Sessions' );
$values[]=array( 'db_checks.php?html=1', 'DB Checks'         );

$yest  = strtotime("yesterday");
$start = date('o_m_d__09',$yest);
$end   = date('o_m_d__17',$yest);
$top_sql_report_url = "top_sql_report.php?start=$start&end=$end&order_by_id=";

$values[]=array( $top_sql_report_url.'1', 'Top SQL Report' );
$values[]=array('db_size.php'           , 'Database Size'  );
$values[]=array('standby_details.php'   , 'Standby Details');
$values[]=array('db_checks_all_dbs.php?html=1','Checks all DB');
$values[]=array('unstable_sql.php?pct_stddev_of_mean=50','Unstable SQL');
$values[]=array('plan_flipping_queries.php','Plan Flipping SQL');
$values[]=array('run_sql.php[sql_file----idle_sessions&param1=1]', 'Sessions over 1 day idle');
$values[]=array('run_sql.php[sql_file----unindexed_fk]','Unindexed foreign keys');
$values[]=array('run_sql.php[sql_file----system_stats]','System Stats');
$values[]=array('run_sql.php[sql_file----logfile_sizes]','Logfile sizes');
$values[]=array('run_sql.php[sql_file----last_login_check]','Last application Login');

if( u::request_val('full',0) ){
   $values[]=array('undo_monitor.php'            ,'Undo Monitor'   );

   $values[]=array( $top_sql_report_url.'2'          , 'AWR by disk reads'   );
   $values[]=array( $top_sql_report_url.'3'          , 'AWR by exec elapsed' );
   $values[]=array('run_sql.php[sql_file----longops]', 'Longops'             );
   $values[]=array('db_sessions.php'                 , 'Sessions'            );

   $base_url=
      "system_load.php?hide=on&snaps_table=$snaps_table&db=$db&sql_mins_hist=2&mins_hist=2&table_styles=1&div_only=1"
   .  "&width=$chart_width&max_width=$chart_max_width"
   .  "&graph_icons=0&chart_icons=0&color_scheme=2&cell_style_param=3";

   $values[]=array( $base_url."&active_sessions=on" , 'Active Sessions' );
   $values[]=array( $base_url."&top_sql=on"         , 'Top SQL'         );
   $values[]=array( $base_url."&top_sessions=on"    , 'Top Sessions'    );
   $values[]=array('rman_progress.php'    ,'RMAN Progress'  );

   $io_url="io_monitor.php?db=$db&wait_secs=10&top_n=10&order_by=";
   $values[]=array( $io_url."2", 'SQL IO Reads'  );
   $values[]=array( $io_url."4", 'SQL IO Writes' );
}


u::select_list('page',$values,$db);
u::end_tag('form');
u::tag('input', 'type="button" style="position:absolute;left:420;top:2;height:27px" value="Stop" id="btn" onclick="toggle_button(this.value);"');
u::end_tag('div');
#
# Need a db to be selected for a page to make any sense
#
if( u::request_val('page') and !$db ){
  $_GET['page'] = '';
}
?>
