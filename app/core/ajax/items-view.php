<?php
session_start();
include_once("../inc/bootstrap.php");

$date_from = isset($_POST["date_from"]) ? $_POST["date_from"] : "";
$date_to = isset($_POST["date_to"]) ? $_POST["date_to"] : "";
$email = isset($_POST["email"]) ? $_POST["email"] : "";
$purpose_id = isset($_POST["purpose"]) ? (int)$_POST["purpose"] : "";

$items = new Items();

$auth = new AuthAdmin();

echo json_encode(array(
	"html" => $items->renderItems($date_from, $date_to, $email, $purpose_id, array('zkusebna'), $auth->is_logged()),
	"items" => $items->getItems()
));

?>