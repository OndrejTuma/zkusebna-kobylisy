<?php

/**
 * Class handles SQL connection to MySQL database
 *
 * @version 1.0
 * @copyright Magnum 2013
 */

define("MYSQL_SERVER", "127.0.0.1");
define("MYSQL_NAME", "root");
define("MYSQL_PASSWORD", "");
define("MYSQL_DATABASE", "zkusebna_kobylisy");
//define("MYSQL_SERVER", "wm99.wedos.net");
//define("MYSQL_NAME", "a23736_zkusebn");
//define("MYSQL_PASSWORD", "A3NFvBwh");
//define("MYSQL_DATABASE", "d23736_zkusebn");

define("MYSQL_ENCODING","utf8");

class mysql {

	private $encoding;
	private $connection;
	private $errors;

	private static $instance;

	function __construct(){
		self::$instance = $this;

		$this->encoding = MYSQL_ENCODING;
		$this->errors = new logger(ZKUSEBNA_LOGS_URL);

		$this->connect();
	}

	/**
	 * tries to connect
	 */
	public function connect(){
		if (! $newConnection = mysql_connect(MYSQL_SERVER,MYSQL_NAME,MYSQL_PASSWORD)) {
			$this->errors->log("Nelze se připojit k databázovému serveru",3);
		}
		else {
			if (! mysql_select_db(MYSQL_DATABASE)) {
				$this->errors->log("Nelze vybrat databázi");
			}
			else {
				$this->connection = $newConnection;
				$this->set_charset($this->encoding);
			}
		}
	}

	/**
	 * @param string $charset
	 * @return bool
	 */
	public function set_charset($charset){
		if (!mysql_set_charset($charset)) {
			$this->errors->log("Nepodařilo se nastavit kódování pro komunikaci s databází");
			return false;
		}
		return true;
	}

	/**
	 * @return bool|int
	 */
	public function hasErrors() {
		return count($this->errors->getErrors()) ? count($this->errors->getErrors()) : false;
	}

	/**
	 * @param $pointer
	 * @return array|bool
	 */
	public function getError($pointer=-1) {
		return $pointer>=0 ? $this->errors->getError($pointer) : $this->errors->getErrors();
	}

	/**
	 * @param string $query
	 * @return resource
	 */
	public function query($query){
		return mysql_query($query);
	}

	/**
	 * @param resource $result
	 * @return array
	 */
	public function fetch_array($result){
		return mysql_fetch_array($result);
	}

	/**
	 * @param resource $result
	 * @return array
	 */
	public function fetch_assoc($result){
		return mysql_fetch_assoc($result);
	}

	/**
	 * @param resource $result
	 * @return array
	 */
	public function fetch_row($result){
		return mysql_fetch_assoc($result);
	}

	/**
	 * @param resource $result
	 * @param int $row
	 * @return string
	 */
	public function result($result, $row){
		return mysql_result($result, $row);
	}

	/**
	 * @return int
	 */
	public function insert_id(){
		return mysql_insert_id();
	}

	/**
	 * returns result of SQL query as an array
	 * @param string $query
	 * @return array
	 */
	public function field($query){
		$q = $this->query($query);
		$field = array();
		while($res = mysql_fetch_array($q)){
			$field[] = $res;
		}
		return $field;
	}

	/**
	 * returns result of SQL query as an associative array
	 * @param string $query
	 * @return array
	 */
	public function field_assoc($query){
		$q = $this->query($query);
		$field = array();
		while($res = mysql_fetch_assoc($q)){
			$field[] = $res;
		}
		return $field;
	}

	/**
	 * @param $query
	 * @return int
	 */
	public function num_rows($query) {
		return mysql_num_rows(mysql_query($query));
	}

	/**
	* close connection
	*/
	private function _close(){
		mysql_close($this->connection);
	}

	function __destruct(){
		//$this->_close();
	}

	/**
	 * @return mysql
	 */
	public static function getInstance() {
		if (!self::$instance instanceof self) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}
?>