<?php
namespace Telegraph;
use ORM, Exception;
use IndieWeb\MentionClient;

class Webmention {

  private static $http = false;

  // Returns false if the target URL is known to not accept webmentions
  public static function isProbablySupported($targetURL) {
    // Reject links that are known to not accept webmentions
    $host = str_replace('www.','',parse_url($targetURL, PHP_URL_HOST));

    if(!$host) return false;

    $unsupported = [
      'twitter.com',
      'instagram.com',
      'facebook.com',
      'meetup.com',
      'eventbrite.com',
      'github.com',
      'gitlab.com',
    ];

    if(in_array($host, $unsupported))
      return false;

    if(preg_match('/.+\.amazonaws\.com/', $host))
      return false;

    return true;
  }

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
        'status' => $code,
      ];
      if($webmention->webmention_endpoint) {
        $payload['type'] = 'webmention';
      } elseif($webmention->pingback_endpoint) {
        $payload['type'] = 'pingback';
      }

      if($status->http_code) {
        $payload['http_code'] = $status->http_code;
      }
      if($raw) {
        $payload['http_body'] = $raw;
      }

      echo "Sending result to callback URL: $webmention->callback\n";
      return self::$http->post($webmention->callback, $payload);
    }
  }

  public static function send($id, $client=false, $http=false) {
    initdb();

    $webmention = ORM::for_table('webmentions')->where('id', $id)->find_one();
    if(!$webmention) {
      echo 'Webmention '.$id.' was not found'."\n";
      return;
    }
    
    echo "Processing webmention $id from ".$webmention->source."\n";

    if(!$client)
      $client = new MentionClient();

    if(!$http)
      $http = new HTTP();

    self::$http = $http;

    echo "Finding webmention endpoint for $webmention->target\n";

    // Discover the webmention or pingback endpoint
    $endpoint = $client->discoverWebmentionEndpoint($webmention->target);

    if(!$endpoint) {
      echo "No webmention endpoint, checking for pingback\n";
      
      // If no webmention endpoint found, try to send a pingback
      $pingbackEndpoint = $client->discoverPingbackEndpoint($webmention->target);

      // If no pingback endpoint was found, we can't do anything else
      if(!$pingbackEndpoint) {
        echo "No webmention or pingback endpoint found\n";
        return self::updateStatus($webmention, null, 'not_supported');
      }

      echo "Found pingback endpoint $pingbackEndpoint\n";
      $webmention->pingback_endpoint = $pingbackEndpoint;
      $webmention->save();
      
      echo "Sending pingback now...\n";
      $success = $client->sendPingbackToEndpoint($pingbackEndpoint, $webmention->source, $webmention->target);
      echo "Result: ".($success?'accepted':'error')."\n";
      return self::updateStatus($webmention, null, ($success ? 'accepted' : 'error'));
    }

    echo "Found webmention endpoint $endpoint\n";

    // There is a webmention endpoint, send the webmention now

    $webmention->webmention_endpoint = $endpoint;
    $webmention->save();

    $params = [];
    if($webmention->code) {
      $params['code'] = $webmention->code;
      if($webmention->realm)
        $params['realm'] = $webmention->realm;
    }
    if($webmention->vouch) {
      $params['vouch'] = $webmention->vouch;
    }
    
    echo "Sending webmention now...\n";
    
    $response = $client->sendWebmentionToEndpoint($endpoint, $webmention->source, $webmention->target, $params);

    echo "Response code: ".$response['code']."\n";

    if(in_array($response['code'], [200,201,202])) {
      $status = 'accepted';

      $webmention->complete = $response['code'] == 200 ? 1 : 0;

      // Check if the endpoint returned a status URL
      if(array_key_exists('Location', $response['headers'])) {
        $webmention->webmention_status_url = \Mf2\resolveUrl($endpoint, $response['headers']['Location']);
        echo "Endpoint returned a status URL: ".$webmention->webmention_status_url."\n";
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

    $result = self::updateStatus($webmention, $response['code'], $status, $response['body']);
    echo "Done\n";
    $pdo = ORM::get_db();
    $pdo = null;
    return $result;
  }

}
