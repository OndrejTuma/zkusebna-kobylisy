<?php
include_once("../inc/bootstrap.php");

$date_from = isset($_POST["date_from"]) ? $_POST["date_from"] : "";
$date_to = isset($_POST["date_to"]) ? $_POST["date_to"] : "";
$email = isset($_POST["email"]) ? $_POST["email"] : "";

$items = new Items();

echo json_encode(array(
	"html" => $items->renderItems($date_from, $date_to, $email),
	"items" => $items->getItems()
));

?>