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
      $payload = [
        'source' => $webmention->source,
        'target' => $webmention->target,
        'status' => $code
      ];
      if($webmention->webmention_endpoint) {
        $payload['type'] = 'webmention';
      }
      if($webmention->pingback_endpoint) {
        $payload['type'] = 'pingback';
      }

      return self::$http->post($webmention->callback, $payload);
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
      $http = new HTTP();

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
      return self::updateStatus($webmention, null, ($success ? 'accepted' : 'error'));
    }

    // There is a webmention endpoint, send the webmention now

    $webmention->webmention_endpoint = $endpoint;
    $webmention->save();

    $response = $client->sendWebmentionToEndpoint($endpoint, $webmention->source, $webmention->target);

    if(in_array($response['code'], [200,201,202])) {
      $status = 'accepted';

      $webmention->complete = $response['code'] == 200 ? 1 : 0;

      // Check if the endpoint returned a status URL
      if(array_key_exists('Location', $response['headers'])) {
        $webmention->webmention_status_url = \Mf2\resolveUrl($endpoint, $response['headers']['Location']);
        // TODO: queue a job to poll the endpoint for updates and deliver to the callback URL
      } else {
        // No status URL was returned, so we can't follow up with this later. Mark as complete.
        $webmention->complete = 1;
      }

    } else {
      $webmention->complete = 1;
      $status = 'error';
    }

    $webmention->save();

    return self::updateStatus($webmention, $response['code'], $status, $response['body']);
  }

}
