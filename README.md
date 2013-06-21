shaarlo
=======

Unified Shaarlis Rss 

Installation : 
*Copy all files into ftp server

*Give write rights to data/

*Access http://../shaarlo/

*Schedule refresh.php (eg. crontab it)


Quelques mots : 

(vu que la plupart des shaarlistes sont français (et aussi que je suis une quiche dans ce langage, je m'exprime en français ci dessous).

La page d'admin est protégée par un système de session (désactivable via le config.php). Merci à Bronco (warriordudimanche).

Les flux RSS des shaarlis sont actuellement chargés par le script refresh.php (dans le cas de shaarli.fr, il est lancé toutes les 10 minutes une fois par heure (car OVH ne permet de lancer un script qu'une fois par heure...))

A la base, le nom de domaine shaarli.fr n'était qu'une redirection vers le wiki de sebsauvage, et vu que j'avais la flemme de prendre un nouveau nom de domaine (surtout pour une démo, c'est resté sur celle la).


Voilà, je pense qu'il y a un projet bien plus important que shaarlo qui se trame en ce moment (c'est une supposition), en attendant ça fait déjà un joli aperçu de ce qu'on peut faire avec des flux rss ;-).


Un grand Merci à Sebsauvage.

