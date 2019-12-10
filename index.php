<?php

require 'vendor/autoload.php';

$dsn = "mongodb://mason:1234@192.168.100.69/admin";

$mongoClient = new MongoDB\Client($dsn);

print_r($mongoClient->listDatabases());


?>
