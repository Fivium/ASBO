<?php
#
# Start HTML page and menu
#
# $Id: //Infrastructure/GitHub/Database/avo/web/db_monitor.php#2 $
#

#
# Start session etc
#
include 'start.php';
#
# Top menu
#
$db = @$_GET['db'];
$form_name = 'db_monitor.php';
u::inc('menu.php');
#
# Exit if no db selected, just display the menu
#
if(!$db) exit;
#
# Divs for displaying monitoring
#
?>
<div id="graph1" style="position:relative;"></div>
<div id="graph3" style="position:relative;left:0;overflow:hidden;"></div>
<div id="graph2" style="position:relative;left:0;overflow:hidden;"></div>
</body>
</html>
