<?php
namespace Telegraph;
use ORM, Exception;
use IndieWeb\MentionClient;

class Webmention {

  private static function saveStatus($webmentionID, $http_code, $code, $raw=null) {
    $status = ORM::for_table('webmention_status')->create();
    $status->webmention_id = $webmentionID;
    $status->created_at = date('Y-m-d H:i:s');
    if($http_code)
      $status->http_code = $http_code;
    $status->status = $code;
    if($raw)
      $status->raw_response = $raw;
    $status->save();
  }

  public static function send($id, $client=false) {
    $webmention = ORM::for_table('webmentions')->where('id', $id)->find_one();
    if(!$webmention) {
      echo 'Webmention '.$id.' was not found'."\n";
      return;
    }

    if(!$client)
      $client = new MentionClient();

    // Discover the webmention or pingback endpoint
    $endpoint = $client->discoverWebmentionEndpoint($webmention->target);

    if(!$endpoint) {
      // If no webmention endpoint found, try to send a pingback
      $pingbackEndpoint = $client->discoverPingbackEndpoint($webmention->target);

      // If no pingback endpoint was found, we can't do anything else
      if(!$pingbackEndpoint) {
        self::saveStatus($id, null, 'not_supported');
        return;
      }

      $webmention->pingback_endpoint = $pingbackEndpoint;
      $webmention->save();

      $success = $client->sendPingbackToEndpoint($pingbackEndpoint, $webmention->source, $webmention->target);
      self::saveStatus($id, null, ($success ? 'pingback_accepted' : 'pingback_error'));
      return;
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

    self::saveStatus($webmention->id, $response['code'], $status, $response['body']);
  }

}
