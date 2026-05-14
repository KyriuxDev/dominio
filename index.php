<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Router.php';

$router = new Router();
$router->dispatch();