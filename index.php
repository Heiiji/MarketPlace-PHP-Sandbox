<?php

declare(strict_types=1);

use config\Database;
use controllers\CartController;
use controllers\ListingController;
use controllers\UserController;
use gateways\CartGateway;
use gateways\ListingGateway;
use gateways\UserGateway;
use helpers\Auth;
use helpers\JWTCodec;

require __DIR__ . '/bootstrap.php';

$path =  parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$parts = explode('/', $path);

$resource = $parts[1];

$id = $parts[2] ?? null;

$database = new Database($_ENV["DB_HOST"], $_ENV["DB_NAME"], $_ENV["DB_USER"], $_ENV["DB_PASS"]);
$database->getConnection();

$user_gateway = new UserGateway($database);

$codec = new JWTCodec($_ENV["SECRET_KEY"]);
$auth = new Auth($user_gateway, $codec);
if (!$auth->authenticateAccessToken()) {
    exit;
}

$user_id = $auth->getUserId();

require __DIR__ . '/src/routes/api.php';
exit;




$cart_gateway = new CartGateway($database);

$listing_gateway = new ListingGateway($database);
$listingController = new ListingController($listing_gateway, $user_id);
$userController = new UserController($user_gateway, $user_id);
$cartController = new CartController($cart_gateway, $user_id);

switch ($resource) {
    case "listings":
        $listingController->processRequest($_SERVER['REQUEST_METHOD'], $id);
        break;
    case "users":
        $userController->processRequest($_SERVER['REQUEST_METHOD'], $id);
        break;
    case "cart":
        $cartController->processRequest($_SERVER['REQUEST_METHOD'], $id);
        break;
    default:
        http_response_code(404);
        exit;
}