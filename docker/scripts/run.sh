#!/bin/bash

# Permissions
chmod -R o-r /var/www

# Start up services
/usr/sbin/nginx
memcached -d -u memcache
tail -f /dev/null