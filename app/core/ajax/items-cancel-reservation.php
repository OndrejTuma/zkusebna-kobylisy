<?php
header('Content-Type: application/json');

include_once("../inc/bootstrap.php");

$date_from = isset($_POST["date_from"]) ? $_POST["date_from"] : "";
$date_to = isset($_POST["date_to"]) ? $_POST["date_to"] : "";
$email = isset($_POST["email"]) ? $_POST["email"] : "";
$phone = isset($_POST["phone"]) ? $_POST["phone"] : "";
$name = isset($_POST["name"]) ? $_POST["name"] : "";

$person = new Person($email, $name, $phone);
$reservation = new Reservation($date_from, $date_to, $person->getID());
$output = "";

if ($item_ids = $reservation->cancel()) {
	$output = array(
		"result" => "success",
		"items" => $reservation->getItemsInfo($item_ids)
	);
}
else {
	$output = array(
		"result" => "failure",
		"message" => "Rezervaci se nepodařilo odstranit"
	);
}


print json_encode($output);

?>