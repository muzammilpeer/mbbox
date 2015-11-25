<?php
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

// include the session handler
require_once('MongoSession.php');
/*
		//$mongo = new Mongo("mongodb://admin:RnGP-tB3qlaQ@127.5.246.130:27017/admin");
		//$mongo = new Mongo("mongodb://mbbox:mbbox@172.31.27.88:27017/mbbox");
		//$mongo = new Mongo("mongodb://mbbox:mbbox@ec2-54-200-104-186.us-west-2.compute.amazonaws.com:27017/mbbox");
*/

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