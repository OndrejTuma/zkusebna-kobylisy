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
	 * Creates virtual reservations for repeated reservation
	 * @param $reservation array - reserved item (required fields: ['repeat_to','repeat_type','start','end'])
	 * @param $date_from
	 * @param $date_to
	 * @return array
	 */
	public static function getRepeatedReservation($reservation, $date_from, $date_to) {
		$period_types = Reservation::getRepeatUnixTypes();
		$repeat_to = $reservation["repeat_to"];
		$period = $reservation["repeat_type"];
		
		if (!isset($period_types[$period])) {
			return false;
		}
		$repeat_end = new DateTime($repeat_to);
		$date_begin = new DateTime($reservation["start"]);
		$date_end = new DateTime($reservation["end"]);
		$interval = new DateInterval($period_types[$period]);
		$date_sub = $date_begin->diff($date_end);

		//$date_period = new DatePeriod($date_begin, $interval, $repeat_end, DatePeriod::EXCLUDE_START_DATE);
		$date_period = new DatePeriod($date_begin, $interval, $repeat_end);

		$repeated_reservations = array();
		$i = 999;
//		var_dump("======= {$date_from}...{$date_to}");
		foreach ($date_period as $date) {
			$start_date = Zkusebna::_parseDate($date->format('Y-m-d H:i:s'));
			$end_date = Zkusebna::_parseDate($date->add($date_sub)->format('Y-m-d H:i:s'));
//			var_dump(($start_date > $date_from).", ".($end_date > $date_from).", ".($start_date < $date_to).", ".($end_date < $date_to)."...............{$start_date}...{$end_date}");
			if(($start_date > $date_from || $end_date > $date_from) && ($start_date < $date_to || $end_date < $date_to)) {
//				var_dump("\\/ ================= VYHOVUJE ================= \\/");
				$new_reservation = $reservation;
				$new_reservation["reservationID"] = $new_reservation["reservationID"] * $i;	//fake new reservation ID
				$new_reservation["start"] = $start_date;
				$new_reservation["end"] = $end_date;
				array_push($repeated_reservations, $new_reservation);
			}
			$i++;
//			var_dump($start_date, $end_date, $date_from, $date_to, $reservation['id'], $date_begin->format('Y-m-d H:i:s'), $repeat_end->format('Y-m-d H:i:s'), $period_types[$period], "===========================================================");
		}

		return $repeated_reservations;
	}
	/**
	 * Resize an image and keep the proportions
	 * @author Allison Beckwith <allison@planetargon.com>
	 * @param string $filename
	 * @param integer $max_width
	 * @param integer $max_height
	 * @return image
	 */
	public static function resizeImage($filename, $max_width, $max_height)
	{
		list($orig_width, $orig_height) = getimagesize($filename);

		$width = $orig_width;
		$height = $orig_height;

		# taller
		if ($height > $max_height) {
			$width = ($max_height / $height) * $width;
			$height = $max_height;
		}

		# wider
		if ($width > $max_width) {
			$height = ($max_width / $width) * $height;
			$width = $max_width;
		}

		$image_p = imagecreatetruecolor($width, $height);

		$image = imagecreatefromjpeg($filename);

		imagecopyresampled($image_p, $image, 0, 0, 0, 0,
			$width, $height, $orig_width, $orig_height);

		return $image_p;
	}
	public static function getFormattedAccountNumber() {
		if (defined('ZKUSEBNA_ACCOUNT_NUMBER') && defined('ZKUSEBNA_BANK_CODE')) {
			return ZKUSEBNA_ACCOUNT_NUMBER . ' / ' . ZKUSEBNA_BANK_CODE;
		}
		return '';
	}
	/**
	 * Returns src to QR image
	 * @param int $amount
	 * @param string $message
	 * @return string
	 */
	public static function getPaymentQRCodeSrc($amount, $message) {
		if (defined('ZKUSEBNA_ACCOUNT_NUMBER') && defined('ZKUSEBNA_BANK_CODE') && defined('ZKUSEBNA_CURRENCY')) {
			return "http://api.paylibo.com/paylibo/generator/czech/image?accountNumber=".ZKUSEBNA_ACCOUNT_NUMBER."&bankCode=".ZKUSEBNA_BANK_CODE."&amount=".(int)$amount."&currency=".ZKUSEBNA_CURRENCY."&message=" . urlencode($message);
		}
		return '';
	}
	public static function sendMail($recipient, $subject, $body, $from = null) {
		$mail = new PHPMailer();
		$mail->setFrom(
			isset($from['email']) ? $from['email'] : Admin::getEmail(),
			isset($from['name']) ? $from['name'] : ZKUSEBNA_MAILING_FROM
		);
		$mail->addAddress($recipient);
		$mail->isHTML(true);
		$mail->Subject = $subject;
		$mail->Body    = $body;
		$mail->AltBody = strip_tags($body);

		if(!$mail->send()) {
			$textLog = new logger(ZKUSEBNA_LOGS_URL);
			$textLog->log("Nepodařilo se odeslat email. Adresát {$recipient}. Chyba: " . $mail->ErrorInfo);
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
	 * @param $table string - table alias
	 * @param $item_id int - id of row to edit
	 * @param $column string - name of column to edit
	 * @param $val string - new value
	 * @return resource
	 */
	public function updateItem($table, $item_id, $column, $val) {

		if (!isset($this->table_names[$table])) return false;

		$query = "UPDATE {$this->table_names[$table]} SET {$column} = '{$val}' WHERE id = $item_id";
		return $this->sql->query($query);

	}

	/**
	 * delete any row from $this->table_names list of tables (pretty risky, i know, but saves time)
	 * @param $table string - table alias
	 * @param $item_id int - id of row to delete
	 * @return resource
	 */
	public function deleteItem($table, $item_id) {

		if (!isset($this->table_names[$table])) return false;

		$query = "DELETE FROM {$this->table_names[$table]} WHERE id = $item_id";
		return $this->sql->query($query);

	}

	/**
	 * toggle any column (0/1) from $this->table_names list of tables (pretty risky, i know, but saves time)
	 * @param $table string - table alias
	 * @param $item_id int - id of row to toggle
	 * @return mixed result after toggle (0/1) or false
	 */
	public function toggleItem($table, $item_id, $column) {

		if (!isset($this->table_names[$table])) return false;

		$query = "SELECT {$column} FROM {$this->table_names[$table]} WHERE id = $item_id";
		$res = $this->sql->field_assoc($query);
		$toggleResult = (int)$res[0][$column] === 0 ? 1 : 0;

		$query = "UPDATE {$this->table_names[$table]} SET {$column} = {$toggleResult} WHERE id = {$item_id}";

		return $this->sql->query($query) ? $toggleResult : false;
	}

	/**
	 * changes reservation purpose
	 * @param $reservationId int - id of a reservation to change
	 * @param $purposeId int - id a new purpose to set
	 * @return mixed result after attemp to change purpose (0/1) or false
	 */
	public function changePurpose($reservationId, $purposeId) {

		$query = "SELECT id FROM {$this->table_names["purpose"]} WHERE id = {$purposeId}";
		$res = $this->sql->field_assoc($query);

		if (!isset($res[0]['id'])) return false;

		$query = "UPDATE {$this->table_names["reservations"]} SET purpose={$purposeId} WHERE id = {$reservationId}";
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