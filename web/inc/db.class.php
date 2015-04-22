<?php
#
# $Id: //Infrastructure/GitHub/Database/avo/web/inc/db.class.php#3 $
#
class db{
    public  $connection_error;
    private $sql_details;
    private $conn;
    private $enterprise_edition;
    private $using_dbsync;
    private $num_cores;
    #
    # Start and connect
    # 
    function __construct($user,$pw,$conn_str,$ignore_connect_error=0){

        #$this->conn = oci_pconnect($user, $pw, $conn_str);
        u::info("getting connection to $conn_str",1);
        
        $this->conn = oci_connect($user, $pw, $conn_str);

        if (!$this->conn) {
            $e    = oci_error();
            $errm = htmlentities($e['message'], ENT_QUOTES);
            if ($ignore_connect_error){
                u::p( $errm );
                $this->connection_error = 1;
            }else{
                trigger_error($errm, E_USER_ERROR);
            }
        }else{
            $this->connection_error = 0;
        }
    }
    function __destruct(){
       if( !$this->connection_error ){ oci_close( $this->conn ); }
    }
    #
    # Display all sql run so far
    #
    function display_sql(){
        #
        # Formating for sqlplus
        #
        u::p('set linesize 200');
        u::p('set pagesize 999');
        u::p('set feedback off');
        u::p('col s_time format a8');
        u::p('col active_sessions format a20');
        u::p('col on_cpu format 900');
        u::p('col user_io format 900');
        u::p('col system_io format 900');
        u::p('col other format 900');
        u::p('col sessions_in_lock format a15');
        u::p('col sql_id format a15');
        u::p('col sql_ids format a60');
        u::p('col sql_text format a90');
        u::p('col sid format 9999');
        u::p('col to_kill format a25');
        u::p('col username format a10');
        u::p('col program format a40');
        u::p('col sids_on_cpu format a40');
        u::p('col sids_user_io format a15');
        u::p('col sids_system_io format a15');
        u::p('col sids_blocked format a16');
        u::p('col others format a15');        
        u::p('----------------------');
        #
        # Dump out the sql we have run
        #
        foreach( $this->sql_details as $sql_detail ) u::p($sql_detail);
    }    
    #
    # parse and execute sql
    #
    function exec_sql( $sql, $binds = '' ){
        $time_start = microtime(true);

        $cur = oci_parse( $this->conn, $sql );
        #
        # Any binds?
        #
        if( $binds ){
            foreach( $binds as $bind_rec ){
                $this->sql_details[] = '-- bind : ' . $bind_rec[0] . ' val : ' . $bind_rec[1];
                #
                # What type of bind
                #
                if( is_numeric( $bind_rec[1] ) ) $bind_type = ' number';
                else{
                    $var_length = strlen($bind_rec[1]);
                    if( $var_length === 0 ) $var_length = 1;
                    $bind_type = " varchar2($var_length)";
                }
                $this->sql_details[] = 'var '  . ltrim($bind_rec[0],':') . $bind_type;
                $this->sql_details[] = 'exec ' . $bind_rec[0]            ." := '".  $bind_rec[1] ."';";
                oci_bind_by_name( $cur, $bind_rec[0], $bind_rec[1] );
            }
        }
        
        $this->sql_details[] = $sql . ';';
        $this->exec_catch( $cur ); 

        $this->sql_details[] = '-- Parse and excec time (secs) : ' . number_format( microtime(true) - $time_start, 4 );
        $this->sql_details[] = '--------------------------------';
        return $cur;
    }
    #
    # Simple one rec query
    #
    function single_rec( $sql, $binds = '' ){
        $cur = $this->exec_sql( $sql, $binds );
        return oci_fetch_object( $cur );
    }
    #
    # exec and catch exceptions
    #
    function exec_catch( $cur ){
        try{
            oci_execute( $cur );
        }catch(Exception $e){
            ##
            ## Might be something in buffer, so check first
            ##
            $this->get_dbms_output();
            die( 'ORACLE ERROR : ' . $e->getMessage() );
        }
    }
    #
    # fetch and catch exceptions
    #
    function fetch_catch( $cur ){
        try{
            $rec = oci_fetch_object( $cur );
        }catch(Exception $e){
            die( 'ORACLE ERROR : ' . $e->getMessage() );
        }
        return $rec;
    }
    #
    # Basic sql output html
    #
    function html_results( $cur, $fmt = 1, $link_col = 0, $link = '', $show_no_rows = 1 ){
        if( $fmt ){
            u::p('<style>table td {white-space: nowrap;border: 1px solid silver;font-family:Courier New}</style>');
            u::p('<table>');
        }else{
            u::p('<pre>');
        }
        $print_header = 1;
        $rows_fetched = 0;
        while ( ( $rec = oci_fetch_object( $cur ) ) ){
            $rows_fetched++;
            if( $fmt ) u::p('<tr>');
            #
            # Display column headers
            #
            if( $print_header ){
                if( $fmt ) u::p('<tr>');
                foreach ( $rec as $key => $val ){ 
                    if( $fmt ){
                        u::th($key, 'style="background-color:silver;color:white"');
                    }else{
                        u::p($key);
                    }
                }
                if( $fmt ) u::p('</tr>');
                $print_header = 0;
            }
            #
            # Row data
            #
            if( $fmt ) u::p('<tr>');
            $col = 0;
            foreach ( $rec as $key => $val ){ 
                $col++;
                if( $col === $link_col ) $val = u::a( $val, str_replace( '__VAL__', $val, $link ), 1 );
                if( $fmt ){
                    #
                    # Extra formatting?
                    #
                    if( $val == 'CRITICAL' ){
                        $extra = 'style="background-color:red;"';
                    }else{
                        $extra = '';
                    }
                    u::td($val,$extra); 
                }else{
                    u::p($val);
                }
            }
            if( $fmt ) u::p('</tr>');
        }
        oci_free_statement($cur);
        if( !$rows_fetched and $fmt and $show_no_rows ) u::trtd('No Rows Returned','style="background-color:red;"');
        if( $fmt ){
            u::p('</table>');
        }else{
            u::p('</pre>');
        }
    }
    #
    # Turn on dbms_output
    #
    function dbms_output_on(){
        $cur = $this->exec_sql( 'begin dbms_output.enable(1000000); end;' );
    }
    #
    # Get the dbms_output
    #
    function get_dbms_output( ){
        $get_ouput_line_sql = "begin dbms_output.get_line(:line_text, :status); end;";
        $cur = oci_parse( $this->conn, $get_ouput_line_sql );
        oci_bind_by_name( $cur, ":line_text", $line_text, 255      );
        oci_bind_by_name( $cur, ":status"   , $status   , SQLT_INT );
        #
        # Get one row from the buffer at a time
        #
        p('<pre>');
        while( ( $row_found = oci_execute( $cur ) ) and !$status ){
            p( $line_text );
        }
        p('</pre>');
        oci_free_statement($cur);
    }
    #
    # Check if this enterprise edition database
    #
    function enterprise_edition(){
        
        if( !isset( $this->enterprise_edition ) ){

            $enterprise_check = $this->single_rec("select 'YES' enterprise_edition from v\$version where lower(banner) like '%enterprise%'");

            if( isset( $enterprise_check->ENTERPRISE_EDITION ) ){
                $this->enterprise_edition = 1;
            }else{
                $this->enterprise_edition = 0;
            }

        }
        return $this->enterprise_edition; 
    }
    #
    # How many cpu cores
    #
    function cpu_cores(){
        if( !isset( $this->cpu_cores ) ){
          $cpu_cores_check = $this->single_rec("select value cpu_cores from v\$osstat where osstat_id = 0");
          $this->cpu_cores = $cpu_cores_check->CPU_CORES;
        }
    
        return $this->cpu_cores;
    }
    #
    # Check if dbsync is configured
    #
    function using_dbsync(){

        if( !isset( $this->using_dbsync ) ){

            $dbsync_check = $this->single_rec("select 'YES' using_dbsync from dba_tables where owner= 'DBAMGR' and table_name = 'DBSYNC_STANDBY'");

            if( isset( $dbsync_check->USING_DBSYNC ) ){
                $this->using_dbsync = 1;
            }else{
                $this->using_dbsync = 0;
            }

        }
        return $this->using_dbsync;
    }
    #
    #
    #
}
?>
