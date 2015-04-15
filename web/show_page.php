<?php
$page = $_GET['page'];
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
echo "\nPage wanted : ".$_SERVER[SERVER_NAME]."/oav/$page \n";

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

include $parsed_url['path']
?>
