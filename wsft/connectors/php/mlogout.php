<?php

require_once('./inc/filemanager.inc.php');
require_once 'controller/MembershipManager.php';
$response = '';
$member = new MembershipManager();
$member->unSetSession();
$User = array('response'=>'1');


echo json_encode($User);
die();
?>