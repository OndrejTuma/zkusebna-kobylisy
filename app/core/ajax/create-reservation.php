<?php
session_start();
header('Content-Type: application/json');

include_once("../inc/bootstrap.php");

$reservation_name = isset($_POST["reservation_name"]) ? $_POST["reservation_name"] : "";
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
		"reservation_name" => $reservation_name,
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
			"message" => "Vypadá to, že vás někdo předbehl u těchto položek: <ul>" . implode("", array_map(function($item){ return "<li class='{$item['category']}'>{$item['itemName']}</li>"; }, $collisions)) . "</ul>Tyto položky byly odstraněny z rezervace."
		);
	}
	elseif ($reservation->addItems($item_ids)) {

		$items = new Items();
		$items = $items->getItemsById($item_ids);
		$price = array_reduce($items, function($carry, $item) { return $carry + $item['price']; });
		$price_total = $price * (100 - (int)$reservation->getDiscount()) / 100;
		array_walk($items, function(&$item) { $item = $item["name"]; });

		Zkusebna::sendMail($email, 'Rekapitulace rezervace', "
<table style=\"max-width: 600px; margin: 20px auto; color: #333; font-family: Arial, Helvetica, sans-serif; font-size: 17px;\">
	<tbody>
	<tr>
		<td>
			<h2 style=\"font-size: 30px; font-weight: 400; margin: 0 0 20px;\">Dobrý den,</h2>
			<p>toto je předběžná rekapitulace rezervace <strong>{$reservation_name}</strong> v Kobyliské zkušebně.</p>
			<p style=\"text-align: center; margin: 50px auto;\"><strong style=\"color: #cc2229;\">Čeká na schválení!</strong></p>
			<p>Pokud to není vaše rezervace, napište správci zkušebny odpovědí na tento email.</p>
			".($price_total > 0 ? "
			<table class=\"list\" cellspacing=\"0\" cellpadding=\"0\" style=\"margin: 50px auto; color: #333; font-family: Arial, Helvetica, sans-serif; font-size: 17px; background: #efefef; padding: 30px; box-shadow: inset 0 0 5px 5px #fff;\">
				<tbody>
				<tr>
					<td style=\"text-align: right; border-bottom: 1px dashed #000; padding: 10px;\">Cena:</td>
					<th style=\"text-align: left; border-bottom: 1px dashed #000; padding: 10px;\">{$price_total},-</th>
				</tr>
				<tr>
					<td colspan='2' style='padding: 10px;'>Platbu poukazujte na účet číslo <strong>1242882944 / 2310</strong> (preferujeme), <strong>do zprávy pro příjemce napište název akce</strong>.<br>Nebo hotově správci zkušebny.</td >
				</tr>
				</tbody>
			</table>
			" : "")."
			<table class=\"list\" cellspacing=\"0\" cellpadding=\"0\" style=\"margin: 50px auto; color: #333; font-family: Arial, Helvetica, sans-serif; font-size: 17px; background: #efefef; padding: 30px; box-shadow: inset 0 0 5px 5px #fff;\">
				<tbody>
				<tr>
					<td style=\"text-align: right; border-bottom: 1px dashed #000; padding: 10px;\">Datum:</td>
					<th style=\"text-align: left; border-bottom: 1px dashed #000; padding: 10px;\">".Zkusebna::parseSQLDate($date_from)." - ".Zkusebna::parseSQLDate($date_to)."</th>
				</tr>
				<tr>
					<td style=\"text-align: right; border-bottom: 1px dashed #000; padding: 10px;\">Jméno:</td>
					<th style=\"text-align: left; border-bottom: 1px dashed #000; padding: 10px;\">{$name}</th>
				</tr>
				<tr>
					<td style=\"text-align: right; border-bottom: 1px dashed #000; padding: 10px;\">Email:</td>
					<th style=\"text-align: left; border-bottom: 1px dashed #000; padding: 10px;\">{$email}</th>
				</tr>
				<tr>
					<td style=\"text-align: right; border-bottom: 1px dashed #000; padding: 10px;\">Telefon:</td>
					<th style=\"text-align: left; border-bottom: 1px dashed #000; padding: 10px;\">{$phone}</th>
				</tr>
				<tr>
					<td style=\"text-align: right; vertical-align: top; padding: 10px;\">Rezervované položky:</td>
					<th style=\"text-align: left; padding: 10px;\">
						<ul style=\"margin: 0; padding: 0 0 0 15px;\">
							<li>".implode("</li><li>", $items)."</li>
						</ul>
					</th>
				</tr>
				</tbody>
			</table>
		</td>
	</tr>
	</tbody>
</table>
");
		Zkusebna::sendMail(Admin::getEmail(), 'Nová rezervace', "
<table style=\"max-width: 600px; margin: 20px auto; color: #333; font-family: Arial, Helvetica, sans-serif; font-size: 17px;\">
	<tbody>
	<tr>
		<td>
			<h2 style=\"font-size: 30px; font-weight: 400; margin: 0 0 20px;\">Čauko,</h2>
			<p>na stránkách zkušebny vykvetla nová rezervace <strong>{$reservation_name}</strong> na jméno {$name} za ".($price * (100 - (int)$reservation->getDiscount()) / 100).",-</p>
			<p style=\"text-align: center; margin: 50px auto;\">Tak se co nejdřív mrkni o co jde</p>
		</td>
	</tr>
	</tbody>
</table>
");

		$output = array(
			"result" => "success",
			"heading" => "Rezervace byla odeslána",
			"message" => "Nyní čeká na schválení. Až se tak stane, pošleme vám zprávu na uvedený email. Zatím si můžete v emailové schránce zrekapitulovat rezervaci."
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