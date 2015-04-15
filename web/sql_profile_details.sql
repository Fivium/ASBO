<?php
#
# $Id: //Infrastructure/GitHub/Database/avo/web/sql_profile_details.sql#2 $
#
include 'start.php';
#
# Locks
#
$sql = "
SELECT 
  comp_data 
FROM 
  dbmshsxp_sql_profile_attr
WHERE
  profile_name = :profile_name
";

$cur = $db_obj->exec_sql( $sql, array( array( ":profile_name", u::request_val('profile_name') ) ) );
$db_obj->html_results( $cur );
include 'end.php';
?>
