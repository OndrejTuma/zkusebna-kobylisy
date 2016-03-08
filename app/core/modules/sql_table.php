<?php

/**
 * třída pracuje se zadanou tabulkou a umožňuje
 * vybírat, přidávat, mazat a editovat záznamy
 *
 * @version 1.1
 * @copyright Ondřej Tůma 2010
 */

class sql_table extends mysql {

	private $table_name;

	/**
	 * konstruktor uloží název MySQL tabulky
	 * @param string	$table_name	název tabulky
	 */
	function __construct($table_name){
		parent::__construct();
		$this->table_name = $table_name;
	}

	/**
	* funkce přidá záznam do tabulky
	* @param array	$set	asociované pole záznamů ve formátu sloupec_tabulky=hodnota
	* @return int	id posledního vkládaného záznamu
	*/
	public function add(array $set){
		return $this->modify($this->table_name, $set);
	}

	/**
	* funkce smaže záznam z tabulky podle zadaných kritérií
	* @param string	$conditions	řetězec podmínek následující po klauzuli WHERE
	*/
	public function delete($conditions){
		$this->modify($this->table_name,array(),$conditions,true);
	}

	/**
	* funkce upraví záznam(y) v tabulce podle zadaných kritérií
	* @param array	$set		asociované pole záznamů ve formátu sloupec_tabulky=hodnota
	* @param string	$conditions	řetězec podmínek následující po klauzuli WHERE
	*/
	public function update(array $set,$conditions){
		$this->modify($this->table_name,$set,$conditions);
	}

	/**
	* funkce vybere záznam(y) z tabulky podle zadaných kritérií
	* @param string	$conditions	řetězec podmínek následující po klauzuli WHERE
	* @return array	číselné pole asociovaných/číselných polí všech vybraných záznamů
	*/
	public function get($conditions = ""){
		if (!empty($conditions)) {
			return mysql::field_assoc("SELECT * FROM ".$this->table_name." WHERE $conditions");
		}
		else {
			return mysql::field_assoc("SELECT * FROM ".$this->table_name);
		}
	}

	/**
	 * tato funkce upravuje záznamy tabulky
	 *
	 * @param string	$table		název tabulky
	 * @param array		$set		asociované pole záznamů ve formátu sloupec_tabulky=hodnota
	 * @param string	$condition	řetězec podmínek následující po klauzuli WHERE
	 * @param bool	$delete		TRUE, pokud chceme záznam smazat
	 * @return int id posledního vloženého záznamu
	 */
	public function modify($table, array $set, $condition = "", $delete = FALSE) {
		if($delete) {
			mysql::query("DELETE FROM $table WHERE $condition");
		}

		// vložení úvozovek do řetězce
		array_walk($set, create_function('&$a', 'if(isset($a)): $a = "\'".AddSlashes($a)."\'"; else: $a = "NULL"; endif;'));

		if(!Empty($condition)) {
			array_walk($set, create_function('&$val, $key', '$val = "$key = $val";'));
			mysql::query("UPDATE $table SET " . implode(", ", $set) . " WHERE $condition");
		}
		else {
			mysql::query("INSERT INTO $table (" . implode(", ", array_keys($set)) . ") VALUES (" . implode(", ", $set) . ")");
			return mysql::insert_id();
		}
	}

	/**
	* destruktor třídy zruší MySQL spojení
	*/
	function __destruct(){

	}
}
?>