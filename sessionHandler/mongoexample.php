<?php
// include the session handler
require_once('MongoSession.php');
$config = array(
    // cookie related vars
    'cookie_path'   => '/',
    'cookie_domain' => '.localhost', // .mydomain.com

    // session related vars
    'lifetime'      => 3600,        // session lifetime in seconds
    'database'      => 'session',   // name of MongoDB database
    'collection'    => 'session',   // name of MongoDB collection

    // persistent related vars
    'persistent'    => false,           // persistent connection to DB?
    'persistentId'  => 'MongoSession',  // name of persistent connection

    // whether we're supporting replicaSet
    'replicaSet'        => false,

    // array of mongo db servers
    'connectionString'      => 'mongodb://mbbox:mbbox@localhost:27017/session'
);

$session = new MongoSession($config);


// load the session
//$session = new MongoSession();

// store session data
$_SESSION['views']= ($_SESSION['views']) + 1;
$_SESSION['login']="sdfsdf";
$_SESSION['testing']="muzamilpeer";
//retrieve session data
echo "Pageviews=". $_SESSION['views'];
?>
