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

$allowed_sql_files = array(
    'advisor_executions',
    'create_outline',
    'last_login_check',
    'logfile_sizes',
    'system_stats',
    'unindexed_fk'
);

if(in_array($sql_file,$allowed_sql_files)){

	u::p("<!--");
	for($i=1;$i<=4;$i++){
	    $param_name="param$i";
	    u::p("Param$i : ".$$param_name);
	}
	u::p("-->");

	p("<title>Run : $sql_file</title>");

	$sql = file_get_contents( "./sql/".$sql_file.".sql" );
	#$sql = str_replace( "'", "\'", $sql );
	#
	# Schema needed?
	#
	if( $schema = u::request_val( 'schema' ) ){
	    $sql = str_replace( '__SCHEMA__', $schema, $sql );
	}

	u::p("<!--");
	p($sql);
	u::p("-->");


	function var_set_to_something($var){
	   
	     if( $var or $var === '0' or $var === 0 ){
	         return 1;
	     }else{
	         return 0;
	     }     
	}

	$binds   = array();
	if( var_set_to_something($param1) ) $binds[] = array( ":param1"  , $param1   );
	if( var_set_to_something($param2) ) $binds[] = array( ":param2"  , $param2   );
	if( var_set_to_something($param3) ) $binds[] = array( ":param3"  , $param3   );
	if( var_set_to_something($param4) ) $binds[] = array( ":param4"  , $param4   );

	u::p("<!--");
	foreach($binds as $bind_pair){
	    u::p($bind_pair[0].' : '.$bind_pair[1]);
	}
	u::p("-->");

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

	}

include 'end.php';
?>

