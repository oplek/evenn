#!/bin/bash

# Start up services
/usr/sbin/nginx
memcached -d -u memcache
tail -f /dev/null