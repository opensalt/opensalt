#!/bin/sh

cd $(dirname $0)/..

./bin/composer install --no-interaction
./bin/encore production
./bin/console assets:install web --symlink --relative
./bin/console cache:clear --env=dev --no-warmup
./bin/console cache:clear --env=prod --no-warmup
