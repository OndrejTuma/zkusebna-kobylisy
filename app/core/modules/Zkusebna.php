<?php

class Zkusebna {

	public $table_names = array(
		"reservations" => "zkusebna_reservations",
		"items" => "zkusebna_items",
		"community" => "zkusebna_community",
		"r-i" => "zkusebna_reserved_items",
		"purpose" => "zkusebna_reservation_purpose",
		"admin" => "zkusebna_admin",
		"r-r" => "zkusebna_reservation_repeat"
	);

	function __construct() {
		$this->sql = new mysql();
	}

	/**
	 * $repeat_from parameter is considered equal to $date_from
	 * @param $event array - reserved item
	 * @param $repeat_from
	 * @param $repeat_to
	 * @param $period ['WEEKLY', 'WEEKLY_2', 'MONTHLY'] - perioda opakování
	 * @param $date_to
	 * @return DatePeriod
	 */
	public static function getRepeatedReservation($event) {
		$period_types = Reservation::getRepeatUnixTypes();
		$repeat_to = $event["repeat_to"];
		$period = $event["repeat_type"];
		
		if (!isset($period_types[$period])) {
			return false;
		}

		$repeat_end = new DateTime($repeat_to);
		$date_begin = new DateTime($event["start"]);
		$date_end = new DateTime($event["end"]);
		$interval = new DateInterval($period_types[$period]);
		$date_sub = $date_begin->diff($date_end);

		$date_period = new DatePeriod($date_begin, $interval, $repeat_end, DatePeriod::EXCLUDE_START_DATE);

		$repeated_reservations = array();
		foreach ($date_period as $date) {
			$new_event = $event;
			$new_event["start"] = Zkusebna::_parseDate($date->format('Y-m-d H:i:s'));
			$new_event["end"] = Zkusebna::_parseDate($date->add($date_sub)->format('Y-m-d H:i:s'));
			array_push($repeated_reservations, $new_event);
		}

		return $repeated_reservations;
	}
	public static function sendMail($recipient, $subject, $body) {
		$mail = new PHPMailer();
		$mail->setFrom(Admin::getEmail(), ZKUSEBNA_MAILING_FROM);
		$mail->addAddress($recipient);
		$mail->isHTML(true);
		$mail->Subject = $subject;
		$mail->Body    = $body;
		$mail->AltBody = strip_tags($body);

		if(!$mail->send()) {
			$textLog = new logger(ZKUSEBNA_LOGS_URL);
			$textLog->log("Nepodařilo se odeslat email: " . $mail->ErrorInfo);
		}
	}

	/**
	 * parse date to SQL DATE format
	 * @param $date string
	 * @return bool|string
	 */
	public static function _parseDate($date) {

		return date("Y-m-d H:i:s", strtotime($date));

	}

	/**
	 * Parse date from MySQL table to human readable format
	 * @param $date
	 * @return bool|string
	 */
	public static function parseSQLDate($date) {

		return date("j.n. G:i", strtotime($date));

	}

	/**
	 * Gets information about item
	 * @param $item_id int - item id
	 * @return array
	 */
	public function getItemInfo($item_id) {

		$query = "SELECT * FROM {$this->table_names["items"]} WHERE id = {$item_id}";
		return $this->sql->fetch_row($this->sql->query($query));

	}

	/**
	 * Gets information about items
	 * @param $item_ids array - array of items ids
	 * @return array
	 */
	public function getItemsInfo($item_ids) {

		$query = "SELECT * FROM {$this->table_names["items"]} WHERE id IN ('" . implode("','", $item_ids) . "')";
		return $this->sql->field_assoc($query);

	}

	/**
	 * update item's info
	 * @param $item_id int - item id
	 * @param $changeSet array(column => val) - array of changes to be made
	 * @return resource
	 */
	public function updateItem($item_id, $column, $val) {

		$query = "UPDATE {$this->table_names["items"]} SET {$column} = '{$val}' WHERE id = $item_id";
		return $this->sql->query($query);

	}



	protected function _isReserved($item_id, $date_from, $date_to) {

		$date_from = self::_parseDate($date_from);
		$date_to = self::_parseDate($date_to);

		$query = "
SELECT i.name FROM {$this->table_names["reservations"]} AS r
LEFT JOIN {$this->table_names["r-i"]} AS ri
ON r.id = ri.reservation_id
LEFT JOIN {$this->table_names["items"]} AS i
ON ri.item_id = i.id
WHERE ri.item_id = {$item_id} AND (r.date_from > '{$date_from}' OR r.date_to >= '{$date_from}') AND (r.date_from <= '{$date_to}' OR r.date_to < '{$date_to}')";
		echo $query;
		return $this->sql->num_rows($query) != 0;

	}



}


?>