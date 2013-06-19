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

La page d'admin n'est pas protégée par un système de session, c'est voulu, tout simplement parce qu'à force de se rajouter des mini projets comme celui ci, 
on se retrouve vite avec mille pages de connexion (et c'est plutot moche à force). Dans l'idéal, il faudrait que cette page soit liée à son compte shaarli 
(mais cela nécessiterait de posséder un compte shaarli sur le même serveur, ce qui n'est pas le cas de shaarli.fr par exemple).

Les flux RSS des shaarlis sont actuellement chargés par le script refresh.php (dans le cas de shaarli.fr, il est lancé toutes les 10 minutes une fois par heure (car OVH ne permet de lancer un script qu'une fois par heure...))

A la base, le nom de domaine shaarli.fr n'était qu'une redirection vers le wiki de sebsauvage, et vu que j'avais la flemme de prendre un nouveau nom de domaine (surtout pour une démo, c'est resté sur celle la).


Voilà, je pense qu'il y a un projet bien plus important que shaarlo qui se trame en ce moment (c'est une supposition), en attendant ça fait déjà un joli aperçu de ce qu'on peut faire avec des flux rss ;-).


Un grand Merci à Sebsauvage.

