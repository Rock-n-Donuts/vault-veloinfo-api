<?php

require __DIR__ . '/vendor/autoload.php';

use Rockndonuts\Hackqc\Controllers\APIController;
use Rockndonuts\Hackqc\Controllers\UserController;
use Rockndonuts\Hackqc\Controllers\ContributionController;
use Rockndonuts\Hackqc\Models\DB;

const APP_PATH = __DIR__;


if (gethostname() === "Luc-Oliviers-MacBook-Pro.local") {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
} else {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/../'); // server, set file out of webroot
}
$dotenv->load();

$db = null;

function DB(): DB
{
    global $db;
    if (!$db) {
        $db = new DB();
    }
    return $db;
}

function getSeason()
{
    return 'winter';
}

$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {

    $controller = new APIController();
    $userController = new UserController();
    $contributionController = new ContributionController();

    $r->addRoute('POST', '/auth', [$userController, 'createUser']);

    $r->addRoute('GET', '/contribution', [$contributionController, 'get']);
    $r->addRoute('POST', '/contribution', [$contributionController, 'createContribution']);

    $r->addRoute('GET', '/troncons', [$controller, 'getTroncons']);
    $r->addRoute('GET', '/update', [$controller, 'updateData']);
    $r->addRoute('GET', '/debug', [$controller, 'validateGeobase']);
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