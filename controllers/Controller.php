<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Controller {

  public $http;

  public function __construct() {
    $this->http = new Telegraph\HTTP();
  }

  private function _is_logged_in(Request $request, Response $response) {
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

  private function _get_role(Request $request) {
    // Default to load their first site, but let the query string override it
    $role = ORM::for_table('roles')->join('sites', 'roles.site_id = sites.id')
      ->where('user_id', session('user_id'))->order_by_asc('sites.created_at')->find_one();

    if($request->get('account')) {
      $role = ORM::for_table('roles')->where('user_id', session('user_id'))->where('site_id', $request->get('account'))->find_one();
      // Check that the user has permission to access this account
      if(!$role) {
        $response->setStatusCode(302);
        $response->headers->set('Location', '/dashboard');
        return false;
      }
    }

    return $role;
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

    if(!$role=$this->_get_role($request, $response)) {
      return $response;
    }

    $site = ORM::for_table('sites')->where_id_is($role->site_id)->find_one();

    $query = ORM::for_table('webmentions')->where('site_id', $site->id)
      ->order_by_desc('created_at')
      ->limit(20)
      ->find_many();

    $webmentions = [];
    foreach($query as $m) {
      $statuses = ORM::for_table('webmention_status')->where('webmention_id', $m->id)->order_by_desc('created_at')->find_many();
      if(count($statuses) == 0) {
        $icon = 'wait';
        $status = 'pending';
      } else {
        $status = $statuses[0]->status;
        switch($status) {
          case 'success':
          case 'accepted':
            $icon = 'checkmark';
            break;
          case 'not_supported':
          case 'error':
            $icon = 'warning';
            break;
          default:
            $icon = '';
        }
      }

      $webmentions[] = [
        'webmention' => $m,
        'statuses' => $statuses,
        'status' => $status,
        'icon' => $icon
      ];
    }

    $response->setContent(view('dashboard', [
      'title' => 'Telegraph Dashboard',
      'user' => $this->_user(),
      'accounts' => $this->_accounts(),
      'site' => $site,
      'webmentions' => $webmentions
    ]));
    return $response;
  }

  public function webmention_details(Request $request, Response $response, $args) {
    if(!$this->_is_logged_in($request, $response)) {
      return $response;
    }

    // Look up the webmention by its token
    $webmention = ORM::for_table('webmentions')->where('token', $args['code'])->find_one();

    if(!$webmention) {
      $response->setContent(view('not-found'));
      return $response;
    }

    $site = ORM::for_table('sites')->where_id_is($webmention->site_id)->find_one();

    $statuses = ORM::for_table('webmention_status')->where('webmention_id', $webmention->id)->order_by_desc('created_at')->find_many();

    $response->setContent(view('webmention-details', [
      'title' => 'Webmention Details',
      'user' => $this->_user(),
      'accounts' => $this->_accounts(),
      'site' => $site,
      'webmention' => $webmention,
      'statuses' => $statuses
    ]));
    return $response;
  }

  public function dashboard_send(Request $request, Response $response) {
    if(!$this->_is_logged_in($request, $response)) {
      return $response;
    }

    if(!$role=$this->_get_role($request, $response)) {
      return $response;
    }

    $site = ORM::for_table('sites')->where_id_is($role->site_id)->find_one();

    $response->setContent(view('webmention-send', [
      'title' => 'Webmention Details',
      'user' => $this->_user(),
      'accounts' => $this->_accounts(),
      'site' => $site,
      'role' => $role,
      'url' => $request->get('url')
    ]));
    return $response;
  }

  public function get_outgoing_links(Request $request, Response $response) {
    if(!$this->_is_logged_in($request, $response)) {
      return $response;
    }

    $sourceURL = $request->get('url');

    $client = new IndieWeb\MentionClient();
    $source = $this->http->get($sourceURL);
    $parsed = \Mf2\parse($source['body'], $sourceURL);

    $links = $client->findOutgoingLinks($parsed);

    $response->headers->set('Content-Type', 'application/json');
    $response->setContent(json_encode([
      'links' => array_values($links)
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
