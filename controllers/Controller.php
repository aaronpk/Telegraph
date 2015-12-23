<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Controller {

  private function _is_logged_in(&$request, &$response) {
    session_start();
    if(!session('user_id')) {
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

    // Default to load their first site, but let the query string override it
    $role = ORM::for_table('roles')->join('sites', 'roles.site_id = sites.id')
      ->where('user_id', session('user_id'))->order_by_asc('sites.created_at')->find_one();

    if($request->get('account')) {
      $role = ORM::for_table('roles')->where('user_id', session('user_id'))->where('site_id', $request->get('account'))->find_one();
      // Check that the user has permission to access this account
      if(!$role) {
        $response->setStatusCode(302);
        $response->headers->set('Location', '/dashboard');
        return $response;
      }
    }

    $site = ORM::for_table('sites')->where_id_is($role->site_id);

    $response->setContent(view('dashboard', [
      'title' => 'Telegraph Dashboard',
      'user' => $this->_user(),
      'accounts' => $this->_accounts()
    ]));
    return $response;
  }

  private function _user() {
    return ORM::for_table('users')->where_id_is(session('user_id'))->find_one();
  }

  private function _accounts() {
    return ORM::for_table('sites')->join('roles', 'roles.site_id = sites.id')
      ->where('roles.user_id', session('user_id'))
      ->find_many();
  }
}
