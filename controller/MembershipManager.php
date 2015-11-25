<?php
require_once 'initSession.php';
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

class MembershipManager {
	public $mbbox;
	
	function __construct() {
		//session_start();
		$session = new MongoSession($config);
		// connects to localhost on port 27017 by default
		$mongo = new Mongo();
		//$mongo = new Mongo("mongodb://192.168.0.102:27017");
		$this->mbbox = $mongo->mbbox;
	}
	function isSessionExist()
	{
		echo "isSessionAlive() <br/>";
//			echo "userid = ".$_SESSION['userObject']."<br/>";
			var_dump($_SESSION['userObject._id']);
		/*
		if(isset($_SESSION['username']) && isset($_SESSION['password']))	{	return true;	}
		else {	return false;	}
		*/
		if(isset($_SESSION['userObject']))	{echo "session found";	return true;	}
		else {	return false;	}
	}
	function setSession($document)
	{
		echo "setSession() <br/>";
		if(!$this->isSessionExist())
		{
	//		var_dump($document);
			$_SESSION['userObject'] = $document;
//			var_dump($_SESSION['userObject']);

			//$_SESSION['username'] = $un;
			//$_SESSION['password'] = $pwd;
		}
	}
	function unSetSession()
	{
		echo "unSetSession() <br/>";
		if($this->isSessionExist())
		{
			unset($_SESSION['userObject']);
			//unset($_SESSION['username']);
			//unset($_SESSION['password']);
			session_destroy();
		}
	}
	function isLogin($un, $pwd) {
		echo "isLogin() <br/>";
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
		echo "Login() <br/>";
		$collection = $this->mbbox->user;
		$query = array('Email' => $un,'Password' => $pwd,'Active' => true);
		//$query = array('Password' => 0);
		try {
			$document = $collection->findOne($query);
			if($document['Password'] == $pwd)
			{
				//var_dump($document);
				return $document;
			}
		}
		catch (MongoCursorException $e) {
			echo "error message: ".$e->getMessage()."\n";
			echo "error code: ".$e->getCode()."\n";
		}
		return false;
	} 
	function AddUser($document) {
		$collection = $this->mbbox->user;
		try {
			$collection->insert($document);
		}
		catch (MongoCursorException $e) {
			echo "error message: ".$e->getMessage()."\n";
			echo "error code: ".$e->getCode()."\n";
		}
	} 
	function RemoveUser($email) {
		$collection = $this->mbbox->user;
		$query = array('Email' => $email);
		try {
			$collection->remove($query, true);
		}
		catch (MongoCursorException $e) {
			echo "error message: ".$e->getMessage()."\n";
			echo "error code: ".$e->getCode()."\n";
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
			echo "error message: ".$e->getMessage()."\n";
			echo "error code: ".$e->getCode()."\n";
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
			echo "error message: ".$e->getMessage()."\n";
			echo "error code: ".$e->getCode()."\n";
		}
	} 
	function ExpiryUsers($email) {
	} 
}
?>
