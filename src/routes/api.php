<?php

use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/CartController.php';
require_once __DIR__ . '/../controllers/ListingController.php';
require_once __DIR__ . '/../controllers/UserController.php';

$dispatcher = simpleDispatcher(function(RouteCollector $r) {
    // Auth routes
    $r->addRoute('POST', '/login', ['AuthController', 'login']);
    $r->addRoute('POST', '/register', ['AuthController', 'register']);
    $r->addRoute('POST', '/refresh-token', ['AuthController', 'refreshToken']);

    // User routes
    $r->addRoute('GET', '/users', ['controllers\UserController', 'getAll']);
    $r->addRoute('GET', '/user/{id:\d+}', ['controllers\UserController', 'getById']);
    $r->addRoute('POST', '/user', ['controllers\UserController', 'create']);
    $r->addRoute('PUT', '/user', ['controllers\UserController', 'update']);
    $r->addRoute('DELETE', '/user/{id:\d+}', ['controllers\UserController', 'delete']);

    // Cart routes
    $r->addRoute('GET', '/cart', ['controllers\CartController', 'get']);
    $r->addRoute('POST', '/cart', ['controllers\CartController', 'add']);
    $r->addRoute('DELETE', '/cart/{id:\d+}', ['controllers\CartController', 'remove']);

    // Listing routes
    $r->addRoute('GET', '/listings', ['controllers\ListingController', 'getAll']);
    $r->addRoute('GET', '/listing/{id:\d+}', ['controllers\ListingController', 'getById']);
    $r->addRoute('POST', '/listing', ['controllers\ListingController', 'create']);
    $r->addRoute('PUT', '/listing/{id:\d+}', ['controllers\ListingController', 'update']);
    $r->addRoute('DELETE', '/listing/{id:\d+}', ['controllers\ListingController', 'delete']);
});

// Fetch method and URI from server variables
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        // ... 404 Not Found
        http_response_code(404);
        echo json_encode(['error' => 'Not Found']);
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        // ... 405 Method Not Allowed
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];

        [$class, $method] = $handler;
        $controller = new $class($user_id);
        $controller->$method($vars);
        break;
}