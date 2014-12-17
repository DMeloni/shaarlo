<?php

Class PdoMysqlConnection {
	private $serverPath;
	
	private $serverDatabase;
	
	private $connection;

	public function connect() {
		try {
			/*** connect to SQLite database ***/
			$this->connection = new PDO("sqlite:/$serverPath/${serverDatabase}.sdb");
			/*** echo a message saying we have connected ***/
		    echo 'Connected to database';
	    }
		catch(PDOException $e)
		{
			echo $e->getMessage();
		}
	}
}