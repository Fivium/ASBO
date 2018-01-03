<?php
#
# $Id: //Infrastructure/GitHub/Database/asbo/web/top_sql_report_all_dbs.php#2 $
#
include 'start.php';

if( u::request_val('html') ){
    u::p('<!doctype html>');
    u::p('<html>');
    u::p('<head>');
    u::p('</head>');
    u::p('<body>');
    $break='<br>';
}else{
    $break="\n";
}
echo $break . 'Report time : ' . date(DATE_RFC2822) . $break;
#
# Get all connection details
#
include 'conf/db_lookup.php';
$db_lookup = simplexml_load_string($db_look_up_xml);
$inc_str   = u::request_val('inc_str');
$exc_str   = u::request_val('exc_str');

foreach($db_lookup as $key0 => $db_detail){
    $db = (string) $db_detail['name'];
    #
    # Run Checks?
    #
    $include_it = 1;
    if( $inc_str ){
        $search_pos = stripos( $db, $inc_str );
        if( $search_pos === 0 or $search_pos > 0 ){
            $include_it = 1;
        }else{
            $include_it = 0;
       }
    }
    #
    # Now excludes
    #
    $exclude_it = 0;
    if( $exc_str ){
        $search_pos = stripos( $db, $exc_str );
        if( $search_pos === 0 or $search_pos > 0 ){
            $exclude_it = 1;
        }
    }

    if( $include_it and !$exclude_it ){

        u::flush();
        $db_obj = new db($db_detail->user, $db_detail->pw, $db_detail->conn_str, 1);

        $just_report = 1;
        if( $db_obj->connection_error ){
            u::p('<span style="background-color:red;">CRITICAL</span>');
        }else{
            u::p("\n__START__$db\n");
            #
            # Use our own history tables?
            #
            if ( $db_detail->use_local_awr_tables ){
                $use_our_own_history_tables = TRUE;
            }else{
                $use_our_own_history_tables = FALSE;
            }            
            include 'top_sql_report.php';
        }

    }
}

if( @$_GET['html'] ){
    u::p('</body>');
    u::p('</html>');
}
?>
