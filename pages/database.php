<?php

if (!isset($_GET['format'], $_GET['token'])) {
    http_response_code(400);
    exit;
}

if ($_GET['token'] != \Config::DOWNLOAD_PASSWORD) {
    http_response_code(401);
    exit;
}
if (\Blacklist\Database::formatExists($_GET['format'])) {
    \Blacklist\Database::print(htmlspecialchars($_GET['format']), isset($_GET['include_expired']), true);
} else {

}
