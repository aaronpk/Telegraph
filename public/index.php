<?php
chdir('..');
include('vendor/autoload.php');

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
$router = new League\Route\RouteCollection;
$templates = new League\Plates\Engine(dirname(__FILE__).'/../views');

$router->addRoute('GET', '/', 'Controller::index');
$router->addRoute('GET', '/dashboard', 'Controller::dashboard');
$router->addRoute('GET', '/api', 'Controller::api');

$router->addRoute('POST', '/webmention', 'API::webmention');
$router->addRoute('GET', '/webmention/{code}', 'API::webmention_status');

$router->addRoute('GET', '/login', 'Auth::login');
$router->addRoute('GET', '/logout', 'Auth::logout');
$router->addRoute('POST', '/login/start', 'Auth::login_start');
$router->addRoute('GET', '/login/callback', 'Auth::login_callback');



$dispatcher = $router->getDispatcher();
$request = Request::createFromGlobals();
$response = $dispatcher->dispatch($request->getMethod(), $request->getPathInfo());
$response->send();
