# DrupGuard
DrupGuard is a tool which analyse your Drupal projects. It use git to checkout the project, composer to build it.  
After that, it search for drupal core, modules and themes installed version and check updates using Drupal's update status infrastructure (see [https://www.drupal.org/drupalorg/docs/apis/update-status-xml](https://www.drupal.org/drupalorg/docs/apis/update-status-xml) for details).  
The dashboard created after each analyses allow you to keep an eye on all your projects and be warned when security fix is available for one of your component.  
Each project's analysis can be execute periodically and a report can be sent by email.

![Screenshot](./screen.png?raw=true "Screenshot")


## Requirements
* Git client
* Web server with php 7
* Composer v2 see [https://getcomposer.org/doc/00-intro.md](https://getcomposer.org/doc/00-intro.md)
* Smtp server

## Installation
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

## Note
In case of multiple php versions installed, you can specify which binary will be used by adding those lines to .env.local :
```
PHP_BINARY=/usr/local/bin/php74
COMPOSER_BINARY="/usr/local/bin/composer"
CONSOLE_BINARY="bin/console"
```

## Licence
[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
