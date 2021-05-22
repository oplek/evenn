#!/bin/bash

# Permissions
chmod -R o-r /var/www

# Set up cron
cat "* * * * * root php /var/www/webroot/engine/run-fetcher.php  2>&1"
cat "* * * * * root php /var/www/webroot/engine/run-proc.php 2>&1"

# Start up services
/usr/sbin/nginx
memcached -d -u memcache
cron &
tail -f /dev/null