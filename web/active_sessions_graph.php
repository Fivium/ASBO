<?php
#
# $Id: //Infrastructure/GitHub/Database/avo/web/active_sessions_graph.php#2 $
#

#
# Show the active sessions as big bar chart
#
define('C_MAX_CELL_HEIGHT',13);
$sql = <<<__SQL_END
WITH
 samples AS
(
  SELECT
    --
    -- Samples we have
    -- 
    DISTINCT sample_time
  FROM
    $snaps_table
  WHERE
    sample_time > SYSDATE - :mins_hist/(24*60)
  UNION ALL
    --
    -- Add this second, so the results looks uptodate
    --
    SELECT SYSDATE - :mins_hist/(24*60) FROM dual
  ORDER BY sample_time DESC
)
, sess_data AS
(
  SELECT 
    sample_time
  , a.session_type
  , session_state
  , wait_class
  , event
  , a.sql_id
  , session_id
  , session_id || '_' || a.sql_id || '_' sid_sqlid
  , blocking_session
  FROM
    $snaps_table a
  WHERE
    sample_time > SYSDATE - :mins_hist/(24*60)
)
SELECT
  --
  -- Agg up all the session that are running for each sample time
  --
  TO_CHAR(s.sample_time,'hh24:mi:ss') s_time
, TO_CHAR((SELECT listagg(ash.sid_sqlid,',') WITHIN GROUP (ORDER BY ash.session_id) FROM sess_data ash WHERE ash.sample_time = s.sample_time AND ash.session_state = 'ON CPU'    )) sids_on_cpu
, TO_CHAR((SELECT listagg(ash.sid_sqlid,',') WITHIN GROUP (ORDER BY ash.session_id) FROM sess_data ash WHERE ash.sample_time = s.sample_time AND ash.wait_class    = 'User I/O'  )) sids_user_io
, TO_CHAR((SELECT listagg(ash.sid_sqlid,',') WITHIN GROUP (ORDER BY ash.session_id) FROM sess_data ash WHERE ash.sample_time = s.sample_time AND ash.wait_class    = 'System I/O')) sids_system_io
, TO_CHAR((SELECT listagg(ash.sid_sqlid,',') WITHIN GROUP (ORDER BY ash.session_id) FROM sess_data ash WHERE ash.sample_time = s.sample_time AND NVL(ash.blocking_session,0) > 0 )) sids_blocked
, TO_CHAR((SELECT listagg(ash.sid_sqlid,',') WITHIN GROUP (ORDER BY ash.session_id) FROM sess_data ash WHERE ash.sample_time = s.sample_time AND ash.session_state != 'ON CPU' AND ash.wait_class NOT IN ( 'User I/O','System I/O' ) AND ash.blocking_session IS NULL )) others
, TO_CHAR((SELECT listagg(ash.sql_id,',')    WITHIN GROUP (ORDER BY ash.session_id) FROM sess_data ash WHERE ash.sample_time = s.sample_time AND sql_id is NOT NULL              )) sql_ids
FROM  samples s
ORDER BY 
  sample_time DESC
__SQL_END;

$binds   = '';
$binds[] = array( ":mins_hist", $mins_hist );
$cur = $db_obj->exec_sql( $sql, $binds );

$last_snap_time = $oracle_sysdate;
#
# Cell styles
#
$cell_width   = 20;
$cell_height  = 20;
$secs_hist    = $mins_hist * 60;
$cells_width  = $cell_width * $secs_hist;
$chart_icons  = u::request_val('chart_icons',0);

$graph_width  = 200;

$img_tail     = "{$cell_width}_{$cell_height}.png";

if( $graph_width > $chart_width ){$chart_width = $graph_width;}

$userio_bk_style = style_color(0,$colors);
$sys_io_bk_style = style_color(1,$colors);
$on_cpu_bk_style = style_color(2,$colors);
$locked_bk_style = style_color(3,$colors);
$others_bk_style = style_color(4,$colors);
#
# Display icons?
#
if( $chart_icons ){
   $bkimg_style = "background-image:url('img/";
   $userio_bk_style .= $bkimg_style."disk_$img_tail')";
   $sys_io_bk_style .= $bkimg_style."disk_$img_tail')";
   $on_cpu_bk_style .= $bkimg_style."cpu_$img_tail')";
   $locked_bk_style .= $bkimg_style."blocked_$img_tail')";
   $others_bk_style .= $bkimg_style."other_$img_tail')";
}
#
# What cell style?
#
$max_cpu_line_adj = 1;
$cell_style = u::request_val('cell_style',2);
if( $cell_style == 1 ){
   ##
   ## Solid
   ##
   $cell_display_width  = $cell_width;
   $cell_display_height = $cell_height;
   $border_style        = '';

}elseif( $cell_style == 4 ){

   $cell_display_width  = $cell_width-3;
   $cell_display_height = $cell_height-3;
   $border_style        = '';
   $border = 'border:1px solid ';
   $userio_bk_style .= $border.'black;';
   $sys_io_bk_style .= $border.'blue;';
   $on_cpu_bk_style .= $border.'green;';
   $locked_bk_style .= $border.'maroon;';
   $others_bk_style .= $border.'purple;';
   $max_cpu_line_adj = 1;

}else{

   if( $cell_style == 3 ) $rad = 2; elseif( $cell_style == 2 ) $rad = 0; else $rad = 10;

   $cell_display_width  = $cell_width-1;
   $cell_display_height = $cell_height-1;
   $border_style        = 'border-radius:'.$rad.'px;';

   if( $cell_style == 4 ){
       $border = 'border:1px solid ';
       $userio_bk_style .= $border.'blue;';
       $sys_io_bk_style .= $border.'blue;';
   }
}

#
# CSS for active sessions
#
p('<style scoped>');
p("    .graph                {overflow:auto;position:relative;min-width:{$chart_width}px;max-width:{$chart_max_width}px;height:{$chart_height}px;border:1px solid #98bf21;}");

$display_max_cpu = u::request_val('display_max_cpu',1);
if( $display_max_cpu ){
   #
   # How may physical cpu cores?
   #
   $cores = $db_obj->cpu_cores();
   #
   # Maxcpu indicator on the graph
   #
   p("    .graph .max_cpu       {border-top:1px solid red;width:".$cells_width.
     "px;height:".($cores*$cell_height-$max_cpu_line_adj).
     "px;bottom:20px;border-radius:0px;font: 10px bold arial, sans-serif;color:red}");
   p("    .graph .max_cpu:hover {background-color:white}");
}
p("    .graph div            {position:absolute;font-size:xx-small;".$border_style."width:".$cell_display_width."px;height:".$cell_display_height."px}");
p("    .graph div:hover      {background-color: silver;cursor:pointer;}");
p("    .graph .time:hover    {background-color: white;cursor:default;}");
p("    .userio               { $userio_bk_style }");
p("    .sys_io               { $sys_io_bk_style }");
p("    .on_cpu               { $on_cpu_bk_style }");
p("    .locked               { $locked_bk_style }");
p("    .others               { $others_bk_style }");
p("    .legend               {position:relative;width:{$cell_width}px;height:{$cell_height}px}"); 
p('</style>');

$col = 0;

function cells( $sid_str, $db, $current_col, &$current_row, $class, $snaps_table, $sample_time ){
    #
    # Anything in the list?
    #
    if( strlen( $sid_str ) > 0 ){
        $sid_list = explode( ',', $sid_str );
        $active_sessions = count( $sid_list );
        $display_cell    = 1;
        #
        # put each sid in div
        #
        foreach( $sid_list as $sid ){
            #
            # Session detail drilldown link
            #
            $just_sid = current( explode( '_', $sid ) );
            $link_str = "onclick=\"window.open('session_drilldown.php?db=$db&amp;sid={$just_sid}&amp;all_secs=1&amp;snaps_table=$snaps_table');return false;\"";
            #
            # Detail popup
            #
            $div_id       = "{$sample_time}_{$just_sid}";
            $show_hide    = "showhide('{$div_id}'";
            $show_div_str = "onmouseover=\"{$show_hide},1);\" onmouseout=\"{$show_hide},0);\" ";
            #
            # Display?
            #
            if( $display_cell ){
                #
                # Where does this go?
                #
                $bottom=($current_row-1)*20;
                $left  =($current_col-1)*20;
                $position_str = 'left:'.$left.'px;bottom:'.$bottom.'px;';                
                $id_tail      = str_replace(':','_',$sample_time);
                $div_format   = 'id="graph_'.$sid.'_'.$id_tail.'" style="'.$position_str.'" class="'.$class.'" '.$show_div_str.' '.$link_str;
                #
                # too many?
                #
                if( $current_row > C_MAX_CELL_HEIGHT and u::request_val('trim',1) ){
                    #
                    # Last cell for this class
                    #
                    $display_cell = 0;
                    #
                    # Just display the session count
                    #
                    u::tagged_item('div',$active_sessions, $div_format, 1);                    
                }else{
                    #
                    # Add cell to page
                    #
                    u::tagged_item('div', '', $div_format,1);
                }
                $current_row++;
            }
        }
    }
}
#
# Main graph boundry div
#
u::start_tag('div', "onscroll=\"toggle_button('Stop');\" class=\"graph\" style=\"top:$chart_top;left:$chart_left_pos\" id=\"active_sessions_graph\"");
if( $display_max_cpu ){
   #
   # Max CPU info
   #
   u::tagged_item('div','Max CPU','class="max_cpu"',1);
}
#
# Move pos for next chart
#
$chart_top = $chart_top + $chart_height + 10;

$graph_heights    = array();
$max_height_cells = 0;

while ( ( $rec = oci_fetch_object( $cur ) ) ){
    $col++;
    u::fill_in_missing_snaps( $last_snap_time, $rec->S_TIME, $col );

    $times = explode( ':', $rec->S_TIME );
    $secs  =  $times[0]*60*60   + $times[1]*60   + $times[2];
    #
    # Date time in the graph, if needed
    #
    u::time_marker( $secs, $col );

    $last_snap_time = $rec->S_TIME;
    #
    # Time markers take up first row
    #
    $current_row = 2;

    cells( $rec->SIDS_ON_CPU   , $db, $col, $current_row, 'on_cpu', $snaps_table, $last_snap_time );
    cells( $rec->SIDS_USER_IO  , $db, $col, $current_row, 'userio', $snaps_table, $last_snap_time );
    cells( $rec->SIDS_SYSTEM_IO, $db, $col, $current_row, 'sys_io', $snaps_table, $last_snap_time );
    cells( $rec->SIDS_BLOCKED  , $db, $col, $current_row, 'locked', $snaps_table, $last_snap_time );
    cells( $rec->OTHERS        , $db, $col, $current_row, 'others', $snaps_table, $last_snap_time );
    #
    # Graph topper cell
    # - need prev, current and next graph heights
    # - so we will do the top of the last col
    #
    $graph_heights[$col] = $current_row;
    #
    # Is this the peak of the graph
    #
    if( $current_row > $max_height_cells ){
        $max_height_cells = $current_row;
    }
}
u::end_tag('div');
oci_free_statement($cur);
#
# Session details, if wanted
# - these are the popups for each cell
#
include 'active_sessions_details.php';
?>
