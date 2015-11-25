<?php
require_once('FileManager.php');


$fs = new FSManager();
echo json_encode($fs->getfolder('/'));
?>