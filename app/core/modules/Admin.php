<?php

class Admin extends Zkusebna {

	function __construct() {
		parent::__construct();
	}

	/**
	 * approves reservation
	 * @param $reservationId
	 */
	public function approveReservation($reservationId) {
		$query = "UPDATE {$this->table_names["reservations"]} SET approved = 1 WHERE id = " . (int)$reservationId;
		return $this->sql->query($query);
	}
	/**
	 * rejects reservation
	 * @param $reservationId
	 */
	public function unapproveReservation($reservationId) {
		$reservationId = (int)$reservationId;
		// delete reserved items
		$query = "DELETE FROM {$this->table_names["r-i"]} WHERE reservation_id = {$reservationId}";
		$this->sql->query($query);
		// delete reservation
		$query = "DELETE FROM {$this->table_names["reservations"]} WHERE id = {$reservationId}";
		$this->sql->query($query);
	}
	public function getUnapprovedReservations() {
		return $this->_getReservarvations("approved = 0");
	}

	private function _getReservarvations($where) {
		$query = "
SELECT r.id as id, c.name as who, i.name as item_name, email, phone, image, date_from, date_to FROM {$this->table_names["reservations"]} as r
LEFT JOIN {$this->table_names["r-i"]} as ri ON ri.reservation_id = r.id
LEFT JOIN {$this->table_names["items"]} as i ON i.id = ri.item_id
LEFT JOIN {$this->table_names["community"]} as c ON c.id = r.who
WHERE {$where}
";
		$reservations = array();
		foreach ($this->sql->field_assoc($query) as $reservation) {
			$reservations[$reservation['id']]["who"] = $reservation['who'];
			$reservations[$reservation['id']]["email"] = $reservation['email'];
			$reservations[$reservation['id']]["phone"] = $reservation['phone'];
			$reservations[$reservation['id']]["date_from"] = $reservation['date_from'];
			$reservations[$reservation['id']]["date_to"] = $reservation['date_to'];
			$reservations[$reservation['id']]["items"][] = array(
				"name" => $reservation['item_name'],
				"image" => $reservation['image']
			);
		}
		return $reservations;
	}

}

?>