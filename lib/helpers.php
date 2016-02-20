<?php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

date_default_timezone_set('UTC');

if(getenv('ENV')) {
  require(dirname(__FILE__).'/../config.'.getenv('ENV').'.php');
} else {
  require(dirname(__FILE__).'/../config.php');
}

function initdb() {
  ORM::configure('mysql:host=' . Config::$db['host'] . ';dbname=' . Config::$db['database']);
  ORM::configure('username', Config::$db['username']);
  ORM::configure('password', Config::$db['password']);
}

function logger() {
  static $log;
  if(!isset($log)) {
    $log = new Logger('name');
    $log->pushHandler(new StreamHandler(dirname(__FILE__).'/../logs/telegraph.log', Logger::DEBUG));
  }
  return $log;
}

function log_info($msg) {
  logger()->addInfo($msg);
}

function log_warning($msg) {
  logger()->addWarning($msg);
}

function view($template, $data=[]) {
  global $templates;
  return $templates->render($template, $data);
}

function q() {
  static $caterpillar = false;
  if(!$caterpillar) {
    $logdir = __DIR__.'/../scripts/logs/';
    $caterpillar = new Caterpillar('telegraph', '127.0.0.1', 11300, $logdir);
  }
  return $caterpillar;
}

function redis() {
  static $client = false;
  if(!$client)
    $client = new Predis\Client('tcp://127.0.0.1:6379');
  return $client;
}

function random_string($len) {
  $charset='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  $str = '';
  $c = strlen($charset)-1;
  for($i=0; $i<$len; $i++) {
    $str .= $charset[mt_rand(0, $c)];
  }
  return $str;
}

// Returns true if $needle is the end of the $haystack
function str_ends_with($haystack, $needle) {
  if($needle == '' || $haystack == '') return false;
  return strpos(strrev($haystack), strrev($needle)) === 0;
}

function display_url($url) {
  return preg_replace(['/^https?:\/\//','/\/$/'], '', $url);
}

function session($k, $default=null) {
  if(!isset($_SESSION)) return $default;
  return array_key_exists($k, $_SESSION) ? $_SESSION[$k] : $default;
}
