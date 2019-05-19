<?php

require 'vendor/autoload.php';
require 'Config.php';

use Blacklist\DatabaseConnection;
use Blacklist\Auth\Auth;

session_start();

// Try database connection
try {
    $database = (new DatabaseConnection())->connect();
} catch (PDOException $e) {
    http_response_code(503);
    echo "Can't connect to database";
    exit(1);
}

$auth = new Auth();

// If page not defined, redirect to /request
if (!isset($_GET['page'])) {
    http_response_code(301);
    header('Location: ' . Config::BASE_URL . '/request');
    exit(0);
}

// Remove all special chars from page slug
$page = preg_replace("/[^A-Za-z0-9\/]/", '', $_GET['page']);

// Include page if exists
if (file_exists('pages/' . $page . '.php')) {
    if ($auth->isAllowed($page)) {
        include 'pages/' . $page . '.php';
    } else {
        http_response_code(403);
        echo 'Your not allowed to access this page';
    }
} else {
    http_response_code(404);
    echo 'Not found';
    exit(1);
}
