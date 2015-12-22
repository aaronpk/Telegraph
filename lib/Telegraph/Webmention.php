<?php
namespace Telegraph;
use ORM, Exception;
use IndieWeb\MentionClient;

class Webmention {

  private static function saveStatus($webmentionID, $code, $raw=null) {
    $status = ORM::for_table('webmention_status')->create();
    $status->webmention_id = $webmentionID;
    $status->created_at = date('Y-m-d H:i:s');
    $status->status = $code;
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
        self::saveStatus($id, 'not_supported');
        return;
      }

      $webmention->pingback_endpoint = $pingbackEndpoint;
      $webmention->save();

      $success = $client->sendPingbackToEndpoint($pingbackEndpoint, $webmention->source, $webmention->target);
      self::saveStatus($id, $success ? 'pingback_success' : 'pingback_error');
      return;
    }




  }

}
