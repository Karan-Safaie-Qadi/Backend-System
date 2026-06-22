<?php

require_once __DIR__ . '/vendor/autoload.php';

use Database\Database;
use App\Core\Config;

Database::setRootPath(__DIR__);

Config::load(__DIR__ . '/config');

date_default_timezone_set('Asia/Tehran');
