<?php
   #
   # $Id$
   #
   # Bar chart using divs and images
   #

   #
   # Do we set the background images?
   #
   $use_images = u::request_val('graph_icons',1);

   if( $use_images ){
      $bar_height = 20;
   }else{
      $bar_height = 24;
   }
   #
   # Multiply the % by
   # 
   $size_factor = 3;
   #
   # Widths we will draw
   #
   $total_bar_width = 0;
   $graph_total     = 0;
    
   foreach( $values as $value ){
      $graph_total = $graph_total + $value;
   }
   $total_bar_width = $graph_total * $size_factor;
   #
   # Change the factoring?
   # 
   if( $graph_total > 100 ){
      $normalize_factor = $graph_total/100;
      $size_factor = $size_factor/$normalize_factor;     
   }else{
      $normalize_factor = 1;
   }
   #
   # Chart width in percent, plus a bit for the %
   # - unless the values are bigger than 100!
   # 
   if($total_bar_width<100){
      $tail_size = 40;
      $width     = 100*$size_factor+$tail_size;
   }else{
      $tail_size = 50;
      $width     = $total_bar_width/$normalize_factor+$tail_size;

   }
   #
   # Container box
   #
   u::box( $width, '', 0, 'none', '', '', $bar_height, true );
   #
   # Lookups
   #
   $color_lookup = array(
      $colors[2]
   ,  $colors[0]
   ,  $colors[1]
   ,  $colors[3]
   ,  $colors[4]
   );

   $bg_image_lookup = array(
      'img/cpu_20_20.png'
   ,  'img/disk_20_20.png'
   ,  'img/disk_20_20.png'
   ,  'img/blocked_20_20.png'
   ,  'img/other_20_20.png'
   );

   $label_lookup = array(
      'CPU'
   ,  'Users IO'
   ,  'System IO'
   ,  'Sesson blocked'
   ,  'Other wait'
   );
   #
   # Graph
   # 
   $offset = 0;
   $item   = 0;
   foreach( $values as $value ){

      if( $value > 0){
         #
         # Draw the box
         #
         $hover_txt = $label_lookup[$item] . ' ' . $value  . '%';
         $value     = $value * $size_factor;
         if( $use_images ){
            $image = $bg_image_lookup[$item];
         }else{
            $image = '';
         }
         u::box( $value, $color_lookup[$item], $offset, $image, $hover_txt, '', $bar_height );
         $offset = $offset + $value;
      }
      $item++; 
   } 
   #
   # Did we draw anything?
   #
   if( $offset ){
      #
      # Total percent label
      #
      $total_str = $offset/$size_factor."%";      
      u::box( $tail_size, '', $offset, '', $total_str, $total_str, $bar_height );
   }
   #
   # End the container div
   #
   u::end_tag('div',1);
?>
