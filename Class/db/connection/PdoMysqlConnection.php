<?php

Class PdoMysqlConnection {
	private $serverHost;
	
	private $serverPort;
	
	private $serverLogin;
	
	private $serverPassword;
	
	private $serverDatabase;
	
	private $connection;

	public function connect() {	
		try {
		    $this->connection = new PDO("mysql:host=$serverHost:$serverPort;;dbname=$serverDatabase", $serverLogin, $serverPassword);
		    /*** echo a message saying we have connected ***/
		    echo 'Connected to database';
	    }
		catch(PDOException $e)
	    {
	        echo $e->getMessage();
	    }
	}

}