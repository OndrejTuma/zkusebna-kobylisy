<?php
header('Content-Type: application/json');

include_once("../inc/bootstrap.php");

$date_from = isset($_POST["date_from"]) ? $_POST["date_from"] : "";
$date_to = isset($_POST["date_to"]) ? $_POST["date_to"] : "";
$email = isset($_POST["email"]) ? $_POST["email"] : "";
$name = isset($_POST["name"]) ? $_POST["name"] : "";
$phone = isset($_POST["phone"]) ? $_POST["phone"] : "";

$zkusebna = new Zkusebna();
$person = new Person($email, $name, $phone);
$reservation = new Reservation($date_from, $date_to, $person->getID());

echo json_encode($reservation->getReservedItems($date_from, $date_to));

?>