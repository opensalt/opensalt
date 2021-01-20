#!/bin/bash

cd $(dirname $0)/..

./bin/build.sh
./bin/db_migrate.sh
