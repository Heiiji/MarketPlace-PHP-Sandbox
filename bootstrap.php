<?php

require __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . '/src/exceptions/ErrorHandler.php';

#set_error_handler("exceptions\ErrorHandler");
#set_exception_handler("exceptions\ErrorHandler");

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

header("content-type: application/json; charset=UTF-8");