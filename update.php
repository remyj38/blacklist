<?php

require 'vendor/autoload.php';
require 'Config.php';

use Blacklist\DatabaseConnection;

// Try database connection
try {
    /* @var $database \PDO */
    $database = (new DatabaseConnection())->connect();
} catch (PDOException $e) {
    http_response_code(503);
    echo "Can't connect to database";
    exit(1);
}

// Get Database schema version
$dbVersion = $database->query('PRAGMA user_version')->fetchColumn();
$schemaUpdates = scandir('schema/');
foreach ($schemaUpdates as $schemaUpdate) {
    $version = preg_replace('/\\.[^.\\s]{3,4}$/', '', $schemaUpdate);
    if ($dbVersion < $version) {
        $script = file_get_contents('schema/' . $schemaUpdate);
        if ($database->exec($script) === false) {
            echo 'Error during database upgrade on ' . $schemaUpdate;
            exit(1);
        }
        $database->exec('PRAGMA user_version = ' . $version);
        $dbVersion = $version;
        if ($dbVersion == 1) {
            $user = new Blacklist\Auth\User();
            $user
                    ->setPassword('admin', true)
                    ->setUsername('admin')
                    ->setIsAdmin(true)
                    ->save();
        }
    }
}
