<?php
header('Content-Type: application/json');

include_once("../inc/bootstrap.php");

$date_from = isset($_POST["date_from"]) ? $_POST["date_from"] : "";
$date_to = isset($_POST["date_to"]) ? $_POST["date_to"] : "";
$email = isset($_POST["email"]) ? $_POST["email"] : "";
$item_ids = isset($_POST["item_ids"]) ? $_POST["item_ids"] : "";
$name = isset($_POST["name"]) ? $_POST["name"] : "";
$phone = isset($_POST["phone"]) ? $_POST["phone"] : "";

$output = array();

$name_test = trim($name) == "";
$phone_test = preg_match("/^(\+420 *)?([0-9]{3} *){3}$/", $phone) == 0;
$email_test = filter_var($email, FILTER_VALIDATE_EMAIL) == false;

if ($name_test || $phone_test || $email_test) {

	$output["result"] = "empty";

}
else {
	$person = new Person($email, $name, $phone);
	$reservation = new Reservation($date_from, $date_to, $person->getID());

	if ($reservation->addItems($item_ids)) {
		$output = array(
			"result" => "success",
			"heading" => "Rezervace je potvrzená",
			"message" => "Nyná čeká na schválení administrátorem. Až se tak stane, pošleme vám zprávu na uvedený email"
		);
	}
	else {
		$reservation->cancel();
		$output = array(
			"result" => "failure",
			"message" => "Rezervaci se nepodařilo potvrdit"
		);
	}

}

echo json_encode($output);

?>