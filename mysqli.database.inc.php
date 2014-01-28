<?php
/**
* Database class for mysqli
* Provides security and quick functionality to your project
* @Copyright Adam Dougherty <adamiandougherty@gmail.com>
* @License https://github.com/aJamDonut/bSimple-PHP-mysqli-DB-Class/wiki/Donut-License
* @Website ajamdonut.com
* @Repo https://github.com/aJamDonut/bSimple-PHP-mysqli-DB-Class
*/

class Database {

	private $server;

	public $connectionStart;

	public $numberOfQueries;

	public $lastQuery;

	public $link;

	private $rows;

	public $inserts = array();

	private $queries = array();

	function __construct($user='root', $pass='', $host="localhost", $database_name="mysql", port=3389, $socket="") {
		if ($type=="normal") {
			$this->server = new mysqli($host, $user, $pass, $database_name, $port, $socket);
		}
		if ($mysqli->connect_error) {
		    die("Connect Error ({$mysqli->connect_errno}) {$mysqli->connect_error}");
		}
		$this->numberOfQueries  = 0;
		$this->lastQuery = NULL;
		$this->debug = NULL;
		$this->connectionStart = $this->getMicroTime();
		return 1;
	}

	function _chooseDB($name) {
		$database = mysqli_select_db($name, $this->server);
		if (!$database) {
			die('Could not connect to database: ' . mysqli_error());
		}
	}

	function _raw($query) {
		$this->lastQuery = $query;
		$this->queries[] = $query;
		if (mysqli_query($query)) {
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

		if ($query2 = mysqli_query($query)) {
			$row = @mysqli_fetch_array($query2);
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

		if ($query2 = mysqli_query($query)) {
			$row = @mysqli_fetch_array($query2);
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
		if ($result = mysqli_query($query)) {
			$row = mysqli_fetch_assoc($result);
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
		if (!$result = mysqli_query($query)) {
			return 0;
		}
		$i=0;
		$table=array();
		while($table[] = mysqli_fetch_assoc($result)) {
			$i++;
		}
		$this->rows = mysqli_num_rows($result);

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
			if (!$_globalResult = mysqli_query($query)) {
				return 0;
			}
		}
		$i=0;
		$table=array();
		$this->rows = mysqli_num_rows($_globalResult);
		while($returnRow = mysqli_fetch_assoc($_globalResult)) {
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
		if (!$result = mysqli_query($query)) {
			return 0;
		} else {
			return (mysqli_insert_id()) ? mysqli_insert_id() : 1;
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

				$data = mysqli_real_escape_string($data);

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
		return mysqli_insert_id($this->server);

	}

	function getTime() {
		return round(($this->getMicroTime() - $this->connectionStart) * 1000) / 1000;
	}

	function getMicroTime() {
		list($msec, $sec) = explode(' ', microtime());
		return floor($sec / 1000) + $msec;
	}

	function getRows() {
		return mysqli_num_rows($this->server);
	}

	function __destruct() {
		$this->server->close();
	}
}
?>