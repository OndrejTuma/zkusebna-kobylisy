<?php
/**
 * Class sql_logger
 */
class sql_logger extends logger {

	private static $tableName = "logy";

	function __construct() {
		parent::__construct();
	}

	/**
	 * @param $message
	 */
	public function log($message,$userId = "",$priority = 1) {
		$this->errors[]=$message;
		mysql::getInstance()->query("
			INSERT INTO ".vselek::getFullSqlTableName(self::$tableName)." (zprava,uzivatel) VALUES (
				'".mysql_real_escape_string($message)."',
				'".($userId!="" ? intval($userId) : "")."'
		)");
	}

}

?>