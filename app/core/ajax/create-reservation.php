<?php
header('Content-Type: application/json');

include_once("../inc/bootstrap.php");

$date_from = isset($_POST["date_from"]) ? $_POST["date_from"] : "";
$date_to = isset($_POST["date_to"]) ? $_POST["date_to"] : "";
$email = isset($_POST["email"]) ? $_POST["email"] : "";
$item_ids = isset($_POST["item_ids"]) ? $_POST["item_ids"] : "";
$name = isset($_POST["name"]) ? $_POST["name"] : "";
$phone = isset($_POST["phone"]) ? $_POST["phone"] : "";
$purpose = isset($_POST["purpose"]) ? $_POST["purpose"] : "";

$output = array();

$name_test = trim($name) == "";
$phone_test = preg_match("/^(\+420 *)?([0-9]{3} *){3}$/", $phone) == 0;
$email_test = filter_var($email, FILTER_VALIDATE_EMAIL) == false;
$purpose_test = (int)$purpose < 1;

if ($name_test || $phone_test || $email_test || $purpose_test) {

	$output["result"] = "empty";

}
else if (!count($item_ids)) {

	$output["result"] = "no-items";

}
else {
	$person = new Person($email, $name, $phone);
	$reservation = new Reservation();
	$reservation->makeReservation($date_from, $date_to, $person->getID(), (int)$purpose);
	$collisions = $reservation->hasCollision($item_ids);

	if ($collisions) {
		$output = array(
			"result" => "collision",
			"collisions" => $collisions,
			"heading" => "Rezervaci se nepodařilo potvrdit",
			"message" => "Vypadá to, že vás někdo předbehl u " . implode(", ", array_map(function($item){ return $item['name']; }, $collisions)) . "<br>Tyto položky byly odstraněny z rezervace."
		);
	}
	elseif ($reservation->addItems($item_ids)) {
		$output = array(
			"result" => "success",
			"heading" => "Rezervace je potvrzená",
			"message" => "Nyná čeká na schválení administrátorem. Až se tak stane, pošleme vám zprávu na uvedený email"
		);
	}
	else {
		$output = array(
			"result" => "failure",
			"message" => "Rezervaci se nepodařilo potvrdit."
		);
	}

}

echo json_encode($output);

?>