shaarlo
=======

Notice : some bash scripts for data update have to be plannified.
Notice : HTTPS is mandatory for API call (hard coded in the api PHP file).

Installation : 
* Git clone project into a web directory (eg. /var/www/shaarlo).

* Create database : Execute shaarlimy.sql content.

* Copy config.php.sample to config.php file and replace configurations with yours.

* Give write rights to data/ and sessions/ directories.

* Access https://../shaarlo/

* Schedule these scripts (eg. crontab it)
5 * * * *  /../bash/do_build_all_rss.sh
6 * * * *  /../bash/update_table_liens.sh


