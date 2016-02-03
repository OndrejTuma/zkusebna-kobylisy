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
			date_from,
			date_to,
			i.name as name,
			image,
			category,
			r.name as reserved_by
		FROM {$this->table_names["reservations"]} as r
		LEFT JOIN {$this->table_names["items"]} as i ON r.item_id = i.id
		WHERE (r.date_from > '{$date_from}' OR r.date_to >= '{$date_from}') AND (r.date_from <= '{$date_to}' OR r.date_to < '{$date_to}')
		ORDER BY category, parent_id, i.name";

		return $this->sql->field_assoc($query);

	}

}


?>