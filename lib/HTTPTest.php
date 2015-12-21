<?php
namespace Telegraph;

class HTTPTest {

  public static function get($url) {
    return self::_read_file($url);
  }

  public static function post($url, $body, $headers=array()) {
    return self::_read_file($url);
  }

  public static function head($url) {
    $response = self::_read_file($url);
    return array(
      'code' => $response['code'],
      'headers' => $response['headers']
    );
  }

  private static function _read_file($url) {
    $filename = dirname(__FILE__).'/../tests/data/'.preg_replace('/https?:\/\//', '', $url);
    if(!file_exists($filename)) {
      $filename = dirname(__FILE__).'/../tests/data/404.response.txt';
    }
    $response = file_get_contents($filename);

    $split = explode("\r\n\r\n", $response);
    if(count($split) != 2) {
      throw new \Exception("Invalid file contents in test data, check that newlines are CRLF: $url");
    }
    list($headers, $body) = $split;

    if(preg_match('/HTTP\/1\.1 (\d+)/', $headers, $match)) {
      $code = $match[1];
    }

    $headers = preg_replace('/HTTP\/1\.1 \d+ .+/', '', $headers);

    return array(
      'code' => $code,
      'headers' => self::parse_headers($headers),
      'body' => $body
    );
  }

  public static function parse_headers($headers) {
    $retVal = array();
    $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $headers));
    foreach($fields as $field) {
      if(preg_match('/([^:]+): (.+)/m', $field, $match)) {
        $match[1] = preg_replace_callback('/(?<=^|[\x09\x20\x2D])./', function($m) {
          return strtoupper($m[0]);
        }, strtolower(trim($match[1])));
        // If there's already a value set for the header name being returned, turn it into an array and add the new value
        $match[1] = preg_replace_callback('/(?<=^|[\x09\x20\x2D])./', function($m) {
          return strtoupper($m[0]);
        }, strtolower(trim($match[1])));
        if(isset($retVal[$match[1]])) {
          if(!is_array($retVal[$match[1]]))
            $retVal[$match[1]] = array($retVal[$match[1]]);
          $retVal[$match[1]][] = $match[2];
        } else {
          $retVal[$match[1]] = trim($match[2]);
        }
      }
    }
    return $retVal;
  }
}
