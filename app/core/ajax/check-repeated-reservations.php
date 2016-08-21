<?php
header('Content-Type: application/json');

include_once("../inc/bootstrap.php");

$date_from = isset($_POST["date_from"]) ? $_POST["date_from"] : "";
$date_to = isset($_POST["date_to"]) ? $_POST["date_to"] : "";
$repeat_to = isset($_POST["repeat_to"]) ? $_POST["repeat_to"] : "";
$period = isset($_POST["repeat_type"]) ? $_POST["repeat_type"] : "";
$period_types = Reservation::getRepeatUnixTypes();
var_dump($_POST);
if (!isset($period_types[$period])) {
	return json_encode(array(
		"result" => "failure",
		"message" => "Neznámý typ opakování. Vyberte hodnotu ze selectboxu"
	));
}

$reservation = new Reservation();



$repeat_end = new DateTime($repeat_to);
$date_begin = new DateTime($date_from);
$date_end = new DateTime($date_to);
$interval = new DateInterval($period_types[$period]);
$date_sub = $date_begin->diff($date_end);

//$date_period = new DatePeriod($date_begin, $interval, $repeat_end, DatePeriod::EXCLUDE_START_DATE);
$date_period = new DatePeriod($date_begin, $interval, $repeat_end);

$repeated_reservations = array();
//		var_dump("======= {$date_from}...{$date_to}");
foreach ($date_period as $date) {
	$start_date = Zkusebna::_parseDate($date->format('Y-m-d H:i:s'));
	$end_date = Zkusebna::_parseDate($date->add($date_sub)->format('Y-m-d H:i:s'));
			var_dump(($start_date > $date_from).", ".($end_date > $date_from).", ".($start_date < $date_to).", ".($end_date < $date_to)."...............{$start_date}...{$end_date}");
	if(($start_date > $date_from || $end_date > $date_from) && ($start_date < $date_to || $end_date < $date_to)) {
//				var_dump("\\/ ================= VYHOVUJE ================= \\/");

	}
//			var_dump($start_date, $end_date, $date_from, $date_to, $reservation['id'], $date_begin->format('Y-m-d H:i:s'), $repeat_end->format('Y-m-d H:i:s'), $period_types[$period], "===========================================================");
}



//echo json_encode($reservation->getReservedItems($date_from, $date_to));

?>