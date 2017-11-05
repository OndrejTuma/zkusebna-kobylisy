<?php
header('Content-Type: application/json');
include_once("../inc/bootstrap.php");

$output = [];

$Reservation = new Reservation();

$sql = new mysql();
$query = "SELECT id FROM zkusebna_reservations";

foreach ($sql->field($query) as $row) {
	$reservationId = $row['id'];

	$reservation = $Reservation->getReservationById($reservationId);

	$items = new Items();
	$items = $items->getItemsById($Reservation->getReservationItems($reservationId));

	$reduction = (100 - (float)$Reservation->getDiscount($reservationId)) / 100;

	$price_total = 0;

	foreach ($items as $item) {
		$price_total += round($item['price'] * $reduction);
	}

	$query = "UPDATE zkusebna_reservations SET price = {$price_total} WHERE id = {$reservationId}";
	$sql->query($query);

	$output[$reservationId] = $price_total;
}

echo json_encode($output)

?>
