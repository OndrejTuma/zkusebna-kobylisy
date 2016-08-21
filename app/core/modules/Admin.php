<?php

class Admin extends Zkusebna {

	function __construct() {
		parent::__construct();
		$this->sql_table = new sql_table($this->table_names['admin']);
		//$this->sql_reservation = new sql_table($this->table_names['reservations']);
	}

	/**
	 * get admin's email
	 */
	public static function getEmail() {
		$sql = new sql_table("zkusebna_admin");
		$admin = $sql->get("name = 'admin'");
		return $admin[0]["email"];
	}

	public function addItem($name, $image, $price, $reservable, $category, $parent_id) {
		$query = "SELECT name FROM {$this->table_names["items"]} WHERE parent_id = '{$parent_id}' AND name = '{$name}'";
		$rows = $this->sql->num_rows($query);
		if (!$rows && !empty($name) && !empty($category) && $price >= 0) {
			$query = "INSERT INTO {$this->table_names["items"]} (name,image,price,reservable,category,parent_id) VALUES ('{$name}','{$image}','{$price}','{$reservable}','{$category}','{$parent_id}')";
			return $this->sql->query($query);
		}
		return false;
	}

	public function addPurpose($purpose, $discount) {
		$query = "SELECT title FROM {$this->table_names["purpose"]} WHERE title = '{$purpose}'";
		$rows = $this->sql->num_rows($query);
		if (!$rows && !empty($purpose) && $discount >= 0) {
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
		if ($this->sql->query($query)) {
			$Reservation = new Reservation();
			$reservation = $Reservation->getReservationById($reservationId);
			$items = new Items();
			$items = $items->getItemsById($Reservation->getReservationItems($reservationId));
			array_walk($items, function(&$item) { $item = $item["name"]; });
			Zkusebna::sendMail($reservation["email"], "Rezervace byla schválena", "
<table style=\"max-width: 600px; margin: 20px auto; color: #333; font-family: Arial, Helvetica, sans-serif; font-size: 17px;\">
	<tbody>
	<tr>
		<td>
			<h2 style=\"font-size: 30px; font-weight: 400; margin: 0 0 20px;\">Dobrý den,</h2>
			<p>Vaše rezervace <strong style=\"color: #67a712;\">{$reservation["reservation_name"]}</strong> byla právě schválena.</p>
			<table class=\"list\" cellspacing=\"0\" cellpadding=\"0\" style=\"margin: 50px auto; color: #333; font-family: Arial, Helvetica, sans-serif; font-size: 17px; background: #efefef; padding: 30px; box-shadow: inset 0 0 5px 5px #fff;\">
				<tbody>
				<tr>
					<td style=\"text-align: right; border-bottom: 1px dashed #000; padding: 10px;\">Datum:</td>
					<th style=\"text-align: left; border-bottom: 1px dashed #000; padding: 10px;\">".Zkusebna::parseSQLDate($reservation["date_from"])." - ".Zkusebna::parseSQLDate($reservation["date_to"])."</th>
				</tr>
				<tr>
					<td style=\"text-align: right; border-bottom: 1px dashed #000; padding: 10px;\">Jméno:</td>
					<th style=\"text-align: left; border-bottom: 1px dashed #000; padding: 10px;\">{$reservation["name"]}</th>
				</tr>
				<tr>
					<td style=\"text-align: right; border-bottom: 1px dashed #000; padding: 10px;\">Email:</td>
					<th style=\"text-align: left; border-bottom: 1px dashed #000; padding: 10px;\">{$reservation["email"]}</th>
				</tr>
				<tr>
					<td style=\"text-align: right; border-bottom: 1px dashed #000; padding: 10px;\">Telefon:</td>
					<th style=\"text-align: left; border-bottom: 1px dashed #000; padding: 10px;\">{$reservation["phone"]}</th>
				</tr>
				<tr>
					<td style=\"text-align: right; vertical-align: top; border-bottom: 1px dashed #000; padding: 10px;\">Rezervované položky:</td>
					<th style=\"text-align: left; border-bottom: 1px dashed #000; padding: 10px;\">
						<ul style=\"margin: 0; padding: 0 0 0 15px;\">
							<li>".implode("</li><li>", $items)."</li>
						</ul>
					</th>
				</tr>
				</tbody>
			</table>
			<p>Pokud to není vaše rezervace, napište to koordinátorovi zkušebny.</p>
		</td>
	</tr>
	</tbody>
</table>
");
		}
		return false;
	}
	/**
	 * rejects reservation
	 * @param $reservationId
	 */
	public function deleteReservation($reservationId) {
		$reservationId = (int)$reservationId;
		$Reservation = new Reservation();
		$reservation = $Reservation->getReservationById($reservationId);
		$items = new Items();
		$items = $items->getItemsById($Reservation->getReservationItems($reservationId));
		//$reservation = $this->sql_reservation->get("id = {$reservationId}");


		// delete reserved items
		$query = "DELETE FROM {$this->table_names["r-i"]} WHERE reservation_id = {$reservationId}";
		$this->sql->query($query);
		// delete repetition
		$query = "DELETE FROM {$this->table_names["r-r"]} WHERE id = {$reservation['repetition']}";
		$this->sql->query($query);
		// delete reservation
		$query = "DELETE FROM {$this->table_names["reservations"]} WHERE id = {$reservationId}";
		$this->sql->query($query);


		array_walk($items, function(&$item) { $item = $item["name"]; });

		Zkusebna::sendMail($reservation["email"], "Rezervace byla zamítnuta", "
<table style=\"max-width: 600px; margin: 20px auto; color: #333; font-family: Arial, Helvetica, sans-serif; font-size: 17px;\">
	<tbody>
	<tr>
		<td>
			<h2 style=\"font-size: 30px; font-weight: 400; margin: 0 0 20px;\">Dobrý den,</h2>
			<p>Vaše rezervace <strong style=\"color: #cc2229;\">{$reservation["reservation_name"]}</strong> byla právě zcela nebo částečně zamítnuta.</p>
			<table class=\"list\" cellspacing=\"0\" cellpadding=\"0\" style=\"margin: 50px auto; color: #333; font-family: Arial, Helvetica, sans-serif; font-size: 17px; background: #efefef; padding: 30px; box-shadow: inset 0 0 5px 5px #fff;\">
				<tbody>
				<tr>
					<td style=\"text-align: right; border-bottom: 1px dashed #000; padding: 10px;\">Datum:</td>
					<th style=\"text-align: left; border-bottom: 1px dashed #000; padding: 10px;\">".Zkusebna::parseSQLDate($reservation["date_from"])." - ".Zkusebna::parseSQLDate($reservation["date_to"])."</th>
				</tr>
				<tr>
					<td style=\"text-align: right; border-bottom: 1px dashed #000; padding: 10px;\">Jméno:</td>
					<th style=\"text-align: left; border-bottom: 1px dashed #000; padding: 10px;\">{$reservation["name"]}</th>
				</tr>
				<tr>
					<td style=\"text-align: right; border-bottom: 1px dashed #000; padding: 10px;\">Email:</td>
					<th style=\"text-align: left; border-bottom: 1px dashed #000; padding: 10px;\">{$reservation["email"]}</th>
				</tr>
				<tr>
					<td style=\"text-align: right; border-bottom: 1px dashed #000; padding: 10px;\">Telefon:</td>
					<th style=\"text-align: left; border-bottom: 1px dashed #000; padding: 10px;\">{$reservation["phone"]}</th>
				</tr>
				<tr>
					<td style=\"text-align: right; vertical-align: top; border-bottom: 1px dashed #000; padding: 10px;\">Rezervované položky:</td>
					<th style=\"text-align: left; border-bottom: 1px dashed #000; padding: 10px;\">
						<ul style=\"margin: 0; padding: 0 0 0 15px;\">
							<li>".implode("</li><li>", $items)."</li>
						</ul>
					</th>
				</tr>
				</tbody>
			</table>
			<p>Důvody zamítnutí vám napíše koordinátor zkušebny v samostatném emailu.</p>
			<p>Pokud to není vaše rezervace, napište to koordinátorovi zkušebny.</p>
		</td>
	</tr>
	</tbody>
</table>
");
	}
	public function deleteReservationItem($itemId, $reservationId) {
		$query = "DELETE FROM {$this->table_names["r-i"]} WHERE reservation_id = {$reservationId} AND item_id = {$itemId}";
		return $this->sql->query($query);
	}
	public function renderApprovedReservations() {
		return $this->_renderReservations($this->_getReservarvations("repetition IS NULL AND approved = 1"));
	}
	public function renderItems() {
		$items = new Items();
		return $items->renderItems("","","",1,array(),true);
	}
	public function renderRepeatedReservations() {
		return $this->_renderReservations($this->_getReservarvations("repetition > 0"), true);
	}
	public function renderUnapprovedReservations() {
		return $this->_renderReservations($this->_getReservarvations("repetition IS NULL AND approved = 0"));
	}

	private function _getReservarvations($where, $limit = 500) {
		$query = "
SELECT r.id as id, i.id as item_id, c.name as who, i.name as item_name, r.name as reservation_name, email, phone, image, payed, date_from, date_to, (date_to < NOW()) as archived, price, discount, title as purpose, repeat_to FROM {$this->table_names["reservations"]} as r
LEFT JOIN {$this->table_names["r-i"]} as ri ON ri.reservation_id = r.id
LEFT JOIN {$this->table_names["items"]} as i ON i.id = ri.item_id
LEFT JOIN {$this->table_names["community"]} as c ON c.id = r.who
LEFT JOIN {$this->table_names["purpose"]} as p ON p.id = r.purpose
LEFT JOIN {$this->table_names["r-r"]} as rr ON rr.id = r.repetition
WHERE {$where}
ORDER BY date_to DESC
LIMIT {$limit}
";
		$reservations = array();
		foreach ($this->sql->field_assoc($query) as $reservation) {
			$reservations[$reservation['id']]["reservation_name"] = $reservation['reservation_name'];
			$reservations[$reservation['id']]["who"] = $reservation['who'];
			$reservations[$reservation['id']]["payed"] = $reservation['payed'];
			$reservations[$reservation['id']]["email"] = $reservation['email'];
			$reservations[$reservation['id']]["phone"] = $reservation['phone'];
			$reservations[$reservation['id']]["date_from"] = $reservation['date_from'];
			$reservations[$reservation['id']]["date_to"] = $reservation['date_to'];
			$reservations[$reservation['id']]["archived"] = $reservation['archived'];
			$reservations[$reservation['id']]["repeat_to"] = $reservation['repeat_to'];
			$reservations[$reservation['id']]["purpose"] = array(
				"title" => $reservation['purpose'],
				"discount" => $reservation['discount']
			);
			$reservations[$reservation['id']]["items"][] = array(
				"id" => $reservation['item_id'],
				"name" => $reservation['item_name'],
				"image" => $reservation['image'],
				"price" => round($reservation['price'] * (1 - $reservation['discount'] / 100))
			);
		}

		return $reservations;
	}

	private function _renderReservations($reservations, $repeated = false) {
		if (count($reservations)) {
			$has_archived_reservations = false;
			$output = "<ol>";
			foreach ($reservations as $id => $reservation) {
				$price = 0;
				foreach ($reservation["items"] as $item) {
					$price += $item['price'];
				}

				if ($reservation["archived"]) {
					$has_archived_reservations = true;
				}

				$output .= "<li data-id='{$id}' class='" . ($reservation["archived"] ? "archived" : "") . "'><strong class='expandable'>{$reservation["reservation_name"]} <small>({$reservation["who"]})</small></strong> ";
				$output .= "<small>" . Zkusebna::parseSQLDate($reservation['date_from']) . " - " . Zkusebna::parseSQLDate($reservation[$repeated ? 'repeat_to' : 'date_to']) . "</small> ";

				if ($price > 0) {
					$output .= "<span data-toggle-0-class='red' data-toggle-1-class='green' data-toggle-0-message='Označit jako zaplacenou' data-toggle-1-message='Označit jako nezaplacenou' data-table='reservations' data-column='payed' data-id='{$id}' class='tooltip toggleable payed icon-coin ".($reservation["payed"] ? "green" : "red")."' data-message='".($reservation["payed"] ? "Označit jako nezaplacenou" : "Označit jako zaplacenou")."'></span>";
				}

				$output .= "<span class='tooltip icon-mobile' data-message='{$reservation["phone"]}' data-clipboard-text='{$reservation["phone"]}'></span>";
				$output .="<span class='icon-mail tooltip' data-message='{$reservation["email"]}' data-clipboard-text='{$reservation["email"]}'></span> ";
				$output .="<i class='approve icon-checkmark'></i>";
				$output .="<i class='delete icon-close'></i> ";
				$output .="<ul>";
				foreach ($reservation["items"] as $item) {
					$output .= "<li>{$item["name"]} <i data-item='{$item["id"]}' class='delete-item icon-close tooltip' data-message='Zamítnout položku'></i> <em>{$item['price']}</em></li>";
				}
				$output .= "</ul>";
				$output .= "<em class='tooltip' data-message='Účel rezervace: <strong>{$reservation["purpose"]["title"]}</strong><br>Plošná sleva: <strong>{$reservation["purpose"]["discount"]}%</strong>'>{$price}</em>";
				$output .= "</li>";
			}
			$output .= "</ol>";
			if ($has_archived_reservations) {
				$output.= "<p><a class='archive' href='#'>Zobrazit staré rezervace</a></p>";
			}
		}
		else {
			$output = "<p><b>Žádné rezervace</b></p>";
		}

		return $output;
	}

	public function renderPurposes() {
		$query = "SELECT id, title, discount FROM {$this->table_names["purpose"]} ORDER BY title";

		$output = "<ol class='reservation-list editable-list percentage'>";
		foreach ($this->sql->field_assoc($query) as $purpose) {
			$output .= "<li>";
			$output .= "<strong data-table='purpose' data-id='{$purpose['id']}' data-column='title' class='editable'>{$purpose['title']}</strong>";
			$output .= "<em data-table='purpose' data-id='{$purpose['id']}' data-column='discount' class='editable'>{$purpose['discount']}</em>";
			$output .= "<i data-table='purpose' data-id='{$purpose['id']}' data-parent='li' class='deletable icon-close'></i>";
			$output .= "</li>";
		}
		$output .= "</ol>";

		return $output;
	}

}

?>