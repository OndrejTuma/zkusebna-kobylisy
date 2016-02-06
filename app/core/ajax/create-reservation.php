<?php
header('Content-Type: application/json');

include_once("../inc/bootstrap.php");

$output = "";

$date_from = isset($_POST["date_from"]) ? $_POST["date_from"] : "";
$date_to = isset($_POST["date_to"]) ? $_POST["date_to"] : "";
$email = isset($_POST["email"]) ? $_POST["email"] : "";
$item_ids = isset($_POST["item_id"]) ? $_POST["item_id"] : "";
$name = isset($_POST["name"]) ? $_POST["name"] : "";
$phone = isset($_POST["phone"]) ? $_POST["phone"] : "";

$name_test = trim($name) == "";
$phone_test = preg_match("/^(\+420 *)?([0-9]{3} *){3}$/", $phone) == 0;
$email_test = filter_var($email, FILTER_VALIDATE_EMAIL) == false;

$output = array();

$person = new Person($email, $name, $phone);
$reservation = new Reservation($date_from, $date_to, $person->getID());

if ($reservation->addItems($item_ids)) {
	$output["result"] = "success";
}
else {
	$reservation->cancel();
	$output["result"] = "failure";
}

print json_encode($output);

?>