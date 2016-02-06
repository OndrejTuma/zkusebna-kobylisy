 <?php

class Items extends Zkusebna {

	function __construct() {
		parent::__construct();
		$this->categories = array(
			"zkusebna" => array(),
			"technika" => array(),
			"nastroje" => array()
		);
		$this->categories_names = array(
			"zkusebna" => "Zkušebna",
			"technika" => "Technika",
			"nastroje" => "Nástroje"
		);
		$this->items = array();
	}

	/**
	 * Gets items in array returned from rennderItems function
	 * @return array
	 */
	public function getItems() {

		$output = array();
		foreach ($this->items as $item) {
			if ($item['reservable']) $output[$item['id']] = $item;
		}
		return $output;
	}

	public function renderItems($date_from, $date_to, $email) {

		$this->preview = !($date_from && $date_to);

		$this->date_from = $this->_parseDate($date_from);
		$this->date_to = $this->_parseDate($date_to);
		$this->email = $email;

		if ($this->preview) {
			$query = "SELECT * FROM {$this->table_names["items"]} ORDER BY category, parent_id, name";
		}
		else {
			$query = "
SELECT i.id AS id, i.name AS name, image, price, category, parent_id, reservable, reservation.name AS reserved_by, email, date_from, date_to, confirmed
FROM {$this->table_names["items"]} AS i
LEFT JOIN
(
SELECT c.name, c.email, r.confirmed, ri.item_id, date_from, date_to FROM {$this->table_names["reservations"]} AS r
LEFT JOIN {$this->table_names["community"]} AS c ON r.who = c.id
LEFT JOIN  {$this->table_names["r-i"]} AS ri ON r.id = ri.reservation_id
WHERE (date_from > '{$this->date_from}' OR date_to > '{$this->date_from}') AND (date_from < '{$this->date_to}' OR date_to < '{$this->date_to}')
) AS reservation
ON i.id = reservation.item_id
ORDER BY category, parent_id, i.name";
		}

		$output = $this->preview ? "" : "";

		$this->items = $this->sql->field_assoc($query);

		foreach ($this->items as $item) {
			if ($item["parent_id"]) {
				$this->categories[$item["category"]][$item["parent_id"]]["children"][$item["id"]] = $item;
			}
			else {
				$this->categories[$item["category"]][$item["id"]] = $item;
			}
		}

		$output .= "<ul id='item-list' class='" . ($this->preview ? "preview" : "") . "'>";
		foreach ($this->categories as $name => $category) {
			$output .= "<li class='{$name}'>";
			$output .= "<strong class='expandable cat " . (!count($category) ? "category-empty" : ($name == "zkusebna" ? " active" : "")) ."'>";
			$output .= $this->_renderCategoryName($name);
			$output .= "</strong>";
			if (count($category)) {
				$output .= $this->_renderSubcategories($category);
			}
			$output .= "</li>";
		}
		$output .= "</ul>";

		return $output;
	}
	
	
	private function _renderCategoryName($category_key) {
		return isset($this->categories_names[$category_key]) ? $this->categories_names[$category_key] : "<i>{$category_key}</i>";
	}
	private function _renderSubcategory($subcategory) {
		$output = "<li class='" . (!isset($subcategory["children"]) ? "item-empty" : "") . "'>";
		$output .= $this->_renderItem($subcategory);
		if (isset($subcategory["children"]) && count($subcategory["children"])) {
			$output .= $this->_renderItems($subcategory["children"]);
		}
		$output .= "</li>";

		return $output;
	}
	private function _renderSubcategories($category) {
		$output = "<ul class='subcategories'>";
		foreach ($category as $id => $subcategory) {
			$output .= $this->_renderSubcategory($subcategory);
		}
		$output .= "</ul>";

		return $output;

	}
	private function _renderItems($children) {
		$output = "<ul class='items'>";
		foreach ($children as $child) {
			$output .= "<li>";
			$output .= $this->_renderItem($child);
			$output .= "</li>";
		}
		$output .= "</ul>";

		return $output;
	}
	private function _renderItem($item) {
		$output = "";
		if ($item["reservable"] == 1) {
			if ($this->preview) {
				$output .= "<strong>{$item["name"]}</strong>";
			}
			else {
				$output .= "<strong class='reservable ";
				if ($item["reserved_by"] != null) {
					$output .= "already-reserved' data-name='{$item["reserved_by"]}' data-date-from='" . Zkusebna::parseSQLDate($item["date_from"]) . "' data-date-to='" . Zkusebna::parseSQLDate($item["date_to"]) . "'";
				}
				else {
					$output .= "' ";
				}
				$output .= "data-id='{$item["id"]}'>{$item["name"]}";
				$output .= "<i class='icon-plus'></i> <span>{$item["price"]},-</span></strong>";
			}
		}
		else {
			$output .= "<strong class='expandable'>{$item["name"]}</strong>";
		}
		return $output;
	}

}


?>