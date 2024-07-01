<?php

use config\Database;
use FastRoute\RouteCollector;
use helpers\Auth;
use helpers\Sections;
use function FastRoute\simpleDispatcher;

require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/CartController.php';
require_once __DIR__ . '/../controllers/EntitlementController.php';
require_once __DIR__ . '/../controllers/ListingController.php';
require_once __DIR__ . '/../controllers/UserController.php';


$dispatcher = simpleDispatcher(function(RouteCollector $r) {
    // Auth routes
    $r->addRoute('POST', '/login', ['controllers\AuthController', 'login', Sections::PUBLIC]);
    $r->addRoute('POST', '/refresh-token', ['controllers\AuthController', 'refreshToken', Sections::PUBLIC]);

    // User routes
    $r->addRoute('GET', '/users', ['controllers\UserController', 'getAll', Sections::PUBLIC]);
    $r->addRoute('GET', '/user/{id:\d+}', ['controllers\UserController', 'getById', Sections::PUBLIC]);
    $r->addRoute('POST', '/user', ['controllers\UserController', 'create', Sections::PUBLIC]);
    $r->addRoute('PUT', '/user', ['controllers\UserController', 'update', Sections::PROTECTED]);
    $r->addRoute('DELETE', '/user/{id:\d+}', ['controllers\UserController', 'delete', Sections::PROTECTED]);

    // Cart routes
    $r->addRoute('GET', '/cart', ['controllers\CartController', 'get', Sections::PROTECTED]);
    $r->addRoute('POST', '/cart', ['controllers\CartController', 'add', Sections::PROTECTED]);
    $r->addRoute('DELETE', '/cart/{id:\d+}', ['controllers\CartController', 'remove', Sections::PROTECTED]);
    $r->addRoute('POST', '/checkout', ['controllers\CartController', 'checkout', Sections::PROTECTED]);

    // Entitlement routes
    $r->addRoute('GET', '/entitlements', ['controllers\EntitlementController', 'get', Sections::PROTECTED]);
    $r->addRoute('POST', '/entitlement', ['controllers\EntitlementController', 'add', Sections::PROTECTED]);
    $r->addRoute('DELETE', '/entitlement/{id:\d+}', ['controllers\EntitlementController', 'remove', Sections::PROTECTED]);

    // Listing routes
    $r->addRoute('GET', '/listings', ['controllers\ListingController', 'getAll', Sections::PUBLIC]);
    $r->addRoute('GET', '/listing/{id:\d+}', ['controllers\ListingController', 'getById', Sections::PUBLIC]);
    $r->addRoute('POST', '/listing', ['controllers\ListingController', 'create', Sections::PROTECTED]);
    $r->addRoute('PUT', '/listing/{id:\d+}', ['controllers\ListingController', 'update', Sections::PROTECTED]);
    $r->addRoute('DELETE', '/listing/{id:\d+}', ['controllers\ListingController', 'delete', Sections::PROTECTED]);
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
        http_response_code(404);
        echo json_encode(['error' => 'Not Found']);
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
        break;
    case FastRoute\Dispatcher::FOUND:
        $database = new Database($_ENV["DB_HOST"], $_ENV["DB_NAME"], $_ENV["DB_USER"], $_ENV["DB_PASS"]);
        $database->getConnection();

        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        [$class, $method, $section] = $handler;

        $user_id = -1;
        if ($section === Sections::PROTECTED) {
            $auth = new Auth();
            if (!$auth->authenticateAccessToken()) {
                exit;
            }
            $user_id = $auth->getUserId();
        }

        $controller = new $class($database, $user_id);
        $controller->$method($vars);
        break;
}