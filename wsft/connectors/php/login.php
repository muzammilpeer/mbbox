<?php
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
require_once('./controller/MembershipManager.php' );
	$member = new MembershipManager();
	if($member->isSessionExist())
	{
		header('Location: ../../index.php');
		exit;
	}else
	if(isset($_POST['email']) && isset($_POST['password']))
	{
		$email = $member->MongoDBSanatize($_POST['email']);
		$password = $member->MongoDBSanatize($_POST['password']);

		if($member->isLogin((string)$email,(string)$password))
		{
		header('Location: ../../index.php');
		exit;
		} else { 
		header('Location: ../../login.html');
		exit;
		}
	} else {
		header('Location: ../../login.html');
		exit;
	}
?>