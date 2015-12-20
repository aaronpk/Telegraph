<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use \Firebase\JWT\JWT;

class Auth {

  public function login(Request $request, Response $response) {
    $response->setContent(view('login', [
      'title' => 'Sign In to Telegraph',
      'return_to' => $request->get('return_to')
    ]));
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

    if(!$request->get('state') || !$request->get('code') || !$request->get('me')) {
      $response->setContent(view('login', [
        'title' => 'Sign In to Telegraph',
        'error' => 'Missing Parameters',
        'error_description' => 'The auth server did not return the necessary parameters, <code>state</code> and <code>code</code> and <code>me</code>.'
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
    if($state->authorization_endpoint) { // only discover the auth endpoint if one was originally found, otherwise use our fallback
      $authorizationEndpoint = IndieAuth\Client::discoverAuthorizationEndpoint($request->get('me'));
    } else {
      $authorizationEndpoint = Config::$defaultAuthorizationEndpoint;
    }

    // Verify the code with the auth server
    $token = IndieAuth\Client::verifyIndieAuthCode($authorizationEndpoint, $request->get('code'), $request->get('me'), self::_buildRedirectURI(), Config::$clientID, $request->get('state'), true);

    if(!array_key_exists('auth', $token) || !array_key_exists('me', $token['auth'])) {
      // The auth server didn't return a "me" URL
      $response->setContent(view('login', [
        'title' => 'Sign In to Telegraph',
        'error' => 'Invalid Auth Server Response',
        'error_description' => 'The authorization server did not return a valid response:<br>'.htmlspecialchars(json_encode($token))
      ]));
      return $response;
    }

    // Create or load the user

    session_start();
    $_SESSION['me'] = $token['auth']['me'];
    $response->setStatusCode(302);
    $response->headers->set('Location', ($state->return_to ?: '/dashboard'));
    return $response;
  }

  private static function _buildRedirectURI() {
    return 'http' . (Config::$ssl ? 's' : '') . '://' . $_SERVER['SERVER_NAME'] . '/login/callback';
  }

}
