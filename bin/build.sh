#!/bin/sh

cd $(dirname $0)/..
PWD=$(pwd)
DIR=/var/www/html

./bin/composer install
./bin/gulp
./bin/console doctrine:migrations:migrate --no-interaction
./bin/console assets:install web --symlink --relative
