#!/bin/sh

cd $(dirname $0)/..

./bin/phpcli rm -rf var/cache/{dev,test,prod}/*
# composer install runs the auto-scripts (importmap:install, sass:build,
# asset-map:compile) which build all front-end assets.
./bin/composer install --no-interaction
