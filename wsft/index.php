<?php
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
//require_once('./connectors/php/controller/MembershipManager.php' );
require_once('./connectors/php/FSManager.php');
	$member = new FSManager();
	$userObj;
	if(!$member->isSessionExist())
	{
		header('Location: login.html');
		exit;
	}
	else 
	{
		$userObj = $member->getSessionObject();
	}
	
	$totalSpace = 0;
	$totalSpace = $member->getTotalSize();
	
	//echo $totalSpace = $fs->getTotalSize();
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>mbBox Cloud Storage</title>
<meta name="description" content="mbBox Cloud Storage">
<meta name="author" content="muzammil peer,bilal">

<link rel="shortcut icon" href="favicon.ico">
<link rel="stylesheet" type="text/css" href="styles/reset.css" />
<link rel="stylesheet" type="text/css" href="scripts/jquery.filetree/jqueryFileTree.css" />
<link rel="stylesheet" type="text/css" href="scripts/jquery.contextmenu/jquery.contextMenu-1.01.css" />
<link rel="stylesheet" type="text/css" href="styles/filemanager.css" />
<!--[if IE 9]>
<link rel="stylesheet" type="text/css" href="styles/ie9.css" />
<![endif]-->
<!--[if lte IE 8]>
<link rel="stylesheet" type="text/css" href="styles/ie8.css" />
<![endif]-->
	<style>
	.floatleft {float: left !important;}
	.floatright {float: right !important;}
	.clear {clear: both;}
	.colFull {width: 99.8%; margin-bottom: 10px !important; clear: both;}
	.colFull .elementFull {width: 100%; float: left; font-weight: normal !important;}
	.colFull .element67 {width: 67%; float: left; font-weight: normal !important;}
	.raw {clear: both; height: 25px; padding-bottom: 6px;}
	.raw1 {clear: both; height: 20px; padding-bottom: 2px;}
	.raw-centre{ display: table; height: 25px;  margin: 0 auto; padding-bottom: 6px; }

	.colWrap {margin: 0 29px; margin-bottom: 10px !important;}
	.colWrap1 {margin: 0 15px; margin-bottom: 10px !important;}
	.colWrap2 {margin: 0 45px; margin-bottom: 10px !important;}

	.hline {border-bottom: 1px solid #ebeaea; width: 98%; margin: auto;}
	.hline1 {border-bottom: 1px solid #ebeaea; width: 100%; margin: auto;}
	.subHeading {color: #235688; font-weight: bold; font-size: 14px; margin-bottom: 6px !important; padding: 0 12px !important;}
	.subHeading1 {color: #235688; font-weight: bold; font-size: 14px; margin-bottom: 6px !important;}
	.subSection {margin-bottom: 6px !important; padding: 0 12px !important;}
	.subSection .filterDown {margin-top: -22px;}
	.subSection .label {width: 20%; float: left; margin-right: 1%; font-weight: bold !important ;}
	.subSection .element {width: 75%; float: left; font-weight: normal !important;}
	.subSection .label1 {width: 30%; float: left; margin-right: 1%; font-weight: bold !important ;}
	.subSection .element1 {width: 60%; float: left; font-weight: normal !important;}


	.mr-25 {margin-right: -25px !important;}
	.ml25 {margin-left: 25px !important;}
	.ml-11 {margin-left: -11px !important;}
	.mB-20 {margin-bottom:-20px !important;}
	
	.mT-10 {margin-top: -10px !important;}
	.mT-30 {margin-top: -30px !important;}
	.mT-60 {margin-top: -60px !important;}
	.mT-90 {margin-top: -90px !important;}
	.mT-100 {margin-top: -100px !important;}
	.mT-120 {margin-top: -120px !important;}
	
	.col10 {width: 10%; float: left;}
	.col20 {width: 20%; float: left;}
	.col30 {width: 30%; float: left;}
	.col40 {width: 40%; float: left;}
	.col50 {width: 50%; float: left;}
	
	.col60 {width: 40%; float: right;}
	.col70 {width: 30%; float: right;}
	.col80 {width: 20%; float: right;}
	.col90 {width: 10%; float: right;}
	
	.mbboxLogo {
		//background-color: #235688;
		background-image: url("images/logo.png");
		background-position: 0 center;
		background-repeat: no-repeat;
		float: left;
		height: 50px;
		width: 400px;
	}	
	
	</style>
   <script type="text/javascript">
    // File Picker modification for FCK Editor v2.0 - www.fckeditor.net
   // by: Pete Forde <pete@unspace.ca> @ Unspace Interactive
   var urlobj;

   function BrowseServer(obj)
   {
        urlobj = obj;
        OpenServerBrowser(
        'index.html',
        screen.width * 0.7,
        screen.height * 0.7 ) ;
   }

   function OpenServerBrowser( url, width, height )
   {
        var iLeft = (screen.width - width) / 2 ;
        var iTop = (screen.height - height) / 2 ;
        var sOptions = "toolbar=no,status=no,resizable=yes,dependent=yes" ;
        sOptions += ",width=" + width ;
        sOptions += ",height=" + height ;
        sOptions += ",left=" + iLeft ;
        sOptions += ",top=" + iTop ;
        var oWindow = window.open( url, "BrowseWindow", sOptions ) ;
   }

   function SetUrl( url, width, height, alt )
   {
        document.getElementById(urlobj).value = url ;
        oWindow = null;
   }
   </script>
</head>
<body>
<div class="colFull raw">
	<div class="mbboxLogo"> </div>
	<div class="col50 subHeading">Upload your files </div>
		<div class="col80 mT120">
			<div class="floatright"> Welcome to <?php echo $userObj['NickName']; ?> </div>
			<div class="clear"></div>
			<div class="floatright"> Total Space Used  <?php echo $totalSpace; ?> </div>
			<div class="clear"></div>
			</br>
			<form id="formlogout" method="post" action="connectors/php/logout.php"  class="floatright">
			<button type="submit" >Logout</button>
			</form>
		</div>
	<div class="clear"></div>
</div>
<div class="colFull raw-centre">
<form id="uploader" method="post">
<button id="home" name="home" type="button" value="Home">&nbsp;</button>
<h1></h1>
<div id="uploadresponse"></div>
<input id="mode" name="mode" type="hidden" value="add" /> 
<input id="currentpath" name="currentpath" type="hidden" />
<div id="file-input-container">
	<div id="alt-fileinput">
		<input	id="filepath" name="filepath" type="text" /><button id="browse" name="browse" type="button" value="Browse"></button>
	</div>
	<input	id="newfile" name="newfile" type="file" />
</div>
<button id="upload" name="upload" type="submit" value="Upload"></button>
<button id="newfolder" name="newfolder" type="button" value="New Folder"></button>
<button id="grid" class="ON" type="button">&nbsp;</button>
<button id="list" type="button">&nbsp;</button>
</form>
<div id="splitter">
<div id="filetree"></div>
<div id="fileinfo">
<h1></h1>
</div>
</div>
<form name="search" id="search" method="get">
		<div>
			<input type="text" value="" name="q" id="q" />
			<a id="reset" href="#" class="q-reset"></a>
			<span class="q-inactive"></span>
		</div> 
</form>

<ul id="itemOptions" class="contextMenu">
	<li class="select"><a href="#select"></a></li>
	<li class="download"><a href="#download"></a></li>
	<li class="rename"><a href="#rename"></a></li>
	<li class="move"><a href="#move"></a></li>
	<li class="share"><a href="#share"></a></li>
	<li class="delete separator"><a href="#delete"></a></li>
</ul>

<script type="text/javascript" src="scripts/jquery-1.8.3.min.js"></script>
<script type="text/javascript" src="scripts/jquery.form-3.24.js"></script>
<script type="text/javascript" src="scripts/jquery.splitter/jquery.splitter-1.5.1.js"></script>
<script type="text/javascript" src="scripts/jquery.filetree/jqueryFileTree.js"></script>
<script type="text/javascript" src="scripts/jquery.contextmenu/jquery.contextMenu-1.01.js"></script>
<script type="text/javascript" src="scripts/jquery.impromptu-3.2.min.js"></script>
<script type="text/javascript" src="scripts/jquery.tablesorter-2.7.2.min.js"></script>
<script type="text/javascript" src="scripts/filemanager.js"></script>


      <!-- FOOTER -->
<div class="clear"></div>
<footer class="colFull">
<p class="raw-centre">&copy; 2013-2014 mbBox Cloud Storage, Inc. &middot; <a href="#">Privacy</a> &middot; <a href="#">Terms</a></p>
</footer>

</div>
</body>
</html>
