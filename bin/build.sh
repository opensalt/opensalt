#!/bin/sh

cd $(dirname $0)/..
PWD=$(pwd)
DIR=/var/www/html

./bin/composer install
./bin/gulp
./bin/console-docker doctrine:migrations:migrate --no-interaction
