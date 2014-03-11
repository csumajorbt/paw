<?php
class Paw
{
	private $SOCKET = null;
	private $RES = null;
	private $SPAWN = null;
	private $_SETTINGS = null;
	private $_REQUESTS = 0;
	
	public function __constructor()
	{
	
	}
	
	public function run()
	{
		$_SERVER = array();
		$_POST = array();
		$_GET = array();
		$_SESSION = array();
		$_COOKIE = array();
	
		writeToLog("Reading config.");
		$CONFFILE = "/etc/paw/paw.conf";
		$CONF = file_get_contents($CONFFILE);
		writeToLog("{$CONF}");
		$this->_SETTINGS = json_decode($CONF,true);

		$ADDR = $this->_SETTINGS['address'];
		$PORT = $this->_SETTINGS['port'];
		writeToLog("Going to bind to {$ADDR}:{$PORT}");
	
	
		set_time_limit(0);
		$this->SOCKET = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

		// Bind to all available addresses.
		writeToLog("BINDING");
		$RES = socket_bind($this->SOCKET, "0.0.0.0", $PORT);
		if(!$RES) {
			writeToLog("FAILED TO BIND!");
			if(function_exists("shutdown"))
				shutdown();
		}
		writeToLog("LISTENING");
		$RES = socket_listen($this->SOCKET, 30);

		$out = 'output: ';

		$HEAD = "HTTP/1.1 200 OK\r\n";
		$HEAD .= "Server: PAW\r\n";
		$HEAD .= "X-Powered-By: Your Mom\r\n";
		$HEAD .= "Connection: close\r\n";
		$HEAD .= "Content-length: __CONTENT_LENGTH__\r\n";
		$HEAD .= "Content-Type: text/html; charset=UTF-8\r\n";
		$HEAD .= "Cache-Control:public, max-age=0\r\n";
		$HEAD .= "Date: ".date('D, d M Y h:i:s T')."\r\n";

		$AHEADERS = headers_list();
		foreach($AHEADERS as $key => $sHeader)
		{
			$HEAD .= $sHeader."\r\n";
		}
		$HEAD .= "\r\n";

		$size = 1 * 1024 * 1024 * 5;
		while(true)
		{
			$out = '';
			//writeToLog("WAITING...");
			$this->SPAWN = socket_accept($this->SOCKET);
			$this->_REQUESTS += 1;
			
			$input = socket_read($this->SPAWN, $size);
			$input = str_replace("\r","",$input);
			
			// Now that we have all of the 
			// data that was submitted
			$data = explode("\n\n",$input);
			
			// Header processing
			$reqHeaders = explode("\n",trim($data[0]));
			//foreach($reqHeaders as $key => $sHeader)
			for($i = 1; $i < count($reqHeaders); $i++)
			{
				list($name, $value) = explode(":", $reqHeaders[$i], 2);
				$name = strtoupper(str_replace(array(" ","-"),array("_","_"),$name));
				$_SERVER[$name] = $value;
			}
			$_SERVER['SERVER_NAME'] = $_SERVER['HOST'];
			
			// Body processing
			$body = trim($data[1]);
			
			// Get the request method
			$_SERVER['REQUEST_METHOD'] = substr($reqHeaders[0],0,stripos($reqHeaders[0]," "));
			
			/*
			$out .= 'REQUEST_METHOD: ' . $_SERVER['REQUEST_METHOD'] . '<br>';
			$out .= 'Requests: ' . (string) $this->_REQUESTS . '<br>';
			$out .= 'HEADERS:<br>'.nl2br($data[0]).'<br>';
			*/
			//$out .= nl2br(print_r($_SERVER,true));
			
			$HEAD = str_replace('__CONTENT_LENGTH__',strlen($out),$HEAD);
			$put = $HEAD . $out;
			
			socket_write($this->SPAWN, $put, strlen($put));
			socket_close($this->SPAWN);
		}
	}
	
	public function getConfig()
	{
	
	}
}
?>
