<?php
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

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


?>