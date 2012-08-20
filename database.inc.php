<?php

class Database {

	private $server;

	public $connectionStart;

	public $numberOfQueries;

	public $lastQuery;

	public $link;

	private $rows;
	
	public $inserts = array();
	
	private $queries = array();

	function __construct($user='root', $pass='', $type="normal", $database_name=false) {
		if ($type=="normal") {
			$this->server = mysql_connect('localhost', $user, $pass);
		} else {
			$this->server = mysql_pconnect('localhost', $user, $pass);
		}
		if (!$this->server) {
			die('Could not connect: ' . mysql_error());
		}
		if($database_name!==false) {
			$database = mysql_select_db($database_name, $this->server);
			if (!$database) {
				die('Could not connect to database: ' . mysql_error());
			}
		}
		$this->numberOfQueries  = 0;
		$this->lastQuery = NULL;
		$this->debug = NULL;
		$this->connectionStart = $this->getMicroTime();
		return 1;
	}

	function _chooseDB($name) {
		$database = mysql_select_db($name, $this->server);
		if (!$database) {
			die('Could not connect to database: ' . mysql_error());
		}
	}

	function _raw($query) {
		$this->lastQuery = $query;
		$this->queries[] = $query;
		if (mysql_query($query)) {
			return 1;
		} else {
			return 0;
		}

	}

	function _one($query) {
		$args = array();
		$args = func_get_args();
		$query = $this->checkvalues($args);
		$this->lastQuery = $query;
		$this->queries[] = $query;
		$this->numberOfQueries++;

		if ($query2 = mysql_query($query)) {
			$row = @mysql_fetch_array($query2);
			return $row[0];
		} else {
			return 0;
		}
	}
	function _array($query) {
		$args = array();
		$args = func_get_args();
		$query = $this->checkvalues($args);
		$this->lastQuery = $query;
		$this->queries[] = $query;
		$this->numberOfQueries++;

		if ($query2 = mysql_query($query)) {
			$row = @mysql_fetch_array($query2);
			return $row;
		} else {
			return 0;
		}
	}

	function _one_assoc($query) {
		$args = array();
		$args = func_get_args();
		$query = $this->checkvalues($args);
		$this->lastQuery = $query;
		$this->queries[] = $query;
		$this->numberOfQueries++;
		if ($result = mysql_query($query)) {
			$row = mysql_fetch_assoc($result);
			return $row;
		} else {
			return 0;
		}
	}

	function _assoc($query) {
		$args = array();
		$args = func_get_args();
		$query = $this->checkvalues($args);
		$this->lastQuery = $query;
		$this->queries[] = $query;
		$this->numberOfQueries++;
		if (!$result = mysql_query($query)) {
			return 0;
		}
		$i=0;
		$table=array();
		while($table[] = mysql_fetch_assoc($result)) {
			$i++;
		}
		$this->rows = mysql_num_rows($result);

		$table = array_slice($table, 0,-1);
		$rows=$i;

		return $table;
	}

	function _assoc_into($query, &$returnRow, $vars="") {
		//Never recreate $_globalResult;
		global $_globalResult;
		
		$args = func_get_args();
		unset($args[1]);

		if (!$_globalResult) {
			$this->lastQuery = $query;
			$this->queries[] = $query;
			$query = $this->checkvalues($args);
			$this->lastQuery = $query;
			$this->numberOfQueries++;
			if (!$_globalResult = mysql_query($query)) {
				return 0;
			}
		}
		$i=0;
		$table=array();
		$this->rows = mysql_num_rows($_globalResult);
		while($returnRow = mysql_fetch_assoc($_globalResult)) {
			return 1;
		}
		unset($returnRow);
		$_globalResult="";
		return 0;
	}

	function _insert($query) {
		$args = array();
		$args = func_get_args();
		$query = $this->checkvalues($args);
		$this->lastQuery = $query;
		$this->queries[] = $query;
		$this->numberOfQueries++;
		if (!$result = mysql_query($query)) {
			return 0;
		} else {
			return (mysql_insert_id()) ? mysql_insert_id() : 1;
		}
	}

	function checkvalues($values) {
		$i = 0;

		$query = $values[0];

		preg_match_all("/%./", $query, $percents);
		
		//Delete the query from the values array;
		$values = array_reverse($values);
		$values = array_slice($values, 0,-1);
		$values = array_reverse($values);

		foreach ($percents[0] as $percent) {

			$data = $values[$i];

			if ($percent == "%s" && is_string($data)) {

				$data = mysql_real_escape_string($data);

				$data = str_replace("'", "\'", $data);

				$data = "'".$data."'";

			} else if ($percent == "%i" && is_numeric($data)) {

				//Is an integer

			} else if ($percent == "%d") {

				$data = strtotime($data);
				
			} else if ($percent == "%l") {

				//like
				
			} else if ($percent == "%p") {

				$data = "%";

			}  else {

				return 0;

			}


			$query = preg_replace("/".$percent."/", $data, $query, 1);

			$i++;
		}

		return $query;
	}

	function insertID() {
		return mysql_insert_id($this->server);

	}

	function getTime() {
		return round(($this->getMicroTime() - $this->connectionStart) * 1000) / 1000;
	}

	function getMicroTime() {
		list($msec, $sec) = explode(' ', microtime());
		return floor($sec / 1000) + $msec;
	}

	function getRows() {
		return mysql_num_rows($this->server);
	}

	function __destruct() {
		mysql_close();
	}
}
?>
