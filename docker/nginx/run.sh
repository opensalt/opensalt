#!/bin/sh
set -e
 
# Generate configuration using passed environment variable
if [ "x$UPSTREAM_HOST" == "x" ]; then
  UPSTREAM_HOST=${HOSTNAME%%-*}-phpfpm:9000
fi
export UPSTREAM_HOST

if [ "x$RESOLVER" == "x" ]; then
  RESOLVER=$(grep 'nameserver' /etc/resolv.conf | head -1 | sed 's/nameserver //')
fi
export RESOLVER

envsubst '$RESOLVER' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf
envsubst '$UPSTREAM_HOST' < /etc/nginx/upstream_phpcgi_tcp.conf.template > /etc/nginx/upstream_phpcgi_tcp.conf

exec nginx -g 'daemon off;'
