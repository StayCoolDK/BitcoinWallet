Bitcoin Wallet - Bachelor Project.

Requirements:

Bitcoin Core (https://bitcoincore.org) - let it synchronize the blockchain to 100%, may take a while.

Composer, NPM, MySQL & PHP 7.2.

Installation:

Composer install

npm install

./node_modules/.bin/encore dev --watch (builds assets and watches for changes)

php bin/console doctrine:database:create 

php bin/console doctrine:schema:update

Make sure your .env file is configured with MySQL and your email settings.

Configure web server to run the Symfony project: https://symfony.com/doc/current/setup/web_server_configuration.html

Restart web server and keep Bitcoin Core running in the background.

Have fun!
