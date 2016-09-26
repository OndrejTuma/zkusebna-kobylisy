<?php

class Reservation extends Zkusebna {

	private $date_from, $date_to, $who;
	public static $repeatTypes = array(
		"weekly" => "WEEKLY",
		"weekly2" => "WEEKLY_2",
		"monthly" => "MONTHLY"
	);
	public static $repeatUnixTypes = array(
		"WEEKLY" => "P7D",
		"WEEKLY_2" => "P14D",
		"MONTHLY" => "P1M"
	);

	function __construct() {
		parent::__construct();
		$this->sql_table = new sql_table($this->table_names["reservations"]);
		$this->date_from = "";
		$this->date_to = "";
		$this->who = 0;
		$this->id = 0;
		$this->purpose_id = 0;
	}

	public static function getRepeatTypes() {
		return self::$repeatTypes;
	}
	public static function getRepeatUnixTypes() {
		return self::$repeatUnixTypes;
	}

	public function createReservation($params) {
		$this->reservation_name = $params["reservation_name"];
		$this->date_from = $params["date_from"];
		$this->date_to = $params["date_to"];
		$this->who = $params["who"];
		$this->purpose_id = $params["purpose_id"];
		$this->id = $this->_createReservationIfNotExists($params);
	}

	/**
	 * Looks for reservation and if it doesn't exist, it creates it. Then returns its id. Otherwise id of existing reservation is returned
	 * @return int - reservation id
	 */
	private function _createReservationIfNotExists($params) {

		$query = "SELECT id FROM {$this->table_names["reservations"]} WHERE who = '{$this->who}' AND date_from = '{$this->date_from}' AND date_to = '{$this->date_to}'";
		$reservation = $this->sql->fetch_row($this->sql->query($query));

		if (!$reservation) {
			if (isset($params["repeat_type"])) {
				$query = "INSERT INTO {$this->table_names["r-r"]} (type, repeat_from, repeat_to) VALUES ('{$params["repeat_type"]}', '{$params["repeat_from"]}', '{$params["repeat_to"]}')";
				$this->sql->query($query);
				$query = "INSERT INTO {$this->table_names["reservations"]} (name, date_from, date_to, who, purpose, repetition, approved) VALUES ('{$this->reservation_name}', '{$this->date_from}', '{$this->date_to}', '{$this->who}', {$this->purpose_id}, " . $this->sql->insert_id() . ", 1)";
				$this->sql->query($query);
			}
			else {
				$query = "INSERT INTO {$this->table_names["reservations"]} (name, date_from, date_to, who, purpose) VALUES ('{$this->reservation_name}', '{$this->date_from}', '{$this->date_to}', '{$this->who}', {$this->purpose_id})";
				$this->sql->query($query);
			}
			return $this->sql->insert_id();
		}

		return (int)$reservation['id'];

	}

	/**
	 * adds items to reservation
	 * @param $id array[int] - array of item ids to add
	 * @return resource
	 */
	public function addItems($ids) {

		$query = "INSERT INTO {$this->table_names["r-i"]} (item_id, reservation_id) VALUES ";
		foreach ($ids as $id) {
			$query .= "(" . (int)$id . ", {$this->id}),";
		}
		$query = substr($query, 0, -1);
		return $this->sql->query($query);
	}

	/**
	 * destroy all reserved items and reservation itself
	 */
	public function cancel() {
		// delete reserved items
		$query = "DELETE FROM {$this->table_names["r-i"]} WHERE reservation_id = {$this->id}";
		$this->sql->query($query);
		// delete reservation
		$query = "DELETE FROM {$this->table_names["reservations"]} WHERE id = {$this->id}";
		$this->sql->query($query);
	}

	/**
	 * gets reservation ID, if it exists
	 * @return null
	 */
	public function getID() {
		return $this->id;
	}

	public function getDiscount($reservationId = 0) {
		$query = "
SELECT discount FROM {$this->table_names["reservations"]} as r
LEFT JOIN {$this->table_names["purpose"]} AS p ON p.id = r.purpose
WHERE r.id = ".($reservationId ? $reservationId : $this->id);
		$discount = $this->sql->field_assoc($query);
		return isset($discount[0]) ? $discount[0]['discount'] : false;
	}

	public function getReservationById($id) {
		$query = "
SELECT r.name as reservation_name, c.name as name, email, phone, date_from, date_to, repetition FROM {$this->table_names["reservations"]} as r
LEFT JOIN {$this->table_names["community"]} AS c ON c.id = r.who
WHERE r.id = {$id}";
		$reservation = $this->sql->field_assoc($query);
		return isset($reservation[0]) ? $reservation[0] : false;
	}

	public function getReservationItems($reservation_id) {
		$query = "
SELECT item_id FROM {$this->table_names["reservations"]} as r
LEFT JOIN {$this->table_names["r-i"]} AS ri ON ri.reservation_id = r.id
WHERE r.id = {$reservation_id}";
		$q = $this->sql->query($query);
		$field = array();
		while($res = $this->sql->fetch_assoc($q)){
			$field[] = $res['item_id'];
		}
		return $field;
	}

	/**
	 * Gets reserved items in specific date range
	 * @param $date_from
	 * @param $date_to
	 * @return array
	 */
	public function getReservedItems($date_from, $date_to) {

		$date_from = parent::_parseDate($date_from);
		$date_to = parent::_parseDate($date_to);

		$query = "
SELECT i.id as id, i.name as itemName, i.active as active, c.name as name, r.name as reservation_name, image as img, price, category, parent_id, reservable, email, date_from as start, date_to as end, r.id as reservationID FROM {$this->table_names["reservations"]} as r
LEFT JOIN {$this->table_names["r-i"]} AS ri ON r.id = ri.reservation_id
LEFT JOIN {$this->table_names["items"]} AS i ON ri.item_id = i.id
LEFT JOIN {$this->table_names["community"]} AS c ON c.id = r.who
WHERE repetition IS NULL AND (r.date_from > '{$date_from}' OR r.date_to > '{$date_from}') AND (r.date_from < '{$date_to}' OR r.date_to < '{$date_to}')
ORDER BY category, itemName
";
		$reserved_items = $this->sql->field_assoc($query);
		$repeated_reserved_items = $this->_getRepeatedReservedItems($date_from, $date_to);

		return array_merge($reserved_items, $repeated_reserved_items);
	}

	private function _getRepeatedReservedItems($date_from, $date_to) {
		//gets all repeated reservations from current date range
		$query = "
SELECT i.id as id, i.name as itemName, i.active as active, c.name as name, r.name as reservation_name, image as img, price, category, parent_id, reservable, email, date_from as start, date_to as end, repeat_from, repeat_to, rr.type as repeat_type, r.id as reservationID FROM {$this->table_names["reservations"]} as r
LEFT JOIN {$this->table_names["r-i"]} AS ri ON r.id = ri.reservation_id
LEFT JOIN {$this->table_names["items"]} AS i ON ri.item_id = i.id
LEFT JOIN {$this->table_names["community"]} AS c ON c.id = r.who
LEFT JOIN {$this->table_names["r-r"]} AS rr ON rr.id = r.repetition
WHERE repetition > 0
";
//		var_dump($query);
		$output = array();
		foreach ($this->sql->field_assoc($query) as $reservation) {
//			var_dump($this->getRepeatedReservation($reservation, $date_from, $date_to));
			$output = array_merge($output, $this->getRepeatedReservation($reservation, $date_from, $date_to));
		}
		return $output;
	}

	/**
	 * checks, if set of item ids has collision within reservation date range and returns collided items
	 * @param $ids array(int)
	 * @return array(int) - array of items with collision
	 */
	public function hasCollision($ids) {
		$collisions = array();
		$reservedItems = $this->getReservedItems($this->date_from, $this->date_to);
		foreach($reservedItems as $item) {
			if (in_array($item['id'], $ids)) $collisions[] = $item;
		}
		return $collisions;
	}

	/**
	 * removes items from reservation
	 * @param $ids array[int] - array of item ids to remove
	 * @return resource
	 */
	public function removeItems($ids) {
		$query = "DELETE FROM {$this->table_names["r-i"]} WHERE reservation_id = {$this->id} AND item_id IN ('" . implode("','", $ids) . "')";
		return $this->sql->query($query);
	}

	/**
	 * update reservation's date range
	 * @return resource
	 */
	public function updateReservation($date_from, $date_to) {

		$this->date_from = parent::_parseDate($date_from);
		$this->date_to = parent::_parseDate($date_to);

		$query = "UPDATE {$this->table_names["reservations"]} SET date_from = '{$this->date_from}', date_to = '{$this->date_to}' WHERE id = {$this->id}";

		return $this->sql->query($query);
	}

	/**
	 * return credentials of user who has reserved wanted item at specific time range
	 * @param $item_id int - wanted item's id
	 * @param $date_from
	 * @param $date_to
	 * @returns array - user credentials
	 */
	public function whoReservedThis($item_id, $date_from, $date_to) {
		$date_from = parent::_parseDate($date_from);
		$date_to = parent::_parseDate($date_to);

		$query = "
SELECT c.name as name, phone, email, date_from, date_to
FROM {$this->table_names["reservations"]} as r
LEFT JOIN {$this->table_names["r-i"]} as ri ON ri.reservation_id = r.id
LEFT JOIN {$this->table_names["items"]} as i ON i.id = ri.item_id
LEFT JOIN {$this->table_names["community"]} as c ON c.id = r.who
WHERE i.id = {$item_id} AND (r.date_from > '{$date_from}' OR r.date_to > '{$date_from}') AND (r.date_from < '{$date_to}' OR r.date_to < '{$date_to}')";

		return $this->sql->fetch_row($this->sql->query($query));
	}

}


?>