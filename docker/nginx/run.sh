#!/bin/bash
set -e
 
# Generate configuration using passed environment variable
if [ "x$UPSTREAM_HOST" == "x" ]; then
  UPSTREAM_HOST=${HOSTNAME%%-*}-phpfpm:9000
fi
envsubst < /etc/nginx/upstream_phpcgi_tcp.conf.template > /etc/nginx/upstream_phpcgi_tcp.conf
 
exec nginx -g 'daemon off;'
