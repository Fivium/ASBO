<?php
#
# Database and links drop downs
#
# $Id: //Infrastructure/GitHub/Database/avo/web/menu.php#3 $
#

#
# Get the saved database details
#
$db_lookup = simplexml_load_file('conf/db_lookup.xml');
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
$values[]=array('Choose','Choose');
foreach($db_lookup as $key0 => $db_obj){
    $values[]=array($db_obj['name'],$db_obj->display_name);
}
u::select_list('db',$values,$db);
u::end_tag('form');

#
# Reports now
#
u::start_tag('form', 'action="show_page.php" style="display:inline"');
u::tag('input','type="hidden" name="db" value="'.$db.'"');
$values=array();
$values[]=array( 'db_monitor.php'   , 'DB Monitoring'     );
$values[]=array( 'blocking_tree.php', 'Blocking Sessions' );
$values[]=array( 'db_checks.php'    , 'DB Checks'         );

$yest    = strtotime("yesterday");
$start   = date('o_m_d__09',$yest);
$end     = date('o_m_d__17',$yest);
$awr_url = "awr.php?start=$start&end=$end&order_by_id=";

$values[]=array( $awr_url.'1'                                            ,'Top SQL Report');

if( u::request_val('full',0) ){
   $values[]=array('standby_details.php'  ,'Standby Details');
   $values[]=array('db_checks_all_dbs.php','Checks all DB'  );
   $values[]=array('db_size.php'          ,'Database Size'  );
   $values[]=array('undo_monitor.php'     ,'Undo Monitor'   );

   $values[]=array( $awr_url.'2'                                            ,'AWR by disk reads'   );
   $values[]=array( $awr_url.'3'                                            ,'AWR by exec elapsed' );
   $values[]=array('run_sql.php[sql_file----longops]'                       ,'Longops'             );
   $values[]=array('db_sessions.php'                                        ,'Sessions'            );

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
