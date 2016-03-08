<?php

class CalendarEvents extends Zkusebna {

	function __construct() {
		parent::__construct();
	}

	public function getEvents($date_from, $date_to) {

		$reservations = new Reservation();
		$reserved_items = $reservations->getReservedItems($date_from,$date_to);
		return $reserved_items;

//		$date_from = parent::_parseDate($date_from);
//		$date_to = parent::_parseDate($date_to);
//
//		$query = "SELECT
//			i.id as id,
//			r.id as reservationID,
//			date_from as start,
//			date_to as end,
//			i.name as itemName,
//			image as img,
//			category,
//			c.name as name,
//			rr.type as repeat_type,
//			repeat_from,
//			repeat_to
//		FROM {$this->table_names["reservations"]} as r
//		LEFT JOIN {$this->table_names["r-i"]} as ri ON r.id = ri.reservation_id
//		LEFT JOIN {$this->table_names["items"]} as i ON ri.item_id = i.id
//		LEFT JOIN {$this->table_names["community"]} as c ON r.who = c.id
//		LEFT JOIN {$this->table_names["r-r"]} as rr ON r.repetition = rr.id
//		WHERE (r.date_from > '{$date_from}' OR r.date_to > '{$date_from}') AND (r.date_from < '{$date_to}' OR r.date_to < '{$date_to}')
//		GROUP BY r.id
//		ORDER BY category, parent_id, i.name";
//
//		$events = $this->sql->field_assoc($query);
//
//		$repeated_events = array_filter($events, function($event){ return $event['repeat_type'] !== null; });
//
//		foreach ($repeated_events as $event) {
//			$events = array_merge($events, Zkusebna::getRepeatedReservation($event));
//		}

//		return $events;

	}

}


?>