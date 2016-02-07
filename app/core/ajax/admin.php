<?php
header('Content-Type: application/json');

include_once("../inc/bootstrap.php");

$action = isset($_POST["action"]) ? $_POST["action"] : "";
$reservationId = isset($_POST["reservationId"]) ? $_POST["reservationId"] : "";

$admin = new Admin();

switch ($action) {
	case "approve":
		$admin->approveReservation($reservationId);
		break;
	case "unapprove":
		$admin->unapproveReservation($reservationId);
		break;
	default:
}

$unapproved = $admin->getUnapprovedReservations();
if (count($unapproved)) {
	$output = "<ol>";
	foreach ($admin->getUnapprovedReservations() as $id => $reservation) {
		$output .= "<li data-id='{$id}'><strong class='expandable'>{$reservation["who"]}</strong> <small>" . Zkusebna::parseSQLDate($reservation['date_from']) . " - " . Zkusebna::parseSQLDate($reservation['date_to']) . "</small> <span class='tooltip icon-mobile' data-message='{$reservation["phone"]}'></span><span class='icon-mail tooltip' data-message='{$reservation["email"]}'></span> <i class='approve icon-checkmark tooltip' data-message='Schválit rezervaci'></i><i class='unapprove icon-close tooltip' data-message='Zanmítnout rezervaci'></i> <ul>";
		foreach ($reservation["items"] as $item) {
			$output .= "<li>{$item["name"]}</li>";
		}
		$output .= "</ul></li>";
	}
	$output .= "</ol>";
}
else {
	$output = "<p><i>Všechno jsi odbavil</i></p>";
}



echo json_encode(array(
	"html" => $output
));

?>