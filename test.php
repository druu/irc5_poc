<?php

#phpinfo();
#die();

#include('Thread.php');

error_reporting (-1);

/*if( ! Thread::available() ) {
	die( 'Threads not supported' );
}
*/

pcntl_fork();
function echoTest($start) {
	
	for ($i=0; $i<10; $i+=1){
		echo ($i+$start)." // ";
	}
	
}

echo "run!";


$t = new Thread('echoTest');
$t->start(10);
while ($t->isAlive()) {
	sleep(1);
}