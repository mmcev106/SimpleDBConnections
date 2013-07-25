<?php 

require_once('AbstractDBConnection.php');

class MySQLConnection extends AbstractDBConnection{
	
	var $dbName;
	var $persistentConnection = true;
	var $pdo;
	
	
	public function __construct($hostname, $username, $password, $dbName){
		parent::__construct($hostname, $username, $password);
		$this->dbName = $dbName;
	}
	
	public function getPDO() {
		if(!isset($this->pdo)){
			$dsn = 'mysql:dbname=' . $this->dbName . ';host=' . $this->hostname . ';';
			$options = array(PDO::ATTR_PERSISTENT => $this->isPersistentConnection());
			$this->pdo = new PDO($dsn, $this->username, $this->password, $options);
			$this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
		}
	
		return $this->pdo;
	}
	
	public function closePDO() {
		$this->pdo = null;
	}
	
	public function query($query, $parameters=array()){
		if(!is_array($parameters)){
			//Assume that there is only one parameter in the query, and it has been passed in directly instead of in an array.
			$parameters = array($parameters);
		}
		
		$adjustedArguments = $this->adjustQueryArguments($query, $parameters);
		$query = $adjustedArguments[0];
		$parameters = $adjustedArguments[1];
		
		$stmt = $this->getPDO()->prepare($query);
		
		if(!$stmt->execute($parameters)){
			$errorInfo = $stmt->errorInfo();
			$errorMessage = $errorInfo[2];
			throw new Exception("An error occurred while executing the following query: $query <br/>With the following parameters: " . print_r($parameters, true) . "<br/>Error: $errorMessage");
		}
		
		return $stmt;
	}
	
	public function insert($tableName, $parameters){
		return $this->insertOrUpdate($tableName, $parameters, false);
	}
	
	/**
	 * This function was based on the pdo_insert() function here:
	 * http://stackoverflow.com/questions/4587988/how-to-pass-an-array-of-rows-to-pdo-to-insert-them
	 *
	 * It seems like PDO would have this feature built in.....
	 */
	public function insertOrUpdate($table, $parameters=array(), $orUpdate=true){
		if (!is_array($parameters) || !count($parameters)){
			throw new Exception("Invalid parameters: " . print_r($parameters, true));
		}
	
		$bind = ':'.implode(',:', array_keys($parameters));
		$sql  = 'insert into '.$table.' (`'.implode('`,`', array_keys($parameters)).'`) '.
				'values ('.$bind.')';
	
		if($orUpdate){
			$parameterUpdateSQL = '';
			foreach($parameters as $name=>$value){
	
				if(!empty($parameterUpdateSQL)){
					$parameterUpdateSQL .= ',';
				}
	
				$parameterUpdateSQL .= "`$name`=:$name";
			}
	
			$sql .= " on duplicate key update $parameterUpdateSQL";
		}
		
		$stmt = $this->query($sql, $parameters);
	
		/**
		 * An update will only count as changing rows if the update changed a value,
		 * so don't throw an exception on update if the row count didn't change.
		 */
		if(!$orUpdate && $stmt->rowCount() != 1){
			throw new Exception("The following insert did not affect the expected number of rows: $sql <br/>\nWith Parameters: " . print_r($parameters, true));
		}
	
		return $stmt;
	}
	
	/**
	 * Adjust the query parameters to support array arguments
	 */
	public function adjustQueryArguments($query, $parameters){
		$newParameters = array();
		foreach($parameters as $key=>$var){
			if(is_array($var)){
				$paramList = "";
				foreach($var as $index=>$arrayVar){
					$arrayParamName = "$key$index";
	
					if(!empty($paramList)){
						$paramList .= ',';
					}
	
					$paramList .= ":$arrayParamName";
					$newParameters[$arrayParamName] = $arrayVar;
				}
	
				$query = str_replace(":$key", "($paramList)", $query);
	
			}else{
				$newParameters[$key] = $var;
			}
		}
	
		return array($query, $newParameters);
	}

	public function isPersistentConnection()
	{
	    return $this->persistentConnection;
	}

	public function setPersistentConnection($persistentConnection)
	{
		if(isset($this->pdo)){
			throw new Exception("This connection has already been initialized, so it is not possible to change whether or not it is persistent.");
		}
		
	    $this->persistentConnection = $persistentConnection;
	}
}