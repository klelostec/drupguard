# DrupGuard

Drupguard est un outil développé par Kévin Le lostec qui analyse les projets drupal.
Il utilise git pour récupérer les sources et composer pour builder le projet.
Après cela il cherche les versions du core Drupal, des modules contrib et des themes installés pour vérifier la présence
de mises à jour (voir [https://www.drupal.org/drupalorg/docs/apis/update-status-xml](https://www.drupal.org/drupalorg/docs/apis/update-status-xml) pour plus de détails).
le tableau de bord créé après chaque analyse permet de garder un oeil sur l'ensemble de vos projets et d'être averti
quand une mise à jour de sécurité est disponible pour une de vos composants.
Chaque analyse de projet peut être exécutée périodiquement et les rapports peuvent être envoyés par Email

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
yarn install
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
fin p up
fin symfony composer install
fin yarn install
fin yarn encore production
fin symfony console drupguard:install
#mysql://user:user@db:3306/default?serverVersion=5.7
#smtp://mail:1025
#/usr/local/bin/php
#/usr/local/bin/composer
``` 

## Environnement

**Site** : http://klelostec-drupguard.dev.klee.lan.net

**Site Klee** : http://drupguard.dev.klee.lan.net

## Gestion de projet

**Lead développeur** : Kévin Le Lostec ([kevin.lelostec@kleegroup.com](mailto:kevin.lelostec@kleegroup.com))

**Rédaction readme fr** : Clément Cuisinier ([clement.cuisinier@kleegroup.com](mailto:clement.cuisinier@kleegroup.com))

**Répertoire projet** : https://github.com/klelostec/drupguard

## mise en place d'un rapport

![Screenshot](./exampleConfig.png?raw=true "config")

**Name** : Le libelle du projet s'affichant dans l'interface du site.
exemple : Tenup D7

**Machine name** : Un nom unique utilisé pour la génération des rapports.
exemple : tenup_fft_d7

**Git remote repository** : Le répository du projet à renseigner au format ssh

**Git branch** la branche sur laquelle vous voulez faire l'analyse. 
(exception klee : si vous ne voyez pas la liste des branches c'est que vous n'avez pas inclus l'utilisateur PIC-Ki dans votre projet)
page -/project_members

**Drupal directory** : le dossier contenant l'installation Drupal.
exemple : /www

**Need email** : permet d'activer l'envoi d'Email après génération du rapport	

**Email level** : permet de choisir quel niveau rapport sera envoyé par mail :
Error => Envoi la liste des modules nécessitant un patch de sécurité
Warning => Envoi la liste des modules nécessitant un patch de sécurité et les modules disposant patchs
Succes => envoi le rapport complet généré par le site

**Email extra** : permet d'ajouter la liste de difusion des mails de rapport.

**Has cron** : permet d'activer un build récurent des rapports

**Cron frequency** : permet de gérer la récurrence du build cron
tuto pour rédiger un cron : https://crontab.guru/

**Allowed users** : permet de rajouter des utilisateurs a la liste de diffusion du rapport
(tuto : ctrl + clic pour ajouter plusieurs personnes)

**Ignored modules** : permet d'ajouter la liste des modules à ne pas analyser pour le rapport
(par défaut la liste prend l'ensemble des modules contrib et les themes).

## Lecture d'un rapport

![Screenshot](./screen.png?raw=true "Screenshot")

Un rapport est constitué de plusieurs lignes de couleurs différentes :

![Screenshot](./exampleRed.png?raw=true "red")

Les lignes rouges sont les modules nécéssitant au plus vite au moins un patch de sécurité, elle sont à traiter en priorité par les developpeurs.

![Screenshot](.exampleYellow.png?raw=true "yellow")

Les lignes jaunes mettent en évidence des modules dont les mises à jour sont disponibles, il est recommandé de mettre à jour ces modules pour 

![Screenshot](.exampleGreen.png?raw=true "green")

Les lignes jaunes mettent en évidence des modules dont les mises à jour sont disponibles, il est recommandé de mettre à jour ces modules pour

![Screenshot](.exampleGrey.png?raw=true "grey")

Les lignes jaunes mettent en évidence des modules dont les mises à jour sont disponibles, il est recommandé de mettre à jour ces modules pour

## Licence
[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
