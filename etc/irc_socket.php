<?php

/**
 * Socket class for handling IRC datastreams
 *
 * @author David Wosnitza
 * @version 0.1
 * @copyright 24 August, 2011
 * @package IRC5
 **/

/**
 * Let's get it on!
 **/
class IRCSocket {

	private 	$server				= '';
	private 	$port				= -1;
	private 	$socket 			= null;
	private		$assoc_web_socket 	= null;
	private		$err 				= '';
	
	
	public function __construct ($websocket, $server, $port, $auto_connect = false) {
		// Initialize the IRCSocket
		$this->server		 	= $server;
		$this->port				= $port;
		$this->assoc_web_socket = $websocket;
		if ($auto_connect) {
			$this->connect();
		}
	}
	
	// Basic Getters & Setters
	public function set_server($server) {
		if($this->socket !== null) {
			$this->server = $server;
		}
	}

	public function set_port($port) {
		if($this->socket !== null) {
			$this->port = $port;
		}
	}
	
	
	/**
	 * This is where the magic happens :P
	 */
	public function connect() {
		
		// Try to create the IRC Stream Socket
		if ($this->server !== '' && $this->port !== -1) {
			$this->socket = fsockopen($this->server, $this->port, $errno, $errstr, 30);
			$this->err = $errno.' - '.$errstr;
		}
		
		if ($this->err !== '') {
			// Error Occured? Output message and exit
			echo '***IRCSocket Error: '.$this->err."\n";
			$this->socket = null;
		}
		else {
			// No error? F/Yeah! Register thread and get the shit running! 
			$t = new Thread($this->socket);
			$t->start('IRCSocket::handle_stream_data');
		}
	}
	
	/**
	 * Pushing stream data like a BOSS!
	 */
	static function handle_stream_data($socket = null) {
		if ($socket !== null && $socket != false) {
			//$socket->
		}
		
	}
	
	public function __destruct() {
		// Gracefully exit :)
		if ($this->socket != null) {
			fclose($this->socket);
		}
	} 
	
	
	
	
}