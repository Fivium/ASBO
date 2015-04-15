<?php
#
# $Id: //Infrastructure/GitHub/Database/avo/web/db_monitor_html_style_and_js.php#2 $
#
# Start of the html page for the main monitor page
#
# T Dale
#



#
# Detail urls
#
$graph_extra_param    = '&active_sessions=on&mins_hist=2';
$sql_extra_param      = '&top_sql=on&mins_hist=2';
$sessions_extra_param = '&top_sessions=on&mins_hist=2';
$graph_icons_param    = '&graph_icons='.u::request_val('graph_icons','0');
$chart_icons_param    = '&chart_icons='.u::request_val('chart_icons','0');
$color_scheme_param   = '&color_scheme='.u::request_val('color_scheme','2');
$cell_style_param     = '&cell_style='.u::request_val('cell_style','3');
$max_cpu_param        = '&display_max_cpu='.u::request_val('display_max_cpu',1);

$sys_load_url = 
   "system_load.php?&hide=on&snaps_table=$snaps_table&db=$db&sql_mins_hist=2&width=$chart_width&max_width=$chart_max_width"
   . $graph_icons_param
   . $chart_icons_param
   . $color_scheme_param
   . $cell_style_param
   . $max_cpu_param;
$graph_url    = $sys_load_url.$graph_extra_param;
$sql_url      = $sys_load_url.$sql_extra_param;
$sessions_url = $sys_load_url.$sessions_extra_param;
$sys_load_url = $sys_load_url."&div_only=1";

u::p('<!DOCTYPE html>');
?>
<html>
<head>
    <title>AVO - <?php echo $db;?></title>
    <style>
<?php include 'table_css.php';?>
        .sess_detail {position:fixed;width:500px;top:310;left:140;z-index:2;background-color:white}
    </style>
    <script>
        var top_div_timer = { timer_ref: null, refresh_secs: 2 };
        var mid_div_timer = { timer_ref: null, refresh_secs: 10 };
        var low_div_timer = { timer_ref: null, refresh_secs: 10 };
        var top_param     = '<?php echo $graph_extra_param;    ?>';
        var mid_param     = '<?php echo $sql_extra_param;      ?>';
        var low_param     = '<?php echo $sessions_extra_param; ?>';

        function refresh(area,extra_param,div_timer){

            loadXMLDoc(area,extra_param);
            div_timer.timer_ref = setTimeout( function() { refresh(area,extra_param,div_timer); }, div_timer.refresh_secs*1000 );

        }

        function start_monitoring(){

            refresh( 'graph1', top_param, top_div_timer );
            refresh( 'graph2', mid_param, mid_div_timer );
            refresh( 'graph3', low_param, low_div_timer );
        }

        function alter_timers(state){

            if (state=='stop'){
                clearTimeout(top_div_timer.timer_ref);
                clearTimeout(mid_div_timer.timer_ref);
                clearTimeout(low_div_timer.timer_ref);
            }else{
                refresh( 'graph1', top_param, top_div_timer );
                refresh( 'graph2', mid_param, mid_div_timer );
                refresh( 'graph3', low_param, low_div_timer );
            }

        }

        function showhide(id,show){
            obj       = document.getElementById(id);
            graph_obj = document.getElementById("active_sessions_graph");
            if (show == 1){
                obj.style.display = "";
                obj.style.top    = 250;//parseInt(graph_obj.style.top) + 290;
                obj.style.left   = 100;//parseInt(graph_obj.style.left) + 100;
                setTimeout( "showhide('"+id+"')", 30*1000 );
            } else {
                obj.style.display = "none";
            }
        }
        function highlight(look_for){
            graph = document.getElementById("active_sessions_graph");
            cells = graph.getElementsByTagName("div");
            cell_count = 0;
            
            for( i in cells){
                cell_count = cell_count + 1;
                cur_cell   = cells[i];
                // Change back we are highlighting somthing else
                if( cur_cell.style !== undefined ){
                    if( cur_cell.style.backgroundColor = 'yellow' ){
                        cur_cell.style.backgroundColor = '';
                    }
                }
                
                if( cur_cell.id !== undefined && cur_cell.id.indexOf( '_' + look_for + '_' ) > 0 ){
                    cur_cell.style.backgroundColor = 'yellow';
                    //alert('Found one!');
                }
            } 
            //alert("looked for "+look_for+" in cell count "+cell_count);


        }

function loadXMLDoc(div_to_replace,extra_param){
    var xmlhttp;
    if (window.XMLHttpRequest){// code for IE7+, Firefox, Chrome, Opera, Safari
        xmlhttp=new XMLHttpRequest();
    }else{// code for IE6, IE5
        xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange=function(){
        if (xmlhttp.readyState==4 && xmlhttp.status==200){
            document.getElementById(div_to_replace).innerHTML=xmlhttp.responseText;
        }
    }

    // Get all the graphs
    base_url = "<?php echo $sys_load_url; ?>";

    xmlhttp.open("GET",base_url + extra_param,true);
    xmlhttp.send();

}
function toggle_button(current_val){
    if ( current_val == 'Stop' ){
        alter_timers('stop');
        document.getElementById("btn").value='Start';
    }else{
        alter_timers('start');
        document.getElementById("btn").value='Stop';
    }
}

    </script>
</head>
<?php
u::start_tag('body', 'onload="start_monitoring();"');
