<?php
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
require_once('initSession.php');

class MembershipManager {
	public $mbbox;
	
	function __construct() {
		//session_start();
		$session = new MongoSession($config);
		// connects to localhost on port 27017 by default
		$mongo = new Mongo();
		//$mongo = new Mongo("mongodb://admin:RnGP-tB3qlaQ@127.5.246.130:27017/admin");
		//$mongo = new Mongo("mongodb://mbbox:mbbox@172.31.27.88:27017/mbbox");
		//$mongo = new Mongo("mongodb://mbbox:mbbox@ec2-54-200-104-186.us-west-2.compute.amazonaws.com:27017/mbbox");

		$this->mbbox = $mongo->mbbox;
	}
	
	//Mongo DB Injection Protection
	function MongoDBSanatize($val) {
		if (!is_array($val))
			return $val;

		$indexes = array();

		foreach($val as $key => $value) {
			if (is_string($key)){
				//$key = str_replace(array('$', chr(0)), '', $key);
				$key = preg_replace('[^a-z0-9]', '', $key);
			}
			$indexes[$key] = $value;
		}

		foreach($indexes as $key => $value)
		if (is_array($value))
			$indexes[$key] = MongoDBSanatize($value);

		return $indexes;
	}
	
	public function getSessionObject()
	{
		if(isset($_SESSION['userObject']))	
		return $_SESSION['userObject'];
		else null;
	}

	
	public function isSessionExist()
	{
		if(isset($_SESSION['userObject']))	
		{ return true;}
		else {	return false;	}
	}
	function setSession($document)
	{
		if(!$this->isSessionExist())
		{
			$_SESSION['userObject'] = $document;
		}
	}
	public function unSetSession()
	{
		if($this->isSessionExist())
		{
			unset($_SESSION['userObject']);
			session_destroy();
		}
	}
	function isLogin($un, $pwd) {
		$isLoggedIn = false;
		if(!$this->isSessionExist())
		{
			$userObject = $this->Login($un,$pwd);
			if($userObject)
			{
				$this->setSession($userObject);
				$isLoggedIn = true;
			}else
			{
				$this->unSetSession();
			}
		} else 
		{
			$isLoggedIn = true;
		}
		return $isLoggedIn;
	} 

	function Login($un, $pwd) {
		$collection = $this->mbbox->user;
		$query = array('Email' => $un,'Password' => $pwd,'Active' => true);
		try {
			$document = $collection->findOne($query);
			if($document['Password'] == $pwd)
			{
				return $document;
			}
		}
		catch (MongoCursorException $e) {
			//echo "error message: ".$e->getMessage()."\n";
			//echo "error code: ".$e->getCode()."\n";
		}
		return false;
	} 
	function isUserExist($email) {
		$collection = $this->mbbox->user;
		$query = array('Email' => $email,'Active' => true);
		try {
			$document = $collection->findOne($query);
			if($document['Email'] == $email)
			{
				return true;
			}else {
				return false;
			}
		}
		catch (MongoCursorException $e) {
			//echo "error message: ".$e->getMessage()."\n";
			//echo "error code: ".$e->getCode()."\n";
		}
		return false;
	} 

	function AddUser($document) {
		$collection = $this->mbbox->user;
		try {
			if(!$this->isUserExist($document['Email']))
			{
				$collection->insert($document);
				return true;
			}
			return false;
		}
		catch (MongoCursorException $e) {
			//echo "error message: ".$e->getMessage()."\n";
			//echo "error code: ".$e->getCode()."\n";
		}
		return false;
	} 
	function RemoveUser($email) {
		$collection = $this->mbbox->user;
		$query = array('Email' => $email);
		try {
			$collection->remove($query, true);
		}
		catch (MongoCursorException $e) {
			//echo "error message: ".$e->getMessage()."\n";
			//echo "error code: ".$e->getCode()."\n";
		}
	} 
	function DeactivateUser($email) {
		$collection = $this->mbbox->user;
		$newdata = array('$set' => array("Active" => false));
		$query = array('Email' => $email);
		try {
			$collection->update($query, $newdata);		
		}
		catch (MongoCursorException $e) {
			//echo "error message: ".$e->getMessage()."\n";
			//echo "error code: ".$e->getCode()."\n";
		}
	} 
	function ReactivateUser($email) {
		$collection = $this->mbbox->user;
		$newdata = array('$set' => array("Active" => true));
		$query = array('Email' => $email);
		try {
			$collection->update($query, $newdata);		
		}
		catch (MongoCursorException $e) {
			//echo "error message: ".$e->getMessage()."\n";
			//echo "error code: ".$e->getCode()."\n";
		}
	} 
	function ExpiryUsers($email) {
	} 
}
?>
