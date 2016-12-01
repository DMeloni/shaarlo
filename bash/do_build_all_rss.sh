#!/bin/bash

SHAARLO_HOST='https://www.shaarlo.fr'
LOCKFILE=/root/cron/build.pid

if [ -f /root/cron/build.pid ]
    then
        if [ -d /proc/$(cat ${LOCKFILE}) ]
            then
                echo 'process deja en cours';
                exit;
            else
               echo "suppression ancien process";
               rm /root/cron/build.pid
            fi
fi

echo $$ > /root/cron/build.pid
echo "lancement nouveau process";
wget --timeout=47 "$SHAARLO_HOST/api.php?do=buildAllRss&nbthreads=16&thread=1" > /dev/null
wget --timeout=47 "$SHAARLO_HOST/api.php?do=buildAllRss&nbthreads=16&thread=2" > /dev/null
wget --timeout=47 "$SHAARLO_HOST/api.php?do=buildAllRss&nbthreads=16&thread=3" > /dev/null
wget --timeout=47 "$SHAARLO_HOST/api.php?do=buildAllRss&nbthreads=16&thread=4" > /dev/null
wget --timeout=47 "$SHAARLO_HOST/api.php?do=buildAllRss&nbthreads=16&thread=5" > /dev/null
wget --timeout=47 "$SHAARLO_HOST/api.php?do=buildAllRss&nbthreads=16&thread=6" > /dev/null
wget --timeout=47 "$SHAARLO_HOST/api.php?do=buildAllRss&nbthreads=16&thread=7" > /dev/null
wget --timeout=47 "$SHAARLO_HOST/api.php?do=buildAllRss&nbthreads=16&thread=8" > /dev/null
wget --timeout=47 "$SHAARLO_HOST/api.php?do=buildAllRss&nbthreads=16&thread=9" > /dev/null
wget --timeout=47 "$SHAARLO_HOST/api.php?do=buildAllRss&nbthreads=16&thread=10" > /dev/null
wget --timeout=47 "$SHAARLO_HOST/api.php?do=buildAllRss&nbthreads=16&thread=11" > /dev/null
wget --timeout=47 "$SHAARLO_HOST/api.php?do=buildAllRss&nbthreads=16&thread=12" > /dev/null
wget --timeout=47 "$SHAARLO_HOST/api.php?do=buildAllRss&nbthreads=16&thread=13" > /dev/null
wget --timeout=47 "$SHAARLO_HOST/api.php?do=buildAllRss&nbthreads=16&thread=14" > /dev/null
wget --timeout=47 "$SHAARLO_HOST/api.php?do=buildAllRss&nbthreads=16&thread=15" > /dev/null
wget --timeout=47 "$SHAARLO_HOST/api.php?do=buildAllRss&nbthreads=16&thread=16" > /dev/null
rm api*


