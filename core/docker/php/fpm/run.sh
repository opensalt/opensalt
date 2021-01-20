#!/bin/bash
set -e
 
echo "Enabling APM metrics for ${NR_APP_NAME}"
newrelic-install install
 
# Update the application name
sed -i "s/newrelic.appname = \"PHP Application\"/newrelic.appname = \"${NR_APP_NAME}\"/" /usr/local/etc/php/conf.d/newrelic.ini
 
exec "php-fpm"
