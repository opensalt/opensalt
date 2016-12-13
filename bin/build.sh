#!/bin/sh

cd $(dirname $0)/..

./bin/composer install --no-interaction
./bin/gulp
./bin/console assets:install web --symlink --relative
