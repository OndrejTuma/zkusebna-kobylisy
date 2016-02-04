<?php
header('Content-Type: application/json');

include_once("../inc/bootstrap.php");

$item_ids = isset($_POST["item_ids"]) ? $_POST["item_ids"] : array();
$date_from = isset($_POST["date_from"]) ? $_POST["date_from"] : "";
$date_to = isset($_POST["date_to"]) ? $_POST["date_to"] : "";
$email = isset($_POST["email"]) ? $_POST["email"] : "";
$phone = isset($_POST["phone"]) ? $_POST["phone"] : "";
$name = isset($_POST["name"]) ? $_POST["name"] : "";

$person = new Person($email, $name, $phone);
$reservation = new Reservation($date_from, $date_to, $person->getID());
$output = "";

if ($reservation->confirmReservation()) {
	$output = array(
		"result" => "success",
		"heading" => "Rezervace je potvrzená",
		"message" => "Nyná čeká na schválení administrátorem. Až se tak stane, pošleme vám zprávu na uvedený email"
	);
}
else {
	$output = array(
		"result" => "failure",
		"message" => "Rezervaci se nepodařilo potvrdit"
	);
}


print json_encode($output);

?>