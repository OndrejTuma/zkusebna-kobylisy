<?php

function __autoload($className){
	$file = ZKUSEBNA_MODULES_URL.$className.".php";
	if (file_exists($file)) {
		include_once($file);
	}
	else {
		$textLog = new logger(ZKUSEBNA_LOGS_URL);
		$textLog->log("Nepodařilo se vložit soubor '{$file}'. Soubor nenalezen");
	}
}

define("ZKUSEBNA_ROOT_URL", $_SERVER["DOCUMENT_ROOT"] . "app/");
define("ZKUSEBNA_APACHE_ROOT_URL", "/");

define("ZKUSEBNA_INC_URL", ZKUSEBNA_ROOT_URL . "core/inc/");
define("ZKUSEBNA_MODULES_URL", ZKUSEBNA_ROOT_URL . "core/modules/");
define("ZKUSEBNA_LOGS_URL", ZKUSEBNA_ROOT_URL . "logs/");
define("ZKUSEBNA_PAGES_URL", ZKUSEBNA_ROOT_URL . "pages/");

define("ZKUSEBNA_IMAGES_URL", ZKUSEBNA_APACHE_ROOT_URL . "public/dist/images/");
define("ZKUSEBNA_CSS_URL", ZKUSEBNA_APACHE_ROOT_URL . "public/dist/css/");
define("ZKUSEBNA_JS_URL", ZKUSEBNA_APACHE_ROOT_URL . "public/dist/js/");

include_once(ZKUSEBNA_MODULES_URL . "logger.php");

?>