#Requirements
* Git client
* Web server with php 7
* Composer v2 see [https://getcomposer.org/doc/00-intro.md](https://getcomposer.org/doc/00-intro.md)
* Smtp server

#Installation
```
git clone git@github.com:klelostec/drupguard.git
cd drupguard
composer install
php bin/console drupaguard:install
```

Add the cron job to cron tab
```
* * * * * /path/to/php /path/to/bin/console drupguard:cron --cron-only
```

####Note
In case of multiple php versions installed, you can specify which binary will be used by adding those lines to .env.local :
```
PHP_BIN=php74
COMPOSER_BIN="php74 /usr/local/bin/composer"
CONSOLE_BIN="php74 bin/console"
```
