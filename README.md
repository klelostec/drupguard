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
symfony composer install
yarn encore production
php bin/console drupguard:install
```

Add the cron job to cron tab
```
* * * * * /path/to/php /path/to/bin/console drupguard:cron --cron-only
```

### Docksal

For docksal users :
```
fin p start
fin bash
wget https://get.symfony.com/cli/installer -O - | bash
sudo mv /home/docker/.symfony/bin/symfony /usr/local/bin/symfony
symfony composer install
yarn encore production
symfony console drupguard:install
#mysql://user:user@db:3306/default?serverVersion=5.7
#smtp://mail:1025
#/usr/local/bin/php
#/usr/local/bin/composer
``` 


## Licence
[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
