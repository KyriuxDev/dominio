<?php
date_default_timezone_set('UTC');
define('TZ_OFFSET', -6); // CST Oaxaca
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Router.php';

$router = new Router();
$router->dispatch();