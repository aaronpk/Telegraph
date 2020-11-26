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

    // Check if the user's URL defines an authorization endpoint
    $authorizationEndpoint = IndieAuth\Client::discoverAuthorizationEndpoint($me);
    if(!$authorizationEndpoint) {
      $authorizationEndpoint = Config::$defaultAuthorizationEndpoint;
    }

    $codeVerifier = IndieAuth\Client::generatePKCECodeVerifier();
    $state = JWT::encode([
      'az' => $authorizationEndpoint,
      'me' => $me,
      'code_verifier' => $codeVerifier,
      'return_to' => $request->get('return_to'),
      'time' => time(),
      'exp' => time()+300 // verified by the JWT library
    ], Config::$secretKey);

    $authorizationURL = IndieAuth\Client::buildAuthorizationURL($authorizationEndpoint, [
      'me' => $me,
      'redirect_uri' => self::_buildRedirectURI(),
      'client_id' => Config::$clientID,
      'state' => $state,
      'code_verifier' => $codeVerifier,
    ]);

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

    $authorizationEndpoint = $state->az;

    // Verify the code with the auth server
    $data = IndieAuth\Client::exchangeAuthorizationCode($state->az, [
      'code' => $request->get('code'),
      'redirect_uri' => self::_buildRedirectURI(),
      'client_id' => Config::$clientID,
      'code_verifier' => $state->code_verifier,
    ]);

    if(!isset($data['response']['me'])) {
      // The authorization server didn't return a "me" URL
      $response->setContent(view('login', [
        'title' => 'Sign In to Telegraph',
        'error' => 'Invalid Auth Server Response',
        'error_description' => 'The authorization server ('.$authorizationEndpoint.') did not return a valid response:<br><pre style="text-align:left; max-height: 400px; overflow: scroll;">HTTP '.$data['response_code']."\n\n".htmlspecialchars(json_encode($data)).'</pre>'
      ]));
      return $response;
    }

    // Verify the authorization endpoint matches
    if($data['response']['me'] != $state->me) {
      $newAuthorizationEndpoint = IndieAuth\Client::discoverAuthorizationEndpoint($data['response']['me']);
      if($newAuthorizationEndpoint != $authorizationEndpoint) {
        $response->setContent(view('login', [
          'title' => 'Sign In to Telegraph',
          'error' => 'Invalid Authorization Endpoint',
          'error_description' => 'The authorization endpoint for the returned profile URL ('.$data['response']['me'].') did not match the authorization endpoint used to begin the login.'
        ]));
        return $response;
      }
    }

    $me = $data['response']['me'];

    // Create or load the user
    $user = ORM::for_table('users')->where('url', $me)->find_one();
    if(!$user) {
      $user = ORM::for_table('users')->create();
      $user->url = $me;
      $user->created_at = date('Y-m-d H:i:s');
      $user->last_login = date('Y-m-d H:i:s');
      $user->save();

      // Create a site for them with the default role
      $site = ORM::for_table('sites')->create();
      $site->name = 'My Website';
      $site->url = $me;
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
