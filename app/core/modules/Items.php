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

	/**
	 * @param $item_ids array of item ids
	 * @return array item names
	 */
	public function getItemsById($item_ids) {
		$query = "SELECT * FROM {$this->table_names["items"]} WHERE id IN (" . implode(",", $item_ids) . ")";
		return $this->sql->field_assoc($query);
	}

	public function getSelectPurpose()
	{

		$output = "<select name=\"purpose\" id=\"purpose\">";
		$output .= "<option value=\"\" disabled selected>Účel rezervace</option>";

		$query = "SELECT * FROM {$this->table_names["purpose"]} ORDER BY title ASC";
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

		if ($is_admin) {
			$this->discount = 0;
		}
		elseif ($purpose_id > 0) {
			$discount_row = $this->sql->field_assoc("SELECT discount FROM {$this->table_names["purpose"]} WHERE id = {$purpose_id}");
			$this->discount = $discount_row ? $discount_row[0]["discount"] : null;
		}

		$query = "SELECT id, name as itemName, active, image as img, price, reservable, category, parent_id FROM {$this->table_names["items"]} WHERE active = 1 ORDER BY parent_id, itemName";
		$this->items = $this->sql->field_assoc($query);

		if (!$this->preview) {
			$reservation = new Reservation();
			$reservedItems = $reservation->getReservedItems($date_from, $date_to);
			$this->items = array_merge($this->items, $reservedItems);
		}

		$output = $this->preview ? "" : "";	//preview is mode without prices

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
				$output .= $item["img"] ? "<a href='" . ZKUSEBNA_IMAGES_URL ."uploaded/". $item["img"] . "' class='magnific image'><img src='" . ZKUSEBNA_IMAGES_URL ."uploaded/". $item["img"] . "' alt='{$item["itemName"]}'/></a>" : "";
				$output .= "<strong>";
				if ($this->is_admin) {
					$output .= "<span data-table='items' data-column='name' data-id='{$item["id"]}' class='editable'>{$item["itemName"]}</span> ";
					$output .= isset($this->discount) ? "<span class='price'><span data-table='items' data-column='price' data-id='{$item["id"]}' class='editable'>" . $this->_getItemPrice($item) . "</span>,-</span>" : "";
					$output .= "<i data-table='items' data-id='{$item["id"]}' data-parent='li:first' class='deletable trigger icon-close tooltip' data-message='Smazat položku'></i>";
					$output .= "<i data-toggle-0-class='icon-toggle-off' data-toggle-1-class='icon-toggle-on' data-toggle-0-message='Označit položku jako aktivní' data-toggle-1-message='Označit položku jako neaktivní' data-table='items' data-column='active' data-id='{$item["id"]}' class='toggleable tooltip icon-toggle-".($item["active"] ? "on" : "off")."' data-message='".($item["active"] ? "Označit položku jako neaktivní" : "Označit položku jako aktivní")."'></i>";
					$output .= "<i data-id='{$item["id"]}' class='icon-image add-image tooltip' data-message='Přidat obrázek'></i>";
				} else {
					$output .= "{$item["itemName"]} ";
					$output .= isset($this->discount) ? "<span class='price'>" . $this->_getItemPrice($item) . ",-</span>" : "";
				}
				$output .= "</strong>";
			} else {
				$output .= $item["img"] ? "<a href='" . ZKUSEBNA_IMAGES_URL ."uploaded/". $item["img"] . "' class='magnific image'><img src='" . ZKUSEBNA_IMAGES_URL ."uploaded/". $item["img"] . "' alt='{$item["itemName"]}'/></a>" : "";
				$output .= "<strong class='reservable-item-{$item["id"]} reservable ";
				if (isset($item["reservation_name"]) && $item["reservation_name"]) {
					$output .= "already-reserved' data-name='{$item["reservation_name"]}' data-date-from='" . Zkusebna::parseSQLDate($item["start"]) . "' data-date-to='" . Zkusebna::parseSQLDate($item["end"]) . "'";
				} else {
					$output .= "' ";
				}
				$output .= "data-id='{$item["id"]}'>";
				$output .= "{$item["itemName"]}";
				$output .= "<i class='icon-plus'></i> ";
				$output .= isset($this->discount) ? "<span class='price'>" . $this->_getItemPrice($item) . ",-</span>" : "";
				$output .= "</strong>";
			}
		} else {
			$output .= $item["img"] ? "<a href='" . ZKUSEBNA_IMAGES_URL ."uploaded/". $item["img"] . "' class='magnific image'><img src='" . ZKUSEBNA_IMAGES_URL ."uploaded/". $item["img"] . "' alt='{$item["itemName"]}'/></a>" : "";
			$output .= "<strong class='expandable'>";
			if ($this->is_admin) {
				$output .= "<span data-id='{$item["id"]}' data-table='items' data-column='name' class='editable trigger'>{$item["itemName"]}</span> ";
				$output .= "<i data-table='items' data-parent_id='{$item["id"]}' data-category='{$item["category"]}' class='add-new-item trigger icon-plus'></i>";
				$output .= "<i data-table='items' data-id='{$item["id"]}' data-parent='li:first' class='deletable trigger icon-close'></i>";
				$output .= "<span class='select-all trigger'>Vybrat vše</span>";

			} else {
				$output .= $item["itemName"];
			}
			$output .= "</strong>";
		}
		return $output;
	}

}


?>