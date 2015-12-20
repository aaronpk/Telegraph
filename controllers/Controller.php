<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Controller {

  private function _is_logged_in(&$request, &$response) {
    session_start();
    if(!array_key_exists('me', $_SESSION)) {
      session_destroy();
      $response->setStatusCode(302);
      $response->headers->set('Location', '/login?return_to='.$request->getPathInfo());
      return false;
    } else {
      return true;
    }
  }

  public function index(Request $request, Response $response) {
    $response->setContent(view('index', [
      'title' => 'Telegraph'
    ]));
    return $response;
  }

  public function dashboard(Request $request, Response $response) {
    if(!$this->_is_logged_in($request, $response)) {
      return $response;
    }

    $response->setContent(view('dashboard', [
      'title' => 'Dashboard'
    ]));
    return $response;
  }

}
