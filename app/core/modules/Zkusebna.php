<?php

class Zkusebna {

	function __construct() {
		$this->sql = new mysql();
		$this->table_names = array(
			"reservations" => "zkusebna_reservations",
			"items" => "zkusebna_items",
			"community" => "zkusebna_community",
			"r-i" => "zkusebna_reserved_items",
		);
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
	 *
	 * @param $date_from
	 * @param $date_to
	 * @param $email
	 * @return array
	 */
	public function getReservedItems($date_from, $date_to, $email) {

		$date_from = $this->_parseDate($date_from);
		$date_to = $this->_parseDate($date_to);

		$email = $email ? $email : "";
		$query = "
SELECT i.id as id, i.name as name, image, price, category, parent_id FROM {$this->table_names["reservations"]} as r
LEFT JOIN {$this->table_names["r-i"]} AS ri ON r.id = ri.reservation_id
LEFT JOIN {$this->table_names["items"]} AS i ON ri.item_id = i.id
LEFT JOIN {$this->table_names["community"]} AS c ON c.id = r.who
WHERE c.email != '{$email}' AND (r.date_from > '{$date_from}' OR r.date_to >= '{$date_from}') AND (r.date_from <= '{$date_to}' OR r.date_to < '{$date_to}')
GROUP BY i.id
";
		echo $query;
		return $this->sql->field_assoc($query);

	}

	public function parseSQLDate($date) {

		return date("j.n. G:i", strtotime($date));

	}



	protected function _isReserved($item_id, $date_from, $date_to) {

		$date_from = $this->_parseDate($date_from);
		$date_to = $this->_parseDate($date_to);

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

	protected function _parseDate($date) {

		return date("Y-m-d H:i:s", strtotime($date));

	}



}


?>