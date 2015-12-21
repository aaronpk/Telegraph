<?php
namespace Telegraph;
use ORM, Exception;

class Webmention {

  public static function send($id) {
    $w = ORM::for_table('webmentions')->where('id', $id)->find_one();
    if(!$w) {
      echo 'Webmention '.$id.' was not found'."\n";
      return;
    }

    
  }

}
