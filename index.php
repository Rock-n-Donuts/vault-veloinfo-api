<?php

use Rockndonuts\Hackqc\Models\DB;

const APP_PATH = __DIR__;

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$db = null;

function DB(): DB
{

    if (!$db) {
        $db = new DB();
    }
    return $db;
}

function getSeason()
{
    return 'winter';
}

$controller = new \Rockndonuts\Hackqc\Controllers\APIController();
$userController = new \Rockndonuts\Hackqc\Controllers\UserController();

$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) use ($controller, $userController) {
    $r->addRoute('POST', '/auth', [$userController, 'createUser']);
    $r->addRoute('POST', '/contribution', [$controller, 'createContribution']);
    $r->addRoute('GET', '/troncons', [$controller, 'getTroncons']);
    $r->addRoute('GET', '/debug', [$controller, 'getCyclableData']);
});

// Fetch method and URI from somewhere
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
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        // ... 405 Method Not Allowed
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        call_user_func_array($handler, $vars);
        // ... call $handler with $vars
        break;
}