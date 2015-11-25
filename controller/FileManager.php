<?php
//require_once 'MembershipManager.php';

header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

class FSManager {
//	$membershipManager;
	public $mbbox;

	function __construct() {
		//$membershipManager = new MembershipManager();
//		$membershipManager->mbbox = $mongo->mbbox;
		// connects to localhost on port 27017 by default
		$mongo = new Mongo();
		//$mongo = new Mongo("mongodb://192.168.0.102:27017");
		$this->mbbox = $mongo->mbbox;

	}
	/*
		Methods to implement for Core 5 File Manager at db level
		setFileRoot
		getinfo
		getfolder
		rename
		move
		delete
		addfolder
		download
		preview
		maxuploadfilesize	
	*/

	function getfolder($path) {
		$array = array();
		$collection = $this->mbbox->record;
//		db.record.find({Path:/\/mbbox\//});
		$regex = new MongoRegex("/mbbox/");
		$query = array('Path' => $regex);
		/*
			"UserId" : "526d29410e7cd9409c00d6bf",
    "Path": "/mbbox/",
    "FileName": "mbbox",
    "FileType": "dir",
    "Preview": "images/fileicons/_Open.png",
    "Error": "",
    "Code": 0,
    "Active": true,
    "Properties": 
	 {
		"DateCreated":"05 Jul 2013 08:25",
		"DateModified":"05 Jul 2013 08:25",
		"Filemtime":1373027110,
		"Height":210,
		"Width":100,
		"Size":12045
	}
*/
		
		$nested = array( "UserId" => 0, "Active" => 0, "_id" => 0 );
		try {
			$cursor = $collection->find($query);
			foreach ($cursor as $document) {
				//print_r($document);
				array_push($array,$document);
			}
		}
		catch (MongoCursorException $e) {
			echo "error message: ".$e->getMessage()."\n";
			echo "error code: ".$e->getCode()."\n";
		}
		return $array;
	} 
}
?>
