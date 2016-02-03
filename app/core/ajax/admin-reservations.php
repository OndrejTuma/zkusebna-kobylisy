<?php
//header('Content-Type: application/json');

include_once("../inc/bootstrap.php");

$sql = new mysql();

$query_confirmed = "SELECT i.id as id, i.name as name, r.name as reserved_by, email, phone, image, date_from, date_to FROM zkusebna_reservations as r LEFT JOIN zkusebna_items as i ON r.item_id = i.id WHERE r.confirmed = 1";

$reservations = array();

foreach ($sql->field_assoc($query_confirmed) as $reservation) {
	$reservations[$reservation["email"]][] = $reservation;
}
$output = "<ul>";
foreach ($reservations as $reservation) {
	$output .= "<li><strong class='expandable'>{$reservation[0]["reserved_by"]}</strong><ul>";
	foreach ($reservation as $item) {
		$output .= "<li>{$item["name"]}</li>";
	}
	$output .= "</ul></li>";
}
$output .= "</ul>";

echo $output;

?>