<?php
namespace Telegraph;
use ORM, Exception, Mf2;

class ProfileFetcher {

  public static function fetch($id) {
    // Fetch the user's home page and look for profile information there
    $user = ORM::for_table('users')->where_id_is($id)->find_one();
    echo "Looking for representative h-card for ".$user->url."\n";
    $data = HTTP::get($user->url);
    $parsed = Mf2\parse($data['body'], $user->url);
    $representative = Mf2\HCard\representative($parsed, $user->url);
    if($representative) {
      echo "Found it!\n";
      print_r($representative);
      if(array_key_exists('name', $representative['properties'])) {
        $user->name = $representative['properties']['name'][0];
      }
      if(array_key_exists('photo', $representative['properties'])) {
        $user->photo = $representative['properties']['photo'][0];
      }
      $user->save();
    }
  }

}
