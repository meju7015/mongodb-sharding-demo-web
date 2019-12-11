<?php
include_once __DIR__ . '/../vendor/autoload.php';
include_once "../app/configure/Static.php";
include_once "../app/configure/Config.php";
include_once "../app/configure/DBConfig.php";
include_once "../app/libraries/Bootstrap.php";

$BootStrap = new BootStrap();

$BootStrap->wakeUp();