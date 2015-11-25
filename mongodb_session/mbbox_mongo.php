<?php
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

// connects to localhost on port 27017 by default
$mongo = new Mongo();
 
// connects to 192.168.25.190 on port 50100
//$mongo = new Mongo("mongodb://192.168.0.102:27017");
//selectDB
$db = $mongo->mbbox;

//$collection = $db->createCollection("posts");

$collection = $db->user;

$query = array('Email' => 'muzammilpeer987@gmail.com');
echo 'Query = ';
var_dump($query);

$cursor = $collection->find($query);
var_dump($cursor);

foreach ($cursor as $document) {
    var_dump($document);
    //print_r($document);
}

//$document = $collection->findOne(array("author" => "shreef"));
//print_r($document);


?>