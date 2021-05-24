#!/bin/bash

# Permissions
chmod -R o-xw,o+r /var/www
chmod -R u+rw /var/www/webroot/engine/cache
chmod -R u+rw /var/www/webroot/engine/log

# Start up services
/usr/sbin/nginx
memcached -d -u memcache
service cron start
tail -f /dev/null