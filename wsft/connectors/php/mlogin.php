<?php

require_once('./inc/filemanager.inc.php');
require_once 'controller/MembershipManager.php';
$response = '';
	$member = new MembershipManager();
	if($member->isSessionExist())
	{
		$response = '1';
	}else
	if(isset($_POST['email']) && isset($_POST['password']))
	{
		$email = $member->MongoDBSanatize($_POST['email']);
		$password = $member->MongoDBSanatize($_POST['password']);

		if($member->isLogin((string)$email,(string)$password))
		{
			$response = '1';
		} else { 
			$response = '-1';
		}
	} else {
		$response = '-2';
	}
$User = array('response'=>$response);


echo json_encode($User);
die();
?>