<?php
header('Content-Type: application/json');

include_once("../inc/bootstrap.php");

$date_from = isset($_POST["date_from"]) ? $_POST["date_from"] : "";
$date_to = isset($_POST["date_to"]) ? $_POST["date_to"] : "";
$email = isset($_POST["email"]) ? $_POST["email"] : "";

$zkusebna = new Zkusebna();

echo json_encode($zkusebna->getReservedItems($date_from, $date_to, $email));

?>