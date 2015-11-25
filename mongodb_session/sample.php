<?php
// connects to localhost on port 27017 by default
//$mongo = new Mongo();
 
// connects to 192.168.25.190 on port 50100
$mongo = new Mongo("mongodb://192.168.0.110:27017");

$db = $mongo->blog;

//$collection = $db->createCollection("posts");

$collection = $db->posts;

$document = array(
    "title" => "cat with a hat",
    "content" => "once upon a time a cat with a hat ...");
$collection->insert($document);
$collection->insert($document);



$insertOpts = array("safe" => true);
$collection->insert($document, $insertOpts);
$collection->insert($document, $insertOpts);


$document["author"] = "Shreef";
$collection->save($document);


$collection->update(
    array("author" => "Shreef"),
    array("author" => "Timothy"));
	
	
$collection->update(
    array("author" => "Shreef"),
    array('$set' => array("author" => "Timothy")));
	
	
$collection->update(
    array("author" => "Shreef"),
    array('$set' => array("author" => "Timothy")),
    array("multiple" => true));
	
$cursor = $collection->find(array("author" => "shreef"));
foreach ($cursor as $document) {
    print_r($document);
}

$document = $collection->findOne(array("author" => "shreef"));
print_r($document);


?>