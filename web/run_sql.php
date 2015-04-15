<?php
#
# $Id: //Infrastructure/GitHub/Database/avo/web/run_sql.php#2 $
#
# Run some sql and do binds if needed
# T Dale
#
include 'start.php';
#
# Simple run sql
#
$sql_file    = u::request_val( 'sql_file'    );
$param1      = u::request_val( 'param1'      );
$param2      = u::request_val( 'param2'      );
$param3      = u::request_val( 'param3'      );
$param4      = u::request_val( 'param4'      );
$dbms_output = u::request_val( 'dbms_output' );

p("<title>Run : $sql_file</title>");

$sql = file_get_contents( "./sql/".$sql_file.".sql" );
#$sql = str_replace( "'", "\'", $sql );
#
# Schema needed?
#
if( $schema = u::request_val( 'schema' ) ){
    $sql = str_replace( '__SCHEMA__', $schema, $sql );
}

#p($sql);

$binds   = '';
if( $param1   ) $binds[] = array( ":param1"  , $param1   );
if( $param2   ) $binds[] = array( ":param2"  , $param2   );
if( $param3   ) $binds[] = array( ":param3"  , $param3   );
if( $param4   ) $binds[] = array( ":param4"  , $param4   );

if( $dbms_output ) $db_obj->dbms_output_on();

$cur = $db_obj->exec_sql( $sql, $binds );
#
# SQL results or dbms_output
#
if( $dbms_output ){
    $db_obj->get_dbms_output();
    oci_free_statement($cur);
}else{
    $db_obj->html_results( $cur );
}

include 'end.php';
?>
