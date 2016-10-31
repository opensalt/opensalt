#!/bin/sh

cd $(dirname $0)/..
PWD=$(pwd)
DIR=/var/www/html

#docker run --rm -v ${PWD}:${DIR} -w ${DIR} geshan/php-composer-alpine "composer install"
#docker run --rm -v ${PWD}:${DIR} -w ${DIR} mhart/alpine-node npm install
#docker run --rm -v ${PWD}:${DIR} -w ${DIR} mhart/alpine-node node node_modules/gulp/bin/gulp.js

#cd docker
#docker-compose run --rm -u $(id -u) composer install
#docker-compose run --rm -u $(id -u) node npm install
#docker-compose run --rm -u $(id -u) node node_modules/gulp/bin/gulp.js

./bin/composer install
./bin/npm install
./bin/gulp
./bin/console-docker doctrine:migrations:migrate --no-interaction
