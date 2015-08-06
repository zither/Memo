<?php

date_default_timezone_set('Asia/Shanghai');

define("ROOT", dirname(__DIR__));
define("DATA_DIR", __DIR__ . "/data");

$autoloader = require dirname(__DIR__) . "/vendor/autoload.php";
$autoloader->addPsr4("Memo\\Tests\\", __DIR__);

session_start();
