<?php
require_once 'controller/MembershipManager.php';
//require_once 'sessionHandler/initSession.php';


header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

$email = $_POST['username'];
$password = $_POST['password'];
//echo $_POST['username'] . '<br/>';
//echo $_POST['password']. '<br/>';

$member = new MembershipManager();
if($member->isLogin($email,$password))
{
	echo 'Login Successfully !<br/>';
}else
{
	echo 'Username or Password Wrong! <br/>';
}


?>