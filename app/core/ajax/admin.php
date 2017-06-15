<?php
session_start();
header('Content-Type: application/json');

include_once("../inc/bootstrap.php");

$auth = new AuthAdmin();

if (!$auth->is_logged()) return '401 Unauthorized';

$action = isset($_POST["action"]) ? $_POST["action"] : "";

$admin = new Admin();

switch ($action) {
	case "addItem":
		$name = isset($_POST["name"]) ? $_POST["name"] : "";
		$price = isset($_POST["price"]) ? $_POST["price"] : "";
		$reservable = isset($_POST["reservable"]) ? 1 : 0;
		$category = isset($_POST["category"]) ? $_POST["category"] : "";
		$image = isset($_FILES["image"]) ? $_FILES["image"] : "";
		$parent_id = isset($_POST["parent_id"]) ? $_POST["parent_id"] : "";

		if (empty($image["name"])) {
			$output["result"] = $admin->addItem($name, "", $price, $reservable, $category, $parent_id);
			if (!$output["result"]) {
				$output["errorMessage"] = "Nepodařilo se přidat položku do databáze";
			}
		}
		else {
			$upload = new Upload($image);
			if ($upload->uploaded) {
				$upload->image_resize = true;
				$upload->image_x = 800;
				$upload->file_overwrite = true;
				$upload->image_ratio_y = true;
				$upload->Process(ZKUSEBNA_ROOT_URL . "../public/dist/images/uploaded/");
				if ($upload->processed) {
					$output["result"] = $admin->addItem($name, $image["name"], $price, $reservable, $category, $parent_id);
					$upload->Clean();
				} else {
					$output["result"] = false;
					$output["errorMessage"] = "Stala se chyba: " . $upload->error;
				}
			}
		}

		break;
	case "addImage":
		$image = isset($_FILES["image"]) ? $_FILES["image"] : "";
		$item_id = isset($_POST["item_id"]) ? $_POST["item_id"] : "";

		if (empty($image["name"])) {
			$output["errorMessage"] = "Není vyplněný obrázek";
		}
		else {
			// https://github.com/verot/class.upload.php/blob/master/README.md
			$upload = new Upload($image);
			if ($upload->uploaded) {
				$upload->image_resize = true;
				$upload->image_x = 800;
				$upload->file_overwrite = true;
				$upload->image_ratio_y = true;
				$upload->Process(ZKUSEBNA_ROOT_URL . "../public/dist/images/uploaded/");
				if ($upload->processed) {
					$output["result"] = $admin->addImage($image["name"], $item_id);

					$upload->Clean();
				} else {
					$output["result"] = false;
					$output["errorMessage"] = "Stala se chyba: " . $upload->error;
				}
			}
		}

		break;
	case "addPurpose":
		$purpose = isset($_POST["purpose"]) ? $_POST["purpose"] : "";
		$discount = isset($_POST["discount"]) ? $_POST["discount"] : "";
		$output["result"] = $admin->addPurpose($purpose, $discount);
		break;
	case "approve":
		$reservationId = isset($_POST["reservationId"]) ? $_POST["reservationId"] : "";
		$admin->approveReservation($reservationId);
		break;
	case "delete":
		$reservationId = isset($_POST["reservationId"]) ? $_POST["reservationId"] : "";
		$reason = isset($_POST["reason"]) ? trim($_POST["reason"]) : "";
		$admin->deleteReservation($reservationId, $reason);
		break;
	case "deleteItem":
		$reservationId = isset($_POST["reservationId"]) ? $_POST["reservationId"] : "";
		$itemId = isset($_POST["itemId"]) ? $_POST["itemId"] : "";
		$admin->deleteReservationItem($itemId, $reservationId);
		break;
	case "updateItem":
		$table = isset($_POST["table"]) ? $_POST["table"] : "";
		$itemId = isset($_POST["itemId"]) ? $_POST["itemId"] : "";
		$column = isset($_POST["column"]) ? $_POST["column"] : "";
		$val = isset($_POST["val"]) ? $_POST["val"] : "";
		if (trim($val) === "") {
			$output["result"] = "failure";
		} else {
			$admin->updateItem($table, $itemId, $column, $val);
		}
		break;
	case "deleteIt":
		$table = isset($_POST["table"]) ? $_POST["table"] : "";
		$itemId = isset($_POST["itemId"]) ? (int)$_POST["itemId"] : "";
		if (!$admin->deleteItem($table, $itemId)) {
			$output["result"] = "failure";
			$output["message"] = "Chyba, položku se nepodařilo smazat.";
		}
		break;
	case "toggleIt":
		$table = isset($_POST["table"]) ? $_POST["table"] : "";
		$itemId = isset($_POST["itemId"]) ? (int)$_POST["itemId"] : "";
		$column = isset($_POST["column"]) ? $_POST["column"] : "";
		$toggleResult = $admin->toggleItem($table, $itemId, $column);
		if ($toggleResult === false) {
			$output["result"] = "failure";
			$output["message"] = "Chyba, položku se nepodařilo upravit.";
		}
		else {
			$output["toggleResult"] = $toggleResult;
		}
		break;
	case "changePurpose":
		$reservationId = isset($_POST["reservationId"]) ? (int)$_POST["reservationId"] : "";
		$purposeId = isset($_POST["purposeId"]) ? (int)$_POST["purposeId"] : "";
		
		if (!$admin->changePurpose($reservationId, $purposeId)) {
			$output["error"] = "Chyba, rezervaci se nepodařilo upravit.";
		}
		break;
	case "emailReservationChange":

		$reservationId = isset($_POST["reservationId"]) ? (int)$_POST["reservationId"] : "";
		$reason = isset($_POST["reason"]) ? $_POST["reason"] : "";
		$Reservation = new Reservation();
		$reservation = $Reservation->getReservationById($reservationId);
		
		if (!$reservationId) {
			$output["error"] = "Rezervaci se podle id nepodařilo najít";
			break;
		}

		$items = new Items();
		$items = $items->getItemsById($Reservation->getReservationItems($reservationId));

		$reduction = (100 - (float)$Reservation->getDiscount($reservationId)) / 100;
		$price_total = 0;
		foreach ($items as $item) {
			$price_total += round($item['price'] * $reduction);
		}

		array_walk($items, function(&$item) { $item = $item["name"]; });

		Zkusebna::sendMail($reservation["email"], "Rezervace byla změněna", "
<table style=\"max-width: 600px; margin: 20px auto; color: #333; font-family: Arial, Helvetica, sans-serif; font-size: 17px;\">
	<tbody>
	<tr>
		<td>
			<h2 style=\"font-size: 30px; font-weight: 400; margin: 0 0 20px;\">Dobrý den,</h2>
			<p>vaše rezervace <strong style=\"color: #cc2229;\">{$reservation["reservation_name"]}</strong> byla upravena správcem zkušebny.</p>
			".($reason ? "<h4 style='margin: 30px 0; text-align: center;'>$reason</h4>" : "")."
			<p>Zkontrolujte si prosím položky a cenu rezervace.</p>
			<br>
			<p>Pokud to není vaše rezervace, napište správci zkušebny odpovědí na tento email.</p>
			<table class=\"list\" cellspacing=\"0\" cellpadding=\"0\" style=\"margin: 50px auto; color: #333; font-family: Arial, Helvetica, sans-serif; font-size: 17px; background: #efefef; padding: 30px; box-shadow: inset 0 0 5px 5px #fff;\">
				<tbody>
				<tr>
					<td style=\"text-align: right; border-bottom: 1px dashed #000; padding: 10px;\">Cena:</td>
					<th style=\"text-align: left; border-bottom: 1px dashed #000; padding: 10px;\">{$price_total},-</th>
				</tr>
				".($price_total > 0 ? "
				<tr>
					<td colspan='2' style='padding: 10px;'>Platbu poukazujte na účet číslo <strong>".Zkusebna::getFormattedAccountNumber()."</strong> (preferujeme), <strong>do zprávy pro příjemce napište název akce ({$reservation["reservation_name"]})</strong>.<br>Nebo hotově správci zkušebny.</td>
				</tr>
				<tr>
					<td colspan='2' style='text-align: center; border-top: 1px dashed #000; padding: 10px;'>
						Případně, pokud používáte mobilní aplikaci internetového bankovnictví, můžete zaplatit přes následující QR kód:<br>
						<img src='".Zkusebna::getPaymentQRCodeSrc($price_total, $reservation_name)."' alt='QR platba' width='150'>
					</td>
				</tr>
				" : "")."
				<tr>
					<td style=\"text-align: right; border-bottom: 1px dashed #000; padding: 10px;\">Datum:</td>
					<th style=\"text-align: left; border-bottom: 1px dashed #000; padding: 10px;\">".Zkusebna::parseSQLDate($reservation["date_from"])." - ".Zkusebna::parseSQLDate($reservation["date_to"])."</th>
				</tr>
				<tr>
					<td style=\"text-align: right; border-bottom: 1px dashed #000; padding: 10px;\">Jméno:</td>
					<th style=\"text-align: left; border-bottom: 1px dashed #000; padding: 10px;\">{$reservation["name"]}</th>
				</tr>
				<tr>
					<td style=\"text-align: right; border-bottom: 1px dashed #000; padding: 10px;\">Email:</td>
					<th style=\"text-align: left; border-bottom: 1px dashed #000; padding: 10px;\">{$reservation["email"]}</th>
				</tr>
				<tr>
					<td style=\"text-align: right; border-bottom: 1px dashed #000; padding: 10px;\">Telefon:</td>
					<th style=\"text-align: left; border-bottom: 1px dashed #000; padding: 10px;\">{$reservation["phone"]}</th>
				</tr>
				<tr>
					<td style=\"text-align: right; vertical-align: top; border-bottom: 1px dashed #000; padding: 10px;\">Rezervované položky:</td>
					<th style=\"text-align: left; border-bottom: 1px dashed #000; padding: 10px;\">
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

		break;
	default:
}


$output["unapproved"] = $admin->renderUnapprovedReservations();
$output["approved"] = $admin->renderApprovedReservations();
$output["repeated"] = $admin->renderRepeatedReservations();
$output["purpose"] = $admin->renderPurposes();
$output["items"] = $admin->renderItems();


echo json_encode($output);

?>