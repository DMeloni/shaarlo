shaarlo
=======

Unified Shaarlis Rss 

Installation : 
*Copy all files into ftp server (http server tested are apache2 & nginx)

*Give write permission to data/

*Schedule refresh.php + refreshdiscuss.php (eg. crontab it) (wget cron, curl cron, ajax cron, dwarf cron, ovh scheduler ...)

*Configure your shaarlo (rename config.php.sample -> config.php and edit)

*Edit mod/mod.php to enable mod

*Access http://../shaarlo/

*Love life

Quelques mots : 

(vu que la plupart des shaarlistes sont français (et aussi que je suis une quiche dans ce langage, je m'exprime en français ci dessous).

Un système de mod (ou plugin selon la région) a été mis en place pour permettre à tous de produire son petit code (même s'il est foireux, toutes les idées sont bonnes !).
 
Les flux RSS des shaarlis sont actuellement chargés par le script refresh.php (dans le cas de shaarli.fr, il est lancé toutes les 10 minutes une fois par heure (car OVH ne permet de lancer un script qu'une fois par heure...))

A la base, le nom de domaine shaarli.fr n'était qu'une redirection vers le wiki de sebsauvage, et vu que j'avais la flemme de prendre un nouveau nom de domaine (surtout pour une démo, c'est resté sur celle la).


Un grand Merci à Sebsauvage.

### Fonctionnalités
* Aggrégation des flux shaarlis selon l'url 
* Possibilité de lier son Shaarli à son Shaarlo (utiliser le module my_shaarli ou éditer le fichier my_shaarli.txt)
* Possibilité de lier son Respawn à son Shaarlo (utiliser le module my_respawn ou éditer le fichier my_respawn.txt)
* Possibilité d'activer WOT pour la notation des liens partagés (on n'est jamais trop prudent)
* Vision des Top Tags 
* Recherche possible par fulltext / catégorie 
* Recherche de nouveaux flux shaarli à partir de la liste existante 


### Auteurs et contributeurs
Merci aux contributeurs directs : 
* jerrywham (quelqu'un qui a la main sur le coeur !)
* o*gina-rouge (idée de la recherche fulltext)
* Leomaradan (rapporteur de bug)
* tcitworld (aide à la compréhension xd)


Et indirects : 
* Bronco (warriordudimanche) pour son fork de Respawn
* Timo (lehollandaisvolant) pour Respawn (forcément :P)
* Tous les shaarlistes =D

