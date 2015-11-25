<?php
/*
error_reporting(E_ALL);
ini_set('display_errors', 1);
 */
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
require_once('inc/filemanager.inc.php');
require_once('controller/MembershipManager.php');
require_once('geoiploc.php'); // Must include this

require 'aws/aws-autoloader.php';
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

	/*
		Need to implement thes methods for Core 5 File Manager at db level
		//setFileRoot
		getinfo [done]
		getfolder [done]
		rename  [rename file down, rename folder done, but child folders/files must be renamed according to their parent folder name]
		move [it's complicated function]
		delete [done] 
		addfolder [done]
		download 
		preview 
		maxuploadfilesize	
	*/

class FSManager {
	public $mbbox;
	private $userid;

	protected $config = array();
	protected $language = array();
	protected $get = array();
	protected $post = array();
	protected $properties = array();
	protected $item = array();
	protected $languages = array();
	protected $root = '';
	protected $doc_root = '';
	protected $dynamic_fileroot = '';
	protected $logger = false;
	protected $logfile = '/tmp/filemanager.log';
	protected $cachefolder = '_thumbs/';
	protected $thumbnail_width = 64;
	protected $thumbnail_height = 64;
	protected $separator = 'userfiles'; // @todo fix keep it or not?
    private $ip = ''; 
	
	public $sessionUser = '';
	private $membershipObject;
	
	//Membership class method override here
	public function isSessionExist()
	{
		return $this->membershipObject->isSessionExist();
	}
	public function getSessionObject()
	{
		return $this->membershipObject->getSessionObject();
	}
	function __construct() {
		$this->ip = $_SERVER["REMOTE_ADDR"];

		// connects to localhost on port 27017 by default
//		$this->language = array('');  
		$mongo = new Mongo();
		//$mongo = new Mongo("mongodb://admin:RnGP-tB3qlaQ@127.5.246.130:27017/admin");
		//$mongo = new Mongo("mongodb://mbbox:mbbox@172.31.27.88:27017/mbbox");
		//$mongo = new Mongo("mongodb://mbbox:mbbox@ec2-54-200-104-186.us-west-2.compute.amazonaws.com:27017/mbbox");
		
		$this->mbbox = $mongo->mbbox;
		$content = file_get_contents("../../scripts/filemanager.config.js");
		$config = json_decode($content, true);
		$this->membershipObject = new MembershipManager();

		$obj = $this->membershipObject->getSessionObject();
		$this->userid =    "" .$obj['_id'];
			
		$this->config = $config;

		// override config options if needed
		if(!empty($extraConfig)) {
			$this->setup($extraConfig);
		}
		
		$this->properties = array(
				'Date Created'=>null,
				'Date Modified'=>null,
				'Height'=>null,
				'Width'=>null,
				'Size'=>null
		);
		
				// Log actions or not?
		if ($this->config['options']['logger'] == true ) {
			if(isset($this->config['options']['logfile'])) {
				$this->logfile = $this->config['options']['logfile'];
			}
			$this->enableLog();
		}
		$this->setParams();
		$this->availableLanguages();
		$this->loadLanguageFile();

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
	
	// $extraconfig should be formatted as json config array.
	public function setup($extraconfig) {

		$this->config = array_merge_recursive($this->config, $extraconfig);
			
	}

	private function setParams() {
		$tmp = (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/');
		$tmp = explode('?',$tmp);
		$params = array();
		if(isset($tmp[1]) && $tmp[1]!='') {
			$params_tmp = explode('&',$tmp[1]);
			if(is_array($params_tmp)) {
				foreach($params_tmp as $value) {
					$tmp = explode('=',$value);
					if(isset($tmp[0]) && $tmp[0]!='' && isset($tmp[1]) && $tmp[1]!='') {
						$params[$tmp[0]] = $tmp[1];
					}
				}
			}
		}
		$this->params = $params;
	}


	function isExist($folderPath,$filename)
	{
		//Connecting to DB and Querying
		$collection = $this->mbbox->record;
		$nested = array( "Active" => 1);
		try {
		
			$document = $collection->findOne(array("Path" => $folderPath,"Filename" => $filename,"UserId"=>$this->userid),$nested);
			if($document['Active'] == true)
			{
				return true;
			} else return false;
		}
		catch (MongoCursorException $e) {
			echo "error message: ".$e->getMessage()."\n";
			echo "error code: ".$e->getCode()."\n";
		}
		return false;
	}	
	function getinfo($path)
	{
		$filename = "";
		$parentFolderPath = $path;
		$pieces = explode('/', $parentFolderPath);
		$isFolder = false;
		if($parentFolderPath[strlen($parentFolderPath)-1] == '/')
		{ //it's folder
			$filename = $pieces[sizeof($pieces)-2];
			$parentFolderPath = substr($parentFolderPath, 0, -1*(strlen($filename)+1));
			$isFolder = true;
		} else 
		{	//its file
			$filename = $pieces[sizeof($pieces)-1];
			$parentFolderPath = substr($parentFolderPath, 0, -1*(strlen($filename)));
		}
		//Connecting to DB and Querying
		$collection = $this->mbbox->record;
		$excludeColumns = array( "UserId" => 0, "Active" => 0, "_id" => 0 );
		try {
		
			$document = $collection->findOne(array("Path" => $parentFolderPath,"Filename" => $filename,"UserId"=>$this->userid),$excludeColumns);
			$obj = $document;
			//Populate predfined object template values.
			$obj['Path'] = $path;
			if($isFolder == true)
			{
				$obj['Filename'] = "";
				$obj['File Type'] = "";
			}
			return $obj;
		}
		catch (MongoCursorException $e) {
			echo "error message: ".$e->getMessage()."\n";
			echo "error code: ".$e->getCode()."\n";
		}
	}
	function getfolder($path) {
		if($path[strlen($path)-1] != '/')
		{
			$path .= '/';
		}
		$array = array();
		$collection = $this->mbbox->record;
//		$regex = new MongoRegex("/\/");
		$nested = array( "UserId" => 0, "Active" => 0, "_id" => 0 );
		try {
			$cursor = $collection->find(array("Path" => $path,"UserId"=>$this->userid),$nested);
			foreach ($cursor as $document) {
				$obj = $document;
				if($obj['File Type'] == 'dir')
				{
					$obj['Path'] = $document['Path'].$document['Filename'].'/';
					
				}else
				{
					$obj['Path'] = $document['Path'].$document['Filename'];
				}
				$obj['Parent'] = $document['Path'];
				//Geo Location based Donwload Link
				$countryCode = getCountryFromIP($this->ip, "code");
				$current_path = $obj['Http_Singapore'];
				if($countryCode == 'PK')
				{
					 $current_path = $obj['Http_Singapore'];
				} else if ($countryCode == 'USA')
				{
					 $current_path = $obj['Http'];
				} else if ($countryCode == 'UK')
				{
					 $current_path = $obj['Http_Ireland'];
				}				
				$obj['Http'] = $current_path;

				
				array_push($array,$obj);
			}
		}
		catch (MongoCursorException $e) {
			echo "error message: ".$e->getMessage()."\n";
			echo "error code: ".$e->getCode()."\n";
		}
		return $array;
	} 

/*
{
  "UserId": "526d29410e7cd9409c00d6bf",
  "Path": "\/mbbox\/",
  "Filename": "new",
  "File Type": "dir",
  "Preview": "images\/fileicons\/_Open.png",
  "Error": "",
  "Code": 0,
  "Active": true,
  "Properties": {
    "DateCreated": "05 Jul 2013 08:25",
    "DateModified": "05 Jul 2013 08:25",
    "Filemtime": 1373027110,
    "Height": 210,
    "Width": 100,
    "Size": 12045
  }
}
{"Parent":"\/wsft\/userfiles\/mbboxFolder\/","Name":"newMuzammil","Error":"","Code":0}
*/		
	function addfolder($name,$path,$time) {
		$obj;
		$obj['Parent'] = $path;
		$obj['Name'] = $name;
		$obj['Error'] = "";
		$obj['Code'] = "0";

		if($this->isExist($path,$name) == true)
		{
			$obj['Error'] = "The directory '$name' already exists.";
			$obj['Code'] = "-1";
			return $obj;
		}
		
		//Connecting to DB and Querying
		$collection = $this->mbbox->record;
		try {
			// Return date/time info of a timestamp; then format the output
			$mydate=getdate(date("U")); //minutes  hours
			$currentDate = "$mydate[mday] $mydate[month] $mydate[year] $mydate[hours]:$mydate[minutes]";
			
			$record = array("UserId" => $this->userid, "Path" => "$path", "Filename" => "$name", "File Type" => "dir", "Preview" => "images/fileicons/_Open.png", "Error" => "", "Code" => "0", "Active" => true,
			 "Properties" => array("DateCreated" => $currentDate,"DateModified" =>  $currentDate,"Filemtime" =>  microtime(true),"Height" => null,"Width" => null,"Size" => null ));
			
			// Inserting record to db
			$collection->insert($record);
		} catch(MongoCursorException $e) {
			//echo "Can't save the same person twice!\n";
			$obj['Error'] = $e;
			$obj['Code'] = "-1";
		}
		return $obj;
	}
	function rename($new,$oldPath) {
		$filename = "";
		$parentFolderPath = $oldPath;
		$pieces = explode('/', $parentFolderPath);
		$isFolder = false;
		if($parentFolderPath[strlen($parentFolderPath)-1] == '/')
		{ //it's folder
			$filename = $pieces[sizeof($pieces)-2];
			$parentFolderPath = substr($parentFolderPath, 0, -1*(strlen($filename)+1));
			$isFolder = true;
		} else 
		{	//its file
			$filename = $pieces[sizeof($pieces)-1];
			$parentFolderPath = substr($parentFolderPath, 0, -1*(strlen($filename)));
		}
		//Populate predfined object template values.
		$obj;
		$obj['Old Path'] = $oldPath;
		$obj['Old Name'] = $filename;
		$obj['New Path'] = $parentFolderPath.''.$new;
		$obj['New Name'] = $new;
		$obj['Error'] = "";
		$obj['Code'] = "0";
		
		if($this->isExist($parentFolderPath,$new) == true)
		{
			$msg = "The file '$new' already exists.";
			if($isFolder)
			{
				$msg = "The directory '$new' already exists.";
			}
			$obj['Error'] = $msg;
			$obj['Code'] = "-1";
			return $obj;
		}
		//Connecting to DB and Querying
		$collection = $this->mbbox->record;
		try {
			$newdata = array('$set' => array("Filename" => $new));
			$collection->update(array("Path" => $parentFolderPath,"Filename" => $filename,"UserId"=>$this->userid), $newdata);	
		} catch(MongoCursorException $e) {
			$obj['Error'] = $e;
			$obj['Code'] = "-1";
		}
		return $obj;
	}	
	// $radioactive->remove(array('type' => 94), array("justOne" => true));
	function shareFile($path)
	{
		$obj;
		$obj['public-url'] = "http://ombboxm.muzammilpeer.me/download/hashedkey/ $path";
		$obj['Error'] = "";
		$obj['Code'] = "0";
		return $obj;
	}
	function deletefolder($path) {
		$filename = "";
		$parentFolderPath = $path;
		$pieces = explode('/', $parentFolderPath);
		$isFolder = false;
		if($parentFolderPath[strlen($parentFolderPath)-1] == '/')
		{ //it's folder
			$filename = $pieces[sizeof($pieces)-2];
			$parentFolderPath = substr($parentFolderPath, 0, -1*(strlen($filename)+1));
			$isFolder = true;
		} else 
		{	//its file
			$filename = $pieces[sizeof($pieces)-1];
			$parentFolderPath = substr($parentFolderPath, 0, -1*(strlen($filename)));
		}
		$obj;
		$obj['Path'] = "$path";
		$obj['Error'] = "";
		$obj['Code'] = "0";

		//Connecting to DB and Querying
		$collection = $this->mbbox->record;
		try {
			// Removing one record from db
			if($isFolder)
			{
				$regex = new MongoRegex("$path/\\");
				$collection->remove(array("Path" => $regex));
				$collection->remove(array("Path" => $parentFolderPath,"Filename" => $filename,"UserId"=>$this->userid), array("justOne" => true));
			}else
			{
				$collection->remove(array("Path" => $parentFolderPath,"Filename" => $filename,"UserId"=>$this->userid), array("justOne" => true));
			}
		} catch(MongoCursorException $e) {
			//echo "Can't save the same person twice!\n";
			$obj['Error'] = $e;
			$obj['Code'] = "-1";
		}
		return $obj;
	}
	public function error($string,$textarea=false) {
		$array = array(
				'Error'=>$string,
				'Code'=>'-1',
				'Properties'=>$this->properties
		);

		//$this->__log( __METHOD__ . ' - error message : ' . $string);

		if($textarea) {
			echo '<textarea>' . json_encode($array) . '</textarea>';
		} else {
			echo json_encode($array);
		}
		die();
	}
	function generatePreviewPath($ext)
	{
		return 'images/fileicons/'.$ext.'.png';
	}
	function addfile($name,$path,$filetype,$mimetype,$filesize,$httppath,$httppath_ireland,$httppath_singapore,$width,$height) {
		//Connecting to DB and Querying
		$collection = $this->mbbox->record;
		$imagepreview = $this->generatePreviewPath($filetype);
		/*
		//$imagepreview = "connectors/php/filemanager.php?mode=preview&thumbnail=true&path=".$path.$name;
		if($filetype != 'png' && $filetype != 'jpg' && $filetype != 'jpeg' &&$filetype != 'gif' &&$filetype != 'svg' )
		{
			$imagepreview = $this->generatePreviewPath($filetype);
		}*/
		
		try {
			// Return date/time info of a timestamp; then format the output
			$mydate=getdate(date("U")); //minutes  hours
			$unix = substr($httppath, -10);
			$currentDate = "$mydate[mday] $mydate[month] $mydate[year] $mydate[hours]:$mydate[minutes]";
			
			$record = array("UserId" => $this->userid, "Path" => "$path","Mime Type" => "$mimetype", "Filename" => "$name","Http" => "$httppath","Http_Singapore" => "$httppath_singapore","Http_Ireland" => "$httppath_ireland", "File Type" => $filetype, "Preview" => "$imagepreview", "Error" => "", "Code" => "0", "Active" => true,
			 "Properties" => array("DateCreated" => $currentDate,"DateModified" =>  $currentDate,"Filemtime" =>  $unix,"Height" => $height,"Width" => $width,"Size" => "$filesize" ));
			
			// Inserting record to db
			$collection->insert($record);
		} catch(MongoCursorException $e) {
			//echo "Can't save the same person twice!\n";
		}
	}
	function updatefile($fileName,$folderPath,$filesize,$width,$height) {
		//Connecting to DB and Querying
		$collection = $this->mbbox->record;
		try {
			$property = array(
				'Height'=>$height,
				'Width'=>$width,
				'Size'=>$filesize
			);
			
			$newdata = array('$set' => array("properties" => $property));
			$collection->update(array("Path" => $folderPath,"Filename" => $fileName,"UserId"=>$this->userid), $newdata);			
			
		} catch(MongoCursorException $e) {
			//echo "Can't save the same person twice!\n";
		}
	}
	
	public function getMaxUploadFileSize() {
			
		$max_upload = (int) ini_get('upload_max_filesize');
		$max_post = (int) ini_get('post_max_size');
		$memory_limit = (int) ini_get('memory_limit');

		$upload_mb = min($max_upload, $max_post, $memory_limit);

		//$this->__log(__METHOD__ . ' - max upload file size is '. $upload_mb. 'Mb');

		return $upload_mb;
	}
	function getFileTime($path,$filename)
	{
		//Connecting to DB and Querying
		$collection = $this->mbbox->record;
		$excludeColumns = array( "Properties" => 1);
		try {
			$document = $collection->findOne(array("Path" => $path,"Filename" => $filename,"UserId"=>$this->userid));
			return $document['Properties']['Filemtime'];
		}
		catch (MongoCursorException $e) {
			echo "error message: ".$e->getMessage()."\n";
			echo "error code: ".$e->getCode()."\n";
		}
	}
	
	function formatBytes($bytes, $precision = 2) { 
		$units = array('B', 'KB', 'MB', 'GB', 'TB'); 

		$bytes = max($bytes, 0); 
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
		$pow = min($pow, count($units) - 1); 

		// Uncomment one of the following alternatives
		$bytes /= pow(1024, $pow);
		// $bytes /= (1 << (10 * $pow)); 

		return round($bytes, $precision) . ' ' . $units[$pow]; 
	}	
	public function getTotalSize()
	{
			//Connecting to DB and Querying
		$collection = $this->mbbox->record;
		$excludeColumns = array( "Properties" => 1);
		try {
		/*
			$document = $collection->findOne(array("Path" => "/","UserId"=>$this->userid),array("$sum"=>"Properties.Size"));
			return $document['Properties']['Size'];
		*/
		$cursor = $collection->find(array("Path" => "/","UserId"=>$this->userid,"File Type"=> array('$ne'=>'dir')),$excludeColumns);
		$calSize = 0;
		foreach ($cursor as $document) {
			$calSize += $document['Properties']['Size'];
		}
		return $this->formatBytes($calSize,2);
		
/*		
		// sample event document

		$map = new MongoCode("function() { emit(this.Properties,1); }");
		$reduce = new MongoCode("function(k, vals) { ".
			"var sum = 0;".
			"for (var i in vals) {".
				"sum += vals[i][Size];". 
			"}".
			"return sum; }");

		$sales = $this->mbbox->command(array(
			"mapreduce" => "record", 
			"map" => $map,
			"reduce" => $reduce,
			"query" => array("Path" => "/","UserId"=>$this->userid),
			"out" => array("merge" => "eventCounts")));

		$users = $this->mbbox->selectCollection($sales['result'])->find();

		foreach ($users as $user) {
			echo "{$user['_id']} had {$user['value']} sale(s).\n";
		}
*/

		}
		catch (MongoCursorException $e) {
			echo "error message: ".$e->getMessage()."\n";
			echo "error code: ".$e->getCode()."\n";
		}
		
		return "1";
	}


	function uploadFile($path,$filepath) {
		
		$client = S3Client::factory(array(
			'key'    => 'AKIAJCS7SMTLCVI275MA',
			'secret' => '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'
		));
		
		$obj;
		$obj['Path'] = "$path";
		$obj['Name'] = "$filepath";
		$obj['Error'] = "";
		$obj['Code'] = "0";
		if(!isset($_FILES['newfile']) || !is_uploaded_file($_FILES['newfile']['tmp_name'])) {
			$this->error(sprintf($this->lang('INVALID_FILE_UPLOAD')),true);
		}
		
		// we determine max upload size if not set
		if($this->config['upload']['fileSizeLimit'] == 'auto') {
			$this->config['upload']['fileSizeLimit'] = $this->getMaxUploadFileSize();
		}
		if($_FILES['newfile']['size'] > ($this->config['upload']['fileSizeLimit'] * 1024 * 1024)) {
			$this->error(sprintf($this->lang('UPLOAD_FILES_SMALLER_THAN'),$this->config['upload']['size'] . 'Mb'),true);
		}
		if($this->config['upload']['imagesOnly'] || (isset($this->params['type']) && strtolower($this->params['type'])=='images')) {
			if(!($size = @getimagesize($_FILES['newfile']['tmp_name']))){
				$this->error(sprintf($this->lang('UPLOAD_IMAGES_ONLY')),true);
			}
			if(!in_array($size[2], array(1, 2, 3, 7, 8))) {
				$this->error(sprintf($this->lang('UPLOAD_IMAGES_TYPE_JPEG_GIF_PNG')),true);
			}
		}
		/*
		move_uploaded_file($_FILES['newfile']['tmp_name'], $current_path . $_FILES['newfile']['name']);	
		chmod($current_path . $_FILES['newfile']['name'], 0644);*/
		//retreive post variables
		$fileName = $_FILES['newfile']['name'];
		$fileTempName = $_FILES['newfile']['tmp_name']; 
		$s3filename = null;
		$s3filename = $this->getFileTime($_POST['currentpath'],$_POST['filepath']);
		$isExists = false;
		if($s3filename != null)
		{
			$fileName = $fileName.'.'.$s3filename;
			$isExists = true;
		}else
		{
			$fileName = $fileName.'.'.time();
			$isExists = false;
		}
		$dbFileName = $_FILES['newfile']['name'];

		$bucket_california = 'californiambbox';
		$bucket_ireland = 'irelandmbbox';
		$bucket_singapore = 'singaporembbox';
		$bucket = 'mbbox';
				try {
					//Uploading file to Singapore Bucket
					$result_singapore = $client->putObject(array(
						'Bucket'     => $bucket_singapore,
						'Key'        => $fileName,
						'ACL'    => 'public-read-write',
						'SourceFile' => $fileTempName,
						'Metadata'   => array(
							'Foo' => 'abc',
							'Baz' => '123'
						)
					));
					// We can poll the object until it is accessible
					$client->waitUntilObjectExists(array(
						'Bucket' => $bucket_singapore,
						'Key'    =>  $fileName
					));
					//Uploading file to Ireland Bucket
					$result_ireland = $client->putObject(array(
						'Bucket'     => $bucket_ireland,
						'Key'        => $fileName,
						'ACL'    => 'public-read-write',
						'SourceFile' => $fileTempName,
						'Metadata'   => array(
							'Foo' => 'abc',
							'Baz' => '123'
						)
					));
					// We can poll the object until it is accessible
					$client->waitUntilObjectExists(array(
						'Bucket' => $bucket_ireland,
						'Key'    =>  $fileName
					));
					//Uploading file to US West 2 Bucket
					$result = $client->putObject(array(
						'Bucket'     => $bucket,
						'Key'        => $fileName,
						'ACL'    => 'public-read-write',
						'SourceFile' => $fileTempName,
						'Metadata'   => array(
							'Foo' => 'abc',
							'Baz' => '123'
						)
					));
					// We can poll the object until it is accessible
					$client->waitUntilObjectExists(array(
						'Bucket' => $bucket,
						'Key'    =>  $fileName
					));

					
					//echo $result->get("ObjectURL");
					$extension = $dbFileName[strlen($dbFileName)-3].$dbFileName[strlen($dbFileName)-2].$dbFileName[strlen($dbFileName)-1];
					$fileObj = $_FILES['newfile']['name'];
    				list($width, $height) = getimagesize($fileObj);
					if($isExists == false)
					{
						$this->addfile($dbFileName,$_POST['currentpath'],$extension,$_FILES['Content-Type'],$_FILES['newfile']['size'],$result->get("ObjectURL"),$result_ireland->get("ObjectURL"),$result_singapore->get("ObjectURL"),$width,$height);
					} else
					{
						//update timestamps, filesize by latest size of file.
						$this->updatefile($dbFileName,$_POST['currentpath'],$_FILES['newfile']['size'],$width,$height);
					}
				} catch (S3Exception $e) {
					$this->error(sprintf("There was an error uploading the file."),true);
				}

		$response = array(
				'Path'=>$_POST['currentpath'],
				'Name'=>$_FILES['newfile']['name'],
				'Error'=>"",
				'Code'=>0
		);
		//$this->__log(__METHOD__ . ' - adding file '. $_FILES['newfile']['name']. ' into '. $current_path);
		echo '<textarea>' . json_encode($response) . '</textarea>';
		die();
	}
	
	private function get_thumbnail($path) {
		
		require_once('./inc/vendor/wideimage/lib/WideImage.php');
		
	
		// echo $path.'<br>';
		$a = explode($this->separator, $path);
		
		$path_parts = pathinfo($path);
		
		// $thumbnail_path = $path_parts['dirname'].'/'.$this->cachefolder;
		$thumbnail_path = $a[0].$this->separator.'/'.$this->cachefolder.dirname(end($a)).'/';
		$thumbnail_name = $path_parts['filename'] . '_' . $this->thumbnail_width . 'x' . $this->thumbnail_height . 'px.' . $path_parts['extension'];
		$thumbnail_fullpath = $thumbnail_path.$thumbnail_name;
		
		// echo $thumbnail_fullpath.'<br>';
		
		// if thumbnail does not exist we generate it
		if(!file_exists($thumbnail_fullpath)) {
			
			// create folder if it does not exist
			if(!file_exists($thumbnail_path)) {
				mkdir($thumbnail_path, 0755, true);
			}
			$image = WideImage::load($path);
			$resized = $image->resize($this->thumbnail_width, $this->thumbnail_height, 'outside')->crop('center', 'center', $this->thumbnail_width, $this->thumbnail_height);
			$resized->saveToFile($thumbnail_fullpath);
	
			$this->__log(__METHOD__ . ' - generating thumbnail :  '. $thumbnail_fullpath);
			
		}
		
		return $thumbnail_fullpath;
	}
	function getFile($cpath) {
		$array = array();
		$str = $cpath; 
		//parse_str($str, $output);
		$filename = "";
		$parentFolderPath = $cpath;
		$pieces = explode('/', $parentFolderPath);
		$isFolder = false;
		if($parentFolderPath[strlen($parentFolderPath)-1] == '/')
		{ //it's folder
			$filename = $pieces[sizeof($pieces)-2];
			$parentFolderPath = substr($parentFolderPath, 0, -1*(strlen($filename)+1));
			$isFolder = true;
		} else 
		{	//its file
			$filename = $pieces[sizeof($pieces)-1];
			$parentFolderPath = substr($parentFolderPath, 0, -1*(strlen($filename)));
		}

		$collection = $this->mbbox->record;
//		$regex = new MongoRegex("/\/");
		$nested = array( "Http" => 1);
		try {
			//$cursor = $collection->find(array("Path" => $path),$nested);
			$document = $collection->findOne(array("Path" => $parentFolderPath,"Filename" => $filename,"UserId"=> $this->userid));
			return $document;
		}
		catch (MongoCursorException $e) {
			echo "error message: ".$e->getMessage()."\n";
			echo "error code: ".$e->getCode()."\n";
		}
		return $array;
	} 
 
	public function download($path) {
		$gPath = 	stripslashes($_GET['path']);
		$obj = $this->getFile($gPath);
		$current_path = $obj['Http_Singapore'];
		$countryCode = getCountryFromIP($this->ip, "code");
		if($countryCode == 'PK')
		{
			 $current_path = $obj['Http_Singapore'];
		} else if ($countryCode == 'USA')
		{
			 $current_path = $obj['Http'];
		} else if ($countryCode == 'UK')
		{
			 $current_path = $obj['Http_Ireland'];
		}
		$renamed = substr($current_path, 0, -10);
		header("Content-type: application/force-download");
		header('Content-Disposition: inline; filename="' .basename($current_path) . '"'); // basename($current_path)
		header("Content-Transfer-Encoding: Binary");
		header("Content-length: ".$this->get_size($current_path));
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="' .$obj['Filename'] . '"');
		$current_path = fopen($current_path,"r"); // open the zip file
		echo fpassthru($current_path); // deliver the zip file
		exit(); //non-essential

//			readfile($current_path);
	}

	public function preview($thumbnail,$current_path) {
		$returned_path = '';
		$current_path = $this->getFile($_GET['path']);
		if(isset($_GET['path'])/* && file_exists($current_path)*/) {
			// if $thumbnail is set to true we return the thumbnail
			/*
			if($this->config['options']['generateThumbnails'] == true && $thumbnail == true) {
				// get thumbnail (and create it if needed)
				//$returned_path = $this->get_thumbnail($current_path);
			} else {
				//$returned_path = $current_path;
			}*/
			$returned_path = $current_path;
			header("Content-type: image/" .$ext = pathinfo($current_path, PATHINFO_EXTENSION));
			header("Content-Transfer-Encoding: Binary");
			header("Content-length: ".filesize($current_path));
			header('Content-Disposition: inline; filename="' . basename($current_path) . '"');
//			readfile($current_path);
//			$this->downloads($current_path,2000);
			$current_path = fopen($current_path,"r"); // open the zip file
			echo fpassthru($current_path); // deliver the zip file
			exit(); //non-essential

		
		} else {
			$this->error(sprintf($this->lang('FILE_DOES_NOT_EXIST'),$current_path));
		}
	}
private function loadLanguageFile() {

	// we load langCode var passed into URL if present and if exists
	// else, we use default configuration var
	$lang = $this->config['options']['culture'];
	if(isset($this->params['langCode']) && in_array($this->params['langCode'], $this->languages)) $lang = $this->params['langCode'];

	if(file_exists($this->root. 'scripts/languages/'.$lang.'.js')) {
		$stream =file_get_contents($this->root. 'scripts/languages/'.$lang.'.js');
		$this->language = json_decode($stream, true);
	} else {
		$stream =file_get_contents($this->root. 'scripts/languages/'.$lang.'.js');
		$this->language = json_decode($stream, true);
	}
}

private function availableLanguages() {

	if ($handle = opendir($this->root.'/scripts/languages/')) {
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				array_push($this->languages, pathinfo($file, PATHINFO_FILENAME));
			}
		}
		closedir($handle);
	}
}

private function __log($msg) {
		
	if($this->logger == true) {

		$fp = fopen($this->logfile, "a");
		$str = "[" . date("d/m/Y h:i:s", mktime()) . "] " . $msg;
		fwrite($fp, $str . PHP_EOL);
		fclose($fp);
	}
		
}

public function enableLog($logfile = '') {
		
	$this->logger = true;
		
	if($logfile != '') {
		$this->logfile = $logfile;
	}
		
	$this->__log(__METHOD__ . ' - Log enabled (in '. $this->logfile. ' file)');
		
}

public function disableLog() {

	$this->logger = false;

	$this->__log(__METHOD__ . ' - Log disabled');
}
	
	public function lang($string) {
		if(isset($this->language[$string]) && $this->language[$string]!='') {
			return $this->language[$string];
		} else {
			return 'Language string error on ' . $string;
		}
	}
	
//Curl Function for File Download
/*
Set Headers
Get total size of file
Then loop through the total size incrementing a chunck size
*/
/*
function curl_download($file,$chunks){
    set_time_limit(0);
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-disposition: attachment; filename='.basename($file));
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Expires: 0');
    header('Pragma: public');
    $size = $this->get_size($file);
    header('Content-Length: '.$size);

    $i = 0;
    while($i<=$size){
        //Output the chunk
        $this->get_chunk($file,(($i==0)?$i:$i+1),((($i+$chunks)>$size)?$size:$i+$chunks));
        $i = ($i+$chunks);
    }

}
*/
//Callback function for CURLOPT_WRITEFUNCTION, This is what prints the chunk
function chunk($ch, $str) {
    print($str);
    return strlen($str);
}

//Function to get a range of bytes from the remote file
function get_chunk($file,$start,$end){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $file);
    curl_setopt($ch, CURLOPT_RANGE, $start.'-'.$end);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, 'chunk');
    $result = curl_exec($ch);
    curl_close($ch);
}

//Get total size of file
function get_size($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_exec($ch);
    $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
    return intval($size);
}

}
?>
