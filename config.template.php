<?php
class Config {
  public static $base = 'http://telegraph.dev/';

  public static $ssl = false;
  public static $secretKey = '000000000000000000000000000000000000000000000';

  public static $clientID = 'http://telegraph.dev/';
  public static $defaultAuthorizationEndpoint = 'https://indieauth.com/auth';

  public static $errbitKey = '';
  public static $errbitHost = '';

  public static $db = [
    'host' => '127.0.0.1',
    'database' => 'telegraph',
    'username' => 'root',
    'password' => ''
  ];
}
