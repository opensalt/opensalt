#!/bin/sh

cd $(dirname $0)/..

./bin/phpcli rm -rf var/cache/{dev,prod}/*
./bin/composer install --no-interaction
./bin/encore production
./bin/console assets:install web --symlink --relative
