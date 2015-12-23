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

    // If there is an account in the query string, set the session variable and redirect back to the dashboard
    if($request->get('account') || !session('account')) {
      // Check that the user has permission to access this account
      $role = ORM::for_table('roles')->where('user_id', session('user_id'))->where('site_id', $request->get('account'))->find_one();
      if(!$role) {
        $role = ORM::for_table('roles')->join('sites', 'roles.site_id = sites.id')
          ->where('user_id', session('user_id'))->order_by_asc('sites.created_at')->find_one();
      }
      $_SESSION['account'] = $role->site_id;
      $response->setStatusCode(302);
      $response->headers->set('Location', '/dashboard');
      return $response;
    }


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
