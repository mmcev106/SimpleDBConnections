<?php 

abstract class AbstractDBConnection {
	
	var $hostname; 
	var $username;
	var $password;

	public function __construct($hostname, $username, $password){
		$this->hostname = $hostname;
		$this->username = $username;
		$this->password = $password;
	}
}