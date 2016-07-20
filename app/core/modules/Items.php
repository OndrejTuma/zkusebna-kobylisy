<?php

class Items extends Zkusebna
{

	private $discount;

	function __construct()
	{
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
	public function getItems()
	{

		$output = array();
		foreach ($this->items as $item) {
			$item["discount"] = isset($this->discount) ? $this->discount : 0;
			if ($item['reservable']) $output[$item['id']] = $item;
		}
		return $output;

	}

	public function getSelectPurpose()
	{

		$output = "<select name=\"purpose\" id=\"purpose\">";
		$output .= "<option value=\"\" disabled selected>Účel rezervace</option>";

		$query = "SELECT * FROM {$this->table_names["purpose"]}";
		$purposes = $this->sql->field_assoc($query);
		foreach ($purposes as $purpose) {
			$output .= "<option value=\"{$purpose["id"]}\">{$purpose["title"]}</option>";
		}

		$output .= "</select>";

		return $output;
	}

	public function renderItems($date_from, $date_to, $email, $purpose_id, $active_categories = array(), $is_admin = false)
	{

		$this->preview = !($date_from && $date_to);
		$this->date_from = parent::_parseDate($date_from);
		$this->date_to = parent::_parseDate($date_to);
		$this->email = $email;
		$this->is_admin = $is_admin;

		if ($purpose_id > 0) {
			$discount_row = $this->sql->field_assoc("SELECT discount FROM {$this->table_names["purpose"]} WHERE id = {$purpose_id}");
			$this->discount = $discount_row ? $discount_row[0]["discount"] : null;
		}

		if ($this->preview) {
			$query = "SELECT id, name as itemName, image as img, price, reservable, category, parent_id FROM {$this->table_names["items"]} ORDER BY category, parent_id, name";
			$this->items = $this->sql->field_assoc($query);
		} else {
//			$query = "
//SELECT i.id AS id, i.name AS name, image, price, category, parent_id, reservable, reservation.name AS reserved_by, email, date_from, date_to
//FROM {$this->table_names["items"]} AS i
//LEFT JOIN
//(
//SELECT c.name, c.email, ri.item_id, date_from, date_to FROM {$this->table_names["reservations"]} AS r
//LEFT JOIN {$this->table_names["community"]} AS c ON c.id = r.who
//LEFT JOIN  {$this->table_names["r-i"]} AS ri ON ri.reservation_id = r.id
//WHERE (date_from > '{$this->date_from}' OR date_to > '{$this->date_from}') AND (date_from < '{$this->date_to}' OR date_to < '{$this->date_to}')
//) AS reservation ON reservation.item_id = i.id
//ORDER BY category, parent_id, i.name";
			$query = "SELECT id, name as itemName, image as img, price, reservable, category, parent_id FROM {$this->table_names["items"]} ORDER BY category, parent_id, name";
			$this->items = $this->sql->field_assoc($query);
			$reservation = new Reservation();
			$reservedItems = $reservation->getReservedItems($date_from, $date_to);
			$this->items = array_merge($this->items, $reservedItems);
		}

		$output = $this->preview ? "" : "";

//		var_dump($this->items);

		foreach ($this->items as $item) {
			if ($item["parent_id"]) {
				$this->categories[$item["category"]][$item["parent_id"]]["children"][$item["id"]] = $item;
			} else {
				$this->categories[$item["category"]][$item["id"]] = $item;
			}
		}
//		var_dump($this->categories);

		$output .= "<ul class='item-list " . ($this->preview ? "preview" : "") . "'>";
		foreach ($this->categories as $name => $category) {
			$output .= "<li class='{$name}'>";
			$output .= "<strong class='expandable cat " . (!count($category) ? "category-empty" : (in_array($name, $active_categories) ? " active" : "")) . "'>";
			$output .= $this->_renderCategoryName($name);
			$output .= "<i data-table='items' data-category='{$name}' class='add-new-item trigger icon-plus'></i>";
			$output .= "<span class='select-all trigger'>Vybrat vše</span>";
			$output .= "</strong>";
			if (count($category)) {
				$output .= $this->_renderSubcategories($category);
			}
			$output .= "</li>";
		}
		$output .= "</ul>";

		return $output;
	}


	private function _getItemPrice($item)
	{
		return isset($this->discount) ? round($item['price'] * (100 - $this->discount) / 100) : $item['price'];
	}

	private function _renderCategoryName($category_key)
	{
		return isset($this->categories_names[$category_key]) ? $this->categories_names[$category_key] : "<i>{$category_key}</i>";
	}

	private function _renderSubcategory($subcategory)
	{
		$output = "<li class='" . (!isset($subcategory["children"]) ? "item-empty" : "") . "'>";
		$output .= $this->_renderItem($subcategory);
		if (isset($subcategory["children"]) && count($subcategory["children"])) {
			$output .= $this->_renderItems($subcategory["children"]);
		}
		$output .= "</li>";

		return $output;
	}

	private function _renderSubcategories($category)
	{
		$output = "<ul class='subcategories'>";
		foreach ($category as $id => $subcategory) {
			$output .= $this->_renderSubcategory($subcategory);
		}
		$output .= "</ul>";

		return $output;

	}

	private function _renderItems($children)
	{
		$output = "<ul class='items'>";
		foreach ($children as $child) {
			$output .= "<li>";
			$output .= $this->_renderItem($child);
			$output .= "</li>";
		}
		$output .= "</ul>";

		return $output;
	}

	private function _renderItem($item)
	{
		$output = "";
		if ($item["reservable"] == 1) {
			if ($this->preview) {
				if ($this->is_admin) {
					$output .= "<strong><span data-table='items' data-column='name' data-id='{$item["id"]}' class='editable'>{$item["itemName"]}</span> ";
					$output .= isset($this->discount) ? "<span class='price'><span data-table='items' data-column='price' data-id='{$item["id"]}' class='editable'>" . $this->_getItemPrice($item) . "</span>,-</span>" : "";
					$output .= "<i data-table='items' data-id='{$item["id"]}' data-parent='li:first' class='deletable trigger icon-close'></i>";
					$output .= "</strong>";
				} else {
					$output .= "<strong>{$item["itemName"]} ";
					$output .= isset($this->discount) ? "<span class='price'>" . $this->_getItemPrice($item) . ",-</span>" : "";
					$output .= "</strong>";
				}
			} else {
				$output .= "<strong class='reservable-item-{$item["id"]} reservable ";
				if (isset($item["reserved_by"]) && $item["reserved_by"]) {
					$output .= "already-reserved' data-name='{$item["reserved_by"]}' data-date-from='" . Zkusebna::parseSQLDate($item["start"]) . "' data-date-to='" . Zkusebna::parseSQLDate($item["end"]) . "'";
				} else {
					$output .= "' ";
				}
				$output .= "data-id='{$item["id"]}'>{$item["itemName"]}";
				$output .= "<i class='icon-plus'></i> ";
				$output .= isset($this->discount) ? "<span class='price'>" . $this->_getItemPrice($item) . ",-</span>" : "";
				$output .= "</strong>";
			}
		} else {
			if ($this->is_admin) {
				$output .= "<strong class='expandable'><span data-id='{$item["id"]}' data-table='items' data-column='name' class='editable trigger'>{$item["itemName"]}</span> ";
				$output .= "<i data-table='items' data-parent_id='{$item["id"]}' data-category='{$item["category"]}' class='add-new-item trigger icon-plus'></i>";
				$output .= "<i data-table='items' data-id='{$item["id"]}' data-parent='li:first' class='deletable trigger icon-close'></i>";
				$output .= "<span class='select-all trigger'>Vybrat vše</span>";
				$output .= "</strong>";

			} else {
				$output .= "<strong class='expandable'>{$item["itemName"]}</strong>";
			}
		}
		return $output;
	}

}


?>