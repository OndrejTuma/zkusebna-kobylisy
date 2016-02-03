<?php
/**
 * Class logger
 */
class logger {

	private $outputFile;
	protected $errors;

	function __construct($logs_url) {
		$this->outputFile = $logs_url . "error.log";
		$this->errors = array();
	}

	/**
	 * @param $message
	 * @param int $priority
	 */
	public function log($message,$user = "",$priority = 1) {
		$this->errors[]=$message;
		$logItem = "=========================================================\n\n".
			"Priority: \t$priority\n"."Date: \t".date("j.n.Y H:i:s")."\n"."Message: \t\"$message\"\n".($user ? "Uživatel: \t$user\n" : "")."\n";
		try {
			file_put_contents($this->outputFile,$logItem,FILE_APPEND);
		}
		catch (Exception $e) {
			echo "Error occured: ". $e->getMessage()."\n";
		}
	}

	/**
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * @param $pointer
	 * @return bool
	 */
	public function getError($pointer) {
		return isset($this->errors[$pointer]) ? $this->errors[$pointer] : false;
	}
}

?>