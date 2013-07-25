<?php 

require_once('AbstractDBConnection.php');

class MSSQLConnection extends AbstractDBConnection {
	
	var $hostname; 
	var $username;
	var $password;
	
	public function query($query, $result_type=MSSQL_BOTH){
		$db = mssql_pconnect($this->hostname, $this->username, $this->password);
		$ret = @mssql_query($query);
		
		if($ret == false){
			$errorMessage = mssql_get_last_message();
		
			if(empty($errorMessage)){
				/**
				 * Assume the database connection is stale, close it, and reconnect.
				 * The only two times mssql_get_last_message() is empty is when the connecting is stale, or a timeout has occurred.
				 */
				logToFile("Closing a stale DB connection to $this->hostname.");
				mssql_close($db);
					
				$db = mssql_pconnect($this->hostname, $this->username, $this->password, true);
				$ret = mssql_query($query);
			}
		
			if($ret == false){
				throw new Exception("An error occurred while executing the query: ($query)<br/>ERROR: ". mssql_get_last_message());
			}
		}
		
		if($ret === true){
			// The result set is empty, return an empty array.
			return Array();
		}
		else{
			$items = $this->mssqlResultToArray($ret, $result_type);
		}
		
		return $items;
	}
	
	private function mssqlResultToArray($result, $result_type=MSSQL_BOTH){
		$array = array();
	
		while($row = mssql_fetch_array($result, $result_type)){
			array_push($array, $row);
		}
	
		return $array;
	}
}