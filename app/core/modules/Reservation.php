<?php

class Reservation extends Zkusebna {

	private $date_from, $date_to, $who;

	function __construct($date_from, $date_to, $who) {
		parent::__construct();
		$this->date_from = $this->_parseDate($date_from);
		$this->date_to = $this->_parseDate($date_to);
		$this->who = $who;
		$this->id = $this->_createReservationIfNotExists();
	}

	/**
	 * Looks for reservation and if it doesn't exist, it creates it. Then returns its id. Otherwise id of existing reservation is returned
	 * @return int - reservation id
	 */
	private function _createReservationIfNotExists() {

		$query = "SELECT id FROM {$this->table_names["reservations"]} WHERE who = '{$this->who}' AND date_from = '{$this->date_from}' AND date_to = '{$this->date_to}'";
		$reservation = $this->sql->fetch_row($this->sql->query($query));

		if (!$reservation) {
			$query = "INSERT INTO {$this->table_names["reservations"]} (date_from, date_to, who) VALUES ('{$this->date_from}', '{$this->date_to}', '{$this->who}')";
			$this->sql->query($query);
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

	/**
	 * Gets reserved items in specific date range
	 * @param $date_from
	 * @param $date_to
	 * @return array
	 */
	public function getReservedItems($date_from, $date_to) {

		$date_from = $this->_parseDate($date_from);
		$date_to = $this->_parseDate($date_to);

		$query = "
SELECT i.id as id, i.name as name, image, price, category, parent_id FROM {$this->table_names["reservations"]} as r
LEFT JOIN {$this->table_names["r-i"]} AS ri ON r.id = ri.reservation_id
LEFT JOIN {$this->table_names["items"]} AS i ON ri.item_id = i.id
LEFT JOIN {$this->table_names["community"]} AS c ON c.id = r.who
WHERE (r.date_from > '{$date_from}' OR r.date_to > '{$date_from}') AND (r.date_from < '{$date_to}' OR r.date_to < '{$date_to}')
GROUP BY i.id
";

		return $this->sql->field_assoc($query);

	}

	/**
	 * return credentials of user who has reserved wanted item at specific time range
	 * @param $item_id int - wanted item's id
	 * @param $date_from
	 * @param $date_to
	 * @returns array - user credentials
	 */
	public function whoReservedThis($item_id, $date_from, $date_to) {
		$date_from = $this->_parseDate($date_from);
		$date_to = $this->_parseDate($date_to);

		$query = "
SELECT c.name as name, phone, email, date_from, date_to, confirmed
FROM {$this->table_names["reservations"]} as r
LEFT JOIN {$this->table_names["r-i"]} as ri ON ri.reservation_id = r.id
LEFT JOIN {$this->table_names["items"]} as i ON i.id = ri.item_id
LEFT JOIN {$this->table_names["community"]} as c ON c.id = r.who
WHERE i.id = {$item_id} AND (r.date_from > '{$date_from}' OR r.date_to > '{$date_from}') AND (r.date_from < '{$date_to}' OR r.date_to < '{$date_to}')";

		return $this->sql->fetch_row($this->sql->query($query));
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

		$this->date_from = $this->_parseDate($date_from);
		$this->date_to = $this->_parseDate($date_to);

		$query = "UPDATE {$this->table_names["reservations"]} SET date_from = '{$this->date_from}', date_to = '{$this->date_to}' WHERE id = {$this->id}";

		return $this->sql->query($query);
	}

}


?>