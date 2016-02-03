<?php

include_once("app/core/inc/bootstrap.php");

$sql = new mysql();

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8"/>
	<title>Zkušebna Kobylisy</title>
	<link rel="stylesheet" href="<?= ZKUSEBNA_CSS_URL; ?>site.css"/>
</head>
<body>
<noscript>
	<p>Bez javascriptu se na této stránce nepohnete</p>
</noscript>

<?php

const PAGES_URL = "app/pages/";

$page = isset($_GET["page"]) ? $_GET["page"] : "homepage";

switch ($page) {
	case "reserve": 	include_once(PAGES_URL . "reserve.php"); break;
	case "admin": 		include_once(PAGES_URL . "admin.php"); break;
	default: 			include_once(PAGES_URL . "homepage.php");
}
?>

<script type="text/javascript" src="<?= ZKUSEBNA_JS_URL; ?>all.js"></script>
</body>
</html>