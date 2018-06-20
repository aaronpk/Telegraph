<?php
namespace Telegraph;

use DOMXPath, DOMDocument;

class FindLinks {

  public static function all($input) {
    if(is_string($input)) {
      return self::inHTML($input);
    } elseif(is_array($input)) {
      $links = [];
      // This recursively iterates over the whole input array and searches for
      // everything that looks like a URL regardless of its depth or property name.
      // For items with a key of "html", it parses the value as HTML instead of text.
      // This supports handling the XRay parsed result format
      foreach(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($input)) as $key => $value) {
        if($key === 'html') {
          $links = array_merge($links, self::inHTML($value));
        }
        else {
          $links = array_merge($links, self::inText($value));
        }
      }
      return array_unique($links);
    } else {
      return [];
    }
  }

  /**
   * find all links in text.
   * @param $input string text block
   * @return mixed array of links in text block.
   */
  public static function inText($input) {
    if(!is_string($input)) return [];
    preg_match_all('/https?:\/\/[^ ]+/', $input, $matches);
    return array_unique($matches[0]);
  }

  /**
   * find all links in text.
   * @param $input string text block
   * @return mixed array of links in text block.
   */
  public static function inHTML($html) {
    if(!is_string($input)) return [];

    $doc = new DOMDocument();
    libxml_use_internal_errors(true); # suppress parse errors and warnings
    @$doc->loadHTML(self::toHtmlEntities($html), LIBXML_NOWARNING|LIBXML_NOERROR);
    libxml_clear_errors();
    if(!$doc) return [];
    $xpath = new DOMXPath($doc);

    $links = [];
    foreach($xpath->query('//a[@href]') as $href) {
      $links[] = $href->getAttribute('href');
    }

    return array_unique($links);
  }

  private static function toHtmlEntities($input) {
    return mb_convert_encoding($input, 'HTML-ENTITIES', mb_detect_encoding($input));
  }

}
