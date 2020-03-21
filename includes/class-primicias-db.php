<?php 

namespace Primicias\Migration;

class PrimiciasDb {

	private static $instance;
	private $db_name;
	private $host;
	private $user;
	private $password;
	private $conn;
	private $is_connected = false;

	function __construct($db, $host, $user, $password) {
		$this->db_name = $db;
		$this->host = $host;
		$this->user = $user;
		$this->password = $password;
	}

	public static function getInstance($db, $host, $user, $password) {

        if (!self::$instance instanceof self) {
            self::$instance = new self($db, $host, $user, $password);
        }

        return self::$instance;
    }

    public function getDatabaseName() {
    	return $this->db_name;
    }

    public function getConnection() {
    	return $this->conn;
    }

	public function connect() {

		//global $formerdb;

		try {

			$this->conn =  new \wpdb($this->user, $this->password, $this->db_name, $this->host);
			$this->is_connected = true;

		} catch(Exception $e) {
			return $e->getMessage();
		}
		

		return $this->is_connected;
	}

	public function disconnect() {
		$this->conn->close();
	}
}



?> 

