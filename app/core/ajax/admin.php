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
		$admin->deleteReservation($reservationId);
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
	default:
}


$output["unapproved"] = $admin->renderUnapprovedReservations();
$output["approved"] = $admin->renderApprovedReservations();
$output["repeated"] = $admin->renderRepeatedReservations();
$output["purpose"] = $admin->renderPurposes();
$output["items"] = $admin->renderItems();


echo json_encode($output);

?>