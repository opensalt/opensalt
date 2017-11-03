#!/bin/bash

cd $(dirname $0)/../../docker

docker-compose down -v --remove-orphans

if [ -f .env,orig ]; then
  mv .env,orig .env
fi

if [ -f docker-compose.yml,orig ]; then
  mv docker-compose.yml,orig docker-compose.yml
fi
