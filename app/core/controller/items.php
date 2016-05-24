<?php

/**
 * @param $sql mysql
 * @param $table_name string
 */
function render_items($sql, $table_name) {

	$output = "";
	$icon_add = "<i class='icon-plus'></i>";
	$categories = array(
		"zkusebna" => array(),
		"technika" => array(),
		"nastroje" => array()
	);
	$categories_names = array(
		"zkusebna" => "Zkušebna",
		"technika" => "Technika",
		"nastroje" => "Nástroje"
	);

	foreach ($sql->field_assoc("SELECT * FROM {$table_name} ORDER BY category, parent_id" ) as $item) {
		if ($item["parent_id"]) {
			$categories[$item["category"]][$item["parent_id"]]["children"][$item["id"]] = $item;
		}
		else {
			$categories[$item["category"]][$item["id"]] = $item;
		}
	}
	$output .= "<ul id='item-list'>\n";
	foreach ($categories as $name => $category) {
		$output .= "\t<li class='{$name} ".(!count($category) ? "category-empty" : "") . " " . ($name == "zkusebna" ? "active" : "") ."'><strong>" . (isset($categories_names[$name]) ? $categories_names[$name] : "<strong>{$name}</strong>") . "</strong>";
		if (count($category)) {
			$output .= "\n\t<ul>\n";
			foreach ($category as $id => $item) {
				$output .= "\t\t<li class='" . ($item["reservable"] == 1 ? "reservable" : (!isset($item["children"]) ? "item-empty" : "")) . "'><strong>{$item["name"]}" . ($item["reservable"] == 1 ? $icon_add : "") . "</strong>";
				if (isset($item["children"]) && count($item["children"])) {
					$output .= "\n\t\t<ul>\n";
					foreach ($item["children"] as $child_item) {
						$output .= "\t\t\t<li class='" . ($child_item["reservable"] == 1 ? "reservable" : "") . "'><strong>{$child_item["name"]}" . ($child_item["reservable"] == 1 ? $icon_add : "") . "</strong></li>\n";
					}
					$output .= "\t\t</ul>";
				}
				$output .= "\t\t</li>\n";
			}
			$output .= "\t</ul>\n";
		}
		$output .= "\t</li>\n";
	}
	$output .= "</ul>\n";

	return $output;

}

?>