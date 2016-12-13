#!/bin/sh

cd $(dirname $0)/..

./bin/console doctrine:migrations:migrate --no-interaction
