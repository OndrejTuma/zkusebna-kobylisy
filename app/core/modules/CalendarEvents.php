<?php

class CalendarEvents extends Zkusebna {

	function __construct() {
		parent::__construct();
	}

	public function getEvents($date_from, $date_to) {

		$date_from = $this->_parseDate($date_from);
		$date_to = $this->_parseDate($date_to);

		$query = "SELECT
			i.id as id,
			r.id as reservationID,
			date_from as start,
			date_to as end,
			i.name as itemName,
			image as img,
			category,
			c.name as name
		FROM {$this->table_names["reservations"]} as r
		LEFT JOIN {$this->table_names["r-i"]} as ri ON r.id = ri.reservation_id
		LEFT JOIN {$this->table_names["items"]} as i ON ri.item_id = i.id
		LEFT JOIN {$this->table_names["community"]} as c ON r.who = c.id
		WHERE (r.date_from > '{$date_from}' OR r.date_to > '{$date_from}') AND (r.date_from < '{$date_to}' OR r.date_to < '{$date_to}')
		ORDER BY category, parent_id, i.name";

		return $this->sql->field_assoc($query);

	}

}


?>