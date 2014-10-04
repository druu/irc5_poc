<?php
$command ="/usr/bin/php -f /var/www/vhosts/crushhour.net/httpdocs/IRC5/tServer.php > irc5.log &";
exec($command, $arrOutput);
?>
