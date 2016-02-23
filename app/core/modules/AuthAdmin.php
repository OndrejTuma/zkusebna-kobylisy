<?php
/**
 * Třída pro přihlašování do administrátorské sekce, vyžaduje třídu sql_table.
 * Před použitím je třeba si nakonfigurovat definované kontanty (většinou názvy sloupců v MySQL tabulce)
 *
 * @version 2.0
 * @copyright Magnum 2010
 */
define("LOGIN_TYPE","session"); // cookie/session
define("DURATION_OF_INACTIVITY", 60*60); // hodina nečinnosti a systém administrátora automaticky odhlásí
define("COOKIE_EXPIRATION", 60*60);	//doba přihlášení "na věčnost" - 1 rok
define("SESSION_NAME","user_login_status");	//název relace

/* nastavit! */
define("TABLE_NAME","zkusebna_admin");	//název tabulky, kde jsou informace o administátorech
define("COL_NAME","name");	//název sloupce, kam se ukládá uživatelské jméno
define("COL_PASS","passwd");	//název sloupce, kam se ukládá uživatelské heslo
define("COL_HASH","hash");	//název sloupce, kam se ukládá unikátní hash
define("COL_TIME","login_time");	//název sloupce, kam se ukládá čas přihlášení (vkládá se přímo hodnota time() - opravit)

class AuthAdmin {

	private $forever;	//přihlášení na věčnost
	private $admin;
	private $name;
	private $COL_NAME = COL_NAME;
	private $COL_PASS = COL_PASS;
	private $COL_HASH = COL_HASH;
	private $COL_TIME = COL_TIME;
	private $sql_relace;
	private $table_name = TABLE_NAME;
	private $types = array("cookie","session");
	private $type = LOGIN_TYPE;
	private $hash;

	/**
	* vytvoří sql relaci a zjistí, zda je administrátor už přihlášený
	*/
	function __construct(){

		$this->sql_relace = new sql_table($this->table_name);

		$this->hash = $this->getHash();

		$this->admin = $this->get_current_admin();

	}

	private function getHash() {
		if ($this->type=="cookie" && isset($_COOKIE[SESSION_NAME])) {
			return $_COOKIE[SESSION_NAME];
		}
		elseif ($this->type=="session" && isset($_SESSION[SESSION_NAME])) {
			return $_SESSION[SESSION_NAME];
		}
		else {
			return false;
		}
	}

	/**
	* nastaví typ pro uchovávání stavu přihlášení (povolené jsou hodnoty cookie a session pro příslušný typ)
	* defaultně nastaveno ukládání do cookies
	* @return string typ
	*/
	public function getType($type) {
		return $this->type;
	}

	private function generateHash($length=50) {
		$chars = 'abcdefghijklmnopqrstuvwxyz123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$output = '';
		$chars_length = strlen($chars);
		for ($i=0;$i<$length;$i++) {
			$output .= $chars[mt_rand(0,$chars_length - 1)];
		}
		return $output;
	}

	/**
	* funkce připojí uživatele jako administrátora
	* @param string $name jméno
	* @param string $pass heslo
	* @return true, pokud přihlášení proběhlo úspěšně, jinak false
	*/
	public function in($name,$pass,$forever = FALSE){
		global $_COOKIES;
		if ($forever) {
			$this->forever = true;
		}
		$this->name = mysql_real_escape_string($name);

		$admin = $this->get_admin_by_name($this->name);

		if ($admin) {

			if (md5($pass) == $admin[$this->COL_PASS]) {

				$this->hash = $this->generateHash();
				$this->sql_relace->update(array($this->COL_HASH=>$this->hash),"id={$admin['id']}");
				$this->update_time(time());
				if ($this->forever) {
					setcookie(SESSION_NAME,$this->hash,time()+COOKIE_EXPIRATION);
					$_COOKIES[SESSION_NAME] = $this->hash;
				}
				else {
					if ($this->type=="cookie") {
						setcookie(SESSION_NAME,$this->hash,time()+DURATION_OF_INACTIVITY);
						$_COOKIES[SESSION_NAME] = $this->hash;
					}
					else {
						$_SESSION[SESSION_NAME] = $this->hash;
					}	
				}
				
				$this->admin = $this->get_current_admin();

				return true;

			}
			else {
				return false;
			}
		}
		else {
			return false;
		}
	}

	/**
	* funkce odhlásí administrátora
	* @return bool	true, pokud odhlášení proběhlo úspěšně, jinak false
	*/
	public function out(){
		$this->sql_relace->update(array($this->COL_HASH=>""),"id={$this->admin['id']}");
		setcookie(SESSION_NAME,$this->hash,time()-DURATION_OF_INACTIVITY);
		unset($_SESSION[SESSION_NAME],$_COOKIE[SESSION_NAME],$this->admin);
	}

	/**
	* funkce zjišťuje, zda je daný uživatel připojen jako administrátor
	* @return bool	true, pokud je uživatel připojen jako administrátor, jinak false
	*/
	public function is_logged(){
		if (isset($this->admin[$this->COL_HASH],$this->hash)) {
			return $this->hash==$this->admin[$this->COL_HASH];
		}
		return false;
	}

	/**
	* funkce zkontroluje, zda byl administrátor neaktivní déle, než je povoleno
	* a pokud ano, odhlásí ho. Tato funkce funguje především z bezpečnostních důvodů
	* @return bool	false, pokud administrátor není přihlášen,
	* 		  string	"out" pokud doba nečinnosti překročila zadanou délku, jinak string "in"
	*/
	public function checkDURATION_OF_INACTIVITY(){
		if ($this->is_logged()) {
			$adminInfo = $this->admin;
			$timeOfTheLastActivity = $adminInfo[COL_TIME];
			$actualTime = time();
			if ($actualTime - $timeOfTheLastActivity > DURATION_OF_INACTIVITY) {
				$this->out();
				return "out";
			}
			else {
				$this->update_time($actualTime);
				return "in";
			}
		}
		else return false;
	}

	/**
	* funkce upraví čas v tabulce na čas předaný v parametru
	* @param int $time čas, který se nastaví v tabulce
	* @return true, pokud přenastavení času proběhne úspěšně, jinak false
	*/
	private function update_time($time){
		return $this->sql_relace->update(array("".$this->COL_TIME."" => $time),$this->COL_NAME." = '".$this->name."'");
	}

	/**
	* funkce najde a vrátí záznam uživatele z databáze
	* @param string $name jméno uživatele
	* @return array	asociační/číselné pole záznamu o aktuálním uživateli
	*/
	private function get_admin_by_name($name) {
		if ($admin = $this->sql_relace->get("LOWER(" . $this->COL_NAME . ") = '" . strtolower($name) . "'")) {
			return $admin[0];
		}
		return false;
	}

	private function get_current_admin() {
		if ($this->hash) {
			$admin = $this->sql_relace->get("{$this->COL_HASH}='$this->hash'");
			$this->admin = $admin[0];
			return $this->admin;
		}
		return false;
	}

	/**
	 * funkce vrátí záznam administrátora z databáze
	 * @return array	asociační/číselné pole záznamu o aktuálním administrátorovi
	 */
	public function get_admin() {
		if ($this->is_logged()) {
			return $this->get_current_admin();
		}
		else {
			return false;
		}
	}

	/**
	* vrátí id záznamu administrátora v tabulce
	* @return int id záznamu administrátora v tabulce
	*/
	public function get_admin_id() {
		if ($this->is_logged()) {
			return $this->admin['id'];
		}
		return false;
	}

}
?>