<?php

class Admin extends Zkusebna {

	function __construct() {
		parent::__construct();
	}

	public function addPurpose($purpose, $discount) {
		$query = "SELECT title FROM {$this->table_names["purpose"]} WHERE title = '{$purpose}'";
		if (!$this->sql->num_rows($query) && $purpose && $discount) {
			$query = "INSERT INTO {$this->table_names["purpose"]} (title,discount) VALUES ('{$purpose}'," . (int)$discount . ")";
			return $this->sql->query($query);
		}
		return false;
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
	public function deleteReservation($reservationId) {
		$reservationId = (int)$reservationId;
		// delete reserved items
		$query = "DELETE FROM {$this->table_names["r-i"]} WHERE reservation_id = {$reservationId}";
		$this->sql->query($query);
		// delete reservation
		$query = "DELETE FROM {$this->table_names["reservations"]} WHERE id = {$reservationId}";
		$this->sql->query($query);
	}
	public function deleteReservationItem($itemId, $reservationId) {
		$query = "DELETE FROM {$this->table_names["r-i"]} WHERE reservation_id = {$reservationId} AND item_id = {$itemId}";
		return $this->sql->query($query);
	}
	public function renderApprovedReservations() {
		return $this->_renderReservations($this->_getReservarvations("approved = 1"));
	}
	public function renderItems() {
		$items = new Items();
		return $items->renderItems("","","","");
	}
	public function renderUnapprovedReservations() {
		return $this->_renderReservations($this->_getReservarvations("approved = 0"));
	}

	private function _getReservarvations($where, $limit = 500) {
		$query = "
SELECT r.id as id, i.id as item_id, c.name as who, i.name as item_name, email, phone, image, date_from, date_to, (date_to < NOW()) as archived, price, discount FROM {$this->table_names["reservations"]} as r
LEFT JOIN {$this->table_names["r-i"]} as ri ON ri.reservation_id = r.id
LEFT JOIN {$this->table_names["items"]} as i ON i.id = ri.item_id
LEFT JOIN {$this->table_names["community"]} as c ON c.id = r.who
LEFT JOIN {$this->table_names["purpose"]} as p ON p.id = r.purpose
WHERE {$where}
ORDER BY date_to DESC
LIMIT {$limit}
";
		$reservations = array();
		foreach ($this->sql->field_assoc($query) as $reservation) {
			$reservations[$reservation['id']]["who"] = $reservation['who'];
			$reservations[$reservation['id']]["email"] = $reservation['email'];
			$reservations[$reservation['id']]["phone"] = $reservation['phone'];
			$reservations[$reservation['id']]["date_from"] = $reservation['date_from'];
			$reservations[$reservation['id']]["date_to"] = $reservation['date_to'];
			$reservations[$reservation['id']]["archived"] = $reservation['archived'];
			$reservations[$reservation['id']]["items"][] = array(
				"id" => $reservation['item_id'],
				"name" => $reservation['item_name'],
				"image" => $reservation['image'],
				"price" => round($reservation['price'] * (1 - $reservation['discount'] / 100))
			);
		}

		return $reservations;
	}

	private function _renderReservations($reservations) {
		if (count($reservations)) {
			$has_archived_reservations = false;
			$output = "<ol>";
			foreach ($reservations as $id => $reservation) {
				$price = 0;
				if ($reservation["archived"]) {
					$has_archived_reservations = true;
				}
				$output .= "<li data-id='{$id}' class='" . ($reservation["archived"] ? "archived" : "") . "'><strong class='expandable'>{$reservation["who"]}</strong> ";
				$output .= "<small>" . Zkusebna::parseSQLDate($reservation['date_from']) . " - " . Zkusebna::parseSQLDate($reservation['date_to']) . "</small> ";
				$output .= "<span class='tooltip icon-mobile' data-message='{$reservation["phone"]}'></span>";
				$output .="<span class='icon-mail tooltip' data-message='{$reservation["email"]}'></span> ";
				$output .="<i class='approve icon-checkmark tooltip' data-message='Schválit rezervaci'></i>";
				$output .="<i class='delete icon-close tooltip' data-message='Zamítnout rezervaci'></i> ";
				$output .="<ul>";
				foreach ($reservation["items"] as $item) {
					$price += $item['price'];
					$output .= "<li>{$item["name"]} <i data-item='{$item["id"]}' class='delete-item icon-close tooltip' data-message='Zamítnout položku'></i></li>";
				}
				$output .= "</ul>";
				$output .= "<em>{$price}</em>";
				$output .= "</li>";
			}
			$output .= "</ol>";
			if ($has_archived_reservations) {
				$output.= "<p><a class='archive' href='#'>Zobrazit staré rezervace</a></p>";
			}
		}
		else {
			$output = "<p><i>Žádné rezervace</i></p>";
		}

		return $output;
	}

}

?>