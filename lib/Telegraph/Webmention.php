<?php
namespace Telegraph;
use ORM, Exception;
use IndieWeb\MentionClient;

class Webmention {

  private static $http = false;

  private static function updateStatus($webmention, $http_code, $code, $raw=null) {
    $status = ORM::for_table('webmention_status')->create();
    $status->webmention_id = $webmention->id;
    $status->created_at = date('Y-m-d H:i:s');
    if($http_code)
      $status->http_code = $http_code;
    $status->status = $code;
    if($raw)
      $status->raw_response = $raw;
    $status->save();

    // Post to the callback URL if one is set
    if($webmention->callback) {
      return self::$http->post($webmention->callback, [
        'source' => $webmention->source,
        'target' => $webmention->target,
        'status' => $code
      ]);
    }
  }

  public static function send($id, $client=false, $http=false) {
    $webmention = ORM::for_table('webmentions')->where('id', $id)->find_one();
    if(!$webmention) {
      echo 'Webmention '.$id.' was not found'."\n";
      return;
    }

    if(!$client)
      $client = new MentionClient();

    if(!$http)
      $http = new Telegraph\HTTP();

    self::$http = $http;

    // Discover the webmention or pingback endpoint
    $endpoint = $client->discoverWebmentionEndpoint($webmention->target);

    if(!$endpoint) {
      // If no webmention endpoint found, try to send a pingback
      $pingbackEndpoint = $client->discoverPingbackEndpoint($webmention->target);

      // If no pingback endpoint was found, we can't do anything else
      if(!$pingbackEndpoint) {
        return self::updateStatus($webmention, null, 'not_supported');
      }

      $webmention->pingback_endpoint = $pingbackEndpoint;
      $webmention->save();

      $success = $client->sendPingbackToEndpoint($pingbackEndpoint, $webmention->source, $webmention->target);
      return self::updateStatus($webmention, null, ($success ? 'pingback_accepted' : 'pingback_error'));
    }

    // There is a webmention endpoint, send the webmention now

    $webmention->webmention_endpoint = $endpoint;
    $webmention->save();

    $response = $client->sendWebmentionToEndpoint($endpoint, $webmention->source, $webmention->target);

    if(in_array($response['code'], [200,201,202])) {
      $status = 'webmention_accepted';

      // Check if the endpoint returned a status URL
      if(array_key_exists('Location', $response['headers'])) {
        $webmention->webmention_status_url = \Mf2\resolveUrl($endpoint, $response['headers']['Location']);
        $webmention->save();
      }

    } else {
      $status = 'webmention_error';
    }

    return self::updateStatus($webmention, $response['code'], $status, $response['body']);
  }

}
