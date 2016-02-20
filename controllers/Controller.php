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
      $response->headers->set('Location', '/login?return_to='.$request->getRequestURI());
      return false;
    } else {
      return true;
    }
  }

  private function _get_role(Request $request, Response $response) {
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

  public function api(Request $request, Response $response) {
    session_start();
    if(session('user_id')) {
      $role = $this->_get_role($request, $response);
      $site = ORM::for_table('sites')->where_id_is($role->site_id)->find_one();
    } else {
      $role = false;
      $site = false;
    }

    $response->setContent(view('api', [
      'title' => 'Telegraph API Documentation',
      'user' => $this->_user(),
      'accounts' => $this->_accounts(),
      'site' => $site,
      'role' => $role,
      'return_to' => $request->getRequestURI()
    ]));
    return $response;
  }

  private static function _icon_for_status($status) {
    switch($status) {
      case 'success':
      case 'accepted':
        return 'green checkmark';
      case 'not_supported':
        return 'yellow x';
      case 'error':
        return 'red x';
      case 'pending':
        return 'orange wait';
      default:
        return '';
    }
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
        $status = 'pending';
      } else {
        $status = $statuses[0]->status;
      }
      $icon = self::_icon_for_status($status);

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
      'role' => $role,
      'webmentions' => $webmentions
    ]));
    return $response;
  }

  public function webmention_details(Request $request, Response $response, $args) {
    session_start();

    // Look up the webmention by its token
    $webmention = ORM::for_table('webmentions')->where('token', $args['code'])->find_one();

    if(!$webmention) {
      $response->setContent(view('not-found'));
      return $response;
    }

    $site = ORM::for_table('sites')->where_id_is($webmention->site_id)->find_one();

    $statuses = ORM::for_table('webmention_status')->where('webmention_id', $webmention->id)->order_by_desc('created_at')->find_many();

    if(count($statuses) == 0) {
      $status = 'pending';
    } else {
      $status = $statuses[0]->status;
    }
    $icon = self::_icon_for_status($status);

    $response->setContent(view('webmention-details', [
      'title' => 'Webmention Details',
      'user' => $this->_user(),
      'accounts' => $this->_accounts(),
      'site' => $site,
      'webmention' => $webmention,
      'statuses' => $statuses,
      'icon' => $icon,
      'status' => $status
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

    $links = array_values($client->findOutgoingLinks($parsed));

    $response->headers->set('Content-Type', 'application/json');
    $response->setContent(json_encode([
      'links' => $links
    ]));
    return $response;
  }

  public function discover_endpoint(Request $request, Response $response) {
    if(!$this->_is_logged_in($request, $response)) {
      return $response;
    }

    $targetURL = $request->get('target');

    // Reject links that are known to not accept webmentions
    $host = str_replace('www.','',parse_url($targetURL, PHP_URL_HOST));

    $unsupported = [
      'twitter.com',
      'instagram.com',
      'facebook.com',
    ];

    if(!$host || in_array($host, $unsupported) || preg_match('/.+\.amazonaws\.com/', $host)) {
      $status = 'none';
      $cached = -1;
    } else {
      // Cache the discovered result
      $cacheKey = 'telegraph:discover_endpoint:'.$targetURL;
      if($request->get('ignore_cache') == 'true' || (!$status = redis()->get($cacheKey))) {
        $client = new IndieWeb\MentionClient();
        $endpoint = $client->discoverWebmentionEndpoint($targetURL);
        if($endpoint) {
          $status = 'webmention';
        } else {
          $endpoint = $client->discoverPingbackEndpoint($targetURL);
          if($endpoint) {
            $status = 'pingback';
          } else {
            $status = 'none';
          }
        }
        $cached = false;
        redis()->setex($cacheKey, 600, $status);
      } else {
        $cached = true;
      }
    }

    $response->headers->set('Content-Type', 'application/json');
    $response->setContent(json_encode([
      'status' => $status,
      'cached' => $cached
    ]));
    return $response;
  }

  private function _user() {
    if(!session('user_id')) return null;
    return ORM::for_table('users')->where_id_is(session('user_id'))->find_one();
  }

  private function _accounts() {
    return ORM::for_table('sites')->join('roles', 'roles.site_id = sites.id')
      ->where('roles.user_id', session('user_id'))
      ->find_many();
  }
}
