<?php

declare(strict_types=1);

require dirname(__DIR__) . "/vendor/autoload.php";

set_error_handler("ErrorHandler::handleError");
set_exception_handler("ErrorHandler::handleException");

$dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$path =  parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$parts = explode('/', $path);

$resource = $parts[2];

$id = $parts[3] ?? null;

if ($resource != "listings") {
    http_response_code(404);
    exit;
}

$api_key = $_SERVER["HTTP_X_API_KEY"];

header("content-type: application/json; charset=UTF-8");

$database = new Database($_ENV["DB_HOST"], $_ENV["DB_NAME"], $_ENV["DB_USER"], $_ENV["DB_PASS"]);

$database->getConnection();

$listing_gateway = new ListingGateway($database);
$controller = new ListingController($listing_gateway);

$controller->processRequest($_SERVER['REQUEST_METHOD'], $id);