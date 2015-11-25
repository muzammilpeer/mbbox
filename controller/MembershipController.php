<?php
require_once '../SessionHandler/initSession.php';

class MembershipController {
//	private $mysql;
	// connects to localhost on port 27017 by default
	private $mongo;
	//echo 'Query = ';
	//var_dump($query);
	$cursor = $collection->find($query);
	foreach ($cursor as $document) {
		if($document['Password'] == $password)
		var_dump($document);
		echo "looping";
	}



	function __construct() {
		$this->mongo = new Mongo();
	// $this->mysql = new Mysql();
	// connects to 192.168.25.190 on port 50100
	//$mongo = new Mongo("mongodb://192.168.0.102:27017");
	$db = $mongo->mbbox;
	$collection = $db->user;
	$query = array('Email' => $email);
	}

	function validate_user($un, $pwd) {
		$result = $this->mysql->login_Username_and_Pass($un,$pwd);
		return $result;
	} 
}
?>
