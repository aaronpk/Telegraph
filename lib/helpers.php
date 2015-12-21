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
