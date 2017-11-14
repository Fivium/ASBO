<?php
#
# $Id: //Infrastructure/GitHub/Database/asbo/web/show_page.php#9 $
#
$page = $_GET['page'];
$page_name_end_pos = strpos($page,'.php');
$page_name = substr($page,0,$page_name_end_pos);

$allowed_pages = array(
    'active_sessions_details',
    'active_sessions_graph',
    'advisor_output',
    'blocking_tree',
    'db_checks_all_dbs',
    'db_checks',
    'db_monitor',    
    'db_size',
    'dbsync_log',
    'kill_session',
    'locks',
    'obj_details',
    'plan_flipping_queries',
    'rman_log',
    'run_sql',
    'session_drilldown',
    'spm_details',
    'sql_details',
    'sql_display_cursor',
    'sql_hist',
    'sql_monitor',
    'sql_plan_management',
    'standby_details',
    'system_load',
    'table_move',
    'tbs_contents',
    'top_sessions',
    'top_sql',
    'top_sql_report',
    'top_sql_report_all_dbs',
    'unstable_sql'
);
if( in_array($page_name,$allowed_pages) ){
    #
    # Replace the tokens
    #
    $page = str_replace( '['   , '?', $page );
    $page = str_replace( '____', '&', $page );
    $page = str_replace( '----', '=', $page );
    $page = str_replace( ']'   , '' , $page );
    echo "<!-- \nParsed URL : \n";
    $parsed_url = parse_url($page);
    print_r( $parsed_url );
    echo "\nPage wanted : ".$_SERVER['SERVER_NAME']."/oav/$page \n";
    $queryfields = split('[;&]', $parsed_url['query']);
    #
    # Parse in parm
    #
    foreach( $queryfields as $queryfield ){
        $item = explode('=', $queryfield);
        echo "-- Setting http GET varible : ".$item[0]." to ".$item[1]."\n";
        $_GET[$item[0]]=$item[1];
    }
    echo "-->\n";
    $db = $_GET['db'];
    include './'.$page_name.'.php';
}
?>
