<?php
#
# T Dale 2010-12-20
# - page start
#
# $Id: //Infrastructure/GitHub/Database/avo/web/start.php#2 $
#
function debug($str){
    if( isset( $_GET['debug'] ) ){
        echo "$str\n";
        ob_flush();
        flush();
    }
}
debug('start of start.php');
function __autoload($class_name) {
    include 'inc/' . $class_name . '.class.php';
}

u::info("start.php",1);
#
# Check what is being called
#
u::info( "<!-- GET/POST" );
$longest = 0;
foreach($_REQUEST as $key=>$val){ if (strlen($key)>$longest) $longest = strlen($key); }
foreach($_REQUEST as $key=>$val){
    u::info( str_pad($key,$longest) . ' = ' . $val );
}
u::info("-->");
#
# Turn warnings into errors
#
function handleError($errno, $errstr, $errfile, $errline, array $errcontext)
{
    // error was suppressed with the @-operator
    if (0 === error_reporting()) {
        return false;
    }

    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler('handleError');
#
# Constants
#
define('C_NONE','none');
define('C_PRODUCTION_ENVIROMENT','PRODUCTION');
define('C_DEVELOPMENT_ENVIROMENT','DEVELOPMENT');
#
# Widths
#
$chart_width     = u::request_val( 'width'    , 300  );
$chart_max_width = u::request_val( 'max_width', 2000 );
#
# Get the database connection details
# - This sets var $db_look_up_xml
# - If you move this file then this is the only update needed
#
include 'conf/db_lookup.php';

if( $chart_max_width < $chart_width ) $chart_max_width = $chart_width;

#
# Are we displaying just the div contents?
# Otherwise we will want page start tags, js etc
#
$div_only = u::request_val('div_only',0);
#
# DB connection
#
$db=@$_GET['db'];

if( !isset( $choose_db ) or $div_only ) $choose_db = 0;

if( $db===C_NONE ) $db='';

if($choose_db){
    $form_name = 'db_monitor.php';
    u::inc( 'menu.php' );
}
#
# What db?
#
if($db){
    $db_lookup = simplexml_load_string($db_look_up_xml);
    foreach($db_lookup as $key0 => $db_obj){
        if( $db_obj['name'] == $db ){
            $user        = $db_obj->user;
            $pw          = $db_obj->pw;
            $conn_str    = $db_obj->conn_str;
            #
            # Do we have an over ride session snaps table
            #
            if ( $db_obj->snaps_table ){
                $snaps_table = $db_obj->snaps_table;
            }else{
                $snaps_table = 'v$active_session_history';
            }
            define( 'SNAPS_TABLE', "$snaps_table" );
            #
            # Use our own history tables?
            #
            if ( $db_obj->use_local_awr_tables ){
                $use_our_own_history_tables = TRUE;
            }else{
                $use_our_own_history_tables = FALSE;
            } 
            #
            # Enviroment
            #
            if( $db_obj->enviroment == C_DEVELOPMENT_ENVIROMENT ){
                define( 'ENVIROMENT', C_DEVELOPMENT_ENVIROMENT );
            }else{
                define( 'ENVIROMENT', C_PRODUCTION_ENVIROMENT );
            }            
        }
    }
}else{
    #exit;
    $snaps_table = '';
}

#
# If we want a full page, the add styles etc
#
if( !$div_only or u::request_val( 'table_styles' ) ) u::inc( 'start_html.php' );
#
# Database object
#
if( $db ){
   $db_obj = new db($user, $pw, $conn_str);
   #
   # Set oracle date format
   #
   $date_fmt = 'HH24:MI:SS';
   $db_obj->exec_sql( "ALTER SESSION SET NLS_DATE_FORMAT = '$date_fmt'" );
   $db_obj->exec_sql( "ALTER SESSION SET NLS_TIMESTAMP_FORMAT = '$date_fmt'" );
   #
   # get database sysdate
   #
   $oracle_sysdate = $db_obj->single_rec("select to_char(sysdate,'hh24:mi:ss') db_date from dual")->DB_DATE;
}
#
# print helpers
#
function p ($str){ u::p ($str); }
function td($str,$extra=''){ u::td($str,$extra); }
function tr($str,$extra=''){ u::tr($str,$extra); }

#
# Build the link to the sql detail page
#
function sql_details_link($sql_id,$db,$hint=''){
    return "<a title=\"$hint\" href=\"sql_details.php?db=$db&amp;sql_id=$sql_id\">$sql_id</a>";
}
#
# cell style
#
function cell_style_str($type,$sid){
    $style_str = '';
    #
    # Anything there?
    #
    if($sid){
        $style_str = 'style="background-color:';
        switch($type){
            case 'ON_CPU':
                $style_str .= 'green';
                break;
            case 'USER_IO':
                $style_str .= 'blue;color:white';
                break;
            case 'SYSTEM_IO':
                $style_str .= 'lightblue';
                break;
            case 'SESSIONS_IN_LOCK':
                $style_str .= 'red';
                break;
        }
        $style_str .= '"';
    }
    return $style_str;
}

#
# Colors
#
$color_scheme = u::request_val('color_scheme',2);
#
# Colors
#
if( $color_scheme == 1 ){
   $colors = array( 
      'blue'
   ,  'lightblue'
   ,  'green' 
   ,  'red'   
   ,  'pink'  
   );
}elseif( $color_scheme == 2 ){
   $colors = array( 
      '#004fef'
   ,  '#0095ef' 
   ,  '#13ce00'
   ,  '#c42c00'
   ,  '#f670ac'
   );
}


#
# background color style
#
function style_color($ind,$colors){
   return 'background-color:'.$colors[$ind].';';
}

?>
