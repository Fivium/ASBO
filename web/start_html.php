<?php
#
# $Id: //Infrastructure/GitHub/Database/avo/web/start_html.php#2 $
#
# Html start and javascript
#

#
# Main page needs some js etc
#
$uri=$_SERVER['REQUEST_URI'];

if ( strpos( $uri, 'db_monitor.php' ) > 0 or strpos( $uri, 'system_load.php' ) ){
    u::inc( 'db_monitor_html_style_and_js.php' );
}else{
    echo "<!doctype html>\n<html>\n<head>\n</head>\n<body>\n";
}
?>
