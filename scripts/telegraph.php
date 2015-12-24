<?php
chdir('..');
include('vendor/autoload.php');

q()->run_workers(4);
