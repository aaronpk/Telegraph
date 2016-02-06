<?php
chdir('..');
include('vendor/autoload.php');

q()->run_workers(array_key_exists(1, $argv) ? $argv[1] : 4);
