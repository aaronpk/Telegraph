<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class API {

  public $http;

  public function __construct() {
    $this->http = new Telegraph\HTTP();
  }

  private function respond(Response $response, $code, $params, $headers=[]) {
    $response->setStatusCode($code);
    foreach($headers as $k=>$v) {
      $response->headers->set($k, $v);
    }
    $response->headers->set('Content-Type', 'application/json');
    $response->setContent(json_encode($params));
    return $response;
  }

  private static function toHtmlEntities($input) {
    return mb_convert_encoding($input, 'HTML-ENTITIES', mb_detect_encoding($input));
  }

  private static function generateStatusToken() {
    $str = dechex(date('y'));
    $chs = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $len = strlen($chs);
    for($i = 0; $i < 16; $i++) {
      $str .= $chs[mt_rand(0, $len - 1)];
    }
    return $str;
  }

  public function webmention(Request $request, Response $response) {

    # Require the token parameter
    if(!$token=$request->get('token')) {
      return $this->respond($response, 401, [
        'error' => 'authentication_required',
        'error_description' => 'A token is required to use the API'
      ]);
    }

    # Require source and target parameters
    if((!$source=$request->get('source')) || (!$target=$request->get('target'))) {
      return $this->respond($response, 400, [
        'error' => 'missing_parameters',
        'error_description' => 'The source or target parameters were missing'
      ]);
    }

    $urlregex = '/^https?:\/\/[^ ]+\.[^ ]+$/';

    # Verify source and target are URLs
    if(!preg_match($urlregex, $source) || !preg_match($urlregex, $target)) {
      return $this->respond($response, 400, [
        'error' => 'invalid_parameter',
        'error_description' => 'The source or target parameters were invalid'
      ]);
    }

    # If a callback was provided, verify it is a URL
    if($callback=$request->get('callback')) {
      if(!preg_match($urlregex, $source) || !preg_match($urlregex, $target)) {
        return $this->respond($response, 400, [
          'error' => 'invalid_parameter',
          'error_description' => 'The callback parameter was invalid'
        ]);
      }
    }

    # Verify the token is valid
    $role = ORM::for_table('roles')->where('token', $token)->find_one();

    if(!$role) {
      return $this->respond($response, 401, [
        'error' => 'invalid_token',
        'error_description' => 'The token provided is not valid'
      ]);
    }

    # Synchronously check the source URL and verify that it actually contains
    # a link to the target. This way we prevent this API from sending known invalid mentions.
    $sourceData = $this->http->get($source);

    $doc = new DOMDocument();
    @$doc->loadHTML(self::toHtmlEntities($sourceData['body']));

    if(!$doc) {
      return $this->respond($response, 400, [
        'error' => 'source_not_html',
        'error_description' => 'The source document could not be parsed as HTML'
      ]);
    }

    $xpath = new DOMXPath($doc);

    $found = false;
    foreach($xpath->query('//a[@href]') as $href) {
      if($href->getAttribute('href') == $target) {
        $found = true;
        continue;
      }
    }

    if(!$found) {
      return $this->respond($response, 400, [
        'error' => 'no_link_found',
        'error_description' => 'The source document does not have a link to the target URL'
      ]);
    }

    # Everything checked out, so write the webmention to the log and queue a job to start sending

    $w = ORM::for_table('webmentions')->create();
    $w->site_id = $role->site_id;
    $w->created_by = $role->user_id;
    $w->created_at = date('Y-m-d H:i:s');
    $w->token = self::generateStatusToken();
    $w->source = $source;
    $w->target = $target;
    $w->vouch = $request->get('vouch');
    $w->callback = $callback;
    $w->save();

    q()->queue('Telegraph\Webmention', 'send', [$w->id]);

    $statusURL = Config::$base . 'webmention/' . $w->token;

    return $this->respond($response, 201, [
      'status' => 'queued',
      'location' => $statusURL
    ], [
      'Location' => $statusURL
    ]);
  }

  public function webmention_status(Request $request, Response $response, $args) {

    $webmention = ORM::for_table('webmentions')->where('token', $args['code'])->find_one();

    if(!$webmention) {
      return $this->respond($response, 404, [
        'status' => 'not_found',
      ]);
    }

    $status = ORM::for_table('webmention_status')->where('webmention_id', $webmention->id)->order_by_desc('created_at')->find_one();

    $statusURL = Config::$base . 'webmention/' . $webmention->token;

    if(!$status) {
      $code = 'queued';
    } else {
      $code = $status->status;
    }

    $data = [
      'status' => $code,
    ];

    if($webmention->webmention_endpoint) {
      $data['type'] = 'webmention';
      $data['endpoint'] = $webmention->webmention_endpoint;
    }
    if($webmention->pingback_endpoint) {
      $data['type'] = 'pingback';
      $data['endpoint'] = $webmention->pingback_endpoint;
    }

    switch($code) {
      case 'queued':
        $summary = 'The webmention is still in the processing queue';
        break;
      case 'not_supported':
        $summary = 'No webmention or pingback endpoint were found at the target';
        break;
      case 'accepted':
        $summary = 'The '.$data['type'].' request was accepted';
        break;
      default:
        $summary = false;
    }

    if($status && $status->http_code)
      $data['http_code'] = (int)$status->http_code;

    if($summary)
      $data['summary'] = $summary;

    if($webmention->complete == 0)
      $data['location'] = $statusURL;

    return $this->respond($response, 200, $data);
  }

}
