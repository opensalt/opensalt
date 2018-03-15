#!/bin/sh

cd $(dirname $0)/..

./bin/phpcli rm -rf var/cache/{dev,test,prod}/*
./bin/composer install --no-interaction
./bin/encore production
