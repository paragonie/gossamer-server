<?php
declare(strict_types=1);
namespace ParagonIE\GossamerServer;
use FastRoute\Dispatcher;
use GuzzleHttp\Psr7\ServerRequest;
use ParagonIE\GossamerServer\Handlers\DefaultHandler;

define('GOSSAMER_SERVER_ROOT', dirname(__DIR__));

require_once GOSSAMER_SERVER_ROOT . '/vendor/autoload.php';

/** @var array $settings */
$settings = require_once GOSSAMER_SERVER_ROOT . '/src/settings.php';

/** @var Dispatcher $dispatcher */
$dispatcher = require_once GOSSAMER_SERVER_ROOT . '/src/routes.php';

// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$request = ServerRequest::fromGlobals();
$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case Dispatcher::NOT_FOUND:
        $handler = (new DefaultHandler)
            ->init($settings)
            ->setStatusCode(404);
        send_response($handler($request));
        // ... 404 Not Found
        break;
    case Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        // ... 405 Method Not Allowed
        $handler = (new DefaultHandler)
            ->init($settings)
            ->setStatusCode(405);
        send_response($handler($request));
        break;
    case Dispatcher::FOUND:
        $handlerName = $routeInfo[1];
        $vars = $routeInfo[2];
        /** @var HandlerInterface&HandlerTrait $handler */
        $handler = new $handlerName;
        $handler->init($settings);
        send_response($handler($request));
}
