<?php

declare(strict_types=1);

require __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . '/src/exceptions/ErrorHandler.php';

set_error_handler(['exceptions\ErrorHandler', 'handleError']);
set_exception_handler(['exceptions\ErrorHandler', 'handleException']);

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

header("content-type: application/json; charset=UTF-8");

require __DIR__ . '/src/routes/api.php';