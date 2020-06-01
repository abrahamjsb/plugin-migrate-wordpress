<?php 

namespace Primicias\Repository;

require "class-primicias-db.php";

use Primicias\Migration\PrimiciasDb as PrimiciasDb;

class Repository {

	protected $db;
	protected $connected = false; 

	public function setDatabase() {

		$hostname = "127.0.0.1";
		$db = "primicias";
		$user = "root";
		$password = "";

		try {

			$this->db = PrimiciasDb::getInstance($db, $hostname, $user, $password);

		} catch(Exception $e) {
			echo $e->getMessage();
		}	
	}

	protected function makeConnection() { 

		if(!$this->connected) { 
			try {

				$this->connected = $this->db->connect();
				if($this->connected){
					$conn = $this->db->getConnection();
					return $conn;
				} else {
					throw new Exception("An Error ocurred trying to connect to the database. (method: importPosts)", 500);
					
				}	

			} catch(Exception $e) {
				return $e->getMessage();
			}
		} else {
			$conn = $this->db->getConnection();
			return $conn;
		}
	}

	protected function getDatabase() {
		return $this->db;
	}

}


 ?>