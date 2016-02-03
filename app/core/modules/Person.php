<?php

class Person extends Zkusebna {

	private $email, $phone, $id;

	function __construct($email, $name, $phone) {

		parent::__construct();

		$this->email = $email;
		$this->name = $name;
		$this->phone = $phone;

		$this->id = $this->_createUserIfNotExists();
	}

	/**
	 * Looks for user credentials and if it doesn't exist, it creates it. Then returns its id. Otherwise id of existing user is returned
	 * @return int - existing user id or new created user id
	 */
	private function _createUserIfNotExists() {

		$query = "SELECT id FROM {$this->table_names["community"]} WHERE name = '{$this->name}' AND email = '{$this->email}' AND phone = '{$this->phone}'";
		$user = $this->sql->fetch_row($this->sql->query($query));

		if (!$user) {
			$query = "INSERT INTO {$this->table_names["community"]} (name, email, phone) VALUES ('{$this->name}', '{$this->email}', '{$this->phone}')";
			$this->sql->query($query);
			return $this->sql->insert_id();
		}

		return (int)$user['id'];
	}

	/**
	 * get user id
	 * @return int
	 */
	public function getID() {
		return $this->id;
	}

}

?>