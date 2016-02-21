<?php
header('Content-Type: application/json');

include_once("../inc/bootstrap.php");

$action = isset($_POST["action"]) ? $_POST["action"] : "";

$admin = new Admin();


switch ($action) {
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
		$itemId = isset($_POST["itemId"]) ? $_POST["itemId"] : "";
		$column = isset($_POST["column"]) ? $_POST["column"] : "";
		$val = isset($_POST["val"]) ? $_POST["val"] : "";
		if (trim($val) === "") {
			$output["result"] = "failure";
		}
		$admin->updateItem($itemId, $column, $val);
		break;
	default:
}


$output["unapproved"] = $admin->renderUnapprovedReservations();
$output["approved"] = $admin->renderApprovedReservations();
$output["items"] = $admin->renderItems();


echo json_encode($output);

?>