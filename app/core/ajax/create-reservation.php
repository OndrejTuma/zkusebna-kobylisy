<?php
session_start();
header('Content-Type: application/json');

include_once("../inc/bootstrap.php");

$date_from = isset($_POST["date_from"]) ? Zkusebna::_parseDate($_POST["date_from"]) : "";
$date_to = isset($_POST["date_to"]) ? Zkusebna::_parseDate($_POST["date_to"]) : "";
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

if ($name_test || $phone_test || $email_test || $purpose_test || !$date_from || !$date_to) {

	$output["result"] = "empty";

}
else if (!count($item_ids)) {

	$output["result"] = "no-items";

}
else {
	$admin = new AuthAdmin();
	$person = new Person($email, $name, $phone);
	$reservation = new Reservation();
	$reservation_params = array(
		"date_from" => $date_from,
		"date_to" => $date_to,
		"who" => $person->getID(),
		"purpose_id" => (int)$purpose
	);

	if ($admin->is_logged()) {
		$repeat_types = Reservation::getRepeatTypes();
		$repeat_type = isset($_POST["repeat_type"]) && isset($repeat_types[$_POST["repeat_type"]]) ? $repeat_types[$_POST["repeat_type"]] : "";
		$repeat_from = Zkusebna::_parseDate(date("j.m.Y", strtotime($date_from)));
		$repeat_to = isset($_POST["repeat_to"]) ? Zkusebna::_parseDate($_POST["repeat_to"]) : "";
		if ($repeat_type && $repeat_to) {
			$reservation_params = array_merge($reservation_params, array(
				"repeat_type" => $repeat_type,
				"repeat_from" => $repeat_from,
				"repeat_to" => $repeat_to
			));
		}
	}

	$reservation->createReservation($reservation_params);
	//reservation is created anyway, so there is only after-check for collisions to notify the user about it
	$collisions = $reservation->hasCollision($item_ids);

	if ($collisions) {
		$output = array(
			"result" => "collision",
			"collisions" => array_map(function($item){ return $item['id']; }, $collisions),
			"heading" => "Rezervaci se nepodařilo potvrdit",
			"message" => "Vypadá to, že vás někdo předbehl u těchto položek: <ul>" . implode(", ", array_map(function($item){ return "<li class='{$item['category']}'>{$item['name']}</li>"; }, $collisions)) . "</ul>Tyto položky byly odstraněny z rezervace."
		);
	}
	elseif ($reservation->addItems($item_ids)) {

		if ($admin->is_logged()) {
			$output = array(
				"result" => "success",
				"heading" => "Rezervace je odeslaná",
				"message" => "A právě čeká na schválení administrátorem. Až se tak stane, pošleme vám zprávu na uvedený email. Zatím si můžete v emailové schránce zrekapitulovat rezervaci."
			);
		}
		else {
			Zkusebna::sendMail($email, 'Rekapitulace rezervace', '
<h3>Dobrý den</h3>
<p>Vypadá to, že jste si v Kobyliské zkušebně rezervoval nějaké věci. Pojďme si to zrekapitulovat</p>
<h4>Rezervoval/a:</h4>
<dl>
<dt>Jméno:</dt>
<dd>{$name}</dd>
<dt>Email:</dt>
<dd>{$email}</dd>
<dt>Telefon:</dt>
<dd>{$phone}</dd>
</dl>
<p>Pokud nevíte, o čem je řeč, napište administrátorovi stránek</p>
');
			$output = array(
				"result" => "success",
				"heading" => "Rezervace je odeslaná",
				"message" => "A právě čeká na schválení administrátorem. Až se tak stane, pošleme vám zprávu na uvedený email. Zatím si můžete v emailové schránce zrekapitulovat rezervaci."
			);
		}

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