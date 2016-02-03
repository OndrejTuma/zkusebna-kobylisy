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

		if (isset($_SESSION["reservation"])) {
			// do we have an old $_SESSION ?
			$query = "SELECT id FROM {$this->table_names["reservations"]} WHERE id = '{$_SESSION["reservation"]["id"]}' AND confirmed = 0";
			$reservation = $this->sql->fetch_row($this->sql->query($query));

			return $reservation ? $_SESSION['reservation']['id'] : $this->_createNewReservation();
		}
		else {
			return $this->_createNewReservation();
		}

	}

	/**
	 * creates reservation from
	 * @return mixed
	 */
	private function _createNewReservation() {
		unset($_SESSION['reservation']);
		$query = "INSERT INTO {$this->table_names["reservations"]} (date_from, date_to, who) VALUES ('{$this->date_from}', '{$this->date_to}', '{$this->who}')";
		$this->sql->query($query);
		$_SESSION["reservation"] = array(
			"id" => $this->sql->insert_id()
		);

		return $_SESSION["reservation"]["id"];
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
	 * @return resource
	 */
	public function cancel() {
		unset($_SESSION["reservation"]);
		$query = "DELETE FROM {$this->table_names["r-i"]} WHERE reservation_id = {$this->id};";
		$query .= "DELETE FROM {$this->table_names["reservations"]} WHERE id = {$this->id};";
		echo $query;
		return $this->sql->query($query);
	}

	/**
	 * confirm reservation
	 * @return resource
	 */
	public function confirmReservation() {
		unset($_SESSION["reservation"]);
		$query = "UPDATE {$this->table_names["reservations"]} SET confirmed = 1 WHERE id = {$this->id}";
		return $this->sql->query($query);
	}

	/**
	 * gets reservation ID, if it exists
	 * @return null
	 */
	public function getId() {
		return isset($_SESSION['reservation']) ? $_SESSION['reservation']['id'] : null;
	}

	/**
	 * return user credentials who have currently reserved wanted item
	 * @param $item_id int - wanted item's it
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
WHERE i.id = {$item_id} AND (r.date_from > '{$date_from}' OR r.date_to >= '{$date_from}') AND (r.date_from <= '{$date_to}' OR r.date_to < '{$date_to}')";

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
		echo $query;
		return $this->sql->query($query);
	}









/*


	public function itemReservedBy($item_id, $date_from, $date_to) {

		$date_from = $this->_parseDate($date_from);
		$date_to = $this->_parseDate($date_to);

		$query = "SELECT r.name as name, phone, email, date_from, date_to, confirmed FROM {$this->table_names["reservations"]} as r LEFT JOIN {$this->table_names["items"]} as i ON r.item_id = i.id WHERE r.item_id = {$item_id} AND (r.date_from > '{$date_from}' OR r.date_to >= '{$date_from}') AND (r.date_from <= '{$date_to}' OR r.date_to < '{$date_to}')";
		return $this->sql->fetch_row($this->sql->query($query));

	}

	public function makeReservation($item_id, $date_from, $date_to, $name, $phone, $email) {

		if ($this->_isReserved($item_id, $date_from, $date_to)) {
			return false;
		}

		return $this->_saveReservation($item_id, $date_from, $date_to, $name, $phone, $email);

	}

	public function deleteReservations($item_ids, $name, $phone, $email) {

		$query = "DELETE FROM {$this->table_names["reservations"]} WHERE confirmed = 0 AND name = '{$name}' AND phone = '{$phone}' AND email = '{$email}' AND item_id IN ('" . implode("','", $item_ids) . "')";
		return $this->sql->query($query);

	}

	public function confirmReservations($item_ids, $name, $phone, $email, $date_from, $date_to) {

		$date_from = $this->_parseDate($date_from);
		$date_to = $this->_parseDate($date_to);

		$query = "UPDATE {$this->table_names["reservations"]} SET confirmed = 1 WHERE date_from = '{$date_from}' AND date_to = '{$date_to}' AND name = '{$name}' AND phone = '{$phone}' AND email = '{$email}' AND item_id IN ('" . implode("','", $item_ids) . "')";
		return $this->sql->query($query);

	}

	public function updateReservations($item_ids, $name, $phone, $email, $date_from, $date_to) {

		$date_from = $this->_parseDate($date_from);
		$date_to = $this->_parseDate($date_to);

		$query = "UPDATE {$this->table_names["reservations"]} SET date_from = '{$date_from}', date_to = '{$date_to}' WHERE confirmed = 0 AND name = '{$name}' AND phone = '{$phone}' AND email = '{$email}' AND item_id IN ('" . implode("','", $item_ids) . "')";
		return $this->sql->query($query);

	}


	private function _saveReservation($item_id, $date_from, $date_to, $name, $phone, $email) {

		$date_from = $this->_parseDate($date_from);
		$date_to = $this->_parseDate($date_to);

		$query = "INSERT INTO {$this->table_names["reservations"]} (date_from,date_to) VALUES ('{$date_from}','{$date_to}');";
		$query .= "INSERT ";
		return $this->sql->query($query);

	}
*/
}


?>