<?php
#
# $Id: //Infrastructure/Database/scripts/oav/u.class.php#5 $
#
# helper functions
#
class u{
    private static $info_tabs=0;
    private static $html_tabs=0;
    #
    # Request value, with a default
    #
    static function request_val( $key, $default = '' ){
        if( isset( $_GET[$key] ) ) $request_val = $_GET[$key];
        else                       $request_val = $default;
        return $request_val;
    }
    #
    # echo with a new line terminator
    #
    static function p($str){ echo "$str\n";}
    #
    # Some helpers
    #
    static function tabs( $how_many ){
        $tab='    ';
        $tabs='';
        for($i=0;$i<$how_many;$i++) $tabs=$tabs.$tab;
        return $tabs;
    }
    static function add_space( &$str ){
        if( $str ) $str = ' '.$str;
    }
    static function start_tag( $tag, $extra='' ){
        $tabs=u::tabs(u::$html_tabs++); 
        u::add_space( $extra );
        u::p($tabs.'<'.$tag.$extra.'>');
    }
    static function end_tag( $tag ){
        u::$html_tabs--;
        $tabs=u::tabs(u::$html_tabs);
        u::p($tabs.'</'.$tag.'>');
    }
    static function tag( $tag, $extra='' ){
        $tabs=u::tabs(u::$html_tabs);
        u::add_space( $extra );
        u::p($tabs."<$tag$extra>");
    }
    static function tagged ( $tag, $str, $extra='' ){ 
        u::add_space( $extra );
        return "<$tag"."$extra>$str</$tag>";
    }
    static function tagged_item( $tag, $str, $extra='', $indent_tag=0 ){
        if( $indent_tag ) $tabs=u::tabs(u::$html_tabs); else $tabs='';
        u::p($tabs.u::tagged($tag, $str, $extra));
    }
    static function td(  $str, $extra='',$indent_tag=0){ u::tagged_item('td',$str, $extra, $indent_tag ); }
    static function th(  $str, $extra='',$indent_tag=0){ u::tagged_item('th',$str, $extra, $indent_tag ); }
    static function tr(  $str, $extra='',$indent_tag=1){ u::tagged_item('tr',$str, $extra, $indent_tag ); }
    static function trtd($str, $extra=''              ){ u::tagged_item('tr',u::tagged( 'td', $str, $extra ) ); }
    static function a($text,$href,$str_only=0 ){ 
        $link = 'href="'.$href.'"';
        $tag  = 'a';
        if( $str_only ){
            return u::tagged($tag,$text,$link);
        }else{
            u::tagged_item  ($tag,$text,$link); 
        }
    }
    static function row($vals){
        $cells_str = '';
        foreach( $vals as $val ){
            $cells_str .= u::tagged( 'td', $val );
        } 
        u::tr( $cells_str );
    }
    #
    # Time marker
    #
    static function time_marker( $secs, $current_col ){
        #
        # every 15secs display the time
        #
        if( ($secs % 15) == 0 ){
            #
            # Where does this go?
            #
            $left=$current_col*20;
            $position_str='left:'.$left.'px;bottom:0px;';

            $time_str = sprintf("%02s:%02s:%02s",floor( $secs / 3600 ),floor( ( $secs % 3600 ) / 60 ),$secs % 60);
            u::tagged_item('div', $time_str, 'style="'.$position_str.'" class="time"',1);
        }
    }
    #
    # Fill in missing seconds
    #
    static function fill_in_missing_snaps($to,$from,&$current_col=0){
        #
        # missing snaps?
        #
        $from_times = explode( ':', $from );
        $to_times   = explode( ':', $to );

        $from_secs = $from_times[0]*60*60 + $from_times[1]*60 + $from_times[2];
        $to_secs   = $to_times[0]*60*60   + $to_times[1]*60   + $to_times[2];

        for( $sec = $to_secs - 1; $sec > $from_secs; $sec-- ){
            if( $current_col ){
                 $current_col++;
                 #
                 # Minute exact
                 #
                 u::time_marker( $sec, $current_col );
                 #u::p("<div class=\"sess col$current_col\"></div>");
            }else{
                 printf("<tr><td>%02s:%02s:%02s</td></tr>\n",floor( $sec / 3600 ),floor( ( $sec % 3600 ) / 60 ),$sec % 60);
            }
        }
    }
    #
    # Checkbox
    #
    static function checkbox( $form_item_name, $text, $default_checked=false ){
        #
        # Is this checked?
        #
        if( isset( $_GET[$form_item_name] ) or $default_checked ) :
            $checked_str = ' checked="checked"';
        else :
            $checked_str = '';
        endif;
        #
        # Set if needed
        #
        if( !isset( $_GET[$form_item_name] ) and $default_checked ) $_GET[$form_item_name]='on';
        p('<input type="checkbox" name="'.$form_item_name.'"'.$checked_str.' onClick="submit();">'.$text);
    }
    #
    # Select lists
    #
    static function select_list($name,$values,$selected_val,$submit_on_change=1){
       if( $submit_on_change ) $submit_str=' onchange="submit();"'; else $submit_str='';
       u::start_tag('select','id="'.$name.'" name="'.$name.'"'.$submit_str);  
       foreach( $values as $value_pair ){
           if( $value_pair[0]==$selected_val ) $select_str=' selected="selected"'; else $select_str='';
           u::tagged_item('option',$value_pair[1],'value="'.$value_pair[0].'"'.$select_str,1);
       }
       u::end_tag('select');
    }
    static function select_list_numbers($name,$from_val,$to_val,$selected_val,$multiply_by=1){
       p("<select name=\"$name\" onchange=\"submit();\">");
       for( $val=$from_val; $val<=$to_val; $val++ ){
           $use_val=$val*$multiply_by;
           if( $use_val==$selected_val ) $select_str='selected="selected"'; else $select_str='';
           p("    <option $select_str>$use_val</option>");
       }
       p('</select>');
    }

    #
    # Draw a div box
    # - Used to draw bar charts
    #
    static function box( $width, $color, $offset, $bg_image, $hover_txt, $text, $height, $container=false ){

        if( $color ) $color_str='background-color:'.$color.';'; else $color_str='';

        $styles = 'width:'.$width.'px; height:'.$height.'px; '.$color_str.'left:'.$offset.'px; top:0px;';

        if( $container ){
            #
            # Wrapper div so just open tag
            #
            $styles = $styles . 'position:relative;';
            u::start_tag('div','style="'.$styles.'"',1);
        }else{
            #
            # Graph element
            # - wrap in a lable and set background
            #
            $styles = $styles."position:absolute;background-image:url('".$bg_image."')";
            u::tagged_item('a','<div style="'.$styles.'">'.$text.'</div>','title="'.$hover_txt.'"',1);
        }

     }
    #
    # Include with comment
    #
    static function inc( $file ){
        extract($GLOBALS, EXTR_REFS);
        u::info('<!-- '.$file.' -->');
        u::$info_tabs++;
        include $file;
        u::$info_tabs--;
        u::info('<!-- END : '.$file.' -->');
    }
    static function info( $msg, $in_comment=0 ){
        if( isset( $_GET['info'] ) ){
            if( $in_comment ) $msg = "<!-- $msg -->";
            $tabs=u::tabs(u::$info_tabs);
            u::p($tabs.$msg);
            ob_flush();
            flush();
        }
        
    }
    #
    # Colored rows
    #
    static function odd_even_class_str( $row_num ){
        if(is_int($row_num++/2)){
            $class_str=' class="alt"';
        }else{
            $class_str='';  
        }
        return $class_str;
    }
    #
    # html table filler/empty row
    #
    static function filler_row( $num_cells=1, $extra='', $cell_content='&nbsp;' ){
        $cells = '';
        for( $i=1;$i<=$num_cells;$i++ ){
            #
            # Last cell?
            # - remove right border on this, done by container div
            #
            if( $i===$num_cells ) $td_extra=' style="border-right:0px;"'; else $td_extra='';
            $cells = $cells . u::tagged('td',$cell_content,$td_extra);
        }
        u::tr($cells,$extra);
    }
    #
    # Filler rows
    #
    static function filler_rows( $max_rows, $current_row_count, $num_cells, $cell_content='&nbsp;' ){
        if( $current_row_count < $max_rows ){        
            for($row_num=$current_row_count;$row_num<=$max_rows;$row_num++){
                $class_str=u::odd_even_class_str($row_num);
                u::filler_row( $num_cells, $class_str, $cell_content );
            }
        }
    }
    #
    # Flush output
    #
    static function flush(){
        ob_flush();
        flush();
    }
    #
    #
    #
}
?>
