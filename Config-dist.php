<?php

class Config {

    /**
     * Root URL of the site
     */
    const BASE_URL = '';

    const CONTACT_CONFIG = array(
        'recipients' => array(                            // Recipients for the mail.
            'hostmaster@example.com',
        ),
        'host' => 'smtp1.example.com;smtp2.example.com',  // Specify main and backup SMTP servers. If null use php mail command
        'username' => 'user@example.com',                 // SMTP username. If null, disable authentication
        'password' => 'secret',                           // SMTP password
        'secure' => 'tls',                                // Enable TLS encryption, `ssl` also accepted. If null, disable encryption
        'port' => 587,                                    // TCP port to connect to server
        'allowed_attachments' => array(                   // Allow only email source. Format: mime => extension (for error message)
            'message/rfc822' => '.eml',
        ),
    );

    /**
     * Path to the SQLite3 database
     */
    const DATABASE_PATH = 'db/database.db';

    /**
     * Default date format
     * http://php.net/manual/function.date.php
     */
    const DATE_FORMAT = 'Y-m-d';

    /**
     * Default expiration for new entries
     * See php documentation for syntax
     * http://php.net/manual/datetime.formats.relative.php
     */
    const DEFAULT_EXPIRATION = '+6 months';

    /**
     * Password userd to download the blacklist
     */
    const DOWNLOAD_PASSWORD = 'download-password';

    /**
     * Postfix error code for blacklisted IPs
     */
    const POSTFIX_CODE = 541;

    /**
     * Message returned by postfix for blacklisted IPs
     */
    const POSTFIX_MESSAGE = "Your IP is blacklisted. More info: " . self::BASE_URL;
    /**
     * String used to salt password. All passwords will be invalid if modified
     * You can generate a salt by using : openssl rand -hex 20
     */
    const SALT = '';

    /**
     * Title for pages
     */
    const SITE_NAME = 'My Personnal Blacklist';
}
