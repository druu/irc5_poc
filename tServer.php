#!/php -q
<?php

include('Thread.php');


error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();

#$master  = WebSocket("localhost",81);
$master  = WebSocket("85.25.146.135",12345);
$sockets = array($master);
$users   = array();
$debug   = true;
$threads = array();



function handleClient($user) {
	if (!$user->ircSocket) {
		send($user->socket, '###NOPE');
		return;
	}
	send($user->socket, '###YUP');
	while (!feof($user->ircSocket)){
		$ircInput = fgets($user->ircSocket, 1024);
		send($user->socket, $ircInput);
    }
}


while(true){
	
	if(count($threads)> 0){
		foreach( $threads as $index => $thread ) {
			if( ! $thread->isAlive() ) {
				unset( $threads[$index] );
			}
		}
	}
	
  $changed = $sockets;
  socket_select($changed,$write=NULL,$except=NULL,NULL);
  foreach($changed as $socket){
    if($socket==$master){
      $client=socket_accept($master);
      if($client<0){ console("socket_accept() failed"); continue; }
      else{ 
		connect($client);
	  }
    }
	else{
	  $bytes = @socket_recv($socket,$buffer,2048,0);
	  if($bytes==0){ disconnect($socket); }
      else{
		$user = getuserbysocket($socket);
        if(!$user->handshake){ dohandshake($user,$buffer); }
        else{ process($user,$buffer); }
      }
    }
  }
}

//---------------------------------------------------------------
function process(&$user,$msg){
	global $threads;
  $action = unwrap($msg);
  say("< ".$action);
	
	if(strpos($action,"QUIT") === 0) {
		disconnect($user->socket);
	}
	
	if ($action==="###IRC" && $user->ircSocket === null){
		$user->ircSocket = fsockopen('irc.freenode.net', 6667, $errno, $errstr, 30);
		$t = new Thread('handleClient');
		$t->start($user);
		array_push($threads, $t);
	}

  if($user->ircSocket){
	fwrite($user->ircSocket, $action."\r\n");
  }

}

function send($client,$msg){
  #say("> ".$msg);
  $msg = wrap($msg);
  socket_write($client,$msg,strlen($msg));
}

function WebSocket($address,$port){
  $master=socket_create(AF_INET, SOCK_STREAM, SOL_TCP)     or die("socket_create() failed");
  socket_set_option($master, SOL_SOCKET, SO_REUSEADDR, 1)  or die("socket_option() failed");
  socket_bind($master, $address, $port)                    or die("socket_bind() failed");
  socket_listen($master,20)                                or die("socket_listen() failed");
  echo "Server Started : ".date('Y-m-d H:i:s')."\n";
  echo "Master socket  : ".$master."\n";
  echo "Listening on   : ".$address." port ".$port."\n\n";
  return $master;
}

function connect($socket){
  global $sockets,$users;
  $user = new User();
  $user->id = uniqid();
  $user->socket = $socket;
  $user->ircSocket = null;
  console('client connected');
  console(print_r($user,true));
  #$user->ircSocket = fsockopen('irc.freenode.net', 6667, $errno, $errstr, 30);
  
  array_push($users,$user);
  array_push($sockets,$socket);
  
  console($socket." CONNECTED!");
  #return $user;
}

function disconnect($socket){
  global $sockets,$users;
  $found=null;
  $n=count($users);
  for($i=0;$i<$n;$i++){
    if($users[$i]->socket==$socket){ $found=$i; break; }
  }
  if(!is_null($found)){ array_splice($users,$found,1); }
  $index = array_search($socket,$sockets);
  socket_close($socket);
  console($socket." DISCONNECTED!");
  if($index>=0){ array_splice($sockets,$index,1); }
}

function dohandshake($user,$buffer){
  console("\nRequesting handshake...");
  console($buffer);
  list($resource,$host,$origin,$strkey1,$strkey2,$data) = getheaders($buffer);
  console("Handshaking...");
  
  $strkey1 = utf8_decode($strkey1);
  #$strkey2 = utf8_decode($strkey2);


  $pattern = '/[^\d]*/';
  $replacement = '';
  $numkey1 = preg_replace($pattern, $replacement, $strkey1);
  $numkey2 = preg_replace($pattern, $replacement, $strkey2);

  $pattern = '/[^ ]*/';
  $replacement = '';
  $spaces1 = strlen(preg_replace($pattern, $replacement, $strkey1));
  $spaces2 = strlen(preg_replace($pattern, $replacement, $strkey2));

  if ($spaces1 == 0 || $spaces2 == 0 || $numkey1 % $spaces1 != 0 || $numkey2 % $spaces2 != 0) {
		console('failed');
		disconnect($user->socket);
		//socket_close($user->socket);
        return false;
  }

  $ctx = hash_init('md5');
  hash_update($ctx, pack("N", $numkey1/$spaces1));
  hash_update($ctx, pack("N", $numkey2/$spaces2));
  hash_update($ctx, $data);
  $hash_data = hash_final($ctx,true);

  $upgrade  = "HTTP/1.1 101 WebSocket Protocol Handshake\r\n" .
              "Upgrade: WebSocket\r\n" .
              "Connection: Upgrade\r\n" .
              "Sec-WebSocket-Origin: " . $origin . "\r\n" .
              "Sec-WebSocket-Location: ws://" . $host . $resource . "\r\n" .
              "\r\n" .
              $hash_data;

  socket_write($user->socket,$upgrade.chr(0),strlen($upgrade.chr(0)));
  $user->handshake=true;
  console($upgrade);
  console("Done handshaking...");
  return true;
}

function getheaders($req){
  $r=$h=$o=null;
  if(preg_match("/GET (.*) HTTP/"   ,$req,$match)){ $r=$match[1]; }
  if(preg_match("/Host: (.*)\r\n/"  ,$req,$match)){ $h=$match[1]; }
  if(preg_match("/Origin: (.*)\r\n/",$req,$match)){ $o=$match[1]; }
  if(preg_match("/Sec-WebSocket-Key2: (.*)\r\n/",$req,$match)){ $key2=$match[1]; }
  if(preg_match("/Sec-WebSocket-Key1: (.*)\r\n/",$req,$match)){ $key1=$match[1]; }
  if(preg_match("/\r\n(.*?)\$/",$req,$match)){ $data=$match[1]; }
  return array($r,$h,$o,$key1,$key2,$data);
}

function getuserbysocket($socket){
  global $users;
  $found=null;
  foreach($users as $user){
    if($user->socket==$socket){ $found=$user; break; }
  }
  return $found;
}

function     say($msg=""){ echo $msg."\n"; }
function    wrap($msg=""){ return chr(0).$msg.chr(255); }
function  unwrap($msg=""){ return substr($msg,1,strlen($msg)-2); }
function console($msg=""){ global $debug; if($debug){ echo $msg."\n"; } }

class User{
  var $id;
  var $socket;
  var $handshake;
  var $ircSocket;
}
