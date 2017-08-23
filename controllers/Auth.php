<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use \Firebase\JWT\JWT;

class Auth {

  public function login(Request $request, Response $response) {
    session_start();
    if(session('user_id')) {
      $response->setStatusCode(302);
      $response->headers->set('Location', '/dashboard');
    } else {
      $response->setContent(view('login', [
        'title' => 'Sign In to Telegraph',
        'return_to' => $request->get('return_to')
      ]));
    }
    return $response;
  }

  public function logout(Request $request, Response $response) {
    session_start();
    if(session('user_id')) {
      $_SESSION['user_id'] = null;
      session_destroy();
    }
    $response->setStatusCode(302);
    $response->headers->set('Location', '/login');
    return $response;
  }

  public function login_start(Request $request, Response $response) {

    if(!$request->get('url') || !($me = IndieAuth\Client::normalizeMeURL($request->get('url')))) {
      $response->setContent(view('login', [
        'title' => 'Sign In to Telegraph',
        'error' => 'Invalid URL',
        'error_description' => 'The URL you entered, "<strong>' . htmlspecialchars($request->get('url')) . '</strong>" is not valid.'
      ]));
      return $response;
    }

    $authorizationEndpoint = IndieAuth\Client::discoverAuthorizationEndpoint($me);

    $state = JWT::encode([
      'me' => $me,
      'authorization_endpoint' => $authorizationEndpoint,
      'return_to' => $request->get('return_to'),
      'time' => time(),
      'exp' => time()+300 // verified by the JWT library
    ], Config::$secretKey);

    if($authorizationEndpoint) {
      // If the user specified only an authorization endpoint, use that
      $authorizationURL = IndieAuth\Client::buildAuthorizationURL($authorizationEndpoint, $me, self::_buildRedirectURI(), Config::$clientID, $state);
    } else {
      // Otherwise, fall back to indieauth.com
      $authorizationURL = IndieAuth\Client::buildAuthorizationURL(Config::$defaultAuthorizationEndpoint, $me, self::_buildRedirectURI(), Config::$clientID, $state);
    }

    $response->setStatusCode(302);
    $response->headers->set('Location', $authorizationURL);
    return $response;
  }

  public function login_callback(Request $request, Response $response) {

    if(!$request->get('state') || !$request->get('code')) {
      $response->setContent(view('login', [
        'title' => 'Sign In to Telegraph',
        'error' => 'Missing Parameters',
        'error_description' => 'The auth server did not return the necessary parameters, <code>state</code> and <code>code</code>.'
      ]));
      return $response;
    }

    // Validate the "state" parameter to ensure this request originated at this client
    try {
      $state = JWT::decode($request->get('state'), Config::$secretKey, ['HS256']);

      if(!$state) {
        $response->setContent(view('login', [
          'title' => 'Sign In to Telegraph',
          'error' => 'Invalid State',
          'error_description' => 'The <code>state</code> parameter was not valid.'
        ]));
        return $response;
      }
    } catch(Exception $e) {
      $response->setContent(view('login', [
        'title' => 'Sign In to Telegraph',
        'error' => 'Invalid State',
        'error_description' => 'The <code>state</code> parameter was invalid:<br>'.htmlspecialchars($e->getMessage())
      ]));
      return $response;
    }

    // Discover the authorization endpoint from the "me" that was returned by the auth server
    // This allows the auth server to return a different URL than the user originally entered,
    // for example if the user enters multiusersite.example the auth server can return multiusersite.example/alice
    if($state->authorization_endpoint) { // only use the discovered endpoint if one was originally found
      $authorizationEndpoint = $state->authorization_endpoint;
    } else {
      $authorizationEndpoint = Config::$defaultAuthorizationEndpoint;
    }

    // Verify the code with the auth server
    $token = IndieAuth\Client::verifyIndieAuthCode($authorizationEndpoint, $request->get('code'), $state->me, self::_buildRedirectURI(), Config::$clientID, true);

    if(!array_key_exists('auth', $token) || !array_key_exists('me', $token['auth'])) {
      // The auth server didn't return a "me" URL
      $response->setContent(view('login', [
        'title' => 'Sign In to Telegraph',
        'error' => 'Invalid Auth Server Response',
        'error_description' => 'The authorization server ('.$authorizationEndpoint.') did not return a valid response:<br><pre style="text-align:left; max-height: 400px; overflow: scroll;">HTTP '.$token['response_code']."\n\n".htmlspecialchars($token['response']).'</pre>'
      ]));
      return $response;
    }

    // Create or load the user
    $user = ORM::for_table('users')->where('url', $token['auth']['me'])->find_one();
    if(!$user) {
      $user = ORM::for_table('users')->create();
      $user->url = $token['auth']['me'];
      $user->created_at = date('Y-m-d H:i:s');
      $user->last_login = date('Y-m-d H:i:s');
      $user->save();

      // Create a site for them with the default role
      $site = ORM::for_table('sites')->create();
      $site->name = 'My Website';
      $site->url = $token['auth']['me'];
      $site->created_by = $user->id;
      $site->created_at = date('Y-m-d H:i:s');
      $site->save();

      $role = ORM::for_table('roles')->create();
      $role->site_id = $site->id;
      $role->user_id = $user->id;
      $role->role = 'owner';
      $role->token = random_string(32);
      $role->save();

    } else {
      $user->last_login = date('Y-m-d H:i:s');
      $user->save();
    }

    q()->queue('Telegraph\ProfileFetcher', 'fetch', [$user->id]);

    session_start();
    $_SESSION['user_id'] = $user->id;
    $response->setStatusCode(302);
    $response->headers->set('Location', ($state->return_to ?: '/dashboard'));
    return $response;
  }

  private static function _buildRedirectURI() {
    return Config::$base . 'login/callback';
  }

}
