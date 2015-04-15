<?php
#
# $Id: //Infrastructure/GitHub/Database/avo/web/system_load.php#2 $
#
# T Dale 2010-12-21
#
# DB server load
#
$choose_db = 1;
include 'start.php';
$chart_top      = 30;
$chart_height   = 300;
$chart_left_pos = 0;
$table_rows     = 10;
$session_type   = u::request_val( 'session_type'    ,'' );
$div_styles_top_10s =
  "border-right:1px solid #98bf21;"
. "position:relative;"
. "left:{$chart_left_pos};"
. "min-width:{$chart_width}px;"
. "max-width:{$chart_max_width}px;"
. "overflow:hidden;";

$hide_pannel    = u::request_val( 'hide', 0 );

if( !$hide_pannel ){$chart_top = $chart_top + 400;}

function busy_percent($busy_count,$sql_mins_hist){
    #
    # There is a sample every second
    # i.e. busy count = secs busy
    #
    return number_format( ( $busy_count/( $sql_mins_hist*60 ) )*100, 2 );
}

function drilldown_links( $sid_str, $db, $num_cpus = '', $snaps_table ){

    $sid_list   = explode( ',', $sid_str );
    $links_html = '';
    $add_sep    = 0;
    $sid_count  = 0;

    $style_str = '';
    foreach( $sid_list as $sid ){
        $sid_count++;
        if ( $sid_count > $num_cpus and $num_cpus > 0 ) $style_str = 'style="color:red"';
        if ( $add_sep ) $links_html .= '-';
        $add_sep = 1;
        $links_html .= "<a $style_str href=\"session_drilldown.php?db=$db&amp;sid=$sid&amp;all_secs=1&amp;snaps_table=$snaps_table\">$sid</a>";
    }
    return $links_html;
}

function highlight_js_str( $what ){
    #
    # Stop/start timers and highlight
    #
    #$mouse_over = "onmouseover=\"toggle_button('Stop');highlight('$what');\"";
    #$mouse_out  = "onmouseout=\"toggle_button('Start');\"";
    #
    # Stop start didnt work great
    #
    $mouse_over = "onmouseover=\"highlight('$what');\"";
    $mouse_out  = '';
    return( $mouse_over . " " . $mouse_out );
}

#
# Refresh options
#
$sql_mins_hist = u::request_val('sql_mins_hist',1);
$mins_hist     = u::request_val('mins_hist'    ,1);

$start = date('o_m_d__07');
$end   = date('o_m_d__H');
#
# OS load
#
if( u::request_val('os_stats') ) u::inc( 'os_stats.php' );
#
# Active session details now
#
if( u::request_val('active_sessions') ) u::inc( 'active_sessions_graph.php' );
#
# Top sql now
#
if( u::request_val('top_sql') ) u::inc('top_sql.php') ;
#
# Top sessions
#
if( u::request_val('top_sessions') ) u::inc( 'top_sessions.php' );

if( !$div_only ) u::inc( 'end.php' );

?>
