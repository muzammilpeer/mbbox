<?php
// only for debug
// error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
// ini_set('display_errors', '1');
/**
 *	Filemanager PHP connector
 *
 *	filemanager.php
 *	use for ckeditor filemanager plug-in by Core Five - http://labs.corefive.com/Projects/FileManager/
 *
 *	@license	MIT License
 *	@author		Riaan Los <mail (at) riaanlos (dot) nl>
 *  @author		Simon Georget <simon (at) linea21 (dot) com>
 *	@copyright	Authors
 */
require_once 'controller/MembershipManager.php';
require_once('./inc/filemanager.inc.php');
require_once('FSManager.php');

/**
 *	Check if user is authorized
 *
 *	@return boolean true is access granted, false if no access
 */
function auth() {
  // You can insert your own code over here to check if the user is authorized.
  // If you use a session variable, you've got to start the session first (session_start())
  
	$member = new MembershipManager();

	if($member->isSessionExist())
	{
		return true;
	}
	return false;
}


// @todo Work on plugins registration
// if (isset($config['plugin']) && !empty($config['plugin'])) {
// 	$pluginPath = 'plugins' . DIRECTORY_SEPARATOR . $config['plugin'] . DIRECTORY_SEPARATOR;
// 	require_once($pluginPath . 'filemanager.' . $config['plugin'] . '.config.php');
// 	require_once($pluginPath . 'filemanager.' . $config['plugin'] . '.class.php');
// 	$className = 'Filemanager'.strtoupper($config['plugin']);
// 	$fm = new $className($config);
// } else {
// 	$fm = new Filemanager($config);
// }

//$fm = new Filemanager();
$fm = new FSManager();
/**
 *	Check if user is authorized
 *
 *	@return boolean true is access granted, false if no access
 */
/*New IMPLT */

$response = '';

if(!auth()) {
  $fm->error($fm->lang('AUTHORIZATION_REQUIRED'));
}

if(!isset($_GET)) {
  $fm->error($fm->lang('INVALID_ACTION'));
} else {

  if(isset($_GET['mode']) && $_GET['mode']!='') {

    switch($_GET['mode']) {
      	
      default:

        $fm->error($fm->lang('MODE_ERROR'));
        break;

      case 'getinfo':
		if(isset($_GET['path']))
		$response = $fm->getinfo($_GET['path']);
        break;

      case 'getfolder':
		if(isset($_GET['path']))
		$response = $fm->getfolder($_GET['path']);
        break;

      case 'rename':
		if(isset($_GET['new']) && isset($_GET['old']))
		$response = $fm->rename($_GET['new'],$_GET['old']);
		break;

      case 'move':
//        // allow "../"
        if($fm->getvar('old') && $fm->getvar('new', 'parent_dir') && $fm->getvar('root')) {
          $response = $fm->move();
        }
        break;
		
      case 'share':
  		if(isset($_GET['path']))
		$response = $fm->shareFile($_GET['path']);
        break;

      case 'delete':
  		if(isset($_GET['path']))
		$response = $fm->deletefolder($_GET['path']);
        break;

      case 'addfolder':
  		if(isset($_GET['path']) && isset($_GET['name']))
		$response = $fm->addfolder($_GET['name'],$_GET['path'],$_GET['time']);
        break;

      case 'download':
  		if(isset($_GET['path']))
		$response = $fm->download($_GET['path']);
        break;
        
      case 'preview':
		if(isset($_GET['path'])) {
			$thumbnail = false;
			if(isset($_GET['thumbnail'])) {
				$thumbnail = true;
			} 
			$fm->preview($thumbnail,$_GET['path']);
		}
        break;
		/*	
      case 'maxuploadfilesize':
        $fm->getMaxUploadFileSize();
        break;*/
    }

  } else if(isset($_POST['mode']) && $_POST['mode']!='') {
    switch($_POST['mode']) {
      default:
        $fm->error($fm->lang('MODE_ERROR'));
        break;
      case 'add':
			$fm->uploadFile($_POST['currentpath'],$_POST['filepath']);
        break;
    }
  }
}

echo json_encode($response);
die();

?>