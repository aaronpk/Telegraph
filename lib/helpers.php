<?php
date_default_timezone_set('UTC');

if(array_key_exists('ENV', $_ENV)) {
  require(dirname(__FILE__).'/../config.'.$_ENV['ENV'].'.php');
} else {
  require(dirname(__FILE__).'/../config.php');
}

ORM::configure('mysql:host=' . Config::$db['host'] . ';dbname=' . Config::$db['database']);
ORM::configure('username', Config::$db['username']);
ORM::configure('password', Config::$db['password']);

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

function random_string($len) {
  $charset='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  $str = '';
  $c = strlen($charset)-1;
  for($i=0; $i<$len; $i++) {
    $str .= $charset[mt_rand(0, $c)];
  }
  return $str;
}
