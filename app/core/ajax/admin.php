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
			$target_dir = ZKUSEBNA_ROOT_URL . "../public/dist/images/uploaded/";
			$target_file = $target_dir . basename($image["name"]);
			$uploadOk = 1;
			$imageFileType = pathinfo($target_file, PATHINFO_EXTENSION);
			$allowed_types = array('jpg','jpeg','png','gif');
			$max_filesize = 500000;

// Check if image file is a actual image or fake image
			$check = getimagesize($image["tmp_name"]);
			if($check !== false) {
				$uploadOk = 1;
			} else {
				$uploadOk = 0;
			}
// Check if file already exists
			if (file_exists($target_file)) {
				$error =  "Obrázek s tímto názvem ({$image["name"]}) už existuje. Nepřepisujeme:(";
				$uploadOk = 0;
			}
// Check file size
			if ($image["size"] > $max_filesize) {
				$error = "Soubor nesmí být větší než " . round($max_filesize/1000) . "MB";
				$uploadOk = 0;
			}
// Allow certain file formats
			if(!in_array($imageFileType, $allowed_types)) {
				$error = "Povolené formáty obrázku jsou: " . implode(",", $allowed_types);
				$uploadOk = 0;
			}
// Check if $uploadOk is set to 0 by an error
			if ($uploadOk == 0) {
				$output["result"] = false;
				$output["errorMessage"] = $error;
// if everything is ok, try to upload file
			} else {
				if (move_uploaded_file($image["tmp_name"], $target_file)) {
					$output["result"] = $admin->addItem($name, $image["name"], $price, $reservable, $category, $parent_id);
					if (!$output["result"]) {
						$output["errorMessage"] = "Nepodařilo se přidat položku do databáze";
					}
				} else {
					$output["result"] = false;
					$output["errorMessage"] = "Sorry, there was an error uploading your file.";
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
		$Reservation = new Reservation();
		$reservation = $Reservation->getReservationById($reservationId);
		
		if (!$reservationId) {
			$output["error"] = "Rezervaci se podle id nepodařilo najít";
			break;
		}

		$items = new Items();
		$items = $items->getItemsById($Reservation->getReservationItems($reservationId));
		$price = array_reduce($items, function($carry, $item) { return $carry + $item['price']; });
		array_walk($items, function(&$item) { $item = $item["name"]; });
		
		Zkusebna::sendMail($reservation["email"], "Rezervace byla změněna", "
<table style=\"max-width: 600px; margin: 20px auto; color: #333; font-family: Arial, Helvetica, sans-serif; font-size: 17px;\">
	<tbody>
	<tr>
		<td>
			<h2 style=\"font-size: 30px; font-weight: 400; margin: 0 0 20px;\">Dobrý den,</h2>
			<p>Vaše rezervace <strong style=\"color: #cc2229;\">{$reservation["reservation_name"]}</strong> byla upravena správcem zkušebny.</p>
			<p><strong>Zkontrolujte si prosím položky rezervace a její cenu</strong></p>
			<p>Pokud to není vaše rezervace, napište správci zkušebny odpovědí na tento email.</p>
			<table class=\"list\" cellspacing=\"0\" cellpadding=\"0\" style=\"margin: 50px auto; color: #333; font-family: Arial, Helvetica, sans-serif; font-size: 17px; background: #efefef; padding: 30px; box-shadow: inset 0 0 5px 5px #fff;\">
				<tbody>
				<tr>
					<td style=\"text-align: right; border-bottom: 1px dashed #000; padding: 10px;\">Cena:</td>
					<th style=\"text-align: left; border-bottom: 1px dashed #000; padding: 10px;\">".($price * (100 - (int)$Reservation->getDiscount($reservationId)) / 100)."</th>
				</tr>
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