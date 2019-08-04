# blacklist

Your personal ip blacklist management interface for postfix

## Requirements

- php 7.2 with PDO sqlite

## Install

1. Install [composer](https://getcomposer.org/) and git
1. Clone the repository to your webserver :
    ```bash
    git clone https://gitlab.com/remyj38/blacklist.git /var/www/html/
    ```
1. Copy `Config-dist.php` to `Config.php` and edit it with your parameters
1. Copy `htaccess-dist` to `.htaccess` and edit it with your parameters
1. Run composer dependencies install:
    ```bash
    composer install --no-dev
    ```
1. Connect on the interface (http://my_base_url/user/login) with admin/admin  
    Think about changing your username and password ;)
    **Be careful, OTP is automatically activated after first login**

## Update

1. In your root directory:
    ```bash
    git reset --hard
    git pull
    composer update --no-dev
    ```

## Postfix configuration

1. Get the postfix formatted database URL on your blacklist admin page (http://your-url/database/download)
1. Copy the `updatePostfix.sh` script in scripts folders to a location (`/root/updatePostfix.sh` for example)
1. Allow execution of the script:
    ```bash
    chmod +x /root/updatePostfix.sh
    ```
1. Edit `updatePostfix.sh` and update `DATABASE_URL` variable with your database URL
1. Run the script to check that everything is OK and create the first version of the database
    ```bash
    /root/updatePostfix.sh
    ```
1. Create a cron entry to schedule an automatic update of your database (each hours in the example)
    ```bash
    sudo crontab -e
    # Insert the following line and save the file
    0 * * * * /root/updatePostfix.sh
    ```
1. Edit /etc/postfix/main.cf and modify or add the `smtpd_client_restrictions` parameter to add the followed check:
    ```
    check_client_access hash:/etc/postfix/blacklist
    ```
1. Restart postfix
