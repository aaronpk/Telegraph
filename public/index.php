<?php
chdir('..');
include('vendor/autoload.php');

use Errbit\Errbit;

if(Config::$errbitHost) {
  Errbit::instance()
  ->configure(array(
    'api_key' => Config::$errbitKey,
    'host' => Config::$errbitHost,
    'port' => 443,
    'secure' => true
  ))
  ->start();
}

initdb();

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
$router = new League\Route\RouteCollection;
$templates = new League\Plates\Engine(dirname(__FILE__).'/../views');

$router->addRoute('GET', '/', 'Controller::index');
$router->addRoute('GET', '/dashboard', 'Controller::dashboard');
$router->addRoute('GET', '/send-a-webmention', 'Controller::send_a_webmention');
$router->addRoute('GET', '/site/new', 'Controller::new_site');
$router->addRoute('GET', '/site/edit', 'Controller::new_site');
$router->addRoute('POST', '/site/save', 'Controller::save_site');
$router->addRoute('GET', '/api', 'Controller::api');
$router->addRoute('GET', '/superfeedr', 'Controller::superfeedr');

$router->addRoute('GET', '/webmention/{code}/details', 'Controller::webmention_details');
$router->addRoute('GET', '/dashboard/send', 'Controller::dashboard_send');
$router->addRoute('POST', '/dashboard/get_outgoing_links.json', 'Controller::get_outgoing_links');
$router->addRoute('POST', '/dashboard/discover_endpoint.json', 'Controller::discover_endpoint');

$router->addRoute('POST', '/webmention', 'API::webmention');
$router->addRoute('POST', '/superfeedr/{token}', 'API::superfeedr_tracker');
$router->addRoute('GET', '/webmention/{code}', 'API::webmention_status');

$router->addRoute('GET', '/login', 'Auth::login');
$router->addRoute('GET', '/logout', 'Auth::logout');
$router->addRoute('POST', '/login/start', 'Auth::login_start');
$router->addRoute('GET', '/login/callback', 'Auth::login_callback');

$dispatcher = $router->getDispatcher();
$request = Request::createFromGlobals();

try {
  $response = $dispatcher->dispatch($request->getMethod(), $request->getPathInfo());
  $response->send();
} catch(League\Route\Http\Exception\NotFoundException $e) {
  $response = new Response;
  $response->setStatusCode(404);
  $response->setContent("Not Found\n");
  $response->send();
} catch(League\Route\Http\Exception\MethodNotAllowedException $e) {
  $response = new Response;
  $response->setStatusCode(405);
  $response->setContent("Method not allowed\n");
  $response->send();
}
