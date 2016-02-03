<?php
header('Content-Type: application/json');

include_once("../inc/bootstrap.php");

$output = "";

$date_from = isset($_POST["date_from"]) ? $_POST["date_from"] : "";
$date_to = isset($_POST["date_to"]) ? $_POST["date_to"] : "";
$email = isset($_POST["email"]) ? $_POST["email"] : "";
$item_id = isset($_POST["item_id"]) ? $_POST["item_id"] : "";
$name = isset($_POST["name"]) ? $_POST["name"] : "";
$phone = isset($_POST["phone"]) ? $_POST["phone"] : "";

$name_test = trim($name) == "";
$phone_test = preg_match("/^(\+420 *)?([0-9]{3} *){3}$/", $phone) == 0;
$email_test = filter_var($email, FILTER_VALIDATE_EMAIL) == false;

$output = array(
	"empties" => array(
		// input_id => is_valid
		"name" => $name_test,
		"phone" => $phone_test,
		"email" => $email_test
	)
);

if ($name_test || $phone_test || $email_test) {

	$output["result"] = "empty";

}
else {

	$person = new Person($email, $name, $phone);
	$reservation = new Reservation($date_from, $date_to, $person->getID());

	if ($reservation->addItems([$item_id])) {
		$output["result"] = "success";
		$output["item"] = $reservation->getItemInfo($item_id);
	}
	else { //TODO
		$user = $reservation->whoReservedThis($item_id, $date_from, $date_to);
		$output["result"] = "failure";
		$output["message"] = "<strong>{$user["name"]}</strong> má rezervaci Od " . $reservation->parseSQLDate($user["date_from"]) . " do " . $reservation->parseSQLDate($user["date_to"]);
	}

}

print json_encode($output);

?>